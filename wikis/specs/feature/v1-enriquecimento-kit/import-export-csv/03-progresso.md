# Progresso — Import e Export de CSV

**Branch**: `feature/v1-enriquecimento-kit`
**Situação**: **implementado**. Wikis, código, testes, rule e documentação fechados.

## 1. Wikis

- [x] `00-requisito.md` — 26 cláusulas, 3 ambiguidades com premissa e "se negado"
- [x] `01-plano-acao.md` — 10 passos, cobertura por RQ, impacto, decisão resource a resource
- [x] `02-decisoes-arquiteturais.md` — 8 ADRs
- [x] `04-casos-de-teste.md` — 7 regras, 23 cenários, 18 mutantes, 4 lacunas declaradas
- [x] Validação do usuário — liberada com a instrução de finalizar o que faltava

## 2. Implementação

| Passo | Estado | Onde |
|---|---|---|
| 1. actions nativas num resource | ✅ | `ListProjetos::getHeaderActions()` |
| 2. as duas classes-base | ✅ | `app/Support/ImportExport/{ImportadorDoKit,ExportadorDoKit}.php` |
| 3. convenção nos resources da RQ-07 | ✅ | 4 ligados, 4 comentados, 1 fora — tabela abaixo |
| 4. permissões | ✅ | `config/filament-shield.php`, 14 policies, `PapeisSeeder` |
| 5. rastreabilidade | ✅ | `KitServiceProvider::configureRastroDeImportExport()` |
| 6. retenção | ✅ | `config/kit.php`, `routes/console.php`, `.env.example` |
| 7. rule | ✅ | `.ai/rules/filament.md` |
| 8. testes de componente | ✅ | 23 casos — tabela abaixo |
| 9. testes de browser | ✅ | `tests/BrowserTenancy/ImportExportDeProjetosTest.php` |
| 10. documentação e `kit:update` | ✅ | READMEs, `wikis/`, `CAMINHOS_DO_KIT` |

### Classes criadas

| Classe | Papel |
|---|---|
| `App\Support\ImportExport\ImportadorDoKit` | fronteira de organização no import, fail-closed, formula injection no CSV de falhas |
| `App\Support\ImportExport\ExportadorDoKit` | `preventFormulaInjection()` em toda coluna, por `getColumns()` **final** |
| `App\Filament\Imports\ProjetoImporter` | demo, com `tenant_id` por options |
| `App\Filament\Imports\AgenteIaImporter` | o caso sem tenant — prova que a fronteira não engessa single-tenant |
| `App\Filament\Exports\{Projeto,AgenteIa,Tenant,User,Convite,AiRun}Exporter` | 6, dois deles usados só por linha comentada (ADR-08) |

### RQ-07 — o estado de cada resource

| Painel | Resource | Import | Export |
|---|---|---|---|
| `/app` | `Projeto` | **ligado** | **ligado** |
| `/admin` | `AgenteIa` | **ligado** | **ligado** |
| `/admin` | `Tenant` | não | **ligado** |
| `/infra` | `AiRun` | não | **ligado** |
| `/admin` · `/app` | `User` | não | **comentado** |
| `/admin` · `/app` | `Convite` | não | **comentado** |
| `/admin` | `Role` | não | não |

## 3. Testes

| Arquivo | CT | Resultado |
|---|---|---|
| `tests/Tenancy/ImportExportTenancyTest.php` | CT-01…CT-04, CT-08, CT-09, CT-16, CT-19, CT-20 | 9 ✅ |
| `tests/Kit/ImportExportTest.php` | CT-05, CT-06, CT-11…CT-15, CT-17, CT-18, CT-20 | 14 ✅ |
| `tests/BrowserTenancy/ImportExportDeProjetosTest.php` | CT-B-01, CT-B-02 | 2 ✅ |

### Verificação final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [x] `vendor/bin/filacheck`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`
- [x] `composer test:browser`

## Desvios do Plano

