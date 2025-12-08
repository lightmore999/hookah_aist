@echo off
chcp 65001 > nul
echo ============================================
echo    Hookah Manager - Полная установка
echo ============================================

echo 1. Остановка старых контейнеров...
docker-compose down 2>nul

echo 2. Удаление старого volume БД...
docker volume rm hookah_aist_postgres_data 2>nul

echo 3. Сборка и запуск контейнеров...
docker-compose up -d --build

echo 4. Ожидание запуска контейнеров...
timeout /t 15 /nobreak > nul

echo 5. Установка системных зависимостей в контейнере...
docker exec hookah_app apt-get update 2>nul
docker exec hookah_app apt-get install -y git unzip zip libpq-dev 2>nul

echo 6. Установка PHP расширений...
docker exec hookah_app docker-php-ext-install pdo pdo_pgsql zip 2>nul

echo 7. Установка Composer...
docker exec hookah_app curl -sS https://getcomposer.org/installer ^| php -- --install-dir=/usr/local/bin --filename=composer 2>nul

echo 8. Установка Laravel зависимостей...
docker exec hookah_app composer install --no-dev --optimize-autoloader 2>nul

echo 9. Настройка .env файла...
if not exist .env (
    copy .env.example .env 2>nul
)

echo 10. Настройка Laravel...
docker exec hookah_app php artisan key:generate --force 2>nul
docker exec hookah_app php artisan storage:link 2>nul
docker exec hookah_app chmod -R 777 storage bootstrap/cache 2>nul

echo 11. Запуск миграций...
docker exec hookah_app php artisan migrate --force 2>nul

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