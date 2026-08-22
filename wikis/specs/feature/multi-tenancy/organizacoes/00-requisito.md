# Requisito — Multi-tenancy por organização

> ## ⚠ RECONSTRUÇÃO POSTERIOR — este arquivo NÃO é o requisito bruto
>
> **Escrito em**: 2026-08-22 · **Feature entregue em**: 2026-08-13 (commit `681be6d`)
>
> **Motivo**: a wiki nasceu sem `00-requisito.md` e a conversa que originou a feature não existe
> mais. O texto do usuário é **irrecuperável**. Sem um `00`, o gate de QA não tem oráculo: não há
> contra o que confrontar o que foi entregue.
>
> **Fidelidade: BAIXA.** Nada aqui é verbatim, exceto os dois fragmentos isolados da seção
> [Fala do usuário](#fala-do-usuário-o-que-sobrou), que sobreviveram entre aspas dentro da própria
> wiki. Todo o resto é **derivado** do plano, das ADRs, do progresso e do código entregue, e cada
> cláusula diz de onde saiu.
>
> **O limite disso, dito em voz alta**: uma cláusula derivada do código **não falsifica o código**.
> Se a entrega divergiu da intenção original, a divergência foi copiada para cá junto — o oráculo
> vira espelho. Por isso cada RQ carrega um **grau** (A/B/C), e as de grau **C** valem como registro
> do que o produto faz, **não** como critério de aceite. Só as de grau **A** e **B** servem para o
> QA acusar omissão.

## Fontes

Toda cláusula abaixo aponta para uma destas, com `arquivo:linha` ou commit:

| # | Fonte | O que ela testemunha |
|---|---|---|
| F1 | `01-plano-acao.md` (mesma pasta) | intenção contemporânea, escrita antes/junto da implementação |
| F2 | `02-decisoes-arquiteturais.md` | ADR-01 a ADR-06, todas datadas de 2026-08-13 |
| F3 | `03-progresso.md` | o que de fato foi feito, os desvios e a retrospectiva |
| F4 | `04-casos-de-teste.md` | os CT derivados na época (CT-01 a CT-12) |
| F5 | commit `681be6d` — `:sparkles: multi-tenancy opt-in via kit:tenancy` | corpo da mensagem + 49 arquivos |
| F6 | commit `a65ac17` — `:bug: kit:tenancy criava tabelas de permissao sem a coluna de tenant` | defeito pós-entrega (v0.9.2) |
| F7 | `CHANGELOG.md` — entrada `[0.9.1] - 2026-08-13` | resumo público da entrega |
| F8 | `README.md` — seção "Multi-tenancy (opt-in)" | promessa ao usuário do kit |
| F9 | `wikis/arquitetura.md:204-300` e `wikis/convencoes.md:43-76` | o resultado, já revisado por features posteriores |

**Nota de deriva**: F9 e o código na árvore de hoje (v0.18.2) descrevem um estado **posterior** —
`temPapelGlobal()` foi removido, `papeisEmQualquerContexto()` entrou, `AtivadorDeTenancy` foi
extraído (`e6989fd`), o `kit:install` passou a ligar tenancy sem recriar o banco. Onde a cláusula é
sobre a **entrega de 2026-08-13**, a citação é do commit `681be6d`, não da árvore atual.

## Fala do usuário: o que sobrou

Dois fragmentos entre aspas, preservados por quem escreveu a wiki na época. São o único texto do
usuário que existe:

> "vincular os usuários às organizações correspondentes"
> — citado em `01-plano-acao.md:307`, ao justificar o `UsersRelationManager` do `/admin`

> "vamos criar uma demo para validar"
> — citado em `02-decisoes-arquiteturais.md:177`, rotulado ali como **"o pedido original"**

Um terceiro fragmento aparece entre aspas em `02-decisoes-arquiteturais.md:83` — *"ver só as
organizações a que estou vinculado"* — mas o contexto o usa como paráfrase do requisito, não como
citação atribuída. **Não é tratado aqui como fala do usuário.**

## Cláusulas reconstruídas

**Grau**: **A** = fala do usuário sobreviveu · **B** = intenção contemporânea registrada no plano ou
nas ADRs (2026-08-13), antes ou junto do código · **C** = só a entrega testemunha; circular para
fins de QA.

| RQ | Cláusula | Origem | Grau | Tipo |
|----|----------|--------|------|------|
| RQ-01 | O kit continua nascendo **single-tenant**; um comando liga o modo multi-organização. Quem não usa não paga nada | `01:5`; ADR-01 (`02:12-16`) | B | funcional |
| RQ-02 | Com o modo ligado, o painel `/app` passa a ser `/app/{organizacao}` | `01:5`, `01:70-74`; F7 | B | funcional |
| RQ-03 | O usuário só enxerga as organizações às quais está vinculado | `01:5`; `02:83` (paráfrase); CT-01/CT-02 | B | funcional |
| RQ-04 | Acesso a organização **não** vinculada é barrado — é a fronteira contra adivinhação de slug/id na URL | `01:62`; CT-03 (`04:78-95`) | B | segurança |
| RQ-05 | O `master_global` acessa qualquer organização e **não perde os poderes** ao entrar numa | CT-04 (`04:99-115`), ancorado no invariante já testado em `tests/Kit/FundacaoTest.php:25` | B | funcional |
| RQ-06 | O `/admin` ganha o CRUD de organizações **e o vínculo de usuários**, e **não** é escopado — quem administra vê todas | `01:307` (fala do usuário); ADR-02 (`02:47`) | A | funcional |
| RQ-07 | O `/infra` segue **global**: saúde, filas, logs e backups são da instalação, não de um cliente | `01:5`, `01:103`; ADR-02 (`02:47`) | B | restrição |
| RQ-08 | Papéis passam a valer **por organização**: o mesmo usuário pode ser `admin` em A e usuário comum em B | `01:7`; ADR-03 (`02:79-83`, que registra "decisão do produto") | B | funcional |
| RQ-09 | Os papéis que governam `/admin` e `/infra` continuam valendo **fora** de qualquer organização | entrega: `Tenant::CONTEXTO_GLOBAL` (`681be6d:app/Models/Tenant.php:61`) e `User::temPapelGlobal()`; motivo em `03:124` | **C** | invariante |
| RQ-10 | Os dados de uma organização não aparecem nas telas de outra — o recorte é real, não visual | `01:17`, `01:123`; CT-05 (`04:119-142`) | B | segurança |
| RQ-11 | O recorte precisa cobrir também o que **não** passa por um Resource (job, comando, listener, API) | `01:123` (risco), `01:339` (a convenção `BelongsToOrganizacao` prevista no plano); entrega: `App\Traits\BelongsToTenant` | B | segurança |
| RQ-12 | A camada de IA passa a gravar a organização real em `ai_runs.tenant_id`, e o budget passa a ser por organização | `01:104`, `01:258-269` (passo 7); CT-10 (`04:243-258`) | B | funcional |
| RQ-13 | A URL usa o **slug**, não o id (`/app/acme`, não `/app/1`) | `01:76` | B | desenho |
| RQ-14 | O comando é **destrutivo** e precisa de salvaguardas: árvore git limpa, aviso explícito, confirmação, `--force` obrigatório em modo não-interativo | ADR-04 (`02:112`); `01:288-289`; entrega: `681be6d:app/Console/Commands/KitTenancy.php:100-160` | B | restrição |
| RQ-15 | Existe uma **demo opt-in** (`--demo`) cujo propósito é *ver* o isolamento funcionando, e que é descartável | `02:177` (fala do usuário); ADR-06 (`02:161-186`); `01:314-324` | A | funcional |
| RQ-16 | A suíte existente do kit continua válida e verde com a tenancy **desligada** | `01:52`, `01:106`; ADR-01 (`02:25`) | B | não-funcional |
| RQ-17 | Nenhuma dependência nova: tudo com o que já está instalado | `01:114-116` | B | restrição |
| RQ-18 | Vocabulário separado do rótulo: o **código** segue a API do Filament (`Tenant`, `tenants`, `tenant_id`) e o que o **usuário lê** é configurável, com "Organização" de default | `03:115`, que atribui a troca a "decisão do usuário" no meio da implementação — mas **não preserva a fala**; entrega: `config/kit.php:67-91` | **C** | funcional |
| RQ-19 | A feature tem channel de log próprio (`tenancy`), e a negação de acesso é registrada nele | `01:128-141`, `01:218` | B | não-funcional |

**19 cláusulas: 2 de grau A, 15 de grau B, 2 de grau C.**

### Sobre RQ-09 e RQ-18 (grau C)

São as duas cláusulas mais consequentes do conjunto e as duas que **nenhuma fonte anterior ao código
sustenta**:

- **RQ-09 foi descoberta na implementação.** A retrospectiva é explícita: *"Faltou no plano: a
  semântica de `model_has_roles.team_id` NOT NULL"* (`03:135`). Ela é defensável como invariante
  **pré-existente** do kit — "master_global vence qualquer gate" já era verdade e já era testado —
  mas a forma (o sentinela `0`) é invenção da entrega, não pedido.
- **RQ-18 é a maior mudança de rumo da feature.** O plano inteiro fala `Organizacao`/`organizacoes`;
  o código entregue fala `Tenant`/`tenants`. `03:115` diz que foi "decisão do usuário" e dá o motivo
  — mas o motivo registrado é o **do implementador**, e a fala não existe. Não há como saber se o
  usuário pediu a troca, aprovou uma proposta, ou apenas não recusou.

## O que não foi possível recuperar

Cada item abaixo é uma **lacuna declarada**. Nenhum foi preenchido com suposição plausível, porque
cláusula inventada vira oráculo falso e o QA passa a validar contra ficção.

1. **O texto bruto do pedido.** Não existe. Não há como saber o escopo que o usuário desenhou, o que
   ele pediu e foi cortado, nem em que ordem ele priorizou.
2. **403 × 404.** O CT-03 escreveu *"Requisição autenticada a `/app/globex` → **403**"* (`04:93`). A
   entrega devolve **404**, porque quem aborta é o Filament:
   `vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:40-41` faz `abort(404)` quando
   `canAccessTenant()` é falso (versão instalada hoje: `filament/filament` v5.7.6). O 404 é
   **melhor** — um 403 confirmaria que a organização existe e permitiria enumerar clientes varrendo
   slugs (`wikis/arquitetura.md:282`). Mas **não há registro de que o usuário tenha pedido qualquer
   um dos dois**. O `403` do CT-03 é hipótese do autor do CT, não requisito: **o QA não deve tratá-lo
   como intenção do usuário**.
3. **Auto-cadastro de organização.** O `->tenantRegistration()` foi deixado de fora, com
   justificativa do implementador no próprio provider (`681be6d:app/Providers/Filament/AppPanelProvider.php`,
   comentário do bloco de tenancy). Se o usuário pediu, proibiu, ou nunca falou do assunto:
   **desconhecido**.
4. **Convite / onboarding de usuário na organização.** A fala que sobrou diz "vincular os usuários",
   e o plano resolveu com `attach`/`detach` num RelationManager. Se o pedido era mais que isso
   (convite por e-mail, autosserviço) não há como saber. Features posteriores
   (`wikis/specs/main/convite-de-usuario/`) trataram o tema, mas **como pedido novo**.
5. **Lockscreen dentro do escopo da organização.** `01:105` levanta o risco e o CT-09 o cobriria, mas
   **nenhuma fala** exige a tela de bloqueio dentro do tenant — é risco identificado pelo
   implementador. (Registro do que aconteceu: o CT-09 entregue **não** inclui a rota do lockscreen;
   `681be6d:tests/Tenancy/TenancyTest.php:164-168` roda só `/admin/users`,
   `/infra/health-check-results` e `/infra/logs`.)
6. **Budget de IA por organização.** `01:104` lista a camada de IA como **impacto** da feature, não
   como pedido. Se o usuário pediu budget por cliente, ou se foi inferência do plano ao ver
   `ai_runs.tenant_id` NOT NULL já existindo (`01:14`): **desconhecido**.
7. **Critérios quantitativos.** Nenhum número foi registrado em fonte alguma: quantidade de
   organizações suportada, limite de usuários por organização, alvo de latência, tamanho de página.
   A feature não tem requisito não-funcional mensurável, e **isso é uma lacuna, não um "não se
   aplica"**.
8. **Tenancy no `/admin` e no `/infra`.** A ADR-02 apresenta a exclusão como decisão do
   implementador, com alternativas descartadas por argumento próprio. Se o usuário chegou a pedir
   recorte nos três painéis: **desconhecido**.
9. **O que fazer com projeto já em produção.** A ADR-04 documenta que a migração é manual, mas não há
   fala do usuário aceitando esse custo — a decisão está registrada só do lado de quem implementou.

## Fora de escopo (reconstruído — nenhuma exclusão tem fala do usuário)

Estas exclusões vêm todas do plano e das ADRs, **não** de uma recusa registrada do usuário:

- **Tenancy nos painéis `/admin` e `/infra`** — ADR-02.
- **Migration aditiva para ligar tenancy em banco já migrado** — ADR-04; o caminho fica manual.
- **Renomear `team_id` para `organizacao_id`** — ADR-05.
- **Demo sempre criada** — ADR-06; o `/app` continua nascendo vazio.
- **Auto-cadastro de organização pelo painel de negócio** — ver lacuna 3.
- **Tabela de convite/associação além do pivot** — `01:354`, sob a filosofia Ponytail do plano.
- **Scope global escrito à mão** — `01:354` proíbe explicitamente. **A entrega contrariou isso** ao
  criar `App\Traits\BelongsToTenant` com escopo global, e justificou em `03:129`: o escopo do
  Filament cobre só model **com** Resource. É desvio consciente e documentado, não omissão.

## Notas para o QA (não são cláusulas)

- **`05-casos-de-teste-browser.md`: esta wiki não deve ter.** O gate do 05 é "browser só para o que
  SÓ o navegador prova", e nenhuma cláusula acima cai nisso. RQ-04/05 são model e HTTP
  (`canAccessTenant`, `abort(404)`); RQ-10/11 são query; RQ-08/09 são spatie; RQ-14 é comando de
  console; RQ-02/13 são roteamento, prováveis com `$this->get()`. O único candidato de forma —
  trocar de organização pelo seletor do painel, que é Livewire/Alpine — nunca apareceu em cláusula
  nenhuma, e a superfície que ele exercitaria já é visitada hoje por
  `tests/BrowserTenancy/CapturaDeArteTest.php:102,115,197`, que abrem `/app/{slug}/projetos` num
  navegador real. Criar um 05 retroativo aqui produziria CT-B sem cláusula de origem — o defeito
  oposto ao que este arquivo existe para evitar.
- **`06-relatorio-qa.md` é viável**, com uma ressalva de método: as 15 cláusulas de grau B são
  confrontáveis (foram escritas antes do código) e as 2 de grau C **não são** — confrontá-las é
  comparar o código consigo mesmo. A matriz de rastreabilidade deve marcar RQ-09 e RQ-18 como
  "oráculo indisponível", nunca como "conforme".
- **As duas caixas abertas em `03:106-107`** não têm o mesmo destino. O `/ponytail:ponytail-review` no
  diff é **fato histórico**: o diff de `681be6d` já foi refatorado por cima (`e6989fd` extraiu o
  `AtivadorDeTenancy`, o `temPapelGlobal()` deixou de existir), então rodar a revisão naquele diff
  hoje revisaria código que não está mais no produto. O `kit:tenancy --demo` num clone limpo, com
  navegação manual, **continua valendo**: é o único caminho que exercita `migrate:fresh --seed` de
  verdade, nenhum teste pode cobri-lo, e foi exatamente ali que nasceu o defeito de `a65ac17` — a
  caixa aberta e o bug pós-entrega são o mesmo buraco.
