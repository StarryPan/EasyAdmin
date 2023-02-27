<?php

namespace app\index\model;


use app\common\model\TimeModel;

class MallGoods extends TimeModel
{

    protected $table = "";

    /**
     * 数据库回调，写入数据后
     *
     * @param [type] $obj
     * @return void
     */
    public static function onAfterWrite($obj)
    {
        // 刷新管理员数量
        SystemConfig::refreshStatisticsData();
    }

    public function cate()
    {
        return $this->belongsTo('app\index\model\MallCate', 'cate_id', 'id');
    }

}