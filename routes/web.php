<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HookahController; 
use App\Http\Controllers\ProductCategoryController; 
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TableController; 

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('hookahs', HookahController::class);
    Route::resource('product_categories', ProductCategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('clients', ClientController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('purchases', PurchaseController::class)->except(['index', 'show']);
    Route::resource('sales', SaleController::class)->except(['show']);
    Route::resource('tables', TableController::class)->except(['show']);
});

require __DIR__.'/auth.php';
