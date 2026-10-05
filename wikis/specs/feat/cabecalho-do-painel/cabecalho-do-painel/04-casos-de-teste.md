# Casos de Teste — feat/cabecalho-do-painel: cabeçalho dos painéis personalizável pelas Configurações da aplicação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas e Superfície de UI, colados no despacho) · Superfície Livewire: `02-decisoes-arquiteturais.md` (colada no despacho)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (ela não existe).
> Derivação: sub-agente `analista` (step 7), skill `feature-test-design` 1.16.0.
> Revisão adversarial: FEITA — 35 achados (ADV-01..ADV-35), 30 aceitos, 1 rejeitado (ADV-13 como mutante; a linha de 255 chars entra), 4 duplicados (ADV-31=10, ADV-35=13, ADV-20=13, ADV-25..30 cobertos). Disparo: Impacto 3 na área "composição da marca". Fechamento gravado pelo `analista` em 2026-10-05 com as decisões da sessão; cada regra/cenário tocado traz *(alterado em 2026-10-05: ADV-nn)*.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Composição da marca (projeto \| painel \| logo) | 2 — integra com `brandLogo()` dos três painéis, tenancy e identidade | 3 — texto de terceiro (nome da organização, do projeto) vai para o layout de todo usuário; nome/logo de outra organização seria vazamento entre organizações | 6 | padrão (+ revisão adversarial pelo Impacto 3) |
| Bloco do usuário (nome + detalhe) | 2 — integra com `papelDoPainel()` e o menu do usuário | 2 — informação errada de papel na tela; reversível; o nome do usuário é texto livre (coberto em R9) | 4 | padrão |
| Opções nas Configurações da aplicação | 2 — integra com a SettingsPage existente, migração de settings, config e `.env` | 2 — retrabalho manual; o pior caso é a tela inteira de configurações travar (precedente QA-01) | 4 | padrão |
| Layout e tema no navegador | 1 — CSS isolado | 2 — bloco sumido/sobrando, logo errada no tema | 2 | mínimo (técnica escalada para BVA 2-valores na largura 768: EP não distingue `<` de `<=` no breakpoint) |

- Técnicas aplicadas: EP, BVA 2-valores (largura 767/768), tabela de decisão (três interruptores; detalhe × papel; segmento do painel × organização × projeto), códigos distintos por propriedade (gravação), rastreio de não-efeito na recusa
- Cenários: 35 · Regras: 23 · Mutantes previstos: 106 · Sem matador: 0 *(recalculado em 2026-10-05 após a revisão adversarial, a R25 da sessão e o QA ciclo 1 — CT-34, CT-35; mutantes trazidos pela revisão, marcados `M-nn — revisão adversarial`, não contam para o teto de 5 por regra)*
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho), recalculado após a revisão adversarial, a R25 e o QA (ciclos 1 e 2): Cenários 35, Regras 23, Mutantes 106, Sem matador 0. Os CT-B e os mutantes deles estão no 05. -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | Settings (5 propriedades novas) + migração de settings; bloco `cabecalho` em `config/kit.php`; 5 chaves no `.env.example`; enum do detalhe; classe de suporte da composição; 2 blades; CSS; 3 PanelProviders; seção nova na SettingsPage existente | CT-19, CT-20, CT-21 |
| F | compor a marca com até três segmentos e separadores; trocar o rótulo do painel pela organização ativa; omitir o segmento repetido; exibir nome + papel/e-mail do usuário; gravar e alinhar as opções; coagir valor fora do domínio | CT-01…CT-18, CT-21…CT-25 |
| D | nome do projeto (texto livre do admin), nome da organização (texto livre, **dado de outra organização**), nome e e-mail do usuário (texto livre), papel por painel/organização (0/1/N papéis), logo clara/escura (presente, ausente, órfã), detalhe (`perfil`, `email`, fora do domínio, vazio, tipo errado) | CT-05…CT-11, CT-14, CT-15, CT-18, CT-22, CT-23 |
| I | layout de toda tela autenticada de `/admin`, `/infra`, `/app` e `/app/{slug}`; tela pública `/admin/login` (marca sem usuário autenticado); tela `/admin/configuracoes-da-aplicacao` (Livewire `$data` + `save()`); `.env` | CT-03, CT-04, CT-21…CT-28, CT-32, CT-33 |
| P | largura da janela (breakpoint 768 px), tema claro/escuro (classe `dark`), disco `public` (arquivo da logo), multibyte (acento/emoji) | CT-08, CT-10, CT-B01, CT-B02 |
| O | personas `admin`, `infra`, `master_global`, `admin_app`, `panel_user`, pessoa vinculada sem papel na organização, admin sem a permissão, papéis em contextos diferentes (global × organização), visitante não autenticado; instalação com e sem multi-organização; projeto derivado que nunca liga as opções (RQ-06) | CT-01, CT-12, CT-14, CT-15, CT-19, CT-24, CT-26…CT-31 |
| T | não se aplica: sem prazo, agendamento ou concorrência; a opção gravada vale a partir do alinhamento da config, mecanismo existente do kit (`alinharConfiguracoesDoKit()`), exercitado em CT-21. A troca de organização na mesma sessão está coberta em CT-04 (linha "depois de abrir a Acme") | CT-04, CT-21 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — com os três segmentos desligados, a marca é a de hoje *(alterado em 2026-10-05: ADV-05, ADV-16..19)* | composição (padrão) | RQ-06, P-02 | EP | CT-01, CT-27 |
| R2 — segmentos ligados aparecem na ordem projeto \| painel \| logo, com separador só entre segmentos presentes | composição (padrão) | RQ-01, P-02 | tabela de decisão (3 interruptores, 7 regras) | CT-02 |
| R3 — sem organização ativa, o segmento do painel é o rótulo do painel, omitido quando idêntico ao nome do projeto ligado | composição (padrão) | RQ-01, P-01 | tabela de decisão | CT-03 |
| R4 — com organização ativa, o segmento do painel é o nome da organização aberta *(alterado em 2026-10-05: ADV-15)* | composição (padrão) | RQ-02, P-01 | tabela de decisão + EP | CT-04 |
| R5 — o nome do projeto é o da Identidade; a logo é a da Identidade, com a variante escura só na marca separada | composição (padrão) | P-07, P-08 | EP | CT-05, CT-06 |
| R6 — a logo da composição é sempre a da instalação, nunca a de uma organização *(alterado em 2026-10-05: ADV-02)* | composição (padrão) | P-07, P-12 | EP | CT-07 |
| R7 — logo ligada sem logo resolvida é omitida: nunca imagem quebrada, nunca marca vazia *(alterado em 2026-10-05: ADV-03, ADV-04)* | composição (padrão) | P-07, P-11 | EP | CT-08 |
| R8 — a composição substitui a marca em todo lugar onde o painel a desenha *(alterado em 2026-10-05: ADV-21)* | composição (padrão) | P-06 | EP (contagem calibrada) | CT-09 |
| R9 — texto exibido no cabeçalho sai escapado e íntegro *(alterado em 2026-10-05: ADV-01, ADV-08, ADV-13)* | composição (padrão) | RQ-01, RQ-02, RQ-03 | EP (marcação em texto e em atributo × multibyte × comprimento) | CT-10, CT-11 |
| R10 — o bloco do usuário nasce desligado; ligado, mostra o nome antes do menu do usuário, que continua *(alterado em 2026-10-05: ADV-16..19)* | bloco do usuário (padrão) | RQ-03, RQ-06, P-09 | EP | CT-12, CT-13 |
| R11 — o detalhe abaixo do nome é o papel do painel corrente (rótulo legível) ou o e-mail *(alterado em 2026-10-05: ADV-23, ADV-33)* | bloco do usuário (padrão) | RQ-04, P-03 | tabela de decisão (detalhe × papel × painel) | CT-14 |
| R12 — com organização ativa, o papel é o da organização aberta; sem papel nela, não há linha de baixo *(alterado em 2026-10-05: ADV-23)* | bloco do usuário (padrão) | RQ-04, P-03 | tabela de decisão + cardinalidade 0/1/N | CT-15 |
| R13 — o detalhe nasce em perfil e só tem duas opções | bloco do usuário + configurações (padrão) | P-05 | EP | CT-16, CT-17 |
| R14 — detalhe fora do domínio gravado por fora da tela não derruba a página nem vira texto | bloco do usuário (padrão) | P-05 (`@premissa` coerção — Superfície Livewire do 02) | EP (partições inválidas isoladas) | CT-18 |
| R15 — valores de fábrica: quatro interruptores desligados e detalhe em perfil, na settings, na config e no `.env.example` | configurações (padrão) | RQ-06, P-02, P-05, P-10 | EP + valor literal do requisito | CT-19, CT-20 |
| R16 — a tela grava as cinco opções e a config as reflete, nos dois sentidos *(alterado em 2026-10-05: ADV-07)* | configurações (padrão) | RQ-05, P-10 | códigos distintos por propriedade (3 linhas) + transição ligado → desligado | CT-21 |
| R17 — o detalhe enviado pelo cliente fora do domínio é recusado e não é gravado | configurações (padrão) | P-05, RQ-05 | EP (inválidas isoladas) | CT-22, CT-23 |
| R18 — a tela de configurações continua gravando com o cabeçalho desligado e com valor de fora gravado *(alterado em 2026-10-05: ADV-14)* | configurações (padrão) | RQ-06, D8 do `01` | EP | CT-24, CT-25 |
| R19 — só quem tem a permissão da tela grava as opções *(alterado em 2026-10-05: ADV-22, ADV-24)* | configurações (padrão) | RQ-05 | matriz papel × ação (célula negativa) | CT-26 |
| R22 — tela pública não renderiza composição nem bloco do usuário *(criada em 2026-10-05: ADV-06)* | composição (padrão) | RQ-06, `00` §Fora de Escopo (telas públicas de autenticação) | EP | CT-28 |
| R23 — o papel exibido é o do contexto do painel corrente, com `master_global` vencendo *(criada em 2026-10-05: ADV-09, ADV-10, ADV-32, ADV-34)* | bloco do usuário (padrão) | RQ-04, P-03 | tabela de decisão (contexto global × organização × master) | CT-29, CT-30, CT-31 |
| R24 — a seção "Cabeçalho dos painéis" expõe as cinco opções, e o detalhe só com o bloco ligado *(criada em 2026-10-05: ADV-11, ADV-12)* | configurações (padrão) | P-10, P-13 | EP | CT-32, CT-33 |
| R25 — toda classe das duas blades do cabeçalho tem regra no CSS do kit, escopada *(criada em 2026-10-05: rule `css-filament.md`)* | layout (padrão) | RQ-01, RQ-03, RQ-08 | EP + controle positivo | CT-34 |
| R20 — o bloco do usuário some abaixo de 768 px e fica à esquerda do avatar | layout (mínimo, BVA escalado) | P-04, P-09 | BVA 2-valores | CT-B01 (no `05`) |
| R21 — na marca separada, a composição mostra a variante escura só no tema escuro | layout (mínimo) | P-07 | EP (tema) | CT-B02 (no `05`) |
| R26 — a composição cabe numa linha na barra lateral aberta, cortando o texto com reticências *(criada em 2026-10-05: QA-04/QA-10 — regra própria da P-06 revisada)* | layout (mínimo, escalado para BVA) | P-06 | BVA (nome longo) | CT-B03 (no `05`) |

