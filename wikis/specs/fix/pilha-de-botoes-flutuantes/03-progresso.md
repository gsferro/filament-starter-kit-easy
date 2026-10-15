# Progresso — Pilha harmônica dos botões flutuantes

**Estado**: em implementação — código e testes em `8f41638`; revisão do diff (step 9) em curso

## 1. Script da pilha
- [x] script da pilha na blade do "Voltar ao topo" — `8f41638`

## 2. "Voltar ao topo" na pilha
- [x] blade e `voltar-ao-topo.css` — `8f41638`

## 3. Chat da IA estilizado e na pilha
- [x] blade, `assistente-chat.css` e registro — `8f41638`

## 4. Documentação
- [x] docs pt/en e CHANGELOG — `bb3f1f8`

## 5. Release v0.48.1
- [ ] versão, CHANGELOG, tag e release

## 6. Dock oculto na tela de bloqueio e nas telas de autenticação
- [x] `App\Support\TelaDeAutenticacao` no `visible()` do dock e no render hook do chat — `8f41638`

## 7. Organização e tooltip nos favoritos
- [x] grupos por organização e tooltip com o caminho — `8f41638`

## Testes
- [x] `tests/Kit/AssistenteChatCssTest.php` (CT-01, CT-02, CT-03) — 6/6
- [x] `tests/Kit/TelasSemDockNemChatTest.php` (CT-04, CT-09, CT-11) — 15/15
- [x] `tests/Kit/DockSemOrganizacaoTest.php` (CT-08) — 1/1
- [x] `tests/Tenancy/DockPorOrganizacaoTest.php` (CT-05, CT-06, CT-07) — 13/13
- [x] `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` (CT-10) — 2/2
- [x] `tests/Browser/PilhaDeBotoesFlutuantesTest.php` (CT-B01 a CT-B08, CT-B11) — 23/23
- [x] `tests/Browser/AssistenteChatEstiloTest.php` (CT-B09, CT-B10) — 4/4
- [x] regressão do chat e do dock — `AssistenteChatWidgetTest` 17/17, `DockDeAtalhosTest` 129/129, `DockDeAtalhosTenancyTest` 44/44

Revisão adversarial: dois ciclos (`fw-adversario-ct`), teto atingido — ADV-01…ADV-35, ver `04`. Disparada porque os Adendos 4–6 trouxeram área de Impacto 3 (telas sem dock nem chat).

## Tickets

Não fatiado — 2026-10-14: 18 RQ vigentes (RQ-01 a RQ-18, cláusulas finas de layout: eixo, 16 px, contraste), 14 CT (3 + 11 CT-B), compactação: não, 10 perguntas de requisito — sinal de tamanho tocado pela granularidade, não pelo escopo; cabe numa sessão.

## Verificação Final
- [x] `vendor/bin/pint --dirty` — passed; PHPStan 0 erros; filacheck 17 regras ok
- [x] testes da feature e CT-B — ver `## Testes`
- [x] `/code-review high main...HEAD` + passe de eixos (step 9) — 4 achados baixos: 2 corrigidos, 2 rejeitados com motivo
- [x] `rastreabilidade.sh {wiki}` silencioso — exit 0
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [x] `citacoes.sh {wiki}` silencioso — exit 0, após corrigir 10 citações de linha desatualizadas
- [x] `ids-ct.sh` silencioso — exit 0 com os 7 arquivos de teste da feature
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [x] Docs pt/en, CHANGELOG e README reconciliados — README sem menção ao dock; `bb3f1f8`
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 / CR-3 | eixos + code-review | mais de 10 organizações distintas nos favoritos do `/app` → os da 11ª em diante somem (grupos-slot = `MAXIMO_DE_ATALHOS`) | rejeitado | — | toda gravação (`DockDeAtalhos::adicionarPaginaAtual`, `salvarAtalhos`) respeita o teto de 10 desde a v0.48.0; só dado editado à mão na coluna chega lá, e só esconde favoritos do próprio usuário |
| RD-02 | eixos | navegação SPA (`livewire:navigated`) troca o `<body>` e o observador fica no antigo | corrigido | — (o kit não liga `->spa()`; sem CT) | — |
| CR-1 | code-review | as linhas condicionais do CT-04 que redirecionam não renderizam a tela | rejeitado | CT-04 | desenho do ADV-35, confirmado pela sessão em 2026-10-15: com sessão, a tela redireciona e o teste afirma o destino |
| CR-2 | code-review | o script media em px fixos e as folhas em rem: com fonte padrão ≠ 16 px a faixa da pilha erra | corrigido | — (CT-B01…B11 verdes após a correção) | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

