<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Pages\Page as PaginaDeResource;
use Filament\Resources\Resource;
use Filament\Support\Contracts\HasLabel;
use HoceineEl\QuickActionDock\Enums\DockLayout;
use HoceineEl\QuickActionDock\Enums\DockStyle;
use HoceineEl\QuickActionDock\Support\DockContext;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use UnitEnum;
use WeakMap;

/**
 * O decisor do dock de atalhos (hoceineel/filament-quick-action-dock) — a ÚNICA leitura de
 * `kit.dock.*` e dos favoritos gravados em `users.dock_atalhos`.
 *
 * ## Settings sem consulta (RQ-22)
 *
 * Tudo o que decide SE e COMO o dock aparece lê `config('kit.dock.*')`, que
 * `ConfiguracoesDoKit::aplicarNaConfig()` já sobrepôs com o banco no boot. Nenhum
 * `app(ConfiguracoesDoKit::class)` aqui: as Closures do plugin rodam em todo request do painel e em
 * todo update Livewire do componente.
 *
 * ## Autorização no contexto do destino (ADR-01)
 *
 * Um favorito pode ser de outro painel e, no `/app`, de outra organização. `canAccess()` chamado
 * "direto" daria a resposta do contexto errado — e, para Page, `true` para todo mundo, pelo
 * fail-open que `App\Filament\Concerns\ExigePermissaoDaTela` herda do `HasPageShield` quando o
 * painel corrente não é o da Page. Por isso favorito de OUTRO painel ou de OUTRA organização é
 * avaliado em `noContexto()`, que fixa painel, organização, `team_id` do spatie e o mapa de Pages
 * do Shield, e restaura os quatro no `finally`.
 *
 * Favorito do painel E da organização deste request não troca nada (P-04): o middleware do painel
 * já fixou os três e já conferiu painel e organização — ver `organizacaoDoRequest()`.
 *
 * ## Memo por request (D8, CR-04)
 *
 * O componente do pacote avalia as actions duas vezes por request
 * (`QuickActionDock::cacheDockActions()` no `mount()`/`hydrate()` e `getDockGroups()` no render), e
 * o slideover pede o catálogo ao montar o formulário e de novo ao validar o envio.
 * `atalhosVisiveis()` e `catalogo()` guardam o resultado num `WeakMap` com a `Request` corrente
 * como chave, como `CabecalhoDoPainel`: some com o request e não vaza entre requests do mesmo
 * processo. `gravar()` e `adicionarPaginaAtual()` descartam o memo inteiro.
 *
 * ## Catálogo e gravação (RQ-11, RQ-12, RQ-29, RQ-35)
 *
 * `catalogo()` sai das telas registradas nos painéis, filtradas como a barra lateral
 * (`shouldRegisterNavigation()` + `canAccess()`) no contexto de cada painel — nunca de uma lista
 * escrita à mão. `gravar()` só aceita chave desse catálogo ou favorito já gravado que o usuário
 * ainda abre; qualquer outra recusa o envio inteiro.
 *
 * ## Adicionar esta página (RQ-21, RQ-23, RQ-28, RQ-33)
 *
 * `paginaAtualComoAtalho()` traduz a página do `DockContext` (props `#[Locked]` do componente)
 * em chave do catálogo: lista de Resource vira o Resource, Page avulsa vira a Page, tela de
 * registro não vira nada. `adicionarPaginaAtual()` refaz essa conta no servidor e anexa no fim.
 *
 * Só estáticos: o único estado é o memo.
 */
final class DockDeAtalhos
{
    /** A permissão exclusiva do dock (`config('filament-shield.custom_permissions')`). */
    public const PERMISSAO = 'Usar:Dock';

    /** O teto de favoritos gravados por usuário, contando os ocultos (RQ-18, RQ-32). */
    public const MAXIMO_DE_ATALHOS = 10;

    /** @var WeakMap<Request, array<string, mixed>>|null */
    private static ?WeakMap $memo = null;

    /**
     * O dock está ligado neste painel? Lista vazia = NENHUM painel (RQ-14) — a semântica oposta à
     * do dashboard dinâmico, de propósito: instalação nova nasce sem dock. Zero consulta.
     */
    public static function habilitadoNoPainel(?Panel $painel): bool
    {
        if ($painel === null) {
            return false;
        }

        return in_array($painel->getId(), (array) config('kit.dock.paineis', []), true);
    }

