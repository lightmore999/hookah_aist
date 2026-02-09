@extends('layouts.app')

@section('title', 'Клиенты')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Клиенты
            </h1>
            <p class="text-muted mb-0 small">Управление клиентами</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('clients.export-excel') }}" 
            class="btn btn-success"
            title="Экспорт в Excel">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createClientModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить клиента
            </button>
            
            <a href="{{ route('bonus-cards.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-credit-card me-1"></i> Бонусные карты
            </a>
        </div>
    </div>
    
    <!-- Панель поиска и сортировки -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('clients.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label">Поиск клиента</label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Поиск по имени или телефону...">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('clients.index') }}" class="btn btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label for="sort_by" class="form-label">Сортировка</label>
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Дата регистрации</option>
                        <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Имя</option>
                        <option value="total_spent" {{ request('sort_by') == 'total_spent' ? 'selected' : '' }}>Сумма покупок</option>
                        <option value="visits_count" {{ request('sort_by') == 'visits_count' ? 'selected' : '' }}>Количество посещений</option>
                        <option value="bonus_points" {{ request('sort_by') == 'bonus_points' ? 'selected' : '' }}>Бонусные баллы</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="sort_order" class="form-label">Порядок</label>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>По убыванию</option>
                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>По возрастанию</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                </div>
            </form>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($clients->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет клиентов. Добавьте первого!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createClientModal">
                        <i class="bi bi-plus-circle me-1"></i> Добавить клиента
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Имя</th>
                                <th>Телефон</th>
                                <th>Бонусная карта</th>
                                <th>
                                    Потрачено
                                    @if(request('sort_by') == 'total_spent')
                                        <i class="bi bi-arrow-{{ request('sort_order') == 'desc' ? 'down' : 'up' }}"></i>
                                    @endif
                                </th>
                                <th>
                                    Посещений
                                    @if(request('sort_by') == 'visits_count')
                                        <i class="bi bi-arrow-{{ request('sort_order') == 'desc' ? 'down' : 'up' }}"></i>
                                    @endif
                                </th>
                                <th>Бонусы</th>
                                <th>Дата рождения</th>
                                <th>Комментарий</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $client)
                            <tr>
                                <td>
                                    <strong>{{ $client->name }}</strong>
                                </td>
                                <td>{{ $client->phone }}</td>
                                
                                <!-- Столбец "Бонусная карта" -->
                                <td>
                                    @if($client->bonusCard)
                                        <span class="badge bg-info" data-bs-toggle="tooltip" 
                                            title="Требуется трат: {{ number_format($client->bonusCard->RequiredSpendAmount, 2) }} руб.">
                                            <i class="bi bi-credit-card me-1"></i>
                                            {{ $client->bonusCard->Name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                
                                <!-- Столбец "Потрачено" -->
                                <td>
                                    <div class="fw-bold">
                                        {{ number_format($client->total_spent, 2) }} ₽
                                    </div>
                                    @if($client->visits_count > 0)
                                        <div class="small text-muted">
                                            Средний чек: {{ number_format($client->total_spent / $client->visits_count, 2) }} ₽
                                        </div>
                                    @endif
                                </td>
                                
                                <!-- Столбец "Посещений" -->
                                <td>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-cart-check me-1"></i>
                                        {{ $client->visits_count }}
                                    </span>
                                </td>
                                
                                <!-- Столбец "Бонусы" -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Количество бонусов -->
                                        @if($client->bonus_points > 0)
                                            <span class="badge bg-success">
                                                <i class="bi bi-star-fill me-1"></i>
                                                {{ $client->bonus_points }}
                                            </span>
                                        @else
                                            <span class="text-muted small">0</span>
                                        @endif
                                        
                                        <!-- Кнопки операций с бонусами -->
                                        <div class="btn-group btn-group-sm" style="height: 26px;">
                                            <button type="button" 
                                                    class="btn btn-outline-success add-bonus-btn px-2 py-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addBonusModal"
                                                    data-id="{{ $client->id }}"
                                                    data-name="{{ $client->name }}"
                                                    title="Начислить бонусы">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-success subtract-bonus-btn px-2 py-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#subtractBonusModal"
                                                    data-id="{{ $client->id }}"
                                                    data-name="{{ $client->name }}"
                                                    data-bonus-points="{{ $client->bonus_points }}"
                                                    {{ $client->bonus_points <= 0 ? 'disabled' : '' }}
                                                    title="Списать бонусы">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>{{ $client->birth_date ? $client->birth_date->format('d.m.Y') : '-' }}</td>
                                <td>{{ $client->comment ? Str::limit($client->comment, 50) : '-' }}</td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-client-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editClientModal"
                                            data-id="{{ $client->id }}"
                                            data-name="{{ $client->name }}"
                                            data-phone="{{ $client->phone }}"
                                            data-birth-date="{{ $client->birth_date ? $client->birth_date->format('Y-m-d') : '' }}"
                                            data-comment="{{ $client->comment }}"
                                            data-bonus-points="{{ $client->bonus_points }}"
                                            data-bonus-card-id="{{ $client->bonus_card_id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <a href="{{ route('clients.bonus-history', $client) }}" 
                                    class="btn btn-info btn-sm"
                                    title="История бонусов">
                                        <i class="bi bi-clock-history"></i>
                                    </a>

                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-client-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteClientModal"
                                            data-id="{{ $client->id }}"
                                            data-name="{{ $client->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@include('clients.modals.create', ['bonusCards' => \App\Models\BonusCard::all()])
@include('clients.modals.edit', ['bonusCards' => \App\Models\BonusCard::all()])
@include('clients.modals.delete')
@include('clients.modals.bonus-operations')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация тултипов
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Обработчик удаления
    const deleteClientModal = document.getElementById('deleteClientModal');
    if (deleteClientModal) {
        deleteClientModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-client-btn')) {
                document.getElementById('deleteClientName').textContent = button.dataset.name;
                document.getElementById('deleteClientForm').action = `/clients/${button.dataset.id}`;
            }
        });
    }
});
</script>
@endsection