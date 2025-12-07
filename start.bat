@echo off
chcp 65001 > nul
echo ============================================
echo    Hookah Manager - Полная установка
echo ============================================

echo 1. Остановка старых контейнеров...
docker-compose down 2>nul

echo 2. Очистка старых данных...
docker volume rm hookah_postgres_data 2>nul

echo 3. Запуск PostgreSQL и PHP...
docker-compose up -d

echo 4. Ожидание запуска...
timeout /t 5 /nobreak > nul

echo 5. Установка PostgreSQL зависимостей...
docker exec hookah_app apt-get update 2>nul
docker exec hookah_app apt-get install -y libpq-dev postgresql-client 2>nul

echo 6. Установка PDO расширений...
docker exec hookah_app docker-php-ext-install pdo pdo_pgsql 2>nul

echo 7. Установка Composer...
docker exec hookah_app sh -c "curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer" 2>nul

echo 8. Установка зависимостей Laravel...
docker exec hookah_app composer install --no-dev --optimize-autoloader 2>nul

echo 9. Настройка прав доступа...
docker exec hookah_app chmod -R 777 storage bootstrap/cache 2>nul

echo 10. Запуск миграций...
docker exec hookah_app php artisan migrate --force 2>nul

echo 11. Перезапуск PHP...
docker restart hookah_app 2>nul

echo.
echo ============================================
echo            УСТАНОВКА ЗАВЕРШЕНА!
echo ============================================
echo Сайт:      http://localhost:8000
echo База:      PostgreSQL на localhost:5432
echo Логин БД:  hookah_user
echo Пароль БД: secret
echo.
echo Для остановки:  docker-compose down
echo Для логов:     docker-compose logs -f
echo ============================================
echo.
pause