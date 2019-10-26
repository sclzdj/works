<?php

namespace App\Console\Commands;

use App\Model\Index\Photographer;
use App\Servers\ErrLogServer;
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
//（若人脉无增长）XXX，过去24小时，你的人脉没有增长，要加油哦！
//（若人脉有增长）XXX，祝贺你！过去24小时，你的人脉变多了，再接再厉哦！
//（若进入排行榜）XXX，祝贺你！过去24小时，你的人脉增长迅速，还进入了云作品人脉排行榜，再接再厉哦！
//
//新增人脉：XXX人
//累计人脉：XXX人
//新增访问：XXX次
//累计访问：XXX次
        $photographers = $this->getPhotographerRankList();
        foreach ($photographers as $k => $photographer) {
            if ($photographer->gh_openid != '') {
                if ($k < 50) {
                    $firstText = $photographer->name.'，祝贺你！过去24小时，你的人脉增长迅速，还进入了云作品人脉排行榜，再接再厉哦！';
                } else {
                    if ($photographer->visitor_today_count > 0) {
                        $firstText = $photographer->name.'，祝贺你！过去24小时，你的人脉变多了，再接再厉哦！';
                    } else {
                        $firstText = $photographer->name.'，过去24小时，你的人脉没有增长，要加油哦！';
                    }
                }
                $app = app('wechat.official_account');
                $template_id = 'CiFcVCzHQI-9G_l7H-uGMaexTheqCSo0AI_LSKM0dNY';
                $tmr = $app->template_message->send(
                    [
                        'touser' => $photographer->gh_openid,
                        'template_id' => $template_id,
                        'url' => config('app.url'),
//                                    'miniprogram' => [
//                                        'appid' => config('custom.wechat.mp.appid'),
//                                        'pagepath' => 'pages/xxx?'.$visitor->id,//访客详情页
//                                    ],
                        'data' => [
                            'first' => $firstText,
                            'keyword1' => $photographer->visitor_today_count.'人',
                            'keyword2' => $photographer->visitor_count.'人',
                            'keyword3' => $photographer->operate_record_today_count.'次',
                            'remark' => $photographer->operate_record_count.'次',
                        ],
                    ]
                );
                if ($tmr['errcode'] != 0) {
                    ErrLogServer::SendWxGhTemplateMessage($template_id, $tmr['errmsg'], $tmr);
                }
            }
        }
    }

    /**
     * 获取所有摄影师排行
     * @return array
     */
    protected function getPhotographerRankList()
    {
        $fields = array_map(
            function ($v) {
                return "`photographers`.`{$v}`";
            },
            Photographer::allowFields()
        );
        $fields = array_merge($fields, ['`users`.`nickname`', '`users`.`gh_openid`']);
        $fields = implode(',', $fields);
        $today = date('Y-m-d H:i:s', time() - 24 * 60 * 60);
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at`>='{$today}') AS `visitor_today_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count`,(SELECT count(*) FROM `operate_records` WHERE `operate_records`.`photographer_id`=`photographers`.`id`) AS `operate_record_count`,(SELECT count(*) FROM `operate_records` WHERE `operate_records`.`photographer_id`=`photographers`.`id` AND `created_at`>='{$today}') AS `operate_record_today_count` FROM `photographers` LEFT JOIN `users` ON `photographers`.`id`=`users`.`photographer_id` WHERE `photographers`.`status`=200 ORDER BY `visitor_today_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}
