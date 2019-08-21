<?php

use Faker\Generator as Faker;

$factory->define(\App\Model\Admin\SystemRole::class, function (Faker $faker) {
    return [
        'name' => str_random(mt_rand(2, 10)),
        'status' => mt_rand(0, 1),
    ];
});
