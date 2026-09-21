# Requisito — Link de acesso direto ao painel da organização

## Fonte

- **Origem**: pedido do usuário no chat, invocando a skill `feature-wiki`
- **Data**: 2026-09-21
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado diretamente

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> no multi-tenant, na onde tem a parte que cadastra o nome e a slug, quero que adicione o link para clicar e acessar diretamente o painel app/{slug}
> - adicione tanto no form e no view
> - adicione na table list como acesso direto
> - crie uma branch especifica para essa wiki
> - abra o PR e faça o merge na main quando for aprovada + tag + release
> -

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Existe um link **clicável** que leva direto ao painel `/app/{slug}` da organização | "adicione o link para clicar e acessar diretamente o painel app/{slug}" | funcional |
| RQ-02 | O link aparece no **formulário**, junto de onde se cadastra nome e slug | "adicione tanto no form", "na onde tem a parte que cadastra o nome e a slug" | funcional |
| RQ-03 | O link aparece na **tela de visualização** | "e no view" | funcional |
| RQ-04 | O link aparece na **listagem**, como acesso direto | "adicione na table list como acesso direto" | funcional |
| RQ-05 | A URL do link é derivada do **slug** da organização | "o painel app/{slug}" | restrição |

## Instruções de Processo (não são RQ do produto)

| ID | Instrução | Trecho literal |
|----|-----------|----------------|
| PR-01 | Branch específica | "crie uma branch especifica para essa wiki" |
| PR-02 | Abrir PR; merge + tag + release **quando aprovado** | "abra o PR e faça o merge na main quando for aprovada + tag + release" |

> **PR-02 tem gate de aprovação explícito.** O agente para no PR.

## Ambiguidades e Perguntas Abertas

- **RQ-01 — O link pode ser um beco sem saída, e esta é a decisão central da feature.**
  Quem abre `/admin/organizacoes` **não necessariamente consegue entrar** em `/app/{slug}`. São
  **dois portões independentes**, nesta ordem:

  1. `User::canAccessPanel($painelApp)` — exige papel do painel `app`
     (`app/Models/User.php:canAccessPanel():219`). Um administrador que só tem papel do `/admin`
     **não entra no `/app` de organização nenhuma**.
  2. `User::canAccessTenant($tenant)` (`app/Models/User.php:canAccessTenant():789-809`) — libera se
     `isMasterGlobal()`, **ou** se houver linha no pivot; caso contrário **nega e loga**
     `[User@canAccessTenant] Acesso a tenant negado … motivo: sem_vinculo`.

  O docblock de `RelationManagers/UsersRelationManager.php:25` diz exatamente isso: *"É esta tela
  que decide quem consegue abrir `/app/{slug}`: sem linha no pivot, …"*.

  Então, para um administrador sem papel do `app` e sem vínculo, um link sempre visível é **um
  clique garantido para o 403** — em três telas diferentes. Três leituras possíveis:
  (a) mostrar sempre;
  (b) mostrar **só quando a pessoa realmente consegue entrar** (os dois portões);
  (c) mostrar sempre, desabilitado/avisando quando não puder.

  **DECIDIDO com o usuário em 2026-09-21: opção (a) — link sempre visível e clicável.**
  O agente havia recomendado a (b); a escolha do usuário prevalece e está registrada na ADR-02 com
  a consequência explícita. **Não é omissão**: quem não passar nos dois portões recebe o **403 do
  Filament**, e isso vira comportamento **coberto por caso de teste**, para ser conhecido em vez de
  descoberto em produção.
  **Se negado**: o link ganha a guarda dos dois portões; é uma condição em cada uma das três
  superfícies, e os CTs de visibilidade invertem.

- **RQ-02 — "no form" é ambíguo num formulário de criação.** No `CreateTenant` o registro **ainda
  não existe** e o slug pode estar vazio ou ser alterado antes de salvar. Um link para
  `/app/{slug}` de algo não gravado leva a 404.
  **DECIDIDO com o usuário em 2026-09-21: só no `EditTenant`.** No `CreateTenant` o link não
  aparece.
  **Invariante afirmado junto**: seja qual for a decisão, **nenhum link é renderizado apontando
  para um slug que não está gravado no banco**.
  **Se negado**: RQ-02 muda de forma e o passo correspondente do PRD é refeito.

- **RQ-04 — "acesso direto" na tabela: coluna ou ação?** O texto diz "como acesso direto", o que
  sugere um botão de ação por linha, mas não é explícito. O kit já tem o padrão de `Action` por
  linha em `TenantsTable.php`.
  **DECIDIDO com o usuário em 2026-09-21: coluna com a URL clicável**, e não ação por linha. A
  coluna mostra o endereço, o que torna o destino visível antes do clique.
  **Se negado**: vira ação de ícone, e o cenário correspondente do `04` é refeito.

