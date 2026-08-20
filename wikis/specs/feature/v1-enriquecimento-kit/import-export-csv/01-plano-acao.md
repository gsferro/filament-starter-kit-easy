# Plano de Ação — Import e Export de CSV

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: nova
- **Toca infra compartilhada?**: **sim** — `config/filament-shield.php`, `PapeisSeeder`,
  `routes/console.php`, e uma convenção que passa a valer para todo resource futuro.

## O que a pesquisa mudou no plano

O requisito supunha que talvez fosse preciso construir o mecanismo (RQ-12). **Não é.** O Filament 5
entrega, pronto e instalado:

| Peça | Onde | Situação |
|---|---|---|
| `ImportAction` / `ExportAction` / `ExportBulkAction` | `Filament\Actions\` | prontas |
| Processamento em job | `Bus::batch()` no import, `Bus::chain(Bus::batch())` no export | pronto |
| Notificação de conclusão com **botão de download** | `Exports\Jobs\ExportCompletion:97` | pronto |
| Tabelas `imports`, `exports`, `failed_import_rows` | `database/migrations/2026_08_12_16495*` | **já migradas** |
| Disco do arquivo de export | guarda anti-disco-público em `Exports\Exporter.php:187-196` | seguro |
| Download | `DownloadExport.php:17-35` — exige sessão **e** dono/policy | seguro |

**RQ-08 a RQ-11 são atendidas pelo nativo, sem uma linha de cola.** Escrever camada própria por
cima seria exatamente o que a RQ-12 proíbe. Ver ADR-01.

O que o Filament **não** faz — e é onde mora esta entrega:

1. **Não escopa import por tenant.** `grep tenant` em `Imports/` e `Exports/` do vendor: zero.
2. **Não autoriza nada.** Nenhuma chamada a `authorize()` nas duas actions.
3. **Não integra ao log nem à retenção do kit.**

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01, RQ-02 | usar as actions nativas | 1 | sem wrapper — ADR-01 |
| RQ-03 | instalado no kit | — | **já está**: o pacote é `filament/actions`, e as migrations estão migradas |
| RQ-04, RQ-05 | ligar/desligar import e export, independentes | 3 | duas linhas separadas na Page |
| RQ-06 | nasce comentável | 3, 7 | o stub da rule entrega as duas linhas |
| RQ-07 | resources com listagem | 3 | **decisão por resource** — tabela abaixo |
| RQ-08, RQ-09 | job assíncrono | — | nativo; o passo 8 prova com teste |
| RQ-10, RQ-11 | notificação com botão de download | — | nativo; o passo 9 prova no navegador |
| RQ-12 | genérico sem duplicação, **se** não houver nativo | 2 | o nativo cobre; o que falta vira **duas classes-base**, não wrapper |
| RQ-13 | isolamento no export | 2, 8 | export já escopa por acidente feliz — ver ADR-02 |
| RQ-14 | isolamento no import | **2** | **é o núcleo da entrega** — ADR-02 |
| RQ-15, RQ-16 | permissão separada para exportar e importar | 4 | `Import:{Model}` e `Export:{Model}` |
| RQ-17 | rastreabilidade com retenção | 5, 6 | sem tabela nova — ADR-03 |
| RQ-18 | READMEs | 10 | |
| RQ-19 | wikis | 10 | |
| RQ-20 | rule ao criar resource | 7 | |
| RQ-21 | nesta branch | — | |
| RQ-22 | commits por escopo | — | um por passo, listados no fim |
| RQ-23 | implementar após validar as wikis | — | gate do usuário |
| RQ-24 | validar a aplicação | 9 | |
| RQ-25 | testes de browser | 9 | |
| RQ-26 | outros impactos | `## Impacto em Features Existentes` | |

## O núcleo: por que o import vaza

`vendor/filament/actions/src/Imports/Importer.php:157-166`, com aviso do próprio Filament:

```php
public function resolveRecord(): ?Model
{
    // Security: This method runs without policy checks.
    // Override to add authorization logic if needed.

    return static::getModel()::find($this->data[$keyColumnName]);
}
```

Esse `find()` roda **dentro do worker**. E `app/Traits/BelongsToTenant.php:64-70` só aplica
`where tenant_id = X` se `Filament::getTenant()` devolver um `Tenant`. Em fila não há painel nem
rota: devolve `null`, o scope vira no-op.