    /** O layout do dock: o do usuário, se ele escolheu um válido; senão o da instalação (RQ-17). */
    public static function layoutPara(?User $usuario): DockLayout
    {
        return DockLayout::tryFrom((string) $usuario?->getAttribute('dock_layout'))
            ?? DockLayout::tryFrom((string) config('kit.dock.layout'))
            ?? DockLayout::SpeedDial;
    }

    /** O estilo do dock — só o da instalação; o usuário não o sobrescreve (RQ-17). */
    public static function estilo(): DockStyle
    {
        return DockStyle::tryFrom((string) config('kit.dock.estilo')) ?? DockStyle::Native;
    }

    /**
     * Os layouts do pacote, `value => rótulo` em pt-BR.
     *
     * Strings, e não o enum, no estado do formulário: instância de enum chegaria à propriedade
     * `string` do settings e estouraria `TypeError` no salvamento (o mesmo motivo de
     * `DensidadeDoLayout::opcoes()`).
     *
     * @return array<string, string>
     */
    public static function opcoesDeLayout(): array
    {
        return array_column(array_map(static fn (DockLayout $layout): array => [
            'valor'  => $layout->value,
            'rotulo' => match ($layout) {
                DockLayout::SpeedDial => 'Leque (speed dial)',
                DockLayout::Bar       => 'Barra',
            },
        ], DockLayout::cases()), 'rotulo', 'valor');
    }

    /**
     * Os estilos do pacote, `value => rótulo` em pt-BR.
     *
     * @return array<string, string>
     */
    public static function opcoesDeEstilo(): array
    {
        return array_column(array_map(static fn (DockStyle $estilo): array => [
            'valor'  => $estilo->value,
            'rotulo' => match ($estilo) {
                DockStyle::Native   => 'Nativo',
                DockStyle::Outline  => 'Contorno',
                DockStyle::Contrast => 'Contraste',
                DockStyle::Tinted   => 'Tingido',
            },
        ], DockStyle::cases()), 'rotulo', 'valor');
    }

    /**
     * Os favoritos gravados que o usuário pode abrir agora, na ordem gravada, com a URL pronta.
     *
     * Favorito que ele deixou de poder abrir some daqui e continua gravado (RQ-19).
     *
     * @return list<array{chave: string, url: string, rotulo: string, rotuloCompleto: string, icone: string|BackedEnum|Htmlable|null}>
     */
    public static function atalhosVisiveis(User $usuario): array
    {
        /** @var list<array{chave: string, url: string, rotulo: string, rotuloCompleto: string, icone: string|BackedEnum|Htmlable|null}> */
        return self::memorizado('atalhos|'.$usuario->getKey(), static fn (): array => self::resolverAtalhosVisiveis($usuario));
    }

    /**
     * As telas que o usuário pode escolher como favorito NESTE request: chave D6 → rótulo com o
     * painel (e a organização) antes do grupo e da tela (RQ-31).
     *
     * Percorre todos os painéis em que ele entra, avaliando cada tela no contexto do painel dela
     * (ADR-01) — uma troca de contexto por painel, não por tela. O painel com tenancy só entra
     * quando é o painel corrente e há organização aberta, e só com a organização aberta (D7,
     * RQ-25): avaliar todas as organizações do usuário custaria uma troca por organização.
     *
     * Memorizado por request (CR-04), com o painel e a organização correntes na chave do memo,
     * porque são eles que decidem quais painéis com tenancy entram.
     *
     * @return array<string, string>
     */
    public static function catalogo(User $usuario): array
    {
        $chaveDoMemo = 'catalogo|'.$usuario->getKey().'|'.Filament::getCurrentPanel()?->getId().'|'.Filament::getTenant()?->getKey();

        /** @var array<string, string> */
        return self::memorizado($chaveDoMemo, static fn (): array => self::resolverCatalogo($usuario));
    }

    /**
     * @return array<string, string>
     */
    private static function resolverCatalogo(User $usuario): array
    {
        $catalogo = [];

        foreach (Filament::getPanels() as $painel) {
            try {
                if (! self::acessaPainel($usuario, $painel)) {
                    continue;
                }

                $organizacao = self::organizacaoDoCatalogo($painel, $usuario);

                if ($organizacao === false) {
                    continue;
                }

                $catalogo += self::noContexto($painel, $organizacao, $usuario, static function () use ($painel, $organizacao): array {
                    $doPainel = [];

                    foreach (self::destinosDoPainel($painel) as $destino) {
                        if (self::abreNoContexto($destino)) {
                            $doPainel[self::chave($painel, $destino, $organizacao)] = self::rotuloNoCatalogo($painel, $organizacao, $destino);
                        }
                    }

                    return $doPainel;
                });
            } catch (Throwable $erro) {
                Log::channel('dock-de-acoes-rapidas')->warning(
                    "[DockDeAtalhos@catalogo] Falha ao avaliar painel, fora do catálogo | user: {$usuario->getKey()} - painel: {$painel->getId()}",
                    ['user_id' => $usuario->getKey(), 'painel' => $painel->getId(), 'erro' => $erro::class],
                );
            }
        }

        return $catalogo;
    }

