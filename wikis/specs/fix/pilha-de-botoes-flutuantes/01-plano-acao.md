# Plano de Ação — Pilha harmônica dos botões flutuantes

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feature/v1-enriquecimento-kit/voltar-ao-topo/` e `wikis/specs/main/dock-de-acoes-rapidas/`
- **Motivo**: o dock (v0.48.0) pode ir para o canto inferior direito, que o "Voltar ao topo" e o chat da IA já ocupam; e o chat, desde o esqueleto do kit, sai sem estilo nos painéis
- **Toca infra compartilhada?**: sim → `App\Providers\KitServiceProvider::configureCorrecoesDeCss()` (registro das folhas de todos os painéis) e a blade do render hook global do "Voltar ao topo". A regressão roda contra os CT do `voltar-ao-topo` e do dock

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | "Voltar ao topo" sobe acima do dock no canto inferior direito | 1, 2 | — |
| RQ-02 | teste controlado antes da adequação | — | ⚠️ fora desta entrega — cumprido antes da wiki; medição em `03`, `## Auditoria Pré-Implementação` |
| RQ-03 | chat no mesmo canto | 3 | — |
| RQ-04 | pilha harmônica, nada coberto | 1, 2, 3 | — |
| RQ-05 | v0.48.1 com tag e release | — | ⚠️ fora do conjunto de teste — é o release, feito depois do merge pelo passo 5; quem confere é o `wikis/checklist-de-release.md` |
| RQ-06 | dock no canto, chat acima, "Voltar ao topo" acima do chat | 1, 2, 3 | — |
| RQ-07 | mesmo eixo vertical | 2, 3 | — |
| RQ-08 | mesmo espaço entre vizinhos | 1 | — |
| RQ-09 | dock aberto oculta chat e "Voltar ao topo" | 1, 2, 3 | — |
| RQ-10 | botão do chat estilizado | 3 | — |
| RQ-11 | slide-over do chat estilizado | 3 | — |
| RQ-12 | pilha reage a qualquer sobreposição com a coluna | 1 | — |
| RQ-13 | reage só se o dock cruzar o lugar de um botão da pilha | 1 | Adendo 2 |
| RQ-14 | solto sobre a pilha e aberto: oculta | 1 | Adendo 2 |
| RQ-15 | barra no canto: pilha acima, visível | 1 | Adendo 2 |
| RQ-16 | 16 px entre vizinhos, em todo painel | 1, 2 | Adendo 2 |
| RQ-17 | slide-over no tema, título ≥ 4,5:1 | 3 | Adendo 2 |
| RQ-18 | barra no canto: pilha na coluna de sempre | 1, 2, 3 | Adendo 3 |
| RQ-19 | sem dock na tela de bloqueio | 6 | Adendo 4 |
| RQ-20 | sem dock em tela de login | 6 | Adendo 5 |
| RQ-21 | sem dock em tela do Auth Designer | 6 | Adendo 5 |
| RQ-22 | sem dock na escolha de painel do login unificado | 6 | Adendo 6 |
| RQ-23 | chat some nas mesmas telas que o dock | 6 | Adendo 6 |
| RQ-24 | favoritos do `/app` agrupados por organização | 7 | Adendo 7 |
| RQ-25 | sem consulta a mais para indicar a organização | 7 | Adendo 7 |
| RQ-26 | tooltip com o caminho de destino | 7 | Adendo 7 |
| RQ-27 | grupos de organização na ordem gravada | 7 | Adendo 8 |
| P-01 | sem JS ou sem dock, posições de hoje | 1, 2, 3 | substituída por P-03 |
| P-03 | alturas de hoje; "Voltar ao topo" sempre no eixo comum (30 px) | 2 | — |
| P-02 | vale em todo painel | 1 | — |

## Objetivo

Os botões flutuantes do canto inferior direito — o trigger do dock de atalhos (quando o usuário o põe ali), o chat da IA e o "Voltar ao topo" — passam a formar uma pilha: o dock no canto, o chat logo acima e o "Voltar ao topo" acima do chat, centralizados no mesmo eixo vertical e com o mesmo espaço entre eles. Com o leque do dock aberto ali, os outros dois somem até ele fechar.

