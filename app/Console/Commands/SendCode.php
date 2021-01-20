<?php

namespace App\Console\Commands;

use App\Model\Admin\SystemConfig;
use App\Model\Index\CrowdFunding;
use App\Model\Index\InvoteCode;
use App\Model\Index\User;
use App\Servers\AliSendShortMessageServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send_code';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送邀请码';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $crowdFunding = CrowdFunding::find(1);
        $send_date = $crowdFunding->send_date;

        if (time() < $send_date) {
            return;
        }

        $app = app('wechat.official_account');
        $datas = InvoteCode::where(
            [
                'type' => 1,
                'is_send' => 0,
            ]
        )->whereIn('status', [0, 1])->get();

        foreach ($datas as $data) {
            try {
                if ($data['user_id'] == 0) {
                    continue;
                }
                $userInfo = User::where('id', $data['user_id'])->first();
                if ($userInfo->gh_openid) {
//                    $tmr = $app->template_message->send(
//                        [
//                            'touser' => $userInfo->gh_openid,
//                            'template_id' => 'eQ4Aj2Sb7VvVF-0is1Pg7wO1QU43UcVkJ36wHlhCTFE',
//                            'miniprogram' => [
//                                'appid' => config('wechat.payment.default.app_id'),
//                                'pagepath' => 'pages/web/web',
//                            ],
//                            'url' => config('app.url'),
//                            'data' => [
//                                'first' => '你的云作品创建码已经生成！点击此处，即可开始创建。',
//                                'keyword1' => $userInfo->nickname,
//                                'keyword2' => $userInfo->phoneNumber,
//                                'keyword3' => $data['code'],
//                                'remark' => '云作品客服微信'.SystemConfig::getVal('customer_wechat','works'),
//                            ],
//                        ]
//                    );
//                    Log::error(json_encode($tmr, JSON_UNESCAPED_UNICODE));
//                    if ($tmr['errmsg'] == "ok") {
//                        InvoteCode::where('id', $data['id'])->update(['is_send' => 1]);
//                    }
                }
                if ($userInfo->purePhoneNumber != '') {
                    //发送短信
                    $third_type = config('custom.send_short_message.third_type');
                    $TemplateCodes = config('custom.send_short_message.' . $third_type . '.TemplateCodes');
                    if ($third_type == 'ali') {
//                        AliSendShortMessageServer::quickSendSms(
//                            $userInfo->purePhoneNumber,
//                            $TemplateCodes,
//                            'register_code_generate',
//                            [
//                                'name' => $userInfo->is_wx_authorize == 1 ? $userInfo->nickname : '亲',
//                                'code' => $data['code'],
//                            ]
//                        );
                    }
                }
            } catch (\Exception $exception) {
                Log::error($exception->getMessage() .':file:'. $exception->getFile() .':line:'.$exception->getLine());
                continue;
            }
        }


    }
}