    /**
     * As opções do slideover: primeiro os favoritos gravados que o usuário ainda abre, de
     * qualquer painel e organização, na ordem gravada (RQ-35); depois o catálogo deste request.
     *
     * @return array<string, string>
     */
    public static function opcoesDoSlideover(User $usuario): array
    {
        return array_column(self::atalhosVisiveis($usuario), 'rotuloCompleto', 'chave') + self::catalogo($usuario);
    }

    /**
     * Grava os favoritos e o layout escolhidos no slideover.
     *
     * 1. Qualquer chave fora das opções do slideover (catálogo + favoritos abríveis) recusa o
     *    envio INTEIRO (RQ-29) — a interface nunca envia chave inválida, só cliente adulterado.
     * 2. Repetição vira uma só (RQ-28), na ordem enviada (RQ-30).
     * 3. Favorito oculto (destino que existe e o usuário deixou de abrir) é preservado no fim
     *    (RQ-19, RQ-27); chave gravada que nunca foi destino é descartada, e também a de
     *    organização que não existe mais (P-02) — ela não volta, ao contrário do oculto
     *    (`destinoExiste()`).
     * 4. Mais de 10 no resultado — contando os ocultos, já sem os descartados — recusa, sem gravar
     *    nada, e devolve `false` para quem chamou avisar o limite (RQ-18, RQ-26, RQ-32). Nunca
     *    corta em silêncio.
     * 5. Layout fora do enum do pacote vira `null` (padrão da instalação).
     * 6. Gravou: o memo do request é descartado, e `atalhosVisiveis()`/`catalogo()` releem os
     *    favoritos na próxima chamada. Quem re-renderiza o dock na mesma requisição ainda tem de
     *    trocar as actions já cacheadas no componente (`PluginDoDock::recachearAtalhos()`, CR-01).
     *
     * @param  array<mixed>  $chaves
     * @return bool `true` quando gravou; `false` quando recusou pelo teto (nada gravado)
     *
     * @throws ValidationException no campo `atalhos`, quando há chave fora das opções (RQ-29)
     */
    public static function gravar(User $usuario, array $chaves, ?string $layout): bool
    {
        $permitidas = array_keys(self::opcoesDoSlideover($usuario));

        $recusadas = count(array_filter(
            $chaves,
            static fn (mixed $chave): bool => ! is_string($chave) || ! in_array($chave, $permitidas, true),
        ));

        if ($recusadas > 0) {
            Log::channel('dock-de-acoes-rapidas')->warning(
                "[DockDeAtalhos@gravar] Chave fora do catálogo descartada | user: {$usuario->getKey()}",
                ['user_id' => $usuario->getKey(), 'descartadas' => $recusadas],
            );

            throw ValidationException::withMessages([
                'atalhos' => 'Há atalhos fora das telas disponíveis. Nada foi gravado.',
            ]);
        }

        /** @var list<string> $escolhidas */
        $escolhidas = array_values(array_unique($chaves));

        $ocultas = array_filter(
            self::chavesGravadas($usuario),
            static fn (string $chave): bool => ! in_array($chave, $permitidas, true)
                && self::destinoExiste($chave),
        );

        $atalhos = array_values(array_unique([...$escolhidas, ...$ocultas]));

        if (count($atalhos) > self::MAXIMO_DE_ATALHOS) {
            return false;
        }

        $usuario->forceFill([
            'dock_atalhos' => $atalhos,
            'dock_layout'  => DockLayout::tryFrom((string) $layout)?->value,
        ])->save();

        self::$memo = null;

        Log::channel('dock-de-acoes-rapidas')->info(
            "[DockDeAtalhos@gravar] Favoritos gravados | user: {$usuario->getKey()}",
            ['user_id' => $usuario->getKey(), 'total' => count($atalhos)],
        );

        return true;
    }

