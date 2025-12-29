<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editClientModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать клиента
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editClientForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Имя клиента -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label fw-bold">Имя клиента *</label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="edit_name" 
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
                            <label for="edit_phone" class="form-label fw-bold">Телефон *</label>
                            <input type="text" 
                                class="form-control @error('phone') is-invalid @enderror" 
                                id="edit_phone" 
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
                            <label for="edit_bonus_card_id" class="form-label fw-bold">Бонусная карта</label>
                            <select class="form-select @error('bonus_card_id') is-invalid @enderror" 
                                    id="edit_bonus_card_id" 
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
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_bonus_points" class="form-label fw-bold">Бонусные баллы</label>
                            <div class="input-group">
                                <input type="number" 
                                    class="form-control @error('bonus_points') is-invalid @enderror" 
                                    id="edit_bonus_points" 
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
                    <div class="alert alert-info py-2 d-none" id="editCardInfo">
                        <div class="row">
                            <div class="col-md-6">
                                <small><i class="bi bi-info-circle me-1"></i> Необходимые траты: <span id="editRequiredAmount">0</span> руб.</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-percent me-1"></i> Начисление: <span id="editEarnPercentTable">0</span>% (стол), <span id="editEarnPercentTakeaway">0</span>% (с собой)</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-credit-card me-1"></i> Макс. оплата бонусами: <span id="editMaxSpendPercent">0</span>%</small>
                            </div>
                            <div class="col-md-6">
                                <small><i class="bi bi-gift me-1"></i> Скидка при закрытии: <span id="editDiscountPercent">0</span>%</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Дата рождения -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_birth_date" class="form-label fw-bold">Дата рождения</label>
                            <input type="date" 
                                class="form-control @error('birth_date') is-invalid @enderror" 
                                id="edit_birth_date" 
                                name="birth_date" 
                                value="{{ old('birth_date') }}">
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Комментарий -->
                        <div class="col-md-6 mb-3">
                            <label for="edit_comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                    id="edit_comment" 
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
                    <button type="submit" class="btn btn-warning">Обновить данные</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBonusCardSelect = document.getElementById('edit_bonus_card_id');
    const editCardInfo = document.getElementById('editCardInfo');
    
    if (editBonusCardSelect) {
        editBonusCardSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                editCardInfo.classList.remove('d-none');
                document.getElementById('editRequiredAmount').textContent = selectedOption.dataset.required || '0';
                document.getElementById('editEarnPercentTable').textContent = selectedOption.dataset.tablePercent || '0';
                document.getElementById('editEarnPercentTakeaway').textContent = selectedOption.dataset.takeawayPercent || '0';
                document.getElementById('editMaxSpendPercent').textContent = selectedOption.dataset.maxPercent || '0';
                document.getElementById('editDiscountPercent').textContent = selectedOption.dataset.discountPercent || '0';
            } else {
                editCardInfo.classList.add('d-none');
            }
        });
    }
    
    // Обновляем обработчик для передачи данных бонусной карты
    const editClientModal = document.getElementById('editClientModal');
    if (editClientModal) {
        editClientModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-client-btn')) {
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_phone').value = button.dataset.phone;
                document.getElementById('edit_birth_date').value = button.dataset.birthDate;
                document.getElementById('edit_comment').value = button.dataset.comment;
                document.getElementById('edit_bonus_points').value = button.dataset.bonusPoints || '0';
                document.getElementById('edit_bonus_card_id').value = button.dataset.bonusCardId || '';
                document.getElementById('editClientForm').action = `/clients/${button.dataset.id}`;
                
                // Обновляем информацию о карте
                if (editBonusCardSelect) {
                    editBonusCardSelect.dispatchEvent(new Event('change'));
                }
            }
        });
    }
});
</script>