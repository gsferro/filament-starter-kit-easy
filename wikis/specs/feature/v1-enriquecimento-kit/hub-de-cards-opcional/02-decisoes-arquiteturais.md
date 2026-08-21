# Decisões Arquiteturais — Hub de cards fora do padrão da instalação

> Wiki ancestral: `wikis/specs/main/hub-de-navegacao-em-cards/`. As ADR-01 a ADR-05 de lá
> continuam válidas; as daqui as **refinam**, nenhuma as revoga.

## ADR-01: O hub sai do padrão por flag, não por remoção

**Status**: Aceita
**Data**: 2026-08-21

### Contexto

RQ-03 manda tirar o hub do padrão da instalação. RQ-01 manda manter o pacote instalado e RQ-02
manda deixá-lo documentado para uso. As três cláusulas juntas descrevem uma tensão real: a forma
mais direta de "tirar do padrão" é apagar as três Pages, e apagá-las transforma a única
implementação **testada** do padrão em um trecho de código dentro de um arquivo Markdown.

O kit já enfrentou a mesma tensão com o resource de demonstração e resolveu por flag:
`ProjetoResource::canAccess()` devolve
`config('kit.tenancy.enabled') && config('kit.demo')`
(`app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88`), e o `phpunit.xml:64` fixa
`KIT_DEMO=false` para que a suíte prove o default.

### Decisão

Chave `kit.hub`, default `false`, lida dentro de `canAccess()` das Pages de `/admin` e `/app`. As
três Pages, a trait, o CSS e os três arquivos de teste permanecem no repositório.

### Alternativas Consideradas

1. **Apagar as três Pages, a trait, o CSS e os testes; deixar só a receita em
   `wikis/receitas.md`.** Recusada por três razões medidas nesta base:
   - a receita passaria a carregar ~40 linhas de código que **nenhum teste executa**, e código em
     Markdown apodrece em silêncio — o próprio `.ai/rules/testes.md` documenta o padrão inverso
     ("citar não é executar") como fonte de erro;
   - o trait `DescobreCardsDoPainel` é a parte com risco de segurança (o `CardItem` do pacote
     **não** verifica autorização — `vendor/harvirsidhu/filament-cards/src/Concerns/CanBeHidden.php`
     avalia só `visible`/`hidden`), e ADR-04 da ancestral existe justamente para que ninguém
     reescreva esse filtro à mão. Apagá-lo devolve a armadilha a quem copiar a receita;
   - ligar de novo passaria a ser copiar-colar mais ressemear o Shield, em vez de um caractere.
2. **`$shouldRegisterNavigation = false` nas Pages, sem flag.** Recusada porque atende RQ-04 e
   **não** atende RQ-03: a rota continua acessível, e o Spotlight do kit continua ofertando a tela
   — o hub deixaria de estar no menu e continuaria sendo padrão da instalação.
3. **Pergunta no `kit:install`.** Recusada por decisão anterior já registrada: o customizador
   aceita "só valor escalar que muda bit no disco" e fechou em cinco perguntas
   (`app/Support/CustomizadorDaInstalacao.php:16-29`). Navegação de painel não passa nessa régua, e
   uma sexta pergunta cobraria de quem instala uma decisão que ele ainda não tem informação para
   tomar.

### Consequências

- **Positivas**: `KIT_HUB=true` devolve a tela que existe hoje, sem edição de código; os três
  arquivos de teste continuam provando o padrão; RQ-01 e RQ-02 são atendidas sem esforço extra.
- **Negativas**: o kit carrega três Pages que o projeto default não usa — ~130 linhas de código
  "adormecido", e a matriz do Shield continua com três permissions que ninguém exerce.
- **Riscos**: código adormecido apodrece. Mitigado por os testes rodarem com a flag ligada, o que
  mantém o caminho exercitado a cada suíte.

### Referências

- `app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88` — o precedente
- `phpunit.xml:64` — o default provado, não presumido
- ADR-04 da wiki ancestral — por que o trait não pode virar receita
- Refina: ADR-01 da wiki ancestral (o hub soma à barra lateral)

---

## ADR-02: A rota continua registrada e responde 403 — o desligamento não é 404

**Status**: Aceita
**Data**: 2026-08-21

### Contexto

Com `canAccess()` falso, o Filament aborta com 403:
`abort_unless(static::canAccess(), 403)` em
`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:8-15`, chamado tanto no
`mount` quanto no `hydrate`. A rota permanece registrada pelo `discoverPages()` do provider.

A alternativa seria tirar a rota do ar, condicionando o registro da Page no provider.

### Decisão

Aceitar o 403 com a rota de pé.

