# Casos de Teste — Roadmap item 1: preferências de aparência por usuário

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas e `## Superfície de UI`) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **pendente — despacho da sessão** (gatilho: Impacto 3 na área "gravação pela pessoa"). Derivação feita em sub-agente, sem autorização de aninhamento.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — aparência efetiva (chaves × escolha × contexto) | 3 | 2 | 6 | padrão |
| A2 — gravação pela pessoa (seção Aparência, superfície Livewire) | 2 | 3 | 6 | padrão (Impacto 3: grava no registro do usuário; valor do cliente vira CSS) |
| A3 — configurações da instalação (padrões e chave) | 2 | 2 | 4 | padrão |
| A4 — chave da organização (cadastro em `/admin`) | 2 | 2 | 4 | padrão |
| A5 — efeito no layout (navegador) | 2 | 2 | 4 | padrão |
| A6 — documento de candidatas a pacote | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP (partições válidas e inválidas isoladas), tabela de decisão (escalada na A1, ver Mapa), tabela estado × operação (chaves), 2-switch (desligar e religar), matriz papel × ação, BVA não se aplica (nenhuma faixa ordenável: os quatro domínios são listas fechadas)
- Cenários: 34 · Regras: 16 · Mutantes previstos: 66 · Sem matador: 0
<!-- derivado do arquivo por grep -c (comandos em references/template-04.md, "Contagem do cabeçalho"); CT-B e seus mutantes estão no 05 -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | coluna de aparência no usuário; chave booleana na organização; três propriedades novas no settings da instalação (chave, fonte, menu); componente Livewire da seção Aparência no Meu perfil; campo no cadastro da organização; documento `wikis/candidatas-a-pacote.md` | CT-01…CT-34 |
| F | resolver a aparência efetiva por contexto (instalação, organização, pessoa); gravar a escolha; ligar/desligar chaves; listar candidatas a pacote | CT-09…CT-14, CT-01…CT-08, CT-17…CT-24, CT-32…CT-34 |
| D | quatro vocabulários fechados (densidade 3, fonte 4, cor 16 da paleta do kit, menu 2) + "Padrão"; valor gravado fora do vocabulário, vazio, de tipo errado, JSON escalar; **dado de outra pessoa** (Bia) e **de outra organização** (Globex) | CT-04, CT-05, CT-06, CT-14, CT-15, CT-16 |
| I | três telas de Meu perfil (`/admin`, `/app`, `/infra`); tela de Configurações da aplicação; cadastro e edição de organização; toda página autenticada dos três painéis; telas sem sessão (login); `_startUpload` e chamadas diretas por `$wire` | CT-07, CT-09, CT-11, CT-20, CT-22, CT-29 |
| P | sqlite em memória nos testes (cast de JSON e booleano); Chromium headless para o recolher do menu (Alpine do Filament) e o tamanho calculado da fonte; tenancy ligada × desligada (duas suítes) | CT-B01, CT-B02 (05); suítes `tests/Kit` e `tests/Tenancy` |
| O | quem escolhe (qualquer pessoa autenticada), quem libera (quem administra a instalação), quem não pode (`infra`, `panel_user`, `admin_app`); visitante sem sessão; uso indevido: payload forjado por `$wire` | CT-06, CT-11, CT-27, CT-28 |
| T | ordem de visita entre duas organizações no mesmo processo (cache de cor/aparência entre requests); chave desligada **depois** de a tela abrir; desligar e religar (2-switch) | CT-08, CT-14, CT-17, CT-18 |

## Matriz estado × operação (chaves)

Estados: E1 instalação ligada + organização liberada · E2 instalação ligada + organização não liberada · E3 instalação desligada.
Operações: O1 ver a aparência efetiva dentro da organização · O2 abrir a seção Aparência do Meu perfil · O3 gravar pela seção.
**3 estados × 3 operações = 9 células**, 7 válidas (✅) e 2 que devolvem o padrão / recusam (❌ = a escolha pessoal não aparece; nenhuma gravação nova vale).

| | O1 ver | O2 seção | O3 gravar |
|---|---|---|---|
| E1 | ✅ CT-12 (linha D1) | ✅ CT-30 (linha liberada) | ✅ CT-30 (linha liberada) |
| E2 | ❌ padrão — CT-12 (linha D2) | ✅ CT-30 (linha não liberada) — o Meu perfil não está em organização (P-06, P-13) | ✅ CT-30 (linha não liberada) |
| E3 | ❌ padrão — CT-12 (linhas D3, D4) | ❌ seção ausente — CT-29 (linhas desligada) | ❌ invariante CT-08; direção aberta (Q?1) |

Legenda conferida célula a célula: a operação O1 não tem efeito colateral além da página; O3 tem um efeito (a escrita no registro da pessoa), afirmado em CT-30 (✅) e, em E3, só o invariante observável de CT-08 até Q?1.

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — A pessoa grava qualquer valor do vocabulário de cada preferência, ou "Padrão", uma preferência independente da outra | A2 (padrão) | RQ-01, RQ-02, RQ-03, RQ-04, RQ-10, P-07, P-08, P-09 | EP (partição válida por caso do enum) | CT-01, CT-02, CT-03 |
| R2 — Valor fora do vocabulário ou de tipo errado, enviado pela seção, não é gravado | A2 (padrão) | P-12, P-07, `## Superfície Livewire` (`$data`) | EP (inválidas isoladas) | CT-04, CT-05 |
| R3 — A gravação atinge só a própria pessoa e só as quatro preferências | A2 (padrão) | RQ-01..RQ-04 ("guardada no perfil dela"), P-01, `## Superfície Livewire` (`salvar()`, `_startUpload`) | matriz papel × ação; EP | CT-06, CT-07, CT-08 |
| R4 — Fora de organização, a escolha vale com a chave da instalação ligada; desligada ou sem escolha, vale o padrão da instalação | A1 (padrão) | RQ-05, RQ-07, RQ-10, P-01, P-02, P-10, P-13 | tabela de decisão | CT-09, CT-10, CT-11 |
| R5 — Dentro de organização, a escolha só vale com as duas chaves; senão, a cor da organização e, depois, o padrão da instalação | A1 (padrão) | RQ-08, RQ-09, P-02, P-04, P-10, P-13, P-01 | tabela de decisão | CT-12, CT-13, CT-14 |
| R6 — Valor gravado fora do vocabulário cai no padrão na leitura, sem erro | A1 (padrão) | P-12, `## Superfície Livewire` (`users.aparencia` lido no render) | EP (inválidas isoladas) | CT-15, CT-16 |
| R7 — Desligar uma chave não apaga a escolha; religada, ela volta a valer | A1 (padrão) | P-05, P-11 | 2-switch | CT-17, CT-18, CT-19 |
| R8 — A instalação define o padrão das quatro preferências e a chave, e o gravado governa a página | A3 (padrão) | RQ-06, RQ-07, P-03, P-08, P-09 | EP; valor de fábrica literal | CT-20, CT-21 |
| R9 — A organização libera pelo interruptor no cadastro do `/admin`, que nasce desligado e só aparece com a chave da instalação ligada | A4 (padrão) | RQ-08, P-04, P-11 | EP; tabela de decisão (visibilidade) | CT-22, CT-23, CT-24 |
| R10 — Os formulários de administração não gravam valor fora do domínio | A3, A4 (padrão) | `## Superfície Livewire` (`ConfiguracoesDoKit`, `TenantForm`), P-12 | EP (inválidas isoladas) | CT-25, CT-26 |
| R11 — Só quem administra a instalação liga a chave, define os padrões e libera organizações | A3, A4 (padrão) | RQ-07, RQ-08, P-11 | matriz papel × ação (por fora do componente) | CT-27, CT-28 |
| R12 — A seção Aparência aparece no Meu perfil dos três painéis só com a chave da instalação ligada, com o aviso sobre organizações | A2 (padrão) | P-06, P-13 | EP; tabela de decisão | CT-29, CT-30, CT-31 |
| R13 — O efeito no layout: "Esconder tudo" some com o menu inteiro ao recolher; os tamanhos de fonte escalam pelos percentuais | A5 (padrão) | RQ-02, RQ-04, P-08, P-09 | EP; valor literal do requisito | CT-B01, CT-B02 (no `05`) |
| R14 — O documento de candidatas a pacote existe em `wikis/`, declara os dois propósitos e viaja com o kit | A6 (mínimo) | RQ-12, P-14 | EP | CT-32 |
| R15 — As preferências de aparência entram no documento como candidata | A6 (mínimo) | RQ-13 | EP | CT-33 |
| R16 — O documento lista o que o kit implementou e poderia virar pacote, a partir do código | A6 (mínimo) | RQ-14 | EP | CT-34 |

