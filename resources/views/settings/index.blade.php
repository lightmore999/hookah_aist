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
                <button class="nav-link active" 
                        id="tables-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#tables" 
                        type="button" 
                        role="tab">
                    <i class="bi bi-table me-1"></i>Столы
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" 
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
        <div class="tab-pane fade show active" 
             id="tables" 
             role="tabpanel" 
             aria-labelledby="tables-tab">
            @include('settings.partials.tables')
        </div>

        <!-- Таб "Способы оплаты" -->
        <div class="tab-pane fade" 
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
document.addEventListener('DOMContentLoaded', function() {
    // Сохраняем активный таб при перезагрузке
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }

    // Сохраняем выбранный таб
    document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            localStorage.setItem('activeSettingsTab', event.target.getAttribute('data-bs-target'));
        });
    });

    // Инициализация для столов
    if (typeof initSortable === 'function') {
        initSortable();
        initEventHandlers();
    }

    // Инициализация для способов оплаты
    const editModal = document.getElementById('editPaymentMethodModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-payment-method-btn')) {
                const form = editModal.querySelector('form');
                form.action = `/payment-methods/${button.dataset.id}`;
                document.getElementById('editName').value = button.dataset.name;
            }
        });
    }

    // Обработчик удаления столов
    document.querySelectorAll('.delete-table-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            const tableName = this.getAttribute('data-table-name');
            
            if (confirm(`Удалить стол "${tableName}"?`)) {
                fetch(`/admin/table-names/${tableId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Ошибка при удалении');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка при удалении');
                });
            }
        });
    });
});
</script>

<style>
/* Стили для drag-and-drop */
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

/* Стили для табов */
.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom: 3px solid #0d6efd;
}

.tab-content {
    padding-top: 20px;
}
</style>
@endsection