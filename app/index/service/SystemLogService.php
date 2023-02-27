<?php

namespace app\index\service;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Config;
use app\index\model\GameConfig;

/**
 * 系统日志表
 * Class SystemLogService
 * @package app\index\service
 */
class SystemLogService
{

    /**
     * 当前实例
     * @var object
     */
    protected static $instance;

    /**
     * 表前缀
     * @var string
     */
    protected $tablePrefix;

    /**
     * 表后缀
     * @var string
     */
    protected $tableSuffix;

    /**
     * 表名
     * @var string
     */
    protected $tableName;

    /**
     * 构造方法
     * SystemLogService constructor.
     */
    protected function __construct()
    {
        $this->tablePrefix = Config::get('database.connections.mysql.prefix');
        $this->tableSuffix = date('Ym', time());
        $this->tableName = "{$this->tablePrefix}system_log_{$this->tableSuffix}";
        return $this;
    }

    /**
     * 获取实例对象
     * @return SystemLogService|object
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 检查是否为新的月份
     *
     * @return boolean
     */
    public function isNewMonthly(): bool
    {
        $cache_data = Cache::get( $this->tableName );
        if (!$cache_data) {

            // 创建数据表
            $this->detectTable();
            Cache::set( $this->tableName, 1, 2678400 );
            return true;
        }

        return false;
    }

    /**
     * 保存数据
     * @param $data
     * @return bool|string
     */
    public function save($data)
    {
        $this->detectTable();
        Db::startTrans();
        try {

            // 检查日志类型是否为空
            if (!isset( $data['log_type'] )) {

                $data['log_type'] = 0;// 默认系统日志

            }else{

                // curl请求日志
                if ($data['log_type'] == 1) {

                    // 解析操作API
                    $data['api']   = $this->analysisApi($data);

                    // 获取请求的标题
                    $data['title'] = GameConfig::getConfigCurlTitleWithApi($data['api']);
                }
            }

            // 请求方式-强制小写
            $data['method'] = strtolower( $data['method'] );

            // 插入数据
            Db::table($this->tableName)->insert($data);
            Db::commit();

        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return true;
    }

    /**
     * 解析操作API
     *
     * @param array $data
     * @return string
     */
    public function analysisApi($data = []): string
    {
        $api = '';

        $url_arr = explode('index.php/Api/', $data['url']);

        if (count($url_arr) == 2) {

            $api = strval($url_arr[1]);
        }

        return $api;
    }

    /**
     * 检测数据表
     * @return bool
     */
    protected function detectTable()
    {
        $check = Db::query("show tables like '{$this->tableName}'");
        if (empty($check)) {
            $sql = $this->getCreateSql();
            Db::execute($sql);
        }
        return true;
    }

    public function getAllTableList()
    {

    }

    /**
     * 根据后缀获取创建表的sql
     * @return string
     */
    protected function getCreateSql()
    {
        return <<<EOT
CREATE TABLE `{$this->tableName}` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
	`log_type` tinyint(1) unsigned NOT NULL COMMENT '日志类型：0.系统，1.curl请求，2.报错',
    `admin_id` int(10) unsigned DEFAULT '0' COMMENT '管理员ID',
    `api` varchar(800) NOT NULL DEFAULT '' COMMENT '操作API',
    `url` varchar(1500) NOT NULL DEFAULT '' COMMENT '操作页面',
    `method` varchar(50) NOT NULL COMMENT '请求方法',
    `title` varchar(100) DEFAULT '' COMMENT '日志标题',
    `content` longtext NOT NULL COMMENT '内容',
	`response` longtext NOT NULL COMMENT '响应数据',
    `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
    `useragent` varchar(255) DEFAULT '' COMMENT 'User-Agent',
    `create_time` int(10) DEFAULT NULL COMMENT '操作时间',
    PRIMARY KEY (`id`),
    KEY `ip` (`ip`),
    KEY `url` (`url`),
	KEY `admin_id` (`admin_id`),
    KEY `log_type` (`log_type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='后台操作日志表 - {$this->tableSuffix}';
EOT;
    }

}