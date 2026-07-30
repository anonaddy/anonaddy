@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-lg">
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">

                <div class="px-6 py-8 md:p-10">

                    <h1 class="text-center font-bold text-2xl">
                        Register a key or passkey
                    </h1>

                    <div class="mx-auto my-6 w-24 border-b-2 border-grey-200"></div>

                    <div class="text-sm border-t-8 rounded text-red-800 border-red-600 bg-red-100 px-3 py-4 mb-4 hidden" role="alert" id="error"></div>

                    <div class="text-sm border-t-8 rounded text-green-700 border-green-600 bg-green-100 px-3 py-4 mb-4 hidden" role="alert" id="success">
                        {{ trans('webauthn::messages.success') }}
                    </div>

                    <p class="text-grey-700">
                        Name your credential, then press <b>Continue</b> below. Your browser will
                        prompt you to use one of the following:
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-6">
                        <div class="border border-grey-200 rounded-lg p-4 flex flex-col items-center text-center bg-grey-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-indigo-700 mb-2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                            <div class="font-semibold text-grey-900">Hardware security key</div>
                            <p class="text-sm text-grey-600 mt-1">
                                Insert your YubiKey, SoloKey, Nitrokey, etc. and press its button when prompted.
                            </p>
                        </div>
                        <div class="border border-grey-200 rounded-lg p-4 flex flex-col items-center text-center bg-grey-50">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-indigo-700 mb-2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />
                            </svg>
                            <div class="font-semibold text-grey-900">Passkey or device</div>
                            <p class="text-sm text-grey-600 mt-1">
                                Use Touch ID, Face ID, Windows Hello, or a passkey from 1Password, Bitwarden, iCloud Keychain, etc.
                            </p>
                        </div>
                    </div>

                    <form method="POST" onsubmit="registerDevice();return false"  class="mt-8" action="{{ route('webauthn.store') }}" id="form">
                        @csrf
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="rawId" id="rawId">
                        <input type="hidden" name="response[attestationObject]" id="attestationObject">
                        <input type="hidden" name="response[clientDataJSON]" id="clientDataJSON">
                        <input type="hidden" name="type" id="type">

                        <label for="name" class="block text-grey-700 text-sm mb-2">
                            Name:
                        </label>
                        <input type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring" name="name" id="name" placeholder="e.g. MacBook Touch ID, 1Password, YubiKey" autocomplete="off" required autofocus>

                        @if ($errors->has('name'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('name') }}
                            </p>
                        @endif

                        <label for="password" class="block text-grey-700 text-sm font-medium leading-6 mt-4 mb-2">
                            Current Password
                        </label>
                        <input type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring" name="password" id="password" placeholder="********" required>

                        @error('password')
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('password') }}
                            </p>
                        @enderror
                    </form>

                </div>

                <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap items-center">
                    <button onclick="registerDevice()" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 ml-auto">
                        Continue
                    </button>
                </div>
            </div>
            <p class="w-full text-xs text-center text-indigo-100 mt-6">
                Changed your mind?
                <a class="text-white hover:text-indigo-50 no-underline" href="{{ route('settings.security') }}">
                    {{ trans('webauthn::messages.cancel') }}
                </a>
            </p>
        </div>
    </div>
@endsection

@section('webauthn')
    <script>
        var publicKey = {!! json_encode($publicKey) !!};
    </script>

    @vite('resources/js/webauthn/register.js')
@endsection