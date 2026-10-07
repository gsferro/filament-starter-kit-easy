# Progresso — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

**Estado**: em revisão
<!-- Uma linha só, no topo: em planejamento | em implementação | em revisão | concluída — {YYYY-MM-DD}.
     Pedido do despacho do step 4: "em andamento" até o início da implementação. O indice.sh lê esta linha. -->

> Branch: `fix/logo-dark-do-tenant` · Base do PR: `{base}` = `main` (`b347fcc`, v0.45.1) · Worktree: `D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/wt-logo`

## 1. `IdentidadeDoKit::logosPara()` e a delegação da tela de bloqueio
- [x] `IdentidadeDoKit::logosPara(?Tenant): array{clara, escura}` com a regra por variante de `## Mapeamentos` do `01` — commit `e969341`; `LogoDarkModeTest` 41/41 2026-10-07
- [x] `TelaBloqueio::urlsDasLogos()` delega ao método, mantendo o `once()`; docblock aponta para o método — commit `e969341`; CT-54 verde 2026-10-07
- [x] CT-16 de `LogoDarkModeTest` verde sem alteração; `logosPara(null)` igual ao par da instalação — CT-16 verde, `LogoDarkModeTest` 41/41 2026-10-07

## 2. `CabecalhoDoPainel`: marca simples e composição com a organização aberta
- [x] `organizacaoAberta()` (`getCurrentPanel()?->hasTenancy()` e `Tenant`, nos dois pontos) e memo único por `Request` com segmentos e par de logos (D4) — commit `e969341`; CT-52 e CT-53 verdes 2026-10-07
- [x] `marca()`, `marcaEscura()` e `resolverSegmentos()` usam o par; docblocks (classe, `resolverSegmentos()`, `marca()`/`marcaEscura()`) com a ressalva da organização aberta — commit `e969341`; docblocks revisados na rodada do step 9 (RC-03) 2026-10-07
- [x] `/app/{organização}` com logo própria mostra o par da organização nas duas formas; `/admin`, `/infra` e `/app` sem organização idênticos aos de hoje — CT-42 a CT-51 verdes, `CabecalhoDoPainelTenancyTest` 45/45 2026-10-07

## 3. Factory e textos que a mudança desmente
- [x] `TenantFactory::comIdentidadeVisual()` com `?string $logoEscura = null` no fim (D3) — commit `e969341`; usada por CT-54 e CT-49 2026-10-07
- [x] `helperText` do upload `logo` do `TenantForm` e docblock de `Tenant::urlDaLogoEscura()` — commit `e969341`; helperText revisto na rodada do step 9 (RC-05) 2026-10-07
- [x] `vendor/bin/filacheck --fix` e `vendor/bin/pint --dirty` silenciosos — filacheck 17 regras passaram, pint passed 2026-10-07

## 4. Testes de backend
- [x] `tests/Kit/LogoDarkModeTest.php`: CT novo da regra `logosPara()` (tabela de decisão, `null`, só a escura, só a escura sem clara na instalação); CT-16 intocado — commit `7d2781b`; `LogoDarkModeTest` 41/41 2026-10-07
- [x] `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`: marca simples, composição, quedas, unificada, `/admin` e `/infra` com organização obsoleta, duas organizações, update Livewire da `Topbar`, um só `fi-logo-dark`, só a escura; todo caso de ausência com controle positivo — commit `7d2781b`; `CabecalhoDoPainelTenancyTest` 45/45, juntos 86/86 com 447 asserções depois da rodada do step 9 2026-10-07
- [x] CT-07 substituído por caso com ID desta wiki (aprovação: Adendo 1); CT-27 sem alteração; `gravarLogoDaInstalacaoDaTenancia(bool $unifica = true)`; docblock cita as duas wikis — commit `7d2781b`; CT-49 no lugar do CT-07, CT-27 intocado 2026-10-07
- [x] casos novos vermelhos com o passo 2 revertido — mutantes medidos pela sessão sobre os 86 testes (ver `## Notas de Implementação`): todos mortos 2026-10-07

