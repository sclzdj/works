<?php

namespace App\Http\Controllers\Api\Notify;

use App\Http\Controllers\Api\BaseController;

use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\CrowdFundingOrder;
use App\Model\Index\InvoteCode;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Log;

// 小程序支付回调
class MiniProgramController extends BaseController
{
    /*
     * 众筹回调函数
     *
     * @return string
     */
    public function crowdfunding(Request $request)
    {
        $this->miniConfig = [
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
        ];
        $app = app('wechat.official_account');
        $miniProgram = Factory::payment($this->miniConfig);
        $response = $miniProgram->handlePaidNotify(
            function ($message, $fail) use ($miniProgram, $app) {
                // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
                $orderInfo = CrowdFundingOrder::where("order_trade_no", $message['out_trade_no'])
                    ->first();

                if (empty($orderInfo)) {
                    return true;
                }

                if ($orderInfo->notify)  // 如果订单不存在 或者 订单已经支付过了
                {
                    return true;
                } // 告诉微信，我已经处理完了，订单没找到，别再通知我了

                $queryTradeInfo = $miniProgram->order->queryByOutTradeNumber($message['out_trade_no']);
                if ($queryTradeInfo['return_code'] != "SUCCESS") {
                    Log::info('通信失败，请稍后再通知我'.$queryTradeInfo['return_msg']);
                }

                if ($queryTradeInfo['return_code'] == "SUCCESS" && $queryTradeInfo['trade_state'] == "SUCCESS") {
                    $orderInfo->transaction_id = $queryTradeInfo['transaction_id'];// 更新支付时间为当前时间
                }

                if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                    // 用户是否支付成功
                    if (array_get($message, 'result_code') === 'SUCCESS') {
                        // 生成二维码
                        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
                        $invoteCode = new InvoteCode();
                        $invoteCode->code = substr(
                            $orderInfo->user_id.$orderInfo->id.substr(
                                str_shuffle($strs),
                                mt_rand(0, strlen($strs) - 11),
                                3
                            ).mt_rand(0, 9999),
                            0,
                            6
                        );
                        $invoteCode->type = 1;
                        $invoteCode->status = 0;
                        $invoteCode->user_id = $orderInfo->user_id;
                        $invoteCode->order_id = $orderInfo->id;
                        $invoteCode->created_at = date('Y-m-d H:i:s');
                        $invoteCode->save();
                        $typeArr = [
                            1 => 99,
                            2 => 399,
                            3 => 599,
                        ];
                        $key = "data_".$typeArr[$orderInfo->type];
                        // 增加具体数据
                        CrowdFunding::increValue($key, 1);
                        CrowdFunding::where('id', 1)
                            ->increment($key, 1);
                        // 总数增加
                        $totalPrice = CrowdFunding::getKeyValue("total_price");
                        CrowdFunding::ResetValue("total_price", ($totalPrice ?? 0) + (1 * $typeArr[$orderInfo->type]));
                        CrowdFunding::where('id', 1)
                            ->increment("total_price", $typeArr[$orderInfo->type]);

                        // 增加参与人数
                        CrowdFunding::increValue("total", 1);
                        CrowdFunding::where('id', 1)
                            ->increment("total", 1);

                        $orderInfo->updated_at = date('Y-m-d H:i:s'); // 更新支付时间为当前时间
                        $orderInfo->pay_status = 1;
                        $orderInfo->notify = 1;

                        $userInfo = User::where('id', $orderInfo->user_id)->first();
                        if ($userInfo->gh_openid) {
                            $nickeName = $userInfo->is_wx_authorize == 1 ? $userInfo->nickname : '亲';
                            $tmr = $app->template_message->send(
                                [
                                    'touser' => $userInfo->gh_openid,
                                    'template_id' => '27lQ_hHMeYWzB5NYddMbpcfCZHyx24_sBNKOcb2E7Nw',
                                    'url' => config('app.url'),
                                    'miniprogram' => [
                                        'appid' => config('wechat.payment.default.app_id'),
                                        'pagepath' => '/subPage/crouwdPay/crouwdPay',
                                    ],
                                    'data' => [
                                        'first' => $nickeName.'，感谢你对云作品团队的信任！我们将于2019年11月18日，通过公众号向你推送云作品注册码。备注：云作品微信客服 JUSHEKEJI。',
                                        'keyword1' => '云作品众筹',
                                        'keyword2' => "成功",
                                    ],
                                ]
                            );
                            Log::error(json_encode($tmr, JSON_UNESCAPED_UNICODE));
                        }
                        if ($userInfo->purePhoneNumber != '') {
                            //发送短信
                            $third_type = config('custom.send_short_message.third_type');
                            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
                            if ($third_type == 'ali') {
                                $crowdFunding=CrowdFunding::find(1);
                                $send_date=date('Y年m月d日',$crowdFunding->send_date);
                                AliSendShortMessageServer::quickSendSms(
                                    $userInfo->purePhoneNumber,
                                    $TemplateCodes,
                                    'crowd_raising_result',
                                    [
                                        'nickname' => $userInfo->is_wx_authorize == 1 ? $userInfo->nickname : '亲',
                                        'date' => $send_date,
                                    ]
                                );
                            }
                        }

                        CrowdFundingLog::where(
                            [
                                'user_id' => $orderInfo->user_id,
                            ]
                        )->update(
                            [
                                'crowd_status' => 1,
                                'order_id' => $orderInfo->id,
                            ]
                        );

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
            }
        );

        $response->send();
    }

}
