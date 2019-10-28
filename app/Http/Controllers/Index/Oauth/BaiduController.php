<?php

namespace App\Http\Controllers\Index\Oauth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Index\SystemRequest;
use App\Model\Index\BaiduOauth;
use App\Model\Index\User;
use Illuminate\Support\Facades\Request;

/**
 * 百度相关控制器
 * Class BaiduController
 * @package App\Http\Controllers\Index\Oauth
 */
class BaiduController extends Controller
{
    /**
     * 网盘授权回调
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pan()
    {
        $url = action('Index\Oauth\BaiduController@panStore');

        return view('/index/oauth/baidu/pan', compact('url'));
    }

    /**
     * 网盘授权保存
     * @param \Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     * @throws \Exception
     */
    public function panStore()
    {
        $request = Request::all();
        if (empty($request['access_token'])) {
            return abort(500, '缺少access_token参数');
        }
        if (empty($request['expires_in'])) {
            return abort(500, '缺少expires_in参数');
        }
        \DB::beginTransaction();//开启事务
        try {
            $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
            $gh_openid = $wx_user->getId();
            //获取unionid必须把授权配置项改为snsapi_userinfo
            //$wx_user=$wx_user->toArray();
            //$unionid=$user['original']['unionid'];die($unionid);
            $user = User::where(['gh_openid' => $gh_openid])->first();
            if (!$user) {
                return abort(500, '该用户没有将公众号openid存在数据库中');
            }
            $expires_in = $request['expires_in'];
            $expired_at = date('Y-m-d H:i:s', time() + $expires_in);
            $baidu_oauth = BaiduOauth::where(['user_id' => $user->id])->first();
            if (!$baidu_oauth) {
                $baidu_oauth = BaiduOauth::create();
                $baidu_oauth->user_id = $user->id;
            }
            $baidu_oauth->access_token = $request['access_token'];
            $baidu_oauth->expired_at = $expired_at;
            $baidu_oauth->save();
            \DB::commit();//提交事务

            return view('/index/oauth/baidu/pan_store');
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return abort(500, $e->getMessage());
        }
    }
}
