# setup.ps1 - РќР°СЃС‚СЂРѕР№РєР° Hookah Manager
Write-Host "=== РќР°СЃС‚СЂРѕР№РєР° Hookah Manager ===" -ForegroundColor Cyan

# 1. РћСЃС‚Р°РЅР°РІР»РёРІР°РµРј СЃС‚Р°СЂС‹Рµ РєРѕРЅС‚РµР№РЅРµСЂС‹
Write-Host "1. РћСЃС‚Р°РЅРѕРІРєР° СЃС‚Р°СЂС‹С… РєРѕРЅС‚РµР№РЅРµСЂРѕРІ..." -ForegroundColor Yellow
docker-compose down 2>$null

# 2. Р—Р°РїСѓСЃРєР°РµРј РєРѕРЅС‚РµР№РЅРµСЂС‹
Write-Host "2. Р—Р°РїСѓСЃРє PostgreSQL Рё PHP..." -ForegroundColor Yellow
docker-compose up -d

# 3. Р–РґРµРј Р·Р°РїСѓСЃРєР° PostgreSQL
Write-Host "3. РћР¶РёРґР°РЅРёРµ Р·Р°РїСѓСЃРєР° PostgreSQL..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# 4. РЈСЃС‚Р°РЅР°РІР»РёРІР°РµРј СЂР°СЃС€РёСЂРµРЅРёСЏ PostgreSQL
Write-Host "4. РЈСЃС‚Р°РЅРѕРІРєР° СЂР°СЃС€РёСЂРµРЅРёР№ PostgreSQL РґР»СЏ PHP..." -ForegroundColor Yellow
docker exec hookah_app docker-php-ext-install pdo pdo_pgsql 2>$null

# 5. РЈСЃС‚Р°РЅР°РІР»РёРІР°РµРј Composer
Write-Host "5. РЈСЃС‚Р°РЅРѕРІРєР° Composer..." -ForegroundColor Yellow
docker exec hookah_app sh -c "curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer" 2>$null

# 6. РЈСЃС‚Р°РЅР°РІР»РёРІР°РµРј Р·Р°РІРёСЃРёРјРѕСЃС‚Рё Laravel
Write-Host "6. РЈСЃС‚Р°РЅРѕРІРєР° Р·Р°РІРёСЃРёРјРѕСЃС‚РµР№ Laravel..." -ForegroundColor Yellow
docker exec hookah_app composer install --no-dev --optimize-autoloader 2>$null

# 7. РќР°СЃС‚СЂР°РёРІР°РµРј РїСЂР°РІР°
Write-Host "7. РќР°СЃС‚СЂРѕР№РєР° РїСЂР°РІ РґРѕСЃС‚СѓРїР°..." -ForegroundColor Yellow
docker exec hookah_app chmod -R 777 storage bootstrap/cache 2>$null

# 8. Р’С‹РїРѕР»РЅСЏРµРј РјРёРіСЂР°С†РёРё
Write-Host "8. Р—Р°РїСѓСЃРє РјРёРіСЂР°С†РёР№ Р±Р°Р·С‹ РґР°РЅРЅС‹С…..." -ForegroundColor Yellow
docker exec hookah_app php artisan migrate --force 2>$null

# 9. РџРµСЂРµР·Р°РїСѓСЃРєР°РµРј PHP РєРѕРЅС‚РµР№РЅРµСЂ
Write-Host "9. РџРµСЂРµР·Р°РїСѓСЃРє PHP РєРѕРЅС‚РµР№РЅРµСЂР°..." -ForegroundColor Yellow
docker restart hookah_app 2>$null

# 10. РџРѕРєР°Р·С‹РІР°РµРј СЃС‚Р°С‚СѓСЃ
Write-Host "`n=== РЎС‚Р°С‚СѓСЃ ===" -ForegroundColor Green
docker ps --format "table {{.Names}}`t{{.Status}}`t{{.Ports}}"

Write-Host "`n=== Р“РѕС‚РѕРІРѕ! ===" -ForegroundColor Green
Write-Host "РЎР°Р№С‚: http://localhost:8000" -ForegroundColor White -BackgroundColor DarkBlue
Write-Host "Р‘Р°Р·Р° РґР°РЅРЅС‹С…: PostgreSQL РЅР° localhost:5432" -ForegroundColor White -BackgroundColor DarkBlue
Write-Host "РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ: hookah_user / secret" -ForegroundColor White -BackgroundColor DarkBlue
Write-Host "`nР”Р»СЏ РѕСЃС‚Р°РЅРѕРІРєРё: docker-compose down" -ForegroundColor Yellow
Write-Host "Р”Р»СЏ РїСЂРѕСЃРјРѕС‚СЂР° Р»РѕРіРѕРІ: docker-compose logs -f" -ForegroundColor Yellow

Pause
