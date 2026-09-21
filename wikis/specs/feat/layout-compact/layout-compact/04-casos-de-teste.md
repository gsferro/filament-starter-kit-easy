# Casos de Teste — Opção de layout compacto

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito** e das **ADRs decididas**. O oráculo é o `00`; o `01` entrou só para
> paths, rotas e a tabela `## Superfície de UI`.

> **Escrito depois do teste existir, e isso muda o que este arquivo pode afirmar.** A ordem certa é
> `04` antes do código; aqui ela foi invertida, e a especificação foi reconstruída a partir de
> **duas** fontes confrontadas: o `00-requisito.md` e `tests/Kit/DensidadeDoLayoutTest.php`. Onde o
> requisito pede algo que **nenhum caso prova**, está escrito como **lacuna declarada** (`L1`…`L4`)
> em vez de ser calado — é a única coisa honesta a fazer com um `04` escrito por último, e duas das
> quatro apontam para a cláusula que atravessou oito commits escrita e não versionada, sem que nada
> ficasse vermelho.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| **A** — A cadeia do settings (propriedade → mapa → semente → alinhamento → hook) | 3 | 3 | **9** | **completo** |
| **B** — O mecanismo de CSS (fora de layer, vence ou não vence) | 2 | 3 | **6** | padrão |
| **C** — Vocabulário e direção da falha | 2 | 3 | **6** | padrão |
| **D** — A escala (quantos degraus, com que valores) | 2 | 2 | 4 | padrão |
| **E** — O ganho em pixels nas quatro superfícies | 1 | 2 | 2 | mínimo (**fora da suíte** — ver `L1`) |

**Justificativa do Impacto 3 nas áreas A, B e C**: as três têm consumidor em **toda tela dos três
painéis**. A área A falha em silêncio (o campo grava e não governa); a B falha em silêncio e *com
aparência de certa* (a declaração sai no HTML e não muda um pixel); a C falha em voz alta e no pior
lugar — um `ValueError` dentro do layout base derruba os três painéis por causa de um erro de
digitação em configuração cosmética.

- Técnicas aplicadas: **partição de equivalência**, **partição exaustiva do enum**, **rastreio de
  efeito com asserção de ausência**, **pairwise (painel × nível)**, **round-trip** (`up()`/`down()`
  da migration), **contrapositivo** (o par ligado/desligado), **oráculo do outro lado da cadeia**
- Cenários: **14** (25 casos executados, com datasets) · Regras: **8** · Mutantes previstos: **30**
  · Sem matador: **2**, mais **1** com matador parcial — os três declarados em
  `## Lacunas Declaradas`, junto com as outras duas lacunas de cobertura (`L2`, `L3`)

> **Cenários × casos**: o arquivo tem 14 `it()`. Três deles têm dataset — CT-03 (4 exemplos),
> CT-10 (7) e CT-12 (3) —, e é daí que saem os **25** casos que o runner conta.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | enum novo `App\Support\DensidadeDoLayout`; propriedade nova no `Settings`; linha nova no `mapaDeConfiguracao()`; migration de settings nova; chave nova em `config/kit.php` e `.env.example`; método novo no `KitServiceProvider`; campo novo na aba Kit. **Sem model, sem tabela de domínio, sem rota, sem policy** | CT-07, CT-08, CT-13 |
| **F**unction | escolher o nível · gravar · alinhar a config no boot · coagir o vocabulário · decidir se emite · emitir a declaração | CT-01…CT-14 |
| **D**ata | o nível, que entra por **três portas**: `.env` (texto livre editado à mão), tabela `settings` (`payload` JSON, editável por fora) e o Select da tela (vocabulário fechado). Valores: os três legítimos, o erro provável em inglês (`compact`), caixa trocada (`COMPACTO`), vazio, `null`, booleano, número, texto qualquer | CT-02, CT-09, CT-10, CT-11, CT-12, CT-14 |
| **I**nterfaces | **duas de escrita** (o Select da tela e a chave do `.env`/tabela) e **uma de leitura**, que é o render hook de toda página dos três painéis. Sem HTTP próprio, sem job, sem webhook, sem comando | CT-03, CT-11, CT-14 |
| **P**latform | cascade layers do Tailwind 4; `@layer theme` do Filament; folhas de plugin que declaram layer própria (`croustibat/filament-jobs-monitor`); layout de autenticação de terceiro (`caresome/filament-auth-designer`); CSS própria do Pulse | CT-03, CT-05 |
| **O**perations | instalação nova (nasce confortável); instalação antiga que atualiza o kit e **não** deve mudar de aparência sozinha; quem liga e desliga na tela; quem edita a tabela por fora; rollback da migration | CT-01, CT-02, CT-04, CT-06, CT-08, CT-11 |
| **T**ime | **nenhum valor temporal.** O eixo temporal real é outro: *quando* a decisão é lida — no **registro do painel** ou **no render**. É a diferença entre "a tela governa" e "grava e vale no próximo deploy" | CT-06 |

