<?php

use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(\App\Model\Index\User::class, function (Faker $faker) {
    return [
        'username' => $faker->unique()->name,
        'nickname' => $faker->name,
        'remember_token' => Str::random(10),
    ];
});