- Técnica escalada: A1 é `padrão`, mas R4/R5 usam **tabela de decisão completa** (não colapsada) — a regra tem cinco condições (contexto, chave da instalação, chave da organização, escolha, cor da organização) e o colapso esconderia justamente o `E`→`OU` entre as duas chaves.
- RQ-11 — sem cenário, justificado: é restrição de entrega (sem pacote externo agora); nenhuma implementação errada plausível muda comportamento observável da aplicação. Conferência pelo quality gate no diff de `composer.json`/`composer.lock` (nenhuma dependência nova).
- Nenhuma `RQ` aberta no `00`. Recortes bloqueados por perguntas novas desta derivação:
  - P-06/RQ-07 — cobertura parcial: CT-08 afirma o invariante (a página continua no padrão); recorte "gravar com a chave desligada: recusa ou grava e ignora" aberto (Q?1)
  - RQ-08/RQ-07 — cobertura parcial: CT-25 e CT-26 afirmam que o valor de tipo errado não é gravado como veio; recorte "valor não booleano liga/libera ou não" aberto (Q?2)
  - P-06 — cobertura parcial: CT-30 e CT-31 cobrem o aviso no `/app` com e sem organizações; recorte "aviso também no Meu perfil de `/admin` e `/infra` com tenancy" aberto (Q?3)

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Seção Aparência (gravação pela pessoa) | R1, R2, R3, R12 (CT-30, CT-31) | componente Livewire/Filament | existente — padrão de `tests/Kit/DefinirSenhaPorEmailTest.php` (componente Livewire do Meu perfil); arquivos novos `tests/Kit/PreferenciasDeAparenciaTest.php` e `tests/Tenancy/PreferenciasDeAparenciaTenancyTest.php` | a regra afirma o que o cliente consegue gravar pelo componente (`$data`, `salvar()`, `_startUpload`) — só o `Livewire::test()` alcança essa superfície | |
| G2 — Aparência efetiva na página servida | R4, R5, R6, R7, R12 (CT-29) | Pest feature HTTP | existente — `tests/Kit/DensidadeDoLayoutTest.php` (GET + HTML servido) e `tests/Tenancy/PaletaDaOrganizacaoTest.php` (GET + `FilamentColor`); arquivos novos `tests/Kit/AparenciaEfetivaTest.php` e `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | o observável é a página servida (HTML e paleta registrada) depois de um request real que boota o painel e o middleware de tenant | |
| G3 — Telas de administração (settings e organização) | R8, R9, R10 | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` (`chaveDoCampo()`/`carregarAba()`) e `tests/Tenancy/PaletaDaOrganizacaoTest.php` (`CreateTenant`/`EditTenant`); arquivos novos `tests/Kit/ConfiguracoesDeAparenciaTest.php` e `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | gate de tela de escrita: `fillForm` → `save`/`create` → valor gravado; visibilidade do campo | |
| G4 — Autorização por fora do componente | R11 | Pest feature HTTP | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` e `tests/Tenancy/PermissoesDeTenantResourceTest.php`; casos novos nos arquivos de G3 | gate de camada da regra: a barreira tem de valer pela rota e pela policy, não só pela tela | |
| G5 — Layout no navegador | R13 | browser | existente — `tests/Browser/CabecalhoDoPainelTest.php` (geometria por `getComputedStyle`/`getBoundingClientRect`); arquivo novo `tests/Browser/PreferenciasDeAparenciaTest.php` | o recolher do menu é Alpine executado e o tamanho da fonte é o valor calculado pelo Chromium depois da cascata — o HTML servido é igual com e sem o defeito | |
| G6 — Documento de candidatas | R14, R15, R16 | Pest feature HTTP | existente — `tests/Kit/SiteDeDocumentacaoTest.php` (lê `wikis/roadmap.md`) e `tests/Kit/KitUpdateTest.php` (`caminhosDoKit()`); arquivo novo `tests/Kit/CandidatasAPacoteTest.php` | lê arquivo da árvore com a aplicação de pé (`base_path()`, `caminhosDoKit()`); sem HTTP | |

**Divergência declarada (Project Rule venceu a skill):** a skill situa `Pest feature HTTP` em `tests/Feature`; aqui os casos vão para `tests/Kit` e `tests/Tenancy` — as suítes do kit em `phpunit.xml` — e todo caso que precise de `admin_app` vai para `tests/Tenancy` (`.ai/rules/testes.md`, "Nem todo papel do kit existe em toda suíte").

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `PreferenciasDeAparencia`, `salvar()`, `$data`, `AparenciaDoUsuario`, `TamanhoDaFonte`, `ModoDoMenu`, valores dos casos (`muito-grande`, `oculto`…), coluna `users.aparencia`, chaves do JSON, propriedades `aparencia_por_usuario`/`tamanho_da_fonte`/`modo_do_menu`, coluna `tenants.aparencia_por_usuario` | escolha de implementação | detalhe do cenário (o executor usa os nomes; o `Então` afirma a página e o valor gravado) |
| "`salvar()` recusa com a chave desligada" (coluna Fronteira do `02`) | comportamento que o `00` não determina | pergunta Q?1; CT-08 afirma só o invariante |
| "valor fora do vocabulário cai em `null`" na gravação (coluna Fronteira do `02`) | o `00` (P-12) fala da **leitura**; na gravação ele só determina que o valor inválido não vale | CT-04/CT-05 afirmam "não gravado como veio" + página no padrão, que vale para recusar ou coagir |
| "`mutateFormDataBeforeSave()` coage fonte e menu" | mecanismo | CT-25 afirma só o invariante (gravado ∈ vocabulário) |
| "auditado por `AuditsFillables`" | o `00` não pede trilha de auditoria (Proibição 12) | sem cenário |
| forma da declaração de CSS da fonte (render hook, `--text-*`) | mecanismo | CT-B02 mede o tamanho calculado; o HTTP só identifica **qual** tamanho foi emitido |
| rótulos "Liberar a aparência pessoal" e "Aparência dos painéis" | texto visível que só o PRD determina | nenhum `Então` depende deles (campos achados por nome); pergunta Q?4 |
| fonte de fábrica = Normal (CT-21) | **não** recusado: vem do `00` — P-08 chama Normal de "o padrão do Filament, nada muda" e o roadmap diz que a fonte "não existe" hoje (nascer diferente mudaria toda instalação) | cenário com o valor literal |
| classes do `<body>` para o modo do menu | **não** vem do plano: é o observável do vendor (`vendor/filament/filament/resources/views/components/layout/index.blade.php:$isSidebarCollapsibleOnDesktop:27`, `:$isSidebarFullyCollapsibleOnDesktop:28`) | observável do menu na camada HTTP |

**Perguntas geradas pela derivação** (provisórias; a sessão renumera ao gravar):

❓ Q?1 · raia: requisito · afeta: P-06, RQ-07 · depende de: —
Com a chave da instalação desligada **depois** que a pessoa abriu o Meu perfil, ela ainda envia a gravação da seção Aparência. O sistema recusa (nada é gravado, com aviso) ou grava e apenas ignora enquanto a chave estiver desligada?
➡️ Recomendação: recusar, sem gravar e com aviso — falha fechado: a chave desligada já some com a seção (P-06), e aceitar a escrita cria um estado que ninguém escolheu ver; o invariante "a página continua no padrão" vale nas duas leituras e já é o CT-08.

❓ Q?2 · raia: requisito · afeta: RQ-07, RQ-08, P-04 · depende de: —
Um valor que não é ligado/desligado chega à chave da instalação ou à da organização (payload forjado: `'talvez'`, `['1']`). O sistema recusa o envio ou o converte — e, se converte, para desligado?
➡️ Recomendação: recusar com erro no campo e manter o valor anterior — falha fechado, o mesmo arranjo de P-04 (liberar só por ato explícito); o invariante "o texto forjado não é gravado como veio, e a tela não quebra" já é o CT-25/CT-26.

❓ Q?3 · raia: requisito · afeta: P-06 · depende de: —
Com organizações ligadas, o aviso "numa organização que não liberou, vale o padrão dela" aparece só no Meu perfil do `/app` ou também no do `/admin` e do `/infra` (a escolha é uma só, P-01)?
➡️ Recomendação: nos três painéis quando a tenancy está ligada — a escolha feita no `/admin` vale igualmente dentro das organizações, e quem a faz ali precisa do mesmo aviso.

❓ Q?4 · raia: requisito · afeta: RQ-06, RQ-08, P-11 · depende de: —
Os rótulos "Liberar a aparência pessoal" (cadastro da organização) e "Aparência dos painéis" (seção das Configurações) existem só no plano. São esses os textos?
➡️ Recomendação: sim, como no plano — nenhum cenário depende deles; a pergunta só fecha a lacuna de texto visível sem origem no requisito.

