# Plano de Ação — Botão "Voltar ao topo" em todos os painéis

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (porte do `mini-pff`, `wikis/specs/main/scroll-to-top-paineis/`)
- **Toca infra compartilhada?**: **sim** — `ConfiguraFilamentGlobal`, que vale para os três painéis
  e para toda tela de plugin de terceiro.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Ler o botão do `mini-pff` | 0 | Blade lido integralmente |
| RQ-02 | Ler a anotação sobre não usar pacote | 0 | `01-plano-acao.md` da wiki de origem; resumida em ADR-01 |
| RQ-03 | Trazer para o kit | 1 | `resources/views/filament/voltar-ao-topo.blade.php` |
| RQ-04 | Padrão em **todos os painéis** | 2 | Render hook **global**, sem `scopes:` |
| RQ-05 | Em **todas as páginas** | 2 | `BODY_END` é emitido no layout, inclusive em tela de vendor |
| RQ-06 | Sem o pacote | ADR-01 | Nenhuma dependência nova |

## Objetivo

Trazer para o kit o botão flutuante "voltar ao topo" do `mini-pff`: ~25 linhas de Blade com Alpine,
injetadas **uma vez** por render hook global, valendo para os três painéis e para toda tela — as do
kit e as dos treze plugins que trazem tela própria.

## Contexto

O kit liga `deferLoading()` e `defaultPaginationPageOption(10)` em toda tabela
(`ConfiguraFilamentGlobal`), então listagem raramente estoura a dobra. Mas o `/infra` tem telas que
estouram sempre: log de autenticação, auditoria, exceções, trilha de e-mail, logs, dependency graph.
Nelas, voltar ao topo hoje é rolagem manual.

O widget de chat do `/app` já antecipa esta feature no próprio comentário:

> *"Ocupa o canto inferior direito; se o seu painel já tiver algo ali (botão "voltar ao topo", chat
> de suporte), ajuste o `bottom-*` aqui."* — `assistente-chat-widget.blade.php:12-13`

