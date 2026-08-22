# Progresso — Customizador de instalação

## 1. Cor primária como configuração

- [x] `cor_primaria` em `config/kit.php`, com o bloco de comentário explicando paleta e precedência
- [x] `KIT_COR_PRIMARIA=` no `.env.example`
- [x] `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env.example` (descomentados)
- [x] `KIT_TENANCY_LABEL` / `_LABEL_PLURAL` / `_SLUG` no `.env.example` (comentados)

## 2. `App\Support\SubstituicaoEmArquivo`

- [x] Classe criada com `aplicar()` devolvendo se alterou
- [x] `KitTenancy` delega e perde o método privado

## 3. `App\Support\AtivadorDeTenancy`

- [x] `escreverEnv()`, `ligarPapeisPorTenant()`, `alinharConfigEmMemoria()`
- [x] Docblock dos prazos das três chaves migrado do `KitTenancy`
- [x] `KitTenancy` delega e mantém só o que é dele (git, destruição, demo, banner)

## 4. `CustomizadorDaInstalacao` — perguntas

- [x] Diretório-base injetável no construtor (sem isso a suíte reescreve o `.env` da máquina)
- [x] Guardas de pulo: `--no-custom`, `! input->isInteractive()` (nunca `stream_isatty`), resposta "não" (RQ-08)
- [x] Pergunta 1 — nome do projeto, default derivado da pasta
- [x] Pergunta 2 — banco (`sqlite` / `pgsql` / `mysql`) + `note` do pgvector (RQ-05, RQ-06)
- [x] Pergunta 3 — e-mail do admin, com validação
- [x] Pergunta 4 — senha do admin (`password`, vazio mantém default) (ADR-08)
- [x] Pergunta 5 — cor primária, lista fechada + "Padrão do Filament"
- [x] Pergunta 6 — multi-organização + rótulos singular/plural
- [x] Logs de pulo e de respostas (sem senha)

## 5. `CustomizadorDaInstalacao` — aplicação

- [x] `APP_NAME`
- [x] Bloco `DB_*` por driver (sqlite / pgsql com valores do compose / mysql no modelo Laravel)
- [x] `KIT_ADMIN_EMAIL` e `KIT_ADMIN_PASSWORD`
- [x] `KIT_COR_PRIMARIA`
- [x] Tenancy via `AtivadorDeTenancy` (env + configs)
- [x] Alinhamento da config em memória (R2) — por `alinharConexao()`, sem `DB::purge()` sem alvo
- [x] Logs de aplicação e de chave anexada

## 6. `KitInstall` — orquestração

- [x] Opções `--no-custom` e `--no-support` (sem `--custom`: `--force` já é a porta)
- [x] `handle()` na ordem nova, com o gate "o `.env` não existia **ou** `--force`" (RQ-03)
- [x] `conferirConexao()` e o pulo de migrate/seed com aviso acionável (ADR-06)
- [x] `resumo()` com valores escolhidos + os sete itens manuais (RQ-10, ADR-02)
- [x] `oferecerTestes()` (RQ-09)
- [x] `oferecerEstrela()` (RQ-13, ADR-09)
- [x] Logs de início, falha de conexão e conclusão

## 7. Cor primária nos três painéis

- [x] `->colors()` no `AppPanelProvider`
- [x] `->colors()` no `AdminPanelProvider`
- [x] `->colors()` no `InfraPanelProvider`
- [x] Guarda de constante inexistente
- [x] Precedência confirmada: a cor da organização continua vencendo no `/app` (R4)

## 8. Documentação

- [x] `README.md` — instalação interativa, "Personalize seu projeto", banco, comandos
- [x] `README.en.md` — o mesmo

## 9. Verificação manual do `create-project`

- [x] **FEITA na v0.16.0 — e reprovou.** `composer create-project gsferro/starter-kit-easy` num
      terminal Windows: instalou sem fazer **nenhuma** pergunta. Duas causas, ambas invisíveis
      para a suíte:
      1. o gate lia a existência do `.env`, que o `post-root-package-install` do Composer cria
         **antes** do `kit:install` (ADR-10) — a feature se pulava sozinha em toda instalação;
      2. o `artisan` rodou sem terminal, o que também pulou o convite da estrela (ADR-11).
      Corrigido na v0.16.1: o gate passou a ser `APP_KEY` vazia, e o pulo por falta de terminal
      virou aviso com o comando que refaz a instalação com as perguntas.
