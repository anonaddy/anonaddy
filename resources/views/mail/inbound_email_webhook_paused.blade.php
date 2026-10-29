@component('mail::message')

# Webhook paused

Webhooks to **{{ $url }}** are paused for {{ $pauseFor }}.

The last {{ $failureCount }} deliveries to this URL failed. This instance will try this URL again after the pause. A successful delivery removes the pause.

Your rules stay active. Other actions on those rules still run.

@component('mail::button', ['url' => config('app.url').'/rules'])
View Rules
@endcomponent

@endcomponent
