# Plano de Ação — Rector no pipeline de qualidade do kit

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Toca infra compartilhada?**: **sim** — `composer.json` (scripts de verificação) e a esteira que
  todo projeto nascido do kit roda. Não toca código de aplicação.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Analisar `driftingly/rector-laravel` | 1 | Medido, não lido |
| RQ-02 | Rector como qualidade de código | 2, 3 | **Sim**, no papel de upgrade |
| RQ-03 | Rector nas validações do lint | 2 | **Não** — ADR-02, com o defeito medido |
| RQ-04 | Regras específicas de Filament v5 | 1 | **Não existem** — ADR-03 |
| RQ-05 | Laravel 13 como base | 4 | `LaravelSetList::LARAVEL_130` existe e é o alvo |
| RQ-06 | Demais stacks do `composer.json` | 4 | PHP 8.4, Pest 5; ver a tabela de sets |
| RQ-07 | Instalar se agregar valor | 3, 4, 5 | Instalado como `require-dev`, papel restrito |
| RQ-08 | Documentar nas wikis | 6 | `wikis/qualidade-de-codigo.md` + `pacotes.md` |
| RQ-09 | Documentar nos READMEs | 7 | `README.md` e `README.en.md` |

## Objetivo

Responder, com medição em vez de opinião, se o Rector entra no kit — e em qual papel.

A conclusão tem duas metades, e elas divergem: o Rector **não** entra como gate de lint, e **entra**
como ferramenta de upgrade documentada. O que separa as duas é um número (103 arquivos) e um defeito
concreto que a primeira opção reintroduziria.

## Contexto

O kit já tem três ferramentas de qualidade, cada uma num eixo distinto:

| Ferramenta | Eixo | O que faz quando acha problema |
|---|---|---|
| **Pint** | estilo | corrige |
| **PHPStan** level 7 | tipos e correção | reporta |
| **FilaCheck** | API do Filament | reporta |

O Rector ocupa um quarto eixo — **reescrita automatizada** — e é o único dos quatro que **muda a
semântica** do código, não só a forma. Num starter kit, cujo produto é justamente o código-exemplo
legível e anotado, isso não é detalhe.

## O que foi medido (RQ-01, RQ-04, RQ-05, RQ-06)

### O pacote

| | |
|---|---|
| Nome | `driftingly/rector-laravel` |
| Versão | **2.5.0** |
| Exige | `rector/rector ^2.2.7` — o Rector **não** vem junto, é dependência separada |
| PHP | `^7.4 \|\| ^8.0` |
| Set de Laravel 13 | ✅ `LaravelSetList::LARAVEL_130` e `LaravelLevelSetList::UP_TO_LARAVEL_130` |

### Regras de Filament v5 — **não existem** (RQ-04)

Busca por "filament" no pacote inteiro: **zero ocorrências**. O `rector-laravel` cobre Laravel, e
só.

**Mas o Filament tem a própria ferramenta, e ela também é Rector**: `filament/upgrade`, versão
**v5.7.6**, que declara `rector/rector: ^2.0` como dependência. É o caminho oficial de upgrade de
major do Filament (`vendor/bin/filament-v4` e equivalentes), versionado em lockstep com o próprio
framework.

Consequência para o kit: **não há lacuna a preencher aqui**. Quem quiser automatizar upgrade de
Filament usa o pacote do Filament, não um set de regras nosso.

### O que o Rector faria neste código — **103 arquivos**

`rector process --dry-run` sobre `app/`, `database/`, `routes/` e `tests/`, com os sets de qualidade
do Laravel mais PHP 8.4:

| Regra | Arquivos | Veredito para o kit |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | ❌ opinião de estilo — `User::find()` é o idioma que todo mundo lê |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | ⚠️ inofensivo, mas 26 arquivos por `: void` em closure |
| `AppToResolveRector` | 21 | ❌ `app()` → `resolve()` é preferência, não correção |
| `AddTypeToConstRector` | 17 | ⚠️ cosmético |
| `CarbonToDateFacadeRector` | 7 | 🔴 **reintroduz defeito** — ver abaixo |
| `ThrowIfAndThrowUnless...` + `ThrowIfRector` | 12 | ❌ estilo |
| `StringClassNameToClassConstantRector` | 3 | ⚠️ |
| `ReadOnlyPropertyRector` / `ReadOnlyClassRector` | 4 | 🔴 muda comportamento |
| resto | 8 | — |

### O defeito que o Rector reintroduziria (a evidência decisiva)

`CarbonToDateFacadeRector` propõe, em `app/Providers/Filament/InfraPanelProvider.php`:

```diff
- Carbon::now()->subDays((int) config('kit.retencao.excecoes_em_dias', 14)),
+ Date::now()->subDays((int) config('kit.retencao.excecoes_em_dias', 14)),
```

Por que isso quebra, provado e não deduzido:

1. `now()` **é** `Date::now()` — `vendor/laravel/framework/src/Illuminate/Foundation/helpers.php:623`
2. O kit faz `Date::use(CarbonImmutable::class)` — `app/Providers/KitServiceProvider.php:57`
3. Logo `Date::now()` devolve `CarbonImmutable`
4. `FilamentExceptionsPlugin::modelPruneInterval()` exige `Carbon` **mutável**

Este é exatamente o erro que o PHPStan level 7 reportou nesta mesma branch, quando a primeira versão
do código usava `now()`:

> `Parameter #1 $interval of method ...::modelPruneInterval() expects Carbon\Carbon,`
> `Carbon\CarbonImmutable given.`

O `Carbon::now()` explícito, com o comentário que o justifica, **é a correção**. O Rector a
desfaria.

> **Uma ferramenta de qualidade que reverte a correção de outra ferramenta de qualidade não é um
> gate — é uma disputa.** É o argumento central de ADR-02.