---

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — Ligada, a opção aperta as **quatro** superfícies nomeadas, e nenhuma fica parcialmente compacta | E (mínimo) | RQ-05 + o invariante do `00` ("nenhuma superfície fica parcialmente compacta") | medição direta + congelamento do valor emitido | CT-12 · **`L1`** |
| **R2** — A declaração emitida **vence** a do Filament, e vence por estar fora de cascade layer | B (padrão) | RQ-05 (o compacto tem de ter efeito) + ADR-03 | partição do mecanismo + mutante de envelope | CT-05 |
| **R3** — **Desligada, nada muda**: o estado de fábrica é indistinguível do kit sem a feature | A (completo) | RQ-05 ("a possibilidade de ativar ou **desativar**") | contrapositivo + rastreio de efeito com asserção de ausência, nos **dois** pontos de observação | CT-01, CT-02, CT-04 |
| **R4** — O que se escolhe **no settings do kit** governa, nos três painéis | A (completo) | RQ-03, RQ-05 | rastreio de efeito ponta a ponta + pairwise painel × nível | CT-03, CT-07, CT-14 |
| **R5** — A decisão é lida **por request**: ligar e desligar vale no próximo request, sem deploy | A (completo) | RQ-05 ("em tempo de execução") + critério **(c)** de RQ-04 | partição do momento da leitura (registro × render) | CT-06 |
| **R6** — A escala tem **três degraus**, com valores fixos — não é um booleano disfarçado | D (padrão) | RQ-05 + ADR-04 | partição **exaustiva** do enum + congelamento de valor | CT-12, CT-13 |
| **R7** — O nível vive no settings sem virar segredo, e a instalação nova nasce com ele **semeado** | A (completo) | RQ-03 | tabela de decisão (lista `encrypted()` × gravação) + **round-trip** da migration | CT-08, CT-09 |
| **R8** — Valor fora do vocabulário **falha fechado**, no nível que não muda nada | C (padrão) | — **não é cláusula do `00`**; é regra derivada do risco, alinhada a `.ai/rules/config.md` ("falhe fechado") | EP das classes de entrada inválida + a consequência na página servida | CT-10, CT-11 |

**Técnica escalada acima do perfil da área**: R2 está em área `padrão` e recebe um mutante de
**envelope** (`@layer utilities{…}` em volta da regra). Motivo: é o único mutante da entrega que
produz HTML com aparência correta e efeito **zero** — nenhuma asserção de status, console ou
conteúdo o distingue, e foi exatamente esse modo de falha que a `v0.37.1` pagou.

### Cobertura das cláusulas

| RQ | Regra / cenário | Situação |
|----|-----------------|----------|
| RQ-01 | — | **documental**: entregue por ADR-05 e pelo item 2 do roadmap. Sem CT — ver `L2` |
| RQ-02 | — | **documental**: entregue por ADR-03/ADR-05 (medição na demo). Sem CT — ver `L2` |
| RQ-03 | R4, R7 | CT-03, CT-07, CT-08, CT-09, CT-14 |
| RQ-04 | — | **decisória**: o resultado dela é a existência das regras R1…R7. A medição que a fecha está no `01` → `### A decisão de RQ-04` e no `03` → `## Medição`. Sem CT — ver `L2` |
| RQ-05 | R1, R2, R3, R4, R5, R6 | CT-01…CT-08, CT-12, CT-13, CT-14 — com a lacuna `L1` no **pixel** |
| RQ-06 | — | ⛔ excluída por RQ-04. Não gera cenário: um CT para ela contradiria a entrega |
| RQ-07 | — | **documental** — `wikis/roadmap.md`, item 1 (commit `bf6e799`). Sem CT — ver `L3` |
| RQ-08 | — | **documental** — tabela de quatro linhas no item 1. Sem CT — ver `L3` |
| RQ-09 | — | **documental** — hipótese de pacote externo, item 1. Sem CT — ver `L3` |
| RQ-10 | — | **documental** — entregue nas duas metades (documento + link nos READMEs), mas **sem oráculo**: ver `L3`, que é a lacuna cara |

---

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| O nome `DensidadeDoLayout` e a assinatura de `espacamento()` | escolha de implementação — o requisito fala em "layout compact", não em enum | detalhe do cenário, nunca asserção |
| O mecanismo ser `--spacing` | é **ADR-03**, decisão tomada com medição, não cláusula do `00` | aceito como oráculo **por via da ADR**, e só em R2 e R6 |
| Os valores `0.2rem` / `0.175rem` | saíram da medição, não do requisito | congelados em CT-12 **como decisão**, não como verdade derivada da cláusula |
| Os rótulos do Select | texto visível que o requisito não determina | fora de asserção |
| O `<style>` ser emitido em `STYLES_BEFORE` | escolha de implementação | detalhe; o que os cenários afirmam é *"chega ao HTML servido"*, que sobrevive à troca do hook |

**Perguntas em aberto**: nenhuma nova. As quatro ambiguidades do `00` foram fechadas antes da
implementação — duas com o usuário (escopo das quatro superfícies, nome do roadmap) e duas com
premissa declarada (acesso à demo, Playwright npm no lugar do MCP).

---

## Setup Global

### Personas

