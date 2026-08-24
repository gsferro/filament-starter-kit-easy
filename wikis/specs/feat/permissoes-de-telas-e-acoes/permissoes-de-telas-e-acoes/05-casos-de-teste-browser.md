# Casos de Teste de Browser — Permissões de telas e ações

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor — HTTP in-process,
> porta aleatória. Nada de Herd, `artisan serve` ou `APP_URL`.
> Comando: `composer test:browser` (em série — **nunca** `--parallel`)

## Gate — por que existe `05` nesta feature

A tabela `## Superfície de UI` do PRD é o gatilho, mas quase tudo aqui é falsificável por
componente Livewire e foi para o `04`: 403, item de menu ausente, Action oculta, Action recusada,
cartão de hub que desaparece.

Sobra **uma** afirmação que só o navegador prova: **o dashboard continua sendo desenhado quando
parte dos widgets é ocultada pela permissão.** A grade do Filament monta `columnSpan` no cliente e
os gráficos são `<svg>` produzido por JavaScript — nada disso existe no HTML que o servidor
devolve. Uma implementação que oculte o widget de um jeito que deixe um buraco na grade, ou que
derrube o Alpine dos widgets vizinhos, passa em **todos** os cenários do `04` e quebra a tela.

Isto não é hipótese genérica: `tests/Browser/GraficosDoDashboardTest.php:16-18` registra que erro
de JS de **um** widget derruba o Alpine dos demais, e que nenhum teste de componente enxerga isso.
Esta feature é a primeira do kit capaz de produzir um dashboard com **subconjunto** de widgets.

**Teto do perfil `completo`**: 1 happy path + 1 erro visível. Dois cenários, e os dois são o mesmo
happy path em dois painéis? Não — CT-B01 é o painel com a grade mais densa (16 widgets, 5 deles
gráficos Apex) e CT-B02 é o erro visível (a tela do usuário comum, onde a ocultação é a regra e não
a exceção). Ver `## Cogitado e cortado`.

## Pré-requisitos

- [ ] `npm run build` executado — sem `public/build/manifest.json` toda tela responde
      `ViteException` e todo cenário falha por um motivo que não é o dele
- [ ] `tests/Browser/Screenshots` no `.gitignore` (já está)
- [ ] Autenticação por `$this->actingAs(usuarioDoKit($papel, $email))` **antes** do `visit()` — é
      o mesmo processo, então funciona. Login pela tela custa dezenas de segundos por cenário
- [ ] Os dois seeders do Shield em `beforeEach`, como em `tests/Browser/GraficosDoDashboardTest.php:22-23`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| gráfico Apex desenhado | `#iaExecucoesPorDia svg.apexcharts-svg` | **sim** — o `$chartId` que o kit declara; usado em `tests/Browser/GraficosDoDashboardTest.php:60-62` |
| gráfico Apex de status | `#iaExecucoesPorStatus svg.apexcharts-svg` | sim |
| gráfico Apex de filas | `#filasTaxaDeSucesso svg.apexcharts-svg` | sim |
| widget de trilha de acesso | texto do IP semeado | sim (dado, não marcação) |

**Dívida registrada**: o kit não tem `data-testid` nos widgets. O `#{chartId}` dos gráficos Apex é
o mais próximo disso e é o que os CT-B usam; para os widgets não-gráficos a âncora é o **dado**
semeado, que é oráculo mais forte que marcação de qualquer forma.

---

## CT-B01: o dashboard de infraestrutura é desenhado com parte dos widgets ocultada pela permissão

**Por que browser e não Livewire**: a asserção é sobre `<svg>` produzido por JavaScript e sobre
ausência de erro de JS na página inteira. Um teste de componente prova que os dados chegaram e
segue verde com a grade quebrada e com o Alpine dos vizinhos derrubado.

