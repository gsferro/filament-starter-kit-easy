# Comparativo — `mini-pff` × starter-kit-easy

> Atende RQ-01, RQ-04, RQ-05 e RQ-07 do `00-requisito.md`.
> Levantamento por sub-agente em `D:\PROJECTS\<interno>\Mini PFF\mini-pff`, sem escrever nada lá.

## O corte

Os dois projetos nasceram da mesma base. O `mini-pff` andou muito mais em **negócio**; o kit andou
mais em **produto empacotável** (instalador, atualizador, wiki, testes, distribuição).

Este documento olha só a interseção útil: **o que o `mini-pff` tem de plataforma e o kit não tem.**

Fica de fora, por RQ-05, tudo que toca PFF, projetos, aportes, pedidos, requisições, SAP, prestação
de contas, GED, Serpro, ticketing e o RAG de documentos institucionais. Também fica de fora o que o
kit já resolve de outro jeito — e isso é a maioria.

## Placar geral

| | `mini-pff` | starter-kit-easy |
|---|---|---|
| Painéis | 4 (admin, gerencial, projetos, pedidos) | 3 (app, admin, infra) |
| Pacotes em `require` | 49 | 51 |
| Pest | 4 | **5** (`--tia`, `--mutate`, sharding) |
| Tema Filament (`viteTheme`) | 4 temas próprios | nenhum (CSS por `FilamentAsset`) |
| Multi-tenancy | `Entidade`, em 2 painéis | `Tenant`, no `/app`, **desligável** |
| Instalador | — | `kit:install` + `kit:update` + `kit:tenancy` |
| Wiki | por feature | por feature **+ 7 documentos permanentes** |
| Project rules (`.ai/rules`) | — | 5 |

O kit não está atrás. Está adiante em empacotamento e em testes, e atrás em duas coisas: **identidade
visual configurável** e **algumas conveniências de plataforma** listadas abaixo.

---

## 1. O que foi portado nesta entrega

| Item | Origem | Estado |
|---|---|---|
| Cabeçalho de identidade no dropdown do usuário | `resources/views/filament/user-menu-header.blade.php` + `perfil-indicator.blade.php` | ✅ **implementado** nos 3 painéis |

Adaptações feitas na tradução do vocabulário:

| `mini-pff` | starter-kit-easy | Por quê |
|---|---|---|
| `App\Enums\PerfilGerencial` com `label()`/`icon()` | `App\Support\Papeis::rotulo()` | O kit não tem enum de perfil; papel é linha na tabela `roles`, criável pela UI |
| `$user->isMasterGlobal() ? MasterGlobal : $user->perfilGerencial($tenant)` no Blade | `User::papelDoPainel($painel)` no model | Regra em Blade não é testável nem passa por PHPStan (ADR-02) |
| `Filament::getTenant()` como eixo | `roles.painel` como eixo | No kit, o que dá acesso é o painel do papel — inclusive nos painéis sem tenancy |
| Sem gancho de teste | `data-user-menu-header` | O CT-B precisa de âncora estável (ADR-06) |

---

## 2. Lacunas reais — o que vale portar, em ordem de valor

### 2.1 Identidade visual: paleta fiel e botão que escurece no hover — **alto valor**

O `mini-pff` tem `App\Services\PaletaDaMarca`, que devolve escalas de **11 tons** por alvo, e um
`App\View\Components\ButtonComponent` que sobrescreve o `ColorMap` do botão do Filament.

Os dois existem por causa da mesma armadilha, medida lá: **`->colors(['primary' => '#2EA6C7'])` não
renderiza `#2EA6C7`**. O Filament descarta luminosidade e croma da string e regenera a paleta a partir
do matiz. Quem passa o hex da marca recebe outra cor, sem erro nenhum.

O kit tem `App\Support\CorPrimaria::paleta()` e a cor por organização, mas resolve por **matiz**, não
por escala completa. Efeito prático: `KIT_COR_PRIMARIA` e a cor da organização são aproximações do
que o usuário pediu.

- **Esforço**: médio · **Risco**: baixo (é aditivo; a assinatura de `CorPrimaria::paleta()` não muda)
- **Impacto**: a cor da organização passa a ser *a cor da organização*
- **Recomendação**: **portar**. É a lacuna de plataforma mais concreta entre os dois projetos.

### 2.2 Formatação brasileira global — **alto valor, baixíssimo custo**

`AppServiceProvider::configureFormatacaoBrasileira()` no `mini-pff`: doze linhas que fixam `d/m/Y`,
`pt_BR` e `BRL` como default de **toda** `Table` e **todo** `Schema` do sistema, num
`configureUsing()`.

O kit é pt-BR de ponta a ponta e **não tem isso**. Hoje, cada `DateTimeColumn` precisa do formato na
mão, e a primeira que esquecer mostra `2026-08-18`.

