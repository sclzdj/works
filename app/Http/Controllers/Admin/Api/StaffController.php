<?php

namespace App\Http\Controllers\Admin\Api;


use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\StaffRequest;
use App\Http\Requests\Index\UserRequest;
use App\Model\Admin\SystemArea;
use App\Model\Index\InviteReward;
use App\Model\Index\WithdrwalRecord;
use App\Servers\SystemServer;
use EasyWeChat\Factory;//引入支付门面类
use App\Model\Admin\SystemConfig;
use App\Model\Index\FamousRank;
use App\Model\Index\FamousUsers;
use App\Model\Index\InviteSetting;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRank;
use App\Model\Index\TargetUser;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
use Illuminate\Http\Request;


/**
 *
 * Class MyController
 * @package App\Http\Controllers\Api
 */
class StaffController extends BaseController{
    public function  Notice(Request $request){
//        \DB::enableQueryLog();
        $user = User::where(['id' => $request->user_id])->first();
        $template_id = '1RgjtVQuWZAkw_fsN_bA8jLAGS3Wv_NvDb_66fScnb8';
        $data =  [
            'first' => '内测申请审核结果提醒',
            'keyword1' => '审核通过',
            'keyword2' => date('Y-m-d H:i:s'),
            'remark' => $user->nickname . '，你已通过云作品的内测申请审核！微信中打开云作品小程序，即可开始创建。',
        ];
        $miniprogram = [
            'appid' => config('wechat.payment.default.app_id'),
        ];
        $purpose = 'review_success';
        \DB::beginTransaction();
        if ($user->status == 0){
            $template_id = '1RgjtVQuWZAkw_fsN_bA8jLAGS3Wv_NvDb_66fScnb8';
            $data =  [
                'first' => '内测申请审核结果提醒',
                'keyword1' => '审核通过',
                'keyword2' => date('Y-m-d H:i:s'),
                'remark' => $user->nickname . '，你已通过云作品的内测申请审核！微信中打开云作品小程序，即可开始创建。',
            ];
            $miniprogram = [
                'appid' => config('wechat.payment.default.app_id'),
            ];
            $purpose = 'review_success';
            $user->status = 1;
//            $user->source =
            $user->is_invite = 1;
        }elseif ($user->status == 1){
            $photographer = Photographer::where(['id' => $user['photographer_id']])->first();
//            $template_id = 'rjph5uR7iIzT2rEn3LjnF65zEdKZYisUGoAVgpipxpk';
            $miniprogram = [
                'appid' => config('custom.wechat.mp.appid'),
                'pagepath' => 'pages/homePage/home Page',//注册成功分享页
            ];
            $data =  [
                'first' => '你的云作品已创建成功。',
                'keyword1' => $photographer->name,
                'keyword2' => SystemArea::where('id', $photographer->city)->value('short_name'),
                'keyword3' => PhotographerRank::where('id', $photographer->photographer_rank_id)->value(
                        'name'
                    ).'摄影师',
                'keyword4' => $photographer->wechat,
                'keyword5' => $photographer->mobile,
                'remark' => '云作品客服微信'.SystemConfig::getVal('customer_wechat', 'works'),
            ];
//            $purpose = 'register_success';
//            $user->status = 2;
//            $user->identity = 1;

        }elseif ($user->status == 2){
            //升级
            $photographer = Photographer::where(['id' => $user->photographer_id])->first();
            $settings = InviteSetting::first();
            $photographer->invite_times = $settings->times;

            $count = User::where(['status' => 3])->count();
            $reword = InviteReward::create();
            $reword->cloud = 1;
            $reword->photographer_id = $photographer->id;
            $reword->cloud_count = 1;
            //设置勋章为白云勋章
            $reword->medal = 'baicloud';
            $reword->baicloud_time = date('Y-m-d H:i:s');

            $reword->invite_rank = $count + 1;
            $reword->save();

            $user->status = 3;
            $photographer->save();
        }
    #发送模板消息
//        if ($user->gh_openid) {
//            $app = app('wechat.official_account');
//            $tmr = $app->template_message->send(
//                [
//                    'touser' => $user->gh_openid,
//                    'template_id' => $template_id,
//                    'url' => config('app.url'),
//                    'miniprogram' => $miniprogram,
//                    'data' => $data,
//                ]
//            );
//        }
//    发送短信息
//        $TemplateCodes = config('custom.send_short_message.ali.TemplateCodes');
//        $sendePhone = AliSendShortMessageServer::quickSendSms(
//            $user->purePhoneNumber,
//            $TemplateCodes,
//            $purpose,
//            [
//                'name' => $user->nickname,
//            ]
//        );

        $user->save();
        \DB::commit();
        return response()->noContent();
    }

