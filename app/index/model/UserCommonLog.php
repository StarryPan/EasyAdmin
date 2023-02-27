<?php

namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 用户-通用日志
 */
class UserCommonLog extends TimeModel
{

    /**
     * 解析日志数据
     *
     * @param array $list
     * @return array
     */
    public function analysis($list = []): array
    {
        $data = [];

        // 检查类型
        if (gettype($list) == 'object') {

            if ($list->isEmpty()) {

                return [];
            }

            $list = $list->toArray();
        }

        // log数据合并方法
        $logMergeFun = function ($val) {

            $log_data            = array_merge($val, json_decode($val['log_data'], true));
            $log_data['rs_data'] = json_decode($val['rs_data'], true);
            unset($log_data['log_data']);

            return $log_data;
        };

        // 检查是否为一维数组
        if (count($list) != count($list, 1)) {

            foreach ($list as $val) {

                $data[] = $logMergeFun($val);
            }
            
        } else {

            $data = $logMergeFun($list);
        }

        return $data;
    }

    /**
     * 创建日志
     *
     * @param string $log_key
     * @param array $log_data
     * @return boolean
     */
    public static function createLog(string $log_key, array $log_data = [], array $rs_data = []): bool
    {
        if (isNull($log_key) || isNull($log_data)) {

            throw new \Exception('创建邮件日志失败，参数为空', 1);
        }

        $insert['id']          = null;
        $insert['log_key']     = $log_key;
        $insert['rs_data']     = json_encode($rs_data);
        $insert['log_data']    = json_encode($log_data);
        $insert['admin_id']    = session('admin.id');
        $insert['create_time'] = time();

        return self::insert($insert) ? true : false;
    }
}
