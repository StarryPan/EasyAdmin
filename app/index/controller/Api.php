<?php

namespace app\index\controller;

use jianyan\excel\Excel;
use app\index\model\PlayerPay;
use app\index\model\ServerGroup;
use app\index\model\SystemConfig;
use app\index\model\TransManager;
use app\index\model\ContentNotice;
use app\index\model\UserRedeemCode;
use app\index\model\MysqlStructSync;
use app\index\model\ServerChannelConfig;
use app\common\controller\AdminController;

/**
 * 后台的开放接口
 */
class Api extends AdminController
{
    /**
     * 获取游戏渠道配置
     *
     * @return void
     */
    public function getChannelConfig()
    {
        // 渠道KEY
        $channel_key = $_REQUEST['channel_key'] ?? '';

        // POST请求
        if (!empty($channel_key)) {

            // 获取环境
            $pre_state = 0;

            // 获取用户IP
            $user_ip   = $this->request->ip();

            // 判断IP白名单
            if (in_array($user_ip, SystemConfig::getWhiteIps())) {

                // 设置环境为预发布
                $pre_state = 1;
            }

            // 获取游戏配置
            $channel_cfgs = ServerChannelConfig::getCacheChannelConfig($channel_key, $pre_state);

            // 获取维护白名单
            $maintain_ip = empty($channel_cfgs['maintain_ip']) ? [] : explode(',', $channel_cfgs['maintain_ip']);

            // 检查维护通知白名单
            if (in_array($user_ip, $maintain_ip)) {

                // 设置维护弹框状态
                $channel_cfgs['is_maintain'] = 0;
            }

            // 屏蔽字段
            unset($channel_cfgs['maintain_ip']);

            // 遍历,检查指定字段是否为空
            foreach ($channel_cfgs as $ckey => $cval) {

                if (in_array($ckey, ServerChannelConfig::$removeValueEmpty) && empty($cval)) {

                    // 去除该参数
                    unset($channel_cfgs[$ckey]);
                }
            }

            return $this->resultClient($channel_cfgs);
        }

        return $this->resultClient(null, 1, '请求错误');
    }

    /**
     * 获取服务器组
     *
     * @return void
     */
    public function getServerGroup()
    {
        $group_key = $_REQUEST['group_key'] ?? '';

        if ($group_key == null) {

            return $this->resultClient(null, 1, '参数为空');
        }

        // 获取服务器组信息
        $server_group = ServerGroup::where('group_key', $group_key)->find();

        if (!$server_group) {

            return $this->resultClient(null, 2010, 'group_key不存在！');
        }

        // 获取服务器组中的服务器ID
        $server_list = $server_group->getGroupServerInfo();

        if (count($server_list) == 0) {

            return $this->resultClient(null, 2010, '配置错误,组中没有服务器');
        }

        return $this->resultClient($server_list);
    }

    /**
     * 获取公告信息
     *
     * @return void
     */
    public function getNoticeInfo()
    {
        // 环境类型
        $pre_state   = $_REQUEST['pre_state'] ?? 0;

        // 渠道KEY
        $channel_key = $_REQUEST['channel_key'] ?? '';

        if (empty($channel_key)) {

            return $this->resultClient(null, 1, '参数为空');
        }

        $list        = [];
        $now_date    = date('Y-m-d H:i:s', time());
        $notice_data = ContentNotice::getCacheNoticeData($channel_key, $pre_state);

        foreach ($notice_data as $arr) {

            if ($now_date >= $arr['start_date'] && $now_date <= $arr['end_date']) {

                $list[] = [
                    'tag'     => $arr['tag'],
                    'title'   => $arr['title'],
                    'content' => $arr['content']
                ];
            }
        }

        return $this->resultClient($list);
    }