- Técnica escalada: R20 usa BVA 2-valores numa área de perfil mínimo — EP sozinho não distingue `max-width: 768px` de `max-width: 767px`.
- RQ-07 — sem cenário: é cláusula de **processo** (a wiki existir e a branch ser criada), verificável pela própria wiki, não por teste.
- RQ-08 — sem cenário próprio: é a exigência de que este conjunto exista e passe; coberta pelo conjunto inteiro.
- Nenhuma `RQ` está `aberta` no `00`. As perguntas desta derivação foram resolvidas: Q14 → P-11, Q16 → P-12, Q17 → P-13 (premissas vigentes do `00`) e Q15 → D8 do `01`. As direções que esperavam resposta ganharam cenário (R6, R7, R24, CT-25). *(alterado em 2026-10-05: fechamento da revisão adversarial)*

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Render do cabeçalho na instalação | R1 (CT-01), R2, R3, R5, R7, R8, R9 (CT-10), R10, R11, R13 (CT-16), R14, R15, R22 | Pest feature HTTP | existente — o padrão de `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` (GET de página cheia: `brandLogo` e render hook só saem no layout, que teste de componente não atravessa) + `regiaoDoHeader()` de `tests/Pest.php`; arquivo novo `tests/Kit/CabecalhoDoPainelTest.php` | o `Então` afirma elemento do layout renderizado e valor gravado/config; nenhum depende de JS | sessão (dev), 2026-10-05 |
| G2 — Tela de Configurações, seção Cabeçalho | R13 (CT-17), R16, R17, R18, R19, R24 | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` e CT-06/CT-08 de `tests/Kit/LogoDarkModeTest.php` (`Livewire::test(ConfiguracoesDoKit::class)->fillForm()->call('save')`); arquivo novo `tests/Kit/CabecalhoDoPainelTelaTest.php` | gravação, validação, autorização e `$wire.set` da SettingsPage | sessão (dev), 2026-10-05 |
| G3 — Render com organização ativa | R1 (CT-27), R4, R6, R9 (CT-11), R12, R23 | Pest feature HTTP | existente — `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` (GET `/app/{slug}` com `permission.teams`); arquivo novo `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | organização na rota e `admin_app` só existem em `tests/Tenancy` (`.ai/rules/testes.md`) | sessão (dev), 2026-10-05 |
| G4 — Layout e tema no navegador | R20, R21, R26 | browser | existente — `tests/Browser/LogoDarkModeTest.php` (uma visita por tema, `getComputedStyle`); arquivo novo `tests/Browser/CabecalhoDoPainelTest.php` | ocultação por media query, posição relativa ao avatar e troca clara/escura por classe `dark` são CSS: só o navegador calcula | sessão (dev), 2026-10-05 |

## Fronteira com o Plano

| Item do PRD/02 | Recusado como oráculo porque | Destino |
|---|---|---|
| classes `kit-cabecalho`, `kit-cabecalho__projeto/__sep/__painel/__logo`, `kit-usuario`, `kit-usuario__nome/__detalhe` | escolha de implementação | **seletor** do cenário (extração de região por `regiaoDoHeader()`), nunca o que se afirma |
| nomes das propriedades `cabecalho_*`, chaves `kit.cabecalho.*`, variáveis `KIT_CABECALHO_*`, valores `perfil`/`email` | escolha de implementação; o requisito determina só que existem 5 opções (P-02, RQ-03/04), os defaults (P-02, P-05) e o par no `.env` (P-10) | detalhe do cenário; o oráculo é o default literal (desligado / perfil) e a correspondência opção → efeito na tela |
| `DetalheDoUsuario::coagir()` → `perfil` para valor fora da lista | mecanismo do 02; o `00` só diz que o detalhe nasce em perfil (P-05) | CT-18 escrito no mecanismo, marcado `@premissa`; o invariante (página não cai, valor cru não vira texto) vale qualquer que seja o mecanismo. Na **tela**, a coerção ao carregar é D8 do `01` (resposta de Q15) e vira oráculo em CT-25 *(alterado em 2026-10-05: ADV-14)* |
| `Select` visível só com o bloco ligado (`->visible()`, `->live()`) | era comportamento visível só no PRD; **P-13** (resposta de Q17) o fixou no `00` | oráculo de CT-32 *(alterado em 2026-10-05: ADV-11)* |
| rótulos das opções do select ("E-mail", "Perfil") e o título "Cabeçalho dos painéis" | o título está em P-10 (cláusula); os rótulos das opções só o PRD determina | o título é asserido em CT-33 *(alterado em 2026-10-05: ADV-12)*; os rótulos das opções, não |
| `@media (min-width: 768px)` | o **número 768** está em P-04 → é cláusula; *onde* mora (CSS do kit) é implementação | CT-B01 usa 767/768 literais |
| `Paineis::rotulo()`, `Papeis::rotulo()`, `User::papelDoPainel()`, `IdentidadeDoKit::logo()/logoEscura()` | fontes **existentes** que a feature consome | os literais vêm do `00` ("Administração", "Infraestrutura", "Administrador Geral") e, para os demais papéis, do rótulo que o cabeçalho do menu do usuário já mostra (P-03), escrito literal: "Admin", "Infra", "Painel App", "Administrador App" *(alterado em 2026-10-05: ADV-23)* |
| caractere do separador ("\|") | o `00` cita "\|" no formato, mas o estilo é fora de escopo | o cenário conta separadores, não afirma o caractere |

**Perguntas geradas pela derivação — resolvidas** *(alterado em 2026-10-05: fechamento da revisão adversarial)*. A sessão renumerou `Q?1..Q?4` para Q14..Q17; nenhuma segue aberta, e as direções ganharam cenário.

| Q | Raia | Pergunta (resumo) | Resolução | Cenários |
|---|---|---|---|---|
| Q14 | requisito | segmento "logo da marca" ligado sem logo (ou com o arquivo apagado); marca vazia quando é o único ligado? | **P-11** (vigente no `00`): o segmento é omitido; composição sem segmento visível cai para a marca de hoje — nunca imagem quebrada, nunca marca vazia | CT-08 |
| Q15 | desenho | detalhe fora da lista gravado direto: a tela carrega coagido e grava, ou recusa? | **D8** do `01`: a tela coage para `perfil` ao carregar (`mutateFormDataBeforeFill`) e grava; depois de salvar, o gravado é `perfil` | CT-25 |
| Q16 | requisito | no `/app` de organização com logo própria, qual logo o segmento mostra? | **P-12** (vigente no `00`): a da **instalação**. A recomendação escrita na derivação (logo da organização) partia de um fato errado — a marca do `/app` hoje mostra a logo da instalação, e a da organização só aparece na tela de bloqueio e no cadastro dela | CT-07 |
| Q17 | requisito | o seletor "e-mail ou perfil" fica escondido com o bloco desligado? | **P-13** (vigente no `00`): só aparece com o bloco ligado | CT-32 |

## Setup Global

### Personas
- `admin` — `usuarioDoKit('admin')` (tem `View:ConfiguracoesDoKit`; abre `/admin`)
- `master_global` — `usuarioCom('master_global')` (entra em `/admin`, `/infra`, `/app`)
- `admin e infra` — `usuarioDoKit('admin')` e **depois** `->assignRole('infra')` (a ordem importa: o papel atribuído primeiro é o que o mutante "primeiro papel" mostraria no `/infra`)
- `admin sem a permissão` — `usuarioDoKit('admin')` + `Role::findByName('admin')->revokePermissionTo('View:ConfiguracoesDoKit')` + `forgetCachedPermissions()` (mesma persona de `ConfiguracoesDoKitTelaTest`)
- Tenancy: `usuarioComPapel('admin_app', $acme)` + `papelNaOrganizacao($u, 'panel_user', $globex)` + `$u->tenants()->attach(...)`; pessoa **sem papel** na Globex: papel só na Acme, vinculada às duas (arranjo de CT-03 de `CabecalhoDoMenuDoUsuarioTenancyTest`)
- `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` em todo arquivo (`beforeEach`)
- Tenancy, papéis em contextos diferentes *(alterado em 2026-10-05: ADV-09, ADV-10, ADV-34)*: `papelNaOrganizacao($u, 'admin')` (contexto global) + `papelNaOrganizacao($u, 'admin_app', $acme)`; `master_global` com e sem `panel_user` na Acme
- Visitante: nenhum `actingAs()` (CT-28)

### Fatos de rótulo e de comportamento já decididos *(alterado em 2026-10-05: fechamento da revisão adversarial)*
- Rótulos legíveis (`Papeis::rotulo()`, fallback `Str::headline`): `admin` → "Admin", `infra` → "Infra", `panel_user` → "Painel App", `admin_app` → "Administrador App", `master_global` → "Administrador Geral". Asserir por igualdade no elemento de detalhe — "Administrador App" contém "Admin".
- Sem usuário autenticado (tela pública, ex.: `/admin/login`), a composição não é renderizada: `segmentos()` devolve `null` e a marca é a de hoje (decisão da sessão, ADV-06).
- A tela de configurações coage o detalhe fora da lista para `perfil` ao carregar (`mutateFormDataBeforeFill`); depois de salvar, o gravado é `perfil` (D8, ADV-14).
- A logo da composição é sempre a da instalação, inclusive no `/app` de organização com logo própria (P-12).

### Fixtures
- Opções do cabeçalho: `gravarConfiguracao('cabecalho_*', valor)` + `alinharConfiguracoesDoKit()` — nunca `config()->set()` direto nos cenários de render, para que a cadeia settings → config seja a real. Os cenários de G2 gravam pela tela.
- Nome do projeto discriminante: `gravarConfiguracao('nome_da_aplicacao', 'Projeto Ômega')` + alinhar — diferente do `APP_NAME` do ambiente ("Starter Kit" no `.env.example`) e de todo rótulo de painel.
- Logo: `Storage::fake('public')` com arquivo pequeno + `gravarConfiguracao('logo', 'kit/logo-ct.png')` (e `logo_dark`, `unifica_logo_marca`) + alinhar, como `tests/Kit/LogoDarkModeTest.php`. No `05`, disco `public` **real** (o navegador baixa a URL).
- Região: `regiaoDoHeader($html, 'kit-cabecalho')`, `regiaoDoHeader($html, 'kit-usuario')` e, para a marca do Filament, `regiaoDoHeader($html, 'fi-logo')` — **obrigatório** asserir dentro da região: o nome do usuário, o e-mail e o rótulo do papel já aparecem no cabeçalho do menu do usuário, e `assertSee` na página inteira passa com a feature removida. Para contar ocorrências (CT-03), contar sobre o HTML inteiro pela classe; CT-09 conta regiões `.fi-logo` e as que contêm `.kit-cabecalho` (a função devolve só a primeira ocorrência — percorrer todas).
- Linha de base do default (CT-01, CT-12; ADV-16..19): medir o HTML no estado de fábrica **primeiro**, depois gravar explicitamente as cinco opções em desligado/`perfil`, alinhar, medir de novo e comparar a contagem do nome ("Projeto Ômega"/"Maria Ômega") — independe do nome de classe da implementação.
- Tenancy: `tenant('Acme','acme')`/`tenant('Globex','globex')` ou `Tenant::factory()`; entre visitas a organizações diferentes no mesmo cenário, `fronteiraDeRequest()`.

### Fakes
- Nenhum de efeito (a feature não envia, não enfileira, não notifica). `Storage::fake('public')` só nos cenários de logo de G1/G3.

### Estratégia de DB
- `RefreshDatabase` global por pasta em `tests/Pest.php` (`Kit`, `Tenancy`, `Browser`).

### Regras de arnês herdadas (`.ai/rules/testes.md`)
- `toContain()` não recebe mensagem; asserção de ausência com mensagem usa `assertStringNotContainsString`.
- `admin_app` só em `tests/Tenancy`; `noPainelBootado()` não serve para o `/app` (aqui todo render é por GET real, então não se aplica).
- Helper usado por mais de um arquivo vai para `tests/Pest.php`.
- Precedência: nenhuma divergência entre esta skill e as rules do projeto nesta derivação.

