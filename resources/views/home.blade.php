<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Employee management for Development Design Consultants Limited: divisions, departments, HQ and project postings, attendance, leave and payroll.">
    <title>{{ config('app.name', 'DDC Workforce') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=arimo:400,600,700|lato:300,400,700|rubik:500" rel="stylesheet">

    @vite(['resources/css/home.css', 'resources/js/home.js'])
</head>
<body>

@php
    // Signed-in visitors go straight into the app; guests are sent to sign in first
    // (protected pages redirect to /login and come back afterwards).
    // While open access is on (config/workforce.php) nobody needs to sign in.
    $openAccess = config('workforce.open_access');
    $signedIn = auth()->check() || $openAccess;
    $signIn = $signedIn ? route('directory') : route('login');
    $sectors = [
        ['Building & Planning', 'M4 21V9l8-5 8 5v12M9 21v-6h6v6M4 21h16'],
        ['Roads & Bridges', 'M2 18h20M4 18c2-6 6-9 8-9s6 3 8 9M8 18v-4M16 18v-4M12 18V9'],
        ['ICT', 'M4 5h16v11H4zM9 20h6M12 16v4'],
        ['Port & Railway', 'M6 20l2-4h8l2 4M7 16V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v10M7 11h10'],
        ['Energy & Power', 'M13 2L5 14h6l-1 8 8-12h-6z'],
        ['Rural Development', 'M3 20h18M6 20v-6l4-3 4 3v6M16 20v-9l3-2 2 2v9'],
        ['Water Resource & Environment', 'M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11z'],
    ];
@endphp

{{-- ================= Header ================= --}}
<header class="header" data-header>
    <div class="wrap header-row">
        <a href="{{ url('/') }}" class="brand" aria-label="DDC Workforce home">
            <span class="brand-mark">ddc</span>
            <span class="brand-name">Development<br>Design<br>Consultants<br>Limited</span>
            <span class="brand-divider" aria-hidden="true"></span>
            <span class="brand-app">Workforce</span>
        </a>

        <nav class="menu" data-menu>
            <a href="{{ route('directory') }}">Staff directory</a>
            <a href="{{ route('directory', ['place' => 'projects']) }}">Projects</a>
            <a href="#structure">How it works</a>
            <a href="#sectors">Sectors</a>
        </nav>

        <div class="header-end">
            <a href="{{ $signIn }}" class="pill pill-brick pill-sm">{{ $signedIn ? 'Open Workforce' : 'Sign in' }}</a>
            <button class="burger" type="button" aria-label="Menu" aria-expanded="false" data-burger><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<main>

{{-- ================= Hero ================= --}}
<section class="hero">
    {{-- building elevation line drawing --}}
    <svg class="hero-art" viewBox="0 0 900 420" preserveAspectRatio="xMaxYMax slice" aria-hidden="true">
        <g class="hero-lines" fill="none">
            <path d="M60 420V170h220v250"/>
            <path d="M280 420V90h300v330"/>
            <path d="M580 420V140h200v280"/>
            <path d="M780 420V230h120"/>
            @for ($y = 120; $y < 420; $y += 34)
                <path d="M300 {{ $y }}h260"/>
            @endfor
            @for ($x = 320; $x < 560; $x += 40)
                <path d="M{{ $x }} 90V420"/>
            @endfor
            @for ($y = 200; $y < 420; $y += 40)
                <path d="M80 {{ $y }}h180"/>
                <path d="M600 {{ $y - 30 }}h160"/>
            @endfor
            <path d="M0 420h900"/>
        </g>
    </svg>

    <div class="wrap hero-row">
        <div class="hero-copy">
            <p class="hero-kicker reveal">DDC Workforce</p>
            <h1 class="reveal" style="--d:.08s">Employee management for Development Design Consultants</h1>
            <p class="hero-text reveal" style="--d:.16s">
                Keep every staff record in one place: which division and department each
                person belongs to, whether they are at Head Office or on a project, and their
                attendance, leave and salary.
            </p>
            <div class="hero-actions reveal" style="--d:.24s">
                <a href="{{ route('directory') }}" class="pill pill-brick">Open staff directory</a>
                @if (! $signedIn)
                    <a href="{{ route('login') }}" class="pill pill-outline">Sign in</a>
                @elseif (auth()->check())
                    <a href="{{ route('me') }}" class="pill pill-outline">My profile</a>
                @else
                    <a href="{{ route('directory', ['place' => 'projects']) }}" class="pill pill-outline">See projects</a>
                @endif
            </div>
        </div>

        <div class="hero-card reveal" style="--d:.3s" aria-hidden="true">
            <div class="profile">
                <div class="profile-head">
                    <span class="avatar"></span>
                    <div>
                        <strong>Employee name</strong>
                        <small>Designation</small>
                    </div>
                    <span class="badge" data-badge>Project</span>
                </div>
                <dl class="profile-rows">
                    <div><dt>Division</dt><dd><span class="line w-60"></span></dd></div>
                    <div><dt>Department</dt><dd><span class="line w-50"></span></dd></div>
                    <div><dt>Posted at</dt><dd data-place>Project site</dd></div>
                </dl>
                <div class="profile-week">
                    <span class="week-label">This week</span>
                    <div class="week">
                        @foreach (['Sat' => 'p', 'Sun' => 'p', 'Mon' => 'l', 'Tue' => 'p', 'Wed' => 'p'] as $day => $s)
                            <span class="wd"><i class="st st-{{ $s }}"></i>{{ $day }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= Quick access band (links into the app) ================= --}}
<section class="band-wrap" aria-label="Quick access">
    <div class="wrap">
        <ul class="band reveal">
            <li>
                <a href="{{ route('directory') }}">
                    <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/></svg>
                    <strong>Staff directory</strong>
                    <span>Everyone, by division or department</span>
                </a>
            </li>
            <li>
                <a href="{{ route('directory', ['place' => 'hq']) }}">
                    <svg viewBox="0 0 24 24"><path d="M4 21V9l8-5 8 5v12M4 21h16M9 21v-6h6v6"/></svg>
                    <strong>Head Office</strong>
                    <span>Staff posted at HQ</span>
                </a>
            </li>
            <li>
                <a href="{{ route('directory', ['place' => 'projects']) }}">
                    <svg viewBox="0 0 24 24"><path d="M2 18h20M4 18c2-6 6-9 8-9s6 3 8 9M12 18V9"/></svg>
                    <strong>Projects</strong>
                    <span>Who is working on which project</span>
                </a>
            </li>
            <li>
                @auth
                    <a href="{{ route('me') }}">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <strong>My profile</strong>
                        <span>Your details, posting and salary</span>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}">
                        <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-4H4zM14 7h6V4h-6z"/></svg>
                        <strong>Overview</strong>
                        <span>Headcount, postings and recent changes</span>
                    </a>
                @endauth
            </li>
        </ul>
    </div>
