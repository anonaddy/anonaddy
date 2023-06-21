@component('mail::message')

# Disallowed Reply/Send Attempt

An attempt to send or reply from your alias **{{ $aliasEmail }}** was just made which failed because your recipient **{{ $recipient }}** has disallowed replying and sending.

The attempt was trying to send the message to the following destination: **{{ $destination }}**

If this attempt was made by you, then you need to visit the [recipients page]({{ config('app.url').'/recipients' }}) and update the "can reply/send" setting for **{{ $recipient }}**.

If this attempt was not made by you, then someone else may be attempting to send a message from your alias. Make sure you have a suitable DMARC policy in place (with p=quarantine or p=reject) along with SPF and DKIM records to protect your recipient's email address from being spoofed.

@if($authenticationResults)
These are the authentication results for the message:

{{ $authenticationResults }}
@endif

@endcomponent
