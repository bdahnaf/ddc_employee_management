<?php

namespace App\Http\Controllers\Hr;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * HR manages sign-in accounts: create, edit role, deactivate / reactivate, reset password.
 * Accounts are never hard-deleted, so the activity log always points at a real person.
 * Routes are protected by the `manage-users` gate (HR only).
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
            })
            ->when($request->input('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hr.users.index', ['users' => $users, 'roles' => Role::options()]);
    }

    public function create(): View
    {
        return view('hr.users.form', ['account' => new User(['role' => Role::Employee, 'is_active' => true]), 'roles' => Role::options()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $account = User::create([...$data, 'is_active' => true]);
        ActivityLog::record('user.created', "Created account for {$account->email} ({$account->role->label()})", $account);

        return redirect()->route('hr.users.index')->with('status', "Account created for {$account->name}.");
    }

    public function edit(User $user): View
    {
        return view('hr.users.form', ['account' => $user, 'roles' => Role::options()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $this->guardLastHr($user, Role::from($data['role']));

        $changes = [];
        if ($user->role->value !== $data['role']) {
            $changes[] = "role {$user->role->label()} → ".Role::from($data['role'])->label();
        }
        if (! empty($data['password'])) {
            $changes[] = 'password reset';
        } else {
            unset($data['password']);
        }

        $user->update($data);
        ActivityLog::record('user.updated', "Updated {$user->email}".($changes ? ': '.implode(', ', $changes) : ''), $user);

        return redirect()->route('hr.users.index')->with('status', "Saved changes for {$user->name}.");
    }

    /** Deactivate or reactivate an account. */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($request->user() && $user->is($request->user())) {
            return back()->withErrors(['account' => 'You cannot deactivate your own account.']);
        }

        if ($user->is_active) {
            $this->guardLastHr($user, null);
        }

        $user->update(['is_active' => ! $user->is_active]);
        $verb = $user->is_active ? 'Reactivated' : 'Deactivated';
        ActivityLog::record($user->is_active ? 'user.reactivated' : 'user.deactivated', "{$verb} {$user->email}", $user);

        return back()->with('status', "{$verb} {$user->name}.");
    }

    /**
     * There must always be at least one active HR account,
     * otherwise nobody could manage the system.
     */
    private function guardLastHr(User $user, ?Role $newRole): void
    {
        $losingHr = $user->isHr() && $user->is_active && $newRole !== Role::Hr;

        if ($losingHr && User::hr()->active()->whereKeyNot($user->id)->doesntExist()) {
            abort(back()->withErrors(['account' => 'This is the last active HR account. Make someone else HR first.']));
        }
    }
}