</section>

{{-- ================= Structure ================= --}}
<section id="structure" class="section">
    <div class="wrap">
        <header class="title reveal">
            <h2>How the organisation is structured</h2>
            <p>Every employee is filed under a division and a department, and posted either at Head Office or on a project.</p>
        </header>

        <ol class="steps" data-steps>
            <li class="reveal" style="--d:0s">
                <span class="step-dot"></span>
                <h3>Division</h3>
                <p>The firm's main business units, set up by an admin.</p>
            </li>
            <li class="reveal" style="--d:.1s">
                <span class="step-dot"></span>
                <h3>Department</h3>
                <p>Teams inside each division that people report to.</p>
            </li>
            <li class="reveal" style="--d:.2s">
                <span class="step-dot"></span>
                <h3>Employee</h3>
                <p>Personal details, designation, salary and documents.</p>
            </li>
            <li class="reveal" style="--d:.3s">
                <span class="step-dot step-dot-split"></span>
                <h3>Head Office or Project</h3>
                <p>Where the person works now, with every past posting kept.</p>
            </li>
        </ol>
    </div>
</section>

{{-- ================= Features ================= --}}
<section id="features" class="section section-soft">
    <div class="wrap">
        <header class="title reveal">
            <h2>What you can manage</h2>
            <p>Admins keep the records up to date. Everyone else sees the information they need.</p>
        </header>

        {{-- Postings --}}
        <div class="feature reveal">
            <div class="feature-copy">
                <h4>Postings &amp; transfers</h4>
                <h3>Know where each person is posted</h3>
                <p>When someone moves from Head Office to a project, or back, the admin records a transfer. The old posting is closed with its end date and the new one starts, so the full history stays on the profile.</p>
            </div>
            <div class="panel" aria-hidden="true">
                <ol class="history">
                    <li><span class="tag tag-hq">HQ</span><span>Head Office</span><span class="line w-30"></span></li>
                    <li><span class="tag tag-proj">Project</span><span>Project site</span><span class="line w-30"></span></li>
                    <li class="is-now"><span class="tag tag-hq">HQ</span><span>Head Office</span><em>Current</em></li>
                </ol>
            </div>
        </div>

        {{-- Attendance + payroll --}}
        <div class="feature feature-flip reveal">
            <div class="feature-copy">
                <h4>Attendance, leave &amp; salary</h4>
                <h3>From daily attendance to the monthly salary</h3>
                <p>Mark each day as present, late, absent or on leave. At the end of the month the salary sheet uses those days together with each person's pay structure.</p>
            </div>
            <div class="panel panel-split" aria-hidden="true">
                @php $month = str_split('ppplpwwppppawwpplppwwvvpppwwppp'); @endphp
                <div>
                    <div class="month" data-month>
                        @foreach ($month as $d => $s)
                            <i class="st st-{{ $s }}" style="--d:{{ $d }}"></i>
                        @endforeach
                    </div>
                    <ul class="key">
                        <li><i class="st st-p"></i>Present</li>
                        <li><i class="st st-l"></i>Late</li>
                        <li><i class="st st-a"></i>Absent</li>
                        <li><i class="st st-v"></i>Leave</li>
                    </ul>
                </div>
                <div class="slip">
                    <div><span>Gross salary</span><span class="line w-40"></span></div>
                    <div><span>Absent days</span><span class="line w-20"></span></div>
                    <div><span>Late days</span><span class="line w-20"></span></div>
                    <div class="slip-net"><span>Net salary</span><span class="line line-dark w-40"></span></div>
                </div>
            </div>
        </div>

        {{-- Admin --}}
        <div class="feature reveal">
            <div class="feature-copy">
                <h4>Admin control</h4>
                <h3>Changes are made by admins and logged</h3>
                <p>Only admins add divisions, departments, projects and postings. Each change is saved with who made it and when.</p>
            </div>
            <div class="panel" aria-hidden="true">
                <ul class="activity">
                    <li><i class="st st-a"></i>Posting added<span class="line w-25"></span></li>
                    <li><i class="st st-p"></i>Project created<span class="line w-30"></span></li>
                    <li><i class="st st-ink"></i>Division updated<span class="line w-20"></span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ================= Sectors (from ddclbd.com) ================= --}}
