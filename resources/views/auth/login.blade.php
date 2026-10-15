<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name', 'DDC Workforce') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=arimo:400,600,700|lato:400,700|rubik:500" rel="stylesheet">
    @vite(['resources/css/panel.css', 'resources/js/panel.js'])
</head>
<body class="auth">

<div class="auth-art" aria-hidden="true">
    <a href="{{ route('home') }}" class="auth-brand">
        <span class="mark mark-lg">ddc</span>
        <span>Development<br>Design<br>Consultants<br>Limited</span>
    </a>
    <svg viewBox="0 0 600 400" preserveAspectRatio="xMidYMax slice" class="auth-lines">
        <g fill="none">
            <path d="M40 400V180h160v220M200 400V90h220v310M420 400V150h140v250"/>
            @for ($y = 120; $y < 400; $y += 32)
                <path d="M215 {{ $y }}h190"/>
            @endfor
            @for ($x = 235; $x < 410; $x += 34)
                <path d="M{{ $x }} 90V400"/>
            @endfor
            @for ($y = 210; $y < 400; $y += 38)
                <path d="M55 {{ $y }}h130M435 {{ $y - 20 }}h110"/>
            @endfor
        </g>
    </svg>
    <p class="auth-tag">DDC Workforce<br><span>Employee management</span></p>
</div>

<main class="auth-main">
    <form method="POST" action="{{ route('login.store') }}" class="auth-card" novalidate>
        @csrf
        <h1>Sign in</h1>
        <p class="auth-sub">Use the email and password your HR team gave you.</p>

        @if (session('status'))
            <div class="notice notice-ok">{{ session('status') }}</div>
        @endif

        <label class="field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </label>
        @error('email')
            <p class="field-error" id="email-error">{{ $message }}</p>
        @enderror

        <label class="field">
            <span>Password</span>
            <span class="pw">
                <input type="password" name="password" autocomplete="current-password" required data-pw
                       @error('password') aria-invalid="true" @enderror>
                <button type="button" class="pw-toggle" data-pw-toggle aria-label="Show password">Show</button>
            </span>
        </label>
        @error('password')
            <p class="field-error">{{ $message }}</p>
        @enderror

        <label class="check">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Keep me signed in on this computer</span>
        </label>

        <button type="submit" class="btn btn-brick btn-block">Sign in</button>

        <p class="auth-help">Forgot your password? Ask HR to reset it.</p>
        <p class="auth-help"><a href="{{ route('home') }}" class="auth-back">← Back to homepage</a></p>
    </form>
</main>

</body>
</html>
