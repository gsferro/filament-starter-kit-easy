<#
.SYNOPSIS
    Sobe/atualiza a aplicação containerizada na máquina que hospeda os containers.

.DESCRIPTION
    A imagem é self-contained (código assado no build), então todo deploy é
    pull -> rebuild -> migrate -> limpar caches -> verificar saúde.

.PARAMETER Recreate
    Força a recriação dos containers (use quando variáveis do .env mudarem).

.EXAMPLE
    ./deploy_docker_local.ps1
    ./deploy_docker_local.ps1 -Recreate
#>
[CmdletBinding()]
param(
    [switch] $Recreate
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

function Assert-Ok([string] $Passo) {
    if ($LASTEXITCODE -ne 0) {
        throw "$Passo falhou (exit code $LASTEXITCODE)."
    }
}

New-Item -ItemType Directory -Force -Path 'storage/logs' | Out-Null
Start-Transcript -Path 'storage/logs/deploy_docker_local.log' -Append | Out-Null

try {
    # 1. Pré-requisitos --------------------------------------------------------
    # Sem .env em disco o Docker criaria um DIRETÓRIO com esse nome no bind-mount
    # e a aplicação subiria sem configuração nenhuma.
    if (-not (Test-Path '.env' -PathType Leaf)) {
        throw 'Arquivo .env ausente (ou é um diretório). Copie .env.example e ajuste antes de subir.'
    }

    # 2. Código ----------------------------------------------------------------
    if (Test-Path '.git') {
        $antes = (git rev-parse HEAD)
        git pull --ff-only
        Assert-Ok 'git pull'

        if ((git rev-parse HEAD) -ne $antes) {
            Write-Host 'Código atualizado.' -ForegroundColor Green

            if ((git diff --name-only $antes HEAD) -contains '.env.example') {
                Write-Warning '.env.example mudou — revise as chaves novas no seu .env e rode de novo com -Recreate.'
            }
        }
    }

    # 3. Build e subida --------------------------------------------------------
    # Rebuild DEPOIS do pull é obrigatório: a imagem carrega o código.
    $argumentos = @('compose', '--profile', 'app', 'up', '-d', '--build')
    if ($Recreate) { $argumentos += '--force-recreate' }

    docker @argumentos
    Assert-Ok 'docker compose up'

    # 4. Migrations ------------------------------------------------------------
    # `up -d` retorna antes do php-fpm aceitar exec — daí o retry.
    $migrou = $false
    foreach ($tentativa in 1..5) {
        docker compose --profile app exec -T app php artisan migrate --force
        if ($LASTEXITCODE -eq 0) { $migrou = $true; break }
        Write-Host "Aguardando o container app... ($tentativa/5)" -ForegroundColor DarkGray
        Start-Sleep -Seconds 3
    }
    if (-not $migrou) { throw 'migrate falhou após 5 tentativas. Veja: docker compose --profile app logs app' }

    # 5. Caches ----------------------------------------------------------------
    docker compose --profile app exec -T app php artisan optimize:clear
    Assert-Ok 'optimize:clear'

    # 6. Saúde -----------------------------------------------------------------
    # A porta vem do Docker, não do .env: quem manda é o mapeamento real.
    $porta = 8000
    $mapeamento = (docker compose --profile app port nginx 80) 2>$null
    if ($mapeamento -match ':(\d+)$') { $porta = $Matches[1] }

    $ok = $false
    foreach ($tentativa in 1..10) {
        try {
            $resposta = Invoke-WebRequest -Uri "http://localhost:$porta/up" -UseBasicParsing -TimeoutSec 5
            if ($resposta.StatusCode -eq 200) { $ok = $true; break }
        } catch {
            Start-Sleep -Seconds 3
        }
    }

    docker compose --profile app ps

    if (-not $ok) {
        throw "A aplicação não respondeu em http://localhost:$porta/up. Veja: docker compose --profile app logs app"
    }

    Write-Host ''
    Write-Host "Aplicação no ar em http://localhost:$porta" -ForegroundColor Green
    Write-Host "  /app    negócio" -ForegroundColor Gray
    Write-Host "  /admin  administração" -ForegroundColor Gray
    Write-Host "  /infra  infraestrutura" -ForegroundColor Gray
} finally {
    Stop-Transcript | Out-Null
}
