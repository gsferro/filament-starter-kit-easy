# Casos de Teste de Browser — Tela de perfis (papéis)

> Runtime: `pest-plugin-browser 5.0.1` (Playwright). O plugin sobe o próprio servidor, HTTP
> in-process, em porta aleatória — nada de Herd, `artisan serve` ou `APP_URL`.
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`). Nunca `--parallel`.

## Por que existe este arquivo

Dois cenários, e cada um afirma sobre algo que **só o navegador prova**:

- **CT-B01** — trocar de painel no tab vertical é Alpine. O teste de componente Livewire renderiza
  o HTML dos três painéis de uma vez (todos os `Tab` estão no DOM); ele não sabe dizer se apenas um
  está visível nem se o clique troca. É o mecanismo do RQ-10.
- **CT-B02** — o slide-over é um modal montado por JavaScript. `assertActionExists` prova que a
  action existe; não prova que o painel lateral abre e mostra o conteúdo.

Todo o resto de RQ-01..RQ-11 é provável por componente Livewire ou por HTTP e está no `04`.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a tela de edição de papel abre sem erro de JS | já coberto: `/admin/shield/roles` e `/admin/shield/roles/create` estão no lote de `telasDoKit()` (`tests/Pest.php:240-241`), rodado por `tests/Browser/TelasDoKitTest.php` |
| o contador de permissões muda ao marcar a caixa | o `CheckboxList` do Shield é `->live()`: a re-renderização é do servidor e o número está no HTML que o teste de componente vê. CT-16 é mais barato e prova a mesma coisa |
| o breadcrumb mostra o rótulo | é texto no HTML; CT-02 (HTTP) prova |
| a coluna de usuários mostra o número | é texto no HTML; CT-04 (componente) prova |
| o tab vertical é **vertical** (orientação) | nenhuma asserção barata distingue orientação. `assertSee` passa nas duas; seletor por classe de CSS é proibido por `.ai/rules/testes-browser.md`. Fica no roteiro *Desenhado × Implementado*, por inspeção — registrado como lacuna declarada em R7/M3 do `04` |
| dark mode do slide-over | `assertSee` não valida tema, e defeito de cor exige screenshot e olho humano. `tests/Browser/TemaEscuroTest.php` já cobre o tema em nível de painel |

## Pré-requisitos

- [x] `npm run build` — sem `public/build/manifest.json` toda tela responde `ViteException`
- [x] `php artisan view:cache` — com cache frio o **primeiro** cenário que renderiza um painel
      estoura o teto de 45 s e falha por um motivo que não é o dele (medido, `.ai/rules/testes-browser.md`)
- [x] `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach` — papel
      sem a matriz do Shield abre painel e não abre tela
- [x] Autenticação por `$this->actingAs(usuarioDoKit('master_global'))` **antes** do `visit()`.
      Login pela tela custa ~20 s por cenário e já tem cenário próprio em `tests/Browser/PerfisTest.php`
- [x] O `beforeEach` **não arranja painel**. Cada cenário arranja o seu, imediatamente antes de
      visitar — o servidor é in-process e o `visit()` renderiza a barra lateral do painel em que o
      processo foi deixado (`.ai/rules/testes-browser.md`)
- [x] `tests/Browser/Screenshots` é caminho fixo do plugin e é limpo a cada run. Nenhum
      `->screenshot()` nestes dois cenários — captura não declarada em `KitArte::IMAGENS` é
      reportada, e não é isso que se quer aqui

## Seletores

O kit não tem `data-testid` (dívida conhecida, registrada em `.ai/rules/testes-browser.md`).

| Elemento | Seletor | Já existe? |
|---|---|---|
| tab de painel (rótulo) | texto `Painel /admin`, `Painel /app`, `Painel /infra` | sim — o rótulo sai de `Paineis::opcoes()` |
| seção de Resource dentro do painel | texto do rótulo do Resource (ex. `Projeto`, `Convite`) | sim |
| ação de usuários na linha da tabela | texto `Ver usuários` | nasce nesta entrega. **Não** é "Usuários": esse é o cabeçalho da coluna nova, e o modo estrito casaria os dois |
| cabeçalho do slide-over | texto `Usuários com o papel Painel App` | **não** — nasce nesta entrega |

⚠️ **Modo estrito do Playwright**: seletor que casa mais de um elemento é **erro**, não "o
primeiro". `text=Painel /app` casa o tab **e** a opção do select "Acesso ao painel" — este é
exatamente o problema que `tests/BrowserTenancy/CapturaDeArteTest.php:145-146` documenta. Nos dois
cenários abaixo o `assertSee` é usado (que não é estrito), e onde houver clique o alvo é o
`->click('text=…')` restrito ao rótulo do tab — que na tela de **criação** não colide, porque o
select "Acesso ao painel" ali mostra o placeholder e não as opções. Se colidir na prática, o
fallback é o `id` gerado do tab, colhido do HTML pelo próprio cenário em falha.

---

## CT-B01: o tab vertical de painel troca de painel por clique

**Por que browser e não Livewire**: a asserção é sobre **qual painel está visível** depois de um
clique, e a visibilidade de aba é Alpine. O teste de componente vê os três painéis no HTML ao
mesmo tempo.

```gherkin
# language: pt

