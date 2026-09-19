# Decisões Arquiteturais — Page header nas telas de registro

## Superfície Livewire

O que o **cliente** alcança nesta entrega, por origem. Tabela obrigatória pela `feature-wiki`:
cada linha é gatilho de cenário no `04`, e linha sem cenário é lacuna declarada.

O `HasPageHeader` é trait, e **método de trait conta como declarado pela classe que o usa** — logo os
nove métodos públicos dele viram ação chamável por `$wire.` em toda página que o aplica. O portão do
Livewire é `Utils::getPublicMethodsDefinedBySubClass($root)`, em
`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570-580`, que só
subtrai `render`.

| Ponto de entrada | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| `getPageHeaderRecord()` | `$wire.getPageHeaderRecord()` | o registro já passou por `authorizeAccess()` no `mount()` **e** a cada `hydrate()`; `password` e `remember_token` saem pelo `$hidden` | `HasPageHeader.php:getPageHeaderRecord:40-47`; `ViewRecord.php:authorizeAccess:80`; `ViewRecord.php:hydrate:85`; `User.php:$hidden:96-99` |
| `getPageHeaderSchemaClass()` | idem | devolve `class-string` ou `null` — não toca registro | `HasPageHeader.php:getPageHeaderSchemaClass:50-76` |
| `getPageHeaderOptions()` / `pageHeaderIsEnabled()` / `getPageHeaderComponent()` / `getHeader()` | idem | sem argumento do cliente; leem só config de painel e o schema | `HasPageHeader.php:78-131` |
| `headerSchema(Schema)` / `defaultHeaderSchema(Schema)` / `pageHeaderOptions(HeaderOptions)` | não alcançáveis na prática | parâmetro é objeto tipado; o Livewire não hidrata `Schema` nem `HeaderOptions` a partir de JSON | `HasPageHeader.php:20-37`, `:78-81` |
| `ViewRecord::$data` (`public ?array`, **sem `#[Locked]`**) | `$wire.set('data', …)` | pré-existente do Filament, não introduzido por esta entrega; o infolist é read-only e não grava | `ViewRecord.php:$data:39` |
| `$record` | — | **`#[Locked]`** | `Concerns/InteractsWithRecord.php:16` |
| `data-fph-options` no DOM | leitura do cliente | só modo/offset/breakpoint; nada de registro | `header.blade.php:16` |

**A medir no `04`** (não deduzir): o que `$wire.getPageHeaderRecord()` de fato devolve ao navegador.
O registro é autorizado, então não há escalada — a pergunta é se o retorno traz **coluna fora do
infolist** (`origem`, `aprovacao_pendente`, `deleted_at`). Se trouxer, é divulgação declarada, não
furo de fronteira, e a decisão vai para o `03`.

**O que esta entrega NÃO acrescenta à superfície**: nenhuma propriedade pública nova, nenhuma ação
que receba id do cliente, nenhum array de estado de framework consumido como índice ou parse.
Verificado por grep nos arquivos novos.

---

## ADR-01 — O plugin entra em `/admin` e `/app`, e fica fora do `/infra`

**Contexto.** As telas que a RQ-02 e a RQ-03 nomeiam vivem em dois painéis: `User` no `/admin` e no
`/app`, `Tenant` só no `/admin`. O `/infra` tem dois `ViewRecord` (`ViewAiRun`, `ViewRole`) e nenhum
está no escopo.

**Decisão.** Registrar `PageHeaderPlugin::make()` só em `AdminPanelProvider` e `AppPanelProvider`.

**Por quê.** O `register()` do plugin não é inerte: ele acrescenta um render hook `STYLES_AFTER` que
emite o `<link>` da CSS do pacote em **toda página do painel**
(`PageHeaderPlugin::register():60-68`), tenha ela header ou não. Registrar no `/infra` custaria uma
requisição de folha de estilo por página, em um painel sem um único consumidor.

**Alternativa recusada — registrar nos três, por simetria.** Os três providers compartilham
`ConfiguraFilamentGlobal`, e há um argumento real de que um *starter kit* deve entregar capacidade,
não só uso. Recusada porque a capacidade custa uma requisição por página e é reconquistada com **uma
linha** — que é exatamente o que a receita do passo 7 ensina. Simetria que cobra por página não é
simetria, é imposto.

**Uma instância por painel, nunca uma variável reusada.** `PageHeaderPlugin::make():33-36` é
`app(self::class)`, e o `PageHeaderServiceProvider` **não** declara binding de singleton
(`PageHeaderServiceProvider::boot():14-23` só faz `loadViewsFrom` e `FilamentAsset::register`) —
logo o container constrói instância nova a cada chamada e o estado (`$options`, `$resourceSchemas`)
é por painel. Uma instância compartilhada faria a configuração de um painel vazar para o outro, e o
modo de falhar seria silencioso.

