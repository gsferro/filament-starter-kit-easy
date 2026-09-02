# Plano de Ação — `kit:info`: um comando que exibe os dados customizados do projeto

> Requisito: `00-requisito.md`
>
> **Implementado.** Este arquivo é o plano como foi escrito **antes** do código; três detalhes da
> tabela de seções abaixo mudaram na execução — o separador da linha de divergência (é `·`, não
> `→`), o formato dos dois helpers de teste e a técnica de derrubar o banco em CT-14. O que valeu
> está em `03-progresso.md` → **Desvios do Plano**, e é essa a versão a seguir.

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (nova). Wikis **relacionadas**, lidas antes de escrever:
  `wikis/specs/feat/settings-do-kit/settings-do-kit/` (ADR-01: o banco vence o `.env`) e a
  customização do `kit:install`, documentada no docblock de `CustomizadorDaInstalacao`.
- **Motivo**: o resumo *"O que foi customizado nesta instalação"* só aparece uma vez, no fim do
  `kit:install`; as configurações do kit vivem em duas fontes com precedência que ninguém enxerga
  pelo terminal. Não há como responder "como este projeto está customizado?" sem abrir três lugares.
- **Toca infra compartilhada?**: **sim, de leve** — dois métodos de `App\Settings\ConfiguracoesDoKit`
  ganham uma extração (passos 1 e 2) e `KitServiceProvider::configureSettingsDoKit()` passa a
  chamar um deles. Comportamento **idêntico**, guardado por casos já existentes em
  `tests/Kit/ConfiguracoesDoKitTest.php` (`:135`, `:156`, `:637`). Regressão: rodar esse arquivo
  inteiro, não só o novo.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Comando novo no namespace `kit:` | 3 | descoberta automática em `app/Console/Commands` — nenhum registro manual |
| RQ-02 | Exibe as cinco respostas da instalação, vigentes | 3 (seção *Instalação*) | sob A1; senha nunca exibida — aponta para `kit:admin` |
| RQ-03 | Exibe as configurações do kit, vigentes, sem segredo em claro | 3 (seção *Configurações do kit*) | sob A1; `ConfiguracoesDoKit::encrypted()` decide o que é segredo |
| RQ-04 | Somente leitura | 3 (todo) | nenhuma escrita em `.env`, banco, `config/` ou cache; o refactor dos passos 1-2 não muda o que já grava |
| A2 | Diz de onde o valor vem e onde o `.env` diverge | 1, 2, 3 (cabeçalho + seção condicional), 4 | premissa assumida; removível sem tocar no resto |

## Objetivo

Criar `php artisan kit:info`, um comando **somente leitura** que imprime, no mesmo visual do resumo
do `kit:install`, como o projeto está customizado **agora**: a versão do kit, qual fonte de
configuração está valendo, as cinco respostas da instalação, as 44 configurações do kit com os
valores vigentes (segredos mascarados), a lista do que o `.env` diz diferente do banco — só quando
houver — e os itens que continuam sendo ajustados à mão, com o comando ou a tela que muda cada coisa.

## Contexto

Três lugares respondem partes da pergunta e nenhum responde inteira:

- `php artisan config:show kit` mostra a config efetiva, mas não sabe que ela veio do banco, e mistura
  o que é customização com o que é constante do kit.
- `/admin/configuracoes-do-kit` mostra o banco, e nada do `.env`. Quem edita o `.env` e "nada muda" não
  tem onde ver por quê (`ConfiguracoesDoKit.php:14-23`).
- O resumo do `kit:install` (`KitInstall.php:488-505`) é a resposta certa e some ao terminar.

## Análise dos Arquivos Existentes

### `app/Settings/ConfiguracoesDoKit.php`

- `mapaDeConfiguracao()` (`:269-374`) — propriedade → chave de `config()`. **É a lista que o comando
  itera**: acrescentar propriedade no futuro aparece no comando sem edição. O docblock da classe
  (`:65-68`) já manda manter esse mapa como a única cópia.
