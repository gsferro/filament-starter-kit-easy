# Relatório de QA — fix/rodape-separado: o recado volta ao cartão do login e a assinatura fica

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão (natureza `ajuste`, UI presente sem JS, domínio comum)
> Natureza da wiki: ajuste · Toca infra compartilhada: sim → `KitServiceProvider::configureTelaDeLogin()` (hook de toda tela de login) · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 7 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 4 · Minor: 3 · Cosmético: 2
- Não verificadas: F (parcial), G, H, J (parcial), K (parcial). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma (Q1 e Q2 retiradas, cobertas por P-01/P-02, que pedem confirmação no PR)
- Ambiente: app em `http://127.0.0.1:8011` · Pest 5.0.5 · Playwright MCP: indisponível · Boost MCP: não usado

O produto atende ao requisito. No app servido, o recado (`<aside class="fi-login-rodape">`, com o Markdown renderizado) sai dentro de `.fi-auth-form-container`, depois do `</form>`. A assinatura `<footer class="kit-versao">` sai fora do cartão e depois dele, em `/admin/login`, `/app/login` e `/infra/login`. Em `/admin/password-reset/request` não há recado. O registro é igual ao de `bfe9a8d^`, sem `scopes:` e depois dos botões; só a tag mudou, de `<div>` para `<aside>`, e isso é a P-05. Todos os Majors são de **consistência documental (L)**: há alegações do `03` que a evidência dele mesmo desmente, e o PRD e a ADR afirmam o que o diff não faz.

## Achados

### QA-01 — A falsificabilidade dos CTs alterados está fechada com evidência que a desmente · Major · destino 1

- **Dimensão**: L6 (e K)
- **Relacionado a**: passo 3 do PRD (critério de pronto), CT-01, CT-04, CT-09, CT-12, M7
- **Esperado**: `03` l.33 `[x] Falsificabilidade: cada CT alterado fica vermelho com o provider de main`. O 01 diz no passo 3: "se não ficar, ele não mede o defeito". O `04` (§Divergências) diz que os mutantes manuais M1, M2, M3, M7 e M8 foram "medidos pelo executor".
- **Observado**: a evidência colada nas linhas 27–33 do `03` diz "provider de `main` contra CT-10, CT-12, CT-09, CT-01 e CT-04 = 25 testes, **4 falharam** (M1 e M7 mortos)". Os 25 testes são as linhas de dataset: CT-10 4 + CT-12 5 + CT-09 4 + CT-01 7 + CT-04 5. Só as 4 linhas do CT-10 podem falhar contra `main`. O provider de `main` escopa o recado a `TelaLogin`/`TelaLoginUnificada`, então as ausências do CT-01, CT-04, CT-09 e CT-12 passam ali. Logo, 21 dos 25 ficaram verdes, e "M7 morto" não decorre dessa execução: M7 é o `FOOTER` **sem** escopo, e o que se rodou foi o `main` com escopo. Não há evidência medida para M2, M3, M7 nem M8.
- **Repro**:
  1. `git show main:app/Providers/KitServiceProvider.php | grep -n "scopes:"` mostra `scopes: [TelaLogin::class, TelaLoginUnificada::class]`.
  2. Contar os datasets em `tests/Kit/RodapeCoerenteTest.php`: 4+5+4+7+5 = 25. Com o recado escopado ao login, só o limite superior do CT-10 (`$posicaoDoRecado < $fim`) quebra.
- **Evidência**: `03-progresso.md:27-33` (a mesma frase repetida em 7 checkboxes) e `04-casos-de-teste.md:458`
- **Ação exigida**: reabrir o checkbox ou reescrevê-lo com o fato: o CT-10 é falsificado contra `main` (4/4); o CT-01, o CT-04, o CT-09 e o CT-12 não são falsificáveis contra `main` (motivo: `main` não vaza o recado) e matam M7–M11 por construção, sem medição. Ou medir M2, M3, M7 e M8 e colar o resultado. Corrigir a mesma alegação no `04` (§Divergências) e no critério do passo 3 do `01`.

