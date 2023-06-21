@component('mail::message')

# Domain Unverified For Sending

A recent DNS record check on your custom domain **{{ $domain }}** failed on AnonAddy. This means that your domain had been unverified for sending until the DNS records are added correctly.

The check failed for the following reason:

**{{ $reason }}**

Please visit the domains page on the site by clicking the button below to resolve the issue.

Emails for your custom domain will be sent from an AnonAddy domain in the mean time.

@component('mail::button', ['url' => config('app.url').'/domains'])
Check Domain
@endcomponent
@endcomponent
