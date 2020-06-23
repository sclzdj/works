<?php

namespace App\Http\Controllers\Index\Oauth;

use App\Http\Controllers\Controller;
use App\Model\Index\User;
use EasyWeChatComposer\EasyWeChat;
use Illuminate\Http\Request;

/**
 * 邀请码
 * Class BaiduController
 * @package App\Http\Controllers\Index\Oauth
 */
class ServiceController extends Controller
{

    public function __construct()
    {
    }

    /**
     * 邀请码验证页面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权微信用户资料
        $gh_openid = $wx_user->getId();
        $data['userId'] = $request->input('userId', "empty");
        $data['openId'] = $gh_openid;

        $user = \App\Model\Index\User::where('id', $data['userId'])->first();
        if ($user && empty($user->gh_openid)) {
            \App\Model\Index\User::where('id', $data['userId'])->update(['gh_openid' => $gh_openid]);
        }
        // jssdk
        $app = app('wechat.official_account');

        return view('/index/oauth/service/index', compact('app' , 'data'));
    }
}
