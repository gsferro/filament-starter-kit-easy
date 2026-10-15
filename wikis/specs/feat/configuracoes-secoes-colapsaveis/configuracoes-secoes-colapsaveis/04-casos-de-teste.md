# Casos de Teste — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas e `## Superfície de UI`) · ADR: `02-decisoes-arquiteturais.md` (só `## Superfície Livewire`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **rodada 1 feita** (`fw-adversario-ct`, 2026-10-15, 25 achados: 23 fechados, 2 rejeitados com motivo — ver `## Revisão adversarial — rodada 1`). O fechamento criou cenário novo (CT-16…CT-20, CT-B05): **rodada 2 devida**, uma única vez.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Rótulo da aba e documentação (R1, R2) | 1 | 1 | 1 | mínimo |
| Estrutura das seções — o enforcement da rule (R3, R4, R5, R13) | 2 | 1 | 2 | mínimo |
| Render adiado das abas e superfície do adiamento (R6, R7, R11, R12) | 2 | 2 | 4 | padrão |
| Gravação sob adiamento e colapso (R8, R9, R10) | 2 | 3 | 6 | padrão |

- Impacto 3 da gravação: salvar a tela com abas nunca carregadas pode regravar como vazio o segredo guardado (senha SMTP, `client_secret`, chave do anti-robô) ou desligar a proteção anti-robô em silêncio — perda de credencial que só volta redigitada e afrouxamento do login.
- Técnicas aplicadas: EP (varredura exaustiva de componentes como partição completa), estado exibido (as seis abas, sem amostragem), não-efeito com destinatário real (valores gravados diferentes do default, segredos incluídos), domínio condicionado (seção SMTP × remetente), 2-switch (edição pendente → carregar outra aba → salvar; carregar → carregar de novo).
- Técnica escalada acima do perfil: R3 e R4 (área mínima) usam varredura exaustiva em vez de um exemplo — um campo amostrado não distingue "todos em seção" de "quase todos", e a rule (RQ-04) é sobre *todos*.
- Estouro de teto: R10 tem 2 CT no `04` + 2 CT-B no `05` (teto padrão: 3 por regra, 1 CT-B) — o gate vence o teto: CT-17 e CT-B05 são os únicos matadores de M48/M49 e MB12/MB13 (achados ADV-04 e ADV-25).
- Cenários: 20 · Regras: 13 · Mutantes previstos: 54 · Sem matador: 0
<!-- grep -c conforme references/template-04.md §Contagem do cabeçalho; os 5 CT-B estão no 05 -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a Page `ConfiguracoesDoKit` (schema: abas, seções, campos); docs pt/en; capturas `art/`; `.ai/rules/pages.md` + `.ai/rules/index.md` (step 12). Sem model, migration, policy ou config novos | CT-01, CT-02, CT-03, CT-04, CT-06, CT-19 |
| F | renomear o rótulo; agrupar todo campo em seção; seção colapsável com ícone; persistir o colapso (Q1, recomendação autorizada); adiar o conteúdo das abas; gravar e validar igual a antes | CT-01…CT-18, CT-20 |
| D | todas as propriedades do grupo `kit` já gravadas, com valor diferente do default — inclusive os três segredos e o anti-robô ligado; caminho de upload; campos de aba nunca carregada (ausentes no HTML, presentes no estado); edição pendente não salva; estado colapsado no `localStorage` | CT-10, CT-11, CT-12, CT-15, CT-16, CT-17 |
| I | UI `GET /admin/configuracoes-da-aplicacao`; `$wire.loadDeferredSchema()`; `$wire.set('loadedDeferredSchemas')`; `?tab=` na URL; `localStorage`; docs e rules | CT-13, CT-14, CT-20, CT-B01, CT-B04 |
| P | navegador: `IntersectionObserver`, Alpine, `localStorage` (CT-B); ruído `ResizeObserver` do ColorPicker em Tabs no CI (rule); `--mutate` no Windows (rule) | CT-B01…CT-B05 |
| O | administrador com a permissão (único perfil que abre a tela — ancestral); links e favoritos antigos com a aba Kit (Q3); a tela "só cresce": aba ou campo novo sem seção/adiamento; quem edita e troca de aba antes de salvar | CT-03, CT-06, CT-16, CT-B04 |
| T | salvar antes de a aba carregar; erro que chega antes da aba; carregar outra aba com edição pendente; carregamento repetido da mesma aba; trocar o remetente sem salvar; recarregar depois de colapsar | CT-09, CT-10, CT-16, CT-17, CT-18, CT-20, CT-B02 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a aba ex-Kit chama "Aplicação", na mesma posição e com o mesmo ícone | rótulo (mínimo) | RQ-01, P-02 | EP (lista exaustiva das abas) | CT-01 |
| R2 — docs pt/en não citam mais a aba Kit; o CHANGELOG histórico fica | rótulo (mínimo) | P-01 | EP por arquivo + controle positivo por arquivo | CT-02 |
| R3 — todo campo do formulário está dentro de uma seção | estrutura (mínimo) | RQ-02, RQ-04 | varredura exaustiva (escalada) | CT-03 |
| R4 — toda seção é colapsável e tem ícone, inclusive as que já existiam | estrutura (mínimo) | RQ-03, RQ-04 | varredura exaustiva (escalada) | CT-04 |
| R5 — toda seção guarda o estado colapsado por navegador `@pendente-Q1` | estrutura (mínimo) | RQ-03 (recorte Q1, recomendação autorizada) | varredura exaustiva | CT-05, CT-B02 |
| R6 — o conteúdo de toda aba é adiado, com chave única, e carrega só a aba pedida | render adiado (padrão) | RQ-05, P-03 | estado exibido (seis abas) | CT-06, CT-07, CT-08, CT-B01, CT-B04 |
| R7 — aba não carregada com campo em erro carrega sozinha e mostra o erro | render adiado (padrão) | P-03 | EP + não-efeito | CT-09 |
| R8 — salvar sem nunca abrir as abas grava o alterado e preserva o resto; carregar uma aba não apaga a edição pendente | gravação (padrão) | P-04, RQ-05 | não-efeito com destinatário real + 2-switch | CT-10, CT-16 |
| R9 — reagrupar em seções não muda o que a tela grava nem quando a seção condicional aparece | gravação (padrão) | RQ-02 (+ Fora de Escopo) | domínio condicionado | CT-11, CT-18 |
| R10 — seção colapsada envia e valida os campos, e o erro nela fica visível | gravação (padrão) | P-05, P-03 | EP | CT-12, CT-17, CT-B03, CT-B05 |
| R11 — os pontos de entrada do adiamento não carregam o que não existe, não repetem a carga e não gravam | render adiado (padrão) | RQ-05 (`## Superfície Livewire` do `02`) | EP: domínio × fora do domínio × tipo errado; 2-switch | CT-13, CT-14, CT-20 |
| R12 — carregar uma aba não entrega segredo guardado | render adiado (padrão) | RQ-05 (+ Fora de Escopo) | EP por segredo | CT-15 |
| R13 — a convenção "seção com ícone e colapsável" está escrita numa rule indexada para a Page | estrutura (mínimo) | RQ-04 | EP (existe/indexada) | CT-19 |

