<?php

namespace App\Http\Controllers\Admin\Api;


use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\StaffRequest;
use App\Model\Admin\SystemArea;
use App\Model\Admin\SystemConfig;
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
        $user = User::where('id', $request->user_id)->first();
        $targetuser = TargetUser::where(['user_id' => $user['id']])->first();
        if ($targetuser['status'] == 0){
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

        }elseif ($targetuser['status'] == 1){
            $photographer = Photographer::where(['id' => $user['photographer_id']])->first();
            $template_id = 'rjph5uR7iIzT2rEn3LjnF65zEdKZYisUGoAVgpipxpk';
            $miniprogram = [
                'appid' => config('custom.wechat.mp.appid'),
                'pagepath' => 'pages/homePage/homePage',//注册成功分享页
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
            $purpose = 'register_success';
        }


        if ($user->gh_openid) {
            $app = app('wechat.official_account');
            $tmr = $app->template_message->send(
                [
                    'touser' => $user->gh_openid,
                    'template_id' => $template_id,
                    'url' => config('app.url'),
                    'miniprogram' => $miniprogram,
                    'data' => $data,
                ]
            );
        }
        $TemplateCodes = config('custom.send_short_message.ali.TemplateCodes');
        $sendePhone = AliSendShortMessageServer::quickSendSms(
            $user->purePhoneNumber,
            $TemplateCodes,
            $purpose,
            [
                'name' => $user->nickname,
            ]
        );

        return response()->noContent();
    }
}
