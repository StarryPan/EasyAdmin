<?php

namespace app\index\model;


use app\common\model\TimeModel;

class SystemAuth extends TimeModel
{

    /**
     * 根据角色ID获取授权节点
     * @param $authId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAuthorizeNodeListByAdminId($authId): array
    {
        $systemNode    = new SystemNode();
        $nodelList     = $systemNode->where('is_auth', 1)->field('id,node,title,type,is_auth')->select()->toArray();
        $checkNodeList = (new SystemAuthNode())->where('auth_id', $authId)->column('node_id');
        
        $all_num       = 0;
        $checked_num   = 0;
        $newNodeList   = [];
        foreach ($nodelList as $vo) {
            if ($vo['type'] == 1) {
                $vo = array_merge($vo, ['field' => 'node', 'spread' => true]);
                $vo['checked'] = false;
                $vo['title'] = "{$vo['title']}【{$vo['node']}】";
                $children = [];
                foreach ($nodelList as $v) {
                    if ($v['type'] == 2 && strpos($v['node'], $vo['node'] . '/') !== false) {
                        $v = array_merge($v, ['field' => 'node', 'spread' => true]);
                        if (in_array($v['id'], $checkNodeList)) {
                            $v['checked'] = true;
                            $checked_num++;
                        }else {
                            $v['checked'] = false;
                        }
                        $v['title'] = "{$v['title']}【{$v['node']}】";
                        $children[] = $v;
                        $all_num++;
                    }
                }
                !empty($children) && $vo['children'] = $children;
                $newNodeList[] = $vo;
            }
        }

        return [
            'is_all'    => ( $checked_num >= $all_num ),
            'node_list' => $newNodeList
        ];
    }

    public function getAuthorizeServerList(): array
    {
        $sid_arr     = explode(',', $this->server) ?? [];
        $server_list = ServerList::field('`id`,`name`')->select();

        $all_num       = 0;
        $checked_num   = 0;
        $newServerList = [];
        foreach ($server_list->toArray() as $v) {
            $v = array_merge($v, ['field' => 'node', 'spread' => true]);
            $v['title'] = "{$v['name']}【{$v['id']}】";
            if (in_array($v['id'], $sid_arr)) {
                $v['checked'] = true;
                $checked_num++;
            }else {
                $v['checked'] = false;
            }
            $newServerList[] = $v;
            $all_num++;
        }
        return [
            'is_all'    => ( $checked_num >= $all_num ),
            'server_list' => $newServerList
        ];
    }
}