<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\AssetUnit;
use App\Models\AssetUnitLog;
use App\Services\TridatuNetmonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionApiController extends Controller
{
    public function __construct(protected TridatuNetmonService $tridatu) {}

    /**
     * GET /api/v1/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $txns = Transaction::with(['fromSite', 'toSite', 'customer', 'createdBy'])
            ->when($request->type,        fn($q, $v) => $q->where('type', $v))
            ->when($request->status,      fn($q, $v) => $q->where('status', $v))
            ->when($request->from_site_id, fn($q, $v) => $q->where('from_site_id', $v))
            ->when($request->to_site_id,  fn($q, $v) => $q->where('to_site_id', $v))
            ->when($request->tridatu_user_id, fn($q, $v) => $q->where('tridatu_user_id', $v))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $txns]);
    }

    /**
     * POST /api/v1/transactions
     *
     * Buat transaksi baru (stock_in / stock_out / deployment / retrieval / transfer / rma)
     * Body: { type, from_site_id?, to_site_id?, customer_id?, tridatu_user_id?, notes, asset_unit_ids[] }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'             => ['required', Rule::in(array_keys([
                'stock_in'=>1,'stock_out'=>1,'transfer'=>1,
                'deployment'=>1,'retrieval'=>1,'rma_out'=>1,'rma_in'=>1
            ]))],
            'from_site_id'     => ['nullable', 'exists:sites,id'],
            'to_site_id'       => ['nullable', 'exists:sites,id'],
            'customer_id'      => ['nullable', 'exists:customers,id'],
            'tridatu_user_id'  => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
            'asset_unit_ids'   => ['required', 'array', 'min:1'],
            'asset_unit_ids.*' => ['exists:asset_units,id'],
        ]);

        DB::beginTransaction();
        try {
            // Resolve nama teknisi dari Tridatu
            $staffName = null;
            if (!empty($validated['tridatu_user_id'])) {
                $staffName = $this->tridatu->getStaffName($validated['tridatu_user_id']);
            }

            // Buat transaksi
            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateNumber($validated['type']),
                'type'               => $validated['type'],
                'from_site_id'       => $validated['from_site_id'] ?? null,
                'to_site_id'         => $validated['to_site_id'] ?? null,
                'customer_id'        => $validated['customer_id'] ?? null,
                'tridatu_user_id'    => $validated['tridatu_user_id'] ?? null,
                'tridatu_user_name'  => $staffName,
                'created_by'         => auth()->id(),
                'status'             => Transaction::STATUS_COMPLETED,
                'transaction_date'   => $validated['transaction_date'] ?? today(),
                'notes'              => $validated['notes'] ?? null,
            ]);

            // Map type → (new_status, action)
            $statusActionMap = [
                'stock_in'   => ['in_stock', AssetUnitLog::ACTION_RECEIVED],
                'stock_out'  => ['in_stock', AssetUnitLog::ACTION_MOVED],
                'transfer'   => ['in_stock', AssetUnitLog::ACTION_MOVED],
                'deployment' => ['deployed', AssetUnitLog::ACTION_DEPLOYED],
                'retrieval'  => ['in_stock', AssetUnitLog::ACTION_RETRIEVED],
                'rma_out'    => ['rma',      AssetUnitLog::ACTION_SENT_RMA],
                'rma_in'     => ['in_stock', AssetUnitLog::ACTION_RMA_RETURNED],
            ];

            [$newStatus, $logAction] = $statusActionMap[$validated['type']];

            foreach ($validated['asset_unit_ids'] as $unitId) {
                $unit = AssetUnit::findOrFail($unitId);

                // Tambah ke detail transaksi
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'asset_unit_id'  => $unit->id,
                ]);

                $fromStatus = $unit->status;
                $fromSiteId = $unit->site_id;

                // Update unit
                $unit->update([
                    'status'      => $newStatus,
                    'site_id'     => $validated['to_site_id'] ?? $unit->site_id,
                    'customer_id' => $validated['type'] === 'deployment'
                        ? ($validated['customer_id'] ?? $unit->customer_id)
                        : ($validated['type'] === 'retrieval' ? null : $unit->customer_id),
                ]);

                // Audit log
                AssetUnitLog::create([
                    'asset_unit_id'    => $unit->id,
                    'action'           => $logAction,
                    'from_status'      => $fromStatus,
                    'to_status'        => $newStatus,
                    'from_site_id'     => $fromSiteId,
                    'to_site_id'       => $validated['to_site_id'] ?? null,
                    'customer_id'      => $validated['customer_id'] ?? null,
                    'tridatu_user_id'  => $validated['tridatu_user_id'] ?? null,
                    'tridatu_user_name'=> $staffName,
                    'performed_by'     => auth()->id(),
                    'transaction_id'   => $transaction->id,
                    'notes'            => $validated['notes'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'message'    => 'Transaksi berhasil dibuat.',
                'data'       => $transaction->load('details.assetUnit'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/transactions/{id}
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load([
            'details.assetUnit.assetType',
            'fromSite.region', 'toSite.region',
            'customer', 'createdBy',
        ]);

        return response()->json(['success' => true, 'data' => $transaction]);
    }
}
