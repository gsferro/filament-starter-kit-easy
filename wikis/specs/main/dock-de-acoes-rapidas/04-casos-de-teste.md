# Casos de Teste — Dock de ações rápidas (favoritos do usuário)

> Requisito: `00-requisito.md` (com os Adendos 1 a 5 e as premissas P-02, P-03, P-04) · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (o pacote nem está instalado; `app/` só foi consultado para nomes de classe e por `grep` de `SoftDeletes`).
> Revisão adversarial: **duas rodadas feitas** pelo `fw-adversario-ct` (contexto novo, sem o plano) — rodada 1 com 21 achados (ADV-01…ADV-21) e rodada 2, a última pelo teto de 2, com 3 reaberturas (ADV-03, ADV-06, ADV-07) e 10 achados novos (ADV-22…ADV-31). Fechamento em `## Revisão Adversarial`. Não há rodada 3.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Autorização do dock e dos favoritos (render, actions, painel, organização, contexto) | 3 | 3 | 9 | completo |
| Fronteira de entrada dos favoritos (slideover, página atual, teto) | 3 | 3 | 9 | completo |
| Settings do dock (painéis, layout, estilo, eficiência) | 2 | 2 | 4 | padrão |
| Inventário da permissão | 1 | 3 | 3 | mínimo |
| Análise do pacote (RQ-01) | 1 | 1 | 1 | mínimo — sem cenário (entregável documental) |

- Técnicas aplicadas: tabela de decisão (painel × permissão), matriz papel × ação, EP com partições inválidas isoladas, BVA 3-valores (teto de 10, incremento 1 favorito), tabela estado × operação do favorito com 2-switch, EP exaustiva do enum de layout, rastreio de não-efeito, contagem de consultas (tabela de settings e banco inteiro), matriz papel × permissão (semeadura), EP de ordem com 2-switch, EP de destinos homônimos, tabela de decisão da chave de outra organização no envio
- Escalada declarada: R4 (área padrão) usa EP **exaustiva** do enum de layout — amostrar dois layouts deixa passar o layout que falta nas opções (regra "estado exibido")
- Estouro declarado do teto de mutantes (completo: 6): R1 e R8 têm 7, R6 tem 8 e R12 tem 7. Cada uma junta duas faces que o requisito amarra na mesma cláusula — R1 render × action (RQ-13), R6 gravação × uso (RQ-11, RQ-29), R8 exibição × URL de destino (RQ-15), R12 borda × forma da recusa (RQ-18, RQ-26); desdobrar renumeraria a rastreabilidade sem mudar cenário. Os mutantes acrescentados na re-derivação (M73–M75 em R12, M102 em R6) vêm das cláusulas novas do Adendo 3, não de enchimento
- Estouro declarado do teto de cenários do perfil mínimo (1 por regra): R17 tem 2 — a semeadura difere por suíte (`admin_app` só existe com tenancy), e uma suíte não alcança o papel da outra
- Re-derivação de 2026-10-13 (Adendos 2 e 3): CT-38…CT-50 novos; oráculo ajustado, sem renumerar, em CT-15, CT-16, CT-17, CT-28, CT-33, CT-34; nenhum CT ficou obsoleto
- Rodada final de 2026-10-13 (Adendo 4 + revisão adversarial): CT-51…CT-60 novos; oráculo ajustado, sem renumerar, em CT-01, CT-02, CT-08, CT-22, CT-25, CT-27, CT-29, CT-33, CT-42, CT-45, CT-46, CT-47, CT-49; nenhum CT ficou obsoleto
- Fechamento da rodada 2 e Adendo 5 (RQ-35 substitui RQ-34): CT-61…CT-65 novos; oráculo ajustado, sem renumerar, em CT-25, CT-36, CT-47, CT-52, CT-55, CT-56, CT-57; costura de CT-59 trocada para G2; nenhum CT ficou obsoleto
- Achados do step 9 (revisão do diff, 2026-10-14): CT-66…CT-75 novos em R23…R28, com origem em RQ (RD-01, CR-01, CR-05/RD-02) e nas premissas P-01 (substituída por P-04), P-02, P-03; nenhum CT existente mudou de oráculo. A R27 deriva a direção da P-02 pela ➡️ da Q28 (aberta), por instrução da sessão — divergência declarada com a skill (princípio 5)
- Estouros trazidos pela revisão adversarial (não contam para o teto — passo 6, item 1; marcados `— revisão adversarial ADV-nn` na tabela): R1, R3, R9, R13, R14, R16, R17, R19, R22 (cenários e mutantes). R19 tem 6 cenários no perfil completo (teto 5) porque CT-53 e CT-54 são ADV-04 e ADV-05; R14 tem 2 cenários no perfil mínimo pelo mesmo motivo (CT-59 é ADV-12)
- Cenários: 75 · Regras: 28 · Mutantes previstos: 149 · Sem matador: 2
<!-- contagem pelos quatro grep -c de references/template-04.md §Contagem do cabeçalho; recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | pacote de terceiro + assets JS; plugin registrado nos 3 painéis; permissão nova (`Usar:Dock`) + seeder; 3 propriedades novas no settings da instalação + migration de settings; 2 colunas novas em `users` (favoritos, layout próprio) | CT-06, CT-08, CT-19, CT-37, CT-B01 |
| F | decidir se o dock renderiza; montar o catálogo (com rótulo por painel/organização); gravar e ordenar favoritos pelo slideover; adicionar a página atual; recusar o 11º com aviso; preservar o oculto; resolver layout/estilo; autorizar favorito de outro painel/organização; tela de configurações; semeadura da permissão | CT-01…CT-34, CT-38…CT-75 |
| D | chave de favorito válida / fora do catálogo / forjada / de outra organização / de organização excluída; 0, 1, 9, 10, 11 favoritos (visíveis + ocultos); destino repetido; ordem escolhida; favoritos de outro usuário; layout nulo × gravado × inválido; lista de painéis vazia × com id inexistente | CT-07, CT-12, CT-13, CT-14, CT-17, CT-18, CT-22, CT-27, CT-28, CT-33, CT-34, CT-40, CT-45, CT-46, CT-47, CT-48, CT-52, CT-54, CT-61, CT-62, CT-63, CT-64 |
| I | endpoint Livewire do componente do vendor (`mountAction`/`callMountedAction`, props `#[Locked]`); formulário da tela de configurações; HTML do painel por HTTP; atribuição em massa no model `User`; JavaScript do dock no navegador | CT-02, CT-05, CT-09, CT-19, CT-32, CT-56, CT-59, CT-65, CT-69, CT-70, CT-B01 |
| P | SQLite `:memory:` (sem concorrência real); cache de permissões do spatie (store `array` — o que deixa o cache em store de banco sem cenário, M130); `Panel::boot()` só por middleware (`noPainelBootado`; `/app` boota por GET real — `.ai/rules/testes.md`); assets do pacote publicados por `filament:assets`; tema claro/escuro aplicado pelo navegador (P-05); `permission.teams` só na suíte Tenancy | CT-04, CT-B01, CT-B02; concorrência: lacuna declarada |
| O | personas `panel_user`, `admin`, `infra`, `master_global`, `admin_app` (só Tenancy); um usuário em vários painéis e várias organizações; usuário que perde e recupera papel, vínculo ou permissão | CT-01, CT-20, CT-22, CT-24, CT-27, CT-29, CT-41, CT-42 |
| T | permissão revogada e restaurada (2-switch); organização excluída depois do favorito gravado; avaliação a cada request (sem memória entre requests); concorrência de duas adições no 9º favorito | CT-25, CT-27 (2-switch com renderização intermediária observada), CT-29, CT-69 (re-render depois de gravar), CT-75 (corrida entre abas); concorrência: lacuna declarada |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o dock só renderiza e só executa action para quem tem `Usar:Dock`, no painel ligado | autorização (completo) | RQ-13, RQ-14 | tabela de decisão + matriz papel × ação + EP da superfície Livewire | CT-01…CT-05 |
| R2 — instalação nova nasce sem painel; lista vazia = nenhum painel; o valor gravado no settings governa | settings (padrão) | RQ-14 | EP + tabela de decisão (lista × painel) | CT-06, CT-07 |
| R3 — a tela de configurações grava painéis, layout e estilo e recusa valor fora do domínio | settings (padrão) | RQ-14, RQ-17, RQ-08 | EP (inválidas isoladas) + EP exaustiva dos enums | CT-08, CT-09, CT-57 |
| R4 — layout e estilo vêm do settings; o usuário sobrescreve só o layout | settings (padrão, técnica escalada) | RQ-08, RQ-17 | tabela de decisão + EP exaustiva do enum | CT-10, CT-11, CT-12 |
| R5 — favoritos são de cada usuário, e o dock existe com 0 a 10 deles | fronteira (completo) | RQ-10, RQ-02, RQ-20 | IDOR por usuário + cardinalidade 0/1/N | CT-13, CT-14 |
| R6 — o slideover grava só destinos do catálogo; envio com chave fora dele é recusado por inteiro | fronteira (completo) | RQ-11, RQ-20, RQ-29 | EP (cada partição inválida isolada) + criação ≠ edição ≠ uso | CT-15…CT-19 |
| R7 — o catálogo é gerado das telas que o usuário pode abrir | autorização (completo) | RQ-12, RQ-11, RQ-04 | matriz papel × tela | CT-20, CT-21, CT-22 |
| R8 — favorito de outro painel aparece em todo painel com dock ligado, só enquanto o usuário acessa painel e tela; some sem ser apagado e volta | autorização (completo) | RQ-15, RQ-19, RQ-04 | tabela estado × operação + 2-switch | CT-23, CT-24, CT-25 |
| R9 — favorito do painel de organização guarda a organização, abre nela e some sem o acesso | autorização (completo) | RQ-16, RQ-19, RQ-21 | tabela de decisão + 2-switch + IDOR por organização | CT-26, CT-27, CT-28, CT-51, CT-58 |
| R10 — avaliar favoritos não altera painel, organização nem permissões do request | autorização (completo) | RQ-15, RQ-16, RQ-19 | EP do tipo de favorito × contexto observado | CT-29 |
| R11 — a opção do dock adiciona a página atual, se ela for destino do catálogo | fronteira (completo) | RQ-21, RQ-11, RQ-23 | EP + superfície Livewire | CT-30, CT-31, CT-32 |
| R12 — no máximo 10 favoritos por usuário; o 11º é recusado com aviso e nada muda | fronteira (completo) | RQ-18, RQ-26 | BVA 3-valores (9, 10, 11), valor literal do requisito + rastreio de não-efeito | CT-33, CT-34 |
| R13 — decidir se o dock aparece e como aparece não acrescenta consulta ao banco | settings (padrão) | RQ-22 | contagem de consultas (ligado × desligado × sem o plugin; settings e total; mesmo aquecimento) | CT-35, CT-36, CT-56, CT-65 |
| R14 — a permissão do dock existe e é concedível na tela de papéis | inventário (mínimo) | RQ-13 | EP + efeito da concessão | CT-37, CT-59 |
| R15 — a opção "adicionar esta página" só aparece em destino do catálogo que ainda não é favorito | fronteira (completo) | RQ-23, RQ-28 | EP (tipo de página) + autorização na ação | CT-38, CT-39 |
| R16 — o mesmo destino nunca fica gravado duas vezes; o mesmo Resource em outra organização é outro destino | fronteira (completo) | RQ-28, RQ-16 | EP (entrada: página atual × slideover; organização igual × diferente) + idempotência no agregado | CT-40, CT-63, CT-64 |
| R17 — a instalação nova concede o dock aos papéis de administração dos três painéis e não ao `panel_user` | inventário (mínimo) | RQ-24 | matriz papel × permissão | CT-41, CT-42 |
| R18 — o catálogo do `/app` é só da organização aberta e só dentro do `/app`; favoritos de outras organizações continuam | autorização (completo) | RQ-25, RQ-12, RQ-16, RQ-32 | tabela de decisão (onde abre × organização da tela) + estado × operação | CT-43, CT-44 |
| R19 — o favorito oculto fica fora do slideover, é preservado ao salvar e conta no teto; chave que nunca foi destino é descartada ao salvar e, enquanto gravada, ocupa vaga | fronteira (completo) | RQ-27, RQ-32, RQ-19, RQ-18, RQ-15 | tabela estado × operação + BVA com oculto e com inválida | CT-45, CT-46, CT-47, CT-53, CT-54, CT-60 |
| R20 — a ordem dos atalhos é a escolhida pelo usuário; a página adicionada entra no fim | fronteira (completo) | RQ-30, RQ-33, RQ-20 | EP (ordem ≠ alfabética ≠ catálogo ≠ painel) + 2-switch (grava → reordena) | CT-48, CT-49 |
| R21 — o rótulo de cada destino no slideover traz o painel (e, no `/app`, a organização) antes do grupo e da tela | autorização (completo) | RQ-31 | EP (destinos homônimos em painéis/organizações diferentes) | CT-50 |
| R22 — em qualquer slideover, todo favorito gravado que o usuário ainda abre aparece entre os escolhidos, com rótulo de painel (e organização), e pode ser removido ou reordenado; só as novas opções variam por onde o slideover abre; o envio aceita esses favoritos e o catálogo corrente, e recusa por inteiro qualquer outra chave | autorização (completo) | RQ-35, RQ-25, RQ-29, RQ-27 | tabela de decisão (onde abre × chave no envio: já gravada e abrível / não gravada fora do catálogo corrente / gravada sem acesso) | CT-52, CT-55, CT-61, CT-62 |
| R23 — Page de outro painel sem a permissão dela não vale como favorito, mesmo com o mapa de Pages do painel corrente já montado no request | autorização (completo) | RQ-04, RQ-15 (RD-01) | matriz papel × tela com estado do framework fixado + controle positivo | CT-66, CT-67, CT-68 |
| R24 — depois de salvar no slideover, o dock da mesma requisição já mostra os favoritos novos, na ordem nova | fronteira (completo) | RQ-10, RQ-30 (CR-01) | 2-switch no mesmo componente | CT-69 |
| R25 — payload forjado nos métodos públicos tipados do componente não estoura e não grava | autorização (completo) | RQ-13 (CR-05, RD-02) | EP de tipo errado | CT-70 |
| R26 — favorito do painel e da organização abertas não recarrega papéis nem busca a organização; admite só a consulta da autorização da tela | settings (padrão) | P-04 (CR-02; substitui a premissa anterior) | contagem 0 × 3 com atribuição pela pilha + limite superior, mesmo aquecimento | CT-71, CT-72 |
| R27 — favorito de organização excluída não aparece no slideover e sai ao salvar | fronteira (completo) | P-02 (CR-03), RQ-27 | estado × operação + BVA do teto | CT-73, CT-74 |
| R28 — "adicionar esta página" que não pode mais ser feito avisa e não muda nada | fronteira (completo) | P-03 (RD-03), RQ-21, RQ-28 | EP do motivo + rastreio de não-efeito | CT-75 |
| R29 — o dock acompanha o tema escuro do painel (cor computada da superfície e do item) | UI com JS (completo) | P-05 | EP do tema efetivo + controle positivo (no `05`) | CT-B02 |

- RQ-01 — sem cenário: entregável documental (análise do pacote), sem comportamento de sistema a falsificar; conferência pelo quality gate
- RQ-03, RQ-05, RQ-06, RQ-07, RQ-09 — substituídas no Adendo 1 (por RQ-10, RQ-13, RQ-14, RQ-15, RQ-16/RQ-17); fora da cobrança
- RQ-12 — coberta: CT-20, CT-21, CT-22, CT-43 (o recorte do `/app` antes bloqueado em Q13 é RQ-25)
- RQ-13 — coberta: CT-01…CT-05, CT-37, CT-59; a semeadura antes bloqueada em Q12 é RQ-24 (CT-41, CT-42). Os demais cenários continuam concedendo `Usar:Dock` no arranjo, para que a única variável seja a permissão
- RQ-16 — coberta: CT-26…CT-28, CT-44, CT-51, CT-58, CT-63, CT-64
- RQ-18 — parcialmente substituída por RQ-26 (Adendo 3); coberta: CT-33, CT-34, CT-47
- RQ-19 — parcialmente substituída por RQ-27 (Adendo 3); coberta: CT-24, CT-25, CT-27, CT-45, CT-58, CT-60
- RQ-21 — coberta: CT-30, CT-31, CT-32; o recorte de RQ-23 em CT-38, CT-39 e o de RQ-28 em CT-38, CT-40
- RQ-23, RQ-24, RQ-26, RQ-28, RQ-29, RQ-31 — cobertas (R15, R17, R12, R16, R6, R21)
- RQ-25 — coberta: CT-43, CT-44, CT-52, CT-55 (o recorte antes aberto em Q23 foi decidido em RQ-34, hoje substituída por RQ-35)
- RQ-27 — coberta: CT-45, CT-46, CT-47, CT-53, CT-54, CT-60, CT-61, CT-62 (o recorte antes aberto em Q21 é RQ-32)
- RQ-30 — coberta: CT-48, CT-49 (o recorte antes aberto em Q22 é RQ-33)
- RQ-32 — coberta: CT-47 (linhas "inválida"), CT-44 (linha "teto")
- RQ-33 — coberta: CT-33, CT-49
- RQ-34 — substituída por RQ-35 (Adendo 5); fora da cobrança
- RQ-35 — coberta: CT-52, CT-55, CT-61, CT-62
- RQ-04, RQ-15 — também em CT-66, CT-67, CT-68 (RD-01: Page de outro painel com o mapa de Pages do painel corrente montado)
- RQ-10, RQ-30 — também em CT-69 (CR-01: re-render na mesma requisição)
- RQ-13 — também em CT-70 (CR-05/RD-02: métodos públicos tipados)
- P-01 — substituída por P-04 (2026-10-14); fora da cobrança
- P-04 — coberta: CT-71, CT-72
- P-02 — cobertura: CT-73 (invariante das duas leituras da Q28); CT-74 (direção "descartar", **derivada pela ➡️ da Q28 aberta**, por instrução da sessão — vira `@obsoleto` se a resposta for "manter")
- P-03 — coberta: CT-75

## Matriz estado × operação do favorito

Estados (derivados no uso, não gravados): **visível** (destino que o usuário abre), **oculto** (destino válido que ele deixou de poder abrir — RQ-19, RQ-27), **inválido** (chave gravada que nunca foi destino do catálogo — RQ-11, RQ-27 — ou favorito de organização excluída — P-02), **de outra organização** (destino do `/app` que ele abre, gravado em organização diferente da aberta, ou visto de outro painel — RQ-25, RQ-35). O favorito de organização à qual ele **perdeu** o acesso é estado **oculto**. Operações: exibir no dock · regravar o slideover sem tocá-lo · remover pelo slideover · contar no teto.
**4 estados × 4 operações = 16 células** — 11 válidas (✅), 5 inválidas (❌), 0 lacunas.
Legenda (auditada célula a célula):
- ✅ = a operação produz o efeito que o requisito manda, e o `Então` afirma o valor gravado/exibido
- ❌ em "exibir" = **nenhum link para o destino no HTML do dock**, e a chave continua gravada (oculto) / nenhum link e nenhum erro (inválido) — "exibir" não dispara outro efeito
- ❌ em "remover pelo slideover" (oculto) = **o destino não é opção do slideover** e o envio sem ele não o apaga — remover não está disponível (RQ-27)
- ❌ em "regravar"/"remover" (inválido) = **a chave é descartada da gravação**, e os demais favoritos ficam como enviados (RQ-27)