    public function deletefamous(Request $request){
        $famous_rank_id = $request->famous_rank_id;
        $photographer_id = $request->photographer_id;

        \DB::beginTransaction();
        try {
            FamousRank::where(['photographer_id' => $photographer_id, 'photographer_rank_id' => $famous_rank_id])->delete();
            $otherranks = FamousRank::where(['photographer_id' => $photographer_id])->get();
            $photographer = Photographer::where(['id' => $photographer_id])->first();
            if (!$otherranks){
                $fuser = FamousUsers::where(['id' => $photographer->famoususer_id]);
                $fuser->status=0;
                $fuser->save();
            }
            $settings = InviteSetting::find(1);
            //转为普通邀请者
            User::where(['photographer_id' => $photographer->id])->update(['status' => 3]);
            $photographer->invite_times = $settings->times;
            $photographer->save();
        }catch (\Exception $exception){
            \DB::rollBack();
            return $this->response->error('删除失败', 500);
        }

        \DB::commit();
        return response()->noContent();

    }

    public function addinvitetimes(Request $request){
        $settings = InviteSetting::first();
        Photographer::where(['id' => $request->photographer_id])->increment('invite_times', $settings->times);
        return response()->noContent();
    }

    public function searchphotographer(Request $request){
        $name = $request->name;
        if (!$name){
            return $this->response->error("name字段没有填写", 500);
        }
        $whereRaw = "users.nickname like '%{$name}%' OR photographers.name like '%{$name}%'";
        $lists = Photographer::join('users', 'users.photographer_id', '=', 'photographers.id')->select(
            'users.nickname',
            'users.id as user_id',
            'users.photographer_id',
            'photographers.name',
            'photographers.id'
        )->where(['users.status' => 3])->whereRaw($whereRaw)->get();

        return $this->responseParseArray($lists);

    }

    public function addfamoususers(Request $request){
        $photographer_id = $request->photographer_id;
        $rank_id = $request->famous_rank_id;
        if (!$photographer_id and !$rank_id){
            return $this->response->error('参数不存在!', 500);
        }
        $photographer = Photographer::where(['id' => $photographer_id])->first();
        $flag = FamousRank::where(['photographer_id' => $photographer_id, 'photographer_rank_id'=> $rank_id])->first();
        if ($flag){
            return $this->response->error('已经存在此领域大咖了', 500);
        }

        \DB::beginTransaction();
        try {
            $famous = FamousUsers::create();
            $famous->status = 1;
            $famous->save();


            $lastsort = 0;
            $lastrank = FamousRank::where(['photographer_rank_id' => $rank_id])->orderBy('sort', 'desc')->first();
            if ($lastrank){
                $lastsort = $lastrank->sort;
            }

            $rank = FamousRank::create();
            $rank->photographer_rank_id = $rank_id;
            $rank->photographer_id = $photographer_id;
            $rank->sort = $lastsort + 1;
            $rank->save();

            $photographer->invite_times = 9999;
            $photographer->famoususer_id = $famous->id;
            User::where(['photographer_id' => $photographer->id])->update(['status' => 4]);
            $photographer->save();
        }catch (\Exception $exception){

            \DB::rollBack();
        }

        \DB::commit();
        return response()->noContent();
    }

