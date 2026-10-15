# Casos de Teste — fix/pilha-de-botoes-flutuantes: Pilha harmônica dos botões flutuantes (dock × "Voltar ao topo" × chat da IA)

> Requisito: `00-requisito.md` (com os Adendos 1 a 8 e a P-03) · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação desta feature. Do `01` entraram só paths, stack e `## Superfície de UI`; do `02`, só `## Superfície Livewire`. Os testes existentes de outras features entraram só para nomear helpers e seletores.
>
> **Revisão adversarial — ciclo 1 executado** (`fw-adversario-ct`, contexto novo, só com o `00` e o `04`/`05`). Disparo: Impacto 3 na área "Telas sem dock nem chat" (RQ-23). Foram 26 achados (ADV-01…ADV-26).
>
> **Aceitos e fechados nesta versão:**
>
> | Achado | Como foi fechado |
> |---|---|
> | ADV-01/15 | título sentinela de conversa no CT-04 e no CT-09, com controle positivo |
> | ADV-02 | CT-09: bloquear → destravar → dock e chat de volta |
> | ADV-03 | CT-10: bloqueio na suíte multi-organização |
> | ADV-05 | CT-B08 linha c: `/admin`, dock solto sobre o voltar |
> | ADV-06 | CT-B08 linha d: BVA na fronteira real |
> | ADV-07 | CT-05 linha f: Acme, Globex, Acme intercalados |
> | ADV-08 | CT-07: favorito de Page avulsa |
> | ADV-09 | CT-08 também afirma o tooltip sem multi-tenant |
> | ADV-10/19/21 | CT-03 afirma, pela folha, os 30 px do voltar |
> | ADV-11/12/22 | CT-04 linhas condicionais de telas de login e verificação de e-mail com sessão |
> | ADV-13 | CT-11: round-trip Livewire do próprio dock |
> | ADV-16 | controle positivo com uma asserção por agulha |
> | ADV-17 | marcador `data-voltar-ao-topo` em cada linha do CT-04 |
> | ADV-18 | CT-B06: `elementFromPoint` no item do leque |
> | ADV-20 | CT-B03 com abrir e "Nova conversa" no `Dado` |
> | ADV-26 | CT-05 linhas d/e: nenhuma URL nem tooltip da Globex |
>
> Mutantes novos: M73–M86, de origem adversarial; não contam para o teto da regra (passo 6, item 1).
>
> **Recusados:**
> - **ADV-04**: o kit não tem modo SPA; `grep -rn "spa(" app/Providers` vem vazio, como já está na varredura T.
> - **ADV-14**: a feature não acrescenta middleware nem provider global; código global é revisado no diff do step 9; o CT-06 fica restrito ao código do dock.
> - **ADV-25**: permanece lacuna declarada, conforme o roteamento da sessão.
>
> **ADV-23 e ADV-24** (RQ sem cenário discriminante): fechados pelos mesmos cenários dos achados que os originaram — ADV-23 pela linha f do CT-05 (ADV-07) e ADV-24 pelas linhas c e d do CT-B08 (ADV-05, ADV-06). Roteamento da sessão, 2026-10-14.
>
> **Revisão adversarial — ciclo 2 executado; teto de 2 rodadas atingido; achados incorporados sem nova rodada.** Foram 9 achados (ADV-27…ADV-35), todos aceitos pela sessão:
>
> | Achado | Como foi fechado |
> |---|---|
> | ADV-27 | CT-07: linha "Dashboard da Globex" (Page avulsa da outra organização) |
> | ADV-28 | CT-B08 linha e: o lado que cruza da fronteira real |
> | ADV-29 | CT-B08 linha f: cruzamento pelo eixo x, sem encostar na borda |
> | ADV-30 | CT-B06 linhas 1 e 3: Tab a partir do leque não põe o foco no chat nem no voltar |
> | ADV-31 | CT-03: seletor exato da regra dos 30 px, fora de `@media`, sem regra posterior que a desfaça, e casando com o botão servido |
> | ADV-32 | CT-B06: `elementFromPoint` no centro da **interseção** entre o item e o antigo retângulo |
> | ADV-33 | CT-05: todo grupo, inclusive "Atalhos", comparado como conjunto exato; nenhuma chave da Globex nas linhas d/e |
> | ADV-34 | CT-07: atributo de tooltip lido no próprio elemento do favorito, decodificado, igual ao caminho |
> | ADV-35 | válvulas do CT-04 e do CT-B06 com destino afirmado e recontagem de "sem matador" |
>
> Mutantes novos: M87–M95, de origem adversarial, fora do teto da regra.
>
> **Divergência em ADV-35 — confirmada pela sessão em 2026-10-15.** Num redirecionamento para página comum do painel (saída do fluxo de autenticação; por exemplo, `/admin/login` com sessão → `/admin`), dock e chat são o comportamento esperado no destino. A asserção de ausência no destino vale só quando o destino é tela das RQ-19 a RQ-22; nos demais casos, a linha registra o destino.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Pilha: posição, ordem, eixo, espaço e reação ao dock (RQ-01, 03, 04, 06, 07, 08, 12, 13, 15, 16, 18, P-02, P-03) | 3 | 2 | 6 | padrão |
| Dock aberto oculta e fechado restaura (RQ-09, RQ-14) | 2 | 2 | 4 | padrão |
| Chat estilizado: botão e slide-over (RQ-10, RQ-11, RQ-17) | 2 | 2 | 4 | padrão |
| Guarda estática da folha do chat e posição sem JS (RQ-10, RQ-11, P-03, Fora de Escopo) | 2 | 2 | 4 | padrão |
| Telas sem dock nem chat: bloqueio, login, Auth Designer (RQ-19 a RQ-23) | 2 | 3 | 6 | padrão |
| Dock por organização: agrupamento, ordem, custo e tooltip (RQ-24 a RQ-27) | 2 | 2 | 4 | padrão |

- **P = 3 na pilha**: integra com um componente de terceiro e com um componente Livewire, reage a estado do navegador e depende de tempo.
- **I = 3 nas telas sem dock nem chat**: numa sessão bloqueada, o chat exporia os títulos das conversas do usuário a quem está diante da tela.
- **I = 2 no dock por organização**: rótulo errado leva a agir na organização errada. O vazamento de organização sem acesso é a regra R22.
- **Técnicas**: EP; tabela de decisão; 2-switch; rastreio de não-efeito; BVA de 2 valores com linhas nos dois lados da fronteira real e no eixo x; valor literal (16 px, 30 px, 4,5:1, separador " · "); controle positivo nas asserções de ausência, com marcador de render hook por tela.
- **Contagem**: Cenários: 11 (+11 CT-B no `05`) · Regras: 22 · Mutantes previstos: 95 · Sem matador: 0
<!-- contagem pelos 4 grep -c do template-04 §Contagem; o 1º grep só casa [CT-nn]; os CT-B estão no 05 -->
- **Recontagem pelas válvulas (ADV-35)**: se uma linha do CT-04 cair em "sem marcador" ou uma linha do CT-B06 em "nenhum item cruza", o executor reporta. Os mutantes que só aquela linha matava passam a "sem matador", e a contagem acima é refeita pelos `grep -c`.

