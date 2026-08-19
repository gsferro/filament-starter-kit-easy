<?php

/**
 * Onde cada cache do Laravel roda no Docker — e quais NÃO podem rodar.
 *
 * Três decisões medidas, e as três somem sem deixar rastro se alguém "otimizar":
 *
 *   view:cache        boot do container  (39s; storage/ é volume e encobre o build)
 *   filament:optimize build da imagem    (14ms; grava em bootstrap/cache, não é volume)
 *   config/route      NUNCA              (quebram .env em runtime e tenancy)
 *
 * O caminho batido é `php artisan optimize`, que traz os dois proibidos junto. Este
 * arquivo existe para que trocar por ele seja uma decisão consciente, não um atalho.
 *
 * Ver as ADRs em wikis/specs/feature/v1-enriquecimento-kit/cache-de-views-no-docker/.
 */
beforeEach(function (): void {
    $this->compose    = (string) file_get_contents(base_path('docker-compose.yml'));
    $this->dockerfile = (string) file_get_contents(base_path('Dockerfile.laravel'));

    /*
     * As asserções de AUSÊNCIA rodam sobre o arquivo SEM comentário.
     *
     * Os dois arquivos citam `config:cache`, `route:cache` e `view:cache` nos comentários,
     * de propósito — é lá que está escrito por que cada um roda onde roda e por que os
     * proibidos são proibidos. Uma asserção sobre o texto cru puniria exatamente a
     * documentação que torna a decisão utilizável.
     *
     * Citar não é executar. Mesma distinção do `rector.php` em QualidadeDeCodigoTest.
     */
    $semComentario = static fn (string $arquivo): string => implode("\n", array_filter(
        explode("\n", $arquivo),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    ));

    $this->composeExecutavel    = $semComentario($this->compose);
    $this->dockerfileExecutavel = $semComentario($this->dockerfile);
});

it('compila as views no boot do container', function (): void {
    expect($this->compose)->toContain('php artisan view:cache');
})->group('kit');

/**
 * O `&&` é o que transforma falha de compilação em falha de boot.
 *
 * Com `;`, o php-fpm subiria mesmo com o `view:cache` quebrado, e o container serviria
 * requests lentos parecendo saudável — degradação silenciosa, que é o modo de falha que
 * este kit evita por padrão.
 */
it('impede o php-fpm de subir se a compilação falhar', function (): void {
    expect($this->compose)->toMatch('/view:cache\s*&&\s*\n?\s*php-fpm/');
})->group('kit');

/**
 * O healthcheck precisa tolerar os ~39s em que o php-fpm ainda não existe.
 *
 * Sem `start_period`, o Docker conta falha desde o primeiro intervalo: o container nasce
 * `unhealthy`, e quem depende dele por `service_healthy` nunca sobe.
 */
it('dá folga ao healthcheck durante a compilação', function (): void {
    expect($this->compose)->toContain('start_period: 90s');
})->group('kit');

/**
 * `view:cache` no Dockerfile seria inócuo — e a inocuidade é o perigo.
 *
 * Ele grava em `storage/framework/views`, e `storage/` é o volume `app-storage` montado
 * por cima do diretório da imagem. Docker copia o conteúdo da imagem para um volume
 * VAZIO na primeira criação, então funcionaria no primeiro teste e sumiria assim que o
 * volume já existisse de uma subida anterior.
 */
it('não tenta compilar views no build da imagem', function (): void {
    expect($this->dockerfileExecutavel)->not->toContain('view:cache');
})->group('kit');

/**
 * `filament:optimize` é o inverso: grava em `bootstrap/cache`, que não é volume.
 */
it('assa o índice de componentes do filament na imagem', function (): void {
    expect($this->dockerfileExecutavel)->toContain('php artisan filament:optimize')
        ->and($this->composeExecutavel)->not->toContain('filament:optimize');
})->group('kit');

/**
 * Os dois que quebram o kit.
 *
 * `config:cache` faz o Laravel parar de ler o `.env` — que o compose monta em runtime, e
 * que o Dockerfile declara como a origem da APP_KEY.
 *
 * `route:cache` congela o estado de tenancy: com `kit.tenancy.enabled` o painel `app`
 * deixa de ser `/app` e passa a `/app/{tenant}`. Assado, um `kit:tenancy` posterior muda
 * o config e não as rotas — e o sintoma é 404 numa tela que deveria existir.
 *
 * `php artisan optimize` está na lista porque é o atalho que traz os dois.
 */
it('mantém fora os caches que quebram env em runtime e tenancy', function (string $proibido): void {
    expect($this->composeExecutavel)->not->toContain($proibido)
        ->and($this->dockerfileExecutavel)->not->toContain($proibido);
})->with([
    'config:cache',
    'route:cache',
    'artisan optimize',
])->group('kit');

/**
 * A instalação local NÃO cacheia view.
 *
 * `kit:install` roda na máquina de quem está desenvolvendo, e view cacheada obriga
 * `view:clear` a cada edição de Blade — contra o loop que o kit protege com
 * `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`.
 */
it('não cacheia views na instalação local', function (): void {
    expect((string) file_get_contents(base_path('app/Console/Commands/KitInstall.php')))
        ->not->toContain('view:cache');
})->group('kit');
