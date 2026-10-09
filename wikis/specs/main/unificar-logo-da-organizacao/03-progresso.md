# Progresso — unificar-logo-da-organizacao

**Estado**: implementado, em verificação
**Base**: `main`

## Requisito e Plano
- [x] `00-requisito.md` — 4 RQ, 4 perguntas respondidas pelo "prossiga" (confirmação das recomendações), 5 premissas
- [x] `01-plano-acao.md` — 5 passos, cobertura RQ × passo
- [x] `02-decisoes-arquiteturais.md` — ADR-01 (backfill por evidência de uso)
- [x] `03-progresso.md`

## Implementação
- [x] Migration `unifica_logo` + backfill (`2026_10_09_100000`)
- [x] `Tenant` — fillable + cast + `@property`
- [x] `TenantForm` — toggle + condição dupla no `logo_dark`
- [x] `IdentidadeDoKit::logosPara()` — flag por organização **+ guard `urlDaLogo() !== null`** (desvio do plano: sem ele, organização sem logo nenhuma suprimia a escura da instalação — o flag decide sobre as logos DELA)
- [x] `TenantFactory` + docs pt/en + CHANGELOG

## Testes

| CT | Cenário | Arquivo | Verde |
|---|---|---|---|
| CT-01 | fillable + cast + default `true` | `UnificaLogoDoTenantTest` | ✅ |
| CT-02 | toggle governa `logo_dark`; ambos ocultos com marca global unificada | idem | ✅ |
| CT-03 | gravação por componente (EditTenant) | idem | ✅ |
| CT-04 | unificada ignora `logo_dark` gravada | idem | ✅ |
| CT-05 | separada sem `logo_dark` → escura da instalação (Q4) | idem | ✅ |
| CT-06 | separada com `logo_dark` → a dela | idem | ✅ |
| CT-06b | unificada SEM clara → não suprime a escura da instalação | idem | ✅ |
| CT-06c | unificada com clara órfã → idem | idem | ✅ |
| CT-07 | lock screen sem `fi-logo-dark` para unificada | idem | ✅ |
| CT-08 | factory respeita `unifica` | idem | ✅ |
| CT-09 | docs pt/en | idem | ✅ (na árvore do kit) |

Suíte vizinha re-verde após o guard: `LogoDarkModeTest` (CT-09/16/40 receberam `unifica_logo => false` nas fixtures — o teste exercita o caminho separado), `CabecalhoDoPainelTenancyTest` (`organizacaoComLogos` ganhou `unifica`, default `false` com a mesma justificativa).

## Tickets

