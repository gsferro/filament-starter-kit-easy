# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [0.18.6] - 2026-08-23

Release de correcao. Dois defeitos de fronteira de configuracao — um deles apagava dado — e os dois ultimos gates de
QA da fila.

### Corrigido

- **Prazo de retencao vazio ou zero apagava a trilha de excecoes inteira.**
  `modelPruneInterval()` recebe uma DATA, e `Exception::prunable()` faz
  `whereDate('created_at', '<=', $intervalo)`, que compara so a data:
  `KIT_RETENCAO_EXCECOES_DIAS=` dava `subDays(0)` = hoje, e o corte de hoje casa com a tabela
  inteira. Negativo era pior — punha o corte no futuro. E o `config/kit.php` promete por escrito
  que zero ou negativo **desliga** a poda: as tres podas de `routes/console.php` honram, esta era
  a quarta e fazia o oposto. **Se o seu `.env` tem essa chave vazia ou em 0 e o agendador roda,
  a trilha ja foi apagada.**
- **Valor vazio no `.env` desligava features em silencio, em cinco chaves.** O segundo argumento
  do `env()` so vale para chave AUSENTE; com valor vazio, `(int) ''` da 0 e o default nunca
  entra. O pior caso: `KIT_CONVITE_LIMITE_LOTE=` dava limite 0 e o convite em massa recusava
  **todo** lote, com a modal culpando a entrada da pessoa. A v0.18.4 corrigiu uma chave e nao
  varreu as outras — esta e a varredura.

### Adicionado

- **`App\Support\NumeroDoEnv`** com duas regras nomeadas: `positivo()` recusa o zero,
  `diasOuDesligado()` o respeita. A distincao e o ponto — unificar obriga a escolher um
  significado para o zero, e as retencoes precisam do oposto do que um limite de lote precisa.
- **Gate de QA em `convite-em-massa` e `lembretes-de-convite`**, os dois ultimos da fila. Os dois
  fecharam com cobertura **completa** (16/16 e 11/11 CT). Os defeitos acima nao vieram dos casos
  de teste: vieram de investigar como uma das features le a config.

### Alterado

- `admin_organizacao` -> `admin_app` em seis pontos de `convite-em-massa` — 43 no total somando
  as wikis desta auditoria.

## [0.18.5] - 2026-08-23

Release de correcao. Throttle na recusa anonima do convite, e o gate de QA da wiki que faltava —
o ultimo item da fila da auditoria aberta na 0.18.3.

### Corrigido

- **A recusa anonima do convite parava de escrever uma linha de log por request.** Um `curl` em
  laco com token inventado gravava um `warning` no channel `autenticacao` por tentativa — driver
  `daily`, 14 dias, e e o arquivo que o Logs Explorer do `/infra` abre. Agora `rateLimit()` de
  cinco por dez minutos por IP, com a trait que ja estava na classe pai.
  O throttle protege o **log**, nao a resposta: quem tem token valido nao pode ser barrado pelo
  vizinho de NAT, e um 429 numa tela de aceite trocaria uma mensagem clara por uma tela de erro.
  Forca bruta de token segue descartada — `Str::random(64)` sobre 62 caracteres, com resposta
  identica para os tres motivos de recusa.

### Adicionado

- **Gate de QA em `convite-para-usuario-existente`** — o primeiro desta auditoria que fecha
  **sem defeito de codigo**: 16 dos 16 CT tem teste, a lacuna do CT-03 ja estava declarada, e a
  barreira da feature (asserecao de e-mail no model, nao na query da tela) tem teste que a chama
  direto. Quatro hipoteses foram testadas e rejeitadas, e estao registradas.

### Alterado

- **`admin_organizacao` -> `admin_app`** em quatro pontos da wiki `convite-para-usuario-existente`;
  o papel foi renomeado por migration e nenhuma wiki soube.
- **O CT-15 de `convite-de-usuario` foi invertido** para dizer o que o codigo faz: e-mail ja
  cadastrado e **convidado**, nao recusado. A wiki `convite-para-usuario-existente` havia previsto
  esta inversao por escrito e o laco nunca foi fechado.

## [0.18.8] - 2026-08-23

Release de correcao. Um caminho nao destrutivo para refazer a customizacao, e a correcao de uma recomendacao
perigosa que a 0.18.7 escreveu no README.

### Corrigido

