# Casos de Teste — página de boas-vindas na rota `/`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando implementação — ela não existe
> ainda. O PRD entrou só para path, rota e a tabela `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Superfície pública e segredo (o que a rota anônima devolve) | 2 | 3 | **6** | padrão |
| Herança de CSS, paleta e tema (painel bootado fora do painel) | 3 | 2 | **6** | padrão |
| Infolist de configuração (12 chaves lidas e formatadas) | 2 | 1 | **2** | mínimo |
| Cartões e navegação (3 destinos fixos) | 1 | 1 | **1** | mínimo |
| Substituição da welcome padrão | 1 | 1 | **1** | mínimo |

Justificativa dos números que não são óbvios:

- **Segredo, I = 3**: a rota é anônima e a config que a página lê é vizinha de
  `config('kit.admin.password')`. Um item a mais na infolist é dado de terceiro exposto.
- **CSS/tema, P = 3**: a página boota um painel Filament **fora** de uma rota de painel — o único
  lugar do kit que faz isso. Integra com 30+ plugins num contexto novo.
- **CSS/tema, I = 2**: o modo de falhar é retrabalho, não perda. Mas é **silencioso**: utilitária
  ausente produz HTML byte a byte correto e sem estilo nenhum, e todo `assertSee` fica verde
  (`.ai/rules/css-filament.md`).

- Técnicas aplicadas: **EP** (partição do domínio de config), **EP exaustiva** (lista negra de
  segredos), **BVA 2-valores com incremento de 1 dia** (prazo em que zero significa "desligado"),
  **ausente ≠ null ≠ vazio**, **rastreio de efeito** — não se aplica (a página não tem efeito
  colateral).
- Cenários: **17** em HTTP + **2** CT-B · Regras: **7** · Mutantes previstos: **33** (M1–M30 aqui,
  M31–M33 no `05`) · Sem matador: **1** (M6, lacuna declarada)
- O segundo CT-B (CT-B02) **não** estava aqui no ciclo 1: ele nasceu do achado QA-01 do
  `06-relatorio-qa.md`, junto com os mutantes M32 e M33. O corte original dele, e por que estava
  errado, estão no `05`.

### Técnica escalada acima do perfil da área

| Regra | Área (perfil) | Técnica usada | Por quê |
|---|---|---|---|
| R5 | Infolist de config (**mínimo**) | **BVA 2-valores** em vez de só EP | `config/kit.php` promete por escrito que zero desliga a poda, e `NumeroDoEnv::diasOuDesligado()` deixa o zero passar de propósito. EP com um valor "válido" não distingue `> 0` de `>= 0`, que é exatamente o defeito que `.ai/rules/config.md` documenta como já tendo apagado dado neste kit |
| R4 | Infolist de config (**mínimo**) | **EP com 3 partições** (ausente/vazio/preenchido) | `CorPrimaria::paleta()` trata `null` e `''` como o mesmo caso, e é a única chave da página cujo domínio tem os três estados |

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | uma Page Filament fora de painel, uma view Blade de uma linha, uma linha em `routes/web.php`. **Apaga** `resources/views/welcome.blade.php`. Sem migration, model, job, policy, command, config, seeder | CT-02 |
| **F** | renderizar; ler 12 chaves de config; montar a URL de 3 painéis; formatar booleano e prazo. **A função escondida a checar não é o que a página faz — é o que ela não deve deixar escapar** (`.ai/rules/config.md` chama isso de fronteira, e é onde os defeitos deste kit moraram) | CT-01, CT-03, CT-07, CT-12 |
| **D** | **entrada: nenhuma.** Sem parâmetro de rota, sem query string lida, sem corpo. Dados lidos: `config('app.name')`, `config('kit.*')`. Dados que **existem e não devem sair**: `kit.admin.*`, `database.*`, `kit.repository`, `app.env`, `mail.*`. Bordas: `kit.retencao.* <= 0`, `kit.convites.lembretes_dias == []`, `kit.cor_primaria` ausente/vazia, `app.name` com HTML | CT-07, CT-08, CT-10, CT-11, CT-12, CT-16, CT-17 |
| **I** | **uma só**: `GET /` anônimo. Nenhum comando artisan, job, webhook ou API. A página é estática, então o round-trip Livewire nunca é exercido — declarado como não-interface | CT-01 |
| **P** | navegador (Alpine + `localStorage` para o tema); **não** depende do manifest do Vite — medido: `grep -rn '@vite' resources/ app/` devolve **uma** ocorrência, a linha 13 de `welcome.blade.php`, que este plano apaga; nenhum painel do kit usa `viteTheme()` (declarado no cabeçalho de `resources/css/filament/cards.css`). ⚠️ O grep cobre `resources/` e `app/`, **não** as blades de vendor — não é prova de que nenhuma delas emita `@vite` | CT-14, CT-B01 |
| **O** | três perfis de uso: visitante anônimo (o caso real — o dev que acabou de rodar `create-project`), visitante autenticado (a página deve funcionar igual) e reconhecimento hostil (o motivo da lista negra do ADR-04) | CT-01, CT-12, CT-13 |
| **T** | **não se aplica**: a página não exibe data nem hora, nada expira, nada é agendado, nada depende de ordem ou de concorrência. Nenhum cenário de timezone, DST ou virada de dia é derivável | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a rota `/` devolve a página de boas-vindas do kit a um visitante anônimo, e a welcome padrão do Laravel deixa de existir | Substituição (mínimo) | RQ-01, RQ-02 | EP | CT-01, CT-02 |
| **R2** — há exatamente um cartão por painel do kit, e cada um aponta para a raiz do seu painel | Cartões (mínimo) | RQ-03 | EP (uma partição por painel) | CT-03, CT-04 |
| **R3** — os cartões são renderizados pelo pacote de Cards, sob o escopo de CSS que o kit mantém para ele | CSS/tema (padrão) | RQ-04, RQ-10 | EP | CT-05, CT-06 |
| **R4** — a página exibe o **valor efetivo lido da config**, não um texto escrito na tela | Infolist (mínimo) | RQ-05, RQ-06, RQ-07, RQ-09, RQ-12 | EP + ausente≠null≠vazio | CT-07, CT-08, CT-16, CT-17 |
| **R5** — chave de config em que zero ou lista vazia significa "desligado" é exibida como desligada, nunca como o número | Infolist (mínimo → escalada) | RQ-06 | **BVA 2-valores**, incremento 1 dia | CT-10, CT-11 |
| **R6** — nenhum segredo, credencial, e-mail de administrador ou dado de infraestrutura aparece na resposta de `/` | Segredo (padrão) | RQ-06, RQ-07 + premissa de anonimato do `00` | **EP exaustiva** sobre a lista negra do ADR-04 | CT-12, CT-13 |
| **R7** — a página herda a folha, a paleta e o tema claro/escuro do painel do kit | CSS/tema (padrão) | RQ-10, RQ-11 | EP + verificação discriminante de paleta | CT-14, CT-15, **CT-B01** |

Toda cláusula `RQ` do `00-requisito.md` aparece em ao menos uma regra, com uma exceção declarada:

| RQ sem regra | Por quê |
|---|---|
| **RQ-08** ("use a skill de design") | Cláusula sobre o **processo** de produção da tela, não sobre o comportamento do sistema. Não é falsificável por teste automatizado: nenhuma asserção distingue uma tela desenhada de uma tela improvisada. Verificada por artefato — `design/Main.dc.html` e o artboard publicado — e conferida pelo roteiro "Desenhado × Implementado" do `05` |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| classe `App\Filament\Pages\BoasVindas` | escolha de implementação | detalhe do cenário |
| método `informacoesDoKit()` | escolha de implementação | detalhe do cenário |
| `$layout = 'filament-panels::components.layout.simple'` | escolha de implementação (ADR-02) | detalhe. O que **é** oráculo é a consequência observável: sem barra lateral e sem menu de usuário para anônimo — CT-06 e CT-13 |
| nome da rota `boas-vindas` | escolha de implementação | detalhe |
| `Width::SevenExtraLarge` | escolha de implementação | não virou cenário |
| rótulos dos cartões ("Painel do negócio", "Administração", "Infraestrutura") | **comportamento visível que o requisito não determina** — o requisito só diz "cards para acessar os paines" | os cenários asserem o **`href`** de cada cartão, não o rótulo. Pergunta registrada abaixo |
| textos das seções da infolist | idem | os cenários asserem o **valor** exibido, não o rótulo |

**Proxy declarado.** CT-05 assere a classe `kit-cards-page` no HTML, e ela é escolha de
implementação. É usada como **proxy barato** de "o CSS dos cartões alcança a página", porque o
oráculo honesto disso é visual e só o navegador o prova. Declarado aqui para que a próxima pessoa
saiba que CT-05 é apoio e CT-B01 é a prova.

**Perguntas em aberto** (bloco pronto para colagem em `00-requisito.md` → `## Ambiguidades`):