- RQ-01 — cobertura parcial: CT-01 (rótulo, posição, ícone); recorte "chave da aba na URL e links antigos com `kit`" aberto (Q3); o invariante das duas leituras (o link antigo ainda mostra conteúdo) e a partição válida da URL são CT-B04
- RQ-02 — cobertura parcial: CT-03 (todo **campo** em seção), CT-11, CT-18; recorte "itens que não são campo (textos, avisos)" aberto (Q4); recorte "qual campo em qual seção" aberto (Q2) — nenhum CT afirma o agrupamento
- RQ-03 — cobertura parcial: CT-04 (colapsável e com ícone); recorte de Q1 implementado pela recomendação autorizada pelo solicitante ("implemente tudo", 2026-10-15), cenários `@pendente-Q1`: CT-05, CT-12, CT-B02. Resposta contrária a Q1 vira Adendo e re-derivação
- RQ-04 — CT-19 (a rule existe e o índice a liga à Page); o enforcement mecânico dela é CT-03 e CT-04 (a prova `arch()` que o step 12 cita). CT-19 fica vermelho até o step 12 — classificação esperada: implementação ainda não feita
- RQ-05 — cobertura parcial: CT-06…CT-10, CT-13…CT-17, CT-20, CT-B01, CT-B04, CT-B05; recorte "autorização no carregamento de aba para quem perdeu a permissão com a tela aberta" aberto (Q6)
- RQ-06, RQ-07, RQ-08, RQ-09 — atendidas no processo, sem cenário

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Estrutura da tela | R1, R3, R4, R5 (CT-05), R6 (CT-06) | componente Livewire/Filament | existente — a costura de `tests/Kit/ConfiguracoesDoKitTelaTest.php` (`livewire(ConfiguracoesDoKit::class)` + `usuarioDoKit('admin')`), em arquivo novo `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | o `Então` é sobre a árvore de componentes (`getFlatComponents(withHidden: true)`, `getLabel()`, `isCollapsible()`); nada disso pede navegador | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Render adiado | R6 (CT-07, CT-08), R7, R11, R12 | componente Livewire/Filament | existente — mesma costura, mesmo arquivo novo | o adiamento é decidido no servidor: o HTML do componente traz o marcador (`fi-sc-loading`) ou o campo; o pedido de carga é um método Livewire | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Gravação sob adiamento | R8, R9, R10 (CT-12, CT-17) | componente Livewire/Filament | existente — mesma costura; leitura por `configuracaoGravada()` e pela classe de settings (segredos decifrados), arranjo por `gravarConfiguracao()` | gravação por componente é o par que a regra do par exige; num teste de componente nenhuma aba carrega sozinha — é o estado "nunca carregada" de graça | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Documentação | R2, R13 | unit de regra | existente — `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` (leitura de `docs/` com `naArvoreDoKit()`) | é leitura de arquivo, sem banco nem tela; vive em `tests/Kit` por convenção do projeto | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |
| Navegador | R5 (CT-B02), R6 (CT-B01, CT-B04), R10 (CT-B03, CT-B05) | browser | existente — a costura de `tests/Browser/ConfiguracoesDoKitTest.php` (`actingAs` + aquecimento pelo kernel no `beforeEach`), em arquivo novo `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` | `IntersectionObserver` + `$wire.loadDeferredSchema`, colapso Alpine e `localStorage` só existem no navegador (`## Superfície de UI`) | sim — convenção existente (molde `ConfiguracoesDoKitTelaTest`/`ConfiguracoesDoKitTest`), sessão, 2026-10-15 |

## Fronteira com o Plano

| Item do PRD/ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| ícone de cada seção (`heroicon-o-identification`, `-swatch`…) | escolha de implementação; o requisito pede *ter* ícone | o `Então` afirma ícone preenchido, nunca qual |
| agrupamento campo → seção (`01` › *Seções por aba*) | Q2 aberta | nenhum CT afirma o agrupamento; os CT-B usam "a seção que contém o campo X" como seletor |
| ids `configuracoes-{aba}-{secao}` | escolha de implementação; só existem para a persistência (Q1) | CT-05 afirma id explícito preenchido e único, nunca o valor; CT-B02 localiza a seção pelo campo |
| `Tab::key()` de cada aba e a chave `aplicacao::tabs` na URL (D1) | chave interna = implementação; chave na URL da aba renomeada é visível ao usuário e o requisito não a determina (Q3) | CT-06 afirma só chave preenchida e distinta; CT-B04 usa só a chave da aba Tabelas (inalterada, fato do framework: slug do rótulo + `::tabs`) como partição válida |
| seção SMTP "visível só com SMTP" (D3) | desenho; a visibilidade dos campos SMTP é comportamento herdado que esta wiki não muda | CT-11 e CT-18 afirmam só o invariante herdado: com SMTP os campos do servidor aparecem e gravam; com outro remetente, não aparecem |
| "a seção abre" no erro (Superfície de UI) | P-05 aceita "a seção abre **ou** o erro aparece no topo" | CT-B03 e CT-B05 afirmam a mensagem de erro visível; a abertura é `@premissa` de mecanismo |
| estado inicial expandido e provedores colapsados; `persistCollapsed` | Q1 aberta no `00`, recomendação **autorizada** pelo solicitante e implementada (decisão da sessão sobre ADV-21/ADV-23) | CT-05, CT-12 e CT-B02 vigentes, marcados `@pendente-Q1`; o estado inicial "tudo expandido" segue sem CT próprio (cortado) |
| `heroicon-o-squares-2x2` (ícone da aba Kit na v0.49.0) | **aceito** como oráculo: P-02 diz "mantém o ícone de hoje", e o "hoje" é a tag v0.49.0 | CT-01, literal |
| 8 menções à aba Kit no CHANGELOG da v0.49.0 (6 "aba Kit", 2 "aba **Kit**") | **aceito** como oráculo: P-01 diz "o CHANGELOG histórico não é reescrito"; o histórico é a v0.49.0 (contagem da sessão) | CT-02, literal |
| composição da tela | o extrato do `01` entregue à derivação veio com a seção vazia; a tabela *Seções por aba* foi lida do próprio `01` › `## Mapeamentos` | desvio declarado; só nomes de campo e de seção usados como seletor/controle |

