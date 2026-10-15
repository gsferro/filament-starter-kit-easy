# Casos de Teste — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas e `## Superfície de UI`) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **pendente** — gatilho Impacto 3 na área *Gravação sob adiamento e colapso*; a sessão despacha o `fw-adversario-ct` com `00` + `04` + `05` + `wikis/glossario.md`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Rótulo da aba e documentação (R1, R2) | 1 | 1 | 1 | mínimo |
| Estrutura das seções — o enforcement da rule (R3, R4, R5) | 2 | 1 | 2 | mínimo |
| Render adiado das abas e superfície do adiamento (R6, R7, R11, R12) | 2 | 2 | 4 | padrão |
| Gravação sob adiamento e colapso (R8, R9, R10) | 2 | 3 | 6 | padrão |

- Impacto 3 da gravação: salvar a tela com abas nunca carregadas pode regravar como vazio o segredo guardado (senha SMTP, `client_secret`, chave do anti-robô) ou desligar a proteção anti-robô em silêncio — perda de credencial que só volta redigitada e afrouxamento do login.
- Técnicas aplicadas: EP (varredura exaustiva de componentes como partição completa), estado exibido (as seis abas, sem amostragem), não-efeito com destinatário real (valores gravados diferentes do default), domínio condicionado (seção SMTP × remetente).
- Técnica escalada acima do perfil: R3 e R4 (área mínima) usam varredura exaustiva em vez de um exemplo — um campo amostrado não distingue "todos em seção" de "quase todos", e a rule (RQ-04) é sobre *todos*.
- Cenários: 15 · Regras: 12 · Mutantes previstos: 40 · Sem matador: 0
<!-- grep -c conforme references/template-04.md §Contagem do cabeçalho; os 4 CT-B estão no 05 -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a Page `ConfiguracoesDoKit` (schema: abas, seções, campos); docs pt/en; capturas `art/`; `.ai/rules/pages.md` (step 12). Sem model, migration, policy ou config novos | CT-01, CT-02, CT-03, CT-04, CT-06 |
| F | renomear o rótulo; agrupar todo campo em seção; seção colapsável com ícone; persistir o colapso (Q1); adiar o conteúdo das abas; gravar igual a antes | CT-01…CT-12 |
| D | todas as propriedades do grupo `kit` já gravadas, com valor diferente do default; segredos guardados; caminho de upload; campos de aba nunca carregada (ausentes no HTML, presentes no estado); estado colapsado no `localStorage` | CT-10, CT-11, CT-12, CT-15 |
| I | UI `GET /admin/configuracoes-da-aplicacao`; `$wire.loadDeferredSchema()`; `$wire.set('loadedDeferredSchemas')`; `?tab=` na URL; `localStorage`; docs | CT-13, CT-14, CT-B01, CT-B04 |
| P | navegador: `IntersectionObserver`, Alpine, `localStorage` (CT-B); ruído `ResizeObserver` do ColorPicker em Tabs no CI (rule); `--mutate` no Windows (rule) | CT-B01…CT-B04 |
| O | administrador com a permissão (único perfil que abre a tela — ancestral); links e favoritos antigos com a aba Kit (Q3); a tela "só cresce": aba ou campo novo sem seção/adiamento | CT-03, CT-06, CT-B04 |
| T | salvar antes de a aba carregar; erro que chega antes da aba; recarregar depois de colapsar; carregamento repetido da mesma aba | CT-09, CT-10, CT-13, CT-B02 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a aba ex-Kit chama "Aplicação", na mesma posição e com o mesmo ícone | rótulo (mínimo) | RQ-01, P-02 | EP (lista exaustiva das abas) | CT-01 |
| R2 — docs pt/en não citam mais a aba Kit; o CHANGELOG histórico fica | rótulo (mínimo) | P-01 | EP por arquivo + controle positivo | CT-02 |
| R3 — todo campo do formulário está dentro de uma seção | estrutura (mínimo) | RQ-02, RQ-04 | varredura exaustiva (escalada) | CT-03 |
| R4 — toda seção é colapsável e tem ícone, inclusive as que já existiam | estrutura (mínimo) | RQ-03, RQ-04 | varredura exaustiva (escalada) | CT-04 |
| R5 — toda seção guarda o estado colapsado por navegador `@pendente-Q1` | estrutura (mínimo) | RQ-03 (recorte Q1) | varredura exaustiva | CT-05, CT-B02 |
| R6 — o conteúdo de toda aba é adiado, com chave única, e carrega só a aba pedida | render adiado (padrão) | RQ-05, P-03 | estado exibido (seis abas) | CT-06, CT-07, CT-08, CT-B01, CT-B04 |
| R7 — aba não carregada com campo em erro carrega sozinha e mostra o erro | render adiado (padrão) | P-03 | EP + não-efeito | CT-09 |
| R8 — salvar sem nunca abrir as abas grava o alterado e preserva o resto | gravação (padrão) | P-04, RQ-05 | não-efeito com destinatário real, uma partição por aba | CT-10 |
| R9 — reagrupar em seções não muda o que a tela grava (seção condicional) | gravação (padrão) | RQ-02 (+ Fora de Escopo) | domínio condicionado | CT-11 |
| R10 — seção colapsada envia e valida os campos | gravação (padrão) | P-05 | EP | CT-12, CT-B03 |
| R11 — os pontos de entrada do adiamento não carregam o que não existe nem gravam | render adiado (padrão) | RQ-05 (`## Superfície Livewire` do `02`) | EP: domínio × fora do domínio × tipo errado | CT-13, CT-14 |
| R12 — carregar uma aba não entrega segredo guardado | render adiado (padrão) | RQ-05 (+ Fora de Escopo) | EP por segredo | CT-15 |

