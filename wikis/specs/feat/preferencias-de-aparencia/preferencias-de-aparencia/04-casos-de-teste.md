# Casos de Teste — Roadmap item 1: preferências de aparência por usuário

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas e `## Superfície de UI`) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **rodadas 1 e 2 feitas** (`fw-adversario-ct`, despacho da sessão) — 18 + 6 achados, todos com destino em `## Revisão Adversarial`. Teto de 2 rodadas atingido: não há rodada 3.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — aparência efetiva (chaves × escolha × contexto) | 3 | 2 | 6 | padrão |
| A2 — gravação pela pessoa (seção Aparência, superfície Livewire) | 2 | 3 | 6 | padrão (Impacto 3: grava no registro do usuário; valor do cliente vira CSS) |
| A3 — configurações da instalação (padrões e chave) | 2 | 2 | 4 | padrão |
| A4 — chave da organização (cadastro em `/admin`) | 2 | 2 | 4 | padrão |
| A5 — efeito no layout (navegador) | 2 | 2 | 4 | padrão |
| A6 — documento de candidatas a pacote | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP (partições válidas e inválidas isoladas), tabela de decisão (escalada na A1, ver Mapa), tabela estado × operação (chaves), 2-switch (desligar e religar), matriz papel × ação, sequência de requests (ler → mudar → ler), BVA não se aplica (nenhuma faixa ordenável: os quatro domínios são listas fechadas)
- Cenários: 39 · Regras: 16 · Mutantes previstos: 85 · Sem matador: 0
<!-- derivado do arquivo por grep -c (comandos em references/template-04.md, "Contagem do cabeçalho"); CT-B e seus mutantes estão no 05 -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | coluna de aparência no usuário; chave booleana na organização; três propriedades novas no settings da instalação (chave, fonte, menu); componente Livewire da seção Aparência no Meu perfil; campo no cadastro da organização; documento `wikis/candidatas-a-pacote.md` | CT-01…CT-40 |
| F | resolver a aparência efetiva por contexto (instalação, organização, pessoa); gravar a escolha; recusar a gravação com a chave desligada; ligar/desligar chaves; listar candidatas a pacote | CT-09…CT-14, CT-01…CT-08, CT-17…CT-24, CT-32…CT-34 |
| D | quatro vocabulários fechados (densidade 3, fonte 4, cor 16 da paleta do kit, menu 2) + "Padrão"; valor gravado fora do vocabulário, vazio, de tipo errado, JSON escalar, JSON misto (válido + inválido); **dado de outra pessoa** (Bia) e **de outra organização** (Globex) | CT-04, CT-05, CT-06, CT-14, CT-15, CT-16, CT-38 |
| I | três telas de Meu perfil (`/admin`, `/app`, `/infra`); tela de Configurações da aplicação; cadastro e edição de organização (só em `/admin`); toda página autenticada dos três painéis; telas sem sessão (login, cadastro, recuperação de senha); `_startUpload` e chamadas diretas por `$wire` | CT-07, CT-09, CT-11, CT-20, CT-22, CT-29 |
| P | sqlite em memória nos testes (cast de JSON e booleano); Chromium headless para o recolher do menu (Alpine do Filament) e o tamanho calculado da fonte; tenancy ligada × desligada (duas suítes) | CT-B01, CT-B02 (05); suítes `tests/Kit` e `tests/Tenancy` |
| O | quem escolhe (qualquer pessoa autenticada, inclusive quem administra), quem libera (quem administra a instalação), quem não pode (`infra`, `panel_user`, `admin_app`); visitante sem sessão; uso indevido: payload forjado por `$wire` | CT-06, CT-11, CT-12 (D9, D10), CT-27, CT-28 |
| T | ordem de visita entre organizações e entre pessoas no mesmo processo (cache de cor/aparência entre requests); ler → mudar → ler; chave desligada **depois** de a tela abrir; desligar e religar (2-switch) | CT-08, CT-10, CT-14, CT-17, CT-18, CT-37 |

## Matriz estado × operação (chaves)

Estados: E1 instalação ligada + organização liberada · E2 instalação ligada + organização não liberada · E3 instalação desligada.
Operações: O1 ver a aparência efetiva dentro da organização · O2 abrir a seção Aparência do Meu perfil · O3 gravar pela seção.
**3 estados × 3 operações = 9 células**, 6 válidas (✅) e 3 que devolvem o padrão ou recusam (❌ = a escolha pessoal não aparece; em O3, nada é gravado e a pessoa vê o aviso).

| | O1 ver | O2 seção | O3 gravar |
|---|---|---|---|
| E1 | ✅ CT-12 (linha D1) | ✅ CT-30 (linha `/app` liberada) | ✅ CT-30 (linha `/app` liberada) |
| E2 | ❌ padrão — CT-12 (linhas D2, D8, D9) | ✅ CT-30 (linhas não liberada) — o Meu perfil não está em organização (P-06, P-13) | ✅ CT-30 (linhas não liberada) |
| E3 | ❌ padrão — CT-12 (linhas D3, D4, D10) | ❌ seção ausente — CT-29 (linhas desligada) | ❌ recusa — CT-08 (perfil intacto, vazio ou com escolha; aviso; sem "Aparência salva") e CT-39 (religar não traz o enviado) |

Legenda conferida célula a célula: O1 não tem efeito colateral além da página; O3 tem um efeito (a escrita no registro da pessoa), afirmado presente em CT-30 (✅) e ausente em CT-08 (❌, com a Ana existente e a seção aberta — o alvo da escrita existe no `Dado`).

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — A pessoa grava qualquer valor do vocabulário de cada preferência, ou "Padrão", uma preferência independente da outra | A2 (padrão) | RQ-01, RQ-02, RQ-03, RQ-04, RQ-10, P-07, P-08, P-09 | EP (partição válida por caso do enum) | CT-01, CT-02, CT-03 |
| R2 — Valor fora do vocabulário ou de tipo errado, enviado pela seção, não é gravado | A2 (padrão) | P-12, P-07, `## Superfície Livewire` (`$data`) | EP (inválidas isoladas) | CT-04, CT-05 |
| R3 — A gravação atinge só a própria pessoa, só as quatro preferências, e é recusada com a chave desligada | A2 (padrão) | RQ-01..RQ-04 ("guardada no perfil dela"), P-01, P-15, `## Superfície Livewire` (`salvar()`, `_startUpload`) | matriz papel × ação; EP; estado × operação | CT-06, CT-35, CT-07, CT-08 |
| R4 — Fora de organização, a escolha vale com a chave da instalação ligada; desligada ou sem escolha, vale o padrão da instalação | A1 (padrão) | RQ-05, RQ-07, RQ-10, P-01, P-02, P-10, P-13 | tabela de decisão; sequência de requests | CT-09, CT-10, CT-11, CT-37 |
| R5 — Dentro de organização, a escolha só vale com as duas chaves; senão, a cor da organização e, depois, o padrão da instalação — para qualquer papel | A1 (padrão) | RQ-08, RQ-09, P-02, P-04, P-10, P-13, P-01 | tabela de decisão | CT-12, CT-13, CT-14 |
| R6 — Valor gravado fora do vocabulário cai no padrão na leitura, sem erro e sem derrubar as preferências válidas | A1 (padrão) | P-12, `## Superfície Livewire` (`users.aparencia` lido no render) | EP (inválidas isoladas) | CT-15, CT-16, CT-38 |
| R7 — Desligar uma chave não apaga a escolha; religada, ela volta a valer | A1 (padrão) | P-05, P-11 | 2-switch | CT-17, CT-18, CT-19, CT-39 |
| R8 — A instalação define o padrão das quatro preferências e a chave, e o gravado governa a página | A3 (padrão) | RQ-06, RQ-07, P-03, P-08, P-09 | EP; valor de fábrica literal; sequência (outro ator) | CT-20, CT-21, CT-40 |
| R9 — A organização libera pelo interruptor no cadastro do `/admin`, que nasce desligado e só aparece com a chave da instalação ligada | A4 (padrão) | RQ-08, P-04, P-11 | EP; tabela de decisão (visibilidade) | CT-22, CT-23, CT-24 |
| R10 — Os formulários de administração recusam valor fora do domínio e mantêm o anterior | A3, A4 (padrão) | `## Superfície Livewire` (`ConfiguracoesDoKit`, `TenantForm`), P-12 | EP (inválidas isoladas) | CT-25, CT-26 |
| R11 — Só quem administra a instalação liga a chave, define os padrões e libera organizações | A3, A4 (padrão) | RQ-07, RQ-08, P-11 | matriz papel × ação (por fora do componente) | CT-27, CT-28 |
| R12 — A seção Aparência aparece no Meu perfil dos três painéis só com a chave da instalação ligada, com o aviso sobre organizações quando elas existem | A2 (padrão) | P-06, P-13, P-16 | EP; tabela de decisão | CT-29, CT-30, CT-31 |
| R13 — O efeito no layout: "Esconder tudo" some com o menu inteiro ao recolher; os tamanhos de fonte escalam pelos percentuais | A5 (padrão) | RQ-02, RQ-04, P-08, P-09 | EP; valor literal do requisito | CT-B01, CT-B02 (no `05`) |
| R14 — O documento de candidatas a pacote existe em `wikis/`, declara os dois propósitos e viaja com o kit | A6 (mínimo) | RQ-12, P-14 | EP | CT-32 |
| R15 — As preferências de aparência entram no documento como candidata | A6 (mínimo) | RQ-13 | EP | CT-33 |
| R16 — O documento lista o que o kit implementou e poderia virar pacote, a partir do código | A6 (mínimo) | RQ-14 | EP | CT-34 |

