# Plano de Ação — Badge de contagem em todo Resource do kit

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: nenhuma wiki nomeou o trait `BadgeContagemNavegacao`; ele nasceu dentro de
  outras entregas. A ancestral funcional é `wikis/specs/main/hub-de-navegacao-em-cards/`, que
  fixou o vocabulário de navegação do kit.
- **Motivo**: o trait existe e é usado por 8 resources, mas (a) esconde o zero, (b) não é
  obrigatório, e (c) dois resources do app ficaram de fora.
- **Toca infra compartilhada?**: **sim** — `app/Filament/Concerns/BadgeContagemNavegacao.php` é
  consumido por 8 resources nos três painéis. Alterá-lo muda o menu inteiro.

> "Toca infra compartilhada: sim" **força regressão** no quality gate, independentemente do tipo.
> A regressão é contra os testes que abrem tela de painel — `tests/Kit/PermissoesDeResourcesTest.php`,
> `tests/Kit/PaginasInfraTest.php` e `tests/Browser/TelasDoKitTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | "Convites" exibe a contagem | 1 | **já tinha o trait**; o que faltava era o zero aparecer |
| RQ-02 | "Papéis" exibe a contagem | 2 | exige resolver 3 colisões de trait — ver ADR-02 |
| RQ-03 | Todo Resource do app tem badge | 1, 2, 3, 4 | escopo `app/Filament/**/Resources/**`, três painéis |
| RQ-04 | Badge renderizado pelo odômetro | 1 | `OdometerNavigationBadge`, já usado pelo trait |
| RQ-05 | O padrão vira **regra** | 4 + step 9 | o passo 4 é o enforço; a Project Rule é proposta no step 9, não implementada aqui |

## Objetivo

Transformar "badge de contagem" de hábito em **invariante do kit**: todo item de menu que abre uma
listagem do app diz, antes do clique, quantos registros vai mostrar — inclusive quando são zero.

Duas metades. A primeira é de **comportamento**: o zero deixa de sumir, porque "não sei se está
vazio ou se o badge quebrou" é pior que um `0` discreto. A segunda é de **enforço**: hoje nada
impede o próximo resource de nascer sem badge, e foi exatamente assim que `RoleResource` e
`ComposerReleasePackageResource` ficaram de fora sem ninguém perceber.

## Contexto

- `App\Filament\Concerns\BadgeContagemNavegacao` já existe e já usa
  `Gsferro\FilamentOdometerEasy\Navigation\OdometerNavigationBadge`. **RQ-04 já está atendido pelo
  código atual** — o requisito pede para manter, não para introduzir.
- Inventário medido de resources registrados, por painel (`Filament::getPanel($id)->getResources()`):

  | Painel | Do app, com trait | Do app, **sem** | De vendor (fora de escopo) |
  |---|---|---|---|
  | `admin` | 4 — `AgenteIa`, `Convite`, `Tenant`, `User` | **1 — `Role`** | 3 |
  | `app` | 3 — `Convite`, `Projeto`, `User` | 0 | 1 |
  | `infra` | 1 — `AiRun` | **1 — `ComposerReleasePackage`** | 6 |

- Contagens reais desta instalação: `convites` **0**, `roles` **5**, `users` 1, `tenants` 1.
  É o zero de `convites` que produziu o requisito.
- `Filament\Resources\Resource\Concerns\HasNavigation::getNavigationBadgeColor()` devolve `null`
  por padrão (`:158`), e `null` é o que o Filament renderiza como cor default — que é a cor que os
  8 badges atuais já têm.

## Análise dos Arquivos Existentes

### `app/Filament/Concerns/BadgeContagemNavegacao.php`

O arquivo central. Hoje:

- `getNavigationBadge()` conta por `getEloquentQuery()` e **devolve `null` quando zero**;
- `getNavigationBadgeTooltip()` devolve `'Total de registros'`;
- não declara `getNavigationBadgeColor()`.

Passa a: sempre devolver o badge, declarar a cor, e memoizar a contagem — porque a cor e o número
passam a precisar do mesmo `count()` no mesmo request.

O docblock precisa ser reescrito: a frase *"Zero não vira badge: um `0` cinza em todo item só polui
o menu"* passa a ser falsa, e comentário que contradiz o código é o que faz o próximo agente
"consertar" a feature.

### `app/Filament/Admin/Resources/Roles/RoleResource.php`

Publicado no projeto por `shield:publish --panel=admin`, então é editável. **Mas usa
`Essentials\HasNavigation` (linha 97), que declara os três métodos de badge** — colisão fatal com o
trait do kit. Ver ADR-02, com a mensagem de erro medida.

### `app/Filament/Infra/Resources/ComposerReleasePackages/ComposerReleasePackageResource.php`

Subclasse de um resource de vendor que estende `Resource` puro — **sem colisão**. Trait entra
direto. O model é `ComposerReleasePackageSnapshot` e `getEloquentQuery()` funciona.

### `tests/Kit/PermissoesDeWidgetsTest.php:234` — `widgetsDePainelDoKit()`

Não é alterado, mas é o **molde** do enforço do passo 4: enumera o que está registrado nos painéis,
filtra por namespace `App\Filament\`, e reprova o que não segue a regra. O docblock dele explica
por que a lista é derivada e não escrita à mão.

## Autorização

Nada novo. Um badge não é superfície de autorização: ele só aparece para quem já vê o item de menu,
e o item de menu já é gated por `canAccess()` do resource, que consulta a policy do Shield.

- **Policies / Gates / Middleware / Guards**: nenhum criado ou modificado.
- **Seeders**: **não rodar**. Nenhuma entidade nova, nenhuma permission nova.

> **Um detalhe que não é óbvio e vale registrar**: a contagem sai de `getEloquentQuery()`, que em
> resource escopado por organização já traz o `where tenant_id`. O badge, portanto, **não vaza
> contagem entre organizações** — e isso é consequência do trait ter sido escrito assim desde o
> início, não algo que este plano acrescenta. O caso de teste correspondente está no `04`.

## Rotas

Nenhuma rota nova nem alterada.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Menu lateral dos três painéis — badge de cada item de Resource | Filament (navigation) | `/admin`, `/app`, `/infra` | leitura | **Sim** — o odômetro anima o número via JS do pacote |

**Gate de CT-B**: o único aspecto que só o navegador prova é a **animação** do odômetro, e ela já
existe hoje nos 8 badges — esta feature não a introduz. Tudo que a feature muda é falsificável sem
navegador: qual classe tem o trait, o que `getNavigationBadge()` devolve com zero e com N, e qual
cor `getNavigationBadgeColor()` devolve. A decisão final é da `feature-test-design`.

**Gate de tela de escrita**: não se aplica. Nenhuma rota `create`/`edit` é acrescentada, e a
feature não grava nada.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Os 8 resources que já usam o trait**, nos três painéis: passam a exibir `0` onde antes não
  exibiam nada. É a mudança visível mais ampla desta entrega, e ela é o pedido.
- **Menu de todos os painéis**: uma consulta `count()` por item de Resource do app, por
  carregamento. Já era assim para 8; passa a 10. A memoização do passo 1 evita que a cor dobre esse
  número.
- **`tests/Kit/PermissoesDeResourcesTest.php`**: tem uma âncora que compara a lista escrita à mão
  com `getResources()` de cada painel. Esta feature **não acrescenta resource**, então a âncora não
  deve mudar — se ficar vermelha, algo saiu do plano.
- **`tests/Browser/TelasDoKitTest.php`**: visita as telas dos três painéis. O badge novo em dois
  itens de menu não deve produzir erro de JS.
- **`.ai/rules/`**: candidata a ganhar uma rule nova — decisão do usuário no step 9 da skill,
  não algo que este plano executa sozinho.

## Rollback

- **Migration down**: não se aplica.
- **Feature flag**: não se aplica.
- **Reversão**: `git revert`. Três arquivos de produção, um de teste. Nada de dado, nada de schema.

## Dependências

Nenhuma nova. `gsferro/filament-odometer-easy` já está instalado e o plugin já está registrado nos
três painéis com `badgeOnCollapsedSidebar()`.

## Riscos

- **Colisão de trait em resource futuro**: qualquer resource que use um trait de vendor com
  `getNavigationBadge()` vai falhar **fatalmente no boot**, não com teste vermelho. O enforço do
  passo 4 obriga o trait, e é justamente aí que a armadilha aparece. **Mitigação**: ADR-02 registra
  o padrão de resolução com a mensagem de erro exata, para o próximo caso ser um `insteadof` e não
  uma investigação.
- **Duas consultas por item de menu**: o número e a cor precisam do mesmo `count()`.
  **Mitigação**: memoização com `once()`, medida no step 5.
- **Zero em toda parte polui o menu** — é exatamente o que o docblock atual argumentava.
  **Mitigação**: é o pedido explícito do usuário, e a cor cinza no zero é a concessão negociada.
  Registrado em ADR-01 para que a reversão, se vier, saiba o que está desfazendo.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `autenticacao`, `tenancy`, `configuracoes` e `ai`.

### Decisão

**Nenhum channel novo, e nenhum log novo.**

> O badge é leitura de menu. Logar aqui produziria uma linha por item de menu, por carregamento de
> qualquer tela de qualquer painel — o log mais barulhento possível, respondendo a pergunta
> nenhuma. Não há erro a registrar: `getEloquentQuery()->count()` sobre um resource registrado ou
> funciona ou já teria derrubado a listagem.

## Estrutura de Implementação

### 1. `BadgeContagemNavegacao`: o zero aparece, e a cor o distingue

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Concerns/BadgeContagemNavegacao.php`
- Passa a ter três métodos públicos e um privado:

  ```php
  public static function getNavigationBadge(): ?string
  {
      return OdometerNavigationBadge::make(static::contagemDoBadge());
  }

  public static function getNavigationBadgeColor(): string|array|null
  {
      // `null` é o default do Filament (HasNavigation:158) — a cor que os badges
      // do kit já tinham. Só o zero é rebaixado para cinza.
      return static::contagemDoBadge() === 0 ? 'gray' : null;
  }

  public static function getNavigationBadgeTooltip(): ?string
  {
      return 'Total de registros';
  }

  private static function contagemDoBadge(): int
  {
      return once(fn (): int => static::getEloquentQuery()->count());
  }
  ```

- **`once()` e não uma propriedade estática**: o número e a cor precisam do mesmo `count()` no
  mesmo request, e propriedade estática de trait sobrevive entre requests sob Octane. `once()` é
  descartado a cada request. **Medido no step 5**: memoiza corretamente dentro de método estático.
- **`getEloquentQuery()`, nunca `getModel()::count()`** — a regra já vigente do trait, e a razão
  continua a mesma: a query do resource carrega os escopos daquele painel (soft delete, escopo de
  organização, filtros de posse). Contar no model mostraria um número que a listagem não confirma.
- **Reescrever o docblock.** A frase *"Zero não vira badge"* sai. O texto novo diz que o zero
  aparece em cinza porque *"badge ausente não distingue 'vazio' de 'quebrado'"*, e aponta ADR-01.
- **Sem log.**

### 2. `RoleResource` recebe o trait, com as três colisões resolvidas (RQ-02)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`
- O `use` de traits, hoje em linhas separadas, vira um bloco único com resolução:

  ```php
  use BadgeContagemNavegacao, Essentials\HasNavigation {
      BadgeContagemNavegacao::getNavigationBadge insteadof Essentials\HasNavigation;
      BadgeContagemNavegacao::getNavigationBadgeColor insteadof Essentials\HasNavigation;
      BadgeContagemNavegacao::getNavigationBadgeTooltip insteadof Essentials\HasNavigation;
  }
  ```

- **São três `insteadof`, não um.** `Essentials\Resource\HasNavigation` declara os três métodos de
  badge (`:41`, `:46`, `:51`), e cada um colide separadamente. Ver ADR-02 — a resolução foi
  **executada** no step 5, com os três, e `RoleResource::getNavigationBadge()` devolveu o odômetro
  com 5.
- Os outros quatro `use Essentials\...` (`BelongsToParent`, `BelongsToTenant`, `HasGlobalSearch`,
  `HasLabels`) **ficam como estão** — não colidem.
- **Sem log.**

### 3. `ComposerReleasePackageResource` recebe o trait (RQ-03)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Infra/Resources/ComposerReleasePackages/ComposerReleasePackageResource.php`
- `use BadgeContagemNavegacao;` e o import. **Sem colisão**: o resource pai do vendor estende
  `Filament\Resources\Resource` puro e não declara nenhum método de badge (verificado no step 5).
- **Sem log.**

### 4. O enforço: teste que reprova Resource do app sem o trait (RQ-03, RQ-05)

> Skills: `pest-testing`

- **Path**: `tests/Kit/BadgeDeNavegacaoTest.php`
- A lista é **derivada dos painéis registrados**, nunca escrita à mão — é o que faz o caso pegar a
  classe nova, que é a única razão de ele existir. Molde:
  `tests/Kit/PermissoesDeWidgetsTest.php:234`.
- O filtro é `str_starts_with($fqcn, 'App\\Filament\\')`, que é exatamente a fronteira que o
  usuário decidiu: resource do app entra, resource de vendor não.
- Este passo é **metade** de RQ-05. A outra metade é o passo 5, e a divisão é deliberada: a
  máquina enforça, a prosa explica. Ver a escada do Ponytail aplicada a rules.
- **Sem log.**

> **A outra metade de RQ-05 não é passo de implementação.** A Project Rule em
> `app/Filament/**/Resources/**` é **proposta ao usuário no step 9 da skill**, gravada só com
> aprovação explícita e sempre pela tool `record-rule` do Boost. Texto curto, apontando para o
> teste do passo 4 — prosa só onde a máquina não alcança, e o que a máquina não alcança aqui é a
> armadilha da colisão (ADR-02), que só se manifesta como fatal no boot.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.**
> A escada, aplicada a esta feature:
> 1. **Reutilizar** — o trait existe; a entrega o corrige e o espalha, não o reescreve
> 2. **Stdlib** — `once()` do Laravel para memoizar; nenhuma cache manual
> 3. **Nativo** — `getNavigationBadgeColor()` do Filament, e `null` para dizer "cor default"
> 4. **Uma linha** — a cor é um ternário
> 5. **Mínimo que funciona** — três arquivos de produção, um de teste
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — prosa normal. Código, commits e PRs também.

## Testes

> Ver `04-casos-de-teste.md`. A existência do `05` é decidida pelo gate da `feature-test-design`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact tests/Kit/BadgeDeNavegacaoTest.php`
- [ ] Regressão (infra compartilhada): `php artisan test --compact tests/Kit/PermissoesDeResourcesTest.php tests/Kit/PaginasInfraTest.php`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] Abrir `/admin`, `/app` e `/infra` e conferir badge em todo item de Resource do app
- [ ] `php artisan about` — **o boot não pode estourar**; colisão de trait é erro fatal, não teste vermelho

## Commits

- `:sparkles: feat(navegacao): badge de contagem em todo Resource do kit, com zero visível`
- `:memo: docs(wiki): wiki da feature badge-de-contagem-em-todo-resource`
