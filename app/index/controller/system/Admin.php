<?php
namespace app\index\controller\system;


use app\index\model\SystemAdmin;
use app\index\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Admin
 * @package app\index\controller\system
 * @ControllerAnnotation(title="管理员管理")
 */
class Admin extends AdminController
{

    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemAdmin();
        $this->assign('auth_list', $this->model->getAuthList());
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

            $list      = $this->model->withoutField('password')->where($where)->page($page, $limit)->order($this->sort)->select();
            $auth_list = $this->model->getAuthList();

            foreach ($list as $key => $val) {
                
                $auth_arr = [];
                $auth_ids = $val->auth_ids ? explode( ',', $val->auth_ids ) : [];

                foreach ($auth_list as $auth_id => $auth_name) {
                    
                    if ( in_array( $auth_id, $auth_ids ) ) {

                        $auth_arr[] = $auth_name;
                    }
                }

                $val->auth_info = implode( ',', $auth_arr );
                $list[ $key ]   = $val;
            }

            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $this->model->where($where)->count(),
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
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
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
            $authIds = $this->request->post('auth_ids', []);
            $post['auth_ids'] = implode(',', array_keys($authIds));
            $rule = [];
            $this->validate($post, $rule);
            if (isset($row['password'])) {
                unset($row['password']);
            }
            try {
                $save = $row->save($post);
                TriggerService::updateMenu($id);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function password($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'password|登录密码'       => 'require',
                'password_again|确认密码' => 'require',
            ];
            $this->validate($post, $rule);
            if ($post['password'] != $post['password_again']) {
                $this->error('两次密码输入不一致');
            }
            try {
                $save = $row->save([
                    'password' => password($post['password']),
                ]);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $row->auth_ids = explode(',', $row->auth_ids);
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
        $id == AdminConstant::SUPER_ADMIN_ID && $this->error('超级管理员不允许修改');
        if (is_array($id)){
            if (in_array(AdminConstant::SUPER_ADMIN_ID, $id)){
                $this->error('超级管理员不允许修改');
            }
        }
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
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        if ($post['id'] == AdminConstant::SUPER_ADMIN_ID && $post['field'] == 'status') {
            $this->error('超级管理员状态不允许修改');
        }
        $row = $this->model->find($post['id']);
        empty($row) && $this->error('数据不存在');
        try {
            $up_data = [$post['field'] => $post['value']];

            if ($post['field'] == 'status') {
                
                $up_data['error_num'] = 0;
            }

            $row->save($up_data);
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }


}
