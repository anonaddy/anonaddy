@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-lg">
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">

                <div class="px-6 py-8 md:p-10">

                    <h1 class="text-center font-bold text-2xl">
                        {{ trans('webauthn::messages.register.title') }}
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
                    </p>

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
                        <input type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring" name="name" id="name" placeholder="Yubikey" autocomplete="off" required autofocus>

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
                    <button onclick="registerDevice()" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto">
                        Add Device
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