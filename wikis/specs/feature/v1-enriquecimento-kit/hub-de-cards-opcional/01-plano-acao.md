# Plano de Ação — Hub de cards fora do padrão da instalação

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/main/hub-de-navegacao-em-cards/`
- **Motivo**: a ancestral entregou o hub nos **três** painéis como padrão. O mantenedor revisou a
  decisão: o padrão do starter-kit passa a ser **sem hub**, com exceção declarada para `/infra`.
  Além disso, os cards que sobrevivem em `/infra` ganham descrição (RQ-07, acréscimo do mesmo dia).
- **Toca infra compartilhada?**: **sim** — `config/kit.php` (chave nova), `phpunit.xml` (env novo),
  `app/Filament/Concerns/DescobreCardsDoPainel.php` (assinatura pública do trait, consumido pelas
  três Pages) e `tests/BrowserTenancy/CapturaDeArteTest.php` (suíte de arte compartilhada).

> Tipo `evolução` **e** infra compartilhada tocada: a regressão contra os CT/CT-B da wiki ancestral
> é obrigatória por dois motivos independentes. Ver `## Regressão obrigatória` mais abaixo.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | o pacote continua instalado | 11 | **nenhum passo de código**: `composer.json` não é tocado, e a ausência é o atendimento. O passo 11 acrescenta o CT-11, que a protege de remoção futura |
| RQ-02 | uso documentado para quando for necessário | 9, 10 | receita existente atualizada, não documento novo. Os READMEs ficaram fora — ver o corte no passo 10 |
| RQ-03 | hub deixa de ser padrão da instalação | 1, 2, 3, 4, 5 | flag `kit.hub`, default `false` |
| RQ-04 | tela inicial de painel não nasce com hub | 4, 5, 6 | **com a exceção do `/infra` declarada pelo usuário** — passo 6 registra a exceção no código |
| RQ-05 | imagem do hub entra como opção de uso | 8, 9 | a imagem não existia; o passo 8 a produz e o 9 a publica na receita |
| RQ-06 | documentação registra o encaixe: links e fluxos | 9 | quinto caso de uso na receita |
| RQ-07 | cada card do `/infra` tem descrição | 6, 7 | passo 7 abre o trait para descrição; passo 6 declara o mapa. **CT-08 pende de decisão sua** — ver `03-progresso.md` → Blockers |

## Objetivo

Tirar o hub em cards do caminho de quem instala o kit, sem tirar o hub do kit. Hoje as três Pages
nascem registradas e visíveis nos três painéis; depois desta entrega, `/admin` e `/app` nascem sem
hub, `/infra` nasce com hub — porque é o painel com mais destinos — e um único
`KIT_HUB=true` no `.env` devolve os três, sem editar uma linha de código.

Na mesma passada, os cards que permanecem em `/infra` deixam de ser só rótulo: cada um ganha uma
frase dizendo para que aquele destino serve. Metade dos rótulos daquele painel vem de plugin de
terceiro e não está traduzida ("audits", "Exception", "Manage commands", "Run history") — a
descrição é o que torna a grade legível sem mexer em sete plugins.

## Contexto

A wiki ancestral registra, em ADR-01, que o hub **soma** à barra lateral em vez de substituí-la.
A consequência que não estava no radar é que, num starter-kit, "somar" custa: cada painel nasce com
um item extra no topo do menu, uma rota extra, uma permission extra na matriz do Shield e uma
página que o dono do projeto não pediu. Para o `/infra` a conta fecha — 16 destinos em quatro
grupos, mais as páginas próprias de sete plugins. Para `/admin` (8 destinos) e `/app` (4 destinos,
e o `/app` de um projeto real nasce vazio) não fecha.

O kit já tem o mecanismo exato para isto, e ele não é um `if` novo: `ProjetoResource` se esconde
por `config('kit.demo')` dentro de `canAccess()`
(`app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88`), e o `phpunit.xml` fixa
`KIT_DEMO=false` para que a suíte prove o default em vez de o presumir (`phpunit.xml:64`).

## Análise dos Arquivos Existentes

### `app/Filament/Infra/Pages/HubDeInfraestrutura.php`

**Permanece ligado**, sem flag. Ganha o mapa de descrições e um parágrafo no docblock dizendo por
que ele é a exceção — sem esse parágrafo, o próximo agente "corrige" a assimetria acrescentando a
flag aqui também, e desfaz a decisão do usuário sem nada acusar.

### `app/Filament/Admin/Pages/HubDeAdministracao.php` e `app/Filament/App/Pages/HubDoNegocio.php`

