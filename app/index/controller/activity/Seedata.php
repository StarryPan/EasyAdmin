<?php

namespace app\index\controller\activity;

use think\App;
use app\index\model\ServerList;
use app\index\model\QuestConfig;
use app\index\model\SystemAdmin;
use app\index\model\ActivityConfig;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Seedata
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="查看活动")
 */
class Seedata extends AdminController
{
    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ServerList();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            if (input('selectFields')) {
                return $this->selectList();
            }

            try {
                list($page, $limit, $where, $excludeFields) = $this->buildTableCurlParames('cfg_activity', ['server_id']);
                $params   = ['page' => $page, 'limit' => $limit, 'where' => $where];

                // 请求服务器配置
                $api_url  = getApiUrl($excludeFields['server_id'], 'getActivityConfigList');
                $url_data = getHttp($api_url, getTmSecKey($params, false));
                return json($url_data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取服务器列表
        $serverList = ServerList::getAuthServerNameList(true);
        $this->assign('serverList', $serverList);

        // 获取活动类型列表
        $this->assign('atypeList', json_encode(ActivityConfig::$atypeList, JSON_UNESCAPED_UNICODE));

        // 获取活动开启类型
        $this->assign('openTypes', json_encode(ActivityConfig::$openTypes, JSON_UNESCAPED_UNICODE));
        
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();
        $rule = [
            'id|ID' => 'require',
            'field|字段' => 'require',
            'value|值' => 'require',
            'server_id|服务器' => 'require',
        ];
        $this->validate($post, $rule);

        try {
            
            // 请求服务器配置
            $api_url  = getApiUrl($post['server_id'], 'modifyActivityConfig');
            $url_data = getHttp($api_url, getTmSecKey($post));
            return json($url_data);
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id, $server_id)
    {
        if (empty($id)) {

            $this->error('参数为空');
        }

        if (empty($server_id)) {

            $this->error('服务器ID不能为空');
        }

        try {

            // 请求服务器配置
            $api_url  = getApiUrl($server_id, 'deleteActivityConfig');
            $url_data = getHttp($api_url, getTmSecKey(['id' => $id]));
            return json($url_data);
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }
}
