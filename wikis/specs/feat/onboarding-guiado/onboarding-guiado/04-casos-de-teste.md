# Casos de Teste — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` (Texto Original + Adendos 1, 2, 3 + P-01..P-28) · Plano: `01-plano-acao.md` · Superfície Livewire: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (a feature não existe). O `vendor/` e o `app/` foram lidos só para nomes (classes, rotas, colunas) e para saber se um efeito **tem destinatário** no caminho feliz.
> Revisão adversarial: concluída — 38 achados, todos fechados (2026-10-21); 2ª e última rodada: 16 achados (ADV2-01..ADV2-16), todos fechados (2026-10-21), ver `## Fechamento da 2ª rodada adversarial`. Fechamento: 17 cenários novos (CT-43…CT-59), 1 CT-B novo (CT-B02), linhas e asserções novas em 16 cenários existentes, 1 oráculo invertido corrigido (CT-11, ADV-21); mutantes da revisão e das premissas novas M110…M176 não contam para o teto por regra (SKILL.md, passo 6, item 1). Detalhe em `## Fechamento da revisão adversarial`.
> Perguntas da derivação renumeradas pela sessão: Q?1–Q?7 → Q11–Q17 (premissas P-04..P-10, tratadas como decididas); Q?8–Q?10 → Q18–Q20 (desenho); Q?11–Q?16 da revisão → Q21–Q26 (P-11..P-16); ADV-12 → P-17.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — Interruptor (fábrica, `.env`, migration, banco → config, tela) | 2 | 2 | 4 | padrão |
| B — Desligado (autoria, 404 com permissão, checklist, página, widget, ação com a tela aberta) | 3 | 3 | 9 | completo |
| C — Ligado (checklist, página e tours nos três painéis; superfície do checklist) | 2 | 2 | 4 | padrão |
| D — Semeadura (pai, filhos, condições, visibilidade, re-semear, idiomas, multi-organização) | 3 | 3 | 9 | completo |
| E — `kit:install` | 2 | 2 | 4 | padrão |
| F — Rule, teste de inventário e rotas de entrega | 1 | 2 | 2 | mínimo |
| G — Documentação | 1 | 1 | 1 | mínimo |

- Probabilidade 3 em B: a decisão atravessa três painéis, duas telas de autoria com quatro e três rotas, o render hook de toda tela, a página do pacote, um widget e as ações Livewire de um componente de vendor já montado. Impacto 3 em B: RQ-16 e RQ-30 são cláusulas de autorização ("404 mesmo com permissão"; "nada é gravado")
- Probabilidade 3 em D: 27 seeders filhos, condições em dois mecanismos, sete decisores de visibilidade, multi-organização e re-semeadura sobre dado existente. Impacto 3 em D: RQ-25 — sobrescrever o texto que o administrador editou é perda irreversível de autoria
- Técnicas aplicadas: EP, BVA 2-valores (contagem de convites 0/1), tabela de decisão (semente × banco; modo × resposta do `kit:install`), matriz papel × ação, tabela estado × operação (uma, 20 células), rastreio de efeito (direção "não aconteceu" com destinatário real e controle positivo), 2-switch, idempotência ancorada no agregado persistido
- Técnica escalada: R16 (área D) usa **2-switch** além de EP, porque o defeito plausível é o progresso do passo escondido sumir ou contar no numerador, que só aparece ao religar a feature; e **BVA** na borda de 100 % (CT-56, ADV-07)
- Quality gate, ciclo 2 (2026-10-21): QA-08 → R33/CT-72; QA-09 → R34/CT-73, CT-74 (P-25); QA-11 → R35/CT-75 (P-26); QA-12 → CT-16 re-derivado (linha `/app` com 5 passos, P-22; oráculo pela chave da jornada). Ver `## Fechamento do quality gate, ciclo 2`
- Quality gate, ciclo 3 (2026-10-22): QA-15 → P-27 → R36/CT-B03 (`05`); QA-16 → P-28 → R37/CT-B04 (`05`); QA-17 → CT-75 reescrito (mesmo ID: linhas físicas depois da sentinela; linha CR; M208–M210). Ver `## Fechamento do quality gate, ciclo 3`
- Quality gate, ciclo 4 (2026-10-22): QA-19 → R36/CT-B03 linhas 5–8 (`05`: partição do estado do rótulo de status — persona com passo concluído, `/admin` e `/infra` × claro e escuro; MB17–MB19); QA-20 → R37/CT-B04 linha 3 (`05`: duas teclas Esc seguidas, painel ausente depois de assentar; MB20–MB21). Ver `## Fechamento do quality gate, ciclo 4` *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*
- `Quando` com sequência (CT-04, CT-12, CT-28, CT-51, CT-58, CT-61, CT-65, CT-74): evento único declarado — é o **2-switch** (o oráculo é o destino do segundo evento, que não existe sem o primeiro); ADV-27..29
- Cenários: 75 · Regras: 37 · Mutantes previstos: 207 · Sem matador: 0
- No `05` (mesmos `grep -c`, com `CT-B` e `MB`): CT-B: 4 · Mutantes MB: 21 · Sem matador: 0 *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho), recalculado em 2026-10-22 (quality gate, ciclo 4). Os CT-B01..CT-B04 e seus mutantes (MB1–MB21) estão no 05; as regras R36 e R37 têm seção no 04 e cenário no 05. IDs M22 e M69 fundidos em M20 e M56 (teto de 6 por regra no perfil completo); M109 acrescentado à R14. -->
- Lacunas declaradas (não são mutantes sem matador): o recorte de RQ-13 "alterar tela existente" e as linhas da taxonomia marcadas "lacuna declarada"

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | propriedade bool nova no settings do kit, chave de config e linha no `.env.example`, migration de settings, seção nova na tela de configurações; plugin do pacote nos três painéis; condições (no banco e em código); seeder pai + 27 filhos; rule em `.ai/rules`; teste de inventário; documentação pt/en | CT-01, CT-02, CT-05, CT-06, CT-20, CT-21, CT-23, CT-39, CT-40, CT-42 |
| F | ligar/desligar o tutorial; esconder autoria de toda navegação; 404 na autoria com e sem permissão; esconder checklist, tours, boas-vindas, item de menu e widget; recusar ação do checklist já montado; semear jornada por painel; concluir passo por condição, visita ou tour; esconder passo de feature desligada; preservar edição ao re-semear | CT-03…CT-19, CT-22…CT-31 |
| D | bool do interruptor; jornadas, passos, paradas de tour, condições (texto pt_BR/en em JSON); progresso por pessoa (e por organização no `/app`); cardinalidade de passos por painel (11/6/15 sem multi-organização; 12/11/15 com); dado já existente que não pode sumir nem ser sobrescrito (progresso, texto editado) | CT-12, CT-20, CT-28, CT-29, CT-30, CT-31, CT-32, CT-34, CT-35 |
| I | rotas de autoria `/admin/onboarding-flows` (lista, criar, ver, editar) e `/admin/onboarding-conditions` (lista, criar, editar); `/{painel}/onboarding`; render hook do checklist em toda tela dos três painéis; métodos `$wire.` do checklist e ações da página de progresso; formulário da SettingsPage; `db:seed --class`; `kit:install` (pergunta e `--onboarding`); barra lateral, hub, ⌘K e dock | CT-07, CT-08, CT-09, CT-11, CT-13, CT-15, CT-16, CT-18, CT-38 |
| P | painéis `admin`, `app` (com e sem organização na rota) e `infra`; suíte `tests/Tenancy` para multi-organização; navegador para o tour (Alpine); `.env` local do desenvolvedor que a suíte lê (Q19) | CT-15, CT-34…CT-37, CT-B01 (05) |
| O | instalação nova (interativa e não interativa); projeto que liga multi-organização depois; administrador com e sem permissão; tela aberta quando o tutorial é desligado; duas pessoas no mesmo painel; administrador que edita o que o seeder criou e depois roda o seeder de novo; agente que cria tela nova | CT-08, CT-13, CT-19, CT-30, CT-35, CT-38, CT-40 |
| T | decisão por request (vale no request seguinte, sem deploy); ordem env → banco; ciclos desligar → religar (tutorial, feature do passo, multi-organização); troca de estado entre o `mount` do checklist e a ação | CT-03, CT-04, CT-12, CT-13, CT-28, CT-35 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — A instalação nova nasce com o tutorial desligado | A (padrão) | RQ-10, RQ-11 | EP (partições do ambiente) | CT-01, CT-02 |
| R2 — O banco vence o `.env` e a decisão vale no request seguinte, sem deploy | A + B (completo — atravessa) | RQ-10, RQ-12, RQ-31 | tabela de decisão (semente × gravado), 2-switch | CT-03, CT-04 |
| R3 — A tela de configurações grava o interruptor, e o settings do tutorial é só o interruptor; o que ela grava vale no request seguinte | A (padrão) | RQ-10, RQ-22, RQ-12, RQ-31 | EP, gate de tela de escrita, 2-switch | CT-05, CT-06, CT-58 |
| R4 — Desligado, a autoria some de toda superfície de navegação do `/admin` | B (completo) | RQ-15, RQ-12 | EP por superfície | CT-07 |
| R5 — Desligado, as rotas de autoria respondem 404 com e sem permissão, inclusive na tela já aberta; ligado, a permissão decide; o `/admin` continua abrindo | B (completo) | RQ-16, P-05 | matriz papel × ação, estado × evento, saída do erro | CT-08, CT-09, CT-10, CT-46 |
| R6 — Desligado, checklist, tours, boas-vindas e "Primeiros passos" (menu, hub, ⌘K, dock e URL) não aparecem em painel nenhum | B (completo) | RQ-17, RQ-12, RQ-29, P-04 | EP por painel e por superfície, controle positivo | CT-11, CT-44, CT-45 |
| R7 — Desligado, o widget de progresso some do dashboard e volta ao religar, sem perder dado | B (completo) | P-02 | 2-switch | CT-12 |
| R8 — Desligado depois de a tela abrir o checklist, toda ação dele responde 404 e nada é gravado, nos três painéis | B (completo) | RQ-30 | tabela estado × evento (mount ligado → desliga → ação), rastreio de efeito com controle, EP por painel | CT-13, CT-14, CT-43, CT-63 |
| R9 — Ligado, o checklist, o item "Primeiros passos" e a página aparecem nos três painéis com a jornada do painel para quem tem a permissão da página, e a boas-vindas não | C (padrão) | RQ-28, RQ-18, RQ-22, RQ-29, P-06, P-18, P-22 (CT-16, linha `/app`) | EP por painel, matriz papel × painel | CT-15, CT-16, CT-47, CT-62, CT-B01, CT-B02 (05) |
| R10 — Todo passo semeado tem tour, e cada parada aponta para uma tela existente do painel da jornada | C (padrão) | RQ-18, P-03 | EP (todos os passos) | CT-17 |
| R11 — Ligado, as ações do checklist só alcançam as jornadas visíveis da própria pessoa no painel corrente; cada painel tem o seu progresso | C (padrão) | RQ-28, RQ-18, P-14 | EP (fora do domínio, tipo), identidade, EP por painel | CT-18, CT-19, CT-48 |
| R12 — O seeder pai cria uma jornada por painel com os passos da tabela aprovada, no modo de conclusão dela, com textos que cabem no schema | D (completo) | RQ-02, RQ-04, RQ-18, RQ-23, RQ-09 | EP por painel, contagem por modo, BVA (comprimento da coluna) | CT-20, CT-59 |
| R13 — Cada feature é um seeder filho próprio, que sozinho cria só o seu passo | D (completo) | RQ-05 | EP por filho | CT-21 |
| R14 — Semear não toca o interruptor | D (completo) | RQ-21 | EP (estado prévio) | CT-22 |
| R15 — As condições de que os passos dependem existem depois de semear e decidem a conclusão; a visita só conclui com o tutorial ligado | D (completo) | RQ-03, RQ-23, P-07 | EP + BVA 2-valores (0/1 convite), EP por estado do convite, identidade, painel | CT-23, CT-24, CT-25, CT-26 |
| R16 — Passo de feature desligada não aparece nem conta no percentual; volta ao religar, com o progresso | D (completo) | P-03, P-13, P-19, RQ-23 | EP por decisor × painel, 2-switch, BVA (borda de 100 %) | CT-27, CT-28, CT-56, CT-60, CT-61 |
| R17 — Re-semear cria só o que falta e nunca sobrescreve | D (completo) | RQ-25 | idempotência no agregado, EP por tipo de edição e de ausência | CT-29, CT-30, CT-31 |
| R18 — Todo texto semeado existe em pt_BR e en, e a autoria grava os dois | D (completo) | P-01 | EP, gate de tela de escrita | CT-32, CT-33 |
| R19 — Com multi-organização, os passos só de organização são semeados e o consumo funciona no `/app/{organização}` | D (completo) | RQ-09, RQ-24, RQ-25, RQ-28, P-03, P-06, P-08 | EP, 2-switch | CT-34, CT-35, CT-36, CT-37 |
| R20 — O `kit:install` semeia as jornadas só por escolha, sem ligar o interruptor | E (padrão) | RQ-06, RQ-19, RQ-20, RQ-21, P-10 | tabela de decisão (modo × resposta) | CT-38, CT-55 |
| R21 — A rule de manutenção existe, carrega pelos caminhos das telas e do código das features base, e manda revisar o seeder filho | F (mínimo) | RQ-26, RQ-13, RQ-14, P-15 | EP | CT-39 |
| R22 — O teste de inventário fica vermelho com tela sem seeder nem isenção | F (mínimo) | RQ-27, RQ-14, RQ-01 | EP (uma situação defeituosa por linha) | CT-40, CT-41 |
| R23 — A documentação pt e en ensina a semear (e a ligar o tutorial depois) e diz o que cada seeder popula, de quais telas | G (mínimo) | RQ-07, RQ-08, RQ-01, RQ-14, P-16 | EP por idioma | CT-42 |
| R24 — Com multi-organização, o progresso e a condição de convite do `/app` são por organização | D (completo) | P-08, P-09, RQ-24 | EP (organização da ação × organização observada) | CT-49, CT-50 |
| R25 — Passos de organização só existem e só aparecem com a multi-organização ligada | D (completo) | P-12, P-17, RQ-09, RQ-24 | 2-switch, EP | CT-51, CT-52, CT-64 |
| R26 — Passo cuja tela a pessoa não pode abrir não aparece nem conta no percentual | D (completo) | P-11, RQ-23 | matriz papel × tela, EP por painel | CT-53, CT-54, CT-65 |
| R27 — Os arquivos da feature viajam pelas duas rotas de entrega do kit | F (mínimo) | RQ-04, RQ-06, RQ-26 (o que a instalação precisa ter para semear e para o agente ler a rule) | EP por arquivo × rota | CT-57 |
| R28 — Escrever a chave de outra jornada direto no checklist não troca a jornada | C (padrão) | P-20, RQ-28 | EP | CT-66 |
| R29 — A falha da semeadura do tutorial não interrompe o `kit:install` | E (padrão) | P-21, RQ-06, RQ-19, RQ-20 | rastreio de efeito | CT-67 |
| R30 — O passo do dashboard dinâmico exige poder montar o dashboard dinâmico do painel | D (completo) | P-22, P-03, P-11 | tabela de decisão | CT-68 |
| R31 — Nenhum passo semeado fica escondido de todo mundo | D (completo) | P-23, RQ-02, P-11 | EP por painel | CT-69 |
| R32 — O canal de log do tutorial existe no projeto atualizado, e a configuração de log do projeto não é sobrescrita | F (mínimo) | P-24, RQ-02 | tabela de decisão (escalada), EP | CT-70, CT-71 |
| R33 — Ligado, a página "Primeiros passos" abre com passo concluído e o mostra concluído, nos três painéis e nos três modos de conclusão | C (padrão) | RQ-28, P-06, P-22 | EP modo de conclusão × painel (each choice; visita só existe no `/admin`) | CT-72 |
| R34 — Ligado, o custo de abrir uma tela não cresce com os passos que ensinam a mesma tela, e a decisão de acesso vale só para o request | C (padrão) | P-25, P-11 | cardinalidade com controle (mesmos passos extras, com e sem a condição), 2-switch | CT-73, CT-74 |
| R35 — Um valor recusado vindo do navegador não forja linha no log do tutorial | C (padrão) | P-26, P-20 | EP forma da quebra (LF, CRLF, CR) × ponto de entrada, rastreio de efeito (linhas físicas: "exatamente uma" / "no máximo uma") | CT-75 |
| R36 — Os botões e os rótulos de status do checklist aberto e da página "Primeiros passos" passam o axe no nível serious, nos temas claro e escuro | C (padrão) | P-27, RQ-28 | EP painel (tema do kit × tema próprio) × tema × estado do rótulo de status (sem passo concluído × com passo concluído), controle positivo (checklist visível, tema efetivo, rótulo "Concluída" visível e não coberto) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | CT-B03 (05) |
| R37 — O checklist aberto fecha com a tecla Esc, sem gravar nada | C (padrão) | P-28, RQ-28 | EP por foco (botão que abriu × dentro do painel) e por repetição (uma tecla × duas seguidas, sem esperar a resposta), rastreio de efeito ("não aconteceu", com destinatário) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | CT-B04 (05) |

Cobertura por cláusula (toda `RQ` e toda `P-nn` vigente):

- RQ-01 — CT-40 (toda tela registrada tem passo ou isenção com motivo), CT-42 (a decisão registrada na documentação)
- RQ-02 — CT-20 · RQ-03 — CT-23…CT-26 · RQ-04 — CT-20 · RQ-05 — CT-21 · RQ-06 — CT-38 · RQ-07, RQ-08 — CT-42
- RQ-09 — CT-20 (desligado), CT-34 (ligado) · RQ-10 — CT-01…CT-05 · RQ-11 — CT-01, CT-02 · RQ-12 — CT-03, CT-07, CT-11
- RQ-13 — **cobertura parcial**: CT-39 (a rule carrega pelos caminhos das telas) e CT-40 (tela nova sem seeder fica vermelha); recorte "alterar tela existente dispara o alerta" → lacuna declarada: o alerta é a rule lida pelo agente, comportamento do agente e não do sistema; nenhum teste observa uma edição de arquivo existente. Tentado: inventário por hash do conteúdo da tela — descartado, mudaria a cada refatoração sem relação com o tutorial
- RQ-14 — CT-39, CT-40 (filho fora do pai), CT-42 (filho sem linha na documentação)
- RQ-15 — CT-07 · RQ-16 — CT-08, CT-09, CT-10, CT-46 (tela já aberta, P-05)
- RQ-17 — CT-11, CT-13, CT-43 (o tour não inicia), CT-44, CT-45 · RQ-18 — CT-15, CT-17, CT-20 · RQ-19, RQ-20 — CT-38, CT-55
- RQ-21 — CT-22, CT-38 · RQ-22 — CT-06 · RQ-23 — CT-20, CT-27, CT-34 (modos dos passos de organização, P-08)
- RQ-24 — CT-34, CT-35, CT-36, CT-49…CT-52 · RQ-25 — CT-29, CT-30, CT-31, CT-35 · RQ-26 — CT-39, CT-57 · RQ-27 — CT-40, CT-41
- RQ-28 — CT-15, CT-16, CT-37, CT-47, CT-48, CT-72 (página com passo concluído, três painéis × três modos), CT-B01, CT-B02
- RQ-29 — CT-11 (desligado), CT-15 (ligado) · RQ-30 — CT-13, CT-14, CT-43, CT-26 (linha desligado, P-07) · RQ-31 — CT-04, CT-58
- RQ-12 — CT-03, CT-07, CT-11, CT-44, CT-45 (URL da página, P-04)
- P-01 — CT-32, CT-33, CT-59 · P-02 — CT-12, CT-56 · P-03 — CT-27, CT-28, CT-36, CT-17 (tour não aponta tela ausente)
- P-04 — CT-45 · P-05 — CT-46 · P-06 — CT-15, CT-47, CT-37 (linha `admin_app`) · P-07 — CT-26 (linha desligado) · P-08 — CT-34, CT-49 · P-09 — CT-50 · P-10 — CT-38 (linha `--no-seed`)
- P-11 — CT-53, CT-54, CT-65 · P-12 — CT-51, CT-64 · P-13 — CT-27 (linha "Execuções de IA") · P-14 — CT-48 · P-15 — CT-39 · P-16 — CT-42 · P-17 — CT-52 · P-18 — CT-62 · P-19 — CT-61 · P-20 — CT-66 · P-21 — CT-67 · P-22 — CT-68, CT-16 (linha `/app`: 5 passos) · P-23 — CT-69 · P-24 — CT-70, CT-71
- P-25 — CT-73 (consultas não crescem com passos da mesma tela), CT-74 (a decisão não atravessa o request) · P-26 — CT-75
- P-27 — CT-B03 (`05`: página e checklist aberto, `/admin` e `/infra` × claro e escuro × sem e com passo concluído — os três estados do rótulo de status) · P-28 — CT-B04 (`05`: foco no botão que abriu e dentro do painel; uma tecla e duas seguidas) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*
- Nenhuma `RQ` está com Estado `aberta` no `00`; Q1–Q8 e Q10 estão respondidas; Q11–Q17 e Q21–Q28 estão abertas **com premissa vigente** (P-04..P-16, P-18, P-19), tratada como decidida pela sessão. A `P-04` citada no `02` (linha `completeStep`) é anterior à numeração atual: o comportamento dela é a RQ-30 (Adendo 3), não a P-04 de hoje (página com 404)

### Matriz estado × operação (única, área B)

Estados da instalação: **E1** tutorial desligado · **E2** tutorial ligado. Em todas as células as jornadas estão semeadas e a pessoa tem passos pendentes (o efeito tem destinatário).

**2 estados × 10 operações = 20 células** · válidas: 9 · inválidas: 11 · todas com CT (as três que esperavam Q11, Q12 e Q14 foram fechadas pelas premissas P-04, P-05 e P-07).
Legenda: `❌` = recusa + não-efeito de **cada** efeito do caminho feliz daquela operação; `✅` = operação executada, valor afirmado.

| Operação | E1 desligado | E2 ligado |
|---|---|---|
| O1 GET das sete rotas de autoria | ❌ 404 com e sem permissão, e `master_global` · CT-08 | ✅ 200 com a Page, e 403 sem permissão · CT-09 |
| O2 autoria na navegação (barra lateral, hub, ⌘K, dock) | ❌ ausente · CT-07 | ✅ presente · CT-07 |
| O3 ação Livewire numa tela de autoria já aberta | ❌ 404, jornada, passo e condição inalterados · CT-46 | ✅ grava · CT-33 |
| O4 render do checklist nos três painéis | ❌ ausente · CT-11 | ✅ presente, com a jornada do painel · CT-15 |
| O5 "Primeiros passos" na navegação (barra, hub, ⌘K, dock) | ❌ ausente · CT-11 (barra), CT-44 (as quatro) | ✅ presente · CT-15, CT-44 |
| O6 GET `/{painel}/onboarding` | ❌ 404 nos três painéis, com a permissão da página · CT-45 | ✅ 200 com a jornada do painel · CT-16; com passo concluído · CT-72 |
| O7 ações do checklist (tela montada com o tutorial ligado) | ❌ 404; progresso, preferência, sessão e eventos de tour e mídia inalterados · CT-13 (`/admin`), CT-43 (`/app`, `/infra`) | ✅ cada verbo produz o seu efeito · CT-14 |
| O8 visita à tela de um passo de visita | ❌ passo continua pendente, nenhuma linha de progresso · CT-26 (linha desligado) | ✅ passo concluído · CT-26 |
| O9 widget de progresso no dashboard do `/admin` | ❌ ausente, progresso intacto · CT-12 | ✅ presente, mesmos números · CT-12 |
| O10 tela de boas-vindas do pacote | ❌ ausente · CT-11 | ❌ ausente (RQ-29: sempre desligada) · CT-15 |

Legenda conferida célula a célula: O1, O2, O4, O5, O6, O10 são leitura, sem efeito colateral no caminho feliz — o `❌` afirma ausência/404 com o controle positivo da coluna E2 no mesmo cenário ou no par; O3 afirma o valor gravado da jornada, do passo e da condição (o efeito do "Salvar" no caminho feliz, provado pelo CT-33); O7 afirma os cinco efeitos que as ações produzem no caminho feliz (linha de progresso de passo/jornada, preferência, marca de sessão "lembrar depois", evento `onboarding-tour-start`, evento `onboarding-media-open`), e o CT-14 prova que cada um deles **acontece** com o tutorial ligado na mesma fixture; O8 afirma a linha de progresso ausente, com o par ligado provando que a mesma visita a cria; O9 afirma ausência **e** a contagem de progresso.

## Costuras de Teste

<!-- Proposta pela derivação em sub-agente; confirmada pela sessão em 2026-10-21. Cenários do fechamento da revisão adversarial entraram nos grupos existentes. -->

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 Interruptor e decisão por request | R1, R2 | Pest feature HTTP | existente — molde `tests/Kit/AgentesIaNoSettingsTest.php` CT-01…CT-05 (`kitConfigCom`, `valorNoEnv`, `gravarConfiguracao` + `alinharConfiguracoesDoKit`, `fronteiraDeRequest`) | a regra afirma valor efetivo de config, linha gravada e o efeito no request seguinte (status e HTML) | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G2 Tela de configurações | R3 (CT-58 grava pela tela e observa o request seguinte por GET, no mesmo arquivo) | componente Livewire/Filament | existente — `tests/Kit/AgentesIaNoSettingsTest.php` CT-07 (`chaveDoCampo`, `carregarAba`, `fillForm` → `save` → `configuracaoGravada`) | gravação pelo formulário Filament (gate de tela de escrita) | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G3 Autoria por URL | R5 (CT-46: tela de autoria montada por `Livewire::test`, mesmo arquivo) | Pest feature HTTP | existente — `tests/Kit/AgentesIaNoSettingsTest.php` CT-11/CT-12/CT-13 (`semAPermissao` + `noPainelDoShield('admin')` + `get`) | autorização pela rota é o observável de RQ-16; é o cenário "por fora do componente" do gate de camada | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G4 Autoria na navegação | R4, R6 (CT-44) | componente Livewire/Filament | existente — `tests/Kit/AgentesIaNoSettingsTest.php` CT-09 (navegação montada, `HubDeAdministracao`, categoria do ⌘K, `dockNaPagina` + `opcoesDoSlideover`) | elemento de navegação; presença não depende de JS | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G5 Consumo por request | R6 (CT-11, CT-45), R7, R9 (CT-15, CT-16, CT-47), R15 (CT-26), R16 (CT-56), R26 (CT-54) | Pest feature HTTP | existente — `tests/Kit/AgentesIaNoSettingsTest.php` CT-16/CT-20 (`get` + `assertSeeLivewire`/`assertDontSeeLivewire`, persona `master_global` para widget) | o requisito afirma o que é renderizado em cada painel e o que uma visita grava | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G6 Ações do checklist | R8, R11 | componente Livewire/Filament | existente — `tests/Kit/AgentesIaNoSettingsChatTest.php` (ação depois do `mount` com a flag trocada) | as ações só são alcançáveis por `$wire.`; `Livewire::test` executa o mesmo hydrate + call. Painel do `/app` boota por GET real antes (`.ai/rules/testes.md`) | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G7 Semeadura | R10, R12, R13, R14, R15 (CT-23), R17, R18 (CT-32), R25 (CT-52) | Pest feature HTTP | existente — `tests/Kit/UsuarioAdminSeederTest.php` (seeder chamado direto, asserção no banco) | o observável é o registro persistido; é a camada "por fora" de toda regra de semeadura | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G8 Estado do checklist por condição e visibilidade | R15 (CT-24, CT-25), R16 (CT-27, CT-28), R26 (CT-53) | componente Livewire/Filament | existente — `Livewire::test` do checklist como em G6; estado lido do `flowState()` do componente montado | a regra afirma o que a pessoa vê (passo concluído, visível, contagem) no painel corrente | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G9 Autoria grava | R18 (CT-33) | componente Livewire/Filament | existente — `tests/Kit/AgenteIaResourceTest.php` (gravação por `Livewire::test(Edit…)`) | gate de tela de escrita da linha "Autoria" da `## Superfície de UI` | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G10 Multi-organização | R19, R22 (CT-41), R24, R25 (CT-51), R20 (CT-55) | Pest feature HTTP + componente Livewire/Filament | existente — `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (`Tests\TenancyTestCase`, `duasOrganizacoes`, `noPainelDa`); arquivo `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | só a suíte Tenancy liga multi-organização no bootstrap (`.ai/rules/testes.md`) | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G11 `kit:install` | R20 | Pest feature HTTP | existente — `tests/Kit/ResumoDoKitInstallTest.php` (`diretorioDeInstalacaoDoKit`, `rodarKitInstallDeVerdade`, `rodarKitInstallDigitandoASenha`) — ver Q18 | o requisito afirma o diálogo e o desfecho do comando real | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G12 Rule, inventário, documentação e rotas de entrega | R21, R22 (CT-40), R23, R27 | Pest feature HTTP | existente — `tests/Kit/InventarioDeTelasTest.php` (`telasRegistradasNoPainel`), `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php`, `painelRegistradoEmTeste` | enumera os painéis da aplicação de pé e lê arquivos da árvore | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G13 Tour no navegador | R9 (CT-B01, CT-B02) | browser | existente — molde `tests/Browser/AgentesIaNoSettingsTest.php`; arquivo `tests/Browser/OnboardingGuiadoTest.php` | o tour é Alpine: só o navegador prova que ele abre o popover (JS executado) | sessão, 2026-10-21 — confirmadas no fechamento da revisão adversarial |
| G14 Acessibilidade do tutorial no navegador | R36 (CT-B03), R37 (CT-B04) | browser | existente — o arquivo do G13, `tests/Browser/OnboardingGuiadoTest.php` (mesmas fixtures e `titulosDoTourSemeado()`) | contraste é cor computada nos dois temas (axe) e o Esc é evento de teclado com foco: só o navegador prova | sessão, 2026-10-22 — confirmada sob a decisão de gsferro de corrigir QA-15/QA-16 (contraste e Esc só o navegador prova) |

