<?php

namespace App\Console\Commands;

use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRankingLog;
use App\Servers\ErrLogServer;
use App\Servers\PhotographerServer;
use Illuminate\Console\Command;

/**
 * 人脉访问汇总
 * Class VisitSummary
 * @package App\Console\Commands
 */
class VisitSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visit_summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '访问汇总';

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
//（人脉无增长）XXX，过去 24 小时，你的人脉没有增长，要加油哦！
//（人脉有增长）XXX，过去 24 小时，你的人脉变多了，再接再厉哦！
//（进入排行榜）厉害了，XXX！过去 24 小时，你的人脉增长迅速，还进入了云作品人脉排行榜。
//
//报告类型：云作品人脉日报
//生成时间：201XX.XX.XX XX:XX
//用户姓名：XXX
//昨日新增人脉：XXX 人（新模板增加）
//昨日活跃人脉：XXX 人（新模板增加）
//昨日累计人脉：XXX 人（新模板增加）
//备注：昨日新增人脉 XXX/活跃人脉 XXX/累计人脉 XXX（新模板删除）
        $photographers = $this->getPhotographerList();
        foreach ($photographers as $k => $photographer) {
            if ($photographer->gh_openid != '') {
                if (PhotographerRankingLog::where(['photographer_id' => $photographer->id])->whereDate(
                    'created_at',
                    date('Y-m-d', strtotime('-1 days'))
                )->first()) {
                    $firstText = '厉害了，'.$photographer->name.'！昨天你的人脉增长迅速，还进入了云作品人脉排行榜。';
                } else {
                    if ($photographer->visitor_yesterday_count > 0) {
                        $firstText = $photographer->name.'，昨天你的人脉变多了，再接再厉哦！';
                    } else {
                        $firstText = $photographer->name.'，昨天你的人脉没有增长，要加油哦！';
                    }
                }
                $app = app('wechat.official_account');
                $template_id = 'Y1ZVLPbeqVAEQPnsBzRSAonF2gWXx7vzzyun34BEcdc';
                $tmr = $app->template_message->send(
                    [
                        'touser' => $photographer->gh_openid,
                        'template_id' => $template_id,
                        'url' => config('app.url'),
                        'miniprogram' => [
                            'appid' => config('custom.wechat.mp.appid'),
                            'pagepath' => 'pages/visitorHistory/visitorHistory',//访客列表页
                        ],
                        'data' => [
                            'first' => $firstText,
                            'keyword1' => '云作品人脉日报',
                            'keyword2' => date('Y.m.d').' 00:00',
//                            'keyword3' =>$photographer->name,
                            'remark' => '昨日新增：'.$photographer->visitor_yesterday_count.'人\昨日活跃：'.$photographer->visitor_yesterday_active_count.'人\你的累计：'.$photographer->visitor_count.'人',
                        ],
                    ]
                );
                if ($tmr['errcode'] != 0) {
                    ErrLogServer::SendWxGhTemplateMessageCommand(
                        $template_id,
                        $photographer->gh_openid,
                        $tmr['errmsg'],
                        $tmr
                    );
                }
            }
        }
    }

    /**
     * 获取所有摄影师排行
     * @return array
     */
    protected function getPhotographerList()
    {
        $fields = array_map(
            function ($v) {
                return "`photographers`.`{$v}`";
            },
            Photographer::allowFields()
        );
        $fields = array_merge($fields, ['`users`.`nickname`', '`users`.`gh_openid`']);
        $fields = implode(',', $fields);
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND date(`created_at`) = '{$yesterday}') AS `visitor_yesterday_count`,(SELECT count(distinct `user_id`) FROM `operate_records` WHERE `operate_records`.`photographer_id`=`photographers`.`id` AND date(`created_at`) = '{$yesterday}') AS `visitor_yesterday_active_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count` FROM `photographers` LEFT JOIN `users` ON `photographers`.`id`=`users`.`photographer_id` WHERE `photographers`.`status`=200 ORDER BY `visitor_yesterday_count` DESC,`visitor_yesterday_active_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}
