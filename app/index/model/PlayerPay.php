<?php

namespace app\index\model;

use app\common\model\TimeModel;

/**
 * 支付执行模型
 */
class PlayerPay extends TimeModel
{

    /**
     * 执行充值接口
     *
     * @return array
     */
    public function executePayInfo($money = 0): array
    {
        if (empty($this->uid)) {

            throw new \Exception('执行充值接口失败，未定义的ID', 1);
        }

        try {

            $secarr = getTmSecKey([
                'money'      => $money,
                'userpms'    => $this->uid,
                'pay_key'    => $this->product_id, //支付的 产品ID
                'order_id'   => $this->order_id, //订单信息
                'detail_id'  => $this->cfg_id,
                'move_type'  => 'ios',
                'extra_info' => $this->extra_info,
            ]);
    
            // 请求服务器配置
            $url      = $this->server . '/' . CfgConst::ApiPay;
            $url_data = getHttp($url, $secarr);
    
            if ($url_data['code'] == 0) {
    
                // 更改支付状态
                $this->isPaid = 1;
            }

            return $url_data;
            
        } catch (\Exception $e) {
            
            // 更改支付状态
            $this->isPaid = $e->getCode();
            LogError($e); // 写入报错日志

            return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        }
    }


}
