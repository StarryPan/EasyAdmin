<?php
namespace app\index\controller\config;

use think\App;
use app\index\model\GameConfig;
use app\index\service\TriggerService;
use app\common\constants\AdminConstant;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;

/**
 * Class Uploadcfg
 * @package app\index\controller\config
 * @ControllerAnnotation(title="上传配置")
 */
class Uploadcfg extends AdminController
{
    use \app\index\traits\Curd;

    /**
     * 排序
     *
     * @var array
     */
    protected $sort = [
        'id'          => 'desc',
        'update_time' => 'desc',
    ];

    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new GameConfig();
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

            $list = $this->model->where($where)->page($page, $limit)->order($this->sort)->select();
            $data = [
                'code'  => 0,
                'msg'   => '',
                'count' => $this->model->where($where)->count(),
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
            $rule = [
                'cfg_key|配置的KEY不能为空'        => 'require',
                'cfg_name|配置的名称不能为空'      => 'require',
                'file_url|上传的文件路径不能为空' => 'require',
            ];
            $this->validate($post, $rule);

            $save = false;
            try {

                // 获取上传的文件配置
                $file_url = $post['file_url'];
                $jsonFile = file_get_contents($file_url);
                $jsonData = json_decode($jsonFile, true);

                if (!$jsonData) {

                    throw new \Exception('获取Json数据表失败：' . $file_url, 20016);
                }

                $post['cfg_data']    = cleanStrFormat($jsonFile);
                $post['update_time'] = time();
                $post['create_time'] = $post['update_time'];

                $save = $this->model->save($post);

                if ( $save ) {

                    // 刷新缓存
                    $this->model->refreshCache();
                }

            } catch (\Exception $e) {

                $this->error($e->getMessage());
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
            $rule = [
                'cfg_key|配置的KEY不能为空'       => 'require',
                'cfg_name|配置的名称不能为空'     => 'require',
            ];
            $this->validate($post, $rule);

            $save = false;
            try {

                if ( !isNull( $post['file_url'] ) ) {

                    // 获取上传的文件配置
                    $file_url = $post['file_url'];
                    $jsonFile = file_get_contents($file_url);
                    $jsonData = json_decode($jsonFile, true);

                    if (!$jsonData) {

                        throw new \Exception('获取Json数据表失败：' . $file_url, 20016);
                    }

                    $post['cfg_data']    = cleanStrFormat($jsonFile);
                    $post['diff_data']   = $this->model->diffConfigData($row->cfg_data, $jsonFile);
                }
                
                $post['update_time'] = time();
                $save = $row->save($post);

                if ( $save ) {

                    // 刷新缓存
                    $this->model->refreshCache();
                }

            } catch (\Exception $e) {

                $this->error($e->getMessage());
            }

            $save ? $this->success('更新配置成功') : $this->error('更新配置失败');
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

            if ( $save ) {

                // 刷新缓存
                $this->model->refreshCache();
            }

        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

}
