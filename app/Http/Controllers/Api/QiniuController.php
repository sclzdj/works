<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

/**
 * 七牛通用
 * Class QiniuController
 * @package App\Http\Controllers\Api
 */
class QiniuController extends UserGuardController
{
    /**
     * 获取参数
     * @return mixed
     */
    public function getParams()
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 用于签名的公钥和私钥
        $accessKey = config('custom.qiniu.accessKey');
        $secretKey = config('custom.qiniu.secretKey');
        // 初始化签权对象
        $auth = new Auth($accessKey, $secretKey);

        $returnBody = '{"key":"$(key)","hash":"$(etag)","fsize":"$(fsize)","avinfo":$(avinfo),"exif":$(exif),"width":"$(imageInfo.width)","height":"$(imageInfo.height)","imageAve":$(imageAve)}';
        $policy = array(
            'returnBody' => $returnBody
        );

        // 生成上传Token
        $upToken = $auth->uploadToken($bucket, null, 3600, $policy, true);
        return $this->responseParseArray(compact('upToken', 'domain'));
    }
}
