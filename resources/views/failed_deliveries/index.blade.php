@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <failed-deliveries :initial-failed-deliveries="{{json_encode($failedDeliveries)}}"/>
    </div>
@endsection