```markdown
- **RQ-03** — os rótulos e as frases dos três cartões não vêm do requisito.
  - **Assumido**: "Painel do negócio" (`/app`), "Administração" (`/admin`), "Infraestrutura"
    (`/infra`), com uma frase cada, escritas no código. Cenários marcados `@premissa`.
  - **Se negado**: só os textos mudam; nenhum cenário é refeito (eles asserem o `href`).

- **RQ-05 / RQ-06** — quais chaves de config, e em que ordem.
  - **Assumido**: as 12 do ADR-04, em duas seções ("Este projeto", "Configuração do kit").
  - **Se negado**: as linhas do `Esquema do Cenário` de CT-07 mudam; a regra R4 e a técnica não.
```

## Setup Global

### Personas

A feature tem **uma** persona relevante — o **visitante anônimo**, que é a ausência de persona.
Nenhum `actingAs()`, nenhum papel, nenhum seeder de permissão. É deliberado, e é o que CT-01
afirma.

Um segundo perfil, o **visitante autenticado**, aparece só em CT-13, com
`usuarioDoKit('master_global')` (helper de `tests/Pest.php`) — para provar que a página não muda de
comportamento e continua sem chrome de painel.

### Fixtures

Nenhuma. A página não lê banco.

### Fakes

Nenhum. A página não envia e-mail, não enfileira job, não faz requisição HTTP externa.

### Estratégia de DB

`RefreshDatabase` global (`tests/Pest.php`, para `Kit`, `Tenancy` e `Browser`), com
`DB_DATABASE=:memory:` do `phpunit.xml`. A página não usa banco, mas o `RefreshDatabase` fica —
tirá-lo de um arquivo só é divergência sem ganho.

### Arranjo de config nos cenários

`phpunit.xml` **fixa** `KIT_COR_PRIMARIA=''`, `KIT_DEMO=false`, `KIT_HUB=false`,
`KIT_TENANCY_LABEL='Organização'` e `KIT_TENANCY_LABEL_PLURAL='Organizações'`, com `force="true"`.
Os cenários que precisam de outro valor usam `config()->set(...)` **antes** do `$this->get('/')` —
o painel avalia `->colors()` e a página lê `config()` dentro do request, então o `set` alcança.

Isso é o que permite CT-07 e CT-08 formarem par: **um planta valores, o outro afirma o de fábrica**.
Sem o par, uma implementação que escreve os valores literalmente na tela passa nos dois.

---

## Regra R1 — a rota `/` devolve a página de boas-vindas, e a welcome padrão deixa de existir

> `RQ-01`, `RQ-02` · área **Substituição** (mínimo) · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Página de boas-vindas na raiz do site

  Regra: a rota "/" devolve a página de boas-vindas do kit a um visitante anônimo,
         e a welcome padrão do Laravel deixa de existir

    Cenário: [CT-01] o visitante anônimo recebe a página de boas-vindas do kit
      Dado que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta tem status 200
      E a resposta traz o título "Bem-vindo ao Starter Kit Easy"
      E a resposta traz o nome da aplicação lido de config('app.name')
      E a resposta não traz o texto "Documentation" da welcome padrão do Laravel

    Cenário: [CT-02] a welcome padrão do Laravel não existe mais no projeto
      Dado o projeto depois desta entrega
      Quando alguém pergunta ao resolvedor de views se "welcome" existe
      Então a resposta é falsa
```

**Notas de execução**

- CT-01: `$this->get('/')->assertOk()->assertSee('Bem-vindo ao Starter Kit Easy')`. A terceira
  asserção usa `config('app.name')` lido no próprio teste, nunca a string `'Starter Kit'`
  literal — o `phpunit.xml` não fixa `APP_NAME`, e cravar o valor tornaria o caso um teste do
  `.env` de quem roda a suíte (é a razão declarada dos `force="true"` do `phpunit.xml`).
- CT-01, quarta asserção: `assertDontSee('Documentation')` é a discriminante contra o mutante
  "a rota continua em `view('welcome')`" — a welcome de fábrica traz esse texto, a nossa não.
- CT-02: `expect(View::exists('welcome'))->toBeFalse()`. Vive em `tests/Kit` e **não** em
  `tests/Unit`: o `tests/Pest.php` liga `Tests\TestCase` a `Feature`, `Kit`, `Tenancy`, `Browser` e
  `BrowserTenancy` — **não** a `Unit`. Um caso "unitário" ali rodaria sem container e o
  `View::exists()` não resolveria.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a rota `/` continua devolvendo `view('welcome')` | CT-01 (`assertDontSee('Documentation')` + o título ausente) |
| M2 | a página nasce numa rota nova (`/boas-vindas`) e `/` fica intocada | CT-01 |
| M3 | `welcome.blade.php` fica no repositório, órfã | CT-02 |
| M4 | a rota é registrada **sem** o middleware `panel:app` | CT-01 — sem painel corrente, `filament()->getTheme()` do `layout.base` estoura e a resposta não é 200 |

---

## Regra R2 — um cartão por painel, apontando para a raiz do painel

> `RQ-03` · área **Cartões** (mínimo) · técnica: **EP** — uma partição por painel

```gherkin
# language: pt

  Regra: há exatamente um cartão por painel do kit, e cada um aponta para a raiz
         do seu painel

    Esquema do Cenário: [CT-03] cada painel do kit tem um cartão que leva à sua raiz
      Dado que ninguém está autenticado
      E que a multi-organização está desligada
      Quando o visitante abre a rota "/"
      Então a resposta traz um link cujo destino é "<destino>"

      Exemplos:
        | painel | destino  | # partição            |
        | app    | /app     | painel de negócio     |
        | admin  | /admin   | painel de administração |
        | infra  | /infra   | painel de infraestrutura |

    Cenário: [CT-04] com a multi-organização ligada, o cartão do negócio ainda leva a "/app"
      Dado que a multi-organização está ligada
      E que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta traz um link cujo destino é "/app"
      E a resposta não traz nenhum link com um segmento de organização depois de "/app"
```

**Notas de execução**

- CT-03 assere o `href`, não o rótulo — os rótulos são premissa (ver `## Fronteira com o Plano`).
  Em Pest: `assertSee('href="'.url('/app').'"', escape: false)`, com a URL montada por `url()` no
  próprio teste.
- CT-04 vive em `tests/Tenancy/BoasVindasTest.php`, e não em `tests/Kit`: `Tests\TenancyTestCase`
  fixa `permission.teams` em `createApplication()`, antes das migrations, e o Pest não permite dois
  TestCases na mesma pasta (`.ai/rules/testes.md`). É a **única** forma de exercer a partição
  "tenancy ligada".
- CT-04, segunda asserção: a discriminante. `Panel::getUrl()` com tenancy e sem usuário cai no
  `return url($this->getPath())` de `HasRoutes.php:196`; uma implementação que resolvesse o tenant
  mais cedo produziria `/app/{slug}` ou uma exceção.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | um painel esquecido — só `/app` e `/admin` ganham cartão | CT-03 (linha `infra`) |
