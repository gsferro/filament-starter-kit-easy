# Casos de Teste — A arte do login exibe o nome da aplicação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. O `01` entrou só para paths, stack e a tabela `## Superfície de UI`;
> o `02` entrou só para o envelope da ADR-01 e a decisão declarada da ADR-02. Nenhum cenário foi
> escrito olhando a implementação — ela ainda não existe.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — geração da arte padrão (nome, escape, uma linha de texto) | 3 | 2 | 6 | **padrão** |
| A2 — precedência da arte enviada (fallback trocado sob um contrato existente) | 2 | 2 | 4 | **padrão** |
| A3 — as superfícies de autenticação dos três painéis | 3 | 2 | 6 | **padrão** |
| A4 — documentação (READMEs) e capturas | 1 | 1 | 1 | **mínimo** |

**Por que P=3 em A1 e A3**: não é código novo isolado. `IdentidadeDoKit::arteDoLogin()` é consumida
em **10 chamadas literais** nos três PanelProviders **e**, indiretamente, por duas telas que não têm
`->media()` próprio e herdam a arte pela chave de outra: a de bloqueio (`TelaBloqueio`, chave `login`)
e o desafio de 2FA (`TelaDoisFatores`, chave `password-reset`), ambas lendo o
`AuthDesignerConfigRepository`. O tipo de retorno continua `string`, mas a **forma** do valor muda —
de URL para data URI — e há consumidor de terceiro no caminho (`MediaDetector`, `media.blade.php`).

**Por que I=2 e não 3**: nada de dinheiro, autorização, dado de terceiro ou irreversível. O pior caso
é a arte sumir das seis telas públicas de autenticação de toda instalação cujo `APP_NAME` tenha `&` —
visível, constrangedor e reversível por `git revert`.

**Técnica escalada acima do perfil da área (declarado)**: a regra R3 (escape XML) recebe **EP por
classe de caractere com oráculo duplo**, mais forte do que o orçamento da área sugeriria. Motivo: sem
escape o documento inteiro é inválido, e o modo de falhar é **silencioso no teste e total na tela** —
nenhum status HTTP se move.

- Técnicas aplicadas: EP por classe de caractere, tabela de decisão (3 partições da precedência),
  matriz superfície × painel, rastreio de efeito (o `warning` do `doDisco`).
- Cenários: **11 CT + 1 CT-B** · Regras: **7** · Mutantes previstos: **31** · Sem matador: **1**
  (declarado — o transbordo visual da ADR-02).
- Revisão adversarial: **1 rodada**, 5 defeitos sobreviventes + 7 oráculos fracos. Todos fechados —
  ver `## Revisão Adversarial`.
- **Estouro de teto declarado**: R3 e R4 ficam com 6 mutantes cada. Os excedentes (M28 e M31) vieram
  da revisão adversarial, e a skill isenta mutante achado em revisão do teto de 5 — ele é achado
  medido, não enchimento, e desdobrar a regra em duas renumeraria a rastreabilidade por motivo
  cosmético.

## Divergências entre a skill e as rules do projeto

| A skill diz | O projeto diz | Quem vence |
|---|---|---|
| a camada mais barata é `Unit` | `tests/Pest.php` **não liga `TestCase` a `tests/Unit`** — só a `Feature`, `Kit`, `Tenancy`, `Browser` e `BrowserTenancy`. Um caso em `tests/Unit` roda **sem container**: `config()`, `view()` e `asset()` não resolvem | **o projeto**. A camada mais barata que o arnês sustenta é `tests/Kit`, e é lá que todo CT deste conjunto mora |
| `pest --parallel --tia` como padrão | `.ai/rules/testes-browser.md` e o `tests/Pest.php`: **nunca `--parallel` com browser**, e o `--tia` exige run completo | **o projeto**. São dois comandos: `php artisan test --testsuite=Kit --parallel --compact` e `composer test:browser` |
| helper de teste vive onde for usado | `.ai/rules/testes.md`: helper usado por **mais de um arquivo** vive em `tests/Pest.php`, sob pena de `Call to undefined function` em `--parallel`, `--tia` e arquivo isolado — enforçado por `tests/Kit/HelpersDeTesteTest.php` | **o projeto**. Ver `## Setup Global` |

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | a fonte da arte padrão (hoje `public/images/auth/login.svg`), `IdentidadeDoKit::arteDoLogin()`, a constante `ARTE_PADRAO`, os dois READMEs e as capturas em `art/` | CT-01, CT-02, CT-11 |
| **F** | montar a arte com o nome; escapar o nome como texto XML; deixar **uma** linha de texto; preservar a precedência da arte enviada; devolver um valor usável em `<img src>` | CT-01…CT-08 |
| **D** | a entrada é uma string só: `config('app.name')`. Partições: comum, com `&`, com `<`/`>`, com aspas, com acento/emoji, **vazia**, **muito longa**. O segundo dado é o caminho gravado em `kit.identidade.arte_do_login`, com as três partições que a feature ancestral já definiu | CT-05, CT-08 |
| **I** | **10 rotas de autenticação alcançáveis**: `/{admin,app,infra}/login` e `/{admin,app,infra}/password-reset/request` (anônimas), `/app/register?token=…` (anônima, com convite válido), `/app/email-verification/prompt` (autenticado e não verificado). Mais o método `IdentidadeDoKit::arteDoLogin()` chamado direto, e duas telas que não têm `->media()` próprio e **herdam** a arte pela chave de outra — o desafio de 2FA (`/{painel}/two-factor-authentication`, chave `password-reset`) e a tela de bloqueio (`/{painel}/screen/lock`, chave `login`) —, ambas exercidas por linha própria em CT-10 | CT-09, CT-10, CT-B01 |
| **P** | o valor sai por `{{ $config->media }}` num `<img src>` — o Blade **escapa** o atributo, e base64 atravessa o escape intacto. `MediaDetector::getExtension()` faz `strtok($media, '?')`, `strtok($media, '#')` e `pathinfo()`: um data URI **cru** seria truncado no `#` do primeiro gradiente, e o `<` viraria `&lt;` no atributo. UTF-8 dentro do payload base64 | CT-02, CT-05, CT-B01 |
| **O** | quem instala roda `kit:install`, que reescreve o `APP_NAME`; quem troca o `APP_NAME` depois espera ver na hora; quem tem marca envia a própria arte pelas Settings | CT-03, CT-08 |
| **T** | não há concorrência, expiração, agendamento nem timezone. **Há uma dimensão temporal real**: "em tempo de execução" (RQ-02) é uma afirmação sobre **quando** o valor é lido — memoização estática no processo, leitura no `boot()` do provider ou `env()` no lugar de `config()` congelam o nome, e a tela passa a mentir depois de um `APP_NAME` novo | CT-03 |

Nenhuma dimensão ficou vazia.

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a arte padrão carrega o nome da aplicação, e não mais um texto fixo | A1 (padrão) | RQ-01 | EP + oráculo por decodificação | CT-01, CT-02 |
| **R2** — o nome é lido a cada render: trocar o `APP_NAME` muda a arte sem reinstalar | A1 (padrão) | RQ-02 | sequência de duas leituras no mesmo processo | CT-03 |
| **R3** — o nome entra como texto XML válido, qualquer que seja o caractere | A1 (padrão) | RQ-02 (implícito de correção, que o próprio `00` manda virar caso de teste) + Riscos do `01` + ADR-02 | EP por classe de caractere, oráculo duplo | CT-05 |
| **R4** — a arte exibe **somente o nome**: a segunda linha não existe | A1 (padrão) | RQ-03 | restrição negativa com contagem de elementos | CT-06, CT-07 |
| **R5** — a arte enviada pelas Settings continua vencendo a padrão | A2 (padrão) | RQ-06 | tabela de decisão (3 partições) + rastreio de efeito | CT-08 |
| **R6** — toda tela de autenticação dos três painéis serve a arte com o nome | A3 (padrão) | RQ-01 na superfície | matriz superfície × painel | CT-09, CT-10, CT-B01 |
| **R7** — os dois READMEs descrevem a arte que passa a existir | A4 (mínimo) | RQ-04 | presença no texto cru + ausência com filtro de citação | CT-11 |

