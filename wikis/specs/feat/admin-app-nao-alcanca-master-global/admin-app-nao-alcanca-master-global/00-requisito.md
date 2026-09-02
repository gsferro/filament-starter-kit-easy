# Requisito — O administrador da organização não alcança quem governa a instalação

## Fonte

- **Origem**: mensagem do mantenedor no chat, via `/feature-wiki`, mais duas respostas a perguntas de
  esclarecimento na mesma sessão
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **alta** — texto escrito, e as duas ampliações de escopo foram decisões explícitas

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> em usuarios com permissão Administrador App, dentro do painel app, podem ver e gerir os usuários, porem, ele não pode NUNCA ver ou alterar um usuário com permissão de master_global.
> - ele so pode ver/editar os usuários do tenant dele

**Respostas às perguntas de esclarecimento (2026-09-02):**

> **Usuários com papel GLOBAL de instalação que não é master_global (`admin` do /admin, `infra`) mas
> vinculados à organização: o admin_app pode vê-los e editá-los no /app?**
> Não — quem tem qualquer papel de instalação fica fora

> **"Não pode NUNCA ver" — o master_global some inteiramente do /app (listagem, busca ⌘K, badge de
> contagem, URL direta = 404) ou aparece bloqueado?**
> Some inteiramente

> **Nome da branch/wiki?**
> feat/admin-app-nao-alcanca-master-global

## O que foi medido antes de escrever (estado, não requisito)

| Fato | Onde |
|---|---|
| O recorte por organização **já existe**: `UserResource::getEloquentQuery()` do `/app` faz `whereHas('tenants', …)` e **fecha** (`1 = 0`) sem organização corrente | `app/Filament/App/Resources/Users/UserResource.php` |
| Quatro consumidores passam por esse método: listagem, route binding (404 na URL direta), busca ⌘K e badge do menu | idem, docblock de `getEloquentQuery()` |
| Provado por teste: `lista apenas os usuarios da organizacao corrente`, `nega o acesso direto ao registro de usuario de outra organizacao` | `tests/Tenancy/AdminDaOrganizacaoTest.php:113-147` |
| **O que não existe**: nenhuma barreira sobre **quem** o usuário listado é. Um `master_global` vinculado à organização aparece na lista e a `EditAction` abre a ficha dele — nome, e-mail e **senha** editáveis pelo `admin_app` | `UserResource::table()` → `EditAction::make()`; `form()` sem guarda de alvo |
| Isso acontece em **toda instalação**: `TenantsSeeder` vincula o `admin@example.com` (`master_global`) a cada organização criada | `database/seeders/TenantsSeeder.php:32` |
| Papéis de instalação são os de `roles.painel` `admin`, `infra` e **nulo** (`master_global`, que entra pelo `Gate::before`); todos atribuídos no contexto global (`team_id = Tenant::CONTEXTO_GLOBAL`) | `PapeisSeeder`, `User::canAccessPanel()`, `User::isMasterGlobal()` |
| A escalada de **papel** já está travada (`gravarPapeis()` só grava papel `painel = app`); a de **conta** (trocar a senha de quem governa a instalação) não | `UserResource::gravarPapeis()`, wiki `travas-de-escalada-de-papeis` |

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O `admin_app`, no painel `/app`, vê e gere (cria, edita) os usuários | "em usuarios com permissão Administrador App, dentro do painel app, podem ver e gerir os usuários" | funcional (já existe; regressão) |
| RQ-02 | O `admin_app` **não vê** — em nenhuma superfície do `/app`: listagem, busca ⌘K, badge, URL direta — usuário que tenha papel de instalação (`master_global`, e por decisão do solicitante também `admin` e `infra`) | "ele não pode NUNCA ver […] um usuário com permissão de master_global" + "quem tem qualquer papel de instalação fica fora" + "Some inteiramente" | autorização |
| RQ-03 | O `admin_app` **não altera** usuário com papel de instalação — nem por tela, nem por caminho que contorne a listagem | "não pode NUNCA […] alterar um usuário com permissão de master_global" | autorização |
| RQ-04 | O `admin_app` só vê e edita usuários **vinculados à sua organização** | "ele so pode ver/editar os usuários do tenant dele" | autorização (já existe; regressão) |

## Ambiguidades e Perguntas Abertas

- **RQ-02/RQ-03, "papel de instalação"** — resolvido pelo solicitante: `master_global` **e** qualquer
  papel de painel sem tenancy (`admin`, `infra`). Operacionalmente: papel cujo `roles.painel` não é
  `app` (inclui nulo), atribuído no **contexto global**. Um papel `admin` atribuído dentro de uma
  organização não governa a instalação (`canAccessPanel()` já o ignora) e **não** entra na regra.
  - **Assumido**: a regra lê a atribuição no contexto global, como `isMasterGlobal()` já faz.
  - **Se negado** (qualquer atribuição, em qualquer contexto): o predicado perde o filtro de
    contexto; CT muda de fixture.
- **RQ-02, o próprio `admin_app` com papel de instalação** — quem é `admin_app` numa organização **e**
  `admin` da instalação deixa de ver a si mesmo na lista do `/app`. Consequência aceita: quem governa
  a instalação se administra pelo `/admin`. Registrada em ADR-01.
- **"Gerir"** — o `/app` já não exclui, não desativa, não impersona (wikis anteriores). Gerir aqui é
  criar e editar. Nada muda nisso.
- **Convite** — o `admin_app` pode convidar o e-mail de alguém que governa a instalação para a sua
  organização? Aceitar o convite vincularia a pessoa à organização, onde ela ficaria **invisível** ao
  `admin_app` pela RQ-02. Não há escalada (o convite só concede papel de `app`). **Fora desta
  entrega**; registrado para o solicitante decidir se quer barrar o convite também.

## Fora de Escopo (declarado)

- O painel `/admin` e o `UsersRelationManager` da organização no `/admin`: quem está lá governa a
  instalação, e a regra é do `/app`.
- Convite de quem governa a instalação para uma organização (ver ambiguidade acima).
- Exclusão, desativação, impersonação a partir do `/app` — já negadas por wikis anteriores
  (`travas-de-exclusao-e-upload-anonimo`, `status-e-exclusao-logica-de-usuario`).
- Escalada de **papel** — já travada (`gravarPapeis()`, wiki `travas-de-escalada-de-papeis`).
- Kit **sem** tenancy: o `UserResource` do `/app` nem se registra (`canAccess()` exige
  `kit.tenancy.enabled`); o `admin_app` só existe com tenancy.
