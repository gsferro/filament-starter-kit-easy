# Relatório de QA — W7: validação de e-mail editável

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (natureza `correção` + domínio **sensível**: a feature decide
> fronteira de acesso a painel)
> Natureza da wiki: correção · Regressão: **sim** (correção + toca infra compartilhada)

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 1 (**fechado no ciclo 2**) · Minor: 1 (débito) · Cosmético: 0
- Ambiente: suíte `Unit,Feature,Kit,Tenancy` + `php artisan route:list` no worktree ·
  Pest 5.1.1 · Filament 5.7.6 · Laravel 13.25.0
- **Playwright MCP: não usado — proibido nesta rodada** (instância única, 4 agentes em paralelo).
  Registrado em *Não Verificado*.

## Achados

### QA-01 — `assertSuccessful()` sozinho como oráculo do caso que nega redirecionamento · Major · destino 3

- **Dimensão**: K (adequação da suíte), passo estático
- **Relacionado a**: RQ-08, CT-10, `tests/Kit/VerificacaoDeEmailTest.php`
- **Esperado**: o caso que prova que `/admin` e `/infra` **não** passaram a exigir e-mail validado
  precisa afirmar que a resposta é a tela do painel.
- **Observado**: o caso afirmava só `assertSuccessful()`. Como o defeito que ele nega é justamente
  um **redirecionamento**, o oráculo era cego para o modo de falha mais próximo: um 200 que não é o
  painel (redirecionamento já resolvido, tela de erro renderizada com 200, shell vazio) passaria.
  Os cenários do `/app` já tinham a âncora; os dois painéis de administração ficaram sem — assimetria
  do autor, não decisão.
- **Repro**: trocar `assertSuccessful()` por um controller que devolve 200 vazio na rota do painel;
  o caso continua verde.
- **Destino**: 3 (teste). **Fechado no ciclo 2** com `assertSeeLivewire(Dashboard::class)`.
- **Lacuna de derivação que o deixou vivo**: *oráculo fraco sobre a resposta* — a mesma classe que a
  revisão adversarial do `04` apontou (achado 6) e que foi fechada nos cenários do `/app` e esquecida
  nos de fora dele. É o padrão "fechei a classe num lugar e não no vizinho".

### QA-02 — `ip` em texto claro no context do log · Minor · destino 5 (não-defeito) → aceito como débito

- **Dimensão**: D (observabilidade real, checagem de PII no context)
- **Relacionado a**: `ExigirEmailVerificado::registrarBarramento()`
- **Esperado**: nenhum dado pessoal em claro no context.
- **Observado**: o e-mail vai **mascarado** (`Str::mask(..., '*', 3)` → `pes***************`,
  asserção explícita em CT-14), e o `user_id` é chave interna. O `ip` vai em claro — e endereço IP é
  dado pessoal sob a LGPD quando associável a pessoa identificada, o que aqui ele é (vai junto do
  `user_id`).
- **Por que é destino 5 e não achado de código**: é o **padrão vigente do kit**, não uma decisão
  desta feature — `RegistroAberto::exigirPortaAberta()` já registra `'ip' => request()->ip()` na
  recusa, e o `autenticacao.log` inteiro é uma trilha de segurança, cujo valor forense depende do IP.
  Divergir aqui criaria inconsistência no mesmo canal.