- [ ] **PENDENTE desde a v0.16.1 — hoje a árvore está na v0.18.1, 7 versões de deriva sobre a
      única camada não provada.** `composer create-project` real, a partir de um checkout local,
      com as perguntas aparecendo. Nenhum teste automatizado alcança a camada de TTY do Composer;
      o que está provado é que o Composer **repassa** o TTY (`EventDispatcher::executeTty`,
      verificado no `composer.phar` 2.9.5) e que o customizador pergunta quando
      `input->isInteractive()`.
      O job `instalacao` do `.github/workflows/ci.yml:83` **não cobre isto**: ele faz
      `cp .env.example .env` e roda `kit:install --ansi` sem terminal, então o customizador se
      pula pelo próprio guarda de `isInteractive()` — prova a instalação headless, não as
      perguntas. Só um terminal humano fecha este item.
- [ ] Resultado registrado em Notas de Implementação (SO, terminal, versão do Composer)

## Testes

- [x] `tests/Kit/CustomizadorDaInstalacaoTest.php` — CT-01…CT-18, CT-21, CT-25…CT-29 (26 casos)
- [x] `tests/Kit/CorPrimariaTest.php` — CT-22, CT-23 (6 casos)
- [x] `tests/Kit/TenancyNaInstalacaoTest.php` — CT-19 e a **ordem** de CT-20 (3 casos)
- [x] `tests/Tenancy/SchemaDaTenancyTest.php` — o **schema** de CT-20 (1 caso)
- [x] `tests/Tenancy/IdentidadeVisualTest.php` (existente) — CT-24, a precedência da cor
- [x] `pestphp/pest-plugin-mutate` declarado no `composer.json` (decisão do usuário)
- [x] Regressão: suíte `Kit` + `Tenancy` inteira — **303 casos, 961 asserções, verde**
- [x] Regressão: `tests/Kit/KitUpdateTest.php` (novos arquivos cobertos por `app/Support`)

## Verificação Final

- [x] `/ponytail:ponytail-review` — rodado no **plano** (step 6), 7 cortes aplicados
- [x] `/ponytail:ponytail-review` no **diff** implementado — nada a cortar; o único achado foi um
      log previsto no PRD que não existe no código (ver Desvios), e cortar já era o certo
- [x] `vendor/bin/pint --dirty --format agent` — 2 arquivos formatados
- [x] `php artisan test tests/Kit/CustomizadorDaInstalacaoTest.php` — 26/26
- [x] `php artisan test --testsuite=Kit,Tenancy` — 303/303
      (**não** `composer test:kit`: o `--group=kit` dele pendura na coleta do Playwright)
- [x] `composer types:check` — PHPStan, 0 erros
- [x] `vendor/bin/pest --mutate --path=app/Support --covered-only` — **275 mutantes, score 100%**
- [x] `git commit` — não commitado **naquela sessão**, para o usuário revisar; commitado e mergeado em `main` (`git branch --no-merged main` vazio)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| README item 3: os painéis definem `->colors([...])` | **Nenhum** dos três providers chama `->colors()`; a única cor registrada é a do tenant, por `FilamentColor::register()` no `bootUsing()` do `AppPanelProvider` | ADR-03 troca reescrita de código por `KIT_COR_PRIMARIA`; passo 7 passa a **criar** o `->colors()` |
| `kit:tenancy` poderia ser chamado no fim da instalação | `preVoo()` exige repositório git com árvore limpa — que `create-project` não tem — e `recriarBanco()` roda `migrate:fresh --seed` | ADR-04: extrair só os três passos não destrutivos; passo 3 |
| Escrever no `.env` bastaria para a instalação usar os valores novos | `prepararBancoSqlite()` lê `config('database.default')` e `semear()` lê `config('kit.admin.*')` — config já carregada não muda por escrita em arquivo (armadilha documentada em `KitTenancy::alinharConfigEmMemoria()`) | R2 + passo 5: alinhamento explícito em memória com `DB::purge()` |
| Um channel de log novo chega a todo mundo | `config/logging.php` **não** está em `KitUpdate::CAMINHOS_DO_KIT` | ADR-05 reescrita na auditoria: a feature não cria channel — `config/logging.php` não é tocado |
| Oferecer rodar os testes poderia afetar o banco recém-criado | `phpunit.xml` fixa `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:`; `Tests\TestCase` ainda força `permission.teams` por suíte | Passo 6: a oferta é segura, inclusive com tenancy ligada |
| Prompt dentro do `post-create-project-cmd` talvez não funcione | Composer 2.9.5: `EventDispatcher` usa `executeTty()` quando `io->isInteractive()` | RQ-01 viável; registrado no `00` e em R1 |
| `password()` do Prompts aceitaria default | `helpers.php:78` — não aceita | ADR-08 |
| `KIT_ADMIN_*` existiriam no `.env.example` para substituir | Não existem no arquivo | Passo 1 acrescenta `KIT_ADMIN_EMAIL` e `KIT_ADMIN_PASSWORD` antes |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `--custom` é uma segunda porta para o que `--force` já significa | **sim** | `01` passo 6; tabela de decisão da R1 no `04`; ADR-01 |
| 2 | dois interruptores para a estrela (`KIT_NO_SUPPORT` + `--no-support`) | **sim** — ficou só a flag | `01` (Variáveis de Ambiente e passo 6); ADR-09 |
| 3 | channel de log `instalacao` + helper de fallback + a ADR que existia só para defendê-lo | **sim** — passo 1 removido, `config/logging.php` não é mais tocado | `01` (Channel de Log, Estrutura); ADR-05 reescrita |
| 4 | CT-23 e CT-27 estavam no índice com `Mata: —` | **sim** — mutantes M39 e M40 nomeados | `04`, regras R8 e R9 |
| 5 | linha "{256 caracteres}" do CT-12 não discrimina nenhum mutante | **sim** — removida | `04`, CT-12 e "Cogitado e Cortado" |
| 6 | `KIT_ADMIN_NAME` entraria no `.env.example` sem nenhuma pergunta que a escreva | **sim** — fora | `01` (Variáveis de Ambiente e passo 1) |
| 7 | receita em `wikis/receitas.md` para um comando que a tabela do README já traz | **sim** — fora | `01` passo 8 |

