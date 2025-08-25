@component('mail::message')

# New Recipient Verified

A new recipient with the email **{{ $newRecipient }}** has just been added and verified on your addy.io account.

If this recipient was **not added by you**, please visit the settings page, click the button to log out of all other browser sessions and then update your account's password.

You should also check if any of your account's API keys may have been compromised.

@component('mail::button', ['url' => config('app.url').'/settings'])
Check Settings
@endcomponent
@endcomponent
