<div class="modal fade" id="addHookahModal" tabindex="-1" aria-labelledby="addHookahModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addHookahModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить кальян в заказ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('orders.hookah-items.store', $order->IDOrder) }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="IDHookah" class="form-label fw-bold">Кальян *</label>
                        <select class="form-select @error('IDHookah') is-invalid @enderror" 
                                id="IDHookah" 
                                name="IDHookah" 
                                required>
                            <option value="">Выберите кальян</option>
                            @foreach(\App\Models\Hookah::all() as $hookah)
                                <option value="{{ $hookah->id }}" data-price="{{ $hookah->price }}">
                                    {{ $hookah->name }} ({{ number_format($hookah->price, 2) }} ₽)
                                </option>
                            @endforeach
                        </select>
                        @error('IDHookah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Информация о выбранном кальяне -->
                    <div class="card mt-3 border-0 bg-light" id="hookahInfo" style="display: none;">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Цена:</small>
                                    <strong id="hookahPrice">0.00 ₽</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Себестоимость:</small>
                                    <strong id="hookahCost">0.00 ₽</strong>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Кальянщик:</small>
                                    <small id="hookahMakerRate">0.00 ₽</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Администратор:</small>
                                    <small id="hookahAdminRate">0.00 ₽</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Добавить кальян
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hookahSelect = document.getElementById('IDHookah');
    const hookahInfo = document.getElementById('hookahInfo');
    const hookahPrice = document.getElementById('hookahPrice');
    const hookahCost = document.getElementById('hookahCost');
    const hookahMakerRate = document.getElementById('hookahMakerRate');
    const hookahAdminRate = document.getElementById('hookahAdminRate');
    
    // Данные о кальянах (можно получить через AJAX или хранить в data-атрибутах)
    const hookahsData = {
        @foreach(\App\Models\Hookah::all() as $hookah)
            {{ $hookah->id }}: {
                price: {{ $hookah->price }},
                cost: {{ $hookah->cost }},
                maker_rate: {{ $hookah->hookah_maker_rate }},
                admin_rate: {{ $hookah->administrator_rate }}
            },
        @endforeach
    };
    
    if (hookahSelect) {
        hookahSelect.addEventListener('change', function() {
            const hookahId = this.value;
            
            if (hookahId && hookahsData[hookahId]) {
                const hookah = hookahsData[hookahId];
                
                // Показываем информацию
                hookahInfo.style.display = 'block';
                
                // Заполняем данные
                hookahPrice.textContent = hookah.price.toFixed(2) + ' ₽';
                hookahCost.textContent = hookah.cost.toFixed(2) + ' ₽';
                hookahMakerRate.textContent = hookah.maker_rate.toFixed(2) + ' ₽';
                hookahAdminRate.textContent = hookah.admin_rate.toFixed(2) + ' ₽';
            } else {
                // Скрываем информацию
                hookahInfo.style.display = 'none';
            }
        });
    }
});
</script>