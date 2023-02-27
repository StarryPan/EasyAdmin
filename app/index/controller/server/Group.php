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
 * Class Group
 * @package app\index\controller\server
 * @ControllerAnnotation(title="服务器组")
 */
class Group extends AdminController
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
     * 字段
     *
     * @var array
     */
    protected $fields = [
        'serverid'
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
        $this->model       = new ServerGroup();
        $this->serverModel = new ServerList();
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

            $model = $this->model->where($where);
            $list  = $model->page($page, $limit)->order($this->sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $list,
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerNameList(true);
        $this->assign('serverList', $serverList);

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
                'name|服务器组名称'     => 'require',
                'group_key|服务器组KEY' => 'require',
                'server_num|服务器数量' => 'require',
            ];
            $this->validate($post, $rule);

            // 服务器
            $servers    = [];

            // 获取服务器
            $post_rews  = $this->getPostRewards($post, $this->fields, 'servers');

            // 设置必要的参数
            $post['id'] = null;

            if ($post_rews != null) {

                $servers = array_column($post_rews, $this->fields[0]);
            }

            // 设置服务器
            $post['server'] = json_encode($servers);

            try {
                if ($this->model->where('group_key', $post['group_key'])->count()) {

                    throw new \Exception('服务器组KEY已存在', 1);
                }
                // 保存
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
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
            $rule = [
                'id|服务器组ID'        => 'require',
                'name|服务器组名称'     => 'require',
                'group_key|服务器组KEY' => 'require',
                'server_num|服务器数量' => 'require',
            ];
            $this->validate($post, $rule);

            // 服务器
            $servers    = [];

            // 获取服务器
            $post_rews  = $this->getPostRewards($post, $this->fields, 'servers');

            if ($post_rews != null) {

                $servers = array_column($post_rews, $this->fields[0]);
            }

            // 设置服务器
            $post['server'] = json_encode($servers);

            try {
                // 保存
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
}
