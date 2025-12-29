<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editOrderModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать заказ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="editOrderForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_IDOrder" name="IDOrder">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Клиент -->
                        <div class="col-md-6">
                            <label for="edit_IDClient" class="form-label fw-bold">Клиент</label>
                            <select class="form-select @error('IDClient') is-invalid @enderror" 
                                    id="edit_IDClient" 
                                    name="IDClient">
                                <option value="">Выберите клиента</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('IDClient')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Столик -->
                        <div class="col-md-6">
                            <label for="edit_IDTable" class="form-label fw-bold">Столик</label>
                            <select class="form-select @error('IDTable') is-invalid @enderror" 
                                    id="edit_IDTable" 
                                    name="IDTable">
                                <option value="">Выберите столик</option>
                                @foreach($tables as $table)
                                    <option value="{{ $table->id }}">
                                        {{ $table->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('IDTable')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Склад -->
                        <div class="col-md-6">
                            <label for="edit_IDWarehouses" class="form-label fw-bold">Склад</label>
                            <select class="form-select @error('IDWarehouses') is-invalid @enderror" 
                                    id="edit_IDWarehouses" 
                                    name="IDWarehouses">
                                <option value="">Выберите склад</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('IDWarehouses')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Ответственный -->
                        <div class="col-md-6">
                            <label for="edit_UserId" class="form-label fw-bold">Ответственный</label>
                            <select class="form-select @error('UserId') is-invalid @enderror" 
                                    id="edit_UserId" 
                                    name="UserId">
                                <option value="">Выберите пользователя</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('UserId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Финансы -->
                        <div class="col-md-4">
                            <label for="edit_Total" class="form-label fw-bold">Сумма</label>
                            <div class="input-group">
                                <input type="number" 
                                       min="0" 
                                       step="0.01"
                                       class="form-control @error('Total') is-invalid @enderror" 
                                       id="edit_Total" 
                                       name="Total">
                                <span class="input-group-text">₽</span>
                                @error('Total')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="edit_Discount" class="form-label fw-bold">Скидка</label>
                            <div class="input-group">
                                <input type="number" 
                                       min="0" 
                                       step="0.01"
                                       class="form-control @error('Discount') is-invalid @enderror" 
                                       id="edit_Discount" 
                                       name="Discount">
                                <span class="input-group-text">₽</span>
                                @error('Discount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="edit_Tips" class="form-label fw-bold">Чаевые</label>
                            <div class="input-group">
                                <input type="number" 
                                       min="0" 
                                       step="0.01"
                                       class="form-control @error('Tips') is-invalid @enderror" 
                                       id="edit_Tips" 
                                       name="Tips">
                                <span class="input-group-text">₽</span>
                                @error('Tips')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- В долг -->
                        <div class="col-md-6">
                            <label for="edit_On_loan" class="form-label fw-bold">В долг</label>
                            <div class="input-group">
                                <input type="number" 
                                       min="0" 
                                       step="0.01"
                                       class="form-control @error('On_loan') is-invalid @enderror" 
                                       id="edit_On_loan" 
                                       name="On_loan">
                                <span class="input-group-text">₽</span>
                                @error('On_loan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Статус -->
                        <div class="col-md-6">
                            <label for="edit_Status" class="form-label fw-bold">Статус</label>
                            <select class="form-select @error('Status') is-invalid @enderror" 
                                    id="edit_Status" 
                                    name="Status">
                                <option value="new">Новый</option>
                                <option value="in_progress">В работе</option>
                                <option value="completed">Завершен</option>
                                <option value="cancelled">Отменен</option>
                            </select>
                            @error('Status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Комментарий -->
                        <div class="col-12">
                            <label for="edit_Comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control @error('Comment') is-invalid @enderror" 
                                      id="edit_Comment" 
                                      name="Comment" 
                                      rows="3"></textarea>
                            @error('Comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>