@extends('layouts.panel')

@php($editing = $account->exists)

@section('title', $editing ? "Edit {$account->name}" : 'Add account')

@section('content')
    <form method="POST" action="{{ $editing ? route('hr.users.update', $account) : route('hr.users.store') }}" class="card form-card" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        <label class="field">
            <span>Full name</span>
            <input type="text" name="name" value="{{ old('name', $account->name) }}" required maxlength="120" @error('name') aria-invalid="true" @enderror>
        </label>
        @error('name') <p class="field-error">{{ $message }}</p> @enderror

        <label class="field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email', $account->email) }}" required autocomplete="off" @error('email') aria-invalid="true" @enderror>
        </label>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror

        <fieldset class="field">
            <legend>Role</legend>
            <div class="radio-cards">
                @foreach ($roles as $value => $label)
                    <label class="radio-card">
                        <input type="radio" name="role" value="{{ $value }}" @checked(old('role', $account->role?->value) === $value)>
                        <span>
                            <strong>{{ $label }}</strong>
                            <small>
                                @switch($value)
                                    @case('hr') Sees and manages everything, including salary and personal details. @break
                                    @case('head') Sees attendance and leave for their own team. @break
                                    @default Sees the staff directory and their own full profile.
                                @endswitch
                            </small>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        @error('role') <p class="field-error">{{ $message }}</p> @enderror

        <div class="field-pair">
            <label class="field">
                <span>{{ $editing ? 'New password' : 'Password' }}</span>
                <input type="password" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }} @error('password') aria-invalid="true" @enderror>
                <small class="hint">{{ $editing ? 'Leave empty to keep the current password.' : 'At least 8 characters, with letters and numbers.' }}</small>
            </label>
            <label class="field">
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" autocomplete="new-password">
            </label>
        </div>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror

        <div class="form-foot">
            <a href="{{ route('hr.users.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-brick">{{ $editing ? 'Save changes' : 'Create account' }}</button>
        </div>
    </form>
@endsection
