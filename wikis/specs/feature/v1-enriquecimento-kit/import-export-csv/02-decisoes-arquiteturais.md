# Decisões Arquiteturais — Import e Export de CSV

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`

## ADR-01 — Usar o nativo do Filament, sem camada própria

**Contexto.** A RQ-12 é condicional: *"crie, se não houver uma forma generica nativa do filament"*.
Antes de decidir era preciso saber se há.

**Há.** Verificado no vendor, com file:line:

| Peça | Onde | Estado |
|---|---|---|
| `ImportAction` / `ExportAction` / `ExportBulkAction` | `Filament\Actions\` | prontas |
| Processamento em job | `Bus::batch()` no import (`ImportAction.php:264`), `Bus::chain(Bus::batch())` no export (`CanExportRecords.php:316`) | pronto |
| Notificação de conclusão com **botão de download** | `Exports\Jobs\ExportCompletion` | pronto |
| Tabelas `imports`, `exports`, `failed_import_rows` | `database/migrations/2026_08_12_16495*` | **já migradas** |
| Disco do arquivo de export | guarda em `Exports\Exporter.php:187-196` | seguro |
| Download | `Exports\Http\Controllers\DownloadExport.php:44-62` — exige sessão **e** policy ou posse | seguro |

**Decisão.** RQ-08 a RQ-11 são atendidas **pelo nativo, sem uma linha de cola**. Escrever camada
própria por cima seria exatamente o que a RQ-12 proíbe.

**Consequência.** O que o kit escreve é só o que o Filament não faz — e o Filament diz por escrito
o que não faz, em comentário no próprio código (`Imports/Importer.php:159-160`:
`// Security: This method runs without policy checks.`).

**Recusado.** Um `TrataImportExport` genérico com `ImportadorGenerico`/`ExportadorGenerico`
resolvendo colunas por reflexão do model. Rejeitado por dois motivos: reimplementaria o
`ImportColumn`/`ExportColumn` do Filament, e — pior — decidir por reflexão **quais** colunas entram
é o oposto do que esta feature precisa. Metade do valor entregue aqui está em colunas que
**deliberadamente não entram** (`token`, `request`, `tenant`), e reflexão as inclui todas.

---

## ADR-02 — O import constrói a fronteira; o export a herda

**Contexto.** RQ-13 e RQ-14 pedem isolamento nos dois lados. A tentação é escrever a mesma coisa
duas vezes.

**O mecanismo é assimétrico, e a assimetria decide o desenho.**

| | Import | Export |
|---|---|---|
| Onde a query nasce | `resolveRecord()`, **dentro do worker** | tabela da tela, **no request** |
| `Filament::getTenant()` ali | `null` | o tenant |
| Escopo de `BelongsToTenant` | **no-op** | aplicado |
| O que chega ao job | uma linha de CSV | a query **serializada, com o `where` dentro** |

`Exports\Jobs\ExportCsv:64` restaura o usuário do mesmo jeito que o import
(`auth()->setUser()`), e também não restaura tenant — mas não precisa: o `where tenant_id = X`
já está dentro do SQL serializado (`CanExportRecords.php:298`, via `EloquentSerializeFacade`).

**Decisão.**

- `ImportadorDoKit` **carrega a fronteira**: recebe `tenant_id` por `->options()`, escopa
  `resolveRecord()`, preenche a criação, e é **fail-closed** — organização necessária e ausente
  recusa a linha com `RowImportFailedException` e loga.
- `ExportadorDoKit` **não tem código de tenant**, e o comentário de classe explica por quê. Ele
  existe por outro motivo: ligar `preventFormulaInjection()` em toda coluna (ADR-05).

**Consequência.** Um caso de teste do export precisa afirmar sobre a **query da tela**, não sobre o
exportador — porque é lá que o isolamento vive. Está em
`it('a query de export da tela só alcança a própria organização')`.

**Risco aceito.** Se algum dia o Filament trocar `getTableQueryForExport()` por
`Model::query()`, o isolamento cai e nada no `ExportadorDoKit` acusaria. Daí o caso de teste
existir apesar de não haver código nosso para cobrir.

---

