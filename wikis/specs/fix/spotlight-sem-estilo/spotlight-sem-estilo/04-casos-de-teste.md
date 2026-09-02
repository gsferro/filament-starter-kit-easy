# Casos de Teste — A busca ⌘K (Spotlight) abre fora da tela

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — a
> correção ainda não existe. O que foi olhado: a blade do **vendor** (é a superfície que o CSS
> precisa cobrir, e é dado, não implementação do kit) e os testes existentes, para herdar
> convenção.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| O overlay na tela (CSS × blade do vendor) | 2 — integra com um componente de terceiro | 2 — feature inteira inutilizável, mas reversível e sem dado | 4 | **padrão** |
| Entrega por `kit:update` | 2 — lista à mão que já falhou duas vezes | 2 — projeto atualizado continua quebrado | 4 | **padrão** |

RQ-03 (o teste que pega o defeito) não é área com cenário: é **gate de execução** sobre CT-B01 —
ver R5.

- Técnicas aplicadas: **EP** sobre as partições da blade (raiz × descendentes × variantes),
  **rastreio de efeito** (o CSS chega / não sobra fora do escopo / chega nos três painéis),
  **medição de geometria** como oráculo de layout, **controle negativo** no detector.
- Cenários: **6** (4 no `04`, 1 `Esquema` no `05` com duas linhas de tema) · Regras: 5 · Mutantes previstos: 17 · Sem matador: 0

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | um arquivo CSS novo, uma linha de registro em `KitServiceProvider`, uma entrada em `CAMINHOS_DO_KIT` e uma em `DIRETORIOS_DE_CODIGO`; o publicado em `public/css/kit/` | CT-01, CT-03, CT-04 |
| **F** | posicionar o overlay do pacote; entregar o CSS a quem atualiza | CT-B01, CT-B02, CT-04 |
| **D** | 66 classes em 3 blades do vendor; o atributo de escopo; variantes `dark:`, `focus:`, opacidade `/70`, arbitrária `[60vh]`, decimal `1.5` | CT-01 (partições), CT-B02 (dark) |
| **I** | a topbar dos três painéis (clique) e o atalho `Ctrl/⌘+K`; o `kit:update` | CT-03 (três painéis), CT-B01 (clique), CT-04 |
| **P** | a CSS pré-compilada do Filament 5 (não tem as classes); Chromium do Playwright; `filament:assets` | CT-03; **declarado**: a versão do Filament não é variável aqui |
| **O** | usuário em qualquer painel, qualquer papel; projeto novo e projeto atualizado | CT-03, CT-04 |
| **T** | **não se aplica**: sem estado, sem concorrência, sem expiração. A única ordem que importa (registro do asset **depois** do `filament-jobs-monitor`) já é regra do `kit.css` e não muda | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — ao abrir, o overlay cobre a viewport, acima do conteúdo, com fundo escurecido, e o campo de busca está dentro da tela | overlay (padrão) | RQ-01 | medição de geometria (layout só o navegador prova) | CT-B01 (linhas `claro` e `escuro`) |
| R2 — toda classe que a blade do vendor emite está declarada no CSS do kit, **sob o escopo**, e o âncora de escopo existe na blade | overlay (padrão) | RQ-01, RQ-02 | EP sobre as partições da blade + controle positivo do detector | CT-01, CT-02 |
| R3 — o CSS é servido nos **três** painéis | overlay (padrão) | RQ-02 | rastreio de efeito por painel | CT-03 |
| R4 — o `kit:update` entrega o CSS do kit — o novo e os que já existiam, fonte e publicado — e nada além | update (padrão) | RQ-04 | EP: dentro / fora da lista, com controle negativo | CT-04 |
| R5 — o teste de RQ-01 fica **vermelho** sem a correção | oráculo (mínimo) | RQ-03 | execução contra o estado anterior (gate, não cenário) | evidência no `03` |

