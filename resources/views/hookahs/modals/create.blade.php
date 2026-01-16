<div class="modal fade" id="createHookahModal" tabindex="-1" aria-labelledby="createHookahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createHookahModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить новый кальян
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('hookahs.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <!-- Название кальяна -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-bold">Название кальяна *</label>
                        <input type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            placeholder="Например: Яблочный кальян, Клубничный микс" 
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Укажите название, которое будет отображаться в меню</div>
                    </div>

                    <!-- Цена и себестоимость -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-bold">Цена продажи (₽) *</label>
                            <div class="input-group">
                                <input type="number" 
                                    min="0" 
                                    step="0.01"
                                    class="form-control @error('price') is-invalid @enderror" 
                                    id="price" 
                                    name="price" 
                                    value="{{ old('price') }}" 
                                    placeholder="0.00" 
                                    required>
                                <span class="input-group-text">₽</span>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">По этой цене кальян будет продаваться</div>
                        </div>

                        <div class="col-md-6">
                            <label for="cost" class="form-label fw-bold">Себестоимость (₽) *</label>
                            <div class="input-group">
                                <input type="number" 
                                    min="0" 
                                    step="0.01"
                                    class="form-control @error('cost') is-invalid @enderror" 
                                    id="cost" 
                                    name="cost" 
                                    value="{{ old('cost') }}" 
                                    placeholder="0.00" 
                                    required>
                                <span class="input-group-text">₽</span>
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Стоимость ингредиентов и материалов</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Сохранить кальян
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>