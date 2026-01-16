@extends('layouts.app')

@section('title', 'История бонусов клиента')

@section('content')
<div class="container py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">История бонусов</h1>
            <p class="text-muted mb-0">
                Клиент: <strong>{{ $client->name }}</strong> | 
                Баланс: <span class="text-primary fw-bold">{{ number_format($currentBalance, 0) }} баллов</span>
            </p>
        </div>
        <div>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    @if($history->isEmpty())
        <!-- Пустая история -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clock-history display-1 text-muted"></i>
                <p class="mt-3 text-muted">История операций пуста</p>
            </div>
        </div>
    @else
        <!-- Простая таблица -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="140">Дата и время</th>
                            <th>Тип</th>
                            <th>Сумма</th>
                            <th>Причина</th>
                            <th>Баланс после</th>
                            <th>Связанная продажа</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $record)
                        <tr>
                            <!-- Дата и время -->
                            <td>
                                <small class="text-muted">
                                    {{ $record->created_at->format('d.m.Y H:i') }}
                                </small>
                            </td>
                            
                            <!-- Тип операции -->
                            <td>
                                @if($record->operation_type === 'credit')
                                    <span class="badge bg-success">Начисление</span>
                                @else
                                    <span class="badge bg-danger">Списание</span>
                                @endif
                            </td>
                            
                            <!-- Сумма -->
                            <td class="fw-bold {{ $record->operation_type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $record->operation_type === 'credit' ? '+' : '-' }}{{ number_format($record->amount, 0) }}
                            </td>
                            
                            <!-- Причина -->
                            <td>{{ $record->reason ?? '—' }}</td>
                            
                            <!-- Баланс после -->
                            <td>{{ number_format($record->balance_after, 0) }}</td>
                            
                            <!-- Связанная продажа -->
                            <td>
                                @if($record->sale)
                                    <a href="{{ route('sales.show', $record->sale_id) }}" 
                                       class="text-decoration-none">
                                        Продажа #{{ $record->sale_id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            @if($history->hasPages())
                <div class="card-footer">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection