<?php

namespace app\index\model;

use app\common\model\TimeModel;
use think\cache\driver\Redis;

/**
 * 用户兑换码
 */
class UserRedeemCode extends TimeModel
{
    /**
     * 缓存KEY包
     *
     * @var string
     */
    private $redisPack    = 'UserRedeemCode:';

    /**
     * 缓存过期时间
     *
     * @var integer
     */
    private $redisExpired = 86400;

    /**
     * 生成奖励码
     * @param array $data 数组
     * @return boolean
     */
    public function generateRwardsCode(array $data): bool
    {
        if (empty($data['code_count'])) {

            throw new \Exception('生成兑换码失败，生成数量为空', 1);
        }

        $nums        = intval($data['code_count']);
        $prefix      = $data['code_prefix'] ?? '';
        $length      = intval($data['length'] ?? 0);
        $exist_array = $data['exist_array'] ?? [];

        if (!$prefix) {

            throw new \Exception('生成兑换码失败，生成长度为空', 1);
        }

        unset($data['length'],
        $data['code_count'],
        $data['exist_array'],
        $data['code_prefix']);

        // 获取兑换码最大ID
        $inc_max      = self::max('id');
        $day_date     = date('yMDHis', time());
        $characters   = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz';
        $insert_codes = array(); // 这个数组用来接收生成的优惠码

        for ($j = 0; $j < $nums; $j++) {

            $code       = '';
            $vinfo      = $data;
            $inc_max    = ($inc_max + 1);
            $characters = $characters . md5($day_date . $inc_max);

            for ($i = 0; $i < $length; $i++) {

                $code .= $characters[mt_rand(0, strlen($characters) - 1)];
            }

            // 排除已经存在的优惠码
            if (!in_array($code, $exist_array)) {

                // 将生成的新优惠码赋值给insert_codes数组
                $vinfo['code']  = $prefix . $code;
                $insert_codes[] = $vinfo;
                $exist_array[]  = $code;
            } else {

                $j--;
            }
        }

        // 检查是否添加
        if ($insert_codes != null) {

            // 批量添加到数据库
            if (!$this->insertAll($insert_codes)) {

                throw new \Exception('生成兑换码失败，批量插入到数据库错误', 1);
            }

            return true;
        }

        return false;
    }

    /**
     * 检查批号是否重复兑换
     *
     * @param integer $uid
     * @param integer $server_id
     * @return boolean
     */
    public function checkBatchRepeat($uid = 0, int $server_id): bool
    {
        if (empty($uid) || empty($server_id)) {

            throw new \Exception('生成兑换码失败，生成数量为空', 1);
        }

        $redis      = Redis::getInstance();
        $redis_key  = $this->redisPack . 'checkBatchRepeat_' . $uid . '_' . $server_id . '_' . $this->batch;
        $redis_data = $redis->get($redis_key);

        if ($redis_data !== false) {

            return ($redis_data > 0);
        }

        $count = UserRedeemCodeLog::where('uid', $uid)->where('sid', $server_id)->where('batch', $this->batch)->count();
        $redis->set($redis_key, $count, $this->redisExpired);
        return ($count > 0);
    }

    /**
     * 创建兑换日志
     *
     * @param array $data
     * @return boolean
     */
    public function createLog(array $data): bool
    {
        if (empty($data)) {

            throw new \Exception('创建兑换日志失败，参数为空', 1);
        }

        $log              = new UserRedeemCodeLog;
        $log->id          = null;
        $log->uid         = $data['uid'];
        $log->code        = $data['code'];
        $log->batch       = $this->batch;
        $log->rewards     = $this->rewards;
        $log->user_ip     = $data['ip'];
        $log->rcode_id    = $this->id;
        $log->server_id   = $data['server_id'];
        $log->channel_key = $data['channel_key'];
        $log->create_time = time();

        if ($log->save() === false) {

            throw new \Exception('创建兑换日志失败，保存错误', 1);
        }

        // 刷新缓存
        $redis     = Redis::getInstance();
        $redis_key = $this->redisPack . 'checkBatchRepeat_' . $log->uid . '_' . $log->server_id . '_' . $log->batch;
        $redis->set($redis_key, 1, $this->redisExpired);
        return true;
    }
}