Ganham `canAccess()`. Nada mais muda: título, colunas, busca e `getPageClasses()` continuam como
estão, porque o objetivo é que ligar a flag devolva exatamente a tela que existe hoje.

O docblock de `HubDoNegocio` tem hoje uma seção inteira ("Esta página NÃO entra na lista de
subtração do `panel_user`") que **continua válida e não deve ser mexida** — a flag esconde a
página; ela não muda a matriz de permissões. Ver ADR-04.

### `app/Filament/Concerns/DescobreCardsDoPainel.php`

`cardsDoPainel(array $excluir = [])` ganha um segundo parâmetro opcional de descrições, e
`cardDe()` passa a receber a descrição resolvida. As três Pages continuam funcionando sem passar
o parâmetro novo — é acréscimo, não quebra.

O `ponytail:` que já está no cabeçalho do trait (substituir tudo por `discoverPanelCards()` no dia
em que o pacote tiver um) **continua verdadeiro e não muda**: o pacote ainda não tem.

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`

**Não são tocados.** O `FilamentCardsPlugin` é inerte — `register()` e `boot()` com corpo vazio em
`vendor/harvirsidhu/filament-cards/src/FilamentCardsPlugin.php:13-22` — então deixá-lo registrado
nos três painéis custa zero em runtime e é o que faz `KIT_HUB=true` bastar. Só o comentário de uma
linha acima de cada registro ganha a menção à flag (passo 4 e 5).

### `app/Providers/KitServiceProvider.php`

**Não é tocado.** O `Css::make('kit-cards', …)` de `configureCorrecoesDeCss()` continua
incondicional, porque o `/infra` continua com hub — o CSS é necessário no default. Gate-á-lo por
`config('kit.hub')` seria um `if` que nunca fica falso no kit instalado.

### `resources/css/filament/cards.css`

**Não é tocado.** Conferi contra a blade do pacote: o bloco de descrição
(`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:373-381`) emite
`text-sm`, `text-gray-500` e `dark:text-gray-400`, e as três já estão cobertas —
`cards.css:114`, `:120` e `:141`. A única classe do bloco que o arquivo não define é `text-start`,
e ela é inócua: `text-align: start` já é o default do navegador em LTR.

> Esta verificação é o ponto de maior risco silencioso da entrega, pelo motivo que ADR-02 da wiki
> ancestral registra: o pacote não traz CSS, o kit mantém um subconjunto à mão, e utilitária que
> falta no arquivo produz **HTML correto sem estilo nenhum**, com todo teste verde.

## Autorização

Nada muda na matriz. O Shield continua gerando `View:HubDeAdministracao`,
`View:HubDoNegocio` e `View:HubDeInfraestrutura`, porque a descoberta dele usa
`$panel->getPages()` cru, sem consultar `canAccess()` —
`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`. Consequências:

- `PapeisSeeder::permissoesDeAdministracaoDoApp()` **não muda** (ADR-05 da ancestral segue de pé).
- O caso `it('mantém o hub do negócio com o usuário comum e a administração fora dele')` em
  `tests/Tenancy/HubDoNegocioTest.php:79-85`, que assere `View:HubDoNegocio` nas permissões do
  `panel_user`, **continua verde sem alteração** — ele fala do banco, não da tela.
- Com a flag desligada, a rota do hub de `/admin` continua registrada e responde **403**:
  `abort_unless(static::canAccess(), 403)` em
  `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:8-15`. O kit tem tela branda
  de 403 (`anselmokossa/filament-sentinel`). Premissa registrada no `00-requisito.md`.

## Rotas

Nenhuma rota criada, alterada ou removida. As três rotas do hub continuam registradas pelo
`discoverPages()`; duas delas passam a responder 403 no default.

| Método | URI | Name | Efeito da flag |
|--------|-----|------|----------------|
| GET | `/infra/hub-de-infraestrutura` | `filament.infra.pages.hub-de-infraestrutura` | **nenhum** — sempre acessível a quem entra no painel |
| GET | `/admin/hub-de-administracao` | `filament.admin.pages.hub-de-administracao` | 403 com `KIT_HUB=false` (default) |
| GET | `/app/{tenant?}/hub-do-negocio` | `filament.app.pages.hub-do-negocio` | 403 com `KIT_HUB=false` (default) |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `HubDeInfraestrutura` | Filament Page (`CardsPage`) | `/infra/hub-de-infraestrutura` | lê a grade, **lê a descrição de cada card**, busca por texto, clica no card | busca sim (Alpine); grade e descrição não |
| `HubDeAdministracao` | Filament Page (`CardsPage`) | `/admin/hub-de-administracao` | nada no default — a tela responde 403 | não |
| `HubDoNegocio` | Filament Page (`CardsPage`) | `/app/{tenant}/hub-do-negocio` | nada no default — a tela responde 403 | não |
| Barra lateral do `/admin` e do `/app` | navegação do painel | qualquer tela do painel | **ausência** do item "Hub" é o observável | não |

**Gate de CT-B**: a grade pintada já é coberta por `tests/Browser/HubDeCardsTest.php` (CT-B01 da
ancestral). O que esta entrega acrescenta de afirmação só-navegador é **uma**: a descrição
aparece na tela, com estilo, sem quebrar o card. Texto presente no DOM é teste de componente; que
ele esteja **pintado** e caiba no card é o mesmo problema de cor/layout que a ancestral já resolveu
por screenshot. Resultado: **um** CT-B (CT-B02), como segundo `it()` do arquivo que já existe —
nenhum arquivo de teste de browser novo.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` nesta entrega. Não se aplica.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_HUB` | `false` | Liga os hubs em cards de `/admin` e de `/app`. O hub de `/infra` não depende dela. |

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Busca ⌘K (`wezlo/filament-search-spotlight`)**: a categoria `PagesAutorizadasCategory` do kit
  consulta `canAccess()` (é o que o comentário de `TenantResource.php:77` registra), então os hubs
  de `/admin` e `/app` **saem do Spotlight** junto com a barra lateral. Isso é desejado — item de
  busca para tela que responde 403 é affordance falsa. Verificar na regressão.
- **`DescobreCardsDoPainel` nos outros hubs**: o trait filtra por
  `canAccess() && shouldRegisterNavigation()`, então os hubs desligados **também deixam de aparecer
  como card** dentro do hub de `/infra` — que é o comportamento correto e vem de graça.
- **Wiki ancestral `hub-de-navegacao-em-cards`**: os CT-01, CT-02, CT-03 e CT-05 **dela** exercitam os hubs de
  `/admin` e `/app`, que passam a nascer desligados. Os arranjos desses casos precisam ligar a
  flag — é o mesmo ajuste que `config(['kit.demo' => true])` faz em nove arquivos de teste hoje.
- **`tests/Kit/PainelAppVazioTest.php`**: assere o que o painel `/app` oferece. O item do hub sai
  do menu no default — conferir se o caso afirma sobre a lista de itens.
- **Suíte de arte**: um cenário novo. O `kit:arte` publica **todo** PNG que encontrar em
  `tests/Browser/Screenshots` (`app/Console/Commands/KitArte.php:79-100`), e o CT-B01 já escreve
  `hub-infraestrutura.png` ali, **fora** da proporção da galeria. Por isso o cenário novo grava com
  outro nome (`infra-hub`) — ver ADR-05.

## Rollback

- **Reverter tudo**: `git revert` do commit. Não há migration, não há dado gravado, não há asset
  novo publicado.
- **Reverter só o comportamento, sem código**: `KIT_HUB=true` no `.env` devolve os três hubs.
  É o rollback de um caractere, e é o motivo de a flag existir em vez da remoção.
- **Reverter só a descrição**: apagar o mapa do `getCards()` do hub de infra. O trait volta a
  produzir card sem descrição, porque o parâmetro é opcional.

## Dependências

Nenhuma nova. `harvirsidhu/filament-cards` `^1.0` continua instalado (RQ-01).

## Riscos

- **A assimetria vira "bug" para o próximo agente.** `/infra` ligado e os outros dois com flag é
  exatamente o tipo de inconsistência que um agente competente "conserta". Mitigação: parágrafo no
  docblock do `HubDeInfraestrutura` (passo 6), linha na receita (passo 9) e o caso de teste do
  passo 12, que fica vermelho se alguém acrescentar a flag lá.
- **Descrição que envelhece.** O mapa é `FQCN => texto`, e plugin removido deixa chave órfã.
  Mitigação: chave órfã é inócua (nunca casa) e o mapa vive ao lado da lista de destinos, na mesma
  Page. Não vale um teste que compare mapa × destinos: ele ficaria vermelho a cada plugin novo,
  que é ruído, não defeito.
- **Descrição errada é pior que descrição ausente.** Um card que promete o que a tela não faz custa
  mais que um card sem frase. Mitigação: cada descrição do passo 6 foi escrita depois de abrir o
  destino (foi assim que "custo (USD), tokens e duração" saiu de
  `app/Filament/Infra/Resources/AiRuns/Schemas/AiRunInfolist.php:38-46`, não de suposição).
- **A captura de arte é frágil por tempo.** A suíte de arte já documenta que painel em cache frio
  custa ~25 s de compilação de componentes Livewire e estoura o teto de 45 s do Playwright
  (`tests/BrowserTenancy/CapturaDeArteTest.php:54-70`). O cenário novo precisa do mesmo aquecimento
  por `$this->get()` antes do `visit()`.

## Channel de Log da Feature

**Nenhum channel novo, e nenhum log novo.** Verifiquei `config/logging.php` e os channels do kit
(`autenticacao`, `tenancy`) antes de decidir.

A justificativa é a natureza da entrega: ela não executa lógica em runtime. O que ela acrescenta é
(a) uma leitura de config dentro de `canAccess()`, avaliada em todo request do painel, e (b) texto
estático em um mapa. Logar a leitura de uma flag em `canAccess()` produziria uma linha de log **por
request de painel**, o que é ruído com custo de I/O e nenhuma pergunta respondida — a resposta a
"o hub está ligado?" é `php artisan config:show kit.hub`, não um arquivo de log.

> Registrado explicitamente para o `feature-quality-gate` não ler a ausência como omissão do
> padrão de log do projeto.

## Estrutura de Implementação

### 1. A chave de config

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`
- Acrescentar, **depois** do bloco `demo` e antes de `retencao` (a ordem do arquivo é
  narrativa: instalação → tenancy → demo → observabilidade):

```php
    /*
    |--------------------------------------------------------------------------
    | Hub de navegação em cards
    |--------------------------------------------------------------------------
    | Desligado por default. Liga as páginas hub — uma grade de cartões com os
    | destinos do painel, em vez da árvore da barra lateral — nos painéis
    | /admin e /app.
    |
    | Desligado porque o kit inicial não precisa: /admin tem oito destinos e o
    | /app de um projeto de verdade nasce vazio. Grade de cartões paga o próprio
    | espaço quando há MUITOS caminhos e a pergunta "onde vejo X?" é real.
    |
    | ## O /infra NÃO depende desta chave
    |
    | Lá o hub nasce ligado, e de propósito: são dezesseis destinos em quatro
    | grupos, metade com rótulo de plugin de terceiro não traduzido. É o único
    | painel do kit onde a grade ganha da árvore no default.
    |
    | Ligando aqui, os três painéis passam a ter hub — nada mais precisa ser
    | editado, porque o FilamentCardsPlugin já está registrado nos três e o CSS
    | dos cartões já é publicado.
    |
    | O pacote (harvirsidhu/filament-cards) fica instalado com a chave desligada:
    | ele é o dono do padrão "página que exibe links e fluxos em grade", e
    | wikis/receitas.md tem a receita de quando usá-lo.
    */

    'hub' => (bool) env('KIT_HUB', false),
```

### 2. O `.env.example`

> Skills: nenhuma

- **Path**: `.env.example`
- Acrescentar logo abaixo do bloco `KIT_DEMO` (linhas 99-101), no mesmo formato de comentário:

```dotenv
# Hub de navegação em cards nos painéis /admin e /app: uma grade de cartões com
# os destinos do painel. Desligado porque o kit inicial não precisa. O /infra
# tem hub independente desta chave.
KIT_HUB=false
```

### 3. O `phpunit.xml`

> Skills: `pest-testing`

- **Path**: `phpunit.xml`
- Acrescentar na lista de `<env>`, ao lado de `KIT_DEMO`:

```xml
        <env name="KIT_HUB" value="false" force="true"/>
```

- **Por que `force="true"`**: é o que o `KIT_DEMO` já faz na linha 64. Sem `force`, um `.env` local
  com `KIT_HUB=true` faria a suíte medir uma configuração que o kit não entrega, e os casos do
  passo 12 (os que provam o default) passariam por acidente.

### 4. O hub de `/admin` atrás da flag

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Pages/HubDeAdministracao.php`
- Acrescentar, depois de `getPageClasses()` e antes de `getCards()`:

```php
    /**
     * Some do menu, da URL e da busca ⌘K quando a flag está desligada — que é o default do kit.
     *
     * Só `canAccess()`, sem `shouldRegisterNavigation()`: em Page do Filament 5 o
     * `registerNavigationItems()` já retorna cedo quando `canAccess()` é falso
     * (`vendor/filament/filament/src/Pages/Page.php:133-135`), então um método basta para os três
     * efeitos. Em Resource seriam dois — é por isso que `ProjetoResource` sobrescreve os dois e
     * esta Page, um.
     *
     * A rota continua registrada e responde 403, com a tela branda do filament-sentinel. Tirar a
     * rota exigiria recortar o `discoverPages()` do provider, e o Shield deixaria de gerar a
     * permission — ver ADR-02.
     */
    public static function canAccess(): bool
    {
        return (bool) config('kit.hub') && parent::canAccess();
    }
```

- **`&& parent::canAccess()` não é decorativo por engano**: hoje o pai devolve `true` fixo
  (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24`), então a chamada não
  muda nada. Ela fica porque é a forma que as outras cinco sobrescritas do kit usam
  (`ProjetoResource`, `ConviteResource`, `TenantResource`, os dois `UserResource`) e porque o dia em
  que a autorização de Page passar a ser enforçada, esta linha já está certa.
