<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Comment;
use App\Entry;
use App\User;
use Faker\Generator as Faker;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'content' => $faker->realText(),
        'createdby_id' => User::where('role','=','ROLE_COLLAB')->inRandomOrder()->first()->id,
        'is_deleted' => false,
    ];
});

$factory->state(Comment::class,'Feed Comment',function (Faker $faker) {
    return [
        'entry_id' => Entry::where('type','TYPE_FEED')->inRandomOrder()->first()->id
    ];
});
$factory->state(Comment::class,'Event Comment',function (Faker $faker) {
    return [
        'entry_id' => Entry::where('type','TYPE_EVENT')->inRandomOrder()->first()->id
    ];
});
$factory->state(Comment::class,'Article Comment',function (Faker $faker) {
    return [
        'entry_id' => Entry::where('type','TYPE_ARTICLE')->inRandomOrder()->first()->id
    ];
});