Funcionalidade: Vínculo de permissões de um papel

  Regra: No tab de recursos, cada painel registrado tem um tab próprio

    Cenário: [CT-B01] o administrador troca de painel dentro do tab de recursos
      Dado o administrador na tela de criação de papel
      Quando ele escolhe o grupo do painel /infra
      Então as permissões do painel /infra ficam visíveis
      E ele escolhe o grupo do painel /admin
      Então as permissões do painel /admin ficam visíveis
      E a tela não registra erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar painel `/admin` e aquecer os componentes fora do cronômetro | `$this->actingAs(usuarioDoKit('master_global')); $this->get('/admin/shield/roles/create');` | — |
| 2 | abrir a tela | `visit('/admin/shield/roles/create')` | formulário de papel |
| 3 | ir ao painel `/infra` | `->click('text=Painel /infra')` | tab ativo troca |
| 4 | provar o conteúdo do `/infra` | `->assertSee('Audit')->assertDontSee('Tenant')` | seção de um Resource que só existe no `/infra`, e ausência do que só existe no `/admin` |
| 5 | voltar ao `/admin` | `->click('text=Painel /admin')` | tab ativo troca |
| 6 | provar o conteúdo do `/admin` | `->assertSee('Tenant')` | seção de um Resource do `/admin` |
| 7 | console | `->assertNoJavaScriptErrors()` | — |

**Assertions**: o oráculo é o **conteúdo de cada painel** (um Resource que só existe naquele
painel), não o rótulo do tab — o rótulo está no DOM nos três casos, então afirmar sobre ele passaria
com o tab quebrado. `assertNoJavaScriptErrors()` é assertion de apoio, nunca o oráculo.
`assertNoSmoke()` não serve: a tela é cheia de plugin de terceiro.

✅ **Conferido em `Paineis::resources()`** antes de escrever o cenário: `Exception` aparece nos
**três** painéis e por isso NÃO serve de oráculo — o rascunho deste roteiro o usava. Exclusivos:
`Audit`, `AiRun`, `MailLog`, `QueueMonitor`, `CommandRecord`, `ComposerReleasePackageSnapshot`,
`AuthenticationLog` no `/infra`; `Tenant`, `AgenteIa`, `Role`, `OnboardingFlow`,
`OnboardingCondition` no `/admin`; `Projeto` no `/app`. O par escolhido é **`Audit` × `Tenant`**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `Tabs` interno com a mesma chave do `Tabs` do vendor — o clique num tab de painel troca o tab externo (Recursos/Páginas/Widgets) | CT-B01, passo 4 |
| M2 | os painéis renderizados como `Section` collapsible (a implementação anterior) — não há o que clicar | CT-B01, passo 3 falha por seletor ausente |
| M3 | todos os painéis dentro de um único `Tab` — o clique não existe | CT-B01, passo 3 |

---

## CT-B02: o slide-over abre e lista os usuários do papel

**Por que browser e não Livewire**: o slide-over é um modal montado por JavaScript. `callAction()`
executa o `->action()` sem provar que o painel lateral abre; `assertActionExists()` prova só que a
action foi declarada.

`@premissa`: o conteúdo exibido é nome e e-mail (ver `04` → Fronteira com o Plano).

