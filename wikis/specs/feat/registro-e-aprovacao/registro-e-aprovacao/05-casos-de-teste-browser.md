# Casos de Teste de Browser — w3b: registro aberto e aprovação

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, in-process.
> Comando: `composer test:browser` (embute o `npm run build`). **Nunca `--parallel`.**
> Derivado do `00-requisito.md`. Perfil da área A1/A3: **completo** → teto de 1 happy path
> + 1 erro visível.

## Por que existe CT-B nesta feature

O gate não é "tem tela", é "só o navegador prova". Duas coisas caem nisso:

1. **A tela de registro no modo aberto nunca foi renderizada por ninguém.** Ela é uma página
   Livewire pública com Alpine e com o layout do Auth Designer, e hoje a única cobertura de
   browser que ela tem é `visit('/app/register')` dentro de `tests/Browser/TelasDoKitTest.php`
   — que **redireciona para o login** (sem token, `recusar()`), e `assertNoJavaScriptErrors()`
   passa numa tela de login. O próprio `tests/Pest.php` documenta essa classe de furo, e
   `wikis/specs/fix/auth-designer-telas/.../05-casos-de-teste-browser.md:87` a registra por
   escrito para esta rota. Ligar o registro aberto é a **primeira** vez que o formulário
   daquela rota existe para ser clicado.
2. **O desfecho do cadastro pendente é uma navegação com notificação.** O `register()` do kit
   desloga, notifica e redireciona; a notificação do Filament é renderizada por JS. Componente
   Livewire prova o `assertNotified()` (CT-12 do `04`); só o navegador prova que a mensagem
   **aparece** depois do redirecionamento, e que o redirecionamento acontece.

## Pré-requisitos

- [ ] `npm run build` executado — sem `public/build/manifest.json` toda tela responde
      `ViteException` (o `composer test:browser` já embute).
- [ ] `beforeEach` com `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` —
      sem `panel_user` no banco o cadastro morre no arranjo.
- [ ] `config(['kit.registro.habilitado' => true])` **antes** do `visit()`. Funciona porque o
      servidor do plugin é in-process e `RegistroPorConvite::mount()` lê a opção em tempo de
      execução. (O que **não** funciona assim é a rota de verificação de e-mail — ver
      *cogitado e cortado*.)
- [ ] Sem `actingAs()`: os dois cenários são de **visitante anônimo**, que é a persona da
      feature. É a exceção legítima à regra de usar `actingAs()`.

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo nome | `#form\\.name` | sim (página `Register` do Filament) |
| campo e-mail | `#form\\.email` | sim — usado por `tests/Browser/PerfisTest.php:52` |
| campo senha | `#form\\.password` | sim — idem `:53` |
| confirmação de senha | `#form\\.passwordConfirmation` | sim (página `Register` do Filament) |
| botão de envio | texto do rótulo do botão de registro | sim |

Dívida registrada: o kit não usa `data-testid` em nenhuma tela. Os seletores acima são o `id`
gerado do campo, com o `.` escapado — o mesmo padrão que `PerfisTest` já usa. Não é dívida
desta feature.

---

## CT-B01: o cadastro aberto funciona clicando, do formulário ao painel

**Por que browser e não Livewire**: CT-08 do `04` já prova, por componente, que o cadastro
grava e que o papel nasce certo. O que ele não pode provar é que o **formulário renderizado
existe e responde ao clique** — um `wire:model` quebrado, um Alpine que estourou ou um asset do
Vite que não subiu deixam CT-08 verde e a tela inutilizável. É a mesma razão pela qual CT-B06
de `PerfisTest` é o único cenário que entra pela porta da frente no login.