- `usuarioDoKit('admin')` — o helper do projeto (`tests/Pest.php:usuarioDoKit():490`). Usado só em
  CT-14, que é o único cenário que passa pela tela. Exige
  `seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` e `Filament::setCurrentPanel('admin')`

### Fixtures

Nenhuma factory. O estado desta feature é **uma linha da tabela `settings`**, escrita pelos
helpers abaixo.

### Helpers do projeto usados

| Helper | O que faz | Por que importa aqui |
|---|---|---|
| `gravarConfiguracao()` (`tests/Pest.php:gravarConfiguracao():347`) | escreve o `payload` na tabela e esquece o singleton | é a porta de escrita **por fora da tela** |
| `alinharConfiguracoesDoKit()` (`tests/Pest.php:alinharConfiguracoesDoKit():368`) | chama o alinhamento como o `boot()` chamaria | com `RefreshDatabase` o `boot()` real roda antes das migrations, então quem quer exercitar o alinhamento o chama |
| `configuracaoGravada()` (`tests/Pest.php:configuracaoGravada():699`) | lê o `payload` **cru** | quem pergunta se o valor está cifrado não pode perguntar para quem decifra |
| `kitConfigCom()` (`tests/Pest.php:kitConfigCom():581`) | `require` do `config/kit.php` com a env forçada | a config do processo já foi resolvida no boot; `putenv()` depois não a reavalia |

**Helper local do arquivo**: `gravarDensidade($nivel)` — `gravarConfiguracao()` + `alinharConfiguracoesDoKit()`,
que é a dupla que reproduz um request de verdade.

### Fakes

Nenhum. Sem fila, sem e-mail, sem HTTP externo.

### Estratégia de DB

`RefreshDatabase` global, do `tests/Pest.php` do projeto.

### O oráculo, e a regra que ele impõe ao arquivo inteiro

> **Nenhum cenário pergunta à `config()` o que a própria `config()` acabou de guardar.** A escrita é
> sempre na **tabela** `settings`; a leitura é sempre do **outro lado da cadeia** — o HTML servido,
> o retorno do render hook registrado, ou o `payload` cru.
>
> Isto não é preferência de estilo: a feature atravessa cinco elos (propriedade → linha do mapa →
> linha semeada → `aplicarNaConfig()` → render hook) e **quatro deles falham em silêncio**. Um
> cenário que fizesse `config()->set()` e lesse `config()` de volta ficaria verde com o Settings
> inteiro fora do caminho — que é precisamente o defeito que `.ai/rules/config.md` registra
> (*"`config()->set()` aceita QUALQUER chave"*).

---

## Regra R1 — Ligada, a opção aperta as quatro superfícies nomeadas

> `RQ-05` + invariante do `00` · perfil **mínimo** · técnica: **medição direta** (fora da suíte) +
> congelamento do valor emitido

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: Ligada, a opção aperta stats, tabela, menu e botão — e nenhuma delas fica de fora

    Cenário: [CT-12] O valor emitido em cada degrau é o que a medição fixou
      Dado o degrau <nível>
      Quando se pergunta qual espaçamento ele declara
      Então a resposta é <espaçamento>

      Exemplos:
        | nível       | espaçamento |
        | confortavel | nenhum      |
        | compacto    | 0.2rem      |
        | denso       | 0.175rem    |
```

**Por que o cenário é esse, e não "a linha da tabela mede 46,4 px"**: as quatro superfícies medem
em `--spacing`, então **o valor emitido é a variável independente inteira**. Congelá-lo faz de uma
troca de `0.2rem` por `0.2em` ou por `2px` uma reprovação — e não há nenhum outro ponto da suíte
onde ela apareceria. O pixel em si é medido fora da suíte (`01`, passo 6), e a lacuna está
declarada em `L1`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `0.2em` ou `2px` no lugar de `0.2rem` — unidade relativa à fonte do elemento, que não é o que o Filament usa | CT-12 |
| M2 | Degraus invertidos: `denso` mais folgado que `compacto` | CT-12 (os três exemplos, em ordem) |
| M3 | A declaração alcança a tabela mas não os stats, o menu ou o botão | ⚠️ **sem matador** — `L1` |

---

## Regra R2 — A declaração vence o Filament, e vence por estar fora de cascade layer

> `RQ-05` + ADR-03 · perfil **padrão** · técnica: **partição do mecanismo** + mutante de envelope

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: A declaração do kit fica fora de cascade layer

    Cenário: [CT-05] A tag emitida pelo kit não está envolvida em @layer nem usa !important
      Dado que o nível gravado é "compacto"
      Quando se lê o bloco de estilos emitido antes do Filament
      Então a tag que carrega "--spacing" começa em "<style>:root{--spacing:"
      E ela não contém "@layer"
      E ela não contém "!important"
```

