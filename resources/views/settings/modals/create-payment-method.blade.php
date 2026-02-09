<div class="modal fade" id="createPaymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('payment-methods.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>Добавить способ оплаты
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="Name" class="form-label">Название *</label>
                        <input type="text" 
                               class="form-control @error('Name') is-invalid @enderror" 
                               id="Name" 
                               name="Name" 
                               value="{{ old('Name') }}"
                               required
                               maxlength="100"
                               placeholder="Например: Наличные, Карта, Оплата онлайн">
                        @error('Name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Название должно быть уникальным
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Создать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>