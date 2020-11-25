<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\PhotographerGatherRequest;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerWork;
use App\Model\Index\User;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * 合集资料相关
 * Class PhotographerGatherInfoController
 * @package App\Http\Controllers\Api
 */
class PhotographerGatherInfoController extends UserGuardController
{
    /**
     * 合集资料列表
     * @return mixed
     */
    public function index()
    {
        $photographer = $this->_photographer();
        $photographerGatherInfos = PhotographerGatherInfo::where(
            ['photographer_id' => $photographer->id]
        )->where(['status' => 200])->orderBy('sort', 'desc')->orderBy('created_at', 'desc')->get()->toArray();
        foreach ($photographerGatherInfos as $k => $photographerGatherInfo) {
            $brand_tags = PhotographerInfoTag::where(
                [
                    'photographer_gather_info_id' => $photographerGatherInfo['id'],
                    'photographer_id' => $photographer->id,
                    'type' => 'brand',
                ]
            )->get();
            $photographerGatherInfos[$k]['count'] = PhotographerGather::where(['photographer_gather_info_id' => $photographerGatherInfo['id'], 'status' => 200])->count();
            $photographerGatherInfos[$k]['brand_tags'] = [];
            foreach ($brand_tags as $brand_tag) {
                $photographerGatherInfos[$k]['brand_tags'][] = $brand_tag['name'];
            }
        }
        $photographerGatherInfos = SystemServer::parsePhotographerRank($photographerGatherInfos);

        return $this->responseParseArray($photographerGatherInfos);
    }

