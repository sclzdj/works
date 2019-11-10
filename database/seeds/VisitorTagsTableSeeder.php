<?php

use Illuminate\Database\Seeder;

class VisitorTagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            '未知',
            '老客',
            '新客',
            '同行',
            '亲友',
        ];
        foreach ($tags as $k => $tag) {
            \App\Model\Index\VisitorTag::create(
                [
                    'name' => $tag,
                    'sort' => $k + 1,
                ]
            );
        }
    }
}