- Técnica escalada: A1 é `padrão`, mas R4/R5 usam **tabela de decisão completa** (não colapsada) — a regra tem cinco condições (contexto, chave da instalação, chave da organização, escolha, cor da organização) e o colapso esconderia justamente o `E`→`OU` entre as duas chaves.
- RQ-11 — sem cenário, justificado: é restrição de entrega (sem pacote externo agora); nenhuma implementação errada plausível muda comportamento observável da aplicação. Conferência pelo quality gate no diff de `composer.json`/`composer.lock` (nenhuma dependência nova).
- Nenhuma `RQ` aberta no `00`; nenhum recorte bloqueado. As perguntas desta derivação foram fechadas pela sessão: gravar com a chave desligada → Q5/P-15; booleano forjado → fato do vendor (Toggle); aviso nos três painéis → Q6/P-16; rótulos → decisão de desenho; cor pela lista do kit → `02` corrigido.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Seção Aparência (gravação pela pessoa) | R1, R2, R3, R12 (CT-30, CT-31) | componente Livewire/Filament | existente — padrão de `tests/Kit/DefinirSenhaPorEmailTest.php` (componente Livewire do Meu perfil); arquivos novos `tests/Kit/PreferenciasDeAparenciaTest.php` e `tests/Tenancy/PreferenciasDeAparenciaTenancyTest.php` | a regra afirma o que o cliente consegue gravar pelo componente (`$data`, `salvar()`, `_startUpload`) — só o `Livewire::test()` alcança essa superfície | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G2 — Aparência efetiva na página servida | R4, R5, R6, R7 (CT-17, CT-18, CT-39), R8 (CT-40), R12 (CT-29) | Pest feature HTTP | existente — `tests/Kit/DensidadeDoLayoutTest.php` (GET + HTML servido) e `tests/Tenancy/PaletaDaOrganizacaoTest.php` (GET + `FilamentColor`); arquivos novos `tests/Kit/AparenciaEfetivaTest.php` e `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | o observável é a página servida (HTML e paleta registrada) depois de um request real que boota o painel e o middleware de tenant | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G3 — Telas de administração (settings e organização) | R7 (CT-19), R8 (CT-20), R9, R10 | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` (`chaveDoCampo()`/`carregarAba()`) e `tests/Tenancy/PaletaDaOrganizacaoTest.php` (`CreateTenant`/`EditTenant`); arquivos novos `tests/Kit/ConfiguracoesDeAparenciaTest.php` e `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | gate de tela de escrita: `fillForm` → `save`/`create` → valor gravado; visibilidade do campo; erro de validação no campo | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G4 — Autorização por fora do componente | R11 | Pest feature HTTP | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` e `tests/Tenancy/PermissoesDeTenantResourceTest.php`; casos novos nos arquivos de G3 | gate de camada da regra: a barreira tem de valer pela rota e pela policy, não só pela tela | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G5 — Layout no navegador | R13 | browser | existente — `tests/Browser/CabecalhoDoPainelTest.php` (geometria por `getComputedStyle`/`getBoundingClientRect`); arquivo novo `tests/Browser/PreferenciasDeAparenciaTest.php` | o recolher do menu é Alpine executado e o tamanho da fonte é o valor calculado pelo Chromium depois da cascata — o HTML servido é igual com e sem o defeito | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G6 — Documento de candidatas | R14, R15, R16 | Pest feature HTTP | existente — `tests/Kit/SiteDeDocumentacaoTest.php` (lê `wikis/roadmap.md`) e `tests/Kit/KitUpdateTest.php` (`caminhosDoKit()`); arquivo novo `tests/Kit/CandidatasAPacoteTest.php` | lê arquivo da árvore com a aplicação de pé (`base_path()`, `caminhosDoKit()`, `secoesDoMarkdown()`); sem HTTP | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |
| G7 — Valores de fábrica | R8 (CT-21) | Pest feature HTTP | existente — `tests/Kit/DensidadeDoLayoutTest.php` CT-02 (`kitConfigCom()`) e `configuracaoGravada()` de `tests/Pest.php` | lê o arquivo de configuração e a linha semeada no settings com a aplicação de pé; nenhum componente | sessão, 2026-11-18 (convenção das irmãs citadas; autorização "prossiga ate finalizar") |

**Divergência declarada (Project Rule venceu a skill):** a skill situa `Pest feature HTTP` em `tests/Feature`; aqui os casos vão para `tests/Kit` e `tests/Tenancy` — as suítes do kit em `phpunit.xml` — e todo caso que precise de `admin_app` vai para `tests/Tenancy` (`.ai/rules/testes.md`, "Nem todo papel do kit existe em toda suíte").

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `PreferenciasDeAparencia`, `salvar()`, `$data`, `AparenciaDoUsuario`, `TamanhoDaFonte`, `ModoDoMenu`, valores dos casos (`muito-grande`, `oculto`…), coluna `users.aparencia`, chaves do JSON, propriedades `aparencia_por_usuario`/`tamanho_da_fonte`/`modo_do_menu`, coluna `tenants.aparencia_por_usuario` | escolha de implementação | detalhe do cenário (o executor usa os nomes; o `Então` afirma a página e o valor gravado) |
| "`salvar()` recusa com a chave desligada" | **não** recusado: agora é P-15 (Q5 do `00`) — recusa, nada gravado, aviso | CT-08 |
| "valor fora do vocabulário cai em `null`" na gravação (coluna Fronteira do `02`) | o `00` (P-12) fala da **leitura**; na gravação ele só determina que o valor inválido não vale | CT-04/CT-05 afirmam "não gravado como veio" + página no padrão, que vale para recusar ou coagir |
| "`mutateFormDataBeforeSave()` coage fonte e menu" | mecanismo | CT-25 afirma o invariante (gravado ∈ vocabulário, ≠ valor enviado) |
| recusa do booleano forjado | **não** é do plano: fato do vendor — `vendor/filament/forms/src/Components/Toggle.php:Toggle::setUp:37` (`$this->rule('boolean')`) | CT-25 (linha chave), CT-26: erro de validação no campo, valor anterior mantido |
| "auditado por `AuditsFillables`" | o `00` não pede trilha de auditoria (Proibição 12) | sem cenário |
| forma da declaração de CSS da fonte (render hook, `--text-*`) | mecanismo | CT-B02 mede o tamanho calculado; o HTTP só identifica **qual** tamanho foi emitido |
| rótulos "Aparência" (seção do perfil), "Salvar aparência" (botão), "Liberar a aparência pessoal" (organização), "Aparência dos painéis" (Configurações) | **não** recusados: decisão de desenho da sessão em 2026-11-18 (antes pergunta de rótulos) | CT-24, CT-29 usam os rótulos |
| cor pessoal só pela lista fechada do kit antes do resolver | **não** recusado: o `02` foi corrigido (antes pergunta de desenho sobre o resolver) e P-07 já o determinava | CT-04, CT-15 (linhas `Gray`, `WCAG_AA_TEXT`) |
| fonte de fábrica = Normal (CT-21) | **não** recusado: vem do `00` — P-08 chama Normal de "o padrão do Filament, nada muda" e o roadmap diz que a fonte "não existe" hoje | cenário com o valor literal |
| classes do `<body>` para o modo do menu | **não** vem do plano: é o observável do vendor (`vendor/filament/filament/resources/views/components/layout/index.blade.php:$isSidebarCollapsibleOnDesktop:27`, `:$isSidebarFullyCollapsibleOnDesktop:28`) | observável do menu na camada HTTP |

**Perguntas geradas pela derivação**: nenhuma aberta. As cinco da rodada anterior foram fechadas pela sessão (ver Mapa de Regras).

## Setup Global

### Personas
- **Ana** — Kit: `usuarioDoKit('master_global', 'ana@example.com')` (entra nos três painéis pelo `Gate::before`). Tenancy: `duasOrganizacoes()['usuario']`, rebatizada `ana@example.com` — membro da Acme e da Globex (`panel_user`) e `admin` global; para CT-30 também `papelNaOrganizacao($ana, 'infra')` e membro da Initech
- **Bia** — `usuarioDoKit('panel_user', 'bia@example.com')` (Kit) / `usuarioComPapel('panel_user', $acme, 'bia@example.com')` (Tenancy): a outra pessoa, com escolha própria gravada, para provar fronteira de identidade
- **Administradora** — Kit: `usuarioDoKit('admin', 'adm@example.com')`; Tenancy: `usuarioComPapel('master_global', email: 'adm@example.com')` com `Filament::setCurrentPanel('admin')` (padrão `administradoraNoAdmin()` de `PaletaDaOrganizacaoTest`). Em CT-12 (D10), a **administradora da instalação** com papel `admin` global e escolha própria
- **Ivo** — `usuarioDoKit('infra', 'ivo@example.com')`; **Carla** — `usuarioComPapel('admin_app', $acme, 'carla@example.com')` (só `tests/Tenancy`)
- Segunda pessoa no mesmo `it()`: `test()->flushSession()` antes do `actingAs` (`.ai/rules/testes.md`)

