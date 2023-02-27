<?php

namespace app\index\controller\user;

use think\App;
use app\index\model\GameConfig;
use app\index\model\SystemAdmin;
use app\index\model\ServerGroup;
use app\index\model\UserRedeemCode;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use app\index\model\ServerChannel;
use app\index\model\ServerList;
use app\index\model\SystemConfig;
use app\index\model\UserRedeemCodeLog;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Redeemcode
 * @package app\index\controller\user
 * @ControllerAnnotation(title="兑换码列表")
 */
class Redeemcode extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    protected $sort         = [
        'id' => 'desc',
    ];

    /**
     * 兑换类型
     *
     * @var array
     */
    protected $redeem_types = [
        1 => '兑换一次',
        2 => '兑换多次',
    ];


    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new UserRedeemCode();
        $this->modelLog = new UserRedeemCodeLog();
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

        // 获取渠道列表
        $channelList = ServerChannel::getChannelNameList(true);
        $this->assign('channelList', $channelList);

        // 获取道具配置
        $configItems = GameConfig::getConfigItems();
        $this->assign('configItems', json_encode($configItems));

        // 获取兑换类型
        $this->assign('redeemTypes', json_encode($this->redeem_types));

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
                'code_prefix|兑换码的前缀'   => 'require',
                'code_count|兑换码的生成数量' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rewards   = [];
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            foreach ($rew_items as $ival) {

                $rewards[$ival['itemid']] = intval($ival['itemcnt']);
            }

            // 数据
            $data                = [];

            // 描述
            $data['descr']       = $post['descr'];

            // 获取自增批号
            $data['batch']       = SystemConfig::incValueWithKey('user_redeemcode_batch');

            // 奖励
            $data['rewards']     = json_encode($rewards);

            // 兑换次数
            $data['use_num']     = $post['use_num'];

            // 管理员ID
            $data['admin_id']    = session('admin.id');

            // 生效时间
            $data['end_time']    = empty($post['end_time']) ? 0 : strtotime($post['end_time']);
            $data['start_time']  = empty($post['start_time']) ? 0 : strtotime($post['start_time']);

            // 兑换数量
            $data['code_count']  = $post['code_count'];

            // 前缀
            $data['code_prefix'] = $post['code_prefix'];

            // 渠道KEY
            $data['channel_key'] = $post['channel_key'];

            // 兑换类型
            $data['redeem_type'] = $post['redeem_type'];

            // 创建时间
            $data['create_time'] = time();

            try {

                // 批量生出兑换码
                $save = $this->model->generateRwardsCode($data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // 获取渠道列表
        $channelList = ServerChannel::getChannelNameList();
        $this->assign('channelList', $channelList);

        // 获取兑换类型
        $this->assign('redeemTypes', $this->redeem_types);

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

            // 获取奖励
            $rewards   = [];
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            foreach ($rew_items as $ival) {

                $rewards[$ival['itemid']] = intval($ival['itemcnt']);
            }

            $post['rewards'] = json_encode($rewards);

            // 管理员ID
            $post['admin_id']    = session('admin.id');

            // 生效时间
            $post['end_time']    = empty($post['end_time']) ? 0 : strtotime($post['end_time']);
            $post['start_time']  = empty($post['start_time']) ? 0 : strtotime($post['start_time']);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);

        // 获取渠道列表
        $channelList = ServerChannel::getChannelNameList();
        $this->assign('channelList', $channelList);

        // 获取兑换类型
        $this->assign('redeemTypes', $this->redeem_types);

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
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
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

            $log  = $this->modelLog->where($where);
            $list = $log->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $log->count(),
                'data'  => $list,
            ];
            return json($data);
        }

        // 获取渠道列表
        $channelList = ServerChannel::getChannelNameList(true);
        $this->assign('channelList', $channelList);

        // 获取服务器列表
        $serverList = ServerList::getAuthServerNameList(true);
        $this->assign('serverList', $serverList);

        // 获取道具配置
        $configItems = GameConfig::getConfigItems();
        $this->assign('configItems', json_encode($configItems));

        return $this->fetch();
    }
}
