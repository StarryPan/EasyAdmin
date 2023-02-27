<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Refresh
 * @package app\index\controller\server;
 * @ControllerAnnotation(title="刷新缓存")
 */
class Refresh extends AdminController
{
    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->serverModel = new ServerList();
    }

    /**
     * @NodeAnotation(title="刷新缓存")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'ref_type|刷新类型不能为空'  => 'require',
                'server_id|服务器ID不能为空' => 'require',
            ];

            $this->validate($post, $rule);

            try {

                // 请求服务器配置
                $api_url  = getApiUrl($post['server_id'], $post['ref_type']);
                $url_data = getHttp($api_url, getTmSecKey($post));
 
                return json($url_data);
            } catch (\Exception $e) {

                $this->error($e->getMessage());
            }
        }

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerList();
        $this->assign( 'serverList', $serverList );

        return $this->fetch();
    }

}