    /**
     * 获取数据库表结构
     *
     * @return void
     */
    public function getDBStruct()
    {
        $config    = config('database.connections');
        $_database = $config['mysql'];
        $arr_dbs   = ['bgdb' => $_database];
        $exp_arr   = [];

        foreach ($arr_dbs as $db_key => $database) {

            $arr_host = explode(',', $database['hostname']);
            $arr_username = explode(',', $database['username']);
            $arr_passwd = explode(',', $database['password']);
            $arr_port = explode(',', $database['hostport']);
            //port
            $host = $arr_host[0];
            $username = $arr_username[0];
            $passwd = $arr_passwd[0];
            $dbname =  $database['database'];
            $port = empty($arr_port[0]) ? "3306" : $arr_port[0];

            $local_database_config = ['host' => $host, 'username' => $username, 'passwd' => $passwd, 'dbname' => $dbname, 'port' => $port];

            $mstruct = new MysqlStructSync($local_database_config);
            $mstruct->removeAutoIncrement();
            $str = $mstruct->getStruct();

            $exp_arr[$db_key] = $str;
        }

        $exprt_str = json_encode($exp_arr);

        echo $exprt_str;
    }

    /**
     * 导入新的数据库表结构
     *
     * @return void
     */
    public function refreshTableStructure()
    {
        ini_set('memory_limit', '-1'); // 无线运行内存
        ini_set('max_execution_time', 0); // 永远不停止

        $new_struct_str = file_get_contents('../dbsql/last_bgdb_struct.txt');
        $new_str_arr = json_decode($new_struct_str, true);

        if (!$new_str_arr) {

            return $this->error('找不到数据库结构', 1);
        }

        $split_tables = ['user_quest'];
        // $split_tables = ['user_quest', 'shop_items'];

        //分表,分为100份
        for ($i = 0; $i < 100; $i++) {

            foreach ($split_tables as $sptable) {

                if (!in_array($sptable . $i, $new_str_arr['ksdb']['tables'])) {
                    $new_str_arr['ksdb']['tables'][] = $sptable . $i; //$new_str_arr['ksdb']['tables']['user_quest'];
                }
                $new_str_arr['ksdb']['columns'][$sptable . $i] = $new_str_arr['ksdb']['columns'][$sptable];

                $create = str_replace($sptable, $sptable . $i, $new_str_arr['ksdb']['show_create'][$sptable]);
                $create = str_replace('fk_', 'fk_' . $sptable . $i, $create);
                $new_str_arr['ksdb']['show_create'][$sptable . $i] = $create; //str_replace('user_quest','user_quest'.$i, $new_str_arr['ksdb']['show_create']['user_quest'] ) ;
                $constr = $new_str_arr['ksdb']['constraints'][$sptable];
                $constr = str_replace('fk_', 'fk_user_quest' . $i, $constr);
                $new_str_arr['ksdb']['constraints'][$sptable . $i] = $constr; //$new_str_arr['ksdb']['constraints']['user_quest']  ;
            }
        }

        //echo json_encode(  $new_str_arr['ksdb']);exit; 

        $config    = config();
        $_database = $config['database'];

        $arr_dbs = ['bgdb' => $_database];
        $execute = [];

        foreach ($arr_dbs as $db_key => $database) {
            $arr_host = explode(',', $database['hostname']);
            $arr_username = explode(',', $database['username']);
            $arr_passwd = explode(',', $database['password']);
            $arr_port = explode(',', $database['hostport']);
            //port
            $host = $arr_host[0];
            $username = $arr_username[0];
            $passwd = $arr_passwd[0];
            $dbname =  $database['database'];
            $port = empty($arr_port[0]) ? '3306' : $arr_port[0];

            $local_database_config = ['host' => $host, 'username' => $username, 'passwd' => $passwd, 'dbname' => $dbname, 'port' => $port];
            $mstruct = new MysqlStructSync($local_database_config);
            $mstruct->removeAutoIncrement();


            $mstruct->baseDiffWithOrg($new_str_arr[$db_key], $local_database_config);
            if (isset($_REQUEST['export'])) {
                $execute[$db_key] =  $mstruct->exportExecuteSql();
            } else {
                $execute[$db_key] = $mstruct->execute();
            }
        }

        return $this->success($execute, '刷新成功');
    }

