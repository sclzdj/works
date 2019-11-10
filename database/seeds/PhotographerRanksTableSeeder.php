<?php

use Illuminate\Database\Seeder;

class PhotographerRanksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ranks = [
            [
                'name' => '人像',
                'children' => [
                    ['name' => '人像'],
                    ['name' => '肖像'],
                    ['name' => '时尚'],
                    ['name' => '写真'],
                    ['name' => '街拍'],
                    ['name' => '私房'],
                    ['name' => '婚纱'],
                    ['name' => '孕照'],
                    ['name' => '儿童'],
                    ['name' => '新生儿'],
                    ['name' => '证件照'],
                    ['name' => '商务照'],
                ],
            ],
            [
                'name' => '活动',
                'children' => [
                    ['name' => '活动'],
                    ['name' => '会议'],
                    ['name' => '婚礼'],
                    ['name' => '旅拍'],
                    ['name' => '体育'],
                ],
            ],
            [
                'name' => '纪实',
                'children' => [
                    ['name' => '纪实'],
                    ['name' => '新闻'],
                    ['name' => '报道'],
                    ['name' => '街头'],
                    ['name' => '人文'],
                    ['name' => '剧照'],
                ],
            ],
            [
                'name' => '产品',
                'children' => [
                    ['name' => '产品'],
                    ['name' => '静物'],
                    ['name' => '美食'],
                    ['name' => '美妆'],
                    ['name' => '花艺'],
                    ['name' => '家具'],
                    ['name' => '家电'],
                    ['name' => '汽车'],
                ],
            ],
            [
                'name' => '服饰',
                'children' => [
                    ['name' => '服饰'],
                    ['name' => '服装'],
                    ['name' => '男装'],
                    ['name' => '女装'],
                    ['name' => '童装'],
                    ['name' => '内衣'],
                    ['name' => '珠宝'],
                    ['name' => '箱包'],
                    ['name' => '鞋靴'],
                ],
            ],
            [
                'name' => '环境',
                'children' => [
                    ['name' => '环境'],
                    ['name' => '风光'],
                    ['name' => '空间'],
                    ['name' => '城市'],
                    ['name' => '建筑'],
                    ['name' => '酒店'],
                    ['name' => '航拍'],
                ],
            ],
            [
                'name' => '其他',
                'children' => [
                    ['name' => '其他'],
                    ['name' => '商业'],
                    ['name' => '广告'],
                    ['name' => '艺术'],
                    ['name' => '观念'],
                    ['name' => '创意'],
                    ['name' => '动物'],
                    ['name' => '宠物'],
                ],
            ],
        ];
        foreach ($ranks as $k => $rank) {
            $photographer_rank = \App\Model\Index\PhotographerRank::create(
                [
                    'name' => $rank['name'],
                    'pid' => 0,
                    'level' => 1,
                    'sort' => $k + 1,
                ]
            );
            foreach ($rank['children'] as $_k => $_v) {
                \App\Model\Index\PhotographerRank::create(
                    [
                        'name' => $_v['name'],
                        'pid' => $photographer_rank->id,
                        'level' => 2,
                        'sort' => $_k + 1,
                    ]
                );
            }
        }
    }
}
