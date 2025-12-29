@extends('layouts.app')

@section('title', 'Управление сменой')

@section('content')
<div style="position: absolute; top: 0; left: 0; right: 0; height: 64px; background: transparent;"></div>

<div class="container-fluid py-4" style="margin-top: 64px;">
    
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-calendar-check me-2"></i>Управление сменой
            </h1>
            <p class="text-muted mb-0 small">
                Дата: {{ $shift->date->translatedFormat('d F Y') }}
                | Статус: 
                @if($shift->isOpen())
                <span class="badge bg-success">Открыта</span>
                @else
                <span class="badge bg-danger">Закрыта</span>
                @endif
            </p>
        </div>
        
        <div class="d-flex align-items-center">
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>Назад к списку
            </a>
        </div>
    </div>

    <!-- Сообщения -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Сотрудники на смене -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-people me-2"></i>Сотрудники на смене
                <span class="badge bg-secondary">{{ $shift->employees->count() }}</span>
            </h5>
            
            @if($shift->isOpen())
            <button type="button" 
                    class="btn btn-sm btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addEmployeeModal">
                <i class="bi bi-person-plus me-1"></i>Добавить сотрудника
            </button>
            @endif
        </div>
        
        <div class="card-body">
            @if($shift->employees->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Сотрудник</th>
                            <th>Должность</th>
                            <th>Начало работы</th>
                            <th>Окончание работы</th>
                            <th>Часы</th>
                            <th>Примечание</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shift->employees as $employee)
                        @php
                            $shiftUser = $shift->shiftUsers->where('user_id', $employee->id)->first();
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                        {{ substr($employee->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <strong>{{ $employee->name }}</strong>
                                        <div class="small text-muted">{{ $employee->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $employee->position }}</span>
                            </td>
                            <td>
                                @if($shiftUser && $shiftUser->start_time)
                                <span class="badge bg-success">
                                    {{ $shiftUser->start_time->format('d.m.Y H:i') }}
                                </span>
                                @if($shift->isOpen())
                                <button type="button" 
                                        class="btn btn-sm btn-outline-warning ms-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editTimeModal"
                                        data-employee-id="{{ $employee->id }}"
                                        data-employee-name="{{ $employee->name }}"
                                        data-start-time="{{ $shiftUser->start_time ? $shiftUser->start_time->format('Y-m-d\TH:i') : '' }}"
                                        data-end-time="{{ $shiftUser->end_time ? $shiftUser->end_time->format('Y-m-d\TH:i') : '' }}"
                                        data-notes="{{ $shiftUser->notes }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endif
                                @else
                                @if($shift->isOpen())
                                <form action="{{ route('shifts.start-employee', [$shift, $employee]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-play-fill me-1"></i>Начать
                                    </button>
                                </form>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                                @endif
                            </td>
                            <td>
                                @if($shiftUser && $shiftUser->end_time)
                                <span class="badge bg-danger">
                                    {{ $shiftUser->end_time->format('d.m.Y H:i') }}
                                </span>
                                @elseif($shiftUser && $shiftUser->start_time && $shift->isOpen())
                                <form action="{{ route('shifts.end-employee', [$shift, $employee]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-stop-fill me-1"></i>Завершить
                                    </button>
                                </form>
                                @elseif($shiftUser && $shiftUser->start_time)
                                <span class="badge bg-warning">Не завершил</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($shiftUser && $shiftUser->hours_worked > 0)
                                <span class="badge bg-info">{{ $shiftUser->hours_worked }} ч</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($shiftUser && $shiftUser->notes)
                                <small class="text-muted">{{ $shiftUser->notes }}</small>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($shift->isOpen())
                                <form action="{{ route('shifts.remove-employee', [$shift, $employee]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Удалить сотрудника из смены?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-4">
                <i class="bi bi-people display-4 text-muted"></i>
                <p class="mt-3 text-muted">В смене нет сотрудников</p>
                @if($shift->isOpen())
                <button type="button" 
                        class="btn btn-primary mt-2"
                        data-bs-toggle="modal"
                        data-bs-target="#addEmployeeModal">
                    <i class="bi bi-person-plus me-1"></i>Добавить первого сотрудника
                </button>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Статистика смены -->
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h1 class="display-4 text-primary">{{ $shift->employees->count() }}</h1>
                    <p class="text-muted mb-0">Сотрудников</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h1 class="display-4 text-success">{{ $shift->total_hours }}</h1>
                    <p class="text-muted mb-0">Всего часов</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h1 class="display-4 text-info">
                        @if($shift->isOpen())
                        Открыта
                        @else
                        Закрыта
                        @endif
                    </h1>
                    <p class="text-muted mb-0">Статус</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления сотрудника -->
@include('shifts.modals.add-employee')

<!-- Модальное окно редактирования времени -->
@include('shifts.modals.edit-time')

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: bold;
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
    
    // Модалка редактирования времени
    const editTimeModal = document.getElementById('editTimeModal');
    if (editTimeModal) {
        editTimeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            if (button) {
                const employeeId = button.dataset.employeeId;
                const employeeName = button.dataset.employeeName;
                const startTime = button.dataset.startTime;
                const endTime = button.dataset.endTime;
                const notes = button.dataset.notes;
                
                document.getElementById('editEmployeeName').textContent = employeeName;
                document.getElementById('edit_start_time').value = startTime;
                document.getElementById('edit_end_time').value = endTime;
                document.getElementById('edit_notes').value = notes || '';
                
                // Устанавливаем action формы
                const form = document.getElementById('editTimeForm');
                form.action = `/shifts/{{ $shift->id }}/update-time/${employeeId}`;
            }
        });
    }
});
</script>
@endsection