**RQ-05 (capturas) não gerou regra automatizável.** Ver `## Lacunas Declaradas`.

## Fronteira com o Plano

| Item que veio do `01`/`02` | Recusado como oráculo porque | Destino |
|---|---|---|
| `resources/views/svg/arte-do-login.blade.php` | escolha de implementação: o requisito não fala em view Blade | detalhe do cenário, nunca `Então` |
| "o `{{ }}` do Blade faz o escape" | mecanismo: o requisito exige que o **documento continue válido e o nome íntegro**, não que o escape venha do Blade | o `Então` de CT-05 afirma sobre o documento, não sobre quem escapou |
| "`ARTE_PADRAO` deixa de existir" / "o arquivo público é removido" | escolha de implementação | nenhum cenário afirma sobre a constante |
| **"11 pontos"** | contagem do plano, e ela **não fecha**: são **10** chamadas literais (3 no `/admin`, 4 no `/app` — login, registro, recuperação, verificação —, 3 no `/infra`), mais **duas** superfícies **indiretas** sem `->media()` próprio (o desafio de 2FA, pela chave `password-reset`, e a tela de bloqueio, pela chave `login`) | nenhum cenário afirma sobre um número. CT-09/CT-10 enumeram **rotas**, derivadas do `route:list`, não da contagem do plano |
| `data:image/svg+xml;base64,` (ADR-01) | **usado, com ressalva**: o oráculo de requisito é *"o valor é utilizável como `src` de `<img>` e o conteúdo é um SVG válido com o nome"*. O prefixo literal é o **mecanismo de decodificação** do teste, e é afirmado como oráculo só em CT-02 e CT-B01, sob a autoridade da ADR-01 | CT-02, CT-B01; ferramenta de leitura nos demais |
| ADR-02 ("nome longo transborda e não se resolve") | é **decisão**, não comportamento a testar: um `Então` sobre o corte afirmaria sobre o `viewBox` | vira a linha "nome longo" de CT-05, que fixa a decisão **pelo avesso**: o nome sai inteiro e sem quebra, o que reprova o conserto não-autorizado (truncar em PHP, reticências, `tspan`). O transbordo visual é lacuna declarada |
| `assertSee('images/auth/login.svg')` como retrato do estado atual | **inverte-se em oráculo legítimo**: a arte antiga **é** o "texto fixo" que RQ-01 manda sair, e o caminho dela ainda aparecer no HTML prova que a tela continua servindo o texto fixo | `assertDontSee` em CT-09 e CT-10 |

### A armadilha que decide este conjunto inteiro

Os dez pontos de consumo já passam o nome no `alt`:

```php
->media(IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))
```

Ou seja: **`$this->get('/admin/login')->assertSee(config('app.name'))` já passa hoje**, antes de uma
linha de código ser escrita. É o falso ✅ perfeito — o nome *está* no HTML, e não está na arte.

O simétrico é igualmente venenoso: **`assertDontSee('Laravel 13 · Filament 5 · pronto para uso')`
também já passa hoje**, e continuaria passando com a segunda linha intacta, porque a partir da ADR-01
o SVG chega **em base64** e nenhum texto dele aparece cru no HTML.

Daí a regra que atravessa todo o conjunto:

> **Nenhum cenário deste arquivo afirma sobre o nome, nem sobre a ausência do texto antigo, lendo o
> HTML direto.** O oráculo é sempre: extrair o `src` da mídia de autenticação, **decodificar** o
> payload e afirmar sobre o **documento** — que ele parseia, quantos elementos de texto tem, e qual é
> o texto deles.

## Setup Global

### Personas

- **anônima** — as seis telas de login e de recuperação de senha são públicas, e a ausência de sessão
  é parte do que elas provam
- `usuarioDoKit('master_global')` com `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`
  — só onde a rota exige sessão (`/app/email-verification/prompt`)

### Fixtures

- `Convite` com token válido — para `/app/register?token=…`, no mesmo molde de
  `tests/Kit/ConviteTest.php:360-372`
- nenhuma factory nova

### Fakes

- `Storage::fake('public')` no `beforeEach` — é o que `tests/Kit/IdentidadeDoKitTest.php` já faz
- `espiarConfiguracoes()` (helper existente em `tests/Pest.php`) para o `warning` de CT-08
- sem `Mail`, `Queue`, `Notification` ou `Http`: a feature não tem efeito colateral externo

### Helper de leitura da arte

Os cenários precisam de duas operações repetidas: **extrair** o `src` da mídia de autenticação de um
HTML, e **decodificar** o data URI para o SVG.

- enquanto **um** arquivo de teste as usar, elas ficam nele;
- no instante em que um **segundo** arquivo as usar — e CT-11 e CT-B01 moram em arquivos diferentes —,
  elas **vão para `tests/Pest.php`**. `.ai/rules/testes.md` é explícita, e
  `tests/Kit/HelpersDeTesteTest.php` reprova quem não seguir. **Nunca um clone com outro nome.**

### Estratégia de DB

`RefreshDatabase` global, ligado no `tests/Pest.php` para `Kit`. Suíte **`Kit`** (single-tenant): a
premissa do `00` fixa o nome em `config('app.name')`, e não no nome da organização, então **nenhum CT
deste conjunto vai para `tests/Tenancy`**. A regressão de tenancy é a suíte existente
(`tests/BrowserTenancy/IdentidadeVisualTest.php`: a logo da organização vencendo na tela de bloqueio).

### Onde termina o contrato desta feature

RQ-02 diz "customizável na instalação", e a perna do `kit:install` → `.env` → `APP_NAME` **não** ganhou
cenário aqui de propósito: ela já é coberta, e com fronteiras, por
`tests/Kit/CustomizadorDaInstalacaoTest.php` (`valorNoEnv('APP_NAME')`, nome com quebra de linha,
reescrita sem duplicar a chave). O contrato desta feature começa **depois** disso, em
`config('app.name')` — que é exatamente onde CT-03 pega. Registrado porque a alternativa silenciosa
seria a meia-cláusula ficar sem cenário **e** sem justificativa, que é a omissão que a rastreabilidade
existe para expor.

### Uma advertência de ambiente

`APP_NAME` **não** está fixado no `phpunit.xml` — ao contrário de todas as chaves `KIT_*`. O valor
efetivo vem do `.env` da máquina (hoje `"Starter Kit"`). Por isso:

> **Nenhum cenário lê o nome do ambiente como valor esperado.** Todo cenário **define**
> `config(['app.name' => …])` com um literal próprio e, onde couber, afirma que o nome **do ambiente**
> não aparece — o mesmo par discriminante que a feature ancestral usa em `IdentidadeDoKitTest.php`
> (`$doAmbiente` + `assertDontSee`). Sem isso, o cenário passa medindo o `.env` de quem rodou.

---

## Regra R1 — a arte padrão carrega o nome da aplicação

> `RQ-01` · área A1, perfil **padrão** · técnica: **EP + oráculo por decodificação**

```gherkin
# language: pt

Funcionalidade: A arte das telas de autenticação mostra o nome da aplicação

  Regra: a arte padrão carrega o nome da aplicação, e não mais um texto fixo

    Cenário: [CT-01] o nome configurado está dentro da arte, e o texto fixo do kit não está
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "Prefeitura de Itabira"
      Quando a instalação resolve a arte das telas de autenticação
      Então o conteúdo da arte contém o texto "Prefeitura de Itabira"
      E o conteúdo da arte não contém o texto "starter-kit-easy"

    Cenário: [CT-02] a arte é entregue como imagem utilizável, e o documento é válido
      Dado uma instalação sem arte enviada nas configurações
      Quando a instalação resolve a arte das telas de autenticação
      Então o valor começa por "data:image/svg+xml;base64," e o restante é base64 puro
      E o conteúdo decodificado é um documento XML válido cujo elemento raiz é "svg"
```

