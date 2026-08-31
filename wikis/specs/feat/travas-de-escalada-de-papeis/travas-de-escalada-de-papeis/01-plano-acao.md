# Plano de Ação — Travas de escalada na tela de papéis e no login social

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/aderencia-ao-blueprint/aderencia-ao-blueprint/` (auditoria Blueprint anterior, que instalou `PoliciesDeVendor` e a norma) e `wikis/specs/feat/auditoria-de-seguranca/travas-de-exclusao-e-upload-anonimo/` (mesma família: achado de autorização vindo de auditoria)
- **Motivo**: nova rodada da mesma auditoria, agora com o teto de escalada de `master_global` já instalado no working dir. A auditoria mostrou que o teto é contornável pela tela de papéis, que casa por **nome**.
- **Toca infra compartilhada?**: **sim** — `AdministradorDaInstalacao` é consumida por três telas de concessão (`Admin\UserResource`, `ConviteForm`, `Admin\ListConvites`) e `RolePolicy` decide toda ação sobre papel. Regressão obrigatória contra `tests/Kit` e `tests/Tenancy`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Nome reservado do super-admin | 2 | `->rule()` no `name` do `RoleResource` |
| RQ-02 | Papel super-admin não é editável por não-master | 1 | `RolePolicy::update()` |
| RQ-03 | Papel super-admin não é excluível por não-master | 1 | `RolePolicy::delete()` + `forceDelete()` |
| RQ-04 | Concessão só de papel de painel que o operador acessa | 3 | `AdministradorDaInstalacao::recortarConcessao()`/`regraDeConcessao()` |
| RQ-05 | Vale nos três caminhos de concessão | 3 | os três já chamam os mesmos dois métodos — nenhuma tela muda |
| RQ-06 | Conta existente não aceita convite na volta do provedor | 4 | remove as duas chamadas de `aceitarConviteSeHouver()` |
| RQ-07 | `?token=` continua criando conta nova | 4 | `criarContaPorConvite()` intocado |
| RQ-08 | Convite não é consumido por conta indisponível | 4 | consequência direta do passo 4 |
| RQ-09 | Link de confirmação de vínculo é de uso único | 5 | `Cache::add()` sobre a assinatura |

## Objetivo

Fechar os cinco achados da rodada de auditoria `filament-security-audit` do Filament Blueprint. Os dois primeiros são a mesma tela e a mesma classe de defeito: o kit resolve "quem é o administrador da instalação" pelo **nome** de um papel, e a tela de papéis do `/admin` edita nome, exclui registro e cunha papel de qualquer painel — logo o teto de escalada instalado na ficha de usuário e no convite é contornável por quem tem `Update:Role`. Os três últimos são do login social: um convite queimado por conta indisponível, um aceite sem consentimento no início do fluxo OAuth e um link assinado reutilizável.

## Contexto

O papel `admin` recebe a matriz inteira do painel `/admin` (`database/seeders/PapeisSeeder.php:58-59`), e `RoleResource` é uma tela do `/admin`. Isso dá a `admin` `Create:Role`, `Update:Role` e `Delete:Role`. `User::isMasterGlobal()` resolve pelo nome de config (`app/Models/User.php:356`) e o `Gate::before` do kit (`app/Providers/KitServiceProvider.php:197`) dá tudo a quem tem esse papel. Duas edições de nome bastam.

## Análise dos Arquivos Existentes

### `app/Policies/RolePolicy.php`

Todos os métodos delegam a `can('{Ação}:Role')`. `update()`, `delete()`, `forceDelete()` e `restore()` já recebem `Role $role` — o registro está à mão e nenhum deles olha para ele. É o ponto único que Filament consulta em `EditAction`, `DeleteAction` e `DeleteBulkAction`.

### `app/Filament/Admin/Resources/Roles/RoleResource.php`

`TextInput::make('name')` (`:109`) tem só `unique()` + `required()` + `maxLength()`. A tela é do Shield (`HasShieldFormComponents`), e a matriz é montada por `Paineis::resources()`.

### `app/Support/AdministradorDaInstalacao.php`

Já tem, do trabalho não commitado desta família: `operadorPodeConceder()`, `recortarConcessao(Builder)` e `regraDeConcessao()`. Os três caminhos de concessão consomem os dois últimos — é onde a trava de painel entra sem tocar em tela nenhuma.

### `app/Http/Controllers/Auth/LoginSocialController.php`

`aceitarConviteSeHouver()` (`:604`) é chamada em dois pontos, ambos de conta **existente**: `:189` (ramo do vínculo) e `:203` (ramo do e-mail). O ramo de conta nova usa `criarContaPorConvite()`, que é outro caminho e não muda. `pedirConfirmacaoDoVinculo()` (`:445`) emite `URL::temporarySignedRoute` de 30 min e `confirmarVinculo()` (`:498`) faz `vincular()` + `Auth::login()` sem consumir nada.

## Autorização

- **Policies**: `RolePolicy::update()`, `delete()`, `forceDelete()`, `restore()` passam a negar o registro do papel super-admin para quem não é `master_global`.
- **Gates**: nenhum novo. O `Gate::before` do `master_global` continua vencendo tudo — é ele que garante que o dono da instalação não se tranca fora.
- **Middleware**: nenhum.

## Rotas

Nenhuma rota nova. `routes/web.php:63-69` (login social) continua como está — a decisão do solicitante mantém o `?token=`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `Admin\Roles\RoleResource` (create/edit) | Filament | `/admin/shield/roles/{create,edit}` | digita `name`; salva | Não |
| `Admin\Roles\ListRoles` | Filament | `/admin/shield/roles` | Edit/Delete/bulk delete sobre o papel super-admin | Não |
| `Admin\Users\EditUser` | Filament | `/admin/users/{id}/edit` | escolhe papéis e organizações | Não |
| `Admin\Convites\CreateConvite` + `ListConvites` (convite em massa) | Filament | `/admin/convites/create`, `/admin/convites` | escolhe papel do convite | Não |
| Volta do provedor social | Controller | `/auth/social/{provedor}/retorno` | nenhuma (redirect) | Não |

**Gate de CT-B**: nenhum cenário afirma sobre JavaScript, console, tema, acessibilidade ou layout. Validação de formulário, autorização de ação e gravação são **teste de componente Livewire** e ficam no `04`. **Sem CT-B nesta wiki.**

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`cadastro-social-por-convite-e-organizacao`**: o passo 4 remove o aceite automático por conta existente. Os cenários daquela wiki que afirmam "conta existente + convite → membro na volta do provedor" mudam de oráculo: o convite fica **pendente** e é aceito em `ConvitesRecebidos`. Ajuste dos testes é parte do passo 4, e o desvio vai para o `03-progresso.md` daquela wiki também.
- **`vinculo-de-provedor-social`**: o link de confirmação passa a ser de uso único; um teste que reutilize o mesmo link duas vezes muda de oráculo.
- **`perfis-e-permissoes` / `admin-da-organizacao`**: a trava de painel na concessão pode reprovar cenários que hoje concedem papel de `/infra` a partir de um operador `admin`. Verificar antes de dar como regressão.

## Rollback

- Sem migration. Reverter é `git revert` do commit: a trava de painel volta a `recortarConcessao()` só por nome, a policy volta a olhar só a permission e o `aceitarConviteSeHouver()` volta ao controller.
- O uso único do vínculo vive no cache; limpar o cache libera links ainda dentro da janela de 30 min (aceitável — a assinatura continua expirando sozinha).

## Dependências

Nenhuma nova.

## Riscos

- **Trancar o dono da instalação fora**: se a trava do papel super-admin fosse por permission em vez de `Gate::before`, um `master_global` sem `Update:Role` não editaria o próprio papel. Mitigação: a guarda pergunta `isMasterGlobal()`, e o `Gate::before` do Shield já entrega tudo a ele.
- **Instalação single-tenant sem `admin_app`**: a trava de painel usa os painéis dos papéis do operador; num kit recém-instalado o primeiro usuário é `master_global` e passa por tudo. Sem risco de bloqueio no dia 1.
- **Papel sem painel** (`painel = null`): permitido a qualquer operador por decisão declarada (RQ-04, ambiguidade 2). Se essa premissa estiver errada, é um `where` a mais.

## Channel de Log da Feature

`config/logging.php` já tem o channel `autenticacao`, e é o channel das três telas de concessão e do login social (`grep 'Log::channel(.autenticacao' app/` → `UserResource::gravarPapeis()`, `LoginSocialController`). **Nenhum channel novo**: criar `travas-de-escalada` espalharia a mesma decisão de acesso por dois arquivos de log. Todos os logs novos vão em `autenticacao`, no padrão `[Classe@Método] mensagem | parâmetro`.

## Estrutura de Implementação

### 1. `RolePolicy` guarda o registro do papel super-admin (RQ-02, RQ-03)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Policies/RolePolicy.php`
- Em `update()`, `delete()`, `forceDelete()` e `restore()`: `return $authUser->can('{Ação}:Role') && AdministradorDaInstalacao::papelEditavelPor($role, $authUser);`
- **Path**: `app/Support/AdministradorDaInstalacao.php` — novo método:
  ```php
  public static function papelEditavelPor(Role $papel, ?Authenticatable $operador = null): bool
  ```
  Devolve `true` quando o papel **não** é o super-admin; quando é, só para operador `master_global`.