## 5. Teste de navegador do swap no topo do `/app`
- [x] `tests/BrowserTenancy/IdentidadeVisualTest.php`: marca simples (B1) e composição (B2), uma visita por tema; `afterEach` apaga os arquivos novos — commit `7d2781b`; `IdentidadeVisualTest` 9/9 (54 asserções) 2026-10-07
- [ ] verde em série; vermelho com o passo 2 revertido

## 6. Documentação (pt e en), CHANGELOG e README
- [x] `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/recursos/configuracoes-do-kit.md`: a frase da composição e a da logo por tema — commits `e969341` e `7d2781b` (docs citam `/app`); `SiteDeDocumentacaoTest` 91/91 2026-10-07
- [x] `CHANGELOG.md` `[Unreleased]` → `### Corrigido` — commit `e969341` 2026-10-07
- [x] `README.md` e `README.en.md`: features especificadas 76 para 77 e o badge de casos de teste recontado; `SiteDeDocumentacaoTest` verde — commit `84c6d01` (badge 1.976); `SiteDeDocumentacaoTest` 91/91 2026-10-07

## 7. Índice das wikis
- [ ] `wikis/specs/INDEX.md` regenerado por `indice.sh` (não à mão)

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [x] Casos de teste derivados no `04` e no `05` pela `feature-test-design` (step 7) — 18 CT (CT-40 a CT-57) e 1 CT-B (CT-B01), 11 regras, 47 mutantes no `04` e 5 no `05` (52), 0 sem matador; perfil padrão com Impacto 3 na área da organização do topo; revisão adversarial: 26 achados, 6 altos; aplicados 23, recusados 3 com motivo (ADV-18: objeto não-organização com atributo `logo`, já coberto pela assinatura tipada e por CT-52; ADV-25: `alt` fora de escopo por P-06; ADV-26: desvínculo com aba aberta, fora de escopo e sem RQ); P-05 do `00` reescrita (ADV-24); `rastreabilidade.sh`, `citacoes.sh` e `checkbox-sem-evidencia.sh` conferidos, 2026-10-07
- [x] `tests/Kit/LogoDarkModeTest.php`: CT-40, CT-41 (regra de resolução do par), CT-54 (tela de bloqueio, organização da sessão), CT-55 (documentação pt/en); numeração a partir de CT-40 decidida pela sessão em 2026-10-07 (ver `### Colisão de IDs` do `04`; `ids-ct.sh` acusa os CT-01 a CT-20 da ancestral `logo-dark-mode` que vivem no mesmo arquivo) — commit `7d2781b`; 41/41 2026-10-07
- [x] `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`: CT-42 a CT-53, CT-56 e CT-57; CT-49 substitui o CT-07 da wiki ancestral `cabecalho-do-painel` (aprovação: Adendo 1 do `00`); CT-27 e CT-04 da ancestral ficam como regressão — commit `7d2781b`; 45/45 2026-10-07
- [x] `tests/BrowserTenancy/IdentidadeVisualTest.php`: CT-B01 (um `Esquema do Cenário`: marca simples e composição, uma visita por tema) — commit `7d2781b`; 9/9 em série, 54 asserções 2026-10-07

## Tickets
<!-- Step 8. -->

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix`: nenhum achado em `app/Filament`
- [ ] `vendor/bin/pest tests/Kit/LogoDarkModeTest.php --compact` e `vendor/bin/pest tests/Tenancy/CabecalhoDoPainelTenancyTest.php --compact`
- [ ] `vendor/bin/pest tests/BrowserTenancy/IdentidadeVisualTest.php` (em série)
- [ ] Regressão: `LogoDarkModeTest`, `CabecalhoDoPainelTest`, `CabecalhoDoPainelTelaTest`, `CabecalhoDoPainelTenancyTest`, `IdentidadeVisualTenancyTest`, `ArquiteturaDoCodigoTest`, `CitacoesDeCodigoTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest` e os três arquivos de navegador da regressão
- [ ] Suíte afetada (ou `--group=kit`) contra a **baseline** de `main`: nada além das falhas pré-existentes por nome
- [ ] `pest --mutate --path=app/Support/IdentidadeDoKit.php` (via `pestw.cmd`, sem `--filter`, `--no-tia`): score, duração e sobreviventes listados; mutantes manuais: remover a guarda de painel de `organizacaoAberta()` e inverter a ordem das quedas
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso (`RQ`/`P-nn` × passo do `01` × CT do `04`)
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/Kit/LogoDarkModeTest.php'`, `'tests/Tenancy/CabecalhoDoPainelTenancyTest.php'` e `'tests/BrowserTenancy/IdentidadeVisualTest.php'` silenciosos
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix; os demais "não falsificável nesta pilha", com motivo
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

