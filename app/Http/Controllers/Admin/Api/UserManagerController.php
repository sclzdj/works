<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Requests\Admin\PhotographerRequest;
//use App\Http\Controllers\Admin\BaseController;
use App\Http\Controllers\Api\BaseController;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
use App\Model\Index\FamousRank;
use App\Model\Index\InviteList;
use App\Model\Index\InviteReward;
use App\Model\Index\InviteSetting;
use App\Model\Index\InvoteCode;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\Sources;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Model\Index\ViewRecord;
use App\Model\Index\Visitor;
use App\Model\Index\WithdrwalRecord;
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

        list($where, $whereraw) = $this->filters($request);
        $filter = [
            'photographer_rank_id' => $request['photographer_rank_id'] !== null ?
                $request['photographer_rank_id'] :
                ''
        ];
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'photographers.created_at',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                '',
        ];
        $orderBy = $orderBy['order_field'] . ' ' . $orderBy['order_type'];
        $orderBy .= ',photographers.created_at desc';

        $where[] = ['users.identity', '=', 1];

        $Photographer = Photographer::select(
            \DB::raw('photographers.*,users.source,photographer_ranks.name as rank_name,target_users.rank_id,target_users.works_info,target_users.reason'),
            \DB::raw('(( SELECT count(*) FROM photographer_works WHERE photographer_works.photographer_id = users.photographer_id )) AS works_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
        )->leftjoin(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->leftjoin(
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
        )->where($where)->whereRaw($whereraw);

        if ($filter['photographer_rank_id'] !== '') {
            $photographerRanks = PhotographerRank::where(['pid' => $filter['photographer_rank_id']])->orderBy(
                'sort',
                'asc'
            )->get()->toArray();
            $photographerRankIds = ArrServer::ids($photographerRanks);
            $photographerRankIds[] = $filter['photographer_rank_id'];
            $Photographer = $Photographer->whereIn('photographer_rank_id', $photographerRankIds);
        }
        $photographers = $Photographer->orderByRaw(\DB::raw($orderBy))->groupBy(
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
     * 统一排序
     */
    private function orderby($request, $default='target_users'){
        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                $default,
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                '',
        ];
        $orderBy = $orderBy['order_field'] . ' ' . $orderBy['order_type'];

        return $orderBy;
    }

    /**
     * 统一分页
     */
    private function page($request){
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        return [
            'pageSize' => $pageInfo['pageSize'],
            'page' => $pageInfo['page']
        ];
    }

    /** 统一过滤
     * @param $request
     * @return array
     */
    private function filters($request){
        $filter = [
            'mobile' => $request['mobile'] !== null ?
                $request['mobile'] :
                '',
            'name' => $request['name'] !== null ?
                $request['name'] :
                '',
            'nickname' => $request['nickname'] !== null ?
                $request['nickname'] :
                '',
            'level' => $request['level'] !== null ?
                $request['level'] :
                '',
            'photographer_rank_id' => $request['photographer_rank_id'] !== null ?
                $request['photographer_rank_id'] :
                '',
            'photographer_id' => $request['photographer_id'] !== null ?
                $request['photographer_id'] :
                '',
            'parent_photographer_id' => $request['parent_photographer_id'] !== null ?
                $request['parent_photographer_id'] :
                '',
            'famous_rank_id' => $request['famous_rank_id'] !== null ?
                $request['famous_rank_id'] :
                '',
            'is_withdrawal' => $request['is_withdrawal'] !== null ?
                $request['is_withdrawal'] :
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
            'status' => $request['status'] !== null ?
                $request['status'] :
                '',
            'auth' => $request['auth'] !== null ?
                $request['auth'] :
                '',
            'is_wx_authorize' => $request['is_wx_authorize'] !== null ?
                $request['is_wx_authorize'] :
                '',
            'is_famous' => $request['is_famous'] !== null ?
                $request['is_famous'] :
                '',
            'is_wx_get_phone_number' => $request['is_wx_get_phone_number'] !== null ?
                $request['is_wx_get_phone_number'] :
                '',
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
        if ($filter['famous_rank_id'] !== '') {
            $where[] = ['famoususer_rank.photographer_rank_id', '=', $filter['famous_rank_id']];
        }
        if ($filter['is_withdrawal'] !== '') {
            $where[] = ['invite_rewards.is_withdrawal', '=', $filter['is_withdrawal']];
        }
        if ($filter['photographer_id'] !== '') {
            $where[] = ['photographers.id', '=', $filter['photographer_id']];
        }
        if ($filter['parent_photographer_id'] !== '') {
            $where[] = ['invite_list.parent_photographer_id', '=', $filter['parent_photographer_id']];
        }
        if ($filter['photographername'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['photographername'].'%'];
        }
        if ($filter['name'] !== '') {
            $where[] = ['photographers.name', 'like', '%'.$filter['name'].'%'];
        }
        if ($filter['auth'] !== '') {
            $opera = '=';
            if ($filter['auth'] == 1){
                $opera = '<>';
            }
            $where[] = ['users.openid', $opera,  ''];
        }
        if ($filter['is_wx_authorize'] !== '') {
            $where[] = ['users.is_wx_authorize', '=',  $filter['is_wx_authorize']];
        }
        if ($filter['is_famous'] !== '') {
            $where[] = ['photographers.famoususer_id', '<>',  0];
        }
        if ($filter['is_wx_get_phone_number'] !== '') {
            $where[] = ['users.is_wx_get_phone_number', '=',  $filter['is_wx_get_phone_number']];
        }
        $whereraw = "1 = 1";
        if ($filter['level'] !== '') {
            $whereraw .= ' and photographers.level in ('. $filter['level'] .')';
        }

        if ($filter['source'] !== '') {
            $whereraw .= ' and users.source in ('. $filter['source'] .')';
        }
        if ($filter['status'] !== '') {
            $whereraw .= ' and users.status in ('. $filter['status'] .')';
        }

        return [$where, $whereraw];
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
        list($where, $whereraw) = $this->filters($request);

        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'users.created_at',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                '',
        ];
        $orderBy = $orderBy['order_field'] . ' ' . $orderBy['order_type'];

//        $where[] = ['users.identity', '=', 0];

        $users = User::select(
            \DB::raw('(( SELECT count(*) FROM photographer_works WHERE photographer_works.photographer_id = users.photographer_id )) AS works_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors'),
            'users.id',
            'users.photographer_id',
            'users.purePhoneNumber',
            'users.nickname',
            'photographers.name',
            'photographers.updated_at',
            'photographers.xacode',
            'photographers.created_at',
            'users.avatar'
        )->leftjoin(
            'photographers',
            'photographers.id',
            '=',
            'users.photographer_id'
        )->where($where)->whereRaw($whereraw)->orderByRaw($orderBy)->paginate(
            $pageInfo['pageSize']
        );

        return $this->responseParseArray($users);
    }

    /***访客记录
     * @param Request $request
     * @return mixed
     */
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
        $viewrecords = Visitor::where(['visitors.photographer_id' => $userid])->leftjoin(
            'users',
            'visitors.user_id',
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
            'visitors.*',
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

        list($where, $whereraw) = $this->filters($request);

        $orderBy = [
            'order_field' => $request['order_field'] !== null ?
                $request['order_field'] :
                'target_users.created_at',
            'order_type' => $request['order_type'] !== null ?
                $request['order_type'] :
                '',
        ];
        $orderBy = $orderBy['order_field'] . ' ' . $orderBy['order_type'];

        $data = TargetUser::where($where)->whereRaw($whereraw)
            ->leftJoin('invote_codes', 'invote_codes.id', '=', 'target_users.invote_code_id')
            ->leftJoin('users', 'users.id', '=', 'target_users.user_id')
            ->leftJoin('photographer_ranks', 'photographer_ranks.id', '=', 'target_users.rank_id')
            ->leftJoin('photographers', 'photographers.id', '=', 'users.photographer_id')
            ->select(
                'target_users.*',
                'users.status as user_status',
                'invote_codes.code', 'invote_codes.type as invote_type',
                'invote_codes.status as invote_status',
                'photographers.name',
                'invote_codes.type as invote_type',
                'users.nickname', 'users.phoneNumber',
                'users.city',
                'photographers.xacode',
                'users.avatar',
                'users.province', 'users.gender', 'users.photographer_id',
                'photographer_ranks.name as rank_name',
                \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as works_count'),
                \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
            )->groupBy('users.photographer_id')->orderByRaw($orderBy)->paginate(
                $pageInfo['pageSize']
            );;



        foreach ($data as &$datum) {
            if ($datum['status'] == 0 && $datum['works_info']) {
                $workinfo = json_decode($datum['works_info'], 1);
                $img = array_column($workinfo, 'url');
                $datum['works_info'] = json_encode($img);
            }
//            if (!$datum['is_invite']){
//                $datum['status'] = 0; #未受邀
//            }else{
//                $datum['status'] = 1; #已受邀
//            }
//            $pw = PhotographerWork::where(['photographer_id' => $datum['photographer_id']])->first();
//            if ($pw){
//                $datum['status'] = 2; #有作品 已创建
//            }else{
//                $invote = InvoteCode::where(['user_id' => $datum['user_id']])->first();
//                if ($invote){
//                    $datum['status'] = 3; #已升级
//                }
//            }
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

    /**
     * 获取用户状态数量
    */
    public function usertypecount(){
        $list = TargetUser::select(
            \DB::raw('status,count(*) as count')
        )->groupBy('status')->get();
        return $this->responseParseArray($list);

    }

    public function getContact(){
//        \DB::enableQueryLog(); //        dd(\DB::getQueryLog());exit();
        $workids = PhotographerWork::where(['status' => 200])->whereRaw(
            \DB::raw('photographer_id not in (2,3,5849,5013,7083)')
        )->get()->pluck(['id'])->toArray();
//        dd(\DB::getQueryLog());exit();
        $errorArr = [];
        foreach ($workids as $work){
            $work = PhotographerWork::where(['id' => $work])->first();
            $sources = $work->photographerWorkSources()->get();
            $errorArr[$work->id] = [];
            if ($sources){
                foreach ($sources as $source){
                    if ($source->key == ''){
                        continue;
                    }
                    $dusources = PhotographerWorkSource::where(['key' => $source->key])->where( 'photographer_work_id', '<>', $work->id)->get();
                    if ($dusources){
                        foreach ($dusources as $dusource){
                            $duwork = PhotographerWork::where(['id' => $dusource->photographer_work_id])->first();
                            if ($duwork->status == 200){
                                if (!array_key_exists($dusource->photographer_work_id, $errorArr)){
                                    array_push($errorArr[$work->id], $dusource->photographer_work_id);
                                }

                            }
                        }
                    }
                }
            }
            if (count($errorArr[$work->id]) == 0){
                unset($errorArr[$work->id]);
            }else{
                $errorArr[$work->id]= array_unique($errorArr[$work->id]);
            }
        }
        print_r($errorArr);
    }

    public function inviteusers(Request $request){
        $pageInfo = $this->page($request);
        $orderBy = $this->orderby($request, 'invite_list.created_at desc');

        list($where, $whereraw) = $this->filters($request);

        $list = InviteList::join('photographers', 'photographers.id', '=', 'invite_list.photographer_id')->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->select(
            'users.id as user_id',
            'photographers.id as photographer_id',
            'photographers.xacode',
            'photographers.name',
            'photographers.avatar',
            'invite_list.parent_photographer_id',
            'users.status',
            'users.source',
            'users.nickname',
            'users.phoneNumber',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as works_count'),
            \DB::raw('((select name from photographers where id=invite_list.parent_photographer_id)) as parent_photographers_name'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
        )->where($where)->whereRaw($whereraw)->groupBy('users.photographer_id')->orderByRaw($orderBy)->paginate(
            $pageInfo['pageSize']
        );

        return $this->responseParseArray($list);

    }

    public function allinviters(Request $request){
        $pageInfo = $this->page($request);
        $orderBy = $this->orderby($request, 'photographers.created_at desc');

        list($where, $whereraw) = $this->filters($request);
        $lists = Photographer::join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->leftjoin(
            'target_users',
            'target_users.user_id',
            '=',
            'users.id'
        )->leftJoin(
            'photographer_ranks',
            'target_users.rank_id',
            '=',
            'photographer_ranks.id'
        )->select(
            'users.id as user_id',
            'photographers.id as photographer_id',
            'photographers.xacode',
            'photographers.avatar',
            'photographers.name',
            'photographers.invite_times',
            'target_users.rank_id',
            'target_users.works_info',
            'target_users.reason',
            'photographer_ranks.name as photographer_rank_name',
            'users.status',
            'users.source',
            'users.nickname',
            'users.phoneNumber',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as works_count'),
            \DB::raw('((select count(*) from invite_list where invite_list.parent_photographer_id=photographers.id)) as invite_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
        )->where($where)->whereRaw($whereraw)->groupBy('users.photographer_id')->orderByRaw($orderBy)->paginate(
            $pageInfo['pageSize']
        );

        return $this->responseParseArray($lists);
    }

    public function famousinvites(Request $request){
        $pageInfo = $this->page($request);


        list($where, $whereraw) = $this->filters($request);

        $orderBy = 'famoususer_rank.sort,famoususers.created_at desc' ;

        $lists = Photographer::join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->leftjoin(
            'famoususer_rank',
            'famoususer_rank.photographer_id',
            '=',
            'users.photographer_id'
        )->join(
            'famoususers',
            'photographers.famoususer_id',
            '=',
            'famoususers.id'
        )->select(
            'users.id as user_id',
            'photographers.id as photographer_id',
            'photographers.xacode',
            'photographers.name',
            'photographers.avatar',
            'photographers.invite_times',
            'famoususer_rank.photographer_rank_id as famous_rank_id',
            'users.status',
            'users.source',
            'users.nickname',
            'users.phoneNumber',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=users.photographer_id)) as works_count'),
            \DB::raw('((select count(*) from invite_list where invite_list.parent_photographer_id=photographers.id)) as invite_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=users.photographer_id)) as vistors')
        )->where('photographers.famoususer_id', '<>', 0)->where($where)->whereRaw($whereraw)->groupBy('users.photographer_id')->orderByRaw($orderBy)->paginate(
            $pageInfo['pageSize']
        );

        return $this->responseParseArray($lists);

    }

    public function getfamousranks(){
        $lists = \DB::select('SELECT a.*,photographer_ranks.name as pid_name FROM (
SELECT  `photographer_ranks`.*,(select count(*) from `famoususer_rank` where photographer_rank_id=photographer_ranks.id) as rank_count FROM  `photographer_ranks` )a INNER JOIN `photographer_ranks` ON a.pid = photographer_ranks.id ');
        $data = [];
        foreach ($lists as $list){
            $flag = false;
            foreach ($data as &$datum){
                if ($list->pid_name == $datum['pid_name']){
                    $flag = true;
                    $tmp  =  [
                        'photographer_rank_id' => $list->id,
                        'name' => $list->name,
                        'rank_count' => $list->rank_count
                    ];
                    array_push($datum['child'], $tmp);
                }
            }
            if (!$flag){

                $tmp = [
                    'pid_name' => $list->pid_name,
                    'pid' => $list->pid,
                    'child' => [
                        [
                            'photographer_rank_id' => $list->id,
                            'name' => $list->name,
                            'rank_count' => $list->rank_count
                        ]
                    ]
                ];


                array_push($data, $tmp);

            }
        }

        return $this->responseParseArray($data);
    }

    public function clouds(Request $request){
        $pageInfo = $this->page($request);
        $orderBy = $this->orderby($request, 'photographers.created_at desc');

        list($where, $whereraw) = $this->filters($request);

        $lists = InviteReward::join('photographers', 'photographers.id', '=', 'invite_rewards.photographer_id')->join(
            'users',
            'users.photographer_id',
            '=',
            'photographers.id'
        )->select(
            'invite_rewards.photographer_id',
            'invite_rewards.medal',
            'invite_rewards.cloud',
            'invite_rewards.cloud_count',
            'invite_rewards.money',
            'invite_rewards.money_count',
            'invite_rewards.withdrawal_money_count',
            'invite_rewards.withdrawal_cloud_count',
            'invite_rewards.is_withdrawal',
            'users.id as user_id',
            'users.phoneNumber',
            'photographers.name',
            'photographers.xacode',
            'photographers.avatar',
            \DB::raw('((select count(photographer_works.id) from photographer_works where photographer_works.photographer_id=invite_rewards.photographer_id)) as works_count'),
            \DB::raw('((select count(*) from visitors where visitors.photographer_id=invite_rewards.photographer_id)) as vistors')
        )->where($where)->whereRaw($whereraw)->paginate(
            $pageInfo['pageSize']
        );


        foreach ($lists as &$list){
            $records = WithdrwalRecord::where(['photographer_id' => $list['photographer_id']])->select(
                'money',
                'cloud',
                'updated_at'
            )->get();
            $list['withdrawl'] = $records;
        }


        return $this->responseParseArray($lists);
    }

    public function getsettings(Request $request){
        $invite = InviteSetting::find(1);
        $invite->cloudmedal = json_decode($invite->cloudmedal, true);

        return $this->responseParseArray($invite);

    }

}