### QA-02 — Números de teste que nenhum comando reproduz · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: `## Despachos` linhas 6, 7 e 10 e checkboxes dos passos 1 e 3 do `03`
- **Esperado**: todo número tem um comando que o gera.
- **Observado**: `RodapeCoerenteTest 73/73 e 322 asserções`, citado em 14 checkboxes e na linha 6 de Despachos. Na HEAD dá **73 / 326**, porque o CT-10 ganhou asserções na rodada pós-revisão (RD-03). A linha 10 diz "pest 81/81 (352 asserções)" sem comando, e nenhuma combinação dos arquivos citados dá 81: Rodape 73, Helpers 1, Google 62, ContaIndisponível 9, Telas 26, Citações 3. A linha 7 (`RodapeNaDobraTest 9/9 e 48`) e `LoginSocialGoogleTest 62/62` **conferem**.
- **Repro**: `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact` devolve `{"tests":73,"passed":73,"assertions":326}`.
- **Evidência**: `grep -rn "322\|81/81\|352" wikis/specs/fix/rodape-separado/` aponta `03-progresso.md` l.12–18, 27–33, 163 e 167.
- **Ação exigida**: trocar por saídas reais com o comando ao lado (326 na HEAD, ou "322 em `b940d2c`" se for histórico) em todas as linhas listadas, e citar o comando do "81/81" ou removê-lo.

### QA-03 — `ConfiguracoesDoKit` mudou, e o PRD e a ADR dizem que não · Major · destino 1

- **Dimensão**: L3 (e A: código sem passo)
- **Relacionado a**: RQ-02, P-04, RD-07
- **Esperado**: o `01` (§Superfície de UI, "Gate de tela de escrita") diz que a tela que grava o recado (aba Login) "não muda (P-04)". O `02` (§Superfície Livewire) diz que a tela que grava o recado (`ConfiguracoesDoKit`) "não é tocada". Nenhum passo lista `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`.
- **Observado**: o diff muda a descrição da seção "Rodapé da tela de login" para "Aparece dentro do cartão de login dos três painéis, abaixo dos botões." A mudança é coerente com o RQ-02, mas existe só como RD-07 no `03`, sem passo, sem Desvio do Plano e sem marca `*(alterado em …)*`.
- **Repro**: `git diff main...HEAD -- app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- **Evidência**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:description:672` (diff de 1 linha). `filacheck` dá "All 17 rules passed". `pint --test` passa.
- **Ação exigida**: incluir o arquivo no passo 5 (ou no 1) do `01` com marca de data, e corrigir as duas frases do `01` e do `02`. Não há código a mudar.

### QA-04 — O comentário de `kit.css` mudou contra o critério do passo 2, e a cópia publicada divergiu · Major · destino 1 (e 2)

- **Dimensão**: L3 / L4
- **Relacionado a**: passo 2 do PRD, P-03, RD-01, `.ai/rules/css-filament.md`
- **Esperado**: o critério de pronto do passo 2 diz que o `git diff --name-only` **não** lista `kit.css`. Em `## Conformidade com Rules` do `03`, `css-filament.md` está "n.a." porque "`resources/css/filament/**` **não** entra no diff; `kit.css` e `public/css/kit/kit-correcoes.css` intocados". A rule manda, depois de editar: `php artisan filament:assets`.
- **Observado**: `kit.css` está no diff (7 linhas, só comentário). O checkbox da l.24 do `03` está `[x]` dizendo "sem os três arquivos" e admite o desvio só no próprio texto. O desvio não está em `## Desvios do Plano` nem no `01`. `public/css/kit/kit-correcoes.css`, que é versionado e idêntico ao `kit.css` em `main`, agora diverge dele nas l.202–212.
- **Repro**: `git diff --name-only main...HEAD | grep kit.css` e `diff resources/css/filament/kit.css public/css/kit/kit-correcoes.css` (2 hunks, só comentário. Em `main`, `git show main:…` dos dois é idêntico).
- **Evidência**: as saídas acima e `03-progresso.md:24,109`
- **Ação exigida**: registrar o desvio em `## Desvios do Plano`, marcar o critério do passo 2 do `01` como alterado e corrigir a linha `css-filament.md` da Conformidade (casa pelo glob `resources/css/filament/**`, aplicada com evidência). Código: regenerar a cópia publicada (`php artisan filament:assets`) ou registrar por que ela fica defasada.

