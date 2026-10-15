@extends('layouts.panel')

@php
    $post = $employee->currentPosting;
    $dash = '—';
    $val = fn ($v) => filled($v) ? $v : $dash;
@endphp

@section('title', $employee->name)

@section('actions')
    @can('manage-records')
        <button type="button" class="btn btn-ghost" data-open="transfer">Transfer</button>
        <a href="{{ route('manage.employees.edit', $employee) }}" class="btn btn-brick">Edit</a>
    @endcan
@endsection

@section('content')
    <a href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), '/directory') ? url()->previous() : route('directory') }}" class="back">← Back to directory</a>

    {{-- ===== Header ===== --}}
    <section class="profile-head">
        <div class="ph-photo pc-photo" data-tone="{{ $employee->id % 6 }}">
            @if ($employee->photo_path)
                <img src="{{ route('employees.photo', $employee) }}" alt="Photo of {{ $employee->name }}">
            @else
                <span class="pc-initials">{{ $employee->initials() }}</span>
            @endif
        </div>
        <div class="ph-main">
            <p class="ph-role">{{ $employee->designation?->title ?? 'No designation' }}</p>
            <p class="ph-org">
                {{ $employee->department?->name ?? 'No department' }}
                @if ($employee->department?->division) · {{ $employee->department->division->name }} @endif
            </p>
            <div class="ph-tags">
                @if ($post)
                    <span @class(['tag', 'tag-hq' => $post->isHq(), 'tag-proj' => ! $post->isHq()])>{{ $post->isHq() ? 'HQ' : 'Project' }}</span>
                    <span>{{ $post->placeName() }} since {{ $post->start_date->format('d M Y') }}</span>
                @else
                    <span class="muted">No current posting</span>
                @endif
            </div>
        </div>
        <dl class="ph-facts">
            <div><dt>Employee ID</dt><dd>{{ $employee->employee_code }}</dd></div>
            <div><dt>Status</dt><dd><span class="state state-{{ $employee->status === 'active' ? 'on' : 'off' }}">{{ $employee->statusLabel() }}</span></dd></div>
            <div><dt>Service</dt><dd>{{ $employee->serviceLength() ?? $dash }}</dd></div>
        </dl>
    </section>

    @if ($isSelf)
        <p class="notice notice-info">This is your profile. You can see everything on it, including private details. Ask HR if something is wrong.</p>
    @endif

    <nav class="sec-nav" aria-label="Sections">
        <a href="#job">Job</a>
        <a href="#postings">Postings</a>
        <a href="#personal">Personal</a>
        <a href="#salary">Salary</a>
    </nav>

    <div class="profile-grid">
        {{-- ===== Job (everyone) ===== --}}
        <section class="card" id="job">
            <h2 class="card-title">Job</h2>
            <dl class="facts">
                <div><dt>Designation</dt><dd>{{ $val($employee->designation?->title) }}</dd></div>
                <div><dt>Grade</dt><dd>{{ $val($employee->designation?->grade) }}</dd></div>
                <div><dt>Department</dt><dd>{{ $val($employee->department?->name) }}</dd></div>
                <div><dt>Division</dt><dd>{{ $val($employee->department?->division?->name) }}</dd></div>
                <div><dt>Employment type</dt><dd>{{ \App\Models\Employee::EMPLOYMENT_TYPES[$employee->employment_type] ?? $dash }}</dd></div>
                <div><dt>Joined</dt><dd>{{ $employee->joining_date?->format('d M Y') ?? $dash }}</dd></div>
                <div><dt>Office email</dt><dd>@if ($employee->office_email)<a href="mailto:{{ $employee->office_email }}" class="link">{{ $employee->office_email }}</a>@else {{ $dash }} @endif</dd></div>
                <div><dt>Office phone</dt><dd>{{ $val($employee->office_phone) }}</dd></div>
            </dl>
        </section>

        {{-- ===== Postings (everyone) ===== --}}
        <section class="card" id="postings">
            <h2 class="card-title">Postings</h2>
            @forelse ($employee->postings as $p)
                <div @class(['post-row', 'is-now' => ! $p->end_date])>
                    <span @class(['tag', 'tag-hq' => $p->isHq(), 'tag-proj' => ! $p->isHq()])>{{ $p->isHq() ? 'HQ' : 'Project' }}</span>
                    <div class="post-main">
                        <strong>{{ $p->placeName() }}</strong>
                        <small>
                            {{ $p->start_date->format('d M Y') }} – {{ $p->end_date?->format('d M Y') ?? 'now' }}
                            @if ($p->role_on_posting) · {{ $p->role_on_posting }} @endif
                            @if ($p->order_ref) · Order {{ $p->order_ref }} @endif
                        </small>
                    </div>
                    @unless ($p->end_date)<span class="post-now">Current</span>@endunless
                </div>
            @empty
                <p class="muted">No postings recorded.</p>
            @endforelse
        </section>

        {{-- ===== Personal (HR + self) ===== --}}
        <section class="card" id="personal">
            <h2 class="card-title">Personal details @unless ($canSeeSensitive)<span class="lock">Private</span>@endunless</h2>
            @if ($canSeeSensitive)
                <dl class="facts">
                    <div><dt>Date of birth</dt><dd>{{ $employee->date_of_birth?->format('d M Y') ?? $dash }}@if ($employee->age()) ({{ $employee->age() }} years)@endif</dd></div>
                    <div><dt>Gender</dt><dd>{{ \App\Models\Employee::GENDERS[$employee->gender] ?? $dash }}</dd></div>
                    <div><dt>Blood group</dt><dd>{{ $val($employee->blood_group) }}</dd></div>
                    <div><dt>NID</dt><dd>{{ $val($employee->nid_no) }}</dd></div>
                    <div><dt>TIN</dt><dd>{{ $val($employee->tin_no) }}</dd></div>
                    <div><dt>Passport</dt><dd>{{ $val($employee->passport_no) }}</dd></div>
                    <div><dt>Personal email</dt><dd>{{ $val($employee->personal_email) }}</dd></div>
                    <div><dt>Personal phone</dt><dd>{{ $val($employee->personal_phone) }}</dd></div>
                    <div class="wide"><dt>Present address</dt><dd>{{ $val($employee->present_address) }}</dd></div>
                    <div class="wide"><dt>Permanent address</dt><dd>{{ $val($employee->permanent_address) }}</dd></div>
                    <div><dt>Emergency contact</dt><dd>{{ $val($employee->emergency_contact_name) }}@if ($employee->emergency_contact_relation) ({{ $employee->emergency_contact_relation }})@endif</dd></div>
                    <div><dt>Emergency phone</dt><dd>{{ $val($employee->emergency_contact_phone) }}</dd></div>
                    <div><dt>Bank</dt><dd>{{ $val($employee->bank_name) }}</dd></div>
                    <div><dt>Account number</dt><dd>{{ $val($employee->bank_account_no) }}</dd></div>
                </dl>
            @else
                <p class="private-note">Personal details, ID numbers, contacts and bank details are visible only to HR and to {{ $employee->name }}.</p>
            @endif
        </section>

        {{-- ===== Salary (HR + self) ===== --}}
        <section class="card" id="salary">
            <h2 class="card-title">Salary @unless ($canSeeSensitive)<span class="lock">Private</span>@endunless</h2>
            @if ($canSeeSensitive)
                @if ($s = $employee->currentSalary)
                    <dl class="facts">
                        @foreach (\App\Models\SalaryStructure::PARTS as $key => $label)
                            <div><dt>{{ $label }}</dt><dd>৳ {{ number_format($s->{$key}) }}</dd></div>
                        @endforeach
                        <div class="total"><dt>Gross per month</dt><dd>৳ {{ number_format($s->gross) }}</dd></div>
                    </dl>
                    <p class="muted small">Since {{ $s->effective_from->format('d M Y') }}</p>

                    @if ($employee->salaries->count() > 1)
                        <h3 class="sub-title">History</h3>
                        <table class="mini-table">
                            <thead><tr><th>From</th><th>To</th><th class="num">Gross</th></tr></thead>
                            <tbody>
                                @foreach ($employee->salaries as $h)
                                    <tr><td>{{ $h->effective_from->format('d M Y') }}</td><td>{{ $h->effective_to?->format('d M Y') ?? 'now' }}</td><td class="num">৳ {{ number_format($h->gross) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @else
                    <p class="muted">No salary recorded yet.</p>
                @endif
            @else
                <p class="private-note">Salary is visible only to HR and to {{ $employee->name }}.</p>
            @endif
        </section>
    </div>

    @can('manage-records')
        <div class="danger-zone">
            <form method="POST" action="{{ route('manage.employees.destroy', $employee) }}" data-confirm="Remove {{ $employee->name }} from the directory? Their history is kept and HR can restore it from the database.">
                @csrf
                @method('DELETE')
                <button type="submit" class="link link-danger">Remove from directory</button>
            </form>
        </div>

        {{-- ===== Transfer dialog ===== --}}
        <dialog class="dialog" id="transfer" @if ($errors->hasAny(['posting_type', 'office_id', 'project_id', 'start_date'])) data-open-now @endif>
            <form method="POST" action="{{ route('manage.employees.transfer', $employee) }}" novalidate>
                @csrf
                <h2>Transfer {{ $employee->name }}</h2>
                <p class="muted">
                    Now: <strong>{{ $post?->placeName() ?? 'no posting' }}</strong>.
                    The current posting ends the day before the new one starts.
                </p>

                @include('manage.employees._posting-fields', ['prefix' => '', 'startName' => 'start_date', 'withRemarks' => true])

                <div class="form-foot">
                    <button type="button" class="btn btn-ghost" data-close>Cancel</button>
                    <button type="submit" class="btn btn-brick">Transfer</button>
                </div>
            </form>
        </dialog>
    @endcan
@endsection