**Perguntas geradas pela derivação** (renumeradas pela sessão):

❓ Q4 · raia: requisito · afeta: RQ-02 (recorte: itens que não são campo) · depende de: —
"Todo item do formulário" inclui os itens que não são campo de entrada — textos explicativos e avisos, como o aviso de pacote ausente do dock — ou só os campos?
➡️ Recomendação: inclui — todo componente folha do formulário fica dentro de uma seção. É a leitura literal de "todos os itens", e um aviso solto entre seções é exatamente o "solto" que o pedido quer acabar; na rule (RQ-04), a versão mais estrita é a que não precisa de exceção. O invariante das duas leituras (todo **campo** em seção) já é CT-03.

❓ Q5 · raia: desenho · afeta: RQ-05, P-03, P-04 · depende de: — · **decidida: D7 do `01`**
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
- Segredos (`encrypted()` na classe de settings): o arranjo e o oráculo usam o **valor decifrado** lido pela classe `App\Settings\ConfiguracoesDoKit` (`app()->forgetInstance()` antes de ler), nunca o payload cru — o payload cifrado muda a cada gravação mesmo com o valor igual
- "Valor diferente do default": o executor lê o default efetivo da propriedade e grava um valor distinto (booleano: o oposto). Sem isso o não-efeito não discrimina
- Nomes dos campos de provedor: `ProvedorSocial::Google->propriedadeDeSettings('client_id' | 'client_secret' | 'habilitado')` (`login_google_client_id`…)
- Sem ciclo de vida: a tela edita um único agregado de settings, sem estado

### Fakes
- nenhum. Upload não é exercitado: CT-10 grava o caminho da logo por `gravarConfiguracao()`

### Estratégia de DB
- `RefreshDatabase` da ligação de `tests/Kit` e `tests/Browser` em `tests/Pest.php`

### Convenções herdadas das rules
- asserção de ausência em texto: `assertStringNotContainsString($agulha, $html, $msg)` — nunca `not->toContain($x, $msg)` (`.ai/rules/testes.md`, variádica)
- caso que lê `docs/`, `CHANGELOG.md` ou `.ai/rules/` leva `->skip(fn (): bool => ! naArvoreDoKit(), …)` (`.ai/rules/testes.md`)

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

> `P-01` · perfil **mínimo** · técnica: **EP por arquivo** (todo `.md` de `docs/` e os READMEs) + **controle positivo por arquivo**

```gherkin
  Regra: toda menção à aba Kit nas docs vira aba Aplicação, e o CHANGELOG histórico fica como estava

    Cenário: [CT-02] cada doc que citava a aba Kit passa a citar a aba Aplicação, e o histórico do CHANGELOG não muda
      Dado a árvore do kit com docs/pt, docs/en, README.md, README.en.md e CHANGELOG.md
      Quando a documentação é varrida
      Então nenhum arquivo de docs/ nem README contém a aba pelo nome antigo ("aba Kit", "aba **Kit**", "Kit tab", "**Kit** tab", a linha de tabela "| **Kit** |")
      E cada um dos oito arquivos que citavam a aba Kit na v0.49.0 contém "Aplicação": docs/pt e docs/en de recursos/configuracoes-do-kit.md, recursos/dock-de-atalhos.md, recursos/multi-tenancy.md e comecar/atualizando-o-projeto.md
      E a parte do CHANGELOG.md abaixo do cabeçalho "## [0.49.0]" tem exatamente 8 menções à aba pelo nome antigo: 6 "aba Kit" e 2 "aba **Kit**"
```

> A parte acima de `## [0.49.0]` (Unreleased e releases seguintes) fica livre para citar o nome antigo — é onde a própria entrega registra a troca. Contagem de referência feita pela sessão na tag v0.49.0.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | atualiza as docs pt e esquece as en | CT-02 | `docs/en/…` ainda contém "**Kit** tab"; ausência falha |
| M7 | atualiza só `configuracoes-do-kit.md`; `dock-de-atalhos`, `multi-tenancy` e `atualizando-o-projeto` ficam | CT-02 | varredura de `docs/**` acha "aba **Kit**" nesses arquivos; e esses arquivos não contêm "Aplicação" |
| M8 | troca global por `sed`, que reescreve também o CHANGELOG histórico (inteiro ou em parte, compensado por menções novas) | CT-02 | contagem abaixo de `## [0.49.0]` ≠ 8 (6 + 2); o mutante dá menos |
| M9 | troca "Kit" por outro nome ("Aplicativo", "Geral") | CT-02 | controle positivo "Aplicação" em cada um dos oito arquivos; o mutante não tem |
| M41 | apaga a frase que citava a aba Kit num arquivo em vez de reescrevê-la (o arquivo fica sem citar aba nenhuma) | CT-02 | o arquivo da frase apagada não contém "Aplicação"; o controle positivo falha |

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

> Mecanismo: `instance()->form->getFlatComponents(withHidden: true)`, filtrando `Filament\Forms\Components\Field`, subindo pelo contêiner pai até achar `Section` ou `Tab`. A falha lista os campos soltos. O segundo `E` é o controle positivo: sem ele, uma varredura que não alcança aba adiada ou campo oculto passa vazia. Campo fora das `Tabs` (contêiner raiz sem aba acima) também é solto, a não ser que tenha seção acima.

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

> `RQ-03` (recorte de Q1: recomendação autorizada pelo solicitante e implementada; Q1 segue aberta no `00`) · perfil **mínimo** · técnica: **varredura exaustiva**

```gherkin
  Regra: o estado colapsado de cada seção é guardado sob um identificador explícito, sem colisão entre seções

    @pendente-Q1
    Cenário: [CT-05] toda seção tem identificador explícito próprio e guarda o estado colapsado
      Dado o remetente de e-mail gravado como "log"
      Quando o formulário da tela é montado para o administrador
      Então toda seção do formulário, visível ou oculta, tem identificador explícito preenchido
      E nenhum identificador explícito se repete entre as seções
      E toda seção guarda o estado colapsado entre visitas
```

