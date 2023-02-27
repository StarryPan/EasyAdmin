<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Clean
 * @package app\index\controller\server;
 * @ControllerAnnotation(title="服务器清档")
 */
class Clean extends AdminController
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
                'server_id|服务器ID不能为空' => 'require',
            ];
            !empty($post['is_captcha']) && $rule['captcha|验证码'] = 'require|captcha';
            $this->validate($post, $rule);

            ini_set('memory_limit', '-1'); // 无线运行内存
            ini_set('max_execution_time', 72000); // 永远不停

            try {

                // 请求服务器配置
                $api_url  = getApiUrl($post['server_id'], 'cleanServerData');
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
