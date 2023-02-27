<?php

namespace app\index\traits;

use think\facade\Db;
use jianyan\excel\Excel;
use EasyAdmin\tool\CommonTool;
use app\index\model\GameConfig;
use think\captcha\facade\Captcha;
use EasyAdmin\annotation\NodeAnotation;

/**
 * 后台CURD复用
 * Trait Curd
 * @package app\index\traits
 */
trait Curd
{

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();
            $count = $this->model
                ->where($where)
                ->count();
            $list = $this->model
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $this->model->save($post);
            } catch (\Exception $e) {
                $this->error('保存失败:'.$e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="编辑")
     */
    public function edit($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [];
            $this->validate($post, $rule);
            try {
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="删除")
     */
    public function delete($id)
    {
        $row = $this->model->whereIn('id', $id)->select();
        $row->isEmpty() && $this->error('数据不存在');
        try {
            $save = $row->delete();
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export($is_page = true)
    {
        list($page, $limit, $where) = $this->buildTableParames();

        $model = $this->model->where($where);

        if (!isNull($is_page)) {

            $model = $model->page($page, $limit);
        }

        $list = $model->order('id', 'asc')->select();

        if ($list->isEmpty()) {

            return $this->error('导出失败，数据为空！');
        }

        $tableName = $this->model->getName();
        $tableName = CommonTool::humpToLine(lcfirst($tableName));
        $prefix = config('database.connections.mysql.prefix');
        $dbList = Db::query("show full columns from {$prefix}{$tableName}");
        $header = [];
        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }

        $fileName = $tableName . '_' . date('Y-m-d H:i:s', time());
        return Excel::exportData($list->toArray(), $header, $fileName, 'xlsx');
    }

    /**
     * @NodeAnotation(title="导入")
     */
    public function import($clear_cache = false)
    {
        $file = $this->request->file('file');

        if (!$file) {

            return $this->error('导入数据失败，参数为空');
        }

        // 获取Excel导入数据
        $excel_data = Excel::importData($file);

        if ($excel_data != null) {

            $tableName = $this->model->getName();
            
            if (!CommonTool::verifyTableData($tableName, $excel_data)) {

                return $this->error("导入数据失败，数据结构错误[$tableName]");
            }
            
            $model_path = $this->app->parseClass('model', $tableName);
            $pk_id      = $this->model->getPk();
            $count      = 0;

            foreach ($excel_data as $data) {
                
                $row = $this->model->where($pk_id, $data[strval($pk_id)])->find();

                if (!$row) {
                    
                    $row         = new $model_path();
                    $row->$pk_id = $data[strval($pk_id)];
                }

                // 保存
                $row->save($data) && $count++;
            }

            // 清除所有的缓存
            !isNull($clear_cache) && \app\index\model\CacheData::clearCache();

            $count ? $this->success('导入数据成功['.$count.']') : $this->error('导入数据失败');
        }
        
        return $this->error('导入数据失败，数据为空');
    }

    /**
     * @NodeAnotation(title="属性修改")
     */
    public function modify()
    {
        $post = $this->request->post();
        $rule = [
            'id|ID'    => 'require',
            'field|字段' => 'require',
            'value|值'  => 'require',
        ];
        $this->validate($post, $rule);
        $row = $this->model->find($post['id']);
        if (!$row) {
            $this->error('数据不存在');
        }
        if (!in_array($post['field'], $this->allowModifyFields)) {
            $this->error('该字段不允许修改：' . $post['field']);
        }
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }

    /**
     * @NodeAnotation(title="获取配置")
     */
    public function getConfig()
    {
        if ($this->request->isAjax()) {

            $post = $this->request->post();
            
            if (isNull($post['ctype'])) {
                
                $this->error('获取配置失败，参数为空');
            }

            // 获取通用配置
            $cfgs = GameConfig::getCommonConfig($post['ctype']);

            return $this->success('成功', $cfgs);
        }
        
        return $this->fetch();
    }

}
