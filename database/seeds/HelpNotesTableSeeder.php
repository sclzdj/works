<?php

use Illuminate\Database\Seeder;

class HelpNotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Model\Index\HelpNote::create(
            [
                'title' => '1这怎么用来着?????????这怎么用来着?????????这怎么用来着?????????',
                'content' => '1就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！',
                'status'=>200
            ]
        );
        \App\Model\Index\HelpNote::create(
            [
                'title' => '2这怎么用来着?????????这怎么用来着?????????这怎么用来着?????????',
                'content' => '2就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！',
                'status'=>200
            ]
        );
        \App\Model\Index\HelpNote::create(
            [
                'title' => '3这怎么用来着?????????这怎么用来着?????????这怎么用来着?????????',
                'content' => '3就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！就是这么用！！！！',
                'status'=>200
            ]
        );
    }
}
