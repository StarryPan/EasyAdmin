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
 * Class Rotateimage
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="轮播图")
 */
class Rotateimage extends AdminController
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

    public $title     = '轮播图';

    /**
     * 活动类型
     *
     * @var integer
     */
    public $act_type  = CfgConst::AtypeRotateImage;

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'rotate_image';

    /**
     * 轮播类型
     *
     * @var array
     */
    public $rotate_types = [
        0 => '普通',
        1 => '签到活动',
        2 => '跳转活动',
        3 => '外部跳转活动',
        4 => '累计充值活动',
        5 => '累计消耗活动',
    ];



    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ActivityConfig();
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
            $list  = $model->where('atype', $this->act_type)->page($page, $limit)->order($this->sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $this->model->getConfigData($list),
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 活动类型
        $act_types = ActivityConfig::getTypesList([CfgConst::AtypeRotateImage]);
        $this->assign('actTypes', json_encode($act_types));

        // 轮播类型
        $this->assign('rotateTypes', json_encode($this->rotate_types));
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
                'name|名称'         => 'require',
                'big_img|背景图片'   => 'require',
                'tag_tex|页签文字'   => 'require',
                'btn_tex|按钮文字'   => 'require',
                'act_type|活动类型'  => 'require',
                'small_img|轮播图片' => 'require',
            ];
            $this->validate($post, $rule);

            // 设置必要的参数
            $data           = [];
            $data['id']     = null;
            $data['sort']   = intval($post['sort'] ?? 0);
            $data['title']  = $this->title;
            $data['atype']  = CfgConst::AtypeRotateImage;
            $data['status'] = intval($post['status'] ?? 0);
            
            try {

                // 设置配置
                $cfgs = $post;
                $data['config'] = json_encode($cfgs, JSON_UNESCAPED_UNICODE);

                if (!empty($post['start_time'])) {

                    if (empty($post['end_time'])) {

                        throw new \Exception('时间格式错误，未配置结束时间', 1);
                    }

                    $data['open_type']  = 3;// 限时
                    $data['open_value'] = $post['start_time'] . '~' . $post['end_time'];
                    unset(
                        $post['sort'],
                        $post['status'],
                        $post['end_time'],
                        $post['start_time']
                    );
                }
                

                // 保存
                $save = $this->model->save($data);

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
        }

        // 活动类型
        $act_types = ActivityConfig::getTypesList([CfgConst::AtypeRotateImage]);
        $this->assign('actTypes', $act_types);

        // 轮播类型
        $this->assign('rotateTypes', $this->rotate_types);
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
            $post['type']   = CfgConst::AtypeRotateImage;
            $post['title']  = $this->title;
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
            $post['title']  = $this->title;
            $post['type']   = CfgConst::AtypeRotateImage;
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