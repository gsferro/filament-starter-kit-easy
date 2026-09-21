# Progresso — CSS quebrado na v0.37.0

## Natureza da Wiki

- **Tipo**: correção
- **Origem do diagnóstico**: o usuário entregou a causa raiz **já medida** e instruiu explicitamente
  a não reinvestigar. A wiki registra a verificação independente, não uma segunda investigação.

## Diagnóstico

- [x] Causa raiz identificada — inversão de cascade layer pelo `croustibat/filament-jobs-monitor`, 2026-09-21
- [x] Confirmação independente de que a folha do plugin está em `@layer components` — `grep` em `public/css/croustibat/.../filament-jobs-monitor-styles.css`, 2026-09-21
- [x] Reprodução em navegador real, instalação limpa — `@layer components;` como primeira declaração; botão `padding 0`, fundo transparente, `radius 0`, altura 20px, 2026-09-21

## Correção

- [x] `KitServiceProvider::configureOrdemDasCascadeLayers()` criado e chamado antes de `configureCorrecoesDeCss()` — 2026-09-21
- [x] Docblock no padrão do provider (causa, medição, alternativa recusada) — 2026-09-21
- [x] Docblock órfão "Health checks padrão…" removido — 2026-09-21

## Guarda

- [x] `tests/Kit/OrdemDasCascadeLayersTest.php` criado — 4 casos, 17 asserções, 2026-09-21
- [x] `[CT-01]` exige que a **primeira** ocorrência de `@layer` seja a do kit, nos três painéis
- [x] `[CT-02]` controle positivo do detector — lê as folhas em runtime, reprova se nenhum plugin declarar mais `@layer`

## Rule

- [x] Seção nova em `.ai/rules/css-filament.md` — 2026-09-21
- [x] Glob ampliado para alcançar `app/Providers/**` (a correção mora lá, não em `resources/css/`) — 2026-09-21
- [x] `.ai/rules/index.md` atualizado — 2026-09-21

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent` — `passed`, 2026-09-21
- [x] `php artisan test --compact tests/Kit/OrdemDasCascadeLayersTest.php` — **4/4, 17 asserções**, 2026-09-21
- [x] Bateria de regressão pedida — `OrdemDasCascadeLayers` + `CorrecaoDeCorPrimaria` + `TelasDeAutenticacao`: **35/35, 121 asserções**, 2026-09-21
- [x] **Falsificabilidade da guarda** — `git stash` da correção: **3 de 4 casos reprovam sem ela**, exit 1; com ela, exit 0, 2026-09-21
- [x] **Verificação em navegador real** (Playwright, Filament v5.8.4): `paddingInline 0px → 12px`, `paddingBlock 0px → 8px`, fundo transparente → `oklch(0.828 0.189 84.429)`, `borderRadius 0px → 8px`, altura `20px → 36px`, 2026-09-21
- [x] CHANGELOG registrado como `### Corrigido`, citando o pacote causador e a data da medição, 2026-09-21

## Desvios do Plano

- **O MCP do Playwright não está configurado nesta sessão.** RQ-05 nomeia a ferramenta; foi atendido
  pelo **pacote npm `playwright`**, decidido com o usuário em 2026-09-21. Entrega a mesma capacidade
  de navegação e ainda permite ler estilo computado, que é o que o oráculo exigia.
- **O MCP do `laravel-boost` caiu durante a sessão.** `CLAUDE.md` manda gravar rule pela tool
  `record-rule`; com o servidor fora, a rule foi escrita à mão em `.ai/rules/css-filament.md` e o
  `.ai/rules/index.md` foi atualizado no mesmo commit, que é o que o `record-rule` faria. **Ao
  reconectar, vale conferir se o Boost regenera o índice e não duplica a entrada.**
- **A worktree precisou de `composer install` próprio.** Junction de `vendor` para o repositório
  principal quebra o autoload (os caminhos do Composer são absolutos), e o sintoma é
  `Container::publicPath()` indefinido — não óbvio. Registrado aqui porque a próxima worktree vai
  esbarrar nisso.

## Notas de Implementação

- **Um byte de backspace entrou no regex da guarda** ao escrever o arquivo por script, no lugar da
  sequência `\b`. O padrão nunca casava e o terminal **escondia o defeito**, porque o backspace
  apagava o caractere anterior na renderização — o `sed` exibia `<link[^>]*` e o arquivo continha
  `<link` + 0x08. Só o `od -c` revelou. O `\b` era dispensável e saiu.
- **Hipóteses investigadas e refutadas por medição**, antes de o usuário entregar o diagnóstico —
  ficam registradas para ninguém refazer:
  - *O `page-header.css` vaza*: **não**. Todos os seletores descendem de `.fph-root`, e o modo
    compact nunca é ligado (`"mode":"normal","compactBelow":null` no render real).
  - *O tema do `dotswan/filament-laravel-pulse` sequestra o `:root`*: **não**. Ele declara um tema
    Tailwind v4 inteiro com 112 variáveis em `:root,:host`, mas os valores são **idênticos** aos do
    Filament (`--spacing:.25rem`, `--text-base:1rem`, `--radius-md:.375rem`), e o `--font-sans:Figtree`
    dele só é lido dentro do próprio arquivo.
  - *A v0.37.0 mudou CSS de aplicação*: **não**. O diff `v0.36.1..v0.37.0` toca dois `.css`, ambos do
    site de documentação.

## Débito Registrado (fora do escopo desta entrega)

- **Reincidência do `ca51ad7` ainda aberta.** Duas folhas carregadas em toda página declaram
  utilitárias de paleta **sem escopo**: `filament-jobs-monitor` (22 distintas, com **RGB literal** —
  ex.: `.text-gray-500{color:rgb(107 114 128)}`) e `filament-laravel-pulse` (16, via `var()`). A
  correção de `ca51ad7` cobre 9 classes da família `primary`; o restante segue decidido pelo build do
  vendor. Produz cor errada, não layout quebrado — por isso não entra nesta correção.

## Retrospectiva

- **Funcionou bem**: transformar sintoma visual em número. O relato era "ficou péssimo"; o que
  fechou o caso foi `paddingInline: 0px → 12px` e a primeira declaração de layer do documento.
- **Faltou**: meu primeiro experimento tinha **duas variáveis** (`composer update` **e**
  `npm run build`), então não distinguia as causas. Achei uma diferença enorme e real — o tema do
  painel sumindo, 236 regras → 49 — que era **artefato do meu próprio arranjo**. Experimento com
  duas variáveis não é experimento.
