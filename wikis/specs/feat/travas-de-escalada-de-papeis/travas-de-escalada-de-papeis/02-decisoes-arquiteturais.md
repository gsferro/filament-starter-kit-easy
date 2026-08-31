# Decisões Arquiteturais — Travas de escalada na tela de papéis e no login social

## ADR-01: A guarda do papel super-admin vive na policy, não em cada Action

**Status**: Aceita
**Data**: 2026-08-30

### Contexto

`RoleResource` (tela do Shield) tem `EditAction`, `DeleteAction` e `DeleteBulkAction` (`RoleResource.php:255-263`). Qualquer uma delas sobre o registro do papel `master_global` é escalada: renomear troca quem é o administrador da instalação, excluir derruba o `Gate::before` de todo mundo. O papel `admin` tem `Update:Role` e `Delete:Role` porque `PapeisSeeder:58-59` lhe dá a matriz inteira do painel `/admin`.

### Decisão

A guarda entra em `RolePolicy::update()`, `delete()`, `forceDelete()` e `restore()` — que já recebem `Role $role` e hoje ignoram o registro. Um método novo em `AdministradorDaInstalacao` responde `papelEditavelPor(Role, ?Authenticatable)`.

### Alternativas Consideradas

1. **`->visible()`/`->hidden()` nas Actions** — descartada: é UX, não fronteira. O `mountAction` do Livewire alcança ação escondida, e a auditoria anterior desta mesma família já registrou "opção não é fronteira".
2. **`getUpdateAuthorizationResponse()`/`getDeleteAuthorizationResponse()` no Resource** — foi o que o relatório sugeriu, e funciona; descartada por ser dois métodos por operação em um Resource, contra um método por operação na policy que **todos** os caminhos consultam (incluindo bulk e qualquer Action futura).
3. **Guarda no model (`Role::booted()` com `saving`/`deleting`)** — descartada como trava primária: a exceção estoura como 500 na tela, sem mensagem utilizável. Continua sendo a rede que faltaria se alguém criar um caminho fora do Filament; não foi implementada porque hoje não existe esse caminho (`ponytail:` YAGNI — vira necessária no dia em que houver comando artisan ou API que edite papel).

### Consequências

- **Positivas**: uma trava, um arquivo, consultada por toda a superfície do Filament. `->strictAuthorization()` (ligado nos três painéis) garante que a ausência de método **lança** em vez de liberar.
- **Negativas**: a policy passa a conhecer `AdministradorDaInstalacao` — acoplamento aceitável, já que é a classe que define quem é o administrador da instalação.
- **Riscos**: trancar o próprio `master_global` fora. Mitigado porque a guarda pergunta `isMasterGlobal()` e o `Gate::before` do Shield (`FilamentShieldServiceProvider.php:55-58`) entrega tudo a ele antes da policy.

### Referências

- `app/Policies/RolePolicy.php:30,37`
- `app/Providers/KitServiceProvider.php:197` (`Gate::before` do kit)
- `app/Models/User.php:356` (`isMasterGlobal()` resolve por nome de config)

---

## ADR-02: A cunhagem de papel de outro painel se fecha na atribuição, não na tela

**Status**: Aceita
**Data**: 2026-08-30

### Contexto

O editor de papéis expõe o Select `painel` com todos os painéis (`RoleResource.php:155`) e a matriz de permissões dos três (`:289-309`, via `Paineis::resources()`). Um `admin` cunha um papel com `painel = infra` e as 140 permissions de infra e se atribui — a trava existente só recusa o papel pelo **nome** `master_global`. O `/infra` dá trilha de auditoria, IPs, exceções e o ledger de IA de todas as organizações.

### Decisão

A trava vai nos dois métodos de `AdministradorDaInstalacao` que os três caminhos de concessão já consomem — `recortarConcessao()` (UX) e `regraDeConcessao()` (escrita). **Nenhuma tela muda.**

O critério é o corrigido em Q1 do `00-requisito.md`: quem não é `master_global` concede papel **sem painel**, papel do **painel de negócio** (o `->default()`, hoje `/app`) e papel de painel que ele **próprio acessa** — nunca papel de painel que governa a instalação e ao qual ele não tem acesso.

A letra da decisão original ("só o painel que ele acessa") foi descartada na derivação dos casos de teste: `User::canAccessPanel()` exige `temPapelDoPainel()`, e o papel `admin` tem `painel = 'admin'` — ele **não** acessa o `/app`. Aplicada ao pé da letra, a trava tiraria do `admin` a concessão de `panel_user`/`admin_app`, que é a mesma perda de funcionalidade usada abaixo para recusar a alternativa 1. O critério por painel default preserva o fluxo e ainda fecha uma escalada que a letra original deixava passar: `admin_app` deixa de conceder papel de `/admin`.

O critério usa o painel **default** e não `Panel::hasTenancy()` porque em instalação single-tenant o `/app` não tem tenancy, e o `admin` continuaria precisando conceder `panel_user`.

### Alternativas Consideradas

1. **Recortar `Paineis::resources()` e o Select `painel` pelo painel do operador**, com validação no `mutateFormDataBefore{Create,Save}` — foi a correção do relatório. Descartada por decisão do solicitante: fecha a cunhagem, mas tira do `admin` a administração dos papéis do `/app`, que é uso legítimo do kit hoje (o convite para organização escolhe papel de `/app` a partir do `/admin`).
2. **As duas juntas** (defesa em profundidade) — descartada pelo mesmo motivo da 1: o recorte da tela custa a funcionalidade, e a trava de atribuição sozinha já fecha a escalada. Quem cunha um papel de `infra` mas não consegue atribuí-lo a ninguém não escalou nada.
3. **Bloquear a criação de papel com painel que o operador não acessa** — descartada: é a mesma perda de funcionalidade da alternativa 1, com o agravante de a mensagem de erro aparecer na criação e não na concessão, onde o risco está.