O chat da IA ganha também a folha de estilo que nunca teve nos painéis: hoje ele sai como um `<button>` cru, fora da tela.

## Contexto

O teste controlado (RQ-02, registrado no `03`) mediu, com a sessão real no servidor local, em 1366×768:

- `/admin`, dock no canto inferior direito, página rolada: trigger do dock (56 px, 24 px da direita e do rodapé) **exatamente** sobre o "Voltar ao topo" (44 px, 24 px da direita e do rodapé)
- dock no canto inferior esquerdo: sem sobreposição
- `/app`: o botão do chat sai com `position: static`, 39 px, no fim do `<body>` e abaixo do rodapé — inalcançável. A blade emite utilitárias Tailwind e os painéis não carregam o build do app; é o mesmo defeito que o `voltar-ao-topo.css`, o `cards.css` e o `spotlight.css` já corrigiram para os seus componentes

A posição do dock é decidida no navegador: o pacote a grava no `localStorage` e a aplica por `transform`, nos cantos ou solto pelo arrasto. O servidor não sabe onde o dock está, então quem reorganiza a pilha tem de ser JavaScript que observa o dock.

## Análise dos Arquivos Existentes

### `resources/views/filament/voltar-ao-topo.blade.php`
- Botão `fixed`, 44 px, `right-6`, `z-20`; recuo `bottom-24` no painel `app` (por causa do chat) e `bottom-6` nos outros (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:97`). Aparece só depois de 400 px de rolagem
- Registrado no render hook `BODY_END` sem escopo, em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraBotaoVoltarAoTopo:157`
- Estilo em `resources/css/filament/voltar-ao-topo.css`, escopado em `[data-voltar-ao-topo]`; guarda em `tests/Kit/VoltarAoTopoCssTest.php`

### `resources/views/livewire/assistente-chat-widget.blade.php`
- Raiz `<div>` sem atributo de escopo antes desta feature (hoje `resources/views/livewire/assistente-chat-widget.blade.php:'data-assistente-chat':10`); botão `fixed bottom-6 right-6 z-40 h-14 w-14` (`resources/views/livewire/assistente-chat-widget.blade.php:'Abrir assistente':25`); slide-over `fixed inset-y-0 right-0 z-50 max-w-md` (`resources/views/livewire/assistente-chat-widget.blade.php:'Assistente':39`). Cerca de 120 utilitárias distintas, nenhuma com regra nos painéis
- Tem um bloco `@assets` com CSS próprio só para a resposta em markdown e a hora (`.chat-hora`, `.resposta-md`)
- Registrado só no painel `app`, em `app/Providers/Filament/AppPanelProvider.php:panel:77`

### `app/Providers/KitServiceProvider.php`
- `configureCorrecoesDeCss()` registra as folhas do kit por `FilamentAsset::register()` (`app/Providers/KitServiceProvider.php:configureCorrecoesDeCss:718`; o `Css::make('kit-voltar-ao-topo', …)` em `app/Providers/KitServiceProvider.php:'kit-voltar-ao-topo':748`). É onde entram a folha do chat e o script da pilha

### Dock (`hoceineel/filament-quick-action-dock`, vendor)
- Raiz `.qad-root`, `position: fixed`, `z-index: 30`, posição por `--qad-transform`; `data-open="true"` com o leque aberto; `data-mode` = `speed-dial` ou `bar`; trigger de 56 px e margem de 24 px (`vendor/hoceineel/filament-quick-action-dock/resources/views/dock.blade.php:'qad-root':61`; regras em `public/css/hoceineel/filament-quick-action-dock/quick-action-dock.css`)

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Um script observa a raiz do dock e publica no `<html>` uma variável CSS, `--kit-pilha-base` (a distância do rodapé onde a pilha começa), e o atributo `data-kit-pilha-oculta`; o CSS dos dois botões soma o que vem abaixo dele, com fallback na posição de hoje *(alterado em 2026-10-14: step 6 — eram duas variáveis e o script detectava o chat; o "Voltar ao topo" já sabe pelo painel se há chat)*. O script mora na blade do "Voltar ao topo", que o render hook global já põe em toda tela dos painéis *(alterado em 2026-10-14: step 5 — um `Js` do `FilamentAsset` cairia em `public/js/kit` e `resources/js/filament`, fora de `KitUpdate::CAMINHOS_DO_KIT`; a blade já viaja pelas duas rotas de entrega)* | autorização de implementar (2026-10-14) | difícil de reverter | sessão, 2026-10-14 |
| D2 | O chat ganha uma folha própria escopada em `[data-assistente-chat]`, no padrão de `voltar-ao-topo.css`/`spotlight.css`, em vez de reescrever a blade com componentes do Filament | autorização de implementar | difícil de reverter | sessão, 2026-10-14 |
| D3 | Eixo comum: o centro do botão de 56 px a 24 px da direita (52 px da borda). O "Voltar ao topo" (44 px) fica a 30 px da direita; o espaço entre vizinhos é 16 px, o mesmo que o `bottom-24` de hoje já deixava entre o chat e o "Voltar ao topo" | Q1 (RQ-07, RQ-08) | difícil de reverter | sessão, 2026-10-14 |
| D4 | "Ocupa a coluna" = o retângulo do dock cruza o lugar de algum botão da pilha nas posições de hoje, ou o espaço entre eles (do rodapé até o topo do "Voltar ao topo", na largura do botão de 56 px). A pilha então começa 16 px acima do topo do dock *(alterado em 2026-10-14: Adendo 2, RQ-13)* | Q4, Q5 (RQ-12, RQ-13) | difícil de reverter | sessão, 2026-10-14 |
| D5 | "Aberto" = `data-mode="speed-dial"` com `data-open="true"`, no canto ou solto sobre a pilha. A barra (`data-mode="bar"`) não sobe por cima da pilha: com ela na coluna, a pilha só sobe e continua visível *(alterado em 2026-10-14: Adendo 2, RQ-14, RQ-15)* | Q2, Q6, Q7 (RQ-09, RQ-14, RQ-15) | surpreendente | sessão, 2026-10-14 |
| D7 | Nas telas de autenticação, o dock e o chat **não são renderizados** (o componente não vai no HTML), em vez de escondidos por CSS | Q13 (desenho, devolvida pela derivação) | difícil de reverter | sessão, 2026-10-14 |
| D6 | Para os CT-B: a posição lembrada se simula movendo pela UI e recarregando a página (o `localStorage` do pacote sobrevive); o dock solto fora do canto vai pelo atalho de teclado do pacote (Alt + seta), e `drag()` só se funcionar | Q10, Q11 (desenho, devolvidas pela derivação) | difícil de reverter | sessão, 2026-10-14 |

## Autorização

Os botões aparecem pelas regras de hoje — o dock pela permissão `Usar:Dock` e pelos painéis ligados; o chat para usuário autenticado no `/app` — e, desde os Adendos 4 a 6, também pela tela: nenhum dos dois é renderizado quando `App\Support\TelaDeAutenticacao::rota()` diz que a rota é de autenticação (bloqueio, login, escolha de painel, telas do Auth Designer). *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*

## Rotas

Nenhuma rota nova.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Botão "Voltar ao topo" | Blade (render hook global) | toda tela dos painéis | rola a página; clica para voltar ao topo | Sim |
| Botão e slide-over do chat da IA | Livewire (render hook do `/app`) | toda tela do `/app` | abre o chat, conversa, fecha | Sim |
| Dock de atalhos (vendor) | Livewire | painéis com o dock ligado | move para um canto ou arrasta; abre e fecha o leque | Sim |

Tudo o que esta feature afirma é **layout** (posição, eixo, espaço, sobreposição, visibilidade): só o navegador prova.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum no servidor. No navegador, o script observa a raiz do dock (`MutationObserver` nos atributos), `resize`, `livewire:navigated` e o aparecimento tardio do dock e do chat no DOM.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | os mesmos de hoje; a folha nova do chat vem pelo `@filamentStyles`, como as demais do kit, e o script vai dentro da blade |
| O que é adiado, e por qual gatilho? | nada no servidor; no navegador, o recálculo roda num `requestAnimationFrame` a cada mudança observada |
| O que é memoizado por request? | nada |
| O que é cacheado entre requests? | nada |
| Custo do caminho principal | zero consulta ao banco |

## Impacto em Features Existentes

- **Voltar ao topo**: a posição passa a vir de variável CSS; o fallback mantém o `bottom-24`/`bottom-6` de hoje (P-01). A guarda `VoltarAoTopoCssTest` continua valendo
- **Chat da IA**: passa a aparecer. Nada muda no componente Livewire, só na apresentação
- **Dock**: observado pelo script e, desde os Adendos 4 a 8, alterado em `app/Filament/Dock/PluginDoDock.php` (`visible()` exclui telas de autenticação; grupos por painel × organização; tooltip com o caminho) e em `app/Support/DockDeAtalhos.php` (o atalho carrega o nome da organização) *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*
- **Testes ancestrais alterados**: em `tests/Kit/AssistenteChatWidgetTest.php` o CT-14 inverteu (`assertDontSeeLivewire` na tela de login, RQ-23); os helpers de `DockDeAtalhosTest` e `DockDeAtalhosTenancyTest` foram movidos para `tests/Pest.php` *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*

## Rollback

Remover o `Css::make` novo de `configureCorrecoesDeCss()` e reverter as duas blades e as duas folhas; apagar `app/Support/TelaDeAutenticacao.php` e reverter `PluginDoDock.php`, `DockDeAtalhos.php` e o render hook do chat em `AppPanelProvider.php`. Sem migration, sem dado. *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*

## Dependências

Nenhuma nova.

## Riscos

- **O pacote do dock mudar a raiz ou os atributos** (`.qad-root`, `data-open`, `data-mode`): a pilha deixa de reagir e volta ao fallback (P-01). Mitigação: CT-B com o dock real
- **Classe nova na blade do chat sem regra**: a guarda lê o HTML renderizado e acusa, como no `voltar-ao-topo`

## Channel de Log da Feature

Não se aplica: a lógica nova no servidor (tela de autenticação, rótulo do grupo, tooltip) não tem caminho de falha que mereça log — é decisão de render sobre dado já resolvido, sem I/O novo. *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*

## Estrutura de Implementação

### 1. Script da pilha
> Skills: `ponytail`

- **Path**: `resources/views/filament/voltar-ao-topo.blade.php` — um `<script>` idempotente ao lado do botão (render hook `BODY_END` global, então vale em todo painel) *(alterado em 2026-10-14: step 5 — sem asset `Js` novo, ver D1)*
- Lê o dock (`.qad-root[data-placed]`) e a presença do chat (`[data-assistente-chat] .bottom-6`), que decide a altura da faixa da pilha *(alterado em 2026-10-15: QA-01 — Adendos 4 a 8 e step 9)*
- Calcula a base da pilha: 24 px do rodapé; se o retângulo do dock cruza a coluna de 56 px da direita na faixa da pilha padrão (D4), a base passa a ser a distância do rodapé ao topo do dock + 16 px
- Publica no `<html>` só `--kit-pilha-base` = base. O CSS soma: o chat fica na base; o "Voltar ao topo" na base + 72 px no painel com chat (`bottom-24`) e na base nos outros (`bottom-6`)
- Com o dock em leque aberto na coluna (D5), põe `data-kit-pilha-oculta` no `<html>`; tira ao fechar
- Sem dock na página, ou sem JS, não publica nada (P-01)
- **Atende**: RQ-01, RQ-04, RQ-06, RQ-08, RQ-09, RQ-12, RQ-13, RQ-14, RQ-15, RQ-16, P-01, P-02
- **Logs**: não se aplica (navegador)

### 2. "Voltar ao topo" na pilha
> Skills: `tailwindcss-development`

- **Path**: `resources/views/filament/voltar-ao-topo.blade.php`, `resources/css/filament/voltar-ao-topo.css`
- O `bottom` vira `calc(var(--kit-pilha-base, 1.5rem) + 4.5rem)` no `bottom-24` e `var(--kit-pilha-base, 1.5rem)` no `bottom-6`: sem a variável, as posições de hoje
- Fica no eixo comum (D3): 30 px da direita
- Oculto com `html[data-kit-pilha-oculta]`
- **Atende**: RQ-01, RQ-04, RQ-06, RQ-07, RQ-09, RQ-16, P-01
- **Logs**: não se aplica

### 3. Chat da IA estilizado e na pilha
> Skills: `tailwindcss-development`, `livewire-development`

- **Path**: `resources/views/livewire/assistente-chat-widget.blade.php` (atributo `data-assistente-chat` na raiz), `resources/css/filament/assistente-chat.css` (novo), registro em `configureCorrecoesDeCss()`
- Uma regra por utilitária da blade, escopada em `[data-assistente-chat]`, com as cores das variáveis do Filament (`--primary-*`, `--gray-*`, `--danger-*`, `--warning-*`) e o tema escuro do painel; `[x-cloak]` escondido
- O `bottom` do botão lê `var(--kit-pilha-base, 1.5rem)`; oculto com `html[data-kit-pilha-oculta]`
- **Atende**: RQ-03, RQ-04, RQ-06, RQ-07, RQ-09, RQ-10, RQ-11, RQ-17, P-01
- **Logs**: não se aplica

### 4. Documentação
- `docs/{pt,en}`: as páginas que citam a posição do dock, do "Voltar ao topo" ou do chat; CHANGELOG
- **Atende**: RQ-04

### 5. Release v0.48.1
- `config/kit.php` (`version`), `docs/{pt,en}/recursos/configuracoes-do-kit.md`, CHANGELOG `[0.48.1]` com as entregas dos PRs #162, #163 e desta feature; tag `v0.48.1` e GitHub Release depois do CI verde na `main`
- **Atende**: RQ-05

### 6. Dock oculto na tela de bloqueio e nas telas de autenticação *(adendos 4 e 5)*
> Skills: `filament-development`

- **Path**: `app/Filament/Dock/PluginDoDock.php:make` — o dock some quando a página da rota corrente usa o layout do Auth Designer (`Caresome\FilamentAuthDesigner\Concerns\HasAuthDesignerLayout`, conferido com `class_uses_recursive()` na classe que o `DockContext` resolve: login, login unificado, recuperar senha, cadastro unificado, registro por convite, dois fatores) ou quando a rota é a da tela de bloqueio (`lockscreen.*`: a rota resolve o `LockerScreen` do pacote, e o `TelaBloqueio` do kit só entra por bind no container, então a regra pela trait não a alcança). O `hiddenOnAuthPages()` do pacote (rotas `{painel}.auth.*`) continua ligado *(alterado em 2026-10-14: adendo 5)*
- A regra mora num lugar só, usado pelo dock e pelo chat: um método estático que responde "a rota corrente é tela de autenticação?" (layout do Auth Designer na classe da rota, rota `lockscreen.*` ou a escolha de painel do login unificado). O chat (`AssistenteChatWidget`) passa a não renderizar nessas telas. O componente não é montado: nada dele vai no HTML de uma sessão bloqueada (Q13, desenho) *(alterado em 2026-10-14: adendo 6)*
- **Atende**: RQ-19, RQ-20, RQ-21, RQ-22, RQ-23
- **Logs**: não se aplica

### 7. Organização e tooltip nos favoritos *(adendo 7)*
> Skills: `filament-development`

- **Path**: `app/Support/DockDeAtalhos.php:montarAtalho` passa a devolver, com o atalho, o id e o nome da organização do destino (já em mãos ali: zero consulta, RQ-25); `app/Filament/Dock/PluginDoDock.php:make` registra, por painel, até `DockDeAtalhos::MAXIMO_DE_ATALHOS` grupos: o grupo `n` reúne os favoritos da `n`-ésima organização distinta, na ordem gravada, com o separador "{rótulo do painel} · {nome da organização}" (sem organização, só o rótulo do painel). O pacote esconde grupo vazio
- Cada favorito ganha `->tooltip()` com o caminho da URL de destino
- **Atende**: RQ-24, RQ-25, RQ-26, RQ-27
- **Logs**: não se aplica

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Script curto, sem dependência; regras CSS só para as classes que a blade emite.
> **Caveman `ultra`** só na conversa; wiki, código, commits e PR em prosa normal.

## Testes

> Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] testes da feature (Kit) e CT-B (browser, em série)
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] `composer test:kit` sem falha nova

## Commits

- `:bug: fix(ui): pilha harmônica dos botões flutuantes`
- `:lipstick: fix(chat): estilo do chat da IA nos painéis`
- `:memo: docs: wiki da feature pilha-de-botoes-flutuantes`