    /**
     * A chave da página atual do dock, quando ela pode virar favorito: é destino do catálogo do
     * usuário NESTE request e ainda não está gravada. `null` em qualquer outro caso.
     *
     * A página vem de `DockContext::current()` (rota `#[Locked]` no componente, RQ-21); o painel
     * sai do nome da rota, porque a mesma Page pode estar em mais de um painel. Lista (`index`) de
     * Resource vira o Resource; Page avulsa vira a Page; criar, editar e ver registro não viram
     * nada — nem a lista do Resource (RQ-23). A conferência de catálogo é a de `catalogo()`, só que
     * para um destino: painel acessível, organização aberta no painel com tenancy (D7) e
     * `abreNoContexto()` no contexto do painel (ADR-01) — trocado por `noContexto()` só quando a
     * rota é de outro painel que o corrente. Já favorita → `null` (RQ-28).
     *
     * Página do painel deste request não reconfere painel nem organização, nem troca o contexto:
     * o middleware do painel já fez as duas coisas (ver `organizacaoDoRequest()`), e reconferir só
     * recarregaria os papéis — consulta a mais em toda página (RQ-22).
     */
    public static function paginaAtualComoAtalho(User $usuario): ?string
    {
        $contexto = DockContext::current();
        $painel   = self::painelDaRota($contexto->getRouteName());
        $destino  = self::destinoDaPagina($contexto->getPageClass());

        if ($painel === null || $destino === null || ! in_array($destino, self::destinosDoPainel($painel), true)) {
            return null;
        }

        try {
            if (Filament::getCurrentPanel()?->getId() === $painel->getId()) {
                $aberta      = Filament::getTenant();
                $organizacao = $painel->hasTenancy() && $aberta instanceof Tenant ? $aberta : null;

                if ($painel->hasTenancy() && $organizacao === null) {
                    return null;
                }

                $abre = self::abreNoContexto($destino);
            } else {
                $organizacao = self::acessaPainel($usuario, $painel) ? self::organizacaoDoCatalogo($painel, $usuario) : false;

                if ($organizacao === false) {
                    return null;
                }

                $abre = self::noContexto($painel, $organizacao, $usuario, static fn (): bool => self::abreNoContexto($destino));
            }

            if (! $abre) {
                return null;
            }
        } catch (Throwable $erro) {
            Log::channel('dock-de-acoes-rapidas')->warning(
                "[DockDeAtalhos@paginaAtualComoAtalho] Falha ao avaliar a página atual, não oferecida | user: {$usuario->getKey()} - painel: {$painel->getId()}",
                ['user_id' => $usuario->getKey(), 'painel' => $painel->getId(), 'erro' => $erro::class],
            );

            return null;
        }

        $chave = self::chave($painel, $destino, $organizacao);

        return in_array($chave, self::chavesGravadas($usuario), true) ? null : $chave;
    }

    /**
     * Anexa a página atual NO FIM dos favoritos, sem mexer na ordem dos demais (RQ-33).
     *
     * Recalcula `paginaAtualComoAtalho()` aqui, e não confia na visibilidade da action: a chamada
     * direta de um cliente adulterado (ou o duplo clique) passa por esta mesma conta, então
     * página fora do catálogo, tela de registro e página já favorita não gravam nada (RQ-23,
     * RQ-28). O teto conta TODA entrada gravada que permanece — oculta e chave que nunca foi
     * destino inclusive, porque esta adição não descarta nada (RQ-18, RQ-26, RQ-32).
     *
     * @return bool|null `true` gravou; `false` recusou pelo teto (nada gravado); `null` não havia
     *                   o que adicionar — já favorita, ou o destino deixou de ser abrível entre o
     *                   render e o clique; quem chamou avisa (P-03)
     */
    public static function adicionarPaginaAtual(User $usuario): ?bool
    {
        $chave = self::paginaAtualComoAtalho($usuario);

        if ($chave === null) {
            return null;
        }

        $gravadas = self::chavesGravadas($usuario);

        if (count($gravadas) >= self::MAXIMO_DE_ATALHOS) {
            return false;
        }

        $usuario->forceFill(['dock_atalhos' => [...$gravadas, $chave]])->save();

        self::$memo = null;

        Log::channel('dock-de-acoes-rapidas')->info(
            "[DockDeAtalhos@adicionarPaginaAtual] Favoritos gravados | user: {$usuario->getKey()}",
            ['user_id' => $usuario->getKey(), 'total' => count($gravadas) + 1],
        );

        return true;
    }

    /**
     * O usuário pode abrir o destino desta chave? Qualquer chave malformada, destino fora do
     * painel ou exceção na avaliação NEGA (ADR-01, ADR-02).
     */
    public static function podeAbrir(User $usuario, string $chave): bool
    {
        return self::avaliar($usuario, $chave) !== null;
    }

