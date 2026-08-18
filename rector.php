<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;

/*
|--------------------------------------------------------------------------
| Rector — ferramenta de UPGRADE, não gate de lint
|--------------------------------------------------------------------------
| Este arquivo nasce SEM NENHUM SET LIGADO, e isso é deliberado.
|
| ## O que ele é
|
| A ferramenta que você liga quando vai subir de major — Laravel 13 → 14, PHP
| 8.4 → 8.5. Descomente o set do destino, rode `composer refactor:preview`, leia
| o diff, e só então `composer refactor:apply`.
|
| ## O que ele NÃO é
|
| Não entra em `composer test`. O kit tem três gates que rodam sempre:
|
|   pint       → estilo      (corrige)
|   phpstan    → tipos       (reporta, level 7)
|   filacheck  → API Filament (reporta)
|
| O Rector é o único dos quatro que REESCREVE semântica, e foi medido antes de
| ficar de fora: com os sets de qualidade do Laravel ligados, ele mudaria
| **103 arquivos** deste projeto. Os três maiores motivos são opinião de estilo,
| não correção:
|
|   35  User::find()  →  User::query()->find()
|   26  ": void" em closure
|   21  app()  →  resolve()
|
| Num kit cujo produto é o código-exemplo legível, `User::find()` e `app()` são o
| idioma que o ecossistema inteiro lê sem parar.
|
| ## E um deles reintroduz defeito
|
| `CarbonToDateFacadeRector` propõe, no InfraPanelProvider:
|
|     - Carbon::now()->subDays(...)
|     + Date::now()->subDays(...)
|
| Isso QUEBRA: `now()` é `Date::now()` (helpers.php:623), o kit faz
| `Date::use(CarbonImmutable::class)` (KitServiceProvider:57), e o
| `modelPruneInterval()` do filament-exceptions exige Carbon MUTÁVEL. O PHPStan
| level 7 já reportou exatamente esse erro nesta base — o `Carbon::now()`
| explícito é a correção, e o Rector a desfaria.
|
| Ferramenta de qualidade que reverte a correção de outra não é gate, é disputa.
|
| Ver `wikis/qualidade-de-codigo.md` e as ADRs em
| `wikis/specs/feature/v1-enriquecimento-kit/rector/02-decisoes-arquiteturais.md`.
|
| ## Filament
|
| Não existe regra de Filament aqui — o `driftingly/rector-laravel` cobre Laravel
| e só. O Filament distribui a PRÓPRIA ferramenta baseada em Rector:
| `composer require filament/upgrade --dev` + `vendor/bin/filament-vN`. Use ela,
| não regras nossas: quem escreve as regras é quem quebra a API.
*/
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        // Migrations publicadas por pacote são stub de vendor, como no phpstan.neon.
        __DIR__.'/database/migrations/*_create_health_tables.php',
        __DIR__.'/database/migrations/*_create_breezy_sessions_table.php',
        __DIR__.'/database/migrations/*_create_pulse_tables.php',

        /*
        |----------------------------------------------------------------------
        | Regras que CONFLITAM com o PHPStan — o PHPStan vence
        |----------------------------------------------------------------------
        | Regra do projeto: quando as duas ferramentas discordam, quem manda é o
        | PHPStan (level 7, zero erros, sem baseline). O Rector é desligado aqui,
        | com o motivo, em vez de a correção do PHPStan ser desfeita a cada
        | `refactor:apply`.
        |
        | Estas exclusões valem SEMPRE — inclusive durante um upgrade de major,
        | que é o único momento em que os sets são ligados. É justamente aí que o
        | conflito apareceria sem ninguém esperando.
        */

        /*
         * `Carbon::now()` → `Date::now()`.
         *
         * Quebra neste projeto, por três fatos:
         *   1. `now()` É `Date::now()`      — Illuminate/Foundation/helpers.php:623
         *   2. o kit faz `Date::use(CarbonImmutable::class)` — KitServiceProvider.php:57
         *   3. `FilamentExceptionsPlugin::modelPruneInterval()` exige Carbon MUTÁVEL
         *
         * Ou seja: `Date::now()` devolve CarbonImmutable e estoura TypeError. O PHPStan
         * level 7 já reportou exatamente isso quando o código usava `now()`, e o
         * `Carbon::now()` explícito do InfraPanelProvider é a correção — que esta regra
         * desfaria.
         */
        CarbonToDateFacadeRector::class,
    ])
    /*
     * Cache fora da raiz. Sem isto o Rector cria `.rector.cache` no diretório do
     * projeto, que é sujeira num kit distribuído por `create-project`.
     *
     * Caminho montado à mão, e não com `storage_path()`: o Rector avalia este arquivo
     * SEM bootar a aplicação, então os helpers do Laravel não existem aqui —
     * `storage_path()` estoura `Call to undefined method Container::storagePath()`.
     */
    ->withCache(__DIR__.'/storage/framework/cache/rector');

/*
|--------------------------------------------------------------------------
| Sets — ligue APENAS no momento do upgrade
|--------------------------------------------------------------------------
| Acrescente ao `RectorConfig::configure()` acima, rode o preview, leia o diff
| inteiro e desligue de novo quando terminar.
|
| ### Subindo de major do Laravel
|
|     use RectorLaravel\Set\LaravelSetList;
|
|     ->withSets([
|         LaravelSetList::LARAVEL_130,   // 12 → 13. Troque para o major de destino.
|     ])
|
| Também existe a escada acumulada, para saltos de mais de uma versão:
|
|     use RectorLaravel\Set\LaravelLevelSetList;
|
|     ->withSets([LaravelLevelSetList::UP_TO_LARAVEL_130])
|
| ### Subindo de major do PHP
|
|     ->withPhpSets(php84: true)   // troque para o alvo
|
| ### Os sets de QUALIDADE
|
| LARAVEL_CODE_QUALITY, LARAVEL_COLLECTION, LARAVEL_IF_HELPERS,
| LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER, LARAVEL_TYPE_DECLARATIONS e
| companhia existem e funcionam — mas são os que produzem os 103 arquivos e o
| conflito do Carbon descritos acima. Se for usar, use uma vez, em branch
| separada, revisando arquivo a arquivo. Nunca no gate.
|
| `tests/Kit/QualidadeDeCodigoTest.php` falha se algum deles for ligado aqui em
| definitivo — a trava existe para que a decisão seja consciente, não acidental.
*/
