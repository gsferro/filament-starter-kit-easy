# Relatório de Auditoria de Segurança — Filament Blueprint

> Produzido com o catálogo `filament-security-audit` do `filament/blueprint` v2.2.0.
> Data: 2026-08-25. Versão auditada: kit v0.19.11 (`851ee89`).
>
> Segue a estrutura que a própria skill especifica (§1 a §5). Auditamos **como a aplicação usa o
> Filament v5**, não o código-fonte do Filament.

## §1. Sumário

Foram executados os **21 checks** do catálogo (A1–A6, B1–B3, C1–C7, D1–D5, E1) contra as três
raízes de código da aplicação (`app/`, `resources/views/`, `config/`) e os **três painéis** do kit:
`/app` (escopado por organização), `/admin` (global) e `/infra` (global, operação).

**Dois achados**, ambos de **controle de acesso**:

| Categoria | Achados |
|---|---|
| A. Controle de acesso | **2** |
| B. Uploads e RCE | 0 |
| C. XSS e injeção | 0 |
| D. Escopo de query, exposição de dados e multi-organização | 0 |
| E. Dependências | 0 |

Nenhum dos dois é explorável por anônimo **com impacto sobre dados** hoje. O F-02 **é** alcançável
por anônimo, e o F-01 depende de uma ação que ainda não foi registrada — mas ambos são travas que a
aplicação **acredita ter** e não tem, o que é a razão pela qual entram como achado e não como dica
de endurecimento.

O que mais chama atenção no resultado é o **B e o C zerados**: os campos de upload do kit já chegam
com allow-list explícita de tipo, teto de tamanho vindo da config e SVG recusado com justificativa
escrita no próprio código; e o único `{!! !!}` sobre conteúdo de usuário já passa por
`html_input: escape`. Isso não é sorte — é resultado das rodadas anteriores, e §3 registra cada um
como `Pass` com o motivo.

## §2. Achados

### [F-01] `canDelete()` e `canDeleteAny()` não autorizam nada no Filament v5

**Check**: A3
**Location**:
- `app/Filament/App/Resources/Users/UserResource.php:98` (`canDelete`) e `:103` (`canDeleteAny`)
- `app/Filament/App/Resources/Convites/ConviteResource.php:174` (`canDelete`) e `:179` (`canDeleteAny`)
- `app/Filament/App/Resources/Users/Pages/EditUser.php:9-11` (o docblock que afirma a trava)

**Component**: `Filament\Resources\Resource\Concerns\HasAuthorization`
**Docs**: https://filamentphp.com/docs/5.x/upgrade-guide#overriding-the-can-authorization-methods-on-a-resource-relationmanager-or-managerelatedrecords-class

**Issue**: os dois resources sobrescrevem `canDelete()` e `canDeleteAny()` devolvendo `false`, e o
docblock do `EditUser` declara que essa é a trava: *"A trava de verdade é
`UserResource::canDelete()`; a ausência aqui é para não haver superfície."*

No Filament v5 esses métodos **não são consultados por nada**. Três medições:

1. `canDelete()` é um invólucro que **lê** a resposta de autorização, não a produz —
   `HasAuthorization.php:154-157` faz `return static::getDeleteAuthorizationResponse($record)->allowed();`
2. quem a **produz** é `getDeleteAuthorizationResponse()` (`HasAuthorization.php:94-97`), que
   consulta a policy;
3. quem **decide** a ação chama a resposta direto, sem passar por `canDelete()`:
   `Resources/Pages/Page.php:313` para a `DeleteAction` e `:329` para a `DeleteBulkAction`.

E a busca por chamadores fecha o caso: `grep -rn "::canDelete(\|->canDelete(\|canDelete()"
vendor/filament/filament/src/ | grep -v "function canDelete"` devolve **zero linhas**. O framework
nunca chama o método que o kit sobrescreveu.

Sobrescrever `canDelete()` portanto não nega nada. O que hoje impede a exclusão é **outra coisa**:
nenhuma `DeleteAction` está registrada em `recordActions()` e o `EditUser` não tem
`getHeaderActions()`. É uma barreira por ausência de superfície, não por autorização — e o
docblock diz o contrário.

Por que isso importa mesmo sem exploração hoje:

- o comentário **instrui** o próximo mantenedor a confiar na trava, então ele adiciona a ação
  achando que está protegido;
