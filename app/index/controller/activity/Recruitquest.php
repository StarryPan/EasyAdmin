<?php
namespace app\index\controller\activity;

use think\App;
use app\index\model\CfgConst;
use app\index\model\GameConfig;
use app\index\model\QuestConfig;
use app\index\model\ActivityConfig;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Recruitquest
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="新兵考核")
 */
class Recruitquest extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    public $sort      = [
        'id'   => 'asc',
    ];

    public $title     = '新兵考核';

    /**
     * 活动类型
     *
     * @var integer
     */
    public $act_type  = CfgConst::AtypeRecruitQuest;

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'recruit_quest';

    /**
     * 限制列表
     *
     * @var array
     */
    public $limit_list = [
        1 => '第1天',
        2 => '第2天',
        3 => '第3天',
        4 => '第4天',
        5 => '第5天',
        6 => '第6天',
        7 => '第7天',
        8 => '第8天',
        9 => '第9天',
        10 => '第10天',
        11 => '第11天',
        12 => '第12天',
        13 => '第13天',
        14 => '第14天',
    ];



    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new QuestConfig();

        // 初始活动数据
        $act_data       = ActivityConfig::loadDataWithType($this->act_type);
        $this->act_id   = $act_data->id;
        $this->act_data = $act_data;
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
            $list  = $model->where('act_id', $this->act_id)->page($page, $limit)->order($this->sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $list,
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 限制列表
        $this->assign('limitList', json_encode($this->limit_list));

        // 获取任务目标配置
        $goalList = [];
        $goaCfgs = GameConfig::getConfigQuestGoals();
        foreach ($goaCfgs as $val) {
            $goalList[strval($val['id'])] = $val['name'];
        }
        $this->assign('goalList', json_encode($goalList));

        // 获取道具配置
        $configItems = GameConfig::getConfigItems();
        $this->assign('configItems', json_encode($configItems));
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
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['act_id'] = $this->act_id;
            $post['type']   = CfgConst::QtypeRecruitQuest;
            $post['id']     = $this->model->getIncId($post['type']);
            
            try {

                $rewards = [];

                foreach ($rew_items as $rew) {
                    
                    $rewards[$rew['itemid']] = $rew['itemcnt'];
                }

                // 奖励
                $post['rewards'] = rewardToString($rewards);

                // 保存
                $save = $this->model->save($post);

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
        }

        // 限制列表
        $this->assign('limitList', $this->limit_list);

        // 获取任务目标配置
        $goalList = GameConfig::getConfigQuestGoals();
        $this->assign('goalList', $goalList);
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
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['id']     = $id;
            $post['type']   = CfgConst::QtypeRecruitQuest;
            $post['act_id'] = $this->act_id;

            try {

                $rewards = [];

                foreach ($rew_items as $rew) {
                    
                    $rewards[$rew['itemid']] = $rew['itemcnt'];
                }

                // 奖励
                $post['rewards'] = rewardToString($rewards);

                // 保存
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // 限制列表
        $this->assign('limitList', $this->limit_list);

        // 获取任务目标配置
        $goalList = GameConfig::getConfigQuestGoals();
        $this->assign('goalList', $goalList);

        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="复制")
     */
    public function copy($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['act_id'] = $this->act_id;
            $post['type']   = CfgConst::QtypeRecruitQuest;
            $post['id']     = $this->model->getIncId($post['type']);

            try {

                $rewards = [];

                foreach ($rew_items as $rew) {
                    
                    $rewards[$rew['itemid']] = $rew['itemcnt'];
                }

                // 奖励
                $post['rewards'] = rewardToString($rewards);

                // 保存
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('复制成功') : $this->error('复制失败');
        }

        // 限制列表
        $this->assign('limitList', $this->limit_list);

        // 获取任务目标配置
        $goalList = GameConfig::getConfigQuestGoals();
        $this->assign('goalList', $goalList);

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
            $this->model->refreshConfig();
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
            $this->model->refreshConfig();
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * @NodeAnotation(title="考核配置")
     */
    public function config()
    {
        // 获取活动数据
        $act_data = ActivityConfig::loadDataWithType($this->act_type);

        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'id|活动ID' => 'require',
                'title|活动标题' => 'require',
                'open_type|开启类型' => 'require',
            ];
            $this->validate($post, $rule);

            try {

                // 保存
                $save = $act_data->save($post);

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // 获取活动类型列表
        $this->assign('atypeList', ActivityConfig::$atypeList);

        // 获取活动开启类型
        $this->assign('openTypes', ActivityConfig::$openTypes);

        $this->assign('info', $act_data);
        return $this->fetch();
    }

}