> Mecanismo: `getCustomId()` (público em `vendor/filament/schemas/src/Components/Concerns/HasId.php:getCustomId():23`) — **não** `getId()`, que cai em `getKey()` quando não há `id()` (`HasId.php:getId():18`) e nunca fica nulo; unicidade sobre a lista; `shouldPersistCollapsed()`. Par no navegador: CT-B02.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | seção nova sem `id()` — o estado colapsado fica sob a chave derivada, que muda quando o schema muda | CT-05 | `getCustomId()` nulo em alguma seção (o `getId()` dela daria a chave e passaria) |
| M18 | id copiado e colado em duas seções — colapsar uma colapsa a outra na próxima visita | CT-05, CT-B02 | lista de ids explícitos com repetido; no navegador, a seção de controle volta colapsada |
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
      E o HTML não traz nenhum id de campo do formulário (nenhuma ocorrência de 'id="form.')
      E o HTML traz os seis rótulos de aba

    Esquema do Cenário: [CT-08] carregar uma aba mostra os campos dela e de nenhuma outra
      Dado o administrador na tela de configurações, sem nenhuma aba carregada
      Quando a tela pede o carregamento da aba <aba>
      Então a tela re-renderizada mostra o campo "<ancora>"
      E não mostra o campo-âncora de nenhuma das outras cinco abas (entre "nome_da_aplicacao", "mail_from_address", "paginacao_padrao", "registro_habilitado", "login_rodape", "hub_de_navegacao")

      Exemplos:
        | aba        | ancora              |
        | Identidade | nome_da_aplicacao   |
        | E-mail     | mail_from_address   |
        | Tabelas    | paginacao_padrao    |
        | Registro   | registro_habilitado |
        | Login      | login_rodape        |
        | Aplicação  | hub_de_navegacao    |
```

> CT-07 é a prova de que a aba **ativa** também é adiada (P-03: carrega ao entrar na viewport) e de que nenhum campo escapa do adiamento — inclusive campo ou seção posta fora das `Tabs`, que renderizaria de cara; o carregamento por viewport e por clique é CT-B01. CT-08 carrega pela chave do schema da aba (`loadDeferredSchema()`, D7) e afirma sobre o HTML re-renderizado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | adiamento ligado só em algumas abas (a renomeada ou a mais nova esquecida) | CT-06, CT-07 | `isLoadingDeferred()` falso na aba esquecida; o HTML inicial traz `id="form.hub_de_navegacao"` e um marcador a menos |
| M21 | adiamento ligado no grupo de abas inteiro, como um bloco, e não por aba | CT-06, CT-08 | o schema de cada aba não está adiado; carregar "Tabelas" traz também as âncoras das outras cinco |
| M22 | duas abas com a mesma chave (cópia de `->key()`) | CT-06 | chaves com repetido (e o Filament lança `LogicException` no render de CT-07) |
| M23 | a chave pedida para carregar não casa com a chave da aba (chave no `Tab` e no schema divergentes) | CT-08, CT-B01 | depois do pedido, `<ancora>` não aparece; no navegador o campo nunca fica visível |
| M24 | só as abas inativas adiadas; a ativa renderizada de cara | CT-07 | o HTML inicial traz `id="form.nome_da_aplicacao"`; a ausência falha |
| M42 | uma seção (ou campo) posta fora das `Tabs`, no topo do formulário, renderizando sem adiamento | CT-07 | o HTML inicial traz um `id="form.` do campo dessa seção |

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

## Regra R8 — salvar sem nunca abrir as abas grava o alterado e preserva o resto; carregar uma aba não apaga a edição pendente

> `P-04`, `RQ-05` · perfil **padrão** · técnica: **não-efeito com destinatário real** — um valor gravado, diferente do default, em cada aba, segredos incluídos — e **2-switch** (editar A → carregar B → salvar)

```gherkin
  Regra: o adiamento é só do HTML; o salvamento vê o formulário inteiro, com o que o usuário editou

    Cenário: [CT-10] alterar um campo de aba nunca carregada e salvar grava o campo e preserva todos os outros valores, segredos incluídos
      Dado gravados, diferentes do default: nome "Projeto Gravado" e logo "logos/marca.png" (Identidade); remetente "Equipe Gravada" e a senha SMTP "senha-smtp-gravada" (E-mail); paginação 50 (Tabelas); cadastro sem convite com o oposto do default (Registro); o login com Google habilitado, com client_id "id-gravado" e client_secret "segredo-google-gravado", e o anti-robô ligado, com a chave secreta "chave-antirobo-gravada" (Login); hub de navegação com o oposto do default e painéis do dock ["admin"] (Aplicação)
      E nenhuma aba carregada
      Quando o administrador altera só o recado do login (aba Login) para "Recado novo" e salva
      Então o banco tem o recado do login "Recado novo", sem erro de formulário
      E toda outra propriedade da classe de settings do kit tem o mesmo valor de antes do salvamento — os segredos comparados decifrados: "senha-smtp-gravada", "segredo-google-gravado", "chave-antirobo-gravada"; o anti-robô e o login com Google continuam ligados
      E a tela depois do salvamento ainda traz o marcador de carregamento da aba Login

    Cenário: [CT-16] editar uma aba, carregar outra e salvar grava a edição feita antes da carga
      Dado o nome da aplicação gravado "Antes"
      E o administrador alterou o nome da aplicação para "Editado" sem salvar
      Quando a tela carrega a aba Tabelas e o administrador salva
      Então o banco tem o nome da aplicação "Editado", sem erro de formulário
      E a tela re-renderizada mostra o campo "paginacao_padrao" (a aba Tabelas carregou)
```

