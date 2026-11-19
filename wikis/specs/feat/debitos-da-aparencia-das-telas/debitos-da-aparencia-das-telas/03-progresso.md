# Progresso — Débitos G, H, J e K da aparência das telas de acesso

**Estado**: em revisão — 2026-11-18

**Base do PR**: `main`

## 1. G — tema escuro
- [x] Login, cadastro e recuperação de senha, no clássico e no painel de marca, no tema escuro a 1440×900 — seis capturas por um teste temporário (`visit()->inDarkMode()->screenshot()`), apagado depois, e lidas. Todas legíveis: texto claro sobre fundo escuro, campos e botões com borda visível, o laranja de ação com contraste. O contraste em si é medido pelo axe do CT-B21, linhas "escuro", 2026-11-18
- [x] Achado da leitura — o `/app/register` saiu sem a arte e sem o alternador nos dois layouts. Sonda: o HTML de uma requisição nova tem os dois (`fi-auth-media` 4 vezes, `theme-switcher` 9 vezes). No navegador, sem o aquecimento, a arte aparece (`block 1008`); com o aquecimento (`$this->get()` em `/admin/...` antes), a página vem `no-media`. É estado do painel corrente vazando entre requisições do mesmo processo, o mesmo do QA-01 da origem; em produção cada requisição é um processo novo, e o kit não usa Octane (`grep -c "octane\|frankenphp" composer.json`: 0). A primeira versão do CT-B21 aquecia as três telas no `beforeEach` e rodava o axe do cadastro nessa página incompleta; o step 9 trocou pelo aquecimento só da tela visitada, e o CT-B21 agora exige o alternador presente (as 4 linhas do cadastro passam), 2026-11-18

## 2. H — navegador
- [x] `tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` — CT-B21 (axe, 12 linhas), CT-B22 (teclado no clássico), CT-B23 (reduzir movimento). O helper `visitaComMovimentoReduzido()` foi para o `tests/Pest.php` (dois arquivos o usam), 2026-11-18
- [x] Achado do axe (P-02) — `aria-required-parent` crítico nos botões `menuitemradio` do alternador de tema, em todo login e recuperação, nos dois layouts e nos dois temas (8 de 12 vermelhos). Correção: a sobrescrita `resources/views/vendor/filament-auth-designer/components/partials/theme-toggle.blade.php` com `role="menu"` no wrapper (o `aria-label` igual ao do grupo de dentro saiu no step 9, para não ser lido duas vezes). Depois dela, 12/12, 2026-11-18
- [x] Falsificabilidade — sem o `role` (mutante H1), 8 de 12 caem na primeira versão e 12 de 12 depois do step 9 (com o cadastro completo), e o CT-114 cai; sem a condição de "reduzir movimento" na regra `@view-transition`, o CT-B23 cai, 2026-11-18
- [x] CT-B13 da origem (que usa o helper movido) — `vendor/bin/pest tests/Browser/AparenciaDasTelasDeAcessoTest.php --filter=CT-B13`: 1/1, 2026-11-18

## 3. J — suíte
- [x] `vendor/bin/pest --tia --parallel --processes=3 --exclude-testsuite=Browser --compact` sobre `276cdc21`: 6206 testes, 6200 passaram, 3 pulados, 3 falhas — as três do `SiteDeDocumentacaoTest` (badge e contagens do README, que a entrega mudou). Corrigidas (badge 2.689, 265 arquivos de teste, 95 features): `vendor/bin/pest tests/Kit/SiteDeDocumentacaoTest.php`: 68/68. O Pest avisou "TIA does not apply to partial runs": com `--exclude-testsuite`, o `--tia` roda tudo o que foi selecionado, sem seleção por impacto, 2026-11-18
- [x] `vendor/bin/pest --testsuite=Browser --compact` sobre `c530039c` — 275 testes, 223 passaram, 52 pulados, 0 falhas, 556 s, 2026-11-18
- [x] Depois das correções do step 9 — `vendor/bin/pest tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php`: 14/14; `vendor/bin/pest tests/Kit/AparenciaDasTelasPorTelaTest.php tests/Kit/AparenciaDasTelasDeAcessoTest.php tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RoteiroDeAtualizacaoTest.php tests/Kit/KitUpdateTest.php`: 630/630 depois do badge 2.690, 2026-11-18
- [x] Cenários 1–4 — **corrigido no gate, QA-01**: a rodada `f1`..`f4` de 2026-11-15 foi sobre `e267f909`, anterior às correções da v0.53.1 (8 arquivos de `app/`/`resources/` mudaram até a tag), e não sobre a v0.53.1, como este item dizia. O que esta entrega acrescenta às rotas de entrega é um arquivo novo, a sobrescrita do alternador: `git archive HEAD | tar -t` lista `resources/views/vendor/filament-auth-designer/components/partials/theme-toggle.blade.php` e `tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` (rota do `create-project`, governada pelo `.gitattributes`), e `KitUpdate::CAMINHOS_DO_KIT` tem `resources/views/vendor/filament-auth-designer`, `tests/Browser`, `tests/Kit` e `tests/Pest.php` (rota do `kit:update`; `KitUpdateTest` CT-06/CT-07 verdes). Os quatro cenários completos sobre a tag publicada ficam como débito (`## Débitos`), 2026-11-18