- `encrypted()` (`:248-258`) — os seis segredos. O comando os mostra como *definida / vazia*.
- `devolverConfigAoEnv()` (`:408-419`) — relê os arquivos de `config/` para as chaves do mapa e
  **escreve** em `config()`. O comando precisa dos **valores** relidos sem escrever: extrair a
  leitura para `valoresDosArquivos()` (passo 2) e fazer `devolverConfigAoEnv()` chamá-la.
- O teste `:135-154` (tabela ausente é silêncio) e `:637-659` (devolver ao `.env`) guardam os dois
  refactors.

### `app/Providers/KitServiceProvider.php`

- `configureSettingsDoKit()` (`:175-191`) — `Schema::hasTable(config('settings.repositories.database.table') ?? 'settings')`
  dentro de `try/catch (Throwable)`. Vira `ConfiguracoesDoKit::gravadoNoBanco()` (passo 1). O
  `try` **fica**: o docblock (`:150-160`) explica que `hasTable()` lança em banco inexistente.

### `app/Support/CustomizadorDaInstalacao.php`

- `propagarParaOSettings()` (`:343-376`) — a mesma expressão de `hasTable()` (`:346`). Passa a chamar
  `gravadoNoBanco()`. Já está dentro de `try/catch (Throwable)`.
- `itensManuais()` (`:378-389`) — **reutilizado** tal como está, para a última seção do comando.
- `CORES` (`:58-61`) — não é preciso: a cor vigente é lida por `CorPrimaria::paleta()`.

### `app/Console/Commands/KitInstall.php`

- `resumoDaCustomizacao()` (`:488-505`) — o **visual** a copiar: `$this->components->info()` como
  título de seção, `twoColumnDetail('<fg=gray>{item}</>', valor)` por linha, `bulletList()` para os
  manuais. Nada é alterado aqui.

### `app/Console/Commands/KitAdmin.php`

- `Str::mask((string) $u->email, '*', 3)` (`:59`, `:171`) — o precedente de mascaramento do e-mail
  do administrador (A4). O comando repete a expressão.

### `app/Support/AdministradorDaInstalacao.php`

- `todos()` (`:47-56`) — a coleção de `master_global`. Devolve coleção de propósito (pode haver mais
  de um); o comando lista todos. **Toca o banco** — precisa da guarda do passo 3.

### `app/Support/CorPrimaria.php`

- `paleta()` (`:64-67`) — `[]` = padrão do Filament; `['primary' => '#hex']` quando o hex venceu;
  `['primary' => [...]]` (array de tons) quando foi o nome. É por esse formato que o comando decide
  o rótulo da cor, sem repetir a regra de precedência (ADR-03).

### `app/Models/Tenant.php`

- Só `Tenant::count()`, para "N cadastradas" quando a multi-organização está ligada. **Toca o banco**.

### Documentação

- `docs/pt/comecar/instalacao-avancada.md:31-52` e `docs/en/comecar/instalacao-avancada.md:31-52` —
  bloco *Comandos*; e a tabela *Personalize seu projeto* (`:59-75`) ganha uma frase.
- `README.md:298-309` e `README.en.md:298-309` — mesmo bloco de comandos, repetido no README (é o que
  o Packagist mostra — `CHANGELOG.md`, v0.24.0).
- `tests/Kit/SiteDeDocumentacaoTest.php` CT-04/CT-05 exigem paridade pt/en de páginas; adicionar as
  mesmas linhas nos dois idiomas mantém a suíte verde.

## Autorização

Nenhuma. Comando artisan não tem policy; quem tem acesso ao terminal já tem acesso ao `.env`. **O
comando não amplia o que esse acesso revela**: segredos saem como *definida / vazia*, e o e-mail do
administrador sai mascarado (A4). Ver ADR-04.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** É um comando de console. Não há `05-casos-de-teste-browser.md`.

## Variáveis de Ambiente

Nenhuma nova. O comando **lê** as existentes através de `config()`.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Settings do kit** (`ConfiguracoesDoKit`): dois métodos refatorados por extração (passos 1 e 2).
  Guardas: `ConfiguracoesDoKitTest` `:135` (tabela ausente → silêncio), `:156` (banco lança →
  warning), `:637` (devolver ao `.env`). Rodar o arquivo inteiro.
- **`kit:install --custom`** (`propagarParaOSettings`): uma linha trocada; guarda em
  `tests/Kit/CustomizadorDaInstalacaoTest.php` (único arquivo que existe — não há `KitInstallTest`).