    /**
     * 爱奇艺支付结束
     *
     * @return void
     */
    public function onIQIYIPurchaseEnd()
    {

        $file_name = "../runtime/onIQIYIPurchaseEnd.log";
        $str = json_encode($_REQUEST);
        file_put_contents($file_name, $str, FILE_APPEND | LOCK_EX);
        /*
		{
			"user_id":"1685159337",
			"role_id":"14700101",
			"order_id":"2111151940016256201",
			"cp_order_id":"163697640714700101",
			"order_type":"ordinary",
			"version":"2.0",
			"money":"100",
			"ts":"1636976419",
			"game_id":"11238",
			"extra_param":"163697640714700101",
			"pay_time":"2021-11-15 19:40:19",
			"sign":"0fc01215220d2efb0e5d7a367d32a81e"
		}
		*/
		/*
        $data = json_decode('{
				"user_id":"1685159337",
				"role_id":"500103",
				"order_id":"2111161529016688901",
				"cp_order_id":"1063",
				"order_type":"ordinary",
				"version":"2.0",
				"money":"600",
				"ts":"1637047787",
				"game_id":"11238",
				"extra_param":"SSSDD1",
				"pay_time":"2021-11-16 15:29:47",
				"sign":"c6b9138a5603e0b5e16781b21b4e3ab2"
			}', true);

		*/
        /**
         * 生成加密字符串
         * @param array $params 合并请求的GET数组和POST数组
         * @param string $signKey 约定的加密key
         * @return string 加密结果字符串
         */
        function makeSign($params, $signKey)
        {
            $preString = makePreString($params, $signKey);
            return md5($preString);
        }

        /**
         * 获取加密前待加密的字符串
         * @param array $params 合并请求的GET数组和POST数组
         * @param string $signKey 约定的加密key
         * @return string 待加密字符串
         */
        function makePreString($params, $signKey)
        {
            $params['sign_key'] = $signKey;
            $params = filterParams($params);
            // 按参数键名排序参数
            ksort($params);
            // 参数连接方式：age=12&name=张三&sex=male
            $preString = urldecode(http_build_query($params));
            return $preString;
        }

        /**
         * 过滤参数值
         * @param array $params
         * @return array
         */
        function filterParams($params = [])
        {
            $filtered = array_filter(
                $params,
                function ($value, $key) {
                    // 排除空值
                    if (in_array($value, ['', null, false], true)) {
                        return false;
                    }
                    // 排除不参与加密的参数
                    if (in_array($key, ['sign'])) {
                        return false;
                    }
                    return true;
                },
                ARRAY_FILTER_USE_BOTH
            );

            return $filtered;
        }

        $mysign = makeSign($data, 'f4406673ecff430c807e6e0d0175c144');

        if ($mysign == $data['sign']) {

            // 先判断order_id 是否存在
            if (PlayerPay::where('order_id', $data['order_id'])->count() == 0) {

                // 启动事务
                TransManager::startTrans();
                try {

                    // 查询最后一次支付的信息
                    $pay = PlayerPay::where('uid', $data['role_id'])->where('product_id', $data['extra_param'])->whereNull('order_id')->order('id', 'desc')->find();

                    if ($pay) {

                        // 修改订单号
                        $pay->order_id = $data['order_id'];

                        // 执行支付
                        $pay->executePayInfo($data['money']);

                        if ($pay->save() === false) {

                            throw new \Exception('保存支付状态失败', 1);
                        }
                    }

                    TransManager::commit();
                } catch (\Exception $e) {

                    TransManager::rollback();
                    LogError($e); // 写入报错日志
                    return $this->resultClient(null, 1, $e->getMessage());
                }
            }

            echo '{"code":200,"msg": "请求成功"}';
        } else {

            echo "faild";
        }
    }
	
	
	//Quick SDK 回调
	public function onQuickNotify(){
		
		
		//uid=543&username=554230339%40qq.com&cpOrderNo=orderNo_xxx&orderNo=0020170210162721805701&payTime=2017-02-10+16%3A27%3A55&payAmount=6.00&payStatus=0&payCurrency=RMB&usdAmount=0.99&extrasParams=&sign=22abf0b204d19316d177baeec6a90fcd
		
		 
	}