## Setup Global

### Personas
- **Ana** — Kit: `usuarioDoKit('master_global', 'ana@example.com')` (entra nos três painéis pelo `Gate::before`). Tenancy: `duasOrganizacoes()['usuario']`, rebatizada `ana@example.com` — membro da Acme e da Globex (`panel_user`) e `admin` global
- **Bia** — `usuarioDoKit('panel_user', 'bia@example.com')` (Kit) / `usuarioComPapel('panel_user', $acme, 'bia@example.com')` (Tenancy): a outra pessoa, com escolha própria gravada, para provar fronteira de identidade
- **Administradora** — Kit: `usuarioDoKit('admin', 'adm@example.com')`; Tenancy: `usuarioComPapel('master_global', email: 'adm@example.com')` com `Filament::setCurrentPanel('admin')` (padrão `administradoraNoAdmin()` de `PaletaDaOrganizacaoTest`)
- **Ivo** — `usuarioDoKit('infra', 'ivo@example.com')`; **Carla** — `usuarioComPapel('admin_app', $acme, 'carla@example.com')` (só `tests/Tenancy`)
- Segunda pessoa no mesmo `it()`: `test()->flushSession()` antes do `actingAs` (`.ai/rules/testes.md`)

### Fixtures
- **Instalação de referência** (todos os grupos salvo dito): chave ligada, densidade **Compacto**, fonte **Grande**, menu **Esconder tudo**, cor da paleta **Teal** e, no Kit, cor livre `#0ea5e9` (que vence a Teal pela regra existente). Os quatro valores diferem da fábrica (Confortável, Normal, Manter os ícones, âmbar) — é o que distingue "padrão da instalação" de "padrão de fábrica"
- **Escolha de referência da Ana**: densidade **Denso**, fonte **Muito grande**, cor **Red**, menu **Manter os ícones**. Os quatro diferem da instalação de referência
- Organizações (Tenancy): `duasOrganizacoes()` — Acme com cor `#7c3aed`, Globex com `#059669`; **Initech** sem cor (`tenant('Initech', 'initech')`), Ana membro. Chave da organização por `$org->update(['aparencia_por_usuario' => …])`
- Novo helper em `tests/Pest.php` (usado por Kit, Tenancy e Browser): `aparenciaDaInstalacao(?bool $porUsuario, ?string $densidade, ?string $fonte, ?string $menu, ?string $cor, ?string $corHex)` — `gravarConfiguracao()` de cada propriedade + `alinharConfiguracoesDoKit()` (o boot do teste roda antes das migrations; ver `DensidadeDoLayoutTest`)
- Novo helper em `tests/Pest.php`: `aparenciaDe(User $u, mixed $bruto)` — grava a coluna **crua** por `DB::table('users')->update(...)`, sem passar pelo cast nem pelo componente (fixture de leitura; não há ciclo de vida nesta entidade)
- Novo helper em `tests/Pest.php`: `aparenciaEfetiva(string $url): array{densidade, fonte, cor, menu, html}` — `fronteiraDeRequest()`, GET, `assertOk()`, e lê os quatro observáveis da tabela abaixo. **Nunca** chama o código da feature para decidir o que espera (a tabela de cada string é literal no helper)

| Preferência | Observável na página servida | Origem do observável |
|---|---|---|
| densidade | a declaração `--spacing` do nível (`compacto` → `--spacing:0.2rem`, `denso` → `--spacing:0.175rem`); nenhuma → Confortável | contrato existente de `tests/Kit/DensidadeDoLayoutTest.php` (CT-01, CT-03) |
| fonte | qual declaração de tamanho de fonte foi emitida (Pequena, Grande ou Muito grande); nenhuma das três → Normal. O número é provado no navegador (CT-B02) | P-08 |
| cor | `FilamentColor::getColors()['primary']` depois do GET: `Color::{Nome}` ou a paleta do hexadecimal (mesma comparação de `tests/Kit/CorPrimariaTest.php` / `tests/Tenancy/PaletaDaOrganizacaoTest.php`) | regra existente de `CorPrimaria::resolver()` |
| menu | exatamente uma das classes do `<body>`: `fi-body-has-sidebar-collapsible-on-desktop` (Manter os ícones) ou `fi-body-has-sidebar-fully-collapsible-on-desktop` (Esconder tudo) | vendor, citado na Fronteira |

### Fakes
- Nenhum: a feature não envia e-mail, não enfileira job, não chama HTTP externo

### Estratégia de DB e arnês
- `RefreshDatabase` global por pasta (`tests/Pest.php`: `->in('Kit')`, `->in('Tenancy')`, `->in('Browser')`)
- Componente no `/app` (Kit): GET real do Meu perfil antes do primeiro `Livewire::test()` (`noPainelBootado('app')` não serve — `.ai/rules/testes.md`)
- Depois de gravar pela tela de Configurações, `alinharConfiguracoesDoKit()` antes do GET seguinte (simula o boot do próximo request)
- Tela de Configurações: campos por `chaveDoCampo()` e aba adiada por `carregarAba()`; `assertDontSee` sempre com controle positivo da mesma tela

---

## Regra R1 — A pessoa grava qualquer valor do vocabulário, ou "Padrão", uma preferência independente da outra

> `RQ-01`, `RQ-02`, `RQ-03`, `RQ-04`, `RQ-10`, `P-07`, `P-08`, `P-09` · perfil **padrão** · técnica: **EP** (uma partição por caso de cada vocabulário que o `00` nomeia; cor por amostra das pontas da lista)

```gherkin
# language: pt
Funcionalidade: Preferências de aparência por usuário

  Contexto:
    Dado a instalação de referência com a chave da instalação ligada
    E a Ana sem nenhuma escolha gravada

  Regra: a pessoa grava qualquer valor do vocabulário de cada preferência

    Esquema do Cenário: [CT-01] a escolha gravada pela seção Aparência governa a página seguinte
      Quando a Ana grava <preferência> = "<valor>" na seção Aparência do Meu perfil do /admin
      Então o perfil dela guarda <preferência> = "<valor>"
      E a página /admin servida a ela mostra <preferência> = "<valor>"
      E as outras três preferências mostram o padrão da instalação

      Exemplos:
        | preferência | valor            | # partição                                   |
        | densidade   | Compacto         | caso do enum                                 |
        | densidade   | Denso            | caso do enum                                 |
        | densidade   | Confortável      | igual à fábrica, diferente da instalação     |
        | fonte       | Pequena          | caso (87,5 %)                                |
        | fonte       | Normal           | igual à fábrica, diferente da instalação     |
        | fonte       | Muito grande     | caso (125 %)                                 |
        | cor         | Amber            | primeira da paleta do kit                    |
        | cor         | Violet           | última da paleta do kit                      |
        | menu        | Manter os ícones | igual à fábrica, diferente da instalação     |

    Cenário: [CT-02] "Padrão" numa preferência a solta da escolha e deixa as outras como estavam
      Dado a Ana com densidade "Denso" e fonte "Muito grande" gravadas
      E a Ana grava densidade "Padrão" na seção Aparência
      Quando a instalação passa a ter densidade "Confortável"
      Então a página /admin servida à Ana mostra densidade "Confortável"
      E mostra fonte "Muito grande"

    Cenário: [CT-03] a seção oferece exatamente o vocabulário de cada preferência, mais "Padrão"
      Quando a Ana abre a seção Aparência do Meu perfil do /admin
      Então a densidade oferece "Padrão", "Confortável", "Compacto" e "Denso"
      E a fonte oferece "Padrão", "Pequena", "Normal", "Grande" e "Muito grande"
      E a cor oferece "Padrão" e exatamente as 16 cores da paleta do kit, sem campo de hexadecimal
      E o menu oferece "Padrão", "Manter os ícones" e "Esconder tudo"
```

Notas: a "paleta do kit" é `CustomizadorDaInstalacao::CORES` (a mesma lista do settings, P-07). Grande e Esconder tudo ficam fora de CT-01 porque são os valores da instalação de referência — a linha não discriminaria; Grande é exercitado em CT-B02 e Esconder tudo em CT-B01 e CT-20.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M01 | escolha igual ao valor de fábrica tratada como "sem escolha" (`if ($valor === padraoDeFabrica) null`) | CT-01 (linhas Confortável, Normal, Manter os ícones) | página mostra Compacto / Grande / Esconder tudo (a instalação); esperado o valor escolhido |
| M02 | "Padrão" gravado como o valor corrente da instalação (congela) em vez de ausente | CT-02 | depois da troca da instalação, a página mostra Compacto; esperado Confortável |
| M03 | salvar uma preferência sobrescreve as outras com nulo | CT-02 | fonte vira Grande (instalação); esperado Muito grande |
| M04 | opções de cor por reflexão sobre `Color` (traz `Gray`, `Zinc`, `WCAG_AA_TEXT`…) | CT-03 | a lista de cor tem mais de 16 itens / contém `Gray` |
| M05 | caso do enum faltando nas opções (ex.: Muito grande esquecido, ou Pequena) | CT-01 (linhas Pequena, Muito grande), CT-03 | a lista de fonte sem "Muito grande"; a gravação da linha é recusada pelo `in()` |

