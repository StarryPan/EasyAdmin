<?php

namespace app\index\model;

use think\cache\driver\Redis;
use app\common\model\TimeModel;

/**
 * 服务器渠道配置
 */
class ServerChannelConfig extends TimeModel
{
    /**
     * Redis包
     *
     * @var string
     */
    public static $redisPack   = 'ServerChannelConfig';

    /**
     * Redis过期时间（10分钟）
     *
     * @var string
     */
    public static $redisExpire = 600;

    /**
     * 去除值为空的配置
     *
     * @var array
     */
    public static $removeValueEmpty = [
        'hot_url',
        'obb_url',
        'OBB_Size',
        'force_update_version',
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
     * 获取渠道配置
     *
     * @param string $channel_key
     * @param integer $pre_state
     * @return array
     */
    public static function getChannelConfig($channel_key = '', $pre_state = 0): array
    {
        if (!$channel_key) {

            return [];
        }

        // 获取渠道配置
        $cfg = self::where('channel_key', $channel_key)->where('pre_state', $pre_state)->value('config');

        if (!$cfg) {

            return [];
        }

        return json_decode($cfg, true);
    }

    /**
     * 获取缓存的渠道配置
     *
     * @param string $channel_key
     * @param integer $pre_state
     * @return array
     */
    public static function getCacheChannelConfig($channel_key = '', $pre_state = 0): array
    {
        $redis     = Redis::getInstance();
        $redis_key = getRedisPackKey(self::$redisPack) . 'getCacheChannelConfi1g_' . $channel_key . '_' . $pre_state;
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        // 获取游戏配置
        $channel_cfgs = self::getChannelConfig($channel_key, $pre_state);

        $redis->set($redis_key, $channel_cfgs, self::$redisExpire);
        return $channel_cfgs;
    }

}
