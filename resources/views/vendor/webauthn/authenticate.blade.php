@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-lg">
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">

                <div class="px-6 py-8 md:p-10">

                    <h1 class="text-center font-bold text-2xl">
                        {{ trans('webauthn::messages.auth.title') }}
                    </h1>

                    <div class="mx-auto my-6 w-24 border-b-2 border-grey-200"></div>

                    <div class="text-sm border-t-8 rounded text-red-800 border-red-600 bg-red-100 px-3 py-4 mb-4 hidden" role="alert" id="error"></div>

                    <div class="text-sm border-t-8 rounded text-green-700 border-green-600 bg-green-100 px-3 py-4 mb-4 hidden" role="alert" id="success">
                        {{ trans('webauthn::messages.success') }}
                    </div>

                    <h3>
                        {{ trans('webauthn::messages.insertKey') }}
                    </h3>

                    <p class="my-4 text-center">
                        <img src="/webauthn.png" alt="security key"/>
                    </p>

                    <p>
                        {{ trans('webauthn::messages.buttonAdvise') }}
                        <br />
                        {{ trans('webauthn::messages.noButtonAdvise') }}
                        <br />
                        If nothing happens then click the button below to authenticate.
                    </p>

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
                        <button onclick="authenticateDevice()" class="flex justify-center bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded ml-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
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