**Teto estourado, com justificativa.** O perfil padrão prevê 1 CT-B, e este conjunto tem 11. Tudo o que a pilha afirma é layout (`01` §Superfície de UI), e cada CT-B é o único matador dos mutantes que lista; o gate vence o teto (passo 6, item 5). Os Adendos 4 a 8 não acrescentam CT-B: o HTML servido e o componente provam o que eles afirmam. Os mutantes além de 5 por regra são de origem adversarial, fora do teto.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | blade do "Voltar ao topo" e sua folha (`voltar-ao-topo.css`); blade do chat (raiz `data-assistente-chat`); folha `assistente-chat.css`; script da pilha; raiz `.qad-root` do dock (vendor, só lida); a regra de visibilidade do dock e do chat por tela; rótulo dos grupos e tooltip dos favoritos | CT-01…CT-11 |
| F | empilhar, alinhar, espaçar, ocultar (inclusive do foco) e restaurar; estilizar; não entregar dock nem chat em bloqueio, login e Auth Designer, e voltar a entregá-los depois de destravar; agrupar por organização na ordem gravada; mostrar o caminho no tooltip | CT-B01…CT-B11, CT-04…CT-11 |
| D | posição e modo do dock (inclusive solto fora da borda); `data-open`; rolagem; presença do chat e do dock; tema; organização do favorito (corrente, outra, única, intercalada, desvinculada, excluída); tipo do destino (Resource, Page); multi-tenant ligado ou não; conversa com título sentinela | CT-B01…CT-B11, CT-04, CT-05, CT-07, CT-08, CT-09, CT-10 |
| I | render hooks dos painéis; rotas `lockscreen.{painel}.page`, `filament.{painel}.auth.two-factor`, `filament.app.auth.register`, `login.painel`, `filament.{painel}.auth.login`, `login`, `esqueci-minha-senha`, verificação de e-mail; componente Livewire do dock (round-trip); tela de bloqueio (destravar); teclado (Tab) | CT-04, CT-05, CT-07, CT-09, CT-11, CT-B06 |
| P | Chromium do plugin; desktop e celular; tema claro e escuro; sem JavaScript (CT-03 pela folha); suíte multi-organização × sem tenancy | CT-B01, CT-B09, CT-B10, CT-03, CT-05, CT-08, CT-10 |
| O | `admin`, `panel_user`, `infra`, `master_global` (com `Usar:Dock` pelo `Gate::before`); duas organizações e uma só; desvinculado; e-mail não verificado | CT-B01, CT-B05, CT-04, CT-05, CT-10 |
| T | posição lembrada; mutação ao vivo; morph do Livewire (chat e dock); bloquear e destravar; consultas por request | CT-B02, CT-B03, CT-04, CT-06, CT-09, CT-11 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1. Com o dock no canto inferior direito e a página rolada, o chat fica acima do dock e o "Voltar ao topo" acima do chat (sem chat, logo acima do dock); nenhum cobre outro, em todo painel | pilha | RQ-01, RQ-03, RQ-04, RQ-06, P-02 | EP | CT-B01 |
| R2. O dock fica no canto em que o usuário o pôs | pilha | RQ-06 | EP | CT-B01 |
| R3. Mesmo eixo vertical e espaços iguais entre vizinhos | pilha | RQ-07, RQ-08 | EP (desktop × celular) | CT-B01 |
| R15. Espaço de 16 px entre vizinhos, em todo painel | pilha | RQ-16 | valor literal × EP | CT-B01 |
| R4. A pilha vale desde o carregamento e sobrevive ao morph do chat | pilha | RQ-04, RQ-06 | EP + 2-switch | CT-B02, CT-B03 |
| R5. Dock nos cantos fora da área: posições sem o dock (alturas de hoje; voltar no eixo, a 30 px da borda), inclusive ao sair do canto inferior direito | pilha | Fora de Escopo, RQ-13, P-03 | EP + 2-switch | CT-B04 |
| R6. Sem o dock na página: alturas de hoje e o "Voltar ao topo" sempre no eixo da pilha, a 30 px da borda | pilha | P-03 | EP | CT-B05 |
| R7. Dock aberto sobre o lugar da pilha oculta chat e voltar (da vista, do clique e do foco); fechar restaura; aberto em outro canto, nada some | aberto | RQ-09, RQ-14, Fora de Escopo | tabela de decisão + 2-switch | CT-B06, CT-B07 |
| R8. Dock solto: a pilha reage só se ele cruza o lugar dos botões | pilha | RQ-12 (parte vigente), RQ-13 | BVA de 2 valores (dois lados da fronteira real e eixo x) | CT-B08 |
| R13. Layout barra no canto inferior direito: visíveis, acima da barra, no eixo do gatilho | pilha | RQ-15, RQ-16, RQ-18 | EP | CT-B11 |
| R9. Botão do chat fixo, redondo, na cor primária | estilo | RQ-10 | EP (tema) | CT-B09 |
| R10. Slide-over lateral fixo, com fundo, cabeçalho, conversa e campo | estilo | RQ-11 | EP (tema) | CT-B10 |
| R14. Slide-over no tema, com título a ≥ 4,5:1 | estilo | RQ-17 | EP + valor literal | CT-B10 |
| R11. Toda classe do chat tem regra escopada; nenhuma regra escapa | guarda | RQ-10, RQ-11, Fora de Escopo | EP | CT-01, CT-02 |
| R12. Sem JavaScript, o HTML servido e as folhas já posicionam os botões | guarda | P-03 ("sem JavaScript") | EP | CT-03 |
| R16. O dock não é entregue no bloqueio, nas telas de login e nas do Auth Designer alcançáveis com sessão; volta depois de destravar; continua montado no round-trip Livewire das páginas comuns | telas sem dock nem chat | RQ-19, RQ-20, RQ-21, RQ-22 | EP (tela × painel × suíte) + 2-switch (bloquear/destravar) + controle positivo | CT-04, CT-09, CT-10, CT-11 |
| R17. O chat da IA não é entregue nas mesmas telas (nem o botão nem a lista de conversas) e volta depois de destravar | telas sem dock nem chat | RQ-23 | EP + 2-switch + controle positivo | CT-04, CT-09, CT-10 |
| R18. Com multi-tenant, os favoritos do `/app` ficam em um grupo por organização, com o separador "{rótulo do painel} · {nome da organização}", inclusive com uma organização só, na ordem da primeira gravação de cada organização | dock por organização | RQ-24, RQ-27 | EP (nº de organizações × página × intercalação) | CT-05 |
| R22. O agrupamento não traz de volta favorito, nome, chave, URL nem tooltip de organização sem acesso | dock por organização | RQ-24 × comportamento acordado do dock (wiki `dock-de-acoes-rapidas` CT-58, CT-73; "não mostra separador de painel sem favorito visível") | EP (vínculo) | CT-05 |
| R19. Sem multi-tenant, o grupo do `/app` tem só o rótulo do painel | dock por organização | RQ-24 (recorte "com o multi-tenant ativo") × comportamento acordado do dock | EP | CT-08 |
| R20. Indicar a organização não acrescenta consulta para montar o dock | dock por organização | RQ-25 | rastreio de não-efeito (consultas) | CT-06 |
| R21. Cada favorito mostra, num tooltip, o caminho para onde leva, com e sem multi-tenant | dock por organização | RQ-26 | EP (painel × organização × tipo de destino × tenancy) | CT-07, CT-08 |

**Fora do conjunto de teste:**
- **RQ-02** (processo): a evidência é o `03-progresso.md`.
- **RQ-05** (release v0.48.1): quem confere é o checklist de release e o quality gate.

**Cobertura parcial declarada:**
- ~~**RQ-15 × RQ-07.** Fica aberto *em relação a que ponto da barra* o eixo se alinha (Q?1).~~ *(alterado em 2026-10-14: Q?1 renumerada Q12, respondida no Adendo 3 — RQ-18: o eixo é o do gatilho da barra, no canto; o CT-B11 passa a afirmá-lo, M48)*
- **P-02.** Os três painéis estão cobertos (CT-B01); o "painel registrado depois" é **lacuna declarada** (ver Taxonomia).
- **RQ-20 / RQ-21 nas telas de visitante sem sessão**: não se aplica. Sem sessão, o dock e o chat nunca são entregues: `vendor/hoceineel/filament-quick-action-dock/src/QuickActionDockPlugin.php:$isAuthenticatedOnly:78` (`= true`) e `app/Livewire/AssistenteChatWidget.php:render:288` (`'disponivel' => auth()->check()`). **Com sessão**, essas telas viraram linhas condicionais do CT-04 (ADV-11/12/22, ADV-35).
- ~~**RQ-24, ordem entre os grupos de organização**: o cenário afirma o conjunto de grupos, não a ordem entre eles (Q?1).~~ *(alterado em 2026-10-14: Q?1 renumerada Q19, respondida no Adendo 8 — RQ-27: a ordem gravada da primeira aparição de cada organização; o CT-05 linha a passa a afirmar a ordem, M72)*
- **RQ-13 × RQ-07, dock solto fora do eixo (CT-B08 linha f)**: o cenário afirma que a pilha sobe acima do dock, sem interseção, com 16 px e com chat e voltar no mesmo eixo entre si. Não afirma a qual eixo a pilha se alinha quando o dock está deslocado da coluna, porque o requisito não determina isso.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Pilha e reação ao dock | R1–R8, R13, R15 | browser | nova: `tests/Browser/PilhaDeBotoesFlutuantesTest.php`, no padrão de `RoteiroDoKitTest.php` (F-45) e `DockDeAtalhosTest.php` | posição, interseção, eixo, espaço, visibilidade e foco só existem com o JS do dock e do script executados | sessão (autorização de implementar e convenção do kit), 2026-10-14 |
| Chat estilizado | R9, R10, R14 | browser | nova: `tests/Browser/AssistenteChatEstiloTest.php` | `position`, raio, cor, contraste e empilhamento só existem com a folha aplicada | sessão (autorização de implementar e convenção do kit), 2026-10-14 |
| Guarda da folha do chat e posição sem JS | R11, R12 | Pest feature HTTP | nova: `tests/Kit/AssistenteChatCssTest.php`, irmã de `tests/Kit/VoltarAoTopoCssTest.php` | HTML renderizado por GET real e arquivos de CSS | sessão (autorização de implementar e convenção do kit), 2026-10-14 |
| Telas sem dock nem chat | R16, R17 | Pest feature HTTP | nova: `tests/Kit/TelasSemDockNemChatTest.php`. Oráculo herdado de `DockDeAtalhosTest` CT-02 (`wire:name="quick-action-dock"`); bloqueio como em `IdentidadeDoKitTest`/`LogoDarkModeTest`; destravar como em `BloqueioDeSessaoTest` | Q13: o componente **não é renderizado**, então a ausência no HTML servido é o observável. Arquivo novo porque os IDs de `DockDeAtalhosTest` são da wiki do dock | |
| Telas sem dock nem chat (multi-organização) | R16, R17 | Pest feature HTTP | nova: `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` (ADV-03) | rota, painel e render hooks do `/app` mudam com `{tenant}`; só a suíte `Tenancy` monta isso | |
| Telas sem dock nem chat (round-trip) | R16 | componente Livewire/Filament | nova: o mesmo `tests/Kit/TelasSemDockNemChatTest.php` (ADV-13) | o round-trip do próprio dock é chamada Livewire; o componente prova sem navegador | |
| Dock por organização (componente) | R18, R21, R22 | componente Livewire/Filament | nova: `tests/Tenancy/DockPorOrganizacaoTest.php`, no padrão de "agrupa os favoritos por painel" (`dockNaPagina(...)->instance()->getDockGroups()`) e do arranjo de `DockDeAtalhosTenancyTest` | grupos e tooltip são estado das actions do componente | |
| Dock por organização (custo) | R20 | Pest feature HTTP | nova: o mesmo `tests/Tenancy/DockPorOrganizacaoTest.php`, no padrão do CT-72 | consulta só se mede num request real | |
| Dock sem multi-tenant | R19, R21 | componente Livewire/Filament | nova: `tests/Kit/DockSemOrganizacaoTest.php` | a suíte Kit roda sem tenancy | |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `MutationObserver`, `resize`, `requestAnimationFrame`, `livewire:navigated` | são mecanismo | efeito testado em CT-B02, B03, B04, B06, B07 |
| path `assistente-chat.css`, atributo `data-assistente-chat` | são nomes do plano | seletor e arquivo de CT-01, CT-02, CT-04 |
| "a folha vem pelo `@filamentStyles`" | é mecanismo de carga | CT-B09 (M28) |
| "tudo é layout: só o navegador prova" | serviu para a costura | `## Costuras de Teste` |
| `bottom-6` = 24 px, `bottom-24` = 96 px, `right-6` = 24 px (chat) | alturas de hoje da P-03, lidas nas blades atuais | oráculo de CT-B04, CT-B05, CT-B08, CT-03 |
| folga direita de 30 px do voltar; regra `right: 1.5rem` + `margin-right: 0.375rem` em `voltar-ao-topo.css` | os 30 px vêm da P-03; a forma da regra foi dada pela sessão (ADV-10/19/21, ADV-31) | oráculo de CT-B04, CT-B05, CT-B06, CT-B08; o CT-03 confere a regra na folha |
| limiares de luminância 0,5 / 0,2 | medida de "acompanha o tema" do projeto | premissa de medida do CT-B10 |
| lista de páginas do Auth Designer | fato de nomeação dado pela sessão | escolha das linhas do CT-04 |
| `Paineis::rotulo(...)`, `getDockGroups()`, `getTooltip()` | nomes do código existente | detalhe de leitura do CT-05, CT-07, CT-08, CT-11 |