**Notas de execução**

- CT-01 — o literal `"starter-kit-easy"` é o texto fixo que RQ-01 manda sair, e ele **não depende do
  ambiente**: é o conteúdo do SVG de hoje. É a metade discriminante do cenário.
- CT-02 — o `Então` do envelope é o que distingue "a string tem o nome dentro" de "a tela tem uma
  imagem". Três implementações plausíveis produzem uma string **com o nome dentro** e uma tela **sem
  arte**: data URI cru (o `<` é escapado no atributo e o `#` do gradiente trunca o `MediaDetector`),
  markup solto sem o prefixo `data:`, e `asset()` de um arquivo que já não existe.
- Camada: `tests/Kit` — a mais barata que o arnês sustenta (`tests/Unit` roda sem container).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a view é cópia fiel do SVG e o `<text>` de 44px continua com o literal `starter-kit-easy` | **CT-01** |
| M2 | devolve `data:image/svg+xml,<svg …>` cru, sem base64, "porque é mais legível" | **CT-02** (o restante não é base64), reforçado por CT-B01 |
| M3 | devolve o markup do SVG direto, sem o prefixo `data:` | **CT-02** |
| M4 | mantém `asset(self::ARTE_PADRAO)` depois de o arquivo ser removido — a assinatura não muda e nada estoura | **CT-02**, e CT-09 pelo `assertDontSee` do caminho antigo |
| M5 | o payload é base64 de um documento truncado (concatenação de string no lugar de render de view) | **CT-02** (o parse do documento) |

---

## Regra R2 — o nome é lido em tempo de execução

> `RQ-02` · área A1, perfil **padrão** · técnica: **sequência de duas leituras no mesmo processo**

```gherkin
# language: pt

  Regra: o nome é lido a cada render, e não congelado na instalação nem no boot

    Cenário: [CT-03] trocar o nome da aplicação muda a arte na leitura seguinte
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "Antes da Troca"
      E que a instalação já resolveu a arte uma vez
      Quando o nome da aplicação passa a ser "Depois da Troca"
      Então a arte da primeira leitura continha "Antes da Troca"
      E o conteúdo da arte resolvida agora contém "Depois da Troca"
      E o conteúdo da arte resolvida agora não contém "Antes da Troca"
```

**Notas de execução**

- **A primeira leitura é asserção, e por isso ela está no `Então`, não no `Dado`.** Sem afirmar o
  conteúdo dela, o cenário não distingue "releu" de "nunca leu o primeiro valor" — e uma asserção
  escondida num passo `Dado` é precisamente o que torna um cenário inauditável. Um único `Quando`
  (a troca do nome), duas leituras afirmadas.
- Duas leituras **no mesmo processo** é o ponto: é assim que memoização estática morre. Congelamento
  no `boot()` do provider morre em CT-09, que define o nome antes do `GET`.
- `config()->set()` e não `putenv()`: um mutante que leia `env('APP_NAME')` sobrevive ao
  `config()->set()` — e é exatamente por isso que ele morre aqui, na segunda leitura, que não muda.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | memoização estática no método (`static ?string $arte = null`), "para não re-encodar a cada render" — a própria ADR-01 menciona o custo do `base64_encode` | **CT-03** |
| M7 | o nome vem de `env('APP_NAME')` em vez de `config('app.name')` | **CT-03** (a segunda leitura não muda) |
| M8 | a arte é montada uma vez no `boot()` do PanelProvider e passada como string constante aos `->media()` | **CT-09** (o nome do ambiente apareceria no lugar do nome definido pelo cenário) |

---

## Regra R3 — o nome entra como texto XML válido, qualquer que seja o caractere

> `RQ-02` (requisito implícito de correção, declarado no `00`) + Riscos do `01` + ADR-02 · área A1,
> perfil **padrão**, **técnica escalada** para EP por classe de caractere com oráculo duplo ·
> justificativa da escala: sem escape o documento inteiro é inválido, e o modo de falhar é silencioso
> no teste e total na tela

```gherkin
# language: pt

  Regra: o nome vira texto do documento sem quebrá-lo e sem ser mutilado

    Esquema do Cenário: [CT-05] o nome atravessa a arte íntegro, seja qual for o caractere
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "<nome>"
      Quando a instalação resolve a arte das telas de autenticação
      Então o conteúdo decodificado é um documento XML válido
      E o documento tem exatamente um elemento de texto
      E o texto de todos os nós de texto do documento, concatenado, é exatamente "<nome>"
      E esse texto tem o mesmo comprimento do nome

      Exemplos:
        | nome                                                                                     | # classe de caractere       |
        | Prefeitura de Itabira                                                                    | comum (linha de referência) |
        | Silva & Cia                                                                              | E comercial                 |
        | Obras <Municipais> e Urbanismo                                                            | menor e maior               |
        | Colégio "Dom Pedro" d'Alcântara                                                           | aspas e apóstrofo           |
        | Instituição de Educação e Inovação 🏫                                                     | acento e emoji (4 bytes)    |
        | X                                                                                        | 1 caractere (borda inferior)|
        | Secretaria Municipal de Obras, Urbanismo, Habitação e Desenvolvimento Territorial Sustentável | nome longo (ADR-02)     |
        | (255 caracteres, gerados no cenário)                                                     | borda superior de um `varchar` |
        |                                                                                          | vazio @premissa             |
```

**Notas de execução**

- **O oráculo é duplo de propósito.** "O documento parseia" sozinho não basta: `Obras <Municipais>`
  **sem escape** produz um documento *válido* — o `<Municipais>` vira elemento aninhado — cujo texto
  perde os sinais. "O texto é o nome" sozinho também não basta: um documento inválido nem chega a ter
  texto para comparar, e o caso falharia por uma mensagem que não explica nada. Juntos, os dois
  separam as quatro implementações erradas.
- **A linha do `&` é a que mata o escape duplo.** `{{ e($nome) }}` (ou `htmlspecialchars()` seguido do
  `{{ }}`) produz `&amp;amp;` no documento, que parseia de volta como `&amp;` — texto ≠ nome. Nome
  redondo, sem caractere especial, não distingue nada aqui.
- **A linha "nome longo" fixa a ADR-02 pelo avesso.** A decisão é *não resolver*: uma linha só, sem
  quebra e sem redução de fonte. O `Então` afirma que o nome sai **inteiro**, o que reprova qualquer
  conserto não-autorizado. O transbordo visual em si é lacuna declarada.
- **A linha vazia é `@premissa`.** O requisito não diz o que acontece com `APP_NAME=""`. Premissa
  adotada: a arte sai **válida, com o texto vazio** — não volta para "starter-kit-easy" e não estoura.
  Ver `## Perguntas para o 00-requisito.md`.
- **O oráculo lê o texto de TODOS os nós, e a escolha da API decide se ele funciona.** Em PHP o jeito
  idiomático — `(string) simplexml_load_string($svg)->xpath('//text')[0]` — devolve apenas os nós de
  caractere **diretos** do elemento, e **ignora um `<tspan>` filho**. Uma implementação que mantenha a
  segunda linha como `<tspan>` dentro do único `<text>` passaria pela contagem *e* pela comparação.
  Use `DOMDocument` + `textContent` (ou o texto concatenado de `//text()`), que inclui descendentes.
  A mesma armadilha vale para `<title>`, `<desc>` e `<foreignObject>` — por isso o `Então` fala em
  **todos os nós de texto do documento**, não no elemento.
- **A afirmação de comprimento é o que mata o truncamento com teto alto.** "O nome sai inteiro" com um
  único nome longo não basta: `Str::limit($nome, 100)` sobrevive a qualquer linha com menos de 100
  caracteres. Comparar o comprimento, e ter uma linha de **255**, fecha a fronteira em vez de amostrá-la.
