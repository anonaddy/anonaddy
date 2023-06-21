@extends('layouts.auth')

@section('content')
    <div class="p-6 bg-indigo-900 min-h-screen flex justify-center items-center">
        <div class="w-full max-w-md">

            <div class="flex justify-center text-white mb-6 text-5xl font-bold">
                <a href="https://anonaddy.com" aria-label="Go to Anonaddy homepage">
                    <img class="w-48" alt="AnonAddy Logo" src="/svg/logo.svg">
                </a>
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

                        <div class="mt-8 flex flex-wrap mb-6">
                            <label for="username" class="block text-grey-700 text-sm mb-2">
                                {{ __('Username') }}:
                            </label>

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

                        <div class="flex flex-wrap mb-2">
                            <label for="password" class="block text-grey-700 text-sm mb-2">
                                {{ __('Password') }}:
                            </label>

                            <input id="password" type="password" class="appearance-none bg-grey-100 rounded w-full p-3 text-grey-700 focus:ring{{ $errors->has('password') ? ' border-red-500' : '' }}" name="password" placeholder="********" required>

                            @if ($errors->has('password'))
                                <p class="text-red-500 text-xs italic mt-4">
                                    {{ $errors->first('password') }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap justify-between items-center">
                            <div class="mr-5 mt-4">
                                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="text-sm text-grey-700 ml-3" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                            </div>
                            @if (Route::has('password.request'))
                                <a class="whitespace-nowrap no-underline text-sm mt-4" href="{{ route('password.request') }}">
                                    {{ __('Forgot Username/Password?') }}
                                </a>
                            @endif
                        </div>

                    </div>

                    <div class="px-6 md:px-10 py-4 bg-grey-50 border-t border-grey-100 flex flex-wrap items-center">
                        <button type="submit" class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none ml-auto">
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