---

## Regra R1 — com os três segmentos desligados, a marca é a de hoje *(alterado em 2026-10-05: ADV-05, ADV-16, ADV-17, ADV-18, ADV-19)*

> `RQ-06`, `P-02` · perfil **padrão** · técnica: **EP** (partições da identidade: logo única, marca separada, sem logo; partições de painel: `/admin`, `/infra`, `/app` sem organização, `/app/{slug}` com organização)
>
> Oráculo (ADV-16..19): a afirmação é feita **dentro da região da marca do Filament** (`.fi-logo`) e por **contagem comparada a uma linha de base medida no próprio teste** — as opções gravadas explicitamente desligadas (`gravarConfiguracao(..., false)` nas quatro e `perfil` no detalhe). O default "sem nada gravado pelo teste" tem de produzir o mesmo número de ocorrências de "Projeto Ômega" que a linha de base; isso independe do nome de classe que a implementação escolher. Ordem de execução: medir o default primeiro, depois gravar a linha de base.

```gherkin
# language: pt
Funcionalidade: Cabeçalho dos painéis personalizável

  Regra: Quem não liga nenhum segmento vê a marca de hoje

    Esquema do Cenário: [CT-01] a marca de fábrica não muda com a feature instalada
      Dado uma instalação com os valores de fábrica do cabeçalho, sem nenhuma opção gravada pelo teste
      E o nome do projeto "Projeto Ômega" e a identidade com <identidade>
      E a linha de base: com as cinco opções gravadas explicitamente no estado desligado/perfil, "Projeto Ômega" aparece B vezes no HTML de <painel>
      Quando o administrador geral abre o painel <painel> no estado de fábrica
      Então "Projeto Ômega" aparece B vezes no HTML
      E nenhuma composição do cabeçalho é renderizada
      E a região da marca do Filament (`.fi-logo`) mostra <marca>

      Exemplos:
        | painel | identidade                                       | marca                                                        | # partição            |
        | /admin | a logo "kit/logo-ct.png" e a marca unificada     | a imagem da logo "kit/logo-ct.png" e nenhuma variante escura | logo única            |
        | /admin | a logo e a variante escura, com a marca separada | a imagem da logo e a imagem da variante escura               | marca separada        |
        | /admin | nenhuma logo enviada                             | "Projeto Ômega" em texto                                     | sem logo              |
        | /infra | a logo "kit/logo-ct.png" e a marca unificada     | a imagem da logo "kit/logo-ct.png"                           | outro painel          |
        | /app   | a logo "kit/logo-ct.png" e a marca unificada     | a imagem da logo "kit/logo-ct.png"                           | painel do negócio     |

    Cenário: [CT-27] com organização ativa, a marca de fábrica não muda *(criado em 2026-10-05: ADV-05)*
      Dado uma instalação multi-organização com os valores de fábrica do cabeçalho, sem nenhuma opção gravada pelo teste
      E a logo da instalação "kit/logo-ct.png" e a pessoa com papel na Acme
      Quando a pessoa abre /app/acme
      Então o layout não tem composição do cabeçalho nem bloco do usuário
      E a imagem da região da marca do Filament (`.fi-logo`) aponta para "kit/logo-ct.png"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `brandLogo()` devolve sempre a composição (envoltório vazio ou só com a logo) mesmo com tudo desligado | CT-01 | qualquer linha: nenhum `.kit-cabecalho` no HTML; o mutante o renderiza |
| M2 | `brandLogo()` passa a devolver `HtmlString` vazia quando não há logo, e o fallback para o nome em texto some | CT-01 | linha "sem logo": a região `.fi-logo` contém "Projeto Ômega"; o mutante a deixa vazia |
| M3 | `darkModeBrandLogo()` substituído pela composição e a variante escura some no default | CT-01 | linha "marca separada": a URL de `logo_dark` está na região `.fi-logo`; o mutante não a emite |
| M4 (M-04 — revisão adversarial) | default de algum interruptor `true` (migração ou config), com a composição sob classe diferente da prevista | CT-01 | contagem de "Projeto Ômega" = B (linha de base explícita); o mutante soma as ocorrências do segmento |
| M5 (M-05 — revisão adversarial) | o provider do `/infra` ou do `/app` lê a opção errada e compõe no default | CT-01 | linhas `/infra` e `/app`: nenhum `.kit-cabecalho` e a `<img>` da logo na `.fi-logo`; o mutante compõe |
| M6 (M-06 — revisão adversarial) | ramo de tenancy do `/app` monta a composição ou o bloco no default | CT-27 | nenhum `.kit-cabecalho` nem `.kit-usuario` em `/app/acme`; o mutante os emite |

---

## Regra R2 — segmentos ligados aparecem na ordem projeto | painel | logo, com separador só entre segmentos presentes

> `RQ-01`, `P-02` · perfil **padrão** · técnica: **tabela de decisão** (3 interruptores independentes → 7 combinações não vazias; a 8ª, tudo desligado, é R1). No `/admin`, onde o rótulo do painel ("Administração") difere do nome do projeto, para P-01 não interferir.

```gherkin
  Regra: Cada interruptor liga um segmento, na ordem do pedido

    Esquema do Cenário: [CT-02] a composição respeita os interruptores, a ordem e os separadores
      Dado o nome do projeto "Projeto Ômega" e a logo "kit/logo-ct.png" na identidade
      E os segmentos nome do projeto <projeto>, nome do painel <painel> e logo da marca <logo>
      Quando o administrador abre o painel /admin
      Então a composição mostra, nesta ordem, <segmentos>
      E a composição tem <separadores> separadores

      Exemplos:
        | projeto    | painel     | logo       | segmentos                                      | separadores | # regra |
        | ligado     | desligado  | desligado  | "Projeto Ômega"                                | 0           | 100     |
        | desligado  | ligado     | desligado  | "Administração"                                | 0           | 010     |
        | desligado  | desligado  | ligado     | a imagem da logo                               | 0           | 001     |
        | ligado     | ligado     | desligado  | "Projeto Ômega", "Administração"               | 1           | 110     |
        | ligado     | desligado  | ligado     | "Projeto Ômega", a imagem da logo              | 1           | 101     |
        | desligado  | ligado     | ligado     | "Administração", a imagem da logo              | 1           | 011     |
        | ligado     | ligado     | ligado     | "Projeto Ômega", "Administração", a imagem da logo | 2       | 111     |
```

A ordem é afirmada pela posição na região: `strpos('Projeto Ômega') < strpos('Administração') < strpos('<img')`. Segmento desligado é afirmado **ausente** da região.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | ordem painel \| projeto \| logo | CT-02 | linha 111: posição de "Projeto Ômega" < posição de "Administração"; o mutante inverte |
| M2 | separador emitido depois de cada segmento (n em vez de n−1) | CT-02 | linha 100: 0 separadores; o mutante emite 1 |
| M3 | separador entre projeto e o seguinte só quando o **painel** está ligado | CT-02 | linha 101: 1 separador; o mutante emite 0 |
| M4 | interruptores trocados (o do projeto liga o painel) | CT-02 | linha 100: região contém "Projeto Ômega" e não "Administração"; o mutante mostra "Administração" |
| M5 | composição só quando os três estão ligados (AND); senão, marca de hoje | CT-02 | linha 110: a região existe com dois segmentos; o mutante devolve a marca padrão |

---

## Regra R3 — sem organização ativa, o segmento do painel é o rótulo do painel, omitido quando idêntico ao nome do projeto ligado

> `RQ-01`, `P-01` · perfil **padrão** · técnica: **tabela de decisão** (painel × interruptor do projeto). Suíte sem multi-organização: o `/app` não tem organização na rota e o rótulo dele é o nome do projeto.

```gherkin
  Regra: O nome do painel não repete o nome do projeto

    Esquema do Cenário: [CT-03] o segmento do painel em cada painel, com e sem o nome do projeto ligado
      Dado o nome do projeto "Projeto Ômega" na identidade
      E o segmento nome do painel ligado, o nome do projeto <projeto> e a logo da marca desligada
      Quando o administrador geral abre o painel <painel>
      Então a composição mostra <segmentos>
      E "Projeto Ômega" aparece <vezes> na composição

      Exemplos:
        | painel  | projeto   | segmentos                          | vezes  | # regra                         |
        | /admin  | ligado    | "Projeto Ômega", "Administração"   | 1 vez  | rótulo ≠ projeto                |
        | /infra  | ligado    | "Projeto Ômega", "Infraestrutura"  | 1 vez  | rótulo ≠ projeto, outro painel  |
        | /app    | ligado    | "Projeto Ômega", sem separador     | 1 vez  | rótulo = projeto → omitido      |
        | /app    | desligado | "Projeto Ômega", sem separador     | 1 vez  | rótulo = projeto, projeto off → exibido |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | nunca omite: imprime "Projeto Ômega \| Projeto Ômega" no `/app` | CT-03 | linha `/app` ligado: 1 ocorrência e 0 separadores; o mutante tem 2 e 1 |
| M2 | omite o rótulo igual sem olhar o interruptor do projeto (composição vazia no `/app` com projeto off) | CT-03 | linha `/app` desligado: "Projeto Ômega" presente 1 vez; o mutante 0 |
| M3 | compara o rótulo com o `APP_NAME` do ambiente em vez do nome do projeto efetivo | CT-03 | linha `/app` ligado: com o nome gravado ≠ ambiente, o mutante não omite → 2 ocorrências |
| M4 | o provider do `/infra` não recebe a composição | CT-03 | linha `/infra`: região existe com "Infraestrutura"; o mutante não tem região |
| M5 | rótulo do painel fixo (o do `/admin` em todos) | CT-03 | linha `/infra`: "Infraestrutura" na região e "Administração" ausente; o mutante mostra "Administração" |

---

## Regra R4 — com organização ativa, o segmento do painel é o nome da organização aberta *(alterado em 2026-10-05: ADV-15)*

> `RQ-02`, `P-01` · perfil **padrão** · técnica: **tabela de decisão** (painel com/sem organização × interruptor) + **EP** com valor discriminante (organização vinculada primeiro ≠ aberta). Suíte `tests/Tenancy`.