| Estado \ Operação | exibir | regravar sem tocá-lo | remover pelo slideover | contar no teto |
|---|---|---|---|---|
| visível | ✅ CT-15, CT-23 | ✅ CT-16 (linha "mesma lista") | ✅ CT-16 (linha "troca"), CT-53 (de outro painel) | ✅ CT-33, CT-34 |
| oculto | ❌ CT-24, CT-27, CT-58 | ✅ preservado — CT-60, CT-62 (organização sem acesso) | ❌ não é opção nem escolhido — CT-45, CT-61 | ✅ conta — CT-47 |
| inválido | ❌ CT-18 | ❌ descartado — CT-46 (linha "mesma lista"), CT-74 (organização excluída) | ❌ descartado — CT-46 (linha "troca"), CT-73 (não é opção nem escolhido) | ✅ conta — CT-47 (linhas "inválida", RQ-32) |
| de outra organização | ✅ CT-26 | ✅ preservado — CT-44 (linha "salva sem tocar"), CT-52 (linha "/admin, sem tocar") | ✅ removível — CT-52 (linhas "remover" no /app/acme e no /admin, RQ-35) | ✅ conta — CT-44 (linha "teto") |

Personas da matriz: `ana` (só `/app`), `bruno` (`/admin` + `/app`) e, na linha "de outra organização", o `usuario` de `duasOrganizacoes()` — o destino oculto é de painel diferente do painel em que o dock é visto (CT-24), e o de outra organização é visto de dentro da organização aberta (CT-44).

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Componente do dock (Kit) | R1, R4, R5, R6, R7, R8, R11, R12, R15, R16, R19, R20, R23, R24, R25, R28 | componente Livewire/Filament | nova — `tests/Kit/DockDeAtalhosTest.php`; nenhum teste cobre o componente do vendor (padrão de arranjo herdado de `tests/Kit/DashboardDinamicoTest.php`) | o observável é o HTML do componente e o efeito das actions do vendor (`callAction`/`mountAction`) — é o próprio endpoint que o cliente alcança | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G2 — Fronteira HTTP do dock (Kit) | R1, R2, R6, R8, R13, R14, R23, R26 | Pest feature HTTP | nova — mesmo arquivo de G1 | gate de camada da regra: render e autorização provados pelo HTML da página real; settings → config pelo boot; contagem de consultas por request; atribuição em massa no model | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G3 — Tela de configurações | R3 | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` (padrão "grava as quatro famílias" e "recusa campo fora do domínio") | gate de tela de escrita: gravação por `fillForm` → `call('save')` | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G4 — Organização (componente) | R1, R7, R9, R10, R16, R18, R21, R22, R27 | componente Livewire/Filament | nova — `tests/Tenancy/DockDeAtalhosTenancyTest.php`; `permission.teams` só existe na suíte Tenancy | papéis por organização (`team_id`) e `Filament::setTenant()` só são observáveis com `TenancyTestCase` | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G5 — Organização (HTTP) | R9, R26 | Pest feature HTTP | nova — mesmo arquivo de G4 | perda de vínculo/organização provada pelo HTML de `/app/{slug}` real, por fora do componente | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G6 — Inventário da permissão | R14, R17 | Pest feature HTTP | existente — `tests/Kit/PermissoesDeAcoesTest.php` (CT-15/CT-16 daquele arquivo: permissão nova no banco e na tela de papéis) | o observável é registro no banco e opção na tela de papéis | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G7 — Dock no navegador | — (CT-B01, RQ-02/RQ-13; CT-B02, P-05) | browser | nova — `tests/Browser/DockDeAtalhosTest.php` | o dock abre por JavaScript do pacote (Alpine + assets publicados por `filament:assets`), e o tema é aplicado pelo Alpine do Filament; nenhum teste de componente prova que ele abre, navega ou pinta as cores do tema | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |
| G8 — Inventário da permissão (Tenancy) | R17 | Pest feature HTTP | existente — `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | `admin_app` só é semeado com tenancy ligada (`.ai/rules/testes.md` §"Nem todo papel do kit existe em toda suíte") | sessão, 2026-10-13 — convenção do projeto (`.ai/rules/testes.md`) |

**Precedência (Project Rule vence a skill)**: a costura `Pest feature HTTP` vive em `tests/Kit` e `tests/Tenancy`, não em `tests/Feature` — `tests/Pest.php` e `.ai/rules/testes.md` reservam `tests/Feature` ao projeto do usuário e põem os testes do kit nessas pastas (Tenancy exige `TenancyTestCase`).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `DockDeAtalhos`, `PluginDoDock`, nomes das actions (`gerenciarAtalhos`, `adicionarPaginaAtual`, `atalho_{n}`) | escolha de implementação | detalhe do cenário (nome usado no `callAction`) |
| formato da chave `{painel}\|{FQCN}\|{organização}` | escolha de implementação | detalhe dos `Exemplos` (partições do domínio inválido) |
| colunas `dock_atalhos`/`dock_layout`, propriedades `dock_paineis`/`dock_layout`/`dock_estilo`, bloco `kit.dock` | escolha de implementação | detalhe do `Dado`/`Então` (onde se lê o valor gravado) |
| "corta em 10" na gravação do slideover (01 e linha `gerenciarAtalhos` da Superfície Livewire do 02) | **contradiz RQ-26** (recusa com aviso, nada muda) — achado para a sessão reconciliar o plano | oráculo de RQ-26: CT-33, CT-34 (M73 é o comportamento do plano) |
| "mantém só chave do catálogo **ou** já gravada e ainda autorizada" | **contradiz RQ-27** (o oculto é preservado ao salvar) — achado para a sessão reconciliar o plano | oráculo de RQ-27: CT-60 (M91 é o comportamento do plano) |
| "dedup" | agora determinado por RQ-28; o mecanismo (dedup × recusa do envio) não é cláusula | CT-40 afirma só "exatamente uma vez" |
| envio com chave inválida grava as válidas (`gravar()` filtra e grava o resto) | **contradiz RQ-29** (recusa o envio inteiro) — achado para a sessão reconciliar o plano | oráculo de RQ-29: CT-17 (M102 é o comportamento do plano) |
| "escolher/ordenar" e "ordem gravada" no dock | agora determinado por RQ-30 e RQ-33 (adicionada entra no fim) | CT-33, CT-48, CT-49 |
| rótulo "Painel — Grupo — Tela" nas opções | a **ordem** painel (organização) → grupo → tela vem de RQ-31; o separador "—" é só do plano | CT-50 afirma presença e ordem dos segmentos, não o separador |
| layout inválido "vira `null` (padrão da instalação)" | o requisito só fixa o invariante (layout fora do pacote nunca vale) | CT-12 afirma o invariante: o dock usa o padrão do settings |
| `DockPosition::BottomCenter`, atalho `mod+.` | posição está em Fora de Escopo; atalho de teclado sem RQ | sem cenário |
| `->authorize('Usar:Dock')` nas actions | o oráculo vem de RQ-13 + taxonomia (autorização na ação), não do PRD | CT-03 |
| papéis que recebem `Usar:Dock` no seeder; subtração do `panel_user` | agora determinado por RQ-24; o mapa de painéis da permissão no seeder é do plano | CT-41, CT-42 afirmam papel × tem/não tem |
| catálogo só da organização aberta (D7) | agora determinado por RQ-25 (novas opções) e RQ-35 (escolhidos em qualquer slideover) | CT-43, CT-44, CT-52, CT-55, CT-61 |
| "`gravar()` aceita chave do catálogo **ou** favorito já gravado ainda autorizado" | com RQ-35 o plano coincide no aceite do favorito já gravado e ainda abrível; diverge em dois pontos: a chave inválida já gravada recusa o envio (RQ-29) e o favorito gravado sem acesso é preservado (RQ-27), não descartado | CT-52, CT-54, CT-62 (M121 e M127 são as leituras do plano) |
| linhas de log do channel `dock-de-acoes-rapidas` | log não é cláusula (Proibição 12) | dimensão D do quality gate |
| notificação de sucesso de `adicionarPaginaAtual` | o requisito só pede aviso na **recusa** (RQ-26) | sem cenário |
| âncoras de inventário (`KitInfoTest` 63 → 66, `inventarioDeAutorizacao()`) | manutenção de teste existente, não comportamento do requisito | fica no `01` |

**Perguntas geradas pela derivação** — devolvidas como saída (após o `>>>04`).

## Setup Global

### Personas
- `ana` — `usuarioDoKit('panel_user', 'ana@example.com')`; `Usar:Dock` concedida **no arranjo**: `papelDoKit('panel_user')->givePermissionTo('Usar:Dock')` + `forgetCachedPermissions()` — independe da semeadura (RQ-24 tem cenário próprio: CT-41, CT-42)
- `ana sem o dock` — o MESMO papel com `semAPermissao('panel_user', 'Usar:Dock')` depois da concessão: a única variável é a permissão (`.ai/rules` via docblock de `semAPermissao()`)
- `bruno` — `admin` + `panel_user` (+ `infra` onde o catálogo precisa de ≥ 11 destinos: R12); `Usar:Dock` concedida aos papéis no arranjo — exceto na linha de CT-02 que usa a semeadura real (RQ-24: `admin` tem, `panel_user` não)
- `master` — `usuarioDoKit('master_global', 'master@example.com')` — linha de controle (passa por `Gate::before`)
- Tenancy: `duasOrganizacoes()` (Acme, Globex, `usuario` com `panel_user` nas duas + `admin` global) e `dani` — `panel_user` na Acme e `admin_app` na Globex (`papelNaOrganizacao()`), para R10
- **Fato da pilha (suíte Tenancy, `permission.teams`)**: dentro de `/app/{org}` o `team_id` é o da organização e os papéis do contexto global **não** valem ali; no `/admin` e no `/infra` valem os papéis globais. Na suíte Kit (sem teams) os papéis do usuário se somam

### Fixtures
- Seeds: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach` (padrão de `DashboardDinamicoTest`)
- Favoritos de partida: gravados direto no usuário (`forceFill([...])->save()`). Legítimo aqui: o favorito não tem máquina de estados — oculto/visível é derivado no uso. Os cenários de **gravação** passam pelas actions
- Dock ligado por painel: `config()->set('kit.dock.paineis', [...])` nos cenários de render; **CT-07** passa por `gravarConfiguracao()` + `alinharConfiguracoesDoKit()` (o caminho real settings → config). Se Kit e Tenancy usarem um helper `ligarDock()`, ele mora em `tests/Pest.php` (`.ai/rules/testes.md`)
- Painel corrente: `noPainelBootado('admin'|'infra')`; `/app` boota por GET real antes do primeiro `Livewire::test()` (`.ai/rules/testes.md`); organização: `noPainelDa($tenant)`; mais de um painel no mesmo caso: `noPainelDoShield()`
- Página corrente do dock: `DockContext::current()->set($routeName, $parameters, $path)`
- Componente: `Livewire::test(\HoceineEl\QuickActionDock\Livewire\QuickActionDock::class)`
- **Observável de "o dock renderiza"**: o `href` do favorito (URL de `{Destino}::getUrl(panel: …, tenant: …)`) no HTML do componente. Nunca texto de layout
- **Observável de layout/estilo**: depois do `composer require`, o executor confirma no vendor a marca que o HTML do dock carrega por layout e por estilo (classe ou atributo) e cita `arquivo:símbolo:linha`; o oráculo é essa marca, nunca a presença do dock

### Fakes
- Aviso de "não dá para adicionar" (P-03): `assertNotified()` com a notificação de status de aviso — o texto não é cláusula
- Aviso do limite (RQ-26): `assertNotified()`, com o texto da notificação contendo "10" — o texto exato não é cláusula
- Nenhum fake de e-mail, fila ou job: o requisito não pede esses efeitos (o aviso de RQ-26 é notificação de tela, conferida acima). Contagem de consultas: `DB::listen()` com `debug_backtrace()` (R13 — `@premissa` de mecanismo)

### Estratégia de DB
- `RefreshDatabase` global das suítes Kit/Tenancy/Browser (`tests/Pest.php`)

---

## Regra R1 — o dock só renderiza e só executa action para quem tem `Usar:Dock`, no painel ligado

> `RQ-13`, `RQ-14` · perfil **completo** · técnica: **tabela de decisão** (painel ligado × permissão; colapsada só em "desligado × sem permissão", onde a ação não depende da permissão) + **matriz papel × ação** (cada action) + EP da superfície Livewire

```gherkin
# language: pt
Funcionalidade: Dock de atalhos

  Regra: só quem tem a permissão do dock o vê e o usa, e só no painel em que ele está ligado

    Esquema do Cenário: [CT-01] o dock renderiza só com a permissão e o painel ligado
      Dado a pessoa <persona> com um favorito para a lista de usuários do /admin
      E o dock <estado> no painel /admin
      Quando o componente do dock é renderizado no painel /admin
      Então o HTML do dock <resultado> o link para a URL da lista de usuários do /admin
      E a action de gerenciar os atalhos <gerenciar>

      Exemplos:
        | persona                         | estado    | resultado   | gerenciar               | # célula                   |
        | bruno, com Usar:Dock            | ligado    | contém      | existe e está visível   | ligado × com permissão     |
        | bruno, mesmo papel sem Usar:Dock| ligado    | não contém  | não está disponível     | ligado × sem permissão     |
        | bruno, com Usar:Dock            | desligado | não contém  | não está disponível     | desligado × com permissão  |
        | master_global (controle)        | ligado    | contém      | existe e está visível   | permissão por Gate::before |

    Esquema do Cenário: [CT-02] a página do painel entrega o dock a quem tem a permissão e não a quem não tem
      Dado o dock <estado> no /app
      E <persona>, com um favorito para uma tela do /app que ela abre
      Quando ela abre a página inicial do /app por HTTP
      Então o HTML da resposta <resultado> o componente do dock, o gatilho que o abre e o link do favorito

      Exemplos:
        | persona                                                                 | estado    | resultado  | # célula                           |
        | ana (panel_user), com Usar:Dock                                         | ligado    | contém     | ligado × com permissão             |
        | ana sem o dock (mesmo papel)                                            | ligado    | não contém | ligado × sem permissão             |
        | ana (panel_user), com Usar:Dock                                         | desligado | não contém | desligado: o componente não monta  |
        | bruno (admin + panel_user), Usar:Dock só no admin pela semeadura (RQ-24), panel_user sem ela | ligado | contém | papéis se somam (suíte Kit, sem teams) |

    Esquema do Cenário: [CT-03] sem a permissão, a chamada direta de action do dock não grava nada
      Dado ana sem o dock, com o favorito X gravado, no /app com o dock ligado
      E a página atual é a lista de um Resource do catálogo dela, diferente de X
      Quando o cliente chama a action <action> do componente do dock com <dados>
      Então os favoritos gravados de ana continuam exatamente [X]

      Exemplos:
        | action               | dados                                  |
        | gerenciarAtalhos     | atalhos = [X, a lista da página atual] |
        | adicionarPaginaAtual | —                                      |

    Esquema do Cenário: [CT-04] a permissão vale na organização em que foi concedida
      Dado o dock ligado no /app
      E dani com Usar:Dock pelo papel na Acme e sem ela pelo papel na Globex, com um favorito de cada organização
      Quando o componente do dock é renderizado em <organizacao>
      Então o HTML do dock <resultado> o link do favorito de <organizacao>

      Exemplos:
        | organizacao | resultado  | # célula                       |
        | Globex      | não contém | papel da organização sem a permissão |
        | Acme        | contém     | controle positivo              |

    Esquema do Cenário: [CT-05] action fora da lista do dock não executa nada
      Dado bruno, com Usar:Dock e o favorito X gravado, no /admin com o dock ligado
      Quando o cliente chama mountAction do componente com <nome>
      Então os favoritos gravados de bruno continuam exatamente [X]
      E o componente responde sem exceção

      Exemplos:
        | nome                          | # partição               |
        | "apagarTudo"                  | nome fora da lista       |
        | "atalho_99"                   | índice de atalho inexistente |
        | ["gerenciarAtalhos", "x"]     | tipo errado (array)      |
```

Nota CT-04: a linha da Acme é o **controle positivo** — sem ela, "não contém" na Globex passa com o dock quebrado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | permissão conferida só na renderização; as actions executam sem `authorize` | CT-03 | linha `gerenciarAtalhos`: favoritos = [X]; o mutante grava [X, lista] |
| M2 | `authorize` só em `gerenciarAtalhos`, esquecido em `adicionarPaginaAtual` (verbo irmão) | CT-03 | linha `adicionarPaginaAtual`: favoritos = [X]; o mutante grava a página atual |
| M3 | `\|\|` no lugar de `&&` entre painel ligado e permissão | CT-01 | linhas "ligado × sem permissão" e "desligado × com permissão": "não contém"; o mutante renderiza |
| M4 | permissão conferida com `hasPermissionTo()` direto, sem passar pelo Gate (ignora `Gate::before`) | CT-01 | linha master_global: "contém"; o mutante não renderiza |
| M5 | permissão avaliada no contexto global de papéis, não no da organização aberta | CT-04 | linha Globex: "não contém"; o mutante usa o papel global/da Acme e renderiza |
| M6 | `mountAction` com nome desconhecido ou de tipo errado estoura (índice/`TypeError`) ou cai num handler genérico que grava | CT-05 | "responde sem exceção" e favoritos = [X]; o mutante lança ou altera |
| M7 | dock escondido só por CSS/condição no Blade da página, componente montado para quem não tem a permissão | CT-02 | resposta de ana sem o dock: componente ausente no HTML; o mutante entrega o componente oculto |
| M104 | painel desligado só esvazia os atalhos; componente, gatilho e gerenciar continuam montados — revisão adversarial ADV-01 | CT-01, CT-02 | CT-01 linha desligado: gerenciar não disponível; CT-02 linha desligado: componente e gatilho ausentes; o mutante os entrega |
| M105 | permissão conferida só no papel do painel corrente (`panel_user` no /app), ignorando a soma de papéis da suíte sem teams — revisão adversarial ADV-20 | CT-02 | linha bruno: "contém"; o mutante não renderiza |

---

## Regra R2 — instalação nova nasce sem painel; lista vazia = nenhum painel; o valor gravado no settings governa

> `RQ-14` · perfil **padrão** · técnica: **EP** (lista vazia, um painel, dois painéis, id inexistente) + **tabela de decisão** (lista × painel)

```gherkin
  Regra: o dock só aparece nos painéis ligados no settings, e nenhum nasce ligado

    Cenário: [CT-06] a configuração de fábrica não liga o dock em painel nenhum
      Dado o ambiente sem a chave de painéis do dock e o settings recém-semeado pela migration
      Quando a configuração efetiva do dock é lida
      Então a lista de painéis do dock lida da config é []
      E a lista gravada no settings é []

    Esquema do Cenário: [CT-07] a lista gravada no settings decide em que painel o dock renderiza
      Dado o settings da instalação gravado com os painéis do dock <lista>, aplicado à config como no boot
      E bruno, com Usar:Dock, admin e infra, e um favorito para uma tela que ele abre
      Quando o componente do dock é renderizado no painel <painel>
      Então o HTML do dock <resultado> o link do favorito

      Exemplos:
        | lista              | painel | resultado  | # partição                     |
        | []                 | admin  | não contém | vazia = nenhum                 |
        | []                 | app    | não contém | vazia = nenhum                 |
        | []                 | infra  | não contém | vazia = nenhum                 |
        | ["app"]            | app    | contém     | um painel, dentro              |
        | ["app"]            | admin  | não contém | um painel, fora                |
        | ["admin", "infra"] | infra  | contém     | dois painéis, o segundo        |
        | ["admin", "infra"] | app    | não contém | dois painéis, fora             |
        | ["financeiro"]     | app    | não contém | id inexistente não liga outro  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M8 | lista vazia = todos os painéis (a semântica do dashboard dinâmico, vizinha) | CT-07 | linhas `[]`: "não contém"; o mutante renderiza nos três |