- Por que na policy e não no Resource: `EditAction`, `DeleteAction` e `DeleteBulkAction` do Filament consultam a policy do registro; `->strictAuthorization()` (já ligado nos três painéis) faz o framework **lançar** se faltar método, então não há caminho de falha aberta.
- **Logs**: nenhum. Policy é consultada em render de tabela (uma vez por linha); logar ali produz ruído por página, não rastro.

### 2. Nome do papel super-admin é reservado (RQ-01)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, `TextInput::make('name')`
- Acrescentar `->rule(fn (): Closure => AdministradorDaInstalacao::regraDeNomeDePapel())` — a regra recusa `name` igual a `AdministradorDaInstalacao::papel()` quando o operador não é `master_global`, com mensagem "Este nome é reservado ao administrador da instalação."
- Cobre criação **e** renomeação. O `unique()` existente já impede duplicar o nome quando o papel original ainda existe; a regra fecha o caminho de renomear o original primeiro.
- **Logs**: `Log::channel('autenticacao')->warning('[RoleResource@name] Nome reservado recusado | operador: {id}', [...])` dentro da regra, quando reprova — é tentativa de escalada, não erro de digitação comum.

### 3. Concessão limitada aos painéis do operador (RQ-04, RQ-05)

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Support/AdministradorDaInstalacao.php`
- Critério (ver Q1 do `00-requisito.md`, que corrigiu a letra da decisão original): não-master concede papel **sem painel**, papel do **painel de negócio** (o `->default()` do kit) e papel de painel que ele **próprio acessa**.
- `recortarConcessao(Builder $papeis)`: além do `where('name', '!=', papel())` já existente, para operador que não é `master_global` acrescentar `->where(fn ($q) => $q->whereNull('painel')->orWhereIn('painel', self::paineisConcedíveis()))`.
- `regraDeConcessao()`: mesma restrição via `->where(...)` do `Rule::exists`.
- Novo método privado `paineisConcedíveis(): array` — o id do painel default (`Filament::getDefaultPanel()->getId()`) mais o `painel` distinto dos papéis do operador em **qualquer** contexto (`papeisEmQualquerContexto()`), sem `null`.
- **Nenhuma tela muda**: `Admin\UserResource` (`Select roles` + `gravarPapeis()`), `ConviteForm` e `Admin\ListConvites` já chamam esses dois métodos.
- O `orWhereIn` fica **agrupado** dentro de uma closure — `orWhere` de topo em customização de query é fronteira falsa (norma D1 do Blueprint, e `Convite.php:468-471` é o precedente do kit).
- **Logs**: `gravarPapeis()` já registra o descarte (`'ids_enviados'`/`'ids_aceitos'`); a mensagem passa a dizer "papel fora do alcance do operador descartado", porque agora o descarte tem duas causas.

### 4. Convite não é aceito na volta do provedor por conta existente (RQ-06, RQ-07, RQ-08)

> Skills: `laravel-specialist`, `pest-testing`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`
- Remover a chamada de `:189` (ramo do vínculo) e a de `:203` (ramo do e-mail), e remover o método `aceitarConviteSeHouver()` (`:604`) — sem chamador, ele é código morto.
- `criarContaPorConvite()` e o ramo de conta nova **não mudam**: é a RQ-07.
- Consequência da RQ-08: como só o ramo de criação consome convite, e ele só roda quando não há conta local, conta desativada/soft-deleted/pendente nunca queima convite.
- Ajustar os testes da wiki `cadastro-social-por-convite-e-organizacao` cujo oráculo era o aceite automático (`tests/Kit/CadastroSocialPorConviteTest.php`, `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php`): o convite fica `aceito_em = null` e a pessoa **não** vira membro na volta.
- **Logs**: remover os dois logs de dentro do método removido. Acrescentar, no ramo de conta existente com token no contexto: `Log::channel('autenticacao')->info('[LoginSocialController@retorno] Convite pendente não consumido no fluxo social — aceite pela tela | user: {id} - convite: {id}', [...])` — sem esse rastro, some a explicação de por que o convite continua pendente.

