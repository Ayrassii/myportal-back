<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', 'ApiController@login');

Route::group([
    'middleware' => ['auth'] ,
    'as' => 'api.'
], function () {
    Route::post('/logout', 'ApiController@logout');
    Route::get('/users','ApiController@listUsers');
    Route::get('/users/{id}','ApiController@getUser');
    Route::get('/me','ApiController@me');
    Route::put('/change-password','ApiController@changepassword');
    Route::get('/feeds','ApiController@listFeed');
    Route::get('/feeds/{id}','ApiController@singleFeed');
    Route::post('/feeds','ApiController@addFeed');
    Route::put('/feeds/{id}','ApiController@editFeed');
    Route::delete('/feeds/{id}','ApiController@deleteFeed');
    Route::put('/comments','ApiController@commentEntry');
    Route::patch('/comments','ApiController@editComment');
    Route::delete('/comments/{id}','ApiController@deleteComment');
    Route::put('/likes','ApiController@like_unlikeEntry');
    Route::put('/participations','ApiController@participate_cancelEntry');
    Route::post('/valids','ApiController@decideEntry');
    Route::get('/upcoming-birthdays','ApiController@upcomingBirthdays');
    Route::get('/question','ApiController@todaysQuestion');
    Route::post('/question','ApiController@answerQuestion');
    Route::get('/employe-of-month','ApiController@employeOfTheMonth');
    Route::post('/employe-of-month','ApiController@setEmployeOfTheMonth');
    Route::get('/events','ApiController@listEvent');
    Route::get('/events/{id}','ApiController@singleEvent');
    Route::post('/events','ApiController@addEvent');
    Route::put('/events/{id}','ApiController@editEvent');
    Route::delete('/events/{id}','ApiController@deleteEvent');
    Route::get('/articles','ApiController@listArticle');
    Route::get('/articles/{id}','ApiController@singleArticle');
    Route::post('/articles','ApiController@addArticle');
    Route::put('/articles/{id}','ApiController@editArticle');
    Route::delete('/articles/{id}','ApiController@deleteArticle');
    Route::post('/annuary','ApiController@addAnnuary');
    Route::get('/annuary','ApiController@listAnnuary');
    Route::get('/discussions','ApiController@listDiscussions');
    Route::post('/discussions','ApiController@createDiscussion');
    Route::get('/messages/{discussion_id}','ApiController@listMessages');
    Route::post('/messages/{discussion_id}','ApiController@sendMessage');
    Route::post('/ideas','ApiController@addIdea');
    Route::get('/ideas','ApiController@listIdeas');
    Route::get('/notifications','ApiController@listNotifications');
    Route::get('/lastnotifs','ApiController@lastNotifs');
    Route::put('/notifications/{id}','ApiController@readNotification');
    Route::patch('/notifications','ApiController@readAllNotifications');
    Route::delete('/notifications','ApiController@deleteAllNotifications');
    Route::delete('/notifications/{id}','ApiController@deleteNotification');
});
