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
    protected $description = '报告生成通知';

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
        //${name}，你上周新增了${num3}个人脉，累计已达到${num4}个人脉。把水印海报和水印照片当诱饵，让人脉统统都到碗里来。
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
                        'num3' => $photographer->visitor_week_count,
                        'num4' => $photographer->visitor_count,
                    ]
                );
            }
        }
    }

    /**
     * 获取所有用户排行
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
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at` >= '{$week_start}' AND `created_at` <= '{$week_end}') AS `visitor_week_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count` FROM `photographers` LEFT JOIN `users` ON `photographers`.`id`=`users`.`photographer_id` WHERE `users`.`is_formal_photographer`=1 AND `photographers`.`mobile` is not null AND `photographers`.`mobile`!='' AND `photographers`.`status`=200 ORDER BY `visitor_week_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}
