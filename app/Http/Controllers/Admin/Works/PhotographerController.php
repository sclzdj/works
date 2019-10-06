<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\PhotographerRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\User;
use App\Servers\ArrServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;

class PhotographerController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $filter = [
            'id' => $request['id'] !== null ?
                $request['id'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'province' => $request['province'] !== null ?
                $request['province'] :
                '',
            'city' => $request['city'] !== null ?
                $request['city'] :
                '',
            'area' => $request['area'] !== null ?
                $request['area'] :
                '',
            'photographer_rank_id' => $request['photographer_rank_id'] !== null ?
                $request['photographer_rank_id'] :
                '',
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                '',
            'wechat' => $request['wechat'] !== null ?
                $request['wechat'] :
                '',
            'created_at_start' => $request['created_at_start'] !== null ?
                $request['created_at_start'] :
                '',
            'created_at_end' => $request['created_at_end'] !== null ?
                $request['created_at_end'] :
                '',
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'id',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'asc',
        ];
        $where = [];
        if ($filter['id'] !== '') {
            $where[] = ['id', 'like', '%'.$filter['id'].'%'];
        }
        if ($filter['name'] !== '') {
            $where[] = ['name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['province'] !== '') {
            $where[] = ['province', '=', $filter['province']];
        }
        if ($filter['city'] !== '') {
            $where[] = ['city', '=', $filter['city']];
        }
        if ($filter['area'] !== '') {
            $where[] = ['area', '=', $filter['area']];
        }
        if ($filter['mobile'] !== '') {
            $where[] = ['mobile', 'like', '%'.$filter['mobile'].'%'];
        }
        if ($filter['wechat'] !== '') {
            $where[] = ['wechat', 'like', '%'.$filter['wechat'].'%'];
        }
        if ($filter['created_at_start'] !== '' &&
            $filter['created_at_end'] !== ''
        ) {
            $where[] = [
                'created_at',
                '>=',
                $filter['created_at_start']." 00:00:00",
            ];
            $where[] = [
                'created_at',
                '<=',
                $filter['created_at_end']." 23:59:59",
            ];
        } elseif ($filter['created_at_start'] === '' &&
            $filter['created_at_end'] !== ''
        ) {
            $where[] = [
                'created_at',
                '<=',
                $filter['created_at_end']." 23:59:59",
            ];
        } elseif ($filter['created_at_start'] !== '' &&
            $filter['created_at_end'] === ''
        ) {
            $where[] = [
                'created_at',
                '>=',
                $filter['created_at_start']." 00:00:00",
            ];
        }
        $Photographer = Photographer::where($where)->where(['status' => 200]);
        if ($filter['photographer_rank_id'] !== '') {
            $photographerRanks = PhotographerRank::where(['pid' => $filter['photographer_rank_id']])->orderBy(
                'sort',
                'asc'
            )->get()->toArray();
            $photographerRankIds = ArrServer::ids($photographerRanks);
            $photographerRankIds[] = $filter['photographer_rank_id'];
            $Photographer = $Photographer->whereIn('photographer_rank_id', $photographerRankIds);
        }
        $photographers = $Photographer->orderBy($orderBy['order_field'], $orderBy['order_type'])->paginate(
            $pageInfo['pageSize']
        );
        foreach ($photographers as $k => $photographer) {
            $photographers[$k]['user'] = User::where('photographer_id', $photographer->id)->first();
            $photographers[$k]['rank'] = PhotographerRank::find($photographer->photographer_rank_id);
            $photographers[$k]['province'] = SystemArea::find($photographer->province);
            $photographers[$k]['city'] = SystemArea::find($photographer->city);
            $photographers[$k]['area'] = SystemArea::find($photographer->area);
            $photographers[$k]['works_count'] = $photographer->photographerWorks()->where(['status' => 200])->count();
        }
        $provinces = SystemArea::select(SystemArea::allowFields())->where(['pid' => 0, 'level' => 1])->orderBy(
            'sort',
            'asc'
        )->get();
        $cities = [];
        if ($filter['province'] > 0) {
            $cities = SystemArea::select(SystemArea::allowFields())->where(
                ['pid' => $filter['province'], 'level' => 2]
            )->orderBy(
                'sort',
                'asc'
            )->get();
        }
        $areas = [];
        if ($filter['city'] > 0) {
            $areas = SystemArea::select(SystemArea::allowFields())->where(
                ['pid' => $filter['city'], 'level' => 3]
            )->orderBy(
                'sort',
                'asc'
            )->get();
        }
        $photographerRanks = PhotographerRank::select(PhotographerRank::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerRanks as $k => $v) {
            $photographerRanks[$k]['children'] = PhotographerRank::select(
                PhotographerRank::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }

        return view(
            '/admin/works/photographer/index',
            compact(
                'photographers',
                'pageInfo',
                'orderBy',
                'filter',
                'provinces',
                'cities',
                'areas',
                'photographerRanks'
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(PhotographerRequest $photographerRequest)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $photographer = Photographer::where(['status' => 200])->find($id);
        if (!$photographer) {
            abort(403, '参数无效');
        }
        $provinces = SystemArea::select(SystemArea::allowFields())->where(['pid' => 0, 'level' => 1])->orderBy(
            'sort',
            'asc'
        )->get();
        $cities = [];
        if ($photographer['province'] > 0) {
            $cities = SystemArea::select(SystemArea::allowFields())->where(
                ['pid' => $photographer['province'], 'level' => 2]
            )->orderBy(
                'sort',
                'asc'
            )->get();
        }
        $areas = [];
        if ($photographer['city'] > 0) {
            $areas = SystemArea::select(SystemArea::allowFields())->where(
                ['pid' => $photographer['city'], 'level' => 3]
            )->orderBy(
                'sort',
                'asc'
            )->get();
        }
        $photographerRanks = PhotographerRank::select(PhotographerRank::allowFields())->where(
            ['pid' => 0, 'level' => 1]
        )->orderBy('sort', 'asc')->get()->toArray();
        foreach ($photographerRanks as $k => $v) {
            $photographerRanks[$k]['children'] = PhotographerRank::select(
                PhotographerRank::allowFields()
            )->where(
                ['pid' => $v['id'], 'level' => 2]
            )->orderBy('sort', 'asc')->get()->toArray();
        }

        return view(
            '/admin/works/photographer/edit',
            compact('photographer', 'provinces', 'cities', 'areas', 'photographerRanks')
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(PhotographerRequest $photographerRequest, $id)
    {
        $photographer = Photographer::find($id);
        if (!$photographer) {
            return $this->response('参数无效', 403);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $photographerRequest->all();
            $data = ArrServer::null2strData($data);
            $photographer->update($data);
            $response = [
                'url' => action('Admin\Works\PhotographerController@index'),
            ];
            \DB::commit();//提交事务

            return $this->response('修改成功', 200, $response);

        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            if ($id > 0) {
                Photographer::where('id', $id)->update(['status' => 0]);
                User::where('photographer_id', $id)->update(['identity' => 0]);
                \DB::commit();//提交事务

                return $this->response('删除成功', 200);
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                Photographer::whereIn('id', $ids)->update(['status' => 0]);
                User::whereIn('photographer_id', $ids)->update(['identity' => 0]);
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

}
