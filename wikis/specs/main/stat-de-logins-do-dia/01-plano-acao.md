# Plano de Ação — Stat de logins do dia em "Usuários e acesso"

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/main/graficos-com-apexcharts/` — é ela que fixou a fronteira
  "qual pacote desenha o quê" (ADR-01 de lá), e este stat toca essa fronteira.
- **Motivo**: um sexto stat, com série temporal dentro da própria caixa. A fronteira da ancestral
  não previu sparkline em Stat, que não é widget de gráfico nem stat card puro.
- **Toca infra compartilhada?**: **não**. Nenhuma migration, nenhum seeder, nenhum middleware,
  nenhuma config. Um arquivo alterado.

> Tipo `evolução` **dispara regressão** no quality gate contra os CT da wiki ancestral e contra os
> casos existentes de `UsuariosVisaoGeralStats` — o widget alterado já tem cobertura viva em
> `tests/Kit/PermissoesDeWidgetsTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Sexto stat no widget | 2 | |
| RQ-02 | Valor = logins do dia | 1, 2 | sob premissa: só bem-sucedidos |
| RQ-03 | Gráfico dentro do stat | 2 | `Stat::chart()`, nativo do Filament — ver ADR-01 |
| RQ-04 | Histórico de 7 dias | 1, 2 | sob premissa: hoje é a última posição |
| RQ-05 | Fonte é a tela de logs de acesso | 1 | `Rappasoft\...\AuthenticationLog`, a mesma do `AuthenticationLogResource` |
| RQ-06 | Seis stats, linha harmônica | 2 | degrada para 5 sem a tabela — ver ADR-03 |

## Objetivo

Dar ao administrador, na primeira linha de números do painel `/admin`, a resposta para "o sistema
está sendo usado hoje?" — hoje o widget conta cadastro (quantos existem, quantos são novos,
quantos têm 2FA) e não conta **uso**. Um cadastro grande com zero login é indistinguível de um
cadastro grande com uso diário.

O sexto stat traz o número de logins de hoje com um sparkline de 7 dias na mesma caixa, o que dá
a leitura que nenhum dos cinco atuais dá: o valor de hoje **contra o próprio ritmo da semana**.
Resolve, de quebra, a linha ragged de 5 caixas numa grade de 3 colunas.

## Contexto

- `App\Filament\Admin\Widgets\UsuariosVisaoGeralStats` tem hoje 5 `StatPlus` e nenhum gráfico.
  O docblock dela diz, textualmente, "StatsOverview e não gráfico: são cinco grandezas sem relação
  entre si — não há composição nem série temporal a desenhar". **Este passo torna essa frase
  falsa** e ela precisa ser reescrita junto com o código, senão o próximo agente lê o comentário e
  desfaz a feature achando que está corrigindo uma inconsistência.
- A "tela de logs de acesso" é o `AuthenticationLogResource` do `tapp/filament-authentication-log`,
  em `/infra`, sobre a tabela `authentication_log` do `rappasoft/laravel-authentication-log`. É
  tabela de **plugin opcional**: o kit já a trata com `Schema::hasTable()` em todos os widgets que
  a consomem.
- `Stat::chart(array $chart)` existe no Filament 5 (`vendor/filament/widgets/src/StatsOverviewWidget/Stat.php:106`)
  e a view usa `array_keys()` como rótulos e `array_values()` como série
  (`stat.blade.php:67-68`), renderizando com **Chart.js já embarcado** em `filament/widgets`.
