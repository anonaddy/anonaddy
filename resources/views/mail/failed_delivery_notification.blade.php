@component('mail::message')

@if($quarantined)
# New Quarantined Email

@if($aliasEmail)
A message for your alias **{{ $aliasEmail }}** has been quarantined because it looks like spam or it failed DMARC checks.
@elseif($recipientEmail)
A message to your recipient **{{ $recipientEmail }}** has been quarantined because it looks like spam or it failed DMARC checks.
@else
A message to one of your recipients has been quarantined because it looks like spam or it failed DMARC checks.
@endif
@else
# New Failed Delivery

@if($aliasEmail)
An attempt to deliver an outbound message for your alias **{{ $aliasEmail }}** has failed because the remote mail server (**{{ $remoteMta }}**) rejected it.
@elseif($recipientEmail)
An attempt to deliver a message to your recipient **{{ $recipientEmail }}** has failed your recipient's mail server (**{{ $remoteMta }}**) rejected it.
@else
An attempt to deliver a message to one of your recipients has failed because the remote mail server (**{{ $remoteMta }}**) rejected it.
@endif
@endif

@if($originalSender)
Sender: **{{ $originalSender }}**<br>
@if($originalSubject)
Subject: **{{ $originalSubject }}**
@endif

@elseif($originalSubject)
The subject of the message was: **{{ $originalSubject }}**

@endif

@if($isStored)
This email has been **temporarily stored**.

You can visit the failed deliveries page, see the reason why this delivery was not successful and **download the email**.
@elseif(! $quarantined)
You can visit the failed deliveries page to see the reason why this delivery was not successful.
@endif

@if($authenticationResults)
These were the authentication results for the message:

{{ $authenticationResults }}
@endif

@if($storeFailedDeliveries && $isStored)
You can disable temporary failed delivery storage in your settings.
@elseif(! $storeFailedDeliveries)
You can enable **temporary failed delivery storage** in your settings to **download and view** undelivered emails.
@endif

@component('mail::button', ['url' => config('app.url').'/failed-deliveries'])
View Failed Deliveries
@endcomponent

@component('mail::subcopy')
Failed deliveries are automatically deleted **after 7 days**.<br>
This notification can be turned off in your account settings.
@endcomponent

@endcomponent
