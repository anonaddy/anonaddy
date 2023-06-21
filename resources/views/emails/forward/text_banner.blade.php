<!--banner-info-->
This email was sent to {{ $aliasEmail }}{{ $aliasDescription ? ' (' . $aliasDescription . ')' : '' }} from {{ $fromEmail }}{!! $replacedSubject !!}.
To deactivate this alias copy and paste the url below into your web browser.

{{ $deactivateUrl }}
<!--banner-info-->