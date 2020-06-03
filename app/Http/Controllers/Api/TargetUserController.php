<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\TargetUserRequest;
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
class TargetUserController extends UserGuardController
{
    public $data = [
        'result' => false,
    ];

    public function insert(TargetUserRequest $request)
    {
        $user = auth($this->guard)->user();

        $target_user = (new TargetUser())->where('user_id', $user->id)->first();

        if (empty($target_user)) {
            $target_user = new TargetUser();
            $target_user->source = $request['source'];
            $target_user->invote_code_id = InvoteCode::createInvote();
            $target_user->user_id = $user->id;
            $target_user->wechat = $request['wechat'] ?? '';
            $target_user->address = $request['address'] ?? '';
            $target_user->phone_code = $request['phone_code'] ?? '';
            $target_user->works_info = isset($request['works_info']) ? json_encode($request['works_info'], 1) : '';
            $target_user->reason = $request['reason'] ?? '';
            $target_user->last_name = $request['last_name'] ?? '';
            $target_user->rank_id = $request['rank_id'] ?? 0;
            $target_user->status = 0;
            $result = $target_user->save();
        } else {
            $target_user->wechat = $request['wechat'] ?? '';
            $target_user->address = $request['address'] ?? '';
            $target_user->phone_code = $request['phone_code'] ?? '';
            $target_user->works_info = isset($request['works_info']) ? json_encode($request['works_info'], 1) : '';
            $target_user->reason = $request['reason'] ?? '';
            $target_user->last_name = $request['last_name'] ?? '';
            $target_user->rank_id = $request['rank_id'] ?? 0;
            $result = $target_user->save();
        }

        if ($result) {
            $data = [
                'result' => true,
                'msg' => '请求完成'
            ];
            return $this->responseParseArray($data);
        } else {
            $data = [
                'result' => false,
                'msg' => '请求失败'
            ];
            return $this->responseParseArray($data);
        }

    }


}
