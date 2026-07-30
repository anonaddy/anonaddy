@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-lg">
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">

                <div class="px-6 py-8 md:p-10">

                    <h1 class="text-center font-bold text-2xl">
                        Sign in with a key or passkey
                    </h1>

                    <div class="mx-auto my-6 w-24 border-b-2 border-grey-200"></div>

                    <div class="text-sm border-t-8 rounded text-red-800 border-red-600 bg-red-100 px-3 py-4 mb-4 hidden" role="alert" id="error"></div>

                    <div class="text-sm border-t-8 rounded text-green-700 border-green-600 bg-green-100 px-3 py-4 mb-4 hidden" role="alert" id="success">
                        {{ trans('webauthn::messages.success') }}
                    </div>

                    <p class="text-grey-700">
                        Press <b>Authenticate</b> below. Your browser will prompt you to use one of
                        your registered credentials:
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

                    <form method="POST" onsubmit="authenticateDevice();return false" action="{{ route('webauthn.auth') }}" id="form">
                        @csrf
                        <input type="hidden" name="id" id="id">
                        <input type="hidden" name="rawId" id="rawId">
                        <input type="hidden" name="response[authenticatorData]" id="authenticatorData">
                        <input type="hidden" name="response[clientDataJSON]" id="clientDataJSON">
                        <input type="hidden" name="response[signature]" id="signature">
                        <input type="hidden" name="response[userHandle]" id="userHandle">
                        <input type="hidden" name="type" id="type">

                    </form>

                    <div class="mt-4">
                        <button onclick="authenticateDevice()" class="flex justify-center bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded ml-auto focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg> Authenticate
                        </button>
                    </div>

                </div>

                @if (Auth::user()->two_factor_enabled)
                <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 text-center">
                    <a  class="flex justify-center font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('login.2fa') }}"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>Use Authentication App (TOTP) Instead</a>
                </div>
                @endif
            </div>
            <div class="flex justify-between mt-6">
                <form action="{{ route('logout') }}" method="POST" class="text-xs">
                    {{ csrf_field() }}
                    <input type="submit" class="bg-transparent cursor-pointer text-white hover:text-indigo-50 no-underline" value="{{ __('Logout') }}">
                </form>
                <a class="text-xs text-white hover:text-indigo-50" href="{{ route('login.backup_code.index') }}">Use backup code</a>
            </div>
        </div>
    </div>
@endsection

@section('webauthn')
    <script>
        var publicKey = {!! json_encode($publicKey) !!};
    </script>

    @vite('resources/js/webauthn/authenticate.js')
@endsection