@extends('layouts.panel')

@section('title', 'User accounts')

@section('actions')
    <a href="{{ route('hr.users.create') }}" class="btn btn-brick">Add account</a>
@endsection

@section('content')
    <form method="GET" class="filters" role="search">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email" aria-label="Search name or email">
        <select name="role" aria-label="Role">
            <option value="">All roles</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
        </select>
        <button type="submit" class="btn btn-ghost">Filter</button>
        @if (request()->hasAny(['q', 'role', 'status']))
            <a href="{{ route('hr.users.index') }}" class="link">Clear</a>
        @endif
    </form>

    <div class="card table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last sign-in</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $account)
                    <tr @class(['is-off' => ! $account->is_active])>
                        <td>
                            <div class="person">
                                <span class="avatar">{{ $account->initials() }}</span>
                                <span>
                                    <strong>{{ $account->name }}</strong>
                                    <small>{{ $account->email }}</small>
                                </span>
                            </div>
                        </td>
                        <td><span class="chip chip-{{ $account->role->value }}">{{ $account->role->shortLabel() }}</span></td>
                        <td>
                            @if ($account->is_active)
                                <span class="state state-on">Active</span>
                            @else
                                <span class="state state-off">Deactivated</span>
                            @endif
                        </td>
                        <td class="muted">{{ $account->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="row-actions">
                            <a href="{{ route('hr.users.edit', $account) }}" class="link">Edit</a>
                            @unless (auth()->check() && $account->is(auth()->user()))
                                <form method="POST" action="{{ route('hr.users.toggle', $account) }}"
                                      data-confirm="{{ $account->is_active ? "Deactivate {$account->name}? They will be signed out and won't be able to sign in." : "Reactivate {$account->name}?" }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="link {{ $account->is_active ? 'link-danger' : '' }}">
                                        {{ $account->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No accounts match. <a href="{{ route('hr.users.index') }}" class="link">Clear filters</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links('partials.pagination') }}
@endsection
