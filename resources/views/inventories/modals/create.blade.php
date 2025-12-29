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
                    <!-- Склад -->
                    <div class="mb-4">
                        <label for="warehouse_id" class="form-label fw-bold">Склад *</label>
                        <select name="warehouse_id" id="warehouse_id" 
                                class="form-select @error('warehouse_id') is-invalid @enderror" 
                                required>
                            <option value="">Выберите склад</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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

                    <!-- Заметки -->
                    <div class="mb-4">
                        <label for="notes" class="form-label fw-bold">Заметки</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="notes" 
                                  name="notes" 
                                  rows="3"
                                  placeholder="Дополнительная информация об инвентаризации">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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