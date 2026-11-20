# Progresso — Mapa interativo do kit com a skill archify

**Estado**: em implementação (passo 1, em paralelo com a re-revisão adversarial única do step 7, que não bloqueia o esqueleto e bloqueia os despachos de `fw-executor-ct` e `fw-executor-ctb`)

> Base do PR (`{base}`): `main` · Branch: `feat/mapa-interativo-do-kit`

## 1. Esqueleto andante — um diagrama de cada tipo, do modelo ao `dist/`, num clone raso no Linux
- [x] `site/mapa/archify.mjs` baixa e confere o commit declarado — 3,9 s no primeiro download (142 MB, raso), sem rede nas seguintes; num contêiner limpo também
- [x] `site/mapa/interface.json` *(desvio: era `pt-BR.json`)* com as 25 chaves que faltam no catálogo do archify e as sobrescritas de tipo de peça (D2, D12)
- [x] `site/.gitignore` com `.archify/`
- [x] `site/verifica-mapa.mjs` com a amostra e a tolerância da ADR-06 (D17), e o passo dele no `pages.yml` e no job `site` do `ci.yml`; os CT-B do `05` entram no passo 8 (`fw-executor-ctb`)
- [x] Um diagrama de cada tipo e um comparativo, pt e en, por `npm run build`, `verifica-links`, `verifica-acessibilidade` e `verifica-mapa` (42 HTML na amostra, 11 tipos; tolerados 40 `nested-interactive` e 4 `color-contrast`) num `git clone --depth 1` dentro de `mcr.microsoft.com/playwright:v1.63.0-noble`
- [x] Cinco conferências inéditas: saída en (o passaporte diz "PACKAGE", e nenhum "Frontend", "Cloud", "Message bus" ou "Backend" no texto visível); `verifica-links` sobre o HTML do archify (0 quebrado); `--repo-root` num clone raso de 1 commit (o build passou com a prova conferida); download pelo commit num contêiner limpo. O piso de 6,5 px **não** foi medido pelo `visual-check` do archify (ver a nota), e fica com a medição do conferidor do mapa (CT-B do `05`)
- [x] Tempos: `render` ≈ 0,36 s por HTML (26 HTML em 15 s no Windows; build inteiro com o mapa em 19 s no Linux); `verifica-mapa` na amostra em 19 s; `visual-check` de 1,2 a 17 s por HTML. Decisão: amostra (ADR-06), e o `visual-check` fica fora do CI

## 2. Modelo do mapa — peças, ligações e diagramas
- [ ] `site/mapa/pecas.json` e `ligacoes.json` normalizados das varreduras, com ids estáveis entre os modos
- [ ] `site/mapa/diagramas/{slug}.json` — architecture com layout, trees de registro e os curados (ERD, classes, tempos); a contagem fechada registrada aqui
- [ ] `site/mapa/posicionar.mjs` (desenvolvimento)
- [ ] Evidência em âncora de trecho, conferida contra a revisão do branch

## 3. Fluxos bilíngues
- [ ] `site/mapa/diagramas/{slug}.json` dos fluxos da matriz, escritos a partir do código (D5)
- [ ] Texto en traduzido pelo Codex e revisado

## 4. Gerador
- [ ] `site/mapa/gerar.mjs` — construtores de architecture e da tree de registro, e a projeção genérica de idioma e âncoras
- [ ] `site/package.json` (`build`)
- [ ] Cards de prova (P-04) em todo diagrama com ligação
- [ ] `compare architecture` por painel

## 5. Catálogo único
- [ ] Coluna Origem nas oito seções, pt e en
- [ ] Features novas a partir de F-72
- [ ] Defasagens corrigidas
- [ ] Conferência independente pelo Codex

## 6. Página do mapa no site
- [ ] `docs/{pt,en}/referencia/mapa-interativo.md`
- [ ] Slug, stub e redirect (diff do conversor só com a página nova)
- [ ] Links no índice de referência e na página de diagramas

## 7. README
- [ ] Seção do mapa em pt e en
- [ ] Contadores

## 8. Guardas
- [ ] `tests/Kit/MapaInterativoTest.php`
- [ ] `tests/Tenancy/MapaInterativoTenancyTest.php`

