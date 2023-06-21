@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <recipients :user="{{json_encode($user)}}" :initial-recipients="{{json_encode($recipients)}}" :aliases-using-default="{{json_encode($aliasesUsingDefault)}}" :aliases-using-default-count="{{$aliasesUsingDefaultCount}}" domain="{{config('anonaddy.domain')}}" />
    </div>
@endsection