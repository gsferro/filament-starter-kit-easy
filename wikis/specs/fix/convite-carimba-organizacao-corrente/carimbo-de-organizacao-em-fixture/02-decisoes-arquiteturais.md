# Decisões Arquiteturais — O carimbo de organização sobre fixture de teste

## ADR-01: A trava do vendor fica como está — o defeito era da leitura, não do código

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O achado chegou descrito como defeito: "um convite criado no contexto errado nasce na organização
errada". A investigação encontrou `Filament\Resources\Resource\Concerns\BelongsToTenant::observeTenancyModelCreation()`
(`:158-185`), que no boot do painel registra um `creating` fazendo `$relationship->associate($tenant)`
**sem verificar se a coluna já veio preenchida**.

Medido: sem o painel `app` bootado, o valor passado é respeitado e há **zero** listeners; com o
painel bootado e a Acme corrente, o `tenant_id` é **sempre** a Acme.

### Decisão

Não alterar o vendor, não alterar `$isScopedToTenant` e não contornar a trava em `app/`. A entrega
é de suíte e documentação.

### Alternativas Consideradas

1. **`$isScopedToTenant = false` no `ConviteResource` do `/app`** — descartada, e é a alternativa
   perigosa: desligaria junto o global scope de leitura e o carimbo de escrita, abrindo a criação de
   registro de outra organização a partir de dentro do `/app`. Trocaria uma inconveniência de teste
   por um furo de fronteira.
2. **Sobrescrever `getTenantOwnershipRelationship()` para respeitar coluna já preenchida** —
   descartada: reimplementa comportamento de vendor para atender a um caso que só existe em teste, e
   passa a divergir a cada upgrade do Filament.
3. **Aceitar o contorno pontual do CT-12** (`DB::table()->update()` no próprio caso) — descartada
   por não escalar: o próximo teste de fronteira que usar `Convite` ou `Projeto` repete a
   investigação inteira. Foi o que motivou a wiki.

### Consequências

- **Positivas**: a trava — que é fail-safe real em produção — continua de pé, e o `/admin` segue
  criando convite para qualquer organização (lá `getCurrentPanel() !== $panel` desliga o hook).
- **Negativas**: a suíte carrega uma correção que não seria necessária se o vendor checasse a
  coluna. Custo: quatro linhas num helper.
- **Riscos**: se o Filament passar a respeitar o valor preenchido, a correção do helper vira
  no-op silenciosa. Mitigado pelo caso do passo 4, que afirma o carimbo diretamente e fica vermelho
  na mudança.

### Referências

- `vendor/filament/filament/src/Resources/Resource/Concerns/BelongsToTenant.php:158-185`
- `app/Filament/App/Resources/Users/UserResource.php:73` — o Resource que declara `false`, e por quê
- `wikis/specs/feat/abas-nas-listagens/abas-nas-listagens/03-progresso.md` — Notas de Implementação

---

## ADR-02: A correção vive no helper, e um caso de teste mede o que ela contorna

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

`ofertaPara()` promete "convite nesta organização" e, com o painel bootado, entrega outra coisa. O
teste que depende disso falha **longe da causa**: a mensagem é "o registro da Globex apareceu na
listagem da Acme", que se lê como vazamento de dados — e foi lida assim antes das medições.

### Decisão

O helper garante o que promete. E, junto, entra um caso que afirma o carimbo do vendor
**diretamente**.

O par é a decisão: correção sozinha é curativo que esconde a causa; caso sozinho documenta um
problema que todo teste continua tendo de contornar à mão.

### Alternativas Consideradas

1. **Só o caso, sem corrigir o helper** — descartada: cada teste de fronteira continuaria com cinco
   linhas de contorno e um comentário.
2. **Só corrigir o helper** — descartada: no dia em que o Filament mudar, a correção vira no-op e
   ninguém percebe. É a mesma razão pela qual a wiki `travas-de-escalada-de-papeis` preferiu
   varredura a prosa.
3. **Helper novo, `ofertaPara` intacto** — descartada por `.ai/rules/testes.md`: "clone com outro
   nome troca um erro que estoura por duas funções idênticas que ninguém percebe". Já houve dois
   near-clones deste helper no kit, e unificá-los foi decisão anterior.

### Consequências

- **Positivas**: quem escreve teste de fronteira não precisa saber que o carimbo existe — e quem
  quiser saber acha o caso e a rule.
- **Negativas**: o helper deixa de ser uma chamada de factory transparente.
- **Riscos**: correção incondicional mascararia o comportamento; por isso ela é **condicional** —
  só age quando o gravado divergiu do pedido.

### Referências

- `tests/Pest.php:792` — `ofertaPara()`
- `.ai/rules/testes.md` — a regra contra clone de helper