    /**
     * Roda `$callback` com o painel, a organização, o `team_id` do spatie e o mapa de Pages do
     * Shield do destino, e restaura os quatro no `finally` — contexto que não volta contamina o
     * resto do request (ADR-01, riscos).
     *
     * A organização é fixada com `isQuiet`: o evento `TenantSet` do Filament é de troca de
     * organização pelo usuário, não de avaliação (`FilamentManager::setTenant()`).
     *
     * ## O mapa de Pages do Shield (RD-01)
     *
     * `HasPageShield::getPagePermission()` acha a permissão da Page em `FilamentShield::getPages()`,
     * que é memoizado com `once()` POR INSTÂNCIA
     * (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:getPages:71`) e só lista as Pages
     * do painel corrente no momento da primeira chamada (`config/filament-shield.php`
     * `discover_all_pages => false`). Trocar o painel não invalida o mapa: com a barra lateral do
     * painel corrente já montada no request, a Page de outro painel não é achada, a chave não
     * resolve e `HasPageShield::canAccess()` LIBERA (fail-open descrito em
     * `App\Filament\Concerns\ExigePermissaoDaTela`). Por isso, quando o destino é de OUTRO painel, a
     * instância do Shield é descartada antes da avaliação — o mapa passa a ser o do destino — e de
     * novo no `finally`, para o mapa do painel corrente voltar a valer. É o mesmo descarte de
     * `Paineis::shieldNovo()` e do `noPainelDoShield()` de `tests/Pest.php`. Mesmo painel não
     * descarta nada: o mapa memoizado já é o certo.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function noContexto(Panel $painel, ?Tenant $organizacao, User $usuario, Closure $callback): mixed
    {
        $painelAnterior      = Filament::getCurrentPanel();
        $organizacaoAnterior = Filament::getTenant();
        $trocaDePainel       = $painelAnterior?->getId() !== $painel->getId();

        try {
            if ($trocaDePainel) {
                Paineis::shieldNovo();
            }

            Filament::setCurrentPanel($painel);
            Filament::setTenant($organizacao, isQuiet: true);

            $contexto = $organizacao === null ? Tenant::CONTEXTO_GLOBAL : (int) $organizacao->getKey();

            return ContextoDePapeis::em($contexto, $usuario, $callback);
        } finally {
            Filament::setCurrentPanel($painelAnterior);
            Filament::setTenant($organizacaoAnterior, isQuiet: true);

            if ($trocaDePainel) {
                Paineis::shieldNovo();
            }
        }
    }

    /**
     * @return list<array{chave: string, url: string, rotulo: string, rotuloCompleto: string, icone: string|BackedEnum|Htmlable|null}>
     */
    private static function resolverAtalhosVisiveis(User $usuario): array
    {
        $atalhos = [];

        foreach (self::chavesGravadas($usuario) as $chave) {
            $atalho = self::avaliar($usuario, $chave);

            if ($atalho !== null) {
                $atalhos[] = ['chave' => $chave, ...$atalho];
            }
        }

        return $atalhos;
    }

    /**
     * As chaves gravadas, só as strings — o JSON pode ter sido adulterado (ADR-02, riscos).
     *
     * O usuário autenticado vem do banco com todas as colunas, então a leitura custa zero
     * consulta (ADR-02). A consulta só acontece quando a instância NÃO carrega a coluna — modelo
     * montado em memória (`User::create()` não traz coluna que não recebeu) ou carregado com
     * `select` parcial —, e aí a coluna é lida em vez de presumida vazia: presumir esconderia
     * favoritos gravados. O memo de `atalhosVisiveis()` limita isso a uma vez por request.
     *
     * @return list<string>
     */
    private static function chavesGravadas(User $usuario): array
    {
        $gravadas = array_key_exists('dock_atalhos', $usuario->getAttributes())
            ? $usuario->getAttribute('dock_atalhos')
            : User::query()->whereKey($usuario->getKey())->value('dock_atalhos');

        if (! is_array($gravadas)) {
            return [];
        }

        return array_values(array_filter($gravadas, is_string(...)));
    }