Cenários da 2ª rodada adversarial nos grupos existentes: CT-60 e CT-62 em G5; CT-61 e CT-65 em G8; CT-63 e CT-64 em G10.

Cenários do quality gate, ciclo 2, nos grupos existentes (sem costura nova; a sessão confere): CT-72, CT-73 e CT-74 em G5 (o `Quando` é um GET real; no CT-72 o passo de tour é concluído antes pelo checklist montado, como no CT-14); CT-75 em G6 (a entrada é o update Livewire do checklist, como no CT-66).

Arquivos: G1–G9, G11, G12 em `tests/Kit/OnboardingGuiado{Interruptor,Consumo,Semeadura,Instalacao}Test.php` *(alterado em 2026-10-21: QA-03 — o arquivo único foi dividido em quatro na implementação)*; G10 em `tests/Tenancy/OnboardingGuiadoTenancyTest.php`; G13 em `tests/Browser/OnboardingGuiadoTest.php`. O plano cita `tests/Kit/InventarioDeOnboardingTest.php` (passo 7); a instrução desta derivação fixa o inventário no `OnboardingGuiadoTest.php` — a sessão decide.

Divergência declarada (Project Rule vence a skill): a skill chama a costura HTTP de "teste em `tests/Feature`"; neste projeto os testes do kit vivem em `tests/Kit` e `tests/Tenancy`, com o `TestCase`/`TenancyTestCase` + `RefreshDatabase` do `tests/Pest.php`.

Gate de camada da regra: R5 (autorização) tem o HTTP por fora do componente (CT-08, CT-09). R8 (autorização das ações do checklist) não tem rota própria: a única entrada é o endpoint de update do Livewire, que `Livewire::test` reproduz — desvio declarado; a forma mais externa disponível é a rota de update (`app('livewire')->getUpdateUri()`, `.ai/rules/kit.md`), que a sessão pode exigir em uma linha do CT-13. Toda regra de semeadura (D) é exercida por fora de UI (seeder chamado direto).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\TutorialGuiado::habilitado()`, `ExigeTutorialGuiadoLigado`, `ChecklistDoOnboarding::hydrate()`, policies com `viewAny()` | escolha de implementação | nenhum `Então` os chama |
| chave `kit.onboarding.habilitado`, variável `KIT_ONBOARDING`, propriedade `onboarding_habilitado`, path da migration | nome escolhido pelo plano | detalhe de fixture; o **valor** `false` é da RQ-11 e é afirmado literalmente (CT-01, CT-02) |
| aba, título da seção "Tutorial guiado", ícone e texto de ajuda do toggle | comportamento visível que só o PRD determina | nenhum cenário depende; o campo é achado por `chaveDoCampo()` |
| permissão `View:OnboardingProgress` como "usa o tutorial", concedida a `panel_user` e `admin` | decidido depois pela P-06 (Q13): a permissão decide, e todo papel do kit a recebe no seu painel | o nome `View:OnboardingProgress` segue detalhe; o oráculo é a P-06 (CT-47) |
| 404 da página `/{painel}/onboarding` com o tutorial desligado; 403 com ligado e sem permissão | o 404 é a P-04 (Q11); o 403 sem a permissão continua só do plano — P-06 diz quem **vê**, não o status da URL de quem não vê | o 404 é oráculo (CT-45); o status da URL sem a permissão é a P-18 (Q27): 403, CT-62; o CT-47 afirma checklist e item ausentes |
| `firstOrCreate` na chave, classe base `SeederDeOnboarding`, constantes `FILHOS`, `FILHOS_DE_TENANCY`, `TELAS`, `ISENTAS` | mecanismo | detalhe; os oráculos são o banco depois de semear (R12, R17) e o veredito do inventário (R22) |
| nomes dos 27 seeders filhos | nome escolhido pelo plano | usados para nomear linhas do CT-21 e para a tabela da documentação (CT-42), nunca como contagem de oráculo — a contagem sai da tabela aprovada (Adendo 2) |
| condição de visibilidade em código e de conclusão no banco (D6); nomes `tem_dois_fatores`, `convidou_alguem`, `dock_no_painel`… | mecanismo | o CT-23 afirma "toda chave citada resolve", em qualquer dos dois; os CT-24…CT-28 afirmam o comportamento |
| `OnboardingSeeder` fora do `DatabaseSeeder` | o `00` determina o efeito (RQ-06: semear é escolha), não o arquivo | o efeito é afirmado no CT-38 (o `kit:install` com a resposta padrão não cria jornada) |
| "Falha vira aviso com o comando a rodar" no `kit:install` | comportamento visível só do plano para a **falha** do seeder | nenhum cenário; o aviso do `--no-seed --onboarding` é da P-10 e é afirmado no CT-38 |
| condição `tela:{chave-do-passo}` (P-11), `multi_tenant_ligado`, classe `SeederDeOnboardingDeTenancy` | nomes que a implementação expõe (`adv-fechamento.md`), usados só para nomear | o oráculo de P-11 é o passo ausente e a contagem (CT-53, CT-54); o de P-17 é o banco vazio (CT-52) |
| `warning` no channel `onboarding` (middleware e checklist) e `info` do seeder | log sem cláusula de auditoria no `00` (Proibição 12) | nenhum CT sobre o texto do aviso; dimensão D do quality gate. A P-26 (valor do cliente não forja linha nova no canal) é premissa vigente: CT-75 |
| "tour" × "manual (tour)" na tabela aprovada | o pacote não tem modo "tour": o tour **é** a tarefa de um passo `manual` (`vendor/wallacemartinss/filament-onboarding/src/Enums/CompletionMode.php:Manual:14`; `vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:finishTour:287`) | premissa de mecanismo `@premissa` no CT-20: as duas grafias da tabela viram `manual` com tour |
| título "Primeiros passos no Admin/App/Infra" | o Adendo 1 cita o título; o oráculo usa só o prefixo "Primeiros passos" e o `panel_id`, para não fixar a forma exata do sufixo | detalhe |
| `KitInfoTest` 71 → 72, `QualidadeDeCodigoTest`, `DiagramasDaArquiteturaTest` (listas paralelas) | listas paralelas do plano, não comportamento | ficam a cargo dos testes existentes, que acusam sozinhos |

**Valor literal do requisito**: RQ-11 ("por padrão vem false") é afirmado pelo CT-01 e pelo CT-02 **sem** `config()->set()`: a config é relida do `config/kit.php` com a variável ausente do ambiente (`kitConfigCom`) e com o valor da linha ativa do `.env.example`. O `phpunit.xml` **não** fixa a variável hoje (Q19). Todo cenário fora de R1 fixa o estado com o helper `ligarTutorialGuiado()`.

**Perguntas geradas pela derivação** (renumeradas pela sessão; Q11–Q17 estão no `00` com as premissas P-04..P-10, tratadas como decididas e já com cenário; Q18–Q20 são de desenho. As da revisão adversarial são Q21–Q26 no `00`, com P-11..P-16; a do fechamento, Q?17, virou Q27 (P-18); a da 2ª rodada, Q?18, virou Q28 (P-19)):

❓ Q11 · raia: requisito · afeta: RQ-12, RQ-17 · depende de: —
Com o tutorial desligado, a URL da página "Primeiros passos" (`/{painel}/onboarding`), digitada ou guardada nos favoritos do navegador, responde 404 ou abre a página?
➡️ Recomendação: 404 — falha fechado, o mesmo regime que o Adendo 1 deu à autoria ("Tudo some + 404"); a página não existia quando Q1 foi respondida. Invariante já coberto: o item de menu some (CT-11).

❓ Q12 · raia: requisito · afeta: RQ-16 · depende de: —
Uma tela de autoria já aberta (editar uma jornada, um passo pelo gerenciador de passos, uma condição) quando o tutorial é desligado: o próximo "Salvar" é recusado com 404 e nada é gravado, ou ainda grava?
➡️ Recomendação: recusado com 404, nada gravado — falha fechado, a mesma resposta da Q10 (Adendo 3) para o checklist e da P-06 dos agentes de IA para o catálogo. Invariante já coberto: toda entrada nova pela URL responde 404 (CT-08).

❓ Q13 · raia: requisito · afeta: RQ-28 · depende de: —
Com o tutorial ligado, quem vê o checklist e a página "Primeiros passos": todo usuário que entra no painel, ou só os papéis a que o administrador conceder a permissão da página? O plano concede a `panel_user` e `admin` "nos três painéis" — o papel `infra` (o único do `/infra` além do `master_global`) ficaria sem o tutorial no `/infra`.
➡️ Recomendação: todo papel do kit que entra no painel recebe a permissão por padrão (`admin`, `panel_user`, `admin_app`, `infra`), e o administrador pode retirá-la. Coberto já: a persona padrão de cada painel vê (CT-15, linha `/infra` com `infra`).

❓ Q14 · raia: requisito · afeta: RQ-30, RQ-17 · depende de: —
Com o tutorial desligado, visitar a tela de Configurações da aplicação (o passo concluído "por visita") conclui o passo em silêncio, para aparecer concluído quando o tutorial for ligado?
➡️ Recomendação: não — nada do onboarding grava com o tutorial desligado (mesma regra da RQ-30, falha fechado). Coberto já: com o tutorial ligado a visita conclui (CT-26).

❓ Q15 · raia: requisito · afeta: RQ-23, RQ-24 · depende de: —
Na linha do `/app` da tabela aprovada ("Membros, Convites, Convites recebidos, Troca de organização | tour / condição"), qual dos quatro conclui por condição, e por qual condição?
➡️ Recomendação: só "Convites", por "já convidou alguém nesta organização" (espelha "Convites" do `/admin`); os outros três por tour. Coberto já: os quatro existem na jornada do `/app` com tour (CT-34, CT-17).

❓ Q16 · raia: requisito · afeta: RQ-28, RQ-24 · depende de: —
Com multi-organização, o progresso do checklist do `/app` é da pessoa em cada organização (concluir "Busca ⌘K" na Acme não conclui na Globex) ou da pessoa em todas?
➡️ Recomendação: por organização — é o escopo do progresso no pacote instalado (`database/migrations/2026_08_12_164953_harden_onboarding_progress_scope.php:up:38`) e a condição de convite já é por organização. Coberto já: a identidade da pessoa (CT-19).

❓ Q17 · raia: requisito · afeta: RQ-06, RQ-20 · depende de: —
`kit:install --no-seed --onboarding` (ou instalação com o banco inacessível): as jornadas são semeadas?
➡️ Recomendação: não — `--no-seed` é "não popula o banco" e as jornadas são dado; o resumo diz como semear depois. Coberto já: as quatro combinações sem `--no-seed` (CT-38).

❓ Q18 · raia: desenho · afeta: RQ-19, RQ-20 · depende de: —
`diretorioDeInstalacaoDoKit()`, `rodarKitInstallDeVerdade()` e `rodarKitInstallDigitandoASenha()` estão declarados em `tests/Kit/ResumoDoKitInstallTest.php`. O CT-38 precisa deles e de responder a uma pergunta escolhida (não só a da senha). Mover os três para `tests/Pest.php` e generalizar o último para um mapa pergunta → resposta?
➡️ Recomendação: sim — `.ai/rules/testes.md` proíbe helper cruzado fora do `tests/Pest.php` e o clone com outro nome.

❓ Q19 · raia: desenho · afeta: RQ-11 · depende de: —
O `phpunit.xml` fixa as chaves `KIT_*` que mudam comportamento para a suíte medir o kit e não o `.env` local, mas não fixará a variável do tutorial. Fixar com `false` e `force="true"`?
➡️ Recomendação: sim — mesmo motivo da Q5 dos agentes de IA. Os CT deste `04` não dependem da resposta (fixam o estado explicitamente).

❓ Q20 · raia: desenho · afeta: RQ-27 · depende de: —
Para provar que o teste de inventário fica vermelho (CT-40) sem quebrar a suíte, a verificação precisa ser uma função que recebe os painéis e devolve as telas descobertas, chamada pelo teste "estado do kit" e pelos controles com tela injetada. Declarar essa função em `tests/Pest.php` (Kit e Tenancy usam)?
➡️ Recomendação: sim — é o que permite os controles negativos e a linha da suíte Tenancy (CT-41) sem helper cruzado.

❓ Q27 (era Q?17; decidida como P-18, cenário CT-62) · raia: requisito · afeta: P-06, RQ-28 · depende de: —
Com o tutorial ligado, alguém de um papel sem a permissão da página "Primeiros passos" (retirada pelo administrador) que digita `/{painel}/onboarding`: recebe 403 (como as outras telas do kit sem permissão) ou 404?
➡️ Recomendação: 403 — é o regime das telas do kit sem permissão, e o 404 fica reservado ao tutorial desligado (P-04), o que mantém os dois motivos distinguíveis. Coberto já: o checklist e o item de menu somem para essa pessoa (CT-47).

## Setup Global

### Personas
- **a administradora** — `usuarioDoKit('admin', 'admin@example.com')`; com as permissões de autoria (`ViewAny:OnboardingFlow`, `ViewAny:OnboardingCondition` e as de ver/criar/editar) que o `PapeisSeeder` dá ao `admin`. Para "sem a permissão": `semAPermissao('admin', 'ViewAny:OnboardingFlow', 'ViewAny:OnboardingCondition', …)` seguido de `noPainelDoShield('admin')` — a única variável é a permissão; `canAccessPanel()` continua passando
- **o Bruno** — `usuarioDoKit('admin', 'bruno@example.com')`, segunda pessoa do `/admin` (identidade nos CT-19, CT-25)
- **a Ana** — `usuarioDoKit('panel_user', 'ana@example.com')`, persona padrão do `/app`
- **a Íris** — `usuarioDoKit('infra', 'iris@example.com')`, persona padrão do `/infra`
- **o administrador geral** — `usuarioDoKit('master_global', 'master@example.com')`, persona do widget (passa no `canView()`) e da linha "Gate::before" do CT-08
- **a Carla** — na suíte Tenancy, `usuarioComPapel('panel_user', $acme)` de `duasOrganizacoes()`

### Fixtures
- Seeds em todo arquivo: `ShieldPermissionsSeeder`, `PapeisSeeder`
- Jornadas do kit: `$this->seed(OnboardingSeeder::class)` (nome do plano, D5). Os CT da área B e o CT-13/CT-14 usam **jornada de teste** criada pelos models do pacote (`OnboardingFlow::create` com `key`, `panel_id = 'admin'`, título pt_BR/en) com seis passos: `manual-pendente` (com tour de 3 paradas), `opcional` (`is_required = false`), `concluido` (concluído pela própria pessoa antes do `Dado`), `visita`, `com-imagem` (mídia de imagem por URL) e `com-video` (mídia de vídeo por URL) — para cada verbo ter destinatário independente do conteúdo semeado. Uma **segunda jornada de teste do `/admin`** (`jornada-b`) dá destino ao `selectFlow`. A mesma jornada de teste existe com `panel_id = 'app'` e `'infra'` para o CT-43
- **o Davi** — persona com o papel `admin_app` (só na suíte Tenancy): `usuarioComPapel('admin_app', $acme, 'davi@example.com')`, e no CT-49 também `admin_app` na Globex (`papelNaOrganizacao($davi, 'admin_app', $globex)`) — linha P-06 do CT-37 e persona do CT-49 (abre a tela de convites da organização, P-11)
- **a Elisa** — segunda persona com o papel `admin_app` na Acme (só na suíte Tenancy): `usuarioComPapel('admin_app', $acme, 'elisa@example.com')` — autora do convite na linha de identidade do CT-49
- Estado do tutorial: helper planejado `ligarTutorialGuiado(bool $ligado = true)` em `tests/Pest.php` (molde `ligarAgentesDeIa()`; Kit, Tenancy e Browser usam). **Todo** cenário fora de R1 o chama, inclusive para desligar
- "Banco" do settings: `gravarConfiguracao('onboarding_habilitado', …)` + `alinharConfiguracoesDoKit()`; leitura por `configuracaoGravada()`
- Config de fábrica: `kitConfigCom('KIT_ONBOARDING', null)` e com o valor do `.env.example` (`valorNoEnv`)
- Features dos passos ligadas (para a visibilidade de P-03 não esconder passo por acaso): `ligarDock(['admin','app','infra'])`, `ligarDashboardDinamico(true, [])`, `config(['kit.hub' => true])` (o `phpunit.xml` força `KIT_HUB=false`), `ligarAgentesDeIa(true, [])`, `config(['lockscreen.enabled' => true])`, `config(['command-center.enabled' => true])`, `config(['kit.demo' => true])` — nomes de chave a confirmar no `config/`; cada cenário que depende da contagem declara as features no `Dado`
- 2FA: sessão do Breezy confirmada / iniciada sem confirmação (`breezySession`), nunca coluna do usuário
- Convites: `Convite::factory()` com `convidado_por_id` da pessoa; no `/app`, `ofertaPara()` (carimbo de organização, `.ai/rules/testes.md`)
- Sem máquina de estados de domínio: as entidades tocadas (settings, jornada, passo, progresso) não têm ciclo de vida do kit; "concluído" na fixture é a ação do pacote (`completeStep` com o tutorial ligado), nunca `insert` na tabela de progresso

### Como cada superfície é observada
- Painel `/app` boota só por request real: `get('/app')->assertOk()` antes de `Livewire::test` no `/app`; `/admin` e `/infra`, `noPainelBootado()`
- Entre requests com estado diferente: `fronteiraDeRequest()`
- Checklist presente/ausente: `assertSeeLivewire('filament-onboarding-launcher')` / `assertDontSeeLivewire(...)` **e** o marcador `fio-ring` fora de `<script>`; toda linha "ausente" convive com uma linha "presente" na mesma tabela (controle positivo)
- Boas-vindas: marcador `fio-welcome-layer`; tour: `x-data="onboardingTour"`
- Navegação: `Filament::getNavigation()` do painel montado; item "Primeiros passos" pela URL `OnboardingProgress::getUrl(panel: …)`
- Estado da jornada: `flowState()` do checklist montado (passos visíveis, concluídos, total) — nunca a tabela de progresso sozinha
- Progresso gravado (não-efeito): fotografia das linhas de `onboarding_step_progress`, `onboarding_flow_progress` e `onboarding_preferences` antes e depois (contagem **e** conteúdo), mais a marca de sessão "lembrar depois" do pacote; eventos por `assertDispatched`/`assertNotDispatched('onboarding-tour-start')` e `('onboarding-media-open')`
- Status 200 nunca sozinho: junto, `assertSeeLivewire` da Page (`ListOnboardingFlows`, `OnboardingProgress`…) ou o conteúdo afirmado

### Fakes
- Nenhum de serviço externo. `Log` não é observado (Proibição 12), com uma exceção: o CT-75 observa o **arquivo** do canal `onboarding` porque a P-26 é premissa vigente (a linha forjada é o efeito que ela proíbe), nunca o conteúdo do aviso

### Estratégia de DB
- `RefreshDatabase` dos blocos `Kit` e `Tenancy` em `tests/Pest.php`. O boot roda antes das migrations e o alinhamento é no-op: quem quer o caminho banco → config chama `alinharConfiguracoesDoKit()`
- Testes que leem `docs/`, `.ai/rules/` ou `wikis/` levam `->skip(fn (): bool => ! naArvoreDoKit(), …)` (`.ai/rules/testes.md`, CT-11 da `RedeDeDocumentacaoTest`)

---

## Regra R1 — A instalação nova nasce com o tutorial desligado

> `RQ-10`, `RQ-11` · perfil **padrão** · técnica: **EP** (partições do ambiente: variável ausente · valor do `.env.example` · `.env` preenchido)

```gherkin
# language: pt
Funcionalidade: Onboarding guiado — jornadas semeadas, interruptor no settings e rule de manutenção

  Regra: a instalação nova e o .env de fábrica nascem com o tutorial desligado

    Esquema do Cenário: [CT-01] a config lida do ambiente de fábrica desliga o tutorial
      Dado o ambiente com <ambiente>
      Quando o config/kit.php é lido
      Então o tutorial lido é false, comparado com tipo estrito

      Exemplos:
        | ambiente                                                       | # partição          |
        | a variável do tutorial ausente                                 | instalação sem ela  |
        | a variável com o valor da linha ativa do .env.example          | .env de fábrica     |

    Esquema do Cenário: [CT-02] a migration de settings semeia o banco com o que o ambiente diz
      Dado a propriedade do tutorial ainda não semeada no settings do kit
      E a config resolvida do ambiente <semente>
      Quando a migration de settings da feature roda
      Então o valor gravado no banco é <gravado>

      Exemplos:
        | semente                                       | gravado | # partição       |
        | de fábrica (variável ausente)                 | false   | fábrica          |
        | a variável do tutorial = true                 | true    | .env preenchido  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | default `true` no `env()` do `config/kit.php` | CT-01 (linha "ausente") | lido `false`; o mutante lê `true` |
| M2 | `.env.example` publicado com a variável `=true` (ou ativa sem valor, lida como string) | CT-01 (linha ".env de fábrica") | lido `false` com `toBe`; o mutante entrega `true` ou `""` |
| M3 | migration semeia `true` fixo | CT-02 (linha "fábrica") | gravado `false`; o mutante grava `true` |
| M4 | migration semeia `false` fixo, ignorando o ambiente | CT-02 (linha ".env preenchido") | gravado `true`; o mutante grava `false` |

---

## Regra R2 — O banco vence o `.env` e a decisão vale no request seguinte, sem deploy

> `RQ-10`, `RQ-12` ("assim como foi feito no uso dos Agentes de IA"), `RQ-31` · perfil **completo** (atravessa A e B) · técnica: **tabela de decisão** (semente × gravado) + **2-switch** (três requests no mesmo processo)

```gherkin
  Regra: o valor gravado no banco vence o .env e a decisão é tomada a cada request

    Esquema do Cenário: [CT-03] depois do boot, o que está gravado decide, não o .env
      Dado a administradora com a permissão de autoria e as jornadas do kit semeadas
      E a config semeada pelo .env com o tutorial <semente>
      E o banco com o tutorial <gravado>
      Quando o boot do request seguinte alinha a config e <alvo> é observado
      Então <resultado>

      Exemplos:
        | semente   | gravado   | alvo                          | resultado                                         |
        | desligado | ligado    | a config efetiva lida         | tutorial `true` (tipo estrito)                    |
        | desligado | ligado    | o GET /admin/onboarding-flows | 200 com a Page ListOnboardingFlows no corpo       |
        | desligado | ligado    | o GET /admin                  | o checklist flutuante aparece                     |
        | ligado    | desligado | o GET /admin/onboarding-flows | 404                                               |
        | ligado    | desligado | o GET /admin                  | o checklist flutuante não aparece                 |

    Cenário: [CT-04] o request seguinte obedece ao interruptor, nos dois sentidos, no mesmo processo
      Dado a administradora com a permissão de autoria e as jornadas do kit semeadas
      E o tutorial ligado, e um primeiro request ao /admin mostrou o checklist e /admin/onboarding-flows respondeu 200
      Quando o tutorial é desligado, o /admin e /admin/onboarding-flows são pedidos, o tutorial é religado e os dois são pedidos de novo
      Então no segundo par o /admin vem sem o checklist e /admin/onboarding-flows responde 404
      E no terceiro par o /admin volta a mostrar o checklist e /admin/onboarding-flows responde 200 com a Page ListOnboardingFlows
```

O `Quando` do CT-04 é uma sequência só (o evento "troca de estado entre requests"); cada par é separado por `fronteiraDeRequest()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M5 | a propriedade fora do mapa settings → config (o banco não chega à config) | CT-03 (linha 1) | config `true`; o mutante fica com o `false` da semente |
| M6 | `.env` preenchido vence o banco | CT-03 (linhas 4 e 5) | 404 e checklist ausente; o mutante mantém a semente ligada |
| M7 | decisão tomada no registro do painel (`manageFlows`/`launcher` com `bool` avaliado no boot), fixa no processo | CT-04 | segundo par: 404 e sem checklist; o mutante segue com o 200 e o checklist do primeiro |
| M8 | autoria desregistrada no boot quando desligado (`manageFlows(false)`), e religar só vale depois de reiniciar o processo | CT-04 (terceiro par) | 200 com `ListOnboardingFlows`; o mutante responde 404 (rota inexistente) |
| M9 | o mapa liga a propriedade a outra chave (a do dock ou dos agentes) | CT-03 (linhas 2 e 3) | 200 e checklist presente; o mutante não liga o tutorial |

---

## Regra R3 — A tela de configurações grava o interruptor, e o settings do tutorial é só o interruptor

> `RQ-10`, `RQ-22` · perfil **padrão** · técnica: **EP** (os dois sentidos da gravação) + **gate de tela de escrita**

```gherkin
  Regra: a tela de configurações grava o interruptor do tutorial, sem lista de painéis

    Esquema do Cenário: [CT-05] a administradora liga e desliga o tutorial pela tela
      Dado a administradora na tela de configurações da aplicação
      E o tutorial gravado como <antes>
      Quando ela marca o interruptor do tutorial como <preenche> e salva
      Então o valor gravado no banco é <gravado>
      E, depois do alinhamento, a config efetiva é <gravado>

      Exemplos:
        | antes | preenche | gravado |
        | false | true     | true    |
        | true  | false    | false   |

    Cenário: [CT-06] o settings do tutorial é um interruptor só, sem lista de painéis
      Dado o settings do kit e a tela de configurações da aplicação
      Quando as propriedades do settings mapeadas para a configuração do tutorial são listadas, e os campos da seção dele na tela
      Então há exatamente uma propriedade, do tipo bool
      E a seção do tutorial na tela tem exatamente um campo, o interruptor

    Esquema do Cenário: [CT-58] o que a tela grava vale no request seguinte, nos dois sentidos
      Dado a administradora com as permissões de autoria, as jornadas do kit semeadas e o tutorial gravado como <antes>
      E o /admin aberto uma vez nesse estado
      Quando ela salva o interruptor como <depois> pela tela de configurações e o request seguinte abre <alvo>
      Então <resultado>

      Exemplos:
        | antes | depois | alvo                     | resultado                                             |
        | true  | false  | /admin                   | o checklist flutuante não está no corpo               |
        | true  | false  | /admin/onboarding-flows  | 404                                                   |
        | false | true   | /admin                   | o checklist flutuante está no corpo                   |
        | false | true   | /admin/onboarding-flows  | 200 com a Page ListOnboardingFlows                    |
```

O `Quando` do CT-58 é um evento só — "salvar e o próximo request" (ADV-24/25): o oráculo é o efeito da gravação pela **tela**, sem `gravarConfiguracao` nem `config()->set()` no meio; entre o `save` e o GET, só `fronteiraDeRequest()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | campo do toggle com `dehydrated(false)` ou fora do `statePath` (não chega ao `save`) | CT-05 (linha 1) | gravado `true`; o mutante deixa `false` |
| M11 | gravação só no sentido de ligar (desligar ignorado por `?:`/default) | CT-05 (linha 2) | gravado `false`; o mutante mantém `true` |
| M12 | lista de painéis do tutorial no settings, à moda dos agentes de IA | CT-06 | uma propriedade; o mutante tem duas |
| M13 | `Select` de painéis na seção da tela, mesmo sem propriedade própria | CT-06 | um campo na seção; o mutante tem dois |
| M110 | a tela grava o banco mas não realinha a config no request seguinte (o `save` não esquece a instância de settings, e o boot seguinte lê o cache) — ADV-24 | CT-58 (linhas 1 e 3) | checklist ausente/presente conforme o salvo; o mutante segue no estado anterior |
| M111 | ligar pela tela não reabre a autoria: os Resources ficam registrados conforme o valor lido no boot do processo em que a tela salvou — ADV-25 | CT-58 (linha 4) | 200 com `ListOnboardingFlows`; o mutante responde 404 |

---

## Regra R4 — Desligado, a autoria some de toda superfície de navegação do `/admin`

> `RQ-15`, `RQ-12` · perfil **completo** · técnica: **EP por superfície** com controle positivo. As superfícies além da barra lateral vêm de RQ-12 ("no mesmo modelo do uso dos Agentes de IA"), cujo modelo esconde o catálogo da barra, do hub, do ⌘K e do dock

