/**
 * Модуль управления таймером "Требуется кальян"
 * Работает как с серверными, так и с клиентскими таймерами
 */
const HookahTimerManager = {
    // =============== КОНСТАНТЫ ===============
    TIMER_LIMIT_MINUTES: 15,
    TIMER_LIMIT_MS: 15 * 60 * 1000,
    STORAGE_PREFIX: 'hookah_timer_',
    
    // =============== ПУБЛИЧНЫЕ МЕТОДЫ ===============
    
    /**
     * Инициализация таймеров на странице
     */
    init() {
        console.log('🎯 [HookahTimerManager] INIT');
        
        // Очищаем старые таймеры из localStorage
        this.cleanupOldTimers();
        
        // Ждем отрисовки таблицы
        setTimeout(() => {
            this.initAllTimers();
        }, 1000);
        
        return this;
    },
    
    /**
     * Инициализация всех таймеров на странице
     */
    initAllTimers() {
        console.log('⏰ [HookahTimerManager] Initializing all hookah timers...');
        
        // Ищем все ячейки с таблицами
        const tableCells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        console.log(`🔍 Found ${tableCells.length} table cells`);
        
        let timerCount = 0;
        tableCells.forEach((cell, index) => {
            if (this.processTableCell(cell, index)) {
                timerCount++;
            }
        });
        
        console.log(`✅ [HookahTimerManager] Processed ${timerCount} tables with hookah timers`);
    },
    
    /**
     * Обработка ячейки таблицы
     */
    processTableCell(cell, index) {
        // Ищем бейдж статуса
        const statusBadge = cell.querySelector('.badge');
        if (!statusBadge) {
            return false;
        }
        
        const statusText = statusBadge.textContent.trim();
        
        // Проверяем статус "без кальяна"
        const isWithoutHookah = statusText.includes('без кальяна') || 
                               statusText.includes('Открытый стол (без кальяна)');
        
        if (!isWithoutHookah) {
            return false;
        }
        
        // Находим tableId
        const tableIdButton = cell.querySelector('button[data-table-id]');
        if (!tableIdButton) {
            return false;
        }
        
        const tableId = tableIdButton.getAttribute('data-table-id');
        
        // Проверяем, есть ли уже серверный таймер
        const serverTimer = cell.querySelector('.hookah-requirement-timer');
        
        if (serverTimer) {
            console.log(`🔄 Cell ${index}: Activating server timer for table ${tableId}`);
            this.activateServerTimer(serverTimer, tableId);
        } else {
            console.log(`🔄 Cell ${index}: Creating new timer for table ${tableId}`);
            this.createClientTimer(cell, tableId);
        }
        
        return true;
    },
    
    /**
     * Активировать существующий серверный таймер
     */
    activateServerTimer(timerElement, tableId) {
        if (!timerElement || !tableId) return;
        
        console.log(`🎯 Activating server timer for table ${tableId}`);
        
        // Находим элементы отображения
        const timeDisplay = timerElement.querySelector('.time-display');
        const alertElement = timerElement.querySelector('.alert');
        
        if (!timeDisplay || !alertElement) {
            console.error(`❌ No display elements found in server timer`);
            return;
        }
        
        // Получаем или создаем время старта
        const storageKey = `${this.STORAGE_PREFIX}${tableId}`;
        let startTime = localStorage.getItem(storageKey);
        
        if (!startTime) {
            startTime = Date.now();
            localStorage.setItem(storageKey, startTime);
            console.log(`💾 Created timer start time in localStorage: ${startTime}`);
        } else {
            startTime = parseInt(startTime);
        }
        
        // Удаляем старый интервал если есть
        if (timerElement._intervalId) {
            clearInterval(timerElement._intervalId);
        }
        
        // Функция обновления таймера
        const updateTimer = () => {
            const now = Date.now();
            const elapsedMs = now - startTime;
            const remainingMs = Math.max(0, this.TIMER_LIMIT_MS - elapsedMs);
            
            if (remainingMs <= 0) {
                // Время вышло
                timeDisplay.textContent = '00:00';
                timeDisplay.classList.add('text-decoration-line-through');
                
                alertElement.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-alarm-fill me-1 text-danger"></i>
                            <span><strong>Пора поставить кальян!</strong></span>
                        </div>
                    </div>
                `;
                
                alertElement.classList.remove('alert-warning');
                alertElement.classList.add('alert-danger');
            } else {
                const remainingMinutes = Math.floor(remainingMs / 60000);
                const seconds = Math.floor((remainingMs % 60000) / 1000);
                
                const displayMinutes = remainingMinutes.toString().padStart(2, '0');
                const displaySeconds = seconds.toString().padStart(2, '0');
                
                timeDisplay.textContent = `${displayMinutes}:${displaySeconds}`;
                timeDisplay.classList.remove('text-decoration-line-through');
                
                alertElement.classList.remove('alert-warning', 'alert-danger');
                alertElement.classList.add('alert-danger');
            }
        };
        
        // Создаем интервал
        const intervalId = setInterval(updateTimer, 1000);
        
        // Сохраняем ссылки на элементы
        timerElement._intervalId = intervalId;
        timerElement._tableId = tableId;
        timerElement._storageKey = storageKey;
        
        // Первоначальное обновление
        updateTimer();
        
        console.log(`✅ Server timer activated for table ${tableId}`);
    },
    
    /**
     * Создать клиентский таймер
     */
    createClientTimer(cell, tableId) {
        console.log(`📝 Creating client timer for table ${tableId}`);
        
        // Проверяем, не существует ли уже таймер
        if (cell.querySelector('.hookah-requirement-timer')) {
            console.log(`⏭️ Timer already exists for table ${tableId}`);
            return;
        }
        
        // Находим или создаем контейнер
        let timerContainer = cell.querySelector('.timer-container');
        if (!timerContainer) {
            timerContainer = this.createTimerContainer(cell);
        }
        
        // Создаем элемент таймера
        const timerElement = document.createElement('div');
        timerElement.className = 'hookah-requirement-timer';
        timerElement.setAttribute('data-table-id', tableId);
        timerElement.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show p-1 mb-2" role="alert" style="font-size: 0.8rem;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-alarm me-1"></i>
                        <span>Поставьте кальян: <strong class="time-display">15:00</strong></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" 
                            data-bs-dismiss="alert" aria-label="Close" 
                            style="padding: 0.25rem; font-size: 0.6rem;"
                            onclick="if (window.HookahTimerManager) window.HookahTimerManager.resetTimer(${tableId})">
                    </button>
                </div>
            </div>
        `;
        
        timerContainer.appendChild(timerElement);
        
        // Активируем таймер
        this.activateServerTimer(timerElement, tableId);
    },
    
    /**
     * Создать контейнер для таймера
     */
    createTimerContainer(cell) {
        const timerContainer = document.createElement('div');
        timerContainer.className = 'timer-container';
        
        // Находим место для вставки - после заголовка с именем
        const headerDiv = cell.querySelector('.d-flex.justify-content-between.align-items-start.mb-1');
        if (headerDiv && headerDiv.nextElementSibling) {
            cell.insertBefore(timerContainer, headerDiv.nextElementSibling);
        } else {
            const buttonsDiv = cell.querySelector('.d-flex.gap-1.mb-2');
            if (buttonsDiv) {
                buttonsDiv.before(timerContainer);
            } else {
                cell.prepend(timerContainer);
            }
        }
        
        return timerContainer;
    },
    
    /**
     * Остановить таймер (при добавлении кальяна)
     */
    stopTimer(tableId) {
        console.log(`🛑 Stopping hookah timer for table: ${tableId}`);
        
        if (!tableId) {
            console.error('❌ No tableId provided to stopTimer');
            return;
        }
        
        // Удаляем из localStorage
        const storageKey = `${this.STORAGE_PREFIX}${tableId}`;
        localStorage.removeItem(storageKey);
        
        // Находим и останавливаем все таймеры для этого стола
        document.querySelectorAll('.hookah-requirement-timer').forEach(timerElement => {
            if (timerElement._tableId == tableId) {
                this.destroyTimerElement(timerElement);
            }
        });
        
        // Удаляем контейнеры
        this.removeTimerContainers(tableId);
        
        console.log(`✅ Timer stopped for table ${tableId}`);
    },
    
    /**
     * Уничтожить элемент таймера
     */
    destroyTimerElement(timerElement) {
        if (timerElement._intervalId) {
            clearInterval(timerElement._intervalId);
        }
        timerElement.remove();
    },
    
    /**
     * Удалить контейнеры таймеров для стола
     */
    removeTimerContainers(tableId) {
        document.querySelectorAll('.timer-container').forEach(container => {
            const cell = container.closest('td');
            if (cell) {
                const hasButtons = cell.querySelector(`button[data-table-id="${tableId}"]`);
                if (hasButtons) {
                    container.remove();
                }
            }
        });
    },
    
    /**
     * Сбросить таймер (вручную)
     */
    resetTimer(tableId) {
        console.log(`🔄 Resetting timer for table: ${tableId}`);
        
        // Удаляем старое время из localStorage
        const storageKey = `${this.STORAGE_PREFIX}${tableId}`;
        localStorage.removeItem(storageKey);
        
        // Перезапускаем таймер
        document.querySelectorAll(`.hookah-requirement-timer[data-table-id="${tableId}"]`).forEach(timerElement => {
            this.destroyTimerElement(timerElement);
        });
        
        // Находим ячейку и создаем новый таймер
        const cells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        cells.forEach(cell => {
            const tableIdButton = cell.querySelector(`button[data-table-id="${tableId}"]`);
            if (tableIdButton) {
                // Удаляем старый контейнер
                const oldContainer = cell.querySelector('.timer-container');
                if (oldContainer) oldContainer.remove();
                
                // Создаем новый таймер
                this.createClientTimer(cell, tableId);
                
                // Показываем уведомление
                if (typeof showToast === 'function') {
                    showToast('info', 'Таймер сброшен', 'Таймер "требуется кальян" сброшен');
                }
                return;
            }
        });
    },
    
    /**
     * Очистка старых таймеров из localStorage
     */
    cleanupOldTimers() {
        const now = Date.now();
        const oneDayMs = 24 * 60 * 60 * 1000;
        
        console.log('🧹 Cleaning up old timers from localStorage...');
        
        const keysToRemove = [];
        
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith(this.STORAGE_PREFIX)) {
                try {
                    const startTime = parseInt(localStorage.getItem(key));
                    if (now - startTime > oneDayMs) {
                        keysToRemove.push(key);
                    }
                } catch (error) {
                    console.error('Error checking timer key:', key, error);
                }
            }
        }
        
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
        });
        
        console.log(`✅ Cleaned up ${keysToRemove.length} old timers`);
    },
    
    /**
     * Проверить, активен ли таймер для стола
     */
    isTimerActive(tableId) {
        const storageKey = `${this.STORAGE_PREFIX}${tableId}`;
        return !!localStorage.getItem(storageKey);
    },
    
    /**
     * Получить оставшееся время для стола
     */
    getRemainingTime(tableId) {
        const storageKey = `${this.STORAGE_PREFIX}${tableId}`;
        const startTime = localStorage.getItem(storageKey);
        
        if (!startTime) return 0;
        
        const now = Date.now();
        const elapsedMs = now - parseInt(startTime);
        const remainingMs = Math.max(0, this.TIMER_LIMIT_MS - elapsedMs);
        
        return Math.floor(remainingMs / 1000);
    },
    
    /**
     * Обновить интерфейс стола при добавлении кальяна
     */
    updateTableOnHookahAdded(tableId) {
        console.log(`🔄 Updating table interface after hookah added: ${tableId}`);
        this.stopTimer(tableId);
    }
};

// Экспорт модуля
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HookahTimerManager;
} else {
    window.HookahTimerManager = HookahTimerManager;
    console.log('📦 HookahTimerManager loaded to window');
}

// Добавляем глобальные вызовы для HTML кнопок
window.resetHookahTimer = function(tableId) {
    if (window.HookahTimerManager && window.HookahTimerManager.resetTimer) {
        window.HookahTimerManager.resetTimer(tableId);
    }
};