- Precedente de agrupamento por dia no kit: `IaExecucoesPorDia::contarPorDia()` agrupa **em PHP**,
  com o motivo escrito — `DATE()` muda de nome em SQLite, MySQL e PostgreSQL, e o kit roda nos três.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php`

Único arquivo de produção alterado. Ganha:

- um `use` de `AuthenticationLog` e de `Carbon`;
- um sexto item em `getStats()`, **condicional**;
- dois métodos privados: a série por dia e a disponibilidade da fonte;
- docblock de classe reescrito (ver Contexto).

Os cinco stats atuais e os dois helpers privados existentes (`contarUsuariosComDoisFatores()`,
`descreverCobertura()`) **não são tocados**.

### `app/Filament/Infra/Widgets/IaExecucoesPorDia.php`

Não é alterado. É a **fonte do padrão** de `contarPorDia()` que o passo 1 copia — e a duplicação
é deliberada, ver ADR-02.

### `app/Filament/Concerns/ExigePermissaoDoWidget.php`

Não é alterado, e é justamente por isso que ele importa aqui: o widget já o usa, e o trait publica
`canView()` como `permissão && fonteDeDadosDisponivel()`. Declarar `fonteDeDadosDisponivel()` nesta
classe para guardar a tabela de log **esconderia os cinco stats existentes** numa instalação sem o
plugin. Ver ADR-03.

## Autorização

Nada novo. O widget já é gated por `View:UsuariosVisaoGeralStats` via `ExigePermissaoDoWidget`, a
permission já existe no banco e já está na matriz do `PapeisSeeder`. Um stat a mais dentro de um
widget já autorizado não cria superfície de autorização nova.

- **Policies / Gates / Middleware / Guards**: nenhum criado ou modificado.
- **Seeders**: **não rodar** `ShieldPermissionsSeeder`/`PapeisSeeder`. Não há entidade nova; rodá-los
  seria no-op. Registrado para ninguém "corrigir" a ausência.

## Rotas

Nenhuma rota nova nem alterada. O widget vive no dashboard do painel `admin` (`/admin`), rota já
existente.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `UsuariosVisaoGeralStats` — sexto stat com sparkline | Filament (StatsOverviewWidget) | `/admin` | leitura | **Sim** — o sparkline é renderizado por Chart.js num `<canvas>` |

**Gate de CT-B**: o sparkline é a **única** coisa desta feature que depende de JavaScript
executado, e o que ele desenha (7 pontos numa curva de 40px) não é falsificável por assertion
textual — `assertSee` não vê `<canvas>` desenhado. Mas o que se pode afirmar sobre ele **sem**
navegador é justamente o que importa e o que quebra: o array de 7 posições que vai para o
`chart()`, os rótulos, os zeros dos dias sem login e o valor do dia.

A decisão sobre criar ou não o `05` é da `feature-test-design`, e o único candidato plausível é
"a tela `/admin` não ganha erro de JavaScript com o `<canvas>` novo" — que já é coberto pela
varredura de telas existente do kit.

**Gate de tela de escrita**: não se aplica. Esta feature não acrescenta rota `create`/`edit` e não
grava nada.

## Variáveis de Ambiente

Nenhuma. A janela de 7 dias é constante privada da classe — ver ADR-04 da wiki
`insights-das-organizacoes`, que decidiu o mesmo para a janela de 30 dias e pelo mesmo motivo.

## Eventos / Listeners / Observers

Nenhum. Esta feature só lê.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`UsuariosVisaoGeralStats` (os 5 stats atuais)**: alterada. Nenhum valor muda, mas a classe
  passa a consultar uma tabela nova. O risco é a consulta estourar e derrubar o widget inteiro —
  mitigado pela guarda do passo 1.
- **Dashboard do `/admin`**: uma consulta a mais por carregamento. Ver Riscos.
- **`tests/Kit/PermissoesDeWidgetsTest.php`**: já exercita `UsuariosVisaoGeralStats` e já insere
  linha em `authentication_log` no `beforeEach`. Precisa continuar verde — é a regressão obrigatória
  do tipo `evolução`.
- **`.ai/rules/filament.md` → "Qual pacote de widget"**: a rule diz "Gráfico é
  `filament-apex-charts`". Este sparkline usa Chart.js. Ver ADR-01; a rule é candidata a emenda no
  step 9.

## Rollback

- **Migration down**: não se aplica — não há migration.
- **Feature flag**: não se aplica.
- **Reversão**: `git revert` de um commit em um arquivo. A feature é puramente aditiva e de
  leitura; não há dado a desfazer.

## Dependências

Nenhuma nova.

- **Composer**: `filament/widgets` (Chart.js embarcado, já é dependência dura do Filament) e
  `rappasoft/laravel-authentication-log` (já instalado via `tapp/filament-authentication-log ^5.0`).
- **NPM**: nada. O Chart.js do stat é servido pelo próprio Filament, não pelo Vite do projeto —
  `FilamentAsset::getAlpineComponentSrc('stats-overview/stat/chart', 'filament/widgets')`.

## Riscos

- **Consulta a mais no dashboard**: a série de 7 dias traz as linhas da janela para agrupar em PHP.
  Numa instalação com muito login, são 7 dias de linhas em memória a cada carregamento do `/admin`.
  **Mitigação**: a consulta seleciona **uma coluna só** (`login_at`), o que a torna barata mesmo com
  volume; e a janela é fixa em 7 dias. Se doer, o caminho é medir com `--profile` antes de cachear.
- **Docblock que contradiz o código**: o comentário atual da classe afirma que não há série
  temporal a desenhar. **Mitigação**: reescrevê-lo no mesmo commit é item do passo 2, não sugestão.
- **Harmonia perdida sem o plugin**: numa instalação sem `authentication_log` o widget volta a 5
  stats e a linha ragged retorna. **Mitigação**: nenhuma — é o comportamento correto, registrado
  como premissa em RQ-06 e decidido em ADR-03.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `autenticacao` (linha 132), `tenancy` (123), `configuracoes` (153) e `ai` (114).

### Decisão

**Nenhum channel novo, e nenhum log novo.**

> Widget de leitura não loga. Uma linha por carregamento de dashboard, por widget, não responde a
> nenhuma pergunta que alguém vá fazer — e o evento que este stat conta (o login) **já é
> registrado**, na tabela `authentication_log`, que é justamente a fonte que ele lê. Logar aqui
> seria escrever no log que se leu o log.

É a mesma decisão da wiki `insights-das-organizacoes`, e pelo mesmo motivo.

## Estrutura de Implementação

### 1. Série de logins por dia, dentro de `UsuariosVisaoGeralStats`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php`
- Constante privada de classe: `private const DIAS_DO_HISTORICO = 7;`
- Método `private function loginsPorDia(): array` — devolve **7 posições**, chaveadas pelo rótulo
  `d/m`, da mais antiga (`hoje - 6`) para hoje:

  ```php
  $primeiroDia = Carbon::today()->subDays(self::DIAS_DO_HISTORICO - 1);

  $porDia = AuthenticationLog::query()
      ->where('login_successful', true)
      ->whereBetween('login_at', [$primeiroDia, Carbon::today()->endOfDay()])
      ->get(['login_at'])
      ->map(fn (AuthenticationLog $acesso): mixed => $acesso->getAttribute('login_at'))
      ->filter(fn (mixed $data): bool => $data instanceof DateTimeInterface)
      ->countBy(fn (DateTimeInterface $data): string => $data->format('Y-m-d'))
      ->all();

  $serie = [];

  for ($i = 0; $i < self::DIAS_DO_HISTORICO; $i++) {
      $dia            = $primeiroDia->copy()->addDays($i);
      $serie[$dia->format('d/m')] = $porDia[$dia->toDateString()] ?? 0;
  }

  return $serie;
  ```

