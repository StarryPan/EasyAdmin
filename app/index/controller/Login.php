<?php
namespace app\index\controller;


use think\facade\Env;
use app\index\model\SystemAdmin;
use think\captcha\facade\Captcha;
use app\common\controller\AdminController;

/**
 * Class Login
 * @package app\index\controller
 */
class Login extends AdminController
{
    /**
     * 失败次数
     *
     * @var integer
     */
    private $error_num = 5;



    /**
     * 初始化方法
     */
    public function initialize()
    {
        parent::initialize();
        $action = $this->request->action();
        if (!empty(session('admin')) && !in_array($action, ['out'])) {
            $adminModuleName = config('app.admin_alias_name');
            $this->success('已登录，无需再次登录', [], __url("@{$adminModuleName}"));
        }
    }

    /**
     * 用户登录
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        $captcha = SystemAdmin::getCaptchaState();
        // $captcha = Env::get('easyadmin.captcha', 1);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'username|用户名'      => 'require',
                'password|密码'       => 'require',
                'keep_login|是否保持登录' => 'require',
            ];
            $captcha && $rule['captcha|验证码'] = 'require|captcha';
            $this->validate($post, $rule);
            try {

                $admin = SystemAdmin::where(['username' => $post['username']])->find();
                if (empty($admin)) {
                    throw new \Exception('用户不存在');
                }

                if ($admin->status == 0) {
                    throw new \Exception('账号已被禁用');
                }

                if (password($post['password']) != $admin->password) {
                    $err_msg = '密码输入有误';
                    $admin->error_num += 1;
                    if ($admin->error_num >= 2) {
                        $err_msg .= '，你还有'.($this->error_num - $admin->error_num).'次即将被禁用';
                    }

                    if ($admin->error_num >= $this->error_num) {
                        $admin->status = 0;
                        $err_msg = '账号已被禁用，请尽快联系管理员';
                    }
                    $admin->save();
                    throw new \Exception($err_msg);
                }

                $admin->error_num  = 0;
                $admin->login_num += 1;
                $admin->setKeepLogin($post['keep_login']);
                $admin->saveData();
                
            } catch (\Exception $e) {
                // 启用验证状态
                SystemAdmin::setCaptchaState(1);
                return $this->error($e->getMessage());
            }
            
            return $this->success('登录成功');
        }
        $this->assign('captcha', $captcha);
        return $this->fetch();
    }

    /**
     * 用户退出
     * @return mixed
     */
    public function out()
    {
        session('admin', null);
        $this->success('退出登录成功');
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }
}
