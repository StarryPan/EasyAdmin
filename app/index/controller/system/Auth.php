<?php
namespace app\index\controller\system;


use app\index\model\SystemAuth;
use app\index\model\SystemAuthNode;
use app\index\service\TriggerService;
use app\common\controller\AdminController;
use app\index\model\ServerList;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;

/**
 * @ControllerAnnotation(title="角色权限管理")
 * Class Auth
 * @package app\index\controller\system
 */
class Auth extends AdminController
{

    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SystemAuth();
    }

    /**
     * @NodeAnotation(title="授权")
     */
    public function authorize($id)
    {
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        if ($this->request->isAjax()) {
            $data1 = $this->model->getAuthorizeNodeListByAdminId($id);
            $data2 = $row->getAuthorizeServerList();
            $data  = array_merge($data1, $data2);
            if ($id == 1) {
                $data['is_all'] = true;
            }else {
                if (!$data1['is_all']) {
                    $data['is_all'] = $data1['is_all'];
                }
            }
            $this->success('获取成功', $data);
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="授权保存")
     */
    public function saveAuthorize()
    {
        $id = $this->request->post('id');
        $node = $this->request->post('node', "[]");
        $node = json_decode($node, true);
        $server = $this->request->post('server', "[]");
        $server = json_decode($server, true);
        $row = $this->model->find($id);
        empty($row) && $this->error('数据不存在');
        try {
            $row->server = implode(',', $server);
            $row->save();

            $authNode = new SystemAuthNode();
            $authNode->where('auth_id', $id)->delete();
            if (!empty($node)) {
                $saveAll = [];
                foreach ($node as $vo) {
                    $saveAll[] = [
                        'auth_id' => $id,
                        'node_id' => $vo,
                    ];
                }
                $authNode->saveAll($saveAll);
            }
            TriggerService::updateMenu();
        } catch (\Exception $e) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

}