- Acrescentar ao comentário de `AdminPanelProvider.php:197` a menção à flag:
  `// Páginas hub em grade de cartões (App\Filament\Admin\Pages\HubDeAdministracao) — ligadas por config('kit.hub').`

### 5. O hub de `/app` atrás da flag

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/App/Pages/HubDoNegocio.php`
- Mesmo `canAccess()` do passo 4, com o docblock apontando para lá em vez de repetir o argumento.
- **Não mexer** na seção "Esta página NÃO entra na lista de subtração do `panel_user`" do docblock
  da classe: ela fala da matriz de permissões, que esta entrega não altera. Acrescentar **uma**
  frase ao final dela:
  `A flag `kit.hub` esconde a página; ela não muda a permissão. Com o hub desligado, `View:HubDoNegocio` continua no `panel_user` — e é isso que faz ligar a flag bastar.`
- Acrescentar a menção à flag no comentário de `AppPanelProvider.php:279`.

### 6. O hub de `/infra`: a exceção declarada, e as descrições

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Infra/Pages/HubDeInfraestrutura.php`
- **Não** acrescentar `canAccess()`. Acrescentar ao docblock da classe:

```
 * ## Por que esta é a única que NÃO depende de `config('kit.hub')`
 *
 * Os hubs de /admin e /app nascem desligados: o kit inicial não precisa de grade de cartões para
 * oito destinos, e o /app de um projeto de verdade nasce vazio. Aqui são dezesseis, em quatro
 * grupos, e metade tem rótulo de plugin de terceiro sem tradução ("audits", "Exception",
 * "Manage commands", "Run history") — a árvore da barra lateral não responde "onde vejo os
 * backups?" e a grade com descrição responde.
 *
 * A assimetria é decisão do mantenedor, registrada em ADR-01 desta wiki, e há um caso de teste
 * que fica vermelho se alguém "corrigir" a inconsistência acrescentando a flag aqui.
```

