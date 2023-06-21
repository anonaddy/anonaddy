@component('mail::message')

# Username Reminder

The account associated with this email address has the following username: **{{ $username }}**

If you've also forgotten your password you can use this username to reset it.

@component('mail::button', ['url' => config('app.url').'/login'])
Login Now
@endcomponent
@endcomponent
