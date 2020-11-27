<?php

namespace App\Http\Controllers\Admin\Works;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Requests\Admin\PhotographerRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\OperateRecord;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerGather;
use App\Model\Index\PhotographerGatherInfo;
use App\Model\Index\PhotographerInfoTag;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerRankingLog;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\RandomPhotographer;
use App\Model\Index\Star;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
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
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'gender' => $request['gender'] !== null ?
                $request['gender'] :
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
//            'wechat' => $request['wechat'] !== null ?
//                $request['wechat'] :
//                '',
//            'created_at_start' => $request['created_at_start'] !== null ?
//                $request['created_at_start'] :
//                '',
//            'created_at_end' => $request['created_at_end'] !== null ?
//                $request['created_at_end'] :
//                '',
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'photographers.created_at',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'desc',
        ];
        $where = [];
//        if ($filter['id'] !== '') {
//            $where[] = ['photographers.id', 'like', '%'.$filter['id'].'%'];
//        }
        if ($filter['name'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['gender'] !== '') {
            $where[] = ['photographers.gender', '=', $filter['gender']];
        }
        if ($filter['province'] !== '') {
            $where[] = ['photographers.province', '=', $filter['province']];
        }
        if ($filter['city'] !== '') {
            $where[] = ['photographers.city', '=', $filter['city']];
        }
        if ($filter['area'] !== '') {
            $where[] = ['photographers.area', '=', $filter['area']];
        }
        if ($filter['mobile'] !== '') {
            $where[] = ['photographers.mobile', 'like', '%'.$filter['mobile'].'%'];
        }
//        if ($filter['wechat'] !== '') {
//            $where[] = ['photographers.wechat', 'like', '%'.$filter['wechat'].'%'];
//        }
//        if ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'photographers.created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//            $where[] = [
//                'photographers.created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] === '' &&
//            $filter['created_at_end'] !== ''
//        ) {
//            $where[] = [
//                'photographers.created_at',
//                '<=',
//                $filter['created_at_end']." 23:59:59",
//            ];
//        } elseif ($filter['created_at_start'] !== '' &&
//            $filter['created_at_end'] === ''
//        ) {
//            $where[] = [
//                'photographers.created_at',
//                '>=',
//                $filter['created_at_start']." 00:00:00",
//            ];
//        }
        $Photographer = Photographer::select(
            \DB::raw('photographers.*,count(photographers.id) as works_count')
        )->leftJoin(
            'photographer_works',
            'photographers.id',
            '=',
            'photographer_works.photographer_id'
        )->where($where)->where(['photographers.status' => 200, 'photographer_works.status' => 200]);

        if ($filter['photographer_rank_id'] !== '') {
            $photographerRanks = PhotographerRank::where(['pid' => $filter['photographer_rank_id']])->orderBy(
                'sort',
                'asc'
            )->get()->toArray();
            $photographerRankIds = ArrServer::ids($photographerRanks);
            $photographerRankIds[] = $filter['photographer_rank_id'];
            $Photographer = $Photographer->whereIn('photographer_rank_id', $photographerRankIds);
        }
        \DB::enableQueryLog();
        $photographers = $Photographer->orderBy($orderBy['order_field'], $orderBy['order_type'])->groupBy(
            'photographers.id'
        )->paginate(
            $pageInfo['pageSize']
        );
        dd(\DB::getQueryLog());
        foreach ($photographers as $k => $photographer) {
            $photographers[$k]['user'] = User::where('photographer_id', $photographer->id)->first();
            if (!$photographers[$k]['user']) {
                unset($photographers[$k]);
                continue;
            }
            $photographers[$k]['rank'] = PhotographerRank::find($photographer->photographer_rank_id);
            $photographers[$k]['province'] = SystemArea::find($photographer->province);
            $photographers[$k]['city'] = SystemArea::find($photographer->city);
            $photographers[$k]['area'] = SystemArea::find($photographer->area);
            $target = TargetUser::where(['user_id' => $photographers[$k]['user']->id])->leftJoin(
                    'photographer_ranks',
                    'target_users.rank_id',
                    '=',
                    'photographer_ranks.id'
                )->first();
            if ($target){
                $photographers[$k]['target'] = $target;
                if ($target->source == 3){
                    $photographers[$k]['puser'] = User::where('id', $target->pid)->first();
                }
                $photographers[$k]['source'] = $target['source'];
            }
            $photographers[$k]['level'] = 0;
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

    public function upgrade(Request $request){
        $photographer = $request->photographer_id;
        Photographer::where(['id' => $photographer])->update(['is_upgrade' => 1]);

        return response()->noContent();
    }

    public function downworks(Request $request){
        $name = $request->name;
        $where = [
            ['name', 'like', '%' . $name . '%'],
//            ['name', '=',  $name],
            ['photographer_id', '=', $request->photographer_id]
        ];
        $works = PhotographerWork::where($where)->join(
            'photographer_work_sources',
            'photographer_work_sources.photographer_work_id',
            '=',
            'photographer_works.id'
        )->select(
            'photographer_works.name',
            'photographer_work_sources.url'
        )->get()->toArray();

        foreach ($works as $work){
            echo ($work['url']) . '<br/>';
        }
    }

    public function Guest(Request $request){
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $userid = $request['photographer_id'];
//         $photographers = $Photographer->orderBy($orderBy['order_field'], $orderBy['order_type'])->groupBy(
//            'photographers.id'
//        )->paginate(
//            $pageInfo['pageSize']
//        );
        $viewrecords = ViewRecord::where(['view_records.photographer_id' => $userid])->leftjoin(
            'users',
            'view_records.user_id',
            '=',
            'users.id'
        )->leftjoin(
            'photographer_works',
            'users.photographer_id',
            '=',
            'photographer_works.photographer_id'
        )->select(
            \DB::raw('count(photographer_works.id) as works_count'),
            'view_records.*',
            'users.phoneNumber',
            'users.id as uid',
            'users.nickname'
        )->groupBy(
            'photographer_works.photographer_id'
        )->paginate(
            $pageInfo['pageSize']
        )->toArray();

        foreach ($viewrecords as $value){
            var_dump($value);
        }

        exit();
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
        //验证手机号的唯一性
        $other_photographer = Photographer::where('id', '!=', $photographer->id)->where(
            ['mobile' => $photographerRequest->mobile, 'status' => 200]
        )->first();
        if ($other_photographer) {
            return $this->response('该手机号已经创建过云作品', 500);
        }
        \DB::beginTransaction();//开启事务
        try {
            $data = $photographerRequest->all();
            $data = ArrServer::null2strData($data);
            $user = User::where('photographer_id', $photographer->id)->first();
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
                PhotographerWork::where('photographer_id', $id)->update(['status' => 400]);
                User::where('photographer_id', $id)->update(['identity' => 0, 'is_formal_photographer' => 0]);
                Star::where('photographer_id', $id)->delete();
                ViewRecord::where('photographer_id', $id)->delete();
                Visitor::where('photographer_id', $id)->delete();
                OperateRecord::where('photographer_id', $id)->delete();
                PhotographerRankingLog::where('photographer_id', $id)->delete();
                RandomPhotographer::where('photographer_id', $id)->delete();
                PhotographerGather::where('photographer_id', $id)->update(['status' => 400]);
                PhotographerGatherInfo::where('photographer_id', $id)->update(['status' => 400]);
                \DB::commit();//提交事务

                return $this->response('删除成功', 200);
            } else {
                $ids = is_array($request->ids) ?
                    $request->ids :
                    explode(',', $request->ids);
                Photographer::whereIn('id', $ids)->update(['status' => 0]);
                PhotographerWork::whereIn('photographer_id', $ids)->update(['status' => 400]);
                User::whereIn('photographer_id', $ids)->update(['identity' => 0, 'is_formal_photographer' => 0]);
                Star::whereIn('photographer_id', $ids)->delete();
                ViewRecord::whereIn('photographer_id', $ids)->delete();
                Visitor::whereIn('photographer_id', $ids)->delete();
                OperateRecord::whereIn('photographer_id', $ids)->delete();
                PhotographerRankingLog::whereIn('photographer_id', $ids)->delete();
                RandomPhotographer::whereIn('photographer_id', $ids)->delete();
                PhotographerGather::whereIn('photographer_id', $ids)->update(['status' => 400]);
                PhotographerGatherInfo::whereIn('photographer_id', $ids)->update(['status' => 400]);
                \DB::commit();//提交事务

                return $this->response('批量删除成功', 200);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->eResponse($e->getMessage(), 500);
        }
    }

    /**
     * 用户海报
     * @param Request $request
     */
    public function poster(Request $request)
    {
        $posters = Photographer::poster2($request->id);

        return $this->response('请求成功', 200, $posters['url'.$request->num]);
    }

    /**
     * 用户图库
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|void
     */
    public function gallery(Request $request)
    {
        $photographer = Photographer::where(['id' => $request->id, 'status' => 200])->first();
        if (!$photographer) {
            return abort(404, '用户不存在');
        }
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];
        $fields = array_map(
            function ($v) {
                return 'photographer_work_sources.'.$v;
            },
            PhotographerWorkSource::allowFields()
        );
        $fields[]='photographer_works.customer_name';
        $photographerWorkSources = PhotographerWorkSource::select(
            $fields
        )->join(
            'photographer_works',
            'photographer_work_sources.photographer_work_id',
            '=',
            'photographer_works.id'
        )->where(
            [
                'photographer_works.photographer_id' => $photographer->id,
                'photographer_work_sources.status' => 200,
                'photographer_works.status' => 200,
                'photographer_work_sources.type' => 'image',
            ]
        )->orderBy(
            'photographer_works.roof',
            'desc'
        )->orderBy(
            'photographer_works.created_at',
            'desc'
        )->orderBy(
            'photographer_works.id',
            'desc'
        )->orderBy(
            'photographer_work_sources.sort',
            'asc'
        )->paginate(
            $pageInfo['pageSize']
        );
        foreach ($photographerWorkSources as $k => $photographerWorkSource) {
            $photographerWorkSources[$k]['thumb_url'] = SystemServer::getPhotographerWorkSourceThumb(
                $photographerWorkSource
            );
        }

        return view(
            '/admin/works/photographer/gallery',
            compact(
                'photographer',
                'photographerWorkSources',
                'pageInfo'
            )
        );
    }

}