**O hook é escopado.** `Filament::getCurrentPanel() === $panel` na closure
(`PageHeaderPlugin::register():64`). Não cai no defeito de render hook sem `scopes:`, que o kit já
mediu: sem escopo o hook cai no bucket `''` e renderiza em qualquer painel.

---

## ADR-02 — O avatar do header sai de `getFilamentAvatarUrl()`, nunca do provider do painel

**Contexto.** A v0.35.0 acabou de entregar `App\Support\AvatarDeIniciais` como
`defaultAvatarProvider` dos três painéis, justamente para parar de mandar o navegador de todo
usuário buscar `ui-avatars.com`. O reflexo natural, ao montar um header com avatar, é reusar esse
provider.

**Decisão.** O slot de identidade recebe `->avatar(fn (User $record) => $record->getFilamentAvatarUrl())`
com queda em `->initials(fn (User $record) => $record->name)`. O provider do painel **não** é
consultado.

**Por quê — e é medido, não deduzido.** `Header::getAvatarUrl():169-180` valida o esquema:

```php
$scheme = parse_url($url, PHP_URL_SCHEME);

if ($url === '' || preg_match('/[\x00-\x20]/', $url) || $scheme === false
    || ($scheme !== null && ! in_array(strtolower($scheme), ['http', 'https'], true))) {
    return null;
}
```

E `AvatarDeIniciais::get():65-69` devolve `'data:image/svg+xml;base64,'.base64_encode($svg)`.
Medido: `parse_url` devolve `"data"`, a condição é verdadeira, e o método devolve **`null`**.

O modo de falhar é o pior que existe: **nenhum erro**. O slot de identidade cai no `@elseif` de
`components/layout.blade.php:24` e some, ou mostra iniciais que o pacote calcula por conta própria
em `Header::getInitials():187-195` — com outra regra de recorte que a do kit. Teste de `assertOk()`
fica verde; `assertSee()` do nome fica verde, porque o nome está no heading.

`getFilamentAvatarUrl()` (`app/Models/User.php:857-861`) devolve `Storage::disk('public')->url(...)`,
que é `http://…/storage/…` — medido, aceito pelo validador — ou `null` quando não há foto, e aí a
queda para `->initials()` é o comportamento correto e desejado.

**Consequência que fica registrada**: o header de quem não tem foto **não** usa o SVG do
`AvatarDeIniciais`; usa as iniciais do pacote, com a tipografia e a cor do pacote. As duas telas
ficam visualmente próximas, não idênticas. Aceito: uniformizar exigiria ou publicar a CSS do
pacote (que o `filament:assets` sobrescreve a cada update) ou servir o SVG por rota HTTP, e nenhuma
das duas paga o ganho.

**Vira oráculo**, não só prosa: um caso afirma que o `data:` URI não chega ao header e que o URL de
`storage` chega. Sem ele, um refactor futuro que "unifique o avatar" reintroduz o branco silencioso.

---

## ADR-03 — Constraint `^2.1.5`, e o piso do Filament sobe junto

**Decisão.** `mortalkiller/filament-page-header: ^2.1.5` e `filament/filament: ^5.8.1`.

**Por que `^2.1.5` e não `^2.1`.** A v2.1.4 está quebrada no Filament 5.8.2: o `.fi-header-actions-ctn`
do 5.8.2 passou a trazer `sm:self-end`, que conflita com o layout do pacote, e a correção é
`align-self: auto` em `resources/css/page-header.css`. Um kit que aceitasse `2.1.4` entregaria as
ações do cabeçalho deslocadas, sem erro, para quem resolvesse a versão mais baixa.

**Por que caret e não pino.** A instrução permanente do mantenedor é não travar versão. `^2.1.5`
aceita toda a série 2 a partir do piso seguro e recusa a 3 — que é o comportamento que o dossiê da
rodada 2 pediu, dado que este repositório **já trocou a API em 24 h** (`HeaderLayout` → `Header`,
v1 → v2).

**O piso do Filament.** Decisão do solicitante, Adendo 2 do `00`. O pacote exige `^5.8.1`; manter
`^5.6` declarado deixaria a constraint descrevendo algo que o resolvedor não permite mais, e quem
estivesse na 5.7 receberia um conflito de resolução em vez da mensagem do kit. `^5.8.1` continua
caret na série 5: medido, aceita `5.8.2` e `5.99.99`, recusa `6.0.0` — `[CT-32]` de
`tests/Kit/PacotesRodada2Test.php:70` segue verde sem uma edição.

