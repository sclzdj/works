<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Requests\Admin\PhotographerRequest;
//use App\Http\Controllers\Admin\BaseController;
use App\Http\Controllers\Api\BaseController;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Servers\ArrServer;
use Illuminate\Http\Request;

/**
 *
 * Class MyController
 * @package App\Http\Controllers\Api
 */
class UserManagerController extends BaseController
{
    /**
     *
     * @param UserRequest $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function photographers(PhotographerRequest $request){
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $filter = [
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',

            'photographer_rank_id' => $request['photographer_rank_id'] !== null ?
                $request['photographer_rank_id'] :
                '',
            'userid' => $request['userid'] !== null ?
                $request['userid'] :
                '',
            'username' => $request['username'] !== null ?
                $request['username'] :
                '',
            'photographerid' => $request['photographerid'] !== null ?
                $request['photographerid'] :
                '',
            'photographername' => $request['photographername'] !== null ?
                $request['photographername'] :
                '',

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
        if ($filter['mobile'] !== '') {
            $where[] = ['users.phoneNumber', '=', $filter['mobile']];
        }
        if ($filter['userid'] !== '') {
            $where[] = ['users.id', '=', $filter['userid']];
        }
        if ($filter['username'] !== '') {
            $where[] = ['users.nickname', 'like', '%'.$filter['username'].'%'];
        }
        if ($filter['photographerid'] !== '') {
            $where[] = ['photographers.id', '=', $filter['photographerid']];
        }
        if ($filter['photographername'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['photographername'].'%'];
        }


        $Photographer = Photographer::select(
            \DB::raw('photographers.*,count(photographers.id) as works_count')
        )->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
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
        $photographers = $Photographer->orderBy($orderBy['order_field'], $orderBy['order_type'])->groupBy(
            'photographers.id'
        )->paginate(
            $pageInfo['pageSize']
        );
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

        return $this->responseParseArray($photographers);
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
        );


        return $this->responseParseArray($viewrecords);
    }

    public function targetuserlist(Request $request)
    {
        $page = $request->input('page', 1);
        $form = $request->input('form', []);
        $size = 20;
        $page = ($page - 1) * $size;

        $where = [];

        $filter = [
//            'id' => $request['id'] !== null ?
//                $request['id'] :
//                '',
            'nickname' => $request['nickname'] !== null ?
                $request['nickname'] :
                '',
            'user_id' => $request['userid'] !== null ?
                $request['userid'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'photographer_id' => $request['photographerid'] !== null ?
                $request['photographerid'] :
                '',
            'sources' => $request['sources'] !== null ?
                $request['sources'] :
                '',
            'status' => $request['status'] !== null ?
                $request['status'] :
                '',
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                '',

        ];
        if ($filter['name'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['nickname'] !== '') {
            $where[] = ['users.nickname', 'like', '%'.$filter['nickname'].'%'];
        }
        if ($filter['user_id'] !== '') {
            $where[] = ['users.user_id', '=', $filter['user_id']];
        }
        if ($filter['photographer_id'] !== '') {
            $where[] = ['users.photographer_id', '=', $filter['name']];
        }
        if ($filter['mobile'] !== '') {
            $where[] = ['users.phoneNumber', '=', '%'.$filter['mobile'].'%'];
        }

        if ($filter['sources'] != -1) {
            $where[] = ['target_users.source', $form['sources']];
        }

        if ($filter['status'] == 1) {
            $where[] = ['target_users.status', 1];
        } elseif ($filter['status'] == 2)
            $where[] = ['target_users.status', '!=', 1];

        if ($filter['codeStatus'] != -1) {
            $where[] = ['invote_codes.status', $filter['codeStatus']];
        }

        if (!empty($form['phone'])) {
            $where[] = ['users.phoneNumber', 'like', '%' . $form['phone'] . '%'];
        }


        if ($request['order']){
            if (substr($request['order'], 0, 1) == '-'){
                $order = [substr($request['order'], 1), 'desc'];
            }else{
                $order = [substr($request['order'], 1), ""];
            }
        }else{
            $order = ["created_at", ""];
        }

        $data = TargetUser::where($where)
            ->skip($page)->take($size)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->leftJoin('photographer_ranks', 'photographer_ranks.id', '=', 'target_users.rank_id')
            ->select('target_users.*', 'invote_codes.code', 'invote_codes.type as invote_type',
                'invote_codes.status as invote_status',
                'invote_codes.type as invote_type',
                'users.nickname', 'users.phoneNumber',
                'users.city',
                'users.province', 'users.gender', 'users.photographer_id',
                'photographer_ranks.name as rank_name',
                \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as pwcount'),
                \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
            )->groupBy('users.photographer_id')->orderBy($order[0], $order[1])->get();

        foreach ($data as &$datum) {
            if ($datum['status'] == 0 && $datum['works_info']) {
                $workinfo = json_decode($datum['works_info'], 1);
                $img = array_column($workinfo, 'url');
                $datum['works_info'] = json_encode($img);
            }
            if (!$datum['is_invite']){
                $datum['status'] = 0; #未受邀
            }else{
                $datum['status'] = 1; #已受邀
            }
            $pw = PhotographerWork::where(['photographer_id' => $datum['photographer_id']])->first();
            if ($pw){
                $datum['status'] = 2; #有作品 已创建
            }else{
                $invote = InvoteCode::where(['user_id' => $datum['user_id']])->first();
                if ($invote){
                    $datum['status'] = 3; #已升级
                }
            }
        }

        $count = TargetUser::where($where)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->count();

        return $this->responseParseArray($datum);
    }

}
