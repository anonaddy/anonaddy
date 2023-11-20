@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-md">

            <div class="flex justify-center text-white mb-6 text-5xl font-bold">
                <img class="w-48" alt="addy.io Logo" src="/svg/logo.svg">
            </div>
            <div class="flex flex-col break-words bg-white border-2 rounded-lg shadow-lg overflow-hidden">
                <form class="" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="px-6 py-8 md:p-10">

                        <h1 class="text-center font-bold text-3xl">
                            Welcome Back!
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

                            <input id="username" type="text" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('username') ? ' border-red-500' : '' }}" name="username" value="{{ old('username') }}" placeholder="johndoe" required autofocus>

                            <p class="text-xs mt-1 text-grey-600">Note: your username is <b>not</b> your email address.</p>

                            @if ($errors->has('username'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('username') }}
                                </p>
                            @endif
                            @if ($errors->has('id'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('id') }}
                                </p>
                            @endif
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <label for="password" class="block text-grey-700 text-sm font-medium leading-6">
                                    {{ __('Password') }}
                                </label>
                                <div class="text-sm">
                                    <a class="whitespace-nowrap no-underline font-medium text-indigo-600 hover:text-indigo-500" tabindex="-1" href="{{ route('password.request') }}">
                                        {{ __('Forgot Password?') }}
                                    </a>
                                </div>
                            </div>

                            <input id="password" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('password') ? ' border-red-500' : '' }}" name="password" placeholder="********" required>

                            @if ($errors->has('password'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('password') }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap justify-between items-center">
                            <div class="mr-5 mt-4">
                                <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" {{ old('remember') ? 'checked' : '' }}>
                                <label class="text-sm text-grey-700 ml-2" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap items-center">
                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            {{ __('Login') }}
                        </button>
                    </div>
                </form>
            </div>
            @if (Route::has('register'))
                <p class="w-full text-xs text-center text-indigo-100 mt-6">
                    Don't have an account?
                    <a class="text-white hover:text-indigo-50 no-underline" href="{{ route('register') }}">
                        Register
                    </a>
                </p>
            @endif
        </div>
    </div>
@endsection
