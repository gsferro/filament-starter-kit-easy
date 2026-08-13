# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [0.9.3] - 2026-08-13

### Corrigido

- **Trocar para o painel `/app` estourava `TypeError` com tenancy ligada.**
  O `FilamentManager::getTenantName()` é tipado como `string` e cai em
  `$tenant->getAttributeValue('name')` quando o model não implementa `HasName`
  — a coluna do kit é `nome`, então o retorno era `null`. `App\Models\Tenant`
  passa a implementar `Filament\Models\Contracts\HasName`.

  A lição fica documentada em `wikis/arquitetura.md`: toda coluna em pt-BR que
  o Filament espera em inglês precisa de um contrato, e esse tipo de erro só
  aparece ao renderizar a página — nenhum teste de model chega lá.

- Testes de **requisição HTTP real** ao painel de negócio (`/app/{slug}`), que
  é o que teria pego o erro acima. Um deles trava também a propriedade de
  segurança de responder **404, e não 403**, num tenant não vinculado: 403
  confirmaria a existência do tenant e permitiria enumerar os clientes da
  instalação por varredura de slug.

### Alterado

- Os testes passam a ligar a tenancy pelo **ambiente**, antes do bootstrap: o
  `AppPanelProvider` lê a flag durante o boot para registrar as rotas com
  `/{tenant}`, e config ajustada depois chegava tarde (as rotas nasciam sem o
  segmento e o painel dava 404).
- `wikis/receitas.md` corrigido: acesso a tenant não vinculado é 404, não 403.

## [0.9.2] - 2026-08-13

### Corrigido

- **`kit:tenancy` criava as tabelas de permissão sem a coluna de tenant.** O
  comando rodava `config:clear` e em seguida `migrate:fresh` no MESMO processo —
  mas `config:clear` apaga o arquivo de cache, não recarrega a config já em
  memória. A migration do spatie lia `permission.teams` ainda como `false` e
  criava as tabelas sem `team_id`. O banco ficava de pé, o comando terminava com
  sucesso, e o erro só aparecia no primeiro login:
  `SQLSTATE[HY000]: no such column: model_has_roles.team_id`.

  Agora o comando alinha a config em memória e descarta o singleton
  `PermissionRegistrar` antes de migrar, e **confere o schema ao final** — se a
  coluna não existir, falha alto em vez de entregar uma instalação quebrada.

- Dois testes novos travam a invariante: a existência das colunas de team e a
  atribuição de papel no contexto global (o caminho dos seeders).

### Alterado

- `App\Policies\TenantPolicy` passa a ser a saída canônica do `shield:generate`
  (assinaturas com o model, conjunto completo de métodos).

## [0.9.1] - 2026-08-13

### Adicionado

- **Multi-tenancy opt-in.** `php artisan kit:tenancy` liga o modo multi-tenant;
  sem ele o kit continua single-tenant e nada muda. Com o modo ligado, o painel
  `/app` vira `/app/{tenant}` e o usuário só enxerga os tenants aos quais está
  vinculado; o `/admin` ganha o cadastro de tenants e o vínculo de usuários; o
  `/infra` segue global, porque saúde, filas e logs são da instalação e não de
  um cliente.
- **Vocabulário separado do rótulo.** O código usa o padrão da API do Filament
  (`Tenant`, `tenants`, `tenant_id`, `getTenants()`), e o que o usuário lê vem de
  `config('kit.tenancy')` — `label`, `label_plural` e `slug`, que nascem como
  "Organização"/"organizacoes" e cada projeto troca pelo termo do seu negócio
  sem tocar em código.
- **`App\Traits\BelongsToTenant`** para as models de negócio: relação `tenant()`,
  escopo global e preenchimento automático de `tenant_id`. O escopo existe porque
  o Filament só recorta o que passa por um Resource — job, comando, listener e
  API ficariam de fora.
- **Papéis por tenant** (`permission.teams`): definição do papel global
  (`roles.team_id` nulo) e atribuição por tenant. Como `model_has_roles.team_id`
  é NOT NULL e o spatie não tem atribuição global, o kit usa o sentinela
  `Tenant::CONTEXTO_GLOBAL` para os papéis que governam `/admin` e `/infra`.