**Situação de partida declarada**: o bloco de `STYLES_BEFORE` **já contém** uma declaração com
`@layer` — a ordem das layers da `v0.37.1` —, e ela é correta. Por isso o cenário **recorta** a tag
do kit e afirma sobre ela, em vez de sobre o bloco inteiro. Um cenário que negasse `@layer` no
bloco todo reprovaria a feature vizinha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | A regra envolvida em `@layer utilities{…}` — **o reflexo de quem leu a correção da `v0.37.1`**. Sai no HTML, parece certa, não muda um pixel | CT-05 |
| M5 | A regra com `!important` — funciona, e esconde que o mecanismo está errado; na primeira layer nova de plugin, quebra | CT-05 |
| M6 | Seletor diferente de `:root` (ex.: `body`), que não alcança as variáveis declaradas em `:root,:host` | CT-05 (`toStartWith`) |

---

## Regra R3 — Desligada, nada muda

> `RQ-05` · perfil **completo** · técnica: **contrapositivo** + rastreio de efeito com asserção de
> ausência, nos dois pontos de observação

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: No nível de fábrica o kit não emite estilo nenhum

    Cenário: [CT-01] O hook não devolve declaração de espaçamento no nível confortável
      Dado que o nível gravado é "confortavel"
      Quando se lê o bloco de estilos emitido antes do Filament
      Então ele não contém "--spacing"
      E ele continua contendo a declaração de ordem das cascade layers

    Cenário: [CT-04] E a página servida também não a contém
      Dado que o nível gravado é "confortavel"
      Quando se pede a tela de login do painel admin
      Então a resposta é 200
      E o corpo dela não contém "--spacing:"

    Cenário: [CT-02] E o valor de fábrica é o confortável
      Dado um ambiente sem a chave KIT_DENSIDADE_DO_LAYOUT
      Quando se lê o arquivo de configuração do kit
      Então a densidade dele é "confortavel"
```

**Por que os três, e por que nenhum substitui o outro.** CT-01 observa o **hook**; CT-04 observa a
**página servida** — e os dois são necessários porque uma implementação pode registrar o hook e o
layout não o emitir, ou emitir por outro caminho. CT-02 fecha o terceiro lado: sem ele, CT-01 e
CT-04 continuariam verdes numa entrega que **nascesse `denso`**, e toda instalação existente mudaria
de aparência sozinha ao atualizar o kit — provando "confortável não emite" enquanto ninguém está no
confortável.

A asserção de CT-01 é sobre a **ausência de `--spacing`** e não sobre string vazia porque o bloco
carrega também a ordem das layers, que precisa continuar lá.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | O confortável emite `<style>:root{--spacing:0.25rem}</style>` — equivalente na tela, mentira na inspeção | CT-01, CT-04 |
| M8 | O confortável emite `<style></style>` vazio | CT-01 (não contém `--spacing`) — ⚠️ **parcial**: a tag vazia passa. Ver `L4` |
| M9 | O padrão de fábrica é `compacto` ou `denso` | CT-02 |
| M10 | O hook devolve a declaração sempre, com o valor certo — o nível governa o **texto**, não a **existência** | CT-04 |
| M11 | O hook deixa de emitir a ordem das cascade layers junto (regressão da `v0.37.1`) | CT-01 (segunda asserção) |

---

## Regra R4 — O que se escolhe no settings governa, nos três painéis

> `RQ-03`, `RQ-05` · perfil **completo** · técnica: **rastreio de efeito ponta a ponta** + pairwise
> painel × nível

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: O nível gravado chega ao HTML servido dos três painéis

    Cenário: [CT-03] O nível gravado aparece na página servida
      Dado que o nível gravado é <nível>
      Quando se pede <rota>
      Então a resposta é 200
      E o corpo dela contém <declaração>

      Exemplos:
        | rota          | nível    | declaração                                  |
        | /admin/login  | compacto | <style>:root{--spacing:0.2rem}</style>      |
        | /admin/login  | denso    | <style>:root{--spacing:0.175rem}</style>    |
        | /app/login    | compacto | <style>:root{--spacing:0.2rem}</style>      |
        | /infra/login  | denso    | <style>:root{--spacing:0.175rem}</style>    |

    Cenário: [CT-07] Os três lugares do settings estão ligados
      Dado a classe de configurações do kit
      Então ela declara a propriedade "densidade_do_layout"
      E o mapa de configuração liga essa propriedade a "kit.densidade_do_layout"
      E existe linha semeada para ela no grupo "kit"
      Quando se grava "compacto" na tabela e se alinha a config como o boot faria
      Então a chave de configuração passa a valer "compacto"

    Cenário: [CT-14] O Select da tela grava, grava string, e o que gravou chega ao HTML
      Dado um administrador autenticado no painel admin
      Quando ele escolhe "compacto" na tela de configurações e salva
      Então o formulário não acusa erro
      E o payload gravado é exatamente a string "compacto"
      E, alinhada a config, o painel servido contém <style>:root{--spacing:0.2rem}</style>
```

**As rotas de CT-03 são de login de propósito.** Elas são servidas pelo layout do
`caresome/filament-auth-designer`, que é o candidato mais forte a **não** emitir o `STYLES_BEFORE`,
por vestir as telas de autenticação com blade própria. Se a declaração chega lá, chega no resto —
todos os layouts passam pelo `base.blade.php`. O pairwise é painel × nível, três painéis e dois
níveis não-padrão em quatro exemplos.