### Fixtures
- **Instalação de referência** (todos os grupos salvo dito): chave ligada, densidade **Compacto**, fonte **Grande**, menu **Esconder tudo**, cor da paleta **Teal** e, no Kit, cor livre `#0ea5e9` (que vence a Teal pela regra existente). Os quatro valores diferem da fábrica (Confortável, Normal, Manter os ícones, âmbar) — é o que distingue "padrão da instalação" de "padrão de fábrica"
- **Escolha de referência**: densidade **Denso**, fonte **Muito grande**, cor **Red**, menu **Manter os ícones**. Os quatro diferem da instalação de referência
- Organizações (Tenancy): `duasOrganizacoes()` — Acme com cor `#7c3aed`, Globex com `#059669`; **Initech** sem cor (`tenant('Initech', 'initech')`), Ana membro. Chave da organização por `$org->update(['aparencia_por_usuario' => …])`
- Novo helper em `tests/Pest.php` (usado por Kit, Tenancy e Browser): `aparenciaDaInstalacao(?bool $porUsuario, ?string $densidade, ?string $fonte, ?string $menu, ?string $cor, ?string $corHex)` — `gravarConfiguracao()` de cada propriedade + `alinharConfiguracoesDoKit()` (o boot do teste roda antes das migrations; ver `DensidadeDoLayoutTest`)
- Novo helper em `tests/Pest.php`: `aparenciaDe(User $u, mixed $bruto)` — grava a coluna **crua** por `DB::table('users')->update(...)`, sem passar pelo cast nem pelo componente (fixture de leitura; não há ciclo de vida nesta entidade)
- Novo helper em `tests/Pest.php`: `aparenciaEfetiva(string $url): array{densidade, fonte, cor, menu, html}` — `fronteiraDeRequest()`, GET, `assertOk()`, e lê os quatro observáveis da tabela abaixo. **Nunca** chama o código da feature para decidir o que espera (a tabela de cada string é literal no helper). Em CT-10 e CT-37 a `fronteiraDeRequest()` **não** é chamada entre os dois requests: o cenário mede justamente o estado que sobra no processo

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

  Regra: a pessoa grava qualquer valor do vocabulário de cada preferência

    Esquema do Cenário: [CT-01] a escolha gravada pela seção Aparência governa a página seguinte, e só ela fica gravada
      Dado a instalação de referência com a chave da instalação ligada
      E a Ana sem nenhuma escolha gravada
      Quando a Ana grava <preferência> = "<valor>" na seção Aparência do Meu perfil do /admin, sem tocar nas outras
      Então o perfil dela guarda <preferência> = "<valor>" e não guarda nenhuma das outras três preferências
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

    Cenário: [CT-02] o que ficou em "Padrão" acompanha a instalação quando ela muda depois
      Dado a instalação de referência, sem cor livre, com a chave da instalação ligada
      E a Ana com densidade "Denso" e fonte "Muito grande" gravadas
      E a Ana gravou densidade "Padrão" na seção Aparência, sem tocar nas outras
      Quando a instalação passa a ter densidade "Confortável", cor "Amber" e menu "Manter os ícones"
      Então a página /admin servida à Ana mostra densidade "Confortável", cor "Amber" e menu "Manter os ícones"
      E mostra fonte "Muito grande"

    Cenário: [CT-03] a seção oferece exatamente o vocabulário de cada preferência, mais "Padrão"
      Dado a chave da instalação ligada
      Quando a Ana abre a seção Aparência do Meu perfil do /admin
      Então a densidade oferece "Padrão", "Confortável", "Compacto" e "Denso"
      E a fonte oferece "Padrão", "Pequena", "Normal", "Grande" e "Muito grande"
      E a cor oferece "Padrão" e exatamente as 16 cores da paleta do kit, sem campo de hexadecimal
      E o menu oferece "Padrão", "Manter os ícones" e "Esconder tudo"
```

Notas: a "paleta do kit" é `CustomizadorDaInstalacao::CORES` (a mesma lista do settings, P-07). Grande e Esconder tudo ficam fora de CT-01 porque são os valores da instalação de referência — a linha não discriminaria; Grande é exercitado em CT-B02 e Esconder tudo em CT-B01 e CT-20. CT-02 mata o congelamento (ADV-02/12): cor e menu nunca foram escolhidos e, se a seção os gravasse com o padrão vigente (Teal, Esconder tudo), a troca da instalação não chegaria à página.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M01 | escolha igual ao valor de fábrica tratada como "sem escolha" (`if ($valor === padraoDeFabrica) null`) | CT-01 (linhas Confortável, Normal, Manter os ícones) | página mostra Compacto / Grande / Esconder tudo (a instalação); esperado o valor escolhido |
| M02 | "Padrão" gravado como o valor corrente da instalação (congela) em vez de ausente | CT-02 | depois da troca da instalação, a página mostra Compacto; esperado Confortável |
| M03 | salvar uma preferência sobrescreve as outras com nulo | CT-02 | fonte vira Grande (instalação); esperado Muito grande |
| M04 | opções de cor por reflexão sobre `Color` (traz `Gray`, `Zinc`, `WCAG_AA_TEXT`…) | CT-03 | a lista de cor tem mais de 16 itens / contém `Gray` |
| M05 | caso do enum faltando nas opções (ex.: Muito grande esquecido, ou Pequena) | CT-01 (linhas Pequena, Muito grande), CT-03 | a lista de fonte sem "Muito grande"; a gravação da linha é recusada pelo `in()` |
| M69 | campos da seção nascem preenchidos com o padrão vigente e são gravados junto (ADV-02/12) | CT-01, CT-02 | CT-01: o perfil guarda as outras três; CT-02: a página continua Teal/Esconder tudo; esperado Amber/Manter os ícones |

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
| M09 | cor da seção validada por "contém/começa com um nome da paleta" (`str_starts_with`/`str_contains` sobre `Red`) | CT-04 (linha de injeção) | o perfil guarda `"Red;}</style><script>…"`; esperado ausente (a asserção sobre `alert(1)` no HTML é de apoio: o CSS vindo do texto gravado é M30, morto em CT-15) |
| M10 | `tryFrom()`/`in_array()` sobre valor não escalar sem guarda (`TypeError`) | CT-05 (linhas lista, objeto) | o componente responde erro 500 |

---

## Regra R3 — A gravação atinge só a própria pessoa, só as quatro preferências, e é recusada com a chave desligada

> `RQ-01..RQ-04` ("guardada no perfil dela"), `P-01`, `P-15`, Superfície Livewire (`salvar()`, `_startUpload`) · perfil **padrão** · técnica: **matriz papel × ação** (dona × outra pessoa), **EP** e **estado × operação**

```gherkin
  Regra: a gravação atinge só a própria pessoa e só as quatro preferências

    Esquema do Cenário: [CT-06] campo extra no envio não grava nada fora das preferências da própria Ana
      Dado a Bia com densidade "Compacto" gravada
      E a Ana com o e-mail "ana@example.com"
      Quando a Ana envia a seção Aparência com densidade "Denso" e o campo extra <extra>
      Então o perfil da Ana guarda densidade "Denso" e nenhuma chave além das quatro preferências
      E a Bia continua com densidade "Compacto"
      E o e-mail da Ana continua "ana@example.com"

      Exemplos:
        | extra                       | # superfície               |
        | id = id da Bia              | alvo forjado               |
        | user_id = id da Bia         | alvo forjado               |
        | email = "outra@example.com" | mass assignment no usuário |
        | tema = "x"                  | chave estranha no JSON     |

    Cenário: [CT-35] o formulário de dados pessoais do Meu perfil não grava a aparência
      Dado a instalação de referência com a chave da instalação ligada
      E a Ana sem nenhuma escolha gravada
      Quando a Ana salva os dados pessoais do Meu perfil com aparencia = {"densidade": "Denso"} escrito no estado do formulário
      Então o perfil da Ana continua sem escolha gravada
      E a página /admin servida a ela mostra densidade "Compacto"

    Cenário: [CT-07] upload para o componente da seção é recusado
      Dado a Ana com a seção Aparência aberta
      Quando o cliente da Ana pede o início de um upload para "data.cor"
      Então a resposta é 403
      E o perfil da Ana continua sem escolha gravada
      E o Meu perfil do /admin continua abrindo para a Ana com status 200

    Esquema do Cenário: [CT-08] com a chave desligada depois de a seção abrir, a gravação é recusada com aviso e nada muda no perfil
      Dado a instalação de referência com a chave ligada
      E a Ana com <estado prévio> e com a seção Aparência aberta
      E a chave da instalação desligada em seguida
      Quando a Ana envia a gravação com densidade "<enviado>"
      Então o perfil da Ana continua com <estado prévio>
      E a Ana vê o aviso de que a personalização está desligada
      E a notificação de sucesso "Aparência salva" não aparece

      Exemplos:
        | estado prévio                     | enviado     | # partição do estado de partida |
        | nenhuma escolha gravada           | Denso       | vazio                           |
        | a escolha de referência gravada   | Confortável | não vazio (ADV-19; P-05)        |