- **Cenário de demonstração** com `--demo`: dois tenants, três usuários e um
  resource no `/app` para ver o isolamento funcionando. Descartável — o comando
  imprime quais arquivos apagar.
- Ledger de IA e budget passam a gravar o tenant real (`ai_runs.tenant_id`).
- Suíte `tests/Tenancy/` (14 casos), no mesmo grupo `kit`.

### Alterado

- `composer test:kit` passa a rodar `--group=kit`, cobrindo as duas suítes.

## [0.8.0] - 2026-08-13

### Adicionado

- **`wikis/` — a documentação que o agente de IA lê antes de codar.** Sete
  documentos com o que o código não conta sozinho: arquitetura (os três
  painéis, a "cola", o ciclo do request, os três níveis de autorização),
  convenções e armadilhas já resolvidas, a camada de IA (agente como dado,
  fail-closed, ledger), receitas passo a passo, o mapa de agentes e skills e a
  lista de "quem é dono de qual tela" — para não reimplementar vendor.
  `wikis/README.md` é o ponto de entrada; `wikis/specs/{branch}/{feature}/`
  continua sendo onde a skill `feature-wiki` grava cada feature.
- **Skills e plugins de IA no kit.** `feature-wiki` (de
  `gsferro/laravel-ai-skills`) instalada via Boost e sincronizada para os cinco
  agentes; no Claude Code, os plugins Ponytail e Caveman habilitados em
  `.claude/settings.json`. As três cobrem camadas distintas — comunicação,
  planejamento e execução — e a fronteira entre elas está documentada.
- **README em inglês** (`README.en.md`), com troca de idioma no topo dos dois
  arquivos, e banner próprio (`art/banner-en.png`).
- **Seção "Pacotes instalados"** nos dois READMEs: 46 dependências, 11 de
  desenvolvimento e 6 de front-end, agrupadas por função no kit, com nota sobre
  os motores que rodam por baixo dos plugins.
- **Thumbnail 16:9** (`art/thumbnail.png`) para a página do plugin no
  filamentphp.com.
- Badge do Filament nos READMEs.

### Alterado

- Imagens dos READMEs passam a apontar para `raw.githubusercontent.com`, para
  renderizarem também no Packagist e em qualquer lugar fora do GitHub.

## [0.7.2] - 2026-08-12

### Adicionado

- `kit:update` recria pastas de teste declaradas no `phpunit.xml` que não
  existem em disco, com um `.gitkeep`. É a outra metade do bug da 0.7.1: quem
  já tinha o projeto criado não recebia a correção, porque `tests/Feature` é
  pasta do usuário e não entra nos caminhos do kit — e sem a pasta o PHPUnit
  aborta com exit 2.

## [0.7.1] - 2026-08-12

### Corrigido

- **`composer test` abortava com `Test directory "tests/Feature" not found`**
  em projeto novo. Ao mover os testes do kit para `tests/Kit`, a pasta
  `tests/Feature` ficou vazia — e git não versiona diretório vazio, então ela
  não existia no pacote distribuído e o PHPUnit parava com exit 2.
  Agora o kit entrega um `tests/Feature/ExemploTest.php` que serve de ponto de
  partida e mantém a pasta no repositório.

## [0.7.0] - 2026-08-12

### Corrigido

- **A busca ⌘K não aparecia na topbar.** O gatilho estava no render hook
  `USER_MENU_BEFORE`, que no Filament 5.7 renderiza DENTRO do dropdown do
  usuário. Agora usa `GLOBAL_SEARCH_BEFORE`, emitido pela topbar
  incondicionalmente — é o lugar exato do campo nativo.
- O gatilho passa a reusar a **marcação nativa** do campo de busca do Filament
  (lupa, sufixo com o atalho, mesmo visual), em vez de um botão próprio. O
  overlay abre em `setTimeout`: sem isso o próprio clique é visto como
  "clique fora" e fecha o painel recém-aberto.
