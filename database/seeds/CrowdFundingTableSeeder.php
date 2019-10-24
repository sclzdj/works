<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrowdFundingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo DB::table('crowd_fundings')->insert([
            'id' => 1,
            'amount' => 15000,
            'total' => 0,
            'total_price' => 0,
            'target' => 15000,
            'complete_rate' => 0,
            'limit_99' => 30,
            'data_99' => 0,
            'limit_399' => 30,
            'data_399' => 0,
            'limit_599' => 30,
            'data_599' => 0,
            'start_date' => 1571850182,
            'end_date' => 1574640000,
            'send_date' => 1574985600,
        ]);
    }
}
