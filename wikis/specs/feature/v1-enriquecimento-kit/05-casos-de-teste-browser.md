# Casos de Teste de Browser — Cabeçalho de identidade no menu do usuário

> Arquivo: `tests/Browser/CabecalhoDoMenuDoUsuarioTest.php`
> Um único cenário, e a justificativa dele é o assunto principal deste documento.

## Por que existe exatamente **um** CT-B

O gate do `05` não é "a feature tem UI". É: **o cenário afirma algo que só o navegador prova?**

Quase tudo desta feature é HTML do servidor, e a suíte `Kit` já prova nos três painéis que o
cabeçalho está lá, com nome, e-mail, badge e rótulo certo — em milissegundos, sem Node, sem
Playwright. Levar isso para o browser seria pagar segundos por cenário para provar o mesmo.

O que o HTML **não** prova é o cabeçalho ficar **visível**. O painel do dropdown é `x-show` do
Alpine. Um erro de JavaScript em qualquer outro componente da topbar — e o kit tem vários lá, do
Spotlight ao PanelSwitch — deixa o cabeçalho presente no DOM e invisível para sempre, com todos os
testes de componente verdes.

Esse é o único fato que exige navegador. Daí um cenário.

## CT-B01 — abrir o dropdown e ver o cabeçalho

**Pré-condição**: `ShieldPermissionsSeeder` + `PapeisSeeder`; usuário `master_global` autenticado por
`actingAs()` antes do `visit()`.

```gherkin
Funcionalidade: Cabeçalho de identidade no menu do usuário
  Regra: o cabeçalho só é visível com o dropdown aberto

    Cenário: o operador abre o menu do usuário e vê quem está logado
      Dado que estou autenticado como master global
      E que estou no painel de administração
      Então o cabeçalho existe no DOM
      Mas o cabeçalho não está visível
      Quando eu clico no gatilho do menu do usuário
      Então o cabeçalho fica visível
      E ele mostra o meu nome
      E ele mostra o meu e-mail
      E não há erro de JavaScript na página
```

### Passos e asserções

| # | Ação / asserção | Por quê |
|---|---|---|
| 1 | `actingAs($user)` **antes** do `visit()` | O plugin sobe o servidor no mesmo processo; login pela tela custaria dezenas de segundos |
| 2 | `visit('/admin')` | — |
| 3 | `assertPresent('[data-user-menu-header]')` | Está no DOM |
| 4 | `assertMissing('[data-user-menu-header]')` | **Não** está visível — é o que impede o caso de virar asserção que passaria de qualquer jeito |
| 5 | `click('.fi-user-menu-trigger')` | Classe do próprio Filament para o gatilho |
| 6 | `assertVisible('[data-user-menu-header]')` | **A asserção que justifica o navegador** |
| 7 | `assertSeeIn('[data-user-menu-header]', $user->name)` | Escopado ao cabeçalho, não à página |
| 8 | `assertSeeIn('[data-user-menu-header]', $user->email)` | idem |
| 9 | `assertNoJavaScriptErrors()` | E não `assertNoSmoke()`: a topbar carrega script de plugin de terceiro, e `console.log` alheio deixaria a suíte vermelha sem defeito nosso |

### As três decisões que fazem este caso valer alguma coisa

**`assertVisible`, nunca `assertPresent`.** Presente é o estado com o dropdown fechado. Trocar um
pelo outro transforma o cenário numa asserção que passa sem o clique ter feito nada — e é o modo mais
comum de um CT-B virar decoração.

**O par presente-e-invisível antes do clique (passos 3 e 4).** Sem ele, o caso não distingue "o
clique abriu o dropdown" de "o dropdown já estava aberto". Com ele, o cenário afirma a transição, que
é o que se quer.

**A âncora é `[data-user-menu-header]`, não o nome do usuário.** O nome aparece também no
`AccountWidget` do dashboard, na mesma página. `assertSee($user->name)` passaria com o dropdown
fechado. Ver ADR-06 — este é o primeiro gancho de teste do kit, e ele existe por esse motivo
concreto.

## O que **não** virou CT-B, e por quê

| Tentação | Onde está em vez disso | Motivo |
|---|---|---|
| O cabeçalho nos três painéis | CT-06 (`Kit`) | Presença é HTML do servidor |
| O badge com o rótulo certo | CT-08, CT-09 (`Kit`) | Texto renderizado é HTML do servidor |
| O papel certo por painel | CT-01, CT-02 (`Kit`, no model) | Regra pura — a camada mais barata que prova |
| O avatar carregar | — | É o componente do Filament; testar seria testar o framework |
| O badge legível no dark mode | — | **Lacuna assumida**: `assertSee` não valida tema, e afirmar contraste exige comparação de pixel. O par claro/escuro das classes é revisão de código, não asserção |

## Pré-requisitos de execução

```bash
npm run build          # pré-requisito DURO: sem o manifest do Vite toda tela responde ViteException
php artisan test --testsuite=Browser        # em série; NUNCA --parallel com browser
```

**Resultado em 2026-08-18**: 1 caso, 6 asserções, verde (14 s).

## Roteiro: desenhado × implementado

Confronto da tabela `## Superfície de UI` do `01-plano-acao.md` com a tela real, feito depois de o
CT-B01 rodar verde.

| Desenhado (PRD) | Implementado | Estado |
|---|---|---|
| Cabeçalho no dropdown do `/admin` | render hook `USER_MENU_PROFILE_BEFORE`, view `filament.user-menu-header` | ✅ |
| Cabeçalho no dropdown do `/app` | idem | ✅ |
| Cabeçalho no dropdown do `/infra` | idem | ✅ |
| Avatar do usuário | `x-filament-panels::avatar.user`, com fallback ui-avatars | ✅ |
| Nome, truncado, com `title` | ✅ | ✅ |
| E-mail, truncado, com `title` | ✅ | ✅ |
| Badge do papel do painel corrente | `filament.perfil-indicator`, via `User::papelDoPainel()` | ✅ |
| Badge com par claro/escuro | classes `dark:` explícitas | ⚠️ presente no código, **não** verificado por asserção |
| Dropdown abre e mostra | CT-B01 | ✅ |

Divergências replicadas em `03-progresso.md` → *Desvios do Plano*.
