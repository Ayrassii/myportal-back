<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Answer;
use App\User;
use App\Question;
use Faker\Generator as Faker;

$factory->define(Answer::class, function (Faker $faker) {
    return [
        "createdby_id" => User::where('role','ROLE_RH')->first(),
        "question_id" => Question::inRandomOrder()->first()->id,
        "text" => $faker->sentence(4),
    ];
});