```gherkin
  Regra: com o tutorial desligado, "Onboarding" e "Condições" não aparecem em nenhuma navegação do /admin

    Esquema do Cenário: [CT-07] cada superfície lista a autoria só com o tutorial ligado
      Dado a administradora com as permissões de autoria, no /admin, com o hub e o dock ligados
      E o tutorial <estado>
      Quando <superficie> é montada
      Então o link para <tela> está <presenca>

      Exemplos:
        | superficie                  | tela                        | estado    | presenca  |
        | a barra lateral             | a lista de jornadas         | desligado | ausente   |
        | a barra lateral             | a lista de condições        | desligado | ausente   |
        | o hub de cartões            | a lista de jornadas         | desligado | ausente   |
        | a busca ⌘K (telas)          | a lista de condições        | desligado | ausente   |
        | o catálogo do dock          | a lista de jornadas         | desligado | ausente   |
        | a barra lateral             | a lista de jornadas         | ligado    | presente  |
        | a barra lateral             | a lista de condições        | ligado    | presente  |
        | o hub de cartões            | a lista de jornadas         | ligado    | presente  |
        | a busca ⌘K (telas)          | a lista de condições        | ligado    | presente  |
        | o catálogo do dock          | a lista de jornadas         | ligado    | presente  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M14 | só `shouldRegisterNavigation()` escondido; `canAccess`/policy continuam liberando — hub, ⌘K e dock listam | CT-07 (linhas hub, ⌘K, dock desligado) | ausente; o mutante lista |
| M15 | só a jornada escondida; "Condições" continua | CT-07 (linha 2) | ausente; o mutante mostra |
| M16 | a condição invertida (aparece só com desligado) | CT-07 (linhas 6 a 10) | presente com ligado; o mutante esconde |
| M17 | esconder pela permissão retirada ao desligar (o `PapeisSeeder`/settings tira `ViewAny`) — volta errado | CT-07 (linhas ligado) e CT-09 | presente com ligado e permissão intacta; o mutante segue sem permissão |

---

## Regra R5 — Desligado, as rotas de autoria respondem 404 com e sem permissão; ligado, a permissão decide; o `/admin` continua abrindo

> `RQ-16` · perfil **completo** · técnica: **matriz papel × ação** (persona × rota × estado), **saída do estado de erro**

```gherkin
  Regra: com o tutorial desligado, as telas de autoria respondem 404 pela URL, inclusive para quem tem a permissão

    Esquema do Cenário: [CT-08] as sete rotas de autoria respondem 404 com o tutorial desligado, para qualquer persona
      Dado as jornadas do kit semeadas e uma condição gravada
      E o tutorial desligado
      E <persona>
      Quando ela abre <rota>
      Então a resposta é 404
      E o corpo não traz a Page de autoria

      Exemplos:
        | persona                                         | rota                                                   |
        | a administradora com as permissões de autoria   | /admin/onboarding-flows                                |
        | a administradora com as permissões de autoria   | /admin/onboarding-flows/create                         |
        | a administradora com as permissões de autoria   | /admin/onboarding-flows/{jornada do /admin}            |
        | a administradora com as permissões de autoria   | /admin/onboarding-flows/{jornada do /admin}/edit       |
        | a administradora com as permissões de autoria   | /admin/onboarding-conditions                           |
        | a administradora com as permissões de autoria   | /admin/onboarding-conditions/create                    |
        | a administradora com as permissões de autoria   | /admin/onboarding-conditions/{condição}/edit           |
        | a administradora sem as permissões de autoria   | /admin/onboarding-flows                                |
        | a administradora sem as permissões de autoria   | /admin/onboarding-conditions/{condição}/edit           |
        | o administrador geral (master_global)           | /admin/onboarding-flows/{jornada do /admin}/edit       |
        | o administrador geral (master_global)           | /admin/onboarding-conditions                           |

    Esquema do Cenário: [CT-09] com o tutorial ligado, a permissão decide como nas outras telas do kit
      Dado as jornadas do kit semeadas e uma condição gravada
      E o tutorial ligado
      E <persona>
      Quando ela abre <rota>
      Então a resposta é <status>
      E <corpo>

      Exemplos:
        | persona                                        | rota                                              | status | corpo                                      |
        | a administradora com as permissões de autoria  | /admin/onboarding-flows                           | 200    | traz a Page ListOnboardingFlows            |
        | a administradora com as permissões de autoria  | /admin/onboarding-flows/{jornada do /admin}/edit  | 200    | traz a Page EditOnboardingFlow             |
        | a administradora com as permissões de autoria  | /admin/onboarding-conditions                      | 200    | traz a Page ListOnboardingConditions       |
        | a administradora sem as permissões de autoria  | /admin/onboarding-flows                           | 403    | não traz a Page ListOnboardingFlows        |
        | a administradora sem as permissões de autoria  | /admin/onboarding-conditions                      | 403    | não traz a Page ListOnboardingConditions   |

    Cenário: [CT-10] depois do 404 da autoria, o painel de administração continua abrindo
      Dado a administradora com as permissões de autoria e o tutorial desligado
      E /admin/onboarding-flows acabou de responder 404
      Quando ela abre /admin
      Então a resposta é 200 com a Page do dashboard
      E a barra lateral lista a tela de Usuários e não lista a autoria

    Esquema do Cenário: [CT-46] a tela de autoria aberta com o tutorial ligado recusa a ação depois do desligar
      Dado as jornadas do kit semeadas e a administradora com as permissões de autoria
      E <tela> montada com o tutorial ligado, com <campo> preenchido com "Texto novo"
      E o tutorial desligado em seguida
      Quando ela aciona <acao>
      Então a resposta é 404
      E <registro> continua com o valor que tinha antes, e a contagem de jornadas, passos e condições não muda

      Exemplos:
        | tela                                                      | campo                    | acao                    | registro                         |
        | a edição da jornada do /admin                              | o título pt_BR           | salvar                  | o título pt_BR da jornada        |
        | a edição de uma condição gravada pelo seeder               | o rótulo pt_BR           | salvar                  | o rótulo pt_BR da condição       |
        | o gerenciador de passos da jornada do /admin (criar passo) | o título pt_BR e a chave | criar o passo           | a lista de passos da jornada     |
        | a lista de jornadas                                        | —                        | ordenar pela coluna título | — (nada a gravar; só o 404)   |
        | a criação de jornada                                       | a chave e o título pt_BR | criar                   | a contagem de jornadas           |
        | a criação de condição                                      | a chave e o rótulo pt_BR | criar                   | a contagem de condições          |
```

ADV2-07: as linhas 5 e 6 do CT-46 cobrem as telas de criar (`CreateOnboardingFlow`, `CreateOnboardingCondition`); o "valor de antes" é a contagem.

P-05 (Q12). Gatilho por `Livewire::test` da Page do pacote (`EditOnboardingFlow`, `EditOnboardingCondition`, `ListOnboardingFlows`) e do `StepsRelationManager`, montados com o tutorial ligado; a troca de estado entre o `mount` e a ação é o que o cenário mede.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | barreira só na policy (`viewAny` falso): sem 404, a resposta é 403 | CT-08 (linhas com permissão) | 404; o mutante responde 403 |
| M19 | `master_global` atravessa pelo `Gate::before` | CT-08 (linhas 10 e 11) | 404; o mutante responde 200 |
| M20 | só a lista guardada; criar/ver/editar abertos por URL direta — inclusive a página "ver" (`ViewOnboardingFlow`) esquecida na lista de rotas guardadas | CT-08 (linhas 2, 3, 4, 6, 7) | 404 em cada uma (a linha 3 isola a "ver"); o mutante responde 200 |
| M21 | só jornadas guardadas; condições abertas | CT-08 (linhas 5, 6, 7) | 404; o mutante responde 200 |
| M23 | ligado, a permissão deixa de ser exigida (`viewAny` só confere o interruptor) | CT-09 (linhas 4 e 5) | 403; o mutante responde 200 |
| M24 | o guarda aborta o painel inteiro quando desligado | CT-10 | 200 no `/admin` com a tela de Usuários na barra; o mutante responde 404 |
| M112 | o guarda do 404 só no GET da rota (middleware não persistente): o update Livewire da tela já aberta passa e grava — ADV (P-05) | CT-46 (linhas 1–3) | 404 e valor anterior; o mutante grava "Texto novo" |
| M113 | o guarda protege as Pages, mas não o gerenciador de passos (relation manager é outro componente Livewire) | CT-46 (linha 3) | lista de passos inalterada; o mutante cria o passo |
| M163 | guarda só nas Pages de edição (subclasse/hook só em `Edit…`), as de criação abertas gravam — ADV2-07 | CT-46 (linhas 5 e 6) | 404 e contagens iguais; o mutante cria a jornada e a condição |

---

## Regra R6 — Desligado, checklist, tours, boas-vindas e "Primeiros passos" (menu, hub, ⌘K, dock e URL) não aparecem em painel nenhum

> `RQ-17`, `RQ-12`, `RQ-29`, `P-04` · perfil **completo** · técnica: **EP por painel** e **por superfície** com controle positivo. O tour só parte do checklist e da página (Fora de Escopo do `00`); com os dois ausentes e o `startTour` recusado (CT-13, CT-43), nenhum tour é oferecido

```gherkin
  Regra: com o tutorial desligado, o consumo do onboarding não aparece em painel nenhum

    Esquema do Cenário: [CT-11] a tela do painel vem sem checklist, tour, boas-vindas e item "Primeiros passos" com o tutorial desligado
      Dado as jornadas do kit semeadas e <persona> com passos pendentes na jornada de <painel>
      E o tutorial <estado>
      Quando ela abre o dashboard de <painel>
      Então o checklist flutuante está <checklist>
      E o item "Primeiros passos" está <item> na navegação
      E a marcação de tour está <tour> no corpo
      E a tela de boas-vindas do pacote não está no corpo

      Exemplos:
        | persona          | painel | estado    | checklist | item     | tour                       |
        | a Ana            | /app   | desligado | ausente   | ausente  | ausente                    |
        | a administradora | /admin | desligado | ausente   | ausente  | ausente                    |
        | a Íris           | /infra | desligado | ausente   | ausente  | ausente                    |
        | a Ana            | /app   | ligado    | presente  | presente | (não afirmado — controle)  |
        | a administradora | /admin | ligado    | presente  | presente | (não afirmado — controle)  |
        | a Íris           | /infra | ligado    | presente  | presente | (não afirmado — controle)  |

    Esquema do Cenário: [CT-44] "Primeiros passos" some de toda superfície de navegação com o tutorial desligado
      Dado as jornadas do kit semeadas, <persona> no <painel>, com o hub e o dock ligados
      E o tutorial <estado>
      Quando <superficie> é montada
      Então o link para a página "Primeiros passos" do <painel> está <presenca>

      Exemplos:
        | persona          | painel | superficie          | estado    | presenca |
        | a administradora | admin  | o hub de cartões    | desligado | ausente  |
        | a administradora | admin  | a busca ⌘K (telas)  | desligado | ausente  |
        | a administradora | admin  | o catálogo do dock  | desligado | ausente  |
        | a Ana            | app    | o catálogo do dock  | desligado | ausente  |
        | a Íris           | infra  | a busca ⌘K (telas)  | desligado | ausente  |
        | a administradora | admin  | o hub de cartões    | ligado    | presente |
        | a administradora | admin  | a busca ⌘K (telas)  | ligado    | presente |
        | a administradora | admin  | o catálogo do dock  | ligado    | presente |
        | a Íris           | infra  | a busca ⌘K (telas)  | ligado    | presente |
        | a Ana            | app    | o hub de cartões    | desligado | ausente  |
        | a Íris           | infra  | o hub de cartões    | desligado | ausente  |
        | a Íris           | infra  | o catálogo do dock  | desligado | ausente  |
        | a Ana            | app    | a busca ⌘K (telas)  | desligado | ausente  |
        | a Ana            | app    | o catálogo do dock  | ligado    | presente |
        | a Ana            | app    | o hub de cartões    | ligado    | presente |
        | a Ana            | app    | a busca ⌘K (telas)  | ligado    | presente |
        | a Íris           | infra  | o hub de cartões    | ligado    | presente |
        | a Íris           | infra  | o catálogo do dock  | ligado    | presente |

    Esquema do Cenário: [CT-45] a URL da página "Primeiros passos" responde 404 com o tutorial desligado, nos três painéis
      Dado as jornadas do kit semeadas e o tutorial desligado
      E <persona>, cujo papel tem a permissão da página no <painel>
      Quando ela abre <url>
      Então a resposta é 404
      E o corpo não traz a Page OnboardingProgress

      Exemplos:
        | persona                               | painel | url               |
        | a administradora                      | admin  | /admin/onboarding |
        | a Ana                                 | app    | /app/onboarding   |
        | a Íris                                | infra  | /infra/onboarding |
        | o administrador geral (master_global) | admin  | /admin/onboarding |
```

ADV-21: a marcação de tour não é afirmada nas linhas "ligado" do CT-11 — com o tutorial ligado o runner pode ser renderizado, e afirmar a ausência seria oráculo invertido; as linhas "ligado" são controle do checklist e do item. O controle positivo do CT-45 é o CT-16 (a mesma URL, ligado, 200).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | o interruptor aplicado só ao `/admin`; `/app` e `/infra` registram o plugin com `launcher(true)` | CT-11 (linhas 1 e 3) | checklist ausente; o mutante o renderiza |
| M26 | `launcher` desligado, mas a página de progresso continua no menu (sem `skipWhen`) | CT-11 (linhas 1–3, coluna item) | item ausente; o mutante o lista |
| M27 | boas-vindas não desligada e mostrada para quem tem jornada pendente | CT-11 (última asserção, linhas desligado e ligado) | sem `fio-welcome-layer`; o mutante o renderiza |
| M28 | o plugin ausente do `/app` ou do `/infra` (só o `/admin` ganha consumo) — esconde no desligado por acidente e falha no ligado | CT-11 (linhas 4 e 6) | checklist presente; o mutante não renderiza |
| M29 | condição invertida | CT-11 (todas) | os pares desligado/ligado divergem |
| M114 | só o item da barra lateral escondido (`shouldRegisterNavigation`); a página continua no hub, no ⌘K e no dock — ADV-03 | CT-44 (linhas desligado) | ausente; o mutante lista |
| M164 | a página registrada só no catálogo do dock e no ⌘K do `/admin` (hub do `/app` e do `/infra` sem ela) — ou escondida em todas as superfícies também com o tutorial ligado — ADV2-11 | CT-44 (linhas 10–18) | ausente desligado e presente ligado em cada superfície × painel; o mutante erra uma das metades |
| M115 | a página some do menu, mas a URL abre com o tutorial desligado (sem guarda na rota da página) — ADV-04 | CT-45 (linhas 1–3) | 404; o mutante responde 200 |
| M116 | guarda da página só no `/admin` | CT-45 (linhas 2 e 3) | 404 no `/app` e no `/infra`; o mutante responde 200 |
| M117 | `master_global` atravessa o guarda da página pelo `Gate::before` | CT-45 (linha 4) | 404; o mutante responde 200 |

---

## Regra R7 — Desligado, o widget de progresso some do dashboard e volta ao religar, sem perder dado

> `P-02` · perfil **completo** · técnica: **2-switch** (ligado → desligado → ligado)

```gherkin
  Regra: com o tutorial desligado, o widget "Jornadas de onboarding concluídas" não aparece, e religado volta com os mesmos números

    Cenário: [CT-12] o widget de progresso some e volta, e o progresso gravado fica intacto
      Dado o administrador geral e a jornada de teste do /admin
      E, com o tutorial ligado, a administradora concluiu todos os passos dela e o Bruno concluiu só um
      E o dashboard do /admin mostra o widget com 1 jornada concluída de 2 iniciadas
      Quando o tutorial é desligado, o dashboard é aberto, o tutorial é religado e o dashboard é aberto de novo
      Então no dashboard desligado o widget não está, e as linhas de progresso de jornada e de passo são as mesmas de antes
      E no dashboard religado o widget mostra 1 jornada concluída de 2 iniciadas
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M30 | `canView()` do widget ignora o interruptor | CT-12 | widget ausente desligado; o mutante o renderiza |
| M31 | desligar limpa o progresso (rotina de "reset" no salvar do settings ou no guarda) | CT-12 | linhas de progresso iguais e "1 de 2" ao religar; o mutante zera |
| M32 | widget removido da descoberta no boot quando desligado, sem voltar no mesmo processo | CT-12 (segundo dashboard) | widget presente ao religar; o mutante segue ausente |

---

## Regra R8 — Desligado depois de a tela abrir o checklist, toda ação dele responde 404 e nada é gravado

> `RQ-30` · perfil **completo** · técnica: **tabela estado × evento** (mount com o tutorial ligado → desliga → ação) + **rastreio de efeito** na direção "não aconteceu", com o **controle** de que cada efeito acontece com o tutorial ligado na mesma fixture (CT-14). "Igual ao chat dos agentes de IA" (Adendo 3) fixa a recusa como 404

```gherkin
  Regra: com o tutorial desligado depois de a tela abrir o checklist, toda ação seguinte dele é recusada e nada é gravado

    Esquema do Cenário: [CT-13] a ação do checklist montado com o tutorial ligado é recusada depois do desligar
      Dado a jornada de teste do /admin e a administradora com o passo <passo> na situação <situacao>
      E o checklist montado no /admin com o tutorial ligado
      E o tutorial desligado em seguida
      Quando ela chama <acao> no checklist
      Então a resposta é 404
      E as linhas de progresso de passo, de jornada e as preferências dela são as mesmas de antes, e a marca de sessão "lembrar depois" também
      E nem o evento de início de tour nem o de abrir mídia foram despachados
      E o checklist continua na jornada em que estava

      Exemplos:
        | acao                                     | passo           | situacao                          |
        | completeStep("manual-pendente")          | manual-pendente | pendente                          |
        | skipStep("opcional")                     | opcional        | pendente                          |
        | undoStep("concluido")                    | concluido       | concluído por ela                 |
        | startTour("manual-pendente")             | manual-pendente | pendente, com tour                |
        | tourProgress("manual-pendente", 1, 3)    | manual-pendente | pendente, com tour                |
        | finishTour("manual-pendente")            | manual-pendente | pendente, com tour                |
        | openMedia("com-imagem")                  | com-imagem      | pendente, com mídia               |
        | videoProgress("com-video", 30.0, 60.0)   | com-video       | pendente, com vídeo               |
        | dismissFlow()                            | —               | jornada em andamento              |
        | restoreFlow()                            | —               | jornada dispensada por ela        |
        | restartFlow()                            | concluido       | jornada com um passo feito        |
        | selectFlow("jornada-b")                  | —               | checklist na jornada de teste     |
        | startOnboarding()                        | —               | nunca recebida pelo pacote        |
        | remindMeLater()                          | —               | sem a marca de sessão             |
        | neverShowOnboarding()                    | —               | sem preferência gravada           |
        | showOnboardingAgain()                    | —               | preferência "nunca mostrar" gravada |
        | toggle()                                 | —               | checklist fechado                 |

    Esquema do Cenário: [CT-14] com o tutorial ligado, a mesma ação na mesma fixture produz o efeito
      Dado a jornada de teste do /admin e a administradora com o passo <passo> na situação <situacao>
      E o checklist montado no /admin com o tutorial ligado
      Quando ela chama <acao> no checklist
      Então <efeito>

      Exemplos:
        | acao                                   | passo           | situacao                   | efeito                                                        |
        | completeStep("manual-pendente")        | manual-pendente | pendente                   | o passo fica concluído para ela                               |
        | skipStep("opcional")                   | opcional        | pendente                   | o passo fica pulado para ela                                  |
        | undoStep("concluido")                  | concluido       | concluído por ela          | o passo deixa de estar concluído                              |
        | startTour("manual-pendente")           | manual-pendente | pendente, com tour         | o evento de início de tour é despachado com as 3 paradas      |
        | tourProgress("manual-pendente", 1, 3)  | manual-pendente | pendente, com tour         | o progresso do tour do passo registra a parada 1 de 3         |
        | finishTour("manual-pendente")          | manual-pendente | pendente, com tour         | o passo fica concluído para ela                               |
        | openMedia("com-imagem")                | com-imagem      | pendente, com mídia        | o evento de abrir mídia é despachado com a imagem do passo    |
        | videoProgress("com-video", 30.0, 60.0) | com-video       | pendente, com vídeo        | o progresso de vídeo do passo registra 30 de 60 segundos      |
        | dismissFlow()                          | —               | jornada em andamento       | a jornada fica dispensada para ela                            |
        | restoreFlow()                          | —               | jornada dispensada por ela | a jornada deixa de estar dispensada                           |
        | restartFlow()                          | concluido       | jornada com um passo feito | o passo "concluido" volta a pendente                          |
        | selectFlow("jornada-b")                | —               | checklist na jornada de teste | o checklist passa a mostrar a jornada-b                    |
        | startOnboarding()                      | —               | nunca recebida pelo pacote | a preferência registra que ela foi recebida                   |
        | remindMeLater()                        | —               | sem a marca de sessão      | a marca de sessão "lembrar depois" fica gravada               |
        | neverShowOnboarding()                  | —               | sem preferência gravada    | a preferência "nunca mostrar" fica gravada para ela           |
        | showOnboardingAgain()                  | —               | preferência "nunca mostrar" gravada | a preferência "nunca mostrar" deixa de valer         |
        | toggle()                               | —               | checklist fechado          | o checklist fica aberto                                       |

    Esquema do Cenário: [CT-43] no /app e no /infra, a ação do checklist montado também é recusada depois do desligar
      Dado a jornada de teste do <painel> e <persona> com o passo "manual-pendente" pendente, com tour
      E o checklist montado no <painel> com o tutorial ligado
      E o tutorial desligado em seguida
      Quando ela chama <acao> no checklist
      Então a resposta é 404
      E as linhas de progresso dela são as mesmas de antes, e o evento de início de tour não foi despachado

      Exemplos:
        | painel | persona | acao                              |
        | /app   | a Ana   | completeStep("manual-pendente")   |
        | /app   | a Ana   | startTour("manual-pendente")      |
        | /infra | a Íris  | completeStep("manual-pendente")   |
        | /infra | a Íris  | startTour("manual-pendente")      |

    Esquema do Cenário: [CT-63] dentro da organização, a ação do checklist montado também é recusada depois do desligar
      Dado a multi-organização ligada, a jornada de teste do /app e a Carla na Acme com o passo "manual-pendente" pendente, com tour
      E o checklist montado em /app/acme com o tutorial ligado
      E o tutorial desligado em seguida
      Quando ela chama <acao> no checklist
      Então a resposta é 404
      E as linhas de progresso dela, nas duas organizações, são as mesmas de antes, e o evento de início de tour não foi despachado

      Exemplos:
        | acao                              |
        | completeStep("manual-pendente")   |
        | startTour("manual-pendente")      |
```

ADV2-10: a linha `/app/{organização}` pedida para o CT-43 virou o CT-63, porque roda na suíte `tests/Tenancy` (outro `TestCase`); o CT-43 fica intacto na suíte Kit.

