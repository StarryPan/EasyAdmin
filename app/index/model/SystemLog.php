<?php
namespace app\index\model;

use think\facade\Cache;
use app\common\model\TimeModel;
use app\index\service\SystemLogService;

class SystemLog extends TimeModel
{

    /**
     * 缓存有效期-24小时
     *
     * @var integer
     */
    private static $cache_expire = 300;

    /**
     * 构造器
     *
     * @param array $data
     */
    public function __construct(array $data = []) 
    {
        parent::__construct($data);
        $this->name = 'system_log_' . date('Ym');
    }

    /**
     * 设置月份
     *
     * @param [type] $month
     * @return void
     */
    public function setMonth($month)
    {
        $this->name = 'system_log_' . $month;

        // 检查是否新的一月，创建数据表
        SystemLogService::instance()->isNewMonthly();
        return $this;
    }

    public function admin()
    {
        return $this->belongsTo('app\index\model\SystemAdmin', 'admin_id', 'id');
    }

    /**
     * 获取欢迎页日志列表
     *
     * @param integer $limit
     * @return array
     */
    public static function getWelcomeLogList($limit = 10): array
    {
        $cache_key  = 'getWelcomeLogList';
        $cache_data = Cache::get($cache_key);

        if (!$cache_data) {

            $mLog       = new self;
            $log_list   = $mLog->where('log_type', 1)->order('create_time', 'desc')->limit($limit)->select();
            $cache_data = '[]';

            if (!$log_list->isEmpty()) {

                $cache_data = json_encode($log_list->toArray(), JSON_UNESCAPED_UNICODE);
            }
            
            Cache::set($cache_key, $cache_data, self::$cache_expire);
        }

        if (!$cache_data || $cache_data == '[]') {

            return [];
        }

        return json_decode($cache_data, true);
    }

    /**
     * 刷新欢迎页请求日志列表
     *
     * @return boolean
     */
    public static function refreshWelcomeCurlLogList(): bool
    {
        Cache::delete('getWelcomeLogList');
        return true;
    }


}