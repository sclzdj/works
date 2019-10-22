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


        // 如果是后台创建的验证码，第一次查询的时候做一下绑定,前提这个账户没有绑定过邀请码
        if ($codeInfo->type == 2 &&
            empty($codeInfo->user_id) &&
            InvoteCode::where('user_id' , $userInfo->user_id)->get()->IsEmpty()
        ) {
            InvoteCode::where('code', $code)->update([
                "user_id" => $userInfo->user_id
            ]);

            $this->data['result'] = true;
            $this->data['msg'] = "邀请码可以使用";
            return $this->responseParseArray($this->data);
        }


        if (empty($codeInfo) || $codeInfo->status != 0) {
            $this->data['msg'] = "邀请码不可用";
        } else if ($codeInfo && $codeInfo->wechat_openid != $userInfo->gh_openid) {
            $this->data['msg'] = "邀请码绑定不正确不可用";
        } else {
            $this->data['result'] = true;
            $this->data['msg'] = "邀请码可以使用";
        }

        return $this->responseParseArray($this->data);
    }

}
