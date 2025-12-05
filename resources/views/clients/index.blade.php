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
            <a href="{{ route('clients.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить клиента
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
            @if($clients->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет клиентов. Добавьте первого!</p>
                    <a href="{{ route('clients.create') }}" class="btn btn-primary mt-2">
                        Добавить клиента
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Имя</th>
                                <th>Телефон</th>
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
                                <td>{{ $client->birth_date ? $client->birth_date->format('d.m.Y') : '-' }}</td>
                                <td>{{ $client->comment ? Str::limit($client->comment, 50) : '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('clients.edit', $client) }}" 
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


