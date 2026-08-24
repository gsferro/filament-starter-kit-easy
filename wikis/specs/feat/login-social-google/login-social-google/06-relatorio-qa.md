# Relatório de QA — Login social com Google

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** — natureza `nova`, superfície de UI presente, domínio
> **sensível** (autorização, integração externa, segredo)
> Natureza da wiki: `nova`, **mas toca infra compartilhada** → regressão obrigatória

## Veredito — Ciclo 1

**REPROVADO → implementação** (Blocker QA-01) e **→ teste** (Major QA-02)

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 2 (débitos aceitos) · Cosmético: 0
- Ambiente: kernel HTTP em `tests/Kit` e `tests/Tenancy`; navegador via `composer test:browser` ·
  Pest 5.1.1 · Playwright MCP: **não usado** (proibido nesta rodada — instância única
  compartilhada com outros agentes)

---

## Achados

### QA-01 — o kit:update nunca entregaria o controller · **Blocker** · destino **2**

- **Dimensão**: J (regressão adjacente) — pego pelo próprio guard do kit
- **Relacionado a**: RQ-12, passo 5 do PRD
- **Esperado**: todo arquivo do kit está em `KitUpdate::CAMINHOS_DO_KIT`, para que quem já
  instalou receba a feature no próximo `php artisan kit:update`
- **Observado**: `app/Http/Controllers/Auth/LoginComGoogleController.php` estava fora. O kit não
  tinha nenhum controller próprio até agora, então `app/Http/Controllers` nunca precisou de linha
- **Impacto**: silencioso e do pior tipo. Quem já instalou receberia `config/services.php`,
  `config/kit.php`, `routes/web.php` e os blades — e **não** receberia o controller que a rota
  aponta. O botão apareceria e levaria a um destino inexistente
- **Repro**: `php artisan test --testsuite=Kit --filter=KitUpdate` → 1 falha, listando o arquivo
- **Evidência**: `tests/Kit/KitUpdateTest.php:142`
- **Ação exigida**: acrescentar `'app/Http/Controllers'` à lista — **feito** (commit `3bcfa86`)

### QA-02 — a feature estava coberta só em single-tenant · **Major** · destino **3**

- **Dimensão**: A (cobertura) + C (matriz de configuração)
- **Relacionado a**: RQ-07, RQ-15, passo 5 do PRD
- **Esperado**: o kit tem multi-tenancy opt-in, e `.ai/rules/testes.md` manda o caso que depende
  dela ir para `tests/Tenancy`
- **Observado**: os 54 casos viviam em `tests/Kit`, que roda **single-tenant**
  (`Tests\TestCase::usaTenancy()` → `false`). Todo o ramo `hasTenancy()` do controller — o destino
  de quem entra e o de quem se registra — **nunca era executado**
- **Impacto**: o caminho mais frágio ficava sem prova. Com a tenancy ligada, a rota do perfil
  exige o slug de uma organização, e conta recém-criada por login social não pertence a nenhuma —
  sem o guarda, `route()` lança `UrlGenerationException` e o callback responde **500 no exato
  caminho de quem acabou de se cadastrar**
- **Repro**: antes da correção, `grep -rl "google" tests/Tenancy/` → vazio
- **Ação exigida**: escrever os casos de tenancy **antes** de qualquer correção de código —
  **feito** (`tests/Tenancy/LoginSocialGoogleTenancyTest.php`, 4 casos, commit `7e21897`).
  Resultado: os quatro passaram, então o guarda já estava correto e **não houve correção de
  código** — a lacuna era só de prova

### QA-03 — CT-18 afirmava sobre o texto do arquivo, não sobre comportamento · **Minor** · destino **3**

- **Dimensão**: K (adequação da suíte)
- **Observado**: o caso do interruptor asserva `toContain("filter_var(env(...")` no
  `config/kit.php` (prende a redação) e `filter_var($valor, ...)` puro (testa a stdlib do PHP,
  nenhuma linha do kit)
- **Ação exigida**: usar `kitConfigCom()`, helper que **já existia** em `TextoDoEnvTest` e relê o
  `config/kit.php` com a env forçada — **feito**. Ele foi movido para `tests/Pest.php` porque
  passou a ter dois consumidores (`.ai/rules/testes.md`)

### QA-04 — comentário em volume acima do que o código sustenta · **Minor** · destino **5**, parcial

- **Dimensão**: K/estética
- **Observado**: a revisão de over-engineering apontou ~600 de ~1700 linhas de código/teste em
  comentário, e ~350 de 776 no arquivo de teste
- **Veredito**: **parcialmente não-defeito**. O estilo densamente comentado é o da casa
  (`config/kit.php` tem 291 linhas majoritariamente de comentário; `app/Models/User.php` e
  `tests/Kit/TelasDeAutenticacaoTest.php` idem), e o que esses comentários carregam — citação de
  `vendor/` com `file:line`, o "por que não X" que impede o próximo conserto errado — é
  exatamente o que `.ai/rules/specs.md` cobra
