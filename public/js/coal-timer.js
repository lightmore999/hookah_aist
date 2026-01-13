// public/js/coal-timer.js
console.log('=== COAL TIMER SYSTEM LOADED ===');

class CoalTimerSystem {
    constructor() {
        this.coalTimers = {};
        console.log('✅ CoalTimerSystem created');
        this.init();
    }

    init() {
        console.log('🔄 Initializing CoalTimerSystem...');
        this.initEventListeners();
        this.cleanupOldTimers();
        this.restoreAllTimers();
    }

    initEventListeners() {
        // Делегирование событий для кнопок таймера углей
        document.addEventListener('click', (e) => {
            // Кнопка "Обновить угли"
            if (e.target.closest('.refresh-coal-btn')) {
                const btn = e.target.closest('.refresh-coal-btn');
                const tableId = btn.dataset.tableId;
                this.refreshCoalTimer(tableId);
                e.preventDefault();
            }

            // Кнопка "Закрыть таймер"
            if (e.target.closest('.close-coal-timer-btn')) {
                const btn = e.target.closest('.close-coal-timer-btn');
                const tableId = btn.dataset.tableId;
                this.closeCoalTimer(tableId);
                e.preventDefault();
            }

            // Кнопка "Открыть таймер углей"
            if (e.target.closest('.open-coal-timer-btn')) {
                const btn = e.target.closest('.open-coal-timer-btn');
                const tableId = btn.dataset.tableId;
                this.openCoalTimer(tableId);
                e.preventDefault();
            }
        });
    }

    // Восстановление всех таймеров при загрузке страницы
    restoreAllTimers() {
        console.log('🔄 Restoring coal timers from localStorage...');
        
        // Очищаем старые интервалы
        Object.values(this.coalTimers).forEach(timer => {
            if (timer.interval) clearInterval(timer.interval);
        });
        this.coalTimers = {};

        // Ждем немного чтобы DOM загрузился
        setTimeout(() => {
            this.findAndRestoreAllTableTimers();
        }, 500);
    }

    // Поиск и восстановление таймеров для всех столов с кальянами
    findAndRestoreAllTableTimers() {
        const tableCells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        console.log(`Found ${tableCells.length} table cells to check`);

        tableCells.forEach((cell, index) => {
            // Ищем стол со статусом "с кальяном"
            const statusBadge = cell.querySelector('.badge');
            if (!statusBadge) return;

            const statusText = statusBadge.textContent.trim();
            if (!statusText.includes('с кальяном')) return;

            // Ищем ID стола
            const tableIdBtn = cell.querySelector('button[data-table-id]');
            if (!tableIdBtn) return;

            const tableId = tableIdBtn.dataset.tableId;
            console.log(`Found table with hookah: ${tableId}, restoring timer...`);
            
            this.checkAndRestoreTimer(tableId, cell);
        });
    }

    // Проверка и восстановление таймера для конкретного стола
    checkAndRestoreTimer(tableId, cell) {
        const isVisible = localStorage.getItem(`coal_timer_visible_${tableId}`) === 'true';
        
        if (isVisible) {
            // Восстанавливаем данные
            const startTime = localStorage.getItem(`coal_timer_start_${tableId}`);
            const count = parseInt(localStorage.getItem(`coal_timer_count_${tableId}`)) || 0;
            
            if (startTime) {
                const now = Date.now();
                const elapsedSeconds = Math.floor((now - parseInt(startTime)) / 1000);
                const timeLeft = Math.max(0, (15 * 60) - elapsedSeconds);
                
                this.showCoalTimer(tableId, cell, timeLeft, count);
            } else {
                // Нет сохраненного времени - создаем новый
                this.showCoalTimer(tableId, cell, 15 * 60, count);
            }
        } else {
            // Таймер закрыт - показываем кнопку открытия
            this.showOpenButton(tableId, cell);
        }
    }

