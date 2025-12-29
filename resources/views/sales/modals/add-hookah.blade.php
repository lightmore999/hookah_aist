<div class="modal fade" id="addHookahModal" tabindex="-1" aria-labelledby="addHookahModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="addHookahModalLabel">
                    <i class="bi bi-cup-straw me-2"></i>Добавить кальян
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('sales.hookahs.store', $sale->id) }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="hookah_id" class="form-label">Кальян *</label>
                        <select class="form-select" id="hookah_id" name="hookah_id" required>
                            <option value="">Выберите кальян</option>
                            @foreach($hookahs as $hookah)
                            <option value="{{ $hookah->id }}">
                                {{ $hookah->name }} - {{ number_format($hookah->price, 2) }} ₽
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-info">
                        Добавить кальян
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>