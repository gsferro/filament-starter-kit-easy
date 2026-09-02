---
title: "Multi-tenancy (opt-in)"
parent: Recursos
grand_parent: Português
nav_order: 1
---

# Multi-tenancy (opt-in)

O kit nasce **single-tenant**. Um comando liga o modo multi-tenant — e quem não precisa não paga nada por ele:

```bash
php artisan kit:tenancy          # liga o modo
php artisan kit:tenancy --demo   # liga + cria um cenário de demonstração
php artisan kit:tenancy --force  # confirma a recriação do banco sem perguntar
```

> O `--demo` também escreve `KIT_DEMO=true` no `.env`. É essa chave que faz o resource de exemplo
> **Projetos** aparecer no `/app` — sem ela o painel de negócio continua vazio, que é o desenho do
> kit. Para tirar a demo da vista sem apagar nada, `KIT_DEMO=false`; para removê-la de vez, apague
> os arquivos que o comando lista ao final.

| Painel | Com o modo ligado |
|---|---|
| **App** | vira `/app/{tenant}`. O usuário só enxerga os tenants a que está vinculado, e ganha a **administração da própria organização** |
| **Admin** | ganha o cadastro de tenants e o **vínculo de usuários** — não é escopado, quem administra vê todos |
| **Infra** | inalterado: saúde, filas e logs são da instalação, não de um cliente |

## Quem administra uma organização não administra a instalação

Os cinco papéis do kit, e o que cada um significa com o modo ligado:

| Papel | Painel | Contexto da atribuição | O que faz |
|---|---|---|---|
| `master_global` | todos | global | vence qualquer permissão, por `Gate::before` |
| `admin` | `/admin` | global | usuários, papéis e permissões da **instalação** |
| `infra` | `/infra` | global | saúde, filas, logs, auditoria, comandos |
| `admin_app` | `/app` | **a organização** | usuários e convites **da organização dele** |
| `panel_user` | `/app` | a organização | usa o negócio; não vê a administração |

`admin_app` é a persona que o modo multi-tenant cria: alguém que administra **uma** organização sem administrar o sistema. Dentro de `/app/{slug}` ele ganha **Usuários** e **Convites**, recortados àquela organização — e nada além disso. Ele não entra em `/admin` nem `/infra`, leva 404 no painel de outra organização, não alcança usuário de fora nem por URL direta, não cria nem edita papéis (só atribui, e só papéis do painel `/app`), não exclui usuário — o delete apagaria a pessoa de **todas** as organizações — e o convite que ele cria nasce carimbado com a organização dele, ignorando o formulário. E ele **não vê nem edita quem governa a instalação**: `master_global`, `admin`, `infra` ou qualquer papel de painel sem tenancy somem da lista, da busca e do badge, e a URL direta responde 404 — mesmo que a pessoa esteja vinculada à organização.

O papel só existe com a tenancy ligada, e a concessão é em `/admin` → organizações → **Usuários vinculados** → *Papéis nesta organização*. **Não** pelo cadastro do usuário: ali a atribuição vai para o contexto global e a pessoa entra no `/app` sem enxergar nada. A receita completa, com o sintoma, está em [`wikis/receitas.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/receitas.md#promover-alguém-a-admin-de-uma-organização).

## Código em inglês, interface no seu idioma

O código segue o vocabulário da API do Filament — model `Tenant`, tabela `tenants`, `getTenants()`, `canAccessTenant()` — para que a documentação oficial se leia sem tradução mental. **O que o usuário vê é configurável**, e nasce como "Organização":

```php
// config/kit.php
'tenancy' => [
    'label'        => 'Empresa',    // Organização · Cliente · Escola · Unidade · Loja
    'label_plural' => 'Empresas',
    'slug'         => 'empresas',   // /admin/empresas
],
```

As mesmas quatro entradas existem no `.env`, como semente e plano B: `KIT_TENANCY` (a flag, escrita
pelo `kit:tenancy`), `KIT_TENANCY_LABEL`, `KIT_TENANCY_LABEL_PLURAL` e `KIT_TENANCY_SLUG`.

## Nas suas models

Toda model do negócio usa a trait do kit:

```php
use App\Traits\BelongsToTenant;

class Projeto extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome'];   // `tenant_id` fora: a trait preenche
}
```

Ela dá a relação `tenant()`, um **escopo global** e o preenchimento automático de `tenant_id`. O escopo importa porque o Filament só recorta o que passa por um Resource — job, comando, listener e API ficariam de fora, e é aí que dado de um cliente vaza para outro.

> ⚠️ **`kit:tenancy` recria o banco.** Ele liga `permission.teams`, e a migration do spatie só cria as colunas de tenant se a flag estiver ativa **antes** do migrate. Por isso exige árvore git limpa, confirmação explícita e roda `migrate:fresh --seed`. **A hora de rodar é o dia 1 do projeto.** O caminho detalhado — inclusive papéis globais × por tenant e `scopedUnique()` — está em [`wikis/arquitetura.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/arquitetura.md#multi-tenancy-opt-in).

