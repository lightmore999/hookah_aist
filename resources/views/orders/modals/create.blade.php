<div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createOrderModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Создать новый заказ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('orders.store') }}" method="POST" id="createOrderForm">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="IDClient" class="form-label">Клиент (необязательно)</label>
                        <select class="form-select" id="IDClient" name="IDClient">
                            <option value="">Без клиента</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="IDTable" class="form-label">Столик (необязательно)</label>
                        <select class="form-select" id="IDTable" name="IDTable">
                            <option value="">Без столика</option>
                            @foreach($tables as $table)
                                <option value="{{ $table->id }}">
                                    {{ $table->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Скрытые поля с дефолтными значениями -->
                    <input type="hidden" name="Status" value="in_progress">
                    <input type="hidden" name="Total" value="0">
                    <input type="hidden" name="Discount" value="0">
                    <input type="hidden" name="Tips" value="0">
                    <input type="hidden" name="On_loan" value="0">
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Создать заказ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>