# Requisito — Identidade visual da organização

## Fonte

- **Origem**: prompt do usuário no chat, invocando a skill `/feature-wiki`
- **Data**: 2026-08-14
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado abaixo verbatim

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos criar um customizador de identidade visual para a Organização, dentro do painel admin
> - cara organização (tenancy) pode ter as imagens, logos, cores personalizados no registro
> - a principio, vamos deixar ele escolher as cores. use a documentação oficial como base: https://filamentphp.com/docs/5.x/styling/colors#introduction
> - uma vez que ele selecione, ao abrir o painel de app, com o tenant correspondente, é carregado as definnições da identidade visual correspondente, para dar uma ideia de customização individualizada por cliente
> - seria ainda melhor, se na tela de lock-screen, eu exibisse a logo do cliente, ao inves da logo da aplicação base, pois eu já sei em qual tenancy ele estaria quando usando o painel app
> - podemos colocar também ali mais definições e informações da organização, porém, isso a cargo do usuário do kit
> - é bom que tenha telas de create e edit, além da view, para assim termos mais opções de evolução. acho que hoje esta apenas abrindo uma modal, é melhor que seja tela completa, seguindo os ritos do Resouce do Filament

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Criar um customizador de identidade visual da organização, dentro do painel `/admin` | "vamos criar um customizador de identidade visual para a Organização, dentro do painel admin" | funcional |
| RQ-02 | Cada organização pode ter imagens, logos e cores próprias, guardadas no registro dela | "cara organização (tenancy) pode ter as imagens, logos, cores personalizados no registro" | funcional |
| RQ-03 | Nesta entrega, o escopo se limita à escolha de **cores** | "a principio, vamos deixar ele escolher as cores" | restrição |
| RQ-04 | Usar a documentação oficial do Filament 5 sobre cores como base | "use a documentação oficial como base: https://filamentphp.com/docs/5.x/styling/colors#introduction" | restrição |
| RQ-05 | Ao abrir o painel `/app` no tenant correspondente, a identidade visual dele é carregada | "ao abrir o painel de app, com o tenant correspondente, é carregado as definnições da identidade visual correspondente" | funcional |
| RQ-06 | Exibir a logo do cliente na tela de lock-screen, em vez da logo da aplicação base | "na tela de lock-screen, eu exibisse a logo do cliente, ao inves da logo da aplicação base" | funcional |
| RQ-07 | Deixar espaço para mais definições e informações da organização, a cargo de quem usa o kit | "podemos colocar também ali mais definições e informações da organização, porém, isso a cargo do usuário do kit" | não-funcional |
| RQ-08 | Ter telas de **create**, **edit** e **view** no Resource | "é bom que tenha telas de create e edit, além da view" | funcional |
| RQ-09 | As telas devem ser **completas**, não modal, seguindo os ritos do Resource do Filament | "acho que hoje esta apenas abrindo uma modal, é melhor que seja tela completa, seguindo os ritos do Resouce do Filament" | funcional |

## Ambiguidades e Perguntas Abertas

### RQ-09 — a premissa do "hoje é modal" está parcialmente errada

**Verificado no código antes de planejar.** O `TenantResource` **já registra páginas completas** de create e
edit (`app/Filament/Admin/Resources/Tenants/TenantResource.php:110-114`):

```php
'index'  => ListTenants::route('/'),
'create' => CreateTenant::route('/create'),
'edit'   => EditTenant::route('/{record}/edit'),
```

E as rotas existem de fato — `/admin/organizacoes/create` e `/admin/organizacoes/{record}/edit` aparecem em
`php artisan route:list`.

O que **não** existe é a página **`view`**. E o que pode estar produzindo a impressão de modal é o
`EditAction::make()` em `TenantsTable.php:38`, declarado sem `->url()`.

**Decidido com o usuário**: verificar por CT-B se aquele `EditAction` abre modal ou navega para a página, e
corrigir só o que estiver de fato divergindo. A página `view` é criada de todo modo, porque essa lacuna está
confirmada. Registrado como passo do PRD, não como suposição.

### RQ-06 — a premissa "eu já sei em qual tenancy ele estaria" NÃO se sustenta pela rota

**Este é o achado mais consequente da pesquisa, e ele contraria o requisito.**

A rota da lock-screen é registrada pelo pacote com o path do **painel**, não o do tenant
(`vendor/marjose123/filament-lockscreen/routes/web.php`):

```php
->middleware(...$panel->getMiddleware())   // só o middleware base
->prefix($panel->getPath())                 // 'app', e não 'app/{tenant}'
```

Consequências, ambas verificáveis:

1. A URL é `/app/screen/lock` — **sem segmento de tenant** — mesmo com `kit.tenancy.enabled` ligado.
2. O `tenantMiddleware` do painel (onde vive o `DefinirTenantDePermissoes` do kit e o `IdentifyTenant` do
   Filament) **não roda** nessa rota.

Ou seja: quando a lock-screen renderiza, `Filament::getTenant()` não tem de onde tirar o tenant. A frase
*"pois eu já sei em qual tenancy ele estaria"* descreve uma intuição correta do ponto de vista do **usuário**
(ele estava operando um tenant quando travou a sessão) e incorreta do ponto de vista do **framework** (a
requisição da lock-screen não carrega essa informação).

**Não é bloqueante** — a informação existe, só não vem pela rota. As saídas estão avaliadas em ADR-03.
**Assumido**: recuperar o tenant de uma fonte lateral, e **falhar para a logo da aplicação base** quando não
houver tenant identificável. Degradar para o genérico é sempre preferível a mostrar a logo do cliente errado.

### RQ-02 × RQ-03 — "imagens, logos, cores" contra "a princípio, cores"

RQ-02 pede três coisas; RQ-03 restringe a entrega a **cores**. Mas RQ-06 exige **logo** na lock-screen, o que
torna a logo necessária nesta entrega, não futura.

**Assumido**: entregar **cores + logo** (as duas que outras cláusulas exigem) e deixar "imagens" no plural —
banner, favicon, imagem de fundo — para depois, com a coluna preparada de forma que acrescentá-las não exija
migration nova. Registrado em ADR-01.

### RQ-07 — "mais definições e informações" não é testável

*"podemos colocar também ali mais definições e informações da organização, porém, isso a cargo do usuário do
kit"* — sem lista, sem critério de aceite.

**Assumido**: a cláusula não pede que o kit **implemente** campos extras; pede que o kit **não impeça** que
quem o usa os acrescente. Cumpre-se com um ponto de extensão óbvio e documentado (uma `Section` no form e uma
coluna que aceita chaves novas sem migration), não com campos inventados. Nada a testar além de o mecanismo
existir.

## Fora de Escopo (declarado)

- **Imagens além da logo** (banner, favicon, imagem de login) — RQ-03 limita a entrega, e só a logo é exigida
  por outra cláusula (RQ-06).
- **Campos de negócio da organização** (CNPJ, endereço, contato) — RQ-07 os coloca explicitamente "a cargo do
  usuário do kit".
- **Identidade visual nos painéis `/admin` e `/infra`** — RQ-05 nomeia só o painel `/app`. Os outros dois não
  têm tenant, e customizá-los por cliente não faria sentido: são a operação da instalação, não do cliente.
- **Tema claro/escuro por organização** — não pedido. O alternador de tema atual continua sendo escolha do
  usuário, não do tenant.
- **Tipografia/fonte por organização** — não pedido.