    // Показать блок с активным таймером
    showCoalTimer(tableId, cell, timeLeft = 15 * 60, count = 0) {
        // Удаляем старый блок если есть
        const oldBlock = cell.querySelector('.coal-timer-block');
        if (oldBlock) oldBlock.remove();

        // Создаем placeholder если его нет
        let placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (!placeholder) {
            // Ищем место для вставки placeholder'а
            const firstDiv = cell.querySelector('div');
            if (firstDiv) {
                const placeholderHtml = `<div id="coal-timer-placeholder-${tableId}" style="min-height: 50px;"></div>`;
                firstDiv.insertAdjacentHTML('afterend', placeholderHtml);
                placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
            }
        }

        // Сохраняем состояние
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        if (!localStorage.getItem(`coal_timer_count_${tableId}`)) {
            localStorage.setItem(`coal_timer_count_${tableId}`, count.toString());
        }

        // Определяем цвет для счетчика
        let countColor = 'success';
        if (count >= 3 && count <= 4) countColor = 'warning';
        if (count >= 5) countColor = 'danger';

        // Форматируем время
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Создаем HTML блока
        const html = `
            <div class="coal-timer-block mb-2" data-table-id="${tableId}">
                <div class="alert alert-info alert-dismissible fade show p-1 mb-1" role="alert" style="font-size: 0.8rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-fire me-1"></i>
                            <span>Угли: <strong class="coal-time-display">${timeDisplay}</strong> 
                            <span class="badge bg-${countColor} ms-1">Смен: ${count}</span></span>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-warning refresh-coal-btn" 
                                    data-table-id="${tableId}"
                                    title="Обновить угли">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger close-coal-timer-btn"
                                    data-table-id="${tableId}"
                                    title="Закрыть таймер">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Вставляем в placeholder
        if (placeholder) {
            placeholder.innerHTML = html;
        } else {
            // Fallback: если placeholder не найден
            const backupPlaceholder = cell.querySelector('.d-flex.gap-1.mb-2');
            if (backupPlaceholder) {
                backupPlaceholder.insertAdjacentHTML('afterend', html);
            } else {
                const firstDiv = cell.querySelector('div');
                if (firstDiv) {
                    firstDiv.insertAdjacentHTML('afterend', html);
                }
            }
        }

        // Запускаем таймер если время не истекло
        if (timeLeft > 0) {
            this.startCoalTimer(tableId, timeLeft);
        } else {
            this.updateDisplayForExpired(tableId);
        }
    }

    // Показать только кнопку открытия (таймер закрыт)
    showOpenButton(tableId, cell) {
        // Удаляем старый блок если есть
        const oldBlock = cell.querySelector('.coal-timer-block');
        if (oldBlock) oldBlock.remove();

        // Создаем placeholder если его нет
        let placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (!placeholder) {
            // Ищем место для вставки placeholder'а
            const firstDiv = cell.querySelector('div');
            if (firstDiv) {
                const placeholderHtml = `<div id="coal-timer-placeholder-${tableId}" style="min-height: 50px;"></div>`;
                firstDiv.insertAdjacentHTML('afterend', placeholderHtml);
                placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
            }
        }

        const html = `
            <div class="coal-timer-block mb-2" data-table-id="${tableId}">
                <button type="button" class="btn btn-sm btn-outline-info open-coal-timer-btn w-100"
                        data-table-id="${tableId}">
                    <i class="bi bi-fire me-1"></i> Открыть таймер углей
                </button>
            </div>
        `;

        // Вставляем в placeholder
        if (placeholder) {
            placeholder.innerHTML = html;
        } else {
            // Fallback: если placeholder не найден
            const backupPlaceholder = cell.querySelector('.d-flex.gap-1.mb-2');
            if (backupPlaceholder) {
                backupPlaceholder.insertAdjacentHTML('afterend', html);
            }
        }
    }

    // Запустить таймер
    startCoalTimer(tableId, timeLeft) {
        // Останавливаем старый таймер если есть
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
        }

        // Сохраняем время старта если его нет
        if (!localStorage.getItem(`coal_timer_start_${tableId}`)) {
            localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        }

        // Создаем объект таймера
        this.coalTimers[tableId] = {
            timeLeft: timeLeft,
            interval: setInterval(() => {
                if (this.coalTimers[tableId] && this.coalTimers[tableId].timeLeft > 0) {
                    this.coalTimers[tableId].timeLeft--;
                    this.updateDisplay(tableId, this.coalTimers[tableId].timeLeft);
                } else {
                    // Время вышло
                    clearInterval(this.coalTimers[tableId].interval);
                    this.updateDisplayForExpired(tableId);
                }
            }, 1000)
        };

        // Корректируем время старта с учетом уже прошедшего времени
        const elapsed = (15 * 60) - timeLeft;
        localStorage.setItem(`coal_timer_start_${tableId}`, (Date.now() - (elapsed * 1000)).toString());
    }

    // Обновить отображение таймера
    updateDisplay(tableId, timeLeft) {
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (!block) return;

        const timeDisplay = block.querySelector('.coal-time-display');
        if (!timeDisplay) return;

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        // Меняем цвет в зависимости от времени
        const alert = block.querySelector('.alert');
        if (alert) {
            alert.classList.remove('alert-info', 'alert-warning', 'alert-danger');
            
            if (timeLeft < 60) { // < 1 минута
                alert.classList.add('alert-danger');
            } else if (timeLeft < 180) { // < 3 минуты
                alert.classList.add('alert-warning');
            } else {
                alert.classList.add('alert-info');
            }
        }
    }

    // Обновить отображение когда время вышло
    updateDisplayForExpired(tableId) {
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (!block) return;

        const timeDisplay = block.querySelector('.coal-time-display');
        if (timeDisplay) {
            timeDisplay.textContent = '00:00';
            timeDisplay.classList.add('text-decoration-line-through');
        }

        const alert = block.querySelector('.alert');
        if (alert) {
            alert.classList.remove('alert-info', 'alert-warning');
            alert.classList.add('alert-danger');
        }
    }

    // Обновить угли (сбросить таймер)
    refreshCoalTimer(tableId) {
        console.log(`🔄 Refreshing coal timer for table ${tableId}`);
        
        // Увеличиваем счетчик
        let count = parseInt(localStorage.getItem(`coal_timer_count_${tableId}`)) || 0;
        count++;
        localStorage.setItem(`coal_timer_count_${tableId}`, count.toString());
        
        // Обновляем время старта
        localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        
        // Останавливаем старый таймер
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
        }
        
        // Находим ячейку и обновляем таймер
        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showCoalTimer(tableId, cell, 15 * 60, count);
        }
        
        // Показываем уведомление
        if (typeof showToast === 'function') {
            showToast('success', 'Угли обновлены', `Таймер сброшен на 15 минут (смена #${count})`);
        }
    }