**Técnica escalada**: R1 usa medição via `script()` em vez do `assertVisible` que o precedente
(`HubDeCardsTest`) aceitou. Motivo em uma linha: aqui o defeito é geométrico e `getBoundingClientRect`
o distingue; lá era cor, e não havia oráculo barato.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nome do arquivo `spotlight.css` e do asset `kit-spotlight` | escolha de implementação | detalhe do cenário (CT-03 procura `kit-spotlight.css` porque é o nome que o registro produz; se mudar, o teste muda junto — não é oráculo de comportamento) |
| O seletor de escopo `[x-on\:open-spotlight\.window]` | escolha de implementação (ADR-02) | detalhe do cenário em CT-01/CT-02 — **mas** o atributo em si é contrato do vendor com o gatilho do kit, e isso é dado, não plano |
| `z-index: 50` | é o valor que a **blade do vendor** pede (`z-50`); o kit só o reproduz | oráculo legítimo: vem do dado, não do plano. CT-B01 exige `>= 50` |
| `top === 0`, backdrop com alfa | idem: `inset-0` e `bg-gray-900/70` são da blade | oráculo legítimo |
| "kit.css e cards.css passam a ser entregues" | o requisito pede só a correção do Spotlight; entregar os outros dois é consequência da granularidade (ADR-03) | entra em CT-04 como **efeito**, não como cláusula: se a lista for por diretório, eles vêm junto |
| `kit:update` não roda `filament:assets` | premissa registrada no `00` (RQ-04) | fora dos cenários; o publicado passa a ser entregue (ver pergunta abaixo) |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **RQ-04** — `public/css/kit/*.css` é **versionado** no kit (`git ls-files` mostra `kit-cards.css` e
  `kit-correcoes.css`). Se o `kit:update` entregar só `resources/css/filament`, quem atualiza recebe a
  fonte e precisa rodar `filament:assets` para ter o publicado. Entregar **também** `public/css/kit`
  elimina o passo manual para estes três arquivos. **Premissa adotada**: entregar os dois diretórios
  (CT-04 assere ambos). **Se negado**: CT-04 perde a linha do publicado e o CHANGELOG mantém o
  passo manual.

## Setup Global

### Camada

- `tests/Kit` para CT-01…CT-04 (é onde vivem `KitUpdateTest` e `BoasVindasTest`, os precedentes; e
  `tests/Unit` não tem `TestCase` ligado em `tests/Pest.php`, então `base_path()` não resolve lá).
- `tests/Browser` para CT-B01/CT-B02 — `RoteiroDoKitTest.php`, onde o F-45 já mora.

### Personas

- `usuarioDoKit('master_global')` — entra nos três painéis; é o que o F-45 usa. Os CT de browser
  semeiam `ShieldPermissionsSeeder` + `PapeisSeeder` no `beforeEach`, como o arquivo já faz.

### Fixtures

- Nenhuma factory. Os dados desta feature são **arquivos**: as três blades do vendor, o CSS do kit,
  o `KitUpdate.php`.

### Fakes

- Nenhum. Sem e-mail, fila, HTTP ou evento.

### Sentinela

- CT-01, CT-02 e CT-04 leem `vendor/` e o código do kit; existem em qualquer instalação. **Sem
  guarda** — diferente da wiki do site, nada aqui é `export-ignore`.

---

## Regra R1 — ao abrir, o overlay cobre a viewport, acima do conteúdo, com fundo escurecido, e o campo de busca está dentro da tela

> `RQ-01` · perfil **padrão** · técnica: **medição de geometria** — ver `05-casos-de-teste-browser.md`

O cenário desta regra é CT-B01, um `Esquema` com as linhas `claro` e `escuro`. Está no `05` porque
afirma sobre **layout calculado pelo navegador** — o HTML é byte a byte idêntico com e sem a
correção, e nenhum teste de componente distingue os dois.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | O CSS declara `position: fixed` e esquece `inset-0` (`top/right/bottom/left: 0`) — é **exatamente o estado atual medido**: `fixed` sem ancoragem fica no fluxo, a 1.833 px | CT-B01 (`top === 0`) |
| M2 | `z-index` ausente ou menor que o da topbar do Filament — o overlay abre **atrás** do cabeçalho | CT-B01 (`z-index >= 50`) |
| M3 | O backdrop sem cor de fundo (`bg-gray-900/70` esquecida ou com alfa 0) — a caixa aparece solta sobre a página, sem escurecer nada | CT-B01 (alfa do `background-color` > 0) |
| M4 | As variantes `dark:` não são escritas, ou são escritas com `@media (prefers-color-scheme)` em vez de `.dark` — a caixa fica branca no tema escuro | CT-B01, linha `escuro` (fundo da caixa **não** branco sob `.dark`) |
| M5 | O seletor de escopo é escrito sem escape (`[x-on:open-spotlight.window]`) e **nunca casa** — CSS válido, publicado, e zero efeito | CT-B01 (toda a geometria continua errada) — e CT-01, que procura o escopo **escapado** |

---

## Regra R2 — toda classe que a blade do vendor emite está declarada no CSS do kit, sob o escopo, e o âncora de escopo existe na blade

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **EP** sobre as partições da blade + **controle
> positivo** do detector

