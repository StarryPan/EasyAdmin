<?php
namespace app\index\controller\activity;

use think\App;
use think\facade\Db;
use jianyan\excel\Excel;
use app\index\model\CfgConst;
use EasyAdmin\tool\CommonTool;
use app\index\model\GameConfig;
use app\index\model\ActivityConfig;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Registersign
 * @package app\index\controller\activity
 * @ControllerAnnotation(title="创角签到")
 */
class Registersign extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    public $sort      = [
        'id'   => 'asc',
    ];
    
    public $title     = '创角签到';

    /**
     * 活动类型
     *
     * @var integer
     */
    public $act_type  = CfgConst::AtypeRegisterSign;

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'register_sign';



    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ActivityConfig();
    }

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

            $model = $this->model->where($where);
            $list  = $model->where('atype', $this->act_type)->page($page, $limit)->order($this->sort)->select();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'data'  => $this->model->getConfigData($list),
                'count' => $model->count(),
            ];

            return json($data);
        }

        // 获取道具配置
        $configItems = GameConfig::getConfigItems();
        $this->assign('configItems', json_encode($configItems));
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="添加")
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $rule = [
                'name|名称'  => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['id']          = null;
            $post['title']       = $this->title;
            $post['atype']       = $this->act_type;// 每月签到
            $post['status']      = 0;
            $post['open_type']   = 4;// 开启类型 4.常在
            $post['open_value']  = null;
            $post['update_time'] = time();
            $post['create_time'] = time();

            try {

                $cfgs = [
                    'name' => $post['name'],
                ];

                foreach ($rew_items as $ik => $ival) {

                    $field_key        = 'day' . ($ik + 1);
                    $cfgs[$field_key] = [$ival['itemid'] => intval($ival['itemcnt'])];
                }

                // 设置配置
                $post['config'] = json_encode($cfgs);

                // 保存
                $save = $this->model->save($post);

            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('添加成功') : $this->error('添加失败');
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
            $rule = [
                'name|名称'  => 'require',
            ];
            $this->validate($post, $rule);

            // 获取奖励
            $rew_items = $this->getPostRewards($post, ['itemid', 'itemcnt']);

            // 设置必要的参数
            $post['id']          = $id;
            $post['name']        = $this->title;
            $post['update_time'] = time();

            try {

                $cfgs = [
                    'name' => $post['name'],
                ];

                foreach ($rew_items as $ik => $ival) {

                    $field_key        = 'day' . ($ik + 1);
                    $cfgs[$field_key] = [$ival['itemid'] => intval($ival['itemcnt'])];
                }

                // 设置配置
                $post['config'] = json_encode($cfgs, JSON_UNESCAPED_UNICODE);

                // 保存
                $save = $row->save($post);
            } catch (\Exception $e) {
                LogError($e);// 写入报错日志
                $this->error($e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }

        $this->assign('row', $row->config ?: '');
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
            $this->model->refreshConfig();
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
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
        empty($row) && $this->error('数据不存在');
        try {
            $row->save([
                $post['field'] => $post['value'],
            ]);
            $this->model->refreshConfig();
        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $this->success('保存成功');
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export($is_page = true)
    {
        list($page, $limit, $where) = $this->buildTableParames();

        $model = $this->model->where('atype', $this->act_type)->where($where);

        if (!isNull($is_page)) {

            $model = $model->page($page, $limit);
        }

        $list = $model->order('id', 'desc')->select();

        if ($list->isEmpty()) {

            return $this->error('导出失败，数据为空！');
        }

        $data = [];

        foreach ($list->toArray() as $val) {
            
            // 解析Config
            $cfgs = json_decode($val['config'], true);

            foreach ($cfgs as $field_n => $field_v) {
                
                $val[strval($field_n)] = is_array($field_v) ? rewardToString($field_v) : $field_v;
            }

            unset($val['config']);

            $data[] = $val;
        }

        $dbList = [
            ['Field' => 'id', 'Comment' => 'ID'],
            ['Field' => 'name', 'Comment' => '名称'],
        ];

        for ($dnum = 1; $dnum <= 7; $dnum++) {

            $dbList[] = ['Field' => 'day'.$dnum, 'Comment' => '第'.$dnum.'天'];
        }

        foreach ($dbList as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }

        $fileName = $this->file_name . '_' . date('Y-m-d H:i:s', time());
        return Excel::exportData($data, $header, $fileName, 'xlsx');
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

            // 获取数据
            $cfgs   = [];
            $data   = $this->model->where('atype', $this->act_type)->select();
            $count  = 0;

            foreach ($data as $obj) {
                
                $cfgs[strval($obj->id)] = $obj;
            }

            foreach ($excel_data as $data) {

                if (empty($data['id'])) {
                    
                    return $this->error("导入数据失败，数据结构错误");
                }
                
                $row = $cfgs[strval($data['id'])] ?? [];

                if (!$row) {
                    
                    $row              = new $this->model;
                    $row->id          = null;
                    $row->atype       = $this->act_type;
                    $row->status      = 0;
                    $row->open_type   = 4;// 开启类型 4.常在
                    $row->open_value  = null;
                    $row->create_time = time();
                }

                foreach ($data as $field_n => $field_v) {
                
                    $data[strval($field_n)] = strstr($field_n, 'day') ? strToIntRewards($field_v) : $field_v;
                }

                $row->title  = $this->title;
                $row->config = json_encode($data);

                // 保存
                $row->save() && $count++;
            }

            // 清除所有的缓存
            !isNull($clear_cache) && \app\index\model\CacheData::clearCache();

            $count ? $this->success('导入数据成功['.$count.']') : $this->error('导入数据失败');
        }
        
        return $this->error('导入数据失败，数据为空');
    }
}
