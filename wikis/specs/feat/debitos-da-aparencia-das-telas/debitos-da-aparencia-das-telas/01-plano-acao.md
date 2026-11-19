# Plano de Ação — Débitos G, H, J e K da aparência das telas de acesso

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: débito de verificação
- **Wiki ancestral**: `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` (o `06-relatorio-qa.md` dela, seção *Não Verificado*, define os quatro débitos)
- **Motivo**: fechar com evidência as verificações que o gate da wiki de origem deixou em aberto
- **Toca infra compartilhada?**: sim. O helper `visitaComMovimentoReduzido()` sai do arquivo de teste de origem e vai para o `tests/Pest.php`, porque agora dois arquivos o usam (`.ai/rules/testes.md`). Um partial do vendor ganha sobrescrita em `resources/views/vendor/` (`.ai/rules/vendor.md`)

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | G: tema escuro conferido visualmente | 1 | captura e leitura; não vira teste (P-01 da origem: visual não tem oráculo automático além do axe) |
| RQ-02 | H: axe nas telas, nos dois layouts | 2 | CT-B21 |
| RQ-03 | H: percurso por teclado | 2 | CT-B22 (clássico); o painel de marca já é o CT-B03 da origem |
| RQ-04 | H: "reduzir movimento" na transição | 2 | CT-B23 |
| RQ-05 | J: suíte inteira e `--tia` | 3 | P-01 |
| RQ-06 | J: cenários 1–4 | 3 | P-01, a rodada `f1`..`f4` de 2026-11-15 |
| RQ-07 | K: mutação por amostragem manual | 4 | P-03 |
| RQ-08 | K: revisão adversarial do Adendo 4 | 5 | `fw-adversario-ct` |
| P-02 | achado do axe no kit é corrigido | 2 | sobrescrita do `theme-toggle` |
| P-04 | resultado também na wiki de origem | 6 | — |

## Objetivo

Pagar os quatro débitos com comando e saída registrados. Nenhum comportamento muda, exceto o que o
axe acusar nas telas do kit.

## Análise dos Arquivos Existentes

- `tests/Browser/AparenciaDasTelasDeAcessoTest.php`: tem os CT-B da origem e o helper `visitaComMovimentoReduzido()`. O helper vira compartilhado
- `resources/views/filament/auth/painel-de-marca.blade.php`: a regra `@view-transition` dentro de `@media (min-width: 1024px) and (prefers-reduced-motion: no-preference)`. É o que o CT-B23 exercita
- `vendor/caresome/filament-auth-designer/resources/views/components/partials/theme-toggle.blade.php`: o wrapper do alternador de tema, sem `role`. Os botões do Filament dentro dele são `menuitemradio`

## Estrutura de Implementação

1. **G, tema escuro**: capturar login, cadastro e recuperação, no clássico e no painel de marca, no tema escuro a 1440×900, e ler as capturas. Teste temporário, apagado depois
2. **H, navegador**: o arquivo `tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` com o CT-B21 (axe: layout × tela × tema), o CT-B22 (teclado no clássico) e o CT-B23 (reduzir movimento com a transição ligada). O que o axe acusar do kit é corrigido (P-02)
3. **J, suíte**: `pest --tia --parallel --processes=3 --exclude-testsuite=Browser` e a suíte Browser sobre o commit desta entrega. Os cenários 1–4 fecham pela rodada registrada (P-01)
4. **K, mutação**: um mutante por vez nos arquivos do Adendo 4 e na correção do passo 2. Roda o CT que deveria matá-lo e restaura o arquivo
5. **K, revisão adversarial**: `fw-adversario-ct` sobre o `00` (Adendo 4) e o `04`/`05` (Adendo 4) da origem
6. **Registro**: `## Débitos` e `## Quality Gate` do `03` da origem apontam para esta wiki

## Decisões de Desenho

- O `role="menu"` vai no wrapper do partial do auth-designer, e não nos botões do Filament. O wrapper é o pai que faltava, e o partial do pacote é pequeno de sobrescrever. Os botões do `theme-switcher` também são usados no menu do usuário, onde o pai já existe
- O teclado no painel de marca não é repetido: o CT-B03 da origem já o cobre

## Superfície de UI

Nenhuma tela nova. A única mudança perceptível ao leitor de tela é o alternador das telas de acesso
anunciado como menu (o rótulo "Tema" é o do grupo do Filament).
