<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;


use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Admin\SystemArea;
use App\Model\Index\OperateRecord;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\PhotographerWorkTag;
use App\Model\Index\Templates;
use App\Model\Index\User;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\PhotographerServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use function Qiniu\base64_urlSafeEncode;


/**
 * 摄影师相关
 * Class SystemController
 * @package App\Http\Controllers\Api
 */
class PhotographerController extends BaseController
{
    /**
     * 摄影师信息
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function info(PhotographerRequest $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer = SystemServer::parseRegionName($photographer);
        $photographer = SystemServer::parsePhotographerRank($photographer);

        return $this->responseParseArray($photographer);
    }

    /**
     * 摄影师作品集列表
     * @param PhotographerRequest $request
     */
    public function works(PhotographerRequest $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $keywords = $request->keywords;
        $whereRaw = '1';
        if (!empty($keywords)) {
            $whereRaw = "(photographer_works.customer_name like '%{$keywords}%' || photographer_work_customer_industries.name like '%{$keywords}%' || photographer_work_categories.name like '%{$keywords}%' || photographer_work_tags.name like '%{$keywords}%')";
        }
        $photographer_works = $photographer->photographerWorks()->select('photographer_works.*')->join(
            'photographer_work_customer_industries',
            'photographer_works.photographer_work_customer_industry_id',
            '=',
            'photographer_work_customer_industries.id'
        )->join(
            'photographer_work_categories',
            'photographer_works.photographer_work_category_id',
            '=',
            'photographer_work_categories.id'
        );
        if (!empty($keywords)) {
            $photographer_works = $photographer_works->join(
                'photographer_work_tags',
                'photographer_work_tags.photographer_work_id',
                '=',
                'photographer_works.id'
            );
        }
        $photographer_works = $photographer_works->where(['photographer_works.status' => 200])->whereRaw(
            $whereRaw
        )->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $all_tags = [];
        foreach ($photographer_works as $k => $photographer_work) {
            $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
                PhotographerWorkTag::allowFields()
            )->get()->toArray();
            $all_tags[] = $photographer_work_tags;
        }
        $photographer_works = SystemServer::parsePaginate($photographer_works->toArray());
        $photographer_works = ArrServer::toNullStrData(
            $photographer_works,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_works['data'] = ArrServer::inData($photographer_works['data'], PhotographerWork::allowFields());
        foreach ($photographer_works['data'] as $k => $v) {
            $photographer_works['data'][$k]['tags'] = $all_tags[$k];
        }
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCover($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_works['data']);
        $photographer_works['data'] = SystemServer::parsePhotographerWorkCategory($photographer_works['data']);

        return $this->response->array($photographer_works);
    }