- Ações "Criar X" na busca: a categoria de ações do pacote não estava
  registrada, então nada aparecia.

### Adicionado

- `App\Filament\Spotlight\AcoesDeCriacao`: sugestões "Criar X" com três
  guards (`canAccess`, `canCreate`, `shouldRegisterNavigation`). O discovery do
  pacote fica desligado — ele não checa permissão e derruba a tela de login
  com 500 ao resolver URLs sem contexto.
- Traduções pt-BR da busca (`lang/vendor/filament-search-spotlight`) e do painel
  de colunas fixas: o placeholder da topbar era a primeira coisa em inglês num
  painel inteiro em português.
- README reescrito: seção da busca ⌘K, badges de contagem (incluindo por que
  resources de terceiros não podem ter), armadilhas já resolvidas e capturas
  atualizadas.

## [0.6.1] - 2026-08-12

### Alterado

- Mensagem de "nada a atualizar" reescrita: diz que o projeto está na versão
  mais atual quando é o caso, e distingue o cenário de comparar com uma versão
  antiga (onde dizer "atualizado" seria mentira).

## [0.6.0] - 2026-08-12

### Corrigido

- **`composer test` falhava num projeto recém-instalado**: o `shield:generate`
  escreve as policies com o estilo dele, e o Pint reprovava três arquivos logo
  na primeira execução. O `kit:install` passa a formatar o código gerado.
- **`phpunit.xml` entra nos caminhos do `kit:update`** — sem ele a testsuite
  `Kit` nunca chegava a quem já tinha o projeto criado, e `composer test:kit`
  não existia.

### Adicionado

- `kit:update` relata o que mudou no `composer.json` do kit (pacotes e scripts)
  sem nunca aplicá-lo: sobrescrever esse arquivo apagaria as dependências do
  projeto. Foi assim que o script `test:kit` deixou de chegar em quem atualizou.

## [0.5.1] - 2026-08-12

### Alterado

- `kit:update` avisa quando atualiza a si próprio: o PHP já carregou a classe
  antiga em memória, então o comportamento (e as mensagens) da versão nova só
  valem na execução seguinte. Sem o aviso, parecia que a melhoria não tinha
  funcionado.
- README documenta que `config/kit.php` sempre aparece como modificado e que
  aplicá-lo substitui o arquivo inteiro, incluindo suas customizações.

## [0.5.0] - 2026-08-12

### Adicionado

- Testes do kit isolados em `tests/Kit` (testsuite `Kit` e grupo `kit`), com o
  atalho `composer test:kit`. Depois de um `kit:update` dá para verificar só a
  fundação, sem esperar a suíte do seu negócio. `tests/Feature` e `tests/Unit`
  ficam livres para os seus testes.

### Alterado

- `kit:update` grava a versão aplicada em `config/kit.php` automaticamente —
  antes ele pedia a edição manual, e esquecer isso estragava o diff da próxima
  atualização. Só a linha da versão é reescrita.
- `kit:update` passa a trazer também `tests/Kit`, para que a suíte da fundação
  acompanhe a atualização.

## [0.4.0] - 2026-08-12

### Adicionado

- `kit:update` aplica em lote: opções `--only-new` (só arquivos que ainda não
  existem no projeto — não sobrescreve nada) e `--all` (tudo, com uma
  confirmação para o conjunto). Durante a revisão arquivo a arquivo também é
  possível mudar para lote a qualquer momento, sem recomeçar.
- Com `--only-new`/`--all` o comando passa a ser scriptável: a aprovação veio
  na linha de comando, então ele roda sem terminal interativo.

## [0.3.1] - 2026-08-12

### Corrigido

- `kit:update --dry-run` não exige mais árvore de trabalho limpa: um relatório
  não altera nada, e cobrar isso atrapalhava justamente quem quer olhar antes
  de mexer. A exigência continua valendo para aplicar mudanças.
- O erro de árvore suja agora **lista os arquivos** que impedem a execução e
  lembra da opção `--dry-run` — antes só dizia que havia pendências.

## [0.3.0] - 2026-08-12

### Adicionado

