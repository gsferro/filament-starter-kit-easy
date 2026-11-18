<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

/*
 * Configuração do `composer-unused` (`composer deps:unused`, e o relatório do CI).
 *
 * Só entra aqui pacote que o kit USA por um caminho que a ferramenta não enxerga — ela procura
 * símbolos do pacote no código, e estes cinco trabalham por auto-discovery, config, daemon ou
 * comando. Pacote realmente sem uso NÃO entra: a lista existe para o relatório acusar só o que é
 * real (wiki `feat/verificacao-de-dependencias`, P-03). A triagem completa, com a evidência de cada
 * um, está em `wikis/qualidade-de-codigo.md`, seção "Dependências declaradas × usadas".
 */
return static function (Configuration $config): Configuration {
    // Auto-discovery do provider registra middleware e o overlay de erros nos painéis.
    $config->addNamedFilter(NamedFilter::fromString('anselmokossa/filament-sentinel'));

    // Recorders em `config/pulse.php`, dashboard publicado e o daemon `pulse:check` do docker-compose.
    $config->addNamedFilter(NamedFilter::fromString('laravel/pulse'));

    // Serviço opt-in: `reverb:start` no docker-compose; os painéis trocam o polling por ele.
    $config->addNamedFilter(NamedFilter::fromString('laravel/reverb'));

    // Auto-discovery do provider intercepta a compilação do Blade.
    $config->addNamedFilter(NamedFilter::fromString('livewire/blaze'));

    // `config/backup.php` e o `backup:run` da Central de Comandos (`config/command-center.php`).
    $config->addNamedFilter(NamedFilter::fromString('spatie/laravel-backup'));

    return $config;
};
