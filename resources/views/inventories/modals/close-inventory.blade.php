<div class="modal fade" id="closeInventoryModal" tabindex="-1" aria-labelledby="closeInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="closeInventoryModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Завершить инвентаризацию
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <div class="modal-body py-4">
                <div class="text-center mb-4">
                    <i class="bi bi-clipboard-check text-success display-4"></i>
                </div>
                
                <h5 class="text-center mb-3">Подтверждение завершения</h5>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    После завершения инвентаризации:
                    <ul class="mb-0 mt-2">
                        <li>Инвентаризация будет закрыта для редактирования</li>
                        <li>Остатки товаров на складе будут обновлены согласно фактическим количествам</li>
                        <li>Данное действие нельзя будет отменить</li>
                    </ul>
                </div>
                
                @if($inventory->hasDifferences())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Обнаружены различия!</strong>
                        Общая разница: 
                        <span class="fw-bold {{ $inventory->total_difference > 0 ? 'text-success' : ($inventory->total_difference < 0 ? 'text-danger' : '') }}">
                            {{ $inventory->total_difference > 0 ? '+' : '' }}{{ $inventory->total_difference }}
                        </span>
                    </div>
                @endif
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmClose" required>
                    <label class="form-check-label" for="confirmClose">
                        Я подтверждаю, что все товары посчитаны верно и готов завершить инвентаризацию
                    </label>
                </div>
            </div>
            
            <form action="{{ route('inventories.close', $inventory) }}" method="POST">
                @csrf
                
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-success" id="closeInventoryBtn" disabled>
                        <i class="bi bi-check-circle me-1"></i>Завершить инвентаризацию
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCloseCheckbox = document.getElementById('confirmClose');
    const closeInventoryBtn = document.getElementById('closeInventoryBtn');
    
    if (confirmCloseCheckbox && closeInventoryBtn) {
        confirmCloseCheckbox.addEventListener('change', function() {
            closeInventoryBtn.disabled = !this.checked;
        });
    }
});
</script>