> CT-10 é o gate de tela de escrita: gravação por componente com a aba do campo **adiada e nunca carregada** (P-04). O último `E` impede que o cenário passe por ter carregado a aba sem querer. O Google habilitado e o anti-robô ligado têm as credenciais gravadas para o formulário salvar sem erro. O executor roda CT-10 também na tag v0.49.0 (sem adiamento) e ele precisa ficar verde lá: se não ficar, alguma normalização legítima do salvamento entrou na comparação e a propriedade sai dela com o motivo escrito. CT-16: o `Quando` são duas ações porque o defeito mora na sequência (carregar com edição pendente); separadas, nenhuma das duas produz o estado afirmado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | o estado de cada aba é preenchido só quando ela carrega | CT-10 | as propriedades de Identidade, E-mail, Tabelas, Registro e Aplicação mudam de valor (vão ao default ou a nulo) |
| M29 | `save()` desidrata só os schemas carregados | CT-10 | valores das outras abas diferentes dos de antes; ou `login_rodape` não gravado |
| M30 | alteração feita em aba nunca renderizada é descartada no salvamento | CT-10 | `login_rodape` continua o anterior; esperado "Recado novo" |
| M31 | o estado do upload (logo) de aba não carregada vira vazio no salvamento | CT-10 | a propriedade `logo` deixa de ser "logos/marca.png" |
| M43 | salvar zera o segredo de aba não carregada (o "em branco mantém o guardado" só vale para campo renderizado) | CT-10 | `client_secret` decifrado ≠ "segredo-google-gravado" (nulo ou ""); idem senha SMTP e chave do anti-robô |
| M44 | o anti-robô (ou o provedor) desligado no salvamento porque o toggle da aba não carregada vai como `false` | CT-10 | `login_anti_robo_habilitado` (ou `login_google_habilitado`) gravado falso; esperado verdadeiro |
| M45 | carregar uma aba re-preenche o formulário a partir do banco (`fill()` no gancho de carga), apagando a edição pendente | CT-16 | o banco tem o nome "Antes"; esperado "Editado" |

---

## Regra R9 — reagrupar em seções não muda o que a tela grava nem quando a seção condicional aparece

> `RQ-02` (+ `## Fora de Escopo`: "a feature reorganiza a tela, não o que ela governa") · perfil **padrão** · técnica: **domínio condicionado** (seção condicional × remetente)

```gherkin
  Regra: o campo que passou a morar numa seção condicional grava como antes, e a seção acompanha o remetente sem salvar

    Cenário: [CT-11] com o remetente SMTP, o servidor SMTP é editável e grava
      Dado o remetente de e-mail gravado "smtp" e o servidor SMTP gravado "smtp.antigo.test"
      E nenhuma aba carregada
      Quando o administrador altera o servidor SMTP para "smtp.novo.test" e salva
      Então o banco tem o servidor SMTP "smtp.novo.test", sem erro de formulário

    Esquema do Cenário: [CT-18] a seção do servidor SMTP acompanha o remetente escolhido, sem salvar
      Dado o remetente de e-mail gravado "<gravado>"
      Quando o administrador escolhe o remetente "<escolhido>" sem salvar
      Então a seção que contém "mail_host" está <estado>
      E o banco continua com o remetente "<gravado>"

      Exemplos:
        | gravado | escolhido | estado  |
        | log     | smtp      | visível |
        | smtp    | log       | oculta  |
```

> CT-18: visibilidade pela árvore (`assertSchemaComponentExists` com closure sobre a seção que contém `mail_host`, `isVisible()`). Recorte que o componente não mata: a remoção do `live()` do remetente (o teste de componente reavalia a visibilidade a cada request com ou sem `live()`) — lacuna declarada na taxonomia; o `live()` é pré-existente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M32 | a condição da seção lê o campo errado (`mailer` em vez de `mail_mailer`) e a seção fica sempre oculta | CT-11, CT-18 | campo oculto não é desidratado; o banco continua "smtp.antigo.test"; linha log→smtp: seção oculta |
| M33 | a condição da seção invertida (oculta com SMTP) | CT-11, CT-18 | banco continua "smtp.antigo.test"; linha log→smtp: seção oculta |
| M46 | a condição da seção lê o remetente **gravado** (settings) em vez do estado do formulário | CT-18 | linha log→smtp: seção continua oculta; linha smtp→log: continua visível |
| M47 | a seção condicional nunca volta a se esconder (`visible` avaliado só no mount) | CT-18 | linha smtp→log: seção visível; esperado oculta |

---

## Regra R10 — seção colapsada envia e valida os campos, e o erro nela fica visível

> `P-05`, `P-03` · perfil **padrão** · técnica: **EP** (seção que nasce colapsada: grava; valida numa aba nunca carregada; no navegador — CT-B03, CT-B05 — o erro fica visível)

```gherkin
  Regra: colapsar só esconde; o campo da seção colapsada continua no salvamento e na validação

    @pendente-Q1
    Cenário: [CT-12] o campo de uma seção que nasce colapsada grava ao salvar
      Dado a seção do provedor Google configurada para nascer colapsada
      E o client_id do Google gravado "id-antigo"
      Quando o administrador altera o client_id do Google para "id-novo" e salva
      Então o banco tem o client_id do Google "id-novo", sem erro de formulário

    Cenário: [CT-17] campo inválido numa seção que nasce colapsada, em aba nunca carregada, é validado e traz a aba
      Dado o nome da aplicação gravado "Antes"
      E o login com Google habilitado e o client_id do Google gravado vazio
      E nenhuma aba carregada
      Quando o administrador salva com o nome "Depois"
      Então o campo "login_google_client_id" tem erro de formulário
      E a tela re-renderizada mostra o campo "login_google_client_id" com a mensagem de erro (a aba Login carregou)
      E o banco continua com o nome "Antes"
```

> CT-12 fica `@pendente-Q1` (decisão da sessão, ADV-21): "a seção do provedor nasce colapsada" é parte da recomendação de Q1. O `Dado` é conferido no arranjo (`isCollapsed()` verdadeiro na seção do Google); se Q1 tirar o colapso inicial dos provedores, o caso troca de seção, não de oráculo. CT-17 vale nas duas leituras de Q1: a seção do provedor existe colapsável e o erro precisa chegar ao usuário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | "nasce colapsada" implementado como oculta até abrir (`->hidden()`/`->visible()`) | CT-12, CT-17 | campo oculto não é desidratado: banco continua "id-antigo"; e sem erro no `client_id`, o salvamento passa e grava "Depois" |
| M35 | campos de seção colapsada com `->dehydrated(false)` ("não enviar o que não se vê") | CT-12 | banco continua "id-antigo" |
| M48 | campos de seção colapsada fora da validação (`->required()` condicionado a a seção estar aberta) | CT-17 | sem erro em `login_google_client_id`; o banco passa a ter "Depois" |
| M49 | o adiamento só consulta o erro de campos de primeiro nível da aba; o campo dentro da seção aninhada do provedor não traz a aba | CT-17 | o HTML depois do salvamento não traz `login_google_client_id` nem a mensagem |

