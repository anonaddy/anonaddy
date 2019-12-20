@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <domains :initial-domains="{{json_encode($domains)}}" domain-name="{{config('anonaddy.domain')}}" hostname="{{config('anonaddy.hostname')}}" :recipient-options="{{ json_encode(Auth::user()->verifiedRecipients) }}" aa-verify="{{ sha1(config('anonaddy.secret') . Auth::user()->id) }}" />
    </div>
@endsection