<!-- Cada [x] acima leva " — {evidência}, {data}". O texto do item não leva " — ": o travessão separa a evidência. -->

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RC-01 | revisor do diff (Major) | CT-50 "sem vínculo" sem `assertNotFound` nem controle positivo; cláusula `null` da instalação sem o que medir | teste | CT-50, M25 | aplicado: `assertNotFound()`, `<div class="sn-brand">` com o nome da aplicação na MESMA resposta, parâmetro `null` removido; M25 ganha matador |
| RC-02 | revisor do diff | comentários de `brandLogo`/`darkModeBrandLogo` do `app` ainda dizem "logo de sempre" | implementação | — | aplicado em `AppPanelProvider` (mesma contagem de linhas, citações dos docs intactas); `/admin` e `/infra` sem mudança |
| RC-03 | revisor do diff | "a marca é a de hoje"/"o de hoje" em `CabecalhoDoPainel`; ressalva da P-12 repetida em `resolverSegmentos()` e em `organizacaoAberta()` | implementação | — | aplicado: "o par de `logos()`", ressalva só no docblock da classe, `organizacaoAberta()` com 3 linhas |
| RC-04 | revisor do diff | docs listam o "bloqueio" entre as telas de autenticação com logo da instalação | docs | CT-55 | aplicado em pt e en: login e recuperação de senha usam a instalação, a tela de bloqueio a organização da sessão |
| RC-05 | revisor do diff | `helperText` do upload `logo` não diz o que acontece sem logo da instalação | implementação | — | aplicado em `TenantForm` |
| RC-06 | revisor do diff | comentários de `TenantForm` e `TenantsTable` citam só a tela de bloqueio | implementação | — | aplicado: citam também o topo do `/app` |
| RC-07 | revisor do diff | título do CT-27 sugere "com organização ativa" em geral, mas a organização dele não tem logo | teste | CT-27 | aplicado: "com organização ativa SEM logo" (o ID fica) |
| RC-08 | revisor do diff | declarado: o `alt` com o nome da organização está fora de escopo por P-06 do `00` (RD-12); sem mudança desta rodada | — | — | sem ação registrada aqui: o texto do achado não foi repassado ao construtor |
| RC-09 | revisor do diff | dois parsers de `<img>` iguais (`imagensDaMarcaPorVariante` e `imagensPorVarianteDaTelaDeBloqueio`) | teste | `testes.md` | aplicado: um helper em `tests/Pest.php`, as duas cópias saíram; `HelpersDeTesteTest` verde |
| RC-10 | revisor do diff | `.fi-user-menu-trigger` é seletor por classe de CSS | teste | CT-B01 | aplicado: `button[aria-label="Menu do usuário"]` (o botão do topbar tem `aria-label`, `user-menu.blade.php` do Filament); `IdentidadeVisualTest` 9/9 |
| RC-12 | revisor do diff | CT-48 "ligada (composição)" não conta as `<img>` da região | teste | CT-48 | aplicado: a região `kit-cabecalho` tem exatamente 1 `<img>` |