### Alternativas Consideradas

1. **Recortar o registro no provider** (`->pages([...])` condicional, ou excluir do
   `discoverPages()`). Recusada por dois efeitos:
   - o Shield descobre as entidades por `$panel->getPages()` cru
     (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`), então a Page
     fora do painel **deixa de gerar a permission**. `View:HubDoNegocio` sairia da matriz, e o caso
     `tests/Tenancy/HubDoNegocioTest.php:79-85` — que existe para fixar ADR-05 da ancestral — ficaria
     vermelho por uma mudança que nada tem a ver com aquela decisão;
   - ligar a flag passaria a exigir `php artisan optimize:clear` mais os dois seeders, em vez de só
     recarregar a página.
2. **`abort(404)` explícito.** Recusada: exigiria sobrescrever `mount()` na Page para preceder o
   trait do Filament, e trocaria a tela branda de 403 do kit
   (`anselmokossa/filament-sentinel`, ver `art/erro-403.png`) por um 404 sem tratamento.

### Consequências

- **Positivas**: a matriz de permissões não muda; ligar a flag é recarregar; o caso da ancestral
  sobre a subtração do `panel_user` continua verde sem tocar nele.
- **Negativas**: quem digitar `/admin/hub-de-administracao` com a flag desligada recebe 403, e não
  "não existe". Enumeração de rota, sem vazamento de dado — a tela não renderiza nada.
- **Riscos**: nenhum de segurança. O 403 é o mesmo comportamento que `ProjetoResource` já produz
  com a demo desligada, então o kit não ganha uma classe nova de resposta.

### Referências

- `vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:8-15`
- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`
- `00-requisito.md`, seção "Devolvidas pela derivação de testes"

---

## ADR-03: O `/infra` fica fora da flag — a assimetria é a decisão, não um esquecimento

**Status**: Aceita
**Data**: 2026-08-21

### Contexto

O texto do requisito diz "para telas iniciais, não compensa" — leitura literal: nenhum painel nasce
com hub. Perguntado, o mantenedor decidiu manter o `/infra`.

O argumento é de cardinalidade, e ele é verificável: levantei os destinos de cada painel com
`Filament::getPanel($id)->getResources()` + `getPages()`. O `/infra` tem **16** destinos
navegáveis, em quatro grupos (`Observabilidade`, `IA`, `Trilhas`, `Sistema` —
`app/Providers/Filament/InfraPanelProvider.php:92-97`), e **metade** deles tem rótulo de plugin de
terceiro sem tradução: `audits`, `Exception`, `Manage commands`, `Run history`, `Commands`,
`Pulse`. O `/admin` tem 8; o `/app`, 4 — e o `/app` de um projeto real nasce vazio, porque o único
resource de negócio é o de demonstração.

### Decisão

`HubDeInfraestrutura` **não** sobrescreve `canAccess()`. Os outros dois sobrescrevem.

### Alternativas Consideradas

1. **Três flags** (`kit.hub.admin`, `kit.hub.app`, `kit.hub.infra`). Recusada: três interruptores
   para uma decisão que ninguém vai tomar por painel, e a chave do `/infra` nasceria `true` para
   nunca ser mudada. Chave que só tem um valor não é configuração.
2. **Uma flag valendo para os três, default `true`.** Recusada: é o estado atual com um nome novo,
   e não atende RQ-03.
3. **Uma flag valendo para os três, default `false`.** Recusada pelo usuário — retiraria o hub do
   painel que a ancestral já argumentava ser o caso mais forte.

### Consequências

- **Positivas**: um interruptor só; o painel que precisa do hub nasce com ele; RQ-04 atendida com
  a exceção declarada, e o `feature-quality-gate` tem onde ler que a exceção é intencional.
- **Negativas**: o kit passa a ter dois comportamentos de hub, e isso é exatamente o tipo de
  inconsistência que um agente de IA "conserta" sem perguntar.
- **Mitigação da negativa, em três camadas**: parágrafo no docblock da classe explicando por que
  ela é a exceção; linha na receita; e um caso de teste que fica **vermelho** se alguém acrescentar
  a flag ao `/infra` — prosa não impede, teste impede.

### Referências

- `app/Providers/Filament/InfraPanelProvider.php:92-97` — os quatro grupos
- `app/Filament/Infra/Pages/HubDeInfraestrutura.php:14-22` — o argumento original, de ADR-01 da ancestral
- Refina: ADR-01 desta wiki

---

## ADR-04: A descrição vem de um mapa por FQCN na Page, não de um método na classe do destino

**Status**: Aceita
**Data**: 2026-08-21

### Contexto

RQ-07 quer uma frase por cartão. O trait monta cada cartão a partir dos metadados que a **própria
classe do destino** declara — `getNavigationLabel()`, `getNavigationIcon()`,
`getNavigationBadge()`, `getNavigationSort()` — e o cabeçalho do trait registra que é isso que
mantém a grade fiel à barra lateral sem duas fontes de verdade
(`app/Filament/Concerns/DescobreCardsDoPainel.php:65-68`).

O caminho coerente com esse desenho seria um `getNavigationDescription()` em cada destino. Ele não
existe no Filament, e — mais decisivo — **13 dos 16 destinos do `/infra` são vendor**:
`QueueMonitorResource`, `AuthenticationLogResource`, `AuditResource`,
`ComposerReleasePackageResource`, `CommandRecordResource`, `ExceptionResource`,
`MailLogResource`, `HealthCheckResults`, `BackupRunsPage`, `LogsExplorer`,
`DependencyGraphPage`, `Commands`, `History`, `RecycleBin`. Não há onde declarar o método.

### Decisão

`array<class-string, string>` declarado em `HubDeInfraestrutura::descricoesDosDestinos()`, passado
ao trait como parâmetro opcional. Chave ausente ou órfã produz cartão sem frase.

### Alternativas Consideradas

1. **Método `getNavigationDescription()` nos destinos.** Inviável para 13 dos 16 — só funcionaria
   para os três destinos do kit, e a grade sairia com três frases e treze buracos.
2. **Arquivo de tradução** (`lang/pt_BR/hub.php`) com a chave por FQCN. Recusada: acrescentaria uma
   indireção sem ganho, porque o kit é pt-BR-only por decisão registrada (`config/kit.php`,
   bloco "Idiomas do painel") e internacionalizar os rótulos do próprio kit está declarado como
   trabalho ainda não feito. No dia em que for, o mapa vira `__()` sem mudar de lugar.
3. **Descrição no `CardGroup`, não no `CardItem`.** O pacote suporta as duas
   (`cards-page.blade.php:157` e `:236`), e descrever o **grupo** custaria quatro frases em vez de
   dezesseis. Recusada porque RQ-07 diz "o que cada link serve", e grupo não é link — "Trilhas"
   descrito não responde a diferença entre "Logs", "Logs de Autenticação" e "E-mails enviados", que
   são justamente os três destinos do grupo.
4. **Sobrescrever o rótulo do destino em vez de descrevê-lo** (`->label('Auditoria')` no lugar de
   `audits`). Recusada: o trait não redigita metadado por decisão de ADR-04 da ancestral —
   resource renomeado no vendor se atualiza sozinho no hub. Sobrescrever rótulo cria a segunda
   fonte de verdade que aquela ADR existe para evitar. A descrição **acompanha** o rótulo do vendor
   em vez de disputar com ele.

### Consequências

- **Positivas**: funciona para vendor e para código do kit sem distinção; a frase entra no
  `data-search-text` do cartão
  (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:264`), então a
  busca do hub passa a encontrar por assunto ("fila", "restaurar", "e-mail") e não só por rótulo —
  ganho que só existe porque este painel tem `$searchable = true`;
  **nenhum CSS novo** (as três classes que a blade emite já estão em `resources/css/filament/cards.css`
  nas linhas 114, 120 e 141).
- **Negativas**: o mapa é uma lista escrita à mão, e lista à mão envelhece — plugin novo entra na
  grade sem frase, plugin removido deixa chave órfã.
- **Riscos**: descrição factualmente errada é pior que ausente. Mitigado por cada frase ter sido
  escrita depois de abrir o destino (a de `AiRunResource` saiu de
  `app/Filament/Infra/Resources/AiRuns/Schemas/AiRunInfolist.php:38-46`, não de suposição) — que é
  exatamente o que `.ai/rules/specs.md` exige.
- **Riscos recusados**: um teste comparando o mapa com a lista de destinos do painel. Ficaria
  vermelho a cada plugin novo — ruído, não defeito. Chave faltando degrada o cartão, não a tela.

### Referências

- `app/Filament/Concerns/DescobreCardsDoPainel.php:65-68` — por que metadado não se redigita
- `vendor/harvirsidhu/filament-cards/src/Concerns/HasDescription.php:12-22`
- `vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:264, 373-381`
- `resources/css/filament/cards.css:114, 120, 141`
- Refina: ADR-04 da wiki ancestral

---

## ADR-05: o CT-B é a captura — uma tela, um screenshot, um nome

**Status**: Aceita — **substitui a primeira versão desta ADR**, que decidia outra coisa
**Data**: 2026-08-21

### Contexto

RQ-05 pede a imagem. Duas suítes escrevem PNG no **mesmo** diretório, porque
`tests/Browser/Screenshots` é caminho fixo do `pest-plugin-browser`, e o `kit:arte` publica **todo**
PNG que encontra lá, sem lista de permissão (`app/Console/Commands/KitArte.php:79-100`) — a única
exceção são os três quadros do GIF.

A primeira versão desta ADR aceitou a duplicação e resolveu por **nome**: a suíte de arte gravaria
`infra-hub`, o CT-B01 continuaria com `hub-infraestrutura`. Duas capturas da mesma tela, uma
publicada e outra não.

**A implementação derrubou aquela decisão, e por um motivo que a wiki não tinha previsto.** A
captura de arte saiu com a **barra lateral do painel errado**: o `beforeEach` da suíte de arte
aquece `/app` e `/admin` por `$this->get()`, o servidor do plugin roda **in-process**, e o estado de
painel atravessa para o `visit()`. Duas tentativas de corrigir falharam — `fronteiraDeRequest()` no
`beforeEach` derrubou os três cenários que criam `Projeto` (`Filament::getTenant()` esquecido, e o
`BelongsToTenant` sem `tenant_id`), e movida para antes do `visit()` produziu topbar **duplicada**
com a barra lateral ainda errada.

O experimento decisivo foi o CT-B02, que vive em `tests/Browser` e **não atravessa painéis**: ele
renderiza a tela corretamente. O defeito é do aquecimento cruzado da suíte de arte, é
**pré-existente** (`art/admin-papeis-import-export.png` está publicada assim desde o commit
`04642b0`) e não pertence a esta entrega.

### Decisão

**A captura de arte do hub é o próprio CT-B02**, em `tests/Browser/HubDeCardsTest.php`: ele ganha
`resize(1400, 875)` e grava `infra-hub`. O `->screenshot()` do CT-B01, que fotografava a **mesma
tela** com outro nome, foi removido. O `composer art` passa a invocar também esse arquivo.

Consequência: **existe uma captura por tela, com um nome só**, e a colisão que a primeira versão
desta ADR contornava deixou de existir. Não há mais nome a reservar nem lista de permissão a
manter.

### Alternativas Consideradas

1. **Insistir na captura dentro da suíte de arte**, diagnosticando o vazamento de painel. Recusada
   como escopo: é defeito pré-existente, atinge outra imagem já publicada, e a correção exige
   reestruturar o `beforeEach` que quatro cenários alheios usam. Fica como trabalho próprio — e
   quando vier, corrige a `admin-papeis-import-export.png` também.
2. **Publicar sem imagem**, RQ-05 como débito. Recusada pelo mantenedor.
3. **Manter as duas capturas e dar ao `kit:arte` uma lista de permissão.** Recusada: resolve por
   configuração um problema que desapareceu ao remover a duplicação. Menos código, não mais.
4. **Guardar o CT-B02 com `KIT_ART`**, como faz a suíte de arte. Recusada: a imagem sairia só na
   captura, e o valor de ela nascer do CT-B é justamente estar **sempre atual** — o screenshot é
   reescrito a cada `composer test:browser`.

### Consequências

- **Positivas**: uma tela, uma captura, um nome; a imagem da documentação nasce do mesmo cenário que
  prova que a tela funciona; o CT-B02 deixou de duplicar uma visita de navegador que o CT-B01 já
  fazia (visita de browser é o item mais caro da suíte).
- **Negativas**: `composer art` ganhou uma invocação de `artisan test` e ficou mais lento; e o
  arquivo passou a rodar **isolado** nessa invocação, o que exige o aquecimento por kernel no
  `beforeEach` — sem ele o primeiro cenário estoura os 45 s do plugin.
- **Dívida que permanece**: o `kit:arte` publicar tudo o que encontra no diretório continua sendo
  uma armadilha aberta para qualquer screenshot futuro de CT-B. Registrada em
  `.ai/rules/testes-browser.md`; não corrigida aqui, mas hoje não há nenhuma captura duplicada para
  ela morder.
- **Dívida declarada e não corrigida**: o vazamento de painel da suíte de arte, e a
  `art/admin-papeis-import-export.png` publicada errada.

### Referências

- `app/Console/Commands/KitArte.php:79-100` — a publicação sem lista de permissão
- `tests/Browser/HubDeCardsTest.php` — o `beforeEach` que aquece só o `/infra`, e por quê
- `.ai/rules/testes-browser.md` — as duas rules que saíram disto
- "Notas de Implementação" e "Débito declarado" do `03-progresso.md` — as duas tentativas erradas
