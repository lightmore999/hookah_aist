<div class="modal fade" id="createInventoryModal" tabindex="-1" aria-labelledby="createInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createInventoryModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Новая инвентаризация
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('inventories.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <!-- Склады (множественный выбор) -->
                    <div class="mb-4">
                        <label for="warehouse_ids" class="form-label fw-bold">Склады *</label>
                        <select name="warehouse_ids[]" id="warehouse_ids" 
                                class="form-select @error('warehouse_ids') is-invalid @enderror" 
                                multiple
                                required>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ in_array($warehouse->id, old('warehouse_ids', [])) ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Выберите один или несколько складов для инвентаризации</div>
                    </div>

                    <!-- Название -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">Название</label>
                        <input type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            placeholder="Оставьте пустым для автоматического названия">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Если оставить пустым, будет создано название "Инвентаризация от [дата время]"</div>
                    </div>

                    <!-- Дата инвентаризации -->
                    <div class="mb-4">
                        <label for="inventory_date" class="form-label fw-bold">Дата инвентаризации</label>
                        <input type="datetime-local" 
                            class="form-control @error('inventory_date') is-invalid @enderror" 
                            id="inventory_date" 
                            name="inventory_date" 
                            value="{{ old('inventory_date') }}">
                        @error('inventory_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Если оставить пустым, будет установлена текущая дата и время</div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Создать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Select2 для выбора нескольких складов
    if (document.getElementById('warehouse_ids')) {
        $('#warehouse_ids').select2({
            placeholder: "Выберите склады",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#createInventoryModal')
        });
    }
});
</script>