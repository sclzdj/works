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
use App\Model\Index\OrderInfo;
use App\Model\Index\Photographer;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
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

    public function addinvite(Request $request){
        $photographer = $this->_photographer($request->photographer_id);
        $user = User::where(['photographer_id' => $photographer->id])->first();
        \DB::beginTransaction();
        try {
            if ($user->status < 3){
                $user->status = 3;
                $user->save();
            }
            $photographer->invite_times = $request->invite_times;
            $photographer->save();

            $reword = InviteReward::where(['photographer_id' => $photographer->id])->first();
            if (!$reword) {
                $reword = InviteReward::create();
                $reword->photographer_id = $photographer->id;
                //设置勋章为白云勋章
                //设置勋章为白云勋章
                $reword->medal = 'baicloud';
                $reword->baicloud_time = date('Y-m-d H:i:s');
                $reword->save();
            }

        }catch (\Exception $e){
            \DB::rollBack();
            $this->response()->error('添加错误！', 500);
        }

        \DB::commit();

        $app = app('wechat.official_account');
        $template_id = 'P6LeOUTLcrmuYw6w8NU2JSsOQPssXdR9wiq83eDwHPU';
        if ($user->gh_openid){
            $tmr = $app->template_message->send(
                [
                    'touser' => $user->gh_openid,
                    'template_id' => $template_id,
                    'url' => config('app.url'),
                    'miniprogram' => [
                        'appid' => config('custom.wechat.mp.appid'),
                        'pagepath' => '/subPage/manage/manage',
                    ],
                    'data' => [
                        'first' => '邀请朋友功能已开通！',
                        'keyword1' => $photographer->name,
                        'keyword2' => '邀请朋友使用云作品',
                        'keyword3' =>  date('Y/m/d H:i:s'),
                        'remark' => '邀请1人赚50，邀请3人可回本，点击开始邀请',
                    ],
                ]
            );
        }

        if ($photographer->mobile){
            $third_type = config('custom.send_short_message.third_type');
            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
            if ($third_type == 'ali') {
                AliSendShortMessageServer::quickSendSms(
                    $photographer->mobile,
                    $TemplateCodes,
                    'success_invite_qualif'
                );
            }
        }


        return $this->response->noContent();
    }

    public function getinviteinfo(Request $request){
        $user = InviteList::join('photographers', 'invite_list.parent_photographer_id', '=', 'photographers.id')->select(
           'photographers.*'
        )->where(['photographer_id' => $request->photographer_id])->first();

        return $this->responseParseArray($user);
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

        if ($guest->status != 0){
            return $this->response->error('你已经是云作品的用户了', 500);
        }


        $cloud = SystemServer::cloudMoneyChange($photographer->id);

        \DB::beginTransaction();
        try {
            $guest->status = 1;
            //设定用户来源为3 用户邀请
            $guest->source = 3;
            if (!$photographer->famoususer_id){
                $photographer->decrement('invite_times');
            }
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
                $reword->photographer_id = $photographer->id;
                //设置勋章为白云勋章
                $reword->medal = 'baicloud';
                $reword->baicloud_time = date('Y-m-d H:i:s');
            }else{
                $medal = InviteSetting::getMedal($photographer->id);

                if ($medal){
                    if ($reword->medal != $medal['medal']){
                        if ($medal['medal'] == 'baicloud'){
                            $reword->baicloud_time = date('Y-m-d H:i:s');
                        }
                        if ($medal['medal'] == 'qincloud'){
                            $reword->qincloud_time = date('Y-m-d H:i:s');
                        }
                        if ($medal['medal'] == 'juancloud'){
                            $reword->juancloud_time = date('Y-m-d H:i:s');
                        }
                        if ($medal['medal'] == 'jicloud'){
                            $reword->jicloud_time = date('Y-m-d H:i:s');
                        }
                    }
                }

            }

            $reword->save();

            #检查请求邀请表是否有数据,有的话把状态改为1  已邀请
            $invitefavor = InviteFavour::where(['favour_photographer_id' => $photographer->id])->where(['request_photographer_id' => $guest->photographer_id])->where(['status' => 0])->first();
            if ($invitefavor){
                $invitefavor->status = 1;
                $invitefavor->final_photographer_id =  $photographer->id;
                $invitefavor->save();
            }


            $guest->is_invite = 1;
            $guest->save();
            $photographer->save();
        }catch (\Exception $e){
            \DB::rollBack();

            return $this->response->error("邀请失败!", 500);
        }

        \DB::commit();

        return $this->response->noContent();

    }

    public function manage2(Request $request){
        $photographer = $this->_photographer($request->photographer_id);
        $frontuser = InviteList::join('users', 'users.photographer_id', '=', 'invite_list.photographer_id')->join('order_info', 'order_info.pay_id', '=', 'users.id')->join('photographers', 'photographers.id', '=', 'users.photographer_id')->select(
            'photographers.id',
            'photographers.name',
            'photographers.avatar',
            'users.purePhoneNumber'
        )->where(['order_info.money' => 9, 'order_info.status' => 1])->where(['invite_list.parent_photographer_id' => $photographer->id])->get();

        $payuser = InviteList::join('users', 'users.photographer_id', '=', 'invite_list.photographer_id')->join('order_info', 'order_info.pay_id', '=', 'users.id')->join('photographers', 'photographers.id', '=', 'users.photographer_id')->select(
            'photographers.id',
            'order_info.money',
            'photographers.name',
            'photographers.avatar',
            'users.purePhoneNumber'
        )->whereRaw('order_info.status=1')->where(['invite_list.parent_photographer_id' => $photographer->id])->get();
        $payuser_count = $payuser->count();

        $cardpay_user = InviteList::join('pay_card', 'pay_card.photographer_id', '=', 'invite_list.photographer_id')->join('users', 'users.photographer_id', '=', 'invite_list.photographer_id')->join('photographers', 'photographers.id', '=', 'invite_list.photographer_id')->select(
            'photographers.id',
            'photographers.name',
            'photographers.avatar',
            'users.purePhoneNumber'
        )->where(['invite_list.parent_photographer_id' => $photographer->id])->get();
//        if ($payuser and $cardpay_user){
//            $payuser = $payuser->toArray();
//            $cardpay_user = $cardpay_user->toArray();
//            $payuser = array_merge($payuser, $cardpay_user);
//        }


        $nopayuser = InviteList::join('users', 'users.photographer_id', '=', 'invite_list.photographer_id')->join('photographers', 'photographers.id', '=', 'users.photographer_id')->select(
            'photographers.id',
            'photographers.name',
            'photographers.avatar',
            'users.purePhoneNumber'
        )->where(['users.identity' => 0])->where(['invite_list.parent_photographer_id' => $photographer->id])->get();

        InviteList::where(['parent_photographer_id' => $photographer->id])->update(['is_read' => 1]);
        OrderInfo::whereRaw("pay_id in (select users.id from invite_list inner join users ON users.photographer_id=invite_list.photographer_id where invite_list.parent_photographer_id=$photographer->id)")->update(['order_info.is_read' => 1]);

        $data = [
            'frontusercount' => $frontuser->count(),
            'frontuser' => $frontuser,
            'payusermoney' => $payuser_count * 50,
            'cardpay_user' => $cardpay_user,
            'payuser' => $payuser,
            'nopayuser' => $nopayuser
        ];
        return $this->responseParseArray($data);
    }


    public function updatealert(Request $request){
        $photographer = $this->_photographer($request->photographer_id);
        $medal = $request->medal;

        $reword = InviteReward::where(['photographer_id' => $photographer->id])->first();
        if ($medal == 'baicloud'){

            $reword->baicloud_alert = 1;
        }
        if ($medal == 'qincloud'){
            $reword->qincloud_alert = 1;
        }
        if ($medal == 'juancloud'){
            $reword->juancloud_alert = 1;
        }
        if ($medal == 'jicloud'){
            $reword->jicloud_alert = 1;
        }
        $reword->save();


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

        $count = User::where(['identity' => 1])->count();
        $data = [
            'photographer' => $photographerfields,
            'expiretime' => $settings->expiretime,
            'invitecount' => $invitecount,
            'reward' => $reword->toArray(),
            'user_count' => $count,
            'freemoney' => $freemoney
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
            'famoususers.cover',
            'famoususers.video',
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
     * 访问总未读数查询
     * @return mixed
     */
    public function unreadCount(UserRequest $request)
    {
        $photographer = Photographer::where(['id' => $request->photographer_id])->first();
        $count1 = InviteList::where(['parent_photographer_id' => $photographer->id, 'is_read' => 0])->count();
        $count2 = InviteList::join('users', 'users.photographer_id', '=', 'invite_list.photographer_id')->join('order_info', 'order_info.pay_id', '=', 'users.id')->where(['parent_photographer_id' => $photographer->id, 'order_info.is_read' => 0])->count();
        $all_unread_count = $count1 + $count2;

        return $this->responseParseArray(compact('all_unread_count'));
    }


    public function withdrawal(UserRequest $request){
        $photographer = $this->_photographer($request->photographer_id);
        $user = User::where(['photographer_id' => $photographer->id])->first();
        $invite = InviteReward::where(['photographer_id' => $photographer->id])->first();
        if (!$invite){
            return $this->response->error('没有邀请奖励!', 500);
        }
        $invite->is_withdrawal = 1;
        $invite->withdrawal_money = $invite->money;
        $invite->money = 0.00;
        $invite->save();

        $app = app('wechat.official_account');
        $template_id = 'Uae2KCcaaqlA8NgTBk9D3xQrRoX_LQln8qRD7PIEKTw';
        if ($user->gh_openid){
            $tmr = $app->template_message->send(
                [
                    'touser' => $user->gh_openid,
                    'template_id' => $template_id,
                    'url' => config('app.url'),
                    'miniprogram' => [
                        'appid' => config('custom.wechat.mp.appid'),
                        'pagepath' => '/subPage/manage/manage',
                    ],
                    'data' => [
                        'first' => '提现申请已提交！',
                        'keyword1' => date('Y/m/d H:i:s'),
                        'keyword2' => $invite->money,
                        'remark' => '预计3个工作日内完成审核。',
                    ],
                ]
            );
        }

        if ($photographer->mobile){
            $third_type = config('custom.send_short_message.third_type');
            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
            if ($third_type == 'ali') {
                AliSendShortMessageServer::quickSendSms(
                    $photographer->mobile,
                    $TemplateCodes,
                    'withdrawal_reply'
                );
            }
        }
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