## Análise dos Arquivos Existentes

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`

É a "cola" global do kit: `configureUsing()` de Table/Toggle/IconColumn, PanelSwitch e o seletor de
idioma. Todos são registros **globais**, não por painel — exatamente a natureza deste hook. É o
equivalente do `FilamentSettingsServiceProvider` do `mini-pff`, que é onde o botão vive lá.

### `resources/views/livewire/assistente-chat-widget.blade.php`

Botão em `fixed bottom-6 right-6 z-40 h-14 w-14`; slide-over em `z-50`. **Idêntico ao `mini-pff`** —
logo o offset e a camada calculados lá valem aqui sem recálculo.

A diferença: no `mini-pff` o chat está em `gerencial` e `projetos`; no kit está **só no `/app`**
(`AppPanelProvider`, `BODY_END`). Então o `bottom-24` se aplica ao painel `app`, não a `gerencial`.

### `resources/views/filament/`

Já tem `spotlight-trigger.blade.php`, `user-menu-header.blade.php` e `perfil-indicator.blade.php` —
o padrão de Blade puro sem estado, com comentário de topo explicando por que a view existe.

## Autorização · Rotas · Variáveis de Ambiente · Eventos · Jobs

**Nenhum.** É chrome de UI, sem estado, sem persistência, sem autorização — aparece para quem já
está numa tela de painel.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação | Depende de JS? |
|---|---|---|---|---|
| Botão "Voltar ao topo" | Blade + Alpine, via render hook global | **todas** as telas dos 3 painéis | Aparece ao rolar > 400px; clique volta ao topo | **Sim** — Alpine inteiro |

**Gate de CT-B**: passa. O botão **só existe** quando o JavaScript executa — `x-show`, `x-cloak`,
`@scroll.window` e o `window.scrollTo` são todos Alpine. Um teste de componente veria o HTML e não
provaria nada: o elemento está sempre no DOM, e o que decide se ele aparece é o Alpine.

## Impacto em Features Existentes

- **Widget de chat do `/app`**: ocupa o mesmo canto. Resolvido por offset (`bottom-24` no `app`), e
  por camada — o botão fica em `z-20`, abaixo do chat (`z-40`) e do slide-over (`z-50`).
- **Suíte Browser**: o botão passa a existir em **toda** tela varrida. `assertNoJavaScriptErrors()`
  cobre a regressão de graça — Alpine quebrado no hook derruba os 55 cenários.
- **Telas de vendor**: passam a ter o botão sem nenhuma edição. É o ponto da abordagem.

## Rollback

Remover a chamada em `ConfiguraFilamentGlobal` e apagar a view. Duas ações, nada de migration.

## Dependências

**Nenhuma.** É o ponto de RQ-06 e de ADR-01.

## Riscos

- **Colisão de z-index com plugin futuro.** *Mitigação*: `z-20` é abaixo de tudo que o kit e os
  plugins usam hoje (topbar 30, sidebar mobile 30, modal 40/50, chat 40/50, notificações 50). O
  comentário no Blade lista cada um com arquivo e linha.
- **Offset do chat mudar e o botão sobrepor.** *Mitigação*: os dois Blades se citam.
- **Tailwind não gerar as classes.** Os literais `bottom-6` e `bottom-24` precisam aparecer no texto
  do arquivo. *Mitigação*: estão, e o CT-B02 pega se sumirem.

## Channel de Log da Feature

**Nenhum log, nenhum channel.** É chrome de UI sem estado. Declarado para ninguém "corrigir a falta".

## Estrutura de Implementação

### 1. A view

> Skills: `tailwindcss-development`

- **Path**: `resources/views/filament/voltar-ao-topo.blade.php`
- Blade puro + Alpine, sem estado, sem Livewire
- Gatilho de visibilidade: `window.scrollY > 400`, em `@scroll.window.passive`
- `x-cloak` para não piscar antes do Alpine subir
- `x-transition.opacity` para o fade
- Offset por painel: `bottom-24` no `app` (por causa do chat), `bottom-6` nos demais
- `z-20`, com o comentário listando as camadas ocupadas
- `prefers-reduced-motion` tratado no `@click` — `behavior: 'auto'` devolve a decisão ao CSS
- `focus-visible:` e não `focus:` — o anel não pinta em clique de mouse
- `aria-label` e `title` "Voltar ao topo"
- `data-voltar-ao-topo` como âncora de teste (mesmo padrão do `data-user-menu-header`)

### 2. Registro global

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php`
- `FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, ...)`, **sem `scopes:`**
- Comentário explicando por que sem escopo: o `ViewManager` normaliza `null` para o bucket `''` e
  lê esse bucket **sempre**, antes de qualquer escopo — logo vale para os três painéis e para
  qualquer painel futuro, sem tocar em nenhum `PanelProvider`

### 3. Testes

> Skills: `pest-testing`

- `tests/Kit/VoltarAoTopoTest.php` — presença nos três painéis, offset por painel, âncora
- `tests/Browser/VoltarAoTopoTest.php` — CT-B: o botão **aparece** ao rolar e leva ao topo

## Filosofia de Implementação

> **Ponytail em `full`.** A escada já foi percorrida pelo `mini-pff` e o resultado se confirma aqui:
> degrau 5 ("dependência já instalada resolve?") e degrau 3 ("feature nativa?") derrubaram o pacote;
> o que sobrou foi ~25 linhas de Blade no degrau 7.
>
> **Caveman `ultra`** na conversa; wiki e código são boundary.

## Testes

> Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [ ] `vendor/bin/filacheck`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel`
- [ ] `npm run build && php artisan test tests/Browser/VoltarAoTopoTest.php`

## Commits

- `:sparkles: feat(ui): botão "voltar ao topo" em todas as telas dos três painéis`
