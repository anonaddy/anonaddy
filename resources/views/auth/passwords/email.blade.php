@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-md">
            <div class="flex justify-center text-white mb-6 text-5xl font-bold">
                <img class="w-48" alt="addy.io Logo" src="/svg/logo.svg">
            </div>
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="px-6 py-8 md:p-10">

                        <h1 class="text-center font-bold text-3xl">
                            {{ __('Reset Password') }}
                        </h1>

                        <div class="mx-auto mt-6 w-24 border-b-2 border-grey-200"></div>

                        @if (session('status'))
                            <div class="text-sm border-t-8 rounded text-green-700 border-green-600 bg-green-100 px-3 py-4 mt-4" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="mt-8 mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <label for="username" class="block text-grey-700 text-sm font-medium leading-6">
                                    {{ __('Username') }}
                                </label>
                                <div class="text-sm">
                                    <a class="whitespace-nowrap no-underline font-medium text-indigo-600 hover:text-indigo-500" tabindex="-1" href="{{ route('username.reminder.show') }}">
                                        {{ __('Forgot Username?') }}
                                    </a>
                                </div>
                            </div>

                            <input id="username" type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('username') ? ' border-red-500' : '' }}" name="username" value="{{ old('username') }}" placeholder="johndoe" required>

                            <p class="text-xs mt-1 text-grey-600">Note: your username is <b>not</b> your email address.</p>

                            @if ($errors->has('username'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('username') }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap mb-4 items-center">
                            <label for="captcha" class="block w-full text-grey-700 text-sm">
                                Human Verification (click image to refresh)
                            </label>

                            <div class="flex grow flex-wrap">
                                <img src="{{captcha_src('mini')}}" onclick="this.src='/captcha/mini?'+Math.random()" class="cursor-pointer shrink-0 h-12 w-16 mr-2 mt-2" title="Click to refresh image" alt="captcha">

                                <input id="captcha" type="text" class="grow mt-2 appearance-none bg-grey-100 rounded p-3 text-grey-700 focus:ring{{ $errors->has('captcha') ? ' border-red-500' : '' }}" name="captcha" placeholder="Enter the text you see" required>
                            </div>

                            @if ($errors->has('captcha'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('captcha') }}
                                </p>
                            @endif
                        </div>

                    </div>

                    <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap items-center justify-center">
                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none">
                            {{ __('Send Password Reset Link') }}
                        </button>
                    </div>
                </form>
            </div>
                <p class="w-full text-xs text-center mt-6">
                    <a class="text-white hover:text-indigo-50 no-underline" href="{{ route('login') }}">
                        Back to login
                    </a>
                </p>
        </div>
    </div>
@endsection