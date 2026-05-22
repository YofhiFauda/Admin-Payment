<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const LOGIN_ROLE_LABELS = [
        'teknisi' => 'Teknisi',
        'admin' => 'Admin',
        'atasan' => 'Atasan',
        'owner' => 'Owner',
    ];

    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user()->role);
        }

        $roles = $this->loginRoles();
        $selectedRole = $request->query('role');

        if ($selectedRole && ! array_key_exists($selectedRole, self::LOGIN_ROLE_LABELS)) {
            return redirect()
                ->route('login')
                ->withHeaders($this->noStoreHeaders());
        }

        return response()
            ->view('auth.login', compact('roles', 'selectedRole'))
            ->withHeaders($this->noStoreHeaders());
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(array_keys(self::LOGIN_ROLE_LABELS))],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ];

        if (! Auth::attempt($credentials, false)) {
            return back()
                ->withErrors([
                    'email' => 'Email, password, atau role tidak sesuai.',
                ])
                ->onlyInput('email', 'role');
        }

        $request->session()->regenerate();

        return $this->redirectByRole(Auth::user()->role);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withHeaders($this->noStoreHeaders());
    }

    private function redirectByRole(string $role)
    {
        return $role === 'teknisi'
            ? redirect()->route('transactions.create')
            : redirect()->route('dashboard');
    }

    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma' => 'no-cache',
            'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
        ];
    }

    private function loginRoles(): array
    {
        return collect(self::LOGIN_ROLE_LABELS)
            ->map(fn (string $label, string $id) => [
                'id' => $id,
                'label' => $label,
            ])
            ->values()
            ->all();
    }
}