- RQ-01 — cobertura parcial: CT-01 (rótulo, posição, ícone); recorte "chave da aba na URL e links antigos com `kit`" aberto (Q3); o invariante das duas leituras (o link antigo ainda mostra conteúdo) é CT-B04
- RQ-02 — cobertura parcial: CT-03 (todo **campo** em seção), CT-11; recorte "itens que não são campo (textos, avisos)" aberto (Q4); recorte "qual campo em qual seção" aberto (Q2) — nenhum CT afirma o agrupamento
- RQ-03 — cobertura parcial: CT-04 (colapsável e com ícone); recorte "estado inicial e persistência" aberto (Q1). CT-05 e CT-B02 estão escritos sobre a recomendação de Q1, marcados `@pendente-Q1`, a pedido da sessão (ver `## Fronteira com o Plano`)
- RQ-04 — o enforcement mecânico da rule é CT-03 e CT-04 (a prova `arch()` que o step 12 cita); a existência do texto em `.ai/rules/` é do step 12, sem cenário
- RQ-05 — cobertura parcial: CT-06…CT-10, CT-13…CT-15, CT-B01, CT-B04; recorte "autorização no carregamento de aba para quem perdeu a permissão com a tela aberta" aberto (Q6)
- RQ-06, RQ-07, RQ-08, RQ-09 — atendidas no processo, sem cenário

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Estrutura da tela | R1, R3, R4, R5 (CT-05), R6 (CT-06) | componente Livewire/Filament | existente — a costura de `tests/Kit/ConfiguracoesDoKitTelaTest.php` (`livewire(ConfiguracoesDoKit::class)` + `usuarioDoKit('admin')`), em arquivo novo `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | o `Então` é sobre a árvore de componentes (`getFlatComponents(withHidden: true)`, `getLabel()`, `isCollapsible()`); nada disso pede navegador | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Render adiado | R6 (CT-07, CT-08), R7, R11, R12 | componente Livewire/Filament | existente — mesma costura, mesmo arquivo novo | o adiamento é decidido no servidor: o HTML do componente traz o marcador (`fi-sc-loading`) ou o campo; o pedido de carga é um método Livewire | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Gravação sob adiamento | R8, R9, R10 (CT-12) | componente Livewire/Filament | existente — mesma costura; leitura por `configuracaoGravada()`, arranjo por `gravarConfiguracao()` | gravação por componente é o par que a regra do par exige; num teste de componente nenhuma aba carrega sozinha — é o estado "nunca carregada" de graça | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Documentação | R2 | unit de regra | existente — `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` (leitura de `docs/` com `naArvoreDoKit()`) | é leitura de arquivo, sem banco nem tela; vive em `tests/Kit` por convenção do projeto | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Navegador | R5 (CT-B02), R6 (CT-B01, CT-B04), R10 (CT-B03) | browser | existente — a costura de `tests/Browser/ConfiguracoesDoKitTest.php` (`actingAs` + aquecimento pelo kernel no `beforeEach`), em arquivo novo `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` | `IntersectionObserver` + `$wire.loadDeferredSchema`, colapso Alpine e `localStorage` só existem no navegador (`## Superfície de UI`) | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| ícone de cada seção (`heroicon-o-identification`, `-swatch`…) | escolha de implementação; o requisito pede *ter* ícone | o `Então` afirma ícone preenchido, nunca qual |
| agrupamento campo → seção (`01` › *Seções por aba*) | Q2 aberta | nenhum CT afirma o agrupamento; os CT-B usam "a seção que contém o campo X" como seletor |
| ids `configuracoes-{aba}-{secao}` | escolha de implementação; só existem para a persistência (Q1) | CT-05 afirma id preenchido e único, nunca o valor; CT-B02 localiza a seção pelo campo |
| `Tab::key()` de cada aba e a chave `aplicacao::tabs` na URL (D1) | chave interna = implementação; chave na URL é visível ao usuário e o requisito não a determina (Q3) | CT-06 afirma só chave preenchida e distinta; URL sem CT |
| seção SMTP "visível só com SMTP" (D3) | desenho; a visibilidade dos campos SMTP é comportamento herdado que esta wiki não muda | CT-11 afirma só o invariante "com SMTP, os campos do servidor gravam" |
| "a seção abre" no erro (Superfície de UI) | P-05 aceita "a seção abre **ou** o erro aparece no topo" | CT-B03 afirma a mensagem de erro visível; a abertura é `@premissa` de mecanismo |
| estado inicial expandido e provedores colapsados; `persistCollapsed` | Q1 aberta | estado inicial sem CT (cortado, ver tabela); persistência escrita como `@pendente-Q1` por pedido explícito da sessão — **diverge do princípio 5 da skill** (cenário sobre suposição); se Q1 vier diferente, CT-05 e CT-B02 são re-derivados ou marcados `@obsoleto` |
| `heroicon-o-squares-2x2` (ícone da aba Kit na v0.49.0) | **aceito** como oráculo: P-02 diz "mantém o ícone de hoje", e o "hoje" é a tag v0.49.0 | CT-01, literal |
| composição da tela | o extrato do `01` entregue à derivação veio com a seção vazia; a tabela *Seções por aba* foi lida do próprio `01` › `## Mapeamentos` (mesmo conteúdo que o extrato deveria trazer) | desvio declarado; só nomes de campo e de seção usados como seletor/controle |

**Perguntas geradas pela derivação** (provisórias; a sessão renumera):

