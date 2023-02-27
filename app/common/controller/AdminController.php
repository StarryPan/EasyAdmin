<?php

namespace app\common\controller;


use app\BaseController;
use EasyAdmin\tool\CommonTool;
use think\facade\Env;
use think\Model;

/**
 * Class AdminController
 * @package app\common\controller
 */
class AdminController extends BaseController
{

    use \app\common\traits\JumpTrait;

    /**
     * 当前模型
     * @Model
     * @var object
     */
    protected $model;

    /**
     * 字段排序
     * @var array
     */
    protected $sort = [
        'id' => 'desc',
    ];

    /**
     * 允许修改的字段
     * @var array
     */
    protected $allowModifyFields = [
        'status',
        'sort',
        'remark',
        'is_delete',
        'is_auth',
        'title',
    ];

    /**
     * 不导出的字段信息
     * @var array
     */
    protected $noExportFields = ['delete_time', 'update_time'];

    /**
     * 下拉选择条件
     * @var array
     */
    protected $selectWhere = [];

    /**
     * 是否关联查询
     * @var bool
     */
    protected $relationSearch = false;

    /**
     * 模板布局, false取消
     * @var string|bool
     */
    protected $layout = 'layout/default';


    /**
     * 初始化方法
     */
    protected function initialize()
    {
        parent::initialize();
        $this->layout && $this->app->view->engine()->layout($this->layout);
    }

    /**
     * 模板变量赋值
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return mixed
     */
    public function assign($name, $value = null)
    {
        return $this->app->view->assign($name, $value);
    }

    /**
     * 解析和获取模板内容 用于输出
     * @param string $template
     * @param array $vars
     * @return mixed
     */
    public function fetch($template = '', $vars = [])
    {
        return $this->app->view->fetch($template, $vars);
    }

    /**
     * 重写验证规则
     * @param array $data
     * @param array|string $validate
     * @param array $message
     * @param bool $batch
     * @return array|bool|string|true
     */
    public function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        try {
            parent::validate($data, $validate, $message, $batch);
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
        return true;
    }

    /**
     * 获取POST请求里的奖励
     *
     * @param array $post
     * @param array $fields
     * @param string $validate
     * @return array
     */
    protected function getPostRewards(array $post, array $fields = [], string $validate = 'items'): array
    {
        try {

            if (empty($post) || empty($fields) || empty($validate)) {

                throw new \Exception('获取POST请求里的奖励失败，参数为空', 1);
            }

            $rewards   = [];
            $type_name = '奖励';

            // 验证类型
            switch ($validate) {
                case 'items':

                    if (empty($post['rew_num'])) {

                        throw new \Exception('获取POST请求里的奖励失败，缺少字段：rew_num', 1);
                    }

                    for ($rew_num = 1; $rew_num <= intval($post['rew_num']); $rew_num++) {

                        $rew = [];

                        foreach ($fields as $name) {

                            $field_key = 'key_' . $name . '_' . $rew_num;

                            if (empty($post[strval($field_key)])) {

                                continue;
                            }

                            $rew[$name] = intval($post[strval($field_key)]);
                        }

                        if ($rew == null) {

                            continue;
                        }

                        $rewards[] = $rew;
                    }
                    break;

                case 'heros':

                    if (empty($post['hero_num'])) {

                        throw new \Exception('获取POST请求里的奖励失败，缺少字段：hero_num', 1);
                    }

                    $type_name = '角色';

                    for ($hero_num = 1; $hero_num <= intval($post['hero_num']); $hero_num++) {

                        $hero = [];

                        foreach ($fields as $name) {

                            $field_key = 'key_' . $name . '_' . $hero_num;

                            if (empty($post[strval($field_key)])) {

                                continue;
                            }

                            $hero[$name] = intval($post[strval($field_key)]);
                        }

                        if ($hero == null) {

                            continue;
                        }

                        $rewards[] = $hero;
                    }
                    break;

                case 'servers':

                    if (empty($post['server_num'])) {

                        throw new \Exception('获取POST请求里的奖励失败，缺少字段：server_num', 1);
                    }

                    $type_name = '服务器';

                    for ($server_num = 1; $server_num <= intval($post['server_num']); $server_num++) {

                        $server = [];

                        foreach ($fields as $name) {

                            $field_key = 'key_' . $name . '_' . $server_num;

                            if (empty($post[strval($field_key)])) {

                                continue;
                            }

                            $server[$name] = intval($post[strval($field_key)]);
                        }

                        if ($server == null) {

                            continue;
                        }

                        $rewards[] = $server;
                    }
                    break;

                default:
                    break;
            }

            if ($rewards == null) {

                throw new \Exception($type_name . '不能为空', 1);
            }

            return $rewards;
        } catch (\Exception $e) {
            LogError($e); // 写入报错日志
            $this->error($e->getMessage());
        }
    }

