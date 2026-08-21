# Casos de Teste — Cabeçalho de identidade no menu do usuário

> Derivados do `00-requisito.md` (RQ-02, RQ-03), não do PRD.
> Arquivos: `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` e
> `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`.

## Perfil de risco

A feature é pequena e o modo de falha dela é específico: **ela some sem erro**. Não há exceção, não
há 500, não há log. O hook deixa de ser emitido, ou o papel não é encontrado, e o dropdown volta a
ser o de antes — e ninguém percebe até alguém abrir o menu.

Isso define a estratégia: os casos precisam afirmar **presença** e **conteúdo**, nunca só "a página
abriu".

| Dimensão | Risco | Onde é coberto |
|---|---|---|
| Hook não registrado em algum painel | **alto** — RQ-03 é "todos os painéis" | CT-04 (os três, por dataset) |
| Papel resolvido errado | **alto** — badge que mente é pior que badge ausente | CT-01 a CT-03, CT-09 a CT-12 |
| `master_global` sem badge | **alto** — `roles.painel` dele é nulo | CT-03, CT-11 |
| Consulta quebrando com `permission.teams` | **alto** — só aparece com tenancy | CT-09, CT-10 |
| Chave vazando para a tela | médio | CT-06 |
| Renderizar sem usuário autenticado | médio | CT-08 |
| Dropdown não abrir | médio — só o navegador prova | `05-casos-de-teste-browser.md` |

## Varredura SFDIPOT (recorte aplicável)

- **Structure**: duas views + um método de model + três registros de hook.
- **Function**: exibir identidade e papel do painel corrente.
- **Data**: usuário com 0, 1 ou N papéis; papel do painel × de outro painel; `master_global` com
  `roles.painel` nulo; papel atribuído em contexto de organização.
- **Interfaces**: `filament()->auth()->user()`, `filament()->getCurrentOrDefaultPanel()`,
  `App\Support\Papeis`, o componente `x-filament-panels::avatar.user`.
- **Platform**: `permission.teams` ligado × desligado — a única variável de ambiente que muda o
  comportamento.
- **Operations**: render a cada página de painel; nenhuma escrita.
- **Time**: não há dimensão temporal.

## Mapa de regras

| Regra | Enunciado | Técnica |
|---|---|---|
| R1 | Papel que abre o painel é o papel exibido | partição por painel |
| R2 | Papel que **não** abre o painel não é exibido | partição complementar |
| R3 | Sem papel nenhum, nada é exibido | valor limite (zero papéis) |
| R4 | `master_global` é exibido em qualquer painel, apesar de `roles.painel` nulo | caso especial |
| R5 | O texto exibido é o rótulo, nunca a chave | oráculo de saída |
| R6 | O cabeçalho existe nos três painéis | tabela painel × presença |
| R7 | Sem sessão, nada é renderizado | fronteira de autenticação |
| R8 | Com `teams` ligado, o papel é achado fora do contexto em que foi atribuído | fronteira de plataforma |

## Setup global

`beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` — os papéis do kit
só existem depois do seeder, e `usuarioCom()` estoura `RoleDoesNotExist` sem ele.

Helpers de `tests/Pest.php`: `usuarioCom()`, `usuarioComPapel()`, `tenant()`, `noPainelDa()`.

> `admin_app` **só existe com tenancy** (o `PapeisSeeder` o cria dentro do ramo condicional). Por
> isso ele aparece só nos casos da suíte `Tenancy` — colocá-lo na `Kit` foi a primeira versão, e
> falhou exatamente assim.

---

## Suíte `Kit` — `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php`

| CT | Regra | Cenário | Oráculo |
|----|-------|---------|---------|
| CT-01 | R1 | `admin` no `/admin`, `infra` no `/infra`, `panel_user` no `/app` | `papelDoPainel()` devolve o próprio papel |
| CT-02 | R2 | `admin` no `/infra`, `infra` no `/admin`, `panel_user` no `/admin`, `admin` no `/app` | devolve `null` |
| CT-03 | R3 | usuário sem papel, nos três painéis | devolve `null` |
| CT-04 | R4 | `master_global` nos três painéis | devolve `master_global`, apesar de `roles.painel` nulo |
| CT-05 | R2 | painel que não existe | devolve `null`, **sem exceção** |
| CT-06 | R6 | GET no dashboard dos três painéis | HTML contém `data-user-menu-header` |
| CT-07 | R1 | GET no `/admin` autenticado | HTML contém nome **e** e-mail do usuário |
| CT-08 | R5 | GET nos três painéis como `master_global` | contém `Administrador Geral` e **não** contém `>master_global<` |
| CT-09 | R1 | `panel_user` no `/app` | contém `Painel App` |
| CT-10 | R1 | `admin` no `/admin` | contém `Acesso ao painel /admin` (título do badge) |
| CT-11 | R7 | GET em `/{painel}/login` sem sessão | **não** contém `data-user-menu-header` |

