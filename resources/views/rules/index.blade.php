@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <rules :initial-rules="{{ json_encode($rules) }}" />
    </div>
@endsection