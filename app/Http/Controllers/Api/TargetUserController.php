<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\TargetUserRequest;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class TargetUserController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function insert(TargetUserRequest $request)
    {
//        $user = auth($this->guard)->user();
        try {

            $codeInfo = InvoteCode::where('user_id', $request['user_id'])->first();
            if (!empty($codeInfo)) {
                $data = [
                    'result' => false,
                    'msg' => '用户绑定了创建码，不再是目标用户'
                ];
                return $this->responseParseArray($data);
            }

            $target_user = (new TargetUser())->where('user_id', $request['user_id'])->first();
//            $Photographer = Photographer::where('id', $request['user_id'])->first();
//            if (!empty($Photographer)) {
//                $data = [
//                    'result' => false,
//                    'msg' => '用户已经是摄影师,不在是目标用户'
//                ];
//                return $this->responseParseArray($data);
//            }

            if (empty($target_user)) {
                $target_user = new TargetUser();
                $target_user->source = $request['source'];
                $target_user->invote_code_id = 0;
                $target_user->user_id = $request['user_id'];
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

                if ($target_user->source < $request['source'] && $target_user->source < 4) {
                    $data = [
                        'result' => false,
                        'msg' => '用户已经提交过更高的来源'
                    ];
                    return $this->responseParseArray($data);
                }

                $target_user->source = $request['source'] ?? 0;
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
        } catch (\Exception $exception) {
            $data = [
                'msg' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'code' => $exception->getLine(),
            ];
            return $this->responseParseArray($data);
        }

    }


}
