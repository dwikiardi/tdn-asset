<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\AssetUnit;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use function PHPSTORM_META\map;

class MonitorController extends Controller
{
    public function asset()
    {
        // $details = Asset::with('transaction_detail', 'transaction_detail.transaction', 'category')->get();
        // dd($details);
        return view('components.monitor-asset');
    }

    public function assetDatatable(Request $request)
    {
        if ($request->ajax()) {
            // $store = Store::where('user_id', auth()->user()->id)->first();
            $details = Asset::with('category', 'transaction_detail');
            // dd($order);
            return DataTables::eloquent($details)
                ->addIndexColumn()
                ->addColumn('_status', function ($row) {
                    if ($row->status == 0) {
                        return '<span class="badge bg-success">Standby</span>';
                    } else {
                        return '<span class="badge bg-danger">Not Standby</span>';
                    }
                })
                ->addColumn('_jumlah_transaksi', function ($row) {
                    return $row->transaction_detail->count();
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="History">
                                    <a href="#" class="btn btn-sm btn-soft-primary btn-history" data-id="' . $row->id . '" data-uid="' . $row->uid . '" data-category="' . $row->category->name . '" data-specification="' . $row->specification . '" data-status="' . $row->status . '"><i class="mdi mdi-source-pull"></i> History</a>
                                </li>

                            </ul>';
                })
                ->rawColumns(['action', '_status'])
                ->make(true);
        }
    }

    public function assetTransaction($id)
    {
        try {
            // Get all unit IDs for this asset type
            $unitIds = AssetUnit::where('asset_type_id', $id)->pluck('id');
            
            $transaction_detail = TransactionDetail::whereIn('asset_unit_id', $unitIds)
                ->with('transaction', 'transaction.employee', 'transaction.division', 'transaction.customer')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Transaction retrieve successfully',
                'data'    => $transaction_detail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }



    public function employee()
    {
        // $details = employee::with('transaction_detail', 'transaction_detail.transaction', 'category')->get();
        // dd($details);
        return view('components.monitor-employee');
    }

    public function employeeDatatable(Request $request)
    {
        if ($request->ajax()) {
            // $store = Store::where('user_id', auth()->user()->id)->first();
            $details = Employee::with('transaction');
            // dd($order);
            return DataTables::eloquent($details)
                ->addIndexColumn()
                ->addColumn('_status', function ($row) {
                    if ($row->status == 0) {
                        return '<span class="badge bg-success">Standby</span>';
                    } else {
                        return '<span class="badge bg-danger">Not Standby</span>';
                    }
                })
                ->addColumn('_jumlah_transaksi', function ($row) {
                    return $row->transaction->count();
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="History">
                                    <a href="#" class="btn btn-sm btn-soft-primary btn-history" data-id="' . $row->id . '" data-name="' . $row->name . '" data-hp="' . $row->hp . '" data-email="' . $row->email . '" ><i class="mdi mdi-source-pull"></i> History</a>
                                </li>

                            </ul>';
                })
                ->rawColumns(['action', '_status'])
                ->make(true);
        }
    }

    public function employeeTransaction($id)
    {
        try {
            $transactions = TransactionDetail::with('transaction', 'transaction.employee', 'transaction.division', 'asset.category')->whereHas('transaction', function ($query) use ($id) {
                $query->where('employee_id', $id);
            })->get();

            return response()->json([
                'success' => true,
                'message' => 'Transaction retrieve successfully',
                'data'    => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }



    public function company()
    {
        // $details = Company::with('division', 'division.transaction')->get();

        // dd($details);
        return view('components.monitor-company');
    }

    public function companyDatatable(Request $request)
    {
        if ($request->ajax()) {
            // Updated to fetch Customers instead of Company due to user request
            $details = \App\Models\Customer::with('transactions.details', 'assetUnits');
            
            return DataTables::eloquent($details)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return $row->name ?? '-';
                })
                ->addColumn('abbreviation', function ($row) {
                    return $row->external_id ?? '-'; // Using external_id as CID placeholder
                })
                ->addColumn('_jumlah_transaksi', function ($row) {
                    // Count total movement items (history rows) instead of just transaction headers
                    $totalItems = 0;
                    foreach ($row->transactions as $tx) {
                        $totalItems += $tx->details->count();
                    }
                    return $totalItems;
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="History">
                                    <a href="#" class="btn btn-sm btn-soft-primary btn-history" data-id="' . $row->id . '" data-name="' . $row->name . '" data-abbreviation="' . $row->external_id . '"><i class="mdi mdi-source-pull"></i> History</a>
                                </li>

                            </ul>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function companyTransaction($id)
    {
        try {
            $transaction = TransactionDetail::with(['transaction.customer', 'assetUnit.assetType.category'])
                ->whereHas('transaction', function ($q) use ($id) {
                    return $q->where('customer_id', $id);
                })
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Transaction retrieve successfully',
                'data'    => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