**Ponytail do diff**: net -190 proposto. Aplicados: helper único dos parsers em `tests/Pest.php`; `expectParDaMarca()` no arquivo Tenancy para o padrão repetido do par (8 usos); recorte do CT-55 por `Str::before`/`Str::after`, mesmos oráculos; ressalva da P-12 só no docblock da classe; docblock de `organizacaoAberta()` em 3 linhas. Recusados: fundir CT-42/43/46 e CT-41 em CT-40 (casos distintos por oráculo e por mutante), cortar linhas do CT-40/CT-49/CT-53, remover `$nenhumaOculta` do CT-B01 (é o oráculo de que a variante oculta de fato some) e reverter o docblock de `Tenant.php` (decisão do revisor).

## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix; os demais "não falsificável nesta pilha", com motivo
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

<!-- Cada [x] acima leva " — {evidência}, {data}". O texto do item não leva " — ": o travessão separa a evidência. -->

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/ lida no step 4. Rascunho: o veredito (aplicada / n.a. / violada) e a evidência entram na implementação. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `specs.md`: justificativa de comportamento de pacote só depois de ler o vendor; citação por símbolo; citação de teste pelo ID do CT | `wikis/specs/**` | a aplicar | o comportamento do `logo.blade.php`, de `getTenant()` e de `hasTenancy()` foi lido no vendor e citado com símbolo no `01`; `citacoes.sh` na reconciliação |
| `app.md`: URL de arquivo é `asset('storage/…')`; painel por `Paineis::correnteOuPadrao()`, com a exceção de quem não pode cair no painel padrão | `app/**` | a aplicar | `logosPara()` só consome `urlDaLogo()`/`urlDaLogoEscura()` (já em `asset()`); `organizacaoAberta()` lê `Filament::getCurrentPanel()?->hasTenancy()` e **não** `Paineis::correnteOuPadrao()` (que cai no `app`), pela exceção da rule; nenhum `Storage::url()` novo |
| `support.md`: chave do `.env` só por `SubstituicaoEmArquivo` | `app/Support/**` (`IdentidadeDoKit`, `CabecalhoDoPainel`) | n.a. | o diff não grava `.env` (`grep -c "definirNoEnv\|\.env"` no diff, a medir) |
| `models.md`: models, `papelNoPainelCorrente()` como precedente de fonte única | `app/Models/**` (docblock de `Tenant::urlDaLogoEscura()`) | a aplicar | só docblock em `Tenant`; a fonte única da regra segue o precedente (ADR-02) |
| `filament.md`: o que toca `app/Filament`, Resource e permissões | `app/Filament/**` (`TelaBloqueio`, `TenantForm`) | a aplicar | nenhuma Page, Widget, Action ou Resource nova (nada a ressemear nem a subtrair do `panel_user`); `TenantForm` só troca texto de ajuda; `filacheck --fix` |
| `auth.md`: página de auth que usa o layout do Auth Designer redeclara `$layout`; cobrir `fi-auth-layout` em par | `app/Filament/Pages/Auth/**` (`TelaBloqueio`) | a aplicar | `TelaBloqueio` só troca o corpo de `urlsDasLogos()`; não toca `$layout`, e `tests/Kit/BloqueioDeSessaoTest.php` (o par `fi-auth-layout`) entra na regressão |
| `filament-resources.md`: Resource novo com badge de contagem, import/export decidido | `app/Filament/**/Resources/**` (`TenantForm`) | n.a. | nenhum Resource novo e nenhum trait de navegação tocado: o `TenantForm` só troca texto de ajuda de um campo |
| `providers-filament.md`: plugin que resolve o painel corrente nos três painéis | `app/Providers/Filament/**` | n.a. | nenhum `PanelProvider` editado: as Closures de `brandLogo()`/`darkModeBrandLogo()` já delegam a `CabecalhoDoPainel` |
| `views.md`: nunca diretiva do Blade dentro de `{{-- --}}` | `resources/views/**` | n.a. | nenhuma view editada (`cabecalho-do-painel.blade.php` recebe outras URLs, não muda) |
| `testes.md`: helper de uso cruzado em `tests/Pest.php`; `toContain()` sem mensagem; caso que lê `docs/`/README com `naArvoreDoKit()` | `tests/**` | a aplicar | os casos novos entram no arquivo dos helpers, que continuam usados por um arquivo só e não migram; oráculo de ausência com `assertStringNotContainsString` e controle positivo no mesmo caso; qualquer caso novo que leia docs pula fora da árvore |
| `testes-browser.md`: view e componentes aquecidos pelo kernel; `inDarkMode()` só no load; sem `--parallel`; sem arranjo de painel no `beforeEach`; disk `public` real | `tests/BrowserTenancy/**` | a aplicar | cenário arranja o painel e aquece com `$this->get()`; uma visita por tema; limpeza dos arquivos no `afterEach`; rodar em série |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Candidatos a Rule

