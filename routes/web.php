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
use App\Http\Controllers\BonusHistoryController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\OperationHistoryController;
use App\Http\Controllers\StatisticsController;

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

    Route::get('/clients/export-excel', [ClientController::class, 'exportExcel'])
        ->name('clients.export-excel');

    Route::resource('clients', ClientController::class);
        Route::post('/clients/{client}/add-bonus', [ClientController::class, 'addBonus'])
        ->name('clients.add-bonus')
        ->middleware('auth');
        
    Route::post('/clients/{client}/subtract-bonus', [ClientController::class, 'subtractBonus'])
        ->name('clients.subtract-bonus')
        ->middleware('auth');
        
    Route::get('/clients/{client}/bonus-history', [BonusHistoryController::class, 'index'])
        ->name('clients.bonus-history')
        ->middleware('auth');
    Route::get('/clients/{client}/total-spent', [SaleController::class, 'getClientTotalSpent']);

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
    Route::post('/sales/{sale}/check-stock', [SaleController::class, 'checkStock'])->name('sales.check-stock');

    Route::resource('tables', TableController::class)->except(['show']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
    Route::post('tables/{table}/change-status', [TableController::class, 'changeStatus'])->name('tables.change-status');
     Route::resource('recipe-items', RecipeItemController::class);

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
    Route::post('/tables/{table}/products/{item}/update-price', [TableController::class, 'updateProductPrice'])
        ->name('tables.products.update-price');

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
    Route::post('/shifts/{shift}/note', [ShiftController::class, 'updateNote'])->name('shifts.update-note');
    Route::post('/shifts/manage-or-create', [ShiftController::class, 'manageOrCreate'])
        ->name('shifts.manage-or-create');

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
    Route::get('expenditures/{expenditure}/confirm-delete', [ExpenditureController::class, 'confirmDelete'])->name('expenditures.confirm-delete');
    Route::get('expenditures/{expenditure}/history', [ExpenditureController::class, 'history'])->name('expenditures.history');
    Route::post('expenditures/{expenditure}/quick-delete', [ExpenditureController::class, 'quickDestroy'])->name('expenditures.quick-destroy');

    // Основные маршруты (если еще нет)
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
        
        // НОВЫЕ РОУТЫ ДЛЯ ЗАРПЛАТЫ:
        
        // Отчет по зарплате
        Route::get('/salary-report', [AccountingController::class, 'salaryReport'])->name('salary-report');
        
        // Экспорт отчета по зарплате
        Route::get('/export-salary-report', [AccountingController::class, 'exportSalaryReport'])->name('export-salary-report');
        
        // Детальный отчет по себестоимости (уже есть в контроллере, но нет роута)
        Route::get('/cost-report', [AccountingController::class, 'costReport'])->name('cost-report');
        
        // API для получения статистики по способам оплаты (уже есть в контроллере, но нет роута)
        Route::get('/payment-methods-stats', [AccountingController::class, 'getPaymentMethodsStats'])->name('payment-methods-stats');
    });

    // История бонусов - общий доступ для всех авторизованных
    Route::get('/bonus-history', [BonusHistoryController::class, 'index'])
        ->name('bonus-history.index')
        ->middleware('auth');

    // Payment Methods Routes
    Route::resource('payment-methods', PaymentMethodController::class);

    Route::get('operation-history', [OperationHistoryController::class, 'index'])->name('operation-history.index');
    Route::get('operation-history/{operationHistory}', [OperationHistoryController::class, 'show'])->name('operation-history.show');
    
    // Статистика
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
    Route::get('/statistics/accounting', [StatisticsController::class, 'accounting'])->name('statistics.accounting');

    // API маршруты для статистики
    Route::get('/statistics/visit-dynamics', [StatisticsController::class, 'visitDynamics']);
    Route::get('/statistics/popular-tables', [StatisticsController::class, 'popularTables']);
    Route::get('/statistics/popular-hours', [StatisticsController::class, 'popularHours']);
    Route::get('/statistics/popular-weekdays', [StatisticsController::class, 'popularWeekdays']);
    Route::get('/statistics/payment-methods', [StatisticsController::class, 'paymentMethods']);
    Route::get('/statistics/summary', [StatisticsController::class, 'summary']);

    // Новые API маршруты для финансовой статистики
    Route::get('/statistics/revenue-profit', [StatisticsController::class, 'revenueProfitStats']);
    Route::get('/statistics/average-check', [StatisticsController::class, 'averageCheckStats']);
    Route::get('/statistics/expenses', [StatisticsController::class, 'expensesStats']);

    Route::get('/statistics/hookah', [StatisticsController::class, 'hookahPage'])->name('statistics.hookah');
    Route::get('/statistics/hookah/data', [StatisticsController::class, 'hookahStatistics'])->name('statistics.hookah.data');

    Route::get('/statistics/products', [StatisticsController::class, 'productsPage'])->name('statistics.products');
    Route::get('/statistics/products/data', [StatisticsController::class, 'productsStatistics'])->name('statistics.products.data');

    Route::get('/statistics/expenses/data', [StatisticsController::class, 'expensesStatistics'])->name('statistics.expenses.data');
    Route::get('/statistics/expenses', [StatisticsController::class, 'expensesPage'])->name('statistics.expenses');


    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('table-names')->name('table-names.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TableNameController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\TableNameController::class, 'store'])->name('store');
            Route::put('/{table}/status', [\App\Http\Controllers\Admin\TableNameController::class, 'updateStatus'])->name('update-status');
            Route::put('/update-order', [\App\Http\Controllers\Admin\TableNameController::class, 'updateOrder'])->name('update-order');
            Route::delete('/{table}', [\App\Http\Controllers\Admin\TableNameController::class, 'destroy'])->name('destroy');
        });
    });

});

require __DIR__.'/auth.php';