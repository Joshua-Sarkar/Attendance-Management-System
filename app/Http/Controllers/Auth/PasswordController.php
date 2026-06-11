<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Display the force password change view.
     */
    public function showChange(): \Illuminate\View\View
    {
        return view('auth.change-password');
    }

    /**
     * Update the temporary password and unlock user access.
     */
    public function storeChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if ($user->role === 'employee') {
            return redirect()->route('employee.dashboard')
                ->with('success', 'Password updated successfully. Welcome to your dashboard!');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Password updated successfully.');
    }
}
