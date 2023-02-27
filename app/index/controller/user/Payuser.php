<?php

namespace app\index\controller\user;

use think\App;
use think\cache\driver\Redis;
use app\index\model\ServerList;
use app\index\model\GameConfig;
use app\index\model\SystemAdmin;
use app\index\model\UserMailLog;
use app\index\model\TransManager;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\index\model\UserCommonLog;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Payuser
 * @package app\index\controller\user;
 * @ControllerAnnotation(title="支付补单")
 */
class Payuser extends AdminController
{
    use \app\index\traits\Curd;

    protected $sort = [
        'id' => 'desc',
    ];

    protected $log_key = 'payuser';
    

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserCommonLog();
        $this->serverModel = new ServerList();
    }

    /**
     * @NodeAnotation(title="支付补单")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'userpms|用户ID'     => 'require',
                'captcha|验证码'     => 'require|captcha',
                'detail_id|商品ID'   => 'require',
                'server_id|服务器ID' => 'require',
            ];

            $this->validate($post, $rule);

            TransManager::startTrans();
            try {

                // 检查金额
                if (empty($post['money'])) {

                    // 获取商品配置
                    $detail_cfg    = GameConfig::getConfigShopDetailWithId($post['detail_id']);
                    $post['money'] = intval($detail_cfg['money']);
                }

                // 检查订单号是否填写
                if (empty($post['order_id'])) {

                    $admin_id         = session('admin.id');
                    $post['order_id'] = 'Backstage_' . $admin_id . '_' . $post['detail_id'] . '_' . time() . '_' . rand(0, 9);
                }

                // 检查移动设备系统类型
                if (empty($post['move_type'])) {

                    $post['move_type'] = 'ios';
                }

                // 请求服务器配置
                $api_url  = getApiUrl($post['server_id'], 'payBackstage', 'Pay');
                $url_data = getHttp($api_url, getTmSecKey($post));

                // 创建日志
                UserCommonLog::createLog($this->log_key, $post, $url_data);

                TransManager::commit();

                return json($url_data);
            } catch (\Exception $e) {

                TransManager::rollback();
                $this->error($e->getMessage());
            }
        }

        // 获取商店商品配置
        $cfgs = GameConfig::getConfigShopDetailList();
        $this->assign('cfgs', $cfgs);

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerList();
        $this->assign('serverList', $serverList);

        // 检查是否为重复状态
        $redis     = Redis::getInstance();
        $copy_data = $redis->get($this->tempKey());

        if ($copy_data) {
            
            $redis->delete($this->tempKey());
            $this->assign('row', $copy_data);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="日志")
     */
    public function log()
    {
        if ($this->request->isAjax()) {

            if (input('selectFields')) {
                return $this->selectList();
            }

            list($page, $limit, $where) = $this->buildTableParames();

            $log  = $this->model->where('log_key', $this->log_key)->where($where);
            $list = $log->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $log->count(),
                'data'  => $this->model->analysis($list),
            ];
            return json($data);
        }

        // 获取管理员列表
        $adminList = SystemAdmin::getAdminNameList(true);
        $this->assign('adminList', $adminList);

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerNameList(true);
        $this->assign('serverList', $serverList);

        // 获取道具配置
        $detailList = GameConfig::getConfigShopDetailList();
        $this->assign('detailList', json_encode($detailList));

        return $this->fetch();
    }

    /**
     * 临时缓存KEY
     *
     * @param Type|null $var
     * @return void
     */
    private function tempKey(): string
    {
        return 'tempKeyData:copyPayuser_' . session('admin.id');
    }

    /**
     * @NodeAnotation(title="复制")
     */
    public function copy($id)
    {
        if (isNull($id)) {

            return $this->error('参数为空');
        }

        // 获取日志数据
        $log_data = $this->model->where('id', $id)->find();

        if (!$log_data) {

            return $this->error('获取日志数据失败，参数错误');
        }

        $redis = Redis::getInstance();
        $redis->set($this->tempKey(), $this->model->analysis($log_data), 120);

        return $this->success('复制成功');
    }
}
