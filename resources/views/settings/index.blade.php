@extends('layouts.app')

@section('title', 'Настройки')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear me-2"></i>Настройки
            </h1>
            <p class="text-muted mb-0 small">Управление системными настройками</p>
        </div>
    </div>

    <!-- Навигация по табам -->
    <div class="mb-4">
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session('active_tab', 'tables') == 'tables') active @endif" 
                        id="tables-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#tables" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-table me-1"></i>Столы
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if(session('active_tab') == 'payment-methods') active @endif" 
                        id="payment-methods-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#payment-methods" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-credit-card me-1"></i>Способы оплаты
                </button>
            </li>
            <!-- Можно добавить больше табов в будущем -->
        </ul>
    </div>

    <!-- Содержимое табов -->
    <div class="tab-content" id="settingsTabsContent">
        <!-- Таб "Столы" -->
        <div class="tab-pane fade @if(session('active_tab', 'tables') == 'tables') show active @endif" 
             id="tables" 
             role="tabpanel" 
             aria-labelledby="tables-tab">
            @include('settings.partials.tables')
        </div>

        <!-- Таб "Способы оплаты" -->
        <div class="tab-pane fade @if(session('active_tab') == 'payment-methods') show active @endif" 
             id="payment-methods" 
             role="tabpanel" 
             aria-labelledby="payment-methods-tab">
            @include('settings.partials.payment-methods')
        </div>
    </div>
</div>

<!-- Модальные окна для столов -->
@include('settings.modals.add-table')
<!-- Модальные окна для способов оплаты -->
@include('settings.modals.create-payment-method')
@include('settings.modals.edit-payment-method')

<!-- Подключаем SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Глобальная переменная для CSRF токена
let csrfToken = '';

document.addEventListener('DOMContentLoaded', function() {
    // Получаем CSRF токен
    csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    console.log('CSRF Token loaded');
    
    // Сохраняем активный таб при перезагрузке
    const activeTab = localStorage.getItem('activeSettingsTab') || '#tables';
    if (activeTab) {
        const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tabTrigger) {
            const tab = new bootstrap.Tab(tabTrigger);
            tab.show();
            
            // Если активный таб - "Столы", инициализируем Sortable после показа
            if (activeTab === '#tables') {
                setTimeout(() => {
                    initSortable();
                    initEventHandlers();
                }, 100);
            }
        }
    }

    // Сохраняем выбранный таб
    document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetTab = event.target.getAttribute('data-bs-target');
            localStorage.setItem('activeSettingsTab', targetTab);
            
            // Инициализируем Sortable при переключении на таб "Столы"
            if (targetTab === '#tables') {
                setTimeout(() => {
                    initSortable();
                    initEventHandlers();
                }, 50);
            }
        });
    });

    // Инициализация при первой загрузке
    if (document.querySelector('#tables.show.active')) {
        setTimeout(() => {
            initSortable();
            initEventHandlers();
        }, 100);
    }
});

// ================== ФУНКЦИИ ДЛЯ ТАБЛИЦЫ СТОЛОВ ==================

let sortableInstance = null;