**O que isto reverte, declarado.** `CHANGELOG.md:38` registra que o `^5.6` era deliberado, *"para
não deixar de fora quem ainda está na 5.7"*. Essa intenção morre aqui, e o `CHANGELOG` desta versão
diz por quê — adotar o pacote já excluía essas instalações, só que em silêncio.

---

## ADR-04 — Três APIs do pacote nascem proibidas, com teste de arquitetura

**Decisão.** `app/` não usa `html: true`, `hideWhenCompact()` nem `retainSummaryWhenCompact()`.
Enforçado por caso de arquitetura, no molde de `tests/Kit/AderenciaAoBlueprintTest.php`.

**`html: true` — segurança.** `Header::heading():340` e `::description():345` aceitam
`bool $html = false`, e `Heading::getContent():26-31` troca `e($state)` por `$state` cru quando
ligado. O estado destes headers é `$record->name` e `$record->nome` — **entrada de usuário**. Não há
caso de uso no kit que precise de marcação no título, e a linha que a habilita é curta demais para
depender de alguém lembrar por que não.

**As duas outras — dívida a prazo.** `hideWhenCompact()` (`Header.php:385`) e
`retainSummaryWhenCompact()` (`Header.php:328`) estão marcadas `@deprecated` no próprio vendor, em
favor de `whenCompact()` com `HeaderPart`. O repositório **não tem CHANGELOG**, e já removeu API de
um major para o outro em 24 h. Escrever código novo sobre método já deprecado é dívida contratada
de propósito.

**Por que teste e não convenção escrita.** A wiki anterior mediu isso: *"quando a afirmação vale uma
citação, ela costuma valer um teste — e o teste não envelhece"*. O caso filtra comentário antes de
afirmar ausência, porque os arquivos do kit **citam** o que proíbem, e é lá que está o porquê —
`.ai/rules/testes.md`.

---

## ADR-05 — A documentação do caso "Resource com Relations" mora em três níveis

**Contexto.** A RQ-04 pede documentar o uso em *"tela de Resouce com Relations, onde exibimos um
resumo do registro e abas para os relacionamentos"*, e não diz onde.

**Decisão.** Três níveis, cada um com papel distinto:

| Nível | Onde | Papel |
|---|---|---|
| Receita | `wikis/receitas.md`, após `## RelationManager novo:200` | o passo a passo de "como fazer" |
| Decisão | este arquivo, ADR-01/02/04 | por que as escolhas são estas |
| Exemplo executável | `ViewTenant` | o caso **real**, não hipotético |

**Por que o `ViewTenant` é o exemplo.** É o único `ViewRecord` do projeto que renderiza
RelationManagers (`TenantResource::getRelations():129-134` → `UsersRelationManager`). Documentar o
padrão com um exemplo inventado, tendo o caso verdadeiro no repositório, é o que produz receita que
nunca foi executada.

**A combinação, com a API confirmada no vendor.** `hasCombinedRelationManagerTabsWithContent()`
(`vendor/filament/filament/src/Resources/Pages/Concerns/HasRelationManagers.php:96`) funde o
conteúdo da página e os relation managers numa barra de abas única, e `getContentTabLabel()`
(`ViewRecord.php:62`) nomeia a primeira. O header do pacote fica **acima** de todas as abas — são
regiões de DOM disjuntas, e o próprio pacote declara que trocar de aba não reinicia o estado
compacto (`docs/configuration.md:232`).