```

Notas: CT-35 existe porque a coluna da aparência entra no `$fillable` do usuário e outra tela de escrita do mesmo usuário passa a alcançá-la. CT-08 segue P-15 (Q5 do `00`): o aviso se afirma pelo **conteúdo** (texto do aviso com `avisosDoFilament()`, que fala da personalização desligada), e a ausência de "Aparência salva" pelo mesmo helper — `assertNotified()` sozinho passaria com a notificação de sucesso (ADV-21). A religação da chave depois da recusa é o CT-39 (R7), separado para o `Então` não carregar um segundo evento (ADV-24). **Estouro de teto justificado** (R3 com 4 cenários no perfil `padrão`): o gate vence o teto — M14 só morre em CT-35, e M16/M70/M82/M84 só em CT-08.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | `salvar()` resolve o usuário por id vindo do estado (`User::find($this->data['id'] ?? auth()->id())`) | CT-06 (linhas id, user_id) | a Bia passa a ter Denso; esperado Compacto |
| M12 | `salvar()` faz `$user->update($this->data)` | CT-06 (linha email) | e-mail da Ana vira `outra@example.com` |
| M13 | o JSON grava `$this->data` inteiro | CT-06 (linha tema) | o perfil guarda a chave `tema` |
| M14 | `aparencia` entra no `$fillable` e o formulário de dados pessoais repassa o estado cru | CT-35 | o perfil da Ana guarda Denso sem ter passado pela seção; a página mostra Denso |
| M15 | componente sem a restrição de upload a campos do schema | CT-07 | a resposta é 200 (upload aceito); esperado 403 |
| M16 | a chave da instalação só é conferida no `mount()`/na visibilidade da seção, não em `salvar()` — grava e ignora (ADV-01/11) | CT-08 (as duas linhas), CT-39 | o perfil guarda Denso / Confortável; esperado o estado prévio |
| M70 | recusa silenciosa (nada gravado, nenhum aviso) | CT-08 | nenhum aviso de personalização desligada |
| M82 | com a chave desligada, `salvar()` limpa a escolha (grava "Padrão" em tudo) em vez de não gravar (ADV-19) | CT-08 (linha não vazio) | o perfil fica sem escolha; esperado a escolha de referência |
| M84 | a recusa dispara o aviso e, em seguida, a notificação de sucesso de sempre (ADV-21) | CT-08 | "Aparência salva" entre as notificações |

---

## Regra R4 — Fora de organização, a escolha vale com a chave da instalação ligada; desligada ou sem escolha, vale o padrão da instalação

> `RQ-05`, `RQ-07`, `RQ-10`, `P-01`, `P-02`, `P-10`, `P-13` · perfil **padrão** · técnica: **tabela de decisão** (contexto × chave × escolha) e **sequência de requests** · suíte `tests/Kit` (sem organizações)

```gherkin
  Regra: fora de organização, a escolha vale só com a chave da instalação ligada

    Esquema do Cenário: [CT-09] a escolha gravada no Meu perfil do /app vale nos três painéis com a chave ligada, e some com ela desligada
      Dado a instalação de referência com a chave <chave>
      E a Ana gravou a escolha de referência pela seção Aparência do Meu perfil do /app, com a chave ligada
      Quando a Ana abre <página>
      Então a página mostra densidade "<densidade>", fonte "<fonte>", cor "<cor>" e menu "<menu>"

      Exemplos:
        | chave     | página | densidade | fonte        | cor     | menu             | # regra                         |
        | ligada    | /admin | Denso     | Muito grande | Red     | Manter os ícones | pessoal vence a cor livre (P-02); gravada no /app, lida no /admin (P-01) |
        | ligada    | /app   | Denso     | Muito grande | Red     | Manter os ícones | mesma escolha (P-01)            |
        | ligada    | /infra | Denso     | Muito grande | Red     | Manter os ícones | mesma escolha (P-01)            |
        | desligada | /admin | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |
        | desligada | /app   | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |
        | desligada | /infra | Compacto  | Grande       | #0ea5e9 | Esconder tudo    | RQ-07                           |

    Cenário: [CT-10] quem não escolheu vê o padrão da instalação, mesmo logo depois de outra pessoa com escolha
      Dado a instalação de referência com a chave ligada
      E a Bia, com a escolha de referência gravada, acabou de abrir /admin no mesmo processo
      E a Ana sem nenhuma escolha gravada
      Quando a Ana abre /admin
      Então a página mostra densidade "Compacto", fonte "Grande", cor "#0ea5e9" e menu "Esconder tudo"

    Esquema do Cenário: [CT-11] tela sem pessoa logada mostra o padrão da instalação
      Dado a instalação de referência com a chave ligada e o cadastro aberto ligado
      E a Ana com a escolha de referência gravada
      Quando um visitante sem sessão abre <tela>
      Então a resposta é 200
      E a página mostra densidade "Compacto", fonte "Grande" e cor "#0ea5e9"

      Exemplos:
        | tela                                  | # tela sem sessão (P-10) |
        | /admin/login                          | login                    |
        | /app/login                            | login                    |
        | o cadastro do /app                    | cadastro                 |
        | o pedido de recuperação de senha do /admin | recuperação de senha |

    Cenário: [CT-37] a página reflete a escolha nova logo depois de a pessoa gravá-la
      Dado a instalação de referência com a chave ligada
      E a Ana, com a escolha de referência gravada, acabou de abrir /admin e viu densidade "Denso" e cor "Red"
      Quando a Ana grava densidade "Confortável" e cor "Amber" na seção Aparência
      Então a página /admin servida a ela em seguida mostra densidade "Confortável" e cor "Amber"
```

Notas: CT-09 grava pela seção do `/app` (transição real) e lê nos três painéis — fecha ADV-09. CT-10 e CT-37 rodam os requests em sequência **sem** `fronteiraDeRequest()` entre eles: o defeito procurado é o estado que sobra no processo (ADV-05, ADV-06). CT-11: cadastro com `kit.registro.habilitado` ligado; as rotas exatas de cadastro e recuperação vêm do `route:list` (ADV-08). **Estouro de teto justificado** (R4 com 4 cenários): M74 só morre em CT-37.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | cor pessoal passada como "nome" a `CorPrimaria::resolver(hexDaInstalacao, nomePessoal)` — o hexadecimal da instalação vence | CT-09 (linhas ligada) | cor `#0ea5e9`; esperado Red |
| M18 | chave da instalação ignorada (lê sempre a escolha) | CT-09 (linhas desligada) | densidade Denso; esperado Compacto |
| M19 | aplicação só no painel `/app` (render hook registrado em um provider) | CT-09 (linhas /admin, /infra) | densidade Compacto no /admin ligado; esperado Denso |
| M20 | sem escolha, cai no valor de fábrica do enum, não no da instalação | CT-10 | densidade Confortável / fonte Normal / menu Manter os ícones; esperado o da instalação |
| M21 | leitura de `auth()->user()->aparencia` sem guarda de usuário nulo | CT-11 | resposta 500; esperado 200 |
| M71 | cor pessoal registrada em `FilamentColor` e nunca desfeita (o registro de um request vale para o seguinte) | CT-10 | cor Red para a Ana; esperado `#0ea5e9` |
| M72 | layout das telas de cadastro/recuperação (Auth Designer) sem o render hook da instalação | CT-11 (linhas cadastro, recuperação) | densidade Confortável / sem declaração; esperado Compacto |
| M73 | escolha gravada com o painel como chave (preferência por painel) | CT-09 (linhas ligada /admin, /infra) | densidade Compacto no /admin; esperado Denso |
| M74 | aparência efetiva guardada em cache por usuário sem invalidar ao salvar (ADV-06) | CT-37 | a página seguinte mostra Denso/Red; esperado Confortável/Amber |

---

## Regra R5 — Dentro de organização, a escolha só vale com as duas chaves; senão, a cor da organização e, depois, o padrão da instalação — para qualquer papel

> `RQ-08`, `RQ-09`, `P-01`, `P-02`, `P-04`, `P-10`, `P-13` · perfil **padrão** · técnica: **tabela de decisão completa** (escalada), com o papel da pessoa como dimensão · suíte `tests/Tenancy`. Instalação de referência **sem** cor livre (cor Teal)

```gherkin
  Regra: dentro de organização, a escolha só vale com a chave da instalação e a da organização ligadas

    Esquema do Cenário: [CT-12] a tabela das duas chaves decide o que cada pessoa vê
      Dado a instalação de referência com a chave <instalação>
      E a Acme, com cor "#7c3aed", e a Initech, sem cor, com a chave <organização>
      E <pessoa> com a escolha de referência gravada
      Quando <pessoa> abre <página>
      Então a página mostra densidade "<densidade>", fonte "<fonte>", cor "<cor>" e menu "<menu>"

      Exemplos:
        | pessoa                                    | instalação | organização | página          | densidade | fonte        | cor     | menu             | # regra                            |
        | a Ana (membro)                            | ligada     | ligada      | /app/acme       | Denso     | Muito grande | Red     | Manter os ícones | D1 duas chaves; pessoal vence a cor da organização (P-02) |
        | a Ana (membro)                            | ligada     | desligada   | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D2 organização não liberou (P-10)  |
        | a Ana (membro)                            | desligada  | ligada      | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D3 as duas, não uma (RQ-09)        |
        | a Ana (membro)                            | desligada  | desligada   | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D4                                 |
        | a Ana (membro)                            | ligada     | desligada   | /admin          | Denso     | Muito grande | Red     | Manter os ícones | D7 /admin não consulta organização |
        | a Ana (membro)                            | ligada     | desligada   | /app/initech    | Compacto  | Grande       | Teal    | Esconder tudo    | D8 organização sem cor não liberou: cor da instalação, não a pessoal (ADV-04) |
        | a Carla (admin_app da Acme)               | ligada     | desligada   | /app/acme       | Compacto  | Grande       | #7c3aed | Esconder tudo    | D9 quem administra a organização segue a chave dela (ADV-17) |
        | a administradora da instalação (`admin`)  | desligada  | ligada      | /admin          | Compacto  | Grande       | Teal    | Esconder tudo    | D10 quem administra a instalação segue a chave dela (ADV-18) |

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
      E a Acme com a chave ligada, a Globex, cor "#059669", e a Initech, sem cor, com a chave desligada
      E a Ana, membro das três, com a escolha de referência gravada, e que acabou de abrir /app/<primeira>
      Quando a Ana abre /app/<segunda>
      Então a página mostra densidade "<densidade>" e cor "<cor>"

      Exemplos:
        | primeira | segunda | densidade | cor     | # sequência                                         |
        | acme     | globex  | Compacto  | #059669 | liberada → não liberada com cor                     |
        | globex   | acme    | Denso     | Red     | não liberada → liberada                             |
        | acme     | initech | Compacto  | Teal    | liberada → não liberada **sem cor** (ADV-22)        |
```

Notas do CT-14: na linha `acme → initech` nenhuma cor de organização é registrada no segundo request; se a Red registrada no primeiro sobrar no processo, só essa linha a vê.

