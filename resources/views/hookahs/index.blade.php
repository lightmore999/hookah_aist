@extends('layouts.app')

@section('title', 'Кальяны')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Кальяны
            </h1>
            <p class="text-muted mb-0 small">Управление кальянами</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createHookahModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить кальян
            </button>
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
    
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($hookahs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет кальянов. Добавьте первый!</p>
                    <!-- <a href="{{ route('hookahs.create') }}" class="btn btn-primary mt-2">
                        Добавить кальян
                    </a> -->
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createHookahModal">
                        <i class="bi bi-plus-circle me-1"></i> Добавить кальян
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th>Цена (₽)</th>
                                <th>Себестоимость (₽)</th>
                                <th>Ставка кальянщика</th>
                                <th>Ставка администратора</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hookahs as $hookah)
                            <tr>
                                <td>
                                    <strong>{{ $hookah->name }}</strong>
                                </td>
                                <td>{{ number_format($hookah->price, 2) }}</td>
                                <td>{{ number_format($hookah->cost, 2) }}</td>
                                <td>{{ number_format($hookah->hookah_maker_rate, 2) }}</td>
                                <td>{{ number_format($hookah->administrator_rate, 2) }}</td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-hookah-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editHookahModal"
                                            data-id="{{ $hookah->id }}"
                                            data-name="{{ $hookah->name }}"
                                            data-price="{{ $hookah->price }}"
                                            data-cost="{{ $hookah->cost }}"
                                            data-maker-rate="{{ $hookah->hookah_maker_rate }}"
                                            data-admin-rate="{{ $hookah->administrator_rate }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-hookah-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteHookahModal"
                                            data-id="{{ $hookah->id }}"
                                            data-name="{{ $hookah->name }}">
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


<script>

document.addEventListener('DOMContentLoaded', function() {


 
    const editHookahModal = document.getElementById('editHookahModal');
    if (editHookahModal) {
        editHookahModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-hookah-btn')) {
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_price').value = button.dataset.price;
                document.getElementById('edit_cost').value = button.dataset.cost;
                document.getElementById('edit_hookah_maker_rate').value = button.dataset.makerRate;
                document.getElementById('edit_administrator_rate').value = button.dataset.adminRate;
                document.getElementById('editHookahForm').action = `/hookahs/${button.dataset.id}`;
            }
        });
    }

   const deleteHookahModal = document.getElementById('deleteHookahModal');
    if (deleteHookahModal) {
        deleteHookahModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-hookah-btn')) {
                document.getElementById('deleteHookahName').textContent = button.dataset.name;
                document.getElementById('deleteHookahForm').action = `/hookahs/${button.dataset.id}`;
            }
        });
    }
    
});

</script>

@include('hookahs.modals.create')
@include('hookahs.modals.edit')
@include('hookahs.modals.delete')

@endsection