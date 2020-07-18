<?php
namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use Log;
use Redis;
use App\Model\Index\User;

class IndexController extends Controller
{
    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function index(){
        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
        $app = app('wechat.official_account');
        $app->server->push(function($message){
            switch ($message['MsgType']) {
                case 'event':
                    $event = $message['Event'];
                    if (in_array($event, array('subscribe', 'SCAN'))
                        && (isset($message['EventKey']) && strpos($message['EventKey'], 'qrcode_login') !== false)) {//web端微信扫码事件
                        $reply = $this->QRCodeLoginEventHandle($message);
                        if ($reply) {
                            return $reply;
                            break;
                        }
                    }
                default:
                    return "欢迎关注云作品！";
                    break;
            }
        });

        return $app->server->serve();
    }

    /**
     * web端微信扫码登录事件处理
     * @param $message
     * @return string
     * @author jsyzchenchen@gmail.com
     * @date 2020/7/14
     */
    public function QRCodeLoginEventHandle($message)
    {
        $openid = $message['FromUserName'];
        $eventKey = $message['EventKey'];

        //获取微信用户信息
        $app = app('wechat.official_account');
        $wechatUser = $app->user->get($openid);
        if (isset($wechatUser['errcode']) && $wechatUser['errcode'] != 0) {
            Log::warning("webQRLoginEventHandle failed, wechat errmsg:" . $wechatUser['errmsg']);
            return "登录失败，请稍后重试！";
        }
        $unionid = $wechatUser['unionid'];

        //根据openid查询user表是否有该用户，如果没有新建用户，如果有将该用户的微信扫码登录状态置为已登录
        $user = User::where(['gh_openid' => $openid])->orWhere(['unionid' => $unionid])->first();
        if (!$user) {
            return "登录失败，请在云作品小程序注册并且绑定微信号。";
        }

        //存储到Redis
        $res = Redis::set($eventKey, $user->id, 3600);
        if (!$res) {
            Log::warning("qrcode_login redis set failed");
            return $this->response->error('qrcode_login redis set failed', 500);
        }

        return "登录成功！";
    }
}
