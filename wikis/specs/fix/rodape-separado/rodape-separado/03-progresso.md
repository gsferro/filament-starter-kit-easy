# Progresso — Rodapé separado: o recado volta ao cartão do login e a assinatura fica

**Estado**: em planejamento
<!-- Uma linha só, no topo: em planejamento | em implementação | em revisão | concluída — {YYYY-MM-DD}.
     Step 4 → "em planejamento"; início da implementação → "em implementação"; step 9 → "em revisão";
     step 11, depois do veredito e antes de regenerar o INDEX.md → "concluída — {data}".
     O indice.sh da feature-tickets lê esta linha para a coluna 03 do wikis/specs/INDEX.md. -->

> `{base}`: `main` (`7221dd3`, v0.45.1) · Branch: `fix/rodape-separado` · Worktree: `D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/wt-rodape` · Wiki criada em 2026-10-07

## 1. O recado volta para `AUTH_LOGIN_FORM_AFTER`
- [ ] Segundo `registerRenderHook` de `configureTelaDeLogin()` em `AUTH_LOGIN_FORM_AFTER`, sem `scopes:`, depois dos botões sociais
- [ ] Bloco de comentário sobre ORDEM e ALCANCE do `FOOTER` substituído por um que explica a volta (pedido do solicitante, wiki `rodape-separado`) e por que o hook dispensa escopo
- [ ] Parágrafo "O rodape USA o hook `FOOTER`" do docblock reescrito
- [ ] `use App\Filament\Pages\Auth\TelaLogin;` removido; `TelaLoginUnificada` mantido (rota `/login`)
- [ ] Reconfirmado no código que `TelaLogin` e `TelaLoginUnificada` não redeclaram `content()`
- [ ] Citação de linhas 458-466 do docblock reancorada para `vendor/filament/filament/src/Auth/Pages/Login.php:content():416`
- [ ] `grep -c 'TelaLogin::class'` do provider = 0 e `scopes:` ausente dentro de `configureTelaDeLogin()`

## 2. O comentário da blade do recado
- [ ] Só o comentário de cabeçalho de `rodape-login.blade.php` muda; `<aside>` e Markdown intactos
- [ ] Nenhuma diretiva Blade no comentário novo (`views.md`)
- [ ] Linha 46 de `assinatura-do-rodape.blade.php` corrigida (só comentário — D5)
- [ ] `git diff --name-only` sem `AssinaturaDoRodape.php`, `ConfiguraFilamentGlobal.php` e `kit.css`

## 3. Testes de backend
- [ ] CT-10 com o oráculo novo: recado depois de `fi-auth-layout` e do `</form>`, antes do fechamento balanceado; assinatura depois; com controle positivo da âncora e, com provedor habilitado, recado depois do bloco `fi-login-social` (prova a ordem botões → recado; sem CT de ordem separado)
- [ ] CT-09 e CT-12: ausência do recado no HTML inteiro (texto e classe) pelo helper `semRecadoEmLugarNenhum`, cada um com controle positivo no mesmo caso
- [ ] CT-01: ausência na recuperação de senha no HTML inteiro, com controle positivo (presença em `/admin/login` no mesmo caso)
- [ ] CT-08 e CT-11 conferidos (presença por recorte, sem mudança de oráculo)
- [ ] CT-04: bloco de ausência do recado na recuperação de senha pelo helper de HTML inteiro (D6)
- [ ] `ligarLoginComGoogleDoKit()` movido para `tests/Pest.php`; `HelpersDeTesteTest` verde (D8)
- [ ] Falsificabilidade: cada CT alterado fica vermelho com o provider de `main`

## 4. Teste de browser
- [ ] CT-B01: ordem visual invertida (recado acima da assinatura) e mensagens trocadas
- [ ] CT-B01: contenção no cartão por `closest('.fi-auth-card, .fi-auth-form-container')`, seletor medido contra o layout (D7)
- [ ] CT-B03: `landmark-complementary-is-top-level` acrescentada à constante `regras`; verde com o `<aside>` no cartão, ou bifurcação (tag volta a `<div>` e P-05 revisada) registrada
- [ ] Mutante M59 (`<aside>` → `<div>`) medido com o recado no cartão; se o CT-B03 seguir verde, oráculo passa a "o recado está contido num landmark" (`closest('aside,[role],main,form')`)
- [ ] CT-B01 medido contra o estado sem o passo 1 (vermelho)

