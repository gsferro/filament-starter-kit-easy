# Requisito — O carimbo de organização do Filament sobre fixture de teste

## Fonte

- **Origem**: achado adjacente durante a implementação da wiki `abas-nas-listagens`, registrado nas Notas de Implementação dela e depois investigado até a causa a pedido do mantenedor.
- **Data**: 2026-08-31
- **Autor / solicitante**: mantenedor do kit ("abre wiki pro carimbo de tenant_id no convite")
- **Fidelidade**: alta — o comportamento foi **medido** três vezes (via factory, via `new Convite` + `save()`, e conferido no banco cru com `DB::table`), e a origem foi localizada por reflection sobre o listener registrado.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

Pedido do solicitante, verbatim:

> abre wiki pro carimbo de tenant_id no convite

Nota de implementação da wiki `abas-nas-listagens` que originou o pedido, verbatim:

> - **Achado adjacente, fora do escopo desta wiki**: `Convite::factory()->create(['tenant_id' => X])`
>   grava o id da organização **corrente**, descartando o valor passado — medido com `new Convite` +
>   `save()` e conferido no banco cru (`DB::table('convites')`). O único listener de
>   `eloquent.creating: App\Models\Convite` chega embrulhado pelo `Dispatcher::makeListener()` e a
>   origem não foi identificada dentro do orçamento desta feature. Não é defeito introduzido aqui —
>   o CT-12 contorna corrigindo a coluna no banco —, **mas merece wiki própria**: um convite criado
>   no contexto errado nasce na organização errada.

## O que a investigação encontrou — e o que ela derrubou da suposição original

A suposição do pedido ("um convite criado no contexto errado nasce na organização errada") supõe
**defeito**. A causa medida mostra o contrário: é **comportamento do vendor, e é fail-safe**.

`vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:158-185` —
`observeTenancyModelCreation()` registra, **no boot do painel**, um `creating` para o model de todo
Resource com `$isScopedToTenant`:

```php
$model::creating(function (Model $record) use ($panel): void {
    if (Filament::getCurrentPanel() !== $panel) { return; }
    $tenant = Filament::getTenant();
    if (! $tenant) { return; }
    $relationship = static::getTenantOwnershipRelationship($record);
    if ($relationship instanceof BelongsTo) { $relationship->associate($tenant); }
});
```

O `associate()` roda **incondicionalmente** — não verifica se a coluna já veio preenchida. Guardas
do vendor: o painel corrente tem de ser o painel do Resource, e tem de haver organização corrente.

**Medições que delimitam o comportamento**:

| Condição | `tenant_id` gravado | Listeners em `eloquent.creating: App\Models\Convite` |
|---|---|---|
| painel `app` **não** bootado | o valor passado (correto) | 0 |
| painel `app` bootado, organização corrente = Acme | **sempre Acme**, mesmo passando Globex | 1 |

**Em produção isso é uma trava, não um furo**: dentro do `/app` da Acme, um `role_id`/payload
forjado não cria registro em outra organização. E o `/admin` não é afetado — lá
`getCurrentPanel() !== $panel` desliga o hook, que é como o convite para qualquer organização
continua funcionando a partir da tela de administração.

**Resources do kit atingidos** (os que não declaram `$isScopedToTenant = false`):
`App\Resources\Convites\ConviteResource` (model `Convite`) e `App\Resources\Projetos\ProjetoResource`
(model `Projeto`). O `App\Resources\Users\UserResource` declara `false` (`:73`) e fica de fora.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O comportamento do carimbo fica **documentado** onde quem escreve teste de fronteira vai tropeçar nele | "merece wiki própria" | funcional |
| RQ-02 | A suíte tem um caminho **explícito** para criar fixture de outra organização com o painel bootado, sem depender de correção manual no banco | "o CT-12 contorna corrigindo a coluna no banco" | restrição |
| RQ-03 | O caminho da RQ-02 substitui o contorno pontual que ficou no CT-12 da wiki `abas-nas-listagens` | "o CT-12 contorna" | restrição |
| RQ-04 | Fica registrado que o comportamento é do **vendor** e **fail-safe**, para ninguém "corrigir" a trava achando que é defeito | derivado da investigação, contra a suposição do pedido | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-01 — onde documentar?** **Assumido**: rule de projeto em `.ai/rules/testes.md` (glob `tests/**`), que é onde um agente escrevendo teste de fronteira lê antes de escrever, mais esta wiki como registro do porquê. **Se negado**: só a wiki, e a próxima pessoa repete a investigação de uma hora.
- **RQ-02 — helper novo ou parâmetro no `ofertaPara()` existente?** **Assumido**: o helper existente ganha o comportamento correto, em vez de nascer um segundo com outro nome — `.ai/rules/testes.md` é explícita: "clone com outro nome troca um erro que estoura por duas funções idênticas que ninguém percebe". **Se negado**: helper separado, e os dois convivem.
- **Escopo — mexer no vendor ou no `ConviteResource`?** **Assumido**: não. A trava é desejável e o pedido não a questiona; mudar `$isScopedToTenant` abriria a criação de registro de outra organização de dentro do `/app`.

### Devolvidas pela derivação dos casos de teste

- **Q1 — sem organização pedida, com o painel bootado: o helper força `null` ou deixa o carimbo de pé?**
  **Assumido**: deixa de pé. A garantia do helper é sobre o que foi **pedido**; sem pedido não há o
  que garantir, e forçar `null` desligaria a trava do vendor pelo lado do teste. Marca a linha 6 do
  CT-01 como `@premissa`.
- **Q2 — organização pedida pelas duas portas (`$tenant` e `$atributos['tenant_id']`) com valores
  divergentes: qual vence?** **Assumido**: o array de atributos, porque é o que já vence no
  `create()` (`...$atributos` vem por último) — a correção honra a mesma precedência, senão
  "corrigiria" para o valor que o `create()` não usou. Nenhum dos 11 consumidores exercita a
  combinação; não há cenário escrito com valor chutado.
- **Q3 — a remoção do contorno do CT-12 deve ser enforçada por varredura?** **Assumido**: não,
  inspeção do diff basta. Varredura aqui seria enforço especulativo e colidiria com a regra de
  filtrar comentário — o docblock do próprio helper cita o padrão que ela proibiria. Lacuna
  declarada M15 no `04`.

## Fora de Escopo (declarado)

- Alterar o comportamento do Filament, o `$isScopedToTenant` dos Resources ou a trava em si.
- `Projeto` — o mesmo carimbo se aplica, mas nenhum teste do kit tropeçou nele até agora; o helper e a rule cobrem quem vier a tropeçar.
- Qualquer mudança em `app/` — esta entrega é de suíte e documentação.
