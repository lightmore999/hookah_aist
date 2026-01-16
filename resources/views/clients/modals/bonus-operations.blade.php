<!-- Модалка начисления бонусов -->
<div class="modal fade" id="addBonusModal" tabindex="-1" aria-labelledby="addBonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addBonusModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Начислить бонусы
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="addBonusForm" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_bonus_amount" class="form-label fw-bold">Сумма бонусов</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="add_bonus_amount" 
                                   name="amount" 
                                   min="1" 
                                   value="100" 
                                   required>
                            <span class="input-group-text">баллов</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_bonus_reason" class="form-label fw-bold">Причина начисления</label>
                        <textarea class="form-control" 
                                  id="add_bonus_reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Укажите причину начисления бонусов" ></textarea>
                        <div class="form-text">Например: "Бонус за рекомендацию", "Исправление ошибки", "Подарок на день рождения"</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Начислить бонусы</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модалка списания бонусов -->
<div class="modal fade" id="subtractBonusModal" tabindex="-1" aria-labelledby="subtractBonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="subtractBonusModalLabel">
                    <i class="bi bi-dash-circle me-2"></i>Списать бонусы
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="subtractBonusForm" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subtract_bonus_amount" class="form-label fw-bold">Сумма списания</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="subtract_bonus_amount" 
                                   name="amount" 
                                   min="1" 
                                   value="100" 
                                   required>
                            <span class="input-group-text">баллов</span>
                        </div>
                        <div class="form-text">Доступно: <span id="availableBonusPoints">0</span> баллов</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subtract_bonus_reason" class="form-label fw-bold">Причина списания</label>
                        <textarea class="form-control" 
                                  id="subtract_bonus_reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Укажите причину списания бонусов" >
                                </textarea>
                        <div class="form-text">Например: "Оплата заказа", "Списание за услугу", "Корректировка баланса"</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Списать бонусы</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для модалки начисления
    const addBonusModal = document.getElementById('addBonusModal');
    if (addBonusModal) {
        addBonusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('add-bonus-btn')) {
                const clientId = button.dataset.id;
                const clientName = button.dataset.name;
                
                // Устанавливаем action формы
                document.getElementById('addBonusForm').action = `/clients/${clientId}/add-bonus`;
                
                // Обновляем заголовок
                document.querySelector('#addBonusModalLabel').innerHTML = 
                    `<i class="bi bi-plus-circle me-2"></i>Начислить бонусы (${clientName})`;
                    
                // Автозаполняем причину
                document.getElementById('add_bonus_reason').value = ``;
            }
        });
    }
    
    // Обработчик для модалки списания
    const subtractBonusModal = document.getElementById('subtractBonusModal');
    if (subtractBonusModal) {
        subtractBonusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('subtract-bonus-btn')) {
                const clientId = button.dataset.id;
                const clientName = button.dataset.name;
                const currentPoints = button.dataset.bonusPoints || '0';
                
                // Устанавливаем action формы
                document.getElementById('subtractBonusForm').action = `/clients/${clientId}/subtract-bonus`;
                
                // Обновляем заголовок
                document.querySelector('#subtractBonusModalLabel').innerHTML = 
                    `<i class="bi bi-dash-circle me-2"></i>Списать бонусы (${clientName})`;
                    
                // Показываем доступные бонусы
                document.getElementById('availableBonusPoints').textContent = currentPoints;
                
                // Устанавливаем максимальное значение для списания
                const amountInput = document.getElementById('subtract_bonus_amount');
                amountInput.max = currentPoints;
                amountInput.value = Math.min(100, currentPoints);
                
                // Автозаполняем причину
                document.getElementById('subtract_bonus_reason').value = `Списание бонусов у клиента ${clientName}`;
            }
        });
    }
});
</script>