Notas: a coluna `organização` vale para Acme e Initech ao mesmo tempo; a Ana é membro das duas e a Carla só da Acme.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M22 | `OU` entre as chaves no lugar de `E` | CT-12 (linhas D2, D3) | densidade Denso; esperado Compacto |
| M23 | dentro da organização, só a chave da organização é lida | CT-12 (linha D3) | densidade Denso com a instalação desligada |
| M24 | a cor da organização vence a da pessoa (precedência da cor por organização mantida em cima da pessoal) | CT-12 (linha D1) | cor `#7c3aed`; esperado Red |
| M25 | sem organização no endereço, lê a "organização da sessão" ou trata como não liberada | CT-12 (linha D7; D5 `@obsoleto` em 2026-11-18 — com tenancy o Meu perfil do `/app` está dentro de organização, P-13) | densidade Compacto em /admin; esperado Denso |
| M26 | "Padrão" na cor cai direto na instalação, pulando a organização | CT-13 (linha Acme) | cor Teal; esperado `#7c3aed` |
| M27 | aparência efetiva guardada por usuário (memo/singleton) no primeiro request | CT-14 (as duas linhas) | segunda organização com a densidade/cor da primeira |
| M75 | a chave da organização só decide densidade/fonte/menu; a cor cai em `cor da organização ?? cor pessoal` | CT-12 (linha D8) | cor Red na Initech não liberada; esperado Teal |
| M76 | quem pode editar a organização (`admin_app`) vê a própria escolha mesmo sem a liberação | CT-12 (linha D9) | densidade Denso; esperado Compacto |
| M77 | quem administra a instalação (`admin`/`can('update', settings)`) passa por cima da chave da instalação | CT-12 (linha D10) | densidade Denso; esperado Compacto |
| M81 | a cor pessoal é registrada em `FilamentColor` e só sobrescrita quando há cor de organização ou de instalação a registrar — sem cor da organização, a do request anterior sobra (ADV-22) | CT-14 (linha acme → initech) | cor Red na Initech; esperado Teal |

---

