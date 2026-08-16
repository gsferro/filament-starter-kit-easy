# Plano de Ação — Lightbox em imagens e documentos

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: pacote novo (`solution-forest/filament-simplelightbox`) e convenção nova de exibição de mídia em tabela
- **Toca infra compartilhada?**: **sim** → os três `PanelProvider` (`AdminPanelProvider`, `AppPanelProvider`, `InfraPanelProvider`) recebem um plugin novo, e `composer.json` ganha uma dependência.

> Como toca os três painéis, a regressão é **obrigatória** mesmo com o tipo "nova": os cenários que hoje provam que os painéis abrem (`tests/Browser/TelasDoKitTest.php`, `tests/Kit/PaginasInfraTest.php`) precisam continuar verdes.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Pacote instalado e integrado | 1, 2 | — |
| RQ-02 | Imagem em tabela abre em lightbox | 3, 4 | provado pelos CT-B |
| RQ-03 | Documento em tabela abre em lightbox | 6 | ⚠️ atendido como **convenção + receita**; não há coluna de documento no kit — ver premissa no `00` e ADR-03 |
| RQ-04 | Vale como convenção permanente | 6, 8 | receita em `wikis/receitas.md` + candidato a rule em `.ai/rules/filament.md` |
| RQ-05 | Documentado em `wikis/` | 6 | `pacotes.md`, `receitas.md`, `convencoes.md` |
| RQ-06 | Listado no `README.md` | 7 | seção "UI e produtividade" |
| RQ-07 | Teste avaliado e escrito | 5 | ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md` |
| RQ-08 | Avatar na tela de Usuários | 3 | `/admin` e `/app` |
| RQ-09 | Logo na tela de Organizações | 4 | `/admin` |
| RQ-10 | Sem upload, nada quebra | 3, 4 | célula vazia, sem clique e sem lightbox — CT dedicado |

## Objetivo

Instalar o `solution-forest/filament-simplelightbox` e torná-lo o mecanismo padrão do kit para ampliar mídia dentro de uma tabela: clicar na miniatura abre a imagem em tamanho cheio sobre a tela, sem sair da listagem e sem abrir aba nova.

Além das duas telas que já têm mídia hoje (avatar em Usuários, logo em Organizações), a entrega deixa a regra escrita: **coluna de imagem nova no kit nasce com lightbox**, e coluna de documento nasce com lightbox **só quando o arquivo é público e não sensível** — restrição que vem de como o pacote monta o preview de PDF/Office (ADR-03).

## Contexto

O kit já grava duas mídias e não mostra nenhuma delas em listagem:

- `users.avatar_url` — upload feito pelo `jeffgreco13/filament-breezy` na página "Meu perfil" (`->myProfile(hasAvatars: true)`), lido pelo Filament via `User::getFilamentAvatarUrl()` (`app/Models/User.php:276-281`)
- `tenants.logo` — upload no `TenantForm` (`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:100-126`), disk `public`, diretório `organizacoes/logos`, com o acessor `Tenant::logoUrl()` (`app/Models/Tenant.php:119-146`) que já degrada para o genérico quando o arquivo não existe

Hoje as duas listagens são só texto. Quem administra 40 organizações não tem como conferir visualmente se a logo subiu certa sem abrir cada registro.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Resources/Users/UserResource.php`

Tabela com 4 colunas de texto (`name`, `email`, `roles.name`, `created_at`), linhas 136-141. Recebe a coluna de avatar como **primeira** coluna. O `form()` não é tocado.

### `app/Filament/App/Resources/Users/UserResource.php`

Irmão escopado por tenant, classe deliberadamente separada (ver ADR-04 de `admin-da-organizacao`). Recebe a mesma coluna de avatar. **Não** reaproveitar por herança: a separação é decisão registrada.

### `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php`

Grid dos tenants, linhas 23-34. Recebe a coluna de logo como **primeira** coluna, antes de `nome`.

### `app/Models/Tenant.php:119-146` — `logoUrl()`

