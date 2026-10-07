# Progresso — Rodapé separado: o recado volta ao cartão do login e a assinatura fica

**Estado**: concluída — 2026-10-07
<!-- Uma linha só, no topo: em planejamento | em implementação | em revisão | concluída — {YYYY-MM-DD}.
     Step 4 → "em planejamento"; início da implementação → "em implementação"; step 9 → "em revisão";
     step 11, depois do veredito e antes de regenerar o INDEX.md → "concluída — {data}".
     O indice.sh da feature-tickets lê esta linha para a coluna 03 do wikis/specs/INDEX.md. -->

> `{base}`: `main` (`b347fcc`, v0.45.1) · Branch: `fix/rodape-separado` · Worktree: `D:/PROJECTS/PACOTES/FILAMENTS/STARTER-KIT-EASY/wt-rodape` · Wiki criada em 2026-10-07

## 1. O recado volta para `AUTH_LOGIN_FORM_AFTER`
- [x] Segundo `registerRenderHook` de `configureTelaDeLogin()` em `AUTH_LOGIN_FORM_AFTER`, sem `scopes:`, depois dos botões sociais — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] Bloco de comentário sobre ORDEM e ALCANCE do `FOOTER` substituído por um que explica a volta (pedido do solicitante, wiki `rodape-separado`) e por que o hook dispensa escopo — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] Parágrafo "O rodape USA o hook `FOOTER`" do docblock reescrito — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] `use App\Filament\Pages\Auth\TelaLogin;` removido; `TelaLoginUnificada` mantido (rota `/login`) — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] Reconfirmado no código que `TelaLogin` e `TelaLoginUnificada` não redeclaram `content()` — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] Citação de linhas 458-466 do docblock reancorada para `vendor/filament/filament/src/Auth/Pages/Login.php:content():416` — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07
- [x] `grep -c 'TelaLogin::class'` do provider = 0 e `scopes:` ausente dentro de `configureTelaDeLogin()` — commit `23d1ed9`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `LoginSocialGoogleTest` 62/62, 2026-10-07

## 2. O comentário da blade do recado
- [x] Só o comentário de cabeçalho de `rodape-login.blade.php` muda; `<aside>` e Markdown intactos — commit `23d1ed9`; blades só com comentário, 2026-10-07
- [x] Nenhuma diretiva Blade no comentário novo (`views.md`) — commit `23d1ed9`; blades só com comentário, 2026-10-07
- [x] Linha 46 de `assinatura-do-rodape.blade.php` corrigida (só comentário — D5) — commit `23d1ed9`; blades só com comentário, 2026-10-07
- [x] `git diff --name-only` sem `AssinaturaDoRodape.php` e `ConfiguraFilamentGlobal.php`; `kit.css` entrou no diff só por comentário (RD-01), contra o critério do passo 2 — `git diff --name-only b347fcc HEAD` e `git diff -U0` em 2026-10-07; desvio registrado em Desvios do Plano *(alterado em 2026-10-07: QA-04)*

