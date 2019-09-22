<?php


namespace App\Servers;


use Carbon\Carbon;

class WechatServer
{
    /**
     * 获取小程序的token
     * @return mixed|null
     */
    static public function getMpAccessToken()
    {
        $access_token = \Cache::get('wechat_mp_access_token');
        if ($access_token) {
            return $access_token;
        } else {
            $mp_config = config('custom.wechat.mp');
            $response = SystemServer::request(
                'GET',
                'https://api.weixin.qq.com/cgi-bin/token',
                [
                    'grant_type' => 'client_credential',
                    'appid' => $mp_config['appid'],
                    'secret' => $mp_config['secret'],
                ]
            );
            if ($response['code'] == 200) {
                if (!isset($response['data']['errcode']) || $response['data']['errcode'] == 0) {
                    $expiresAt = Carbon::now()->addSeconds($response['data']['expires_in'] - 1);
                    \Cache::put('wechat_mp_access_token', $response['data']['access_token'], $expiresAt);

                    return $response['data']['access_token'];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
    }

    /**
     * @param $scene
     * @param string $page
     * @param int $width
     * @param bool $auto_color
     * @param bool $is_hyaline
     * @param array $line_color
     * @return array
     */
    static public function getxacodeunlimit(
        $scene,
        $page = null,
        $width = 370,
        $auto_color = false,
        $is_hyaline = true,
        $line_color = ['r' => 0, 'g' => 0, 'b' => 0]
    ) {
        $access_token = WechatServer::getMpAccessToken();
        if (!$access_token) {
            return ['code' => 500, 'msg' => 'access_token错误'];
        }
        $data = [
            'scene' => $scene,
            'width' => $width,
            'auto_color' => $auto_color,
            'is_hyaline' => $is_hyaline,
            'line_color' => $line_color,
        ];
        if (!empty($page)) {
            $data['page'] = $page;
        }
        $response = SystemServer::request(
            'POST',
            'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token,
            $data
        );
        if (!isset($response['data']['errcode']) || $response['data']['errcode'] == 0) {
            return $response;
        } else {
            return ['code' => 500, 'msg' => '微信错误'.$response['data']['errcode'].':'.$response['data']['errmsg']];
        }
    }
}
