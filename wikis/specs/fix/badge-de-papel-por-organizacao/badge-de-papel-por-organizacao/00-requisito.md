# Requisito — O badge de papel precisa refletir a organização ativa

## Fonte

- **Origem**: mensagem do mantenedor no chat, a partir de uso real na instalação de verificação `D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT\v0223-tenancy`
- **Data**: 2026-09-01
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **alta** — o texto veio escrito, e descreve um comportamento observado numa instalação real, com o e-mail e os dois papéis nomeados

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - use blueprint
> - no projeto "D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT\v0223-tenancy", funcionou bem a questão do convite (email: g_santana26@yahoo.com.br) para 2 tenancy diferentes com perfis diferentes, porem, como em cada tenant ele tem um perfil diferente, quando eu olho no card do perfil, ele so exibe o 1º (que foi painel app) e no 2º foi admin
> - então, precisamos ajustar a exibição para refleti o perfil x exibição quando o tenant estiver ativo, já que ele pode ter mais de +1.

## O que a investigação encontrou antes de escrever o plano

**O card é o badge do cabeçalho do menu do usuário** — `resources/views/filament/perfil-indicator.blade.php`, incluído por `filament/user-menu-header.blade.php`. Ele pergunta o papel a `User::papelDoPainel($painel)`.

**A causa está em `app/Models/User.php:398`**:

```php
$papel = $this->papeisEmQualquerContexto()
    ->where('painel', $painel)
    ->where('guard_name', $this->getDefaultGuardName())
    ->first();
```

`papeisEmQualquerContexto()` é a relação **sem** o `wherePivot(team_id)` que o spatie aplica com `permission.teams` ligado. Com dois papéis do mesmo painel em organizações diferentes, o `->first()` devolve o primeiro que o banco entregar — sem ordenação declarada, na prática o de menor `id`. É exatamente o "só exibe o 1º" do relato.

**O mecanismo de filtrar por organização já existe no model**: `temPapelDoPainel(string $painel, ?int $contexto = null)` aceita o contexto e aplica `wherePivot`. `papelDoPainel()` é que não o usa.

**E há uma decisão anterior que este requisito reverte, em parte.** `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` tem um caso que afirma o comportamento atual **de propósito**:

> `it('acha o papel mesmo fora do contexto de organização em que foi atribuído')` — "a pergunta 'com que papel este usuário entra no /app' não depende de qual organização está aberta agora, do mesmo jeito que `canAccessPanel()` não depende."

O requisito novo diz o contrário para a **exibição**. Isso não é bug corrigido: é mudança de decisão, e o caso acima **muda de oráculo**. A distinção que a implementação precisa preservar: `canAccessPanel()` e `temPapelDoPainel()` continuam sem depender da organização aberta — quem passa a depender é só o badge.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O badge de papel exibe o papel que o usuário tem **na organização ativa**, e não o primeiro papel daquele painel | "precisamos ajustar a exibição para refleti o perfil x exibição quando o tenant estiver ativo" | funcional |
| RQ-02 | O comportamento vale para usuário com **mais de uma** organização, com papéis diferentes em cada | "já que ele pode ter mais de +1" | funcional |
| RQ-03 | Trocar de organização troca o badge, sem novo login | "quando o tenant estiver ativo" | funcional |
| RQ-04 | Nos painéis **sem** tenancy (`/admin`, `/infra`) o badge continua como está — não há organização ativa para consultar | derivado: o requisito fala de "tenant ativo", que só existe no painel de negócio | restrição |
| RQ-05 | O acesso ao painel **não** muda: quem entra continua entrando, com ou sem organização aberta | derivado do conflito com a decisão anterior (ver acima); o requisito pede ajuste de **exibição** | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-01 — o usuário tem papel do painel em OUTRA organização, mas nenhum na ativa. O badge some ou mostra o da outra?**
  - **Assumido**: **some**. O badge afirma "é assim que você está nesta organização"; mostrar o papel de outra é a mentira que o requisito pediu para corrigir, só que ao contrário. A view já trata ausência sem renderizar nada, então o caminho existe.
  - **Se negado**: cai um fallback ("mostra o de outra organização, marcado como tal"), o passo 2 do PRD muda e entram dois CTs.

