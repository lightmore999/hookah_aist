<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0">Управление столами</h2>
        <p class="text-muted mb-0 small">Добавление, удаление и изменение порядка столов</p>
    </div>
    
    <div>
        <button type="button" 
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addTableModal">
            <i class="bi bi-plus-circle me-1"></i> Добавить стол
        </button>
    </div>
</div>

@if(session('success_tables'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success_tables') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error_tables'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ session('error_tables') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($tables->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-table text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3">Столы не добавлены</p>
                <button type="button" 
                        class="btn btn-sm btn-primary mt-2"
                        data-bs-toggle="modal"
                        data-bs-target="#addTableModal">
                    <i class="bi bi-plus-circle me-1"></i> Добавить первый стол
                </button>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 50px;">№</th>
                            <th>Название стола</th>
                            <th style="width: 120px;">Статус</th>
                            <th style="width: 100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="tablesList">
                        @foreach($tables as $table)
                        <tr data-id="{{ $table->id }}" class="{{ $table->is_active ? '' : 'table-secondary' }}">
                            <td>
                                <div class="drag-handle" title="Перетащите для изменения порядка">
                                    <i class="bi bi-grip-vertical"></i>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table me-2"></i>
                                    <span class="fw-medium">{{ $table->name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input table-status-toggle" 
                                           type="checkbox" 
                                           role="switch"
                                           data-table-id="{{ $table->id }}"
                                           id="status-{{ $table->id }}"
                                           {{ $table->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="status-{{ $table->id }}">
                                        {{ $table->is_active ? 'Активен' : 'Неактивен' }}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger delete-table-btn"
                                        data-table-id="{{ $table->id }}"
                                        data-table-name="{{ $table->name }}"
                                        title="Удалить стол">
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

<div class="alert alert-info mt-3">
    <div class="d-flex align-items-center">
        <i class="bi bi-info-circle me-3 fs-4"></i>
        <div>
            <strong>Как использовать:</strong>
            <div class="small mt-1">
                1. Перетаскивайте строки за иконку <i class="bi bi-grip-vertical"></i> для изменения порядка<br>
                2. Используйте переключатель для активации/деактивации стола<br>
                3. Неактивные столы не отображаются в расписании
            </div>
        </div>
    </div>
</div>