    /**
     * URL, rótulos e ícone do destino da chave, ou `null` quando o usuário não pode abri-lo.
     *
     * A ordem das barreiras é a do ADR-01: formato, painel existente, destino registrado NAQUELE
     * painel (o FQCN vira nome de classe — só classe que o painel já registra é chamada),
     * acesso ao painel (`acessaPainel()`), organização e `canAccessTenant()` no painel com tenancy, e só então
     * `shouldRegisterNavigation()` e `canAccess()` dentro do contexto do destino.
     *
     * Favorito do painel e da organização deste request pula as conferências de painel e de
     * organização e a troca de contexto (P-04, `organizacaoDoRequest()`): sem recarga de papéis nem
     * busca da organização; a única consulta admitida é a da autorização da própria tela
     * (`Gate::before` → `User::isMasterGlobal()`), a mesma de cada item da barra lateral.
     *
     * @return array{url: string, rotulo: string, rotuloCompleto: string, icone: string|BackedEnum|Htmlable|null}|null
     */
    private static function avaliar(User $usuario, string $chave): ?array
    {
        $partes = self::destinoDaChave($chave);

        if ($partes === null) {
            return null;
        }

        [$painel, $destino, $idDaOrganizacao] = $partes;

        try {
            $doRequest = self::organizacaoDoRequest($painel, $idDaOrganizacao);

            if ($doRequest !== false) {
                return self::montarAtalho($painel, $doRequest, $destino);
            }

            if (! self::acessaPainel($usuario, $painel)) {
                return null;
            }

            $organizacao = self::organizacaoDoDestino($painel, $idDaOrganizacao, $usuario);

            if ($organizacao === false) {
                return null;
            }

            return self::noContexto($painel, $organizacao, $usuario, static fn (): ?array => self::montarAtalho($painel, $organizacao, $destino));
        } catch (Throwable $erro) {
            Log::channel('dock-de-acoes-rapidas')->warning(
                "[DockDeAtalhos@podeAbrir] Falha ao avaliar destino, negado | user: {$usuario->getKey()} - painel: {$painel->getId()}",
                ['user_id' => $usuario->getKey(), 'painel' => $painel->getId(), 'erro' => $erro::class],
            );

            return null;
        }
    }

    /**
     * O atalho do destino no contexto CORRENTE — o do request, ou o fixado por `noContexto()`.
     *
     * @param  class-string<resource>|class-string<Page>  $destino
     * @return array{url: string, rotulo: string, rotuloCompleto: string, icone: string|BackedEnum|Htmlable|null}|null
     */
    private static function montarAtalho(Panel $painel, ?Tenant $organizacao, string $destino): ?array
    {
        if (! self::abreNoContexto($destino)) {
            return null;
        }

        $url = is_subclass_of($destino, Resource::class)
            ? $destino::getUrl('index', panel: $painel->getId(), tenant: $organizacao)
            : $destino::getUrl(panel: $painel->getId(), tenant: $organizacao);

        return [
            'url'            => $url,
            'rotulo'         => self::textoDoRotulo($destino::getNavigationLabel()),
            'rotuloCompleto' => self::rotuloNoCatalogo($painel, $organizacao, $destino),
            'icone'          => $destino::getNavigationIcon(),
        ];
    }

    /**
     * A organização do favorito quando ele é do painel E da organização deste request: `null` em
     * painel sem tenancy, a organização aberta no `/app`; `false` em qualquer outro caso (P-04).
     *
     * Nesse caso não há o que conferir nem o que trocar. O Filament registra `Authenticate`,
     * `IdentifyTenant` e `SetUpPanel` como middleware PERSISTENTE do Livewire
     * (`vendor/filament/filament/src/FilamentServiceProvider.php:addPersistentMiddleware:128`),
     * então eles rodam na página e em todo update do componente do dock: `Authenticate` já
     * respondeu 403 a quem não abre o painel (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:canAccessPanel:37`),
     * `IdentifyTenant` já respondeu 404 a quem não acessa a organização da rota e a fixou
     * (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:canAccessTenant:40`,
     * `vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:setTenant:44`), e o `DefinirTenantDePermissoes` do `/app` (middleware de tenant
     * persistente, `App\Providers\Filament\AppPanelProvider`) já fixou o `team_id` dela — fora do
     * `/app` vale o contexto global do boot (`KitServiceProvider::configureTenancy()`).
     * Reconferir chamaria `canAccessTenant()` (uma consulta por favorito) e trocar o contexto
     * recarregaria os papéis (outra), sem mudar a resposta.
     */
    private static function organizacaoDoRequest(Panel $painel, string $idDaOrganizacao): Tenant|false|null
    {
        if (Filament::getCurrentPanel()?->getId() !== $painel->getId()) {
            return false;
        }

        if (! $painel->hasTenancy()) {
            return $idDaOrganizacao === '' ? null : false;
        }

        $aberta = Filament::getTenant();

        return $aberta instanceof Tenant && (string) $aberta->getKey() === $idDaOrganizacao ? $aberta : false;
    }

