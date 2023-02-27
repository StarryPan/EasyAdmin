<?php

namespace app\index\model;

use think\cache\driver\Redis;
use app\common\model\TimeModel;

/**
 * 游戏配置
 */
class GameConfig extends TimeModel
{

    /**
     * 缓存的包
     *
     * @var string
     */
    private static $redisPack    = 'GameConfig:';

    /**
     * 缓存有效期一周
     *
     * @var integer
     */
    private static $redis_expire = 604800;


    /**
     * 获取配置数据
     *
     * @param string $cfg_key
     * @return array
     */
    public static function getConfig($cfg_key = '', $default = []): array
    {
        if (isNull($cfg_key)) {

            throw new \Exception('获取配置数据失败，参数为空', 1);
        }

        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfig_' . $cfg_key;
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfg_json = self::where('cfg_key', $cfg_key)->where('lan_type', 0)->value('cfg_data');

        if (!$cfg_json) {

            if ($default !== '') {

                return $default;
            }

            throw new \Exception('获取配置数据失败，KEY: ' . $cfg_key . '不存在！', 1);
        }

        $cfg_data = json_decode($cfg_json, true);
        $redis->set($redis_key, $cfg_data, self::$redis_expire);

        return $cfg_data;
    }

    /**
     * 保存配置数据
     *
     * @param string $cfg_key
     * @param array $cfg_data
     * @return boolean
     */
    public static function saveConfig($cfg_key = '', array $value = []): bool
    {
        if (isNull($cfg_key) || isNull($value)) {

            throw new \Exception('保存配置数据失败，参数为空', 1);
        }

        $cfg_data = self::where('cfg_key', $cfg_key)->where('lan_type', 0)->find();

        if (!$cfg_data) {

            $cfg_data = new self;
            $cfg_data->cfg_key = $cfg_key;
            $cfg_data->create_time = time();
        }

        $cfg_data->cfg_data = json_encode($value, JSON_UNESCAPED_UNICODE);
        $cfg_data->update_time = time();

        if ($cfg_data->save() == false) {

            throw new \Exception('保存配置失败');
        }

        // 刷新缓存
        return $cfg_data->refreshRedis();
    }

    /**
     * 获取通用的配置
     *
     * @param string $ctype
     * @return array
     */
    public static function getCommonConfig($ctype = ''): array
    {
        $cfgs = [];

        switch ($ctype) {
            case 'items':
                $cfgs['config_items'] = GameConfig::getConfigItems();
                break;

            case 'heros':
                $cfgs['config_heros'] = GameConfig::getConfigHeros();
                break;

            case 'chest':
                $cfgs['server_list'] = ServerList::getAuthServerNameList();
                $cfgs['chest_list']  = GameConfig::getConfigChestNameList();
                break;

            case 'servers':
                $cfgs['server_list']  = ServerList::getAuthServerNameList();
                break;

            case 'logquery':
                $cfgs['server_list'] = ServerList::getAuthServerNameList();
                $cfgs['logtable_list'] = GameConfig::getLogTableNameList();
                break;

            case 'userinfo':
                $cfgs['server_list'] = ServerList::getAuthServerNameList();
                break;

            case 'loganalysis':
                $cfgs['item_list'] = GameConfig::getConfigItemList();
                break;

            default:
                $cfgs['config_items'] = GameConfig::getConfigItems();
                break;
        }

        return $cfgs;
    }

    /**
     * 刷新缓存
     *
     * @return boolean
     */
    public static function refreshRedis(): bool
    {
        // 刷新缓存版本号
        updateRedisPackKey(self::$redisPack);
        return true;
    }

    /**
     * 获取全部道具配置
     *
     * @return array
     */
    public static function getConfigItems(): array
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigItems';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('Item');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $cfg_data = [];

        foreach ($cfgs as $cfg_val) {

            if (empty($cfg_val['type'])) {

                continue;
            }

            $cfg_data[$cfg_val['id']] = $cfg_val;
        }

        $redis->set($redis_key, $cfg_data, self::$redis_expire);

