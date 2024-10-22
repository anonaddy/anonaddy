@component('mail::message')

# Your API key expires soon

@if($tokenName)
Your API key named "**{{ $tokenName }}**" on your {{ config('app.name') }} account expires in **one weeks time**.
@else
One of the API keys on your {{ config('app.name') }} account will expire in **one weeks time**.
@endif

If you are not using this API key for the browser extensions, mobile apps or to access the API then you do not need to take any action.

If you **are using the key** for the any of the above then please log into your account and generate a new API key to replace this one before your current one expires.

Once an API key has expired it can no longer be used to access the API.

@endcomponent
