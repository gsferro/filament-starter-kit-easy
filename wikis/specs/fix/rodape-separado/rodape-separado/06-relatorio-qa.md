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
