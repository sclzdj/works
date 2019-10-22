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
            factory(\App\Model\Admin\SystemUser::class, 2)->create();
        $systemUser = $systemUsers[0];
        $systemUser->username = 'admin';
        $systemUser->nickname = '站长';
        $systemUser->type = 0;
        $systemUser->status = 1;
        $systemUser->save();
        $systemUser = $systemUsers[1];
        $systemUser->username = 'works';
        $systemUser->password = bcrypt('works123456');
        $systemUser->nickname = '云作品';
        $systemUser->type = 2;
        $systemUser->status = 1;
        $systemUser->save();
        $node_ids = [1, 3, 5, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67];
        foreach ($node_ids as $node_id) {
            \DB::table('system_user_nodes')->insert(
                [
                    'system_user_id' => $systemUser->id,
                    'system_node_id' => $node_id,
                ]
            );
        }
    }
}
