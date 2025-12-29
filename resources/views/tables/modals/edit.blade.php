<!-- Модальное окно редактирования стола -->
<div class="modal fade" id="editTableModal" tabindex="-1" aria-labelledby="editTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editTableModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать стол
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editTableForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_date" value="{{ $currentDate }}">
                <input type="hidden" id="edit_duration" name="duration">
                
                <div class="modal-body">
                    <!-- Показываем ошибки валидации в модалке -->
                    @if($errors->any())
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Пожалуйста, исправьте ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Номер стола -->
                    <div class="mb-4">
                        <label for="edit_table_number" class="form-label fw-bold">
                            <span class="text-danger">*</span> Номер стола
                        </label>
                        <select name="table_number" 
                                class="form-select @error('table_number') is-invalid @enderror" 
                                id="edit_table_number" 
                                required>
                            <option value="">-- Выберите стол --</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="Барная стойка">Барная стойка</option>
                            <option value="6">6</option>
                            <option value="7">7</option>
                        </select>
                        @error('table_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Дата и время начала -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_booking_date" class="form-label fw-bold">
                                <span class="text-danger">*</span> Дата
                            </label>
                            <input type="date" 
                                   class="form-control @error('booking_date') is-invalid @enderror" 
                                   id="edit_booking_date" 
                                   name="booking_date" 
                                   required>
                            @error('booking_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_booking_time" class="form-label fw-bold">
                                <span class="text-danger">*</span> Время начала
                            </label>
                            <select name="booking_time" 
                                    class="form-select @error('booking_time') is-invalid @enderror" 
                                    id="edit_booking_time" 
                                    required>
                                <option value="">-- Выберите время --</option>
                                @php
                                    // Генерируем времена с 14:00 до 03:30 следующего дня
                                    $start = \Carbon\Carbon::createFromTime(14, 0);
                                    $end = \Carbon\Carbon::createFromTime(3, 30)->addDay();
                                    $currentTime = $start->copy();
                                    
                                    while ($currentTime->lt($end)) {
                                        $timeValue = $currentTime->format('H:i');
                                        $displayTime = $currentTime->format('H:i');
                                        
                                        if ($currentTime->hour < 4) {
                                            $displayTime = $displayTime . ' (след. день)';
                                        }
                                        
                                        echo "<option value=\"$timeValue\">$displayTime</option>";
                                        
                                        $currentTime->addMinutes(30);
                                    }
                                @endphp
                            </select>
                            @error('booking_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Время окончания и информация о длительности -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_end_time" class="form-label fw-bold">
                                <span class="text-danger">*</span> Время окончания
                            </label>
                            <select name="end_time" 
                                    class="form-select @error('end_time') is-invalid @enderror" 
                                    id="edit_end_time" 
                                    required>
                                <option value="">-- Выберите время окончания --</option>
                                @php
                                    // Генерируем времена с 14:30 до 06:00 следующего дня
                                    $start = \Carbon\Carbon::createFromTime(14, 30);
                                    $end = \Carbon\Carbon::createFromTime(6, 0)->addDay();
                                    $currentTime = $start->copy();
                                    
                                    while ($currentTime->lte($end)) {
                                        $timeValue = $currentTime->format('H:i');
                                        $displayTime = $currentTime->format('H:i');
                                        
                                        if ($currentTime->hour < 6 || $currentTime->hour >= 24) {
                                            $displayTime = $displayTime . ' (след. день)';
                                        }
                                        
                                        echo "<option value=\"$timeValue\">$displayTime</option>";
                                        
                                        $currentTime->addMinutes(30);
                                    }
                                @endphp
                            </select>
                            @error('end_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Измените время окончания чтобы продлить стол</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Длительность</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control bg-light" 
                                       id="edit_duration_display" 
                                       readonly>
                                <span class="input-group-text">минут</span>
                            </div>
                            <div class="form-text">Рассчитывается автоматически</div>
                        </div>
                    </div>

                    <!-- Количество гостей и статус -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_guests_count" class="form-label fw-bold">
                                Количество гостей
                            </label>
                            <input type="number" 
                                   min="1" 
                                   max="50"
                                   class="form-control @error('guests_count') is-invalid @enderror" 
                                   id="edit_guests_count" 
                                   name="guests_count">
                            @error('guests_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="edit_status" class="form-label fw-bold">Статус</label>
                            <select name="status" 
                                    class="form-select @error('status') is-invalid @enderror" 
                                    id="edit_status">
                                <option value="new">Забронирован</option>
                                <option value="opened_without_hookah">Открыт без кальяна</option>
                                <option value="opened_with_hookah">Открыт с кальяном</option>
                                <option value="closed">Закрыт</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Клиент -->
                    <div class="mb-4">
                        <label for="edit_client_id" class="form-label fw-bold">Клиент</label>
                        <select name="client_id" 
                                class="form-select @error('client_id') is-invalid @enderror" 
                                id="edit_client_id">
                            <option value="">-- Выберите клиента (необязательно) --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}"
                                        data-client-name="{{ $client->name }}"
                                        data-client-phone="{{ $client->phone }}">
                                    {{ $client->name }} ({{ $client->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Имя гостя -->
                    <div class="mb-4">
                        <label for="edit_guest_name" class="form-label fw-bold">Имя гостя</label>
                        <input type="text" 
                               class="form-control @error('guest_name') is-invalid @enderror" 
                               id="edit_guest_name" 
                               name="guest_name" 
                               placeholder="Имя гостя">
                        @error('guest_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Если не выбран клиент, укажите имя гостя</div>
                    </div>

                    <!-- Телефон -->
                    <div class="mb-4">
                        <label for="edit_phone" class="form-label fw-bold">Телефон</label>
                        <input type="text" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="edit_phone" 
                               name="phone" 
                               placeholder="+7 (999) 123-45-67">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Комментарий -->
                    <div class="mb-4">
                        <label for="edit_comment" class="form-label fw-bold">Комментарий</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror" 
                                  id="edit_comment" 
                                  name="comment" 
                                  rows="3" 
                                  placeholder="Дополнительная информация"></textarea>
                        @error('comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функция для обновления видимости полей в модалке редактирования
    function updateEditGuestFieldsVisibility(clientSelect, guestNameInput, phoneInput) {
        if (clientSelect && clientSelect.value) {
            // Если выбран клиент, автоматически заполняем поля
            const selectedOption = clientSelect.options[clientSelect.selectedIndex];
            const clientName = selectedOption.getAttribute('data-client-name');
            const clientPhone = selectedOption.getAttribute('data-client-phone');
            
            if (guestNameInput) guestNameInput.value = clientName || '';
            if (phoneInput) phoneInput.value = clientPhone || '';
        }
    }
    
    // Функция для расчета времени окончания на основе времени начала и длительности
    function calculateEndTime(startTime, duration) {
        if (!startTime || !duration) return null;
        
        const [hours, minutes] = startTime.split(':').map(Number);
        const startDate = new Date();
        startDate.setHours(hours, minutes, 0, 0);
        
        const endDate = new Date(startDate.getTime() + duration * 60000);
        
        // Форматируем в HH:mm
        const endHours = endDate.getHours().toString().padStart(2, '0');
        const endMinutes = endDate.getMinutes().toString().padStart(2, '0');
        
        return `${endHours}:${endMinutes}`;
    }
    
    // Функция для расчета длительности на основе времени начала и окончания
    function calculateDuration(startTime, endTime) {
        if (!startTime || !endTime) return 0;
        
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);
        
        const startDate = new Date();
        startDate.setHours(startHours, startMinutes, 0, 0);
        
        const endDate = new Date();
        endDate.setHours(endHours, endMinutes, 0, 0);
        
        // Если время окончания меньше времени начала, значит это на следующий день
        if (endDate < startDate) {
            endDate.setDate(endDate.getDate() + 1);
        }
        
        const durationMs = endDate - startDate;
        return Math.round(durationMs / 60000); // в минутах
    }
    
    // Обработчик открытия модалки редактирования
    const editModal = document.getElementById('editTableModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-table-btn')) {
                // Берем данные
                const tableNumber = button.dataset.tableNumber;
                const bookingDate = button.dataset.bookingDate;
                const bookingTime = button.dataset.bookingTime;
                const duration = parseInt(button.dataset.duration) || 120; // дефолт 2 часа
                const guestName = button.dataset.guestName;
                const phone = button.dataset.phone;
                const guestsCount = button.dataset.guestsCount;
                const comment = button.dataset.comment;
                const clientId = button.dataset.clientId || '';
                const status = button.dataset.status || 'new';
                
                // Рассчитываем время окончания
                const endTime = calculateEndTime(bookingTime, duration);
                
                // Заполняем форму данными
                document.getElementById('edit_table_number').value = tableNumber;
                document.getElementById('edit_booking_date').value = bookingDate;
                document.getElementById('edit_booking_time').value = bookingTime;
                document.getElementById('edit_end_time').value = endTime;
                document.getElementById('edit_duration').value = duration;
                document.getElementById('edit_duration_display').value = duration;
                document.getElementById('edit_guest_name').value = guestName || '';
                document.getElementById('edit_phone').value = phone || '';
                document.getElementById('edit_guests_count').value = guestsCount || '';
                document.getElementById('edit_comment').value = comment || '';
                document.getElementById('edit_client_id').value = clientId;
                document.getElementById('edit_status').value = status;
                
                // Устанавливаем action формы
                const form = document.getElementById('editTableForm');
                form.action = `/tables/${button.dataset.id}`;
                
                // Обновляем поля клиента при загрузке
                const clientSelect = document.getElementById('edit_client_id');
                const guestNameInput = document.getElementById('edit_guest_name');
                const phoneInput = document.getElementById('edit_phone');
                
                updateEditGuestFieldsVisibility(clientSelect, guestNameInput, phoneInput);
                
                // Добавляем обработчик изменения клиента
                if (clientSelect) {
                    clientSelect.addEventListener('change', function() {
                        updateEditGuestFieldsVisibility(clientSelect, guestNameInput, phoneInput);
                    });
                }
            }
        });
    }
    
    // Автоматический расчет длительности при изменении времени начала или окончания
    const editBookingTime = document.getElementById('edit_booking_time');
    const editEndTime = document.getElementById('edit_end_time');
    const editDuration = document.getElementById('edit_duration');
    const editDurationDisplay = document.getElementById('edit_duration_display');
    
    function updateDuration() {
        if (editBookingTime.value && editEndTime.value) {
            const duration = calculateDuration(editBookingTime.value, editEndTime.value);
            
            if (duration > 0) {
                editDuration.value = duration;
                editDurationDisplay.value = duration;
            }
        }
    }
    
    if (editBookingTime) {
        editBookingTime.addEventListener('change', updateDuration);
    }
    
    if (editEndTime) {
        editEndTime.addEventListener('change', updateDuration);
    }
});
</script>