| M6 | URL montada à mão com `url('/app')` em vez de `Filament::getPanel('app')->getUrl()` | ⚠️ **sem matador** — **lacuna declarada**. As duas produzem exatamente `/app` nas duas partições de tenancy, e a diferença só aparece com `->domains()` configurado no painel, que o kit não usa. Tentado: cenário com `Filament::getPanel('app')->domains([...])` no arranjo — o painel já registrou rotas no boot da aplicação e reconfigurá-lo no teste não reescreve as rotas. Declarado em vez de fingido |
| M7 | o cartão do `/app` resolve o tenant e emite `/app/{slug}` | CT-04 |
| M8 | `CardItem::make(AlgumaPage::class)` no lugar da URL — o `make()` despacha para o ramo de classe e o `href` sai vazio | CT-03 (as três linhas) |

---

## Regra R3 — os cartões vêm do pacote, sob o escopo de CSS do kit

> `RQ-04`, `RQ-10` · área **CSS/tema** (padrão) · técnica: **EP**

```gherkin
# language: pt

  Regra: os cartões são renderizados pelo pacote de Cards, sob o escopo de CSS
         que o kit mantém para ele

    Cenário: [CT-05] a grade sai sob o escopo de CSS que o kit mantém para os cartões
      Dado que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta traz a classe de escopo "kit-cards-page"
      E a resposta traz a folha "kit-cards.css"
      E a resposta traz a classe de grade "lg:grid-cols-3"

    Cenário: [CT-06] a página pública não traz a barra lateral nem o menu de usuário do painel
      Dado que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta não traz o elemento da barra lateral do painel
      E a resposta não traz o cabeçalho de identidade do layout simples
      E a resposta traz o container do layout simples
```

**Notas de execução**

- CT-05, terceira asserção: `lg:grid-cols-3` é a única largura de grade que
  `resources/css/filament/cards.css` cobre junto com `md:grid-cols-2` e `xl:grid-cols-4`; o
  cabeçalho do arquivo declara que `$columns >= 5` **nunca** teria classe gerada. Asserir a classe
  presente é o que mata M10.
- CT-06: `assertDontSee('id="fi-main-sidebar"')` + `assertDontSee('fi-simple-layout-header')` +
  `assertSee('fi-simple-main-ctn')`. As três juntas — as duas primeiras sozinhas passariam numa
  página vazia.
- **Corrigido na implementação (causa "a", CT especificado errado):** a primeira redação usava a
  **classe** `fi-sidebar` e ficou vermelha sem defeito nenhum. Medido: `fi-sidebar` aparece **11
  vezes** na resposta, todas dentro de blocos `<style>` — o CSS do
  `gsferro/filament-odometer-easy` e o `resources/css/filament/kit.css` escrevem seletores
  `.fi-main-sidebar` que existem mesmo numa página sem barra lateral. Nome de classe em texto de
  folha de estilo não é elemento renderizado; o `id`, que só
  `vendor/filament/filament/resources/views/livewire/sidebar.blade.php:20` emite, é.
- **Nenhum destes dois cenários prova que a grade está legível.** Eles provam que o veículo do
  estilo chegou. A legibilidade é CT-B01, e a declaração está em `## Fronteira com o Plano`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | `getPageClasses()` não sobrescrito — a grade sai sem o escopo e, portanto, sem estilo nenhum, com HTML correto | CT-05 |
| M10 | `$columns` fora do conjunto que o `cards.css` cobre (5 ou mais) | CT-05 (a classe `lg:grid-cols-3` deixaria de existir) |
| M11 | a página usa o layout `index` — barra lateral vazia e menu de usuário sem usuário | CT-06 |

---

## Regra R4 — a página exibe o valor efetivo lido da config

> `RQ-05`, `RQ-06`, `RQ-07`, `RQ-09`, `RQ-12` · área **Infolist** (mínimo, técnica escalada) ·
> técnica: **EP** + **ausente ≠ null ≠ vazio**

```gherkin
# language: pt

  Regra: a página exibe o valor efetivo lido da config, e não um texto escrito na tela

    Esquema do Cenário: [CT-07] cada informação exibida vem da chave de config dela
      Dado que a chave "<chave>" vale "<plantado>"
      E que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta traz "<exibido>"

      Exemplos:
        | chave                              | plantado                | exibido                 | # partição               |
        | app.name                           | Projeto Sentinela       | Projeto Sentinela       | nome da aplicação        |
        | kit.version                        | 9.9.9-sentinela         | 9.9.9-sentinela         | versão do kit            |
        | kit.tenancy.label                  | Unidade                 | Unidade                 | rótulo singular          |
        | kit.tenancy.label_plural           | Unidades                | Unidades                | rótulo plural            |
        | kit.idiomas                        | ["pt_BR","en"]          | pt_BR, en               | lista de idiomas         |
        | kit.convites.validade_em_dias      | 21                      | 21 dias                 | prazo de convite         |
        | kit.convites.limite_do_lote        | 42                      | 42                      | limite do lote           |
        | kit.retencao.importacoes_em_dias   | 77                      | 77 dias                 | retenção de importações  |

    Esquema do Cenário: [CT-08] com a configuração de fábrica, os valores do kit aparecem
      Dado que config('kit.convites.validade_em_dias') vale efetivamente 7
      E que config('kit.convites.limite_do_lote') vale efetivamente 100
      E que config('kit.retencao.excecoes_em_dias') vale efetivamente 14
      E que config('kit.retencao.importacoes_em_dias') vale efetivamente 30
      Quando o visitante abre a rota "/"
      Então a resposta traz "7 dias"
      E a resposta traz "100"
      E a resposta traz "14 dias"
      E a resposta traz "30 dias"

    Esquema do Cenário: [CT-16] a cor primária distingue ausente, vazia e escolhida
      Dado que a chave kit.cor_primaria está no estado "<estado>"
      Quando o visitante abre a rota "/"
      Então a resposta traz "<exibido>"

      Exemplos:
        | estado          | exibido                      | # partição |
        | ausente (null)  | Âmbar (padrão do Filament)   | ausente    |
        | string vazia    | Âmbar (padrão do Filament)   | vazio      |
        | "Violet"        | Violet                       | preenchido |

    Cenário: [CT-17] o nome da aplicação é escapado antes de ir para a tela
      Dado que config('app.name') vale "<script>alert(1)</script> Ação"
      E que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta não traz a marcação "<script>alert(1)</script>" como HTML
      E a resposta traz o nome escapado, com o acento de "Ação" preservado
```