## 3. Testes de backend
- [x] CT-10 com o oráculo novo: recado depois de `fi-auth-layout` e do `</form>`, antes do fechamento balanceado; assinatura depois; com controle positivo da âncora e, com provedor habilitado, recado depois do bloco `fi-login-social` (prova a ordem botões → recado; sem CT de ordem separado) — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1; falsificado contra o provider de `main` (M1): CT-10 4 falharam de 4 linhas, 2026-10-07
- [x] CT-09 e CT-12: ausência do recado no HTML inteiro (texto e classe) pelo helper `semRecadoEmLugarNenhum`, cada um com controle positivo no mesmo caso — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1; não falsificáveis contra `main` (`main` não vaza o recado); CT-12 falha em M7 e M8, CT-09 em M8 (ver Falsificabilidade), 2026-10-07
- [x] CT-01: ausência na recuperação de senha no HTML inteiro, com controle positivo (presença em `/admin/login` no mesmo caso) — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1; não falsificável contra `main` (`main` não vaza o recado); CT-01 falha em M7 e M8 (ver Falsificabilidade), 2026-10-07
- [x] CT-08 e CT-11 conferidos (presença por recorte, sem mudança de oráculo) — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1; CT-08 e CT-11 falham em M8 (ver Falsificabilidade), 2026-10-07
- [x] CT-04: bloco de ausência do recado na recuperação de senha pelo helper de HTML inteiro (D6) — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1; não falsificável contra `main`; CT-04 falha em M7 (ver Falsificabilidade), 2026-10-07
- [x] `ligarLoginComGoogleDoKit()` movido para `tests/Pest.php`; `HelpersDeTesteTest` verde (D8) — commit `b940d2c`; `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1, 2026-10-07
- [x] Falsificabilidade, medida em 2026-10-07 com `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact` (73 testes), um mutante por vez sobre o provider commitado, `git checkout` depois: **M7** (recado no `FOOTER` sem escopo) 63 passaram, 10 falharam (CT-01, CT-04, CT-10, CT-12); **M8** (recado em `AUTH_REGISTER_FORM_AFTER`) 59 passaram, 14 falharam (CT-01, CT-08, CT-09, CT-10, CT-11, CT-12, CT-20); **M3** (recado em `AUTH_LOGIN_FORM_BEFORE`) 69 passaram, 4 falharam (CT-10 ×4); **M2** (recado registrado antes dos botões sociais) 71 passaram, 2 falharam (CT-10, as 2 linhas com Google); **M1** (provider de `main`, recado no `FOOTER` escopado) filtro `CT-10|CT-12|CT-09|CT-01|CT-04` = 25 testes, 4 falharam (todos CT-10); CT-01, CT-04, CT-09 e CT-12 não são falsificáveis contra `main`, porque `main` não vaza o recado, e matam M7 a M11 por construção (M7 e M8 medidos acima) — commit `b940d2c`

## 4. Teste de browser
- [x] CT-B01: ordem visual invertida (recado acima da assinatura) e mensagens trocadas — commit `b940d2c`; `RodapeNaDobraTest` 9/9 e 48 asserções; `<aside>` trocado por `<div>` contra CT-B03 = 3 testes, 2 falharam (M21 morto: CT-B03 2 de 3 falharam com a `<div>` e verde com o `<aside>`, bifurcação (c) do `05` (D1 e P-05 se confirmam)); seletor medido `.fi-auth-form-container`, `.fi-auth-card` não emitido; nenhuma das 4 regras acusa o `<aside>` nas 3 rotas do CT-B03 (2 com recado), 2026-10-07
- [x] CT-B01: contenção no cartão por `closest('.fi-auth-card, .fi-auth-form-container')`, seletor medido contra o layout (D7) — commit `b940d2c`; `RodapeNaDobraTest` 9/9 e 48 asserções; `<aside>` trocado por `<div>` contra CT-B03 = 3 testes, 2 falharam (M21 morto: CT-B03 2 de 3 falharam com a `<div>` e verde com o `<aside>`, bifurcação (c) do `05` (D1 e P-05 se confirmam)); seletor medido `.fi-auth-form-container`, `.fi-auth-card` não emitido; nenhuma das 4 regras acusa o `<aside>` nas 3 rotas do CT-B03 (2 com recado), 2026-10-07
- [x] CT-B03: `landmark-complementary-is-top-level` acrescentada à constante `regras`; verde com o `<aside>` no cartão, ou bifurcação (tag volta a `<div>` e P-05 revisada) registrada — commit `b940d2c`; `RodapeNaDobraTest` 9/9 e 48 asserções; `<aside>` trocado por `<div>` contra CT-B03 = 3 testes, 2 falharam (M21 morto: CT-B03 2 de 3 falharam com a `<div>` e verde com o `<aside>`, bifurcação (c) do `05` (D1 e P-05 se confirmam)); seletor medido `.fi-auth-form-container`, `.fi-auth-card` não emitido; nenhuma das 4 regras acusa o `<aside>` nas 3 rotas do CT-B03 (2 com recado), 2026-10-07
- [x] Mutante M21 (`<aside>` → `<div>`) medido com o recado no cartão; se o CT-B03 seguir verde, oráculo passa a "o recado está contido num landmark" (`closest('aside,[role],main,form')`) — commit `b940d2c`; `RodapeNaDobraTest` 9/9 e 48 asserções; `<aside>` trocado por `<div>` contra CT-B03 = 3 testes, 2 falharam (M21 morto: CT-B03 2 de 3 falharam com a `<div>` e verde com o `<aside>`, bifurcação (c) do `05` (D1 e P-05 se confirmam)); seletor medido `.fi-auth-form-container`, `.fi-auth-card` não emitido; nenhuma das 4 regras acusa o `<aside>` nas 3 rotas do CT-B03 (2 com recado), 2026-10-07
- [x] CT-B01 medido contra o estado sem o passo 1 (vermelho) — medido pela sessão: provider de `main` na árvore, `pest tests/Browser/RodapeNaDobraTest.php --filter CT-B01` → RE-MEDIDO no ciclo 2: 5 testes, 1 passou, 4 falharam — `/admin/login`, `/app/login`, `/infra/login` e `/login`, todas com "o recado em {rota} não está dentro do cartão do formulário"; a linha `/admin/password-reset/request` ficou verde, como o `05` previa (`git show main:app/Providers/KitServiceProvider.php`, `view:clear`, `pest tests/Browser/RodapeNaDobraTest.php --compact --filter CT-B01`), árvore restaurada e `git status` limpo, 2026-10-07

## 5. Docs, CHANGELOG e READMEs
- [x] `docs/pt/recursos/configuracoes-do-kit.md` e espelho en — commits `23d1ed9` e `e3f9ab2` (docs reancoradas), 2026-10-07
- [x] `docs/pt/autenticacao/login-social.md` e espelho en — commits `23d1ed9` e `e3f9ab2` (docs reancoradas), 2026-10-07
- [x] `CHANGELOG.md`: `### Alterado` em `[Unreleased]` — commits `23d1ed9` e `e3f9ab2` (docs reancoradas), 2026-10-07
- [x] `README.md` e `README.en.md`: features especificadas 76 → 77 — commits `23d1ed9` e `e3f9ab2` (docs reancoradas), 2026-10-07

