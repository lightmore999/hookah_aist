@extends('layouts.app')

@section('title', 'Управление столами')

@section('content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-table me-2"></i>Управление столами
            </h1>
            <p class="text-muted mb-0 small">Добавление, удаление и изменение порядка столов</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addTableModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить стол
            </button>
        </div>
    </div>

    <!-- Таблица столов -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($tables->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-table text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Столы не добавлены</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th style="width: 50px;">№</th>
                                <th>Название стола</th>
                                <th style="width: 120px;">Статус</th>
                                <th style="width: 100px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="tablesList">
                            @foreach($tables as $table)
                            <tr data-id="{{ $table->id }}" class="{{ $table->is_active ? '' : 'table-secondary' }}">
                                <td>
                                    <div class="drag-handle" title="Перетащите для изменения порядка">
                                        <i class="bi bi-grip-vertical"></i>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $loop->iteration }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-table me-2"></i>
                                        <span class="fw-medium">{{ $table->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input table-status-toggle" 
                                               type="checkbox" 
                                               role="switch"
                                               data-table-id="{{ $table->id }}"
                                               id="status-{{ $table->id }}"
                                               {{ $table->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="status-{{ $table->id }}">
                                            {{ $table->is_active ? 'Активен' : 'Неактивен' }}
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <form action="{{ route('admin.table-names.destroy', $table) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Удалить стол \"{{ $table->name }}\"?')"
                                                title="Удалить стол">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Подсказка -->
    <div class="alert alert-info mt-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle me-3 fs-4"></i>
            <div>
                <strong>Как использовать:</strong>
                <div class="small mt-1">
                    1. Перетаскивайте строки за иконку <i class="bi bi-grip-vertical"></i> для изменения порядка<br>
                    2. Используйте переключатель для активации/деактивации стола<br>
                    3. Неактивные столы не отображаются в расписании
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления стола -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Добавить стол
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.table-names.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tableName" class="form-label">Название стола *</label>
                        <input type="text" 
                               class="form-control" 
                               id="tableName" 
                               name="name"
                               placeholder="Например: 8, VIP-стол, Балкон и т.д."
                               required>
                        <div class="form-text">
                            Название должно быть уникальным
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Подключаем SortableJS для перетаскивания -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    initSortable();
    initEventHandlers();
});

// Инициализация перетаскивания
let sortableInstance = null;

function initSortable() {
    const tablesList = document.getElementById('tablesList');
    
    if (!tablesList) {
        console.log('Element #tablesList not found, skipping Sortable init');
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
            console.log('Drag started');
            evt.item.classList.add('table-row-dragging');
        },
        onEnd: function(evt) {
            console.log('Drag ended');
            evt.item.classList.remove('table-row-dragging');
            updateTableOrder();
        }
    });
    
    console.log('Sortable initialized');
}

// Обновление порядка столов
function updateTableOrder() {
    console.log('Updating table order...');
    
    const tableRows = document.querySelectorAll('#tablesList tr[data-id]');
    const order = Array.from(tableRows).map(row => row.getAttribute('data-id'));
    
    console.log('Order to send:', order);
    
    fetch('{{ route("admin.table-names.update-order") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ order: order })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Update order response:', data);
        if (!data.success) {
            showToast('danger', 'Ошибка', data.message);
            // Перезагружаем для восстановления порядка
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('success', 'Успешно', 'Порядок сохранен');
            // Обновляем номера строк
            updateRowNumbers();
        }
    })
    .catch(error => {
        console.error('Error updating order:', error);
        showToast('danger', 'Ошибка', 'Не удалось обновить порядок');
        setTimeout(() => location.reload(), 1000);
    });
}

// Обновление номеров строк после перетаскивания
function updateRowNumbers() {
    const rows = document.querySelectorAll('#tablesList tr');
    rows.forEach((row, index) => {
        const numberCell = row.querySelector('td:nth-child(2) .badge');
        if (numberCell) {
            numberCell.textContent = index + 1;
        }
    });
}

// Обновление статуса активности (AJAX)
function updateTableStatus(tableId, isActive) {
    console.log('Updating status for table', tableId, 'to', isActive);
    
    fetch(`/admin/table-names/${tableId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Меняем внешний вид строки
            const row = document.querySelector(`tr[data-id="${tableId}"]`);
            if (row) {
                if (isActive) {
                    row.classList.remove('table-secondary');
                } else {
                    row.classList.add('table-secondary');
                }
            }
            showToast('success', 'Успешно', 'Статус обновлен');
        } else {
            // Если ошибка, возвращаем переключатель в исходное состояние
            const checkbox = document.querySelector(`input[data-table-id="${tableId}"]`);
            if (checkbox) {
                checkbox.checked = !isActive;
            }
            showToast('danger', 'Ошибка', data.message || 'Не удалось обновить статус');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const checkbox = document.querySelector(`input[data-table-id="${tableId}"]`);
        if (checkbox) {
            checkbox.checked = !isActive;
        }
        showToast('danger', 'Ошибка', 'Ошибка при обновлении статуса');
    });
}

// Инициализация обработчиков событий
function initEventHandlers() {
    console.log('=== INIT EVENT HANDLERS ===');
    
    // Обработчики для переключателей статуса
    document.querySelectorAll('.table-status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            console.log('Status toggle changed for table:', this.dataset.tableId);
            const tableId = this.getAttribute('data-table-id');
            const isActive = this.checked;
            updateTableStatus(tableId, isActive);
        });
    });
    
    console.log('Event handlers initialized');
}

// Функция для показа уведомлений (для AJAX операций)
function showToast(type, title, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
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

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<style>
.drag-handle {
    cursor: grab;
    color: #6c757d;
    transition: color 0.2s;
    padding: 5px;
}

.drag-handle:hover {
    color: #0d6efd;
    cursor: grabbing;
}

.table-row-dragging {
    background-color: rgba(0, 123, 255, 0.1) !important;
    border: 2px dashed #0d6efd !important;
    opacity: 0.8;
}

.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

/* Стили для Sortable.js */
.sortable-ghost {
    opacity: 0.5;
    background-color: #f8f9fa !important;
}

.sortable-chosen {
    background-color: rgba(13, 110, 253, 0.05) !important;
}

.sortable-drag {
    transform: rotate(2deg);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
@endsection