- Acrescentar o mapa de descrições e passá-lo ao trait:

```php
    /**
     * O que cada destino do painel serve, por FQCN.
     *
     * ## Por que um mapa aqui, e não um método na classe de cada destino
     *
     * Treze dos dezesseis destinos deste painel são **vendor** — QueueMonitorResource,
     * ExceptionResource, HealthCheckResults, BackupRunsPage, LogsExplorer, RecycleBin e as três
     * Pages do command center, entre outras. Não há onde declarar a frase na classe, e um método
     * novo no trait (`getNavigationDescription()`) só funcionaria para os três destinos do kit.
     *
     * A descrição entra no `data-search-text` de cada cartão
     * (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:264`), então
     * a busca desta página passa a encontrar por assunto — "fila", "restaurar", "e-mail" — e não
     * só pelo rótulo. É a razão de valer a pena num painel com `$searchable = true`.
     *
     * Chave órfã (plugin removido) é inócua: nunca casa e nada acusa. Chave ausente também: o
     * cartão sai sem frase, como antes desta wiki.
     *
     * @return array<class-string, string>
     */
    protected static function descricoesDosDestinos(): array
    {
        return [
            HealthCheckResults::class          => 'Estado atual dos checks de banco, cache, fila, agendador, disco e ambiente.',
            QueueMonitorResource::class        => 'Histórico dos jobs da fila: o que rodou, o que falhou e quanto tempo levou.',
            ExceptionResource::class           => 'Exceções agrupadas por tipo e frequência, com stack trace e requisição.',
            Pulse::class                       => 'Requisições e queries lentas, uso de servidor e vazão das filas.',
            AiRunResource::class               => 'Ledger das execuções de IA: prompt, resposta, tokens, custo em USD e duração.',
            AuthenticationLogResource::class   => 'Quem entrou, de qual IP e em qual dispositivo — e as tentativas que falharam.',
            AuditResource::class               => 'Trilha de alterações dos registros: quem mudou o quê, com valor antes e depois.',
            MailLogResource::class             => 'Todo e-mail que a aplicação enviou, com destinatário, assunto e corpo.',
            LogsExplorer::class                => 'Os arquivos de log da aplicação, lidos pela interface, sem acesso ao servidor.',
            BackupRunsPage::class              => 'Últimos backups: quando rodaram, tamanho e se o destino respondeu.',
            ComposerReleasePackageResource::class => 'Versões novas dos pacotes instalados, comparadas com o composer.lock.',
            Commands::class                    => 'Roda um comando Artisan pela interface, dentro da lista autorizada.',
            History::class                     => 'Histórico de execução dos comandos: quem rodou, com quais argumentos e a saída.',
            CommandRecordResource::class       => 'Cadastro dos comandos liberados para a central de comandos.',
            RecycleBin::class                  => 'Registros apagados com soft delete, com restauração registro por registro.',
            DependencyGraphPage::class         => 'Mapa dos models, relações, resources e painéis da aplicação.',
        ];
    }

    /**
     * @return array<CardGroup>
     */
    protected static function getCards(): array
    {
        return static::cardsDoPainel(
            excluir: [static::class, Dashboard::class],
            descricoes: static::descricoesDosDestinos(),
        );
    }
