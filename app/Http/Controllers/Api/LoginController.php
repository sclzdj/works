<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\SystemRequest;
use App\Http\Requests\Index\UserRequest;
use App\Model\Index\User;
use Illuminate\Support\Facades\Redis;
use Log;
use Cookie;

/**
 * 登录相关
 * Class LoginController
 * @package App\Http\Controllers\Api
 */
class LoginController extends UserGuardController
{
    /**
     * 小程序登录
     *
     * @param Request $request
     *
     * @return mixed|void
     */
    public function mpLogin(UserRequest $request)
    {
          $data = $this->_wxCode2Session($request->code);
//        $data = [
//            "session_key" => 'TKg5Edd10SeX1Po+NH2y3A1==',
//            'openid' => 'oOR6g5uTkJKvRvo2g2kJoTzNal',
//        ];
        \DB::beginTransaction();//开启事务
        try {
            $user = User::where(['openid' => $data['openid']])->first();
            if (!$user) {
                $user = User::create();
                $random = str_random(10);
                $user->username = 'user_'.$random;
                $user->password = bcrypt('works123456');
                $user->nickname = '微信用户_'.$random;
                $user->remember_token = str_random(10);
                $user->openid = $data['openid'];
                $user->unionid = $data['unionid'];//用于公众号授权登录时将账号打通，add by chenchen 2020/7/14
                $userPresetCreate = User::presetCreate();
                $user->photographer_id = $userPresetCreate['photographer_id'];
                $user->save();
            }
            $user->session_key = $data['session_key'];
            $user->save();
            if (!$token = auth($this->guard)->login($user)) {
                \DB::rollback();//回滚事务

                return $this->response->error('登录失败', 422);
            }
            \DB::commit();//提交事务
            \Log::debug($token);
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }


    /**
     * 账号密码登录
     *
     * @return mixed|void
     */
    public function login(UserRequest $request)
    {
        $data = ['username' => $request->username, 'password' => $request->password];
        if (!$token = auth($this->guard)->attempt($data)) {
            return $this->response->error('帐号或密码错误', 422);
        }

        return $this->respondWithToken($token);
    }

    /**
     * 扫描二维码登录
     * @param UserRequest $request
     * @return mixed|void
     * @author jsyzchenchen@gmail.com
     * @date 2020/7/14
     */
    public function qrcodeLogin(UserRequest $request)
    {
        $qrcodeLoginToken = Cookie::get("QRCODE_LOGIN_TOKEN");
        if (empty($qrcodeLoginToken)) {
            $qrcodeLoginToken = $request->input("qrcode_login_token", "");
        }
        if (empty($qrcodeLoginToken)) {
            Log::warning("qrcode login token is empty");
            return $this->response->error('qrcode login token is empty.', 400);
        }

        //从redis里获取
        $userId = Redis::get('qrcode_login_' .  $qrcodeLoginToken);
        if ($userId === false) {
            Log::warning("redis get failed");
            return $this->response->error('qrcode_login redis get failed', 500);
        } elseif (!$userId) {
            return $this->response->error('not yet login', 400);
        }

        $user = User::find($userId);
        if (!$token = auth($this->guard)->login($user)) {
            return $this->response->error('登录失败', 422);
        }
        return $this->respondWithToken($token);
    }
}
