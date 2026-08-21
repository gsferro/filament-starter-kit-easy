# Casos de Teste — Import e Export de CSV

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**, não do plano.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Fronteira de organização no import | 3 | 3 | **9** | completo |
| Matriz de permissão | 3 | 3 | **9** | completo |
| Colunas que não podem sair no arquivo | 2 | 3 | **6** | completo |
| Isolamento no export | 1 | 3 | 3 | médio |
| Processamento em job e notificação | 2 | 1 | 2 | mínimo |
| Retenção | 1 | 2 | 2 | mínimo |

- Técnicas: partição, tabela de decisão (modo × model), matriz de permissão, checklist de
  taxonomia (IDOR, mass assignment, injeção)
- Cenários: 23 · Regras: 7 · Mutantes previstos: 18

## Fatos de plataforma que decidem o desenho

Verificados no vendor **antes** de escrever oráculo. Cada um invalidaria cenários se ignorado.

| # | Fato | Evidência | Consequência para os CT |
|---|---|---|---|
| **F1** | `resolveRecord()` roda no worker, e nada restaura o tenant | `Imports/Jobs/ImportCsv.php:72-74` restaura só `auth()->setUser()`; zero ocorrências de `setTenant` em `vendor/filament/actions/src` | **cenário que passa pela tela mede o contexto, não a fronteira**. Os CT chamam o importador direto, com `Filament::setTenant(null)` |
| **F2** | `resolveRecord()` devolvendo `null` faz a linha ser **pulada em silêncio** | `Importer.php:71-73` | oráculo "não alterou nada" é ambíguo: pode ser fronteira funcionando ou linha ignorada. Precisa afirmar o que **nasceu** |
| **F3** | Action do Filament **não** consulta policy | `Concerns/CanBeAuthorized.php:16-21` — autorização default `null` | os CT de permissão medem a Action, não a policy: policy verde com `->authorize()` esquecido é o furo |
| **F4** | A query do export vem de `getTableQueryForExport()` e é **serializada com o `where`** | `CanExportRecords.php:191`, `:298` | o isolamento do export não tem código nosso; o oráculo é sobre a query da tela |
| **F5** | `Exporter::getFileDisk()` recusa disco público | `Exports/Exporter.php:187-196` | não há CT de vazamento do arquivo de export: o pacote já falha fechado, e `filament.default_filesystem_disk` é `local` aqui |
| **F6** | Não existe evento de export | `vendor/filament/actions/src/Exports/` sem `Events/` | o rastro do export é por model event, e a `ExportAction` salva a linha **duas vezes** antes de qualquer job |
| **F7** | `preventFormulaInjection()` são **duas APIs** e as duas nascem `false` | `Exports/Concerns/CanFormatState.php:27` (por coluna); `Imports/Importer.php:46` (estático) | um CT que cheque só uma das duas deixa metade aberta |
| **F8** | O CSV do export sai em **pedaços numerados** no diretório | `Exports/Jobs/ExportCsv.php:101` | oráculo de arquivo afirma sobre o diretório, não sobre `file_name` |

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | classes-base, importers, exporters, `config/filament-shield.php`, `PapeisSeeder`, `routes/console.php`, `config/kit.php` | CT-01…CT-23 |
| **F** | resolver linha, criar, atualizar, recusar, exportar, notificar, podar | todos |
| **D** | linha com chave de outra organização; linha com chave própria; linha nova; model sem tenant; tenancy desligada | CT-01…CT-07 |
| **I** | modal da tela; importador chamado direto; seeders; agendador | CT-01…CT-23 |
| **P** | fila `sync` no teste, `database` em produção | CT-16 |
| **O** | `master_global`, `admin_app`, `panel_user`, usuário sem permissão | CT-08…CT-12 |
| **T** | retenção em dias; `completed_at` | CT-17, CT-18 |

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a linha do CSV nunca alcança registro de outra organização | fronteira (completo) | RQ-14 | tabela de decisão | CT-01…CT-04 |
| **R2** — a fronteira não engessa o modo single-tenant | fronteira (completo) | RQ-13/14 (ambos os cenários) | partição | CT-05…CT-07 |
| **R3** — importar e exportar são permissões separadas, e nenhuma é herdada de ver | permissão (completo) | RQ-15, RQ-16 | matriz | CT-08…CT-12 |
| **R4** — o arquivo não leva coluna que não pode sair | colunas (completo) | RQ-13 (exposição) | checklist de taxonomia | CT-13…CT-15 |
| **R5** — processa em job e entrega o download | job (mínimo) | RQ-08…RQ-11 | — | CT-16 |
| **R6** — o export só alcança a própria organização | export (médio) | RQ-13 | — | CT-19 |
| **R7** — o histórico não cresce para sempre | retenção (mínimo) | RQ-17 | — | CT-17, CT-18 |