```

- **Os dezesseis FQCN foram levantados do painel, não escritos de memória**:
  `php artisan tinker --execute` sobre `Filament::getPanel('infra')`, `getResources()` +
  `getPages()`. `MyProfilePage` e `RunView` ficaram fora porque
  `shouldRegisterNavigation()` é falso neles e o trait já os descarta; `Dashboard` e a própria
  Page saem pelo `excluir`.
- **Verificar antes de escrever cada frase**: abrir o destino. As frases acima já foram conferidas
  assim — a de `AiRunResource` saiu de
  `app/Filament/Infra/Resources/AiRuns/Schemas/AiRunInfolist.php:38-46` (seção "Consumo": tokens
  in/out, custo em USD, duração em ms), a de `LogsExplorer` do `deletable(false)` documentado em
  `InfraPanelProvider.php:186-193`.

### 7. O trait aceita descrição

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Concerns/DescobreCardsDoPainel.php`
- `cardsDoPainel()` ganha o parâmetro e o repassa:

```php
    /**
     * @param  list<class-string>  $excluir  classes que não viram cartão (a própria página, o Dashboard)
     * @param  array<class-string, string>  $descricoes  frase de cada destino, por FQCN; chave ausente sai sem frase
     * @return list<CardGroup>
     */
    protected static function cardsDoPainel(array $excluir = [], array $descricoes = []): array
    {
        // ... inalterado até o ->map()
            ->map(fn (string $componente): CardItem => static::cardDe($componente, $descricoes[$componente] ?? null))
            ->pipe(static::agrupar(...));
    }
```

