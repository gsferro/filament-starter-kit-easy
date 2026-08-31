# Decisões Arquiteturais — Abas de recorte nas listagens

> As decisões de **adoção** (pacote pago × alternativas gratuitas × nativo) estão nas ADRs do
> estudo ancestral, `wikis/specs/feat/estudo-advanced-tables/estudo-advanced-tables/02-decisoes-arquiteturais.md`.
> Aqui ficam só as decisões desta implementação.

## ADR-01: A regra de recorte é extraída, não reescrita na aba

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O recorte "pendentes de aprovação" já existe dentro de `AprovacaoDeCadastro::filtroDePendentes()`, e o de convites dentro do `TernaryFilter` de `ConvitesTable`. A aba precisa do mesmo recorte. Escrever `where('aprovacao_pendente', true)` de novo dentro do `getTabs()` de cada uma das duas páginas de usuários cria quatro cópias da mesma regra.

### Decisão

Extrair a closure para um método (`AprovacaoDeCadastro::recorteDePendentes()`, `ConvitesTable::pendentes()`/`aceitos()`) e referenciá-lo dos dois lados — filtro e aba.

### Alternativas Consideradas

1. **Repetir a query na aba** — descartada: a regra derivaria em silêncio no dia em que "pendente" mudar de definição (por exemplo, passar a excluir contas inativas). O filtro diria uma coisa e a aba outra, ambos verdes.
2. **Um scope no model (`User::scopePendentes()`)** — descartada por escopo: a regra é de tela, os dois consumidores são de tela, e um scope global convida terceiros a dependerem dela sem o contexto do Resource (que é onde o recorte de organização mora).

### Consequências

- **Positivas**: uma definição, dois consumidores. A extração roda e é testada **antes** de a aba existir, então uma regressão aponta para si mesma.
- **Negativas**: um método a mais em cada uma das duas classes.

### Referências

- `app/Filament/Concerns/AprovacaoDeCadastro.php:75`
- `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:60-66`

---

## ADR-02: O badge conta pela query do Resource, nunca pelo model

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O badge da aba "Pendentes" mostra quantos são. No painel `/app` a listagem é recortada por organização, e o recorte vive em `UserResource::getEloquentQuery()` — não no model, porque `User` não tem relação de posse com o tenant (a posse é a pivot `tenant_user`).

### Decisão

`static::getResource()::getEloquentQuery()` como base da contagem, em todas as abas de todas as páginas.

### Alternativas Consideradas

1. **`User::query()->where(...)->count()`** — descartada: no `/app` contaria a instalação inteira, e o número apareceria ao lado de uma tabela que mostra só a organização. É vazamento de contagem — não expõe o registro, mas informa quantos existem fora.

### Consequências

- **Positivas**: o badge nunca discorda da tabela que ele rotula.
- **Negativas**: uma `count()` por render da página.
- **Riscos**: em volume alto o custo cresce. Saída registrada pelo estudo: `->deferBadge()`, que adia a contagem para depois do primeiro paint.

### Referências

- `.ai/rules/filament.md` — "Resource de model sem relação de posse com o tenant"
- ADR-03 de `wikis/specs/main/admin-da-organizacao/`

---

## ADR-03: "Todos" é a primeira aba, e a aba ativa não persiste

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

`ListRecords::getTabs()` ativa a primeira chave quando não há `?tab=` na URL, e não guarda a aba escolhida na sessão (ao contrário das colunas, que o `HasColumnManager` persiste por padrão).

### Decisão

"Todos" é a primeira chave nas quatro listagens, e a não-persistência é aceita e **documentada** no README em vez de contornada.

### Alternativas Consideradas

1. **Abrir em "Pendentes"** — descartada: muda a tela para todo mundo que só queria a listagem, e transforma um recorte em estado default.
2. **Persistir a aba na sessão** — descartada: é código para um problema que ninguém relatou, e a URL com `?tab=` já resolve o caso real (linkar uma listagem já recortada de um card ou de uma notificação).

### Consequências

- **Positivas**: a tela de hoje continua sendo a tela de hoje para quem não clicar em nada.
- **Negativas**: quem usa "Pendentes" o dia inteiro reclica a cada visita — e a resposta é favoritar a URL com `?tab=pendentes`.

### Referências

- `vendor/filament/filament/src/Resources/Pages/ListRecords.php:39,54`
