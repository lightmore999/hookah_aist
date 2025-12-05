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
            <a href="{{ route('hookahs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить кальян
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
            @if($hookahs->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет кальянов. Добавьте первый!</p>
                    <a href="{{ route('hookahs.create') }}" class="btn btn-primary mt-2">
                        Добавить кальян
                    </a>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('hookahs.edit', $hookah) }}" 
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