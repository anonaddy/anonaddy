@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-md">
            <div class="flex justify-center text-white mb-6 text-5xl font-bold">
                <img class="w-48" alt="AnonAddy Logo" src="/svg/logo.svg">
            </div>
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">
                <form method="POST" action="{{ route('login.backup_code.login') }}">
                    @csrf

                    <div class="px-6 py-8 md:p-10">

                        <h1 class="text-center font-bold text-3xl">
                            Login Using 2FA Backup Code
                        </h1>

                        <div class="mx-auto mt-6 w-24 border-b-2 border-grey-200"></div>

                        <div class="text-sm border-t-8 rounded text-yellow-800 border-yellow-600 bg-yellow-100 px-3 py-4 mt-4" role="alert">
                            After logging in using your backup code, two factor authentication will be disabled on your account. If you would like to use 2FA, you should re-enable it after logging in.
                        </div>

                        <div class="mt-8 flex flex-wrap">
                            <label for="backup_code" class="block text-grey-700 text-sm mb-2">
                                Backup Code:
                            </label>

                            <input id="backup_code" type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('backup_code') ? ' border border-red-500' : '' }}" name="backup_code" required autofocus>

                            @if ($errors->has('backup_code'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('backup_code') }}
                                </p>
                            @endif
                        </div>

                    </div>

                    <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap items-center justify-center">
                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                            {{ __('Authenticate') }}
                        </button>
                    </div>
                </form>
            </div>
                <form action="{{ route('logout') }}" method="POST" class="w-full text-xs text-center mt-6">
                    {{ csrf_field() }}
                    <input type="submit" class="bg-transparent cursor-pointer text-white hover:text-indigo-50 no-underline" value="{{ __('Logout') }}">
                </form>
        </div>
    </div>
@endsection