### QA-05 — Duas rules casadas pelo diff sem linha em Conformidade · Minor · destino 1

- **Dimensão**: L4
- **Esperado**: uma linha por rule casada.
- **Observado**: `filament.md` e `pages.md` casam `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` e não têm linha. O código cumpre as duas: a mudança é um texto de `->description()`, sem permissão nem segredo, e o `filacheck` passa.
- **Repro**: `bash .ai/skills/feature-wiki/scripts/conformidade-rules.sh wikis/specs/fix/rodape-separado/rodape-separado main` sai com exit 1 e duas linhas.
- **Ação exigida**: acrescentar as duas linhas como "n.a.", com a evidência.

### QA-06 — Reconciliação do step 10 incompleta e bifurcação do CT-B03 rotulada errado · Minor · destino 1

- **Dimensão**: L3 / L6
- **Observado**:
  - (a) As l.36–39 do `03` dizem "M59 morto … bifurcação **(a)** do `05`". O resultado descrito (`<div>` vermelho, `<aside>` verde) é a linha **(c)** da tabela do `05`. A (a) mandaria voltar a tag a `<div>` e revisar P-05. O `04` chama esse mutante de **M21**.
  - (b) O `05` §Roteiro Desenhado × Implementado está vazio, assim como os Pré-requisitos.
  - (c) O `05` §Seletores diz que o cartão "é medido no step 10 e fixado no teste", mas o teste mantém `.fi-auth-card, .fi-auth-form-container`, e o `03` registra que `.fi-auth-card` não é emitido.
  - (d) `## Verificação Final` do `03` tem 0 de 18 itens marcados, e os 3 do passo 6 estão abertos, embora a ADR-02 exista.
- **Repro**: leitura de `03-progresso.md:36-39,49-51,61-79`, `05-casos-de-teste-browser.md:25,120-128,134-141`
- **Ação exigida**: corrigir o rótulo para (c) e usar o ID M21. Preencher o Roteiro do `05` e a Verificação Final com evidência. Alinhar o `05` ao seletor efetivo.

### QA-07 — Vocabulário "recado" × "assinatura" fora do glossário · Minor · destino 1

- **Dimensão**: L7
- **Observado**: o texto original usa "rodapé" para as duas coisas, e a feature inteira (00–05, docs, CHANGELOG) depende da distinção entre **recado** do login e **assinatura** automática. `wikis/glossario.md` não tem nenhum dos dois termos.
- **Repro**: `grep -n -i "recado\|assinatura\|rodap" wikis/glossario.md` não devolve nada.
- **Ação exigida**: registrar os dois termos no glossário, cada um com o "Não confundir com" apontando o outro.

### QA-08 — Base do PR desatualizada na wiki · Cosmético · destino 1

- **Dimensão**: L3
- **Observado**: o `01` (l.274) e o `03` (l.9, l.24) citam `main` = `7221dd3` e um teto de pulados de 914. O merge-base real é `b347fcc`, e esse commit subiu o teto para 920.
- **Repro**: `git merge-base main HEAD` devolve `b347fcc…`. `grep -rn "7221dd3\|914" wikis/specs/fix/rodape-separado/`
- **Ação exigida**: atualizar o `01` e o `03` (as três ocorrências).

### QA-09 — Célula de Custo com placeholder · Cosmético · destino 1

- **Dimensão**: L6
- **Observado**: na linha 1 de `## Despachos` a coluna Custo é `{tokens · duração, quando o host reportar}`, que não é valor nem `—`.
- **Ação exigida**: colar o valor reportado pelo host ou `—`.

## Matriz de Rastreabilidade