❓ Q4 · raia: requisito · afeta: RQ-02 (recorte: itens que não são campo) · depende de: —
"Todo item do formulário" inclui os itens que não são campo de entrada — textos explicativos e avisos, como o aviso de pacote ausente do dock — ou só os campos?
➡️ Recomendação: inclui — todo componente folha do formulário fica dentro de uma seção. É a leitura literal de "todos os itens", e um aviso solto entre seções é exatamente o "solto" que o pedido quer acabar; na rule (RQ-04), a versão mais estrita é a que não precisa de exceção. O invariante das duas leituras (todo **campo** em seção) já é CT-03.

❓ Q5 · raia: desenho · afeta: RQ-05, P-03, P-04 · depende de: —
Os CT de componente podem chamar `loadDeferredSchema()` e ler `$loadedDeferredSchemas` (ambos `@internal` em `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:loadDeferredSchema():138` e `:$loadedDeferredSchemas:55`) para carregar e conferir uma aba, ou devem provar tudo só pelo HTML?
➡️ Recomendação: chamar `loadDeferredSchema()` para carregar (é o que o JS do Filament chama) e usar o HTML como oráculo (marcador `fi-sc-loading` / campo-âncora); a propriedade só como apoio. Em patch do Filament que mude isso, o CT fica vermelho e aponta — custo aceitável.

❓ Q6 · raia: requisito · afeta: RQ-05 (recorte: autorização no carregamento) · depende de: —
Abrir uma aba agora é um pedido ao servidor. Quem perdeu a permissão da tela com ela aberta deve ser recusado ao abrir outra aba?
➡️ Recomendação: recusar — falha fechado, mesma fronteira que a gravação já tem (CT-33 da ancestral `settings-do-kit`). O invariante das duas leituras (o carregamento não entrega segredo guardado) já é CT-15.

## Setup Global

### Personas
- `admin` — `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` e `$this->actingAs(usuarioDoKit('admin'))`, como o arquivo-molde

### Painel
- `noPainelBootado('admin')` antes do primeiro `livewire(ConfiguracoesDoKit::class)`

### Fixtures
- `gravarConfiguracao($propriedade, $valor)` para o valor de partida; `configuracaoGravada($propriedade)` para o oráculo no banco (ambos em `tests/Pest.php`)
- "Valor diferente do default": o executor lê o default efetivo da propriedade e grava um valor distinto (booleano: o oposto). Sem isso o não-efeito não discrimina
- Sem ciclo de vida: a tela edita um único agregado de settings, sem estado

### Fakes
- nenhum. Upload não é exercitado: CT-10 grava o caminho da logo por `gravarConfiguracao()`

### Estratégia de DB
- `RefreshDatabase` da ligação de `tests/Kit` e `tests/Browser` em `tests/Pest.php`

### Convenções herdadas das rules
- asserção de ausência em texto: `assertStringNotContainsString($agulha, $html, $msg)` — nunca `not->toContain($x, $msg)` (`.ai/rules/testes.md`, variádica)
- caso que lê `docs/` ou o `CHANGELOG` com path interpolado leva `->skip(fn (): bool => ! naArvoreDoKit(), …)` (`.ai/rules/testes.md`)

---

## Regra R1 — a aba ex-Kit chama "Aplicação", na mesma posição e com o mesmo ícone

> `RQ-01`, `P-02` · perfil **mínimo** · técnica: **EP** (a lista inteira das abas como uma partição)

```gherkin
# language: pt
Funcionalidade: Tela de configurações com aba Aplicação, seções colapsáveis e abas adiadas

  Regra: a aba que se chamava Kit passa a se chamar Aplicação e só o rótulo muda

    Cenário: [CT-01] as abas mantêm a ordem e a última passa a se chamar Aplicação com o ícone de antes
      Dado o administrador na tela de configurações
      Quando o formulário da tela é montado
      Então os rótulos das abas, na ordem, são exatamente "Identidade", "E-mail", "Tabelas", "Registro", "Login", "Aplicação"
      E nenhuma aba tem o rótulo "Kit"
      E a aba "Aplicação" tem o ícone "heroicon-o-squares-2x2"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | troca só a chave/URL da aba; o rótulo continua "Kit" | CT-01 | 6º rótulo esperado "Aplicação"; o mutante dá "Kit" |
| M2 | cria a aba "Aplicação" e mantém a "Kit" (cópia ao lado) | CT-01 | lista exata de 6 rótulos sem "Kit"; o mutante tem 7 |
| M3 | rótulo sem acento ou no plural ("Aplicacao", "Aplicações") | CT-01 | comparação estrita com "Aplicação"; o mutante difere |
| M4 | move a aba renomeada para o início, como aba principal | CT-01 | ordem exata; o mutante põe "Aplicação" em 1º |
| M5 | troca o ícone junto com o nome | CT-01 | ícone `heroicon-o-squares-2x2`; o mutante dá outro |

---

## Regra R2 — a documentação deixa de citar a aba Kit, sem reescrever o histórico

> `P-01` · perfil **mínimo** · técnica: **EP por arquivo** (todo `.md` de `docs/` e os READMEs) + controle positivo

```gherkin
  Regra: toda menção à aba Kit nas docs vira aba Aplicação, e o CHANGELOG fica como estava

    Cenário: [CT-02] as docs pt e en citam a aba Aplicação e nenhuma cita mais a aba Kit
      Dado a árvore do kit com docs/pt, docs/en, README.md, README.en.md e CHANGELOG.md
      Quando a documentação é varrida
      Então nenhum arquivo de docs/ nem README contém a aba pelo nome antigo ("aba Kit", "aba **Kit**", "Kit tab", "**Kit** tab", a linha de tabela "| **Kit** |")
      E docs/pt/recursos/configuracoes-do-kit.md e docs/en/recursos/configuracoes-do-kit.md contêm "**Aplicação**"
      E o CHANGELOG.md continua com ao menos as 8 menções à aba Kit que tinha na v0.49.0
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | atualiza as docs pt e esquece as en | CT-02 | `docs/en/…` ainda contém "**Kit** tab"; ausência falha |
| M7 | atualiza só `configuracoes-do-kit.md`; `dock-de-atalhos`, `multi-tenancy` e `atualizando-o-projeto` ficam | CT-02 | varredura de `docs/**` acha "aba **Kit**" nesses arquivos |
| M8 | troca global por `sed`, que reescreve também o CHANGELOG histórico | CT-02 | contagem no CHANGELOG < 8; o mutante dá 0 (ou só a entrada nova) |
| M9 | troca "Kit" por outro nome ("Aplicativo", "Geral") | CT-02 | controle positivo "**Aplicação**" nas duas páginas de configurações; o mutante não tem |