## Setup Global

### Personas
- `bruno`: `usuarioDoKit('admin', 'bruno@example.com')`, em `/admin`.
- `ana`: `usuarioDoKit('panel_user', 'ana@example.com')`, em `/app`.
- `iris`: `usuarioDoKit('infra', 'iris@example.com')`, em `/infra`.
- `mestre`: `usuarioDoKit('master_global')`, com `Usar:Dock` pelo `Gate::before` nos três painéis (CT-04, CT-09). A senha da fábrica é `password`, a usada por `BloqueioDeSessaoTest`.
- **Multi-organização** (`tests/Tenancy`): `duasOrganizacoes()` via `arranjarFavoritosDoDockTenancy()`. **Uma organização só**: `usuarioComPapel('panel_user', $acme)` + `tenants()->attach([$acme->id])`.
- Sempre `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.

### Fixtures
- **Dock presente**: `concederDock('<papel>')` + `ligarDock(['admin', 'app', 'infra'])`. **Ausente** (P-03): o papel sem `concederDock()`, ou o painel fora de `ligarDock([...])`.
- **Layout barra** (CT-B11): `DB::table('users')->where('id', $id)->update(['dock_layout' => 'bar'])`.
- **Conversa anterior** (CT-01): `conversaPara($ana, ...)`, que **muda para `tests/Pest.php`**.
- **Título sentinela** (CT-04, CT-09, CT-10; ADV-01/15): `conversaPara($mestre, 'Sentinela da pilha 7f3c')`, um texto que não ocorre em nenhuma outra parte do HTML. O executor confirma com o controle positivo, onde o título aparece no HTML do `/app` comum.
- **Página rolável** (`@premissa` de mecanismo): `document.body.style.minHeight = '4000px'`, depois `scrollTo(0, 800)`.
- **Dock lembrado** (Q10): mover pela UI e recarregar.
- **Dock solto** (Q11, corrigido): `keys('[data-layout] button[x-ref="trigger"]', 'Alt+ArrowUp')` (ou `'Alt+ArrowLeft'` para o eixo x), um passo por chamada, esperando `.qad-root` deixar de ter `data-settling`. `drag()` só se funcionar. `Alpine.$data(.qad-root).commit({x, y})` é o último recurso, também quando o passo do teclado não cair na faixa da linha (CT-B08 linhas d, e, f); o teste registra o uso.
- **Sessão bloqueada**: `$this->post(route('lockscreen.{painel}.lock-session'))->assertRedirect()`, depois `GET` da rota `lockscreen.{painel}.page`.
- **Destravar** (CT-09): como em `BloqueioDeSessaoTest`: `Livewire::test(TelaBloqueio::class)->set('data.password', 'password')->call('authenticate')->assertHasNoErrors()`. Se o `/app` tropeçar no boot do painel (`.ai/rules/testes.md` §noPainelBootado), boote por GET antes.
- **Desafio 2FA**: `GET` da rota `filament.{painel}.auth.two-factor` autenticado (`TelasDeAutenticacaoTest`, `IdentidadeDoKitTest`).
- **Registro por convite**: `GET` da rota `filament.app.auth.register` com o convite arranjado como em `ConviteTest`/`CadastroSocialPorConviteTest`.
- **Escolha de painel**: `ligarLoginUnificado(true)`, depois `GET` da rota `login.painel` autenticado.
- **Verificação de e-mail** (linha condicional): `config(['kit.registro.verificar_email' => true])`, e-mail não verificado, `GET` do `/app` seguindo redirecionamentos (como em `IdentidadeDoKitTest`).
- **Helpers que mudam para `tests/Pest.php`**: `arranjarFavoritosDoDockTenancy()`, `abrirDockTenancy()`, `pilhaComCodigoDoDock()`, `consultasDoGet(string $url): array` (extraído do CT-72) e `conversaPara()`.
- **Organização desvinculada**: `$usuario->tenants()->detach($globex->id)` depois de gravar. **Excluída**: `$globex->delete()`.

### Medidas (para os CT-B; uma função JS no arquivo de teste)
- **Retângulo**: `getBoundingClientRect()`. **Viewport**: `documentElement.clientWidth/clientHeight`.
- **Folgas**: direita = `clientWidth − rect.right`; inferior = `clientHeight − rect.bottom`.
- **Visível**: `display ≠ none`, `visibility ≠ hidden`, `opacity > 0`, área > 0.
- **Interseção**: estrita. **Centro x**. **Espaço** = `rect_de_baixo.top − rect_de_cima.bottom`.
- **Dock**: no `speed-dial`, o gatilho, e, aberto, a união com os `.qad-item` visíveis. **No layout barra**, a superfície `.qad-root` para o topo, os espaços e as interseções; o eixo no gatilho (RQ-18). **Chat**: `[data-assistente-chat] button[aria-label="Abrir assistente"]`. **Voltar**: `[data-voltar-ao-topo]`.
- **Contraste** WCAG. **Tolerância**: ±1 px.

### Fakes
- Nenhum.

### Estratégia de DB
- `RefreshDatabase` por pasta (`Browser`, `Kit`, `Tenancy`); `TenancyTestCase` na `Tenancy`.

### Arnês de browser
- `composer test:browser`; aquecimento por GET; um painel por cenário; uma visita por tema; em série.

---

## Regra R1. Pilha acima do dock no canto inferior direito, sem sobreposição, em todo painel

> `RQ-01`, `RQ-03`, `RQ-04`, `RQ-06`, `P-02` · perfil **padrão** · técnica: **EP** · cenário: **CT-B01** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M01 | o "Voltar ao topo" fica no recuo de hoje (`bottom-6`) com o dock no canto | CT-B01 linha `/admin` | `intersecta(voltar, dock)` é falso |
| M02 | a pilha lê o `data-position` do servidor (sempre `bottom-center`), não a posição real | CT-B01 todas as linhas | linha `/app`: `chat.bottom ≤ dock.top` |
| M03 | ordem trocada: voltar logo acima do dock e chat acima dele | CT-B01 linha `/app` | `voltar.bottom ≤ chat.top` e `chat.bottom ≤ dock.top` |
| M04 | só o voltar entra na pilha; o chat fica no canto | CT-B01 linha `/app` | `intersecta(chat, dock)` é falso |
| M05 | a pilha vale só para uma lista fixa de painéis (`admin`, `app`) | CT-B01 linha `/infra` | `intersecta(voltar, dock)` é falso em `/infra` |

## Regra R2. O dock fica no canto que o usuário escolheu

> `RQ-06` · perfil **padrão** · técnica: **EP** · cenário: **CT-B01** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M06 | resolve o conflito subindo o **dock** acima do chat | CT-B01 linha `/app` | folga inferior do dock = `--qad-offset` (24 px) ±1 |
| M07 | afasta o dock para a esquerda, com os botões ao lado | CT-B01 linhas `/app` e `/admin` | folga direita do dock = `--qad-offset` ±1 |

## Regra R3. Mesmo eixo vertical e espaços iguais entre vizinhos

> `RQ-07`, `RQ-08` · perfil **padrão** · técnica: **EP** (desktop × celular) · cenário: **CT-B01** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M08 | alinha pela borda direita, não pelo centro | CT-B01 linha `/app` | `|centroX(voltar) − centroX(dock)| ≤ 1` |
| M09 | espaços com alturas fixas erradas, diferentes entre si | CT-B01 linha `/app` | `|espaço(dock→chat) − espaço(chat→voltar)| ≤ 1` |
| M10 | usa o tamanho de desktop do gatilho no celular | CT-B01 linha `/app celular` | espaços iguais e `intersecta(chat, dock)` falso em 390×844 |
| M11 | centraliza só o chat; o voltar fica em `right-6` | CT-B01 linhas `/admin` e `/app` | `|centroX(voltar) − centroX(dock)| ≤ 1` |

## Regra R15. Espaço de 16 px entre vizinhos, em todo painel

> `RQ-16` · perfil **padrão** · técnica: **valor literal** × **EP** · cenário: **CT-B01** (`05`), todas as linhas

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M41 | espaços iguais, mas com outro valor (24 px ou 8 px) | CT-B01 todas as linhas | cada espaço = 16 ±1 |
| M42 | 16 px só no `/app`; sem chat, recuo fixo de 96 px | CT-B01 linhas `/admin` e `/infra` | `espaço(dock→voltar)` = 16 ±1 |

## Regra R4. A pilha vale desde o carregamento e sobrevive à re-renderização do chat

> `RQ-04`, `RQ-06` (dimensão T) · perfil **padrão** · técnica: **EP** + **2-switch** · cenários: **CT-B02**, **CT-B03** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M12 | reage só à mudança ao vivo, não à posição lembrada | CT-B02 | sem ação depois do load, `chat.bottom ≤ dock.top` e sem interseção |
| M13 | calcula antes de o dock se posicionar e não recalcula | CT-B02 | idem |
| M14 | a posição é escrita como `style` no chat e o morph do Livewire a apaga | CT-B03 | `chat.rect` = antes ±1 e sem interseção com o dock |

## Regra R5. Dock nos cantos fora da área: posições sem o dock

> Fora de Escopo, `RQ-13`, `P-03` · perfil **padrão** · técnica: **EP** + **2-switch** · cenário: **CT-B04** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | reage a qualquer canto inferior | CT-B04 linha "inferior esquerdo `/app`" | voltar com folga inferior 96 e direita 30 ±1; chat 24/24 ±1 |
| M16 | reage ao canto superior direito | CT-B04 linha "superior direito `/app`" | chat 24/24 ±1 |
| M17 | o estado "no canto" fica preso ao sair do canto inferior direito | CT-B04 linha "do inferior direito para o inferior esquerdo" | chat 24/24 e voltar 96/30 ±1 |

## Regra R6. Sem o dock na página: alturas de hoje e voltar no eixo, a 30 px

> `P-03` · perfil **padrão** · técnica: **EP** · cenário: **CT-B05** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | sem `.qad-root`, assume um dock no canto e reserva o espaço | CT-B05 linha `/app` | chat 24/24 ±1 |
| M19 | estoura com a raiz do dock ausente (`null`) | CT-B05 todas as linhas | `assertNoJavaScriptErrors()` (apoio) + folgas |
| M20 | sem o dock, o voltar do `/app` perde o recuo e cai sobre o chat | CT-B05 linha `/app` | voltar com folga inferior 96 ±1, sem interseção com o chat |
| M69 | sem o dock, o voltar fica em `right-6` (24 px) e só vai ao eixo quando o dock chega *(P-03)* | CT-B05 as duas linhas | folga direita do voltar = 30 ±1 |

## Regra R7. Dock aberto sobre o lugar da pilha oculta chat e voltar; fechar restaura

> `RQ-09`, `RQ-14`, Fora de Escopo · perfil **padrão** · técnica: **tabela de decisão** + **2-switch** · cenários: **CT-B06**, **CT-B07** (`05`)

| Lugar do dock | Aberto | Chat e voltar | Cenário |
|---|---|---|---|
| canto inferior direito | sim | ocultos; o leque recebe o clique no lugar deles; Tab a partir do leque não os alcança | CT-B06 linha 1 |
| canto inferior direito | não, depois de aberto | visíveis, nos lugares da pilha | CT-B07 |
| canto inferior esquerdo | sim | visíveis, nas posições sem o dock | CT-B06 linha 2 |
| solto na borda direita, sobre o lugar da pilha | sim | ocultos; o leque recebe o clique no lugar deles; Tab a partir do leque não os alcança | CT-B06 linha 3 |
| layout barra no canto inferior direito | sim | visíveis, acima da barra | CT-B11 (R13) |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | oculta só o voltar; o chat continua | CT-B06 linha 1 | chat oculto |
| M22 | oculta ao abrir em qualquer canto | CT-B06 linha 2 | chat e voltar visíveis, nas folgas sem o dock |
| M23 | não restaura ao fechar | CT-B07 | chat e voltar visíveis |
| M24 | restaura fora da pilha | CT-B07 | retângulos = os de antes ±1, sem interseção com o dock |
| M27 | oculta só com o dock aberto **no canto** | CT-B06 linha 3 | chat e voltar ocultos |
| M86 | "oculta" com `opacity: 0`, mas chat e voltar continuam acima do leque e capturam o clique *(ADV-18, ADV-32)* | CT-B06 linhas 1 e 3 | `elementFromPoint` no centro da **interseção** entre cada `.qad-item` visível e o antigo retângulo do chat ou do voltar devolve um descendente do item; o mutante devolve o chat ou o voltar |
| M90 | oculta só da vista: chat e voltar continuam focáveis por teclado *(ADV-30)* | CT-B06 linhas 1 e 3 | com o foco no último item do leque, Tab não deixa `document.activeElement` no chat nem no voltar |

## Regra R8. Dock solto: a pilha reage só se ele cruza o lugar dos botões

> `RQ-12` (parte vigente), `RQ-13` · perfil **padrão** · técnica: **BVA de 2 valores**, com linhas nos dois lados da fronteira real (ADV-06, ADV-28) e no eixo x (ADV-29) · cenário: **CT-B08** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | reage só ao canto exato | CT-B08 linha a | `chat.bottom ≤ dock.top`, `voltar.bottom ≤ chat.top`, sem interseção |
| M43 | reage a qualquer dock na borda direita (coluna de altura inteira) | CT-B08 linha b | chat 24/24 e voltar 96/30 ±1 |
| M44 | a reação devolve o dock solto ao canto | CT-B08 linhas a, c, e, f | `dock.rect` = o medido logo depois de solto ±1 |
| M78 | a reação ao dock solto só existe quando há chat; no `/admin`, o voltar fica sob o dock *(ADV-05)* | CT-B08 linha c | voltar 16 px acima do dock, no eixo, sem interseção |
| M79 | reage com margem: considera "sobre a pilha" o dock a menos de 16 px do voltar sem cruzá-lo *(ADV-06)* | CT-B08 linha d | chat 24/24 e voltar 96/30 ±1 |
| M88 | só reage quando a sobreposição passa de um limiar (ex.: metade da altura do botão); um corte de poucos px no topo do voltar fica sem reação *(ADV-28)* | CT-B08 linha e | `voltar.bottom ≤ chat.top`, `chat.bottom ≤ dock.top`, sem interseção, espaços de 16 px |
| M89 | considera o dock "na coluna" só quando ele encosta na borda direita (x no máximo); deslocado para dentro, a pilha não reage *(ADV-29)* | CT-B08 linha f | chat e voltar acima do dock, sem interseção, espaços de 16 px |

## Regra R13. Layout barra no canto inferior direito: visíveis, acima da barra, no eixo do gatilho

> `RQ-15`, `RQ-16`, `RQ-18` · perfil **padrão** · técnica: **EP** · cenário: **CT-B11** (`05`)
> Medida corrigida (executor dos CT-B): o topo da barra e o espaço de 16 px são medidos na superfície `.qad-root`, e o eixo no gatilho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | reconhece o dock só no `speed-dial`; na barra, a pilha fica coberta | CT-B11 | `chat.bottom ≤ .qad-root.top` e sem interseção |
| M45 | trata o `data-open="true"` da barra como leque aberto e oculta os dois | CT-B11 | chat e voltar visíveis |
| M48 | centraliza a pilha sobre o meio da barra *(Adendo 3)* | CT-B11 | `|centroX(chat) − centroX(gatilho)| ≤ 1` e folga direita do chat = 24 ±1 |

## Regra R9. Botão do chat fixo no canto inferior direito, redondo, na cor primária

> `RQ-10` · perfil **padrão** · técnica: **EP** (tema) · cenário: **CT-B09** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | a folha existe mas não é carregada no painel | CT-B09 as duas linhas | `position = fixed` |
| M29 | `rounded-full` sem regra | CT-B09 | raio ≥ largura/2 − 1 e largura = altura ±1 |
| M30 | `fixed` sem `bottom-*`/`right-*` | CT-B09 | dentro do viewport, centro no quadrante inferior direito |
| M31 | cor de fundo só no tema claro | CT-B09 linha "escuro" | fundo = sonda `var(--primary-N)`, alfa > 0 |

## Regra R10. Slide-over lateral fixo à direita, com fundo, cabeçalho, conversa e campo de mensagem

> `RQ-11` · perfil **padrão** · técnica: **EP** (tema) · cenário: **CT-B10** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M32 | painel sem fundo | CT-B10 as duas linhas | fundo com alfa > 0 no próprio painel |
| M33 | `inset-y-0` sem regra | CT-B10 | `top = 0` e `bottom = vh` ±1 |
| M34 | `max-w-md` sem regra | CT-B10 | largura < `vw` a 1280 px |
| M35 | `z-50` sem regra | CT-B10 | `elementFromPoint` em dois pontos pertence ao painel |

## Regra R14. O slide-over segue o tema, com o título a ≥ 4,5:1 de contraste

> `RQ-17` · perfil **padrão** · técnica: **EP** + **valor literal** · cenário: **CT-B10** (`05`)

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M46 | as variantes `dark:` do painel sem regra | CT-B10 linha "escuro" | luminância do fundo ≤ 0,2 |
| M47 | `dark:text-white` do título sem regra | CT-B10 linha "escuro" | contraste ≥ 4,5 |

## Regra R11. Toda classe que o chat emite tem regra escopada; nenhuma regra escapa do escopo

> `RQ-10`, `RQ-11`, Fora de Escopo · perfil **padrão** · técnica: **EP** · cenários: **CT-01**, **CT-02**

```gherkin
# language: pt
Funcionalidade: Estilo do chat da IA nos painéis

  Regra: Toda classe que o chat emite tem regra escopada, e nenhuma regra da folha alcança outra tela

    Esquema do Cenário: [CT-01] toda classe que o chat emite tem regra sob o escopo do chat
      Dado a Ana autenticada no painel /app
      E <estado>
      Quando o HTML de <origem> é lido
      Então toda classe dentro da raiz [data-assistente-chat] tem regra em assistente-chat.css sob [data-assistente-chat], ou existe na CSS pré-compilada do Filament
      E o conjunto lido tem ao menos <piso> classes

      Exemplos:
        | estado                         | origem                                             | piso | # partição                                       |
        | sem conversa anterior          | GET /app renderizado                               | 20   | botão + slide-over inicial                       |
        | com uma conversa anterior dela | GET /app renderizado                               | 25   | ramo "Conversas anteriores"                      |
        | qualquer                       | atributos class literais do fonte da blade do chat | 40   | ramos condicionais (mensagens, recusa, renomear) |

    Cenário: [CT-02] nenhuma regra da folha do chat fica fora do escopo do chat
      Dado a folha resources/css/filament/assistente-chat.css sem os comentários
      Quando cada seletor de cada regra é lido
      Então todo seletor começa por [data-assistente-chat]
      E a lista de seletores fora do escopo é vazia
