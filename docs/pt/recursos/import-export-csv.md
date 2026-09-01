---
title: "Import e export (CSV)"
parent: Recursos
grand_parent: Português
nav_order: 3
---

# Import e export (CSV)

O mecanismo é **nativo do Filament 5**: `ImportAction`, `ExportAction`, os jobs, o batch e a
notificação de conclusão com botão de download. As tabelas `imports`, `exports` e
`failed_import_rows` já vêm migradas, e o kit **não escreve wrapper nenhum** em volta disso. O que
ele acrescenta são duas classes base, uma permissão própria para cada lado e a decisão — resource
por resource — de ligar ou não.

![Fluxo de import e export no /app: a listagem de Projetos com os botões no cabeçalho, o modal de exportação com um campo por coluna e o modal de importação com o CSV de exemplo](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/fluxo-import-export.gif)

Os dois botões vivem no cabeçalho da listagem, ao lado do "Novo": nada de tela nova, nada de rota
própria — o que muda de resource para resource é só a permissão que cada um exige.

## `ImportadorDoKit`: a fronteira de organização que o pacote não entrega

`Importer::resolveRecord()` roda **dentro do worker**. Lá não há painel nem rota na sessão, então
`Filament::getTenant()` devolve `null` e o escopo global de `BelongsToTenant` vira **no-op** — o
`ImportCsv` restaura o `auth()->setUser()`, o **usuário**, e nada restaura o tenant. Duas
consequências, as duas silenciosas:

| Linha do CSV | Sem `App\Support\ImportExport\ImportadorDoKit` |
|---|---|
| com chave de **outra** organização | UPDATE no registro alheio, sem 403 e sem log |
| nova | nasce com `tenant_id` **nulo** — invisível para todo mundo, inclusive para quem importou |

A correção tem duas pontas. A **Action** captura o tenant no request, onde ele existe
(`->options(['tenant_id' => Filament::getTenant()?->getKey()])`), e a classe base o usa nas duas
pontas: filtra a resolução do registro e preenche a criação, no lugar do hook `creating` que ali
não tem contexto.

E ela **falha fechada**: tenancy ligada + model que usa `BelongsToTenant` + nenhum `tenant_id` nas
options = a linha é **recusada** com `RowImportFailedException` (vai para `failed_import_rows`, sai
no CSV de falhas da notificação) e o motivo é logado. Seguir sem escopo seria exatamente o defeito
que a classe existe para fechar.

## `ExportadorDoKit`: fórmula neutralizada em toda coluna

`preventFormulaInjection()` existe no Filament **por coluna**, e nasce **desligado**. Uma célula
começando em `=`, `+`, `-` ou `@` vira fórmula quando alguém abre o CSV no Excel — e o dado que a
preencheu veio de formulário de usuário. `App\Support\ImportExport\ExportadorDoKit` aplica a
neutralização a **toda** coluna que a subclasse declarar; por isso a subclasse declara `colunas()`,
e não `getColumns()`.