## 6. Reconciliação com as wikis anteriores
- [x] ADR-02 do `02` registra o que da `rodape-coerente` fica superado — ADR-02 escrita (`02`), 2026-10-07
- [x] `wikis/specs/feat/rodape-coerente/**` sem diff — `git diff --stat main...HEAD -- wikis/specs/feat/rodape-coerente` vazio, 2026-10-07
- [x] `wikis/specs/INDEX.md` regenerado pelo `indice.sh` — regenerado por `indice.sh`, linha `fix/rodape-separado` presente, 2026-10-07

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05: um arquivo de teste por linha, com os IDs que ele cobre. -->
- [x] `tests/Kit/RodapeCoerenteTest.php` (CT-01, CT-09, CT-10, CT-12 substituem os da ancestral; CT-04 em parte; CT-08 e CT-11 regressão; CT-02, CT-07, CT-18 regressão como matadores de M15 a M18) — derivado no `04` (6 cenários, 5 regras, 23 mutantes, 1 sem matador), 2026-10-07
- [x] `tests/Browser/RodapeNaDobraTest.php` (CT-B01 e CT-B03 substituem os da ancestral; CT-B02 só regressão) — derivado no `05` (gate: costura `browser` na linha Dobra, ordem visual e acessibilidade do `04`), 2026-10-07

