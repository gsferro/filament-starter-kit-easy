# Decisões Arquiteturais — Rector no kit

## ADR-01: Rector entra, no papel de ferramenta de upgrade

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O kit tem três ferramentas de qualidade, e nenhuma delas resolve upgrade de major. Quando o Laravel
14 sair, ou o PHP 8.5 virar o alvo, o trabalho é achar e reescrever chamada por chamada — em código
que o mantenedor escreveu e em código que quem instalou o kit escreveu depois.

O `driftingly/rector-laravel` 2.5.0 tem `LaravelSetList::LARAVEL_130` e a escada
`UP_TO_LARAVEL_130`, exatamente a base que o requisito pede (RQ-05).

### Decisão

Instalar `rector/rector` + `driftingly/rector-laravel` como `require-dev`, com um `rector.php` que
nasce **sem nenhum set ligado** e dois scripts de composer (`refactor:preview`, `refactor:apply`)
fora do `composer test`.

O papel é: **ferramenta que você chama quando vai subir de versão**, não rotina que roda sozinha.

### Alternativas Consideradas

1. **Não instalar nada** — descartada: o upgrade continua manual, e o kit perde a única ferramenta
   do ecossistema que automatiza isso. A ausência custa numa data conhecida (o próximo major).
2. **Instalar só quando precisar** — descartada: no dia do upgrade, quem estiver fazendo vai
   descobrir o pacote, escolher sets no escuro e provavelmente rodar com os sets de qualidade
   juntos — que é o cenário que ADR-02 mostra ser ruim. Entregar o `rector.php` **pronto e
   comentado** é o que transforma a ferramenta em decisão já tomada.

### Consequências

- **Positivas**: o upgrade de major deixa de ser exploração; o `rector.php` documenta a escolha de
  sets no lugar onde ela é usada.
- **Negativas**: duas dependências de desenvolvimento a mais (o Rector puxa `phpstan/phpstan`, que
  o kit já tem via larastan — sem custo real).
- **Riscos**: alguém rodar `refactor:apply` sem entender. Mitigado pelo arquivo nascer sem sets e
  pelo aviso no topo.

### Referências

- `rector.php`
- `wikis/qualidade-de-codigo.md`

---

## ADR-02: Rector **fica fora** do gate de lint

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O requisito pergunta explicitamente pelas "validações no lint" (RQ-03). A forma natural seria
`rector --dry-run` dentro de `composer test`, reprovando enquanto houvesse diferença — foi assim que
o FilaCheck entrou, uma semana antes.

Antes de decidir, mediu-se: `rector process --dry-run` com os sets de qualidade do Laravel mais PHP
8.4, sobre `app/`, `database/`, `routes/` e `tests/`.

### Decisão

**Não.** Os sets de qualidade não entram no `rector.php`, e nenhum comando de Rector entra em
`composer test`.

### O que a medição mostrou

**103 arquivos** seriam reescritos. Os três maiores drivers são opinião de estilo, não correção:

| Regra | Arquivos | O que propõe |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | `User::find()` → `User::query()->find()` |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | `: void` em closure |
| `AppToResolveRector` | 21 | `app()` → `resolve()` |

Num kit cujo produto **é o código-exemplo legível**, `User::find()` e `app()` são o idioma que
qualquer pessoa do ecossistema lê sem parar. Trocá-los por variantes mais verbosas não melhora nada
e piora a primeira leitura — que é a função do código deste repositório.

### O argumento que fecha a questão

`CarbonToDateFacadeRector` (7 arquivos) propõe em `InfraPanelProvider.php`:

```diff
- Carbon::now()->subDays(...)
+ Date::now()->subDays(...)
```

E isso **quebra**, por três fatos verificáveis:

1. `now()` é `Date::now()` — `Illuminate/Foundation/helpers.php:623`
2. O kit faz `Date::use(CarbonImmutable::class)` — `KitServiceProvider.php:57`
3. `FilamentExceptionsPlugin::modelPruneInterval()` exige `Carbon` mutável