| M9 | migration de settings semeia os três painéis, ou o default da config é a lista completa | CT-06 | lista gravada = [] e lista da config = []; o mutante devolve ["admin","app","infra"] |
| M10 | propriedade do settings fora do mapa settings → config (gravado não muda nada) | CT-07 | linha `["app"]`/`app`: "contém"; o mutante lê a config de fábrica [] e não renderiza |
| M11 | compara só o primeiro item da lista | CT-07 | linha `["admin","infra"]`/`infra`: "contém"; o mutante não renderiza |
| M12 | id desconhecido na lista invalida a lista e cai no "todos" | CT-07 | linha `["financeiro"]`: "não contém"; o mutante renderiza |

---

## Regra R3 — a tela de configurações grava painéis, layout e estilo e recusa valor fora do domínio

> `RQ-14`, `RQ-17`, `RQ-08` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + **EP exaustiva** dos enums de layout e estilo (CT-57) + partição "nenhum painel" (CT-08)

```gherkin
  Regra: a tela de configurações grava as escolhas do dock e recusa valor fora do domínio

    Esquema do Cenário: [CT-08] o administrador grava painéis, layout e estilo padrão do dock
      Dado o administrador na tela de configurações, com os painéis do dock ["admin"] e layout/estilo de fábrica gravados
      Quando ele preenche os painéis <paineis>, um layout L1 e um estilo S1 diferentes dos de fábrica e salva
      Então a tela não mostra erro
      E o settings gravado tem os painéis <paineis>, o layout L1 e o estilo S1

      Exemplos:
        | paineis           | # partição                         |
        | ["app", "infra"]  | dois painéis                       |
        | []                | todos desmarcados (nenhum painel)  |

    Esquema do Cenário: [CT-57] todo layout e todo estilo do pacote podem ser gravados na tela de configurações
      Dado o administrador na tela de configurações, com <campo> gravado num caso do pacote diferente de <valor>
      Quando ele escolhe <campo> = <valor> e salva
      Então a tela não mostra erro
      E o settings gravado tem <campo> = <valor>

      Exemplos:
        | campo  | valor                                                                         |
        | layout | uma linha por caso do enum de layout do pacote (lista confirmada no vendor)  |
        | estilo | uma linha por caso do enum de estilo do pacote (lista confirmada no vendor)  |

    Esquema do Cenário: [CT-09] valor fora do domínio é recusado no campo e nada é gravado
      Dado o administrador na tela de configurações, com os painéis do dock ["app"] gravados
      Quando ele preenche <campo> com <valor> e salva
      Então a tela mostra erro no campo <campo>
      E o settings gravado continua com os painéis ["app"] e o layout e o estilo anteriores

      Exemplos:
        | campo          | valor             | # partição                  |
        | painéis        | ["financeiro"]    | painel inexistente          |
        | painéis        | "app"             | tipo errado (texto, não lista) |
        | layout         | "xpto"            | fora do enum do pacote      |
        | layout         | ["speed-dial"]    | tipo errado (lista)         |
        | estilo         | "xpto"            | fora do enum do pacote      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M13 | campo de painéis sem validação `in()` dos ids registrados | CT-09 | linha `["financeiro"]`: erro no campo e painéis gravados = ["app"]; o mutante grava ["financeiro"] |
| M14 | layout sem validação contra o enum | CT-09 | linha `"xpto"`: erro e layout anterior mantido; o mutante grava "xpto" |
| M15 | estilo sem validação contra o enum | CT-09 | linha estilo `"xpto"`: erro; o mutante grava |
| M16 | campo de estilo (ou de layout) fora do mapeamento de gravação do settings | CT-08 | estilo gravado = S1; o mutante mantém o de fábrica |
| M17 | lista de painéis gravada como texto/serializada errada | CT-08, CT-09 | CT-08: gravado = ["app","infra"] (lista); linha `"app"` do CT-09: erro; o mutante aceita o texto |
| M118 | opções de layout ou de estilo escritas à mão sem um caso do pacote — revisão adversarial ADV-08 | CT-57 | a linha do caso ausente: gravado = o caso; o mutante recusa a opção |
| M119 | campo de painéis obrigatório (lista vazia recusada) — revisão adversarial ADV-09 | CT-08 | linha []: sem erro e gravado []; o mutante mostra erro e mantém ["admin"] |

---

## Regra R4 — layout e estilo vêm do settings; o usuário sobrescreve só o layout

> `RQ-08`, `RQ-17` · perfil **padrão** com técnica escalada (EP exaustiva do enum de layout) · técnica: **tabela de decisão** (layout do usuário nulo × gravado) + **EP exaustiva** + EP de domínio inválido

```gherkin
  Regra: o settings define layout e estilo padrão; o usuário troca só o layout

    Esquema do Cenário: [CT-10] o dock usa o layout do usuário quando há, e sempre o estilo do settings
      Dado o settings com layout padrão L1 e estilo padrão S1, ambos diferentes dos de fábrica do pacote
      E bruno, com Usar:Dock, com layout próprio <layout_do_usuario>
      Quando o componente do dock é renderizado no /admin ligado
      Então o HTML do dock traz a marca do layout <layout_renderizado> e do estilo S1

      Exemplos:
        | layout_do_usuario | layout_renderizado | # célula                    |
        | nenhum (nulo)     | L1                 | sem preferência → settings  |
        | L2                | L2                 | preferência → sobrescreve   |

    Esquema do Cenário: [CT-11] todo layout do pacote pode ser escolhido pelo usuário no slideover
      Dado bruno, com Usar:Dock e sem layout próprio, no /admin ligado
      Quando ele grava pelo slideover do dock o layout <layout>
      Então o layout próprio gravado de bruno é <layout>
      E o componente renderizado em seguida traz a marca do layout <layout>

      Exemplos:
        | layout                                              |
        | uma linha por caso do enum de layout do pacote (lista confirmada no vendor após a instalação) |

    Esquema do Cenário: [CT-12] o slideover não aceita layout fora do pacote nem estilo do usuário
      Dado o settings com layout L1 e estilo S1, e bruno sem layout próprio, no /admin ligado
      Quando ele envia pelo slideover <campo> = <valor>
      Então o layout próprio gravado de bruno continua nulo
      E o componente renderizado em seguida traz a marca do layout L1 e do estilo S1

      Exemplos:
        | campo  | valor           | # partição                              |
        | layout | "xpto"          | fora do enum                            |
        | layout | ["speed-dial"]  | tipo errado                             |
        | estilo | S2 (válido)     | campo que o usuário não pode sobrescrever (chave extra no payload) |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | layout padrão do settings ignorado (usa o de fábrica do pacote) | CT-10 | linha "nulo": marca de L1; com L1 ≠ fábrica, o mutante traz a marca de fábrica |
| M19 | preferência do usuário gravada e não aplicada | CT-10 | linha L2: marca de L2; o mutante traz L1 |
| M20 | estilo também lido da preferência do usuário (ou aceito no slideover) | CT-12 | linha `estilo`: marca de S1; o mutante traz S2 |
| M21 | lista de opções de layout escrita à mão sem um dos casos do pacote | CT-11 | a linha do caso ausente: gravado = o caso; o mutante recusa a opção |
| M22 | layout recebido gravado cru, sem conferir o enum | CT-12 | linha `"xpto"`: layout gravado nulo; o mutante grava "xpto" (e a renderização seguinte quebra) |

---

## Regra R5 — favoritos são de cada usuário, e o dock existe com 0 a 10 deles

> `RQ-10`, `RQ-02`, `RQ-20` · perfil **completo** · técnica: **IDOR por usuário** (duas pessoas no mesmo processo) + **cardinalidade 0/1/N**, com ausente (nulo) ≠ vazio

```gherkin
  Regra: cada usuário vê no dock os próprios favoritos, e o dock aparece mesmo sem nenhum

    Cenário: [CT-13] o dock de um usuário não mostra favorito de outro
      Dado o dock ligado no /admin
      E bruno com o favorito "lista de usuários do /admin" e outro admin, carla, com o favorito "lista de papéis do /admin"
      E o componente já foi renderizado para carla no mesmo processo
      Quando o componente do dock é renderizado para bruno
      Então o HTML contém o link para a URL da lista de usuários do /admin
      E não contém o link para a URL da lista de papéis do /admin

    Esquema do Cenário: [CT-14] o dock aparece com 0 a 10 favoritos e sempre oferece o gerenciamento
      Dado bruno, com Usar:Dock, no /admin ligado, com favoritos <favoritos>
      Quando o componente do dock é renderizado
      Então o HTML tem exatamente <n> links de favorito
      E a action de gerenciar os atalhos existe e está visível

      Exemplos:
        | favoritos            | n  | # partição       |
        | nunca gravados (nulo)| 0  | ausente          |
        | lista vazia []       | 0  | vazio            |
        | 1 destino            | 1  | um               |
        | 10 destinos          | 10 | teto (N)         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M23 | lista de favoritos única da instalação (settings/cache sem id do usuário) | CT-13 | HTML de bruno não contém a lista de papéis; o mutante mostra a de carla |
| M24 | memo dos atalhos por processo, não por usuário/request | CT-13 | idem: a renderização anterior de carla vaza para bruno |
| M25 | dock não renderiza quando o usuário não tem favorito (e ele nunca consegue gravar o primeiro) | CT-14 | linhas "nulo" e "[]": action de gerenciar visível; o mutante não renderiza |
| M26 | coluna nula tratada como erro (iteração sobre `null`) | CT-14 | linha "nulo": 0 links e action visível, sem exceção; o mutante lança |
| M27 | exibição limitada por `< 10` (perde o 10º) | CT-14 | linha 10: exatamente 10 links; o mutante mostra 9 |

---

## Regra R6 — o slideover grava só destinos do catálogo; envio com chave fora dele é recusado por inteiro

> `RQ-11`, `RQ-20`, `RQ-29` · perfil **completo** · técnica: **EP** com cada partição inválida isolada (uma chave inválida por linha, ao lado de **uma** chave válida — é o par que distingue "recusa o envio inteiro" de "descarta a inválida") + **criação ≠ edição ≠ uso** + atribuição em massa

```gherkin
  Regra: o favorito só pode ser destino do catálogo, por qualquer via de escrita, e o envio com chave fora dele não grava nada

    Cenário: [CT-15] o usuário grava dois destinos do catálogo pelo slideover
      Dado bruno, com Usar:Dock e nenhum favorito, no /admin ligado
      Quando ele grava pelo slideover do dock os destinos A (lista de usuários do /admin) e B (uma Page do /admin que ele abre)
      Então os favoritos gravados de bruno são exatamente A e B, nesta ordem
      E o componente renderizado em seguida contém os links das URLs de A e de B

    Esquema do Cenário: [CT-16] o slideover regrava a escolha: troca, esvazia ou mantém
      Dado bruno, com Usar:Dock, com os favoritos A e B gravados, no /admin ligado
      Quando ele grava pelo slideover <envio>
      Então os favoritos gravados de bruno são exatamente <gravados>, na ordem enviada

      Exemplos:
        | envio   | gravados | # operação                            |
        | [B, C]  | B, C     | troca (remove A, mantém B, acrescenta C) |
        | []      | nenhum   | remove todos                          |
        | [A, B]  | A, B     | mesma lista (regravação idempotente)  |

    Esquema do Cenário: [CT-17] envio com chave fora do catálogo é recusado por inteiro
      Dado ana, com Usar:Dock e o favorito X gravado, no /app ligado
      E Y, um destino do catálogo dela diferente de X
      Quando o cliente envia pelo slideover atalhos = [Y, <chave>]
      Então os favoritos gravados de ana continuam exatamente [X]
      E o componente renderizado em seguida não contém link para Y nem para nenhum destino de <chave>, sem exceção

      Exemplos:
        | chave                                   | # partição                                   |
        | "https://exemplo.invalido/x"            | URL externa                                  |
        | "/admin/users"                          | URL interna digitada                         |
        | admin\|{UserResource do /admin}\|       | destino que ana não pode abrir (painel sem acesso) |
        | admin\|App\Nada\Inexistente\|            | classe inexistente                           |
        | admin\|App\Models\User\|                 | classe que existe e não é tela               |
        | admin\|{EditUser do /admin}\|            | página de registro (exige registro)          |
        | financeiro\|{UserResource do /admin}\|   | painel inexistente                           |
        | "xpto"                                  | malformada (sem as partes)                   |
        | 42                                      | tipo errado (inteiro)                        |
        | ["a"]                                   | tipo errado (lista dentro da lista)          |

    Esquema do Cenário: [CT-18] chave inválida já gravada não aparece nem quebra a página
      Dado o dock ligado no /admin e bruno com os favoritos gravados [A, <chave>] por fora da aplicação
      Quando bruno abre a página inicial do /admin por HTTP
      Então a resposta é 200 e o HTML contém o link da URL de A
      E o HTML não contém link para <chave>

      Exemplos:
        | chave                         | # partição           |
        | "https://exemplo.invalido/x"  | URL externa          |
        | admin\|App\Nada\Inexistente\|  | classe inexistente   |
        | admin\|App\Models\User\|       | classe que não é tela|

    Esquema do Cenário: [CT-19] os favoritos e o layout do usuário não entram por atribuição em massa
      Dado bruno sem favoritos e sem layout próprio
      Quando o model do usuário recebe <atributos> por atribuição em massa e é salvo
      Então os favoritos gravados de bruno continuam nenhum e o layout próprio continua nulo

      Exemplos:
        | atributos                                                   |
        | favoritos = ["https://exemplo.invalido/x"]                  |
        | layout próprio = "xpto"                                     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | qualquer string enviada é gravada (sem conferir o catálogo) | CT-17 | linha URL externa: favoritos = [X]; o mutante grava [Y, URL] |
| M29 | catálogo = todas as telas de todos os painéis, sem filtrar acesso | CT-17 | linha "destino que ana não pode abrir": favoritos = [X]; o mutante grava |
| M30 | só `class_exists()` na parte de classe | CT-17 | linha `App\Models\User`: favoritos = [X]; o mutante grava |
| M31 | chave usada como índice/`explode` sem conferir as partes → exceção | CT-17 | linhas "xpto", 42, ["a"]: "sem exceção"; o mutante lança |
| M32 | conferência só na gravação; o uso confia no gravado (URL externa vira link) | CT-18 | linha URL externa: HTML sem o link; o mutante renderiza `href` externo |
| M33 | favoritos/layout no `$fillable` do model | CT-19 | favoritos continuam nenhum; o mutante grava a URL |
| M34 | gravação acrescenta em vez de substituir a escolha (remoção impossível) | CT-16 | linha "troca": gravados = B, C; o mutante mantém A |
| M102 | descarta só a chave inválida e grava as válidas do mesmo envio (o comportamento descrito no plano) | CT-17 | qualquer linha: favoritos = [X]; o mutante grava [Y] |

---

## Regra R7 — o catálogo é gerado das telas que o usuário pode abrir

> `RQ-12`, `RQ-11`, `RQ-04` · perfil **completo** · técnica: **matriz papel × tela** (persona discriminante: mesmo papel com e sem a permissão da tela) · o recorte do `/app` por organização aberta está em R18 (RQ-25)

```gherkin
  Regra: o slideover oferece todas as telas que o usuário abre, em todos os painéis que acessa, e nenhuma outra

    Esquema do Cenário: [CT-20] as opções do slideover seguem o acesso de quem o abre
      Dado o dock ligado no /admin e no /app, e <persona> com Usar:Dock
      Quando <persona> abre o slideover de gerenciar atalhos no painel <painel>
      Então as opções de destino <inclui_ou_nao> a tela <tela>

      Exemplos:
        | persona                                       | painel | tela                                 | inclui_ou_nao | # célula                       |
        | bruno (admin + panel_user)                    | app    | lista de usuários do /admin          | incluem       | outro painel, com acesso       |
        | bruno (admin + panel_user)                    | admin  | uma Page do /infra                   | não incluem   | painel sem acesso              |
        | ana (panel_user)                              | app    | lista de usuários do /admin          | não incluem   | painel sem acesso              |
        | bruno, papel admin sem a permissão de listar usuários | admin | lista de usuários do /admin    | não incluem   | mesmo papel, sem a permissão da tela |

    Cenário: [CT-21] tela de um painel novo entra no catálogo sem configuração
      Dado um painel registrado depois da instalação, com uma Page que o master_global abre
      E o master_global com o dock ligado no /admin
      Quando ele abre o slideover de gerenciar atalhos
      Então as opções de destino incluem a Page do painel novo

    Esquema do Cenário: [CT-22] o catálogo aberto numa organização respeita papel global e vínculo
      Dado a Acme e a Globex, o dock ligado no /app e <persona> com Usar:Dock, na Acme
      Quando <persona> abre o slideover de gerenciar atalhos
      Então as opções de destino <inclui_ou_nao> <tela>

      Exemplos:
        | persona                                         | tela                               | inclui_ou_nao | # célula                                  |
        | usuario de duasOrganizacoes (admin global)      | lista de usuários do /admin        | incluem       | destino do /admin avaliado no contexto global (onde o papel global vale), com o slideover aberto na organização |
        | usuária só da Acme (sem vínculo com a Globex)   | qualquer tela do /app na Globex    | não incluem   | organização sem vínculo |
```

Nota CT-21 (`@premissa` de mecanismo): `painelRegistradoEmTeste()` + uma Page registrada nele; o master_global é a persona porque o acesso a painel desconhecido pelo kit é dele. Se o arranjo não der acesso ao painel novo, o executor classifica como **CT errado** e devolve — não troca o oráculo por uma lista fixa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M35 | catálogo sem filtro de permissão da tela (só de acesso ao painel) | CT-20 | linha "mesmo papel, sem a permissão da tela": não inclui; o mutante inclui |
| M36 | catálogo sem filtro de acesso ao painel | CT-20 | linha ana: não inclui a lista do /admin; o mutante inclui |
| M37 | catálogo limitado ao painel corrente | CT-20 | linha bruno/app: inclui a lista do /admin; o mutante só oferece o /app |
| M38 | catálogo escrito à mão (lista de classes) | CT-21 | inclui a Page do painel novo; o mutante não a conhece |
| M39 | permissão de tela do /admin avaliada com o `team_id` da organização aberta (onde o papel global não vale) | CT-22 | linha admin global: inclui; o mutante exclui |
| M40 | catálogo do /app percorre todas as organizações da instalação, sem conferir vínculo | CT-22 | linha "sem vínculo": nenhuma opção da Globex; o mutante oferece |