```gherkin
  Regra: Com organização ativa, ela ocupa o segmento do painel

    Esquema do Cenário: [CT-04] o segmento do painel mostra a organização aberta, e só nela
      Dado o nome do projeto "Projeto Ômega" e os segmentos nome do projeto e logo da marca <demais>
      E a pessoa é vinculada primeiro à Acme e depois à <outra>, com papel nas duas e papel admin
      E o segmento nome do painel <painel_seg>
      E <antes>
      Quando a pessoa abre <tela>
      Então a composição mostra <mostra>
      E a composição não mostra <nao_mostra>

      Exemplos:
        | demais                  | outra          | painel_seg | antes                      | tela             | mostra                               | nao_mostra          | # linha                      |
        | projeto ligado, logo off | Globex        | ligado     | nenhuma visita anterior    | /app/acme        | "Acme"                               | "Globex"            | organização aberta           |
        | projeto ligado, logo off | Globex        | ligado     | nenhuma visita anterior    | /app/globex      | "Globex"                             | "Acme"              | não é a primeira vinculada   |
        | projeto ligado, logo off | Globex        | ligado     | a pessoa abriu /app/acme   | /admin           | "Administração"                      | "Acme" nem "Globex" | sem organização na rota      |
        | projeto ligado, logo off | Projeto Ômega | ligado     | nenhuma visita anterior    | /app/{Projeto Ômega} | "Projeto Ômega" duas vezes, com 1 separador | —            | organização = nome do projeto |
        | projeto ligado, logo off | Globex        | desligado  | nenhuma visita anterior    | /app/acme        | "Projeto Ômega"                      | "Acme"              | segmento desligado           |
        | projeto desligado, logo off | Globex     | ligado     | nenhuma visita anterior    | /app/acme        | "Acme", sem separador                | "Projeto Ômega"     | só o painel ligado *(ADV-15)* |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | usa a primeira organização da pessoa (`tenants()->first()`) | CT-04 | linha `/app/globex`: região contém "Globex" e não "Acme"; o mutante mostra "Acme" |
| M2 | usa a organização guardada na sessão/última aberta, inclusive no `/admin` | CT-04 | linha `/admin` depois de abrir a Acme: região sem "Acme"; o mutante mostra "Acme" |
| M3 | aplica a omissão de P-01 também com organização ativa (omite organização de nome igual ao projeto) | CT-04 | linha "organização = nome do projeto": 2 ocorrências e 1 separador; o mutante 1 e 0 |
| M4 | mostra a organização mesmo com o segmento do painel desligado | CT-04 | linha "segmento desligado": região sem "Acme"; o mutante a mostra |
| M5 (M-05 — revisão adversarial) | organização só entra quando o segmento do projeto também está ligado (cai no ramo de P-01 sem organização e omite) | CT-04 | linha "só o painel ligado": região contém "Acme"; o mutante a deixa vazia |

---

## Regra R5 — o nome do projeto é o da Identidade; a logo é a da Identidade, com a variante escura só na marca separada

> `P-07`, `P-08` · perfil **padrão** · técnica: **EP** (fonte do nome; partições da marca: unificada, separada, unificada com variante escura inerte)

```gherkin
  Regra: Os segmentos usam a identidade configurada na aba Identidade

    Cenário: [CT-05] o nome do projeto é o nome da aplicação gravado nas configurações
      Dado o nome da aplicação "Projeto Ômega" gravado na aba Identidade, diferente do APP_NAME do ambiente
      E só o segmento nome do projeto ligado
      Quando o administrador abre o painel /admin
      Então a composição mostra "Projeto Ômega"
      E a composição não mostra o APP_NAME do ambiente nem "Administração"

    Esquema do Cenário: [CT-06] a logo do segmento segue a identidade e o modo da marca
      Dado só o segmento logo da marca ligado
      E a identidade com <identidade>
      Quando o administrador abre o painel /admin
      Então a composição tem <imagens>
      E a composição <escura>

      Exemplos:
        | identidade                                                          | imagens                                         | escura                                         | # partição               |
        | a logo "kit/logo-ct.png" e a marca unificada                        | uma imagem com a URL de "kit/logo-ct.png"       | não tem imagem de variante escura              | unificada                |
        | a logo "kit/logo-ct.png", a variante escura "kit/logo-ct-dark.png" e a marca separada | a imagem clara com a URL de "kit/logo-ct.png" | tem a imagem escura com a URL de "kit/logo-ct-dark.png" | separada |
        | a logo, a variante escura "kit/logo-ct-dark.png" gravada e a marca unificada | uma imagem com a URL de "kit/logo-ct.png" | não contém a URL de "kit/logo-ct-dark.png"     | variante escura inerte   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | nome do projeto lido de `env('APP_NAME')` (ou cacheado antes do alinhamento) | CT-05 | região contém "Projeto Ômega"; o mutante mostra o valor do ambiente |
| M2 | segmento do projeto preenchido com o rótulo do painel | CT-05 | região sem "Administração"; o mutante a mostra |
| M3 | variante escura com a URL da logo clara | CT-06 | linha "separada": URL de `kit/logo-ct-dark.png` na região; o mutante repete a clara |
| M4 | variante escura emitida ignorando a marca unificada (lê `logo_dark` direto da config) | CT-06 | linha "variante escura inerte": URL de `kit/logo-ct-dark.png` ausente; o mutante a emite |
| M5 | composição só com a logo clara (a variante escura não entra na composição) | CT-06 | linha "separada": 2 imagens; o mutante 1 |

---

## Regra R6 — a logo da composição é sempre a da instalação, nunca a de uma organização *(alterado em 2026-10-05: ADV-02)*

> `P-07`, `P-12` · perfil **padrão** · técnica: **EP** com valor discriminante: **as duas** organizações têm logo própria, a aberta (Acme) e a outra (Globex, vinculada primeiro e aberta por último). Suíte `tests/Tenancy`. P-12 fixou a direção (Q16): a marca do `/app` hoje mostra a logo da **instalação**.

```gherkin
  Regra: A logo da composição é a da instalação, com ou sem organização

    Cenário: [CT-07] no /app da Acme, a composição mostra a logo da instalação e nenhuma logo de organização
      Dado a logo da instalação "kit/logo-ct.png", a Acme com logo "organizacoes/logos/acme.png" e a Globex com logo "organizacoes/logos/globex.png"
      E só o segmento logo da marca ligado
      E a pessoa vinculada primeiro à Globex e depois à Acme, que acabou de abrir /app/globex
      Quando a pessoa abre /app/acme
      Então a composição tem exatamente uma imagem, e ela aponta para "kit/logo-ct.png"
      E nenhuma imagem da composição aponta para "organizacoes/logos/acme.png" nem para "organizacoes/logos/globex.png"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | logo resolvida pela organização aberta (`tenant.logo ?? kit.logo`) | CT-07 | URL `acme.png` ausente e a única `<img>` com `kit/logo-ct.png`; o mutante mostra `acme.png` |
| M2 | logo resolvida pela primeira organização da pessoa | CT-07 | URL `globex.png` ausente; o mutante a emite (Globex vinculada primeiro) |
| M3 | logo resolvida pela organização da sessão anterior | CT-07 | URL `globex.png` ausente; o mutante a emite (Globex aberta antes) |
| M4 | segmento sem imagem no `/app` com organização (resolução devolve `null` com tenant) | CT-07 | a região tem 1 `<img>`; o mutante 0 |

---

## Regra R7 — logo ligada sem logo resolvida é omitida: nunca imagem quebrada, nunca marca vazia *(alterado em 2026-10-05: ADV-03, ADV-04)*

> `P-07`, `P-11` · perfil **padrão** · técnica: **EP** (sem logo; logo órfã — declarada e ausente do disco) × (logo sozinha; logo com o projeto). P-11 fixou a direção (Q14): o segmento é omitido e, sem nenhum segmento visível, a marca volta à de hoje.

```gherkin
  Regra: Sem logo resolvida, o segmento some e a marca nunca fica vazia

    Esquema do Cenário: [CT-08] o segmento da logo sem logo resolvida
      Dado o nome do projeto "Projeto Ômega" e os segmentos <segmentos>
      E <situacao>
      Quando o administrador abre o painel /admin
      Então a página responde com sucesso e o HTML não tem imagem com endereço vazio nem apontando para "kit/sumiu.png"
      E <marca>

      Exemplos:
        | segmentos                    | situacao                                                    | marca                                                                                          | # partição |
        | só logo da marca ligado      | a logo "kit/sumiu.png" gravada e o arquivo ausente do disco | não existe composição, e a região da marca do Filament (`.fi-logo`) mostra "Projeto Ômega" em texto | órfã, sozinha |
        | só logo da marca ligado      | nenhuma logo enviada                                        | não existe composição, e a região `.fi-logo` mostra "Projeto Ômega" em texto *(ADV-03)*        | sem logo, sozinha |
        | nome do projeto e logo ligados | nenhuma logo enviada                                      | a composição mostra "Projeto Ômega" e tem 0 separadores *(ADV-04)*                              | sem logo, com projeto |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `<img src="">` (ou `src` com `null` convertido) quando não há logo | CT-08 | linha "sem logo, sozinha": nenhum `src=""` no HTML; o mutante emite |
| M2 | URL montada do path gravado sem checar o disco | CT-08 | linha "órfã": `kit/sumiu.png` ausente do HTML; o mutante emite `storage/kit/sumiu.png` |
| M3 | `Storage::url(null)` lança e a página cai | CT-08 | linha "sem logo, sozinha": status de sucesso; o mutante 500 |
| M4 (M-04 — revisão adversarial) | composição vazia renderizada (envoltório sem segmento) no lugar da marca | CT-08 | linhas "sozinha": nenhum `.kit-cabecalho` e "Projeto Ômega" na `.fi-logo`; o mutante deixa a marca vazia |
| M5 (M-05 — revisão adversarial) | separador emitido para o segmento omitido ("Projeto Ômega \|") | CT-08 | linha "com projeto": 0 `.kit-cabecalho__sep`; o mutante 1 |

---

## Regra R8 — a composição substitui a marca em todo lugar onde o painel a desenha *(alterado em 2026-10-05: ADV-21)*

> `P-06` · perfil **padrão** · técnica: **EP** com contagem **calibrada pelo próprio layout** (o cenário não presume quantos lugares o Filament desenha: mede com tudo desligado). O oráculo conta **regiões da marca do Filament** (`.fi-logo`, na barra lateral e no topbar) que contêm uma composição — não texto solto na página (ADV-21).