---

## Regra R2 — Valor fora do vocabulário ou de tipo errado, enviado pela seção, não é gravado

> `P-12`, `P-07`, Superfície Livewire (`$data` com chaves `densidade`, `fonte`, `cor`, `menu`) · perfil **padrão** · técnica: **EP**, uma partição inválida por linha, as demais chaves em "Padrão"

```gherkin
  Regra: valor fora do vocabulário enviado pela seção não é gravado

    Esquema do Cenário: [CT-04] valor fora do domínio escrito direto no estado do componente não vale
      Dado a Ana sem escolha gravada e com a seção Aparência aberta
      Quando a Ana envia a gravação com <chave> = <valor> escrito direto no estado do componente
      Então o perfil dela não guarda <valor> em <chave>
      E a página /admin servida a ela mostra <chave> no padrão da instalação
      E o HTML servido não contém o texto "alert(1)"

      Exemplos:
        | chave     | valor                                    | # partição inválida                          |
        | densidade | "ultra"                                  | texto fora do enum                           |
        | fonte     | "gigante"                                | texto fora do enum                           |
        | menu      | "nenhum"                                 | texto fora do enum                           |
        | cor       | "Gray"                                   | constante de `Color` fora da paleta do kit   |
        | cor       | "WCAG_AA_TEXT"                           | constante de `Color` que não é cor           |
        | cor       | "#ff0000"                                | hexadecimal livre (P-07)                     |
        | cor       | "Red;}</style><script>alert(1)</script>" | texto que viraria CSS/HTML                   |

    Esquema do Cenário: [CT-05] valor de tipo errado escrito no estado do componente não vale e não quebra a tela
      Dado a Ana sem escolha gravada e com a seção Aparência aberta
      Quando a Ana envia a gravação com <chave> = <valor>
      Então a resposta do componente não é erro de servidor
      E o perfil dela não guarda <chave>
      E a página /admin servida a ela mostra as quatro preferências no padrão da instalação

      Exemplos:
        | chave     | valor        | # tipo errado |
        | densidade | ["denso"]    | lista         |
        | fonte     | 3            | inteiro       |
        | cor       | {"a": "Red"} | objeto        |
        | menu      | true         | booleano      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M06 | validação só pelo `in()` do Select na tela; `salvar()` grava `$this->data` cru | CT-04 (linhas "ultra", "gigante", "nenhum") | o perfil guarda `"ultra"`; esperado ausente |
| M07 | cor validada por `defined(Color::X)` em vez da paleta do kit | CT-04 (linhas "Gray", "WCAG_AA_TEXT") | guarda `"Gray"` e a página mostra a paleta Gray; esperado a cor da instalação |
| M08 | cor aceita hexadecimal válido (reaproveita a regra da instalação, "hex vence") | CT-04 (linha "#ff0000") | a página mostra a paleta de `#ff0000`; esperado `#0ea5e9` |
| M09 | CSS da página montado a partir do texto gravado | CT-04 (linha de injeção) | o HTML contém `alert(1)` |
| M10 | `tryFrom()`/`in_array()` sobre valor não escalar sem guarda (`TypeError`) | CT-05 (linhas lista, objeto) | o componente responde erro 500 |

---

## Regra R3 — A gravação atinge só a própria pessoa e só as quatro preferências

> `RQ-01..RQ-04` ("guardada no perfil dela"), `P-01`, Superfície Livewire (`salvar()`, `_startUpload`) · perfil **padrão** · técnica: **matriz papel × ação** (dona × outra pessoa) e **EP**

```gherkin
  Regra: a gravação atinge só a própria pessoa e só as quatro preferências

    Esquema do Cenário: [CT-06] campo extra no envio não grava nada fora das preferências da própria Ana
      Dado a Bia com densidade "Compacto" gravada
      E a Ana com o e-mail "ana@example.com"
      Quando a Ana envia <tela> com densidade "Denso" e o campo extra <extra>
      Então o perfil da Ana guarda densidade "Denso" e nenhuma chave além das quatro preferências
      E a Bia continua com densidade "Compacto"
      E o e-mail da Ana continua "ana@example.com"

      Exemplos:
        | tela                                | extra                                    | # superfície                      |
        | a seção Aparência                   | id = id da Bia                           | alvo forjado                      |
        | a seção Aparência                   | user_id = id da Bia                      | alvo forjado                      |
        | a seção Aparência                   | email = "outra@example.com"              | mass assignment no usuário        |
        | a seção Aparência                   | tema = "x"                               | chave estranha no JSON            |
        | o formulário de dados pessoais do Meu perfil | aparencia = {"densidade": "Denso"} — sem a seção | coluna nova `fillable` por outra tela |

    Cenário: [CT-07] upload para o componente da seção é recusado
      Dado a Ana com a seção Aparência aberta
      Quando o cliente da Ana pede o início de um upload para "data.cor"
      Então a resposta é 403
      E o perfil da Ana continua sem escolha gravada
      E o Meu perfil do /admin continua abrindo para a Ana com status 200

    Cenário: [CT-08] chave desligada depois de a seção abrir: a página continua no padrão da instalação
      Dado a Ana sem escolha gravada e com a seção Aparência aberta
      E a chave da instalação desligada em seguida
      Quando a Ana envia a gravação com densidade "Denso"
      Então a página /admin servida à Ana mostra densidade "Compacto"
```

Notas: a última linha de CT-06 tem `Então` próprio para a coluna, já que lá a escolha "Denso" não é enviada pela seção: o perfil da Ana continua **sem** escolha. CT-08 é o invariante das duas leituras de Q?1; a direção (recusa sem gravar × grava e ignora) fica sem cenário até a resposta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | `salvar()` resolve o usuário por id vindo do estado (`User::find($this->data['id'] ?? auth()->id())`) | CT-06 (linhas id, user_id) | a Bia passa a ter Denso; esperado Compacto |
| M12 | `salvar()` faz `$user->update($this->data)` | CT-06 (linha email) | e-mail da Ana vira `outra@example.com` |
| M13 | o JSON grava `$this->data` inteiro | CT-06 (linha tema) | o perfil guarda a chave `tema` |
| M14 | `aparencia` entra no `$fillable` e o formulário de dados pessoais repassa o estado cru | CT-06 (última linha) | o perfil da Ana guarda Denso sem ter passado pela seção |
| M15 | componente sem a restrição de upload a campos do schema | CT-07 | a resposta é 200 (upload aceito); esperado 403 |
| M16 | escolha lida mesmo com a chave desligada quando foi gravada depois do desligamento | CT-08 | página mostra Denso; esperado Compacto |

---

## Regra R4 — Fora de organização, a escolha vale com a chave da instalação ligada; desligada ou sem escolha, vale o padrão da instalação

> `RQ-05`, `RQ-07`, `RQ-10`, `P-01`, `P-02`, `P-10`, `P-13` · perfil **padrão** · técnica: **tabela de decisão** (contexto × chave × escolha) · suíte `tests/Kit` (sem organizações)

```gherkin
  Regra: fora de organização, a escolha vale só com a chave da instalação ligada

    Esquema do Cenário: [CT-09] a escolha da Ana vale nos três painéis com a chave ligada, e some com ela desligada
      Dado a instalação de referência com a chave <chave>
      E a Ana com a escolha de referência gravada
      Quando a Ana abre <página>
      Então a página mostra densidade "<densidade>", fonte "<fonte>", cor "<cor>" e menu "<menu>"

      Exemplos:
        | chave     | página | densidade | fonte        | cor     | menu             | # regra                         |
        | ligada    | /admin | Denso     | Muito grande | Red     | Manter os ícones | pessoal vence a cor livre (P-02)|
        | ligada    | /app   | Denso     | Muito grande | Red     | Manter os ícones | mesma escolha (P-01)            |
        | ligada    | /infra | Denso     | Muito grande | Red     | Manter os ícones | mesma escolha (P-01)            |
        | desligada | /admin | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |
        | desligada | /app   | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |
        | desligada | /infra | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |

    Cenário: [CT-10] quem não escolheu vê o padrão da instalação, não o de fábrica nem o de outra pessoa
      Dado a instalação de referência com a chave ligada
      E a Bia com a escolha de referência gravada
      E a Ana sem nenhuma escolha gravada
      Quando a Ana abre /admin
      Então a página mostra densidade "Compacto", fonte "Grande", cor "#0ea5e9" e menu "Esconder tudo"

    Esquema do Cenário: [CT-11] tela sem pessoa logada mostra o padrão da instalação
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      Quando um visitante sem sessão abre <tela>
      Então a resposta é 200
      E a página mostra densidade "Compacto", fonte "Grande" e cor "#0ea5e9"

      Exemplos:
        | tela        |
        | /admin/login |
        | /app/login   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | cor pessoal passada como "nome" a `CorPrimaria::resolver(hexDaInstalacao, nomePessoal)` — o hexadecimal da instalação vence | CT-09 (linhas ligada) | cor `#0ea5e9`; esperado Red |
