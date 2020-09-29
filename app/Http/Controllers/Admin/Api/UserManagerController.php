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
use App\Model\Index\Sources;
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
            'level' => $request['level'] !== null ?
                $request['level'] :
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
            'source' => $request['source'] !== null ?
                $request['source'] :
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
        $whereraw = "1 = 1";
        if ($filter['level'] !== '') {
            $whereraw .= ' and photographers.level in ('. $filter['level'] .')';
        }

        if ($filter['source'] !== '') {
            $where[] = ['target_users.source', '=', $filter['source']];
        }



        $Photographer = Photographer::select(
            \DB::raw('photographers.*,target_users.source,photographer_ranks.name as rank_name,target_users.rank_id,target_users.works_info'),
            \DB::raw('(( SELECT count(*) FROM photographer_works WHERE photographer_works.photographer_id = users.photographer_id )) AS works_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
        )->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->join(
            'target_users',
            'target_users.user_id',
            '=',
            'users.id'
        )->leftJoin(
            'photographer_ranks',
            'target_users.rank_id',
            '=',
            'photographer_ranks.id'
        )->leftJoin(
            'photographer_works',
            'photographers.id',
            '=',
            'photographer_works.photographer_id'
        )->where($where)->whereRaw($whereraw)->where(['photographers.status' => 200, 'photographer_works.status' => 200]);

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
//            $target = TargetUser::where(['user_id' => $photographers[$k]['user']->id])->leftJoin(
//                'photographer_ranks',
//                'target_users.rank_id',
//                '=',
//                'photographer_ranks.id'
//            )->select(
//                'target_users.*',
//                'photographer_ranks.name as rank_name'
//            )->first();
//            if ($target){
//                $photographers[$k]['target'] = $target;
//                if ($target->source == 3){
//                    $photographers[$k]['puser'] = User::where('id', $target->pid)->first();
//                }
//                $photographers[$k]['source'] = $target['source'];
//            }
//            $photographers[$k]['level'] = 0;
        }

        return $this->responseParseArray($photographers);
    }

    /**
     * 游客
     * @param PhotographerRequest $request
     * @return mixed
     */
    public function users(PhotographerRequest $request){

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
                'users.created_at',
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

        $users = User::select(
            \DB::raw('(( SELECT count(*) FROM photographer_works WHERE photographer_works.photographer_id = users.photographer_id )) AS works_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors'),
            'users.id',
            'users.photographer_id',
            'users.purePhoneNumber',
            'users.nickname',
            'photographers.name',
            'photographers.updated_at',
            'photographers.created_at',
            'users.avatar'
        )->join(
            'photographers',
            'photographers.id',
            '=',
            'users.photographer_id'
        )->where($where)->orderBy($orderBy['order_field'], $orderBy['order_type'])->paginate(
            $pageInfo['pageSize']
        );

        return $this->responseParseArray($users);
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
//            \DB::raw('count(photographer_works.id) as works_count'),
            \DB::raw('(( SELECT count(*) FROM photographer_works WHERE photographer_works.photographer_id = users.photographer_id )) AS works_count'),
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
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];


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
            'source' => $request['source'] !== null ?
                $request['source'] :
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

        if ($filter['source'] != '') {
            $where[] = ['target_users.source', '=',  $filter['source']];
        }
        if ($filter['status'] != '') {
            $where[] = ['target_users.status', '=',  $filter['status']];
        }

        if (!empty($filter['phone'])) {
            $where[] = ['users.phoneNumber', 'like', '%' . $filter['phone'] . '%'];
        }

        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'target_users.created_at',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                'desc',
        ];

        $data = TargetUser::where($where)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->leftJoin('photographer_ranks', 'photographer_ranks.id', '=', 'target_users.rank_id')
            ->leftJoin('photographers', 'photographers.id', '=', 'users.photographer_id')
            ->select('target_users.*', 'invote_codes.code', 'invote_codes.type as invote_type',
                'invote_codes.status as invote_status',
                'photographers.name',
                'invote_codes.type as invote_type',
                'users.nickname', 'users.phoneNumber',
                'users.city',
                'users.avatar',
                'users.province', 'users.gender', 'users.photographer_id',
                'photographer_ranks.name as rank_name',
                \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as works_count'),
                \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
            )->groupBy('users.photographer_id')->orderBy($orderBy['order_field'], $orderBy['order_type'])->paginate(
                $pageInfo['pageSize']
            );;

//            var_dump($data);exit();

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


        return $this->responseParseArray($data);
    }

    /*** 添加来源
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function sourcestore(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        if ($id){
            Sources::where(['id' => $id])->update(['name' => $name]);
        }else{
            $result = Sources::insert([
                'name' => $name
            ]);
        }

        return response()->noContent();
    }

    /**
     * 添加邀请次数
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function addinvite(Request $request){
        $photographer_id = $request['photographer_id '];
        Photographer::where(['id' => $photographer_id])->increment('invite', 3);

        return response()->noContent();
    }

}