- **Documentação**: paridade pt/en (`SiteDeDocumentacaoTest` CT-04, CT-05, CT-19).

## Rollback

- Apagar `app/Console/Commands/KitInfo.php` e `tests/Kit/KitInfoTest.php` (e o de `tests/Tenancy`).
- Os passos 1 e 2 são extrações sem mudança de comportamento; podem ficar. Reverter é inline de volta.
- Reverter as linhas de documentação e do `CHANGELOG.md`.
- Sem migration, sem `.env`, sem cache.

## Dependências

Nenhuma nova. Tudo é `laravel/framework` (`^13.17`) já instalado: `Illuminate\Console\Command`,
`$this->components->twoColumnDetail()/info()/bulletList()`, `Str::mask()`.

## Riscos

- **Branch**: a wiki nasce em `feat/paleta-do-filament-na-organizacao`, que tem **outra feature em
  andamento, não commitada** (9 arquivos, `git diff --stat` de 2026-09-02). Recomendação: fechar
  aquela entrega ou abrir `feat/kit-info` e mover esta pasta com `git mv` antes de implementar.
  Mitigação: os passos aqui não tocam nenhum dos 9 arquivos, exceto `docs/*/recursos/configuracoes-do-kit.md`,
  que **não** será editado nesta feature (menção ao comando fica em `instalacao-avancada.md`).
- **Comparação `.env` × banco** (A2): tipos divergem por natureza (`MAIL_PORT` chega string do
  `env()`, o settings grava `int`). Comparar sem normalizar produziria divergência falsa em toda
  instalação. Mitigação: `normalizar()` no passo 3, com caso de teste dedicado (ver `04`).
- **Banco indisponível**: `AdministradorDaInstalacao::todos()` e `Tenant::count()` lançam sem
  banco/migrations. Mitigação: guarda `noBanco()` no passo 3; o comando **não** falha — imprime
  *indisponível* na linha e segue.
- **Custo do `gravadoNoBanco()` no boot**: é a mesma chamada que já existe, só extraída. Zero.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php:153-160` já tem o canal **`configuracoes`** — *"Configurações do kit gravadas em
/admin (spatie/laravel-settings)"* — com `handler => NullHandler::class` e driver por
`LOG_KIT_DRIVER`, o desenho que impede a suíte de escrever em `storage/logs`. É o canal certo: o
comando é uma **leitura** das configurações do kit.

### Decisão

**Reutilizar `configuracoes`.** Nada em `config/logging.php` muda. Um único log, `debug`, no fim do
`handle()` — a nota do canal (`:146-149`) proíbe ruído por abertura de tela; um comando rodado de
propósito, uma vez, é o oposto disso, mas `debug` é o nível honesto para "alguém olhou".

## Estrutura de Implementação

### 1. `ConfiguracoesDoKit::gravadoNoBanco()` — a pergunta "o banco está valendo?" num lugar só

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Settings/ConfiguracoesDoKit.php`
- Novo método estático, abaixo de `group()`:

  ```php
  /**
   * A tabela de settings existe? É o que decide se o banco está valendo sobre o `.env`.
   *
   * Lança em banco inexistente — `Schema::hasTable()` conecta antes de responder. Quem chama
   * decide o que fazer com isso: o provider silencia (é o primeiro `migrate` de uma instalação
   * nova), o `kit:info` imprime "indisponível".
   */
  public static function gravadoNoBanco(): bool
  {
      return Schema::hasTable(config('settings.repositories.database.table') ?? 'settings');
  }
  ```

  Import: `use Illuminate\Support\Facades\Schema;` (a classe hoje importa só `Log` e `Settings`).
- **Path**: `app/Providers/KitServiceProvider.php:178-182` — substituir as duas linhas
  (`$tabela = ...; if (! Schema::hasTable($tabela)) return;`) por
  `if (! ConfiguracoesDoKit::gravadoNoBanco()) { return; }`. O `try/catch (Throwable)` **fica**.
  Verificar na revisão profunda se `Schema` continua usado no provider; se não, remover o import.