    /**
     * 新增合集资料
     * @param PhotographerGatherRequest $request
     * @return mixed|void
     */
    public function store(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $brand_tags = $request->brand_tags;
//        $is_default = (int)$request->is_default;
        //暂时不需要客户填写默认资料
        $is_default = 0;
        \DB::beginTransaction();//开启事务
        try {
//            if ($is_default) {
//                PhotographerGatherInfo::where(['photographer_id' => $photographer->id])->update(['is_default' => '0']);
//            }
            $photographerGatherInfo = PhotographerGatherInfo::create();
            if ($brand_tags) {
                foreach ($brand_tags as $brand_tag) {
                    $photographerInfoTag = PhotographerInfoTag::create();
                    $photographerInfoTag->photographer_id = $photographer->id;
                    $photographerInfoTag->photographer_gather_info_id = $photographerGatherInfo->id;
                    $photographerInfoTag->type = 'brand';
                    $photographerInfoTag->name = $brand_tag;
                    $photographerInfoTag->save();
                }
            }
            $photographerGatherInfo->photographer_id = $photographer->id;
            $photographerGatherInfo->photographer_rank_id = $request->photographer_rank_id;
            $photographerGatherInfo->start_year = $request->start_year;
            $photographerGatherInfo->is_default = $is_default;
            $photographerGatherInfo->status = 200;

            $lastpgw = PhotographerGatherInfo::where(['photographer_id' => $photographer->id])->where('photographer_rank_id', '<>', '')->select(
                \DB::raw("MAX(sort) as maxsort")
            )->first();
            $photographerGatherInfo->sort = 1;
            if ($lastpgw){
                $photographerGatherInfo->sort = $lastpgw->maxsort + 1;
            }

            $photographerGatherInfo->save();
            \DB::commit();//提交事务

            return $this->response->array(['photographer_gather_info_id' => $photographerGatherInfo->id]);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 更新合集资料
     * @param PhotographerGatherRequest $request
     * @throws \Exception
     */
    public function update(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $photographerGatherInfo = PhotographerGatherInfo::where(
            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
        )->where(['status' => 200])->first();
        if (!$photographerGatherInfo) {
            return $this->response->error('合集资料不存在', 500);
        }
        $brand_tags = $request->brand_tags;
        $is_default = (int)$request->is_default;
        \DB::beginTransaction();//开启事务
        try {
            if ($is_default) {
                PhotographerGatherInfo::where(['photographer_id' => $photographer->id])->update(['is_default' => '0']);
            }
            PhotographerInfoTag::where(
                ['photographer_id' => $photographer->id, 'photographer_gather_info_id' => $photographerGatherInfo->id]
            )->delete();
            if ($brand_tags) {
                foreach ($brand_tags as $brand_tag) {
                    $photographerInfoTag = PhotographerInfoTag::create();
                    $photographerInfoTag->photographer_id = $photographer->id;
                    $photographerInfoTag->photographer_gather_info_id = $photographerGatherInfo->id;
                    $photographerInfoTag->type = 'brand';
                    $photographerInfoTag->name = $brand_tag;
                    $photographerInfoTag->save();
                }
            }
            $photographerGatherInfo->photographer_rank_id = $request->photographer_rank_id;
            $photographerGatherInfo->start_year = $request->start_year;
            $photographerGatherInfo->is_default = $is_default;
            $photographerGatherInfo->showtype = $request->showtype;
            $photographerGatherInfo->status = 200;
            $photographerGatherInfo->save();
            \DB::commit();//提交事务

            return $this->response()->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 合集资料详情
     * @param PhotographerGatherRequest $request
     * @return mixed|void
     */
    public function show(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $photographerGatherInfo = PhotographerGatherInfo::where(
            ['photographer_gather_infos.id' => $request->photographer_gather_info_id, 'photographer_gather_infos.photographer_id' => $photographer->id]
        )->join(
            'photographer_gathers',
            'photographer_gathers.photographer_gather_info_id',
            '=',
            'photographer_gather_infos.id'
        )->select(
            'photographer_gather_infos.*',
            'photographer_gathers.id as photographer_gather_id'
        )->where(['photographer_gather_infos.status' => 200])->first();
        if (!$photographerGatherInfo) {
            return $this->response->error('合集资料不存在', 500);
        }
        $photographerGatherInfo=$photographerGatherInfo->toArray();
        $brand_tags = PhotographerInfoTag::where(
            [
                'photographer_gather_info_id' => $request->photographer_gather_info_id,
                'photographer_id' => $photographer->id,
                'type' => 'brand',
            ]
        )->get();
        $photographerGatherInfo['brand_tags'] = [];
        foreach ($brand_tags as $brand_tag) {
            $photographerGatherInfo['brand_tags'][] = $brand_tag['name'];
        }
        $photographerGatherInfo = SystemServer::parsePhotographerRank($photographerGatherInfo);

        return $this->responseParseArray($photographerGatherInfo);
    }

    /**
     * 删除合集资料
     * @param PhotographerGatherRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function destroy(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $photographerGatherInfo = PhotographerGatherInfo::where(
            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
        )->where(['status' => 200])->first();
        if (!$photographerGatherInfo) {
            return $this->response->error('合集资料不存在', 500);
        }
        $photographerGathers = PhotographerGather::where(
            ['photographer_gather_info_id' => $photographerGatherInfo->id]
        )->whereIn('status', [0, 200])->get()->toArray();
        if ($photographerGathers) {
            PhotographerGather::where(
                ['photographer_gather_info_id' => $photographerGatherInfo->id]
            )->whereIn('status', [0, 200])->update(['photographer_gather_info_id' => 0]);
//            return $this->response->error('有合集正在使用该资料，不可删除', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            PhotographerGatherInfo::where(
                ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
            )->update(['status' => 400]);

            if ($photographerGatherInfo->is_default == 1){
                $lastinfo = PhotographerGatherInfo::where(['photographer_id' => $photographer->id])->orderBy('id', 'desc')->first();
                $lastinfo->is_default = 1;
                $photographerGatherInfo->is_default = 0;
            }

            \DB::commit();//提交事务

            return $this->response()->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 复制合集资料
     * @param PhotographerGatherRequest $request
     * @return mixed|void
     * @throws \Exception
     */
    public function copy(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $photographerGatherInfo = PhotographerGatherInfo::where(
            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
        )->where(['status' => 200])->first();
        if (!$photographerGatherInfo) {
            return $this->response->error('合集资料不存在', 500);
        }
        \DB::beginTransaction();//开启事务
        try {

            $new_photographerGatherInfo = PhotographerGatherInfo::create();
            $brand_tags = PhotographerInfoTag::where(
                [
                    'photographer_gather_info_id' => $request->photographer_gather_info_id,
                    'photographer_id' => $photographer->id,
                    'type' => 'brand',
                ]
            )->get();
            if ($brand_tags) {
                foreach ($brand_tags as $brand_tag) {
                    $photographerInfoTag = PhotographerInfoTag::create();
                    $photographerInfoTag->photographer_id = $photographer->id;
                    $photographerInfoTag->photographer_gather_info_id = $new_photographerGatherInfo->id;
                    $photographerInfoTag->type = 'brand';
                    $photographerInfoTag->name = $brand_tag->name;
                    $photographerInfoTag->save();
                }
            }
            $new_photographerGatherInfo->photographer_id = $photographer->id;
            $new_photographerGatherInfo->photographer_rank_id = $photographerGatherInfo->photographer_rank_id;
            $new_photographerGatherInfo->start_year = $photographerGatherInfo->start_year;
            $new_photographerGatherInfo->is_default = 0;
            $new_photographerGatherInfo->status = 200;
            $new_photographerGatherInfo->sort = $photographerGatherInfo->sort;
            $new_photographerGatherInfo->save();
            \DB::commit();//提交事务
            $new_photographerGatherInfo = PhotographerGatherInfo::find($new_photographerGatherInfo->id)->toArray();
            $new_photographerGatherInfo['brand_tags'] = [];
            foreach ($brand_tags as $brand_tag) {
                $new_photographerGatherInfo['brand_tags'][] = $brand_tag->name;
            }
            $new_photographerGatherInfo = SystemServer::parsePhotographerRank($new_photographerGatherInfo);

            return $this->responseParseArray($new_photographerGatherInfo);
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 默认合集资料
     * @param PhotographerGatherRequest $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Exception
     */
    public function setDefault(PhotographerGatherRequest $request)
    {
        $photographer = $this->_photographer();
        $is_default = (int)$request->is_default;
        $photographerGatherInfo = PhotographerGatherInfo::where(
            ['id' => $request->photographer_gather_info_id, 'photographer_id' => $photographer->id]
        )->where(['status' => 200])->first();
        if (!$photographerGatherInfo) {
            return $this->response->error('合集资料不存在', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            if ($is_default) {
                PhotographerGatherInfo::where(['photographer_id' => $photographer->id])->update(['is_default' => '0']);
            }
            $photographerGatherInfo->is_default = $is_default;
            $photographerGatherInfo->save();
            \DB::commit();//提交事务

            return $this->response()->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }
}