- **Ação exigida**: nenhuma nesta feature. Registrado como débito de escopo maior — se o kit vier a
  adotar política de retenção/mascaramento de IP, o lugar é o canal, não este middleware.

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | Código | Resultado |
|----|----------|-----------|----|--------|-----------|
| RQ-01 | editável na tela | 3, 4, 5 | CT-11, CT-11b, CT-12, CT-13 | `ConfiguracoesDoKit` (settings + página), migration | ✅ |
| RQ-02 | decide por request | 1, 2 | CT-05/CT-06 (liga→desliga→liga num só caso) | `ExigirEmailVerificado::handle()` | ✅ |
| RQ-03 | middleware **próprio do kit** | 1, 2 | **CT-03b** (string no array de middleware da rota) | `emailVerifiedMiddlewareName()` | ✅ |
| RQ-04 | ligada barra quem não validou | 1 | CT-01, CT-01b (2 rotas), CT-01c (registro fechado), CT-04 (JSON), CT-07 (fluxo real) | idem | ✅ |
| RQ-05 | desligada não barra e não envia | 1, 2 | CT-02, CT-04 (`desligada → 200`), CT-07, CT-14b | idem | ✅ |
| RQ-06 | vale no request seguinte | 3 | CT-05/CT-06 + **CT-37 herdado** | linha do `mapaDeConfiguracao()` | ✅ com limitação declarada |
| RQ-07 | convite não regride | — | CT-09 | `Convite::aceitar()` **intocado** | ✅ |
| RQ-08 | `/admin` e `/infra` inalterados | — | CT-10 (comportamental) + CT-10 estrutural | nenhum dos providers pede verificação | ✅ |
| RQ-09 | rota de destino existe sempre | 2 | CT-08 (existe) + **CT-08b** (alcança) | `->emailVerification(EmailVerification::class)` incondicional | ✅ |

**Nenhuma omissão silenciosa.** As 9 cláusulas têm passo, teste e código.

**A limitação declarada de RQ-06**, porque ela é honesta e não é lacuna: nenhum caso prova "request
seguinte" num processo separado — `alinharConfiguracoesDoKit()` reproduz o que o
`KitServiceProvider::boot()` faz, e não pode ser diferente com `RefreshDatabase` (o boot acontece
antes de a tabela `settings` existir). A metade que falta — *"o boot faz isso sozinho"* — é provada
por **CT-37** de `ConfiguracoesDoKitTest`, que é estrutural e afirma que o alinhamento está no
`KitServiceProvider` e **em nenhum painel nem middleware**. O par cobre a cláusula; nenhuma das duas
metades a cobre sozinha. É o mesmo arranjo que a wiki `settings-do-kit` já declarou.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | 9/9 cláusulas rastreadas; nenhuma omissão |
| B | Fronteiras e dados | ✅ | domínio é **booleano** — não há fronteira ordenável. As duas partições estão instanciadas em CT-04, CT-08, CT-12 e CT-13. `email_verified_at` é lido como nulo/não-nulo por `hasVerifiedEmail()`, e as duas partições estão em CT-01…CT-03 |
| C | Matriz de permissão | ✅ | painel × papel × validado. A célula que interessava não estava no `04`: **`master_global` sem e-mail validado É barrado no `/app`** — o middleware não consulta o `Gate::before`. Verificado como **correto e sem risco de trancar ninguém fora**: o `/admin`, onde o toggle mora, não é afetado, então quem ligou por acidente consegue desligar. Destino 5, registrado aqui em vez de virar achado |
| D | Observabilidade real | ⚠️ | formato `[Classe@Método]`, channel `autenticacao`, nível `warning` (condição anormal não fatal), context com 4 campos, e-mail mascarado — todos verificados por CT-14. Caminho liberado sem log, por decisão (ADR-04). Ver QA-02 sobre o `ip` |
| E | Performance | ✅ | o middleware acrescenta **zero query**: uma leitura de `config()` (array em memória) e `hasVerifiedEmail()` sobre o usuário já resolvido pelo `Authenticate`. Nada é carregado do banco que já não fosse |
| F | UX de erro | ✅ | o barrado cai na tela de confirmação do Filament, traduzida, com ação de reenvio e de sair (a do Auth Designer acrescenta o logout). Nenhuma mensagem nova foi escrita, logo nada a avaliar de redação |
| G | Tema e cor | ⏭️ pulada | **sem superfície visual nova**: nenhum arquivo de `resources/` foi tocado, o único componente novo é um `Toggle` padrão do Filament, e a tela de confirmação já existia e já é visitada pelo smoke de browser do kit. Grep de classe de cor no diff: zero ocorrências |
| H | Acessibilidade | ⏭️ pulada | mesmo motivo: nenhum markup novo |
| I | Segurança da superfície nova | ✅ | a superfície nova são **duas rotas que passaram a existir sempre**. `prompt` exige autenticação (`authMiddleware` do painel); `verify` exige `signed` + `throttle:6,1` (vendor) — ou seja, só aceita link assinado pela `APP_KEY`. **IDOR**: a `verify` recebe `{id}/{hash}`, e é a assinatura que a protege, não o `id`. Sem mass assignment novo (a propriedade é do Settings, e a tela é permissionada). Nenhum dado sensível em resposta nova |
| J | Regressão adjacente | ✅ | suíte `Unit,Feature,Kit,Tenancy` completa (ver *Regressão* abaixo) + os CT da wiki ancestral por arquivo (`RegistroAbertoTest`, `ConviteTest`, `RegistroAbertoTenancyTest`, `ConfiguracoesDoKitTest`) |
| K | Adequação da suíte | ⚠️ | passo estático rodado: **1 achado** (QA-01), fechado. Passo medido (`--mutate`): ver *Não Verificado* |

