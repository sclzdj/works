<?php

namespace App\Servers;

use App\Model\Admin\SystemArea;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\SmsCode;
use App\Model\Index\VisitorTag;
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use function GuzzleHttp\Psr7\build_query;

class SystemServer
{
    /**
     * 验证短信验证码
     * @param $mobile 手机号
     * @param $code 验证码
     * @param $purpose 用途
     * @param $ip IP
     * @return array
     */
    public static function verifySmsCode($mobile, $code, $purpose, $ip)
    {
        $sms_code = SmsCode::where(
            ['mobile' => $mobile, 'code' => $code, 'purpose' => $purpose, 'ip' => $ip, 'is_used' => 0]
        )->orderBy('created_at', 'desc')->first();
        if ($sms_code) {
            if (strtotime($sms_code->expired_at) < time()) {
                return ['status' => 'ERROR', 'message' => '短信验证码已过期，请重新发送'];
            } else {
                $sms_code->is_used = 1;
                $sms_code->save();

                return ['status' => 'SUCCESS', 'message' => 'OK'];
            }
        } else {
            return ['status' => 'ERROR', 'message' => '短信验证码错误'];
        }
    }

    /**
     * 解析数据中的地区名称
     * @param $data 数据
     * @param bool $px
     * @return array
     */
    public static function parseRegionName($data, $px = true)
    {
        if (!is_array($data) && $px) {
            return $data;
        }
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parseRegionName($v, false);
            } else {
                $temp = [
                    'id' => $v,
                    'name' => '',
                    'short_name' => '',
                ];
                if ($k == 'province') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['province'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['province'] = $temp;
                    }
                } elseif ($k == 'city') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['city'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['city'] = $temp;
                    }
                } elseif ($k == 'area') {
                    $system_area = SystemArea::find($v);
                    if ($system_area) {
                        $data['area'] = [
                            'id' => $v,
                            'name' => $system_area->name,
                            'short_name' => $system_area->short_name,
                        ];
                    } else {
                        $data['area'] = $temp;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 格式化接口分页数据
     * @param $data 数据
     * @return array
     */
    public static function parsePaginate($data)
    {
        $page_info = [
            'current_page' => $data['current_page'],
            'pageSize' => $data['per_page'],
            'last_page' => $data['last_page'],
            'total' => $data['total'],
        ];
        $data = $data['data'];

        return compact('data', 'page_info');
    }

    /**
     * 格式化作品集封面数据
     * @param $data 数据
     * @param $random 是否随机取一张图片，否则取第一张
     * @return array
     */
    public static function parsePhotographerWorkCover($data, $random = false)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCover($v, false);
            } else {
                if ($k == 'id' && !isset($data['cover'])) {
                    $where = ['photographer_work_id' => $v, 'type' => 'image', 'status' => 200];
                    $total = PhotographerWorkSource::where($where)->count();
                    $data['cover'] = '';
                    if ($total > 0) {
                        if ($random) {
                            $skip = mt_rand(0, $total - 1);
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->skip($skip)->take(1)->first()->toArray();
                            $data['cover'] = $photographer_work_source;
                        } else {
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->orderBy('sort', 'asc')->first()->toArray();
                            $data['cover'] = $photographer_work_source;
                        }
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集客户行业数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerWorkCustomerIndustry($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCustomerIndustry($v);
            } else {
                if ($k == 'photographer_work_customer_industry_id' && !isset($data['photographer_work_customer_industry'])) {
                    $customerIndustry = PhotographerWorkCustomerIndustry::select(
                        PhotographerWorkCustomerIndustry::allowFields()
                    )->where(['id' => $data['photographer_work_customer_industry_id']])->first();
                    $data['photographer_work_customer_industry'] = [];
                    if ($customerIndustry) {
                        $customerIndustry = $customerIndustry->toArray();
                        $data['photographer_work_customer_industry'] = PhotographerWorkCustomerIndustry::elderCustomerIndustries(
                            $data['photographer_work_customer_industry_id']
                        );
                        array_unshift($data['photographer_work_customer_industry'], $customerIndustry);
                        $data['photographer_work_customer_industry'] = array_reverse(
                            $data['photographer_work_customer_industry']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集分类数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerWorkCategory($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCategory($v);
            } else {
                if ($k == 'photographer_work_category_id' && !isset($data['photographer_work_category'])) {
                    $category = PhotographerWorkCategory::select(
                        PhotographerWorkCategory::allowFields()
                    )->where(['id' => $data['photographer_work_category_id']])->first();
                    $data['photographer_work_category'] = [];
                    if ($category) {
                        $category = $category->toArray();
                        $data['photographer_work_category'] = PhotographerWorkCategory::elderCategories(
                            $data['photographer_work_category_id']
                        );
                        array_unshift($data['photographer_work_category'], $category);
                        $data['photographer_work_category'] = array_reverse(
                            $data['photographer_work_category']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化作品集分类数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parsePhotographerRank($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerRank($v);
            } else {
                if ($k == 'photographer_rank_id' && !isset($data['photographer_rank'])) {
                    $rank = PhotographerRank::select(
                        PhotographerRank::allowFields()
                    )->where(['id' => $data['photographer_rank_id']])->first();
                    $data['photographer_rank'] = [];
                    if ($rank) {
                        $rank = $rank->toArray();
                        $data['photographer_rank'] = PhotographerRank::elderRanks(
                            $data['photographer_rank_id']
                        );
                        array_unshift($data['photographer_rank'], $rank);
                        $data['photographer_rank'] = array_reverse(
                            $data['photographer_rank']
                        );
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化访客标签数据
     * @param $data
     * @param bool $random
     * @return mixed
     */
    public static function parseVisitorTag($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parseVisitorTag($v);
            } else {
                if ($k == 'visitor_tag_id' && !isset($data['visitor_tag'])) {
                    $tag = VisitorTag::select(
                        VisitorTag::allowFields()
                    )->where(['id' => $data['visitor_tag_id']])->first();
                    $data['visitor_tag'] = [];
                    if ($tag) {
                        $data['visitor_tag'] = $tag->toArray();
                    }
                    break;
                }
            }
        }

        return $data;
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
    static public function request($type, $url, $data = [], $ssl = true, $headers = [])
    {
        //提交方式
        if ($type != 'get' && $type != 'GET' && $type != 'post' &&
            $type != 'POST'
        ) {
            return ['code' => 500, 'msg' => 'curl parameter error'];
        }
        //请求数据处理
        if ($type == 'post' || $type == 'POST') {
            if (is_array($data)) {
                $data = json_encode($data);
            }
        } else {
            if ($data) {
                if (is_array($data)) {
                    $data = build_query($data);
                }
                if (strpos($url, '?') !== false) {
                    $url .= '&'.$data;
                } else {
                    $url .= '?'.$data;
                }
            }
        }
        //curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//请求url
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ?
            $_SERVER['HTTP_USER_AGENT'] :
            'works';//配置代理信息
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);//设置请求时间
        if ($headers) {
            $http_headers = [];
            foreach ($headers as $k => $v) {
                $http_headers[] = $k.':'.(is_array($v) ? json_encode($v) : $v);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $http_headers);
        }
        curl_setopt(
            $curl,
            CURLOPT_FOLLOWLOCATION,
            true
        );//允许请求的链接跳转，可以抓取重定向的链接内容
        //SSL相关
        if ($ssl) {
            curl_setopt(
                $curl,
                CURLOPT_SSL_VERIFYPEER,
                false
            );//禁用后curl将终止从服务端进行验证
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
            return ['code' => 500, 'msg' => 'curl request error：'. curl_error($curl)];
        } else {
            $data = is_null(json_decode($response, true)) ? $response : json_decode($response, true);

            return ['code' => 200, 'msg' => 'ok', 'data' => $data];
        }
    }

    /**
     * 百度网盘直接上传到七牛
     * @param $type
     * @param $url
     * @param null $callbackurl
     * @param null $key
     */
    static public function qiniuFetchBaiduPan($type, $url, $callbackurl = null, $key = null)
    {
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        $bucket = 'zuopin';
        $auth = new Auth($accessKey, $secretKey);
        $config = new Config();
        $config->useHTTPS = true;
        $ak = $auth->getAccessKey();
        $apiHost = $config->getApiHost($ak, $bucket);
        $body = compact('url', 'bucket');
        if ($key) {
            $body['key'] = $key;
        }
        if ($callbackurl) {
            $body['callbackurl'] = $callbackurl;
        }
        if ($type == 'image') {
            $callbackbody =
                '{"key":"$(key)","hash":"$(etag)","width":"$(imageInfo.width)","height":"$(imageInfo.height)","size":"$(imageInfo.size)"}';
        } elseif ($type == 'video') {
            $callbackbody = '{"key":"$(key)","hash":"$(etag)","size":"$(avinfo.format.size)"}';
        } else {
            $callbackbody = '{"key":"$(key)","hash":"$(etag)","size":"$(fsize)"}';
        }
        $body['callbackbody'] = $callbackbody;
        $body['callbackbodytype'] = 'application/json';
        $headers = [];
        $headers["Content-Type"] = 'application/json';
        $apiUrl = $apiHost.'/sisyphus/fetch';
        $method = 'POST';
        $authorization = $auth->authorizationV2(
            $apiUrl,
            $method,
            json_encode($body),
            $headers["Content-Type"]
        );
        $headers = array_merge($headers, $authorization);

        return SystemServer::request(
            $method,
            $apiUrl,
            $body,
            true,
            $headers
        );
    }

    /**
     * 获取当前格式化后的毫秒时间戳
     * @return
     */
    static public function getMicrotime()
    {
        $microtime = explode(' ', microtime());
        $microtime = $microtime[1] + $microtime[0];

        return $microtime;
    }

    /**
     * 添加记录
     * @param $filename
     * @param $data
     * @param int $flags
     * @param null $context
     * @return bool|int
     */
    static public function filePutContents($filename, $data, $flags = FILE_APPEND, $context = null)
    {
        $files = explode('/', $filename);
        $newFiles = [];
        foreach ($files as $k => $file) {
            if ($file !== '') {
                $newFiles[] = $file;
            }
        }
        $filename = implode('/', $newFiles);
        if (count($newFiles) > 1) {
            unset($newFiles[count($newFiles) - 1]);
            $dir = implode('/', $newFiles);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * 七牛持久化
     * @param $key
     * @param $fops
     * @param $pipeline
     * @param $notifyUrl
     * @param $useHTTPS
     */
    static public function qiniuPfop($bucket, $key, $fops, $pipeline, $notifyUrl, $useHTTPS = true, $force = true)
    {
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        $config = new Config();
        if ($useHTTPS) {
            $config->useHTTPS = true;
        }
        $auth = new Auth($accessKey, $secretKey);
        $pfop = new PersistentFop($auth, $config);
        list($id, $err) = $pfop->execute($bucket, $key, $fops, $pipeline, $notifyUrl, $force);

        return compact('id', 'err');
    }
}
