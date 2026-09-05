# Plano de Ação — A paleta do Filament na identidade visual da organização

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feature/identidade-visual-da-organizacao/` (a cor e a logo por
  organização; ADR-01 "uma cor, não um tema", ADR-02 `FilamentColor::register()` no `bootUsing()`) e
  `wikis/specs/feat/settings-do-kit/` (os dois campos de cor do kit e a precedência hex > nome)
- **Motivo**: a organização só tem a cor livre; o kit tem paleta + livre. O solicitante quer a mesma
  escolha nos dois lugares.
- **Toca infra compartilhada?**: **sim** — `App\Support\CorPrimaria` (usada pelos três painéis no
  `->colors()`) ganha o resolvedor que passa a servir também à organização; migration em `tenants`.
  Regressão obrigatória: `tests/Kit/CorPrimariaTest.php`, `tests/Kit/IdentidadeVisualTest.php`,
  `tests/Tenancy/IdentidadeVisualTenancyTest.php`, `tests/BrowserTenancy/IdentidadeVisualTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | escolha da paleta do Filament na organização | 1, 2, 4 | coluna + `Select` com `CustomizadorDaInstalacao::CORES` |
| RQ-02 | a cor livre continua | 4 | o `ColorPicker` fica como está; regressão |
| RQ-03 | hex vence | 3, 5 | um resolvedor, o do kit, servindo às duas fontes |
| RQ-04 | a paleta é aplicada no `/app` | 3, 5 | `bootUsing()` passa a resolver as duas colunas |
| RQ-05 | mesma lista, mesmo vazio, mesmo inválido | 3, 4 | a lista é a constante do kit; o resolvedor é o do kit |

## Objetivo

Dar à organização a mesma escolha de cor que o kit já dá a si mesmo: uma cor da paleta padrão do
Filament **ou** um hexadecimal livre, com o hexadecimal vencendo — e fazer isso sem uma segunda
regra de precedência: a que existe em `CorPrimaria` passa a receber as duas fontes como argumento e
serve ao kit e à organização.

## Contexto

`ConfiguracoesDoKit` tem `Select cor_primaria` (16 nomes de `CustomizadorDaInstalacao::CORES`) e
`ColorPicker cor_primaria_hex`; `CorPrimaria::paleta()` lê as duas chaves de config e decide. A
organização (`TenantForm`) tem só o `ColorPicker cor_primaria` (`#RRGGBB`, `string(7)`), aplicado
em `AppPanelProvider::bootUsing()` como string. Falta o `Select` — e falta um lugar onde a
organização possa guardar um **nome** de paleta, porque `cor_primaria` é a coluna do hexadecimal.

## Análise dos Arquivos Existentes

### `app/Support/CorPrimaria.php`

- `paleta(): array` lê `config('kit.cor_primaria_hex')` e `config('kit.cor_primaria')` e aplica:
  hex válido → `['primary' => $hex]`; senão nome que existe em `Color::` → `['primary' => Color::X]`;
  senão `[]`. `FORMATO_HEX` aceita `#rgb` e `#rrggbb`.
- Ganha `resolver(?string $hex, ?string $nome): array` com esse corpo; `paleta()` vira
  `resolver(config(...), config(...))`. Comportamento idêntico, um chamador a mais.

### `app/Providers/Filament/AppPanelProvider.php:152-175`

- `FilamentColor::register(fn () => ...)`: guarda de painel `app` + `instanceof Tenant` +
  `blank($tenant->cor_primaria)`; devolve `['primary' => $tenant->cor_primaria]` com `debug` no
  channel `tenancy`.
