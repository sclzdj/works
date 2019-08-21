<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\UserRequest;

class MyController extends UserGuardController
{
    /**
     * 用户信息保存
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function save(UserRequest $request)
    {
        $user = auth($this->guard)->user();
        $user->nickname = $request->nickname;
        if ($request->avatar !== null) {
            $user->avatar = $request->avatar;
        }
        if ($request->gender !== null) {
            $user->gender = $request->gender;
        }
        if ($request->province !== null) {
            $user->province = $request->province;
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
        return $this->response()->item(auth($this->guard)->user());
    }
}
