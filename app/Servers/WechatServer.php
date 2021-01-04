<?php


namespace App\Servers;


use Carbon\Carbon;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

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
                    $expiresAt = Carbon::now()->addSeconds($response['data']['expires_in'] - 60);
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
        $is_hyaline = false,
        $width = 370,
        $auto_color = false,
        $line_color = ['r' => 0, 'g' => 0, 'b' => 0]
    ) {
        $app = app('wechat.mini_program');
        $accessToken = $app->access_token; // EasyWeChat\Core\AccessToken 实例
        $access_token = $accessToken->getToken(); // token 字符串
        $access_token = $access_token['access_token'];

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
            return ['code' => 500, 'msg' => '微信生成小程序码错误'.$response['data']['errcode'].':'.$response['data']['errmsg']];
        }
    }

    /**
     * 生成小程序码并上传到七牛
     * @param $scene
     * @param bool $is_hyaline
     * @return array
     */
    public static function generateXacode($scene, $is_hyaline = true, $page="pages/authorization/step1/index")
    {
        $response = WechatServer::getxacodeunlimit($scene, $page, $is_hyaline, 420);
        if ($response['code'] == 200) {
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            //用于签名的公钥和私钥
            $accessKey = config('custom.qiniu.accessKey');
            $secretKey = config('custom.qiniu.secretKey');
            // 初始化签权对象
            $auth = new Auth($accessKey, $secretKey);
            // 生成上传Token
            $upToken = $auth->uploadToken($bucket);
            // 构建 UploadManager 对象
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->put($upToken, null, $response['data']);
            if ($err) {
                return ['code' => 500, 'msg' => '七牛上传小程序码错误:'.$err['error']];
            } else {
                return ['code' => 200, 'xacode' => $domain.'/'.$ret['key']];
            }
        } else {
            return $response;
        }
    }

    /**
     * 同时生成透明和白底两种小程序码并上传到七牛
     * @param $scene
     * @return array
     */
    public static function generateXacodes($scene)
    {
        $response = self::generateXacode($scene,false);
        if ($response['code'] == 200) {
            $res = self::generateXacode($scene);
            if ($res['code'] == 200) {
                return ['code' => 200, 'xacode' => $response['xacode'], 'xacode_hyaline' => $res['xacode']];
            } else {
                return $res;
            }
        } else {
            return $response;
        }
    }

    /**
     * 请求微信内容安全接口
     */
    public static function checkContentSecurity($content, $media=false){
        $app = app('wechat.mini_program');
        $accessToken = $app->access_token; // EasyWeChat\Core\AccessToken 实例
        $access_token = $accessToken->getToken(); // token 字符串
        $access_token = $access_token['access_token'];

        if (!$access_token) {
            return ['code' => 500, 'msg' => 'access_token错误'];
        }
        if ($media){
            $fileinfo = pathinfo($content);

            if (array_key_exists('extension',$fileinfo)){
                $tmpfile = '/tmp/' . $fileinfo['filename'] . '_' . uniqid() . '.' . $fileinfo['extension'];
            }else{
                $tmpfile = '/tmp/' . $fileinfo['basename'] . '_' . uniqid() . '.jpg';
            }

            file_put_contents($tmpfile, file_get_contents($content));

            $data = [
                'media' => new \CURLFile($tmpfile)
            ];

            $response = SystemServer::request(
                'POST',
                'https://api.weixin.qq.com/wxa/img_sec_check?access_token='.$access_token,
                $data,
                true,
                [],
                true
            );
            @unlink($tmpfile);
        }else{
            $data = [
                'content' => $content
            ];
            $response = SystemServer::request(
                'POST',
                'https://api.weixin.qq.com/wxa/msg_sec_check?access_token='.$access_token,
                $data
            );

        }

        if (!isset($response['data']['errcode']) || $response['data']['errcode'] == 0) {
            return true;
        } else {
            return false;
        }
    }
}
