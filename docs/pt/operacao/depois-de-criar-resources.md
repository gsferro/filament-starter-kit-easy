---
title: "Depois de criar seus Resources"
parent: "Operação"
grand_parent: "Português"
nav_order: 4
---

# Depois de criar seus Resources

```bash
php artisan make:filament-resource Produto --panel=app
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

## Pacote novo com Resource: a policy precisa ser registrada

O Laravel descobre policy por convenção só para `App\Models\*`. Um resource de **pacote** — a trilha
de auditoria, os logs de e-mail, as filas — tem modelo em namespace de vendor, e a
`App\Policies\XPolicy` que você escrever para ele **não é consultada por nada** até alguém chamar
`Gate::policy()`. A permissão aparece na tela de papéis, e não decide.

Foi assim que o kit ficou por várias versões, e a auditoria de aderência ao Blueprint (v0.21) pegou:
oito telas do `/infra` e do `/admin` abriam com a permissão revogada. A correção é
`App\Support\PoliciesDeVendor`, um mapa `modelo => policy` registrado no boot. Ao instalar um pacote
com resource, acrescente a linha lá — e confira duas coisas no resource do pacote:

- `$shouldSkipAuthorization = true` desliga a policy inteira (o do Composer Release tinha; o kit o
  subclassifica com `false` **e** com a página apontando para a subclasse);
- `canAccess()` sobrescrito sem `&& parent::canAccess()` desliga a policy só para o índice.

`tests/Kit/PermissoesDeResourcesTest.php` reprova resource novo sem policy registrada e resource que
abre com `ViewAny` revogada — com o nome do resource.

**Os dois, nesta ordem, sempre.** O primeiro roda `shield:generate --all` em **cada** painel e escreve as policies; o segundo recorta a matriz pelo painel em que o Resource está registrado e devolve as permissões aos papéis. Só o primeiro cria a permission e não a entrega a ninguém — a tela continua em 403 para quem não é `master_global`. Os dois são idempotentes: rodar de novo é operação normal.

## Page, Widget e Action novos

Resource é o caso fácil: os dois seeders resolvem. As outras três famílias exigem uma linha de código,
porque os defaults do Filament são **permissivos** — o vendor diz isso em comentário, em
`Pages/Concerns/CanAuthorizeAccess.php` (`canAccess()` retorna `true`), em `Widget.php` (`canView()`
retorna `true`) e em `Actions/Concerns/CanBeAuthorized.php` (autorização default `null`, liberada).

O Shield **gera** `View:{Page}` e `View:{Widget}` por descoberta, o `PapeisSeeder` **entrega** aos
papéis do painel e a tela de papéis **mostra** o checkbox — mas nada disso faz a permissão ser
consultada. Sem o trait, desmarcar o checkbox não muda nada.

```php
// Page de painel nova
use App\Filament\Concerns\ExigePermissaoDaTela;

class MinhaPage extends Page
{
    use ExigePermissaoDaTela;

    // Regra local (flag de config, tenancy) vai NO HOOK, nunca sobrescrevendo canAccess():
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.minha_flag');
    }
}

// Widget novo
use App\Filament\Concerns\ExigePermissaoDoWidget;

class MeuWidget extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    // Checagem de fonte opcional vai NO HOOK, nunca sobrescrevendo canView():
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(fn (): bool => Schema::hasTable('minha_tabela'), false);
    }
}
```

> ⚠️ **Sobrescrever `canAccess()`/`canView()` na classe desliga a permissão em silêncio.** Método de
> classe vence método de trait, sem erro e sem aviso. É por isso que os dois concerns publicam um
> **hook** para a regra local, e por isso `tests/Kit/PermissoesDeTelasTest.php` e
> `PermissoesDeWidgetsTest.php` têm um caso que percorre TODAS as classes e reprova a que não consulta.

**Action** é declaração explícita, porque o Shield não descobre Action nenhuma:

| A Action é de… | A permissão nasce em | E na Action |
|---|---|---|
| Resource (tabela, header, RelationManager) | `config('filament-shield.resources.manage')` no Resource daquele painel | `->authorize('MinhaAcao:MeuModel')` |
| Page | `config('filament-shield.custom_permissions')` **e** `PapeisSeeder::paineisDasPermissoesCustomizadas()` | `->authorize('MinhaAcao:MeuModel')` |

A segunda linha tem duas metades porque `custom_permissions` **não conhece painel**: sem o mapa do
seeder, a chave nova cai em `admin`, `infra`, `admin_app` **e `panel_user`**. Chave sem entrada no
mapa não vai para papel nenhum (fail-closed) e o caso `CT-19` de
`tests/Kit/PermissoesDeAcoesTest.php` fica vermelho nomeando a chave.

> ⚠️ **Em RelationManager, nem a Action NATIVA está coberta.** `AttachAction`, `DetachAction`,
> `AssociateAction` e `DissociateAction` só checam `isReadOnly()` — o comentário está no
> `getDefaultActionAuthorizationResponse()` do vendor. No kit, o vínculo `tenant_user` que a
> `AttachAction` cria é exatamente o que `User::canAccessTenant()` consulta para liberar
> `/app/{slug}`, então as duas levam `->authorize()`.

**Page e Widget de vendor ficam fora**: são classes de pacote, sem ponto de extensão. A permissão
delas existe no banco e no checkbox, e **não é consultada** — a barreira é `canAccessPanel()` mais os
gates nomeados de `KitServiceProvider` (`ver-logs`, `command-center:access`, `viewPulse`,
`ver-ai-tasks`).

> **RelationManager o Shield não enxerga.** A descoberta dele cobre apenas Resources, Pages e Widgets, então nenhuma permission é gerada e a autorização recai na **policy do model relacionado**. Se esse model já tem Resource em algum painel, não há nada a fazer. Se não tem, crie a policy à mão (`php artisan make:policy`) e declare as chaves em `config('filament-shield.custom_permissions')` **antes** de rodar os seeders — do contrário o RelationManager fica aberto a qualquer um que consiga abrir o Resource pai.

Adicione os dois traits do kit ao que foi gerado:

```php
// No Resource — badge de contagem animado no menu:
use App\Filament\Concerns\BadgeContagemNavegacao;

class ProdutoResource extends Resource
{
    use BadgeContagemNavegacao;
}

// Na List page — lembra a largura das colunas escolhida pelo usuário:
use Asmit\ResizedColumn\HasResizableColumn;

class ListProdutos extends ListRecords
{
    use HasResizableColumn;
}
```

## Badges de contagem

Todos os Resources **do kit** já têm badge no menu (Usuários, Agentes de IA, Execuções de IA). A contagem sai de `getEloquentQuery()`, nunca de `Model::count()`: a query do resource carrega os escopos que valem para aquele painel, e contar direto no model mostraria um número que a listagem não confirma. Zero não vira badge — um "0" cinza em todo item só polui.

Resources de **plugins de terceiros** (Auditoria, Logins, Filas, Pacotes do Composer, Comandos, Papéis do Shield, Onboarding) ficam sem badge: `getNavigationBadge()` é um método estático do resource, e o Filament não oferece API para sobrescrevê-lo de fora — a `ResourceConfiguration` do painel só permite trocar o slug. Dar badge a eles exigiria estender cada resource de vendor e impedir o plugin de registrar o seu, o que quebra a cada atualização do pacote. Se algum for importante no seu projeto, o caminho é esse — resource por resource, conscientemente.