Já resolve o caso "arquivo sumiu do disco": devolve `null` quando `blank($this->logo)` ou quando `Storage::disk('public')->exists()` é falso. É o acessor certo para alimentar RQ-10.

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`

Cada um tem um array `->plugins([...])`. O `SimpleLightBoxPlugin` entra nos três — ver ADR-02 para por que nos três e não só nos dois que têm mídia hoje.

### `app/Providers/KitServiceProvider.php:146-152`

Registra CSS do kit por `FilamentAsset::register()`. **Não é tocado nesta wiki**: o pacote registra o JS dele sozinho, no `packageBooted()` do próprio service provider (`FilamentSimpleLightBoxServiceProvider.php:33-38`).

## Autorização

- **Policies**: nenhuma criada ou alterada. A coluna nova aparece dentro de tabelas cuja autorização já existe (`ViewAny:User`, `ViewAny:Tenant`).
- **Gates**: nenhum.
- **Middleware**: nenhum.
- **Nenhuma permission nova é gerada** — não há Resource, Page nem Widget novo. Os seeders do Shield **não** precisam rodar nesta entrega.

> Ponto de atenção de segurança, não de autorização: o avatar e a logo ficam no disk `public`, servidos por URL direta sem passar por autenticação. Isso **já é verdade hoje** (é o que faz a logo aparecer na tela de bloqueio); a coluna nova não muda o modelo de exposição, só o torna visível. Registrado como ADR-04.

## Rotas

Nenhuma rota nova. A feature é apresentação dentro de rotas existentes.

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| — | — | — | — |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ListUsers` (admin) | Filament | `/admin/users` | clica na miniatura do avatar → abre lightbox | **Sim** (`SimpleLightBox.open` + fslightbox) |
| `ListUsers` (app) | Filament | `/app/{tenant}/users` | idem | **Sim** |
| `ListTenants` | Filament | `/admin/tenants` | clica na miniatura da logo → abre lightbox | **Sim** |

**Gate de CT-B**: as três linhas afirmam sobre **JavaScript executado** (o overlay do fslightbox é criado em runtime, não existe no HTML inicial) — logo, há `05-casos-de-teste-browser.md`.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é alterada por esta wiki, então não há cenário de gravação novo a exigir. Os cenários de gravação existentes de `User` e `Tenant` seguem valendo como regressão.

## Variáveis de Ambiente

Nenhuma. O pacote não publica config.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Identidade visual da organização** (`tests/BrowserTenancy/IdentidadeVisualTest.php`): a logo passa a aparecer também na listagem. O teste existente olha a tela de bloqueio; não deve ser afetado, mas entra na regressão.
- **Tabelas dos três painéis**: o plugin registra **macros** (`ImageColumn::macro`, `TextColumn::macro`, `ImageEntry::macro`, `TextEntry::macro`) no `boot(Panel $panel)`. Macro é estático na classe: registrar em painel nenhum e chamar `simpleLightbox()` estoura `BadMethodCallException` na renderização da tabela. É a armadilha principal da entrega — ver ADR-02.
- **Peso da página**: um `<script>` a mais por painel (fslightbox, ~20 KB minificado). Medir na verificação final não é necessário; registrar aqui basta.
- **`asmit/resized-column`**: a coluna nova entra no mecanismo de redimensionamento/persistência por sessão como qualquer outra. Sem ação.

## Rollback

- **Migration down**: não há migration.
- **Reverter**: remover a linha `SimpleLightBoxPlugin::make()` dos três providers, remover as três colunas de imagem, `composer remove solution-forest/filament-simplelightbox`, `php artisan filament:assets`. Nenhum dado é criado ou alterado — o rollback é puramente de código.
- **Kill-switch parcial**: trocar `->simpleLightbox()` por nada deixa a coluna de imagem funcionando como miniatura simples. Degradação suave, sem tela quebrada.

## Dependências

- **Composer**: `solution-forest/filament-simplelightbox` `^1.0` (v1.x é a linha que suporta Filament v4 e v5; v0.x é Filament v3)
- **NPM**: nenhuma. O pacote entrega o JS já compilado em `resources/dist/filament-simplelightbox.js` e o registra via `FilamentAsset` — **não** depende do Vite do app nem de tema Filament customizado.

## Riscos

- **Macro não registrada no painel** → `BadMethodCallException` ao abrir a tabela. Mitigação: registrar nos três painéis (ADR-02) + CT que abre as três telas.
- **Preview de documento vaza URL para terceiro** (Google/Microsoft) e exige arquivo público. Mitigação: a convenção documentada restringe o uso em documento; ADR-03.
- **`php artisan filament:assets` esquecido** → o JS não é publicado em `public/js/filament/` e o clique não faz nada, **sem erro visível**. Mitigação: o comando entra no passo 1 e é citado na receita; `composer setup`/`kit:install` já roda `filament:upgrade`, que republica assets.
- **Avatar ausente**: `ImageColumn` sem valor renderiza célula vazia. Confirmar que não sobra um `<img>` clicável apontando para lugar nenhum — é exatamente o RQ-10, e tem CT próprio.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem hoje os channels do kit: `ai` (linha 85), `tenancy` (93) e `autenticacao` (101), além dos padrão do Laravel.

