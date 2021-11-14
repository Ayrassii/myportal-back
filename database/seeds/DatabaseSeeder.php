<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        factory('App\User',12)->states('Collaborateur')->create();
        factory('App\User',2)->states('Marketing')->create();
        factory('App\User',5)->states('RH')->create();
        factory('App\Entry',20)->states('Feed')->create();
        factory('App\Entry',5)->states('Article')->create();
        factory('App\Entry',15)->states('Evenement')->create();
        factory('App\Comment',30)->states('Feed Comment')->create();
        factory('App\Comment',30)->states('Event Comment')->create();
        factory('App\Comment',30)->states('Article Comment')->create();
        factory('App\Person',15)->create();
        factory('App\Question',3)->create();
        factory('App\Answer',12)->create();
        $month = \App\User::where('role','ROLE_COLLAB')->inRandomOrder()->first();
        $month->is_employe_of_month = true;
        $month->save();
        $users = \App\User::all();
        foreach ($users as $key => $user) {
            if ($key === 0) {
                $user->avatar = "default.jpg";
            } else {
                $user->avatar = $user->id.".jpg";
            }
            $user->save();
        }
    }
}