## 5. Docs, CHANGELOG e READMEs
- [ ] `docs/pt/recursos/configuracoes-do-kit.md` e espelho en
- [ ] `docs/pt/autenticacao/login-social.md` e espelho en
- [ ] `CHANGELOG.md`: `### Alterado` em `[Unreleased]`
- [ ] `README.md` e `README.en.md`: features especificadas 76 → 77

## 6. Reconciliação com as wikis anteriores
- [ ] ADR-02 do `02` registra o que da `rodape-coerente` fica superado
- [ ] `wikis/specs/feat/rodape-coerente/**` sem diff
- [ ] `wikis/specs/INDEX.md` regenerado pelo `indice.sh`

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [x] `tests/Kit/RodapeCoerenteTest.php` (CT-01, CT-09, CT-10, CT-12 substituem os da ancestral; CT-04 em parte; CT-08 e CT-11 regressão; CT-02, CT-07, CT-18 regressão como matadores de M15 a M18) — derivado no `04` (6 cenários, 5 regras, 23 mutantes, 1 sem matador), 2026-10-07
- [x] `tests/Browser/RodapeNaDobraTest.php` (CT-B01 e CT-B03 substituem os da ancestral; CT-B02 só regressão) — derivado no `05` (gate: costura `browser` na linha Dobra, ordem visual e acessibilidade do `04`), 2026-10-07

## Tickets
Sem fatiar — 6 passos, 8 CT (6 HTTP + 2 CT-B), um PR; step 8 não se aplica

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact`
- [ ] `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` (via `composer test:browser`)
- [ ] `vendor/bin/pest --parallel --tia`: nada mais no suite quebrou além da **baseline** de `main` (falhas pré-existentes por nome)
- [ ] `pest --mutate`: não se aplica (sem classe de regra); mutantes manuais sobre o provider listados
- [ ] **Custo medido**: não se aplica (sem query)
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso (`RQ`/`P-nn` × passo do `01` × CT do `04`)
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/**/RodapeCoerenteTest.php'` só acusa IDs da `rodape-coerente` nos dois arquivos compartilhados (ruído esperado, registrado no step 7)
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs alterados: quantos falham sem o fix; os demais "não falsificável nesta pilha", com motivo
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

## Revisão do Diff (step 9)

<!-- Um achado por linha, dos dois passes (/code-review e fw-revisor-diff), confirmados e rejeitados.
     A dimensão I do feature-quality-gate lê daqui o que o step 9 já cobriu. -->

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/ cujo paths: casa com um arquivo do diff (conformidade-rules.sh acusa a que falta).
     "violada" = blocker do PR. No planejamento, "atendida" quer dizer prevista no passo citado; o step 10 reconfere contra o diff. -->

