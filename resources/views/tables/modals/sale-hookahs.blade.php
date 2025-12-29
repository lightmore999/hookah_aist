<div class="modal fade" id="saleHookahsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saleHookahsModalLabel">Кальяны</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <!-- Информация -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning" id="saleHookahsInfo">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Выберите стол</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Форма добавления кальяна -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3 text-muted">Добавить кальян</h6>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <select class="form-select" id="hookahSelect">
                                            <option value="">Выберите кальян...</option>
                                            @foreach($hookahsForModal as $hookah)
                                                <option value="{{ $hookah->id }}" 
                                                        data-price="{{ $hookah->price }}">
                                                    {{ $hookah->name }} - {{ number_format($hookah->price, 0) }} ₽
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-warning w-100" id="addHookahBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Добавить кальян
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Таблица кальянов -->
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Кальян</th>
                                        <th>Цена</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody id="hookahsTableBody">
                                    <!-- Кальяны будут загружены через JavaScript -->
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td class="text-end fw-bold">Итого:</td>
                                        <td class="fw-bold">
                                            <span id="hookahsTotalAmount">0</span> ₽
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Закрыть
                </button>
            </div>
        </div>
    </div>
</div>