        return $cfg_data;
    }

    /**
     * 获取全部道具配置列表
     *
     * @return array
     */
    public static function getConfigItemList(): array
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigItemList';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfigItems();

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $cfg_list = [];

        foreach ($cfgs as $cfg_val) {

            $cfg_list[$cfg_val['id']] = $cfg_val['id'] . '.' . $cfg_val['name'];
        }

        $redis->set($redis_key, $cfg_list, self::$redis_expire);

        return $cfg_list;
    }

    /**
     * 获取全部角色配置
     *
     * @return array
     */
    public static function getConfigHeros(): array
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigHeros';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('Role');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $cfg_data = [];

        foreach ($cfgs as $cfg_val) {

            if (empty($cfg_val['name'])) {

                continue;
            }

            $cfg_data[$cfg_val['id']] = $cfg_val;
        }

        $redis->set($redis_key, $cfg_data, self::$redis_expire);

        return $cfg_data;
    }

    /**
     * 获取任务全部枚举
     *
     * @return array
     */
    public static function getConfigQuestGoals(): array
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigQuestGoals';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('TaskGoal');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $cfg_data = [];

        foreach ($cfgs as $cfg_val) {

            if (empty($cfg_val['name'])) {

                continue;
            }

            $cfg_data[$cfg_val['id']] = $cfg_val;
        }

        $redis->set($redis_key, $cfg_data, self::$redis_expire);

        return $cfg_data;
    }

    /**
     * 差异数据对比
     *
     * @param string $old_json
     * @param string $new_json
     * @return string
     */
    public function diffConfigData($old_json = '', $new_json = ''): string
    {

        if (isNull($old_json) || isNull($new_json)) {

            throw new \Exception('差异数据对比失败，参数为空', 1);
        }

        // 处理旧数据
        $old_arr  = json_decode($old_json, true);
        $old_data = [];

        foreach ($old_arr as $old_k => $old_val) {
            $old_data[$old_k] = json_encode($old_val, JSON_UNESCAPED_UNICODE);
        }

        // 处理新数据
        $new_arr  = json_decode($new_json, true);
        $new_data = [];

        foreach ($new_arr as $new_k => $new_val) {
            $new_data[$new_k] = json_encode($new_val, JSON_UNESCAPED_UNICODE);
        }

        // 对比两个数据的差异 - 添加
        $diff_str   = '';
        $diff_data  = array_diff($new_data, $old_data);

        // 对比颜色
        $diff_color = 'color: green;';

        if ($diff_data == null) {

            // 对比两个数据的差异 - 减少
            $diff_data  = array_diff($old_data, $new_data);
            $diff_color = 'color: red;';
        }

        if ($diff_data != null) {

            foreach ($diff_data as $json) {

                $diff_str .= $json . ', ';
            }

            $diff_str = substr($diff_str, 0, strlen($diff_str) - 2);
        }

        if ($diff_str == '') {

            return $diff_str;
        }

        return '<span style="' . $diff_color . '">' . $diff_str . '</span>';
    }

    /**
     * 通过KEY获取文本翻译
     *
     * @param string $_key
     * @param string $def
     * @return string
     */
    public static function getConfigTextTransWithKey($_key = '', $def = ''): string
    {
        if ($_key == null) {

            return $_key;
        }

        $redis      = Redis::getInstance();
        $exist_key  = getRedisPackKey(self::$redisPack) . 'getConfigTextTransWithKey';
        $trans_key  = $exist_key  . '_' . $_key;

        if (!$redis->exists($exist_key)) {

            $cfgs = self::getConfig('LuaTextTrans');

            foreach ($cfgs as $cval) {

                $rs_key  = $exist_key  . '_' . $cval['index'];
                $redis->set($rs_key, $cval['str_chs'], self::$redis_expire);
            }

            $redis->set($exist_key, 1, self::$redis_expire);
        }

        return $redis->get($trans_key) ?: $def;
    }

    /**
     * 通过API获取CURL的标题
     *
     * @param string $_api
     * @param string $def
     * @return string
     */
    public static function getConfigCurlTitleWithApi($_api = '', $default = ''): string
    {
        if ($_api == null) {

            return $_api;
        }

        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigCurlTitleWithApi';
        $redis_data = $redis->get($redis_key);
        if ($redis_data == null) {

            $cfgs = self::getConfig('CurlApiTitle', []);

            if (!$cfgs) {

                return $default;
            }

            $redis_data = [];

            foreach ($cfgs as $cval) {

                $redis_data[strval($cval['api'])] = $cval['title'];
            }

            $redis->set($redis_key, $redis_data, self::$redis_expire);
        }

        if (!empty($redis_data[$_api])) {

            return $redis_data[$_api];
        }

        return $default;
    }

    /**
     * 获取日志表名列表
     *
     * @return void
     */
    public static function getLogTableNameList()
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getLogTableNameList';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('LogTable');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $list = [];

        foreach ($cfgs as $cfg_val) {

            $list[$cfg_val['key']] = $cfg_val['name'];
        }

        $redis->set($redis_key, $list, self::$redis_expire);

        return $list;
    }

    /**
     * 获取抽卡配置
     *
     * @return void
     */
    public static function getConfigChestNameList()
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigChestNameList';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('Drawctrl');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $list = [];

        foreach ($cfgs as $cfg_val) {

            $list[$cfg_val['id']] = $cfg_val['name'] ?? '';
        }

        $redis->set($redis_key, $list, self::$redis_expire);

        return $list;
    }
    
    /**
     * 获取商店商品配置
     *
     * @return void
     */
    public static function getConfigShopDetailList()
    {
        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigShopDetailList';
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('ShopDetail');

        if ($cfgs == null) {

            throw new \Exception('获取全部道具配置失败，配置为空', 1);
        }

        $list = [];

        foreach ($cfgs as $cfg_val) {

            $list[$cfg_val['id']] = $cfg_val['name'] ?? '';
        }

        $redis->set($redis_key, $list, self::$redis_expire);

        return $list;
    }

    /**
     * 通过商品ID，获取商店商品配置
     *
     * @param integer $id
     * @return array
     */
    public static function getConfigShopDetailWithId(int $id): array
    {
        if (empty($id)) {

            throw new \Exception('通过商品ID，获取商店商品配置失败，参数为空', 1);
        }

        $redis      = Redis::getInstance();
        $redis_key  = getRedisPackKey(self::$redisPack) . 'getConfigShopDetailWithId_' . $id;
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cfgs = self::getConfig('ShopDetail');

        if ($cfgs == null) {

            throw new \Exception('获取商店商品配置失败，配置为空 [ShopDetail]', 1);
        }

        $cfg_data = [];

        foreach ($cfgs as $cfg_val) {

            if ($cfg_val['id'] == $id) {

                $cfg_data = $cfg_val;
                break;
            }
        }

        $redis->set($redis_key, $cfg_data, self::$redis_expire);
        return $cfg_data;
    }
    
    
}