```gherkin

# language: pt

  Cenário: [CT-B01] a grade sobrevive à ocultação de um widget por permissão
    Dado um usuário com o papel "infra"
    E que a permissão "View:UltimosAcessos" foi revogada daquele papel
    E dado semeado nas fontes de IA e de filas
    Quando ele abre o painel de infraestrutura
    Então os três gráficos continuam desenhados
    E a página não mostra o IP do acesso semeado
    E o console não acusa erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar a persona sem uma permissão | `$papel = Role::findByName('infra'); $papel->revokePermissionTo('View:UltimosAcessos'); $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));` | — |
| 2 | abrir com janela alta | `visit('/infra')->resize(1440, 4000)` | os widgets adiados entram em viewport |
| 3 | provar que a grade desenhou | `->assertPresent('#iaExecucoesPorDia svg.apexcharts-svg')` ×3 | os `<svg>` existem |
| 4 | provar que o widget revogado não vazou | `->assertDontSee('203.0.113.7')` | o IP semeado não está na página |
| 5 | provar que nada quebrou | `->assertNoJavaScriptErrors()` | console limpo |

O passo 2 não é detalhe: os widgets do Filament carregam **adiado** e o gatilho é a entrada em
viewport. Sem o `resize`, os gráficos ficam abaixo da dobra, nunca são pedidos, e o cenário falha
dizendo que o elemento não existe — quando o que aconteceu é que ele nem chegou a ser pedido
(medido e registrado em `tests/Browser/GraficosDoDashboardTest.php:48-55`).

`assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a página tem widget de plugin de terceiro, e
`assertNoSmoke()` reprova por `console.log` alheio (regra do `.ai/rules/testes-browser.md` e do
runtime do plugin).

**Assertions**: os três `assertPresent` são o oráculo do cenário; `assertDontSee` é o oráculo da
permissão; `assertNoJavaScriptErrors()` é **apoio** e não vale sozinho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| MB1 | o widget é ocultado por CSS (`->hidden()` no wrapper) em vez de removido da grade — o HTML ainda carrega o dado | CT-B01 (passo 4) |
| MB2 | o widget oculto deixa a grade com `columnSpan` inconsistente e o Alpine estoura, derrubando os gráficos vizinhos | CT-B01 (passos 3 e 5) |
| MB3 | o concern do widget quebra o `$chartId`/`getColumnSpan()` dos `ApexChartWidget` (a trait entra no lugar errado da hierarquia) | CT-B01 (passo 3) |

---

## CT-B02: o painel de administração é desenhado para quem tem só parte dos widgets

**Por que browser e não Livewire**: o **erro visível** desta feature é a grade vazia ou quebrada,
e ela só existe depois do JavaScript. É a partição oposta de CT-B01: lá um widget de 16 é ocultado,
aqui a maioria é.