ADV-01: as linhas de `openMedia`, `videoProgress`, `restoreFlow`, `selectFlow`, `startOnboarding`, `remindMeLater` e `showOnboardingAgain` entraram no CT-13 com o controle no CT-14 (cada efeito acontece com o tutorial ligado). ADV-02: CT-43 (no `/app`, GET real antes do `Livewire::test`, `.ai/rules/testes.md`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M33 | guarda só no `render`/`mount` (o pacote resolve o sujeito por `resolveSubject()`, não por `current()` — `02`) | CT-13 (todas) | 404 e progresso igual; o mutante grava |
| M34 | guarda em `completeStep`/`skipStep`/`undoStep`, esquecendo o tour (`startTour`, `tourProgress`, `finishTour`) | CT-13 (linhas 4–6) | evento não despachado e progresso igual; o mutante despacha/grava |
| M35 | guarda nos verbos de passo, esquecendo os de jornada (`dismissFlow`, `restartFlow`) e de preferência | CT-13 (linhas 9, 11 e 15) | linhas iguais; o mutante grava dispensa, reinício ou preferência |
| M36 | recusa silenciosa (retorna sem gravar, 200) | CT-13 (todas, primeira asserção) | 404; o mutante responde 200 |
| M37 | grava e depois aborta (guarda depois do efeito) | CT-13 (linhas 1, 3, 9) | linhas iguais; o mutante deixa a gravação |
| M38 | recusa também com o tutorial ligado (guarda invertido ou sempre ativo na subclasse) | CT-14 (todas) | o efeito acontece; o mutante responde 404 |
| M118 | guarda nos métodos que gravam progresso, esquecendo os que só despacham evento (`openMedia`) e os de preferência e sessão (`startOnboarding`, `remindMeLater`, `showOnboardingAgain`) — ADV-01 | CT-13 (linhas 7, 13, 14, 16) | evento de mídia não despachado; preferência e sessão iguais; o mutante despacha/grava |
| M119 | `restoreFlow` e `selectFlow` fora da lista de métodos guardados (sobrescrita método a método na subclasse) — ADV-01 | CT-13 (linhas 10 e 12) | 404, jornada continua dispensada e checklist na mesma jornada; o mutante restaura e troca |
| M120 | a subclasse com o guarda registrada só para o `/admin`; `/app` e `/infra` montam o checklist do pacote sem guarda — ADV-02 | CT-43 | 404 e progresso igual; o mutante grava no `/app` e no `/infra` |
| M165 | o guarda decide pelo painel `/app` sem organização (lê a rota sem o parâmetro do tenant) e deixa passar o update de `/app/{organização}` — ADV2-10 | CT-63 | 404 e progresso igual; o mutante grava na Acme |

---

## Regra R9 — Ligado, o checklist, o item "Primeiros passos" e a página aparecem nos três painéis com a jornada do painel, e a boas-vindas não

> `RQ-28`, `RQ-18`, `RQ-22`, `RQ-29`, `P-06` · perfil **padrão** · técnica: **EP por painel**, persona padrão de cada painel, **matriz papel × painel** para a permissão da página (P-06). O tour no navegador é o CT-B01 (`/admin`) e o CT-B02 (`/infra`) no `05`. As contagens do CT-16 pressupõem que a persona padrão abre a tela de todo passo do seu painel — é o que o CT-54 prova (P-11)

```gherkin
  Regra: com o tutorial ligado, cada painel mostra o checklist e a página "Primeiros passos" com a sua jornada, sem a tela de boas-vindas

    Esquema do Cenário: [CT-15] o dashboard de cada painel traz o checklist da jornada daquele painel
      Dado as jornadas do kit semeadas e o tutorial ligado
      E <persona>, que nunca abriu o checklist
      Quando ela abre o dashboard de <painel>
      Então o checklist flutuante está no corpo, com a jornada cujo painel é <painel> e título começando por "Primeiros passos"
      E nenhuma jornada de outro painel é oferecida no checklist
      E o item "Primeiros passos" está na navegação
      E a tela de boas-vindas do pacote não está no corpo

      Exemplos:
        | persona                               | painel |
        | a administradora                      | admin  |
        | a Ana                                 | app    |
        | a Íris                                | infra  |
        | o administrador geral (master_global) | infra  |
        | o administrador geral (master_global) | admin  |

    # (alterado em 2026-10-21: QA-12 do quality gate, ciclo 2)
    Esquema do Cenário: [CT-16] a página "Primeiros passos" mostra a jornada do painel e nenhuma outra
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E <persona>, com as permissões do papel como semeadas
      Quando ela abre /<painel>/onboarding
      Então a resposta é 200 com a Page OnboardingProgress
      E a jornada listada é a do painel <painel>, com exatamente <passos> passos
      E a chave primeiros-passos-<painel> está no corpo
      E as chaves primeiros-passos-<outros painéis> não estão no corpo

      Exemplos:
        | persona            | painel | passos | outros painéis | # origem da contagem                              |
        | a administradora   | admin  | 11     | app, infra     | tabela aprovada                                   |
        | a Ana              | app    | 5      | admin, infra   | tabela aprovada − dashboard dinâmico (P-22)       |
        | a Íris             | infra  | 15     | admin, app     | tabela aprovada                                   |

    Esquema do Cenário: [CT-47] a permissão da página decide quem vê o checklist, e todo papel do kit a recebe no seu painel
      Dado a instalação nova semeada (papéis e jornadas) e o tutorial ligado
      E <persona> com o papel <papel>, <permissao>
      Quando ela abre o dashboard de <painel>
      Então o checklist flutuante está <presenca>
      E o item "Primeiros passos" está <presenca> na navegação

      Exemplos:
        | persona          | papel       | painel | permissao                                          | presenca |
        | a administradora | admin       | admin  | com a permissão da página como semeada             | presente |
        | a Ana            | panel_user  | app    | com a permissão da página como semeada             | presente |
        | a Íris           | infra       | infra  | com a permissão da página como semeada             | presente |
        | a administradora | admin       | admin  | com a permissão da página retirada do papel        | ausente  |
        | a Íris           | infra       | infra  | com a permissão da página retirada do papel        | ausente  |
        | a Ana            | panel_user  | app    | com a permissão da página retirada do papel        | ausente  |

    Esquema do Cenário: [CT-62] com o tutorial ligado e sem a permissão da página, a URL de "Primeiros passos" responde 403
      Dado as jornadas do kit semeadas e o tutorial ligado
      E <persona> com o papel <papel>, com a permissão da página retirada do papel
      Quando ela abre <url>
      Então a resposta é 403
      E o corpo não traz a Page OnboardingProgress

      Exemplos:
        | persona          | papel      | url               |
        | a administradora | admin      | /admin/onboarding |
        | a Ana            | panel_user | /app/onboarding   |
        | a Íris           | infra      | /infra/onboarding |
```

P-06: as linhas "como semeada" provam que a instalação nova concede (nenhum `givePermissionTo` no arranjo); as linhas "retirada" usam `semAPermissao()` e provam que a permissão é quem decide. ADV2-09: linha 6 do CT-47 (`/app`). P-18 (Q27): CT-62 — 403 sem a permissão com o tutorial ligado; o controle 200 é o CT-16 e o 404 desligado é o CT-45. `admin_app` está no CT-37 (só existe com multi-organização). Estouro do teto do perfil padrão em R9 (4 cenários além dos CT-B): CT-62 nasce de premissa decidida no fechamento da revisão e não cabe no `Esquema` do CT-47 (outro `Quando` e outro `Então`).

CT-16 *(alterado em 2026-10-21: QA-12 do quality gate, ciclo 2)*: a linha `/app` passa de 6 a **5** passos — o `panel_user` não pode montar o dashboard dinâmico, e o passo dele não aparece para a Ana (P-22, CT-68); os outros cinco passos do `/app` têm tela que a Ana abre (CT-54, P-11), então o número é exato, sem a faixa `1..6` que o teste usava. O oráculo de exclusão troca o **título** (as três jornadas se chamam "Primeiros passos", e o oráculo por título nunca falhava) pela **chave** `primeiros-passos-{painel}`, que distingue o painel. A asserção positiva da própria chave é controle: sem ela, uma página que não expõe chave nenhuma passa a negativa no vácuo (ver Q?1 do fechamento do ciclo 2). O `Dado` declara as permissões como semeadas porque a contagem do `/app` depende delas (P-11, P-22).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | plugin com consumo só no `/admin` (como hoje) | CT-15 (linhas app e infra) | checklist presente; o mutante não renderiza |
| M40 | jornada semeada com `panel_id` nulo (vale para todos os painéis) | CT-15 (segunda asserção), CT-16 (última) | nenhuma jornada de outro painel; no CT-16, nenhuma chave `primeiros-passos-` de outro painel no corpo; o mutante lista as três em cada painel |
| M41 | `welcome(true)` (o default do pacote) | CT-15 (última asserção) | sem `fio-welcome-layer` para quem nunca abriu; o mutante o renderiza |
| M42 | permissão da página não concedida ao papel `infra` (`skipWhen` pela permissão) | CT-15 e CT-16 (linha infra) | checklist e 200; o mutante esconde e responde 403 |
| M43 | `progressPage()` registrado sem item de navegação | CT-15 (terceira asserção) | item presente; o mutante não lista |
| M121 | `master_global` (sem painel no papel) recebe as três jornadas no checklist de qualquer painel — ADV-34 | CT-15 (linhas 4 e 5) | só a jornada do painel corrente; o mutante oferece as três |
| M122 | a permissão da página não é semeada para os papéis (só gerada pelo Shield) — P-06 | CT-47 (linhas 1–3) | presente; o mutante esconde |
| M123 | o checklist ignora a permissão da página (`skipWhen` só pelo interruptor) — P-06 | CT-47 (linhas 4, 5 e 6) | ausente; o mutante mostra |
| M166 | a permissão da página consultada sempre no painel `admin` (o predicado sem o painel corrente) — ADV2-09 | CT-47 (linha 6) | ausente no `/app` sem a permissão; o mutante mostra |
| M167 | sem a permissão, a página responde 404 (mesmo guarda do tutorial desligado) ou 200 (página do vendor sem barreira) — P-18 | CT-62 | 403; o mutante responde 404 ou 200 |
| M196 | a regra da P-22 (poder montar o dashboard dinâmico) aplicada só no `/admin` e no `/infra`; no `/app` o passo do dinâmico aparece para quem não pode montar — QA-12 | CT-16 (linha `app`) | exatamente 5 passos; o mutante dá 6 |

---

## Regra R10 — Todo passo semeado tem tour, e cada parada aponta para uma tela existente do painel da jornada

> `RQ-18` ("cada seeder filho acrescenta o seu passo, com tour"), `P-03` (falha fechado: "tour que aponta para tela ausente") · perfil **padrão** · técnica: **EP** — todos os passos, nos dois estados de multi-organização (a linha "com" vive no CT-34)

```gherkin
  Regra: todo passo semeado tem tour, e toda parada leva a uma tela que existe no painel da jornada

    Cenário: [CT-17] as paradas de tour de todos os passos semeados resolvem dentro do painel da jornada
      Dado o seeder pai rodado sem multi-organização
      Quando as paradas de tour de cada passo de cada jornada são lidas
      Então todo passo tem ao menos uma parada
      E toda parada com rota nomeia uma rota registrada que pertence ao painel da jornada
      E toda parada com URL começa pelo caminho do painel da jornada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M44 | passo concluído por condição ou visita semeado sem tour ("Perfil e 2FA", "Configurações") | CT-17 | ao menos uma parada; o mutante tem zero |
| M45 | parada do passo do `/app` copiada do `/admin` (rota `filament.admin.…` na jornada do `/app`) | CT-17 | rota do painel da jornada; o mutante aponta outro painel |
| M46 | nome de rota digitado à mão e errado (ou de tela de pacote que mudou de nome) | CT-17 | rota registrada; o mutante nomeia rota inexistente |

---

## Regra R11 — Ligado, as ações do checklist só alcançam as jornadas visíveis da própria pessoa no painel corrente

> `RQ-28`, `RQ-18`, `P-14` · perfil **padrão** · técnica: **EP** com valor fora do domínio e tipo errado por ponto de entrada da `## Superfície Livewire` do `02`, **identidade** (duas pessoas) e **EP por painel** (a mesma pessoa em dois painéis)

```gherkin
  Regra: com o tutorial ligado, o checklist de um painel age só sobre a jornada daquele painel e só para quem o usa

    Esquema do Cenário: [CT-18] entrada fora do domínio no checklist do /admin não grava fora da jornada do /admin
      Dado as jornadas do kit semeadas, o tutorial ligado e a administradora com o checklist montado no /admin
      Quando ela chama <entrada>
      Então <resultado>
      E nenhuma linha de progresso dela existe na jornada do /infra

      Exemplos:
        | entrada                                                            | resultado                                                        | # ponto de entrada         |
        | completeStep(passo de Pulse, "primeiros-passos-infra")             | nada é gravado                                                   | flowKey de outro painel    |
        | completeStep("passo-que-nao-existe")                               | nada é gravado e a resposta não é 500                            | stepKey fora da lista      |
        | selectFlow("primeiros-passos-infra")                               | o checklist continua mostrando a jornada do /admin               | $flowKey sem #[Locked]     |
        | tourProgress(passo de Usuários, -1, 0)                             | nenhum progresso de tour com parada negativa é gravado           | índice fora da faixa       |
        | tourProgress(passo de Usuários, "um", "três")                      | nada é gravado                                                   | tipo errado                |
        | a ação "reiniciar" da página /admin/onboarding com flow = "primeiros-passos-infra" | nada é gravado                                    | $arguments['flow']         |

    Cenário: [CT-19] o passo concluído por uma pessoa não conclui para a outra
      Dado as jornadas do kit semeadas e o tutorial ligado
      E a administradora e o Bruno, os dois no /admin, com o passo de Usuários pendente
      Quando a administradora conclui o passo de Usuários pelo checklist
      Então o passo de Usuários está concluído no checklist dela
      E no checklist do Bruno o passo de Usuários está pendente, e não há linha de progresso dele

    Esquema do Cenário: [CT-48] concluir um passo num painel não conclui o passo de mesmo nome em outro painel
      Dado as jornadas do kit semeadas, o tutorial ligado e todas as features da tabela ligadas
      E a Íris com os papéis infra, panel_user e admin, que entra no /infra, no /app e no /admin, com o passo "Busca ⌘K" pendente nas três jornadas
      Quando ela conclui o passo "Busca ⌘K" pelo checklist do <painel da ação>
      Então o passo "Busca ⌘K" está concluído na jornada do <painel da ação>
      E está pendente na jornada do <outro painel>, sem linha de progresso dela nessa jornada

      Exemplos:
        | painel da ação | outro painel |
        | infra          | app          |
        | app            | infra        |
        | admin          | infra        |
        | infra          | admin        |
```

P-14 (ADV-35). A Íris recebe `panel_user` e `admin` para entrar nos três painéis sem multi-organização; no `/app`, GET real antes do `Livewire::test`. ADV2-16: linhas 3 e 4 (par `/admin` × `/infra`); o papel `admin` acrescentado ao `Dado` não muda as linhas 1 e 2.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M47 | busca do passo em todas as jornadas, não só nas visíveis do painel (subclasse do kit que sobrescreve `findStepState`) | CT-18 (linha 1) | nenhuma linha na jornada do `/infra`; o mutante conclui Pulse |
| M48 | `selectFlow` aceita qualquer chave e o checklist passa a mostrar a jornada do `/infra` | CT-18 (linha 3) | jornada do `/admin`; o mutante mostra a do `/infra` |
| M49 | `tourProgress` grava índice negativo sem faixa | CT-18 (linha 4) | sem parada negativa; o mutante grava |
| M50 | ação da página resolve `$arguments['flow']` sem conferir o painel | CT-18 (linha 6) | nada gravado; o mutante reinicia a jornada do `/infra` |
| M51 | progresso gravado sem o sujeito (ou com o sujeito vindo do cliente) | CT-19 | passo pendente para o Bruno; o mutante o mostra concluído |
| M124 | os passos "todos" semeados numa jornada só, sem painel, e mostrados nos três painéis com o mesmo progresso — ADV-35 | CT-48 | pendente no outro painel; o mutante mostra concluído |
| M125 | passo de mesma chave nas três jornadas resolvido pela chave sem a jornada (o primeiro achado é concluído) — ADV-35 | CT-48 (linha 2) | concluído no `/app` e pendente no `/infra`; o mutante conclui na jornada errada |
| M168 | `/admin` e `/infra` compartilham a jornada (os passos "todos" semeados com `panel_id` só para `app` e os outros dois juntos) — ADV2-16 | CT-48 (linhas 3 e 4) | pendente no outro painel; o mutante conclui nos dois |

---

## Regra R12 — O seeder pai cria uma jornada por painel com os passos da tabela aprovada, no modo de conclusão dela

> `RQ-02`, `RQ-04`, `RQ-18`, `RQ-23`, `RQ-09` · perfil **completo** · técnica: **EP por painel** com contagem por modo de conclusão e âncora de tela. Contagens tiradas da tabela aprovada (Adendo 2): `/admin` = 5 "todos" + Usuários, Convites, Papéis, Configurações, Agentes de IA + Hub = 11; `/app` = 5 + Hub = 6; `/infra` = 5 + 10 = 15. `@premissa` de mecanismo: "tour" e "manual (tour)" da tabela viram `manual` com tour (ver `## Fronteira com o Plano`)

```gherkin
  Regra: o seeder pai cria uma jornada "Primeiros passos" por painel, com um passo por feature qualificada, no modo de conclusão da tabela aprovada

    Esquema do Cenário: [CT-20] sem multi-organização, cada painel recebe a sua jornada com os passos da tabela
      Dado o banco sem jornadas, passos nem condições e a multi-organização desligada
      Quando o seeder pai roda
      Então existem exatamente 3 jornadas, uma com o painel <painel>
      E a jornada de <painel> tem <total> passos: <condicao> por condição, <visita> por visita e <manual> manuais
      E as paradas de tour da jornada alcançam as telas <ancoras>
      E nenhum passo alcança telas que só existem com multi-organização

      Exemplos:
        | painel | total | condicao | visita | manual | ancoras                                                                                                                      |
        | admin  | 11    | 2        | 1      | 8      | Usuários, Convites, Papéis, Configurações da aplicação, Agentes de IA, Hub de administração, Meu perfil, dashboard             |
        | app    | 6     | 1        | 0      | 5      | Hub do negócio, Meu perfil, dashboard                                                                                         |
        | infra  | 15    | 1        | 0      | 14     | Hub de infraestrutura, Pulse, Backups, Logs, Exceções, Filas, Auditoria, Execuções de IA, Lixeira, Central de comandos, Meu perfil, dashboard |

    Cenário: [CT-59] todo valor semeado em coluna de texto com limite cabe no limite da coluna
      Dado o seeder pai rodado
      E o limite de cada coluna string de jornadas, passos e condições, tal como declarado nas migrations de onboarding do kit
      Quando o comprimento de cada valor gravado nessas colunas é comparado ao limite declarado da sua coluna
      Então nenhum valor ultrapassa o limite declarado
      E nenhum valor gravado termina truncado em relação ao valor que o seeder declarou
```

CT-59 (ADV-38, ADV2-01): o limite vem das migrations `database/migrations/2026_08_12_164950_create_onboarding_tables.php`, `…164952_add_visibility_to_onboarding.php` e `…164955_create_onboarding_conditions.php` — constante do teste derivada delas: `string('x')` sem comprimento = 255, `string('x', 2048)` = 2048 —, **nunca** do schema do banco de teste (o SQLite não impõe nem reporta o limite do `varchar`, e o teste passaria vácuo). Colunas `json` (títulos, descrições, paradas de tour) não têm teto e ficam fora. A segunda asserção pega o banco que trunca em silêncio.

"Telas que só existem com multi-organização": `TenantResource` no `/admin`; `UserResource`, `ConviteResource`, `ConvitesRecebidos` e `ProjetoResource` do `/app`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M52 | uma jornada por feature, não por painel | CT-20 | 3 jornadas; o mutante cria dezenas |
| M53 | seeders de organização chamados sempre | CT-20 (linhas admin e app) | 11 e 6 passos e nenhuma tela de organização; o mutante dá 12 e 11 |
| M54 | os passos "todos" semeados só no `/admin` | CT-20 (linhas app e infra) | 6 e 15; o mutante dá 1 e 10 |
| M55 | "Hub (cards)" semeado só no `/admin` | CT-20 (linha app) | 6 e âncora Hub do negócio; o mutante dá 5 |
| M56 | "Configurações" com modo manual (ou "Usuários" por visita) | CT-20 (linha admin), CT-26 (linha 1) | 1 por visita e 8 manuais; o mutante dá 0/9 ou 2/7; e a visita não conclui |
| M57 | "Agentes de IA" ou "Execuções de IA" no painel errado | CT-20 (linhas admin e infra) | 11 e 15 com as âncoras; o mutante dá 10/16 |
| M126 | chave de passo montada pelo título longo (slug do texto pt_BR) ou `condition_key`/`visibility_condition` com o nome descritivo, acima de 255 caracteres — no SQLite passa, no MySQL/PostgreSQL estoura — ADV-38 | CT-59 | comprimento dentro do limite declarado na migration (255/2048); o mutante passa — e só é pego porque o limite vem da migration, não do schema SQLite (ADV2-01) |

---

## Regra R13 — Cada feature é um seeder filho próprio, que sozinho cria só o seu passo

> `RQ-05` · perfil **completo** · técnica: **EP por filho** (um filho de cada forma: os três painéis, um painel só, dois painéis, o `/infra`)

```gherkin
  Regra: cada feature tem o seu seeder filho, e ele sozinho acrescenta só o passo da sua feature

    Esquema do Cenário: [CT-21] o seeder filho rodado sozinho cria só o passo da sua feature
      Dado o banco sem jornadas nem passos
      Quando o seeder filho <filho> roda sozinho
      Então existem exatamente <total> passos no banco
      E <onde>

      Exemplos:
        | filho                  | total | onde                                                                         |
        | BuscaGlobalSeeder      | 3     | um em cada jornada (admin, app, infra), nenhum com tela de outra feature     |
        | UsuariosSeeder         | 1     | na jornada do /admin, com parada na lista de Usuários                         |
        | HubDeNavegacaoSeeder   | 2     | um na jornada do /admin e um na do /app, nenhum na do /infra                  |
        | PulseSeeder            | 1     | na jornada do /infra, com parada no Pulse                                     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M58 | um seeder só com tudo, e os "filhos" como apelidos dele | CT-21 (todas) | 1–3 passos; o mutante cria 32 |
| M59 | o filho cria os passos e o pai é quem cria a jornada (filho sozinho falha por jornada ausente) | CT-21 (todas) | o passo existe numa jornada do painel; o mutante lança exceção ou não cria |
| M60 | filho de feature de dois painéis acrescenta o passo nos três | CT-21 (linha Hub) | nenhum no `/infra`; o mutante cria 3 |

---

## Regra R14 — Semear não toca o interruptor

> `RQ-21` · perfil **completo** · técnica: **EP** (estado prévio do interruptor)

```gherkin
  Regra: rodar o seeder pai não liga nem desliga o tutorial

    Esquema do Cenário: [CT-22] o interruptor gravado continua o mesmo depois de semear
      Dado o tutorial gravado como <antes> e alinhado na config
      Quando o seeder pai roda
      Então o valor gravado no banco é <antes>
      E a config efetiva é <antes>

      Exemplos:
        | antes |
        | false |
        | true  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M61 | o seeder liga o tutorial "para a pessoa ver o que semeou" | CT-22 (linha false) | gravado `false`; o mutante grava `true` |
| M62 | o seeder grava `false` explícito ("só semeia: continua desligado") | CT-22 (linha true) | gravado `true`; o mutante grava `false` |
| M109 | o seeder esquece a instância de settings e realinha a config pelo `.env` (o `.env` de teste não liga o tutorial) | CT-22 (linha true, segunda asserção) | config efetiva `true`; o mutante a devolve a `false` |

---

## Regra R15 — As condições de que os passos dependem existem depois de semear e decidem a conclusão

> `RQ-03`, `RQ-23`, `P-07` · perfil **completo** · técnica: **EP** (situações do sujeito; estados do convite), **BVA 2-valores** na contagem de convites (0, 1), **identidade** (quem convidou), **painel** (a condição vale nos três painéis), estado do tutorial na visita

```gherkin
  Regra: depois de semear, toda condição citada por um passo existe, e ela decide o passo para a pessoa certa

    Cenário: [CT-23] toda chave de condição citada pelos passos semeados resolve no painel do passo
      Dado o seeder pai rodado sem multi-organização
      Quando as chaves de condição de conclusão e de visibilidade de cada passo e de cada jornada são listadas
      Então cada chave existe como condição ativa gravada ou como condição registrada no painel da jornada do passo

    Esquema do Cenário: [CT-24] o passo "Perfil e 2FA" conclui só com o segundo fator confirmado, em cada painel
      Dado as jornadas do kit semeadas e o tutorial ligado
      E <persona> no <painel> com <dois fatores>
      Quando o checklist de <painel> é montado para ela
      Então o passo "Perfil e 2FA" está <estado>

      Exemplos:
        | persona          | painel | dois fatores                         | estado     |
        | a administradora | admin  | nenhum                               | pendente   |
        | a administradora | admin  | configurado e ainda não confirmado   | pendente   |
        | a administradora | admin  | confirmado                           | concluído  |
        | a Ana            | app    | confirmado                           | concluído  |
        | a Íris           | infra  | confirmado                           | concluído  |

    Esquema do Cenário: [CT-25] o passo "Convites" do /admin conclui quando a própria pessoa já convidou alguém
      Dado as jornadas do kit semeadas e o tutorial ligado
      E <convites> no banco
      Quando o checklist do /admin é montado para a administradora
      Então o passo "Convites" está <estado>

      Exemplos:
        | convites                                           | estado    | # partição            |
        | nenhum convite                                     | pendente  | 0 (borda−1)           |
        | 1 convite enviado pela administradora              | concluído | 1 (borda)             |
        | 1 convite enviado pelo Bruno, nenhum por ela       | pendente  | identidade            |
        | 1 convite enviado por ela, ainda Pendente          | concluído | estado do convite     |
        | 1 convite enviado por ela, Aceito                  | concluído | estado do convite     |
        | 1 convite enviado por ela, Recusado                | concluído | estado do convite     |
        | 1 convite enviado por ela, Expirado                | concluído | estado do convite     |

    Esquema do Cenário: [CT-26] o passo "Configurações da aplicação" conclui pela visita à tela só com o tutorial ligado
      Dado as jornadas do kit semeadas e a administradora com o passo "Configurações da aplicação" pendente
      E o tutorial <estado do tutorial>
      Quando ela abre <tela>
      Então o passo "Configurações da aplicação" está <estado> para ela, lido com o tutorial ligado
      E <progresso>

      Exemplos:
        | estado do tutorial | tela                                   | estado    | progresso                                              |
        | ligado             | /admin/configuracoes-da-aplicacao      | concluído | existe a linha de progresso dela para o passo          |
        | ligado             | /admin/users                           | pendente  | não existe linha de progresso dela para o passo        |
        | desligado          | /admin/configuracoes-da-aplicacao      | pendente  | não existe linha de progresso dela para o passo        |
```

ADV-15: "já convidou alguém" vale para o convite em qualquer estado do glossário (Pendente, Aceito, Recusado, Expirado) — o estado do convite não é discriminante do passo. P-07: a linha "desligado" lê o estado religando o tutorial **depois** da visita, sem nova visita.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M63 | condição de convites citada pelo passo e não semeada (nem registrada) | CT-23, CT-25 (linha 2) | a chave resolve; concluído; o mutante esconde o passo (chave não registrada esconde) |
| M64 | condições em código registradas só no plugin do `/admin` | CT-23, CT-24 (linhas app e infra) | concluído no `/app` e no `/infra`; o mutante deixa pendente ou esconde |
| M65 | 2FA decidido pelo segredo gravado, não pela confirmação | CT-24 (linha 2) | pendente; o mutante conclui |
| M66 | condição de convites sem a coluna do sujeito (qualquer convite conta) | CT-25 (linha 3) | pendente; o mutante conclui |
| M67 | mínimo 2 ou comparação `>` no lugar de `>=` 1 | CT-25 (linha 2) | concluído com 1; o mutante deixa pendente |
| M68 | URL de visita com casamento por prefixo do painel (`/admin`) | CT-26 (linha 2) | pendente em `/admin/users`; o mutante conclui |
| M127 | condição de convites com filtro de estado (só Aceito, ou só não Expirado) — ADV-15 | CT-25 (linhas 4, 6, 7) | concluído; o mutante deixa pendente |
| M128 | a visita é registrada por middleware de painel que não consulta o interruptor — P-07 | CT-26 (linha 3) | pendente e sem linha; o mutante grava a conclusão |

O mutante "passo de Configurações semeado como manual" é o M56 (R12), morto também pelo CT-26 linha 1 (concluído pela visita; o mutante segue pendente).

---

## Regra R16 — Passo de feature desligada não aparece nem conta no percentual; volta ao religar, com o progresso

> `P-03`, `P-13`, `RQ-23` (coluna "Some quando…"), `P-02` (widget no CT-56) · perfil **completo** · técnica: **EP por decisor × painel** + **2-switch** + **BVA** na borda de 100 % (escalada, ver `## Perfil de Derivação`)

```gherkin
  Regra: enquanto a feature de um passo estiver desligada no painel, o passo não aparece no checklist nem conta no percentual

    Esquema do Cenário: [CT-27] desligar a feature esconde exatamente o passo dela no painel
      Dado as jornadas do kit semeadas sem multi-organização e o tutorial ligado
      E todas as features da tabela ligadas, exceto <desligada>
      Quando o checklist de <painel> é montado para <persona>
      Então a jornada tem <visiveis> passos visíveis
      E o passo de <feature> está <presenca>

      Exemplos:
        | desligada                                        | painel | persona          | visiveis | feature                | presenca |
        | nenhuma                                          | admin  | a administradora | 11       | Dock de atalhos        | visível  |
        | o dock fora do /admin (ligado no /app e /infra)  | admin  | a administradora | 10       | Dock de atalhos        | ausente  |
        | o dock fora do /infra (ligado no /admin)         | admin  | a administradora | 11       | Dock de atalhos        | visível  |
        | o bloqueio de tela                               | admin  | a administradora | 10       | Bloqueio de tela       | ausente  |
        | o dashboard dinâmico no /admin                   | admin  | a administradora | 10       | Dashboard dinâmico     | ausente  |
        | o hub de cartões                                 | admin  | a administradora | 10       | Hub (cards)            | ausente  |
        | os agentes de IA                                 | admin  | a administradora | 10       | Agentes de IA          | ausente  |
        | nenhuma                                          | infra  | a Íris           | 15       | Central de comandos    | visível  |
        | a central de comandos                            | infra  | a Íris           | 14       | Central de comandos    | ausente  |
        | o dock fora do /infra (ligado no /admin)         | infra  | a Íris           | 14       | Dock de atalhos        | ausente  |
        | o dashboard dinâmico só no /infra                | infra  | a Íris           | 14       | Dashboard dinâmico     | ausente  |
        | o dashboard dinâmico só no /infra                | admin  | a administradora | 11       | Dashboard dinâmico     | visível  |
        | os agentes de IA                                 | infra  | a Íris           | 15       | Execuções de IA        | visível  |
        | nenhuma                                          | app    | a Ana            | N₀ (controle do /app) | Hub (cards) | visível  |
        | o hub de cartões                                 | app    | a Ana            | N₀ − 1   | Hub (cards)            | ausente  |
        | o dock fora do /app (ligado no /admin e /infra)  | app    | a Ana            | N₀ − 1   | Dock de atalhos        | ausente  |
        | o dashboard dinâmico só no /app                  | app    | o administrador geral | N₀′ − 1 (controle dele no /app) | Dashboard dinâmico | ausente  |
        | o dashboard dinâmico só no /app                  | admin  | a administradora | 11       | Dashboard dinâmico     | visível  |

    Cenário: [CT-28] o passo escondido sai do percentual e volta concluído ao religar a feature
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features ligadas no /admin
      E a administradora concluiu os passos "Dock de atalhos" e "Usuários"
      Quando o dock sai do /admin, o checklist é montado, o dock volta ao /admin e o checklist é montado de novo
      Então com o dock fora a jornada mostra 1 concluído de 10 visíveis
      E com o dock de volta a jornada mostra 2 concluídos de 11 visíveis, e o passo "Dock de atalhos" concluído

    Cenário: [CT-56] com todos os passos visíveis concluídos e um escondido pendente, a jornada está concluída
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e o dock fora do /admin
      E a administradora concluiu os 10 passos visíveis da jornada do /admin, e o passo "Dock de atalhos" (escondido) nunca foi feito
      Quando o checklist do /admin é montado para ela
      Então a jornada dela mostra 10 concluídos de 10 visíveis e está concluída

    Cenário: [CT-60] o widget de progresso conta como concluída a jornada com um passo escondido pendente
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e o dock fora do /admin
      E a administradora concluiu os 10 passos visíveis da jornada do /admin, e o passo "Dock de atalhos" (escondido) nunca foi feito
      Quando o administrador geral abre o dashboard do /admin
      Então o widget "Jornadas de onboarding concluídas" conta a jornada dela como concluída

    Cenário: [CT-61] a jornada concluída continua concluída quando o passo escondido volta a aparecer
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e o dock fora do /admin
      E a administradora concluiu os 10 passos visíveis da jornada do /admin, que ficou concluída, e o passo "Dock de atalhos" nunca foi feito
      Quando o dock volta ao /admin e o checklist do /admin é montado para ela
      Então a jornada continua concluída, com 10 concluídos de 11 visíveis
      E o passo "Dock de atalhos" aparece pendente
      E o widget "Jornadas de onboarding concluídas", aberto pelo administrador geral, continua contando a jornada dela como concluída
```

ADV2-05: as linhas 14–18 do CT-27 cobrem o `/app` com a Ana, exceto a 17: o `panel_user` não pode montar o dashboard e nunca vê esse passo (P-22), então a linha 17 usa o administrador geral, contado contra o próprio controle; a contagem do `/app` é relativa ao controle da própria tabela (`N₀`, linha 14) porque o número absoluto depende de quais telas do `/app` o `panel_user` abre (P-11, ADV2-13) — `@premissa P-11`. ADV2-14: o CT-56 ficou com um `Quando` só; a contagem do widget foi para o CT-60. P-19 (Q28): CT-61, 2-switch a partir do estado do CT-56.

ADV-05/06/22: as linhas 10–12 do CT-27 usam a persona do outro painel (a Íris no `/infra` com o dock só no `/admin`; o dinâmico só no `/infra`, visto do `/infra` e do `/admin`). P-13: a linha 13 prova que "Execuções de IA" **não** some com os agentes desligados. ADV-07: CT-56 é a borda de 100 % — o passo escondido e pendente não impede a conclusão (o widget, no CT-60).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M70 | condição de visibilidade do dock global (qualquer painel com dock), não do painel corrente | CT-27 (linhas 2 e 3) | linha 2: 10 e ausente; linha 3: 11 e visível — o global erra uma das duas |
| M71 | condições de visibilidade citadas e não registradas (a chave desconhecida esconde todo passo que a cita) | CT-27 (linhas 1 e 8) | 11 e 15; o mutante dá 6 e 14 |
| M72 | condição invertida (passo aparece só com a feature desligada) | CT-27 (linhas 2, 4–7, 9) | ausente; o mutante mostra |
| M73 | passo escondido continua no total do percentual | CT-27 (linhas 2, 4–7, 9) e CT-28 | 10 visíveis / "de 10"; o mutante conta 11 |
| M74 | passo escondido e concluído conta no numerador | CT-28 (primeira asserção) | 1 concluído; o mutante dá 2 |
| M75 | esconder apaga o progresso do passo (ou o seeder/condição o "reinicia") | CT-28 (segunda asserção) | 2 de 11 e "Dock" concluído; o mutante dá 1 de 11 |
| M129 | condição de visibilidade lê a lista do dock/dinâmico do `/admin` em todo painel (painel corrente fixo) — ADV-05/06/22 | CT-27 (linhas 10, 11, 12) | `/infra` 14 e ausente; `/admin` 11 e visível; o mutante inverte |
| M130 | "Execuções de IA" ganha a visibilidade dos agentes de IA, como o catálogo do `/admin` — P-13 | CT-27 (linha 13) | 15 e visível; o mutante dá 14 e ausente |
| M131 | a jornada só fica concluída com **todos** os passos concluídos, inclusive os escondidos — ADV-07 | CT-56, CT-60 | concluída (CT-56) e contada no widget (CT-60); o mutante a deixa em andamento (10 de 11) |
| M159 | condição de visibilidade do hub ou do dock lida só para `/admin` e `/infra` (o `/app` sem a closure) — ADV2-05 | CT-27 (linhas 15–17) | `N₀ − 1` e ausente no `/app`; o mutante mantém `N₀` |
| M160 | o widget conta a jornada pela tabela de progresso de passos (todos os passos da jornada), não pelo estado de conclusão — ADV2-14 | CT-60 | contada como concluída; o mutante não conta |
| M161 | a conclusão da jornada é recalculada a cada montagem: o passo que volta a aparecer a reabre — P-19 | CT-61 (primeira e última asserções) | continua concluída e contada; o mutante a reabre (10 de 11, em andamento) e o widget baixa |
| M162 | ao reaparecer, o passo escondido é dado como concluído porque a jornada está concluída — P-19 | CT-61 (segunda asserção) | pendente; o mutante o marca concluído |

---

## Regra R17 — Re-semear cria só o que falta e nunca sobrescreve

> `RQ-25` · perfil **completo** · técnica: **idempotência ancorada no agregado persistido** (contagens e conteúdo do banco, não o retorno do seeder) + **EP** por tipo de edição e por tipo de ausência

```gherkin
  Regra: rodar o seeder pai de novo cria só a jornada, o passo e a condição que faltam, e nunca sobrescreve o que existe

    Cenário: [CT-29] o seeder pai rodado duas vezes deixa o banco igual ao da primeira vez
      Dado o seeder pai rodado uma vez sem multi-organização
      E a fotografia de jornadas, passos e condições (contagem e conteúdo)
      Quando o seeder pai roda de novo
      Então jornadas, passos e condições são iguais à fotografia, com 3 jornadas e 32 passos
      E nenhuma chave de passo se repete dentro de uma jornada

    Esquema do Cenário: [CT-30] o que o administrador editou sobrevive ao re-semear
      Dado o seeder pai rodado uma vez sem multi-organização
      E <edicao>
      Quando o seeder pai roda de novo
      Então <conferido> continua com o valor editado

      Exemplos:
        | edicao                                                                     | conferido                                   |
        | o título pt_BR da jornada do /admin trocado para "Comece por aqui"         | o título pt_BR da jornada do /admin          |
        | o título en do passo de Usuários trocado para "Your team"                  | o título en do passo de Usuários             |
        | as paradas de tour do passo de Pulse reduzidas a uma parada                | as paradas de tour do passo de Pulse (1)     |
        | o passo "Busca ⌘K" do /app desativado                                      | o passo "Busca ⌘K" do /app (inativo)         |
        | o passo de Usuários movido para a última posição da jornada do /admin       | a posição do passo de Usuários               |
        | o rótulo pt_BR de uma condição gravada pelo seeder trocado para "Convidou" | o rótulo pt_BR dessa condição                |
        | a jornada do /infra desativada                                             | a jornada do /infra (inativa)                |
        | uma condição gravada pelo seeder desativada                                | essa condição (inativa)                      |
        | o modo de conclusão do passo de Usuários trocado para "por visita" de /admin/users | o modo do passo de Usuários (visita) e a URL de visita |
        | um passo novo "Relatórios" criado na autoria na jornada do /admin           | o passo "Relatórios" (existe, com o texto dele) e a jornada do /admin com 12 passos |

    Esquema do Cenário: [CT-31] o que falta é recriado, e o resto fica como está
      Dado o seeder pai rodado uma vez sem multi-organização
      E o título pt_BR da jornada do /admin editado para "Comece por aqui"
      E <ausencia>
      Quando o seeder pai roda de novo
      Então <resultado>
      E o título pt_BR da jornada do /admin continua "Comece por aqui", e ela é a única jornada do /admin

      Exemplos:
        | ausencia                                         | resultado                                                                 |
        | o passo de Usuários apagado                      | o passo de Usuários existe de novo na jornada do /admin, com 11 passos   |
        | a jornada do /infra apagada (com os seus passos) | a jornada do /infra existe de novo com 15 passos                          |
        | uma condição gravada pelo seeder apagada         | a condição existe de novo, ativa                                          |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M76 | `create()` sem conferir a chave (duplica ou estoura a unicidade) | CT-29 | 3 jornadas e 32 passos, sem chave repetida; o mutante dobra ou lança |
| M77 | `updateOrCreate` na jornada (o padrão dos outros seeders do kit) | CT-30 (linha 1), CT-31 (última asserção) | "Comece por aqui"; o mutante volta ao texto do seeder |
| M78 | `updateOrCreate` nos passos (jornada preservada, passos regravados) | CT-30 (linhas 2–5) | valores editados; o mutante restaura título, paradas, ativo e ordem |
| M79 | `updateOrCreate` nas condições | CT-30 (linha 6) | "Convidou"; o mutante restaura o rótulo |
| M80 | filho pula tudo quando a jornada já existe ("já semeado") | CT-31 (linhas 1 e 2 com a jornada do /admin existente) | passo de Usuários recriado; o mutante não recria |
| M81 | passo faltante cria uma jornada nova com outra chave em vez de entrar na existente | CT-31 (linha 1, última asserção) | uma jornada só no `/admin`; o mutante cria a segunda |
| M132 | `firstOrCreate` com `is_active` na parte de busca (`['key' => …, 'is_active' => true]`): a jornada ou a condição desativada não é achada e nasce uma segunda, ativa — ADV-08 | CT-30 (linhas 7 e 8) | a desativada continua inativa e única; o mutante cria a ativa ao lado |
| M133 | busca do passo pela chave **e** pelo modo de conclusão: o passo com modo trocado não é achado e é recriado em duplicidade (ou o modo é "reparado") — ADV-09 | CT-30 (linha 9) | modo visita e uma linha só; o mutante restaura `manual` ou duplica |
| M134 | o pai "sincroniza" a jornada com a lista do seeder, apagando o passo que não conhece — ADV-09 | CT-30 (linha 10) | "Relatórios" existe e 12 passos; o mutante apaga |

---

## Regra R18 — Todo texto semeado existe em pt_BR e en, e a autoria grava os dois

> `P-01` · perfil **completo** · técnica: **EP** (todos os textos) + **gate de tela de escrita** da linha "Autoria" da `## Superfície de UI`

```gherkin
  Regra: o texto semeado é escrito em pt_BR e en, e os dois idiomas são editáveis no painel de autoria

    Cenário: [CT-32] jornadas, passos, paradas de tour e condições semeadas têm texto nos dois idiomas
      Dado o seeder pai rodado sem multi-organização
      Quando os textos traduzíveis de jornadas, passos, paradas de tour e condições são lidos por idioma
      Então todos têm texto não vazio em pt_BR e em en
      E o título de cada jornada, a descrição de cada passo e o corpo de cada parada de tour diferem entre pt_BR e en

    Esquema do Cenário: [CT-33] a administradora edita os dois idiomas de <registro> semeado pela autoria
      Dado as jornadas do kit semeadas, o tutorial ligado e a administradora com as permissões de autoria
      Quando ela preenche, em <tela>, <campo> pt_BR "Comece por aqui" e en "Start here", e salva
      Então <registro> tem gravados <campo> pt_BR "Comece por aqui" e en "Start here"
      E a chave, o painel e os demais campos de <registro> não mudaram, e a jornada do /admin continua com 11 passos

      Exemplos:
        | registro                         | tela                                                   | campo     |
        | a jornada do /admin              | a edição da jornada                                    | o título  |
        | o passo de Usuários              | a edição do passo no gerenciador de passos da jornada  | o título  |
        | uma condição gravada pelo seeder | a edição da condição                                   | o rótulo  |
```

ADV-19: títulos de passo e de parada podem coincidir nos dois idiomas por nome próprio ("Pulse"); a diferença é afirmada na descrição do passo e no corpo da parada, que são frases. ADV-20 e ADV-33: CT-33 afirma o que **não** muda e cobre passo e condição (gate de tela de escrita das três telas de autoria).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M82 | texto só em pt_BR (string simples ou só a chave `pt_BR`) | CT-32 | `en` não vazio; o mutante não tem |
| M83 | `en` copiado do `pt_BR` | CT-32 (segunda asserção) | títulos diferentes; o mutante iguala |
| M84 | paradas de tour com `title`/`body` monolíngues | CT-32 | as paradas também têm os dois idiomas; o mutante não |
| M85 | `filament-onboarding.locales` continua `['en']`: o campo pt_BR não existe na autoria | CT-33 | pt_BR "Comece por aqui"; o mutante não grava (campo ausente) |
| M135 | `en` do passo e da parada copiado do `pt_BR` (só a jornada traduzida) — ADV-19 | CT-32 (segunda asserção) | descrição e corpo diferentes; o mutante iguala |
| M136 | salvar a jornada pela autoria regrava a chave a partir do título (slug), ou zera o painel — ADV-20 | CT-33 (linha 1, segunda asserção) | chave e painel iguais; o mutante muda |
| M137 | o gerenciador de passos ou a edição de condição grava só o idioma corrente da tela — ADV-33 | CT-33 (linhas 2 e 3) | os dois idiomas gravados; o mutante perde um |

---

## Regra R19 — Com multi-organização, os passos só de organização são semeados e o consumo funciona no `/app/{organização}`

> `RQ-09`, `RQ-24`, `RQ-25`, `RQ-28`, `P-03`, `P-06`, `P-08` · perfil **completo** · técnica: **EP** (multi-organização ligada) + **2-switch** (ligada depois de semear). Suíte `tests/Tenancy`. Modo de conclusão dos passos de organização: P-08 (só "Convites" do `/app` por condição; Organizações, Membros, Convites recebidos, Troca de organização e Projetos manuais com tour)

```gherkin
  Regra: com o multi-tenant ligado, as features que só existem com ele são semeadas, e o tutorial funciona dentro da organização

    Cenário: [CT-34] com multi-organização, o /admin e o /app ganham os passos só de organização
      Dado o banco sem jornadas, a multi-organização ligada e a demo desligada no momento de semear
      Quando o seeder pai roda
      Então a jornada do /admin tem 12 passos (2 por condição, 1 por visita, 9 manuais), um deles com parada na tela de Organizações
      E a jornada do /app tem 11 passos (2 por condição — Perfil e 2FA, Convites —, 0 por visita, 9 manuais), com paradas em Membros, Convites, Convites recebidos e Projetos, e um passo de troca de organização
      E a jornada do /infra tem 15 passos, e todo passo de toda jornada tem ao menos uma parada de tour que resolve no painel da jornada
      E, com a Acme aberta, toda parada da jornada do /app gera endereço dentro de /app/acme

    Cenário: [CT-35] ligar a multi-organização depois e re-semear acrescenta só os passos de organização
      Dado o seeder pai rodado com a multi-organização desligada
      E o título pt_BR da jornada do /admin editado para "Comece por aqui"
      Quando a multi-organização é ligada e o seeder pai roda de novo
      Então a jornada do /admin tem 12 passos e a do /app tem 11, e continua havendo uma jornada por painel
      E o título pt_BR da jornada do /admin continua "Comece por aqui"

    Esquema do Cenário: [CT-36] o passo de Projetos aparece no /app da organização só com a demo ligada
      Dado as jornadas semeadas com multi-organização, o tutorial ligado e todas as outras features ligadas
      E a Carla na Acme e a demo <demo>
      Quando o checklist do /app/acme é montado para ela
      Então a jornada tem <visiveis> passos visíveis e o passo de Projetos está <presenca>

      Exemplos:
        | demo      | visiveis                         | presenca |
        | ligada    | N₁ (controle da Carla na Acme)   | visível  |
        | desligada | N₁ − 1                           | ausente  |

    Esquema do Cenário: [CT-37] dentro da organização, o checklist e a página seguem o interruptor
      Dado as jornadas semeadas com multi-organização e todas as features da tabela ligadas
      E <persona> na Acme, com passos pendentes
      E o tutorial <estado>
      Quando ela abre <tela>
      Então <resultado>

      Exemplos:
        | persona                   | estado    | tela                   | resultado                                                              |
        | a Carla (panel_user)      | ligado    | /app/acme              | o checklist está no corpo, com a jornada do /app                       |
        | a Carla (panel_user)      | ligado    | /app/acme/onboarding   | 200 com a Page OnboardingProgress e a jornada do /app, só com passos cuja tela ela abre e sem o passo do dashboard dinâmico (P-22) *(alterado em 2026-10-21: Q31)* |
        | o Davi (admin_app)        | ligado    | /app/acme              | o checklist está no corpo, com a jornada do /app                       |
        | a Carla (panel_user)      | desligado | /app/acme              | o checklist não está no corpo, nem o item "Primeiros passos"           |
        | a Carla (panel_user)      | desligado | /app/acme/onboarding   | 404                                                                    |
```

P-06: a linha `admin_app` prova que o papel que só existe com multi-organização recebe a permissão da página na instalação nova. P-04: a última linha é o 404 da página dentro da organização. ADV2-13 (`@premissa P-11`): o CT-36 e a linha 2 do CT-37 não afirmam número absoluto para `panel_user` — quantos passos do `/app` ela vê depende de quais telas o papel abre (P-11); o CT-36 compara com o controle da própria tabela (`N₁`) e o CT-37 afirma a invariante, cujo número fica para o exemplo derivado da matriz de papéis, que a sessão confere na implementação. ADV-13/23: o CT-34 afirma as paradas resolvidas dentro da organização aberta. ADV-14: contagem por modo nas duas jornadas que mudam com multi-organização.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M86 | seeders de organização nunca chamados (condição lida da chave errada) | CT-34 | 12 e 11; o mutante dá 11 e 6 |
| M87 | Projetos semeado só se a demo estiver ligada na hora de semear (decisão no seeder em vez de visibilidade) | CT-34 (demo desligada ao semear), CT-36 | 11 passos no `/app` no banco (CT-34); o mutante dá 10, e a linha "ligada" do CT-36 fica sem o passo |
| M88 | passo de organização no painel errado (Organizações no `/app`) | CT-34 | 12/11 com as âncoras; o mutante desloca a contagem |
| M89 | re-semear com multi-organização recria as jornadas (`updateOrCreate`) ao acrescentar | CT-35 | "Comece por aqui"; o mutante restaura |
| M90 | visibilidade de Projetos não registrada no `/app` | CT-36 (linha ligada) | `N₁` e visível; o mutante esconde (e as duas linhas ficam iguais) |
| M91 | o guarda do interruptor só no `/app` sem organização na rota | CT-37 (linhas desligado) | checklist ausente e 404; o mutante renderiza em `/app/acme` |
| M138 | parada de tour do `/app` com URL fixa sem a organização (`/app/users`) ou rota sem o parâmetro do tenant — ADV-13/23 | CT-34 (última asserção) | endereço sob `/app/acme`; o mutante gera fora da organização (ou estoura por parâmetro ausente) |
| M139 | os quatro passos de organização do `/app` todos por condição, ou "Convites" do `/app` manual — ADV-14, P-08 | CT-34 (contagem por modo do `/app`) | 2 por condição e 9 manuais; o mutante dá 5/6 ou 1/10 |
| M140 | a permissão da página não concedida ao `admin_app` (papel criado só no ramo de multi-organização do semeador de papéis) — P-06 | CT-37 (linha 3) | checklist presente; o mutante esconde |

---

## Regra R20 — O `kit:install` semeia as jornadas só por escolha, sem ligar o interruptor

> `RQ-06`, `RQ-19`, `RQ-20`, `RQ-21`, `P-10` · perfil **padrão** · técnica: **tabela de decisão** (modo × resposta), uma linha por combinação

```gherkin
  Regra: o kit:install pergunta "Popular os tutoriais guiados?" com padrão não, aceita --onboarding no modo não interativo, e nunca liga o tutorial

    Esquema do Cenário: [CT-38] o kit:install semeia as jornadas conforme o modo e a resposta, e o tutorial fica desligado
      Dado um diretório de instalação novo do kit, sem multi-organização
      Quando o kit:install roda <modo>
      Então existem <jornadas> jornadas e <passos> passos de onboarding no banco
      E <pergunta>
      E o tutorial gravado no banco é false, e a linha ativa da variável do tutorial no .env da instalação não é true

      Exemplos:
        | modo                                                               | jornadas | passos | pergunta                                                                     |
        | interativo, com a resposta padrão de cada pergunta                 | 0        | 0      | a pergunta "Popular os tutoriais guiados?" foi feita, com padrão não          |
        | interativo, respondendo sim a "Popular os tutoriais guiados?"      | 3        | 32     | a pergunta "Popular os tutoriais guiados?" foi feita                          |
        | não interativo, com --onboarding                                   | 3        | 32     | nenhuma pergunta "Popular os tutoriais guiados?" foi feita                    |
        | não interativo, sem --onboarding                                   | 0        | 0      | nenhuma pergunta "Popular os tutoriais guiados?" foi feita                    |
        | não interativo, com --no-seed e --onboarding                       | 0        | 0      | nenhuma pergunta foi feita, e o resumo traz o comando do seeder pai para semear depois |

    Cenário: [CT-55] com multi-organização, o kit:install --onboarding semeia também os passos de organização
      Dado um diretório de instalação novo do kit, com a multi-organização ligada
      Quando o kit:install roda não interativo, com --onboarding
      Então existem 3 jornadas e 38 passos de onboarding no banco (12 no /admin, 11 no /app, 15 no /infra)
      E o tutorial gravado no banco é false
```

P-10: a linha `--no-seed` afirma o aviso pelo comando do seeder pai no resumo (o texto ao redor não é do `00`). ADV-10/17: a contagem de passos (32 sem, 38 com multi-organização) mata o `kit:install` que semeia só as jornadas ou só os filhos sem organização. ADV-11: a linha do `.env` mata o `kit:install` que "liga" o tutorial escrevendo a variável (o banco ficaria `false` até o próximo alinhamento, e o `.env` venceria numa instalação nova). CT-55 vive na suíte Tenancy (G10).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M92 | `confirm(..., default: true)` | CT-38 (linha 1) | 0 jornadas e padrão "não"; o mutante semeia 3 |
| M93 | o seeder pai chamado pelo `DatabaseSeeder` (semeia em todo `db:seed`) | CT-38 (linhas 1 e 4) | 0 jornadas; o mutante semeia 3 |
| M94 | `--onboarding` ignorado no modo não interativo (só a pergunta semeia) | CT-38 (linha 3) | 3 jornadas; o mutante dá 0 |
| M95 | o `kit:install` liga o tutorial quando semeia | CT-38 (linhas 2 e 3) | `false`; o mutante grava `true` |
| M96 | texto da pergunta diferente do requisito | CT-38 (linhas 1 e 2) | a pergunta com o texto literal; o mutante pergunta outra coisa |
| M141 | `--onboarding` semeia mesmo com `--no-seed` (a task do onboarding fora do `if` da semeadura) — P-10 | CT-38 (linha 5) | 0 jornadas e o comando no resumo; o mutante semeia 3 |
| M142 | `kit:install --onboarding` escreve `KIT_ONBOARDING=true` no `.env` "para a pessoa já ver" — ADV-11 | CT-38 (linhas 2 e 3, última asserção) | linha ativa não `true`; o mutante a grava |
| M143 | o `kit:install` chama o seeder pai antes de alinhar a config de multi-organização da instalação (lê o default desligado) — ADV-17 | CT-55 | 38 passos; o mutante dá 32 |
| M144 | o `kit:install` cria só as jornadas (seeder da base) sem os filhos — ADV-10 | CT-38 (linhas 2 e 3) | 32 passos; o mutante dá 0 |

---

## Regra R21 — A rule de manutenção existe, carrega pelos caminhos das telas e manda revisar o seeder filho

> `RQ-26`, `RQ-13`, `RQ-14`, `P-15` · perfil **mínimo** · técnica: **EP**. Lê `.ai/rules/` — `->skip(fn () => ! naArvoreDoKit())`

```gherkin
  Regra: uma rule de projeto em .ai/rules, carregada pelos caminhos das telas e do código das features base, manda revisar, atualizar ou criar o seeder filho

    Cenário: [CT-39] a rule de onboarding está em .ai/rules, indexada, e cobre as telas dos três painéis e as features base sem tela
      Dado a árvore do kit
      Quando a rule que cita o seeder pai de onboarding é lida, junto com o .ai/rules/index.md
      Então os caminhos dela cobrem as telas do /admin, do /app, do /infra e as compartilhadas (app/Filament/**) e os seeders de onboarding
      E os caminhos dela casam com ao menos um arquivo de código de cada feature base sem tela própria: dock, busca ⌘K, bloqueio de tela, dashboard dinâmico, hub e agentes de IA
      E o corpo manda revisar, atualizar ou criar o seeder filho, chamá-lo pelo pai e declarar a isenção com motivo
      E o index.md tem uma linha com os mesmos caminhos apontando para ela
```

P-15 (ADV-31): o teste casa os globs da rule contra arquivos que existem na árvore para cada feature base (por exemplo `app/Support/DockDeAtalhos.php`, `app/Support/DashboardDinamico.php`, `app/Support/AgentesDeIa.php`, `config/lockscreen.php`) — lista do teste, conferida por `glob`, nunca afirmada só como texto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M97 | rule sem `paths:` no frontmatter (nunca carrega por caminho) | CT-39 | caminhos cobrem as telas; o mutante não tem |
| M98 | rule fora do `index.md` | CT-39 (última asserção) | linha no índice; o mutante não tem |
| M99 | caminhos só do `/admin` (`app/Filament/Admin/**`) | CT-39 (primeira asserção) | cobre `/app` e `/infra`; o mutante não |
| M145 | caminhos só de telas (`app/Filament/**`): alterar o decisor do dock ou do dashboard dinâmico não carrega a rule — P-15 | CT-39 (segunda asserção) | casa com o código das seis features; o mutante não casa nenhum |

---

## Regra R22 — O teste de inventário fica vermelho com tela sem seeder nem isenção

> `RQ-27`, `RQ-14`, `RQ-01` · perfil **mínimo** · técnica: **EP**, uma situação defeituosa por linha, com a linha do estado do kit como controle; estouro do teto do perfil (2 cenários): a linha de multi-organização exige o `TenancyTestCase` (bootstrap), e não cabe no mesmo `Esquema`

```gherkin
  Regra: o inventário acusa toda tela de painel sem seeder de onboarding e sem isenção declarada com motivo

    Esquema do Cenário: [CT-40] o inventário acusa exatamente a tela, a isenção ou o filho defeituoso
      Dado os três painéis do kit, sem multi-organização
      E <situacao>
      Quando o inventário de onboarding é verificado
      Então as telas descobertas são <descobertas>

      Exemplos:
        | situacao                                                                       | descobertas                         |
        | o estado do kit, sem alteração                                                 | nenhuma                             |
        | uma Page nova registrada no /admin, sem seeder nem isenção                     | essa Page                           |
        | um Resource novo num painel registrado em teste, sem seeder nem isenção        | esse Resource                       |
        | uma tela do kit isenta com motivo vazio                                        | essa tela                           |
        | um seeder filho mapeado a uma tela, mas não chamado pelo seeder pai            | a tela desse seeder filho           |

    Cenário: [CT-41] com multi-organização, toda tela de organização tem seeder ou isenção
      Dado os três painéis do kit com a multi-organização ligada
      Quando o inventário de onboarding é verificado
      Então nenhuma tela é descoberta
      E as telas de Organizações, Membros, Convites, Convites recebidos e Projetos estão cobertas por seeder filho
```

Ver Q20 (a verificação como função que recebe os painéis, para os controles).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M100 | inventário só de Resources (Pages esquecidas) | CT-40 (linha 2) | a Page descoberta; o mutante não a vê |
| M101 | inventário só dos três ids de painel conhecidos | CT-40 (linha 3) | o Resource do painel novo descoberto; o mutante não o vê |
| M102 | isenção aceita sem motivo (`array_key_exists` em vez de motivo não vazio) | CT-40 (linha 4) | a tela descoberta; o mutante aceita |
| M103 | não confere que o filho é chamado pelo pai | CT-40 (linha 5) | a tela descoberta; o mutante aceita |
| M104 | inventário lê os painéis com multi-organização desligada sempre (telas de organização nunca verificadas) | CT-41 | as cinco telas cobertas; o mutante não as enumera e a asserção de cobertura falha |

---

## Regra R23 — A documentação pt e en ensina a semear e diz o que cada seeder popula

> `RQ-07`, `RQ-08`, `RQ-01`, `RQ-14`, `P-16` · perfil **mínimo** · técnica: **EP por idioma**. Lê `docs/` — `->skip(fn () => ! naArvoreDoKit())` (`.ai/rules/testes.md`)

```gherkin
  Regra: a documentação, em pt e en, diz como iniciar as jornadas via seeder e quais features, de quais telas e painéis, cada seeder popula

    Esquema do Cenário: [CT-42] a página do tutorial guiado de <idioma> ensina a semear e tabela os seeders filhos
      Dado a árvore do kit
      Quando a página do tutorial guiado em <idioma> é lida
      Então ela traz o comando do seeder pai por db:seed --class e o kit:install --onboarding
      E diz que, depois de semear, é preciso ligar o tutorial na tela de configurações da aplicação
      E cada seeder filho do diretório de seeders de onboarding aparece numa linha da tabela com o(s) painel(is) dele e a(s) tela(s) que o tour percorre, e os de multi-organização marcados como tal
      E ela cita as telas excluídas do tutorial (login, cadastro, recuperação de senha, boas-vindas, widgets soltos) com o motivo

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M105 | página só em pt | CT-42 (linha en) | a página en existe com o comando; o mutante não |
| M106 | tabela sem o painel de cada seeder | CT-42 | painel na linha do seeder; o mutante não tem |
| M107 | seeder filho novo sem linha na documentação (deriva de RQ-14) | CT-42 | toda linha presente; o mutante deixa um filho de fora |
| M108 | documenta só o `kit:install`, não o `db:seed --class` | CT-42 (primeira asserção) | o comando do seeder pai; o mutante não tem |
| M146 | tabela com seeder e painel, sem a tela — ADV-18 (RQ-08 "de quais telas") | CT-42 (terceira asserção) | a tela na linha do seeder, conferida contra a âncora do tour semeado; o mutante não tem |
| M147 | a documentação manda semear e para aí (a pessoa semeia e não vê nada, porque o tutorial nasce desligado) — P-16 | CT-42 (segunda asserção) | a frase de ligar na tela de configurações; o mutante não tem |

---

## Regra R24 — Com multi-organização, o progresso e a condição de convite do `/app` são por organização

> `P-08`, `P-09`, `RQ-24` · perfil **completo** · técnica: **EP** (organização da ação × organização observada). Suíte `tests/Tenancy` (G10)

```gherkin
  Regra: no /app com multi-organização, a mesma pessoa faz a jornada separadamente em cada organização

    Esquema do Cenário: [CT-49] o passo "Convites" do /app conclui só na organização em que a pessoa convidou
      Dado as jornadas semeadas com multi-organização, o tutorial ligado e o Davi com o papel admin_app na Acme e na Globex
      E <convites>
      Quando o checklist do /app/<organizacao> é montado para o Davi
      Então o passo "Convites" está <estado>

      Exemplos:
        | convites                                              | organizacao | estado    |
        | 1 convite enviado pelo Davi na Acme                   | acme        | concluído |
        | 1 convite enviado pelo Davi na Acme                   | globex      | pendente  |
        | nenhum convite                                        | acme        | pendente  |
        | 1 convite enviado pela Elisa na Acme, nenhum pelo Davi | acme       | pendente  |

    Esquema do Cenário: [CT-50] concluir um passo numa organização não conclui na outra
      Dado as jornadas semeadas com multi-organização, o tutorial ligado e a Carla na Acme e na Globex, com "Busca ⌘K" pendente nas duas
      E a Carla concluiu "Busca ⌘K" pelo checklist do /app/acme
      Quando o checklist do /app/<organizacao> é montado para ela
      Então o passo "Busca ⌘K" está <estado>

      Exemplos:
        | organizacao | estado    |
        | acme        | concluído |
        | globex      | pendente  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M148 | condição de convites do `/app` sem a coluna de escopo (qualquer convite da pessoa conta) — P-08 | CT-49 (linha 2) | pendente na Globex; o mutante conclui |
| M149 | condição de convites do `/app` escopada pela organização da sessão, não pela aberta na rota | CT-49 (linhas 1 e 2, com a Globex na sessão) | concluído na Acme e pendente na Globex; o mutante troca |
| M150 | progresso gravado sem escopo de organização (o checklist do `/app` usa o sujeito sem o tenant) — P-09 | CT-50 (linha 2) | pendente na Globex; o mutante mostra concluído |
| M170 | condição de convites do `/app` escopada só pela organização, sem a coluna do sujeito (qualquer convite da Acme conta) — ADV2-06 | CT-49 (linha 4) | pendente para o Davi; o mutante conclui pelo convite da Elisa |

---

## Regra R25 — Passos de organização só existem e só aparecem com a multi-organização ligada

> `P-12`, `P-17`, `RQ-09`, `RQ-24` · perfil **completo** · técnica: **2-switch** (ligada → semeada → desligada → religada) e **EP** (filho de organização rodado sozinho)

```gherkin
  Regra: com a multi-organização desligada, os passos de organização não são criados e, se já existiam, não aparecem — sem apagar

    Cenário: [CT-51] desligar a multi-organização esconde os passos de organização sem apagar, e religar os traz de volta
      Dado as jornadas semeadas com a multi-organização ligada, o tutorial ligado e todas as features da tabela ligadas
      E a administradora com o passo de Organizações concluído
      Quando a multi-organização é desligada, o checklist do /admin é montado, a multi-organização é religada e o checklist é montado de novo
      Então com ela desligada a jornada do /admin mostra 0 concluídos de 11 visíveis, sem o de Organizações, e os 12 passos continuam no banco
      E com ela religada a jornada mostra 1 concluído de 12 visíveis, e o de Organizações concluído

    Cenário: [CT-64] desligar a multi-organização esconde os passos de organização do /app sem apagar
      Dado as jornadas semeadas com a multi-organização ligada, o tutorial ligado e todas as features da tabela ligadas
      E a Ana, que entra no /app, e a fotografia dos passos da jornada do /app no banco
      Quando a multi-organização é desligada e o checklist do /app é montado para ela
      Então os passos de Membros, Convites, Convites recebidos, Troca de organização e Projetos não estão entre os visíveis
      E os 11 passos da jornada do /app continuam no banco, iguais à fotografia

    Esquema do Cenário: [CT-52] o seeder filho de organização rodado sozinho com a multi-organização desligada não cria nada
      Dado o banco sem jornadas nem passos e a multi-organização desligada
      Quando o seeder filho <filho> roda sozinho por db:seed --class
      Então não existe jornada nem passo no banco

      Exemplos:
        | filho                       |
        | OrganizacoesSeeder          |
        | ConvitesDaOrganizacaoSeeder |
        | ProjetosSeeder              |
        | MembrosDaOrganizacaoSeeder  |
        | ConvitesRecebidosSeeder     |
        | TrocaDeOrganizacaoSeeder    |
```

CT-51 e CT-64 na suíte Tenancy (G10); o `Quando` do CT-51 é o 2-switch (evento único declarado). CT-52 na suíte Kit (G7), onde a multi-organização está desligada no bootstrap. ADV2-03/12: o CT-51 afirma o numerador (0 concluídos com o passo concluído escondido; 1 ao religar). ADV2-04: CT-64 (o `/app`; a Ana entra no `/app` com a multi-organização desligada, como `panel_user`). ADV2-08: CT-52 com os seis filhos de organização.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M151 | passos de organização sem condição de visibilidade (a decisão fica só no seeder) — P-12 | CT-51 (primeira asserção) | 11 visíveis; o mutante mostra 12 com o passo de Organizações |
| M152 | desligar a multi-organização apaga os passos de organização (limpeza no boot ou no seeder) — P-12 | CT-51 | 12 no banco e Organizações concluído ao religar; o mutante perde o passo e o progresso |
| M153 | a guarda da multi-organização só no pai: o filho rodado sozinho cria o passo — P-17 | CT-52 | nada no banco; o mutante cria o passo (e a jornada) |
| M171 | o passo de Organizações escondido e concluído continua no numerador — ADV2-12 | CT-51 (primeira asserção) | 0 concluídos de 11; o mutante dá 1 de 11 |
| M172 | a visibilidade de organização registrada só para o passo do `/admin` (os do `/app` só dependem da rota de tenant) — ADV2-04 | CT-64 | os cinco ausentes; o mutante os mostra no `/app` sem organização |
| M173 | só os filhos de organização do `/admin` e Projetos herdam a guarda; os do `/app` criados por uma base comum sem ela — ADV2-08 | CT-52 (linhas 4–6) | nada no banco; o mutante cria os passos |

---

## Regra R26 — Passo cuja tela a pessoa não pode abrir não aparece nem conta no percentual

> `P-11`, `RQ-23` · perfil **completo** · técnica: **matriz papel × tela** (com e sem a permissão da tela) e **EP por painel** (persona padrão × toda tela de passo do seu painel)

```gherkin
  Regra: o checklist só mostra, e só conta, o passo cuja tela a pessoa pode abrir no painel corrente

    Esquema do Cenário: [CT-53] tirar a permissão da tela de um passo tira o passo do checklist
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E <persona> no <painel>, <permissao>
      Quando o checklist do <painel> é montado para ela
      Então a jornada tem <visiveis> passos visíveis e o passo de <feature> está <presenca>

      Exemplos:
        | persona          | painel | permissao                                          | visiveis                     | feature         | presenca |
        | a administradora | admin  | com as permissões do papel admin como semeadas     | 11                           | Usuários        | visível  |
        | a administradora | admin  | sem a permissão de ver a lista de Usuários         | 10                           | Usuários        | ausente  |
        | a administradora | admin  | sem a permissão de ver a lista de Papéis           | 10                           | Papéis          | ausente  |
        | a Íris           | infra  | com as permissões do papel infra como semeadas     | N₂ (controle da Íris)        | Pulse           | visível  |
        | a Íris           | infra  | sem a permissão da tela do Pulse                   | N₂ − 1                       | Pulse           | ausente  |
        | a Ana            | app    | com as permissões do papel panel_user como semeadas | N₃ (controle da Ana)        | Hub (cards)     | visível  |
        | a Ana            | app    | sem a permissão da tela do Hub do negócio          | N₃ − 1                       | Hub (cards)     | ausente  |

    Cenário: [CT-65] o passo concluído cuja tela a pessoa deixa de abrir sai também do numerador
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E a administradora concluiu só o passo de Usuários, e o checklist do /admin mostrava 1 concluído de 11 visíveis
      Quando a permissão de ver a lista de Usuários é retirada do papel dela e o checklist do /admin é montado de novo
      Então a jornada mostra 0 concluídos de 10 visíveis
      E a linha de progresso do passo de Usuários dela continua no banco

    Esquema do Cenário: [CT-54] a persona padrão de cada painel abre a tela de todo passo visível do seu checklist
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E <persona> no <painel>, com as permissões do papel como semeadas
      Quando ela abre a tela de cada passo da jornada do <painel> que tem tela própria
      Então toda tela de passo visível no checklist dela abre (nenhuma responde 403 nem 404)
      E todo passo cuja tela responde 403 para ela está ausente do checklist

      Exemplos:
        | persona          | painel |
        | a administradora | admin  |
        | a Ana            | app    |
        | a Íris           | infra  |
```

P-11 (ADV-36). ADV2-02: linhas 4–7 do CT-53 (`/infra` e `/app`), com contagem relativa ao controle da própria persona (`N₂`, `N₃`) — `@premissa P-11`, ADV2-13; as colunas `persona` e `painel` entraram com o valor que as linhas 1–3 já tinham. ADV2-03: CT-65 (2-switch concluir → perder a permissão). ADV2-13: o CT-54 afirma a invariante nos dois sentidos (visível ⇒ abre; 403 ⇒ ausente), sem número. "Tela do passo" é a tela da primeira parada de tour do passo; os passos sem tela própria (busca ⌘K, dock, bloqueio de tela, troca de organização) ficam fora do CT-54 e o do dashboard dinâmico segue a condição do dinâmico (CT-27).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M154 | visibilidade de passo só pela feature ligada (P-03), sem consultar o acesso à tela — P-11 | CT-53 (linhas 2 e 3) | 10 e ausente; o mutante mostra 11 |
| M155 | acesso conferido pela permissão do Resource no painel `admin` fixo, não no painel corrente | CT-54 (linhas app e infra) | nenhum 403; o mutante mostra passo que leva a 403 (ou esconde passo acessível, e a contagem do CT-16 cai) |
| M156 | passo semeado para uma tela que a persona padrão do painel não recebe na instalação nova (ex.: uma tela de pacote do `/infra` sem permissão concedida ao papel `infra`) — ADV-36 | CT-54 (linha infra) | nenhum 403; o mutante leva a 403 |
| M174 | o acesso à tela conferido só para Resources do kit (Pages de pacote do `/infra`, como Pulse, e Pages do `/app`, como o Hub do negócio, sempre visíveis) — ADV2-02 | CT-53 (linhas 5 e 7) | `N − 1` e ausente; o mutante mantém o passo |
| M175 | o passo escondido por falta de acesso continua no numerador — ADV2-03 | CT-65 (primeira asserção) | 0 de 10; o mutante dá 1 de 10 |
| M176 | perder o acesso apaga o progresso do passo — ADV2-03 | CT-65 (segunda asserção) | linha de progresso continua; o mutante a apaga |

---

## Regra R27 — Os arquivos da feature viajam pelas duas rotas de entrega do kit

> `RQ-04`, `RQ-06`, `RQ-26` (a instalação precisa ter os seeders para semear, a migration para o interruptor e a rule para o agente) · perfil **mínimo** · técnica: **EP por arquivo × rota de entrega** (glossário: *Rota de entrega*)

```gherkin
  Regra: o que a feature cria chega ao projeto pelo create-project e pelo kit:update

    Esquema do Cenário: [CT-57] cada arquivo da feature está nas duas rotas de entrega
      Dado a árvore do kit
      Quando <arquivo> é conferido contra o .gitattributes e contra os caminhos do kit:update
      Então ele não está excluído do create-project
      E ele está coberto pelos caminhos do kit:update

      Exemplos:
        | arquivo                                                  |
        | o seeder pai de onboarding                               |
        | cada seeder filho do diretório de seeders de onboarding  |
        | a migration de settings do tutorial                      |
        | a rule de onboarding em .ai/rules                        |
        | o config do pacote de onboarding                         |
```

ADV-16. Molde: `tests/Kit/KitUpdateTest.php` (`KitUpdate::CAMINHOS_DO_KIT`) e o helper `caminhosDoKit()` de `tests/Pest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M157 | diretório novo `database/seeders/Onboarding/` fora de `KitUpdate::CAMINHOS_DO_KIT` (projeto atualizado sem os filhos) — ADV-16 | CT-57 (linha 2) | coberto pelo `kit:update`; o mutante não |
| M158 | a rule nova fora da lista de entrega (só `.ai/rules/` antigas listadas uma a uma) | CT-57 (linha 4) | coberta; o mutante não |

---

## Regra R28 — Escrever a chave de outra jornada direto no checklist não troca a jornada

> `P-20` (step 9, CR-01) · perfil **padrão** · técnica: **EP** (chave fora do domínio: jornada de outro painel, chave inexistente) · arquivo `tests/Kit/OnboardingGuiadoConsumoTest.php` (G6)

```gherkin
  Regra: a jornada do checklist só muda para uma jornada que a pessoa vê no painel corrente

    Esquema do Cenário: [CT-66] a chave de uma jornada invisível escrita direto na propriedade do checklist não muda o checklist
      Dado as jornadas do kit semeadas, o tutorial ligado e a administradora com o checklist montado no /admin, na jornada do /admin
      Quando a propriedade flowKey do checklist recebe <chave> diretamente, sem a troca de aba
      Então o checklist continua mostrando a jornada do /admin
      E concluir em seguida o passo de Usuários, sem informar a jornada, grava o progresso na jornada do /admin e nenhum na jornada <outra>

      Exemplos:
        | chave                       | outra        | # partição                 |
        | "primeiros-passos-infra"    | do /infra    | jornada de outro painel    |
        | "primeiros-passos-app"      | do /app      | jornada de outro painel    |
        | "jornada-que-nao-existe"    | do /infra    | chave inexistente          |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M177 | a validação da jornada só em `selectFlow`; o `set` direto da propriedade (sem `#[Locked]`) passa — P-20 | CT-66 (linhas 1 e 2) | jornada do `/admin` mostrada; o mutante mostra a do `/infra`/`/app` |
| M178 | chave inválida cai na primeira jornada encontrada (de qualquer painel), não na visível do painel | CT-66 (linha 3) | jornada do `/admin` e progresso só nela; o mutante resolve para outra jornada |
| M179 | a chave escrita é ignorada na exibição, mas usada nas ações (o `completeStep` sem jornada lê a propriedade) | CT-66 (segunda asserção) | progresso na jornada do `/admin`; o mutante grava na outra |

---

## Regra R29 — A falha da semeadura do tutorial não interrompe o `kit:install`

> `P-21` (step 9, CR-02), `RQ-06`, `RQ-19`, `RQ-20` · perfil **padrão** · técnica: **rastreio de efeito** (o efeito que precisa acontecer mesmo com a falha: o resumo com a senha) · arquivo `tests/Kit/OnboardingGuiadoInstalacaoTest.php` (G11)

```gherkin
  Regra: se a semeadura do tutorial falha durante o kit:install, a instalação termina e o resumo final aparece com o aviso de como semear depois

    Esquema do Cenário: [CT-67] com a semeadura do tutorial falhando, o kit:install mostra o resumo final e o aviso
      Dado um diretório de instalação novo do kit, sem multi-organização
      E a semeadura do tutorial forçada a lançar exceção
      Quando o kit:install roda <modo>
      Então a saída traz o resumo final com a senha do administrador gerada
      E traz o aviso com o comando do seeder pai para semear depois
      E o administrador existe no banco, e não há jornada de onboarding

      Exemplos:
        | modo                                                            |
        | interativo, respondendo sim a "Popular os tutoriais guiados?"   |
        | não interativo, com --onboarding                                |
```

A falha é induzida no teste (o seeder pai trocado no container por um que lança), sem alterar código da aplicação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M180 | a semeadura do tutorial chamada sem tratamento: a exceção sobe e o comando aborta antes do resumo — P-21 | CT-67 (primeira asserção) | resumo com a senha; o mutante para na exceção |
| M181 | a falha é engolida em silêncio (sem aviso) | CT-67 (segunda asserção) | comando do seeder pai na saída; o mutante não avisa |
| M182 | a falha da semeadura do tutorial marca a instalação como "banco não populado" e pula os passos seguintes | CT-67 (terceira asserção) | administrador existe; o mutante não o cria |

---

## Regra R30 — O passo do dashboard dinâmico exige poder montar o dashboard dinâmico do painel

> `P-22` (step 9, CR-03), `P-03`, `P-11` · perfil **completo** (área D) · técnica: **tabela de decisão** (dinâmico ligado no painel × permissão de montar o dashboard) · arquivo `tests/Kit/OnboardingGuiadoConsumoTest.php` (G8)

```gherkin
  Regra: o passo do dashboard dinâmico aparece só com o dinâmico ligado no painel e para quem pode montar o dashboard dinâmico daquele painel

    Esquema do Cenário: [CT-68] o passo do dashboard dinâmico segue a permissão de montar o dashboard do painel
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E <persona> no <painel>, <permissao>
      Quando o checklist do <painel> é montado para ela
      Então o passo "Dashboard dinâmico" está <presenca>
      E a jornada tem <visiveis> passos visíveis

      Exemplos:
        | persona          | painel | permissao                                                         | presenca | visiveis       |
        | a administradora | admin  | com a permissão de montar o dashboard                             | visível  | N₄ (controle)  |
        | a administradora | admin  | sem a permissão de montar o dashboard                             | ausente  | N₄ − 1         |
        | a Íris           | infra  | com a permissão de montar o dashboard                             | visível  | N₅ (controle)  |
        | a Íris           | infra  | sem a permissão de montar o dashboard                             | ausente  | N₅ − 1         |
        | a Íris           | infra  | com a do papel infra, e com o papel admin sem ela                 | visível  | N₅             |
```

A página é `App\Filament\{Admin,Infra}\Pages\Dashboard` (rota `filament.{painel}.pages.dashboard-dinamico`), nome só para o arranjo. A permissão de montar é `Manage:Dashboard`, o `canEdit()` da página (abrir a página não filtra: ela é a home do painel e abre para todos). O dinâmico fica ligado em todas as linhas; a única variável é a permissão (o dinâmico desligado é o CT-27, P-03). A última linha prova que basta um dos papéis da pessoa ter a permissão. Contagem relativa ao controle da própria persona (`@premissa P-11`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M183 | o passo segue só o dinâmico ligado no painel (P-03), sem a permissão de montar — P-22 | CT-68 (linhas 2 e 4) | ausente; o mutante mostra |
| M184 | o passo segue o `canAccess()` da página do dinâmico (abrir), não o `canEdit()` (montar) | CT-68 (linhas 2 e 4) | ausente sem a permissão de montar; o mutante mostra porque a página abre para todos |
| M185 | o passo exige a permissão em todos os papéis da pessoa | CT-68 (linha 5) | visível com a permissão só no papel infra; o mutante esconde |
| M186 | o passo escondido continua no total | CT-68 (linhas 2 e 4, coluna visiveis) | `N − 1`; o mutante mantém `N` |

---

## Regra R31 — Nenhum passo semeado fica escondido de todo mundo

> `P-23` (step 9, CR-04), `RQ-02`, `P-11` · perfil **completo** (área D) · técnica: **EP por painel** (união das personas do painel) · arquivo `tests/Kit/OnboardingGuiadoSemeaduraTest.php` (G8)

```gherkin
  Regra: todo passo semeado é visível para alguém no seu painel quando as features dele estão ligadas

    Esquema do Cenário: [CT-69] com tudo ligado, a união do que as personas do painel veem cobre todos os passos semeados do painel
      Dado o seeder pai rodado sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E as personas do <painel>: <personas>, com as permissões dos papéis como semeadas
      Quando o checklist do <painel> é montado para cada uma delas
      Então todo passo semeado na jornada do <painel> aparece para ao menos uma delas
      E toda condição de visibilidade citada por um passo semeado do <painel> está registrada no <painel>

      Exemplos:
        | painel | personas                                                |
        | admin  | a administradora, o administrador geral (master_global) |
        | app    | a Ana, o administrador geral (master_global)            |
        | infra  | a Íris, o administrador geral (master_global)           |
```

O CT-23 afirma que toda chave citada resolve; o CT-69 afirma o efeito que a P-23 protege (passo invisível para todos), painel a painel.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M187 | condição de visibilidade com chave digitada errada num seeder filho (`tela:usuario` em vez da registrada): a chave desconhecida esconde o passo de todos — P-23 | CT-69 | o passo aparece para alguém; o mutante o esconde de todos |
| M188 | condição de tela registrada só no plugin do `/admin` (os passos do `/app` e do `/infra` que a citam somem para todos) | CT-69 (linhas app e infra) | passo visível e condição registrada no painel; o mutante esconde |
| M189 | passo semeado citando condição de feature que não é registrada naquele painel (ex.: a do hub no `/infra`) | CT-69 (linha infra, segunda asserção) | condição registrada no painel; o mutante cita chave não registrada |

---

## Regra R32 — O canal de log do tutorial existe no projeto atualizado, e a configuração de log do projeto não é sobrescrita

*(alterado em 2026-10-21: P-24 reescrita na rodada 2 do step 9)*

> `P-24` (step 9, RD-02; redação revista na rodada 2, RD2-03 e CR2-02), `RQ-02` · perfil **mínimo** (área F) · técnica: **tabela de decisão** (canal `onboarding` declarado × não declarado no `config/logging.php` do projeto) + **EP** (`config/logging.php` dentro × fora dos caminhos do `kit:update`) · arquivo `tests/Kit/OnboardingGuiadoInstalacaoTest.php` (G12)

Técnica escalada acima do mínimo (só EP): as duas colunas da tabela pedem ações opostas sobre a mesma condição — suprir o canal quando falta, não tocar quando existe —, e uma partição sozinha não distingue "suprir sempre" de "suprir só quando falta". Teto do mínimo (1 cenário por regra) estourado em 1: o CT-71 é o único matador de M194 e M195 (o gate vence o teto); a regra tem duas costuras reais (o boot da aplicação e a lista de entrega) e não foi desdobrada porque a sessão fixou o ID R32. Seis mutantes, um acima do teto do perfil padrão, pelo mesmo motivo.

| | D1 | D2 |
|---|---|---|
| C1 — o `config/logging.php` do projeto declara o canal `onboarding` | não | sim (driver `single`, caminho `storage/logs/onboarding-do-projeto.log`) |
| A1 — `onboarding` resolve para uma configuração definida, sem logger de emergência | sim | sim |
| A2 — a configuração de `onboarding` é a que o projeto declarou | — (não há declaração) | sim |
| A3 — o canal próprio `auditoria-do-projeto`, padrão do projeto, continua o do projeto | sim | sim |

Nenhuma coluna colapsada: C1 é a única condição de que a ação depende; A3 vale nas duas colunas e é o destinatário real do não-efeito "não sobrescreve".

```gherkin
  Regra: no projeto atualizado, o canal de log do tutorial existe sem que a configuração de log do projeto seja sobrescrita

    Esquema do Cenário: [CT-70] o canal de log do tutorial resolve, e o que o projeto declarou na configuração de log vence
      Dado a aplicação do projeto iniciada com um config/logging.php que <declaracao>
      E esse config/logging.php declara o canal próprio auditoria-do-projeto como canal padrão
      Quando um aviso é gravado no canal onboarding
      Então a configuração do canal onboarding é <configuracao>
      E o aviso não aciona o logger de emergência ("Log [onboarding] is not defined")
      E o canal auditoria-do-projeto continua definido e continua sendo o canal padrão

      Exemplos:
        | linha | declaracao                                                                                   | configuracao                                                                 |
        | D1    | não declara o canal onboarding                                                               | definida (não nula, com driver)                                              |
        | D2    | declara o canal onboarding com driver single e caminho storage/logs/onboarding-do-projeto.log | driver single e caminho storage/logs/onboarding-do-projeto.log — a do projeto |

    Cenário: [CT-71] o config/logging.php do projeto não está entre os caminhos que o kit:update entrega
      Dado a árvore do kit
      Quando config/logging.php é conferido contra os caminhos entregues pelo kit:update
      Então ele não está coberto por nenhum desses caminhos, nem como arquivo nem por um diretório que o contenha
      E o config do pacote de onboarding está coberto por esses caminhos
```

- **Por que "não lança exceção" não é oráculo**: o `LogManager` lança `"Log [{$name}] is not defined."` quando a configuração do canal é nula (`vendor/laravel/framework/src/Illuminate/Log/LogManager.php:resolve:222`), mas o `get()` captura e devolve o logger de emergência, que grava `Unable to create configured logger. Using emergency logger.` (`vendor/laravel/framework/src/Illuminate/Log/LogManager.php:get:140`). O erro nunca chega a quem gravou o aviso. A primeira asserção (configuração do canal) é o oráculo; a segunda observa o destino real do logger de emergência (`storage/logs/laravel.log` do cenário), com o arquivo isolado por cenário para não medir aviso de outro teste
- **O `Dado` vale antes do boot** (`@premissa` de mecanismo, raia desenho — Q29, decidida em D13 do `01`: aplicação nova com `afterBootstrapping(LoadConfiguration::class)`): a configuração de log do cenário é a que a aplicação carrega ao iniciar. `config()->set()` depois do boot não exercita o caminho que supre o canal (falso vermelho se o kit supre no boot; falso verde se o teste só apaga e relê). E o `config/logging.php` da árvore do kit não serve de `Dado` para a linha D1: se ele declarar o canal, a linha passa medindo o kit, não o projeto
- **Proibição 12 não se aplica**: o cenário afirma a existência e a origem da configuração do canal (P-24), não o conteúdo de uma linha de log; o `warning` do tutorial continua fora de CT (`## Fronteira com o Plano`)
- A segunda asserção do CT-71 é controle positivo: com `caminhosDoKit()` vazio ou mal lido, a primeira passaria no vácuo
- `App\Console\Commands\KitUpdate::CAMINHOS_DO_KIT` (molde `tests/Kit/KitUpdateTest.php`, helper `caminhosDoKit()` de `tests/Pest.php`, a mesma noção de "coberto" do CT-57); canal `onboarding`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M190 | nada supre o canal: só o `config/logging.php` da árvore do kit o declara, e o projeto, cujo arquivo não o tem, fica sem ele (o estado que o RD-02 achou) — P-24 | CT-70 (linha D1) | configuração de `onboarding` definida; o mutante deixa nula e o aviso cai no logger de emergência |
| M191 | o kit supre o canal por mesclagem rasa da configuração de log: a chave `channels` do projeto substitui a do kit inteira | CT-70 (linha D1) | configuração definida; o mutante perde o canal justamente no projeto que declara `channels` próprio |
| M192 | o kit grava o seu canal `onboarding` sempre (ou mescla em profundidade com o kit por cima), mesmo quando o projeto o declara | CT-70 (linha D2) | driver `single` e caminho `storage/logs/onboarding-do-projeto.log`; o mutante põe os do kit |
| M193 | o kit troca a configuração de log inteira pela do esqueleto ao subir | CT-70 (linhas D1 e D2, última asserção) | `auditoria-do-projeto` definido e padrão; o mutante apaga o canal e devolve o padrão do esqueleto |
| M194 | `config/logging.php` listado em `CAMINHOS_DO_KIT`: o arquivo do esqueleto entregue inteiro apaga os canais próprios do projeto (RD2-03, CR2-02) | CT-71 (primeira asserção) | não coberto; o mutante cobre pelo arquivo |
| M195 | `config/` inteiro listado em `CAMINHOS_DO_KIT` para levar os configs novos da feature | CT-71 (primeira asserção) | não coberto nem por diretório; o mutante cobre pelo prefixo |

---

## Regra R33 — Com o tutorial ligado, a página "Primeiros passos" abre com passo concluído e o mostra concluído, nos três painéis e nos três modos de conclusão

*(nova em 2026-10-21: QA-08 do quality gate, ciclo 2)*

> `RQ-28` ("a página 'Primeiros passos' no menu, nos três painéis" — a página existe para mostrar o progresso), `P-06`, `P-22` (contagem do `/app`) · perfil **padrão** (área C) · técnica: **EP** em duas dimensões — modo de conclusão (tour, visita, condição) × painel (`admin`, `app`, `infra`) —, cada partição de cada dimensão ao menos uma vez e o produto completo onde a tabela aprovada permite (a visita só existe no `/admin`: "Configurações da aplicação") · arquivo `tests/Kit/OnboardingGuiadoConsumoTest.php` (G5)

```gherkin
  Regra: com o tutorial ligado, a página "Primeiros passos" mostra o progresso da jornada do painel, inclusive o passo já concluído

    Esquema do Cenário: [CT-72] a página "Primeiros passos" abre com um passo já concluído e o mostra concluído
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e todas as features da tabela ligadas
      E <persona> com o passo "<passo>" concluído por <modo>, e nenhum outro passo da jornada do <painel> concluído
      Quando ela abre /<painel>/onboarding
      Então a resposta é 200 com a Page OnboardingProgress
      E o passo "<passo>" aparece concluído, e o passo "<pendente>" aparece pendente
      E a contagem da jornada é 1 concluído de <total>

      Exemplos:
        | persona          | painel | passo                      | modo                                                          | pendente                   | total |
        | a administradora | admin  | Configurações da aplicação | visita (ela abriu /admin/configuracoes-da-aplicacao)          | Usuários                   | 11    |
        | a administradora | admin  | Usuários                   | tour (ela terminou o tour pelo checklist)                     | Configurações da aplicação | 11    |
        | a administradora | admin  | Convites                   | condição (1 convite enviado por ela)                          | Usuários                   | 11    |
        | a Ana            | app    | Busca ⌘K                   | tour (ela terminou o tour pelo checklist)                     | Perfil e 2FA               | 5     |
        | a Ana            | app    | Perfil e 2FA               | condição (segundo fator confirmado)                           | Busca ⌘K                   | 5     |
        | a Íris           | infra  | Pulse                      | tour (ela terminou o tour pelo checklist)                     | Backups                    | 15    |
        | a Íris           | infra  | Perfil e 2FA               | condição (segundo fator confirmado)                           | Pulse                      | 15    |
```

- **Por que este cenário e não o CT-16/CT-18**: o CT-16 abre a página com **zero** passos concluídos; o CT-18 conclui um passo do `/infra` e abre a página do `/admin`, onde o passo não aparece. Nenhum dos dois renderiza um passo concluído **visível** — o defeito do QA-08 mora exatamente aí
- **A conclusão vem pela ação real**, nunca por `insert` na tabela de progresso (`## Setup Global`): visita = GET real da tela com o tutorial ligado (como o CT-26, linha 1); tour = `finishTour` no checklist montado no painel (como o CT-14), com `fronteiraDeRequest()` antes do GET da página; condição = `breezySession` confirmada ou `Convite::factory()` com `convidado_por_id` dela (como o CT-24 e o CT-25). No `/app`, GET real antes do `Livewire::test`
- **"Aparece concluído"** é lido na linha do passo na página (a marca de concluído que a página dá ao passo); o passo `<pendente>` da mesma resposta é o **controle** de que a marca discrimina — sem ele, uma página que marca tudo (ou nada) do mesmo jeito passaria
- **Contagem**: "1 concluído de `<total>`" é o progresso da jornada como a página o expõe; `<total>` é o mesmo do CT-16 (11 / 5 / 15) — o `/app` com 5 pela P-22. "Nenhum outro passo concluído" no `Dado` faz o 1 discriminar (o "Perfil e 2FA" fica pendente nas linhas que não são dele: sem segundo fator)
- O mesmo `KitServiceProvider` sobe nos testes (a data do kit é imutável), então o cenário reproduz o 500 do QA-08 antes da correção — o CT tem de falhar primeiro

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M197 | a página quebra ao renderizar passo concluído: a data de conclusão lida por um retorno tipado que não aceita a data imutável que o kit usa (o estado atual, QA-08) | CT-72 (todas as linhas) | 200 com a Page; o mutante responde 500 |
| M198 | correção que lê a conclusão só da linha de progresso gravada: o passo concluído por condição (decidido na leitura) aparece pendente na página | CT-72 (linhas 3, 5, 7) | "`<passo>`" concluído e 1 de `<total>`; o mutante mostra pendente e 0 de `<total>` |
| M199 | correção que pula os passos concluídos na lista da página (para não ler a data de conclusão) | CT-72 (segunda e terceira asserções) | o passo concluído está na página, e o total é `<total>`; o mutante o omite (0 de `<total>` − 1, ou 1 de `<total>` sem a linha do passo) |
| M200 | correção feita só na página de um painel (a do `/admin`), com o `/app` e o `/infra` ainda renderizando pela view antiga | CT-72 (linhas 4–7) | 200 no `/app` e no `/infra`; o mutante responde 500 |

---

## Regra R34 — Com o tutorial ligado, o custo de abrir uma tela não cresce com os passos que ensinam a mesma tela, e a decisão de acesso vale só para o request

*(nova em 2026-10-21: QA-09 do quality gate, ciclo 2)*

> `P-25` ("o custo de abrir uma tela do painel não cresce com o número de passos que ensinam a mesma tela: o acesso a cada tela é decidido uma vez por request"), `P-11` · perfil **padrão** (área C) · técnica: **cardinalidade com controle** — o mesmo número de passos extras (10) com e sem a condição de visibilidade da tela; a única variável entre a medida e o controle é a condição — e **2-switch** para "uma vez por **request**" (a decisão não sobrevive ao request seguinte) · arquivo `tests/Kit/OnboardingGuiadoConsumoTest.php` (G5)

```gherkin
  Regra: com o tutorial ligado, abrir uma tela custa o mesmo número de consultas por mais passos que ensinem a mesma tela, e o acesso decidido num request não vale para o seguinte

    Cenário: [CT-73] dez passos a mais que ensinam a tela de Usuários não acrescentam consultas ao abrir uma tela do /admin
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e a administradora, que já abriu /admin/configuracoes-da-aplicacao uma vez
      E dez passos extras na jornada do /admin, e C₀ = o número de consultas ao banco de ela abrir /admin/configuracoes-da-aplicacao com os dez sem condição de visibilidade (controle)
      Quando ela abre /admin/configuracoes-da-aplicacao com os mesmos dez passos extras citando a condição de visibilidade da tela de Usuários
      Então o número de consultas ao banco desse request não passa C₀
      E o checklist da resposta mostra os dez passos extras visíveis

    Cenário: [CT-74] a decisão de acesso à tela vale só para o request em que foi tomada
      Dado as jornadas do kit semeadas sem multi-organização, o tutorial ligado e a administradora
      E ela já abriu o dashboard do /admin, com o passo "Usuários" visível no checklist e a jornada com N visíveis
      Quando a permissão de abrir a tela de Usuários é retirada do papel admin e ela abre o dashboard do /admin de novo
      Então o passo "Usuários" não está no checklist da segunda resposta
      E a jornada mostra N − 1 passos visíveis
```

- **Por que o controle e não um número absoluto**: o total de consultas da tela depende de tudo o que ela carrega (medido no QA-09: 53 com o tutorial ligado, 56 com dez passos extras sem condição) e muda com qualquer feature; o número do requisito é "não cresce". Medida e controle têm os **mesmos** dez passos extras, então o custo de ter passos se anula, e sobra só o custo da condição de tela. A jornada semeada já tem o passo "Usuários", que decide a tela de Usuários nos dois requests: com a decisão uma vez por request, os dez extras acrescentam zero; com a decisão por passo, acrescentam ≈ 3 por passo (QA-09: 86 contra 56)
- **Aquecimento**: a primeira abertura do `Dado` carrega os caches (permissões, settings) antes das duas medidas; o oráculo é "não passa C₀", e não "igual", para que um cache aquecido entre as medidas não gere falso vermelho
- **Anti-vácuo**: a segunda asserção do CT-73 prova que a condição foi avaliada e deu "pode abrir" — uma implementação que esconde os passos de condição sem avaliá-la também gastaria menos consultas
- Consultas contadas por `DB::listen` ou `DB::enableQueryLog()` em volta do GET, como em `tests/Kit/VisoesDeTabelaTest.php` e `consultasDuranteACaptura()` (`tests/Kit/DockDeAtalhosTest.php`); os passos extras pelos models do pacote (`OnboardingStep` na jornada `primeiros-passos-admin`), com a condição de visibilidade da tela de Usuários pela chave que o kit registra (`tela:{chave}`, nome só para o arranjo — `## Fronteira com o Plano`)
- **CT-74**: os dois GET rodam no mesmo processo PHP, com `fronteiraDeRequest()` entre eles e `semAPermissao('admin', …)` + `noPainelDoShield('admin')` para retirar a permissão — é o que faz uma memória estática (do processo) falhar aqui, e não só em produção com worker persistente. 2-switch declarado como evento único (`## Perfil de Derivação`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M201 | o acesso à tela é decidido de novo a cada passo que cita a tela (o estado atual, QA-09: N+1 em toda tela do painel) | CT-73 | consultas ≤ C₀; o mutante soma ≈ 3 por passo extra (≈ C₀ + 30) |
| M202 | memória chaveada pelo passo (ou pela condição de cada passo), não pela tela: passos diferentes que ensinam a mesma tela não aproveitam a decisão | CT-73 | consultas ≤ C₀; o mutante consulta uma vez por passo extra |
| M203 | memória estática do processo (ou cache sem o request e a pessoa na chave): a decisão do primeiro request vale para o seguinte | CT-74 | "Usuários" ausente e N − 1 no segundo request; o mutante mantém o passo visível, decidido antes da retirada |

---

## Regra R35 — Um valor recusado vindo do navegador não forja linha no log do tutorial

*(nova em 2026-10-21: QA-11 do quality gate, ciclo 2)*

> `P-26` ("um valor vindo do navegador e recusado pelo checklist não cria linha nova no log do tutorial (quebra de linha não forja registro)"), `P-20` · perfil **padrão** (área C) · técnica: **EP** — forma da quebra de linha (LF, CRLF, CR) × ponto de entrada da chave da jornada (propriedade escrita direto, troca de aba) — com **rastreio de efeito** contado em linhas físicas ("exatamente uma" pela propriedade, "no máximo uma" pela troca de aba) *(alterado em 2026-10-22: QA-17 do quality gate, ciclo 3)* · arquivo `tests/Kit/OnboardingGuiadoConsumoTest.php` (G6)

**Proibição 12 não se aplica**: o cenário não afirma o texto do aviso de recusa (que continua sem CT, `## Fronteira com o Plano`); afirma o efeito que a P-26 proíbe — linha nova forjada no arquivo do canal — e a contagem de linhas físicas por recusa (exatamente uma pela propriedade, como controle positivo de que o valor passou pelo canal observado; no máximo uma pela troca de aba).

```gherkin
  Regra: a chave de jornada recusada pelo checklist, com quebra de linha, não cria linha nova no canal de log do tutorial

    Esquema do Cenário: [CT-75] uma chave de jornada com quebra de linha não cria linha nova no canal de log do tutorial
      Dado as jornadas do kit semeadas, o tutorial ligado e a administradora com o checklist montado no /admin, na jornada do /admin
      E o canal de log do tutorial gravando num arquivo do cenário, com uma linha sentinela já gravada nele
      Quando o checklist recebe, pela <entrada>, o valor "qa-gate" + <quebra> + "[2026-10-21 23:59:59] local.CRITICAL: linha forjada"
      Então o checklist continua mostrando a jornada do /admin
      E a linha sentinela está no arquivo, e depois dela há <linhas físicas> linha física, separando por \r\n, \n ou \r
      E nenhuma linha física do arquivo, fora da primeira depois da sentinela, contém "local.CRITICAL: linha forjada"

      Exemplos:
        | entrada                                  | quebra        | linhas físicas | # partição                                                                 |
        | propriedade flowKey escrita direto       | LF (\n)       | exatamente 1   | quebra simples; a entrada do QA-11 (P-20)                                  |
        | propriedade flowKey escrita direto       | CRLF (\r\n)   | exatamente 1   | quebra do Windows; o `\r` que sobra de tirar só o `\n` vira quebra no formatter (QA-17) |
        | propriedade flowKey escrita direto       | CR (\r)       | exatamente 1   | `\r` sozinho: o tratamento de `\r\n` e `\n` não o alcança                  |
        | troca de aba (selectFlow)                | LF (\n)       | no máximo 1    | segundo ponto de entrada da chave (ver Q?3)                                |
```

*(alterado em 2026-10-22: QA-17 do quality gate, ciclo 3)* — o oráculo deixou de contar só as linhas que começam com `[` e passou a contar as **linhas físicas** depois da sentinela; a última asserção deixou de ser "começa com" e passou a "contém, fora da primeira linha nova"; linha `CR (\r)` e coluna `linhas físicas` acrescentadas; M205 desdobrado (M205 só `\n`, M208 só `\r\n`), M209 e M210 novos. Motivo medido: com o tratamento só de `\n`, o `\r` vai escapado no JSON do context, o formatter de linha o devolve como quebra real, e a linha forjada nasce com um espaço na frente (`" [2026-10-21 23:59:59] local.CRITICAL: linha forjada"`) — o oráculo antigo, ancorado em `^\[`, não a via.

- **`@premissa` de mecanismo (raia desenho)**: o canal `onboarding` tem o **caminho** desviado para um arquivo do cenário, mantendo o driver e o formatter que o projeto declara — a quebra de linha só vira linha nova pelo formatter de linha do Laravel. **Nunca** `Log::spy()` / `Log::partialMock()->shouldReceive('channel')` (o molde de `tests/Pest.php` para outros canais): o mock recebe a string e não prova linha nenhuma no arquivo
- **A sentinela é o controle positivo da observação**: gravada no canal antes da ação, ela prova que o teste lê o arquivo para onde o canal escreve — sem ela, um canal que grava em outro lugar passa as duas negativas no vácuo
- **"Linha física"** = trecho do arquivo entre quebras `\r\n`, `\n` ou `\r` — separar por `/\r\n|\n|\r/`, **não** por `\R` sem o modificador `u`, que também parte no byte `0x85` de um caractere UTF-8. Conta-se da linha seguinte à da sentinela até o fim, descartando só o trecho vazio que o `\n` final do arquivo produz. Nenhum filtro por prefixo: a linha forjada conta qualquer que seja o primeiro caractere
- **"Exatamente 1" (linhas 1–3) é controle positivo, não oráculo de log** (Proibição 12): a recusa pela propriedade grava uma entrada no canal do tutorial (medido no QA-11, ciclo 3, dimensão B: "gera 1 linha"); exigir exatamente uma prova que o valor recusado **passou** pelo formatter do canal observado. Com "no máximo 1", uma recusa que deixasse de passar pelo canal (M210) passaria as duas negativas no vácuo. O cenário não afirma nível, texto nem campos da entrada
- **"No máximo 1" (linha 4)**: o `00` não diz se a troca de aba registra a recusa, e o log não é cláusula; a linha vale como guarda do M206 — a regressão em que ela passa a registrar o valor cru. Apertar para "exatamente 1" é a Q?3
- **"Fora da primeira"**: a única linha nova é a entrada da recusa, que pode trazer o valor tratado (numa linha só); o texto forjado em qualquer outra linha física é a forja que a P-26 proíbe, com ou sem `[` na coluna 0

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M204 | o valor do cliente concatenado na mensagem do aviso sem tratamento (o estado do QA-11) | CT-75 (linhas 1–4) | 1 (ou ≤ 1) linha física depois da sentinela e o texto forjado fora dela; o mutante grava a linha `CRITICAL` falsa como segunda linha física |
| M205 | tratamento que troca só `\n` (`preg_replace('/\n/', …)`): sobra o `\r` do CRLF, que vai escapado no JSON do context e volta como quebra real pelo formatter (o K5 do QA-17) | CT-75 (linha 2) | exatamente 1 linha física depois da sentinela; o mutante produz 2, a segunda `" [2026-10-21 23:59:59] local.CRITICAL: linha forjada…"` — e a última asserção a acha pelo "contém" |
| M206 | o tratamento só no gancho da propriedade `flowKey`; a troca de aba registra a chave recusada crua | CT-75 (linha 4) | no máximo 1 linha física e o texto forjado fora da primeira; o mutante forja pela troca de aba |
| M207 | a recusa registrada duas vezes (no gancho da propriedade e de novo ao resolver a jornada no render) | CT-75 (linhas 1–3) | exatamente 1 linha física depois da sentinela; o mutante grava 2 |
| M208 | tratamento que troca só a sequência `\r\n` (`str_replace("\r\n", …)`): o `\n` sozinho passa | CT-75 (linha 1) | exatamente 1 linha física; o mutante produz 2, a segunda com o texto forjado |
| M209 | tratamento de `\r\n` e `\n` (`str_replace(["\r\n", "\n"], …)`), sem o `\r` sozinho | CT-75 (linha 3) | exatamente 1 linha física; o mutante deixa o `\r`, que o formatter devolve como quebra — 2 linhas, a segunda com o texto forjado |
| M210 | a recusa da propriedade registrada fora do canal do tutorial (canal padrão), com o valor cru: o arquivo observado não recebe nada e as negativas passam no vácuo | CT-75 (linhas 1–3) | exatamente 1 linha física depois da sentinela; o mutante deixa 0 |

Estouro do teto de mutantes da regra (perfil padrão, 2 a 5): M208–M210 nascem de achado medido do quality gate (QA-17, o K5 e o oráculo ancorado em `^\[`), como os mutantes da revisão adversarial — não contam para o teto (SKILL.md, passo 6, item 1).

---

## Regra R36 — Os botões e os rótulos de status do checklist aberto e da página "Primeiros passos" passam o axe no nível serious, nos temas claro e escuro

*(nova em 2026-10-22: QA-15 do quality gate, ciclo 3)*

> `P-27` ("os botões e os rótulos de status que o tutorial guiado mostra (checklist e página "Primeiros passos") têm contraste suficiente para leitura (WCAG AA), nos temas claro e escuro, como o resto das telas do kit"), `RQ-28` · perfil **padrão** (área C) · técnica: **EP** painel (tema do kit × tema próprio) × tema (claro × escuro) × estado do rótulo de status (sem passo concluído: a seguir e a fazer × com passo concluído: os três), com quatro controles positivos (checklist visível quando o axe roda, conteúdo da página presente, tema efetivo no documento, estado da linha na tela — o rótulo "Concluída" visível e não coberto pelo checklist) · grupo **G14** (`browser`)

*(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* — o QA-19 mediu o rótulo "Concluída" a 3,24:1 no tema claro, no `/admin` e no `/infra`; as quatro linhas do CT-B03 usavam persona sem passo concluído, e o MB10 o declarava. O estado exibido é partição obrigatória (passo 3, "Estado exibido"): o CT-B03 ganha as linhas 5–8, com passo concluído pela ação real, e MB17–MB19. Estouro do teto de mutantes (8) declarado no `05`.

Cenário e mutantes no `05`: **CT-B03** (MB7–MB11, MB17–MB19). Só o navegador computa contraste (princípio 3: pixel/acessibilidade). Escolha dos painéis (`/admin` e `/infra`) justificada no próprio CT-B03; o `/app` é a Q?2.

---

## Regra R37 — O checklist aberto fecha com a tecla Esc, sem gravar nada

*(nova em 2026-10-22: QA-16 do quality gate, ciclo 3)*

> `P-28` ("o checklist flutuante aberto fecha com a tecla Esc"), `RQ-28` · perfil **padrão** (área C) · técnica: **EP** por foco (no botão que abriu × dentro do painel) e por repetição (uma tecla × duas seguidas, sem esperar a resposta entre elas) com **rastreio de efeito** na direção "não aconteceu" — concluir, pular, dispensar, "lembrar depois" e "não mostrar mais", com destinatário (passos pendentes, jornada não dispensada) · grupo **G14** (`browser`)

*(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* — o QA-20 mediu que o tratador inverte o estado do painel (toggle): com uma tecla isso é indistinguível de fechar, e duas teclas seguidas reabrem. "Fecha" (P-28) é idempotente; o CT-B04 ganha a linha 3 (dois Esc seguidos, painel ausente depois de a página assentar) e MB20–MB21. Estouro do teto de mutantes (7) declarado no `05`.

Cenário e mutantes no `05`: **CT-B04** (MB12–MB16, MB20, MB21). "Fecha" é o observável de P-28; "sem gravar nada" é o invariante de quem fecha um diálogo (o `00` não dá ao Esc nenhum efeito além de fechar), e o rastreio de efeito o guarda contra o mutante que liga o Esc a um verbo do pacote que grava (MB15, MB16).

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | progresso: CT-19 (outra pessoa), CT-48 (outro painel), CT-50 (outra organização), CT-18 (jornada de outro painel); autoria (`{record}` de jornada e condição): CT-08, CT-09 | G6, G10, G3 |
| Autorização exercida na ação (não só `can()`) | CT-08 (as sete rotas), CT-45 (página), CT-46 (tela de autoria aberta), CT-13/CT-43 (ações do checklist depois do desligar), CT-18 | G3, G5, G6 |
| Autorização por fora do componente de UI | CT-08, CT-09, CT-45 (HTTP); checklist sem rota própria — desvio declarado em `## Costuras de Teste` | G3, G5 |
| Idempotência (ancorada no agregado) | CT-29 (re-semear, agregado persistido), CT-30 (edições, desativações, modo trocado e passo da autoria preservados), CT-35 (re-semear com multi-organização ligada) | G7, G10 |
| Concorrência | não se aplica: sem contador, saldo ou limite; o seeder roda de uma linha de comando, e a chave única do pacote (`onboarding_flows.key`, `(flow_id, key)`) é a barreira — `vendor/wallacemartinss/filament-onboarding/database/migrations/create_onboarding_tables.php.stub:up:24` | — |
| **Fronteira no ponto de entrada** (gravação) | CT-05 e CT-58 (tela), CT-02 (semente da migration), CT-18 (valores fora do domínio no checklist), CT-59 (comprimento na gravação do seeder), CT-75 (chave com quebra de linha não forja linha no log, P-26) | G2, G1, G6, G7 |
| **Domínio condicionado** (tipo × valor) | modo de conclusão × condição: CT-20 e CT-34 (contagem por modo), CT-24/CT-25/CT-49 (condição decide o passo de modo condição), CT-26 (visita) | G7, G8, G5, G10 |
| **Estado × operação** | matriz única em `## Mapa de Regras`: 20 células, todas com CT | G1, G3, G4, G5, G6, G9 |
| Cardinalidade (0 / 1 / N) | convites 0/1: CT-25, CT-49; passos por painel 6/11/15, 10/11/12 e 14/15: CT-20, CT-27, CT-34, CT-53; jornadas 3: CT-20; passos 32/38 na instalação: CT-38, CT-55; custo por cardinalidade de passos da mesma tela (N+1): CT-73 | G8, G7, G10, G11 |
| Ausente ≠ null ≠ vazio | variável ausente × `.env.example`: CT-01; motivo de isenção vazio: CT-40; `panel_id` nulo (vale para todos os painéis): CT-15, CT-16 | G1, G12, G5 |
| Paginação / ordenação | ordem de passo editada preservada: CT-30 (linha 5); a feature não cria listagem | G7 |
| Timezone / DST | não se aplica: sem dado temporal decidido pela feature | — |
| Unicode / limite de varchar | limite da coluna: CT-59 (todo valor semeado cabe e não chega truncado); acento e "⌘" nos dois idiomas: lacuna declarada — CT-32 afirma não vazio e pt_BR ≠ en, não a integridade do caractere; tentado: afirmar o título pt_BR literal, recusado porque o texto não é do `00` | G7 |
| Unicidade + soft delete | não se aplica: os models do pacote não usam `SoftDeletes` (apagar é físico — CT-31 cobre o recriar) | — |
| CRUD combinado | CT-31 (apagar → re-semear recria), CT-30 (criar na autoria → re-semear preserva), CT-33 (editar pela autoria: jornada, passo e condição), CT-08 (ver/editar com o tutorial desligado) | G7, G9, G3 |
| Mass assignment | `settings`: não se aplica — a SettingsPage grava só o estado do schema (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:save:62` e `:save:83`); `onboarding_*`: lacuna declarada — formulário de autoria do pacote, anterior à feature e não alterado por ela; nenhuma coluna de autorização nas tabelas (`create_onboarding_tables.php.stub:up:24`), escrito como se fosse falso no CT-33 (chave, painel e demais campos não mudam) | G9 |
| Upload | não se aplica: o seeder não envia mídia (Fora de Escopo); a mídia da jornada de teste do CT-13 é URL, não upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| **Superfície Livewire** (método público, prop pública) | todos os métodos públicos do checklist (`completeStep`, `skipStep`, `undoStep`, `startTour`, `tourProgress`, `finishTour`, `openMedia`, `videoProgress`, `dismissFlow`, `restoreFlow`, `restartFlow`, `selectFlow`, `startOnboarding`, `remindMeLater`, `neverShowOnboarding`, `showOnboardingAgain`, `toggle`) desligado: CT-13 (`/admin`), CT-43 (`/app`, `/infra`), com o controle ligado no CT-14; fora do domínio e tipo errado ligado: CT-18 (`stepKey`, `flowKey`, `selectFlow`/`$flowKey`, `tourProgress`, `$arguments['flow']`); Pages e relation manager da autoria já abertos: CT-46; `$onboardingScopeType`/`$onboardingScopeId`/`$onboardingPanel`: não se aplica — `#[Locked]` (`vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:$onboardingPanel:57`); `$isOpen`: só UI, `toggle` desligado em CT-13; `StartTourAction`: não se aplica — não usada (`02`) | G6, G3 |
| **Estado do framework usado sem validar** | não se aplica: a feature não consome `$filters`, `$tableFilters`, `$tableSearch` nem `$tableSortColumn` (`02`, "Valores do cliente que viram índice"); a ordenação do CT-46 é só o gatilho do update | — |
| **IDOR por entidade** (por tabela persistida) | `settings`: não se aplica — configuração da instalação, sem dono; `onboarding_flows`, `onboarding_steps`, `onboarding_conditions`: CT-08, CT-09, CT-46; `onboarding_step_progress`, `onboarding_flow_progress`: CT-19, CT-48, CT-50, CT-18, CT-13; `onboarding_preferences`: CT-13 (desligado), ligado: lacuna declarada — preferência só do sujeito resolvido no servidor (`02`, linha `completeStep`), sem cenário de segunda pessoa | G3, G6, G10 |
| **Escopo com discriminante nulo** (fecha ou abre?) | `panel_id` nulo abriria a jornada em todos os painéis — fecha: CT-15 (inclusive para `master_global`), CT-16; organização: progresso e convite por organização aberta (CT-49, CT-50); `master_global` sem painel no papel vê só a jornada do painel corrente (CT-15) | G5, G10 |
| **Acessibilidade** (contraste e teclado) *(novo em 2026-10-22: QA-15, QA-16)* | contraste no nível serious do axe, nos dois temas: CT-B03 (página e checklist aberto, `/admin` e `/infra`); estado do rótulo de status (a seguir, a fazer, concluído): CT-B03 (linhas 1–4 e 5–8) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*; fechar o diálogo pelo teclado: CT-B04 (Esc, foco no botão e dentro do painel); repetição da tecla antes da resposta (fechar idempotente): CT-B04 (linha 3) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*; ordem de Tab e foco devolvido ao botão depois do Esc: lacuna declarada — nem o `00` nem P-28 os pedem (QA-16 mediu que o Tab já alcança o botão) | G14 |
| **Saída do estado de erro** (4xx tem destino) | 404 da autoria → o `/admin` abre: CT-10; 404 da página "Primeiros passos" (CT-45) → o dashboard do mesmo painel abre sem o checklist (CT-11, linhas desligado); 404 da ação do checklist → próximo carregamento sem checklist e painel abrindo: CT-04, CT-58 | G3, G5, G1, G2 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a config lida do ambiente de fábrica desliga o tutorial | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M1, M2 |
| CT-02 | a migration de settings semeia o banco com o que o ambiente diz | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M3, M4 |
| CT-03 | depois do boot, o que está gravado decide, não o .env | R2 | tabela de decisão | G1 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M5, M6, M9 |
| CT-04 | o request seguinte obedece ao interruptor, nos dois sentidos | R2 | 2-switch | G1 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M7, M8 |
| CT-05 | a administradora liga e desliga o tutorial pela tela | R3 | EP, gate de escrita | G2 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M10, M11 |
| CT-06 | o settings do tutorial é um interruptor só | R3 | EP | G2 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M12, M13 |
| CT-07 | cada superfície lista a autoria só com o tutorial ligado | R4 | EP por superfície | G4 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M14, M15, M16, M17 |
| CT-08 | as sete rotas de autoria respondem 404 desligado, para qualquer persona | R5 | matriz papel × ação | G3 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M18, M19, M20, M21 |
| CT-09 | ligado, a permissão decide | R5 | matriz papel × ação | G3 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M17, M23 |
| CT-10 | depois do 404 da autoria, o /admin continua abrindo | R5 | saída do erro | G3 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M24 |
| CT-11 | tela do painel sem checklist, tour, boas-vindas e item com o tutorial desligado | R6 | EP por painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M25, M26, M27, M28, M29 |
| CT-12 | o widget de progresso some e volta, com o progresso intacto | R7 | 2-switch | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M30, M31, M32 |
| CT-13 | a ação do checklist montado é recusada depois do desligar | R8 | estado × evento, rastreio de efeito | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M33, M34, M35, M36, M37, M118, M119 |
| CT-14 | ligado, a mesma ação produz o efeito | R8 | rastreio de efeito (controle) | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M38 |
| CT-15 | o dashboard de cada painel traz o checklist da jornada daquele painel | R9 | EP por painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M39, M40, M41, M42, M43, M121 |
| CT-16 | a página "Primeiros passos" mostra a jornada do painel e nenhuma outra *(alterado em 2026-10-21: QA-12 do quality gate, ciclo 2)* | R9 | EP por painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M40, M42, M196 |
| CT-17 | as paradas de tour resolvem dentro do painel da jornada | R10 | EP | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M44, M45, M46 |
| CT-18 | entrada fora do domínio no checklist do /admin não grava fora dele | R11 | EP (fora do domínio, tipo) | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M47, M48, M49, M50 |
| CT-19 | o passo concluído por uma pessoa não conclui para a outra | R11 | identidade | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M51 |
| CT-20 | sem multi-organização, cada painel recebe a sua jornada com os passos da tabela | R12 | EP por painel | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M52, M53, M54, M55, M56, M57 |
| CT-21 | o seeder filho rodado sozinho cria só o passo da sua feature | R13 | EP por filho | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M58, M59, M60 |
| CT-22 | o interruptor gravado continua o mesmo depois de semear | R14 | EP | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M61, M62, M109 |
| CT-23 | toda chave de condição citada resolve no painel do passo | R15 | EP | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M63, M64 |
| CT-24 | "Perfil e 2FA" conclui só com o segundo fator confirmado, em cada painel | R15 | EP, painel | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M64, M65 |
| CT-25 | "Convites" do /admin conclui quando a própria pessoa já convidou | R15 | BVA 2-valores, identidade | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M63, M66, M67, M127 |
| CT-26 | "Configurações da aplicação" conclui pela visita à tela | R15 | EP | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M56, M68, M128 |
| CT-27 | desligar a feature esconde exatamente o passo dela no painel | R16 | EP decisor × painel | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M70, M71, M72, M73, M129, M130, M159 |
| CT-28 | o passo escondido sai do percentual e volta concluído ao religar | R16 | 2-switch | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M73, M74, M75 |
| CT-29 | o seeder pai rodado duas vezes deixa o banco igual | R17 | idempotência | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M76 |
| CT-30 | o que o administrador editou sobrevive ao re-semear | R17 | EP por edição | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M77, M78, M79, M132, M133, M134 |
| CT-31 | o que falta é recriado, e o resto fica como está | R17 | EP por ausência | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M77, M80, M81 |
| CT-32 | textos semeados nos dois idiomas | R18 | EP | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M82, M83, M84, M135 |
| CT-33 | a autoria grava os dois idiomas de uma jornada semeada | R18 | gate de escrita | G9 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M85, M136, M137 |
| CT-34 | com multi-organização, /admin e /app ganham os passos de organização | R19 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M86, M87, M88, M138, M139 |
| CT-35 | ligar a multi-organização depois e re-semear acrescenta só o que falta | R19 | 2-switch | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M89 |
| CT-36 | Projetos aparece no /app da organização só com a demo ligada | R19 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M87, M90 |
| CT-37 | dentro da organização, checklist e página seguem o interruptor | R19 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M91, M140 |
| CT-38 | o kit:install semeia conforme modo e resposta, tutorial desligado | R20 | tabela de decisão | G11 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M92, M93, M94, M95, M96, M141, M142, M144 |
| CT-39 | a rule de onboarding está em .ai/rules, indexada, cobre as telas | R21 | EP | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M97, M98, M99, M145 |
| CT-40 | o inventário acusa exatamente a tela, a isenção ou o filho defeituoso | R22 | EP | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M100, M101, M102, M103 |
| CT-41 | com multi-organização, toda tela de organização tem seeder ou isenção | R22 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M104 |
| CT-42 | a página do tutorial guiado de cada idioma ensina a semear e tabela os seeders | R23 | EP por idioma | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M105, M106, M107, M108, M146, M147 |
| CT-43 | no /app e no /infra, a ação do checklist montado é recusada depois do desligar | R8 | estado × evento, EP por painel | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M120 |
| CT-44 | "Primeiros passos" some de toda superfície de navegação com o tutorial desligado | R6 | EP por superfície | G4 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M114, M164 |
| CT-45 | a URL da página "Primeiros passos" responde 404 desligado, nos três painéis | R6 | EP por painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M115, M116, M117 |
| CT-46 | a tela de autoria aberta recusa a ação depois do desligar | R5 | estado × evento | G3 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M112, M113, M163 |
| CT-47 | a permissão da página decide quem vê o checklist, e todo papel a recebe | R9 | matriz papel × painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M122, M123, M166 |
| CT-48 | concluir num painel não conclui o passo de mesmo nome em outro | R11 | EP por painel | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M124, M125, M168 |
| CT-49 | "Convites" do /app conclui só na organização em que a pessoa convidou | R24 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M148, M149, M170 |
| CT-50 | concluir numa organização não conclui na outra | R24 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M150 |
| CT-51 | desligar a multi-organização esconde os passos de organização sem apagar | R25 | 2-switch | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M151, M152, M171 |
| CT-52 | filho de organização sozinho, multi-organização desligada, não cria nada | R25 | EP | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M153, M173 |
| CT-53 | tirar a permissão da tela de um passo tira o passo do checklist | R26 | matriz papel × tela | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M154, M174 |
| CT-54 | a persona padrão de cada painel abre a tela de todo passo visível | R26 | EP por painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M155, M156 |
| CT-55 | com multi-organização, kit:install --onboarding semeia os passos de organização | R20 | tabela de decisão | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M143 |
| CT-56 | todos os visíveis concluídos e um escondido pendente: jornada concluída | R16 | BVA (100 %) | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M131 |
| CT-57 | cada arquivo da feature está nas duas rotas de entrega | R27 | EP arquivo × rota | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M157, M158 |
| CT-58 | o que a tela grava vale no request seguinte, nos dois sentidos | R3 | 2-switch | G2 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoInterruptorTest.php` | M110, M111 |
| CT-59 | todo valor semeado em coluna com limite cabe no limite | R12 | BVA (comprimento) | G7 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M126 |
| CT-60 | o widget conta como concluída a jornada com um passo escondido pendente | R16 | BVA (100 %) | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M131, M160 |
| CT-61 | a jornada concluída continua concluída quando o passo escondido volta | R16 | 2-switch | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M161, M162 |
| CT-62 | ligado e sem a permissão da página, a URL responde 403 | R9 | matriz papel × painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M167 |
| CT-63 | dentro da organização, a ação do checklist montado é recusada depois do desligar | R8 | estado × evento | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M165 |
| CT-64 | desligar a multi-organização esconde os passos de organização do /app sem apagar | R25 | EP | G10 | Pest feature HTTP + componente Livewire/Filament | `tests/Tenancy/OnboardingGuiadoTenancyTest.php` | M172 |
| CT-65 | o passo concluído cuja tela a pessoa deixa de abrir sai também do numerador | R26 | 2-switch | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M175, M176 |
| CT-66 | a chave de uma jornada invisível escrita direto no checklist não muda o checklist | R28 | EP | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M177, M178, M179 |
| CT-67 | com a semeadura do tutorial falhando, o kit:install mostra o resumo e o aviso | R29 | rastreio de efeito | G11 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M180, M181, M182 |
| CT-68 | o passo do dashboard dinâmico segue a permissão de montar o dashboard do painel | R30 | tabela de decisão | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M183, M184, M185, M186 |
| CT-69 | com tudo ligado, as personas do painel cobrem todos os passos semeados | R31 | EP por painel | G8 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoSemeaduraTest.php` | M187, M188, M189 |
| CT-70 | o canal de log do tutorial resolve, e a declaração do projeto vence | R32 | tabela de decisão | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M190, M191, M192, M193 |
| CT-71 | o config/logging.php do projeto não está nos caminhos do kit:update | R32 | EP | G12 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoInstalacaoTest.php` | M194, M195 |
| CT-72 | a página "Primeiros passos" abre com um passo já concluído e o mostra concluído | R33 | EP modo × painel | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M197, M198, M199, M200 |
| CT-73 | dez passos a mais que ensinam a tela de Usuários não acrescentam consultas | R34 | cardinalidade com controle | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M201, M202 |
| CT-74 | a decisão de acesso à tela vale só para o request em que foi tomada | R34 | 2-switch | G5 | Pest feature HTTP | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M203 |
| CT-75 | uma chave de jornada com quebra de linha não cria linha nova no canal de log do tutorial *(alterado em 2026-10-22: QA-17 do quality gate, ciclo 3)* | R35 | EP, rastreio de efeito | G6 | componente Livewire/Filament | `tests/Kit/OnboardingGuiadoConsumoTest.php` | M204, M205, M206, M207, M208, M209, M210 |
| CT-B01 | o tour do passo de Usuários abre no navegador a partir do checklist do /admin (`05`) | R9 | EP por painel | G13 | browser | `tests/Browser/OnboardingGuiadoTest.php` | MB1, MB2, MB3, MB4 |
| CT-B02 | o tour do passo de Pulse abre no navegador a partir do checklist do /infra (`05`) | R9 | EP por painel | G13 | browser | `tests/Browser/OnboardingGuiadoTest.php` | MB5, MB6 |
| CT-B03 | a página "Primeiros passos" com o checklist aberto não tem problema sério de acessibilidade, no /admin e no /infra, claro e escuro, sem e com passo concluído (`05`) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | R36 | EP painel × tema × estado do rótulo, controle positivo | G14 | browser | `tests/Browser/OnboardingGuiadoTest.php` | MB7, MB8, MB9, MB10, MB11, MB17, MB18, MB19 |
| CT-B04 | a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada, com uma tecla e com duas seguidas (`05`) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | R37 | EP por foco e por repetição, rastreio de efeito | G14 | browser | `tests/Browser/OnboardingGuiadoTest.php` | MB12, MB13, MB14, MB15, MB16, MB20, MB21 |

## Tabela de cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| reversibilidade própria da migration de settings (`down` remove, `up` recria) | já provada para toda migration de settings por `tests/Kit/ConfiguracoesDoKitTest.php` (`down` na ordem inversa e `up` de novo, contagem do mapa × banco); e rollback não é cláusula do `00` |
| ⌘K por **registro** (jornada pelo título) com o tutorial desligado | mata o mesmo M14 que as linhas de hub/dock do CT-07 |
| item "Primeiros passos" e checklist em cada tela de cada painel, não só no dashboard | o render hook é global ao painel; uma tela por painel mata M25/M28; a regra "toda tela" é do pacote |
| `assertNoJavaScriptErrors()` no `/infra` com o checklist ligado | oráculo proibido sozinho; o CT-B01 já carrega a asserção de apoio na tela onde o tour roda |
| tour que navega para outra tela (parada com rota de outra página) no navegador | o CT-17 prova a rota no backend; o navegador só acrescentaria a navegação do runner do pacote. O teto do perfil padrão (1 CT-B) foi estourado uma vez, pelo CT-B02 no `/infra` (ADV-26), e não mais |
| cenário por fora da UI para a ação do checklist pela rota de update do Livewire | mesma recusa que o CT-13 prova; fica como opção da sessão (ver Gate de camada) |
| linha "recriar o passo apagado pelo administrador" como pergunta | o literal "cria só o que falta (pela key)" decide; virou a linha 1 do CT-31 |
| contagem exata de condições semeadas | depende do mecanismo (banco × código, D6); o CT-23 afirma o que o `00` pede (existem) e o CT-29 a estabilidade |
| log `warning` do guarda e `info` do seeder | Proibição 12 — sem cláusula de auditoria |
| CT-B no `/app` com organização, além do `/infra` | o CT-B02 cobre o segundo painel com tema próprio; a URL dentro da organização é provada no backend (CT-34, última asserção) |
| linha `admin_app` no CT-47 | o papel só existe na suíte Tenancy (`.ai/rules/testes.md`); virou a linha 3 do CT-37 |
| linha "status 403 da página sem a permissão" no CT-47 | outro `Quando` e outro `Então`; virou Q?17 → Q27 → P-18, cenário próprio CT-62 |
| CT próprio para os verbos restantes do checklist (ADV-01) | cabem no `Esquema` do CT-13 sem custo de cenário (um `Esquema` conta 1); o controle de cada um entrou no CT-14 |
| CT-B03 no `/app` (QA-15 mediu 7/6 violações lá) | o defeito é a mesma regra CSS do pacote nos três painéis; o `/app` só mata um mutante a mais se carregar tema próprio, distinto do `/admin` — Q?2 decide; se sim, a linha entra (o gate vence o teto) |
| CT-B03 com uma linha "dashboard com o tutorial desligado" como controle | não mata mutante (gate, item 4); virou pré-requisito medido no `05` (linha de base do axe no documento inteiro) |
| CT-B03 com `getComputedStyle` da cor do botão | P-27 diz "contraste AA", não qual cor; fixar a cor seria oráculo do plano. O axe mede a razão, que é a cláusula |
| CT-B04 no `/infra` e no `/app` | mesmo componente e mesma view nos três painéis; nenhum mutante plausível do Esc depende do painel (QA-16: igual nas seis combinações) |
| CT-B04 com a abertura por clique, além do Enter | a abertura não é a regra; o que muda o resultado do Esc é o foco, e a linha 2 já move o foco para dentro do painel |
| CT-B04 afirmando o foco devolvido ao botão depois do Esc | nem o `00` nem a P-28 pedem; registrado como lacuna declarada na taxonomia (Acessibilidade) |
| CT-B03 linhas 5–8 sem as linhas 1–4 (a página com um passo concluído também mostra os rótulos a seguir e a fazer) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | não cortado: a derivação não confirma pelo `00` que a página com passo concluído mostra os outros dois estados; Q?1 do ciclo 4 |
| CT-B03 com `getComputedStyle` do verde do rótulo "Concluída" *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | mesmo motivo da linha do botão: a P-27 diz "contraste AA", não qual verde; o axe mede a razão |
| CT-B03 com passo concluído no `/app` *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | Q33: os três painéis usam a mesma paleta; o `/app` não mata mutante que o `/admin` não mate |
| CT-B04 com duas teclas Esc e o foco dentro do painel *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | o MB20 e o MB21 não dependem do foco (o tratador é o mesmo); a linha 2 já cobre o foco interno com uma tecla |
| CT-B04 com três teclas Esc *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | número ímpar: o toggle termina fechado e passa; não mata o MB20. Duas é o menor número que discrimina |
| CT-B04 linha 3 com espera explícita entre as teclas (20, 150, 400 ms) *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* | `wait($segundos)` é proibido (fato 3) e o QA-20 já mediu a faixa; o `keys()` com as duas teclas cai dentro dela |
| CT-75 com U+2028 e U+0085 (NEL) | o formatter não os transforma em `\r`/`\n`; não abrem linha física para quem lê o arquivo por `\r\n`, `\n` ou `\r`, e o QA-11 (ciclo 3, dimensão B) mediu 1 linha pelo app. Sem mutante plausível que só eles matem |

## Fechamento da revisão adversarial

Revisão do `fw-adversario-ct` (ADV-01..ADV-38), fechada pela sessão em 2026-10-21 (`adv-fechamento.md`). Todos os achados foram fechados; nenhum ficou como lacuna nova.

| Achado | Destino |
|---|---|
| ADV-01 | CT-13 ganhou `openMedia`, `videoProgress`, `restoreFlow`, `selectFlow`, `startOnboarding`, `remindMeLater`, `showOnboardingAgain`; CT-14 o controle de cada um; fixture com mídia e `jornada-b`; M118, M119 |
| ADV-02 | CT-43 (`/app`, `/infra`); M120 |
| ADV-03 | CT-44 ("Primeiros passos" no hub, ⌘K e dock); M114 |
| ADV-04 | P-04 → CT-45, CT-37 (última linha); M115–M117 |
| ADV-05, ADV-06, ADV-22 | CT-27 linhas 10–12 (persona do outro painel); M129 |
| ADV-07 | CT-56 (borda de 100 % com passo escondido; widget); M131 |
| ADV-08, ADV-09 | CT-30 linhas 7–10 (jornada e condição desativadas, modo trocado, passo criado na autoria); M132–M134 |
| ADV-10, ADV-17 | CT-38 coluna de passos (32); CT-55 (38 com multi-organização); M143, M144 |
| ADV-11 | CT-38 última asserção (`.env` sem a variável `true`); M142 |
| ADV-12 | P-17 → CT-52; M153 |
| ADV-13, ADV-23 | CT-34: toda parada de toda jornada resolve no painel e, com a Acme aberta, sob `/app/acme`; M138 |
| ADV-14 | CT-34 contagem por modo das jornadas do `/admin` e do `/app` com multi-organização (P-08); M139 |
| ADV-15 | CT-25 linhas por estado do convite; M127 |
| ADV-16 | R27, CT-57; M157, M158 |
| ADV-18 | CT-42 tela por seeder; M146 |
| ADV-19 | CT-32 descrição de passo e corpo de parada diferem entre pt_BR e en; M135 |
| ADV-20 | CT-33 chave, painel e demais campos inalterados; M136 |
| ADV-21 | CT-11: oráculo invertido corrigido — marcação de tour não afirmada nas linhas "ligado" |
| ADV-24, ADV-25 | CT-58 (desliga e religa pela tela); M110, M111 |
| ADV-26 | CT-B02 (`05`, `/infra`) |
| ADV-27, ADV-28, ADV-29 | `Quando` com sequência declarado como evento único de 2-switch (CT-04, CT-12, CT-28, CT-51, CT-58, CT-61, CT-65) — `## Perfil de Derivação` |
| ADV-30 | **recusado** (ADV2-15): o recorte que ele apontava já está coberto — "Primeiros passos" some de todas as superfícies é o CT-44, e o status da URL com o tutorial desligado é o CT-45 (P-04) |
| ADV-31 | P-15 → CT-39 segunda asserção; M145 |
| ADV-32 | P-16 → CT-42 segunda asserção; M147 |
| ADV-33 | CT-33 virou `Esquema` (jornada, passo, condição); M137 |
| ADV-34 | CT-15 linhas `master_global`; M121 |
| ADV-35 | P-14 → CT-48; M124, M125 |
| ADV-36 | P-11 → CT-53, CT-54; M154–M156 |
| ADV-37 | P-12 → CT-51 (M151, M152); P-13 → CT-27 linha 13 (M130) |
| ADV-38 | CT-59; M126 |
| Q11–Q17 (P-04..P-10) | P-04 CT-45; P-05 CT-46 (M112, M113); P-06 CT-47, CT-37 (M122, M123, M140); P-07 CT-26 linha 3 (M128); P-08 CT-34, CT-49 (M139, M148, M149); P-09 CT-50 (M150); P-10 CT-38 linha 5 (M141) |

Re-revisão: feita (2ª rodada, ADV2-01..ADV2-16), fechada abaixo. Teto de 2 rodadas atingido.

## Fechamento da 2ª rodada adversarial (última)

Revisão do `fw-adversario-ct`, 2ª rodada (ADV2-01..ADV2-16), fechada pela sessão em 2026-10-21 (`adv2-fechamento.md`). Teto de 2 rodadas atingido; sem nova revisão. Nenhum ID existente foi renumerado.

| Achado | Destino |
|---|---|
| ADV2-01 | CT-59: o limite vem das migrations de onboarding do kit (255 / 2048), nunca do schema do SQLite; M126 reescrito |
| ADV2-02 | CT-53 linhas 4–7 (`/infra` com Pulse, `/app` com o Hub do negócio); M174 |
| ADV2-03, ADV2-12 | CT-65 (concluir → perder a permissão: 0 de 10); CT-51 afirma o numerador (0 de 11; 1 de 12 ao religar); M171, M175, M176 |
| ADV2-04 | CT-64 (`/app`); M172 |
| ADV2-05 | CT-27 linhas 14–18 (`/app`); M159 |
| ADV2-06 | CT-49 linha 4 (convite do Davi na Acme não conclui o passo da Carla); M170 |
| ADV2-07 | CT-46 linhas 5 e 6 (telas de criar jornada e condição); M163 |
| ADV2-08 | CT-52 linhas 4–6 (os seis filhos de organização); M173 |
| ADV2-09 | CT-47 linha 6 (`/app`, `panel_user` sem a permissão); M166 |
| ADV2-10 | CT-63 (`/app/{organização}`, suíte Tenancy — não cabe no `Esquema` do CT-43, que roda na suíte Kit); M165 |
| ADV2-11 | CT-44 linhas 10–18 (hub do `/app`, hub e dock do `/infra`, ⌘K do `/app`, e os controles positivos); M164 |
| ADV2-13 | `@premissa P-11`: CT-36 (contagem relativa ao controle `N₁`), CT-37 linha 2 (invariante no lugar de "11 passos"), CT-54 (invariante nos dois sentidos); M87, M90 reescritos |
| ADV2-14 | CT-56 com um `Quando` só; a contagem do widget foi para o CT-60; M160 |
| ADV2-15 | ADV-30 registrado como **recusado**, com o CT-44 e o CT-45 nomeados (tabela da 1ª rodada) |
| ADV2-16 | CT-48 linhas 3 e 4 (par `/admin` × `/infra`); M168 |
| Q27 / P-18 | CT-62 (403 sem a permissão, tutorial ligado); M167 |
| Q28 / P-19 | CT-61 (2-switch a partir do estado do CT-56: religar o dock); M161, M162 |

### Para materializar só a diferença

CT **novos** (inteiros): CT-60, CT-61, CT-62 e CT-65 (`tests/Kit/OnboardingGuiado{Consumo,Semeadura}Test.php`); CT-63 e CT-64 (`tests/Tenancy/OnboardingGuiadoTenancyTest.php`).

CT **existentes que ganharam linhas de `Exemplos`** (as linhas antigas não mudaram):

| CT | Linhas novas | Arquivo |
|---|---|---|
| CT-27 | 14–18 (`/app`: controle `N₀`, hub, dock, dinâmico; dinâmico só no `/app` visto do `/admin`) | Kit |
| CT-44 | 10–18 | Kit |
| CT-46 | 5 e 6 (criar jornada, criar condição) | Kit |
| CT-47 | 6 (`/app`) | Kit |
| CT-48 | 3 e 4 (`/admin` × `/infra`) — e o `Dado` passou a dar também o papel `admin` à Íris | Kit |
| CT-49 | 4 (convite do Davi) | Tenancy |
| CT-52 | 4–6 (Membros, Convites recebidos, Troca de organização) | Kit |
| CT-53 | 4–7 — e o `Dado`/`Quando` ganharam `<persona>`/`<painel>` (colunas novas, preenchidas nas linhas 1–3 com a administradora e o `/admin`, o mesmo de antes) | Kit |

CT **existentes cujo oráculo mudou** (rever o teste já escrito):

| CT | O que mudou |
|---|---|
| CT-36 | as contagens deixaram de ser 11/10 e passaram a `N₁` (controle da própria tabela) e `N₁ − 1` |
| CT-37 | linha 2: "11 passos" virou "só com passos cuja tela ela abre" |
| CT-51 | as contagens viraram "0 concluídos de 11 visíveis" e "1 concluído de 12 visíveis" (numerador acrescentado) |
| CT-54 | o `Quando` percorre todos os passos com tela da jornada, e o `Então` ganhou "todo passo cuja tela responde 403 para ela está ausente do checklist" |
| CT-56 | a asserção do widget saiu (foi para o CT-60); o `Quando` ficou só com a montagem do checklist |
| CT-59 | o limite comparado é o declarado nas migrations (constante do teste), não o do schema |

## Fechamento do quality gate, ciclo 2

Achados de destino 3 do `06-relatorio-qa.md` (ciclo 2), derivados em 2026-10-21 a partir do `00` (RQ-28, P-22, P-25, P-26) e das seções QA-08, QA-09, QA-11 e QA-12 do `06`. Nenhum ID existente foi renumerado. Revisão adversarial: não refeita — o teto de 2 rodadas foi atingido (`## Fechamento da 2ª rodada adversarial`); fica a cargo da sessão decidir se os quatro cenários novos pedem uma rodada extra.

| Achado | Destino |
|---|---|
| QA-08 | R33, CT-72 (página com passo concluído: três modos × três painéis); M197–M200 |
| QA-09 | P-25 → R34, CT-73 (consultas com controle) e CT-74 (a decisão não atravessa o request); M201–M203 |
| QA-11 | P-26 → R35, CT-75 (LF, CRLF, troca de aba; sentinela como controle); M204–M207 |
| QA-12 | CT-16 re-derivado: linha `/app` com 5 passos (P-22), contagem exata nas três linhas, oráculo pela chave `primeiros-passos-{painel}` com controle positivo; M40 reescrito; M196 |

### Para materializar só a diferença

- CT **novos** (inteiros): CT-72, CT-73, CT-74 e CT-75, em `tests/Kit/OnboardingGuiadoConsumoTest.php`. Cada um tem de falhar antes da correção (CT-72 com 500; CT-73 com ≈ C₀ + 30; CT-75 com a linha `CRITICAL` forjada nas linhas 1 e 2). O CT-74 pode passar antes da correção (o estado atual não memoriza): ele guarda a correção, e a prova dele é rodar contra a memória estática numa cópia descartável
- CT-16 **cujo oráculo mudou**: dataset `app` de 6 para 5 e `toBe($passos)` também no `/app` (sai a faixa `1..6` com `@premissa P-11`); asserção positiva da chave do próprio painel antes das negativas
- **Fora do `04`** (rótulo de teste, não oráculo): o dataset do CT-37 em `tests/Tenancy/OnboardingGuiadoTenancyTest.php` ainda se chama `'200, 11 passos'`; o `04` já diz "só com passos cuja tela ela abre" (ADV2-13). Ver Q?2

Perguntas do ciclo 2 (numeração provisória; a sessão renumera):

❓ Q30 (era Q?1; decidida pela sessão em 2026-10-21: seguir a ➡️, conferir no primeiro vermelho) · raia: desenho · afeta: CT-16 · depende de: —
A página "Primeiros passos" expõe no corpo a chave da jornada (`primeiros-passos-{painel}`)? O CT-16 afirma a chave do próprio painel como controle positivo antes das negativas; se a página não a renderiza, a negativa é vácua.
➡️ Recomendação: confirmar no primeiro vermelho do CT-16. Se a chave não estiver no corpo, trocar o par por um passo **exclusivo** de outro painel lido na lista de passos da página (ex.: "Pulse", só do `/infra`, ausente em `/admin/onboarding` e `/app/onboarding`; "Usuários", só do `/admin`, ausente nos outros dois), com o passo exclusivo do próprio painel como controle — nunca voltar ao título, que é igual nas três.

❓ Q31 (era Q?2; decidida pela sessão em 2026-10-21: rótulo pela invariante e linha 2 com a P-22) · raia: desenho · afeta: CT-37 · depende de: —
A linha 2 do CT-37 diz "só com passos cuja tela ela abre" (P-11), mas a P-22 também esconde da Carla (`panel_user`) o passo do dashboard dinâmico, cuja tela ela **abre** (só não pode montar). O texto do `04` fica incompleto, e o rótulo do dataset (`'200, 11 passos'`) está defasado no teste.
➡️ Recomendação: na materialização, renomear o rótulo para a invariante (ex.: `'200, só passos que ela vê (P-11, P-22)'`) e, se a sessão autorizar mexer no CT-37, acrescentar "e sem o passo do dashboard dinâmico (P-22)" à linha 2. Esta derivação não reescreveu o CT-37.

## Fechamento do quality gate, ciclo 3

Achados de destino 3 do `06-relatorio-qa.md` (ciclo 3), derivados em 2026-10-22 a partir do `00` (P-26, P-27, P-28, RQ-28) e só das seções QA-15, QA-16 e QA-17 do `06`. Nenhum ID existente foi renumerado; o CT-75 foi reescrito com o mesmo ID. **Revisão adversarial: não rodada** — o teto de 2 rodadas foi atingido (`## Fechamento da 2ª rodada adversarial`); os dois CT-B novos e o CT-75 reescrito ficam sem re-revisão independente, registrado aqui como débito para o quality gate.

| Achado | Destino |
|---|---|
| QA-15 | P-27 → R36, CT-B03 (`05`: página "Primeiros passos" com o checklist aberto, `/admin` e `/infra` × claro e escuro, `assertNoAccessibilityIssues()` nível serious, com três controles positivos); MB7–MB11; costura nova G14 (`browser`, a confirmar pela sessão) |
| QA-16 | P-28 → R37, CT-B04 (`05`: Esc com o foco no botão que abriu e dentro do painel; não-efeito sobre progresso, preferências e recarga); MB12–MB16; G14 |
| QA-17 | CT-75 reescrito (mesmo ID): linhas físicas depois da sentinela (exatamente 1 pela propriedade, no máximo 1 pela troca de aba), texto forjado proibido fora da primeira linha nova, linha `CR (\r)` nova; M205 agora só `\n`, morto pela linha 2; M208 (só `\r\n`, linha 1), M209 (sem o `\r` sozinho, linha 3), M210 (recusa fora do canal observado, controle) |

### Para materializar só a diferença

- CT-B **novos** (inteiros): CT-B03 e CT-B04, em `tests/Browser/OnboardingGuiadoTest.php`. Cada um tem de falhar antes da correção: o CT-B03 com `color-contrast` serious nas quatro linhas (o estado atual é o MB7); o CT-B04 com o painel ainda visível depois do Esc nas duas linhas (o estado atual é o MB12). Antes do primeiro vermelho do CT-B03, medir a linha de base do `05` (dashboard com o tutorial desligado, dois temas)
- CT-75 **cujo oráculo mudou** (`tests/Kit/OnboardingGuiadoConsumoTest.php`): (1) separar por `/\r\n|\n|\r/` em vez de `/\R/`; (2) trocar o `array_filter` por `^\[` pela contagem de todas as linhas físicas depois da sentinela (sem o trecho vazio final), com `toBe(1)` nas linhas da propriedade e `toBeLessThanOrEqual(1)` na troca de aba; (3) trocar o `assertStringStartsNotWith` por "não contém `local.CRITICAL: linha forjada`" em toda linha física exceto a primeira depois da sentinela; (4) dataset novo `'propriedade, CR' => ['propriedade', "\r"]` e a coluna do esperado. Prova: o K5 do QA-17 (`preg_replace('/\n/', …)`) tem de ficar vermelho na linha CRLF numa cópia descartável, e o código atual (`\s+`) verde nas quatro

Perguntas do ciclo 3 (numeração provisória; a sessão renumera):

❓ Q32 (era Q?1; decidida pela sessão em 2026-10-22: seguir a ➡️, linha de base antes do primeiro vermelho) · raia: desenho · afeta: CT-B03 · depende de: —
O `assertNoAccessibilityIssues()` do plugin roda o axe no documento inteiro, sem escopo nem filtro de regra. Se a página "Primeiros passos" (ou o shell do painel) tiver uma violação serious alheia ao tutorial — não de contraste, ou de outra peça do kit —, o CT-B03 fica vermelho depois da correção do QA-15, por motivo que não é o dele.
➡️ Recomendação: medir a linha de base do `05` antes do primeiro vermelho. Se o dashboard com o tutorial desligado passar nos dois temas, seguir com o oráculo do documento inteiro (é o que a P-27 compara: "como o resto das telas do kit"). Se não passar, registrar a violação alheia como achado próprio e **não** trocar o oráculo por um `axe.run` escopado sem decisão da sessão.

❓ Q33 (era Q?2; decidida pela sessão em 2026-10-22: os três painéis usam `CorPrimaria::paleta()`, sem linha do `/app`) · raia: desenho · afeta: CT-B03 · depende de: —
O `/app` carrega o mesmo CSS de tema que o `/admin`? O CT-B03 cobre o `/admin` (tema do kit) e o `/infra` (tema próprio) porque o defeito é o mesmo nos três e o que distingue os painéis é onde a correção mora (MB9).
➡️ Recomendação: conferir na materialização a configuração de tema/cores dos três painéis. Se o `/app` tiver tema ou `primary` próprio, acrescentar as linhas `/app` × claro e escuro ao `Esquema` do CT-B03 (o gate vence o teto); se compartilhar o do `/admin`, manter o corte.

❓ Q34 (era Q?3; decidida pela sessão em 2026-10-22: o `selectFlow` não registra recusa, a linha 4 fica "no máximo 1") · raia: desenho · afeta: CT-75 (linha 4) · depende de: —
A troca de aba (`selectFlow`) com chave recusada registra a recusa no canal do tutorial? Se registra, a linha 4 pode passar de "no máximo 1" a "exatamente 1" e ganhar o mesmo controle positivo das linhas 1–3 (o M210 pela troca de aba).
➡️ Recomendação: conferir no primeiro verde da linha 4 a contagem real (0 ou 1). Se for 1, apertar para "exatamente 1"; se for 0, manter "no máximo 1" — o log não é cláusula, e a linha segue como guarda do M206.

## Fechamento do quality gate, ciclo 4

*(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*

Achados de destino 3 do `06-relatorio-qa.md` (ciclo 4), derivados em 2026-10-22 a partir do `00` (P-27, P-28, RQ-28) e só das seções QA-19 e QA-20 do `06`. Nenhum ID foi renumerado nem criado: os cenários são os mesmos (CT-B03, CT-B04), com linhas novas no `Esquema`; os mutantes continuam a série do `05` (MB17–MB21). **Revisão adversarial: não rodada** — o teto de 2 rodadas foi atingido (`## Fechamento da 2ª rodada adversarial`); as linhas novas ficam sem re-revisão independente, registrado aqui como débito para o quality gate (o mesmo do ciclo 3).

| Achado | Destino |
|---|---|
| QA-19 | P-27 → R36, CT-B03 linhas 5–8 (`05`: persona com um passo concluído pela ação real — `/admin` "Configurações da aplicação" por visita, `/infra` "Perfil e 2FA" por condição —, `/admin` e `/infra` × claro e escuro); controle 4 novo (linhas 1–4: nenhum rótulo de concluído; linhas 5–8: o rótulo "Concluída" visível **e não coberto** pelo checklist antes do axe, porque o axe classifica texto coberto como `incomplete`, não violação); MB17 (verde 600 do pacote sobre branco, 3,24:1 — o estado atual), MB18 (correção só no tema do `/admin`), MB19 (verde escurecido sem escopo de tema) |
| QA-20 | P-28 → R37, CT-B04 linha 3 (`05`: foco no botão, `keys(…, ['Escape', 'Escape'])` sem esperar a resposta, painel ausente depois de `waitForEvent('networkidle')`); assentamento estendido às linhas 1 e 2; MB20 (o tratador inverte em vez de fechar — o estado atual), MB21 (guarda no cliente que lê o estado antes de a resposta voltar) |

Correção de afirmação do `05` feita junto: o `assertMissing` do plugin **não** reexecuta até o `timeout()` — lê `isVisible()` uma vez (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesElementAssertions.php:assertMissing:532`). Sem o assentamento, a linha 3 do CT-B04 seria verde com o MB20 vivo, ou vermelha com uma correção certa feita no servidor, conforme a corrida.

### Para materializar só a diferença

- CT-B03 **linhas novas** (`tests/Browser/OnboardingGuiadoTest.php`): o dataset `onboarding-contraste` ganha a coluna do estado (`bool $comPassoConcluido`) e as linhas 5–8; nas linhas com passo concluído, a ação real antes do `visit()` (GET de `/admin/configuracoes-da-aplicacao`; `breezySession` confirmada da Íris), depois `fronteiraDeRequest()`; o controle 4 (`assertNotPresent` nas linhas 1–4; `assertVisible` + `assertScript` com `elementFromPoint` nas 5–8) entra depois de abrir o checklist e antes do `assertNoAccessibilityIssues()`. Tem de falhar antes da correção: linhas 5 e 7 vermelhas com `color-contrast` em `.fio-tile--done .fio-tile-status`; as linhas 1–4 seguem verdes; as 6 e 8 podem estar verdes já (QA-19: escuro dá 0)
- CT-B04 **linha nova e passo novo**: o dataset ganha a coluna das teclas (`array $teclas`) e a linha 3 (`'botao'`, `['Escape', 'Escape']`); `keys($seletorDoFoco, $teclas)` e `waitForEvent('networkidle')` antes do `assertMissing($painel)`, nas três linhas. Tem de falhar antes da correção: a linha 3 vermelha (painel visível depois de assentar); as linhas 1 e 2 seguem verdes
- Contagens: o `04` não muda (75 cenários, 37 regras, 207 mutantes, 0 sem matador); o `05` passa a 21 mutantes MB, com os mesmos 4 CT-B

Perguntas do ciclo 4 (numeração provisória; a sessão renumera):

❓ Q35 (era Q?1; decidida pela sessão em 2026-10-22: manter as oito linhas, conferir no primeiro verde) · raia: desenho · afeta: CT-B03 (linhas 1–4) · depende de: —
Com um passo concluído e os demais pendentes, a página "Primeiros passos" mostra ao mesmo tempo os três estados do rótulo (concluído, a seguir, a fazer)? Se mostra, as linhas 5–8 matam tudo o que as linhas 1–4 matam (MB7–MB11), e as 1–4 viram redundantes pela poda (passo 7: "dois cenários que matam o mesmo conjunto → manter um"). A derivação não pode confirmar pelo `00`, e o `app/` está fora da leitura.
➡️ Recomendação: manter as oito linhas até o primeiro verde das linhas 5–8. Se, nele, a página com passo concluído mostrar também os rótulos a seguir e a fazer (conferir no relatório do axe ou com `assertVisible` dos dois na mesma linha), a sessão pode podar as linhas 1–4 — registrando a poda nesta tabela de cogitado e cortado; se não mostrar, as oito ficam.