- Uma linha por partição, nunca duas classes no mesmo nome: a primeira falha mascararia as demais.
- O `Esquema do Cenário` conta como **1 cenário**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | sem escape — `{!! $nome !!}` ou concatenação de string, "porque é só um nome" | CT-05, linha do `&` (documento inválido) |
| M10 | escape duplo — `{{ e($nome) }}`, ou `htmlspecialchars()` antes de entregar ao Blade | CT-05, linha do `&` (texto ≠ nome) |
| M11 | sanitização destrutiva — `strip_tags()` ou regex que remove `<`, `>` e `&` "para não quebrar o SVG" | CT-05, linha de menor e maior |
| M12 | o payload perde UTF-8 (re-encode, `htmlentities` com charset default, `utf8_decode`) | CT-05, linha de acento e emoji |
| M13 | truncamento preventivo do nome longo — reticências ou `Str::limit()`, contra a ADR-02 | CT-05, linhas de nome longo e de 255 caracteres (a afirmação de comprimento) |
| M28 — revisão adversarial | a segunda linha vira `<tspan>` dentro do único `<text>`, com outra redação | CT-05 e **CT-06** (texto concatenado de todos os nós) |

---

## Regra R4 — a arte exibe somente o nome

> `RQ-03` · área A1, perfil **padrão** · técnica: **restrição negativa com contagem de elementos**

```gherkin
# language: pt

  Regra: a arte tem uma única linha de texto, e ela é o nome

    Cenário: [CT-06] a segunda linha de texto não existe mais na arte
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "Prefeitura de Itabira"
      Quando a instalação resolve a arte das telas de autenticação
      Então o documento decodificado tem exatamente um elemento de texto
      E o texto de todos os nós de texto do documento, concatenado, é "Prefeitura de Itabira"
      E esse elemento tem posição dentro da área de desenho, tamanho de fonte não nulo e
        preenchimento diferente do fundo
      E o conteúdo decodificado não contém "Laravel 13"

    Cenário: [CT-07] a forma da arte permanece — sai o texto, não o desenho @premissa
      Dado uma instalação sem arte enviada nas configurações
      Quando a instalação resolve a arte das telas de autenticação
      Então o documento decodificado mantém os cinco círculos e os dois gradientes do desenho
      E a área de desenho continua sendo "0 0 800 1000", recortada e não esticada
```

**Notas de execução**

- **A contagem é o oráculo; a ausência da frase é apoio.** `não contém "Laravel 13"` sozinho é o falso
  ✅ descrito na `## Fronteira com o Plano`: em base64 nenhuma frase do SVG aparece crua, e a asserção
  passaria com as duas linhas intactas. Ela fica no cenário **depois da decodificação**, onde tem
  sentido, e quem falsifica é **"exatamente um elemento de texto"** — que também mata o mutante que
  ninguém prevê: a segunda linha passar a repetir o nome.
- CT-07 é `@premissa`: o `00` declara que "sem outro texto" **não** inclui a marca d'água visual
  (gradiente e círculos são forma, não texto). Se a premissa for negada, este cenário inverte — e é
  por isso que ele existe escrito, e não subentendido.
- **A geometria do elemento de texto é oráculo, e não detalhe.** Ao converter o SVG em view é
  perfeitamente possível perder os atributos que sobreviviam no irmão removido — `x`, `y`,
  `font-size`, `fill`. O resultado é um documento válido, com **exatamente um** nó de texto contendo
  **exatamente** o nome, todos os círculos e gradientes no lugar, base64 correto e a imagem pintando:
  o conjunto inteiro fica verde e **o nome não aparece na tela**, porque está fora do `viewBox` ou na
  cor do fundo. É o modo mais barato de RQ-01 não ser entregue com tudo verde.
- **Recusado**: afirmar raio, posição e cor de cada círculo em CT-07. O mutante correspondente
  (`<circle r="0">` sobrevivendo a uma conversão de arquivo para view) não passa no teste de
  plausibilidade — ninguém zera raio ao portar markup —, e a asserção fixaria o desenho a ponto de
  qualquer ajuste visual legítimo virar teste vermelho.
- **A linha da área de desenho fecha o mutante que o desenho sozinho não pega**: quem reescreve o SVG
  como view pode perder o `viewBox="0 0 800 1000"` ou o `preserveAspectRatio="xMidYMid slice"` — a arte
  continua tendo todos os círculos, todos os gradientes e o nome, e sai **esticada** na coluna
  lateral. Círculo virando elipse não move nenhuma outra asserção deste conjunto.
- Os números (cinco círculos, dois gradientes) vêm do desenho de hoje, que o `01` manda preservar. Se
  o desenho mudar por decisão explícita, o cenário muda junto: é o preço de fixar a premissa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | a segunda linha permanece — só o `<text>` de 44px foi tocado | **CT-06** (contagem) |
| M15 | a segunda linha vira o nome também, ou vira `config('app.env')`, "para não deixar buraco" | **CT-06** (contagem) |
| M16 | a segunda linha sai levando o `<g>` do desenho junto, e a arte vira um retângulo com gradiente | **CT-07** |
| M17 | o texto some inteiro (o `<g>` de texto foi removido em bloco) | **CT-06** e CT-01 |
| M27 | ao virar view, o SVG perde o `viewBox` ou o `preserveAspectRatio` — desenho e nome intactos, arte esticada | **CT-07** (a linha da área de desenho) |
| M31 — revisão adversarial | o único `<text>` perde `x`/`y`, `font-size` ou `fill` na conversão — o nome existe no documento e não aparece na tela | **CT-06** (a linha de apresentação) |

---

## Regra R5 — a arte enviada continua vencendo a padrão

> `RQ-06` · área A2, perfil **padrão** · técnica: **tabela de decisão (3 partições)** + rastreio de
> efeito (o `warning` do channel `configuracoes`)

```gherkin
# language: pt

  Regra: a arte enviada nas configurações tem precedência sobre a arte padrão

    Esquema do Cenário: [CT-08] a precedência da arte enviada não muda com o fallback novo
      Dado que a arte do login está <estado_da_configuracao>
      Quando a instalação resolve a arte das telas de autenticação
      Então o valor devolvido é <valor>
      E o aviso no channel "configuracoes" <aviso>

      Exemplos:
        | estado_da_configuracao                    | valor                                                                             | aviso            |
        | enviada, com o arquivo no disco           | a URL pública do arquivo enviado, e não um data URI                               | não foi gravado  |
        | declarada, com o arquivo ausente no disco | a arte padrão com o nome dentro, e não a URL do arquivo declarado                 | foi gravado      |
        | não configurada                           | a arte padrão, com o nome da aplicação dentro                                     | não foi gravado  |
```

**Notas de execução**

- **As duas primeiras linhas precisam da metade negativa, e são metades diferentes.** Na linha
  "enviada": além de `toBe(Storage::disk('public')->url(…))`, o valor **não** começa por `data:` — é
  ela que separa "a precedência existe" de "o fallback passou na frente". Na linha "declarada e
  ausente": além da arte padrão, o valor **não** é a URL do arquivo declarado — sem essa, o cenário
  repete R1 e deixa passar a implementação que devolve uma URL para um arquivo que não existe, que é
  o defeito de 404 no `<head>` que a classe foi criada para impedir.
- A linha "declarada e ausente" é a que justifica a classe `IdentidadeDoKit` existir, segundo a própria
  feature ancestral, e carrega **duas** afirmações: a arte padrão sai **e** o `warning` é gravado. Sem
  o `warning`, uma implementação que troque o `exists()` por `blank()` fica verde no resto do conjunto.
- A linha "não configurada" existe para que a asserção do aviso seja **de ausência** numa partição e
  **de presença** noutra — sem o par, `shouldHaveReceived('warning')` prova pouco.
- Este cenário **substitui** o `it('cai na arte padrao do kit quando nao ha arquivo utilizavel')` da
  suíte ancestral, cujo `toBe(asset(IdentidadeDoKit::ARTE_PADRAO))` deixa de existir. Ver
  `## Colisões com a suíte ancestral`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | o fallback passa a ser incondicional — a arte padrão é gerada e o `doDisco()` vira parâmetro ignorado | CT-08, linha "enviada" |
