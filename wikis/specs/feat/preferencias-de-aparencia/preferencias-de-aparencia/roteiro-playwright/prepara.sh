#!/usr/bin/env bash
# Uso (da raiz do repositório): bash wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/roteiro-playwright/prepara.sh <tenancy 0|1>
# Cria um banco SQLite descartável em ${TMPDIR:-/tmp}; o banco de desenvolvimento não é tocado.
# Depois: DB_CONNECTION=sqlite DB_DATABASE=<o banco> KIT_TENANCY=<true|false> php artisan serve --port=8091
# e:      node <esta pasta>/manual.cjs http://127.0.0.1:8091 <0|1> <pasta de saída>
set -e
T=$1
AQUI="$(cd "$(dirname "$0")" && pwd)"
export DB_CONNECTION=sqlite
export DB_DATABASE="${TMPDIR:-/tmp}/aparencia-manual-t$T.sqlite"
export KIT_TENANCY=$([ "$T" = 1 ] && echo true || echo false)
export MAIL_MAILER=log
rm -f "$DB_DATABASE"; touch "$DB_DATABASE"
php artisan migrate:fresh --seed --force --no-interaction > /dev/null
php artisan tinker --execute "require '$AQUI/dados.php';"
echo "banco: $DB_DATABASE"
