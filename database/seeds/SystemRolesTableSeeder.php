<?php

use Illuminate\Database\Seeder;

class SystemRolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $systemRoles =
            factory(\App\Model\Admin\SystemRole::class, 30)->create();
        $systemRole = $systemRoles[0];
        $systemRole->name = '普通管理员';
        $systemRole->status = 1;
        $systemRole->save();
        $systemRole = $systemRoles[1];
        $systemRole->name = '编辑人员';
        $systemRole->status = 1;
        $systemRole->save();
        $systemRole = $systemRoles[2];
        $systemRole->name = '游客';
        $systemRole->status = 0;
        $systemRole->save();
    }
}