## Matriz de Decisão (RQ-02, RQ-03, RQ-07)

| Papel | Agrega valor? | Decisão |
|---|---|---|
| **Gate de lint** (`--dry-run` no `composer test`) | **Não** | ❌ recusado — ADR-02 |
| **Ferramenta de upgrade** (Laravel/PHP major) | **Sim** | ✅ adotado — ADR-01 |
| **Refatoração em massa do código atual** | Não agora | ⏸️ fora de escopo, decisão do mantenedor |

## Autorização · Rotas · Superfície de UI · Eventos · Jobs

**Nenhum.** A entrega é dependência de desenvolvimento, arquivo de configuração e documentação.
**Sem superfície de UI** — logo, sem `05-casos-de-teste-browser.md`.

## Variáveis de Ambiente

Nenhuma.

## Impacto em Features Existentes

- **`composer test`**: **não muda**. O Rector fica fora do gate, por decisão.
- **CI**: não muda.
- **`kit:update`**: `rector.php` entra em `KitUpdate::CAMINHOS_DO_KIT`, senão quem já instalou nunca
  recebe o arquivo — e o `tests/Kit/KitUpdateTest.php` reprova.

## Rollback

`composer remove --dev rector/rector driftingly/rector-laravel` e apagar `rector.php`. Nenhum código
de aplicação depende deles.

## Dependências

- **Composer (dev)**: `rector/rector ^2.6`, `driftingly/rector-laravel ^2.5`
- **NPM**: nenhuma

## Riscos

- **Alguém rodar `rector process` sem `--dry-run` e commitar 103 arquivos.** *Mitigação*: o
  `rector.php` entregue **não** carrega os sets de qualidade — só os de upgrade, comentados e
  desligados por padrão. Mais o aviso no topo do arquivo e na wiki.
- **O `rector.php` apodrecer** quando sair Laravel 14. *Mitigação*: o arquivo diz onde trocar o set,
  em uma linha.

## Channel de Log da Feature

**Nenhum log, e nenhum channel.** A entrega não executa nada em runtime: é dependência de
desenvolvimento, um arquivo de config e documentação. Declarado aqui para que ninguém "corrija a
falta de log" depois.

## Estrutura de Implementação

### 1. Medição (concluída antes deste plano)

> Skills: —

- Verificação do pacote no Packagist: versão, dependências, sets disponíveis.
- Busca por regras de Filament: zero.
- Descoberta do `filament/upgrade` como ferramenta oficial equivalente.
- `rector process --dry-run --output-format=json` com sets de qualidade → 103 arquivos, agrupados
  por regra.
- Prova do defeito do `CarbonToDateFacadeRector` contra `helpers.php:623` e `KitServiceProvider:57`.

### 2. Decisão registrada

> Skills: —

- `02-decisoes-arquiteturais.md` com ADR-01 (adotar como upgrade), ADR-02 (recusar como lint),
  ADR-03 (Filament tem ferramenta própria).

### 3. Dependências

> Skills: —

- `composer require --dev rector/rector:^2.6 driftingly/rector-laravel:^2.5`

### 4. `rector.php` — configuração de **upgrade**, não de lint

> Skills: `laravel-best-practices`

- **Path**: `rector.php` (raiz)
- Paths analisados: `app`, `database`, `routes`, `tests`
- Sets **ativos**: nenhum por padrão — o arquivo nasce com os sets de upgrade **comentados**, para
  serem ligados no momento do upgrade
- Comentário de topo com: o que este arquivo é, o que ele **não** é, e por que os sets de qualidade
  estão fora (com o caso do Carbon nomeado)
- Cache em `storage/framework/cache/rector` para não sujar a raiz

### 5. Script no `composer.json`

> Skills: —

- `"refactor:preview"` → `rector process --dry-run`
- `"refactor:apply"` → `rector process`
- **Nenhum dos dois entra em `composer test`** — é o ponto da decisão
- `rector.php` acrescentado a `KitUpdate::CAMINHOS_DO_KIT`

### 6. Wiki (RQ-08)

> Skills: —

- **`wikis/qualidade-de-codigo.md`** (novo): as quatro ferramentas, o eixo de cada uma, o que roda no
  gate e o que roda sob demanda, e a tabela de quando usar o Rector
- `wikis/pacotes.md`: linha nova na tabela do "já existe"
- `wikis/README.md`: entrada no índice

### 7. READMEs (RQ-09)

> Skills: —

- Seção sobre a esteira de qualidade, com Rector no papel correto e o aviso de que ele **não** está
  no `composer test` — e por quê

### 8. Teste do contrato

> Skills: `pest-testing`

- `tests/Kit/QualidadeDeCodigoTest.php`: o `rector.php` existe, é válido, **não** tem set de
  qualidade ligado, e o `composer test` **não** invoca Rector

## Filosofia de Implementação

> **Ponytail em `full`.** A escada aqui deu um resultado incomum e vale registrar: o degrau 1
> ("isto precisa existir?") **recusou** metade do pedido. O lint já é coberto por três ferramentas;
> a quarta só entra no papel que nenhuma das três ocupa.
>
> **Caveman `ultra`** na conversa. Arquivos de wiki, código e commits são boundary.

## Testes

> Ver `04-casos-de-teste.md`. **Sem CT-B** — não há superfície de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel`
- [ ] `vendor/bin/filacheck`
- [ ] `composer refactor:preview` roda sem erro e **não** propõe mudança (sets desligados)

## Commits

- `:sparkles: feat(qualidade): Rector como ferramenta de upgrade, fora do gate de lint`
- `:memo: docs: esteira de qualidade do kit — Pint, PHPStan, FilaCheck e Rector`
