# Decisões Arquiteturais — Dock de ações rápidas

## ADR-01: Destino de outro painel é autorizado trocando painel, organização e contexto de papéis

**Status**: Aceita
**Data**: 2026-10-13
**Portões**: difícil de reverter ✅ (toda leitura de favorito passa por ela; trocar depois muda o resultado de autorização de todos os favoritos gravados) · surpreendente ✅ (avaliar `canAccess()` "direto" parece suficiente e libera tudo) · trade-off ✅ (custo de troca de contexto por favorito × correção da autorização)

### Contexto

RQ-15 pede que o favorito de um painel apareça no dock de qualquer painel, só enquanto o usuário puder abrir aquela tela. A autorização das telas do kit não é neutra quanto ao painel:

- `ExigePermissaoDaTela` herda o comportamento do `HasPageShield` do Shield: quando o painel corrente não é o da Page, a chave da permissão não resolve e `canAccess()` **libera** (fail-open, documentado no próprio trait e aceito lá porque em request real o `SetUpPanel` fixa o painel)
- No `/app` os papéis são escopados por organização (`team_id` do spatie, fixado por `DefinirTenantDePermissoes` a cada request). Avaliar a permissão de uma tela do `/app` estando no `/admin` consultaria o contexto global, não o da organização
- Resources de `/app` resolvem tenant pelo `Filament::getTenant()`

Chamar `$destino::canAccess()` a partir de outro painel daria a resposta do contexto errado — e, para Pages, a resposta seria `true` para todo mundo.

### Decisão

`DockDeAtalhos::podeAbrir()` avalia cada favorito **dentro do contexto do painel dele**: fixa o painel corrente (`Filament::setCurrentPanel`), a organização (`Filament::setTenant`, só no `/app`) e o `team_id` (`ContextoDePapeis::em`, com `CONTEXTO_GLOBAL` fora do `/app`), e restaura os três no `finally`. Antes de qualquer troca, exige: painel existente, destino presente em `getResources()`/`getPages()` daquele painel, `canAccessPanel()` e, no `/app`, `canAccessTenant()`. Qualquer exceção na avaliação nega o favorito.

### Alternativas Consideradas

1. **`canAccess()` sem troca de contexto** — descartada: fail-open das Pages e permissão do `/app` lida no contexto errado
2. **Favoritos só do painel corrente** (cada painel com a sua lista) — descartada pelo solicitante (Q5 aprovada): o valor da feature é atravessar painéis
3. **Gravar a URL e checar a rota pelo `Gate` no clique** — descartada: o usuário veria atalhos que respondem 403, contra RQ-19; e URL livre já foi recusada (RQ-11)

### Consequências

- **Positivas**: a resposta do dock é a mesma que a tela daria se o usuário a abrisse; nenhum destino de outro painel aparece por fail-open
- **Negativas**: até 10 trocas de contexto por montagem do dock (memo por request, D8); o catálogo do `/app` fica restrito à organização corrente (D7) para não multiplicar trocas por organização
- **Riscos**: contexto que não é restaurado contamina o resto do request (painel ou `team_id` errados) — mitigado pelo `finally` e por CT que confere painel, tenant e `team_id` depois da montagem

### Referências

- `App\Filament\Concerns\ExigePermissaoDaTela`, `App\Support\ContextoDePapeis`, `App\Http\Middleware\DefinirTenantDePermissoes`
- Wiki `permissoes-de-telas-e-acoes` (ADR-01, fail-open herdado)

---

## ADR-02: Favoritos e layout do usuário em colunas da tabela `users`, não em tabela própria

**Status**: Aceita
**Data**: 2026-10-13
**Portões**: difícil de reverter ✅ (migrar favoritos gravados para outra estrutura exige migração de dados) · surpreendente ✅ (lista de favoritos sem FK para organização nem para tela) · trade-off ✅ (zero query × integridade referencial)

### Contexto