### Consequências

- **Positivas**: diff de um arquivo; os três caminhos (ficha de usuário, convite individual, convite em massa) herdam a trava sem tocar em tela.
- **Negativas**: um papel de `/infra` cunhado por um `admin` continua existindo no banco, órfão. É lixo, não escalada — e some com `Delete:Role`, que ele tem.
- **Riscos**: a definição de "painel que o operador acessa" é a união dos painéis dos papéis dele em qualquer contexto. Um operador com papel de `/app` numa organização pode conceder papel de `/app` em outra — coerente com o desenho do kit, onde o papel é global e a atribuição é por tenant (`PapeisSeeder:37-43`).

### Referências

- `app/Support/AdministradorDaInstalacao.php` (`recortarConcessao`, `regraDeConcessao`)
- `app/Filament/Admin/Resources/Users/UserResource.php` (`gravarPapeis`)
- `app/Support/Paineis.php:62-67`
- Refina: a decisão original do teto de escalada, no trabalho não commitado desta família

---

## ADR-03: Convite pelo social só é consumido quando cria conta nova

**Status**: Aceita
**Data**: 2026-08-30

### Contexto

Dois achados na mesma linha de código. **F-03**: `aceitarConviteSeHouver()` roda antes de `redirecionarSeIndisponivel()` no ramo do e-mail (`LoginSocialController.php:203` contra `:266`), então uma conta desativada ou soft-deleted queima o convite e não entra. **F-04**: o `?token=` entra na sessão no `redirecionar()` (`:83-84`), numa rota GET pública sem CSRF; com SSO silencioso do provedor, redirect e callback acontecem sem interação, e o convite é aceito sem consentimento da vítima. O `state` do Socialite protege o callback, não o início do fluxo.

### Decisão

Remover as duas chamadas de `aceitarConviteSeHouver()` (ramo do vínculo, `:189`; ramo do e-mail, `:203`) e o método. O aceite por conta **existente** passa a acontecer só na tela autenticada `ConvitesRecebidos`, que já tem `->requiresConfirmation()` e `exigirDono()`. A criação de conta nova por convite (`criarContaPorConvite()`) não muda.

### Alternativas Consideradas

1. **Só reordenar (F-03) e manter o aceite automático** — descartada: corrige o convite queimado e deixa o aceite sem consentimento de pé.
2. **Não ler `token` no `redirecionar()`** — foi a correção do relatório para F-04. Descartada por decisão do solicitante: derruba a feature de cadastro por convite via provedor social, que é uma wiki inteira (`cadastro-social-por-convite-e-organizacao`).
3. **Aceitar como está e documentar o risco** — descartada: exige SSO silencioso, mas o resultado é vincular a vítima a uma organização de terceiro sem clique.

### Consequências

- **Positivas**: uma remoção fecha dois achados. Menos código: o método `aceitarConviteSeHouver()` some inteiro.
- **Negativas**: quem já tem conta e recebe convite não vira membro na volta do provedor — precisa aceitar em `ConvitesRecebidos`. É um clique a mais, e é o clique que faltava.
- **Riscos**: os cenários da wiki `cadastro-social-por-convite-e-organizacao` que afirmavam o aceite automático mudam de oráculo. Tratado como desvio explícito, não como teste quebrado.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:189,203,604`
- `app/Models/Convite.php:645-668` (`aceitarComoUsuarioExistente`)
- `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/`

---

## ADR-04: O uso único do link de vínculo é cache, não coluna

**Status**: Aceita
**Data**: 2026-08-30

### Contexto

`pedirConfirmacaoDoVinculo()` emite `URL::temporarySignedRoute` de 30 minutos (`:445-449`) e `confirmarVinculo()` faz `vincular()` + `Auth::login()` sem consumir nada (`:502-508`). `ValidateSignature` confere assinatura e expiração, nunca unicidade. Quem alcançar o e-mail dentro da janela entra na conta.

### Decisão

`Cache::add('vinculo-social:'.hash('sha256', $signature), true, 30 min)` antes de vincular. `add()` é atômico e devolve `false` na segunda tentativa, que cai em recusa.

### Alternativas Consideradas

1. **Coluna `confirmado_em` com `UPDATE … WHERE … IS NULL`**, o padrão de `Convite.php:645-653` — descartada: exige migration e uma linha de tabela por link emitido, para um dado que expira em 30 minutos. O `Convite` precisa de coluna porque o convite é um registro com vida própria; o link de confirmação não é registro nenhum.
2. **Invalidar por `updated_at` do `VinculoSocial`** — descartada: o vínculo pode não existir ainda no primeiro uso, que é exatamente o caso que o link cria.

### Consequências

- **Positivas**: sem migration, sem tabela, TTL igual à janela da assinatura.
- **Negativas**: limpar o cache libera links ainda dentro dos 30 minutos. Aceito — a assinatura continua expirando sozinha, e o pior caso volta a ser o comportamento de hoje.
- **Riscos**: driver de cache `array` (default em teste) não persiste entre requests. Os CTs precisam usar o driver configurado do ambiente de teste, não presumir persistência entre processos.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:445-449,498-508`
- `app/Models/VinculoSocial.php:66-72` (`firstOrCreate` é idempotente — por isso o reuso não deixava rastro)
- `app/Models/Convite.php:645-653` (o padrão atômico que **não** foi copiado, e por quê)
