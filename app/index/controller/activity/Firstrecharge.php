<?php
namespace app\index\controller\activity;

use think\App;
use app\index\model\CfgConst;
use app\index\model\GameConfig;
use app\index\model\ActivityConfig;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Firstrecharge
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="首次奖励")
 */
class Firstrecharge extends AdminController
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

    /**
     * 活动标题
     *
     * @var string
     */
    public $title     = '首冲奖励';

    /**
     * 活动类型
     *
     * @var integer
     */
    public $act_type  = CfgConst::AtypeFirstRecharge;

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'first_recharge';

    

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
     * @NodeAnotation(title="主页")
     */
    public function index()
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

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);
            try {

                $rewards = [];

                foreach ($rew_items as $rew) {
                    
                    $rewards[$rew['itemid']] = $rew['itemcnt'];
                }

                // 奖励
                $post['config'] = rewardToString($rewards);

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