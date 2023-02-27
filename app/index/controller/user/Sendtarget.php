<?php

namespace app\index\controller\user;

use think\App;
use think\cache\driver\Redis;
use app\index\model\CfgConst;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Sendtarget
 * @package app\index\controller\user;
 * @ControllerAnnotation(title="发送目标奖励")
 */
class Sendtarget extends AdminController
{

    use \app\index\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->serverModel = new ServerList();
    }

    /**
     * @NodeAnotation(title="发送目标奖励")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'mtype|邮件类型'       => 'require',
                'server_id|服务器'     => 'require',
                'target_type|条件类型' => 'require',
            ];

            $this->validate($post, $rule);

            // 获取奖励
            $rew_items          = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['way']        = 10; // 途径
            $post['extend']     = '发送邮件信息';
            $post['expire_day'] = intval($post['expire_day']);

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
                $api_url  = getApiUrl($post['server_id'], 'sendTargetMail');
                $url_data = getHttp($api_url, getTmSecKey($post));

                return json($url_data);
            } catch (\Exception $e) {

                $this->error($e->getMessage());
            }
        }

        // 获取服务器列表
        $serverList = $this->serverModel->getAuthServerList();
        $this->assign('serverList', $serverList);

        // 邮件类型
        $this->assign('mail_types', CfgConst::MailTypes);

        // 目标类型
        $this->assign('target_types', CfgConst::MailTargetTypes);

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
     * @NodeAnotation(title="列表")
     */
    public function list($server_id = '')
    {
        if (empty($server_id)) {

            return $this->error('获取列表失败，服务器ID不能为空！');
        }

        if ($this->request->isAjax()) {

            // 请求服务器配置
            $api_url  = getApiUrl($server_id, 'getTargetMailList');
            $url_data = getHttp($api_url, getTmSecKey());

            // 缓存下来
            $redis = Redis::getInstance();
            $redis->set($this->tempKey() . '_List', $url_data['data']??[], 3600);

            return json($url_data);
        }

        // 服务器ID
        $this->assign('server_id', $server_id);

        // 邮件类型
        $this->assign('mail_types', json_encode(CfgConst::MailTypes, JSON_UNESCAPED_UNICODE));

        // 目标类型
        $this->assign('target_types', json_encode(CfgConst::MailTargetTypes, JSON_UNESCAPED_UNICODE));

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

        // 缓存下来
        $redis = Redis::getInstance();
        $list  = $redis->get($this->tempKey() . '_List');

        if ($list == false) {

            return $this->error('获取列表数据失败，请刷新列表');
        }

        try {
    
            $list_data = [];

            foreach ($list as $val) {
                
                if ($val['id'] == $id) {

                    $rewards        = json_decode($val['rewards'], true);
                    $val['rewards'] = json_encode($rewards['items']);
                    $list_data      = $val;
                    break;
                }
            }

            if (!$list_data) {

                throw new \Exception('获取列表数据失败，参数错误');
            }

            $redis = Redis::getInstance();
            $redis->set($this->tempKey(), $list_data, 120);

        } catch (\Exception $e) {

            return $this->error($e->getMessage());
        }

        return $this->success('复制成功');
    }

    /**
     * @NodeAnotation(title="列表删除")
     */
    public function list_delete($server_id, $id)
    {
        if (empty($server_id) || $server_id == 'undefined') {

            return $this->error('获取列表失败，服务器ID不能为空！');
        }

        // 请求服务器配置
        $api_url  = getApiUrl($server_id, 'deleteTargetMailList');
        $url_data = getHttp($api_url, getTmSecKey(['id' => $id]));

        return json($url_data);
    }

}