- Passa a: `$paleta = CorPrimaria::resolver($tenant->cor_primaria, $tenant->cor_primaria_nome)`;
  `[]` → devolve `[]` (neutro); senão loga e devolve `$paleta`. A guarda `blank()` sai — o resolvedor
  já responde vazio.

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:98-118`

- `Section 'Identidade visual'`, `columns(2)`, com `ColorPicker cor_primaria` e `FileUpload logo`.
- Ganha `Select cor_primaria_nome` **antes** do `ColorPicker`, com o helper text do kit adaptado. A
  seção passa a ter três campos; `columns(2)` fica (o `FileUpload` cai para a segunda linha) — ou
  `columnSpanFull()` na logo. Decidir no step 5 olhando a tela.

### `database/migrations/2026_08_14_000003_add_identidade_visual_to_tenants_table.php`

- Molde da migration nova (docblock com o porquê; `after('cor_primaria')`; `down()` com `dropColumn`).

### `app/Models/Tenant.php:75-90`, `database/factories/TenantFactory.php:39`

- `$fillable` ganha `cor_primaria_nome`; `@property ?string $cor_primaria_nome` no docblock.
- `comIdentidadeVisual(string $cor = '#7c3aed', ?string $logo = null)` ganha um terceiro parâmetro
  opcional `?string $paleta = null`. Estados novos não: um parâmetro a mais serve aos CTs.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:246-262`

- É o **modelo** dos rótulos e do helper text. Não muda.

## Autorização

Nenhuma mudança: o campo novo entra no formulário do `TenantResource`, que já é governado por
`TenantPolicy` (`Update:Tenant`).

## Rotas

Nenhuma.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `CreateTenant` / `EditTenant` — seção "Identidade visual" | Filament (form) | `/admin/organizacoes/create`, `/admin/organizacoes/{record}/edit` | escolhe uma cor da paleta no `Select` e/ou uma cor livre no `ColorPicker`; salva | Não (o `Select` é nativo) |
| Painel `/app/{slug}` | Filament | `/app/{slug}` | vê o painel pintado com a cor escolhida | Não para o dado (a paleta registrada é lida por `FilamentColor::getColors()`); sim para o pixel |

**Gate de CT-B**: **não passa**. O que se afirma é a **paleta registrada** (`FilamentColor::getColors()['primary']`
igual a `Color::Blue`), que é exatamente o oráculo que `IdentidadeVisualTenancyTest` já usa para a
cor livre — e a forma `Feature`, sem navegador. A renderização do pixel a partir de uma paleta
registrada é responsabilidade do Filament e já tem o CT-B da wiki ancestral
(`tests/BrowserTenancy/IdentidadeVisualTest.php`), que roda na regressão. Sem `05`.

**Gate de tela de escrita**: `create`/`edit` existem → o `04` precisa de **gravação por componente**
do `Select` (e do par `Select` + `ColorPicker`).

## Variáveis de Ambiente · Eventos · Jobs

Nenhum.

## Impacto em Features Existentes

- **Cor livre da organização** — a resolução passa por `CorPrimaria::resolver()`, que para um hex
  válido devolve **a mesma string** que o `bootUsing()` devolvia hoje (`['primary' => $hex]`).
  Regressão: `nao vaza a cor entre organizacoes e paineis`, `mantem a cor da organizacao vencendo a
  cor livre do kit`, CT-B `IdentidadeVisualTest`.
- **`CorPrimaria::paleta()`** — mesmo corpo, via `resolver()`. Regressão: `CorPrimariaTest` inteiro.
- **`TenantFactory::comIdentidadeVisual()`** — parâmetro opcional novo; chamadores existentes
  (`duasOrganizacoes()`, testes) não mudam.
- **Hex inválido gravado direto no banco** hoje cai em `['primary' => 'lixo']` e o painel fica
  acromático (o docblock do `ColorPicker` descreve isso); com o resolvedor, cai para o nome ou para
  a cor da aplicação. **Melhora colateral**, registrada.
- **`kit:update`** — `database/migrations`, `app/Support`, `app/Filament` e `app/Providers` já estão
  em `CAMINHOS_DO_KIT`; a migration nova chega a quem atualiza e o aviso pós-update manda migrar.

## Rollback

`php artisan migrate:rollback --step=1` remove `cor_primaria_nome`; `git revert` do resto. Sem dado
a preservar: a coluna nasce nula em toda organização.

## Dependências

Nenhuma.

## Riscos

- **Nome de paleta que o Filament renomeie ou remova** num upgrade — o resolvedor já ignora nome
  inexistente (`defined()`), e `CorPrimariaTest` cobre. O `Select` mostra vazio e quem editar salva
  de novo.
- **Ordem de registro de cores** — nada muda: continua no mesmo `FilamentColor::register()` do
  `bootUsing()`, depois do `Panel::colors()`; a organização segue vencendo o kit.