## Candidatos a Rule

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-14 — Guilherme Ferro (respostas às Q1–Q4, Q5–Q9 e Q12, Adendos 1 a 3) — 3 rodadas; perguntas: 0 fato, 2 desenho (Q10, Q11, decididas pela sessão), 10 requisito

### Teste controlado (RQ-02)

Sessão real do `admin@example.com` no servidor local (`php artisan serve`, porta 8000), Chromium headless do Playwright, janela de 1366×768; medidas por `getBoundingClientRect()`.

| Cenário (página rolada) | Dock (trigger) | "Voltar ao topo" | Chat | Sobreposição |
|---|---|---|---|---|
| `/admin/users`, dock no canto inferior direito | 56 px, 24 px da direita, 24 px do rodapé | 44 px, 24 px da direita, 24 px do rodapé | — | dock × "Voltar ao topo": sim |
| `/admin/users`, dock no canto inferior esquerdo | 24 px da esquerda | 44 px, 24 px da direita, 24 px do rodapé | — | não |
| `/app/padrao` | desligado no `/app` nesta instalação | 44 px, 24 px da direita, 96 px do rodapé | `position: static`, 39×39 px, no fim do `<body>`, abaixo do rodapé | o chat não aparece |

### Confronto código × afirmação (step 5)

| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| o script entra como `Js::make` em `configureCorrecoesDeCss()` | `KitUpdate::CAMINHOS_DO_KIT` entrega `public/css/kit` e `resources/css/filament`, e não tem `public/js/kit` nem `resources/js/filament` (`app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93`) | D1 e passo 1 do `01`: o script vai na blade do "Voltar ao topo", que `resources/views/filament` já entrega |
| o "Voltar ao topo" é renderizado em toda tela dos painéis | render hook `BODY_END` sem escopo (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraBotaoVoltarAoTopo:157`) | nenhuma |
| a folha do chat entra pelas duas rotas de entrega | `resources/css/filament` e `public/css/kit` estão em `CAMINHOS_DO_KIT`; a publicada é versionada (`public/css/kit/kit-voltar-ao-topo.css`) | o passo 3 publica `public/css/kit/kit-assistente-chat.css` por `php artisan filament:assets` |

### Varredura da classe irmã (step 5)

Irmã da folha nova do chat: `resources/css/filament/voltar-ao-topo.css` (`grep -rnF "voltar-ao-topo.css"` em `app config database tests composer.json .gitattributes docs`). Ocorrências: o registro em `app/Providers/KitServiceProvider.php:'kit-voltar-ao-topo':748`, a guarda `tests/Kit/VoltarAoTopoCssTest.php` e a publicada `public/css/kit/kit-voltar-ao-topo.css`. A folha do chat precisa das três: registro, guarda por HTML renderizado e publicada versionada. Nenhuma outra lista paralela.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | duas variáveis (`--kit-pilha-chat`, `--kit-pilha-topo`) e o script detectando o chat → uma só, `--kit-pilha-base`, e o CSS soma pelo painel (`bottom-24` × `bottom-6`) | sim | `01`, D1 e passos 1, 2 e 3 |
| 2 | `02` | nada a cortar | — |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 7 | `general-purpose` como analista — derivação do `04`/`05` pela `feature-test-design` | opus | conversa, implementação, `01` além de paths/rotas/Superfície de UI | `04` e `05` como texto, 7 perguntas (5 requisito, 2 desenho) | 209.866 tokens · 700 s | `04`/`05` gravados verbatim por extração dos delimitadores; amostra: CT-B01 e CT-03 conferidos contra o `00` |
| 2 | 7 | o mesmo agente, retomado — re-derivação com o Adendo 2 | opus | idem | `04`/`05` completos, 1 pergunta (Q12) | 245.768 tokens · 259 s | gravados verbatim; contagem de mutantes conferida (47 + M48 da sessão) |
| 3 | 6 | `ponytail-review` sobre `01`/`02` | o da sessão | — | 1 corte (uma variável CSS) | — | aplicado no `01` |
| 4 | impl. | Codex (`codex exec --yolo`) como `fw-executor-ct` + `fw-executor-ctb`, sob os contratos dos agentes | não exposto pelo host | `01`, `02`, `03`, conversa | 3 arquivos novos de teste + `conversaPara()` movido para `tests/Pest.php`; Kit 23/23; browser pilha 10 ✓ 9 ✗ (8 (b) folga direita 30 × 24 px, 1 (a) CT-B11 medidor da barra), estilo do chat 4/4 | — | hashes antes/depois dos arquivos fora de `tests/` iguais (as 3 diferenças são edições da sessão na wiki); 2 correções de especificação devolvidas como texto e repassadas à derivação |
| 5 | 7 | o analista do #1, retomado — re-derivações com P-03 e os Adendos 3 a 8 (rodadas 2–6) | opus | idem #1 | `04`/`05` com CT-01…CT-11 e CT-B01…CT-B11; Q13 (desenho, sessão) e Q19 (requisito, Adendo 8) | — | gravados verbatim; contagem pelos `grep -c` |
| 6 | 7 | `fw-adversario-ct`, ciclo 1 | do agente | PRD, código, raciocínio da derivação | ADV-01…ADV-26 | — | aceitos exceto ADV-04, ADV-14 e ADV-25 (lacuna declarada); fechados pelo analista |
| 7 | 7 | `fw-adversario-ct`, ciclo 2 (teto) | do agente | idem | ADV-27…ADV-35, todos aceitos | — | incorporados pelo analista; divergência do ADV-35 confirmada pela sessão |
| 8 | impl. | Codex, lote 2 — testes dos Adendos 4–8 sob o `04` do ciclo 1 | não exposto pelo host | `01`, `02`, `03`, conversa | 7 arquivos novos; 248/251; 3 (b) | — | hashes fora de `tests/` iguais; os 3 (b) eram especificação: CT-04 convite (302 → `/app`) e CT-06 origem/limite (conferência de acesso ao favorito da Globex, já existente) — sessão corrigiu `04` e teste |
| 10 | 9 | `fw-revisor-diff` — passe de eixos | do agente | PRD, `00`/`01`/`03`/`04`/`05`, conversa | RD-01, RD-02 (Minor) | 90.773 tokens · 309 s | `git status` antes/depois: só o `03` mudou, edição da sessão; amostra: RD-02 conferido na blade |
| 11 | 9 | `general-purpose` como `/code-review` high | opus | wiki, conversa | CR-1 (confirmado), CR-2, CR-3 (plausíveis) | 159.217 tokens · 361 s | CR-1 conferido pela saída do CT-04; CR-2 conferido nas constantes do script |
| 9 | impl. | Codex, lote 3 — achados do ciclo 2 (CT-B08 e/f, CT-B06 Tab e interseção, CT-03 seletor exato, CT-05 conjunto exato) | não exposto pelo host | idem | 1 vermelho (CT-05 f) classificado (b) | — | hashes fora de `tests/`: só as 6 edições da sessão (wiki, docs, CHANGELOG); CT-05 f reclassificado (a) pela sessão — a página aberta já é favorita e o dock esconde "Adicionar esta página"; teste corrigido |

## Blockers

## Desvios do Plano

- CT-04 (2026-10-15): a linha do convite passou a condicional (ADV-35). Com sessão, `filament.app.auth.register` redireciona para `/app`. Destinos registrados: login do `/admin` → `/admin`; login unificado e recuperação → `/login/painel`; convite → `/app`; verificação de e-mail → 200.
- CT-06 (2026-10-15): a linha origem proibia toda consulta a `tenants`/`tenant_user` pelo código do dock, mas a conferência de acesso ao favorito de outra organização (R22 do `dock-de-acoes-rapidas`) já fazia duas. O oráculo passou a: toda consulta dessas traz o id da Globex, e há ao menos uma. A linha limite usa só favoritos da organização corrente (+4).
- CT-07 (2026-10-15): o lote 2 não trazia a linha "Dashboard da Globex" (ADV-27) nem a igualdade exata do `x-tooltip` decodificado (ADV-34); a sessão acrescentou. Mutante "tooltip = URL completa" morto (5 vermelhos).
- P-01 → P-03 (2026-10-14): o executor dos CT-B classificou como (b) a folga direita de 30 px do "Voltar ao topo" contra os 24 px de "posições de hoje". A sessão decidiu que vale o eixo comum também fora da pilha (o botão não salta de lado quando o dock chega); a P-01 foi substituída e o `04`/`05` re-derivados.

## Notas de Implementação

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-14
- `template-01-plano.md` — step 4 — 2026-10-14
- `template-02-adr.md` — step 4 — 2026-10-14
- `template-03-progresso.md` — step 4 — 2026-10-14
- `delegacao-casos-de-teste.md` — step 7 — 2026-10-14
- `roteamento-e-despacho.md` — step 7 — 2026-10-14
- `citacoes-de-codigo.md` — step 10 — 2026-10-15

## Retrospectiva
