<?php
namespace app\index\controller\system;


use app\index\model\SystemConfig;
use app\index\service\TriggerService;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * Class Config
 * @package app\index\controller\system
 * @ControllerAnnotation(title="系统配置管理")
 */
class Config extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemConfig();
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="保存")
     */
    public function save($group = 'site')
    {
        $post = $this->request->post();
        
        if ($group == null) {

            return $this->error('配置组不能为空');
        }

        try {
            foreach ($post as $key => $val) {

                $cfg = $this->model->where('group', $group)->where('name', $key)->find();

                if (!$cfg) {

                    $cfg        = $this->model;
                    $cfg->name  = $key;
                    $cfg->group = $group;
                }

                $cfg->value = $val;
                $cfg->save();
            }
            TriggerService::updateMenu();
            TriggerService::updateSysconfig();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

}