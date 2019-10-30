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
use App\Model\Index\User;
use App\Model\Index\Visitor;
use App\Servers\ArrServer;
use App\Servers\PhotographerServer;
use App\Servers\SystemServer;

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
                return 'photographer_work_sources.'.$v;
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
