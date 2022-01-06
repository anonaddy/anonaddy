@auth
<nav class="bg-indigo-900 py-4 shadow">
    <div class="container flex items-center justify-between flex-wrap">
        <div class="flex items-center shrink-0 text-white mr-6">
            <a href="{{ route('aliases.index') }}">
                <img class="h-6" alt="AnonAddy Logo" src="/svg/icon-logo.svg">
            </a>
        </div>
        <div class="block md:hidden">
            <button @click="mobileNavActive = !mobileNavActive" class="flex items-center px-3 py-2 border rounded text-indigo-200 border-indigo-400 hover:text-white hover:border-white focus:outline-none">
            <svg class="fill-current h-3 w-3" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><title>Menu</title><path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/></svg>
            </button>
        </div>
        <div class="w-full grow md:flex md:items-center md:w-auto" :class="mobileNavActive ? 'block' : 'hidden'">
            <div class="text-base md:grow">
                <a href="{{ route('aliases.index') }}" class="block mt-4 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('aliases.index') ? 'text-white' : 'text-indigo-100' }}">
                    Aliases
                </a>
                <a href="{{ route('recipients.index') }}" class="block mt-4 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('recipients.index') ? 'text-white' : 'text-indigo-100' }}">
                    Recipients
                </a>
                <a href="{{ route('domains.index') }}" class="block mt-4 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('domains.index') ? 'text-white' : 'text-indigo-100' }}">
                    Domains
                </a>
                <a href="{{ route('usernames.index') }}" class="block mt-4 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('usernames.index') ? 'text-white' : 'text-indigo-100' }}">
                    Usernames
                </a>
                <a href="{{ route('failed_deliveries.index') }}" class="block mt-3 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('failed_deliveries.index') ? 'text-white' : 'text-indigo-100' }}">
                    Failed Deliveries
                </a>
                <a href="{{ route('rules.index') }}" class="block mt-4 md:inline-block md:mt-0 hover:text-white mr-4 {{ Route::currentRouteNamed('rules.index') ? 'text-white' : 'text-indigo-100' }}">
                    Rules
                </a>

                <a href="{{ route('settings.show') }}" class="block md:hidden mt-4 hover:text-white mr-4 {{ Route::currentRouteNamed('settings.show') ? 'text-white' : 'text-indigo-100' }}">
                    Settings
                </a>
                <form action="{{ route('logout') }}" method="POST" class="block md:hidden">
                    {{ csrf_field() }}
                    <input type="submit" class="bg-transparent block text-indigo-100 mt-4 hover:text-white mr-4" value="{{ __('Logout') }}">
                </form>
            </div>
            <dropdown class="hidden md:block" username="{{ user()->username }}">
                <ul>
                    <li>
                        <a href="{{ route('settings.show') }}" class="block px-4 py-2 hover:bg-indigo-500 hover:text-white">Settings</a>
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="block">
                            {{ csrf_field() }}
                            <input type="submit" class="w-full px-4 py-2 bg-transparent hover:bg-indigo-500 hover:text-white cursor-pointer text-left" value="{{ __('Logout') }}">
                        </form>
                    </li>
                </ul>
            </dropdown>
        </div>
    </div>
</nav>
@endauth