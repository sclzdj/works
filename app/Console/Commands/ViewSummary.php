<?php

namespace App\Console\Commands;

use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRankingLog;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ErrLogServer;
use App\Servers\PhotographerServer;
use Illuminate\Console\Command;

/**
 * 浏览汇总
 * Class ViewSummary
 * @package App\Console\Commands
 */
class ViewSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view_summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '浏览汇总';

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
// ${name}，过去7天，你的云作品被${num1}人浏览了${num2}次。想更受欢迎吗？试试将水印照片和水印海报分享给更多人！
        set_time_limit(0);
        $photographers = $this->getPhotographerList();
        foreach ($photographers as $k => $photographer) {
            //发送短信
            $third_type = config('custom.send_short_message.third_type');
            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
            if ($third_type == 'ali') {
                AliSendShortMessageServer::quickSendSms(
                    $photographer->mobile,
                    $TemplateCodes,
                    'report_generate',
                    [
                        'name' => $photographer->name,
                        'num1' => $photographer->visitor_week_count,
                        'num2' => $photographer->record_week_count,
                    ]
                );
            }
        }
    }

    /**
     * 获取所有摄影师排行
     * @return array
     */
    protected function getPhotographerList()
    {
        $weekday = date('w');
        $weekday = ($weekday + 6) % 7;
        $week_end = date('Y-m-d 23:59:59', strtotime("-".($weekday + 1)." days"));
        $week_start = date('Y-m-d 00:00:00', strtotime("-".($weekday + 7)." days"));
        $fields = array_map(
            function ($v) {
                return "`photographers`.`{$v}`";
            },
            Photographer::allowFields()
        );
        $fields = implode(',', $fields);
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at` >= '{$week_start}' AND `created_at` <= '{$week_end}') AS `visitor_week_count`,(SELECT count(*) FROM `operate_records` WHERE `operate_records`.`photographer_id`=`photographers`.`id` AND `created_at` >= '{$week_start}' AND `created_at` <= '{$week_end}') AS `record_week_count` FROM `photographers` LEFT JOIN `users` ON `photographers`.`id`=`users`.`photographer_id` WHERE `users`.`is_formal_photographer`=1 AND `photographers`.`mobile` is not null AND `photographers`.`mobile`!='' AND `photographers`.`status`=200 ORDER BY `visitor_week_count` DESC,`record_week_count` DESC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}