### Por que a âncora é `data-user-menu-header` e não o nome do usuário

O nome aparece **também** no `AccountWidget` do dashboard, na mesma página. Um `assertSee($user->name)`
passaria com o render hook removido — falso ✅ no caso exato que RQ-03 exige provar. Ver ADR-06.

### Por que CT-08 usa `assertDontSee('>master_global<')`

A chave `master_global` aparece legitimamente em outros pontos do HTML de uma página de painel
(atributos, dados de componente). O que não pode acontecer é ela ser **texto renderizado** — daí a
asserção com os delimitadores de tag, e não a chave solta.

---

## Suíte `Tenancy` — `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

Aqui e não em `Kit` porque `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`,
antes das migrations — ligar a flag num `beforeEach` seria tarde.

| CT | Regra | Cenário | Oráculo |
|----|-------|---------|---------|
| CT-12 | R1 | `admin_app` numa organização, painel `/app` | devolve `admin_app` |
| CT-13 | **R8** | papel atribuído na organização A; contexto trocado para a B | **continua** devolvendo o papel |
| CT-14 | R4 | `master_global` nos três painéis, com `teams` ligado | devolve `master_global` |
| CT-15 | R6 | GET em `/app/{slug}` com organização na URL | contém a âncora, o e-mail e `Painel App` |
| CT-16 | R1 | papel atribuído em organização **inativa** | continua devolvendo o papel |

### CT-13 é o caso central desta suíte

É o único que falha se a implementação usar `roles()` do spatie em vez de
`papeisEmQualquerContexto()`. Com `teams` ligado, `roles()` acrescenta
`wherePivot(team_id, getPermissionsTeamId())`, e o papel atribuído na organização A desaparece quando
o contexto corrente é a B — ou quando não há contexto nenhum, que é a situação do `/admin` e do
`/infra`.

Sem CT-13, a feature passaria em todos os outros casos e quebraria em produção no dia em que alguém
ligasse a tenancy.

### CT-16 é fronteira, não redundância

Separa duas coisas que a tela poderia misturar: **o papel** (fato sobre o usuário) e **o estado da
organização** (fato sobre o tenant). Se a consulta filtrasse por `tenants.ativo`, o cabeçalho sumiria
para quem continua tendo o papel — e sumiço silencioso é o pior modo de falha desta feature.

---

## Mutantes previstos e o cenário que mata cada um

| # | Mutante | Morto por |
|---|---------|-----------|
| M1 | Remover o `if (isMasterGlobal())` de `papelDoPainel()` | CT-04, CT-08, CT-14 |
| M2 | Trocar `papeisEmQualquerContexto()` por `roles()` | **CT-13** (e só ele) |
| M3 | Remover o `where('painel', $painel)` | CT-02 (papel de outro painel apareceria) |
| M4 | Remover o `where('guard_name', ...)` | nenhum — **lacuna conhecida**, ver abaixo |
| M5 | Registrar o hook só no `/admin` | CT-06 (datasets `app` e `infra`) |
| M6 | Trocar `Papeis::rotulo($papel)` por `$papel` cru | CT-08 |
| M7 | Remover a guarda `@if ($papelDoBadge)` | nenhum diretamente — o badge vazio não quebra asserção |
| M8 | Remover a guarda de usuário nulo no `user-menu-header` | CT-11 (a tela de login estouraria) |
| M9 | Trocar `assertVisible` do CT-B por `assertPresent` | — é o mutante do **teste**, evitado por construção |

### Lacunas de derivação assumidas

- **M4 (`guard_name`)**: o kit tem um único guard (`web`). Um caso que o matasse exigiria criar um
  papel homônimo em outro guard — cenário que não existe no kit e que o teste teria de fabricar.
  **Assumido como aceitável**; se o kit ganhar um segundo guard (API), este caso passa a ser
  obrigatório.
- **M7 (badge vazio)**: um `<span>` vazio renderizado não quebra nenhuma asserção de texto. Cobri-lo
  exigiria afirmar sobre o HTML da ausência, que é frágil. **Assumido**: o custo de detectar é maior
  que o dano (um badge vazio é feio, não errado).

## Cobertura do requisito

| RQ | Coberto por |
|----|-------------|
| RQ-02 (importar a view) | CT-06, CT-07, CT-08, CT-09, CT-10 |
| RQ-03 (todos os painéis) | CT-06 (dataset com os três) |

## Execução

```bash
php artisan test tests/Kit/CabecalhoDoMenuDoUsuarioTest.php
php artisan test tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php

# na suíte inteira
php artisan test --testsuite=Kit,Tenancy --parallel
```

**Resultado em 2026-08-18**: 33 casos novos, 52 asserções, verdes.
Suíte completa `Kit,Tenancy`: **388 casos, 1099 asserções, verdes** (era 355 antes desta feature).