## Tickets
Sem fatiar — 6 passos, 8 CT (6 HTTP + 2 CT-B), um PR; step 8 não se aplica

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff (validar contra over-engineering) — ponytail do diff no step 9 (net -43 proposto, cortes de redundância aplicados; tabela em Revisão do Diff), 2026-10-07
- [x] `vendor/bin/pint --dirty --format agent` — `vendor/bin/pint --test` nos 8 PHP do diff: pint passed; `php -l` sem erro; `vendor/bin/filacheck`: All 17 rules passed, 2026-10-07
- [x] `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact` — `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/RodapeCoerenteTest.php tests/Kit/HelpersDeTesteTest.php tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php --compact`: 81/81, 352 asserções, 2026-10-07
- [x] `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` (via `composer test:browser`) — 9/9, 48 asserções (`pest tests/Browser/RodapeNaDobraTest.php --compact`, ciclo 2 do QA reproduziu), 2026-10-07
- [ ] `vendor/bin/pest --parallel --tia`: nada mais no suite quebrou além da **baseline** de `main` (falhas pré-existentes por nome)
- [x] `pest --mutate`: não se aplica (sem classe de regra); mutantes manuais sobre o provider listados — mutantes manuais M1, M2, M3, M7 e M8 no passo 3 deste arquivo, com comando e números, 2026-10-07
- [x] **Custo medido**: não se aplica (sem query) — sem query nova no diff, 2026-10-07
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo — RD-01 a RD-08 na tabela de Revisão do Diff, todos aplicados ou declarados em Desvios do Plano, 2026-10-07
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido — seção preenchida no `05` (2 linhas, ambas confere = sim), 2026-10-07
- [x] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*` — QA-01 a QA-04 e QA-06 propagados ao `01`, `02`, `04` e `05` com a marca `*(alterado em 2026-10-07)*`, 2026-10-07
- [x] `rastreabilidade.sh {wiki}` silencioso (`RQ`/`P-nn` × passo do `01` × CT do `04`) — `rastreabilidade.sh` na wiki: exit 0, sem saída, 2026-10-07
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — `checkbox-sem-evidencia.sh` exit 0, sem saída, 2026-10-07
- [x] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso — `citacoes.sh` exit 0 (2026-10-07, depois da reciclagem do ciclo 1)
- [x] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/**/RodapeCoerenteTest.php'` só acusa IDs da `rodape-coerente` nos dois arquivos compartilhados (ruído esperado, registrado no step 7) — `ids-ct.sh` só acusa 15 linhas "no teste, sem cenário definido" de IDs da `rodape-coerente`, o ruído esperado, 2026-10-07
- [x] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso — `conformidade-rules.sh` na wiki com a base `main`: exit 0, sem saída, 2026-10-07
- [x] Falsificabilidade dos CTs alterados: quantos falham sem o fix; os demais "não falsificável nesta pilha", com motivo — passo 3 deste arquivo: M1 (CT-10 4/4 contra `main`), M2, M3, M7 e M8 medidos; CT-01, CT-04, CT-09 e CT-12 não falsificáveis contra `main`, com motivo, 2026-10-07
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

## Revisão do Diff (step 9)

<!-- Um achado por linha, dos dois passes (/code-review e fw-revisor-diff), confirmados e rejeitados.
     A dimensão I do feature-quality-gate lê daqui o que o step 9 já cobriu. -->

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 | fw-revisor-diff | achado menor 1: comentário do bloco da dobra em `kit.css` descrevia o estado antigo | comentário | — | aplicado (rodada pós-revisão, 2026-10-07) |
| RD-02 | fw-revisor-diff | achado menor 2: docblock do CT-01 sem o nome novo do caso | teste | CT-01 | aplicado |
| RD-03 | fw-revisor-diff | achado menor 3: CT-10 sem o limite superior pelo cartão | teste | CT-10 | aplicado (fechamento balanceado da `<div>` `.fi-auth-form-container`, pois `.fi-auth-card` não é emitido) |
| RD-04 | fw-revisor-diff | achado menor 4: comentário do CT-B03 dizia três regras | teste | CT-B03 | aplicado |
| RD-05 | fw-revisor-diff | achado menor 5: mensagem do CT-B01 citava "escopo do hook" | teste | CT-B01 | aplicado |
| RD-06 | fw-revisor-diff | achado menor 6: docblock de `ligarLoginComGoogleDoKit()` longo | teste | — | aplicado |
| RD-07 | fw-revisor-diff | achado menor 7: citação `Login.php:content():416` duplicada no provider; descrição da seção em `ConfiguracoesDoKit` desatualizada | código | — | aplicado |
| RD-08 | fw-revisor-diff | achado menor 8: CT-B01 usa `closest` com classes do vendor, contra `testes-browser.md` | teste | CT-B01 | declarado em Desvios do Plano |

Ponytail do diff (sonnet): net -43 proposto. Aplicados os cortes de redundância: `$inicioDosBotoes`, o bloco `fi-login-rodape`/`kit-versao` duplicado do CT-10, `recadoConteiner` do CT-B01 e a segunda chamada de `closest()`, docblocks de `semRecadoEmLugarNenhum()` e `ligarLoginComGoogleDoKit()` encurtados. Recusados os que a especificação exige: controles positivos de CT-01, CT-09 e CT-12, limite inferior pelo layout e a nota do Breezy no provider.

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/ cujo paths: casa com um arquivo do diff (conformidade-rules.sh acusa a que falta).
     "violada" = blocker do PR. No planejamento, "atendida" quer dizer prevista no passo citado; o step 10 reconfere contra o diff. -->

