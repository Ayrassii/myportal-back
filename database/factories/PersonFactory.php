<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Person;
use App\User;
use Faker\Generator as Faker;


$factory->define(Person::class, function (Faker $faker) {
    $gender = array_rand(['Male','Female']);
    return [
        "createdby_id" => User::where('role','ROLE_RH')->inRandomOrder()->first()->id,
        "full_name" => $faker->name($gender),
        "email" => $faker->unique()->safeEmail,
        "phone" => $faker->phoneNumber,
        "picture" => "default.jpg",
    ];
});