**A última asserção de CT-07 é o que impede o cenário de ficar verde sobre um mapa vazio**: ela
fecha na chave de configuração de verdade, **depois** do alinhamento, com um valor que **difere**
do de fábrica — um alinhamento que não fizesse nada devolveria `confortavel`.

**CT-14 é a única porta de escrita que é a porta de verdade.** Os outros treze cenários gravam
direto na tabela, que é justamente o caminho que **contorna** o elo da tela. Ele cobre o
`## Gate de tela de escrita` do `01`, e as três asserções dele não se substituem:
`assertHasNoFormErrors()` mata o campo que recusa o valor válido; o `toBe('compacto')` sobre o
**payload cru** mata a gravação do enum serializado (`{"value":"compacto"}` satisfaria uma
comparação frouxa); e o HTML fecha a cadeia até a ponta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | **A linha do `mapaDeConfiguracao()` ausente** — o campo aparece, grava, e não governa nada | CT-03, CT-06, CT-07, CT-14 (**medido**: 7 dos 14 CTs reprovam) |
| M13 | O hook registrado por painel, alcançando só um dos três | CT-03 (os três painéis) |
| M14 | O campo declarado com `->options(DensidadeDoLayout::class)`, devolvendo instância do enum ao `fill()` do spatie e estourando `TypeError` no salvamento da tela inteira | CT-14 — **e nenhum outro**; foi o defeito real da entrega |
| M15 | O campo com `->dehydrated(false)` ou fora do schema — grava nada | CT-14 (payload cru) |
| M16 | O valor gravado como enum serializado em vez de string | CT-14 (`toBe('compacto')` sobre o payload cru) |

---

## Regra R5 — A decisão é lida por request

> `RQ-05` ("em tempo de execução") + critério **(c)** de RQ-04 · perfil **completo** · técnica:
> **partição do momento da leitura** (registro do painel × render)

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: Trocar o nível vale no próximo request, sem remontar o painel

    Cenário: [CT-06] A mesma chamada responde diferente depois da troca
      Dado que o nível gravado é "confortavel" e o bloco de estilos foi lido uma vez
      Quando o nível gravado passa a ser "denso", no mesmo processo
      Então a leitura anterior não continha "--spacing"
      E a nova leitura contém "--spacing:0.175rem"
```

Este é **o** cenário de falsificabilidade da entrega. Uma implementação por `viteTheme()` — ou um
render hook que recebesse a string **já resolvida** em vez de uma `Closure` — congelaria a decisão
no momento do registro: CT-03 poderia até passar, porque o processo de teste nasceria com o valor
certo, e o toggle em produção gravaria **sem fazer efeito até o próximo deploy**. É exatamente a
armadilha que `.ai/rules/settings.md` registra para `registro_verificar_email`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | O hook recebe a string já resolvida no `boot()`, e não uma `Closure` | CT-06 |
| M18 | O valor memoizado em `static` dentro do hook — "otimização" que roda uma vez por processo | CT-06 |
| M19 | A densidade movida para `viteTheme()` | CT-06 (e o item 4 do roadmap avisa quem tentar) |

---

## Regra R6 — Três degraus, e não um booleano disfarçado

> `RQ-05` + ADR-04 · perfil **padrão** · técnica: **partição exaustiva do enum**

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: A escala tem exatamente três degraus, nesta ordem

    Cenário: [CT-13] Os degraus expostos são três, na ordem da escala
      Quando se listam os degraus da escala
      Então eles são exatamente "confortavel", "compacto" e "denso", nessa ordem
```

A decisão do usuário foi por **níveis** justamente porque a distorção de proporção escala com a
intensidade (ADR-04). Um enum reduzido a dois casos cumpriria **todos** os outros cenários deste
arquivo e desfaria a decisão em silêncio — por isso a partição é exaustiva e a ordem entra na
asserção.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | A escala reduzida a dois casos (ligado/desligado) — a ADR-04 desfeita em silêncio | CT-13 |
| M21 | Degrau extra não documentado, ou ordem trocada | CT-13 |

---

## Regra R7 — Vive no settings sem virar segredo, e a instalação nova nasce semeada

> `RQ-03` · perfil **completo** · técnica: **tabela de decisão** (lista `encrypted()` × gravação) +
> **round-trip** da migration

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: A densidade é semeada na instalação e não é tratada como segredo

    Cenário: [CT-08] A migration semeia com o valor de fábrica e o rollback remove
      Dado a migration de settings da densidade
      Quando se executa o rollback dela
      Então não existe mais linha "densidade_do_layout" no grupo "kit"
      Dado que a configuração do kit passa a valer "denso"
      Quando se executa a migration de novo
      Então a linha semeada vale "denso"

    Cenário: [CT-09] A densidade não entra na lista de cifrados, e o payload fica legível
      Dado a lista de propriedades cifradas do settings do kit
      Então ela não contém "densidade_do_layout"
      Quando se grava "denso" na tabela
      Então o payload cru dela é exatamente "denso"
