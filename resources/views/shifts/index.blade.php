@extends('layouts.app')

@section('title', 'Смены - Календарь')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar-check text-primary me-2"></i>
                Календарь смен
            </h1>
             <p class="text-dark mb-0 fw-bold fs-5">
                @php
                    $months = [
                        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
                        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
                        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
                    ];
                    echo $months[$currentMonth->month] . ' ' . $currentMonth->year;
                @endphp
            </p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Навигация по месяцам -->
            <div class="btn-group" role="group">
                <a href="{{ route('shifts.index', ['month' => $prevMonth->format('Y-m')]) }}" 
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="{{ route('shifts.index', ['month' => $nextMonth->format('Y-m')]) }}" 
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            
            <!-- Кнопка создания смены -->
            <button type="button" 
                    class="btn btn-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createShiftModal">
                <i class="bi bi-plus-circle me-1"></i> Создать смену
            </button>
        </div>
    </div>

    <!-- Сообщения -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Календарь на всю ширину -->
    <div class="px-4">
        <!-- Дни недели (фиксированные) -->
        <div class="row border-bottom bg-light">
            @foreach(['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $day)
            <div class="col border-end">
                <div class="p-3 text-center fw-bold">
                    {{ $day }}
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Дни месяца -->
        @php
            $dayCounter = 0; // Счетчик для шахматного порядка
        @endphp
        
        @foreach($weeks as $weekIndex => $week)
        <div class="row g-0 border-bottom" style="min-height: 160px;">
            @foreach($week as $dayIndex => $date)
            @php
                // Увеличиваем счетчик для КАЖДОГО дня (даже пустых)
                $dayCounter++;
                
                // Четные дни - один цвет, нечетные - другой
                if ($dayCounter % 2 == 0) {
                    $cellBgClass = 'bg-light bg-opacity-25'; // Для четных дней
                } else {
                    $cellBgClass = 'bg-white'; // Для нечетных дней
                }
            @endphp
            
            <div class="col border-end p-2 position-relative d-flex flex-column {{ $cellBgClass }}" style="min-height: 160px;">
                
                @if($date)
                <!-- Заголовок дня -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="{{ $date->month == $currentMonth->month ? 'fw-bold' : 'text-muted' }} {{ $date->isToday() ? 'text-primary' : '' }} fs-6">
                        {{ $date->day }}
                        @if($date->isToday())
                        <span class="badge bg-primary ms-1">Сегодня</span>
                        @endif
                    </div>
                    
                    @if(isset($shifts[$date->format('Y-m-d')]))
                    @php $shift = $shifts[$date->format('Y-m-d')]; @endphp
                    <span class="badge bg-{{ $shift->status_color }} fs-7">
                        {{ $shift->status_text }}
                    </span>
                    @endif
                </div>
                
                <!-- Контент дня -->
                <div class="day-content mt-2 flex-grow-1 d-flex flex-column">
                    @if(isset($shifts[$date->format('Y-m-d')]))
                        @php $shift = $shifts[$date->format('Y-m-d')]; @endphp
                        
                        <!-- Карточка смены -->
                        <div class="p-3 mb-2 rounded border d-flex flex-column flex-grow-1
                            @if($shift->status === 'open') bg-success bg-opacity-10 border-success border-opacity-25
                            @elseif($shift->status === 'closed') bg-light
                            @endif">
                                            
                            <!-- Информация о смене -->
                            <div class="mb-3 flex-grow-1">
                                <!-- Сотрудники -->
                                @if($shift->employees->count() > 0)
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <small class="text-muted fw-bold">
                                            <i class="bi bi-people-fill me-1"></i> Сотрудники ({{ $shift->employees->count() }})
                                        </small>
                                        @if($shift->employees->count() > 3)
                                        <small class="badge bg-secondary">+{{ $shift->employees->count() - 3 }}</small>
                                        @endif
                                    </div>
                                    <div class="employee-list">
                                        @foreach($shift->employees->take(3) as $employee)
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                style="width: 28px; height: 28px; font-size: 0.8rem;">
                                                {{ strtoupper(substr($employee->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small fw-medium">{{ $employee->name }}</div>
                                                @if($employee->position)
                                                <div class="x-small text-muted">{{ $employee->position }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @else
                                <div class="text-center py-2 d-flex flex-column justify-content-center flex-grow-1">
                                    <i class="bi bi-person-x text-muted fs-4"></i>
                                    <div class="small text-muted mt-1">Нет сотрудников</div>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Кнопка Управлять (ДЛЯ ВСЕХ СМЕН) -->
                            <div class="mt-2 mt-auto">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#manageShiftModal"
                                        data-shift-id="{{ $shift->id }}"
                                        data-date="{{ $shift->date->format('d.m.Y') }}"
                                        data-status="{{ $shift->status }}"
                                        data-status-text="{{ $shift->status_text }}"
                                        data-status-color="{{ $shift->status_color }}"
                                        data-employees="{{ $shift->employees->pluck('name')->implode(', ') }}"
                                        data-employees-count="{{ $shift->employees->count() }}"
                                        data-opened-at="{{ $shift->opened_at ? $shift->opened_at->format('d.m.Y H:i') : '' }}"
                                        data-closed-at="{{ $shift->closed_at ? $shift->closed_at->format('d.m.Y H:i') : '' }}"
                                        data-notes="{{ $shift->notes }}">
                                    <i class="bi bi-gear me-1"></i> Управлять
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- День без смены -->
                        <div class="no-shift text-center py-4 d-flex flex-column justify-content-center flex-grow-1">
                            <i class="bi bi-calendar-x text-muted fs-1"></i>
                            <p class="small text-muted mb-3 mt-2">Смены нет</p>
                            
                            <!-- Кнопка Управлять (для создания смены) -->
                            <!-- Теперь показывается ВСЕГДА, если дата существует -->
                            @if($date)
                                <!-- Определяем, в каком месяце должна быть ссылка -->
                                @php
                                    $targetMonth = $date->copy()->format('Y-m');
                                @endphp
                                
                                <a href="{{ route('shifts.index', ['focus' => $date->format('Y-m-d'), 'month' => $targetMonth]) }}" 
                                   class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-gear me-1"></i> Управлять
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
</div>

<!-- Модальное окно создания смены -->
@include('shifts.modals.create')

<!-- Модальное окно управления сменой -->
@include('shifts.modals.manage')

<!-- Модальное окно добавления сотрудников -->
@include('shifts.modals.add-employees')

<style>
/* Только для выравнивания кнопки */
.calendar-cell {
    display: flex;
    flex-direction: column;
}

.calendar-cell > :last-child {
    margin-top: auto;
}

/* Анимация для выделения дня */
.day-highlight {
    animation: highlight-pulse 2s ease;
}

@keyframes highlight-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

/* Стили для дней вне текущего месяца */
.text-muted-day {
    color: #6c757d !important;
}

.bg-out-of-month {
    background-color: #f8f9fa !important;
    opacity: 0.7;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое скрытие алертов
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Заполнение даты при создании смены для конкретного дня
    const createShiftModal = document.getElementById('createShiftModal');
    if (createShiftModal) {
        createShiftModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.dataset.date) {
                document.getElementById('date').value = button.dataset.date;
            }
        });
    }

    @if($focusDate)
        // Ждем полной загрузки DOM
        setTimeout(function() {
            // Ищем день для фокусируемой даты
            const focusDate = '{{ $focusDate }}';
            const formattedDate = new Date(focusDate + 'T00:00:00')
                .toLocaleDateString('ru-RU', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric' 
                })
                .replace(/(\d{2})\.(\d{2})\.(\d{4})/, '$1.$2.$3');
            
            console.log('Ищем дату:', formattedDate, 'в фокусе');
            
            // Ищем все ячейки с днями
            const dayCells = document.querySelectorAll('.col.border-end');
            
            dayCells.forEach(cell => {
                const dayNumber = cell.querySelector('.fw-bold, .text-muted');
                if (dayNumber) {
                    const dayText = dayNumber.textContent.trim();
                    const dayNum = parseInt(dayText.replace('Сегодня', '').replace(/\D/g, '').trim());
                    
                    if (!isNaN(dayNum)) {
                        // Определяем месяц для этой ячейки
                        const isCurrentMonth = !dayNumber.classList.contains('text-muted');
                        const cellMonth = isCurrentMonth ? 
                            '{{ $currentMonth->format("Y-m") }}' : 
                            (dayNum > 20 ? '{{ $prevMonth->format("Y-m") }}' : '{{ $nextMonth->format("Y-m") }}');
                        
                        const cellDateStr = cellMonth + '-' + dayNum.toString().padStart(2, '0');
                        const cellDate = new Date(cellDateStr + 'T00:00:00');
                        
                        if (cellDateStr === focusDate) {
                            console.log('Найдена ячейка для даты:', cellDateStr);
                            
                            // Прокручиваем к элементу
                            cell.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'center',
                                inline: 'center' 
                            });
                            
                            // Добавляем выделение
                            cell.classList.add('day-highlight');
                            cell.style.border = '2px solid #3b82f6';
                            
                            // Ищем кнопку управления в этой ячейке
                            const manageButton = cell.querySelector('button[data-bs-target="#manageShiftModal"]');
                            if (manageButton) {
                                // Открываем модалку управления
                                setTimeout(() => {
                                    manageButton.click();
                                    console.log('Модалка открыта для смены', manageButton.getAttribute('data-shift-id'));
                                }, 1000);
                            } else {
                                // Если кнопки нет (смены нет), но мы уже создали смену через focus параметр
                                // Обновляем страницу через секунду, чтобы показать новую смену
                                setTimeout(() => {
                                    console.log('Смена должна была создаться, обновляем страницу');
                                    window.location.reload();
                                }, 1500);
                            }
                            
                            // Убираем выделение через 3 секунды
                            setTimeout(() => {
                                cell.classList.remove('day-highlight');
                                cell.style.border = '';
                            }, 3000);
                        }
                    }
                }
            });
            
        }, 800); // Увеличил задержку для полной загрузки
    @endif
    
    // Заполнение модалки управления сменой
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
                const employees = button.dataset.employees;
                const employeesCount = button.dataset.employeesCount;
                const openedAt = button.dataset.openedAt;
                const closedAt = button.dataset.closedAt;
                
                // Заполняем поля
                document.getElementById('manage_shift_date').textContent = date;
                document.getElementById('manage_shift_status').textContent = statusText;
                document.getElementById('manage_shift_status').className = `badge bg-${statusColor}`;
                document.getElementById('manage_shift_employees').textContent = employees;
                document.getElementById('manage_shift_employees_count').textContent = employeesCount;
                
                // Время открытия/закрытия
                const openedAtElement = document.getElementById('manage_shift_opened_at');
                const closedAtElement = document.getElementById('manage_shift_closed_at');
                
                if (openedAt) {
                    openedAtElement.textContent = openedAt;
                    openedAtElement.parentElement.style.display = 'block';
                } else {
                    openedAtElement.parentElement.style.display = 'none';
                }
                
                if (closedAt) {
                    closedAtElement.textContent = closedAt;
                    closedAtElement.parentElement.style.display = 'block';
                } else {
                    closedAtElement.parentElement.style.display = 'none';
                }
                
                // Устанавливаем формы открытия/закрытия смены
                const openForm = document.getElementById('openShiftForm');
                const closeForm = document.getElementById('closeShiftForm');
                
                if (openForm) openForm.action = `/shifts/${shiftId}/open`;
                if (closeForm) closeForm.action = `/shifts/${shiftId}/close`;
                
                // Показываем/скрываем кнопки в зависимости от статуса
                if (status === 'planned') {
                    if (openForm) openForm.style.display = 'block';
                    if (closeForm) closeForm.style.display = 'none';
                } else if (status === 'open') {
                    if (openForm) openForm.style.display = 'none';
                    if (closeForm) closeForm.style.display = 'block';
                } else {
                    if (openForm) openForm.style.display = 'none';
                    if (closeForm) closeForm.style.display = 'none';
                }
                
                // Устанавливаем data-shift-id для кнопки управления сотрудниками
                const manageEmployeesBtn = document.querySelector('#manageShiftModal button[data-bs-target="#addEmployeesModal"]');
                if (manageEmployeesBtn) {
                    manageEmployeesBtn.setAttribute('data-shift-id', shiftId);
                    manageEmployeesBtn.setAttribute('data-date', date);
                }
            }
        });
    }
    
    // Установка action для формы добавления сотрудников
    const addEmployeesModal = document.getElementById('addEmployeesModal');
    
    if (addEmployeesModal) {
        addEmployeesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            if (button && button.dataset.shiftId) {
                const shiftId = button.dataset.shiftId;
                const date = button.dataset.date || '';
                
                // Обновляем action формы
                const form = document.getElementById('addEmployeesForm');
                if (form) {
                    form.action = `/shifts/${shiftId}/update-employees`;
                }
                
                // Обновляем заголовок с датой
                const modalTitleDate = document.getElementById('modal_shift_date');
                if (modalTitleDate) {
                    modalTitleDate.textContent = date;
                }
            }
        });
    }
    
    // Плавная анимация при наведении на карточки
    const shiftCards = document.querySelectorAll('.shift-card');
    shiftCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transition = 'all 0.2s ease';
        });
    });
    
    // Обработка успешного закрытия смены с перенаправлением
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('shift_closed')) {
        setTimeout(() => {
            alert('Смена успешно закрыта!');
            // Убираем параметр из URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 500);
    }
});
</script>
@endsection