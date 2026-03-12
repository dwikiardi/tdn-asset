<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\GenericUser;
use App\Services\TridatuNetmonService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TridatuUserProvider implements UserProvider
{
    protected $service;

    public function __construct(TridatuNetmonService $service)
    {
        $this->service = $service;
    }

    public function retrieveById($identifier)
    {
        // Try getting from local DB first (for local accounts)
        $localUser = User::find($identifier);
        if ($localUser) {
            return $localUser;
        }

        // Try getting from Tridatu API
        $staff = $this->service->getStaffById($identifier);
        if ($staff) {
            return $this->getGenericUser($staff);
        }

        return null;
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Not supported for Tridatu via API
    }

    public function retrieveByCredentials(array $credentials)
    {
        // We only use this when Auth::attempt() is used.
        // For Tridatu, AuthController manually authenticates, but we can implement it.
        $username = $credentials['username'] ?? null;
        if (!$username) {
            return null;
        }

        $localUser = User::where('username', $username)->first();
        if ($localUser) {
            return $localUser;
        }

        return null; // For tridatu, AuthController already checks it manually
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }

    protected function getGenericUser($staff)
    {
        $role = $staff['role'] ?? 'operator';
        if (!in_array($role, ['super_admin', 'admin', 'operator', 'viewer'])) {
            $role = 'operator';
        }

        return new GenericUser([
            'id' => $staff['id'],
            'tridatu_user_id' => $staff['id'],
            'name' => $staff['name'] ?? $staff['full_name'] ?? $staff['username'],
            'username' => $staff['username'] ?? '',
            'email' => $staff['email'] ?? '',
            'role' => $role,
            'is_active' => true,
            'remember_token' => null,
        ]);
    }
}