## 4. K — mutação por amostragem
- [x] Script de mutantes — (um por vez, roda o CT esperado, restaura o arquivo; `git status` limpo depois), 2026-11-18:

| Mutante | Arquivo | CT | Resultado |
|---|---|---|---|
| M134 — a regra `@view-transition` sem a condição da opção | `painel-de-marca.blade.php` | CT-98 | cai (1 de 5) |
| M135 — a regra sem "reduzir movimento" | `painel-de-marca.blade.php` | CT-98 | cai (3 de 5) |
| M136 — sem a cópia da opção do login para as telas próprias | `AparenciaDasTelasDeAcesso.php` | CT-99 | **sobrevive** (4/4), e também aos 391 casos dos quatro arquivos da aparência |
| M140 — a raiz gravada sem a opção, com o login no clássico | `AparenciaDasTelasDeAcesso.php` | CT-102 | cai |
| M142 — a organização sem a opção da instalação | `VesteAparenciaDasTelas.php` | CT-104 | cai (2 de 2) |
| M145 — a página que participa não pula a transição | `painel-de-marca.blade.php` | CT-107 | cai |
| H1 — o alternador sem `role="menu"` | `theme-toggle.blade.php` | CT-B21, CT-114 | cai (8 de 12 na primeira versão; 12 de 12 e o CT-114 depois do step 9) |

- [x] O M136 sobrevive porque a página também aplica a opção da instalação a toda tela (P-35, em `VesteAparenciaDasTelas::aparenciaDaTela()`) — duas defesas da mesma regra. Com as duas fora juntas, o CT-111 (novo) cai, 2 de 2. Registrado como equivalente quando sozinho, 2026-11-18

## 5. K — revisão adversarial do Adendo 4
- [x] `fw-adversario-ct` com só o `00`, o `04` e o `05` do Adendo 4 da origem — insuficiente, 12 brechas. Triagem com o código à vista no `04` da origem (`## Revisão adversarial do Adendo 4`): 6 lacunas de teste viraram CT-110..CT-113 (verdes, 11 casos; os mutantes M149, M152, M153 e o par M151 caem), 5 refutadas pela leitura e o ADV-E5 como débito, 2026-11-18

## 6. Registro na origem
- [x] `03` da origem, `## Débitos`; `04` da origem, contagens — (114 cenários, 129 mutantes) e a seção da revisão, 2026-11-18

## Testes
- [x] `vendor/bin/pest tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` — 14/14 (12 do CT-B21, CT-B22, CT-B23)
- [x] `vendor/bin/pest tests/Kit/AparenciaDasTelasPorTelaTest.php --filter='CT-11[0-3]'` — 11/11

## Tickets
Não fatiado — 2026-11-18: débitos de verificação, uma correção de uma linha

## Débitos
- **ADV-E5**: a lista de destinos da transição é fixa e não olha o layout da tela de destino (detalhe no `03` da origem). Decisão do mantenedor
- **Mutantes M3 e M4 do `04`** (tabindex na senha, contraste no escuro): previstos, não medidos
- **Os quatro cenários de `wikis/checklist-de-release.md` sobre a v0.55.1 publicada** (QA-01 do gate): não rodaram nesta entrega nem nas v0.54.0 e v0.55.0. Nenhum cenário passou pelas correções da v0.53.1 (a última rodada é a `f1`..`f4` sobre `e267f909`). A entrega do arquivo novo desta versão está provada pelas duas rotas (passo 3); a rodada completa fica para depois da tag
- **A arte do painel no cadastro** não é afirmada pelo CT-B21 (só o alternador); com o aquecimento por tela ela deve estar presente, mas não foi observada no navegador