| M19 | a arte enviada passa a ser embutida como data URI também, "para uniformizar" | CT-08, linha "enviada" |
| M20 | a guarda do arquivo ausente cai junto com a reescrita (sobra só o `blank()`) — a URL quebrada volta | CT-08, linha "declarada e ausente" |

---

## Regra R6 — toda tela de autenticação dos três painéis serve a arte com o nome

> `RQ-01` na superfície · área A3, perfil **padrão** · técnica: **matriz superfície × painel**

**Decisão pedida no enunciado: vale um cenário sobre as telas — o método não basta.** Três motivos,
nesta ordem:

1. **É o defeito histórico documentado desta mesma família.** O `04` da feature ancestral registra,
   com a origem: *"uma implementação em que `IdentidadeDoKit` está perfeita e **nenhum painel a
   consome** passava no conjunto inteiro"* — foi para isso que CT-35/CT-35b nasceram. Aqui o risco é
   **maior**, não menor: a forma do valor muda de URL para data URI, então um painel deixado com o
   literal `asset('images/auth/login.svg')` continua compilando, continua respondendo 200, e serve uma
   imagem para um arquivo que o plano remove.
2. **A configuração é copiada à mão em três providers**, e o `TelasDeAutenticacaoTest` do projeto já
   registra que "o defeito histórico do kit nessa área é configurar um painel e esquecer os outros
   dois". Amostrar um painel é confiar justamente no que já falhou.
3. **A chave também varia dentro do painel** (`login`, `password-reset`, `registration`,
   `email-verification`): quem trocar só a do login deixa metade das telas com a arte velha.

**Mas não são dez cenários.** Duas `Esquema do Cenário` cobrem as oito rotas alcançáveis — 1 cenário
cada, pela regra de contagem do perfil. Os dois pontos restantes (`email-verification` do `/admin` e do
`/infra`) **não têm rota registrada**, e isso já é medido pela suíte existente
(`tests/Kit/TelasDeAutenticacaoTest.php`, `Route::has(...)->toBeFalse()`); cobri-los aqui seria afirmar
sobre uma URL que não existe — que foi exatamente o defeito DT-07 do próprio kit.

```gherkin
# language: pt

  Regra: as telas de autenticação dos três painéis servem a arte com o nome da aplicação

    Esquema do Cenário: [CT-09] cada tela pública de autenticação serve a arte com o nome
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "Prefeitura de Itabira"
      Quando alguém sem sessão abre "<rota>"
      Então a mídia de autenticação da página é um elemento de imagem
      E a arte servida nela contém o texto "Prefeitura de Itabira"
      E a página não cita o caminho "images/auth/login.svg"
      E a página não contém o nome que o ambiente de teste traz

      Exemplos:
        | rota                          |
        | /admin/login                  |
        | /app/login                    |
        | /infra/login                  |
        | /admin/password-reset/request |
        | /app/password-reset/request   |
        | /infra/password-reset/request |

    Esquema do Cenário: [CT-10] as telas de autenticação com pré-condição servem a mesma arte
      Dado uma instalação sem arte enviada nas configurações
      E que o nome da aplicação é "Prefeitura de Itabira"
      Quando quem cumpre a pré-condição "<pre_condicao>" abre "<rota>"
      Então a mídia de autenticação da página é um elemento de imagem
      E a arte servida nela contém o texto "Prefeitura de Itabira"
      E a página não cita o caminho "images/auth/login.svg"
      E a página não contém o nome que o ambiente de teste traz

      Exemplos:
        | rota                           | pre_condicao                            | # chave herdada  |
        | /app/register?token={token}     | um convite válido na query string       | registration     |
        | /app/email-verification/prompt  | sessão de usuário sem e-mail verificado | email-verification |
        | /admin/two-factor-authentication | sessão de qualquer usuário             | password-reset (herdada) |
        | /admin/screen/lock              | sessão com o bloqueio acionado          | login (herdada)  |
```

**Notas de execução**

- **"a arte servida na tela contém o texto"** é o passo carregado: significa *extrair o `src` da mídia
  de autenticação da resposta, decodificar e procurar no documento*. Nunca `assertSee($nome)` na
  resposta — o `alt` já traz o nome, e o cenário passaria antes de a feature existir.
- **"não contém o nome que o ambiente de teste traz"** é a metade que mata o congelamento no boot (M8),
  no mesmo molde do `assertDontSee($doAmbiente)` da suíte ancestral.
- `assertDontSee('images/auth/login.svg', escape: false)` é a metade que mata o painel esquecido.
- Camada: `tests/Kit`, resposta HTTP. Não é browser: o SVG chega **dentro** do documento, e o que se
  afirma é o conteúdo do documento. Ver `## Gate do CT-B`.
- **Arranjo das duas pré-condições de CT-10**, para o caso não morrer no `Dado`:
  - `/app/register` — convite com token válido na query string, molde de `tests/Kit/ConviteTest.php:360-372`;
  - `/app/email-verification/prompt` — `config(['kit.registro.verificar_email' => true])`, um usuário
    `panel_user` **sem** `email_verified_at`, e chegar à tela por `followingRedirects()->get('/app')`,
    molde de `tests/Kit/VerificacaoDeEmailTest.php:377-384`. As duas linhas de arranjo são inline: os
    helpers `exigenciaDeEmail()`/`usuarioSemEmailValidado()` são **locais** daquele arquivo, e usá-los
    de outro exigiria movê-los para `tests/Pest.php` (`.ai/rules/testes.md`) sem ganho nenhum;
  - `/admin/two-factor-authentication` — basta `$this->actingAs(usuario())`, sem arranjo de 2FA: a rota
    do Breezy roda sob o middleware do painel e qualquer autenticado a renderiza, como
    `tests/Kit/TelasDeAutenticacaoTest.php` documenta e usa;
  - `/admin/screen/lock` — `$this->post(route('lockscreen.admin.lock-session'))` antes do `GET`, molde
    de `tests/Browser/IdentidadeVisualPadraoTest.php`.

- **Por que as duas últimas linhas existem — e por que elas não estavam aqui na primeira derivação.**
  A tela de bloqueio e o desafio de 2FA **não têm `->media()` próprio**: cada uma declara uma chave
  (`login` e `password-reset`, respectivamente) e herda a configuração da tela correspondente. Isso as
  torna consumidoras do valor sem aparecer em nenhuma das dez chamadas — e uma matriz fechada por
  *rota de autenticação registrada* as deixa de fora, com a arte quebrada e o conjunto verde. É o
  achado 2 da revisão adversarial, e ele corrigiu uma **cobertura declarada e não entregue**: a linha
  **I** da varredura SFDIPOT já as citava apontando para cenários que não as tocavam.
  Um painel só por linha: o que varia aqui é a **chave herdada**, não o painel, e a variação por
  painel já é exercida nas seis linhas de CT-09.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | um dos três painéis fica com o literal `asset('images/auth/login.svg')` | **CT-09** (a linha daquele painel, pelo `assertDontSee`) |
| M22 | só a chave `login` recebe a arte nova; `password-reset`, `registration` e `email-verification` ficam com a velha | **CT-09** (linhas de recuperação) e **CT-10** |
| M23 | a arte é montada uma vez no boot do provider (M8 visto da superfície) | **CT-09** (o nome do ambiente apareceria) |
| M29 — revisão adversarial | as telas que herdam a chave de outra (bloqueio, 2FA) ficam com o literal antigo, e o arquivo que elas apontam não existe mais | **CT-10**, linhas de `screen/lock` e `two-factor-authentication` |
| M30 — revisão adversarial | o data URI deixa de ser reconhecido como imagem e a mídia é renderizada no ramo de vídeo do pacote — `<source src>` no lugar de `<img src>` | **CT-09** e **CT-10** ("a mídia é um elemento de imagem"), e **CT-B01** |