---

## Regra R8 — favorito de outro painel aparece em todo painel com dock ligado, só enquanto o usuário acessa painel e tela; some sem ser apagado e volta

> `RQ-15`, `RQ-19`, `RQ-04` · perfil **completo** · técnica: **tabela estado × operação** (linha "oculto") + **2-switch** (revoga → restaura) + persona discriminante (permissão de mesmo nome sem acesso ao painel)

```gherkin
  Regra: o favorito vale em qualquer painel com dock, enquanto o usuário puder abrir o destino

    Cenário: [CT-23] favorito do /admin aparece no dock do /app, mesmo com o dock desligado no /admin
      Dado o dock ligado só no /app
      E bruno, com Usar:Dock, com o favorito "lista de usuários do /admin"
      Quando o componente do dock é renderizado no /app
      Então o HTML contém o link para a URL da lista de usuários no /admin, e não para a de outro painel

    Esquema do Cenário: [CT-24] sem acesso ao painel ou à tela, o favorito some da página e continua gravado
      Dado o dock ligado no /app e uma pessoa com os favoritos "lista de usuários do /admin" (U) e "uma tela do /app que ela abre" (P)
      E <perda>
      Quando a pessoa abre a página inicial do /app por HTTP
      Então o HTML contém o link de P e não contém o link de U
      E os favoritos gravados continuam U e P

      Exemplos:
        | perda                                                                 | # célula                       |
        | bruno, com a permissão de listar usuários revogada do papel admin     | perdeu a tela                  |
        | bruno, sem o papel admin                                              | perdeu o painel                |
        | ana, cujo papel do /app tem a permissão de listar usuários mas que não acessa o /admin | permissão sem o painel |

    Cenário: [CT-25] o favorito oculto volta quando a permissão volta
      Dado o dock ligado no /app, bruno com o favorito U e a permissão de listar usuários revogada do papel admin
      E o dock, renderizado no /app nesse estado, não continha o link de U (renderização intermediária observada)
      E a permissão foi restaurada ao papel admin depois dessa renderização
      Quando o componente do dock é renderizado de novo no /app
      Então o HTML contém o link de U
      E os favoritos gravados de bruno são exatamente U
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M41 | dock mostra só favoritos do painel corrente | CT-23 | HTML do /app contém o link do /admin; o mutante não |
| M42 | favorito filtrado pelo dock estar ligado no painel de origem | CT-23 | idem, com o /admin desligado; o mutante esconde |
| M43 | URL gerada no painel corrente (rota do /app) | CT-23 | href é a URL do /admin; o mutante gera do /app ou lança |
| M44 | permissão conferida só ao gravar; o uso confia no gravado | CT-24 | linha "perdeu a tela": sem o link de U; o mutante mostra |
| M45 | só a permissão da tela é conferida, sem o acesso ao painel | CT-24 | linha "permissão sem o painel": sem o link de U; o mutante mostra (o nome da permissão casa) |
| M46 | favorito sem acesso é apagado na leitura ("limpeza") | CT-24, CT-25 | CT-24: gravados continuam U e P depois da leitura sem acesso; CT-25: depois da renderização intermediária, gravados = U; o mutante apagou na leitura |
| M47 | resultado da autorização guardado entre requests (cache por usuário sem invalidar) | CT-25 | a renderização intermediária popula o "oculto"; depois de restaurar, o link de U aparece; o mutante devolve o oculto memorizado |

---

## Regra R9 — favorito do painel de organização guarda a organização, abre nela e some sem o acesso

> `RQ-16`, `RQ-19`, `RQ-21` · perfil **completo** · técnica: **tabela de decisão** (vínculo × papel na organização × organização existe) + **2-switch** com uso intermediário observado (desvincula → abre → revincula) + IDOR por organização + discriminante nulo (dock fora do `/app`, sem organização aberta: CT-26, CT-58) + organização da rota × da sessão (CT-51)

```gherkin
  Regra: o favorito do /app pertence à organização em que foi criado

    Esquema do Cenário: [CT-26] o favorito gravado na Globex abre na Globex, de onde quer que seja visto
      Dado o usuario de duasOrganizacoes com Usar:Dock e o dock ligado no /app e no /admin
      E ele gravou pelo slideover, dentro da Globex, a lista de projetos como favorito
      Quando o componente do dock é renderizado em <onde>
      Então o HTML contém o link para a URL da lista de projetos da Globex, e não da Acme

      Exemplos:
        | onde                       | # partição                       |
        | /app/acme                  | outra organização aberta       |
        | /admin (sem organização)   | discriminante nulo               |

    Esquema do Cenário: [CT-27] sem acesso à organização, o favorito dela some da página e continua gravado
      Dado o dock ligado no /app, o usuario de duasOrganizacoes com os favoritos "projetos na Acme" (PA) e "projetos na Globex" (PG)
      E <situacao>
      Quando ele abre a página inicial de /app/acme por HTTP
      Então o HTML <pg> o link de PG e contém o link de PA
      E os favoritos gravados continuam PA e PG

      Exemplos:
        | situacao                                                        | pg         | # célula                    |
        | desvinculado da Globex                                          | não contém | perdeu o vínculo            |
        | com o papel da Globex trocado por um do /app sem a permissão de listar projetos | não contém | papel na organização |
        | desvinculado da Globex, com /app/acme aberto uma vez sem o link de PG (observado), e vinculado de novo | contém | 2-switch (volta) |

    Esquema do Cenário: [CT-28] o slideover não grava favorito de organização sem vínculo
      Dado a usuária só da Acme, com Usar:Dock e o favorito "projetos na Acme" (PA) gravado, no /app/acme ligado
      Quando o cliente envia pelo slideover atalhos = [PA, <chave>]
      Então os favoritos gravados dela continuam exatamente [PA]

      Exemplos:
        | chave                                     | # partição                      |
        | app\|{ProjetoResource}\|{id da Globex}     | organização sem vínculo (IDOR)  |
        | app\|{ProjetoResource}\|                   | /app sem organização            |
        | app\|{ProjetoResource}\|{id inexistente}   | organização inexistente         |

    Cenário: [CT-51] "adicionar esta página" no /app grava a organização da rota
      Dado o usuario de duasOrganizacoes, com Usar:Dock e o dock ligado no /app, cuja organização da sessão e primeira vinculada é a Acme
      E ele está em /app/globex, na lista de projetos, que ainda não é favorita
      Quando ele escolhe a opção de adicionar a página atual
      Então o favorito gravado é a lista de projetos da Globex
      E o dock renderizado em /app/acme contém o link para a URL da lista de projetos da Globex, e não da Acme

    Cenário: [CT-58] fora do /app, o favorito de organização desvinculada some e continua gravado
      Dado o dock ligado no /admin, o usuario de duasOrganizacoes com os favoritos PA e PG
      E desvinculado da Globex
      Quando ele abre a página inicial do /admin por HTTP
      Então o HTML contém o link de PA e não contém o link de PG
      E os favoritos gravados continuam PA e PG
```

Nota CT-27: a organização **excluída** depois do favorito gravado fica no CT-29 (linha "organização excluída"), onde o oráculo é o contexto restaurado; aqui o `Dado` da Globex precisa existir para a linha de 2-switch.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | organização não guardada; o favorito abre na organização aberta | CT-26 | linha /app/acme: href da Globex; o mutante gera da Acme |
| M49 | sem organização aberta (/admin), o favorito do /app quebra ou some | CT-26 | linha /admin: href da Globex presente; o mutante lança ou omite |
| M50 | vínculo com a organização não conferido no uso | CT-27 | linha "perdeu o vínculo": sem PG; o mutante mostra |
| M51 | permissão do favorito avaliada com o papel da organização aberta (Acme) | CT-27 | linha "papel na organização": sem PG; o mutante usa o papel da Acme e mostra |
| M52 | favorito de organização sem acesso apagado no uso | CT-27 | linha 2-switch: a abertura intermediária sem vínculo é o uso; depois de revincular, PG volta e gravados continuam PA e PG; o mutante apagou na abertura intermediária |
| M53 | gravação aceita qualquer id de organização na chave | CT-28 | linha "sem vínculo": favoritos = [PA]; o mutante grava [PA, chave] |
| M106 | a página adicionada guarda a organização da sessão ou a primeira vinculada, não a da rota — revisão adversarial ADV-02 | CT-51 | favorito = projetos da Globex; o mutante grava os da Acme |
| M107 | fora do /app (sem organização aberta) o vínculo não é conferido — revisão adversarial ADV-11 | CT-58 | HTML do /admin sem o link de PG; o mutante mostra |

---

## Regra R10 — avaliar favoritos não altera painel, organização nem permissões do request

> `RQ-15`, `RQ-16`, `RQ-19` · perfil **completo** · técnica: **EP do tipo de favorito** (outro painel / outra organização / falha antes da troca / exceção depois da troca) × **contexto observado** depois da renderização · persona discriminante: K vale no contexto global e no da Globex, não no da Acme

```gherkin
  Regra: depois de avaliar os favoritos, o request continua no painel, na organização e com as permissões de antes

    Esquema do Cenário: [CT-29] o contexto do request sobrevive à avaliação de favorito de outro painel ou organização
      Dado o dock ligado no /app e no /admin, e dani (panel_user na Acme, admin_app na Globex, admin global) com Usar:Dock
      E a permissão K, que o admin_app e o admin global têm e o panel_user não tem (ex.: listar usuários), conferida no arranjo nos três papéis
      E dani com o favorito <favorito>, no /app/acme, onde ela não tem K (o papel global não vale dentro da organização)
      Quando o componente do dock é renderizado na Acme
      Então o painel corrente é o /app, a organização aberta é a Acme e o `team_id` de permissões é o da Acme
      E dani não tem K

      Exemplos:
        | favorito                                                                  | # partição                                         |
        | lista de usuários do /admin                                               | outro painel (contexto global, onde K vale)        |
        | lista de usuários da Globex                                               | outra organização (contexto da Globex, onde K vale) |
        | lista de projetos de uma organização excluída depois de gravado           | avaliação que falha antes da troca                 |
        | uma Page de painel registrado no teste cujo acesso lança exceção ao ser avaliado | avaliação que lança depois da troca de contexto |
```

Nota CT-29: a linha "outro painel" só discrimina porque K vale no contexto global (papel `admin`); a linha "outra organização", porque K vale no contexto da Globex (`admin_app`). A linha "lança depois da troca" usa `painelRegistradoEmTeste()` com uma Page de teste cuja verificação de acesso lança; é a única em que a exceção nasce com o contexto já trocado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M54 | painel corrente trocado para avaliar e não restaurado | CT-29 | linha "outro painel": painel corrente = app; o mutante deixa admin |
| M55 | organização aberta trocada e não restaurada | CT-29 | linha "outra organização": organização = Acme; o mutante deixa Globex |
| M56 | `team_id` de permissões trocado e não restaurado | CT-29 | linhas "outro painel" e "outra organização": dani não tem K; o mutante deixa o contexto global ou o da Globex, onde ela tem K |
| M57 | restauração fora do `finally`: exceção na avaliação deixa o contexto trocado | CT-29 | linha "lança depois da troca": painel /app, Acme e `team_id` da Acme; o mutante fica no painel de teste |
| M58 | memo do Shield (lista de telas por painel) do painel avaliado sobrevive à troca | ⚠️ **sem matador** — o efeito só aparece em telas do Shield renderizadas DEPOIS do dock no mesmo request; o dock fica no fim do `BODY`, então nenhuma tela observável roda depois; tentado: expor pela própria `canAccess()` de uma Page do /app chamada após o render — mede o vendor, não o requisito | — (lacuna declarada) |

---

## Regra R11 — a opção do dock adiciona a página atual, se ela for destino do catálogo

> `RQ-21`, `RQ-11` · perfil **completo** · técnica: **EP** (lista de Resource / Page avulsa / página fora do acesso) + superfície Livewire (props `#[Locked]`) · onde a opção aparece (RQ-23) e a página já favorita (RQ-28) estão em R15 e R16

```gherkin
  Regra: adicionar a página atual grava o destino dela, e só se ele for do catálogo do usuário

    Esquema do Cenário: [CT-30] a página atual que é destino do catálogo vira favorito
      Dado bruno, com Usar:Dock e nenhum favorito, no /admin ligado
      E a página atual do dock é <pagina>
      Quando ele escolhe a opção do dock de adicionar a página atual
      Então os favoritos gravados de bruno são exatamente o destino de <pagina>
      E o componente renderizado em seguida contém o link para a URL de <pagina>

      Exemplos:
        | pagina                                | # partição          |
        | a lista de usuários do /admin         | lista de Resource   |
        | uma Page avulsa do /admin que ele abre| Page avulsa         |

    Cenário: [CT-31] página que o usuário não pode abrir não vira favorito
      Dado ana, com Usar:Dock e nenhum favorito, no /app ligado
      E a página atual do dock posta como a lista de usuários do /admin
      Quando ana chama a action de adicionar a página atual
      Então os favoritos gravados de ana continuam nenhum

    Esquema do Cenário: [CT-32] o cliente não troca a página atual do dock
      Dado bruno, com Usar:Dock e nenhum favorito, com a página atual do dock sendo uma Page avulsa do /admin
      Quando o cliente altera a propriedade <propriedade> do componente para <valor>
      Então a alteração é recusada pelo componente
      E os favoritos gravados de bruno continuam nenhum

      Exemplos:
        | propriedade      | valor                                         | # partição           |
        | routeName        | "filament.infra.pages.backup-runs-page"       | rota fora do domínio do usuário |
        | routeParameters  | {"tenant": "globex"}                          | parâmetro forjado    |
        | path             | ["array"]                                     | tipo errado          |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M59 | grava o caminho/URL da página em vez do destino do catálogo | CT-30 | favoritos = destino; o href renderizado é a URL de `getUrl()`; o mutante grava o path cru |
| M60 | adição sem conferir o catálogo do usuário | CT-31 | favoritos continuam nenhum; o mutante grava a lista do /admin para ana |
| M61 | Page avulsa não reconhecida (só listas de Resource) | CT-30 | linha "Page avulsa": gravada; o mutante não grava |
| M62 | propriedade de contexto sem `#[Locked]` (o cliente escolhe a página) | CT-32 | alteração recusada; o mutante aceita e a página forjada pode ser gravada |

---

## Regra R12 — no máximo 10 favoritos por usuário; o 11º é recusado com aviso e nada muda

> `RQ-18`, `RQ-26` · perfil **completo** · técnica: **BVA 3-valores** (incremento: 1 favorito) nos dois pontos de entrada, com o **número literal do requisito** (10), nunca lido de config + **rastreio de não-efeito** na recusa (os favoritos gravados do `Dado` existem e mudariam no caminho feliz) · favorito oculto no teto: R19

```gherkin
  Regra: nenhum usuário fica com mais de 10 favoritos gravados; o que passaria do teto é recusado com aviso, e nada muda

    Esquema do Cenário: [CT-33] adicionar a página atual respeita o teto de 10
      Dado bruno (admin + infra), com Usar:Dock e <ja_gravados> favoritos visíveis, numa página do catálogo que ainda não é favorita
      Quando ele escolhe a opção do dock de adicionar a página atual
      Então os favoritos gravados de bruno são <gravados>
      E <aviso>

      Exemplos:
        | ja_gravados | gravados                                         | aviso                                   | # borda                      |
        | 8           | os 8 do Dado, na mesma ordem, e a página atual por último | nenhum aviso de limite é exibido | borda−1 (resultado 9)        |
        | 9           | os 9 do Dado, na mesma ordem, e a página atual por último | nenhum aviso de limite é exibido | borda (resultado 10)         |
        | 10          | exatamente os 10 do Dado, na mesma ordem         | um aviso que cita o limite de 10 é exibido | borda+1 (recusado, nada muda) |

    Esquema do Cenário: [CT-34] o slideover respeita o teto de 10
      Dado bruno (admin + infra), com Usar:Dock e o favorito A gravado, com ao menos 12 destinos no catálogo
      Quando ele envia pelo slideover <enviados> destinos distintos do catálogo, sem A
      Então os favoritos gravados de bruno são <gravados>
      E <aviso>

      Exemplos:
        | enviados | gravados                              | aviso                                      | # borda                      |
        | 9        | exatamente os 9 enviados, na ordem    | nenhum aviso de limite é exibido           | borda−1                      |
        | 10       | exatamente os 10 enviados, na ordem   | nenhum aviso de limite é exibido           | borda                        |
        | 11       | continuam exatamente [A]              | um aviso que cita o limite de 10 é exibido | borda+1 (recusado, nada muda) |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M63 | `<=` no lugar de `<` (aceita o 11º) | CT-33, CT-34 | linha borda+1: gravados = os 10 do Dado (CT-33) / [A] (CT-34); o mutante grava 11 |
| M64 | teto 9 (`< 9` / off-by-one para baixo) | CT-33, CT-34 | linha borda: o 10º é gravado e não há aviso; o mutante recusa e avisa |
| M65 | teto aplicado só no formulário do slideover (`maxItems`), não na adição da página | CT-33 | linha borda+1: gravados = os 10 do Dado; o mutante grava 11 pela opção do dock |
| M66 | teto lido de config com default diferente de 10 | CT-33, CT-34 | linhas borda e borda+1 com o literal 10; o mutante diverge |
| M73 | excedente cortado em silêncio (grava os 10 primeiros do envio) — o comportamento do plano | CT-34 | linha borda+1: gravados = [A]; o mutante grava 10 dos 11 enviados |
| M74 | o 11º substitui o favorito mais antigo | CT-33 | linha borda+1: exatamente os 10 do Dado e a página fora; o mutante tira o primeiro e grava a página |
| M75 | recusa sem aviso | CT-33, CT-34 | linha borda+1: aviso que cita 10; o mutante recusa calado |

---

## Regra R13 — decidir se o dock aparece e como aparece não acrescenta consulta ao banco

> `RQ-22` · perfil **padrão** · técnica: **contagem de consultas** por request, ligado × desligado — à tabela de settings (CT-35, CT-36) e ao banco inteiro (CT-56), com atribuição pela pilha na tabela de settings (`@premissa` de mecanismo, decidida pela sessão em Q20: `DB::listen()` + `debug_backtrace()`) e contagem total do request em CT-56, sem favoritos (a autorização de cada favorito não é "decidir se o dock aparece")

```gherkin
  Regra: decidir se o dock aparece e como aparece não acrescenta consulta ao banco

    Esquema do Cenário: [CT-35] o dock não consulta a tabela de settings para decidir nem para desenhar
      Dado bruno, com Usar:Dock, 3 favoritos e layout próprio, e o settings com layout e estilo padrão
      E o dock <estado> no /admin
      Quando bruno abre a página inicial do /admin por HTTP, com as consultas registradas
      Então nenhuma consulta à tabela de settings tem, na pilha, código do dock (pacote ou classes do kit para o dock)

      Exemplos:
        | estado    | # partição                 |
        | ligado    | decidir + como aparece     |
        | desligado | só decidir                 |

    Cenário: [CT-36] ligar o dock não acrescenta consulta à tabela de settings
      Dado bruno, com Usar:Dock, 3 favoritos e layout próprio, e a página inicial do /admin já aberta uma vez antes das medidas (aquecimento)
      E a página, aberta de novo com o dock desligado, fez N consultas à tabela de settings
      Quando bruno abre a mesma página com o dock ligado
      Então a página faz exatamente N consultas à tabela de settings

    Cenário: [CT-56] ligar o dock não acrescenta consulta nenhuma ao banco
      Dado bruno, com Usar:Dock, sem favoritos e com layout próprio, o settings com layout e estilo padrão, e a página inicial do /admin já aberta uma vez antes das medidas (aquecimento)
      E a página, aberta de novo com o dock desligado, fez T consultas ao banco, em qualquer tabela
      Quando bruno abre a mesma página com o dock ligado
      Então a página faz exatamente T consultas ao banco

    Cenário: [CT-65] o painel com o dock desligado consulta a settings tanto quanto um painel sem o plugin
      Dado um painel registrado no teste sem o plugin do dock, e o /admin com o plugin e o dock desligado
      E bruno, com Usar:Dock, e os dois painéis já bootados e renderizados uma vez antes das medidas (aquecimento)
      Quando cada painel é bootado e renderiza o fim do corpo da página (onde o dock entra), com as consultas registradas
      Então o número de consultas à tabela de settings é igual nos dois