## ADR-03 — Rastro no channel `tenancy`, sem tabela nova

**Contexto.** A RQ-17 delegou o mecanismo: *"salvar no audits ou em um channel de log especifico
… ou em uma table com prune de 30 dias, o que voce julgar melhor"*.

**Decisão.** Log no channel **`tenancy`**, e o rastro estruturado nas tabelas que **já existem**.

**Por quê, das três opções:**

| Opção | Recusada porque |
|---|---|
| Tabela nova | `imports` e `exports` já guardam quem, o quê, quantas linhas e quando terminou. Tabela nova seria uma terceira cópia dos mesmos campos |
| `audits` | Import em massa gera **um registro de audit por linha** (o `AuditsFillables` do kit observa o model). CSV de 10 mil linhas inunda a tabela de auditoria e afoga a trilha que ela existe para guardar. Ver ADR-04 |
| Channel novo | O que há de sensível a registrar é **de qual organização o arquivo saiu**, que é o assunto do channel `tenancy`. Channel novo fragmentaria a investigação em dois arquivos |

**O que o log acrescenta às tabelas do pacote**: as duas são do Filament e **não têm
`tenant_id`** — verificado nas três migrations. Numa investigação de vazamento, a pergunta é
exatamente essa, e só o log responde.

**Ganchos, e eles são diferentes nos dois lados:**

| Lado | Gancho | Observação |
|---|---|---|
| Import | eventos `ImportStarted` e `ImportCompleted` | eventos de verdade do Filament |
| Export | model `Export`: `created` e `completed_at` recém-preenchido | **não existe evento de export** — `Exports/` não tem diretório `Events/` |

**Armadilha registrada**: a `ExportAction` salva a linha de `exports` **duas vezes** antes de
qualquer job rodar (uma para obter o id do diretório, outra com o `file_name`). Sem o
`wasChanged('completed_at')` cada export renderia três registros de "concluído".

---

## ADR-04 — Import não gera audit por linha

**Contexto.** O kit tem `AuditsFillables`, e ele observa os models de negócio.

**Decisão.** Nenhuma alteração no comportamento de auditoria **nesta entrega**, e o motivo é
honesto: o `Projeto` da demo não usa `AuditsFillables`, então o problema não é reproduzível hoje.
Registrar a decisão em vez de mexer no que não quebra.

**O que fica escrito para quem ligar auditoria num model importável**: envolva a importação em
`withoutAuditing()` e grave **um** registro de resumo. Sem isso, uma planilha de 10 mil linhas
produz 10 mil registros de audit, e a trilha de quem-mudou-o-quê fica inútil justamente no dia em
que alguém precisa dela.

**Revisável.** É a decisão mais frágil deste documento: ela aposta que quem ligar auditoria vai
ler esta linha. Se o kit passar a auditar `Projeto`, isto vira código.

---

## ADR-05 — Formula injection ligada por default

**Contexto.** `preventFormulaInjection()` existe no Filament e nasce **desligado**. E são duas APIs
distintas, o que é fácil de confundir:

| Onde | Assinatura | Default |
|---|---|---|
| `ExportColumn` (via `Exports\Concerns\CanFormatState:102`) | fluente, **por coluna** | `false` |
| `Importer` (`Imports/Importer.php:313`) | estático, por importador — afeta **só o CSV de falhas** | `false` |

**Decisão.** Ligada nos dois, por construção:

- `ExportadorDoKit::getColumns()` é **`final`** e aplica a neutralização a toda coluna devolvida
  por `colunas()`. Subclasse declara `colunas()`, não `getColumns()` — não há como esquecer.
- `ImportadorDoKit` redeclara `$shouldPreventFormulaInjection = true`.

**Por quê.** O CSV exportado é aberto no Excel, e célula começando em `=`, `+`, `-` ou `@` é
fórmula. O dado que a preencheu veio de formulário de usuário. E o CSV de **linhas que falharam** é
devolvido para download com o conteúdo original intacto — é o mesmo risco, no arquivo que o próprio
pacote entrega de volta.

**Consequência.** Número com sinal (`-5`, `+42`) **não** é afetado: a sanitização do Filament
distingue numérico de payload (`CanFormatState.php:133-161`).