**Saldo**: um passo de implementação a menos (9 em vez de 10), um arquivo de config a menos
(`config/logging.php`), um helper a menos, uma opção de comando a menos, uma chave de `.env` a
menos — e dois cenários que passaram a matar mutante nomeado em vez de existirem por simetria.

**Recusado**: extrair `SubstituicaoEmArquivo` e `AtivadorDeTenancy` para `app/Support` parece
abstração nova, mas os dois têm **dois chamadores reais** (`KitTenancy` e o customizador) e a
alternativa é duplicar o método privado. É reutilização, não camada.

## Desvios do Plano

- **A `ligarPapeisPorTenant()` também recebe o diretório de config.** O plano previu diretório-base
  injetável só no `CustomizadorDaInstalacao`; na prática o teste da tenancy reescreveria o
  `config/permission.php` **do projeto**, ligando `permission.teams` na máquina de quem roda a
  suíte. O parâmetro subiu para o `AtivadorDeTenancy`.
- **`DB::purge()` sem alvo saiu.** O plano mandava `DB::purge()` no alinhamento de config. Ele
  descarta a conexão *default* — que na suíte é o SQLite `:memory:`, e descartá-la **apaga o banco
  inteiro no meio do caso** (sintoma: `cannot VACUUM from within a transaction`, depois
  `table "migrations" already exists` em cascata). No lugar entrou `alinharConexao()`, que só toca
  driver não-sqlite e purga **aquela** conexão nomeada. E o desvio expôs um buraco real do plano:
  trocar `database.default` não bastava — o array `database.connections.{driver}` também precisa
  ser alinhado, senão uma reinstalação com nome novo migraria para o banco antigo.
- **`Command::getInput()` não existe** (o `$input` é protegido). A interatividade passou a ser
  parâmetro de `perguntar(Command $comando, bool $interativo)`, alimentado por
  `$this->input->isInteractive()` de dentro do `KitInstall`.
- **`App\Support\CorPrimaria` não estava no plano.** O passo 7 previa a resolução inline no
  `->colors()` dos três providers; a guarda contra constante inexistente ficaria duplicada três
  vezes num caminho que roda no boot de toda página. Virou uma classe de 20 linhas com um chamador
  por painel.
- **A oferta de testes usa `--testsuite=Kit,Tenancy`, não `--group=kit`.** O PRD (e o README)
  dizem `--group=kit`. Ele **não serve para um projeto recém-instalado**: o `pest-plugin-browser`
  sobe o Playwright na **coleta**, ao parsear qualquer arquivo com `visit()`, antes de qualquer
  filtro de grupo ser consultado — e num projeto novo os browsers não foram baixados, então o
  comando morre em `PlaywrightNotInstalledException` sem rodar um único teste. O CI do kit já
  tinha sido corrigido assim na 0.14.2; o instalador herdou a correção. Descoberto ao rodar a
  própria suíte durante a implementação, que pendurou por 10 minutos.
