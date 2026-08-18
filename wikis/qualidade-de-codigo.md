# Qualidade de código: quatro ferramentas, quatro eixos

O kit não tem "um linter". Tem quatro ferramentas que olham coisas diferentes, e a diferença entre
elas é o que decide qual roda sempre e qual roda quando você chama.

| Ferramenta | Eixo | O que faz ao achar | Roda |
|---|---|---|---|
| **Pint** | estilo | **corrige** | sempre (gate) |
| **PHPStan** + larastan | tipos e correção | reporta | sempre (gate), **level 7** |
| **FilaCheck** | API do Filament | reporta | sempre (gate) |
| **Rector** | reescrita de código | **muda semântica** | **sob demanda** |

```bash
composer test              # config:clear + pint + phpstan + filacheck + a suíte
composer lint              # pint, corrigindo
composer lint:check        # pint, só conferindo
composer types:check       # phpstan
composer filament:check    # filacheck
composer refactor:preview  # rector --dry-run   ← FORA do composer test
composer refactor:apply    # rector             ← FORA do composer test
```

---

## Pint — estilo

Preset do projeto em `pint.json`. Corrige em vez de reclamar, então não existe discussão de
formatação em code review.

Rode `vendor/bin/pint --dirty` antes de commitar. O `composer test` roda `--test`, que só confere.

## PHPStan — tipos, no level 7

`phpstan.neon`, com `larastan`. Analisa `app`, `bootstrap/app.php`, `config`, `database` e `routes`.

**Level 7, com zero erros e sem baseline.** A maioria dos projetos Laravel para no 5 ou 6. O que o 7
cobra a mais:

- **nulo não checado** — `Filament::getCurrentPanel()` é `?Panel`, `auth()->user()` é `?User`
- **tipo largo do vendor** entrando no seu código — `session()` é `mixed`, `env()` é `bool|string`
- **`list<T>` vs `array<int,T>`** — `filter()` e `map()` preservam chave, e um array com buraco
  entregue onde se esperava lista vira objeto no `json_encode`

Subir de 6 para 7 expôs **29 erros reais**, um deles um `Convite|null` com método chamado direto.

Existe **uma** exceção em `ignoreErrors`, para o macro `simpleLightbox()` — que é resolvido em
runtime e nenhuma análise estática alcança. Ela vem com o motivo, as duas alternativas testadas e
descartadas, e o teste que cobre o ponto de verdade. **Esse é o padrão**: exceção com justificativa e
com o teste que a substitui, nunca `@phpstan-ignore` solto.

## FilaCheck — a API do Filament

`laraveldaily/filacheck`, 17 regras que Pint e PHPStan não têm como ter: método depreciado da API do
Filament, namespace errado de action, chamada que mudou entre versões.

Ao ser adotado, achou **7 problemas preexistentes** no próprio kit — seis métodos de teste
depreciados e um `ImageColumn::size()`.

---

## Rector — e por que ele **não** está no gate

`rector/rector` + `driftingly/rector-laravel`, ambos `require-dev`. O `rector.php` da raiz nasce
**sem nenhum set ligado**.

### Para que serve

Upgrade de major. Laravel 13 → 14, PHP 8.4 → 8.5. Você liga o set do destino, roda o preview, lê o
diff, aplica, desliga.

```bash
# 1. edite rector.php e descomente o set do destino
# 2. veja o que ele propõe — leia o diff inteiro
composer refactor:preview
# 3. aplique
composer refactor:apply
# 4. rode a suíte
composer test
# 5. desligue o set de novo
```

### Por que ele fica fora do `composer test`

Foi **medido**, não opinado. Com os sets de qualidade do Laravel ligados, o Rector reescreveria
**103 arquivos** deste projeto. Os três maiores motivos:

| Regra | Arquivos | O que propõe |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | `User::find()` → `User::query()->find()` |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | `: void` em closure |
| `AppToResolveRector` | 21 | `app()` → `resolve()` |

São opinião de estilo, não correção. Num kit cujo produto **é o código-exemplo legível**,
`User::find()` e `app()` são o idioma que qualquer pessoa do ecossistema lê sem parar.

### O argumento que fecha a questão