```

Nota CT-65 (`@premissa` de mecanismo, aceita pela sessão na rodada 2): `painelRegistradoEmTeste()` cria o painel sem o plugin; a medida é feita no boot do painel e na renderização do gancho do fim do corpo, porque o painel de teste não tem rotas. É o único cenário que pega a leitura do settings feita numa **closure registrada no PanelProvider** — ela não tem código do dock na pilha, e CT-35 não a vê. Se o arnês não alcançar a renderização do gancho, o executor devolve como **CT errado**, sem trocar o oráculo.

Nota CT-36/CT-56 (ADV-22): as duas medidas partem do mesmo estado de aquecimento — a página é aberta uma vez antes delas —, senão o cache que só o primeiro request aquece somaria consultas a um lado só.

Nota CT-56: sem favoritos, o que o dock faz é exatamente "decidir se aparece" (painel, permissão) e "como aparece" (layout, estilo) — o escopo de RQ-22. Com favoritos, a autorização de **cada destino** (RQ-04, RQ-15, RQ-16) é outra decisão, que a RQ-22 não cobre. O `phpunit.xml` usa `CACHE_STORE=array`: não há tabela de cache a contar, e o cache de permissões do spatie já está aquecido pela página no desligado.

Nota: as suítes rodam com `RefreshDatabase`, e o boot alinha settings → config **antes** das migrations (docblock de `alinharConfiguracoesDoKit()` em `tests/Pest.php`): nenhum objeto de settings está resolvido no request do teste, então uma leitura do settings pelo dock gera consulta real — é isso que torna a contagem discriminante. O `Dado` de CT-35/CT-36 usa `config()->set()` para ligar o dock, nunca o settings.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M67 | dock resolve o objeto de settings da instalação a cada request para ler painéis/layout/estilo | CT-35, CT-36 | consulta à settings com frame do dock (CT-35); N+1 consultas (CT-36) |
| M68 | leitura do settings só para o estilo/layout ("como aparece") | CT-35 | linha "ligado": consulta com frame do dock; o mutante consulta |
| M69 | leitura do settings para decidir, mesmo com o dock desligado | CT-35 | linha "desligado": consulta com frame do dock; o mutante consulta (CT-36 não pega: o baseline também a teria) |
| M70 | uma leitura por favorito (`n` consultas) | CT-36 | exatamente N; o mutante faz N+3 |
| M117 | o dock recarrega o usuário, os papéis ou as permissões sem cache para decidir ou desenhar (consulta fora da tabela de settings) — revisão adversarial ADV-06 | CT-56 | exatamente T; o mutante faz T+1 ou mais |
| M130 | o dock lê o settings ou as permissões de um cache em store `database` a cada request — revisão adversarial ADV-06 (rodada 2) | ⚠️ **sem matador** — lacuna de pilha: o `phpunit.xml` força `CACHE_STORE=array`, então a leitura do cache não vira consulta e nenhum cenário a vê. Tentativa prevista para o executor: `config(['cache.default' => 'database'])` antes do primeiro request do CT-56. Se o store do spatie e o do settings já estiverem resolvidos no boot do provider, a troca não os alcança e a lacuna fica | — (lacuna declarada) |
| M131 | leitura do settings numa closure do PanelProvider (`visible`/`layout` do plugin), fora das classes do dock — revisão adversarial ADV-07 | CT-65 | consultas à settings iguais; o mutante soma uma no painel com o plugin |

---

## Regra R14 — a permissão do dock existe e é concedível na tela de papéis

> `RQ-13` · perfil **mínimo** · técnica: **EP** + efeito da concessão (CT-59, ADV-12) · a semeadura por papel (RQ-24) está em R17

```gherkin
  Regra: a permissão do dock é concedida a papéis na tela de papéis

    Cenário: [CT-37] a permissão do dock existe depois dos seeders e é oferecida na tela de papéis
      Dado os seeders de permissões e de papéis executados
      Quando o administrador abre a edição de um papel do /app na tela de papéis
      Então a permissão "Usar:Dock" existe no banco
      E ela é uma das opções concedíveis ao papel

    Cenário: [CT-59] conceder o dock pela tela de papéis faz o dock aparecer para quem tem o papel
      Dado o dock ligado no /app e ana, do papel panel_user, sem Usar:Dock, com um favorito para uma tela do /app que ela abre
      Quando o administrador marca Usar:Dock no papel panel_user pela tela de papéis e salva
      Então a página inicial do /app, aberta por ana, contém o componente do dock e o link do favorito
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M71 | permissão citada nas actions e ausente das permissões customizadas do Shield (nunca criada) | CT-37 | "Usar:Dock" existe; o mutante não cria |
| M72 | permissão criada sem painel declarado no mapa do seeder (não aparece na tela de papéis do painel) | CT-37 | opção presente; o mutante não a oferece |
| M122 | o dock confere um nome ou guard de permissão diferente do que a tela de papéis grava — revisão adversarial ADV-12 | CT-59 | o dock aparece para ana depois da concessão; o mutante não |

---

## Regra R15 — a opção "adicionar esta página" só aparece em destino do catálogo que ainda não é favorito

> `RQ-23`, `RQ-28` · perfil **completo** · técnica: **EP** do tipo de página atual (lista de Resource / Page avulsa / criar / editar / ver / fora do catálogo / já favorita) + **autorização na ação** (a action escondida, chamada direto, não grava)

```gherkin
  Regra: "adicionar esta página" só é oferecida onde a página atual é destino do catálogo e ainda não é favorita

    Esquema do Cenário: [CT-38] a opção de adicionar a página aparece só em destino do catálogo ainda não favorito
      Dado bruno, com Usar:Dock e o favorito "uma Page avulsa do /admin" (F) gravado, no /admin ligado
      E a página atual do dock é <pagina>
      Quando o componente do dock é renderizado
      Então a action de adicionar a página atual está <visibilidade>

      Exemplos:
        | pagina                                                        | visibilidade | # partição                 |
        | a lista de usuários do /admin                                 | visível      | lista de Resource          |
        | outra Page avulsa do /admin que ele abre, diferente de F       | visível      | Page avulsa                |
        | a tela de criar usuário do /admin                              | oculta       | tela de registro: criar    |
        | a tela de editar um usuário do /admin                          | oculta       | tela de registro: editar   |
        | a tela de ver um usuário do /admin (se o Resource a tiver)     | oculta       | tela de registro: ver      |
        | uma página do /admin fora do catálogo de bruno                 | oculta       | fora do catálogo           |
        | F                                                              | oculta       | já favorita (RQ-28)        |

    Esquema do Cenário: [CT-39] chamada direta da opção escondida não grava nada
      Dado bruno, com Usar:Dock e o favorito F gravado, no /admin ligado
      E a página atual do dock é <pagina>
      Quando o cliente chama direto a action de adicionar a página atual
      Então os favoritos gravados de bruno continuam exatamente [F]

      Exemplos:
        | pagina                                    | # partição                                   |
        | a tela de editar um usuário do /admin     | tela de registro (não grava nem a lista do Resource) |
        | a tela de criar usuário do /admin         | tela de registro                             |
        | uma página do /admin fora do catálogo     | fora do catálogo                             |
```

Nota CT-38: "página fora do catálogo" é escolhida no próprio arranjo — uma página do `/admin` que **não** aparece entre as opções do slideover de bruno no mesmo teste. Se nenhuma existir no kit, o executor devolve como **CT errado**, sem trocar o oráculo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M76 | opção visível em toda página com Resource (inclusive criar/editar/ver) | CT-38 | linhas de tela de registro: "oculta"; o mutante mostra |
| M77 | em tela de registro, a opção favorita a lista do Resource (a leitura descartada de Q11) | CT-39 | linha "editar": favoritos = [F]; o mutante grava a lista de usuários |
| M78 | visibilidade não confere se a página já é favorita | CT-38 | linha F: "oculta"; o mutante mostra |
| M79 | a condição de visibilidade existe só no `visible()`, e a action executa quando chamada direto | CT-39 | qualquer linha: favoritos = [F]; o mutante grava |
| M80 | qualquer Page registrada no painel conta como destino (sem conferir o catálogo do usuário) | CT-38, CT-39 | linha "fora do catálogo": oculta e [F]; o mutante mostra e grava |

---

## Regra R16 — o mesmo destino nunca fica gravado duas vezes

> `RQ-28`, `RQ-16` · perfil **completo** · técnica: **EP** das duas entradas (página atual × slideover) e da organização (mesma × outra) + **idempotência ancorada no agregado** (os favoritos gravados, não a tela) · CT-63 e CT-64 vivem na suíte Tenancy (G4)

```gherkin
  Regra: cada destino aparece no máximo uma vez nos favoritos gravados, e a organização faz parte do destino

    Esquema do Cenário: [CT-40] o mesmo destino não é gravado duas vezes
      Dado bruno, com Usar:Dock e os favoritos A e B gravados, no /admin ligado, com a página atual sendo A
      Quando <operacao>
      Então A aparece exatamente uma vez nos favoritos gravados de bruno
      E o total de favoritos gravados não passa de <maximo>

      Exemplos:
        | operacao                                                    | maximo | # entrada              |
        | o cliente chama direto a action de adicionar a página atual | 2      | página atual já favorita |
        | o cliente chama a mesma action duas vezes seguidas          | 2      | repetição (duplo clique) |
        | ele envia pelo slideover [A, A, B]                          | 2      | chave repetida no envio |

    Cenário: [CT-63] a mesma tela em outra organização é outro destino para "adicionar esta página"
      Dado o usuario de duasOrganizacoes, com Usar:Dock e o dock ligado no /app, com o favorito PA (projetos da Acme) gravado
      E a página atual é a lista de projetos em /app/globex
      Quando o componente do dock é renderizado em /app/globex
      Então a action de adicionar a página atual existe e está visível

    Cenário: [CT-64] adicionar a mesma tela em outra organização grava um favorito novo
      Dado o usuario de duasOrganizacoes, com Usar:Dock e o dock ligado no /app, com o favorito PA gravado, na lista de projetos em /app/globex
      Quando ele escolhe a opção de adicionar a página atual
      Então os favoritos gravados são exatamente [PA, PG], nesta ordem
```

Nota CT-40: a linha do slideover não afirma se o envio é gravado sem a repetição ou recusado — as duas leituras deixam A uma vez e no máximo 2 favoritos; RQ-28 só decide a unicidade.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M81 | adição da página atual sem conferir se já é favorita (só a visibilidade confere) | CT-40 | linhas "página atual já favorita" e "duplo clique": A uma vez; o mutante grava A duas ou três vezes |
| M82 | slideover grava a lista como veio, sem unicidade | CT-40 | linha [A, A, B]: A uma vez; o mutante grava [A, A, B] |
| M83 | unicidade só na exibição do dock (banco com repetição) | CT-40 | o `Então` lê os favoritos gravados; o mutante exibe uma vez e grava duas |
| M128 | unicidade comparada só pelo Resource, sem a organização — revisão adversarial ADV-24 | CT-63, CT-64 | CT-63: a opção está visível; CT-64: gravados [PA, PG]; o mutante esconde a opção e não grava PG |

---

## Regra R17 — a instalação nova concede o dock aos papéis de administração dos três painéis e não ao `panel_user`

> `RQ-24` · perfil **mínimo** · técnica: **matriz papel × permissão** · estouro declarado: 2 cenários (um por suíte — `admin_app` só existe com tenancy)

```gherkin
  Regra: os seeders entregam Usar:Dock a quem administra cada painel, e só a eles

    Esquema do Cenário: [CT-41] a semeadura single-tenant entrega o dock ao admin e ao infra, não ao panel_user
      Dado uma instalação sem multi-organização
      Quando os seeders de permissões e de papéis rodam
      Então o papel <papel> <tem> a permissão Usar:Dock

      Exemplos:
        | papel      | tem       | # painel administrado |
        | admin      | tem       | /admin                |
        | infra      | tem       | /infra                |
        | panel_user | não tem   | usuário comum do /app |

    Esquema do Cenário: [CT-42] com multi-organização, o admin da organização recebe o dock e o panel_user não
      Dado uma instalação com multi-organização
      Quando os seeders de permissões e de papéis rodam
      Então o papel <papel> <tem> a permissão Usar:Dock

      Exemplos:
        | papel      | tem     | # painel administrado |
        | admin_app  | tem     | /app (organização)    |
        | admin      | tem     | /admin                |
        | infra      | tem     | /infra                |
        | panel_user | não tem | usuário comum do /app |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M84 | permissão entregue a todo papel com painel declarado (inclusive `panel_user`) | CT-41, CT-42 | linhas `panel_user`: "não tem"; o mutante entrega |
| M85 | permissão entregue só ao `admin` (esquece `infra`) | CT-41 | linha `infra`: "tem"; o mutante não entrega |
| M86 | permissão do /app entregue só ao papel global, esquecendo o `admin_app` do ramo de tenancy | CT-42 | linha `admin_app`: "tem"; o mutante não entrega |
| M120 | no ramo de tenancy o seeder entrega só ao `admin_app`, esquecendo `admin` e `infra` — revisão adversarial ADV-10 | CT-42 | linhas `admin` e `infra`: "tem"; o mutante não entrega |

---

## Regra R18 — o catálogo do `/app` é só da organização aberta e só dentro do `/app`; favoritos de outras organizações continuam

> `RQ-25`, `RQ-12`, `RQ-16`, `RQ-32` · perfil **completo** · técnica: **tabela de decisão** (onde o slideover abre × organização da tela) + **estado × operação** (linha "de outra organização" da matriz)

```gherkin
  Regra: o slideover oferece telas do /app só da organização em que está aberto, e salvar não apaga favorito de outra organização

    Esquema do Cenário: [CT-43] telas do /app oferecidas só da organização aberta e só dentro do /app
      Dado o usuario de duasOrganizacoes (panel_user na Acme e na Globex, admin global), com Usar:Dock, e o dock ligado no /app e no /admin
      Quando ele abre o slideover de gerenciar atalhos em <onde>
      Então as opções de destino <inclui_ou_nao> a lista de projetos <de_qual>

      Exemplos:
        | onde       | de_qual     | inclui_ou_nao | # célula                                  |
        | /app/acme  | da Acme     | incluem       | organização aberta                      |
        | /app/acme  | da Globex   | não incluem   | organização vinculada, mas não a aberta |
        | /admin     | da Acme     | não incluem   | aberto fora do /app                       |
        | /admin     | da Globex   | não incluem   | aberto fora do /app                       |

    Esquema do Cenário: [CT-44] favorito de outra organização continua gravado e conta no teto
      Dado o dock ligado no /app, o usuario de duasOrganizacoes com Usar:Dock, em /app/acme, com os favoritos <gravados_antes>, entre eles "projetos na Globex" (PG)
      Quando <operacao>
      Então os favoritos gravados são <gravados_depois>
      E o dock renderizado na Acme contém o link de PG

      Exemplos:
        | gravados_antes                 | operacao                                                                    | gravados_depois                    | # célula                |
        | PA, PG                         | ele salva o slideover sem mexer nas escolhas                                | exatamente PA e PG                 | salva sem tocar          |
        | 9 destinos da Acme e PG (10)   | ele escolhe a opção de adicionar a página atual, uma lista da Acme ainda não favorita | exatamente os 10 do Dado, com aviso do limite de 10 | teto            |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M87 | catálogo do /app percorre todas as organizações vinculadas | CT-43 | linha "/app/acme · da Globex": não inclui; o mutante inclui |
| M88 | telas do /app oferecidas também fora do /app (organização "primeira" ou da sessão) | CT-43 | linhas "/admin": não inclui; o mutante oferece a Acme |
| M89 | salvar o slideover reconstrói os favoritos só a partir do catálogo corrente e apaga os de outra organização | CT-44 | linha "salva sem tocar": PA e PG gravados e link de PG; o mutante grava só PA |
| M103 | o teto conta só os favoritos da organização aberta | CT-44 | linha "teto": exatamente os 10 do Dado e aviso; o mutante conta 9 e grava o 11º |

Nota: M89 e M103 são a mesma família de M91/M93 de R19, na organização — a linha "de outra organização" da matriz não herda evidência da linha "oculto".

---

## Regra R19 — o favorito oculto fica fora do slideover, é preservado ao salvar e conta no teto; chave que nunca foi destino é descartada ao salvar e, enquanto gravada, ocupa vaga

> `RQ-27`, `RQ-32`, `RQ-19`, `RQ-18`, `RQ-15` · perfil **completo** · técnica: **tabela estado × operação** (linhas "oculto" e "inválido" da matriz; "visível de outro painel" como contraste do oculto) + **BVA** do teto com um oculto e com uma inválida (9 + 1, 10 + 1)