    public function modifysettings(Request $request){
        $settings = InviteSetting::find(1);


        $settings->expiretime = $request->expiretime;
        $settings->times = $request->times;
        $settings->cloudmedal = $request->cloudmedal;
        $settings->save();

        return response()->noContent();

    }


    public function modifyfamoussort(Request $request){
        $type = $request->type;
        $rank_id = $request->photographer_rank_id;
        $photographer_id = $request->photographer_id;

        if ($type == 'up'){
            $rank = FamousRank::where(['photographer_id' => $photographer_id, 'photographer_rank_id'=> $rank_id])->first();
            if ($rank->sort == 1){
                return $this->response->error('已经是第一名', 500);
            }
            $tmprank = FamousRank::where(['sort' => $rank->sort-1, 'photographer_rank_id'=> $rank_id])->first();
            if ($tmprank){
                $tmprank->sort = $rank->sort;
                $tmprank->save();
            }

            $rank->sort = $rank->sort - 1;
            $rank->save();
        }elseif ($type == 'down'){
            $rank = FamousRank::where(['photographer_id' => $photographer_id, 'photographer_rank_id'=> $rank_id])->first();
            $tmprank = FamousRank::where(['sort' => $rank->sort + 1, 'photographer_rank_id'=> $rank_id])->first();
            if ($tmprank){
                $tmprank->sort = $rank->sort;
                $tmprank->save();
            }
            $rank->sort = $rank->sort + 1;
            $rank->save();
        }

        return response()->noContent();
    }

    public function withdrawal(Request $request){

        $photographers = Photographer::where(['id' => $request->photographer_id])->first();
        $user = User::where(['photographer_id' => $photographers->id])->first();
        if (!$photographers){
            return abort(400, '没有用户');
        }

        $invite_reward = InviteReward::join(
            'users',
            'users.photographer_id',
            '=',
            'invite_rewards.photographer_id'
        )->select(
            'invite_rewards.*',
            'users.openid'
        )->where(['invite_rewards.photographer_id' => $photographers->id])->first();
        if ($invite_reward->is_withdrawal != 1){
            return abort(400, '用户没有提现的请求');
        }

        $order_trade_no = date('YmdHis') . SystemServer::getRandomString(6);


        $flag = SystemServer::withdrawal($order_trade_no, $invite_reward->openid, $invite_reward->withdrawal_money, '邀请奖励提现');
        if (!$flag){
            return abort(400, '提现失败');
        }
        $money  = $invite_reward->withdrawal_money;
        \DB::beginTransaction();
        try{
            $invite_reward->withdrawal_money_count = $invite_reward->withdrawal_money_count + $money;
            $invite_reward->is_withdrawal = 0;
            $invite_reward->withdrawal_money = 0;
            $invite_reward->save();

            $withdrawal = WithdrwalRecord::create();
            $withdrawal->order_no = $order_trade_no;
            $withdrawal->photographer_id = $request->photographer_id;
            $withdrawal->money = $money;
            $withdrawal->save();


        }catch (\Exception $exception){
            \DB::rollBack();

            return abort(400, '提现失败');

        }

        \DB::commit();


        $app = app('wechat.official_account');
        $template_id = 'UcZqXgLXfWL1P5CBT-nOrQrtl4gPl5phZBfIleaK5MY';
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
                        'first' => '提现已成功！',
                        'keyword1' => $money .'元',
                        'keyword2' => date('Y/m/d H:i:s'),
                        'keyword3' => '成功',
                        'remark' => '请打开微信零钱查看。',
                    ],
                ]
            );
        }

        if ($photographers->mobile){
            $third_type = config('custom.send_short_message.third_type');
            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
            if ($third_type == 'ali') {
                AliSendShortMessageServer::quickSendSms(
                    $photographers->mobile,
                    $TemplateCodes,
                    'withdrawal_success'
                );
            }
        }

        return response()->noContent();
    }


    public function getxacode(Request $request){

        $code = Photographer::getXacode($request->id, false);

        return $this->response->array($code);
    }


}