---

## Regra R1 — a linha nunca alcança registro de outra organização

> `RQ-14` · **completo** · tabela de decisão · `tests/Tenancy/ImportExportTenancyTest.php`

| # | Chave da linha | `tenant_id` nas options | Esperado | Cenário |
|---|---|---|---|---|
| 1 | colide com registro de **outra** organização | presente | registro alheio **intacto**, nasce um novo na minha | CT-01 |
| 2 | não colide | presente | nasce com a minha organização | CT-02 |
| 3 | qualquer | **ausente** | linha **recusada**, nada gravado | CT-03 |
| 4 | colide com registro da **própria** organização | presente | **atualiza**, não duplica | CT-04 |

```gherkin
# language: pt

Funcionalidade: Import de CSV com fronteira de organização

  Regra: a linha do CSV nunca alcança registro de outra organização

    Cenário: [CT-01] chave que colide com outra organização não altera o registro alheio
      Dado um projeto chamado "Contrato 2026" na organização Globex
      Quando o worker importa uma linha com esse mesmo nome para a organização Acme
      Então o projeto da Globex permanece na Globex
      E existe um projeto novo com esse nome na Acme

    Cenário: [CT-02] o registro criado pelo import nasce na organização correta
      Quando o worker importa uma linha nova para a organização Acme
      Então o registro criado pertence à Acme

    Cenário: [CT-03] linha sem organização nas options é recusada
      Quando o worker importa uma linha sem o tenant_id nas options
      Então a linha é recusada
      E nada é gravado

    Cenário: [CT-04] chave da própria organização atualiza em vez de duplicar
      Dado um projeto chamado "Contrato 2026" na organização Acme
      Quando o worker importa uma linha com esse nome para a Acme
      Então existe apenas um projeto com esse nome
      E é o mesmo registro de antes
```

**CT-01 tem dois `Então`, e os dois são necessários.** Por F2, "o registro alheio permanece
intacto" sozinho fica verde com a linha **pulada** — um `resolveRecord()` que devolvesse sempre
`null` passaria. O segundo `Então` fixa que a importação de fato aconteceu, do lado certo.

**CT-04 é o par positivo de CT-01.** Sem ele, uma fronteira que recusasse tudo passaria CT-01,
CT-02 e CT-03.

**CT-03 é o fail-closed.** É o cenário da Action escrita errada — `->options()` esquecido — e o
oráculo é duplo: recusa **e** nada gravado. Recusar e gravar seria pior que não recusar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `firstOrNew()` do gerador, sem `where` de tenant | **CT-01** |
| M2 | escopar a leitura e esquecer de preencher a criação | **CT-02** |
| M3 | fail-**open** quando falta `tenant_id` | **CT-03** |
| M4 | escopo que recusa tudo (`where tenant_id = null`) | **CT-04** |
| M5 | `resolveRecord()` devolvendo `null` em vez de registro novo | **CT-01** (2º `Então`) |
| M6 | preencher `tenant_id` também em registro existente | CT-04 |

---

## Regra R2 — a fronteira não engessa o modo single-tenant

> `RQ-13`/`RQ-14` ("ambos os cenários") · **completo** · partição · `tests/Kit/ImportExportTest.php`

| # | Tenancy | Model usa `BelongsToTenant` | Exige `tenant_id`? | Cenário |
|---|---|---|---|---|
| 1 | desligada | — | **não** | CT-05, CT-06 |
| 2 | ligada | não (`AgenteIa`) | **não** | coberto por CT-05 na suíte Kit |
| 3 | ligada | sim | **sim** | CT-03 |

```gherkin
  Regra: a fronteira não exige organização onde não há

    Cenário: [CT-05] importa sem exigir organização quando não há tenancy
      Dado que a tenancy está desligada
      Quando o sistema importa um agente de IA sem tenant_id nas options
      Então o agente é criado

    Cenário: [CT-06] reimportar a mesma chave atualiza em vez de duplicar
      Dado um agente de IA importado com o slug "revisor"
      Quando o sistema importa outra linha com o mesmo slug
      Então existe apenas um agente com esse slug
      E ele carrega os dados da segunda linha
```

**CT-05 é o caso que mata o fail-closed escrito largo.** Uma guarda que exigisse organização
*sempre* passaria toda a suíte `Tenancy` e mataria a feature no modo single-tenant — que é metade do
que a RQ-13/14 pediu.

**CT-06 fixa a resolução por `colunaDeResolucao()`**, e não por chave primária: CSV de planilha
raramente traz ID, e resolver por ID sem escopo é o caminho mais curto para o CT-01.