```gherkin
# language: pt

  Cenário: [CT-B01] o visitante cria a conta pela tela e chega ao painel de negócio
    Dado que a instalação está com o registro aberto ligado e a aprovação automática
    E que o visitante não está autenticado
    Quando ele preenche nome, e-mail e senha na tela de cadastro e envia
    Então ele chega ao painel de negócio
    E o painel exibe conteúdo
    E o console do navegador não acusa erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | ligar a opção | `config(['kit.registro.habilitado' => true])` | — |
| 2 | abrir a tela | `visit('/app/register')` | formulário de cadastro |
| 3 | preencher | `->fill('#form\\.name', 'Fulano')` … | campos preenchidos |
| 4 | enviar | `->press('…')` | navegação |
| 5 | **esperar a navegação** | `->assertPathIs('/app')` | URL do painel |
| 6 | provar que renderizou | `->assertSee('…')` | conteúdo do dashboard |
| 7 | console | `->assertNoJavaScriptErrors()` | sem erro |

**Assertions**: `assertPathIs` **primeiro** — é ela que espera a navegação; invertida, o
`assertSee` roda contra o snapshot da tela de cadastro e falha com o cadastro tendo funcionado.
`assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a tela é o `Register` do Filament vestido
pelo Auth Designer, ou seja, **tela de vendor** — `assertNoSmoke()` reprovaria por
`console.log` alheio (`.ai/rules/testes-browser.md`).

O par (path, conteúdo) é o oráculo. Nenhuma das duas sozinha basta: a URL certa pode levar a um
dashboard que não renderizou, e o conteúdo pode ser o da tela anterior.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | o modo aberto renderiza sem o layout do Auth Designer e a tela sai sem assets | CT-B01 (passo 7 e o `assertSee` do passo 2) |
| MB2 | o campo de e-mail continua `disabled` no modo aberto — o formulário não envia | **CT-B01** (o `fill` falharia) |
| MB3 | o redirecionamento pós-cadastro aponta para uma rota que não existe | CT-B01 (passo 5) |

---

## CT-B02: com aprovação manual, o visitante é avisado e volta ao login

**Por que browser e não Livewire**: CT-12 do `04` prova por componente que a sessão não fica
autenticada e que a notificação é despachada. O que só o navegador prova é que a notificação do
Filament — renderizada por JS, num componente Livewire de notificações — **aparece depois do
redirecionamento**. Notificação despachada num request que termina em `redirect` é exatamente o
caso que morre em silêncio: ela vive na sessão e depende de o layout de destino montar o
componente.

```gherkin
  Cenário: [CT-B02] @premissa o cadastro pendente termina no login, com a mensagem na tela
    Dado que a instalação está com o registro aberto ligado e a aprovação manual
    Quando o visitante preenche e envia o cadastro
    Então ele é levado à tela de login
    E a tela informa que o cadastro aguarda aprovação
    E o console do navegador não acusa erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | ligar as duas opções | `config([… 'habilitado' => true, … 'aprovacao_manual' => true])` | — |
| 2 | abrir e preencher | `visit('/app/register')->fill(…)` | formulário |
| 3 | enviar | `->press('…')` | navegação |
| 4 | **esperar a navegação** | `->assertPathIs('/app/login')` | URL do login |
| 5 | a mensagem | `->assertSee('aprova')` | texto da notificação |
| 6 | console | `->assertNoJavaScriptErrors()` | sem erro |

**Assertion `@premissa`**: o texto exato da mensagem não está no requisito (ver
`## Fronteira com o Plano` do `04`). O passo 5 casa um **radical** (`aprova`), não a frase
inteira — o cenário afirma que *existe mensagem sobre aprovação*, que é o que o requisito
determina, e não a redação, que é escolha do PRD. Se a redação mudar sem perder o sentido, o
cenário continua válido; se a mensagem desaparecer, ele reprova.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB4 | o pendente é redirecionado ao painel e leva 403 na cara | **CT-B02** (passo 4) |
| MB5 | a notificação é enviada sem `persistent()` / antes do redirect e morre na navegação | **CT-B02** (passo 5) |
| MB6 | o `logout()` invalida a sessão **antes** de a notificação ser gravada nela | CT-B02 (passo 5) |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a tela de verificação de e-mail renderiza sem erro de JS | **inexpressável neste arnês**: a rota só nasce se `hasEmailVerification()` for verdade no **boot** do painel (`vendor/filament/filament/routes/web.php:75-84`), e `config()` ajustado no teste chega tarde. Exigiria um `TestCase` próprio e uma quarta suíte no `phpunit.xml` para um cenário. CT-22b do `04` mede a mesma decisão onde ela é tomada. **Lacuna declarada** |
| o link "Cadastre-se" aparece no login e navega | o link é um `<a>` renderizado por `Login::getSubheading()`; presença e destino são prováveis por componente Livewire, mais barato. Mata o mesmo mutante que um cenário do `04` |
| a organização certa aparece no cabeçalho do cadastro (`?org=`) | precisa de `tests/BrowserTenancy`, e o que ele provaria (o vínculo) é CT-24 do `04`, por componente. O navegador não acrescenta nada — nenhum JS decide isso |
| a coluna "Pendente" e a ação Aprovar na listagem | ação de tabela é componente Livewire por definição (`callAction`), e CT-19/CT-23 do `04` já cobrem os dois lados. Empurrar para o browser é exatamente o que o gate proíbe |
| tema escuro na tela de cadastro no modo aberto | `assertSee` não valida tema, e o alternador da tela vem do Auth Designer, já coberto por `tests/Browser/TemaEscuroTest.php`. Nada específico desta feature |
| 403 do recém-registrado em `/admin` visto na tela | `tests/Browser/PerfisTest.php` já prova que a página de 403 é tela legível, para `panel_user` inclusive. Duplicaria |