```

> **Notas de execução:**
> - O piso é o controle positivo do detector; o executor recalibra pela contagem real e registra.
> - O CT-02 lê o fonte sem comentário.
> - A negativa usa `toBe([], $msg)`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M36 | classe nova na blade sem regra | CT-01 linhas 1 e 2 | `semDeclaracao` não vazio |
| M37 | regra global (`.fixed { … }`) | CT-02 | `foraDoEscopo` contém `.fixed` |
| M38 | ramos condicionais sem regra | CT-01 linha 3 | `semDeclaracao` não vazio |

## Regra R12. Sem JavaScript, o HTML servido e as folhas já posicionam os botões

> `P-03` ("Sem JavaScript […]") · perfil **padrão** · técnica: **EP** · cenário: **CT-03**
> `@premissa` (raia desenho): sem JS, a posição é a das classes do HTML servido mais as regras das folhas, lidas como texto. O plugin não desliga JS (`grep -rn "javaScriptEnabled" vendor/pestphp/pest-plugin-browser/src` vem vazio). A forma da regra dos 30 px (`right: 1.5rem` + `margin-right: 0.375rem` sob `[data-voltar-ao-topo]`) foi dada pela sessão (ADV-10/19/21); o seletor exato e a ausência de regra posterior vêm do ADV-31.

```gherkin
  Regra: Sem JavaScript, a posição de hoje vem do HTML servido e das folhas

    Esquema do Cenário: [CT-03] o HTML servido e as folhas posicionam os botões flutuantes sem JavaScript
      Dado <persona> autenticado em <painel>
      Quando o HTML de <painel> e as folhas do voltar e do chat são lidos, sem executar JavaScript
      Então o botão "Voltar ao topo" traz as classes fixed, right-6 e <recuo_voltar>
      E o botão "Abrir assistente" <chat>
      E cada uma dessas classes tem regra na folha do respectivo escopo
      E a folha voltar-ao-topo.css dá a [data-voltar-ao-topo] right de 1.5rem e margin-right de 0.375rem, que somam 30 px
      E essa regra tem o seletor exatamente [data-voltar-ao-topo] ou [data-voltar-ao-topo].right-6, fora de @media, sem atributo que só o script põe
      E nenhuma regra posterior da folha muda right ou margin-right do voltar
      E o botão servido, só com os atributos do HTML sem JavaScript, casa com esse seletor

      Exemplos:
        | persona | painel | recuo_voltar | chat                                      |
        | ana     | /app   | bottom-24    | traz as classes fixed, bottom-6 e right-6 |
        | bruno   | /admin | bottom-6     | não está no HTML                          |