Esta é a regra que **envelhece com o pacote**: um upgrade que acrescente uma classe produz HTML
correto sem estilo, em silêncio. O cenário lê a blade do vendor **em runtime** — não uma lista
congelada —, então é ele que fica vermelho no `composer update`, antes de alguém abrir a tela.

As partições da blade, e por que cada uma é uma classe de equivalência (uma implementação pode
acertar uma e errar outra):

| Partição | Exemplo | Como o CSS a expressa | O que erra sozinho |
|---|---|---|---|
| classe da **raiz** (o próprio overlay) | `fixed`, `inset-0`, `z-50` | seletor **composto**: `[escopo].fixed` | escrever com espaço (`[escopo] .fixed`) não casa a raiz |
| classe de **descendente** | `rounded-xl`, `px-4` | `[escopo] .rounded-xl` | — |
| variante **`dark:`** | `dark:bg-gray-900` | `.dark [escopo] .dark\:bg-gray-900` | esquecer o escape do `:` |
| variante **`focus:`** | `focus:ring-0` | `[escopo] .focus\:ring-0:focus` | esquecer a pseudo-classe |
| **opacidade** com barra | `bg-gray-900/70`, `ring-black/10` | `.bg-gray-900\/70` | esquecer o escape da `/` |
| valor **arbitrário** | `max-h-[60vh]` | `.max-h-\[60vh\]` | escape dos colchetes |
| **decimal** | `px-1.5` | `.px-1\.5` | escape do `.` |

```gherkin
# language: pt

Funcionalidade: O overlay do Spotlight recebe estilo do kit

  Regra: toda classe emitida pela blade do vendor está declarada no CSS do kit, sob o escopo

    Cenário: [CT-01] nenhuma classe da blade do vendor fica sem declaração no CSS do kit
      Dado as três blades do pacote em vendor/wezlo/filament-search-spotlight/resources/views
      E o arquivo resources/css/filament/spotlight.css
      Quando o mantenedor extrai cada classe dos atributos class="…" das blades
      Então a lista de classes extraídas tem ao menos 60 entradas
      E toda classe extraída aparece no CSS precedida do seletor de escopo escapado

    Cenário: [CT-02] o âncora de escopo é contrato dos dois lados
      Dado a blade do overlay do pacote
      E a blade do gatilho do kit em resources/views/filament/spotlight-trigger.blade.php
      Quando o mantenedor lê as duas
      Então a blade do pacote contém o atributo x-on:open-spotlight.window
      E a blade do kit dispara o evento open-spotlight
      E o CSS do kit não contém nenhum seletor fora do escopo
```

> **O piso de 60 em CT-01 é controle positivo do detector.** Um regex de extração com erro devolve
> lista vazia, e "toda classe está declarada" sobre conjunto vazio é verdadeiro. Medido hoje: 66.
> O piso fica abaixo para o teste não reprovar quando o pacote **remover** classes — é
> acrescentar que quebra o kit, não tirar.
>
> **"Nenhum seletor fora do escopo" em CT-02** é a metade que protege os outros plugins: um
> `.flex { display: flex }` global no arquivo passaria por CT-01 e mudaria toda blade de vendor
> que emite `flex` sem estilo. O oráculo é: toda regra do arquivo (fora de comentário e de
> `@media`) começa com `.dark [escopo]` ou `[escopo]`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | Uma das 66 classes fica de fora — tipicamente as das partials (`result.blade.php`, `empty-state.blade.php`), que ninguém abre | CT-01 |
| M7 | As classes da raiz são escritas com espaço (`[escopo] .fixed`) — descendentes ok, o overlay em si sem estilo | CT-B01 (`top`, `zIndex`, `fundo` reprovam; `caixaFundo` passa — é o que o separa de M5). CT-01 **não** distingue: a classe existe, só o seletor está errado — e distinguir raiz de descendente no parser do teste custaria mais que o que prova (auditoria Ponytail) |
| M8 | Escape esquecido numa partição (`dark:`, `/`, `[`, `.`): a regra existe, mas com outro nome | CT-01 (procura a classe **escapada** após o escopo) |
| M9 | Uma regra global escapa do escopo | CT-02 (terceiro `Então`) |
| M10 | O detector de classes tem erro e extrai zero | CT-01 (piso de 60) |
| M11 | O pacote renomeia o evento num upgrade; o gatilho do kit continua disparando `open-spotlight` para ninguém — **e o CSS perde o escopo junto** | CT-02 (primeiro `Então`) — e o F-45 atual já morreria nesse caso |

---

## Regra R3 — o CSS é servido nos três painéis

> `RQ-02` · perfil **padrão** · técnica: **rastreio de efeito por painel**