**Uma fronteira que a receita precisa dizer em voz alta**: `whenCompact()` decide **apresentação**,
nunca autorização. O vendor é explícito (`docs/configuration.md:229`: *"Compaction only changes
presentation of already-rendered content; it is not an authorization boundary"*). Um `summary()` com
contagens continua sendo dado renderizado; escondê-lo no modo compacto não o protege.

---

## ADR-06 — A atenção ao `kit:update` é documental, não código

**Contexto.** A RQ-12 pede *"atenção ao `kit:update` para levar essa melhoria"*.

**Decisão.** Nenhuma linha nova em `app/Console/Commands/KitUpdate.php`. A entrega é o passo manual
escrito em `docs/pt/comecar/atualizando-o-projeto.md` e no par `en`.

**Por quê — o mecanismo já cobre quase tudo.** Verificado:

- os caminhos que esta entrega toca já estão em `KitUpdate::CAMINHOS_DO_KIT`: `app/Filament:101`,
  `app/Providers:137`, `database/seeders:192`, `tests/Kit:219`, `wikis/receitas.md:260`,
  `wikis/pacotes-candidatos.md:256`;
- a dependência nova aparece **sozinha** no relatório: `relatarComposerJson():941-972` filtra o diff
  do `composer.json` por `/^[+-]\s{8,}"[^"]+"\s*:/`, que casa a linha do `require`, e imprime `warn`
  + `note` com o comando pronto;
- o `composer.json` nunca é aplicado, e isso é decisão registrada:
  `CAMINHOS_SO_RELATORIO:305-307` — *"aplicá-lo apagaria tudo que você instalou"*.

**O que de fato falta, e é a lacuna que a doc fecha.** `encerrar():1032-1039` imprime como próximos
passos `filament:assets`, `composer test:kit` e `composer test` — e **não cita os dois seeders**.
Quem atualiza e recebe a `ViewUser` precisa ressemear, e hoje nada diz isso.

**Alternativa recusada — acrescentar o reseed ao `encerrar()`.** Tentadora e errada por duas razões:
o `kit:update` roda sobre o projeto de terceiro, onde `db:seed` pode alcançar seeders de negócio que
não são do kit; e a lacuna não é desta feature — vale para toda entrega que traga Resource ou
permissão. Corrigi-la aqui seria resolver um problema geral dentro do escopo de um específico, que
é a classe de erro que o quality gate da rodada anterior nomeou: *"a remediação fechou o ponto
citado e não a classe"*. Fica registrada como dívida, com o motivo.

**Uma ressalva medida**: `relatarComposerJson():943` **retorna cedo** quando a origem é nula — quem
não tem `config('kit.version')` casando com uma tag do kit nunca vê o aviso da dependência. Também
dívida pré-existente, fora do mandato.

---

## ADR-07 — A `ViewUser` do `/app` nasce com `getViewAuthorizationResponse()`

**Contexto.** O `UserResource` do `/app` nega edição de quem governa a instalação em duas camadas: a
query (`getEloquentQuery():198`, via `User::queNaoGovernamAInstalacao()`) e a resposta
(`getEditAuthorizationResponse():215-232`, com `Response::deny()` e log). **Não existe**
`getViewAuthorizationResponse()`.

**Decisão.** Criar a sobrescrita, espelhando a de edição — mesmo canal de log, mesmo nível, mesmo
formato.

**Por que não basta a query.** Ela basta **hoje**, e é isso que torna a omissão perigosa: o alvo some
da listagem e o route binding devolve 404 antes de a policy ser consultada. Mas `.ai/rules/resources.md`
já registra o motivo de a segunda camada existir: *"a query é falha de um só ponto — uma action nova
que receba o model de fora da tabela passa por fora dela"*.

**E há o argumento decisivo, que é de assimetria.** A Edit tem duas camadas; entregar a View com uma
só a torna **mais permissiva que a Edit** para o mesmo alvo. Toda vez que uma tela de leitura fica
mais aberta que a de escrita sobre o mesmo registro, alguém acabou de criar a brecha sem perceber —
porque a intuição diz que ler é menos grave que escrever, e a intuição não sabe o que a ficha mostra.

**Teste**: chamando a resposta **direto**, com o alvo em mãos
(`UserResource::getViewAuthorizationResponse($alvo)->denied()`), nunca pela tela. Caso que passa
pela tela mede o recorte da query, não a barreira — ficaria verde com a sobrescrita inteira
removida.

---

## ADR-08 — O oráculo da rodada 2 muda de veredito; não sai da suíte

**Contexto.** `tests/Kit/PacotesRodada2Test.php` trava o resultado da rodada anterior. Duas metades
reprovam esta entrega **por desenho**: `[CT-33]:110` casa o veredito na mesma linha do nome Composer,
com o dataset declarando `'mortalkiller/filament-page-header' => 'ADIAR':37`; e `[CT-34]:148` assere
que **nenhum** dos dez entrou nas dependências.

**Decisão.** O dataset passa a declarar `ADOTAR` para este pacote; o `[CT-34]` passa a varrer os
**nove** que continuam fora; e um caso novo assere que este **está** no `require`.

**Por que não apagar os casos.** Eles protegem uma coisa que continua valendo: que a próxima
varredura não reavalie do zero dez pacotes já avaliados, e não adote por engano um recusado **por
motivo de segurança** — `matondojk/filament-avatar-picker` vaza foto de perfil entre organizações.
Apagar o arquivo porque um dos dez mudou de veredito jogaria fora a proteção dos outros nove.

**Por que não apenas remover a linha do dataset.** A asserção de ausência ficaria com um conjunto
menor e **ninguém saberia por quê**. Virando `ADOTAR` + presença no `require`, o registro continua
completo e a mudança de decisão fica auditável no próprio oráculo — que é o que o caso serve para
fazer.

**O que isso preserva**: o `[CT-34]` continua não-vácuo. Ele compara dois conjuntos não vazios (nove
nomes declarados × dezenas de dependências reais), que é exatamente a forma que o docblock dele
defende contra *"nenhuma dependência nova"* genérico.