---

## Regra R11 — os pontos de entrada do adiamento não carregam o que não existe, não repetem a carga e não gravam

> `RQ-05` (linhas `loadDeferredSchema()` e `$loadedDeferredSchemas` da `## Superfície Livewire` do `02`) · perfil **padrão** · técnica: **EP** — dentro do domínio, fora do domínio, tipo errado — e **2-switch** (carregar → carregar de novo)

```gherkin
  Regra: pedir o carregamento só carrega aba que existe, uma vez, e nada do que o cliente pede grava

    Esquema do Cenário: [CT-13] o pedido de carregamento com argumento fora do domínio não carrega aba nenhuma
      Dado o nome da aplicação gravado "Antes" e nenhuma aba carregada
      Quando a tela recebe o pedido de carregamento com <argumento>
      Então <resultado>
      E o banco continua com o nome "Antes"

      Exemplos:
        | argumento                                     | resultado                                                        | # partição        |
        | a chave da aba Tabelas                        | só a aba Tabelas é marcada carregada e mostra "paginacao_padrao" | controle (válido) |
        | uma chave que não existe                      | nenhuma aba é marcada carregada                                  | fora do domínio   |
        | só a chave do grupo de abas, sem a da aba     | nenhuma aba é marcada carregada                                  | fora do domínio   |
        | uma lista no lugar do texto da chave          | a chamada falha com erro de tipo e nenhuma aba é marcada         | tipo errado       |

    Cenário: [CT-20] pedir de novo uma aba já carregada não muda as abas carregadas nem o que foi editado
      Dado o nome da aplicação gravado "Antes"
      E a aba Tabelas já carregada, com a paginação padrão alterada para 30 sem salvar
      Quando a tela recebe de novo o pedido de carregamento da aba Tabelas
      Então a aba Tabelas continua a única marcada carregada
      E o estado do formulário continua com a paginação padrão 30
      E o banco continua com o nome "Antes"

    Esquema do Cenário: [CT-14] escrever na lista de abas carregadas só antecipa a aba e nunca grava
      Dado o nome da aplicação gravado "Antes" e nenhuma aba carregada
      E o nome alterado para "Editado" no formulário, sem salvar
      Quando o cliente escreve na lista de abas carregadas <valor>
      Então <resultado>
      E o banco continua com o nome "Antes", e nenhum aviso de configuração salva foi emitido

      Exemplos:
        | valor                             | resultado                                                                       | # partição        |
        | a chave da aba Login              | a tela re-renderizada mostra "login_rodape", como se a aba tivesse sido aberta  | dentro do domínio |
        | uma chave que não existe          | a tela re-renderiza sem erro e sem nenhum campo de aba                          | fora do domínio   |
        | um texto no lugar da lista        | a escrita é recusada com erro de tipo                                           | tipo errado       |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M36 | a página sobrescreve o carregamento para "carregar tudo" quando a chave é o prefixo do grupo | CT-13 | linha "só a chave do grupo": abas marcadas ≠ vazio |
| M37 | a página aceita qualquer chave e a marca como carregada (sem o `skipPartialRender`) | CT-13 | linha "chave que não existe": a lista de carregadas ganha a chave |
| M38 | gancho do kit ao marcar aba carregada (ex.: `updatedLoadedDeferredSchemas`) que chama `save()` | CT-14 | linha "aba Login": o banco passa a ter "Editado" e o aviso de salvo é emitido; esperado "Antes" e nenhum aviso |
| M50 | o pedido repetido alterna a marca (abre/fecha), deixando a aba desmarcada | CT-20 | a aba Tabelas deixa de estar marcada; esperado continua |
| M51 | o pedido repetido re-renderiza a aba a partir do banco, descartando a edição pendente | CT-20 | o estado volta à paginação gravada; esperado 30 |

> CT-14 parte com uma edição pendente ("Editado") para que uma gravação disparada pela escrita na lista divirja do banco: o mutante grava "Editado" e emite o aviso de salvo.

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

## Regra R13 — a convenção "seção com ícone e colapsável" está escrita numa rule indexada para a Page

> `RQ-04` · perfil **mínimo** · técnica: **EP** (a rule existe, tem a seção do tema, e o índice a liga ao glob da Page)

```gherkin
  Regra: a convenção vira rule do projeto, lida por quem edita a tela

    Cenário: [CT-19] a rule de seções existe e o índice a liga à pasta das Pages do admin
      Dado a árvore do kit com .ai/rules/index.md e .ai/rules/pages.md
      Quando as rules do projeto são lidas
      Então .ai/rules/pages.md tem uma seção "## " cujo título cita seção (ou Section) com ícone e colapso
      E .ai/rules/index.md tem a linha que liga "app/Filament/Admin/Pages/**" a ".ai/rules/pages.md"
