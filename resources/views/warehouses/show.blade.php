@extends('layouts.app')

@section('title', 'Склад: ' . $warehouse->name)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('warehouses.index') }}">Склады</a>
            </li>
            <li class="breadcrumb-item active">{{ $warehouse->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-box-seam text-primary me-2"></i>
                Склад: {{ $warehouse->name }}
            </h1>
            <p class="text-muted mb-0">Товары на складе</p>
        </div>
        
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Товары на складе</h5>
        </div>
        <div class="card-body p-0">
            @if($stocks->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">На этом складе нет товаров</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Дата обновления</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                            <tr>
                                <td>
                                    <strong>{{ $stock->product->name }}</strong>
                                </td>
                                <td>{{ $stock->quantity }}</td>
                                <td>{{ $stock->last_updated ? $stock->last_updated->format('d.m.Y H:i') : '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger">
                                            <i class="bi bi-dash-circle"></i> Списать
                                        </button>
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