```

> **Notas de execução:**
> - A soma converte `rem` a 16 px. A leitura é sobre a folha sem comentário.
> - "Regra posterior" é qualquer regra que venha depois, na mesma folha, cujo seletor case com o botão servido (`[data-voltar-ao-topo]` com qualquer combinação das classes que ele traz no HTML) e declare `right` ou `margin-right`.
> - "Casa com o seletor": os atributos e as classes do botão no HTML servido cobrem os do seletor.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | a posição do chat passa a ser só do script | CT-03 linha `/app` | o chat traz `bottom-6` e `right-6`, cobertas na folha |
| M40 | o recuo do voltar no `/app` vira só do script | CT-03 linha `/app` | o voltar traz `bottom-24` |
| M84 | o eixo de 30 px do voltar é aplicado só pelo script, sem regra na folha *(ADV-10/19/21)* | CT-03 as duas linhas | a folha declara `right: 1.5rem` e `margin-right: 0.375rem` sob `[data-voltar-ao-topo]`, com soma 30 px |
| M91 | a regra dos 30 px existe, mas presa a um atributo que só o script põe (ex.: `[data-voltar-ao-topo][data-pilha]`) ou dentro de `@media` *(ADV-31)* | CT-03 as duas linhas | seletor exato fora de `@media`; o botão servido casa com ele |
| M92 | uma regra posterior da folha (ex.: `[data-voltar-ao-topo].bottom-24 { margin-right: 0 }`) desfaz os 30 px *(ADV-31)* | CT-03 linha `/app` | nenhuma regra posterior que case com o botão servido muda `right` ou `margin-right` |

## Regra R16. O dock não é entregue no bloqueio, no login e nas telas do Auth Designer alcançáveis com sessão

> `RQ-19`, `RQ-20`, `RQ-21`, `RQ-22` · perfil **padrão** · técnica: **EP** (tela × painel × suíte) + **2-switch** (bloquear/destravar) + **controle positivo** · cenários: **CT-04**, **CT-09**, **CT-10**, **CT-11**
> Q13 (decidida): o dock **não é renderizado** nessas telas, então a ausência no HTML servido é o oráculo (`wire:name="quick-action-dock"`).

## Regra R17. O chat da IA não é entregue nas mesmas telas

> `RQ-23` · perfil **padrão** · técnica: **EP** + **2-switch** + **controle positivo** · cenários: **CT-04**, **CT-09**, **CT-10**
> Oráculos de ausência: `data-assistente-chat`, `wire:name="assistente-chat-widget"` e o **título sentinela** da conversa do mestre (ADV-01/15).

```gherkin
  Regra: Bloqueio, login e telas do Auth Designer não entregam o dock nem o chat da IA

    Esquema do Cenário: [CT-04] em <tela> do painel <painel>, com sessão e dock liberado, a resposta <resposta>, e o HTML <dock> o dock e <chat> o chat
      Dado o mestre autenticado, com o dock ligado nos três painéis e uma conversa de título sentinela
      E <preparo>
      Quando o HTML de <tela> é pedido
      Então a resposta <resposta>
      E o HTML traz data-voltar-ao-topo, o marcador de que os render hooks do painel rodaram nesta tela
      E o HTML <dock> wire:name="quick-action-dock"
      E o HTML <chat> data-assistente-chat, wire:name="assistente-chat-widget" e o título sentinela

      Exemplos:
        | painel | tela                                  | preparo                                       | resposta                                    | dock     | chat                       | # partição                       |
        | /admin | /admin/screen/lock                    | sessão bloqueada pela rota do pacote          | é 200                                       | não traz | não traz (painel sem chat) | bloqueio (RQ-19, RQ-21)          |
        | /app   | /app/screen/lock                      | sessão bloqueada pela rota do pacote          | é 200                                       | não traz | não traz                   | bloqueio com chat (RQ-23)        |
        | /infra | /infra/screen/lock                    | sessão bloqueada pela rota do pacote          | é 200                                       | não traz | não traz (painel sem chat) | bloqueio, 3º painel              |
        | /admin | rota filament.admin.auth.two-factor   | nenhum                                        | é 200                                       | não traz | não traz (painel sem chat) | Auth Designer: 2FA (RQ-21)       |
        | /app   | rota filament.app.auth.two-factor     | nenhum                                        | é 200                                       | não traz | não traz                   | 2FA com chat (RQ-23)             |
        | /app   | rota filament.app.auth.register       | convite válido arranjado                      | redireciona (ver nota ADV-35) ou é 200      | não traz | não traz                   | Auth Designer: convite (condicional) |
        | —      | rota login.painel                     | login unificado ligado                        | é 200                                       | não traz | não traz                   | escolha de painel (RQ-22)        |
        | /admin | rota filament.admin.auth.login        | nenhum                                        | redireciona (ver nota ADV-35) ou é 200      | não traz | não traz (painel sem chat) | login com sessão (condicional)   |
        | —      | rota login                            | login unificado ligado                        | redireciona (ver nota ADV-35) ou é 200      | não traz | não traz                   | login unificado (condicional)    |
        | —      | rota esqueci-minha-senha              | login unificado ligado                        | redireciona (ver nota ADV-35) ou é 200      | não traz | não traz                   | recuperação (condicional)        |
        | /app   | aviso de verificação de e-mail        | verificar_email ligado, e-mail não verificado | redireciona (ver nota ADV-35) ou é 200      | não traz | não traz                   | verificação (condicional)        |
        | /app   | /app (página comum)                   | nenhum                                        | é 200                                       | traz     | traz                       | controle positivo                |
        | /admin | /admin (página comum)                 | nenhum                                        | é 200                                       | traz     | não traz (painel sem chat) | controle positivo                |
