<?php

namespace App\Model\Index;

use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Database\Eloquent\Model;
use function Qiniu\base64_urlSafeEncode;

class PhotographerWorkSource extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_work_id',
        'key',
        'url',
        'size',
        'width',
        'height',
        'deal_key',
        'deal_url',
        'deal_size',
        'deal_width',
        'deal_height',
        'rich_key',
        'rich_url',
        'rich_size',
        'rich_width',
        'rich_height',
        'is_newest_rich',
        'type',
        'origin',
        'status',
        'sort',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'photographer_work_id',
            'key',
            'url',
            'size',
            'width',
            'height',
            'deal_key',
            'deal_url',
            'deal_size',
            'deal_width',
            'deal_height',
            'rich_key',
            'rich_url',
            'rich_size',
            'rich_width',
            'rich_height',
            'is_newest_rich',
            'type',
            'origin',
            'created_at',
        ];
    }

    /*
     * 生成水印图
     *
     * @param string $photographer_work_source_id 作品图资源id
     *
     * @return void
     */
    public function generateWatermark($photographer_work_source_id)
    {
        $photographerWorkSource = PhotographerWorkSource::where('id', $photographer_work_source_id)->first();
        if (empty($photographerWorkSource)) {
            throw new \LogicException("PhotographerWorkSource不存在");
        }

        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        if (empty($photographerWork)) {
            throw new \LogicException("photographerWork不存在");
        }

        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            throw new \LogicException("photographer不存在");
        }

        $user = User::where(['photographer_id' => $photographerWork->photographer_id])->first();
        if (!$user) {
            throw new \LogicException("user不存在");
        }
        $srcKey = "";
        if (empty($photographerWorkSource->deal_key)) {
            $srcKey = config('custom.qiniu.crop_work_source_image_bg');
        } else {
            $srcKey = $photographerWorkSource->deal_key;
        }

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 生成水印图
        $xacode =PhotographerWork::xacode($photographerWork->id);
        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/auto-orient/thumbnail/185x185!'
            );
        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/auto-orient/thumbnail/210x210!|roundPic/radius/!50p'
            );
        }

        // 计算出作品名的初始位置
        $fistX = $this->calcWaterText($photographerWork->customer_name);
        // 水印剩余图片的数量和文字
        $count = PhotographerWorkSource::where('photographer_work_id', $photographerWorkSource->photographer_work_id)->where('status', 200)->count();
        $text = $count - 1 <= 0 ? '微信扫一扫，看我的全部作品' : "微信扫一扫，看剩余" . ($count - 1) . "张作品";

        $hanlde = [];
        // 对原图进行加高处理 增加水印框架图位置
        $hanlde[] = "imageMogr2/auto-orient/crop/1200x" . ($photographerWorkSource->deal_height + 250);
        // 作品图
        if ($photographerWorkSource->deal_url) {
            $hanlde[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url) . "/gravity/North/dx/0/dy/0/";
        }
        // 水印底部框架图
        $hanlde[] = "|watermark/3/image/" . base64_encode("https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T") . "/gravity/South/dx/0/dy/0/";
        // 水印小程序
        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";
        // 水印作品名
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographerWork->customer_name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/71/dy/162/";
        // 水印中的 @
        $hanlde[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode("https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/" . $fistX . "/dy/170/";
        // 水印的摄影师名字
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#C8C8C8") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/" . ($fistX + 45) . "/dy/162/";
        // 水印最后一行 微信扫一扫
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($text) . "/fontsize/609/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/78/";
        $hanlde[] = "|imageslim";

        $fops[] = implode($hanlde);
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            $srcKey,
            $fops,
            null,
            config(
                'app.url'
            ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=2&width=1200&height=' . $photographerWorkSource->deal_height . '&sort=0',
            true
        );
        if ($qrst['err']) {
            return ErrLogServer::QiniuNotifyFop(
                2,
                '持久化请求失败',
                "",
                $photographerWorkSource,
                $qrst['err']
            );
        }
    }

    private function calcWaterText($customer_name)
    {
        $fistX = 75;
        for ($i = 0; $i < mb_strlen($customer_name); $i++) {
            $char = mb_substr($customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 42;
            } else {
                $fistX += 26;
            }
        }
        return $fistX;
    }
}
