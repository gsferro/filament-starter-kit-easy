# Divergências README × código — lista completa

> Produzida por sub-agente de fact-checking (88 leituras), re-verificada por amostragem. Duas
> afirmações do agente foram **refutadas** na re-verificação e estão marcadas como tal no fim. As 37
> restantes são o backlog de correção do passo 6 do plano.
>
> Linhas referem-se ao estado da branch `feat/aderencia-ao-blueprint` em 2026-08-26, antes de
> qualquer edição desta wiki.

## Chaves de ambiente

| # | README afirma | Código diz | Tipo | Correção |
|---|---|---|---|---|
| 1 | `KIT_ADMIN_NAME` no `.env` — README.md:98 / en:59 | `config/kit.php:592` lê; `.env.example` só tem `_EMAIL` (:111) e `_PASSWORD` (:112) | órfã | acrescentar `KIT_ADMIN_NAME=Administrador` ao `.env.example` |
| 2 | `KIT_ART=1` — README.md:1643,1648 / en:1615,1620 | só em `tests/BrowserTenancy/CapturaDeArteTest.php:56` e `composer.json:176` | órfã aceitável | dizer que é variável de teste |
| 3 | cor livre só como campo de tela — README.md:1777-1785 | `KIT_COR_PRIMARIA_HEX` em `.env.example:83`, `config/kit.php:78` | não documentada | documentar ao lado de `KIT_COR_PRIMARIA` |
| 4 | nenhuma menção a `KIT_HUB` | `.env.example:134`, `config/kit.php:342`, lida em `HubDeAdministracao.php:79`, `HubDoNegocio.php:75`, `BoasVindas.php:226` | não documentada | seção do hub (ver #36) |
| 5 | `KIT_TABELA_*` ausentes; README.md:1734 só "editáveis na aba Tabelas" | `.env.example:89-92`, `config/kit.php:203-206` | não documentada | listar as 4 como plano B do `.env` |
| 6 | só o array PHP `'tenancy' => [...]` — README.md:913-919 / en:881-887 | `KIT_TENANCY`, `_LABEL`, `_LABEL_PLURAL`, `_SLUG` (`.env.example:118-124`, `config/kit.php:237-246`) | não documentada | mostrar as 4 chaves |
| 7 | só a flag `--repo` — README.md:2077 | `config/kit.php:33` lê `KIT_REPOSITORY`; ausente do `.env.example` e dos READMEs | não documentada | documentar ou remover do config |
| 8 | `KIT_DEMO=true` pelo `--demo` — README.md:881-884 | **README.en.md nunca menciona `KIT_DEMO`** | PT≠EN | traduzir o blockquote |

## Comandos e flags

| # | README afirma | Código diz | Tipo | Correção |
|---|---|---|---|---|
| 9 | "Comandos `kit:*` \| 4" — README.md:155 / en:116 | são **7** (`php artisan list kit`) | número errado | 7 |
| 10 | flags do `kit:install`: só `--force`, `--custom`, `--no-custom` — README.md:1511-1513 | `KitInstall.php:38-44` também `--no-npm`, `--no-seed`, `--no-support`, `--create-project` | não documentada | completar |
| 11 | tabela do `kit:update` com 8 flags — README.md:2072-2081 | `KitUpdate.php:30` também `--repo=` | não documentada | acrescentar |
| 12 | `kit:tenancy` / `--demo` — README.md:877-878 | `KitTenancy.php:43-44` também `--force` | não documentada | documentar |
| 13 | `kit:admin` sem opções — README.md:65,1514 | `KitAdmin.php:30-32`: `--email=`, `--senha=`, `--force` | não documentada | listar, com o aviso de histórico de shell |
| 14 | lista de `composer` — README.md:1503-1517 / en:1478-1490 | `lint:check` (`composer.json:126`) e `upgrade:filament` (:150) ausentes | não documentada | acrescentar |
| 15 | "`composer require filament/upgrade --dev -W`" — README.md:1587 / en:1560 | já está em `require-dev`; existe `composer upgrade:filament` | divergente | trocar pelo script |
| 16 | bloco PT tem `kit:install --custom` e `kit:admin` — README.md:1512,1514 | EN (:1486-1489) não tem | PT≠EN | sincronizar |

## Números da tabela "Nossos números"

| # | README afirma | Código diz | Correção |
|---|---|---|---|
| 17 | "Migrations \| 48" — :153 / en:114 | 54 | 54 |
| 18 | "Pacotes de desenvolvimento \| 15" — :152 / en:113 | 19 no commitado | 19 |
| 19 | "Arquivos de teste \| 94" — :161 / en:122 | Kit 61 + Tenancy 23 = 84; todos = 105 | declarar recorte |
| 20 | "411 casos, com 1138 asserções" — :159 / en:120 | ≥704 declarações `test(`/`it(` só em Kit+Tenancy; a suíte Kit sozinha rodou **1212 testes / 3520 asserções** nesta sessão | recontar |
| 21 | "20 widgets" — :249 / en:211 | 24 (7 admin + 17 infra); o próprio README diz 24 em :1347 | 24 |
| 22 | "61 telas prontas" — :1289 / en:1267 | roteiro tem 70 IDs; tabela diz 67 telas em :140 | alinhar |
| 23 | "Rotas GET \| 19 \| 34 \| 33 \| 86" — :144 / en:105 | 21 / 35 / 33 = 89 sem tenancy | recontar |
| 24 | "Páginas próprias \| 4 \| 3 \| 12 \| 19" — :142 / en:103 | 4 / 4 / 12 = 20 | 20 |
| 25 | "Project rules \| 13" — :169 / en:130 | 14 arquivos de regra | 14 |
| 26 | "exatamente **uma** exceção em `phpstan.neon`" — :205-208 / en:166-169 | duas (`:29-32` macro; `:52-57` breezy) | duas, com as duas justificativas |
| 27 | "dez `__()` em todo o app" — :283 / en:245 e `config/kit.php:408` | 11 | recontar |

## Pacotes

| # | README afirma | Código diz | Correção |
|---|---|---|---|
| 28 | "Pacotes instalados" — :2172-2257 / en:2147-2232 | 4 de `require` fora das tabelas: `harvirsidhu/filament-cards`, `laravel/socialite`, `leandrocfe/filament-apex-charts`, `solution-forest/filament-simplelightbox` | acrescentar |
| 29 | dev com 11 linhas — :2273-2287 / en:2247-2261 | faltam 7: `driftingly/rector-laravel`, `filament/upgrade`, `laravel/boost`, `pest-plugin-browser`, `pest-plugin-mutate`, `pest-plugin-phpstan`, `rector/rector` | completar |
| 31 | tabela Front-end — :2291-2296 / en:2265-2270 | `playwright` é devDependency e falta | acrescentar |

## Afirmações sobre funcionalidades

| # | README afirma | Código diz | Correção |
|---|---|---|---|
| 32 | exceções e trilha de e-mails "vivem **só** no `/infra`" — :826-827 / en:799-800 | `ExceptionResource` registrado nos **3** painéis (`admin/exceptions`, `app/{tenant}/exceptions`, `infra/exceptions`); a barreira é a subtração de permissão do `PapeisSeeder.php:171-198` | "só alcançável no `/infra`; a rota existe nos três e a barreira é a permissão" |
| 33 | "`/admin/configuracoes-do-kit`, em **quatro abas**" + tabela de 4 — :1740-1747 / en:1712-1719 | **6** abas (`ConfiguracoesDoKit.php:227,266,370,424,476,555`) — Registro e Login faltam da tabela, e o próprio README as cita em :467, :564, :615 | seis, com as duas linhas |
| 34 | "Os **quatro** papéis" + tabela de **cinco** — :894-902 | `PapeisSeeder.php:55,58,61,80,101` semeia 5 | cinco |
| 35 | EN :862 "The kit's roles" sem número | PT diz quatro | PT≠EN | sincronizar em cinco |
| 36 | hub de cartões só de passagem — :1747 / en:1719 | feature inteira: 3 hubs, `KIT_HUB`, `filament-cards`, `HubDeCardsTest`, captura em `composer art`, `/infra` ligado por default (`config/kit.php:328-330`) | **seção própria** + entrada no roteiro |
| 37 | health checks: "banco, cache, filas, agendador, disco, debug mode e IA local" — :229 / en:191 | `KitServiceProvider.php:408-416` registra também `EnvironmentCheck` e `OptimizedAppCheck`; `UsedDiskSpaceCheck` **pulado no Windows** | acrescentar dois; disco "exceto Windows" |
| 38 | bloco Windows/sem-TTY — README.md:35-72 | **não existe em inglês** (en salta de :33 para :35). Maior assimetria dos dois arquivos | traduzir o bloco inteiro |
| 39 | "`composer test:kit` … **em paralelo**" — :1506 | en:1481 omite | PT≠EN nit | acrescentar |

## Refutadas na re-verificação (não corrigir)

| # do agente | Afirmação | Por que caiu |
|---|---|---|
| 25 (metade) | "`general.md` não está na tabela de `index.md`" | está: `.ai/rules/index.md:12` `| composer.json | .ai/rules/general.md |` |
| 30 | "`package.json` não tem `@laravel/multiplex`" | tem: `grep -c multiplex package.json` = 1 |

## Dúvidas do agente que ficam como estão

- "Telas navegáveis 12 / 28 / 27 / 67" (:140) — sem critério operacional de "navegável" no repositório, não é falsificável. Fica.
- "Telas varridas 55" (:160) — 58 chamadas `visit()`, mas visita ≠ tela distinta. Fica.
- "FilaCheck: 17 regras", "29 erros no level 6→7", "Rector reescreveria 103 arquivos" — medições históricas, não reproduzíveis sem rodar as ferramentas. Ficam como histórico.