- **O README recomendava `kit:install --force` sem dizer que ele APAGA o banco.** A 0.18.7
  documentou o caminho do Windows como "instale normalmente e rode a configuracao depois, que da
  no mesmo" — verdade no minuto seguinte a instalacao, destrutivo depois: o `--force` apaga o
  SQLite antes de perguntar (`KitInstall.php`). Quem lesse aquela linha uma semana depois perdia
  o banco. Agora o README diz a ordem ("rode logo depois de instalar, com o banco so com dados de
  seed") e oferece a alternativa nao destrutiva.
- **O aviso do instalador** tambem apontava para o `--force` sem ressalva. Passa a dizer que ele
  recria o banco — "inocuo neste instante, destrutivo depois" — e a oferecer o `--custom`.

### Adicionado

- **`php artisan kit:install --custom`**: refaz **nome e cor** sem tocar em banco, seeder nem
  asset. Sai antes de `prepararBancoSqlite()`, entao nao passa perto do `File::delete()`.

  O recorte e conservador de proposito, e o comando imprime o motivo de cada corte: **banco**
  exige recriar (trocar SQLite por PostgreSQL depois do `migrate` e outra instalacao);
  **multi-organizacao** idem (as tabelas de permissao so nascem com a coluna de contexto antes do
  `migrate`); e **credenciais do administrador** passam pelo `UsuarioAdminSeeder`, que faz
  `firstOrCreate` por e-mail — mudar o endereco criaria um SEGUNDO `master_global` com o primeiro
  vivo. Um comando que promete "refazer as perguntas" e entrega dois administradores e pior que
  nao existir.

## [0.18.7] - 2026-08-23

Release de correcao. Fecha o ledger de divida tecnica — 11 de 11 — e encerra o item de TTY do instalador.

### Corrigido

- **DT-06 — a suite de navegador nascia vermelha na primeira execucao.** Compilar as ~590 views
  custa dezenas de segundos, e o primeiro cenario que renderiza um painel pagava a conta DENTRO
  do timeout de 45 s do Playwright. A conta passa a ser paga num `$this->get()` pelo kernel,
  fora do cronometro — a receita que `.ai/rules/testes-browser.md` ja prescrevia.
- **O falso achado de contraste do CT-B09 tinha outra causa, e a 0.18.4 errou o diagnostico.**
  Nao era cache frio: era a emulacao de `prefers-color-scheme` do cenario anterior alcancando o
  seguinte, o que produz paleta escura sobre fundo claro. O caso passa a **declarar** o tema com
  `->inLightMode()`. Ver a retificacao na secao da 0.18.4.

### Adicionado

- **Smoke HTTP das telas do `/app`** (DT-04), dataset de 8 rotas em `tests/Tenancy` — e nao em
  `tests/Kit`, onde sem tenancy a resposta seria 403 e provaria permissao em vez de "a tela
  abre". Na primeira execucao ele documentou que `/projetos` exige `kit.demo`.
- **README**: bloco explicando que **no Windows as perguntas do instalador nao aparecem** — o
  Composer nunca liga TTY ali — e que `php artisan kit:install --force` faz o mesmo depois.

### Alterado

- **DT-05 recusada, com medicao**: em ~20 episodios de teste desta auditoria, nenhuma falha veio
  de seletor fragil. `data-testid` fica disponivel sob demanda, como o `data-voltar-ao-topo` que
  o kit ja usa, em vez de espalhado por todas as telas antes de existir um teste que precise.
- **Item de TTY do instalador encerrado por decisao do usuario**, com o resultado medido nos dois
  shells do Windows: as perguntas nao aparecem em nenhum, e a causa esta no `vendor/` do Composer.
  Instalar e rodar `kit:install --force` depois e o caminho aceito por ora.

## [0.18.4] - 2026-08-23

Release de correcao. Os testes que faltavam nas wikis auditadas, uma divida fechada sem codigo e
um convite que nascia morto.

> **RETIFICACAO (0.18.7).** O trabalho desta release incluiu um `waitForEvent('networkidle')` no
> CT-B09, justificado — no PR #5, na mensagem do commit `456d97e` e no docblock do proprio caso —
> como conserto de um falso achado de contraste atribuido a **cache frio** ("varre antes de a
> folha de estilo assentar"). **O diagnostico estava errado.**
>
> A causa e emulacao de tema vazando entre cenarios: o caso anterior chama `->inDarkMode()` e a
> emulacao de `prefers-color-scheme` alcanca o seguinte, entao o Filament emite os tokens de
> texto do tema escuro sobre fundo claro. O experimento que derruba: em cache frio, rodar so
> aquele cenario passa; rodar o arquivo inteiro falha.
>
> O conserto de verdade e o caso DECLARAR o tema (`->inLightMode()`), na 0.18.7. O `networkidle`
> nao fez mal e ficou, mas nao era o que faltava. Esta entrada e as notas desta release **nao**
> afirmaram a causa errada — quem a afirmou foi o PR e o commit, e a retificacao esta la tambem.

### Corrigido

- **Prazo vazio no `.env` deixava o convite nascer morto.** `KIT_CONVITE_VALIDADE_DIAS=` (chave
  presente, valor vazio) devolvia string vazia, e o segundo argumento do `env()` nao alcanca esse
  caso — so vale para chave ausente. `(int) ''` da 0, `addDays(0)` grava `expira_em` igual ao
  instante do envio, e o `valido()` rejeita no primeiro clique: o e-mail sai, o log registra
  sucesso, e quem recebe ve "convite expirado". Agora
  `max(1, (int) (env(...) ?: 7))` no `config/kit.php`: vazio, `0` e ausente caem em 7; negativo e
  texto caem em 1. Guarda com dataset dos seis casos.

### Adicionado

- **CT-11, CT-14 e CT-15 de `perfil-e-acesso-ao-painel` ganham teste.** O CT-11 e o que a wiki
  chama de *"a falha silenciosa mais provavel de todo este plano"*: sem `painel` nas listas de
  exclusao, o `afterCreate` do Shield cria uma permission **chamada `app`** e nada falha. As duas
  metades (`CreateRole` e `EditRole`) sao separadas de proposito — as listas sao duas, e corrigir
  uma e esquecer a outra e o cenario realista.
- **`KIT_CONVITE_VALIDADE_DIAS` ganha teste**, com dataset de 3 e 30 dias — valores diferentes do
  default, porque com 7 o mutante do literal passaria.

Os quatro casos foram **vistos falhando** contra mutantes cirurgicos antes de serem aceitos.

### Alterado

- **DT-08 fechada sem conserto**, porque a correcao de duas linhas que ela prescrevia e no-op: o
  `FilamentView` embrulha o registro em `Facade::resolved()`, que instala um `afterResolving`
  persistente — toda instancia nova de `ViewManager` recebe de volta todos os hooks. O que a
  divida chamava de vazamento e o mecanismo deliberado do "Voltar ao topo", e a guarda contra
  regressao ja existia. Com esta, sao **cinco** prescricoes de divida que estavam erradas.
- **O `04-casos-de-teste.md` de `perfil-e-acesso` nomeava dois arquivos que nunca existiram**
  (`PerfilEAcessoTest.php` e `PerfilEAcessoTenancyTest.php`); 30 ocorrencias corrigidas para os
  arquivos reais.

## [0.18.3] - 2026-08-22

Release de correcao. Gate de QA em tres wikis, tres dividas tecnicas pagas e uma correcao de
matriz de permissao — a unica que muda comportamento, e so no modo multi-organizacao.

### Corrigido

- **`admin_app` deixa de receber as permissions de `Exception`** no painel `app`, `DeleteAny`
  inclusive. O `ExceptionResource` esta naquele painel so por obrigacao tecnica (o plugin precisa
  estar nos tres ou o pacote estoura em todo request), e `registerNavigation(false)` esconde o menu
  sem fechar o acesso. Agora o seeder tem **duas** listas: `permissoesForaDoApp()` sai do
  `admin_app` e do `panel_user`, `permissoesDeAdministracaoDoApp()` so do `panel_user`.
  Nao era vazamento de stack trace — com a tenancy ligada a tela estourava 500 —, mas a permission
  existir num papel de cliente ja era defeito.
- **DT-01** — o botao *Clear Cache* do `/infra` ganha nome acessivel (`aria-label`), por copia da
  blade do vendor com uma linha de `:label`. O remedio antes prescrito (JS copiando o tooltip)
  faria a acessibilidade depender de JavaScript e nenhum teste de servidor poderia prova-la.
- **DT-02** — contraste do indicador de ambiente sai de 4,2:1 para 5,4:1 no tema claro, por uma
  linha de CSS. Medido: **tres das quatro** cores que o plugin escolhe reprovam no degrau 600
  (Orange 3,39:1), e todas passam no 700.
- **Um CT-B testava uma pagina 404 e passava.** `/infra/queue-monitors/pending` so existe quando
  `queue.default` e `database` (`QueueJob::isSupported()`), e o `phpunit.xml` fixa `sync`. O
  cenario visitava o 404, onde `assertNoJavaScriptErrors()` passa. As "52 telas" do kit sao 51.

### Alterado

- **DT-07** — o inventario de telas dos CT-B virou `telasDoKit()` em `tests/Pest.php`, com
  `InventarioDeTelasTest` reconciliando nos dois sentidos: tela registrada fora do inventario, e
  rota do inventario que o roteador nao resolve. Foi essa guarda que achou o CT-B do 404.
  A derivacao automatica foi implementada e **recusada**: perde as rotas de
  `two-factor-authentication` (nem Page nem Resource) e ignora as exclusoes deliberadas.
- **CT-B09** sai do `->todo()` e passa a medir acessibilidade um painel por cenario.
- **A contagem da matriz do painel `app`** estava parada em 38 em tres lugares; sao **59** (56 de
  Resource, 3 de Page). O texto agora manda recontar em vez de confiar no numero.
- **DT-09 estava quase paga e o ledger nao sabia**: `5511a0a` traduziu as telas de `/infra` em
  2026-08-15. Resta um titulo de plugin sem ponto de extensao.
- **DT-08 saiu de "investigacao" para causa endereçada**: o render hook nunca foi do painel
  `/infra` — escopo vazio no `ViewManager` significa TODO escopo. O vazamento real e no processo de
  teste, porque `fronteiraDeRequest()` esquece o `ViewManager`.

### Adicionado

- **Gate de QA** em `perfil-e-acesso-ao-painel`, `admin-da-organizacao` e `convite-de-usuario`,
  escolhidas por triagem de risco entre as 18 wikis sem relatorio. Cinco wikis ficaram
  **dispensadas** do gate, com o motivo escrito.
- **`00-requisito.md` da wiki de multi-tenancy**, reconstruido e declarado como reconstrucao: 19
  clausulas graduadas por forca de evidencia e **9 lacunas declaradas** em vez de preenchidas.

## [0.18.2] - 2026-08-22

Release de correcao. Revisao das wikis, uma divida tecnica paga e uma recusa revertida. A unica
mudanca de comportamento e a do log em teste — quem ja esta na v0.18.1 nao precisa fazer nada em
producao.

### Corrigido

- **A suite deixa de escrever no `storage/logs` real** (DT-10). Os canais `ai`, `tenancy` e
  `autenticacao` passam a ler o driver de `LOG_KIT_DRIVER`, e o `phpunit.xml` fixa `monolog` nele —
  com `handler` sempre presente, o `NullHandler`. Em producao nada muda: sem a variavel, driver
  `daily`. Medido antes: 4.463 linhas e 1,1 MB num dia em `autenticacao-*.log`, produzidas so pelas
  rodadas de teste.
  - `LOG_CHANNEL=null` **nao** resolvia (troca so o canal default, e as 60 chamadas do kit sao
    `Log::channel()` nomeado) e `LOG_KIT_DRIVER=null` **piorava em silencio** — nao existe
    `createNullDriver` no `LogManager`, o `resolve()` lanca, o `get()` cai no emergency logger e o
    log ia para `storage/logs/laravel.log`. Guarda em `tests/Kit/QualidadeDeCodigoTest.php`.

### Adicionado

- **Project rule do `CardItem`** (`.ai/rules/filament.md`, glob `app/Filament/**`): cartao de hub
  sai sempre de `DescobreCardsDoPainel`. `CardItem` nao verifica autorizacao — escrito a mao, o
  cartao aparece para todo mundo e so devolve 403 no clique.

### Alterado

- **A recusa do TIA foi revertida** em `wikis/qualidade-de-codigo.md`. A medicao que a sustentava
  estava errada — dizia que o ambiente nao tinha driver de cobertura, e media com Xdebug na mesma
  frase. Com PCOV instalado: `pest --tia --fresh` roda completo em **24m59s** (antes: abortado apos
  35 min) e a rodada seguinte custa **6,4 s**. Continua valendo que `--testsuite`/`--group`/
  `--filter` desligam o TIA, e que PCOV e por maquina, nao por commit.
- **21 wikis de `specs/` revisadas.** Caixas que diziam pendencia sobre obra ja entregue foram
  fechadas com a evidencia (11 `git commit`, os CT-B de quatro features, o relatorio de QA da
  regressao) e o `03-progresso.md` do `v1-enriquecimento-kit` deixou de afirmar que o merge nao
  havia sido feito.

## [0.18.1] - 2026-08-22

Release de ferramenta e de medição. **Nada muda no comportamento do kit** — quem já está na
v0.18.0 não precisa fazer nada.

### Adicionado

- **`pestphp/pest-plugin-phpstan`** (dev), incluído no `phpstan.neon`. Ele tipa `expect()`, o
  `$this` das closures de teste e o higher-order testing. Vale mesmo com `tests` fora dos `paths`,
  e custa zero enquanto está fora.

### Alterado

- **`wikis/qualidade-de-codigo.md`** ganha duas seções: por que `tests` **não** está nos `paths` do
  PHPStan, com o custo medido, e o que foi medido e **recusado** — para ninguém refazer a medição
  daqui a três meses.

### Medido

O número que decide a inclusão de `tests` no PHPStan:

| Configuração | Erros |
|---|---|
| `tests` nos paths, **com** o plugin | **117**, em 26 dos 62 arquivos |
| `tests` nos paths, **sem** o plugin | **566** |

O plugin não adiciona ruído — ele **remove 449 falsos positivos**. Os 117 que sobram não são
defeito: são level 7 vendo código de teste pela primeira vez, e três padrões respondem por 33 deles
(`artisan()` devolve `PendingCommand|int`, spy do Mockery em `LoggerInterface`, fake de Mail em
`TransportInterface`). `tests/Kit/ConviteTest.php` sozinho tem 35.

**As regras próprias do plugin acusaram zero** — nenhuma expectation impossível, nenhuma descrição
de teste duplicada, nenhum `covers()` com classe inexistente. O ganho de incluir `tests` é
prevenção, não um lote de defeito esperando; e `types:check` é gate dentro do `composer test`.

### Recusado, com número

- **TIA (`--tia`) não dá agilidade neste projeto.** Três motivos independentes: é inerte em comando
  filtrado (`--testsuite` está em `PARTIAL_SELECTION_FLAGS` do Pest); exige PCOV ou Xdebug, e o
  ambiente não tem nenhum dos dois; e sem filtro arrasta o Playwright já na coleta.
- **Não existe teste lento para consertar.** `tests/Kit`: 398 testes, 665,8s em série, top-10 =
  **6,6%**, máximo de 6,98s numa distribuição chata. E o topo do `tests/Tenancy` é **artefato de
  medição** — o caso de 33,30s custa **5,8s** rodado sozinho, porque o `--profile` atribui a
  compilação de componente Livewire a quem renderizou painel primeiro.
- **Ajustar `--processes` não ganha nada.** 10 processos → 277s; 20 (o default, igual aos núcleos)
  → **227s**. Menos worker é mais lento.
- **Sharding no CI não paga.** Os jobs reais são 3,4 / 2,2 / 0,8 min e rodam em paralelo. `--shard`
  funciona (verificado: `--shard=1/4` do Kit roda 8 de 31 arquivos), mas pede
  `tests/.pest/shards.json` commitado e aviso a cada teste novo até regerar.

### Armadilhas registradas

- **O Pest troca de printer quando `AI_AGENT` está no ambiente**, e a saída vira
  `{"tool":"pest",...}` — engolindo a tabela de `--profile`, `--coverage` e `--type-coverage`.
  Redirecionar para arquivo **não** contorna: o printer é escolhido no processo do Pest. Para ver
  saída humana: `(unset AI_AGENT CLAUDECODE; vendor/bin/pest --profile ...)`.
- **`--profile` não agrega em `--parallel`** — a tabela simplesmente não sai. Perfil exige série.

## [0.18.0] - 2026-08-21

### Alterado

- **O hub em cards deixa de ser padrão em `/admin` e `/app`.** Nova chave `kit.hub`
  (`KIT_HUB`), **desligada por default**: os dois painéis nascem sem a página de grade de cartões,
  e `KIT_HUB=true` no `.env` devolve as duas — sem editar código e sem ressemear o Shield, porque
  o `FilamentCardsPlugin` continua registrado nos três painéis e a permissão continua na matriz.

  O motivo é cardinalidade: grade de cartões paga o próprio espaço quando há **muitos** caminhos.
  O `/admin` tem oito destinos, e o `/app` de um projeto de verdade nasce vazio — ali a grade é a
  barra lateral com um clique a mais.

  **O `/infra` não mudou e não depende da chave.** São dezesseis destinos em quatro grupos, metade
  com rótulo de plugin de terceiro sem tradução ("audits", "Exception", "Manage commands",
  "Run history"): é o único painel do kit onde a grade ganha da árvore no default. A assimetria é
  deliberada e tem caso de teste que fica vermelho se alguém "corrigir" a inconsistência.

  Para quem já instalou o kit e vai rodar `kit:update`: se você usava os hubs de `/admin` ou
  `/app`, acrescente `KIT_HUB=true` ao `.env`. O pacote `harvirsidhu/filament-cards` continua
  instalado nos dois casos.

### Adicionado

- **Descrição em cada cartão do hub de infraestrutura.** Os dezesseis destinos passam a exibir uma
  frase dizendo para que o link serve, e a frase entra no texto pesquisável do cartão — a busca da
  página passa a encontrar por assunto ("fila", "restaurar", "e-mail") e não só pelo rótulo.

  Vem de um mapa por FQCN em `HubDeInfraestrutura::descricoesDosDestinos()`, porque treze dos
  dezesseis destinos são vendor e não há onde declarar a frase na classe. Plugin novo no painel
  entra sem frase e a suíte acusa, apontando a classe que falta.

### Corrigido

- **A captura `art/admin-papeis-import-export.png` estava publicada com a barra lateral do painel
  errado** — navegação do `/app` sob o cabeçalho do `/admin` — desde a v0.17.0. Cenário de navegador
  precisa visitar o painel em que o processo foi deixado: o servidor do `pest-plugin-browser` roda
  in-process, e atravessar painel dentro do mesmo processo faz a tela renderizar com a barra lateral
  do painel anterior. O `beforeEach` da suíte de arte arranjava o `/app` para todos os cenários, e o
  de papéis visita o `/admin`.

  Nenhum teste ficou vermelho por isso: os cenários afirmam sobre o conteúdo da tela, e ninguém
  afirma sobre a barra lateral. Foi encontrado ao **abrir a imagem**.

- **`kit:arte` publicava todo PNG que encontrasse** em `tests/Browser/Screenshots` — inclusive os
  screenshots que o Pest grava sozinho quando um cenário de navegador falha. Agora publica de uma
  lista declarada (`KitArte::IMAGENS`), e o que não está declarado é **reportado**, nunca publicado
  e nunca silenciado.

- **`composer art` rodava duas invocações do `artisan test`**, e o plugin limpa o diretório de
  screenshots no início de cada run — a segunda apagava o que a primeira escrevera, e quatro imagens
  ficavam silenciosamente sem atualizar. Passou a ser uma invocação com os dois caminhos.

### Armadilhas registradas

- **Em Page do Filament, `canAccess()` sozinho basta** para tirar da URL, do menu e da busca ⌘K —
  `Page::registerNavigationItems()` já retorna cedo. Em **Resource** são dois métodos. Copiar o par
  do Resource para uma Page acrescenta código que não muda nada
  (`.ai/rules/filament.md`).
- **Cenário de navegador visita o painel em que o processo foi deixado.** Atravessar painel dentro
  do mesmo processo renderiza a barra lateral do painel anterior, sem nenhum teste vermelho
  (`.ai/rules/testes-browser.md`).
- **Captura nova exige a linha em `KitArte::IMAGENS`**, e o `composer art` roda os arquivos de
  captura numa única invocação do `artisan test` (`.ai/rules/testes-browser.md`).
- **Utilitária Tailwind que blade de vendor emite precisa existir no CSS do kit.** Ausência produz
  HTML correto sem estilo nenhum, com todo teste verde (`.ai/rules/css-filament.md`).

## [0.17.0] - 2026-08-18

Seis pacotes novos, todos Filament v5 e gratuitos, escolhidos numa varredura dos **547** plugins do
diretório oficial. O método, os 112 finalistas ranqueados e os 435 descartados com motivo estão em
[`wikis/pacotes-ranking.md`](wikis/pacotes-ranking.md) e
[`wikis/pacotes-candidatos.md`](wikis/pacotes-candidatos.md).

### Adicionado

- **Camada de mídia** — `filament/spatie-laravel-media-library-plugin`. Até aqui upload era
  `FileUpload` gravando caminho em coluna, sem coleções, conversões nem uma tabela que soubesse o
  que é anexo de quê. `App\Models\Projeto`, a model de demonstração, ganhou a coleção `anexos` e a
  conversão `miniatura`, e o `ProjetoResource` mostra o padrão no form e na tabela.

  Escolhido em vez do `awcodes/filament-curator` pelo critério de **multi-organização**: a tabela
  `media` do Spatie é polimórfica, então o anexo pertence ao registro e herda o escopo de
  `BelongsToTenant` — sem coluna de tenant, sem configuração para esquecer ligada. O Curator tem
  suporte a tenancy, mas com biblioteca compartilhada e escopo **desligado por padrão**.

- **Lixeira** — `promethys/revive`, no `/infra`, grupo "Sistema". Restaura registros apagados com
  `SoftDeletes`. `App\Models\Projeto` ganhou a trait junto: nenhuma model do kit usava soft delete
  antes desta versão, e a tela nasceria vazia.

  A lista de models é **explícita** (`models()`), e não `modelsNamespace()`: a varredura automática
  alcançaria `User`, `Role` e `Tenant`, cuja restauração tem consequência de autorização.

- **Exceções agrupadas** — `bezhansalleh/filament-exceptions`, no `/infra`, grupo "Observabilidade".
  O painel via saúde, desempenho, arquivo de log e filas; faltava "qual exception está estourando, e
  quantas vezes".

- **Trilha de e-mail** — `tapp/filament-maillog`, no `/infra`, grupo "Trilhas". O `ConviteDeAcesso` é
  a única porta de entrada de usuário e não deixava rastro: "o convite não chegou" era impossível de
  responder.

- **Seletor de idioma** — `bezhansalleh/filament-language-switch`, nos três painéis e nas telas de
  login. **Dirigido por dado, sem flag**: `config('kit.idiomas')` com um item só — que é como o kit
  nasce — esconde o botão.

  Está declarado no config e na wiki que a tradução cobre a camada do Filament e dos pacotes, **não**
  os rótulos do kit, que são strings pt-BR no código (há dez `__()` em todo o app).
  Internacionalizar o kit continua sendo trabalho declarado e não feito.

- **Lint de Filament no CI** — `laraveldaily/filacheck` (dev), como `composer filament:check`, dentro
  do `composer test`. Achou **7 problemas preexistentes** no primeiro run, todos corrigidos abaixo.

- **`config/kit.php` → `retencao`** (`KIT_RETENCAO_EXCECOES_DIAS`, `KIT_RETENCAO_EMAILS_DIAS`, ambos
  14 dias) e **`idiomas`**. A retenção é aplicada por dois mecanismos diferentes em
  `routes/console.php`, porque os pacotes diferem: `model:prune` para as exceções, que declaram
  `prunable()`, e exclusão direta para o `MailLog`, que **não** implementa `Prunable` — passá-lo ao
  `model:prune` seria um agendamento verde que nunca apaga nada.

### Corrigido

- **Seis métodos de teste depreciados** (`assertHasNoActionErrors`, `assertHasActionErrors`) e um
  **`ImageColumn::size()`** no `TenantsTable`, todos apontados pelo FilaCheck no primeiro run.

### Segurança

- **`ExceptionResource` entrou na lista de subtração do `panel_user`** no `PapeisSeeder`. O plugin de
  exceções precisou ser registrado nos **três** painéis (ver abaixo), o que fez o resource existir na
  matriz do `/app`. Sem a subtração, todo usuário comum herdaria `ViewAny:Exception` — e a rota
  existe naquele painel — ganhando leitura de stack trace da instalação inteira, com parâmetro de
  request dentro. Verificado: 12 permissions de `Exception` no banco, 0 no `panel_user`. Fixado por
  `tests/Kit/PacotesTierSTest.php`.

- **Retenção obrigatória nas duas trilhas novas.** O stack trace pode conter parâmetro de request; o
  corpo do e-mail é gravado, e o convite carrega o link de aceite. As duas telas vivem só no
  `/infra`, onde entrar já exige `master_global` ou `infra`.

- **`MEDIA_DISK` documentado como superfície aberta.** Com o default `public` o caminho é
  `/storage/{id}/{arquivo}`, ID sequencial, alcançável sem sessão — a multi-organização do Filament
  não chega ao sistema de arquivos. Serve para avatar e logo; para documento, disco privado e rota
  autorizada. O campo de anexos do `ProjetoResource` já usa `->visibility('private')`.

### Armadilhas registradas

- **Plugin que resolve o painel corrente precisa estar nos TRÊS painéis.** Registrar o
  `filament-exceptions` só no `/infra` derrubou **todo comando artisan** — `migrate` e `inspire`
  inclusive — com `LogicException: Plugin [filament-exceptions] is not registered for panel [app]`. O
  `ExceptionResource` chama `FilamentExceptionsPlugin::get()` nos métodos estáticos de navegação, e o
  `filament-shield` percorre `Filament::getPanels()` no boot sem fixar o painel corrente: a resolução
  cai no default. Mesma armadilha já documentada do `Lockscreen`. Virou rule em
  `.ai/rules/providers-filament.md`.

- **`modelPruneInterval()` recebe DATA DE CORTE, não quantidade de dias.** `Exception::prunable()`
  faz `whereDate('created_at', '<=', $intervalo)`; passar `14` compararia com o ano 14 e nunca
  podaria nada. E precisa de `Carbon` mutável, porque o kit usa `Date::use(CarbonImmutable::class)`.

- **`nonQueued()` antes de `width()`/`height()`** em `registerMediaConversions()`: os dois últimos
  devolvem o `ImageDriver`, não a `Conversion`. Virou rule em `.ai/rules/models.md`.

### Notas de adoção

Dois nomes de pacote do ranking estavam errados — vinham do slug da URL do diretório, que não expõe o
nome Composer. Corrigidos na wiki: `filament-exception-viewer` → **`bezhansalleh/filament-exceptions`**
(e a série para Filament 5 é a **4.x**, não a 5.x), e `filament-mail-log` →
**`tapp/filament-maillog`**.

Suíte: **408 casos**, 1135 asserções (era 388). PHPStan 0 erros. FilaCheck 17 regras.

## [0.16.9] - 2026-08-17

### Corrigido

- **`create-project` da v0.16.8 nascia sem `phpunit.xml`, `pint.json` e `phpstan.neon`.** O
  `export-ignore` dos três tirava do pacote distribuído justamente os arquivos que os scripts
  `test`, `lint` e `types:check` do `composer.json` — que seguem no dist — precisam para rodar.
  Os três voltaram ao pacote, e o `.gitattributes` agora explica por que não podem sair: eles
  estão em `KitUpdate::CAMINHOS_DO_KIT`, e `tests/Kit/KitUpdateTest.php` falha se o
  `export-ignore` voltar.

- **Todo login na suíte de testes custava ~7s numa chamada de rede a `repo.packagist.org`.** O
  `filament-composer-release-notifier` enfileira o `SyncComposerReleaseSnapshotsJob` no evento de
  `Login`; com `QUEUE_CONNECTION=sync`, o job rodava dentro do teste. Media 9,776s no listener,
  contra 0,014s do listener do log de autenticação. Pesava em todo caso que autentica de verdade
  — formulário de login, aceite de convite, destravar sessão — e no CI a latência estourava a
  espera do `assertPathIs('/app')`, derrubando o login pela tela de `tests/Browser/PerfisTest.php`.
  O `phpunit.xml` agora desliga o notifier na suíte (`FILAMENT_COMPOSER_RELEASE_NOTIFIER_ENABLED`),
  o que afeta só o listener: o resource de releases do painel `/infra` segue registrado.
  `tests/Kit/BloqueioDeSessaoTest.php`, caso do destravamento: 14,0s → 3,4s.

### Alterado

- **Timeout do `pest-plugin-browser`: 20s → 45s** (`tests/Pest.php`), como folga para a primeira
  tela de cada arquivo, que ainda paga a compilação das views.

## [0.16.8] - 2026-08-17

### Segurança

- **Ações do CI presas por SHA** em vez de tag móvel (`actions/checkout`, `shivammathur/setup-php`,
  `actions/setup-node`), e o repositório ganhou `SECURITY.md` e `dependabot.yml`. Apontamentos do
  Plumb.

## [0.16.7] - 2026-08-17

### Corrigido

- **Título dos READMEs duplicado na página do plugin no site do Filament.** Removeu-se o
  cabeçalho `# starter-kit-easy` dos arquivos `README.md` e `README.en.md`, mantendo a imagem
  com a classe `filament-hidden` já ajustada na versão anterior.

## [0.16.6] - 2026-08-17

### Corrigido

- **Imagens duplicadas na página do plugin no site do Filament.** As logos do `README.md` e
  `README.en.md` passaram a usar a classe `filament-hidden`, ocultando a imagem no site da Filament
  enquanto a mantêm no repositório.

## [0.16.5] - 2026-08-16

### Alterado

- **`composer test:kit` roda em paralelo por padrão: 12m26s → 3m36s** nesta suíte (20 núcleos),
  com os mesmos 355 casos e 1047 asserções. Cada worker tem o próprio banco porque o `phpunit.xml`
  usa SQLite `:memory:`, que é por processo.

  A troca de `--group=kit` por `--testsuite=Kit,Tenancy` resolve um segundo problema junto: o
  `pest-plugin-browser` sobe o Playwright já na **coleta**, ao parsear qualquer arquivo com
  `visit()`, **antes** de qualquer filtro de grupo ser consultado — e num projeto recém-instalado,
  sem os browsers baixados, `--group=kit` morre em `PlaywrightNotInstalledException` sem rodar um
  único teste. As duas seleções cobrem o mesmo conjunto: o `tests/Pest.php` marca as duas pastas
  com o grupo `kit`.

  E um terceiro, silencioso: `composer test:kit --parallel` é **engolido** pelo Composer — só
  `composer test:kit -- --parallel` encaminha o argumento. Com o paralelo virando padrão, ninguém
  precisa saber disso.

- **Novo `composer test:kit:serial`.** Paralelo embaralha a ordem e usa processos separados; se uma
  falha aparecer só nele, é sinal de teste que depende de ordem ou de estado compartilhado — e a
  diferença entre os dois comandos **é** o diagnóstico.

  `composer test` e `composer test:browser` seguem como estavam: browser em paralelo multiplica
  processos de navegador e vira timeout.

## [0.16.4] - 2026-08-16

### Alterado

- **⚠️ O papel `admin_organizacao` passou a se chamar `admin_app`.** É quebra para quem já
  instalou e escreveu código com esse nome — `hasRole('admin_organizacao')`, `@role`, `@can`,
  policies, seeders próprios. Uma migration renomeia o papel **no banco**
  (`rename_admin_organizacao_role`), porque papel é dado e mudar só o seeder não alcançaria quem
  já está rodando; o **seu** código ela não alcança. Procure pelo nome antigo antes de atualizar.

  A migration é condicional: num projeto single-tenant, que nunca semeou esse papel, ela não cria
  nada — papel a mais é papel que alguém atribui sem querer.

- **Os papéis do kit têm nome de gente na interface.** `master_global` → **Administrador Geral**,
  `admin_app` → **Administrador App**, `panel_user` → **Painel App**. Antes o Title Case derivava
  da chave e produzia "Master Global" e "Panel User" — inglês, e sem dizer o que o papel faz.
  As chaves não mudam (fora a do item acima), e um papel criado por você em `/admin` continua
  derivando da própria chave.

### Corrigido

- **A suíte do kit quebrava em projeto personalizado.** Um projeto que escolheu a cor Violet e
  chamou a organização de "Universidade" via **cinco casos falharem** — a URL do cadastro vira
  `/admin/universidades`, o `/admin` nasce violeta em vez do âmbar padrão, e o plural sugerido
  deixa de ser "Organizações". Nenhum era regressão.

  E é isso que tornava o estrago grave: o `composer test:kit` existe para dizer se a **fundação**
  continua íntegra depois de um `kit:update`, e um vermelho por diferença de configuração não
  deixa ninguém distinguir uma coisa da outra.

  O `phpunit.xml` passa a fixar `KIT_COR_PRIMARIA`, `KIT_DEMO` e os rótulos de tenancy, pelo mesmo
  motivo por que já fixava banco, fila e e-mail: a suíte roda contra a configuração do kit, não
  contra a do projeto.

## [0.16.3] - 2026-08-16

### Corrigido

- **Quem já tinha conta era convidado para outra organização e não achava onde aceitar.** A tela de
  aceite manda essa pessoa entrar e promete, com todas as letras, que "o convite aparece no menu do
  seu usuário" — e o menu contava zero. Beco sem saída: autentica e não tem o que fazer.

  A causa não estava no convite. O `Panel::boot()` do Filament registra um escopo global **no
  model** de todo resource escopado por tenant (`Panel.php:85-90`). Como existe um
  `ConviteResource` no painel `/app`, **toda** query de `Convite` dentro de um request de
  `/app/{organização}` nascia filtrada pela organização corrente — inclusive a que conta as ofertas
  recebidas. A oferta pertence à organização de **destino**, que não é a corrente.

  `Convite::pendentesPara()` passa a ignorar esse escopo — só ele, e não `withoutGlobalScopes()`,
  para não derrubar de carona um escopo futuro que seja legítimo ali. A pergunta é, por definição,
  entre organizações: "o que endereçaram a esta pessoa, em qualquer lugar".

- **Dois botões idênticos de criar na mesma tela.** Convites, Usuários e Projetos do painel de
  negócio registravam `CreateAction` no cabeçalho da **página** e no `headerActions()` da
  **tabela**. Ficou só o da página, que é a convenção das outras sete listagens do kit.

- **Rótulos em minúscula.** O kit desliga o title-case automático do Filament (ele produzia
  "Agentes De IA", que é regra do inglês), então o rótulo vale exatamente como escrito — e os
  painéis divergiram: `/admin` dizia "Convite" e `/app` dizia "convite". Padronizados `Convite`,
  `Usuário`, `Projeto` e `Execução de IA`.

### Alterado

- **Papéis são exibidos em Title Case.** `master_global` virava rótulo de tela em sete lugares, e
  identificador não é rótulo. A **chave não muda** — ela é o que vai em `assignRole()`, nos seeders
  e nas policies; muda só a exibição, por `App\Support\Papeis::rotulo()`, inclusive nos selects em
  que o papel é escolhido.

- **O campo que dá acesso ao painel diz isso.** `roles.painel` é a coluna que `User::canAccessPanel()`
  lê, e ela aparecia como "app" — parecia categoria. Agora o campo se chama **"Acesso ao painel"** e
  o valor lê "Acesso ao painel /app". Papel sem painel lê **"Não abre painel"**, em vez de um traço
  que o leitor tinha de interpretar: nulo não é coringa, e a tela passa a dizer isso.

- **O botão "fixar colunas" saiu de todas as tabelas.** O gerenciador de colunas fica no mesmo
  cabeçalho e já resolve organizar a tabela; dois botões para o mesmo objetivo é escolha a mais na
  cara de quem só quer ver a listagem. Para trazer de volta, é uma linha em
  `ConfiguraFilamentGlobal::aplicaMacrosDeColuna()`.

## [0.16.2] - 2026-08-16

### Adicionado

- **A recuperação de senha ganhou o layout do Auth Designer, espelhado.** As telas de
  `/{painel}/password-reset` não passavam pelo plugin — caíam no layout padrão do Filament, sem
  arte e sem alternador de tema. Agora usam a mesma arte do login com o eixo invertido:
  **arte à direita, formulário à esquerda**. É o sinal de que se saiu do login, sem trocar cor,
  texto ou marca. Vale para os três painéis.

### Corrigido

- **O resource de exemplo aparecia em todo projeto.** `Projetos` é a demonstração do isolamento
  entre organizações, criada por `kit:tenancy --demo` — mas não tinha guarda nenhuma e ocupava o
  menu do `/app` de qualquer instalação, com ou sem multi-organização. O painel de negócio nasce
  **vazio** de propósito: ninguém sabe o que o seu projeto vai construir.

  Agora ele exige as duas condições: multi-organização ligada **e** demo. A segunda não existia —
  o `--demo` só rodava o seeder —, então nasceu a chave `KIT_DEMO` / `config('kit.demo')`, que o
  próprio comando escreve. Sem ela a demo nasceria invisível: dados no banco e menu vazio.

  A guarda é `canAccess()` **e** `shouldRegisterNavigation()`. Só o segundo tiraria o item do menu
  deixando a rota de pé e a busca ⌘K oferecendo "Criar projeto" — affordance para uma tela que não
  deveria existir ali.

  > Num projeto que já rodou `kit:tenancy --demo`, acrescente `KIT_DEMO=true` ao `.env` para
  > continuar vendo a tela de Projetos.

- **O plural sugerido para o rótulo da organização usava a regra do inglês.** Quem apertasse Enter
  nas duas perguntas da instalação via **"Organizaçãos"** oferecido. Quando o rótulo não é
  alterado, a sugestão passa a vir de `config('kit.tenancy.label_plural')` — só um rótulo novo cai
  no palpite `+s`, que acerta as palavras que alguém de fato escolhe aqui (Empresa, Escola, Loja).

## [0.16.1] - 2026-08-16

### Corrigido

- **As perguntas da instalação nunca apareciam.** A 0.16.0 saiu com a feature inteira se pulando
  sozinha, em todo `create-project`, sem erro nenhum.

  O gate era "o `.env` já existia antes desta execução?". Só que o `composer.json` traz, desde o
  skeleton do Laravel, um `post-root-package-install` que copia `.env.example` para `.env` — e ele
  roda **antes** do `post-create-project-cmd` que chama o `kit:install`. A resposta era sempre
  sim.

  O sinal passou a ser a `APP_KEY` vazia, que é o que significa "este projeto nunca foi
  instalado". A decisão virou `CustomizadorDaInstalacao::devePerguntar()`, pura, com tabela-verdade
  testada — e um teste estrutural proíbe voltar a decidir por existência de arquivo, porque esse é
  o tipo de defeito que nenhuma suíte pega: a sequência que o expõe é a do Composer, não a do Pest.

- **Instalação sem terminal agora se explica.** O Composer só repassa o terminal ao script quando
  ele mesmo consegue (`ProcessExecutor::executeTty`), e em várias combinações de sistema e console
  isso não acontece — o `artisan` roda com a entrada fechada e todo prompt é pulado. Continuar
  pulando é o certo; ficar calado não era. Quando o projeto é novo e não há terminal, a instalação
  termina avisando o que aconteceu e qual comando refaz a instalação **com** as perguntas
  (`php artisan kit:install --force`).

## [0.16.0] - 2026-08-16

### Adicionado

- **O `create-project` agora pergunta.** Cinco perguntas antes de tocar no banco,
  no mesmo lugar em que o `laravel new` faz as dele: nome do projeto, banco de
  dados, credenciais do administrador, cor primária dos painéis e modo
  multi-organização. As respostas são aplicadas pela própria instalação — `.env`,
  `config/permission.php` e `config/filament-shield.php` — e a config já carregada
  é alinhada, para que o `migrate` e os seeders **da mesma execução** usem o que
  foi escolhido.

  Todas têm resposta padrão: **Enter em tudo instala exatamente como antes**. A
  primeira pergunta é "personalizar agora?", que pula todas de uma vez, e
  `--no-custom` faz o mesmo pela linha de comando. Sem terminal (CI, Docker,
  `--no-interaction`) nada é perguntado — o Composer só repassa o TTY ao script
  quando ele mesmo é interativo (`EventDispatcher::executeTty`).

  Ao final: resumo do que mudou, os sete itens da lista "Personalize seu projeto"
  que continuam sendo editados à mão (cada um com o arquivo), a oferta de rodar
  `composer test:kit` e o convite para dar uma estrela ao kit — desligável com
  `--no-support`.

- **Escolha de banco na instalação: SQLite, PostgreSQL ou MySQL.** O padrão segue
  SQLite. PostgreSQL é o **recomendado** e a razão é funcional: é o único com
  `pgvector`, de que dependem as funções de IA local com busca semântica. Se o
  serviço escolhido não estiver de pé, o instalador avisa, **pula migrations e
  seeders** e imprime o comando para refazer — em vez de derramar duas falhas de
  PDO em cascata.

- **`KIT_COR_PRIMARIA`.** A cor primária dos três painéis passou a ser
  configuração, e não edição de `PanelProvider`. Nome de uma cor da paleta do
  Filament; vazio mantém o padrão. Nome inválido volta ao padrão em vez de
  derrubar toda página com `Undefined constant`. A cor de uma **organização**
  continua vencendo esta dentro de `/app/{slug}` — o `Panel::boot()` registra as
  cores do painel antes dos `bootCallbacks`, e quem registra por último vence.

- **Multi-organização ligada na instalação não recria o banco.** Os três passos
  não destrutivos do `kit:tenancy` — a flag no `.env`, `permission.teams` +
  `tenant_model` do Shield, e o alinhamento da config em memória — saíram para
  `App\Support\AtivadorDeTenancy` e rodam **antes do primeiro migrate**. O
  `kit:tenancy` segue existindo, e segue destrutivo, para quem decide depois.

## [0.14.3] - 2026-08-15

### Corrigido

- **A marca de versão do kit ficou defasada três releases seguidas.**
  `config('kit.version')` apontava para `0.13.1` enquanto as tags já eram
  `v0.14.0`, `v0.14.1` e `v0.14.2`.

  Não é cosmético: é dessa chave que o `kit:update` parte para comparar
  (`KitUpdate.php:383`). Com ela atrasada, quem instalasse uma 0.14.x e rodasse
  a atualização veria como "novidade" o que já tinha — e teria de revisar
  arquivo a arquivo um diff que não era dele.

  O comando **grava a chave sozinho** ao final de cada atualização, que é o
  fluxo normal; o que faltava era o passo equivalente no **release**, onde a
  tag é criada à mão. Fica registrado aqui como parte da checklist: subir a
  versão em `config/kit.php` **antes** de taggear.

## [0.14.2] - 2026-08-15

### Corrigido

- **O CI não rodava um único teste desde a 0.13.0.** O job `qualidade` morria em
  `PlaywrightNotInstalledException` — e Pint, PHPStan e as 230 asserções que
  vinham depois nunca eram exercidos. `--exclude-group=browser` filtra na
  **execução**, mas o `pest-plugin-browser` sobe o Playwright já na **coleta**,
  ao parsear qualquer arquivo com `visit()`
  (`UsesBrowserTestCaseMethodFilter.php:57-60`): o grupo nunca chegava a ser
  consultado.

  Trocado por seleção de suíte — `--testsuite=Unit,Feature,Kit,Tenancy` —, que
  tira `tests/Browser` e `tests/BrowserTenancy` da coleta. As telas seguem
  cobertas no job `telas`, que tem Node e os browsers.

- **Title case no menu, que é regra do inglês.** `getNavigationLabel()` cai em
  `getTitleCasePluralModelLabel()` e aplica `Str::ucwords()` a **toda** palavra,
  preposição inclusive: "Agentes De IA", "Logs De Autenticação", "Pacotes Do
  Composer". Desligado por `Resource::titleCaseModelLabel(false)` no
  `ConfiguraFilamentGlobal` — chave estática, então vale também para os
  Resources de plugin de terceiro, que é justamente onde o kit não tem como
  declarar label.

- **Cinco telas do `/infra` ainda saíam em inglês.** O kit promete UI 100% em
  pt-BR, inclusive nos plugins que só trazem inglês. Traduzidos
  `filament-logs-explorer` e `filament-dependency-graph` (o pacote tinha
  `lang/`, o kit não tinha publicado), completado o
  `filament-jobs-monitor` (faltavam 84 das 116 chaves, caindo no fallback `en`)
  e o `resized-column`, e publicadas as views do `filament-command-center`, que
  tem o texto no Blade.

## [0.14.1] - 2026-08-15

### Adicionado

- **Caveman e Ponytail versionados para os agentes sem plugin.** A `feature-wiki`
  depende **por nome** de `/ponytail-review` (auditoria obrigatória do plano),
  `ponytail` (execução) e `caveman` (comunicação). No Claude Code os dois chegam
  como plugin; nos outros agentes não existe sistema de plugin, e a skill
  invocava um comando que não estava lá. Agora as três vão versionadas em
  `.agents/`, `.ai/` e `.junie/` — cópias MIT, com o `LICENSE` original junto.

  `.claude/skills/` fica **de fora de propósito**: o plugin já está habilitado em
  `.claude/settings.json`, e a cópia criaria duas `ponytail` ativas ao mesmo
  tempo. Fora do Claude Code a invocação perde o namespace: `/ponytail-review`,
  não `/ponytail:ponytail-review`.

  `boost:update` não apaga essas pastas — ele só remove skill que já rastreou e
  saiu do `boost.json`, e nenhuma das três está listada lá.

- **`README`: o ciclo de uma feature com agente.** As sete etapas, cada uma com o
  arquivo que produz e por que ele existe — do requisito imutável (00) ao
  relatório de QA (06) e à regra durável em `.ai/rules`. Mais o que isso muda na
  prática: contexto vira arquivo em vez de histórico de chat, e correção vira
  regra em vez de lembrança.

## [0.14.0] - 2026-08-14

O kit passa a levar junto **como a feature é feita**, não só o que ela usa: as
skills que orquestram wiki, QA e regra durável, e o roteiro que lista o que já
existe antes de alguém reimplementar.

### Adicionado

- **Skill `feature-wiki`.** Toda feature nova começa por `wikis/specs/{branch}`
  com cinco arquivos obrigatórios: requisito bruto **imutável** (00), plano de
  ação, decisões arquiteturais, tracking e casos de teste — mais o roteiro de
  navegador (05) quando a feature tem tela. O requisito nunca é reescrito para
  caber na implementação: é ele que julga a entrega no fim.

- **Skill `feature-quality-gate`.** A estação de QA depois de os testes
  passarem. Confronta requisito × plano × app rodando e monta a **matriz de
  rastreabilidade** — a cláusula que nunca virou passo, teste nem código aparece
  como linha vazia, que é a omissão que nenhuma suíte verde denuncia. Só lê e
  reporta em `06-relatorio-qa.md`; não corrige nada.

- **Skill `requirement-to-rule`.** Decisão que vale além da feature atual vira
  Project Rule em `.ai/rules` pela tool `record-rule`, com quatro gates
  (durável, escopável por path, não-inferível, não-redundante) e aprovação
  explícita. Mantém o `index.md` atualizado — **regra fora do índice não é
  descoberta pelos agentes**.

- **Roteiro de features no `README`.** As 56 features (F-01…F-56) com onde fica,
  quem alcança, como conferir e o que já é testado sozinho — 🟢 suíte, 🔵
  navegador real, ⚪ sem teste. A última coluna é honesta: cinco features
  dependem de worker, cron, SMTP, Docker ou API key, e a tabela final diz o que
  acontece sem cada um.

### Alterado

- **Skill `pest-testing` atualizada para Pest 5**: Tia (`--parallel --tia`)
  rerroda só o que a mudança afetou e reproduz o resto do cache, sharding
  balanceado por tempo de execução, e as oito novas expectations de validação
  (`toBeEmail()`, `toBeUlid()`, `toBeIpAddress()`, …).

As skills são instaladas nos quatro diretórios que os agentes leem —
`.agents/`, `.ai/`, `.claude/` e `.junie/` — para valerem qualquer que seja a
ferramenta de quem clonar o kit.

## [0.13.1] - 2026-08-14

Identidade visual por organização, e dois defeitos de cor que ela revelou.

### Adicionado

- **Cor e logo por organização.** No `/admin` → Organizações, cada uma escolhe a
  cor primária e envia a logo. Ao abrir `/app/{organizacao}`, o painel inteiro
  veste a cor dela — botões, links, ícones, badges — e a **tela de bloqueio**
  mostra a logo do cliente no lugar da imagem base. Sem nada preenchido, a
  feature é **inerte**: o painel usa o default do Filament.

  Uma coluna de cor, não onze: `Color::generatePalette()` deriva as 11
  tonalidades de um hex, e o Filament escolhe a legível por contraste em runtime.

- **Página `view` no cadastro de organizações**, com o `ViewAction` na listagem.
  Era a única lacuna real do CRUD — create e edit já eram telas cheias.

### Corrigido

- **A cor da organização não chegava a toda a tela.** O CSS do
  `croustibat/filament-jobs-monitor` é registrado como asset **global**, e dentro
  dele as utilitárias vêm com a paleta âmbar **literal**
  (`.text-primary-600 { color: rgb(217 119 6) }`). Quem usa essas classes ficava
  âmbar mesmo com `--primary-600` dizendo outra coisa — na prática, o alternador
  de painel, que aparece em toda tela, ficava âmbar dentro de um painel verde.

  A correção vive em `resources/css/filament/kit.css`, registrado por
  `FilamentAsset::register()`. **Escrever a regra em `resources/css/app.css` não
  funcionaria**: o painel Filament não carrega o Vite da aplicação.

- **`AcoesDeCriacao` resolvia a URL sem fixar o painel.** Como o registry do
  Spotlight é singleton de container, num processo que atende dois painéis as
  ações do primeiro sobrevivem e tentam uma rota que só existe lá — `Route
  [...] not defined`, 500 numa tela sem relação com o resource citado. **Sob
  worker persistente (Octane) isso era defeito de produção.**

### Segurança

- **O upload da logo aceitava SVG.** `FileUpload::image()` gera a regra
  `mimetypes:image/*`, e `image/svg+xml` casa com ela — ao contrário da regra
  `image` do Laravel, que recusa. Com disk público, um SVG com `<script>` é
  servido pelo próprio origin da aplicação: abrir a URL executa o script com
  acesso ao cookie de sessão. Exige quem já administra organizações, então é
  escalada de insider, não porta anônima. Trocado por `acceptedFileTypes()`
  explícito.
- **A cor primária entrava sem validação.** `ColorPicker::hex()` não valida — só
  troca o formato do picker. Strings arbitrárias eram persistidas, e
  `generatePalette()` degradava para uma paleta **acromática**: o painel do
  cliente ficava cinza, sem erro em lugar nenhum.

## [0.13.0] - 2026-08-14

O kit ganha a camada de teste que não tinha: **navegador real, com JavaScript
executando, sobre as 52 telas dos três painéis**. Até aqui a cobertura de tela era
HTTP (`$this->get()`), que prova que o servidor devolveu 200 e nada além disso — e
um painel Filament é Livewire + Alpine, então o HTML pode vir íntegro com status
200 e a tela estar inutilizável porque um `x-on:click` estourou, porque um asset
do Vite não subiu ou porque um componente de plugin registrou erro no console.
Nenhuma dessas três falhas move o status HTTP.

A rodada também virou uma auditoria: **11 dívidas técnicas identificadas, uma
paga**, e duas Project Rules novas para o que foi aprendido não se perder. Wiki
completa em `wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/`.

### Adicionado

- **Suíte de testes de browser** (`tests/Browser`, grupo `browser`), com 11
  cenários cobrindo **100% das 52 telas alcançáveis por URL fixa** — das 74 rotas
  GET dos painéis, 13 exigem `{record}`, 3 são endpoint JSON de passkey e 6 exigem
  estado ou token. Rode com `composer test:browser`, que embute o `npm run build`
  de que a suíte depende.

  O painel `/app` ganha aqui a **primeira cobertura de tela que já teve**: o
  smoke HTTP cobria 15 rotas de `/infra` e 3 de `/admin`, e o painel de negócio —
  o único que o consumidor do kit usa todo dia — tinha só o `GET /app` genérico.

  Fica **fora** do `composer test:kit` de propósito: aquele é o comando de
  resposta rápida depois de um `kit:update`, e browser em série custa ordens de
  magnitude mais que HTTP.

- **Validação de perfis pela tela.** Cada papel entra no painel dele e vê uma
  página de 403 **legível** no painel negado — o teste HTTP afirmava
  `assertForbidden()`, que passa igual se o usuário barrado receber tela branca.

- **Validação de dark mode.** `->inDarkMode()` nos três dashboards, e o alternador
  de tema exercitado por clique. Com uma ressalva honesta, que está na wiki:
  `assertSee()` **passa** com texto branco em fundo branco, então o teste prova
  que a tela abre sob `prefers-color-scheme: dark`, não que está legível. A
  legibilidade foi conferida por inspeção visual de 9 telas nos dois temas —
  nenhum texto ilegível, ícone sumido ou logo com fundo cravado.

- **Job de CI `telas`**, com Node, browsers do Playwright e build do Vite.
  Separado do job de qualidade, que passa a rodar `--exclude-group=browser`:
  registrar a testsuite nova fazia `php artisan test` incluí-la, e o CI quebraria
  em toda tela com `ViteException`.

- **Duas Project Rules** em `.ai/rules/`: `testes.md` (glob `tests/**`) e
  `testes-browser.md` (glob `tests/Browser/**`). A segunda registra os quatro
  fatos sobre o `pest-plugin-browser` que a doc oficial não diz e que custaram uma
  sonda inteira — entre eles que **o plugin sobe o próprio servidor in-process**,
  então `:memory:`, `RefreshDatabase` e `$this->actingAs()` continuam valendo
  dentro do navegador, e nenhum Herd ou `artisan serve` é necessário.

- **`tests/Kit/HelpersDeTesteTest.php`** — guarda automática contra helper de
  teste usado de outro arquivo. Usa `token_get_all()` e não regex, porque menção
  em docblock é comum nesta suíte e guarda com falso positivo ensina o time a
  ignorá-la.

### Corrigido

- **Helper de teste declarado dentro de arquivo de teste** (era a dívida
  bloqueante). Em PHP função é global no processo: quando o Pest carrega **todos**
  os arquivos, um helper declarado em `AlgumTest.php` vaza para o vizinho e tudo
  passa — o acoplamento fica invisível. Ele só aparece em execução **parcial**, que
  é o que fazem `--parallel`, `--tia` e `pest tests/Kit/AlgumTest.php`.

  Eram **7 erros** `Call to undefined function` em `--parallel`, e o `--tia` do
  Pest 5 — a feature que motivou o upgrade — era inutilizável. `usuarioCom()`,
  `noPainelDa()` e `pivotDePapeis()` foram para `tests/Pest.php`, e **dois clones
  desapareceram**: existiam só para escapar da colisão de redeclaração, cada um
  idêntico ao original, trocando um erro que estoura por duas funções iguais que
  ninguém percebe.

  Medido: `pest --parallel --group=kit` de 206/213 com 7 erros para **214/214** em
  196 s, contra 818 s em série — **4,2× mais rápido**.

- **`tests/Unit/ExampleTest.php` convertido para Pest.** Era o scaffolding
  class-based do Laravel, e o `--tia` **aborta a execução inteira** ao encontrar
  uma classe PHPUnit. Um arquivo esquecido desligava o Test Impact Analysis para o
  projeto todo.

### Alterado

- **Pest 4.7 → 5.1** e **PHPUnit 12.5 → 13.3** (requisito duro do Pest 5), mais
  `pestphp/pest-plugin-browser` 5.0 e `playwright`. São dependências de
  desenvolvimento: nada muda em runtime para quem usa o kit.
- `pest()->tia()->defaultBranch('main')->locally()` em `tests/Pest.php` — o default
  do TIA é `master`, e o `locally()` liga o TIA no desenvolvimento e o desliga em
  CI, como a doc do Pest recomenda.

### Conhecido

Dez dívidas seguem abertas, com custo estimado e caminho de correção em
`06-divida-tecnica.md`. As que mais importam:

- **Sem PCOV**, o `--tia` é impraticável: com Xdebug, em série não termina
  (abortado após 35 min), e `--parallel` derruba 4 dos 11 cenários de browser
  porque multiplica processos de navegador. O contorno são dois comandos.
- **Botão *Clear Cache* sem texto acessível** (a11y *critical*) no `/infra`, e
  **contraste 4.25:1** no indicador de ambiente (a11y *serious*, só no tema claro).
  Ambos em `vendor/`.
- **Render hook de plugin vaza entre painéis** no mesmo processo PHP: `/admin`
  isolado tem 0 botões de *Clear Cache* e 9 depois de visitar `/infra`. Impacto em
  produção hoje é nulo (sem Octane), mas os testes de browser validam um DOM
  contaminado.
- **Nenhum `data-testid`** nas telas, então os seletores são `id` de framework,
  texto visível e `aria-label`.

## [0.12.0] - 2026-08-14

O convite deixa de ser só uma porta para gente nova. Três features nasceram de
ler o código-fonte de dois pacotes que resolvem o mesmo problema —
[`jeffersongoncalves/teamkitv4`](https://github.com/jeffersongoncalves/teamkitv4)
(via `jeffersongoncalves/filament-teams`) e
[`offload-project/laravel-invite-only`](https://github.com/offload-project/laravel-invite-only).
**Nenhum dos dois é instalado**; o que se copiou foram ideias, e três decisões
saíram dos defeitos deles. Cada feature tem a wiki dela em `wikis/specs/main/`.

### Adicionado

- **Convite para quem já tem conta.** Convidar um endereço que já é usuário
  deixa de ser erro e passa a ser **oferta de acesso**: a pessoa entra com a
  senha que já tem, confirma, e é vinculada com o papel do convite. Ou **recusa**,
  e a recusa fica registrada — "ela disse não" é diferente de "o convite
  desapareceu".

  Era uma parede no caso mais comum de SaaS multi-tenant: a consultora que
  atende dois clientes, a funcionária em duas unidades. Antes, só o
  `master_global` resolvia, por `/admin` → Organizações → *Vincular usuário*; o
  `admin_organizacao`, a persona criada justamente para dar autonomia à
  organização, **não conseguia**.

  Duas vias, uma tabela, decididas **no aceite** e não na criação (entre criar e
  clicar passam dias, e a pessoa pode ter criado conta por outro caminho). Na via
  de conta nova o token é **suficiente**; na de oferta é **necessário mas não
  suficiente** — exige também que o e-mail do autenticado seja o do convite.

  Junto vem a **caixa de entrada de convites**, no menu do usuário do `/app` com
  a contagem das ofertas pendentes. Ela não substitui o link: vive sob
  `/app/{tenant}`, então não alcança quem tem zero organizações nem quem só tem
  papel de `/admin` ou `/infra`. Ganha o lugar dela por outro motivo — é o único
  lugar onde a **recusa** existe.

- **Convite em massa.** Colar vários endereços, um papel e uma organização para o
  lote, e o resultado por endereço: quantos foram e quais falharam, com o motivo
  de cada um. O lote **não aborta** por causa de um endereço torto.

- **Lembretes de convite pendente.** `kit:convites-lembrar`, agendado às 08:00,
  reenvia em D+3 e D+5. Idempotente e com catch-up: cron parado por dias não
  produz rajada, e cada convite recebe no máximo um lembrete por execução.

  O lembrete leva um **segundo token, também hasheado** (`token_lembrete`), e o
  link original **não é tocado**. Foi a saída do beco: o token em claro existiu
  só no momento do envio, e as alternativas eram rotacionar (um lembrete não
  entregue revogaria o convite) ou guardar uma cópia reversível no banco (que
  contradiria uma promessa já publicada aqui). Ver ADR-01 da wiki.

- **`email_verified_at` no aceite de conta nova**: o token prova posse do
  endereço, então pedir verificação depois é pedir a mesma prova duas vezes.
  Inócuo hoje; no dia em que alguém ligar `->emailVerification()`, sem isso todo
  usuário nascido de convite é barrado na porta.

### Corrigido

- **A subtração do `panel_user` não cobria Page nem Widget.**
  `Paineis::permissoes('app')` sai de `getEntitiesPermissions()`, que mistura
  Resources, Pages, Widgets e permissions custom — mas a subtração só varria
  Resources. Medido: 38 permissões no painel `app`, 36 alcançáveis, 2 não.

  Inofensivas hoje, mas o mecanismo estava aberto: a próxima Page de
  **administração** registrada no `/app` cairia na matriz do usuário comum — todo
  mundo virando administrador da própria organização, sem migration, sem 403 e sem
  log. E não era hipótese: quando o buraco foi encontrado a medição era 37/36/1;
  duas semanas depois já era 38/36/2, porque a feature de convite registrou
  `ConvitesRecebidos` como Page naquele painel. A "próxima Page" chegou, e
  continuou inofensiva por sorte.

  A matriz de nenhum papel mudou — verificado por dump antes/depois. O que mudou
  é o alcance da subtração.

- **`kit:update` não entregava `.ai/rules` nem as wikis.** Projeto novo recebia
  os dois; projeto que atualizava, nenhum dos dois. Para um kit cujo diferencial
  anunciado é "a documentação que o agente de IA lê antes de codar", isso
  significava entregar o **código** de uma feature e não a armadilha que ela
  documenta. `wikis/specs/` continua fora de propósito: é o histórico de
  planejamento do kit, e não do projeto de quem instalou.

- **O form de convite do `/app` recusava quem já tem conta.** Regra própria, além
  da do `/admin`, com comentário que já não valia. Efeito: a feature nascia
  desligada exatamente para a persona que a motivou. Achado por um caso de teste,
  não por leitura.

- **`save()` não gravava a limpeza do token de lembrete.** `save()` escreve só o
  que está sujo: numa instância carregada antes de um lembrete, o `forceFill` que
  zera `token_lembrete` igualava o valor ao `original` e **não entrava no UPDATE**
  — o link de lembrete sobrevivia a um reenvio que promete matá-lo, sem erro.
  Corrigido com `refresh()` na primeira linha de `enviar()`.

### Notas

- **Três decisões vindas dos defeitos dos pacotes analisados**, todas em
  [convenções](wikis/convencoes.md#armadilhas-já-resolvidas):

  - **Asserção de identidade vive no model, não na query da tela.** O
    `filament-teams` tem `TeamInvitation::accept(Authenticatable $user)` que faz
    `attach()` + `delete()` sem comparar e-mail nenhum — a única barreira é o
    `where('email', …)` da página. Enquanto a página for o único chamador funciona;
    o primeiro job, comando ou rota de API passa por cima sem nada acusar.
  - **Consumo por `update` condicional.** O `invite-only` faz check-then-act sem
    transação nem lock, então clique duplo dispara dois eventos de aceite e duplica
    o grant de papel. Onde não existe `unique` que salve, o
    `UPDATE … WHERE aceito_em IS NULL` é o que garante uso único.
  - **Não reprovar o formulário inteiro por causa de um item do lote.** O
    `inviteMany()` do `invite-only` só captura `InvalidArgumentException`, e o
    `unique` do schema dele derruba o lote inteiro num endereço com convite
    recusado.

- **O agrupamento do `orWhere` em `Convite::valido()`** é a armadilha mais cara
  desta versão, e o procedimento de "ver o teste falhar antes de implementar"
  mostrou que ela é pior do que parecia: `AND` liga mais forte que `OR`, então sem
  o closure o **token original** também perde os três filtros de estado — um
  convite já aceito volta a ser aceitável pelo link antigo, sem erro e sem log.

- **`config/` continua fora do `kit:update`.** As chaves novas
  (`kit.convites.limite_do_lote`, `kit.convites.lembretes_dias`) não chegam a
  projeto instalado; os defaults do código cobrem a ausência.

## [0.11.0] - 2026-08-13

Três features que se completam: o painel passa a ser dado do papel, o cadastro
de quem vem de fora passa por convite, e a organização ganha um administrador
próprio dentro do `/app`. Cada uma tem a wiki dela em `wikis/specs/main/`.

### Alterado — quebra deliberada

- **`/app` deixou de ser aberto a qualquer usuário autenticado.** Acesso a painel
  agora vem do papel, pela coluna nova `roles.painel`, e `User::canAccessPanel()`
  lê essa coluna no lugar da lista de nomes que estava escrita dentro do model.
  Usuário sem papel autentica e leva 403 nos três painéis — dar acesso virou um
  ato explícito.

  **Nulo não é coringa**: papel sem painel não abre painel algum. Quem entra em
  todos é o `master_global`, pelo `Gate::before`, como sempre foi.

  Painel **sem** tenancy (`/admin`, `/infra`) exige o papel atribuído no contexto
  global; painel **com** tenancy (`/app`) aceita o papel em qualquer organização,
  e quem barra a organização errada continua sendo `canAccessTenant()`, com 404 e
  não 403. É a propriedade que impede alguém promovido a `admin` dentro de uma
  organização de administrar a instalação inteira.

  **Ao atualizar**, rode os dois seeders e revise seus usuários:

  ```bash
  php artisan migrate
  php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
  php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
  ```

- **`User::temPapelGlobal()` foi removido.** Quem o chamava troca por
  `temPapelDoPainel()` ou `isMasterGlobal()`. O método trocava o
  `PermissionRegistrar` do container e descarregava a relação duas vezes para
  responder uma pergunta de leitura; a relação nova `papeisEmQualquerContexto()`
  responde com um `exists()`.

- **`panel_user` deixou de receber a matriz inteira do painel `app`.** Com as
  telas de administração da organização registradas nesse painel, dar tudo ao
  perfil básico promoveria todo usuário comum a administrador — sem migration e
  sem erro nenhum. A subtração é por FQCN de Resource.

### Adicionado

- **Convite por e-mail.** `/admin` → **Convites**: e-mail, papel e (com tenancy) a
  organização. O link leva a `/app/register?token=…`, que é a página de registro
  nativa do Filament com uma guarda no `mount()` — sem token válido ela recusa,
  então o registro nunca vira cadastro aberto. Quem clica escolhe **só nome e
  senha**; o resto vem do convite, imposto pelo servidor.

  O token é a credencial: `Str::random(64)` gravado como `hash('sha256', …)`,
  válido **uma vez** (`aceito_em`) e por um **prazo**
  (`kit.convites.validade_em_dias`, 7 dias). Em claro ele existe no e-mail e em
  lugar nenhum mais — nunca é logado nem entra na trilha de auditoria. Token
  inexistente, expirado e já aceito dão a **mesma** resposta: distinguir
  confirmaria que o convite existiu.

  No aceite o papel é atribuído no contexto certo — global se o papel for de
  `/admin` ou `/infra`, a organização do convite se for de `/app`.

  O e-mail sai por Notification enfileirável: **sem worker no ar o convite não
  chega** (`QUEUE_CONNECTION=database`).

- **Administrador da organização** (`admin_organizacao`, só com a tenancy ligada).
  Ele administra a **própria** organização dentro do `/app` — cria usuários,
  convida por e-mail, vê só quem pertence à organização corrente — e **não entra
  no `/admin`**. Seis barreiras contra escalada de privilégio, cada uma com teste
  próprio: papéis oferecidos e gravados restritos ao painel `app`, atribuição
  sempre no contexto da organização, sem criar ou editar papéis, sem alcançar
  usuário de fora (nem trocando o id na URL), sem promover ninguém a
  `admin`/`infra`/`master_global`, e convite nascendo com a organização dele
  carimbada à força.

- **A tela de papéis agrupa as permissões por painel.** O `RoleResource` do Shield
  foi publicado no projeto (`app/Filament/Admin/Resources/Roles/`) porque o pacote
  não oferece hook para isso, e ganhou o campo **Painel**. As edições em relação
  ao vendor são mínimas de propósito — duas Pages e um método —, para o diff de um
  upgrade continuar legível.

- **`App\Support\Paineis`**: o mapa painel × Resource × permission, colhido na
  mesma fonte que o `shield:generate` usa. É ele que faz o `PapeisSeeder` recortar
  a matriz por painel em vez de adivinhar por substring — o casamento antigo
  (`str_contains($p, 'User')`) colocaria um `UserPreferenceResource` futuro no
  papel `admin` sem ninguém decidir.

- **`App\Models\Role`**, para a coluna `painel` ter tipo. `config/permission.php`
  passa a apontar `models.role` para ele.

- Regras novas em `.ai/rules/filament.md`, que é o que os cinco agentes de IA leem
  antes de escrever código: Resource ou RelationManager novo exige gerar as
  permissões; papel novo precisa declarar o painel; Resource de model sem relação
  de posse com o tenant precisa de `$isScopedToTenant = false` e de um
  `getEloquentQuery()` que falhe fechado.

### Corrigido

- **As permissões de `/app` e `/infra` nunca existiram no banco.** O
  `ShieldPermissionsSeeder` rodava `shield:generate --all --panel=admin` e mais
  nada, e o comando só enxerga o painel corrente. Agora ele varre os três: 79
  permissions viraram 186, e sete policies novas apareceram. Telas que estavam sem
  policy — logo, abertas — passam a exigir permissão.

- **A suíte de testes do kit nunca teve uma permission no banco.** O `$this->seed()`
  do Laravel passa por `PendingCommand`, que liga um mock de `OutputStyle` no
  container; comando chamado de dentro do seeder resolve esse mock e é engolido.
  O `shield:generate` terminava com exit 0, imprimia "79 permissions generated" e
  gravava **zero** linhas. Nada acusava porque os testes autenticavam como
  `master_global`, que vence pelo `Gate::before` justamente sem precisar de
  permission. `Tests\TestCase::seed()` passa a usar `Artisan::call` — medido: 0
  contra 186.

- **Rodar a suíte deixava a árvore de trabalho suja.** O `shield:generate` reescreve
  as policies com o estilo dele, e o seeder roda em todo `beforeEach`: o
  `composer test` seguinte falhava no `lint:check` e o `kit:update` recusava a
  árvore. O seeder passa a usar `--ignore-existing-policies`, o que também o torna
  idempotente de verdade — quem editou uma policy à mão não a perde ao gerar as
  permissões de um Resource novo.

- `kit:update` passa a cobrir `app/Support`, `app/Notifications`,
  `app/Models/Role.php` e `app/Models/Convite.php`. O teste que varre a árvore
  pegou os dois primeiros sozinho.

### Notas

- **`config/` continua fora do `kit:update`, de propósito**, então
  `permission.models.role` apontando para `App\Models\Role` **não chega** a quem já
  instalou — e não precisa: sem a troca, `painel` volta a ser atributo dinâmico e
  tudo funciona igual. É por isso que o `UserResource` tipa o papel pela classe do
  spatie e não pela do kit; com o type hint concreto, um projeto atualizado teria
  `TypeError` na tela de usuários.

- Seis armadilhas novas na tabela de
  [convenções](wikis/convencoes.md#armadilhas-já-resolvidas), todas encontradas
  executando e nenhuma visível na leitura do vendor. As três que mais custaram:

  - **A facade `FilamentShield` cacheia a instância resolvida**, e o
    `forgetInstance()` do container não a alcança — é preciso
    `Facade::clearResolvedInstance()` junto. Sem isso os três painéis devolvem o
    mapa do primeiro, e os três papéis nascem com a mesma matriz. Parecia sucesso.
  - **`->when()` numa relação Eloquent entrega o `Builder`**, não a relação:
    `wherePivot()` dentro do closure não é aplicado, sem erro nenhum.
  - **O Filament injeta parâmetro de closure por NOME, não por tipo** — o parâmetro
    tem de se chamar `$record`, e o erro só aparece ao renderizar o campo.

## [0.10.0] - 2026-08-13

### Adicionado

- **A tela de bloqueio de sessão agora usa o layout do login.** O
  `marjose123/filament-lockscreen` entrega a tela como `SimplePage` do Filament,
  então ela ignorava o `caresome/filament-auth-designer`: quem bloqueava a sessão
  caía numa caixa cinza no meio da tela, sem a arte, sem a marca e sem o
  alternador de tema. Agora é a mesma barreira do login, nos três painéis.

  Quem faz isso é `App\Filament\Pages\Auth\TelaBloqueio`, colocada no lugar da
  classe do pacote por um bind em `AppServiceProvider` — a rota do pacote resolve
  `LockerScreen::class` pelo container.

- **Tradução pt-BR do lockscreen** em `lang/vendor/filament-lockscreen/pt_BR/` —
  o pacote só traz inglês, e "Lock Screen"/"Sign In" apareciam na tela.

### Corrigido

- **`GET /{painel}/screen/lock` com a sessão destravada dava 500.** O `mount()` do
  pacote chama `redirect()` **sem `return`**; num processo onde o Livewire já
  instalou o Redirector dele, esse objeto chega onde o Laravel espera um código
  HTTP e o request morre em `ErrorException: Object of class
  Livewire\...\Redirector could not be converted to int`. É a mesma falha já
  registrada para o Command Center, e aqui doía mais: a URL fica em favorito e
  histórico do usuário. A `TelaBloqueio` sai por `HttpResponseException`.

- **"Bloquear sessão" estava no fim do menu do usuário**, depois do alternador de
  tema e colado em "Sair". O item que o pacote registra nasce sem `sort`, e a view
  do menu agrupa por `getSort() < 0`. Agora vem com `sort(-1)`, logo abaixo de
  "Meu perfil" — registrado em `bootUsing()`, porque plugin boota antes dos
  callbacks de boot e quem registra por último vence.

### Notas

- Armadilha nova na tabela de [convenções](wikis/convencoes.md#armadilhas-já-resolvidas)
  e em `.ai/rules/auth.md`: **a `TelaBloqueio` redeclara `protected static string
  $layout`**, e isso não é redundância com a trait `HasAuthDesignerLayout`. A trait
  faz `static::$layout = ...`; sem storage próprio na subclasse a atribuição cai no
  estático herdado de `Filament\Pages\Page` e o layout de login passa a vestir
  **toda** página Filament do processo (a de 2FA do Breezy morre em
  `getAuthDesignerConfig does not exist`). `tests/Kit/BloqueioDeSessaoTest.php`
  cobre em par: a tela nova com `fi-auth-layout`, e o `/admin` sem ele depois dela.

## [0.9.9] - 2026-08-13

### Corrigido

- **O `kit:update` precisa de DUAS rodadas quando ele próprio muda — e não dizia
  isso.** A lista de caminhos que filtra o diff é uma constante da própria classe
  `KitUpdate`, e o PHP já carregou a versão antiga em memória. Então a rodada que
  traz um `KitUpdate.php` novo ainda filtra pela lista VELHA: arquivo coberto só
  pela lista nova não entra. Verificado na prática — a correção da tela de
  usuários da 0.9.7 só apareceu na segunda rodada.

  O aviso de "atualizou a si próprio" agora manda rodar de novo com o mesmo
  `--from`, e diz como saber que terminou ("Nada a atualizar").

### Notas

- Recuperando um projeto que ficou para trás nos buracos de 0.9.1–0.9.7:

  ```bash
  php artisan optimize:clear
  php artisan kit:update --from=v0.8.0            # traz a lista de caminhos nova
  git add -A && git commit -m "kit:update, rodada 1"
  php artisan kit:update --from=v0.8.0 --no-branch # traz o que só a lista nova cobre
  composer test:kit
  ```

## [0.9.8] - 2026-08-13

### Corrigido

- **Metade do Filament do kit nunca chegava a quem instalou.** A correção da tela
  de usuários publicada na 0.9.7 não alcançou projeto nenhum: o
  `app/Filament/Admin/Resources/Users` não estava em
  `KitUpdate::CAMINHOS_DO_KIT`. Junto com ele estavam de fora
  `app/Filament/Admin/Resources/AgentesIa`, `app/Filament/Infra/Resources/AiRuns`,
  `app/Livewire/AssistenteChatWidget.php`, `app/Models/AgenteIa.php` e as
  policies de `User`, `Role` e `AgenteIa`.

  A causa é a granularidade: a lista tinha uma linha por subpasta do Filament
  (`Admin/Widgets`, `Admin/Resources/Tenants`, `Infra/Pages`…), e o que não
  ganhou linha própria simplesmente não existia para o `kit:update`. Agora entra
  `app/Filament` inteiro, mais `app/Livewire`, `app/Policies` e
  `database/factories`.

- **O teste que devia ter pegado isso era uma lista à mão.** O
  `tests/Kit/KitUpdateTest.php` cobrava 22 arquivos escolhidos a dedo — e
  `UserResource.php` não era um deles. Ele passa a **varrer a árvore**: todo
  arquivo sob `app/`, `database/factories`, `database/migrations` e
  `database/seeders` precisa estar coberto, com uma allowlist explícita para o
  que não é do kit. A varredura roda só no repositório do kit (detectado pelo
  `.github`, que é `export-ignore`), porque em projeto instalado o model e o
  resource do usuário moram nesses mesmos diretórios.

### Notas

- **Quem já atualizou até a 0.9.7 precisa comparar a partir da 0.8.0 uma vez.**
  O `kit:update` compara duas tags: indo de 0.9.7 para 0.9.8 o diff traz apenas o
  que mudou entre elas, e os arquivos que estavam fora da lista **não voltam**.
  Para recuperar tudo o que os buracos de 0.9.1–0.9.7 engoliram:

  ```bash
  php artisan kit:update --from=v0.8.0
  ```

  É também o que traz o `app/Models/Tenant.php` com `HasName` (correção da 0.9.3),
  sem o qual `/app/{tenant}` responde 500 —
  `FilamentManager::getTenantName(): Return value must be of type string, null returned`.

- `config/` segue fora da lista de propósito: é o que cada projeto calibra, e
  sobrescrever apagaria ajuste seu. A exceção é `config/kit.php`, a marca de
  nascença.

## [0.9.7] - 2026-08-13

### Corrigido

- **Salvar papéis do usuário em `/admin/users/{id}/edit` dava 500 com a tenancy
  ligada.** `NOT NULL constraint failed: model_has_roles.team_id`, na gravação —
  abrir a tela funcionava.

  O `Select::make('roles')->relationship('roles', 'name')` salva com
  `$relationship->sync()`, que escreve na pivot apenas as colunas da chave. Com
  multi-tenancy a `model_has_roles.team_id` é NOT NULL e ninguém a preenchia: o
  `wherePivot` que o spatie põe em `roles()` filtra **leitura**, não alimenta
  escrita. Quem carimba o `team_id` do contexto corrente é o
  `assignRole()`/`syncRoles()` — a API que o kit passou a usar, via
  `->saveRelationshipsUsing()`.

  Vale também para single-tenant, onde o sintoma era silencioso: o `sync()` cru
  não invalida o cache de papéis do spatie, então uma permissão recém-tirada
  continuava valendo até o cache expirar.

- Dois testes novos, em par, porque abrir a tela não cobre gravar (o
  `GET /admin/users` seguia verde com o salvamento quebrado): um em `tests/Kit`
  para o modo single-tenant e um em `tests/Tenancy` conferindo o `team_id` da
  pivot.

### Notas

- A armadilha ficou registrada em `.ai/rules/filament.md`: campo que grava
  `roles` ou `permissions` nunca usa o sync da relação.

## [0.9.6] - 2026-08-13

### Corrigido

- **`composer test:kit` estourava `model_has_roles.team_id` em projeto com a
  tenancy ligada.** Vinte e quatro testes de `tests/Kit` morriam com
  `NOT NULL constraint failed: model_has_roles.team_id` na primeira atribuição de
  papel. A suíte `tests/Tenancy` passava inteira, o que tornava o sintoma
  confuso: o modo multi-tenant funcionava, o single-tenant não.

  O modo de tenancy vive em três chaves que precisam concordar, e elas não vêm do
  mesmo lugar. `kit.tenancy.enabled` é env (`KIT_TENANCY`); `permission.teams` e
  `filament-shield.tenant_model` são arquivos que o `kit:tenancy` reescreve em
  **disco**. O `Tests\TestCase` alinhava só a primeira — então num projeto com a
  tenancy ligada as suítes single-tenant migravam o schema COM as colunas de team
  (`permission.teams` ainda `true`) e atribuíam papel SEM contexto de team
  (`kit.tenancy.enabled` já `false`, e é essa flag que o
  `KitServiceProvider::configureTenancy()` usa para fixar o contexto global).

  Agora `usaTenancy()` decide as três, em `Tests\TestCase::createApplication()` —
  antes das migrations, com o `PermissionRegistrar` descartado para renascer
  sabendo do modo. O `Tests\TenancyTestCase` ficou só com a declaração do modo: o
  mecanismo deixou de estar duplicado nos dois arquivos.

- `tests/Kit/PaineisTest.php`: o teste `roda em modo single-tenant` passa a cobrar
  as três chaves, não só a primeira. É o que faz a dessincronia falhar dizendo o
  nome, em vez de virar um 404 ou um `NOT NULL` sem pista.

## [0.9.5] - 2026-08-13

### Corrigido

Os dois erros abaixo só apareciam em **projeto instalado** — no repositório do
kit a suíte passava inteira. É a pior categoria de bug para um starter kit: o
primeiro `composer test:kit` de quem instala falha, e nada no kit reproduz.

- **`.github` na lista de caminhos do `kit:update`.** O `.gitattributes` marca
  `/.github export-ignore`, então a pasta não vai no pacote distribuído — o CI é
  do kit, não do projeto que nasce dele. Mas ela estava em
  `KitUpdate::CAMINHOS_DO_KIT`, e daí duas consequências: o teste "só lista
  caminhos que existem de fato" falhava em toda instalação, e o `kit:update`
  (que lê o repositório git, onde a pasta existe) ofereceria o CI do kit ao
  projeto — justamente o que o `export-ignore` decidiu evitar.

  `tests/Kit/KitUpdateTest.php` passa a ler o `.gitattributes` e a cobrar que
  nenhum caminho `export-ignore` volte para a lista.

- **`GET /app` respondia 404 na suíte do kit quando a config estava cacheada.**
  Não era rota nem painel: `tests/Kit` pressupõe o modo single-tenant, e o
  `Tests\TestCase` garantia isso escrevendo `KIT_TENANCY=false` no ambiente antes
  do bootstrap. Com um `bootstrap/cache/config.php` no lugar, o `env()` nem é
  consultado — a tenancy voltava a ligar, o `->tenant()` reescrevia o painel para
  `/app/{tenant}` e o dashboard sumia. A tela de login seguia de pé, o que
  escondia a causa.

  O `Tests\TestCase` agora aponta `APP_CONFIG_CACHE` e `APP_ROUTES_CACHE` para
  arquivos inexistentes: nos testes o Laravel boota da fonte, sem apagar o cache
  do projeto. Ambos os caches congelam decisões de um ambiente e nunca deveriam
  valer numa suíte que alterna modos do kit.

  `tests/Kit/PaineisTest.php` ganhou o teste `roda em modo single-tenant`, para a
  premissa quebrar com nome em vez de virar um 404 sem pista.

## [0.9.4] - 2026-08-13

### Corrigido

- **O `kit:update` não entregava a multi-tenancy.** Ele compara duas versões do
  kit restrito a uma lista fechada de caminhos, e essa lista não foi atualizada
  quando a feature nasceu: das versões 0.9.1 a 0.9.3, um projeto já instalado só
  recebia `config/kit.php` — a marca de versão, sem nenhum dos arquivos. A
  feature existia no repositório e era invisível na prática.

  Entraram na lista: `app/Console/Commands` (que só cobria dois comandos),
  `app/Http/Middleware`, `app/Policies/TenantPolicy.php`, os resources de
  tenants e da demo, `app/Models/User.php`, `Tenant.php` e `Projeto.php`,
  `database/migrations`, `database/seeders`, `database/factories/TenantFactory.php`
  e as suítes `tests/Tenancy`, `tests/TestCase.php` e `tests/TenancyTestCase.php`.

- `tests/Kit/KitUpdateTest.php` passa a cobrar a lista: 22 arquivos da fundação
  precisam estar cobertos, e todo caminho listado precisa existir. É o que faz a
  lista envelhecer com barulho em vez de em silêncio.

### Notas

- Diretório na lista é seguro mesmo com arquivos seus dentro: a comparação é
  kit-versão-A × kit-versão-B, então um arquivo que só existe no seu projeto
  nunca entra no diff. `app/Models` continua arquivo a arquivo, onde a colisão
  de nome com um model seu é plausível.
- Quem atualizou para 0.9.1–0.9.3 e recebeu só o `config/kit.php` precisa
  comparar a partir da última versão anterior à tenancy — que é a **0.8.0**,
  não uma 0.9.0 (essa nunca existiu como tag; a série foi de 0.8.0 para 0.9.1):

  ```bash
  php artisan kit:update --from=v0.8.0
  ```

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
