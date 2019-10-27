<?php


namespace App\Servers;


use App\Model\Index\Photographer;

/**
 * 摄影师服务类
 * Class PhotographerServer
 * @package App\Servers
 */
class PhotographerServer
{
    /**
     * 摄影师人脉排行榜
     * @param null $limit 条数
     * @param null $fields 字段
     * @return array
     */
    public static function visitorRankingList($limit = null, $fields = null)
    {
        if ($fields === null) {
            $fields = array_map(
                function ($v) {
                    return "`photographers`.`{$v}`";
                },
                Photographer::allowFields()
            );
            $fields = implode(',', $fields);
        } elseif (is_array($fields)) {
            $fields = implode(',', $fields);
        }
        $today = date('Y-m-d').' 00:00:00';
        $sql = "SELECT {$fields},(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id` AND `created_at`>='{$today}') AS `visitor_today_count`,(SELECT count(*) FROM `visitors` WHERE `visitors`.`photographer_id`=`photographers`.`id`) AS `visitor_count` FROM `photographers` WHERE `photographers`.`status`=200 ORDER BY `visitor_today_count` DESC,`visitor_count` DESC,`photographers`.`created_at` ASC";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        $photographers = \DB::select($sql, []);

        return $photographers;
    }
}
