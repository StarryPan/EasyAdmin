<?php

namespace app\index\model;

use think\facade\Cache;
use think\cache\driver\Redis;

/**
 * 缓存数据
 */
class CacheData
{
    /**
     * 缓存的REDIS-KEY
     *
     * @var array
     */
    public static $cachekey      = [];

    /**
     * 清除所有缓存
     *
     * @return boolean
     */
    public static function clearCache(): bool
    {
        Cache::clear();

        // 清除Redis缓存
        $redis = Redis::getInstance();
        $redis->clear();

        return true;
    }
    
}
