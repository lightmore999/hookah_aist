<div class="modal fade" id="manageShiftModal" tabindex="-1" aria-labelledby="manageShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manageShiftModalLabel">
                    <i class="bi bi-gear me-2"></i>Управление сменой
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <div class="modal-body">
                <!-- Основная информация и кнопки -->
                <div class="row mb-4">
                    <!-- Основная информация -->
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Основная информация</h6>
                            </div>
                            <div class="card-body">
                                <h3 class="text-primary mb-3" id="manage_shift_date"></h3>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <strong class="me-3">Статус:</strong>
                                    <span id="manage_shift_status" class="badge fs-6 px-3 py-2"></span>
                                </div>
                                
                                <div id="manage_opened_at_container" class="mb-2 d-none">
                                    <i class="bi bi-play-circle text-success me-2"></i>
                                    <strong>Открыта:</strong> 
                                    <span id="manage_shift_opened_at" class="ms-2"></span>
                                </div>
                                <div id="manage_closed_at_container" class="mb-2 d-none">
                                    <i class="bi bi-stop-circle text-danger me-2"></i>
                                    <strong>Закрыта:</strong> 
                                    <span id="manage_shift_closed_at" class="ms-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Действия</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-grid gap-2">
                                    <!-- Кнопка открытия (для planned) -->
                                    <form id="openShiftForm" method="POST" class="d-none">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100"
                                                onclick="return confirm('Вы уверены, что хотите открыть смену?')">
                                            <i class="bi bi-play-circle me-1"></i>Открыть смену
                                        </button>
                                    </form>
                                    
                                    <!-- Кнопка закрытия (для open) -->
                                    <form id="closeShiftForm" method="POST" class="d-none">
                                        @csrf
                                        <button type="submit" class="btn btn-danger w-100"
                                                onclick="return confirm('Вы уверены, что хотите закрыть смену и рассчитать зарплату?')">
                                            <i class="bi bi-stop-circle me-1"></i>Закрыть смену
                                        </button>
                                    </form>
                                    
                                    <!-- Кнопка повторного открытия (для closed) -->
                                    <form id="reopenShiftForm" method="POST" class="d-none">
                                        @csrf
                                        <button type="submit" class="btn btn-warning w-100"
                                                onclick="return confirm('Вы уверены, что хотите повторно открыть закрытую смену?')">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Повторно открыть
                                        </button>
                                    </form>
                                    
                                    <!-- Сообщение, когда ничего нельзя сделать -->
                                    <div id="noActionsMessage" class="text-center p-3 bg-light rounded d-none">
                                        <i class="bi bi-slash-circle text-muted fs-4 d-block mb-2"></i>
                                        <p class="small text-muted mb-0">Действия недоступны</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Сотрудники и Комментарий -->
                <div class="row">
                    <!-- Сотрудники -->
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card h-100">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-people-fill me-2"></i>
                                    Сотрудники 
                                    <span id="manage_shift_employees_count" class="badge bg-primary ms-2">0</span>
                                </h6>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary manage-employees-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addEmployeesModal">
                                    <i class="bi bi-plus-circle me-1"></i>Управление
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="manage_shift_employees" class="overflow-auto" style="max-height: 200px;">
                                    <!-- Сотрудники будут здесь -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Комментарий (один) -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="bi bi-chat-text me-2"></i>
                                    Комментарий к смене
                                </h6>
                            </div>
                            <div class="card-body">
                                <!-- Форма для комментария -->
                                <form id="noteForm" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="shift_id" id="note_shift_id">
                                    
                                    <div class="mb-3">
                                        <textarea name="note" 
                                                id="shiftNote" 
                                                class="form-control" 
                                                rows="4" 
                                                placeholder="Введите комментарий к смене (максимум 500 символов)..."
                                                maxlength="500"></textarea>
                                        <div class="text-muted small mt-1">
                                            <span id="noteCounter">0</span>/500 символов
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Сохранить комментарий
                                        </button>
                                    </div>
                                </form>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const manageShiftModal = document.getElementById('manageShiftModal');
    const noteTextarea = document.getElementById('shiftNote');
    const noteCounter = document.getElementById('noteCounter');
    const noteForm = document.getElementById('noteForm');
    
    // Счетчик символов
    if (noteTextarea && noteCounter) {
        noteTextarea.addEventListener('input', function() {
            noteCounter.textContent = this.value.length;
        });
    }
    
    // Обработка формы комментария
    if (noteForm) {
        noteForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const shiftId = document.getElementById('note_shift_id').value;
            const note = noteTextarea.value.trim();
            const formData = new FormData(this);
            
            try {
                const response = await fetch(`/shifts/${shiftId}/note`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-HTTP-Method-Override': 'PUT'
                    }
                });
                
                if (response.ok) {
                    // Показываем уведомление
                    showToast('Комментарий сохранен', 'success');
                } else {
                    showToast('Ошибка при сохранении', 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Ошибка при сохранении', 'danger');
            }
        });
    }
    
    // Обработка открытия модалки
    if (manageShiftModal) {
        manageShiftModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.dataset.shiftId) {
                loadShiftData(button);
            }
        });
    }
    
    // Функция загрузки данных смены
    function loadShiftData(button) {
        const shiftId = button.dataset.shiftId;
        const date = button.dataset.date;
        const status = button.dataset.status;
        const statusText = button.dataset.statusText;
        const statusColor = button.dataset.statusColor;
        const employees = button.dataset.employees;
        const employeesCount = button.dataset.employeesCount;
        const openedAt = button.dataset.openedAt;
        const closedAt = button.dataset.closedAt;
        const notes = button.dataset.notes;
        
        // Основная информация
        document.getElementById('manage_shift_date').textContent = date;
        document.getElementById('manage_shift_status').textContent = statusText;
        document.getElementById('manage_shift_status').className = `badge bg-${statusColor} fs-6 px-3 py-2`;
        document.getElementById('manage_shift_employees_count').textContent = employeesCount;
        document.getElementById('note_shift_id').value = shiftId;
        
        // Комментарий
        if (noteTextarea) {
            noteTextarea.value = notes || '';
            noteCounter.textContent = notes ? notes.length : 0;
        }
        
        // Время
        updateTimeInfo(openedAt, closedAt);
        
        // Сотрудники
        updateEmployeesList(employees, employeesCount);
        
        // Кнопки действий
        updateActionButtons(status, shiftId);
    }
    
    // Обновление времени
    function updateTimeInfo(openedAt, closedAt) {
        const openedContainer = document.getElementById('manage_opened_at_container');
        const closedContainer = document.getElementById('manage_closed_at_container');
        
        if (openedAt) {
            document.getElementById('manage_shift_opened_at').textContent = openedAt;
            openedContainer.classList.remove('d-none');
        } else {
            openedContainer.classList.add('d-none');
        }
        
        if (closedAt) {
            document.getElementById('manage_shift_closed_at').textContent = closedAt;
            closedContainer.classList.remove('d-none');
        } else {
            closedContainer.classList.add('d-none');
        }
    }
    
    // Обновление списка сотрудников
    function updateEmployeesList(employees, count) {
        const container = document.getElementById('manage_shift_employees');
        
        if (employees && employees.trim() !== '' && employees !== 'null') {
            // Разделяем строку по запятой
            const employeesArray = employees.split(', ');
            
            if (employeesArray.length > 0) {
                container.innerHTML = employeesArray.map(emp => {
                    const trimmedEmp = emp.trim();
                    if (!trimmedEmp) return '';
                    
                    return `
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                style="width: 32px; height: 32px; font-size: 0.9rem;">
                                ${trimmedEmp.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-medium">${trimmedEmp}</div>
                                <div class="small text-muted">Сотрудник</div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                showNoData(container, 'Нет сотрудников', 'bi-person-x');
            }
        } else {
            showNoData(container, 'Нет сотрудников', 'bi-person-x');
        }
    }
    
    // Обновление кнопок действий
    function updateActionButtons(status, shiftId) {
        const openForm = document.getElementById('openShiftForm');
        const closeForm = document.getElementById('closeShiftForm');
        const reopenForm = document.getElementById('reopenShiftForm');
        const noActions = document.getElementById('noActionsMessage');
        
        // Обновляем URL форм
        if (openForm) openForm.action = `/shifts/${shiftId}/open`;
        if (closeForm) closeForm.action = `/shifts/${shiftId}/close`;
        if (reopenForm) reopenForm.action = `/shifts/${shiftId}/reopen`;
        if (noteForm) noteForm.action = `/shifts/${shiftId}/note`;
        
        // Скрываем все элементы управления
        const allActionElements = [openForm, closeForm, reopenForm, noActions];
        allActionElements.forEach(el => {
            if (el) el.classList.add('d-none');
        });
        
        // Показываем нужные элементы в зависимости от статуса
        switch (status) {
            case 'planned':
                if (openForm) openForm.classList.remove('d-none');
                break;
                
            case 'open':
                if (closeForm) closeForm.classList.remove('d-none');
                break;
                
            case 'closed':
                if (reopenForm) reopenForm.classList.remove('d-none');
                break;
                
            default:
                if (noActions) noActions.classList.remove('d-none');
                break;
        }
    }
    
    // Вспомогательные функции
    function showNoData(container, message, icon) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="bi ${icon} text-muted fs-4"></i>
                <p class="text-muted mb-0 mt-2 small">${message}</p>
            </div>
        `;
    }
    
    function showToast(message, type) {
        // Простой toast на Bootstrap
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toastEl.setAttribute('role', 'alert');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => {
            document.body.removeChild(toastEl);
        });
    }
});
</script>