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
                <!-- Первая строка: Основная информация и кнопки действий -->
                <div class="row mb-4">
                    <!-- Левая колонка: Основная информация (увеличенная) -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Основная информация</h6>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <!-- Дата - увеличенная и выделенная -->
                                <div class="mb-4">
                                    <h3 class="text-primary mb-2" id="manage_shift_date"></h3>
                                    <div class="d-flex align-items-center">
                                        <strong class="me-3">Статус:</strong>
                                        <span id="manage_shift_status" class="badge fs-6 px-3 py-2"></span>
                                    </div>
                                </div>
                                
                                <!-- Время открытия/закрытия -->
                                <div class="mt-auto">
                                    <div id="manage_opened_at_container" class="mb-2" style="display: none;">
                                        <i class="bi bi-play-circle text-success me-2"></i>
                                        <strong>Открыта:</strong> 
                                        <span id="manage_shift_opened_at" class="ms-2"></span>
                                    </div>
                                    <div id="manage_closed_at_container" class="mb-2" style="display: none;">
                                        <i class="bi bi-stop-circle text-danger me-2"></i>
                                        <strong>Закрыта:</strong> 
                                        <span id="manage_shift_closed_at" class="ms-2"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Правая колонка: Кнопки действий (занимает меньше места) -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Действия</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                <!-- Кнопки действий - вертикальное расположение -->
                                <div class="d-grid gap-2">
                                    <form id="openShiftForm" method="POST" style="display: none;">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="bi bi-play-circle me-1"></i>Открыть смену
                                        </button>
                                    </form>
                                    
                                    <form id="closeShiftForm" method="POST" style="display: none;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bi bi-stop-circle me-1"></i>Закрыть смену
                                        </button>
                                    </form>
                                    
                                    <!-- Сообщение, когда смена закрыта -->
                                    <div id="closedShiftMessage" class="text-center p-3 bg-light rounded" style="display: none;">
                                        <i class="bi bi-lock text-muted fs-4 d-block mb-2"></i>
                                        <p class="small text-muted mb-0">Смена закрыта</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Вторая строка: Сотрудники в столбик -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
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
                                <!-- Список сотрудников в столбик -->
                                <div id="manage_shift_employees" class="employee-list">
                                    <!-- Сотрудники будут вставляться сюда динамически -->
                                </div>
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
    
    if (manageShiftModal) {
        manageShiftModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.dataset.shiftId) {
                const shiftId = button.dataset.shiftId;
                const date = button.dataset.date;
                const status = button.dataset.status;
                const statusText = button.dataset.statusText;
                const statusColor = button.dataset.statusColor;
                const employees = button.dataset.employees; // JSON строка с массивом имен
                const employeesCount = button.dataset.employeesCount;
                const openedAt = button.dataset.openedAt;
                const closedAt = button.dataset.closedAt;
                
                // 1. Заполняем основную информацию
                document.getElementById('manage_shift_date').textContent = date;
                document.getElementById('manage_shift_status').textContent = statusText;
                document.getElementById('manage_shift_status').className = `badge bg-${statusColor} fs-6 px-3 py-2`;
                document.getElementById('manage_shift_employees_count').textContent = employeesCount;
                
                // 2. Отображаем сотрудников в столбик
                const employeesContainer = document.getElementById('manage_shift_employees');
                employeesContainer.innerHTML = '';
                
                if (employees && employees !== '[]' && employees !== '') {
                    try {
                        const employeesArray = JSON.parse(employees);
                        if (Array.isArray(employeesArray) && employeesArray.length > 0) {
                            employeesArray.forEach((employeeName, index) => {
                                const employeeItem = document.createElement('div');
                                employeeItem.className = 'd-flex align-items-center mb-2';
                                
                                employeeItem.innerHTML = `
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                         style="width: 32px; height: 32px; font-size: 0.9rem;">
                                        ${employeeName.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">${employeeName}</div>
                                        <div class="small text-muted">Сотрудник</div>
                                    </div>
                                `;
                                employeesContainer.appendChild(employeeItem);
                            });
                        } else {
                            employeesContainer.innerHTML = `
                                <div class="text-center py-4">
                                    <i class="bi bi-person-x text-muted fs-4"></i>
                                    <p class="text-muted mb-0 mt-2">Нет сотрудников</p>
                                </div>
                            `;
                        }
                    } catch (e) {
                        // Если employees не JSON, показываем как текст
                        employeesContainer.innerHTML = `
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                ${employees}
                            </div>
                        `;
                    }
                } else {
                    employeesContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="bi bi-person-x text-muted fs-4"></i>
                            <p class="text-muted mb-0 mt-2">Нет сотрудников</p>
                        </div>
                    `;
                }
                
                // 3. Время открытия/закрытия
                const openedAtContainer = document.getElementById('manage_opened_at_container');
                const closedAtContainer = document.getElementById('manage_closed_at_container');
                
                if (openedAt && openedAt !== '') {
                    document.getElementById('manage_shift_opened_at').textContent = openedAt;
                    openedAtContainer.style.display = 'block';
                } else {
                    openedAtContainer.style.display = 'none';
                }
                
                if (closedAt && closedAt !== '') {
                    document.getElementById('manage_shift_closed_at').textContent = closedAt;
                    closedAtContainer.style.display = 'block';
                } else {
                    closedAtContainer.style.display = 'none';
                }
                
                // 4. Обновляем формы открытия/закрытия
                const openForm = document.getElementById('openShiftForm');
                const closeForm = document.getElementById('closeShiftForm');
                const closedShiftMessage = document.getElementById('closedShiftMessage');
                
                if (openForm) openForm.action = `/shifts/${shiftId}/open?_no_focus=1`;
                if (closeForm) closeForm.action = `/shifts/${shiftId}/close?_no_focus=1`;
                
                // 5. Показываем/скрываем кнопки в зависимости от статуса
                if (status === 'planned') {
                    // Только кнопка "Открыть смену"
                    if (openForm) openForm.style.display = 'block';
                    if (closeForm) closeForm.style.display = 'none';
                    if (closedShiftMessage) closedShiftMessage.style.display = 'none';
                } else if (status === 'open') {
                    // Только кнопка "Закрыть смену"
                    if (openForm) openForm.style.display = 'none';
                    if (closeForm) closeForm.style.display = 'block';
                    if (closedShiftMessage) closedShiftMessage.style.display = 'none';
                } else {
                    // Смена закрыта - скрываем все кнопки, показываем сообщение
                    if (openForm) openForm.style.display = 'none';
                    if (closeForm) closeForm.style.display = 'none';
                    if (closedShiftMessage) closedShiftMessage.style.display = 'block';
                }
                
                // 6. Устанавливаем data-атрибуты для кнопки управления сотрудниками
                const manageEmployeesBtn = document.querySelector('#manageShiftModal .manage-employees-btn');
                if (manageEmployeesBtn) {
                    manageEmployeesBtn.setAttribute('data-shift-id', shiftId);
                    manageEmployeesBtn.setAttribute('data-date', date);
                }
            }
        });
    }
});
</script>