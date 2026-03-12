<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login()
    {
        return view('components.login');
    }

    public function authenticated(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $tridatu = app(\App\Services\TridatuNetmonService::class);
        $tridatuUser = $tridatu->authenticate($credentials['username'], $credentials['password']);

        if ($tridatuUser) {
            $role = $tridatuUser['role'] ?? 'operator';
            if (!in_array($role, ['super_admin', 'admin', 'operator', 'viewer'])) {
                $role = 'operator';
            }

            // Create an ephemeral user object for Auth session
            $user = new \Illuminate\Auth\GenericUser([
                'id' => $tridatuUser['id'],
                'tridatu_user_id' => $tridatuUser['id'],
                'name' => $tridatuUser['name'] ?? $tridatuUser['full_name'] ?? $credentials['username'],
                'username' => $credentials['username'],
                'email' => $tridatuUser['email'] ?? ($credentials['username'] . '@tridatu.net.id'),
                'role' => $role,
                'is_active' => true,
                'remember_token' => null,
            ]);

            Auth::login($user);

            $request->session()->regenerate();
            $request->session()->flash('success', 'Login berhasil, Welcome to dashboard');
            return redirect()->route('dashboard');
        }

        // Optional: Fallback to local auth
        $localCredentials = [
            'username' => $credentials['username'],
            'password' => $credentials['password']
        ];
        
        if (Auth::attempt($localCredentials)) {
            $request->session()->regenerate();
            $request->session()->flash('success', 'Login berhasil (Local Auth)');
            return redirect()->route('dashboard');
        }

        $request->session()->flash('error', 'Login gagal, username atau password salah (Tridatu Netmon)');
        return back();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