### Decisão

**Nenhum channel novo, e nenhum log novo.**

A feature não executa lógica: ela acrescenta colunas de apresentação e um atributo `x-on:click` no HTML. Não há decisão de fluxo, não há falha a capturar, não há chamada externa a auditar. Um `Log::info('coluna renderizada')` por linha de tabela seria ruído em volume proporcional ao número de registros.

> Se um dia a exibição de documento sensível for implementada (hoje fora de escopo — ver `00-requisito.md`), aí sim há o que logar: quem abriu qual documento. Isso é auditoria de acesso, não log de renderização, e nasceria no channel `autenticacao` ou num channel próprio da feature de anexos.

## Estrutura de Implementação

### 1. Instalar o pacote e publicar os assets

> Skills: `ponytail`

```bash
composer require solution-forest/filament-simplelightbox:"^1.0"
php artisan filament:assets
```

- **Verificar** que `public/js/filament/solutionforest/filament-simplelightbox/` existe depois do comando. Se não existir, o clique não abre nada e **nenhum erro aparece**.
- **Logs**: nenhum (passo de instalação).

### 2. Registrar o plugin nos três painéis

> Skills: `laravel-best-practices`

- **Paths**:
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/AppPanelProvider.php`
  - `app/Providers/Filament/InfraPanelProvider.php`
- Em cada um, dentro do array `->plugins([...])`, acrescentar:

```php
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;

// …
SimpleLightBoxPlugin::make(),
```

- **Onde no array**: junto dos plugins de UI (perto de `ResizedColumnPlugin::make()` no `/infra`), não no bloco de observabilidade. Ordem não importa funcionalmente — o `boot()` do plugin só registra macros.
- **Comentário obrigatório no código** (uma vez, no `AdminPanelProvider`, com referência nos outros dois): o plugin registra **macros** de `ImageColumn`/`TextColumn`/`ImageEntry`/`TextEntry` no `boot(Panel $panel)`. Painel sem o plugin registrado + coluna chamando `->simpleLightbox()` = `BadMethodCallException` na renderização. Por isso os três, inclusive o `/infra`, que hoje não tem mídia.
- **Logs**: nenhum.

### 3. Coluna de avatar nas duas telas de Usuários

> Skills: `laravel-best-practices`, `pest-testing`

- **Paths**:
  - `app/Filament/Admin/Resources/Users/UserResource.php` — método `table()`, primeira posição do array `->columns([...])`
  - `app/Filament/App/Resources/Users/UserResource.php` — mesma posição

```php
use Filament\Tables\Columns\ImageColumn;

ImageColumn::make('avatar_url')
    ->label('Avatar')
    // `disk('public')` explícito: o default é `local`, que aponta para
    // storage/app/private e não é servível por URL — a miniatura nasceria quebrada.
    // É o mesmo disk em que o Breezy grava (BreezyCore::myProfile(hasAvatars: true)).
    ->disk('public')
    ->circular()
    // Sem `defaultImageUrl()`: quem nunca subiu avatar deve ter a célula VAZIA,
    // não um placeholder clicável que abriria o lightbox em cima de nada (RQ-10).
    ->simpleLightbox(),
```

- **Por que `avatar_url` e não `getFilamentAvatarUrl()`**: `ImageColumn::make()` resolve o **atributo** do registro e monta a URL com o disk; `getFilamentAvatarUrl()` já devolve URL absoluta e passaria pelo disk de novo. O acessor continua sendo o que alimenta o avatar do menu do usuário — não é tocado.
- **Comportamento do macro** (confirmado em `vendor/solution-forest/filament-simplelightbox/src/SimpleLightBoxPlugin.php:41-53`): sem argumento, `simpleLightbox()` aplica `defaultImageUrl(null)`, `openUrlInNewTab()`, `action(fn () => null)` (para o clique não disparar a ação padrão da linha) e injeta `x-on:click="SimpleLightBox.open(event, '')"` + a classe `simple-light-box-img-indicator` na `<img>`. Com URL vazia, o JS (`resources/js/index.js`, `open()`) cai no ramo "múltiplas imagens" e usa o `src` das `<img>` marcadas — que é exatamente a miniatura. É por isso que **não** é preciso passar closure de URL aqui.
- **Logs**: nenhum.

### 4. Coluna de logo na tela de Organizações

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php` — primeira posição do array `->columns([...])`

