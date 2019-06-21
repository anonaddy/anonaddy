@if($location === 'top')
This email was sent to {{ $aliasEmail }} from {{ $fromEmail }} and has been forwarded by AnonAddy.
To deactivate this alias copy and paste the url below into your web browser.

{{ $deactivateUrl }}

-----


@endif
{!! $text !!}
@if($location === 'bottom')


-----

This email was sent to {{ $aliasEmail }} from {{ $fromEmail }} and has been forwarded by AnonAddy.
To deactivate this alias copy and paste the url below into your web browser.

{{ $deactivateUrl }}
@endif