- **`Select` com valor gravado fora da lista** (removido de `CORES`) — o `in()` nativo do Filament
  reprovaria ao **salvar** o formulário mesmo sem tocar no campo. Mitigação: o `Select` pode usar o
  mesmo truque de `ConfiguracoesDoKit::comValorConfigurado()` (acrescenta o valor atual como opção
  rotulada "fora da lista do kit"). Decidir no step 5; se a lista for estável, não vale o código.

## Channel de Log da Feature

`tenancy` — **existe**, é onde o `bootUsing()` já registra "Cor da organização aplicada". O log
existente ganha `cor_primaria_nome` no contexto. Sem channel novo.

## Estrutura de Implementação

### 1. Migration: `tenants.cor_primaria_nome` (RQ-01)

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_09_02_000001_add_cor_primaria_nome_to_tenants_table.php`
  (`php artisan make:migration add_cor_primaria_nome_to_tenants_table --table=tenants --no-interaction`)
- `$table->string('cor_primaria_nome', 32)->nullable()->after('cor_primaria');` — o nome de uma
  constante de `Filament\Support\Colors\Color` (`Blue`, `Emerald`…); 32 dá folga para nomes
  futuros. `down()`: `dropColumn('cor_primaria_nome')`.
- Docblock no padrão da migration ancestral: por que uma coluna separada (a de hex é `string(7)` e
  os nomes cabem nela **por coincidência**; misturar tipo numa coluna esconde o erro), por que
  nulo é o neutro.

### 2. `Tenant` e `TenantFactory` (RQ-01)

> Skills: `laravel-best-practices`

- `app/Models/Tenant.php`: `'cor_primaria_nome'` em `$fillable`; `@property ?string $cor_primaria_nome`.
- `database/factories/TenantFactory.php`: `comIdentidadeVisual(string $cor = '#7c3aed', ?string $logo = null, ?string $paleta = null)`
  gravando `'cor_primaria_nome' => $paleta`.

### 3. `CorPrimaria::resolver()` (RQ-03, RQ-04, RQ-05)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/CorPrimaria.php`

  ```php
  /**
   * Resolve a paleta a partir das duas fontes, com o hexadecimal vencendo — a MESMA regra
   * para o kit (config) e para a organização (colunas do Tenant).
   *
   * @return array<string, array<int, string>|string> vazio = mantém o que já está registrado
   */
  public static function resolver(?string $hex, ?string $nome): array
  {
      if (is_string($hex) && preg_match(self::FORMATO_HEX, $hex) === 1) {
          return ['primary' => $hex];
      }

      if (! is_string($nome) || $nome === '' || ! defined(Color::class.'::'.$nome)) {
          return [];
      }

      return ['primary' => constant(Color::class.'::'.$nome)];
  }

  public static function paleta(): array
  {
      return self::resolver(config('kit.cor_primaria_hex'), config('kit.cor_primaria'));
  }
  ```

- Docblock da classe: acrescentar a organização como segundo chamador e apagar a frase "não
  confundir com a cor de uma ORGANIZAÇÃO" (agora é a mesma regra, aplicada mais tarde no ciclo).
- **Logs**: nenhum — função pura, como hoje.

### 4. O `Select` no `TenantForm` (RQ-01, RQ-02, RQ-05)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`, seção "Identidade visual"
- Imports novos (o arquivo não os tem): `use App\Support\CustomizadorDaInstalacao;` e
  `use Filament\Forms\Components\Select;`
- Antes do `ColorPicker`:

  ```php
  Select::make('cor_primaria_nome')
      ->label('Cor primária (paleta do Filament)')
      ->helperText('A mesma lista do settings do kit. Em branco, a organização usa a cor da aplicação. A cor livre ao lado VENCE quando preenchida.')
      ->options(array_combine(CustomizadorDaInstalacao::CORES, CustomizadorDaInstalacao::CORES))
      ->placeholder('Cor da aplicação (padrão)')
      ->native(false)
      ->searchable(),
  ```

- O `ColorPicker` existente: rótulo passa a "Cor primária livre" e o helper text ganha "Vence a
  paleta escolhida acima quando preenchida" — espelho do kit.