	public function getQuickMd5Sign($callbackkey){
	 
	   $params = $_POST;
	 
	   unset($params['sign']);
	 
	   ksort($params);$signKey = '';
	 
	   foreach($params as $key => $val){
	 
	   $signKey .= $key.'='.$val.'&';
	 
	   }
	 
	   $signKey .= $callbackkey;
	 
	   return md5($signKey);
	 
	}
    /**
     * 初始化支付
     *
     * @return void
     */
    public function initPay()
    {
        // POST请求
        if (!$this->request->isPost()) {

            return $this->resultClient(null, 20, '请求错误');
        }

        $uid        = $this->request->post('uid'); // 用户ID
        $money      = $this->request->post('money'); // 商品ID
        $cfg_id     = $this->request->post('cfg_id'); // 商品ID
        $server     = $this->request->post('server'); // 服务器信息
        $product_id = $this->request->post('product_id'); // 产品信息

        if (empty($uid) || empty($server) || empty($cfg_id) || empty($product_id)) {

            return $this->resultClient(null, 9009, '参数为空');
        }

        // 返回
        $rs = [];

        // 启动事务
        TransManager::startTrans();
        try {

            // 创建支付
            $pay              = new PlayerPay();
            $pay->id          = null;
            $pay->uid         = $uid;
            $pay->cfg_id      = $cfg_id;
            $pay->server      = $server;
            $pay->product_id  = $product_id;
            $pay->create_time = time();

            // 获取订单号自增值
            $order_inc = SystemConfig::incValueWithKey('InitPayOrderId');
			
			
            // 随机订单号
            $pay->order_id = 'initPay_' . $pay->uid . '_' . $pay->cfg_id . '_' . $pay->product_id . '_' . time() . '_test' . $order_inc;

            // 执行支付
            $pay->executePayInfo($money);
			

            if ($pay->save() === false) {

                throw new \Exception('创建支付执行数据失败', 1);
            }

            $rs = ['order_id' => $pay->id];

            TransManager::commit();

            return json($rs);
        } catch (\Exception $e) {

            TransManager::rollback();
            LogError($e); // 写入报错日志
            return $this->resultClient(null, 1, $e->getMessage());
        }
    }
	
	

