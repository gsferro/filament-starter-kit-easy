# Plano de Ação — unificar-logo-da-organizacao

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/fix/logo-dark-do-tenant/` — a feature que criou `tenants.logo_dark` e a regra de queda por variante em `IdentidadeDoKit::logosPara()`; e `wikis/specs/feat/logo-dark-mode/` — a que criou `unifica_logo_marca` no settings da instalação
- **Motivo**: estende a regra de unificação da instalação para dentro de cada organização
- **Toca infra compartilhada?**: sim — migration em `tenants` (tabela compartilhada), `IdentidadeDoKit::logosPara()` (consumida por cabeçalho e tela de bloqueio), `TenantForm` e `TenantFactory`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Verificação: tenant já tem logo clara/escura | — | respondida na análise; nenhum passo |
| RQ-02 | Opção de unificar a logo por organização | 1, 2, 3, 4 | coluna + toggle + regra |
| RQ-03 | Unificada: a clara da organização serve os dois temas | 4 | `escura = null` → consumidor renderiza a clara |
| RQ-04 | Fluxo padrão, tag + release ao final | todos + verificação final | — |
| P-01 | Opção por organização, não global | 1, 2 | — |
| P-02 | Toggle só existe com a marca da instalação separada | 2 | mesmo critério do campo `logo_dark` |
| P-03 | Vale no topo do `/app` e na tela de bloqueio | 4 | as duas leem `logosPara()` |
| P-04 | Backfill: quem tem `logo_dark` gravada nasce `false`; quem não tem, `true` | 1 | — |
| P-05 | Sem tenancy/organização aberta, nada muda | 4 | organização `null` não consulta o flag |

## Objetivo

Dar a cada organização o mesmo interruptor que a instalação tem (`unifica_logo_marca`): ligado, a logo clara dela serve os dois temas e a empresa não é obrigada a enviar uma segunda imagem para o dark não mostrar a marca da instalação.

## Contexto

Hoje, com a marca da instalação separada (`unifica_logo_marca` desligado), o formulário da organização ganha o campo `logo_dark` e `IdentidadeDoKit::logosPara()` monta o par assim:

```php
'escura' => self::unificaLogo()
    ? null
    : ($organizacao?->urlDaLogoEscura() ?? self::logoEscura()),
