<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'appid' => 'wxe1cd43a6ae4a1600',
            'secret' => '71b7c7f6a68ae20f86e016c27a06e654',
            'js_code' => $js_code,
            'grant_type' => 'authorization_code'
        ];
        $url .= '?' . http_build_query($data);
        $response = $this->_request('GET', $url);
        if (isset($response['errcode']) && $response['errcode'] != 0) {
            return $this->response->error('微信接口报错：' . $response['errmsg'], 500);
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
    protected function _request($type, $url, $data = [], $ssl = true)
    {
        //提交方式
        if ($type != 'get' && $type != 'GET' && $type != 'post' &&
            $type != 'POST'
        ) {
            return $this->response->error('curl parameter error', 500);
        }
        //请求数据处理
        if (is_array($data)) {
            $data = json_encode($data);
        }
        //curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//请求url
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ?
            $_SERVER['HTTP_USER_AGENT'] :
            'HTTP_USER_AGENT_' . $data;//配置代理信息
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);//设置请求时间
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,
                    true);//允许请求的链接跳转，可以抓取重定向的链接内容
        //SSL相关
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,
                        false);//禁用后curl将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//检查服务器SSL证书中是否存在一个。。
        }
        //post请求相关
        if ($type == 'post' || $type == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);//是否为post请求
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//处理post请求数据
        }
        //处理响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果

        //发出请求
        $response = curl_exec($curl);
        $code = curl_errno($curl);
        if ($code !== 0) {
            return $this->response->error('curl request error', 500);
        } else {
            return json_decode($response, true);
        }
    }
}