    /**
     * 兑换码奖励
     *
     * @return void
     */
    public function redeemCodeRewards()
    {
        // POST请求
        if (!$this->request->isPost()) {

            return $this->resultClient(null, 1, '请求错误');
        }

        $post = $this->request->post();
        $rule = [
            'ip|IP地址'           => 'require|ip',
            'te|兑换时间'         => 'require|number',
            'uid|用户ID'          => 'require|number',
            'code|兑换码'         => 'require|length:4,50',
            'server_id|服务器ID'  => 'require|number',
            'channel_key|渠道KEY' => 'require'
        ];
        $this->validate($post, $rule);

        // 一秒兑换一次
        if (isLock('redeemCodeRewards_' . $post['uid'], 1)) {

            return $this->resultClient(null, 10473, '您的操作太频繁了，请稍后再试！');
        }

        // 判断激活失败次数
        $run_tkey = 'redeemCodeRewardsError_' . $post['uid'];

        if (runTimes($run_tkey, 6)) {

            return $this->resultClient(null, 40410, '你在短时间内尝试过多错误的激活码，请不要尝试刷激活码，一经发现，我们将会处理，谢谢合作。');
        }

        // 检查服务器ID
        if (($post['uid'] % 100000) != $post['server_id']) {

            return $this->resultClient(null, 9008, '参数错误');
        }

        // 检查请求时间
        if ((time() - $post['te']) > 300 || (time() - $post['te']) < -300) {

            return $this->resultClient(null, 9008, '参数错误');
        }

        // 获取兑换码信息 
        $redeem_info = UserRedeemCode::where('code', $post['code'])->find();

        // 判断兑换码是否存在
        if ($redeem_info == null) {

            return $this->resultClient(null, 10469, '此礼包卡号不存在，兑换失败.');
        }

        // 检查是否判断渠道
        if ($redeem_info->channel_key != null && $redeem_info->channel_key != $post['channel_key']) {

            return $this->resultClient(null, 10469, '此礼包卡号不存在，兑换失败！');
        }

        // 判断兑换码是否已被使用
        if ($redeem_info->exp_type == 1 && $redeem_info->use_num > 0) {

            return $this->resultClient(null, 10468, '此礼包卡号已被使用，无法再次使用。');
        }

        // 检查是否生效
        if ($redeem_info->start_time > 0) {

            // 还未开始
            if ($redeem_info->start_time > time()) {

                return $this->resultClient(null, 40255, '礼品码无效，请确认后再试。');
            }

            // 已经结束
            if ($redeem_info->end_time < time()) {

                return $this->resultClient(null, 40256, '礼品码不在有效期内，请确认后再试。');
            }
        }

        // 检查同批号是否重复兑换
        if ($redeem_info->checkBatchRepeat($post['uid'], $post['server_id'])) {

            return $this->resultClient(null, 40192, '兑换失败，相同批次的卡号不能重复兑换。');
        }

        // 启动事务
        TransManager::startTrans();
        try {

            // 获取兑换码信息 
            $redeem_info = UserRedeemCode::find($redeem_info->id);
            $redeem_info->use_num++;

            if ($redeem_info->save() === false) {

                throw new \Exception('兑换奖励失败，保存错误', 1);
            }

            // 解析奖励
            $rewards = json_decode($redeem_info->rewards, true);

            // 创建日志
            $redeem_info->createLog($post);

            // 重置兑换次数
            runTimes($run_tkey, 0);

            TransManager::commit();

            return $this->resultClient([
                'rewards' => $rewards,
                'use_num' => $redeem_info->use_num
            ]);
        } catch (\Exception $e) {

            TransManager::rollback();
            LogError($e); // 写入报错日志
            return $this->resultClient(null, 1, $e->getMessage());
        }
    }

