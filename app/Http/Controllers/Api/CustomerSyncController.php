<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\AssetUnit;
use App\Services\TridatuNetmonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSyncController extends Controller
{
    public function __construct(protected TridatuNetmonService $tridatu) {}

    /**
     * POST /api/v1/customers/sync
     *
     * Upsert customer dari sistem Tridatu Netmon berdasarkan external_id.
     * Digunakan ketika ada event baru di Tridatu (customer baru, update data).
     *
     * Body: { external_id, external_source, name, phone, email, address, metadata }
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'external_id'       => ['required', 'string', 'max:255'],
            'external_source'   => ['nullable', 'string', 'max:100'],
            'name'              => ['required', 'string', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'email'             => ['nullable', 'email', 'max:255'],
            'address'           => ['nullable', 'string'],
            'site_id'           => ['nullable', 'exists:sites,id'],
            'metadata'          => ['nullable', 'array'],
        ]);

        $customer = Customer::updateOrCreate(
            ['external_id' => $validated['external_id']],
            [
                'name'              => $validated['name'],
                'phone'             => $validated['phone'] ?? null,
                'email'             => $validated['email'] ?? null,
                'address'           => $validated['address'] ?? null,
                'site_id'           => $validated['site_id'] ?? null,
                'external_source'   => $validated['external_source'] ?? 'tridatu_netmon',
                'external_metadata' => $validated['metadata'] ?? null,
                'synced_at'         => now(),
            ]
        );

        return response()->json([
            'success'  => true,
            'message'  => 'Customer berhasil disinkronkan.',
            'data'     => $customer,
            'action'   => $customer->wasRecentlyCreated ? 'created' : 'updated',
        ], $customer->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * GET /api/v1/customers/{externalId}/assets
     *
     * Ambil semua aset yang ter-deploy di customer berdasarkan external_id Tridatu.
     */
    public function assetsByExternalId(string $externalId): JsonResponse
    {
        $customer = Customer::where('external_id', $externalId)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan. Lakukan sync terlebih dahulu.',
            ], 404);
        }

        $assets = AssetUnit::with(['assetType.category', 'site'])
            ->where('customer_id', $customer->id)
            ->where('status', 'deployed')
            ->get();

        // Grouping for Netmon summary (e.g. "10 AP Ruijie", "10 meter Comscope")
        $summary = $assets->groupBy('asset_type_id')->map(function ($units) {
            $first = $units->first();
            $name  = $first->assetType->name ?? 'Unknown';
            $qty   = $units->sum('quantity');
            $uom   = $first->assetType->uom ?? 'pcs';
            
            return [
                'asset_name' => $name,
                'total_qty'  => $qty,
                'uom'        => $uom,
                'label'      => $qty . ' ' . $uom . ' ' . $name
            ];
        })->values();

        return response()->json([
            'status'   => 'success',
            'customer' => $customer,
            'summary'  => $summary,
            'assets'   => $assets,
            'total_items' => $assets->count(),
        ]);
    }

    /**
     * GET /api/v1/customers
     * List semua customer (bisa difilter by external_source)
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::with('site.region')
            ->when($request->external_source, fn($q, $v) => $q->where('external_source', $v))
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%")
                ->orWhere('external_id', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $customers]);
    }
}