| M18 | chave da instalação ignorada (lê sempre a escolha) | CT-09 (linhas desligada) | densidade Denso; esperado Compacto |
| M19 | aplicação só no painel `/app` (render hook registrado em um provider) | CT-09 (linhas /admin, /infra) | densidade Compacto no /admin ligado; esperado Denso |
| M20 | sem escolha, cai no valor de fábrica do enum, não no da instalação | CT-10 | densidade Confortável / fonte Normal / menu Manter os ícones; esperado o da instalação |
| M21 | leitura de `auth()->user()->aparencia` sem guarda de usuário nulo | CT-11 | resposta 500; esperado 200 |

---

## Regra R5 — Dentro de organização, a escolha só vale com as duas chaves; senão, a cor da organização e, depois, o padrão da instalação

> `RQ-08`, `RQ-09`, `P-01`, `P-02`, `P-04`, `P-10`, `P-13` · perfil **padrão** · técnica: **tabela de decisão completa** (escalada) · suíte `tests/Tenancy`. Instalação de referência **sem** cor livre (cor Teal)

```gherkin
  Regra: dentro de organização, a escolha só vale com a chave da instalação e a da organização ligadas

    Esquema do Cenário: [CT-12] a tabela das duas chaves decide o que a Ana vê
      Dado a instalação de referência com a chave <instalação>
      E a Acme, com cor "#7c3aed", com a chave <organização>
      E a Ana, membro da Acme, com a escolha de referência gravada
      Quando a Ana abre <página>
      Então a página mostra densidade "<densidade>", fonte "<fonte>", cor "<cor>" e menu "<menu>"

      Exemplos:
        | instalação | organização | página          | densidade | fonte        | cor     | menu             | # regra                            |
        | ligada     | ligada      | /app/acme       | Denso     | Muito grande | Red     | Manter os ícones | D1 duas chaves; pessoal vence a cor da organização (P-02) |
        | ligada     | desligada   | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D2 organização não liberou (P-10)  |
        | desligada  | ligada      | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D3 as duas, não uma (RQ-09)        |
        | desligada  | desligada   | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D4                                 |
        | ligada     | desligada   | /app/meu-perfil | Denso     | Muito grande | Red     | Manter os ícones | D5 fora de organização (P-13)      |
        | desligada  | ligada      | /app/meu-perfil | Compacto  | Grande       | Teal    | Esconder tudo    | D6                                 |
        | ligada     | desligada   | /admin          | Denso     | Muito grande | Red     | Manter os ícones | D7 /admin não consulta organização |

    Esquema do Cenário: [CT-13] com as duas chaves e a cor em "Padrão", vale a cor da organização e, sem ela, a da instalação
      Dado a instalação de referência com a chave ligada
      E <organização> com a chave ligada
      E a Ana, membro dela, com só a densidade "Denso" gravada
      Quando a Ana abre /app/<slug>
      Então a página mostra densidade "Denso", cor "<cor>", fonte "Grande" e menu "Esconder tudo"

      Exemplos:
        | organização           | slug    | cor     |
        | a Acme, cor "#7c3aed" | acme    | #7c3aed |
        | a Initech, sem cor    | initech | Teal    |

    Esquema do Cenário: [CT-14] a aparência de uma organização não vaza para a seguinte no mesmo processo
      Dado a instalação de referência com a chave ligada
      E a Acme com a chave ligada e a Globex, cor "#059669", com a chave desligada
      E a Ana, membro das duas, com a escolha de referência gravada, e que acabou de abrir /app/<primeira>
      Quando a Ana abre /app/<segunda>
      Então a página mostra densidade "<densidade>" e cor "<cor>"

      Exemplos:
        | primeira | segunda | densidade | cor     |
        | acme     | globex  | Compacto  | #059669 |
        | globex   | acme    | Denso     | Red     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M22 | `OU` entre as chaves no lugar de `E` | CT-12 (linhas D2, D3) | densidade Denso; esperado Compacto |
| M23 | dentro da organização, só a chave da organização é lida | CT-12 (linha D3) | densidade Denso com a instalação desligada |
| M24 | a cor da organização vence a da pessoa (precedência da cor por organização mantida em cima da pessoal) | CT-12 (linha D1) | cor `#7c3aed`; esperado Red |
| M25 | sem organização no endereço, lê a "organização da sessão" ou trata como não liberada | CT-12 (linhas D5, D7) | densidade Compacto em /app/meu-perfil e /admin; esperado Denso |
| M26 | "Padrão" na cor cai direto na instalação, pulando a organização | CT-13 (linha Acme) | cor Teal; esperado `#7c3aed` |
| M27 | aparência efetiva guardada por usuário (memo/singleton) no primeiro request | CT-14 (as duas linhas) | segunda organização com a densidade/cor da primeira |

---

## Regra R6 — Valor gravado fora do vocabulário cai no padrão na leitura, sem erro

> `P-12`, Superfície Livewire (`users.aparencia` lido no render; valor que vira `match`/`tryFrom`/CSS) · perfil **padrão** · técnica: **EP**, uma partição inválida por linha (inclui ausente ≠ nulo ≠ vazio)

```gherkin
  Regra: o que está gravado fora do vocabulário vale como "Padrão" na leitura

    Esquema do Cenário: [CT-15] a escolha gravada fora do vocabulário mostra o padrão da instalação, sem erro
      Dado a instalação de referência com a chave ligada
      E o perfil da Ana gravado diretamente como <gravado>
      Quando a Ana abre /admin
      Então a resposta é 200
      E a página mostra densidade "Compacto", fonte "Grande", cor "#0ea5e9" e menu "Esconder tudo"
      E o HTML servido não contém o texto "alert(1)"

      Exemplos:
        | gravado                                                | # partição                               |
        | {"densidade": "ultra"}                                 | texto fora do enum                       |
        | {"densidade": ""}                                      | vazio                                    |
        | {"densidade": null}                                    | nulo explícito                           |
        | {"fonte": "gigante"}                                   | texto fora do enum                       |
        | {"menu": "nenhum"}                                     | texto fora do enum                       |
        | {"cor": "Gray"}                                        | constante fora da paleta do kit          |
        | {"cor": "WCAG_AA_TEXT"}                                | constante que não é cor                  |
        | {"cor": "#ff0000"}                                     | hexadecimal (P-07)                       |
        | {"cor": "Red</style><script>alert(1)</script>"}        | texto que viraria CSS/HTML               |
        | {"densidade": ["denso"]}                               | tipo errado na chave                     |
        | "denso"                                                | JSON escalar no lugar do objeto          |
        | {}                                                     | objeto vazio (ausente)                   |

    Esquema do Cenário: [CT-16] padrão da instalação gravado fora do vocabulário cai no de fábrica, sem erro
      Dado a chave da instalação ligada e a <propriedade> da instalação gravada como "<gravado>"
      E a Ana sem nenhuma escolha gravada
      Quando a Ana abre /admin
      Então a resposta é 200
      E a página mostra <preferência> "<fábrica>"

      Exemplos:
        | propriedade      | gravado | preferência | fábrica          |
        | tamanho da fonte | gigante | fonte       | Normal           |
        | modo do menu     | nenhum  | menu        | Manter os ícones |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | `match` sem `default` / `from()` em vez de `tryFrom()` (`UnhandledMatchError`/`ValueError`) | CT-15 (linhas "ultra", "gigante", "nenhum"), CT-16 | resposta 500 |
| M29 | `constant(Color::class.'::'.$nome)` sem conferir a paleta do kit | CT-15 (linhas "Gray", "WCAG_AA_TEXT") | paleta Gray / erro com o float 4.5; esperado `#0ea5e9` |
| M30 | texto gravado interpolado no `<style>` | CT-15 (linha de injeção) | o HTML contém `alert(1)` |
| M31 | leitura assume array (`$aparencia['densidade']` sobre string) | CT-15 (linha JSON escalar) | resposta 500 |
| M32 | vazio `""` tratado como valor e passado adiante (declaração vazia, ou `tryFrom('')` sem `??`) | CT-15 (linha vazio) | densidade diferente de Compacto ou 500 |
| M33 | padrão da instalação lido cru da config | CT-16 | 500, ou nenhuma das duas classes de menu no `<body>` |

