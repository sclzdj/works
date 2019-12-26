<?php

namespace App\Http\Controllers\Api;

use App\Model\Admin\SystemArea;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\Star;
use App\Model\Index\User;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use function Qiniu\base64_urlSafeDecode;
use function Qiniu\base64_urlSafeEncode;
use Qiniu\Storage\UploadManager;
use Validator;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class StarController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function __construct()
    {

    }

    /**
     * 查询邀请码状态是否可用
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function getStars(Request $request)
    {
        $page = $request->input('page', 999);
        $size = $request->input('size', 15);
        if ($page == 999) {
            $photographer_ids = Star::all()->pluck('photographer_id');
        } else {
            $page = ($page - 1) * $size;
            $photographer_ids = (new Star())->skip($page)->take($size)->pluck('photographer_id');
        }

        $this->data['data'] = Photographer::with(['photographerWorks' => function ($query) {
            $query->where('status', 200);
        }])
            ->whereIn('photographers.id', $photographer_ids)
            ->leftJoin('photographer_ranks', 'photographers.photographer_rank_id', '=', 'photographer_ranks.id')
            ->select([
                'photographers.id', 'photographers.name',
                'photographers.avatar', 'photographer_ranks.name as ranks',
                'photographers.province', 'photographers.city', 'photographers.area'
            ])
            ->get();
        foreach ($this->data['data'] as &$datum) {
            $areas = SystemArea::whereIn('id', [$datum['province'], $datum['city'], $datum['area']])->get()->pluck('name');
            $datum['areas'] = $areas;
            $works_ids = $datum['photographerWorks']->pluck('id');
            $datum['cover'] = PhotographerWorkSource::whereIn('photographer_work_id', $works_ids)
                ->where(['status' => 200, 'type' => 'image'])
                ->select(['key', 'url'])
                ->orderBy('updated_at', 'desc')->limit(3)->get();
            unset($datum['photographerWorks']);
            unset($datum['province']);
            unset($datum['city']);
            unset($datum['area']);
        }
        $this->data['result'] = true;
        return $this->responseParseArray($this->data);
    }

    public function test(Request $request)
    {
        $work = PhotographerWork::find(68);
        $sheets_number = $work->hide_sheets_number == 1 ? '保密' : $work->sheets_number . '张';
        $project_number = $work->hide_project_amount == 1 ? '保密' : $work->project_amount . '元';
        $shooting_duration = $work->hide_shooting_duration == 1 ? '保密' : $work->shooting_duration . '小时';
        $customer_name = $work->customer_name;
        $buttonText = $project_number . '·' . $sheets_number . '·' . $shooting_duration;
        $firstPhoto = PhotographerWorkSource::where(
            [
                'photographer_work_id' => $work->id,
                'status' => 200,
            ]
        )->orderBy('created_at', 'asc')->first();

        if (empty($firstPhoto)) {
            return ['result' => false, 'msg' => "作品集不存在"];
        }

        // 拿到七牛url
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 背景图
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';
        // 上面图
        $sharePhoto = $firstPhoto->deal_url . "?imageMogr2/auto-orient/crop/1200x657";

        $handleUrl = array();
        $handleUrl[0] = $whiteBg;
        $handleUrl[1] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($sharePhoto) . "/gravity/North/dx/0/dy/0";
        $handleUrl[2] = "/text/" . \Qiniu\base64_urlSafeEncode($customer_name) . "/fontsize/1500/fill/" . base64_urlSafeEncode("#323232") . "/gravity/North/dx/0/dy/743";
        $handleUrl[3] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/900/fill/" . base64_urlSafeEncode("#969696") . "/gravity/North/dx/0/dy/887";

        // echo implode("", $handleUrl);die();
        array_shift($handleUrl);

        $fops = ["imageMogr2/auto-orient/thumbnail/1200x960!" . implode("", $handleUrl)];
        $bucket = 'zuopin';
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            "FtSr3gPOeI8CjSgh5fBkeHaIsJnm",
            $fops,
            null,
            config(
                'app.url'
            ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $firstPhoto->id . '&step=4',
            true
        );
        if ($qrst['err']) {
            ErrLogServer::QiniuNotifyFop(
                0,
                '七牛持久化接口返回错误信息',
                $request->all(),
                $firstPhoto,
                $qrst['err']
            );
        }

        var_dump($qrst);
    }

    public function upload()
    {
        $filename = 'xacodes/' . time() . mt_rand(10000, 99999) . '.png';
        $bgimg = Image::make('xacodes/bbg.jpg')->resize(383, 320);
        $bgimg->save($filename);
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
        list($ret, $err) = $uploadMgr->putFile($upToken, null, $filename);

        dd($ret);
    }

    public function test2(Request $request)
    {
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 白背景图
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';
        // 黑背景图
        $blackBgs = [];
        $blackBg = $domain . '/FtXkbly4Qu-tEeiBiolLj-FFPXeo?imageMogr2/auto-orient/thumbnail/383x320!';
        $blackBgs = array_fill(0, 6, $blackBg);

        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $workIds = PhotographerWork::where('photographer_id', $request->photographer_id)
            ->where('status', 200)->get()->pluck('id');
        $resources = PhotographerWorkSource::where(['status' => 200])
            ->where('type', 'image')
            ->whereIn('photographer_work_id', $workIds)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
        $buttonText = SystemArea::find($photographer->province)->name . ' · ' . PhotographerRank::find($photographer->photographer_rank_id)->name . '摄影师';

        $resourceId = 0;
        foreach ($resources as $key => $resource) {
            $resourceId = $resource->id;
            if ($resource->deal_width < $resource->deal_height) {  // 长图
                $width = 380;
                $height = $resource->deal_height;
                $imgs = $domain . '/' . $resource->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/crop/382x320";
            } else { // 宽图
                $width = $resource->deal_width;
                $height = $resource->deal_height / 2;
                $imgs = $domain . '/' . $resource->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/crop/382x320";
            }
            $blackBgs[$key] = $imgs;
        }

        $handleUrl = array();
        $handleUrl[] = $whiteBg;
        $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[0]) . "/gravity/NorthWest/dx/0/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[1]) . "/gravity/NorthWest/dx/409/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[2]) . "/gravity/NorthWest/dx/817/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[3]) . "/gravity/NorthWest/dx/0/dy/340";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[4]) . "/gravity/NorthWest/dx/409/dy/340";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[5]) . "/gravity/NorthWest/dx/817/dy/340";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/1500/fill/" . base64_urlSafeEncode("#323232") . "/gravity/North/dx/0/dy/743";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/1000/fill/" . base64_urlSafeEncode("#969696") . "/gravity/North/dx/0/dy/886";

        array_shift($handleUrl);

        $fops = ["imageMogr2/auto-orient/thumbnail/1200x960!" . implode("", $handleUrl)];
        $bucket = 'zuopin';
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            "FtSr3gPOeI8CjSgh5fBkeHaIsJnm",
            $fops,
            null,
            config(
                'app.url'
            ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $resourceId . '&step=5',
            true
        );
        var_dump($qrst);
    }

    public function test3(Request $request)
    {

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $photographerWorkSource = PhotographerWorkSource::where(
            [
                'id' => $request->input('photographer_work_source_id'),
                'type' => 'image',
            ]
        )->first();
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();

        $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
        $xacode = User::createXacode2($photographerWork->id, 'photographer_work');

        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/thumbnail/185x185!'
            );
        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/thumbnail/185x185!|roundPic/radius/!50p'
            );
        }

        $hanlde = [];
        $hanlde[] = "https://file.zuopin.cloud/work_source_image_bg.jpg";
        $hanlde[] = "?imageMogr2/auto-orient/crop/" . $photographerWorkSource->deal_width . 'x' . ($photographerWorkSource->deal_height + 250);

        $hanlde[] = "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/";
        $hanlde[] = "|watermark/3/image/" . base64_encode("https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T") . "/gravity/South/dx/0/dy/0/";

        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";

        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographerWork->customer_name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/71/dy/162/";
        $fistX = 75;
        // 根据字体来判断宽度 中文40 数字字母20
        for ($i = 0; $i < mb_strlen($photographerWork->customer_name); $i++) {
            $char = mb_substr($photographerWork->customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 42;
            } else {
                $fistX += 26;
            }
        }

        $hanlde[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode("https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/" . $fistX . "/dy/170/";
        $secondX = $fistX + 45;
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#C8C8C8") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/" . $secondX . "/dy/162/";

        $count = PhotographerWorkSource::where('photographer_work_id', $photographerWorkSource->photographer_work_id)->count();
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫,看剩余" . $count . "张作品") . "/fontsize/609/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/101/dy/80/";
        $hanlde[] = "|imageslim";
        echo implode($hanlde) . PHP_EOL;
    }

    public function test4(Request $request)
    {
        $photographer_id = $request->input('photographer_id');
        $response = [];
        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '摄影师不存在';
            return $response;
        }
        $user = User::where(['photographer_id' => $photographer_id])->first();
        if (!$user) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';
            return $response;
        }
        if ($user->identity != 1) {
            $response['code'] = 500;
            $response['msg'] = '用户不是摄影师';
            return $response;
        }

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $xacode = User::createXacode2($photographer_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'created_at',
            'desc'
        )->limit(4)->get()->toArray();

        $text = [];
        foreach ($photographer_works as $photographer_work) {
            $text[] = $photographer_work['customer_name'];
        }

        $data = [];
        $data['url1'] = $this->getPersonStyle1($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text);
        $data['url2'] = $this->getPersonStyle2($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text);
        $data['url3'] = $this->getPersonStyle3($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text);

        return $data;
    }


    private function getPersonStyle1($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode("https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH") . "/gravity/South/dx/0/dy/0/";
        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/141/dy/334/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/98/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer_city . ' · ' . $photographer_rank . '摄影师') . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/99/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/99/dy/90/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("Hi!") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/180/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("我是摄影师") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/330/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/480/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode('Base' . $photographer_city) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/West/dx/101/dy/-220/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode('擅长' . $photographer_rank . '摄像') . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/West/dx/101/dy/-70/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle2($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {

        if ($photographer->bg_img) {
            $photographer->bg_img = $photographer->bg_img . '?imageMogr2/auto-orient/thumbnail/x1507/gravity/Center/crop/!1200x1507-0-0|imageslim';
        }

        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/thumbnail/1200x2187!";
        $handle = array();
        $handle[] = $bg;

        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode("https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH") . "/gravity/South/dx/0/dy/0/";
        if ($photographer->bg_img) {
            $handle[] = "image/" . base64_urlSafeEncode($photographer->bg_img) . "/gravity/North/dx/0/dy/0/";
        }

        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer_city . ' · ' . $photographer_rank . '摄影师') . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/90/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

    private function getPersonStyle3($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode("https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH") . "/gravity/South/dx/0/dy/0/";
        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer_city . ' · ' . $photographer_rank . '摄影师') . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/90/";
        $endKey = count($text);

        $indexPos = 180;
        foreach ($text as $key => $item) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($item) .
                "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
                "/fontstyle/" . base64_urlSafeEncode("Bold") .
                "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
                "/gravity/NorthWest/dx/100/dy/" . ($indexPos + ($key * 150)) . "/";
        }

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("……") .
            "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
            "/fontstyle/" . base64_urlSafeEncode("Bold") .
            "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
            "/gravity/NorthWest/dx/100/dy/" . ($indexPos + ($endKey  * 160)) . "/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("都是我拍的") .
            "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
            "/fontstyle/" . base64_urlSafeEncode("Bold") .
            "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
            "/gravity/West/dx/100/dy/80/";

        $handle[] = "|imageslim";

        return implode($handle);
    }

}
