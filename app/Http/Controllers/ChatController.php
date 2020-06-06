<?php

namespace App\Http\Controllers;

use App\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'content' => 'required',
            'name' => 'required'
        ]);

        $input = $request->all();
        $input['ip'] = request()->ip();
        $input['type'] = 'chat';
        $input['name'] = Auth::user()->name;

        $chat = Chat::create($input);
        return response(['data' => $chat], 200);
    }

    public function join(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $input = $request->all();
        $input['content'] = 'join';
        $input['ip'] = request()->ip();
        $input['type'] = 'info';

        $chat = Chat::create($input);
        return response(['data' => $chat], 200);
    }
}
