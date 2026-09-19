<?php

use App\Settings\ConfiguracoesDoKit;
use Filament\Facades\Filament;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * O aviso antes de sair de um formulário com alteração não salva.
 *
 * O Filament tem o recurso e nasce com ele DESLIGADO
 * (`vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php:9`); o kit nunca
 * ligou, e sair de um formulário preenchido perdia tudo em silêncio. Foi o que dois pacotes de
 * rascunho prometiam resolver, e nenhum dos dois entrou — ver ADR-02 de
 * `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 *
 * O caso que vale mais aqui é o da leitura POR REQUEST: é ele que separa "o toggle governa" de
 * "o toggle grava e não faz nada até o próximo deploy", que é o defeito que
 * `.ai/rules/settings.md` documenta e que `registro_verificar_email` já produziu uma vez.
 */
/**
 * Sem CT no `04`, e mantido: ele mede o default do ARQUIVO chegando aos três painéis, que é um
 * degrau entre CT-16 (o default no arquivo) e CT-14 (o valor gravado na tela chegando ao painel).
 * Nenhum dos dois o cobre — CT-16 para em `config/kit.php` e CT-14 grava antes de perguntar.
 */
it('nasce ligado nos tres paineis', function (string $painel): void {
    expect(Filament::getPanel($painel)->hasUnsavedChangesAlerts())->toBeTrue();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * O caso de falsificabilidade da Closure.
 *
 * Trocar a Closure por um escalar (`->unsavedChangesAlerts(true)`) deixaria o caso acima VERDE e
 * este VERMELHO: o escalar é resolvido quando o `Panel` é construído e congela, enquanto
 * `hasUnsavedChangesAlerts()` avalia a Closure no render (`HasUnsavedChangesAlerts.php:18-21`).
 *
 * Mudar a config **depois** do painel montado e exigir que a resposta mude é a única forma de
 * provar que a tela governa de verdade.
 */
it('[CT-15] muda de resposta no mesmo processo, sem remontar o painel', function (string $painel): void {
    $antes = Filament::getPanel($painel)->hasUnsavedChangesAlerts();

    config()->set('kit.alerta_alteracoes_nao_salvas', false);

    expect($antes)->toBeTrue()
        ->and(Filament::getPanel($painel)->hasUnsavedChangesAlerts())->toBeFalse();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * CT-14 — cada painel obedece ao que foi gravado nas configurações.
 *
 * **A matriz grava pela TELA, e não por `config()->set()`**, e essa é a correção que faz o caso
 * valer: um `Dado` que setasse a config e um `Então` que lesse a mesma config formam tautologia —
 * provariam que `config()` devolve o que `config()` guardou, com o Settings inteiro fora do
 * caminho. Gravando na tabela e alinhando, o caso atravessa propriedade → mapa → alinhamento →
 * painel, que é a cadeia que o requisito compra.
 *
 * As seis células discriminam dois mutantes que nenhuma linha isolada alcança: as linhas `infra` e
 * `app` matam M21 (o alerta ligado só no `/admin`), e as linhas `false` matam M22
 * (`->unsavedChangesAlerts()` sem argumento, fixo em `true`, com a chave não governando nada).
 *
 * É aqui que vive o invariante da premissa nº 4 — *qualquer que seja o valor de fábrica, a chave
 * governa* —, e o `04` declara o desvio: escrevê-lo dentro de CT-16 seria uma ação disfarçada de
 * asserção.
 *
 * `gravarConfiguracao()` escreve direto na tabela e esquece o singleton; `alinharConfiguracoesDoKit()`
 * faz o que o `KitServiceProvider::boot()` faria — com `RefreshDatabase` o boot roda antes das
 * migrations, então quem quer exercitar o alinhamento o chama. Ver o docblock dos dois helpers.
 */
it('[CT-14] leva ate cada painel o valor gravado na tela', function (string $painel, bool $valor): void {
    gravarConfiguracao('alerta_alteracoes_nao_salvas', $valor);
    alinharConfiguracoesDoKit();

    expect(config('kit.alerta_alteracoes_nao_salvas'))->toBe($valor)
        ->and(Filament::getPanel($painel)->hasUnsavedChangesAlerts())->toBe($valor);
})->with([
    'admin ligado'    => ['admin', true],
    'admin desligado' => ['admin', false],
    'infra ligado'    => ['infra', true],
    'infra desligado' => ['infra', false],
    'app ligado'      => ['app', true],
    'app desligado'   => ['app', false],
])->group('kit');

/**
 * CT-16 — o valor de fábrica do alerta, lido do ARQUIVO.
 *
 * `@premissa` (pergunta nº 4): o estado inicial é decisão do plano, não do requisito, e a direção
 * adotada segue falha fechado no sentido do dado do usuário — a ausência do alerta perde digitação
 * e é irreversível, o excesso de alerta custa um clique.
 *
 * `kitConfigCom($chave, null)` e não `config(...)`: com a variável de ambiente presente, o caso
 * mediria o `.env` do processo em vez do default do arquivo, e um default invertido sobreviveria.
 */
it('[CT-16] nasce com o alerta ligado no arquivo de configuracao', function (): void {
    expect(kitConfigCom('KIT_ALERTA_ALTERACOES_NAO_SALVAS', null)['alerta_alteracoes_nao_salvas'])->toBeTrue();
})->group('kit');

/**
 * CT-17 — chave presente e VAZIA cai no default, e "desligado" continua desligando.
 *
 * A linha vazia é a que importa nesta chave, e ela não existe no cenário irmão da versão do kit:
 * numa chave cujo default é `false`, vazio e defeito produzem o mesmo resultado e a partição não
 * discrimina nada. Numa chave cujo default é `true`, vazio é exatamente onde o defeito medido do
 * projeto aparece — `(bool) env('X', true)` devolve `false` para `X=`, e o default nunca entra
 * (M23). O `.env.example` promete o contrário, e `tests/Kit/BooleanoDoEnvTest.php` registra que a
 * "correção óbvia" com `filter_var` reproduz o defeito.
 *
 * `off` está aqui pelo mesmo motivo que na chave irmã: `(bool) 'off'` é `true`, e é o vocabulário
 * que o kit promete e o PHP não conhece.
 */
it('[CT-17] respeita o vocabulario do env na chave do alerta', function (?string $env, bool $esperado): void {
    $valor = kitConfigCom('KIT_ALERTA_ALTERACOES_NAO_SALVAS', $env)['alerta_alteracoes_nao_salvas'];

    expect($valor)->toBe($esperado)->toBeBool();
})->with([
    'presente e vazia' => ['', true],
    'off'              => ['off', false],
    'false'            => ['false', false],
    'zero'             => ['0', false],
    'true'             => ['true', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| O contrato de três lugares do Settings
|--------------------------------------------------------------------------
*/

/**
 * As três propriedades desta entrega cumprem o contrato de `App\Settings\ConfiguracoesDoKit`:
 * a propriedade, a linha no `mapaDeConfiguracao()` e o `add()` numa migration.
 *
 * O caso cobre os dois primeiros; o terceiro é coberto por `ConfiguracoesDoKitTest`, que compara
 * o mapa com as linhas realmente gravadas — propriedade sem migration estoura `MissingSettings`
 * no alinhamento, e é lá que isso aparece.
 */
it('[CT-18] declara as propriedades novas com a chave de config correspondente', function (string $propriedade, string $chave, mixed $valor): void {
    expect(property_exists(ConfiguracoesDoKit::class, $propriedade))->toBeTrue()
        ->and(ConfiguracoesDoKit::mapaDeConfiguracao())->toHaveKey($propriedade, $chave);

    /*
     * A metade que a versão anterior deste caso não tinha, e sem a qual ele ficava verde com o
     * `mapaDeConfiguracao()` VAZIO: cada linha fecha na chave de configuração de verdade, depois
     * do alinhamento. É o defeito que `.ai/rules/settings.md` chama de "o campo aparece, grava, e
     * não governa nada" (M25) — e a presença da linha semeada, que mata M26 (migration esquecida,
     * com o boot avisando em todo request).
     *
     * Os valores DIFEREM do que o `phpunit.xml` e o arquivo forçam, de propósito: `false` contra o
     * alerta que nasce `true`, `true` contra a exibição que nasce `false`, `"2.4.0"` contra o
     * `APP_VERSION=""`. Um alinhamento que não fizesse nada reprovaria nas três.
     */
    expect(SettingsProperty::query()->where('group', 'kit')->where('name', $propriedade)->exists())
        ->toBeTrue("a propriedade {$propriedade} não tem linha semeada: o boot vai avisar em todo request");

    gravarConfiguracao($propriedade, $valor);
    alinharConfiguracoesDoKit();

    expect(config($chave))->toBe($valor);
})->with([
    'versão do sistema'      => ['versao_do_sistema', 'app.version', '2.4.0'],
    'alerta de não salvas'   => ['alerta_alteracoes_nao_salvas', 'kit.alerta_alteracoes_nao_salvas', false],
    'exibir versão do kit'   => ['exibir_versao_do_kit', 'kit.exibir_versao', true],
])->group('kit');

/**
 * CT-39 — a migration semeia cada propriedade com o valor de fábrica que o kit promete.
 *
 * CT-07 e CT-16 leem `config/kit.php` e provam o default do ARQUIVO — mas quem governa a partir do
 * primeiro boot real é a **linha semeada no banco**, que o alinhamento sobrepõe por cima. Uma
 * migration que semeasse a exibição da versão do kit como ligada faria toda instalação nascer
 * anunciando o kit no rodapé, contra a decisão explícita do Adendo 2, e passaria em CT-07, CT-16 e
 * na versão antiga de CT-18 (M56).
 *
 * A terceira asserção só não é vácua porque o `Dado` fixa a versão do ambiente antes de refazer as
 * migrations: o `phpunit.xml` força `APP_VERSION=""`, e medir "semeou o valor do ambiente" num
 * ambiente vazio não distingue implementação nenhuma.
 */
it('[CT-39] semeia as tres propriedades novas com o valor de fabrica prometido', function (): void {
    config()->set('app.version', '9.9.9');
    config()->set('kit.exibir_versao', false);
    config()->set('kit.alerta_alteracoes_nao_salvas', true);

    $migrations = [
        require base_path('database/settings/2026_09_18_100000_add_alerta_alteracoes_nao_salvas_to_kit_settings.php'),
        require base_path('database/settings/2026_09_18_110000_add_versao_do_sistema_to_kit_settings.php'),
    ];

    foreach (array_reverse($migrations) as $migration) {
        $migration->down();
    }

    foreach (['versao_do_sistema', 'alerta_alteracoes_nao_salvas', 'exibir_versao_do_kit'] as $propriedade) {
        expect(SettingsProperty::query()->where('group', 'kit')->where('name', $propriedade)->exists())->toBeFalse();
    }

    foreach ($migrations as $migration) {
        $migration->up();
    }

    expect(configuracaoGravada('exibir_versao_do_kit'))->toBeFalse()
        ->and(configuracaoGravada('alerta_alteracoes_nao_salvas'))->toBeTrue()
        ->and(configuracaoGravada('versao_do_sistema'))->toBe('9.9.9');
})->group('kit');

/**
 * Nenhuma das três é segredo.
 *
 * O caso existe porque a lista `encrypted()` tem um modo de falhar de DUAS caras documentado no
 * docblock dela: nome fora da lista com `addEncrypted` na migration devolve texto cifrado na
 * leitura, e nome dentro da lista sem necessidade cifra o que não precisava. Declarar a ausência
 * é mais barato que descobrir qualquer uma das duas em produção.
 */
it('[CT-19] nao trata as chaves novas como segredo', function (): void {
    expect(ConfiguracoesDoKit::encrypted())
        ->not->toContain('versao_do_sistema')
        ->not->toContain('alerta_alteracoes_nao_salvas')
        ->not->toContain('exibir_versao_do_kit');

    /*
     * A segunda metade do `Então` de CT-19, e ela não é redundante: a lista é uma das duas caras do
     * defeito. `configuracaoGravada()` lê o `payload` CRU, sem passar pelo decifrador do spatie —
     * quem pergunta se o valor está cifrado não pode perguntar para quem decifra. Um
     * `addEncrypted()` na migration, com o nome fora da lista, devolveria criptograma aqui (M27).
     */
    gravarConfiguracao('versao_do_sistema', '2.4.0');

    expect(configuracaoGravada('versao_do_sistema'))->toBe('2.4.0');
})->group('kit');

/*
|--------------------------------------------------------------------------
| A armadilha do `.env` depois da instalação
|--------------------------------------------------------------------------
*/

/**
 * O banco vence o `.env` **inclusive quando o banco está vazio**.
 *
 * Achado pelo `/code-review` do diff, e a consequência não era óbvia: `aplicarNaConfig()` faz
 * `$novo[$chave] = $this->{$propriedade}` sem guarda de nulo
 * (`app/Settings/ConfiguracoesDoKit.php:483-484`), e isso é contrato — há caso próprio fixando
 * (`ConfiguracoesDoKitTest`, "zera a chave de configuracao quando a propriedade e limpada").
 *
 * Para `versao_do_sistema` isso significa que, num projeto já instalado com o campo em branco,
 * escrever `APP_VERSION=2.4.1` no `.env` **não** muda o rodapé. A primeira redação da
 * documentação mandava o operador fazer exatamente isso no script de deploy, e teria produzido um
 * rodapé permanentemente vazio sem ninguém entender por quê.
 *
 * O caso fixa o comportamento — que é o do kit inteiro, não uma exceção — para que a próxima
 * pessoa encontre a armadilha aqui, executável, em vez de em produção.
 */
it('[CT-38] deixa o banco vencer o env na versao do sistema, mesmo vazio', function (): void {
    config()->set('app.version', '2.4.1');

    gravarConfiguracao('versao_do_sistema', null);
    alinharConfiguracoesDoKit();

    expect(config('app.version'))->toBeNull();
})->group('kit');

/**
 * E o contrapositivo: o valor gravado chega ao rodapé.
 *
 * Sem este par, o caso acima sozinho ficaria verde numa implementação que simplesmente ignorasse
 * a propriedade — o que também deixaria o rodapé vazio, por outro motivo.
 */
it('[CT-38] leva a versao gravada na tela ate a config', function (): void {
    gravarConfiguracao('versao_do_sistema', '9.1.0');
    alinharConfiguracoesDoKit();

    expect(config('app.version'))->toBe('9.1.0');
})->group('kit');
