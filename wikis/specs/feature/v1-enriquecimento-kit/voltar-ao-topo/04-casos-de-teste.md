# Casos de Teste — Botão "Voltar ao topo"

> Derivados do `00-requisito.md`. Arquivo: `tests/Kit/VoltarAoTopoTest.php`.

## Perfil de risco

O modo de falha desta feature é **sumir sem erro**. Não há exceção, não há 500: o hook deixa de ser
emitido, ou o escopo é acrescentado por engano, e o botão simplesmente não aparece mais — numa tela,
num painel, ou nas telas de vendor.

| Dimensão | Risco | Coberto por |
|---|---|---|
| Hook não registrado num painel | **alto** — RQ-04 é "todos" | CT-01 |
| Hook ganhar `scopes:` e perder as telas de vendor | **alto** — é o ponto da abordagem | **CT-02** |
| Offset errado, botão colidindo com o chat | médio | CT-03, CT-04 |
| Camada errada, botão por cima de modal/sidebar | médio | CT-05 |
| Botão sem rótulo acessível | médio | CT-06 |
| Botão não **aparecer** ao rolar | **alto** | ⚠️ **lacuna declarada** — ver abaixo |

## Mapa de regras

| Regra | Enunciado | Técnica |
|---|---|---|
| R1 | O botão existe nos três painéis | partição por painel |
| R2 | O botão existe também nas telas de vendor | partição por origem da tela |
| R3 | No `/app` o botão sobe, por causa do chat | valor por painel |
| R4 | Nos demais painéis fica na posição padrão | partição complementar |
| R5 | A camada é a menor de todas | asserção sobre classe |
| R6 | O botão tem rótulo para leitor de tela | acessibilidade mínima |

## Casos

| CT | Regra | Cenário | Oráculo |
|----|-------|---------|---------|
| CT-01 | R1 | GET no dashboard de `app`, `admin`, `infra` | HTML contém `data-voltar-ao-topo` |
| CT-02 | R2 | GET em 5 telas de plugin: auditoria, log de acesso, exceções, filas, permissões | idem |
| CT-03 | R3 | GET em `/app` | contém `bottom-24` |
| CT-04 | R4 | GET em `/admin` e `/infra` | contém `bottom-6` e **não** contém `bottom-24` |
| CT-05 | R5 | GET em `/admin` | contém `z-20` |
| CT-06 | R6 | GET em `/admin` | contém `aria-label="Voltar ao topo"` |

**13 casos** ao todo (CT-01, CT-02 e CT-04 são datasets).

### CT-02 é o caso que justifica a arquitetura

As cinco telas são de plugin: Shield, `tapp/filament-auditing`, `tapp/filament-authentication-log`,
`bezhansalleh/filament-exceptions` e `croustibat/filament-jobs-monitor`. **Nenhuma delas pode receber
trait nem edição.**

Se alguém trocar o render hook global por registro por painel, ou por um trait em `ListRecords`
— que é o que o pacote `gboquizosanchez/filament-scroll-to-top` exige —, é este caso que cai. Ele é
a diferença entre "o botão está no kit" e "o botão está em todas as telas".

### CT-04 usa `assertDontSee` de propósito

`bottom-6` sozinho não distingue os painéis: o botão do chat também usa `bottom-6`, e ele está no
`/app`. A ausência de `bottom-24` é o que prova que o ramo do painel foi avaliado.

## Mutantes previstos e o cenário que mata cada um

| # | Mutante | Morto por |
|---|---------|-----------|
| M1 | Registrar o hook num `PanelProvider` em vez de global | CT-01 (dois dos três datasets) |
| M2 | Acrescentar `scopes: ['app']` ao `registerRenderHook()` | CT-01 e **CT-02** |
| M3 | Inverter a condição do painel no `@php` | CT-03 e CT-04 |
| M4 | Trocar `z-20` por `z-30` | CT-05 |
| M5 | Remover o `aria-label` | CT-06 |
| M6 | Trocar o gatilho `window.scrollY > 400` por outro valor | nenhum — **lacuna, ver abaixo** |
| M7 | Remover `x-show="visivel"` (botão sempre visível) | nenhum — **lacuna, ver abaixo** |

## ⚠️ Lacuna declarada: o botão APARECER não está coberto

**O que falta**: nenhum caso prova que o botão fica visível ao rolar e volta ao topo no clique. É
comportamento de Alpine — `x-show`, `@scroll.window.passive`, `x-cloak` e `window.scrollTo` — e só o
navegador prova.

**Por que não está coberto**: o CT-B foi escrito e **falhou em três tentativas**, sempre no
`assertVisible` após o scroll programático:

| Tentativa | O que mudou | Resultado |
|---|---|---|
| 1 | `/infra`, `window.scrollTo(0, 1200)` | timeout de 45s no `assertVisible` |
| 2 | idem + `document.body.style.minHeight = '4000px'` | timeout, 0 asserções |
| 3 | `/admin` + o mesmo alongamento | timeout no `assertVisible` |

A causa provável é que o `scrollTo` programático não produz o evento que o `@scroll.window` escuta
neste layout — o Filament declara `min-h-dvh` em `.fi-body`, e alongar o `body` por script pode não
tornar a `window` rolável. Não foi confirmado: a sondagem que mediria `scrollHeight` e `scrollY`
estourou o tempo do ambiente.

**Não foi entregue um teste instável nem um teste que passa sem provar.** A opção foi remover o CT-B
e registrar a lacuna aqui.

**O que mitiga hoje, e não é pouco**: a varredura de `tests/Browser/TelasDoKitTest.php` roda
`assertNoJavaScriptErrors()` em **55 telas**, e o botão agora está em todas elas. Alpine quebrado
neste hook — atributo malformado, `x-data` inválido, erro no `@click` — derruba os 55 cenários. O que
fica sem prova é especificamente a **transição de invisível para visível**.

**Como fechar**: descobrir por que o scroll programático não dispara o listener neste layout. Um
`Playwright MCP` observando a página ao vivo resolveria em minutos — ele não está configurado neste
projeto (`.mcp.json` só tem o `laravel-boost`).

## Cobertura do requisito

| RQ | Coberto por |
|----|-------------|
| RQ-03 (trazer para o kit) | CT-01 |
| RQ-04 (todos os painéis) | CT-01 |
| RQ-05 (todas as páginas) | **CT-02** |
| RQ-06 (sem o pacote) | nenhuma dependência nova — verificável no `composer.json` |

RQ-01 e RQ-02 são de leitura e análise; a prova é o ADR-01.

## Execução

```bash
php artisan test tests/Kit/VoltarAoTopoTest.php
```

**Resultado em 2026-08-18**: 13 casos, 28 asserções, verdes.
