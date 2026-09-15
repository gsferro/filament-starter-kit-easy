---
title: Domínio local
parent: Começar
grand_parent: Português
nav_order: 3
---

# Domínio local

## Por que vale a pena

Acessar o projeto por `http://meu-projeto.test` em vez de `http://127.0.0.1:8000` custa uma linha
no arquivo `hosts` da sua máquina e duas chaves no seu `.env`. Nenhum arquivo versionado muda, e
quem não fizer nada continua em `http://localhost:8000` sem perceber diferença. É adoção
individual: cada pessoa da equipe decide, e o servidor publicado não é tocado.

O ganho não é só estético. Cookie de sessão, link absoluto gravado em banco e callback de login
social passam a usar um nome estável, e não um IP com porta — o mesmo formato do ambiente real.

## Receita, em três passos

### 1. Arquivo hosts

Uma vez por máquina, e **exige terminal como administrador**. No Windows o caminho **não** é
`/etc/hosts`:

```text
C:\Windows\System32\drivers\etc\hosts      # Windows
/etc/hosts                                 # Linux e macOS

127.0.0.1    meu-projeto.test
```

O `hosts` é arquivo em disco: a linha vale para sempre, não apenas para a sessão aberta. Num
PowerShell **elevado**:

```powershell
Add-Content "$env:windir\System32\drivers\etc\hosts" "`n127.0.0.1`tmeu-projeto.test" -Encoding ascii
ipconfig /flushdns
```

O `-Encoding ascii` é precaução: o PowerShell 7 grava UTF-8 por padrão, e o resolvedor do Windows
espera ASCII puro nesse arquivo.

### 2. O seu .env

O `.env` não é versionado, então estas chaves ficam só na sua máquina:

```dotenv
APP_URL=http://meu-projeto.test
FORWARD_APP_PORT=80          # só no profile `app`; sem ela a porta continua 8000
COMPOSE_PROJECT_NAME=meu-projeto
```

A `FORWARD_APP_PORT` é o que tira o `:8000` do endereço: o `docker-compose.yml` já publica a porta
como `${FORWARD_APP_PORT:-8000}:80`, então basta definir a chave. O default segue 8000 para todo
mundo que não a definir.

### 3. Subir e limpar o cache de configuração

```bash
docker compose --profile app up -d --build
docker compose --profile app exec app php artisan config:clear
```

Rodando o PHP no host em vez do container, troque por `php artisan serve --host=0.0.0.0 --port=80`
— ou pule a `FORWARD_APP_PORT` e use `http://meu-projeto.test:8000`.

Conferindo, sem precisar de elevação:

```powershell
Get-Content "$env:windir\System32\drivers\etc\hosts" | Select-String meu-projeto
ping meu-projeto.test        # deve responder 127.0.0.1
```

## Por que funciona sem mexer em nada versionado

| Peça | Motivo |
|---|---|
| `docker/nginx/nginx.conf` | `server_name _` é catch-all: o nginx já atende qualquer `Host` |
| `bootstrap/app.php` | sem `trustHosts()`, o Laravel não rejeita host desconhecido |
| `config/session.php` | `SESSION_DOMAIN=null` amarra o cookie ao host corrente sozinho |
| multi-tenancy | é por caminho (`/app/{tenant}`), não por subdomínio — sem wildcard de DNS |
| `docker-compose.yml` | a porta publicada já é `${FORWARD_APP_PORT:-8000}:80` |

O `name:` do `docker-compose.yml` é o **piso** do kit e não deve ser editado: há caso de teste que
o guarda. O nome por projeto viaja no `.env`, pela `COMPOSE_PROJECT_NAME`, e é ela que o
`kit:install` grava.

## Armadilhas

### Elevação: o flushdns engana

Se o `Add-Content` responder um erro de acesso negado ao `hosts`, o terminal não está elevado — a
ACL do arquivo dá escrita apenas a `BUILTIN\Administradores`, com `AUTORIDADE NT\SISTEMA` como
dono. A pegadinha: `ipconfig /flushdns` roda **sem** elevação e responde "bem-sucedida" de
qualquer jeito, então o sucesso dele não prova que a sessão é de administrador.

Abra o Windows Terminal com *Executar como administrador*, ou eleve só o comando:

```powershell
Start-Process pwsh -Verb RunAs -ArgumentList '-NoProfile','-Command',
  'Add-Content "$env:windir\System32\drivers\etc\hosts" "`n127.0.0.1`tmeu-projeto.test" -Encoding ascii'
```

Persistindo o erro mesmo com elevação, o suspeito seguinte é o Acesso Controlado a Pastas do
Defender ou um antivírus corporativo protegendo o arquivo.

### Sufixo .test, nunca .local

Use `.test`, reservado pela RFC 6761 exatamente para este uso. O sufixo `.local` é reservado pela
RFC 6762 ao mDNS (Bonjour/Avahi): funciona no Windows, porque o `hosts` é consultado antes, mas
convive com descoberta de impressoras e AirPlay, e é fonte conhecida de latência de resolução.

### Login social e Vite

Dois pontos merecem atenção depois da troca do `APP_URL`:

- **Login social**: os provedores registram `APP_URL + /auth/{provider}/callback`. A URI nova
  precisa ser cadastrada no console do provedor, ou o retorno falha.
- **`npm run dev`**: os módulos saem de `localhost:5173` enquanto a página está no domínio local,
  e o Vite restringe CORS por padrão. Com `npm run build` — o que o profile `app` usa — a questão
  não aparece.

## Como desfazer

Apague a linha do `hosts` e as chaves do `.env`. Não há nada mais a limpar: nenhum arquivo do
repositório foi tocado em momento algum, e o endereço volta a ser `http://localhost:8000`.