| Desvio | Motivo |
|---|---|
| **`config/filament-shield.php` entrou em `CAMINHOS_DO_KIT`** | não estava no plano, e sem isso a feature **não chega a quem já instalou**: os métodos `import`/`export` em `policies.methods` são o que faz o `shield:generate` criar as permissions. Sem elas, a Action desaparece da tela sem erro nenhum |
| **`panel_user` perde import/export por PREFIXO, não por FQCN** | o plano previa acrescentar FQCN à subtração existente. Não serve: a subtração por FQCN é por RESOURCE, e o que se quer subtrair é uma AÇÃO de todo resource. Prefixo do segmento de ação é exato aqui (o Shield o monta de `policies.methods`), e faz Resource novo nascer fechado sem lista para manter. Virou ADR-06 |
| **`import` e `export` também em `single_parameter_methods`** | não estava no plano. Sem isso o Shield geraria `import(User $user, Model $record)` na policy, e a Action — que autoriza sem registro — estouraria `ArgumentCountError` |
| **Sem `ImportadorGenerico`/`ExportadorGenerico` por reflexão** | ADR-01: metade do valor da entrega está em colunas que **deliberadamente não entram**, e reflexão as inclui todas |
| **O `ExportadorDoKit` não carrega log nem poda**, ao contrário do passo 2 do plano | o rastro é de infraestrutura (listeners e model events, passo 5) e a poda é agendamento (passo 6). Na classe-base sobrariam duas responsabilidades sem relação com exportar |
| **A conclusão do export é CT de componente, não de browser** | dois botões chamados "Exportar" na mesma página; desambiguar no navegador custaria mais que o oráculo vale, e o oráculo do ARQUIVO gerado é mais forte no componente. Registrado no arquivo de browser |
| **Nada foi feito sobre audit por linha** | ADR-04: não é reproduzível hoje — o `Projeto` da demo não usa `AuditsFillables`. A decisão está escrita para quem ligar auditoria num model importável |

## Notas de Implementação

- **A assimetria import × export é a descoberta da feature.** O import perde o tenant porque
  resolve dentro do worker; o export o herda porque a query é montada no request e serializada com
  o `where` dentro. Escrever escopo dos dois lados teria produzido código morto num deles — e
  esconder que o lado do export depende de um detalhe do pacote (`getTableQueryForExport()`) que
  nada nosso protege. Daí CT-19 existir sem código nosso para cobrir.
- **Action do Filament não autoriza nada por default**, e o vendor diz isso por escrito
  (`Concerns/CanBeAuthorized.php:16-19`). Toda action da entrega carrega `->authorize()` explícito, e
  os CT medem a ACTION, não a policy: policy correta com `authorize()` esquecido deixaria a
  listagem exportável por quem só a abre.
- **`preventFormulaInjection()` são duas APIs distintas** — fluente por coluna no export, estática
  por importador no import (e no import ela afeta só o CSV de linhas que falharam, que o pacote
  devolve para download). Ligar só uma deixa metade aberta.
- **A `ExportAction` salva a linha de `exports` duas vezes** antes de qualquer job rodar. Sem o
  `wasChanged('completed_at')` no listener, cada export renderia três registros de "concluído".
- **Os `Import`/`Export` do Filament usam `Prunable` e não declaram `prunable()`** — `model:prune`
  daria `LogicException`. Daí a exclusão direta agendada, no mesmo padrão da poda da trilha de
  e-mails que já existia ali pelo mesmo motivo.
- **O gerador do Filament é uma fonte de regressão**, não só de conveniência: `-G` infere colunas
  do banco e devolve `token`, `request`/`response` e a FK do tenant. Três CT existem só para
  reprovar quem rodar `--force`.

## Lacunas que seguem abertas

| # | O quê | Estado |
|---|---|---|
| **L1** | import pela tela com upload de CSV de verdade | o modal usa upload temporário do Livewire; o cenário mediria o FilePond. O caminho do dado é coberto direto, no worker, onde o defeito mora |
| **L2** | `--dry-run` do import | o Filament não oferece, e a RQ não pediu. Linha errada vira `failed_import_rows` com CSV para download |
| **L3** | audit por linha | ADR-04 |
| **L4** | conteúdo do CSV exportado, célula por célula | o oráculo escolhido é a declaração das colunas, que é onde o defeito entra |

## Candidatos a Rule de Projeto

**Um, e foi gravado** como parte do passo 7, em `.ai/rules/filament.md`:

> **Resource novo decide import e export, e a decisão nasce escrita no arquivo.**
> Glob: `app/Filament/**`
> Cobre: as duas linhas do `getHeaderActions()`, o `->authorize()` obrigatório, as classes-base
> (e por que o import precisa de uma e o export precisa de outra), o reseed, a subtração do
> `panel_user`, as três classes de coluna que o gerador devolve e não podem ir, e a camada certa
> do teste.

É o que a RQ-20 pediu literalmente: *"coloque como uma rule que ao criar um resource, deve-se
perguntar se tera import e/ou export para ser criado junto"*.
