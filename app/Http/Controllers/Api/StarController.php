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

//    public function upload()
//    {
//        $filename = 'xacodes/' . time() . mt_rand(10000, 99999) . '.png';
//        $bgimg = Image::make('xacodes/bbg.jpg')->resize(383, 320);
//        $bgimg->save($filename);
//        $bucket = 'zuopin';
//        $buckets = config('custom.qiniu.buckets');
//        $domain = $buckets[$bucket]['domain'] ?? '';
//        //用于签名的公钥和私钥
//        $accessKey = config('custom.qiniu.accessKey');
//        $secretKey = config('custom.qiniu.secretKey');
//        // 初始化签权对象
//        $auth = new Auth($accessKey, $secretKey);
//        // 生成上传Token
//        $upToken = $auth->uploadToken($bucket);
//        // 构建 UploadManager 对象
//        $uploadMgr = new UploadManager();
//        list($ret, $err) = $uploadMgr->putFile($upToken, null, $filename);
//
//        dd($ret);
//    }

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


}