`rastreabilidade.sh` saiu com exit 0: todo RQ e todo P-nn tem passo e CT, e o RQ-04 é de processo, com justificativa. Há uma única lacuna, a de código fora de passo:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-02 / P-04 | o recado no cartão; a opção de configuração não muda | 1, 5 (sem o arquivo) | — | — | `ConfiguracoesDoKit.php` (descrição da seção) | texto coerente | ❌ código sem passo (QA-03) |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. O diff, passo a passo, confere com o PRD (provider, blades e testes), salvo QA-03. App servido: recado no cartão e assinatura fora, nas 3 telas de painel. `indice.sh --check` exit 2: sem `07-tickets/` |
| B | Fronteiras e dados | ✅ | Leitura: ausente/vazio/espaços (CT-09, `filled()` + `trim`) e Markdown/HTML cru (`LoginSocialGoogleTest`, verde). Sondagem `--agent` indisponível (pest-plugin-agent ausente); leitura da validação basta |
| C | Matriz de permissão | ✅ | Sem ação nem policy. Visitante × admin/panel_user/infra × registro coberto pelo CT-12 (verde) |
| D | Observabilidade | ✅ | Sem log, por decisão do PRD. `git diff main...HEAD \| grep "^+.*Log::"` só acha texto da wiki. Nenhum PII |
| E | Performance | ✅ | `rodapeDoLogin()` lê só `config()`; o hook troca de chave, sem query nova (leitura) |
| F | UX de erro | ⏭️ parcial | Sem caminho de erro novo. O recado vem do schema de `Login::content()` e sobrevive ao re-render do erro de login (leitura); sem reprodução dinâmica |
| G | Tema e cor | ⏭️ | fora do perfil `padrão` |
| H | Acessibilidade | ⏭️ | fora do perfil `padrão` (registro: o CT-B03 com 4 regras de landmark passou, 9/9 do arquivo) |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (8 achados, 0 rejeitados). Além dele: nenhuma rota nova, nenhum `public $` Livewire novo, Markdown com `html_input => strip` e `allow_unsafe_links => false` inalterado, nenhum mass assignment |
| J | Regressão adjacente | ⚠️ parcial | Por arquivo, todos verdes: RodapeCoerente 73, VersaoNoRodape 46, LoginSocialGoogle 62, LoginSocialProvedores 187, ContaIndisponível 9, TelasDeAutenticacao 26, LoginUnificado (Kit 118, Browser 1), HelpersDeTeste 1, SiteDeDocumentacao 68, RedeDeDocumentacao 20, CitacoesDeCodigo 3, Browser RodapeNaDobra 9/48 e LoginSocial 2. `--parallel --tia` não rodado |
| K | Adequação da suíte | ⏭️ parcial | K1 `k1-oraculo-fraco.sh` exit 0. K2 fora do perfil. Revisão adversarial do `04`: "não exigida", não "NÃO FEITA". Falsificação medida só para o CT-10 e o CT-B01 (QA-01) |
| L | Consistência documental | ❌ | 8 achados (QA-01 a QA-09, menos o QA-03 da A). L1 `ids-ct.sh` exit 1 (só IDs da ancestral, ruído declarado no `04`; contagem do cabeçalho 6/5/23/1 confere). L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh` exit 1 (QA-05). L6 `checkbox-sem-evidencia.sh` exit 0, mas os números e a falsificação não se reproduzem (QA-01, QA-02). L5: pt × en × CHANGELOG × README coerentes (77 = `find wikis/specs -name 00-requisito.md \| wc -l`) |

## Débitos Aceitos

- (nenhum ainda: os Minor e Cosméticos QA-05 a QA-09 entram no `03` como débito se não forem fechados com os Majors)

## Suspeitas Não Confirmadas

- Recado longo (Markdown de várias linhas) pode empurrar a assinatura para fora da dobra (R1). O CT-B01 só mede "Fale com o suporte". Não reproduzido, porque não há driver de browser fora da suíte.

## Não Verificado

- G (tema e cor) — motivo: fora do perfil padrão
- H (acessibilidade além do CT-B03) — motivo: fora do perfil padrão
- F (reprodução dinâmica do erro de login com recado) — motivo: Playwright MCP indisponível e pest-plugin-agent ausente
- J passo 1 (`pest --parallel --tia`) — motivo: não rodado (custo e memória da suíte completa no host Windows; risco de gravar cache de TIA na árvore). Os passos 2 e 3 rodaram
- K2 (mutação medida) — motivo: fora do perfil padrão. PCOV, Xdebug e `pest-plugin-mutate` **estão** instalados (`php -m`, `ls vendor/pestphp/`)

## Veredito — Ciclo 2

**REPROVADO → especificação**

> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 7 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

- Blocker: 0 · Major: 2 · Minor: 2 · Cosmético: 0
- Não verificadas: F (em parte), G, H, J (em parte), K (em parte). As causas estão em *Não Verificado — Ciclo 2*.
- `RQ` abertas: nenhuma.
- Ambiente: app em `http://127.0.0.1:8011` · Pest 5 · Playwright MCP indisponível · Boost MCP não usado. Merge-base `b347fcc`.