    /**
     * O usuário abre o painel? A decisão de `User::canAccessPanel()`, sem o `warning` que ele
     * registra no canal `autenticacao` a cada negativa (RD-05): o dock CONSULTA todos os painéis em
     * todo render, e painel que o usuário não abre não é tentativa de entrada.
     */
    private static function acessaPainel(User $usuario, Panel $painel): bool
    {
        return $usuario->motivoDaNegativaDoPainel($painel) === null;
    }

    /**
     * A chave ainda aponta para um destino que existe? Painel e classe registrados
     * (`destinoDaChave()`) e, no painel com tenancy, organização que existe no banco — favorito de
     * organização excluída não volta, então não é oculto: é descartado ao gravar (P-02). Fora do
     * painel com tenancy a parte da organização tem de vir vazia.
     */
    private static function destinoExiste(string $chave): bool
    {
        $partes = self::destinoDaChave($chave);

        if ($partes === null) {
            return false;
        }

        [$painel, , $idDaOrganizacao] = $partes;

        if (! $painel->hasTenancy()) {
            return $idDaOrganizacao === '';
        }

        return ctype_digit($idDaOrganizacao) && Tenant::query()->whereKey((int) $idDaOrganizacao)->exists();
    }

    /**
     * O valor de `$resolver` memorizado na `Request` corrente sob `$chave` (D8). Fora de request
     * (console sem `request` no container), resolve sempre.
     *
     * @param  Closure(): mixed  $resolver
     */
    private static function memorizado(string $chave, Closure $resolver): mixed
    {
        $request = app()->bound('request') ? app('request') : null;

        if (! $request instanceof Request) {
            return $resolver();
        }

        $memo      = self::$memo ??= new WeakMap;
        $doRequest = $memo[$request] ?? [];

        if (! array_key_exists($chave, $doRequest)) {
            $doRequest[$chave] = $resolver();
            $memo[$request]    = $doRequest;
        }

        return $doRequest[$chave];
    }

    /**
     * As partes de uma chave que aponta para um destino registrado: painel registrado e classe
     * registrada naquele painel. Não diz nada sobre acesso — é o que separa o favorito oculto
     * (preservado ao gravar) da chave que nunca foi destino (descartada, RQ-27).
     *
     * O destino devolvido é o da lista do painel, não a string recebida: só classe que o painel já
     * registra é chamada.
     *
     * @return array{0: Panel, 1: class-string<resource>|class-string<Page>, 2: string}|null
     */
    private static function destinoDaChave(string $chave): ?array
    {
        $partes = explode('|', $chave);

        if (count($partes) !== 3) {
            return null;
        }

        [$idDoPainel, $destino, $idDaOrganizacao] = $partes;

        $painel = Filament::getPanels()[$idDoPainel] ?? null;

        if (! $painel instanceof Panel) {
            return null;
        }

        foreach (self::destinosDoPainel($painel) as $registrado) {
            if ($registrado === $destino) {
                return [$painel, $registrado, $idDaOrganizacao];
            }
        }

        return null;
    }

    /**
     * Os Resources e as Pages registrados no painel.
     *
     * @return list<class-string<resource>|class-string<Page>>
     */
    private static function destinosDoPainel(Panel $painel): array
    {
        $destinos = [];

        foreach ([...$painel->getResources(), ...$painel->getPages()] as $destino) {
            if (is_a($destino, Resource::class, true) || is_a($destino, Page::class, true)) {
                $destinos[] = $destino;
            }
        }

        return $destinos;
    }

    /**
     * O destino aparece e abre no contexto corrente — o mesmo filtro da barra lateral
     * (`DescobreCardsDoPainel`). Só faz sentido dentro de `noContexto()`.
     *
     * Resource sem página `index` fica de fora: não há lista para o atalho abrir.
     *
     * @param  class-string<resource>|class-string<Page>  $destino
     */
    private static function abreNoContexto(string $destino): bool
    {
        if (is_subclass_of($destino, Resource::class) && ! $destino::hasPage('index')) {
            return false;
        }

        return $destino::shouldRegisterNavigation() && $destino::canAccess();
    }

