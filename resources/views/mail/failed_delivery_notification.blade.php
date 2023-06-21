@component('mail::message')

# New Failed Delivery

@if($aliasEmail)
An attempt to deliver a message for your alias **{{ $aliasEmail }}** has failed.
@elseif($recipientEmail)
An attempt to deliver a message to your recipient **{{ $recipientEmail }}** has failed.
@else
An attempt to deliver a message to one of your recipients has failed.
@endif

@if($originalSender)
The message was sent by **{{ $originalSender }}** {{ $originalSubject ? 'with subject: ' . $originalSubject : '' }}

@elseif($originalSubject)
The subject of the message was: **{{ $originalSubject }}**

@endif

@if($isStored)
This email has been **temporarily stored**. You can visit the failed deliveries page to see the reason why this delivery was not successful and to **download the email**.
@else
You can visit the failed deliveries page to see the reason why this delivery was not successful.
@endif

@if($storeFailedDeliveries && $isStored)
You can disable the option to **temporarily store failed deliveries** from the settings page.
@elseif(! $storeFailedDeliveries)
You can enable the option to **temporarily store failed deliveries** from the settings page so that any emails that fail to be delivered can still be **downloaded and viewed**.
@endif

Failed deliveries are automatically deleted **after 7 days**.

@component('mail::button', ['url' => config('app.url').'/failed-deliveries'])
View Failed Deliveries
@endcomponent

@endcomponent