## 9. Rule do catálogo
- [ ] `.ai/rules/catalogo-de-features.md` e a linha no índice

## 10. Documentação de manutenção e CHANGELOG
- [ ] `docs/{pt,en}/operacao/desenvolvendo-o-kit.md`
- [ ] `CHANGELOG.md` (`[Unreleased]`)

## 11. PR com CI verde
- [ ] PR aberto para `main`
- [ ] CI verde

## Testes
- [ ] `tests/Kit/MapaInterativoTest.php` (CT-01 a CT-11, CT-13 a CT-29, CT-31, CT-34 a CT-37, CT-39 a CT-43)
- [ ] `tests/Tenancy/MapaInterativoTenancyTest.php` (CT-12, CT-30, CT-38)
- [ ] `site/verifica-mapa.mjs` (CT-B01 a CT-B07) — conferidor Node + Playwright do site construído (D17); o
  `ids-ct.sh` só lê `it()`/`test()` do Pest e acusa estes sete como sem teste, por desenho; a conferência deles é o
  próprio conferidor no `pages.yml` e no job `site` do `ci.yml`

Recortes bloqueados até a resposta do mantenedor (nenhuma RQ inteira): a lista fechada e a ordem dos fluxos
(Q6) e a marca da peça condicional (Q11) — com ela ficam estacionados a R30 (CT-32 e CT-33, IDs reservados e
riscados no Índice do `04`), o passo 4 do CT-B01 e o R24-M5. O `04` lista os recortes em `### Cláusulas abertas e
coberturas parciais`.

## Tickets
Não fatiado — 2026-11-20: 26 RQ vigentes, 41 CT (dois deles estacionados na Q11) e 7 CT-B, compactação: sim, 2 perguntas de requisito abertas (Q6, Q11) — dois sinais do step 8 (tamanho e compactação); a sugestão de `/feature-tickets` foi feita ao mantenedor no status de 2026-11-20 e ficou sem resposta; a sessão segue sem fatiar, com a autorização "implemente ate o fim" (RQ-14, RQ-26), e paraleliza os passos do `01` por conjuntos de arquivos disjuntos

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/MapaInterativoTest.php tests/Tenancy/MapaInterativoTenancyTest.php --compact`
- [ ] Suítes `Kit` e `Tenancy`: nada quebrou além da **baseline** de `main` (falhas pré-existentes por nome)
- [ ] `npm run build`, `verifica-links` e acessibilidade do site num clone raso, no contêiner Linux do passo 1
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh {wiki} 'tests/**/MapaInterativo*Test.php'` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-11-20 — mantenedor, pelas respostas a Q1–Q4 (Adendo 1) e pela autorização "implemente ate o fim" do pedido — 1 rodada; perguntas: 0 fato, 0 desenho, 4 requisito

Sinal de escopo do step 4: 26 RQ (18 originais + 8 do Adendo 1) — acima do limiar de tamanho do step 8; a sugestão de fatiar é feita no step 8.

Roteiro manual: não — 2026-11-20 — sem resposta a Q5 — a entrega é um site estático gerado e conferido por máquina (links, acessibilidade, leitura); o valor de um passo a passo manual seria abrir alguns HTML no navegador, o que o esqueleto (passo 1) e o quality gate já fazem

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| O painel montado expõe `getResources()`, `getPages()`, `getWidgets()`, `getPlugins()`, `getMiddleware()` e `getAuthMiddleware()` | confirmado em `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getPages:442`, `:getResources:466`, `:getWidgets:490`, `vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getMiddleware:97`, `:getAuthMiddleware:108`, `vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:getPlugins:38`; existe também `vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getTenantMiddleware:116`, que o plano não citava | `01` → *Painéis vivos* passa a citar `getTenantMiddleware()` (a pilha do `/app` com organização) |
| A suíte `Tenancy` liga o modo antes do boot dos painéis | `tests/TenancyTestCase.php:usaTenancy:19` devolve `true`; o mecanismo mora em `tests/TestCase.php` | nenhuma |
| `tests/Kit` e `tests/Tenancy` viajam pelo `kit:update` | `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93` (entradas nas linhas 252–253) | nenhuma (o passo 9 já exige `naArvoreDoKit()`) |
| Suíte nova que lê documentação precisa ser registrada em alguma lista | não precisa: `tests/Kit/RedeDeDocumentacaoTest.php:suitesDeDocumentacao:50` descobre por varredura de `tests/Kit` e `tests/Tenancy` | nenhuma |
| O README manda o leitor aos diagramas na linha 169 | `README.md:169` e `README.en.md:169` | nenhuma |
| `docs/{pt,en}/operacao/desenvolvendo-o-kit.md` existem | sim, nos dois idiomas | nenhuma |