---

## Regra R7 — Desligar uma chave não apaga a escolha; religada, ela volta a valer

> `P-05`, `P-11` · perfil **padrão** · técnica: **2-switch** (desligar → religar; oráculo sobre o destino do segundo evento e sobre o registro do ciclo anterior)

```gherkin
  Regra: desligar uma chave ignora a escolha sem apagá-la

    Cenário: [CT-17] a chave da instalação desligada e religada pela tela devolve a escolha da Ana
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      Quando a administradora desliga a chave nas Configurações, a Ana abre /admin, e a administradora religa a chave
      Então, com a chave desligada, a página da Ana mostrou densidade "Compacto"
      E, religada, a página /admin servida à Ana mostra densidade "Denso" e cor "Red"
      E o perfil da Ana guarda a mesma escolha de referência de antes

    Cenário: [CT-18] a chave da organização desligada e religada pelo cadastro devolve a escolha da Ana
      Dado a instalação de referência com a chave ligada
      E a Acme com a chave ligada e a Ana, membro dela, com a escolha de referência gravada
      Quando a administradora desliga a chave da Acme na edição da organização, a Ana abre /app/acme, e a administradora a religa
      Então, com a chave desligada, a página da Ana mostrou densidade "Compacto"
      E, religada, a página /app/acme servida à Ana mostra densidade "Denso" e cor "Red"
      E o perfil da Ana guarda a mesma escolha de referência de antes

    Esquema do Cenário: [CT-19] com a chave da instalação desligada, editar a organização não mexe na chave dela
      Dado a chave da instalação desligada
      E a Acme com a chave ligada
      Quando a administradora salva a edição da Acme com <alteração>
      Então a Acme continua com a chave ligada
      E, com a chave da instalação religada, a Ana vê densidade "Denso" em /app/acme

      Exemplos:
        | alteração                                                       | # caminho                         |
        | só o nome trocado para "Acme S.A."                              | campo oculto desidratado como falso |
        | o interruptor oculto forjado como desligado no estado do formulário | campo oculto aceito do cliente    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | desligar a chave da instalação limpa as escolhas (hook no `save` das configurações) | CT-17 | o perfil da Ana sem escolha; página religada em Compacto |
| M35 | desligar a chave da organização limpa a escolha dos membros | CT-18 | o perfil da Ana sem escolha; /app/acme religado em Compacto |
| M36 | interruptor oculto desidratado como `false` no `save` | CT-19 (linha nome) | chave da Acme desligada depois da edição |
| M37 | interruptor oculto com `dehydratedWhenHidden()`/valor aceito do estado | CT-19 (linha forjado) | chave da Acme desligada |
| M38 | religar só vale para escolhas gravadas depois do religamento (escolha marcada como "inativa" ao desligar) | CT-17, CT-18 | religada, a página continua Compacto |

---

## Regra R8 — A instalação define o padrão das quatro preferências e a chave, e o gravado governa a página

> `RQ-06`, `RQ-07`, `P-03`, `P-08`, `P-09` · perfil **padrão** · técnica: **EP** + **valor de fábrica literal** (o `Dado` afirma o valor efetivo lido, não o ambiente)

```gherkin
  Regra: a instalação define o padrão das quatro preferências e a chave

    Cenário: [CT-20] o que a administradora grava nas Configurações governa a página de quem não escolheu
      Dado a chave da instalação ligada, fonte "Normal" e menu "Manter os ícones"
      E a Bia sem nenhuma escolha gravada
      Quando a administradora grava nas Configurações fonte "Grande", menu "Esconder tudo" e a chave desligada
      Então as configurações gravadas guardam fonte "Grande", menu "Esconder tudo" e a chave desligada
      E a página /app servida à Bia mostra fonte "Grande" e menu "Esconder tudo"

    Esquema do Cenário: [CT-21] os valores de fábrica são a chave ligada, a fonte Normal e o menu com ícones
      Dado uma instalação recém-migrada, sem gravação de teste nessas configurações
      Quando o valor efetivo de <configuração> é lido do arquivo de configuração sem variável de ambiente e da linha semeada no settings
      Então os dois valem "<fábrica>"

      Exemplos:
        | configuração       | fábrica          | # origem |
        | chave da instalação | ligada          | P-03     |
        | tamanho da fonte    | Normal          | P-08     |
        | modo do menu        | Manter os ícones | P-09    |
```

Notas: CT-21 usa `kitConfigCom('{variável}', null)` (arquivo de configuração) e `configuracaoGravada('{propriedade}')` (linha da migration de settings) — nunca `config()`, que mediria o `phpunit.xml`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | propriedade nova no settings sem linha no mapa de configuração (o campo grava e não governa) | CT-20 | configuração gravada Grande, página da Bia em Normal |
| M40 | campo da tela grava em propriedade trocada (fonte ↔ menu) | CT-20 | configurações gravadas com valores trocados |
| M41 | chave nasce desligada (`false` no default) | CT-21 (linha chave) | valor lido desligado |
| M42 | fonte de fábrica diferente de Normal | CT-21 (linha fonte) | valor lido ≠ Normal |
| M43 | menu de fábrica "Esconder tudo" | CT-21 (linha menu) | valor lido Esconder tudo |

---

## Regra R9 — A organização libera pelo interruptor no cadastro do `/admin`, que nasce desligado e só aparece com a chave da instalação ligada

> `RQ-08`, `P-04`, `P-11` · perfil **padrão** · técnica: **EP** (criar × editar) + **tabela de decisão** (visibilidade: chave × tela) · suíte `tests/Tenancy`

```gherkin
  Regra: a organização libera pelo interruptor do cadastro, desligado de nascença

    Esquema do Cenário: [CT-22] ligar o interruptor no cadastro libera a escolha pessoal dentro da organização
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      Quando a administradora <ação> a organização "<slug>" com o interruptor ligado
      Então a organização "<slug>" fica gravada com a chave ligada
      E a Ana, membro dela, vê densidade "Denso" em /app/<slug>

      Exemplos:
        | ação   | slug    |
        | cria   | umbrella |
        | edita  | acme    |

    Esquema do Cenário: [CT-23] a chave da organização nasce desligada
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      Quando <origem>
      Então a organização fica gravada com a chave desligada
      E a Ana, membro dela, vê densidade "Compacto" nela

      Exemplos:
        | origem                                                                  |
        | a administradora cria a organização sem tocar no interruptor            |
        | uma organização já existente antes da feature é lida depois da migração |

    Esquema do Cenário: [CT-24] o interruptor só aparece com a chave da instalação ligada
      Dado a chave da instalação <instalação>
      Quando a administradora abre a tela de <tela> de organização
      Então o interruptor da organização está <visível>

      Exemplos:
        | instalação | tela    | visível |
        | ligada     | criação | visível |
        | ligada     | edição  | visível |
        | desligada  | criação | oculto  |
        | desligada  | edição  | oculto  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M44 | campo no formulário sem `$fillable`/cast no model (aparece e não grava) | CT-22 | coluna desligada depois de salvar |
| M45 | campo só na edição (`visible` por `$record !== null`, como o slug) | CT-22 (linha cria), CT-24 (linha criação) | a criação não grava / campo oculto na criação |
| M46 | coluna com `default(true)` ou interruptor com `default(true)` | CT-23 | organização gravada com a chave ligada; Ana vê Denso |
| M47 | visibilidade invertida ou ausente | CT-24 (linhas desligada) | interruptor visível com a instalação desligada |

---

## Regra R10 — Os formulários de administração não gravam valor fora do domínio

> Superfície Livewire (`ConfiguracoesDoKit` — `aparencia_por_usuario`, `tamanho_da_fonte`, `modo_do_menu`; `TenantForm` — `aparencia_por_usuario`), `P-12` · perfil **padrão** · técnica: **EP**, uma partição inválida por linha. Direção de recusa × conversão do booleano aberta (Q?2)