- `php artisan make:filament-resource` inclui `DeleteAction` por default — a superfície nasce sozinha
  no próximo scaffold;
- a permissão existe na matriz: `UserPolicy::delete()` devolve `$authUser->can('Delete:User')`, e o
  papel do painel a tem. No instante em que a ação aparecer, a exclusão é permitida.

O ato é global e irreversível: apagar a linha de `users` derruba o vínculo da pessoa com **todas**
as organizações, porque `tenant_user` tem `cascadeOnDelete`. Quem administra UMA organização não
deveria alcançar isso — que é exatamente o que o comentário do kit já dizia querer.

**Fix**: sobrescrever o método que o framework consulta, em ambos os resources:

```php
use Illuminate\Auth\Access\Response;

public static function getDeleteAuthorizationResponse(Model $record): Response
{
    return Response::deny('Excluir usuário é ato global e não se faz a partir de uma organização.');
}

public static function getDeleteAnyAuthorizationResponse(): Response
{
    return Response::deny('Excluir usuário é ato global e não se faz a partir de uma organização.');
}
```

`Response::deny()` e não `bool`: a assinatura exige `Illuminate\Auth\Access\Response`, e a mensagem
aparece para quem tentar, em vez de um 403 mudo. Manter os `can*()` existentes não faz mal — eles
gateiam navegação e busca global —, mas o comentário tem de deixar claro qual dos dois é a trava.

**Verify**: CT-01 a CT-04 do `04-casos-de-teste.md`.

---

### [F-02] Página pública expõe o RPC de upload do Livewire a visitante anônimo

**Check**: A5
**Location**:
- `app/Filament/Pages/BoasVindas.php:54` — servida em `routes/web.php:22` na rota `/`
- `app/Filament/App/Pages/ConvitesRecebidos.php` — mesma falta, dentro do painel autenticado

**Component**: `Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents`
**Docs**: https://filamentphp.com/docs/5.x/advanced/security#restricting-livewire-file-uploads-to-schema-components

**Issue**: `BoasVindas extends CardsPage extends Filament\Pages\Page extends BasePage`, e
`BasePage.php:8,23` compõe `InteractsWithSchemas` — que por sua vez compõe o `WithFileUploads` do
Livewire e **expõe `_startUpload` / `_finishUpload` no componente**.

A rota é pública e anônima por desenho: `Route::get('/', BoasVindas::class)->middleware('panel:app')`
não tem `auth`. O `panel:app` é o alias de `SetUpPanel` e serve para bootar o painel (folha de
estilo, paleta, tema) — não autentica ninguém.

O schema da página não tem **nenhum** campo de upload: é `Section` + `TextEntry`. Ou seja, o RPC
existe e não tem destino legítimo algum. Um visitante sem conta pode iniciar uploads que vão para o
disco temporário do Livewire.

O catálogo do Blueprint manda excluir da busca as classes que estendem `Page`, e a razão que ele dá
é *"Panel resources/pages re-authorize every request, so the trait isn't needed there"*. **A premissa
dessa exclusão não vale aqui**: `BoasVindas` não é página de painel registrada — é uma classe de
página montada numa rota própria, sem `canAccess()` e sem middleware de autenticação. Não há nada
para reautorizar. É por isso que ela aparece como achado apesar de estender `Page`, e é o caso que o
próprio "Flag if" do check nomeia primeiro: *"Chief cases: unauthenticated pages, or components
whose schema has no upload field"* — aqui os **dois** valem ao mesmo tempo.

`ConvitesRecebidos` é o mesmo defeito com gravidade menor: exige sessão, e quem tem sessão já pode
subir arquivo em outras telas do painel. Entra no mesmo achado por ser a mesma correção, conforme a
regra de consolidação da skill.

**Fix**: adicionar o trait nas duas classes de página:

```php
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;

class BoasVindas extends CardsPage
{
    use RestrictsFileUploadsToSchemaComponents;
```

O trait devolve 403 para todo upload cujo destino não seja um campo de upload presente no schema do
componente. Como nenhuma das duas páginas tem campo de upload, o efeito é fechar o RPC por completo,
sem configuração e sem afetar nenhum fluxo existente.

**Verify**: CT-08 a CT-10 do `04-casos-de-teste.md`. (Esta linha dizia CT-05 a CT-07, que são o `/admin` e as regressões — a revisão adversarial pegou.)

## §3. Checks Executados

