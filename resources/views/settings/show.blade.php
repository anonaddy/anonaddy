@extends('layouts.app')

@section('content')
    <div class="container py-8">
        @include('shared.status')

        @if(session('backupCode'))
            <div class="text-sm border-t-8 rounded text-yellow-800 border-yellow-600 bg-yellow-100 px-3 py-4 mb-4" role="alert">
                <div class="flex items-center mb-2">
                    <span class="rounded-full bg-yellow-400 uppercase px-2 py-1 text-xs font-bold mr-2">Important</span>
                    <div>
                        2FA enabled successfully. Please <b>make a copy of your backup code below</b>. If you have an old backup code saved <b>you must update it with this one.</b> If you lose your 2FA device you can use this backup code to disable 2FA on your account. <b>This is the only time this code will be displayed, so be sure not to lose it!</b>
                    </div>
                </div>
                <pre class="flex p-3 text-grey-900 bg-white border rounded">
                    <code class="break-all whitespace-normal">{{ session('backupCode') }}</code>
                </pre>
            </div>
        @endif

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Usage
            </h2>
            <p class="text-grey-500">Account Usage details</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">
            <div>
                <h3 class="font-bold text-xl">
                    Bandwidth
                </h3>

                <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                <p class="mt-6">You've used <b>{{ $user->bandwidth_mb }}MB out of your {{ $user->getBandwidthLimitMb() }}MB limit</b> this calendar month ({{ now()->format('F') }}).</p>
                <p class="mt-4">Your bandwidth usage will reset on <b>{{ now()->addMonthsNoOverflow(1)->startOfMonth()->format('jS F') }}</b>.</p>
                <p class="mt-4">At the start of each calendar month your bandwidth usage is reset to 0. If you get close to your bandwidth limit we'll send you emails to let you know.</p>
                <p class="mt-4">If you go over your limit we will start rejecting emails until your bandwidth usage drops back below your limit.</p>
            </div>
        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Settings
            </h2>
            <p class="text-grey-500">Update preferences</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">

            @if($user->hasVerifiedDefaultRecipient())

                <form method="POST" action="{{ route('settings.default_recipient') }}">
                    @csrf

                    <div class="mb-6">

                        <h3 class="font-bold text-xl">
                            Update Default Recipient
                        </h3>

                        <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                        <p class="mt-6">The default recipient is used for all new aliases and any aliases that do not have any recipients attached. Once an alias has been created in your dashboard you can update the recipient to a different one.</p>

                        <div class="mt-6 flex flex-wrap mb-4">
                            <label for="default-recipient" class="block text-grey-700 text-sm mb-2">
                                {{ __('Select Recipient') }}:
                            </label>

                            <div class="block relative w-full">
                                <select id="default-recipient" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="default_recipient" required>
                                    @foreach($recipientOptions as $recipient)
                                    <option value="{{ $recipient->id }}" {{ $user->email === $recipient->email ? 'selected' : '' }}>{{ $recipient->email }}</option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                </div>
                            </div>

                            @if ($errors->has('default_recipient'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('default_recipient') }}
                                </p>
                            @endif
                        </div>

                    </div>

                    <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                        {{ __('Update Default Recipient') }}
                    </button>

                </form>

            @else

                <form method="POST" action="{{ route('settings.edit_default_recipient') }}">
                    @csrf

                    <div class="mb-6">

                        <h3 class="font-bold text-xl">
                            Update Email
                        </h3>

                        <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                        <p class="mt-6">Made a mistake or typo with the email addresss you signed up with? Update your email below, you'll receive a new email verification link.</p>

                        <div class="mt-6 flex flex-wrap mb-4">
                            <label for="email" class="block text-grey-700 text-sm mb-2">
                                {{ __('Email') }}:
                            </label>

                            <div class="block relative w-full">
                                <input id="email" type="email" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="email" value="{{ old('email') ?? $user->email }}">
                            </div>

                            @if ($errors->has('email'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('email') }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-6 flex flex-wrap mb-4">
                            <label for="email_confirmation" class="block text-grey-700 text-sm mb-2">
                                {{ __('Confirm Email') }}:
                            </label>

                            <div class="block relative w-full">
                                <input id="email_confirmation" type="email" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="email_confirmation">
                            </div>
                        </div>

                    </div>

                    <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                        {{ __('Update Email') }}
                    </button>

                </form>

            @endif

            <form method="POST" action="{{ route('settings.default_alias_domain') }}" class="pt-16">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Update Default Alias Domain
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">The default alias domain is the domain you'd like to be selected by default in the drop down options when generating a new alias on the site or the browser extension. This will save you needing to select your preferred domain from the dropdown each time.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="default-alias-domain" class="block text-grey-700 text-sm mb-2">
                            {{ __('Select Default Domain') }}:
                        </label>

                        <div class="block relative w-full">
                            <select id="default-alias-domain" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="domain" required>
                                @foreach($user->domainOptions() as $domainOption)
                                <option value="{{ $domainOption }}" {{ $user->default_alias_domain === $domainOption ? 'selected' : '' }}>{{ $domainOption }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>

                        @if ($errors->has('domain'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('domain') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update Default Alias Domain') }}
                </button>

            </form>

            <form method="POST" action="{{ route('settings.default_alias_format') }}" class="pt-16">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Update Default Alias Format
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">The default alias format is the format you'd like to be selected by default in the drop down options when generating a new alias on the site or the browser extension. This will save you needing to select your preferred format from the dropdown each time.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="default-alias-format" class="block text-grey-700 text-sm mb-2">
                            {{ __('Select Default Format') }}:
                        </label>

                        <div class="block relative w-full">
                            <select id="default-alias-format" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="format" required>
                                <option value="random_characters" {{ $user->default_alias_format === 'random_characters' ? 'selected' : '' }}>Random Characters</option>
                                <option value="uuid" {{ $user->default_alias_format === 'uuid' ? 'selected' : '' }}>UUID</option>
                                <option value="random_words" {{ $user->default_alias_format === 'random_words' ? 'selected' : '' }}>Random Words</option>
                                <option value="custom" {{ $user->default_alias_format === 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>

                        @if ($errors->has('format'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('format') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update Default Alias Format') }}
                </button>

            </form>

            <form class="pt-16" method="POST" action="{{ route('settings.use_reply_to') }}">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Use Reply-To Header For Replying
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">This will determine if forwarded emails use the From header or the Reply-To header for sending replies. Some users may find it easier to set up inbox filters having the From: header set as just the alias.
                    </p>
                    <p class="mt-4">If enabled, then the <b>From:</b> header will be set as the alias email e.g. <b>alias{{ '@'.$user->username }}.{{ config('anonaddy.domain') }}</b> instead of the default <b class="break-words">alias+sender=example.com{{ '@'.$user->username }}.{{ config('anonaddy.domain') }}</b> (this will be set as the Reply-To header instead)</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="use_reply_to" class="block text-grey-700 text-sm mb-2">
                            {{ __('Update Use Reply-To') }}:
                        </label>

                        <div class="block relative w-full">
                            <select id="use_reply_to" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="use_reply_to" required>
                                <option value="1" {{ $user->use_reply_to ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ ! $user->use_reply_to ? 'selected' : '' }}>Disabled</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>

                        @if ($errors->has('use_reply_to'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('use_reply_to') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    Update Use Reply-To
                </button>
            </form>

            <form method="POST" action="{{ route('settings.store_failed_deliveries') }}" class="pt-16">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Store Failed Deliveries
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">This setting allows you to choose whether or not AnonAddy should <b>temporarily store</b> failed delivery attempts, this ensures that <b>emails are not lost</b> if they are rejected by your recipients as they can be downloaded from the failed deliveries page. Failed deliveries are <b>automatically deleted after 7 days</b>.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="store_failed_deliveries" class="block text-grey-700 text-sm mb-2">
                            {{ __('Store Failed Deliveries') }}:
                        </label>

                        <div class="block relative w-full">
                            <select id="store_failed_deliveries" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="store_failed_deliveries" required>
                                <option value="1" {{ $user->store_failed_deliveries === true ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ $user->store_failed_deliveries === false ? 'selected' : '' }}>Disabled</option>

                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>

                        @if ($errors->has('store_failed_deliveries'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('store_failed_deliveries') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    Update Store Failed Deliveries
                </button>

            </form>

            <form id="update-password" method="POST" action="{{ route('settings.password') }}" class="pt-16">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Update Password
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">Ensure your account is using a long, random, unique password to stay secure. It is recommended to use a password manager such as BitWarden. Updating your password will also logout your active sessions on other browsers and devices.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="current" class="block text-grey-700 text-sm mb-2">
                            {{ __('Current Password') }}:
                        </label>

                        <input id="current" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('current') ? ' border-red-500' : '' }}" name="current" placeholder="********" required>

                        @if ($errors->has('current'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('current') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap mb-4">
                        <label for="password" class="block text-grey-700 text-sm mb-2">
                            {{ __('New Password') }}:
                        </label>

                        <input id="password" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('password') ? ' border-red-500' : '' }}" name="password" placeholder="********" required>

                        @if ($errors->has('password'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('password') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap">
                        <label for="password-confirm" class="block text-grey-700 text-sm mb-2">
                            {{ __('Confirm New Password') }}:
                        </label>

                        <input id="password-confirm" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring" name="password_confirmation" placeholder="********" required>
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update Password') }}
                </button>

            </form>

            <form id="logout-browser-sessions" method="POST" action="{{ route('browser-sessions.destroy') }}" class="pt-16">
                @method('DELETE')
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Browser Sessions
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">If necessary, you may logout of all of your other browser sessions across all of your devices. If you feel your account has been compromised, you should also update your password.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="current-password-sessions" class="block text-grey-700 text-sm mb-2">
                            {{ __('Current Password') }}:
                        </label>

                        <input id="current-password-sessions" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('current_password_sesssions') ? ' border-red-500' : '' }}" name="current_password_sesssions" placeholder="********" required>

                        @if ($errors->has('current_password_sesssions'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('current_password_sesssions') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Logout Other Browser Sessions') }}
                </button>

            </form>

        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Two-Factor Authentication
            </h2>
            <p class="text-grey-500">Manage your 2FA options</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">

            <div id="two-factor">

                <h3 class="font-bold text-xl">
                Information
                </h3>

                <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                <p class="mt-6">
                    Two-factor authentication, also known as 2FA or multi-factor, adds an extra layer of security to your account beyond your username and password. There are <b>two options for 2FA</b> - Authentication App (e.g. Google Authenticator or another, Aegis, andOTP) or Hardware Security Key (e.g. YubiKey, SoloKey, Nitrokey).
                </p>

                <p class="mt-4 pb-16">
                    When you login with 2FA enabled, you will be prompted to use a security key or enter a OTP (one time passcode) depending on which method you choose below. You can only have one method of 2nd factor authentication enabled at once.
                </p>

                @if($user->two_factor_enabled || LaravelWebauthn\Facades\Webauthn::enabled($user))
                    <h3 class="font-bold text-xl">
                        Generate New Backup Code
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="my-4">
                        The backup code can be used in a situation where you have lost your 2FA device to allow you to access your account.
                        If you've forgotten or lost your backup code then you can generate a new one by clicking the button below. <b>This code will only be displayed once</b> so make sure you store it in a <b>secure place</b>.
                    </p>

                    <form method="POST" action="{{ route('settings.new_backup_code') }}" class="pb-16">
                        @csrf
                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                            Generate New Backup Code
                        </button>
                    </form>
                @endif

                @if($user->two_factor_enabled)

                    <form method="POST" action="{{ route('settings.2fa_disable') }}">
                        @csrf

                        <div class="mb-6">

                            <h3 class="font-bold text-xl">
                                Disable Authentication App (TOTP)
                            </h3>

                            <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                            <p class="mt-6">To disable 2 factor authentication enter your password below. You can always enable it again later if you wish.</p>

                            <div class="mt-6 flex flex-wrap">
                                <label for="current_password_2fa" class="block text-grey-700 text-sm mb-2">
                                    {{ __('Current Password') }}:
                                </label>

                                <input id="current_password_2fa" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('current_password_2fa') ? ' border-red-500' : '' }}" name="current_password_2fa" placeholder="********" required>

                                @if ($errors->has('current_password_2fa'))
                                    <p class="text-red-500 text-xs italic mt-4">
                                        {{ $errors->first('current_password_2fa') }}
                                    </p>
                                @endif
                            </div>

                        </div>

                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                            {{ __('Disable') }}
                        </button>

                    </form>

                @else

                    @if(LaravelWebauthn\Facades\Webauthn::enabled($user))

                        <webauthn-keys />

                    @else

                        <div class="mb-6">

                            <h3 class="font-bold text-xl">
                                Enable Authentication App (TOTP)
                            </h3>

                            <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                            <p class="mt-6">TOTP 2 factor authentication requires the use of Google Authenticator or another compatible app such as Aegis or andOTP (both on F-droid) for Android. Alternatively, you can use the code below. Make sure that you write down your secret code in a safe place.</p>

                            <div>
                                {!! $qrCode !!}
                                <p class="mb-2">Secret: {{ $authSecret }}</p>
                                <form method="POST" action="{{ route('settings.2fa_regenerate') }}">
                                    @csrf
                                    <input type="submit" class="text-indigo-900 bg-transparent cursor-pointer" value="Click here to regenerate your secret key">

                                    @if ($errors->has('regenerate_2fa'))
                                        <p class="text-red-500 text-xs italic mt-4">
                                            {{ $errors->first('regenerate_2fa') }}
                                        </p>
                                    @endif
                                </form>
                            </div>

                        </div>

                        <form method="POST" action="{{ route('settings.2fa_enable') }}">
                            @csrf
                            <div class="my-6 flex flex-wrap">
                                <label for="two_factor_token" class="block text-grey-700 text-sm mb-2">
                                    {{ __('Verify and Enable') }}:
                                </label>

                                <div class="block relative w-full">
                                    <input id="two_factor_token" type="text" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="two_factor_token" placeholder="123456" />
                                </div>

                                @if ($errors->has('two_factor_token'))
                                    <p class="text-red-500 text-xs italic mt-4">
                                        {{ $errors->first('two_factor_token') }}
                                    </p>
                                @endif
                            </div>
                            <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                                {{ __('Verify and Enable') }}
                            </button>
                        </form>

                        <div class="pt-16">

                            <h3 class="font-bold text-xl">
                                Enable Device Authentication (WebAuthn)
                            </h3>

                            <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                            <p class="my-6">WebAuthn is a new W3C global standard for secure authentication. You can use any hardware key such as a Yubikey, Solokey, NitroKey etc.</p>

                            <a
                            type="button"
                            href="{{ route('webauthn.create') }}"
                            class="block bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none text-center"
                            >
                                Register New Hardware Key
                            </a>

                        </div>

                    @endif
                @endif
            </div>

        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Other Settings
            </h2>
            <p class="text-grey-500">Update your other account preferences</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">

            <form class="mb-16" method="POST" action="{{ route('settings.from_name') }}">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Update From Name
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">The from name is shown when you reply anonymously to a forwarded email. If set to empty then the email alias will be used as the from name e.g. "ebay{{ '@'.$user->username }}.{{ config('anonaddy.domain') }}".</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="from_name" class="block text-grey-700 text-sm mb-2">
                            {{ __('From Name') }}:
                        </label>

                        <div class="block relative w-full">
                            <input id="from_name" type="text" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="from_name" value="{{ $user->from_name }}" placeholder="John Doe" />
                        </div>

                        @if ($errors->has('from_name'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('from_name') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update From Name') }}
                </button>

            </form>

            <form class="mb-16" method="POST" action="{{ route('settings.banner_location') }}">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Update Email Banner Location
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">This is the information displayed in forwarded emails letting you know who the email was from and which alias it was sent to. You can choose for it to be displayed at the top or bottom of the email or just turn if off altogether.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="banner_location" class="block text-grey-700 text-sm mb-2">
                            {{ __('Update Location') }}:
                        </label>

                        <div class="block relative w-full">
                            <select id="banner_location" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="banner_location" required>
                                <option value="top" {{ $user->banner_location === 'top' ? 'selected' : '' }}>Top</option>
                                <option value="bottom" {{ $user->banner_location === 'bottom' ? 'selected' : '' }}>Bottom</option>
                                <option value="off" {{ $user->banner_location === 'off' ? 'selected' : '' }}>Off</option>

                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>

                        @if ($errors->has('banner_location'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('banner_location') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update Banner Location') }}
                </button>

            </form>

            <form method="POST" action="{{ route('settings.email_subject') }}">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Replace Email Subject
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">This is useful if you are <b>using encryption</b>. After you add your public GPG/OpenPGP key for a recipient the body of forwarded emails will be encrypted (this includes email attachments). Unfortunately the email subject cannot be encrypted as it is one of the headers. To prevent revealing the contents of emails you can replace the subject with something generic below e.g. "The subject" or "Hello".</p>
                    <p class="mt-4">If set to empty then the email's original subject will be used.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="email_subject" class="block text-grey-700 text-sm mb-2">
                            {{ __('Email Subject') }}:
                        </label>

                        <div class="block relative w-full">
                            <input id="email_subject" type="text" class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring" name="email_subject" value="{{ $user->email_subject }}" placeholder="The subject" />
                        </div>

                        @if ($errors->has('email_subject'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('email_subject') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                    {{ __('Update Email Subject') }}
                </button>

            </form>

        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                API
            </h2>
            <p class="text-grey-500">Manage your API Access Tokens</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">

            <personal-access-tokens />

        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Data
            </h2>
            <p class="text-grey-500">Manage your account data</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow mb-10">

            <div class="mb-6">
                <h3 class="font-bold text-xl">
                    Import Aliases
                </h3>

                <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                <p class="mt-6">You can import aliases for <b>your custom domains</b> by uploading a CSV file. Please note this is <b>only available for custom domains</b>.</p>


                <p class="mt-4">Aliases that <b>already exist</b> will not be imported.</p>
                <p class="mt-4">The import is <b>limited to 1,000 rows (aliases)</b>. Please ensure you use multiple CSV files if you need to import more than this.</p>
                <p class="mt-4">Please use the template file provided below. Only CSV files are supported.</p>
                <p class="mt-4">The import will take a few minutes. You will <b>receive an email</b> once it is complete.</p>
                <p class="mt-4"><a href="/import-aliases-template.csv" rel="nofollow noopener noreferrer" class="text-indigo-700 cursor-pointer">Click here to download the CSV import template</a></p>
            </div>

            <form action="{{ route('aliases.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <input type="file" name="aliases_import" required />
                    @if ($errors->has('aliases_import'))
                        <p class="text-red-500 text-xs italic mt-4">
                            {{ $errors->first('aliases_import') }}
                        </p>
                    @endif
                    <div class="mt-4">
                        <button type="submit" class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">Import Alias Data</button>
                    </div>
                </div>
            </form>

            <div class="my-6">
                <h3 class="font-bold text-xl">
                    Export Aliases
                </h3>

                <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                <p class="mt-6">You can click the button below to export all the data for your <b>{{ $user->aliases()->withTrashed()->count() }}</b> aliases as a .csv file.</p>
            </div>

            <a href="{{ route('aliases.export') }}" class="bg-cyan-400 block w-full text-center hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                Export Alias Data
            </a>

        </div>

        <div class="mb-4">
            <h2 class="text-3xl font-bold">
                Danger Zone
            </h2>
            <p class="text-grey-500">Irreversible and destructive actions</p>
        </div>

        <div class="px-6 py-8 md:p-10 bg-white rounded-lg shadow">

            <form method="POST" action="{{ route('account.destroy') }}">
                @csrf

                <div class="mb-6">

                    <h3 class="font-bold text-xl">
                        Delete Account
                    </h3>

                    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

                    <p class="mt-6">Once you delete your account, there is no going back. This username will not be able to be used again. Please make sure you are certain.</p>

                    <div class="mt-6 flex flex-wrap mb-4">
                        <label for="current-password-delete" class="block text-grey-700 text-sm mb-2">
                            {{ __('Enter your password to continue') }}:
                        </label>

                        <input id="current-password-delete" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:outline-none focus:ring{{ $errors->has('current_password_delete') ? ' border-red-500' : '' }}" name="current_password_delete" placeholder="********" required>

                        @if ($errors->has('current_password_delete'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('current_password_delete') }}
                            </p>
                        @endif
                    </div>

                </div>

                <button type="submit" class="text-white font-semibold bg-red-500 hover:bg-red-600 w-full py-3 px-4 rounded focus:outline-none">
                    {{ __('Delete Account') }}
                </button>

            </form>
        </div>

    </div>
@endsection
