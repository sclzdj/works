<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;

class UserGuardController extends BaseController {

    protected $guard = 'users';

    public function __construct() {
        // 排除需要验证
        $this->middleware('jwt.auth:'.$this->guard, ['except' => ['login','mpLogin']]);
    }

    /**
     * 响应token
     *
     * @param $token
     *
     * @return mixed
     */
    protected function respondWithToken($token) {
        return $this->responseParseArray([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth($this->guard)->factory()->getTTL() * 60,
          ]
        );
    }
}
