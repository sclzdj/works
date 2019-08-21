<?php

use Illuminate\Database\Seeder;

class SystemAreasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Model\Admin\SystemArea::fillAll();
    }
}
