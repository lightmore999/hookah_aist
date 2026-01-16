<div class="modal fade" id="createCardModal" tabindex="-1" aria-labelledby="createCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createCardModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Создание бонусной карты
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('bonus-cards.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <!-- Название карты -->
                    <div class="mb-3">
                        <label for="Name" class="form-label fw-bold">Название карты *</label>
                        <input type="text" 
                               class="form-control @error('Name') is-invalid @enderror" 
                               id="Name" 
                               name="Name" 
                               value="{{ old('Name') }}" 
                               placeholder="Например: Золотая карта" 
                               required>
                        @error('Name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <!-- Необходимая сумма трат -->
                        <div class="col-md-6 mb-3">
                            <label for="RequiredSpendAmount" class="form-label fw-bold">Необходимые траты (руб) *</label>
                            <input type="number" 
                                   class="form-control @error('RequiredSpendAmount') is-invalid @enderror" 
                                   id="RequiredSpendAmount" 
                                   name="RequiredSpendAmount" 
                                   value="{{ old('RequiredSpendAmount', 0) }}" 
                                   min="0" 
                                   step="100"
                                   required>
                            @error('RequiredSpendAmount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Сумма, которую должен потратить клиент для получения карты</small>
                        </div>
                        
                        <!-- Начисление бонусов с продажи -->
                        <div class="col-md-6 mb-3">
                            <label for="BonusPercent" class="form-label fw-bold">Начисление бонусов (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('BonusPercent') is-invalid @enderror" 
                                       id="BonusPercent" 
                                       name="BonusPercent" 
                                       value="{{ old('BonusPercent', 5) }}" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                                @error('BonusPercent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Процент бонусов от суммы продажи</small>
                        </div>
                    </div>
                    
                    <!-- Максимальный процент оплаты бонусами -->
                    <div class="mb-3">
                        <label for="MaxSpendPercent" class="form-label fw-bold">Макс. оплата бонусами (%) *</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control @error('MaxSpendPercent') is-invalid @enderror" 
                                   id="MaxSpendPercent" 
                                   name="MaxSpendPercent" 
                                   value="{{ old('MaxSpendPercent', 10) }}" 
                                   min="0" 
                                   max="100"
                                   required>
                            <span class="input-group-text">%</span>
                            @error('MaxSpendPercent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Максимальный процент от суммы заказа, который можно оплатить бонусами</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать карту</button>
                </div>
            </form>
        </div>
    </div>
</div>