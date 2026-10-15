@extends('layouts.panel')

@php
    $placeName = $place['model']?->name;
    $title = $placeName ?? ($place['type'] ?? 'Staff directory');
@endphp

@section('title', $title)

@section('subtitle')
    @if ($place['type'] && $placeName)
        {{ $place['type'] }}@if ($place['type'] === 'Department' && $place['model']->division) in {{ $place['model']->division->name }}@endif
        @if ($place['type'] === 'Project' && $place['model']->location) · {{ $place['model']->location }}@endif
    @elseif ($place['type'] === 'All projects')
        Everyone currently posted to a project. Pick one project to see just that team.
    @elseif ($place['type'] === 'Head Office')
        Everyone currently posted at Head Office.
    @else
        Everyone at DDC. Choose a division, department, Head Office or project to narrow it down.
    @endif
@endsection

@section('actions')
    @can('manage-records')
        <a href="{{ route('manage.employees.create') }}" class="btn btn-brick">Add employee</a>
    @endcan
@endsection

@section('content')
    {{-- Where: tabs + which one --}}
    <form method="GET" action="{{ route('directory') }}" class="where" data-autosubmit>
        @php($current = $filters['place'])
        @php($kind = \Illuminate\Support\Str::before($current, ':') ?: 'all')

        <div class="where-tabs" role="tablist">
            <a href="{{ route('directory') }}" @class(['tab', 'is-on' => $kind === 'all'])>Everyone <span>{{ $counts['all'] }}</span></a>
            <a href="{{ route('directory', ['place' => 'hq']) }}" @class(['tab', 'is-on' => in_array($kind, ['hq', 'office'])])>Head Office <span>{{ $counts['hq'] }}</span></a>
            <a href="{{ route('directory', ['place' => 'projects']) }}" @class(['tab', 'is-on' => in_array($kind, ['projects', 'project'])])>Projects <span>{{ $counts['project'] }}</span></a>
            <button type="button" @class(['tab', 'is-on' => $kind === 'division']) data-pick="division">Division</button>
            <button type="button" @class(['tab', 'is-on' => $kind === 'department']) data-pick="department">Department</button>
        </div>

        <div class="where-pick">
            <label class="pick" data-pick-box="project" @if (! in_array($kind, ['projects', 'project'])) hidden @endif>
                <span>Project</span>
                <select name="place" @disabled(! in_array($kind, ['projects', 'project']))>
                    <option value="projects">All projects</option>
                    @foreach ($projects as $p)
                        <option value="project:{{ $p->id }}" @selected($current === "project:{$p->id}")>{{ $p->name }}@if ($p->status !== 'ongoing') ({{ strtolower($p->statusLabel()) }})@endif</option>
                    @endforeach
                </select>
            </label>
            <label class="pick" data-pick-box="division" @if ($kind !== 'division') hidden @endif>
                <span>Division</span>
                <select name="place" @disabled($kind !== 'division')>
                    <option value="">Choose a division…</option>
                    @foreach ($divisions as $d)
                        <option value="division:{{ $d->id }}" @selected($current === "division:{$d->id}")>{{ $d->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="pick" data-pick-box="department" @if ($kind !== 'department') hidden @endif>
                <span>Department</span>
                <select name="place" @disabled($kind !== 'department')>
                    <option value="">Choose a department…</option>
                    @foreach ($departments->groupBy(fn ($d) => $d->division?->name ?? 'Other') as $division => $group)
                        <optgroup label="{{ $division }}">
                            @foreach ($group as $d)
                                <option value="department:{{ $d->id }}" @selected($current === "department:{$d->id}")>{{ $d->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </label>
            @if (in_array($kind, ['hq', 'office']) && $offices->count() > 1)
                <label class="pick">
                    <span>Office</span>
                    <select name="place">
                        <option value="hq">All offices</option>
                        @foreach ($offices as $o)
                            <option value="office:{{ $o->id }}" @selected($current === "office:{$o->id}")>{{ $o->name }}</option>
                        @endforeach
                    </select>
                </label>
            @elseif (in_array($kind, ['hq', 'office']))
                <input type="hidden" name="place" value="{{ $current }}">
            @endif
        </div>

        {{-- Filters --}}
        <div class="filters">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search by name or employee ID" aria-label="Search by name or employee ID">
            <select name="designation" aria-label="Position">
                <option value="">Any position</option>
                @foreach ($designations as $des)
                    <option value="{{ $des->id }}" @selected($filters['designation'] === $des->id)>{{ $des->title }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Status">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
                <option value="all" @selected($filters['status'] === 'all')>Any status</option>
            </select>
            <select name="sort" aria-label="Sort">
                <option value="name" @selected($filters['sort'] === 'name')>Name A–Z</option>
                <option value="joined" @selected($filters['sort'] === 'joined')>Newest joiners</option>
                @if ($canUseSensitive)
                    <option value="salary" @selected($filters['sort'] === 'salary')>Highest salary</option>
                    <option value="age" @selected($filters['sort'] === 'age')>Oldest first</option>
                @endif
            </select>

            @if ($canUseSensitive)
                <details class="more" @if ($filters['age_min'] || $filters['age_max'] || $filters['salary_min'] || $filters['salary_max']) open @endif>
                    <summary>Age &amp; salary <span class="hr-only">HR only</span></summary>
                    <div class="more-grid">
                        <label><span>Age from</span><input type="number" name="age_min" min="16" max="80" value="{{ $filters['age_min'] }}"></label>
                        <label><span>to</span><input type="number" name="age_max" min="16" max="80" value="{{ $filters['age_max'] }}"></label>
                        <label><span>Gross salary from (৳)</span><input type="number" name="salary_min" min="0" step="1000" value="{{ $filters['salary_min'] }}"></label>
                        <label><span>to (৳)</span><input type="number" name="salary_max" min="0" step="1000" value="{{ $filters['salary_max'] }}"></label>
                    </div>
                </details>
            @endif

            <button type="submit" class="btn btn-ghost">Apply</button>
            @if (request()->hasAny(['q', 'designation', 'sort', 'age_min', 'age_max', 'salary_min', 'salary_max']) || request('status', 'active') !== 'active')
                <a href="{{ route('directory', array_filter(['place' => $filters['place']])) }}" class="link">Clear filters</a>
            @endif
        </div>
    </form>

    <p class="result-count">
        {{ $employees->total() }} {{ \Illuminate\Support\Str::plural('person', $employees->total()) }}
    </p>

    @if ($employees->isEmpty())
        <div class="empty-state">
            <p><strong>No one matches.</strong></p>
            <p class="muted">Try another place or clear the filters.</p>
            @can('manage-records')
                <a href="{{ route('manage.employees.create') }}" class="btn btn-brick">Add employee</a>
            @endcan
        </div>
    @else
        <ul class="cards">
            @foreach ($employees as $e)
                @php($post = $e->currentPosting)
                <li>
                    <a href="{{ route('employees.show', $e) }}" class="person-card">
                        <span class="pc-photo" data-tone="{{ $e->id % 6 }}">
                            @if ($e->photo_path)
                                <img src="{{ route('employees.photo', $e) }}" alt="" loading="lazy">
                            @else
                                <span class="pc-initials">{{ $e->initials() }}</span>
                            @endif
                            @if ($e->status !== 'active')
                                <span class="pc-status">{{ $e->statusLabel() }}</span>
                            @endif
                        </span>
                        <span class="pc-body">
                            <strong class="pc-name">{{ $e->name }}</strong>
                            <span class="pc-code">{{ $e->employee_code }}</span>
                            <span class="pc-role">{{ $e->designation?->title ?? 'No designation' }}</span>
                            <span class="pc-dept">{{ $e->department?->name }}@if ($e->department?->division) · {{ $e->department->division->name }}@endif</span>
                        </span>
                        <span class="pc-foot">
                            @if ($post)
                                <span @class(['tag', 'tag-hq' => $post->isHq(), 'tag-proj' => ! $post->isHq()])>{{ $post->isHq() ? 'HQ' : 'Project' }}</span>
                                <span class="pc-place" title="{{ $post->placeName() }}">{{ $post->placeName() }}</span>
                            @else
                                <span class="pc-place muted">No posting</span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        {{ $employees->links('partials.pagination') }}
    @endif
@endsection
