@component('mail::message')

# Attempted Reply/Send Failed

An attempt to send or reply from your alias **{{ $aliasEmail }}** was just made from **{{ $recipient }}** which failed because it didn't pass authentication checks and could be spoofed.

In order to send or reply from an alias there must be a valid DMARC policy present for **{{ \Illuminate\Support\Str::afterLast($recipient, '@') }}** and your message must be permitted by that DMARC policy.

The attempt was trying to send the message to the following destination: **{{ $destination }}**

@if($authenticationResults)
These are the authentication results for the message:

{{ $authenticationResults }}
@endif

If this attempt was made by yourself, then you need to @if($authenticationResults) inspect the authentication results above and @endif make sure your recipient's domain (**{{ \Illuminate\Support\Str::afterLast($recipient, '@') }}**) has the correct DNS records in place; SPF, DKIM and DMARC.

If this attempt was not made by you, then someone else may be attempting to send a message from your alias. Make sure you have a suitable DMARC policy in place (with p=quarantine or p=reject) along with SPF and DKIM records to protect your recipient's email address from being spoofed.

@endcomponent
