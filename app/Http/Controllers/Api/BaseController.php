<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Servers\SystemServer;
use Dingo\Api\Routing\Helpers;

class BaseController extends Controller
{
    use helpers;

    /**
     * 格式化返回数组
     * @param $data 数据
     *
     * @return mixed
     */
    protected function responseParseArray($data)
    {
        return $this->response->array(compact('data'));
    }

    /**
     * 微信小程序code换openid
     * @param $js_code  jscode
     *
     * @return 返回请求结果|void
     */
    protected function _wxCode2Session($js_code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $data = [
            'appid' => config('custom.wechat.mp.appid'),
            'secret' => config('custom.wechat.mp.secret'),
            'js_code' => $js_code,
            'grant_type' => 'authorization_code',
        ];
        $response = $this->_request('GET', $url, $data);
        if (isset($response['errcode']) && $response['errcode'] != 0) {
            return $this->response->error('微信接口报错：'.$response['errmsg'], 500);
        }

        return $response;

    }

    /**
     * 请求函数
     *
     * @param    $type 请求类型
     * @param    $url  请求服务器url
     * @param    $data post请求数据
     * @param    $ssl  是否为https协议 boolean类型
     *
     * @return   返回请求结果
     */
    protected function _request($type, $url, $data = [], $ssl = true, $headers = [])
    {
        $response = SystemServer::request($type, $url, $data, $ssl, $headers);
        if ($response['code'] != 200) {
            return $this->response->error($response['msg'], $response['code']);
        } else {
            return $response['data'];
        }
    }
}