```gherkin
  Cenário: [CT-B02] o painel continua utilizável quando a maioria dos widgets é ocultada
    Dado um usuário com o papel "admin"
    E que só a permissão "View:UsuariosVisaoGeralStats" foi mantida entre as dos widgets do painel
    Quando ele abre o painel de administração
    Então a página mostra o número total de usuários
    E a página não mostra o e-mail do usuário semeado
    E o console não acusa erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | revogar as permissões dos outros widgets do painel | `collect(['View:UsuariosPorPapel','View:UltimosUsuariosCadastrados','View:ConvitesPorSituacao','View:AgentesIaStats','View:AgentesIaPorProvider','View:ProgressoOnboarding'])->each(fn ($p) => $papel->revokePermissionTo($p));` | — |
| 2 | abrir | `visit('/admin')->resize(1440, 4000)` | — |
| 3 | provar que o que sobrou desenhou | `->assertSee('Usuários')` **mais** a contagem concreta | o card restante está lá com o valor |
| 4 | provar que o revogado não vazou | `->assertDontSee('semeado@example.com')` | o e-mail não está na página |
| 5 | console | `->assertNoJavaScriptErrors()` | — |

O passo 3 afirma **o valor**, não só o rótulo: `assertSee('Usuários')` sozinho é texto de layout e
passaria com a grade inteira vazia — o falso ✅ que o `04` proíbe explicitamente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| MB4 | com a maioria oculta, a grade colapsa e o widget restante não renderiza | CT-B02 (passo 3) |
| MB5 | o `UltimosUsuariosCadastrados` é ocultado mas o link do rodapé dele permanece | CT-B02 (passo 4) |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| 403 na Page vista no navegador | já provado por CT-02, na camada HTTP, que é mais barata. O navegador não acrescenta nada a um `abort(403)` |
| item de menu ausente no navegador | já provado por CT-02 (segunda asserção) em componente |
| Action oculta na tabela, no navegador | já provado por CT-10 em componente Livewire — `assertActionHidden` é determinístico e roda em milissegundos |
| aceite de convite pela tela, no navegador | já provado por CT-13/CT-14. O modal de confirmação é JS, mas a existência dele não é o que esta feature muda |
| hub de infraestrutura com cartão faltando, no navegador | já provado por CT-03 em componente. A grade de cartões é HTML do servidor (`cards-page.blade.php`), não JS |
| dashboard do painel de negócio | o painel `app` **não tem widget** — nada a ocultar |
| tema escuro com widget oculto | mata o mesmo mutante que MB2 e custa outro boot de navegador. `tests/Browser/TemaEscuroTest.php` já cobre o tema |

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido no step 7, depois de rodar os CT-B e a suíte de backend.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `HubDeInfraestrutura` responde 403 sem `View:HubDeInfraestrutura` | idem, por `ExigePermissaoDaTela` | ✅ | CT-02, linha `hub sem flag` |
| 2 | `Pulse` responde 403 sem `View:Pulse` | idem | ✅ | CT-02 linha `Page simples`, CT-04, CT-26 |
| 3 | `HubDeAdministracao` exige flag **e** permissão | idem; `canAccess()` virou o hook `regraLocalDeAcesso()` | ✅ | CT-02 linha `hub com flag`, CT-05 |
| 4 | `HubDoNegocio` exige flag **e** permissão | idem | ✅ | CT-06 (suíte Tenancy) |
| 5 | `ConvitesRecebidos` exige tenancy **e** permissão | idem | ✅ | CT-13, CT-14 |
| 6 | 7 widgets do `/admin` consultam permissão | idem | ✅ | CT-22 e CT-32 percorrem os 23 |
| 7 | 16 widgets do `/infra` consultam permissão | idem | ✅ | CT-22 e CT-32 |
| 8 | 18 widgets mantêm a checagem de fonte de dados | corpo do `canView()` movido intacto para `fonteDeDadosDisponivel()` | ✅ | CT-07 linha `fonte ausente` |
| 9 | 6 Actions com `->authorize()` | idem | ✅ | CT-09/CT-10 (Kit e Tenancy), CT-11, CT-12, CT-14, CT-28 |
| 10 | link `dashboardAiTasks` com `->visible()` pelo gate | ⚠️ **NÃO implementado, e a mudança é o achado** — `AiRunResource::canAccess()` é o próprio gate `ver-ai-tasks`, então o `->visible()` seria no-op e infalsificável | ⚠️ divergência deliberada | CT-20/CT-31 reescritos; ver Desvios no `03-progresso.md` |
| 11 | 6 permissões novas no banco e selecionáveis | idem | ✅ | CT-15, CT-16 |
| 12 | custom permission recortada por painel no seeder | idem, com o mapa montado sobre as chaves REAIS do Shield (fail-closed) | ✅ | CT-17 linhas de vazamento, CT-19, CT-27 |
| 13 | (não desenhado) dashboard desenha com subconjunto de widgets | os dois painéis renderizam, gráficos Apex inclusos, sem erro de JS | ✅ | CT-B01, CT-B02 |
