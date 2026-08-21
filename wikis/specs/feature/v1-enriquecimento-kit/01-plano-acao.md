# Plano de Ação — Enriquecimento do kit para a versão 1.0

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: não há wiki anterior sobre o cartão de identidade do usuário no dropdown nem sobre a
  varredura do diretório de plugins.
- **Toca infra compartilhada?**: **sim** — o render hook novo é registrado nos **três** PanelProviders
  (`admin`, `app`, `infra`), e o método novo entra no `App\Models\User`, consumido por
  `canAccessPanel()`, pelas policies e por toda a suíte de tenancy.

> Regressão **obrigatória** apesar de o tipo ser "nova": a marca "toca infra compartilhada" força o
> `feature-quality-gate` a rodar contra os CT/CT-B das features que consomem `User` e os painéis —
> `multi-tenancy`, `perfil-e-acesso-ao-painel` e `admin-da-organizacao`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Analisar o AdminPanel do `mini-pff` | 0 | Feito por sub-agente; resultado em `02` (ADR-01) e no `08-comparativo-mini-pff.md` |
| RQ-02 | Importar a view do dropdown do usuário | 2, 3 | — |
| RQ-03 | A view em **todos** os painéis | 4 | Três painéis: `admin`, `app`, `infra` |
| RQ-04 | Levantar demais features do `mini-pff` | 8 | `08-comparativo-mini-pff.md` |
| RQ-05 | Só plataforma, nada de negócio | 8 | Critério de corte declarado no próprio comparativo |
| RQ-06 | Revisar o projeto e confirmar que funciona | 1 | Suítes, Pint e PHPStan; resultado no `03-progresso.md` |
| RQ-07 | Propor melhorias e features de valor | 8, 9 | Backlog priorizado para a 1.0 |
| RQ-08 | Revisão completa dos pacotes do diretório | 7 | 547 plugins, 61 páginas |
| RQ-09 | Varrer do início ao fim das páginas | 7 | 7 lotes em `varredura/`, fim confirmado na página 61 |
| RQ-10 | Listar candidatos que enriqueceriam o kit | 7, 9 | `wikis/pacotes-candidatos.md` |
| RQ-11 | Top 10 com prós e contras | 9 | `wikis/pacotes-candidatos.md` seção "Top 10" |
| RQ-12 | Só Filament v5 e free | 7 | Filtro `v=5&price=free` na URL de toda página varrida |
| RQ-13 | Sub-agentes para a varredura | 7 | 7 sub-agentes de varredura |
| RQ-14 | Sub-agentes para estudo/análise | 7 | Mesmos agentes, com o perfil do kit como critério — ver `00` §Ambiguidades |
| RQ-15 | Documentar tudo na wiki | 2..10 | — |
| RQ-16 | Lista de usados / descartados / candidatos | 9, 10 | `wikis/pacotes-candidatos.md` (novo) + `wikis/pacotes.md` (existente, atualizado) |
| RQ-17 | Branch nova | 0 | `feature/v1-enriquecimento-kit` |
| RQ-18 | Não fazer merge com a `main` | — | Restrição de conduta, não passo |
| RQ-19 | Implementar na branch | 2..6 | — |
| RQ-20 | Relatório da varredura para decisão posterior | 9 | — |
| RQ-21 | Responder em Caveman `ultra` | — | Conduta de conversa; arquivos de wiki são boundary do Caveman |

## Objetivo

Trazer para o starter-kit o **cartão de identidade do usuário** que o `mini-pff` exibe dentro do
dropdown do canto superior direito — avatar, nome, e-mail e o badge do papel — e registrá-lo nos três
painéis do kit, com o vocabulário do kit (`master_global`, `roles.painel`, `Tenant`) no lugar do
vocabulário de negócio do projeto de origem.

Em paralelo, fechar o levantamento que a versão 1.0 exige: o que o `mini-pff` tem de plataforma e o
kit não tem, e quais dos 547 plugins Filament v5 gratuitos do diretório oficial valem uma instalação.
Os dois levantamentos terminam em documento, não em código — instalar dependência exige aprovação
(`CLAUDE.md`), e a decisão é do mantenedor.