```

> **Notas de execução:**
> - **Linhas condicionais (ADV-35).** Quando a resposta é redirecionamento, o teste afirma o destino (`assertRedirect` com o caminho registrado no `03-progresso.md`) e segue o redirecionamento.
>   - Se o destino é tela das RQ-19 a RQ-22, o HTML dele traz o marcador e **não traz** nenhuma das agulhas do dock e do chat, nem o título sentinela.
>   - Se o destino é página comum do painel (saída do fluxo de autenticação), ali dock e chat são esperados: a linha registra o destino e não afirma ausência (divergência declarada no cabeçalho).
>   - Quando a resposta é 200, todas as asserções da linha valem.
> - **Marcador `data-voltar-ao-topo`** (ADV-17, ADV-35): prova que o render hook global `BODY_END` rodou na tela, e por isso a ausência ali discrimina. Se uma tela não o tiver, o executor **reporta**; a linha passa a valer como tela de visitante, e os mutantes que só ela matava passam a **sem matador**, com a contagem do cabeçalho refeita.
> - **Controle positivo** (ADV-16): uma asserção por agulha — `wire:name="quick-action-dock"`, `data-assistente-chat`, `wire:name="assistente-chat-widget"`, título sentinela —, cada uma com a própria mensagem.
> - **Ausência**: `$this->assertStringNotContainsString($agulha, $html, $msg)`, nunca `not->toContain($x, $msg)`.
> - O caminho da tela vem do nome da rota.

```gherkin
    Cenário: [CT-09] depois de bloquear e destravar com a senha, o /app volta a entregar o dock e o chat
      Dado o mestre autenticado, com o dock ligado e uma conversa de título sentinela
      E a sessão bloqueada no /app, com a tela de bloqueio sem dock nem chat
      Quando o mestre destrava a sessão com a senha correta, pela tela de bloqueio
      Então o HTML de /app traz wire:name="quick-action-dock"
      E traz data-assistente-chat
      E traz o título sentinela
```

```gherkin
    Esquema do Cenário: [CT-10] com multi-tenant, a tela de bloqueio do /app não entrega dock nem chat, e o painel da organização entrega
      Dado o usuário de duasOrganizacoes() com o dock liberado no /app e uma conversa de título sentinela
      E <preparo>
      Quando o HTML de <tela> é pedido
      Então a resposta é 200 e o HTML traz data-voltar-ao-topo
      E o HTML <dock> wire:name="quick-action-dock"
      E o HTML <chat> data-assistente-chat e o título sentinela

      Exemplos:
        | tela                     | preparo                                      | dock     | chat     | # partição        |
        | rota lockscreen.app.page | sessão bloqueada a partir do painel da Acme | não traz | não traz | bloqueio          |
        | o painel da Acme no /app | nenhum                                       | traz     | traz     | controle positivo |
```

```gherkin
    Cenário: [CT-11] numa página comum do /app, um round-trip Livewire do próprio dock não o tira da tela
      Dado a Ana com o dock liberado no /app e um favorito gravado
      E o dock montado numa página comum do /app
      Quando a action "gerenciarAtalhos" do dock é montada, num round-trip do componente
      Então o grupo do favorito continua em getDockGroups()
      E o HTML do componente continua trazendo o favorito
```

> CT-11 pela `dockNaPagina('app')` (Kit, sem tenancy), depois de bootar o `/app` por GET.

#### Mutantes previstos (R16)

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M49 | esconde só na rota de bloqueio do `/app` (nome fixo `lockscreen.app.page`) | CT-04 linhas `/admin` e `/infra` de bloqueio | o HTML não traz `wire:name="quick-action-dock"` |
| M50 | compara o caminho sem o prefixo do painel e nunca casa | CT-04 linhas de bloqueio | idem |
| M51 | a condição vale para toda página do painel (`Route::has(...)` no lugar de `routeIs(...)`) | CT-04 linhas de controle positivo | o HTML traz `wire:name="quick-action-dock"` em `/app` e `/admin` |
| M52 | esconde só no bloqueio, não nas outras telas do Auth Designer | CT-04 linhas de 2FA e de convite | o HTML não traz o dock |
| M53 | esconde nas telas do Auth Designer, mas não na escolha de painel | CT-04 linha `login.painel` | o HTML não traz o dock |
| M74 | tela de login aberta com sessão (sem redirecionar) entrega o dock *(ADV-11/12/22)* | CT-04 linhas condicionais, quando a resposta é 200 | o HTML não traz o dock |
| M75 | o dock some enquanto a chave `lockscreen` existe na sessão, e o pacote a deixa `false` ao destravar *(ADV-02)* | CT-09 | o HTML do `/app` traz o dock depois de destravar |
| M77 | a regra de ocultar mora num ramo que só roda sem multi-tenant *(ADV-03)* | CT-10 linha de bloqueio | o HTML não traz o dock |
| M85 | a regra lê a rota do request corrente; no round-trip Livewire, a rota não é a da página e o dock some *(ADV-13)* | CT-11 | `getDockGroups()` conserva o grupo do favorito; o HTML traz o favorito |
| M95 | a tela condicional redireciona para outra tela do fluxo de autenticação que entrega o dock *(ADV-35)* | CT-04 linhas condicionais, quando redirecionam para tela das RQ-19 a RQ-22 | o HTML do destino não traz o dock |

#### Mutantes previstos (R17)

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M54 | o chat some só no bloqueio; no 2FA e no convite continua | CT-04 linhas de 2FA e de convite do `/app` | o HTML não traz `data-assistente-chat` |
| M68 | a condição do chat vale para toda página do `/app` | CT-04 linha de controle `/app`; CT-10 linha de controle | o HTML traz `data-assistente-chat` |
| M73 | some só o botão do chat; a lista "Conversas anteriores" continua renderizada *(ADV-01/15)* | CT-04 linhas de bloqueio, 2FA e convite do `/app`; CT-10 | o HTML não traz o título sentinela; o controle positivo traz |
| M76 | o chat fica oculto enquanto a chave `lockscreen` existe na sessão *(ADV-02)* | CT-09 | o HTML do `/app` traz `data-assistente-chat` e o título sentinela depois de destravar |

## Regra R18. Com multi-tenant, um grupo por organização com o separador "{rótulo do painel} · {nome da organização}", na ordem gravada

> `RQ-24`, `RQ-27` · perfil **padrão** · técnica: **EP** (nº de organizações × página corrente × intercalação) · cenário: **CT-05** (linhas a, b, c, f)

## Regra R22. O agrupamento não traz de volta favorito, nome, chave, URL nem tooltip de organização sem acesso

> `RQ-24` × comportamento acordado do dock (wiki `dock-de-acoes-rapidas`: CT-58, CT-73; "não mostra separador de painel sem favorito visível") · perfil **padrão** · técnica: **EP** (vínculo) · cenário: **CT-05** (linhas d, e)

```gherkin
  Regra: Com multi-tenant, os favoritos do /app ficam agrupados por organização

    Esquema do Cenário: [CT-05] <caso>: os grupos do dock são <grupos>
      Dado <usuário> com o dock liberado nos painéis /app e /admin
      E os favoritos gravados <favoritos>
      Quando o dock é montado em <página>
      Então o rótulo de cada grupo, com as chaves dos favoritos dele, é <grupos>
      E cada grupo tem exatamente o conjunto de nomes de action listado, sem nenhum a mais
      E o grupo "Atalhos" tem exatamente as actions que tem no mesmo arranjo sem favoritos gravados
      E nenhum grupo tem lista de favoritos vazia

      Exemplos:
        | caso                             | usuário                                                        | favoritos                                                              | página                   | grupos                                                                                                                                  |
        | a: duas organizações, na Acme    | o usuário de duasOrganizacoes()                                | Projetos da Globex, Projetos da Acme, Usuários do /admin (nesta ordem) | o painel da Acme no /app | "{rótulo do /app} · Globex" [Projetos da Globex], "{rótulo do /app} · Acme" [Projetos da Acme], "Administração" [Usuários], "Atalhos"   |
        | b: uma organização só            | panel_user vinculado só à Acme                                 | Projetos da Acme                                                       | o painel da Acme no /app | "{rótulo do /app} · Acme" [Projetos da Acme], "Atalhos"                                                                                 |
        | c: duas organizações, no /admin  | o usuário de duasOrganizacoes()                                | Projetos da Acme, Projetos da Globex                                   | o painel /admin          | "{rótulo do /app} · Acme" [Projetos da Acme], "{rótulo do /app} · Globex" [Projetos da Globex], "Atalhos"                               |
        | d: desvinculado da Globex        | o usuário de duasOrganizacoes(), depois desvinculado da Globex | Projetos da Acme, Projetos da Globex                                   | o painel da Acme no /app | "{rótulo do /app} · Acme" [Projetos da Acme], "Atalhos"                                                                                 |
        | e: Globex excluída               | o usuário de duasOrganizacoes(), com a Globex excluída         | Projetos da Acme, Projetos da Globex                                   | o painel da Acme no /app | "{rótulo do /app} · Acme" [Projetos da Acme], "Atalhos"                                                                                 |
        | f: intercalados                  | o usuário de duasOrganizacoes()                                | Projetos da Acme, Projetos da Globex, Dashboard da Acme (nesta ordem)  | o painel da Acme no /app | "{rótulo do /app} · Acme" [Projetos da Acme, Dashboard da Acme], "{rótulo do /app} · Globex" [Projetos da Globex], "Atalhos"            |
