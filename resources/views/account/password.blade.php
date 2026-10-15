@extends('layouts.panel')

@section('title', 'Change password')

@section('content')
    <form method="POST" action="{{ route('account.password.update') }}" class="card form-card" novalidate>
        @csrf
        @method('PUT')

        <label class="field">
            <span>Current password</span>
            <input type="password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" @enderror>
        </label>
        @error('current_password') <p class="field-error">{{ $message }}</p> @enderror

        <label class="field">
            <span>New password</span>
            <input type="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" @enderror>
            <small class="hint">At least 8 characters, with letters and numbers.</small>
        </label>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror

        <label class="field">
            <span>Confirm new password</span>
            <input type="password" name="password_confirmation" autocomplete="new-password" required>
        </label>

        <div class="form-foot">
            <button type="submit" class="btn btn-brick">Change password</button>
        </div>
    </form>
@endsection
