<?php

namespace app\index\controller\user;

use think\App;
use app\index\model\UserSuper;
use app\index\model\ServerList;
use app\index\model\SystemAdmin;
use app\index\model\TransManager;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Superuser
 * @package app\index\controller\Superuser;
 * @ControllerAnnotation(title="高级用户")
 */
class Superuser extends AdminController
{

    use \app\index\traits\Curd;

    /**
     * 排序规则
     *
     * @var array
     */
    protected $sort = [
        'id' => 'desc',
    ];

    /**
     * 可操作列表
     *
     * @var array
     */
    protected $demandList = [
        'lv', 'res', 'item', 'hero', 'stage', 'quest', 'equip', 'showlog', 'tutorial', 'nickname', 'user_book', 'defin_items'
    ];

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // 初始化模型
        $this->model       = new UserSuper();
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
                'count' => $model->count(),
                'data'  => $list,
            ];
            return json($data);
        }

        // 获取管理员列表
        $adminList = SystemAdmin::getAdminNameList(true);
        $this->assign('adminList', $adminList);

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerNameList(true);
        $this->assign('serverList', $serverList);

        // 获取需求列表
        $this->assign('demandList', json_encode($this->demandList, JSON_UNESCAPED_UNICODE));

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
                'uid|用户ID'         => 'require',
                'server_id|服务器ID' => 'require',
            ];
            $this->validate($post, $rule);

            // 请求的参数
            $params  = [
                'uid' => $post['uid']
            ];

            // 操作内容
            $contnet = [];

            // 检查字段
            foreach ($post as $field => $val) {
            
                if (in_array($field, $this->demandList) && !empty($val)) {
                    
                    $params[$field] = $val;
                    $contnet[] = $field . ': ' . $val;
                }
            }

            if (!$contnet) {

                return $this->error('请选择其中的一个');
            }

            // 启动事务
            TransManager::startTrans();
            try {
                
                // 设置数据
                $post['content']     = implode(', ', $contnet);
                $post['admin_id']    = session('admin.id');
                $post['create_time'] = time();

                $save = $this->model->save($post);

                if ($save) {

                    // 请求高级账号的接口
                    $api_url = getApiUrl($post['server_id'], 'setSuperUser');
                    getHttp($api_url, getTmSecKey($params));
                }

                TransManager::commit();

            } catch (\Exception $e) {

                TransManager::rollback();
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
        }

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerNameList();
        $this->assign('serverList', $serverList);

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
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }
}