O produto continua atendendo ao requisito. No app servido, em `/admin/login`, `/app/login` e `/infra/login`, a ordem dos marcadores é `fi-auth-layout` → `fi-auth-form-container` → `</form>` → `fi-login-rodape` → `kit-versao`. O recado ("Fale com o **suporte** da Acme") fecha antes do `</div>` do contêiner. A assinatura é `<footer class="kit-versao">` e fica depois do layout. Em `/admin/password-reset/request` não há recado. `/login` responde 302 porque o login unificado está desligado no app servido; a suíte cobre essa rota. O ciclo 1 fechou 7 dos 9 achados. Dos outros dois, o QA-04 ficou com cópias da mesma afirmação (viraram o QA-10) e o QA-06 ficou aberto em parte. Os achados novos são todos da dimensão L.

### Verificação dos achados do ciclo 1

| ID | Sev. | Estado | Evidência (ciclo 2) |
|---|---|---|---|
| QA-01 | Major | **fechado** | `03` l.33, `04` l.458 e `01` l.216 dizem agora o mesmo: M1 matou 4 de 25 (só o CT-10), e o CT-01, o CT-04, o CT-09 e o CT-12 não são falsificáveis contra `main`, com o motivo. Não reexecutei os mutantes, porque isso altera a árvore. Conferi as contagens pelos datasets: M7 = 4+4+1+1 = 10, M8 = 4+4+1+1+2+1+1 = 14, M3 = 4, M2 = 2. Todas batem com o que o `03` declara |
| QA-02 | Major | **fechado** | `pest tests/Kit/RodapeCoerenteTest.php --compact` dá `73/73, 326`. O comando colado ao lado do "81/81" dá `81/81, 352`. Os dois reproduzem |
| QA-03 | Major | **fechado** | Passo 5 do `01` (l.36 e l.242), Superfície do `01` (l.114) e `02` l.83 corrigidos, com a marca de data |
| QA-04 | Major | **fechado em parte** | Corrigido: Desvio registrado (`03` l.189), critério do passo 2 marcado, linha `css-filament.md` com "aplicada" e cópia publicada regenerada (`diff kit.css kit-correcoes.css` vazio). Ficaram cópias da mesma afirmação em outros arquivos → **QA-10** |
| QA-05 | Minor | **fechado** | `conformidade-rules.sh … main` sai com exit 0 |
| QA-06 | Minor | **aberto em parte** | (a), (b) e (c) fechados: rótulo (c), ID M21, Roteiro, Pré-requisitos e seletor efetivo no `05`. O (d) segue aberto (detalhe abaixo) |
| QA-07 | Minor | **fechado** | `wikis/glossario.md` tem "Recado do login" e "Assinatura do rodapé", uma apontando a outra |
| QA-08 | Cosm. | **fechado** | `grep -rn "7221dd3\|914\b"` na wiki, fora do `06`, não devolve nada |
| QA-09 | Cosm. | **fechado** | A coluna Custo de `## Despachos` está com `—` em todas as linhas |

### QA-06 (resto do (d)) — A Verificação Final segue aberta onde já há evidência · Minor · destino 1

- **Dimensão**: L6
- **Observado**:
  - `03` l.65 (`RodapeNaDobraTest`) está `[ ]`, mas a evidência existe: 9/9 e 48 em Despachos l.178 e no Roteiro do `05`, e reproduzi agora (9/9, 48).
  - `03` l.74 está aberto com o motivo "citacoes.sh acusa uma citação no 06", mas `citacoes.sh` sai hoje com exit 0 e em silêncio.
  - Passo 6, l.49–50: a ADR-02 existe, e `git diff --stat main...HEAD -- wikis/specs/feat/rodape-coerente` volta vazio. Mesmo assim, os dois itens estão `[ ]`.
- **Repro**: `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}; echo $?` dá 0. `git diff --stat main...HEAD -- wikis/specs/feat/rodape-coerente | wc -l` dá 0.
- **Ação exigida**: marcar os quatro itens com a evidência colada. O `INDEX.md` (l.51) fica para depois do veredito, como manda o step 11.