| Check | Resultado | Motivo |
|---|---|---|
| A1 — bulk delete sem guarda `*Any()` | `Pass` | As policies do Shield têm `deleteAny`/`forceDeleteAny`/`restoreAny` para todo modelo com o par por registro. Os dois `DeleteBulkAction` do `/admin` (`RoleResource.php:259`, `UserResource.php:181`) têm `deleteAny()` correspondente. |
| A2 — import contorna policy | `N/A` | Os importadores do kit não escrevem em modelo com policy `create()`/`update()` de conteúdo — `ImportAction` só aparece nos resources cujo import já roda sob a permissão da tela. |
| A3 — `can*()` sobrescrito não é mais chamado | **Finding** | F-01. |
| A4 — coluna editável inline | `N/A` | Não existe nenhuma `ToggleColumn`/`SelectColumn`/`TextInputColumn`/`CheckboxColumn` na aplicação. |
| A5 — RPC de upload em componente sem campo de upload | **Finding** | F-02. |
| A6 — trabalho antes da autorização | `Pass` | Os dois `mount()` de página customizada (`RegistroPorConvite.php:87`, `TelaBloqueio.php:192`) autorizam **antes** de qualquer efeito: o primeiro recusa por `RegistroAberto::habilitado()` e por organização; o segundo redireciona sem sessão. Nenhum `boot()` da aplicação faz escrita ou dispara evento. |
| B1 — adulteração de caminho em disco compartilhado | `Pass` | Os três campos de upload resolvem para disco **público** (`ConfiguracoesDoKit.php:668`, `TenantForm.php:137`) — que o catálogo manda descartar por já ser endereçável — ou são **Spatie** (`ProjetoResource.php:129`), que é writer seguro. O avatar do Breezy também é `->disk('public')` (`HasMyProfile.php:63`). O disco privado `local`, onde vivem os anexos de Projeto, **não tem nenhum writer não-Spatie**. |
| B2 — upload aceita qualquer tipo | `Pass` | Todos restringem: `->image()` + `mimes` (`ConfiguracoesDoKit.php:657-658`), `acceptedFileTypes(['image/png','image/jpeg','image/webp'])` (`TenantForm.php:135`), e o campo de anexos recusa SVG por regra. O comentário do `TenantForm` documenta por que `->image()` **não** basta ali: ele gera `image/*`, e `image/svg+xml` casa. |
| B3 — nome de arquivo controlado pelo usuário | `Pass` | `grep "preserveFilenames\|getUploadedFileNameForStorageUsing"` não devolve nada. Nomes de armazenamento são aleatórios, então a cadeia do polyglot não fecha nem no disco público. |
| C1 — saída de editor sem sanitização | `Pass` | Não existe `RichEditor` nem `MarkdownEditor` na aplicação. O único `{!! !!}` sobre conteúdo de usuário (`assistente-chat-widget.blade.php:154`) passa `html_input => 'escape'` e `allow_unsafe_links => false`. |
| C2 — HTML cru contorna o sanitizador | `Pass` | Único `HtmlString` é `RoleResource.php:574`, e o valor interpolado é `Utils::showModelPath($entity['modelFqcn'])` — nome de classe vindo da descoberta do Shield, não de entrada de usuário. (E `filament-shield.show_model_path` é `false`.) |
| C3 — esquema de URL inseguro | `Pass` | Todos os `->url()`/`->recordUrl()` devolvem `route(...)` ou `Resource::getUrl(...)`. O único que não é literal (`UltimosUsuariosCadastrados.php:67`) chama `UserResource::getUrl('edit', …)` por dentro. |
| C4 — HTML em rótulo de opção | `N/A` | Nenhum `allowHtml`/`allowOptionsHtml` na aplicação. |
| C5 — `extraAttributes()` sem escape | `Pass` | Os usos são arrays estáticos de classe e diretiva Alpine; nenhum monta nome ou valor a partir de dado de usuário. |
| C6 — mensagem de validação com HTML | `N/A` | Nenhum `allowHtmlValidationMessages`. |
| C7 — entrada de usuário em expressão JS | `Pass` | Único `$this->js()` é `AssistenteChatWidget.php:82`, com a string estática `'$wire.responder()'`. Sem interpolação. |
| D1 — query ignora regra de posse | `N/A` | Nenhuma policy do kit é record-dependente: todas devolvem `$authUser->can('Acao:Modelo')` e não tocam `$record` no corpo. Sem gradiente de posse, o seed do check não acende. A fronteira do kit é a de organização, coberta em D3. Nenhum `orWhere` em customização de query. |
| D2 — atributo sensível exposto ao JS | `Pass` | `users` tem apenas `password` e `remember_token` como sensíveis, e ambos estão em `$hidden` (`User.php:74-77`). O 2FA do Breezy mora em `breezy_sessions`, não em `users`. |
| D3 — modelo não escopado à organização | `Pass` | `Projeto` usa `BelongsToTenant`; `Convite` é escopado explicitamente em `ConviteResource::getEloquentQuery()` com `Filament::getTenant()`. `ai_runs` e `recycle_bin_items` têm `tenant_id` e vivem **só no `/infra`**, painel global de operação onde ver tudo é o desenho — e o acesso à tela passa por `PermissaoDaTela::permite()` (`InfraPanelProvider.php:535`). Nenhum `withoutGlobalScopes()` sem argumento; nenhum `saveQuietly`/`withoutEvents`/`unguarded`. |
| D4 — método de acesso à organização permissivo | `Pass` | `canAccessTenant()` (`User.php:423`) exige pertencimento via `$this->tenants()->whereKey(...)->exists()`; `getTenants()` (`User.php:409`) devolve só os do usuário — a lista completa é atalho do `master_global`; `canAccessPanel()` (`User.php:107`) recusa cadastro pendente **antes** do atalho de master, e o comentário explica que a ordem é a decisão. |
| D5 — `unique`/`exists` sem escopo de organização | `N/A` | Os `->unique()` estão todos no `/admin`, que não é escopado por organização. O `/app` não usa `unique()`/`exists()` — e nos dois pontos onde caberia, o kit documenta em comentário por que **não** usa. |
| E1 — vulnerabilidade conhecida em dependência | `Pass` | `composer audit --format=plain` → *"No security vulnerability advisories found."* |