```

> Fica vermelho até o step 12 (`requirement-to-rule`) gravar a rule — vermelho esperado, classificação "implementação ainda não feita". Leva `naArvoreDoKit()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M52 | a rule não é escrita (step 12 pulado) | CT-19 | `.ai/rules/pages.md` sem a seção do tema |
| M53 | a rule é escrita, mas o índice não ganha a linha do glob — ninguém a lê ao editar a Page | CT-19 | `.ai/rules/index.md` sem `app/Filament/Admin/Pages/**` → `.ai/rules/pages.md` |
| M54 | a convenção vai para outro arquivo de rule (ex.: `filament.md`) e `pages.md` não a tem | CT-19 | `pages.md` sem a seção do tema |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a rota não recebe `{id}`; a tela edita um agregado único de settings | — |
| Autorização exercida na ação (não só `can()`) | lacuna declarada: recorte de RQ-05 dependente de Q6 (carregar aba depois de perder a permissão); a autorização da tela e da gravação não muda e é da ancestral (CT-13, CT-15, CT-33 de `settings-do-kit`), regressão do quality gate | Render adiado |
| Gate de camada da regra (autorização/validação por fora da UI) | não se aplica: a feature não cria regra de autorização nem de validação de domínio (`## Fora de Escopo` do `00`); a por-fora da ancestral é CT-33 de `settings-do-kit` | — |
| Idempotência | CT-20 (pedir a mesma aba duas vezes não altera as carregadas nem o estado); a gravação repetida sem mudança é CT-23 da ancestral | Render adiado |
| Concorrência | não se aplica: sem contador, saldo ou limite; dois salvamentos simultâneos seguem o último-grava-vence pré-existente, fora de escopo | — |
| **Fronteira no ponto de entrada** (gravação) | CT-09 (valor abaixo do mínimo num campo de aba nunca carregada), CT-17 (obrigatório vazio em seção colapsada de aba nunca carregada); as demais bordas são da ancestral (CT-09 de `settings-do-kit`) | Render adiado |
| **Domínio condicionado** | CT-11, CT-18 (seção SMTP × remetente); lacuna declarada: remover o `live()` do remetente não é observável em teste de componente (a visibilidade é reavaliada a cada request) e o cenário de navegador foi cortado — o `live()` é pré-existente e fora do que esta feature muda | Gravação sob adiamento |
| **Estado × operação de escrita** | não se aplica: sem entidade com ciclo de vida | — |
| Ausente ≠ null ≠ vazio | CT-10 (campo ausente do HTML por aba não carregada não é gravado como nulo/vazio; segredo "em branco" de aba não carregada não apaga o guardado) | Gravação sob adiamento |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: sem data/hora | — |
| Unicode / limite de varchar | CT-01 ("Aplicação" com acento, comparação estrita), CT-02; sem campo de texto novo | Estrutura da tela |
| Unicidade + soft delete | não se aplica: sem entidade removível | — |
| CRUD combinado | não se aplica: só edição de settings | — |
| Mass assignment | CT-14 (o cliente forja a lista de abas carregadas e nada grava); `$data` forjado é da ancestral (02: "já inventariada") | Render adiado |
| Upload | CT-10 (o caminho da logo de aba não carregada é preservado); envio de arquivo é da ancestral (CT-10 de `settings-do-kit`) | Gravação sob adiamento |
| Precisão monetária | não se aplica: sem dinheiro | — |
| **Superfície Livewire** — `loadDeferredSchema()` | CT-13 (fora do domínio e tipo errado), CT-20 (repetição) | Render adiado |
| **Superfície Livewire** — `$loadedDeferredSchemas` | CT-14 (fora do domínio e tipo errado) | Render adiado |
| **Superfície Livewire** — `$data` | não se aplica nesta wiki: inventariada e coberta na ancestral (CT-09, CT-33 de `settings-do-kit`); o `02` registra que esta feature não muda o que entra nem sai | — |
| **Superfície Livewire** — estado colapsado no `localStorage` | não se aplica: não chega ao servidor e só o próprio usuário o escreve no próprio navegador (`vendor/filament/schemas/src/Components/Section.php:$persist:364`, `:x-bind:class:375` — colapsar só troca classe, o campo continua no DOM e no salvamento) | — |
| **Superfície Livewire** — `?tab=` | CT-B04 (chave válida, chave antiga e chave inexistente) | Navegador |
| **Estado do framework usado sem validar** | CT-13 (a chave pedida vira busca de schema), CT-B04 (`?tab=` vira aba ativa) | Render adiado |
| **IDOR por entidade** | não se aplica: a feature não persiste entidade nova | — |
| **Escopo com discriminante nulo** | não se aplica: sem filtro de escopo | — |
| **Saída do estado de erro** | CT-09 e CT-17 (o erro leva o usuário à aba do campo), CT-B03 (corrigido o erro, o salvamento seguinte grava), CT-B05 (o erro em seção que nasce colapsada fica visível) | Render adiado |
| Cardinalidade do destinatário | não se aplica: sem efeito colateral com destinatário | — |
| Texto livre (acento, emoji, teto) | não se aplica: nenhum campo novo; os existentes são da ancestral | — |
| Capturas `art/` | lacuna declarada: sem oráculo barato (hash contra a tag exige `git` no teste; PNG regenerado muda de bytes sem mudança visual) — conferência visual da dimensão G do quality gate (ADV-24, rejeitado) | — |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| toda seção nasce expandida, salvo as quatro de provedor | recorte de Q1; a recomendação autorizada cobre persistência (CT-05, CT-B02) e o colapso inicial dos provedores (CT-12); "tudo o mais expandido" não tem mutante próprio que CT-B02/CT-B03 já não exercitem |
| a aba Aplicação responde em `?tab=aplicacao::tabs`; `?tab=kit::tabs` cai na primeira aba | recorte de Q3 (aberta); o invariante das duas leituras e a partição válida (`tabelas::tabs`) são CT-B04 |
| item que não é campo (aviso, texto) dentro de seção | recorte de Q4 (aberta); o invariante (todo campo) é CT-03 |
| quem perdeu a permissão não carrega aba | recorte de Q6 (aberta); o invariante (sem segredo) é CT-15 |
| as capturas `art/` mostram a aba Aplicação | sem oráculo barato; dimensão G do quality gate (ADV-24 rejeitado) |
| ícone específico de cada seção | escolha de implementação (Fronteira com o Plano) |
| trocar o remetente no navegador mostra a seção SMTP sem salvar (o `live()`) | `live()` pré-existente, fora do que a feature muda; CT-18 cobre a reação da seção no servidor |
| salvar duas vezes sem mudança | CT-23 da ancestral; o adiamento não cria escrita |
| docs en com rótulo traduzido ("Application tab") | o rótulo é string literal sem `__()`/`trans()` e vale em qualquer locale; as docs en citam o rótulo de tela em pt (ADV-10 rejeitado) |
| tema escuro das seções | nenhuma RQ |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | as abas mantêm a ordem e a última chama Aplicação com o ícone de antes | R1 | EP | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M1–M5 |
| CT-02 | cada doc que citava Kit cita Aplicação; histórico do CHANGELOG intacto | R2 | EP por arquivo | Documentação | unit de regra | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | M6–M9, M41 |
| CT-03 | todo campo tem uma seção antes da aba | R3 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M10–M12 |
| CT-04 | toda seção é colapsável e tem ícone | R4 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M13–M16 |
| CT-05 | toda seção tem id explícito próprio e guarda o colapso `@pendente-Q1` | R5 | varredura exaustiva | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M17–M19 |
| CT-06 | toda aba tem chave própria e conteúdo adiado | R6 | estado exibido | Estrutura da tela | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M20–M22 |
| CT-07 | a primeira renderização tem um marcador por aba e nenhum id de campo | R6 | estado exibido | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M20, M24, M42 |
| CT-08 | carregar uma aba mostra a âncora dela e nenhuma das outras cinco | R6 | estado exibido | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M21, M23 |
| CT-09 | erro em aba nunca carregada carrega a aba e não grava | R7 | EP + não-efeito | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M25–M27 |
| CT-10 | alterar campo de aba nunca carregada grava e preserva o resto, segredos incluídos | R8 | não-efeito com destinatário | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M28–M31, M43, M44 |
| CT-11 | com SMTP, o servidor SMTP grava | R9 | domínio condicionado | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M32, M33 |
| CT-12 | campo de seção que nasce colapsada grava `@pendente-Q1` | R10 | EP | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M34, M35 |
| CT-13 | pedido de carregamento fora do domínio não carrega aba | R11 | EP | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M36, M37 |
| CT-14 | lista de abas carregadas forjada não grava | R11 | EP | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M38 |
| CT-15 | aba carregada não entrega segredo guardado | R12 | EP por segredo | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M39, M40 |
| CT-16 | editar uma aba, carregar outra e salvar grava a edição | R8 | 2-switch | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M45 |
| CT-17 | campo inválido em seção colapsada de aba nunca carregada é validado e traz a aba | R10 | EP | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M34, M48, M49 |
| CT-18 | a seção SMTP acompanha o remetente escolhido, sem salvar | R9 | domínio condicionado | Gravação sob adiamento | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M32, M33, M46, M47 |
| CT-19 | a rule de seções existe e o índice a liga à Page | R13 | EP | Documentação | unit de regra | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | M52–M54 |
| CT-20 | pedir de novo uma aba carregada não muda carregadas nem estado | R11 | 2-switch | Render adiado | componente Livewire/Filament | `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` | M50, M51 |
| CT-B01…CT-B05 | ver `05-casos-de-teste-browser.md` | R5, R6, R10 | — | Navegador | browser | `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` | MB1–MB13 |