```

**O `Dado` de CT-08 fixa a config antes de refazer a migration**, e é isso que salva o cenário de
ser vácuo: semear "o valor da config" e conferir contra a mesma config não distingue implementação
nenhuma. Aqui a config diz `denso` e a semente precisa dizer `denso` — o que mata de uma vez a
implementação que ignorasse a config e escrevesse o literal `confortavel`.

**CT-09 tem duas caras de falha, e por isso duas asserções**: nome fora da lista com
`addEncrypted()` na migration devolve criptograma na leitura; nome dentro da lista sem necessidade
cifra o que não precisava. A segunda metade lê o `payload` **cru**, sem passar pelo decifrador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | A propriedade acrescentada **à migration que já rodou**, em vez de numa nova — instalação de terceiro que só roda `migrate` fica sem a linha e o boot estoura `MissingSettings` em todo request | CT-08 (existência da linha) + CT-07 |
| M23 | `down()` que não remove a propriedade | CT-08 |
| M24 | A semente com o literal `confortavel`, ignorando a config | CT-08 (o `Dado` com `denso`) |
| M25 | A propriedade cifrada sem necessidade | CT-09 |

---

## Regra R8 — Valor fora do vocabulário falha fechado

> Regra **derivada do risco**, não de cláusula do `00` · perfil **padrão** · técnica: **EP das
> classes de entrada inválida** + a consequência na página servida

```gherkin
# language: pt
Funcionalidade: Layout compacto

  Regra: Vocabulário desconhecido cai no nível que não muda nada

    Cenário: [CT-10] Qualquer entrada fora do vocabulário vira o nível confortável
      Quando se coage <entrada>
      Então o resultado é o nível confortável

      Exemplos:
        | entrada     | classe de equivalência                  |
        | 'compact'   | o erro provável — o termo em inglês     |
        | 'COMPACTO'  | caixa trocada (o enum é sensível)       |
        | ''          | vazio                                    |
        | null        | ausente                                  |
        | true        | tipo errado — booleano                   |
        | 1           | tipo errado — número                     |
        | 'sim'       | texto qualquer                           |

    Cenário: [CT-11] Um nível ilegível gravado no banco não derruba a página
      Dado que a tabela settings contém "compact" como densidade
      Quando se pede a tela de login do painel admin
      Então a resposta é 200
      E o corpo dela não contém "--spacing:"
```

**CT-10 mede a função; CT-11 mede a consequência, e os dois não se substituem.** Uma implementação
que coagisse corretamente em `config/kit.php` e chamasse `from()` no render hook passaria em CT-10 e
**derrubaria o painel** em CT-11 — e esse é o caminho real do defeito, porque `aplicarNaConfig()`
escreve o `payload` do banco **direto** na config, sem passar pelo arquivo. São duas portas, e a
coerção precisa estar nas duas.

A direção da falha é a parte que importa: o pior resultado de um valor ilegível tem de ser *"a
aplicação não muda de aparência"*, **nunca** *"tela em branco nos três painéis"*.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | `from()` no lugar de `tryFrom()` — `ValueError` no layout base de toda tela | CT-10, CT-11 |
| M27 | Coerção só em `config/kit.php`, não no consumo — a porta do banco fica aberta | CT-11 |
| M28 | Normalização de caixa (`strtolower`) — "conserto" que aceita `COMPACTO` e cria um segundo vocabulário não documentado | CT-10 (caixa alta) |
| M29 | Default de falha em `compacto` em vez de `confortavel` — falha **aberta** | CT-10 |
| M30 | `config/kit.php` aceitando `KIT_DENSIDADE_DO_LAYOUT` ilegível sem coagir | ⚠️ **sem matador** — `L4` |

---

## Lacunas Declaradas

> Quatro. Duas delas (`L2`, `L3`) explicam por que uma cláusula do requisito chegou ao fim da
> implementação **sem ter sido entregue** e nada ficou vermelho.

### `L1` — Nenhum caso versionado mede o pixel das quatro superfícies

**A cláusula**: RQ-05, *"adiciona o compact no stats, table, menu, button e etc"*, com o invariante
do `00` de que nenhuma superfície fica parcialmente compacta.

**O que existe**: CT-12 congela o **valor emitido**, e a medição do `01` (passo 6) mostra as quatro
superfícies encolhendo nos dois degraus — 46,4 px por linha de tabela, 32,8 px de botão, 28,8 px de
campo, 32,8 px de item de menu, 137,2 px de cartão de estatística no `compacto`.

**O que falta**: a medição é de **sessão**, não de suíte. Se um plugin futuro passar a declarar
`--spacing` próprio numa layer que vença, ou se o Filament trocar o token, CT-12 continua verde e o
layout para de apertar. **M3 não tem matador.**

**O que foi tentado**: o gate do `05` recusa o cenário — medir altura computada exige navegador, e a
suíte de browser do kit não carrega Playwright com leitura de estilo computado. O caminho honesto
seria um CT-B que lesse `getComputedStyle` de um seletor de vendor, e isso é **lista congelada de
classe de terceiro**, exatamente o que a ADR-03 recusa como mecanismo.

**Consequência aceita**: a prova do pixel é a medição registrada, refeita quando o Filament subir de
versão. O item 3 do roadmap é o gatilho.

### `L2` — As cláusulas de estudo (RQ-01, RQ-02, RQ-04) não têm oráculo executável

São cláusulas **documentais e decisórias**: pedem que algo seja estudado, navegado e medido. O
artefato que as atende é a ADR, e a ADR é prosa com número. Nenhum teste pode afirmar *"o estudo foi
feito"* sem virar asserção sobre a existência de um arquivo.

**Aceito como lacuna real**, e não como "não se aplica": o kit **tem** precedente de oráculo
documental — `tests/Kit/RedeDeDocumentacaoTest.php` e `tests/Kit/SiteDeDocumentacaoTest.php`
afirmam sobre a rede de documentação, e a wiki `kit-install-host-local` usou um CT para a cláusula
de documentação dela. Aqui não foi usado.

### `L3` — A cláusula sem CT é a que ficou oito commits no limbo

**A cláusula**: RQ-10, *"crie uma para TODOs ou com o nome normalmente utilizando no @README.md para
informas futuras melhorias"* — duas metades: **o documento** e **a ligação com o README**.

**O que aconteceu**: `wikis/roadmap.md` foi escrito cedo, completo, e ficou **fora do índice do
git** durante os oito primeiros commits da feature — `git status` devolvia `?? wikis/roadmap.md` —,
e o `README.md` não tinha a linha. A suíte ficou **verde do começo ao fim**. A cláusula só fechou
no commit `bf6e799`, e o que a fechou foi alguém reparar, não um caso vermelho.

**Por que nada ficou vermelho**: nenhum caso afirma sobre a existência do documento nem sobre a
ligação. Os contadores do README que **têm** teste (`SiteDeDocumentacaoTest`) contam *features
especificadas* e *arquivos de teste* — nenhum deles conta documentos de topo de `wikis/` —, e
`RedeDeDocumentacaoTest` varre o que está **no repositório**, não o que está no disco.

**O cenário que faltou**, e ele é barato:

```gherkin
    Cenário: [CT-15, NÃO ESCRITO] O roadmap existe no repositório e o README o aponta
      Dado a árvore versionada do kit
      Então existe "wikis/roadmap.md"
      E o texto dele diz que descreve o futuro do KIT, não o do projeto de quem o instala
      E o README.md contém um link para ele
