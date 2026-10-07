<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasName;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use WeakMap;

/**
 * A marca do topo dos painéis e o bloco do usuário, governados pela aba Identidade das
 * Configurações da aplicação (`kit.cabecalho.*`).
 *
 * As três Closures dos `PanelProvider` (`brandLogo()`, `darkModeBrandLogo()` e o render hook
 * `USER_MENU_BEFORE`) delegam para cá, e o contrato que esta classe sustenta é o RQ-06 da
 * feature: **com tudo desligado, os painéis renderizam o que renderizavam antes** — `marca()`
 * devolve exatamente `IdentidadeDoKit::logo()` e `usuario()` devolve uma string vazia — **para
 * quem não tem organização aberta com logo própria**. No `/app` com organização aberta, a logo
 * (a marca simples e a da composição) é a da organização, clara e escura, com queda por variante
 * para a da instalação: reverte a P-12 do cabeçalho só para esse painel (wiki
 * `fix/logo-dark-do-tenant`, Adendo 1). A regra do par vive em `IdentidadeDoKit::logosPara()`,
 * a mesma da tela de bloqueio; fora do `/app` (`/admin`, `/infra`) a organização é `null` e o
 * par é o da instalação, sempre — mostrar logo de cliente ao administrador seria vazamento.
 *
 * Ligado qualquer segmento, `marca()` passa a devolver um `HtmlString` — o Filament aceita
 * `Htmlable` no `brandLogo()` (`vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php:getBrandLogo():37`)
 * e, nesse caso, envolve o HTML numa `div.fi-logo` sem acrescentar `<img>`
 * (`vendor/filament/filament/resources/views/components/logo.blade.php:$brandLogo:6`, ramo
 * `instanceof Htmlable`). O par claro/escuro da logo vai dentro da composição, com as classes
 * nativas do swap; por isso `marcaEscura()` devolve `null` com a composição ativa (ADR-01).
 *
 * ## Custo e memo
 *
 * O Filament desenha a marca duas vezes por página (barra lateral e topbar) e, em cada uma,
 * chama `getBrandLogo()` e `getDarkModeBrandLogo()`. Sem memo, `segmentos()` rodaria quatro
 * vezes por página, com os `exists()` de disco de `IdentidadeDoKit` em cada uma — o dobro do
 * que a logo solta custava. O memo é por **request** (um `WeakMap` com a `Request` corrente
 * como chave): some junto com o request, então não vaza entre requests do mesmo processo — que
 * é o que um `once()` em método estático faria na suíte, devolvendo a composição velha depois
 * de a configuração mudar. Resultado: uma resolução e, no pior caso, um `warning` por request.
 * A mesma entrada do memo guarda os segmentos e o par de logos (`logos()`), para que a marca
 * simples, a escura e a composição leiam a mesma resolução. Sem `Request` ligada, resolve sem memo.
 *
 * Só estáticos, como `IdentidadeDoKit` e `AssinaturaDoRodape`: o único estado é o memo.
 */
final class CabecalhoDoPainel
{
    /** @var WeakMap<Request, array{segmentos: array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null, logos: array{clara: ?string, escura: ?string}}>|null */
    private static ?WeakMap $memo = null;

    /**
     * O que o `brandLogo()` dos três painéis devolve.
     *
     * `null` em `segmentos()` cobre os casos em que a marca é o par de `logos()` — o da
     * organização aberta no `/app`, senão o da instalação —: nenhum segmento ligado, tela de
     * autenticação, ou ligado e nada resolvido (P-11 — nunca marca vazia, nunca imagem quebrada).
     */
    public static function marca(): string|Htmlable|null
    {
        $segmentos = self::segmentos();

        if ($segmentos === null) {
            return self::logos()['clara'];
        }

        return new HtmlString(view('filament.cabecalho-do-painel', $segmentos)->render());
    }

    /**
     * O que o `darkModeBrandLogo()` devolve: `null` com a composição ativa, porque a variante
     * escura já vai dentro dela (`<img class="fi-logo fi-logo-dark">`); do contrário, o par de
     * `logos()` — o da organização aberta no `/app`, senão o da instalação.
     */
    public static function marcaEscura(): ?string
    {
        return self::segmentos() === null ? self::logos()['escura'] : null;
    }

    /**
     * O que o render hook `USER_MENU_BEFORE` devolve: nome de quem está autenticado e, abaixo,
     * o e-mail ou o papel no painel corrente — à esquerda do avatar, complementando o menu
     * nativo (P-09). Vazio com o bloco desligado ou sem usuário autenticado.
     *
     * O papel vem de `User::papelNoPainelCorrente()` + `Papeis::rotulo()` — a mesma regra, no
     * mesmo método, que o badge do menu do usuário usa; nunca `getRoleNames()`, que com
     * `permission.teams` ligado some fora do contexto da organização (`.ai/rules/models.md`).
     * Sem papel no painel, a linha de baixo não é renderizada (P-03): é estado normal, não
     * anomalia, por isso sem log.
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
     * Os segmentos da composição, já resolvidos — ou `null` quando a marca deve ser o par de
     * `logos()` — o da organização aberta no `/app`, senão o da instalação.
     * Memoizado por request (ver o docblock da classe).
     *
     * @return array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null
     */
    public static function segmentos(): ?array
    {
        return self::entrada()['segmentos'];
    }

    /**
     * O par de logos da marca sem composição: o da organização aberta no `/app`, com queda por
     * variante para o da instalação (`IdentidadeDoKit::logosPara()`); fora dele, o da instalação.
     * Privado: quem consome é `marca()`, `marcaEscura()` e a composição.
     *
     * @return array{clara: ?string, escura: ?string}
     */
    private static function logos(): array
    {
        return self::entrada()['logos'];
    }

