<?php

namespace app\index\controller\user;

use think\App;
use app\index\model\ServerList;
use app\index\model\SystemAdmin;
use app\index\service\TriggerService;
use app\common\constants\AdminConstant;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class List
 * @package app\index\controller\List;
 * @ControllerAnnotation(title="用户列表")
 */
class Lists extends AdminController
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
                list($page, $limit, $where, $excludeFields) = $this->buildTableCurlParames('user_basic', ['server_id']);
                $params   = ['page' => $page, 'limit' => $limit, 'where' => $where];

                // 请求服务器配置
                $api_url  = getApiUrl($excludeFields['server_id'], 'getUserList');
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

    /**
     * @NodeAnotation(title="冻结")
     */
    public function freeze($id, $status, $server_id)
    {
        if ($this->request->isAjax()) {
            try {
                if (isNull($server_id)) {
                    $this->error('请选择服务器');
                }

                // 请求服务器配置
                $api_url  = getApiUrl($server_id, 'freezeUserStatus');
                $url_data = getHttp($api_url, getTmSecKey([
                    'ids'    => $id,
                    'status' => $status
                ]));
                return json($url_data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();

        if (isNull($post['server_id'])) {
            $this->error('请选择服务器');
        }

        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);

        try {

            // 状态
            $status   = ($post['value'] == 0) ? 2 : 1;

            // 请求服务器配置
            $api_url  = getApiUrl($post['server_id'], 'freezeUserStatus');
            $url_data = getHttp($api_url, getTmSecKey([
                'ids'    => [$post['id']],
                'status' => $status
            ]));
            return json($url_data);
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }
}