- **Aceito como débito**: os trechos que só reescrevem a asserção seguinte em prosa. Cortados os
  piores; o restante fica como débito **DL-09**

### QA-05 — a notificação de recusa chega à tela de login? · **não-defeito** · destino **5**

- **Dimensão**: F (UX de erro)
- **Suspeita**: `recusar()` usa `Notification::make()->danger()->send()`, que fora do Livewire é
  apenas `session()->push('filament.notifications', ...)`
  (`vendor/filament/notifications/src/Notification.php`). Se a tela de login não renderizasse o
  componente de notificações, **toda recusa seria silenciosa** — a pessoa voltaria ao login sem
  explicação nenhuma
- **Verificado**: `@livewire(Filament\Livewire\Notifications::class)` está em
  `vendor/filament/filament/resources/views/components/layout/base.blade.php:141`, e o layout do
  Auth Designer usa `<x-filament-panels::layout.base>`
  (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:13`).
  O componente puxa da sessão em
  `vendor/filament/notifications/src/Livewire/Notifications.php:37-38`
- **Veredito**: comportamento correto. Registrado para não voltar como suspeita no próximo ciclo

### QA-06 — o stack trace do `catch` pode vazar o `client_secret`? · **não-defeito** · destino **5**

- **Dimensão**: D (observabilidade) + I (segurança)
- **Suspeita**: o `catch` loga `'exception' => $e`. Uma falha no endpoint de token do Google
  produz exceção do Guzzle, e o corpo daquela requisição **contém o `client_secret`**. Se o
  formatador incluísse o stack trace com argumentos escalares, o segredo iria para o disco
- **Verificado**: (a) o channel `autenticacao` não configura `formatter` nem
  `includeStacktraces` (`config/logging.php:132-139`), e o default do Monolog é **não** incluir
  trace; (b) mesmo com trace, o segredo viaja dentro de um array (`RequestOptions::FORM_PARAMS`),
  e `getTraceAsString()` renderiza array como `Array`, não como valor; (c) a mensagem de
  `RequestException` do Guzzle inclui o corpo da **resposta**, não da requisição
- **Veredito**: não vaza. Coberto também por CT-13, que assere a ausência do valor do segredo no
  que o channel grava

---

## Matriz de Rastreabilidade

Só as linhas com observação. As demais (`RQ-01`, `RQ-03`, `RQ-05`, `RQ-06`, `RQ-09`, `RQ-10`,
`RQ-12`, `RQ-13`) estão completas: passo → CT → código → verde.

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Veredito |
|----|----------|-----------|----|------|--------|----------|
| RQ-02 | config decide se usa | 2,3,4 | CT-01 | — | `ConfiguracaoDoLogin::googleDisponivel()` | ✅ |
| RQ-04 | config expõe as três chaves | 2 | CT-17, CT-12, CT-13 | — | `config/services.php` | ⚠️ **metade fora de escopo declarada**: "abrir os campos" é ato de formulário, e o formulário é da branch de Settings |
| RQ-07 | quem se registra vai ao perfil | 5 | CT-09, CT-10 + tenancy | — | `urlDoPerfil()` | ⚠️ **atendida sob premissa**: criação gated por registro aberto (default false); papel não atribuído |
| RQ-08 | só Google agora | — | — | — | ADR-10 | ✅ sem CT, justificado: cenário de ausência de feature não pedida |
| RQ-11 | rodapé vem do Settings | 4,9 | CT-14, CT-15 | CT-B01 | `rodapeDoLogin()` | ⚠️ **atendida sob premissa**: lê de `config` hoje; o ponto único é o ponto de ligação (ADR-02) |
| RQ-14 | default false do register | 4 | — | — | `registroAberto()` | ⚠️ **fora desta entrega**, declarado no `00`. Só o *consumo* do interruptor, com default false |
| RQ-15 | true reflete em tudo | 4,6,7 | CT-01, CT-02, CT-03, CT-09 | — | `abort_unless` + blades | ✅ |

**Nenhuma omissão silenciosa.** As quatro linhas com ⚠️ estão declaradas em
`00-requisito.md` → `## Ambiguidades` / `## Fora de Escopo`, com o par "Assumido / Se negado".

---

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-02. Nenhuma omissão silenciosa; 4 premissas declaradas |
| B | Fronteiras e dados | ✅ | CT-01 (ausente × vazio), CT-07 (caixa, espaços), CT-11 (6 partições de `email_verified`), CT-14 (vazio × espaços), CT-18 (9 valores de env). Sem faixa ordenável no requisito ⇒ sem BVA |
| C | Matriz de permissão | ✅ | A feature **não tem** policy nem permission: é superfície de autenticação, anterior a qualquer papel, e não cria Page/Widget/Action — logo não entra na matriz do `PapeisSeeder`. A matriz relevante é config × rota, coberta por CT-02/CT-03 |
| D | Observabilidade real | ✅ | Channel, prefixo `[Classe@Método]`, nível por severidade, context não-vazio e e-mail mascarado: CT-13, CT-19, CT-20. **PII**: só e-mail (mascarado) e IP — o IP é consistente com o `authentication_log` nativo do kit. QA-06 fechou o risco do stack trace |
| E | Performance | ✅ | Uma query por callback (`contaCom`), nenhuma listagem, nenhum N+1 possível. Nenhum job |
| F | UX de erro | ✅ | QA-05 fechado. Mensagens genéricas são **decisão** (ADR-09), não pobreza: nomear a barreira entrega reconhecimento. O `motivo` no log é o que serve ao operador |
| G | Tema e cor | ✅ | Nenhuma classe de cor sem contraparte `dark:` nos dois blades; o botão é `<x-filament::button>`, vestido pelo Filament nos dois temas; divisor e rodapé usam `currentColor`/`inherit` + `opacity`. Os quatro hex são as cores da marca do Google, fixas por definição. **Legibilidade** em dark é DL-06 |
| H | Acessibilidade | ⚠️ parcial | `aria-label` no botão, `aria-hidden` no ícone e no divisor, `focusable="false"` no SVG. `assertNoAccessibilityIssues()` **não** foi adicionado: a tela é de plugin de terceiro e tem achados próprios — mediria a dívida do vendor. Débito DL-07 |
| I | Segurança da superfície nova | ✅ | Sem IDOR (nenhuma rota recebe id de recurso; a barreira é o `state`). `abort_unless` nas duas rotas. `throttle:10,1`. Sem mass assignment (`User::create` explícito; `$fillable` sem campo de autorização). Segredo sem caminho de saída (CT-12, CT-13). Nenhum token guardado |
| J | Regressão adjacente | ✅ | Obrigatória por "toca infra compartilhada". **716 testes, 715 passando** antes da correção — a única falha era QA-01, do guard do kit. Depois: verde. Regressão específica contra `TelasDeAutenticacaoTest` (as três telas de login) e `BloqueioDeSessaoTest`: intactos, e o par de casos de vazamento de `$layout` continua verde — esta feature não cria página de auth e não toca `$layout` |
| K | Adequação da suíte | ⚠️ | Passo estático: nenhum teste sem assertion; os três `assertOk()`/`assertNoJavaScriptErrors()` são de **apoio**, sempre acompanhados do oráculo real. Achados QA-03 e QA-04 corrigidos. Passo **medido** (`--mutate`): ver "Não Verificado" |

---

## Débitos Aceitos

Replicados em `03-progresso.md` → `## Débitos declarados`.

- **QA-04** (Minor) → **DL-09**: volume de comentário acima do que o código sustenta em alguns
  trechos. Os piores foram cortados; o restante segue o estilo da casa
- **DL-06**: legibilidade do rodapé em tema escuro — `assertSee` não valida tema e para defeito de
  cor não há saída barata
- **DL-07**: acessibilidade da tela de login com a superfície nova

---

## Suspeitas Não Confirmadas

Nenhuma. As duas que existiram (QA-05, QA-06) foram fechadas com leitura de `vendor/` e viraram
não-defeito registrado.

---

## Não Verificado

Honestidade sobre o alcance desta execução.

- **Mutation score (`pest --mutate`)** — não medido. `.ai/rules/testes-browser.md` registra que o
  ambiente **não tem PCOV** e que, em série com Xdebug, a análise **não termina** (medido:
  abortado após 35 min). A rule vence a skill, e a divergência está declarada no `04`. O que
  substitui: o gate de mutantes **de especificação** (63 mutantes previstos, 2 sem matador
  declarados no `04`), que enxerga omissão — coisa que o mutation score, por construção, não faz
- **Playwright MCP** — proibido nesta rodada (instância única compartilhada com quatro agentes).
  O inventário de elementos × cobertura do CT-B não foi confrontado. Mitigação: a superfície nova
  são **dois** elementos, ambos listados na `## Superfície de UI` e ambos com asserção
- **Log em disco** — o channel `autenticacao` usa `NullHandler` na suíte (fixado no `phpunit.xml`
  para não escrever no `storage/logs` real). A verificação de channel, formato, nível e context foi
  feita pelo espião (`espiarAutenticacao()`), que é mais forte para asserção e mais fraco para
  formato-em-disco
- **Fluxo real contra o Google** — por decisão: nenhum teste sai para a rede. O `state` de CSRF, o
  `email_verified` e o token nunca foram exercitados contra o provedor de verdade. É o trade-off
  declarado do `Socialite::fake()`, e a única parte que o fake não cobre (`state`) tem caso com o
  provedor real
