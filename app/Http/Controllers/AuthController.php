<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('transactions.index');
        }

        $roles = [
            ['id' => 'teknisi', 'label' => 'Teknisi', 'email' => 'teknisi@adminpay.com'],
            ['id' => 'admin', 'label' => 'Admin', 'email' => 'admin@adminpay.com'],
            ['id' => 'atasan', 'label' => 'Atasan', 'email' => 'atasan@adminpay.com'],
            ['id' => 'owner', 'label' => 'Owner', 'email' => 'owner@adminpay.com'],
        ];

        return view('auth.login', compact('roles'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:teknisi,admin,atasan,owner',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, true)) {
            // Verify the user's role matches the selected role
            if (Auth::user()->role !== $request->role) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun ini tidak memiliki akses role ' . ucfirst($request->role) . '.',
                ])->withInput()->with('role', $request->role);
            }

            $request->session()->regenerate();
            return redirect()->route('transactions.index');
        }

        return redirect()->route('login', ['role' => $request->role])
            ->withErrors(['email' => 'Email atau password salah.'])
            ->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
