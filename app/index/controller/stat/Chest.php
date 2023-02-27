<?php

namespace app\index\controller\stat;

use think\App;
use app\index\model\GameConfig;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Chest
 * @package app\index\controller\Chest;
 * @ControllerAnnotation(title="抽卡模拟")
 */
class Chest extends AdminController
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
     * @NodeAnotation(title="抽卡")
     */
    public function index()
    {
        $get = $this->request->get();

        if (!empty($get['server_id']) && !empty($get['cfg_id'])) {

            ini_set('memory_limit', '-1'); // 无线运行内存
            ini_set('max_execution_time', 72000); // 永远不停
            
            $rule = [
                'count|抽卡次数'      => 'require',
                'cfg_id|抽卡配置'     => 'require',
                'server_id|服务器ID'  => 'require',
            ];
            $this->validate($get, $rule);
            try {
                $params   = ['id' => $get['cfg_id'], 'count' => $get['count']];
    
                // 请求获取日志表注释
                $api_url  = getApiUrl($get['server_id'], 'imitateChest');
                $url_data = getHttp($api_url, getTmSecKey($params));
                return json($url_data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取配置
        $this->assign('cfgs', GameConfig::getCommonConfig('chest'));

        // 查询参数
        $this->assign('query', $this->request->post());

        return $this->fetch();
    }

}
