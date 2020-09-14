<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Index\PhotographerGatherRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerGatherWork;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkCategory;
use App\Model\Index\PhotographerWorkCustomerIndustry;
use App\Model\Index\PhotographerWorkSource;
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
            $photographerGathers['data'][$k]['workscount'] = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->count();
            $photographerGathers['data'][$k]['cover'] = [];
            $photographerGatherWorks = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->orderBy('sort', 'asc')->limit(3)->get();
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
//            return $this->response->error('合集资料不存在', 500);
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
            $scene = '2/'.$photographerGather->id;
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
//            $photographerGather->photographer_gather_info_id = $request->photographer_gather_info_id;
            $photographerGather->status = 200;
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
            ['id' => $request->photographer_gather_id, 'photographer_id' => $photographer->id]
        )->where(['status' => 200])->first();
        if (!$photographerGather) {
            return $this->response->error('合集不存在', 500);
        }
//        $photographerGatherInfo = PhotographerGatherInfo::where(
//            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
//        )->where(['status' => 200])->first();
//        if (!$photographerGatherInfo) {
//            return $this->response->error('合集资料不存在', 500);
//        }
        $photographer_work_ids = $request->photographer_work_ids;
        \DB::beginTransaction();//开启事务
        try {
            PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather->id]
            )->delete();
            if ($photographer_work_ids) {
                foreach ($photographer_work_ids as $k => $photographer_work_id) {
                    $photographerWork = PhotographerWork::where(
                        ['photographer_id' => $photographer->id, 'id' => $photographer_work_id, 'status' => 200]
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
                $request['photographer_work_customer_industry_id'] :
                '',
            'sheets_number' => $request['sheets_number'] !== null ?
                $request['sheets_number'] :
                '',
            'shooting_duration' => $request['shooting_duration'] !== null ?
                $request['shooting_duration'] :
                ''
        ];

        $where = ['photographer_gather_works.photographer_gather_id' => $request->photographer_gather_id];

        if (!empty($filter['sheets_number'])) {
            $where[] = ['photographer_works.sheets_number', 'in', [$filter['sheets_number'][0], $filter['sheets_number'][1]]];
        }
        if (!empty($filter['shooting_duration'])) {
            $where[] = ['photographer_works.shooting_duration', 'in', [$filter['shooting_duration'][0], $filter['shooting_duration'][1]]];
        }
        if ($filter['photographer_work_customer_industry_id'] !== '') {
            $where[] = ['photographer_works.photographer_work_customer_industry_id', '<>', 0];
        }

        $photographer = $this->_photographer(null, $this->guards['user'])->toArray();
        $photographerGather = PhotographerGather::where(['id' => $request->photographer_gather_id])->first()->toArray();

        $photographerWorks = PhotographerWork::where($where)->join(
            'photographer_gather_works',
            'photographer_gather_works.photographer_work_id',
            '=',
            'photographer_works.id'
        )->select(
            'photographer_works.id',
            'photographer_works.name',
            'photographer_works.sheets_number',
            'photographer_works.shooting_duration',
            'photographer_works.photographer_work_customer_industry_id',
            'photographer_works.photographer_work_category_id'
        )->paginate(
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
}
