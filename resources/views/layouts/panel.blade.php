{{--
    Signed-in layout shared by everyone: top bar like the homepage, content below.
    Pages: @extends('layouts.panel'), then @section('title'), optional @section('subtitle'),
    @section('actions') (buttons beside the title) and @section('content').
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'DDC Workforce') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=arimo:400,600,700|lato:400,700|rubik:500" rel="stylesheet">
    @vite(['resources/css/panel.css', 'resources/js/panel.js'])
</head>
<body class="app">

@php($me = auth()->user())

<header class="bar" data-bar>
    <div class="bar-in">
        <a href="{{ route('home') }}" class="brand" aria-label="DDC Workforce home">
            <span class="mark">ddc</span>
            <span class="brand-app">Workforce</span>
        </a>

        <nav class="nav" data-nav>
            @php($placeKind = \Illuminate\Support\Str::before((string) request('place'), ':'))
            <a href="{{ route('directory') }}" @class(['is-on' => request()->routeIs('directory') && ! in_array($placeKind, ['hq', 'office', 'projects', 'project'])])>Staff directory</a>
            <a href="{{ route('directory', ['place' => 'hq']) }}" @class(['is-on' => request()->routeIs('directory') && in_array($placeKind, ['hq', 'office'])])>Head Office</a>
            <a href="{{ route('directory', ['place' => 'projects']) }}" @class(['is-on' => request()->routeIs('directory') && in_array($placeKind, ['projects', 'project'])])>Projects</a>
            @if ($me)
                <a href="{{ route('me') }}" @class(['is-on' => request()->routeIs('me') || (request()->routeIs('employees.show') && request()->route('employee')?->user_id === $me->id)])>My profile</a>
            @endif

            @can('manage-records')
                <div class="menu" data-menu>
                    <button type="button" aria-expanded="false" data-menu-btn @class(['menu-btn', 'is-on' => request()->routeIs('manage.*', 'hr.*', 'dashboard')])>
                        Manage <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="menu-list" role="menu">
                        <a href="{{ route('dashboard') }}" role="menuitem">Overview</a>
                        <a href="{{ route('manage.employees.create') }}" role="menuitem">Add employee</a>
                        <hr>
                        <a href="{{ route('manage.structure.index', 'divisions') }}" role="menuitem">Divisions</a>
                        <a href="{{ route('manage.structure.index', 'departments') }}" role="menuitem">Departments</a>
                        <a href="{{ route('manage.structure.index', 'offices') }}" role="menuitem">Head Office</a>
                        <a href="{{ route('manage.structure.index', 'projects') }}" role="menuitem">Projects</a>
                        <a href="{{ route('manage.structure.index', 'designations') }}" role="menuitem">Designations</a>
                        <hr>
                        <a href="{{ route('hr.users.index') }}" role="menuitem">Sign-in accounts</a>
                    </div>
                </div>
            @endcan
        </nav>

        @if ($me)
        <div class="menu me" data-menu>
            <button type="button" class="me-btn" aria-expanded="false" data-menu-btn aria-label="Account menu">
                <span class="me-avatar">{{ $me->initials() }}</span>
                <span class="me-text">
                    <strong>{{ $me->name }}</strong>
                    <small>{{ $me->role->shortLabel() }}</small>
                </span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="menu-list menu-right" role="menu">
                <a href="{{ route('me') }}" role="menuitem">My profile</a>
                <a href="{{ route('account.password') }}" role="menuitem">Change password</a>
                <hr>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" role="menuitem">Sign out</button>
                </form>
            </div>
        </div>
        @elseif (config('workforce.open_access'))
            <span class="open-badge" title="Sign-in and permissions are switched off while the rules are being decided. See config/workforce.php.">Open access</span>
        @endif

        <button class="burger" type="button" aria-label="Menu" aria-expanded="false" data-burger><span></span><span></span><span></span></button>
    </div>
</header>

<main class="page">
    <div class="wrap">
        <div class="page-head">
            <div>
                <h1>@yield('title')</h1>
                @hasSection('subtitle')
                    <p class="page-sub">@yield('subtitle')</p>
                @endif
            </div>
            @hasSection('actions')
                <div class="page-actions">@yield('actions')</div>
            @endif
        </div>

        @if (session('status'))
            <div class="notice notice-ok" role="status" data-notice>{{ session('status') }}</div>
        @endif
        @if ($errors->has('account'))
            <div class="notice notice-error" role="alert">{{ $errors->first('account') }}</div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="foot">
    <div class="wrap">© {{ date('Y') }} Development Design Consultants Limited</div>
</footer>

</body>
</html>
