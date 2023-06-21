@component('mail::message')

# GPG Key Encryption Error

An error occured while trying to encrypt an email recently forwarded to you by AnonAddy.

This was likely caused because the key has expired.

The fingerprint of the key is: **{{ $recipient->fingerprint }}**

Encryption for this recipient has been turned off, please update the key if you wish to continue using encryption.

@component('mail::button', ['url' => config('app.url').'/recipients'])
Update Key
@endcomponent
@endcomponent
