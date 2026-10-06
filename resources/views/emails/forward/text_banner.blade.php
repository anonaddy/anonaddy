<!--banner-info-->
--------------------
To: {{ $aliasEmail }}{{ filled($aliasDescription) ? ' ('.$aliasDescription.')' : '' }}
From: {{ $fromEmail }}
@if($replacedSubject)
Original subject: {!! $replacedSubject !!}
@endif
@if(! empty($deactivateUrl))
Deactivate:
{!! $deactivateUrl !!}
@endif
@if(! empty($blockEmailUrl))
{{ "\n" }}Block email:
{!! $blockEmailUrl !!}
@endif
@if(! empty($blockDomainUrl))
{{ "\n" }}Block domain:
{!! $blockDomainUrl !!}
@endif
--------------------
<!--banner-info-->