- **Esforço**: baixo — cabe em `ConfiguraFilamentGlobal`, ao lado do que já está lá
- **Risco**: baixo, mas **toca toda tabela do kit**: precisa da suíte inteira verde
- **Recomendação**: **portar**. Melhor relação valor/linha de toda esta lista.

### 2.3 Página de lixeira (restauração de soft deletes) — **médio valor**

`App\Filament\Pages\Lixeira` restaura qualquer model com `SoftDeletes`, de qualquer entidade,
varrendo `app/Models`.

O kit não tem tela de restauração. Ver o item 3 do top 10 em
[`wikis/pacotes-candidatos.md`](../../../pacotes-candidatos.md): existe pacote mantido (`promethys/revive`) que faz
isto. **Trocar código nosso por pacote é o movimento certo** — desde que a policy seja do kit.

- **Esforço**: baixo (pacote) / médio (policies) · **Risco**: restaurar ignora regra de negócio
- **Recomendação**: **adotar o pacote**, não portar o código.

### 2.4 Botão "voltar ao topo" — **médio valor, custo trivial**

`resources/views/filament/scroll-topo.blade.php` no `BODY_END`, registrado **globalmente** (sem
`scopes:`), então vale para qualquer painel presente ou futuro. Trata `prefers-reduced-motion` e
resolve o offset por painel dentro do próprio Blade.

O kit só usa `BODY_END` no `/app`, para o widget de chat. Em tabela longa no `/infra`, a volta ao topo
é rolagem manual.

- **Esforço**: baixo · **Risco**: baixo (z-index contra chat/modal/topbar, já resolvido na origem)
- **Recomendação**: **portar**, com o cuidado do z-index — o kit tem o chat no mesmo canto.

### 2.5 Skeletons de carregamento — **médio valor**

`resources/views/components/skeletons/*` + traits `PlaceholderSkeleton*`. O kit liga `deferLoading()`
em toda tabela (`ConfiguraFilamentGlobal`), então **já tem o problema que os skeletons resolvem**:
o intervalo entre a página aparecer e a tabela carregar é hoje um vazio.

- **Esforço**: médio · **Risco**: baixo · **Recomendação**: **avaliar** depois das lacunas 2.1 e 2.2.

### 2.6 Mascaramento de dado pessoal em log — **médio valor**

`App\Support\Mascara` + `mascaraIdentificador()`, usados no log de login falhado do `mini-pff`.

O kit loga `user_id` e `tenant_id` (só IDs) na maior parte, o que já é a decisão certa. Mas o
`ConviteDeAcesso` e o fluxo de convite trabalham com e-mail, e uma linha de log com e-mail em claro
num kit distribuído é dívida de LGPD que nasce no primeiro `composer create-project`.

- **Esforço**: baixo · **Risco**: baixo · **Recomendação**: **portar** o helper e auditar os logs de
  convite.

### 2.7 Widget base de estatística reaproveitável — **baixo valor**

`StatsRecursoWidget` serve header de listagem e dashboard sem ramificação, com props `#[Reactive]`.
O kit tem 21 widgets próprios (7 no admin, 14 no infra) escritos um a um.

- **Esforço**: médio (refatoração de 21 arquivos) · **Risco**: médio
- **Recomendação**: **não portar agora**. É refatoração interna sem ganho visível, e o kit já é
  estável nesses widgets. Anotar como candidato pós-1.0.

---

## 3. O que o `mini-pff` tem e o kit **não deve** portar

| Item | Por quê |
|---|---|
| 4 temas `viteTheme` | Decisão de design system de um cliente. O kit deliberadamente não tem tema próprio |
| `SapServiceProvider`, GED, Serpro, RAG institucional | Negócio (RQ-05) |
| `ButtonComponent` isolado do resto | Só faz sentido junto da paleta de 11 tons (item 2.1) — portar sozinho é remendo |
| `configureProcessEnvNoWindows()` | **O kit já tem**, no `KitServiceProvider` |
| `Date::use(CarbonImmutable)`, `DB::prohibitDestructiveCommands`, `Password::defaults` | **O kit já tem**, no `KitServiceProvider` |
| `isolarScriptDoPulse()` | **O kit já tem**, como `isolarScriptsConflitantes()` |
| `TemUuid`, `AuditsFillables`, `BelongsToEntidade` | **O kit já tem** os três equivalentes |
| `BadgeContagemNavegacao`, categorias do Spotlight com `canAccess()` | **O kit já tem** |
| `TelaBloqueio` com layout de login | **O kit já tem**, e ainda documentou a armadilha em `.ai/rules/auth.md` |
| Camada de notificação fluente (`Notificacao`, `SininhoDaEntidade`) | Boa, mas acoplada às categorias de negócio do `mini-pff`. Portar significaria reescrever, não copiar |
| `TestCase` com banco por worktree | Resolve dor de Postgres em worktree; o kit usa SQLite em memória |

---

## 4. Onde o kit está **à frente** do `mini-pff`

Registrado porque o fluxo inverso também vale — e porque explica por que a lista da seção 2 é curta.

