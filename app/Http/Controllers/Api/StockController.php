<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetUnit;
use App\Models\AssetType;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * GET /api/v1/stock
     *
     * Ringkasan stok seluruh aset.
     * Filter: site_id, asset_type_id, category_id, status
     */
    public function index(Request $request): JsonResponse
    {
        $query = AssetUnit::with(['assetType.category', 'site'])
            ->when($request->site_id,       fn($q, $v) => $q->where('site_id', $v))
            ->when($request->asset_type_id, fn($q, $v) => $q->where('asset_type_id', $v))
            ->when($request->category_id,   fn($q, $v) => $q->whereHas('assetType', fn($q) => $q->where('category_id', $v)))
            ->when($request->status,        fn($q, $v) => $q->where('status', $v));

        // Ringkasan stok per status
        $summary = $query->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Stok per tipe aset
        $byType = AssetUnit::with('assetType:id,name,brand,model')
            ->when($request->site_id,     fn($q, $v) => $q->where('site_id', $v))
            ->when($request->category_id, fn($q, $v) => $q->whereHas('assetType', fn($q) => $q->where('category_id', $v)))
            ->selectRaw('asset_type_id, status, COUNT(*) as total')
            ->groupBy('asset_type_id', 'status')
            ->get()
            ->groupBy('asset_type_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'asset_type_id' => $first->asset_type_id,
                    'asset_type'    => optional($first->assetType)->only(['id', 'name', 'brand', 'model']),
                    'by_status'     => $rows->pluck('total', 'status'),
                    'total'         => $rows->sum('total'),
                ];
            })->values();

        return response()->json([
            'success'        => true,
            'summary'        => array_merge([
                'new'      => 0, 'in_stock' => 0, 'deployed' => 0,
                'faulty'   => 0, 'rma'      => 0, 'pulled'   => 0,
            ], $summary->toArray()),
            'by_asset_type'  => $byType,
        ]);
    }

    /**
     * GET /api/v1/stock/by-site
     * Ringkasan stok dikelompokkan per site/lokasi
     */
    public function bySite(Request $request): JsonResponse
    {
        $sites = Site::with('region')
            ->withCount([
                'assetUnits as total_units',
                'assetUnits as in_stock_count' => fn($q) => $q->where('status', 'in_stock'),
                'assetUnits as deployed_count' => fn($q) => $q->where('status', 'deployed'),
                'assetUnits as faulty_count'   => fn($q) => $q->where('status', 'faulty'),
            ])
            ->when($request->region_id, fn($q, $v) => $q->where('region_id', $v))
            ->when($request->type,      fn($q, $v) => $q->where('type', $v))
            ->get();

        return response()->json(['success' => true, 'data' => $sites]);
    }
}