- A `Section` continua `columns(2)`; o `FileUpload` da logo ganha `->columnSpanFull()` para não
  ficar espremido ao lado de um campo vazio. Confirmar na tela (step 5).
- **Logs**: nenhum — o `TenantResource` já é auditado por `AuditsFillables` (a coluna nova é
  `$fillable`, então entra na auditoria sozinha).

### 5. `bootUsing()` resolve as duas colunas (RQ-03, RQ-04)

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php:152-175`

  ```php
  FilamentColor::register(function (): array {
      $tenant = Filament::getTenant();

      if (Filament::getCurrentPanel()?->getId() !== 'app' || ! $tenant instanceof Tenant) {
          return [];
      }

      $paleta = CorPrimaria::resolver($tenant->cor_primaria, $tenant->cor_primaria_nome);

      if ($paleta === []) {
          return [];
      }

      Log::channel('tenancy')->debug(
          '[AppPanelProvider@bootUsing] Cor da organização aplicada | tenant: '.$tenant->getKey(),
          [
              'tenant_id'         => $tenant->getKey(),
              'tenant_slug'       => $tenant->slug,
              'cor_primaria'      => $tenant->cor_primaria,
              'cor_primaria_nome' => $tenant->cor_primaria_nome,
              'fonte'             => is_string($paleta['primary']) ? 'hex' : 'paleta',
          ],
      );

      return $paleta;
  });
  ```

- O comentário "Uma cor, não uma paleta" ajusta: agora pode ser string **ou** paleta — o
  `ColorManager` aceita os dois (`ColorManager.php:84-85` gera a paleta só quando recebe string).
- **Logs**: o `debug` existente, com `cor_primaria_nome` e `fonte` no contexto.

### 6. CHANGELOG e documentação

- `CHANGELOG.md` → `[Unreleased]` → `### Adicionado`.
- `docs/pt/recursos/configuracoes-do-kit.md:135` e `docs/en/…`: a frase "nas colunas `cor_primaria`
  e `logo`" passa a citar a paleta e a precedência. `docs/pt/recursos/multi-tenancy.md` só se
  mencionar identidade visual (step 5 confere).

## Filosofia de Implementação

> **Ponytail em `full`.**
> 1. **Uma regra de precedência**, a que existe: `resolver()` é a extração de `paleta()`, não código
>    novo. Zero lógica duplicada entre kit e organização.
> 2. **A lista é a constante do kit** — nenhuma lista nova, nenhuma config nova.
> 3. **Uma coluna nova**, não JSON nem tabela: ADR-01 da wiki ancestral fixou "ao terceiro campo,
>    reavaliar"; este é o terceiro e ele **é** da mesma natureza dos dois (identidade visual). Ver
>    ADR-02 desta wiki.
> 4. **Sem estado de factory novo**: um parâmetro opcional.
> 5. **Sem `comValorConfigurado()`** salvo se o step 5 provar que o `in()` do `Select` bloqueia a
>    edição com valor legado — hoje não há valor legado (coluna nasce nula).
>
> **Caveman `full`** na conversa; wiki, código, commits e PR em prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05` (gate não passa; o CT-B da wiki ancestral roda na regressão).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --no-tia tests/Kit/CorPrimariaTest.php tests/Kit/IdentidadeVisualTest.php tests/Tenancy/PaletaDaOrganizacaoTest.php --compact`
- [ ] `vendor/bin/pest --no-tia tests/Tenancy/IdentidadeVisualTenancyTest.php --compact`
- [ ] `vendor/bin/pest --no-tia --parallel --testsuite=Kit,Tenancy --compact`
- [ ] `composer test:browser` filtrado em `IdentidadeVisual` (o CT-B da ancestral)
- [ ] Abrir `/admin/organizacoes/{id}/edit`: `Select` com as 16 cores, `ColorPicker` ao lado, logo em linha inteira; salvar `Blue` e abrir `/app/{slug}` — painel azul

## Commits

- `✨ feat(organizacao): a identidade visual escolhe uma cor da paleta do Filament, como o settings do kit`
- `✅ test(organizacao): paleta, hex vencendo, vazio e inválido — a mesma régua do kit`
- `📝 docs(wiki): feat/paleta-do-filament-na-organizacao`
