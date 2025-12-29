<div class="modal fade" id="createSaleModal" tabindex="-1" aria-labelledby="createSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createSaleModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Создать новую продажу
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('sales.store') }}" method="POST" id="createSaleForm">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Клиент (необязательно)</label>
                        <select class="form-select" id="client_id" name="client_id">
                            <option value="">Без клиента</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="warehouse_id" class="form-label">Склад *</label>
                        <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                            <option value="">Выберите склад</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Создать продажу
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>