```gherkin
  Regra: o oculto não é oferecido, sobrevive ao salvar e ocupa vaga; o que nunca foi destino ocupa vaga até sair na gravação; o visível de outro painel é removível

    Cenário: [CT-45] o favorito oculto não é oferecido no slideover
      Dado o dock ligado no /app, bruno com os favoritos U ("lista de usuários do /admin") e P ("uma tela do /app que ele abre")
      E a permissão de listar usuários revogada do papel admin (U oculto)
      Quando ele abre o slideover de gerenciar atalhos no /app
      Então nem as opções de destino nem os escolhidos do slideover incluem U

    Cenário: [CT-60] o favorito oculto sobrevive ao salvar o slideover
      Dado o dock ligado no /app, bruno com os favoritos U e P gravados, nesta ordem, e U oculto (permissão de listar usuários revogada do papel admin)
      Quando ele salva o slideover escolhendo P e Q, outro destino do catálogo dele
      Então os favoritos gravados de bruno são exatamente {U, P, Q}, cada um uma vez

    Cenário: [CT-53] favorito visível de outro painel sai quando o usuário o remove pelo slideover
      Dado o dock ligado no /app, bruno com os favoritos U ("lista de usuários do /admin", visível) e P ("uma tela do /app")
      Quando ele salva, no slideover aberto no /app, a escolha [P]
      Então os favoritos gravados de bruno são exatamente [P]
      E o dock renderizado em seguida não contém o link de U

    Esquema do Cenário: [CT-46] a chave que nunca foi destino é descartada quando o slideover é salvo
      Dado bruno, com Usar:Dock, no /admin ligado, com os favoritos gravados [A, "https://exemplo.invalido/x", B] por fora da aplicação
      Quando ele salva o slideover com <envio>
      Então os favoritos gravados de bruno são exatamente <gravados>

      Exemplos:
        | envio  | gravados | # célula da matriz        |
        | [A, B] | A, B     | inválido × regravar sem tocá-lo |
        | [B, C] | B, C     | inválido × remover/trocar  |

    Esquema do Cenário: [CT-54] reenviar a chave inválida já gravada recusa o envio inteiro
      Dado bruno, com Usar:Dock, no /admin ligado, com os favoritos gravados [A, "https://exemplo.invalido/x", B] por fora da aplicação
      Quando o cliente envia pelo slideover <envio>
      Então os favoritos gravados de bruno continuam exatamente [A, "https://exemplo.invalido/x", B]

      Exemplos:
        | envio                                  | # partição                                 |
        | [A, "https://exemplo.invalido/x"]      | inválida já gravada + válida               |
        | [A, "https://exemplo.invalido/x", B]   | os gravados exatamente como estão          |

    Esquema do Cenário: [CT-47] o favorito oculto e a chave inválida ainda gravada ocupam vaga no teto de 10
      Dado bruno (admin + infra), com Usar:Dock, com <visiveis> favoritos visíveis e 1 entrada <extra> gravados
      Quando <operacao>
      Então os favoritos gravados de bruno são <gravados>
      E <aviso>

      Exemplos:
        | visiveis | extra    | operacao                                                 | gravados                                  | aviso                                      | # borda                            |
        | 8        | oculta   | ele adiciona a página atual (destino ainda não favorito) | os 9 do Dado e a página atual por último  | nenhum aviso de limite é exibido           | 8 + 1 oculto + 1 = 10 (aceito)     |
        | 9        | oculta   | ele adiciona a página atual (destino ainda não favorito) | exatamente os 10 do Dado                  | um aviso que cita o limite de 10 é exibido | 9 + 1 oculto + 1 = 11 (recusado)   |
        | 9        | oculta   | ele salva o slideover com 9 destinos visíveis            | os 9 enviados e o oculto                  | nenhum aviso de limite é exibido           | 9 + 1 oculto = 10 (aceito)         |
        | 9        | oculta   | ele salva o slideover com 10 destinos visíveis           | exatamente os 10 do Dado                  | um aviso que cita o limite de 10 é exibido | 10 + 1 oculto = 11 (recusado)      |
        | 8        | inválida | ele adiciona a página atual (destino ainda não favorito) | os 9 do Dado e a página atual por último  | nenhum aviso de limite é exibido           | 8 + 1 inválida + 1 = 10 (aceito, RQ-32) |
        | 9        | inválida | ele adiciona a página atual (destino ainda não favorito) | exatamente os 10 do Dado                  | um aviso que cita o limite de 10 é exibido | 9 + 1 inválida + 1 = 11 (recusado, RQ-32) |
        | 9        | inválida | ele salva o slideover com 10 destinos visíveis           | exatamente os 10 enviados                 | nenhum aviso de limite é exibido           | a inválida sai ao salvar (RQ-27): 10 (aceito) |
```

Nota CT-60: U é de painel diferente do painel em que o slideover abre e foi gravado **antes** de P; o oráculo é o **conjunto** exato {U, P, Q} — RQ-27 não fixa a posição do preservado, e RQ-33 só fixa a da página adicionada pela opção do dock. CT-53 é o contraste: o mesmo U, **visível**, sai quando não é escolhido — a regra do oculto não pode virar "preserva todo favorito de outro painel".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M90 | favoritos gravados entram nas opções ou nos escolhidos do slideover sem passar pela autorização (rótulo do oculto exposto) | CT-45 | opções e escolhidos sem U; o mutante inclui |
| M91 | ao salvar, mantém só chaves do envio ou "ainda autorizadas" — o oculto é apagado (comportamento do plano) | CT-60 | gravados = {U, P, Q}; o mutante grava só P e Q |
| M92 | ao salvar, preserva toda chave gravada fora do envio (inclusive a que nunca foi destino) | CT-46 | linha [A, B]: exatamente A, B; o mutante mantém a URL |
| M93 | o teto conta só os visíveis | CT-47 | linhas "11 (recusado)": gravados = os 10 do Dado e aviso; o mutante grava 11 |
| M94 | o teto do slideover soma o envio com todos os gravados (conta duas vezes os que continuam) | CT-47 | linha "9 + 1 oculto = 10" (slideover): aceito sem aviso; o mutante recusa |
| M114 | ao salvar, preserva todo favorito de outro painel (trata o visível como oculto) — revisão adversarial ADV-04 | CT-53 | gravados = [P]; o mutante mantém U |
| M115 | o teto conta só destinos válidos e ignora a chave inválida ainda gravada | CT-47 | linha "9 + 1 inválida + 1": gravados = os 10 do Dado e aviso; o mutante grava 11 |
| M121 | chave já gravada é aceita no envio só por estar gravada, mesmo inválida (a leitura ampla do plano) — revisão adversarial ADV-05 | CT-54 | gravados continuam os 3 do Dado; o mutante grava [A, URL] |
| M129 | o teto do slideover conta a inválida que a própria gravação vai descartar — revisão adversarial ADV-25 | CT-47 | linha "inválida, 10 visíveis": exatamente os 10 enviados, sem aviso; o mutante recusa com aviso |

---

## Regra R20 — a ordem dos atalhos é a escolhida pelo usuário; a página adicionada entra no fim

> `RQ-30`, `RQ-33`, `RQ-20` · perfil **completo** · técnica: **EP** de ordem (a escolhida difere da alfabética, da do catálogo e do agrupamento por painel) + **2-switch** (grava → reordena)

```gherkin
  Regra: o dock mostra os atalhos na ordem do usuário, e a página adicionada entra no fim

    Esquema do Cenário: [CT-48] o dock segue a ordem escolhida e a reordenação do slideover
      Dado o dock ligado no /admin e bruno (admin + infra), com Usar:Dock, com os favoritos gravados [C, A, B]
      E C é uma Page do /infra, A é a lista de usuários do /admin e B é uma Page do /admin — nem alfabética, nem do catálogo, nem agrupada por painel
      Quando ele salva o slideover com <envio>
      Então os favoritos gravados são <envio>, nesta ordem
      E os links do dock renderizado em seguida aparecem agrupados por painel (/admin antes de /infra), na ordem de <envio> dentro de cada painel

      Exemplos:
        | envio     | # operação                      |
        | [C, A, B] | mesma ordem (regravar sem tocar) |
        | [B, C, A] | reordenar o mesmo conjunto      |

    Cenário: [CT-49] adicionar a página atual não reordena os favoritos existentes
      Dado o dock ligado no /admin e bruno com os favoritos gravados [C, A, B]
      E a página atual é um destino do catálogo ainda não favorito, D
      Quando ele escolhe a opção de adicionar a página atual
      Então os favoritos gravados são exatamente [C, A, B, D], nesta ordem
      E os links do dock renderizado em seguida aparecem na ordem A, B, D, C (o /admin agrupado antes do /infra)
```

> *(alterado em 2026-10-14: Adendo 6, RQ-36)* A exibição no dock passou a agrupar os favoritos por
> painel, com o rótulo do painel como separador. A ordem **gravada** segue a do usuário e continua
> asserida em CT-48 e CT-49; a ordem **exibida** é a gravada, agrupada por painel (app → admin → infra).
> O M95 deixa de contar "agrupa por painel" como implementação errada. Casos novos em
> `tests/Kit/DockDeAtalhosTest.php`: "agrupa os favoritos por painel, com o rótulo do painel e a ordem
> gravada dentro do grupo" e "não mostra separador de painel sem favorito visível".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M95 | dock ordena por rótulo ou pela ordem do catálogo, ou não respeita a ordem gravada dentro do grupo do painel *(alterado em 2026-10-14: Adendo 6 — agrupar por painel passou a ser o comportamento pedido)* | CT-48 | linha [B, C, A]: no grupo /admin, B antes de A; o mutante exibe A antes de B |
| M96 | gravação compara só o conjunto e ignora mudança de ordem ("nada mudou") | CT-48 | linha [B, C, A]: gravados B, C, A; o mutante mantém C, A, B |
| M97 | gravação ordena as chaves (ordem de seleção perdida) | CT-48 | linha [B, C, A]: ordem B, C, A; o mutante grava ordenado |
| M98 | adicionar a página reconstrói a lista na ordem do catálogo | CT-49 | [C, A, B, D]; o mutante reordena |
| M116 | a página adicionada entra no início | CT-49, CT-33 | CT-49: D por último; CT-33: "a página atual por último"; o mutante põe na frente |

---

## Regra R21 — o rótulo de cada destino no slideover traz o painel (e, no `/app`, a organização) antes do grupo e da tela

> `RQ-31` · perfil **completo** (área de autorização: o rótulo é o que impede favoritar o destino homônimo errado) · técnica: **EP** com destinos **homônimos** em painéis diferentes

```gherkin
  Regra: no slideover, cada destino se identifica pelo painel — e pela organização, no /app — antes do grupo e da tela

    Esquema do Cenário: [CT-50] destinos homônimos se distinguem pelo painel e pela organização no rótulo
      Dado o dock ligado no /app, e uma pessoa admin global e admin_app na Acme, com Usar:Dock, em /app/acme
      Quando ela abre o slideover de gerenciar atalhos
      Então o rótulo da opção <destino> contém <prefixo> antes do grupo e do nome da tela
      E os rótulos das duas listas de usuários são diferentes

      Exemplos:
        | destino                      | prefixo                                       | # partição        |
        | lista de usuários do /admin  | o nome do painel /admin                       | painel sem organização |
        | lista de usuários do /app    | o nome do painel /app e, depois dele, "Acme"  | painel de organização  |
```

Nota CT-50: o oráculo é a **posição** dos segmentos (painel < organização < grupo < tela no texto do rótulo), não o separador; os nomes dos painéis e do grupo são os que o kit já exibe (lidos do painel/Resource no arranjo, não escritos à mão no teste).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M99 | rótulo só com grupo e tela | CT-50 | linha /admin: contém o nome do painel; e os dois rótulos diferem; o mutante dá "Usuários" nos dois |
| M100 | organização ausente no rótulo do /app | CT-50 | linha /app: contém "Acme" depois do painel; o mutante omite |
| M101 | painel no fim do rótulo ("Tela — Grupo — Painel") | CT-50 | posição: painel antes do grupo e da tela; o mutante inverte |

---

## Regra R22 — em qualquer slideover, todo favorito gravado que o usuário ainda abre aparece entre os escolhidos e pode ser removido ou reordenado; o envio aceita esses favoritos e o catálogo corrente

> `RQ-35`, `RQ-25`, `RQ-29`, `RQ-27` · perfil **completo** · técnica: **tabela de decisão** onde o slideover abre (`/app/acme` × `/admin`) × chave no envio (já gravada e abrível / não gravada e fora do catálogo corrente) × operação (manter, reordenar, remover, acrescentar) + **estado × operação** do favorito de organização sem acesso (oculto) · a chave **inválida** já gravada reenviada está em R19 (CT-54)

```gherkin
  Regra: o favorito gravado e abrível se gerencia de qualquer slideover; só as novas opções dependem de onde ele abre

    Esquema do Cenário: [CT-52] o envio aceita favorito já gravado e abrível e recusa chave não gravada fora do catálogo corrente
      Dado o usuario de duasOrganizacoes (admin global), com Usar:Dock e o dock ligado no /app e no /admin, com os favoritos gravados <antes>
      E A é a lista de usuários do /admin, PA e PG as listas de projetos da Acme e da Globex, e PG2 outra tela da Globex que ele abre e que não é favorita
      Quando ele envia, pelo slideover aberto em <onde>, <envio>
      Então os favoritos gravados são exatamente <depois>, nesta ordem

      Exemplos:
        | onde      | antes        | envio          | depois       | # célula                                                     |
        | /app/acme | [PA]         | [PA, PG]       | [PA]         | outra organização, não gravada → recusa o envio inteiro      |
        | /app/acme | [PA, PG]     | [PA, PG, PG2]  | [PA, PG]     | uma gravada, outra não → recusa                              |
        | /app/acme | [PA, PG]     | [PG, PA]       | [PG, PA]     | reordenar o já gravado (RQ-35)                               |
        | /app/acme | [PA, PG]     | [PA]           | [PA]         | remover o já gravado (RQ-35)                                 |
        | /admin    | [A]          | [A, PA]        | [A]          | chave do /app não gravada, enviada do /admin → recusa (ADV-03) |
        | /admin    | [A, PA, PG]  | [A]            | [A]          | remover favoritos do /app a partir do /admin (RQ-35)         |
        | /admin    | [A, PA, PG]  | [A, PA, PG]    | [A, PA, PG]  | sem tocar: os do /app continuam (RQ-35)                      |

    Esquema do Cenário: [CT-55] todo favorito gravado e abrível aparece entre os escolhidos, com o rótulo inteiro, em qualquer slideover
      Dado o usuario de duasOrganizacoes (admin global), com Usar:Dock, com os favoritos gravados <gravados>, nesta ordem
      E PA2 e PG2 são telas da Acme e da Globex que ele abre e que não são favoritas
      Quando ele abre o slideover de gerenciar atalhos em <onde>
      Então os escolhidos do slideover são <gravados>, nesta ordem
      E o rótulo de PG traz o nome do painel /app, depois "Globex", antes do grupo e do nome da tela, e não contém "Acme"
      E as novas opções de destino não incluem <fora>

      Exemplos:
        | onde      | gravados      | fora                                  | # célula                                  |
        | /app/acme | [PA, PG]      | PG2 nem outra tela da Globex          | outra organização: escolhida, não oferecida |
        | /admin    | [A, PA, PG]   | PA2, PG2 nem outra tela do /app       | fora do /app: escolhidos do /app, nenhuma opção do /app |

    Cenário: [CT-61] favorito de organização sem acesso não aparece no slideover
      Dado o usuario de duasOrganizacoes, com Usar:Dock e os favoritos gravados [PA, PG], desvinculado da Globex
      Quando ele abre o slideover de gerenciar atalhos em /app/acme
      Então nem os escolhidos nem as novas opções do slideover incluem PG

    Cenário: [CT-62] favorito de organização sem acesso é preservado ao salvar o slideover
      Dado o usuario de duasOrganizacoes, com Usar:Dock e os favoritos gravados [PA, PG], desvinculado da Globex, em /app/acme
      Quando ele salva o slideover com a escolha [PA]
      Então os favoritos gravados são exatamente {PA, PG}
```

Nota CT-52: a linha 1 é a armadilha da rodada 1 do ADV-03 — PG é um destino que o usuário abre, mas não está no catálogo da Acme (RQ-25) nem estava gravado: a recusa é por inteiro (RQ-29). A linha 5 é a reabertura da rodada 2: do `/admin`, nenhuma tela do `/app` é nova opção (RQ-25), então PA não gravado também recusa. As linhas 3, 4, 6 e 7 são o outro lado: a mesma chave, **já gravada e ainda abrível**, é aceita (RQ-35).

Nota CT-61/CT-62: PG desvinculado é favorito **oculto** (RQ-19, RQ-27): fica fora do slideover e sobrevive à gravação, mesmo que o envio não o traga. A posição dele depois da gravação não é afirmada (RQ-27 não a fixa).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M108 | o envio aceita destino de qualquer organização vinculada, gravado ou não — revisão adversarial ADV-03 | CT-52 | linhas 1 e 2: gravados = os do Dado; o mutante grava PG / PG2 |
| M109 | o envio recusa o favorito já gravado que não está no catálogo corrente | CT-52 | linhas 3, 4, 6 e 7: o envio é aceito; o mutante recusa e mantém o Dado |
| M110 | o favorito de outra organização ou de outro painel é sempre preservado ao salvar (não removível dali) | CT-52 | linhas 4 e 6: exatamente [PA] / [A]; o mutante mantém PG / PA e PG |
| M111 | favorito gravado fica fora dos escolhidos quando o slideover abre em outra organização ou em outro painel | CT-55 | escolhidos = [PA, PG] / [A, PA, PG]; o mutante mostra só os do contexto corrente |
| M112 | rótulo do escolhido sem o painel, sem a organização dele, com a organização aberta ou fora de ordem — revisão adversarial ADV-28 | CT-55 | rótulo de PG: painel /app, depois "Globex", antes do grupo e da tela, sem "Acme"; o mutante diverge em um dos segmentos |
| M113 | telas do contexto do favorito escolhido passam a ser oferecidas como novas opções | CT-55 | novas opções sem PG2 (/app/acme) e sem telas do /app (/admin); o mutante as oferece |
| M123 | do /admin, o envio aceita chave do /app não gravada (o catálogo do /admin passa a incluir o /app) — revisão adversarial ADV-03 (rodada 2) | CT-52 | linha 5: gravados = [A]; o mutante grava [A, PA] |
| M124 | o slideover do /admin trata os favoritos do /app como intocáveis e os preserva sempre — revisão adversarial ADV-26 | CT-52 | linha 6: exatamente [A]; o mutante mantém PA e PG |
| M125 | o slideover do /admin descarta os favoritos do /app ao salvar sem tocá-los (só guarda o que é do painel corrente) — revisão adversarial ADV-31 | CT-52 | linha 7: [A, PA, PG]; o mutante grava [A] |
| M126 | favorito de organização sem acesso aparece entre os escolhidos (rótulo de tela que ele não abre exposto) — revisão adversarial ADV-23 | CT-61 | escolhidos e opções sem PG; o mutante inclui |
| M127 | salvar descarta o favorito de organização sem acesso (o "ainda autorizado" do plano) — revisão adversarial ADV-23 | CT-62 | gravados = {PA, PG}; o mutante grava só PA |

---

## Regra R23 — Page de outro painel sem a permissão dela não vale como favorito, mesmo com o mapa de Pages do painel corrente já montado no request

> Origem: `RQ-04`, `RQ-15` (achado RD-01 do step 9) · perfil **completo** · técnica: **matriz papel × tela** com o estado do framework fixado (mapa de permissões das Pages do painel corrente montado antes do dock) + **controle positivo** no mesmo `Dado` (outra Page do mesmo painel, ainda permitida)

