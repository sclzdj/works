<?php

namespace App\Http\Controllers\Index\Oauth;

use App\Http\Controllers\Controller;
use App\Model\Index\User;

/**
 * 百度相关控制器
 * Class BaiduController
 * @package App\Http\Controllers\Index\Oauth
 */
class BaiduController extends Controller
{
    /**
     * 网盘授权
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pan()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $gh_openid = $wx_user->getId();
        //获取unionid必须把授权配置项改为snsapi_userinfo
        //$wx_user=$wx_user->toArray();
        //$unionid=$user['original']['unionid'];die($unionid);
        $user = User::where(['gh_openid' => $gh_openid])->first();
        $user_id = $user->id;

        return view('/index/oauth/baidu/pan', compact('user_id'));
    }
}
