<!-- Модальное окно для переноса товаров -->
<div class="modal fade" id="transferStockModal" tabindex="-1" aria-labelledby="transferStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('warehouses.transfer-stock') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="transferStockModalLabel">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i>
                        Перенос товара между складами
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="transferFromWarehouseId" name="from_warehouse_id" value="{{ $warehouse->id }}">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transferProductId" class="form-label">Товар *</label>
                            <select class="form-select" id="transferProductId" name="product_id" required>
                                <option value="">Выберите товар</option>
                                @foreach($stocks as $stock)
                                    @if($stock->quantity > 0)
                                        <option value="{{ $stock->product_id }}" 
                                                data-quantity="{{ $stock->quantity }}"
                                                data-unit="{{ $stock->product->unit ?? 'шт' }}">
                                            {{ $stock->product->name }} ({{ $stock->quantity }} {{ $stock->product->unit ?? 'шт' }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transferToWarehouseId" class="form-label">Целевой склад *</label>
                            <select class="form-select" id="transferToWarehouseId" name="to_warehouse_id" required>
                                <option value="">Выберите склад</option>
                                @foreach(\App\Models\Warehouse::where('id', '!=', $warehouse->id)->get() as $otherWarehouse)
                                    <option value="{{ $otherWarehouse->id }}">{{ $otherWarehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transferQuantity" class="form-label">Количество для переноса *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="transferQuantity" 
                                       name="quantity" 
                                       required 
                                       min="0.001" 
                                       step="0.001">
                                <span class="input-group-text" id="transferQuantityUnit">шт</span>
                            </div>
                            <div class="form-text">
                                Доступно: <span id="availableQuantity">0</span> <span id="availableQuantityUnit">шт</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="transferNotes" class="form-label">Примечание</label>
                            <textarea class="form-control" id="transferNotes" name="notes" rows="1"></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Товар будет списан с текущего склада и добавлен на выбранный целевой склад.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Перенести
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>