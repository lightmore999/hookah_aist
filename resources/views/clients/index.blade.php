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
        
        <div>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createClientModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить клиента
            </button>
            <a href="{{ route('bonus-cards.index') }}" class="btn btn-outline-primary ms-2">
                <i class="bi bi-credit-card me-1"></i> Бонусные карты
            </a>
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
                                <td>
                                    @if($client->bonusCard)
                                        <span class="badge bg-info" data-bs-toggle="tooltip" title="{{ $client->bonusCard->Name }}">
                                            <i class="bi bi-credit-card me-1"></i>
                                            {{ $client->bonusCard->Name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($client->bonus_points > 0)
                                        <span class="badge bg-success">
                                            <i class="bi bi-star-fill me-1"></i>
                                            {{ $client->bonus_points }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация тултипов
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Обработчик удаления оставляем без изменений
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