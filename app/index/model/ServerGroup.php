<?php

namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 服务器组
 */
class ServerGroup extends TimeModel
{
    /**
     * 去除服务器组中的服务器ID
     *
     * @param integer $sid
     * @return boolean
     */
    public static function removeServerId($server_id = 0): bool
    {
        if ($server_id == null) {

            throw new \Exception('去除服务器组中的服务器ID失败，参数为空', 1);
        }

        // 获取包含服务器ID 的组
        $groups = self::whereLike('server', "%$server_id%")->select();

        if ($groups->isEmpty()) {

            return false;
        }

        foreach ($groups as $val) {

            $server = json_decode($val->server, true);

            foreach ($server as $skey => $sid) {

                if ($sid == $server_id) {

                    unset($server[$skey]);
                }
            }

            $val->server = json_encode($server);
            $val->save();
        }

        return true;
    }

    /**
     * 获取全部渠道列表
     *
     * @return void
     */
    public static function getGroupList($sort = ['sort' => 'desc'])
    {
        $channels = self::where('server', '<>', '[]')->order($sort)->select();
        return $channels;
    }

    /**
     * 获取全部渠道名称列表
     *
     * @param boolean $is_json
     * @return array|string
     */
    public static function getGroupNameList($is_json = false)
    {

        $list     = self::getGroupList();
        $nameList = [];

        if (!$list->isEmpty()) {

            foreach ($list->toArray() as $val) {

                $nameList[$val['group_key']] = $val['name'] . '( ' . $val['group_key'] . ' )';
            }
        }

        return $is_json ? json_encode($nameList, JSON_UNESCAPED_UNICODE) : $nameList;
    }

    /**
     * 获取服务器组服务器信息
     *
     * @return void
     */
    public function getGroupServerInfo()
    {
        if ($this->id == null) {

            return;
        }

        // 获取组中的服务器ID
        $sid = json_decode($this->server, true);

        if (!$sid || !is_array($sid)) {

            return [];
        }

        return ServerList::field('`id`, `sort`, `shost`, `lhost`, `name`, `is_lan`')->whereIn('id', $sid)->where('status', 1)->select();
    }
}
