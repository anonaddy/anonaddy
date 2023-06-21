@component('mail::message')

# Bandwidth Usage Warning

You've used **{{ $bandwidthUsage }}MB out of your {{ $bandwidthLimit }}MB limit** this calendar month ({{ $month }}).</p>

Your bandwidth usage will reset on **{{ $reset }}**.

At the start of each calendar month your bandwidth usage is **reset to 0**.

If you go over your limit we will start rejecting emails until your bandwidth usage drops back below your limit.

@component('mail::button', ['url' => config('app.url').'/settings'])
Check Usage
@endcomponent
@endcomponent