---

## Regra R3 — todo campo do formulário está dentro de uma seção

> `RQ-02`, `RQ-04` · perfil **mínimo** · técnica: **varredura exaustiva** (escalada: amostrar um campo não prova "todos")

```gherkin
  Regra: nenhum campo fica solto numa aba, fora de seção

    Cenário: [CT-03] todo campo, visível ou oculto e de qualquer aba, tem uma seção como contêiner antes da aba
      Dado o remetente de e-mail gravado como "log", o que esconde os campos do servidor SMTP
      Quando o formulário da tela é montado para o administrador
      Então todo campo do formulário, inclusive os ocultos, tem uma seção como primeiro contêiner acima dele, antes de chegar à aba
      E a varredura encontrou o campo-âncora de cada aba: "nome_da_aplicacao", "mail_host", "paginacao_padrao", "registro_habilitado", "login_rodape", "hub_de_navegacao"
```

> Mecanismo: `instance()->form->getFlatComponents(withHidden: true)`, filtrando `Filament\Forms\Components\Field`, subindo pelo contêiner pai até achar `Section` ou `Tab`. A falha lista os campos soltos. O segundo `E` é o controle positivo: sem ele, uma varredura que não alcança aba adiada ou campo oculto passa vazia.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | um campo fica direto na aba, fora das seções (o caso típico de campo acrescentado depois numa tela que cresce) | CT-03 | lista de campos soltos esperada vazia; o mutante a deixa com o campo |
| M11 | campos agrupados em `Grid`/`Fieldset`/`Group` em vez de `Section` | CT-03 | o primeiro contêiner acima do campo é `Grid`, não `Section`; o campo entra na lista de soltos |
| M12 | campos SMTP deixados fora da seção, com a visibilidade própria que tinham | CT-03 | com remetente "log", `mail_host` aparece na varredura com ocultos e entra na lista de soltos |

---

## Regra R4 — toda seção é colapsável e tem ícone, inclusive as que já existiam

> `RQ-03`, `RQ-04` · perfil **mínimo** · técnica: **varredura exaustiva** (escalada)

```gherkin
  Regra: toda seção da tela, nova ou existente, em qualquer nível, é colapsável e tem ícone

    Cenário: [CT-04] toda seção do formulário é colapsável e tem ícone, em qualquer estado dos provedores
      Dado o remetente de e-mail gravado como "log"
      E o login com Google habilitado e o login com GitHub desabilitado
      Quando o formulário da tela é montado para o administrador
      Então toda seção do formulário, visível ou oculta e em qualquer nível, é colapsável e tem ícone preenchido
      E entre as seções varridas estão as que já existiam ("Cabeçalho dos painéis", "Página única de login", "Login social", "Proteção anti-robô", "Rodapé da tela de login", "Dashboard dinâmico", "Dock de atalhos"), as quatro de provedor e a seção que contém "mail_host"
```

> Mecanismo: `getFlatComponents(withHidden: true)` filtrando `Filament\Schemas\Components\Section`; `isCollapsible()` e `filled(getIcon())`. A falha lista os títulos (`getHeading()`) que reprovam.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M13 | só as seções novas ganham ícone e colapso; as existentes ("Cabeçalho dos painéis", "Dashboard dinâmico", "Dock de atalhos") ficam como estavam | CT-04 | essas seções entram na lista de reprovadas (`getIcon()` nulo ou `isCollapsible()` falso) |
| M14 | `->icon()` posto e `->collapsible()` esquecido | CT-04 | `isCollapsible()` falso em alguma seção |
| M15 | o ícone de status do provedor só existe quando ele está habilitado (nulo quando desabilitado) | CT-04 | seção do GitHub (desabilitado) com `getIcon()` nulo |
| M16 | a seção SMTP condicional fica sem ícone (escapa de quem confere só o visível) | CT-04 | com remetente "log", a seção que contém `mail_host` está na varredura e reprova |

---

## Regra R5 — toda seção guarda o estado colapsado por navegador `@pendente-Q1`

> `RQ-03` (recorte de Q1, escrito sobre a recomendação implementada, a pedido da sessão) · perfil **mínimo** · técnica: **varredura exaustiva**

```gherkin
  Regra: o estado colapsado de cada seção é guardado por seção, sem colisão entre seções

    @pendente-Q1
    Cenário: [CT-05] toda seção tem identificador próprio e guarda o estado colapsado
      Dado o remetente de e-mail gravado como "log"
      Quando o formulário da tela é montado para o administrador
      Então toda seção do formulário, visível ou oculta, tem identificador preenchido
      E nenhum identificador se repete entre as seções
      E toda seção guarda o estado colapsado entre visitas
```

