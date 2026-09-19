<?php

declare(strict_types=1);

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Model;

/**
 * O avatar de quem não enviou foto, desenhado AQUI em vez de buscado lá fora.
 *
 * ## O que isto corrige
 *
 * O provider padrão do Filament é o `UiAvatarsProvider`
 * (`vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10`), e ele devolve
 * uma URL de terceiro:
 *
 *     https://ui-avatars.com/api/?name={iniciais}&format=svg&color=FFFFFF&background={hex}
 *     (vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:29)
 *
 * Como `User::getFilamentAvatarUrl()` devolve `null` quando não há foto
 * (`app/Models/User.php:857-862`), o Filament caía nesse provider — e o NAVEGADOR
 * de cada pessoa passava a requisitar `ui-avatars.com` em toda tela dos três
 * painéis, levando as iniciais na query string e o `Referer` do painel junto.
 *
 * Três coisas vazavam sem que nada no kit dissesse isso: **quem** (as iniciais),
 * **de onde** (a URL do painel, no `Referer`) e **quando** (o horário de cada
 * carga de tela). Num kit que se preocupa com LGPD e que cifra `client_secret` no
 * settings, mandar o nome do usuário para um domínio de terceiro a cada page load
 * é incoerente — e não era decisão de ninguém, era o default do framework que
 * nenhum dos três painéis sobrescrevia.
 *
 * ## Por que SVG embutido, e não arquivo gerado
 *
 * Um `data:` URI não toca disco, não toca rede, não precisa de link simbólico, não
 * precisa de limpeza e não tem o que vazar. O custo é o tamanho do atributo `src`
 * no HTML — algumas centenas de bytes por avatar, contra uma requisição de rede
 * externa por avatar. É mais barato nas duas pontas.
 *
 * ## Por que a aparência não muda
 *
 * O fundo sai da MESMA expressão do provider do vendor (`UiAvatarsProvider.php:27`)
 * e o texto é branco, como o `color=FFFFFF` da URL dele. A troca é de origem, não
 * de visual: quem já usava o kit não vê diferença, e é de propósito — a correção
 * não deve pedir uma decisão de estética para ser aceita.
 *
 * `gray[950]` e não a cor primária: o avatar aparece dentro de `/app/{slug}`, onde
 * a paleta é a da ORGANIZAÇÃO (`AppPanelProvider::bootUsing()`), e um fundo que
 * mudasse de cor por organização tornaria a legibilidade do texto branco uma
 * aposta — `Rose` e `Amber` têm luminâncias muito diferentes. Cinza escuro fixo é
 * contraste garantido em qualquer paleta.
 */
final class AvatarDeIniciais implements AvatarProvider
{
    /**
     * Lado do quadro do SVG. Não é tamanho de exibição — o Filament dimensiona por
     * CSS; é só a caixa de coordenadas de onde as proporções abaixo saem.
     */
    private const LADO = 96;

    public function get(Model $record): string
    {
        $svg = $this->svg($this->iniciais(Filament::getNameForDefaultAvatar($record)));

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Primeira letra dos dois primeiros pedaços do nome.
     *
     * Nome vazio devolve string vazia, e o SVG sai só com o fundo. Devolver algo
     * como "?" seria inventar conteúdo para um caso que o dado não tem — e o
     * provider do vendor também não inventa (`UiAvatarsProvider.php:15-25`).
     */
    private function iniciais(string $nome): string
    {
        return str($nome)
            ->trim()
            ->explode(' ')
            ->filter(fn (string $pedaco): bool => filled($pedaco))
            ->take(2)
            ->map(fn (string $pedaco): string => mb_strtoupper(mb_substr($pedaco, 0, 1)))
            ->implode('');
    }

    /**
     * O SVG, com o texto ESCAPADO.
     *
     * O escape não é zelo decorativo: o nome vem da coluna `users.name`, que é
     * preenchida pela própria pessoa no cadastro e no "Meu perfil". Sem escape, um
     * nome contendo `<`, `>` ou `&` produziria um SVG malformado — e como o
     * resultado vira `data:` URI dentro de um atributo `src`, o estrago seria uma
     * imagem quebrada em toda tela, para todo mundo que a visse.
     */
    private function svg(string $iniciais): string
    {
        /*
         * O `?? Color::Gray[950]` não é zelo: `ColorManager::getColor()` devolve
         * `?array` (`vendor/filament/support/src/Colors/ColorManager.php:104-107`), e uma
         * instalação que registre a própria paleta sem a chave `gray` faria o avatar
         * estourar no render de toda tela. É a mesma guarda do provider do vendor.
         */
        $cinza = FilamentColor::getColor('gray');
        $fundo = Color::convertToHex((string) ($cinza[950] ?? Color::Gray[950]));
        $lado  = self::LADO;
        /*
         * `ENT_SUBSTITUTE` não é enfeite. Passando flags explícitas, a substituição padrão do PHP
         * é DESLIGADA, e `htmlspecialchars()` devolve string VAZIA diante de um byte UTF-8
         * inválido. `users.name` chega por caminhos que não passam pelo formulário de cadastro —
         * nome vindo de provedor social e importação são dois —, e o resultado seria o avatar
         * daquela pessoa virando um quadrado escuro sem letra nenhuma, em toda tela, sem erro.
         * Com a flag, o byte inválido degrada para U+FFFD e as iniciais continuam lá.
         */
        $texto = htmlspecialchars($iniciais, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$lado} {$lado}" width="{$lado}" height="{$lado}">
            <rect width="{$lado}" height="{$lado}" fill="{$fundo}"/>
            <text x="50%" y="50%" dy=".35em" fill="#FFFFFF" font-size="40" font-weight="500" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif">{$texto}</text>
            </svg>
            SVG;
    }
}
