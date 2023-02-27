<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use app\index\model\SystemAdmin;
use app\index\model\ServerChannel;
use app\index\model\ContentNotice;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Notice
 * @package app\index\controller\content
 * @ControllerAnnotation(title="公告列表")
 */
class Notice extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ContentNotice();
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
            $list  = $model->page($page, $limit)->order(ContentNotice::$sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $list,
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 获取服务器列表
        $admin_list = SystemAdmin::getAdminNameList(true);
        $this->assign('admin_list', $admin_list);

        // 获取渠道列表
        $channel_list = ServerChannel::getChannelNameList(true);
        $this->assign('channel_list', $channel_list);

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
                'tag|标签'         => 'require',
                'title|标题'       => 'require',
                'content|内容'     => 'require',
                'channel_key|渠道' => 'require',
            ];
            $this->validate($post, $rule);

            $post['admin_id'] = session('admin.id');

            try {
                // 保存
                $save = $this->model->save($post);
                $this->model->refreshConfig();
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
        }

        // 获取服务器列表
        $channel_list = ServerChannel::getChannelNameList();
        $this->assign('channel_list', $channel_list);

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
                'tag|标签'         => 'require',
                'title|标题'       => 'require',
                'content|内容'     => 'require',
                'channel_key|渠道' => 'require',
            ];
            $this->validate($post, $rule);

            $post['admin_id'] = session('admin.id');

            try {
                // 保存
                $save = $row->save($post);
                $this->model->refreshConfig();
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // 获取服务器列表
        $channel_list = ServerChannel::getChannelNameList();
        $this->assign('channel_list', $channel_list);

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
            $this->model->refreshConfig();
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
            $this->model->refreshConfig();
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }
}
