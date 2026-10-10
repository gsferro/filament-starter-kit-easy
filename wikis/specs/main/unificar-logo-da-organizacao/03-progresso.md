# Progresso — unificar-logo-da-organizacao

**Estado**: correções publicadas na v0.47.1; tag local v0.47.0 preservada; CI completo em execução no fechamento
**Base**: `683067a^` para a feature; `50706ad` para as correções ainda não commitadas

## Requisito e Plano
- [x] `00-requisito.md` — 4 RQ, 4 perguntas respondidas pelo "prossiga" (confirmação das recomendações), 5 premissas
- [x] `01-plano-acao.md` — 6 passos, incluindo correção da partial e cobertura RQ × passo
- [x] `02-decisoes-arquiteturais.md` — ADR-01 (backfill por evidência de uso)
- [x] `03-progresso.md` — histórico e evidências registrados neste arquivo

## Implementação
- [x] Migration `unifica_logo` + backfill (`2026_10_09_100000`) — CT-10 exercita registros anteriores à coluna
- [x] `Tenant` — fillable + cast + `@property`
- [x] `TenantForm` — toggle + condição dupla no `logo_dark`
- [x] `IdentidadeDoKit::logosPara()` — clara própria resolvida uma vez; sem clara utilizável em organização unificada, retorna o par completo da instalação — CT-11/CT-12
- [x] `TenantFactory` + docs pt/en + CHANGELOG — CT-08 e CT-09 cobrem factory e documentação

## Testes

| CT | Cenário | Arquivo | Verde |
|---|---|---|---|
| CT-01 | fillable + cast + default `true` | `UnificaLogoDoTenantTest` | ✅ |
| CT-02 | toggle governa `logo_dark`; ambos ocultos com marca global unificada | idem | ✅ |
| CT-03 | gravação por componente (EditTenant) | idem | ✅ |
| CT-04 | unificada ignora `logo_dark` gravada | idem | ✅ |
| CT-05 | separada sem `logo_dark` → escura da instalação (Q4) | idem | ✅ |
| CT-06 | separada com `logo_dark` → a dela | idem | ✅ |
| CT-11 (antigo CT-06b) | unificada SEM clara, com escura antiga → par completo da instalação | idem | ✅ |
| CT-12 (antigo CT-06c) | unificada com clara órfã e escura antiga → idem | idem | ✅ |
| CT-07 | lock screen sem `fi-logo-dark` para unificada | idem | ✅ |
| CT-08 | factory respeita `unifica` | idem | ✅ |
| CT-09 | docs pt/en | idem | ✅ (na árvore do kit) |
| CT-10 | backfill com registros anteriores à coluna | idem | ✅ |
| CT-B02 | logo único visível e da origem correta, nos dois temas | `tests/Browser/LogoDarkModeTest.php` | ✅ (4 combinações; arquivo completo 5 testes/41 assertions) |

Suíte vizinha re-verde após o guard: `LogoDarkModeTest` (CT-09/16/40 receberam `unifica_logo => false` nas fixtures — o teste exercita o caminho separado), `CabecalhoDoPainelTenancyTest` (`organizacaoComLogos` ganhou `unifica`, default `false` com a mesma justificativa).

## Tickets

Não fatiado — 2026-10-09: 4 RQ vigentes, CTs a derivar no step 7, compactação: não, 0 perguntas de requisito abertas — nenhum sinal

## Verificação histórica da entrega

Registro anterior à revisão. Suíte TIA e revisão manual abaixo não comprovam aceite das correções atuais; a revalidação está no `06-relatorio-qa.md`.
- [x] `/ponytail:ponytail-review` no diff (step 6 — zero achados)
- [x] `vendor/bin/pint --dirty` — execução histórica; correções atuais passaram em `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/pest` — `UnificaLogoDoTenantTest` 12 verde; vizinhos re-verdes
- [x] `vendor/bin/pest --parallel --tia` — 4286 ok; falhas do diff corrigidas (KitInfo CT-06, SiteDoc CT-50, citações deslocadas); sobra = baseline de `main` (2× `TemaEscuroTest` acessibilidade + drift pré-existente do CT-26 em arquivos fora do diff)
- [x] `vendor/bin/phpstan analyse` — limpo (o `?->` esquerdo de `??` virou guarda explícita)
- [x] `pestw.cmd --mutate --path=app/Support/IdentidadeDoKit.php --covered-only --no-tia` — evidência antiga de 37/100% invalidada; nova medição real: 42 mutantes, 37 mortos, 5 sobreviventes, 88,10%, 448,10 s; baseline 101 testes/518 assertions em 95,96 s
- [x] Revisão do diff (step 9 — o host não tem /code-review; revisão manual por eixos abaixo)
- [x] Reconciliação wiki × código — desvios 1-4 registrados; `04` ganhou CT-06b/CT-06c e a cláusula "clara própria resolvível" em R-C
- [x] Docs pt/en, CHANGELOG reconciliados — parágrafo novo nos dois idiomas; citações de linha deslocadas corrigidas (`urlDaLogo:193`, `artePadrao:136`, `users:118`, `configure:32`)
- [x] `git commit` + tag local — feature `683067a`, release `50706ad`, tag `v0.47.0`; publicação remota não verificada
- [x] Publicação remota — correções `365dc29`, release `11c408a`, tag `v0.47.1`; push de main e tag confirmado

