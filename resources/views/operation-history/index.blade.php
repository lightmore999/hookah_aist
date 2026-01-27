@extends('layouts.app')

@section('title', 'История операций')

@section('content')
<div class="container py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-clock-history me-2"></i>История операций
            </h1>
            <p class="text-muted mb-0 small">Все действия в системе</p>
        </div>
    </div>

    <!-- Простые фильтры -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('operation-history.index') }}" class="row g-2">
                <div class="col-md-3">
                    <select name="entity_type" class="form-select form-select-sm">
                        <option value="">Все типы</option>
                        @foreach($entityTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('entity_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <select name="action_type" class="form-select form-select-sm">
                        <option value="">Все действия</option>
                        @foreach($actionTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('action_type') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <input type="date" 
                           name="date_from" 
                           class="form-control form-control-sm" 
                           value="{{ request('date_from') }}"
                           placeholder="С даты">
                </div>
                
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        Фильтр
                    </button>
                    <a href="{{ route('operation-history.index') }}" class="btn btn-outline-secondary btn-sm">
                        Сброс
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- История операций - ПРОСТО -->
    <div class="row">
        <div class="col-12">
            @if($history->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clock display-1 text-muted"></i>
                        <p class="mt-3 text-muted">Нет операций</p>
                    </div>
                </div>
            @else
                <!-- Просто цикл с карточками -->
                @foreach($history as $record)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <!-- Левая часть - основная информация -->
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <!-- Иконка действия -->
                                    @php
                                        $badgeClass = 'bg-secondary';
                                        $icon = 'question-circle';
                                        
                                        if($record->action_type == 'create') {
                                            $badgeClass = 'bg-success';
                                            $icon = 'plus-circle';
                                        } 
                                        elseif($record->action_type == 'update') {
                                            $badgeClass = 'bg-warning text-dark';
                                            $icon = 'pencil';
                                        }
                                        elseif($record->action_type == 'delete') {
                                            $badgeClass = 'bg-danger';
                                            $icon = 'trash';
                                        }
                                        elseif($record->action_type == 'close') {
                                            $badgeClass = 'bg-dark';
                                            $icon = 'door-closed';
                                        }
                                        elseif($record->action_type == 'open') {
                                            $badgeClass = 'bg-info';
                                            $icon = 'door-open';
                                        }
                                    @endphp
                                    
                                    <div class="badge {{ $badgeClass }} me-2 p-2">
                                        <i class="bi bi-{{ $icon }}"></i>
                                    </div>
                                    
                                    <div>
                                        <h6 class="mb-0">
                                            {{ $record->action_text }} 
                                            <span class="text-muted">{{ $record->entity_text }} #{{ $record->entity_id }}</span>
                                        </h6>
                                        <div class="small text-muted">
                                            {{ $record->created_at->format('d.m.Y H:i:s') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Пользователь -->
                                <div class="d-flex align-items-center mb-2">
                                    @if($record->user)
                                        <div class="small">
                                            <strong>Пользователь:</strong> 
                                            {{ $record->user->name }} ({{ $record->user->email }})
                                        </div>
                                    @else
                                        <div class="small text-muted">
                                            <i class="bi bi-robot me-1"></i>Система
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Комментарий если есть -->
                                @if($record->comment)
                                    <div class="bg-light p-2 rounded mb-2 small">
                                        <strong>Комментарий:</strong> {{ $record->comment }}
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Правая часть - ID и кнопки -->
                            <div class="text-end">
                                <div class="small text-muted mb-2">
                                    ID: {{ $record->id }}
                                </div>
                                
                                <!-- Кнопка показать изменения данных -->
                                @if($record->old_data || $record->new_data)
                                    <button type="button" 
                                            class="btn btn-outline-info btn-sm show-data-btn"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#dataDetails{{ $record->id }}"
                                            aria-expanded="false"
                                            aria-controls="dataDetails{{ $record->id }}">
                                        <i class="bi bi-chevron-down"></i> Данные
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Скрытая секция с данными -->
                        @if($record->old_data || $record->new_data)
                        <div class="collapse mt-3" id="dataDetails{{ $record->id }}">
                            <div class="card card-body bg-light">
                                <h6 class="mb-3">
                                    <i class="bi bi-database me-1"></i> Изменения данных
                                </h6>
                                
                                @if($record->action_type == 'create')
                                    <!-- Для создания -->
                                    @if($record->new_data && is_array($record->new_data))
                                        <div class="mb-3">
                                            <strong>Созданные данные:</strong>
                                            <div class="mt-2">
                                                @foreach($record->new_data as $key => $value)
                                                    <div class="row mb-1 small">
                                                        <div class="col-md-4 text-muted">{{ $key }}:</div>
                                                        <div class="col-md-8">
                                                            @if(is_array($value))
                                                                <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @elseif(is_bool($value))
                                                                {{ $value ? 'Да' : 'Нет' }}
                                                            @elseif(is_null($value))
                                                                <span class="text-muted">(пусто)</span>
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                
                                @elseif($record->action_type == 'update')
                                    <!-- Для обновления -->
                                    @if($record->old_data && $record->new_data && is_array($record->old_data) && is_array($record->new_data))
                                        <div class="mb-3">
                                            <strong>Измененные поля:</strong>
                                            <div class="mt-2">
                                                @foreach($record->new_data as $key => $newValue)
                                                    @php
                                                        $oldValue = $record->old_data[$key] ?? null;
                                                    @endphp
                                                    @if($oldValue != $newValue)
                                                        <div class="row mb-2">
                                                            <div class="col-md-3">
                                                                <strong class="small">{{ $key }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="text-danger me-2 small">
                                                                        <del>
                                                                            @if(is_array($oldValue))
                                                                                {{ json_encode($oldValue) }}
                                                                            @elseif(is_bool($oldValue))
                                                                                {{ $oldValue ? 'Да' : 'Нет' }}
                                                                            @elseif(is_null($oldValue))
                                                                                <span class="text-muted">(пусто)</span>
                                                                            @else
                                                                                {{ $oldValue }}
                                                                            @endif
                                                                        </del>
                                                                    </div>
                                                                    <i class="bi bi-arrow-right text-muted mx-2"></i>
                                                                    <div class="text-success small">
                                                                        @if(is_array($newValue))
                                                                            {{ json_encode($newValue) }}
                                                                        @elseif(is_bool($newValue))
                                                                            {{ $newValue ? 'Да' : 'Нет' }}
                                                                        @elseif(is_null($newValue))
                                                                            <span class="text-muted">(пусто)</span>
                                                                        @else
                                                                            {{ $newValue }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                
                                @elseif($record->action_type == 'delete')
                                    <!-- Для удаления -->
                                    @if($record->old_data && is_array($record->old_data))
                                        <div class="mb-3">
                                            <strong>Удаленные данные:</strong>
                                            <div class="mt-2">
                                                @foreach($record->old_data as $key => $value)
                                                    <div class="row mb-1 small">
                                                        <div class="col-md-4 text-muted">{{ $key }}:</div>
                                                        <div class="col-md-8">
                                                            @if(is_array($value))
                                                                <pre class="mb-0 small">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @elseif(is_bool($value))
                                                                {{ $value ? 'Да' : 'Нет' }}
                                                            @elseif(is_null($value))
                                                                <span class="text-muted">(пусто)</span>
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                
                                <!-- Сырые данные (для отладки) -->
                                <div class="mt-3 pt-3 border-top">
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#rawData{{ $record->id }}">
                                        <i class="bi bi-code me-1"></i> Сырые данные
                                    </button>
                                    
                                    <div class="collapse mt-2" id="rawData{{ $record->id }}">
                                        <div class="row">
                                            @if($record->old_data)
                                                <div class="col-md-6">
                                                    <div class="small text-muted">Old Data:</div>
                                                    <pre class="bg-dark text-light p-2 rounded small"><code>{{ json_encode($record->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            @endif
                                            @if($record->new_data)
                                                <div class="col-md-6">
                                                    <div class="small text-muted">New Data:</div>
                                                    <pre class="bg-dark text-light p-2 rounded small"><code>{{ json_encode($record->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                
                <!-- Пагинация -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Стили для улучшения отображения */
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.85rem;
    max-height: 300px;
    overflow-y: auto;
}

.collapse.show {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.show-data-btn {
    transition: all 0.3s;
}

.show-data-btn[aria-expanded="true"] {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
    color: white;
}

.show-data-btn[aria-expanded="true"] i {
    transform: rotate(180deg);
}

.show-data-btn i {
    transition: transform 0.3s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация иконки при клике
    document.querySelectorAll('.show-data-btn').forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (this.getAttribute('aria-expanded') === 'true') {
                this.innerHTML = '<i class="bi bi-chevron-down"></i> Данные';
            } else {
                this.innerHTML = '<i class="bi bi-chevron-up"></i> Данные';
            }
        });
    });
});
</script>
@endsection