<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

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
            $result = $app->qrcode->temporary('work_web_login_qrcode', 3600);
            $data["qrcode_url"] = $app->qrcode->url($result['ticket']);
            $data["expried_at"] = date("Y-m-d H:i:s", time() + $result['expire_seconds']);
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

        $contents = $response->getBodyContents();

        echo $contents;
        exit();
    }
}
