<?php

namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 服务器渠道列表
 */
class ServerChannel extends TimeModel
{
    /**
     * 排序
     *
     * @var array
     */
    public $sort           = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    /**
     * 获取全部渠道列表
     *
     * @return void
     */
    public static function getChannelList($sort = ['sort' => 'desc'])
    {
        $channels = self::where('status', 1)->order($sort)->select();
        return $channels;
    }

    /**
     * 获取全部渠道名称列表
     *
     * @param boolean $is_json
     * @return array|string
     */
    public static function getChannelNameList($is_json = false){

        $list     = self::getChannelList();
        $nameList = [];

        if (!$list->isEmpty()) {
            
            foreach ($list->toArray() as $val) {
                
                $nameList[$val['channel_key']] = $val['channel_name'] . '( '.$val['channel_key'].' )';
            }
        }

        return $is_json ? json_encode($nameList, JSON_UNESCAPED_UNICODE) : $nameList;
    }

}