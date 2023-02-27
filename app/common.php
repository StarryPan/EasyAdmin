<?php
// 应用公共文件
use think\facade\Cache;
use think\facade\Request;
use think\cache\driver\Redis;
use app\index\model\CacheData;

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new \app\common\service\AuthService(session('admin.id'));
        $check       = $authService->checkNode($node);
        return $check;
    }
}

if (!function_exists('isNull')) {

    /**
     * 判断是否为空
     *
     * @param string $val
     * @return boolean
     */
    function isNull($val = ''): bool
    {
        $bool = false;

        switch ($val) {
            case '':
            case '0':
            case null:
            case false:
            case 'null':
            case 'false':
            case empty($val):
                $bool = true;
                break;

            default:
                $bool = false;
                break;
        }

        return $bool;
    }
}

if (!function_exists('isLock')) {
    /**
     * 进程上锁
     *
     * @param string $key
     * @param integer $exp
     * @return boolean
     */
    function isLock(string $key = '', int $exp = 300): bool
    {
        $key   = 'isLock:' . $key;
        $redis = Redis::getInstance();
        if ($redis->setnx($key, time())) {

            return false;
        }

        $locktime = $redis->get($key);
        if ((time() - $locktime) > $exp) {

            $redis->delete($key);
            return false;
        }

        return true;
    }

    /**
     * 运行次数
     *
     * @param string $key
     * @param integer $limit
     * @param integer $expire
     * @return boolean
     */
    function runTimes(string $key = '', int $limit = 1, $expire = 86400): bool
    {
        $key   = 'runTimes:' . $key;
        $redis = Redis::getInstance();

        if ($limit > 0) {

            // 自增
            $inc_num = $redis->inc($key, 1);

            if ($inc_num > $limit) {

                return true;
            }

            $redis->expire($key, $expire);
        } else {

            // 重置
            $redis->set($key, 0, $expire);
            return true;
        }

        return false;
    }
}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('isLock')) {

    /**
     * 进程上锁
     *
     * @param string $key
     * @param integer $exp
     * @return boolean
     */
    function isLock($key = '', $exp = 300): bool
    {
        $redis = Redis::getInstance();

        if ($redis->setnx($key, time())) {

            return false;
        }

        $locktime = $redis->get($key);
        if ((time() - $locktime) > $exp) {

            $redis->delete($key);
            return false;
        }

        return true;
    }
}

