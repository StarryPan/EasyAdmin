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
 * Class Ltvdata
 * @package app\index\controller\Ltvdata;
 * @ControllerAnnotation(title="用户价值")
 */
class Ltvdata extends AdminController
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
    public $file_name = 'LtvData';



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
     * @NodeAnotation(title="Ltv列表")
     */
    public function index()
    {
        $get = $this->request->get();

        if (!empty($get['server_id']) && !empty($get['start'])) {

            ini_set('memory_limit', '-1'); // 无线运行内存
            ini_set('max_execution_time', 72000); // 永远不停

            $rule = [
                'end|结束时间'        => 'require',
                'start|起始时间'      => 'require',
                'ltv_days|LTV天数'    => 'require',
                'server_id|服务器ID'  => 'require',
                'exchange_rate|汇率'  => 'require',
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
            'ltv_days|LTV天数'    => 'require',
            'server_id|服务器ID'  => 'require',
            'exchange_rate|汇率'  => 'require',
        ];
        $this->validate($get, $rule);
        try {

            $dbList = [
                ['Field' => 'date', 'Comment' => '日期'],
                ['Field' => 'reg_num', 'Comment' => '注册人数'],
                ['Field' => 'login_num', 'Comment' => 'DAU'],
                ['Field' => 'pay_num', 'Comment' => '付费次数'],
                ['Field' => 'people_num', 'Comment' => '付费人数'],
                ['Field' => 'total_money', 'Comment' => '付费金额'],
                ['Field' => 'pay_rate', 'Comment' => '付费率'],
                ['Field' => 'pay_arpu', 'Comment' => 'ARPU'],
                ['Field' => 'pay_arppu', 'Comment' => 'ARPPU'],
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
        $api_url  = getApiUrl($param['server_id'], 'getDataLtv');
        $url_data = getHttp($api_url, getTmSecKey($param));

        if (empty($url_data['data'])) {

            throw new \Exception('获取数据失败，返回为空', 1);
        }

        $list          = [];
        $data          = $url_data['data'];
        $exchange_rate = intval($param['exchange_rate']);

        // 合计所需变量
        $total_data   = ['date' => '合计'];
        $sumTotalData = function ($fname, $num) use (&$total_data) {
            $num = intval($num);
            $total_data[$fname]  = $total_data[$fname] ?? 0;
            $total_data[$fname] += $num;
            return $num;
        };

        $reg_data_arr = $data['reg_data'];

        // 计算 ltv值
        foreach ($data['ltv_data'] as $data_val) {

            $reg_date        =  $data_val['date'];
            $pay_ltv1_money  =  $data_val['pay_ltv1_money'];
            $pay_ltv3_money  =  $data_val['pay_ltv3_money'];
            $pay_ltv7_money  =  $data_val['pay_ltv7_money'];
            $pay_ltv14_money =  $data_val['pay_ltv14_money'];
            $pay_ltv30_money =  $data_val['pay_ltv30_money'];
            $pay_ltv_money   =  $data_val['pay_ltv_money'];
            $reg_num         =  isset($reg_data_arr[$reg_date]) ? $reg_data_arr[$reg_date]['reg_num'] : 0;

            // 累计
            $list[] = [
                'date'        => $reg_date,
                'reg_num'     => $sumTotalData('reg_num', $reg_num),
                'ltv_val'     => $sumTotalData('ltv_val', round(($reg_num > 0 ? ($pay_ltv_money * $exchange_rate / $reg_num) : 0), 2)),
                'ltv_val_1'   => $sumTotalData('ltv_val_1', round(($reg_num > 0 ? ($pay_ltv1_money * $exchange_rate / $reg_num) : 0), 2)),
                'ltv_val_3'   => $sumTotalData('ltv_val_3', round(($reg_num > 0 ? ($pay_ltv3_money * $exchange_rate / $reg_num) : 0), 2)),
                'ltv_val_7'   => $sumTotalData('ltv_val_7', round(($reg_num > 0 ? ($pay_ltv7_money * $exchange_rate / $reg_num) : 0), 2)),
                'ltv_val_14'  => $sumTotalData('ltv_val_14', round(($reg_num > 0 ? ($pay_ltv14_money * $exchange_rate / $reg_num) : 0), 2)),
                'ltv_val_30'  => $sumTotalData('ltv_val_30', round(($reg_num > 0 ? ($pay_ltv30_money * $exchange_rate / $reg_num) : 0), 2)),
                'total_money' => $sumTotalData('total_money', round($pay_ltv_money * $exchange_rate, 2)),
            ];
        }

        // 合计
        $roundSum = function ($fields, $precision = 2) use ($total_data) {
            foreach ($fields as $fname) {
                $total_data[$fname] = round($total_data[$fname], $precision);
            }

            return $total_data;
        };

        // 计算合计
        $list[] = $roundSum([
            'ltv_val', 'ltv_val_1', 'ltv_val_3', 'ltv_val_7', 'ltv_val_14', 'ltv_val_30', 'total_money'
        ]);

        return $list;
    }
}
