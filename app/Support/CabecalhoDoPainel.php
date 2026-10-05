<?php

namespace App\Support;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

/**
 * A marca do topo dos painéis e o bloco do usuário, governados pela aba Identidade das
 * Configurações da aplicação (`kit.cabecalho.*`).
 *
 * As três Closures dos `PanelProvider` (`brandLogo()`, `darkModeBrandLogo()` e o render hook
 * `USER_MENU_BEFORE`) delegam para cá, e o contrato que esta classe sustenta é o RQ-06 da
 * feature: **com tudo desligado, os painéis renderizam o que renderizavam antes** — `marca()`
 * devolve exatamente `IdentidadeDoKit::logo()` e `usuario()` devolve uma string vazia.
 *
 * Ligado qualquer segmento, `marca()` passa a devolver um `HtmlString` — o Filament aceita
 * `Htmlable` no `brandLogo()` (`vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php:getBrandLogo():37`)
 * e, nesse caso, envolve o HTML numa `div.fi-logo` sem acrescentar `<img>`
 * (`vendor/filament/filament/resources/views/components/logo.blade.php:$brandLogo:6`). O par
 * claro/escuro da logo vai dentro da composição, com as classes nativas do swap; por isso
 * `marcaEscura()` devolve `null` com a composição ativa (ADR-01).
 *
 * Sem memoização de propósito: o Filament avalia `brandLogo()` duas vezes por página (barra
 * lateral e topbar) hoje também, e um `once()` em método estático sobrevive de um request para
 * o outro no mesmo processo (a suíte), devolvendo a composição velha depois de a configuração
 * mudar. O custo é o de hoje — os `exists()` de `IdentidadeDoKit` — e nada mais.
 *
 * Só estáticos, como `IdentidadeDoKit` e `AssinaturaDoRodape`: não há estado.
 */
final class CabecalhoDoPainel
{
    /**
     * O que o `brandLogo()` dos três painéis devolve.
     *
     * `null` em `segmentos()` cobre os dois casos em que a marca é a de hoje: nenhum segmento
     * ligado, ou ligado e nada resolvido (P-11 — nunca marca vazia, nunca imagem quebrada).
     */
    public static function marca(): string|Htmlable|null
    {
        $segmentos = self::segmentos();

        if ($segmentos === null) {
            return IdentidadeDoKit::logo();
        }

        return new HtmlString(view('filament.cabecalho-do-painel', $segmentos)->render());
    }

    /**
     * O que o `darkModeBrandLogo()` devolve: `null` com a composição ativa, porque a variante
     * escura já vai dentro dela (`<img class="fi-logo fi-logo-dark">`); do contrário, o de hoje.
     */
    public static function marcaEscura(): ?string
    {
        return self::segmentos() === null ? IdentidadeDoKit::logoEscura() : null;
    }

    /**
     * O que o render hook `USER_MENU_BEFORE` devolve: nome de quem está autenticado e, abaixo,
     * o e-mail ou o papel no painel corrente — à esquerda do avatar, complementando o menu
     * nativo (P-09). Vazio com o bloco desligado ou sem usuário autenticado.
     *
     * O papel vem de `User::papelDoPainel()` + `Papeis::rotulo()`, a mesma fonte do badge do
     * menu do usuário — nunca `getRoleNames()`, que com `permission.teams` ligado some fora do
     * contexto da organização (`.ai/rules/models.md`). Sem papel no painel, a linha de baixo
     * não é renderizada (P-03): é estado normal, não anomalia, por isso sem log.
     */
    public static function usuario(): Htmlable
    {
        if (! (bool) config('kit.cabecalho.usuario', false)) {
            return new HtmlString('');
        }

        $usuario = Filament::auth()->user();

        if (! $usuario instanceof User) {
            return new HtmlString('');
        }

        $detalhe = match (DetalheDoUsuario::coagir(config('kit.cabecalho.detalhe_do_usuario'))) {
            DetalheDoUsuario::Email  => (string) $usuario->email,
            DetalheDoUsuario::Perfil => self::rotuloDoPapel($usuario),
        };

        return new HtmlString(view('filament.usuario-no-cabecalho', [
            'nome'    => (string) $usuario->name,
            'detalhe' => filled($detalhe) ? $detalhe : null,
        ])->render());
    }

    /**
     * Os segmentos da composição, já resolvidos — ou `null` quando a marca deve ser a de hoje.
     *
     * - `projeto`: o nome da aplicação (P-08), se o interruptor estiver ligado.
     * - `painel`: com organização aberta, o nome dela (RQ-02); sem, o rótulo do painel
     *   (`Paineis::rotulo()`), omitido quando o projeto está ligado e tem o mesmo texto (P-01 —
     *   o `/app` usa o nome da aplicação como rótulo).
     * - `logo_clara`/`logo_escura`: a logo da aba Identidade (P-07, P-12), se o interruptor
     *   estiver ligado; a escura só existe com a marca separada.
     *
     * @return array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null
     */
    public static function segmentos(): ?array
    {
        $exibeProjeto = (bool) config('kit.cabecalho.nome_do_projeto', false);
        $exibePainel  = (bool) config('kit.cabecalho.nome_do_painel', false);
        $exibeLogo    = (bool) config('kit.cabecalho.logo_da_marca', false);

        if (! $exibeProjeto && ! $exibePainel && ! $exibeLogo) {
            return null;
        }

        /*
         * Tela pública (login, recuperação de senha, bloqueio) fica com a marca de hoje: a
         * composição é dos painéis autenticados (Fora de Escopo do `00`), e o `brandLogo()`
         * é o mesmo nas duas — a guarda tem de estar aqui, não no provider.
         */
        if (! Filament::auth()->check()) {
            return null;
        }

        $painel  = Paineis::correnteOuPadrao();
        $tenant  = Filament::getTenant();
        $projeto = $exibeProjeto ? (string) config('app.name') : null;

        $nomeDoPainel = null;

        if ($exibePainel) {
            $nomeDoPainel = $tenant instanceof HasName ? $tenant->getFilamentName() : Paineis::rotulo($painel);

            if ($tenant === null && $projeto !== null && $nomeDoPainel === $projeto) {
                $nomeDoPainel = null;
            }
        }

        $logoClara  = $exibeLogo ? IdentidadeDoKit::logo() : null;
        $logoEscura = $logoClara !== null ? IdentidadeDoKit::logoEscura() : null;

        if (blank($projeto) && blank($nomeDoPainel) && $logoClara === null) {
            Log::channel('configuracoes')->warning(
                '[CabecalhoDoPainel@segmentos] Composição pedida sem nenhum segmento resolvido, usando a marca padrão | painel: '.$painel->getId(),
                [
                    'painel'          => $painel->getId(),
                    'nome_do_projeto' => $exibeProjeto,
                    'nome_do_painel'  => $exibePainel,
                    'logo_da_marca'   => $exibeLogo,
                    'tenant_id'       => $tenant?->getKey(),
                ],
            );

            return null;
        }

        return [
            'projeto'     => filled($projeto) ? $projeto : null,
            'painel'      => filled($nomeDoPainel) ? $nomeDoPainel : null,
            'logo_clara'  => $logoClara,
            'logo_escura' => $logoEscura,
        ];
    }

    private static function rotuloDoPapel(User $usuario): ?string
    {
        $contexto = Filament::getTenant()?->getKey();
        $papel    = $usuario->papelDoPainel(
            Paineis::correnteOuPadrao()->getId(),
            is_numeric($contexto) ? (int) $contexto : null,
        );

        return $papel === null ? null : Papeis::rotulo($papel);
    }
}
