@component('mail::message')

# Alias Deactivated via One-Click Unsubscribe

Your alias **{{ $aliasEmail }}** has been deactivated because a one-click unsubscribe request was received.

**Important:** This means all emails to this alias will now be discarded, not just emails from the sender in the email that the unsubscribe link was clicked.

@if($ipAddress || $userAgent || $timestamp)
**Request details:**
@if($timestamp)
- **When:** {{ $timestamp }}
@endif
@if($ipAddress)
- **IP:** {{ $ipAddress }}
@endif
@if($userAgent)
- **User Agent:** {{ $userAgent }}
@endif
@endif

If you did not intend to deactivate this alias, you can click the button below and reactivate it.

@component('mail::button', ['url' => config('app.url').'/aliases/'.$aliasId.'/edit'])
View Alias
@endcomponent

@endcomponent
