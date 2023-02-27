<?php

namespace app\index\model;

use think\facade\Cache;
use app\common\model\TimeModel;
use app\index\service\TriggerService;

class SystemConfig extends TimeModel
{
    /**
     * 缓存有效期-24小时
     *
     * @var integer
     */
    private static $cache_expire = 86400;

    /**
     * 获取统计数据
     *
     * @return void
     */
    public static function getStatisticsData(): array
    {
        $cache_data = Cache::get('getStatisticsData');

        if (!$cache_data) {

            $cache_data = [];
            $cache_data['admin_count']    = SystemAdmin::where('status', 1)->count();
            $cache_data['goods_count']    = MallGoods::where('status', 1)->count();
            $cache_data['server_count']   = ServerList::where('status', 1)->count();
            $cache_data['activity_count'] = ActivityConfig::where('status', 1)->count();
            Cache::set('getStatisticsData', $cache_data, self::$cache_expire);
        }

        return $cache_data;
    }

    /**
     * 删除统计数据
     *
     * @return void
     */
    public static function refreshStatisticsData(): bool
    {
        Cache::delete('getStatisticsData');
        return true;
    }

    /**
     * 获取白名单IP地址
     *
     * @return array
     */
    public static function getWhiteIps(): array
    {
        $white_ips = sysconfig('back', 'white_ips');
        return explode(',', $white_ips) ?: [];
    }

    /**
     * 获取系统配置
     *
     * @param string $group
     * @param string $name
     * @return void
     */
    public static function getConfig($group = '', $name = '')
    {
        if ($group == null) {

            throw new \Exception('获取系统配置失败，参数为空', 1);
        }

        $value          = null;
        $where['group'] = $group;

        if (!empty($name)) {

            $where['name'] = $name;
            $value = self::where($where)->value('value');
        } else {

            $value = self::where($where)->column('value', 'name');
        }

        return $value;
    }

    /**
     * 保存系统配置
     *
     * @param string $group
     * @param array $cfgs
     * @return boolean
     */
    public static function saveConfig($group = '', $cfgs = []): bool
    {
        if ($group == null || $cfgs == null) {

            throw new \Exception('保存系统配置失败，参数为空', 1);
        }

        if (!is_array($cfgs)) {

            throw new \Exception('保存系统配置失败，cfgs 必须为数组', 1);
        }

        foreach ($cfgs as $name => $value) {

            $cfg = self::where('group', $group)->where('name', $name)->find();

            if (!$cfg) {

                $cfg = new self;
            }

            // 如果被修改值相同就不保存
            if (!empty($cfg->value) && $cfg->value == $value) {

                continue;
            }

            $cfg->save([
                'name'  => $name,
                'group' => $group,
                'value' => $value
            ]);
        }

        TriggerService::updateSysconfig();
        return true;
    }

    /**
     * 指定KEY，自增值
     *
     * @param string $key
     * @param integer $inc_num
     * @param string $group
     * @return integer
     */
    public static function incValueWithKey(string $key, int $inc_num = 1, string $group = 'back'): int
    {
        if (empty($key) || empty($inc_num)) {

            throw new \Exception('指定KEY，自增值失败，参数为空', 1);
        }

        $cfg = self::where('name', $key)->find();

        if (!$cfg) {

            $cfg        = new self;
            $cfg->value = 0;
        }

        $cfg->value += intval($inc_num);

        $save = $cfg->save([
            'name'  => $key,
            'group' => $group,
            'value' => $cfg->value
        ]);

        if ($save === false) {

            throw new \Exception('指定KEY，自增值失败，保存错误', 1);
        }

        return $cfg->value;
    }
}