- **RQ-01 — abre na mesma aba ou em nova?** Sair de `/admin` para `/app/{slug}` na mesma aba faz a
  pessoa perder o contexto de administração.
  **Premissa adotada**: **nova aba** (`openUrlInNewTab()`), que é o comportamento esperado de um
  atalho de "ir ver como está lá".
  **Se negado**: é uma linha em cada uma das três superfícies.

- **RQ-05 — o slug é a chave de rota?** Confirmar que `/app/{slug}` usa o slug e não o id — o
  `AppPanelProvider` chama `->tenant()`, e a chave de rota do `Tenant` precisa ser conferida antes
  de montar a URL à mão. **Preferir o gerador de URL do Filament a concatenar string.**

### Perguntas acrescentadas pela derivação dos casos de teste (2026-09-21)

<!-- Acrescentadas pela skill `feature-test-design` no step 4. Só perguntas: nada acima foi
     editado. Cada uma bloqueia o que está indicado e tem a premissa adotada registrada no
     `04-casos-de-teste.md`. -->

- **RQ-01 — o código de status do portão 2 é 404, não 403.** A decisão (a) acima foi registrada
  dizendo que quem não passa nos dois portões "recebe o **403 do Filament**". Isso está certo para
  o **portão 1** e **errado para o portão 2**:

  | Portão | O que nega | Status | Evidência no vendor |
  |---|---|---|---|
  | 1 | `canAccessPanel()` | **403** | `vendor/filament/filament/src/Http/Middleware/Authenticate.php:authenticate:35-41` (`abort_if(..., 403)`) |
  | 2 | `canAccessTenant()` | **404** | `vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:40-42` (`abort(404)`) |

  O `Authenticate` embrulha as rotas de tenant (`vendor/filament/filament/routes/web.php:60`), então
  o portão 1 decide primeiro. E o 404 do portão 2 é **deliberado**: já existe teste do kit
  afirmando-o e explicando o motivo — *"um 403 confirmaria que a organização EXISTE, e bastaria
  varrer slugs para enumerar os clientes da instalação"*
  (`tests/Tenancy/AdminDaOrganizacaoTest.php:98-105`).
  **Premissa adotada**: o `04` afirma o que o vendor faz — 403 no portão 1, 404 no portão 2 (CT-08).
  **Se confirmado**: este parágrafo e a ADR-02 passam a dizer 403 **e** 404, e nenhum CT muda.

- **RQ-01 — e o link de uma organização INATIVA, seguido até o fim?** A decisão (a) já resolve a
  **renderização**: o link aparece sempre, inclusive para organização inativa (a listagem mostra as
  duas). O que ninguém decidiu é o **destino**, e as duas metades do kit discordam:
  `User::getTenants()` filtra `->where('ativo', true)` (`app/Models/User.php:getTenants:775-782`),
  mas `canAccessTenant()` **não olha `ativo`** (`app/Models/User.php:canAccessTenant:789-809`).
  Alterar os portões está em `## Fora de Escopo`, então a feature não pode decidir isso sozinha.
  **Premissa adotada** (de escopo): o cenário de renderização é obrigatório e está escrito (CT-05,
  que afirma o link da inativa e que o endereço é o dela); o de **seguir** o link de organização
  inativa é **lacuna declarada** no `04`. **Bloqueia**: a célula `inativa × seguir` da matriz.
  **Se negado** (decidir que organização inativa não abre o painel): nasce um CT afirmando a recusa
  e a decisão sai de `Fora de Escopo`.

- **RQ-05 — o `slug` deve ser barrado fora do formulário?** `->alphaDash()` e `->maxLength(120)`
  vivem só em `TenantForm`; o model não tem barreira, e `Tenant::create(['slug' => '../outra'])`
  grava. A partir desta feature o `slug` deixa de ser só um segmento de rota e passa a compor um
  `href` renderizado numa tela de administração — a feature **depende** dessa validação sem ser
  dona dela.
  **Premissa adotada** (de mecanismo): o formulário é o único portão, e o CT escrito é o invariante
  que vale nas duas leituras — **a feature não normaliza o slug gravado** (CT-18), então um slug
  gravado por fora produz o link daquele slug e não um "consertado" que aponta para endereço
  inexistente. **Bloqueia**: o cenário de recusa fora do componente de UI para essa regra.
  **Se negado** (decidir que o model barra): CT-18 inverte e nasce um CT chamando
  `Tenant::create()` direto.

## Fora de Escopo (declarado)

- Conceder acesso, criar vínculo no pivot ou atribuir papel a partir do link — o link **navega**,
  não autoriza
- Alterar as regras de `canAccessPanel()` / `canAccessTenant()`
- Link para os painéis `/admin` e `/infra` (não têm tenancy)
- Impersonar usuário da organização (o kit já tem `filament-impersonate`, é outra feature)
