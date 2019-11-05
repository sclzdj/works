<?php

namespace App\Console\Commands;

use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRankingLog;
use App\Model\Index\User;
use App\Servers\ErrLogServer;
use App\Servers\PhotographerServer;
use Illuminate\Console\Command;

/**
 * 摄影师排名通知
 * Class VisitSummary
 * @package App\Console\Commands
 */
class PhotographerRanking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photographer_ranking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '摄影师排名';

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
//        log::info('test');
//排行榜入选通知（入选排行榜时通知）
//20XX年XX月XX日
//
//XXX，祝贺你！
//你已入选今天的云作品人脉排行榜！
//
//实时排名：XX名
//入选时间：20XX-XX-XX XX:XX:XX

//详情（链接至人脉排行榜）
        $rankingList = PhotographerServer::visitorRankingList(50);
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        foreach ($rankingList as $k => $photographer) {
            $photographerRankingLog = PhotographerRankingLog::where(
                ['photographer_id' => $photographer->id]
            )->whereDate('created_at', $date)->first();
            if (!$photographerRankingLog) {
                $photographerRankingLog = PhotographerRankingLog::create();
                $photographerRankingLog->photographer_id = $photographer->id;
                $photographerRankingLog->ranking = $k + 1;
                $photographerRankingLog->save();
                $user = User::where('photographer_id', $photographer->id)->first();
                if ($user && $user->gh_openid != '') {
                    $app = app('wechat.official_account');
                    $template_id = 'CiFcVCzHQI-9G_l7H-uGMaexTheqCSo0AI_LSKM0dNY';
                    $tmr = $app->template_message->send(
                        [
                            'touser' => $user->gh_openid,
                            'template_id' => $template_id,
                            'url' => config('app.url'),
                            'miniprogram' => [
                                'appid' => config('custom.wechat.mp.appid'),
                                'pagepath' => 'subPage/ranking/ranking',//人脉排行榜页
                            ],
                            'data' => [
                                'first' => '《这是通知6》'.$photographer->name.'，祝贺你！你已入选今天的云作品人脉排行榜！',
                                'keyword1' => ($k + 1).'名',
                                'keyword2' => $time,
                                'keyword3' => '',
                                'remark' => '',
                            ],
                        ]
                    );
                    if ($tmr['errcode'] != 0) {
                        ErrLogServer::SendWxGhTemplateMessage($template_id, $tmr['errmsg'], $tmr);
                    }
                }
            }
        }
    }

}
