<!-- tables/modals/sale-products.blade.php -->
<div class="modal fade" id="saleProductsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="saleProductsModalLabel">
                    <i class="bi bi-cart me-2"></i>Товары стола
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <!-- Информация -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info" id="saleProductsInfo">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Выберите стол</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Фильтры в две колонки -->
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="categoryFilterProducts" class="form-label fw-bold">Категория</label>
                        <select class="form-select form-select-sm" id="categoryFilterProducts">
                            <option value="all">Все категории</option>
                            @php
                                // Получаем категории из продуктов
                                $categories = $products->where('product_category_id', '!=', null)
                                    ->pluck('category')
                                    ->unique('id')
                                    ->filter()
                                    ->sortBy('name');
                            @endphp
                            
                            @foreach($categories as $category)
                                @if($category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endif
                            @endforeach
                            
                            {{-- Опция для товаров без категории --}}
                            @if($products->where('product_category_id', null)->count() > 0)
                                <option value="null">Без категории</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="searchTableProduct" class="form-label fw-bold">Поиск</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="searchTableProduct" 
                                   placeholder="Название товара...">
                            <button class="btn btn-outline-secondary" type="button" id="clearTableSearch">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Форма добавления товара -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3 text-muted">Добавить товар</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <select class="form-select" id="productSelect" size="4" style="height: auto;">
                                            <option value="">Выберите товар...</option>
                                            @foreach($products as $product)
                                            @php
                                                // Для столов обычно используется основной склад
                                                $warehouseId = \App\Models\Warehouse::first()->id ?? 1;
                                                $stock = \App\Models\Stock::where('warehouse_id', $warehouseId)
                                                    ->where('product_id', $product->id)
                                                    ->first();
                                                $available = $stock ? $stock->quantity : 0;
                                                
                                                // Для составных товаров рассчитываем доступное количество
                                                if ($product->is_composite) {
                                                    $minAvailable = PHP_INT_MAX;
                                                    foreach ($product->recipeComponents as $component) {
                                                        $componentStock = \App\Models\Stock::where('warehouse_id', $warehouseId)
                                                            ->where('product_id', $component->component_product_id)
                                                            ->first();
                                                        
                                                        if (!$componentStock || $componentStock->quantity <= 0) {
                                                            $available = 0;
                                                            break;
                                                        }
                                                        
                                                        $availableForComponent = floor($componentStock->quantity / $component->quantity);
                                                        $minAvailable = min($minAvailable, $availableForComponent);
                                                    }
                                                    $available = ($minAvailable !== PHP_INT_MAX) ? $minAvailable : 0;
                                                }
                                                
                                                // Категория товара
                                                $categoryName = $product->category ? $product->category->name : 'Без категории';
                                                $categoryId = $product->product_category_id ? (string)$product->product_category_id : 'null';
                                            @endphp
                                            <option value="{{ $product->id }}" 
                                                    data-unit="{{ $product->unit }}"
                                                    data-price="{{ $product->price }}"
                                                    data-available="{{ $available }}"
                                                    data-category="{{ $categoryId }}"
                                                    data-category-name="{{ $categoryName }}"
                                                    data-is-composite="{{ $product->is_composite ? '1' : '0' }}"
                                                    class="table-product-option"
                                                    {{ $available <= 0 ? 'disabled' : '' }}>
                                                {{ $product->name }} 
                                                @if($available > 0)
                                                    ({{ $available }} шт • {{ number_format($product->price, 2) }} ₽)
                                                @else
                                                    - нет в наличии
                                                @endif
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" id="productQuantity" 
                                               class="form-control" 
                                               placeholder="Кол-во" 
                                               min="0.001" 
                                               step="any" 
                                               value="1">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" id="productPrice" 
                                               class="form-control" 
                                               placeholder="Цена" 
                                               min="0.01" 
                                               step="0.01">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-primary w-100" id="addProductBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Добавить
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted" id="quantityHint"></small>
                                    <div id="productAvailabilityInfo" class="alert alert-warning mt-2 p-2" style="display: none;">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <span id="availabilityMessage"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Таблица товаров -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Товар</th>
                                                <th>Кол-во</th>
                                                <th>Цена</th>
                                                <th>Сумма</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="productsTableBody">
                                            <!-- Товары будут загружены через JavaScript -->
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Итого товаров:</td>
                                                <td class="fw-bold">
                                                    <span id="totalAmount">0.00</span> ₽
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.table-product-option {
    padding: 6px 10px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}
.table-product-option:last-child {
    border-bottom: none;
}
.table-product-option:disabled {
    color: #999;
    background-color: #f8f9fa;
}
select[multiple] option:checked {
    background-color: #e7f1ff;
    color: #0d6efd;
}
.form-label {
    font-size: 0.875rem;
    font-weight: 600;
}
</style>