`FilamentAsset::register()` sem `package` escopado por painel registra para todos — é assim que
`kit.css` funciona. Mas a regra é do requisito ("em nenhuma das instalações", "funcionando" — nos
painéis onde a busca existe), então ela se afirma por painel, e não se infere do mecanismo.

```gherkin
# language: pt

  Regra: o CSS do Spotlight é servido em todo painel que tem a busca

    Esquema do Cenário: [CT-03] cada painel carrega a folha do Spotlight
      Dado um usuário master_global autenticado
      Quando ele abre o painel "<painel>"
      Então a resposta é 200
      E ela referencia a folha kit-spotlight.css
      E ela referencia kit-cards.css e kit-correcoes.css, como antes

      Exemplos:
        | painel  |
        | /admin  |
        | /app    |
        | /infra  |
```

> O terceiro `Então` é regressão: `BoasVindasTest` CT-05 já assere `kit-cards.css`; registrar o
> asset novo não pode derrubar os dois anteriores (por exemplo, substituindo o array em vez de
> acrescentar). E a asserção é sobre a **referência** no HTML, não sobre o arquivo em `public/`:
> a publicação é passo do `filament:assets`, conferida na Verificação Final.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | O `Css::make` é registrado dentro de um `Panel::assets()` de um painel só | CT-03 (linhas `/app` e `/infra`) |
| M13 | O array de `FilamentAsset::register` é **substituído** e `kit-cards`/`kit-correcoes` somem | CT-03 (terceiro `Então`) |

---

## Regra R4 — o `kit:update` entrega o CSS do kit, fonte e publicado, e nada além

> `RQ-04` · perfil **padrão** · técnica: **EP** dentro/fora da lista + **controle negativo**

```gherkin
# language: pt

  Regra: o CSS do kit está na lista de caminhos que o kit:update entrega

    Esquema do Cenário: [CT-04] o que é do kit está coberto, o que é do usuário não
      Dado a lista KitUpdate::CAMINHOS_DO_KIT
      Quando o mantenedor consulta a cobertura de "<arquivo>"
      Então o resultado é "<coberto>"

      Exemplos:
        | arquivo                                            | coberto | # partição                        |
        | resources/css/filament/spotlight.css               | sim     | o arquivo desta correção          |
        | resources/css/filament/cards.css                   | sim     | já existia, nunca foi entregue    |
        | resources/css/filament/kit.css                     | sim     | idem                              |
        | public/css/kit/kit-spotlight.css                   | sim     | o publicado, versionado no kit    |
        | resources/css/app.css                              | não     | controle negativo: skeleton       |
        | resources/css/vendor/filament-onboarding/onboarding.css | não | controle negativo: publicado por pacote |
```

> A varredura de `KitUpdateTest` ("cobre todo o código do kit") passa a incluir
> `resources/css/filament` em `DIRETORIOS_DE_CODIGO`. Ela não é cenário desta wiki — já existe —,
> mas é o que impede o **próximo** CSS de nascer fora da lista. CT-04 vive no mesmo arquivo e usa
> o `estaCoberto()` que já está lá.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | Só `resources/css/filament/spotlight.css` entra na lista, arquivo a arquivo — a granularidade que o comentário de `app/Filament` já condenou | CT-04 (linhas `cards.css`, `kit.css`) |
| M15 | `resources/css` inteiro entra — e `app.css` do usuário é sobrescrito no update | CT-04 (controle negativo `app.css`) |
| M16 | Só a fonte é entregue; o publicado fica dependendo de `filament:assets` | CT-04 (linha `public/css/kit`) — **sob a premissa** registrada em `## Fronteira com o Plano` |

---

## Regra R5 — o teste de RQ-01 fica vermelho sem a correção

> `RQ-03` · **gate de execução**, não cenário

Não há cenário Gherkin que prove que outro cenário é falsificável: isso se prova **executando**.
O procedimento, com evidência obrigatória no `03-progresso.md`:

1. Escrever CT-B01 (o F-45 reescrito) **antes** do CSS existir.
2. Rodar `vendor/bin/pest --no-tia tests/Browser/RoteiroDoKitTest.php --filter=F-45` → tem de
   ficar **vermelho**, e a mensagem tem de ser a da geometria (`top` ≠ 0, ou alfa 0, ou
   `z-index` `auto`), não outra coisa. Colar a linha da falha no `03`.
3. Aplicar a correção. Rodar de novo → verde. Colar a linha.