**Notas de execução**

- **CT-07 e CT-08 são um par indivisível.** CT-07 planta valores que não existem em nenhum
  `.env` do kit — nenhum deles pode ser acertado por acidente. CT-08 afirma os de fábrica.
  Uma implementação que escreve os valores literalmente na tela passa em CT-08 e morre em CT-07;
  uma que lê a chave errada morre em CT-07, porque cada linha planta um valor diferente.
- **CT-08 afirma o valor efetivo, não "a configuração de fábrica"**: o `Dado` é
  `expect(config('kit.convites.validade_em_dias'))->toBe(7)` **dentro do teste**. Sem isso o caso
  mediria o `.env` de quem roda a suíte, e um default errado sobreviveria sem nada ficar vermelho
  — o `phpunit.xml` fixa cinco chaves com `force="true"`, mas **nenhuma** destas quatro.
- CT-07, linha `kit.idiomas`: o valor plantado tem **dois** idiomas, e não um. Com um só, uma
  implementação que exibe apenas o primeiro item da lista passaria.
- CT-16 usa `config(['kit.cor_primaria' => null])` e `''` em linhas separadas: `CorPrimaria::paleta()`
  trata os dois como "mantém o padrão", e uma implementação que só testa `=== null` mostraria uma
  linha vazia na string vazia.
- CT-17: `assertDontSee('<script>alert(1)</script>', escape: false)` **e**
  `assertSee('Ação')`. A segunda existe para o caso não passar com a página quebrada — o
  `assertDontSee` sozinho passa numa resposta 500.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | os valores são escritos como texto fixo na tela em vez de lidos da config | CT-07 (todas as linhas) |
| M13 | o rodapé não é ligado — a infolist é declarada e nunca renderizada | CT-07, CT-08 |
| M14 | chave vizinha trocada (`label_plural` no lugar de `label`) | CT-07 (as duas linhas de rótulo plantam valores diferentes) |
| M15 | a lista de idiomas é exibida como `Array` ou só o primeiro item | CT-07 (linha `kit.idiomas`, com dois itens) |
| M16 | `cor_primaria` vazia cai numa linha em branco em vez do padrão | CT-16 (linha "vazio") |
| M17 | o nome da aplicação é impresso sem escapar (`{!! !!}` ou `->html()`) | CT-17 |

---

## Regra R5 — zero e lista vazia são exibidos como "desligado", nunca como o número

> `RQ-06` · área **Infolist** (mínimo → **escalada para BVA 2-valores**) ·
> incremento: **1 dia** (as chaves são inteiro de dias)

```gherkin
# language: pt

  Regra: chave de config em que zero ou lista vazia significa "desligado" é exibida
         como desligada, nunca como o número

    Esquema do Cenário: [CT-10] o prazo de retenção mostra "Sem poda" quando está desligado
      Dado que config('kit.retencao.excecoes_em_dias') vale <dias>
      Quando o visitante abre a rota "/"
      Então a resposta traz "<exibido>"

      Exemplos:
        | dias | exibido   | # borda   |
        | -1   | Sem poda  | borda−1   |
        | 0    | Sem poda  | borda     |
        | 1    | 1 dia     | borda+1   |
        | 14   | 14 dias   | dentro    |

    Esquema do Cenário: [CT-11] os lembretes de convite mostram "Desligados" com a lista vazia
      Dado que config('kit.convites.lembretes_dias') vale <lista>
      Quando o visitante abre a rota "/"
      Então a resposta traz "<exibido>"

      Exemplos:
        | lista   | exibido      | # partição   |
        | []      | Desligados   | vazio        |
        | [3]     | 3º dia       | um elemento  |
        | [3,5]   | 3º e 5º dia  | dois         |
```

**Notas de execução**

- A fronteira é **0**, e as três linhas ao redor dela são o ponto do cenário. `.ai/rules/config.md`
  documenta que este exato limite, escrito com o comparador errado, **apagou a trilha de exceções
  inteira** neste kit (`subDays(0)` é hoje). Aqui a consequência é só de exibição, mas a fronteira
  é a mesma e o comparador errado é o mesmo.
- A linha `1` não é redundante com `14`: ela é a única que distingue `"1 dia"` de `"1 dias"`.
- CT-11 planta a lista com um e com dois elementos porque a formatação ordinal (`3º e 5º dia`)
  tem um ramo de junção que a lista de um elemento não exercita.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | `>= 0` no lugar de `> 0` — zero é exibido como "0 dias" | CT-10 (linha `0`) |
