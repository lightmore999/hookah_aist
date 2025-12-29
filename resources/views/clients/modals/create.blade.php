<div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createClientModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить нового клиента
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Имя клиента -->
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label fw-bold">Имя клиента *</label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="Например: Иван Иванов" 
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Телефон -->
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label fw-bold">Телефон *</label>
                            <input type="text" 
                                class="form-control @error('phone') is-invalid @enderror" 
                                id="phone" 
                                name="phone" 
                                value="{{ old('phone') }}" 
                                placeholder="+7 (999) 123-45-67" 
                                required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Бонусная карта -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="bonus_card_id" class="form-label fw-bold">Бонусная карта</label>
                            <select class="form-select @error('bonus_card_id') is-invalid @enderror" 
                                    id="bonus_card_id" 
                                    name="bonus_card_id">
                                <option value="">Выберите карту</option>
                                @foreach($bonusCards ?? [] as $card)
                                    <option value="{{ $card->IDBonusCard }}" 
                                            {{ old('bonus_card_id') == $card->IDBonusCard ? 'selected' : '' }}
                                            data-required="{{ $card->RequiredSpendAmount }}"
                                            data-table-percent="{{ $card->EarntRantTable }}"
                                            data-takeaway-percent="{{ $card->EarntRantTakeaway }}"
                                            data-max-percent="{{ $card->MaxSpendPercent }}"
                                            data-discount-percent="{{ $card->TableCloseDiscountPercent }}">
                                        {{ $card->Name }}
                                        @if($card->RequiredSpendAmount > 0)
                                            (от {{ $card->RequiredSpendAmount }} руб.)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('bonus_card_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#createBonusCardModal">
                                    <i class="bi bi-plus-circle"></i> Создать новую карту
                                </a>
                            </small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="bonus_points" class="form-label fw-bold">Бонусные баллы</label>
                            <div class="input-group">
                                <input type="number" 
                                    class="form-control @error('bonus_points') is-invalid @enderror" 
                                    id="bonus_points" 
                                    name="bonus_points" 
                                    value="{{ old('bonus_points', 0) }}" 
                                    min="0"
                                    step="1">
                                <span class="input-group-text">баллов</span>
                                @error('bonus_points')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Информация о выбранной карте -->
                    <div class="alert alert-info py-2 d-none" id="cardInfo">
                        <div class="row">
                            <div class="col-md-6">
                                <small><i class="bi bi-info-circle me-1"></i> Необходимые траты: <span id="requiredAmount">0</span> руб.</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-percent me-1"></i> Начисление: <span id="earnPercentTable">0</span>% (стол), <span id="earnPercentTakeaway">0</span>% (с собой)</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-credit-card me-1"></i> Макс. оплата бонусами: <span id="maxSpendPercent">0</span>%</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-gift me-1"></i> Скидка при закрытии: <span id="discountPercent">0</span>%</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Дата рождения -->
                        <div class="col-md-6 mb-3">
                            <label for="birth_date" class="form-label fw-bold">Дата рождения</label>
                            <input type="date" 
                                class="form-control @error('birth_date') is-invalid @enderror" 
                                id="birth_date" 
                                name="birth_date" 
                                value="{{ old('birth_date') }}">
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Комментарий -->
                        <div class="col-md-6 mb-3">
                            <label for="comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                    id="comment" 
                                    name="comment" 
                                    rows="2" 
                                    placeholder="Дополнительная информация о клиенте">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить клиента</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модалка для создания новой карты -->
<div class="modal fade" id="createBonusCardModal" tabindex="-1" aria-labelledby="createBonusCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createBonusCardModalLabel">
                    <i class="bi bi-credit-card me-2"></i>Создать бонусную карту
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form action="{{ route('bonus-cards.store') }}" method="POST" id="createBonusCardForm">
                @csrf
                <div class="modal-body">
                    <!-- Здесь будет форма создания карты -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> После создания карты, обновите страницу для её отображения в списке.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <a href="{{ route('bonus-cards.create') }}" class="btn btn-primary">
                        <i class="bi bi-credit-card me-1"></i> Перейти к созданию карты
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bonusCardSelect = document.getElementById('bonus_card_id');
    const cardInfo = document.getElementById('cardInfo');
    
    if (bonusCardSelect) {
        bonusCardSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                cardInfo.classList.remove('d-none');
                document.getElementById('requiredAmount').textContent = selectedOption.dataset.required || '0';
                document.getElementById('earnPercentTable').textContent = selectedOption.dataset.tablePercent || '0';
                document.getElementById('earnPercentTakeaway').textContent = selectedOption.dataset.takeawayPercent || '0';
                document.getElementById('maxSpendPercent').textContent = selectedOption.dataset.maxPercent || '0';
                document.getElementById('discountPercent').textContent = selectedOption.dataset.discountPercent || '0';
            } else {
                cardInfo.classList.add('d-none');
            }
        });
        
        // Инициализация при загрузке
        if (bonusCardSelect.value) {
            bonusCardSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>