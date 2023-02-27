<?php

namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 服务器列表
 */
class ServerList extends TimeModel
{
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

    /**
     * 获取服务器列表
     *
     * @return void
     */
    public static function getServerList()
    {
        $list = self::where('status', 1)->order('sort', 'desc')->select();
        return $list;
    }

    /**
     * 获取服务器信息
     *
     * @param integer $id
     * @return void
     */
    public static function getServerInfo($id = 0)
    {

        if ($id == null) {

            throw new \Exception('获取服务器信息失败，参数为空。', 1);
        }

        $info = self::where('id', $id)->find();

        if (!$info) {

            return null;
        }

        return $info;
    }

    /**
     * 获取权限服务器列表
     *
     * @return self
     */
    public static function getAuthServerList()
    {
        $server = self::where('status', 1);

        if (!SystemAdmin::checkAuthMax()) {

            $server = $server->whereIn('id', SystemAdmin::getAuthServerId());
        }

        return $server->order('sort', 'desc')->select();
    }

    /**
     * 获取权限服务器名称列表
     *
     * @param boolean $is_json
     * @return array|string
     */
    public static function getAuthServerNameList($is_json = false)
    {
        $list     = self::getAuthServerList();
        $nameList = [];

        if (!$list->isEmpty()) {
            
            foreach ($list->toArray() as $val) {
                
                $nameList[$val['id']] = $val['id'] . '. ' . $val['name'];
            }
        }

        return $is_json ? json_encode($nameList, JSON_UNESCAPED_UNICODE) : $nameList;
    }
}
