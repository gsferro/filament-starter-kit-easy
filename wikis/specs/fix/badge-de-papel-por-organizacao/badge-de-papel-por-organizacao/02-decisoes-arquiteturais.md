# Decisões Arquiteturais — O badge de papel por organização

## ADR-01: A exibição passa a depender da organização; o acesso, não

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

A wiki ancestral decidiu que `papelDoPainel()` consulta `papeisEmQualquerContexto()` — a relação sem `wherePivot(team_id)` —, e o teste `acha o papel mesmo fora do contexto de organização em que foi atribuído` afirma isso de propósito, com o argumento escrito:

> "a pergunta 'com que papel este usuário entra no /app' não depende de qual organização está aberta agora, do mesmo jeito que `canAccessPanel()` não depende."

O argumento está certo **para acesso**. Ele não vale para o **badge**, que afirma ao usuário como ele está agora — e num kit multi-organização "agora" inclui qual organização está aberta.

### Decisão

Separar as duas perguntas:

| Pergunta | Método | Depende da organização aberta? |
|---|---|---|
| "este usuário entra no `/app`?" | `canAccessPanel()`, `temPapelDoPainel()` | **não** |
| "com que papel ele está nesta organização?" | `papelDoPainel($painel, $contexto)` | **sim**, quando há organização |

`papelDoPainel()` ganha um `?int $contexto = null`. Com `null`, comportamento de hoje; com um id, filtra pelo pivot.

### Alternativas Consideradas

1. **Método novo, `papelDoPainelNaOrganizacao()`** — descartada: 90% do corpo igual, e todo chamador passaria a escolher entre dois métodos parecidos. É como nasce a divergência que o kit já pagou em `Convite::atribuirPapel()` (quatro cópias do mesmo guard, documentadas em `ContextoDePapeis`).
2. **Usar `ContextoDePapeis::em()` na view** — descartada: ele fixa o `PermissionRegistrar` global, faz `unsetRelation` nas duas pontas e restaura no `finally`. É a ferramenta certa para **escrita**; para uma leitura de badge seria trocar o estado global do request para responder uma pergunta, com o risco de contaminar o que renderiza depois — exatamente o que o docblock dele adverte.
3. **Usar a `roles()` do spatie na view** (que já filtra por team) — descartada, e é a armadilha que a ancestral documentou: no `/admin` e no `/infra` não há tenant na rota, o `team_id` corrente é o que o middleware deixou, e o badge **sumiria** nesses painéis. É o motivo de `papeisEmQualquerContexto()` existir.
4. **Ordenar o `->first()`** (por exemplo, pelo papel de maior privilégio) — descartada: escolheria um papel *plausível* em vez do papel *certo*, e continuaria mostrando o de outra organização.

### Consequências

- **Positivas**: o badge deixa de mentir para quem tem mais de uma organização; `/admin` e `/infra` não mudam, porque lá não há organização corrente e o parâmetro chega `null`.
- **Negativas**: quem tem papel do painel em outra organização e nenhum na ativa perde o badge. É o comportamento pedido, mas é uma ausência visível — documentada no README.
- **Riscos**: um chamador futuro que queira "o papel em qualquer organização" precisa lembrar de **não** passar contexto. Mitigado pelo default `null` ser exatamente esse caso.

### Referências

- `app/Models/User.php:398` — `papelDoPainel()`
- `app/Models/User.php` — `temPapelOnde()`, que já faz o `wherePivot`
- `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` — o caso que muda de oráculo
- `wikis/specs/main/perfil-e-acesso-ao-painel/` — a decisão que esta ADR refina

---

## ADR-02: Sem papel na organização ativa = sem badge

**Status**: Aceita
**Data**: 2026-09-01

### Contexto

Com o filtro por organização, aparece um estado que antes não existia: a pessoa tem papel do painel em **outra** organização, e nenhum na que está aberta. Ela entra no painel (o acesso não depende da organização — ADR-01), então a tela existe e o badge precisa decidir o que dizer.

### Decisão

Não renderizar nada, que é o que a view já faz quando `papelDoPainel()` devolve `null`.

### Alternativas Consideradas

1. **Mostrar o papel da outra organização** — descartada: é a mentira que este requisito veio corrigir, invertida. O badge afirma "é assim que você está aqui".
2. **Mostrar o papel da outra organização com marcação** ("`panel_user` na Acme") — descartada por YAGNI e por ruído: o badge tem uma linha no cabeçalho de um menu, e o caso é de borda.
3. **Badge com um traço ou "sem papel"** — descartada pelo argumento que a própria view já registra: papel ausente é estado normal do modelo, e um badge dizendo "—" afirmaria que falta alguma coisa.

### Consequências

- **Positivas**: nenhum código novo — o caminho de ausência já existe e já é testado.
- **Negativas**: a diferença entre "não tenho papel aqui" e "o badge quebrou" não é visível para quem olha. Mitigado pelo README.
- **Riscos**: se esse estado for comum em algum projeto derivado, a ausência vira dúvida recorrente. Aí o caminho é a alternativa 2, que esta ADR deixa nomeada.

### Referências

- `resources/views/filament/perfil-indicator.blade.php` — o comentário que decide não renderizar nada sem papel
