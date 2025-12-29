<!-- resources/views/shifts/partials/employees-list.blade.php -->
@php
    // Определяем активного сотрудника
    $isInShift = function($employeeId) use ($shiftEmployees) {
        return in_array($employeeId, $shiftEmployees);
    };
@endphp

<!-- Поиск и фильтры -->
<div class="row mb-4">
    <div class="col-md-8">
        <input type="text" 
               class="form-control" 
               id="searchEmployees" 
               placeholder="Поиск по имени или должности...">
    </div>
    <div class="col-md-4 text-end">
        <div class="form-check form-switch d-inline-block me-3">
            <input class="form-check-input" type="checkbox" id="showAll" checked>
            <label class="form-check-label" for="showAll">Все сотрудники</label>
        </div>
    </div>
</div>

<!-- Статистика -->
<div class="alert alert-info mb-3">
    <div class="row">
        <div class="col-md-4">
            <i class="bi bi-person-check me-1"></i>
            <span id="selectedCount">{{ count($shiftEmployees) }}</span> выбрано
        </div>
        <div class="col-md-4">
            <i class="bi bi-person-plus me-1"></i>
            <span id="inShiftCount">{{ count($shiftEmployees) }}</span> в смене
        </div>
        <div class="col-md-4">
            <i class="bi bi-people me-1"></i>
            <span id="totalCount">{{ $allEmployees->count() }}</span> всего
        </div>
    </div>
</div>

<!-- Список сотрудников -->
<div class="row" id="employeesContainer">
    @foreach($allEmployees as $employee)
    <div class="col-md-4 mb-3 employee-item" 
         data-name="{{ strtolower($employee->name) }}"
         data-position="{{ strtolower($employee->position ?? '') }}">
        <div class="card {{ $isInShift($employee->id) ? 'border-primary' : '' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">{{ $employee->name }}</h6>
                        @if($employee->position)
                        <small class="text-muted d-block">{{ $employee->position }}</small>
                        @endif
                    </div>
                    <div class="form-check">
                        <input class="form-check-input employee-checkbox" 
                               type="checkbox" 
                               name="employee_ids[]" 
                               value="{{ $employee->id }}"
                               {{ $isInShift($employee->id) ? 'checked' : '' }}>
                    </div>
                </div>
                @if($isInShift($employee->id))
                <div class="mt-2">
                    <span class="badge bg-primary">
                        <i class="bi bi-check-circle me-1"></i>В смене
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($allEmployees->isEmpty())
<div class="text-center py-5">
    <i class="bi bi-people display-4 text-muted"></i>
    <p class="mt-3 text-muted">Нет сотрудников в системе</p>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Поиск сотрудников
    const searchInput = document.getElementById('searchEmployees');
    const showAllCheckbox = document.getElementById('showAll');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterEmployees();
        });
    }
    
    if (showAllCheckbox) {
        showAllCheckbox.addEventListener('change', function() {
            filterEmployees();
        });
    }
    
    function filterEmployees() {
        const searchTerm = searchInput.value.toLowerCase();
        const showAll = showAllCheckbox.checked;
        
        document.querySelectorAll('.employee-item').forEach(item => {
            const name = item.dataset.name;
            const position = item.dataset.position;
            const isInShift = item.querySelector('.employee-checkbox').checked;
            
            const matchesSearch = name.includes(searchTerm) || position.includes(searchTerm);
            const shouldShow = (showAll || isInShift) && matchesSearch;
            
            item.style.display = shouldShow ? 'block' : 'none';
        });
    }
    
    // Подсчет выбранных
    function updateCounts() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        document.getElementById('selectedCount').textContent = checkedCount;
        document.getElementById('inShiftCount').textContent = checkedCount;
    }
    
    // Обновляем счетчики при изменении чекбоксов
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('employee-checkbox')) {
            updateCounts();
            
            // Подсвечиваем карточку
            const card = e.target.closest('.card');
            if (e.target.checked) {
                card.classList.add('border-primary');
            } else {
                card.classList.remove('border-primary');
            }
        }
    });
    
    // Клик по карточке - выбирает чекбокс
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.card');
        if (card && !e.target.closest('.form-check')) {
            const checkbox = card.querySelector('.employee-checkbox');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        }
    });
    
    // Инициализируем счетчики
    updateCounts();
});
</script>

<style>
.card {
    cursor: pointer;
    transition: all 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.card.border-primary {
    border-width: 2px;
}
</style>