### Fechamento da publicação — 2026-10-09

- Release publicada: https://github.com/gsferro/filament-starter-kit-easy/releases/tag/v0.47.1
- Check remoto `Release/marcador` aprovado: https://github.com/gsferro/filament-starter-kit-easy/actions/runs/38008040022
- Testes focados de logos e cabeçalho: 101 verdes, 518 assertions. KitInfo e checklist: 58 verdes, três pulados, 238 assertions.
- Pint passou; marcador, CHANGELOG e exemplos PT/EN concordam em 0.47.1. Tag local v0.47.0 continua em `50706ad`.
- CI completo estava em execução na publicação. As notas da release declaram os limites da validação local; instalação via Packagist e nova resolução de dependências não foram verificadas.

### Revalidação das correções — 2026-10-09

- [x] Logos e fallback — 159 testes relacionados/639 assertions; CT-11/CT-12 com escura antiga válida; CT-07 e CT-B02 verificam tema único
- [x] Browser — cinco testes/41 assertions; quatro combinações de origem e tema, visibilidade, carregamento e erros JavaScript
- [x] Backfill e cache anterior — CT-10 verifica registros preexistentes; payload serializado anterior falhou antes da correção e passou após migration
- [x] Launcher, ambiente e entrega — quatro regressões/12 assertions; variável externa `true` não altera CT-02; launcher presente nos quatro projetos instalados
- [x] Quatro cenários locais — resultados iniciais e reexecuções no `06-relatorio-qa.md`; teto 924 medido, +2 CT-09 PT/EN; dependências/assets reutilizados
- [x] Concorrência do arnês — limpeza restrita ao PID; duas suítes simultâneas no mesmo temporário passaram, 43 testes/236 assertions cada
- [x] Qualidade — Pint `--dirty --format agent` passou; PHPStan zero erros; Filacheck 17 rules passaram; validadores da wiki passaram

Publicação confirmada no fechamento acima. Axe, build novo e instalação pelo Packagist permanecem não verificados. Suítes integrais não foram repetidas após os ajustes finais; arquivos afetados foram reexecutados. Gate atual: **APROVADO COM DÉBITO**, restrito às evidências locais.

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

Registro histórico do step 11, reprovado pela revisão posterior: não comprova aceite atual. O relatório de revalidação está no `06-relatorio-qa.md`.

- **Cobertura do requisito** — RQ-01..RQ-04 todos com passo do plano, código e CT verde; matriz de rastreabilidade sem órfãos.
- **Fronteiras** — org sem logo: par da instalação intacto (guard, CT-06b/06c); org `null`: caminho antigo (R-G); marca global unificada: flag do tenant inerte por desenho (R-A).
- **Permissão** — nenhuma superfície nova: o toggle vive no mesmo resource e na mesma aba dos uploads vizinhos.
- **N+1** — zero consultas novas: `unifica_logo` é atributo do row já carregado; `Storage::exists` nos dois `urlDaLogo*()` já existia.
- **UX de erro** — `helperText` do toggle e do `logo_dark` explicam a queda para a instalação; campo escondido não é required nem validado.
- **Regressão** — TIA 4286 ok; as fixtures vizinhas receberam o flag explícito para continuar exercitando o modo separado.
- **Consistência documental** — 00–04 × código reconciliados; docs pt/en com o parágrafo do toggle; CHANGELOG com a entrada; citações de linha re-apontadas.

Conclusão histórica invalidada: foram confirmados logo invisível no tema escuro, reaparecimento da escura antiga sem clara resolvível e pontuação de mutação inválida. CT-11/CT-12 agora incluem escura antiga válida; CT-07 verifica as classes; CT-B02 verifica visibilidade real.

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
| "Toggle precisa de `use Filament\Schemas\Components\Utilities\Get`" | Confirmado — `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:Set:17` importa `Set` do mesmo namespace | nenhuma |
| "A tela de bloqueio consome o mesmo par" | Confirmado — `app/Filament/Pages/Auth/TelaBloqueio.php:urlsDasLogos:105` chama `logosPara($this->organizacaoResolvida())` | nenhuma |
| "`comIdentidadeVisual()` já grava `logo_dark`" | Confirmado — `database/factories/TenantFactory.php:comIdentidadeVisual:44` | nenhuma |
| "A suíte Kit cria tenants sem tenancy ligada" | Confirmado — `tests/Kit/LogoDarkModeTest.php:'[CT-09]':152` liga `kit.tenancy.enabled` no arranjo | nenhuma |

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

1. **Implementação histórica: `logosPara()` ganhou uma terceira cláusula** — `&& $organizacao->urlDaLogo() !== null`. O plano previa só `unifica_logo`; a suíte `CabecalhoDoPainelTenancyTest` (CT-43/46/49/58) revelou que organização SEM logo nenhuma suprimia a escura da instalação, mudando o topo de quem não contribui logo — fora do pedido. Com o guard, organização sem clara resolvível recebe o par da instalação inteiro. Na revisão, CT-11/CT-12 substituíram CT-06b/CT-06c e revelaram que a guarda ainda permitia escura antiga da organização. A correção atual resolve a clara uma vez e retorna o par completo da instalação antes de escolher a escura.
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
