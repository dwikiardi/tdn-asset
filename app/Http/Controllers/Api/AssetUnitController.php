<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetUnit;
use App\Models\AssetUnitLog;
use App\Services\TridatuNetmonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetUnitController extends Controller
{
    public function __construct(protected TridatuNetmonService $tridatu) {}

    /**
     * GET /api/v1/asset-units
     * Filter: status, site_id, asset_type_id, serial_number, mac_address
     */
    public function index(Request $request): JsonResponse
    {
        $query = AssetUnit::with(['assetType.category', 'site.region', 'customer'])
            ->when($request->status,        fn($q, $v) => $q->where('status', $v))
            ->when($request->site_id,       fn($q, $v) => $q->where('site_id', $v))
            ->when($request->asset_type_id, fn($q, $v) => $q->where('asset_type_id', $v))
            ->when($request->serial_number, fn($q, $v) => $q->where('serial_number', 'like', "%{$v}%"))
            ->when($request->mac_address,   fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('mac_address', 'like', "%{$v}%")
                  ->orWhere('mac_address_2', 'like', "%{$v}%");
            }));

        $units = $query->orderByDesc('updated_at')->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $units,
        ]);
    }

    /**
     * GET /api/v1/asset-units/{id}
     * Detail unit + history log lengkap
     */
    public function show(AssetUnit $assetUnit): JsonResponse
    {
        $assetUnit->load([
            'assetType.category',
            'assetType.supplier',
            'site.region',
            'customer',
            'logs.fromSite',
            'logs.toSite',
            'logs.customer',
            'logs.performedBy',
        ]);

        // Resolusi nama staff dari Tridatu untuk setiap log
        $logs = $assetUnit->logs->map(function ($log) {
            if ($log->tridatu_user_id && !$log->tridatu_user_name) {
                $log->tridatu_user_name = $this->tridatu->getStaffName($log->tridatu_user_id);
            }
            return $log;
        });

        return response()->json([
            'success' => true,
            'data'    => array_merge($assetUnit->toArray(), ['logs' => $logs]),
        ]);
    }

    /**
     * PATCH /api/v1/asset-units/{id}/status
     * Update status unit + otomatis buat log
     */
    public function updateStatus(Request $request, AssetUnit $assetUnit): JsonResponse
    {
        $validated = $request->validate([
            'status'           => ['required', Rule::in(AssetUnit::ALL_STATUSES)],
            'to_site_id'       => ['nullable', 'exists:sites,id'],
            'customer_id'      => ['nullable', 'exists:customers,id'],
            'tridatu_user_id'  => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $fromStatus = $assetUnit->status;
            $fromSiteId = $assetUnit->site_id;

            // Map status ke aksi log
            $actionMap = [
                'in_stock' => AssetUnitLog::ACTION_RECEIVED,
                'deployed' => AssetUnitLog::ACTION_DEPLOYED,
                'faulty'   => AssetUnitLog::ACTION_FAULTY_NOTED,
                'rma'      => AssetUnitLog::ACTION_SENT_RMA,
                'pulled'   => AssetUnitLog::ACTION_PULLED,
            ];
            $action = $actionMap[$validated['status']] ?? AssetUnitLog::ACTION_CHECKED;

            // Update unit
            $assetUnit->update([
                'status'      => $validated['status'],
                'site_id'     => $validated['to_site_id'] ?? $assetUnit->site_id,
                'customer_id' => $validated['customer_id'] ?? ($validated['status'] === 'deployed' ? $assetUnit->customer_id : null),
            ]);

            // Resolve nama staff
            $staffName = null;
            if (!empty($validated['tridatu_user_id'])) {
                $staffName = $this->tridatu->getStaffName($validated['tridatu_user_id']);
            }

            // Buat log audit
            AssetUnitLog::create([
                'asset_unit_id'    => $assetUnit->id,
                'action'           => $action,
                'from_status'      => $fromStatus,
                'to_status'        => $validated['status'],
                'from_site_id'     => $fromSiteId,
                'to_site_id'       => $validated['to_site_id'] ?? null,
                'customer_id'      => $validated['customer_id'] ?? null,
                'tridatu_user_id'  => $validated['tridatu_user_id'] ?? null,
                'tridatu_user_name'=> $staffName,
                'performed_by'     => auth()->id(),
                'notes'            => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status unit berhasil diperbarui.',
                'data'    => $assetUnit->fresh(['assetType', 'site', 'customer']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