```gherkin
  Regra: a permissão de uma Page é conferida no painel dela, não no mapa de Pages do painel em que o dock está

    Cenário: [CT-66] Page do /infra sem a permissão dela some do dock do /admin, com a barra lateral do /admin já renderizada
      Dado o dock ligado no /admin e bruno (admin + infra), com Usar:Dock, com os favoritos "Pulse" (Page do /infra) e "execuções de backup" (outra Page do /infra)
      E a permissão de ver a Page Pulse revogada do papel infra, e a de ver a Page de execuções de backup mantida
      Quando bruno abre a página inicial do /admin por HTTP (a barra lateral do /admin é renderizada antes do dock)
      Então o HTML não contém o link da Page Pulse
      E o HTML contém o link da Page de execuções de backup

    Cenário: [CT-67] o catálogo não oferece a Page do /infra sem permissão, com o mapa de Pages do /admin já montado
      Dado o dock ligado no /admin, bruno (admin + infra) com Usar:Dock e a permissão de ver a Page Pulse revogada do papel infra
      E no arranjo, o /admin bootado e a navegação dele montada antes do slideover, sem reconstruir o mapa de Pages entre painéis
      Quando bruno abre o slideover de gerenciar atalhos no /admin
      Então as opções de destino não incluem a Page Pulse
      E incluem a Page de execuções de backup

    Cenário: [CT-68] a gravação recusa a Page do /infra sem permissão, com o mapa de Pages do /admin já montado
      Dado o dock ligado no /admin, bruno (admin + infra) com Usar:Dock, o favorito X gravado e a permissão de ver a Page Pulse revogada do papel infra
      E no arranjo, o /admin bootado e a navegação dele montada antes do envio, sem reconstruir o mapa de Pages entre painéis
      Quando o cliente envia pelo slideover atalhos = [X, a Page Pulse]
      Então os favoritos gravados de bruno continuam exatamente [X]
```

Nota R23 (`@premissa` de mecanismo): o defeito mora num mapa de permissões de Pages memorizado por painel (ver M58, o caso inverso, ainda sem matador). Por isso CT-66 entra por HTTP — a barra lateral do `/admin` monta o mapa no mesmo request, antes do dock — e CT-67/CT-68 reproduzem essa ordem no arranjo. **Nenhum dos três usa `noPainelDoShield()`** (o helper descarta o mapa e esconderia o defeito). A Page de execuções de backup é o controle positivo: sem ela, "não contém Pulse" passaria com o dock quebrado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M132 | a permissão de Page de outro painel é consultada no mapa de Pages do painel corrente; a Page ausente dele cai em "permitida" (falha aberta) — achado RD-01 | CT-66 | HTML sem o link de Pulse; o mutante o mostra |
| M133 | o catálogo filtra Pages de outros painéis pelo mesmo mapa memorizado do painel corrente | CT-67 | opções sem Pulse; o mutante oferece |
| M134 | a gravação confere a Page de outro painel pelo mapa memorizado do painel corrente | CT-68 | gravados = [X]; o mutante grava [X, Pulse] |

---

## Regra R24 — depois de salvar no slideover, o dock da mesma requisição já mostra os favoritos novos, na ordem nova

> Origem: `RQ-10`, `RQ-30` (achado CR-01 do step 9) · perfil **completo** · técnica: **2-switch** dentro do mesmo componente (grava → re-renderiza sem novo request), com troca de conjunto **e** de ordem no mesmo envio

```gherkin
  Regra: o dock re-renderizado depois da gravação reflete o que foi gravado

    Cenário: [CT-69] o dock re-renderizado na mesma requisição Livewire mostra os favoritos novos na ordem nova
      Dado o dock ligado no /admin e bruno, com Usar:Dock e os favoritos gravados [A, B], com o componente do dock já renderizado mostrando A e B
      Quando ele salva o slideover com [C, A], no mesmo componente, sem novo request
      Então o HTML re-renderizado pelo componente contém os links de C e de A, nessa ordem
      E não contém o link de B
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M135 | a lista de atalhos é memorizada na montagem do componente e não é recalculada depois da gravação — achado CR-01 | CT-69 | HTML com C e A, sem B; o mutante continua mostrando A e B |
| M136 | a lista é recalculada, mas a ordem vem da memória anterior | CT-69 | ordem C, A; o mutante mostra A, C |
| M137 | a lista recalculada é a união da antiga com a nova (o removido continua) | CT-69 | sem B; o mutante mostra C, A, B |

---

## Regra R25 — payload forjado nos métodos públicos tipados do componente não estoura e não grava

> Origem: `RQ-13` (achados CR-05 e RD-02 do step 9; extensão de CT-05) · perfil **completo** · técnica: **EP de tipo errado** em cada método público alcançável por `$wire` (uma partição inválida por linha)

```gherkin
  Regra: o componente do dock recusa, sem erro, chamada pública com argumento forjado

    Esquema do Cenário: [CT-70] chamada pública forjada no componente do dock não estoura e não grava
      Dado bruno, com Usar:Dock e o favorito X gravado, no /admin com o dock ligado
      Quando o cliente chama no componente <chamada>
      Então o componente responde sem exceção
      E os favoritos gravados de bruno continuam exatamente [X]

      Exemplos:
        | chamada                                                                                   | # partição                                       |
        | mountAction('gerenciarAtalhos', 'x')                                                      | argumentos como texto, não lista                 |
        | callMountedAction('x')                                                                    | argumentos como texto, sem action montada        |
        | callMountedAction(['atalhos' => ['https://exemplo.invalido/x']])                          | sem action montada, payload de favoritos        |
        | mountAction('gerenciarAtalhos', ['atalhos' => ['https://exemplo.invalido/x']]) e callMountedAction() | favoritos forjados nos argumentos, não no formulário |
        | mountAction(42)                                                                           | nome da action de tipo errado                    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M138 | argumento tipado recebe texto e estoura `TypeError` (500) — achados CR-05/RD-02 | CT-70 | linhas "texto": sem exceção; o mutante lança |
| M139 | `callMountedAction` sem action montada cai num caminho que grava ou lança | CT-70 | linhas "sem action montada": sem exceção e [X]; o mutante lança ou grava |
| M140 | a action lê os favoritos dos argumentos da chamada em vez do formulário validado | CT-70 | linha "favoritos forjados nos argumentos": [X]; o mutante grava a URL |

---

## Regra R26 — favorito do painel e da organização abertas não recarrega papéis nem resolve a organização por favorito; admite só a consulta da autorização da própria tela

> Origem: `P-04` (achado CR-02 do step 9; substitui a P-01) · perfil **padrão** · técnica: **contagem de consultas** 0 × 3 favoritos, com **atribuição pela pilha** (como CT-35) para o que a P-04 proíbe e **limite superior** (no máximo uma consulta por favorito) para o que ela admite, medidas simétricas e no mesmo aquecimento (como CT-56)

```gherkin
  Regra: cada favorito do contexto corrente custa no máximo a consulta da autorização da própria tela, e nenhuma recarga de papéis nem busca de organização

    Esquema do Cenário: [CT-71] três favoritos do /admin não recarregam papéis e custam no máximo uma consulta cada
      Dado bruno, com Usar:Dock e o dock ligado no /admin, e a página inicial do /admin já aberta uma vez com 0 e uma vez com 3 favoritos do /admin, antes das medidas (aquecimento)
      E a página, aberta de novo com 0 favoritos, fez T consultas ao banco, em qualquer tabela
      Quando bruno abre a mesma página com 3 favoritos do /admin gravados, com as consultas registradas
      Então <afirmacao>

      Exemplos:
        | afirmacao                                                                                                  | # o que a P-04 decide        |
        | nenhuma consulta que seleciona papéis em `model_has_roles` tem, na pilha, código do dock                   | proíbe: recarga de papéis    |
        | a página faz no máximo T + 3 consultas ao banco                                                            | admite: 1 por favorito       |

    Esquema do Cenário: [CT-72] três favoritos da organização aberta não recarregam papéis nem buscam a organização, e custam no máximo uma consulta cada
      Dado o usuario de duasOrganizacoes, com Usar:Dock e o dock ligado no /app, e a página inicial de /app/acme já aberta uma vez com 0 e uma vez com 3 favoritos da Acme, antes das medidas (aquecimento)
      E a página, aberta de novo com 0 favoritos, fez T consultas ao banco, em qualquer tabela
      Quando ele abre a mesma página com 3 favoritos da Acme gravados, com as consultas registradas
      Então <afirmacao>

      Exemplos:
        | afirmacao                                                                                                  | # o que a P-04 decide        |
        | nenhuma consulta que seleciona papéis em `model_has_roles` tem, na pilha, código do dock                   | proíbe: recarga de papéis    |
        | nenhuma consulta a `tenants` tem, na pilha, código do dock                                                 | proíbe: busca da organização |
        | a página faz no máximo T + 3 consultas ao banco                                                            | admite: 1 por favorito       |
```

Nota R26: a P-04 admite exatamente uma consulta por favorito do contexto corrente — a da autorização da tela (`Gate::before` → `User::isMasterGlobal()`), a mesma de cada item da barra lateral. Por isso o total não é mais "exatamente T": o oráculo é o **limite** T + 3. O que a P-04 proíbe é afirmado à parte, pela pilha (mecanismo de CT-35, `@premissa` decidida em Q20). Cada linha do `Exemplos` mede o mesmo request; a separação em linhas existe só para cada asserção ter o seu matador. P-04 cobre só favoritos do painel **e** da organização abertas; favorito de outro painel ou organização pode exigir troca de contexto (R10). O `phpunit.xml` usa `CACHE_STORE=array` (ver M130).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M141 | os papéis do usuário são recarregados a cada favorito para autorizar o destino — achado CR-02 | CT-71, CT-72 | linha "recarga de papéis": nenhuma consulta de papéis com frame do dock; o mutante faz 3 |
| M142 | a organização da chave é buscada no banco a cada favorito (`find` da organização) | CT-72 | linha "busca da organização": nenhuma consulta a `tenants` com frame do dock; o mutante faz 3 |
| M149 | cada favorito custa mais de uma consulta admitida (por exemplo, a do `Gate` mais uma leitura da permissão da tela fora do cache) | CT-71, CT-72 | linha "1 por favorito": no máximo T + 3; o mutante faz T + 6 |

---

## Regra R27 — favorito de organização excluída não aparece no slideover e sai quando o usuário salva

> Origem: `P-02` (achado CR-03 do step 9) · perfil **completo** · técnica: **estado × operação** (estado "organização excluída", contraste do oculto de R22) + **BVA** do teto depois do descarte · **direção derivada pela ➡️ da Q28, aberta, por instrução da sessão** (descartar falha fechado contra o teto travado)

```gherkin
  Regra: o favorito de organização que não existe mais não é oferecido nem escolhido, e sai da gravação

    Cenário: [CT-73] favorito de organização excluída não aparece no slideover
      Dado o usuario de duasOrganizacoes, com Usar:Dock, com os favoritos gravados [PA, PX], em que PX é de uma organização excluída depois de gravado
      Quando ele abre o slideover de gerenciar atalhos em /app/acme
      Então nem os escolhidos nem as novas opções do slideover incluem PX
      E o slideover abre sem exceção

    Esquema do Cenário: [CT-74] favorito de organização excluída sai quando o slideover é salvo e deixa de ocupar vaga
      Dado o usuario de duasOrganizacoes, com Usar:Dock, em /app/acme, com os favoritos gravados <antes>, em que PX é de uma organização excluída depois de gravado
      Quando ele salva o slideover com <envio>
      Então os favoritos gravados são exatamente <depois>
      E <aviso>

      Exemplos:
        | antes                        | envio                         | depois                        | aviso                            | # célula                          |
        | [PA, PX]                     | [PA]                          | [PA]                          | nenhum aviso de limite é exibido | descarte ao salvar               |
        | 9 destinos da Acme e PX (10) | 10 destinos da Acme visíveis  | exatamente os 10 enviados     | nenhum aviso de limite é exibido | 9 + 1 excluída → 10, sem vaga presa |
```

Nota R27: CT-73 é o **invariante das duas leituras** da Q28 (um favorito cujo destino não existe não pode aparecer). CT-74 é a **direção** recomendada (descartar), derivada antes da resposta por instrução da sessão — divergência declarada com a skill, que manteria o recorte sem cenário até a resposta. Se a Q28 for respondida "manter", CT-74 vira `@obsoleto` e a linha "teto" troca de oráculo. A organização excluída não é estado **oculto** (RQ-27 preserva o oculto, porque ele pode voltar; a organização excluída não volta).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M143 | o favorito de organização excluída entra nos escolhidos, ou o slideover estoura ao montar o rótulo dele | CT-73 | escolhidos sem PX e abertura sem exceção; o mutante inclui ou lança |
| M144 | o favorito de organização excluída é tratado como oculto e preservado ao salvar — achado CR-03 | CT-74 | linha "descarte": exatamente [PA]; o mutante mantém PX |
| M145 | o teto do slideover conta o favorito de organização excluída que a gravação vai descartar | CT-74 | linha "teto": os 10 enviados, sem aviso; o mutante recusa com aviso |

---

## Regra R28 — "adicionar esta página" que não pode mais ser feito avisa e não muda nada

> Origem: `P-03` (achado RD-03 do step 9), `RQ-21`, `RQ-28` · perfil **completo** · técnica: **EP** do motivo pelo qual a adição deixou de ser possível depois da renderização (corrida entre abas) + **rastreio de não-efeito**