```

> **Notas de execução:**
> - `{rótulo do /app}` = `Paineis::rotulo(Filament::getPanel('app'))`. O separador " · " é literal da RQ-24.
> - Leitura: `abrirDockTenancy(...)->instance()->getDockGroups()`, mapeando `[label, array_keys(actions)]`. A comparação é sobre a **sequência** de grupos: entre organizações, a ordem da primeira aparição na lista gravada (RQ-27); dentro do grupo, a ordem gravada *(alterado em 2026-10-14: Adendo 8)*.
> - **Conjunto exato** (ADV-33): o conjunto de "Atalhos" é medido no mesmo arranjo sem favoritos gravados, na mesma página, antes de gravar.
> - Nas linhas d e e, a ausência tem alvo real, porque o favorito da Globex está gravado. Além do rótulo, nenhuma chave de favorito da Globex aparece em grupo nenhum, inclusive "Atalhos" (ADV-33), e nenhuma action de nenhum grupo tem URL nem tooltip contendo o caminho de Projetos na Globex (ADV-26).

#### Mutantes previstos (R18)

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M55 | mostra a organização só quando o usuário tem mais de uma | CT-05 linha b | rótulo = "{rótulo do /app} · Acme" |
| M56 | um grupo só para o `/app`, com a organização apenas no rótulo de cada item | CT-05 linhas a e c | dois grupos de `/app`, um por organização |
| M57 | usa o slug ou o id da organização em vez do nome | CT-05 linhas a, b, c | rótulo exato com o **nome** |
| M58 | rotula pela organização corrente (`Filament::getTenant()`), não pela do favorito | CT-05 linhas a e c | linha a: o favorito da Globex fica sob "· Globex"; linha c: os rótulos mantêm o nome |
| M59 | acrescenta organização também ao grupo do `/admin` | CT-05 linha a | o grupo do `/admin` é exatamente "Administração" |
| M72 | ordena os grupos de organização por nome ou id, não pela ordem gravada *(Adendo 8)* | CT-05 linha a, gravada Globex antes de Acme | "· Globex" vem antes de "· Acme" |
| M80 | agrupa por trechos contíguos da lista gravada e cria dois grupos "· Acme" quando os favoritos estão intercalados *(ADV-07)* | CT-05 linha f | um só grupo "· Acme", com os dois favoritos da Acme na ordem gravada, seguido de "· Globex" |

#### Mutantes previstos (R22)

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M70 | agrupa a partir das chaves gravadas, não da lista filtrada por acesso | CT-05 linha d | nenhum rótulo contém "Globex" |
| M71 | cria o separador da organização excluída mesmo sem favorito visível | CT-05 linha e | nenhum grupo vazio; nenhum rótulo contém "Globex" |
| M83 | tira o rótulo da organização sem acesso, mas mantém a action dela em outro grupo (ex.: "Outros painéis") *(ADV-26)* | CT-05 linhas d e e | nenhuma action tem URL nem tooltip com o caminho de Projetos na Globex |
| M93 | o favorito sem rótulo de organização vai parar no grupo "Atalhos" (ou um grupo ganha action a mais) *(ADV-33)* | CT-05 linhas d e e; todas as linhas pelo conjunto exato | "Atalhos" = o conjunto do arranjo sem favoritos; nenhuma chave da Globex em grupo nenhum |

## Regra R19. Sem multi-tenant, o grupo do `/app` tem só o rótulo do painel

> `RQ-24` (recorte "com o multi-tenant ativo") × comportamento acordado do dock · perfil **padrão** · técnica: **EP** · cenário: **CT-08**

```gherkin
  Regra: Sem multi-tenant, o rótulo do grupo do /app é só o do painel

    Cenário: [CT-08] sem multi-tenant, os favoritos do /app ficam sob o rótulo do painel, com o tooltip do caminho
      Dado a Ana, panel_user, com o dock liberado no /app, numa instalação sem multi-tenant
      E um favorito do /app gravado
      Quando o dock é montado numa página do /app
      Então existe um grupo de rótulo exatamente igual a "{rótulo do /app}" com esse favorito
      E nenhum rótulo de grupo contém " · "
      E o tooltip desse favorito é exatamente o caminho da URL dele
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M60 | concatena o separador com a organização vazia | CT-08 | rótulo exato; nenhum contém " · " |
| M61 | sem organização na chave, o favorito cai em "Outros painéis" | CT-08 | o favorito está no grupo "{rótulo do /app}" |

## Regra R20. Indicar a organização não acrescenta consulta para montar o dock

> `RQ-25` · perfil **padrão** · técnica: **rastreio de não-efeito** (consultas), no padrão do CT-72 · cenário: **CT-06**
> Escopo: só consultas cuja pilha passa pelo código do dock. Código global fica para o diff do step 9 (ADV-14 recusado).

```gherkin
  Regra: A organização no rótulo não custa consulta

    Esquema do Cenário: [CT-06] montar o dock com favoritos de duas organizações não faz consulta de organização pelo código do dock
      Dado o usuário de duasOrganizacoes() com o dock liberado no /app
      E três favoritos gravados: dois da Acme e um da Globex
      Quando o painel da Acme no /app é pedido por GET, medido em pares simétricos com zero e com os três favoritos
      Então <afirmação>

      Exemplos:
        | afirmação                                                                                                                                              | # recorte |
        | toda consulta cuja pilha passa pelo código do dock (pilhaComCodigoDoDock) e lê a tabela de organizações ou o pivô de vínculo traz o id da Globex: é a conferência de acesso ao favorito de outra organização, que já existia; e há ao menos uma (controle positivo) | origem    |
        | com só os dois favoritos da Acme, a soma das consultas fica em no máximo a soma sem favoritos + 4 (no máximo uma por favorito, como no CT-72)                                                                                                | limite    |
```

> Pares simétricos (zero, três, três, zero), como no CT-72. O executor confirma no schema os nomes da tabela de organizações e do pivô.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M62 | busca o nome da organização por favorito (`Tenant::find($id)`) | CT-06 linhas origem e limite | consulta do dock na tabela de organizações; soma acima do limite |
| M63 | conta as organizações do usuário para decidir se mostra o nome | CT-06 linha origem | consulta do dock no pivô de vínculo |

## Regra R21. Cada favorito mostra, num tooltip, o caminho para onde leva

> `RQ-26` · perfil **padrão** · técnica: **EP** (painel × organização × tipo de destino × tenancy) · cenários: **CT-07**, **CT-08**

```gherkin
  Regra: O tooltip de cada favorito mostra o caminho do destino

    Esquema do Cenário: [CT-07] o favorito <favorito>, visto em <página>, tem tooltip igual ao caminho <caminho>
      Dado o usuário de duasOrganizacoes() com o dock liberado no /app e no /admin
      E os favoritos gravados: Projetos da Acme, Projetos da Globex, Dashboard da Acme, Dashboard da Globex e Usuários do /admin
      Quando o dock é montado em <página>
      Então o tooltip da action do favorito <favorito> é exatamente <caminho>
      E, no HTML do dock, o atributo de tooltip do próprio elemento do favorito (o que tem o href dele), decodificado, é exatamente esse caminho

      Exemplos:
        | favorito            | página                   | caminho                                                     | # partição                                |
        | Projetos da Acme    | o painel da Acme no /app | o caminho (sem esquema nem host) da URL de Projetos na Acme | organização corrente                      |
        | Projetos da Globex  | o painel da Acme no /app | o caminho da URL de Projetos na Globex                      | outra organização                         |
        | Usuários do /admin  | o painel da Acme no /app | o caminho da URL de Usuários no /admin                      | painel sem organização                    |
        | Dashboard da Acme   | o painel da Acme no /app | o caminho da URL da Page de dashboard na Acme               | Page avulsa (ADV-08)                      |
        | Dashboard da Globex | o painel da Acme no /app | o caminho da URL da Page de dashboard na Globex             | Page avulsa de outra organização (ADV-27) |
```

