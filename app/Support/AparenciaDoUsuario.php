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
 * 3. se a tela é de uma organização (`Filament::getTenant()`), a chave DELA está ligada (RQ-09).
 *    Tela do `/app` sem organização no endereço — o Meu perfil — segue só a 1 (P-13).
 *
 * ## O que é lido, e por que nada é memoizado
 *
 * `config()` (já sobreposto pelo banco no boot), o usuário da sessão e a organização corrente —
 * os três já carregados. Zero consulta por chamada; memoizar só arriscaria servir a resposta de
 * antes de um `actingAs()`/troca de organização no mesmo processo.
 *
 * ## Valor ilegível
 *
 * A coluna `users.aparencia` é JSON e pode chegar com qualquer coisa (edição à mão, versão futura
 * revertida). Toda leitura passa por `escolhas()`, que reduz ao vocabulário: o que não é dele vira
 * `null`, e `null` é "o padrão" (P-12). Nada escrito na coluna chega cru ao CSS.
 */
final class AparenciaDoUsuario
{
    /** As quatro chaves da coluna `users.aparencia`, na ordem da tela. */
    public const CHAVES = ['densidade', 'fonte', 'cor', 'menu'];

    /** A escolha pessoal vale na tela corrente? Ver o docblock da classe. */
    public static function liberada(): bool
    {
        if (! config('kit.aparencia_por_usuario')) {
            return false;
        }

        if (! self::pessoa() instanceof User) {
            return false;
        }

        $organizacao = Filament::getTenant();

        if ($organizacao === null) {
            return true;
        }

        // `instanceof` e não `?->`: o model de tenancy é configurável, e um que não seja o do kit
        // não tem a chave — falha fechada.
        return $organizacao instanceof Tenant && $organizacao->aparencia_por_usuario;
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

        $texto = static fn (string $chave): ?string => is_string($bruto[$chave] ?? null) ? $bruto[$chave] : null;

        $cor = $texto('cor');

        return [
            'densidade' => DensidadeDoLayout::tryFrom((string) $texto('densidade'))?->value,
            'fonte'     => TamanhoDaFonte::tryFrom((string) $texto('fonte'))?->value,
            'cor'       => in_array($cor, CustomizadorDaInstalacao::CORES, true) ? $cor : null,
            'menu'      => ModoDoMenu::tryFrom((string) $texto('menu'))?->value,
        ];
    }

    public static function densidade(): DensidadeDoLayout
    {
        return DensidadeDoLayout::tryFrom((string) self::escolha('densidade')) ?? DensidadeDoLayout::deConfig();
    }

    public static function tamanhoDaFonte(): TamanhoDaFonte
    {
        return TamanhoDaFonte::tryFrom((string) self::escolha('fonte')) ?? TamanhoDaFonte::deConfig();
    }

    public static function modoDoMenu(): ModoDoMenu
    {
        return ModoDoMenu::tryFrom((string) self::escolha('menu')) ?? ModoDoMenu::deConfig();
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

        return $cor === null ? [] : CorPrimaria::resolver(null, $cor);
    }

    /** A escolha válida da pessoa para uma chave, ou `null` quando não vale ou não há. */
    private static function escolha(string $chave): ?string
    {
        if (! self::liberada()) {
            return null;
        }

        $pessoa = self::pessoa();

        return $pessoa instanceof User ? self::escolhas($pessoa->aparencia)[$chave] : null;
    }

    private static function pessoa(): ?User
    {
        $pessoa = Filament::auth()->user();

        return $pessoa instanceof User ? $pessoa : null;
    }
}
