<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TridatuNetmonService
 *
 * Provides integration with the Tridatu Netmon external system.
 * Staff/user data is fetched from here — no local DB copy.
 * Results are cached in Laravel cache (not persisted to DB).
 */
class TridatuNetmonService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int    $cacheTtl; // seconds

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('tridatu.base_url', ''), '/');
        $this->apiKey   = config('tridatu.api_key', '');
        $this->cacheTtl = (int) config('tridatu.cache_ttl', 300); // 5 menit default
    }

    // ----------------------------------------------------------
    // STAFF / USER
    // ----------------------------------------------------------

    /**
     * Verifikasi kredensial ke Tridatu Netmon.
     */
    public function authenticate(string $username, string $password): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept'    => 'application/json',
            ])->timeout(10)->post($this->baseUrl . '/api/v1/login', [
                'username' => $username,
                'password' => $password,
            ]);

            if ($response->successful()) {
                return $response->json('data') ?? $response->json();
            }
        } catch (\Throwable $e) {
            Log::error('[TridatuNetmon] Auth exception', ['username' => $username, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Ambil semua data staff dari Tridatu Netmon.
     * Di-cache untuk menghindari panggilan API berulang.
     */
    public function getStaffList(): array
    {
        return Cache::remember('tridatu_staff_list', $this->cacheTtl, function () {
            return $this->get('/api/v1/staff') ?? [];
        });
    }

    /**
     * Ambil detail satu staff berdasarkan ID Tridatu.
     */
    public function getStaffById(string $tridatuUserId): ?array
    {
        return Cache::remember("tridatu_staff_{$tridatuUserId}", $this->cacheTtl, function () use ($tridatuUserId) {
            $individual = $this->get("/api/v1/staff/{$tridatuUserId}");
            if ($individual) return $individual;

            // Fallback: cari di list
            $list = $this->getStaffList();
            foreach ($list as $s) {
                if (($s['id'] ?? null) == $tridatuUserId) return $s;
            }
            return null;
        });
    }

    /**
     * Ambil nama staff (dengan fallback ringan).
     */
    public function getStaffName(string $tridatuUserId, string $fallback = '-'): string
    {
        $staff = $this->getStaffById($tridatuUserId);
        return $staff['name'] ?? $staff['full_name'] ?? $staff['username'] ?? $fallback;
    }

    // ----------------------------------------------------------
    // CUSTOMER SYNC
    // ----------------------------------------------------------

    /**
     * Ambil semua data customer dari Tridatu Netmon.
     */
    public function getCustomerList(): array
    {
        return Cache::remember('tridatu_customer_list', $this->cacheTtl, function () {
            return $this->get('/api/v1/customers') ?? [];
        });
    }

    /**
     * Ambil detail customer dari Tridatu berdasarkan external ID (CID).
     */
    public function getCustomerByExternalId(string $externalId): ?array
    {
        return Cache::remember("tridatu_customer_{$externalId}", $this->cacheTtl, function () use ($externalId) {
            $individual = $this->get("/api/v1/customers/{$externalId}");
            if ($individual) return $individual;

            // Fallback: cari di list
            $list = $this->getCustomerList();
            foreach ($list as $c) {
                if (($c['cid'] ?? null) == $externalId || ($c['id'] ?? null) == $externalId) return $c;
            }
            return null;
        });
    }

    // ----------------------------------------------------------
    // PUSH NOTIFICATIONS KE TRIDATU (OPSIONAL)
    // ----------------------------------------------------------

    /**
     * Kirim notifikasi ke Tridatu Netmon bahwa aset berhasil di-deploy ke customer.
     */
    public function notifyAssetDeployed(string $externalCustomerId, array $unitData): bool
    {
        return $this->post('/api/assets/notify-deployed', [
            'customer_id'   => $externalCustomerId,
            'serial_number' => $unitData['serial_number'] ?? '',
            'mac_address'   => $unitData['mac_address'] ?? '',
            'asset_name'    => $unitData['name'] ?? '',
            'deployed_at'   => now()->toIso8601String(),
        ]);
    }

    /**
     * Kirim notifikasi bahwa aset diambil/dicabut dari pelanggan.
     */
    public function notifyAssetRetrieved(string $externalCustomerId, array $unitData): bool
    {
        return $this->post('/api/assets/notify-retrieved', [
            'customer_id'   => $externalCustomerId,
            'serial_number' => $unitData['serial_number'] ?? '',
            'retrieved_at'  => now()->toIso8601String(),
        ]);
    }

    // ----------------------------------------------------------
    // INTERNAL HELPERS (HTTP)
    // ----------------------------------------------------------

    protected function get(string $path): mixed
    {
        if (empty($this->baseUrl)) {
            Log::warning('[TridatuNetmon] base_url not configured.');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept'    => 'application/json',
            ])->timeout(10)->get($this->baseUrl . $path);

            if ($response->successful()) {
                return $response->json('data') ?? $response->json();
            }

            Log::warning('[TridatuNetmon] GET failed', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[TridatuNetmon] GET exception', ['path' => $path, 'error' => $e->getMessage()]);
        }

        return null;
    }

    protected function post(string $path, array $data): bool
    {
        if (empty($this->baseUrl)) {
            Log::warning('[TridatuNetmon] base_url not configured, skipping POST.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept'    => 'application/json',
            ])->timeout(10)->post($this->baseUrl . $path, $data);

            if ($response->successful()) {
                return true;
            }

            Log::warning('[TridatuNetmon] POST failed', [
                'path'   => $path,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[TridatuNetmon] POST exception', ['path' => $path, 'error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Hapus cache staff/customer (untuk force refresh).
     */
    public function flushCache(): void
    {
        Cache::forget('tridatu_staff_list');
        Cache::forget('tridatu_customer_list');
    }
}
