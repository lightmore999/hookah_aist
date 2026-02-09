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
        
        <!-- Статистика -->
        @if(isset($stats) && $stats['total_operations'] > 0)
        <div class="text-end">
            <div class="small text-muted">Всего операций</div>
            <div class="h4 mb-0">{{ $stats['total_operations'] }}</div>
        </div>
        @endif
    </div>

    <!-- Фильтры -->
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
                
                <div class="col-md-3">
                    <input type="date" 
                           name="date_to" 
                           class="form-control form-control-sm" 
                           value="{{ request('date_to') }}"
                           placeholder="По дату">
                </div>
                
                @if(isset($users) && $users->count() > 0)
                <div class="col-md-4">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Все пользователи</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <div class="col-md-4">
                    <input type="text" 
                           name="search" 
                           class="form-control form-control-sm" 
                           value="{{ request('search') }}"
                           placeholder="Поиск...">
                </div>
                
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-funnel"></i> Применить фильтры
                    </button>
                    <a href="{{ route('operation-history.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Сброс
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- История операций -->
    <div class="row">
        <div class="col-12">
            @if($history->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clock display-1 text-muted"></i>
                        <p class="mt-3 text-muted">Нет операций по заданным фильтрам</p>
                    </div>
                </div>
            @else
                <!-- Просто цикл с карточками -->
                @foreach($history as $record)
                <div class="card border-0 shadow-sm mb-3" data-record-id="{{ $record->id }}" 
                     data-entity-type="{{ $record->entity_type }}" data-entity-id="{{ $record->entity_id }}">
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
                                        elseif($record->action_type == 'add_hookah') {
                                            $badgeClass = 'bg-primary';
                                            $icon = 'plus-circle';
                                        }
                                        elseif($record->action_type == 'remove_hookah') {
                                            $badgeClass = 'bg-dark';
                                            $icon = 'trash';
                                        }
                                        // ДОБАВЛЯЕМ НОВЫЕ ТИПЫ:
                                        elseif($record->action_type == 'add_product') {
                                            $badgeClass = 'bg-success';
                                            $icon = 'cart-plus';
                                        }
                                        elseif($record->action_type == 'remove_product') {
                                            $badgeClass = 'bg-danger';
                                            $icon = 'cart-dash';
                                        }
                                        elseif($record->action_type == 'update_product_quantity') {
                                            $badgeClass = 'bg-warning text-dark';
                                            $icon = 'arrow-up-down';
                                        }
                                        elseif($record->action_type == 'update_product_price') {
                                            $badgeClass = 'bg-info';
                                            $icon = 'currency-dollar';
                                        }
                                    @endphp
                                    
                                    <div class="badge {{ $badgeClass }} me-2 p-2">
                                        <i class="bi bi-{{ $icon }}"></i>
                                    </div>
                                    
                                    <div>
                                        <h6 class="mb-0">
                                            <span class="badge bg-light text-dark border">{{ $record->action_text }}</span>
                                            <span class="badge bg-light text-dark border ms-1">{{ $record->entity_text }}</span>
                                            @if($record->entity_id)
                                                <span class="text-muted">#{{ $record->entity_id }}</span>
                                            @endif
                                        </h6>
                                        <div class="small text-muted">
                                            <i class="bi bi-calendar me-1"></i>{{ $record->created_at->format('d.m.Y') }}
                                            <i class="bi bi-clock ms-2 me-1"></i>{{ $record->created_at->format('H:i:s') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Пользователь -->
                                <div class="d-flex align-items-center mb-2">
                                    @if($record->user)
                                        <div class="small">
                                            <i class="bi bi-person me-1"></i>
                                            <strong>Пользователь:</strong> 
                                            <span class="text-primary">{{ $record->user->name }}</span>
                                            @if($record->user->email)
                                                <span class="text-muted">({{ $record->user->email }})</span>
                                            @endif
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
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        <strong>Комментарий:</strong> {{ $record->comment }}
                                    </div>
                                @endif
                                
                                <!-- Информация о продаже (если это продажа) -->
                                @if($record->entity_type == 'sale' && isset($record->sale) && $record->sale)
                                    <div class="border-top pt-2 mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">
                                                <i class="bi bi-cart me-1"></i> Продажа #{{ $record->sale->id }}
                                            </h6>
                                            <a href="{{ route('sales.show', $record->sale->id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right"></i> Перейти
                                            </a>
                                        </div>
                                        
                                        <div class="row small">
                                            <!-- Основная информация -->
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <strong>Статус:</strong>
                                                    @php
                                                        $statusColors = [
                                                            'new' => 'secondary',
                                                            'in_progress' => 'info',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $color = $statusColors[$record->sale->status] ?? 'secondary';
                                                    @endphp
                                                    <span class="badge bg-{{ $color }}">
                                                        {{ $record->sale->status_text }}
                                                    </span>
                                                </div>
                                                
                                                @if($record->sale->client)
                                                    <div class="mb-1">
                                                        <strong>Клиент:</strong> 
                                                        {{ $record->sale->client->name }}
                                                        @if($record->sale->client->phone)
                                                            <span class="text-muted">({{ $record->sale->client->phone }})</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                <div class="mb-1">
                                                    <strong>Дата продажи:</strong> {{ $record->sale->formatted_sale_date }}
                                                </div>
                                            </div>
                                            
                                            <!-- Финансовая информация -->
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <strong>Сумма:</strong> 
                                                    <span class="fw-bold">{{ number_format($record->sale->total, 2, '.', ' ') }} ₽</span>
                                                </div>
                                                
                                                @if($record->sale->discount > 0)
                                                    <div class="mb-1">
                                                        <strong>Скидка:</strong> 
                                                        <span class="text-danger">-{{ number_format($record->sale->discount, 2, '.', ' ') }} ₽</span>
                                                    </div>
                                                @endif
                                                
                                                @if($record->sale->used_bonus_points > 0)
                                                    <div class="mb-1">
                                                        <strong>Бонусы:</strong> 
                                                        <span class="text-warning">-{{ $record->sale->used_bonus_points }} бон.</span>
                                                    </div>
                                                @endif
                                                
                                                <div class="mb-1">
                                                    <strong>Итог:</strong> 
                                                    <span class="fw-bold text-success">
                                                        {{ number_format($record->sale->final_total, 2, '.', ' ') }} ₽
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Товары в продаже -->
                                            @if($record->sale->items->count() > 0)
                                            <div class="col-12 mt-2">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="small">
                                                        <i class="bi bi-box-seam me-1"></i> 
                                                        Товары ({{ $record->sale->items->count() }})
                                                    </strong>
                                                    <span class="badge bg-light text-dark">
                                                        {{ number_format($record->sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }), 2, '.', ' ') }} ₽
                                                    </span>
                                                </div>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-sm mb-0">
                                                        <tbody>
                                                            @foreach($record->sale->items as $item)
                                                            <tr>
                                                                <td class="ps-0">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="flex-grow-1">
                                                                            {{ $item->product->name ?? 'Товар #' . $item->product_id }}
                                                                        </div>
                                                                        <div class="text-muted small">
                                                                            {{ number_format($item->unit_price, 2, '.', ' ') }} ₽ × {{ $item->quantity }}
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="text-end pe-0 fw-bold">
                                                                    {{ number_format($item->quantity * $item->unit_price, 2, '.', ' ') }} ₽
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            @endif
                                            
                                            <!-- Кальяны в продаже -->
                                            @if($record->sale->hookahs->count() > 0)
                                            <div class="col-12 mt-2">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="small">
                                                        <i class="bi bi-cloud-fog me-1"></i> 
                                                        Кальяны ({{ $record->sale->hookahs->count() }})
                                                    </strong>
                                                    <span class="badge bg-light text-dark">
                                                        {{ number_format($record->sale->hookahs_total, 2, '.', ' ') }} ₽
                                                    </span>
                                                </div>
                                                
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($record->sale->hookahs as $hookah)
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                        {{ $hookah->name }} ({{ number_format($hookah->price, 2, '.', ' ') }} ₽)
                                                    </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif
                                            
                                            <!-- Комментарий к продаже -->
                                            @if($record->sale->comment)
                                            <div class="col-12 mt-2">
                                                <div class="bg-light p-2 rounded small">
                                                    <strong>Комментарий к продаже:</strong> {{ $record->sale->comment }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($record->entity_type == 'sale')
                                    <!-- Если продажа не найдена (удалена) -->
                                    <div class="border-top pt-2 mt-2">
                                        <div class="alert alert-warning py-2 mb-0">
                                            <i class="bi bi-exclamation-triangle me-1"></i> 
                                            Продажа #{{ $record->entity_id }} не найдена (возможно, удалена)
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Правая часть - ID и кнопки -->
                            <div class="text-end">
                                <div class="small text-muted mb-2">
                                    ID операции: {{ $record->id }}
                                </div>
                                
                                <!-- Кнопки действий -->
                                <div class="d-flex gap-2">
                                    <!-- Кнопка показать изменения данных -->
                                    @if($record->old_data || $record->new_data)
                                        <button type="button" 
                                                class="btn btn-outline-info btn-sm show-data-btn"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#dataDetails{{ $record->id }}"
                                                aria-expanded="false"
                                                aria-controls="dataDetails{{ $record->id }}"
                                                data-record-id="{{ $record->id }}">
                                            <i class="bi bi-chevron-down"></i> Изменения
                                        </button>
                                    @endif
                                    
                                    <!-- Кнопка детального просмотра -->
                                    <a href="{{ route('operation-history.show', $record->id) }}" 
                                       class="btn btn-outline-secondary btn-sm"
                                       title="Детальный просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Скрытая секция с данными изменений -->
                        @if($record->old_data || $record->new_data)
                        <div class="collapse mt-3" id="dataDetails{{ $record->id }}">
                            <div class="card card-body bg-light-subtle border-0">
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
                                                        <div class="col-md-3">
                                                            <span class="text-muted">{{ $key }}:</span>
                                                        </div>
                                                        <div class="col-md-9">
                                                            @if(is_array($value))
                                                                <pre class="mb-0 small bg-white p-2 rounded border">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @elseif(is_bool($value))
                                                                <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">
                                                                    {{ $value ? 'Да' : 'Нет' }}
                                                                </span>
                                                            @elseif(is_null($value))
                                                                <span class="text-muted fst-italic">(не указано)</span>
                                                            @else
                                                                <span class="fw-medium">{{ $value }}</span>
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
                                                @php
                                                    $changedFields = [];
                                                    foreach($record->new_data as $key => $newValue) {
                                                        $oldValue = $record->old_data[$key] ?? null;
                                                        if($oldValue != $newValue) {
                                                            $changedFields[$key] = [
                                                                'old' => $oldValue,
                                                                'new' => $newValue
                                                            ];
                                                        }
                                                    }
                                                @endphp
                                                
                                                @if(count($changedFields) > 0)
                                                    @foreach($changedFields as $key => $values)
                                                        <div class="row mb-2 align-items-center">
                                                            <div class="col-md-3">
                                                                <strong class="small">{{ $key }}:</strong>
                                                            </div>
                                                            <div class="col-md-9">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="text-danger bg-danger-subtle p-1 rounded me-2 small">
                                                                        @if(is_array($values['old']))
                                                                            <pre class="mb-0 small">{{ json_encode($values['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                        @elseif(is_bool($values['old']))
                                                                            <span class="badge bg-{{ $values['old'] ? 'success' : 'secondary' }}">
                                                                                {{ $values['old'] ? 'Да' : 'Нет' }}
                                                                            </span>
                                                                        @elseif(is_null($values['old']))
                                                                            <span class="text-muted fst-italic">(не указано)</span>
                                                                        @else
                                                                            {{ $values['old'] }}
                                                                        @endif
                                                                    </div>
                                                                    <i class="bi bi-arrow-right text-muted mx-2"></i>
                                                                    <div class="text-success bg-success-subtle p-1 rounded small">
                                                                        @if(is_array($values['new']))
                                                                            <pre class="mb-0 small">{{ json_encode($values['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                                        @elseif(is_bool($values['new']))
                                                                            <span class="badge bg-{{ $values['new'] ? 'success' : 'secondary' }}">
                                                                                {{ $values['new'] ? 'Да' : 'Нет' }}
                                                                            </span>
                                                                        @elseif(is_null($values['new']))
                                                                            <span class="text-muted fst-italic">(не указано)</span>
                                                                        @else
                                                                            {{ $values['new'] }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="alert alert-info py-2 mb-0 small">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        Данные изменились, но все поля одинаковые (возможно, изменены связи или файлы)
                                                    </div>
                                                @endif
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
                                                        <div class="col-md-3">
                                                            <span class="text-muted">{{ $key }}:</span>
                                                        </div>
                                                        <div class="col-md-9">
                                                            @if(is_array($value))
                                                                <pre class="mb-0 small bg-white p-2 rounded border">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                            @elseif(is_bool($value))
                                                                <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">
                                                                    {{ $value ? 'Да' : 'Нет' }}
                                                                </span>
                                                            @elseif(is_null($value))
                                                                <span class="text-muted fst-italic">(не указано)</span>
                                                            @else
                                                                <span class="fw-medium">{{ $value }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                
                                <!-- Сырые данные (для отладки) -->
                                @if(config('app.debug'))
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
                                                    <div class="small text-muted mb-1">Old Data:</div>
                                                    <pre class="bg-dark text-light p-2 rounded small border"><code>{{ json_encode($record->old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            @endif
                                            @if($record->new_data)
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1">New Data:</div>
                                                    <pre class="bg-dark text-light p-2 rounded small border"><code>{{ json_encode($record->new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                
                <!-- Пагинация -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $history->links('pagination::bootstrap-5') }}
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
    min-width: 110px;
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

.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.table-sm td {
    padding: 0.25rem 0.5rem;
}

.badge {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация иконки при клике
    document.querySelectorAll('.show-data-btn').forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (this.getAttribute('aria-expanded') === 'true') {
                this.innerHTML = '<i class="bi bi-chevron-down"></i> Изменения';
            } else {
                this.innerHTML = '<i class="bi bi-chevron-up"></i> Изменения';
            }
            
            // Прокрутка к раскрытому элементу
            setTimeout(() => {
                if (this.getAttribute('aria-expanded') === 'true') {
                    const targetId = this.getAttribute('data-bs-target');
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            }, 300);
        });
    });
    
    // Подсветка активной карточки
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('border-primary');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('border-primary');
        });
    });
    
    // Автоматическое раскрытие если есть hash в URL
    const hash = window.location.hash;
    if (hash) {
        const element = document.querySelector(hash);
        if (element && element.classList.contains('collapse')) {
            const bsCollapse = new bootstrap.Collapse(element, {
                toggle: true
            });
        }
    }
});
</script>
@endsection