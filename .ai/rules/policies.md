---
paths:
  - 'app/Policies/**'
---

# Policies

## Policy para modelo de VENDOR precisa de Gate::policy() — o Laravel não a descobre
O Laravel descobre policy por convenção só para `App\Models\X` → `App\Policies\XPolicy`. Para modelo de vendor (`Tapp\FilamentAuditing\Models\Audit`) ele procura `Tapp\...\Models\Policies\AuditPolicy`, não acha, e `Gate::getPolicyFor()` devolve null. A `App\Policies\AuditPolicy` que você escreveu não é consultada por nada.

Medido na auditoria de aderência ao Blueprint: OITO resources de vendor abriam com `ViewAny:X` revogada, enquanto `/admin/users` (modelo do kit) fechava com 403. Permissão no banco, checkbox em /admin/shield/roles, e nada decidindo.

Toda policy de modelo de vendor entra no mapa de `App\Support\PoliciesDeVendor` — registro explícito por `Gate::policy()`. NÃO use `FilamentShield::enforcePolicies()`: ele lê `getResources()`, que é `once()` por processo, e o primeiro painel a bootar vence — os outros ficam sem registro em silêncio.

E dois irmãos do mesmo defeito, pegos pelo mesmo sweep: resource de vendor pode ter `$shouldSkipAuthorization = true` (o do Composer tinha — subclasse no kit com `false` E a página apontando para a subclasse, senão `CanAuthorizeResourceAccess` autoriza pela classe do vendor); e sobrescrever `canAccess()` num resource sem `&& parent::canAccess()` desliga a policy para o índice (o AiRun fazia isso).

Enforço: `tests/Kit/PermissoesDeResourcesTest.php` — todo resource dos painéis globais tem policy registrada e fecha com ViewAny revogada. Resource novo fica vermelho lá com o nome.
