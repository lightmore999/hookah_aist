<div class="modal fade" id="saleProductsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saleProductsModalLabel">Товары</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <!-- Информация -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info" id="saleProductsInfo">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Выберите стол</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Форма добавления товара -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3 text-muted">Добавить товар</h6>
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <select class="form-select" id="productSelect">
                                            <option value="">Выберите товар...</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-unit="{{ $product->unit }}"
                                                        data-price="{{ $product->price }}">
                                                    {{ $product->name }} - {{ number_format($product->price, 2) }} ₽/{{ $product->unit }}
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Таблица товаров -->
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                        <td colspan="3" class="text-end fw-bold">Итого:</td>
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
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Закрыть
                </button>
            </div>
        </div>
    </div>
</div>