O PHPStan level 7 **já reportou exatamente esse erro** nesta branch, quando a primeira versão do
código usava `now()`. O `Carbon::now()` explícito é a correção, e o Rector a desfaria.

> Uma ferramenta de qualidade que reverte a correção de outra não é um gate. É uma disputa — e o
> build passaria a depender de qual das duas roda por último.

### Alternativas Consideradas

1. **Entrar no lint com todos os sets** — descartada pelo acima.
2. **Entrar no lint com uma lista curta de regras seguras** — descartada por custo/benefício: as
   regras que sobrariam depois de tirar estilo, opinião e o Carbon são cosméticas (`: void` em
   closure, const tipada), e nenhuma delas pega defeito que o PHPStan level 7 já não pegue. Seria
   uma quarta ferramenta no gate para ganhar quase nada, com manutenção de allow-list permanente.
3. **Entrar no lint só em `app/`, poupando testes** — descartada: o `EloquentMagicMethod` e o
   `AppToResolve`, que são o grosso, estão justamente em `app/`.

### Consequências

- **Positivas**: `composer test` continua com três gates que discordam entre si em zero pontos; o
  tempo de CI não cresce; ninguém precisa manter allow-list de regra.
- **Negativas**: as poucas melhorias reais que o Rector proporia (const tipada, `: void`) não são
  aplicadas automaticamente. Aceito — o PHPStan cobre o que importa.
- **Riscos**: alguém no futuro achar que "faltou" o Rector no lint e adicionar sem ler isto.
  Mitigado por `tests/Kit/QualidadeDeCodigoTest.php`, que falha se um set de qualidade for ligado
  ou se o Rector entrar no `composer test`.

### Referências

- `01-plano-acao.md` → seção "O que foi medido"
- `app/Providers/Filament/InfraPanelProvider.php` — o comentário do `Carbon::now()`
- Refine: ADR-01

---

## ADR-03: Filament não precisa de regras nossas — ele tem a própria ferramenta

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

RQ-04 pergunta se existem regras de Rector específicas para Filament v5, já que o Filament é a base
do kit. Se existissem, seriam o argumento mais forte para adotar o pacote.

### Decisão

Registrar que **não existem no `rector-laravel`** — busca por "filament" no pacote devolve zero — e
que a lacuna **não precisa ser preenchida pelo kit**, porque o Filament distribui a própria
ferramenta.

`filament/upgrade`, versão **v5.7.6**, declara `rector/rector: ^2.0` e é o caminho oficial de
upgrade de major (`vendor/bin/filament-v4` e equivalentes). É mantido em lockstep com o framework —
quem escreve as regras é quem quebra a API.

### Alternativas Consideradas

1. **Escrever regras Rector de Filament dentro do kit** — descartada: seria assumir a manutenção de
   um conjunto de regras contra uma API de terceiro que muda a cada major, competindo com a
   ferramenta oficial. É o oposto da regra do kit de não reimplementar vendor.
2. ~~**Adicionar `filament/upgrade` como dependência agora**~~ — **revista, ver a emenda abaixo.**

### Emenda — 2026-08-18: `filament/upgrade` **instalado**

A versão original desta ADR recusava instalar o pacote agora, com o argumento de que "a versão dele
acompanha a versão de destino, e instalar hoje o `~5.0` fixaria a ferramenta na versão errada quando
o 6 sair".

**O mantenedor decidiu o contrário, e o argumento dele é melhor**: já que a ferramenta existe e é
oficial, ela deve estar instalada e configurada — não descrita num parágrafo que alguém vai ter de
seguir sob pressão, no dia do upgrade.

O contra-argumento original não some, mas encolhe: quando o Filament 6 sair, `composer require
filament/upgrade:^6.0 --dev` troca a ferramenta, e o script `upgrade:filament` passa a apontar para
`filament-v6`. É uma linha em cada lugar, e o `rector.php` já documenta o padrão.

**Instalado**: `filament/upgrade ^5.7` em `require-dev`, com o script `composer upgrade:filament`.

