<div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editSaleModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать продажу
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" action="" id="editSaleForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_sale_id" name="sale_id">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Клиент -->
                        <div class="col-md-12">
                            <label for="edit_client_id" class="form-label fw-bold">Клиент</label>
                            <select class="form-select" id="edit_client_id" name="client_id">
                                <option value="">Без клиента</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Склад -->
                        <div class="col-md-12">
                            <label for="edit_warehouse_id" class="form-label fw-bold">Склад</label>
                            <select class="form-select" id="edit_warehouse_id" name="warehouse_id" required>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Комментарий -->
                        <div class="col-12">
                            <label for="edit_comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control" 
                                      id="edit_comment" 
                                      name="comment" 
                                      rows="2"></textarea>
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