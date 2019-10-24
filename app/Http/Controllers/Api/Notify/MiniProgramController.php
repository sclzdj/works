<?php

namespace App\Http\Controllers\Api\Notify;

use App\Http\Controllers\Api\BaseController;

use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingOrder;
use App\Model\Index\InvoteCode;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Log;

class MiniProgramController extends BaseController
{
    public function crowdfunding(Request $request)
    {
        $this->config = [
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
        ];
        $miniProgram = Factory::payment($this->config);
        $response = $miniProgram->handlePaidNotify(function ($message, $fail) use ($miniProgram) {
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $orderInfo = CrowdFundingOrder::where("order_trade_no", $message['out_trade_no'])
                ->first();

            if (empty($orderInfo))
                return true;

            if ($orderInfo->notify)  // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了

            $queryTradeInfo = $miniProgram->order->queryByOutTradeNumber($message['out_trade_no']);
            if ($queryTradeInfo['return_code'] != "SUCCESS") {
                Log::info('通信失败，请稍后再通知我' . $queryTradeInfo['return_msg']);
            }

            if ($queryTradeInfo['return_code'] == "SUCCESS" && $queryTradeInfo['trade_state'] == "SUCCESS") {
                $orderInfo->transaction_id = $queryTradeInfo['transaction_id'];// 更新支付时间为当前时间
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    // 清空购物车
                    // 生成二维码
                    $invoteCode = new InvoteCode();
                    $invoteCode->code = substr($orderInfo->user_id . $orderInfo->id . uniqid(), 0, 6);
                    $invoteCode->type = 1;
                    $invoteCode->status = 0;
                    $invoteCode->user_id = $orderInfo->user_id;
                    $invoteCode->order_id = $orderInfo->id;
                    $invoteCode->created_at = date('Y-m-d H:i:s');
                    $invoteCode->save();
                    $typeArr = [
                        1 => 99,
                        2 => 399,
                        3 => 599
                    ];
                    $key = "data_" . $typeArr[$orderInfo->type];
                    // 增加数据
                    CrowdFunding::increValue($key, 1);
                    CrowdFunding::where('id', 1)
                        ->increment($key, 1);

                    $totalPrice = CrowdFunding::getKeyValue("total_price");
                    CrowdFunding::ResetValue("total_price", ($totalPrice ?? 0) + (1 * $typeArr[$orderInfo->type]));

                    $orderInfo->updated_at = date('Y-m-d H:i:s'); // 更新支付时间为当前时间
                    $orderInfo->pay_status = 1;
                    $orderInfo->notify = 1;
                    // 用户支付失败
                } elseif (array_get($message, 'result_code') === 'FAIL') {
                    $orderInfo->pay_status = 2;
                    $orderInfo->notify = 1;
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            $orderInfo->save(); // 保存订单
            return true; // 返回处理完成
        });

        $response->send();
    }

}