- **O eixo é construído a partir do calendário, não do resultado da consulta.** Dia sem login tem
  de aparecer como `0`; se ele for omitido, a curva "pula" o buraco e um fim de semana sem
  ninguém vira um trecho reto — mentindo sobre o uso. É a mesma razão escrita em
  `IaExecucoesPorDia::quantidadesPorDia()`.
- **Agrupamento em PHP, não `GROUP BY DATE(login_at)`** — a função de data muda de nome em cada
  banco e o kit roda em SQLite, MySQL e PostgreSQL. Ver ADR-02.
- Método `private function logDeAcessoDisponivel(): bool` — guarda da tabela opcional:

  ```php
  return (bool) rescue(
      fn (): bool => Schema::hasTable((string) config('authentication-log.table_name', 'authentication_log')),
      false,
      report: false,
  );
  ```

- **Método de instância, `private`, e NÃO `fonteDeDadosDisponivel()`.** O nome está reservado pelo
  trait `ExigePermissaoDoWidget` e sobrescrevê-lo aqui esconderia o widget **inteiro** — os cinco
  stats que não dependem de log de acesso junto. Ver ADR-03.
- **Sem log.**

### 2. O sexto stat em `getStats()`

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php`
- O array de retorno passa a ser montado e, **no fim**, recebe o stat condicional:

  ```php
  $stats = [ /* os cinco existentes, sem alteração */ ];

  if ($this->logDeAcessoDisponivel()) {
      $serie = $this->loginsPorDia();

      $stats[] = StatPlus::make('Logins hoje', (int) end($serie))
          ->icon('heroicon-o-arrow-right-on-rectangle')
          ->iconColor('success')
          ->accentColor('success')
          ->chart($serie)
          ->chartColor('success')
          ->description('Entradas confirmadas · série dos últimos '.self::DIAS_DO_HISTORICO.' dias');
  }

  return $stats;
  ```

- **O valor do stat sai da última posição da série, não de uma segunda consulta.** Duas consultas
  para o mesmo número abrem a porta para número e gráfico discordarem — bastaria um filtro
  divergir entre elas. Uma consulta, um array, o valor é a ponta direita da curva.
- **`end($serie)`, e não `array_values($serie)[self::DIAS_DO_HISTORICO - 1]`** — corte da auditoria
  Ponytail. A segunda forma faz aritmética de índice sobre um array chaveado por rótulo, e um
  off-by-one ali exibiria **ontem** como hoje, com um valor plausível e nada vermelho. Com
  `end()` esse defeito deixa de ser expressável.
- `StatPlus` e não `Stat` nativo: é a regra do kit para stat card, e `StatPlus` herda
  `chart()`/`chartColor()` do `Stat` do Filament — o pacote não substitui nenhum dos dois
  (README do `filament-stat-plus-easy`: *"Chart, description, url, polling — nada é substituído"*).
- `chartColor('success')` explícito: sem ele o gráfico herda `getColor()`, que no `StatPlus` pode
  não ser o mesmo do `accentColor()`.
- **Reescrever o docblock da classe.** A frase atual — *"StatsOverview e não gráfico: são cinco
  grandezas sem relação entre si — não há composição nem série temporal a desenhar"* — passa a ser
  falsa. O novo texto diz que cinco stats são grandezas de **cadastro**, o sexto é de **uso**, e
  que ele é o único com série porque é o único cuja leitura depende do ritmo dos dias anteriores.
- **Sem log.**

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> A escada, aplicada a esta feature:
> 1. **Reutilizar** — o padrão de `contarPorDia()` já existe em `IaExecucoesPorDia`
> 2. **Stdlib** — `Collection::countBy()` faz o agrupamento; nenhum loop manual
> 3. **Nativo** — `Stat::chart()` do Filament, com o Chart.js que já vem embarcado; nenhuma
>    dependência, nenhum asset, nenhuma view publicada
> 4. **Uma linha** — o valor do stat é `array_values($serie)[6]`, não uma consulta nova
> 5. **Mínimo que funciona** — um arquivo alterado, dois métodos privados
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — prosa normal. Código, commits e PRs também.

## Mapeamentos

| Conceito do requisito | Onde vive no schema | Como é lido |
|---|---|---|
| "login" | `authentication_log.login_successful = true` | linha da tabela |
| "no dia" | `authentication_log.login_at` agrupado por `Y-m-d` | `countBy()` em PHP |
| "últimos 7 dias" | janela `[hoje-6 00:00, hoje 23:59:59]` | `whereBetween` |
| "gráfico dentro do stat" | `Stat::chart()` | rótulos = `array_keys`, série = `array_values` |
| "tela de logs de acesso" | `AuthenticationLogResource` → `authentication_log` | mesmo model do `/infra` |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários.
> A existência do `05-casos-de-teste-browser.md` é decidida pelo gate da `feature-test-design`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --filter=LoginsDoDia`
- [ ] Regressão (tipo `evolução`): `php artisan test --compact tests/Kit/PermissoesDeWidgetsTest.php tests/Kit/InventarioDeTelasTest.php`
- [ ] `vendor/bin/pest --parallel --tia` — confirma o que mais o diff afetou
- [ ] Abrir `/admin` e conferir 6 caixas com o sparkline na sexta

## Commits

- `:sparkles: feat(admin): stat de logins do dia com histórico de 7 dias`
- `:memo: docs(wiki): wiki da feature stat-de-logins-do-dia`
