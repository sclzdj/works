<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Index\PhotographerGatherRequest;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerGatherWork;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerWork;
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
            $photographerGatherWork = PhotographerGatherWork::where(
                ['photographer_gather_id' => $photographerGather['id']]
            )->orderBy('sort', 'asc')->first();
            if ($photographerGatherWork) {
                $photographerWork = PhotographerWork::where(
                    ['id' => $photographerGatherWork->photographer_work_id, 'status' => 200]
                )->first();
                if($photographerWork){
                    $photographerWork = SystemServer::parsePhotographerWorkCover($photographerWork->toArray());
                    $photographerGathers['data'][$k]['cover']=$photographerWork['cover'];
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
                    $photographerGatherWork = PhotographerGatherWork::create();
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
}