function initSortable() {
    const tablesList = document.getElementById('tablesList');
    
    if (!tablesList) {
        console.log('Element #tablesList not found');
        return;
    }
    
    console.log('Initializing Sortable on #tablesList');
    
    // Уничтожаем предыдущий экземпляр если есть
    if (sortableInstance) {
        sortableInstance.destroy();
    }
    
    sortableInstance = Sortable.create(tablesList, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onStart: function(evt) {
            evt.item.classList.add('table-row-dragging');
            console.log('Drag started');
        },
        onEnd: function(evt) {
            evt.item.classList.remove('table-row-dragging');
            console.log('Drag ended. Old index:', evt.oldIndex, 'New index:', evt.newIndex);
            
            // Обновляем порядок только если позиция изменилась
            if (evt.oldIndex !== evt.newIndex) {
                updateTableOrder();
            }
        }
    });
}

    function initEventHandlers() {
        console.log('Initializing event handlers');
        
        // Обработчики для переключателей статуса
        document.querySelectorAll('.table-status-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const tableId = this.getAttribute('data-table-id');
                const isActive = this.checked;
                console.log('Status toggle changed for table:', tableId, 'to:', isActive);
                updateTableStatus(tableId, isActive);
            });
        });
    }

        

    // Обновление порядка столов
    function updateTableOrder() {
    const tableRows = document.querySelectorAll('#tablesList tr[data-id]');
    const order = Array.from(tableRows).map(row => parseInt(row.getAttribute('data-id')));
    
    fetch('/admin/table-names/update-order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
            order: order
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Успешно', 'Порядок сохранен');
            updateRowNumbers();
        } else {
            showToast('danger', 'Ошибка', data.message || 'Не удалось сохранить порядок');
        }
    })
    .catch(error => {
        console.error('Error updating order:', error);
        showToast('danger', 'Ошибка', 'Ошибка соединения с сервером');
    });
}

// Обновление статуса активности
function updateTableStatus(tableId, isActive) {
    fetch(`/admin/table-names/${tableId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            is_active: isActive,
            _method: 'PUT'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`tr[data-id="${tableId}"]`);
            if (row) {
                if (isActive) {
                    row.classList.remove('table-secondary');
                } else {
                    row.classList.add('table-secondary');
                }
                const label = row.querySelector(`label[for="status-${tableId}"]`);
                if (label) {
                    label.textContent = isActive ? 'Активен' : 'Неактивен';
                }
            }
            showToast('success', 'Успешно', 'Статус обновлен');
        } else {
            // Откатываем переключатель в случае ошибки
            const checkbox = document.querySelector(`input[data-table-id="${tableId}"]`);
            if (checkbox) {
                checkbox.checked = !isActive;
            }
            showToast('danger', 'Ошибка', data.message || 'Не удалось обновить статус');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        const checkbox = document.querySelector(`input[data-table-id="${tableId}"]`);
        if (checkbox) {
            checkbox.checked = !isActive;
        }
        showToast('danger', 'Ошибка', 'Ошибка соединения с сервером');
    });
}

// Обновление номеров строк
function updateRowNumbers() {
    const rows = document.querySelectorAll('#tablesList tr[data-id]');
    rows.forEach((row, index) => {
        const numberCell = row.querySelector('td:nth-child(2) .badge');
        if (numberCell) {
            numberCell.textContent = index + 1;
        }
    });
}

// Функция для показа уведомлений
function showToast(type, title, message) {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
</script>

<style>

.drag-handle {
    cursor: grab;
    color: #6c757d;
    transition: color 0.2s;
    padding: 5px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.drag-handle:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 4px;
}

.drag-handle:active {
    cursor: grabbing;
}

.table-row-dragging {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(13, 110, 253, 0.05) 100%) !important;
    border: 2px solid rgba(13, 110, 253, 0.3) !important;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    position: relative;
}

.sortable-ghost {
    opacity: 0.4;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.2) 0%, rgba(13, 110, 253, 0.1) 100%) !important;
    border: 2px dashed #0d6efd !important;
}

.sortable-chosen {
    background-color: rgba(13, 110, 253, 0.08) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: scale(1.02);
    transition: transform 0.2s;
}

.sortable-drag {
    transform: rotate(1deg);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    opacity: 0.9;
}

/* Плавные переходы для строк таблицы */
#tablesList tr {
    transition: background-color 0.3s ease, transform 0.3s ease;
}

/* Стили для неактивных столов */
.table-secondary {
    --bs-table-bg: rgba(248, 249, 250, 0.5);
    --bs-table-color: #6c757d;
    opacity: 0.8;
}

/* Улучшенные стили для переключателей */
.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.form-check-input:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
    cursor: pointer;
}

/* Анимация для строк при перетаскивании */
@keyframes highlightRow {
    0% { background-color: rgba(13, 110, 253, 0.1); }
    100% { background-color: transparent; }
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
    animation: none;
}
</style>

@endsection