`Imports\Jobs\ImportCsv.php:72` faz `auth()->setUser($user)` — restaura o **usuário**, não o
**tenant**. Nada restaura o tenant.

Duas consequências, e as duas são silenciosas:

| Linha do CSV | O que acontece hoje |
|---|---|
| com ID de **outra** organização | UPDATE no registro alheio, sem 403, sem log |
| **nova** | nasce com `tenant_id` **nulo** — o hook `creating` não tem tenant para preencher |

## Superfície de UI

| Tela | Elemento | Novo? |
|---|---|---|
| Listagem de cada resource habilitado | botão **Importar** no header | sim |
| idem | botão **Exportar** no header | sim |
| modal do import | upload de CSV + mapeamento de colunas | nativo |
| sino de notificações | notificação de conclusão com **botão de download** | nativo |
| notificação do import | link do CSV de linhas que falharam | nativo |

## Autorização

Padrão de nome do Shield neste projeto — **não** é `view_any_projeto`. `config/filament-shield.php:119-124`
fixa `separator: ':'` e `case: 'pascal'`:

```
ViewAny:Projeto    Create:Projeto    DeleteAny:Projeto
```

Acrescento `Import` e `Export` a `policies.methods`. Como `policies.merge => true` (:143), eles
**somam** aos 12 defaults, gerando `Import:{Model}` e `Export:{Model}` para todo resource.

**Armadilha do kit, e ela é fácil de perder**: `PapeisSeeder::permissoesDeAdministracaoDoApp()`
(`PapeisSeeder.php:118-139`) lista por FQCN o que o `panel_user` **não** recebe. Permissão nova
que não entre nessa subtração é herdada em silêncio por todo usuário de painel.

## RQ-07 — decisão resource a resource

Ligar export em bloco criaria vazamento em massa na mesma branch que fecha outro. Decisão por
resource, com o motivo escrito:

| Painel | Resource | Import | Export | Por quê |
|---|---|---|---|---|
| `/app` | `Projeto` | **sim** | **sim** | é o resource de demonstração; é onde a feature se mostra |
| `/admin` | `AgenteIa` | sim | sim | configuração, sem dado pessoal |
| `/admin` | `Tenant` | não | **sim** | criar organização por CSV pula o fluxo de provisionamento |
| `/admin` | `User` | **não** | comentado | e-mail de todo mundo; ver abaixo |
| `/app` | `User` | não | comentado | idem, e ainda por organização |
| `/admin` · `/app` | `Convite` | não | comentado | carrega e-mail e token de aceite |
| `/admin` | `Role` | não | não | papel é identificador de código; CSV quebraria policy |
| `/infra` | `AiRun` | não | sim | ledger de custo, útil exportar; importar não faz sentido |

**"Comentado"** é o estado que a RQ-06 pede: as duas linhas nascem no arquivo, comentadas, com
o aviso do que se está ligando. Quem descomenta toma a decisão sabendo.

`User` não recebe import em hipótese nenhuma: criar usuário por CSV contorna convite, verificação
de e-mail e atribuição de papel — os três pilares do acesso no kit.

## Impacto em Features Existentes (RQ-26)

| Feature | Impacto | Tratamento |
|---|---|---|
| **Auditoria** (`AuditsFillables`) | import em massa gera **um registro de audit por linha**; CSV de 10 mil linhas inunda a tabela | `withoutAuditing()` no import + um registro de resumo — ADR-04 |
| **`kit:update`** | classes novas em `app/Support/` e `app/Filament/` precisam entrar em `CAMINHOS_DO_KIT` | passo 10; o `KitUpdateTest` reprova se esquecer |
| **Retenção** | `imports`/`exports` crescem sem teto, e `exports` guarda **arquivo em disco** | poda + apagar o arquivo — ADR-03 |
| **PHPStan level 7** | classes-base com generics de Eloquent são onde o level 7 morde | tipar `class-string<Model>` desde o início |
| **Tenancy desligada** | o mesmo código roda sem tenant; não pode exigir tenant onde não há | `Kit::tenancyAtiva()` guarda — RQ-13/14 pedem os dois cenários |
| **`filament:optimize` no Docker** | actions novas entram no índice de componentes | nada a fazer; roda no build |
| **Fila** | `.env` tem `QUEUE_CONNECTION=database`; sem worker, nada processa | o passo 10 documenta; `composer dev` já sobe worker |
| **Vazamento de anexo** (wiki irmã) | o export grava arquivo em disco — **mesma classe de risco** | verificado: `Exporter.php:187-196` tem guarda anti-disco-público, e o download é por controller autenticado. **Não repete o defeito** |