**O export não tem uma linha de código de tenant, e é isso que interessa entender.** A query dele
vem da tabela da tela (`getTableQueryForExport()`), montada no request, onde o escopo global já
aplicou o `where tenant_id = X`; ela é serializada **com** esse `where` dentro, e é isso que o job
executa. O isolamento do export é **herdado**; o do import é **construído** — o inverso exato. O
raciocínio completo está em
[`wikis/arquitetura.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/arquitetura.md#import-e-export-o-worker-perde-o-tenant-o-export-o-herda).

Os dois modais são os nativos do Filament — o kit não desenha tela nenhuma aqui:

| Importar | Exportar |
|---|---|
| [![Modal de importação de Projetos, com o link para baixar um CSV de exemplo e o campo de upload do arquivo](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/import-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/import-modal.png) | [![Modal de exportação de Projetos, com um campo por coluna do exporter: Nome, Organização, Criado em e Atualizado em, cada um com checkbox e rótulo editável](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/export-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/export-modal.png) |
| **Baixar um arquivo CSV de exemplo** monta o cabeçalho a partir das colunas do importer — é ali que se vê, na prática, que `tenant` não está entre elas | Um campo por coluna declarada em `colunas()`, com checkbox e rótulo editável: quem exporta escolhe o recorte e renomeia o cabeçalho, mas não acrescenta coluna que o exporter não declarou |

## Permissão própria, e ela não é opcional

`import` e `export` são **acréscimo do kit** aos 12 métodos default do Shield, em
`config/filament-shield.php` → `policies.methods` — e também em `single_parameter_methods`, porque
nenhum dos dois recebe registro (fora dessa lista o Shield geraria
`import(User $user, Model $record)` na policy, e a Action, que chama `Gate::authorize('import')` sem
registro, estouraria `ArgumentCountError`). Daí saem `Import:{Model}` e `Export:{Model}` para todo
resource.

[![Tela de edição de um papel no Filament Shield, com as checkboxes Import e Export ao lado de View Any, Create e Delete](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-papeis-import-export.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-papeis-import-export.png)

Na tela de papéis, `Import` e `Export` aparecem lado a lado com `View Any`, `Create` e `Delete` —
para **todo** resource, inclusive os que não ligaram as Actions. É o que permite conceder ou tirar
cada lado por papel, em `/admin` → Papéis, sem tocar em código.

Elas são necessárias porque **Action do Filament não consulta policy sozinha** — o próprio vendor
diz isso em `Actions/Concerns/CanBeAuthorized.php`: a autorização default é `null`, ou seja,
liberada. Por isso toda Action do kit carrega `->authorize('import')` ou `->authorize('export')`
explícito. Sem a linha, quem abre a listagem leva a listagem inteira embora.

> ⚠️ **Mexeu nesse config? Ressemeie.** A permission nova não existe no banco até o
> `shield:generate` rodar de novo, e o sintoma é a Action **desaparecer da tela sem erro nenhum**:
>
> ```bash
> php artisan db:seed --class=Database\Seeders\ShieldPermissionsSeeder
> php artisan db:seed --class=Database\Seeders\PapeisSeeder
> ```

## `panel_user` não nasce com nenhuma das duas

A subtração está em `PapeisSeeder::ehPermissaoDeImportOuExport()`, e casa por **prefixo de ação**
(`Import:` / `Export:`), não por lista de FQCN — de propósito: **resource novo nasce com as duas
fora do usuário comum sem ninguém precisar lembrar de acrescentá-lo a lista nenhuma.** O critério é
o que cada uma é de fato: import é **escrita em massa**; export **tira o dado da organização da
aplicação** num arquivo. Quem usa o negócio faz isso um registro por vez; quem move planilha é quem
opera a organização. O `admin_app` fica com as duas, porque recebe a matriz inteira do painel — e
conceder ao `panel_user` é um clique em `/admin` → Papéis, se fizer sentido no seu caso.

## Quem tem o quê hoje

| Painel | Resource | Import | Export | Motivo |
|---|---|---|---|---|
| `/app` | **Projeto** | ✅ | ✅ | resource de demonstração — é o exemplo de referência dos dois |
| `/admin` | **AgenteIa** | ✅ | ✅ | configuração, sem dado pessoal |
| `/admin` | **Tenant** | — | ✅ | criar organização por CSV pularia o provisionamento: papéis por tenant, primeiro administrador, identidade visual. Uma linha de planilha viraria uma organização que ninguém alcança |
| `/admin`, `/app` | **User** | — | 💤 comentado | a planilha sai com o e-mail de todo mundo que tem acesso; e import contornaria convite, verificação de e-mail e atribuição de papel — os três pilares do acesso no kit |
| `/admin`, `/app` | **Convite** | — | 💤 comentado | e-mail do convidado |
| `/admin` | **Role** | — | — | papel é identificador de código, não dado de planilha |
| `/infra` | **AiRun** | — | ✅ | ledger de custo; a pergunta que ele responde é "quanto gastamos" |

**Comentado** quer dizer que as duas linhas **já estão** no arquivo da Page, comentadas, com o aviso
do que ligar expõe — o exporter existe pronto, é descomentar uma linha. A decisão nasce **escrita**,
e não esquecida: é a convenção que `.ai/rules/filament.md` cobra de todo resource novo, porque
ausência silenciosa não é decisão — ninguém volta para reavaliar o que nunca foi escrito.

## As colunas que faltam de propósito

O gerador do Filament infere as colunas do banco, e três delas o kit tira na mão. Não as devolva:

| Classe | Coluna ausente | O que ela entregaria |
|---|---|---|
| `ConviteExporter` | `token`, `token_lembrete` | `Convite::aceitar()` valida o token e vincula o usuário à organização com o papel do convite: um CSV com essa coluna é uma **planilha de chaves de entrada** |
| `AiRunExporter` | `request`, `response` | prompt e resposta completos, de qualquer organização — e o `/infra` não tem tenant na rota |
| `ProjetoImporter` | `tenant` | o gerador cria `ImportColumn::make('tenant')->relationship()` para toda FK; aceitá-la deixaria o **CSV escolher a organização de destino** e tornaria a fronteira do `ImportadorDoKit` decorativa |

O gerador recoloca todas elas em `--force`. Quem guarda a ausência são os testes de
`tests/Kit/ImportExportTest.php`.

## Sem worker, nada acontece

Import e export do Filament são **jobs**. O kit nasce com `QUEUE_CONNECTION=database` no `.env`;
`composer dev` já sobe um worker, e em produção quem processa é o serviço `worker` do docker
compose. Com a fila parada, o arquivo é aceito, a linha entra em `imports`/`exports` e a notificação
de conclusão nunca chega — fila parada aparece no **Jobs Monitor** do `/infra`.

## Rastro: sem tabela nova

`imports` e `exports` já guardam quem pediu, qual importador, quantas linhas e quando terminou. O
que **não** está lá é justamente o que uma auditoria de vazamento pergunta — **de qual organização
saiu o arquivo** —, porque as duas tabelas são do pacote e não têm `tenant_id`. É o que
`KitServiceProvider::configureRastroDeImportExport()` acrescenta, no channel **`tenancy`**: o
assunto é cruzamento de organização.

Os dois lados usam gancho diferente porque o pacote é assimétrico: o import tem eventos de verdade
(`ImportStarted` / `ImportCompleted`), o export **não tem nenhum**, então o gancho é o próprio model
`Export` — `created` marca o pedido e o `completed_at` recém-preenchido marca a conclusão.

## Retenção: 30 dias, e a do export apaga o arquivo

| Chave | `.env` | Padrão |
|---|---|---|
| `kit.retencao.importacoes_em_dias` | `KIT_RETENCAO_IMPORTACOES_DIAS` | 30 |
| `kit.retencao.exportacoes_em_dias` | `KIT_RETENCAO_EXPORTACOES_DIAS` | 30 |

**30, e não os 14 das trilhas de exceção e e-mail**: o histórico de uma escrita em massa é o que
responde "quem escreveu isso na semana passada", e essa pergunta costuma chegar depois do fechamento
do mês. `failed_import_rows` cai por cascata; **a poda do export apaga o ARQUIVO**, não só a linha —
sem isso o disco cresce para sempre com CSV que ninguém mais consegue baixar, porque o link de
download é assinado e a linha que o autorizava já foi.

Os dois agendamentos estão em `routes/console.php` (02:20 e 02:30), como `Schedule::call` e não como
`model:prune`: os models `Import` e `Export` do Filament **usam a trait `Prunable` mas não declaram
`prunable()`**, então o comando estouraria `LogicException` — e não há como acrescentar o método sem
editar o `vendor/`. É o mesmo padrão já usado na poda da trilha de e-mails. Zero ou negativo desliga
aquela poda, e **quem executa é o agendador**: sem `php artisan schedule:work` (ou o serviço
`scheduler` do compose) o número no config é só intenção.

## Ligar num resource novo

```bash
php artisan make:filament-importer Produto -G
php artisan make:filament-exporter Produto -G
```

Troque o `extends Importer` / `extends Exporter` gerado pelas classes base do kit (no exporter,
renomeie `getColumns()` para `protected static function colunas()`), **apague a coluna `tenant`** do
importer, e acrescente as Actions no `getHeaderActions()` da Page de listagem:

```php
ImportAction::make()
    ->importer(ProdutoImporter::class)
    ->authorize('import')
    ->options(fn (): array => ['tenant_id' => Filament::getTenant()?->getKey()]),

ExportAction::make()
    ->exporter(ProdutoExporter::class)
    ->authorize('export'),
```

Depois **ressemeie os dois seeders** (`ShieldPermissionsSeeder`, então `PapeisSeeder`) e confira que
há worker no ar. A receita completa, inclusive o que fazer quando a decisão é *não* ligar, está em
[`wikis/receitas.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/receitas.md#ligar-importexport-num-resource).