`CarbonToDateFacadeRector` propõe, no `InfraPanelProvider`:

```diff
- Carbon::now()->subDays(...)
+ Date::now()->subDays(...)
```

E isso **quebra**, por três fatos verificáveis:

1. `now()` **é** `Date::now()` — `Illuminate/Foundation/helpers.php:623`
2. O kit faz `Date::use(CarbonImmutable::class)` — `KitServiceProvider.php:57`
3. `FilamentExceptionsPlugin::modelPruneInterval()` exige `Carbon` **mutável**

O PHPStan level 7 **já reportou exatamente esse erro** quando o código usava `now()`. O
`Carbon::now()` explícito é a correção — e o Rector a desfaria.

> **Ferramenta de qualidade que reverte a correção de outra não é gate. É disputa** — e o build
> passaria a depender de qual das duas rodou por último.

`tests/Kit/QualidadeDeCodigoTest.php` fixa isso: falha se o Rector entrar no `composer test`, se um
set de qualidade for ligado em definitivo, ou se o cache voltar para a raiz.

### Quando as duas discordam, o PHPStan vence

Tirar o Rector do gate resolve o dia a dia — mas não protege o **único** momento em que os sets são
ligados, que é o upgrade de major. É justamente aí que o conflito apareceria, sem ninguém esperando.

Por isso a regra é explícita e mora no `rector.php`:

```php
->withSkip([
    // Regras que conflitam com o PHPStan — o PHPStan vence.
    RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector::class,
])
```

**O skip vale sempre**, inclusive com sets ligados. Medido: com `LARAVEL_CODE_QUALITY` ligado, as
ocorrências dessa regra vão de **7 para 0**.

Por que o PHPStan é o árbitro, em ordem de peso:

1. **O PHPStan prova; o Rector opina.** Um erro do PHPStan é afirmação verificável sobre tipo. Uma
   regra de qualidade do Rector é preferência de escrita.
2. **O PHPStan está no gate; o Rector não.** Deixar o Rector vencer criaria um estado em que
   `composer test` reprova logo depois de um `refactor:apply` bem-sucedido.
3. **O nível está pago** — level 7, zero erros, sem baseline. Ceder isso para um rewriter de estilo
   é trocar garantia por preferência.

**Ao acrescentar um skip**: escreva o motivo com arquivo e linha, como o do Carbon. Skip sem
justificativa é baseline disfarçada.

### Upgrade de Filament é outra ferramenta

**Não existe regra de Filament no `driftingly/rector-laravel`** — ele cobre Laravel, e só. Busca por
"filament" no pacote: zero ocorrências.

Não é lacuna: o Filament distribui a **própria** ferramenta, também baseada em Rector — e ela **já
vem instalada** no kit (`filament/upgrade`, `require-dev`).

```bash
composer upgrade:filament     # = vendor/bin/filament-v5
```

Ela é mantida em lockstep com o framework — quem escreve as regras é quem quebra a API. Escrever
regras de Filament dentro do kit seria competir com a ferramenta oficial, e é o oposto da regra de
não reimplementar vendor.

> ⚠️ **Dois nomes quase iguais, ferramentas diferentes.**
>
> | Comando | O que é |
> |---|---|
> | `php artisan filament:upgrade` | comando do **próprio Filament** que republica assets. O kit já roda em `post-autoload-dump` — nada a ver com upgrade de major |
> | `composer upgrade:filament` | o binário `filament-v5` do pacote `filament/upgrade`, que **reescreve seu código** para o major novo |
>
> O script foi nomeado invertido (`upgrade:filament`) de propósito, para não ser confundido com o
> artisan.

**Quando o Filament 6 sair**: `composer require filament/upgrade:^6.0 --dev` e aponte o script para
`filament-v6`. Duas linhas.

---

## Ordem de leitura ao entrar no projeto

1. `composer test` verde é o contrato mínimo
2. `vendor/bin/pint --dirty` antes de commitar
3. PHPStan reclamou? Corrija na **origem** — não com `@phpstan-ignore`
4. Vai subir de major? Aí, e só aí, o Rector

Ver também: [convencoes.md](convencoes.md) para as armadilhas já resolvidas, e
[pacotes.md](pacotes.md) para quem é dono de cada tela.
