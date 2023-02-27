<?php
namespace app\index\controller\server;

use think\App;
use app\index\model\SystemAdmin;
use app\index\model\ServerGroup;
use app\index\model\ServerChannel;
use EasyAdmin\annotation\NodeAnotation;
use app\index\model\ServerChannelConfig;
use app\common\controller\AdminController;
use app\index\model\ServerList;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Channel
 * @package app\index\controller\server
 * @ControllerAnnotation(title="服务器渠道")
 */
class Channel extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    protected $sort           = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    /**
     * 语言列表
     *
     * @var array
     */
    private $lans_list        = [
        'CN' => '中文',
        'JP' => '日文',
        'KR' => '韩文',
        'EN' => '英文',
        'TL' => '泰文',
    ];

    /**
     * 账号系统
     *
     * @var array
     */
    private $account_sys      = [
        0 => '默认',
        1 => 'QUICK-SDK',
        2 => '爱奇艺SDK'
    ];

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ServerChannel();
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
                'channel_key|渠道KEY'  => 'require',
                'channel_name|渠道名称' => 'require',
            ];
            $this->validate($post, $rule);

            // 设置必要的参数
            $post['id']     = null;
            $post['status'] = 1;

            try {
                if ($this->model->where('channel_key', $post['channel_key'])->count()) {

                    throw new \Exception('渠道KEY已存在', 1);
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
                'channel_key|渠道KEY'  => 'require',
                'channel_name|渠道名称' => 'require',
            ];
            $this->validate($post, $rule);

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
    
    /**
     * @NodeAnotation(title="获取服务器配置")
     *
     * @return void
     */
    public function config($channel_key = '', $pre_state = 1)
    {

        // 获取渠道列表
        $channel_list = $this->model->getChannelNameList();

        if (!$channel_key && $channel_list) {

            $channel_idx = array_keys($channel_list);
            $channel_key = strval($channel_idx[0]);
        }

        // 获取渠道配置
        $info = ServerChannelConfig::where('channel_key', $channel_key)->where('pre_state', $pre_state)->find();

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'channel_key|渠道KEY'  => 'require',
            ];
            $this->validate($post, $rule);

            try {

                if (!$info) {

                    $info              = new ServerChannelConfig();
                    $info->id          = null;
                    $info->pre_state   = $pre_state;
                    $info->channel_key = $channel_key;
                }

                // 写入配置
                $info->config = json_encode($post, JSON_UNESCAPED_UNICODE);
                
                // 保存
                $save = $info->save();

                if ($save) {
                    
                    // 刷新缓存
                    ServerChannelConfig::refreshConfig();
                }

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // GET参数
        $this->assign('channel_key', $channel_key);
        $this->assign('pre_state', $pre_state);

        // 最高权限
        $this->assign('max_auth', SystemAdmin::checkAuthMax());

        // 渠道配置
        $this->assign('info', json_decode($info->config ?? '[]', true));

        // 渠道配置
        $this->assign('cfgs', [
            'lans_list'    => $this->lans_list,// 语言列表
            'account_sys'  => $this->account_sys,// 账号系统
            'server_list'  => ServerList::getAuthServerNameList(),// 服务器列表
            'server_group' => ServerGroup::getGroupNameList(),// 获取服务器组列表
            'channel_list' => $this->model->getChannelNameList(),// 获取渠道列表
        ]);
        
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="服务器配置添加")
     */
    public function config_add()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            $rule = [
                'id|服务器ID不能为空'  => 'require',
                'g_key|键名不能为空'   => 'require',
                'g_value|键值不能为空' => 'require',
            ];
            $this->validate($post, $rule);

            try {

                // 请求服务器，添加配置
                $api_url  = getApiUrl($post['id'], 'saveServerConfig');
                getHttp($api_url, getTmSecKey($post));

                $save = true;
            } catch (\Exception $e) {

                $this->error($e->getMessage());
            }

            $save ? $this->success('保存成功') : $this->error('保存失败');
        } else {

            $get = $this->request->get();
            $this->assign('id', $get['id']);
        }

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="服务器配置修改")
     */
    public function config_modify($id)
    {
        if (isNull($id)) {
            $this->error('服务器ID不能为空。');
        }

        $post = $this->request->post();
        $rule = [
            'g_key|字段不能为空' => 'require',
        ];
        $this->validate($post, $rule);

        try {

            // 请求服务器，添加配置
            $api_url  = getApiUrl($id, 'saveServerConfig');
            getHttp($api_url, getTmSecKey($post));

            $save = true;
        } catch (\Exception $e) {

            $this->error($e->getMessage());
        }

        $save ? $this->success('保存成功') : $this->error('保存失败');
    }

    /**
     * 服务器配置删除
     *@NodeAnotation(title="服务器配置删除")
     * 
     * @param integer $id
     * @param string $g_key
     * @return void
     */
    public function config_delete()
    {
        if ($this->request->isAjax()) {

            $get  = $this->request->get();
            $rule = [
                'id|服务器ID不能为空'  => 'require',
                'g_key|键名不能为空'   => 'require',
            ];
            $this->validate($get, $rule);
            $save = false;

            if (in_array($get['g_key'], $this->protect_field)) {
                $this->error('受保护的配置，不能删除');
            }

            try {

                // 请求服务器，删除配置
                $api_url  = getApiUrl($get['id'], 'deleteServerConfig');
                getHttp($api_url, getTmSecKey($get));

                $save = true;
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }

            $save ? $this->success('删除成功') : $this->error('删除失败');
        } else {

            $get = $this->request->get();
            $this->assign('id', $get['id']);
        }

        return $this->fetch();
    }
}
