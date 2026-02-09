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
                
                <!-- Блок выбора товара - объединенный визуально -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="categoryFilterProducts" class="form-label fw-bold">Категория</label>
                        <select class="form-select form-select-sm" id="categoryFilterProducts">
                            <option value="all">Все категории</option>
                            @php
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
                            
                            @if($products->where('product_category_id', null)->count() > 0)
                                <option value="null">Без категории</option>
                            @endif
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Блок поиска и выбора товара -->
                        <div class="product-select-container">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">Товар</label>
                                <small class="text-muted">Выберите из списка или ищите ниже</small>
                            </div>
                            
                            <!-- Поле поиска - вплотную к select -->
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="searchTableProduct" 
                                       placeholder="Начните вводить название товара..."
                                       style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                                <button class="btn btn-outline-secondary" type="button" id="clearTableSearch">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            
                            <!-- Select - прямо под поиском -->
                            <select class="form-select" id="productSelect" size="4" style="height: auto; border-top-left-radius: 0; border-top-right-radius: 0;">
                                <option value="">Начните вводить название выше или выберите из списка...</option>
                                @foreach($products as $product)
                                @php
                                    $available = \App\Models\Stock::where('product_id', $product->id)->sum('quantity');
                                    
                                    if ($product->is_composite) {
                                        $minAvailable = PHP_INT_MAX;
                                        foreach ($product->recipeComponents as $component) {
                                            $totalComponentQuantity = \App\Models\Stock::where('product_id', $component->component_product_id)->sum('quantity');
                                            
                                            if ($totalComponentQuantity <= 0) {
                                                $available = 0;
                                                break;
                                            }
                                            
                                            $availableForComponent = floor($totalComponentQuantity / $component->quantity);
                                            $minAvailable = min($minAvailable, $availableForComponent);
                                        }
                                        $available = ($minAvailable !== PHP_INT_MAX) ? $minAvailable : 0;
                                    }
                                    
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
                                        <div class="selected-product-display p-2 border rounded bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <div class="fw-medium" id="selectedProductName">Товар не выбран</div>
                                                    <div class="small text-muted" id="selectedProductDetails">Выберите товар из списка</div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fw-bold text-primary" id="selectedProductPrice">- ₽</div>
                                                    <div class="small text-success" id="selectedProductStock" style="display: none;">
                                                        Доступно: <span id="selectedProductAvailable">0</span> <span id="selectedProductUnit">шт</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="productQuantity" class="form-label small mb-1">Кол-во</label>
                                        <input type="number" id="productQuantity" 
                                               class="form-control" 
                                               placeholder="Кол-во" 
                                               min="0.001" 
                                               step="any" 
                                               value="1">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="productPrice" class="form-label small mb-1">Цена</label>
                                        <input type="number" id="productPrice" 
                                               class="form-control" 
                                               placeholder="Цена" 
                                               min="0.01" 
                                               step="0.01">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
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
                
                <!-- Таблица товаров (остается без изменений) -->
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
/* Контейнер для объединенного поиска и select */
.product-select-container {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 8px;
    background: #fff;
}

/* Убираем стандартные рамки у input и select внутри контейнера */
.product-select-container .input-group,
.product-select-container select {
    border: none !important;
    box-shadow: none !important;
}

/* Стили для отображения выбранного товара */
.selected-product-display {
    min-height: 60px;
    display: flex;
    align-items: center;
}

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

/* Плавный переход при фокусе */
.product-select-container:focus-within {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>