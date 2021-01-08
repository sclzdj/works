<?php
namespace App\Http\Controllers\Api;


use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Index\InviteList;
use App\Model\Index\InviteReward;
use App\Model\Index\OrderInfo;
use App\Model\Index\PayCard;
use App\Model\Index\Photographer;
use App\Model\Index\Settings;
use App\Model\Index\User;
use App\Servers\SystemServer;
use Dompdf\Exception;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Support\XML;


class PayMentController extends BaseController {

    public $config = [];

    public function __construct()
    {
        parent::__construct();
        // 初始化小程序的信息
        $this->config = [
            'app_id' => config('wechat.payment.default.app_id'),
            'mch_id' => config('wechat.payment.default.mch_id'),
            'key' => config('wechat.payment.default.key'),   // API 密钥
            'notify_url' => '',     // 你也可以在下单时单独设置来想覆盖它
        ];
    }


    public function pay(PhotographerRequest $request){
        $user = User::where(['id' => $request->user_id])->first();
        if (!$user){
            return $this->response->error('用户不存在', 500);
        }
        $miniProgram = Factory::payment($this->config);
        $order_trade_no = date('YmdHis') . SystemServer::getRandomString(6);

        $settings = Settings::first();
        $payinfo = json_decode($settings->payinfo, true);

        $money = $this->activity($user->id);
        if ($money === 0){
            $this->vipstaff($user->id);
            return $this->response()->noContent();
        }

        $orderinfo = OrderInfo::create();
        $orderinfo->pay_id = $user->id;
        $orderinfo->money = $money;
        $orderinfo->pay_no = $order_trade_no;
        $orderinfo->save();

        $result = $miniProgram->order->unify([
            'body' => '云作品个人账号（1年）',
            'out_trade_no' => $order_trade_no,
            'total_fee' => $money * 100,
            'notify_url' => config('app.url') . '/api/payment/notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $user->openid,
        ]);

        if (!isset($result['prepay_id'])) {
            return $this->response->error("请求支付失败！", 500);
        }


        // 给小程序生成签名
        $result['timeStamp'] = time();
        $result['paySign'] = $this->generateSign($result);

        return $this->responseParseArray($result);
    }

    /***
     * 这是一个活动，具有时效性。
     */
    private function activity($user_id){
        $settings = Settings::find(1);
        $info = json_decode($settings->activity1, true);
        $money = 299;
        $now = date('Y-m-d');
        $user = User::where(['id' => $user_id])->first();
        if (strtotime($now) < strtotime($info['expire_time2'])){
            $money = 199;
        }

        $paycard = PayCard::where(['photographer_id' =>  $user->photographer_id])->first();
        if ($paycard){
            if (strtotime($now) < strtotime($info['expire_time2'])){
                $money -= 149;
            }else{
                $money -= 249;
            }
        }

        $invite = InviteList::where(['photographer_id' => $user->photographer_id])->first();
        if ($invite){
            $order = OrderInfo::where(['pay_id' => $user->id, 'status' => 1])->first();
            if (!$order){
                $money = $money - 50;
            }
        }


        return $money;
    }