- `cardDe()` ganha o segundo parâmetro e a chamada:

```php
    protected static function cardDe(string $componente, ?string $descricao = null): CardItem
    {
        // ...
        return CardItem::make($componente)
            ->label($componente::getNavigationLabel())
            ->description($descricao)
            // ... resto inalterado
```

- **`->description(null)` é seguro**: a assinatura é
  `description(string|Htmlable|Closure|null $description)`
  (`vendor/harvirsidhu/filament-cards/src/Concerns/HasDescription.php:12`) e a blade só renderiza o
  `<p>` sob `@if (filled($itemDescription))` (linha 373). Cartão sem frase sai byte a byte como
  hoje — o que é exatamente o que mantém os hubs de `/admin` e `/app` intactos ao ligar a flag.
- **Parâmetro opcional, e não obrigatório**: as outras duas Pages não passam nada, e RQ-07 é só do
  `/infra`.

### 8. A captura de tela do hub

> Skills: `pest-testing`

- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php`
- Acrescentar um cenário, no padrão dos quatro que já existem:

```php
/**
 * O hub de infraestrutura — a grade com descrição em cada cartão.
 *
 * É a imagem que a documentação da opção usa: sem ela, "grade de cartões em vez de árvore" é
 * uma frase, e quem lê a receita não sabe o que ganha ao ligar a flag.
 *
 * Nome `infra-hub` e não `hub-infraestrutura`: o CT-B01 (`tests/Browser/HubDeCardsTest.php`)
 * já escreve `hub-infraestrutura.png` no MESMO diretório, sem `resize()`, e o `kit:arte`
 * publica todo PNG que encontra lá (`app/Console/Commands/KitArte.php:79-100`). Dois cenários
 * com o mesmo nome fariam a imagem publicada depender de qual suíte rodou por último — e a
 * galeria receberia, de vez em quando, uma captura fora de proporção. Ver ADR-05.
 */
