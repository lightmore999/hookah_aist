<div class="modal fade" id="createExpenditureTypeModal" tabindex="-1" aria-labelledby="createExpenditureTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createExpenditureTypeModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить тип расхода
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('expenditure-types.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">Название типа *</label>
                        <input type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            placeholder="Например: Аренда, Зарплаты, Коммунальные" 
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>