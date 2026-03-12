<?php

namespace App\Http\Controllers;

use DNS2D;
use App\Models\Asset;
use App\Models\AssetUnit;
use App\Models\AssetImages;
use App\Models\Category;
use App\Models\Supplier;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use Image;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    public function index()
    {
        // echo DNS2D::getBarcodeHTML('https://psikotesdaring.com', 'QRCODE');
        // echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG('454541255', 'I25+', 2, 40, array(1, 1, 1), true) . '" alt="barcode"   />';
        // die;
        $categories = Category::all();
        $suppliers = Supplier::all();
        $brands = Asset::select('brand')->whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand');

        return view('components.asset', compact('categories', 'suppliers', 'brands'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            // $store = Store::where('user_id', auth()->user()->id)->first();
            $assets = Asset::with('category', 'supplier', 'image', 'units');

            if ($request->filled('filter_category_id')) {
                $assets->where('category_id', $request->filter_category_id);
            }

            if ($request->filled('filter_brand')) {
                $assets->where('brand', $request->filter_brand);
            }

            // dd($order);
            return DataTables::eloquent($assets)
                ->addIndexColumn()
                ->addColumn('_status', function ($row) {
                    $inStockQty = $row->units->where('status', \App\Models\AssetUnit::STATUS_IN_STOCK)->sum('quantity');
                    if ($inStockQty > 0) {
                        return '<span class="badge bg-success">Standby</span>';
                    } else {
                        return '<span class="badge bg-danger">Not Standby</span>';
                    }
                })
                ->addColumn('_barcode', function ($row) {
                    return '<img class="img-fluid" src="data:image/png;base64,' . DNS1D::getBarcodePNG($row->uid, 'C128', 2, 40, array(1, 1, 1), true) . '" alt="barcode"   />';
                })
                ->addColumn('_images', function ($row) {
                    $html = '';
                    foreach ($row->image as $key => $image) {
                        $html .= '  <div class="avatar-group-item">
                                        <a href="javascript: void(0);" class="d-inline-block image-asset">
                                            <img src="' . asset('images/assets/' . $image->name) . '" alt="" class="rounded-circle avatar-xs">
                                        </a>
                                    </div>';
                    }

                    return '<div class="avatar-group">' . $html . '</div>';
                })
                ->addColumn('_quantity', function ($row) {
                    $qty = $row->units->where('status', \App\Models\AssetUnit::STATUS_IN_STOCK)->sum('quantity');
                    return number_format($qty, 0, ',', '.') . ' ' . ($row->uom ?? 'pcs');
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                    <button class="btn btn-sm btn-soft-primary btn-view" data-id="' . $row->id . '"><i class="mdi mdi-eye-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                    <button class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->id . '"><i class="mdi mdi-pencil-outline mdi-18px"></i></button>
                                </li>
                                <li data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                    <button data-id="' . $row->id . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline mdi-18px"></i></button>
                                </li>
                            </ul>';
                })
                ->rawColumns(['action', '_status', '_barcode', '_images'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->merge([
            'purchase_price' => preg_replace('/[^0-9]/', '', $request->purchase_price),
            'production_year' => $request->production_year ? date('Y', strtotime($request->production_year)) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'category_id' => 'required',
            'supplier_id' => 'required',
            'specification' => 'required',
            'serial_number' => 'nullable|string|unique:asset_units,serial_number',
            'mac_address'   => 'nullable|string|unique:asset_units,mac_address',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric',
            'production_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 10),
            'quantity' => 'nullable|numeric|min:0',
            'uom' => 'nullable|string|max:20',
            'condition' => 'required',
            'foto1'     => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'foto2'     => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'foto3'     => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'foto4'     => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'foto5'     => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // dd($request);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Bad parameter!',
                'data' => [
                    'error' => $validator->errors()
                ]
            ]);
        }

        $category = $request->category_id;
        $supplier = $request->supplier_id;

        $cat = Category::where('id', $request->category_id)->count();
        // dd($cat);
        if ($cat == 0) {
            $inCategory = Category::create([
                'name' => $request->category_id
            ]);

            $category = $inCategory->id;
        }

        $sup = Supplier::where('id', $request->supplier_id)->count();
        // dd($supplier);
        if ($sup == 0) {
            $inSupplier = Supplier::create([
                'name' => $request->supplier_id
            ]);

            $supplier = $inSupplier->id;
        }

        $foto1 = $request->file('foto1');
        $foto2 = $request->file('foto2');
        $foto3 = $request->file('foto3');
        $foto4 = $request->file('foto4');
        $foto5 = $request->file('foto5');

        $images = [];

        if ($foto1) {
            $images[1] = time() . '1.' . $foto1->extension();

            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto1->path());
            $img->resize(480, 360, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $images[1]);
        }

        if ($foto2) {
            $images[2] = time() . '2.' . $foto2->extension();

            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto2->path());
            $img->resize(480, 360, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $images[2]);
        }

        if ($foto3) {
            $images[3] = time() . '3.' . $foto3->extension();

            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto3->path());
            $img->resize(480, 360, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $images[3]);
        }

        if ($foto4) {
            $images[4] = time() . '4.' . $foto4->extension();

            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto4->path());
            $img->resize(480, 360, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $images[4]);
        }

        if ($foto5) {
            $images[5] = time() . '5.' . $foto5->extension();

            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto5->path());
            $img->resize(480, 360, function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath . '/' . $images[5]);
        }

        // dd($images);
        try {
            $asset = Asset::create([
                'name'             => $request->name,
                'brand'            => $request->brand,
                'category_id'      => intval($category),
                'supplier_id'      => intval($supplier),
                'uid'              => $request->serial_number ?: $this->generateAssetUID($request->brand),
                'specification'    => $request->specification,
                'production_year'  => $request->production_year,
                'purchase_date'    => $request->purchase_date,
                'purchase_price'   => $request->purchase_price,
                'uom'              => $request->uom ?: 'pcs',
                'condition'        => $request->condition,
            ]);

            // Create the first physical unit
            AssetUnit::create([
                'asset_type_id' => $asset->id,
                'serial_number' => $request->serial_number ?: $asset->uid,
                'mac_address'   => $request->mac_address,
                'status'        => AssetUnit::STATUS_IN_STOCK,
                'quantity'      => $request->quantity ?: 1,
                'purchase_date' => $request->purchase_date,
                'purchase_price'=> $request->purchase_price,
                'condition_notes'=> $request->condition,
            ]);

            foreach ($images as $key => $image) {
                AssetImages::create([
                    'asset_id'  => $asset->id,
                    'name'      => $image
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Asset created successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $asset = Asset::with('image', 'category', 'supplier', 'units')->find($id);

        if (is_null($asset)) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
            ]);
        }

        // Add compatibility fields from first unit
        $firstUnit = $asset->units->first();
        if ($firstUnit) {
            $asset->serial_number = $firstUnit->serial_number;
            $asset->mac_address = $firstUnit->mac_address;
            $asset->quantity = $firstUnit->quantity;
        }

        $asset->barcode =
            '<img class="img-fluid" src="data:image/png;base64,' . DNS1D::getBarcodePNG($asset->uid, 'C128', 2, 40, array(1, 1, 1), true) . '" alt="barcode"   />';

        return response()->json([
            'success' => true,
            'message' => 'Asset retrieved successfully',
            'data' => $asset
        ]);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);

        $request->merge([
            'purchase_price' => preg_replace('/[^0-9]/', '', $request->purchase_price),
            'production_year' => $request->production_year ? date('Y', strtotime($request->production_year)) : null,
        ]);

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'category_id'   => 'required',
            'supplier_id'   => 'required',
            'specification' => 'required',
            'serial_number' => 'nullable|string|unique:asset_units,serial_number,' . $id . ',asset_type_id',
            'mac_address'   => 'nullable|string|unique:asset_units,mac_address,' . $id . ',asset_type_id',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric',
            'production_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 10),
            'quantity' => 'nullable|numeric|min:0',
            'uom' => 'nullable|string|max:20',
            'condition'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Bad parameter!',
                'data' => [
                    'error' => $validator->errors()
                ]
            ]);
        }

        // ... [Old image handling logic remains] ...
        $foto1 = $request->file('foto1');
        $foto2 = $request->file('foto2');
        $foto3 = $request->file('foto3');
        $foto4 = $request->file('foto4');
        $foto5 = $request->file('foto5');
        $images = [];

        if ($foto1) {
            $images[1] = time() . '1.' . $foto1->extension();
            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto1->path());
            $img->resize(480, 360, function ($constraint) { $constraint->aspectRatio(); })->save($destinationPath . '/' . $images[1]);
            if(file_exists('images/assets/' . $request->foto1_old)) unlink('images/assets/' . $request->foto1_old);
            AssetImages::where('name', $request->foto1_old)->delete();
        }
        if ($foto2) {
            $images[2] = time() . '2.' . $foto2->extension();
            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto2->path());
            $img->resize(480, 360, function ($constraint) { $constraint->aspectRatio(); })->save($destinationPath . '/' . $images[2]);
            if(file_exists('images/assets/' . $request->foto2_old)) unlink('images/assets/' . $request->foto2_old);
            AssetImages::where('name', $request->foto2_old)->delete();
        }
        if ($foto3) {
            $images[3] = time() . '3.' . $foto3->extension();
            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto3->path());
            $img->resize(480, 360, function ($constraint) { $constraint->aspectRatio(); })->save($destinationPath . '/' . $images[3]);
            if(file_exists('images/assets/' . $request->foto3_old)) unlink('images/assets/' . $request->foto3_old);
            AssetImages::where('name', $request->foto3_old)->delete();
        }
        if ($foto4) {
            $images[4] = time() . '4.' . $foto4->extension();
            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto4->path());
            $img->resize(480, 360, function ($constraint) { $constraint->aspectRatio(); })->save($destinationPath . '/' . $images[4]);
            if(file_exists('images/assets/' . $request->foto4_old)) unlink('images/assets/' . $request->foto4_old);
            AssetImages::where('name', $request->foto4_old)->delete();
        }
        if ($foto5) {
            $images[5] = time() . '5.' . $foto5->extension();
            $destinationPath = public_path('/images/assets');
            $img = Image::make($foto5->path());
            $img->resize(480, 360, function ($constraint) { $constraint->aspectRatio(); })->save($destinationPath . '/' . $images[5]);
            if(file_exists('images/assets/' . $request->foto5_old)) unlink('images/assets/' . $request->foto5_old);
            AssetImages::where('name', $request->foto5_old)->delete();
        }

        try {
            $asset->update([
                'name'             => $request->name,
                'brand'            => $request->brand,
                'category_id'      => $request->category_id,
                'supplier_id'      => $request->supplier_id,
                'specification'    => $request->specification,
                'production_year'  => $request->production_year,
                'purchase_date'    => $request->purchase_date,
                'purchase_price'   => $request->purchase_price,
                'uom'              => $request->uom ?: 'pcs',
                'condition'        => $request->condition,
                'uid'              => $request->serial_number,
            ]);

            // Sync the physical unit
            $unit = AssetUnit::where('asset_type_id', $asset->id)->first();
            if ($unit) {
                $unit->update([
                    'serial_number' => $request->serial_number ?: $asset->uid,
                    'mac_address'   => $request->mac_address,
                    'quantity'      => $request->quantity ?: 1,
                    'purchase_date' => $request->purchase_date,
                    'purchase_price'=> $request->purchase_price,
                    'condition_notes'=> $request->condition,
                ]);
            }

            foreach ($images as $key => $image) {
                AssetImages::create([
                    'asset_id'  => $asset->id,
                    'name'      => $image
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Asset updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $asset = Asset::find($id);

        if (is_null($asset)) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not found',
            ]);
        }

        try {
            foreach ($asset->Image as $key => $image) {
                unlink('images/assets/' . $image->name);
            }

            $asset->delete();
            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function generateAssetUID($brand)
    {
        $prefix = 'TDN-';
        $brandCode = $brand ? strtoupper(substr($brand, 0, 2)) : 'XX';
        $search = $prefix . $brandCode;

        $lastAsset = Asset::where('uid', 'like', $search . '%')
            ->orderBy('uid', 'desc')
            ->first();

        if ($lastAsset) {
            $lastUID = $lastAsset->uid;
            // Extract the numeric part at the end
            preg_match('/(\d+)$/', $lastUID, $matches);
            if ($matches) {
                $lastNum = intval($matches[0]);
                $nextNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNum = '001';
            }
        } else {
            $nextNum = '001';
        }

        return $search . $nextNum;
    }
}