**Classe irmã.** A feature não cria classe em `app/`. Para os dois arquivos de teste novos, a irmã é
`tests/Kit/GuardasDosDiagramasTest.php`: citada por `tests/Pest.php` (docblocks de helpers), pela página
"Arquitetura em diagramas" (pt e en, primeiro parágrafo) e por `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`
(comentário). Nenhuma lista mantida à mão precisa da classe nova; a página do mapa cita as próprias guardas.

### Auditoria Ponytail (step 6)

`ponytail-review` rodado na sessão sobre o `01` e o `02` (o `03` acompanhou os cortes) — net estimado: cerca de
1.000 linhas a menos na implementação e dois passos a menos no plano.

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `yagni`: dez construtores em `site/mapa/tipos/`; só architecture e a tree de registro saem do modelo, os oito tipos curados viram o próprio JSON do archify com textos `{pt, en}` | sim | `01` passos 2, 3 e 4; `03` seções 2, 3 e 4 |
| 2 | `delete`: `esquema.json`, `classes.json` e `tempos.json`; ERD, classes e tempos são diagramas curados, e o ERD é conferido contra o esquema vivo | sim | `01` passo 2; `03` seção 2 |
| 3 | `yagni`: o manifesto `diagramas.json` repetia o que cada arquivo de diagrama já diz; a pasta é o manifesto | sim | `01` passo 2; ADR-01 |
| 4 | `shrink`: `site/mapa/modelo/{,fluxos/,diagramas/}` vira `site/mapa/{pecas,ligacoes}.json` e `site/mapa/diagramas/` | sim | `01` passos 2 e 3 |
| 5 | `delete`: o parser do `CHANGELOG.md` para a timeline (os marcos são curados e o texto precisa dos dois idiomas; o CHANGELOG é só pt) | sim | `01` passo 4; a guarda confere as versões citadas |
| 6 | `shrink`: o `pt-BR.json` de 486 chaves; o `examples/locales/pt.json` do archify fixado já tem 461, idênticas (0 diferenças medidas contra o catálogo do laboratório) | sim | `01` D2 e passo 1; `03` seção 1 |
| 7 | `delete`: o passo 8 (acessibilidade), que o passo 1 já cumpre com a amostra automática | sim | `01` (passos renumerados); `03` |
| 8 | `delete`: o passo 12 (clone raso), que repetia o script do contêiner do passo 1 e o item da Verificação Final | sim | `01` (passos renumerados) e Verificação Final; `03` |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `web-fetch` — leitura de `https://archify.si/` | não exposto pelo host | a conversa | resumo da página da skill | — | conferido contra o clone do repositório do archify (SKILL.md, schemas) |
| 2 | 3 | Codex CLI 0.163.0 (`codex exec --yolo`, somente leitura no repo) — varredura independente de features do código inteiro | não exposto pelo host | a conversa, as outras pesquisas | `mapa-pesquisa/codex-varredura.md`: 238 linhas de feature, 25 diferenças com/sem tenant, 24 fluxos, 85 requisitos do Composer | — | `git status --porcelain` igual antes e depois (`git-status-antes-codex.txt`); 1.847 de 1.851 citações conferidas por script na crítica do workflow |
| 3 | 3 | Workflow `wf_f70be519-368` — 11 pesquisas (archify, site-infra, catálogo, painel-admin, painel-app, painel-infra, transversal-auth, transversal-dados, tenancy, diagramas-existentes, dados-reais) + crítico de completude + 2 lacunas (IA, catálogo × código) | opus (herdado da sessão) | a conversa | 14/14 agentes concluídos, 0 erros; 13 arquivos em `mapa-pesquisa/` | 7.059.723 tokens · 2 h 13 min | crítico conferiu 4.667 citações únicas (0 arquivo ou linha inexistente); amostragem manual da sessão: blocos JSON de `painel-admin.md`, `painel-app.md`, `painel-infra.md`, `transversal-auth.md`, `lacuna-ia.md` parseados (contagens batem com os resumos) |
| 4 | 3 | Workflow `wf_07bf9f13-7ca` — spike de escala: 6 protótipos (admin inteiro, admin em recortes, compare do `/app`, ERD, fluxos A e B) + juiz | opus (herdado da sessão) | a conversa | 7/7 agentes concluídos, 0 erros; `/admin` inteiro reprovado (falhou), os outros 5 passaram com ressalvas; veredito do juiz com limites por tipo, decomposição e violações de axe novas | 3.057.836 tokens · 1 h 11 min | o juiz abriu 37 capturas e refez o teste da busca do visualizador; a sessão abriu 2 capturas (`moldura.k6` e o delta do compare do `/app`) e conferiu no código do archify a formatação en-US do `waterfall` (função `number` de `renderers/render-waterfall.mjs` do archify, linha 131) e da `timeline` (função `partsFormat` de `renderers/render-timeline.mjs` do archify, linha 113); o ` M wikis/glossario.md` que os agentes estranharam é edição desta sessão (step 4) |
| 5 | 7 | `general-purpose` como analista (a `feature-test-design` lida por path) — derivação do `04`/`05` e duas rodadas de revisão com as respostas da sessão (renumeração Q6–Q17; Q7, Q9, Q13 e Q16 fechadas pelo `00`; P-11; Q8, Q10, Q14, Q15 e Q17 decididas como desenho; CT-B no conferidor próprio) | opus | a conversa; o `01` e o `02` (só trechos: paths, rotas, stack, `## Superfície de UI` e a `## Superfície Livewire`); código da feature (não existe) | 33 regras, 34 CT, 6 CT-B, 133 mutantes, 2 sem matador; 12 perguntas (Q6–Q17), 2 abertas (Q6, Q11) | 1.824.682 tokens · 1 h 48 min (3 rodadas: 70 + 24 + 13 min) | repositório intocado nas três rodadas (`git status --porcelain` e mtimes de `00`–`03` iguais antes e depois); contagens do cabeçalho refeitas pela sessão com os `grep -c` do template (34/33/133/2 e 6 CT-B); 9 citações amostradas por `sed -n` (CT-22:613, CT-45, `canAccess:107`, `blocosMermaidDaArvore:1478`, `kit.tenancy.enabled:658`, `painelRegistradoEmTeste:812`, `codigoSemComentario:2188`, `AgenteIaResource canAccess:70`, `Par sempre registrado:151`) e o fato do plugin no vendor (`FilamentSpatieLaravelHealthPlugin.php`, linha 36); base congelada remedida (74 F-nn por idioma, maior 71; 46 itens do README em pt e en; 91 pacotes nos manifestos; os 4 fora da referência). Ajuste da sessão sem mudar cenário: 3 citações postas no formato do `citacoes.sh` (símbolo entre aspas) e a coluna `Confirmada` das costuras |
| 6 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` (não exigida pelo perfil; despachada por decisão da sessão pelo tamanho do conjunto) | opus (do agente) | o `01`, o `02`, o `03`, o código, a conversa e o raciocínio da derivação | 34 achados (ADV-01 a ADV-34): 18 implementações erradas que passam, 12 oráculos fracos, 3 de cobertura de RQ, 1 da sonda 6; nenhum cenário malformado. Triagem da sessão, revista pelo advisor e fechada na quarta rodada do analista (placar único, o da tabela `## Revisão adversarial` do `04`, *(alterado em 2026-11-20: a triagem dizia 30/2/1/1 e contava o ADV-31 duas vezes)*): 32 aceitos — 31 por inteiro e o ADV-31 com a marca da peça condicional estacionada na Q11 e o sub-ponto do Estado no `00` rejeitado (contraria a regra da skill) —, 1 estacionado na Q11 (ADV-17; a Q11 tem três leituras, e a marca não vale em nenhuma delas sozinha) e 1 rejeitado como cenário e virado controle de processo (ADV-34, D24). Decorreram a P-12 no `00` e as D20–D24 no `01` | 293.369 tokens · 29 min | leu só os quatro arquivos permitidos (o relatório os lista; nenhuma leitura negada, o hook não falhou); a sessão conferiu os fatos que a triagem usou antes de aceitar: sobreposição de 103/135 classes (ADV-33), 687/748 ligações com prova no arquivo da origem (ADV-07), HTML do spike aberto no Chromium para o passaporte, os cards, `lang` e requisições externas (ADV-14, ADV-25, ADV-28 a ADV-30), CHANGELOG com F-nn em 5 versões (ADV-12) |
| 7 | 7 | o analista do #5 (`SendMessage`, contexto intacto) — quarta rodada: fechar os 34 achados do #6 no `04`/`05`, com a tabela `## Revisão adversarial` | opus | a conversa; o `01`, o `02` e o código além dos trechos já dados | tabela de fechamento com 34 linhas (32/1/1); R34 a R36 e CT-35 a CT-43 e CT-B07 novos; R30, CT-32, CT-33, passo 4 do CT-B01 e R24-M5 estacionados na Q11; 41 CT, 35 regras, 178 mutantes, 4 sem matador, 7 CT-B; 2 perguntas de raia desenho (Q?1, Q?2 → Q18, Q19) | 657.703 tokens · 56 min | contagens refeitas com os `grep -c` do template (41/35/178/4 e 7 CT-B); 34 linhas ADV distintas; a coluna `Confirmada` e as três citações com aspas preservadas; mtimes de `00`–`03` e do glossário anteriores ao envio (19:18:50Z), fora a edição da própria sessão no `03` às 19:18:59Z; `rastreabilidade.sh` e `citacoes.sh` silenciosos depois da cópia para a wiki. Escolhas que ninguém ditou, conferidas por comando: 135 = 123 classes + 7 enums + 5 traits (confere); `SimplePage` e `Page` estendem `BasePage` no Filament instalado (confere); "37 de 74" com rota (não confere em en: 38, pela F-44 — a regra "caminho em qualquer ponto" dá 40 nos dois idiomas); termo `dock` (não discrimina: casa "docker" na F-56); leitura da porta (fechada pela P-10 literal). Q18 e Q19 decididas com medição (D25, D26) |
| 8 | 7 | o analista do #5 (`SendMessage`) — ajuste da quarta rodada: Q18/Q19 renumeradas e decididas (D25, D26), casamento por palavra inteira na R5, recorte da R6 pela regra que dá 40/40, porta fechada pela P-10, linhas 211 e 225 | opus | idem #7 | Q18/Q19 registradas e decididas no `04`; R10 com a quinta forma (CT-10 com 2 linhas aceitas e 3 de controle; R10-M8 e M9); R5 por palavra inteira (CT-35 com a linha da F-56; R5-M8); R6 pela regra 40/40; porta fechada pela P-10 na R12; RQ-24 com cobertura completa; 41 CT, 35 regras, 181 mutantes, 4 sem matador, 7 CT-B (o `05` não mudou); nenhuma pergunta nova. Três desvios declarados e aceitos: o trecho do `PoliciesDeVendor` é `=> ExceptionPolicy::class,` (a linha alinha a seta com espaços, e o literal com `Exception::class` não existe); o `Event::listen` ficou na prosa da R10, porque evento não é tipo de peça da R8; a linha da RQ-24 saiu das coberturas parciais | 717.644 tokens · 7 min | contagens refeitas com os `grep -c` (41/35/181/4); nenhum `Q?n`; `05` idêntico ao anterior (`cmp`); mtimes de `00`, `02` e glossário inalterados, `01` e `03` só com as edições da sessão; `rastreabilidade.sh` e `citacoes.sh` silenciosos depois da cópia para a wiki |
| 9 | 7 | `fw-adversario-ct` — re-revisão única (segunda e última rodada, teto de 2 da `feature-test-design`), porque o fechamento criou cenário novo (CT-35 a CT-43 e CT-B07) | opus (do agente) | o `01`, o `02`, o `03`, o código, a conversa e o raciocínio da derivação | 32 achados (ADV2-01 a ADV2-32): 20 implementações erradas que passam, 8 oráculos fracos, 2 de RQ sem cenário discriminante (ADV2-29: o CT-37 afirmava recorte da Q11; ADV2-30: parte determinada bloqueada sem necessidade), 2 das sondas 7 e 8; nenhum cenário malformado. Teto de 2 rodadas atingido: sem terceira revisão; os estruturais (ADV2-14, ADV2-20, ADV2-29, ADV2-30) escalados ao mantenedor | 295.853 tokens · 22 min | leu só os quatro arquivos permitidos (o relatório os lista; nenhuma leitura negada). Triagem da sessão revista pelo advisor, que pediu medir cada oráculo novo contra dado real antes do `04`: palavras pt nos 827 valores en (0 com fronteira); 18 rótulos que não são peça iguais em pt e en, todos código (ADV2-11 vira lista do glossário); 4 de 74 F-nn da base com tokens legitimamente diferentes (ADV2-01 só para F-72+); 51 caminhos da base, 3 só com tenant e a F-07 en errada (ADV2-04); 116 provas em provider, 3 com as duas pontas (ADV2-09 vira D27); `getPages()` sem as páginas de autenticação nos três painéis (ADV2-10, D30); o comparativo sem cards nem passaporte (ADV2-20, P-04 emendada); `archify check` cego a `<script>` injetado sem recibo (ADV2-15, D32); `tag` do componente validado (ADV2-05, D29) |
| 10 | 7 | o analista do #5 (`SendMessage`) — quinta rodada, a última: fechar os 32 ADV2 com as decisões e as medições da sessão, com uma segunda tabela de fechamento e cada oráculo conferido por comando | opus | idem #7 | em curso | — | — |
| 11 | 1 | Codex CLI 0.163.0 (`codex exec --yolo`, pasta de trabalho no scratchpad) — tradução en dos 792 textos únicos dos diagramas do esqueleto (RQ-12) | gpt-6.1-sol (o log do Codex) | a conversa, o repositório | `traducoes-esqueleto.json`: 792 chaves | 39.089 tokens (o log do Codex) | D24: `HEAD`, tags e branch iguais antes e depois; `git status` só com as edições da própria sessão feitas durante o despacho. Conferido por script: 792 chaves, nenhuma faltando ou sobrando, 366 iguais ao pt (código), nenhum texto de até 30 caracteres cresceu; uma nota de sequência encurtada à mão depois do `render` en (`sequence/note-overlap`) |
| 12 | 1 | Codex CLI 0.163.0 — tradução en dos 35 textos do par r1 do comparativo do `/app` | gpt-6.1-sol | idem #11 | 35 chaves | 7.221 tokens | D24 idem; `git status` idêntico antes e depois; 35 chaves, nenhuma faltando, nenhuma curta que cresceu |
| 13 | 5 | `general-purpose` — catálogo único: dono exclusivo das F-72 em diante, coluna Origem, as defasagens (F-07 en), os 4 pacotes da referência e o mapa de ids provisórios → F-nn em `catalogo-mapa.json` | opus | a conversa | 124 F-nn por idioma (74 da base + F-72 a F-121, uma por id provisório da pesquisa, P-01 a P-50, nenhum descartado); coluna Origem nas 10 tabelas (67 pacote com customização, 53 código próprio, 4 pacote; as seções "Instalação e operação" e "Desenvolvimento" entraram no fim); os seis itens do README casam por palavra inteira (F-93, F-94, F-95, F-73, F-74, F-76); todo caminho do "Onde" resolve (os `/app/{org}…` só com tenant); 16 defasagens corrigidas sem trocar número (F-06, F-07 en, F-17, F-26, F-30, F-31, F-32, F-33, F-41, F-44, F-45, F-50, F-53, F-54, F-61, F-68); a referência lista os 91 pacotes dos manifestos; `catalogo-mapa.json` com o mapa de ids provisórios, a tabela README → F-nn e as listas sugeridas para a guarda | 715.214 tokens · 55 min | contagens refeitas por `grep -c` (124/50 nos dois idiomas); 4 linhas amostradas (F-73, F-76, F-93, F-102 em pt; F-76, F-93 em en) com o termo, a Origem e o link da página; `git status` só com os quatro arquivos do pedido e os diagramas dos autores do #14; testes do pedido 613/616 — a falha pré-existente do contador e os dois `[CT-22]` do link da sessão para a página do mapa, que ainda não existia. Pendências que o agente apontou fora dos seus arquivos foram devolvidas a ele na tarefa seguinte (#15) |
| 15 | 2 | o agente do #13 (`SendMessage`) — associação peça → F-nn pela R6, lista das sem feature própria pela R28 e F-nn sem peça, com a peça proposta; e as três pendências de documentação que ele achou | opus | idem #13 | em curso | — | — |
| 14 | 3 | Workflow `wf_23c4acca-a3e` — 7 autores de diagramas curados em paralelo, arquivos disjuntos (contrato em `brief-autores.md` no scratchpad): sequências transversais, sequências por painel, workflows `kit:*`, ciclos de vida, fluxos de dados, ERD e classes, linha do tempo e tempos | herdado da sessão | a conversa | 7/7 autores, 0 erro; 36 arquivos de diagrama curado (7 sequências transversais, com a pilha da requisição dividida na comum e na do `/app` com tenant; 5 sequências por painel; 3 workflows `kit:*`; 3 ciclos de vida; 2 fluxos de dados; 10 ERD; 3 de classes, a linha do tempo e 2 cascatas de tempos; o `kit-install-ci` do esqueleto apagado), todos com prova em toda ligação, textos em pt e as linhas `[mapa]` pt e en sem ERRO; o que ficou de fora por falta de prova no kit (passo feito só em `vendor/`) está listado por autor no journal do workflow. Um autor mediu um **defeito do kit**: a poda agendada da trilha de exceções não roda (`TypeError` de `getModelPruneInterval()` com `CarbonImmutable` no console, onde o painel padrão `/app` registra o plugin sem `modelPruneInterval`) — relatado ao mantenedor, não corrigido (RQ-15) | 3.131.596 tokens · 44 min | os 36 renderizados pela sessão com `gerar.mjs --saida` (todos sem ERRO); cada autor conferiu as âncoras com script próprio (primeira ocorrência na linha, fora de comentário, 12+ caracteres, fora de `vendor/`) e os limites da D5, D10 e D21; amostra da sessão: o defeito relatado conferido nos fatos citados (o `->registerNavigation(false)` do `/app` e o `->modelPruneInterval(…)` só no `/infra`) |

