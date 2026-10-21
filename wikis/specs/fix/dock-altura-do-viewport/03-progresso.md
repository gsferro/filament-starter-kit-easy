# Progresso — Botão do dock nasce e some se não for movido

**Estado**: concluída — 2026-10-20

**Base do PR**: `main` · **Processo**: fix enxuto com teste (escolha do solicitante), sem quality gate independente

## Processo do pedido

- [x] Entender o motivo — `innerHeight` 867 × `clientHeight` 780 medidos no navegador do solicitante; botão em y = 787 depois do reposicionamento, 2026-10-20
- [x] Simular no Playwright — sondas de 40 s em `/app` e `/admin`, 1280×800 a 1920×1080, página longa (não reproduz com `innerHeight == clientHeight`); CT-B01 reproduz forçando a diferença: bottom 863 com a área visível até 800, 2026-10-20
- [x] Resolver de vez — correção na fonte (`b()`), cobrindo os três chamadores, 2026-10-20
- [x] Issue e PR upstream — HoceineEl/filament-quick-action-dock#2 e #3 (`npm test` 18/18; e2e novo não executado localmente, declarado no PR), 2026-10-20

## 1. Cópia corrigida e troca do asset

- [x] `public/js/kit/quick-action-dock.js` gerado do `dist/` com uma substituição — `grep -c "clientHeight||window.innerHeight"` = 1, 2026-10-20
- [x] `DockDeAtalhos::servirScriptCorrigido()` no `KitServiceProvider::boot()` — PHPStan 0 erros, 2026-10-20
- [x] `public/js/kit` em `CAMINHOS_DO_KIT` — `KitUpdateTest` 97/97, 2026-10-20

## 2. Testes

- [x] `tests/Browser/DockAlturaDoViewportTest.php` — 3/3 com a cópia; 1/3 com o `dist/` original (`Failed asserting that 863 is equal to 800 or is less than 800`, CT-B01 nos dois painéis), 2026-10-20
- [x] `tests/Kit/DockScriptCorrigidoTest.php` — 2/2, 2026-10-20

## Revisão do Diff

`/code-review high main...HEAD` (sub-agente do comando), 9 achados:

| # | Achado | Destino |
|---|---|---|
| 1 | `apply()`/`R()` usa `b()` com `innerHeight` (direção do menu) | corrigido — correção movida para `b()` |
| 2 | `prePlace` sem cobertura (pisca no primeiro paint) | corrigido — idem |
| 3 | docblock do chat órfão em `ConfiguraFilamentGlobal` | saiu junto com o embrulho |
| 4 | sem guarda se o `env` sumir num bump | saiu junto com o embrulho; a guarda nova é o CT-01 Kit |
| 5 | `this` perdido no embrulho | saiu junto com o embrulho |
| 6 | CT-B esperava `wait(0.3)` | corrigido — sentinela no `--qad-transform` |
| 7 | guarda da #170 ignorava trait/interface/enum e namespace × pasta | corrigido — CT-01 e CT-02 de `FixturesComNamespaceTest`, provados com violações temporárias |
| 8 | altitude: embrulho global de método privado | corrigido — cópia do bundle |
| 9 | cópias cruas do sentinel são dispensáveis | rejeitado nesta entrega: apagar o publish muda o que o kit instala, e o `kit:update` não apaga nos projetos atualizados — débito declarado |

## Notas de Implementação

- A primeira tentativa (embrulho do `Alpine.data` no `HEAD_END`, commit `e45a6b94`) passou no CT-B e ficou incompleta; o `/code-review` a pegou antes do PR
- Calcular o `src` no boot quebrou o dock no servidor do `pest-plugin-browser` (o script não carregava): o `src` passou para a renderização