---

## Regra R7 — os dois READMEs descrevem a arte que passa a existir

> `RQ-04` · área A4, perfil **mínimo** · técnica: **presença no texto cru + ausência com filtro de citação**

```gherkin
# language: pt

  Regra: os dois READMEs descrevem a arte como ela passa a ser

    Esquema do Cenário: [CT-11] cada README diz que a arte usa o nome, e como trocá-la
      Dado o arquivo "<readme>"
      Quando alguém procura como é a arte das telas de autenticação
      Então o texto contém "<origem_do_nome>"
      E o texto contém "<frase_da_arte>"
      E o texto indica "/admin/configuracoes-do-kit" como o lugar de enviar a própria arte
      E o texto, fora das linhas de citação, não contém "public/images/auth/login.svg"

      Exemplos:
        | readme       | origem_do_nome | frase_da_arte                       |
        | README.md    | APP_NAME       | mostra o nome da aplicação          |
        | README.en.md | APP_NAME       | shows the application name          |
```

**Notas de execução**

- **A asserção de ausência precisa do filtro de citação**, por `.ai/rules/testes.md`: o README pode
  perfeitamente **citar** o caminho antigo num bloco `>` explicando o que mudou, e citar não é
  instruir. O helper `readmeSemCitacao()` já existe em
  `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` — se este cenário morar em outro arquivo, o helper
  **muda para `tests/Pest.php`**; nunca um clone com outro nome.
- A asserção de **presença** roda sobre o texto cru — é a convenção já estabelecida no kit.
- Hoje o caminho aparece **três vezes por README** (linhas 128, 226/227 e a tabela ~1836/1845), sempre
  como instrução ao leitor: "troque a arte em `public/images/auth/login.svg`". Depois da feature esse
  arquivo não existe, e a instrução vira mentira — é isso, e não o "atualizar o README" genérico, que o
  cenário falsifica.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | só o `README.md` é atualizado; o `README.en.md` fica para trás — o padrão histórico do kit | **CT-11** (linha do inglês) |
| M25 | a frase nova entra e a instrução velha **permanece**, e o README passa a dizer as duas coisas | **CT-11** (a asserção de ausência) |

---

## Gate do CT-B — **contestado em um ponto**

> **Nota da implementação (2026-09-01), acrescentada depois da entrega deste arquivo.** O argumento
> abaixo apoia-se numa afirmação do `00-requisito.md` que era **falsa**: a de que a arte aparece
> quebrada nas capturas do `composer art`. Abrindo `art/login.png`, `art/login-social.png` e
> `art/app-bloqueio-social.png`, a arte **pinta corretamente** — o `asset()` é servido pelo navegador
> da suíte. O erro era de quem escreveu o `00`, não de quem derivou este arquivo, e já foi corrigido lá.
>
> **A recomendação foi aceita mesmo assim, e o motivo ficou mais forte.** O CT-B01 não conserta um
> defeito existente: ele **guarda um comportamento que hoje funciona** e que esta feature pode
> quebrar, porque o data URI passa a ser construído por nós. Tudo que este bloco diz sobre o oráculo
> — `naturalWidth`, a existência antes da ausência, os mutantes M2/M26/M30 — continua valendo
> integralmente. Só a moldura ("o defeito que nenhuma suíte pegou") vira "a regressão que nenhuma
> suíte pegaria".

O `01` declara **Sem CT-B**, com o argumento: *"o SVG é gerado no servidor e chega embutido no HTML; o
oráculo é o conteúdo do documento; `assertSee` na resposta HTTP prova tudo, e o navegador só provaria
'a imagem renderizou', que é comportamento do `<img>`, não do kit."*

**Concordo com quase tudo e contesto um ponto.** As três afirmações do gate — o nome está lá, o texto
antigo não está, o XML é válido — são de fato provadas em HTTP, e é onde CT-01…CT-11 vivem. Mas a frase
*"o navegador só provaria que a imagem renderizou"* trata como irrelevante exatamente o defeito que o
**próprio `00` registra como existente hoje**:

> *"nas capturas de tela geradas pelo `composer art`, a arte lateral aparece **quebrada** — o navegador
> de teste mostra o `alt` em vez da imagem"*

Esse defeito viveu na base **com toda a suíte verde**, e a razão é estrutural: para o HTTP, "a imagem
quebrou" e "a imagem pintou" são a **mesma resposta**. Nenhum status se move, nenhuma string muda. E
"a imagem pinta" deixa de ser comportamento genérico do `<img>` quando a fonte passa a ser um data URI
**construído por nós**: mime errado, `;base64` esquecido, payload truncado e escape do atributo
produzem, todos, um `<img>` com `naturalWidth === 0` — e **todos os cenários HTTP deste arquivo
continuam verdes**, porque a string está no documento.

E há um oráculo barato e específico, já disponível no projeto: `assertNoBrokenImages()` do
`pest-plugin-browser`, que avalia `document.images.filter(img => img.complete && img.naturalWidth === 0)`.

### CT-B01 — a arte realmente pinta na tela de autenticação

```gherkin
# language: pt

  Regra: a arte servida é uma imagem que o navegador consegue exibir

    Cenário: [CT-B01] a tela de login abre com a arte pintada, e não com o texto alternativo
      Dado uma instalação sem arte enviada nas configurações
      Quando alguém abre a tela de login num navegador
      Então a mídia de autenticação existe na página e é uma imagem
      E essa imagem foi decodificada pelo navegador, com largura natural maior que zero
      E nenhuma imagem da página está quebrada
      E o endereço da mídia de autenticação começa por "data:image/svg+xml;base64,"
```

**Por que browser e não HTTP**: a asserção é sobre a **decodificação pelo agente de usuário**
(`naturalWidth`), que nenhuma resposta HTTP expressa. É o defeito que o `00` documenta como presente
hoje e que nenhuma suíte pegou.

**Por que a asserção de existência vem antes.** `assertNoBrokenImages()` é um **filtro** sobre
`document.images` — e um conjunto vazio satisfaz "nenhuma imagem quebrada". Se a mídia deixar de ser
uma `<img>` (o pacote tem um ramo de vídeo, escolhido por extensão, e um data URI base64 não tem
extensão), a página fica sem arte e a asserção passa. Por isso o cenário afirma **primeiro que a
mídia existe e é imagem**, e só então que nenhuma quebrou. Asserção de ausência sobre conjunto que
pode estar vazio não é oráculo — é o achado 5 da revisão adversarial.

**Onde ele mora — e por que esta rodada não gerou um `05`**:
`tests/Browser/IdentidadeVisualPadraoTest.php` **já visita** uma tela de autenticação e **já afirma
sobre o `src` da mídia** (`assertAttributeContains('.fi-auth-media', 'src', 'images/auth/login.svg')`,
linha 33). Essa asserção **quebra com esta feature de qualquer jeito** e tem de ser reancorada. CT-B01
é **a reancoragem mais uma linha** — não um arquivo novo, não um cenário de navegador novo, não um
runbook novo. Um `05` para uma linha acrescentada a um cenário existente seria burocracia, e a entrega
desta rodada é apenas o `04`.

