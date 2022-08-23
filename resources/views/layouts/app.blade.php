<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#19216C">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-grey-50 antialiased text-grey-900">
    <div id="app" v-cloak>

        @include('nav.nav')

        @yield('content')

        <notifications position="bottom right" />
    </div>

    @if(App\Helpers\GitVersionHelper::version())
        <footer>
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 lg:max-w-7xl">
            <div class="border-t border-grey-200 py-4 text-sm text-grey-500 text-center"><a href="https://github.com/anonaddy/anonaddy/releases/tag/v{{ App\Helpers\GitVersionHelper::version() }}" target="_blank" rel="nofollow noreferrer noopener" class="block sm:inline">v{{ App\Helpers\GitVersionHelper::version() }}</a></div>
        </div>
        </footer>
    @endif

    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>