- **Path**: `app/Support/CustomizadorDaInstalacao.php:346` — idem, dentro do `try` existente.
- **Logs**: nenhum novo — os dois chamadores já logam nos seus `catch`.

### 2. `ConfiguracoesDoKit::valoresDosArquivos()` — reler os arquivos sem escrever na config

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Settings/ConfiguracoesDoKit.php:408-419`
- Extrair de `devolverConfigAoEnv()`:

  ```php
  /**
   * O que os ARQUIVOS de `config/` (e o `.env` que eles leem) dizem para cada chave do mapa,
   * como se o banco não existisse. Não escreve nada.
   *
   * Relê os arquivos, e não `env()` direto, porque é neles que mora a coerção de cada chave
   * (`FILTER_VALIDATE_BOOLEAN`, `NumeroDoEnv`, default).
   *
   * @return array<string, mixed> chave de `config()` → valor do arquivo
   */
  public static function valoresDosArquivos(): array
  {
      $arquivos = [];
      $valores  = [];

      foreach (self::mapaDeConfiguracao() as $chave) {
          [$arquivo, $caminho] = explode('.', $chave, 2);
          $arquivos[$arquivo] ??= require config_path($arquivo.'.php');

          $valores[$chave] = data_get($arquivos[$arquivo], $caminho);
      }

      return $valores;
  }

  public static function devolverConfigAoEnv(): void
  {
      config(self::valoresDosArquivos());
  }
  ```

  O docblock atual de `devolverConfigAoEnv()` (o porquê — `kit:install --force` e o caso medido do
  `KIT_SOCIALITE_GOOGLE`) **permanece nele**; o novo método fica com a nota sobre reler arquivos.
- **Guarda**: `ConfiguracoesDoKitTest.php:637` continua verde sem edição.
- **Logs**: nenhum.

### 3. `App\Console\Commands\KitInfo` — o comando

> Skills: `laravel-best-practices`, `ponytail`, `pest-testing`

- **Path**: `app/Console/Commands/KitInfo.php` — criar com
  `php artisan make:command KitInfo --no-interaction` e reescrever.
- Descoberta automática: o Laravel 13 registra tudo em `app/Console/Commands`; os outros sete
  comandos do kit não têm registro manual (confirmado: nenhum `commands()` em `routes/console.php`
  com esses nomes — **verificar na revisão profunda**).

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Settings\ConfiguracoesDoKit;
use App\Support\AdministradorDaInstalacao;
use App\Support\CorPrimaria;
use App\Support\CustomizadorDaInstalacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mostra como ESTE projeto foi customizado — e de onde cada valor está vindo.
 *
 * O resumo do `kit:install` responde a pergunta uma vez e some. A tela de settings mostra o banco e
 * nada do `.env`. O `config:show` mostra a config efetiva sem dizer a origem. Este comando reúne os
 * três, é SOMENTE LEITURA, e aponta para quem muda cada coisa.
 *
 * Não expõe mais do que o `.env` já expõe a quem tem o terminal: segredo sai como "definida /
 * vazia", e-mail do administrador sai mascarado (mesma régua do `kit:admin`).
 */
class KitInfo extends Command
{
    protected $signature = 'kit:info';

    protected $description = 'Mostra como este projeto foi customizado: instalação, configurações do kit e de onde cada valor vem';

    public function handle(): int
    {
        $doBanco = $this->noBanco(fn (): bool => ConfiguracoesDoKit::gravadoNoBanco()) === true;

        $this->cabecalho($doBanco);
        $this->instalacao();
        $this->configuracoes();

        $divergencias = $doBanco ? $this->divergencias() : [];

        $this->manuais();

        Log::channel('configuracoes')->debug(
            '[KitInfo@handle] Resumo exibido | fonte: '.($doBanco ? 'banco' : 'env'),
            ['fonte' => $doBanco ? 'banco' : 'env', 'divergencias' => array_keys($divergencias)],
        );

        return self::SUCCESS;
    }
}
```

**Seções** (métodos privados; cada linha é `twoColumnDetail('<fg=gray>{rótulo}</>', valor)`; cada
título de seção é `$this->components->info()`; `newLine()` entre seções, como
`KitInstall::resumoDaCustomizacao()`):

