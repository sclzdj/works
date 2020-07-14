<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Index\User;
use App\Servers\SystemServer;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Request;

class BaseController extends Controller
{
    protected $guards = ['user' => 'users'];

    public function __construct()
    {
        $microtime = explode(' ', microtime());
        $log_filename = 'logs/api_request/'.date('Y-m-d').'/'.date('H').'.log';
        $requset_params = Request::all();
        $log = [];
        $log['时间'] = date('i:s', $microtime[1]).ltrim($microtime[0], '0');
        $log['来源'] = Request::getClientIp();
        $log['地址'] = $requset_params['s'] ?? '';
        $log['参数'] = Request::all();

        return SystemServer::filePutContents(
            $log_filename,
            json_encode($log, JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    use helpers;

    /**
     * 格式化返回数组
     * @param $data array 数据
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

    /**
     * 关联的用户
     * @param null $photographer_id
     * @param null $guard
     * @return mixed|void
     */
    protected function _photographer($photographer_id = null, $guard = null)
    {
        $photographer = User::photographer($photographer_id, $guard);
        if ($photographer === false) {
            return $this->response->errorUnauthorized();
        } elseif (!$photographer) {
            return $this->response->error('用户未找到', 500);
        } else {
            return $photographer;
        }
    }
}
