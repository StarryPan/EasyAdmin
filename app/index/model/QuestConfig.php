<?php
namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 任务配置表
 */
class QuestConfig extends TimeModel
{
    /**
     * Redis包
     *
     * @var string
     */
    public static $redisPack   = 'QuestConfig';

    /**
     * Redis过期时间（10分钟）
     *
     * @var string
     */
    public static $redisExpire = 600;

    /**
	 * 基础自增数量
	 * 
	 */
	public static $_increase   = 100000;

    /**
     * 排序
     *
     * @var array
     */
    public static $sort         = [
        'id'   => 'desc',
    ];



    /**
     * 加载活动配置数据
     *
     * @param integer $aid
     * @return self
     */
    public static function loadData($aid = 0): self
    {
        if ($aid == null) {

            throw new \Exception('加载活动配置数据失败，参数为空', 1);
        }

        $aconfig = self::get($aid);

        if (!$aconfig) {

            $aconfig     = new self;
            $aconfig->id = $aid;

            if ($aconfig->save() == false) {

                throw new \Exception('加载活动配置数据失败', 1);
            }

            $aconfig = self::get($aid);
        }

        return $aconfig;
    }

    /**
     * 通过类型，获取全部数据
     *
     * @param integer $type
     * @return array
     */
    public static function getDatasWithType(int $type): array
    {
        if (!$type) {

            throw new \Exception('通过类型，获取全部数据，参数为空', 1);
        }

        $data = self::where('type', $type)->select();

        return $data;
    }

    /**
     * 获取配置数据
     *
     * @param object $arr
     * @return void
     */
    public static function getConfigData(object $arr)
    {
        if (!$arr || $arr->isEmpty()) {

            return [];
        }

        $list = [];

        foreach ($arr->toArray() as $val) {
            
            // 解析配置
            $cfgs = json_decode($val['config'], true) ?: [];
            
            // 去除配置
            unset($val['config']);

            $list[] = array_merge($val, $cfgs);
        }

        return $list;
    }

    /**
     * 验证参数是否存在
     *
     * @param integer $param
     * @param string $option
     * @return boolean
     */
    public static function validateIsExist(int $param, string $option): bool
    {
        switch ($option) {
            case 'register':

                $cfgs = self::getConfigsWithType(CfgConst::typeRegisterSign);

                foreach ($cfgs as $val) {
                    
                    if ($val['day'] == $param) {
                        
                        return true;
                    }
                }

                break;
            
            default:
                break;
        }

        return false;
    }


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
     * 数据库回调，写入数据后
     *
     * @param array $obj
     * @return void
     */
    public static function onAfterWrite($obj)
    {

    }

    /**
     * 获取自增ID
     *
     * @param integer $type
     * @return integer
     */
    public static function getIncId(int $type): int
    {
        // 获取最大id
        $max_id = self::where('type', $type)->max('id');
        $num    = ($type * self::$_increase);

        if ($max_id > self::$_increase) {

            $max_id = ($max_id - $num);
        }

        $max_id++;

        return intval($num + $max_id);
    }

}