```gherkin
  Regra: A identidade não troca ao recolher a barra lateral

    Cenário: [CT-09] cada região da marca desenhada no layout contém a composição
      Dado que, com os segmentos desligados e a logo da marca unificada, o /admin tem N regiões da marca (`.fi-logo`)
      E os segmentos nome do projeto e nome do painel ligados
      Quando o administrador abre o painel /admin
      Então o layout tem N regiões da marca, e cada uma contém exatamente uma composição
      E nenhuma composição existe fora de uma região da marca
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | composição injetada só no topbar (render hook) e a barra lateral continua com a marca de hoje | CT-09 | toda região `.fi-logo` contém `.kit-cabecalho`; o mutante deixa a da barra lateral sem (com N ≥ 2) |
| M2 | composição duplicada no mesmo lugar (hook **e** `brandLogo()`) | CT-09 | exatamente uma por região e nenhuma fora; o mutante tem duas numa região ou uma solta |

Nota de arnês: se o layout medido tiver N = 1 no HTML servido, M1 fica sem matador neste cenário e vira lacuna declarada para o CT-B; registrar o N medido no índice ao implementar.

---

## Regra R9 — texto exibido no cabeçalho sai escapado e íntegro *(alterado em 2026-10-05: ADV-01, ADV-08, ADV-13)*

> `RQ-01`, `RQ-02`, `RQ-03` (taxonomia: texto livre) · perfil **padrão** (Impacto 3) · técnica: **EP** — partições do texto: marcação HTML em conteúdo, aspas que fecham atributo, multibyte (acento + emoji de 4 bytes), comprimento máximo do cadastro (255). Cada linha isola uma fonte e uma partição.
>
> ADV-13 (e duplicados ADV-20, ADV-35): o mutante "corte não multibyte" foi **rejeitado** — a feature não corta texto — e saiu da tabela; a linha de 255 caracteres entra como dado. As linhas multibyte e a de 255 não têm mutante próprio: ficam por decisão da sessão como cobertura de dado.

```gherkin
  Regra: Nomes vindos de configuração ou de cadastro são texto, nunca marcação

    Esquema do Cenário: [CT-10] o nome do projeto e o nome do usuário saem como texto íntegro
      Dado <ligados>
      E <fonte> igual a <valor>
      Quando o administrador abre o painel <painel>
      Então o cabeçalho mostra <valor> como texto literal
      E o HTML do cabeçalho <marcacao>

      Exemplos:
        | ligados                                      | fonte                    | valor                                         | painel | marcacao                                                         | # partição |
        | o segmento do projeto e o bloco do usuário   | o nome da aplicação      | "<b>Ômega</b>"                                | /admin | não contém a tag `<b>` dentro de `.kit-cabecalho` (só `&lt;b&gt;`) | marcação no segmento do projeto |
        | só o segmento do painel, sem multi-organização | o nome da aplicação    | "<b>Ômega</b>"                                | /app   | não contém a tag `<b>` dentro de `.kit-cabecalho` *(ADV-01)*     | marcação no rótulo do painel |
        | o segmento do projeto e a logo da marca      | o nome da aplicação      | `x" onerror="alert(1)`                        | /admin | não contém `onerror="alert(1)"` como atributo (só escapado) *(ADV-08)* | aspas em atributo |
        | o segmento do projeto e o bloco do usuário   | o nome do administrador  | "<img src=x onerror=alert(1)>"                | /admin | não contém `<img src=x` (só `&lt;img`)                           | marcação no bloco |
        | o segmento do projeto e o bloco do usuário   | o nome da aplicação      | "Ação Ñandú 🚀"                               | /admin | contém "Ação Ñandú 🚀" byte a byte                               | multibyte |
        | o segmento do projeto e o bloco do usuário   | o nome do administrador  | "José Gonçalves 🚀"                           | /admin | contém "José Gonçalves 🚀" byte a byte                           | multibyte |
        | o segmento do projeto e o bloco do usuário   | o nome do administrador  | 255 caracteres terminando em "ç🚀"            | /admin | o bloco contém o nome inteiro, com "ç🚀" no fim *(ADV-13)*       | comprimento máximo |

  Regra: O nome da organização é texto, nunca marcação

    Cenário: [CT-11] o nome da organização sai escapado na composição
      Dado uma organização chamada "<img src=x onerror=alert(1)>" com a pessoa vinculada
      E o segmento nome do painel ligado
      Quando a pessoa abre o /app dessa organização
      Então a composição mostra "<img src=x onerror=alert(1)>" como texto literal
      E o HTML da composição não contém `<img src=x`
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | composição montada por concatenação dentro de `HtmlString` sem `e()` | CT-10 | linha "marcação no segmento do projeto": região sem `<b>`; o mutante emite a tag |
| M2 | nome do usuário com `{!! !!}` no blade do bloco | CT-10 | linha "marcação no bloco": região do bloco sem `<img src=x`; o mutante emite |
| M3 (M-03 — revisão adversarial, ADV-01) | rótulo do painel (no `/app`, o nome do projeto) interpolado sem escape, enquanto o segmento do projeto escapa | CT-10 | linha "marcação no rótulo do painel": `.kit-cabecalho` sem `<b>`; o mutante emite |
| M4 | nome da organização interpolado sem escape | CT-11 | região sem `<img src=x`; o mutante emite |
| M5 (M-05 — revisão adversarial, ADV-08) | nome do projeto em atributo da `<img>` (`alt`/`title`) sem escape de atributo | CT-10 | linha "aspas em atributo": `onerror="alert(1)"` ausente como atributo; o mutante fecha o `alt` e injeta |

---

## Regra R10 — o bloco do usuário nasce desligado; ligado, mostra o nome antes do menu do usuário, que continua *(alterado em 2026-10-05: ADV-16, ADV-17, ADV-18, ADV-19)*

> `RQ-03`, `RQ-06`, `P-09` · perfil **padrão** · técnica: **EP** (desligado nos três painéis; ligado)
>
> Oráculo do default (ADV-16..19): a afirmação é feita na região do menu do usuário/topbar (`.fi-user-menu` e o topbar) e por **contagem comparada a uma linha de base medida no próprio teste** — com as opções gravadas explicitamente desligadas, "Maria Ômega" aparece B vezes no HTML; o default tem de dar B. Independe do nome de classe do bloco.

```gherkin
  Regra: O bloco do usuário complementa o menu do usuário, e só quando ligado

    Esquema do Cenário: [CT-12] com os valores de fábrica, nenhum bloco do usuário aparece
      Dado os valores de fábrica do cabeçalho, sem nenhuma opção gravada pelo teste
      E o administrador geral "Maria Ômega"
      E a linha de base: com as cinco opções gravadas explicitamente no estado desligado/perfil, "Maria Ômega" aparece B vezes no HTML de <painel>
      Quando ele abre o painel <painel> no estado de fábrica
      Então "Maria Ômega" aparece B vezes no HTML
      E o topbar não tem bloco do usuário antes do menu do usuário

      Exemplos:
        | painel |
        | /admin |
        | /infra |
        | /app   |

    Cenário: [CT-13] ligado, o bloco mostra o nome antes do menu do usuário, que continua inteiro
      Dado o bloco do usuário ligado
      E o administrador "Maria Ômega" com o e-mail "maria@example.com"
      Quando ela abre o painel /admin
      Então o bloco do usuário mostra "Maria Ômega" como nome
      E o bloco aparece no HTML antes do gatilho do menu do usuário
      E o cabeçalho do menu do usuário continua com "maria@example.com" e o rótulo do papel
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | render hook emite o bloco sem consultar o interruptor | CT-12 | contagem de "Maria Ômega" = B; o mutante B + 1 |
| M2 | interruptor checado em dois providers e esquecido no terceiro | CT-12 | linha do painel esquecido: contagem = B; o mutante B + 1 |
| M3 | bloco implementado substituindo o menu do usuário (menu desligado/escondido) | CT-13 | `data-user-menu-header` presente com o e-mail; o mutante o remove |
| M4 | bloco registrado depois do menu (`USER_MENU_AFTER`) | CT-13 | posição da região < posição do gatilho do menu; o mutante inverte |
| M5 | linha principal com o e-mail em vez do nome | CT-13 | nome na região = "Maria Ômega"; o mutante mostra o e-mail |

---

## Regra R11 — o detalhe abaixo do nome é o papel do painel corrente (rótulo legível) ou o e-mail *(alterado em 2026-10-05: ADV-23, ADV-33)*

> `RQ-04`, `P-03` · perfil **padrão** · técnica: **tabela de decisão** (detalhe × persona × painel), com persona de papéis múltiplos nos **dois** painéis dela, para o discriminante "papel do painel corrente". Rótulos literais (ADV-23): `admin` → "Admin", `infra` → "Infra", `panel_user` → "Painel App", `master_global` → "Administrador Geral".

```gherkin
  Regra: Abaixo do nome, o papel no painel corrente ou o e-mail, conforme a opção

    Esquema do Cenário: [CT-14] o detalhe segue a opção e o painel corrente
      Dado o bloco do usuário ligado com o detalhe <detalhe>
      E <persona> com o e-mail "pessoa@example.com"
      Quando a pessoa abre o painel <painel>
      Então a linha de baixo do bloco mostra <mostra>
      E o bloco não mostra <nao_mostra>

      Exemplos:
        | detalhe | persona                                   | painel | mostra                  | nao_mostra                 | # regra                          |
        | perfil  | o administrador (papel admin)             | /admin | "Admin"                 | "pessoa@example.com"       | perfil, 1 papel                  |
        | perfil  | o usuário do negócio (papel panel_user)   | /app   | "Painel App"            | "pessoa@example.com"       | perfil, painel do negócio        |
        | perfil  | o administrador geral                     | /admin | "Administrador Geral"   | "master_global"            | rótulo legível                   |
        | perfil  | o administrador geral                     | /infra | "Administrador Geral"   | "pessoa@example.com"       | master_global em qualquer painel |
        | perfil  | o administrador geral                     | /app   | "Administrador Geral"   | "pessoa@example.com"       | master_global em qualquer painel |
        | perfil  | a pessoa com admin e, depois, infra       | /infra | "Infra"                 | "Admin"                    | N papéis → o do painel           |
        | perfil  | a pessoa com admin e, depois, infra       | /admin | "Admin"                 | "Infra"                    | N papéis, outro painel *(ADV-33)* |
        | email   | o administrador (papel admin)             | /admin | "pessoa@example.com"    | "Admin"                    | e-mail                           |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | mapeamento invertido (perfil → e-mail) | CT-14 | linha perfil/admin: "Admin" presente e e-mail ausente; o mutante inverte |
| M2 | primeiro papel da pessoa em vez do papel do painel | CT-14 | linha "admin e, depois, infra" no `/infra`: "Infra" presente, "Admin" ausente; o mutante mostra "Admin" |
| M3 | nome técnico do papel em vez do rótulo legível | CT-14 | linha master_global/admin: "Administrador Geral"; o mutante mostra "master_global" |
| M4 | papel consultado só por `roles.painel` (master_global some fora do /admin) | CT-14 | linhas /infra e /app: "Administrador Geral" presente; o mutante deixa a linha vazia |
| M5 | e-mail exibido junto com o perfil | CT-14 | linha perfil/admin: e-mail ausente da região; o mutante o mostra |
| M6 (M-06 — revisão adversarial, ADV-33) | último papel atribuído em vez do papel do painel | CT-14 | linha "admin e, depois, infra" no `/admin`: "Admin" presente, "Infra" ausente; o mutante mostra "Infra" |

---

## Regra R12 — com organização ativa, o papel é o da organização aberta; sem papel nela, não há linha de baixo *(alterado em 2026-10-05: ADV-23)*

> `RQ-04`, `P-03` · perfil **padrão** · técnica: **tabela de decisão** + **cardinalidade 0/1/N** (papéis por organização). Suíte `tests/Tenancy`. Rótulos literais (ADV-23): `admin_app` → "Administrador App", `panel_user` → "Painel App".

```gherkin
  Regra: O papel exibido é o da organização aberta, e sem papel não se inventa texto

    Esquema do Cenário: [CT-15] o detalhe com organização ativa
      Dado o bloco do usuário ligado com o detalhe <detalhe>
      E a pessoa "Bianca" ("bianca@example.com") com <papeis>, vinculada à Acme e à Globex
      Quando ela abre <tela>
      Então a linha de baixo do bloco <linha>
      E o bloco não mostra <nao_mostra>

      Exemplos:
        | detalhe | papeis                                   | tela        | linha                                    | nao_mostra                                | # regra                 |
        | perfil  | admin_app na Acme e panel_user na Globex | /app/acme   | mostra "Administrador App"               | "Painel App"                              | papel da organização    |
        | perfil  | admin_app na Acme e panel_user na Globex | /app/globex | mostra "Painel App"                      | "Administrador App"                       | outra organização       |
        | perfil  | panel_user só na Acme                    | /app/globex | não existe (o bloco tem só "Bianca")     | "bianca@example.com" nem "Painel App"     | 0 papéis → sem linha    |
        | email   | panel_user só na Acme                    | /app/globex | mostra "bianca@example.com"              | "Painel App"                              | e-mail independe de papel |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | papel de qualquer organização (primeiro papel encontrado) | CT-15 | linha `/app/globex` com dois papéis: "Painel App" presente, "Administrador App" ausente; o mutante mostra "Administrador App" |
| M2 | sem papel, cai para o e-mail | CT-15 | linha "0 papéis": e-mail ausente da região; o mutante o mostra |
| M3 | sem papel, renderiza a linha vazia ou um "—" | CT-15 | linha "0 papéis": elemento de detalhe ausente da região; o mutante o emite |
| M4 | com detalhe e-mail, ainda exige papel para mostrar a linha | CT-15 | linha e-mail/0 papéis: e-mail presente; o mutante não mostra nada |

---

## Regra R13 — o detalhe nasce em perfil e só tem duas opções

> `P-05` · perfil **padrão** · técnica: **EP** (default sem toque; conjunto fechado de opções)

```gherkin
  Regra: Perfil é o detalhe inicial, e e-mail é a única alternativa

    Cenário: [CT-16] ligar só o bloco do usuário mostra o papel
      Dado o bloco do usuário ligado e o detalhe nunca gravado pelo teste
      E o administrador com o papel admin
      Quando ele abre o painel /admin
      Então a linha de baixo do bloco mostra "Admin"
      E o bloco não mostra o e-mail dele

    Cenário: [CT-17] o seletor do detalhe oferece exatamente duas opções
      Dado o administrador na tela de configurações da aplicação
      E o bloco do usuário ligado no formulário
      Quando ele consulta as opções do detalhe abaixo do nome
      Então há exatamente duas opções: perfil e e-mail
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | default do consumidor/da migração em e-mail | CT-16 | rótulo do papel presente, e-mail ausente; o mutante inverte |
| M2 | terceira opção "nenhum" no seletor | CT-17 | `count($select->getOptions()) === 2`; o mutante 3 |
| M3 | seletor sem opções fechadas (lista vazia ou campo de texto) | CT-17 | chaves das opções = {perfil, email}; o mutante devolve outro conjunto |