> **Recomendação registrada**: se a implementação preferir formalizar, o conteúdo do `05` seria
> exatamente este bloco. A decisão prática — e a que recomendo — é reancorar
> `tests/Browser/IdentidadeVisualPadraoTest.php` e acrescentar `assertNoBrokenImages()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M2 (repetido) | data URI cru, sem base64 — o `<` vira `&lt;` no atributo e o `#` do gradiente trunca o `MediaDetector` | **CT-B01** (imagem quebrada), e CT-02 em HTTP |
| M26 | o mime declarado é `image/svg` ou `text/xml` em vez de `image/svg+xml` — o navegador recusa e mostra o `alt` | **CT-B01** — nenhum cenário HTTP deste arquivo o mata |

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B nas seis telas públicas, um por painel | mata os mesmos mutantes que CT-B01; a variação por painel é provada em HTTP por CT-09, ordens de magnitude mais barato |
| CT-B de tema escuro sobre a arte | `assertSee` não valida tema (`.ai/rules/testes-browser.md`) e o requisito não fala em contraste; sem oráculo barato |
| CT-B do nome longo transbordando | é pixel: o oráculo seria screenshot e olho humano. Vira lacuna declarada da ADR-02 |
| CT de superfície para cada uma das dez chamadas | duas `Esquema do Cenário` cobrem as oito rotas alcançáveis; as duas restantes não têm rota, e afirmar sobre URL inexistente foi o defeito DT-07 |
| CT-04, "a tela servida depois da troca de nome traz o nome novo" | mata exatamente os mesmos mutantes que CT-03 e CT-09 juntos |
| CT sobre o `alt` da imagem | o `alt` já é `config('app.name')` hoje e o requisito não o toca — cenário que não mata mutante nenhum |
| CT em `tests/Tenancy` | o `00` fixa o nome em `config('app.name')`, não no da organização; nenhuma regra nova que a tenancy mude |

---

## Colisões com a suíte ancestral (regressão obrigatória)

A precedência "arte customizada vence a padrão" já é coberta e **não muda** (RQ-06). O que muda é o
**fallback** — e quatro asserções existentes estão ancoradas na forma antiga dele. Todas quebram, e
todas quebram **por mudança deliberada**, não por defeito:

| Arquivo e ponto | Asserção de hoje | O que fazer |
|---|---|---|
| `tests/Kit/IdentidadeDoKitTest.php` — `it('cai na arte padrao do kit quando nao ha arquivo utilizavel')` | `toBe(asset(IdentidadeDoKit::ARTE_PADRAO))` | **substituída** por CT-08, que mantém as mesmas partições de fallback e reancora o valor esperado |
| `tests/Kit/IdentidadeDoKitTest.php` — `it('veste as telas de login com a arte gravada')` | `assertDontSee(IdentidadeDoKit::ARTE_PADRAO)` | reancorar: a metade discriminante passa a ser *o `src` da mídia não é um data URI* |
| `tests/Kit/ConviteTest.php:370` | `assertSee('images/auth/login.svg', escape: false)` na tela de aceite de convite | reancorar — é a mesma superfície de **CT-10**, linha `/app/register` |
| `tests/Browser/IdentidadeVisualPadraoTest.php:33` | `assertAttributeContains('.fi-auth-media', 'src', 'images/auth/login.svg')` | reancorar **+** acrescentar `assertNoBrokenImages()` = **CT-B01** |

Fora de teste, três textos referenciam o arquivo removido e passam a mentir: `README.md` e
`README.en.md` (CT-11 cobre), `config/kit.php:99` e o comentário de `app/Models/Tenant.php:149`
(comentários — sem oráculo, item de checklist da implementação).

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a superfície é pública e anônima por natureza (o `01` registra "Autorização: nenhuma"); não há `{id}` de recurso nem dado de usuário na arte |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: nenhuma autorização é introduzida ou consultada |
| Idempotência (ancorada no agregado) | **não se aplica**: `arteDoLogin()` é leitura pura — não há escrita, logo não há agregado onde ancorar. O único agregado da família (o arquivo enviado pelas Settings) não é tocado |
| Concorrência | **não se aplica**: sem contador, saldo, limite ou estado compartilhado |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: a feature não cria ponto de entrada. A gravação da arte enviada continua coberta por `tests/Kit/UploadLimiteETiposTest.php` (teto de 10 240 bytes e tipos aceitos), que esta feature não altera |
| Domínio condicionado (um campo muda a fronteira do outro) | **não se aplica**: uma entrada só (`config('app.name')`), sem discriminador |
| Estado × operação de escrita | **não se aplica**: não há entidade com ciclo de vida |
| Ausente ≠ `null` ≠ vazio | **CT-05**, linha "vazio" (`@premissa`) — a partição "ausente" está em `## Perguntas para o 00-requisito.md` |
| Paginação / ordenação | **não se aplica**: não há listagem |
| Timezone / DST | **não se aplica**: nenhum dado temporal. A dimensão **T** desta feature é "quando o valor é lido", e quem a cobre é **CT-03** |
| Unicode / limite de campo | **CT-05**, linhas de acento e emoji (4 bytes) e de nome longo |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum payload novo |
| Upload | **não se aplica** nesta feature — coberto por `tests/Kit/UploadLimiteETiposTest.php` |
| Precisão monetária | **não se aplica** |
| Efeito colateral (log) | **CT-08**, linha "declarada e ausente" — o `warning` do channel `configuracoes`, com o par de ausência na linha "não configurada" |
| Asserção de ausência sobre arquivo documentado precisa filtrar citação (`.ai/rules/testes.md`) | **CT-11** |
| Falso ✅ por asserção sobre o HTML em vez do documento decodificado | prevenido por construção — ver `## Fronteira com o Plano` |

## Lacunas Declaradas

| Lacuna | O que foi tentado / por que fica declarada |
|---|---|
| **RQ-05 — as capturas mostram a arte com o nome** | sem oráculo automatizável. `tests/BrowserTenancy/CapturaDeArteTest.php` só roda com `KIT_ART=1` (`composer art`), **escreve** PNG e é pulado no CI por decisão de projeto; e o conteúdo de um PNG só é conferível por olho. O que dá para automatizar é o degrau anterior — a tela capturada servir a arte **pintada** —, e isso é **CT-B01**. A conferência da imagem permanece o passo manual já listado na `## Verificação Final` do `01`. **Guarda que o projeto já tem e que vale cobrar aqui**: se o passo 5 do `01` decidir adotar `art/login.png` no comando, `.ai/rules/testes-browser.md` exige o par — o `->screenshot(filename: 'login')` no cenário **e** a linha `'login'` em `KitArte::IMAGENS`. Sem a segunda, o `kit:arte` reporta a imagem como ignorada e não publica |
| **ADR-02 — nome longo transborda** | o comportamento *declarado como aceito* é visual (corte pelo `viewBox`), e um `Então` sobre ele afirmaria sobre o `viewBox`, isto é, sobre a implementação. Tentado e recusado: cenário de navegador com screenshot — só olho humano decide "cortou demais". O que **é** falsificável está coberto: a linha "nome longo" de CT-05 prova que o nome sai **inteiro**, o que reprova qualquer conserto não-autorizado |
| **M26 — mime errado** | coberto **apenas** por CT-B01. Se a recomendação do gate for recusada e nenhum cenário de navegador existir, M26 fica **sem matador** — é a única lacuna de mutante deste conjunto |

## Revisão Adversarial

Executada por um sub-agente **independente**, que recebeu apenas o `00-requisito.md` e este arquivo —
sem o plano, sem o `02`, sem o código e sem o raciocínio de quem derivou. Contrato: *provar que este
conjunto deixa passar um defeito*. Uma rodada.

**Resultado: 5 implementações erradas sobreviviam ao conjunto inteiro, e 7 oráculos eram fracos.**
Todos fechados. Nenhuma segunda rodada: o fechamento não criou cenário novo — criou **linha** de
`Esquema` e `Então` em cenário existente, e a regra da skill só exige re-revisão quando nasce cenário.

