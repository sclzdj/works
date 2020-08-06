<?php
namespace App\Servers;

use Log;
/**
 * 百度服务
 * @package App\Servers
 * @author jsyzchenchen@gmail.com
 * @date 2020/07/26
 */
class BaiduServer
{
    const OPENAPI_OAUTH_URL_PREFIX = 'https://openapi.baidu.com/oauth/2.0';
    const XPAN_API_URL_PREFIX = 'https://pan.baidu.com/rest/2.0/xpan';
    const PAN_API_URL_PREFIX = 'https://pan.baidu.com/api';
    const PCS_DATA_API_URL_PREFIX = 'https://d.pcs.baidu.com/rest/2.0/pcs';
    const ACCESS_TOKEN_GET_URL = '/token?grant_type=authorization_code&';
    const NAS_UINFO_GET_URL = '/nas?method=uinfo&';
    const QUOTA_GET_URL = '/quota?';
    const FILE_PRECREATE_URL = '/file?method=precreate&';
    const SUPERFILE2_UPLOAD_URL = '/superfile2?method=upload&';
    const FILE_CREATE_URL = '/file?method=create&';

    //superfile2
    const SUPER_VIP_UPLOAD_LIMIT = 33554432; //32M
    const VIP_UPLOAD_LIMIT       = 16777216; //16M
    const NORMAL_UPLOAD_LIMIT    = 4194304;  //4M
    const SUPER_VIP_COMMIT_LIMIT = 21474836480; //20G
    const VIP_COMMIT_LIMIT       = 10737418240; //10G
    const NORMAL_COMMIT_LIMIT    = 4294967296;  //4G

    public static $errNo = 1;
    public static $errMsg = "server error";