- **RQ-04 — o `master_global` muda alguma coisa?**
  - **Assumido**: **não**. Ele atravessa por `isMasterGlobal()`, que corta antes de qualquer consulta de painel ou organização, e o badge dele é o mesmo nos três painéis. O caso que prova isso já existe na suíte de tenancy.

- **Texto do relato — "no 2º foi admin".** O papel `admin` é do painel `/admin` (`roles.painel = 'admin'`), não do `/app`; papéis de painel sem tenancy vivem no contexto global. Se o segundo convite realmente concedeu `admin`, o badge do `/app` não o mostraria em hipótese alguma — nem antes, nem depois desta mudança.
  - **Assumido**: o segundo papel é `admin_app` (o papel de quem administra **uma** organização, que existe só com tenancy), e "admin" no relato é o nome curto dele. É o único papel que produz o sintoma descrito.
  - **CONFIRMADO pelo solicitante em 2026-09-01**: era `admin_app`. A premissa vira fato, e o plano segue como escrito.

### Devolvidas pela derivação dos casos de teste

- **A premissa do "`/app` sem organização selecionada" descreve um estado INALCANÇÁVEL.** Medido:
  `AppPanelProvider:504` diz "sem `->tenantRegistration()` de propósito", e `:510` reescreve toda
  rota para `/app/{tenant}`. Não existe tela do painel de negócio sem organização na URL. A
  premissa fica **retirada** — não é que a resposta mudou, é que a pergunta não tem caso. O código
  continua tratando `null` (é o default do parâmetro, e é o que `/admin` e `/infra` usam), mas
  nenhum cenário do `/app` o exercita.

- **Papel do painel `app` gravado no contexto GLOBAL (`team_id = 0`) — vira badge em toda
  organização, ou em nenhuma?**
  - **Assumido**: em nenhuma. Com organização aberta, o filtro por pivot não casa com `team_id = 0`
    e o badge some — fail-closed, coerente com a ADR-02.
  - **Se negado**: é preciso um `orWhere` pelo contexto global, e o único matador de M19 muda.
  - **Como isso acontece na prática**: atribuição feita fora de `ContextoDePapeis::em()`. O kit já
    tem guarda para isso (`tests/Tenancy/PapeisPorOrganizacaoTest.php`), então é estado de defeito,
    não de operação normal.

- **`guard_name` é a terceira coluna da chave de leitura** (`painel`, `team_id`, `guard_name`) e
  nunca foi instanciada em cenário nenhum. O kit tem guard único (`web`) por contrato?
  - **Assumido**: sim — `config('auth.guards')` tem só `web`, e `RoleResource` monta o Select a
    partir dessa config. Enquanto valer, a coluna não é fonte de variação e não precisa de cenário.
  - **Se negado**: um projeto derivado com dois guards precisa de caso próprio.

- **Dois papéis do MESMO painel na MESMA organização — qual vence no badge?** Não assumido: é
  **lacuna declarada**, não premissa. O requisito não fala do caso, e inventar um vencedor
  (alfabético, maior privilégio, menor id) seria escrever requisito em vez de derivá-lo. Hoje o
  `first()` decide sem critério declarado. Perguntar quando aparecer.

- **`master_global` que acumula um papel de organização** e **organização da URL × da sessão**:
  as duas seguem `@premissa` no `04`, com o cenário marcado.

## Fora de Escopo (declarado)

- Mudar `canAccessPanel()`, `temPapelDoPainel()` ou `isMasterGlobal()` — são perguntas de **acesso**, e a decisão de que não dependem da organização aberta continua valendo (RQ-05).
- Exibir **todos** os papéis do usuário no badge. O requisito pede o papel da organização ativa, um por vez.
- Trocar o local do badge, o ícone ou o layout do cabeçalho do menu.
- O fluxo de convite em si, que o relato declara funcionando ("funcionou bem a questão do convite").
