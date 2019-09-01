<?php

use Illuminate\Database\Seeder;

class PhotographerWorkCustomerIndustriesTableSeeder extends Seeder
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
                'name' => '互联网',
                'children' => [
                    ['name' => '互联网'],
                    ['name' => '游戏'],
                    ['name' => '通信'],
                    ['name' => '电子'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '房地产',
                'children' => [
                    ['name' => '房地产'],
                    ['name' => '建筑'],
                    ['name' => '物业'],
                    ['name' => '经纪'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '消费品',
                'children' => [
                    ['name' => '消费品'],
                    ['name' => '汽车'],
                    ['name' => '家电'],
                    ['name' => '服饰'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '金融',
                'children' => [
                    ['name' => '金融'],
                    ['name' => '银行'],
                    ['name' => '保险'],
                    ['name' => '证券'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '医疗',
                'children' => [
                    ['name' => '医疗'],
                    ['name' => '美容'],
                    ['name' => '制药'],
                    ['name' => '器械'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '能源',
                'children' => [
                    ['name' => '能源'],
                    ['name' => '环保'],
                    ['name' => '化工'],
                    ['name' => '采掘'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '服务',
                'children' => [
                    ['name' => '服务'],
                    ['name' => '餐饮'],
                    ['name' => '酒店'],
                    ['name' => '出行'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '文化',
                'children' => [
                    ['name' => '文化'],
                    ['name' => '教育'],
                    ['name' => '影视'],
                    ['name' => '传媒'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '工业',
                'children' => [
                    ['name' => '工业'],
                    ['name' => '机械'],
                    ['name' => '设备'],
                    ['name' => '硬件'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '商业',
                'children' => [
                    ['name' => '商业'],
                    ['name' => '零售'],
                    ['name' => '贸易'],
                    ['name' => '物流'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '咨询',
                'children' => [
                    ['name' => '咨询'],
                    ['name' => '法律'],
                    ['name' => '财税'],
                    ['name' => '检测'],
                    ['name' => '其他'],
                ],
            ],
            [
                'name' => '其他',
                'children' => [
                    ['name' => '政府'],
                    ['name' => '军队'],
                    ['name' => '农业'],
                    ['name' => 'NGO'],
                    ['name' => '其他'],
                ],
            ],
        ];
        foreach ($ranks as $k => $rank) {
            $photographer_rank = \App\Model\Index\PhotographerWorkCustomerIndustry::create(
                [
                    'name' => $rank['name'],
                    'pid' => 0,
                    'level' => 1,
                    'sort' => $k + 1,
                ]
            );
            foreach ($rank['children'] as $_k => $_v) {
                \App\Model\Index\PhotographerWorkCustomerIndustry::create(
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