- Comando `php artisan kit:update`: compara o projeto com uma versão nova do kit,
  mostra o que mudou e aplica só o que for aprovado, arquivo a arquivo. Vincula o
  repositório do kit de forma temporária e somente-leitura (tags em namespace
  `kit-*`), sugere um branch de trabalho e desfaz o vínculo ao sair.
- `config/kit.php` passa a registrar a versão do kit que originou o projeto,
  usada pelo `kit:update` como ponto de partida da comparação.
- README: seção completa sobre atualizar um projeto existente (comando e passo
  a passo manual).

## [0.2.0] - 2026-08-12

### Adicionado

- Documentação visual: banner, GIF da instalação e capturas dos três painéis em `art/`.
- Badges de downloads e de status dos testes no README.
- Dashboards preenchidos nos painéis admin e infra (StatPlus + widgets de funil,
  meta, breakdown, timeline e composição) sobre os dados que os painéis já têm.
- Badge de contagem animado no menu (`App\Filament\Concerns\BadgeContagemNavegacao`).
- Colunas redimensionáveis, reordenáveis e fixáveis como padrão de toda tabela,
  documentadas em "Configuração global do Filament".

### Corrigido

- **Spotlight (⌘K) não abria em nenhum painel**: faltavam as categorias e um
  gatilho visível — a busca nativa do Filament tinha sido desligada sem
  substituto. As categorias do kit checam `canAccess()`, então a busca não
  oferece tela que resultaria em 403.
- **Conflito de JavaScript entre pacotes**: os bundles do Pulse (dotswan) e do
  resized-column declaram constantes no escopo global; o segundo a carregar
  morria inteiro com `SyntaxError: Identifier '$e' has already been declared`,
  derrubando os gráficos do Pulse sem nenhum erro visível na tela. Agora os dois
  são carregados como ES module.
- **Grupos de navegação do painel infra** misturavam inglês e português
  (`Settings`, `System`, `Logins`): agora são Observabilidade, IA, Trilhas e Sistema.
- **Página 403 do Sentinel**: traduzida para pt-BR, expõe o diagnóstico de
  permissão apenas fora de produção, identifica a conta pelo e-mail em vez do id
  e o botão "Voltar" retorna à página anterior em vez da raiz.
- Demais páginas de erro (404, 419, 500, 503) traduzidas.
- Ações de filtro NÃO são mais customizadas globalmente: num `configureUsing()`
  elas atingiam tabelas sem filtro e derrubavam 8 telas do painel infra com
  `LogicException: Action ... must have a unique name`.
- Painel de colunas fixas (resized-column) traduzido para pt-BR.

## [0.1.0] - 2026-08-12

### Adicionado

- Skeleton Laravel 13 + Filament 5 instalável via `composer create-project gsferro/starter-kit-easy`.
- Comando `kit:install`: cria `.env`, gera `APP_KEY`, prepara o banco SQLite, migra,
  semeia papéis/permissões/usuário, publica assets e faz o build do front-end.
  Roda sozinho no `post-create-project-cmd` e é idempotente.
- Três painéis: `/app` (negócio, vazio de propósito), `/admin` (usuários, Shield,
  agentes de IA, onboarding) e `/infra` (health, backups, filas, logs, auditoria,
  caches, Command Center, Pulse, custos de IA).
- Fundação: traits `TemUuid` e `AuditsFillables`, `Gate::before` para `master_global`,
  `CarbonImmutable`, `prohibitDestructiveCommands` em produção, `Password::defaults()`,
  configuração global do Filament (tabelas, toggles, Panel Switch).
- Núcleo de IA com `laravel/ai`: catálogo de agentes no banco, guardrails encadeados,
  ledger `ai_runs` e chat com streaming. Inferência local por padrão (llama.cpp).
- Docker com profiles opt-in: `pgsql`+`redis` na base, `ai`, `mail`, `app`, `realtime`.
- Qualidade: Pest, Pint (setas alinhadas), PHPStan level 6, CI com job que prova o
  `create-project` ponta a ponta.
- Traduções pt-BR (laravel-lang) e UI dos painéis em português.
