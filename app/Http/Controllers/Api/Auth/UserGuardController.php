<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Model\Index\BaiduOauth;

class UserGuardController extends BaseController
{

    protected $guard = 'users';

    public function __construct()
    {
        parent::__construct();
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

    /**
     * 获取accesstoken
     * @return mixed
     */
    protected function _getBaiduAccessToken()
    {
        $access_token = BaiduOauth::where(
            [
                ['user_id', '=', auth($this->guard)->id()],
                ['expired_at', '>', date('Y-m-d H:i:s')],
            ]
        )->value('access_token');
        if (!$access_token) {
            return $this->response->error('百度网盘未授权或者授权过期', 500);
        }

        return $access_token;
    }
}
