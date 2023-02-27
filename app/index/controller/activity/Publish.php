<?php
namespace app\index\controller\activity;

use think\App;
use app\index\model\ServerList;
use app\index\model\QuestConfig;
use app\index\model\SystemAdmin;
use app\index\model\ActivityConfig;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Publish
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="发布")
 */
class Publish extends AdminController
{

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'sort' => 'desc',
    ];

    

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ServerList();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index($id = '')
    {
        if ($this->request->isAjax()) {

            if (input('selectFields')) {
                return $this->selectList();
            }

            list($page, $limit, $where) = $this->buildTableParames();

            $server = $this->model->where($where);

            if (!SystemAdmin::checkAuthMax()) {

                $server = $server->whereIn('id', SystemAdmin::getAuthServerId());
            }

            $list = $server->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $server->count(),
                'data'  => $list,
            ];
            return json($data);
        }

        $this->assign('id', $id);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="获取服务器状态")
     *
     * @return void
     */
    public function status()
    {
        $post = $this->request->post();

        try {
            // 请求服务器配置
            $api_url  = getApiUrl($post['id'], 'getServerStatus');
            $url_data = getHttp($api_url, getTmSecKey([], false));
            return json($url_data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($e->getCode() == 1) {
                $msg = '请求错误';
            }
            return $this->error($msg);
        }
    }
    
    /**
     * @NodeAnotation(title="发布到服务器")
     *
     * @return void
     */
    public function publishToServer()
    {
        $post = $this->request->post();
        $rule = [
            'id|服务器ID'      => 'require',
            'act_id|选择的活动' => 'require',
        ];
        $this->validate($post, $rule);

        // 获取选择的活动数据
        $act_str = $post['act_id'];
        $act_ids = explode(',', $act_str);

        if (!$act_ids) {

            return $this->error('解析选中的活动失败');
        }

        try {

            $data = [];

            // 获取活动配置
            $act_cfg = ActivityConfig::whereIn('id', $act_ids)->select();

            foreach ($act_cfg->toArray() as $val) {
                
                // 获取活动包含的任务
                $quest_data = QuestConfig::where('act_id', $val['id'])->select();

                // 检查是否存在任务
                if (!$quest_data->isEmpty()) {

                    // 获取任务数据
                    $val['quests'] = $quest_data->toArray();
                }

                $val['config'] = json_decode($val['config'], true);
                $data[] = $val;
            }

            // 请求服务器配置
            $api_url  = getApiUrl($post['id'], 'updateActivityConfig');
            $url_data = getHttp($api_url, getTmSecKey(['rs' => $data]));
            return json($url_data);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($e->getCode() == 1) {
                $msg = '请求错误';
            }
            return $this->error($msg);
        }
    }
}