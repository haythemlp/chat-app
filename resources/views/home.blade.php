@extends('layouts.app')

@section('content')

    <ul class="list-group my-5">
        @foreach($users as $user)
        <li class="list-group-item"><a href="{{route("generateChat",$user->id)}}">{{$user->name}}</a></li>
        @endforeach

    </ul>

@endsection

