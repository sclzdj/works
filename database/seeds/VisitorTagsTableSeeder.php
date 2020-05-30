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
            '新客',
            '老客',
            '粉丝',
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
