<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;

class UserGuardController extends BaseController
{

    protected $guard = 'users';

    public function __construct()
    {
        // 排除需要验证
        $this->middleware('jwt.auth:'.$this->guard, ['except' => ['login', 'mpLogin']]);
    }

    /**
     * 响应token
     *
     * @param $token
     *
     * @return mixed
     */
    protected function respondWithToken($token)
    {
        return $this->responseParseArray(
            [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth($this->guard)->factory()->getTTL() * 60,
            ]
        );
    }

    /**
     * 非游客身份验证
     */
    protected function notVisitorIdentityVerify()
    {
        $info = auth($this->guard)->user();
        if ($info->identity != 0) {
            return $this->response->error('非游客身份', 403);
        }
    }

    /**
     * 非摄影师身份验证
     */
    protected function notPhotographerIdentityVerify()
    {
        $info = auth($this->guard)->user();
        if ($info->identity != 1) {
            return $this->response->error('非摄影师身份', 403);
        }
    }
}
