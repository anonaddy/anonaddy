@component('mail::message')

# New Failed Delivery

@if($aliasEmail)
An attempt to deliver a message for your alias **{{ $aliasEmail }}** has failed.
@else
An attempt to deliver a message for one of your aliases has failed.
@endif

@if($originalSender)
The message was sent by **{{ $originalSender }}** {{ $originalSubject ? 'with subject: ' . $originalSubject : '' }}

@elseif($originalSubject)
The subject of the message was: **{{ $originalSubject }}**

@endif

You can head over to the failed deliveries page to see the reason why this delivery was not successful.

@component('mail::button', ['url' => config('app.url').'/failed-deliveries'])
View Failed Deliveries
@endcomponent

@endcomponent
