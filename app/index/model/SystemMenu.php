<?php

namespace app\index\model;

use think\cache\driver\Redis;
use app\common\model\TimeModel;
use app\common\constants\MenuConstant;

class SystemMenu extends TimeModel
{
    /**
     * Redis包
     *
     * @var string
     */
    public static $redisPack  = 'SystemMenu';


    /**
     * 数据库回调，写入数据后
     *
     * @param [type] $obj
     * @return void
     */
    public static function onAfterWrite($obj)
    {
        updateRedisPackKey(self::$redisPack);
    }
    /**
     * 获取缓存列表
     *
     * @param array $sort
     * @return array
     */
    public function getCacheList($sort = []): array
    {
        $redis     = Redis::getInstance();
        $redis_key = getRedisPackKey(self::$redisPack) . 'getCacheList' . implode('_', $sort);
        $redis_data = $redis->get($redis_key);

        if ($redis_data != null) {

            return $redis_data;
        }

        $cnt  = 0;
        $arr  = [];
        $list = $this->order($sort)->select();

        if (!$list->isEmpty()) {

            $cnt = count($list);
            $arr = $list->toArray();
        }

        $data = [$arr, $cnt];
        $redis->set($redis_key, $data);

        return $data;
    }

    public function getPidMenuList()
    {
        $list        = $this->field('id,pid,title')
            ->where([
                ['pid', '<>', MenuConstant::HOME_PID],
                ['status', '=', 1],
            ])
            ->select()
            ->toArray();
        $pidMenuList = $this->buildPidMenu(0, $list);
        $pidMenuList = array_merge([[
            'id'    => 0,
            'pid'   => 0,
            'title' => '顶级菜单',
        ]], $pidMenuList);
        return $pidMenuList;
    }

    protected function buildPidMenu($pid, $list, $level = 0)
    {
        $newList = [];
        foreach ($list as $vo) {
            if ($vo['pid'] == $pid) {
                $level++;
                foreach ($newList as $v) {
                    if ($vo['pid'] == $v['pid'] && isset($v['level'])) {
                        $level = $v['level'];
                        break;
                    }
                }
                $vo['level'] = $level;
                if ($level > 1) {
                    $repeatString = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $markString   = str_repeat("{$repeatString}├{$repeatString}", $level - 1);
                    $vo['title']  = $markString . $vo['title'];
                }
                $newList[] = $vo;
                $childList = $this->buildPidMenu($vo['id'], $list, $level);
                !empty($childList) && $newList = array_merge($newList, $childList);
            }
        }
        return $newList;
    }
}
