<?php


namespace App\Http\Controllers\Api;

use App\Http\Requests\Index\UserRequest;
use App\Model\Admin\SystemConfig;
use App\Model\Index\FamousRank;
use App\Model\Index\FamousUsers;
use App\Model\Index\InviteFavour;
use App\Model\Index\InviteList;
use App\Model\Index\InviteReward;
use App\Model\Index\InviteSetting;
use App\Model\Index\User;
use App\Servers\SystemServer;
use Illuminate\Http\Request;

/**
 * 邀请码相关
 * Class InvoteCodeController
 * @package App\Http\Controllers\Api
 */
class InviteController extends BaseController
{
    public $data = [
        'result' => false,
    ];

    public function __construct()
    {

    }

    public function getinvite(Request $request){
        $photographer = $this->_photographer($request->photographer_id);
        $times = [
            'invite_times' => $photographer->invite_times
        ];
        return $this->responseParseArray($times);
    }


    /**
     * 接受邀请
     */
    public function accept(UserRequest $request){
        $photographer = $this->_photographer($request->photographer_id);
        $guest = User::where(['id' => $request->user_id])->first();

        if ($photographer['invite_times'] == 0){
            return $this->response->error('手慢了', 500);
        }

        if ($guest['status'] != 0){
            return $this->response->error('你已经是云作品的用户了', 500);
        }


        $cloud = SystemServer::cloudMoneyChange($photographer->id);

        \DB::beginTransaction();
        try {
            $guest->status = 1;
            $photographer->decrement('invite_times');
            #邀请列表每次邀请一个人添加一条记录
            $invite = InviteList::create();
            $invite->photographer_id = $guest->photographer_id;
            $invite->parent_photographer_id = $photographer->id;
            $invite->created_at = date('Y-m-d H:i:s');

            #邀请奖励，邀请一个人添加相应奖励
            $reword = InviteReward::where(['photographer_id' => $photographer->id])->first();
            if (!$reword){
                $reword = InviteReward::create();
                $reword->cloud = 1;
                $reword->cloud_count = 1;
                $reword->save();
            }
            $reword->increment('cloud');
            $reword->increment('cloud_count');

            #检查请求邀请表是否有数据,有的话把状态改为1  已邀请
            $invitefavor = InviteFavour::where(['favour_photographer_id' => $photographer->id])->where(['request_photographer_id' => $guest->photographer_id])->where(['status' => 0])->first();
            if ($invitefavor){
                $invitefavor->status = 1;
                $invitefavor->final_photographer_id =  $photographer->id;
            }
            $invitefavor->save();


            $reword->save();
            $invite->save();
            $guest->save();
            $photographer->save();
        }catch (\Exception $e){
            \DB::rollBack();

            return $this->response->error("邀请失败!", 500);
        }

        \DB::commit();

        return $this->response->noContent();

    }

    /**
     *  裂变邀请管理界面
     * @param UserRequest $request
     */
    public function manage(UserRequest $request){
        $photographer = $this->_photographer($request->photographer_id);

        $famoususer = FamousUsers::where(['photographer_id' => $photographer->id])->first();
        $title = "云作品内测用户";
        if ($famoususer){
            $rank = FamousRank::join('photographer_ranks', 'photographer_ranks.id', '=', 'famoususer_rank.photographer_rank_id')->join('famoususers','famoususers.id',  '=', 'famoususer_rank.famoususer_id')->where(['famoususers.id' => $famoususer->id])->select('photographer_ranks.name')->first();
            $title = $rank->name . '摄影领域KOL';
        }

        $settings = InviteSetting::first();
        $photographerfields = [
            'photographer_id' => $photographer->id,
            'avatar' => $photographer->avatar,
            'title' => $title,
            'invite_times' => $photographer['invite_times']
        ];

        $invitecount = InviteList::where(['parent_photographer_id' => $photographer->id])->count();

        $cloud = SystemServer::cloudMoneyChange($photographer->id);
        $reword = InviteReward::where(['photographer_id' => $photographer->id])->first();
        if (!$reword){
            $reword = InviteReward::create();
            $reword->photographer_id = $photographer->id;
            $reword->cloud = 0;
            $reword->cloud_count = 0;
            $reword->save();
        }
        $freemoney = $reword->cloud * $cloud['money'];


        $data = [
            'photographer' => $photographerfields,
            'expiretime' => $settings->expiretime,
            'invitecount' => $invitecount,
            'reward' => [
                'cloud' => $reword->cloud,
                'freemoney' => $freemoney
            ]
        ];

        return $this->responseParseArray($data);
    }

    /**
     * @param UserRequest $request
     * @return mixed
     *  请求邀请的列表
     */
    public function favors(UserRequest $request){
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $photographer = $this->_photographer($request->photographer_id);
        $invitefavor = InviteFavour::where(['favour_photographer_id' => $photographer->id])->paginate(
            $pageInfo['pageSize']
        );

        return $this->response->array($invitefavor);
    }

    /**
     * @param $request
     *  邀请列表
     */
    public function lists(UserRequest $request){
        $photographer = $this->_photographer($request->photographer_id);
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];
        $lists = InviteList::leftjoin(
            'invite_favour',
            'invite_favour.final_photographer_id',
            '=',
            'invite_list.parent_photographer_id'
        )->select(
            'invite_list.*',
            \DB::raw('if(isnull(invite_favour.id), 0.5, 1) as cloud')
        )->where(['parent_photographer_id' => $photographer->id])->paginate(
            $pageInfo['pageSize']
        );

        return $this->response->array($lists);

    }

    /**
     * @param $request
     *  大咖列表
     */
    public function famoususerslist(UserRequest $request){
        $pageInfo = [
            'pageSize' => $request['pageSize'] !== null ?
                $request['pageSize'] :
                SystemConfig::getVal('basic_page_size'),
            'page' => $request['page'] !== null ?
                $request['page'] :
                1,
        ];

        $rank = $request->rank;
        $famous = FamousUsers::join(
            'photographers',
            'photographers.id',
            '=',
            'famoususers.photographer_id'
        );
        $where = [];
        if ($rank){
            $famous->join(
                'famoususer_rank',
                'famoususer_rank.famoususer_id',
                '=',
                'famoususers.id'
            );
            $where[] = ['famoususer_rank.photographer_rank_id', '=', $rank];
        }
        $users = $famous->select(
            'photographers.name',
            'photographers.id',
            'famoususers.id as famoususers_id',
            'photographers.avatar',
            \DB::raw('(select count(*) from invite_list where parent_photographer_id = photographers.id) as invitecount')
        )->where(['famoususers.status' => 1])->where($where)->orderBy('invitecount', 'desc')->paginate(
                $pageInfo['pageSize']
            );


        return $this->response->array($users);
    }

    public function plzfavors(UserRequest $request){
        $request_photographer = $request->request_photographer_id;
        $favour_photographer = $request->favour_photographer_id;


        $favor = InviteFavour::where(['favour_photographer_id' => $favour_photographer, 'request_photographer_id' => $request_photographer])->first();

        if ($favor){
            return $this->response->error("你已经申请过，请耐心等待", 500);
        }

        $times = InviteFavour::where(['request_photographer_id' => $request_photographer])->whereTime('created_at', '>=', '00:00')->whereTime('created_at', '<=', '23:59')->count();

        if ($times >= 5){
            return $this->response->error("每天只能求带5次", 500);
        }

        $favor = new TargetUser();
        $favor->favour_photographer_id = $favour_photographer;
        $favor->request_photographer_id = $request_photographer;

        $favor->save();

        return $this->response->noContent();
    }

}