    /**
     * A entrada do memo do request corrente: segmentos e logos resolvidos juntos.
     *
     * @return array{segmentos: array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null, logos: array{clara: ?string, escura: ?string}}
     */
    private static function entrada(): array
    {
        $request = app()->bound('request') ? app('request') : null;

        if (! $request instanceof Request) {
            return self::resolverEntrada();
        }

        self::$memo ??= new WeakMap;

        if (! isset(self::$memo[$request])) {
            self::$memo[$request] = self::resolverEntrada();
        }

        return self::$memo[$request];
    }

    /**
     * @return array{segmentos: array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null, logos: array{clara: ?string, escura: ?string}}
     */
    private static function resolverEntrada(): array
    {
        $logos = IdentidadeDoKit::logosPara(self::organizacaoAberta());

        return ['segmentos' => self::resolverSegmentos($logos), 'logos' => $logos];
    }

    /**
     * A organização aberta, só em painel com tenancy (o `/app`); `null` nos demais.
     *
     * Nunca `Paineis::correnteOuPadrao()`: cai no `app`, que tem tenancy, e vazaria a logo de
     * organização — exceção da `app.md`; precedente: a Closure da cor em `AppPanelProvider`.
     */
    private static function organizacaoAberta(): ?Tenant
    {
        if (Filament::getCurrentPanel()?->hasTenancy() !== true) {
            return null;
        }

        $organizacao = Filament::getTenant();

        return $organizacao instanceof Tenant ? $organizacao : null;
    }

    /**
     * - `projeto`: o nome da aplicação (P-08), se o interruptor estiver ligado.
     * - `painel`: com organização aberta, o nome dela (RQ-02); sem, o rótulo do painel
     *   (`Paineis::rotulo()`), omitido quando o projeto está ligado e tem o mesmo texto (P-01 —
     *   o `/app` usa o nome da aplicação como rótulo).
     * - `logo_clara`/`logo_escura`: o par de `logos()` (P-07), se o interruptor estiver ligado:
     *   a da organização aberta no `/app`, a da instalação no resto. A escura só existe
     *   com a marca separada e só acompanha uma clara.
     *
     * @param  array{clara: ?string, escura: ?string}  $logos
     * @return array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null
     */
    private static function resolverSegmentos(array $logos): ?array
    {
        $exibeProjeto = (bool) config('kit.cabecalho.nome_do_projeto', false);
        $exibePainel  = (bool) config('kit.cabecalho.nome_do_painel', false);
        $exibeLogo    = (bool) config('kit.cabecalho.logo_da_marca', false);

        if (! $exibeProjeto && ! $exibePainel && ! $exibeLogo) {
            return null;
        }

        /*
         * Tela de autenticação fica com a marca de hoje — pública (login, recuperação,
         * bloqueio) ou já autenticada (dois fatores, confirmação de e-mail): a composição é
         * das telas internas dos painéis (Fora de Escopo do `00`). O `brandLogo()` é o mesmo
         * em todas, então a guarda tem de estar aqui, não no provider.
         */
        if (! Filament::auth()->check() || self::emTelaDeAutenticacao()) {
            return null;
        }

        $painel  = Paineis::correnteOuPadrao();
        $tenant  = self::organizacaoAberta();
        $projeto = $exibeProjeto ? (string) config('app.name') : null;

        $nomeDoPainel = null;

        if ($exibePainel) {
            $nomeDoPainel = $tenant instanceof HasName ? $tenant->getFilamentName() : Paineis::rotulo($painel);

            if ($tenant === null && $projeto !== null && $nomeDoPainel === $projeto) {
                $nomeDoPainel = null;
            }
        }

        $logoClara  = $exibeLogo ? $logos['clara'] : null;
        $logoEscura = $logoClara !== null ? $logos['escura'] : null;

        if (blank($projeto) && blank($nomeDoPainel) && $logoClara === null) {
            Log::channel('configuracoes')->warning(
                '[CabecalhoDoPainel@resolverSegmentos] Composição pedida sem nenhum segmento resolvido, usando a marca padrão | painel: '.$painel->getId(),
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

    /**
     * Três sinais, porque cada um deixa um buraco sozinho:
     *
     * - sem usuário autenticado é tela pública — tratado antes de chamar este método;
     * - a rota `filament.{painel}.auth.*` cobre o GET das telas autenticadas de autenticação
     *   (2FA do Breezy, aviso de verificação de e-mail), mas **não** o update Livewire delas:
     *   ali a rota corrente é `default-livewire.update`, e a `SimplePage` redesenha a logo
     *   dentro do próprio componente (`vendor/filament/filament/resources/views/components/page/simple.blade.php:$hasLogo:23`).
     *   Foi o QA-01 do ciclo 1: GET limpo, composição aparecendo depois do primeiro clique;
     * - por isso o componente Livewire corrente decide: toda tela de autenticação do kit é
     *   `SimplePage` (login do Filament, prompt de e-mail do Auth Designer, 2FA do Breezy),
     *   e `Livewire::current()` é o mesmo no GET e no update.
     */
    private static function emTelaDeAutenticacao(): bool
    {
        if (str_contains((string) request()->route()?->getName(), '.auth.')) {
            return true;
        }

        return Livewire::current() instanceof SimplePage;
    }

    private static function rotuloDoPapel(User $usuario): ?string
    {
        $papel = $usuario->papelNoPainelCorrente();

        return $papel === null ? null : Papeis::rotulo($papel);
    }
}
