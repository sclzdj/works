<?php

use Faker\Generator as Faker;

$factory->define(\App\Model\Admin\SystemNode::class, function (Faker $faker) {
    return [
        'name' => str_random(mt_rand(2, 10)),
        'action' => str_random(mt_rand(1, 50)).'@'.str_random(mt_rand(1, 49)),
        'icon' => 'fa fa-fw fa-laptop',
        'status' => 1,
        'sort' => mt_rand(0, 100),
    ];
});