it('captura o hub de infraestrutura', function (): void {
    visit('/infra/hub-de-infraestrutura')
        ->resize(1400, 875)
        ->assertSee('Saúde da aplicação')
        ->screenshot(fullPage: false, filename: 'infra-hub');
})->group('browser', 'art');
```

- **O `beforeEach` do arquivo precisa aquecer o `/infra`**: ele hoje aquece
  `ProjetoResource::getUrl()` e `/admin/shield/roles` por `$this->get()`, exatamente para pagar a
  compilação dos componentes Livewire fora do cronômetro do Playwright (o docblock de lá, linhas
  54-70, mede: sem isso, o primeiro cenário morre em "Timeout 45000ms exceeded"). Acrescentar
  `$this->get('/infra/hub-de-infraestrutura');` à lista.
- O usuário do arranjo já é `master_global`, que entra no `/infra` pelo `Gate::before`. Nada mais
  a arranjar.
- **Publicar**: `composer art` (que roda `npm run build`, `view:cache`, a suíte com `KIT_ART=1` e
  o `kit:arte`). Produz `art/infra-hub.png` (1400x875) e `art/thumbs/infra-hub.png` (760x475).

### 9. A receita

> Skills: nenhuma

- **Path**: `wikis/receitas.md`, seção "Página hub de cards"
- Quatro edições:
  1. **Abrir a seção com o estado atual**, antes do exemplo de código: o `/infra` nasce com hub;
     `/admin` e `/app` são `KIT_HUB=true`; o pacote fica instalado nos dois casos. (RQ-02, RQ-03)
  2. **Quinto caso de uso** na lista "Quatro casos de uso" — que passa a ser cinco:
     *"**Página de fluxo** — apresentar as etapas de um processo como cartões, em ordem, com
     descrição em cada etapa. É o encaixe que o pacote pede e que o kit não usa hoje."* (RQ-06)
  3. **Descrição nos cartões**, subseção nova depois de "O que NÃO fazer": como o mapa por FQCN
     funciona, por que não é método na classe do destino (vendor), e que a frase entra na busca
     do hub. Com o trecho de `descricoesDosDestinos()`. (RQ-07, RQ-02)
  4. **A imagem**, no topo da seção, no padrão de thumb-com-link do README:
     `[![Hub de infraestrutura …](…/art/thumbs/infra-hub.png)](…/art/infra-hub.png)` (RQ-05)
- Acrescentar em "O que NÃO fazer": *"**Ligar o hub em painel com poucos destinos.** Grade de
  cartões para quatro links é a árvore da barra lateral com passos a mais. Foi por isso que
  `/admin` e `/app` saíram do default."*

### 10. O `pacotes.md` e o CHANGELOG

> Skills: nenhuma

- **`wikis/pacotes.md:36`**: a linha do `harvirsidhu/filament-cards` ganha o estado —
  `páginas hub: grade de cartões de navegação em vez de árvore na barra lateral. **Ligado no /infra; opt-in (`KIT_HUB`) nos outros dois** — nunca Blade de cartões à mão`
- **`CHANGELOG.md`**: entrada de mudança de comportamento — o hub deixa de nascer em `/admin` e
  `/app`, `KIT_HUB` devolve, `/infra` inalterado e com descrição nos cartões. É mudança visível
  para quem já instalou o kit e vai rodar `kit:update`.

**`README.md` e `README.en.md` não são tocados** — corte da auditoria Ponytail. O motivo é
verificável: o README **nunca** mencionou o hub nem o pacote (zero ocorrências de `hub`,
`cards` ou `harvirsidhu` nas duas versões, conferido). Acrescentar uma seção agora cria **dois**
arquivos espelhados a manter por uma feature que nasce desligada em dois dos três painéis, e
RQ-05 pede a imagem "como opções de uso" — que é exatamente o papel de `wikis/receitas.md`,
não do README. Se o usuário quiser o hub anunciado no README, é acréscimo de escopo consciente,
não esquecimento.

### 11. Os casos de teste novos, e a regressão obrigatória

> Skills: `pest-testing`

Primeiro os oito CT e o CT-B especificados no `04` e no `05`:

| Arquivo | Casos |
|---|---|
| `tests/Kit/HubDeCardsTest.php` | CT-01 (linhas `/admin`), CT-02, CT-05, CT-06, CT-07, CT-08, CT-11, CT-12 |
| `tests/Tenancy/HubDoNegocioTest.php` | CT-01 (linha `panel_user`) |
| `tests/Browser/HubDeCardsTest.php` | CT-B02, como **segundo `it()`** ao lado do CT-B01 existente |

Depois a regressão. Tipo `evolução` + infra compartilhada tocada. Rodar os CT/CT-B da wiki ancestral e ajustar o
**arranjo**, nunca a assertion:

> ⚠️ **Os IDs da tabela abaixo são da wiki ancestral**, num espaço de numeração separado do `04`
> desta wiki. O "CT-03 da ancestral" (o hub responde em cada painel) não tem relação com o CT-03
> **desta** wiki, que foi cortado pela auditoria Ponytail. Sempre escrever "da ancestral" ao citá-los.

| Caso da ancestral | Arquivo | O que muda |
|---|---|---|
| CT-01 | `tests/Kit/HubDeCardsTest.php:78` | `config(['kit.hub' => true])` no arranjo (painel `/admin`) |
| CT-02 | `tests/Tenancy/HubDoNegocioTest.php:44` | idem (painel `/app`), ao lado do `kit.demo` que já está lá |
| CT-03 | `tests/Kit/HubDeCardsTest.php:32` | as duas linhas de `/admin` precisam da flag; a de `/infra`, **não** — e essa diferença é o que o caso passa a provar |
| CT-04 | `tests/Tenancy/HubDoNegocioTest.php:79` | **nada** — fala do banco, não da tela |
| CT-05 | `tests/Kit/HubDeCardsTest.php:53` | nada: roda no `/infra` |
| CT-B01 | `tests/Browser/HubDeCardsTest.php` | nada no arranjo; ganha assertion da descrição |

- **Ajuste de arranjo é ajuste legítimo; afrouxar assertion não é.** Se um caso da ancestral ficar
  vermelho por outro motivo que não a flag, é achado — vai para `03-progresso.md`, não para o
  `.gitignore`.

### 12. Verificação e commits

> Skills: `pest-testing`, `ponytail`

- Ver `## Verificação Final`.
- Commits, na ordem:
  - `:wrench: feat(hub): flag kit.hub tira o hub de cards do padrão em /admin e /app`
  - `:sparkles: feat(hub): descrição em cada cartão do hub de infraestrutura`
  - `:white_check_mark: test(hub): o default sem hub e a descrição dos cartões`
  - `:bento: docs(arte): captura do hub de infraestrutura`
  - `:memo: docs(hub): receita, pacotes.md, CHANGELOG e wiki da feature`

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> A escada já cortou três coisas nesta wiki, e elas ficam registradas para não voltarem:
> 1. `shouldRegisterNavigation()` ao lado de `canAccess()` — desnecessário em Page, porque
>    `Page::registerNavigationItems()` já sai cedo quando `canAccess()` é falso
>    (`vendor/filament/filament/src/Pages/Page.php:133-135`). Um método, não dois.
> 2. CSS novo para a descrição — o `cards.css` já cobre as três classes que a blade emite.
> 3. Gate de `config('kit.hub')` no `Css::make('kit-cards')` do `KitServiceProvider` — seria um
>    `if` que nunca fica falso, porque o `/infra` continua com hub.
>
> Atalhos deliberados marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário. Arquivos wiki (00-06) são
> boundary do Caveman — prosa normal. Código, commits e PRs também.

## Testes

> Ver `04-casos-de-teste.md` — **8 cenários** (CT-01, CT-02, CT-05..CT-08, CT-11, CT-12), depois de
> a auditoria Ponytail cortar quatro, um deles tautológico.
> Ver `05-casos-de-teste-browser.md` — **um** CT-B (CT-B02), segundo `it()` do arquivo que já
> abriga o CT-B01 da ancestral. É a única afirmação desta entrega que só o navegador prova: a
> descrição **pintada** e caber no cartão.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --filter=Hub` (os três arquivos de hub)
- [ ] `php artisan test --compact --filter=PainelAppVazio` (o menu do `/app` sem o hub)
- [ ] `vendor/bin/pest --testsuite=Browser --filter=Hub` (CT-B01, sem `--parallel`)
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] `php artisan config:show kit.hub` devolve `false` num `.env` limpo
- [ ] `composer art` produziu `art/infra-hub.png` e `art/thumbs/infra-hub.png` na proporção da
      galeria, e **não** sobrescreveu nenhuma imagem existente
- [ ] a captura foi **aberta e olhada**: os dezesseis cartões com frase legível, sem texto
      estourando o cartão, nos dois temas

## Commits

Ver passo 12.