    /**
     * 构建CURL请求参数
     * @param array $tableName 表名
     * @param array $excludeFields 忽略构建搜索的字段
     * @param array $verifyFields 验证忽略构建搜索的字段
     * @return array
     */
    protected function buildTableCurlParames($tableName = '', $excludeFields = [], $verifyFields = true)
    {
        $get      = $this->request->get('', null, null);
        $ops      = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
        $page     = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit    = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        $filters  = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';

        // json转数组
        $ops      = json_decode($ops, true);
        $where    = [];
        $filters  = json_decode($filters, true);
        $excludes = [];

        if (!$tableName) {

            // 判断是否关联查询
            $tableName = CommonTool::humpToLine(lcfirst($this->model->getName()));
        }

        foreach ($filters as $key => $val) {

            if (in_array($key, $excludeFields)) {

                $excludes[$key] = $val;
                continue;
            }

            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
            }

            switch (strtolower($op)) {
                case '=':
                    $where[] = [$key, '=', $val];
                    break;
                case '%*%':
                    $where[] = [$key, 'LIKE', "%{$val}%"];
                    break;
                case '*%':
                    $where[] = [$key, 'LIKE', "{$val}%"];
                    break;
                case '%*':
                    $where[] = [$key, 'LIKE', "%{$val}"];
                    break;
                case 'range':
                    [$beginTime, $endTime] = explode(' - ', $val);
                    $where[] = [$key, '>=', strtotime($beginTime)];
                    $where[] = [$key, '<=', strtotime($endTime)];
                    break;
                default:
                    $where[] = [$key, $op, "%{$val}"];
            }
        }

        // 验证是否缺少字段
        if ($verifyFields) {

            foreach ($excludeFields as $field_name) {

                if (empty($excludes[$field_name])) {

                    $error_msg = $field_name . ' 不能为空';

                    switch ($field_name) {
                        case 'server_id':
                            $error_msg = '请选择服务器';
                            break;

                        case 'month':
                            $error_msg = '请选择查询日期';
                            break;

                        case 'act_id':
                            $error_msg = '请选择所属的活动';
                            break;

                        default:
                            break;
                    }

                    throw new \Exception($error_msg, 1);
                }
            }

        } else {

            foreach ($excludeFields as $field_name) {

                $excludes[$field_name] = isset($excludes[$field_name]) ? $excludes[$field_name] : '';
            }
        }

        $excludes['table_name'] = $tableName;

        return [$page, $limit, $where, $excludes];
    }

    /**
     * 构建请求参数
     * @param array $excludeFields 忽略构建搜索的字段
     * @return array
     */
    protected function buildTableParames($excludeFields = [])
    {
        $get = $this->request->get('', null, null);
        $page = isset($get['page']) && !empty($get['page']) ? $get['page'] : 1;
        $limit = isset($get['limit']) && !empty($get['limit']) ? $get['limit'] : 15;
        $filters = isset($get['filter']) && !empty($get['filter']) ? $get['filter'] : '{}';
        $ops = isset($get['op']) && !empty($get['op']) ? $get['op'] : '{}';
        // json转数组
        $filters = json_decode($filters, true);
        $ops = json_decode($ops, true);
        $where = [];
        $excludes = [];

        // 判断是否关联查询
        $tableName = CommonTool::humpToLine(lcfirst($this->model->getName()));

        foreach ($filters as $key => $val) {
            if (in_array($key, $excludeFields)) {
                $excludes[$key] = $val;
                continue;
            }
            $op = isset($ops[$key]) && !empty($ops[$key]) ? $ops[$key] : '%*%';
            if ($this->relationSearch && count(explode('.', $key)) == 1) {
                $key = "{$tableName}.{$key}";
            }
            switch (strtolower($op)) {
                case '=':
                    $where[] = [$key, '=', $val];
                    break;
                case '%*%':
                    $where[] = [$key, 'LIKE', "%{$val}%"];
                    break;
                case '*%':
                    $where[] = [$key, 'LIKE', "{$val}%"];
                    break;
                case '%*':
                    $where[] = [$key, 'LIKE', "%{$val}"];
                    break;
                case 'range':
                    [$beginTime, $endTime] = explode(' - ', $val);
                    $where[] = [$key, '>=', strtotime($beginTime)];
                    $where[] = [$key, '<=', strtotime($endTime)];
                    break;
                default:
                    $where[] = [$key, $op, "%{$val}"];
            }
        }
        return [$page, $limit, $where, $excludes];
    }

    /**
     * 下拉选择列表
     * @return \think\response\Json
     */
    public function selectList()
    {
        $fields = input('selectFields');
        $data = $this->model
            ->where($this->selectWhere)
            ->field($fields)
            ->select();
        $this->success(null, $data);
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
    public function curlData($url, $params = false, $ispost = 0, $header = [], $verify = false)
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

        // 复制请求参数
        $par_pms = $params;

        // 判断是否为数组
        if (is_array($params)) {
            $params = http_build_query($params);
        }

        // 是否是POST请求
        if ($ispost) {

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {

            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
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

        // 返回数据
        return $response;
    }
}
