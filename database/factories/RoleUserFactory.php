<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\{RoleUser, User, Role};
use Faker\Generator as Faker;

$factory->define(RoleUser::class, function (Faker $faker) {
    return [
        'user_id' => User::all()->random()->id,
        'role_id' => Role::all()->random()->id
    ];
});