- **CT-20 mudou de lugar.** "O schema nasce com a coluna de contexto" não é verificável em
  `tests/Kit` (teams desligado). O par ficou: `tests/Kit/TenancyNaInstalacaoTest.php` prova a
  **ordem** (customização antes de `prepararBancoSqlite`/`migrar`, e nenhum `migrate:fresh` no
  comando) e `tests/Tenancy/SchemaDaTenancyTest.php` prova o **schema**.
- **O log "chave anexada ao final" não foi escrito.** O PRD o previa em `aplicar()`. Na
  implementação a substituição é uma chamada só, que já resolve os três estados da linha, e o
  chamador não sabe (nem precisa saber) qual deles ocorreu — o retorno diz apenas "alterou".
  Registrar isso exigiria propagar um motivo que não muda decisão nenhuma.

## Blockers

- Nenhum. O único item em aberto é a verificação manual do `create-project` (passo 9), que depende
  de um terminal humano e não de código.

## Notas de Implementação

- **O escape do `.env` é exatamente o que o phpdotenv aceita** — confirmado em
  `vendor/vlucas/phpdotenv/src/Parser/EntryParser.php:236-260`: dentro de aspas duplas só `\"`,
  `\\` e `\$` são escapes válidos; `\n` e afins passam por `stripcslashes`. Por isso a quebra de
  linha do valor vira **espaço** em vez de `\n` escapado: escapada, ela voltaria a ser uma quebra
  dentro do valor e derrubaria a expansão `${APP_NAME}` de `MAIL_FROM_NAME` e `VITE_APP_NAME`.
- **A precedência da cor é garantida pela ordem do `Panel::boot()`**, e não por sorte:
  `FilamentColor::register($this->getColors())` está em `Panel.php:95` e os `bootCallbacks` — onde
  vive a cor da organização — rodam depois, no mesmo método. `ColorManager::getColors()` sobrescreve
  a chave a cada registro, então quem registra por último vence.
- **`substr_count($env, 'APP_NAME=')` conta `VITE_APP_NAME=` junto.** A asserção de unicidade de
  chave precisa ser ancorada em início de linha (`/^APP_NAME=/m`).
- **Trocar `database.default` dentro de um teste envenena o `RefreshDatabase`**: a transação foi
  aberta na conexão antiga e o rollback do teardown procura na nova (`cannot start a transaction
  within a transaction`, e uma tentativa de conectar num Postgres real). O `afterEach` restaura a
  conexão antes do teardown. É restrição do arnês, não defeito do produto — na instalação de
  verdade não há transação aberta.
- **Mutação em `app/Support`: 275 mutantes, score 100%** (`pest --mutate --path=app/Support
  --covered-only`, 8,9 s). Nenhum mutante sobrevivente, logo nenhuma lacuna de derivação nova.
  Vale a ressalva que a própria `feature-test-design` faz: o score é piso de qualidade de
  assertion, e é **cego a omissão** — quem responde por cláusula não implementada é a matriz de
  rastreabilidade do `01`, não este número.

- **Um repositório `path` do Composer copia o diretório INTEIRO**, inclusive `.env`,
  `database/database.sqlite` e `vendor/` — arquivos que o zip do Packagist não tem. O primeiro
  smoke test montado assim nasceu com `APP_KEY` já preenchida e teria dado um falso resultado sobre
  o gate novo. Para simular a instalação de verdade, a fonte precisa ser só o que está versionado:
  `git ls-files -z | xargs -0 -I{} cp --parents "{}" destino/`.

## Retrospectiva

- **Funcionou bem**: verificar as premissas no vendor **antes** de escrever o plano. Três decisões
  que teriam custado retrabalho saíram de leitura de código, não de suposição: o TTY do Composer
  (`EventDispatcher::executeTty`), a ordem de registro de cor no `Panel::boot()` (linha 95 antes
  dos `bootCallbacks`) e os escapes que o phpdotenv aceita de verdade dentro de aspas duplas.
- **Faltou no plano**: o comportamento do arnês sob `RefreshDatabase`. O PRD tratou "alinhar a
  config em memória" como um passo trivial, e ele foi a origem dos dois únicos ciclos de vermelho
  da implementação (`DB::purge()` apagando o `:memory:`, e a troca de `database.default`
  envenenando a transação do teste). Um plano que toca conexão de banco deveria declarar
  explicitamente o que acontece **dentro da suíte**, não só em produção.
- **Faltou no plano**: que `--group=kit` não serve para projeto recém-instalado. Estava escrito no
  CHANGELOG do próprio kit (0.14.2) e não foi consultado na fase de pesquisa — o histórico de
  correções do projeto é fonte tão boa quanto o código.