```php
use Filament\Tables\Columns\ImageColumn;

ImageColumn::make('logo')
    ->label('Logo')
    ->disk('public')
    // Quadrada e não circular: logo de organização costuma ser retangular, e
    // `circular()` corta as pontas. Diferente do avatar de pessoa, de propósito.
    ->size(40)
    ->simpleLightbox(),
```

- **RQ-10 aqui é mais forte que no avatar**: `Tenant::logoUrl()` já trata o caso "coluna preenchida, arquivo sumiu do disco". O `ImageColumn` **não** faz essa verificação — ele monta a URL a partir da coluna. Consequência aceita: registro com `logo` apontando para arquivo inexistente mostra imagem quebrada na listagem, igual a qualquer `ImageColumn` do Filament. Não é regressão (hoje não há coluna nenhuma), e resolver exigiria um `Storage::exists()` por linha — N+1 de I/O numa listagem. Registrado como ADR-05.
- **Logs**: nenhum.

### 5. Testes

> Skills: `pest-testing`, `feature-test-design`

Ver `04-casos-de-teste.md` (componente Livewire) e `05-casos-de-teste-browser.md` (o lightbox abrindo de verdade).

Resumo do que precisa existir:
- as três listagens abrem com a coluna nova, sem `BadMethodCallException` — **este é o teste que paga a entrega**, porque a falha do macro só aparece na renderização
- registro sem upload não oferece lightbox (RQ-10)
- o overlay do fslightbox aparece no DOM depois do clique (CT-B — só o navegador prova)

### 6. Documentação do kit

> Skills: nenhuma específica

- **`wikis/pacotes.md`** → seção "Já existe — não escreva de novo": entrada dizendo que ampliação de imagem em tabela **já tem dono**, e que não se escreve modal de preview à mão.
- **`wikis/receitas.md`** → receita nova **"Imagem ou documento em tabela"**, entre "Resource novo" e "RelationManager novo", com:
  - o snippet de `ImageColumn` + `simpleLightbox()`
  - o snippet de `TextColumn::make('contrato_url')->simpleLightbox(fn ($record) => $record->contrato_url)` para documento
  - o aviso do ADR-03 (documento vai para Google/Microsoft e precisa ser público)
  - o lembrete de que o painel precisa ter o plugin registrado
- **`wikis/convencoes.md`** → seção "Invariantes de model" ou "Armadilhas já resolvidas": a convenção de RQ-04 em uma frase, mais a armadilha do macro.
- **`wikis/README.md`** → se houver índice das receitas, incluir a nova.

### 7. README — dependência

> Skills: nenhuma específica

- **Path**: `README.md`, seção `### UI e produtividade` (linha ~836)
- Acrescentar a linha, mantendo o formato da tabela:

```markdown
| [solution-forest/filament-simplelightbox](https://packagist.org/packages/solution-forest/filament-simplelightbox) | ampliação de imagens e documentos em lightbox nas tabelas |
```

- **Posição**: logo depois de `awcodes/filament-badgeable-column`, junto do bloco de colunas de tabela.

### 8. Candidato a rule de projeto

> Skills: `requirement-to-rule`

Ao final, propor ao usuário (**não gravar sem aprovação**) a rule de `app/Filament/**`:
coluna de imagem em tabela nasce com `->simpleLightbox()`; documento só com arquivo público; e a armadilha do macro por painel. Ver step 9 da skill `feature-wiki`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Aplicação concreta nesta wiki: **nenhuma classe nova é criada**. A entrega inteira são 3 linhas de plugin, 3 colunas e documentação. Qualquer proposta de "componente `AvatarColumn` reutilizável" ou "trait `TemLightbox`" é over-engineering — duas chamadas idênticas não são duplicação que justifique abstração.
>
> Atalhos deliberados marcados com `ponytail:` comment.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário. Arquivos wiki (00-06), código, commits e PRs são boundary do Caveman — prosa normal.

## Testes

> Ver `04-casos-de-teste.md` para os cenários de componente Livewire.
> Ver `05-casos-de-teste-browser.md` para os cenários que exigem navegador.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `vendor/bin/pest --group=kit --compact`
- [ ] `composer test:browser` (embute `npm run build`; CT-B em série, nunca `--parallel`)
- [ ] Abrir `/admin/users`, `/app/{slug}/users` e `/admin/tenants` com e sem mídia enviada
- [ ] Confirmar que `public/js/filament/solutionforest/` foi publicado

## Commits

- `:package: instala o solution-forest/filament-simplelightbox nos tres paineis`
- `:sparkles: avatar e logo abrem em lightbox nas listagens`
- `:white_check_mark: testes do lightbox em imagem de tabela`
- `:memo: documenta o lightbox como padrao de midia em tabela`
