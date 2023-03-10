<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace EasyAdmin\tool;


class CommonTool
{

    /**
     * 下划线转驼峰
     * @param $str
     * @return null|string|string[]
     */
    public static function lineToHump($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param $str
     * @return null|string|string[]
     */
    public static function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return $str;
    }

    /**
     * 获取真实IP
     * @return mixed
     */
    public static function getRealIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        return $ip;
    }

    /**
     * 读取文件夹下的所有文件
     * @param $path
     * @param $basePath
     * @return array|mixed
     */
    public static function readDirAllFiles($path, $basePath = '')
    {
        list($list, $temp_list) = [[], scandir($path)];
        empty($basePath) && $basePath = $path;
        foreach ($temp_list as $file) {
            if ($file != ".." && $file != ".") {
                if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                    $childFiles = self::readDirAllFiles($path . DIRECTORY_SEPARATOR . $file, $basePath);
                    $list = array_merge($childFiles, $list);
                } else {
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;
                    $fileName = str_replace($basePath . DIRECTORY_SEPARATOR, '', $filePath);
                    $list[$fileName] = $filePath;
                }
            }
        }
        return $list;
    }

    /**
     * 模板值替换
     * @param $string
     * @param $array
     * @return mixed
     */
    public static function replaceTemplate($string, $array)
    {
        foreach ($array as $key => $val) {
            $string = str_replace("{{" . $key . "}}", $val, $string);
        }
        return $string;
    }

    /**
     * 验证表数据
     *
     * @param string $tableName
     * @param array $data
     * @return boolean
     */
    public static function verifyTableData($tableName = '', $data = []): bool
    {
        if ($tableName == null || !$data) {

            throw new \Exception('验证表数据失败，参数为空', 1);
        }

        if (!is_array($data)) {

            throw new \Exception('验证表数据失败，data不是数组类型', 1);
        }

        $tableName  = CommonTool::humpToLine(lcfirst($tableName));
        $db_prefix  = config('database.connections.mysql.prefix');
        $tb_columns = \think\facade\Db::query("show full columns from {$db_prefix}{$tableName}");

        if (!$tb_columns) {
            
            throw new \Exception('验证表数据失败，数据不存在！', 1);
        }

        // 不导出字段
        $not_export = [
            'update_time',
            'delete_time'
        ];

        // 检查是否为多维数组
        count($data) != count($data, 1) && $data = array_shift($data);

        foreach ($tb_columns as $val) {

            // 跳过验证的字段
            if (in_array($val['Field'], $not_export)) {

                continue;
            }
            
            if (!isset($data[strval($val['Field'])])) {

                return false;
            }
        }

        return true;
    }

}