| # | O defeito que sobrevivia | Virou |
|---|---|---|
| D1 | o único `<text>` perde `x`/`y`/`font-size`/`fill` na conversão: documento válido, um nó de texto, texto exatamente igual ao nome, imagem pintando — **e o nome fora do `viewBox` ou na cor do fundo** | linha de apresentação em **CT-06** + mutante **M31** |
| D2 | a tela de bloqueio e o desafio de 2FA ficam com o literal antigo: elas herdam a chave de outra tela e não aparecem nas dez chamadas, então a matriz fechada por "rota de autenticação" as ignora | duas linhas novas em **CT-10** + mutante **M29**, e a correção da linha **I** do SFDIPOT, que declarava cobertura inexistente |
| D3 | `Str::limit($nome, 100)`: a linha "nome longo" tinha 93 caracteres, e qualquer teto acima dela sobrevivia — M13 ficava vivo e a ADR-02 caía sem nada reprovar | linhas de **1** e de **255** caracteres em **CT-05**, e a afirmação de **comprimento igual** |
| D4 | a segunda linha vira `<tspan>` dentro do único `<text>`: a contagem dá 1, e o oráculo idiomático do PHP (`(string) $xml->xpath('//text')[0]`) **ignora o filho** | o `Então` passa a ser o **texto concatenado de todos os nós de texto**, com a API de leitura fixada em nota + mutante **M28** |
| D5 | a mídia deixa de ser `<img>` (o pacote escolhe o ramo por extensão, e data URI não tem extensão): `assertNoBrokenImages()` filtra `document.images` e **conjunto vazio passa** | asserção de **existência antes da ausência** em **CT-B01**, "a mídia é um elemento de imagem" em **CT-09**/**CT-10**, mutante **M30** |

**Oráculos fracos apontados e o que foi feito**

| Apontado | Fechamento |
|---|---|
| CT-11 sem literal — "afirma que mostra o nome" não fixa nada | literais por idioma no `Esquema` (`APP_NAME`, a frase pt e a frase en) |
| CT-B01 afirma sobre conjunto possivelmente vazio | asserção de existência acrescentada (D5) |
| CT-10 sem a metade discriminante do ambiente que CT-09 tem | acrescentada — a assimetria não tinha justificativa |
| CT-08, linha "declarada e ausente", sem a metade negativa | acrescentado "e não a URL do arquivo declarado" |
| CT-05, linha "vazio", não distingue `<text>` vazio de `<text>` ausente | resolvido pelo `Então` novo, que afirma **a contagem** antes do texto |
| CT-03 com asserção escondida no `Dado` | a primeira leitura foi movida para o `Então` |
| CT-07 conta círculos sem afirmar raio/cor: `r="0"` passaria | **recusado, com motivo escrito**: o mutante não passa no teste de plausibilidade, e a asserção congelaria o desenho |
| CT-02 "base64 puro" não prova que é *este* documento | **recusado, com motivo escrito**: o cenário é do **envelope**; o conteúdo é CT-01, CT-05 e CT-06, e duplicar ali seria cenário que não mata mutante novo |
| meia-cláusula de RQ-02 ("customizável na instalação") sem cenário nem justificativa | fechada em `## Onde termina o contrato desta feature`, com a suíte que já cobre a perna do `kit:install` |
| `config/kit.php:99` e `app/Models/Tenant.php:149` passam a mentir, sem oráculo | **fora de RQ-04**, que nomeia só o README. Fica como item da `## Colisões com a suíte ancestral`, e a recomendação ao `01` é varrer o repositório pelo caminho removido antes do commit |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo sugerido | Mata |
|----|---------|-------|---------|--------|------------------|------|
| CT-01 | o nome está na arte; o texto fixo do kit não | R1 | EP | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M1, M17 |
| CT-02 | a arte é imagem utilizável e o documento é válido | R1 | EP | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M2, M3, M4, M5 |
| CT-03 | trocar o nome muda a arte na leitura seguinte | R2 | sequência de duas leituras | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M6, M7 |
| CT-05 | o nome atravessa íntegro, seja qual for o caractere (9 linhas) | R3 | EP por classe de caractere + valor limite de comprimento | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M9, M10, M11, M12, M13, M28 |
| CT-06 | a arte tem um texto só, é o nome, e ele é visível | R4 | restrição negativa por contagem + oráculo de apresentação | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M14, M15, M17, M28, M31 |
| CT-07 | a forma da arte permanece `@premissa` | R4 | premissa fixada | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M16, M27 |
| CT-08 | a precedência da arte enviada não muda (3 linhas) | R5 | tabela de decisão + rastreio de efeito | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M18, M19, M20 |
| CT-09 | as seis telas públicas servem a arte com o nome | R6 | matriz superfície × painel | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M8, M21, M22, M23, M30 |
| CT-10 | as telas com pré-condição e as que herdam a chave servem a mesma arte (4 linhas) | R6 | matriz superfície × chave | Kit | `tests/Kit/IdentidadeDoKitTest.php` | M22, M29, M30 |
| CT-11 | os dois READMEs descrevem a arte que existe (2 linhas) | R7 | presença crua + ausência filtrada | Kit | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` ou arquivo próprio | M24, M25 |
| CT-B01 | a arte realmente pinta na tela de login | R6 | oráculo de renderização | Browser | `tests/Browser/IdentidadeVisualPadraoTest.php` (existente) | M2, M26, M30 |

> Não há CT-04. O buraco na numeração é deliberado: o cenário cogitado foi cortado por redundância, e
> está registrado em `## Cogitado e cortado`. Renumerar tornaria a poda invisível.

## Comandos de Verificação

```bash
# a suíte desta feature (as rules do projeto vencem a skill: browser NUNCA em --parallel)
php artisan test --testsuite=Kit --parallel --compact
composer test:browser

# fechamento do ciclo — o plugin de mutação é dependência DIRETA no composer.json
vendor/bin/pest tests/Kit/IdentidadeDoKitTest.php --mutate --path=app/Support/IdentidadeDoKit.php
```

Lembrete do que o mutation score **não** responde: ele só muta código que existe. RQ-04 e RQ-05 são
omissões possíveis que nenhum mutante expressa — quem responde por elas é a rastreabilidade
`RQ → regra → cenário` acima.

## Perguntas para o 00-requisito.md

> **Desvio declarado**: o enunciado desta rodada proíbe editar o `00`. As perguntas ficam aqui, em
> bloco pronto para colagem em `## Ambiguidades e Perguntas Abertas`. Elas continuam **bloqueando** o
> que delas depende, e os cenários afetados estão marcados `@premissa`.

- **RQ-01/RQ-03 — o que a arte mostra quando `APP_NAME` está vazio (`APP_NAME=""`)?**
  - **Assumido**: a arte sai **válida, com o texto vazio**. Não volta para "starter-kit-easy" (o
    requisito manda o texto fixo sair) e não estoura. Cenário: **CT-05**, linha "vazio".
  - **Se negado**: se a resposta for "volta para o nome do kit" ou "esconde o elemento de texto", a
    linha muda e nasce uma partição nova.
- **RQ-02 — e quando a chave `APP_NAME` está ausente do `.env`, e não vazia?**
  - **Assumido**: `config('app.name')` cai no default do framework (`'Laravel'`) e a arte o exibe, sem
    tratamento especial. Partição **não coberta por cenário**: cobri-la exigiria decidir se "Laravel"
    na tela de login de uma instalação é aceitável, e isso é decisão de produto.
- **RQ-03 — "exatamente um elemento de texto" é a leitura correta de "somente o nome"?**
  - **Assumido**: sim, e é o oráculo de CT-06. A alternativa ("a segunda linha some, mas outro texto
    poderia entrar depois") tornaria a restrição inauditável.
- **RQ-05 — `art/login-anti-robo.png`, citado no `00` como evidência da arte quebrada, não existe em
  `art/`.** Os arquivos com tela de autenticação hoje são `art/login.png`, `art/login-social.png` e
  `art/app-bloqueio-social.png`.
  - **Bloqueia**: a lista de capturas a regerar do passo 5 do `01`. Confirmar qual era a evidência e se
    `art/app-bloqueio-social.png` entra na regeração.
- **`01` — a contagem "11 pontos" não fecha**: são **10** chamadas literais de
  `IdentidadeDoKit::arteDoLogin()`, mais **duas** telas que herdam a arte pela chave de outra, sem
  `->media()` próprio — a de bloqueio (chave `login`) e o desafio de 2FA (chave `password-reset`).
  - **Não bloqueia** nenhum cenário — CT-09/CT-10 enumeram rotas, não pontos de chamada. Registrado
    para o `01` não induzir a próxima pessoa a procurar uma 11ª chamada que não existe.