    // Закрыть таймер (показать только кнопку)
    closeCoalTimer(tableId) {
        console.log(`❌ Closing coal timer for table ${tableId}`);
        
        // Останавливаем таймер
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
            delete this.coalTimers[tableId];
        }
        
        // Меняем состояние на закрытое
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'false');
        
        // Находим ячейку и показываем кнопку
        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showOpenButton(tableId, cell);
        }
        
        // Показываем уведомление
        if (typeof showToast === 'function') {
            showToast('info', 'Таймер закрыт', 'Таймер углей скрыт');
        }
    }

    // Открыть таймер (показать блок с таймером)
    openCoalTimer(tableId) {
        console.log(`🔓 Opening coal timer for table ${tableId}`);
        
        // СБРАСЫВАЕМ СЧЕТЧИК ПРИ ОТКРЫТИИ
        localStorage.setItem(`coal_timer_count_${tableId}`, '0');
        
        // Меняем состояние на открытое
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        
        // Находим ячейку
        const cell = this.findTableCell(tableId);
        if (cell) {
            // Показываем таймер с нулевым счетчиком
            this.showCoalTimer(tableId, cell, 15 * 60, 0);
        }
        
        // Показываем уведомление
        if (typeof showToast === 'function') {
            showToast('info', 'Таймер открыт', 'Таймер углей запущен на 15 минут');
        }
    }

    // Найти ячейку стола по ID
    findTableCell(tableId) {
        const cells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        
        for (const cell of cells) {
            const buttons = cell.querySelectorAll('button[data-table-id]');
            for (const button of buttons) {
                if (button.dataset.tableId == tableId) {
                    return cell;
                }
            }
        }
        return null;
    }

    // Вызывается при добавлении кальяна
    onHookahAdded(tableId) {
        console.log(`➕ Hookah added to table ${tableId}, adding coal timer`);
        
        // Сбрасываем все данные таймера для этого стола
        localStorage.setItem(`coal_timer_count_${tableId}`, '0');
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        
        // Ждем обновления DOM и добавляем таймер
        setTimeout(() => {
            const cell = this.findTableCell(tableId);
            if (cell) {
                console.log(`✅ Ячейка найдена для таймера углей, tableId: ${tableId}`);
                this.showCoalTimer(tableId, cell, 15 * 60, 0);
            } else {
                console.error(`❌ Ячейка не найдена для tableId: ${tableId}`);
                
                // Пробуем еще раз через 500ms
                setTimeout(() => {
                    const cell2 = this.findTableCell(tableId);
                    if (cell2) {
                        console.log(`✅ Ячейка найдена при повторной попытке`);
                        this.showCoalTimer(tableId, cell2, 15 * 60, 0);
                    }
                }, 500);
            }
        }, 500);
}

    // Очистить все данные таймера
    clearCoalTimer(tableId) {
        console.log(`🧹 Clearing coal timer for table ${tableId}`);
        
        // Останавливаем таймер
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
            delete this.coalTimers[tableId];
        }
        
        // Удаляем из localStorage
        localStorage.removeItem(`coal_timer_visible_${tableId}`);
        localStorage.removeItem(`coal_timer_start_${tableId}`);
        localStorage.removeItem(`coal_timer_count_${tableId}`);
        
        // Удаляем placeholder из DOM
        const placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (placeholder) {
            placeholder.remove();
        }
        
        // Также удаляем блок если он есть вне placeholder'а
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (block) {
            block.remove();
        }
    }

    // Очистка устаревших таймеров из localStorage
    cleanupOldTimers() {
        const now = Date.now();
        const oneDayMs = 24 * 60 * 60 * 1000;
        let removedCount = 0;
        
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            
            if (key.startsWith('coal_timer_start_')) {
                try {
                    const startTime = parseInt(localStorage.getItem(key));
                    if (now - startTime > oneDayMs) {
                        // Удаляем все связанные ключи
                        const tableId = key.replace('coal_timer_start_', '');
                        localStorage.removeItem(key);
                        localStorage.removeItem(`coal_timer_visible_${tableId}`);
                        localStorage.removeItem(`coal_timer_count_${tableId}`);
                        removedCount++;
                    }
                } catch (error) {
                    console.error('Error cleaning up coal timer:', error);
                }
            }
        }
        
        if (removedCount > 0) {
            console.log(`🧹 Removed ${removedCount} old coal timers from localStorage`);
        }
    }

    // Принудительное восстановление всех таймеров
    forceRestoreAllTimers() {
        console.log('🔄 Force restoring all coal timers...');
        this.restoreAllTimers();
    }

    updateTableStatus(tableId, newStatus) {
        console.log(`CoalTimerSystem: Updating table ${tableId} to ${newStatus}`);
        
        const cell = this.findTableCell(tableId);
        if (!cell) return;
        
        if (newStatus === 'opened_with_hookah') {
            // Стол с кальяном - проверяем таймер углей
            const isVisible = localStorage.getItem(`coal_timer_visible_${tableId}`) === 'true';
            
            if (isVisible) {
                this.checkAndRestoreTimer(tableId, cell);
            } else {
                this.showCoalTimer(tableId, cell, 15 * 60, 0);
            }
        } else if (newStatus === 'opened_without_hookah') {
            // Стол без кальяна - скрываем таймер углей
            this.closeCoalTimer(tableId);
        }
    }
}

// Глобальная инициализация
let coalTimerSystem = null;

function initCoalTimerSystem() {
    if (!coalTimerSystem) {
        coalTimerSystem = new CoalTimerSystem();
        console.log('✅ CoalTimerSystem initialized successfully');
    }
    return coalTimerSystem;
}

// Экспорт в глобальную область
window.CoalTimerSystem = {
    init: initCoalTimerSystem,
    instance: () => coalTimerSystem,
    forceRestore: () => {
        if (coalTimerSystem) {
            coalTimerSystem.forceRestoreAllTimers();
        }
    }
};

// Автоматическая инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        initCoalTimerSystem();
    }, 1000);
});

console.log('✅ Coal Timer System ready');