RQ-22 pede que o dock não acrescente consulta ao banco por request para decidir se e como aparece. O dock monta em toda página autenticada dos painéis em que estiver ligado, e o componente reavalia as actions em toda requisição Livewire dele.

### Decisão

`users.dock_atalhos` (JSON, lista de chaves `{painel}|{FQCN}|{organização}`, no máximo 10) e `users.dock_layout` (texto curto, nulo = padrão da instalação). O usuário autenticado já está carregado no request; ler os favoritos custa zero query. Gravação só por `DockDeAtalhos::gravar()` (`forceFill`), fora do `$fillable`.

### Alternativas Consideradas

1. **Tabela `atalhos_do_usuario`** com FK para `users` e `tenants` — descartada: 1 query por request em toda página, model + factory + migration a mais, por uma lista de ≤ 10 itens pessoais
2. **Guardar no `localStorage`** como o pacote faz com a posição — descartada: o favorito não acompanharia o usuário entre navegadores, e a gravação não passaria pela validação do catálogo no servidor

### Consequências

- **Positivas**: zero query de favorito; rollback é derrubar duas colunas
- **Negativas**: organização excluída ou destino removido do código deixam entrada órfã no JSON — inofensiva, porque `podeAbrir()` a nega (RQ-19) e a próxima gravação a descarta
- **Riscos**: JSON adulterado/malformado — `DockDeAtalhos::podeAbrir()` nega chave que não casa o formato ou não está no painel

### Referências

- `App\Models\User` (regime de colunas fora do `$fillable`), `App\Support\CabecalhoDoPainel` (memo por request)

---

## Superfície Livewire

Tudo o que o navegador alcança. Pacote ainda não instalado: as linhas do vendor vêm da tag `v0.2.0` no GitHub e são re-varridas sobre `vendor/` depois do passo 1 e antes do step 9.

| Origem | Ponto | Tipo | O que o cliente controla | Fronteira / validação |
|---|---|---|---|---|
| vendor | `HoceineEl\QuickActionDock\Livewire\QuickActionDock::$routeName`, `$routeParameters`, `$path` | propriedade pública | nada — `#[Locked]` | o contexto da página vem do servidor no `mount()` |
| vendor | `QuickActionDock` — `mountAction`/`callMountedAction` (`InteractsWithActions`) | ação chamável | o **nome** da action e os dados do formulário | só actions registradas em `cacheDockActions()` existem, e só quando `shouldRender()` (dock ligado + `Usar:Dock`); `authorize()` é refeito na execução |
| projeto | action `atalho_{n}` | action de URL | nada além de clicar | só navega; URL gerada no servidor por `getUrl()` de destino já autorizado |
| projeto | action `adicionarPaginaAtual` | ação chamável | o momento da chamada | `->authorize('Usar:Dock')`; a página vem de `DockContext` (props `#[Locked]`); a chave precisa estar no catálogo recalculado; teto de 10 |
| projeto | action `gerenciarAtalhos` — campo `atalhos` | dado de formulário (lista de strings) | **qualquer** string de chave | `->authorize('Usar:Dock')`; `DockDeAtalhos::gravar()` recusa o envio inteiro se houver chave fora do catálogo (RQ-29); dedup (RQ-28); preserva favoritos ocultos (RQ-27); acima de 10, contando ocultos, recusa com aviso e nada grava (RQ-26) *(alterado em 2026-10-13: Adendo 3)*. Chave vira **índice** de array de catálogo e **nome de classe** — domínio inválido é cenário obrigatório |
| projeto | action `gerenciarAtalhos` — campo `layout` | dado de formulário (string) | qualquer string | `DockLayout::tryFrom()`; inválido vira `null` (padrão da instalação) |
| projeto | `App\Filament\Admin\Pages\ConfiguracoesDoKit` — `dock_paineis`, `dock_layout`, `dock_estilo` | estado de formulário da Page existente | qualquer valor | Page já exige a permissão da tela; `in()` com os ids de painel e os valores dos enums |
