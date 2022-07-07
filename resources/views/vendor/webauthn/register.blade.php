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
                        <input type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring" name="name" id="name" placeholder="Yubikey" required autofocus>

                        @if ($errors->has('name'))
                            <p class="text-red-500 text-xs italic mt-4">
                                {{ $errors->first('name') }}
                            </p>
                        @endif
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
                <a class="text-white hover:text-indigo-50 no-underline" href="{{ route('settings.show') }}">
                    {{ trans('webauthn::messages.cancel') }}
                </a>
            </p>
        </div>
    </div>

    <script>
        var publicKey = {!! json_encode($publicKey) !!};

        var errors = {
            key_already_used: "{{ trans('webauthn::errors.key_already_used') }}",
            key_not_allowed: "{{ trans('webauthn::errors.key_not_allowed') }}",
            not_secured: "{{ trans('webauthn::errors.not_secured') }}",
            not_supported: "{{ trans('webauthn::errors.not_supported') }}",
        };

        function errorMessage(name, message) {
            switch (name) {
            case 'InvalidStateError':
                return errors.key_already_used;
            case 'NotAllowedError':
                return errors.key_not_allowed;
            default:
                return message;
            }
        }

        function error(message) {
            document.getElementById("error").innerHTML = message;
            document.getElementById("error").classList.remove("hidden");
        }

        var webauthn = new WebAuthn((name, message) => {
            error(errorMessage(name, message));
        });

        if (! webauthn.webAuthnSupport()) {
            switch (webauthn.notSupportedMessage()) {
                case 'not_secured':
                error(errors.not_secured);
                break;
                case 'not_supported':
                error(errors.not_supported);
                break;
            }
        }

        function registerDevice() {

            if(document.getElementById("name").value === '') {
                return error('A device name is required');
            }

            if(document.getElementById("name").value.length > 50) {
                return error('The device name may not be greater than 50 characters.');
            }

            webauthn.register(
                publicKey,
                function (data) {
                    document.getElementById("success").classList.remove("hidden");
                    document.getElementById("id").value = data.id;
                    document.getElementById("rawId").value = data.rawId;
                    document.getElementById("attestationObject").value = data.response.attestationObject;
                    document.getElementById("clientDataJSON").value = data.response.clientDataJSON;
                    document.getElementById("type").value = data.type;
                    document.getElementById("form").submit();
                }
            );
        }

    </script>
@endsection

@section('webauthn')
    <script src="{!! secure_asset('js/webauthn.js') !!}"></script>
@endsection