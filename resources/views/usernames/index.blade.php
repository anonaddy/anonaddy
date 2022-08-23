@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <usernames :user="{{json_encode(Auth::user())}}" :initial-usernames="{{json_encode($usernames)}}" :username-count="{{config('anonaddy.additional_username_limit')}}" :recipient-options="{{ json_encode(Auth::user()->verifiedRecipients()->select(['id', 'email'])->get()) }}" />
    </div>
@endsection