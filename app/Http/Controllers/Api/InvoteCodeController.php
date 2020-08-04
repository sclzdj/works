<?php

namespace App\Http\Controllers\Api;

use App\Model\Index\InvoteCode;
use App\Model\Index\TargetUser;
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
                    'required' => '创建码必传',
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
                $this->data['msg'] = "请退出重新输入";
                return $this->responseParseArray($this->data);
            }
            if (empty($codeInfo)) {
                $this->data['result'] = false;
                $this->data['msg'] = "创建码不存在";
                return $this->responseParseArray($this->data);
            }


            if ($codeInfo->used_count <= 0) {
                $this->data['result'] = false;
                $this->data['msg'] = "创建码可用次数不足";
                return $this->responseParseArray($this->data);
            }

            switch ($codeInfo->type) {
                case 1:  // 众筹来的才是这个状况

                    if ($codeInfo != 1) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "请关闭重新输入";
                    }

                    if ($codeInfo->user_id == $userInfo->id) {
                        $this->data['result'] = true;
                        $this->data['msg'] = "创建码可以使用";
                        InvoteCode::where('code', $code)->update([
                            'is_use' => 1,
                            'status' => 2,
                        ]);
                    } else {
                        $this->data['result'] = false;
                        $this->data['msg'] = "这枚创建码不是你的";
                    }

                    $this->checkTargetUser($codeInfo->user_id, $codeInfo->id);

                    return $this->responseParseArray($this->data);

                    break;
                case 2:  // 邀请管理来的 （邀请是后台创建 ，码状态是0   ）

                    if ($codeInfo->status != 0) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "创建码不可用";
                    }

                    if ($codeInfo->user_id) {
                        $this->data['result'] = false;
                        $this->data['msg'] = "这枚创建码不是你的";
                    }
                    // 如果用户是通过邀请管理绑定 或者 众筹又绑定了一个码会走到这里 更新这个码的使用状态
                    if (InvoteCode::where('user_id', $userInfo->id)->first()) {
                        $this->data['result'] = true;
                        $this->data['msg'] = "已经绑定过创建码";

//                        InvoteCode::where('user_id', $userInfo->id)->delete();
//                        InvoteCode::where('code', $code)->update([
//                            "user_id" => $userInfo->id,
//                            'is_use' => 1,
//                            'status' => 2
//                        ]);
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


                    $this->checkTargetUser($userInfo->id, $codeInfo->id);
                    return $this->responseParseArray($this->data);

                    break;
                case 3: //目标管理来的，这个码状态直接是1，而且已经绑定了

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
                    $this->data['msg'] = "创建码不可用";
                    return $this->responseParseArray($this->data);
                    break;
            }
        } catch (\Exception $exception) {

            $this->data['result'] = true;
            $this->data['msg'] = $exception->getMessage();
            return $this->responseParseArray($this->data);
        }

    }

    private function checkTargetUser($user_id, $code_id)
    {
        $targetUser = TargetUser::where('user_id', $user_id)->first();
        if (empty($targetUser))
            return "";

        if ($targetUser->invote_code_id != 0) {
            $originInvoteCode = $targetUser->invote_code_id;
            InvoteCode::where('id', $originInvoteCode)->delete();
            TargetUser::where('user_id', $user_id)->delete();
            \Log::error("删除:" . var_export([
                    'invote_code_id' => $originInvoteCode,
                    'user_id' => $user_id
                ], 1));
        }


//        $targetUser = TargetUser::where('user_id', $user_id)->update([
//            'invote_code_id' => $code_id
//        ]);

        return "";
    }

    /**
     * 更改邀请码状态
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function update(Request $request)
    {
//        $validateRequest = Validator::make(
//            $request->all(), [
//            'code' => 'required|alpha_num|size:6',
//        ], [
//            'code' => [
//                'required' => '创建码必传',
//                'alpha_num' => '只能是英文或数字组成',
//                'size' => '6位英文或字母组成'
//            ],
//        ]);
//        if ($validateRequest->fails()) {
//            $msg = $validateRequest->errors()->all();
//            $this->data['msg'] = array_shift($msg);
//            return $this->responseParseArray($this->data);
//        }

//        $code = $request->input('code');
        $this->data['result'] = false;
        $userid = $request->input('userid', 0);
        $userInfo = User::where('id', $userid)->first();
        $InvodeInfo = InvoteCode::where('user_id', $userid)->first();
        if (empty($InvodeInfo) || empty($userInfo)) {
            $this->data['msg'] = "用户没有绑定创建码";
            return $this->responseParseArray($this->data);

        }

        $InvodeInfo = $InvodeInfo->toArray();
        $code = $InvodeInfo['code'];

        if ($InvodeInfo['status'] == 4) {
            $this->data['msg'] = "创建码已经完成了注册";
            return $this->responseParseArray($this->data);
        }

        if ($InvodeInfo['status'] != 2) {
            $this->data['msg'] = "创建码没有经过校验";
            return $this->responseParseArray($this->data);
        }

        $result = InvoteCode::where('code', $code)->where('user_id', $userid)->update([
            'status' => 4
        ]);

        InvoteCode::where('code', $code)->where('user_id', $userid)->decrement('used_count');

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
            //->where('status', 2)
            ->get();

        if ($code->isEmpty()) {
            $this->data['result'] = true;
            $this->data['msg'] = "未使用过创建码";
            return $this->responseParseArray($this->data);
        } else {
            $this->data['result'] = false;
            $this->data['msg'] = "使用过创建码";
            return $this->responseParseArray($this->data);
        }
    }

}
