<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\UserRequest;
use App\Model\Index\User;
use App\Servers\ArrServer;

class MyController extends UserGuardController
{
    /**
     * 用户信息保存
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function saveInfo(UserRequest $request)
    {
        $user = auth($this->guard)->user();
        $user->nickname = $request->nickname;
        if ($request->avatar !== null) {
            $user->avatar = $request->avatar;
        }
        if ($request->gender !== null) {
            $user->gender = $request->gender;
        }
        if ($request->country !== null) {
            $user->country = $request->country;
        }
        if ($request->province !== null) {
            $user->province = $request->province;
        }
        if ($request->city !== null) {
            $user->city = $request->city;
        }
        $user->save();

        return $this->response->noContent();
    }
    /**
     * 刷新token
     *
     * @return mixed
     */
    public function refresh()
    {
        return $this->respondWithToken(auth($this->guard)->refresh());
    }

    /**
     * 退出
     *
     * @return \Dingo\Api\Http\Response
     */
    public function logout()
    {
        auth($this->guard)->logout();

        return $this->response()->noContent();
    }

    /**
     * 我的资料
     *
     * @return \Dingo\Api\Http\Response
     */
    public function info()
    {
        $info=auth($this->guard)->user()->toArray();
        $info=ArrServer::inData($info,User::allowFields());
        return $this->responseParseArray($info);
    }
}