---

## Regra R14 — detalhe fora do domínio gravado por fora da tela não derruba a página nem vira texto

> `P-05` · `@premissa` (mecanismo do 02: o consumidor coage valor fora da lista para perfil) · perfil **padrão** · técnica: **EP**, uma partição inválida por linha. É o cenário **por fora do componente** da regra de domínio do detalhe (gate de camada).

```gherkin
  Regra: Valor estranho gravado direto vira o detalhe inicial, nunca erro nem texto

    @premissa
    Esquema do Cenário: [CT-18] o painel abre com detalhe fora do domínio gravado direto
      Dado o bloco do usuário ligado e o detalhe gravado direto como <valor>
      E o administrador com o papel admin
      Quando ele abre o painel /admin
      Então a página responde com sucesso
      E a linha de baixo do bloco mostra "Admin"
      E o bloco não mostra <valor> nem o e-mail dele

      Exemplos:
        | valor      | # partição         |
        | "telefone" | fora da lista      |
        | ""         | vazio              |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `match` sem braço default (`UnhandledMatchError`) | CT-18 | linha "telefone": sucesso; o mutante 500 |
| M2 | `DetalheDoUsuario::from()` em vez de coerção (`ValueError`) | CT-18 | linha "": sucesso; o mutante 500 |
| M3 | valor fora da lista tratado como e-mail (ramo `else`) | CT-18 | rótulo do papel presente e e-mail ausente; o mutante mostra o e-mail |
| M4 | valor cru impresso como detalhe | CT-18 | linha "telefone": "telefone" ausente da região; o mutante o imprime |

---

## Regra R15 — valores de fábrica: quatro interruptores desligados e detalhe em perfil, na settings, na config e no `.env.example`

> `RQ-06`, `P-02`, `P-05`, `P-10` · perfil **padrão** · técnica: **EP + valor literal do requisito** (desligado / perfil), afirmando o **valor efetivo lido**, não o ambiente.

```gherkin
  Regra: Instalação nova e atualizada nascem com o cabeçalho de hoje

    Cenário: [CT-19] as cinco opções nascem desligadas e em perfil
      Dado uma instalação recém-migrada, sem nenhuma opção do cabeçalho gravada pelo teste
      E o ambiente de teste sem nenhuma variável de cabeçalho definida (lido, não presumido)
      Quando as opções gravadas e a config do cabeçalho são lidas, antes do alinhamento
      Então nome do projeto, nome do painel, logo da marca e bloco do usuário valem falso nas duas leituras
      E o detalhe abaixo do nome vale "perfil" nas duas leituras

    Cenário: [CT-20] o .env.example traz o par de cada opção com o mesmo default
      Dado o arquivo .env.example do kit
      Quando as linhas ativas das cinco variáveis do cabeçalho são lidas
      Então as quatro variáveis de interruptor valem "false"
      E a variável do detalhe vale "perfil"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | migração com um interruptor `true` (cópia de outra propriedade booleana) | CT-19 | `configuracaoGravada()` de cada interruptor `=== false`; o mutante `true` |
| M2 | migração com o detalhe em "email" | CT-19 | `configuracaoGravada()` do detalhe `=== 'perfil'`; o mutante "email" |
| M3 | `config/kit.php` com default `env(..., true)` divergente da migração | CT-19 | leitura da config **antes** do alinhamento `=== false`; o mutante `true` |
| M4 | variável ausente ou com default divergente no `.env.example` | CT-20 | valor da linha ativa de cada variável; o mutante não tem a linha ou tem `true`/`email` |
| M5 | variável presente só como linha comentada | CT-20 | leitura por `Dotenv::parse()` (linha ativa); o mutante devolve ausente |

---

## Regra R16 — a tela grava as cinco opções e a config as reflete, nos dois sentidos *(alterado em 2026-10-05: ADV-07)*

> `RQ-05`, `P-10` · perfil **padrão** · técnica: **códigos distintos por propriedade** — cada propriedade tem um padrão de valores diferente nas linhas 1–3 (projeto 100, painel 010, logo 001, usuário 111; detalhe e-mail/perfil/e-mail), então toda troca de mapeamento entre duas propriedades diverge em ao menos uma linha, e toda propriedade sai do default em ao menos uma linha — mais a **transição ligado → desligado** (linha 4, ADV-07), em que o default não esconde o valor gravado. Gate de tela de escrita.

```gherkin
  Regra: As opções vivem nas Configurações da aplicação e chegam à config

    Esquema do Cenário: [CT-21] o administrador grava as opções do cabeçalho pela tela
      Dado o administrador na tela de configurações da aplicação, com <partida>
      Quando ele grava nome do projeto <projeto>, nome do painel <painel>, logo da marca <logo>, bloco do usuário <usuario> e detalhe <detalhe>
      Então o formulário não tem erros
      E as opções gravadas são exatamente as escolhidas
      E, depois do alinhamento, a config do cabeçalho tem os mesmos valores

      Exemplos:
        | partida                                                        | projeto   | painel    | logo      | usuario   | detalhe | # linha |
        | os valores de fábrica                                          | ligado    | desligado | desligado | ligado    | e-mail  | 1       |
        | os valores de fábrica                                          | desligado | ligado    | desligado | ligado    | perfil  | 2       |
        | os valores de fábrica                                          | desligado | desligado | ligado    | ligado    | e-mail  | 3       |
        | os quatro interruptores ligados e gravados antes por esta tela | desligado | desligado | desligado | desligado | (não tocado) | 4 — ligado → desligado *(ADV-07)* |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | propriedade nova fora do mapa settings → config | CT-21 | `config('kit.cabecalho.*')` de cada uma após alinhar; o mutante fica no default |
| M2 | duas propriedades mapeadas para a mesma chave de config | CT-21 | linha 1: config do painel `false` com projeto `true`; o mutante iguala |
| M3 | detalhe não dehidratado (não grava) | CT-21 | linha 1: gravado "email"; o mutante fica "perfil" |
| M4 | interruptor da logo fora do schema (não grava) | CT-21 | linha 3: gravado `true`; o mutante `false` |
| M5 (M-05 — revisão adversarial, ADV-07) | o `false` não é gravado (toggle dehidratado só quando ligado, ou `array_filter` no payload) | CT-21 | linha 4: os quatro gravados `false`; o mutante os mantém `true` |
| M6 (M-06 — revisão adversarial, ADV-07) | alinhamento só sobrescreve a config com valores verdadeiros | CT-21 | linha 4: config dos quatro `false` após alinhar; o mutante fica `true` |

---

## Regra R17 — o detalhe enviado pelo cliente fora do domínio é recusado e não é gravado

> `P-05`, `RQ-05` · Superfície Livewire do 02 (`$wire.set('data.<detalhe>', …)` + `save()`) · perfil **padrão** · técnica: **EP**, uma partição inválida por linha (valor fora, vazio, tipo errado ×2). Cenário de recusa afirma o não-efeito de **cada** gravação do caminho feliz: o detalhe **e** o outro campo alterado no mesmo envio.

```gherkin
  Regra: O cliente não grava detalhe fora das duas opções

    Esquema do Cenário: [CT-22] com o bloco ligado, detalhe inválido enviado pelo cliente é recusado
      Dado o bloco do usuário e o detalhe "email" já gravados
      E o administrador na tela de configurações, que troca o nome da aplicação para "Não Gravou"
      Quando o cliente envia o detalhe como <valor> e salva
      Então o formulário aponta erro no detalhe
      E o detalhe gravado continua "email"
      E o nome da aplicação gravado continua o anterior

      Exemplos:
        | valor       | # partição       |
        | "telefone"  | fora da lista    |
        | ""          | vazio            |
        | ["email"]   | tipo errado: lista |
        | 7           | tipo errado: número |

    Cenário: [CT-23] com o bloco desligado, detalhe inválido enviado pelo cliente não é gravado
      Dado o bloco do usuário desligado e o detalhe "email" já gravado
      E o administrador na tela de configurações
      Quando o cliente envia o detalhe como "telefone" e salva
      Então a página não cai
      E o detalhe gravado continua "email"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | seletor sem `in:` das opções (aceita qualquer string) | CT-22 | linha "telefone": `assertHasFormErrors(['<detalhe>'])` e gravado "email"; o mutante grava "telefone" |
| M2 | detalhe sem `required` | CT-22 | linha "": erro no campo; o mutante grava "" |
| M3 | lista chega ao settings tipado `string` (`TypeError`) | CT-22 | linha lista: erro de formulário, sem exceção; o mutante lança |
| M4 | número convertido para string e aceito | CT-22 | linha 7: erro no campo; o mutante grava "7" |
| M5 | gravação parcial antes da validação (o nome grava mesmo com erro) | CT-22 | qualquer linha: nome gravado ≠ "Não Gravou"; o mutante grava |
| M6 | `dehydratedWhenHidden()` sem validação: o escondido grava o que o cliente mandou | CT-23 | gravado "email"; o mutante grava "telefone" |

---

## Regra R18 — a tela de configurações continua gravando com o cabeçalho desligado e com valor de fora gravado *(alterado em 2026-10-05: ADV-14)*

> `RQ-06`, D8 do `01` (resposta de Q15: a tela **coage** o detalhe fora da lista para `perfil` ao carregar, em `mutateFormDataBeforeFill`) · perfil **padrão** · técnica: **EP** (bloco desligado — o estado de fábrica; valor fora da lista gravado direto).