> **Notas de execução:**
> - `caminho` = `parse_url($url, PHP_URL_PATH)`. Para Resource, `getUrl('index', panel:, tenant:)`; para Page, `getUrl(panel:, tenant:)`, sempre com a organização **do favorito**.
> - Leitura (ADV-34): `getTooltip()` da action e, no HTML do componente, o atributo `x-tooltip` (presente em `vendor/filament/support/resources/views/components/icon-button.blade.php`) **do mesmo elemento que traz o `href` do favorito**. O conteúdo é decodificado (entidades HTML e a expressão do Alpine) antes da comparação exata.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M64 | tooltip com a URL completa (esquema e host) | CT-07 todas as linhas | igualdade exata com o caminho |
| M65 | tooltip com o rótulo do favorito | CT-07 todas as linhas | igualdade exata |
| M66 | caminho montado com a organização corrente | CT-07 linha "outra organização" | caminho com `globex` |
| M67 | tooltip só nos favoritos do `/app` | CT-07 linha "painel sem organização" | tooltip do `/admin` = caminho |
| M81 | caminho calculado só para Resource (`getUrl('index')`); a Page avulsa fica sem tooltip *(ADV-08)* | CT-07 linha "Page avulsa" | tooltip = caminho da Page |
| M82 | o caminho exige a organização e, sem multi-tenant, sai vazio *(ADV-09)* | CT-08 | tooltip = caminho do favorito |
| M87 | Page avulsa usa a organização corrente na URL do tooltip (o `getUrl()` da Page sem `tenant:` explícito), enquanto o Resource usa a do favorito *(ADV-27)* | CT-07 linha "Page avulsa de outra organização" | caminho com `globex`; o mutante dá `acme` |
| M94 | o tooltip de cada favorito sai com o caminho de outro (a closure no laço captura a última variável), ou fica num elemento que não é o do favorito *(ADV-34)* | CT-07 todas as linhas | o atributo do elemento com o `href` do favorito, decodificado, é o caminho **desse** favorito |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | a pilha não lê nem grava registro; no agrupamento, nada de organização sem acesso: CT-05 linhas d, e | Dock por organização (componente) |
| IDOR por entidade (organização) | CT-05 linhas d e e (rótulo, chave, URL e tooltip) | Dock por organização (componente) |
| Autorização exercida na ação | não se aplica: não há ação nova. Regressão: `AssistenteChatWidgetTest` CT-14, CT-16; `DockDeAtalhosTest` CT-B01 | — |
| Exposição em sessão bloqueada | CT-04 (com título sentinela), CT-10 | Telas sem dock nem chat |
| Saída do estado de bloqueio | CT-09 (destravar devolve dock e chat) | Telas sem dock nem chat |
| Redirecionamento como saída (ADV-35) | CT-04 linhas condicionais: destino afirmado e, quando é tela de autenticação, sem dock nem chat | Telas sem dock nem chat |
| Idempotência / concorrência / gravação | não se aplica | — |
| Domínio condicionado | CT-B06, CT-B08, CT-B11; CT-04 (tela × painel); CT-05 | vários |
| Estado × operação | CT-B04, CT-B06, CT-B07; CT-04/CT-09 (bloqueada × destravada) | Pilha; Telas |
| Cardinalidade 0/1/N | 0 dock: CT-B05; 2 e 3 botões: CT-B01, CT-B11; 1 e 2 organizações: CT-05 | Pilha; Dock por organização |
| Ausente ≠ nulo ≠ vazio | dock ausente: CT-B05; organização ausente: CT-08; excluída: CT-05 linha e | vários |
| Valor literal do requisito | 16 px, 30 px, 4,5:1, " · " | vários |
| Fronteira BVA real (dois lados e eixo x) | CT-B08 linhas d (não cruza), e (cruza), f (eixo x) | Pilha |
| Foco por teclado em elemento oculto | CT-B06 linhas 1 e 3 | Pilha |
| Controle positivo de asserção de ausência | CT-04 (marcador + uma asserção por agulha), CT-10, CT-05 linhas d e e | Telas; Dock por organização |
| Custo por request (N+1) | CT-06 | Dock por organização (custo) |
| Superfície Livewire (estado do framework) | CT-B03 (morph do chat); CT-11 (round-trip do dock) | Pilha; Telas (round-trip) |
| Estado do navegador | CT-B02 | Pilha |
| Tema e contraste | CT-B09, CT-B10 | Chat estilizado |
| Viewport de celular | CT-B01 | Pilha |
| Sem JavaScript | CT-03 (classes, regra exata dos 30 px, sem regra posterior) + lacuna declarada: o plugin não desliga JS | Guarda |
| CSS global em outra tela | CT-02 | Guarda |
| Tela de visitante sem sessão | não se aplica (evidência em Cobertura parcial); com sessão: linhas condicionais do CT-04 | Telas sem dock nem chat |
| Navegação SPA (ADV-04, recusado) | não se aplica: `grep -rn "spa(" app/Providers` vem vazio | — |
| Painel registrado depois (P-02) | lacuna declarada: o executor tenta `painelRegistradoEmTeste()`; `/infra` mata M05 | Pilha |
| Paginação, timezone, unicode, mass assignment, upload, dinheiro | não se aplica | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | toda classe do chat tem regra sob o escopo | R11 | EP | Guarda da folha do chat e posição sem JS | Pest feature HTTP | `tests/Kit/AssistenteChatCssTest.php` | M36, M38 |
| CT-02 | nenhuma regra fora do escopo | R11 | EP | Guarda da folha do chat e posição sem JS | Pest feature HTTP | `tests/Kit/AssistenteChatCssTest.php` | M37 |
| CT-03 | HTML servido e folhas posicionam sem JS, voltar a 30 px por regra exata | R12 | EP | Guarda da folha do chat e posição sem JS | Pest feature HTTP | `tests/Kit/AssistenteChatCssTest.php` | M39, M40, M84, M91, M92 |
| CT-04 | bloqueio, login e Auth Designer sem dock nem chat; condicionais; controle positivo | R16, R17 | EP + controle positivo | Telas sem dock nem chat | Pest feature HTTP | `tests/Kit/TelasSemDockNemChatTest.php` | M49–M54, M68, M73, M74, M95 |
| CT-05 | um grupo por organização, na ordem gravada, conjuntos exatos, sem organização sem acesso | R18, R22 | EP | Dock por organização (componente) | componente Livewire/Filament | `tests/Tenancy/DockPorOrganizacaoTest.php` | M55–M59, M70–M72, M80, M83, M93 |
| CT-06 | a organização no rótulo não custa consulta | R20 | rastreio de não-efeito | Dock por organização (custo) | Pest feature HTTP | `tests/Tenancy/DockPorOrganizacaoTest.php` | M62, M63 |
| CT-07 | tooltip com o caminho do destino, lido no próprio elemento | R21 | EP | Dock por organização (componente) | componente Livewire/Filament | `tests/Tenancy/DockPorOrganizacaoTest.php` | M64–M67, M81, M87, M94 |
| CT-08 | sem multi-tenant, rótulo só do painel e tooltip do caminho | R19, R21 | EP | Dock sem multi-tenant | componente Livewire/Filament | `tests/Kit/DockSemOrganizacaoTest.php` | M60, M61, M82 |
| CT-09 | destravar devolve dock e chat ao `/app` | R16, R17 | 2-switch | Telas sem dock nem chat | Pest feature HTTP | `tests/Kit/TelasSemDockNemChatTest.php` | M75, M76 |
| CT-10 | bloqueio sem dock nem chat com multi-tenant | R16, R17 | EP + controle positivo | Telas sem dock nem chat (multi-organização) | Pest feature HTTP | `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` | M68, M73, M77 |
| CT-11 | round-trip do dock numa página comum não o tira da tela | R16 | EP | Telas sem dock nem chat (round-trip) | componente Livewire/Filament | `tests/Kit/TelasSemDockNemChatTest.php` | M85 |
| CT-B01 | pilha no canto inferior direito, rolada, 16 px | R1, R2, R3, R15 | EP + valor literal | Pilha e reação ao dock | browser | `tests/Browser/PilhaDeBotoesFlutuantesTest.php` | M01–M11, M41, M42 |
| CT-B02 | pilha no carregamento com posição lembrada | R4 | EP | Pilha e reação ao dock | browser | idem | M12, M13 |
| CT-B03 | pilha sobrevive ao morph do chat | R4 | 2-switch | Pilha e reação ao dock | browser | idem | M14 |
| CT-B04 | cantos fora da área: posições sem o dock | R5 | EP + 2-switch | Pilha e reação ao dock | browser | idem | M15, M16, M17 |
| CT-B05 | sem dock: alturas de hoje, voltar a 30 px | R6 | EP | Pilha e reação ao dock | browser | idem | M18, M19, M20, M69 |
| CT-B06 | dock aberto: ocultos da vista, do clique e do foco sobre a pilha; visíveis no canto esquerdo | R7 | tabela de decisão | Pilha e reação ao dock | browser | idem | M21, M22, M27, M86, M90 |
| CT-B07 | dock fechado: voltam aos lugares | R7 | 2-switch | Pilha e reação ao dock | browser | idem | M23, M24 |
| CT-B08 | dock solto: reage se cruza (inclusive por poucos px e pelo eixo x), não reage longe nem logo acima | R8 | BVA de 2 valores | Pilha e reação ao dock | browser | idem | M25, M43, M44, M78, M79, M88, M89 |
| CT-B09 | botão do chat fixo, redondo, primário | R9 | EP | Chat estilizado | browser | `tests/Browser/AssistenteChatEstiloTest.php` | M28–M31 |
| CT-B10 | slide-over lateral, opaco, no tema, ≥ 4,5:1 | R10, R14 | EP + valor literal | Chat estilizado | browser | idem | M32–M35, M46, M47 |
| CT-B11 | barra: visíveis, acima da barra, 16 px, eixo do gatilho | R13 | EP | Pilha e reação ao dock | browser | `tests/Browser/PilhaDeBotoesFlutuantesTest.php` | M26, M45, M48 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Pilha depois de redimensionar o viewport | nenhum mutante plausível que só ele mataria |
| Dock cruzando **só** o espaço entre dois botões | geometria inviável: o gatilho (≥ 44 px) é mais alto que o espaço de 16 px; o ramo é coberto por CT-B08 linha a, e a borda pelas linhas d e e |
| `/admin` no celular; dock aberto no `/admin` | matam os mesmos mutantes que as linhas do `/app` |
| Folha linkada no `<head>` | CT-B09 mata M28 pelo efeito |
| Contraste de outros textos do slide-over | RQ-17 fixa só o título |
| CT-B para "dock ausente na tela de bloqueio" | Q13: não renderizado; o HTML servido prova mais barato (CT-04) |
| Linhas de 2FA no `/infra` e de convite fora do `/app` | `/infra` já está no bloqueio (M49); o convite só existe no `/app` |
| Linhas de telas de visitante **sem sessão** no CT-04 | ausência em mundo vazio (gate, item 7); com sessão, viraram linhas condicionais |
| Tooltip no layout barra e no speed-dial separadamente | o tooltip é da action, não do layout |
| Destravar no `/admin` e no `/infra` | M75 e M76 dependem só da sessão, não do painel; o `/app` é o único com os dois componentes |
| Eixo da pilha com o dock deslocado da coluna (CT-B08 linha f) | o requisito não determina a qual eixo a pilha se alinha nesse caso; cenário sobre a suposição é proibido |

## Sem CT-B

Não se aplica: há costura `browser`, e o `05` existe.

> **Divergência declarada com a skill.** A costura `Pest feature HTTP` da skill diz `tests/Feature`, mas as guardas do kit vivem em `tests/Kit` e os casos multi-organização em `tests/Tenancy` (convenção do projeto, que vence).