**Teto respeitado**: 2 CT-B (1 happy path + 1 erro visível), o teto do perfil `completo`.
Nenhum mutante do `04` ficou dependendo de browser.

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | modo aberto em `/app/register`, campo de e-mail habilitado e vazio | idem | ✅ | CT-04 (`assertFormFieldEnabled` + `assertSchemaStateSet`), CT-B01 (preenchido no navegador) |
| 2 | modo convite em `/app/register?token=…`, e-mail desabilitado e preenchido | idem, inalterado | ✅ | `ConviteTest` inteiro segue verde; CT-06 e CT-07 com o registro **ligado** |
| 3 | link "Cadastre-se" no login, só com o registro ligado | idem — `TelaLogin::getSubheading()` devolve o do pai quando ligado | ✅ | CT-04b, nas duas partições |
| 4 | ação Aprovar na tabela de usuários do `/app` | idem, pela trait `AprovacaoDeCadastro` | ✅ | CT-19b (`admin_app` aprova), CT-23a/CT-23b (as duas negativas) |
| 5 | ação Aprovar na tabela de usuários do `/admin` | idem, mesma trait | ✅ | CT-19 (`master_global` aprova pela listagem) |
| 6 | toggle de registro no formulário de organização | ⚠️ **divergiu**: entrou na `Section` de *Identificação* já existente, ao lado de `ativo`, e não numa `Section::make('Registro')` própria | ⚠️ | corte 2 da auditoria do step 6 — registrado em *Auditoria Ponytail* do `03`. CT-25 (grava) e CT-25b (escondido com a opção global desligada) |
| 7 | tela de verificação de e-mail no ar só com a opção ligada | idem, mas **não coberta por navegador** | ⚠️ | CT-22b mede a decisão no provider. A visita em navegador é a lacuna declarada em *Cogitado e cortado* — a rota nasce no boot e `config()` no caso chega tarde |
| 8 | *(não desenhado)* título do modo aberto | ⚠️ **acréscimo**: o plano dizia "Criar conta", que é o rótulo do próprio botão de envio; virou "Criar sua conta" | ⚠️ | *Desvios do Plano* no `03`. Descoberto ao escrever CT-B01 |
| 9 | *(não desenhado)* campo *Papéis* obrigatório | ⚠️ **acréscimo**: deixa de ser obrigatório para cadastro pendente, que não tem papel por desenho — sem isso a edição de um pendente era impossível de salvar | ⚠️ | *Notas de Implementação*, achado 1. CT-16 |

**Divergências**: 4 (itens 6, 7, 8 e 9). Nenhuma é implementação contradizendo o desenho por
descuido: 6 é corte aprovado da auditoria, 7 é limite de arnês declarado, e 8 e 9 são defeitos
que os CT-B e os CT acharam **antes** de a feature ir para o ar. Todas replicadas em
`03-progresso.md`.