```gherkin
  Regra: A seção nova não trava a tela de quem nunca a usa

    Cenário: [CT-24] com o bloco desligado, salvar outro campo grava e preserva o detalhe
      Dado o bloco do usuário desligado e o detalhe "email" gravado antes
      E o administrador na tela de configurações
      Quando ele troca o nome da aplicação para "Gravou Mesmo Assim" e salva
      Então o formulário não tem erros e o nome gravado é "Gravou Mesmo Assim"
      E o detalhe gravado continua "email" e os quatro interruptores continuam desligados

    Cenário: [CT-25] com detalhe fora da lista gravado direto, salvar outro campo grava e normaliza o detalhe *(alterado em 2026-10-05: ADV-14)*
      Dado o bloco do usuário ligado e o detalhe gravado direto como "telefone"
      E o administrador na tela de configurações
      Quando ele troca o nome da aplicação para "Gravou Mesmo Assim" e salva
      Então o formulário não tem erros e o nome gravado é "Gravou Mesmo Assim"
      E o detalhe gravado passa a ser "perfil"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | seletor escondido dehidrata `null` e a settings tipada `string` lança | CT-24 | sem erros e nome gravado; o mutante falha no save |
| M2 | seletor `required` validado mesmo escondido | CT-24 | `assertHasNoFormErrors()`; o mutante aponta erro no detalhe |
| M3 | ao esconder, o detalhe volta para "perfil" | CT-24 | gravado "email"; o mutante "perfil" |
| M4 | `Rule::in()` do seletor rejeita o valor carregado e trava a tela inteira (classe QA-01) | CT-25 | sem erros e nome gravado; o mutante aponta erro no detalhe e não grava o nome |
| M5 (M-05 — revisão adversarial, ADV-14) | a tela carrega o valor cru (sem coerção) e o `Select` o descarta sem gravar, ou preserva "telefone" | CT-25 | `configuracaoGravada('cabecalho_detalhe_do_usuario') === 'perfil'`; o mutante deixa "telefone" |

---

## Regra R19 — só quem tem a permissão da tela grava as opções *(alterado em 2026-10-05: ADV-22, ADV-24)*

> `RQ-05` · perfil **padrão** · técnica: **matriz papel × ação**, célula negativa com a persona discriminante (tem o papel, não tem a permissão). A célula positiva é CT-21.
>
> A célula "autorização na ação" (`save()`) é coberta pela **montagem**: a barreira da página (`ExigePermissaoDaTela`) recusa com 403 antes de existir componente montado, então não há `save()` alcançável por quem não tem a permissão. A asserção "gravado continua desligado" saiu por ser trivial sob o 403 (ADV-24).

```gherkin
  Regra: As opções do cabeçalho herdam a barreira da tela de configurações

    Cenário: [CT-26] o administrador sem a permissão da tela é recusado na montagem *(alterado em 2026-10-05: ADV-22, ADV-24)*
      Dado um administrador cujo papel perdeu a permissão da tela de configurações
      Quando ele tenta montar a tela de configurações
      Então a resposta é 403
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | seção servida por página/ação nova sem a barreira de permissão | CT-26 | `Livewire::test(...)->assertForbidden()`; o mutante monta |
| M2 | checagem por papel `admin` em vez da permissão | CT-26 | persona com o papel e sem a permissão: 403; o mutante monta |

---

## Regra R22 — tela pública não renderiza composição nem bloco do usuário *(criada em 2026-10-05: ADV-06)*

> `RQ-06` e `00` §Fora de Escopo ("cabeçalho nas telas públicas de autenticação") · perfil **padrão** · técnica: **EP** (visitante sem sessão, tudo ligado). Decisão da sessão: sem usuário autenticado, a composição não existe e a marca é a de hoje.

```gherkin
  Regra: Telas públicas mantêm a marca de hoje

    Cenário: [CT-28] o visitante vê a marca de hoje no login, com tudo ligado
      Dado os quatro interruptores ligados, o nome do projeto "Projeto Ômega" e a logo "kit/logo-ct.png"
      E nenhum usuário autenticado
      Quando o visitante abre /admin/login
      Então a página responde com sucesso
      E não há composição do cabeçalho nem bloco do usuário
      E a marca é a de hoje: a imagem da logo "kit/logo-ct.png" na região da marca do Filament (`.fi-logo`)

    Cenário: [CT-35] a tela de autenticação autenticada continua sem composição depois de um update Livewire *(criado em 2026-10-05: QA-01 do ciclo 1)*
      Dado o nome do projeto ligado, com o nome "Projeto Ômega"
      E um usuário autenticado com e-mail NÃO verificado
      Quando ele abre o aviso de verificação de e-mail do painel /app e dispara um update Livewire da própria tela (um `$refresh`)
      Então nem o HTML do GET nem o HTML devolvido pelo update contêm a composição (`kit-cabecalho`)
      E a marca da tela é a de hoje nos dois: o nome da aplicação "Projeto Ômega" dentro da região da marca do Filament (`.fi-logo`) *(alterado em 2026-10-05: QA-08 — asserção positiva, não só a negada)*
```

> A tela de verificação de e-mail e o 2FA são `SimplePage` **autenticadas**: a logo é redesenhada dentro do componente Livewire (`vendor/filament/filament/resources/views/components/page/simple.blade.php:$hasLogo:23`), e no update a rota corrente é `default-livewire.update`, não `filament.{painel}.auth.*`. Guarda por nome de rota não cobre o update; a guarda tem de reconhecer a página (QA-01).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `brandLogo()` compõe sem checar autenticação (a closure vale também na página de login) | CT-28 | nenhum `.kit-cabecalho` no HTML; o mutante o emite |
| M2 | composição/bloco acessa `auth()->user()->name` sem guarda e a página cai | CT-28 | status de sucesso; o mutante 500 |
| M3 | sem usuário, `brandLogo()` devolve `HtmlString` vazia e a marca some | CT-28 | `<img>` com `kit/logo-ct.png` na `.fi-logo`; o mutante não tem imagem |
| M4 — ciclo 1 do QA | guarda de tela de autenticação só pelo nome da rota (`.auth.`): o GET fica limpo e o update Livewire (`default-livewire.update`) compõe | CT-35 | `kit-cabecalho` ausente no HTML do `call('$refresh')`; o mutante o emite |
| M5 — ciclo 2 do QA | com a guarda disparada, `marca()` devolve `HtmlString('')` e a marca some da tela de autenticação | CT-35 | "Projeto Ômega" dentro de `.fi-logo` nos dois renders; o mutante não tem texto na região |

---

## Regra R23 — o papel exibido é o do contexto do painel corrente, com `master_global` vencendo *(criada em 2026-10-05: ADV-09, ADV-10, ADV-31, ADV-32, ADV-34)*

> `RQ-04`, `P-03` ("`master_global` aparece em qualquer painel"; o papel é o do **painel corrente**) · perfil **padrão** · técnica: **tabela de decisão** — contexto do painel (global × organização) × papéis da pessoa (master, global, da organização). Suíte `tests/Tenancy`, bloco ligado com o detalhe em perfil.

```gherkin
  Regra: O papel do painel corrente vem do contexto dele, e o administrador geral vence

    Esquema do Cenário: [CT-29] o administrador geral aparece como tal na organização *(criado em 2026-10-05: ADV-09, ADV-32)*
      Dado o bloco do usuário ligado com o detalhe perfil
      E a pessoa com papel master_global <alem>
      Quando ela abre /app/acme
      Então a linha de baixo do bloco mostra "Administrador Geral"
      E o bloco não mostra "Painel App"

      Exemplos:
        | alem                          | # linha                 |
        | e nenhum outro papel          | só master               |
        | e também panel_user na Acme   | master vence o da organização |

    Cenário: [CT-30] papel global não vence o papel da organização aberta *(criado em 2026-10-05: ADV-10, ADV-31)*
      Dado o bloco do usuário ligado com o detalhe perfil
      E a pessoa com papel admin global e admin_app na Acme
      Quando ela abre /app/acme
      Então a linha de baixo do bloco mostra "Administrador App"
      E o bloco não mostra "Admin" como papel

    Cenário: [CT-31] o papel da organização não vaza para o painel global *(criado em 2026-10-05: ADV-34)*
      Dado o bloco do usuário ligado com o detalhe perfil
      E a pessoa com papel admin global e admin_app na Acme, que acabou de abrir /app/acme
      Quando ela abre /admin
      Então a linha de baixo do bloco mostra "Admin"
      E o bloco não mostra "Administrador App"
```

A asserção de "Admin" ausente em CT-30 é sobre o **texto do elemento de detalhe** (igualdade), não `assertDontSee('Admin')` — "Administrador App" contém "Admin".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | papel da organização consultado antes do `master_global` | CT-29 | linha "master vence": detalhe = "Administrador Geral"; o mutante mostra "Painel App" |
| M2 | `master_global` some no `/app` com organização (consulta filtrada pelo team da organização) | CT-29 | linha "só master": detalhe = "Administrador Geral"; o mutante deixa a linha vazia |
| M3 | papel do contexto global vence o da organização no `/app` | CT-30 | detalhe = "Administrador App"; o mutante mostra "Admin" |
| M4 | team da organização aberta vaza para o `/admin` | CT-31 | detalhe = "Admin"; o mutante mostra "Administrador App" |

---

## Regra R24 — a seção "Cabeçalho dos painéis" expõe as cinco opções, e o detalhe só com o bloco ligado *(criada em 2026-10-05: ADV-11, ADV-12)*

> `P-10` (seção própria "Cabeçalho dos painéis" na aba Identidade), `P-13` (o seletor só aparece com o bloco ligado; resposta de Q17) · perfil **padrão** · técnica: **EP**. Helpers do Filament 5 a confirmar no vendor pelo executor: `assertSchemaComponentHidden`/`assertSchemaComponentVisible`/`assertSchemaComponentExists` (não `@deprecated` em `vendor/filament/schemas/.stubs.php`; `assertFormFieldHidden` é `@deprecated`).

```gherkin
  Regra: A seção mostra as opções do cabeçalho, e o detalhe acompanha o bloco do usuário

    Cenário: [CT-32] o seletor do detalhe acompanha o interruptor do bloco do usuário *(criado em 2026-10-05: ADV-11)*
      Dado o bloco do usuário desligado gravado
      E o administrador na tela de configurações, com o seletor do detalhe oculto
      Quando ele liga o bloco do usuário no formulário
      Então o seletor do detalhe fica visível

    Cenário: [CT-33] a seção "Cabeçalho dos painéis" traz as cinco opções *(criado em 2026-10-05: ADV-12)*
      Dado o administrador com a permissão da tela
      Quando ele abre a tela de configurações da aplicação
      Então a tela contém o título "Cabeçalho dos painéis"
      E o formulário tem os campos nome do projeto, nome do painel, logo da marca, bloco do usuário e detalhe abaixo do nome
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | seletor sempre visível (sem `->visible()`) | CT-32 | `assertSchemaComponentHidden(detalhe)` no estado inicial; o mutante visível |
| M2 | visibilidade sem `->live()` no interruptor (não reage) ou condição invertida | CT-32 | `assertSchemaComponentVisible(detalhe)` depois de `fillForm([usuario => true])`; o mutante continua oculto |
| M3 | um dos cinco campos fora do schema da seção | CT-33 | `assertSchemaComponentExists` de cada um; o mutante falha no ausente |
| M4 | campos soltos na aba, sem a seção própria | CT-33 | `assertSee('Cabeçalho dos painéis')`; o mutante não tem o título |

---

## Regra R25 — toda classe que as duas blades do cabeçalho emitem tem regra no CSS do kit, escopada *(criada em 2026-10-05: conformidade com `.ai/rules/css-filament.md`, pela sessão — origem é rule, não achado)*

> `RQ-01`, `RQ-03` (o cabeçalho **aparece** — e aparece estilizado), `RQ-08` (a entrega vem com o teste que prova que funciona — este é o que prova o estilo, que nenhum teste de HTML prova) · perfil **padrão** · técnica: **EP** com controle positivo (piso de contagem). A rule `css-filament.md` manda: utilitária que blade emite e não existe no CSS produz HTML correto e sem estilo nenhum, com todo teste verde; a guarda lê a blade em runtime e exige cada classe no CSS do kit, escopada e com piso. Modelos: `tests/Kit/SpotlightCssTest.php`, `tests/Kit/CardsCssTest.php`.

```gherkin
  Regra: Toda classe das blades do cabeçalho existe no kit.css, e o par dark existe

    Cenário: [CT-34] as classes das duas blades do cabeçalho têm regra no CSS do kit
      Dado as blades "resources/views/filament/cabecalho-do-painel.blade.php" e "resources/views/filament/usuario-no-cabecalho.blade.php"
      Quando se extraem as classes "kit-*" de todo atributo class="…" das duas (as "fi-logo*" são do Filament e ficam de fora)
      Então há pelo menos 8 classes extraídas
      E cada uma aparece como seletor em "resources/css/filament/kit.css"
      E ".kit-cabecalho" e ".kit-usuario" têm o par ".dark:root"
      E ".kit-usuario" aparece dentro de um bloco "@media (min-width: 768px)"
      E o CSS publicado "public/css/kit/kit-correcoes.css" contém ".kit-cabecalho" e ".kit-usuario"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | classe nova na blade sem regra no CSS (ex.: `kit-cabecalho__sep` renomeada só num lado) | CT-34 | laço "cada classe extraída aparece no CSS"; o mutante falha na ausente |