| Item | Só no kit |
|---|---|
| `kit:install` interativo com customizador (nome, banco, admin, cor, multi-organização) | ✅ |
| `kit:update` (838 linhas) e `kit:tenancy` (liga/desliga tenancy no projeto instalado) | ✅ |
| Pest **5** — `--tia`, `--mutate`, sharding por tempo real | ✅ (o `mini-pff` está no 4) |
| Tenancy **desligável**, com o modelo de dados coerente nos dois modos | ✅ |
| Convite como única porta de entrada, com lote, lembrete e usuário já existente | ✅ |
| `.ai/rules` com 5 rules e as armadilhas já resolvidas escritas | ✅ |
| 7 documentos permanentes de wiki + 14 features especificadas | ✅ |
| Distribuição como `create-project` no diretório oficial de plugins | ✅ |
| `master_global` como `Gate::before`, com `roles.painel` governando os 3 painéis | ✅ |

---

## 5. Melhorias próprias do kit para a 1.0 (RQ-07)

Não vêm do `mini-pff`. Saíram da revisão do próprio projeto (passo 1 do PRD).

### 5.1 `ConfiguraFilamentGlobal` tem TODO de virar Settings editável em `/admin`

`spatie/laravel-settings` **e** o plugin do Filament já estão instalados, e a migration
`create_settings_table` já existe. Os defaults globais (paginação, densidade, comportamento de
filtro) são hoje código; virar tela é feature quase pronta, e é exatamente o tipo de coisa que
distingue um kit de um boilerplate.

**Recomendação**: forte candidata a entrar na 1.0.

### 5.2 Cobertura desbalanceada

388 casos entre `Kit` e `Tenancy`, contra 23 de `Browser` e **3** de `Feature`+`Unit` — e esses 3 são
os stubs do Laravel (`ExemploTest`, `ExampleTest`).

**Recomendação**: apagar os dois stubs antes da tag (ruído no `composer test` de quem instala), e
subir a suíte `Browser` nas telas que hoje só têm teste de componente.

### 5.3 A dívida de `data-testid`

`.ai/rules/testes-browser.md` registra que o kit não tem nenhum gancho de teste, e que a suíte
`Browser` depende de texto traduzido. Esta feature abriu o precedente com `data-user-menu-header`
(ADR-06).

**Recomendação**: pagar a dívida incrementalmente — uma âncora por tela, quando o CT-B daquela tela
for escrito ou revisado. Não fazer varredura em massa.

### 5.4 O painel `/app` nasce vazio

É design (o negócio é de quem instala), mas significa que a experiência do primeiro dia depende
inteiramente de `kit:tenancy --demo`.

**Recomendação**: para a 1.0, ou o `kit:install` oferece o modo demo com mais destaque, ou o `/app`
ganha uma tela de boas-vindas que ensina o próximo passo. Hoje o usuário novo vê um painel em branco.

### 5.5 O kit não é usável no celular

Nenhum dos 3 painéis tem navegação inferior nem tabela em cartões no mobile. Há pacote free v5 para
isso na varredura (Mobile Bottom Navigation, Mobile Preset).

**Recomendação**: decidir explicitamente **se** a 1.0 se propõe a ser mobile. Se sim, é feature com
wiki própria. Se não, dizer isso no README é melhor que deixar implícito.

### 5.6 Faxina antes da tag

- `wikis/specs/main/lembretes-de-convite/getLoginUrl())` — arquivo com nome corrompido por
  redirecionamento de shell. **Apagar.**
- `tests/Feature/ExemploTest.php` e `tests/Unit/ExampleTest.php` — stubs do Laravel.
- `composer create-project` limpo de verificação: a 0.16.9 corrigiu justamente o pacote distribuído
  (`export-ignore` removia `phpunit.xml`/`pint.json`/`phpstan.neon`), e a última *breaking change*
  (`admin_organizacao` → `admin_app`) é de 16/08. Antes de tagear 1.0, vale nascer um projeto do zero.

---

## 6. Ordem sugerida para a 1.0

| # | Item | Origem | Esforço |
|---|---|---|---|
| 1 | Faxina (5.6) | próprio | trivial |
| 2 | Formatação brasileira global (2.2) | `mini-pff` | baixo |
| 3 | Paleta de 11 tons + botão (2.1) | `mini-pff` | médio |
| 4 | Settings editável em `/admin` (5.1) | próprio | médio |
| 5 | Botão voltar ao topo (2.4) | `mini-pff` | baixo |
| 6 | Mascaramento em log de convite (2.6) | `mini-pff` | baixo |
| 7 | Lixeira via `promethys/revive` (2.3) | pacote | baixo/médio |
| 8 | Decidir mobile (5.5) | próprio | decisão |
| 9 | Skeletons (2.5) | `mini-pff` | médio |

Os itens 1, 2, 5 e 6 cabem numa tarde e não têm risco. Os itens 3 e 4 são as duas features que de
fato mudam o produto.