| # | Mutante | Cenário |
|---|---|---|
| M7 | exigir `tenant_id` sempre | **CT-05** |
| M8 | resolver por `id` em vez da coluna declarada | **CT-06** |

---

## Regra R3 — importar e exportar são permissões separadas

> `RQ-15`, `RQ-16` · **completo** · matriz

| Persona | `Import:Projeto` | `Export:Projeto` | Botão importar | Botão exportar | Cenário |
|---|---|---|---|---|---|
| `panel_user` (default) | não | não | escondido | escondido | CT-08, CT-11 |
| `panel_user` + `Export:Projeto` | não | sim | escondido | **visível** | CT-09 |
| `admin_app` | sim | sim | visível | visível | CT-10 (implícito nos CT de fluxo) |

```gherkin
  Regra: ver a listagem não é poder importar nem exportar

    Cenário: [CT-08] usuário sem as permissões não vê os dois botões
      Dado um usuário comum da organização Acme
      Quando ele abre a listagem de projetos
      Então o botão de importar está escondido
      E o botão de exportar está escondido

    Cenário: [CT-09] permissão de exportar não libera importar
      Dado um usuário comum com permissão de exportar projetos
      Quando ele abre a listagem de projetos
      Então o botão de exportar está visível
      E o botão de importar está escondido

    Cenário: [CT-11] o usuário comum não nasce com nenhuma das duas
      Dado os papéis semeados pelo kit
      Quando as permissões do papel de usuário comum são lidas
      Então nenhuma começa com "Import:"
      E nenhuma começa com "Export:"

    Cenário: [CT-12] as permissões existem no banco
      Dado as permissões geradas pelo Shield
      Então existem Import e Export para os resources que usam a feature
```

**CT-08 e CT-09 medem a ACTION, não a policy.** Por F3, a Action não consulta policy sozinha: uma
policy correta com `->authorize()` esquecido deixa a listagem exportável por quem só a abre, e um CT
de policy ficaria verde.

**CT-11 é o caso do ADR-06**, e ele é a rede de segurança da subtração por prefixo: Resource novo
nasce com as duas fora do usuário comum, e este cenário reprova se a subtração for removida.

**CT-12 parece redundante e não é.** Permissão que não existe no banco faz a Action **desaparecer da
tela sem erro nenhum** — o sintoma mais confuso desta feature, e o que acontece com quem esquece o
reseed.

| # | Mutante | Cenário |
|---|---|---|
| M9 | esquecer `->authorize()` numa das Actions | **CT-08** |
| M10 | usar a mesma permissão nas duas | **CT-09** |
| M11 | remover a subtração do `panel_user` | **CT-11** |
| M12 | não acrescentar os métodos em `policies.methods` | **CT-12** |

---

## Regra R4 — o arquivo não leva coluna que não pode sair

> `RQ-13` (exposição de dado) · **completo** · checklist de taxonomia

```gherkin
  Regra: coluna de segredo, de payload livre e de organização não entram no arquivo

    Cenário: [CT-13] o export de convites não leva o token de aceite
      Dado o exportador de convites
      Então ele não declara a coluna de token
      E não declara a coluna de token de lembrete

    Cenário: [CT-14] o export do ledger de IA não leva prompt nem resposta
      Dado o exportador de execuções de IA
      Então ele não declara a coluna de request
      E não declara a coluna de response

    Cenário: [CT-15] o import de projetos não aceita a organização como coluna
      Dado o importador de projetos
      Então ele não declara coluna de organização
```

**Os três são guardas contra o GERADOR, não contra o autor.**
`make:filament-exporter Model -G --force` infere colunas do banco e põe as três de volta, em
silêncio, num arquivo que já estava certo. É por isso que o oráculo é sobre as colunas declaradas e
não sobre o conteúdo de um CSV: o defeito entra por regeneração.

**CT-13 é o mais grave.** `Convite::aceitar()` valida o token e vincula o usuário à organização com
o papel do convite: um CSV com essa coluna é uma planilha de chaves de entrada.

**CT-15 é o que impede a fronteira de virar decorativa.** Com a coluna aceita, a linha escolhe o
destino e toda a R1 fica sem efeito prático.

```gherkin
  Regra: fórmula em célula de CSV é neutralizada

    Cenário: [CT-20] todo exportador do kit neutraliza fórmula em toda coluna
      Dado os exportadores do kit
      Então cada um estende a classe-base do kit
      E toda coluna de cada um neutraliza fórmula
```

**CT-20 varre TODO exportador**, não só o de demonstração: um exportador novo que estenda o
`Exporter` do Filament direto, pulando a classe-base, é o jeito de reabrir isso — e é este cenário
que reprova.

