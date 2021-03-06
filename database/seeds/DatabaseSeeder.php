<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SystemUsersTableSeeder::class);
        $this->call(SystemRolesTableSeeder::class);
        $this->call(SystemNodesTableSeeder::class);
        $this->call(SystemConfigsTableSeeder::class);
        $this->call(SystemAreasTableSeeder::class);
        $this->call(PhotographerRanksTableSeeder::class);
        $this->call(PhotographerWorkCustomerIndustriesTableSeeder::class);
        $this->call(PhotographerWorkCategoriesTableSeeder::class);
        $this->call(VisitorTagsTableSeeder::class);
        $this->call(HelpNotesTableSeeder::class);
        $this->call(CrowdFundingTableSeeder::class);
    }
}
