<?php

use App\Settings\ConfiguracoesDoKit;
use Filament\Facades\Filament;

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
it('muda de resposta no mesmo processo, sem remontar o painel', function (string $painel): void {
    $antes = Filament::getPanel($painel)->hasUnsavedChangesAlerts();

    config()->set('kit.alerta_alteracoes_nao_salvas', false);

    expect($antes)->toBeTrue()
        ->and(Filament::getPanel($painel)->hasUnsavedChangesAlerts())->toBeFalse();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * O que a tela grava chega ao painel.
 *
 * `gravarConfiguracao()` escreve direto na tabela e esquece o singleton; `alinharConfiguracoesDoKit()`
 * faz o que o `KitServiceProvider::boot()` faria — com `RefreshDatabase` o boot roda antes das
 * migrations, então quem quer exercitar o alinhamento o chama. Ver o docblock dos dois helpers.
 */
it('leva o valor gravado na tela ate o painel', function (): void {
    gravarConfiguracao('alerta_alteracoes_nao_salvas', false);
    alinharConfiguracoesDoKit();

    expect(config('kit.alerta_alteracoes_nao_salvas'))->toBeFalse()
        ->and(Filament::getPanel('admin')->hasUnsavedChangesAlerts())->toBeFalse();
})->group('kit');

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
it('declara as propriedades novas com a chave de config correspondente', function (string $propriedade, string $chave): void {
    expect(property_exists(ConfiguracoesDoKit::class, $propriedade))->toBeTrue()
        ->and(ConfiguracoesDoKit::mapaDeConfiguracao())->toHaveKey($propriedade, $chave);
})->with([
    'versão do sistema'      => ['versao_do_sistema', 'app.version'],
    'alerta de não salvas'   => ['alerta_alteracoes_nao_salvas', 'kit.alerta_alteracoes_nao_salvas'],
    'exibir versão do kit'   => ['exibir_versao_do_kit', 'kit.exibir_versao'],
])->group('kit');

/**
 * Nenhuma das três é segredo.
 *
 * O caso existe porque a lista `encrypted()` tem um modo de falhar de DUAS caras documentado no
 * docblock dela: nome fora da lista com `addEncrypted` na migration devolve texto cifrado na
 * leitura, e nome dentro da lista sem necessidade cifra o que não precisava. Declarar a ausência
 * é mais barato que descobrir qualquer uma das duas em produção.
 */
it('nao trata as chaves novas como segredo', function (): void {
    expect(ConfiguracoesDoKit::encrypted())
        ->not->toContain('versao_do_sistema')
        ->not->toContain('alerta_alteracoes_nao_salvas')
        ->not->toContain('exibir_versao_do_kit');
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
it('deixa o banco vencer o env na versao do sistema, mesmo vazio', function (): void {
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
it('leva a versao gravada na tela ate a config', function (): void {
    gravarConfiguracao('versao_do_sistema', '9.1.0');
    alinharConfiguracoesDoKit();

    expect(config('app.version'))->toBe('9.1.0');
})->group('kit');
