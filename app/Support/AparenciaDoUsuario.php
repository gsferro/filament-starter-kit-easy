<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * A aparência efetiva da tela corrente: a escolha da pessoa logada, quando vale, ou o padrão.
 *
 * Fonte ÚNICA das duas perguntas da wiki `feat/preferencias-de-aparencia` — *"a escolha pessoal
 * vale aqui?"* e *"qual o valor efetivo de cada preferência?"*. Os quatro consumidores (o render
 * hook de densidade e fonte, a largura do menu, o modo do menu e a cor) leem daqui; uma segunda
 * cópia da regra das duas chaves seria a forma de um deles divergir no primeiro ajuste.
 *
 * ## Quando a escolha pessoal vale (`liberada()`)
 *
 * 1. a chave da instalação está ligada (`kit.aparencia_por_usuario`, RQ-07);
 * 2. há uma pessoa logada (P-10: login, cadastro e recuperação de senha mostram o padrão);
 * 3. a tela não é a pré-visualização das telas de acesso — ela mostra o que um VISITANTE vê, e o
 *    administrador que a abre está logado (CR-01 do step 9);
 * 4. se há organização corrente (`Filament::getTenant()`), a chave DELA está ligada (RQ-09). Com
 *    organizações ligadas, toda tela do `/app` — inclusive o Meu perfil — está dentro de uma; sem
 *    organização corrente (o `/admin`, o `/infra`, o `/app` sem tenancy) vale só a 1 (P-13).
 *
 * ## O que é lido, e por que nada é memoizado
 *
 * `config()` (já sobreposto pelo banco no boot), o usuário da sessão e a organização corrente —
 * os três já carregados. Zero consulta por chamada; memoizar arriscaria servir a resposta de antes
 * de um `actingAs()`, de uma troca de organização ou de uma gravação das configurações no mesmo
 * processo (CT-10, CT-14, CT-37 e CT-40 medem exatamente isso). O custo de repetir foi medido no
 * step 9 (CR-03): ver o `03` da wiki.
 *
 * ## Valor ilegível
 *
 * A coluna `users.aparencia` é JSON e pode chegar com qualquer coisa (edição à mão, versão futura
 * revertida). Toda leitura passa por `escolhas()`, que reduz ao vocabulário: o que não é dele vira
 * `null`, e `null` é "o padrão" (P-12). Nada escrito na coluna chega cru ao CSS.
 */
final class AparenciaDoUsuario
{
    /** A rota da pré-visualização das telas de acesso (`KitServiceProvider`), que mostra o padrão. */
    private const ROTA_DA_PREVIEW = 'kit.preview-das-telas-de-acesso';

    /** A escolha pessoal vale na tela corrente? Ver o docblock da classe. */
    public static function liberada(): bool
    {
        return self::pessoaLiberada() instanceof User;
    }

    /**
     * Qualquer coisa vinda de fora (a coluna, o formulário) → as quatro chaves no vocabulário,
     * `null` onde não há escolha válida.
     *
     * @return array{densidade: ?string, fonte: ?string, cor: ?string, menu: ?string}
     */
    public static function escolhas(mixed $bruto): array
    {
        $bruto = is_array($bruto) ? $bruto : [];

        $texto = static fn (string $chave): string => is_string($bruto[$chave] ?? null) ? $bruto[$chave] : '';

        return [
            'densidade' => DensidadeDoLayout::tryFrom($texto('densidade'))?->value,
            'fonte'     => TamanhoDaFonte::tryFrom($texto('fonte'))?->value,
            'cor'       => in_array($texto('cor'), CustomizadorDaInstalacao::CORES, true) ? $texto('cor') : null,
            'menu'      => ModoDoMenu::tryFrom($texto('menu'))?->value,
        ];
    }

    public static function densidade(): DensidadeDoLayout
    {
        return DensidadeDoLayout::tryFrom(self::escolha('densidade')) ?? DensidadeDoLayout::deConfig();
    }

    public static function tamanhoDaFonte(): TamanhoDaFonte
    {
        return TamanhoDaFonte::tryFrom(self::escolha('fonte')) ?? TamanhoDaFonte::deConfig();
    }

    public static function modoDoMenu(): ModoDoMenu
    {
        return ModoDoMenu::tryFrom(self::escolha('menu')) ?? ModoDoMenu::deConfig();
    }

    /**
     * A paleta da cor que a pessoa escolheu, ou `[]` — o neutro do `FilamentColor::register()`:
     * nada é sobrescrito e a cor da organização ou da instalação sobrevive (P-02).
     *
     * @return array<string, array<int, string>|string>
     */
    public static function paletaDaPessoa(): array
    {
        $cor = self::escolha('cor');

        return $cor === '' ? [] : CorPrimaria::resolver(null, $cor);
    }

    /**
     * A escolha válida da pessoa para uma chave, ou `''` quando não vale ou não há — string vazia, e
     * não `null`, para ir direto ao `tryFrom()` dos enums, que devolve `null` para ela.
     */
    private static function escolha(string $chave): string
    {
        $pessoa = self::pessoaLiberada();

        return $pessoa instanceof User ? (self::escolhas($pessoa->aparencia)[$chave] ?? '') : '';
    }

    /** A pessoa logada, se a escolha dela vale na tela corrente; senão `null`. */
    private static function pessoaLiberada(): ?User
    {
        if (! config('kit.aparencia_por_usuario') || request()->routeIs(self::ROTA_DA_PREVIEW)) {
            return null;
        }

        $pessoa = Filament::auth()->user();

        if (! $pessoa instanceof User) {
            return null;
        }

        $organizacao = Filament::getTenant();

        if ($organizacao === null) {
            return $pessoa;
        }

        // `instanceof` e não `?->`: o model de tenancy é configurável, e um que não seja o do kit
        // não tem a chave — falha fechada.
        return $organizacao instanceof Tenant && $organizacao->aparencia_por_usuario ? $pessoa : null;
    }
}
