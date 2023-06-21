@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        <aliases :default-recipient-email="{{json_encode($defaultRecipientEmail)}}" :initial-aliases="{{json_encode($aliases)}}" :recipient-options="{{json_encode($recipients)}}" :total-forwarded="{{$totals->forwarded}}" :total-blocked="{{$totals->blocked}}" :total-replies="{{$totals->replies}}" domain="{{config('anonaddy.domain')}}" subdomain="{{$domain}}" :bandwidth-mb="{{$user->bandwidth_mb}}" :month="{{json_encode(now()->format('M'))}}" :domain-options="{{$domainOptions}}" default-alias-domain="{{$user->default_alias_domain}}" default-alias-format="{{$user->default_alias_format}}" />
    </div>
@endsection