<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\RegionalController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['guest']], function () {
    Route::get('/', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticated'])->name('login.process');
});

Route::group(['middleware' => ['auth']], function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    Route::get('/dashboard', function () {
        $totalItems = \App\Models\AssetUnit::count();
        $totalIn = \App\Models\AssetUnit::where('status', \App\Models\AssetUnit::STATUS_IN_STOCK)->count();
        $totalOut = \App\Models\AssetUnit::where('status', \App\Models\AssetUnit::STATUS_DEPLOYED)->count();
        $totalBroken = \App\Models\AssetUnit::where('status', \App\Models\AssetUnit::STATUS_FAULTY)->count();
        $totalRented = \App\Models\AssetUnit::whereIn('ownership_status', ['rented_to_customer', 'rented_company'])->count();
        $totalCompanyOwned = \App\Models\AssetUnit::where('ownership_status', 'company_owned')->count();
        $totalSold = \App\Models\AssetUnit::where('ownership_status', 'sold_to_customer')->count();

        return view('components.dashboard', compact(
            'totalItems', 'totalIn', 'totalOut', 'totalBroken', 'totalRented', 'totalCompanyOwned', 'totalSold'
        ));
    })->name('dashboard');

    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee/edit/{id}', [EmployeeController::class, 'show'])->name('employee.show');
    Route::put('/employee/edit/{id}', [EmployeeController::class, 'update'])->name('employee.update');
    Route::delete('/employee/delete/{id}', [EmployeeController::class, 'destroy'])->name('employee.delete');
    Route::post('/employee/datatable', [EmployeeController::class, 'datatable'])->name('employee.datatable');

    Route::get('/regional', [RegionalController::class, 'index'])->name('regional');
    Route::post('/regional', [RegionalController::class, 'store'])->name('regional.store');
    Route::get('/regional/edit/{id}', [RegionalController::class, 'show'])->name('regional.show');
    Route::put('/regional/edit/{id}', [RegionalController::class, 'update'])->name('regional.update');
    Route::delete('/regional/delete/{id}', [RegionalController::class, 'destroy'])->name('regional.delete');
    Route::post('/regional/datatable', [RegionalController::class, 'datatable'])->name('regional.datatable');

    Route::get('/company', [CompanyController::class, 'index'])->name('company');
    Route::post('/company', [CompanyController::class, 'store'])->name('company.store');
    Route::get('/company/edit/{id}', [CompanyController::class, 'show'])->name('company.show');
    Route::put('/company/edit/{id}', [CompanyController::class, 'update'])->name('company.update');
    Route::delete('/company/delete/{id}', [CompanyController::class, 'destroy'])->name('company.delete');
    Route::post('/company/datatable', [CompanyController::class, 'datatable'])->name('company.datatable');

    Route::get('/division', [DivisionController::class, 'index'])->name('division');
    Route::post('/division', [DivisionController::class, 'store'])->name('division.store');
    Route::get('/division/edit/{id}', [DivisionController::class, 'show'])->name('division.show');
    Route::put('/division/edit/{id}', [DivisionController::class, 'update'])->name('division.update');
    Route::delete('/division/delete/{id}', [DivisionController::class, 'destroy'])->name('division.delete');
    Route::post('/division/datatable', [DivisionController::class, 'datatable'])->name('division.datatable');


    Route::get('/category', [CategoryController::class, 'index'])->name('category');
    Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
    Route::get('/category/edit/{id}', [CategoryController::class, 'show'])->name('category.show');
    Route::put('/category/edit/{id}', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/category/delete/{id}', [CategoryController::class, 'destroy'])->name('category.delete');
    Route::post('/category/datatable', [CategoryController::class, 'datatable'])->name('category.datatable');

    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
    Route::get('/supplier/edit/{id}', [SupplierController::class, 'show'])->name('supplier.show');
    Route::put('/supplier/edit/{id}', [SupplierController::class, 'update'])->name('supplier.update');
    Route::delete('/supplier/delete/{id}', [SupplierController::class, 'destroy'])->name('supplier.delete');
    Route::post('/supplier/datatable', [SupplierController::class, 'datatable'])->name('supplier.datatable');

    Route::get('/asset', [AssetController::class, 'index'])->name('asset');
    Route::post('/asset', [AssetController::class, 'store'])->name('asset.store');
    Route::get('/asset/edit/{id}', [AssetController::class, 'show'])->name('asset.show');
    Route::put('/asset/edit/{id}', [AssetController::class, 'update'])->name('asset.update');
    Route::delete('/asset/delete/{id}', [AssetController::class, 'destroy'])->name('asset.delete');
    Route::post('/asset/datatable', [AssetController::class, 'datatable'])->name('asset.datatable');

    Route::get('/transaction', [TransactionController::class, 'index'])->name('transaction');
    Route::get('/transaction/create', [TransactionController::class, 'create'])->name('transaction.create');
    Route::get('/transaction/detail/{id}', [TransactionController::class, 'show'])->name('transaction.detail');
    Route::get('/transaction/pdf/{id}', [TransactionController::class, 'exportPDF'])->name('transaction.pdf');
    Route::post('/transaction/store', [TransactionController::class, 'store'])->name('transaction.store');
    Route::post('/transaction/datatable', [TransactionController::class, 'datatable'])->name('transaction.datatable');
    Route::delete('/transaction/delete/{id}', [TransactionController::class, 'destroy'])->name('transaction.delete');

    Route::get('/monitoring/asset', [MonitorController::class, 'asset'])->name('monitor.asset');
    Route::post('/monitoring/asset/datatable', [MonitorController::class, 'assetDatatable'])->name('monitor.asset.datatable');
    Route::get('/monitoring/asset/detail/{id}', [MonitorController::class, 'assetTransaction'])->name('monitor.asset.detail');

    Route::get('/monitoring/employee', [MonitorController::class, 'employee'])->name('monitor.employee');
    Route::post('/monitoring/employee/datatable', [MonitorController::class, 'employeeDatatable'])->name('monitor.employee.datatable');
    Route::get('/monitoring/employee/detail/{id}', [MonitorController::class, 'employeeTransaction'])->name('monitor.employee.detail');

    Route::get('/monitoring/company', [MonitorController::class, 'company'])->name('monitor.company');
    Route::post('/monitoring/company/datatable', [MonitorController::class, 'companyDatatable'])->name('monitor.company.datatable');
    Route::get('/monitoring/company/detail/{id}', [MonitorController::class, 'companyTransaction'])->name('monitor.company.detail');
});


// Select2
Route::get('/select/category', [SelectController::class, 'category'])->name('select.category');
Route::get('/select/supplier', [SelectController::class, 'supplier'])->name('select.supplier');
Route::get('/select/employee', [SelectController::class, 'employee'])->name('select.employee');
Route::get('/select/division', [SelectController::class, 'division'])->name('select.division');
Route::get('/select/asset', [SelectController::class, 'asset'])->name('select.asset');
Route::get('/select/asset/{id}', [SelectController::class, 'assetById'])->name('select.asset.id');
Route::get('/select/customer', [SelectController::class, 'customer'])->name('select.customer');
Route::get('/select/staff', [SelectController::class, 'staff'])->name('select.staff');
Route::get('/select/customer-assets', [SelectController::class, 'getAssetsByCustomer'])->name('select.customer.assets');
