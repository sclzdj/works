<?php

use Illuminate\Database\Seeder;

class SystemUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $systemUsers =
            factory(\App\Model\Admin\SystemUser::class, 30)->create();
        $systemUser = $systemUsers[0];
        $systemUser->username = 'admin';
        $systemUser->nickname = '站长';
        $systemUser->type = 0;
        $systemUser->status = 1;
        $systemUser->save();
        $systemUser = $systemUsers[1];
        $systemUser->username = 'dujun';
        $systemUser->nickname = '军哥';
        $systemUser->avatar = '';
        $systemUser->status = 1;
        $systemUser->save();
        $systemUser = $systemUsers[2];
        $systemUser->username = 'sclzdj';
        $systemUser->nickname = '小阆';
        $systemUser->status = 0;
        $systemUser->save();
    }
}
