<?php

namespace App\Http\Controllers\Api;

use App\Model\Index\InvoteCode;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class InvoteCodeController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function __construct()
    {

    }

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function query(Request $request)
    {
        $validateRequest = Validator::make(
            $request->all(), [
            'code' => 'required|alpha_num|size:6',
        ], [
            'code' => [
                'required' => '邀请码必传',
                'alpha_num' => '只能是英文或数字组成',
                'size' => '6位英文或字母组成'
            ],
        ]);
        if ($validateRequest->fails()) {
            $msg = $validateRequest->errors()->all();
            $this->data['msg'] = array_shift($msg);
            return $this->responseParseArray($this->data);
        }

        $code = $request->input('code');
        $userid = $request->input('userid');

        $codeInfo = InvoteCode::where('code', $code)->first();
        $userInfo = User::where('id', $userid)->first();

        if (empty($userInfo)) {
            $this->data['result'] = false;
            $this->data['msg'] = "用户不存在";
            return $this->responseParseArray($this->data);
        }
        if (empty($codeInfo)) {
            $this->data['result'] = false;
            $this->data['msg'] = "邀请码不存在";
            return $this->responseParseArray($this->data);
        }
        // 如果是后台创建的验证码，第一次查询的时候做一下绑定,前提这个账户没有绑定过邀请码
        if ($codeInfo->type == 2 &&
            empty($codeInfo->user_id) &&
            InvoteCode::where('user_id', $userInfo->id)->get()->IsEmpty()
        ) {
            InvoteCode::where('code', $code)->update([
                "user_id" => $userInfo->id
            ]);
            $this->data['result'] = true;
            $this->data['msg'] = "邀请码可以使用";
            return $this->responseParseArray($this->data);
        }

        if ($codeInfo->status != 0) {
            $this->data['msg'] = "邀请码不可用";
        }

        if ($codeInfo->user_id == $userInfo->id) {
            $this->data['result'] = true;
            $this->data['msg'] = "邀请码可以使用";
            return $this->responseParseArray($this->data);
        }

        $this->data['msg'] = "邀请码不可用";
        return $this->responseParseArray($this->data);
    }

    /**
     * 更改邀请码状态
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function update(Request $request)
    {

        $validateRequest = Validator::make(
            $request->all(), [
            'code' => 'required|alpha_num|size:6',
        ], [
            'code' => [
                'required' => '邀请码必传',
                'alpha_num' => '只能是英文或数字组成',
                'size' => '6位英文或字母组成'
            ],
        ]);
        if ($validateRequest->fails()) {
            $msg = $validateRequest->errors()->all();
            $this->data['msg'] = array_shift($msg);
            return $this->responseParseArray($this->data);
        }

        $code = $request->input('code');
        $userid = $request->input('userid');

        $codeInfo = InvoteCode::where('code', $code)->where('user_id', $userid)->first();
        $userInfo = User::where('id', $userid)->first();
        if (empty($codeInfo) || empty($userInfo)) {
            $this->data['msg'] = "邀请码不存在";
            return $this->responseParseArray($this->data);
        }

        if ($codeInfo->status != 0) {
            $this->data['msg'] = "邀请码状态不正确";
            return $this->responseParseArray($this->data);
        }

        $result = InvoteCode::where('code', $code)->where('user_id', $userid)->update([
            'status' => 2
        ]);

        if ($result) {
            $this->data['msg'] = "更改成功";
            $this->data['result'] = true;
            return $this->responseParseArray($this->data);
        }

        $this->data['msg'] = "更改失败";
        return $this->responseParseArray($this->data);
    }

}
