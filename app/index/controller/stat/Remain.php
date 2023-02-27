<?php

namespace app\index\controller\stat;

use think\App;
use jianyan\excel\Excel;
use app\index\model\GameConfig;
use app\index\model\ServerList;
use EasyAdmin\annotation\NodeAnotation;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;


/**
 * Class Remain
 * @package app\index\controller\Remain;
 * @ControllerAnnotation(title="数据概览")
 */
class Remain extends AdminController
{
    use \app\index\traits\Curd;

    protected $sort = [
        'sort' => 'desc',
        'id'   => 'desc',
    ];

    /**
     * 文件名
     *
     * @var string
     */
    public $file_name = 'Remain';



    /**
     * 构造器
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new ServerList();
    }

    /**
     * @NodeAnotation(title="概览列表")
     */
    public function index()
    {
        $get = $this->request->get();

        if (!empty($get['server_id']) && !empty($get['start'])) {

            ini_set('memory_limit', '-1'); // 无线运行内存
            ini_set('max_execution_time', 72000); // 永远不停

            $rule = [
                'end|结束时间'       => 'require',
                'start|起始时间'     => 'require',
                'server_id|服务器ID' => 'require',
            ];
            $this->validate($get, $rule);
            try {
                // 获取数据
                $data = $this->handleData($get);
                return json([
                    'code'  => 0,
                    'msg'   => '',
                    'data'  => $data,
                    'count' => 0,
                ]);
            } catch (\Exception $e) {
                LogError($e); // 写入报错日志
                $this->error($e->getMessage());
            }
        }

        // 获取配置
        $this->assign('cfgs', GameConfig::getCommonConfig('servers'));

        // 查询参数
        $this->assign('query', $this->request->post());

        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="导出")
     */
    public function export()
    {
        $get  = $this->request->get();
        $rule = [
            'end|结束时间'        => 'require',
            'start|起始时间'      => 'require',
            'server_id|服务器ID'  => 'require',
        ];
        $this->validate($get, $rule);
        try {

            $dbList = [
                ['Field' => 'reg_date', 'Comment' => '日期'],
                ['Field' => 'reg_num', 'Comment' => '注册人数'],
                ['Field' => 'day_1', 'Comment' => '次留'],
                ['Field' => 'day_2', 'Comment' => '三留'],
                ['Field' => 'day_3', 'Comment' => '四留'],
                ['Field' => 'day_4', 'Comment' => '五留'],
                ['Field' => 'day_5', 'Comment' => '六留'],
                ['Field' => 'day_6', 'Comment' => '七留'],
                ['Field' => 'day_14', 'Comment' => '十五留'],
                ['Field' => 'day_30', 'Comment' => '三十留'],
            ];

            foreach ($dbList as $vo) {
                $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
                if (!in_array($vo['Field'], $this->noExportFields)) {
                    $header[] = [$comment, $vo['Field']];
                }
            }

            $data     = $this->handleData($get);
            $fileName = $this->file_name . '_' . date('Y-m-d H:i:s', time());
            return Excel::exportData($data, $header, $fileName, 'xlsx');
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }

    /**
     * @NodeAnotation(title="数据处理")
     *
     * @param array $data
     * @return array
     */
    public function handleData(array $param): array
    {
        // 请求获取日志表注释
        $api_url  = getApiUrl($param['server_id'], 'getDataRemain');
        $url_data = getHttp($api_url, getTmSecKey($param));

        $data = $url_data['data'];

        foreach ($data as $key => $data_v) {

            $data[$key]['day_1']  = round($data_v['day_1'] * 100, 2) . '%';
            $data[$key]['day_2']  = round($data_v['day_2'] * 100, 2) . '%';
            $data[$key]['day_3']  = round($data_v['day_3'] * 100, 2) . '%';
            $data[$key]['day_4']  = round($data_v['day_4'] * 100, 2) . '%';
            $data[$key]['day_5']  = round($data_v['day_5'] * 100, 2) . '%';
            $data[$key]['day_6']  = round($data_v['day_6'] * 100, 2) . '%';
            $data[$key]['day_7']  = round($data_v['day_7'] * 100, 2) . '%';
            $data[$key]['day_14'] = round($data_v['day_14'] * 100, 2) . '%';
            $data[$key]['day_30'] = round($data_v['day_30'] * 100, 2) . '%';
        }

        return array_values($data);
    }
}
