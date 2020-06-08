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
        try {
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
                $this->data['msg'] = "创建码错误";
                return $this->responseParseArray($this->data);
            }


            switch ($codeInfo->type) {
                case 1:  // 用户创建的邀请码只有通过众筹来的才是这个状况

                    if ($codeInfo != 1) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "创建码不可用";
                    }

                    if ($codeInfo->user_id == $userInfo->id) {
                        $this->data['result'] = true;
                        $this->data['msg'] = "创建码可以使用";
                        InvoteCode::where('code', $code)->update([
                            'is_use' => 1,
                            'status' => 2,
                        ]);
                    }

                    return $this->responseParseArray($this->data);

                    break;
                case 2:  // 目标用户 （邀请是后台创建 ，码状态是0   ）

                    if ($codeInfo->status != 0) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "创建码不可用";
                    }

                    if ($codeInfo->user_id) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "创建码已经被使用过";
                    }

                    if (InvoteCode::where('user_id', $userInfo->id)->get()) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "已经绑定过创建码";
                    }

                    if (empty($codeInfo->user_id) && InvoteCode::where('user_id', $userInfo->id)->get()->IsEmpty()
                    ) {
                        InvoteCode::where('code', $code)->update([
                            "user_id" => $userInfo->id,
                            'is_use' => 1,
                            'status' => 2
                        ]);
                        $this->data['result'] = true;
                        $this->data['msg'] = "创建码可以使用";
                    }

                    return $this->responseParseArray($this->data);

                    break;
                case 3: //也就是活动来的，这个码状态直接是1，而且已经绑定了

                    if ($codeInfo->status != 1) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "创建码不可用";
                    }

                    if ($codeInfo->user_id == $userInfo->id) {
                        $this->data['result'] = true;
                        $this->data['msg'] = "创建码可以使用";
                        InvoteCode::where('code', $code)->update([
                            'is_use' => 1,
                            'status' => 2,
                        ]);
                    }

                    return $this->responseParseArray($this->data);


                    break;

                default:
                    $this->data['msg'] = "邀请码不可用";
                    return $this->responseParseArray($this->data);
                    break;
            }
        } catch (\Exception $exception) {

            $this->data['result'] = true;
            $this->data['msg'] = $exception->getMessage();
            return $this->responseParseArray($this->data);
        }

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
            $this->data['msg'] = "创建码错误";
            return $this->responseParseArray($this->data);
        }

        if ($codeInfo->status != 2) {
            $this->data['msg'] = "邀请码没有经过校验";
            return $this->responseParseArray($this->data);
        }

        $result = InvoteCode::where('code', $code)->where('user_id', $userid)->update([
            'status' => 4
        ]);

        if ($result) {
            $this->data['msg'] = "更改成功";
            $this->data['result'] = true;
            return $this->responseParseArray($this->data);
        }

        $this->data['msg'] = "更改失败";
        return $this->responseParseArray($this->data);
    }

    /**
     * 查询是否用过邀请码
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function used(Request $request)
    {
        $info = auth($this->guards['user'])->user();
        if (empty($info)) {
            $this->data['result'] = false;
            $this->data['msg'] = "账户不存在";
            return $this->responseParseArray($this->data);
        }

        $code = InvoteCode::where('user_id', $info->id)
            ->where('status', 2)
            ->get();

        if ($code->isEmpty()) {
            $this->data['result'] = false;
            $this->data['msg'] = "未使用过创建码";
            return $this->responseParseArray($this->data);
        } else {
            $this->data['result'] = true;
            $this->data['msg'] = "使用过创建码";
            return $this->responseParseArray($this->data);
        }
    }

}