| Rule | Glob que casou | Atendida / n.a. / violada | Evidência |
|---|---|---|---|
| `specs.md` — seções "Justificativa de comportamento de pacote se escreve depois de ler o vendor", "Citação de vendor se confere por símbolo" e "Citação de teste se escreve pelo ID do CT entre aspas" | `wikis/specs/**` | atendida | o hook `AUTH_LOGIN_FORM_AFTER` foi justificado lendo `Login::content()` e o layout do Auth Designer, citados por símbolo no `01`; testes citados como `'[CT-nn]'`; `citacoes.sh` reconfere no step 10 |
| `app.md` — seções "Papel se atribui dentro de ContextoDePapeis", "DTO no kit é spatie/laravel-data", "Painel corrente por Paineis::correnteOuPadrao()" e "URL pública de arquivo do disco é asset(...)" | `app/**` (`KitServiceProvider.php`) | n.a. | o diff em `app/` é a chave de um render hook e comentários; nenhum papel, DTO, painel nem URL de arquivo |
| `providers.md` — seção "Rota do kit nasce no KitServiceProvider com `web` explícito" | `app/Providers/**` | n.a. | nenhuma rota nova; o registro de hook não é rota |
| `css-filament.md` — seções "Utilitária que blade de vendor emite precisa existir no CSS do kit", "CSS de plugin que declara @layer reordena a página inteira" e "Regra fora de camada vence o :where()" | `resources/css/filament/**` (`kit.css`) e `app/Providers/**` | aplicada | `kit.css` entrou no diff só por comentário; `php artisan filament:assets` rodado, `public/css/kit/kit-correcoes.css` regenerado; o provider não registra asset nem mexe em `@layer`; o CT-B01 mede se a regra da dobra ainda basta (R1) |
| `views.md` — seção "Nunca escreva diretiva do Blade dentro de comentário {{-- --}}" | `resources/views/**` (`rodape-login.blade.php` e `assinatura-do-rodape.blade.php`) | atendida | passo 2: os comentários novos não citam diretiva; confere por `grep -n '@'` nas linhas adicionadas do diff das duas blades |
| `testes.md` — seções "Helper de teste usado por mais de um arquivo vive em tests/Pest.php", "`toContain()` do Pest não recebe mensagem" e "Asserção de ausência sobre arquivo documentado precisa filtrar comentário" | `tests/**` | atendida | passo 3: o helper `semRecadoEmLugarNenhum` é usado só em `RodapeCoerenteTest.php` e fica nele; ausência por `assertStringNotContainsString` com mensagem; a ausência é medida no HTML renderizado, não em arquivo comentado |
| `testes-browser.md` — seções "`assertVisible` não prova posição — para layout, meça geometria via `script()`", "`view:cache` é o segundo pré-requisito duro" e "Nunca `--parallel` com browser" | `tests/Browser/**` | atendida | passo 4: CT-B01 já mede geometria por `script()` e a ordem visual invertida segue o mesmo método; roda via `composer test:browser` (build e `view:cache` embutidos); falsificado contra o estado sem o passo 1 |
| `filament.md` | `app/Filament/**` (`ConfiguracoesDoKit.php`) | n.a. | só o texto de `->description()` em `ConfiguracoesDoKit.php`; filacheck All 17 rules passed |
| `pages.md` | `app/Filament/Admin/Pages/**` (`ConfiguracoesDoKit.php`) | n.a. | só o texto de `->description()` em `ConfiguracoesDoKit.php`; filacheck All 17 rules passed |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-07 · 0 Blocker, 4 Major, 3 Minor, 2 Cosmético · 7/12 dimensões
- **Relatório**: `06-relatorio-qa.md`

**Reciclagem do ciclo 1** (destino 1 = wiki; o ciclo 2 reconfere)

