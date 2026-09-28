> Referência da feature-test-design 1.16.0. Lida em: passo 4 (percorrer a tabela inteira, uma vez
> por feature, e dar a cada gatilho presente o ID do cenário que o mata). Fonte única de: a tabela de
> gatilhos da taxonomia de defeito.

# Taxonomia de defeito — gatilho × cenário obrigatório (passo 4)

A obrigação de percorrer a tabela, as três respostas válidas e o critério de `não se aplica` estão
no `SKILL.md` §Passo 4; a coluna `Grupo` do checklist no `04` (a linha de `## Costuras de Teste` onde
vive cada CT), nas costuras que fecham o passo 2. O caso que originou as três linhas de superfície Livewire está em
`references/casos-medidos.md` §Passo 4.

| Gatilho na feature | Cenário obrigatório |
|---|---|
| rota/ação que recebe `{id}` de um recurso | **IDOR / autorização horizontal**: usuário A pede o recurso de B → 403/404. Dois usuários no setup |
| autorização declarada em policy/permission | **a ação disparada fora do caminho feliz** — não basta afirmar `can()`. Policy correta que o Resource nunca consulta passa em todo teste de `can()`. E **ao menos um** dos cenários dispara a ação **por fora do componente de UI** (gate de camada da regra — `SKILL.md` §Escolha de Camada em Laravel/Filament) |
| qualquer operação de escrita | **idempotência**: a mesma requisição duas vezes (duplo clique, retry, webhook redundante), com a assertion **no agregado afetado** |
| campo cujo domínio depende de outro campo | fronteira **por combinação** (tipo × valor), não fronteira do campo isolado |
| todo campo, em **todo ponto de entrada** | valor abaixo do mínimo, acima do máximo e no limite — **na gravação**, não só no uso |
| contador, saldo, estoque, limite de uso | **concorrência**: duas execuções simultâneas não ultrapassam o limite |
| efeito colateral com destinatário variável | **cardinalidade do destinatário (0 / 1 / N)** — e o cenário de **zero** nunca é citado como prova de não-efeito ou de atomicidade |
| campo opcional | **ausente ≠ `null` ≠ `""`** — três casos, com semântica declarada |
| listagem | **paginação**: 0, 1, limite, além do limite; e item inserido entre a página 1 e a 2 |
| ordenação por coluna | coluna inexistente (injeção via `orderBy`), coluna nullable, empate sem desempate determinístico |
| data/hora | **timezone do app × do banco × do usuário**; virada de meia-noite; DST; `date` comparado com `datetime` |
| texto livre | acento, emoji (4 bytes), string no limite do `varchar`, só espaços, espaços nas bordas |
| unicidade + `SoftDeletes` | criar → excluir → recriar com o mesmo valor único |
| entidade removível ou desativável | **o registro removido/desligado ainda funciona?** — a operação de escrita sobre ele, não a ausência dele na listagem. Premissa de mecanismo ("a exclusão é física") fixa **como** escrever o cenário, não dispensa escrevê-lo (`references/tecnicas-por-regra.md` §Premissa) |
| CRUD | ler/editar/excluir ID inexistente; excluir duas vezes; editar sem alterar nada |
| formulário/payload | **mass assignment**: enviar campo não previsto (`is_admin`, `user_id`, `status`) e provar que é ignorado |
| upload | 0 byte, extensão que mente sobre o conteúdo, acima do limite |
| valor monetário | inteiro em centavos ou `decimal`; **nunca `float`**; arredondamento na borda de centavo |
| **a feature cria página, widget ou componente Livewire** | **superfície do cliente**: para cada linha de `## Superfície Livewire` do `02`, um cenário que exercita o ponto de entrada **com valor fora do domínio** e um **com tipo errado**. Vale para o que o projeto escreve, para o que o framework publica e para o que o pacote expõe — a origem não muda a exposição |
| **valor de estado do framework que vira índice, `parse`, coluna ou operador** | `$filters`, `$pageFilters`, `$tableFilters`, `$tableSearch`, `$tableSortColumn` são **entrada de usuário não validada**. Um cenário por consumo: chave inexistente num array de rótulos, texto que não é data num `parse`, nome de coluna que não existe. **A página sanitiza e o widget recebe cru** — o cenário precisa entrar pelo widget |
| **método público de componente Livewire** | todo `public function` de Page/Widget é ação chamável por `$wire.`, e o retorno vai para o navegador: um cenário chamando-o com argumento **fora da lista fechada** |
| **cada entidade que a feature persiste** | uma linha de IDOR **e** uma de mass assignment **por tabela** — não por feature. Fechar a linha com o CT da tabela-pai é o falso ✅ mais caro do checklist |
| filtro de escopo (global scope, `where` por tenant/owner/discriminante) | **discriminante nulo**: a query fecha (nenhuma linha) ou abre (todas)? o cenário declara qual é o desejado. Atenção: `where('col', null)` vira `whereNull` e **abre** para os globais |
| cenário cujo `Então` é 4xx, 5xx ou redirect | **a saída**: para onde o usuário vai depois — ver *Todo estado de erro declara a saída* em `references/tecnicas-por-regra.md` |

> Esta tabela é **viva**: todo defeito que escapou para produção e gerou retrabalho deve virar
> uma linha nova no checklist de taxonomia do `04`. Taxonomia alimentada pelo histórico do próprio
> projeto é o item de maior alavancagem do pipeline inteiro.

A linha só chega a `.ai/rules/` se passar na definição *Vale virar rule* da `requirement-to-rule`
(step 12 da `feature-wiki`), que decide e grava; esta skill não grava rule (`SKILL.md` §Passo 4).
