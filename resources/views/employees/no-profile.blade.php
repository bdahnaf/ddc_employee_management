@extends('layouts.panel')

@section('title', 'My profile')

@section('content')
    <div class="empty-state">
        <p><strong>@auth Your sign-in isn't linked to a staff record yet. @else No personal profile while sign-in is off. @endauth</strong></p>
        @auth
            <p class="muted">Ask HR to link your account ({{ auth()->user()->email }}) to your employee profile.</p>
        @else
            <p class="muted">Sign-in is switched off for now, so there is no "me". Open anyone's profile from the directory.</p>
        @endauth
        <a href="{{ route('directory') }}" class="btn btn-ghost">Go to the staff directory</a>
    </div>
@endsection
