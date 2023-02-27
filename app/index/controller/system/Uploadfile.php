<?php
namespace app\index\controller\system;


use app\index\model\SystemUploadfile;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="上传文件管理")
 * Class Uploadfile
 * @package app\index\controller\system
 */
class Uploadfile extends AdminController
{

    use \app\index\traits\Curd;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemUploadfile();
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

            if ($save) {
                foreach ($row as $rval) {

                    // 检查上传类型
                    if ( $rval->upload_type != 'local' ) {

                        continue;
                    }

                    // 拆分地址
                    $url_arr = explode( '/upload/', $rval->url );

                    if (count($url_arr) != 2) {
                        
                        continue;
                    }

                    // 删除文件
                    unlink( 'upload/' . $url_arr[1] );
                }
            }

        } catch (\Exception $e) {
            LogError($e);// 写入报错日志
            $this->error($e->getMessage());
        }
        $save ? $this->success('删除成功') : $this->error('删除失败');
    }

}