<?php

namespace app\index\model;

use think\facade\Db;

/**
 * 事务管理
 */
class TransManager
{
    /**
     * 事务次数
     *
     * @var integer
     */
    public static $TRANS_NUM = 0;

    /**
     * 事务开始
     *
     * @return void
     */
    public static function startTrans()
    {
        Db::startTrans();
        self::$TRANS_NUM = self::$TRANS_NUM + 1;
    }

    /**
     * 事务提交
     *
     * @return void
     */
    public static function commit()
    {
        if (self::$TRANS_NUM > 0) {

            Db::commit();
            self::$TRANS_NUM = self::$TRANS_NUM - 1;
        }
    }

    /**
     * 事务回滚
     *
     * @return void
     */
    public static function rollback()
    {
        if (self::$TRANS_NUM > 0) {

            Db::rollback();
            self::$TRANS_NUM = self::$TRANS_NUM - 1;
        }
    }

    /**
     * 事务出错回滚
     *
     * @return void
     */
    public static function onAppError()
    {
        self::rollback();
    }
}
