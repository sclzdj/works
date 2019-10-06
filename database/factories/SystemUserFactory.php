<?php

use Faker\Generator as Faker;

$factory->define(\App\Model\Admin\SystemUser::class, function (Faker $faker) {
    return [
        'username' => str_random(mt_rand(2, 10)),
        'password' => bcrypt('273461'),
        'nickname' => str_random(mt_rand(2, 10)),
        'type' => 0,
        'status' => mt_rand(0, 1),
        'remember_token' => str_random(64),
    ];
});