| Rule | Glob que casou | Atendida / n.a. / violada | Evidência |
|---|---|---|---|
| `specs.md` — seções "Justificativa de comportamento de pacote se escreve depois de ler o vendor", "Citação de vendor se confere por símbolo" e "Citação de teste se escreve pelo ID do CT entre aspas" | `wikis/specs/**` | atendida | o hook `AUTH_LOGIN_FORM_AFTER` foi justificado lendo `Login::content()` e o layout do Auth Designer, citados por símbolo no `01`; testes citados como `'[CT-nn]'`; `citacoes.sh` reconfere no step 10 |
| `app.md` — seções "Papel se atribui dentro de ContextoDePapeis", "DTO no kit é spatie/laravel-data", "Painel corrente por Paineis::correnteOuPadrao()" e "URL pública de arquivo do disco é asset(...)" | `app/**` (`KitServiceProvider.php`) | n.a. | o diff em `app/` é a chave de um render hook e comentários; nenhum papel, DTO, painel nem URL de arquivo |
| `providers.md` — seção "Rota do kit nasce no KitServiceProvider com `web` explícito" | `app/Providers/**` | n.a. | nenhuma rota nova; o registro de hook não é rota |
| `css-filament.md` — seções "Utilitária que blade de vendor emite precisa existir no CSS do kit", "CSS de plugin que declara @layer reordena a página inteira" e "Regra fora de camada vence o :where()" | `app/Providers/**` (o `resources/css/filament/**` **não** entra no diff) | n.a. | `kit.css` e `public/css/kit/kit-correcoes.css` intocados (P-03); o provider não registra asset nem mexe em `@layer`; o CT-B01 mede se a regra da dobra ainda basta (R1) |
| `views.md` — seção "Nunca escreva diretiva do Blade dentro de comentário {{-- --}}" | `resources/views/**` (`rodape-login.blade.php` e `assinatura-do-rodape.blade.php`) | atendida | passo 2: os comentários novos não citam diretiva; confere por `grep -n '@'` nas linhas adicionadas do diff das duas blades |
| `testes.md` — seções "Helper de teste usado por mais de um arquivo vive em tests/Pest.php", "`toContain()` do Pest não recebe mensagem" e "Asserção de ausência sobre arquivo documentado precisa filtrar comentário" | `tests/**` | atendida | passo 3: o helper `semRecadoEmLugarNenhum` é usado só em `RodapeCoerenteTest.php` e fica nele; ausência por `assertStringNotContainsString` com mensagem; a ausência é medida no HTML renderizado, não em arquivo comentado |
| `testes-browser.md` — seções "`assertVisible` não prova posição — para layout, meça geometria via `script()`", "`view:cache` é o segundo pré-requisito duro" e "Nunca `--parallel` com browser" | `tests/Browser/**` | atendida | passo 4: CT-B01 já mede geometria por `script()` e a ordem visual invertida segue o mesmo método; roda via `composer test:browser` (build e `view:cache` embutidos); falsificado contra o estado sem o passo 1 |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`

## Candidatos a Rule

<!-- Step 12, depois do veredito. Quem coleta, julga e pergunta é a requirement-to-rule — um prompt de
     aprovação só, o dela. Aqui fica só o resultado. -->

## Auditoria Pré-Implementação
<!-- Saída dos steps 4 a 6, ANTES de escrever código. Não confundir com "Desvios do Plano",
     que é pós-implementação. -->

Entendimento confirmado: —

- step 5 — revisão profunda (analista/opus): 15 achados RD-01..RD-15 (1 bloqueante, 2 altas, 6 médias, 6 baixas), todos aplicados nesta rodada
- step 6 — ponytail (sonnet): 40 achados, net -190 proposto; aceitos 9 (lista curta), recusados os demais por exigência do template/scripts (`rastreabilidade.sh`, `indice.sh`) ou decisão D5

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. Host sem sub-agente: uma linha "Sem despacho — host sem sub-agente".
     Tarefa que rodou em linha por exceção: "Sem despacho — {motivo}".
     Auditoria REPROVADA também é linha (com o redespacho ao lado); fallback para general-purpose
     por agente fw-* indisponível vai na coluna Modelo; fallback de Explore ou SendMessage, na coluna
     Agente / tarefa. Custo: o que o host reporta no retorno (tokens, duração); "—" se não reporta. -->

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 4 | construtor · `general-purpose` — rascunho do `01`, `02` e `03` | sonnet | — | `01` (6 passos), `02` (2 ADRs) e `03` gravados; `citacoes.sh` rodado pelo construtor | {tokens · duração, quando o host reportar} | lido pelo orquestrador, que o devolveu na revisão do step 5 |
| 2 | 5 | analista — revisão profunda do `01`–`03` | opus | — | 15 achados RD-01..RD-15 (1 bloqueante, 2 altas, 6 médias, 6 baixas), todos aplicados | — | aplicados pelo construtor na rodada de correção; `citacoes.sh` e `checkbox-sem-evidencia.sh` silenciosos |
| 3 | 6 | ponytail — corte de excesso do `01`–`03` | sonnet | — | 40 achados, net -190 proposto; 9 aceitos, o resto recusado (template, scripts, D5) | — | aceitos aplicados; recusados com motivo na Auditoria Pré-Implementação |
| 4 | 7 | derivação dos CT · `general-purpose` com `feature-test-design` — `04` e `05` | (modelo da sessão) | a implementação (não existe) | `04` (6 cenários, 5 regras, 23 mutantes, 1 sem matador) e `05` (CT-B01, CT-B03); 3 perguntas de desenho Q?1 a Q?3 devolvidas para a sessão confirmar e renumerar; `rastreabilidade.sh` e `citacoes.sh` silenciosos, `ids-ct.sh` só com ruído de IDs da ancestral | — | revisão adversarial **não exigida** (perfil padrão, nenhuma área com Impacto 3: tela pública, texto de rodapé, reversível); a sessão decide se a despacha |

## Blockers
<!-- Impedimentos encontrados durante implementação -->

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->

## Referências Abertas
<!-- Uma linha por arquivo de references/ das skills aberto nesta feature, com o step. O checklist
     final confere contra o mínimo da tabela do Índice do SKILL.md; o que foi pulado diz por quê. -->
- `template-01-plano.md`, `template-02-adr.md`, `template-03-progresso.md`, `padrao-de-log.md`, `citacoes-de-codigo.md` — step 4 — 2026-10-07

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: —
- **Faltou no plano**: —
