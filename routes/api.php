<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Laravel\Sanctum\Http\Controllers\AuthorizedAccessTokenController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\KendaraanController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\SuratJalanController;
use App\Http\Controllers\Api\DepoController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\BarangSalesController;
use App\Http\Controllers\Api\PengirimanController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\BarangBonusController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\BarangKemasanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Route::post('/sanctum/csrf-cookie', CsrfCookieController::class);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::match(['get', 'post'], '/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('login');
Route::match(['get', 'post'], '/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->name('logout');
Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value])
    ->match(['get', 'post'], '/refresh-token', [\App\Http\Controllers\Api\AuthController::class, 'refreshToken'])
    ->name('refresh-token');

Route::middleware(['auth:sanctum'])->group(function () {
    // Users
    
    // Depo
    Route::middleware(['role:depo|super admin'])->group(function () {
        Route::apiResource('/users', UserController::class);
        Route::apiResource('/sales-products', BarangSalesController::class);
        Route::apiResource('/customers', CustomerController::class);
        Route::apiResource('/stores', StoreController::class);
        Route::apiResource('/transactions', TransaksiController::class);
        Route::apiResource('/product-packages', BarangKemasanController::class);

          // Other routes accessible by all roles
    Route::apiResource('/transports', KendaraanController::class);
    Route::apiResource('/customers', CustomerController::class);
    Route::apiResource('/inventories', GudangController::class);
    Route::apiResource('/gnr', SuratJalanController::class);
    Route::apiResource('/depos', DepoController::class);
    Route::apiResource('/transactions', TransaksiController::class);
    Route::apiResource('/stores', StoreController::class);
    Route::apiResource('/distributions', PengirimanController::class);
    Route::apiResource('/drivers', DriverController::class);
    Route::apiResource('/bonus-products', BarangBonusController::class);
    Route::apiResource('/products', BarangController::class);
    });

    // Sales
    Route::middleware(['role:sales mobilris|sales motoris|depo|super admin'])->group(function () {
        Route::apiResource('/sales', SalesController::class);
        Route::apiResource('/sales-products', BarangSalesController::class);
        Route::apiResource('/customers', CustomerController::class);
        Route::apiResource('/stores', StoreController::class);
        Route::apiResource('/transactions', TransaksiController::class);
    });

    // Sales TO
    Route::middleware(['role:sales TO|depo|super admin'])->group(function () {
        Route::apiResource('/products', BarangController::class);
    });

  
});

// Route::post('login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