### QA-10 — O plano e os casos de teste ainda dizem que o CSS e a assinatura não entram no diff · Major · destino 1

- **Dimensão**: L3. São cópias da afirmação do QA-04, que só foi corrigida no critério do passo 2.
- **Relacionado a**: P-03, passo 2, RD-01, D5
- **Esperado**: o diff tem `resources/css/filament/kit.css` e `public/css/kit/kit-correcoes.css` (só comentário, Desvio no `03` l.189), além de `assinatura-do-rodape.blade.php` (comentário, D5) e `tests/Pest.php` (helper do D8).
- **Observado**, sem marca `*(alterado em …)*`:
  - `01` l.22 (Cobertura, P-03): "nenhum arquivo da assinatura nem o CSS entra no diff"
  - `01` l.74 (título): "`kit.css` e `tests/Pest.php` (fora do diff)"
  - `01` l.306 (Rastreabilidade, P-03): "a ausência de diff nos arquivos da assinatura e do CSS"
  - `04` l.329: "nenhum arquivo da assinatura entra no diff"
  - `04` l.417: "o diff não toca `kit.css` nem a blade da assinatura"
- **Repro**: `git diff --name-only main...HEAD | grep -E "kit.css|kit-correcoes|assinatura-do-rodape|tests/Pest.php"` devolve 4 arquivos. `grep -n "entra no diff\|fora do diff\|não toca .kit.css\|nem o CSS" {wiki}0[0-5]*.md` devolve as 5 linhas acima.
- **Ação exigida**: reescrever as 5 linhas com o fato. Esses arquivos estão no diff só com comentário ou com o helper do D8, e o comportamento da assinatura e do CSS não muda. Marcar a data. A prova de P-03 deixa de ser "ausência de diff" e passa a ser "diff só com comentário" mais o CT-B02 e o CT-07.

### QA-11 — Números das medições de browser que o próprio teste desmente · Major · destino 1

- **Dimensão**: L6 (e K)
- **Relacionado a**: passo 4 (critério "CT-B01 medido também contra o estado sem o passo 1"), CT-B01, CT-B03, M1
- **Observado**:
  - (a) O `03` l.40 diz que o CT-B01 contra o provider de `main` ficou "vermelho nas 5 linhas". A 5ª linha do dataset (`/admin/password-reset/request`, `esperaRecado=false`) não pode falhar contra `main`. O `main` escopa o recado a `TelaLogin` e `TelaLoginUnificada`, então a tela de recuperação não tem recado, e `temRecado` = `false` como o esperado. A dobra da assinatura é igual à da HEAD, que passa. O próprio `05` l.77 prevê "vermelha nas quatro telas de login". Uma 5ª linha vermelha indica falha de arnês, não morte do mutante. A citação ("o recado em /login não…") é a mesma mensagem para todas as linhas, o que reforça isso. A falsificação do CT-B01 fica sem prova confiável.
  - (b) O `03` l.36–39 e o `05` l.142 dizem que "nenhuma das 4 regras acusa o `<aside>` nas 5 (cinco) rotas". O CT-B03 roda em 3 rotas (`/admin/login`, `/login`, `/admin/password-reset/request`), e só 2 delas têm recado. Nenhum comando reproduz "5 rotas".
- **Repro**:
  1. `sed -n 90,96p tests/Browser/RodapeNaDobraTest.php` mostra 5 linhas, a última com `false`.
  2. `git show main:app/Providers/KitServiceProvider.php | grep -n "scopes:"` mostra `[TelaLogin::class, TelaLoginUnificada::class]`.
  3. `sed -n 222,226p tests/Browser/RodapeNaDobraTest.php` mostra o dataset do CT-B03 com 3 rotas.
  4. `grep -rn "5 rotas\|cinco rotas" {wiki}` aponta o `03` l.36–39 e o `05` l.142. O "cinco rotas" do `01` l.35 é do CT-B01 e está certo.
- **Ação exigida**: (a) medir de novo e colar o resultado linha a linha: quais falharam e com qual mensagem. O esperado é 4 de 5, com a recuperação de senha verde. Se a 5ª falhar, investigar o arnês antes de aceitar a falsificação. (b) Trocar por "nas 3 rotas do CT-B03 (2 com recado)", nos quatro checkboxes do `03` e no `05` l.142.