```gherkin
# language: pt

Funcionalidade: Usuários de um papel

  Regra: Só quem pode ver o papel vê quem o tem, e a consulta deixa rastro

    Cenário: [CT-B02] @premissa o administrador abre a lista de usuários de um papel
      Dado uma pessoa chamada Marina com o papel "panel_user"
      E o administrador na listagem de papéis
      Quando ele abre a lista de usuários do papel "Painel App"
      Então o painel lateral mostra "Marina"
      E mostra o e-mail de Marina
      E não oferece nenhum botão de gravação
      E a tela não registra erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | criar a pessoa e arranjar o painel | `$marina = usuario('marina@example.com'); $marina->update(['name' => 'Marina']); $marina->assignRole('panel_user'); $this->actingAs(usuarioDoKit('master_global', 'admin@example.com')); $this->get('/admin/shield/roles');` | — |
| 2 | abrir a listagem | `visit('/admin/shield/roles')` | tabela de papéis |
| 3 | abrir o slide-over | `->click('text=Usuários')` | painel lateral desliza |
| 4 | provar o conteúdo | `->assertSee('Marina')->assertSee('marina@example.com')` | nome e e-mail |
| 5 | provar que é leitura | `->assertDontSee('Salvar')` | nenhum submit |
| 6 | console | `->assertNoJavaScriptErrors()` | — |

**Assertions**: o oráculo é o **nome e o e-mail da pessoa criada no arranjo** — dado que não existe
em nenhum outro lugar da tela, então `assertSee('Marina')` só passa se o slide-over abriu **e**
carregou a lista. `assertDontSee('Salvar')` é o que mata o mutante do modal com submit.

⚠️ `->click('text=Usuários')` pode colidir com o cabeçalho da coluna nova, cujo rótulo premissado é
"Usuários" (modo estrito do Playwright). Se colidir, o alvo passa a ser o botão da ação por
`aria-label`, colhido do HTML no primeiro run em falha — e a colisão é, ela própria, o argumento
para o rótulo da coluna ser diferente do rótulo da ação. Registrado como pergunta em `00`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | modal comum em vez de slide-over | ⚠️ **sem matador**: `assertSee` não distingue modal de slide-over. Lacuna declarada; tentado seletor pela classe `fi-modal-slide-over`, recusado por `.ai/rules/testes-browser.md` (seletor por classe de CSS). Fica no roteiro *Desenhado × Implementado* |
| M2 | `modalSubmitAction(false)` esquecido — o slide-over ganha um "Salvar" que gravaria estado vazio sobre o papel | CT-B02, passo 5 |
| M3 | schema do slide-over sem o `->state()` — o painel abre vazio | CT-B02, passo 4 |
| M4 | `RepeatableEntry` sobre a relação crua — sob tenancy a mesma pessoa aparece duas vezes | ⚠️ não observável aqui: `tests/Browser` é single-tenant. Morto por CT-06 em `tests/Tenancy` |

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido no step 7 da `feature-wiki`, depois de rodar os CT-B.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `ListRoles` com coluna "Usuários" em badge, cinza quando 0 | igual | ✅ | CT-04 (estado da coluna em 0/1/3), CT-05 (ordenável) |
| 2 | Slide-over "Usuários com o papel X", leitura, botão "Fechar" | igual, com o rótulo da ação em **"Ver usuários"** e não "Usuários" | ⚠️ | CT-B02. O texto mudou para não colidir com o cabeçalho da coluna no modo estrito do Playwright — a colisão prevista neste arquivo virou o argumento para os dois textos serem diferentes. Registrado em Desvios do Plano, item 5 |
| 3 | Slide-over com estado vazio próprio quando o papel não tem ninguém | `EmptyState::make('Nenhum usuário tem este papel')` | ⚠️ | **sem cenário**: nenhum CT abre o slide-over de um papel vazio. CT-08 chama a ação de um papel COM usuário. Lacuna declarada — o `->visible()` do `EmptyState` e o do `RepeatableEntry` são complementares e um erro de sinal deixaria os dois visíveis ou nenhum |
| 4 | `EditRole` com breadcrumb no rótulo legível | igual | ✅ | CT-02 (método) + o caso HTTP que confere o breadcrumb da tela de alteração |
| 5 | Tab **vertical** de painel dentro do tab "Recursos" (orientação) | `Tabs::make('paineis')->vertical()` | ⚠️ | a TROCA de painel está provada (CT-B01, com `assertSee` que confere visibilidade); a **orientação** continua sem oráculo barato. Lacuna R7/M3, como previsto |
| 6 | Badge `selecionadas/total` em cada tab de painel | igual | ✅ | CT-15 (`0/`+total do painel), CT-16 (sobe para 1 no grupo marcado) |
| 7 | Badge `selecionadas/total` em cada seção de Resource | por `afterHeader([Text::make(...)->badge()])`, porque `Section` não tem `badge()` | ✅ | CT-16, segunda asserção (o grupo vizinho segue em 0) |
| 8 | Guard como select fechado, com "web" como default | igual, **mais** `->in()` de servidor que o PRD não previa | ✅ | CT-18 (guard novo da config é aceito), CT-19 (fora da lista é recusado e nada grava), CT-20 (vazio) |
| 9 | URL de edição com uuid | igual | ✅ | CT-12 (uuid 200 / id 404, em alteração e visualização), CT-11, CT-14, CT-14b |
| 10 | Slide-over é slide-over, e não modal central | `->slideOver()` | ⚠️ | sem oráculo: `assertSee` não distingue modal de slide-over, e seletor por classe de CSS é proibido por `.ai/rules/testes-browser.md`. Lacuna CT-B02/M1, como previsto |

**Duas lacunas de oráculo (№ 5 e № 10) eram previstas neste arquivo e continuam abertas** — as duas
são sobre aparência, e nenhuma asserção barata as alcança. Elas são exatamente o que a inspeção
visual com Playwright MCP (RQ-12, fora desta entrega) fecharia.

**Uma lacuna NOVA (№ 3)**: o estado vazio do slide-over não tinha cenário. É provável por
componente e ficou de fora por omissão, não por escolha — **fechada** com
`it('mostra estado vazio no slide-over de papel sem usuario')` em `tests/Kit/TelaDePapeisTest.php`.
O cenário discrimina porque o `->visible()` do `EmptyState` e o do `RepeatableEntry` são
complementares: um erro de sinal deixaria os dois visíveis ou nenhum, e o caso confere o texto do
estado vazio E a ausência do cabeçalho da tabela de usuários.
