<?php

namespace app\index\model;

use app\common\model\TimeModel;
use think\cache\driver\Redis;

class SystemAdmin extends TimeModel
{
    /**
     * 保持登陆状态
     *
     * @var integer
     */
    public $keep_login = false;

    /**
     * 最大权限
     *
     * @var integer
     */
    public static $auth_max = 1;
    

    /**
     * 保存数据
     *
     * @param boolean $up_session
     * @return boolean
     */
    public function saveData($data = [], $up_session = true): bool
    {
        $save = $this->save($data);
        $admin = $this->toArray();
        unset($this['password']);
        $admin['expire_time'] = $this->keep_login ? true : (time() + 7200);
        // 更新Session
        $up_session && session('admin', $admin);
        // 取消验证状态
        self::setCaptchaState(0);

        return $save;
    }

    /**
     * 设置保持登陆状态
     *
     * @param integer $keep_login
     * @return boolean
     */
    public function setKeepLogin($keep_login = 0): bool
    {
        $this->keep_login = $keep_login ? true : false;
        return true;
    }

    /**
     * 数据库回调，写入数据后
     *
     * @param [type] $obj
     * @return void
     */
    public static function onAfterWrite($obj)
    {
        // 刷新管理员数量
        SystemConfig::refreshStatisticsData();
    }

    /**
     * 获取规则列表
     *
     * @return void
     */
    public function getAuthList()
    {
        $list = (new SystemAuth())
            ->where('status', 1)
            ->column('title', 'id');
        return $list;
    }

    private static function getCaptchaKey(): string
    {
        $real_ip = \EasyAdmin\tool\CommonTool::getRealIp();
        return 'LoginCaptcha:' . $real_ip;
    }

    public static function getCaptchaState(): bool
    {
        $redis      = Redis::getInstance();
        $redis_key  = self::getCaptchaKey();
        $redis_data = $redis->get($redis_key);

        return !empty($redis_data);
    }

    public static function setCaptchaState(int $state): bool
    {
        $redis     = Redis::getInstance();
        $redis_key = self::getCaptchaKey();
        return $redis->set($redis_key, $state, 86400) ? true : false;
    }


    /**
     * 检查最高权限
     *
     * @return boolean
     */
    public static function checkAuthMax(): bool
    {
        $admin    = session('admin');
        $auth_ids = explode(',', $admin['auth_ids']);

        if ($auth_ids && in_array(self::$auth_max, $auth_ids)) {

            return true;
        }

        return false;
    }

    /**
     * 获取权限服务器ID
     *
     * @return array
     */
    public static function getAuthServerId(): array
    {
        $admin       = session('admin');
        $auth_ids    = explode(',', $admin['auth_ids']);
        $auth_server = SystemAuth::field('`server`')->distinct(true)->whereIn('id', $auth_ids)->select();

        if ($auth_server->isEmpty()) {

            return [];
        }

        $sid_arr = [];

        foreach ($auth_server as $obj) {
            $arr = explode(',', $obj->server);
            empty($arr) ? $sid_arr : $sid_arr = array_merge($sid_arr, $arr);
        }

        return $sid_arr;
    }

    /**
     * 获取管理员列表
     *
     * @return void
     */
    public static function getAdminList()
    {
        $list = self::where('status', 1)->select();
        return $list;
    }

    /**
     * 获取管理员名称列表
     *
     * @param boolean $is_kv 是否为键值对
     * @return array|string
     */
    public static function getAdminNameList($is_json = false)
    {
        $arr  = [];
        $list = self::field('`id`, `username`, `remark`')->where('status', 1)->select();

        if (!$list->isEmpty()) {

            foreach ($list as $obj) {
                $arr[$obj->id] = $obj->remark . ' ( ' . $obj->username . ' )';
            }
        }

        return $is_json ? json_encode($arr, JSON_UNESCAPED_UNICODE) : $arr;
    }
}
