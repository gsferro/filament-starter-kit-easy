# Progresso — Botão "Voltar ao topo"

**Branch**: `feature/v1-enriquecimento-kit`
**Concluído em**: 2026-08-18

## 1. A view

- [x] `resources/views/filament/voltar-ao-topo.blade.php`
- [x] Gatilho `window.scrollY > 400` em `@scroll.window.passive`
- [x] `x-cloak` + `x-transition.opacity`
- [x] Offset por painel: `bottom-24` no `app`, `bottom-6` nos demais
- [x] `z-20`, com o comentário listando cada camada ocupada
- [x] `prefers-reduced-motion` tratado no `@click`
- [x] `focus-visible:` e não `focus:`
- [x] `aria-label` e `title`
- [x] `data-voltar-ao-topo` como âncora de teste

## 2. Registro global

- [x] `ConfiguraFilamentGlobal::configuraBotaoVoltarAoTopo()`
- [x] `BODY_END`, **sem `scopes:`**
- [x] Comentário explicando o bucket `''` do `ViewManager` e por que isso alcança tela de vendor

## 3. Testes

- [x] `tests/Kit/VoltarAoTopoTest.php` — 13 casos, CT-01 a CT-06
- [ ] ~~`tests/Browser/VoltarAoTopoTest.php`~~ — **removido**, ver Desvios

## Verificação Final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` → 0 erros (level 7)
- [x] `vendor/bin/filacheck` → 17 regras
- [x] `php artisan test tests/Kit/VoltarAoTopoTest.php` → 13 casos, 28 asserções
- [x] `php artisan test tests/Kit/PaineisTest.php` → 23 casos (nenhum painel quebrou)
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel`
- [x] `git commit`

---

## Auditoria Pré-Implementação

### Revisão profunda — premissas contra o código real

| Premissa | O que o código diz | Correção |
|---|---|---|
| "o offset é para o painel `gerencial`, como no mini-pff" | o kit tem o chat só no `/app` | ramo trocado para `app` |
| "o kit precisa de um lugar novo para registrar o hook" | `ConfiguraFilamentGlobal` já é o registro global (PanelSwitch, seletor de idioma) | reaproveitado |
| "z-index precisa ser recalculado" | o chat do kit é **idêntico** ao do mini-pff (`bottom-6 right-6 z-40 h-14`, slide-over `z-50`) | valores portados sem recálculo |

### Auditoria Ponytail

| # | Sugestão | Aplicada? |
|---|---|---|
| 1 | Não instalar o pacote — degrau 3/5 da escada | sim, ADR-01 |
| 2 | Um registro global em vez de três por painel | sim |
| 3 | Blade puro em vez de componente Livewire | sim |
| 4 | Não criar config para o gatilho de 400px — número no Blade basta | sim |

---

## Blockers

Nenhum.

## Desvios do Plano

- **O CT-B foi escrito e removido.** O passo 3 do PRD previa
  `tests/Browser/VoltarAoTopoTest.php`. Ele falhou em **três** tentativas, sempre no
  `assertVisible` após o scroll programático:

  | # | O que mudou | Resultado |
  |---|---|---|
  | 1 | `/infra`, `window.scrollTo(0, 1200)` | timeout de 45s |
  | 2 | + `document.body.style.minHeight = '4000px'` | timeout, 0 asserções |
  | 3 | `/admin` + o mesmo alongamento | timeout no `assertVisible` |

  Causa provável, **não confirmada**: o `scrollTo` programático não produz o evento que o
  `@scroll.window` escuta neste layout. A sondagem que mediria `scrollHeight` e `scrollY`
  estourou o tempo do ambiente.

  **Decisão**: remover, e declarar a lacuna no `04-casos-de-teste.md` em vez de entregar
  teste instável ou teste que passa sem provar. O que mitiga: a varredura de 55 telas roda
  `assertNoJavaScriptErrors()` em todas as telas onde o botão agora está — Alpine quebrado
  neste hook derruba os 55 cenários. Fica sem prova só a transição invisível → visível.

- **`script()` do pest-plugin-browser devolve `mixed`, não a página.** Encadear depois dele
  estoura `Call to a member function assertVisible() on array`. Descoberto na primeira
  tentativa do CT-B.

## Notas de Implementação

- **O kit já esperava esta feature.** O comentário do widget de chat, escrito antes,
  diz: *"se o seu painel já tiver algo ali (botão \"voltar ao topo\", chat de suporte),
  ajuste o `bottom-*` aqui"* (`assistente-chat-widget.blade.php:12-13`).

- **Convergência independente sobre o pacote.** O `mini-pff` recusou
  `gboquizosanchez/filament-scroll-to-top` lendo o código-fonte; a varredura dos 547 plugins
  deste kit o classificou em "coberto por recurso nativo — um render hook `BODY_END` de 20
  linhas de Blade". Duas análises separadas, mesma conclusão.

- **`FilamentView` e `PanelsRenderHook` não estavam importados** em
  `ConfiguraFilamentGlobal` — o arquivo só tinha `FilamentAsset`. Três imports novos:
  `FilamentView`, `PanelsRenderHook` e `Illuminate\Contracts\View\View`.

## Retrospectiva

**Funcionou bem**

- Ler a wiki de origem antes do código. O `mini-pff` já tinha feito a análise do pacote, com
  o código-fonte citado — repetir isso teria custado uma hora para chegar na mesma decisão.
- Comparar o chat dos dois projetos antes de recalcular z-index e offset. São idênticos, e os
  valores portaram direto.
- CT-02, com as cinco telas de vendor, é o caso que dá sentido à arquitetura. Sem ele, a
  suíte não distinguiria hook global de registro por painel.

**Faltou no plano**

- O PRD tratou o CT-B como certo, sem prever que scroll programático é justamente o tipo de
  coisa que falha em ambiente headless. Três tentativas depois, a decisão foi remover — e o
  plano não tinha um critério de parada escrito.
- Não previ que `script()` quebra o encadeamento. Uma linha de leitura da assinatura teria
  evitado a primeira falha.

## Candidatos a Rule de Projeto

**Nenhum proposto.**

O que esta feature ensina — "render hook global sem `scopes:` alcança tela de vendor" — já está
escrito no lugar onde é usado (`ConfiguraFilamentGlobal`) e fixado por CT-02. Uma rule repetiria
o que o teste já garante.