- QA-01 (Major, destino 1): a falsificabilidade reescrita com os mutantes M1, M2, M3, M7 e M8 medidos (comando e número) no passo 3 deste arquivo, na §Divergências do `04` e no critério do passo 3 do `01`.
- QA-02 (Major, destino 1): "73/73, 326 asserções (HEAD) — 322 em `b940d2c`" em todas as ocorrências; a linha "81/81 (352)" ganhou o comando, rodado em 2026-10-07 (81/81, 352).
- QA-03 (Major, destino 1): `ConfiguracoesDoKit.php` entrou no passo 5 do `01`; corrigidos "não muda (P-04)" no `01` e "não é tocada" no `02`.
- QA-04 (Major, destino 1 e 2): desvio de `kit.css` registrado em Desvios do Plano e no passo 2 do `01`; linha `css-filament.md` da Conformidade corrigida; `php artisan filament:assets` rodado e a cópia publicada regenerada.
- QA-05 (Minor, destino 1): linhas `filament.md` e `pages.md` na Conformidade; `conformidade-rules.sh` silencioso.
- QA-06 (Minor, destino 1): rótulo (c) e ID M21; Roteiro e Pré-requisitos do `05` preenchidos; seletor efetivo no `05`; Verificação Final marcada onde há evidência.
- QA-07 (Minor, destino 1): termos "Recado do login" e "Assinatura do rodapé" no `wikis/glossario.md`.
- QA-08 (Cosmético, destino 1): base `b347fcc` (v0.45.1) e teto de pulados 920 no `01` e no `03`.
- QA-09 (Cosmético, destino 1): célula de Custo da linha 1 de Despachos = `—`.

- **Ciclo**: 2 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-07 · 0 Blocker, 2 Major, 2 Minor, 0 Cosmético · 7/12 dimensões · produto correto, achados só de texto
- **Relatório**: `06-relatorio-qa.md`

**Reciclagem do ciclo 2** (destino 1 = wiki; aplicados nesta rodada)

- QA-06d (Minor, destino 1): Verificação Final e passo 6 marcados com a evidência (`RodapeNaDobraTest` 9/9, 48; `citacoes.sh` exit 0; ADR-02; diff da `rodape-coerente` vazio). O item do `INDEX.md` fica aberto, depois do veredito.
- QA-10 (Major, destino 1): as cinco linhas do `01` e do `04` reescritas com o fato (`kit.css` e a cópia publicada só por comentário, `assinatura-do-rodape.blade.php` só por comentário, `tests/Pest.php` pelo helper de D8) e a marca de data.
- QA-11 (Major, destino 1): CT-B01 re-medido contra o provider de `main` (4 de 5 falharam; a recuperação de senha ficou verde); "5 rotas" do CT-B03 trocado por "3 rotas (2 com recado)" no `03` e no `05`.
- QA-12 (Minor, destino 1): títulos de Falsificabilidade do `05` como "prevista"; M19, M22 e M23 "não medido nesta entrega"; M20 não plantável com `.fi-auth-form-container`.

**Teto de ciclos do perfil padrão (2) atingido.** Os achados abertos foram reciclados nesta rodada e não há achado de código; conforme a regra de convergência, o aceite final é do solicitante e está pedido na descrição do PR.

## Candidatos a Rule

<!-- Step 12, depois do veredito. Quem coleta, julga e pergunta é a requirement-to-rule — um prompt de
     aprovação só, o dela. Aqui fica só o resultado. -->

