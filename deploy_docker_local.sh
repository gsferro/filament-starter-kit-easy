#!/usr/bin/env bash
#
# Atualiza a stack Docker desta máquina com a última evolução do repositório.
#
# Roda NA máquina que hospeda os containers (não na máquina de desenvolvimento).
# Sequência: git pull -> rebuild da imagem -> subir o profile `app` -> migrations ->
# optimize:clear -> health check do nginx -> sonda do Reverb.
#
# O profile `app` inclui `reverb` (WebSocket) e `pulse` (batimento das métricas) — por isso o
# deploy não ganhou comando novo. O rebuild recria os dois, o que já cumpre o papel do
# `reverb:restart`: processo long-running não vê código novo sem reiniciar.
#
# A imagem é self-contained (o código é assado nela), por isso o rebuild é obrigatório e vem
# DEPOIS do pull — rebuild antes reassa o código velho.
#
# Uso:
#   ./deploy_docker_local.sh
#   ./deploy_docker_local.sh --recreate
#
# --recreate acrescenta --force-recreate ao `up`. Necessário quando o `.env` mudou: o Compose lê
# o `env_file` na CRIAÇÃO do container, então um container já existente mantém os valores antigos.

set -euo pipefail

cd "$(dirname "$(readlink -f "$0")")"

recreate=0
for arg in "$@"; do
    case "$arg" in
        -r|--recreate) recreate=1 ;;
        -h|--help) sed -n '3,21p' "$0"; exit 0 ;;
        *) echo "[deploy_docker_local] argumento desconhecido: $arg" >&2; exit 2 ;;
    esac
done

mkdir -p storage/logs
exec > >(tee -a storage/logs/deploy_docker_local.log) 2>&1

# Cores só quando a saída é terminal — com o tee acima, stdout não é tty e o log fica limpo.
if [[ -t 1 ]]; then
    ciano=$'\e[36m'; verde=$'\e[32m'; amarelo=$'\e[33m'; reset=$'\e[0m'
else
    ciano=''; verde=''; amarelo=''; reset=''
fi

passo()  { printf '\n%s== %s%s\n' "$ciano" "$1" "$reset"; }
aviso()  { printf '%sAVISO: %s%s\n' "$amarelo" "$1" "$reset"; }
morrer() { printf '[deploy_docker_local] %s\n' "$1" >&2; exit 1; }

echo "--- $(date '+%Y-%m-%d %H:%M:%S') deploy iniciado (recreate=$recreate)"

# O .env é bind-mount de ARQUIVO: se não existir, o Docker cria uma PASTA com esse nome e a
# app sobe sem configuração nenhuma. Barrar aqui é mais barato que diagnosticar depois.
passo 'Pré-requisitos'
[[ -f .env ]] || morrer ".env ausente (ou é um diretório) em $PWD — copie o `.env.example` e ajuste antes de rodar"
command -v docker >/dev/null || morrer 'docker não encontrado no PATH'
docker compose version >/dev/null 2>&1 || morrer 'plugin `docker compose` (v2) não disponível'

passo 'Atualizando código'
antes=$(git rev-parse HEAD)
git pull --ff-only
depois=$(git rev-parse HEAD)

if [[ "$antes" == "$depois" ]]; then
    echo 'Nada novo no remoto — seguindo com rebuild mesmo assim.'
elif [[ -n "$(git diff --name-only "$antes" "$depois" -- .env.example)" ]]; then
    aviso '.env.example mudou neste pull: confira as chaves novas no seu .env e rode de novo com --recreate.'
fi

passo 'Rebuild e subida dos containers (profile app)'
up=(compose --profile app up -d --build)
(( recreate )) && up+=(--force-recreate)
docker "${up[@]}"

# Retry: `up -d` retorna assim que o container inicia; o php-fpm ainda pode não aceitar exec.
passo 'Migrations'
for tentativa in {1..5}; do
    if docker compose exec -T app php artisan migrate --force; then
        break
    fi
    (( tentativa == 5 )) && morrer 'migrate falhou após 5 tentativas'
    echo "migrate falhou (tentativa $tentativa/5), nova tentativa em 3s..."
    sleep 3
done

passo 'Limpando caches da aplicação'
docker compose exec -T app php artisan optimize:clear

# Porta pelo Docker, não pelo .env: FORWARD_APP_PORT é default do compose e costuma
# nem existir no arquivo. `port` responde "0.0.0.0:8000" (uma linha por família de IP).
passo 'Health check'
porta=$(docker compose --profile app port nginx 80 2>/dev/null | head -1 | sed 's/.*://' || true)
porta=${porta:-8000}

url="http://localhost:$porta/up"
ok=0
for _ in {1..10}; do
    # nginx/FPM ainda subindo depois do recreate — tenta de novo
    if [[ "$(curl -s -o /dev/null -m 10 -w '%{http_code}' "$url" || true)" == '200' ]]; then
        ok=1
        break
    fi
    sleep 3
done

docker compose --profile app ps
(( ok )) || morrer "app não respondeu 200 em $url — veja: docker compose --profile app logs app"

# Sonda TCP, não HTTP: o Reverb responde ao handshake de WebSocket em /app e à API em /apps,
# e um GET / devolve erro — "conectou" já prova que o processo está aceitando conexão.
# AVISO, não erro: a app está de pé, e com BROADCAST_CONNECTION != reverb o sininho volta ao
# polling de 30s sozinho (os providers passam o intervalo condicionado à conexão).
passo 'Reverb (WebSocket)'
porta_reverb=$(docker compose --profile app port reverb 8090 2>/dev/null | head -1 | sed 's/.*://' || true)

if [[ -z "$porta_reverb" ]]; then
    aviso 'Serviço `reverb` não publicou porta — tempo real indisponível nesta subida.'
else
    reverb_ok=0
    for _ in {1..5}; do
        # /dev/tcp do bash: sonda sem depender de nc/netcat instalado no host.
        if timeout 3 bash -c "exec 3<>/dev/tcp/127.0.0.1/$porta_reverb" 2>/dev/null; then
            reverb_ok=1
            break
        fi
        sleep 2
    done

    if (( reverb_ok )); then
        printf '%sReverb aceitando conexão em ws://localhost:%s%s\n' "$verde" "$porta_reverb" "$reset"
    else
        aviso "Reverb não aceitou conexão em 127.0.0.1:$porta_reverb — o sininho fica sem tempo real (a app segue de pé). Veja: docker compose --profile app logs reverb"
    fi
fi

printf '\n%sDeploy concluído. App respondendo em http://localhost:%s%s\n' "$verde" "$porta" "$reset"