> Mecanismo: `getId()`, unicidade sobre a lista de ids, `shouldPersistCollapsed()`. Par no navegador: CT-B02.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | seção nova sem `id()` — o estado colapsado não tem chave estável | CT-05 | `getId()` vazio em alguma seção |
| M18 | id copiado e colado em duas seções — colapsar uma colapsa a outra na próxima visita | CT-05, CT-B02 | lista de ids com repetido; no navegador, a seção de controle volta colapsada |
| M19 | `persistCollapsed()` esquecido nas seções novas | CT-05, CT-B02 | `shouldPersistCollapsed()` falso; no navegador, a seção volta expandida depois do recarregamento |

---

## Regra R6 — o conteúdo de toda aba é adiado, com chave única, e carrega só a aba pedida

> `RQ-05`, `P-03` · perfil **padrão** · técnica: **estado exibido** (as seis abas, sem amostragem)

```gherkin
  Regra: toda aba nasce sem conteúdo e carrega o seu, e só o seu, quando pedida

    Cenário: [CT-06] toda aba tem chave própria e conteúdo com carregamento adiado
      Dado o administrador na tela de configurações
      Quando o formulário da tela é montado
      Então o grupo de abas tem chave preenchida
      E toda aba tem chave preenchida, e nenhuma chave se repete entre as abas
      E o conteúdo de toda aba tem o carregamento adiado ligado
      E a varredura encontrou ao menos as seis abas de CT-01

    Cenário: [CT-07] a primeira renderização traz um marcador de carregamento por aba e nenhum campo
      Dado o administrador na tela de configurações
      Quando a tela é renderizada pela primeira vez, sem nenhuma aba carregada
      Então o HTML traz um marcador de carregamento ("fi-sc-loading" com aria-busy="true") para cada aba encontrada em CT-06
      E o HTML não traz o campo-âncora de nenhuma aba: "nome_da_aplicacao", "mail_from_address", "paginacao_padrao", "registro_habilitado", "login_rodape", "hub_de_navegacao"
      E o HTML traz os seis rótulos de aba

    Esquema do Cenário: [CT-08] carregar uma aba mostra os campos dela e de nenhuma outra
      Dado o administrador na tela de configurações, sem nenhuma aba carregada
      Quando a tela pede o carregamento da aba <aba>
      Então a tela re-renderizada mostra o campo "<ancora>"
      E continua sem o campo "<de_outra_aba>"

      Exemplos:
        | aba        | ancora              | de_outra_aba        |
        | Identidade | nome_da_aplicacao   | login_rodape        |
        | E-mail     | mail_from_address   | nome_da_aplicacao   |
        | Tabelas    | paginacao_padrao    | registro_habilitado |
        | Registro   | registro_habilitado | paginacao_padrao    |
        | Login      | login_rodape        | hub_de_navegacao    |
        | Aplicação  | hub_de_navegacao    | mail_from_address   |
```

> CT-07 é a prova de que a aba **ativa** também é adiada (P-03: carrega ao entrar na viewport); o carregamento por viewport e por clique é CT-B01. CT-08 carrega pela chave do schema da aba (`loadDeferredSchema()`, ver Q5) e afirma sobre o HTML re-renderizado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | adiamento ligado só em algumas abas (a renomeada ou a mais nova esquecida) | CT-06, CT-07 | `isLoadingDeferred()` falso na aba esquecida; o HTML inicial traz `hub_de_navegacao` e um marcador a menos |
| M21 | adiamento ligado no grupo de abas inteiro, como um bloco, e não por aba | CT-06, CT-08 | o schema de cada aba não está adiado; carregar "Tabelas" traz também `registro_habilitado` |
| M22 | duas abas com a mesma chave (cópia de `->key()`) | CT-06 | chaves com repetido (e o Filament lança `LogicException` no render de CT-07) |
| M23 | a chave pedida para carregar não casa com a chave da aba (chave no `Tab` e no schema divergentes) | CT-08, CT-B01 | depois do pedido, `<ancora>` não aparece; no navegador o campo nunca fica visível |
| M24 | só as abas inativas adiadas; a ativa renderizada de cara | CT-07 | o HTML inicial traz `nome_da_aplicacao`; a ausência falha |

---

## Regra R7 — aba não carregada com campo em erro carrega sozinha e mostra o erro

> `P-03` · perfil **padrão** · técnica: **EP** (campo inválido numa aba nunca carregada) + **não-efeito**

```gherkin
  Regra: o erro de validação num campo de aba não carregada traz aquela aba, e só ela

    Cenário: [CT-09] salvar com erro numa aba nunca carregada carrega essa aba com o erro e não grava nada
      Dado o nome da aplicação gravado "Antes" e a paginação padrão gravada 25
      E nenhuma aba carregada
      Quando o administrador salva com o nome "Depois" e a paginação padrão 0
      Então o campo "paginacao_padrao" tem o erro de mínimo
      E a tela re-renderizada mostra o campo "paginacao_padrao" com a mensagem de erro, sem pedido de carregamento da aba Tabelas
      E a tela continua sem o campo "login_rodape" (a aba Login não carregou)
      E o banco continua com o nome "Antes" e a paginação 25
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | adiamento por mecanismo que não consulta os erros (componente Livewire `lazy` por aba, ou visibilidade própria) | CT-09 | o HTML depois do salvamento não traz `paginacao_padrao` nem a mensagem |
| M26 | o erro força o carregamento de todas as abas | CT-09 | `login_rodape` aparece no HTML; a ausência falha |
| M27 | grava a parte válida antes de validar a aba não carregada | CT-09 | o banco tem nome "Depois"; o esperado é "Antes" |

---

## Regra R8 — salvar sem nunca abrir as abas grava o alterado e preserva o resto

> `P-04`, `RQ-05` · perfil **padrão** · técnica: **não-efeito com destinatário real** — um valor gravado, diferente do default, em cada aba

```gherkin
  Regra: o adiamento é só do HTML; o salvamento vê o formulário inteiro

    Cenário: [CT-10] alterar um campo de aba nunca carregada e salvar grava o campo e preserva todos os outros valores
      Dado gravados, diferentes do default: nome "Projeto Gravado" e logo "logos/marca.png" (Identidade), remetente "Equipe Gravada" (E-mail), paginação 50 (Tabelas), cadastro sem convite com o oposto do default (Registro), hub de navegação com o oposto do default e painéis do dock ["admin"] (Aplicação)
      E nenhuma aba carregada
      Quando o administrador altera só o recado do login (aba Login) para "Recado novo" e salva
      Então o banco tem o recado do login "Recado novo", sem erro de formulário
      E toda outra propriedade do grupo kit tem no banco o mesmo payload de antes do salvamento
      E a tela depois do salvamento ainda traz o marcador de carregamento da aba Login