## Blockers

## Desvios do Plano

Do passo 1 (esqueleto andante), 2026-11-20:
- `site/mapa/pt-BR.json` virou `site/mapa/interface.json`: o arquivo guarda as 25 chaves pt e as sobrescritas
  dos tipos de peça nos DOIS idiomas (D12), e o nome `pt-BR` enganaria quem fosse mexer no en.
- `obterArchify()` é síncrona e devolve o caminho da CLI (o plano dizia `Promise<string>`): o gerador é um
  script de build, e o `execFileSync` basta.
- Sem `--quality` na linha de comando: o perfil fica em cada arquivo de diagrama (`meta.quality_profile`), que o
  archify respeita quando a opção não vem (`renderers/shared/cli.mjs`, função `svgRootAttrs`, do archify fixado).
- O gerador põe `meta.output` = `{slug}.html`: o `lifecycle` o exige no esquema; o destino da linha de
  comando prevalece.
- O `compare` exige gravar o recibo ao lado do HTML ("The compare receipt must be written beside the HTML
  artifact"); o gerador o apaga em seguida, e nenhum arquivo do archify além do HTML chega ao `dist/`.
- O par r2 do comparativo do `/app` do spike (37 e 50 peças) é recusado pelo archify fixado, sozinho e no
  `compare` (`clean-flow/endpoint-side-direction`, e com grade, `clean-flow/edge-through-node`): estoura o
  orçamento da ADR-07. O esqueleto usa o par r1 (11 e 22 peças); o resumo real do `/app` sai do passo 2, com
  layout em grade (`layout.mode: grid`, `row`/`col` e `size`, que o esqueleto mediu) e o orçamento.
- O `class` escolhido no spike (`class-nivel0`) é recusado (`layout/marker-capacity`); o esqueleto usa a
  variante `class-nivel0-membros-manual-gx170`, que valida.
- O arquivo de diagrama troca `painel?` por `paineis[]` (D28).
- O navegador do Playwright 1.63 do site não estava instalado nesta máquina (`npx playwright install chromium`
  resolveu); o CI já o instala.

## Notas de Implementação

- `search-docs` indisponível: o MCP `laravel-boost` não conectou (timeout de conexão) nesta sessão; as APIs de painel do Filament foram lidas no código instalado em `vendor/filament/filament`.
- **Baseline antes de qualquer código** (2026-11-20, branch no commit `d9608c5a` + só a wiki nova, sem código):
  `php -d memory_limit=2G vendor/bin/pest --testsuite=Kit --compact` — 5.475 testes, 5.471 passaram, 1 falhou, 3 pulados (2.757 s);
  `php -d memory_limit=2G vendor/bin/pest --testsuite=Tenancy --compact` — 814 testes, 814 passaram (539 s). A falha pré-existente, por
  nome: `Tests\Kit\SiteDeDocumentacaoTest` › *mantém os números objetivos dos READMEs sincronizados com a árvore*
  (`tests/Kit/SiteDeDocumentacaoTest.php:$especificacoes:1002`) — o README diz `**96**` features especificadas (`README.md:233`) e a pasta desta
  wiki faz a árvore contar 97 (o teste espera `**97**`). Não é regressão: o passo 7 atualiza os contadores por último.

- **Passo 1 no Linux** (2026-11-20, `mcr.microsoft.com/playwright:v1.63.0-noble`, Node 24.20, clone raso do branch
  com o `origin` trocado para o GitHub, como o `actions/checkout` deixa): no contêiner, `localhost` resolve
  primeiro para `::1` e o `wait-on` não alcançou a prévia; com `NODE_OPTIONS=--dns-result-order=ipv4first` tudo
  passou. O runner do GitHub não tem isso, e os workflows ficam como estão. O `visual-check` do archify (com
  `ARCHIFY_CHROME` apontando o Chromium da imagem e `ARCHIFY_CHROME_NO_SANDBOX=1`) passou no primeiro HTML e
  falhou em todos os seguintes com "visual-check could not complete its Chrome inspection" — falha de execução
  do Chrome, não de leitura; o `minimumProjectedNodeTextPx: 6` que ele imprime é o limite dele, não uma medida.
- **Tamanho**: 26 HTML do mapa = 26 MB no `dist/` (≈ 0,9 MB cada, o runtime do archify embutido). A matriz
  inteira, perto de 70 diagramas × 2 idiomas, dá ≈ 130 MB — abaixo do limite de 1 GB do site publicado do GitHub
  Pages.

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-11-20
- `entrevista-tres-raias.md` — step 4 — 2026-11-20
- `template-01-plano.md` — step 4 — 2026-11-20
- `template-02-adr.md` — step 4 — 2026-11-20
- `template-03-progresso.md` — step 4 — 2026-11-20
- `pesquisa-step-3.md` — step 3 — 2026-11-20
- `entrevista-tres-raias.md` — step 7 (raias e bloqueio das perguntas da derivação) — 2026-11-20
- `delegacao-casos-de-teste.md` — step 7 (costuras como proposta, revisão adversarial) — 2026-11-20
- `template-03-progresso.md` — step 7 (`## Testes`) e step 8 (`## Tickets`) — 2026-11-20
- `scripts/rastreabilidade.sh`, `scripts/citacoes.sh` e `scripts/ids-ct.sh` (cabeçalho e lógica) — step 7 — 2026-11-20
- `feature-test-design/SKILL.md`, "O que fazer com os achados" (re-revisar uma única vez quando o fechamento cria cenário novo; teto de 2 rodadas) — step 7 — 2026-11-20

## Retrospectiva
