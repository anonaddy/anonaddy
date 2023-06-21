@component('mail::message')

# Failed two factor authentication login attempt

Someone just entered an incorrect OTP while trying to login to your AnonAddy account. The username (**{{ $username }}**) and password were correct.

The login has been blocked. If this was you, then you can ignore this notification.

If this **was not you** then please login and **change your password immediately**.

@component('mail::button', ['url' => config('app.url').'/settings'])
Change Password
@endcomponent
@endcomponent