| Seção | Rótulo | Valor | Fonte |
|---|---|---|---|
| **cabeçalho** | `Versão do kit` | `config('kit.version')` | `config/kit.php:22` |
| | `Fonte da configuração` | `$doBanco` → `banco (/admin/configuracoes-do-kit) — o .env semeia e é o plano B`; senão → `.env — a tabela de settings ainda não existe` | `ConfiguracoesDoKit.php:21-23` |
| **Instalação** — título *"O que a instalação perguntou:"* | `Nome do projeto` | `config('app.name')` | |
| | `Banco de dados` | `{config('database.default')} — {database.connections.{default}.database}`; para conexões com `host`, `{host}/{database}` | |
| | `Administrador da instalação` | `AdministradorDaInstalacao::todos()` → `#id — e-mail mascarado`, separados por `, `; coleção vazia → `nenhum — rode php artisan db:seed --class=UsuarioAdminSeeder`; sem banco → `indisponível (banco não acessível)` | mascaramento: `Str::mask($email, '*', 3)` |
| | `Senha do administrador` | `não exibida — troque com php artisan kit:admin` | ADR-04 |
| | `Cor primária` | `CorPrimaria::paleta()`: `[]` → `padrão do Filament (âmbar)`; `is_string($paleta['primary'])` → `{hex} (hexadecimal — vence o nome)`; senão → `{config('kit.cor_primaria')} (paleta do Filament)` | ADR-03 |
| | `Multi-organização` | `config('kit.tenancy.enabled')` → `ligada — {label_plural} em /admin/{slug}, {N} cadastrada(s)` (N por `Tenant::count()` via `noBanco()`; sem banco → `ligada — {label_plural} em /admin/{slug}`); senão → `desligada — php artisan kit:tenancy` | |
| **Configurações do kit** — título *"Configurações do kit (/admin/configuracoes-do-kit):"* | `Str::headline($propriedade)` para cada par de `mapaDeConfiguracao()`, **na ordem do mapa** | `$this->exibir($propriedade, config($chave))` | ADR-02 |
| **Divergências** — título *"Onde o .env diz diferente do banco:"* — **só impresso se houver alguma** | a chave de config (`app.name`, `mail.mailers.smtp.port`…) | `.env: {arquivo} → banco: {vigente}`; para segredo: `diverge (valores não exibidos)` | A2, ADR-05 |
| **Manuais** — título *"O que continua sendo ajustado à mão:"* | `bulletList(CustomizadorDaInstalacao::itensManuais())` | | reuso |
| **rodapé** | `Para mudar` | `kit:install --custom (nome e cor) · /admin/configuracoes-do-kit · kit:admin · kit:tenancy` | |

**Helpers privados**:

```php
/**
 * Roda algo que toca o banco; sem banco, devolve `null` em vez de derrubar o comando.
 * Um resumo com uma linha "indisponível" vale mais do que nenhum resumo.
 */
private function noBanco(callable $consulta): mixed
{
    try {
        return $consulta();
    } catch (Throwable) {
        return null;
    }
}

/** Como um valor aparece na tela: segredo vira "definida/vazia", booleano vira "sim/não", vazio vira "—". */
private function exibir(string $propriedade, mixed $valor): string
{
    if (in_array($propriedade, ConfiguracoesDoKit::encrypted(), true)) {
        return filled($valor) ? 'definida' : 'vazia';
    }

    return match (true) {
        is_bool($valor)  => $valor ? 'sim' : 'não',
        blank($valor)    => '—',
        is_array($valor) => implode(', ', $valor),
        default          => (string) $valor,
    };
}

/**
 * `.env` × banco comparam pelo TEXTO, porque os tipos divergem por natureza: `MAIL_PORT` chega
 * string do `env()` e o settings grava `int`. Sem isto, toda instalação "diverge" na porta.
 *
 * @return array<string, array{arquivo: mixed, vigente: mixed}> chave de config → par
 */
private function divergencias(): array
{
    $divergentes = [];

    foreach (ConfiguracoesDoKit::valoresDosArquivos() as $chave => $doArquivo) {
        $vigente = config($chave);

        if ($this->normalizar($doArquivo) !== $this->normalizar($vigente)) {
            $divergentes[$chave] = ['arquivo' => $doArquivo, 'vigente' => $vigente];
        }
    }

    // imprime a seção só se $divergentes !== []

    return $divergentes;
}

private function normalizar(mixed $valor): string
{
    return match (true) {
        is_bool($valor)  => $valor ? '1' : '0',
        $valor === null  => '',
        is_array($valor) => json_encode($valor, JSON_THROW_ON_ERROR),
        default          => (string) $valor,
    };
}
```