<!-- Step 12, depois do veredito. -->

## Auditoria Pré-Implementação
<!-- Saída dos steps 4 a 6, ANTES de escrever código. -->

Entendimento confirmado: pendente (step 5); revisão profunda e ponytail já registradas abaixo

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5): premissas do plano contra o código real

step 5 — revisão profunda (analista/opus): 18 achados RD-01..RD-18 (7 médias, 11 baixas), todos aplicados; varredura da classe irmã: a Closure da cor da organização em `AppPanelProvider` (guarda por `getCurrentPanel()`).

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|

### Auditoria Ponytail (step 6)

step 6 — ponytail (sonnet): 38 achados, net -140 proposto; aceitos os de A (regra escrita uma vez, sem log novo, tabela de decisões enxuta, sem arquivos de teste novos, ADR-01 enxuta), recusados os demais por template/scripts ou decisão D3.

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. -->

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 4 | construtor · `general-purpose`: rascunho do `01`, `02` e `03` | sonnet | a conversa da sessão; recebeu o `00`, o mapeamento do step 3 e as decisões D1 a D5 já tomadas | `01` (7 passos, revisão RQ-01/RQ-02 com 12 superfícies e 8 lacunas), `02` (3 ADRs), `03` (este); CT-27 não inverte porque a organização dele não tem logo; D2/L8 resolvida pelo orquestrador (regra por variante, tela de bloqueio não muda) | — | pendente: `citacoes.sh`, `rastreabilidade.sh` e `git status --porcelain` na reconciliação |
| 2 | 5 | `general-purpose` (analista): revisão profunda do plano contra o código real | opus | a conversa da sessão | 18 achados RD-01..RD-18 (7 médias, 11 baixas), todos aplicados no `00` (P-05, P-06), `01`, `02` e `03` | — | pendente: `citacoes.sh` e `checkbox-sem-evidencia.sh` silenciosos |
| 3 | 6 | `general-purpose` (ponytail): auditoria de simplicidade do `01`/`02`/`03` | sonnet | a conversa da sessão | 38 achados, net -140 proposto; aceitos os de A, recusados os demais por template/scripts ou decisão D3 | — | pendente: `citacoes.sh` e `checkbox-sem-evidencia.sh` silenciosos |

| 4 | 8 | construtor do código · `general-purpose`: `logosPara()`, `CabecalhoDoPainel`, docs, CHANGELOG | — | a conversa da sessão | commit `e969341` | — | `LogoDarkModeTest` 41/41 |
| 5 | 8 | `fw-executor-ct`: testes de backend (CT-40 a CT-57) | — | o código de `app/` como oráculo | commit `7d2781b`; `CabecalhoDoPainelTenancyTest` 45/45 | — | CT-51 `/app` e `/app/new` trocados; pendência do CT-50 resolvida pela sessão |
| 6 | 10 | `fw-executor-ctb`: CT-B01 no navegador | — | o código de `app/` como oráculo | `IdentidadeVisualTest` 9/9 (54 asserções), em série | — | verde em série; seletor por classe corrigido no RC-10 |
| 7 | 9 | `fw-revisor-diff`: passe de eixos no diff | — | o `01` e o raciocínio de quem implementou | RC-01 a RC-12 | — | achados decididos pela sessão; rodou a suíte na mesma worktree durante a medição dos mutantes |
| 8 | 9 | ponytail do diff | — | — | net -190 proposto; parte aplicada, parte recusada (ver `## Revisão do Diff (step 9)`) | — | decisão da sessão |
| 9 | 9→10 | construtor da rodada pós-revisão: RC-01 a RC-07, RC-09, RC-10, RC-12 e o ponytail aceito | — | a conversa da sessão | 86/86 (447 asserções) e 9/9 (54), verdes; citações reancoradas | — | `pint`, `filacheck`, `citacoes.sh` silenciosos |

