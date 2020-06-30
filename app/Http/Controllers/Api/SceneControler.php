<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Model\Index\InvoteCode;
use App\Model\Index\RecodeScence;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;


class SceneControler extends UserGuardController
{
    private $data;
    public function inRecord(Request $request)
    {
        $user = auth($this->guard)->user();

        $validateRequest = Validator::make(
            $request->all(), [
            'scene' => 'required|',
        ], [
            'scene' => [
                'required' => '场景值必须填写',
            ],
        ]);
        if ($validateRequest->fails()) {
            $msg = $validateRequest->errors()->all();
            $this->data['msg'] = array_shift($msg);
            return $this->responseParseArray($this->data);
        }

        $result = RecodeScence::insert([
            'user_id' => $user->id,
            'scene' => $request->input('scene'),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if (empty($result)) {
            $this->data['result'] = false;
            $this->data['msg'] = "添加失败";
            return $this->responseParseArray($this->data);
        } else {
            $this->data['result'] = true;
            $this->data['msg'] = "添加成功";
            return $this->responseParseArray($this->data);
        }

    }

}
