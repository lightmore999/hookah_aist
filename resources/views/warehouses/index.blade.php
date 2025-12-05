@extends('layouts.app')

@section('title', 'Склады')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Склады
            </h1>
            <p class="text-muted mb-0 small">Управление складами</p>
        </div>

        <div class="d-flex gap-3">
            
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить склад
            </a>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Склады</h5>
        </div>
        <div class="card-body p-0">
            @if($warehouses->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет складов. Добавьте первый!</p>
                    <a href="{{ route('warehouses.create') }}" class="btn btn-primary mt-2">
                        Добавить склад
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouses as $warehouse)
                            <tr>
                                <td>
                                    <strong>{{ $warehouse->name }}</strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('warehouses.show', $warehouse) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('warehouses.edit', $warehouse) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

     <div class="d-flex justify-content-end align-items-center mb-4">
        <a href="{{ route('purchases.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить закупку
        </a>
    </div>
    <div class="card border-0 shadow-sm">
        
        <div class="card-header bg-light">
            <h5 class="mb-0">Закупки</h5>
        </div>
        <div class="card-body p-0">
            @if($purchases->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет закупок. Добавьте первую!</p>
                    <a href="{{ route('purchases.create') }}" class="btn btn-success mt-2">
                        Добавить закупку
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th>Склад</th>
                                <th>Количество</th>
                                <th>Цена за единицу (₽)</th>
                                <th>Общая стоимость (₽)</th>
                                <th>Дата закупки</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                            <tr>
                                <td>
                                    <strong>{{ $purchase->product->name }}</strong>
                                </td>
                                <td>{{ $purchase->warehouse->name }}</td>
                                <td>{{ $purchase->quantity }}</td>
                                <td>{{ number_format($purchase->unit_price, 2) }}</td>
                                <td>{{ number_format($purchase->quantity * $purchase->unit_price, 2) }}</td>
                                <td>{{ $purchase->purchase_date->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('purchases.edit', $purchase) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
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
@endsection