```

> Gate de tela de escrita: é a gravação por componente com a aba do campo **adiada e nunca carregada** (P-04). O último `E` impede que o cenário passe por ter carregado a aba sem querer. O segundo `Então` compara o snapshot de todas as linhas do grupo `kit` (exceto `login_rodape`) antes e depois — o executor roda o caso também na tag v0.49.0 (sem adiamento) e ele precisa ficar verde lá: se não ficar, alguma normalização legítima do salvamento entrou no snapshot e a linha sai dele com o motivo escrito.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | o estado de cada aba é preenchido só quando ela carrega | CT-10 | as linhas de Identidade, E-mail, Tabelas, Registro e Aplicação mudam de payload (vão ao default ou a nulo) |
| M29 | `save()` desidrata só os schemas carregados | CT-10 | payloads das outras abas diferentes dos de antes; ou `login_rodape` não gravado |
| M30 | alteração feita em aba nunca renderizada é descartada no salvamento | CT-10 | `login_rodape` continua o anterior; esperado "Recado novo" |
| M31 | o estado do upload (logo) de aba não carregada vira vazio no salvamento | CT-10 | a linha `logo` deixa de ser "logos/marca.png" |

---

## Regra R9 — reagrupar em seções não muda o que a tela grava

> `RQ-02` (+ `## Fora de Escopo`: "a feature reorganiza a tela, não o que ela governa") · perfil **padrão** · técnica: **domínio condicionado** (seção condicional × remetente)

```gherkin
  Regra: o campo que passou a morar numa seção condicional grava como antes

    Cenário: [CT-11] com o remetente SMTP, o servidor SMTP é editável e grava
      Dado o remetente de e-mail gravado "smtp" e o servidor SMTP gravado "smtp.antigo.test"
      E nenhuma aba carregada
      Quando o administrador altera o servidor SMTP para "smtp.novo.test" e salva
      Então o banco tem o servidor SMTP "smtp.novo.test", sem erro de formulário
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M32 | a condição da seção lê o campo errado (`mailer` em vez de `mail_mailer`) e a seção fica sempre oculta | CT-11 | campo oculto não é desidratado; o banco continua "smtp.antigo.test" |
| M33 | a condição da seção invertida (oculta com SMTP) | CT-11 | idem: banco continua "smtp.antigo.test" |

---

## Regra R10 — seção colapsada envia e valida os campos

> `P-05` · perfil **padrão** · técnica: **EP** (seção que nasce colapsada; erro em seção colapsada no navegador — CT-B03)

```gherkin
  Regra: colapsar só esconde; o campo da seção colapsada continua no salvamento

    Cenário: [CT-12] o campo de uma seção que nasce colapsada grava ao salvar
      Dado a seção do provedor Google nascendo colapsada
      E o client_id do Google gravado "id-antigo"
      Quando o administrador altera o client_id do Google para "id-novo" e salva
      Então o banco tem o client_id do Google "id-novo", sem erro de formulário
```

> O primeiro `Dado` é conferido no arranjo (`isCollapsed()` verdadeiro na seção do Google): se Q1 tirar o colapso inicial dos provedores, o caso troca de seção, não de oráculo. Campo: `ProvedorSocial::Google->propriedadeDeSettings('client_id')`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | "nasce colapsada" implementado como oculta até abrir (`->hidden()`/`->visible()`) | CT-12 | campo oculto não é desidratado; banco continua "id-antigo" |
| M35 | campos de seção colapsada com `->dehydrated(false)` ("não enviar o que não se vê") | CT-12 | banco continua "id-antigo" |

---

## Regra R11 — os pontos de entrada do adiamento não carregam o que não existe nem gravam

> `RQ-05` (linhas `loadDeferredSchema()` e `$loadedDeferredSchemas` da `## Superfície Livewire` do `02`) · perfil **padrão** · técnica: **EP** — dentro do domínio, fora do domínio, tipo errado