| M19 | nenhum ramo de desligado — sempre "n dias" | CT-10 (linhas `-1` e `0`) |
| M20 | negativo não tratado — "-1 dias" | CT-10 (linha `-1`) |
| M21 | plural fixo — "1 dias" | CT-10 (linha `1`) |
| M22 | `implode()` cru na lista vazia — a linha sai em branco, sem "Desligados" | CT-11 (linha `[]`) |

---

## Regra R6 — nenhum segredo aparece na resposta de `/`

> `RQ-06`, `RQ-07` + a premissa de anonimato do `00-requisito.md` · área **Segredo** (padrão) ·
> técnica: **EP exaustiva** — uma linha por item da lista negra do ADR-04

```gherkin
# language: pt

  Regra: nenhum segredo, credencial, e-mail de administrador ou dado de
         infraestrutura aparece na resposta da rota "/"

    Esquema do Cenário: [CT-12] o valor sentinela plantado em cada chave proibida não aparece
      Dado que a chave "<chave>" vale "<sentinela>"
      E que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta tem status 200
      E a resposta não traz "<sentinela>"

      Exemplos:
        | chave                                | sentinela                          | # motivo do ADR-04         |
        | kit.admin.email                      | admin-sentinela@proibido.test      | enumeração de conta        |
        | kit.admin.password                   | SenhaSentinela9Z                   | credencial                 |
        | kit.admin.name                       | NomeAdminSentinela9Z               | mesmo eixo, sem valor      |
        | kit.repository                       | https://git.interno.sentinela/x.git | rede interna              |
        | database.connections.mysql.host      | host-sentinela.interno             | topologia                  |
        | database.connections.mysql.username  | usuario-sentinela-db               | credencial                 |
        | app.env                              | ambiente-sentinela                 | reconhecimento             |
        | mail.mailers.smtp.username           | smtp-sentinela                     | credencial                 |

    Cenário: [CT-13] a página não muda de comportamento para quem está autenticado
      Dado um usuário com sessão ativa
      Quando esse usuário abre a rota "/"
      Então a resposta tem status 200
      E a resposta traz o título "Bem-vindo ao Starter Kit Easy"
      E a resposta traz o link do painel de administração
```

**Notas de execução**

- CT-12 é **exaustivo por construção**: uma linha por item da lista negra do ADR-04. Se alguém
  acrescentar uma entrada à infolist lendo uma dessas chaves, a linha correspondente fica
  vermelha. É o mecanismo de mitigação que o ADR-04 promete.
- **Uma exceção, declarada:** `config('app.url')` está na lista negra do ADR-04 e **não** tem linha
  aqui. Ele alimenta o gerador de URL do Laravel, e os `href` dos três cartões vêm de `url()`;
  plantar um sentinela ali arriscaria uma falha por um caminho que não é o do caso, medindo o
  gerador de URL em vez da página. A chave continua proibida na página — o que não existe é o
  cenário sentinela dela.
- Os sentinelas são **distintivos de propósito**: cada um contém `sentinela` e um sufixo, para que
  `assertDontSee` não case por acidente com texto de layout, com nome de classe CSS ou com script
  do Filament. `assertDontSee('password')` cru seria inútil.
- A primeira asserção (`status 200`) não é enfeite: sem ela, uma resposta 500 passaria em todas as
  linhas de `assertDontSee`. É a armadilha do "cenário de recusa que não afirma o não-efeito"
  aplicada a uma asserção de ausência.
- **CT-13 foi re-especificado na implementação (causa "a", CT especificado errado).** A primeira
  redação afirmava a **ausência do e-mail** do próprio usuário autenticado, e isso estava errado
  como especificação: com sessão ativa o `layout.simple` renderiza a topbar
  (`components/layout/simple.blade.php:30`), e o kit pendura ali o cabeçalho de identidade
  (`resources/views/filament/user-menu-header.blade.php`, pelo render hook
  `USER_MENU_PROFILE_BEFORE`). O e-mail aparece — para o dono da sessão, no menu de usuário padrão
  do Filament. **Isso não é vazamento; é a tela funcionando.** O oráculo de "nenhuma identidade
  para quem não está autenticado" migrou para CT-06, onde não há sessão nenhuma.
