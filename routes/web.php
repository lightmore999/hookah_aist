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
use App\Http\Controllers\WriteOffController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\BonusCardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\ExpenditureTypeController;
use App\Http\Controllers\ExpenditureController;
use App\Http\Controllers\AccountingController;

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
    Route::prefix('products/{product}/components')->group(function () {
        Route::get('/', [ProductController::class, 'getComponents'])->name('products.components');
        Route::post('/add', [ProductController::class, 'addComponent'])->name('products.components.add');
        Route::delete('/{component}/remove', [ProductController::class, 'removeComponent'])->name('products.components.remove');
        Route::get('/available', [ProductController::class, 'getAvailableComponents'])->name('products.components.available');
    });
    Route::resource('clients', ClientController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('purchases', PurchaseController::class)->except(['index', 'show']);

    // Старые маршруты продаж (можно оставить для совместимости или удалить)
    Route::prefix('sales')->name('sales.')->group(function () {
        // Main sales routes
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::post('/', [SaleController::class, 'store'])->name('store'); 
        
        // Individual sale routes
        Route::prefix('{sale}')->group(function () {
            Route::get('/', [SaleController::class, 'show'])->name('show');
            Route::get('/edit', [SaleController::class, 'edit'])->name('edit');
            Route::put('/', [SaleController::class, 'update'])->name('update');
            Route::delete('/', [SaleController::class, 'destroy'])->name('destroy');
            
            // Complete sale
            Route::post('/complete', [SaleController::class, 'complete'])->name('complete');
            
            // Sale items routes
            Route::prefix('items')->name('items.')->group(function () {
                Route::post('/', [SaleController::class, 'addItem'])->name('store');
                Route::prefix('{item}')->group(function () {
                    Route::put('/', [SaleController::class, 'updateItem'])->name('update');
                    Route::delete('/', [SaleController::class, 'removeItem'])->name('destroy');
                });
            });
            
            // Hookah routes
            Route::prefix('hookahs')->name('hookahs.')->group(function () {
                Route::post('/', [SaleController::class, 'addHookah'])->name('store');
                Route::delete('/{hookah}', [SaleController::class, 'removeHookah'])->name('destroy');
            });
        });
    });

    Route::resource('tables', TableController::class)->except(['show']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    Route::post('tables/{table}/change-status', [TableController::class, 'changeStatus'])->name('tables.change-status');


    // НОВЫЕ маршруты для модальных окон столов
    Route::prefix('tables/{table}')->group(function () {
        // Модальные окна (если еще используются)
        Route::get('/sale-modal', [TableController::class, 'showSaleModal'])->name('tables.sale-modal');
        Route::get('/hookah-modal', [TableController::class, 'showHookahModal'])->name('tables.hookah-modal');
        Route::get('/close-modal', [TableController::class, 'showCloseModal'])->name('tables.close-modal');
        
        // Действия с товарами
        Route::post('/add-product', [TableController::class, 'addProductToSale'])->name('tables.add-product');
        Route::delete('/remove-product/{item}', [TableController::class, 'removeProductFromSale'])->name('tables.remove-product');
        Route::put('/update-quantity/{item}', [TableController::class, 'updateProductQuantity'])->name('tables.update-quantity');
        
        // Действия с кальянами
        Route::post('/add-hookah', [TableController::class, 'addHookahToSale'])->name('tables.add-hookah');
        Route::delete('/remove-hookah/{hookah}', [TableController::class, 'removeHookahFromSale'])->name('tables.remove-hookah');
        
        // Закрытие продажи и стола
        Route::post('/close-sale', [TableController::class, 'closeSaleAndTable'])->name('tables.close-sale');
        
        // НОВЫЕ AJAX маршруты для получения данных
        Route::get('/get-sale-items', [TableController::class, 'getSaleItems'])->name('tables.get-sale-items');
        Route::get('/get-sale-hookahs', [TableController::class, 'getSaleHookahs'])->name('tables.get-sale-hookahs');
        Route::get('/get-sale-data', [TableController::class, 'getSaleData'])->name('tables.get-sale-data');
    });

    Route::resource('write-offs', WriteOffController::class);
    Route::resource('employees', EmployeeController::class);

   // Смены
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::post('/', [ShiftController::class, 'store'])->name('store');
        Route::post('/generate-monthly', [ShiftController::class, 'generateMonthly'])->name('generate-monthly');
        
        // Действия с конкретной сменой
        Route::post('/{shift}/open', [ShiftController::class, 'open'])->name('open');
        Route::post('/{shift}/close', [ShiftController::class, 'close'])->name('close');
        
        // Старые методы управления сотрудниками
        Route::post('/{shift}/employees', [ShiftController::class, 'addEmployee'])->name('add-employee');
        Route::delete('/{shift}/employees/{employee}', [ShiftController::class, 'removeEmployee'])->name('remove-employee');
        Route::post('/{shift}/bulk-add-employees', [ShiftController::class, 'bulkAddEmployees'])->name('bulk-add-employees');
        Route::delete('/{shift}/clear-employees', [ShiftController::class, 'clearEmployees'])->name('clear-employees');
        
        // НОВЫЕ маршруты
        Route::put('/{shift}/update-employees', [ShiftController::class, 'updateEmployees'])->name('update-employees');
        Route::get('/{shift}/get-employees-data', [ShiftController::class, 'getEmployeesData'])->name('get-employees-data');
        Route::get('/current-shift', [ShiftController::class, 'getCurrentShift'])->name('shifts.current');
    });
    Route::get('/shifts/{shift}/json-data', [ShiftController::class, 'jsonData'])->name('shifts.json-data');


    Route::resource('bonus-cards', BonusCardController::class);
    Route::resource('fines', FineController::class);

    // Инвентаризация
    Route::resource('inventories', InventoryController::class);

    Route::prefix('inventories/{inventory}')->group(function () {
        // Закрытие инвентаризации
        Route::post('/close', [InventoryController::class, 'close'])->name('inventories.close');
        
        // Работа с товарами инвентаризации
        Route::prefix('items')->group(function () {
            Route::post('/', [InventoryController::class, 'addItem'])->name('inventories.items.store');
            Route::post('/multiple', [InventoryController::class, 'addMultipleItems'])->name('inventories.items.store-multiple');
            Route::put('/{item}', [InventoryController::class, 'updateItem'])->name('inventories.items.update');
            Route::delete('/{item}', [InventoryController::class, 'removeItem'])->name('inventories.items.destroy');
            Route::get('/', [InventoryController::class, 'getItems'])->name('inventories.items.index');
        });
        
        // Получение доступных товаров для добавления
        Route::get('/available-products', [InventoryController::class, 'getAvailableProducts'])->name('inventories.available-products');
    });

    Route::resource('expenditure-types', ExpenditureTypeController::class);
    Route::resource('expenditures', ExpenditureController::class);

    Route::prefix('accounting')->name('accounting.')->group(function () {
        // Главная страница бухгалтерии
        Route::get('/', [AccountingController::class, 'index'])->name('index');
        
        // Статистика по кальянам
        Route::get('/hookah-stats', [AccountingController::class, 'hookahStats'])->name('hookah-stats');
        
        // Статистика по способам оплаты
        Route::get('/payment-stats', [AccountingController::class, 'paymentStats'])->name('payment-stats');
        
        // Статистика по бонусам
        Route::get('/bonus-stats', [AccountingController::class, 'bonusStats'])->name('bonus-stats');
        
        // Экспорт данных
        Route::get('/export', [AccountingController::class, 'export'])->name('export');
    });

    
});

require __DIR__.'/auth.php';