```gherkin
  Regra: pedir o carregamento só carrega aba que existe, e nada do que o cliente pede grava

    Esquema do Cenário: [CT-13] o pedido de carregamento com argumento fora do domínio não carrega aba nenhuma
      Dado o nome da aplicação gravado "Antes" e nenhuma aba carregada
      Quando a tela recebe o pedido de carregamento com <argumento>
      Então <resultado>
      E o banco continua com o nome "Antes"

      Exemplos:
        | argumento                                     | resultado                                                       | # partição        |
        | a chave da aba Tabelas                        | só a aba Tabelas é marcada carregada e mostra "paginacao_padrao" | controle (válido) |
        | uma chave que não existe                      | nenhuma aba é marcada carregada                                 | fora do domínio   |
        | só a chave do grupo de abas, sem a da aba     | nenhuma aba é marcada carregada                                 | fora do domínio   |
        | a chave da aba Tabelas pela segunda vez       | a aba Tabelas continua a única marcada carregada                | repetição         |
        | uma lista no lugar do texto da chave          | a chamada falha com erro de tipo e nenhuma aba é marcada        | tipo errado       |

    Esquema do Cenário: [CT-14] escrever na lista de abas carregadas só antecipa a aba e nunca grava
      Dado o nome da aplicação gravado "Antes" e nenhuma aba carregada
      Quando o cliente escreve na lista de abas carregadas <valor>
      Então <resultado>
      E o banco continua com o nome "Antes"

      Exemplos:
        | valor                             | resultado                                                        | # partição      |
        | a chave da aba Login              | a tela re-renderizada mostra "login_rodape", como se a aba tivesse sido aberta | dentro do domínio |
        | uma chave que não existe          | a tela re-renderiza sem erro e sem nenhum campo de aba           | fora do domínio |
        | um texto no lugar da lista        | a escrita é recusada com erro de tipo                            | tipo errado     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M36 | a página sobrescreve o carregamento para "carregar tudo" quando a chave é o prefixo do grupo | CT-13 | linha "só a chave do grupo": abas marcadas ≠ vazio |
| M37 | a página aceita qualquer chave e a marca como carregada (sem o `skipPartialRender`) | CT-13 | linha "chave que não existe": a lista de carregadas ganha a chave |
| M38 | hook do kit ao marcar aba carregada (ex.: `updatedLoadedDeferredSchemas`) que salva ou normaliza o formulário | CT-14 | o banco deixa de ter "Antes" (ou ganha linha nova) |

---

## Regra R12 — carregar uma aba não entrega segredo guardado

> `RQ-05` (o carregamento adiado é caminho de render novo) + `## Fora de Escopo` (o que a tela governa não muda) · perfil **padrão** · técnica: **EP por segredo**

```gherkin
  Regra: o HTML de uma aba carregada sob demanda não carrega segredo guardado

    Esquema do Cenário: [CT-15] o HTML da aba carregada não contém o segredo guardado
      Dado o remetente de e-mail gravado "smtp"
      E gravados a senha SMTP "senha-smtp-gravada", o client_secret do Google "segredo-google-gravado" e a chave secreta do anti-robô "chave-antirobo-gravada"
      Quando a tela pede o carregamento da aba <aba>
      Então o HTML re-renderizado traz o campo "<campo>"
      E não contém "<segredo>"

      Exemplos:
        | aba    | campo                      | segredo                  |
        | E-mail | mail_password              | senha-smtp-gravada       |
        | Login  | client_secret do Google    | segredo-google-gravado   |
        | Login  | chave secreta do anti-robô | chave-antirobo-gravada   |
```

