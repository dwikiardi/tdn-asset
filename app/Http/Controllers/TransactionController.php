<?php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\AssetUnit;
use App\Services\TridatuNetmonService;
use Illuminate\Http\Request;
use App\Models\TransactionDetail;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(protected TridatuNetmonService $tridatu) {}

    public function index()
    {
        return view('components.transaction');
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'out'); // Pasang (out) atau Cabut (in)
        return view('components.create-transaction', compact('type'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $transactions = Transaction::with('createdBy', 'customer', 'details.assetUnit.assetType');
            
            return DataTables::eloquent($transactions)
                ->addIndexColumn()
                ->addColumn('_status', function ($row) {
                    if ($row->type == 'stock_in') {
                        return '<span class="badge bg-success">IN</span>';
                    } else {
                        return '<span class="badge bg-danger">OUT</span>';
                    }
                })
                ->addColumn('_asset', function ($row) {
                    return $row->details->map(function($d) {
                        $assetName = $d->assetUnit->assetType->name ?? 'Unknown';
                        $identifier = $d->assetUnit->serial_number ?? $d->assetUnit->uid ?? '-';
                        $qty = $d->assetUnit->quantity ?? 1;
                        $uom = $d->assetUnit->assetType->uom ?? 'pcs';
                        
                        return '<div class="mb-1"><span class="badge badge-soft-dark">' . $assetName . '</span> ' . 
                               '<span class="badge badge-soft-info">' . $qty . ' ' . $uom . '</span> ' .
                               '<small class="text-muted">(' . $identifier . ')</small></div>';
                    })->implode('');
                })
                ->addColumn('technician', function($row) {
                    return $row->tridatu_user_name ?? '-';
                })
                ->addColumn('log_user', function($row) {
                    return $row->createdBy->name ?? '-';
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                    <a href="#" class="btn btn-sm btn-soft-warning btn-view" data-id="' . $row->id . '"><i class="mdi mdi-eye-outline mdi-18px"></i></a>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Print PDF">
                                    <a href="' . route('transaction.pdf', $row->transaction_number) . '" class="btn btn-sm btn-soft-primary"><i class="mdi mdi-file-pdf-box mdi-18px"></i></a>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <button data-id="' . $row->id . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline mdi-18px"></i></button>
                                </li>
                            </ul>';
                })
                ->rawColumns(['action', '_status', '_asset'])
                ->make(true);
        }
    }

    public function show($id)
    {
        $transaction = Transaction::with('createdBy', 'customer', 'details.assetUnit.assetType.category')->find($id);

        if (is_null($transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ]);
        }

        // Add technician name for compatibility with view if needed
        $transaction->employee_name = $transaction->tridatu_user_name;
        $transaction->customer_name = $transaction->customer->name ?? '-';

        return response()->json([
            'success' => true,
            'message' => 'Transaction retrieved successfully',
            'data' => $transaction
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required', // Technician ID from Tridatu
            'division_id' => 'required', // CID from Tridatu
            'status'      => 'required', // 0=IN, 1=OUT
            'uid.*'       => 'required', // Asset unit IDs
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'data'    => ['error' => $validator->errors()]
            ]);
        }

        // Note: uid in form refers to the ID of the Asset (Type) in current old UI, 
        // but for transactions we need AssetUnit IDs. 
        // However, the current create-transaction.blade.php select2-asset returns Asset ID.
        // We need to map this carefully. 
        // If it's a "Stock Out", we need to find an available AssetUnit for that Type.
        // For now, let's keep it simple and assume the UI select return AssetUnit ID (uid column in DB).

        try {
            if (empty($request->uid) || !is_array($request->uid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal: Belum ada asset yang dipilih!',
                ]);
            }

            DB::beginTransaction();
            
            $type = ($request->status == 0) ? Transaction::TYPE_STOCK_IN : Transaction::TYPE_STOCK_OUT;
            
            // Ambil nama teknisi dari service
            $technicianName = $this->tridatu->getStaffName($request->employee_id, 'Unknown Staff');

            // Find or create customer if CID doesn't exist locally
            $customer = Customer::where('external_id', $request->division_id)->first();
            
            // Jika customer tidak ada, atau namanya masih placeholder "Guest Customer", coba tarik data terbaru
            if (!$customer || str_starts_with($customer->name, 'Guest Customer')) {
                $customerData = $this->tridatu->getCustomerByExternalId($request->division_id);
                $newName = $customerData['nama'] ?? ($customer->name ?? 'Guest Customer (' . $request->division_id . ')');
                
                if (!$customer) {
                    $customer = Customer::create([
                        'external_id' => $request->division_id,
                        'name'        => $newName,
                        'external_source' => 'tridatu_netmon',
                    ]);
                } else {
                    $customer->update(['name' => $newName]);
                }
            }

            $transaction = Transaction::create([
                'transaction_number' => Transaction::generateNumber($type),
                'type'               => $type,
                'contract_type'      => $request->contract_type,
                'contract_start_date'=> $request->contract_start_date,
                'contract_end_date'  => $request->contract_end_date,
                'customer_id'        => $customer->id,
                'tridatu_user_id'    => $request->employee_id,
                'tridatu_user_name'  => $technicianName,
                'created_by'         => Auth::id(),
                'status'             => Transaction::STATUS_COMPLETED,
                'transaction_date'   => now(),
                'notes'              => $request->note,
            ]);

            if (!empty($request->uid) && is_array($request->uid)) {
                foreach ($request->uid as $key => $assetUnitId) {
                    $requestedQty = $request->qty[$key] ?? 1;
                    
                    // Update asset unit status
                    $unit = AssetUnit::find($assetUnitId);
                    if ($unit) {
                        // VALIDASI: Mencegah barang keluar melebihi stok
                        if ($request->status == 1 && $unit->quantity < $requestedQty && $unit->status == AssetUnit::STATUS_IN_STOCK) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Gagal: Stok asset ' . ($unit->serial_number ?: $unit->id) . ' tidak mencukupi! (Sisa: ' . $unit->quantity . ')',
                            ]);
                        }

                        $ownershipStatus = $unit->ownership_status;
                        $targetStatus = ($request->status == 0) ? AssetUnit::STATUS_IN_STOCK : AssetUnit::STATUS_DEPLOYED;
                        $targetCustomerId = ($request->status == 1) ? $customer->id : null;

                        // SMART LOGIC: Jika barang ditarik (Rusak/Service/Dibuang)
                        $itemAction = $request->action_type[$key] ?? 'normal';
                        if ($itemAction == 'rusak') {
                            $targetStatus = AssetUnit::STATUS_FAULTY;
                            $targetCustomerId = null;
                            $ownershipStatus = 'company_owned';
                        } elseif ($itemAction == 'dibuang') {
                            $targetStatus = AssetUnit::STATUS_PULLED; // Status Pulled untuk barang yang tidak aktif di gudang (dibuang)
                            $targetCustomerId = null;
                            $ownershipStatus = 'company_owned';
                        } elseif ($itemAction == 'cabut' || ($request->status == 0 && $unit->status == AssetUnit::STATUS_DEPLOYED)) {
                            $targetStatus = AssetUnit::STATUS_IN_STOCK;
                            $targetCustomerId = null;
                            $ownershipStatus = 'company_owned';
                        }

                        if ($request->status == 1 && $itemAction == 'normal') { // OUT / Deployed
                            if ($request->contract_type == 'beli_putus') {
                                $ownershipStatus = 'sold_to_customer';
                            } elseif ($request->contract_type == 'sewa' || $request->contract_type == 'cicil') {
                                $ownershipStatus = 'rented_to_customer';
                            } elseif ($request->contract_type == 'pinjam') {
                                $ownershipStatus = 'company_owned';
                            }
                        }

                        // SMART SPLIT LOGIC: Untuk barang bulk (Kabel/Meteran)
                        // Jika quantity yang diproses kurang dari total stok di record ini
                        if ($unit->quantity > $requestedQty) {
                            // Jika Stock Out (Pemasangan): Record asal (Stock) dikurangi, record baru (Deployed) dibuat
                            if ($request->status == 1 && $unit->status == AssetUnit::STATUS_IN_STOCK) {
                                $unit->decrement('quantity', $requestedQty);
                                
                                $newUnit = $unit->replicate();
                                $newUnit->quantity = $requestedQty;
                                $newUnit->status = $targetStatus;
                                $newUnit->customer_id = $targetCustomerId;
                                $newUnit->ownership_status = $ownershipStatus;
                                // Tambah suffix pada SN jika ada, agar tidak duplikat
                                if ($unit->serial_number) {
                                    $newUnit->serial_number = $unit->serial_number . '-CUST-' . ($request->division_id ?? rand(100, 999)) . '-' . rand(1000, 9999);
                                }
                                $newUnit->save();
                                $assetUnitId = $newUnit->id;
                            } 
                            // Jika Stock In (Penarikan Sebagian): Record asal (Deployed) dikurangi, record baru (Stock) dibuat
                            elseif ($request->status == 0 && $unit->status == AssetUnit::STATUS_DEPLOYED) {
                                $unit->decrement('quantity', $requestedQty);
                                
                                $newUnit = $unit->replicate();
                                $newUnit->quantity = $requestedQty;
                                $newUnit->status = AssetUnit::STATUS_IN_STOCK;
                                $newUnit->customer_id = null;
                                $newUnit->ownership_status = 'company_owned';
                                if ($unit->serial_number) {
                                    $newUnit->serial_number = $unit->serial_number . '-IN-' . date('ymd') . '-' . rand(1000, 9999);
                                }
                                $newUnit->save();
                                $assetUnitId = $newUnit->id;
                            }
                            else {
                                $updateData = [
                                    'status'           => $targetStatus,
                                    'customer_id'      => $targetCustomerId,
                                    'ownership_status' => $ownershipStatus,
                                    'quantity'         => $requestedQty,
                                ];
                                
                                // Jika ini penarikan dan barang ini sebelumnya hasil pecahan CUST
                                if ($request->status == 0 && $unit->serial_number && strpos($unit->serial_number, '-CUST-') !== false) {
                                    $parts = explode('-CUST-', $unit->serial_number);
                                    $baseSN = $parts[0];
                                    $updateData['serial_number'] = $baseSN . '-IN-' . date('ymd') . '-' . rand(1000, 9999);
                                } 
                                // Jika ini update normal tapi status deploy dan SN belum ada suffix, tambahkan suffix
                                elseif ($request->status == 1 && $unit->serial_number && strpos($unit->serial_number, '-CUST-') === false) {
                                    $updateData['serial_number'] = $unit->serial_number . '-CUST-' . ($request->division_id ?? rand(100, 999)) . '-' . rand(1000, 9999);
                                }

                                $unit->update($updateData);
                            }
                        } else {
                            // Update unit secara utuh jika quantity pas atau lebih
                            $updateData = [
                                'status'           => $targetStatus,
                                'customer_id'      => $targetCustomerId,
                                'ownership_status' => $ownershipStatus,
                                'quantity'         => $requestedQty,
                            ];
                            
                            // Jika ini penarikan dan barang ini sebelumnya hasil pecahan CUST
                            if ($request->status == 0 && $unit->serial_number && strpos($unit->serial_number, '-CUST-') !== false) {
                                // Ganti SN agar jadi IN
                                // Pisahkan SN asli
                                $parts = explode('-CUST-', $unit->serial_number);
                                $baseSN = $parts[0];
                                $updateData['serial_number'] = $baseSN . '-IN-' . date('ymd') . '-' . rand(1000, 9999);
                            } 
                            // Jika ini update normal tapi status deploy dan SN belum ada suffix, tambahkan suffix. Atau jika sudah ada suffix tapi untuk CUST lain
                            elseif ($request->status == 1 && $unit->serial_number && strpos($unit->serial_number, '-CUST-') === false) {
                                $updateData['serial_number'] = $unit->serial_number . '-CUST-' . ($request->division_id ?? rand(100, 999)) . '-' . rand(1000, 9999);
                            }

                            $unit->update($updateData);
                        }

                        TransactionDetail::create([
                            'transaction_id' => $transaction->id,
                            'asset_unit_id'  => $assetUnitId,
                            'notes'          => ($itemAction != 'normal' ? '['.strtoupper($itemAction).'] ' : '') . 'Stock ' . (($request->status == 0) ? 'In' : 'Out') . ' (Qty: '.$requestedQty.')',
                        ]);
                    }
                }
            }
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $transaction = Transaction::find($id);

        if (is_null($transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ]);
        }

        try {
            $details = TransactionDetail::where("transaction_id", $id)->get();

            foreach ($details as $detail) {
                // Rollback status
                // Jika ini adalah penarikan (Cabut/Rusak/Dibuang), kembalikkan ke Deployed
                $status = ($transaction->type == 'stock_out') ? AssetUnit::STATUS_IN_STOCK : AssetUnit::STATUS_DEPLOYED;
                if (str_contains($detail->notes, '[CABUT]') || str_contains($detail->notes, '[RUSAK]') || str_contains($detail->notes, '[DIBUANG]')) {
                    // If the original transaction was a "pull" (IN transaction for a deployed item),
                    // rolling it back means the item should return to DEPLOYED state.
                    $status = AssetUnit::STATUS_DEPLOYED;
                }

                AssetUnit::where('id', $detail->asset_unit_id)->update([
                    'status' => $status,
                    'customer_id' => ($status == AssetUnit::STATUS_DEPLOYED) ? $transaction->customer_id : null
                ]);
            }

            $transaction->delete();
            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function exportPDF($transaction_number)
    {
        $transaction = Transaction::where('transaction_number', $transaction_number)->with('details.assetUnit.assetType.category', 'customer')->first();

        if (!$transaction) abort(404);

        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');
        $transaction->date = $transaction->created_at->isoFormat('dddd, D MMMM Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.receipt', ['transaction' => $transaction]);
        return $pdf->stream();
    }
}
