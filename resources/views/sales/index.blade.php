@extends('layouts.app')

@section('title', 'Продажи')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Продажи
            </h1>
            <p class="text-muted mb-0 small">Управление продажами</p>
        </div>

        <div>
            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить продажу
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

    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($sales->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет продаж. Добавьте первую!</p>
                    <a href="{{ route('sales.create') }}" class="btn btn-primary mt-2">
                        Добавить продажу
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th>Склад</th>
                                <th>Клиент</th>
                                <th>Количество</th>
                                <th>Способ оплаты</th>
                                <th>Общая сумма (₽)</th>
                                <th>Дата продажи</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                            <tr>
                                <td>
                                    <strong>{{ $sale->product->name }}</strong>
                                </td>
                                <td>{{ $sale->warehouse->name }}</td>
                                <td>{{ $sale->client ? $sale->client->name : '-' }}</td>
                                <td>{{ $sale->quantity }}</td>
                                <td>
                                    @if($sale->payment_method == 'cash')
                                        <span class="badge bg-success">Наличные</span>
                                    @else
                                        <span class="badge bg-primary">Карта</span>
                                    @endif
                                </td>
                                <td>{{ number_format($sale->total, 2) }}</td>
                                <td>{{ $sale->sold_at->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('sales.edit', $sale) }}" 
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


