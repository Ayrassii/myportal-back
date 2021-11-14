<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

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

$factory->define(User::class, function (Faker $faker) {
    $gender = array_rand(['Male','Female']);
    return [
        'name' => $faker->firstName($gender),
        'last_name' => $faker->lastName,
        'avatar' => "default.jpg",
        'birthday' => $faker->dateTimeBetween('-35 years','-22 years'),
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'password' => \Illuminate\Support\Facades\Hash::make('secret'),
        'remember_token' => Str::random(10),
    ];
});

$factory->state(User::class,'Collaborateur',function () {
    return [
        'role' => 'ROLE_COLLAB'
    ];
});

$factory->state(User::class,'Marketing',function () {
    return [
        'role' => 'ROLE_MARK'
    ];
});

$factory->state(User::class,'RH',function () {
    return [
        'role' => 'ROLE_RH'
    ];
});
