<?php

use Faker\Generator as Faker;

$factory->define(Modules\User\Entities\User::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'username' => $faker->userName,
        'email' => $faker->unique()->safeEmail,
        // 'password' => $password ?: $password = bcrypt('123456'),
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'access_level' => 0,
        'is_active' => $faker->boolean,
    ];
});