```gherkin
  Regra: os formulários de administração não gravam valor fora do domínio

    Esquema do Cenário: [CT-25] valor fora do domínio no estado da tela de Configurações não é gravado como veio
      Dado a chave da instalação ligada, fonte "Grande" e menu "Esconder tudo" gravados
      Quando a administradora salva as Configurações com <campo> = <valor> escrito no estado do formulário
      Então a resposta não é erro de servidor
      E a configuração gravada de <campo> não é <valor> e pertence ao vocabulário dela

      Exemplos:
        | campo            | valor     | # partição inválida |
        | tamanho da fonte | "gigante" | fora do enum        |
        | modo do menu     | "nenhum"  | fora do enum        |
        | tamanho da fonte | ["grande"] | tipo errado        |
        | modo do menu     | 1         | tipo errado         |
        | chave            | "talvez"  | não booleano        |

    Esquema do Cenário: [CT-26] valor não booleano no interruptor da organização não é gravado como veio
      Dado a chave da instalação ligada
      E a Acme com a chave desligada
      Quando a administradora salva a edição da Acme com o interruptor = <valor> no estado do formulário
      Então a resposta não é erro de servidor
      E a chave da Acme gravada é um booleano, não <valor>

      Exemplos:
        | valor    |
        | "talvez" |
        | ["1"]    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | Select sem `in()` e sem coerção antes de gravar | CT-25 (linhas "gigante", "nenhum") | configuração gravada `"gigante"` |
| M49 | coerção por `tryFrom()` sobre não escalar (`TypeError`) | CT-25 (linhas tipo errado) | resposta 500 |
| M50 | propriedade da chave sem tipo `bool` no settings (grava a string) | CT-25 (linha "talvez") | configuração gravada `"talvez"` |
| M51 | coluna sem cast `boolean` no model | CT-26 | chave gravada como `"talvez"` / `["1"]` |

---

## Regra R11 — Só quem administra a instalação liga a chave, define os padrões e libera organizações

> `RQ-07`, `RQ-08`, `P-11` · perfil **padrão** · técnica: **matriz papel × ação**, por fora do componente (rota e policy) · saída do erro declarada

```gherkin
  Regra: só quem administra a instalação mexe nas chaves e nos padrões

    Esquema do Cenário: [CT-27] quem não administra a instalação não chega às Configurações
      Dado a chave da instalação ligada
      Quando <pessoa> pede /admin/configuracoes-da-aplicacao
      Então a resposta é 403
      E a página de Configurações nega acesso a <pessoa> pela própria checagem de acesso
      E a chave da instalação continua ligada
      E <pessoa> abre <painel dela> com status 200

      Exemplos:
        | pessoa | painel dela |
        | o Ivo (infra)      | /infra |
        | a Bia (panel_user) | /app   |

    Cenário: [CT-28] quem administra uma organização não libera a aparência dela
      Dado a Acme com a chave desligada
      E a Carla administradora da Acme (admin_app)
      Quando a Carla pede /admin/organizacoes/{Acme}/edit
      Então a resposta é 403
      E a policy nega à Carla a atualização da Acme
      E a Acme continua com a chave desligada
      E a Carla abre /app/acme com status 200
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M52 | campos de aparência movidos para uma página nova de configurações sem `canAccess()` restrito | CT-27 | resposta 200 para o Ivo |
| M53 | checagem só por `shouldRegisterNavigation()` (some do menu, a rota abre) | CT-27 | resposta 200; `canAccess()` verdadeiro |
| M54 | `TenantPolicy::update` liberada a `admin_app` da própria organização (para "a organização liberar") | CT-28 | policy permite; resposta 200 |
| M55 | 403 que derruba a sessão ou redireciona em laço (sem saída) | CT-27, CT-28 | o painel da pessoa não abre com 200 |

---

## Regra R12 — A seção Aparência aparece no Meu perfil dos três painéis só com a chave da instalação ligada, com o aviso sobre organizações

> `P-06`, `P-13` · perfil **padrão** · técnica: **EP** (painel × chave) e **tabela de decisão** (tenancy × liberação). Recorte "aviso em `/admin` e `/infra` com tenancy" aberto (Q?3)

```gherkin
  Regra: a seção Aparência aparece só com a chave da instalação ligada

    Esquema do Cenário: [CT-29] a seção Aparência acompanha a chave da instalação nos três painéis
      Dado a chave da instalação <chave>
      Quando a Ana abre <meu perfil>
      Então a seção "Aparência" está <presença>
      E a seção de dados pessoais do Meu perfil está presente

      Exemplos:
        | chave     | meu perfil       | presença |
        | ligada    | /admin/meu-perfil | presente |
        | ligada    | /app/meu-perfil   | presente |
        | ligada    | /infra/meu-perfil | presente |
        | desligada | /admin/meu-perfil | ausente  |
        | desligada | /app/meu-perfil   | ausente  |
        | desligada | /infra/meu-perfil | ausente  |

    Esquema do Cenário: [CT-30] com organizações, a Ana grava pelo Meu perfil do /app e lê o aviso sobre a organização que não liberou
      Dado a chave da instalação ligada
      E a Acme, da qual a Ana é membro, com a chave <organização>
      Quando a Ana grava densidade "Denso" na seção Aparência do /app/meu-perfil
      Então o perfil da Ana guarda densidade "Denso"
      E a seção mostra o aviso de que, numa organização que não liberou, vale o padrão dela

      Exemplos:
        | organização |
        | ligada      |
        | desligada   |

    Cenário: [CT-31] sem organizações, a seção não mostra o aviso
      Dado a chave da instalação ligada e o kit sem organizações
      Quando a Ana abre a seção Aparência do /app/meu-perfil
      Então a seção mostra as quatro preferências
      E a seção não mostra o aviso sobre organizações
```

Notas: CT-29 é HTTP (G2): `assertSee` sobre o título da seção e controle positivo na seção de dados pessoais do Breezy (o mesmo HTML), para o `assertDontSee` não provar a ausência de tudo; procure o título num elemento de cabeçalho de seção — "Aparência" sozinho pode casar com outro texto do painel. CT-30 (Tenancy) e CT-31 (Kit) são de componente (G1). O fragmento do aviso vem do conteúdo determinado por P-06 ("vale o padrão dela").

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M56 | componente registrado só num painel (`myProfileComponents` de um provider) | CT-29 (linhas ligadas de /app e /infra) | seção ausente |
| M57 | seção sempre visível (sem condição na chave) | CT-29 (linhas desligada) | seção presente com a chave desligada |
| M58 | gravação recusada quando as organizações da pessoa não liberaram (chave da organização consultada fora da organização) | CT-30 (linha desligada) | o perfil não guarda Denso |
| M59 | aviso sempre visível, mesmo sem organizações | CT-31 | o aviso aparece |
| M60 | aviso esquecido no modo com organizações | CT-30 | o aviso não aparece |

---

## Regra R13 — O efeito no layout: "Esconder tudo" some com o menu inteiro ao recolher; os tamanhos de fonte escalam pelos percentuais

> `RQ-02`, `RQ-04`, `P-08`, `P-09` · perfil **padrão** · costura **browser** (G5). Cenários CT-B01 e CT-B02, mutantes e roteiro em `05-casos-de-teste-browser.md`.

---

## Regra R14 — O documento de candidatas a pacote existe em `wikis/`, declara os dois propósitos e viaja com o kit

> `RQ-12`, `P-14` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: o documento de candidatas a pacote existe e viaja com o kit

    Cenário: [CT-32] o documento está ao lado do roadmap, declara os dois propósitos e viaja nas duas rotas de entrega
      Dado a árvore do kit
      Quando o arquivo wikis/candidatas-a-pacote.md é lido
      Então ele existe e não está sob wikis/specs
      E está na lista de caminhos do kit:update e fora do export-ignore do .gitattributes
      E declara o uso por outros projetos e a contribuição de colaboradores