Não fatiado — 2026-10-09: 4 RQ vigentes, CTs a derivar no step 7, compactação: não, 0 perguntas de requisito abertas — nenhum sinal

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff (step 6 — zero achados)
- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/pest` — `UnificaLogoDoTenantTest` 12 verde; vizinhos re-verdes
- [x] `vendor/bin/pest --parallel --tia` — 4286 ok; falhas do diff corrigidas (KitInfo CT-06, SiteDoc CT-50, citações deslocadas); sobra = baseline de `main` (2× `TemaEscuroTest` acessibilidade + drift pré-existente do CT-26 em arquivos fora do diff)
- [x] `vendor/bin/phpstan analyse` — limpo (o `?->` esquerdo de `??` virou guarda explícita)
- [x] `pestw.cmd --mutate --path=app/Support/IdentidadeDoKit.php --covered-only --no-tia` — **37 mutantes, 100%, 4,81 s** (escopado aos 3 arquivos cobridores; a tentativa anterior sem `--no-tia` travou na cobertura completa — o restarter do TIA perde o `pcov.enabled`, armadilha já documentada no próprio `pestw.cmd`)
- [x] Revisão do diff (step 9 — o host não tem /code-review; revisão manual por eixos abaixo)
- [x] Reconciliação wiki × código — desvios 1-4 registrados; `04` ganhou CT-06b/CT-06c e a cláusula "clara própria resolvível" em R-C
- [x] Docs pt/en, CHANGELOG reconciliados — parágrafo novo nos dois idiomas; citações de linha deslocadas corrigidas (`urlDaLogo:193`, `artePadrao:136`, `users:118`, `configure:32`)
- [ ] `git commit` + tag + release

### Revisão do diff (step 9, manual — sem /code-review no host)

| Eixo | Resultado |
|---|---|
| Cobertura RQ×código | RQ-01 (coluna+backfill), RQ-02 (form), RQ-03 (`logosPara`), RQ-04 (docs/changelog) — todos mapeados a diff |
| Fronteiras | `unifica_logo` sem clara própria → par da instalação inteiro (guard); org `null` → inalterado |
| N+1 / consultas | `urlDaLogo()`/`urlDaLogoEscura()` são `Storage::exists` — sem query; `unifica_logo` é coluna do mesmo row já hidratado |
| Permissões | toggle no resource admin — `master_global` e admin do painel, mesma superfície dos campos vizinhos |
| Segurança | coluna bool + toggle — nenhuma superfície nova de escrita fora do form autorizado |
| Regressão | suíte TIA re-verde; fixtures vizinhas receberam o flag explícito onde exercitam o modo separado |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — `asset('storage/…')`, DTO, `Paineis` | `app/**` | n.a. | nenhum dos três pontos tocados — sem URL nova de disco |
| `models.md` | `app/Models/Tenant.php` | aplicada | coluna bool nova — casts + fillable + `@property` |
| `filament.md` — `Get`/`Set` do `Filament\Schemas` | `app/Filament/Admin/Resources/**` | aplicada | `Get` importado do namespace certo; `->visible()` com `Get` no `logo_dark` |
| `filament-resources.md` | `app/Filament/Admin/Resources/**` | n.a. | nenhum resource novo, nenhuma query |
| `support.md` | `app/Support/IdentidadeDoKit.php` | n.a. | nenhuma gravação de `.env` |
| `config.md` | `config/**` | n.a. | nenhuma chave nova — opção é coluna da organização |
| `testes.md` | `tests/**` | aplicada | helpers novos só no arquivo ou em `tests/Pest.php`; sem `not->toContain` com mensagem |
| `specs.md` | `wikis/specs/**` | aplicada | citações `{path}:{símbolo}:{linha}`; afirmações de vendor com `file:line` |

## Quality Gate

Step 11 executado na sessão (o host não despacha o `feature-quality-gate` como sub-agente; a passada manual cobriu as dimensões do perfil):

- **Cobertura do requisito** — RQ-01..RQ-04 todos com passo do plano, código e CT verde; matriz de rastreabilidade sem órfãos.
- **Fronteiras** — org sem logo: par da instalação intacto (guard, CT-06b/06c); org `null`: caminho antigo (R-G); marca global unificada: flag do tenant inerte por desenho (R-A).
- **Permissão** — nenhuma superfície nova: o toggle vive no mesmo resource e na mesma aba dos uploads vizinhos.
- **N+1** — zero consultas novas: `unifica_logo` é atributo do row já carregado; `Storage::exists` nos dois `urlDaLogo*()` já existia.
- **UX de erro** — `helperText` do toggle e do `logo_dark` explicam a queda para a instalação; campo escondido não é required nem validado.
- **Regressão** — TIA 4286 ok; as fixtures vizinhas receberam o flag explícito para continuar exercitando o modo separado.
- **Consistência documental** — 00–04 × código reconciliados; docs pt/en com o parágrafo do toggle; CHANGELOG com a entrada; citações de linha re-apontadas.

Sem achados bloqueantes. Único desvio de comportamento além do pedido: o guard `urlDaLogo() !== null` — documentado como desvio 1 e coberto por CT-06b/CT-06c.

## Candidatos a Rule

Nenhum. As decisões da feature são específicas dela (o flag decide sobre as logos da organização; o backfill infere separação por evidência de uso). O único aprendizado com cara de regra — "arquivo `.md` editado por ferramenta precisa de conferência de EOL porque `frontMatterDe()` exige `\A---\n`" — já está coberto pelo CT-29 do `SiteDeDocumentacaoTest`, que reprova o front-matter inteiro quando o EOL quebra; uma rule diria a mesma coisa sem adicionar dente.

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-09 — solicitante — 1 rodada; perguntas: 4 requisito (respondidas no mesmo "prossiga"), 0 fato, 0 desenho pendentes

### Confronto código × afirmação (step 5)

Nenhuma afirmação do `01` diverge do código — todas foram verificadas na própria sessão de escrita (leituras de `Tenant.php`, `IdentidadeDoKit.php`, `TenantForm.php`, `TelaBloqueio.php`, `LogoDarkModeTest.php`).

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "`logo_dark` do tenant cai para a `logo_dark` da instalação quando vazia" | Confirmado — `IdentidadeDoKit.php:logosPara:98-100` faz `$organizacao?->urlDaLogoEscura() ?? self::logoEscura()` | nenhuma |
| "Toggle precisa de `use Filament\Schemas\Components\Utilities\Get`" | Confirmado — `TenantForm.php:16` já importa `Set` do mesmo namespace | nenhuma |
| "A tela de bloqueio consome o mesmo par" | Confirmado — `TelaBloqueio.php:urlsDasLogos:105-108` chama `logosPara($this->organizacaoResolvida())` | nenhuma |
| "`comIdentidadeVisual()` já grava `logo_dark`" | Confirmado — `TenantFactory.php:41-47` | nenhuma |
| "A suíte Kit cria tenants sem tenancy ligada" | Confirmado — `LogoDarkModeTest.php:'[CT-09]':152` liga `kit.tenancy.enabled` no arranjo | nenhuma |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Inferir unificada por `logo_dark` vazia, sem coluna — seria o mínimo absoluto | recusada: vazio não é decisão gravada; quem apagar a escura depois mudaria de modo sem ver o toggle (ADR-01, alternativa 3) | `01`, passo 1 |
| 2 | Classe `Support` nova para o flag | recusada: `logosPara()` já é a dona da regra; classe nova seria indireção vazia | `01`, passo 4 |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| — | — | Sem despacho — host sem sub-agente | — | — | — | — | — |

## Blockers

Nenhum.

## Desvios do Plano

1. **`logosPara()` ganhou uma terceira cláusula** — `&& $organizacao->urlDaLogo() !== null`. O plano previa só `unifica_logo`; a suíte `CabecalhoDoPainelTenancyTest` (CT-43/46/49/58) revelou que organização SEM logo nenhuma suprimia a escura da instalação, mudando o topo de quem não contribui logo — fora do pedido. Com o guard, organização sem clara resolvível recebe o par da instalação inteiro. CT-06b/CT-06c cobrem os dois lados.
2. **Fixtures dos testes vizinhos receberam `unifica_logo = false` explícito** — `LogoDarkModeTest` CT-09/16/40 e o helper `organizacaoComLogos` exercitam a tabela do modo separado; sem o flag o default `true` da coluna as fazia afirmar o caminho unificado. Nenhum oráculo mudou.
3. **`pestw.cmd` (não `vendor/bin/pest`) para o mutate** — o wrapper já resolve o driver Windows.
4. **phpstan reprovou `?->` à esquerda de `??`** em booleano não-nulo — a guarda virou `($organizacao !== null && $organizacao->unifica_logo && …)`.

## Notas de Implementação

- CRLF vs LF: os edits em `docs/` chegaram com CRLF e derrubaram CT-29 (front-matter `/\A---\n/`). Regravados em LF — lição: qualquer `.md` editado por ferramenta precisa de conferência de EOL.
- O backfill `unifica_logo = (logo_dark IS NULL)` mora na migration, não em seeder: update existente precisa da decisão por linha, e seeder só roda em instalação nova.

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-09
- `template-01-plano.md` — step 4 — 2026-10-09
- `template-02-adr.md` — step 4 — 2026-10-09
- `template-03-progresso.md` — step 4 — 2026-10-09

## Retrospectiva
<!-- pós-implementação -->
