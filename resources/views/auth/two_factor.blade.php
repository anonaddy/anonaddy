@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-md">
            <div class="flex justify-center text-white mb-6 text-5xl font-bold">
                <img class="w-48" alt="AnonAddy Logo" src="/svg/logo.svg">
            </div>
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">
                <form method="POST" action="{{ route('login.2fa') }}">
                    @csrf

                    <div class="px-6 py-8 md:p-10">

                        <h1 class="text-center font-bold text-3xl">
                            {{ __('2nd Factor Authentication') }}
                        </h1>

                        <div class="mx-auto mt-6 w-24 border-b-2 border-grey-200"></div>

                        @if (session('status'))
                            <div class="text-sm border-t-8 rounded text-green-700 border-green-600 bg-green-100 px-3 py-4 mt-4" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="mt-8 flex flex-wrap">
                            <label for="one_time_password" class="block text-grey-700 text-sm mb-2">
                                {{ __('One Time Token') }}:
                            </label>

                            <input id="one_time_password" type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('message') ? ' border border-red-500' : '' }}" name="one_time_password" placeholder="123456" required autofocus>

                            @if ($errors->has('message'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('message') }}
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