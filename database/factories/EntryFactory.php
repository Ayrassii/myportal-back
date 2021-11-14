<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Entry;
use App\User;
use Faker\Generator as Faker;

$factory->define(Entry::class, function (Faker $faker) {
    return [
        'createdby_id' => User::where('role','!=','ROLE_COLLAB')->inRandomOrder()->first()->id,
        'description' => $faker->text,
        'title' => $faker->sentence(10),
        'is_deleted' => false,
        'is_featured' => false,
        'is_valid' => true,
    ];
});

$factory->state(Entry::class,'Evenement',function (Faker $faker) {
    $start = $faker->dateTimeBetween('next Monday', 'next Monday +7 days');
    $start = new DateTimeImmutable($start->format('Y-m-d H:i:s'));
    $end = $start->modify('+'.random_int(0,4) .' days');
    return [
        'type' => 'TYPE_EVENT',
        'start_date' => $start,
        'end_date' => $end,
    ];
});

$factory->state(Entry::class,'Article',function (Faker $faker) {
    return [
        'type' => 'TYPE_ARTICLE',
        'content' => $faker->randomHtml
    ];
});

$factory->state(Entry::class,'Feed',function (Faker $faker) {
    return [
        'type' => 'TYPE_FEED'
    ];
});
