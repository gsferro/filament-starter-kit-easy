#!/usr/bin/env bash
# Cria um worktree pronto para rodar a suite: branch propria, .env proprio, banco proprio,
# vendor/node_modules/public-build copiados (NAO junction: uma wiki instala dependencia nova
# e contaminaria as vizinhas pelo autoload compartilhado).
set -euo pipefail

BRANCH="$1"
DIR_NAME="$2"
RAIZ="D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/starter-kit-easy"
DEST="$RAIZ/.claude/worktrees/$DIR_NAME"

cd "$RAIZ"
git worktree add -b "$BRANCH" "$DEST" main

cp .env "$DEST/.env"
for d in vendor node_modules public/build; do
  [ -e "$d" ] && cp -r "$d" "$DEST/$d"
done

cd "$DEST"
touch database/database.sqlite
php artisan migrate:fresh --seed --no-interaction --force >/dev/null
echo "PRONTO: $DEST ($BRANCH)"