```gherkin
  Regra: a opção clicada que já não pode adicionar a página mostra um aviso, e os favoritos ficam como estavam

    Esquema do Cenário: [CT-75] clicar em "adicionar esta página" quando ela já não pode ser adicionada avisa e não grava
      Dado bruno, com Usar:Dock, no /admin ligado, com o componente do dock renderizado na lista de usuários do /admin e a opção de adicionar visível
      E, depois dessa renderização, <mudanca>
      Quando ele clica na opção de adicionar a página atual
      Então uma notificação de aviso é exibida
      E os favoritos gravados de bruno continuam exatamente <gravados>

      Exemplos:
        | mudanca                                                                 | gravados              | # partição                          |
        | a lista de usuários foi favoritada em outra aba (gravados [B, A])       | [B, A]                | já é favorita (duas abas)           |
        | a permissão de listar usuários foi revogada do papel admin (gravados [B]) | [B]                 | o destino deixou de ser abrível     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M146 | a action reconfere e sai calada, sem aviso — achado RD-03 | CT-75 | as duas linhas: notificação de aviso; o mutante não notifica |
| M147 | a action confia na visibilidade calculada na renderização e grava sem reconferir o destino | CT-75 | linha "deixou de ser abrível": [B]; o mutante grava [B, A] |
| M148 | o aviso é exibido, mas a gravação acontece assim mesmo (duplicata ou destino sem acesso) | CT-75 | linha "duas abas": exatamente [B, A]; o mutante grava [B, A, A] |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-13 (usuário × usuário), CT-28 (organização), CT-43 e CT-52 linhas "não gravado" (organização vinculada, mas não a aberta, e chave do /app enviada do /admin), CT-61 (organização sem acesso), CT-17 linha "destino que ana não pode abrir" | G1, G4 |
| Autorização exercida na ação (não só `can()`) | CT-03 (as duas actions), CT-05, CT-39 (action escondida chamada direto em tela de registro), CT-59 (concessão real pela tela de papéis), CT-68 (Page de outro painel na gravação), CT-75 (reconferência no clique) | G1, G2 |
| Idempotência (ancorada no agregado) | CT-75 linha "duas abas" (adição repetida por corrida entre abas), CT-16 linha "mesma lista" e CT-40 (a mesma página adicionada de novo; chave repetida no envio), ambos sobre os favoritos gravados; CT-64 (o mesmo Resource em outra organização **não** é repetição) | G1, G4 |
| Cache em store de banco (RQ-22) | lacuna declarada (pilha): `CACHE_STORE=array` no `phpunit.xml` torna a leitura de cache invisível à contagem — M130; tentativa prevista em R13 | G2 |
| Concorrência | lacuna declarada: duas adições simultâneas no 9º favorito; SQLite `:memory:` usa uma conexão só, e dois componentes no mesmo teste compartilham o mesmo model autenticado (`actingAs`) — o arnês não produz leitura defasada; tentado: dois `Livewire::test()` montados antes da primeira chamada | G1 |
| Fronteira no ponto de entrada (gravação) | CT-33, CT-34 (teto nas duas entradas), CT-47 (teto com oculto e com inválida), CT-09, CT-08 linha [] e CT-57 (settings) | G1, G3 |
| Domínio condicionado | CT-28 (a parte "organização" da chave só vale no /app: sem organização e organização inexistente), CT-43 (tela do /app só da organização aberta) | G4 |
| Estado × operação de escrita | matriz do favorito (16 células, 0 lacunas): CT-15, CT-16, CT-18, CT-23, CT-24, CT-26, CT-27, CT-33, CT-34, CT-44, CT-45, CT-46, CT-47, CT-52, CT-53, CT-58, CT-60, CT-61, CT-62, CT-73, CT-74 | G1, G2, G4, G5 |
| Ausente ≠ null ≠ vazio | CT-14 (favoritos nulos × []), CT-10 (layout nulo → settings) | G1 |
| Paginação / ordenação | paginação: não se aplica (o dock não pagina) · ordenação: CT-48, CT-49 (a ordem é a do usuário — RQ-30; o exemplo foge da ordem alfabética, da do catálogo e da por painel) | G1 |
| Timezone / DST | não se aplica: nenhum dado temporal no requisito | — |
| Unicode / limite de varchar | não se aplica: não há texto livre — favorito é escolha do catálogo (RQ-11) e layout é enum; chave arbitrária coberta em CT-17 | — |
| Unicidade + soft delete | não se aplica: `Tenant` não usa `SoftDeletes` (só `App\Models\User` declara — `grep -n SoftDeletes app/Models/Tenant.php` vazio); organização excluída é exclusão física, coberta em CT-29 | — |
| CRUD combinado | CT-16 (troca, esvaziar, regravar sem alterar) | G1 |
| Mass assignment | CT-19 (`users`), CT-12 linha `estilo` (payload do slideover) | G2, G1 |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| Superfície Livewire (método público, prop pública, estado do framework) | props `#[Locked]`: CT-32 · `mountAction`/`callMountedAction` fora da lista e tipo errado: CT-05, CT-70 (argumentos tipados forjados) · re-render depois de gravar: CT-69 · `atalho_{n}`: CT-05 linha `atalho_99` · `adicionarPaginaAtual`: CT-30, CT-31, CT-33, CT-39, CT-40, CT-51 · campo `atalhos`: CT-17 (fora do domínio + tipo errado), CT-52, CT-54 (chaves já gravadas reenviadas) · campo `layout`: CT-12 · campos da tela de configurações: CT-09 | G1, G3 |
| Estado do framework usado sem validar | CT-32 (`routeName`/`routeParameters`/`path`), CT-17 (chave como índice e nome de classe), CT-66…CT-68 (mapa de permissões de Pages memorizado do painel corrente) | G1, G2 |
| IDOR por entidade (uma linha por tabela) | `users` (colunas do dock): CT-13, CT-28, CT-43, CT-52 · `settings` (3 propriedades): permissão da tela já coberta em `tests/Kit/ConfiguracoesDoKitTelaTest.php` ("recusa a tela de configurações a quem não tem a permissão", linha 183) · `permissions`: não se aplica — só o seeder escreve | G1, G4, G3 |
| Escopo com discriminante nulo | CT-26 linha "/admin (sem organização)", CT-28 linha "/app sem organização", CT-43 linha "aberto no /admin" e CT-58 (vínculo conferido sem organização aberta) — fecha (não abre para todas) | G4, G5 |
| Saída do estado de erro | não se aplica: nenhum cenário termina em 4xx ou redirect; as recusas (CT-09, e o teto em CT-33/CT-34 com aviso) mantêm a pessoa na tela, com os favoritos intactos | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | dock renderiza só com permissão e painel ligado | R1 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M3, M4, M104 |
| CT-02 | página entrega o dock só a quem tem a permissão, no painel ligado | R1 | matriz papel × ação | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M7, M104, M105 |
| CT-03 | sem permissão, action direta não grava | R1 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M1, M2 |
| CT-04 | permissão vale na organização em que foi concedida | R1 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M5 |
| CT-05 | action fora da lista não executa | R1 | EP superfície Livewire | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M6 |
| CT-06 | fábrica não liga painel nenhum | R2 | EP | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M9 |
| CT-07 | lista gravada decide o painel | R2 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M8, M10, M11, M12 |
| CT-08 | administrador grava painéis (inclusive nenhum), layout e estilo | R3 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M16, M17, M119 |
| CT-09 | fora do domínio recusado no campo | R3 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M13, M14, M15, M17 |
| CT-10 | layout do usuário, estilo do settings | R4 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M18, M19 |
| CT-11 | todo layout do pacote escolhível | R4 | EP exaustiva | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M21 |
| CT-12 | slideover recusa layout inválido e estilo | R4 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M20, M22 |
| CT-13 | favorito de outro usuário não aparece | R5 | IDOR | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M23, M24 |
| CT-14 | dock com 0 a 10 favoritos | R5 | cardinalidade | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M25, M26, M27 |
| CT-15 | grava dois destinos pelo slideover, na ordem | R6 | EP (válida) | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | — (gate de tela de escrita; controle positivo de CT-17) |
| CT-16 | troca, esvazia, mantém | R6 | criação ≠ edição | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M34 |
| CT-17 | envio com chave fora do catálogo recusado por inteiro | R6 | EP inválidas | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M28, M29, M30, M31, M102 |
| CT-18 | chave inválida gravada não aparece | R6 | uso | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M32 |
| CT-19 | sem atribuição em massa | R6 | mass assignment | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M33 |
| CT-20 | opções seguem o acesso | R7 | matriz papel × tela | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M35, M36, M37 |
| CT-21 | painel novo entra no catálogo | R7 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M38 |
| CT-22 | catálogo na organização: papel global e vínculo | R7 | matriz papel × tela | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M39, M40 |
| CT-23 | favorito do /admin no dock do /app | R8 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M41, M42, M43 |
| CT-24 | sem acesso, some e continua gravado | R8 | estado × operação | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M44, M45, M46 |
| CT-25 | oculto volta com a permissão | R8 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M46, M47 |
| CT-26 | favorito da Globex abre na Globex | R9 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M48, M49 |
| CT-27 | sem acesso à organização, some e volta | R9 | tabela de decisão + 2-switch | G5 | Pest feature HTTP | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M50, M51, M52 |
| CT-28 | slideover não grava organização sem vínculo | R9 | IDOR | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M53 |
| CT-29 | contexto do request sobrevive | R10 | EP | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M54, M55, M56, M57 |
| CT-30 | página atual do catálogo vira favorito | R11 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M59, M61 |
| CT-31 | página sem acesso não vira favorito | R11 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M60 |
| CT-32 | cliente não troca a página atual | R11 | superfície Livewire | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M62 |
| CT-33 | teto na adição da página: recusa com aviso | R12 | BVA 3-valores | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M63, M64, M65, M66, M74, M75, M116 |
| CT-34 | teto no slideover: recusa com aviso | R12 | BVA 3-valores | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M63, M64, M66, M73, M75 |
| CT-35 | nenhuma consulta à settings com frame do dock | R13 | contagem | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M67, M68, M69 |
| CT-36 | ligar o dock não acrescenta consulta | R13 | contagem | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M67, M70 |
| CT-37 | permissão existe e é concedível | R14 | EP | G6 | Pest feature HTTP | `tests/Kit/PermissoesDeAcoesTest.php` | M71, M72 |
| CT-38 | opção de adicionar só em destino do catálogo não favorito | R15 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M76, M78, M80 |
| CT-39 | opção escondida chamada direto não grava | R15 | autorização na ação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M77, M79, M80 |
| CT-40 | mesmo destino nunca gravado duas vezes | R16 | EP + idempotência | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M81, M82, M83 |
| CT-41 | semeadura single-tenant: admin e infra sim, panel_user não | R17 | matriz papel × permissão | G6 | Pest feature HTTP | `tests/Kit/PermissoesDeAcoesTest.php` | M84, M85 |
| CT-42 | semeadura com tenancy: admin_app, admin e infra sim, panel_user não | R17 | matriz papel × permissão | G8 | Pest feature HTTP | `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | M84, M86, M120 |
| CT-43 | telas do /app só da organização aberta e só dentro do /app | R18 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M87, M88 |
| CT-44 | favorito de outra organização preservado e no teto | R18 | estado × operação | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M89, M103 |
| CT-45 | oculto não é oferecido no slideover | R19 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M90 |
| CT-46 | chave que nunca foi destino descartada ao salvar | R19 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M92 |
| CT-47 | oculto e inválida ocupam vaga no teto; a inválida sai ao salvar | R19 | BVA com oculto e inválida | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M93, M94, M115, M129 |
| CT-48 | ordem escolhida e reordenação | R20 | EP + 2-switch | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M95, M96, M97 |
| CT-49 | página adicionada entra no fim, sem reordenar os demais | R20 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M98, M116 |
| CT-50 | rótulo com painel e organização antes do grupo e da tela | R21 | EP (homônimos) | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M99, M100, M101 |
| CT-51 | "adicionar esta página" grava a organização da rota | R9 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M106 |
| CT-52 | envio aceita favorito já gravado e abrível e recusa chave não gravada fora do catálogo corrente, no /app e no /admin | R22 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M108, M109, M110, M123, M124, M125 |
| CT-53 | favorito visível de outro painel sai quando removido no slideover | R19 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M114 |
| CT-54 | reenviar a chave inválida já gravada recusa o envio | R19 | EP | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M121 |
| CT-55 | favoritos gravados entre os escolhidos em qualquer slideover, com rótulo inteiro | R22 | EP | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M111, M112, M113 |
| CT-56 | ligar o dock não acrescenta consulta nenhuma ao banco | R13 | contagem | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M117 |
| CT-57 | todo layout e todo estilo do pacote gravam na tela de configurações | R3 | EP exaustiva | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M118 |
| CT-58 | no /admin, favorito de organização desvinculada some e continua gravado | R9 | tabela de decisão | G5 | Pest feature HTTP | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M107 |
| CT-59 | conceder o dock pela tela de papéis faz o dock aparecer | R14 | efeito da concessão | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M122 |
| CT-60 | oculto sobrevive ao salvar o slideover | R19 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M91 |
| CT-61 | favorito de organização sem acesso fora dos escolhidos e das opções | R22 | estado × operação | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M126 |
| CT-62 | favorito de organização sem acesso preservado ao salvar | R22 | estado × operação | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M127 |
| CT-63 | mesma tela em outra organização oferece "adicionar esta página" | R16 | EP | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M128 |
| CT-64 | mesma tela em outra organização é gravada como favorito novo | R16 | EP | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M128 |
| CT-65 | dock desligado consulta a settings tanto quanto um painel sem o plugin | R13 | contagem | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M131 |
| CT-66 | Page do /infra sem permissão some do dock do /admin com a barra lateral já renderizada | R23 | matriz papel × tela | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M132 |
| CT-67 | catálogo não oferece Page do /infra sem permissão com o mapa do /admin montado | R23 | matriz papel × tela | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M133 |
| CT-68 | gravação recusa Page do /infra sem permissão com o mapa do /admin montado | R23 | matriz papel × tela | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M134 |
| CT-69 | dock re-renderizado na mesma requisição mostra os favoritos novos na ordem nova | R24 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M135, M136, M137 |
| CT-70 | chamada pública forjada no componente não estoura e não grava | R25 | EP tipo errado | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M138, M139, M140 |
| CT-71 | três favoritos do /admin não recarregam papéis e custam no máximo uma consulta cada | R26 | contagem com atribuição + limite | G2 | Pest feature HTTP | `tests/Kit/DockDeAtalhosTest.php` | M141, M149 |
| CT-72 | três favoritos da organização aberta não recarregam papéis nem buscam a organização, e custam no máximo uma consulta cada | R26 | contagem com atribuição + limite | G5 | Pest feature HTTP | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M141, M142, M149 |
| CT-73 | favorito de organização excluída não aparece no slideover | R27 | estado × operação | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M143 |
| CT-74 | favorito de organização excluída sai ao salvar e deixa de ocupar vaga | R27 | estado × operação + BVA | G4 | componente Livewire/Filament | `tests/Tenancy/DockDeAtalhosTenancyTest.php` | M144, M145 |
| CT-75 | "adicionar esta página" que não pode mais ser feito avisa e não grava | R28 | EP + não-efeito | G1 | componente Livewire/Filament | `tests/Kit/DockDeAtalhosTest.php` | M146, M147, M148 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| chamada de action do dock num painel com o dock **desligado** | RQ-14 fala de aparecer, não de aceitar chamada; a gravação continua restrita ao catálogo (CT-17) e à permissão (CT-03) — sem mutante de requisito que só ele mate |
| 11 favoritos gravados por fora da aplicação: o dock mostra quantos? | nenhuma entrada produz esse estado (RQ-26 recusa nas duas); exibir já é coberto por CT-14 até 10 |
| CT-B do aviso de limite | a notificação é provada por `assertNotified()` no componente (CT-33, CT-34) — mesmo conjunto de mutantes |
| semeadura de `master_global` | passa por `Gate::before` (controle de CT-01), não depende do seeder |
| CT-B do slideover abrindo no navegador | modal do Filament core; a gravação é provada por componente (CT-15) — mesmo conjunto de mutantes |
| CT-B de cada layout (visual) | a marca do layout no HTML é provada por componente (CT-10, CT-11); pixel do vendor não é cláusula |
| atalho de teclado `mod+.` | sem `RQ` |
| repetir CT-24 por componente | o HTTP prova mais (página real) e mata o mesmo conjunto (M44–M46) |

## Revisão Adversarial

### Rodada 1

`fw-adversario-ct`, contexto novo, sem o plano: **21 achados** — 20 fechados (CT novo ou oráculo reescrito) e 1 rejeitado (ADV-07), rejeição revogada na rodada 2.

| ADV | RQ | Destino | Onde |
|---|---|---|---|
| ADV-01 | RQ-14 | oráculo reescrito: no painel desligado, componente, gatilho e gerenciar ausentes, não só o `href` | CT-01 (coluna `gerenciar`), CT-02 (linha "desligado") — M104 |
| ADV-02 | RQ-16 × RQ-21 | CT novo: a página adicionada guarda a organização da rota | CT-51 — M106 |
| ADV-03 | RQ-25 × RQ-29 (× RQ-35) | CT novo: envio na Acme com destino da Globex não gravado → recusa; o já gravado é aceito | CT-52 — M108 |
| ADV-04 | RQ-15, RQ-20, RQ-27 | CT novo: o visível de outro painel sai quando não é escolhido ("exatamente [P]") | CT-53 — M114 |
| ADV-05 | RQ-29, RQ-27 | CT novo: reenviar a inválida já gravada recusa o envio, nada muda | CT-54 — M121 |
| ADV-06 | RQ-22 | CT novo, com recorte: total de consultas (qualquer tabela) ligado × desligado **sem favoritos**. Com favoritos, a autorização de cada destino é decisão de RQ-04/RQ-15/RQ-16, não de "se o dock aparece nem como" | CT-56 — M117 |
| ADV-07 | RQ-22 | rejeitado na rodada 1 (CT-35 parecia bastar); **rejeição revogada na rodada 2**: a leitura feita numa closure do PanelProvider não tem código do dock na pilha | ver rodada 2 |
| ADV-08 | RQ-08, RQ-17 | CT novo: EP exaustiva de layout e estilo na tela de configurações | CT-57 — M118 |
| ADV-09 | RQ-14 | oráculo ampliado: desmarcar todos os painéis grava [] sem erro | CT-08 linha [] — M119 |
| ADV-10 | RQ-24 | linhas novas: `admin` e `infra` têm na semeadura com tenancy | CT-42 — M120 |
| ADV-11 | RQ-16, RQ-19 | CT novo: no /admin, favorito da Globex some depois do desvínculo e continua gravado | CT-58 — M107 |
| ADV-12 | RQ-13 | CT novo: conceder pela tela de papéis faz o dock aparecer | CT-59 — M122 |
| ADV-13 | RQ-19 | oráculo reescrito: 2-switch com renderização intermediária observada (sem U → com U); M46 e M47 reatribuídos | CT-25 |
| ADV-14 | RQ-15, RQ-16 | persona e permissão reescritas: K vale no contexto global e no da Globex, não na Acme; dentro de `/app/{org}` o papel global não vale | CT-29; CT-22 linha 1 reescrita (destino do /admin avaliado no contexto global) |
| ADV-15 | RQ-15, RQ-16, RQ-19 | partição nova: a avaliação lança **depois** da troca de contexto | CT-29 linha "lança depois da troca" — M57 |
| ADV-16 | RQ-16, RQ-19 | oráculo reescrito: 2-switch com abertura intermediária sem vínculo observada; M52 reatribuído | CT-27 linha 2-switch |
| ADV-17 | RQ-33 | oráculo: a página adicionada fica **por último** | CT-33 (linhas borda−1 e borda), CT-49 — M116 |
| ADV-18 | RQ-27 | oráculo: "exatamente {U, P, Q}" | CT-60 |
| ADV-19 | — (escrita) | CT-45 tinha dois `Quando`: dividido em CT-45 (abrir o slideover) e CT-60 (salvar) | CT-45, CT-60 |
| ADV-20 | RQ-13, RQ-24 | linha nova: na suíte Kit os papéis se somam; `Usar:Dock` só no `admin` (semeadura real) → contém no /app | CT-02 linha bruno — M105 |
| ADV-21 | RQ-15, RQ-16 | mesma causa de ADV-14: o oráculo de CT-29 dependia de o papel global valer dentro da organização | CT-29 |

### Rodada 2 (última — teto de 2 rodadas)

`fw-adversario-ct`, contexto novo, sem o plano. Os fechamentos da rodada 1 foram conferidos. Três foram reabertos — ADV-03 e ADV-06 por fechamento parcial, ADV-07 com a rejeição revogada pela sessão —, e vieram **10 achados novos** (ADV-22…ADV-31). Nesta rodada não houve rejeição. A pergunta Q24, que a rodada levantou, virou o Adendo 5 (RQ-35 substitui RQ-34). Os cenários novos desta rodada **não** passam por uma rodada 3.

| ADV | RQ | Destino | Onde |
|---|---|---|---|
| ADV-03 (reaberto) | RQ-25, RQ-29 | linha nova: do `/admin`, chave do `/app` não gravada → recusa por inteiro | CT-52 linha 5 — M123 |
| ADV-06 (reaberto) | RQ-22 | **lacuna declarada de pilha**: `CACHE_STORE=array` torna inobservável o mutante "cache em store `database`"; tentativa prevista para o executor registrada no mutante | M130 (sem matador) |
| ADV-07 (rejeição revogada) | RQ-22 | CT novo: painel sem o plugin × painel com o dock desligado, mesmo número de consultas à settings — mata a leitura numa closure do PanelProvider | CT-65 — M131 |
| ADV-22 | RQ-22 | oráculo reescrito: as duas medidas no mesmo estado de aquecimento | CT-36, CT-56 (e CT-65) |
| ADV-23 | RQ-27, RQ-35 | CTs novos: favorito de organização sem acesso fora dos escolhidos e das opções, e preservado ao salvar | CT-61, CT-62 — M126, M127 |
| ADV-24 | RQ-16, RQ-28 | CTs novos: a mesma tela em outra organização oferece "adicionar esta página" e é gravada como favorito novo | CT-63, CT-64 — M128 |
| ADV-25 | RQ-27, RQ-32 | linha nova: 9 visíveis + 1 inválida, enviar 10 visíveis → os 10, sem aviso | CT-47 — M129 |
| ADV-26 | RQ-35 | decidido pelo Adendo 5: R22 reescrita; do `/admin`, os favoritos do `/app` estão entre os escolhidos e saem quando não são escolhidos | R22, CT-52 linha 6, CT-55 linha `/admin`, matriz — M124 |
| ADV-27 | RQ-08, RQ-17 | oráculo reescrito: o `Dado` parte de um caso ≠ do da linha, e a tela não mostra erro | CT-57 |
| ADV-28 | RQ-31, RQ-35 | oráculo reescrito: o rótulo inteiro (painel /app, depois "Globex", antes do grupo e da tela, sem "Acme") | CT-55 — M112 |
| ADV-29 | — (costura) | CT-59 movido para G2 (o `Então` é o HTML da página) | CT-59 |
| ADV-30 | — (escrita) | CT-25 com a restauração no `Dado`, depois da renderização intermediária; um único `Quando` | CT-25 |
| ADV-31 | RQ-35 | mesmo eixo do ADV-26: salvar no `/admin` sem tocar nos favoritos do `/app` os preserva | CT-52 linha 7 — M125 |

## Achados da revisão do diff (step 9)

| Achado | Origem do CT | Destino |
|---|---|---|
| RD-01 (`fw-revisor-diff`) | RQ-04, RQ-15 | R23 — CT-66, CT-67, CT-68 (M132…M134); vizinho de M58, que segue sem matador (o caso inverso: memo do painel avaliado depois da troca) |
| CR-01 (`/code-review`) | RQ-10, RQ-30 | R24 — CT-69 (M135…M137) |
| CR-05 (`/code-review`), RD-02 (`fw-revisor-diff`) | RQ-13 | R25 — CT-70 (M138…M140), extensão de CT-05 |
| CR-02 (`/code-review`) | P-04 (substitui P-01) | R26 — CT-71, CT-72 (M141, M142, M149) |
| CR-03 (`/code-review`) | P-02 | R27 — CT-73 (invariante), CT-74 (direção pela ➡️ da Q28, aberta) (M143…M145) |
| RD-03 (`fw-revisor-diff`) | P-03 | R28 — CT-75 (M146…M148) |

## CT-B

Ver `05-casos-de-teste-browser.md` (costura G7).
