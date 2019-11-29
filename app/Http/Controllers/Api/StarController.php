<?php

namespace App\Http\Controllers\Api;

use App\Model\Admin\SystemArea;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\Star;
use App\Model\Index\User;
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

    public function test()
    {
        $work = PhotographerWork::find(17);
        $sheets_number = $work->hide_sheets_number == 1 ? '保密' : $work->sheets_number . '张';
        $project_number = $work->hide_project_amount == 1 ? '保密' : $work->project_amount . '元';
        $shooting_duration = $work->hide_shooting_duration == 1 ? '保密' : $work->shooting_duration . '小时';
        $customer_name = $work->customer_name;
        $buttonText = $project_number . '.' . $sheets_number . '.' . $shooting_duration;

        $firstPhoto = PhotographerWorkSource::where(
            [
                'photographer_work_id' => $work->id,
                'status' => 200,
            ]
        )->orderBy('created_at', 'asc')->first();
        // 拿到七牛url
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/crop/1200x303';

        $handleUrl = [];
        $handleUrl[] = $firstPhoto->url;
        $handleUrl[] = "?imageMogr2/auto-orient/crop/1200x960"; //原图
        $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($whiteBg) . "/gravity/South/dx/0/dy/0";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode($customer_name) . "/fontsize/1500/fill/" . base64_urlSafeEncode("#323232") . "/gravity/South/dx/0/dy/136";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/700/fill/" . base64_urlSafeEncode("#969696") . "/gravity/South/dx/0/dy/20";

        dd(implode("", $handleUrl));

    }

    public function test2()
    {
        $photographer_id = 16;
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';

        $workIds = PhotographerWork::where('photographer_id', $photographer_id)
            ->where('status', 200)->get()->pluck('id');

        $resources = PhotographerWorkSource::where(['status' => 200])->whereIn('photographer_work_id', $workIds)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $handleUrl = [];
        $handleUrl[] = $whiteBg;

        foreach ($resources as $key => $resource) {
            if ($key == 0) {
                $imgs = $domain .'/'. $resource->key.'?imageMogr2/auto-orient/thumbnail/382x320!';
                $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/NorthWest/dx/0/dy/0";
            }

            if ($key == 1) {
                $imgs = $domain .'/'. $resource->key.'?imageMogr2/auto-orient/thumbnail/382x320!';
                $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/North/dx/0/dy/0";
            }

            if ($key == 2) {
                $imgs = $domain .'/'. $resource->key.'?imageMogr2/auto-orient/thumbnail/382x320!';
                $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/NorthEast/dx/0/dy/0";
            }
        }

        echo implode("", $handleUrl);


    }


}
