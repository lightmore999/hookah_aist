<div class="modal fade" id="createTableModal" tabindex="-1" aria-labelledby="createTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createTableModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить стол
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('tables.store') }}" method="POST" id="createTableForm">
                @csrf
                <input type="hidden" name="redirect_date" value="{{ $currentDate }}">
                
                <div class="modal-body">
                    <!-- Показываем ошибки валидации в модалке -->
                    @if($errors->any())
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Пожалуйста, исправьте ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Номер стола -->
                    <div class="mb-4">
                        <label for="table_number" class="form-label fw-bold">
                            <span class="text-danger">*</span> Номер стола
                        </label>
                        <select name="table_number" 
                                class="form-select @error('table_number') is-invalid @enderror" 
                                id="table_number" 
                                required>
                            <option value="">-- Выберите стол --</option>
                            <option value="1" {{ old('table_number', 1) == '1' ? 'selected' : '' }}>1</option>
                            <option value="2" {{ old('table_number', 1) == '2' ? 'selected' : '' }}>2</option>
                            <option value="3" {{ old('table_number', 1) == '3' ? 'selected' : '' }}>3</option>
                            <option value="4" {{ old('table_number', 1) == '4' ? 'selected' : '' }}>4</option>
                            <option value="Барная стойка" {{ old('table_number', 1) == 'Барная стойка' ? 'selected' : '' }}>Барная стойка</option>
                            <option value="6" {{ old('table_number', 1) == '6' ? 'selected' : '' }}>6</option>
                            <option value="7" {{ old('table_number', 1) == '7' ? 'selected' : '' }}>7</option>
                        </select>
                        @error('table_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Дата и время -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="booking_date" class="form-label fw-bold">
                                <span class="text-danger">*</span> Дата
                            </label>
                            <input type="date" 
                                   class="form-control @error('booking_date') is-invalid @enderror" 
                                   id="booking_date" 
                                   name="booking_date" 
                                   value="{{ old('booking_date', $currentDate) }}" 
                                   required>
                            @error('booking_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="booking_time" class="form-label fw-bold">
                                <span class="text-danger">*</span> Время
                            </label>
                            <select name="booking_time" 
                                    class="form-select @error('booking_time') is-invalid @enderror" 
                                    id="booking_time" 
                                    required>
                                <option value="">-- Выберите время --</option>
                                @php
                                    // Генерируем времена с 14:00 до 03:30 следующего дня
                                    $start = \Carbon\Carbon::createFromTime(14, 0);
                                    $end = \Carbon\Carbon::createFromTime(3, 30)->addDay();
                                    $currentTime = $start->copy();
                                    
                                    while ($currentTime->lt($end)) {
                                        $timeValue = $currentTime->format('H:i');
                                        $displayTime = $currentTime->format('H:i');
                                        
                                        if ($currentTime->hour < 4) {
                                            $displayTime = $displayTime . ' (след. день)';
                                        }
                                        
                                        // Ставим 14:00 по умолчанию
                                        $selected = old('booking_time', '14:00') == $timeValue ? 'selected' : '';
                                        echo "<option value=\"$timeValue\" $selected>$displayTime</option>";
                                        
                                        $currentTime->addMinutes(30);
                                    }
                                @endphp
                            </select>
                            @error('booking_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Длительность и количество гостей -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="duration" class="form-label fw-bold">
                                <span class="text-danger">*</span> Длительность (минуты)
                            </label>
                            <select name="duration" 
                                    class="form-select @error('duration') is-invalid @enderror" 
                                    id="duration" 
                                    required>
                                <option value="">-- Выберите длительность --</option>
                                <option value="60" {{ old('duration', 120) == '60' ? 'selected' : '' }}>1 час</option>
                                <option value="90" {{ old('duration', 120) == '90' ? 'selected' : '' }}>1.5 часа</option>
                                <option value="120" {{ old('duration', 120) == '120' ? 'selected' : '' }}>2 часа</option>
                                <option value="150" {{ old('duration', 120) == '150' ? 'selected' : '' }}>2.5 часа</option>
                                <option value="180" {{ old('duration', 120) == '180' ? 'selected' : '' }}>3 часа</option>
                                <option value="210" {{ old('duration', 120) == '210' ? 'selected' : '' }}>3.5 часа</option>
                                <option value="240" {{ old('duration', 120) == '240' ? 'selected' : '' }}>4 часа</option>
                                <option value="270" {{ old('duration', 120) == '270' ? 'selected' : '' }}>4.5 часа</option>
                                <option value="300" {{ old('duration', 120) == '300' ? 'selected' : '' }}>5 часов</option>
                                <option value="330" {{ old('duration', 120) == '330' ? 'selected' : '' }}>5.5 часов</option>
                                <option value="360" {{ old('duration', 120) == '360' ? 'selected' : '' }}>6 часов</option>
                            </select>
                            @error('duration')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Выберите длительность бронирования</div>
                        </div>

                        <div class="col-md-6">
                            <label for="guests_count" class="form-label fw-bold">
                                Количество гостей <span class="text-muted small">(необязательно)</span>
                            </label>
                            <input type="number" 
                                   min="1" 
                                   max="50"
                                   class="form-control @error('guests_count') is-invalid @enderror" 
                                   id="guests_count" 
                                   name="guests_count" 
                                   value="{{ old('guests_count') }}" 
                                   placeholder="Введите количество">
                            @error('guests_count')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Клиент -->
                    <div class="mb-4">
                        <label for="client_id" class="form-label fw-bold">Клиент <span class="text-muted small">(необязательно)</span></label>
                        <select name="client_id" 
                                class="form-select @error('client_id') is-invalid @enderror" 
                                id="client_id">
                            <option value="">-- Выберите клиента (необязательно) --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" 
                                        data-client-name="{{ $client->name }}"
                                        data-client-phone="{{ $client->phone }}"
                                        {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} ({{ $client->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Имя гостя -->
                    <div class="mb-4">
                        <label for="guest_name" class="form-label fw-bold">Имя гостя <span class="text-muted small">(необязательно)</span></label>
                        <input type="text" 
                               class="form-control @error('guest_name') is-invalid @enderror" 
                               id="guest_name" 
                               name="guest_name" 
                               value="{{ old('guest_name') }}" 
                               placeholder="Введите имя гостя">
                        @error('guest_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Если не выбран клиент, укажите имя гостя</div>
                    </div>

                    <!-- Телефон -->
                    <div class="mb-4">
                        <label for="phone" class="form-label fw-bold">Телефон <span class="text-muted small">(необязательно)</span></label>
                        <input type="text" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}" 
                               placeholder="+7 (999) 123-45-67">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Комментарий -->
                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold">Комментарий <span class="text-muted small">(необязательно)</span></label>
                        <textarea class="form-control @error('comment') is-invalid @enderror" 
                                  id="comment" 
                                  name="comment" 
                                  rows="3" 
                                  placeholder="Дополнительная информация">{{ old('comment') }}</textarea>
                        @error('comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>