> ⚠️ **Confusão de nomes que vale registrar**: `php artisan filament:upgrade` é OUTRA coisa — o
> comando do próprio Filament que republica assets, que o kit já roda em `post-autoload-dump`. O
> pacote `filament/upgrade` entrega o binário `vendor/bin/filament-v5`. São ferramentas diferentes
> com nomes quase iguais. O script foi chamado de `upgrade:filament` (invertido) justamente para não
> parecer o artisan.

### Consequências

- **Positivas**: nenhuma manutenção de regra de Filament; o caminho oficial fica **instalado**, não
  só documentado.
- **Negativas**: uma dependência de desenvolvimento que só é usada em upgrade de major, e cuja
  versão vai precisar de bump manual quando o Filament 6 sair.

### Referências

- `wikis/qualidade-de-codigo.md` → seção de upgrade
- Packagist: `filament/upgrade` v5.7.6
- `tests/Kit/QualidadeDeCodigoTest.php` — fixa o script e a dependência

---

## ADR-04: Quando Rector e PHPStan discordam, o PHPStan vence

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

ADR-02 mostrou um caso concreto de conflito: `CarbonToDateFacadeRector` desfaz uma correção que o
PHPStan level 7 exigiu. Mas ADR-02 resolveu isso mantendo o Rector fora do gate — o que não protege
o **único** momento em que os sets são ligados: o upgrade de major.

Sem uma regra explícita, quem estiver fazendo o upgrade liga o set, roda `refactor:apply`, e o
PHPStan reprova logo depois num arquivo que ninguém tocou de propósito.

### Decisão

**O PHPStan é o árbitro.** Regra do Rector que conflita com ele é desligada em
`rector.php` → `withSkip()`, com o motivo escrito ali mesmo — nunca o contrário.

Primeira entrada: `RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector::class`.

O skip vale **sempre**, inclusive com sets ligados. É de propósito: o conflito só se manifesta
durante o upgrade, então é exatamente aí que a proteção precisa estar de pé.

### Por que o PHPStan, e não o Rector

Três razões, em ordem de peso:

1. **O PHPStan prova; o Rector opina.** Um erro do PHPStan é uma afirmação verificável sobre tipo.
   Uma regra do Rector é uma preferência de escrita — exceto quando corrige API depreciada, que é o
   caso dos sets de upgrade, e esses não conflitam.
2. **O PHPStan está no gate; o Rector não.** Deixar o Rector vencer criaria um estado em que
   `composer test` reprova depois de um `refactor:apply` bem-sucedido.
3. **O nível está pago.** Level 7, zero erros, sem baseline. Ceder um ponto disso para um rewriter
   de estilo é trocar garantia por preferência.

### Alternativas Consideradas

1. **Ajustar o código para agradar aos dois** — descartada no caso do Carbon: não existe forma de
   escrever isso que satisfaça as duas ferramentas. `Date::now()` é CarbonImmutable neste projeto,
   ponto. A única saída seria remover o `Date::use(CarbonImmutable::class)`, que é uma decisão de
   arquitetura do kit tomada por outros motivos.
2. **Baseline do PHPStan para o que o Rector produz** — descartada: seria esconder um TypeError real
   para agradar a uma preferência de estilo. Inverte o valor das duas ferramentas.

### Consequências

- **Positivas**: o conflito vira config com motivo, no lugar onde ele acontece; upgrade de major não
  produz surpresa.
- **Negativas**: a lista de skips vai crescer, e cada entrada precisa do porquê — senão vira
  baseline disfarçada.
- **Riscos**: alguém acrescentar skip sem justificativa. Mitigado pelo formato do arquivo: cada
  entrada tem bloco de comentário, e o bloco explica com arquivo e linha.

### Referências

- `rector.php` → `withSkip()`
- `tests/Kit/QualidadeDeCodigoTest.php` — "desliga no rector as regras que conflitam com o phpstan"
- Medido: com `LARAVEL_CODE_QUALITY` ligado, as ocorrências da regra vão de **7 para 0**
- Refine: ADR-02