    public function notify(\Request $request){
        $data = file_get_contents("php://input");
        file_put_contents('/tmp/notify', $data, FILE_APPEND);

        $payment = Factory::payment($this->config);
//        $message = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';
//        if(empty($message)) $message = file_get_contents("php://input");
//        if(empty($message))  return false;
//
//        // 将xml数据解析位数组
//        $message = XML::parse($message);

        $response = $payment->handlePaidNotify(function ($message, $fail)
        {
            // 根据返回的订单号查询订单数据

            $order = OrderInfo::where(['pay_no' =>  $message['out_trade_no']])->first();
            if (!$order) {
                $fail('Order not exist.');
            }

            if($message['result_code'] === 'SUCCESS')
            {
                \DB::beginTransaction();
                try{
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->status = 1;
                    $order->save();

                    $photographer = Photographer::join('users', 'users.photographer_id', '=', 'photographers.id')->where(['users.id' => $order->pay_id])->select('photographers.*')->first();

                    if ($photographer){
                        $photographer->level = 1;

                        $photographer->vip_expiretime = date('Y-m-d H:i:s',strtotime('+1year'));

                        $photographer->save();
                    }else{
                        $fail('photographer not exist.');
                        throw new Exception("回调失败");

                    }
                    //如果不是正式用户改为正式用户，支付时新用户没有被用户邀请就自动归为官方邀请
                    $user = User::where(['id' => $order->pay_id])->first();
                    if ($user){
                        if ($user->status == 0){
                            $user->status = 1;
                        }
                        $user->identity = 1;
                        $user->save();

                    }else{
                        $fail('user  not exist.');
                        throw new Exception("回调失败");

                    }

                    //如果有邀请人，则返现
                    $invite = InviteList::where(['photographer_id' => $photographer->id ])->first();
                    if ($invite){
                        if ($message['total_fee'] / 100 >= 140) {
                            $count = OrderInfo::where(['pay_id' => $user->id, 'status' => 1])->whereRaw('money >= 140')->count();
                            //只有一次付了140之后才会返现，之后就不会返现
                            if ($count == 1){
                                $reward = InviteReward::where(['photographer_id' => $invite->parent_photographer_id])->first();
                                if ($reward){
                                    $reward->money = $reward->money + 50;
                                    $reward->money_count = $reward->money_count + 50;
                                    $reward->save();
                                }
                            }
                        }
                    }

                }catch (\Exception $e){
                    \DB::rollBack();

                    $fail('Order failed.');
                }
            }

            \DB::commit();
            return true;
        });

        return $response;

    }


    // 给小程序做支付签名
    private function generateSign($result)
    {
        $params = array();
        $params['appId'] = $result['appid'];
        $params['timeStamp'] = $result['timeStamp'];
        $params['nonceStr'] = $result['nonce_str'];
        $params['package'] = "prepay_id=" . $result['prepay_id'];
        $params['signType'] = "MD5";
        ksort($params);
        $params['key'] = $this->config['key'];
        return strtoupper(call_user_func_array('MD5', [urldecode(http_build_query($params))]));
    }

    public function cardcheck(PhotographerRequest $request){
        $card = PayCard::where(['code' => $request->code])->first();
        if (!$card){
            return $this->response->error('优惠码错误', 401);
        }

        if ($card->photographer_id !== 0){
            return $this->response->error('优惠码已失效', 401);
        }

        return $this->response->noContent();
    }

    private function vipstaff($user_id){
        $user = User::where(['id' => $user_id])->first();
        $photographer = Photographer::where(['id' => $user->photographer_id])->first();
        $now = date('Y-m-d');
        if (strtotime($now) < strtotime($photographer->vip_expiretime)){
            return false;
        }
        \DB::beginTransaction();
        try {
            $photographer->level = 3;
            $photographer->vip_expiretime = date('Y-m-d H:i:s', strtotime('+1year'));
            $photographer->save();

            //如果不是正式用户改为正式用户，支付时新用户没有被用户邀请就自动归为官方邀请
            if ($user) {
                $user->identity = 1;
                if ($user->status == 0) {
                    $user->status = 1;
                }
                $user->save();
            }
        }catch (\Exception $exception){
            \DB::rollBack();
            return false;
        }

        \DB::commit();
        return true;
    }

    public function cardpay(PhotographerRequest $request){
        $user = User::where(['id' => $request->user_id])->first();
        $photographer = Photographer::where(['id' => $user->photographer_id])->first();
        $code  = $request->code;
        $card = PayCard::where(['code' => $code])->first();
        if (!$card){
            return $this->response->error('优惠码错误', 401);
        }

        if ($card->photographer_id !== 0){
            return $this->response->error('优惠码已失效', 401);
        }

        $data['pay'] = 1;
        \DB::beginTransaction();
        try {
            $card->photographer_id = $photographer->id;
            $card->save();

            $invite = InviteList::where(['photographer_id' => $photographer->id ])->first();
            if ($invite){
                $data['pay'] = 0;
                $this->vipstaff($user->id);
            }

        }catch (\Exception $e){
            \DB::rollBack();
            return $this->response->error('兑换失败', 500);
        }

        \DB::commit();


        return $this->responseParseArray($data);


    }
}
