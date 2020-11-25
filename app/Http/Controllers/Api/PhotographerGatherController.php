<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Index\PhotographerGatherRequest;
use App\Http\Requests\Index\PhotographerRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherFilterRecord;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerGatherWork;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
use App\Servers\ArrServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * 合集相关
 * Class PhotographerGatherController
 * @package App\Http\Controllers\Api
 */
class PhotographerGatherController extends BaseController
{
    public function index(PhotographerGatherRequest $request)
    {
        if ($request->photographer_id > 0) {
            $photographer = $this->_photographer($request->photographer_id);
        } else {
            $photographer = $this->_photographer(null, $this->guards['user']);
        }
        if (!$photographer || $photographer->status != 200) {
            return $this->response->error('用户不存在', 500);
        }
        $keywords = $request->keywords;
        if ($request->keywords !== null && $request->keywords !== '') {
            $whereRaw = "(name like ?)";
            $whereRaw2 = ["%{$keywords}%"];
        }
        $photographerGathers = PhotographerGather::select(PhotographerGather::allowFields());
        if ($request->keywords !== null && $request->keywords !== '') {
            $photographerGathers = $photographerGathers->whereRaw(
                $whereRaw,
                $whereRaw2
            );
        }
        $photographerGathers = $photographerGathers->where(['photographer_id'=>$photographer->id,'status' => 200])->orderBy(
            'created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $photographerGathers = SystemServer::parsePaginate($photographerGathers->toArray());
        foreach ($photographerGathers['data'] as $k => $photographerGather) {
            $photographerGathers['data'][$k]['xacode'] = PhotographerGather::getXacode(
                $photographerGather['id'],
                false
            );
            //添加review
            $photographerGathers['data'][$k]['review'] = PhotographerGather::getPhotographerGatherReviewStatus($photographerGathers['data'][$k]['id']);
            $photographerGathers['data'][$k]['count'] = PhotographerGather::getGatherWorkSourcescount($photographerGathers['data'][$k]['id']);
            $photographerGathers['data'][$k]['workscount'] = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->count();
            $photographerGathers['data'][$k]['cover'] = [];
            $photographerGatherWorks = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->orderBy('sort', 'desc')->limit(3)->get();
            if ($photographerGatherWorks) {
                foreach ($photographerGatherWorks as $photographerGatherWork){
                    $photographerWork = PhotographerWork::where(
                        ['id' => $photographerGatherWork->photographer_work_id, 'status' => 200]
                    )->first();
                    if($photographerWork){
                        $photographerWork = SystemServer::parsePhotographerWorkCover($photographerWork->toArray());
                        array_push($photographerGathers['data'][$k]['cover'], $photographerWork['cover']);
                    }
                }
            }
        }
        return $this->response->array($photographerGathers);
    }

    public function InfoSimple(PhotographerGatherRequest $request){
        if ($request->photographer_id > 0) {
            $photographer = $this->_photographer($request->photographer_id);
        } else {
            $photographer = $this->_photographer(null, $this->guards['user']);
        }

        $photographerGathers = PhotographerGather::select(PhotographerGather::allowFields());
        $photographerGathers = $photographerGathers->where(['photographer_id'=>$photographer->id,'status' => 200])->orderBy(
            'created_at',
            'desc'
        )->paginate(
            $request->pageSize
        );
        $photographerGathers = SystemServer::parsePaginate($photographerGathers->toArray());
        foreach ($photographerGathers['data'] as $k => $photographerGather) {
            $photographerGathers['data'][$k]['xacode'] = PhotographerGather::getXacode(
                $photographerGather['id'],
                false
            );
            //添加review
            $photographerGathers['data'][$k]['review'] = PhotographerGather::getPhotographerGatherReviewStatus($photographerGathers['data'][$k]['id']);
            $photographerGathers['data'][$k]['count'] = PhotographerGather::getGatherWorkSourcescount($photographerGathers['data'][$k]['id']);
            $photographerGathers['data'][$k]['workscount'] = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->count();
            $photographerGathers['data'][$k]['cover'] = [];
            $photographerGatherWorks = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->orderBy('sort', 'desc')->limit(3)->get();
            if ($photographerGatherWorks) {
                foreach ($photographerGatherWorks as $photographerGatherWork){
                    $photographerWork = PhotographerWork::where(
                        ['id' => $photographerGatherWork->photographer_work_id, 'status' => 200]
                    )->first();
                    if($photographerWork){
                        $photographerWork = SystemServer::parsePhotographerWorkCover($photographerWork->toArray());
                        array_push($photographerGathers['data'][$k]['cover'], $photographerWork['cover']);
                    }
                }
            }
        }

        return $this->response->array($photographerGathers);

    }


    /**
     * 新增合集
     * @param PhotographerGatherRequest $request
     */
    public function store(PhotographerGatherRequest $request)
    {
        if (\Auth::guard($this->guards['user'])->guest()) {
            return $this->response->errorUnauthorized();
        }
        $photographer = $this->_photographer(null, $this->guards['user']);
//        $photographerGatherInfo = PhotographerGatherInfo::where(
//            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
//        )->where(['status' => 200])->first();
//        if (!$photographerGatherInfo) {
//            return $this->response->error('合集资料不存在合集资料不存在', 500);
//        }
//        $photographer_work_ids = $request->photographer_work_ids;
        \DB::beginTransaction();//开启事务
        try {
            $photographerGather = PhotographerGather::create();
//            if ($photographer_work_ids) {
//                foreach ($photographer_work_ids as $k => $photographer_work_id) {
//                    $photographerWork = PhotographerWork::where(
//                        ['photographer_id' => $photographer->id, 'id' => $photographer_work_id, 'status' => 200]
//                    )->first();
//                    if (!$photographerWork) {
//                        \DB::rollback();//回滚事务
//
//                        return $this->response->error('ID为'.$photographer_work_id.'的项目不存在', 500);
//                    }
//                    $photographerGatherWork = PhotographerGatherWork::create();
//                    $photographerGatherWork->photographer_gather_id = $photographerGather->id;
//                    $photographerGatherWork->photographer_work_id = $photographer_work_id;
//                    $photographerGatherWork->sort = $k + 1;
//                    $photographerGatherWork->save();
//                }
//            }
            $scene = '2/'. $photographer->id . '/' . $photographerGather->id;
            if (!$photographerGather->xacode) {
                $xacode_res = WechatServer::generateXacode($scene, false);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographerGather->xacode = $xacode_res['xacode'];
            }
            if (!$photographerGather->xacode_hyaline) {
                $xacode_res = WechatServer::generateXacode($scene);
                if ($xacode_res['code'] != 200) {
                    \DB::rollback();//回滚事务

                    return $this->response->error($xacode_res['msg'], $xacode_res['code']);
                }
                $photographerGather->xacode_hyaline = $xacode_res['xacode'];
            }
            $photographerGather->photographer_id = $photographer->id;
            $photographerGather->name = $request->name;
            $defaultinfo = PhotographerGatherInfo::where(['photographer_id' => $photographer->id, 'is_default' => 1])->first();
            if ($defaultinfo){
                $photographerGather->photographer_gather_info_id = $defaultinfo->id;
            }
//            $photographerGather->photographer_gather_info_id = $request->photographer_gather_info_id;
            $photographerGather->status = 200;
            $photographerGather->type = $request->type;
            $photographerGather->save();
            \DB::commit();//提交事务

            return $this->response->array(['photographer_gather_id' => $photographerGather->id]);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 更新合集
     * @param PhotographerGatherRequest $request
     * @throws \Exception
     */
    public function update(PhotographerGatherRequest $request)
    {
        if (\Auth::guard($this->guards['user'])->guest()) {
            return $this->response->errorUnauthorized();
        }

        $photographer = $this->_photographer(null, $this->guards['user']);
        $photographerGather = PhotographerGather::where(
            ['photographer_id' => $photographer->id, 'id' => $request->photographer_gather_id] #,
        )->where(['status' => 200])->first();
        if (!$photographerGather) {
            return $this->response->error('合集不存在', 500);
        }

        $photographer_work_ids = $request->photographer_work_ids;
        \DB::beginTransaction();//开启事务
        try {
            PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather->id]
            )->delete();
            if ($photographer_work_ids) {
                $photographer_work_ids = array_unique($photographer_work_ids);
                foreach ($photographer_work_ids as $k => $photographer_work_id) {
                    $photographerWork = PhotographerWork::where(
                        [ 'id' => $photographer_work_id, 'status' => 200, 'photographer_id' => $photographer->id,]
                    )->first();
                    if (!$photographerWork) {
                        \DB::rollback();//回滚事务

                        return $this->response->error('ID为'.$photographer_work_id.'的项目不存在', 500);
                    }
                    $photographerGatherWork = new PhotographerGatherWork();
                    $photographerGatherWork->photographer_gather_id = $photographerGather->id;
                    $photographerGatherWork->photographer_work_id = $photographer_work_id;
                    $photographerGatherWork->sort = $k + 1;
                    $photographerGatherWork->save();
                }
            }
            $photographerGather->name = $request->name;
//            $photographerGather->photographer_gather_info_id = $request->photographer_gather_info_id;
            $photographerGather->status = 200;
            $photographerGather->save();
            \DB::commit();//提交事务

            return $this->response()->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 合集用户展示
     */
    public function show(PhotographerGatherRequest $request){
        if (\Auth::guard($this->guards['user'])->guest()) {
            return $this->response->errorUnauthorized();
        }

        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1
        ];
        $filter = [
            'photographer_work_customer_industry_id' => $request['photographer_work_customer_industry_id'] !== null ?
                json_decode($request['photographer_work_customer_industry_id'], true) :
                '',
            'sheets_number' => $request['sheets_number'] !== null ?
                json_decode($request['sheets_number'], true) :
                '',
            'shooting_duration' => $request['shooting_duration'] !== null ?
                json_decode($request['shooting_duration'], true) :
                '',
            'photographer_work_category_ids' => $request['photographer_work_category_ids'] !== null ?
                json_decode($request['photographer_work_category_ids'], true) :
                ''
        ];

        $where = ['photographer_gather_works.photographer_gather_id' => $request->photographer_gather_id];
        $where2 = "";
        if (!empty($filter['sheets_number'])) {
            $where2 .= 'photographer_works.sheets_number >= '. $filter['sheets_number'][0] . ' and  photographer_works.sheets_number <= ' . $filter['sheets_number'][1];
        }
        if (!empty($filter['shooting_duration'])) {
            if ($where2){
                $where2 .= ' and ';
            }
            $where2 .= ' photographer_works.shooting_duration >= '. $filter['shooting_duration'][0] . ' and  photographer_works.shooting_duration <= ' . $filter['shooting_duration'][1];
        }
        if ($filter['photographer_work_customer_industry_id'] !== '') {
            $photographer_work_category_ids = implode(',', $filter['photographer_work_customer_industry_id']);
            if ($where2){
                $where2 .= ' and ';
            }
            $where2 .= ' photographer_works.photographer_work_customer_industry_id in ('.$photographer_work_category_ids.') ';
        }
        if ($filter['photographer_work_category_ids']){
            $photographer_work_category_ids = implode(',', $filter['photographer_work_category_ids']);
            if ($where2){
                $where2 .= ' and ';
            }
            $where2 .= ' photographer_works.photographer_work_category_id in ('.$photographer_work_category_ids.') ';
        }


        $photographer = $this->_photographer(null, $this->guards['user'])->toArray();
        $photographer['rank'] = "";
        if ($photographer['photographer_rank_id'] != 0){
            $rank = PhotographerRank::where(['id' => $photographer['photographer_rank_id']])->first();
            $photographer['rank'] = $rank->name;
        }

        $photographerGather = PhotographerGather::where(['id' => $request->photographer_gather_id])->first()->toArray();
        $photographerGather['gatherinfo'] = [];
        if ($photographerGather['photographer_gather_info_id'] != 0){

            $gatherinfo = PhotographerGatherInfo::where(['id' => $photographerGather['photographer_gather_info_id']])->first();
            if ($gatherinfo){
                    $photographerGather['gatherinfo'] = $gatherinfo->toArray();
                    $brand_tags = PhotographerInfoTag::where(
                        [
                            'photographer_gather_info_id' => $gatherinfo->id,
                            'photographer_id' => $photographer['id'],
                            'type' => 'brand',
                        ]
                    )->get();

                    if ($brand_tags){
                        $photographerGather['gatherinfo']['brand_tags'] = [];
                        foreach ($brand_tags as $brand_tag) {
                            $photographerGather['gatherinfo']['brand_tags'][] = $brand_tag->name;
                        }
                    }
            }

        }
        $photographerGather['review'] = PhotographerGather::getPhotographerGatherReviewStatus($photographerGather['id']);
        $photographerGather['works_count'] = PhotographerGather::getGatherWorkSourcescount($photographerGather['id']);
        $photographerWorks = PhotographerWork::where($where);
        if ($where2){
            $photographerWorks = PhotographerWork::where($where)->whereRaw($where2);
        }
        $photographerWorks = $photographerWorks->join(
            'photographer_gather_works',
            'photographer_gather_works.photographer_work_id',
            '=',
            'photographer_works.id'
        )->select(
            'photographer_works.id',
            'photographer_works.name',
            'photographer_works.customer_name',
            'photographer_gather_works.sort',
            'photographer_works.sheets_number',
            'photographer_works.shooting_duration',
            'photographer_works.photographer_work_customer_industry_id',
            'photographer_works.photographer_work_category_id'
        )->orderBy('photographer_gather_works.id', 'desc')->paginate(
            $pageInfo['pageSize']
        )->toArray();

        foreach ($photographerWorks['data'] as &$photographerWork){
            $photographerWork = SystemServer::parsePhotographerWorkCover($photographerWork, false, true);
            $photographerWork['industry_name'] = '';
            if ($photographerWork['photographer_work_customer_industry_id']){
                $PhotographerWorkCustomerIndustry = PhotographerWorkCustomerIndustry::where(['id' => $photographerWork['photographer_work_customer_industry_id']])->first()->toArray();
                $photographerWork['industry_name']  = $PhotographerWorkCustomerIndustry['name'];
            }

            $photographerWork['category_name'] = '';
            if ($photographerWork['photographer_work_category_id']){
                $PhotographerWorkCategory = PhotographerWorkCategory::where(['id' => $photographerWork['photographer_work_category_id']])->first()->toArray();
                $photographerWork['category_name']  = $PhotographerWorkCategory['name'];
            }

        }

        $data = [
            'photographer' => $photographer,
            'photographerGather' => $photographerGather,
            'photographerWorks' => $photographerWorks
        ];

        return $this->response->array($data);
    }

    public function  changesort(PhotographerGatherRequest $request){
        PhotographerGather::where(['id' => $request->photographer_gather_id])->update(['sort' => $request->sort]);

        return response()->noContent();
    }

    public function showAllSource(PhotographerGatherRequest $request){
        $photographer = $this->_photographer(null, $this->guards['user']);

        $worksids = [];
        $PhotographerWorkSourcesArr = [];
//        try{
            if ($request->photographer_work_ids){
                $worksids = json_decode($request->photographer_work_ids, true);
            }elseif ($request->photographer_gather_id){
                $photographerworks = \DB::table('photographer_gather_works')->where(['photographer_gather_id' => $request->photographer_gather_id])->get(['photographer_work_id']);
                if ($photographerworks){
                    foreach ($photographerworks as $photographerwork){
                        array_push($worksids,  $photographerwork->photographer_work_id);
                    }
                }
            }

            $PhotographerWorkSources = PhotographerWorkSource::where(['status' => 200])->whereRaw(
                '(`photographer_work_id` in ('.implode(',', $worksids).') )'
            )->select('id', 'photographer_work_id', 'type', 'deal_width', 'deal_url', 'rich_url','deal_height', 'image_ave', 'url')->paginate(
                $request->pageSize
            );

            if ($PhotographerWorkSources){
                $PhotographerWorkSources = $PhotographerWorkSources->toArray();
                $WorkSourcesdata = $PhotographerWorkSources['data'];
                foreach ($WorkSourcesdata as  $key => $source){
                    $photographer_work = PhotographerWork::where(['id' => $source['photographer_work_id'], 'status' => 200])->first();

                    $category = [];
                    if ($photographer_work->photographer_work_category_id){
                        $category = PhotographerWorkCategory::elderCategories($photographer_work->photographer_work_category_id);
                        $cg = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->find($photographer_work->photographer_work_category_id)->toArray();
                        array_push($category, $cg);
                    }

                    $cover = "";
                    try {
                        $cover = SystemServer::parsePhotographerWorkCover($photographer_work->toArray());
                    }catch (\Exception $e){

                    }

                    $work = [
                        'id' => $photographer_work->id,
                        'photographer_id' => $photographer->id,
                        'photographer_work_category_id' => $photographer_work->photographer_work_category_id,
                        'custom_name' => $photographer_work->custom_name,
                        'name' => $photographer_work->name,
                        'cover' => $cover,
                        'category' => $category
                    ];

                    $source['work'] = $work;
                    $source['thumb_url'] = $source['url'] . '?imageMogr2/auto-orient/thumbnail/600x';
//                    array_push($PhotographerWorkSourcesArr, $source);
                    $PhotographerWorkSources['data'][$key] = $source;
                }
            }
//
//            foreach ($worksids as $worksid) {
//                $photographer_work = PhotographerWork::where(['id' => $worksid])->first();
//                $category = [];
//                if ($photographer_work->photographer_work_category_id){
//                    $category = PhotographerWorkCategory::elderCategories($photographer_work->photographer_work_category_id);
//                    $cg = PhotographerWorkCategory::select(PhotographerWorkCategory::allowFields())->find($photographer_work->photographer_work_category_id)->toArray();
//                    array_push($category, $cg);
//                }
//
//                $cover = "";
//                try {
//                    $cover = SystemServer::parsePhotographerWorkCover($photographer_work->toArray());
//                }catch (\Exception $e){
//
//                }
//
//                $work = [
//                    'id' => $photographer_work->id,
//                    'photographer_id' => $photographer->id,
//                    'photographer_work_category_id' => $photographer_work->photographer_work_category_id,
//                    'custom_name' => $photographer_work->custom_name,
//                    'name' => $photographer_work->name,
//                    'cover' => $cover,
//                    'category' => $category
//                ];
//
//                $where = [
//                    'photographer_work_id' => $photographer_work->id,
//                    'status' => 200
//                ];
//
//                $PhotographerWorkSources = PhotographerWorkSource::where($where)->get(['id', 'photographer_work_id', 'type', 'deal_width', 'deal_url', 'rich_url','deal_height', 'image_ave', 'url']);
//                if ($PhotographerWorkSources){
//                    foreach ($PhotographerWorkSources->toArray() as  $source){
//
//                        $source['work'] = $work;
//                        $source['thumb_url'] = $source['url'] . '?imageMogr2/auto-orient/thumbnail/600x';
//                        array_push($PhotographerWorkSourcesArr, $source);
//                    }
//                }
//
//            }
//        }catch (\Exception $e){
//            return $this->response->error('解析出错', 500);
//        }

        return $this->response->array($PhotographerWorkSources);
    }


    public function bindphotographerinfo(PhotographerGatherRequest $request){
        PhotographerGather::where(['id' => $request->photographer_gather_id])->update(['photographer_gather_info_id' => $request->photographer_gather_info_id]);

        return response()->noContent();
    }

    public function destory(PhotographerGatherRequest $request){
      \DB::beginTransaction();
        try{
            $PhotographerGather =  PhotographerGather::where(['id' => $request->photographer_gather_id])->first();
//            if ($PhotographerGather->photographer_gather_info_id){
//                PhotographerGatherInfo::where(['id' => $PhotographerGather->photographer_gather_info_id])->delete();
//            }

            PhotographerGather::where(['id' => $request->photographer_gather_id])->delete();
            PhotographerGatherWork::where(['photographer_gather_id' =>  $request->photographer_gather_id])->delete();

        }catch (\Exception $e){
            \DB::rollBack();
        }
        \DB::commit();

        return response()->noContent();
    }


    public function getGatherWorksInfo(PhotographerGatherRequest $request){
        if (\Auth::guard($this->guards['user'])->guest()) {
            return $this->response->errorUnauthorized();
        }

        $gather_id = $request->photographer_gather_id;

        $lists = PhotographerWork::join(
            'photographer_gather_works',
            'photographer_gather_works.photographer_work_id',
            '=',
            'photographer_works.id'
        )->select(
            'photographer_work_category_id',
            'photographer_work_customer_industry_id'
        )->where(['photographer_gather_works.photographer_gather_id' => $gather_id])->groupBy('photographer_work_category_id', 'photographer_work_customer_industry_id')->get();
        $info = [
            'category' => [],
            'customer_industry' => []
        ];
        foreach ($lists as $list){
            if ($list['photographer_work_category_id'] != 0){
                $category = PhotographerWorkCategory::where(['id' => $list['photographer_work_category_id']])->first();
                if ($category){
                    $tmp = [
                        'photographer_work_category_id' => $list['photographer_work_category_id'],
                        'name' => $category->name
                    ];
                    array_push($info['category'], $tmp);
                }
            }

            if ($list['photographer_work_customer_industry_id'] != 0){
                $customer_industry = PhotographerWorkCustomerIndustry::where(['id' => $list['photographer_work_customer_industry_id']])->first();
                if ($customer_industry){
                    $tmp = [
                        'photographer_work_customer_industry_id' => $list['photographer_work_customer_industry_id'],
                        'name' => $customer_industry->name
                    ];
                    array_push($info['customer_industry'], $tmp);
                }
            }
        }

        $data = PhotographerWork::join(
            'photographer_gather_works',
            'photographer_gather_works.photographer_work_id',
            '=',
            'photographer_works.id'
        )->select(
            \DB::raw('(MIN(sheets_number)) as min_sheets_number'),
            \DB::raw('(MAX(sheets_number)) as max_sheets_number'),
            \DB::raw('(MIN(shooting_duration)) as min_shooting_duration'),
            \DB::raw('(MAX(shooting_duration)) as max_shooting_duration')
        )->where(['photographer_gather_works.photographer_gather_id' => $gather_id])->where(['photographer_works.status' => 200])->first()->toArray();


        $result = array_merge($info, $data);

        return $this->responseParseArray($result);
    }

    public function savefilterecord(PhotographerGatherRequest $request){
        if (\Auth::guard($this->guards['user'])->guest()) {
            return $this->response->errorUnauthorized();
        }
        $photographer = $this->_photographer(null, $this->guards['user']);
        $photographer_work_category_ids = [];
        if ($request->photographer_work_category_ids !== null && $request->photographer_work_category_ids !== '') {
            $photographer_work_category_ids = explode(',', $request->photographer_work_category_ids);
        }
        $photographer_work_customer_industry_ids = [];
        if ($request->photographer_work_customer_industry_ids !== null && $request->photographer_work_customer_industry_ids !== '') {
            $photographer_work_customer_industry_ids = explode(',', $request->photographer_work_customer_industry_ids);
        }
        //记录筛选条件
        \DB::beginTransaction();
        try {
            $record = PhotographerGatherFilterRecord::where(['photographer_gather_id' => $request->photographer_gather_id])->first();

            if (!$record){
                $record = PhotographerGatherFilterRecord::create();
            }
            $record->photographer_id = $photographer->id;
            $record->photographer_gather_id = $request->photographer_gather_id;
            $record->customer_industries = json_encode($photographer_work_customer_industry_ids);
            $record->category_ids = json_encode($photographer_work_category_ids);


            $project_amount = [
                'max' => $request->project_amount_max,
                'min' => $request->project_amount_min,
            ];
            $sheets_number = [
                'max' => $request->sheets_number_max,
                'min' => $request->sheets_number_min
            ];
            $shooting_duration = [
                'max' => $request->sheets_number_max,
                'min' => $request->sheets_number_min,
            ];
            $record->project_amount = json_encode($project_amount);
            $record->sheets_number = json_encode($sheets_number);
            $record->shooting_duration = json_encode($shooting_duration);
            $record->save();
        }catch (\Exception $exception){
            \DB::rollBack();

        }

        \DB::commit();


        return $this->response()->noContent();
    }

    public function filterecord(PhotographerGatherRequest $request){
        $photographer = $this->_photographer(null, $this->guards['user']);

        $photographer_gather_id = $request->photographer_gather_id;
        $record = PhotographerGatherFilterRecord::where(['photographer_gather_id' => $photographer_gather_id])->first();
        if ($record){
            $data = ArrServer::decodedata($record->toArray(), ['category_ids', 'customer_industries', 'project_amount', 'sheets_number', 'shooting_duration']);
            return $this->responseParseArray($data);
        }

        return $this->responseParseArray([]);
    }
}
