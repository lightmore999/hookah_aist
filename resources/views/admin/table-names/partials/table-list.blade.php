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
                    <th style="width: 100px;">Статус</th>
                    <th style="width: 80px;">Действия</th>
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