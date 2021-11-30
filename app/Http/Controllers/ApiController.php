<?php

namespace App\Http\Controllers;

use App\Answer;
use App\AnswerResponse;
use App\Comment;
use App\Discussion;
use App\Entry;
use App\Idea;
use App\Like;
use App\Media;
use App\Message;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewEntryNotification;
use App\Notifications\NewLikeNotification;
use App\Participation;
use App\Person;
use App\Question;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    //Login
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    //logged User
    public function me()
    {
        $user = auth('api')->user();
        return response()->json($user);
    }
    //Change Password
    public function changepassword(Request $request){
        $user = auth('api')->user();
        try {
            $this->validate($request, [
                'password' => ['required'],
                'new_password' => ['confirmed', 'required', 'min:6','different:password'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()['new_password'][0]]);
        }

        if (Hash::check($request->password, $user->password)) {
            $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();
            return response()->json(['success' => 'Mot de Passe changé']);
        } else {
            return response()->json(['error' => 'Mot de Passe Courant Incorrect']);
        }
    }
    // User By Id
    public function getUser($id) {
        $user = User::find($id);
        return response()->json($user);
    }
    // list Users
    public function listUsers() {
        $users = User::orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    //Logout
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    //list Feed
    public function listFeed(){
        $feeds = Entry::with(['owner','likes','medias' => function($q) {
            return $q->where('is_main', true);
        }])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_FEED')
            ->where('is_deleted',false);
        if (!auth()->user()->isAdmin()) {
            $feeds = $feeds->where('is_valid', true);
        }
        $feeds = $feeds->orderBy('created_at','desc')
            ->get();
        return response()->json($feeds);
    }

    //single Feed
    public function singleFeed($id){
        $feed = Entry::with(['owner','comments.owner','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_FEED')
            ->where('is_deleted',false)
            ->where('id',$id)
            ->first();
        return response()->json($feed);
    }

    //add Feed
    public function addFeed(Request $request){
        $logged = auth()->user();
        $feed = new Entry();
        $feed->type = "TYPE_FEED";
        $this->validateEntry($feed, $logged);
        $feed->createdby_id = auth()->id();
        $feed->title = $request->get('title');
        $feed->description = $request->get('description');
        $feed->is_featured = $request->get('is_featured');
        if ($logged->isAdmin()) {
            $feed->is_valid = 1;
            //NewEntryNotification
            $users = User::where('id','!=',auth()->id())->get();
            foreach ($users as $user) {
                $user->notify(new NewEntryNotification($feed));
            }
        } else {
            $feed->is_valid = 0;
        }
        $feed->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $medias = $request->get('medias');
            $count = 0;
            foreach ($medias as $key => $media) {
                $count++;
                if (isset($request->files->get('medias')[$key]['file'])) {
                    $destinationPath = 'medias/feeds/';
                    $filename = date('YmdHis') . "." . $request->files->get('medias')[$key]['file']->getClientOriginalExtension();
                    $request->files->get('medias')[$key]['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$feed->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $count === 1
                    ]);
                } else {
                   try{
                    Media::create([
                        "entry_id" =>$feed->id,
                        "createdby_id" => $logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $count === 1
                    ]);
                   } catch(\Exception $e) {
                    $return = Entry::with(['owner','comments','likes','medias' => function ($q) {
                        return $q->where('is_main', true);
                    }])
                        ->withCount(['comments','likes'])
                        ->where('type','=','TYPE_FEED')
                        ->where('id',$feed->id)
                        ->first();
                    return response()->json($return);
                   }
                }
            }
        }
        $return = Entry::with(['owner','comments','likes','medias' => function ($q) {
            return $q->where('is_main', true);
        }])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_FEED')
            ->where('id',$feed->id)
            ->first();
        return response()->json($return);
    }

    //edit Feed
    public function editFeed(Request $request, $id){
        $logged = auth()->user();
        $feed = Entry::where('type','TYPE_FEED')->where('id', $id)->first();
        if (!$feed)
            return response()->json(['message' => 'Feed Not Found'], 404);
        $feed->is_valid = true;
        $feed->title = $request->get('title');
        $feed->description = $request->get('description');
        $feed->is_featured = $request->get('is_featured');
        $feed->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $feed->medias()->delete();
            $medias = $request->get('medias');
            foreach ($medias as $key => $media) {
                $first = $key == 0;
                if (isset($media['file'])) {
                    $destinationPath = 'medias/feeds/';
                    $filename = date('YmdHis') . "." . $media['file']->getClientOriginalExtension();
                    $media['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$feed->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $first
                    ]);
                } else {
                    Media::create([
                        "entry_id" =>$feed->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $first
                    ]);
                }
            }
        }
        $return = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_FEED')
            ->where('id',$feed->id)
            ->first();
        return response()->json($return);
    }

    //delete Feed
    public function deleteFeed($id){
        $logged = auth()->user();
        $feed = Entry::where('type','TYPE_FEED')->where('id', $id)->first();
        if (!$feed)
            return response()->json(['message' => 'Feed Not Found'], 404);
        if ($logged->isAdmin()) {
            $feed->is_deleted = true;
        }
        $feeds = Entry::with('owner')
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_FEED')
            ->where('is_deleted',false)
            ->get();
        return response()->json($feeds);
    }

    //comment Entry
    public function commentEntry(Request $request){
        $comment = Comment::create([
            "content" => $request->get('body'),
            "createdby_id" => auth()->id(),
            "entry_id" => $request->get('entry_id'),
            "is_deleted" => false
        ]);
        //EntryCommentedNotif
        $entry = Entry::with(['owner','comments.owner','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('is_deleted',false)
            ->where('id',$request->get('entry_id'))
            ->first();
        $entry->owner->notify(new NewCommentNotification($comment));
        return response()->json($entry);
    }

    public function editComment(Request $request) {
        $comment = Comment::find($request->get('comment_id'));
        $entry_id = $comment->entry_id;
        if (auth()->id() == $comment->createdby_id) {
            $comment->content = $request->get('content');
            $comment->save();
            $entry = Entry::with(['owner','comments.owner','likes','medias'])
                ->withCount(['comments','likes'])
                ->where('is_deleted',false)
                ->where('id',$entry_id)
                ->first();
            return response()->json($entry);
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function deleteComment($id) {
        $comment = Comment::find($id);
        $entry_id = $comment->entry_id;
        if (auth()->id() == $comment->createdby_id) {
            $comment->delete();
            $entry = Entry::with(['owner','comments.owner','likes','medias'])
                ->withCount(['comments','likes'])
                ->where('is_deleted',false)
                ->where('id',$entry_id)
                ->first();
            return response()->json($entry);
        }
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    //comment Entry
    public function decideEntry(Request $request){
        //getEntireEntry
        $entry = Entry::where('is_deleted',false)
            ->where('id',$request->get('entry_id'))
            ->first();
        $type = $entry->type;
        if ($request->get('is_valid') == 'yes') {
            $entry->is_valid = 1;
            //NewEntryNotification
            $users = User::where('id','!=',$entry->createdby_id)->get();
            foreach ($users as $user) {
                $user->notify(new NewEntryNotification($entry));
            }
        } else {
            $entry->is_valid = 0;
            $entry->is_deleted = 1;
        }
        $entry->save();
        $entries = Entry::with(['owner','likes','medias' => function($q) {
            return $q->where('is_main', true);
        }])
            ->withCount(['comments','likes'])
            ->where('type','=',$type)
            ->where('is_deleted',false)
            ->orderBy('created_at','desc')
            ->get();
        return response()->json($entries);
    }

    //like/unlike Entry
    public function like_unlikeEntry(Request $request){
        $entry = Entry::findOrFail($request->entry_id);
        $liked = $entry->likes()->where('user_id', auth()->id())->first();
        if ($liked) {
            $liked->delete();
        } else {
            $like = Like::create([
                "user_id" => auth()->id(),
                "entry_id" => $request->entry_id
            ]);
            $entry->owner->notify(new NewLikeNotification($like));
        }
        $entry = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('is_deleted',false)
            ->where('id',$request->get('entry_id'))
            ->first();
        return response()->json($entry);
    }

    //Participate/Canecl Entry
    public function participate_cancelEntry(Request $request){
        $entry = Entry::findOrFail($request->entry_id);
        $participant = $entry->participants()->where('user_id', auth()->id())->first();
        if ($participant) {
            $participant->delete();
        } else {
            Participation::create([
                "user_id" => auth()->id(),
                "entry_id" => $request->entry_id
            ]);
        }
        $entry = Entry::with(['owner','comments','likes','participants.owner','medias'])
            ->withCount(['comments','likes'])
            ->where('is_deleted',false)
            ->where('id',$request->get('entry_id'))
            ->first();
        return response()->json($entry);
    }

    //Upcoming Birthdays
    public function upcomingBirthdays(){
        $result = User::select('name','last_name','birthday','avatar')
            ->whereMonth('birthday',date('m'))
            ->whereDay('birthday','>=',date('d'))
            ->take(3)
            ->get();
        return response()->json($result);
    }

    //Today's Question
    public function todaysQuestion(){
        $question = Question::with('answers')
            ->orderBy('created_at','desc')
            ->first();
        $answers_ids = $question->answers()->pluck('id');
        $user_responded = AnswerResponse::whereIn('answer_id',$answers_ids)
            ->where('createdby_id', auth()->id())
            ->exists();
        $responses_count = AnswerResponse::whereIn('answer_id',$answers_ids)
        ->count();
        if ($user_responded) {
            $question = Question::with('answers.answer_responses')
                ->orderBy('questions.created_at','desc')
                ->first();
            return response()->json(['question' =>$question, 'answered' => true, 'responses_count' => $responses_count]);
        } else {
            return response()->json(['question' =>$question, 'answered' => false, 'responses_count' => $responses_count]);
        }
    }

    //Answer Question
    public function answerQuestion(Request $request){
        AnswerResponse::create([
           "answer_id" => $request->answer_id,
           "createdby_id" => auth()->id(),
           "is_deleted" => false
        ]);
        $question = Question::with('answers.answer_responses')
            ->find($request->question_id);
        $answers_ids = $question->answers()->pluck('id');
        $responses_count = AnswerResponse::whereIn('answer_id',$answers_ids)
        ->count();
        return response()->json(['question' =>$question, 'answered' => true,'responses_count' => $responses_count]);
    }

    //Employe Of Month
    public function employeOfTheMonth(){
        $employee = User::where('is_employe_of_month', true)->first();
        return response()->json($employee);
    }
    //set Employe Of Month
    public function setEmployeOfTheMonth(Request $request){
        User::query()->update(['is_employe_of_month' => false]);
        $employee = User::find($request->user_id);
        $employee->is_employe_of_month = true;
        $employee->save();
        return response()->json($employee);
    }

    //list Event
    public function listEvent(Request $request){
        $events = Entry::with(['owner','medias' => function($q) {
            return $q->where('is_main', true);
        }])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_EVENT')
            ->where('is_deleted',false);
        if (!!$request->query('start_date') && !!$request->query('end_date')) {
            $events = $events->whereBetween('start_date',[$request->query('start_date'),$request->query('end_date')]);

        }
        if ($request->query('count')) {
            $events = $events->take($request->query('count'));
        }
        $events = $events->orderBy('created_at','desc')
            ->get();
        return response()->json($events);
    }

    //single Event
    public function singleEvent($id){
        $feed = Entry::with(['owner','comments.owner','participants.owner','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_EVENT')
            ->where('is_deleted',false)
            ->where('id',$id)
            ->first();
        return response()->json($feed);
    }

    //add Event
    public function addEvent(Request $request){
        $logged = auth()->user();
        $event = new Entry();
        $event->type = "TYPE_EVENT";
        $this->validateEntry($event, $logged);
        $event->createdby_id = auth()->id();
        $event->title = $request->get('title');
        $event->description = $request->get('description');
        $event->is_featured = 0;
        $event->start_date = $request->get('start_date');
        $event->end_date = $request->get('end_date');
        $event->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $medias = $request->get('medias');
            $count = 0;
            foreach ($medias as $key => $media) {
                $count++;
                if (isset($request->files->get('medias')[$key]['file'])) {
                    $destinationPath = 'medias/events/';
                    $filename = date('YmdHis') . "." . $request->files->get('medias')[$key]['file']->getClientOriginalExtension();
                    $request->files->get('medias')[$key]['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$event->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $count === 1
                    ]);
                } else {
                    Media::create([
                        "entry_id" =>$event->id,
                        "createdby_id" => $logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $count === 1
                    ]);
                }
            }
        }
        //NewEntryNotification
        $users = User::where('id','!=',auth()->id())->get();
        foreach ($users as $user) {
            $user->notify(new NewEntryNotification($event));
        }
        $return = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_EVENT')
            ->where('id',$event->id)
            ->first();
        return response()->json($return);
    }

    //edit Event
    public function editEvent(Request $request, $id){
        $logged = auth()->user();
        $event = Entry::where('type','TYPE_EVENT')->where('id', $id)->first();
        if (!$event)
            return response()->json(['message' => 'Event Not Found'], 404);
        $event->is_valid = true;
        $event->title = $request->get('title');
        $event->description = $request->get('description');
        $event->is_featured = $request->get('is_featured');
        $event->start_date = $request->get('start_date');
        $event->end_date = $request->get('end_date');
        $event->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $event->medias()->delete();
            $medias = $request->get('medias');
            foreach ($medias as $key => $media) {
                $first = $key == 0;
                if (isset($media['file'])) {
                    $destinationPath = 'medias/events/';
                    $filename = date('YmdHis') . "." . $media['file']->getClientOriginalExtension();
                    $media['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$event->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $first
                    ]);
                } else {
                    Media::create([
                        "entry_id" =>$event->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $first
                    ]);
                }
            }
        }
        $return = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_EVENT')
            ->where('id',$event->id)
            ->first();
        return response()->json($return);
    }

    //delete Event
    public function deleteEvent($id){
        $logged = auth()->user();
        $event = Entry::where('type','TYPE_EVENT')->where('id', $id)->first();
        if (!$event)
            return response()->json(['message' => 'Event Not Found'], 404);
        if ($logged->isAdmin()) {
            $event->is_deleted = true;
        }
        $feeds = Entry::with('owner')
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_EVENT')
            ->where('is_deleted',false)
            ->get();
        return response()->json($feeds);
    }

    //list Article
    public function listArticle(Request $request){
        $articles = Entry::with(['owner','medias' => function($q) {
            return $q->where('is_main', true);
        }])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_ARTICLE')
            ->where('is_deleted',false)
            ->orderBy('created_at','desc')
            ->get();
        return response()->json($articles);
    }

    //single Article
    public function singleArticle($id){
        $feed = Entry::with(['owner','comments.owner','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_ARTICLE')
            ->where('is_deleted',false)
            ->where('id',$id)
            ->first();
        return response()->json($feed);
    }

    //add Article
    public function addArticle(Request $request){
        $logged = auth()->user();
        $article = new Entry();
        $article->type = "TYPE_ARTICLE";
        $this->validateEntry($article, $logged);
        $article->createdby_id = auth()->id();
        $article->title = $request->get('title');
        $article->content = $request->get('content');
        $article->is_featured = 0;
        $article->start_date = $request->get('start_date');
        $article->end_date = $request->get('end_date');
        $article->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $medias = $request->get('medias');
            $count = 0;
            foreach ($medias as $key => $media) {
                $count++;
                if (isset($request->files->get('medias')[$key]['file'])) {
                    $destinationPath = 'medias/articles/';
                    $filename = date('YmdHis') . "." . $request->files->get('medias')[$key]['file']->getClientOriginalExtension();
                    $request->files->get('medias')[$key]['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$article->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $count === 1
                    ]);
                } else {
                    Media::create([
                        "entry_id" =>$article->id,
                        "createdby_id" => $logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $count === 1
                    ]);
                }
            }
        }
        //NewEntryNotification
        $users = User::where('id','!=',auth()->id())->get();
        foreach ($users as $user) {
            $user->notify(new NewEntryNotification($article));
        }
        $return = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_ARTICLE')
            ->where('id',$article->id)
            ->first();
        return response()->json($return);
    }

    //edit Article
    public function editArticle(Request $request, $id){
        $logged = auth()->user();
        $article = Entry::where('type','TYPE_ARTICLE')->where('id', $id)->first();
        if (!$article)
            return response()->json(['message' => 'Article Not Found'], 404);
        $article->is_valid = true;
        $article->title = $request->get('title');
        $article->description = $request->get('description');
        $article->is_featured = $request->get('is_featured');
        $article->start_date = $request->get('start_date');
        $article->end_date = $request->get('end_date');
        $article->save();
        if ($request->has('medias') && !!$request->get('medias')) {
            $article->medias()->delete();
            $medias = $request->get('medias');
            $count = 0;
            foreach ($medias as $key => $media) {
                $count++;
                if (isset($request->files->get('medias')[$key]['file'])) {
                    $destinationPath = 'medias/articles/';
                    $filename = date('YmdHis') . "." . $request->files->get('medias')[$key]['file']->getClientOriginalExtension();
                    $request->files->get('medias')[$key]['file']->move($destinationPath, $filename);
                    Media::create([
                        "entry_id" =>$article->id,
                        "createdby_id" =>$logged->id,
                        "type" => $media['type'],
                        "path" => $filename,
                        "is_main" => $count == 1
                    ]);
                } else {
                    Media::create([
                        "entry_id" =>$article->id,
                        "createdby_id" => $logged->id,
                        "type" => $media['type'],
                        "path" => $media['path'],
                        "is_main" => $count == 1
                    ]);
                }
            }
        }
        $return = Entry::with(['owner','comments','likes','medias'])
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_ARTICLE')
            ->where('id',$article->id)
            ->first();
        return response()->json($return);
    }

    //delete Article
    public function deleteArticle($id){
        $logged = auth()->user();
        $article = Entry::where('type','TYPE_ARTICLE')->where('id', $id)->first();
        if (!$article)
            return response()->json(['message' => 'Article Not Found'], 404);
        if ($logged->isAdmin()) {
            $article->is_deleted = true;
        }
        $feeds = Entry::with('owner')
            ->withCount(['comments','likes'])
            ->where('type','=','TYPE_ARTICLE')
            ->where('is_deleted',false)
            ->get();
        return response()->json($feeds);
    }

    //add Annuary
    public function addAnnuary(Request $request){
        $person = new Person($request->except('image'));
        $person->createdby_id = auth()->id();
        if ($image = $request->files->get('image')) {
            $destinationPath = 'medias/people/';
            $filename = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move($destinationPath, $filename);
            $person->picture = $filename;
        }
        $person->save();
        $persons = Person::orderBy('created_at','desc')->get();
        return response()->json($persons);
    }

    //list Annuary
    public function listAnnuary(){
        $persons = Person::orderBy('created_at','desc')->get();
        return response()->json($persons);
    }

    //list Discussions
    public function listDiscussions(){
        $discussions = Discussion::with(['messages' => function($q) {
            return $q->with(['sender','receiver'])
                ->orderBy('created_at','desc')->first();
        }])
            ->where('createdby_id',auth()->id())
            ->orWhere('destination_id',auth()->id())
            ->get();
        return response()->json($discussions);
    }

    //create Discussion
    public function createDiscussion(Request $request){
        $me = auth()->user();
        $him = User::find($request->destination_id);
        $discussion = Discussion::where(function ($q) use ($me, $him) {
            $q->where('createdby_id', $me->id)->where('destination_id', $him->id);
        })
            ->orWhere(function ($q) use ($me, $him) {
                $q->where('createdby_id', $him->id)->where('destination_id', $me->id);
            })
            ->first();
        if (!$discussion) {
            $discussion = Discussion::create([
                "destination_id" => $request->destination_id,
                "createdby_id" => auth()->id()
            ]);
        }
        return response()->json($discussion);
    }

    //list Messages
    public function listMessages($discussion_id){
        $messages = Message::with(['sender','receiver'])
            ->where('discussion_id',$discussion_id)
            ->orderBy('created_at','desc')
            ->get();
        return response()->json($messages);
    }

    //send Message
    public function sendMessage(Request $request, $discussion_id){
        Message::create([
            "createdby_id" => auth()->id(),
            "destination_id" => $request->get('receiver_id'),
            "discussion_id" => $discussion_id,
            "body" => $request->get('content'),
        ]);
        $messages = Message::with(['sender','receiver'])
            ->where('discussion_id',$discussion_id)
            ->orderBy('created_at','desc')
            ->get();
        return response()->json($messages);
    }

    //list Notifications
    public function listNotifications(){
        $user = auth()->user();
        $notifs = $user->notifications;
        return response()->json($notifs);
    }

    //last Notifs
    public function lastNotifs(){
        $user = auth()->user();
        $notifsUnread = $user->unreadNotifications()->count();
        $notifs = $user->notifications()
            ->orderBy('created_at','desc')
            ->take(4)
            ->get();
        return response()->json([
            'unread' => $notifsUnread,
            'notifs' => $notifs
        ]);
    }

    //read Notification
    public function readNotification($id){
        $user = auth()->user();
        $notif = $user->notifications()->find($id);
        $notif->markAsRead();
        return response()->json(['message' => 'success'], 200);
    }

    //read All Notifications
    public function readAllNotifications(){
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();
        $notifs = $user->notifications;
        return response()->json($notifs);
    }

    //delete All Notifications
    public function deleteAllNotifications(){
        $user = auth()->user();
        $user->notifications()->delete();
        return response()->json(['message'=> 'success']);
    }

    //delete Notification
    public function deleteNotification($id){
        $user = auth()->user();
        $notif = $user->notifications()->find($id);
        if ($notif){
            $notif->delete();
            return response()->json(['message'=> 'deleted']);
        }
        return response()->json(['message'=> 'nothing to delete']);
    }

    //add Idea
    public function addIdea(Request $request) {
        $idea = Idea::create([
            "createdby_id" => auth()->id(),
            "content" => $request->get('content')
        ]);
        return response()->json($idea);
    }

    //list Ideas
    public function listIdeas() {
        if (auth()->user()->isAdmin()) {
        $ideas = Idea::orderBy('created_at','desc')->get();
        return response()->json($ideas);
        }
        return response()->json(['message' => 'not admin'], 401);

    }

    protected function validateEntry($entry, $user) {
        if ($user->isAdmin()) {
            $entry->is_valid = true;
        } else {
            $entry->is_valid = false;
        }
        $entry->is_deleted = 0;
    }



    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'role' => auth('api')->user()->role,
            'id' => auth()->id()
        ]);
    }
}