    /** O painel dono da rota (`filament.{painel}.…`), ou `null` fora dos painéis. */
    private static function painelDaRota(?string $rota): ?Panel
    {
        if ($rota === null) {
            return null;
        }

        foreach (Filament::getPanels() as $painel) {
            if (str_starts_with($rota, 'filament.'.$painel->getId().'.')) {
                return $painel;
            }
        }

        return null;
    }

    /**
     * O destino de catálogo que a classe da página representa: a página `index` de um Resource
     * vira o Resource; Page avulsa é ela mesma; qualquer outra página de Resource (criar, editar,
     * ver) não é destino (RQ-23).
     *
     * @return class-string<resource>|class-string<Page>|null
     */
    private static function destinoDaPagina(?string $pagina): ?string
    {
        if ($pagina === null) {
            return null;
        }

        if (is_subclass_of($pagina, PaginaDeResource::class)) {
            $resource = $pagina::getResource();
            $index    = $resource::getPages()['index'] ?? null;

            return $index?->getPage() === $pagina && is_subclass_of($resource, Resource::class) ? $resource : null;
        }

        return is_subclass_of($pagina, Page::class) ? $pagina : null;
    }

    /** A chave D6: `{painel}|{FQCN}|{organização ou vazio}`. */
    private static function chave(Panel $painel, string $destino, ?Tenant $organizacao): string
    {
        return $painel->getId().'|'.$destino.'|'.$organizacao?->getKey();
    }

    /**
     * O rótulo do destino no slideover: painel — organização (no painel com tenancy) — grupo —
     * tela (RQ-31). Destinos homônimos de painéis ou organizações diferentes se distinguem pelo
     * começo do rótulo.
     *
     * @param  class-string<resource>|class-string<Page>  $destino
     */
    private static function rotuloNoCatalogo(Panel $painel, ?Tenant $organizacao, string $destino): string
    {
        $grupo = $destino::getNavigationGroup();

        $segmentos = [
            Paineis::rotulo($painel),
            $organizacao === null ? '' : self::textoDoRotulo(Filament::getTenantName($organizacao)),
            match (true) {
                is_string($grupo)                                           => $grupo,
                $grupo instanceof HasLabel                                  => self::textoDoRotulo($grupo->getLabel()),
                $grupo instanceof UnitEnum                                  => (string) ($grupo->value ?? $grupo->name),
                default                                                     => '',
            },
            self::textoDoRotulo($destino::getNavigationLabel()),
        ];

        return implode(' — ', array_filter($segmentos, static fn (string $segmento): bool => $segmento !== ''));
    }

    /** Rótulo do Filament 5.10 (`string|Htmlable`) como texto — `Htmlable` vira o HTML dele, como o Blade faria. */
    private static function textoDoRotulo(string|Htmlable|null $rotulo): string
    {
        return $rotulo instanceof Htmlable ? $rotulo->toHtml() : (string) $rotulo;
    }

    /**
     * A organização com que o catálogo avalia o painel: `null` em painel sem tenancy; a
     * organização aberta quando o painel com tenancy é o corrente e o usuário a acessa; `false`
     * (painel fora do catálogo) em qualquer outro caso (D7, RQ-25).
     */
    private static function organizacaoDoCatalogo(Panel $painel, User $usuario): Tenant|false|null
    {
        if (! $painel->hasTenancy()) {
            return null;
        }

        $aberta = Filament::getTenant();

        if (Filament::getCurrentPanel()?->getId() !== $painel->getId() || ! $aberta instanceof Tenant) {
            return false;
        }

        return $usuario->canAccessTenant($aberta) ? $aberta : false;
    }

    /**
     * A organização do favorito: `null` em painel sem tenancy (a parte precisa vir vazia), a
     * organização quando o usuário a acessa, `false` quando o favorito deve ser negado.
     *
     * A organização aberta no request é reaproveitada sem consulta — é o caso comum no `/app`.
     */
    private static function organizacaoDoDestino(Panel $painel, string $idDaOrganizacao, User $usuario): Tenant|false|null
    {
        if (! $painel->hasTenancy()) {
            return $idDaOrganizacao === '' ? null : false;
        }

        if ($idDaOrganizacao === '' || ! ctype_digit($idDaOrganizacao)) {
            return false;
        }

        $aberta      = Filament::getTenant();
        $organizacao = $aberta instanceof Tenant && (string) $aberta->getKey() === $idDaOrganizacao
            ? $aberta
            : Tenant::query()->find((int) $idDaOrganizacao);

        if (! $organizacao instanceof Tenant || ! $usuario->canAccessTenant($organizacao)) {
            return false;
        }

        return $organizacao;
    }
}