## Revisão adversarial — rodada 1

`fw-adversario-ct`, 2026-10-15 · 25 achados · 23 fechados · 2 rejeitados com motivo · cenários novos: CT-16, CT-17, CT-18, CT-19, CT-20, CT-B05 → rodada 2 devida.

| ADV | Desfecho |
|---|---|
| ADV-01 | fechado — CT-10 grava fora do default a senha SMTP, o `client_secret` do Google e a chave do anti-robô, com o anti-robô e o Google ligados; comparação decifrada; M43, M44 |
| ADV-02 | fechado — CT-16 (editar A, carregar B, salvar); M45 |
| ADV-03 | fechado — CT-B04 ganha a partição válida `?tab=tabelas::tabs` → `paginacao_padrao` visível sem clique; MB11 |
| ADV-04 | fechado — CT-17 (componente: erro em seção que nasce colapsada, aba nunca carregada) e CT-B05 (navegador: a mensagem fica visível); M48, M49, MB12, MB13 |
| ADV-05 | fechado — CT-07 exige nenhum `id="form.` no HTML inicial; M42 |
| ADV-06 | fechado — CT-08: cada linha afirma a âncora da aba pedida e a ausência das outras cinco |
| ADV-07 | fechado — CT-18 (a seção SMTP acompanha o remetente sem salvar); M46, M47; remoção do `live()` declarada como lacuna na taxonomia |
| ADV-08 | fechado — CT-02 conta exatamente 8 menções abaixo de `## [0.49.0]` (6 + 2) |
| ADV-09 | fechado — CT-02 com controle positivo "Aplicação" em cada um dos oito arquivos; M41 |
| ADV-10 | rejeitado — o rótulo é string literal em `Tab::make()` sem `__()`/`trans()`; vale em qualquer locale e as docs en citam o rótulo de tela em pt |
| ADV-11 | fechado — com ADV-01: o snapshot de CT-10 parte de valores fora do default em toda aba, segredos e toggles incluídos |
| ADV-12 | fechado — com ADV-05 (varredura de todo `id="form.`, não seis âncoras) |
| ADV-13 | fechado — com ADV-06 |
| ADV-14 | fechado — com ADV-08 (contagem exata, recortada no histórico) |
| ADV-15 | fechado — M38 reescrito como gancho que chama `save()`, com a asserção que diverge (estado "Editado" + nenhuma notificação de salvo); o "re-preenche ao carregar" virou M45 em CT-16 |
| ADV-16 | fechado — MB8 morto pelo passo 5 de CT-B03; CT-B03 ganha campo válido alterado numa seção colapsada pelo usuário, conferido no banco depois de corrigir o erro e salvar; MB10 |
| ADV-17 | fechado — CT-05 usa `getCustomId()` (`HasId.php:getId():18` cai em `getKey()`); M17 reescrito |
| ADV-18 | fechado — com ADV-03: a linha válida tem oráculo exato; as inválidas mantêm o "ou" das duas leituras de Q3 |
| ADV-19 | fechado — a linha "segunda vez" saiu de CT-13 e virou CT-20, com a aba já carregada no `Dado` e edição pendente; M50, M51 |
| ADV-20 | fechado — CT-B01: as afirmações saíram do `Dado` para os `Então` |
| ADV-21 | fechado por decisão da sessão — CT-12 marcado `@pendente-Q1` |
| ADV-22 | fechado — CT-19 (a rule existe e está indexada para a Page); M52–M54 |
| ADV-23 | fechado por decisão da sessão — Q1 aberta, recomendação autorizada e implementada; RQ-03 "cobertura parcial: recorte de Q1 implementado pela recomendação autorizada, cenários `@pendente-Q1`" |
| ADV-24 | rejeitado — capturas sem oráculo barato; lacuna declarada, dimensão G do quality gate |
| ADV-25 | fechado — CT-17 (validação da seção colapsada no servidor) e CT-B05 (o erro visível no navegador) |

## Precedência e pós-implementação

- A rule venceu a skill: o template do `05` pede `assertNoJavaScriptErrors()`, e `.ai/rules/testes-browser.md` (*ColorPicker dentro de Tabs*) a proíbe na tela de configurações. Os CT-B não usam essa asserção, e os oráculos são de visibilidade.
- Mutation: `--path=app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, pelo `pestw.cmd`, sem `--filter`, com `--no-tia`. No Windows o score só vale com conferência manual por amostragem (aplicar M10, M20, M28 e M43 por `sed`, rodar o CT previsto e fazer `git checkout` no arquivo) — `.ai/rules/testes.md` (*UNTESTED do pest-plugin-mutate*). Os mutantes desta wiki são, na maioria, de **configuração do schema** (método encadeado removido), que o plugin muta como remoção de chamada.