Para a seção de divergências, a chave de config precisa virar propriedade para saber se é segredo:
`array_flip(ConfiguracoesDoKit::mapaDeConfiguracao())[$chave]`. Segredo divergente imprime só
`diverge (valores não exibidos)`.

- **Logs**: um só, `debug`, no fim do `handle()` (código acima). Nenhum `warning` — banco
  indisponível já é logado pelo provider no boot; repetir aqui seria a mesma linha duas vezes.
- **Somente leitura (RQ-04)**: o comando não chama `config([...])` (por isso o passo 2 separa
  `valoresDosArquivos()` de `devolverConfigAoEnv()`), não escreve arquivo, não grava settings.

### 4. Documentação e changelog

> Skills: nenhuma específica — prosa

- **Path**: `docs/pt/comecar/instalacao-avancada.md` — bloco *Comandos* (`:31-52`), após a linha
  de `kit:admin --email=...`:
  `php artisan kit:info              # mostra como o projeto está customizado e de onde cada valor vem`.
  Tabela *Personalize seu projeto* (`:59-75`): uma frase antes da tabela — *"`php artisan kit:info`
  mostra o valor atual de cada item abaixo, e se ele está vindo do banco ou do `.env`."*
- **Path**: `docs/en/comecar/instalacao-avancada.md` — as mesmas duas edições, em inglês:
  `php artisan kit:info              # shows how the project is customized and where each value comes from`
  e *"`php artisan kit:info` shows the current value of every item below, and whether it comes from
  the database or the `.env`."*
- **Path**: `README.md:298-309` e `README.en.md:298-309` — a linha do bloco de comandos, nos dois
  idiomas (o README repete o bloco de propósito — v0.24.0 do `CHANGELOG.md`).
- **Path**: `CHANGELOG.md` → `## [Unreleased]` → `### Adicionado`, uma entrada:
  *"**`php artisan kit:info`** mostra como o projeto foi customizado — as respostas da instalação, as
  configurações do kit com os valores vigentes (segredos mascarados), qual fonte está valendo (banco
  ou `.env`) e onde o `.env` diz diferente do banco. Somente leitura; aponta para o comando ou a tela
  que muda cada coisa."*
- **Não editar** `docs/*/recursos/configuracoes-do-kit.md` nesta feature (arquivo em edição pela
  feature da branch — ver Riscos).

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo — `mapaDeConfiguracao()`, `encrypted()`,
>    `itensManuais()`, `AdministradorDaInstalacao::todos()`, `CorPrimaria::paleta()`, o visual do
>    `resumoDaCustomizacao()`
> 2. Usar stdlib do PHP/Laravel antes de código custom — `Str::headline`, `Str::mask`, `twoColumnDetail`
> 3. Usar features nativas antes de dependências — nenhuma dependência
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

## Mapeamentos

Nenhum além da tabela de seções do passo 3.

## Testes

> Ver `04-casos-de-teste.md` — derivado pela `feature-test-design` a partir do `00-requisito.md`.
> Sem `05`: comando de console não tem superfície de navegador.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/KitInfoTest.php tests/Kit/ConfiguracoesDoKitTest.php`
- [ ] `php artisan test --compact tests/Tenancy/KitInfoTenancyTest.php`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php` — paridade pt/en após as edições de doc
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] `php artisan kit:info` à mão, uma vez com o banco migrado e uma vez com `DB_DATABASE` apontando para arquivo inexistente (a guarda `noBanco()`)

## Commits

- `✨ feat(kit:info): comando que mostra como o projeto está customizado e de onde cada valor vem`
- `📝 docs(kit:info): documenta o comando nos dois idiomas e no changelog`
- `📝 docs(wiki): wiki da feature kit-info`
