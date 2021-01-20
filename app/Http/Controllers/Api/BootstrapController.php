<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Model\Index\Bootstrap;
use App\Model\Index\InvoteCode;
use App\Model\Index\User;
use Illuminate\Http\Request;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class BootstrapController extends UserGuardController
{
    protected $types = [
        0 => 'home',
        1 => 'work',
        2 => 'user',
        3 => 'relation',
        4 => 'storage',
        5 => 'clear',
        6 => 'preview'
    ];

    public function query(Request $request)
    {
        $type = $request->input('type', 0);
        $userInfo = auth($this->guard)->user();
        $types = $this->types[$type] ?? 'error';
        if ($types == "error")
            return ['result' => false, 'msg' => '类型不存在'];
        if ($types == "clear") {
            (new Bootstrap())->where('user_id' , $userInfo->id)->update([
                'home' => 0,
                'work' => 0,
                'user' => 0,
                'relation' => 0,
                'storage' => 0,
            ]);
            return ['result' => true, 'msg' => '清除完成'];
        }

        $isExist = Bootstrap::where([
            'user_id' => $userInfo->id,
        ])->first();

        if (empty($isExist)) {
            $bootstrap = new Bootstrap();
            $bootstrap->user_id = $userInfo->id;
            $bootstrap->$types = 1;
            $bootstrap->save();
            return ['result' => true, 'msg' => '第一次使用'];
        }

        if ($isExist->$types == 1) {
            return ['result' => false, 'msg' => '已经使用过'];
        }

        $isExist->$types = 1;
        $isExist->save();
        return ['result' => true, 'msg' => '第一次使用'];
    }

}
