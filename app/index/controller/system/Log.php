<?php

namespace app\index\controller\system;

use think\App;
use app\index\model\SystemLog;
use app\index\model\SystemAdmin;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * @ControllerAnnotation(title="操作日志管理")
 * Class Auth
 * @package app\index\controller\system
 */
class Log extends AdminController
{

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemLog(); // 构建请求参数需要 - buildTableParames
    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        // 获取全部的管理员
        $adminList = SystemAdmin::getAdminNameList(true);
        $this->assign('admin_list', $adminList);
        return $this->fetch();
    }

    /**
     * 获取日志数据
     *
     * @param integer $log_type
     * @return array
     */
    private function getLogData($log_type = 0): array
    {
        if (input('selectFields')) {

            return $this->selectList();
        }

        // 获取筛选条件规则
        list($page, $limit, $where, $excludeFields) = $this->buildTableParames(['month']);

        // 获取当前月份
        $month = (isset($excludeFields['month']) && !empty($excludeFields['month'])) ? date('Ym', strtotime($excludeFields['month'])) : date('Ym');

        // todo TP6框架有一个BUG，非模型名与表名不对应时（name属性自定义），withJoin生成的sql有问题
        $model = $this->model->setMonth($month)->where('log_type', $log_type)->where($where);
        $list  = $model->page($page, $limit)->order($this->sort)->select();
        $data  = [
            'code'  => 0,
            'msg'   => '',
            'count' => $model->count(),
            'data'  => $list,
        ];

        return $data;
    }

    /**
     * 获取系统日志
     * @NodeAnotation(title="获取系统日志")
     *
     * @return void
     */
    public function systemlog()
    {
        if ($this->request->isAjax()) {

            // 获取日志数据
            $data = $this->getLogData();

            return json($data);
        }

        return $this->fetch();
    }

    /**
     * 获取CURL请求日志
     * @NodeAnotation(title="获取CURL请求日志")
     * 
     * @return void
     */
    public function curllog()
    {
        if ($this->request->isAjax()) {

            // 获取日志数据
            $data = $this->getLogData(1);

            return json($data);
        }

        return $this->fetch();
    }

    /**
     * 获取ERROR错误日志
     * @NodeAnotation(title="获取ERROR错误日志")
     *
     * @return void
     */
    public function errorlog()
    {
        if ($this->request->isAjax()) {

            // 获取日志数据
            $data = $this->getLogData(2);

            return json($data);
        }

        return $this->fetch();
    }
}
