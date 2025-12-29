<div class="modal fade" id="editHookahModal" tabindex="-1" aria-labelledby="editHookahModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editHookahModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Редактировать кальян
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                
                <form id="editHookahForm" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="modal-body">
                        <!-- Название кальяна -->
                        <div class="mb-4">
                            <label for="edit_name" class="form-label fw-bold">Название кальяна *</label>
                            <input type="text" 
                                class="form-control" 
                                id="edit_name" 
                                name="name" 
                                placeholder="Например: Яблочный кальян, Клубничный микс" 
                                required>
                            <div class="invalid-feedback" id="edit_name_error"></div>
                            <div class="form-text">Укажите название, которое будет отображаться в меню</div>
                        </div>

                        <!-- Цена и себестоимость -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="edit_price" class="form-label fw-bold">Цена продажи (₽) *</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control" 
                                        id="edit_price" 
                                        name="price" 
                                        placeholder="0.00" 
                                        required>
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="invalid-feedback" id="edit_price_error"></div>
                                <div class="form-text">По этой цене кальян будет продаваться</div>
                            </div>

                            <div class="col-md-6">
                                <label for="edit_cost" class="form-label fw-bold">Себестоимость (₽) *</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control" 
                                        id="edit_cost" 
                                        name="cost" 
                                        placeholder="0.00" 
                                        required>
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="invalid-feedback" id="edit_cost_error"></div>
                                <div class="form-text">Стоимость ингредиентов и материалов</div>
                            </div>
                        </div>

                        <!-- Ставки -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="edit_hookah_maker_rate" class="form-label fw-bold">Ставка кальянщика (₽) *</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control" 
                                        id="edit_hookah_maker_rate" 
                                        name="hookah_maker_rate" 
                                        placeholder="0.00" 
                                        required>
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="invalid-feedback" id="edit_hookah_maker_rate_error"></div>
                                <div class="form-text">Оплата за приготовление одного кальяна</div>
                            </div>

                            <div class="col-md-6">
                                <label for="edit_administrator_rate" class="form-label fw-bold">Ставка администратора (₽) *</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control" 
                                        id="edit_administrator_rate" 
                                        name="administrator_rate" 
                                        placeholder="0.00" 
                                        required>
                                    <span class="input-group-text">₽</span>
                                </div>
                                <div class="invalid-feedback" id="edit_administrator_rate_error"></div>
                                <div class="form-text">Оплата за обслуживание одного кальяна</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Отмена
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle me-1"></i>Обновить кальян
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>