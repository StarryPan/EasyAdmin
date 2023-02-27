<?php

namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Logs
 * @package app\index\controller\List;
 * @ControllerAnnotation(title="服务器日志")
 */
class Logs extends AdminController
{

    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
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
                list($page, $limit, $where, $excludeFields) = $this->buildTableCurlParames('server_err_log', ['month', 'server_id']);
                $params   = ['page' => $page, 'limit' => $limit, 'where' => $where, 'month' => $excludeFields['month']];

                // 请求服务器配置
                $api_url  = getApiUrl($excludeFields['server_id'], 'getServerLogs');
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

        return $this->fetch();
    }
}
