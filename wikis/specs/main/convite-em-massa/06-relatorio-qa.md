# Relatório de QA — Convite em massa

**Data**: 2026-08-23 · **Ciclo**: 1 de 3
**Oráculo**: `01-plano-acao.md` — **fraco**, e declarado

> Sem `00-requisito.md` e sem seção com a fala do usuário. Procurei: as 34 passagens entre
> aspas do plano são cópia de UI (*"Um por linha, ou separados por vírgula"*) ou paráfrase do
> autor (*"para validar direito"*, `01:221`), não citação. Confrontar entrega contra plano mede
> fidelidade de execução, não de requisito.

**Veredito: APROVADO COM DÉBITO.** Um Major de implementação — **corrigido nesta rodada** — e
um Minor de especificação.

---

## Cobertura: completa, e o `04` tem a tabela que prova

| Verificação | Resultado |
|---|---|
| CT especificados | 16, com tabela de rastreamento própria (`04:635-650`) |
| CT com arquivo de teste nomeado | 16 de 16 |
| Casos reais | 20 em `tests/Kit/ConviteEmMassaTest.php` + 4 em `tests/Tenancy/ConviteEmMassaTenancyTest.php` |
| CT-16 fora dos dois arquivos | por desenho — vive em `PaineisTest.php`, porque prova a subtração do `panel_user`, não o lote |

Os três CT de tenancy conferidos um a um contra o arquivo: CT-05 (`pula quem ja e membro`),
CT-09 (`carimba a organizacao corrente`), CT-14 (`recusa papel de outro painel`). Há um quarto
caso além dos CT — `usa a organizacao escolhida no lote do admin` —, que é cobertura a mais, não
a menos.

O `04` também declara, em "Cobertura dos métodos públicos", **quais CT falham hoje se o código
for revertido** (CT-02, CT-12, CT-16). É a única wiki auditada que escreveu isso.

---

## QA-01 — `KIT_CONVITE_LIMITE_LOTE` vazio bloqueava todo lote · **Major** · destino 2 · ✅ corrigido

- **Dimensão**: I (superfície nova) + fronteira de confiança de configuração
- **Observado**: `config/kit.php` trazia `'limite_do_lote' => (int) env('KIT_CONVITE_LIMITE_LOTE', 100)`.
  O segundo argumento do `env()` **só vale para chave ausente**: com `KIT_CONVITE_LIMITE_LOTE=`
  (chave presente, valor vazio — o que sobra quando alguém apaga o número e esquece o `=`),
  `env()` devolve string vazia e `(int) ''` é **0**.
- **Consequência**: `ConvidaEmMassa.php:85` faz `if ($emails->count() > $limite)` e chama
  `halt()`. Com limite 0, **qualquer** lote — inclusive de um endereço — é recusado, a modal fica
  aberta e a mensagem culpa a entrada da pessoa. A feature inteira desligada por um `=` sozinho,
  sem erro em lugar nenhum.
- **É a mesma família do defeito corrigido na v0.18.4** (`KIT_CONVITE_VALIDADE_DIAS` vazio fazia
  o convite nascer expirado). Aquela correção tratou **uma** chave; a varredura desta rodada achou
  o padrão em **cinco**.
- **Correção**: `NumeroDoEnv::positivo(env('KIT_CONVITE_LIMITE_LOTE'), 100)`. Vazio, `0` e
  ausente caem em 100; negativo e texto caem em 1. Guarda em `tests/Kit/NumeroDoEnvTest.php`,
  dataset de oito, vista falhando.

> **Achado colateral, e maior que este** — está no relatório de `lembretes-de-convite`, porque
> foi lá que a varredura terminou: a mesma família de defeito na retenção de exceções **apagava
> a trilha inteira**. Corrigido junto.

## QA-02 — a wiki chama o papel de `admin_organizacao`; ele é `admin_app` · Minor · destino 1

Seis ocorrências (uma delas dentro do enunciado de CT-09). O papel foi renomeado por
`database/migrations/2026_08_16_000001_rename_admin_organizacao_role.php` e nenhuma wiki soube —
39 ocorrências na wiki `admin-da-organizacao`, 4 em `convite-para-usuario-existente`, 6 aqui.
Corrigido nesta rodada.

---

## Hipóteses testadas e **rejeitadas**

| Hipótese | Veredito | O que a derrubou |
|---|---|---|
| *"`role_id` forjado no lote do `/app` cria `admin` da instalação"* | **Rejeitada** | CT-14 existe e é caso próprio: `recusa papel de outro painel no lote do painel de negocio` |
| *"um endereço inválido derruba o lote"* | **Rejeitada** | é o defeito declarado do `laravel-invite-only`, e CT-02/CT-10 cobrem os dois lados (inválido no meio, exceção no envio) |
| *"a ação aparece para quem só tem `ViewAny:Convite`"* | **Rejeitada** | `->authorize('create', Convite::class)` em `ConvidaEmMassa.php:45`, com CT-12 |
| *"o resumo do lote vaza token no log"* | **Rejeitada** | CT-08 assere o resumo mascarado e sem token |

---

## Dimensões

| | Dimensão | Estado | Nota |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | 16/16 CT cobertos; o ⚠️ é do **oráculo** |
| B | Fronteiras | ✅ | limite, duplicado, já membro, recusado, inválido no meio |
| C | Matriz de permissão | ✅ | CT-12 (affordance) e CT-16 (subtração) |
| D | Log real | ✅ | CT-08: resumo com `countBy`, mascarado |
| E | N+1 | ✅ | uma query por endereço já é o desenho; lote limitado |
| F | UX de erro | ✅ | `halt()` mantém a modal aberta — a pessoa não perde as cem linhas |
| I | Segurança da superfície nova | ⚠️→✅ | QA-01 era config, não código; corrigido |
| J | Regressão adjacente | ✅ | CT-13 (recusado não é reconvidado) amarra com a wiki irmã |
| K | Adequação da suíte | ✅ | o `04` declara os mutantes que cada CT mata |

## Ações

| # | Ação | Destino | Estado |
|---|---|---|---|
| 1 | Tratar vazio em `KIT_CONVITE_LIMITE_LOTE` | 2 | aplicado |
| 2 | `admin_organizacao` → `admin_app` (6 pontos) | 1 | aplicado |
