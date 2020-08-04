<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Log;
use Cookie;

/**
 * 微信控制器
 * @package App\Http\Controllers\Api
 * @author jsyzchenchen@gmail.com
 * @date 2020/07/14
 */
class WechatController extends BaseController
{
    /**
     * 获取微信公众号的二维码
     * @param Request $request
     * @return mixed
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/14
     */
    public function QRCode(Request $request)
    {
        $scene_type = $request->input('scene_type', 0);
        $data = array();

        if ($scene_type == 1) {//web端登录时使用的二维码
            $app = app('wechat.official_account');

            $qrcodeLoginToken = uniqid("", true);
            $timeout = 3600;
            $result = $app->qrcode->temporary('qrcode_login_' .  $qrcodeLoginToken, $timeout);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->response->error('wechat return failed: '. $result['errmsg'], 500);
            }
            $data["qrcode_url"] = $app->qrcode->url($result['ticket']);
            $data["expried_at"] = date("Y-m-d H:i:s", time() + $result['expire_seconds']);
            $data["qrcode_login_token"] = $qrcodeLoginToken;

            //在客户端设置Cookie
            cookie("QRCODE_LOGIN_TOKEN", $qrcodeLoginToken, $timeout);
        }

        return $this->responseParseArray($data);
    }

    /**
     * 获取微信小程序的二维码
     * @param Request $request
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/14
     */
    public function miniProgramQRCode(Request $request)
    {
        $path = $request->input("path", "");
        $width = $request->input("width", 430);

        $app = app('wechat.mini_program');

        $response = $app->app_code->getUnlimit('miniprogram_qrcode', [
            'page'  => $path,
            'width' => $width,
        ]);

//        if (isset($response['errcode']) && $response['errcode'] != 0) {
//            Log::warning("gen miniProgramQRCode failed, wechat return:" . $response['errmsg']);
//            return $this->response->error('wechat return failed: '. $response['errmsg'], 500);
//        }

        $contents = $response->getBodyContents();

        echo $contents;
        exit();
    }
}
