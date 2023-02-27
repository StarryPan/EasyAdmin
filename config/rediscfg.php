<?php
use think\facade\Env;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    'host'       => Env::get('redis.host', '127.0.0.1'),
    'port'       => (int)Env::get('redis.port', 6379),
    'password'   => Env::get('redis.password', '123456'),
    'select'     => (int)Env::get('redis.select', 0),
	'timeout'    => (int)Env::get('redis.timeout', 0),
	'expire'     => (int)Env::get('redis.expire', 0),
	'persistent' => true,
	'prefix'     => Env::get('redis.prefix', ''),
    'serialize'  => true,
	
	'MASTER_ADDR' => Env::get('redis.host', '127.0.0.1'),//主服务器
	'LAN_IP' => '127.0.0.1'//本机ip，  如果本机ip = 主服务器 ip ， 本机为主服务器
	
];
