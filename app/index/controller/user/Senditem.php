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
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Senditem
 * @package app\index\controller\user;
 * @ControllerAnnotation(title="发送道具")
 */
class Senditem extends AdminController
{

    use \app\index\traits\Curd;

    protected $sort = [
        'id' => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserMailLog();
        $this->serverModel = new ServerList();
    }

    /**
     * @NodeAnotation(title="发送道具")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'userpms|用户ID'     => 'require',
                'server_id|服务器ID' => 'require',
            ];

            $this->validate($post, $rule);

            // 获取奖励
            $rew_items          = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['way']        = 10; // 途径
            $post['extend']     = '发送邮件信息';
            $post['expire_day'] = intval($post['expire_day']);

            TransManager::startTrans();
            try {

                // 检查是否多语言-标题
                if (!empty($post['lan_title'])) {

                    // 解析填写的-多语言
                    $post['lan_title'] = strLansData($post['lan_title'], ['CN_LAN' => 'cn_title', 'EN_LAN' => 'en_title']);
                }

                // 检查是否多语言-内容
                if (!empty($post['lan_content'])) {

                    // 解析填写的-多语言
                    $post['lan_content'] = strLansData($post['lan_content'], ['CN_LAN' => 'cn_content', 'EN_LAN' => 'en_content']);
                }

                $rewards = [];

                foreach ($rew_items as $ival) {

                    $rewards[$ival['itemid']] = intval($ival['itemcnt']);
                }

                // 设置奖励
                $post['items'] = $rewards;

                // 请求服务器配置
                $api_url  = getApiUrl($post['server_id'], 'sendUserMails');
                $url_data = getHttp($api_url, getTmSecKey($post));

                // 创建日志
                UserMailLog::createLog('items', $post, $rewards);

                TransManager::commit();

                return json($url_data);
            } catch (\Exception $e) {

                TransManager::rollback();
                $this->error($e->getMessage());
            }
        }

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

            $log  = $this->model->where('log_key', 'items')->where($where);
            $list = $log->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $log->count(),
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

        // 获取道具配置
        $configItems = GameConfig::getConfigItems();
        $this->assign('configItems', json_encode($configItems));

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
        return 'tempKeyData:copyMaill_' . session('admin.id');
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
        $redis->set($this->tempKey(), $log_data->toArray(), 120);

        return $this->success('复制成功');
    }
    
}