| M2 | par `.dark:root` esquecido (texto preto no tema escuro) | CT-34 | `toContain('.dark:root .kit-cabecalho')` e `.kit-usuario` |
| M3 | media query removida (bloco sempre visível, espreme o topbar) | CT-34 | bloco `@media (min-width: 768px)` com `.kit-usuario` dentro |
| M4 | `filament:assets` não rodado (publicado velho) | CT-34 | as duas classes no `public/css/kit/kit-correcoes.css` |
| M5 | extrator quebrado devolvendo lista vazia (guarda verde sobre nada) | CT-34 | piso `>= 8` classes extraídas |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota nova recebe `{id}`; a organização vem da rota existente `/app/{slug}`, barrada antes do layout por `app/Models/User.php:canAccessTenant:819` (inclusive inativa). O vazamento horizontal possível — nome/logo/papel de **outra** organização ou de outro contexto — é coberto por CT-04, CT-07, CT-15, CT-30, CT-31 | G3 |
| Autorização exercida na ação (não só `can()`) | CT-26 — a célula da ação (`save()`) é coberta pela montagem: a barreira `ExigePermissaoDaTela` recusa com 403 antes de haver componente, então `save()` não é alcançável sem a permissão *(alterado em 2026-10-05: ADV-22)* | G2 |
| Gate de camada: autorização por fora do componente | lacuna declarada: a barreira é a da tela existente (não é regra nova desta feature) e não há outra entrada HTTP que grave o settings — os demais escritores (`app/Console/Commands/KitInstall.php`, `KitUpdate.php`, `app/Support/CustomizadorDaInstalacao.php`) são CLI com acesso ao servidor. CT-26 é escrito como se a afirmação fosse falsa (página nova sem barreira) | G2 |
| Gate de camada: validação de domínio por fora do componente | CT-18 | G1 |
| Idempotência (ancorada no agregado) | não se aplica: gravar opção é substituição de valor; nenhum agregado acumula | — |
| Concorrência | não se aplica: sem contador, saldo ou limite | — |
| Fronteira no ponto de entrada (gravação) | CT-22 (fora, vazio, tipo errado) | G2 |
| Domínio condicionado (tipo × valor) | CT-22, CT-23 (validade do detalhe × bloco ligado/desligado) | G2 |
| Estado × operação de escrita (desligado ainda funciona?) | CT-23, CT-24 (escrever com o bloco desligado) | G2 |
| Ausente ≠ null ≠ vazio | CT-16 (nunca gravado → default), CT-18 ("" gravado), CT-22 ("" enviado). `null` gravado: não se aplica — a propriedade é `string` e `null` só chega por escrita direta no banco, fora de qualquer entrada da feature | G1, G2 |
| Cardinalidade do destinatário 0/1/N | CT-15 (0 papéis na organização), CT-14 (1 e N papéis no mesmo contexto), CT-29, CT-30 (N papéis em contextos diferentes) | G1, G3 |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: sem dado temporal | — |
| Texto livre (acento, emoji, marcação em conteúdo e em atributo, 255 caracteres) | CT-10, CT-11 | G1, G3 |
| Unicode / limite de varchar | CT-10 (linha de 255 caracteres terminando em "ç🚀", ADV-13; a feature não corta texto, então não há mutante de corte) | G1 |
| Unicidade + soft delete | não se aplica: nenhuma entidade única criada | — |
| Entidade removível ou desativável | não se aplica: organização inativa é barrada antes do layout (`app/Models/User.php:canAccessTenant:819`); logo apagada do disco coberta em CT-08 | G1 |
| CRUD combinado | não se aplica: sem CRUD | — |
| Mass assignment | não se aplica: nenhum model novo; o estado `$data` da SettingsPage existente ganha só as 5 chaves, exercitadas em CT-21…CT-23 | — |
| Upload | não se aplica: sem upload novo (P-07) | — |
| Precisão monetária | não se aplica | — |
| Superfície Livewire (valor fora do domínio e tipo errado) | CT-22 (fora, vazio, lista, número), CT-23 (campo escondido) | G2 |
| Estado do framework usado sem validar | não se aplica: o 02 declara que nenhum array de estado do framework é consumido | — |
| Método público de componente | não se aplica: o 02 declara nenhum `public function` novo | — |
| IDOR por entidade / mass assignment por tabela | `settings` (grupo do kit): CT-21…CT-23, CT-26. Nenhuma outra tabela persistida | G2 |
| Escopo com discriminante nulo | CT-04 (linha `/admin`: organização nula → rótulo do painel, nunca a última aberta), CT-31 (papel no `/admin` depois de abrir a organização), CT-28 (usuário nulo → sem composição) | G1, G3 |
| Saída do estado de erro (4xx tem destino) | não se aplica como cenário novo: o 403 da tela (CT-26) é o da página existente, com destino coberto em `tests/Kit/ConfiguracoesDoKitTelaTest.php` | — |
| Regressão do default (RQ-06) | CT-01, CT-27, CT-12, CT-19, CT-24, CT-28 | G1, G2, G3 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | marca de fábrica não muda nos três painéis *(alterado: ADV-05, ADV-16..19)* | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R1 M1–M5 |
| CT-02 | interruptores, ordem e separadores | R2 | tabela de decisão | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R2 M1–M5 |
| CT-03 | segmento do painel por painel, omissão | R3 | tabela de decisão | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R3 M1–M5 |
| CT-04 | organização aberta no segmento do painel *(alterado: ADV-15)* | R4 | tabela de decisão + EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R4 M1–M5 |
| CT-05 | nome do projeto da Identidade | R5 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R5 M1–M2 |
| CT-06 | logo segue identidade e modo da marca | R5 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R5 M3–M5 |
| CT-07 | logo da composição é a da instalação *(alterado: ADV-02)* | R6 | EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R6 M1–M4 |
| CT-08 | logo sem logo resolvida é omitida *(alterado: ADV-03, ADV-04)* | R7 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R7 M1–M5 |
| CT-09 | composição em cada região da marca *(alterado: ADV-21)* | R8 | EP calibrada | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R8 M1–M2 |
| CT-10 | nome do projeto e do usuário escapados e íntegros *(alterado: ADV-01, ADV-08, ADV-13)* | R9 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R9 M1–M3, M5 |
| CT-11 | nome da organização escapado | R9 | EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R9 M4 |
| CT-12 | sem bloco do usuário de fábrica *(alterado: ADV-16..19)* | R10 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R10 M1–M2 |
| CT-13 | bloco antes do menu, menu inteiro | R10 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R10 M3–M5 |
| CT-14 | detalhe × papel × painel *(alterado: ADV-23, ADV-33)* | R11 | tabela de decisão | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R11 M1–M6 |
| CT-15 | detalhe com organização ativa *(alterado: ADV-23)* | R12 | tabela de decisão + 0/1/N | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R12 M1–M4 |
| CT-16 | só o bloco ligado mostra o papel | R13 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R13 M1 |
| CT-17 | seletor com duas opções | R13 | EP | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R13 M2–M3 |
| CT-18 | detalhe fora do domínio gravado direto | R14 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R14 M1–M4 |
| CT-19 | defaults na settings e na config | R15 | EP + literal | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R15 M1–M3 |
| CT-20 | defaults no `.env.example` | R15 | EP + literal | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R15 M4–M5 |
| CT-21 | gravação pela tela e alinhamento, ida e volta *(alterado: ADV-07)* | R16 | códigos distintos + transição | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R16 M1–M6 |
| CT-22 | detalhe inválido recusado (bloco ligado) | R17 | EP inválidas | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R17 M1–M5 |
| CT-23 | detalhe inválido não gravado (bloco desligado) | R17 | EP inválida | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R17 M6 |
| CT-24 | salvar com bloco desligado | R18 | EP | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R18 M1–M3 |
| CT-25 | salvar com valor de fora gravado normaliza para perfil *(alterado: ADV-14)* | R18 | EP | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R18 M4–M5 |
| CT-26 | sem permissão é recusado na montagem *(alterado: ADV-22, ADV-24)* | R19 | matriz papel × ação | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R19 M1–M2 |
| CT-27 | default inalterado no `/app/acme` *(criado: ADV-05)* | R1 | EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R1 M6 |
| CT-28 | tela pública com a marca de hoje *(criado: ADV-06)* | R22 | EP | G1 | Pest feature HTTP | `tests/Kit/CabecalhoDoPainelTest.php` | R22 M1–M3 |
| CT-29 | master_global na organização *(criado: ADV-09, ADV-32)* | R23 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R23 M1–M2 |
| CT-30 | papel global não vence o da organização *(criado: ADV-10, ADV-31)* | R23 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R23 M3 |
| CT-31 | papel da organização não vaza para o `/admin` *(criado: ADV-34)* | R23 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | R23 M4 |
| CT-32 | seletor do detalhe acompanha o bloco *(criado: ADV-11)* | R24 | EP | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R24 M1–M2 |
| CT-33 | seção com título e cinco campos *(criado: ADV-12)* | R24 | EP | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R24 M3–M4 |
| CT-34 | classes das blades têm regra no CSS do kit *(criado: rule `css-filament.md`)* | R25 | EP + controle positivo | G1 | Pest feature (arquivo) | `tests/Kit/CabecalhoDoPainelTest.php` | R25 M1–M5 |
| CT-35 | tela de autenticação autenticada sem composição após update Livewire *(criado: QA-01)* | R22 | estado × evento | G2 | componente Livewire/Filament | `tests/Kit/CabecalhoDoPainelTelaTest.php` | R22 M4–M5 |
| CT-B03 | composição cabe numa linha na barra lateral, com reticências *(criado: QA-04)* | R26 | BVA (nome longo) | G4 | browser | `tests/Browser/CabecalhoDoPainelTest.php` | ver `05` |
| CT-B01 | bloco some abaixo de 768 px, à esquerda do avatar | R20 | BVA 2-valores | G4 | browser | `tests/Browser/CabecalhoDoPainelTest.php` | ver `05` |
| CT-B02 | variante escura na composição por tema | R21 | EP | G4 | browser | `tests/Browser/CabecalhoDoPainelTest.php` | ver `05` |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| troca de organização na mesma sessão com `fronteiraDeRequest()` (memo estático do segmento) | o mutante "primeira organização" já morre em CT-04; memo estático entre requests só existe em processo persistente (Octane), que o kit não usa |
| trilha de auditoria da gravação das opções | log/auditoria não é cláusula do requisito (Proibição 12); a auditoria do settings é mecanismo existente |
| `null` gravado direto no detalhe | estado inalcançável por qualquer entrada (env dá string, tela valida); cogitado só por completude de "ausente ≠ null ≠ vazio" |
| repetir CT-02 nos três painéis | M4/M5 de R3 já matam o provider esquecido; repetir não mata mutante novo |
| mutante "corte não multibyte" do nome (ADV-13, ADV-20, ADV-35) | rejeitado pela sessão: a feature não corta texto; a linha de 255 caracteres entrou em CT-10 como dado *(alterado em 2026-10-05: ADV-13)* |
| asserção "gravado continua desligado" em CT-26 | trivial sob o 403 da montagem (ADV-24) |