```

A segunda asserção não é enfeite: a decisão registrada no `00` é que o roadmap **viaja** para todo
projeto criado do kit (`wikis/*.md` fora do `export-ignore`), e sem essa frase o arquivo vira ruído
confuso na raiz de um projeto de terceiro.

**Roteamento**: destino **3 — teste**, e continua **aberto**. O artefato está entregue; o oráculo
que impede a omissão de voltar, não. Registrado em `03-progresso.md` → `## Pendências` → P1.

### `L4` — Duas fronteiras sem cenário

1. **`<style></style>` vazio** (M8): CT-01 nega a presença de `--spacing`, o que deixa passar uma
   implementação que emitisse a tag vazia no confortável. O contrato é `null` em `espacamento()`, e
   CT-12 o congela — o par cobre por composição, não por asserção direta.
2. **`config/kit.php` com env ilegível** (M30): CT-02 lê o arquivo com a chave **ausente** e CT-10
   exercita a função de coerção **isolada**. Nenhum cenário faz
   `kitConfigCom('KIT_DENSIDADE_DO_LAYOUT', 'compact')` e confere que o arquivo devolve
   `confortavel`. O helper existe e o cenário custaria três linhas.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a densidade é configuração da instalação, não tem dono nem registro por usuário |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: o campo herda a proteção da tela, que já tem cobertura própria em `ConfiguracoesDoKitTelaTest` |
| Idempotência (ancorada no agregado) | CT-06 — gravar o mesmo nível duas vezes é indistinguível de gravar uma; o efeito é função pura do valor corrente |
| Concorrência | **não se aplica**: não há leitura-modificação-escrita; a escrita é substituição total de uma linha |
| **Fronteira no ponto de entrada** (gravação) | CT-14 |
| **Domínio condicionado** (tipo × valor) | **não se aplica**: o vocabulário não depende de nenhum outro campo |
| **Estado × operação de escrita** | **não se aplica**: sem ciclo de vida; a propriedade só tem "semeada" e "não semeada", cobertos por CT-08 |
| Ausente ≠ null ≠ vazio | CT-10 (`''`, `null`) + CT-02 (chave ausente no arquivo) |
| Paginação / ordenação | **não se aplica** |
| Timezone / DST | **não se aplica**: nenhum valor temporal |
| Unicode / limite de varchar | **não se aplica**: vocabulário fechado de três palavras ASCII, coagido antes de qualquer uso |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | CT-08 (`up()` depois de `down()`) |
| Mass assignment | **não se aplica**: a escrita passa pelo `fill()` do spatie sobre propriedade declarada |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Superfície Livewire** (método público, prop pública, estado do framework) | **não se aplica**: a feature não cria Page, Widget nem componente. Ela acrescenta **um campo** a uma Page existente e **um render hook**; `grep -rn "public function" app/Support/DensidadeDoLayout.php` devolve três métodos estáticos e um de instância, nenhum deles alcançável por `$wire.` — o enum não é componente |
| **Estado do framework usado sem validar** (índice de array, `parse`, coluna) | CT-10, CT-11 — o valor **é** usado cru numa concatenação de string CSS, e é por isso que a coerção existe nas duas portas |
| **IDOR por entidade** | **não se aplica**: nenhuma tabela de domínio persistida |
| **Escopo com discriminante nulo** (fecha ou abre?) | CT-10 (`null`) — **fecha**, no `confortavel` |
| **Saída do estado de erro** (4xx/redirect tem destino) | **não se aplica**: a feature não produz 4xx. CT-11 prova o oposto — o caminho de erro é **200 sem efeito**, que é a saída desenhada |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | O confortável não emite declaração de espaçamento | R3 | rastreio de efeito com ausência | Feature | `tests/Kit/DensidadeDoLayoutTest.php` | M7, M8 (parcial), M11 |
| CT-02 | Nasce confortável no arquivo de configuração | R3 | EP do default | Feature | idem | M9 |
| CT-03 | O nível gravado chega ao HTML dos três painéis (4 exemplos) | R4 | pairwise painel × nível | Feature (HTTP) | idem | M12, M13 |
| CT-04 | Nenhuma declaração no HTML quando o nível é confortável | R3 | contrapositivo de CT-03 | Feature (HTTP) | idem | M7, M10 |
| CT-05 | A declaração fica fora de cascade layer | R2 | mutante de envelope | Feature | idem | M4, M5, M6 |
| CT-06 | Muda de resposta no mesmo processo, sem remontar o painel | R5 | partição do momento da leitura | Feature | idem | M12, M17, M18, M19 |
| CT-07 | Propriedade, linha do mapa e linha semeada | R4, R7 | rastreio dos três elos | Feature | idem | M12, M22 |
| CT-08 | A migration semeia o valor de fábrica e o rollback remove | R7 | round-trip | Feature | idem | M22, M23, M24 |
| CT-09 | A densidade não é tratada como segredo | R7 | tabela de decisão | Feature | idem | M25 |
| CT-10 | Cai no confortável para qualquer valor fora do vocabulário (7 exemplos) | R8 | EP das entradas inválidas | Feature | idem | M26, M28, M29 |
| CT-11 | Página servida normalmente com nível ilegível no banco | R8 | consequência do inválido | Feature (HTTP) | idem | M26, M27 |
| CT-12 | Fixa o espaçamento medido de cada nível (3 exemplos) | R1, R6 | congelamento de valor | Feature | idem | M1, M2 |
| CT-13 | Expõe exatamente os três níveis da escala | R6 | partição exaustiva do enum | Feature | idem | M20, M21 |
| CT-14 | Grava pelo Select da tela e leva até o HTML | R4 | gravação por componente Livewire | Feature (Livewire + HTTP) | idem | M14, M15, M16 |
| ~~CT-15~~ | O roadmap existe no repositório e o README o aponta | — | oráculo documental | — | **não escrito** — ver `L3`; o artefato está entregue (`bf6e799`), o oráculo não | — |

**14 cenários, 25 casos executados.** Todos em `tests/Kit/DensidadeDoLayoutTest.php`, suíte `Kit`.

### Gate de falsificabilidade

| Verificação | Resultado |
|---|---|
| Mutação **M12** — apagar a linha do `mapaDeConfiguracao()` | **7 dos 14 CTs reprovam**, medido em 2026-09-21 (commit `c2189d7`). Entre eles CT-03, CT-06, CT-07 e CT-14, que são os que afirmam sobre o valor do banco chegando ao outro lado da cadeia |
| Mutantes previstos | 30 |
| Mutantes **sem matador** | **2** — M3 (`L1`) e M30 (`L4`), ambos declarados |
| Mutantes com matador parcial | **1** — M8 (`L4`), coberto por composição de CT-01 com CT-12 |

Nenhum cenário positivo passa sem situação de partida declarada: todos os que afirmam presença
começam por um `Dado` que **grava o nível na tabela**, nunca por um `config()->set()`.

---

## Sem CT-B

Não existe `05-casos-de-teste-browser.md`, e o gate é o da `feature-wiki`: *o cenário só vai para o
browser quando afirma sobre algo que **só** o navegador prova*.

A feature é visual, e por isso a tentação é grande — mas o que os cenários precisam provar não é
visual:

| O que parece exigir browser | Por que não exige |
|---|---|
| "a declaração chega na página" | é string no corpo da resposta HTTP — CT-03, CT-04, CT-11, CT-14 |
| "a declaração está fora de cascade layer" | é a forma do texto emitido — CT-05 |
| "o toggle vale no próximo request" | é o retorno do hook mudando no mesmo processo — CT-06 |
| "o campo grava" | é componente Livewire — CT-14 |

O único enunciado que **de fato** só o navegador prova — *"a linha da tabela passou de 56,0 px para
46,4 px"* — foi medido com Playwright como **instrumento de decisão** (`01`, passo 6), e a razão de
ele não virar CT-B está em `L1`: ele exigiria congelar seletor de vendor, que é o acoplamento que a
ADR-03 recusa como mecanismo. A lacuna está declarada, não escondida.
