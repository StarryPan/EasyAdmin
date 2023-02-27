<?php

namespace app\index\controller\user;

use think\App;
use app\index\model\ServerList;
use app\index\model\GameConfig;
use app\index\model\TransManager;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Logquery
 * @package app\index\controller\Logquery;
 * @ControllerAnnotation(title="查询日志")
 */
class Logquery extends AdminController
{

    use \app\index\traits\Curd;

    /**
     * 排序规则
     *
     * @var array
     */
    protected $sort = [
        'id' => 'desc',
    ];
    

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // 初始化模型
        $this->serverModel = new ServerList();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index($server_id = '', $table_name = '')
    {
        if ($this->request->isPost()) {

            $post = $this->request->post();
            $rule = [
                'server_id|服务器ID'  => 'require',
                'table_name|查询表名' => 'require',
            ];
            $this->validate($post, $rule);
            
            if (input('selectFields')) {

                return $this->selectList();
            }

            try {
                $params   = ['table_name' => $post['table_name']];

                // 请求获取日志表注释
                $api_url  = getApiUrl($post['server_id'], 'getUserLogTableComment');
                $url_data = getHttp($api_url, getTmSecKey($params, false));

                // 修改返回数据
                $comments = $url_data['data'];
                $url_data['data'] = [
                    'comment' => $comments,
                    'cfgs'    => GameConfig::getCommonConfig('loganalysis')
                ];
                return json($url_data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        if ($server_id && $table_name) {
                
            try {
                list($page, $limit, $where, $excludeFields) = $this->buildTableCurlParames($table_name, ['month'], false);
                $params   = ['page' => $page, 'limit' => $limit, 'where' => $where, 'month' => $excludeFields['month'], 'table_name' => $excludeFields['table_name']];

                // 请求服务器配置
                $api_url  = getApiUrl($server_id, 'getUserLogs');
                $url_data = getHttp($api_url, getTmSecKey($params, false));
                return json($url_data);

            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取配置
        $this->assign('cfgs', GameConfig::getCommonConfig('logquery'));

        // 查询参数
        $this->assign('query', $this->request->post());

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="刷新日志表")
     */
    public function refreshLogTableList()
    {
        $post = $this->request->post();
        $rule = [
            'server_id|服务器ID' => 'require',
        ];
        $this->validate($post, $rule);
        
        if (input('selectFields')) {

            return $this->selectList();
        }

        try {
            $params   = [];

            // 请求获取日志表注释
            $api_url  = getApiUrl($post['server_id'], 'getLogTableNameList');
            $url_data = getHttp($api_url, getTmSecKey($params, false));

            // 保存配置
            GameConfig::saveConfig('LogTable', $url_data['data']);
            
            return json([
                'code'  => 0,
                'msg'   => '更新成功',
                'count' => -1,
                'data'  => [],
            ]);
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }
}
