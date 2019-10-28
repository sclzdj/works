<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use App\Model\Index\CrowdFundingOrder;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Validator;
use function Stringy\create;

/**
 * 众筹相关
 * Class CrowdFundingController
 * @package App\Http\Controllers\Api
 */
class CrowdFundingController extends UserGuardController
{
    public $data = [
        'result' => false,
        'status' => false
    ];
    // 众筹使用的档位
    protected $type = [
        1 => 99,
        2 => 399,
        3 => 599
    ];

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

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function getData()
    {
//        $crowdData = CrowdFunding::where('id', 1)
//            ->select(CrowdFunding::allowFields())
//            ->first();
        $data = [
            'amount' => CrowdFunding::getKeyValue('amount'),
            'total' => CrowdFunding::getKeyValue('total'),
            'total_price' => CrowdFunding::getKeyValue('total_price'),
            'target' => CrowdFunding::getKeyValue('target'),
            'complete_rate' => CrowdFunding::getKeyValue('complete_rate'),
            'data_99' => CrowdFunding::getKeyValue('data_99'),
            'data_399' => CrowdFunding::getKeyValue('data_399'),
            'data_599' => CrowdFunding::getKeyValue('data_599'),
            'limit_99' => CrowdFunding::getKeyValue('limit_99'),
            'limit_399' => CrowdFunding::getKeyValue('limit_399'),
            'limit_599' => CrowdFunding::getKeyValue('limit_599'),
            'start_date' => CrowdFunding::getKeyValue('start_date'),
            'end_date' => CrowdFunding::getKeyValue('end_date'),
            'send_date' => CrowdFunding::getKeyValue('send_date'),
        ];

        $data['complete_rate'] = sprintf("%.2f", ($data['total_price'] / $data['amount']) * 100);

        return $this->responseParseArray($data);
    }

    /**
     * 下单支付接口
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function order(Request $request)
    {
        $type = $request->input('type');
        if (!in_array($type, array_keys($this->type))) {
            $this->data['msg'] = "选择的档位不存在";
            return $this->responseParseArray($this->data);
        }

        $pureData = CrowdFunding::getKeyValue('data_' . $this->type[$type]);
        $limitData = CrowdFunding::getKeyValue('limit_' . $this->type[$type]);
        if ($limitData <= $pureData) {
            $this->data['msg'] = "选择的档位已经满员";
            return $this->responseParseArray($this->data);
        }
        // 准备数据做小程序预支付单
        $user = auth($this->guard)->user();
        if (empty($user)) {
            $this->data['msg'] = "账户不存在";
            return $this->responseParseArray($this->data);
        }

        $orderQueryResult = CrowdFundingOrder::where([
            'user_id' => $user->id,
            'pay_status' => 1,
            'notify' => 1
        ])->get();

        if (!$orderQueryResult->isEmpty()) {
            $this->data['msg'] = "您已经参与了众筹";
            return $this->responseParseArray($this->data);
        }

        $order_trade_no = substr(uniqid(), 0, 5) . time() . $user->id;
        $miniProgram = Factory::payment($this->config);
        $result = $miniProgram->order->unify([
            'body' => '购买云作品',
            'out_trade_no' => $order_trade_no,
            'total_fee' => 1,

            'notify_url' => config('app.url') . '/api/notify/miniprogram/crowdfunding', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $user->openid,
        ]);

        if (!isset($result['prepay_id'])) {
            $this->data['msg'] = $result['return_msg'];
            return $this->responseParseArray($this->data);
        }

        // 创建支付单成功插入数据库
        CrowdFundingOrder::insert([
            'user_id' => $user->id,
            'order_trade_no' => $order_trade_no,
            'pay_status' => 0,
            'type' => $type,
            'notify' => 0,
            'price' => 100
        ]);
        // 给小程序生成签名
        $result['timeStamp'] = time();
        $result['paySign'] = $this->generateSign($result);

        $this->data['result'] = true;
        $this->data['data'] = $result;
        $this->data['status'] = true;
        return $this->responseParseArray($this->data);
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

    /*
     * 记录用户手机号和相关信息
     *
     * @param string $method
     * @param array  $args
     *
     * @return \EasyWeChat\Support\Collection
     */
    public function log(Request $request)
    {
        $user = auth($this->guard)->user();
        if (empty($user)) {
            $this->data['msg'] = "账户不存在";
            return $this->responseParseArray($this->data);
        }

        $crowFudinglog = CrowdFundingLog::firstOrCreate([
            'user_id' => $user->id,
            'phone' => 'phone',
            'crowd_status' => 0,
            'type' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($crowFudinglog->save()) {
            $this->data['result'] = true;
            $this->data['msg'] = "插入完成";
        } else {
            $this->data['msg'] = "未插入成功";
        }
        return $this->responseParseArray($this->data);
    }

}