    /**
     * 数据分析
     */
    private function dataAnalysis($file)
    {
        ini_set("memory_limit", -1); //-1不限制内存
        
        if (!file_exists($file . '.json')) {
            
            // 获取Excel导入数据
            $excel_data = Excel::importData($file . '.xlsx');
            file_put_contents($file . '.json', json_encode($excel_data, JSON_UNESCAPED_UNICODE));
        }else {

            $file_txt = file_get_contents($file . '.json');
            $excel_data = json_decode($file_txt, true);
        }

        // print_r($excel_data);

        $id_list = [];
        $code_list = [];

        $cosnts = [
           '成长' => 1,
           '成熟' => 2,
           '衰退' => 3,
        ];

        $params = [];

        foreach ($excel_data as $idx => $v) {
            
            $id_list[$v['ID']][$v['year']] = [
                'id' => $v['ID'],
                'idx' => $idx,
                'scale' => $v['scale'],
                'output' => $v['output'],
            ];

            $code_list[$v['code']][$v['year']][] = [
                'id' => $v['ID'],
                'idx' => $idx,
                'scale' => $v['scale'],
                'output' => $v['output'],
            ];

            $rk = $v['code'] . '_' . $v['year'];

            if ($v['year'] != '2009') {

                $params[$rk] = 1; 
            }
        }


        // 获取一年总值
        $getYearOutput = function ($code, $year) use ($code_list)
        {
            // echo ("getYearOutput： code: $code, year: $year <hr>");
            $groups = $code_list[$code][$year] ?? [];
            return array_sum(array_column($groups, 'output'));
        };

        // 获取一年企业规模
        $getYearScale = function ($code, $year) use ($code_list)
        {
            // echo ("getYearOutput： code: $code, year: $year <hr>");
            $groups = $code_list[$code][$year] ?? [];
            return array_sum(array_column($groups, 'scale'));
        };

        // 检查占比
        $checkPercent = function ($total, $num, $per, $greater = true) 
        {
            if ($greater) {

                return (round($num / $total * 100, 2) > $per);
            }

            return (round($num / $total * 100, 2) < $per);
        };

        foreach ($params as $k => $v) {
            
            list($_code, $_year) = explode('_', $k);

            $groups = $code_list[$_code][$_year];
            $curr_sum = $getYearOutput($_code, $_year);

            // 获取去年总值
            $last_sum = $getYearOutput($_code, ($_year - 1));

            // 今年大于等于去年的总产值
            if ($curr_sum >= $last_sum) {

                $status = [
                    $cosnts['成熟'], 
                    $cosnts['衰退'], 
                ];
            }else {

                $status = [
                    $cosnts['成长'], 
                    $cosnts['成熟'], 
                ];
            }

            $prPercent = function ($param, $field = 'output', $type = 1, $per = 60) use ($groups, $checkPercent)
            {
                // 集群的权力关系
                if (count($groups) == 3) {// 集群个数3
                    
                    foreach ($groups as $idx => $val) {

                        if ($type == 2) {

                            // 是否有一个 > $per%
                            if ($checkPercent($param, $val[$field], $per)) {

                                return 1;// 不均衡型
                            }

                            return 0;// 均衡型
                        }
                        
                        // 是否有一个 > 50%
                        if ($checkPercent($param, $val[$field], 50)) {

                            $left_groups = $groups;
                            unset($left_groups[$idx]);

                            foreach ($left_groups as $vv) {
                                
                                // 是否有一个 > 40%
                                if ($checkPercent($param, $vv[$field], 40)) {

                                    return 2;// 两大一小
                                }else {

                                    return 3;// 两小一大
                                }
                            }
                        }else {

                            $left_groups = $groups;
                            unset($left_groups[$idx]);

                            foreach ($left_groups as $vv) {
                                
                                // 是否有一个 < 20%
                                if ($checkPercent($param, $vv[$field], 20, false)) {

                                    return 3;// 一大一小
                                }else {

                                    return 1;// 均衡型
                                }
                            }
                        }
                    }

                }else {
                    
                    // 是否有一个 < 20%
                    if ($checkPercent($param, $groups[0][$field], 80) || $checkPercent($param, $groups[1][$field], 80)) {

                        return ($type == 2) ? 1 : 2;// 不均衡型 | 两大一小
                    }else {

                        return ($type == 2) ? 0 : 1;// 均衡型
                    }
                }
            };

            $curr_scale = $getYearScale($_code, $_year);

            // 1-均衡型；2-两大一小；3-两小一大
            $pr_output1 = $prPercent($curr_sum);// 权力关系（产值）
            $pr_scale1  = $prPercent($curr_scale, 'scale');// 权力关系（企业规模）

            // 1-不均衡型；0-均衡型 > 60%
            $pr_output2 = $prPercent($curr_sum, 'output', 2);// 权力关系（产值）
            $pr_scale2  = $prPercent($curr_scale, 'scale', 2);// 权力关系（企业规模）

            // 1-不均衡型；0-均衡型 > 50%
            $pr_output3 = $prPercent($curr_sum, 'output', 2, 50);// 权力关系（产值）
            $pr_scale3  = $prPercent($curr_scale, 'scale', 2, 50);// 权力关系（企业规模）

            // 单个
            foreach ($groups as $val) {
                
                $cur_output = $val['output'];

                // 获取去年自己的总产值
                $last_output = $id_list[$val['id']][($_year - 1)]['output'] ?? 0;

                // 集群生命周期
                $lifecycle1 = $status[0];

                if ($cur_output < $last_output) {

                    $lifecycle1 = $status[1];
                }

                // 第二种情况，去除自身
                $rem_curr_sum = ($curr_sum - $cur_output);
                $rem_last_sum = ($last_sum - $last_output);

                if ($rem_curr_sum >= $rem_last_sum) {

                    $status2 = [
                        $cosnts['成熟'], 
                        $cosnts['衰退'], 
                    ];
                }else {
    
                    $status2 = [
                        $cosnts['成长'], 
                        $cosnts['成熟'], 
                    ];
                }

                $lifecycle2 = $status2[0];

                if ($cur_output < $last_output) {

                    $lifecycle2 = $status2[1];
                }

                $excel_val = $excel_data[$val['idx']];
                $excel_val['lifecycle1'] = $lifecycle1;
                $excel_val['lifecycle2'] = $lifecycle2;
                $excel_val['pr_scale1']  = $pr_scale1;
                $excel_val['pr_output1'] = $pr_output1;
                $excel_val['pr_scale2']  = $pr_scale2;
                $excel_val['pr_output2'] = $pr_output2;
                $excel_val['pr_scale3']  = $pr_scale3;
                $excel_val['pr_output3'] = $pr_output3;
                $excel_data[$val['idx']] = $excel_val;

                $id = $val['id'];
                echo ("id: $id, _code: $_code, _year: $_year, curr_sum: $curr_sum, last_sum: $last_sum, cur_output: $cur_output, last_output: $last_output, lifecycle1: $lifecycle1<hr>");
            }

            $_str_status = implode('_', $status);
            // echo ("_code: $_code, _year: $_year, curr_sum: $curr_sum, last_sum: $last_sum, status: $_str_status <hr>");

        }

        // print_r($code_list['026'][2010]);
        var_dump($excel_data);

        // return;
        
        $field_list = [];

        foreach ($excel_data[0] as $k => $v) {
            $field_list[] = ['Field' => $k, 'Comment' => '-'];
        }
        var_dump($field_list);

        foreach ($field_list as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }

        $fileName = '0218' . '_' . date('Y-m-d H:i:s', time());
        return Excel::exportData($excel_data, $header, $fileName, 'xlsx');
    }

