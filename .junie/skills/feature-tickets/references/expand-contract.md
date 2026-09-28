> Referência da feature-tickets 1.0.0. Lida em: passo 4 (fatiar), quando a wiki tem
> `## Natureza da Wiki` = `refatoração` e a mudança é larga. Fonte única de: a sequência
> expand → migrate → contract em Laravel, com o exemplo de renomear coluna, os comandos de
> fechamento de cada ticket e as armadilhas do Eloquent.

# Expand–contract para refatoração larga

A obrigação e o motivo estão no `SKILL.md`, seção *Expand–Contract*. Aqui ficam o exemplo, os
comandos e as armadilhas.

## Quando usar, e quando não

- **Usar**: a mudança toca muitos leitores e escritores de um símbolo compartilhado — renomear
  coluna, retipar coluna (valor `float` → `integer` em centavos), renomear método público usado em
  muitos lugares — e **não cabe numa sessão**. Feita de uma vez, ela deixa a árvore quebrada no meio
  se a sessão acaba antes do último arquivo.
- **Não usar**: cabe numa sessão. Aí um `renameColumn` numa migration e a troca de todas as
  referências no mesmo ticket bastam — é o critério *"se cabe numa sessão, não fatie"*.
- **Não usar**: refatoração pequena, interna, já coberta por teste verde. Essa fica fora da esteira.

## A sequência

| Ticket | Nome do arquivo | Faz | Bloqueado por | Fecha com |
|---|---|---|---|---|
| **expand** | `NN-expand-{slug}.md` | cria o novo **ao lado** do antigo e liga a escrita dupla; nenhum leitor muda | prefactoring, se houver | CT da escrita dupla verde + suíte do CI verde |
| **migrate** (um por lote) | `NN-migrate-{slug}.md` | move um lote de leitores e escritores para o novo; o antigo continua existindo | o expand (direto) | CT do lote verde + suíte do CI verde |
| **contract** | `NN-contract-{slug}.md` | desliga a escrita dupla e remove o antigo | todos os migrate | CT de remoção verde + suíte do CI verde + busca pelo antigo revisada |

- Um lote é o que cabe numa sessão nova. O número de lotes é decisão de quem fatia, confirmada no
  quiz — nenhum tamanho de lote foi medido (hipótese a calibrar em uso). Sinal de retorno: a sessão
  de um lote compactou → o lote era grande demais, e os seguintes ficam menores.
- **Suíte do CI, não `--tia`, entre lotes**: a `feature-wiki` proíbe `--tia` no comando do CI, e
  um leitor esquecido do antigo só aparece na suíte completa.
- Os `RQ` da refatoração (*"a coluna passa a se chamar X"*, *"nenhum comportamento visível muda"*)
  ficam no **contract**, onde passam a ser verdade. Expand e migrate levam `**RQ cobertas**: —
  (transição; a cláusula fica verdadeira no contract)` e CT próprios, que provam a transição.
- Na `## Cobertura do Requisito` do `01`, cada `RQ` liga **todos** os passos que o tornam verdade. O
  contract lista em `**Passos do 01 envolvidos**` só o próprio passo; o `--check` aceita os demais nos
  expand e migrate que o bloqueiam, direta ou indiretamente:

  | RQ | Cláusula | Passo(s) que atende(m) | Observação |
  |----|----------|------------------------|------------|
  | RQ-01 | a coluna `nome` de `clientes` passa a se chamar `razao_social` | 1, 2, 3, 4 | expand → migrate → contract |
  | RQ-02 | nenhum comportamento visível muda | 2, 3 | os lotes |

  Tickets: `01-expand-razao-social` (passo 1) → `02-migrate-telas` (passo 2) e
  `03-migrate-api` (passo 3), cada um bloqueado pelo 01 → `04-contract-razao-social` (passo 4,
  `**Bloqueado por**: 02, 03`, `**RQ cobertas**: RQ-01, RQ-02`).

## Exemplo: renomear `clientes.nome` para `razao_social`

### Levantamento (antes de fatiar)

Listar todo leitor e escritor da coluna. É heurística: `nome` é palavra comum, e cada linha é
julgada antes de entrar num lote.

```bash
grep -rnE "['\"]nome['\"]|->nome\b" app/ database/ resources/ tests/ routes/ config/
```

Classificar cada ocorrência: **escritor Eloquent** (`create`, `fill`, `save`, formulário Filament),
**escritor fora do Eloquent** (`DB::table()`, `->update([...])` em query builder, `insert`,
`upsert`), **leitor** (Blade, `TextColumn::make('nome')`, API Resource, `orderBy('nome')`,
`where('nome', …)`, regra de validação, factory, seeder, teste). Índice e restrição de unicidade na
coluna também entram.