    /**
     * 使用code换取access_token
     * @param $code string
     * @return string
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/26
     */
    public static function getAccessTokenByCode($code, $redirectUri)
    {
        $baiduConfig = config('custom.baidu.pan');
        $apiKey = $baiduConfig["apiKey"];
        $secretKey = $baiduConfig["secretKey"];

        $url = self::OPENAPI_OAUTH_URL_PREFIX . self::ACCESS_TOKEN_GET_URL . "code={$code}&client_id={$apiKey}&client_secret={$secretKey}&redirect_uri={$redirectUri}";

        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        $httpCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($httpCode != 200) {
            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        $contentsArr = json_decode($contents, true);
        if (isset($contentsArr['error']) && !empty($contentsArr['error'])) {
            self::$errMsg = $contentsArr['error_description'];
            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        return "";
    }

    /**
     * 获取用户信息
     * @param $accessToken string
     * @return array|bool
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/26
     */
    public static function getNasUInfo($accessToken)
    {
        $url = self::XPAN_API_URL_PREFIX . self::NAS_UINFO_GET_URL . 'access_token=' . $accessToken;
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        $httpCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($httpCode != 200) {
            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        $contentsArr = json_decode($contents, true);
        if ($contentsArr['errno'] != 0) {
            self::$errNo = $contentsArr['errno'];
            self::$errMsg = $contentsArr['errmsg'];

            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        return $contentsArr;
    }

    /**
     * 获取用户的容量信息
     * @param $accessToken
     * @return bool|mixed
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/26
     */
    public static function getQuotaInfo($accessToken)
    {
        $url = self::PAN_API_URL_PREFIX . self::QUOTA_GET_URL . 'access_token=' . $accessToken . '&chckfree=1&checkexpire=1';
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        $httpCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($httpCode != 200) {
            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        $contentsArr = json_decode($contents, true);
        if ($contentsArr['errno'] != 0) {
            self::$errNo = $contentsArr['errno'];
            self::$errMsg = $contentsArr['errmsg'];
            Log::warning("getAccessTokenByCode failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        return $contentsArr;
    }

    /**
     * 上传文件
     * @param $accessToken string
     * @param $panStoragePath string
     * @param $filename string
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/26
     */
    public static function upload($accessToken, $panStoragePath, $filename)
    {
        $fileSize = filesize($filename);
        $fileMd5 = md5_file($filename);

        //将文件进行切片
        /*
         * 分片上传大小限制规则
         * 1、普通用户单个分片大小固定为4MB
         * 2、普通会员用户单个分片大小上限为16MB
         * 3、超级会员用户单个分片大小上限为32MB
         */
        $sliceFileSizeLimit = self::NORMAL_UPLOAD_LIMIT;
        if ($fileSize > self::NORMAL_UPLOAD_LIMIT) {
            //获取用户信息
            $uInfo = self::getNasUInfo($accessToken);
            if (!$uInfo) {
                return false;
            }

            $vipType = $uInfo['vip_type'];
            switch ($vipType) {
                case 0://普通用户
                    $sliceFileSizeLimit = self::NORMAL_UPLOAD_LIMIT;
                    if ($fileSize > self::NORMAL_COMMIT_LIMIT) {
                        Log::warning("file zise gt file limit");
                        return false;
                    }
                    break;
                case 1://会员用户
                    $sliceFileSizeLimit = self::VIP_UPLOAD_LIMIT;
                    if ($fileSize > self::VIP_COMMIT_LIMIT) {
                        Log::warning("file zise gt file limit");
                        return false;
                    }
                    break;
                case 2://超级会员
                    $sliceFileSizeLimit = self::SUPER_VIP_UPLOAD_LIMIT;
                    if ($fileSize > self::SUPER_VIP_COMMIT_LIMIT) {
                        Log::warning("file zise gt file limit");
                        return false;
                    }
                    break;
                default:
                    $sliceFileSizeLimit = self::NORMAL_UPLOAD_LIMIT;
            }
            Log::info("vipType:" . $vipType);
        }

        Log::info("sliceFileSizeLimit:" . $sliceFileSizeLimit);

        $splitFileList = array();
        $blockList = array();
        if ($fileSize > $sliceFileSizeLimit) {//需要将文件进行切割后分片上传
            $i    = 0;                                  //分割的块编号
            $fp   = fopen($filename,"rb");           //要分割的文件
            while (!feof($fp)) {
                $splitFilename = storage_path("app") . "/split_{$fileMd5}_{$i}";
                $handle = fopen($splitFilename, "wb");
                fwrite($handle, fread($fp, $sliceFileSizeLimit));//切割的块大小
                fclose($handle);
                unset($handle);
                $blockList[] = md5_file($splitFilename);
                $splitFileList[] = $splitFilename;
                $i++;
            }
            fclose($fp);
        } else {//无需切片
            $blockList[] = $fileMd5;
            $splitFileList[] = $filename;
        }

        $fp   = fopen($filename,"rb");           //要分割的文件
        $splitFilename = storage_path("app") . "/split_256KB_{$fileMd5}";
        $handle = fopen($splitFilename, "wb");
        fwrite($handle, fread($fp, 262144));//切割的块大小，固定为256KB
        fclose($handle);
        unset($handle);
        fclose($fp);
        $sliceMd5 = md5_file($splitFilename);
        @unlink($splitFilename);

        //1.预上传，拿到uploadid
        $url = self::XPAN_API_URL_PREFIX . self::FILE_PRECREATE_URL . 'access_token=' . $accessToken;
        $postParam = array(
            'path' => $panStoragePath,
            'size' => $fileSize,
            'isdir' => 0,
            'autoinit' => 1,
            'rtype' => 1,//冲突时重命名
            'block_list' => json_encode($blockList),
            'content-md5' => $fileMd5,
            'slice_md5' => $sliceMd5,
        );
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url, ['form_params' => $postParam]);
        $httpCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($httpCode != 200) {
            Log::warning("file precreate failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }
        $contentsArr = json_decode($contents, true);
        if ($contentsArr['errno'] != 0) {
            self::$errNo = $contentsArr['errno'] ?? 1;
            self::$errMsg = $contentsArr['errmsg'] ?? '';
            Log::warning("file precreate failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }
        if (isset($contentsArr["return_type"]) && $contentsArr["return_type"] == 2) {//秒传成功，直接返回成功
            return true;
        }
        $uploadid = $contentsArr['uploadid'];

        //2.分片上传
        foreach ($splitFileList as $partseq => $splitFilename) {
            $url = self::PCS_DATA_API_URL_PREFIX . self::SUPERFILE2_UPLOAD_URL . "access_token={$accessToken}&type=tmpfile&uploadid={$uploadid}&partseq={$partseq}&path=" . rawurlencode($panStoragePath);
            $response = $client->request('POST', $url, [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($splitFilename, 'r')
                    ],
                ]
            ]);
            @unlink($splitFilename);//删除临时文件
            $httpCode = $response->getStatusCode();
            $contents = $response->getBody()->getContents();
            if ($httpCode != 200) {//pcs只需要判断http code
                $contentsArr = json_decode($contents, true);
                self::$errNo = $contentsArr['error_code'] ?? 1;
                self::$errMsg = $contentsArr['error_msg'] ?? '';
                Log::warning("file upload failed, httpCode[{$httpCode}], contents[$contents]");
                return false;
            }
        }

        //3.文件提交
        $url = self::XPAN_API_URL_PREFIX . self::FILE_CREATE_URL . "access_token={$accessToken}";
        $postParam = array(
            'path' => $panStoragePath,
            'size' => $fileSize,
            'isdir' => 0,
            'rtype' => 1,
            'uploadid' => $uploadid,
            'block_list' => json_encode($blockList),
        );
        $response = $client->post($url, ['form_params' => $postParam]);
        $httpCode = $response->getStatusCode();
        $contents = $response->getBody()->getContents();
        if ($httpCode != 200) {
            Log::warning("file create failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        $contentsArr = json_decode($contents, true);
        if ($contentsArr['errno'] != 0) {
            self::$errNo = $contentsArr['errno'] ?? 1;
            self::$errMsg = $contentsArr['errmsg'] ?? '';
            Log::warning("file create failed, httpCode[{$httpCode}], contents[$contents]");
            return false;
        }

        return true;
    }

    /**
     * 获取错误码
     * @return int
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/28
     */
    public static function getErrNo()
    {
        return self::$errNo;
    }

    /**
     * 获取错误消息
     * @return string
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/28
     */
    public static function getErrMsg()
    {
        return self::$errMsg;
    }
}
