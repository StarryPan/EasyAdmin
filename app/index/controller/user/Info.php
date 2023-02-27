<?php

namespace app\index\controller\user;

use think\App;
use app\index\model\ServerList;
use app\index\model\GameConfig;
use app\index\model\TransManager;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Info
 * @package app\index\controller\Info;
 * @ControllerAnnotation(title="用户信息")
 */
class Info extends AdminController
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
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);

        // 初始化模型
        $this->serverModel = new ServerList();
    }

    /**
     *  @NodeAnotation(title="查询")
     *
     * @param string $server_id
     * @param string $search
     * @return void
     */
    public function index($server_id = '', $search = '')
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'search|查询参数'    => 'require',
                'server_id|服务器ID' => 'require',
            ];
            $this->validate($post, $rule);
                
            try {
                $params   = ['search' => $search];

                // 请求服务器配置
                $api_url  = getApiUrl($server_id, 'getUserInfo');
                $url_data = getHttp($api_url, getTmSecKey($params, false));
                return json($url_data);

            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取配置
        $this->assign('cfgs', GameConfig::getCommonConfig('userinfo'));

        // 查询参数
        $this->assign('query', $this->request->post());

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
            
                if (in_array($field, $this->demandList) && $val > 0) {
                    
                    $params[$field] = $val;
                    $contnet[] = $field . ': ' . $val;
                }
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
                    $api_url = getApiUrl($post['server_id'], 'setInfo');
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