<section id="sectors" class="section">
    <div class="wrap">
        <header class="title reveal">
            <h2>Our Sectors</h2>
            <p>One system for staff working across all of DDC's sectors.</p>
        </header>
        <ul class="sectors">
            @foreach ($sectors as $i => [$name, $icon])
                <li class="reveal" style="--d:{{ $i * .05 }}s">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    <span>{{ $name }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ================= Call to action ================= --}}
<section class="cta">
    <div class="wrap cta-row reveal">
        <div>
            <h2>{{ $signedIn ? 'Open DDC Workforce' : 'Sign in to DDC Workforce' }}</h2>
            <p>{{ $signedIn ? 'Browse the staff directory, projects and Head Office.' : 'Use the account provided by your admin.' }}</p>
        </div>
        <a href="{{ $signIn }}" class="pill pill-white">{{ $signedIn ? 'Open Workforce' : 'Sign in' }}</a>
    </div>
</section>

</main>

<footer class="footer">
    <div class="wrap footer-row">
        <a href="{{ url('/') }}" class="brand brand-small">
            <span class="brand-mark">ddc</span>
            <span class="brand-app">Workforce</span>
        </a>
        <p>&copy; {{ date('Y') }} Development Design Consultants Limited. Internal staff system.</p>
    </div>
</footer>

</body>
</html>