```

Notas: o propósito se afirma pelos termos do texto original de Q4 ("outros projetos", "contribu"), não por frase inventada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M61 | documento criado em `wikis/specs/...` (não viaja) ou fora de `CAMINHOS_DO_KIT` | CT-32 | `caminhosDoKit()` sem o caminho |
| M62 | documento só lista, sem dizer para que serve | CT-32 | texto sem "outros projetos" ou sem "contribu" |

---

## Regra R15 — As preferências de aparência entram no documento como candidata

> `RQ-13` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: a aparência por usuário é candidata a pacote

    Cenário: [CT-33] o documento tem a entrada das preferências de aparência, apontando código que existe
      Dado o documento wikis/candidatas-a-pacote.md
      Quando suas seções são lidas
      Então uma seção trata das preferências de aparência por usuário
      E essa seção cita ao menos um caminho de app/ que existe na árvore
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M63 | documento sem a entrada da aparência (só a revisão do resto do kit) | CT-33 | nenhuma seção sobre aparência |
| M64 | entrada cita caminho que não existe (planejado, renomeado) | CT-33 | `file_exists()` falso para o caminho citado |

---

## Regra R16 — O documento lista o que o kit implementou e poderia virar pacote, a partir do código

> `RQ-14` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: o documento lista as outras melhorias do kit candidatas a pacote

    Cenário: [CT-34] além da aparência, o documento lista outras features do kit, cada uma apontando seu código
      Dado o documento wikis/candidatas-a-pacote.md
      Quando suas seções de candidata são lidas
      Então há ao menos duas candidatas além das preferências de aparência
      E cada candidata cita ao menos um caminho de app/ ou resources/ que existe na árvore
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M65 | documento só com a aparência (a revisão do código não foi feita) | CT-34 | contagem de candidatas além da aparência < 2 |
| M66 | candidatas tiradas do roadmap/ideias, sem código no kit | CT-34 | candidata sem caminho existente |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-06 (alvo forjado: Bia) | G1 |
| Autorização exercida na ação (não só `can()`) | CT-27, CT-28 (rota 403 + policy/`canAccess()`, valor gravado intacto) | G4 |
| Idempotência (ancorada no agregado) | não se aplica: a gravação substitui o valor de cada preferência no registro da pessoa; não há acúmulo, contador nem efeito colateral a duplicar — o mutante "acumula" não é expressável num valor por chave | — |
| Concorrência | não se aplica: cada pessoa escreve só o próprio registro, sem limite, saldo ou contador; duas abas gravando = a última vence, que é o comportamento de qualquer formulário de perfil | — |
| **Fronteira no ponto de entrada** (gravação) | CT-04, CT-05 (pessoa), CT-25 (instalação), CT-26 (organização) | G1, G3 |
| **Domínio condicionado** (tipo × valor) | CT-24 (interruptor da organização condicionado à chave da instalação) | G3 |
| **Estado × operação de escrita** | matriz de 9 células acima: CT-08 (gravar com a chave desligada), CT-30, CT-12, CT-29 | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-15 (linhas `{}`, `null`, `""`) e CT-02 ("Padrão") | G2, G1 |
| Paginação / ordenação | não se aplica: nenhuma listagem nova | — |
| Timezone / DST | não se aplica: nenhum dado temporal | — |
| Unicode / limite de varchar / texto livre | não se aplica a texto livre (todos os domínios são listas fechadas); o texto hostil que chega ao CSS está em CT-04 e CT-15 (linhas de injeção) | G1, G2 |
| Unicidade + soft delete | não se aplica: nenhum campo único novo | — |
| Entidade removível ou desativável | não se aplica: a escolha não tem ciclo de vida; desligar as chaves é o "desativar" e está em CT-17, CT-18, CT-19 | G2, G3 |
| CRUD combinado | não se aplica: nenhuma entidade nova com CRUD; editar sem alterar nada = CT-19 (linha nome) | G3 |
| Mass assignment | `users`: CT-06 (linhas email, tema, formulário de dados pessoais) · `tenants`: CT-19 (interruptor oculto forjado), CT-26 · `settings`: CT-25 | G1, G3 |
| Upload | CT-07 (`_startUpload` recusado) | G1 |
| Precisão monetária | não se aplica: sem valor monetário; os percentuais de fonte são provados por razão calculada em CT-B02 | — |
| **Superfície Livewire** (método público, prop pública, estado do framework) | `$data`: CT-04, CT-05, CT-06 · `salvar()`: CT-06, CT-08 · `_startUpload`: CT-07 · `ConfiguracoesDoKit` (três campos): CT-25 · `TenantForm`: CT-26 · `users.aparencia` lido no render: CT-15 | G1, G2, G3 |
| **Estado do framework usado sem validar** (índice, `match`, `tryFrom`, CSS) | CT-15 (`match`/`tryFrom`/`constant()`/CSS), CT-16 (padrão da instalação) | G2 |
| **IDOR por entidade** (uma linha por tabela persistida) | `users`: CT-06 · `tenants`: CT-28 · `settings`: CT-27 | G1, G4 |
| **Escopo com discriminante nulo** (organização ausente no endereço) | CT-12 (linhas D5, D6 — /app/meu-perfil sem organização; D7 — /admin); CT-11 (usuário nulo) | G2 |
| **Saída do estado de erro** (4xx tem destino) | CT-27, CT-28 (o painel da pessoa abre com 200); CT-07 (o Meu perfil reabre com 200) | G4, G1 |
| Efeito colateral (cardinalidade 0/1/N) | não se aplica: a feature não notifica, não enfileira e não audita por requisito | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a escolha gravada pela seção governa a página seguinte | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M01, M05 |
| CT-02 | "Padrão" solta uma preferência e preserva as outras | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M02, M03 |
| CT-03 | a seção oferece exatamente o vocabulário + "Padrão" | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M04, M05 |
| CT-04 | valor fora do domínio no estado do componente não vale | R2 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M06, M07, M08, M09 |
| CT-05 | valor de tipo errado não vale e não quebra | R2 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M10 |
| CT-06 | campo extra não grava fora das preferências da própria Ana | R3 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M11, M12, M13, M14 |
| CT-07 | upload para o componente é recusado | R3 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M15 |
| CT-08 | chave desligada depois de abrir: página no padrão | R3 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M16 |
| CT-09 | escolha nos três painéis com a chave ligada; some desligada | R4 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M17, M18, M19 |
| CT-10 | sem escolha: padrão da instalação | R4 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M20 |
| CT-11 | tela sem sessão: padrão da instalação | R4 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M21 |
| CT-12 | tabela das duas chaves | R5 | tabela de decisão | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M22, M23, M24, M25 |
| CT-13 | "Padrão" na cor: organização, depois instalação | R5 | tabela de decisão | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M26 |
| CT-14 | não vaza de uma organização para a seguinte | R5 | tabela de decisão (ordem) | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M27 |
| CT-15 | escolha gravada fora do vocabulário: padrão, sem erro | R6 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M28, M29, M30, M31, M32 |
| CT-16 | padrão da instalação fora do vocabulário: fábrica | R6 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M28, M33 |
| CT-17 | chave da instalação desligada e religada | R7 | 2-switch | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M34, M38 |
| CT-18 | chave da organização desligada e religada | R7 | 2-switch | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M35, M38 |
| CT-19 | editar a organização com a instalação desligada não mexe na chave | R7 | 2-switch | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M36, M37 |
| CT-20 | o gravado nas Configurações governa a página | R8 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M39, M40 |
| CT-21 | valores de fábrica | R8 | valor literal | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M41, M42, M43 |
| CT-22 | interruptor ligado no cadastro libera | R9 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M44, M45 |
| CT-23 | chave da organização nasce desligada | R9 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M46 |
| CT-24 | interruptor só com a chave da instalação ligada | R9 | tabela de decisão | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M45, M47 |
| CT-25 | Configurações não gravam valor fora do domínio | R10 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M48, M49, M50 |
| CT-26 | interruptor da organização não grava não booleano | R10 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M51 |
| CT-27 | quem não administra não chega às Configurações | R11 | matriz papel × ação | G4 | Pest feature HTTP | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M52, M53, M55 |
| CT-28 | admin da organização não libera a aparência | R11 | matriz papel × ação | G4 | Pest feature HTTP | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M54, M55 |
| CT-29 | seção acompanha a chave nos três painéis | R12 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M56, M57 |
| CT-30 | com organizações: grava pelo /app e lê o aviso | R12 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Tenancy/PreferenciasDeAparenciaTenancyTest.php` | M58, M60 |
| CT-31 | sem organizações: sem aviso | R12 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M59 |
| CT-32 | documento existe, propósito, viaja | R14 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M61, M62 |
| CT-33 | aparência listada como candidata | R15 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M63, M64 |
| CT-34 | outras candidatas do kit, com código | R16 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M65, M66 |
| CT-B01, CT-B02 | ver `05-casos-de-teste-browser.md` | R13 | EP | G5 | browser | `tests/Browser/PreferenciasDeAparenciaTest.php` | ver `05` |

Estouro de teto: nenhum no `04` (máximo 3 por regra). R1/R2/R3 dividem a superfície Livewire em três regras para respeitar o teto `padrão`.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| tela de bloqueio (`/screen/lock`) com a escolha da pessoa | o `00` não a menciona (P-10 lista só telas sem pessoa logada); nenhum mutante previsto depende dela |
| BVA nos percentuais de fonte | não há faixa: quatro valores fixos; o número literal está em CT-B02 |
| gravação idêntica repetida (duplo clique) | não mata mutante nenhum (substituição de valor; ver taxonomia) |
| cor "Grande"/"Esconder tudo" em CT-01 | não discriminam contra a instalação de referência; cobertos por CT-20 e pelos CT-B |
| comparação do HTML inteiro com fonte Normal × sem escolha | HTML tem tokens variáveis por request; o helper identifica o tamanho pela declaração e CT-B02 mede o pixel |
| `canAccess()` da tela de Configurações para `admin` (positivo) | já coberto por `tests/Kit/ConfiguracoesDoKitTelaTest.php` e implícito em CT-20 |