apresentados 0 · gravados 0 · recusados 0 · descartados no gate 2 · poda 0 — candidatos avaliados pela sessão (requirement-to-rule): (1) «o `FOOTER` é da assinatura; o recado do login é do cartão» — descartado no gate 3 (inferível: o docblock de `configureTelaDeLogin()` e a ADR-02 o dizem; um agente que leia o provider não erra); (2) «recado do login sem escopo no `AUTH_LOGIN_FORM_AFTER`» — descartado no gate 1 (decisão de uma feature, fica na ADR)

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
| 1 | 4 | construtor · `general-purpose` — rascunho do `01`, `02` e `03` | sonnet | — | `01` (6 passos), `02` (2 ADRs) e `03` gravados; `citacoes.sh` rodado pelo construtor | — | lido pelo orquestrador, que o devolveu na revisão do step 5 |
| 2 | 5 | analista — revisão profunda do `01`–`03` | opus | — | 15 achados RD-01..RD-15 (1 bloqueante, 2 altas, 6 médias, 6 baixas), todos aplicados | — | aplicados pelo construtor na rodada de correção; `citacoes.sh` e `checkbox-sem-evidencia.sh` silenciosos |
| 3 | 6 | ponytail — corte de excesso do `01`–`03` | sonnet | — | 40 achados, net -190 proposto; 9 aceitos, o resto recusado (template, scripts, D5) | — | aceitos aplicados; recusados com motivo na Auditoria Pré-Implementação |
| 4 | 7 | derivação dos CT · `general-purpose` com `feature-test-design` — `04` e `05` | (modelo da sessão) | a implementação (não existe) | `04` (6 cenários, 5 regras, 23 mutantes, 1 sem matador) e `05` (CT-B01, CT-B03); 3 perguntas de desenho Q?1 a Q?3 devolvidas para a sessão confirmar e renumerar; `rastreabilidade.sh` e `citacoes.sh` silenciosos, `ids-ct.sh` só com ruído de IDs da ancestral | — | revisão adversarial **não exigida** (perfil padrão, nenhuma área com Impacto 3: tela pública, texto de rodapé, reversível); a sessão decide se a despacha |
| 5 | 8 | construtor do código · sonnet | sonnet | — | passos 1 a 5 em `23d1ed9` e `e3f9ab2` (código, blades, docs, CHANGELOG, READMEs) | — | revisado no step 9 |
| 6 | 9 | `fw-executor-ct` | sonnet | — | `RodapeCoerenteTest` 73/73, 326 asserções (HEAD) — 322 em `b940d2c`, `HelpersDeTesteTest` 1/1 em `b940d2c`; mutantes do provider de `main` medidos | — | números reproduzidos pela rodada pós-revisão |
| 7 | 9 | `fw-executor-ctb` | sonnet | — | `RodapeNaDobraTest` 9/9 e 48 asserções; M21 morto (CT-B03) | — | reproduzido pela rodada pós-revisão (9/9, 48) |
| 8 | 9 | `fw-revisor-diff` | (agente) | PRD e raciocínio de quem implementou | 8 achados menores RD-01..RD-08 | — | todos aplicados, RD-08 declarado em Desvios do Plano |
| 9 | 9 | ponytail do diff | sonnet | — | net -43 proposto; cortes de redundância aplicados, os exigidos pela especificação recusados | — | aplicados na rodada seguinte |
| 10 | 9→10 | rodada pós-revisão (construtor) | sonnet | — | RD-01..RD-08 aplicados; pest 81/81 (352 asserções; `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/RodapeCoerenteTest.php tests/Kit/HelpersDeTesteTest.php tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php --compact`, 2026-10-07) e browser 9/9 (48); wiki 03 parcial | — | conferido por `git diff --stat` e CRLF vazio |

## Blockers
<!-- Impedimentos encontrados durante implementação -->
- [x] Aceite do solicitante para o veredito pós-reciclagem do ciclo 2 (teto do perfil padrão); nenhum achado de código em aberto — aceite dado pelo solicitante no chat em 2026-10-07 ("pode prosseguir", depois do relato dos vereditos e das premissas P-01/P-02/P-05); merge por squash em seguida

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- RD-08 — o CT-B01 usa `closest('.fi-auth-card, .fi-auth-form-container')`, classes do vendor, contra `testes-browser.md` §Seletores: não há `aria-label` para medir contenção. Falha fechado se o vendor renomear (a asserção `recadoNoCartao` passa a falso).
- `kit.css`: só comentário (RD-01), contra o critério do passo 2 do `01`, que mandava não tocar o arquivo. O comentário do bloco da dobra descrevia o estado antigo (o recado fora do cartão); a regra de CSS não mudou. `php artisan filament:assets` rodado em 2026-10-07 e `public/css/kit/kit-correcoes.css` regenerado (`diff resources/css/filament/kit.css public/css/kit/kit-correcoes.css` vazio; no `git diff --stat`, só as linhas de comentário) *(alterado em 2026-10-07: QA-04)*.
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`: só o texto de `->description()` da seção "Rodapé da tela de login" (RD-07, achado QA-03), que o `01` não listava; entrou no passo 5 do `01` *(alterado em 2026-10-07: QA-03)*.

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->

## Referências Abertas
<!-- Uma linha por arquivo de references/ das skills aberto nesta feature, com o step. O checklist
     final confere contra o mínimo da tabela do Índice do SKILL.md; o que foi pulado diz por quê. -->
- `template-01-plano.md`, `template-02-adr.md`, `template-03-progresso.md`, `padrao-de-log.md`, `citacoes-de-codigo.md` — step 4 — 2026-10-07
- `.env.example:358-360` ainda diz que o recado é "TEXTO, não HTML" (anterior a esta feature; fora de escopo) — step 10, 2026-10-07

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: —
- **Faltou no plano**: —
