# Progresso — Pilha harmônica dos botões flutuantes

**Estado**: em planejamento

## 1. Script da pilha
- [ ] script da pilha na blade do "Voltar ao topo"

## 2. "Voltar ao topo" na pilha
- [ ] blade e `voltar-ao-topo.css`

## 3. Chat da IA estilizado e na pilha
- [ ] blade, `assistente-chat.css` e registro

## 4. Documentação
- [ ] docs pt/en e CHANGELOG

## 5. Release v0.48.1
- [ ] versão, CHANGELOG, tag e release

## 6. Dock oculto na tela de bloqueio e nas telas de autenticação
- [ ] regra em `PluginDoDock::make()`: layout do Auth Designer ou rota `lockscreen.*`

## 7. Organização e tooltip nos favoritos
- [ ] grupos por organização e tooltip com o caminho

## Testes
- [ ] `tests/Kit/AssistenteChatCssTest.php` (CT-01, CT-02, CT-03)
- [ ] `tests/Browser/PilhaDeBotoesFlutuantesTest.php` (CT-B01 a CT-B08, CT-B11)
- [ ] `tests/Browser/AssistenteChatEstiloTest.php` (CT-B09, CT-B10)

Revisão adversarial: não disparada — nenhuma área com perfil completo nem Impacto 3 (`04`, Perfil de Derivação). Costuras confirmadas pela sessão em 2026-10-14.

## Tickets

Não fatiado — 2026-10-14: 18 RQ vigentes (RQ-01 a RQ-18, cláusulas finas de layout: eixo, 16 px, contraste), 14 CT (3 + 11 CT-B), compactação: não, 10 perguntas de requisito — sinal de tamanho tocado pela granularidade, não pelo escopo; cabe numa sessão.

## Verificação Final
- [ ] `vendor/bin/pint --dirty`
- [ ] testes da feature e CT-B
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

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

## Blockers

## Desvios do Plano

- P-01 → P-03 (2026-10-14): o executor dos CT-B classificou como (b) a folga direita de 30 px do "Voltar ao topo" contra os 24 px de "posições de hoje". A sessão decidiu que vale o eixo comum também fora da pilha (o botão não salta de lado quando o dock chega); a P-01 foi substituída e o `04`/`05` re-derivados.

## Notas de Implementação

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-14
- `template-01-plano.md` — step 4 — 2026-10-14
- `template-02-adr.md` — step 4 — 2026-10-14
- `template-03-progresso.md` — step 4 — 2026-10-14
- `delegacao-casos-de-teste.md` — step 7 — 2026-10-14
- `roteamento-e-despacho.md` — step 7 — 2026-10-14

## Retrospectiva