if (!function_exists('getHttp')) {

    /**
     * 匹配去除空格及换行
     *
     * @param string $str
     * @return string
     */
    function cleanStrFormat($str = ''): string
    {
        $qian = array(" ", "　", "\t", "\n", "\r");
        return str_replace($qian, '', $str);
    }

    /**
     * 字符串解析为数组
     *
     * @param string $str
     * @param string $pms
     * @return void
     */
    function strToIntArray($str = '', $pms = '|')
    {
        if ($str == '') {

            return [];
        }

        // 左右括号舍去
        $str = str_replace('[', '', $str);
        $str = str_replace(']', '', $str);

        $arr = explode($pms, $str);

        if ($arr == null) {

            return [];
        }

        return $arr;
    }

    /**
     * 奖励转字符串
     *
     * @param array $arr
     * @param string $pms
     * @return string
     */
    function rewardToString(array $arr, $pms = '|'): string
    {
        if (!$arr) {

            return '';
        }

        $str = '';

        foreach ($arr as $itemid => $itemcnt) {

            $str .= "[$itemid$pms$itemcnt]";
        }

        return $str;
    }

    /**
     * 字符串解析为多个奖励格式
     *
     * @param string $str
     * @param string $par
     * @param integer $multiple
     * @return array
     */
    function strToIntRewards(string $str = '', string $par = '|', int $multiple = 1): array
    {
        if ($str == null || $str == 'null' || $par == null) {

            return [];
        }

        if (!$multiple) {

            throw new \Exception('字符串解析为多个奖励格式，倍数不能为0', 1);
        }

        // 去除头和尾的字符
        $str  = substr($str, 1);
        $str  = substr($str, 0, strlen($str) - 1);

        // 分割字符串
        $arrs   = explode('][', $str);
        $arrays = [];

        foreach ($arrs as $val) {

            list($itemid, $count) = explode('|', $val);
            $mt_count             = (intval($count) * $multiple);
            $arrays[$itemid]      = empty($arrays[$itemid]) ? $mt_count : ($arrays[$itemid] + $mt_count);
        }

        return $arrays;
    }

    /**
     * 获取接口地址
     * 
     * @param int $sid      服务器ID
     * @param int $fun_name 方法名
     * @param int $action   控制器
     * @return string
     */
    function getApiUrl($sid = '', $fun_name = '', $action = 'Api'): string
    {
        // 判断服务器ID是否为空
        if ($sid == NULL) {

            throw new \Exception('服务器ID为空', 1);
        } elseif ($action == NULL) {

            throw new \Exception('控制器参数为空', 1);
        } elseif ($fun_name == NULL) {

            throw new \Exception('方法名参数为空', 1);
        } else {

            // 获取服务器信息
            $server_info = \app\index\model\ServerList::getServerInfo($sid);

            if ($server_info->shost == null) {

                throw new \Exception('服务器：短连接地址为空', 1);
            }

            // 生成地址
            return 'http://' . $server_info->shost . '/index.php/Api/' . $action . '/' . $fun_name;
        }

        return NULL;
    }

    /**
     * 获取后台请求秘钥
     *
     * @param array $pms
     * @return array
     */
    function getTmSecKey($pms = [], $is_log = true): array
    {

        $itme   = time();
        $pass   = \app\index\model\CfgConst::SystemServerPassword;
        $seckey = [
            'time'   => $itme,
            'pass'   => md5($itme . $pass),
            'is_log' => $is_log
        ];

        return array_merge($seckey, $pms);
    }

    /**
     * http请求
     * @param  string  $url    请求地址
     * @param  boolean|string|array $params 请求数据
     * @param  integer $ispost 0/1，是否post
     * @param  array  $header
     * @param  $verify 是否验证ssl
     * return string|boolean          出错时返回false
     */
    function getHttp($url = '', $params = false, $ispost = 1, $header = [], $verify = false)
    {
        $httpInfo = array();
        $ch       = curl_init();
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        //忽略ssl证书
        if ($verify === true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Log记录的参数
        $log_params = $params;

        // 判断是否为数组
        if (is_array($params)) {
            $params = http_build_query($params);
        }

        $method = 'null';

        // 是否是POST请求
        if ($ispost) {

            $method = 'post';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {

            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            $method = 'get';
        }

        // 访问地址
        $response = curl_exec($ch);

        // 是否访问失败
        if ($response === FALSE) {
            trace("cURL Error: " . curl_errno($ch) . ',' . curl_error($ch), 'error');
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
            trace($httpInfo, 'error');
            return false;
        }

        // 关闭请求
        curl_close($ch);

        if (isNull($response)) {

            throw new \Exception('服务器未响应。', 1);
        }

        $url_data = json_decode($response, true);

        if (!$url_data) {

            throw new \Exception($response, 1);
        }

        // 检查是否记录Log
        if (isset($log_params['is_log']) && $log_params['is_log']) {

            // 清除无效的字段
            unset($log_params['time'],
            $log_params['pass'],
            $log_params['is_log']);

            // 记录Log
            $data = [
                'ip'          => \EasyAdmin\tool\CommonTool::getRealIp(),
                'url'         => $url,
                'method'      => $method,
                'content'     => json_encode($log_params, JSON_UNESCAPED_UNICODE),
                'log_type'    => 1, // CURL请求类型
                'admin_id'    => session('admin.id'),
                'response'    => $response,
                'useragent'   => $_SERVER['HTTP_USER_AGENT'],
                'create_time' => time(),
            ];

            // 写入数据库
            \app\index\service\SystemLogService::instance()->save($data);

            // 刷新日志缓存
            \app\index\model\SystemLog::refreshWelcomeCurlLogList();
        }

        // 检查返回是否报错
        if ($url_data['code'] != 0) {

            throw new \Exception($url_data['msg'], $url_data['code']);
        }

        // 返回数据
        return $url_data;
    }

    /**
     * 返回给Ajax请求的JSON数据
     *
     * @param integer $code
     * @param string $msg
     * @param array $data
     * @param integer $count
     * @return void
     */
    function ajaxJson($code = 0, $msg = '', $data = [], $count = 0)
    {
        $data = [
            'code'  => $code,
            'msg'   => $msg,
            'count' => $count,
            'data'  => $data,
        ];

        return json($data);
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }
}

if (!function_exists('LogError')) {

    /**
     * 把错误写入文件
     *
     * @param string $e
     * @param boolean $is_obj
     * @return boolean
     */
    function LogError($e = ''): bool
    {
        $params = $_REQUEST;
        $method = Request::method();

        // 报错字符串
        $err_str = "";

        // 检查是否为对象
        if (!empty($e) && is_object($e) && method_exists($e, "getMessage") && method_exists($e, "getTraceAsString")) {

            // 检查是否入库
            if ($e->getCode() == 90009) {

                return false;
            }

            $err_str .= $e->getMessage() . "\n" . $e->getTraceAsString();
        } else {

            $err_str .= $e;
        }

        // 检查报错中是否有HTML代码
        if (stristr($err_str, '<!DOCTYPE html>')) {

            // 不允许HTML代码入库，以免显示报错
            return false;
        }

        // 记录Log
        $data = [
            'ip'          => \EasyAdmin\tool\CommonTool::getRealIp(),
            'url'         => Request::url(),
            'method'      => $method,
            'content'     => json_encode($params, JSON_UNESCAPED_UNICODE),
            'log_type'    => 2, // ERROR报错类型
            'response'    => $err_str,
            'admin_id'    => session('admin.id'),
            'useragent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'server',
            'create_time' => time(),
        ];

        return \app\index\service\SystemLogService::instance()->save($data);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\index\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\index\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('strLansData')) {

    /**
     * 提取字符串中的多语言数据
     *
     * @param string $lan_str
     * @param array $lans
     * @return array
     */
    function strLansData($lan_str = '', $lans = []): array
    {
        $data        = [];
        $lan_content = str_replace(' ', '', $lan_str);

        foreach ($lans as $lan_name => $pname) {

            $str_arr = explode('*' . $lan_name . '{', $lan_content);
            if (empty($str_arr[1])) {
                continue;
            }

            $str_arr2       = explode('}', $str_arr[1]);
            $data[$pname] = str_replace(array("\r\n", "\r", "\n"), "", $str_arr2[0]);
        }

        return $data;
    }
}

if (!function_exists('errorMsgStr')) {

    /**
     * 数组转字符串
     *
     * @param array $arr
     * @param string $pms
     * @return string
     */
    function arrayToStr(array $arr, string $pms = ', '): string
    {
        if (isNull($arr)) {

            return '';
        }

        if (isNull($pms)) {

            throw new \Exception('数组转字符串失败，参数为空', 1);
        }

        $forFun = function ($data) use ($pms, &$forFun) {

            $str        = '';
            $is_orderly = arrayIsOrderly($data);

            foreach ($data as $key => $val) {

                // 不是有序数组，打印KEY
                !$is_orderly && $str .= "'$key'=>";

                if (is_array($val)) {

                    $str .= 'Array(' . $forFun($val) . ')' . $pms;
                } else {

                    $str .= $val . $pms;
                }
            }

            return substr($str, 0, strlen($str) - strlen($pms));
        };

        return $forFun($arr);
    }

    /**
     * 检查数组是否为有序
     *
     * @param array $arr
     * @return boolean
     */
    function arrayIsOrderly(array $arr): bool
    {
        if (isNull($arr)) {

            return true;
        }

        for ($i = 0; $i < count($arr); $i++) {

            if (!isset($arr[$i])) {

                return false;
            }
        }

        return true;
    }

    /**
     * 保存消息字符串
     *
     * @param string $msg
     * @param array $err_arr
     * @return string
     */
    function errorMsgStr($msg = '', $err_arr = []): string
    {
        $err_pms = '';

        if ($err_arr && is_array($err_arr)) {

            $err_pms = ' [' . arrayToStr($err_arr) . ']';
        }

        return $msg . $err_pms;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }
}

if (!function_exists('getRedisPackKey')) {

    /**
     * 获取公共Redis包Key
     *
     * @param string $_key
     * @return string
     */
    function getRedisPackKey($_key = ''): string
    {
        if ($_key == null) {

            return $_key;
        }


        $redis_key = 'CommonConfigInc:' . $_key;
        $redis_pid = null;

        if (isset(CacheData::$cachekey[$redis_key])) {

            $redis_pid = CacheData::$cachekey[$redis_key];
        } else {

            $redis     = Redis::getInstance();
            $redis_pid = $redis->get($redis_key);

            if ($redis_pid == null) {

                $redis_pid = $redis->inc($redis_key, 133);
            }

            CacheData::$cachekey[$redis_key] = $redis_pid;
        }

        // 重组KEY
        return $_key . '_' . $redis_pid . ':';
    }

    /**
     * 修改公共Redis包Key
     *
     * @param string $_key
     * @param integer $inc
     * @return integer
     */
    function updateRedisPackKey($_key = '', $inc = 1): int
    {
        if ($_key == null || $inc == null) {

            return false;
        }

        // 引用Redis
        $redis     = Redis::getInstance();
        $redis_key = 'CommonConfigInc:' . $_key;
        unset(CacheData::$cachekey[$redis_key]); // 删除进程级缓存

        return $redis->inc($redis_key, $inc);
    }
}
