<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Model\Index\CrowdFunding;
use App\Model\Index\CrowdFundingLog;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Validator;
use function Stringy\create;

/**
 * 众筹相关
 * Class CrowdFundingController
 * @package App\Http\Controllers\Api
 */
class CrowdFundingController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function getData()
    {
//        $redis = new \Redis();
//        $redis->connect("127.0.0.1");
//        $redis->select(5);
        $crowdData = CrowdFunding::where('id', 1)
            ->select(CrowdFunding::allowFields())
            ->first();

        return $this->responseParseArray($crowdData);
    }

    public function order()
    {
        $user = auth($this->guard)->user();

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