### Ticket expand

1. Migration: coluna nova, anulável, preenchida a partir da antiga.

   ```php
   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('clientes', function (Blueprint $table) {
               $table->string('razao_social')->nullable()->after('nome');
           });

           // tabela grande: preencher em lotes com chunkById() em vez de um UPDATE só
           DB::table('clientes')->whereNull('razao_social')->update(['razao_social' => DB::raw('nome')]);
       }

       public function down(): void
       {
           Schema::table('clientes', function (Blueprint $table) {
               $table->dropColumn('razao_social');
           });
       }
   };
   ```

   Índice ou `unique` que existe em `nome` ganha o equivalente em `razao_social` nesta migration.

2. Escrita dupla no model, até o contract:

   ```php
   protected static function booted(): void
   {
       // expand–contract (ticket NN-expand): escrita dupla até o ticket contract
       static::saving(function (Cliente $cliente): void {
           if ($cliente->isDirty('nome') && ! $cliente->isDirty('razao_social')) {
               $cliente->razao_social = $cliente->nome;
           } elseif ($cliente->isDirty('razao_social') && ! $cliente->isDirty('nome')) {
               $cliente->nome = $cliente->razao_social;
           }
       });
   }
   ```

   `razao_social` entra no `$fillable` (ou no que o model usa no lugar dele).

3. **Armadilha**: `update()` em massa pelo query builder, `DB::table()`, `insert()` e `upsert()`
   **não disparam** `saving`. Todo escritor fora do Eloquent achado no levantamento passa a gravar as
   duas colunas **neste** ticket — senão a coluna nova diverge em silêncio durante a migração.

4. CT do expand (no `04`): *gravar pelo caminho antigo preenche `razao_social`; gravar pelo novo
   preenche `nome`*.

### Tickets migrate (um por lote)

Cada lote troca `nome` por `razao_social` num grupo de leitores e escritores Eloquent — por exemplo,
lote 1: resource Filament e componentes Livewire de cliente; lote 2: notas, PDFs e API Resources;
lote 3: factories, seeders e testes.

CT de cada lote que **falha antes e passa depois**: gravar valores **diferentes** nas duas colunas
por `DB::table()` (sem passar pela escrita dupla) e afirmar que o leitor do lote mostra o da coluna
nova.

```php
it('[CT-02] lista de clientes mostra a razão social', function (): void {
    $id = Cliente::factory()->create()->id;
    DB::table('clientes')->where('id', $id)->update(['nome' => 'Antigo', 'razao_social' => 'Novo']);

    Livewire::test(ListaClientes::class)
        ->assertSee('Novo')
        ->assertDontSee('Antigo');
});
```

Antes do lote a tela lê `nome` e mostra `Antigo` (vermelho); depois lê `razao_social` (verde).

### Ticket contract

1. Tirar a escrita dupla do model e `nome` do `$fillable`.
2. Migration que remove a coluna antiga, com `down()` que a recria e preenche a partir da nova:

   ```php
   public function up(): void
   {
       Schema::table('clientes', function (Blueprint $table) {
           $table->dropColumn('nome');
       });
   }

   public function down(): void
   {
       Schema::table('clientes', function (Blueprint $table) {
           $table->string('nome')->nullable()->after('id');
       });

       DB::table('clientes')->update(['nome' => DB::raw('razao_social')]);
   }
   ```

3. CT do contract: `expect(Schema::hasColumn('clientes', 'nome'))->toBeFalse()` e o fluxo principal
   de cliente verde.
4. Fechamento: suíte do CI verde **e** o grep do levantamento rodado de novo, com cada linha restante
   julgada (sobra legítima: migrations antigas e a própria migration do contract).

Com deploy sem janela de manutenção, o contract só entra depois que nenhuma versão em produção lê a
coluna antiga — o código do lote precisa estar em produção antes da migration que remove a coluna.

## Retipar um símbolo compartilhado

Mesma sequência. Exemplo: método público `Pedido::total(): float` passa a `Pedido::totalCentavos(): int`.

- **expand**: `totalCentavos()` nasce; `total()` passa a delegar (`return $this->totalCentavos() / 100;`)
  e ganha `@deprecated`.
- **migrate**: cada lote troca os chamadores de `total()` por `totalCentavos()`.
- **contract**: remove `total()`, depois que `grep -rn -- '->total(' app/ resources/ tests/` não
  achar chamador (cada linha julgada: outro model pode ter um `total()` próprio). O PHPStan, se o
  projeto usa, acusa chamada a método inexistente.