- CT-13 usa `usuario()` e **não** `usuarioDoKit()`: o caso é sobre sessão, não sobre autorização, e
  pedir um papel obrigaria a semear `PapeisSeeder` + `ShieldPermissionsSeeder` para um cenário que
  não consulta permissão. Sem os seeders o caso morria no arranjo com
  `RoleDoesNotExist: There is no role named 'master_global'` — defeito de suíte, não de código
  (`.ai/rules/testes.md`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | uma entrada "Administrador inicial" acrescentada à infolist com `config('kit.admin.email')` | CT-12 (linha `kit.admin.email`) |
| M24 | uma entrada "Repositório do kit" com link para `config('kit.repository')` | CT-12 (linha `kit.repository`) |
| M25 | uma entrada "Ambiente" com `config('app.env')` | CT-12 (linha `app.env`) |
| M26 | a página usa o layout `index`, que traz o menu de usuário com nome e e-mail | CT-13, CT-06 |

---

## Regra R7 — a página herda a folha, a paleta e o tema do painel

> `RQ-10`, `RQ-11` · área **CSS/tema** (padrão) · técnica: **EP** + verificação **discriminante**
> de paleta

```gherkin
# language: pt

  Regra: a página herda a folha, a paleta e o tema claro/escuro do painel do kit

    Cenário: [CT-14] a resposta traz a folha do Filament e o script de tema do painel
      Dado que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a resposta traz a folha "css/filament/filament/app.css"
      E a resposta traz a folha "kit-correcoes.css"
      E a resposta traz a variável CSS "--primary-500"
      E a resposta traz a função "loadDarkMode" que aplica o tema salvo

    Cenário: [CT-15] a cor primária escolhida pelo projeto chega até a página
      Dado que config('kit.cor_primaria') vale "Violet"
      E que ninguém está autenticado
      Quando o visitante abre a rota "/"
      Então a variável CSS "--primary-500" tem o tom 500 da paleta Violet do Filament
```

**Notas de execução**

- **CT-15 é o cenário discriminante desta regra, e o único que distingue as duas
  implementações.** Medido antes de escrever o plano: `FilamentAsset::renderStyles()` **sem painel
  corrente**, com `KIT_COR_PRIMARIA=Violet` na env, emite
  `--primary-500:oklch(0.769 0.188 70.08)` — **âmbar**, o default do Filament. A paleta do projeto
  só entra por `Panel::boot()` → `FilamentColor::register($this->getColors())`
  (`vendor/filament/filament/src/Panel.php:95`). Uma página que emitisse só `@filamentStyles`
  passaria em CT-14 e morreria aqui.
- É por isso que a asserção usa `Violet`, e não a cor de fábrica: com `KIT_COR_PRIMARIA` vazio
  (o que o `phpunit.xml` fixa) a cor correta **é** o âmbar do default, e as duas implementações
  produziriam o mesmo byte. Valor redondo, no vocabulário da técnica.
- O tom é lido de `Filament\Support\Colors\Color::Violet[500]` no próprio teste — nunca escrito como
  literal `oklch(...)`, que quebraria num upgrade de paleta do Filament sem defeito nenhum no kit.
- **Corrigido na implementação (causa "a", CT especificado errado):** a primeira redação afirmava
  também `assertDontSee(Color::Amber[500])` e ficou vermelha sem defeito. Medido: o âmbar **é** a
  paleta padrão de `--warning-*` no Filament, então `oklch(0.769 0.188 70.08)` aparece na resposta
  de qualquer jeito. A asserção passou a ser sobre o **par** `--primary-500:{tom}`, que prende a cor
  à variável certa — é isso que o cenário quer dizer, e é mais forte que a asserção anterior.
- CT-14, quarta asserção: `assertSee('loadDarkMode')` prova que o **script** está lá. Não prova
  que o tema é aplicado — isso é CT-B01, e a distinção está declarada em `.ai/rules/testes-browser.md`
  ("`assertSee` não valida tema").

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | a página emite `@filamentStyles` sem painel corrente — a folha do Filament não sai | CT-14 (primeira asserção) |
| M28 | a página emite a folha, mas a paleta do projeto é ignorada | **CT-15** |
| M29 | o script de tema é omitido (layout próprio em vez do `layout.base` do painel) | CT-14 (quarta asserção) + CT-B01 |
| M30 | o tema é forçado em claro (`darkMode(false)` ou `theme` fixado) | CT-B01 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a rota não recebe `{id}`, nem parâmetro de rota, nem query string lida. Não há recurso de dono |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: a página não executa ação nenhuma. A ausência de filtro por autorização nos cartões é decisão declarada em ADR-03, não lacuna |
| Idempotência (ancorada no agregado) | **não se aplica**: sem operação de escrita. Não existe agregado em que ancorar — a `/` é `GET` puro |
| Concorrência | **não se aplica**: sem contador, saldo, estoque ou limite consumível |
| Fronteira no ponto de entrada (gravação) | **não se aplica na gravação** — a página não grava. A fronteira de **leitura** existe e é CT-10 |
| Domínio condicionado (tipo × valor) | **não se aplica**: nenhuma chave exibida tem domínio que dependa de outra |
| Estado × operação de escrita | **não se aplica**: sem entidade com ciclo de vida |
| **Ausente ≠ null ≠ vazio** | **CT-16** (`kit.cor_primaria`) e **CT-11** (`lembretes_dias` vazia) |
| Paginação / ordenação | **não se aplica**: nenhuma listagem paginada; a ordem dos cartões é fixa em código |
| Timezone / DST | **não se aplica**: a página não exibe data nem hora, e nada expira. Declarado também em SFDIPOT → T |
| Unicode / limite de varchar / HTML em texto livre | **CT-17** (`APP_NAME` com `<script>` e acento) |
| Unicidade + soft delete | **não se aplica**: sem persistência |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: sem formulário e sem corpo de requisição |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: nenhum valor monetário |
| **Vazamento em superfície pública** (linha nova, própria deste kit) | **CT-12** — nove partições, uma por item da lista negra do ADR-04 |
| **Página anônima que não revela identidade** | **CT-13** |

> A penúltima linha é candidata a virar linha permanente da taxonomia do projeto: "rota anônima
> que exibe `config()` exige um cenário de ausência por chave sensível". Registrada como proposta
> de rule no `03-progresso.md`.

## Divergência entre esta skill e as rules do projeto

| A skill diz | A rule do projeto diz | O que vale |
|---|---|---|
| `pest --parallel --tia` como comando padrão | `.ai/rules/testes-browser.md`: `--parallel` derrube 4 dos 11 CT-B, e sem PCOV o `--tia` não termina (abortado após 35 min) | **A rule.** Dois comandos: `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` (com `--parallel` onde já é o padrão do projeto) e `composer test:browser` em série |
| `pest --mutate` fecha o ciclo | — | Roda, **com a ressalva abaixo** |

**Ressalva do mutation testing nesta feature.** A página é quase inteira **declarativa** —
`CardItem::make()->label()->url()`, `TextEntry::make()->state()`. Os operadores de mutação
(relacional, lógico, aritmético, literal) têm quase nada em que morder: o único código com ramo é
o formatador de prazo e lista de R5. Consequência: um score alto aqui não diz quase nada sobre a
qualidade do conjunto, e um score baixo aponta para o formatador. Escopar em
`--path=app/Filament/Pages` e ler o resultado **como medida do formatador, não da feature**.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | visitante anônimo recebe a página do kit | R1 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M1, M2, M4 |
| CT-02 | a welcome padrão não existe mais | R1 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M3 |
| CT-03 | um cartão por painel, com o `href` da raiz | R2 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M5, M8 |
| CT-04 | com tenancy, o cartão do `/app` ainda leva a `/app` | R2 | EP | Feature (HTTP) | `tests/Tenancy/BoasVindasTest.php` | M7 |
| CT-05 | a grade sai sob o escopo `kit-cards-page` | R3 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M9, M10 |
| CT-06 | sem barra lateral nem menu de usuário | R3 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M11, M26 |
| CT-07 | cada informação vem da sua chave de config | R4 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M12, M13, M14, M15 |
| CT-08 | com a config de fábrica, os valores do kit aparecem | R4 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M12, M13 |
| CT-10 | prazo desligado mostra "Sem poda" | R5 | BVA | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M18, M19, M20, M21 |
| CT-11 | lembretes vazios mostram "Desligados" | R5 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M22 |
| CT-12 | sentinela de cada chave proibida não aparece | R6 | EP exaustiva | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M23, M24, M25 |
| CT-13 | não revela identidade a quem está autenticado | R6 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M26 |
| CT-14 | folha do Filament, CSS do kit e script de tema | R7 | EP | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M27, M29 |
| CT-15 | a cor primária do projeto chega até a página | R7 | discriminante | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | **M28** |
| CT-16 | cor primária ausente ≠ vazia ≠ escolhida | R4 | ausente≠null≠vazio | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M16 |
| CT-17 | o nome da aplicação é escapado | R4 | injeção em texto livre | Feature (HTTP) | `tests/Kit/BoasVindasTest.php` | M17 |
| CT-B01 | abre em tema escuro, legível e sem erro de JS | R7 | — | **Browser** | `tests/Browser/BoasVindasTest.php` | M29, M30, M31 |
| CT-B02 | sem problema de acessibilidade nos dois temas | R7 | — | **Browser** | `tests/Browser/BoasVindasTest.php` | M32, M33 |

Não existe CT-09: a numeração foi mantida contígua no `Esquema do Cenário` de CT-07/CT-08 e o
identificador ficou vago durante a poda. Registrado em vez de renumerado — renumerar quebraria as
referências das tabelas de mutantes.

## Estouros de teto, declarados

| Regra | Teto do perfil | Cenários | Justificativa |
|---|---|---|---|
| R2 | 1 (área mínimo) | 2 | CT-04 exerce a partição "tenancy ligada", que só existe noutra suíte (`Tests\TenancyTestCase`) e é o único matador de M7 |
| R4 | 1 (área mínimo) | 4 | CT-07 e CT-08 são um par — nenhum dos dois sozinho mata M12. CT-16 e CT-17 vêm do checklist de taxonomia, não da regra |
| R5 | 1 (área mínimo) | 2 | duas chaves com semânticas diferentes de "desligado": inteiro zero e lista vazia. Um cenário só deixaria M22 vivo |

Nenhum estouro veio de caminho feliz redundante: cada cenário acima é o único matador de ao
menos um mutante.

## Cenários cogitados e cortados

| Cenário cogitado | Por que foi cortado |
|---|---|
| `GET /` responde 200 também com `HEAD` | não mata mutante nenhum; o roteador do Laravel registra `GET\|HEAD` junto |
| a rota `/` tem o nome `boas-vindas` | teste do PRD, não do requisito (ver `## Fronteira com o Plano`) |
| a página responde 200 com a multi-organização **desligada** e **ligada** | CT-01 e CT-04 já cobrem as duas partições nas duas suítes |
| cada rótulo de cartão aparece na tela | os rótulos são premissa; asserir texto de premissa fixa uma decisão que o usuário ainda pode mudar |
| acessibilidade da página (`assertNoAccessibilityIssues`) | o teto de CT-B do perfil padrão é 1, e o único mutante que ela mataria (contraste próprio) não está previsto. Registrado como candidato para quando a página ganhar conteúdo próprio |
| screenshot comparado (`assertScreenshotMatches`) | o kit não tem baseline de screenshot versionada, e `tests/Browser/Screenshots` é limpo a cada run |
| `Livewire::test(BoasVindas::class)` para a infolist | o componente não tem interação: `fillForm`, `callAction` e `assertNotified` não têm o que exercer. A camada mais barata que prova é o HTTP, que já renderiza tudo |
