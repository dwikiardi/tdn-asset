<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\AssetUnit;
use App\Services\TridatuNetmonService;
use Illuminate\Http\Request;

class SelectController extends Controller
{
    public function __construct(protected TridatuNetmonService $tridatu) {}
    public function category(Request $request)
    {
        $search = $request->search;

        if ($search == '') {
            $categories = Category::orderby('name', 'asc')->select('id', 'name')->limit(10)->get();
        } else {
            $categories = Category::orderby('name', 'asc')->select('id', 'name')->where('name', 'like', '%' . $search . '%')->limit(10)->get();
        }

        $response = array();
        foreach ($categories as $category) {
            $response[] = array(
                "id" => $category->id,
                "text" => $category->name
            );
        }

        return response()->json($response);
    }

    public function supplier(Request $request)
    {
        $search = $request->search;

        if ($search == '') {
            $suppliers = Supplier::orderby('name', 'asc')->select('id', 'name')->limit(10)->get();
        } else {
            $suppliers = Supplier::orderby('name', 'asc')->select('id', 'name')->where('name', 'like', '%' . $search . '%')->limit(10)->get();
        }

        $response = array();
        foreach ($suppliers as $supplier) {
            $response[] = array(
                "id" => $supplier->id,
                "text" => $supplier->name
            );
        }

        return response()->json($response);
    }

    public function employee(Request $request)
    {
        $search = $request->search;

        if ($search == '') {
            $employees = Employee::orderby('name', 'asc')->select('id', 'name')->limit(10)->get();
        } else {
            $employees = Employee::orderby('name', 'asc')->select('id', 'name')->where('name', 'like', '%' . $search . '%')->limit(10)->get();
        }

        $response = array();
        foreach ($employees as $employee) {
            $response[] = array(
                "id" => $employee->id,
                "text" => $employee->name
            );
        }

        return response()->json($response);
    }

    public function division(Request $request)
    {
        $search = $request->search;

        if ($search == '') {
            $divisions = Division::with('company')->orderby('name', 'asc')->select('id', 'name', 'company_id')->limit(10)->get();
        } else {
            $divisions = Division::with('company')->orderby('name', 'asc')->select('id', 'name', 'company_id')->where('name', 'like', '%' . $search . '%')->orWhereHas('company', function ($query) use ($search) {
                return $query->where('name', 'like', '%' . $search . '%');
            })->limit(10)->get();
        }

        // dd($divisions);
        $response = array();
        foreach ($divisions as $division) {
            $response[] = array(
                "id" => $division->id,
                "text" => $division->name . ' - ' . $division->company->name
            );
        }

        return response()->json($response);
    }

    public function asset(Request $request)
    {
        $search = $request->search;
        $type = $request->type; // 'stock_in' or 'stock_out'

        $query = AssetUnit::with('assetType');

        if ($type == 'stock_out') {
            $query->where('status', AssetUnit::STATUS_IN_STOCK);
        } elseif ($type == 'stock_in') {
            $query->whereIn('status', [AssetUnit::STATUS_DEPLOYED, 'pulled', 'faulty']);
        }

        if ($search != '') {
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', '%' . $search . '%')
                  ->orWhere('mac_address', 'like', '%' . $search . '%')
                  ->orWhereHas('assetType', function($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('specification', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%');
                  });
            });
        }

        $units = $query->limit(10)->get();

        $response = array();
        foreach ($units as $unit) {
            $displayName = $unit->assetType->name ?: $unit->assetType->specification ?: $unit->assetType->model ?: 'Unknown';
            $response[] = array(
                "id" => $unit->id,
                "text" => ($unit->serial_number ?: $unit->mac_address ?: 'ID: '.$unit->id) . ' - ' . $displayName . ' (' . $unit->status . ')'
            );
        }

        return response()->json($response);
    }

    public function assetById($id)
    {
        $unit = AssetUnit::with(['assetType.category', 'customer'])->find($id);
        
        // Map back to old UI expected format
        if ($unit) {
            $unit->uid = $unit->serial_number ?? $unit->mac_address ?? ('ID: ' . $unit->id);
            $unit->category = $unit->assetType->category ?? null;
            $unit->specification = $unit->assetType->specification ?? '-';
            $unit->production_year = $unit->assetType->production_year ?? '-';
            $unit->purchase_date = $unit->purchase_date ? $unit->purchase_date->format('Y-m-d') : '-';
            $unit->purchase_price = number_format($unit->purchase_price ?? 0);
            $unit->condition = $unit->status; // or mapping
            $unit->status_label = $unit->status;
            $unit->status = ($unit->status === AssetUnit::STATUS_IN_STOCK) ? 0 : 1; // 0=Standby/IN for old UI
            
            // CID (Customer ID) info for retrieval
            $unit->cid = $unit->customer ? $unit->customer->external_id : null;
            $unit->customer_name = $unit->customer ? $unit->customer->name : null;
            $unit->quantity = $unit->quantity ?? 1;
            $unit->uom = $unit->assetType->uom ?? 'pcs';
            
            $displayName = $unit->assetType->name ?: $unit->assetType->specification ?: $unit->assetType->model ?: 'Unknown';
            $unit->text = $unit->uid . ' - ' . $displayName . ' (' . $unit->status_label . ')';
        }

        return response()->json($unit);
    }

    public function customer(Request $request)
    {
        $search = $request->search;
        $customers = $this->tridatu->getCustomerList();

        if ($search) {
            $customers = array_filter($customers, function($c) use ($search) {
                return (isset($c['nama']) && stripos($c['nama'], $search) !== false) || 
                       (isset($c['cid']) && stripos($c['cid'], $search) !== false);
            });
        }

        $response = [];
        foreach (array_slice($customers, 0, 20) as $c) {
            $response[] = [
                'id' => $c['cid'] ?? $c['id'],
                'text' => ($c['cid'] ?? '') . ' - ' . ($c['nama'] ?? '')
            ];
        }
        return response()->json($response);
    }

    public function staff(Request $request)
    {
        $search = $request->search;
        $staff = $this->tridatu->getStaffList();

        if ($search) {
            $staff = array_filter($staff, function($s) use ($search) {
                return (isset($s['name']) && stripos($s['name'], $search) !== false) ||
                       (isset($s['username']) && stripos($s['username'], $search) !== false);
            });
        }

        $response = [];
        foreach (array_slice($staff, 0, 20) as $s) {
            $response[] = [
                'id' => $s['id'],
                'text' => $s['name'] ?? $s['username']
            ];
        }
        return response()->json($response);
    }

    public function getAssetsByCustomer(Request $request)
    {
        $customerId = $request->cid;
        if (!$customerId) return response()->json([]);

        // Cari customer local dulu untuk dapatkan ID local-nya
        $customer = Customer::where('external_id', $customerId)->first();
        if (!$customer) return response()->json([]);

        $assets = AssetUnit::with('assetType')
            ->where('customer_id', $customer->id)
            ->where('status', AssetUnit::STATUS_DEPLOYED)
            ->get();

        return response()->json($assets);
    }
}
