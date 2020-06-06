<?php

namespace App\Http\Controllers;

use App\Chat;
use App\chatId;
use App\User;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return view('home', compact("users"));
    }


    public function findOrGenerateChatAppId($user)
    {
        $chat_id = chatId::where(function ($query) use ($user) {
            $query->where('first_user_id', $user)
                ->where('second_user_id', Auth::id());
        })->orWhere(function ($query) use ($user) {
            $query->where('second_user_id', $user)
                ->where('first_user_id', Auth::id());
        })->first();

        if (empty($chat_id)) {
            $chat_id = new  chatId();
            $chat_id->first_user_id = Auth::id();
            $chat_id->second_user_id = $user;
            $chat_id->key = "";
            $chat_id->save();
            $chat_id->key = "chat_" . $chat_id->id;
            $chat_id->save();

        }
        return redirect()->route("chat-room",$chat_id->key);

        return $chat_id;


    }
}