## Estrutura de Implementação

### 1. Habilitar as actions nativas num resource — prova de conceito

`ListProjetos::getHeaderActions()`. Confirma o caminho feliz antes de generalizar.

### 2. As duas classes-base — o que o Filament não dá

`app/Support/ImportExport/ImportadorDoKit.php` e `ExportadorDoKit.php`.

O `ImportadorDoKit` resolve o vazamento, e é a única peça de segurança da entrega:

- recebe `tenant_id` por `->options()`, capturado **no request**, onde o tenant existe
- sobrescreve `resolveRecord()` filtrando por esse `tenant_id`
- preenche `tenant_id` na criação, já que o hook `creating` não tem contexto
- **fail-closed**: tenancy ligada e sem `tenant_id` nas options → recusa e loga, no padrão do kit
- liga `preventFormulaInjection()`, que o Filament deixa desligado por padrão

O `ExportadorDoKit` carrega a poda do arquivo e o log; o escopo do export já vem da query da
tabela (ADR-02).

### 3. Aplicar a convenção nos resources da tabela da RQ-07

Ligados de fato onde a tabela diz "sim"; comentados onde diz "comentado".

### 4. Permissões

- `Import` e `Export` em `policies.methods` do `config/filament-shield.php`
- métodos nas policies do kit (o Shield gera só os 12 defaults)
- FQCN novos em `PapeisSeeder::permissoesDeAdministracaoDoApp()`
- reseed na ordem obrigatória de `.ai/rules/filament.md:33-40`

### 5. Rastreabilidade

Listeners nos eventos `ImportStarted`/`ImportCompleted` e no fim do export, gravando no channel
`tenancy` no padrão `[Classe@metodo] frase | chave: valor`. Sem tabela nova — ADR-03.

### 6. Retenção

`kit.retencao.importacoes_em_dias` e `exportacoes_em_dias`, agendados em `routes/console.php`
junto das duas podas que já existem. A poda do export **apaga o arquivo**, não só a linha.

### 7. Rule (RQ-20)

`.ai/rules/filament.md`: ao criar resource, decidir import e export, com o stub das duas linhas
comentadas e o aviso da subtração do `panel_user`.

### 8. Testes de componente

Kit e Tenancy. O caso que importa: **CSV com ID de outra organização não altera nada**.

### 9. Testes de browser (RQ-25)

Navegar, importar, exportar, ver a notificação e o botão de download.

### 10. Documentação (RQ-18, RQ-19) e `kit:update`

## Rollback

Remover as actions das Pages. As tabelas `imports`/`exports` já existiam antes desta wiki e não
são criadas por ela.

## Riscos

- **Fail-open silencioso se o `tenant_id` não chegar às options.** É o defeito que a entrega
  existe para fechar; se a guarda for fraca, a wiki não entregou nada. *Mitigação*: fail-closed
  com log, e um CT que prova a recusa.
- **Reseed esquecido.** Permissão nova sem reseed não aparece, e a action some sem erro.
  *Mitigação*: documentado no passo 4 e no `03-progresso.md`.
- **Sem worker, nada acontece.** *Mitigação*: documentar; `composer dev` já sobe um.

## Channel de Log da Feature

**`tenancy`**, não um channel novo. O que há de sensível a registrar é justamente cruzamento de
organização, que é o assunto desse channel. Ver ADR-03.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [ ] `vendor/bin/filacheck`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`
- [ ] `composer test:browser`

## Commits (RQ-22)

| # | Escopo |
|---|---|
| 1 | classes-base de import/export com escopo por tenant |
| 2 | permissões `Import`/`Export` no Shield e nos papéis |
| 3 | actions nos resources, conforme a tabela da RQ-07 |
| 4 | rastreabilidade e retenção |
| 5 | rule e documentação |
| 6 | testes de browser |
