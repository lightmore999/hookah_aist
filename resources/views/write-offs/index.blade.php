@extends('layouts.app')

@section('title', 'Списания')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                @if(isset($warehouse) && $warehouse)
                    Списания со склада: {{ $warehouse->name }}
                @else
                    Все списания
                @endif
            </h1>
            <p class="text-muted mb-0 small">История списаний товаров</p>
        </div>
        
        <div>
            @if(isset($warehouse) && $warehouse)
                <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-outline-secondary mt-2">
                    <i class="bi bi-arrow-left me-1"></i> Назад к складу
                </a>
            @else
                <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary mt-2">
                    <i class="bi bi-arrow-left me-1"></i> К складам
                </a>
            @endif
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
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if(count($writeOffs) == 0)
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">
                        @if(isset($warehouse) && $warehouse)
                            На складе "{{ $warehouse->name }}" нет списаний
                        @else
                            Нет записей о списаниях
                        @endif
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Тип операции</th>
                                <th>Сотрудник</th>
                                @if(!isset($warehouse))
                                    <th>Склад</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($writeOffs as $writeOff)
                            @php
                                $product = $writeOff->product;
                                $packaging = $product->packaging ?? 1;
                                $unit = $product->unit ?? 'шт';
                                $wholePackages = floor($writeOff->quantity / $packaging);
                                $openedQuantity = fmod($writeOff->quantity, $packaging);
                            @endphp
                            <tr>
                                <td>
                                    {{ $writeOff->write_off_date->format('d.m.Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ $product->name ?? 'Товар удален' }}</strong>
                                    @if($product)
                                        <br>
                                        <small class="text-muted">
                                            @if($packaging > 1)
                                                1 уп. = {{ $packaging }} {{ $unit }}
                                            @endif
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="badge bg-danger">
                                            {{ number_format($writeOff->quantity, 3) }} {{ $unit }}
                                        </span>
                                        @if($packaging > 1 && ($wholePackages > 0 || $openedQuantity > 0))
                                            <small class="text-muted mt-1">
                                                @if($wholePackages > 0)
                                                    {{ $wholePackages }} уп.
                                                @endif
                                                @if($openedQuantity > 0)
                                                    @if($wholePackages > 0)+@endif
                                                    {{ number_format($openedQuantity, 3) }} {{ $unit }}
                                                @endif
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @switch($writeOff->operation_type)
                                        @case('spoilage')
                                            <span class="badge bg-danger">Порча</span>
                                            @break
                                        @case('damage')
                                            <span class="badge bg-warning text-dark">Повреждение</span>
                                            @break
                                        @case('expired')
                                            <span class="badge bg-secondary">Просрочка</span>
                                            @break
                                        @case('other')
                                            <span class="badge bg-info">Другое</span>
                                            @break
                                        @default
                                            <span class="badge bg-light text-dark">{{ $writeOff->operation_type }}</span>
                                    @endswitch
                                    @if($writeOff->reason)
                                        <br>
                                        <small class="text-muted">{{ $writeOff->reason }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $writeOff->user->name ?? 'Система' }}
                                </td>
                                @if(!isset($warehouse))
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $writeOff->warehouse->name ?? 'Не указан' }}
                                        </span>
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                {{-- Пагинация удалена --}}
            @endif
        </div>
    </div>
</div>
@endsection