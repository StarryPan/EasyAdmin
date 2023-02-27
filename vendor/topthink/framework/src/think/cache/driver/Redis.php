<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\cache\driver;

use think\cache\Driver;

/**
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 *
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @author    尘缘 <130775@qq.com>
 */
class Redis extends Driver
{
    /** @var \Predis\Client|\Redis */
    protected $handler;

    /**
     * 配置参数
     * @var array
     */
    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
        'tag_prefix' => 'tag:',
        'serialize'  => [],
    ];

    static protected $instance;

    /**
     * 实例化
     *
     * @return void
     */
    static public function getInstance(){
        //判断$instance是否是Uni的对象
        //没有则创建
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 架构函数
     * @access public
     * @param array $options 缓存参数
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new \Redis;
            $this->options = empty( $options ) ? \think\facade\Config::get('rediscfg') : $options;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int) $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], (int) $this->options['port'], $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
        } elseif (class_exists('\Predis\Client')) {
            $params = [];
            foreach ($this->options as $key => $val) {
                if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
                    $params[$key] = $val;
                    unset($this->options[$key]);
                }
            }

            if ('' == $this->options['password']) {
                unset($this->options['password']);
            }

            $this->handler = new \Predis\Client($this->options, $params);

            $this->options['prefix'] = '';
        } else {
            throw new \BadFunctionCallException('not support: redis');
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name): bool
    {
        return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name    缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $this->readTimes++;

        $value = $this->handler->get($this->getCacheKey($name));

        if (false === $value) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name   缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire 有效时间（秒）
     * @return bool
     */
    public function set($name, $value, $expire = 86400): bool
    {
        $this->writeTimes++;

        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value  = $this->serialize($value);

        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }

        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int    $step 步长
     * @return false|int
     */
    public function inc(string $name, int $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int    $step 步长
     * @return false|int
     */
    public function dec(string $name, int $step = 1)
    {
        $this->writeTimes++;

        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function delete($name): bool
    {
        $this->writeTimes++;

        $result = $this->handler->del($this->getCacheKey($name));
        return $result > 0;
    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     */
    public function clear(): bool
    {
        $this->writeTimes++;

        $this->handler->flushDB();
        return true;
    }

    /**
     * 删除缓存标签
     * @access public
     * @param array $keys 缓存标识列表
     * @return void
     */
    public function clearTag(array $keys): void
    {
        // 指定标签清除
        $this->handler->del($keys);
    }

    /**
     * 追加（数组）缓存数据
     * @access public
     * @param string $name  缓存标识
     * @param mixed  $value 数据
     * @return void
     */
    public function push(string $name, $value): void
    {
        $this->handler->sAdd($name, $value);
    }

    /**
     * 获取标签包含的缓存标识
     * @access public
     * @param string $tag 缓存标签
     * @return array
     */
    public function getTagItems(string $tag): array
    {
        return $this->handler->sMembers($tag);
    }

    /**
     * 添加日志列表
     * @access public
     * @param string $tab_name 表名
     * @param array  $log_data 日志数据
     * @return booleanean
     */
    public function addLogList( $tab_name = '', $log_data = [] )
    {

        if ( empty( $tab_name ) ) {
            
            return false;
        }elseif ( !is_array( $log_data ) ) {
            
            return false;
        }else{

            $redis_key  = 'AddLogList:'.$tab_name;
            
            $redis_data = json_encode( $log_data , JSON_UNESCAPED_UNICODE );

            $this->rpush( $redis_key, $redis_data );

            return true;
        }

        return false;
    }

    public function exists($k){
        return $this->handler->exists($k);
    }
    
    public function setnx($k,$v){
        return $this->handler->setnx($k,$v);
    }
    
    public function incrBy($k,$v){
        return $this->handler->incrBy($k,$v);
    }
    
    public function HGETALL($k){
        return $this->handler->HGETALL($k);
    }

    public function hMset($k,$v){
        //echo 'redis  hMsethMsethMset';
        return $this->handler->hMset($k,$v);
    }
    
    public function hSet($k,$k1,$v){
        //echo 'redis  hMsethMsethMset';
        return $this->handler->hSet($k,$k1,$v);
    }
    
    public function lLen($k){
        //echo 'redis  hMsethMsethMset';
        return $this->handler->lLen($k);
    }

    public function lpop($k){
        //echo 'redis  hMsethMsethMset';
        return $this->handler->lpop($k);
    }

    public function hDel($k){
        return $this->handler->hDel($k);
    }

    public function keys($k){
        return $this->handler->keys($k);
    }

    public function scans($k = '', $count = 1000){

        $keys     = [];
        $iterator = null;

		while (true) {

            $scans = $this->handler->scan( $iterator, $k, $count );
            
            // 迭代结束，未找到匹配pattern的key
            if ( $scans === false ) {

                break;
            }
            
			foreach ($scans as $key) {

                $keys[] = $key;
            }
        }
        
        return $keys;
    }

    public function scanDelete($k = '', $count = 1000){

        if ( $k == null ) {
            
            return false;
        }

        ini_set('memory_limit', '-1');// 无线运行内存
		ini_set('max_execution_time', 7200 );// 永远不停止

        $handler  = $this->handler;
        $iterator = null;

		while (true) {

            $scans = $handler->scan( $iterator, $k, $count );
            
            // 迭代结束，未找到匹配pattern的key
            if ( $scans === false ) {

                break;
            }

            $handler->delete( $scans );
        }
        
        return true;
    }
    
    public function expire($k,$v){
        return $this->handler->expire($k,$v);
    }
    
    public function publish($key , $msg){
        $msg = json_encode($msg);
        return $this->handler->publish($key , $msg);
    }
	
	public function rpush($k,$v){
		return $this->handler->rpush($k,$v);
	}

    public function incr($k){
        return $this->handler->incr($k);
    }

    public function getHandler(){
        return $this->handler;
    }

    // 向名称为key的zset中添加元素member，score用于排序。如果该元素已经存在，则根据score更新该元素的顺序。
    public function zAdd($key = '', $score = 0, $member = 0){
         return $this->handler->zAdd( $key, $score, $member );
    }

    // 返回名称为key的zset的所有元素的个数
    public function zCard($key = ''){
         return $this->handler->zCard( $key );
    }

    // 返回名称为key的zset（元素已按score从小到大排序）中val元素的rank（即index，从0开始），若没有val元素，返回“null”。zRevRank 是从大到小排序
    public function zRank( $key = '' , $val = 0){
         return $this->handler->zRevRank( $key, $val);
    }

    // 返回名称为key的zset（元素已按score从大到小排序）中的index从start到end的所有元素.withscores: 是否输出socre的值，默认false，不输出
    public function zRevRange($key = '', $start = 0, $end = 10, $socre = true){
         return $this->handler->zRevRange( $key, $start, $end, $socre );
    }

    // 返回名称为key的zset中元素val2的score
    public function zScore($key = '', $val = 0){
         return $this->handler->zScore( $key, $val );
    }

}