| # | Mutante | Cenário |
|---|---|---|
| M13 | regenerar o exporter com `--force` | **CT-13, CT-14** |
| M14 | aceitar `tenant` como coluna do CSV | **CT-15** |
| M15 | tornar `getColumns()` sobrescrevível na classe-base | **CT-20** |
| M16 | exportador novo estendendo `Exporter` direto | **CT-20** |

---

## Regra R5, R6 e R7

```gherkin
  Regra: processa em job e entrega o download

    Cenário: [CT-16] exportar pela tela processa e deixa o arquivo pronto
      Dado um projeto na organização Acme
      Quando o operador exporta pela tela
      Então ele é notificado
      E a exportação está concluída com uma linha
      E há arquivo no diretório da exportação

  Regra: o export só alcança a própria organização

    Cenário: [CT-19] a query de export da tela não vê a outra organização
      Dado um projeto na Globex e um na Acme
      Quando a query de export da listagem da Acme é lida
      Então ela devolve apenas o projeto da Acme

  Regra: o histórico não cresce para sempre

    Cenário: [CT-17] a poda do histórico de importações está agendada
    Cenário: [CT-18] a poda do histórico de exportações está agendada
```

**CT-16 afirma sobre o ARQUIVO, não sobre a notificação.** A notificação anexa a ação de download
apontando para a rota assinada; sem arquivo, o botão existe e não baixa nada — exatamente a falha
que `assertNotified()` sozinho não vê. Por F8, o oráculo é o **diretório**.

**CT-19 existe apesar de não haver código nosso** (F4, ADR-02): se o Filament trocar a origem da
query, nada no `ExportadorDoKit` acusaria.

**CT-17 e CT-18 afirmam sobre o AGENDAMENTO, não sobre a config.** Chave de retenção sem
agendamento é a falha silenciosa clássica: o número está escrito e nada o executa.

| # | Mutante | Cenário |
|---|---|---|
| M17 | export que completa sem gravar arquivo | **CT-16** |
| M18 | chave de retenção sem agendamento | **CT-17, CT-18** |

---

## CT-B — o que só o navegador prova

> `RQ-25` · `tests/BrowserTenancy/ImportExportDeProjetosTest.php`

| ID | Cenário | Por que só o navegador |
|---|---|---|
| **CT-B-01** | os dois botões aparecem no cabeçalho | `authorize()` mal escrita, importer com FQCN inexistente ou coluna com `->relationship()` quebrada derrubam a **tela**, não o `getColumns()` |
| **CT-B-02** | o modal do export abre com o mapeamento de colunas | o modal é schema do Filament montado sobre `getColumns()`; coluna mal declarada estoura no **render** |

**A conclusão do export ficou no componente (CT-16), não no navegador**, e o motivo está escrito no
arquivo: submeter no navegador exigiria desambiguar dois botões chamados "Exportar" na mesma página,
e o oráculo do arquivo gerado é mais forte no componente.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| **IDOR / autorização horizontal** | **CT-01** — é o núcleo da entrega |
| Autorização exercida na ação | **CT-08, CT-09** (a Action, não a policy) |
| Idempotência | **CT-04, CT-06** |
| Concorrência | não se aplica: import é batch de operador |
| Fronteira no ponto de entrada | **CT-03** (fail-closed) |
| Domínio condicionado | **CT-05** (tenancy ligada × desligada) |
| Ausente ≠ null ≠ vazio | **CT-03** (`tenant_id` ausente nas options) |
| **Mass assignment** | **CT-15** (`tenant` como coluna do CSV) |
| **Injeção (fórmula em planilha)** | **CT-20** |
| Vazamento de segredo | **CT-13** |
| Vazamento de payload livre | **CT-14** |
| Retenção de dado | **CT-17, CT-18** |
| Paginação / ordenação | não se aplica |
| Timezone | não se aplica |

## Lacunas declaradas

| # | O quê | Por que fica |
|---|---|---|
| **L1** | import pela TELA, com upload de CSV de verdade | O modal usa upload temporário do Livewire, e o cenário mediria o FilePond. O caminho do dado — `resolveRecord()` no worker — é coberto direto, onde o defeito mora. **CT-B-01** garante que a Action existe e a tela não quebra |
| **L2** | `--dry-run` do import | Não existe: o Filament não oferece, e a RQ não pediu. Linha errada vira `failed_import_rows` com CSV para download |
| **L3** | audit por linha no import | ADR-04: não reproduzível hoje (o `Projeto` da demo não usa `AuditsFillables`). Vira CT no dia em que o kit auditar um model importável |
| **L4** | conteúdo do CSV exportado (célula por célula) | O oráculo escolhido é a declaração das colunas (CT-13, CT-14, CT-20), que é onde o defeito entra — por regeneração. Ler o CSV gerado testaria o escritor do Filament |
