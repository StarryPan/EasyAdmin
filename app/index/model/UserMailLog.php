<?php
namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 用户-发送邮件日志
 */
class UserMailLog extends TimeModel
{

    /**
     * 创建日志
     *
     * @param string $log_key
     * @param array $log_data
     * @param array $rewards
     * @return boolean
     */
    public static function createLog(string $log_key, $log_data = [], $rewards = []): bool
    {
        if (isNull($log_key) || isNull($log_data)) {

            throw new \Exception('创建邮件日志失败，参数为空', 1);
        }

        $insert['id']          = null;
        $insert['title']       = $log_data['title'];
        $insert['sender']      = $log_data['sender'];
        $insert['log_key']     = $log_key;
        $insert['userpms']     = $log_data['userpms'];
        $insert['content']     = $log_data['content'];
        $insert['rewards']     = json_encode($rewards);
        $insert['admin_id']    = session('admin.id');
        $insert['server_id']   = $log_data['server_id'];
        $insert['expire_day']  = $log_data['expire_day'];
        $insert['create_time'] = time();

        return self::insert($insert) ? true : false;
    }
}