## Regressão (obrigatória — correção + infra compartilhada)

O `## Impacto em Features Existentes` do PRD previu quatro pontos. O medido:

| Previsto no PRD | Medido |
|---|---|
| registro aberto não muda | confirmado — `RegistroAberto::registrar()` não foi tocado |
| convite não regride | confirmado por CT-09, e `Convite.php` não aparece no diff |
| `/admin` e `/infra` inalterados | confirmado **estruturalmente**: `route:list` → 12 rotas com o middleware, **todas** sob `/app`; `/admin` e `/infra` com zero |
| base legada: comportamento idêntico ao do `.env` | confirmado — o middleware barra pelo mesmo critério (`hasVerifiedEmail()`); o que mudou é a **facilidade de acionar**, mitigada no `helperText` e no README |

**Uma divergência entre previsto e medido**, e ela é achado do PRD (destino 1, severidade
Cosmético — não reprova): o PRD listou `ConfiguracoesDoKitTest` com a pergunta *"conta
propriedades? — verificar na revisão profunda"*. A revisão profunda achou o caso que conta
(`count(linhas) === count(mapa)`) **e**, ao consertá-lo, achou um segundo defeito que o PRD não
podia prever — CT-37 não podia falhar, porque a mensagem de falha estava sendo passada como segunda
agulha de `toContain()`, que é variádico. Detalhado em `03-progresso.md` → *Notas de Implementação*.
É defeito **anterior** à feature; foi o docblock do middleware novo que forçou a leitura do caso.

## Débitos Aceitos

- **QA-02** (Minor, destino 5): `ip` em claro no context do log de barramento — consistente com o
  padrão do canal `autenticacao`. Replicado em `03-progresso.md`.

## Suspeitas Não Confirmadas

- **Laço de redirecionamento** na tela de confirmação (levantado pela revisão adversarial do `04`):
  **refutado com medição**, não com argumento. `route:list` mostra que
  `filament.app.auth.email-verification.prompt` não carrega o middleware, porque nasce de um
  `Route::get()` direto no `routes/web.php` do Filament e não de `Page::registerRoutes()`. Mesmo
  refutado, virou CT-08b — o que hoje é verdade por construção passa a ser verdade verificada.

## Não Verificado

- **Mutation score do middleware** (`pest --mutate --path=app/Http/Middleware/...`): a classe tem
  **um** `if` e uma delegação, e os mutantes plausíveis dela estão nomeados e mortos no `04`
  (M1…M6). A medição foi deixada de fora porque o `--mutate` exige driver de cobertura e um run
  dedicado, e a suíte completa desta feature já leva ~25 min neste ambiente. **Declarado, não
  fingido.**
- **Confronto visual e inventário de elementos via Playwright MCP**: proibido nesta rodada por
  restrição do orquestrador (instância única, 4 agentes em paralelo). O que ele acrescentaria aqui é
  pouco — não há superfície visual nova —, mas a lacuna fica registrada.
- **Comportamento em processo separado** (`php artisan` de verdade lendo o valor do banco): ver a
  limitação declarada de RQ-06.

## Veredito — Ciclo 2

**APROVADO**

- QA-01 fechado: `tests/Kit/VerificacaoDeEmailTest.php`, caso *"deixa os paineis de administracao
  entrarem sem email validado"*, agora com `assertSeeLivewire(Dashboard::class)`.
- QA-02 permanece como débito aceito (destino 5).
- Nenhum achado novo no ciclo 2. Loop encerrado em 2 de 3 ciclos.
