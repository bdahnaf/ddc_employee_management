<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** The signed-in user's own account settings. */
class AccountController extends Controller
{
    public function editPassword(): View
    {
        return view('account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        $user = $request->user();
        $user->update(['password' => $request->input('password')]);
        ActivityLog::record('account.password', 'Changed own password', $user);

        return back()->with('status', 'Your password has been changed.');
    }
}
