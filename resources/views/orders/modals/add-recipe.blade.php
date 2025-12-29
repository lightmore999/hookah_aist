<div class="modal fade" id="addRecipeModal" tabindex="-1" aria-labelledby="addRecipeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addRecipeModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить рецепт в заказ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('orders.recipe-items.store', $order->IDOrder) }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="IDRecipes" class="form-label fw-bold">Рецепт *</label>
                        <select class="form-select @error('IDRecipes') is-invalid @enderror" 
                                id="IDRecipes" 
                                name="IDRecipes" 
                                required>
                            <option value="">Выберите рецепт</option>
                            @foreach($recipes as $recipe)
                                <option value="{{ $recipe->id }}" data-price="{{ $recipe->price }}">
                                    {{ $recipe->name }} ({{ number_format($recipe->price, 2) }} ₽)
                                </option>
                            @endforeach
                        </select>
                        @error('IDRecipes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="Quantity" class="form-label fw-bold">Количество *</label>
                            <input type="number" 
                                   min="1" 
                                   class="form-control @error('Quantity') is-invalid @enderror" 
                                   id="Quantity" 
                                   name="Quantity" 
                                   value="1" 
                                   required>
                            @error('Quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="UnitPrice" class="form-label fw-bold">Цена за единицу *</label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control @error('UnitPrice') is-invalid @enderror" 
                                       id="UnitPrice" 
                                       name="UnitPrice" 
                                       required>
                                <span class="input-group-text">₽</span>
                                @error('UnitPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Информация о рецепте -->
                    <div class="card mt-3 border-0 bg-light" id="recipeInfo" style="display: none;">
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <small class="text-muted d-block">Описание:</small>
                                <small id="recipeDescription"></small>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Себестоимость:</small>
                                    <small id="recipeCost">0.00 ₽</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Маржа:</small>
                                    <small id="recipeMargin">0.00 ₽</small>
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
                        Добавить рецепт
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const recipeSelect = document.getElementById('IDRecipes');
    const unitPriceInput = document.getElementById('UnitPrice');
    const recipeInfo = document.getElementById('recipeInfo');
    const recipeDescription = document.getElementById('recipeDescription');
    const recipeCost = document.getElementById('recipeCost');
    const recipeMargin = document.getElementById('recipeMargin');
    
    // Данные о рецептах
    const recipesData = {
        @foreach($recipes as $recipe)
            {{ $recipe->id }}: {
                price: {{ $recipe->price }},
                cost: {{ $recipe->cost }},
                margin: {{ $recipe->margin }},
                description: `{{ $recipe->description ?? '' }}`
            },
        @endforeach
    };
    
    if (recipeSelect) {
        recipeSelect.addEventListener('change', function() {
            const recipeId = this.value;
            
            if (recipeId && recipesData[recipeId]) {
                const recipe = recipesData[recipeId];
                
                // Автозаполнение цены
                unitPriceInput.value = recipe.price;
                
                // Показываем информацию
                recipeInfo.style.display = 'block';
                
                // Заполняем данные
                recipeDescription.textContent = recipe.description || '—';
                recipeCost.textContent = recipe.cost.toFixed(2) + ' ₽';
                recipeMargin.textContent = recipe.margin.toFixed(2) + ' ₽';
            } else {
                // Скрываем информацию
                recipeInfo.style.display = 'none';
            }
        });
    }
});
</script>