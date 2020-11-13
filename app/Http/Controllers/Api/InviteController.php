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
use App\Model\Index\Photographer;
use App\Model\Index\TargetUser;
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
        $guest = User::where(['photographer_id' => $request->request_photographer_id])->first();

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
            //设定用户来源为5 用户页点击
            $guest->source = 5;
            $photographer->decrement('invite_times');
            #邀请列表每次邀请一个人添加一条记录
            $invite = InviteList::create();
            $invite->photographer_id = $guest->photographer_id;
            $invite->parent_photographer_id = $photographer->id;
            $invite->created_at = date('Y-m-d H:i:s');
            $invite->save();

            #邀请奖励，邀请一个人添加相应奖励
            $reword = InviteReward::where(['photographer_id' => $photographer->id])->first();
            if (!$reword){
                $reword = InviteReward::create();
                $reword->cloud = 1;
                $reword->cloud_count = 1;
                //设置勋章为白云勋章
                $reword->medal = 'baicloud';

            }else{
                $medal = InviteSetting::getMedal($photographer->id);
                $reword->medal = $medal['medal'];

                $reword->increment('cloud');
                $reword->increment('cloud_count');

            }

            $reword->save();

            #检查请求邀请表是否有数据,有的话把状态改为1  已邀请
            $invitefavor = InviteFavour::where(['favour_photographer_id' => $photographer->id])->where(['request_photographer_id' => $guest->photographer_id])->where(['status' => 0])->first();
            if ($invitefavor){
                $invitefavor->status = 1;
                $invitefavor->final_photographer_id =  $photographer->id;
                $invitefavor->save();
            }



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

        $title = "云作品内测用户";
        if ($photographer['famoususer_id']){
            $rank = FamousRank::join('photographer_ranks', 'photographer_ranks.id', '=', 'famoususer_rank.photographer_rank_id')->where(['photographer_id' => $photographer->id])->select('photographer_ranks.name')->first();
            $title = $rank->name . '摄影领域KOL';
        }

        $target = TargetUser::join('users', 'users.id', '=', 'target_users.user_id')->where(['users.photographer_id' => $photographer->id])->first();
        $apply = 0;
        if ($target){
            $apply = 1;
        }
        $settings = InviteSetting::first();
        $photographerfields = [
            'photographer_id' => $photographer->id,
            'name' => $photographer->name,
            'avatar' => $photographer->avatar,
            'title' => $title,
            'apply' => $apply,
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
        $invitefavor = InviteFavour::join(
            'photographers',
            'photographers.id',
            '=',
            'invite_favour.request_photographer_id'
        )->select(
            'invite_favour.*',
            'photographers.name',
            'photographers.avatar'
        )->where(['favour_photographer_id' => $photographer->id])->paginate(
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

        $lists = InviteList::join(
            'photographers',
            'photographers.id',
            '=',
            'invite_list.photographer_id'
        )->select(
            'photographers.name',
            'photographers.avatar',
            'photographers.status',
            'invite_list.photographer_id',
            'invite_list.parent_photographer_id',
            \DB::raw("(select if((select favour_photographer_id from invite_favour where invite_favour.request_photographer_id=photographer_id and invite_favour.final_photographer_id=parent_photographer_id)=parent_photographer_id, 1, 0.5)) as cloud")
        )->where(['invite_list.parent_photographer_id' => $request->photographer_id])->paginate(
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

        $orderbyRaw = 'invitecount desc';

        $rank = $request->rank;
        $famous = Photographer::join(
            'famoususers',
            'famoususers.id',
            '=',
            'photographers.famoususer_id'
        );
        $where = [];
        if ($rank){
            $famous->join(
                'famoususer_rank',
                'famoususer_rank.photographer_id',
                '=',
                'photographers.id'
            );
            $where[] = ['famoususer_rank.photographer_rank_id', '=', $rank];
            $orderbyRaw = 'famoususer_rank.sort, invitecount desc';
        }
        $users = $famous->select(
            'photographers.name',
            'photographers.id',
            'famoususers.id as famoususers_id',
            'photographers.avatar',
            \DB::raw("(select if((select id from invite_favour where favour_photographer_id=photographers.id and request_photographer_id=$request->photographer_id)<>0, 1, 0)) as favour_status"),
            \DB::raw('(select count(*) from invite_list where parent_photographer_id = photographers.id) as invitecount')
        )->where(['famoususers.status' => 1])->where($where)->orderByRaw($orderbyRaw)->paginate(
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

        $favor = new InviteFavour();
        $favor->favour_photographer_id = $favour_photographer;
        $favor->request_photographer_id = $request_photographer;

        $favor->save();

        return $this->response->noContent();
    }

    /**
     * @param $request
     *  大咖领域
     */
    public function getfamousranks(){
        $lists = \DB::select('
SELECT `famoususer_rank`.`photographer_rank_id`, `photographer_ranks`.`name`
FROM
	`famoususer_rank`
	INNER JOIN `photographer_ranks` ON `photographer_ranks`.`id` = `famoususer_rank`.`photographer_rank_id`
GROUP BY
	`famoususer_rank`.`photographer_rank_id`
	 ');
        $data = [];


        return $this->responseParseArray($lists);
    }

}
