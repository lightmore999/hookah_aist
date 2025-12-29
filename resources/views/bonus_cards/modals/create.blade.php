<!-- resources/views/bonus_cards/modals/create.blade.php -->
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
                        
                        <!-- Начисление за стол -->
                        <div class="col-md-6 mb-3">
                            <label for="EarntRantTable" class="form-label fw-bold">Начисление за стол (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('EarntRantTable') is-invalid @enderror" 
                                       id="EarntRantTable" 
                                       name="EarntRantTable" 
                                       value="{{ old('EarntRantTable', 5) }}" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                                @error('EarntRantTable')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Процент бонусов от суммы заказа за стол</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Начисление за доставку/с собой -->
                        <div class="col-md-6 mb-3">
                            <label for="EarntRantTakeaway" class="form-label fw-bold">Начисление с собой (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('EarntRantTakeaway') is-invalid @enderror" 
                                       id="EarntRantTakeaway" 
                                       name="EarntRantTakeaway" 
                                       value="{{ old('EarntRantTakeaway', 2) }}" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                                @error('EarntRantTakeaway')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">Процент бонусов от суммы заказа с собой/доставка</small>
                        </div>
                        
                        <!-- Максимальный процент оплаты бонусами -->
                        <div class="col-md-6 mb-3">
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
                    
                    <!-- Скидка при закрытии стола -->
                    <div class="mb-3">
                        <label for="TableCloseDiscountPercent" class="form-label fw-bold">Скидка при закрытии (%) *</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control @error('TableCloseDiscountPercent') is-invalid @enderror" 
                                   id="TableCloseDiscountPercent" 
                                   name="TableCloseDiscountPercent" 
                                   value="{{ old('TableCloseDiscountPercent', 0) }}" 
                                   min="0" 
                                   max="100"
                                   required>
                            <span class="input-group-text">%</span>
                            @error('TableCloseDiscountPercent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Процент скидки при закрытии стола</small>
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