<?php
namespace app\index\model;

use app\common\model\TimeModel;
use think\helper\Arr;

/**
 * 活动配置表
 */
class ActivityConfig extends TimeModel
{
    /**
     * Redis包
     *
     * @var string
     */
    public static $redisPack   = 'ActivityConfig';

    /**
     * Redis过期时间（10分钟）
     *
     * @var string
     */
    public static $redisExpire = 600;

    /**
     * 活动列表
     *
     * @var array
     */
    public static $atypeList = [
        CfgConst::AtypeFirstRecharge   => '首冲奖励',// 1. 首冲奖励
        CfgConst::AtypeRegisterSign    => '创角签到',// 2. 创角签到
        CfgConst::AtypeMonthlySign     => '每月签到',// 3. 每月签到
        CfgConst::AtypeRecruitQuest    => '新兵任务',// 8. 新兵任务
        CfgConst::AtypeTotalRecharge   => '累计充值',// 9. 累计充值
    ];

    /**
     * 开启类型
     *
     * @var array
     */
    public static $openTypes = [
        1 => '开服', 
        2 => '创角', 
        3 => '限时', 
        4 => '常在'
    ];

    /**
     * 排序
     *
     * @var array
     */
    public static $sort = [
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
     * 通过类型，加载数据
     *
     * @param integer $atype
     * @return self
     */
    public static function loadDataWithType(int $atype): self
    {
        if ($atype == null) {

            throw new \Exception('通过类型，加载活动数据失败，参数为空', 1);
        }

        $data = self::where('atype', $atype)->find();

        if (!$data) {

            $data = new self;
            $data->id = null;
            $data->atype = $atype;

            if ($data->save() == false) {

                throw new \Exception('通过类型，加载活动数据失败', 1);
            }

            $data = self::where('atype', $atype)->find();
        }

        return $data;
    }

    /**
     * 获取全部类型列表
     *
     * @return array
     */
    public static function getTypesList(array $shield_types = []): array
    {
        $data = self::distinct(true)->field('`atype`, `title`')->select();

        if ($data->isEmpty()) {

            return [];
        }

        $sss = new self;
        $sqlLast = $sss->getLastSql();

        $types = [];

        foreach ($data as $val) {

            if ($shield_types && in_array($val->atype, $shield_types))
                continue;

            $types[strval($val->atype)] = $val->title;
        }

        return $types;
    }

    /**
     * 通过类型，获取全部配置
     *
     * @param integer $atype
     * @return array
     */
    public static function getConfigsWithType(int $atype): array
    {
        if (!$atype) {

            throw new \Exception('通过类型，获取全部配置，参数为空', 1);
        }

        $cfgs = [];
        $data = self::where('atype', $atype)->select();

        foreach ($data as $val) {
            
            $cfgs[] = json_decode($val['config'], true);
        }

        return $cfgs;
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

            // 检查是否为限时
            if ($val['open_type'] == 3 && !empty($val['open_value'])) {

                list($start, $end) = explode('~', $val['open_value']);
                $end = is_string($end) ? strtotime($end) : $end;
                $start = is_string($start) ? strtotime($start) : $start;

                if ($start > time()) {

                    $val['status'] = -1;
                }

                if ($end < time()) {

                    $val['status'] = -2;
                }
            }
            
            // 去除配置
            unset($val['config']);

            $list[] = array_merge($val, $cfgs);
        }

        return $list;
    }

    /**
     * 通过类型，获取列表
     *
     * @param integer $atype
     * @return array
     */
    public static function getListWithType(int $atype): array
    {
        if (!$atype) {

            throw new \Exception('通过类型，获取全部配置，参数为空', 1);
        }

        $list = [];
        $data = self::where('atype', $atype)->select();

        foreach ($data as $val) {
            
            $list[$val->id] = $val->id . '. ' . $val->title;
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

                $cfgs = self::getConfigsWithType(CfgConst::AtypeRegisterSign);

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
        // 刷新管理员数量
        SystemConfig::refreshStatisticsData();
    }

}