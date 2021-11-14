<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Question;
use App\User;
use Faker\Generator as Faker;

$factory->define(Question::class, function (Faker $faker) {
    return [
        "createdby_id"=> User::where('role','ROLE_RH')->first(),
        "content" => $faker->sentence(5). " ?"
    ];
});