> O primeiro `Então` é o controle positivo (a aba carregou); sem ele a ausência passa com a aba ainda no marcador. A ancestral já prova o mesmo para a renderização completa; o caminho do carregamento parcial é novo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | a dica "segredo guardado" do campo (avaliada no render) passa a interpolar o valor guardado | CT-15 | linha Login/client_secret: o HTML contém "segredo-google-gravado" |
| M40 | o campo de senha é preenchido no estado quando a aba hidrata (`afterStateHydrated` lendo o segredo) | CT-15 | linha E-mail: o HTML contém "senha-smtp-gravada" |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a rota não recebe `{id}`; a tela edita um agregado único de settings | — |
| Autorização exercida na ação (não só `can()`) | lacuna declarada: recorte de RQ-05 dependente de Q6 (carregar aba depois de perder a permissão); a autorização da tela e da gravação não muda e é da ancestral (CT-13, CT-15, CT-33 de `settings-do-kit`), regressão do quality gate | Render adiado |
| Gate de camada da regra (autorização/validação por fora da UI) | não se aplica: a feature não cria regra de autorização nem de validação de domínio (`## Fora de Escopo` do `00`); a por-fora da ancestral é CT-33 de `settings-do-kit` | — |
| Idempotência | CT-13 (linha "segunda vez": pedir a mesma aba duas vezes não duplica nem regrava); a gravação repetida sem mudança é CT-23 da ancestral | Render adiado |
| Concorrência | não se aplica: sem contador, saldo ou limite; dois salvamentos simultâneos seguem o último-grava-vence pré-existente, fora de escopo | — |
| **Fronteira no ponto de entrada** (gravação) | CT-09 (valor abaixo do mínimo num campo de aba nunca carregada é recusado e nada grava); as demais bordas são da ancestral (CT-09 de `settings-do-kit`) | Render adiado |
| **Domínio condicionado** | CT-11 (seção SMTP × remetente) | Gravação sob adiamento |
| **Estado × operação de escrita** | não se aplica: sem entidade com ciclo de vida | — |
| Ausente ≠ null ≠ vazio | CT-10 (campo ausente do HTML por aba não carregada não é gravado como nulo/vazio) | Gravação sob adiamento |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: sem data/hora | — |
| Unicode / limite de varchar | CT-01 ("Aplicação" com acento, comparação estrita), CT-02; sem campo de texto novo | Estrutura da tela |
| Unicidade + soft delete | não se aplica: sem entidade removível | — |
| CRUD combinado | não se aplica: só edição de settings | — |
| Mass assignment | CT-14 (o cliente forja a lista de abas carregadas e nada grava); `$data` forjado é da ancestral (02: "já inventariada") | Render adiado |
| Upload | CT-10 (o caminho da logo de aba não carregada é preservado); envio de arquivo é da ancestral (CT-10 de `settings-do-kit`) | Gravação sob adiamento |
| Precisão monetária | não se aplica: sem dinheiro | — |
| **Superfície Livewire** — `loadDeferredSchema()` | CT-13 (fora do domínio e tipo errado) | Render adiado |
| **Superfície Livewire** — `$loadedDeferredSchemas` | CT-14 (fora do domínio e tipo errado) | Render adiado |
| **Superfície Livewire** — `$data` | não se aplica nesta wiki: inventariada e coberta na ancestral (CT-09, CT-33 de `settings-do-kit`); o `02` registra que esta feature não muda o que entra nem sai | — |
| **Superfície Livewire** — estado colapsado no `localStorage` | não se aplica: não chega ao servidor e só o próprio usuário o escreve no próprio navegador (`vendor/filament/schemas/src/Components/Section.php:$persist:364`, `:x-bind:class:375` — colapsar só troca classe, o campo continua no DOM e no salvamento) | — |
| **Superfície Livewire** — `?tab=` | CT-B04 (chave antiga e chave inexistente) | Navegador |
| **Estado do framework usado sem validar** | CT-13 (a chave pedida vira busca de schema), CT-B04 (`?tab=` vira aba ativa) | Render adiado |
| **IDOR por entidade** | não se aplica: a feature não persiste entidade nova | — |
| **Escopo com discriminante nulo** | não se aplica: sem filtro de escopo | — |
| **Saída do estado de erro** | CT-09 (o erro leva o usuário à aba do campo), CT-B03 (o erro em seção colapsada fica visível) | Render adiado |
| Cardinalidade do destinatário | não se aplica: sem efeito colateral com destinatário | — |
| Texto livre (acento, emoji, teto) | não se aplica: nenhum campo novo; os existentes são da ancestral | — |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| toda seção nasce expandida, salvo as quatro de provedor | recorte de Q1 (aberta); a sessão pediu só id/persistência sobre a recomendação |
| a aba Aplicação responde em `?tab=aplicacao::tabs`; `?tab=kit::tabs` cai na primeira aba | recorte de Q3 (aberta); o invariante das duas leituras é CT-B04 |
| item que não é campo (aviso, texto) dentro de seção | recorte de Q4 (aberta); o invariante (todo campo) é CT-03 |
| quem perdeu a permissão não carrega aba | recorte de Q6 (aberta); o invariante (sem segredo) é CT-15 |
| o texto da rule existe em `.ai/rules/pages.md` | processo do step 12 (`requirement-to-rule`); o enforcement é CT-03/CT-04 |
| as capturas `art/` mostram a aba Aplicação | imagem não tem oráculo barato; conferência visual da dimensão G do quality gate |
| ícone específico de cada seção | escolha de implementação (Fronteira com o Plano) |
| colapsar no navegador e salvar grava o campo alterado | colapsar só troca classe (citação do `02`); CT-12 mata os mutantes de "colapsada = oculta" mais barato |
| salvar duas vezes sem mudança | CT-23 da ancestral; o adiamento não cria escrita |
| tema escuro das seções | nenhuma RQ |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | as abas mantêm a ordem e a última chama Aplicação com o ícone de antes | R1 | EP | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M1–M5 |
| CT-02 | docs pt e en citam Aplicação, nenhuma cita Kit; CHANGELOG intacto | R2 | EP por arquivo | Documentação | unit de regra | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | M6–M9 |
| CT-03 | todo campo tem uma seção antes da aba | R3 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M10–M12 |
| CT-04 | toda seção é colapsável e tem ícone | R4 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M13–M16 |
| CT-05 | toda seção tem id próprio e guarda o colapso `@pendente-Q1` | R5 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M17–M19 |
| CT-06 | toda aba tem chave própria e conteúdo adiado | R6 | estado exibido | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M20–M22 |
| CT-07 | a primeira renderização tem um marcador por aba e nenhum campo | R6 | estado exibido | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M20, M24 |
| CT-08 | carregar uma aba mostra os campos dela e de nenhuma outra | R6 | estado exibido | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M21, M23 |
| CT-09 | erro em aba nunca carregada carrega a aba e não grava | R7 | EP + não-efeito | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M25–M27 |
| CT-10 | alterar campo de aba nunca carregada grava e preserva o resto | R8 | não-efeito com destinatário | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M28–M31 |
| CT-11 | com SMTP, o servidor SMTP grava | R9 | domínio condicionado | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M32, M33 |
| CT-12 | campo de seção que nasce colapsada grava | R10 | EP | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M34, M35 |
| CT-13 | pedido de carregamento fora do domínio não carrega aba | R11 | EP | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M36, M37 |
| CT-14 | lista de abas carregadas forjada não grava | R11 | EP | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M38 |
| CT-15 | aba carregada não entrega segredo guardado | R12 | EP por segredo | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M39, M40 |
| CT-B01…CT-B04 | ver `05-casos-de-teste-browser.md` | R5, R6, R10 | — | Navegador | browser | `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` | MB1–MB9 |

## Precedência e pós-implementação

- Rule venceu a skill: o template do `05` pede `assertNoJavaScriptErrors()`; `.ai/rules/testes-browser.md` (*ColorPicker dentro de Tabs*) proíbe na tela de configurações — os CT-B não usam a asserção, e os oráculos são de visibilidade.
- Mutation: `--path=app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, pelo `pestw.cmd`, sem `--filter`, com `--no-tia`; no Windows o score só vale com conferência manual por amostragem (aplicar M10, M20 e M28 por `sed`, rodar o CT previsto, `git checkout` no arquivo) — `.ai/rules/testes.md` (*UNTESTED do pest-plugin-mutate*). Os mutantes desta wiki são majoritariamente de **configuração do schema** (método encadeado removido), que o plugin muta como remoção de chamada.
