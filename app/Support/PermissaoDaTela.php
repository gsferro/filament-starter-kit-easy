<?php

declare(strict_types=1);

namespace App\Support;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * "O usuário corrente tem a permissão `View:{Tela}` desta Page?" — para tela que vem de PACOTE.
 *
 * As Pages escritas no kit não precisam disto: elas usam o concern
 * `App\Filament\Concerns\ExigePermissaoDaTela`, que é a trait do Shield com alias. Classe de
 * pacote não aceita `use`, e os pacotes de tela do `/infra` publicam um callback de autorização
 * (`->authorize()`, `->canAccessUsing()`) que recebe um predicado. Este é o predicado.
 *
 * ## Por que perguntar ao Shield em vez de montar a string
 *
 * `'View:'.class_basename($pagina)` é mais curto e é o defeito. A chave sai de
 * `FilamentShield::getDefaultPermissionKeys()` e depende de quatro chaves de
 * `config/filament-shield.php` — `permissions.case`, `permissions.separator`, `pages.prefix` e
 * `widgets.prefix`. O docblock de `App\Support\Paineis` (`:24-29`) já registra que elas
 * "dessincronizam em silêncio", e aqui o sintoma seria o pior possível: a string montada não casa
 * com nenhuma permission, cai no fail-open abaixo e a tela **abre para todos** com o diff
 * parecendo correto.
 *
 * ## Por que não `Paineis::permissoesDe()`
 *
 * `Paineis::mapa()` percorre os TRÊS painéis e, a cada volta, faz
 * `app()->forgetInstance('filament-shield')`, `Facade::clearResolvedInstance('filament-shield')` e
 * `Filament::setCurrentPanel()` (`Paineis.php:129-154`). É correto para semear a matriz de papéis,
 * e é veneno aqui: este método roda dentro de `canAccess()`, que é consultado em laço de render —
 * uma vez por item de navegação, por cartão de hub e por categoria do Spotlight. Trocar o painel
 * corrente e descartar a instância do Shield no meio do render é o mecanismo que o próprio
 * docblock de `Paineis` (`:31-42`) descreve como causa de "6/1/6 nos três painéis". Ver ADR-02 de
 * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
 *
 * ## Fail-open, herdado de propósito
 *
 * `true` aqui significa **"a permissão não opinou"**, não "está liberado". É a mesma semântica de
 * `HasPageShield::canAccess()`
 * (`vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-27`), que cai em
 * `parent::canAccess()` quando a chave não resolve ou quando não há usuário. A chave só não
 * resolve quando o painel corrente não é o da Page — o que em request real não acontece (o
 * middleware `SetUpPanel` fixa o painel antes de qualquer Page ser tocada) e em teste de
 * componente se arranja com `noPainelDoShield()` (`tests/Pest.php:649`).
 *
 * Inverter para fail-closed daria ao kit DUAS semânticas para a mesma pergunta — a trait falhando
 * aberta e o helper falhando fechado — e nenhuma delas seria lembrada na hora de depurar. Ver
 * ADR-03.
 *
 * ## Sem cache
 *
 * A trait do vendor guarda a chave em `static::$pagePermissionKey` porque lá ela é por classe.
 * Aqui o parâmetro varia, e um cache por classe daria a chave da primeira tela a todas as
 * seguintes. É desnecessário de qualquer forma: `FilamentShield::getPages()` já é memoizado com
 * `once()` na instância `scoped` do Shield (`FilamentShield.php:71-74`).
 */
final class PermissaoDaTela
{
    /**
     * @param  class-string  $pagina  FQCN da Page, do jeito que ela está registrada no painel
     */
    public static function permite(string $pagina): bool
    {
        $entidade = FilamentShield::getPages()[$pagina] ?? null;

        $chave = is_array($entidade) && is_array($entidade['permissions'] ?? null)
            ? array_key_first($entidade['permissions'])
            : null;

        $usuario = Filament::auth()?->user();

        return is_string($chave) && $usuario instanceof Authorizable
            ? $usuario->can($chave)
            : true;
    }
}
