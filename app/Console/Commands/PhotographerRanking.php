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
// XXX，祝贺你！
//你已入选今天的云作品人脉排行榜！
//比赛名称：云作品人脉排行榜
//获得奖项：XX 名
//备注：点击详情查看
        set_time_limit(0);
        $rankingList = PhotographerServer::visitorRankingList(50);
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        foreach ($rankingList as $k => $photographer) {
            $photographerRankingLog0 = PhotographerRankingLog::where(
                ['photographer_id' => $photographer->id]
            )->whereDate('created_at', $date)->first();
            $photographerRankingLog = PhotographerRankingLog::create();
            $photographerRankingLog->photographer_id = $photographer->id;
            $photographerRankingLog->ranking = $k + 1;
            $photographerRankingLog->save();
            if (!$photographerRankingLog0) {
//            if (true) {
                $user = User::where('photographer_id', $photographer->id)->first();
                if ($user && $user->gh_openid != '') {
                    $app = app('wechat.official_account');
                    $template_id = 'PAObqNiE4rt9WfCJbQlBcBCxWHwmgFgI3Ey7Hnel6oc';
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
                                'first' => $photographer->name.'，祝贺你！你已入选今天的云作品人脉排行榜！',
                                'keyword1' => '云作品人脉排行榜',
                                'keyword2' => ($k + 1).'名',
                                'remark' => '点击详情查看',
                            ],
                        ]
                    );
                    if ($tmr['errcode'] != 0) {
                        ErrLogServer::SendWxGhTemplateMessageCommand(
                            $template_id,
                            $user->gh_openid,
                            $tmr['errmsg'],
                            $tmr
                        );
                    }
                }
            }
        }
    }

}
