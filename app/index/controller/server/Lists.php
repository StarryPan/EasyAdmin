<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use app\index\model\SystemAdmin;
use app\index\model\ServerGroup;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Lists
 * @package app\index\controller\server
 * @ControllerAnnotation(title="服务器列表")
 */
class Lists extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    protected $sort = [
        'id'   => 'asc',
        'sort' => 'asc',
    ];

    /**
     * 受保护的字段
     *
     * @var array
     */
    protected $protect_field = [
        'lan',
        'act',
        'ips',
        'status',
        'server_id',
        'server_open',
        'activity_time',
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

            list($page, $limit, $where) = $this->buildTableParames();

            $server = $this->model->where($where);

            if (!SystemAdmin::checkAuthMax()) {

                $server = $server->whereIn('id', SystemAdmin::getAuthServerId());
            }

            $list = $server->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $server->count(),
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'id|服务器ID'       => 'require',
                'name|服务器名称'   => 'require',
                'shost|短连接地址'  => 'require',
                'lhost|长连接地址'  => 'require',
            ];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();

            if ($save) {

                // 遍历
                foreach ($row as $val) {
                    // 删除渠道里的服务器
                    ServerGroup::removeServerId($val->id);
                }
            }

        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        $row = $this->model->find($post['id']);
        empty($row) && $this->error('数据不存在');
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * @NodeAnotation(title="获取服务器状态")
     *
     * @return void
     */
    public function status()
    {
        $post = $this->request->post();

        try {
            // 请求服务器配置
            $api_url  = getApiUrl($post['id'], 'getServerStatus');
            $url_data = getHttp($api_url, getTmSecKey([], false));
            return json($url_data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($e->getCode() == 1) {
                $msg = '请求错误';
            }
            return $this->error($msg);
        }
    }
    
    /**
     * @NodeAnotation(title="获取服务器配置")
     *
     * @return void
     */
    public function config()
    {
        if ($this->request->isAjax()) {

            $get = $this->request->get();

            if (empty($get['id'])) {

                $this->error('服务器ID，不能为空。');
            }

            try {
                // 请求服务器配置
                $api_url  = getApiUrl($get['id'], 'getServerConfig');
                $url_data = getHttp($api_url, getTmSecKey([], false));

                // 设置不分页
                $url_data['count'] = 0;

                return json($url_data);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg'   => '服务器错误【 找不到配置 】',
                    'count' => 0,
                    'data'  => []
                ]);
            }
        } else {
            $get = $this->request->get();
            $this->assign('id', $get['id']);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="服务器配置添加")
     */
    public function config_add()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'id|服务器ID不能为空'  => 'require',
                'g_key|键名不能为空'   => 'require',
                'g_value|键值不能为空' => 'require',
            ];
            $this->validate($post, $rule);

            try {

                // 请求服务器，添加配置
                $api_url  = getApiUrl($post['id'], 'saveServerConfig');
                getHttp($api_url, getTmSecKey($post));

                $save = true;
            } catch (\Exception $e) {

                $this->error($e->getMessage());
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        } else {

            $get = $this->request->get();
            $this->assign('id', $get['id']);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="服务器配置修改")
     */
    public function config_modify($id)
    {
        if (isNull($id)) {
            $this->error('服务器ID不能为空。');
        }

        $post = $this->request->post();
        $rule = [
            'g_key|字段不能为空' => 'require',
        ];
        $this->validate($post, $rule);

        try {

            // 请求服务器，添加配置
            $api_url  = getApiUrl($id, 'saveServerConfig');
            getHttp($api_url, getTmSecKey($post));

            $save = true;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
        }

        $save ? $this->success('保存成功') : $this->error('保存失败');
    }

    /**
     * 服务器配置删除
     *@NodeAnotation(title="服务器配置删除")
     * 
     * @param integer $id
     * @param string $g_key
     * @return void
     */
    public function config_delete()
    {
        if ($this->request->isAjax()) {

            $get  = $this->request->get();
            $rule = [
                'id|服务器ID不能为空'  => 'require',
                'g_key|键名不能为空'   => 'require',
            ];
            $this->validate($get, $rule);
            $save = false;

            if (in_array($get['g_key'], $this->protect_field)) {
                $this->error('受保护的配置，不能删除');
            }

            try {

                // 请求服务器，删除配置
                $api_url  = getApiUrl($get['id'], 'deleteServerConfig');
                getHttp($api_url, getTmSecKey($get));

                $save = true;
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }

            $save ? $this->success('删除成功') : $this->error('删除失败');
        } else {

            $get = $this->request->get();
            $this->assign('id', $get['id']);
        }

        return $this->fetch();
    }
}