## Verificação Final

- `rastreabilidade.sh`: RQ-05..RQ-08 e P-01, P-03, P-04 saem "sem CT". É declarado: são execuções e registros (a suíte inteira, os cenários 1–4, a amostragem de mutantes, a revisão adversarial, o registro na origem), não comportamento. A evidência é o comando e a saída nos passos 3, 4, 5 e 6. Um CT para elas seria um teste do próprio teste. RQ-01..RQ-04 e P-02 estão no `## Mapa de Regras` do `04`
- `ids-ct.sh` desta wiki com `tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` acusa o CT-114, que mora em `tests/Kit/AparenciaDasTelasDeAcessoTest.php`, ao lado dos CT da origem; com os dois arquivos, acusaria os CT da origem como sem cenário aqui. Pela origem (`ids-ct.sh` com os arquivos da aparência e o de acessibilidade): limpo
- `checkbox-sem-evidencia.sh`, `citacoes.sh`, `conformidade-rules.sh main`: limpos

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `config.md` | `config/**` | n.a. | só `kit.version` 0.55.1 |
| `kit.md` | `tests/Kit/**` | n.a. | nenhum `Livewire::test` novo; CT-110..CT-114 leem arquivo ou HTML |
| `specs.md` | `wikis/specs/**` | aplicada | a sobrescrita foi escrita depois de ler o partial do vendor (o CT-114 compara os dois); citação de vendor por símbolo; teste citado pelo ID |
| `testes-browser.md` | `tests/Browser/**` | aplicada, com uma exceção | aquecimento pelo kernel da tela visitada; `assertPathIs` antes do conteúdo; sem `--parallel`. Exceção: seletores por classe (`.fi-auth-theme-switcher-wrapper[role="menu"]`, `.kit-painel-de-marca[data-kit-transicao]`), porque o que se afirma é a estrutura e o papel ARIA, não um texto |
| `testes.md` | `tests/**` | aplicada | `visitaComMovimentoReduzido()` vive no `tests/Pest.php` (dois arquivos o usam); a asserção de ausência no CSS (CT-110) filtra comentários; os `toContain()` não recebem mensagem |
| `views.md` | `resources/views/**` | aplicada | o comentário `{{-- --}}` da sobrescrita não tem diretiva do Blade |
| `vendor.md` | `resources/views/vendor/**` | aplicada | `resources/views/vendor/filament-auth-designer` já está em `KitUpdate::CAMINHOS_DO_KIT`; a view difere da do pacote (`KitUpdateTest` CT-06/CT-07 verdes) |

## Despachos

| # | Agente | Modelo | Tarefa | Resultado | Custo |
|---|---|---|---|---|---|
| 1 | `fw-adversario-ct` | herdado do agente | revisão adversarial dos CT do Adendo 4 da origem | 12 brechas, triadas no `04` da origem | 40.968 tokens · 133 s |
| 2 | `fw-revisor-diff` | herdado do agente | passe de eixos do diff | RD-01..RD-03 (Minor, Minor, Cosmético) | 61.311 tokens · 187 s |
| 3 | `/code-review` (fork) | sessão | revisão do diff | 9 achados | 86.160 tokens · 140 s |
| 4 | `codex exec` | não exposto | revisão do diff | 3 achados (Média) | 65.101 tokens |
| 5 | `general-purpose` | sonnet | roteiro `v0.55.1` e cópia no `agentes-e-skills.md` | entregue; faltava `### Defaults mudados` no CHANGELOG (a sessão acrescentou) | 82.924 tokens · 125 s |

- O `04` foi escrito pela sessão, sem sub-agente da `feature-test-design`: sem cegueira de derivação. Os três cenários repetem o oráculo já fixado na origem (RQ-12, P-33)

## Revisão do Diff (step 9)

Três revisores sem o PRD: `fw-revisor-diff` (eixos), `/code-review` (fork, nível medium) e o codex. Achados e destino:

