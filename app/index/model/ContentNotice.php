<?php

namespace app\index\model;

use think\cache\driver\Redis;
use app\common\model\TimeModel;

/**
 * 公告内容
 */
class ContentNotice extends TimeModel
{
    /**
     * Redis包
     *
     * @var string
     */
    public static $redisPack   = 'ContentNotice';

    /**
     * Redis过期时间（10分钟）
     *
     * @var string
     */
    public static $redisExpire = 600;

    /**
     * 排序
     *
     * @var array
     */
    public static $sort = [
        'id'   => 'desc',
        'sort' => 'desc',
    ];

    /**
     * 刷新配置
     *
     * @return void
     */
    public static function refreshConfig()
    {
        return updateRedisPackKey(self::$redisPack);
    }

    /**
     * 获取缓存的公告数据
     *
     * @param string $channel_key
     * @param integer $pre_state
     * @return array
     */
    public static function getCacheNoticeData($channel_key = '', $pre_state = 0): array
    {
        $redis     = Redis::getInstance();
        $redis_key = getRedisPackKey(self::$redisPack) . 'getCacheNoticeData_' . $channel_key . '_' . $pre_state;

        if (!$redis->exists($redis_key)) {
            
            $arr         = [];
            $now_date    = date('Y-m-d H:i:s', time());
            $cont_notice = new self;
            $cont_notice = $cont_notice->where('status', 1);
            $cont_notice = $cont_notice->where('channel_key', $channel_key);
            $cont_notice = $cont_notice->where('pre_state', $pre_state);
            $cont_notice = $cont_notice->where('start_date', '<=', $now_date);
            $cont_notice = $cont_notice->where('end_date', '>=', $now_date);

            // 获取游戏配置
            $notice_data = $cont_notice->order(self::$sort)->select();

            if (!$notice_data->isEmpty()) {

                $arr = $notice_data->toArray();
            }

            return $arr;
            $redis->set($redis_key, $arr, self::$redisExpire);
        }

        return $redis->get($redis_key);
    }

}