    public function index()
    {        
        return $this->dataAnalysis('../data/Data0218');

        $field_list = [
            ['Field' => 'id', 'Comment' => 'ID'],
            ['Field' => 'sex', 'Comment' => '性别'],
            ['Field' => 'agx', 'Comment' => '年龄'],
            ['Field' => 'name', 'Comment' => '名称'],
            ['Field' => 'desc', 'Comment' => '介绍'],
            ['Field' => 'regtime', 'Comment' => '注册时间'],
            ['Field' => 'online_time', 'Comment' => '在线时长'],
        ];

        for ($i = 1; $i <= 10000; $i++) { 

            $info = [];
            
            foreach ($field_list as $val) {
                
                switch ($val['Field']) {
                    case 'id':
                        $info[strval($val['Field'])] = intval($i * 10000);
                        break;

                    case 'sex':
                        $arr = ['男', '女'];
                        $info[strval($val['Field'])] = $arr[rand(0, 1)];
                        break;

                    case 'agx':
                        $info[strval($val['Field'])] = rand(5, 100);
                        break;

                    case 'name':
                        $info[strval($val['Field'])] = $randomStr(rand(2, 4));
                        break;

                    case 'regtime':
                        $info[strval($val['Field'])] =  date('Y-m-d H:i:s', time() - rand(100, 10000));
                        break;

                    case 'online_time':
                        $info[strval($val['Field'])] =  rand(0, 1000000);
                        break;
                    
                    default:
                        $info[strval($val['Field'])] = $randomStr(rand(8, 100));
                        break;
                }
            }

            $data[] = $info;
        }

        $header = [];
        foreach ($field_list as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }

        $fileName = 'randomData' . '_' . date('Y-m-d H:i:s', time());
        return Excel::exportData($data, $header, $fileName, 'xlsx');

        $data = [];

        // $randomStr = function ($length)
        // {
        //     $code         = '';
        //     $characters   = "据美国媒体报道，美国北卡罗来纳州罗利市近日发生枪击案，年仅15岁的嫌疑人奥斯汀·汤普森开枪造成至少5人死亡，2人受伤，死者中包括他的哥哥。这是当地历史上最为严重的枪案之一。15岁的少年为什么能轻易获取枪支？美国枪患阴影对青少年造成了怎样的影响？
        //     枪支泛滥 4岁儿童持枪上学美国校园枪击案是其全国枪患的缩影。美国以占世界4.2%的人口保有世界46%的民用枪支，总数量高达3.93亿支。新冠疫情暴发以来，美国社会经济发展受阻叠加族群撕裂加剧，两党控枪进展缓慢，枪支泛滥愈演愈烈。据彭博社10月6日报道，美国2020年购买枪支的人数比2019年增加了约300万。
        //     枪支泛滥令美国青少年获取枪支的难度大大降低，甚至出现了幼儿携枪上学的“奇景”。据法新社9月1日报道，得克萨斯州一名4岁的男孩、亚利桑那州一名7岁的儿童被查出携带手枪上学，其父母被指控使用武器不当等罪名校园枪击事件数量创新高近年来，美国治安形势持续恶化，枪支暴力犯罪日益猖獗。美国总统拜登5月在白宫会见新西兰总理阿德恩时直言，他去过的大规模枪击案现场“比美国历任总统都要多”。美国疾病控制与预防中心（CDC）6日发布的最新报告显示，2020年至2021年，美国的持枪杀人案和持枪自杀案数量都增长8%以上，达上世纪90年代初以来的新高。美国芝加哥大学哈里斯公共政策学院和美联社公共事务研究中心近期联手举行的民意调查发现，有五分之一的受访者表示，过去五年中亲身经历过枪支暴力事件或有身边人经历过枪支暴力事件。";
        //     $day_date     = date('yMDHis', time());
            

        //     for ($i = 0; $i < $length; $i++) {

        //         $code .= $characters[mt_rand(0, strlen($characters) - 1)];
        //     }

        //     return $code;
        // };

        $randomStr = function ($num)
        {
            $b = '';
            for ($i=0; $i<$num; $i++) {
                // 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
                $a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
                // 转码
                $b .= iconv('GB2312', 'UTF-8', $a);
            }

            return $b;
        };

        $field_list = [
            ['Field' => 'id', 'Comment' => 'ID'],
            ['Field' => 'sex', 'Comment' => '性别'],
            ['Field' => 'agx', 'Comment' => '年龄'],
            ['Field' => 'name', 'Comment' => '名称'],
            ['Field' => 'desc', 'Comment' => '介绍'],
            ['Field' => 'regtime', 'Comment' => '注册时间'],
            ['Field' => 'online_time', 'Comment' => '在线时长'],
        ];

        for ($i = 1; $i <= 10000; $i++) { 

            $info = [];
            
            foreach ($field_list as $val) {
                
                switch ($val['Field']) {
                    case 'id':
                        $info[strval($val['Field'])] = intval($i * 10000);
                        break;

                    case 'sex':
                        $arr = ['男', '女'];
                        $info[strval($val['Field'])] = $arr[rand(0, 1)];
                        break;

                    case 'agx':
                        $info[strval($val['Field'])] = rand(5, 100);
                        break;

                    case 'name':
                        $info[strval($val['Field'])] = $randomStr(rand(2, 4));
                        break;

                    case 'regtime':
                        $info[strval($val['Field'])] =  date('Y-m-d H:i:s', time() - rand(100, 10000));
                        break;

                    case 'online_time':
                        $info[strval($val['Field'])] =  rand(0, 1000000);
                        break;
                    
                    default:
                        $info[strval($val['Field'])] = $randomStr(rand(8, 100));
                        break;
                }
            }

            $data[] = $info;
        }

        // echo json_encode($data, JSON_UNESCAPED_UNICODE);

        // return ;        
        $header = [];
        foreach ($field_list as $vo) {
            $comment = !empty($vo['Comment']) ? $vo['Comment'] : $vo['Field'];
            if (!in_array($vo['Field'], $this->noExportFields)) {
                $header[] = [$comment, $vo['Field']];
            }
        }

        $fileName = 'randomData' . '_' . date('Y-m-d H:i:s', time());
        return Excel::exportData($data, $header, $fileName, 'xlsx');
    }
}
