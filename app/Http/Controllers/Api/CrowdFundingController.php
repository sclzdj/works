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
    ];

    protected $type = [
        1 => 99,
        2 => 399,
        3 => 599
    ];

    public $config = [];

    public function __construct()
    {
        parent::__construct();
        $this->config = [
            'app_id' => 'wxeec7c320c3eb0477',
            'mch_id' => '1555639731',
            'key' => 'zuopinzuopinzuopinzuopinzuopinzu',   // API 密钥
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
        $crowdData = CrowdFunding::where('id', 1)
            ->select(CrowdFunding::allowFields())
            ->first();

        return $this->responseParseArray($crowdData);
    }

    public function order(Request $request)
    {
        $type = $request->input('type');

        if (!in_array($type, array_keys($this->type))) {
            $this->data['msg'] = "选择的档位不存在";
            return $this->responseParseArray($this->data);
        }

        $user = auth($this->guard)->user();
        $order_trade_no = substr(uniqid(), 0, 5) . time() . $user->id;
        $miniProgram = Factory::payment($this->config);

        $result = $miniProgram->order->unify([
            'body' => '购买云作品',
            'out_trade_no' => $order_trade_no,
            'total_fee' => 1000,
            'notify_url' => config('app.url') . '/api/notify/miniprogram/crowdfunding', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $user->openid,
        ]);

        if (!isset($result['prepay_id'])) {
            $this->data['msg'] = $result['return_msg'];
            return $this->responseParseArray($this->data);
        }

        CrowdFundingOrder::insert([
            'user_id' => $user->id,
            'order_trade_no' => $order_trade_no,
            'pay_status' => 0,
            'type' => $type,
            'notify' => 0,
            'price' => 100
        ]);
        $result['timeStamp'] = time();
        $this->data['result'] = true;
        $this->data['data'] = $result;
        return $this->responseParseArray($this->data);

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
        $validateRequest = Validator::make(
            $request->all(), [
            'code' => 'required',
            'phone' => 'required'
        ], [
            'code' => [
                'required' => 'code必传',
            ],
            'phone' => [
                'required' => '电话号码必传',
            ]
        ]);

        if ($validateRequest->fails()) {
            $msg = $validateRequest->errors()->all();
            $this->data['msg'] = array_shift($msg);
            return $this->responseParseArray($this->data);
        }

        $data = $this->_wxCode2Session($request->code);
        $crowFudinglog = CrowdFundingLog::create();
        $crowFudinglog->open_id = $data['open_id'];
        $crowFudinglog->phone = $request->phone;
        $crowFudinglog->crowd_status = 0;
        $crowFudinglog->type = 0;
        $crowFudinglog->created_at = date('Y-m-d H:i:s');

        if ($crowFudinglog->save()) {
            $this->data['result'] = true;
            $this->data['msg'] = "插入完成";
        } else {
            $this->data['msg'] = "未插入成功";
        }
        return $this->responseParseArray($this->data);
    }

}
