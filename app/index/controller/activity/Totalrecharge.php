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
 * Class Totalrecharge
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="累充列表")
 */
class Totalrecharge extends AdminController
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

    public $title     = '累充列表';

    /**
     * 活动类型
     *
     * @var integer
     */
    public $act_type  = CfgConst::AtypeTotalRecharge;

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'total_recharge';

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
        $this->act_model = new ActivityConfig();
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

            $model = $this->act_model->where($where);
            $list  = $model->where('atype', $this->act_type)->page($page, $limit)->order($this->sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $list,
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 获取活动开启类型
        $this->assign('openTypes', json_encode(ActivityConfig::$openTypes, JSON_UNESCAPED_UNICODE));
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
                'title|名称' => 'require',
                'open_type|开启类型' => 'require',
            ];
            $this->validate($post, $rule);

            // 设置必要的参数
            $post['id']          = null;
            $post['atype']       = $this->act_type;
            $post['update_time'] = time();
            $post['create_time'] = time();
            
            try {

                // 保存
                $save = $this->act_model->save($post);

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
        }

        // 获取任务开启类型
        $this->assign('openTypes', ActivityConfig::$openTypes);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->act_model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'title|名称' => 'require',
                'open_type|开启类型' => 'require',
            ];
            $this->validate($post, $rule);

           // 设置必要的参数
           $post['atype']       = $this->act_type;
           $post['update_time'] = time();

            try {

                // 保存
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        // 获取任务开启类型
        $this->assign('openTypes', ActivityConfig::$openTypes);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="复制")
     */
    public function copy($id)
    {
        $row = $this->act_model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'title|名称' => 'require',
                'open_type|开启类型' => 'require',
            ];
            $this->validate($post, $rule);

           // 设置必要的参数
           $post['id']          = null;
           $post['atype']       = $this->act_type;
           $post['update_time'] = time();
           $post['create_time'] = time();

            try {

                // 保存
                $save = $this->act_model->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('复制成功') : $this->error('复制失败');
        }

        // 获取任务开启类型
        $this->assign('openTypes', ActivityConfig::$openTypes);
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->act_model->whereIn('id', $id)->select();
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
        $row = $this->act_model->find($post['id']);
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
     * @NodeAnotation(title="任务列表")
     */
    public function quest()
    {
        if ($this->request->isAjax()) {

            if (input('selectFields')) {
                return $this->selectList();
            }

            try {

                list($page, $limit, $where, $excludeFields) = $this->buildTableCurlParames(false, ['act_id']);

                $model = $this->model->where($where);
                $list  = $model->where('act_id', $excludeFields['act_id'])->page($page, $limit)->order($this->sort)->select();
                $data  = [
                    'code'  => 0,
                    'msg'   => '',
                    'data'  => $list,
                    'count' => $model->count(),
                ];

                return json($data);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
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

        // 获取活动列表
        $activity_list = $this->act_model->getListWithType($this->act_type);
        $this->assign('activityList', json_encode($activity_list));

        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="添加任务")
     */
    public function addQuest()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
                'act_id|所属的活动ID' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['type']   = CfgConst::QtypeTotalRecharge;
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

        // 获取活动列表
        $activity_list = $this->act_model->getListWithType($this->act_type);
        $this->assign('activityList', $activity_list);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑任务")
     */
    public function editQuest($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
                'act_id|所属的活动ID' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['id']     = $id;
            $post['type']   = CfgConst::QtypeTotalRecharge;

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

        // 获取活动列表
        $activity_list = $this->act_model->getListWithType($this->act_type);
        $this->assign('activityList', $activity_list);

        // 限制列表
        $this->assign('limitList', $this->limit_list);

        // 获取任务目标配置
        $goalList = GameConfig::getConfigQuestGoals();
        $this->assign('goalList', $goalList);

        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="复制任务")
     */
    public function copyQuest($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|名称' => 'require',
                'goal|目标类型' => 'require',
                'value|目标数值' => 'require',
                'act_id|所属的活动ID' => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['type']   = CfgConst::QtypeTotalRecharge;
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

        // 获取活动列表
        $activity_list = $this->act_model->getListWithType($this->act_type);
        $this->assign('activityList', $activity_list);

        // 限制列表
        $this->assign('limitList', $this->limit_list);

        // 获取任务目标配置
        $goalList = GameConfig::getConfigQuestGoals();
        $this->assign('goalList', $goalList);

        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除任务")
     */
    public function deleteQuest($id)
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
     * @NodeAnotation(title="属性修改任务")
     */
    public function modifyQuest()
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

}
