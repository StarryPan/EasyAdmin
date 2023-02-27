<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\ServerList;
use app\index\model\SystemAdmin;
use app\index\model\TransManager;
use EasyAdmin\annotation\NodeAnotation;
use app\index\model\ServerConfigConflict;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Conflict
 * @package app\index\controller\Conflict;
 * @ControllerAnnotation(title="删除冲突")
 */
class Conflict extends AdminController
{

    use \app\index\traits\Curd;

    /**
     * 排序规则
     *
     * @var array
     */
    protected $sort      = [
        'id' => 'desc',
    ];

    /**
     * 配置类型
     *
     * @var array
     */
    protected $cfg_types = [
        'item'       => '道具',
        'hero'       => '角色',
        'equip'      => '装备',
        'quest'      => '任务',
        'shop'       => '商店',
        'shopDetail' => '商店商品',
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
        $this->model       = new ServerConfigConflict();
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

        // 获取配置类型
        $this->assign('configTypes', json_encode($this->cfg_types, JSON_UNESCAPED_UNICODE));

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="清除")
     */
    public function remover()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'ctype|配置类型'     => 'require',
                'captcha|验证码'     => 'require|captcha',
                'remover|删除的id'   => 'require',
                'server_id|服务器ID' => 'require',
            ];
            $this->validate($post, $rule);

            // 检查操作的KEY
            $cfg_keys = array_keys($this->cfg_types);
            if (!in_array($post['ctype'], $cfg_keys)) {

                return $this->error('配置类型错误');
            }

            // 解析移除的配置ID
            $remover = strToIntArray(cleanStrFormat($post['remover']), ',');

            if (!$remover || isNull($remover)) {

                return $this->error('移除的配置ID，解析错误！');
            }

            // 请求的参数
            $params = [
                'ctype'   => $post['ctype'],
                'remover' => $remover,
            ];

            // 启动事务
            TransManager::startTrans();
            try {
                
                // 设置数据
                $post['admin_id']    = session('admin.id');
                $post['create_time'] = time();

                if ($this->model->save($post) == false) {
                    
                    throw new \Exception('保存日志失败');
                }

                // 请求清除配置冲突的接口
                $api_url   = getApiUrl($post['server_id'], 'removerConfigConflict');
                $http_data = getHttp($api_url, getTmSecKey($params));

                TransManager::commit();

                return json($http_data);

            } catch (\Exception $e) {

                TransManager::rollback();
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerNameList();
        $this->assign('serverList', $serverList);

        // 获取配置类型
        $this->assign('configTypes', $this->cfg_types);

        return $this->fetch();
    }
}