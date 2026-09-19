# Casos de Teste — Page header nas telas de registro

> Requisito: [`00-requisito.md`](00-requisito.md) · Plano: [`01-plano-acao.md`](01-plano-acao.md) · ADRs: [`02-decisoes-arquiteturais.md`](02-decisoes-arquiteturais.md)
> Derivado do **requisito**. Nenhum cenário foi escrito olhando a implementação da feature — ela ainda não existe.
> O `01` e o `02` entraram só para **superfície** (paths, classes, rotas, a tabela `## Superfície Livewire`) e para
> **fatos medidos do vendor**, nunca como fonte do comportamento esperado. O que foi recusado como oráculo está
> em [`## Fronteira com o Plano`](#fronteira-com-o-plano).

## Perfil de Derivação

| Área | P | I | P×I | Perfil | Por quê |
|---|---|---|---|---|---|
| **A** — autorização da `ViewUser` (`View:User`) | 3 | 3 | **9** | **completo** | tela nova que expõe conta alheia; a permissão já existe e nunca teve consumidor |
| **B** — avatar do header (ADR-02) | 3 | 2 | 6 | padrão | integra com o `defaultAvatarProvider` do kit; o modo de falhar é **silencioso** |
| **C** — o header substitui o nativo nas seis telas | 2 | 2 | 4 | padrão | trait de vendor sobre páginas existentes |
| **D** — superfície Livewire do trait | 2 | 3 | 6 | padrão (**Impacto 3**) | nove métodos públicos novos por página, chamáveis por `$wire.`; retorno vai ao navegador |
| **E1** — `html: true` e escape do heading | 2 | 3 | 6 | padrão (**Impacto 3**) | `$record->name` é entrada de usuário; a API que desliga o `e()` é uma linha |
| **E2** — guarda de CSS do vendor | 1 | 1 | 1 | mínimo | quebra é cosmética e reversível |
| **F** — fronteira do `/app` (tenancy + governa a instalação) | 3 | 3 | **9** | **completo** | multi-tenancy, dado de terceiro, assimetria View × Edit |
| **G** — registro documental, dependência e `kit:update` | 1 | 2 | 2 | mínimo | retrabalho manual, nada irreversível |
| **H** — rota `view`, `ViewAction`, convivência com widgets/relations | 2 | 1 | 2 | mínimo | defeito visível na hora |

- **Técnicas aplicadas**: EP; BVA (contagem, com incremento inteiro); tabela de decisão (2×2 dos badges de `Tenant`);
  duas tabelas de decisão 2×2 completas (situação da conta; estado da organização); matriz papel × operação;
  rastreio de efeito (log de negativa);
  varredura de superfície Livewire; **controle negativo de detector** (técnica local, ver R1).
- **Revisão adversarial**: obrigatória — perfil completo em A e F, **e** Impacto 3 em D e E1. Resultado em
  [`## Revisão Adversarial`](#revisão-adversarial).
- Cenários: **55 CT** + **4 CT-B** · Regras: **13** · Mutantes previstos: **66** (+14 no `05`) · Sem matador: **5** (declarados)
- **Pós-revisão adversarial**: +7 cenários (CT-49…CT-51, CT-53…CT-56), +10 mutantes (M57…M66), 4 cenários
  órfãos adotados por R13i, 14 oráculos reescritos e a matriz de R4 refeita de eixo. Detalhe em
  [`## Revisão Adversarial`](#revisão-adversarial).
- **Contagem de cenário**: um `Esquema do Cenário` conta como **1**, qualquer que seja o número de linhas de
  `Exemplos:` — é a regra da skill, e vale uniformemente para os 55. (A revisão apontou, com razão, que isso
  não estava declarado; sem a declaração o total que rege os tetos de perfil não era auditável.)
- **Estouro de teto de mutantes, declarado**: R2 e R11 são de perfil `padrão` (teto 5) e declaram **6** cada. Em
  R2 os seis são de **registro do plugin** (esquecer o trait, esquecer um painel, registrar nos três, instância
  compartilhada, hook sem escopo, convenção de nome) — seis modos de errar a mesma decisão, não duas regras. Em
  R11 são seis slots independentes do mesmo invariante. Desdobrar qualquer das duas renumeraria a rastreabilidade
  inteira por motivo cosmético.

### Divergência declarada: rule do projeto vence a skill

| A skill diz | O projeto mede | Vence |
|---|---|---|
| `pest --parallel --tia` como verificação padrão | `.ai/rules/testes-browser.md`: `--parallel` derrubou 4 de 11 CT-B; sem PCOV o `--tia` não termina (abortado aos 35 min) | **a rule**. Dois comandos: `composer test:kit` (Kit+Tenancy, `--parallel`) e `composer test:browser` (série, com `npm run build` + `view:cache` embutidos) |
| `pest --mutate` no fechamento | `pestphp/pest-plugin-mutate` precisa ser confirmado no `composer.json` e exige PCOV/Xdebug | **a rule**: rodar `--mutate --path=app/Filament` só se o driver existir; senão, declarar a lacuna. Ver [`## Fechamento`](#fechamento-pós-implementação) |

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S**tructure | 3 classes `Schemas\*Header`; 2 `Pages\ViewUser`; 1 `Schemas\UserInfolist` ×2; `use HasPageHeader` em 6 páginas; `->plugin()` em 2 providers; `'view'` em 2 `getPages()`; `getViewAuthorizationResponse()` no `/app`; nenhuma migration, nenhum model, nenhum job | CT-03, CT-30, CT-41, CT-47, CT-48 |
| **F**unction | renderizar cabeçalho rico; autorizar leitura de conta; negar leitura de quem governa a instalação; escolher avatar; derivar badges do estado da conta | CT-07…CT-19, CT-37…CT-40 |
| **D**ata | `users` (`name`, `email`, `avatar_url`, `email_verified_at`, `aprovacao_pendente`, `origem`, `ativo`, `deleted_at`); `tenants` (`nome`, `slug`, `logo`, `cor_primaria_nome`, `ativo`, `registro_habilitado`); **nenhuma tabela nova**. Dado de **outra organização** é o eixo crítico. Nome é **texto livre de usuário** e vai para o `<h1>` | CT-06, CT-16, CT-27, CT-37…CT-40, CT-43 |
| **I**nterfaces | `GET /admin/users/{record}`, `GET /app/{tenant}/users/{record}`, `GET /admin/tenants/{record}` e os três `/edit`; **e a interface nova de fato**: `$wire.<método>()` para os 9 métodos públicos do trait, em toda página que o aplica | CT-11, CT-16, CT-20…CT-25 |
| **P**latform | CSS do vendor publicada por `filament:assets` (`public/css/mortalkiller/filament-page-header/`); `AlpineComponent` `page-header` carregado por `x-load`; tokens `--gray-*` do Filament e classe `.dark`; Vite manifest obrigatório no browser | CT-28, CT-47, CT-B01…CT-B04 |
| **O**perations | `admin` e `master_global` no `/admin`; `admin_app` e `panel_user` no `/app`; `infra` no painel **sem** o plugin. Uso indevido: URL direta para id de outra organização; `$wire.set()` a partir do console do navegador | CT-11…CT-19, CT-21, CT-22 |
| **T**ime | a **data de criação** no metadata é `created_at` formatado — fuso do app × fuso do banco × virada de dia. Nenhuma expiração, nenhum agendamento, nenhuma concorrência (a entrega não acrescenta operação de escrita) | CT-39 |

---

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o recorte de região do header é exato: o predicado roda dentro do `fph-root`, nunca sobre a página | C (padrão) | meta-regra, imposta pelo defeito medido da rodada anterior | controle negativo de detector | CT-01, CT-02 |
| **R2** — nas seis telas em escopo o cabeçalho é o do pacote; fora delas, o nativo | C (padrão) | RQ-02, RQ-03 | EP por tela + controle negativo | CT-03…CT-06, CT-47, CT-48 |
| **R3** — o slot de identidade do header não fica vazio quando o kit tem avatar a mostrar | B (padrão) | RQ-02, RQ-03 (invariante) + ADR-02 (mecanismo) | partição por esquema de URL | CT-07…CT-10 |
| **R4** — a `ViewUser` só abre para quem tem `View:User` | A (completo) | **RQ-13** | matriz papel × operação + par positivo/negativo | CT-11…CT-15 |
| **R5** — no `/app` a `ViewUser` não alcança conta de outra organização nem quem governa a instalação | F (completo) | RQ-02 + ADR-07 | matriz + camada externa à UI | CT-16…CT-19 |
| **R6** — a superfície Livewire nova não entrega ao navegador mais do que a tela mostra, e não aceita estado forjado | D (padrão, I=3) | `02` → `## Superfície Livewire` | varredura linha a linha da tabela | CT-20…CT-25 |
| **R7** — o conteúdo do header é escapado; `html: true` não existe em `app/` | E1 (padrão, I=3) | ADR-04 | asserção de ausência com filtro de comentário + caso comportamental | CT-26, CT-27 |
| **R8** — toda classe `fph-*` que as blades do vendor emitem tem regra na CSS do vendor | E2 (mínimo) | `01` passo 8 | leitura em runtime + piso de contagem | CT-28, CT-29 |
| **R9** — a rota `view` é declarada antes da `edit`, e o `ViewAction` navega | H (mínimo) | RQ-02 | EP | CT-30, CT-31, CT-32 |
| **R10** — o registro do pacote diz ADOTADO e onde; a dependência está declarada em caret | G (mínimo) | RQ-01, RQ-07, RQ-12, RQ-14 | EP + BVA de constraint | CT-33…CT-36 |
| **R11** — os slots do header refletem **este** registro, e mudam quando ele muda | C (padrão) | RQ-02, RQ-03 (invariante); conteúdo específico é `@premissa` | duas tabelas de decisão 2×2 completas | CT-37…CT-40 |
| **R12** — nenhuma página que aplica o trait declara `getHeader()` próprio | C (padrão) | `01` passo 4 (fato do vendor: `docs/specification.md:15`) | asserção de ausência com piso | CT-41, CT-42 |
| **R13** — trazida pela **revisão adversarial**: header completo nas telas de Edit (a); o trait só nas seis (b); o `/app` não excede o `/admin` (c); a ficha mostra o registro fora do header (e); o `kit:update` avisa (f); as duas células omitidas da matriz (g); ciclo de vida do registro (i) | C, D, F, G, A | achados I-1…I-7 + item 4 da revisão | tela × slot; igualdade de conjunto; comparação entre painéis; complemento de região; célula omitida | CT-49…CT-51, CT-53…CT-56, CT-43…CT-46 |

**Técnica escalada acima do perfil da área** — R3 está em área `padrão`, e partição por esquema de URL com
controle negativo é técnica de perfil completo. Escalada de propósito: o mutante de ADR-02 é **silencioso**
(o slot some sem erro, com `assertOk()` e `assertSee($nome)` verdes), e EP simples não o distingue.

---

## Fronteira com o Plano

O `01` e o `02` são ricos em nomes e em conteúdo de tela. Metade viraria oráculo sem ninguém notar. O que foi
**recusado**:

| Item vindo do `01`/`02` | Recusado como oráculo porque | Destino |
|---|---|---|
| `UserHeader`, `TenantHeader`, `UserInfolist` — os nomes das classes | escolha de implementação (a convenção é do vendor, não do requisito) | detalhe do cenário. O oráculo é o HTML renderizado, não o FQCN |
| `getFilamentAvatarUrl()` como fonte do avatar | **premissa de mecanismo** (ADR-02): fixa **como** escrever o cenário, não dispensa escrevê-lo | R3 escrita nesse mecanismo; o mecanismo descartado (provider do painel) vira o **controle negativo** de CT-07 |
| Os campos do metadata (`origem`, contagem de usuários, `slug`) e a lista de badges | comportamento **visível** que o requisito não determina | `@premissa` em R11 + **pergunta nº 1** ao `00` |
| O texto exato do `Log::warning` e o nome do canal `autenticacao` | o canal é escolha de implementação; **que haja registro da negativa** é o que R5 afirma | oráculo = "há um `warning` com `alvo_id` e `ator_id`", não a string |
| `hasCombinedRelationManagerTabsWithContent()` / `getContentTabLabel()` | API de vendor citada pela receita; o requisito pede **documentar**, não implementar combinação | CT-46 afirma convivência (regiões disjuntas), não a API |
| "26 classes `fph-*` emitidas, 26 definidas" | número do `01`. **Medido e divergente** — ver pergunta nº 4 | CT-28 lê o vendor em runtime; o número não é oráculo |
| Os seis commits agrupados, `bp:on`, `/code-review`, sub-agentes (RQ-05, RQ-06, RQ-08, RQ-09, RQ-10, RQ-11) | processo de entrega, não comportamento do sistema | fora do `04`. Ver [`## RQ sem cenário`](#rq-sem-cenário-e-por-quê) |

### Perguntas para o `00-requisito.md`

> O `00` desta wiki está aberto; ainda assim o bloco vai replicado aqui, no formato da seção de destino, para
> colagem direta em `## Ambiguidades e Perguntas Abertas`.

- **RQ-02/RQ-03 — o requisito não diz o que o header mostra.** "Adicione ele nas telas" determina *que haja*
  header, não *quais* campos. Badges de situação da conta, `origem`, `slug`, contagem de usuários e data de
  criação são escolha do `01`.
  - **Premissa adotada**: o conteúdo é o do `01`; o **invariante** afirmado por R11 vale em qualquer resposta —
    *seja qual for o conjunto de slots, ele é lido deste registro e muda quando o registro muda*.
  - **Se negado**: CT-37, CT-38 e CT-40 trocam de coluna em `Exemplos:`; CT-06 e o invariante de R11 **não**
    mudam.
- **RQ-02 — a `ViewUser` do `/app` deve mostrar os mesmos campos que a do `/admin`?** O `01` não distingue, e o
  `/app` é a tela que um administrador de organização vê sobre um colega.
  - **Premissa adotada, por falha fechado**: o `/app` mostra **o mesmo ou menos**, nunca mais. CT-20 é o cenário
    que mede, e o invariante é *nenhum slot do `/app` traz campo que a `ViewUser` do `/admin` não traga*.
  - **Se negado** (o `/app` pode mostrar algo a mais): CT-20 perde a comparação entre painéis e fica só com a
    medição do retorno ao navegador.
- **RQ-13 — a permissão governa também o `ViewAction` da tabela?** O Adendo 1 fala da tela.
  - **Premissa adotada, por falha fechado**: sim — ação que leva a uma tela negada não aparece. CT-14.
  - **Se negado**: CT-14 inverte (a ação aparece e a tela é que nega), e CT-12 passa a ser o único oráculo da
    saída do erro.
- **RQ-13 — abrir a *própria* ficha exige `View:User`?** Dimensão trazida pela revisão adversarial; nem o
  requisito nem o Adendo 1 a decidem, e é onde `View:User` costuma ser contornada com `if ($record->is($user))`.
  - **Premissa adotada, por falha fechado**: exige — nega sem a permissão. CT-56.
  - **Invariante afirmado junto, que vale em qualquer resposta**: a decisão sobre a conta própria **nunca** é
    mais permissiva que a decisão sobre a conta do colega.
  - **Se negado** (a própria ficha é sempre aberta): CT-56 inverte as duas primeiras linhas; a última fica.

- **Achado, não pergunta — a contagem de classes do `01` passo 8 está errada.** Medido no vendor hoje
  (`mortalkiller/filament-page-header` v2.1.5): as blades emitem **27** classes `fph-*` e a
  `resources/css/page-header.css` define **26**. A classe **`fph-schema`** (`header.blade.php:22`) **não tem
  regra**. O `01` diz "26 e 26". CT-28 nasce **vermelho** se a exceção não for declarada — e isso é a guarda
  funcionando, não defeito do caso. Decisão necessária: exceção nominal com motivo, ou abrir issue no vendor.

---

## Setup Global

### Personas

Três pessoas distintas em toda a área A/F — persona colapsada (dono = ator = aprovador) não exercita barreira
nenhuma.

| Persona | Como criar | Suíte |
|---|---|---|
| `admin` (instalação) | `usuarioDoKit('admin')` | `Kit` e `Tenancy` |
| `master_global` | `usuarioDoKit('master_global')` — **linha de controle, nunca linha de prova** (vence tudo pelo `Gate::before`) | ambas |
| `admin_app` (administra UMA organização) | `usuarioComPapel('admin_app', $acme)` | **só `tests/Tenancy`** — `.ai/rules/testes.md` |
| `panel_user` | `usuarioComPapel('panel_user', $acme)` | ambas |
| **o alvo** — conta observada, nunca o observador | `User::factory()->create([...])` | — |
| **o alvo da outra organização** | `duasOrganizacoes()` + `papelNaOrganizacao($outro, 'panel_user', $globex)` | `Tenancy` |

### Fixtures

- `User::factory()` com os estados que discriminam: com/sem `avatar_url`; `email_verified_at` nulo/preenchido;
  `aprovacao_pendente` true/false; `ativo` true/false; `origem` em cada partição; `deleted_at` preenchido.
- `Tenant::factory()->comIdentidadeVisual('#7c3aed')` e `duasOrganizacoes()` (já em `tests/Pest.php`).
- Logo de organização: arquivo real em `Storage::fake('public')` **e** um `logo` apontando para caminho
  inexistente — `Tenant::urlDaLogo():138` confere `exists()` antes.

### Fakes

- `Log::spy()` no canal `autenticacao` via `espiarAutenticacao()` (já existe em `tests/Pest.php`) para o
  rastreio de efeito de R5.
- Nenhum `Queue::fake`/`Mail::fake`: a entrega não despacha job nem envia e-mail.

### Estratégia de DB e de suíte

| Cenário | Suíte | Por quê |
|---|---|---|
| tudo de `/admin`, arquitetura, CSS, docs, constraint | `tests/Kit` | single-tenant; `admin` e `infra` existem |
| tudo que use `admin_app` ou organização | `tests/Tenancy` | `admin_app` **só** é semeado no ramo de tenancy; `TenancyTestCase` fixa `permission.teams` em `createApplication()` |
| tema, console, acessibilidade, scroll | `tests/Browser` / `tests/BrowserTenancy` | ver `05` |

### Helpers novos — **vão para `tests/Pest.php`**, não para o arquivo de teste

`.ai/rules/testes.md` §*"Helper de teste usado por mais de um arquivo vive em `tests/Pest.php`"*: os três abaixo
são usados por 4+ arquivos desta entrega. Declará-los num `*Test.php` passa no run completo e estoura
`Call to undefined function` em `--parallel`, em `--tia` e ao rodar o arquivo isolado — e
`tests/Kit/HelpersDeTesteTest.php` fica vermelho.

| Helper | Assinatura proposta | Papel |
|---|---|---|
| `regiaoDoHeader` | `regiaoDoHeader(string $html, string $classe = 'fph-root'): string` | recorte **balanceado** de `<div>`: abre no elemento que carrega a classe, conta `<div`/`</div>` e devolve só o interior. É o antídoto do defeito da rodada anterior |
| `classesDoVendorPageHeader` | `classesDoVendorPageHeader(): array{emitidas: list<string>, definidas: list<string>}` | lê `vendor/mortalkiller/filament-page-header/resources/{views,css}` em **runtime** |
| `semComentarios` | **já existe** — `tests/Kit/AderenciaAoBlueprintTest.php:55-60` (`preg_replace('#/\*.*?\*/#s')` + `#^\s*//.*$#m`) | tira comentário antes de toda asserção de **ausência** sobre fonte |

> **Atenção, e é uma armadilha nomeada pela rule**: `semComentarios()` hoje está declarado **dentro** de
> `AderenciaAoBlueprintTest.php`, e ali está certo — um arquivo só o usa. CT-26, CT-28, CT-41 e CT-42 o tornam
> **cruzado**, e nesse momento ele precisa **mudar de casa** para `tests/Pest.php`.
> **Não crie `semComentarioPhp()` ao lado dele.** `.ai/rules/testes.md` é explícita: clone com outro nome para
> escapar da colisão de redeclaração *"troca um erro que estoura por duas funções idênticas que ninguém
> percebe"* — e cita os precedentes (`pivotDePapeisDaOrganizacao()`, `entrarNoPainelDa()`). Mova a existente e
> use uma só. `tests/Kit/HelpersDeTesteTest.php` usa `token_get_all()` e fica vermelho se isto for ignorado.

---

## Regra R1 — o recorte de região do header é exato

> meta-regra · área C · técnica: **controle negativo de detector**

**Por que esta regra existe e vem primeiro.** Na rodada anterior desta base um predicado foi aplicado sobre uma
região grande demais do HTML e **qualquer** texto o satisfazia: 46 de 46 verdes sobre nada. Toda asserção de
R2, R3, R7 e R11 roda **dentro** de uma região, e a região é produzida por um extrator. Extrator não testado é
o oráculo nascendo inerte outra vez.

**As regiões, e o que cada uma contém** (medido no vendor, v2.1.5):

| Região | Marcação exata | Contém |
|---|---|---|
| `fph-root` | `<div class="fph-root" data-fph-root …>` (`header.blade.php:13`) | o header inteiro: `fph-header` > `fph-schema` > `fph-layout` + `fph-actions` |
| `fph-heading` | `<h1 … class="fi-header-heading fph-heading">` (`components/heading.blade.php:3`) | só o título — **sem divs aninhadas** |
| `fph-avatar` | `<div class="fph-avatar…">` (`layout.blade.php:25` e `:33`) | `<img src alt width height>` **ou** `<span aria-hidden>` — **sem divs aninhadas** |
| `fph-badges` | `<div class="fph-slot fph-inline fph-badges" …>` (`layout.blade.php:46`) | markup do Filament, **com** divs aninhadas → exige recorte balanceado |
| `fph-metadata` | `<div class="fph-slot fph-metadata" …>` (`layout.blade.php:60`) | idem |
| *fora* | `fi-topbar`, `fi-sidebar`, `<title>`, widgets do `ViewTenant` | é o que o extrator **tem** de deixar de fora |

```gherkin
# language: pt
Funcionalidade: Recorte da região do header

  Regra: o extrator devolve o interior do elemento pedido, e só ele

    Esquema do Cenário: [CT-01] o recorte separa o que está dentro do que está fora
      Dado um HTML sintético com a sentinela "SENTINELA-FORA" antes e depois do `fph-root`
      E a sentinela "SENTINELA-DENTRO" no interior de "<regiao>"
      Quando a pessoa que escreve o teste recorta a região "<regiao>"
      Então o recorte contém "SENTINELA-DENTRO"
      E o recorte não contém "SENTINELA-FORA"
      E o recorte não contém o conteúdo do irmão "<irmao>"

      Exemplos:
        | regiao       | irmao        | # o que prova                                  |
        | fph-root     | fi-topbar    | o header inteiro, sem barra superior           |
        | fph-heading  | fph-badges   | elemento simples, irmão adjacente              |
        | fph-avatar   | fph-main     | elemento simples que pode estar ausente        |
        | fph-badges   | fph-metadata | **com divs aninhadas** — o contador balanceado |
        | fph-metadata | fph-summary  | idem, e é o último irmão da região             |

    Cenário: [CT-02] região ausente devolve vazio, e o caso que a usa reprova
      Dado um HTML sem nenhum elemento com a classe "fph-badges"
      Quando a pessoa que escreve o teste recorta a região "fph-badges"
      Então o recorte é a string vazia
      E uma asserção de presença sobre esse recorte reprova
      E uma asserção de **ausência** sobre esse recorte é declarada inválida no próprio caso
```

**A regra de uso que CT-02 institui, e que vale para o `04` inteiro:** asserção de **ausência** sobre uma
região só vale quando o caso **antes** prova que a região não está vazia. Região vazia satisfaz toda ausência —
é a versão local do *"não-efeito só discrimina se o mundo tiver destinatário"*.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o extrator usa `strpos` da classe e devolve o **resto do documento** (foi o defeito de 46/46) | CT-01, linha `fph-root` (a sentinela depois do header entraria) |
| M2 | o extrator usa regex não-guloso `(.*?)</div>` e **corta no primeiro** `</div>` aninhado | CT-01, linhas `fph-badges` e `fph-metadata` |
| M3 | o extrator devolve o **documento inteiro** quando não acha a classe (falha aberto) | CT-02 |
| M4 | o extrator casa a classe por substring e `fph-heading` também casa `fph-heading-icon` | CT-01, linha `fph-heading` (o ícone é filho, a sentinela do irmão não pode entrar) |

---

## Regra R2 — nas seis telas em escopo o cabeçalho é o do pacote; fora delas, o nativo

> `RQ-02`, `RQ-03` · área C · perfil **padrão** · técnica: **EP por tela + controle negativo**

As seis telas do escopo, e o painel de cada uma:

| # | Tela | Painel | Rota |
|---|---|---|---|
| 1 | `EditUser` | `/admin` | `/admin/users/{record}/edit` |
| 2 | `ViewUser` (**nova**) | `/admin` | `/admin/users/{record}` |
| 3 | `EditUser` | `/app` | `/app/{tenant}/users/{record}/edit` |
| 4 | `ViewUser` (**nova**) | `/app` | `/app/{tenant}/users/{record}` |
| 5 | `EditTenant` | `/admin` | `/admin/tenants/{record}/edit` |
| 6 | `ViewTenant` | `/admin` | `/admin/tenants/{record}` |

```gherkin
# language: pt
Funcionalidade: O cabeçalho rico nas telas de registro

  Regra: as seis telas em escopo renderizam o header do pacote, com a identidade do registro

    Esquema do Cenário: [CT-03] o header do pacote substitui o nativo nas telas em escopo
      Dado um administrador autenticado no painel "<painel>"
      E um registro alvo com nome conhecido
      Quando ele abre "<tela>"
      Então a resposta é 200
      E a página contém um elemento com a classe "fph-root"
      E a região "fph-heading" contém o nome do registro
      E a região "fph-heading" não contém o nome da aplicação

      Exemplos:
        | painel | tela            | # partição                                   |
        | admin  | EditUser        | Edit, painel sem tenancy                     |
        | admin  | ViewUser        | View, tela nova                              |
        | app    | EditUser        | Edit, painel com tenancy                     |
        | app    | ViewUser        | View, tela nova, painel com tenancy          |
        | admin  | EditTenant      | outro model, outra classe de header          |
        | admin  | ViewTenant      | outro model + widgets + relation managers    |

    Cenário: [CT-04] painel sem o plugin continua com o cabeçalho nativo
      Dado um usuário de infraestrutura autenticado no painel "/infra", que não registra o plugin
      E que o mesmo processo já renderizou uma tela do "/admin" **com** "fph-root" neste cenário
      Quando ele abre a tela de visualização de uma execução de IA
      Então a resposta é 200
      E a página exibe o título da execução no cabeçalho nativo do Filament
      E a página não contém nenhum elemento com a classe "fph-root"
      E a página não contém o "<link>" da folha de estilo do pacote

    Cenário: [CT-05] a folha de estilo do pacote não é servida no painel sem plugin
      Dado um usuário autenticado que alcança "/admin" e "/infra"
      Quando ele abre o painel "/admin" e depois o painel "/infra"
      Então a resposta do "/admin" contém um "<link>" para "css/mortalkiller/filament-page-header"
      E a resposta do "/infra" não contém esse "<link>"

    Cenário: [CT-06] o header mostra este registro, não o de outra pessoa nem o de quem olha
      Dado duas contas na mesma instalação, "Ana Prado" e "Bruno Sales"
      E um administrador chamado "Rita Nunes", com a permissão de ficha, autenticado no painel "/admin"
      Quando ele abre a tela de visualização da conta de "Ana Prado"
      Então a região "fph-heading" contém "Ana Prado"
      E a região "fph-heading" não contém "Bruno Sales"
      E a região "fph-metadata" contém o e-mail de "Ana Prado"
      E a região "fph-metadata" não contém o e-mail de "Bruno Sales"
      E a região "fph-metadata" não contém o e-mail de "Rita Nunes"
```

**Por que CT-04 e CT-05 são um par e não um só.** CT-04 prova que o header **não** aparece; CT-05 prova que a
**CSS** não é servida. São mutantes distintos: registrar o plugin no `/infra` (ADR-01 recusou) produziria
`<link>` sem header em toda página — CT-04 continuaria verde.

**Destinatário real das ausências.** CT-04 afirma ausência de `fph-root` num processo que **já renderizou** um
`fph-root` no mesmo cenário (o `Dado`). Sem essa linha, a ausência passaria com o pacote inteiro desinstalado.
CT-05 idem, por comparação entre duas respostas no mesmo cenário.

```gherkin
    Cenário: [CT-47] o render hook da folha de estilo é escopado por painel
      Dado os painéis "/admin", "/app" e "/infra" registrados
      Quando a pessoa que escreve o teste percorre os três painéis no mesmo processo
      Então o hook "STYLES_AFTER" emite a folha do pacote em "/admin" e em "/app"
      E não a emite em "/infra"

    Cenário: [CT-48] cada painel recebe a própria instância do plugin
      Dado os painéis "/admin" e "/app", cada um com o plugin registrado
      Quando a pessoa que escreve o teste compara as duas instâncias
      Então elas são objetos distintos
      E configurar opções na instância de um painel não altera as do outro
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | `use HasPageHeader` esquecido em **uma** das seis páginas | CT-03, a linha daquela tela |
| M6 | o plugin registrado só em `AdminPanelProvider` | CT-03, linhas `app` |
| M7 | a classe de header nomeada pelo **Resource** (`UserResourceHeader`) em vez de pelo **model** — a convenção do vendor é `class_basename(getModel())` | **CT-49** (slots vazios nas três telas de Edit) e CT-37/CT-38 (nas de View). **Não** CT-03: o heading é herdado do Filament e sobrevive à classe ausente |
| M8 | o plugin registrado nos **três** painéis, "por simetria" (ADR-01 recusou) | CT-04 + CT-05 |
| M9 | uma única instância do plugin guardada em variável e reusada nos dois providers | CT-48 |
| M10 | o render hook registrado sem `scopes:` → cai no bucket vazio e emite em todo painel | CT-47 |

---

## Regra R3 — o slot de identidade não fica vazio quando o kit tem avatar a mostrar

> `RQ-02`, `RQ-03` (invariante) + **ADR-02** (mecanismo) · área B · perfil **padrão**, técnica **escalada** ·
> técnica: **partição por esquema de URL + controle negativo**

**Este é o caso mais fácil de escrever errado do `04`, e ele precisa provar DUAS coisas separadas.** Um caso que
só afirme "o header renderiza" passa nos dois mundos — com o `data:` URI chegando (e o slot sumindo em silêncio)
e com o URL de `storage` chegando. Medido no vendor: `Header::getAvatarUrl():169-180` recusa todo esquema fora
de `http`/`https` e devolve **`null`, sem erro**; o `@elseif` de `layout.blade.php:24-31` então troca o `<img>`
por um `<span>` de iniciais calculadas pelo próprio pacote — ou, se não houver iniciais, some.

**Premissa de mecanismo declarada** (ADR-02): o avatar sai de `getFilamentAvatarUrl()`. Ela fixa **qual** cenário
escrever; não dispensa escrevê-lo. O mecanismo descartado — o `defaultAvatarProvider` do kit
(`App\Support\AvatarDeIniciais`, que devolve `data:image/svg+xml;base64,…`) — vira o **controle negativo**.

```gherkin
# language: pt
Funcionalidade: O avatar do header

  Regra: o slot de identidade do header recebe um URL que o navegador consegue abrir

    Cenário: [CT-07] o data: URI do provider do painel não chega ao <img> do header
      Dado o provider de avatar do kit ativo nos painéis, devolvendo "data:image/svg+xml;base64,…"
      E uma conta **sem** foto de perfil
      E um administrador autenticado no painel "/admin"
      Quando ele abre a tela de visualização dessa conta
      Então a página contém "data:image/svg+xml;base64," **fora** do header — no avatar da barra superior
      E a região "fph-avatar" não está vazia
      E a região "fph-avatar" não contém "data:image/svg+xml"
      E a região "fph-avatar" não contém nenhuma tag "<img>"
      E a região "fph-avatar" contém as iniciais da conta

    Cenário: [CT-08] o URL de storage chega ao <img> do header
      Dado uma conta **com** foto de perfil gravada no disco "public"
      E um administrador autenticado no painel "/admin"
      Quando ele abre a tela de visualização dessa conta
      Então a região "fph-avatar" contém uma tag "<img>"
      E o atributo "src" desse "<img>" começa por "http"
      E o atributo "src" desse "<img>" contém "/storage/"
      E o atributo "src" desse "<img>" não contém "data:"
      E o atributo "alt" desse "<img>" contém o nome da conta

    Esquema do Cenário: [CT-09] o esquema do URL, e o nome, decidem se o slot sobrevive
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E uma conta cujo nome é "<nome>" e cuja coluna de avatar contém "<avatar>"
      Quando ele abre a ficha dessa conta
      Então a região "fph-avatar" resulta em "<resultado>"
      E a região "fph-root" não está vazia

      Exemplos:
        | avatar                          | nome        | resultado          | # partição                          |
        | avatars/a.png (arquivo existe)  | Ana Prado   | img com src http   | válida — é o que o kit produz        |
        | avatars/a.png sob disco https   | Ana Prado   | img com src https  | válida                               |
        | (a conta usa o provider do painel)| Ana Prado | iniciais, sem img  | **o defeito do ADR-02** — ver CT-07  |
        | (nulo)                          | Ana Prado   | iniciais, sem img  | ausente — queda desejada             |
        | (string vazia)                  | Ana Prado   | iniciais, sem img  | **vazio ≠ ausente**, mesmo destino   |
        | (nulo)                          | (vazio)     | **região ausente** | **sem avatar E sem iniciais** — o único caminho em que o slot some |

    Cenário: [CT-10] a organização sem logo no disco cai nas iniciais tingidas
      Dado uma organização cuja coluna de logo aponta para um arquivo que não existe no disco
      E que a organização tem cor primária "#7c3aed"
      E um administrador autenticado no painel "/admin"
      Quando ele abre a tela de visualização dessa organização
      Então a região "fph-avatar" não contém nenhuma tag "<img>"
      E a região "fph-avatar" contém as iniciais do nome da organização
      E o atributo "style" da região "fph-avatar" não está vazio
```

**Por que CT-07 tem a linha "contém `data:` fora do header".** É o que torna a ausência **não-vácua**: no mesmo
HTML existe um `data:image/svg+xml` real (o avatar da barra superior, do `AvatarDeIniciais`). Sem essa linha, a
ausência dentro de `fph-avatar` passaria com o provider desligado, com o pacote desinstalado e com a página em
branco.

**Por que CT-09 é `Esquema` e não cinco cenários.** É uma partição de domínio de um campo. Conta como **1**
cenário no teto do perfil.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `->avatar(fn () => Filament::getUserAvatarUrl($record))` — o reflexo natural, e o defeito que a ADR-02 nomeia | CT-07 (o `<img>` sumiria mas as iniciais apareceriam; a linha "não contém `<img>`" **passaria**) → **matador real é CT-08**, que exige `<img>` com `/storage/` quando há foto |
| M12 | `->avatar()` passando o **caminho relativo** (`avatars/a.png`) em vez do URL | CT-08 (`src` não começaria por `http`) |
| M13 | `->initials()` esquecido → conta sem foto fica com o slot de identidade **ausente** | CT-07 (região vazia) |
| M14 | o avatar da organização passado sem conferir `exists()` → `<img>` quebrado | CT-10 |
| M15 | `->avatar()` que devolve sempre o avatar de **quem está autenticado**, não do registro | ⚠️ **sem matador direto** — CT-08 usa um alvo que não é o ator, mas só compara o esquema do URL. **Lacuna declarada**: fechar com uma linha em CT-08 afirmando que o `src` contém o identificador do arquivo **do alvo**, e não o do ator. Tentado e mantido como lacuna porque exige duas contas **ambas com foto**, e o nome do arquivo depende do gerador de upload |

---

## Regra R4 — a `ViewUser` só abre para quem tem `View:User`

> **`RQ-13`** · área A · perfil **completo** · técnica: **matriz papel × operação + par positivo/negativo**

**O ponto que a rodada anterior errou nesta classe de caso.** `View:User` **já existe no banco hoje** e **já é
distribuída** (`config/filament-shield.php:183` + matriz do `PapeisSeeder`). O que não existe é a **tela
consultando**. Logo, o lado positivo sozinho — "com a permissão, 200" — fica **verde numa tela que não consulta
permissão nenhuma**. O oráculo é obrigatoriamente o **par**.

### Matriz papel × alvo — refeita depois da revisão adversarial

> **A matriz anterior estava errada em três frentes, e a revisão as nomeou.** (a) A aritmética somava *linhas de
> `Exemplos:`* com *células* — "4 válidas + 3 inválidas + 13 não se aplica" não fecha 20. (b) Duas células de
> `master_global` apontavam **CT-11**, que nunca autentica um `master_global` e nunca abre listagem — célula
> fantasma, e ainda contada como "válida exercitada" justamente na linha que o documento declara ser **controle,
> nunca prova**. (c) O eixo estava errado: com 3 das 4 operações fora de escopo, 15 das 20 células eram
> decoração, enquanto **painel** e **conta própria** — os eixos que de fato discriminam esta entrega — não
> existiam.

O eixo refeito é **papel × alvo**, com o painel como terceira dimensão explícita. A operação é uma só —
`View:User`, que é o que a entrega cria. **5 papéis × 3 alvos = 15 células.**

| Papel (painel de origem) | conta **alheia** | conta **própria** | conta que **governa a instalação** |
|---|---|---|---|
| `master_global` | não se aplica: vence pelo `Gate::before` — **controle, nunca prova** | idem | idem |
| `admin` (`/admin`) | ✅ **CT-11** (tem) / ❌ **CT-11** (revogada) | **CT-56** | não se aplica: no `/admin` a ação é legítima |
| `admin_app` (`/app`) | ✅ **CT-15** (tem) / ❌ **CT-15** (revogada) | **CT-56** | ❌ **CT-17**, **CT-19** |
| `panel_user` (`/app`) | ❌ **CT-15**, linha `panel_user` | **CT-56** | ❌ coberta por CT-15 (nunca recebe a permissão) |
| `infra` (`/infra`) | ❌ **CT-55** — o papel existe e autentica; `View:User` é global, **não por painel** | **CT-55**, segunda linha | não se aplica: o alvo não é alcançável do `/infra` |

- **Total: 15 células.** Exercitadas: **11** (CT-11 ×2 polaridades numa célula, CT-15 ×3 células, CT-17/CT-19,
  CT-55 ×2, CT-56 ×3). `não se aplica`: **4**, com motivo na própria célula.
- **Cobertura de prova** (descontando `master_global`, que é controle): 11 de 12 células que provam algo.
- **Legenda, e ela é auditada célula a célula**: ❌ = **403 ou 404** **e** o nome do alvo ausente do corpo **e**
  nenhum `fph-root`. Os **três**, em toda célula ❌ — foi a conferência dessa legenda que achou CT-15 entregando
  só dois (correção aplicada no Gherkin).
- **A coluna "conta própria" é dimensão nova, trazida pela revisão.** Ela não estava em lugar nenhum do `04`, e
  é onde `View:User` costuma ser contornada com um `if ($record->is($user))`. O requisito não a decide → premissa
  por **falha fechado** (nega), com a pergunta nº 5 registrada.
- **A célula `infra`** saíra como "não se aplica: `User` não é do painel `/infra`". A revisão mostrou que isso
  confunde *"o Resource não está registrado no `/infra`"* com *"o papel `infra` não alcança a tela do `/admin`"*
  — afirmações diferentes, e a segunda é falsa por construção. Virou CT-55.
- **CT-12 e CT-14 não são células desta matriz** (saída do erro e visibilidade da ação, não par papel × alvo).
  Ficam listados em R4 fora dela, de propósito.

```gherkin
# language: pt
Funcionalidade: Permissão da tela de visualização de conta

  Regra: abrir a ficha de uma conta exige a permissão View:User

    Esquema do Cenário: [CT-11] a tela consulta a permissão, nos dois sentidos
      Dado o papel "admin" semeado pela matriz do kit
      E que esse papel "<estado>" a permissão "View:User"
      E um administrador com esse papel, autenticado no painel "/admin"
      E uma conta alvo chamada "Ana Prado", que não é a dele
      Quando ele abre a ficha dessa conta
      Então a resposta é "<status>"
      E a página "<contem>" um elemento com a classe "fph-root"
      E a página "<contem>" o nome "Ana Prado" no corpo

      Exemplos:
        | estado | status | contem     | # lado                                  |
        | tem    | 200    | contém     | positivo — sozinho não prova nada        |
        | não tem| 403    | não contém | **negativo — é ele que mata o mutante**  |

    Cenário: [CT-12] o 403 tem saída
      Dado um administrador cujo papel não tem "View:User"
      E que ele alcança a listagem de contas do painel "/admin"
      Quando ele recebe 403 ao abrir a ficha de uma conta
      Então a listagem de contas continua respondendo 200 para ele
      E o painel "/admin" continua respondendo 200 para ele

    Cenário: [CT-13] a barreira existe fora da tela
      Dado um administrador cujo papel não tem "View:User"
      E uma conta alvo
      Quando a autorização de leitura é consultada **direto**, sem passar pela página
      Então ela nega
      E consultá-la com o papel que tem a permissão permite

    Cenário: [CT-14] a ação de ficha some para quem não pode abri-la
      Dado um administrador cujo papel não tem "View:User"
      E a listagem de contas do painel "/admin" carregada, com pelo menos uma conta
      Quando ele olha as ações da linha
      Então a ação de ficha está oculta
      E, com a permissão devolvida ao papel, a mesma ação está visível

    Esquema do Cenário: [CT-15] a mesma barreira no painel /app
      Dado uma organização "Acme" com duas contas vinculadas, uma delas de "Ana Prado"
      E uma pessoa com o papel "<papel>" nessa organização, que não é "Ana Prado"
      E que a matriz de papéis deixa esse papel "<estado>" a permissão "View:User"
      Quando ela abre a ficha da conta de "Ana Prado"
      Então a resposta é "<status>"
      E a página "<contem>" um elemento com a classe "fph-root"
      E a página "<contem>" o nome "Ana Prado" no corpo

      Exemplos:
        | papel      | estado           | status | contem     | # partição                              |
        | admin_app  | com              | 200    | contém     | administra a organização                 |
        | admin_app  | sem (revogada)   | 403    | não contém | a barreira no /app                       |
        | panel_user | sem (por bloco)  | 403    | não contém | **nunca recebe**: subtraída por FQCN em `permissoesDeAdministracaoDoApp()`. Não é revogação do caso — é o estado semeado |
```

**Armadilha de suíte que CT-15 obriga a respeitar** (`.ai/rules/testes.md`, duas linhas distintas):
1. `admin_app` **só existe em `tests/Tenancy`** — em `tests/Kit` o caso morre no arranjo com `RoleDoesNotExist`.
2. `noPainelBootado('app')` **não funciona** no `/app`: `BreezyCore::boot()` lê `route()->parameter()` e morre
   com *"Call to a member function parameter() on null"*. O caminho que funciona é um `GET` real pelo kernel
   **antes** do primeiro `Livewire::test()`, e então `noPainelDa($acme)`. CT-15 é escrito em HTTP justamente por
   isso; CT-14, que é Livewire, roda no `/admin`.

**`semAPermissao()` é variádico e devolve o `Role`** — `semAPermissao('admin', 'View:User')`. Ele já faz o
`forgetCachedPermissions()`, que sem isso deixaria o caso verde medindo cache.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | a `ViewUser` nasce sem consultar permissão — `ViewRecord` sem `authorizeAccess` alcançável, ou `canView()` sobrescrito devolvendo `true` | CT-11 linha negativa |
| M17 | `canView()` sobrescrito **sem** `&& parent::canView()` — desliga a policy | CT-11 linha negativa + CT-13 |
| M18 | a permissão é consultada no `mount()` e **não** no `hydrate()` — muda o papel entre requisições Livewire e a tela continua aberta | ⚠️ **sem matador** — exigiria revogar a permissão entre duas requisições Livewire da mesma página. **Lacuna declarada**: tentado com `semAPermissao()` entre `Livewire::test()` e um `->call()`; o cache de permissão do spatie é por processo e o `forgetCachedPermissions()` do helper o invalida, mas o `ViewRecord::hydrate()` só roda em atualização real do componente. Candidato a CT-B |
| M19 | o `ViewAction` aparece para todo mundo (`Action` nasce com autorização `null` = liberada) | CT-14 |
| M20 | a barreira mora **só** no `Resource`, e a policy nunca decide | CT-13 |
| M21 | a tela nega **todo mundo** (o inverso, que passaria num conjunto só com o lado negativo) | CT-11 linha positiva |

---

## Regra R5 — no `/app` a ficha não alcança conta de outra organização nem quem governa a instalação

> `RQ-02` + **ADR-07** · área F · perfil **completo** · técnica: **matriz + camada externa à UI + rastreio de efeito**

```gherkin
# language: pt
Funcionalidade: Fronteira da ficha de conta no painel da organização

  Regra: a ficha do /app só alcança conta da organização corrente, e nunca quem governa a instalação

    Cenário: [CT-16] URL direta para conta de outra organização não abre
      Dado duas organizações, "Acme" e "Globex"
      E uma pessoa que administra a "Acme" e não pertence à "Globex"
      E uma conta "Carla Dias" vinculada apenas à "Globex"
      Quando ela abre, pela URL, a ficha de "Carla Dias" dentro do caminho da "Acme"
      Então a resposta é 404
      E o corpo não contém "Carla Dias"
      E o corpo não contém nenhum elemento com a classe "fph-root"
      E a mesma pessoa, abrindo a ficha de um colega da "Acme", recebe 200 com "fph-root"

    Cenário: [CT-17] a negativa de ficha de quem governa a instalação é decidida fora da tela
      Dado "Marta Reis", que governa a instalação e também pertence à organização "Acme"
      E "Léo Braga", administrador da "Acme", autenticado como ator
      E o espião do canal de autenticação instalado, sem nenhum registro
      Quando a resposta de autorização de ficha do recurso do "/app" é consultada direto, com "Marta Reis" como alvo
      Então ela nega
      E exatamente um aviso é registrado no canal de autenticação
      E esse aviso traz o identificador de "Marta Reis" e o de "Léo Braga"
      E esse aviso não contém o e-mail nem o nome de nenhum dos dois

    Cenário: [CT-18] com organização a consulta recorta; sem organização ela fecha
      Dado duas organizações, "Acme" e "Globex", cada uma com uma conta própria
      E o espião do canal de autenticação instalado
      Quando a consulta base do recurso de contas do "/app" é executada com a "Acme" corrente
      Então ela devolve exatamente a conta da "Acme"
      E não devolve a conta da "Globex"
      E nenhum aviso é registrado no canal de autenticação
      E a mesma consulta, sem organização corrente, devolve zero registros e registra um aviso

    Cenário: [CT-19] a ficha não é mais permissiva que a edição
      Dado "Marta Reis", que governa a instalação e pertence à organização "Acme"
      E "Pedro Lima", colega comum da "Acme", que não governa a instalação
      E "Léo Braga", administrador da "Acme", autenticado como ator
      E o espião do canal de autenticação instalado, sem nenhum registro
      Quando as respostas de autorização de ficha e de edição são consultadas direto, para os dois alvos
      Então as duas negam para "Marta Reis"
      E as duas permitem para "Pedro Lima"
      E nenhum aviso é registrado no canal de autenticação para "Pedro Lima"
      E dois avisos são registrados para "Marta Reis" — um por verbo
```

**Por que CT-17 e CT-19 são consultados "direto" e nunca pela tela.** ADR-07 é explícita, e a razão é medida: o
`getEloquentQuery()` já recorta por `User::queNaoGovernamAInstalacao()`, então o alvo some da listagem e o route
binding devolve **404 antes de a policy ser consultada**. Um caso que passe pela tela mede o **recorte da
query** — ficaria verde com a sobrescrita inteira removida. É exatamente o falso ✅ que
`.ai/rules/resources.md` nomeia.

**Rastreio de efeito da negativa — corrigido pela revisão adversarial.** O efeito é **um**: o `warning` no canal
`autenticacao`. As quatro direções, e agora **todas estão no Gherkin**, não só no texto:

| Direção | Onde | Antes da revisão |
|---|---|---|
| aconteceu | CT-17, "exatamente um aviso" | ✅ |
| **não** aconteceu quando a autorização permite | **CT-19**, linha de "Pedro Lima"; **CT-18**, ramo com organização | ❌ **ausente** — CT-19 não mencionava log e seus dois ramos negavam. O mutante *"o `warning` é emitido na entrada do método, antes do ramo"* atravessava intacto, e a trilha que o `/infra` lê viraria ruído |
| aconteceu **uma só vez** | CT-17 ("exatamente um"), CT-19 ("dois — um por verbo") | ❌ era "um aviso é registrado", que passa com dez |
| o que **não** pode estar nele (PII) | CT-17, última linha | ✅ |

**Destinatário real em todas as quatro**: `espiarAutenticacao()` instala o espião, o canal existe e o `/infra` lê
a trilha. A linha de ausência de CT-19 roda num mundo em que o caminho irmão (CT-17) **grava** — sem isso, seria
ausência em mundo vazio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | `getViewAuthorizationResponse()` não criada — a View fica com uma camada só | CT-17 |
| M23 | `canView()` devolvendo `false` em vez de `Response::deny()` — no Filament 5 o `can*()` **lê** a resposta, não a define | CT-17 |
| M24 | o recorte escrito só na `table()`, não no `getEloquentQuery()` — o route binding deixa de filtrar | CT-16 |
| M25 | sem organização corrente a query **não** aplica `where` e devolve a base inteira (falha aberto) | CT-18 |
| M26 | o log grava o e-mail do alvo no contexto | CT-17, última linha |
| M57 | **(revisão adversarial)** o `warning` é emitido na **entrada** do método, antes do ramo — todo acesso legítimo vira aviso e a trilha do `/infra` vira ruído | CT-19, linha de "Pedro Lima"; CT-18, ramo com organização |
| M58 | **(revisão adversarial)** a resposta confere o **ator** em vez do **alvo** — nega para quem governa a instalação quando é ele quem olha, e libera quando ele é o olhado | CT-17 e CT-19, que agora nomeiam ator e alvo como pessoas distintas |

---

## Regra R6 — a superfície Livewire nova não entrega mais do que a tela mostra

> `02` → `## Superfície Livewire` · área D · perfil **padrão**, **Impacto 3** · técnica: **varredura linha a linha**

A tabela do `02` tem **7 linhas**. Cada uma é gatilho de cenário; linha sem cenário é lacuna declarada. O trait
`HasPageHeader` traz **9 métodos públicos**, e método de trait conta como declarado pela classe que o usa
(`HandleComponents.php:570-580` só subtrai `render`) — logo os nove viram ação chamável por `$wire.` em **seis
páginas**.

| Linha do `02` | Cenário |
|---|---|
| `getPageHeaderRecord()` | **CT-20** (a medição em aberto) |
| `getPageHeaderSchemaClass()` | CT-23 (varre os nove de uma vez) |
| `getPageHeaderOptions()` / `pageHeaderIsEnabled()` / `getPageHeaderComponent()` / `getHeader()` | CT-23 |
| `headerSchema(Schema)` / `defaultHeaderSchema(Schema)` / `pageHeaderOptions(HeaderOptions)` | **CT-24** — a negativa do `02` ("não alcançáveis na prática") escrita **como se fosse falsa** |
| `ViewRecord::$data`, público **sem** `#[Locked]` | **CT-22** |
| `$record`, com `#[Locked]` | **CT-21** |
| `data-fph-options` no DOM | **CT-25** |

```gherkin
# language: pt
Funcionalidade: A superfície que o navegador alcança

  Regra: o que a página devolve ao navegador não excede o que a tela mostra, e estado forjado não é aceito

    Cenário: [CT-20] o que getPageHeaderRecord() de fato devolve ao navegador
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E uma conta alvo com origem "google", aprovação pendente e um registro de exclusão lógica
      E o componente da ficha dessa conta montado
      Quando o método público que devolve o registro do header é chamado como ação
      Então a lista de chaves devolvidas tem pelo menos uma chave
      E a lista contém "name" e "email" — as que a tela mostra
      E a lista não contém "password" nem "remember_token"
      E a lista é exatamente a lista nominal fixada neste cenário
      E toda chave dessa lista que não aparece no infolist da tela está nomeada aqui, com o motivo de ficar

    Cenário: [CT-21] o identificador do registro não aceita troca pelo navegador
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E duas contas, "Ana Prado" e "Bruno Sales"
      E o componente da ficha de "Ana Prado" montado
      Quando o navegador tenta fixar o identificador do registro no de "Bruno Sales"
      Então a troca é recusada
      E a região "fph-heading" continua contendo "Ana Prado"
      E a região "fph-heading" não contém "Bruno Sales"

    Cenário: [CT-22] o estado do formulário da ficha não grava nada
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E uma conta alvo com nome "Ana Prado" e e-mail conhecido
      E o componente da ficha dessa conta montado
      Quando o navegador fixa o estado do formulário com nome "INJETADO" e um papel de administrador
      Então o nome gravado da conta continua "Ana Prado"
      E a conta continua sem o papel injetado
      E a região "fph-heading" contém "Ana Prado"
      E a região "fph-heading" não contém "INJETADO"

    Esquema do Cenário: [CT-23] método público do trait, chamado como ação, não derruba nem vaza
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E o componente da ficha de uma conta montado
      Quando o navegador chama o método público "<metodo>" como ação
      Então a resposta não é erro de servidor
      E nenhuma exceção não tratada é lançada
      E o componente continua renderizando a região "fph-heading" com o nome da conta
      E a carga devolvida ao navegador não contém "<proibido_na_carga>"
      E a carga devolvida ao navegador cabe abaixo do teto declarado neste cenário

      Exemplos:
        | metodo                      | proibido_na_carga | # o que arrisca                                  |
        | getPageHeaderSchemaClass    | (caminho de disco)| devolve class-string — FQCN é aceitável, path não |
        | getPageHeaderOptions        | (nome do registro)| devolve objeto de opções                          |
        | pageHeaderIsEnabled         | (nada)            | devolve booleano                                  |
        | getPageHeaderComponent      | (nome do registro)| devolve componente ou nulo                        |
        | getHeader                   | `<div`            | **devolve uma View** — se serializar, o HTML inteiro do header entra no JSON |

    Esquema do Cenário: [CT-24] método que recebe objeto tipado recusa argumento vindo do navegador
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E o componente da ficha de uma conta montado
      Quando o navegador chama "<metodo>" passando "<argumento>"
      Então a chamada é recusada
      E o corpo da recusa não é vazio
      E o corpo da recusa não contém caminho de arquivo nem rastro de pilha
      E o registro da conta não é alterado
      E a ficha continua respondendo 200 com a região "fph-heading" preenchida

      Exemplos:
        | metodo             | argumento                | # partição            |
        | headerSchema       | "texto"                  | tipo errado           |
        | headerSchema       | {"record": 1}            | objeto arbitrário     |
        | pageHeaderOptions  | []                       | array vazio           |
        | defaultHeaderSchema| null                     | ausente               |

    Cenário: [CT-25] o atributo de opções no DOM não carrega dado do registro
      Dado uma conta alvo cujo e-mail é "ana@example.com"
      E um administrador autenticado no painel "/admin"
      Quando ele abre a ficha dessa conta
      Então a página contém "ana@example.com" na região "fph-metadata"
      E o valor do atributo de opções do header não contém "ana@example.com"
      E o valor do atributo de opções do header não contém o nome da conta
      E o valor do atributo de opções do header não é a string vazia
```

**CT-20 é a medição que o `02` declarou em aberto, e o `Então` dele é deliberadamente "registrar".** O registro
já passou por `authorizeAccess()`, então não há escalada de privilégio — a pergunta é se o retorno traz **coluna
fora do infolist** (`origem`, `aprovacao_pendente`, `deleted_at`, `ativo`, `uuid`). Se trouxer, é **divulgação
declarada**, e a decisão vai para o `03`, não para o teste. O caso vira oráculo permanente no momento em que a
lista for fixada: `expect($chaves)->toBe([...])` (`toBe`, não `toContain` — ver a armadilha abaixo).

**CT-25 tem destinatário real**: o e-mail **está** na página (primeira linha), então a ausência dele dentro do
atributo discrimina. A última linha (`não é vazia`) é a que impede o caso de passar com o atributo inexistente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | `getHeader()` devolve uma `View` e o Livewire a serializa com o HTML inteiro do header no payload JSON | CT-23, linha `getHeader` |
| M28 | `$record` perde o `#[Locked]` numa sobrescrita da `ViewUser` | CT-21 |
| M29 | a `ViewUser` grava algo a partir de `$data` (copiar o molde de um `EditRecord`) | CT-22 |
| M30 | o schema do header injeta o registro inteiro nas opções (`data-fph-options`) para "facilitar o Alpine" | CT-25 |
| M31 | `headerSchema()` aceita argumento e explode com rastro de pilha visível | CT-24 |

---

## Regra R7 — o conteúdo do header é escapado; `html: true` não existe em `app/`

> **ADR-04** · área E1 · perfil **padrão**, **Impacto 3** · técnica: **ausência com filtro de comentário + caso comportamental**

**As duas metades não são substituíveis.** O caso de arquitetura varre o fonte e morre no dia em que alguém
escrever `Heading::make()->html()` por outro caminho; o caso comportamental prova o escape no HTML servido e
não sabe nada sobre a linha que o desligou. Os dois.

```gherkin
# language: pt
Funcionalidade: Segurança do conteúdo do header

  Regra: o título do header escapa a entrada do usuário

    Cenário: [CT-26] as três APIs proibidas não aparecem no código da aplicação
      Dado os arquivos PHP de "app/", com comentários removidos
      E que o conjunto varrido tem pelo menos 200 arquivos
      Quando a pessoa que escreve o teste procura "html: true", "hideWhenCompact" e "retainSummaryWhenCompact"
      Então nenhuma ocorrência é encontrada
      E a mesma varredura **com** os comentários encontra as citações que explicam a proibição

    Esquema do Cenário: [CT-27] o título escapa marcação e preserva texto
      Dado uma conta cujo nome é "<nome>"
      E um administrador autenticado no painel "/admin"
      Quando ele abre a ficha dessa conta
      Então a região "fph-heading" contém "<esperado>"
      E a região "fph-heading" não contém "<proibido>"

      Exemplos:
        | nome                      | esperado              | proibido            | # partição            |
        | <script>alert(1)</script> | &lt;script&gt;        | <script>            | marcação — o que mata |
        | Ana & Bruno               | &amp;                 | (nada)              | entidade simples      |
        | José Antônio              | José Antônio          | (nada)              | acento                |
        | Ana 🎯 Prado              | 🎯                    | (nada)              | emoji, 4 bytes        |
        | (256 caracteres)          | os 256 caracteres     | (nada)              | limite de varchar     |
```

**A segunda linha de CT-26 é o controle negativo da varredura.** Sem ela, um `grep` quebrado (regex errado,
diretório errado, `glob` vazio) devolveria zero ocorrências e o caso ficaria **verde sobre nada** — é o mesmo
defeito de R8, em outra vestimenta. O piso de 200 arquivos é a segunda trava.

**O filtro de comentário é obrigatório** (`.ai/rules/testes.md`): os arquivos do kit **citam** o que proíbem, e
é lá que está o porquê. `semComentarios()` (movida para `tests/Pest.php`) vale só na asserção de **ausência**; a de **presença** roda sobre o
texto cru.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | `->heading($nome, html: true)` no `UserHeader` "para pôr um `<br>` no título" | CT-26 **e** CT-27 linha `<script>` |
| M33 | a varredura roda sobre o texto cru e reprova pela própria documentação | CT-26, segunda linha (o caso inverte e acusa) |
| M34 | a varredura roda sobre um diretório vazio e passa | CT-26, piso de 200 arquivos |
| M35 | o título é montado com `Str::of()->toHtmlString()` — escape desligado por outro caminho | CT-27 linha `<script>` (CT-26 não o pega) |

---

## Regra R8 — toda classe `fph-*` emitida pelo vendor tem regra na CSS do vendor

> `01` passo 8 · área E2 · perfil **mínimo** · técnica: **leitura em runtime + piso de contagem nos dois conjuntos**

**Por que ler o vendor em runtime e nunca uma lista congelada.** O pacote já trocou API de major em 24 h. Uma
lista fixa no teste vira documentação do passado no primeiro `composer update`, e o caso continua verde. E o
**piso de contagem nos dois conjuntos** existe porque a falha natural de um extrator por regex é devolver lista
**vazia** — e "toda classe do conjunto vazio está coberta" é verdade trivial.

**Medido hoje (v2.1.5), e o `01` diverge:**

| Conjunto | Contagem | Fonte |
|---|---|---|
| classes `fph-*` emitidas pelas blades | **27** | `resources/views/**/*.blade.php`, em `class="…"` **e** em `->class([…])` |
| seletores `.fph-*` definidos na CSS | **26** | `resources/css/page-header.css` |
| **emitida sem regra** | **1 — `fph-schema`** (`header.blade.php:22`) | — |
| classes `fi-*` emitidas | **2** — `fi-section`, `fi-header-actions-ctn` | vêm da CSS compilada do Filament, não do pacote |

**O extrator precisa cobrir as duas formas de emissão.** Um regex só sobre `class="…"` encontra **24** e perde
`fph-heading`, `fph-subheading` e `fph-layout`, que saem de `ComponentAttributeBag->class([...])` — e essas três
são justamente as que o `05` usa como âncora de tema. Regex incompleto aqui produz cobertura que parece boa.

```gherkin
# language: pt
Funcionalidade: Guarda da folha de estilo do pacote

  Regra: nenhuma classe que o pacote emite fica sem regra de estilo

    Cenário: [CT-28] as classes emitidas estão cobertas, com exceção nominal
      Dado as classes "fph-" lidas em runtime das blades do pacote instalado
      E os seletores "fph-" lidos em runtime da folha de estilo do pacote instalado
      E que o conjunto de emitidas tem pelo menos 20 elementos
      E que o conjunto de definidas tem pelo menos 20 elementos
      Quando a pessoa que escreve o teste subtrai as definidas das emitidas
      Então o que sobra é exatamente o conjunto de exceções declarado no próprio caso
      E cada exceção declarada tem, ao lado, o motivo escrito

    Cenário: [CT-29] o detector acha uma agulha plantada
      Dado um par sintético de blade e folha de estilo
      E que a blade emite "fph-agulha" por atributo "class" e "fph-agulha-bag" por ComponentAttributeBag
      E que a folha de estilo não define nenhuma das duas
      Quando o mesmo extrator roda sobre esse par
      Então as duas classes aparecem como não cobertas
```

**CT-29 é o controle negativo de CT-28**, e ele não é opcional: sem ele, um extrator que devolvesse listas
vazias, ou que só lesse `class="…"`, passaria em CT-28 pelo piso e pela subtração vazia ao mesmo tempo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | o caso congela a lista de 26/27 nomes e para de ler o vendor | ⚠️ **sem matador automático** — **lacuna declarada**: nenhum caso distingue "leu o vendor" de "leu uma constante com os mesmos nomes". Mitigação escrita no caso: a lista de exceções é a **única** constante, e ela é curta o bastante para a revisão notar |
| M37 | o extrator lê só `class="…"` e perde as três de `ComponentAttributeBag` | CT-29 |
| M38 | o extrator devolve lista vazia (regex quebrado) e a subtração fica vazia | CT-28, os dois pisos |
| M39 | a exceção de `fph-schema` é aceita em silêncio, sem motivo escrito | CT-28, última linha |

---

## Regra R9 — a rota `view` é declarada antes da `edit`, e a ação navega

> `RQ-02` · área H · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt
Funcionalidade: Rota e ação da ficha

  Regra: a ficha tem rota própria, declarada antes da de edição

    Esquema do Cenário: [CT-30] a ordem das rotas no recurso
      Dado o recurso de contas do painel "<painel>"
      Quando a pessoa que escreve o teste lê as páginas declaradas
      Então a chave "view" existe
      E a chave "view" aparece antes da chave "edit"
      E o caminho declarado para "view" é "/{record}"

      Exemplos:
        | painel |
        | admin  |
        | app    |

    Cenário: [CT-31] a ação de ficha navega em vez de abrir modal
      Dado a listagem de contas do painel "/admin" carregada, com uma conta
      E um administrador com a permissão de ficha
      Quando ele olha a ação de ficha da linha
      Então ela tem um endereço de destino
      E esse endereço termina no identificador da conta, sem "/edit"

    Cenário: [CT-32] a ficha e a edição são telas distintas
      Dado uma conta alvo
      E um administrador com as duas permissões
      Quando ele abre o endereço da ficha e depois o da edição
      Então a ficha responde 200 e não contém campo de formulário editável do nome
      E a edição responde 200 e contém campo de formulário editável do nome
      E as duas contêm um elemento com a classe "fph-root"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M40 | `'view'` declarada **depois** de `'edit'` — `/{record}` deixa de casar primeiro | CT-30 |
| M41 | `ViewAction::make()` sem página `view` registrada → abre **modal** em vez de navegar | CT-31 |
| M42 | a rota `view` aponta para a `EditUser` (copiar e colar) | CT-32 |

---

## Regra R10 — o registro do pacote diz ADOTADO e onde; a dependência é caret

> `RQ-01`, `RQ-07`, `RQ-12`, `RQ-14` · área G · perfil **mínimo** · técnica: **EP + BVA de constraint**

```gherkin
# language: pt
Funcionalidade: Registro documental da adoção

  Regra: o veredito do pacote e a dependência declarada contam a mesma história

    Cenário: [CT-33] o registro de pacotes candidatos diz ADOTADO e onde
      Dado o arquivo de pacotes candidatos
      Quando a pessoa que escreve o teste lê a linha do pacote
      Então o veredito na mesma linha é "ADOTADO"
      E a linha cita um caminho dentro de "app/"
      E o arquivo não descreve o pacote como adiado em nenhuma outra linha

    Cenário: [CT-34] o oráculo da rodada anterior muda de veredito sem perder a proteção
      Dado o conjunto declarado dos dez pacotes avaliados na rodada anterior
      E que nove deles seguem com veredito de recusa
      Quando a pessoa que escreve o teste confere as dependências instaladas
      Então os nove recusados não aparecem em nenhuma seção de dependências
      E este pacote aparece na seção de dependências de produção
      E o conjunto dos nove não está vazio

    Esquema do Cenário: [CT-35] as constraints aceitam a série e recusam o major seguinte
      Dado a constraint declarada para "<pacote>"
      Quando ela é avaliada contra "<versao>"
      Então o resultado é "<resultado>"

      Exemplos:
        | pacote                              | versao  | resultado |
        | filament/filament                   | 5.8.0   | recusa    |
        | filament/filament                   | 5.8.1   | aceita    |
        | filament/filament                   | 5.8.2   | aceita    |
        | filament/filament                   | 5.99.99 | aceita    |
        | filament/filament                   | 6.0.0   | recusa    |
        | mortalkiller/filament-page-header   | 2.1.4   | recusa    |
        | mortalkiller/filament-page-header   | 2.1.5   | aceita    |
        | mortalkiller/filament-page-header   | 2.99.99 | aceita    |
        | mortalkiller/filament-page-header   | 3.0.0   | recusa    |

    Cenário: [CT-36] a doc de atualização manda ressemear
      Dado a página de atualização do projeto, em português e em inglês
      Quando a pessoa que escreve o teste a lê
      Então as duas versões citam a instalação da dependência nova
      E as duas citam a publicação de assets do Filament
      E as duas citam os dois seeders de permissões e papéis
      E as duas têm o mesmo número de seções
```

**Colisão de ID, e como resolvê-la — obrigatório ler antes de implementar R10.** `tests/Kit/PacotesRodada2Test.php`
**já tem** um `[CT-33]` e um `[CT-34]`, que pertencem à wiki `estudo-de-pacotes-rodada-2` e descrevem exatamente
estes dois comportamentos. Verificado: o dataset daquele arquivo **já declara** `'mortalkiller/filament-page-header'
=> 'ADOTAR'` (`:43`), o `[CT-34]` já varre os **nove** (`:170`) e o par positivo já existe (`:196`).

Consequência prática, em duas linhas:
1. **Não escreva casos novos** para CT-33 e CT-34 desta wiki. Eles são **os casos daquele arquivo**, e a
   aceitação é "aqueles dois, já atualizados, seguem verdes" — mais CT-35 e CT-36, que são novos.
2. **Não reuse os rótulos `[CT-33]`/`[CT-34]` num arquivo de teste desta feature.** O teste de arquitetura de
   sincronia casa `[CT-nn]` por par *(arquivo de teste, pasta da wiki)*; um `[CT-33]` num arquivo novo desta
   entrega apontaria para a wiki errada. No índice abaixo as duas linhas estão marcadas **herdado**.

**CT-35 é BVA com o incremento do tipo certo**: versão semântica, incremento na menor casa. `5.8.0` é
`piso − 1` e `2.1.4` é o `piso − 1` que a ADR-03 mediu como **quebrado** no Filament 5.8.2 — não é valor
redondo, é a versão que produz ações do cabeçalho deslocadas sem erro.

**Sobre o CHANGELOG**: a asserção vale sobre o **arquivo inteiro**, nunca sobre a primeira seção `## [`
(`.ai/rules/testes.md`: recortar o topo faz o caso expirar na entrega seguinte e reprovar uma feature que nada
tem a ver com ele).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M43 | o veredito corrigido em um dos quatro arquivos e não nos outros três | ⚠️ **sem matador** — CT-33 abre **um** arquivo, e a terceira linha dele varre outras linhas do mesmo arquivo. **Lacuna declarada** (achado D da revisão): fechar acrescentando ao `Dado` de CT-33 os quatro arquivos que replicam o veredito, ou aceitar que a reconciliação documental fica com o quality gate |
| M44 | o pacote **removido** do dataset em vez de mudar de veredito — a asserção de ausência encolhe em silêncio | CT-34, última linha (conjunto não vazio) + a linha de presença |
| M45 | `composer require` grava o **pino** `"2.1.5"` em vez de `^2.1.5` | CT-35, linha `2.99.99` |
| M46 | o piso do Filament fica em `^5.6` | CT-35, linha `5.8.0` |
| M47 | a doc `en` fica para trás da `pt` | CT-36, última linha |

---

## Regra R11 — os slots do header refletem **este** registro e mudam quando ele muda

> `RQ-02`, `RQ-03` (invariante) · conteúdo específico é **`@premissa`** — pergunta nº 1 · área C · perfil **padrão**
> · técnica: **duas tabelas de decisão 2×2 completas + EP + instante discriminante**

**Correção aplicada durante a derivação, declarada.** A primeira versão de CT-37 particionava a situação da
conta por *verificação de e-mail*. Errado: a situação é decidida por `aprovacao_pendente` × `ativo`, com
**precedência** de `Pendente` sobre `Inativo`. A partição continua saindo da estrutura que o `01` nomeia
(Pendente/Inativo/Ativo); o que mudou foi a **técnica** — de partição de enum (3 linhas, e a linha da precedência
sumindo) para **tabela de decisão 2×2 completa** (4 linhas). Escrita como estava, a tabela deixaria o caso
**vermelho contra a implementação correta**, que é o defeito que a skill mede em premissa assumida na direção
errada.

**O invariante vale em qualquer resposta à pergunta nº 1**: seja qual for o conjunto de slots, ele é lido
**deste** registro e muda quando o registro muda. Se a resposta trocar os campos, mudam as colunas de
`Exemplos:`; o invariante e CT-06 não mudam.

```gherkin
# language: pt
Funcionalidade: O conteúdo do header reflete o registro

  Regra: os slots do header são derivados do estado do registro

    @premissa
    Esquema do Cenário: [CT-37] a situação da conta aparece no badge, em toda combinação
      Dado uma conta com "aprovação pendente" igual a <pendente> e "ativo" igual a <ativo>
      E um administrador autenticado no painel "/admin"
      Quando ele abre a ficha dessa conta
      Então a região "fph-badges" contém "<rotulo>"
      E a região "fph-badges" não contém nenhum dos outros dois rótulos de situação
      E a região "fph-badges" não está vazia

      Exemplos:
        | pendente | ativo | rotulo   | # linha da tabela de decisão                          |
        | true     | true  | Pendente | 1 de 4 — pendente vence, mesmo com a conta ativa       |
        | true     | false | Pendente | 2 de 4 — **a precedência: uma ordem trocada dá "Inativo"** |
        | false    | false | Inativo  | 3 de 4                                                 |
        | false    | true  | Ativo    | 4 de 4 — o caminho feliz                               |

    @premissa
    Esquema do Cenário: [CT-38] a origem da conta aparece traduzida, não crua
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E uma conta cuja coluna de origem contém "<origem>"
      Quando ele abre a ficha dessa conta
      Então a região "fph-metadata" contém "<rotulo>"
      E a região "fph-metadata" não contém "<proibido>"
      E a região "fph-metadata" não está vazia

      Exemplos:
        | origem   | rotulo    | proibido  | # partição                                   |
        | interno  | Interno   | "interno" | default da coluna                             |
        | google   | Google    | "google"  | social                                        |
        | convite  | Convite   | "convite" | fluxo de convite                              |
        | (nulo)   | Interno   | (nada)    | **ausente cai no default declarado**, não em vazio |

    > **Os quatro rótulos ficam fixados aqui, não "o rótulo do kit".** A revisão adversarial acusou o placeholder:
    > valor esperado não especificado obriga quem escreve o teste a ir buscá-lo na implementação, que é
    > exatamente o que o preâmbulo deste `04` proíbe. Se os rótulos reais divergirem destes, **a divergência é o
    > achado** — corrija aqui antes de escrever o teste, nunca o contrário. E a linha nula troca ausência por
    > **presença do default**: com `origem` nula não existe "valor cru" para não conter, e a asserção seria
    > indefinida.

    Cenário: [CT-39] a data de criação sai no fuso do aplicativo
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E que o aplicativo está em "America/Sao_Paulo" e o banco grava em UTC
      E uma conta criada em 2026-03-10 às 23:30 no fuso do aplicativo
      Quando ele abre a ficha dessa conta
      Então a região "fph-metadata" contém "10/03/2026"
      E a região "fph-metadata" não contém "11/03/2026"

    Esquema do Cenário: [CT-40] os dois estados da organização decidem dois badges
      Dado um administrador autenticado no painel "/admin"
      E uma organização com "ativo" igual a <ativo> e "registro habilitado" igual a <registro>
      Quando ele abre a ficha dessa organização
      Então a região "fph-badges" contém "<badge_ativo>"
      E a região "fph-badges" contém "<badge_registro>"
      E a região "fph-badges" não contém "<oposto_ativo>"
      E a região "fph-badges" não contém "<oposto_registro>"

      Exemplos:
        | ativo | registro | badge_ativo | oposto_ativo | badge_registro | oposto_registro | # linha |
        | true  | true     | ativa       | inativa      | aberto         | fechado         | 1 de 4  |
        | true  | false    | ativa       | inativa      | fechado        | aberto          | 2 de 4  |
        | false | true     | inativa     | ativa        | aberto         | fechado         | **3 de 4 — a combinação que um `&&` trocado apaga** |
        | false | false    | inativa     | ativa        | fechado        | aberto          | 4 de 4  |

    > **As duas colunas de oposto vieram da revisão adversarial.** Sem elas os quatro `Então` eram só presença, e
    > uma implementação que emitisse **os quatro rótulos sempre** passava nas quatro linhas da tabela de decisão.
    > CT-37, o cenário irmão, já tinha a linha de exclusividade; a assimetria entre os dois era o defeito.
```

**CT-39 escolhe o instante dentro da janela de divergência.** 23:30 em `America/Sao_Paulo` é 02:30 UTC **do dia
seguinte** — as duas implementações divergem. Testar às 14:00 não distingue nada. E 2026-03-10 evita a virada
de horário de verão do hemisfério norte, que traria um segundo motivo de falha ao mesmo caso.

**CT-40 é tabela de decisão completa (2 condições × 2 = 4 linhas, todas escritas).** A linha 3 é a que um
`&&`/`||` trocado apaga.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M48 | os badges são constantes ("Ativo" sempre) | CT-37, linhas 1, 2 e 3 |
| M49 | a situação reescrita à mão no header em vez de reusar a decisão já existente, e a **ordem** do `match` inverte: `Inativo` passa a vencer `Pendente` | CT-37, **linha 2** — a única em que as duas condições são verdadeiras ao mesmo tempo |
| M50 | `origem` impressa crua | CT-38, segunda linha |
| M51 | a data formatada em UTC | CT-39 |
| M52 | os dois badges da organização decididos por um `&&` no lugar de dois testes | CT-40, linha 3 |
| M53 | o metadata lê o usuário **autenticado** em vez do registro | CT-06 |

---

## Regra R12 — nenhuma página que aplica o trait declara `getHeader()` próprio

> `01` passo 4 (fato do vendor: *"a page `getHeader()` override wins"*) · área C · perfil **padrão**

**Por que isto é regra e não nota.** É o modo de falhar mais silencioso de toda a entrega: uma página com
`getHeader()` próprio torna o pacote **inerte, sem erro nenhum**. Hoje `getHeader()` tem zero ocorrências em
`app/` — o caso existe para que continue assim quando alguém copiar um molde de outra base.

```gherkin
# language: pt
Funcionalidade: O trait não é neutralizado por sobrescrita

  Regra: página que aplica o trait do header não declara getHeader()

    Cenário: [CT-41] nenhuma página do painel sobrescreve getHeader()
      Dado os arquivos de página sob "app/Filament/", com comentários removidos
      E que o conjunto varrido tem pelo menos 30 arquivos
      E que pelo menos 6 desses arquivos aplicam o trait do header
      Quando a pessoa que escreve o teste procura a declaração de "getHeader"
      Então nenhuma das páginas que aplicam o trait a declara

    Cenário: [CT-42] o detector acha a agulha plantada
      Dado um arquivo sintético que aplica o trait do header e declara "getHeader"
      Quando o mesmo detector roda sobre ele
      Então ele acusa esse arquivo pelo nome
```

**A linha "pelo menos 6 aplicam o trait" é o piso que impede o falso ✅**: sem ela, o caso fica verde no dia em
que o trait não for aplicado a nenhuma página — que é o defeito que M5 descreve.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M54 | `ViewUser` copiada de um molde com `getHeader()` — header nativo, pacote inerte | CT-41 + CT-03 |
| M55 | a varredura procura `getHeader` em **todo** `app/Filament` e reprova por uma página legítima que não usa o trait | CT-41 (a condição é o **par** trait+declaração) |
| M56 | o detector procura a string sem o `function` e casa a **chamada** `$page->getHeader()` de um comentário | CT-42 + o filtro de comentário |

---

## Cenários de taxonomia fora das doze regras

```gherkin
# language: pt
Funcionalidade: Bordas do ciclo de vida do registro

    Cenário: [CT-43] a ficha de uma conta removida não abre
      Dado uma conta com exclusão lógica registrada
      E um administrador com a permissão de ficha
      Quando ele abre o endereço da ficha dessa conta
      Então a resposta é 404
      E o corpo não contém o nome da conta
      E, com a mesma conta restaurada, o mesmo endereço responde 200 com "fph-root"

    Cenário: [CT-44] identificador inexistente não abre, mas a rota existe
      Dado um administrador com a permissão de ficha
      E uma conta alvo que existe
      Quando ele abre o endereço da ficha com um identificador que não existe
      Então a resposta é 404
      E nenhuma exceção não tratada é registrada
      E o mesmo endereço, com o identificador da conta que existe, responde 200 com "fph-root"

    > A última linha é o controle que faltava: sem ela, uma rota `view` **inexistente** também devolve 404 e o
    > caso fica verde com a tela inteira ausente. CT-43 tinha esse controle; CT-44 não tinha.

    Cenário: [CT-45] o header convive com os widgets de cabeçalho
      Dado um administrador autenticado no painel "/admin"
      E a tela de visualização de uma organização, que já declara widgets de cabeçalho
      Quando ele a abre
      Então a página contém um elemento com a classe "fph-root"
      E a página contém os widgets de cabeçalho
      E nenhum widget aparece dentro da região "fph-root"
      E as ações de cabeçalho aparecem dentro da região "fph-root"

    Cenário: [CT-46] o header fica acima das abas de relacionamento
      Dado um administrador autenticado no painel "/admin"
      E a tela de visualização de uma organização, que declara um gerenciador de relacionamento
      Quando ele a abre
      Então a página contém um elemento com a classe "fph-root"
      E a região "fph-root" contém o nome da organização
      E a região "fph-root" não contém a barra de abas dos relacionamentos
      E a barra de abas dos relacionamentos aparece depois do fechamento da região "fph-root"
```

**CT-43 fecha a linha "estado × operação de escrita" do checklist pelo lado que existe aqui**: a entrega não
acrescenta escrita, então a operação testada sobre o registro removido é a **leitura nova**. E a última linha
(restaurada → 200) é o que impede o caso de passar com a tela inteira quebrada.

**CT-45 e CT-46 afirmam ausência dentro de uma região que as linhas anteriores provaram não estar vazia** — a
regra que CT-02 institui.

---

## Regra R13 — cenários trazidos pela revisão adversarial

> Oito cenários novos (CT-49…CT-56) e a adoção formal de CT-43…CT-46, que eram órfãos de regra. Cada um fecha um
> achado numerado da revisão; a rastreabilidade está em [`## Revisão Adversarial`](#revisão-adversarial).

### R13a — o header rico está **completo** também nas telas de Edit (fecha I-1)

**O achado, e ele é o mais grave do conjunto.** CT-03 era o único cenário que tocava as três telas de Edit, e o
`Então` dele parava no `fph-heading`. Todo o resto do conteúdo — avatar, badges, metadata — estava amarrado à
palavra "ficha"/"visualização" em CT-07…CT-10, CT-25 e CT-37…CT-40. Uma implementação que resolvesse a classe de
schema **só nas páginas `View*`**, deixando as três `Edit*` com o schema default do vendor (heading e nada mais),
passava nos 48 cenários. E RQ-02/RQ-03 dizem literalmente "View/**Edit**".

A causa é de técnica: a partição de R2 era **unidimensional** (uma dimensão, a tela). O que o requisito pede é
**tela × slot**.

```gherkin
# language: pt
  Regra: as telas de edição têm o mesmo header rico das de visualização

    Esquema do Cenário: [CT-49] os slots do header estão preenchidos nas telas de edição
      Dado um administrador com as permissões de edição, autenticado no painel "<painel>"
      E um registro alvo com nome, identificador secundário e estado conhecidos
      Quando ele abre a tela de edição "<tela>"
      Então a região "fph-heading" contém o nome do registro
      E a região "fph-avatar" não está vazia
      E a região "fph-badges" não está vazia
      E a região "fph-metadata" contém o identificador secundário do registro
      E nenhuma dessas regiões contém o nome de quem está autenticado

      Exemplos:
        | painel | tela       | identificador secundário | # partição            |
        | admin  | EditUser   | e-mail                    | Edit, sem tenancy      |
        | app    | EditUser   | e-mail                    | Edit, com tenancy      |
        | admin  | EditTenant | slug                      | Edit, outro model      |
```

### R13b — o trait está nas seis telas e **em mais nenhuma** (fecha I-2)

**O achado.** A metade negativa de R2 — *"fora delas, o nativo"* — só tinha um destinatário: o painel `/infra`.
Nenhum cenário verificava uma página do **próprio** `/admin`/`/app` fora das seis. E CT-41 contava páginas com o
trait por um **piso** ("pelo menos 6"), que 30 páginas satisfazem. O dev que aplica o trait numa `Page` base
"para não esquecer nenhuma" passava inteiro.

```gherkin
    Cenário: [CT-50] exatamente seis páginas aplicam o trait do header
      Dado a varredura das páginas sob "app/Filament/**/Pages", com comentários removidos
      E que o conjunto varrido tem pelo menos 30 arquivos
      Quando a pessoa que escreve o teste lista as classes que aplicam o trait do header
      Então esse conjunto é **igual** à lista nominal de seis declarada neste cenário
      E a listagem de contas do painel "/admin" responde 200 sem nenhum elemento com a classe "fph-root"
```

> `toBe` sobre o conjunto de FQCN, **não** `toContain` nem piso. É a mesma armadilha que a seção de armadilhas
> deste `04` já nomeava para CT-20 e que não havia sido aplicada aqui.

### R13c — a ficha do `/app` não mostra mais que a do `/admin` (fecha I-3)

**O achado.** A premissa declarada nomeava CT-20 como "o cenário que mede", mas CT-20 compara **chaves
serializadas do model** — idênticas nos dois painéis por construção, quaisquer que sejam os slots renderizados.
O oráculo media uma grandeza que não pode divergir.

```gherkin
    Cenário: [CT-51] os slots da ficha do /app estão contidos nos da ficha do /admin
      Dado uma conta alcançável nos dois painéis
      E um administrador da instalação e um administrador da organização, ambos com a permissão de ficha
      Quando a ficha dessa conta é aberta em cada um dos dois painéis
      Então o conjunto de rótulos de "fph-metadata" do "/admin" tem pelo menos três elementos
      E o conjunto de rótulos de "fph-metadata" do "/app" é subconjunto do conjunto do "/admin"
      E o conjunto de rótulos de "fph-badges" do "/app" é subconjunto do conjunto do "/admin"
```

### R13d — o caminho permitido não escreve na trilha (fecha I-4)

**CT-52 não existe, de propósito.** O achado I-4 foi fechado **dentro de R5**, reescrevendo CT-18 e CT-19 em vez
de criar cenário novo — a direção "não aconteceu" pertence ao rastreio de efeito daquela regra, e movê-la para um
cenário à parte separaria o não-efeito do efeito que ele nega. O número fica vago para que ninguém o procure.

Fechado **dentro de R5**, reescrevendo CT-18 e CT-19 em vez de criar cenário novo — a direção "não aconteceu"
estava declarada no texto do rastreio de efeito e ausente do Gherkin. Ver a tabela das quatro direções em R5 e
os mutantes M57/M58.

### R13e — a ficha mostra o registro **fora** do cabeçalho (fecha I-5)

**O achado.** O único cenário que olhava o corpo da ficha era CT-32, e o `Então` dele sobre a ficha era uma
**ausência** ("não contém campo de formulário editável"). Página vazia satisfaz. A resolução de RQ-02 no `00` é
explícita — *"criar o `ViewUser`, com rota `view`, **infolist**, permissão e testes"* — e a cláusula `infolist`
não tinha oráculo. O dev que entrega `$schema->components([])` porque "o resumo já está no header" passava.

```gherkin
    Cenário: [CT-53] a ficha mostra o registro fora do cabeçalho
      Dado um administrador com a permissão de ficha, autenticado no painel "/admin"
      E uma conta alvo com e-mail e data de criação conhecidos
      Quando ele abre a ficha dessa conta
      Então a página, descontada a região "fph-root", contém o e-mail da conta
      E a página, descontada a região "fph-root", contém a data de criação da conta
      E o infolist da página tem pelo menos três entradas
```

> O extrator de R1 já dá a subtração: `regiaoDoHeader()` devolve o **dentro**; o complemento é o corpo. Sem
> isso, toda asserção "a página contém o e-mail" seria satisfeita pelo próprio header.

### R13f — o `kit:update` avisa sobre a dependência nova (fecha I-6)

**O achado.** RQ-12 assume **duas** coisas: que o comando **avise** e que o passo manual esteja **documentado**.
A tabela de rastreabilidade marcava "RQ-12 | CT-36" sem ressalva, e CT-36 lê a **página de documentação**, não a
saída do comando. A metade (a) não tinha cenário — uma cláusula com duas assunções foi tratada como um item só.

```gherkin
    Cenário: [CT-54] o comando de atualização avisa sobre a dependência nova
      Dado um projeto do kit cuja declaração de dependências não traz o pacote do header
      Quando o comando de atualização do kit roda em modo de relatório
      Então a saída cita o nome do pacote
      E a saída cita o comando de instalação e o de publicação de assets
      E, com o pacote já declarado, a saída não o cita
```

> **Ressalva medida, que o `02` já registra**: `relatarComposerJson()` **retorna cedo** quando a origem é nula —
> quem não tem `config('kit.version')` casando com uma tag do kit nunca vê o aviso. O `Dado` deste cenário
> precisa fixar uma origem válida, senão ele mede o retorno cedo e fica verde sobre nada.

### R13g — as duas células que a matriz de R4 omitia (fecha o item 4 da revisão)

```gherkin
    Esquema do Cenário: [CT-55] o papel de infraestrutura não alcança a ficha de conta
      Dado um usuário com o papel "infra", autenticado
      Quando ele abre, pela URL do painel "/admin", a ficha de "<alvo>"
      Então a resposta é 403 ou 404
      E o corpo não contém o nome do alvo
      E o corpo não contém nenhum elemento com a classe "fph-root"
      E, no mesmo processo, um administrador com a permissão recebe 200 com "fph-root"

      Exemplos:
        | alvo          | # partição       |
        | uma conta alheia | conta de terceiro |
        | a própria conta  | conta própria     |

    @premissa
    Cenário: [CT-56] abrir a própria ficha também exige a permissão
      Dado uma pessoa sem a permissão "View:User", autenticada num painel que tem a tela
      Quando ela abre a ficha da **própria** conta
      Então a resposta é 403
      E o corpo não contém nenhum elemento com a classe "fph-root"
      E a ficha de um colega, para a mesma pessoa, também responde 403
      E, com a permissão concedida, as duas respondem 200 com "fph-root"
```

> **CT-55 corrige uma justificativa falsa.** A célula saíra como *"não se aplica: `User` não é do painel
> `/infra`"* — que confunde *"o Resource não está registrado no `/infra`"* com *"o papel `infra` não alcança a
> tela do `/admin`"*. `View:User` é permissão do Shield, **global, não por painel**, e o papel `infra` existe,
> é semeado e autentica. É exatamente o tipo de afirmação negativa que a skill manda escrever **como se fosse
> falsa**.
>
> **CT-56 é dimensão nova**, e é `@premissa`: o requisito não decide se a conta própria dispensa a permissão.
> Direção por **falha fechado** — nega. Invariante afirmado junto, e ele vale em qualquer resposta: *seja qual
> for a decisão sobre a conta própria, ela nunca é mais permissiva do que a decisão sobre a conta do colega*.
> **Se negado** (a própria ficha é sempre aberta): CT-56 inverte as duas primeiras linhas e a última fica.
> Pergunta nº 5, registrada abaixo.

### R13h — CT-43…CT-46 deixam de ser órfãos

Os quatro estavam sob "Cenários de taxonomia fora das doze regras", sem `Regra:` no Gherkin e sem linha no Mapa
de Regras — rastreabilidade declarada completa com quatro órfãos. Passam a pertencer a **R13i — o ciclo de vida
do registro e a convivência do header com o resto da página**, com os mutantes abaixo.

#### Mutantes previstos de R13

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M59 | a classe de schema é resolvida só nas páginas `View*`; as três `Edit*` caem no schema default do vendor | **CT-49** |
| M60 | o trait é aplicado numa `Page` base e vaza para toda página dos dois painéis | **CT-50** |
| M61 | a ficha do `/app` ganha um slot que o `/admin` não tem | **CT-51** |
| M62 | a `ViewUser` nasce com infolist vazio, porque "o resumo já está no header" | **CT-53** |
| M63 | o `kit:update` não menciona a dependência nova | **CT-54** |
| M64 | o papel `infra` alcança a ficha do `/admin` por ter recebido `View:User` numa matriz mal recortada | **CT-55** |
| M65 | a ficha da própria conta dispensa a permissão com um `if ($record->is($user))` | **CT-56** |
| M66 | a ficha de conta removida abre, porque o route binding foi declarado `withTrashed()` | CT-43 |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`.
> Nunca "sim".

| Item | Cenário que mata |
|---|---|
| **IDOR / autorização horizontal** | CT-16 (conta de outra organização, pela URL) |
| **Autorização exercida na ação, não só `can()`** | CT-13, CT-17, CT-19 — consultadas **direto**, fora da página |
| **≥1 cenário de autorização fora do componente de UI** | CT-13, CT-17, CT-18, CT-19 |
| **Verbo irmão não herda evidência** | CT-19 — view **e** edit, com o mesmo alvo |
| Idempotência (ancorada no agregado) | não se aplica: a entrega não acrescenta nenhuma operação de escrita — a `ViewUser` é read-only e o header é apresentação. O agregado que sofreria efeito não existe, e escrever o cenário assim mesmo produziria caso tautológico |
| Concorrência | não se aplica: sem contador, saldo, estoque ou limite |
| **Fronteira no ponto de entrada (gravação)** | não se aplica: sem ponto de gravação novo. O ponto de entrada novo é de **leitura** — CT-11, CT-16, CT-43, CT-44 |
| Domínio condicionado (campo × campo) | CT-40 (`ativo` × `registro_habilitado`); CT-37 (verificação × `ativo` × aprovação) |
| **Estado × operação: o registro removido ainda funciona?** | CT-43 |
| **Ausente ≠ `null` ≠ `""`** | CT-09 (avatar: nulo e string vazia, mesmo destino, declarado); CT-38 (origem nula) |
| Paginação | não se aplica: o header não lista; a listagem é pré-existente |
| Ordenação | não se aplica: idem |
| **Timezone / virada de dia** | CT-39 — instante dentro da janela de divergência (23:30 em `America/Sao_Paulo`) |
| DST | não se aplica: a data escolhida evita a virada de propósito, para não somar dois motivos de falha num caso. **Dívida**: nenhum caso cobre um `created_at` dentro da virada brasileira |
| **Unicode / limite de varchar** | CT-27 (emoji 4 bytes, acento, 256 caracteres) |
| Unicidade + soft delete | não se aplica: sem campo único novo, sem criação |
| **CRUD: ler id inexistente** | CT-44 |
| **Mass assignment** | CT-22 — papel de administrador injetado pelo estado do componente |
| Upload | não se aplica: a entrega não acrescenta upload. O avatar existente é **lido**, não gravado — CT-08 |
| Precisão monetária | não se aplica: sem valor monetário |
| **Superfície Livewire — método público** | CT-23, CT-24 (os 9 métodos do trait, em 6 páginas) |
| **Superfície Livewire — propriedade pública sem `#[Locked]`** | CT-22 (`$data`), CT-21 (`$record`, com trava) |
| **Estado do framework usado sem validar** (índice, `parse`, coluna) | não se aplica: verificado no `02` — a entrega não consome `$filters`, `$pageFilters`, `$tableFilters` nem `$tableSearch`. **Confirmado por grep**, não por dedução |
| **IDOR por entidade persistida** | `users`: CT-16 · `tenants`: não se aplica — o `TenantResource` é do `/admin`, global por desenho, e a entrega não muda o recorte dele |
| Mass assignment por entidade | `users`: CT-22 · `tenants`: não se aplica — a entrega não acrescenta ponto de escrita em `ViewTenant`/`EditTenant` |
| **Escopo com discriminante nulo (fecha ou abre?)** | CT-18 — fecha, e o desejado está declarado |
| **Saída do estado de erro (4xx tem destino)** | CT-12 (403 da ficha); CT-43/CT-44 (404 → a listagem segue alcançável) |
| **Efeito colateral — canal exato e destinatário** | CT-17 (canal `autenticacao`, `warning`, com `alvo_id` e `ator_id`); CT-18 |
| **Efeito colateral — o que NÃO pode estar nele (PII)** | CT-17, terceira linha |
| **Detector inerte (o defeito da rodada anterior)** | CT-01, CT-02, CT-29, CT-42, e os pisos de CT-26/CT-28/CT-41 |
| **Efeito colateral — NÃO disparado no caminho feliz** | **CT-19** (autorização que permite), **CT-18** (com organização). Era a direção declarada no texto e ausente do Gherkin |
| **Autorização sobre a *própria* conta** | **CT-56** (`@premissa`, falha fechado) |
| **Papel que não é do painel alcançando a tela pela URL** | **CT-55** — a dispensa anterior era afirmação negativa falsa |
| **Completude do header por tela** (tela × slot, não só tela) | **CT-49** |
| **Teto do conjunto que recebe o trait** (não só piso) | **CT-50** |
| **Corpo da tela nova, fora do cabeçalho** | **CT-53** |
| **Saída do comando de atualização** (metade (a) de RQ-12) | **CT-54** |

---

## Armadilhas de API que invalidariam estes CT

| Armadilha | Consequência aqui |
|---|---|
| **`toContain()` é variádico** — `expect($html)->toContain('fph-root', 'Ana')` exige **as duas** agulhas, e `expect($x)->not->toContain('a','b')` reprova se **qualquer** uma aparecer | em CT-03 e CT-11 o segundo argumento parece um "reason"/mensagem e vira **outra agulha**. Use uma agulha por `toContain()`, encadeando, e `expect($chaves)->toBe([...])` em CT-20 (não `toContain`) |
| `noPainelBootado('app')` | **não funciona** no `/app` — `BreezyCore::boot()` lê `route()->parameter()` sem request. CT-15 entra por HTTP; qualquer Livewire do `/app` precisa de um `GET` real pelo kernel antes, e então `noPainelDa($acme)` |
| `admin_app` em `tests/Kit` | `RoleDoesNotExist` no arranjo. CT-15, CT-16, CT-17, CT-18, CT-19 vão para `tests/Tenancy` |
| asserção de ausência sobre fonte documentado | CT-26, CT-41 e CT-42 reprovariam pela própria documentação do kit. Filtro de comentário **só na ausência** |
| helper novo declarado num `*Test.php` | `regiaoDoHeader()` é usado por 6+ arquivos. Fora do `tests/Pest.php`, quebra em `--parallel`, em `--tia` e no arquivo isolado, e `HelpersDeTesteTest` fica vermelho |
| tabela do kit sem `->loadTable()` | CT-14 e CT-31 leriam o HTML do **esqueleto** (`deferLoading` é global no kit) |
| `Filament::setCurrentPanel()` sem descartar o Shield | CT-47 e CT-15 percorrem mais de um painel no mesmo processo — use `noPainelDoShield($painel)`, que descarta a instância memoizada **e** a da facade. Sem os dois descartes as traits do Shield **falham abertas** |
| `assertSee()` como oráculo único | passa com o nome no `<title>`, no breadcrumb e na barra lateral. Toda asserção de conteúdo desta wiki roda **dentro de uma região** |
| `assertOk()` como oráculo único | CT-11 linha positiva tem de afirmar `fph-root`, senão fica verde numa tela sem header |
| `@deprecated` do Filament 5 | `assertFormSet`, `callTableAction`, `assertTableActionExists` funcionam **e não avisam**. Use `assertSchemaStateSet`, `callAction(TestAction::…)`, `assertActionExists` — `AderenciaAoBlueprintTest` reprova os antigos |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Suíte sugerida | Mata |
|---|---|---|---|---|---|---|
| CT-01 | recorte separa dentro de fora | R1 | controle negativo | Unit | `Kit` | M1, M2, M4 |
| CT-02 | região ausente devolve vazio | R1 | controle negativo | Unit | `Kit` | M3 |
| CT-03 | header nas seis telas | R2 | EP | Feature (HTTP) | `Kit` + `Tenancy` | M5, M6 |
| CT-04 | painel sem plugin segue nativo | R2 | controle negativo | Feature | `Kit` | M8 |
| CT-05 | CSS não servida no `/infra` | R2 | controle negativo | Feature | `Kit` | M8 |
| CT-06 | header mostra este registro, nem o de outro nem o de quem olha | R2 | três personas distintas | Feature | `Kit` | M53 |
| CT-07 | `data:` não chega ao `<img>` | R3 | partição | Feature | `Kit` | M13 |
| CT-08 | URL de storage chega | R3 | partição | Feature | `Kit` | M11, M12 |
| CT-09 | esquema do URL + nome vazio | R3 | EP (ausente/vazio nos dois campos) | Feature | `Kit` | M11, M12, M13 |
| CT-10 | organização sem logo | R3 | partição | Feature | `Kit` | M14 |
| CT-11 | par permissão/403 | R4 | matriz | Feature | `Kit` | M16, M17, M21 |
| CT-12 | o 403 tem saída | R4 | saída de erro | Feature | `Kit` | — (par de CT-11) |
| CT-13 | barreira fora da tela | R4 | camada externa | Feature | `Kit` | M17, M20 |
| CT-14 | ação some sem permissão | R4 | Livewire | Livewire | `Kit` | M19 |
| CT-15 | mesma barreira no `/app` | R4 | matriz | Feature (HTTP) | **`Tenancy`** | M16, M17 |
| CT-16 | conta de outra organização | R5 | IDOR | Feature (HTTP) | **`Tenancy`** | M24 |
| CT-17 | negativa decidida fora da tela | R5 | camada externa + efeito | Feature | **`Tenancy`** | M22, M23, M26, M58 |
| CT-18 | com e sem organização | R5 | discriminante nulo + não-efeito | Feature | **`Tenancy`** | M25, M57 |
| CT-19 | view não é mais permissiva que edit | R5 | verbo irmão + não-efeito | Feature | **`Tenancy`** | M22, M57, M58 |
| CT-20 | **medição do retorno ao navegador** | R6 | varredura + lista nominal | Livewire | `Kit` | — (medição; oráculo vira `toBe` ao fechar) |
| CT-21 | `$record` não aceita troca | R6 | superfície | Livewire | `Kit` | M28 |
| CT-22 | estado do formulário não grava | R6 | mass assignment | Livewire | `Kit` | M29 |
| CT-23 | métodos públicos como ação | R6 | superfície + carga devolvida | Livewire | `Kit` | M27 |
| CT-24 | argumento tipado recusado | R6 | tipo errado | Livewire | `Kit` | M31 |
| CT-25 | atributo de opções sem PII | R6 | ausência não-vácua | Feature | `Kit` | M30 |
| CT-26 | APIs proibidas ausentes | R7 | ausência + piso | Kit (arch) | `Kit` | M32, M33, M34 |
| CT-27 | título escapa marcação | R7 | EP + unicode | Feature | `Kit` | M32, M35 |
| CT-28 | classes cobertas, com exceção | R8 | runtime + piso | Kit (arch) | `Kit` | M38, M39 |
| CT-29 | detector acha a agulha | R8 | controle negativo | Kit (arch) | `Kit` | M37 |
| CT-30 | ordem das rotas | R9 | EP | Unit/Kit | `Kit` | M40 |
| CT-31 | ação navega | R9 | Livewire | Livewire | `Kit` | M41 |
| CT-32 | ficha ≠ edição | R9 | EP | Feature | `Kit` | M42 |
| CT-33 | registro diz ADOTADO | R10 | EP | Kit (docs) | `Kit` — **herdado** de `PacotesRodada2Test.php:118` | M43 |
| CT-34 | oráculo da rodada 2 | R10 | EP | Kit (docs) | `Kit` — **herdado** de `PacotesRodada2Test.php:170` | M44 |
| CT-35 | constraints | R10 | BVA | Unit | `Kit` | M45, M46 |
| CT-36 | doc manda ressemear | R10 | EP | Kit (docs) | `Kit` | M47 |
| CT-37 | situação da conta (2×2) | R11 | tabela de decisão | Feature | `Kit` | M48, M49 |
| CT-38 | origem traduzida | R11 | EP | Feature | `Kit` | M50 |
| CT-39 | data no fuso do app | R11 | instante discriminante | Feature | `Kit` | M51 |
| CT-40 | 2×2 da organização | R11 | tabela de decisão | Feature | `Kit` | M52 |
| CT-41 | `getHeader()` ausente | R12 | ausência + piso | Kit (arch) | `Kit` | M54, M55 |
| CT-42 | detector acha `getHeader()` | R12 | controle negativo | Kit (arch) | `Kit` | M56 |
| CT-43 | conta removida não abre | R13i | estado × operação | Feature | `Kit` | **M66** |
| CT-44 | id inexistente | R13i | CRUD + controle positivo | Feature | `Kit` | — |
| CT-45 | convivência com widgets | R13i | região disjunta | Feature | `Kit` | — |
| CT-46 | header acima das abas | R13i | região disjunta | Feature | `Kit` | — |
| CT-47 | hook escopado por painel | R2 | controle negativo | Feature | `Kit` | M10 |
| CT-48 | instância por painel | R2 | identidade de objeto | Unit/Kit | `Kit` | M9 |
| **CT-49** | **slots preenchidos nas telas de Edit** | R13a | tela × slot | Feature | `Kit` + `Tenancy` | **M59**, M7 |
| **CT-50** | **exatamente seis páginas com o trait** | R13b | igualdade de conjunto | Kit (arch) | `Kit` | **M60** |
| **CT-51** | **slots do /app ⊆ slots do /admin** | R13c | comparação entre painéis | Feature | `Tenancy` | **M61** |
| **CT-53** | **a ficha mostra o registro fora do header** | R13e | complemento da região | Feature | `Kit` | **M62** |
| **CT-54** | **o kit:update avisa** | R13f | EP + retorno cedo | Kit | `Kit` | **M63** |
| **CT-55** | **o papel infra não alcança a ficha** | R13g | célula omitida da matriz | Feature | `Kit` | **M64** |
| **CT-56** | **a própria ficha exige a permissão** | R13g | dimensão nova (`@premissa`) | Feature | `Kit` | **M65** |

**Mutantes sem matador — 3, declarados**: M15 (avatar do ator no lugar do alvo), M18 (permissão só no `mount()`),
M36 (lista congelada no lugar da leitura do vendor). Cada um traz, na tabela da regra, o que foi tentado.

---

## RQ sem cenário, e por quê

| RQ | Tem cenário? | Observação |
|---|---|---|
| RQ-01 | CT-34, CT-35 | — |
| RQ-02 | CT-03, CT-11, CT-16, CT-30, CT-32 | — |
| RQ-03 | CT-03, CT-10, CT-40 | — |
| RQ-04 | **parcial** — CT-46 prova a convivência; **a existência da receita não tem caso** | a receita é prosa em `wikis/receitas.md`. Cabe um caso de doc (a seção existe, cita as três armadilhas). **Lacuna declarada** — o quality gate cobre por rastreabilidade documental |
| RQ-05 | **não** | processo de entrega (sub-agentes). Não é comportamento do sistema |
| RQ-06 | **não** | processo (branch). Idem |
| RQ-07 | CT-33, CT-34 | — |
| RQ-08 | **não** | processo (commits agrupados) |
| RQ-09 | **não** | processo (Blueprint) — o enforço é `AderenciaAoBlueprintTest`, que já existe |
| RQ-10 | **não** | processo (`/code-review`) |
| RQ-11 | **não** | processo (PR com suíte verde) |
| RQ-12 | CT-36 **e CT-54** | a cláusula tem **duas** assunções — o comando avisar (CT-54) e o passo manual documentado (CT-36). Antes da revisão só a segunda tinha cenário |
| RQ-13 | CT-11, CT-13, CT-14, CT-15 | o **par** é o oráculo; só o lado positivo passaria numa tela que não consulta nada |
| RQ-14 | CT-35 | — |

Seis RQ sem cenário, **todas de processo** (RQ-05, 06, 08, 09, 10, 11). Uma parcial: RQ-04.

---

## Revisão Adversarial

**Disparo**: perfil completo em A e F; Impacto 3 em D e E1. Contrato da skill — sub-agente que **não** derivou,
recebendo `00` + `04`, sem o `01`, sem o `02` e sem o raciocínio de derivação.

**Rodada 1 — executada.** Sub-agente independente, que não derivou os cenários, recebendo `00` + `04` e
**sem** o `01`, o `02`, o `05`, o código ou o vendor. Percorreu as 13 regras, as 9 áreas, as 27 linhas do
checklist, as 48 linhas do índice e as 14 cláusulas do `00`. **26 achados**, todos fechados abaixo.

### Os cinco defeitos que passavam pelo conjunto inteiro

| # | Implementação errada que passava nos 48 | Regra | Técnica que faltou | Virou |
|---|---|---|---|---|
| I-1 | **o header rico só nas telas de View**; as três de Edit ficam com o schema default do vendor (heading e nada mais) | R2, R3, R11 | partição **unidimensional**: o eixo era *tela*, e o produto que o requisito pede é *tela × slot* | **CT-49** + **M59**; killer de M7 corrigido |
| I-2 | **o trait aplicado a todas** as páginas dos dois painéis, via `Page` base | R2 (metade negativa), R12 | controle negativo só no eixo *painel* (`/infra`), nunca no eixo *página*; e piso onde cabia **igualdade** | **CT-50** + **M60** |
| I-3 | **a ficha do `/app` mostra mais** que a do `/admin` | R6 | o oráculo (chaves serializadas) media grandeza que **não pode divergir**; o invariante era sobre slots renderizados | **CT-51** + **M61**; CT-20 ganhou piso de não-vacuidade |
| I-4 | o `warning` emitido **na entrada** do método, não só na negativa — a trilha do `/infra` vira ruído | R5 | a direção "não aconteceu" estava **no texto** do rastreio de efeito e **ausente do Gherkin** | CT-18 e CT-19 reescritos + **M57**, **M58** |
| I-5 | a `ViewUser` nasce com **infolist vazio** | R9 / resolução de RQ-02 no `00` | par positivo/negativo sobre a mesma região: CT-32 só tinha o sinal negativo sobre a ficha | **CT-53** + **M62** |
| I-6 | `KitUpdate.php` nunca é tocado — a metade (a) de RQ-12 sem cenário | R10 | cláusula com **duas assunções** tratada como um item só na rastreabilidade | **CT-54** + **M63** |
| I-7 | `getHeader()` serializa a View inteira no payload (M27 sobrevivia ao killer) | R6 | o cenário media o canal errado (exceção) para um mutante de **divulgação** | CT-23 ganhou duas colunas sobre a carga devolvida |

### Oráculos reescritos (14)

| CT | O que passava com defeito | Correção |
|---|---|---|
| CT-06 | ausência sobre `fph-metadata` sem prova de que a região não está vazia — **M53 sobrevivia ao próprio killer**: com o metadata lendo o usuário autenticado, o e-mail de Bruno nunca apareceria | três personas distintas (alvo, terceiro, ator) + linha de presença |
| CT-09 | `Dado` **circular** ("uma conta cujo avatar *resolve para*") pressupunha o mecanismo que CT-07/CT-08 provam; sem ator, sem painel | ator e painel declarados; coluna de avatar em vez de URL resolvida; **linha nova de nome vazio** (achado K: era o único caminho em que o slot some, e não estava coberto) |
| CT-15 | entregava **dois** dos três efeitos que a legenda da matriz declara; coluna `estado` valia literalmente `tem?` | terceiro efeito acrescentado; o estado de `panel_user` declarado como subtração semeada, não como pergunta |
| CT-18 | só o ramo sem organização — uma query que devolvesse sempre vazio passava | ramo positivo com duas organizações + não-efeito no log |
| CT-19 | segunda linha **tautológica** (implicada pela primeira); nenhuma menção a log, e os dois ramos negavam | três personas, ramo que **permite**, contagem exata de avisos |
| CT-20 | as três linhas finais eram **satisfeitas por lista vazia**; e comparava grandeza errada entre painéis | piso de não-vacuidade, presença de `name`/`email`, lista nominal (`toBe`); a comparação entre painéis migrou para CT-51 |
| CT-22 | ausência de "INJETADO" sem presença de "Ana Prado" | linha de presença + ator |
| CT-23 | `Então` de robustez pura, sem inspecionar a carga | duas colunas sobre o payload devolvido |
| CT-24 | "não expõe rastro de pilha" satisfeito por corpo vazio | "o corpo não é vazio" + a tela segue viva |
| CT-38 | valor esperado **não especificado** (`(o rótulo do kit)`), obrigando a ir buscá-lo na implementação; e ausência indefinida na linha nula | quatro rótulos fixados; linha nula troca ausência por presença do default |
| CT-39 | sem ator, sem painel | declarados |
| CT-40 | **só presença**: uma implementação que emitisse os quatro rótulos sempre passava nas quatro linhas | duas colunas de oposto (exclusividade), como CT-37 já tinha |
| CT-44 | 404 sem controle positivo — uma rota `view` **inexistente** também dá 404 | controle com identificador existente |
| CT-45, CT-46 | ator só no `Quando`; ausência sobre `fph-root` sem prova de região não vazia | ator no `Dado` + linha de presença |

### Matriz de R4 — refeita

A aritmética não fechava (somava *linhas de `Exemplos:`* com *células*), duas células de `master_global`
apontavam um cenário que nunca autentica `master_global`, e o eixo estava errado: 15 das 20 células eram
operações fora de escopo, enquanto **painel** e **conta própria** não existiam. Refeita como **papel × alvo**,
15 células, 11 exercitadas. Duas células omitidas viraram **CT-55** (o papel `infra`, cuja dispensa era uma
afirmação negativa **falsa**) e **CT-56** (a conta própria, dimensão que não existia em lugar nenhum).

### Contabilidade corrigida

| Achado | Estava | Ficou |
|---|---|---|
| total de mutantes no cabeçalho | 44 | **66** (M1…M66) — já corrigido para 56 antes da revisão, e agora com os 10 novos |
| M7 sem linha no índice | órfão | ligado a **CT-49** |
| M7, M43, M53, M27 com killer que não mata | 4 falsos ✅ | 3 corrigidos; **M43 vira lacuna declarada** |
| "Sem matador: 3" | subcontado | **5**: M15, M18, M36, M43 e M27-residual. **E a ressalva do achado C**: M7, M48, M49 e M50 dependem de cenários `@premissa` — se a pergunta nº 1 for respondida contra a premissa, sobem para 9 |
| CT-43…CT-46 sem regra | 4 órfãos | adotados por **R13i**, com M66 |
| regra de contagem de `Esquema` | não declarada | declarada no cabeçalho |

### Achados aceitos **sem** correção, com motivo

| Achado | Por que fica |
|---|---|
| "mais de um `Quando`" em CT-05, CT-19, CT-32, CT-47 | são **observações comparativas**: o oráculo é a *diferença* entre dois pontos de medida. Dividi-los destrói o oráculo — um cenário que só abre o `/admin` não pode afirmar "e o `/infra` não". Declarado como exceção consciente, não como descuido |
| "ação escondida no `Então`" em CT-12, CT-13, CT-14, CT-16, CT-26, CT-43 | mesma natureza: a segunda leitura é o **controle** que torna a primeira falsificável. É o que a skill chama de par linha-de-base × oráculo |
| "ação escondida no `Dado`" de CT-04 | o `Dado` existe justamente para dar **destinatário** à ausência. Sem ele a ausência é vácua — o custo de parecer um `Quando` é menor que o de um oráculo inerte |
| achado G ("6 cenários com `Mata: —`") | CT-12, CT-20, CT-44, CT-45, CT-46 são **controles e medições**, não caçadores de mutante. CT-43 ganhou M66. Cenário de controle sem mutante próprio é legítimo; o que a skill manda cortar é caminho feliz redundante |

### Rodada 2

**Devida, e não executada nesta sessão.** O fechamento criou **7 cenários novos** (não apenas reforçou oráculos),
e a regra da skill é re-revisar uma única vez quando isso acontece — é onde mora a lacuna de segunda ordem.
Fica como o primeiro item do [`## Fechamento`](#fechamento-pós-implementação). Teto de 2 rodadas: se a segunda
trouxer achado estrutural, o problema é da regra, que provavelmente deveria ser duas.

---

## Fechamento pós-implementação

- [ ] **Rodada 2 da revisão adversarial** — devida, porque o fechamento criou 7 cenários novos. Sub-agente
      independente, `00` + `04` + `05`, sem o `01`/`02`/código. Teto de 2 rodadas
- [ ] `composer test:kit` verde (Kit + Tenancy, `--parallel`)
- [ ] `composer test:browser` verde (série — nunca `--parallel`)
- [ ] `vendor/bin/filacheck --fix` sem pendência (obrigatório após mexer em `app/Filament`)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] **Mutação**, se e só se houver driver de cobertura: `vendor/bin/pest tests/Kit --mutate --path=app/Filament/Admin/Resources/Users`.
      Sem PCOV, **declarar a lacuna** em vez de rodar — a rule do projeto mediu que com Xdebug, em série, não termina
- [ ] Cada mutante sobrevivente traduzido em lacuna de derivação e convertido em cenário novo **aqui**, não no arquivo de teste
- [ ] Índice de cenários atualizado com o arquivo de teste real de cada CT
- [ ] **Sincronia nos dois sentidos**: todo `[CT-nn]` do teste existe neste `04`, e todo CT do índice aponta um teste existente ou declara "fundido em CT-nn"
- [ ] Contagem do cabeçalho recalculada
- [ ] CT-20 fechado: a lista de chaves medida foi escrita **aqui** e a decisão (aceitar a divulgação ou recortar) foi para o `03`
- [ ] Pergunta nº 4 (`fph-schema` sem regra) respondida: exceção nominal com motivo, ou issue no vendor