| Achado | Origem | Destino |
|---|---|---|
| O alternador anunciado como menu continua fora do teclado (`tabindex="-1"` nos botões do Filament) | RD-01, code-review 1, codex 1 | débito do vendor, já registrado na origem (`## Débitos`); a correção do axe não piora o teclado |
| O axe do cadastro rodava numa página sem a arte e o alternador (aquecimento das três telas) | code-review 2 e 7 | corrigido: aquecimento só da tela visitada; o CT-B21 exige o alternador |
| `close()` dos botões fora de um dropdown daria erro de JavaScript | code-review 3 | refutado por medição: clique no tema escuro em `/admin/login`, `assertNoJavaScriptErrors()` verde e o tema muda |
| CT-B22 não conferia a ordem nem o formulário | RD-02, code-review 4 | corrigido: senha antes do botão de enviar do mesmo formulário |
| Nada na suíte Kit guarda a sobrescrita | code-review 5 | CT-114 (Kit): a sobrescrita é o partial do pacote mais o `role` |
| Rótulo "Tema" lido duas vezes (wrapper e grupo) | code-review 6 | corrigido: o `aria-label` saiu do wrapper; CT-B21 12/12 sem ele |
| CT-113 preso ao formato do `@js` | code-review 8 | mantido: o teste falha alto se o formato mudar; o `fw-revisor-diff` conferiu o decode |
| O helper de "reduzir movimento" usa propriedades privadas do plugin | code-review 9 | mantido (já existia); agora num lugar só |
| CT-B23 sem controle positivo | codex 2 | corrigido: a página participa da transição (regra e painel marcado) antes de medir |
| CT-110 lia só o primeiro bloco do `kit-painel` | codex 3 | corrigido: todos os blocos |
| IDs CT-01..CT-03 sem o prefixo `CT-B` em `tests/Browser` | RD-03 | corrigido: CT-B21..CT-B23 |


## Quality Gate

| Ciclo | Veredito | Data |
|---|---|---|
| 1 | REPROVADO → especificação (QA-01 Major; QA-02, QA-03 Minor; QA-04, QA-05 Cosmético); G nível 3 e K2 não verificadas pelo gate; independência parcial | 2026-11-18 |
| 2 | REPROVADO → especificação (QA-06 Major: a correção do QA-01 não chegou ao `01` nem ao `## Débitos` da origem; QA-07 Cosmético) — teto do perfil padrão, escalado ao solicitante | 2026-11-18 |

Tratamento do ciclo 1:

| QA | Destino | O que foi feito |
|---|---|---|
| QA-01 | 1 | o item dos cenários diz o commit real (`e267f909`); P-01 revista no `00`; a entrega do arquivo novo provada pelas duas rotas; os quatro cenários sobre a tag como débito explícito |
| QA-02 | 1 | o comentário de contagem fecha na mesma linha; a R4, o CT-114 e o índice voltam ao documento; um só `## Mutantes Previstos` |
| QA-03 | 1 | o débito do cadastro virou o resíduo real (a arte não é afirmada); a linha do H1 diz 12 de 12 |
| QA-04 | 1 | 114 cenários no cabeçalho do `04` da origem e aqui |
| QA-05 | 1 | o motivo da revisão adversarial é o perfil padrão sem Impacto 3 |

Tratamento do ciclo 2 (depois do veredito, sem ciclo 3):

| QA | Destino | O que foi feito |
|---|---|---|
| QA-06 | 1 | `01` (linha do RQ-06 na Cobertura e o passo 3) com a marca de alteração; `## Débitos` da origem diz que os cenários não foram pagos e ganhou o débito dos quatro cenários sobre a v0.55.1. O `grep` do repro não acha mais alegação sem marca |
| QA-07 | 1 | o comentário de contagem do `04` diz o padrão adaptado para CT-B |

## Blockers

- **Gate no teto de ciclos** (2026-11-18): o ciclo 2 reprovou só por documentação (QA-06, QA-07), corrigida depois do veredito; o código não mudou desde o ciclo 1 (`git diff --stat a50089aa HEAD -- . ':!wikis'` vazio). Não há ciclo 3; o aceite final é do solicitante, no PR


No `06` do ciclo 2, a citação `arquivo:linha` sem símbolo do CT-114 foi reescrita como prosa ("`tests/Kit/AparenciaDasTelasDeAcessoTest.php` (o CT-114)") para o `citacoes.sh` passar; o conteúdo do achado não mudou.
