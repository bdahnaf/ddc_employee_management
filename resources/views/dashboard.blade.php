@extends('layouts.panel')

@section('title', 'Overview')
@section('subtitle', 'Where DDC staff are posted today, and what changed recently.')

@section('actions')
    <a href="{{ route('manage.employees.create') }}" class="btn btn-brick">Add employee</a>
@endsection

@section('content')
    <div class="stats">
        <a class="stat" href="{{ route('directory') }}"><span>Active staff</span><strong>{{ $counts['employees'] }}</strong></a>
        <a class="stat" href="{{ route('directory', ['place' => 'hq']) }}"><span>At Head Office</span><strong>{{ $counts['hq'] }}</strong></a>
        <a class="stat" href="{{ route('directory', ['place' => 'projects']) }}"><span>On projects</span><strong>{{ $counts['project'] }}</strong></a>
        <a class="stat" href="{{ route('manage.structure.index', 'projects') }}"><span>Ongoing projects</span><strong>{{ $counts['projects'] }}</strong></a>
    </div>

    <div class="grid-2 top">
        <section class="card">
            <header class="card-head"><h2 class="card-title">Recent postings</h2></header>
            @forelse ($recentTransfers as $p)
                <div class="post-row">
                    <span @class(['tag', 'tag-hq' => $p->isHq(), 'tag-proj' => ! $p->isHq()])>{{ $p->isHq() ? 'HQ' : 'Project' }}</span>
                    <div class="post-main">
                        @if ($p->employee)
                            <a href="{{ route('employees.show', $p->employee) }}" class="strong-link">{{ $p->employee->name }}</a>
                        @endif
                        <small>{{ $p->placeName() }} from {{ $p->start_date->format('d M Y') }}</small>
                    </div>
                </div>
            @empty
                <p class="empty">No postings yet.</p>
            @endforelse
        </section>

        <section class="card">
            <header class="card-head"><h2 class="card-title">Recent activity</h2></header>
            @forelse ($activity as $log)
                <div class="log-row">
                    <span class="log-dot log-{{ \Illuminate\Support\Str::before($log->action, '.') }}"></span>
                    <span class="log-text"><strong>{{ $log->user_name ?? 'System' }}</strong> {{ lcfirst($log->description) }}</span>
                    <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('d M Y, h:i A') }}">{{ $log->created_at->diffForHumans() }}</time>
                </div>
            @empty
                <p class="empty">Nothing yet.</p>
            @endforelse
        </section>
    </div>

    <section class="card">
        <header class="card-head"><h2 class="card-title">Set-up</h2></header>
        <div class="setup">
            <a href="{{ route('manage.structure.index', 'divisions') }}"><strong>{{ $counts['divisions'] }}</strong> divisions</a>
            <a href="{{ route('manage.structure.index', 'departments') }}"><strong>{{ $counts['departments'] }}</strong> departments</a>
            <a href="{{ route('hr.users.index') }}"><strong>{{ $counts['accounts'] }}</strong> active sign-in accounts</a>
            <a href="{{ route('hr.users.index', ['role' => 'head']) }}"><strong>{{ $counts['heads'] }}</strong> heads</a>
        </div>
    </section>
@endsection