**Step 7 fechado**: a revisão adversarial do `04`/`05` foi feita (26 achados, 6 altos; aplicados 23, recusados 3) e o preenchimento da coluna `Confirmada` fica com o construtor da implementação; a Q6 está respondida por D5 do `01` e a colisão de IDs, resolvida (CT-40 a CT-57). Pendências para a implementação: o executor mede se a página de recusa de CT-50 renderiza a marca (senão M25 fica sem matador), se `/app` de CT-51 renderiza topo ou redireciona, e o seletor de tema de CT-B01.

## Blockers
<!-- Impedimentos encontrados durante implementação -->
Nenhum.

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- **CT-51 sem `/app` e `/app/new`** (2026-10-07): `/app` só redireciona (302) e `/app/new` é 404 (o kit não tem cadastro de organização), sem topo para afirmar. As duas linhas viraram `/app/password-reset/request`; M51 ficou sem matador por HTTP e está declarado na tabela de mutantes do `04`.
- **CT-50, linha "sem vínculo"**: a página de recusa não renderiza a marca do painel, então a cláusula sobre a logo da instalação saiu; M25 passou a ter matador pelo 404 com controle positivo (RC-01).
- **RC-10 resolvido, sem desvio**: o botão do topbar tem `aria-label="Menu do usuário"` (`vendor/filament/filament/resources/views/components/user-menu.blade.php`, ramo `UserMenuPosition::Topbar`; rótulo pt_BR em `layout.php:open_user_menu`), então o seletor por classe saiu. Com o menu na barra lateral o `aria-label` seria o nome do usuário.

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- **Mutantes medidos pela sessão** (2026-10-07) sobre `pest tests/Tenancy/CabecalhoDoPainelTenancyTest.php tests/Kit/LogoDarkModeTest.php` (86 testes), um por vez, com `git checkout` entre eles: controle 86/86; M26, guarda por `Paineis::correnteOuPadrao()`, 1 falha (CT-52); `marcaEscura()` devolvendo a escura com composição, 2 falhas (CT-45, CT-47); regra em bloco (a escura olha a clara), 10 falhas (CT-16, CT-40, CT-43, CT-44, CT-57); `logosPara()` ignorando `unificaLogo()`, 5 falhas (CT-16, CT-40, CT-48); composição lendo `IdentidadeDoKit::logo()` direto, 6 falhas (CT-45, CT-48, CT-49, CT-56); `organizacaoAberta()` sem `instanceof`, 1 falha (CT-52). Todos mortos. Ressalva: o revisor do diff rodou a suíte na mesma worktree durante a medição e viu falhas intermitentes por isso; valem a medição de controle (86/86) e as mortes por CT específico.
- **Depois da rodada do step 9**: `LogoDarkModeTest` + `CabecalhoDoPainelTenancyTest` 86/86 com 447 asserções (eram 444), `IdentidadeVisualTest` 9/9 com 54, `HelpersDeTesteTest`, `SiteDeDocumentacaoTest` e `CitacoesDeCodigoTest` verdes (158 testes, 712 asserções no conjunto). Os mutantes acima não foram refeitos depois dos refactors de teste (helper único, `expectParDaMarca()`).
- Edição por script: o heredoc do Bash colapsa barra invertida (`\b` virou backspace no helper movido para `tests/Pest.php`) e o `expectParDaMarca()` foi capturado pelo próprio regex de substituição e chamou a si mesmo (recursão infinita: o processo do Pest morria mudo, exit 127). Os dois corrigidos e cobertos pelos 86 testes.

## Referências Abertas
- `template-01-plano.md`, `template-02-adr.md`, `template-03-progresso.md`, `padrao-de-log.md`, `citacoes-de-codigo.md`: step 4: 2026-10-07

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
