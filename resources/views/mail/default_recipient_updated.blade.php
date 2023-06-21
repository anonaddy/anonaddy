@component('mail::message')

# Default Recipient Updated

Your account's default recipient has just been updated from **{{ $defaultRecipient }}** to **{{ $newDefaultRecipient }}**.

If this change was not made by you, please visit the settings page, log out of all other browser sessions and update your account's password.

@component('mail::button', ['url' => config('app.url').'/settings'])
Check Settings
@endcomponent
@endcomponent