```

O buraco é o `?? self::logoEscura()`: organização que mandou só a clara exibe no dark **a marca da instalação** — o problema exato que o `unifica_logo_marca` resolve para a própria instalação e que este pedido replica para a organização. A `logo_dark` da organização também fica inerte quando a marca global é unificada (o campo nem aparece no form) — coerente, e o novo toggle segue o mesmo regime.

## Análise dos Arquivos Existentes

### `database/migrations/2026_10_03_100000_add_logo_dark_to_tenants_table.php`
- Padrão: `Schema::table('tenants', $table->string('logo_dark')->nullable()->after('logo'))`. A nova migration segue a forma — booleana com default e backfill condicional.

### `app/Models/Tenant.php`
- `$fillable` já tem `logo`/`logo_dark`; `casts()` tem os booleanos `ativo`/`registro_habilitado`. Entram `unifica_logo` nos dois lugares e o `@property` no docblock.

### `app/Support/IdentidadeDoKit.php` (`logosPara()`, linha ~94-102)
- Regra única do par, consumida por `CabecalhoDoPainel` (topo do `/app`) e `TelaBloqueio::urlsDasLogos()`. A consulta ao flag da organização entra aqui: `unifica_logo` ligado → `escura = null` → a clara da organização serve os dois temas (RQ-03).

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- `FileUpload::make('logo_dark')` já tem `->visible(fn (): bool => ! config('kit.identidade.unifica_logo_marca', true))`. O `Toggle::make('unifica_logo')` entra na mesma seção (Identidade visual), **antes** do `logo_dark`, visível sob a mesma condição, e o `logo_dark` ganha uma segunda condição: `! $get('unifica_logo')` — toggle desligado é o que abre o campo.

### `database/factories/TenantFactory.php`
- `comIdentidadeVisual(?string $cor, ?string $logo, ?string $paleta, ?string $logoEscura)` já grava `logo` e `logo_dark`. Ganha um parâmetro `?bool $unifica` — sem ele, o default da coluna vale.

### `app/Filament/Pages/Auth/TelaBloqueio.php` (`urlsDasLogos():105-108`)
- Consumidor indireto: `logosPara()` devolve `escura = null` e a tela já renderiza a clara sozinha — nenhum código a mudar aqui; o efeito é gratuito.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---------|----------|------------------|--------------------|
| D1 | Coluna `tenants.unifica_logo` (bool), não settings global nem inferência por "logo_dark em branco" | Q1 | difícil de reverter | solicitante + sessão, 2026-10-09 |
| D2 | Backfill: `unifica_logo = (logo_dark IS NULL)` — quem já separou preserva a separação; quem não mandou escura ganha a unificação | Q2 | trade-off (muda a tela de quem tem só clara — é o comportamento pedido) | solicitante + sessão, 2026-10-09 |
| D3 | O toggle governa `logo_dark` por `->live()` no form; os dois campos dividem a mesma condição de visibilidade | — | difícil de reverter | sessão, 2026-10-09 (P-02) |
| D4 | `logosPara()` vira a dona da consulta ao flag — nenhum consumidor novo aprende a regra | — | difícil de reverter | sessão, 2026-10-09 (P-03) |

## Autorização

- **Policies**: nenhuma nova. O `TenantResource` já é governado pela policy de `Tenant` — o campo novo é mais um atributo editável na mesma tela.
- **Gates / Middleware**: nenhum.

## Rotas

Nenhuma rota nova. Os caminhos afetados são o render do `/app/{tenant}` e a tela de bloqueio.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `Toggle::make('unifica_logo')` + `FileUpload::make('logo_dark')` na seção Identidade visual do `TenantForm` | Filament (form do resource) | `/admin/tenants/{id}/edit` e create | ligar/desligar o toggle esconde ou mostra o upload da escura | Livewire (`->live()`) — sim, na interação; a gravação é POST padrão |
| Topo do `/app` (marca clara × escura) | Filament (blade do vendor) | `/app/{tenant}/*` | — | o swap é CSS (`fi-logo-dark`), não JS da feature |

**Gate de tela de escrita**: o `04` precisa de cenário de gravação por componente — criar/editar organização com `unifica_logo` e conferir o banco.

## Variáveis de Ambiente

Nenhuma nova — a coluna é da organização e não recebe seed por `.env` (diferente dos settings, que nascem de `kit.*`).

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 — `logosPara()` roda no mesmo render que já resolve o par |
| O que é adiado, e por qual gatilho? | Nenhum |
| O que é memoizado **por request**? | O `WeakMap` do `CabecalhoDoPainel` já cobre a resolução inteira — o flag é um atributo hidratado da organização, zero query extra |
| O que é cacheado **entre** requests? | Nada novo |
| Custo do caminho principal | Zero queries novas: `$organizacao->unifica_logo` é atributo do model já carregado |

## Impacto em Features Existentes

- **`fix/logo-dark-do-tenant`** (ancestral): organizações existentes **com** `logo_dark` gravada passam a ter `unifica_logo = false` — mesma tela; as **sem** viram `true` e o dark passa a mostrar a clara delas em vez da `logo_dark` da instalação — é o comportamento pedido (P-04), registrado no CHANGELOG.
- **`feat/logo-dark-mode`**: a marca da instalação unificada continua desligando o campo `logo_dark` e agora também o toggle (P-02).
- **`TelaBloqueio`**: efeito gratuito por compartilhar `logosPara()` — coberto por CT.
- **`TenantFactory`**: assinatura ganha parâmetro opcional — compatível com chamadores existentes.

## Rollback

- **Migration down**: `dropColumn('unifica_logo')`.
- **Feature flag**: não tem — é dado da organização. Reverter é `UPDATE tenants SET unifica_logo = false` ou desligar na tela.
- **Reversão de dados**: o backfill é reproduzível (`unifica_logo = (logo_dark IS NULL)`).

## Dependências

Nenhuma.

## Riscos

- **`unifica_logo` fora do `$fillable`/`casts`**: o `Toggle` gravaria errado ou não gravaria — coberto por CT de gravação por componente.
- **Toggle visível sem marca separada**: promessa quebrada — mitigado pela mesma condição do `logo_dark` (P-02) e CT de visibilidade.
- **Backfill errado**: organização que tinha `logo_dark` perdendo a separação no update — mitigado pelo `= (logo_dark IS NULL)` e CT sobre migration.

## Channel de Log da Feature

Nenhum — a decisão é um booleano sobre dado já carregado; não há fronteira a registrar. (Contraste: `IdentidadeDoKit::doDisco()` loga arquivo ausente porque isso é anomalia; `unifica_logo` desligado/ligado é estado normal.)

## Estrutura de Implementação

### 1. Migration `unifica_logo` em `tenants`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_10_09_100000_add_unifica_logo_to_tenants_table.php`
- `Schema::table`: `boolean('unifica_logo')->default(true)->after('logo_dark')`.
- Depois do `Schema::table`, backfill: `DB::table('tenants')->whereNotNull('logo_dark')->update(['unifica_logo' => false])` — quem já enviou a escura separou de propósito (D2).
- `down()`: `dropColumn`.
- Docblock citando a decisão do default e do backfill.
- **Atende**: RQ-02, P-01, P-04

### 2. `Tenant` — fillable + cast + docblock

> Skills: `laravel-best-practices`

- **Path**: `app/Models/Tenant.php`
- `@property bool $unifica_logo` no docblock, `'unifica_logo'` no `$fillable` (junto de `logo`/`logo_dark`), `'unifica_logo' => 'boolean'` no `casts()`.
- **Atende**: RQ-02, P-01

### 3. `TenantForm` — toggle + condição do `logo_dark`

> Skills: `filament-development`, `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- Antes do `FileUpload::make('logo_dark')`, na seção Identidade visual:

```php
Toggle::make('unifica_logo')
    ->label('Uma logo só, nos dois temas')
    ->helperText('Ligado (padrão), a logo acima vale no tema claro e no escuro. Desligado, aparece o campo da variante escura — sem ela preenchida, o dark usa a logo_dark da instalação.')
    ->default(true)
    ->live()
    ->visible(fn (): bool => ! config('kit.identidade.unifica_logo_marca', true))
    ->columnSpanFull(),
```

- `logo_dark`: `->visible(fn (Get $get): bool => ! config('kit.identidade.unifica_logo_marca', true) && ! (bool) $get('unifica_logo'))` — precisa de `use Filament\Schemas\Components\Utilities\Get;` (o form já importa `Set` do mesmo namespace).
- O helper do `logo_dark` ganha a frase "ou desligue a unificação" — hoje ele promete a queda para a instalação sem citar o toggle.
- **Atende**: RQ-02, RQ-03, P-02

### 4. `IdentidadeDoKit::logosPara()` — consulta ao flag

> Skills: `laravel-best-practices`

- **Path**: `app/Support/IdentidadeDoKit.php`

```php
return [
    'clara'  => $organizacao?->urlDaLogo() ?? self::logo(),
    'escura' => self::unificaLogo() || ($organizacao?->unifica_logo ?? false)
        ? null
        : ($organizacao?->urlDaLogoEscura() ?? self::logoEscura()),
];
```

- `?? false`: organização `null` cai no ramo de baixo, que já devolve o par da instalação; sem organização não há flag a consultar (P-05).
- Docblock do método ganha a frase sobre o flag por organização (antes só citava `unifica_logo_marca`).
- **Atende**: RQ-02, RQ-03, P-03, P-05

### 5. `TenantFactory::comIdentidadeVisual()` + docs + CHANGELOG

> Skills: `laravel-best-practices`

- **Path**: `database/factories/TenantFactory.php`, `docs/pt/recursos/multi-tenancy.md`, `docs/en/recursos/multi-tenancy.md`, `CHANGELOG.md`
- Factory ganha `?bool $unifica = null` no fim da assinatura; `null` não sobrescreve o default da coluna (escrita condicional no array de atributos).
- Docs pt/en: parágrafo na seção de identidade visual da organização sobre o toggle — mesma frase da regra (ligado: uma logo nos dois temas; desligado: campo da escura aparece, e em branco cai para a `logo_dark` da instalação).
- CHANGELOG: entrada no `Unreleased`/`Adicionado`.
- **Atende**: RQ-02, RQ-04

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**: uma coluna, um flag consultado no ponto único (`logosPara`), um toggle reutilizando a condição do `logo_dark`. Sem settings global, sem classe nova, sem channel novo.
>
> **Baseline antes do primeiro commit**: suíte atual em `main` está verde exceto as 3 falhas pré-existentes de `TemaEscuroTest` (acessibilidade), medidas no ciclo da feature anterior — a `## Verificação Final` compara contra ela.

## Mapeamentos

Nenhum — booleano direto.

## Testes

> Ver `04-casos-de-teste.md` (a derivar no step 7).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter=UnificaLogo --compact` (ou nome do arquivo de teste)
- [ ] `vendor/bin/pest --parallel --tia` — comparado à baseline (3 falhas de `TemaEscuroTest`)
- [ ] `pest --mutate --path=app/Support/IdentidadeDoKit.php` via `pestw.cmd` — score, duração e sobreviventes
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**
- [ ] Scripts de reconciliação silenciosos (rastreabilidade, checkbox, citações, ids-ct, conformidade-rules)
- [ ] `git commit` + tag + release

## Commits

- `:sparkles: feat(tenancy): opcao de unificar a logo da organizacao nos dois temas`
- `:memo: docs(wiki): especificacao de unificar-logo-da-organizacao`