## Regra R6 — Valor gravado fora do vocabulário cai no padrão na leitura, sem erro e sem derrubar as preferências válidas

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

    Esquema do Cenário: [CT-38] uma chave inválida no perfil não derruba a preferência válida gravada ao lado
      Dado a instalação de referência com a chave ligada
      E o perfil da Ana gravado diretamente como <gravado>
      Quando a Ana abre /admin
      Então a resposta é 200
      E a página mostra <inválida> no padrão da instalação e <válida> com o valor gravado

      Exemplos:
        | gravado                                          | inválida              | válida                         |
        | {"densidade": "ultra", "cor": "Red"}             | densidade (Compacto)  | cor (Red)                      |
        | {"cor": "Gray", "fonte": "muito-grande"}         | cor (#0ea5e9)         | fonte (Muito grande)           |
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
| M78 | uma chave inválida descarta o JSON inteiro (validação "tudo ou nada" na leitura) (ADV-07) | CT-38 | cor `#0ea5e9` / fonte Grande; esperado Red / Muito grande |

---

## Regra R7 — Desligar uma chave não apaga a escolha; religada, ela volta a valer

> `P-05`, `P-11` · perfil **padrão** · técnica: **2-switch** (desligar → religar; oráculo sobre o destino do segundo evento e sobre o registro do ciclo anterior)

```gherkin
  Regra: desligar uma chave ignora a escolha sem apagá-la

    Cenário: [CT-17] a chave da instalação desligada e religada pela tela devolve a escolha da Ana
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      E a administradora desligou a chave salvando a tela de Configurações
      Quando a administradora religa a chave salvando a tela de Configurações
      Então a página /admin servida à Ana mostra densidade "Denso" e cor "Red"
      E o perfil da Ana guarda a mesma escolha de referência de antes

    Cenário: [CT-18] a chave da organização desligada e religada pelo cadastro devolve a escolha da Ana
      Dado a instalação de referência com a chave ligada
      E a Acme com a chave ligada e a Ana, membro dela, com a escolha de referência gravada
      E a administradora desligou a chave da Acme salvando a edição da organização
      Quando a administradora religa a chave da Acme salvando a edição da organização
      Então a página /app/acme servida à Ana mostra densidade "Denso" e cor "Red"
      E o perfil da Ana guarda a mesma escolha de referência de antes

    Esquema do Cenário: [CT-19] com a chave da instalação desligada, editar a organização não mexe na chave dela
      Dado a chave da instalação desligada
      E a Acme com a chave ligada
      Quando a administradora salva a edição da Acme com <alteração>
      Então a Acme continua gravada com a chave ligada

      Exemplos:
        | alteração                                                       | # caminho                         |
        | só o nome trocado para "Acme S.A."                              | campo oculto desidratado como falso |
        | o interruptor oculto forjado como desligado no estado do formulário | campo oculto aceito do cliente    |

    Cenário: [CT-39] religar a chave depois de uma gravação recusada não traz o que foi enviado com ela desligada
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      E, com a chave desligada depois de a seção abrir, a gravação da Ana com densidade "Confortável" foi recusada
      Quando a administradora religa a chave salvando a tela de Configurações
      Então a página /admin servida à Ana mostra densidade "Denso"
      E o perfil da Ana guarda a escolha de referência
```

Notas: em CT-17 e CT-18 o desligamento do `Dado` é transição real (salvar a tela), não gravação direta — é o caminho em que um hook de limpeza rodaria. O "padrão enquanto desligada" já é afirmado por CT-09 (linhas desligada) e CT-12 (linha D2). CT-19 afirma só o valor gravado da chave (um evento, ADV-24); o efeito na página da chave ligada já é CT-12/CT-18. CT-39 é o segundo evento que antes estava no `Então` do CT-08 (ADV-24): religada, vale a escolha anterior (P-05), não a enviada durante a recusa (P-15). **Estouro de teto justificado** (R7 com 4 cenários): M16 na sua forma "grava e ignora" só aparece na página depois de religar — CT-39 é o único observável disso pela página.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | desligar a chave da instalação limpa as escolhas (hook no `save` das configurações) | CT-17 | o perfil da Ana sem escolha; página religada em Compacto |
| M35 | desligar a chave da organização limpa a escolha dos membros | CT-18 | o perfil da Ana sem escolha; /app/acme religado em Compacto |
| M36 | interruptor oculto desidratado como `false` no `save` | CT-19 (linha nome) | chave da Acme desligada depois da edição |
| M37 | interruptor oculto com `dehydratedWhenHidden()`/valor aceito do estado | CT-19 (linha forjado) | chave da Acme desligada |
| M38 | religar só vale para escolhas gravadas depois do religamento (escolha marcada como "inativa" ao desligar) | CT-17, CT-18 | religada, a página continua Compacto |
| M85 | a gravação recusada fica pendente (sessão/rascunho) e é aplicada quando a chave volta | CT-39 | religada, a página mostra Confortável; esperado Denso |

---

## Regra R8 — A instalação define o padrão das quatro preferências e a chave, e o gravado governa a página

> `RQ-06`, `RQ-07`, `P-03`, `P-08`, `P-09` · perfil **padrão** · técnica: **EP** + **valor de fábrica literal** (o `Dado` afirma o valor efetivo lido, não o ambiente)

```gherkin
  Regra: a instalação define o padrão das quatro preferências e a chave

    Cenário: [CT-20] o que a administradora grava nas Configurações governa a página de quem não escolheu
      Dado a chave da instalação ligada, fonte "Normal" e menu "Manter os ícones"
      E a Bia sem nenhuma escolha gravada
      Quando a administradora grava na seção "Aparência dos painéis" das Configurações fonte "Grande", menu "Esconder tudo" e a chave desligada
      Então as configurações gravadas guardam fonte "Grande", menu "Esconder tudo" e a chave desligada
      E a página /app servida à Bia mostra fonte "Grande" e menu "Esconder tudo"

    Esquema do Cenário: [CT-21] os valores de fábrica são a chave ligada, a fonte Normal e o menu com ícones
      Dado uma instalação recém-migrada, sem gravação de teste nessas configurações
      Quando o valor efetivo de <configuração> é lido do arquivo de configuração sem variável de ambiente e da linha semeada no settings
      Então os dois valem "<fábrica>"

      Exemplos:
        | configuração        | fábrica          | # origem |
        | chave da instalação | ligada           | P-03     |
        | tamanho da fonte    | Normal           | P-08     |
        | modo do menu        | Manter os ícones | P-09     |

    Cenário: [CT-40] a mudança de padrão feita pela administradora chega a quem já estava navegando
      Dado a chave da instalação ligada, fonte "Normal" e menu "Manter os ícones"
      E a Bia, sem nenhuma escolha gravada, acabou de abrir /app no mesmo processo e viu fonte "Normal"
      Quando a administradora grava fonte "Muito grande" na seção "Aparência dos painéis" das Configurações
      Então a página /app servida à Bia em seguida mostra fonte "Muito grande"
```

Notas: CT-40 roda o primeiro GET da Bia, a gravação da administradora e o segundo GET da Bia no mesmo processo, sem `fronteiraDeRequest()` entre eles, com `test()->flushSession()` na troca de pessoa (ADV-20, que completa o ADV-06: ler → mudar por **outro** ator → ler). A variante com a chave da organização (o `/app/acme` da Ana antes e depois de a administradora liberar) foi cortada pelo teto — ver Cogitado e cortado. CT-21 usa `kitConfigCom('{variável}', null)` (arquivo de configuração) e `configuracaoGravada('{propriedade}')` (linha da migration de settings) — nunca `config()`, que mediria o `phpunit.xml`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | propriedade nova no settings sem linha no mapa de configuração (o campo grava e não governa) | CT-20 | configuração gravada Grande, página da Bia em Normal |
| M40 | campo da tela grava em propriedade trocada (fonte ↔ menu) | CT-20 | configurações gravadas com valores trocados |
| M41 | chave nasce desligada (`false` no default) | CT-21 (linha chave) | valor lido desligado |
| M42 | fonte de fábrica diferente de Normal | CT-21 (linha fonte) | valor lido ≠ Normal |
| M43 | menu de fábrica "Esconder tudo" | CT-21 (linha menu) | valor lido Esconder tudo |
| M83 | padrão efetivo da instalação guardado por pessoa (sessão/cache) no primeiro request e não invalidado quando as Configurações são salvas (ADV-20) | CT-40 | o segundo /app da Bia continua em Normal; esperado Muito grande |

---

## Regra R9 — A organização libera pelo interruptor no cadastro do `/admin`, que nasce desligado e só aparece com a chave da instalação ligada

> `RQ-08`, `P-04`, `P-11` · perfil **padrão** · técnica: **EP** (criar × editar) + **tabela de decisão** (visibilidade: chave × tela) · suíte `tests/Tenancy`

```gherkin
  Regra: a organização libera pelo interruptor do cadastro, desligado de nascença

    Esquema do Cenário: [CT-22] ligar o interruptor no cadastro libera a escolha pessoal dentro da organização
      Dado a instalação de referência com a chave ligada
      E a Ana com a escolha de referência gravada
      Quando a administradora <ação> a organização "<slug>" com "Liberar a aparência pessoal" ligado
      Então a organização "<slug>" fica gravada com a chave ligada
      E a Ana, membro dela, vê densidade "Denso" em /app/<slug>

      Exemplos:
        | ação   | slug     |
        | cria   | umbrella |
        | edita  | acme     |

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

    Esquema do Cenário: [CT-24] o interruptor "Liberar a aparência pessoal" só aparece com a chave da instalação ligada
      Dado a chave da instalação <instalação>
      Quando a administradora abre a tela de <tela> de organização
      Então o interruptor "Liberar a aparência pessoal" está <visível>

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

## Regra R10 — Os formulários de administração recusam valor fora do domínio e mantêm o anterior

> Superfície Livewire (`ConfiguracoesDoKit` — `aparencia_por_usuario`, `tamanho_da_fonte`, `modo_do_menu`; `TenantForm` — `aparencia_por_usuario`), `P-12` · perfil **padrão** · técnica: **EP**, uma partição inválida por linha. Recusa do booleano forjado: fato do vendor (`Toggle::setUp` acrescenta a regra `boolean`; ver Fronteira)

```gherkin
  Regra: os formulários de administração recusam valor fora do domínio

    Esquema do Cenário: [CT-25] fonte e menu fora do vocabulário no estado da tela de Configurações não são gravados como vieram
      Dado a chave da instalação ligada, fonte "Grande" e menu "Esconder tudo" gravados
      Quando a administradora salva as Configurações com <campo> = <valor> escrito no estado do formulário
      Então a resposta não é erro de servidor
      E a configuração gravada de <campo> pertence ao vocabulário dela e não é <valor>

      Exemplos:
        | campo            | valor      | # partição inválida |
        | tamanho da fonte | "gigante"  | fora do enum        |
        | modo do menu     | "nenhum"   | fora do enum        |
        | tamanho da fonte | ["grande"] | tipo errado         |
        | modo do menu     | 1          | tipo errado         |

    Esquema do Cenário: [CT-26] valor não booleano numa chave é recusado e a chave fica como estava
      Dado a chave da instalação <instalação antes>
      E a Acme com a chave desligada
      E a Ana, membro da Acme, com a escolha de referência gravada
      Quando a administradora salva <tela> com <interruptor> = <valor> no estado do formulário
      Então há erro de validação no campo <interruptor>
      E <chave> continua desligada
      E a Ana vê densidade "Compacto" em <página>

      Exemplos:
        | instalação antes | tela                   | interruptor                         | valor    | chave                    | página    |
        | desligada        | as Configurações       | a chave da instalação               | "talvez" | a chave da instalação    | /admin    |
        | ligada           | a edição da Acme       | "Liberar a aparência pessoal"       | "talvez" | a chave da Acme          | /app/acme |
        | ligada           | a edição da Acme       | "Liberar a aparência pessoal"       | ["1"]    | a chave da Acme          | /app/acme |
```

Notas: CT-25 afirma só o que o `00` determina — o valor fora do vocabulário não vale e o gravado continua no vocabulário —, sem decidir entre recusar com erro e coagir (ADV-23). CT-26 afirma o fato do vendor para as duas chaves booleanas (`Toggle::setUp` → `rule('boolean')`): erro no campo, valor anterior mantido; a chave começa **desligada** para que a conversão de `"talvez"` em `true` seja observável. CT-26 vive em `tests/Tenancy` (a tela de Configurações existe nas duas suítes).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | Select sem `in()` e sem coerção antes de gravar | CT-25 (linhas "gigante", "nenhum") | configuração gravada `"gigante"` |
| M49 | coerção por `tryFrom()` sobre não escalar (`TypeError`) | CT-25 (linhas tipo errado) | resposta 500 |
| M50 | propriedade da chave sem tipo `bool` no settings (grava a string) | CT-26 (linha da instalação) | configuração gravada `"talvez"` |
| M51 | coluna sem cast `boolean` no model | CT-26 (linhas da Acme) | chave gravada como `"talvez"` / `["1"]` |
| M67 | a chave da instalação vira campo não-Toggle (Checkbox/Select "sim/não" sem regra `boolean`) e o cast converte o `"talvez"` forjado em `true` | CT-26 (linha da instalação) | sem erro no campo e chave gravada ligada; Ana vê Denso no /admin; esperado erro, chave desligada e Compacto |
| M68 | o interruptor da organização perde a validação (`->rules([])`/campo não-Toggle) e o cast converte `"talvez"`/`["1"]` em `true`, liberando a Acme | CT-26 (linhas da Acme) | sem erro no campo e Acme liberada; Ana vê Denso; esperado erro, chave desligada e Compacto |

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
        | pessoa             | painel dela |
        | o Ivo (infra)      | /infra      |
        | a Bia (panel_user) | /app        |

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

## Regra R12 — A seção Aparência aparece no Meu perfil dos três painéis só com a chave da instalação ligada, com o aviso sobre organizações quando elas existem

> `P-06`, `P-13`, `P-16` · perfil **padrão** · técnica: **EP** (painel × chave) e **tabela de decisão** (tenancy × painel × liberação)

```gherkin
  Regra: a seção Aparência aparece só com a chave da instalação ligada

    Esquema do Cenário: [CT-29] a seção Aparência, com os quatro campos, acompanha a chave da instalação nos três painéis
      Dado a chave da instalação <chave>
      Quando a Ana abre <meu perfil>
      Então a seção "Aparência" está <presença>
      E, presente, ela traz os campos de densidade, fonte, cor e menu e o botão "Salvar aparência"
      E a seção de dados pessoais do Meu perfil está presente

      Exemplos:
        | chave     | meu perfil        | presença |
        | ligada    | /admin/meu-perfil | presente |
        | ligada    | /app/meu-perfil   | presente |
        | ligada    | /infra/meu-perfil | presente |
        | desligada | /admin/meu-perfil | ausente  |
        | desligada | /app/meu-perfil   | ausente  |
        | desligada | /infra/meu-perfil | ausente  |

    Esquema do Cenário: [CT-30] com organizações, a Ana grava pelo Meu perfil de qualquer painel e lê o aviso sobre a organização que não liberou
      Dado a chave da instalação ligada
      E a Acme, da qual a Ana é membro, com a chave <organização>
      Quando a Ana grava densidade "Denso" na seção Aparência de <meu perfil>
      Então o perfil da Ana guarda densidade "Denso"
      E a seção mostra o aviso de que, numa organização que não liberou, vale o padrão dela

      Exemplos:
        | meu perfil        | organização |
        | /app/meu-perfil   | ligada      |
        | /app/meu-perfil   | desligada   |
        | /admin/meu-perfil | desligada   |
        | /infra/meu-perfil | desligada   |

    Esquema do Cenário: [CT-31] sem organizações, a seção não mostra o aviso em nenhum painel
      Dado a chave da instalação ligada e o kit sem organizações
      Quando a Ana abre a seção Aparência de <meu perfil>
      Então a seção mostra os quatro campos
      E a seção não mostra o aviso sobre organizações

      Exemplos:
        | meu perfil        |
        | /admin/meu-perfil |
        | /app/meu-perfil   |
        | /infra/meu-perfil |
```

Notas: CT-29 é HTTP (G2): `assertSee` sobre o título "Aparência" num elemento de cabeçalho de seção (o texto sozinho pode casar com outro do painel) e sobre "Salvar aparência"; os quatro campos se acham pelos rótulos do `00` (Densidade/fonte/cor/menu) ou pelo `id` do campo; controle positivo na seção de dados pessoais do Breezy, para o `assertDontSee` não provar a ausência de tudo (ADV-13). CT-30 (Tenancy) e CT-31 (Kit) são de componente (G1); o fragmento do aviso vem do conteúdo determinado por P-06 ("vale o padrão dela"). P-16 (Q6 do `00`) fixa o aviso nos três painéis com organizações (ADV-03).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M56 | componente registrado só num painel (`myProfileComponents` de um provider) | CT-29 (linhas ligadas de /app e /infra) | seção ausente |
| M57 | seção sempre visível (sem condição na chave) | CT-29 (linhas desligada) | seção presente com a chave desligada |
| M58 | gravação recusada quando as organizações da pessoa não liberaram (chave da organização consultada fora da organização) | CT-30 (linhas desligada) | o perfil não guarda Denso |
| M59 | aviso sempre visível, mesmo sem organizações | CT-31 | o aviso aparece |
| M60 | aviso esquecido no modo com organizações | CT-30 | o aviso não aparece |
| M79 | aviso só no Meu perfil do `/app` (condição pelo painel corrente) (ADV-03) | CT-30 (linhas /admin, /infra) | o aviso não aparece no /admin e no /infra |
| M80 | seção com um campo a menos num painel (schema montado por painel) (ADV-13) | CT-29 (linhas ligadas) | falta o campo de menu/fonte no /infra |

---

## Regra R13 — O efeito no layout: "Esconder tudo" some com o menu inteiro ao recolher; os tamanhos de fonte escalam pelos percentuais

> `RQ-02`, `RQ-04`, `P-08`, `P-09` · perfil **padrão** · costura **browser** (G5). Cenários CT-B01 e CT-B02, mutantes e roteiro em `05-casos-de-teste-browser.md`.

---

## Regra R14 — O documento de candidatas a pacote existe em `wikis/`, declara os dois propósitos e viaja com o kit

> `RQ-12`, `P-14` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: o documento de candidatas a pacote existe e viaja com o kit

    Cenário: [CT-32] o documento está ao lado do roadmap, declara os dois propósitos numa seção própria e viaja nas duas rotas de entrega
      Dado a árvore do kit
      Quando o arquivo wikis/candidatas-a-pacote.md é lido seção por seção
      Então ele existe e não está sob wikis/specs
      E está na lista de caminhos do kit:update e fora do export-ignore do .gitattributes
      E uma mesma seção (sob um título de seção Markdown) declara o uso por outros projetos e a contribuição de colaboradores
```

Notas: as seções vêm de `secoesDoMarkdown()`; o propósito se afirma pelos termos do texto original de Q4 ("outros projetos", "contribu") **dentro da mesma seção**, não soltos no arquivo (ADV-14).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M61 | documento criado em `wikis/specs/...` (não viaja) ou fora de `CAMINHOS_DO_KIT` | CT-32 | `caminhosDoKit()` sem o caminho |
| M62 | documento só lista, sem dizer para que serve (os termos aparecem só de passagem numa entrada de candidata) | CT-32 | nenhuma seção tem os dois termos juntos |

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
| Autorização exercida na ação (não só `can()`) | CT-27, CT-28 (rota 403 + policy/`canAccess()`, valor gravado intacto); CT-08 (`salvar()` confere a chave na ação) | G4, G1 |
| Idempotência (ancorada no agregado) | não se aplica: a gravação substitui o valor de cada preferência no registro da pessoa; não há acúmulo, contador nem efeito colateral a duplicar — o mutante "acumula" não é expressável num valor por chave | — |
| Concorrência | não se aplica: cada pessoa escreve só o próprio registro, sem limite, saldo ou contador; duas abas gravando = a última vence, que é o comportamento de qualquer formulário de perfil | — |
| **Fronteira no ponto de entrada** (gravação) | CT-04, CT-05 (pessoa), CT-25 (instalação), CT-26 (organização) | G1, G3 |
| **Domínio condicionado** (tipo × valor) | CT-24 (interruptor da organização condicionado à chave da instalação) | G3 |
| **Estado × operação de escrita** | matriz de 9 células acima: CT-08 (gravar com a chave desligada: recusa), CT-30, CT-12, CT-29 | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-15 (linhas `{}`, `null`, `""`), CT-02 ("Padrão"), CT-01 (campo não tocado não é gravado) | G2, G1 |
| Paginação / ordenação | não se aplica: nenhuma listagem nova | — |
| Timezone / DST | não se aplica: nenhum dado temporal | — |
| Unicode / limite de varchar / texto livre | não se aplica a texto livre (todos os domínios são listas fechadas); o texto hostil que chega ao CSS está em CT-04 e CT-15 (linhas de injeção) | G1, G2 |
| Unicidade + soft delete | não se aplica: nenhum campo único novo | — |
| Entidade removível ou desativável | não se aplica: a escolha não tem ciclo de vida; desligar as chaves é o "desativar" e está em CT-08, CT-17, CT-18, CT-19 | G1, G2, G3 |
| CRUD combinado | não se aplica: nenhuma entidade nova com CRUD; editar sem alterar nada = CT-19 (linha nome) | G3 |
| Mass assignment | `users`: CT-06 (linhas email, tema), CT-35 (formulário de dados pessoais) · `tenants`: CT-19 (interruptor oculto forjado), CT-26 · `settings`: CT-25 | G1, G3 |
| Escrita da chave da organização por outra tela (ADV-10) | não se aplica: o `/app` não tem tela de edição nem de cadastro da organização — nenhum `tenantProfile()`/`tenantRegistration()` nos providers (`app/Providers/Filament/AppPanelProvider.php:AppPanelProvider::panel:651`, comentário "Sem `->tenantRegistration()` de propósito"; `grep -n tenantProfile app/Providers/Filament/*.php` vazio). A única escrita é o cadastro em `/admin/organizacoes`, coberta por CT-22…CT-28. Escrito como se fosse falso: CT-28 (quem administra a organização tenta pela única rota e recebe 403) | — |
| Upload | CT-07 (`_startUpload` recusado) | G1 |
| Precisão monetária | não se aplica: sem valor monetário; os percentuais de fonte são provados por razão calculada em CT-B02 | — |
| **Superfície Livewire** (método público, prop pública, estado do framework) | `$data`: CT-04, CT-05, CT-06 · `salvar()`: CT-06, CT-08 · `_startUpload`: CT-07 · `ConfiguracoesDoKit` (três campos): CT-25 · `TenantForm`: CT-26 · `users.aparencia` lido no render: CT-15, CT-38 | G1, G2, G3 |
| **Estado do framework usado sem validar** (índice, `match`, `tryFrom`, CSS) | CT-15 (`match`/`tryFrom`/`constant()`/CSS), CT-16 (padrão da instalação), CT-38 (JSON misto) | G2 |
| **Estado que sobra no processo entre requests** (cor registrada, memo) | CT-10 (outra pessoa antes), CT-14 (outra organização antes), CT-37 (a mesma pessoa antes e depois de gravar) | G2 |
| **IDOR por entidade** (uma linha por tabela persistida) | `users`: CT-06 · `tenants`: CT-28 · `settings`: CT-27 | G1, G4 |
| **Escopo com discriminante nulo** (organização ausente no endereço) | CT-12 (linhas D7, D10 — /admin; D5 e D6 `@obsoleto` em 2026-11-18, P-13); CT-11 (usuário nulo) | G2 |
| **Saída do estado de erro** (4xx tem destino) | CT-27, CT-28 (o painel da pessoa abre com 200); CT-07 (o Meu perfil reabre com 200) | G4, G1 |
| Efeito colateral (cardinalidade 0/1/N) | não se aplica: a feature não notifica por canal externo, não enfileira e não audita por requisito; o único aviso (CT-08) é na tela da própria pessoa | — |

## Revisão Adversarial

Rodada 1 — `fw-adversario-ct`, despacho da sessão, entrada `00` + `04` + `05` + glossário. **18 achados**; todos fechados. Cenário novo criado (CT-37, CT-38): re-revisão única devida.

| Achado | Resumo | Destino |
|---|---|---|
| ADV-01 | CT-08 não distinguia "recusa" de "grava e ignora" | oráculo reescrito: CT-08 afirma perfil sem escolha, aviso visível e religar sem trazer a escolha (P-15); mutantes M16 (reescrito) e M70 |
| ADV-02 | campos nascendo com o padrão vigente congelariam as outras preferências | oráculo reforçado: CT-01 (só a preferência escolhida fica gravada) e CT-02 (a instalação muda cor e menu depois e a página acompanha); mutante M69 |
| ADV-03 | aviso em `/admin` e `/infra` com organizações | linhas novas de Exemplos em CT-30 (/admin, /infra) e CT-31 (três painéis), por P-16; mutante M79 |
| ADV-04 | organização sem cor própria e não liberada, pessoa com cor | linha nova D8 em CT-12 (cor da instalação, não a pessoal); mutante M75 |
| ADV-05 | sequência de requests: Ana com cor, depois Bia sem escolha | oráculo reforçado: CT-10 com a outra pessoa abrindo `/admin` antes, no mesmo processo, sem `fronteiraDeRequest()`; mutante M71 |
| ADV-06 | ler → mudar → ler sem cache | cenário novo CT-37; mutante M74 |
| ADV-07 | JSON misto (uma chave inválida + uma válida) | cenário novo CT-38; mutante M78 |
| ADV-08 | cadastro e recuperação de senha sem sessão | linhas novas de Exemplos em CT-11; mutante M72 |
| ADV-09 | gravar no `/app/meu-perfil` e ler no `/admin` | oráculo reescrito: o `Dado` de CT-09 grava pela seção do `/app` e as linhas leem nos três painéis; mutante M73 |
| ADV-10 | escrita da chave da organização fora do `/admin` | não se aplica, com evidência (fato confirmado pela sessão): o `/app` não tem tela de edição da organização — linha própria no Checklist de Taxonomia |
| ADV-11 | (par de ADV-01) religar a chave não pode trazer a escolha feita com ela desligada | fechado com ADV-01 (última linha do `Então` de CT-08) |
| ADV-12 | (par de ADV-02) mudar o padrão da instalação depois tem de chegar às outras três | fechado com ADV-02 (CT-02) |
| ADV-13 | CT-29 não afirmava os quatro campos | oráculo reforçado: CT-29 afirma os quatro campos e "Salvar aparência" nos três painéis; mutante M80 |
| ADV-14 | CT-32 aceitava os termos soltos no arquivo | oráculo reforçado: os dois propósitos na mesma seção (`secoesDoMarkdown()`); M62 reescrito |
| ADV-15 | CT-B01 com mais de um `Quando` | cenário reescrito no `05`: abrir vai para o `Dado`, o `Quando` é só recolher |
| ADV-16 | booleano forjado sem direção | fechado pelo fato do vendor (`Toggle::setUp` → `rule('boolean')`): CT-25 e CT-26 afirmam erro no campo e valor anterior mantido; M67 e M68 ganharam matador |
| ADV-17 | `admin_app` da organização com escolha própria | linha nova D9 em CT-12; mutante M76 |
| ADV-18 | quem administra a instalação com escolha própria | linha nova D10 em CT-12; mutante M77 |

Rodada 2 — `fw-adversario-ct`, mesma entrada. **Última pelo teto de 2 rodadas; não há rodada 3.** Dos 18 da rodada 1: 16 confirmados fechados; **ADV-06 parcial** (fechado agora por CT-40, abaixo, como ADV-20); **ADV-10 não verificável pelo revisor** — que só lê `00`/`04`/`05` — e fica registrado como está, com a evidência de código citada no Checklist de Taxonomia. Seis achados novos, todos fechados:

| Achado | Resumo | Destino |
|---|---|---|
| ADV-19 | CT-08 só partia de perfil vazio; "nada é gravado" não era testado com escolha anterior | linha nova de Exemplos em CT-08 (`a escolha de referência gravada`, envia Confortável → perfil continua com a escolha de referência); mutante M82 |
| ADV-20 | ler → mudar por **outro** ator → ler | cenário novo CT-40 (R8): a Bia lê, a administradora grava fonte nas Configurações, a Bia lê de novo; mutante M83. A variante com a chave da organização foi cortada pelo teto (Cogitado e cortado) |
| ADV-21 | CT-08 afirmava o aviso só por existir notificação | oráculo reforçado: aviso pelo conteúdo (personalização desligada) e ausência de "Aparência salva"; mutante M84 |
| ADV-22 | sequência liberada com cor pessoal → organização sem cor e não liberada | linha nova em CT-14 (`acme → initech`, cor Teal); mutante M81 |
| ADV-23 | CT-25 exigia "erro presente ou ausente" para fonte e menu, além do que o `00` determina | CT-25 reescrito só com o invariante (∈ vocabulário, ≠ enviado); as duas chaves booleanas foram para CT-26, com erro no campo e valor mantido (fato do Toggle); M50 e M67 mudaram de matador |
| ADV-24 | CT-08 e CT-19 com um segundo evento no `Então` | CT-08 sem a religação (ela virou o cenário novo CT-39, R7, mutante M85); CT-19 afirma só o valor gravado da chave |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a escolha gravada governa a página, e só ela fica gravada | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M01, M05, M69 |
| CT-02 | o que ficou em "Padrão" acompanha a instalação | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M02, M03, M69 |
| CT-03 | a seção oferece exatamente o vocabulário + "Padrão" | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M04, M05 |
| CT-04 | valor fora do domínio no estado do componente não vale | R2 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M06, M07, M08, M09 |
| CT-05 | valor de tipo errado não vale e não quebra | R2 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M10 |
| CT-06 | campo extra não grava fora das preferências da própria Ana | R3 | matriz papel × ação | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M11, M12, M13 |
| CT-35 | dados pessoais do Meu perfil não gravam a aparência | R3 | EP (mass assignment) | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M14 |
| CT-07 | upload para o componente é recusado | R3 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M15 |
| CT-08 | chave desligada depois de abrir: gravação recusada com aviso, perfil intacto (vazio ou com escolha) | R3 | estado × operação | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M16, M70, M82, M84 |
| CT-09 | escolha gravada no /app vale nos três painéis com a chave ligada; some desligada | R4 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M17, M18, M19, M73 |
| CT-10 | sem escolha: padrão da instalação, logo depois de outra pessoa | R4 | tabela de decisão; sequência | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M20, M71 |
| CT-11 | telas sem sessão: padrão da instalação | R4 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M21, M72 |
| CT-37 | a página reflete a escolha nova logo depois de gravá-la | R4 | sequência (ler → mudar → ler) | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M74 |
| CT-12 | tabela das duas chaves, para cada papel | R5 | tabela de decisão | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M22, M23, M24, M25, M75, M76, M77 |
| CT-13 | "Padrão" na cor: organização, depois instalação | R5 | tabela de decisão | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M26 |
| CT-14 | não vaza de uma organização para a seguinte (inclusive para a sem cor) | R5 | tabela de decisão (ordem) | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M27, M81 |
| CT-15 | escolha gravada fora do vocabulário: padrão, sem erro | R6 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M28, M29, M30, M31, M32 |
| CT-16 | padrão da instalação fora do vocabulário: fábrica | R6 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M28, M33 |
| CT-38 | chave inválida não derruba a válida ao lado | R6 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M78 |
| CT-17 | chave da instalação desligada e religada | R7 | 2-switch | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M34, M38 |
| CT-18 | chave da organização desligada e religada | R7 | 2-switch | G2 | Pest feature HTTP | `tests/Tenancy/AparenciaEfetivaTenancyTest.php` | M35, M38 |
| CT-19 | editar a organização com a instalação desligada não mexe na chave | R7 | 2-switch | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M36, M37 |
| CT-39 | religar depois de uma gravação recusada não traz o que foi enviado | R7 | 2-switch | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M16, M85 |
| CT-20 | o gravado nas Configurações governa a página | R8 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M39, M40 |
| CT-21 | valores de fábrica | R8 | valor literal | G7 | Pest feature HTTP | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M41, M42, M43 |
| CT-40 | mudança de padrão pela administradora chega a quem já navegava | R8 | sequência (ler → outro ator muda → ler) | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M83 |
| CT-22 | interruptor ligado no cadastro libera | R9 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M44, M45 |
| CT-23 | chave da organização nasce desligada | R9 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M46 |
| CT-24 | interruptor só com a chave da instalação ligada | R9 | tabela de decisão | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M45, M47 |
| CT-25 | fonte e menu fora do vocabulário não são gravados como vieram | R10 | EP | G3 | componente Livewire/Filament | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M48, M49 |
| CT-26 | valor não booleano numa chave (instalação ou organização) é recusado e a chave fica como estava | R10 | EP | G3 | componente Livewire/Filament | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M50, M51, M67, M68 |
| CT-27 | quem não administra não chega às Configurações | R11 | matriz papel × ação | G4 | Pest feature HTTP | `tests/Kit/ConfiguracoesDeAparenciaTest.php` | M52, M53, M55 |
| CT-28 | admin da organização não libera a aparência | R11 | matriz papel × ação | G4 | Pest feature HTTP | `tests/Tenancy/ChaveDeAparenciaDaOrganizacaoTest.php` | M54, M55 |
| CT-29 | seção com os quatro campos acompanha a chave nos três painéis | R12 | EP | G2 | Pest feature HTTP | `tests/Kit/AparenciaEfetivaTest.php` | M56, M57, M80 |
| CT-30 | com organizações: grava por qualquer painel e lê o aviso | R12 | tabela de decisão | G1 | componente Livewire/Filament | `tests/Tenancy/PreferenciasDeAparenciaTenancyTest.php` | M58, M60, M79 |
| CT-31 | sem organizações: sem aviso nos três painéis | R12 | EP | G1 | componente Livewire/Filament | `tests/Kit/PreferenciasDeAparenciaTest.php` | M59 |
| CT-32 | documento existe, propósito numa seção, viaja | R14 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M61, M62 |
| CT-33 | aparência listada como candidata | R15 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M63, M64 |
| CT-34 | outras candidatas do kit, com código | R16 | EP | G6 | Pest feature HTTP | `tests/Kit/CandidatasAPacoteTest.php` | M65, M66 |
| CT-B01, CT-B02 | ver `05-casos-de-teste-browser.md` | R13 | EP | G5 | browser | `tests/Browser/PreferenciasDeAparenciaTest.php` | ver `05` |

Estouro de teto: R3, R4 e R7 com 4 cenários cada (justificados nas regras: M14/M16/M70/M82/M84, M74 e M85 só morrem nos cenários excedentes); as demais com no máximo 3. R1/R2/R3 dividem a superfície Livewire em três regras para respeitar o teto `padrão`. Nenhuma lacuna declarada.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| tela de bloqueio (`/screen/lock`) com a escolha da pessoa | o `00` não a menciona (P-10 lista só telas sem pessoa logada); nenhum mutante previsto depende dela |
| BVA nos percentuais de fonte | não há faixa: quatro valores fixos; o número literal está em CT-B02 |
| gravação idêntica repetida (duplo clique) | não mata mutante nenhum (substituição de valor; ver taxonomia) |
| cor "Grande"/"Esconder tudo" em CT-01 | não discriminam contra a instalação de referência; cobertos por CT-20 e pelos CT-B |
| comparação do HTML inteiro com fonte Normal × sem escolha | HTML tem tokens variáveis por request; o helper identifica o tamanho pela declaração e CT-B02 mede o pixel |
| `canAccess()` da tela de Configurações para `admin` (positivo) | já coberto por `tests/Kit/ConfiguracoesDoKitTelaTest.php` e implícito em CT-20 |
| cenário separado para ADV-05 (outra pessoa antes) | mata o mesmo mutante que CT-10 reforçado; fundido nele |
| cenário separado para ADV-09 (gravar no /app, ler no /admin) | fundido no `Dado` de CT-09, que já lê nos três painéis |
| variante do ADV-20 com a chave da organização (Ana lê `/app/acme`, a administradora libera a Acme, Ana lê de novo) | R9 já está no teto (3) e R5 também; o mutante análogo ao M83 para a organização (liberação guardada por sessão) é o mesmo mecanismo de cache que M27/M83 já matam pelo lado da instalação e da sequência entre organizações; CT-18 afirma a página depois de a chave da Acme mudar pela tela. Risco residual declarado: cache da liberação por organização sem leitura anterior no mesmo processo |
