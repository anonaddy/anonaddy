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
                        <button onclick="authenticateDevice()" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto">
                            Authenticate
                        </button>
                    </div>

                </div>

                <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap justify-between">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <input type="submit" class="bg-transparent cursor-pointer no-underline font-medium text-indigo-600 hover:text-indigo-500" value="{{ __('Logout') }}">
                    </form>
                    <a class="font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('login.backup_code.index') }}">Use backup code</a>
                </div>
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