---

## ADR-06 — `panel_user` não nasce com import nem export

**Contexto.** A RQ-15 e a RQ-16 pediram permissões separadas. Elas dizem o que **pode** ser
distinguido; não dizem quem recebe o quê por default.

**Decisão.** `Import:{Model}` e `Export:{Model}` são **subtraídas do `panel_user`**. `admin_app`
recebe (matriz do painel inteira). Concessão ao usuário comum é decisão de quem opera, feita na
tela de Papéis.

**Por quê.** Import é **escrita em massa**; export **tira o dado da organização da aplicação** num
arquivo que segue por e-mail. O usuário comum do `/app` usa o negócio um registro por vez. Herdar
as duas de `ViewAny` seria a mesma classe de furo que o ADR-06 da wiki `convite-em-massa` fechou —
permissão nova herdada em silêncio por registrar um Resource.

**A subtração casa por PREFIXO da ação, e é a única do kit que faz isso.** A subtração de
administração casa por FQCN de propósito, porque `str_contains($p, 'User')` pegaria um
`UserPreferenceResource` futuro por acidente. Aqui é diferente: o segmento de ação é montado
deterministicamente pelo Shield a partir de `policies.methods` + `permissions.separator`, então
`Import:` só aparece em permissão de import — para qualquer model, presente ou futuro. **É o
comportamento desejado**: Resource novo nasce com as duas fora do usuário comum sem ninguém
precisar lembrar de acrescentá-lo a lista nenhuma.

**Consequência.** Quem quiser o comportamento antigo remove um `reject()` — e o caso
`it('panel_user não nasce com permissão de import nem de export')` fica vermelho, o que é o ponto.

---

## ADR-07 — Retenção por exclusão direta, não por `model:prune`

**Contexto.** `Import` e `Export` do Filament usam a trait `Prunable`.

**Mas não declaram `prunable()`** — verificado no vendor. Passá-los ao `model:prune` daria
`LogicException`, e acrescentar o método exigiria editar `vendor/`.

**Decisão.** Duas closures agendadas em `routes/console.php`, no mesmo padrão da poda da trilha de
e-mails que já existia ali pelo mesmo motivo (`MailLog` também não implementa `Prunable`).

**30 dias**, e não 14 como as duas retenções existentes: histórico de escrita em massa é o que
responde *"quem escreveu isso na semana passada"*, e a pergunta costuma chegar depois do fechamento
do mês. Configurável em `kit.retencao.importacoes_em_dias` / `exportacoes_em_dias`; zero ou negativo
desliga.

**A poda do export apaga o ARQUIVO antes da linha.** Invertido, sobraria arquivo órfão em disco sem
nada que aponte para ele — e é arquivo com dado exportado da aplicação. `failed_import_rows` cai por
cascata (`import_id` é FK com `cascadeOnDelete`).

**Teto declarado.** Sem `chunk`: tabela com milhões de linhas faz um DELETE longo. É o mesmo teto
que a poda de e-mails já carrega escrito.

---

## ADR-08 — Decisão por resource, e a ausência é escrita

**Contexto.** A RQ-07 diz *"todas as models/Resources com list"*. Ligar export em bloco criaria
vazamento em massa na mesma branch que fecha outro.

**Decisão.** Três estados, resource a resource, com o motivo no arquivo da Page:

| Estado | Significa |
|---|---|
| **ligado** | as duas linhas ativas |
| **comentado** | as linhas existem no arquivo, comentadas, com o aviso do que descomentar expõe |
| **não** | nada no arquivo, e o motivo no PHPDoc do `getHeaderActions()` |

`User` não recebe import em hipótese nenhuma: criar conta por CSV contorna convite, verificação de
e-mail e atribuição de papel — os três pilares do acesso no kit.

**"Comentado" é o estado que a RQ-06 pede**, e ele carrega uma consequência: as classes
`UserExporter` e `ConviteExporter` **existem sem estar ligadas em lugar nenhum**. É deliberado —
descomentar tem de ser uma linha, não um `make:filament-exporter` seguido de limpar as colunas de
segredo na mão.
