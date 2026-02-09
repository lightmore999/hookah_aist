<!-- Модальное окно для удаления товара со склада -->
<div class="modal fade" id="removeStockModal" tabindex="-1" aria-labelledby="removeStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- ИСПРАВЬТЕ ЭТУ СТРОКУ: добавьте action с правильным маршрутом -->
            <form action="{{ route('warehouses.remove-stock', ['warehouse' => $warehouse->id]) }}" method="POST" id="removeStockForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="removeStockModalLabel">
                        <i class="bi bi-trash text-danger me-2"></i>
                        Удалить товар со склада
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="removeProductId" name="product_id">
                    
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                    </div>
                    
                    <p class="text-center">
                        Вы действительно хотите удалить товар 
                        <strong><span id="removeProductName"></span></strong> 
                        со склада?
                    </p>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <div>
                                <strong>Внимание!</strong><br>
                                Эта операция удалит запись о товаре из таблицы остатков склада.
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Удаление возможно только если остаток товара равен 0.
                        Для товаров с остатками используйте функцию списания.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>