    /**
     * 获取摄影师作品的上一个和下一个id
     * @param PhotographerRequest $request
     */
    public function workNext(Request $request)
    {
        $photographer = User::photographer($request->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographerWorks = $photographer->photographerWorks()->where(['photographer_works.status' => 200])->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->get()->pluck('id')->toArray();

        $data = [];
        $data['next'] = 0;
        $data['previous'] = 0;
        foreach ($photographerWorks as $key => $item) {
            if ($item == $request->current_photographerwork_id) {
                $data['next'] = $photographerWorks[$key - 1] ?? 0;
                $data['previous'] = $photographerWorks[$key + 1] ?? 0;
            }
        }


        return $this->response->array($data);

    }

    /**
     * 摄影师作品集信息
     * @param PhotographerRequest $request
     */
    public function work(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $photographer_work_sources = $photographer_work->photographerWorkSources()->select(
            PhotographerWorkSource::allowFields()
        )->where('status', 200)->orderBy('sort', 'asc')->get()->toArray();
        $photographer_work_tags = $photographer_work->photographerWorkTags()->select(
            PhotographerWorkTag::allowFields()
        )->get()->toArray();
        $photographer_work = ArrServer::inData($photographer_work->toArray(), PhotographerWork::allowFields());
        $photographer_work = ArrServer::toNullStrData(
            $photographer_work,
            ['project_amount', 'sheets_number', 'shooting_duration']
        );
        $photographer_work = SystemServer::parsePhotographerWorkCover($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCustomerIndustry($photographer_work);
        $photographer_work = SystemServer::parsePhotographerWorkCategory($photographer_work);
        $photographer_work['sources'] = $photographer_work_sources;
        $photographer_work['tags'] = $photographer_work_tags;
        $photographer_work['photographer'] = ArrServer::inData($photographer->toArray(), Photographer::allowFields());
        $photographer_work['photographer'] = SystemServer::parseRegionName($photographer_work['photographer']);
        $photographer_work['photographer'] = SystemServer::parsePhotographerRank($photographer_work['photographer']);

        return $this->response->array($photographer_work);
    }

    /**
     * 摄影师海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function poster(PhotographerRequest $request)
    {
        $response = Photographer::poster($request->photographer_id);
        if ($response['code'] != 200) {
            return $this->response->error($response['msg'], $response['code']);
        }
        $url = $response['url'];

        return $this->responseParseArray(compact('url'));
    }

    /**
     * 摄影师作品集海报
     * @param PhotographerRequest $request
     * @return mixed|void
     */
    public function workPoster(PhotographerRequest $request)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $request->photographer_work_id]
        )->first();
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }
        $response = PhotographerWork::poster($request->photographer_work_id);
        if ($response['code'] != 200) {
            return $this->response->error($response['msg'], $response['code']);
        }
        $url = $response['url'];

        return $this->responseParseArray(compact('url'));
    }

    public function workPoster2(Request $request)
    {
        $photographer_work_id = $request->input('photographer_work_id', 0);
        $template_id = $request->input('template_id', 0);
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $photographer_work_id]
        )->first();
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer_work) {
            return $this->response->error('摄影师作品集不存在', 500);
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('摄影师不存在', 500);
        }

        $user = User::where(['photographer_id' => $photographer->id])->first();
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

        $photographer_work_source = $photographer_work->photographerWorkSources()
            ->where(
                ['status' => 200, 'type' => 'image']
            )
            ->orderBy(
                'sort',
                'asc'
            )
            ->first();

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $template = Templates::where('number', $template_id)->first();
        if (empty($template)) {
            return $this->response->error('模板不存在', 500);
        }

        $xacode = User::createXacode2($photographer->id);
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

        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $workName = $photographer_work->customer_name;
        $name = "{$photographer->name} · 摄影作品";
        $money = "{$photographer_work->project_amount}元 · {$photographer_work->sheets_number}张 · {$photographer_work->shooting_duration}小时";
        $datas = [
            '##money##' => "{$photographer_work->project_amount}元",
            '##number##' => "{$photographer_work->sheets_number}张",
            '##time##' => "{$photographer_work->shooting_duration}小时",
            '##customer##' => $workName,
            '##name##' => $photographer->name,
            '##title##' => "{$photographer_rank}摄像师",
        ];

        if ($photographer_work_source->deal_height > 600) {  // 长图
            $width = 1000;
            $height = $photographer_work_source->deal_height;
            $imgs = $domain . '/' . $photographer_work_source->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/1000x600";
        } else { // 宽图
            $imgs = $domain . '/' . $photographer_work_source->deal_key . "?imageMogr2/auto-orient/thumbnail/x600/gravity/Center/crop/!1000x600-0-0|imageslim";
        }

        $bg = $template->background . "?imageMogr2/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . $xacodeImgage . "/gravity/SouthEast/dx/180/dy/275/";
        $handle[] = "/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/South/dx/0/dy/600/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($workName) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/960/fill/" . base64_urlSafeEncode("#323232") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/478/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($name) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/342/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($money) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/275/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看完整作品") . "/fontsize/600/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/86/";

        foreach ($datas as $key => $data) {
            $template->text1 = str_replace($key, $data, $template->text1);
            $template->text2 = str_replace($key, $data, $template->text2);
            $template->text3 = str_replace($key, $data, $template->text3);
            $template->text4 = str_replace($key, $data, $template->text4);
        }

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text1) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/190/";
        if ($template->text2) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text2) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/340/";
        }
        if ($template->text3) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text3) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/490/";
        }

        if ($template->text4) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text4) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/640/";
        }


        $url = implode($handle);

        return $this->responseParseArray(compact('url'));
    }

    /**
     * 人脉排行榜
     * @param PhotographerRequest $request
     * @return mixed
     */
    public function rankingList(PhotographerRequest $request)
    {
        $limit = $request->limit ?? 50;
        $photographers = PhotographerServer::visitorRankingList($limit);
        $_fields = array_map(
            function ($v) {
                return 'photographer_work_sources.' . $v;
            },
            PhotographerWorkSource::allowFields()
        );
        foreach ($photographers as $k => $p) {
            $photographers[$k] = json_decode(json_encode($p), true);

            $photographer_work_sources = PhotographerWorkSource::join(
                'photographer_works',
                'photographer_work_sources.photographer_work_id',
                '=',
                'photographer_works.id'
            )->select($_fields)
                ->where(
                    [
                        'photographer_works.status' => 200,
                        'photographer_work_sources.status' => 200,
                        'photographer_works.photographer_id' => $p->id,
                        'photographer_work_sources.type' => 'image',
                    ]
                )
                ->orderBy('photographer_work_sources.created_at', 'desc')->take(3)->get()->toArray();
            $photographers[$k]['photographer_work_sources'] = $photographer_work_sources;
        }
        $photographers = SystemServer::parseRegionName($photographers);
        $photographers = SystemServer::parsePhotographerRank($photographers);

        return $this->responseParseArray($photographers);
    }
}