## Contexto

Hoje o dropdown do usuário no kit responde "o que eu posso fazer" (Meu perfil, Bloquear sessão,
Convites recebidos, tema, sair) mas **não responde "quem sou eu"**. Numa instalação com três painéis,
impersonação ligada e papéis por organização, essa lacuna custa caro: o operador não distingue à vista
se está em sessão própria ou impersonada, com que papel entrou, e — no `/app` — em qual organização.

O `mini-pff` resolveu isso com 35 linhas de Blade e um render hook por painel. É a peça mais portável
do repositório de origem: sem dependência nova, sem migration, sem tabela.

## Análise dos Arquivos Existentes

### `resources/views/filament/`

Contém hoje um único arquivo: `spotlight-trigger.blade.php`. É o precedente de estilo — Blade puro,
sem estado, comentário de topo explicando por que a view existe e por que mora na raiz de
`views/filament/`. As views novas seguem o mesmo padrão e o mesmo diretório.

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`

Os três já registram um `->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, ...)` idêntico, com o
bloco de comentário explicando por que **não** é `USER_MENU_BEFORE`. O hook novo entra logo abaixo
desse, e o comentário existente é o lugar natural para diferenciar os dois hooks — `USER_MENU_BEFORE`
(rejeitado, renderiza dentro do dropdown) e `USER_MENU_PROFILE_BEFORE` (adotado agora, porque
renderizar dentro do dropdown é exatamente o que se quer aqui).

### `app/Models/User.php`

Já tem `isMasterGlobal()`, `temPapelDoPainel()`, `papeisEmQualquerContexto()` e
`getFilamentAvatarUrl()`. Falta apenas o passo de "qual papel eu exibo neste painel" — que hoje não
existe porque nenhuma tela precisava dele. É onde o método novo entra, ao lado de `temPapelDoPainel()`,
já que responde à mesma pergunta com outro retorno (nome em vez de booleano).

### `app/Support/Papeis.php`

Já converte chave técnica em rótulo de tela (`master_global` → "Administrador Geral"). O badge
consome `Papeis::rotulo()` — nenhum rótulo novo é escrito na view, senão o kit teria dois lugares
dizendo o nome do mesmo papel, e um deles divergiria.

## Autorização

- **Policies**: nenhuma criada ou alterada. A view só lê o próprio usuário autenticado.
- **Gates**: nenhum. O `Gate::before` do `master_global` continua intocado.
- **Middleware**: nenhum.
- **Guards**: o método novo filtra por `guard_name` do usuário, igual aos irmãos em `User`.

> A superfície nova é de **leitura do próprio sujeito**: não há registro de terceiro exibido, nem
> ação. Por isso não há decisão de autorização a tomar — o que a view mostra, o usuário já sabe.

## Rotas

Nenhuma rota criada, alterada ou removida.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Cabeçalho do dropdown do usuário | Blade via render hook | `/admin/*` | Abre o dropdown e lê avatar, nome, e-mail e badge do papel | Sim — o dropdown é Alpine |
| Cabeçalho do dropdown do usuário | Blade via render hook | `/app/*` | idem, com o badge refletindo o papel na organização aberta | Sim |
| Cabeçalho do dropdown do usuário | Blade via render hook | `/infra/*` | idem | Sim |

**Gate de CT-B**: a tabela é o gatilho, não o critério. O conteúdo do cabeçalho (nome, e-mail, papel,
presença nos três painéis) é **renderização de Blade** e prova-se em teste de componente — o hook é
emitido no HTML da página, esteja o dropdown aberto ou fechado. Só um cenário exige navegador: o
dropdown **abrir** e o cabeçalho ficar de fato visível ao clique, porque isso é Alpine executando.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é criada por esta feature — não se aplica.

## Variáveis de Ambiente

Nenhuma. A feature não tem kill-switch de config de propósito (ADR-04 do `02`).

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`perfil-e-acesso-ao-painel`**: o badge lê a mesma coluna `roles.painel` que governa o acesso.
  Papel sem painel continua sem badge — e isso é informação correta, não bug.
- **`multi-tenancy`**: no `/app`, o papel exibido depende do tenant aberto. O método novo usa a
  relação `papeisEmQualquerContexto()`, a mesma que `canAccessPanel()` usa, então não introduz um
  segundo entendimento de "papel do usuário" no código.
- **Impersonação (`stechstudio/filament-impersonate`)**: com o cabeçalho no lugar, o operador
  impersonado vê o nome do alvo — ganho colateral direto de segurança operacional.
- **Suíte Browser**: nenhum seletor existente muda; o cabeçalho é inserido **antes** do item
  "Meu perfil", que continua com o mesmo texto e a mesma posição relativa aos irmãos.

## Rollback

- **Migration down**: não há migration.
- **Feature flag**: não há, por decisão (ADR-04).
- **Reversão**: remover os três `->renderHook(USER_MENU_PROFILE_BEFORE, ...)` e apagar as duas views.
  O método em `User` pode ficar sem dano — mas o correto é sair junto, senão vira código sem chamador.

## Dependências

- **Composer**: nenhuma nova.
- **NPM**: nenhuma nova.

> Esta linha é o compromisso central da entrega: os 547 pacotes varridos terminam em **relatório**,
> não em `composer require`. Ver `00-requisito.md` §Ambiguidades.

## Riscos

- **Sombra do `filament()->auth()->user()` fora de painel** — a view é renderizada só dentro de um
  request de painel, mas um teste que a renderize isolada quebraria. *Mitigação*: a view trata usuário
  nulo com early return, e o CT-08 fixa isso.
- **Custo de query por request** — o badge faz uma consulta a mais na tabela de papéis por página
  renderizada. *Mitigação*: é a mesma relação que `canAccessPanel()` já carregou no request; medido em
  CT-09 com `assertQueryCount` não é viável em Livewire, então o controle é o `->first()` com `LIMIT 1`
  implícito e a ausência de N+1 (uma consulta, não uma por linha).
- **Divergência de rótulo com a tabela de papéis** — se a view escrevesse o rótulo à mão, o kit teria
  dois textos para o mesmo papel. *Mitigação*: `Papeis::rotulo()` é a fonte única; CT-07 fixa.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` do kit já tem os channels `tenancy` e `autenticacao`, ambos usados pelo
`User` (`canAccessPanel()` grava em `autenticacao`, `canAccessTenant()` em `tenancy`).

### Decisão

**Nenhum channel novo, e nenhum log novo.** É decisão consciente e está registrada como ADR-05 no
`02-decisoes-arquiteturais.md`: a feature é uma leitura de identidade do próprio usuário autenticado,
renderizada a cada página. Logar isso produziria uma linha por request por usuário, sem responder
pergunta nenhuma que o `authentication-log` já instalado não responda melhor.

> O padrão `[Classe@Método]` continua obrigatório em todo log **que exista**. O que este plano declara
> é que aqui não deve existir nenhum — e declarar isso explicitamente é o que impede a próxima pessoa
> de "corrigir a falta de log".

## Estrutura de Implementação

### 0. Preparação — branch e recon (concluído antes deste plano)

> Skills: —

- Branch `feature/v1-enriquecimento-kit` criada a partir de `main` em `198ccc9`.
- Sub-agente mapeou o `mini-pff` (4 painéis, plugins, views de user menu, features de plataforma).
- Sub-agente mapeou o starter-kit (3 painéis, `app/`, views, testes, wikis, rules, config, rotas).
- **Logs**: nenhum (passo de leitura).

### 1. Revisão de saúde do projeto (RQ-06)

> Skills: `pest-testing`

- `php artisan test --testsuite=Kit --parallel`
- `php artisan test --testsuite=Tenancy --parallel`
- `vendor/bin/pint --test --parallel`
- `vendor/bin/phpstan analyse --no-progress`
- Registrar os quatro resultados em `03-progresso.md` → *Revisão de saúde*.
- **Logs**: nenhum (passo de verificação).

### 2. `App\Models\User::papelDoPainel()`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/User.php`
- Assinatura: `public function papelDoPainel(string $painel): ?string`
- Posição: logo **abaixo** de `temPapelDoPainel()`, dentro do bloco "Acesso aos painéis" — responde à
  mesma pergunta que ele, mudando só o retorno (nome do papel em vez de booleano).
- Lógica:
  1. Se `isMasterGlobal()`, devolve `config('filament-shield.super_admin.name', 'master_global')` —
     o master não tem `roles.painel` preenchido (nulo não é coringa), então uma consulta por painel
     devolveria `null` e o badge sumiria justo para quem tem mais acesso.
  2. Senão, consulta `papeisEmQualquerContexto()` filtrando `painel = $painel` e
     `guard_name = $this->getDefaultGuardName()`, e devolve o `name` do primeiro.
- Uso de `papeisEmQualquerContexto()` e **não** de `roles()`: é a mesma relação que `canAccessPanel()`
  usa. Duas relações diferentes respondendo "qual o papel do usuário" divergiriam no `/app`, onde o
  `roles()` do spatie aplica `wherePivot(team_id, ...)` do tenant corrente.
- Retorno via `getAttribute('name')` e não `->name`: o genérico da relação é `Model`, porque
  `Config::roleModel()` é `class-string<Model>` — prometer `Role` seria afirmar mais do que a fonte
  diz, e o PHPStan reprovaria o acesso direto.
- **Logs**: nenhum (ver §Channel de Log).

### 3. As duas views

> Skills: `tailwindcss-development`

#### 3.1 `resources/views/filament/perfil-indicator.blade.php`

- Blade puro, sem estado. Recebe o usuário por `filament()->auth()->user()`.
- Resolve o painel corrente por `filament()->getCurrentOrDefaultPanel()->getId()` e chama
  `papelDoPainel()`.
- Renderiza um `fi-badge` com o rótulo de `App\Support\Papeis::rotulo()` e um ícone
  (`Heroicon::OutlinedShieldCheck` para `master_global`, `OutlinedUserCircle` para o resto).
- `@if` de guarda: sem papel para o painel, **nada é renderizado** — nem badge vazio, nem traço.
- Classes de cor: par claro/escuro explícito (`bg-primary-50 ... dark:bg-primary-400/10`), porque
  `assertSee` não valida tema e badge sem par escuro fica ilegível no dark mode.

#### 3.2 `resources/views/filament/user-menu-header.blade.php`

- Early return se não houver usuário autenticado (blindagem do risco de render fora de painel).
- `<x-filament-panels::avatar.user :user="$user" size="lg" loading="lazy" />` — o componente do próprio
  Filament, que já consome `getFilamentAvatarUrl()` e cai no fallback ui-avatars quando é nulo.
- Nome e e-mail em `truncate`, com `title` para o valor inteiro — nome longo não pode alargar o
  dropdown.
- `@include('filament.perfil-indicator')` como terceira linha.
- Atributo `data-user-menu-header` no elemento raiz, para os CT-B terem seletor estável. (É a primeira
  peça do kit com gancho de teste próprio; ver ADR-06 e a dívida registrada em
  `.ai/rules/testes-browser.md`.)

### 4. Registro do render hook nos três painéis

> Skills: `laravel-best-practices`

- **Paths**: `app/Providers/Filament/AdminPanelProvider.php`,
  `app/Providers/Filament/AppPanelProvider.php`,
  `app/Providers/Filament/InfraPanelProvider.php`
- Em cada um, logo **abaixo** do `renderHook(GLOBAL_SEARCH_BEFORE, ...)` existente:

  ```php
  ->renderHook(
      PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
      fn (): View => view('filament.user-menu-header'),
  )
  ```

- Import novo por arquivo: `Illuminate\Contracts\View\View`. `PanelsRenderHook` já está importado nos
  três.
- **Comentário obrigatório** acima do hook, no estilo dos providers do kit: explicar que
  `USER_MENU_PROFILE_BEFORE` renderiza **dentro** do dropdown, acima do item "Meu perfil" — o mesmo
  comportamento que fez `USER_MENU_BEFORE` ser rejeitado para o gatilho ⌘K é o que o torna certo aqui.
  Sem essa nota, a próxima pessoa lê o bloco de cima e "corrige" este hook.
- **Logs**: nenhum.

### 5. Testes de componente (CT do `04`)

> Skills: `pest-testing`

- **Path**: `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php`
- Suite `Kit` (grupo `kit`), helpers de `tests/Pest.php` — `usuarioDoKit()`, `usuarioComPapel()`,
  `noPainelBootado()`.
- Cenários em `04-casos-de-teste.md`.
- **Path (tenancy)**: `tests/Tenancy/CabecalhoDoMenuDoUsuarioComTenantTest.php` para o cenário do badge
  no `/app` com organização aberta.

### 6. Teste de browser (CT-B do `05`)

> Skills: `pest-testing`

- **Path**: `tests/Browser/CabecalhoDoMenuDoUsuarioTest.php`
- Um único cenário: clicar no gatilho do dropdown e ver o cabeçalho aparecer — a única afirmação desta
  feature que exige JavaScript executado.
- `npm run build` é pré-requisito duro; nunca `--parallel`.

### 7. Consolidação da varredura de plugins (RQ-08 a RQ-14)

> Skills: —

- 7 lotes de sub-agente cobrindo as páginas 1 a 61 de `filamentphp.com/plugins?v=5&price=free`.
- Saída bruta preservada em `wikis/specs/feature/v1-enriquecimento-kit/varredura/lote-{1..7}-*.md`.
- Fim da paginação **confirmado**: página 61 com 7 itens, 62 e 63 vazias, total 547 — igual ao
  contador do site.
- **Logs**: nenhum (passo de pesquisa).

### 8. Comparativo `mini-pff` × starter-kit (RQ-04, RQ-05, RQ-07)

> Skills: —

- **Path**: `wikis/specs/feature/v1-enriquecimento-kit/08-comparativo-mini-pff.md`
- Tabela: feature de plataforma do `mini-pff` → já existe no kit? → vale portar? → esforço → risco.
- Corte declarado: tudo que toca PFF, projetos, aportes, pedidos, SAP ou prestação de contas fica de
  fora, por RQ-05.

### 9. Relatório de pacotes (RQ-10, RQ-11, RQ-16, RQ-20)

> Skills: —

- **Path**: `wikis/pacotes-candidatos.md` (arquivo novo, meio-permanente da wiki — sobrevive à feature)
- Seções: instalados hoje · **Top 10 com prós e contras** · candidatos de segunda linha · descartados
  com motivo · método e limites da varredura.
- Linkado a partir de `wikis/README.md` e de `wikis/pacotes.md`.

### 10. Fechamento da wiki

> Skills: `feature-test-design`, `feature-quality-gate`

- `03-progresso.md` com todos os checkboxes fechados, desvios e notas.
- `06-relatorio-qa.md` com o veredito do quality gate.
- Limpar `wikis/specs/main/lembretes-de-convite/getLoginUrl())` — arquivo com nome corrompido por
  redirecionamento de shell, encontrado na revisão do passo 1.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> A escada aplicada a esta feature:
> 1. Reutilizar: `x-filament-panels::avatar.user` (componente do Filament) em vez de montar `<img>`;
>    `Papeis::rotulo()` em vez de escrever rótulo na view; `papeisEmQualquerContexto()` em vez de uma
>    segunda relação.
> 2. Stdlib/nativo: render hook do Filament em vez de publicar a view de vendor do user menu.
> 3. Sem dependência nova.
> 4. O método em `User` é uma consulta com dois `where` e um `first()`.
>
> Atalhos deliberados marcados com `ponytail:`.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na conversa agent ↔ usuário.
> Arquivos de wiki (00-08), código, commits e PRs são boundary do Caveman — prosa normal.

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend e de componente.
> Ver `05-casos-de-teste-browser.md` para o único cenário que exige navegador.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test --testsuite=Kit --parallel`
- [ ] `php artisan test --testsuite=Tenancy --parallel`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `npm run build && php artisan test --testsuite=Browser` (série, nunca `--parallel`)

## Commits

- `:sparkles: feat(user-menu): cabeçalho de identidade no dropdown dos três painéis`
- `:memo: docs(wiki): varredura dos 547 plugins Filament v5 free e comparativo com o mini-pff`
