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
use Qiniu\Http\Client;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use function GuzzleHttp\Psr7\build_query;
use function Qiniu\base64_urlSafeDecode;
use function Qiniu\base64_urlSafeEncode;

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
            return ['status' => 'ERROR', 'message' => '验证码错误'];
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
     * 格式化项目封面数据
     * @param $data 数据
     * @param $random 是否随机取一个资源，否则取第一张
     * @param $isSimple 是否需要简易数据
     * @return array
     */
    public static function parsePhotographerWorkCover($data, $random = false, $isSimple = false)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = self::parsePhotographerWorkCover($v, false);
            } else {
                if ($k == 'id' && !isset($data['cover'])) {
                    $where = ['photographer_work_id' => $v, 'status' => 200];
                    $total = PhotographerWorkSource::where($where)->count();
                    $data['cover'] = '';
                    if ($total > 0) {
                        if ($random) {
                            $skip = mt_rand(0, $total - 1);
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->skip($skip)->take(1)->first();
                            $thumb_url = self::getPhotographerWorkSourceThumb($photographer_work_source);
                            $thumb_url_2 = self::getPhotographerWorkSourceThumb(
                                $photographer_work_source,
                                'imageMogr2/auto-orient/thumbnail/600x'
                            );
                            if (!$isSimple) {
                                $data['cover'] = $photographer_work_source->toArray();
                                $data['cover']['thumb_url'] = $thumb_url;
                                $data['cover']['thumb_url_2'] = $thumb_url_2;
                            } else {
                                $data['cover'] = $thumb_url;
                            }
                        } else {
                            $photographer_work_source = PhotographerWorkSource::where($where)->select(
                                PhotographerWorkSource::allowFields()
                            )->orderBy('sort', 'asc')->first();
                            $thumb_url = self::getPhotographerWorkSourceThumb($photographer_work_source);
                            $thumb_url_2 = self::getPhotographerWorkSourceThumb(
                                $photographer_work_source,
                                'imageMogr2/auto-orient/thumbnail/600x'
                            );
                            if (!$isSimple) {
                                $data['cover'] = $photographer_work_source->toArray();
                                $data['cover']['thumb_url'] = $thumb_url;
                                $data['cover']['thumb_url_2'] = $thumb_url_2;
                            } else {
                                $data['cover'] = $thumb_url;
                            }
                        }
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * 格式化项目客户行业数据
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
     * 格式化项目分类数据
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
     * 格式化作品头衔数据
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
    static public function request($type, $url, $data = [], $ssl = true, $headers = [], $file=false)
    {
        //提交方式
        if ($type != 'get' && $type != 'GET' && $type != 'post' &&
            $type != 'POST'
        ) {
            return ['code' => 500, 'msg' => 'curl parameter error'];
        }
        //请求数据处理
        if ($type == 'post' || $type == 'POST') {
            if (!$file){
                if (is_array($data)) {
                    $data = json_encode($data,JSON_UNESCAPED_UNICODE);
                }
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
        if (isset($headers['User-Agent']) && $headers['User-Agent'] !== '') {
            $user_agent = $headers['User-Agent'];
            unset($headers['User-Agent']);
        } else {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ?
                $_SERVER['HTTP_USER_AGENT'] :
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36';//配置代理信息
        }
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
        //允许请求的链接跳转，可以抓取重定向的链接内容
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        //SSL相关
        if ($ssl) {
            //禁用后curl将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //检查服务器SSL证书中是否存在一个。
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
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
            $curl_error = curl_error($curl);
            curl_close($curl);

            return ['code' => 500, 'msg' => 'curl request error：'.$curl_error];
        } else {
            curl_close($curl);
            $response_arr = json_decode($response, true);
            $data = is_null($response_arr) ? $response : $response_arr;

            return ['code' => 200, 'msg' => 'ok', 'data' => $data];
        }
    }

    /**
     * @param $url
     * @param bool $ssl
     * @param array $headers
     * @return bool|string
     */
    static public function getCurl($url, $ssl = true, $headers = [], $filename)
    {
        $fp = fopen($filename, 'wb');
        //curl完成
        $curl = curl_init();
        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//请求url
        if (isset($headers['User-Agent']) && $headers['User-Agent'] !== '') {
            $user_agent = $headers['User-Agent'];
            unset($headers['User-Agent']);
        } else {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ?
                $_SERVER['HTTP_USER_AGENT'] :
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36';//配置代理信息
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        curl_setopt($curl, CURLOPT_TIMEOUT, 600000000);//设置请求时间
        if ($headers) {
            $http_headers = [];
            foreach ($headers as $k => $v) {
                $http_headers[] = $k.':'.(is_array($v) ? json_encode($v) : $v);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $http_headers);
        }
        //允许请求的链接跳转，可以抓取重定向的链接内容
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        //SSL相关
        if ($ssl) {
            //禁用后curl将终止从服务端进行验证
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //检查服务器SSL证书中是否存在一个。
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        //处理响应结果
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);//curl_exec()是否返回响应结果

        //发出请求
        curl_exec($curl);
        curl_close($curl);
        fclose($fp);
    }

    /**
     * 百度网盘直接上传到七牛
     * @param $asyncBaiduWorkSourceUpload_id
     * @param $type
     * @param $url
     * @param null $callbackurl
     * @param null $key
     */
    static public function qiniuFetchBaiduPan(
        $asyncBaiduWorkSourceUpload_id,
        $type,
        $url,
        $callbackurl = null,
        $key = null
    ) {
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        $bucket = 'zuopin';
        $auth = new Auth($accessKey, $secretKey);
        $config = new Config();
        $config->useHTTPS = true;
        $ak = $auth->getAccessKey();
        $apiHost = $config->getApiHost($ak, $bucket);
//        $body['url'] = config('app.url').'/api/baiduDlink?dlink='.base64_urlSafeEncode($url);
        $body['url'] = $url;
        $body['bucket'] = $bucket;
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
        $headers = array_merge(['Authorization' => $authorization['Authorization']], $headers);
        $client = new Client();
        $res = $client->post($apiUrl, json_encode($body), $headers);
        $return = [
            'asyncBaiduWorkSourceUpload_id' => $asyncBaiduWorkSourceUpload_id,
            'res' => json_decode(json_encode($res), true),
        ];

        return $return;
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

    /**
     * 统一获取七牛缩略图地址
     * @param $url
     * @param $fop
     * @return string
     */
    static public function getQiniuUnifiedThumb($url, $fop = null)
    {
        $fop = $fop ? $fop : 'imageMogr2/auto-orient/thumbnail/!600x600r/gravity/Center/crop/!600x600';

        return $url.'?'.$fop;
    }

    /**
     * 根据资源获取缩略图地址
     * @param PhotographerWorkSource $photographerWorkSource
     * @param $fop
     * @return mixed|string
     */
    static public function getPhotographerWorkSourceThumb(PhotographerWorkSource $photographerWorkSource, $fop = null)
    {
        if ($photographerWorkSource) {
            if ($photographerWorkSource->url && $photographerWorkSource->deal_url) {
                if ($photographerWorkSource->type != 'image') {
                    return $photographerWorkSource->url;
                } else {
                    if ($photographerWorkSource->url == $photographerWorkSource->deal_url) {
                        return $photographerWorkSource->url;
                    } else {
                        return self::getQiniuUnifiedThumb($photographerWorkSource->deal_url, $fop);
                    }
                }
            }
        }

        return '';
    }

    /**
     * 根据资源集合获取缩略图地址
     * @param $photographerWorkSources
     * @param $fop
     * @return mixed
     */
    static public function getPhotographerWorkSourcesThumb($photographerWorkSources, $fop = null)
    {
        foreach ($photographerWorkSources as $k => $photographerWorkSource) {
            $photographerWorkSources[$k]['thumb_url'] = self::getPhotographerWorkSourceThumb(
                $photographerWorkSource,
                $fop
            );
        }

        return $photographerWorkSources;
    }

    /**
     * 修改图片尺寸
     */
    static public function resize_image($img_src, $new_img_path, $new_width, $new_height)
    {
        $img_info = @getimagesize($img_src);
        if (!$img_info || $new_width < 1 || $new_height < 1 || empty($new_img_path)) {
            return false;
        }
        if (strpos($img_info['mime'], 'jpeg') !== false) {
            $pic_obj = imagecreatefromjpeg($img_src);
        } else if (strpos($img_info['mime'], 'gif') !== false) {
            $pic_obj = imagecreatefromgif($img_src);
        } else if (strpos($img_info['mime'], 'png') !== false) {
            $pic_obj = imagecreatefrompng($img_src);
        } else {
            return false;
        }
        $pic_width = imagesx($pic_obj);
        $pic_height = imagesy($pic_obj);
        if (function_exists("imagecopyresampled")) {
            $new_img = imagecreatetruecolor($new_width,$new_height);
            imagecopyresampled($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
        } else {
            $new_img = imagecreate($new_width, $new_height);
            imagecopyresized($new_img, $pic_obj, 0, 0, 0, 0, $new_width, $new_height, $pic_width, $pic_height);
        }
        if (preg_match('~.([^.]+)$~', $new_img_path, $match)) {
            $new_type = strtolower($match[1]);
            switch ($new_type) {
                case 'jpg':
                    imagejpeg($new_img, $new_img_path);
                    break;
                case 'gif':
                    imagegif($new_img, $new_img_path);
                    break;
                case 'png':
                    imagepng($new_img, $new_img_path);
                    break;
                default:
                    imagejpeg($new_img, $new_img_path);
            }
        } else {
            imagejpeg($new_img, $new_img_path);
        }
        imagedestroy($pic_obj);
        imagedestroy($new_img);
        return true;
    }

    /**
     *
     */
    static public function checkImgSecurity($PhotographerWorkSource_id){
        $PhotographerWorkSource = PhotographerWorkSource::where(['id' => $PhotographerWorkSource_id])->first();
        $picurl = $PhotographerWorkSource->deal_url;
        $localtmppic = '/tmp/' . uniqid() . random_int(1, 1000) . '.jpg';
        file_put_contents($localtmppic, file_get_contents($picurl));
        $localpic = '/tmp/' . md5($PhotographerWorkSource->deal_key) . '.jpg';
        SystemServer::resize_image($localtmppic, $localpic, 750, 1334);

        $flag = WechatServer::checkContentSecurity($localpic, true);
        if ($flag){
            $PhotographerWorkSource->review = 1;
        }else{
            $PhotographerWorkSource->review = 2;
        }

        $PhotographerWorkSource->save();

        \Log::info("checkImgSecurity " . $PhotographerWorkSource->picurl);

        return $flag;
    }
}
