<?php

namespace App\Console\Commands;

use App\Model\Index\Photographer;
use App\Model\Index\PhotographerRankingLog;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ErrLogServer;
use App\Servers\PhotographerServer;
use Illuminate\Console\Command;

/**
 * 沉默激活
 * Class SilentActivation
 * @package App\Console\Commands
 */
class SilentActivation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silent_activation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '沉默激活';

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
        set_time_limit(0);
        $photographers = $this->getPhotographerList();
        $day_10 = date('Y-m-d 00:0000', strtotime('-10 days'));
        $day_20 = date('Y-m-d 00:0000', strtotime('-20 days'));
        $day_30 = date('Y-m-d 00:0000', strtotime('-30 days'));
//        $now_time = time();
//        $day_10 = date('Y-m-d H:i:s', $now_time - 60 * 60 * 12);
//        $day_20 = date('Y-m-d H:i:s', $now_time - 60 * 60 * 24);
//        $day_30 = date('Y-m-d H:i:s', $now_time - 60 * 60 * 36);
        foreach ($photographers as $k => $photographer) {
            if (!$photographer->max_created_at || strtotime($photographer->max_created_at) < strtotime($day_30)) {
//                $purpose = 'silent_activation_3';
                $content_vars = ['name' => $photographer->name];
            } elseif (strtotime($photographer->max_created_at) < strtotime($day_20)) {
//                $purpose = 'silent_activation_2';
                $content_vars = ['name' => $photographer->name, 'num' => 20];
            } elseif (strtotime($photographer->max_created_at) < strtotime($day_10)) {
//                $purpose = 'silent_activation_1';
                $content_vars = ['name' => $photographer->name, 'num' => 10];
            } else {
                continue;
            }
            //发送短信
            $third_type = config('custom.send_short_message.third_type');
            $TemplateCodes = config('custom.send_short_message.'.$third_type.'.TemplateCodes');
//            if ($third_type == 'ali') {
//                AliSendShortMessageServer::quickSendSms(
//                    $photographer->mobile,
//                    $TemplateCodes,
//                    $purpose,
//                    $content_vars
//                );
//            }
        }
    }

    /**
     * 获取所有用户排行
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
        $fields = implode(',', $fields);
        $sql = "SELECT {$fields},(SELECT MAX(`created_at`) FROM `photographer_works` WHERE `photographers`.`id`=`photographer_works`.`photographer_id` AND `photographer_works`.`status`=200) AS `max_created_at` FROM `photographers` LEFT JOIN `users` ON `photographers`.`id`=`users`.`photographer_id` WHERE `users`.`is_formal_photographer`=1 AND `photographers`.`mobile` is not null AND `photographers`.`mobile`!='' AND `photographers`.`status`=200 ORDER BY `max_created_at` ASC,`photographers`.`created_at` ASC";
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}

