@extends('layouts.panel')

@php
    $editing = $employee->exists;
    $s = $employee->currentSalary;
    $field = fn ($name) => old($name, $employee->{$name} instanceof \Illuminate\Support\Carbon ? $employee->{$name}->toDateString() : $employee->{$name});
@endphp

@section('title', $editing ? "Edit {$employee->name}" : 'Add employee')
@section('subtitle', $editing ? 'Changes are saved to the profile and logged.' : 'Fields marked * are required. Everything else can be filled in later.')

@section('content')
    @if ($errors->any() && ! $errors->has('account'))
        <div class="notice notice-error" role="alert">Please check the highlighted fields below.</div>
    @endif

    <form method="POST" action="{{ $editing ? route('manage.employees.update', $employee) : route('manage.employees.store') }}" enctype="multipart/form-data" class="form-stack" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- ===== Basic ===== --}}
        <section class="card form-section">
            <header><h2>Basic information</h2><p>Visible to everyone in the directory.</p></header>

            <div class="photo-field">
                <span class="photo-preview" data-photo-preview>
                    @if ($employee->photo_path)
                        <img src="{{ route('employees.photo', $employee) }}" alt="">
                    @else
                        <span class="pc-initials">{{ $editing ? $employee->initials() : '+' }}</span>
                    @endif
                </span>
                <div>
                    <label class="btn btn-ghost btn-sm file-btn">
                        {{ $employee->photo_path ? 'Change photo' : 'Upload photo' }}
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input>
                    </label>
                    @if ($employee->photo_path)
                        <label class="check small"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label>
                    @endif
                    <small class="hint">JPG, PNG or WebP, up to 2 MB. A square photo looks best.</small>
                    @error('photo') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid-2">
                <label class="field"><span>Full name *</span><input type="text" name="name" value="{{ $field('name') }}" required maxlength="150" @error('name') aria-invalid="true" @enderror></label>
                <label class="field"><span>Employee ID *</span><input type="text" name="employee_code" value="{{ $field('employee_code') }}" required maxlength="30" placeholder="e.g. DDC-0142" @error('employee_code') aria-invalid="true" @enderror></label>
            </div>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
            @error('employee_code') <p class="field-error">{{ $message }}</p> @enderror

            <div class="grid-2">
                <label class="field"><span>Department *</span>
                    <select name="department_id" @error('department_id') aria-invalid="true" @enderror>
                        <option value="">Choose…</option>
                        @foreach ($departments->groupBy(fn ($d) => $d->division?->name ?? 'Other') as $division => $group)
                            <optgroup label="{{ $division }}">
                                @foreach ($group as $d)
                                    <option value="{{ $d->id }}" @selected((int) $field('department_id') === $d->id)>{{ $d->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @if ($departments->isEmpty())<small class="hint">No departments yet. <a class="link" href="{{ route('manage.structure.create', 'departments') }}">Add one first</a>.</small>@endif
                </label>
                <label class="field"><span>Designation</span>
                    <select name="designation_id">
                        <option value="">None</option>
                        @foreach ($designations as $d)
                            <option value="{{ $d->id }}" @selected((int) $field('designation_id') === $d->id)>{{ $d->title }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @error('department_id') <p class="field-error">{{ $message }}</p> @enderror

            <div class="grid-3">
                <label class="field"><span>Status *</span>
                    <select name="status">
                        @foreach (\App\Models\Employee::STATUSES as $v => $l)
                            <option value="{{ $v }}" @selected($field('status') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Employment type *</span>
                    <select name="employment_type">
                        @foreach (\App\Models\Employee::EMPLOYMENT_TYPES as $v => $l)
                            <option value="{{ $v }}" @selected($field('employment_type') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field"><span>Joining date</span><input type="date" name="joining_date" value="{{ $field('joining_date') }}"></label>
            </div>

            <div class="grid-3">
                <label class="field"><span>Office email</span><input type="email" name="office_email" value="{{ $field('office_email') }}" @error('office_email') aria-invalid="true" @enderror></label>
                <label class="field"><span>Office phone</span><input type="text" name="office_phone" value="{{ $field('office_phone') }}"></label>
                <label class="field"><span>Sign-in account</span>
                    <select name="user_id">
                        <option value="">Not linked</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected((int) $field('user_id') === $a->id)>{{ $a->name }} ({{ $a->email }})</option>
                        @endforeach
                    </select>
                    <small class="hint">Links this record to a login so they can see their own full profile.</small>
                </label>
            </div>
            @error('office_email') <p class="field-error">{{ $message }}</p> @enderror
            @error('user_id') <p class="field-error">{{ $message }}</p> @enderror
        </section>

        {{-- ===== Posting (create only; later changes use Transfer) ===== --}}
        @unless ($editing)
            <section class="card form-section">
                <header><h2>Current posting</h2><p>Where they work now. Later moves are done with <strong>Transfer</strong> on the profile.</p></header>
                @include('manage.employees._posting-fields', ['startName' => 'posting_start'])
            </section>
        @endunless

        {{-- ===== Salary ===== --}}
        <section class="card form-section">
            <header><h2>Salary <span class="lock">Private</span></h2><p>Only HR and the employee can see this. Changing the amounts keeps the old salary in the history.</p></header>
            <div class="grid-3">
                @foreach (\App\Models\SalaryStructure::PARTS as $key => $label)
                    <label class="field"><span>{{ $label }} (৳)</span>
                        <input type="number" name="{{ $key }}" min="0" step="1" value="{{ old($key, $s ? (float) $s->{$key} : '') }}" data-salary-part>
                    </label>
                @endforeach
                <label class="field"><span>Gross per month (৳)</span><output class="gross" data-gross>{{ $s ? number_format($s->gross) : '0' }}</output></label>
            </div>
            <label class="field narrow"><span>{{ $s ? 'New amounts apply from' : 'Salary starts from' }}</span>
                <input type="date" name="salary_from" value="{{ old('salary_from') }}">
                <small class="hint">{{ $s ? 'Leave empty for today. Use an earlier date only to correct the current salary.' : 'Leave empty to use the joining date.' }}</small>
            </label>
        </section>

        {{-- ===== Personal ===== --}}
        <section class="card form-section">
            <header><h2>Personal details <span class="lock">Private</span></h2><p>Only HR and the employee can see this.</p></header>
            <div class="grid-3">
                <label class="field"><span>Date of birth</span><input type="date" name="date_of_birth" value="{{ $field('date_of_birth') }}" @error('date_of_birth') aria-invalid="true" @enderror></label>
                <label class="field"><span>Gender</span>
                    <select name="gender"><option value="">—</option>
                        @foreach (\App\Models\Employee::GENDERS as $v => $l)<option value="{{ $v }}" @selected($field('gender') === $v)>{{ $l }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>Blood group</span>
                    <select name="blood_group"><option value="">—</option>
                        @foreach (\App\Models\Employee::BLOOD_GROUPS as $b)<option value="{{ $b }}" @selected($field('blood_group') === $b)>{{ $b }}</option>@endforeach
                    </select>
                </label>
            </div>
            @error('date_of_birth') <p class="field-error">{{ $message }}</p> @enderror
            <div class="grid-3">
                <label class="field"><span>NID number</span><input type="text" name="nid_no" value="{{ $field('nid_no') }}" @error('nid_no') aria-invalid="true" @enderror></label>
                <label class="field"><span>TIN number</span><input type="text" name="tin_no" value="{{ $field('tin_no') }}"></label>
                <label class="field"><span>Passport number</span><input type="text" name="passport_no" value="{{ $field('passport_no') }}"></label>
            </div>
            @error('nid_no') <p class="field-error">{{ $message }}</p> @enderror
            <div class="grid-2">
                <label class="field"><span>Personal email</span><input type="email" name="personal_email" value="{{ $field('personal_email') }}"></label>
                <label class="field"><span>Personal phone</span><input type="text" name="personal_phone" value="{{ $field('personal_phone') }}"></label>
            </div>
            <div class="grid-2">
                <label class="field"><span>Present address</span><textarea name="present_address" rows="2">{{ $field('present_address') }}</textarea></label>
                <label class="field"><span>Permanent address</span><textarea name="permanent_address" rows="2">{{ $field('permanent_address') }}</textarea></label>
            </div>
            <div class="grid-3">
                <label class="field"><span>Emergency contact</span><input type="text" name="emergency_contact_name" value="{{ $field('emergency_contact_name') }}"></label>
                <label class="field"><span>Relationship</span><input type="text" name="emergency_contact_relation" value="{{ $field('emergency_contact_relation') }}"></label>
                <label class="field"><span>Emergency phone</span><input type="text" name="emergency_contact_phone" value="{{ $field('emergency_contact_phone') }}"></label>
            </div>
            <div class="grid-2">
                <label class="field"><span>Bank</span><input type="text" name="bank_name" value="{{ $field('bank_name') }}"></label>
                <label class="field"><span>Bank account number</span><input type="text" name="bank_account_no" value="{{ $field('bank_account_no') }}"></label>
            </div>
        </section>

        <div class="form-bar">
            <a href="{{ $editing ? route('employees.show', $employee) : route('directory') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-brick">{{ $editing ? 'Save changes' : 'Add employee' }}</button>
        </div>
    </form>
@endsection
