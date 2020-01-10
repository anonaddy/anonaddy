@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <aliases :default-recipient="{{json_encode($defaultRecipient)}}" :initial-aliases="{{json_encode($aliases)}}" :recipient-options="{{json_encode($recipients)}}" :total-forwarded="{{$totalForwarded}}" :total-blocked="{{$totalBlocked}}" :total-replies="{{$totalReplies}}" domain="{{config('anonaddy.domain')}}" subdomain="{{$domain}}" :bandwidth-mb="{{$bandwidthMb}}" :month="{{json_encode(now()->format('M'))}}" :domain-options="{{$domainOptions}}" />
    </div>
@endsection