### 5. Link de confirmação de vínculo é de uso único (RQ-09)

> Skills: `laravel-specialist`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`, `confirmarVinculo()`
- Antes de `vincular()` + `Auth::login()`: `Cache::add('vinculo-social:'.hash('sha256', (string) $request->query('signature')), true, now()->addMinutes(30))`. `add()` é atômico e devolve `false` quando a chave já existe — segunda tentativa cai em `recusar('Este link já foi usado.')`.
- Sem coluna e sem migration: a janela de reuso é a mesma da assinatura (30 min), então cache com TTL igual cobre exatamente o período em que o link vale.
- **Logs**: `Log::channel('autenticacao')->warning('[LoginSocialController@confirmarVinculo] Link de confirmação reutilizado | user: {id} - provedor: {p}', [...])` no caminho recusado.

### 6. Verificação e regressão

- `vendor/bin/pint --dirty --format agent`
- `composer types:check`
- `php artisan test --testsuite=Kit,Tenancy --compact`
- Regressão obrigatória (a wiki toca infra compartilhada): `tests/Tenancy/PapeisPorOrganizacaoTest.php`, `tests/Kit/CadastroSocialPorConviteTest.php`, `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` e a suíte de convites.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**. A escada aqui já decidiu três vezes:
> 1. A trava do papel super-admin vai na **policy**, não em cada Action — um lugar que todos os caminhos consultam.
> 2. A trava de painel vai nos **dois métodos de `AdministradorDaInstalacao`** que as três telas já chamam — zero mudança de tela.
> 3. O uso único do link vai em `Cache::add()`, não em coluna nova — o TTL da assinatura já define a janela.
>
> Atalhos deliberados marcados com `ponytail:`.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-casos-de-teste-browser.md` — nenhum cenário exige navegador (ver `## Superfície de UI`).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --compact`
- [ ] Mutação manual: remover a guarda da policy → CT de RQ-02/RQ-03 reprovam

## Commits

- `🔒 fix(seguranca): papel do administrador da instalação não é editável, renomeável nem excluível por não-master`
- `🔒 fix(seguranca): concessão de papel limitada aos painéis do operador`
- `🔒 fix(seguranca): convite não é consumido na volta do provedor social por conta existente`
- `🔒 fix(seguranca): link de confirmação de vínculo social é de uso único`
- `📝 docs(wiki): travas-de-escalada-de-papeis`
