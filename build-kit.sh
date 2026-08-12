#!/usr/bin/env bash
# =============================================================================
# build-kit.sh — gera o projeto completo do start-kit a partir do laravel/laravel
# Rode UMA vez para materializar o kit; depois publique o resultado no seu Git
# (e opcionalmente no Packagist) para instalar via `composer create-project`.
#
# Uso:   ./build-kit.sh [nome-da-pasta]   (padrão: fiotec-start-kit)
# Requer: PHP 8.3+, Composer 2 (rode no Git Bash/WSL no Windows)
# =============================================================================
set -euo pipefail

TARGET="${1:-fiotec-start-kit}"
OVERLAY="$(cd "$(dirname "$0")" && pwd)/overlay"

echo "==> Criando projeto Laravel 13 em ./${TARGET}"
composer create-project laravel/laravel:^13.0 "$TARGET" --no-interaction
cd "$TARGET"

echo "==> Instalando Filament v5 e pacotes do kit"
composer require \
    filament/filament:"^5.0" \
    bezhansalleh/filament-shield \
    spatie/laravel-permission \
    spatie/laravel-health \
    spatie/laravel-activitylog \
    spatie/laravel-backup \
    laravel/horizon \
    laravel/pulse \
    prism-php/prism \
    -W --no-interaction

echo "==> Instalando pacotes de desenvolvimento"
composer require --dev laravel/pint larastan/larastan --no-interaction || true

echo "==> Instalando traducoes pt-BR (Laravel, Filament e pacotes)"
composer require --dev laravel-lang/common --no-interaction
php artisan lang:add pt_BR --no-interaction
php artisan lang:update --no-interaction || true

echo "==> Definindo locale pt_BR e timezone America/Sao_Paulo no .env.example"
for ENVFILE in .env.example .env; do
  [ -f "$ENVFILE" ] || continue
  sed -i.bak \
    -e 's/^APP_LOCALE=.*/APP_LOCALE=pt_BR/' \
    -e 's/^APP_FAKER_LOCALE=.*/APP_FAKER_LOCALE=pt_BR/' \
    "$ENVFILE" && rm -f "$ENVFILE.bak"
  grep -q '^APP_TIMEZONE=' "$ENVFILE" || echo 'APP_TIMEZONE=America/Sao_Paulo' >> "$ENVFILE"
  grep -q '^KIT_TENANCY=' "$ENVFILE" || printf '\n# Multi-tenancy do painel App (opcional)\nKIT_TENANCY=false\n' >> "$ENVFILE"
done

echo "==> Publicando migrations e configs"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
php artisan vendor:publish --tag="health-migrations" --no-interaction
php artisan vendor:publish --tag="health-config" --no-interaction
php artisan vendor:publish --tag="activitylog-migrations" --no-interaction
php artisan vendor:publish --tag="backup-config" --no-interaction
php artisan vendor:publish --provider="Prism\Prism\PrismServiceProvider" --no-interaction || true
php artisan horizon:install --no-interaction
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider" --no-interaction

echo "==> Aplicando overlay do kit (painéis, páginas, seeders, docker, README)"
cp -R "$OVERLAY"/. .

echo "==> Instalando assets do Filament"
php artisan filament:install --panels --no-interaction || true
# O overlay sobrescreve o AdminPanelProvider gerado e registra os 3 painéis.
cp -R "$OVERLAY"/app/Providers/Filament/. app/Providers/Filament/
cp "$OVERLAY"/bootstrap/providers.php bootstrap/providers.php

echo "==> Ajustando nome do pacote (para publicar no Packagist se quiser)"
php -r '
$f = "composer.json";
$j = json_decode(file_get_contents($f), true);
$j["name"] = "fiotec/start-kit";
$j["description"] = "Start-kit Laravel 13 + Filament v5 com paineis admin, infra e app.";
file_put_contents($f, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
'

echo ""
echo "=========================================================="
echo " Pronto! Próximos passos dentro de ./${TARGET}:"
echo "   1. cp .env.example .env && php artisan key:generate"
echo "   2. (opcional) docker compose up -d  + variáveis de .env.docker"
echo "   3. php artisan migrate --seed"
echo "   4. php artisan shield:setup e shield:generate --all --panel=admin"
echo "   5. git init && git add . && git commit -m 'start-kit v0.1.0'"
echo "   6. Publique no GitHub e (opcional) no Packagist"
echo "=========================================================="
