@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-lg">
            <div class="flex flex-col break-words bg-white border border-2 rounded-lg shadow-lg overflow-hidden">

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
                        <img src="https://ssl.gstatic.com/accounts/strongauth/Challenge_2SV-Gnubby_graphic.png" alt=""/>
                    </p>

                    <p>
                        {{ trans('webauthn::messages.buttonAdvise') }}
                        <br />
                        {{ trans('webauthn::messages.noButtonAdvise') }}
                    </p>

                    <form method="POST" action="{{ route('webauthn.auth') }}" id="form">
                        @csrf
                        <input type="hidden" name="data" id="data" />
                    </form>

                </div>

                <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap justify-between">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <input type="submit" class="bg-transparent cursor-pointer no-underline" value="{{ __('Logout') }}">
                    </form>
                    <a href="{{ route('login.backup_code.index') }}">Use backup code</a>
                </div>
            </div>
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

        webauthn.sign(
            publicKey,
            function (datas) {
                document.getElementById("success").classList.remove("hidden");
                document.getElementById("data").value = JSON.stringify(datas);
                document.getElementById("form").submit();
            }
        );
    </script>
@endsection

@section('webauthn')
    <script src="{!! secure_asset('js/webauthn.js') !!}"></script>
@endsection