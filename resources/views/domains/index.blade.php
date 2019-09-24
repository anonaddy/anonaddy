@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <domains :initial-domains="{{json_encode($domains)}}" hostname="{{config('anonaddy.hostname')}}" :recipient-options="{{ json_encode(Auth::user()->verifiedRecipients) }}" />
    </div>
@endsection