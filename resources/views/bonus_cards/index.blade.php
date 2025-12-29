@extends('layouts.app')

@section('title', 'Бонусные карты')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-credit-card me-2"></i>Бонусные карты
            </h1>
            <p class="text-muted mb-0 small">Управление бонусными картами клиентов</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createCardModal">
                <i class="bi bi-plus-circle me-1"></i> Создать карту
            </button>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-primary ms-2">
                <i class="bi bi-people me-1"></i> Клиенты
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row">
        @forelse($bonusCards as $card)
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-credit-card-2-front text-primary me-2"></i>
                                {{ $card->Name }}
                            </h5>
                            <span class="badge bg-info">
                                {{ $card->clients_count }} клиентов
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Условия получения:</small>
                            <p class="mb-1">
                                <i class="bi bi-cash-coin me-1 text-success"></i>
                                Траты от <strong>{{ number_format($card->RequiredSpendAmount, 0, ',', ' ') }} ₽</strong>
                            </p>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Начисление за стол:</small>
                                <span class="badge bg-primary">{{ $card->EarntRantTable }}%</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Начисление с собой:</small>
                                <span class="badge bg-secondary">{{ $card->EarntRantTakeaway }}%</span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Макс. оплата бонусами:</small>
                                <span class="badge bg-warning text-dark">{{ $card->MaxSpendPercent }}%</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Скидка при закрытии:</small>
                                <span class="badge bg-success">{{ $card->TableCloseDiscountPercent }}%</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between">
                                <button type="button" 
                                        class="btn btn-outline-warning btn-sm edit-card-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCardModal"
                                        data-id="{{ $card->IDBonusCard }}"
                                        data-name="{{ $card->Name }}"
                                        data-required="{{ $card->RequiredSpendAmount }}"
                                        data-table="{{ $card->EarntRantTable }}"
                                        data-takeaway="{{ $card->EarntRantTakeaway }}"
                                        data-max="{{ $card->MaxSpendPercent }}"
                                        data-discount="{{ $card->TableCloseDiscountPercent }}">
                                    <i class="bi bi-pencil me-1"></i> Редактировать
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-danger btn-sm delete-card-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteCardModal"
                                        data-id="{{ $card->IDBonusCard }}"
                                        data-name="{{ $card->Name }}">
                                    <i class="bi bi-trash me-1"></i> Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-credit-card display-1 text-muted"></i>
                        <h4 class="mt-4 text-muted">Нет бонусных карт</h4>
                        <p class="text-muted mb-4">Создайте первую бонусную карту для ваших клиентов</p>
                        <button type="button" 
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#createCardModal">
                            <i class="bi bi-plus-circle me-1"></i> Создать карту
                        </button>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

@include('bonus_cards.modals.create')
@include('bonus_cards.modals.edit')
@include('bonus_cards.modals.delete')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Обработчик для модалки УДАЛЕНИЯ
    const deleteModal = document.getElementById('deleteCardModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-card-btn')) {
                const cardId = button.getAttribute('data-id');
                const cardName = button.getAttribute('data-name');
                
                if (cardId) {
                    document.getElementById('deleteCardName').textContent = cardName;
                    const form = document.getElementById('deleteCardForm');
                    form.action = `/bonus-cards/${cardId}`;
                }
            }
        });
    }
    
    // 2. Обработчик для модалки РЕДАКТИРОВАНИЯ
    const editModal = document.getElementById('editCardModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-card-btn')) {
                const cardId = button.getAttribute('data-id');
                
                if (cardId) {
                    // Заполняем поля формы
                    document.getElementById('edit_Name').value = button.getAttribute('data-name');
                    document.getElementById('edit_RequiredSpendAmount').value = button.getAttribute('data-required');
                    document.getElementById('edit_EarntRantTable').value = button.getAttribute('data-table');
                    document.getElementById('edit_EarntRantTakeaway').value = button.getAttribute('data-takeaway');
                    document.getElementById('edit_MaxSpendPercent').value = button.getAttribute('data-max');
                    document.getElementById('edit_TableCloseDiscountPercent').value = button.getAttribute('data-discount');
                    
                    // Устанавливаем action формы
                    const form = document.getElementById('editCardForm');
                    form.action = `/bonus-cards/${cardId}`;
                }
            }
        });
    }
});
</script>

@endsection

@section('scripts')

@endsection