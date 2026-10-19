# Casos de Teste de Browser — agentes-ia-no-settings: Agentes de IA ligados pelo settings, por painel

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, no mesmo processo.
> Costura: `browser` — linha "G8 Pilha de botões flutuantes" de `## Costuras de Teste` do `04` (confirmada pela sessão, 2026-10-19)
> Comando: `vendor/bin/pest tests/Browser/AgentesIaNoSettingsTest.php` (série, padrão conservador)
> Derivado do requisito (P-01: o chat passa a ser renderizado em todo painel da lista). Perfil **mínimo** (P2 × I1); teto 1 happy path.

## Pré-requisitos
- [ ] `npm run build` executado (sem `public/build/manifest.json` toda tela responde `ViteException`)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($usuario)` antes do `visit()`; o `get($painel)->assertOk()` antes do `visit()` boota o painel, como o `abrirPainelDaPilha()` já faz
- [ ] `MEDIR_PILHA_FLUTUANTE` e `abrirPainelDaPilha()` acessíveis a este arquivo — hoje locais de `tests/Browser/PilhaDeBotoesFlutuantesTest.php`; Q7 (era Q?3): vão para `tests/Pest.php`
- [ ] Estado da feature por `ligarAgentesDeIa(true, ['admin', 'infra'])` antes do `visit()`; dock por `ligarDock(['admin', 'app', 'infra'])` + `concederDock($papel)` (o helper da pilha já faz)

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|
| botão do chat | `[data-assistente-chat] button[aria-label="Abrir assistente"]` | sim (`MEDIR_PILHA_FLUTUANTE`) |
| gatilho do dock | `.qad-root .qad-trigger` | sim |
| voltar ao topo | `[data-voltar-ao-topo]` | sim |

---

## CT-B01: o chat entra na pilha de botões flutuantes no painel que nunca o teve

**Por que browser e não Livewire**: a presença do chat no HTML do `/admin` e do `/infra` já é o CT-16 do `04` (Pest feature HTTP). O que só o navegador prova é que o botão, renderizado no tema de um painel que nunca o desenhou, tem dimensão, fica visível e entra na pilha acima do dock sem sobrepor — posição calculada por CSS do tema e pelo JS da pilha.

Origem da geometria: o contrato da pilha de botões flutuantes já vigente (wiki `fix/pilha-de-botoes-flutuantes`, medido pelo CT-B01 de `tests/Browser/PilhaDeBotoesFlutuantesTest.php`: 16 px entre os botões, centro no eixo do gatilho, ±1 px). Esta feature não muda o contrato; estende-o a dois painéis.

```gherkin
# language: pt
Funcionalidade: Agentes de IA ligados pelo settings, por painel

  Regra: no painel da lista que nunca teve o chat, o botão dele entra na pilha de botões flutuantes

    Esquema do Cenário: [CT-B01] o chat aparece na pilha acima do dock, alinhado e sem sobrepor, no <painel>
      Dado o uso de agentes ligado com a lista ["admin", "infra"]
      E <persona> com Usar:Dock e o dock ligado, no desktop de 1280 × 800 px, tema claro
      E o dock está no canto inferior direito e a página foi rolada até o "voltar ao topo" aparecer
      Quando <persona> abre o <painel>
      Então o botão do chat está visível, com largura e altura maiores que 0
      E a base do botão do chat fica 16 px (±1) acima do topo do dock
      E o centro horizontal do botão do chat fica no eixo do gatilho do dock (±1 px)
      E o retângulo do chat não intercepta o do dock nem o do voltar ao topo

      Exemplos:
        | painel  | persona                       |
        | /admin  | a administradora (papel admin) |
        | /infra  | a Íris (papel infra)           |
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `ligarAgentesDeIa(true, ['admin', 'infra'])` + `$pagina = abrirPainelDaPilha($painel)` | o helper faz `actingAs`, `get($painel)->assertOk()`, `visit($painel)->on()->desktop()->inLightMode()`, `resize(1280, 800)`, `assertPathIs($painel)` e `assertVisible('.qad-root .qad-trigger')` |
| 1b | armar a pilha | `moverDockDaPilha($pagina)` + `rolarPaginaDaPilha($pagina)` (helpers de `tests/Pest.php`) | dock no canto inferior direito, assentado; página rolada 800 px, "voltar ao topo" visível — sem isso o dock abre centralizado em baixo e o "voltar ao topo" mede 0×0, e a geometria de 16 px e eixo comum não vale *(alterado em 2026-10-19: causa (a) do executor-ctb)* |
| 2 | o chat está de pé | `$pagina->assertVisible('[data-assistente-chat] button[aria-label="Abrir assistente"]')` | botão do chat visível |
| 3 | medir a pilha | `$m = $pagina->script('() => '.MEDIR_PILHA_FLUTUANTE)` | retângulos de `dock`, `chat`, `voltar` |
| 4 | afirmar | `expect($m['chat'])->not->toBeNull()`; `expect($m['chat']['visivel'])->toBeTrue()`; `abs($m['dock']['top'] - $m['chat']['bottom'] - 16) <= 1`; `abs($m['chat']['centroX'] - $m['gatilho']['centroX']) <= 1`; nenhuma interseção entre `chat` e `dock`/`voltar` | — |
| 5 | apoio | `$pagina->assertNoJavaScriptErrors()` | console limpo |

**Assertions**: `assertPathIs` primeiro (no helper) · o oráculo é a geometria medida (passos 2–4), nunca só `assertNoJavaScriptErrors()` · sem `wait()`; a espera é o `assertVisible` do botão do chat

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o estilo do chat existe só no tema do `/app` (no `/admin`/`/infra` o botão nasce sem dimensão ou sem `position: fixed`) | CT-B01 (as duas linhas) | `chat.visivel` `true` e largura/altura > 0, base 16 px acima do dock; o mutante mede 0 ou cai no fluxo da página |
| M2 | o JS da pilha só procura o chat no layout do `/app` (no `/admin` ele fica na posição original, sob o dock) | CT-B01 | `dock.top − chat.bottom = 16 ± 1`; o mutante deixa o chat a 24 px do canto, sobreposto ao gatilho |
| M3 | o render global do chat entra no `BODY_END` depois da raiz do dock e herda empilhamento que o põe atrás dele | CT-B01 | retângulos sem interseção e `chat.visivel` `true`; o mutante o deixa coberto (interseção com o dock) |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | chat do Assistente (botão flutuante + slide-over) em toda tela dos painéis da lista; depende de JS (pilha, slide-over) | no `/admin` e no `/infra` o botão aparece com 56×56 px, base 16 px acima do dock, centro no eixo do gatilho, sem interseção com o dock nem com o "voltar ao topo"; console sem erro de JS. O slide-over não é exercitado pelo CT-B01 | ✅ | `vendor/bin/pest tests/Browser/AgentesIaNoSettingsTest.php` → 2/2, 34 asserções, 2026-10-19 |
| 2 | gate de CT-B: a pilha com o chat num painel que nunca o teve (`/admin`, `/infra`) | CT-B01 verde nos dois painéis | ✅ | idem |