| # | Implementação errada plausível | O que mata |
|---|---|---|
| M17 | O F-45 é mantido com `assertVisible` e recebe só um `assertNoJavaScriptErrors()` a mais — continua verde com o overlay a 1.833 px | o passo 2 acima: se o teste não ficar vermelho antes, RQ-03 **não** está atendida, e o `03` registra isso como blocker |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`.

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: sem recurso por identificador; CSS e lista de caminhos |
| Autorização exercida na ação | não se aplica: o recorte por permissão do **conteúdo** do Spotlight é das categorias `App\Filament\Spotlight\*`, já cobertas em `PermissoesDeAcoesTest` — fora desta wiki |
| Idempotência | não se aplica: registrar o asset duas vezes produziria duas tags `<link>` iguais; o Filament deduplica por id. Sem efeito acumulável a ancorar |
| Concorrência | não se aplica |
| Fronteira no ponto de entrada | não se aplica: sem entrada de dado |
| Domínio condicionado | **CT-01** — a partição é o tipo de classe (raiz × descendente × variante), e cada uma tem forma de escrita própria |
| Estado × operação de escrita | não se aplica |
| Ausente ≠ null ≠ vazio | **CT-01 (piso)** — a distinção que importa é *lista de classes vazia* (detector quebrado) × *lista com uma ausência* × *lista completa* |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | **CT-01 (partição de escape)** — o "caractere especial" deste meio é `:`, `/`, `[`, `.` no nome da classe |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Silêncio por CSS ausente** (linha do projeto — rule `css-filament.md`) | **CT-B01** (mede o efeito), **CT-01** (mede a causa). É a primeira vez que a linha ganha um oráculo automático além do screenshot |
| **Controle positivo do detector** (linha nova, herdada da wiki do site) | CT-01 (piso de 60), CT-04 (controles negativos) |
| **Regressão de asset registrado** | CT-03 (terceiro `Então`) |

## Fora do alcance da suíte — e por quê

| Afirmação | Por que a suíte não alcança | Quem verifica |
|---|---|---|
| O overlay está **legível** (contraste do texto sobre a caixa) nos dois temas | geometria se mede; cor se olha. `.ai/rules/testes-browser.md`: "para defeito de cor não há saída barata" | screenshot de CT-B01/CT-B02 + olhar, na Verificação Final |
| O arquivo publicado `public/css/kit/kit-spotlight.css` é igual à fonte | `filament:assets` copia; testar a cópia é testar o Filament | `git diff` depois de `php artisan filament:assets` deve vir vazio |
| Projeto **já instalado** recebe e aplica a correção | exige um `kit:update` real numa instalação de `TESTES KIT/` | Verificação Final, item manual, com a saída do comando colada no `03` |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | nenhuma classe da blade do vendor sem declaração, sob o escopo | R2 | EP + controle positivo | Kit | `tests/Kit/SpotlightCssTest.php` | M5, M6, M7, M8, M10 |
| CT-02 | o âncora de escopo é contrato dos dois lados; nada fora do escopo | R2 | contrato + ausência | Kit | idem | M9, M11 |
| CT-03 | cada painel carrega a folha | R3 | rastreio por painel | Kit (HTTP) | idem | M12, M13 |
| CT-04 | cobertura da lista do `kit:update`, com controles | R4 | EP + controle negativo | Kit | `tests/Kit/KitUpdateTest.php` | M14, M15, M16 |
| CT-B01 | o overlay cobre a viewport, acima, escurecido, input na tela — `Esquema` com linhas `claro` e `escuro` | R1 | geometria + cor computada da caixa | Browser | `tests/Browser/RoteiroDoKitTest.php` (F-45) | M1, M2, M3, M4 (linha `escuro`), M5, M7 |
| — | R5: CT-B01 vermelho antes da correção | R5 | execução | — | `03-progresso.md` | M17 |

## Divergência entre skill e rule do projeto

- A skill sugere `pest --parallel --tia` como padrão; `.ai/rules/testes-browser.md` proíbe
  `--parallel` com browser e exige `npm run build` + `view:cache` antes. **A rule vence**: os CT-B
  rodam por `composer test:browser` (ou o arquivo isolado depois de aquecer pelo kernel), e os CT
  de `tests/Kit` por `vendor/bin/pest --no-tia`, porque nesta máquina o TIA não termina (medido na
  wiki `site-de-documentacao`).

## Fechamento do ciclo — por que não há mutation score aqui

`pest --mutate` muta **código PHP**. O código desta correção é uma linha de `Css::make` e duas
entradas de array; a lógica toda está num arquivo `.css`, que nenhum operador de mutação alcança.
Os "mutantes" desta wiki são de **especificação** (M1–M17), e o que os mata é a execução dos
cenários acima — em particular o gate de R5, que é a mutação manual mais honesta possível: rodar o
teste contra o estado defeituoso real.
