# Progresso — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

**Estado**: em planejamento
<!-- Uma linha só, no topo: em planejamento | em implementação | em revisão | concluída — {YYYY-MM-DD}.
     Pedido do despacho do step 4: "em andamento" até o início da implementação. O indice.sh lê esta linha. -->

> Branch: `fix/logo-dark-do-tenant` · Base do PR: `{base}` = `main` (`b347fcc`, v0.45.1) · Worktree: `D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/wt-logo`

## 1. `IdentidadeDoKit::logosPara()` e a delegação da tela de bloqueio
- [ ] `IdentidadeDoKit::logosPara(?Tenant): array{clara, escura}` com a regra por variante de `## Mapeamentos` do `01`
- [ ] `TelaBloqueio::urlsDasLogos()` delega ao método, mantendo o `once()`; docblock aponta para o método
- [ ] CT-16 de `LogoDarkModeTest` verde sem alteração; `logosPara(null)` igual ao par da instalação

## 2. `CabecalhoDoPainel`: marca simples e composição com a organização aberta
- [ ] `organizacaoAberta()` (`getCurrentPanel()?->hasTenancy()` e `Tenant`, nos dois pontos) e memo único por `Request` com segmentos e par de logos (D4)
- [ ] `marca()`, `marcaEscura()` e `resolverSegmentos()` usam o par; docblocks (classe, `resolverSegmentos()`, `marca()`/`marcaEscura()`) com a ressalva da organização aberta
- [ ] `/app/{organização}` com logo própria mostra o par da organização nas duas formas; `/admin`, `/infra` e `/app` sem organização idênticos aos de hoje

## 3. Factory e textos que a mudança desmente
- [ ] `TenantFactory::comIdentidadeVisual()` com `?string $logoEscura = null` no fim (D3)
- [ ] `helperText` do upload `logo` do `TenantForm` e docblock de `Tenant::urlDaLogoEscura()`
- [ ] `vendor/bin/filacheck --fix` e `vendor/bin/pint --dirty` silenciosos

## 4. Testes de backend
- [ ] `tests/Kit/LogoDarkModeTest.php`: CT novo da regra `logosPara()` (tabela de decisão, `null`, só a escura, só a escura sem clara na instalação); CT-16 intocado
- [ ] `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`: marca simples, composição, quedas, unificada, `/admin` e `/infra` com organização obsoleta, duas organizações, update Livewire da `Topbar`, um só `fi-logo-dark`, só a escura; todo caso de ausência com controle positivo
- [ ] CT-07 substituído por caso com ID desta wiki (aprovação: Adendo 1); CT-27 sem alteração; `gravarLogoDaInstalacaoDaTenancia(bool $unifica = true)`; docblock cita as duas wikis
- [ ] casos novos vermelhos com o passo 2 revertido

## 5. Teste de navegador do swap no topo do `/app`
- [ ] `tests/BrowserTenancy/IdentidadeVisualTest.php`: marca simples (B1) e composição (B2), uma visita por tema; `afterEach` apaga os arquivos novos
- [ ] verde em série; vermelho com o passo 2 revertido

## 6. Documentação (pt e en), CHANGELOG e README
- [ ] `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/recursos/configuracoes-do-kit.md`: a frase da composição e a da logo por tema
- [ ] `CHANGELOG.md` `[Unreleased]` → `### Corrigido`
- [ ] `README.md` e `README.en.md`: features especificadas 76 para 77 e o badge de casos de teste recontado; `SiteDeDocumentacaoTest` verde

## 7. Índice das wikis
- [ ] `wikis/specs/INDEX.md` regenerado por `indice.sh` (não à mão)

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [x] Casos de teste derivados no `04` e no `05` pela `feature-test-design` (step 7) — 18 CT (CT-40 a CT-57) e 1 CT-B (CT-B01), 11 regras, 47 mutantes no `04` e 5 no `05` (52), 0 sem matador; perfil padrão com Impacto 3 na área da organização do topo; revisão adversarial: 26 achados, 6 altos; aplicados 23, recusados 3 com motivo (ADV-18: objeto não-organização com atributo `logo`, já coberto pela assinatura tipada e por CT-52; ADV-25: `alt` fora de escopo por P-06; ADV-26: desvínculo com aba aberta, fora de escopo e sem RQ); P-05 do `00` reescrita (ADV-24); `rastreabilidade.sh`, `citacoes.sh` e `checkbox-sem-evidencia.sh` conferidos, 2026-10-07
- [ ] `tests/Kit/LogoDarkModeTest.php`: CT-40, CT-41 (regra de resolução do par), CT-54 (tela de bloqueio, organização da sessão), CT-55 (documentação pt/en); numeração a partir de CT-40 decidida pela sessão em 2026-10-07 (ver `### Colisão de IDs` do `04`; `ids-ct.sh` acusa os CT-01 a CT-20 da ancestral `logo-dark-mode` que vivem no mesmo arquivo)
- [ ] `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`: CT-42 a CT-53, CT-56 e CT-57; CT-49 substitui o CT-07 da wiki ancestral `cabecalho-do-painel` (aprovação: Adendo 1 do `00`); CT-27 e CT-04 da ancestral ficam como regressão
- [ ] `tests/BrowserTenancy/IdentidadeVisualTest.php`: CT-B01 (um `Esquema do Cenário`: marca simples e composição, uma visita por tema)

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

**Step 7 fechado**: a revisão adversarial do `04`/`05` foi feita (26 achados, 6 altos; aplicados 23, recusados 3) e o preenchimento da coluna `Confirmada` fica com o construtor da implementação; a Q6 está respondida por D5 do `01` e a colisão de IDs, resolvida (CT-40 a CT-57). Pendências para a implementação: o executor mede se a página de recusa de CT-50 renderiza a marca (senão M25 fica sem matador), se `/app` de CT-51 renderiza topo ou redireciona, e o seletor de tema de CT-B01.

## Blockers
<!-- Impedimentos encontrados durante implementação -->
Nenhum.

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->

## Referências Abertas
- `template-01-plano.md`, `template-02-adr.md`, `template-03-progresso.md`, `padrao-de-log.md`, `citacoes-de-codigo.md`: step 4: 2026-10-07

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
