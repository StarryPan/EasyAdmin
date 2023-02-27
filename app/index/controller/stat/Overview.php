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
 * Class Overview
 * @package app\index\controller\Overview;
 * @ControllerAnnotation(title="数据概览")
 */
class Overview extends AdminController
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
    public $file_name = 'Overview';



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
                'end|结束时间'        => 'require',
                'start|起始时间'      => 'require',
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
        $api_url  = getApiUrl($param['server_id'], 'getDataOverview');
        $url_data = getHttp($api_url, getTmSecKey($param));

        if (empty($url_data['data'])) {

            throw new \Exception('获取数据失败，返回为空', 1);
        }

        $list = [];
        $data = $url_data['data'];
        $repeat = intval($param['repeat']);
        $exchange_rate = intval($param['exchange_rate']);

        // 注册数量
        foreach ($data['reg_num'] as $reg_val) {

            $v_date = $reg_val['reg_date'];

            if (empty($list[$v_date]['reg_num'])) {

                $list[$v_date]['reg_num'] = 0;
            }

            // 设置数据
            $list[$v_date]['date']     = $v_date;
            $list[$v_date]['reg_num'] += $reg_val['reg_num'];
        }

        // 注册有效数量
        foreach ($data['valid_num'] as $reg_val) {

            $v_date = $reg_val['reg_date'];

            if (empty($list[$v_date]['valid_num'])) {

                $list[$v_date]['valid_num'] = 0;
            }

            // 设置数据
            $list[$v_date]['date']       = $v_date;
            $list[$v_date]['valid_num'] += $reg_val['valid_num'];
        }

        // 登陆数量
        foreach ($data['login_num'] as $reg_val) {

            $v_date = $reg_val['day'];

            if (empty($list[$v_date]['login_num'])) {

                $list[$v_date]['login_num'] = 0;
            }

            // 设置数据
            $list[$v_date]['date']       = $v_date;
            $list[$v_date]['login_num'] += $reg_val['login_num'];
        }

        // 总的充值人数 | 充值的总金额
        foreach ($data['total_money'] as $reg_val) {

            $v_date = $reg_val['pay_date'];

            if (empty($list[$v_date]['total_money'])) {

                $list[$v_date]['total_money'] = 0;
            } else {

                $list[$v_date]['total_money'] *= $exchange_rate;
                $list[$v_date]['total_money'] = round($list[$v_date]['total_money'], 2);
            }

            if (empty($list[$v_date]['people_num'])) {

                $list[$v_date]['people_num'] = 0;
            }

            if (empty($list[$v_date]['pay_num'])) {

                $list[$v_date]['pay_num'] = 0;
            }

            // 设置数据
            $list[$v_date]['date']         = $v_date;
            $list[$v_date]['pay_num']     += $reg_val['pay_num'];
            $list[$v_date]['people_num']  += $reg_val['people_num'];
            $list[$v_date]['total_money'] += round($reg_val['total_money'] * $exchange_rate, 2);
            $list[$v_date]['total_money'] = ceil($list[$v_date]['total_money']);
        }

        // 日付费率
        foreach ($list as $v_date => $data_v) {

            // 设置参数
            $data_v['date']        = $v_date;
            $data_v['reg_num']     = empty($data_v['reg_num']) ? 0 : $data_v['reg_num'];
            $data_v['pay_num']     = empty($data_v['pay_num']) ? 0 : $data_v['pay_num'];
            $data_v['reg_date']    = empty($data_v['reg_date']) ? 0 : $data_v['reg_date'];
            $data_v['login_num']   = empty($data_v['login_num']) ? 0 : $data_v['login_num'];
            $data_v['valid_num']   = empty($data_v['valid_num']) ? 0 : $data_v['valid_num'];
            $data_v['people_num']  = empty($data_v['people_num']) ? 0 : $data_v['people_num'];
            $data_v['total_money'] = empty($data_v['total_money']) ? 0 : $data_v['total_money'];

            // 检查是否为 IP去重
            if ($repeat > 0) {

                // 重新计算 DAU
                // $data_v['login_num'] = $data_v['login_num'] - ( $data_v['reg_num'] - $data_v['valid_num'] );
                $data_v['reg_num']   = $data_v['valid_num'];
            }

            unset($data_v['valid_num']);

            // 设置日付费率
            $data_v['pay_rate']    = $data_v['login_num'] ? round($data_v['people_num'] / $data_v['login_num'] * 100, 2) . '%' : 0 . '%';

            // ARPU
            $data_v['pay_arpu']   = $data_v['login_num'] ? round($data_v['total_money'] / $data_v['login_num'], 2) : 0;

            // ARPPU
            $data_v['pay_arppu']  = $data_v['people_num'] ? round($data_v['total_money'] / $data_v['people_num'], 2) : 0;

            $list[$v_date]        = $data_v;
        }

        return array_values($list);
    }


}