## §4. Testes Recomendados

Ver `04-casos-de-teste.md`, derivado do `00-requisito.md` pela skill `feature-test-design`. Em
resumo, o que cada achado exige:

**F-01** — o oráculo é a **resposta de autorização**, não a ausência da ação. Um teste que apenas
verifique que a tela não mostra botão de excluir fica verde com o defeito presente, porque o defeito
é justamente que a barreira é a ausência do botão. O caso tem de afirmar sobre
`getDeleteAuthorizationResponse()` — que ela é `denied()` — e sobre o par simétrico
`getDeleteAnyAuthorizationResponse()`. Um caso de mutação fecha: com a correção revertida, o teste
tem de ficar vermelho.

**F-02** — o oráculo é a presença do trait no componente e o 403 no RPC. Verificar por
`class_uses_recursive()` prova a composição sem depender de subir um upload anônimo, que é caro e
frágil; um caso por página, mais um caso que percorre as páginas do kit e falha se alguma página
pública nascer sem o trait — para a correção não envelhecer no próximo `make:filament-page`.

## §5. Dicas de Endurecimento

**Default global de `preventFilePathTampering` ausente.**

- **Condição verificada que torna isto relevante**: existem **três** campos `FileUpload` não-Spatie
  na aplicação (dois do kit e o avatar do Breezy), e
  `grep -rn "preventFilePathTampering" app/Providers` não devolve nada. Hoje os três apontam para o
  disco **público**, o que é o caso benigno — e é por isso que B1 passou.
- **Por que ainda assim vale a dica**: `FILESYSTEM_DISK=local` (`.env.example:41`) e
  `filament.default_filesystem_disk` não é sobrescrito. Um campo `FileUpload` novo que **esqueça** o
  `->disk()` explícito cai no `local` — exatamente o disco onde vivem os anexos privados de Projeto.
  Nesse instante o disco passa a ter alvo (anexo de outra organização) e writer desprotegido, e o
  B1 acende.
- **Onde entraria**: `ConfiguraFilamentGlobal`, junto dos outros `configureUsing()` que já existem
  ali —
  `FileUpload::configureUsing(fn (FileUpload $c) => $c->preventFilePathTampering())`.
- **Por que não é achado de §2**: nenhuma linha da aplicação hoje é vulnerável. A skill é explícita
  em não transformar `Pass` em achado, e em manter §5 restrito a configuração de projeto sem
  `file:line`.
