<?php

namespace App\Http\Controllers\Index\Oauth;

use App\Http\Controllers\Controller;

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
        $user_id = \Request::get('user_id');

        return view('/index/oauth/baidu/pan', compact('user_id'));
    }
}