### QA-12 — O `05` diz que M19, M20, M22 e M23 foram medidos, e não há registro · Minor · destino 1

- **Dimensão**: L6 / L3
- **Observado**: o `05` l.75 e l.131 têm o título "Falsificabilidade (medida no step 10, registrada no `03`)" e listam M20, M19, M22 e M23. O `03` registra só M1 (CT-B01) e M21 (CT-B03). M20 não é plantável no modo atual: sem cartão, o vendor não renderiza `CardAfter`. Ver `vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php` l.49–58.
- **Repro**: `grep -n "M19\|M20\|M22\|M23" {wiki}03-progresso.md` não devolve nada.
- **Ação exigida**: reescrever os dois títulos como "previsto". Para cada mutante, marcar "não medido nesta entrega", ou "herdado da ancestral" com o ID dela, ou medir e registrar no `03`. No M20, registrar que ele não é plantável com `.fi-auth-form-container`.

### Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. `indice.sh --check` não roda: não há `07-tickets/`. O código fora de passo (QA-03) foi fechado. App servido: recado dentro de `.fi-auth-form-container` e assinatura fora, nas 3 telas de painel |
| B | Fronteiras e dados | ✅ | Sem código novo desde o ciclo 1. CT-09 (ausente, vazio, só espaços) verde |
| C | Matriz de permissão | ✅ | Sem ação nem policy. CT-12 verde |
| D | Observabilidade | ✅ | Nenhum `Log::` no diff de código. Nenhum PII |
| E | Performance | ✅ | Sem query nova |
| F | UX de erro | ⏭️ em parte | Sem reprodução dinâmica do erro de login com recado |
| G | Tema e cor | ⏭️ | Fora do perfil padrão. `dark-mode.sh --mecanismo` sai com exit 1 (Filament com classe `dark`) |
| H | Acessibilidade | ⏭️ | Fora do perfil padrão. O CT-B03 (4 regras de landmark) está verde, 9/9 no arquivo |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (8 achados, 0 rejeitados). Além dele: nenhuma rota, `public $` nem mass assignment novos. `filacheck`: All 17 rules passed |
| J | Regressão adjacente | ⚠️ em parte | Verdes: `RodapeCoerente` 73/326, os três arquivos do "81/81" com 81/352, `RodapeNaDobra` 9/48 e Site + Rede + Citações + Helpers + LoginSocialGoogle + VersaoNoRodape + TelasDeAutenticacao com 226/2189. `--parallel --tia` não rodou |
| K | Adequação da suíte | ⏭️ em parte | K1 `k1-oraculo-fraco.sh` exit 0. K2 fora do perfil. As contagens de mutantes manuais conferem pelos datasets. A falsificação do CT-B01 não está provada (QA-11). Revisão adversarial "não exigida" |
| L | Consistência documental | ❌ | 4 achados abertos (QA-06 resto, QA-10, QA-11, QA-12). L1 `ids-ct.sh` exit 1, com as 15 linhas de ruído da ancestral, e o cabeçalho do `04` 6/5/23/1 confere pelo `grep -c`. L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh` exit 0. L5: docs pt × en × CHANGELOG coerentes, e README 77 = `find wikis/specs -name 00-requisito.md \| wc -l`. L6 `checkbox-sem-evidencia.sh` exit 0, mas há números de browser que não se reproduzem (QA-11). L7 fechado |

### Suspeitas Não Confirmadas — Ciclo 2

- R1 (recado longo empurrando a assinatura para fora da dobra) segue sem medir. O CT-B01 só usa "Fale com o suporte".

### Não Verificado — Ciclo 2

- G e H: fora do perfil padrão.
- F (reprodução dinâmica): Playwright MCP indisponível e `pest-plugin-agent` ausente.
- J passo 1 (`pest --parallel --tia`): não rodou, por memória do host e por instrução do orquestrador.
- K2 (mutação medida): fora do perfil padrão. Os mutantes manuais M1, M2, M3, M7 e M8 não foram reexecutados, porque isso exigiria alterar a árvore. Foram conferidos por aritmética sobre os datasets.
