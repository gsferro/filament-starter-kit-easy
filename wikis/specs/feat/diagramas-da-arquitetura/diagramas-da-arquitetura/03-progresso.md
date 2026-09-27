# Progresso — Diagramas da arquitetura do kit no README e no site

## 1. Correções de texto e docblocks (RQ-28, RQ-29)
- [ ] `README.md`/`README.en.md`: corrigir a senha `password` (tabela de credenciais e passo a passo de instalação), remover "passkeys" da lista do Breezy, trocar "vite juntos"/"vite together" por frase que inclua o Reverb
- [ ] `app/Support/CustomizadorDaInstalacao.php:295`: texto do resumo do `kit:install` para dizer que a senha é gerada + caso novo no bloco `R9` de `CustomizadorDaInstalacaoTest`
- [ ] Ocorrências irmãs de "passkeys": `docs/pt/referencia/pacotes-instalados.md:23`, `docs/en/referencia/pacotes-instalados.md:23`, `docs/pt/operacao/roteiro-de-features.md:32` (`F-05`), `docs/en/operacao/roteiro-de-features.md:30` (`F-05`), `wikis/pacotes.md:10` — reescritas, nunca apagadas
- [ ] Ocorrências irmãs de "`composer dev` = servidor + fila + vite": `docs/pt/comecar/instalacao-avancada.md:168`, `docs/en/comecar/instalacao-avancada.md:166`, `docs/pt/referencia/pacotes-instalados.md:141`, `docs/en/referencia/pacotes-instalados.md:140`
- [ ] Ocorrências irmãs de "`schedule:work` vem no `composer dev`": `docs/pt/operacao/roteiro-de-features.md:150`, `docs/en/operacao/roteiro-de-features.md:148`, `wikis/arquitetura.md:375`
- [ ] `routes/console.php:20`: reescrever o comentário para não afirmar que `schedule:work` já está incluso no `composer dev`
- [ ] `app/Providers/Filament/AppPanelProvider.php:67`: docblock de `canAccessPanel` com a regra real (papel com `roles.painel = 'app'`)
- [ ] `app/Providers/Filament/InfraPanelProvider.php:343`: citação corrigida de `KitServiceProvider.php:172` para `:'ver-logs':429`
- [ ] `app/Providers/Filament/AdminPanelProvider.php:262-269`: docblock do `FilamentOnboardingPlugin` sem prometer consumo no painel `app`
- [ ] `app/Console/Commands/KitInstall.php:27`: docblock sobre passos idempotentes e falha de infraestrutura que pode interromper
- [ ] `app/Models/AgenteIa.php:75-76` e `app/Ai/Agents/GuardaPrompt.php:19`: docblock corrigido — a temperatura/`max_tokens` do catálogo ainda não chegam ao provider
- [ ] Verificação: `grep -rn -i "passkeys" README.md README.en.md docs/pt/referencia/pacotes-instalados.md docs/en/referencia/pacotes-instalados.md` vazio; `` grep -n '`password`' README.md README.en.md `` vazio; `grep -rni "vite juntos\|vite together\|fila e vite$\|incluso no .composer dev" README.md README.en.md docs wikis/*.md routes/console.php` vazio; `vendor/bin/pest tests/Kit/CustomizadorDaInstalacaoTest.php --compact` verde

## 2. Dependência do site: `astro-mermaid` + `mermaid@11.17.2` (RQ-05, RQ-12, RQ-18)
- [ ] `site/package.json`: adicionar `astro-mermaid` `^2.1.0` e `mermaid` `11.17.2` em `dependencies`
- [ ] `site/package-lock.json`: regenerado por `npm install` e commitado junto com o `package.json`
- [ ] `site/astro.config.mjs:integrations:51`: importar `astro-mermaid` e inserir no array `integrations`, antes de `starlight(...)`, com `autoTheme: true`
- [ ] `site/verifica-acessibilidade.mjs:77`: esperar `svg[role~="graphics-document"]` (`~=`) antes do axe nas páginas com bloco `.mermaid`
- [ ] `site/verifica-acessibilidade.mjs`: reprovar (`violacoes`) bloco `.mermaid` que não virou `<svg>` ou mostra o erro de sintaxe do Mermaid
- [ ] `site/verifica-acessibilidade.mjs:AMOSTRA_CLARA:63`: acrescentar `/pt/referencia/arquitetura-em-diagramas/`
- [ ] Verificação: `cd site && npm install && npm run build` verde; `git status --porcelain site/` mostra `package.json` **e** `package-lock.json`; `grep -c 'class="mermaid"' site/dist/pt/referencia/arquitetura-em-diagramas/index.html` ≥ 4; `node site/verifica-acessibilidade.mjs` verde

## 3. README (pt/en): DG-01 + link para a página de diagramas (RQ-11, RQ-20, RQ-21)
- [ ] `README.md`/`README.en.md`: inserir o diagrama **DG-01 — Arquitetura em camadas** (`flowchart TD` com `subgraph`, 19 nós) dentro de "## Os três painéis", antes de "### Como cada um se parece"
- [ ] Logo abaixo do diagrama, o link para a página de diagramas do site (pt/en)
- [ ] Verificação: `wc -l README.md README.en.md` continua ≤ 756/767 (hoje 445/446); `[CT-13]`/`[CT-14]` verdes; guarda `[DG-01]` do passo 14 (`Filament::getPanels()` produz exatamente os 3 ids do diagrama)

## 4. Página nova de diagramas: `docs/{pt,en}/referencia/arquitetura-em-diagramas.md` (RQ-05, RQ-12, RQ-21, RQ-23, RQ-30)
- [ ] Criar `docs/pt/referencia/arquitetura-em-diagramas.md` e `docs/en/referencia/arquitetura-em-diagramas.md` (front matter `title`/`description`/`sidebar.order: 5`)
- [ ] Conteúdo: DG-01 (mesmo bloco do README, texto idêntico), DG-02 — Casos de uso por papel, DG-03 — Regra de acesso ao painel, DG-13 — ER do núcleo, cada um com parágrafo curto de contexto
- [ ] Índice final linkando (relativo) para as 12 páginas que têm diagrama (passos 5 a 13 e 15 a 18)
- [ ] Crédito ao GitDiagram (ADR-11), sem embutir
- [ ] Rodar `node converter.mjs` dentro de `site/` para regenerar sidebar/stubs/redirects
- [ ] Verificação: `git diff --stat site/sidebar.json site/public/ site/redirects.json` mostrando só adições; guardas `[DG-02]`, `[DG-03]`, `[DG-13]` do passo 14; `[CT-01,03,04,05,19,20,22,29,30,34,36,37,38,41,44,45]` verdes; `node verifica-links.mjs` verde

## 5. `docs/{pt,en}/autenticacao/index.md`: DG-04, DG-10 + link ao DG-03 (RQ-21, RQ-22, RQ-25)
- [ ] Acrescentar link para o DG-03 na página de diagramas (passo 4), sem copiar o bloco Mermaid
- [ ] Inserir **DG-04 — Sequência: login por senha num painel** (`sequenceDiagram`)
- [ ] Inserir **DG-10 — Sessão autenticada** (pertinente, RQ-25, `stateDiagram-v2`)
- [ ] Verificação: guardas `[DG-04]` e `[DG-10]` do passo 14 (o `[DG-03]` é provado no passo 4, onde o bloco mora)

## 6. `docs/{pt,en}/autenticacao/login-unificado.md`: DG-05 (RQ-22)
- [ ] Inserir **DG-05 — Sequência: login unificado → destino por 0/1/N painéis**, perto de "## O que muda para quem entra" (linha 37)
- [ ] Verificação: guarda `[DG-05]` do passo 14

## 7. `docs/{pt,en}/autenticacao/login-social.md`: DG-06 (RQ-22)
- [ ] Inserir **DG-06 — Sequência: retorno do login social**, perto de "## Vínculo com o provedor: a primeira vez, e as seguintes" (linha 137)
- [ ] Verificação: guarda `[DG-06]` do passo 14

## 8 e 9. `docs/{pt,en}/autenticacao/convites.md`: DG-07, DG-09 (RQ-22)
- [ ] Inserir **DG-07 — Sequência: convite do envio ao aceite** (`sequenceDiagram`)
- [ ] Inserir **DG-09 — Estados do convite** (`stateDiagram-v2`)
- [ ] Verificação: guardas `[DG-07]` e `[DG-09]` do passo 14

## 10. `docs/{pt,en}/autenticacao/estados-de-usuario.md`: DG-08 (RQ-22)
- [ ] Inserir **DG-08 — Estados da conta do usuário** (`stateDiagram-v2`)
- [ ] Verificação: guarda `[DG-08]` do passo 14

## 11. `docs/{pt,en}/operacao/roteiro-de-features.md`: DG-11 (RQ-23)
- [ ] Nova subseção "### Sequência do assistente, do prompt ao ledger" dentro de "## IA" (linha 126)
- [ ] Inserir **DG-11 — Sequência: assistente de IA** (`sequenceDiagram`, ordem real do pipeline de middleware + `remember` + ledger)
- [ ] Verificação: guarda `[DG-11]` do passo 14

## 12. `docs/{pt,en}/recursos/trilhas-de-infraestrutura.md`: DG-12 (RQ-23)
- [ ] Inserir **DG-12 — Mapa do `/infra`: tela → fonte → quem grava** (`flowchart LR`)
- [ ] Verificação: guarda `[DG-12]` do passo 14

## 13. `docs/{pt,en}/recursos/configuracoes-do-kit.md`: DG-14 (RQ-23)
- [ ] Inserir **DG-14 — De onde vem a configuração**, seção "## Quem manda: o banco ou o `.env`?" (linha 182)
- [ ] Verificação: guarda `[DG-14]` do passo 14

## 14. Teste-guarda novo: `tests/Kit/DiagramasDaArquiteturaTest.php` (RQ-10, RQ-26)
- [ ] Criar `tests/Kit/DiagramasDaArquiteturaTest.php` com sentinela de arquivo inteiro (`beforeEach` + `naArvoreDoKit()`)
- [ ] Marcador `%% DG-xx` na linha seguinte à declaração do tipo, em todo bloco do catálogo
- [ ] Camada 1 (regras comuns): `accTitle`/`accDescr` obrigatórios; proibido `%%{init`, `classDef ... fill|color`; lista branca de tipo (`flowchart`, `sequenceDiagram`, `stateDiagram-v2`, `erDiagram`); bloco duplicado (DG-01) idêntico nos dois lugares
- [ ] Camada 2: um `it('[DG-xx]')` por diagrama que mora aqui (DG-01 a DG-16, DG-20), cada um com `->with(['pt', 'en'])`
- [ ] `it('[DG-17]')` em `tests/Kit/DuasRotasDeEntregaTest.php`; `it('[DG-18]')` e `it('[DG-19]')` em `tests/Kit/MysqlNoDockerTest.php`, cada um com `->skip(fn () => ! naArvoreDoKit(), …)` no próprio caso
- [ ] Criar `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` (suíte `Tenancy`, `permission.teams` + painel `app` com tenancy) com as 4 metades que só existem com tenancy: `admin_app` (DG-02), contexto/`canAccessTenant` (DG-03), `IdentifyTenant`→`DefinirTenantDePermissoes` (DG-04), `hasTenancy()` (DG-20) — só afirma código, não lê bloco
- [ ] Verificação: `vendor/bin/pest tests/Kit/DiagramasDaArquiteturaTest.php --compact` e `vendor/bin/pest tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php --compact` verdes; `vendor/bin/pest --parallel --tia` sem quebrar mais nada

## 15. `docs/{pt,en}/comecar/instalacao-avancada.md`: DG-15, DG-18 (RQ-24)
- [ ] Inserir **DG-15 — Sequência da instalação** como nova seção final "## Como a instalação acontece por dentro"
- [ ] Inserir **DG-18 — Containers por profile do Docker** logo após "## A aplicação containerizada e o banco" (linha 146), antes de "## Comandos" (linha 165)
- [ ] Verificação: guarda `[DG-15]` do passo 14; `vendor/bin/pest tests/Kit/MysqlNoDockerTest.php --compact`, caso `[DG-18]`

## 16. `docs/{pt,en}/comecar/atualizando-o-projeto.md`: DG-16, DG-17 (RQ-24)
- [ ] Inserir **DG-16 — Fluxo do `kit:update`** dentro de "## O jeito fácil: `php artisan kit:update`" (linha 18)
- [ ] Inserir **DG-17 — As duas rotas de entrega** como nova seção antes de "## O jeito manual" (linha 117)
- [ ] Verificação: guarda `[DG-16]` do passo 14; `vendor/bin/pest tests/Kit/DuasRotasDeEntregaTest.php --compact`, caso `[DG-17]`

## 17. `docs/{pt,en}/recursos/multi-tenancy.md`: DG-20 (RQ-25)
- [ ] Inserir **DG-20 — `kit:tenancy`: de single para multi-organização** (`flowchart TD` + `sequenceDiagram` curto, dois blocos na mesma seção)
- [ ] Verificação: guarda `[DG-20]` do passo 14

## 18. `docs/{pt,en}/operacao/desenvolvendo-o-kit.md`: DG-19 (RQ-25)
- [ ] Inserir **DG-19 — Segundo plano: `composer dev` × Compose × agendador** como nova seção final "## O que roda em segundo plano"
- [ ] Verificação: `vendor/bin/pest tests/Kit/MysqlNoDockerTest.php --compact`, caso `[DG-19]` (mora ali)

## 19. `KitArte.php`: generalizar `QUADROS_DO_GIF` → `CLIPES` (RQ-27)
- [ ] `app/Console/Commands/KitArte.php`: trocar `private const QUADROS_DO_GIF` (lista) por `private const CLIPES` (mapa `clipe => quadros`) com `fluxo-import-export`, `densidade`, `busca-spotlight`, `login-unificado`
- [ ] `montarGif()` passa a iterar `self::CLIPES`, produzindo `art/{clipe}.gif` (a chave é sempre o nome do arquivo final)
- [ ] `IMAGENS` não muda nesta feature (os 2 órfãos existentes ficam como estão — dívida declarada em Riscos); quadros dos clipes novos não entram em `IMAGENS`
- [ ] `publicar()` confere `IMAGENS` primeiro, só então generaliza o `continue` de `QUADROS_DO_GIF:134`
- [ ] Verificação: `php artisan kit:arte --sem-gif` não cria `art/busca-spotlight-*.png` nem `art/login-unificado-*.png`, reporta os 2 órfãos como ignorados; com GIF, `art/fluxo-import-export.gif`, `art/densidade.gif`, `art/busca-spotlight.gif`, `art/login-unificado.gif` existem

## 20. Capturas novas para os clipes `busca-spotlight` e `login-unificado` (RQ-27)
- [ ] `tests/BrowserTenancy/CapturaDeArteTest.php`: cenário `busca-spotlight` — tela do `/app` fechada (`busca-spotlight-1-fechada`) e com o Spotlight aberto (`busca-spotlight-2-aberta`), mesmo seletor de `tests/Browser/RoteiroDoKitTest.php:F-45:100-154`
- [ ] `tests/BrowserTenancy/CapturaDeArteTest.php`: cenário `login-unificado` — com `ligarLoginUnificado()` (`tests/Pest.php:507`), formulário único (`login-unificado-1-formulario`) e cartões de escolha (`login-unificado-2-escolha`), reaproveitando o arranjo de `tests/Browser/LoginUnificadoTest.php` (CT-B01) sem duplicar asserção funcional
- [ ] Verificação: `KIT_ART=1 php artisan test tests/BrowserTenancy/CapturaDeArteTest.php` produz os 4 PNGs novos em `tests/Browser/Screenshots/`

## 21. Correção do `art/install.gif` (RQ-28)
- [ ] Criar `tests/Browser/Fixtures/terminal-instalacao.blade.php` (fixture única, sem rota pública) com a transcrição REAL de uma execução de `php artisan kit:install --ansi`, senha substituída por placeholder fixo (nunca valor real ou inventado), parâmetro `$ate` recortando cada quadro
- [ ] `tests/BrowserTenancy/CapturaDeArteTest.php`: cenário novo com 4 a 6 valores de `$ate`, screenshots `instalacao-1-inicio` .. `instalacao-N-resumo`
- [ ] `app/Console/Commands/KitArte.php`: `CLIPES` ganha `'install' => [...]` (a chave já é `art/install.gif`, o nome que `[CT-11]` exige)
- [ ] Verificação: `assertDontSee('/ password')` e `assertDontSee('password (padrão do kit)')` em cada quadro antes do `screenshot()`; `art/install.gif` conferido visualmente; `[CT-11]` continua verde

## Testes
<!-- 04-casos-de-teste.md está em derivação pela feature-test-design (step 4, despacho #23 abaixo) — os IDs de CT ainda não existem. Nenhum número é inventado aqui; os arquivos e casos abaixo vêm do 01. -->
- [ ] `tests/Kit/DiagramasDaArquiteturaTest.php` (passo 14) — CTs do `04-casos-de-teste.md` (pendente, `04` em derivação)
- [ ] `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` (passo 14) — CTs do `04` (pendente)
- [ ] `tests/Kit/CustomizadorDaInstalacaoTest.php`, bloco `R9` (passo 1) — CTs do `04` (pendente)
- [ ] `tests/Kit/MysqlNoDockerTest.php`, `it('[DG-18]')` e `it('[DG-19]')` (passos 14, 15, 18) — CTs do `04` (pendente)
- [ ] `tests/Kit/DuasRotasDeEntregaTest.php`, `it('[DG-17]')` (passo 16) — CTs do `04` (pendente)
- [ ] `tests/BrowserTenancy/CapturaDeArteTest.php`, cenários novos (passos 20 e 21) — CTs do `04` (pendente)

**Sem `05` (CT-B) por enquanto**: o `01` declara "Gate de CT-B: N/A, o comportamento já é provado em `RoteiroDoKitTest` (F-45) e `LoginUnificadoTest` (CT-B01)" — a `feature-test-design` confirma ou não isso ao derivar o `04`.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] Cada arquivo tocado rodado sozinho (é o modo em que uma constante ou função presa a outro arquivo quebra): `tests/Kit/DiagramasDaArquiteturaTest.php`, `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, `tests/Kit/MysqlNoDockerTest.php`, `tests/Kit/DuasRotasDeEntregaTest.php`, `tests/Kit/HelpersDeTesteTest.php`, `tests/Kit/CustomizadorDaInstalacaoTest.php`, `tests/Kit/RedeDeDocumentacaoTest.php`, `tests/Kit/SiteDeDocumentacaoTest.php` (`--filter=SiteDeDocumentacao`), `tests/Browser`/`tests/BrowserTenancy` (`--filter=CapturaDeArte`) — cada um `--compact`
- [ ] `vendor/bin/pest --parallel --tia` (nada mais quebrou, contra a baseline de `main`; os números do README/`docs/` que dependem da árvore — `[CT-25]` e os badges — saem certos ou a falha já aponta o valor)
- [ ] **Localmente, antes do PR** (o `pages.yml` só roda em push na `main`): `cd site && npm ci && npm run build && node verifica-links.mjs && node verifica-acessibilidade.mjs` — `npm ci`, e não `npm install`, para reproduzir o que o `pages.yml` roda sobre o lock commitado; `git status --porcelain site/` mostra o `package-lock.json` junto do `package.json`
- [ ] `/code-review high {base}...HEAD` + passe de eixos (step 6.5)
- [ ] Citações `arquivo:símbolo:linha` reverificadas com o script de `.claude/skills/feature-wiki/SKILL.md` (seção "Citações de código") — {n}/{n} ok. O script da skill só casa `.php` e símbolo sem `-`/`:`; rodar também a versão estendida (md/yml/mjs/json/ts, símbolo entre aspas como `'ver-logs'`/`'health:check'`, `[CT-nn]`) usada na revisão do step 5, e path sempre completo — path curto (só o nome do arquivo, sem o diretório) é `ERRO` no script mesmo com a linha certa

<!-- Cada [x] acima leva " — {evidência}, {data}". Ex.: `- [x] composer test:kit — 677/677, 2026-09-05`
     Evidência com NÚMERO leva o comando que o gerou (`grep -c …`, saída do script). Degradação
     declarada ("sem PCOV", "plugin ausente") leva a PROVA NEGATIVA (`php -m`, `ls vendor/…`).
     Número sem comando e ausência sem prova foram os dois achados que o juiz cego devolveu
     CONTRA A SESSÃO em 2026-09-21 (QA-03, QA-04). -->

## Conformidade com Rules

<!-- Uma linha por rule de .ai/rules/index.md cujo glob casa com um arquivo do diff. "violada" = blocker do PR. Preenchida no step 7 — vazia até lá. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: {n} · **Veredito**: {APROVADO | APROVADO COM DÉBITO | REPROVADO → destino} · **Data**: {YYYY-MM-DD}
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação
<!-- Saída dos steps 5 e 6, ANTES de escrever código. Não confundir com "Desvios do Plano",
     que é pós-implementação. -->

### Revisão profunda (step 5) — premissas do plano contra o código real

| # | Premissa do plano | O código real diz | Correção aplicada (arquivo, seção) |
|---|---|---|---|
| 1 | Citações com caminho curto e símbolo (7) | Símbolo certo na linha citada; o script dá ERRO por causa do caminho | Caminho completo. 01: passos 4, 17. 02: ADR-03, 04, 07, 09, 10 |
| 2 | `src/lib/sponsor-campaign.ts:sentCampaign:21`, `explainer-share.tsx:130` como citação local | Arquivos do GitDiagram (`abe0620f`), fora deste repositório | Reescritas como citação de repositório externo, sem apagar. 02: ADR-11, ADR-12 |
| 3 | Citações sem símbolo (README, `site/*`, `composer.json`, `import-export-csv.md`) | A skill reprova citação sem símbolo | Símbolo acrescentado. 01: passos 1, 15, 19. 02: ADR-02, ADR-09, ADR-10 |
| 4 | DG-20: `KIT_TENANCY=true` em `AtivadorDeTenancy.php:169` | O arquivo tem 116 linhas; a escrita é `KitTenancy.php:ligarFlagNoEnv:70` → `AtivadorDeTenancy.php:escreverEnv:35` | Citação corrigida. 01: passo 17 |
| 5 | DG-18: `mysql:55` | O cabeçalho do serviço é a `:52`; a 55 é o `profiles:` | `:52`. 01: passo 15 |
| 6 | DG-15, DG-16 e DG-20: a Reflection compara a ordem das chamadas por igualdade | `handle()` chama métodos fora do diagrama, retorna cedo e tem `try/finally`; nomes de método ≠ nós | Subsequência em ordem relativa, mapa nó → método com linha, controle positivo; DG-20 também confere o corpo de `recriarBanco()`. 01: passos 15, 16, 17 |
| 7 | Cobertura do Requisito com os números de passo errados (16 linhas) | Os passos 15 a 21 foram deslocados | 16 linhas corrigidas. 01: Cobertura. Também "passo 18" → 21 em Autorização e Riscos |
| 8 | ADR-05: DG-16 é sequência; faltam DG-03 e a sequência do DG-20 | DG-16 é `flowchart TD`, DG-03 é `flowchart`, DG-20 tem sequência curta | Tabela corrigida. 02: ADR-05. Também RQ-08 no 01 |
| 9 | RQ-28 (resumo do `kit:install`) aponta para o passo 1, que não o corrigia | `CustomizadorDaInstalacao.php:password:295` imprime `password (padrão do kit)` quando a senha é gerada | Novo item no passo 1, com caso no bloco `R9` do `CustomizadorDaInstalacaoTest`. 01: Natureza, Análise, passo 1. 02: linha nova no ADR-10 |
| 10 | Verificação `grep -n "password" README*` "só acha o texto novo" | O `README.en.md` usa "password" como palavra comum em 10 linhas | Grep com crase. 01: passo 1 |
| 11 | "`DevCommands::registerDefaults` registra server/queue/vite/reverb" | O `DevCommands` registra serve/queue/pail/vite; o reverb vem de `Reverb.php:registerDevCommands:12` | Texto e citação corrigidos. 01: passo 1. 02: ADR-10 |
| 12 | Esperar `svg[role="graphics-document"]` | O Mermaid grava `role="graphics-document document"`; o seletor de igualdade nunca casa | `svg[role~="graphics-document"]`. 01: Riscos, passo 2. 02: ADR-02 |
| 13 | Sentinela `grep graphics-document site/dist/...` | O `astro-mermaid` desenha no cliente; o `dist/` só tem `<pre class="mermaid">` | Sentinela em duas metades: `pre.mermaid` no `dist/`, um `<svg>` por bloco no navegador. 01: passo 2. 02: ADR-02 |
| 14 | ADR-02: "a guarda RQ-26 roda o Mermaid num navegador" | A guarda Pest só lê texto | `verifica-acessibilidade.mjs` reprova bloco sem `<svg>` e `Syntax error in text`. 01: passo 2. 02: ADR-02, ADR-03 |
| 15 | ADR-04: "sem `accTitle`, o axe reprova `svg-img-alt`" | O seletor do axe é por igualdade; se ele chega a se aplicar, não está confirmado | Quem cobra é a camada 1 da guarda. 02: ADR-04. 01: linha nova em Riscos |
| 16 | Lock do site não citado | O workflow do site roda `npm ci` (`pages.yml:npm:55`); o `[CT-15]` exige o lock (`:package-lock:673`) | `site/package-lock.json` no mesmo commit. 01: Impacto, passo 2, Verificação. 02: ADR-02 |
| 17 | README "após a seção de arquitetura já existente" | Essa seção não existe | Dentro de `## Os três painéis` (`README.md:106`). 01: passo 3 |
| 18 | DG-01 sem o nó `pulse` | O catálogo põe pulse no segundo plano | Nó acrescentado. 01: passo 3 |
| 19 | "~15 nós/linhas", "Nove páginas", "as 9 páginas dos passos 5 a 13" | DG-01 tem 19 nós, DG-02 tem 26; são 12 páginas, nos passos 5-13 e 15-18 | 01: Análise, Impacto, passo 4. 02: ADR-04 item 8, ADR-07 |
| 20 | DG-02: atores iguais aos papéis, "com e sem `permission.teams`" | Visitante, agendador e CLI não são papel; o `admin_app` depende de `kit.tenancy.enabled` (`PapeisSeeder.php:tenancy:79`) | Só os atores de papel entram na comparação; condição corrigida. 01: passo 4 |
| 21 | DG-03 sem "contexto" nem `canAccessTenant`; dataset de 4 contas | O catálogo exige os dois (`User.php:contexto:217`, `:canAccessTenant:789`); são 5 ramos | Nós e dataset (5 + 4) acrescentados. 01: passo 4 |
| 22 | DG-04: "LoginResponse ou equivalente", `gatherMiddleware()` | Contrato em `KitServiceProvider.php:31`; lista resolvida em `Router::gatherRouteMiddleware` | Guarda exata, por subsequência. 01: passo 5 |
| 23 | DG-09: "Reenviar só em Pendente/Expirado", sem citação nem guarda | `ConvitesTable.php:visible:89` | Citação e guarda de visibilidade. 01: passos 8 e 9 |
| 24 | DG-11: `remember` no fim, filtro de saída antes da auditoria; guarda sobre `middleware()` crua | O SDK põe `RememberConversation` primeiro (`GeneratesText.php:148`); `middleware()` lança exceção sem o `AssistenteSeeder` | Ordem real; seed obrigatório; guarda própria para o `remember`; `getRawListeners` no lugar de `hasListeners`. 01: passo 11 |
| 25 | DG-12: plugins de `getPlugins()` "batem" com as telas | São 21 plugins; 3 telas são Page/Resource; o total de checks do Health varia | Subconjunto + páginas/resource + 7 checks incondicionais; coluna "quem grava" do catálogo acrescentada; "substitui" o teste do `FundacaoTest` → "complementa". 01: passo 12 |
| 26 | Guarda sem marcador por DG; IDs por uma regex só; sentinela por caso | `tests/Kit` viaja pelo `kit:update` sem `docs/` | Sentinela no arquivo inteiro, marcador `%% DG-xx`, IDs extraídos por tipo. 01: passo 14. 02: ADR-06 |
| 27 | DG-18 "reusar `($this->blocoDoServico)`" | É uma closure no `beforeEach` de outro arquivo, inalcançável daqui | Move para `tests/Pest.php` (mover, não copiar). 01: Natureza, regressão, passo 15. 02: ADR-06 |
| 28 | DG-17 com 4 + 4 nós, guarda de igualdade | São 7 alvos `export-ignore` e 9 chaves; `README.md` não é chave; a constante só existe quando o arquivo dela é carregado | Conjuntos inteiros; asserção própria para o `README.md`; constante para `tests/Pest.php`. 01: passo 16 |
| 29 | DG-16: nós sem os passos intermediários | `<tag>` some no Mermaid; o catálogo cita `CAMINHOS_SO_RELATORIO` e exige o rótulo "não interativo" | `#lt;tag#gt;`; nós `resumo`, `nao_interativo` e `so_relatorio`. 01: passo 16 |
| 30 | DG-20 sem o nó `permission.teams` | Faz parte do catálogo (`ligarPapeisPorTenant:71`) | Nó acrescentado. 01: passo 17 |
| 31 | DG-19: "não contém `schedule:work`" sem mais nada | O `ArtisanServiceProvider` é deferível (`:120`, `:274`) e a lista pode vir vazia; o catálogo pede também os eventos agendados e os comandos do Compose | Controle positivo (medido com tinker) e as duas outras metades. 01: passo 18 |
| 32 | `IMAGENS` recebe os quadros dos clipes; verificação usa `art/import-export.gif` | Quadro de GIF não vira PNG (`KitArte.php:41`, `:134`); o arquivo se chama `fluxo-import-export.gif` | `IMAGENS` só recebe os 2 órfãos; `publicar()` generalizado, com exceção para `densidade`; nome do GIF corrigido. 01: Análise, passos 19 e 21. 02: ADR-09 |
| 33 | View nova em `resources/views/arte/` | O `CAMINHOS_DO_KIT` lista `resources/views` por subdiretório; o `kit:update` não entregaria o `arte/` | A view vai para `tests/Browser/Fixtures/`. 01: Superfície de UI, passo 21. 02: ADR-09 |
| 34 | `install.gif` conferido "visualmente" | O texto vem da fixture | `grep` na fixture e `assertDontSee` antes do screenshot. 01: passo 21 |
| 35 | `tests/Tenancy/LoginUnificadoTest.php` | Não existe; o CT-B01 está em `tests/Browser/LoginUnificadoTest.php` | Caminho corrigido. 01: regressão, passo 20 |
| 36 | Erros de digitação e incoerências | "Mermoid", "published", "iIegível", `architecture-beta` "além do básico" contra o item 9, "filament-shield/Packagist", "20 (mais os 3)" | 02: ADR-02, 04, 07. 01: Riscos, pergunta 6 |

### Varredura da classe irmã

| irmã | ocorrências | entradas acrescentadas ao 01 |
|---|---|---|
| `tests/Kit/DiagramasDaArquiteturaTest.php` | `RedeDeDocumentacaoTest [CT-10]`; `CAMINHOS_DO_KIT` → `tests/Kit` viaja; `MysqlNoDockerTest.php:50`; `DuasRotasDeEntregaTest.php:132`; `[CT-25]` e `[CT-50]` (números do README) | Sentinela no `beforeEach`; os 2 ajudantes movidos para `tests/Pest.php`; cada arquivo rodado sozinho na Verificação Final |
| `docs/*/referencia/arquitetura-em-diagramas.md` | `sidebar.json`, `redirects.json`, stubs, `AMOSTRA_CLARA:63`, `[CT-41]` (já previstos); `[CT-22]` com `caminhoResolvido:190` | Estilo dos links no índice (`../../x/`) |
| `site/package.json` | `site/package-lock.json`; `pages.yml:55` (`npm ci`); `[CT-15]:673` | Lock no passo 2, no Impacto e na Verificação |
| `terminal-instalacao.blade.php` | `CAMINHOS_DO_KIT` (`resources/views/{auth,errors,filament,livewire,svg}`); o `DuasRotas` só desce um nível abaixo do topo | View movida para `tests/Browser/Fixtures/` |
| `KitArte::CLIPES` | `IMAGENS`; `publicar():134`; `montarGif` `:206`/`:228`; `import-export-csv.md:13` | `IMAGENS` só com os órfãos; exceção do `densidade`; nome do GIF mantido |
| "`composer dev` = servidor + fila + vite" | `README.md:365`, `README.en.md:366`, `instalacao-avancada.md:168`/`:166`, `pacotes-instalados.md:141`/`:140` | 4 linhas nos `docs/` |
| "`schedule:work` vem no `composer dev`" | `routes/console.php:20`, `roteiro-de-features.md:150`/`:148`, `wikis/arquitetura.md:375` | 3 linhas |
| "passkeys" | `README.md:201`, `README.en.md:201`, `pacotes-instalados.md:23` ×2, `roteiro F-05 :32`/`:30`, `wikis/pacotes.md:10` | 3 linhas (reescritas, nunca apagadas) |
| "password" | `README.md:32` e `:97` (pt e en), `CustomizadorDaInstalacao.php:295`, `install.gif` | `CustomizadorDaInstalacao` e o caso no teste `R9` |

### Auditoria Ponytail (step 6)

| # | Achado | Aplicada? | Onde |
|---|---|---|---|
| 1 | A:L485-493 (defaults de config no DG-01) | aceito, com regra | A guarda do DG-01 confere tudo que o diagrama desenha, e só isso. Se o implementador rotular "SQLite (padrão)" ou "llama.cpp (padrão)", a guarda passa a ler o default no fonte do `config/*.php`; sem rótulo de default, sai. Escrever essa regra no passo 3 e no ADR-06 |
| 2 | A:L655-657, B:7 (DG-03 copiado em `autenticacao/index`) | aceito | `autenticacao/index` ganha só um link para o DG-03 da página de diagramas. Sai a checagem de texto idêntico do DG-03 (a do DG-01 README × página fica) |
| 3 | A:L759-761, L798-801, L851-852, L884-885, L1034-1035 (datasets que repetem testes de comportamento existentes) | aceito, com o limite declarado | A guarda do DG confere a estrutura que o diagrama desenha (rotas, enums/valores, métodos, chaves, ordem por Reflection) contra o código. O comportamento dos ramos já é provado pelos testes de feature citados pelo revisor — o `it()` do DG cita esses testes num comentário `// comportamento: tests/Kit/…Test.php [CT-nn]`. O ADR-06 ganha o limite: mudar o comportamento de um ramo e atualizar só o teste de feature não reprova a guarda do diagrama; o comentário é o ponteiro para quem mexer |
| 4 | B:3, ADR:13 (`{{` na camada 1) | aceito | O `[CT-18]` já cobre `docs/`; o README não passa pelo Liquid |
| 5 | B:4 (lista branca de 4 tipos) | aceito | Substitui a lista negra: primeira linha significativa do bloco ∈ {`flowchart`, `sequenceDiagram`, `stateDiagram-v2`, `erDiagram`}; barra também front matter e `%%{init}%%` no topo |
| 6 | ADR:14 (tirar a checagem de tipo do Pest) | recusada: o gate do site só roda em push na `main` (não há gate de PR para o site) e ninguém confere o render do GitHub | A lista branca do B:4 é uma linha e pega antes do merge |
| 7 | B:5 (página en irmã) | aceito | `[CT-04]`/`[CT-34]` já exigem |
| 8 | B:6, ADR:16 (constante com os 20 IDs / completude) | aceito | Cada `it('[DG-xx]')` acha o próprio bloco pelo marcador `%% DG-xx` e falha se ele sumir |
| 9 | ADR:12, B:9, B:10 (comparação pt=en separada; marcadores no irmão; extrator em `tests/Pest.php`) | aceito | A camada 2 roda `->with(['pt', 'en'])` e confere o bloco de cada idioma contra o fato do código — a paridade sai de brinde. O extrator de IDs por tipo continua (a camada 2 precisa dele), mas mora local em `DiagramasDaArquiteturaTest.php`. O irmão `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` só afirma código (não lê diagrama); o lado do diagrama dessas metades é lido no arquivo Kit |
| 10 | B:11 (metade de middleware do DG-20) | aceito | O DG-20 no irmão fica só com `hasTenancy()` = `kit.tenancy.enabled` |
| 11 | B:13 (precedente de Reflection) | aceito | `ReflectionMethod` + `getStartLine`/`getEndLine` (`tests/Kit/RaizDeUrlRegistradaTest.php:162`) + `codigoSemComentario()` (`tests/Pest.php:1264`) + `preg_match_all('~\$this->(\w+)\(~')`, função local com os 4 usos. Conferir as duas citações com `sed -n` antes de gravar |
| 12 | B:14 (controle positivo "12 métodos") | aceito | Mantido só no DG-19, onde a asserção é negativa |
| 13 | B:17 (reuse `caminhosDoKit()` e o laço de `caminhosDeTopoQueViajam()`) | aceito | Conferir as citações (`tests/Pest.php:1242`, `tests/Kit/DuasRotasDeEntregaTest.php:181`) |
| 14 | ADR:17 (guardas do DG-17 e DG-18 nos arquivos onde os helpers já moram) | aceito | `it('[DG-18]')` em `tests/Kit/MysqlNoDockerTest.php` e `it('[DG-17]')` em `tests/Kit/DuasRotasDeEntregaTest.php`, cada um com `->skip(fn () => ! naArvoreDoKit(), …)` no próprio caso (padrão `tests/Kit/AcoesPinadasPorShaTest.php:72`, e o `[CT-10]` de `RedeDeDocumentacaoTest` exige sentinela no caso quando o caminho literal aparece). Nada se move para `tests/Pest.php`. Registrar no passo 14 onde cada `DG` mora |
| 15 | B:20, ADR:24 (chave do clipe = nome do arquivo) | aceito | `fluxo-import-export`, `install`, `busca-spotlight`, `login-unificado`, `densidade` → `art/{clipe}.gif` sem exceção |
| 16 | B:21, B:23, ADR:25, A:L138-141 (origem alternativa `art/` para `densidade`) | aceito | Uma origem só (`tests/Browser/Screenshots`); `publicar()` confere `IMAGENS` antes de pular quadro de clipe |
| 17 | B:22, ADR:27 (publicar os 2 órfãos) | aceito | Ficam como estão (o `kit:arte` os relata como ignorados). Viram dívida declarada no 01 (seção Riscos ou Dívidas): violam o par de `.ai/rules/testes-browser.md`, e nenhum RQ pede a correção |
| 18 | B:25 (uma fixture só para o install.gif) | aceito | Uma view Blade em `tests/Browser/Fixtures/` com a transcrição inline e um `$ate` para recortar cada quadro |
| 19 | ADR:26 (cortar os últimos quadros do GIF atual com ffmpeg) | recusada: a opção que o mantenedor escolheu diz "Refazer o GIF e corrigir o texto e o resumo" (Adendo 2); o GIF atual também omite etapas que o código executa hoje ("Gerando senha do administrador", "Formatando o código gerado"); cortar o último quadro tira a URL e o login do fim e deixa o resto defasado | Mantém o refazer, com a fixture única do B:25 |
| 20 | B:26 (fixar o cenário em `CapturaDeArteTest.php`) | aceito | — |
| 21 | B:27 (grep na fixture × `assertDontSee`) | aceito | Fica o `assertDontSee` (as duas formas), que roda em todo `composer art` |
| 22 | B:29 (5 contagens à mão) | aceito | A Verificação Final roda os testes que guardam os números; o número certo sai da falha |
| 23 | B:30 (perguntas respondidas) | aceito | Fica a tabela de decisões da sessão + os riscos 2, 3, 5 (medição na implementação) na seção Riscos |
| 24 | ADR:1, A:L427-432 (sentinela manual do `dist/`) | aceito, com substituto | Sai o grep manual. Entra na Verificação Final: `npm ci && npm run build && node verifica-links.mjs && node verifica-acessibilidade.mjs` rodados localmente em `site/` antes do PR — o `pages.yml` só roda em push na `main`, então sem isso a primeira vez que o plugin é exercido é depois do merge |
| 25 | ADR:2 (seletor `.mermaid svg`) | aceito | — |
| 26 | ADR:5 (ADR-03 inteira) | aceito | Fundir em ADR-02 (o fato do `[CT-21]` vira uma linha nas alternativas) |
| 27 | ADR:20 (ADR-07 inteira) | aceito | O dado útil (teto do `[CT-13]`: 756/767, hoje 445/446) vai para o passo 3 do 01 |
| 28 | ADR:30 (entrada do `AgenteIa.php` na tabela de correções) | recusada: e a retratação do construtor está ERRADA | O docblock `app/Models/AgenteIa.php:75-76` ("a temperatura vai direto para o provider como número") é falso: `vendor/laravel/ai/src/Gateway/TextGenerationOptions.php:forAgent:75-76` só lê `temperature()`/`maxTokens()` por método ou atributo do agente, e `grep -rnE "temperature|maxTokens" app/Ai/` não acha nenhum. A entrada fica na tabela de correções do ADR-10 e do passo 1; o parágrafo que a retira sai. O docblock passa a dizer a verdade: o cast é `float`, e o valor do catálogo ainda não chega ao SDK (`AgenteBase` não implementa `temperature()`). Irmã: `app/Ai/Agents/GuardaPrompt.php:19` — mesma correção. Não mudar o comportamento de `AgenteBase` (fora do escopo; vira achado para o mantenedor) |
| 29 | ADR:34 (custo de integrar o sent.dm) | parcialmente rejeitado | O RQ-15 pediu avaliar o sent.dm como funcionalidade do kit — a avaliação É o custo. Encolher para 3 linhas (dependência pré-1.0, credencial + HMAC, dado pessoal fora do país sem LGPD na política), sem tabela de preços |
| 30 | ADR:37 (Superfície Livewire) | aceito | Uma linha com a evidência: nenhuma página/widget/componente; `KitArte` é Command |
| 31 | todos os demais | aceitos | Aplicar como o revisor descreveu |

**Linhas resultantes** (`wc -l`): `01-plano-acao.md` 1.584 → 1.426; `02-decisoes-arquiteturais.md` 842 → 625.

## Despachos

<!-- Claude Code: uma linha por disparo de sub-agente, com o modelo, o que ele NÃO recebeu e a
     auditoria do retorno. Host sem sub-agente: uma linha "Sem despacho — host sem sub-agente".
     Tarefa que rodou em linha por exceção: "Sem despacho — {motivo}".
     Auditoria REPROVADA também é linha (com o redespacho ao lado); fallback para general-purpose
     por agente fw-* indisponível vai na coluna Modelo. -->

Modelo da sessão orquestradora: Opus 5.5 (`claude-opus-5-5`). "herdado" = sem `model` explícito no `agent()` do Workflow, que herda o modelo da sessão (o Workflow não é `general-purpose`, e sim o sub-agente padrão de workflow).

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 3 | `gitdiagram` — estudo do GitDiagram + Mermaid verbatim (workflow `pesquisa-diagramas-do-kit`) | herdado (Opus 5.5) | 01, 02 (não existiam) | funcionalidades, geração, Mermaid 6.021 B (sha256 conferido em duas leituras GET), riscos | sessão conferiu o Mermaid e as provas negativas; nenhum POST de geração |
| 2 | 3 | `fidelidade-gitdiagram` — auditoria nó a nó do Mermaid (pipeline após #1) | herdado | — | 23 nós (17 corretos, 6 imprecisos), 27 arestas (26/1), 7 ausentes, 10 omissões | sessão conferiu amostras (`DestinoAposLogin`, `AgenteIaResource`) |
| 3 | 3 | `site-e-readme` — superfícies de publicação e testes-guarda | herdado | — | matriz de impacto de 20+ CT; achou a contagem de specs 71→72 já reprovando | sessão reconferiu `SiteDeDocumentacaoTest.php:990` e o `find` |
| 4 | 3 | `tipos-de-diagrama` — catálogo web de tipos Mermaid | herdado | — | GitHub em Mermaid 11.17.2; `usecase-beta` não renderiza no GitHub | URLs citadas; aceitas como fonte externa |
| 5 | 3 | `inv-acesso` — inventário de acesso/auth/tenancy | herdado | — | 431 citações (script do agente: 0 falhas) | cruzado pelo crítico (#8) |
| 6 | 3 | `inv-ciclo-de-vida` — inventário do ciclo de vida | herdado | — | D1–D6 (divergências doc × código) | cruzado pelo crítico (#8) |
| 7 | 3 | `inv-ia-infra-dados` — inventário IA/infra/dados | herdado | — | pipeline da IA, N1–N10 | sessão reconferiu N1 (`TextGenerationOptions::forAgent`) |
| 8 | 3 | `critico-inventarios` — completude e exatidão (barreira após #5–7) | herdado | — | ~250 citações, 4 erros de caminho, F1–F5, 13 lacunas, 6 possíveis invenções | aceito; suas correções vencem os inventários |
| 9 | 3 | `sent-dm` (workflow `pesquisa-adendo-videos`) | herdado | — | sent.dm = API de SMS/WhatsApp/RCS; vídeo é do GitDiagram `/video` | sessão conferiu o 302 do link de patrocínio no texto do retorno |
| 10 | 3 | `gifs-e-videos` | herdado | — | pipeline `kit:arte`; `install.gif` com `password` (RQ-10) | sessão reconferiu `SenhaDoAdministrador::ehUtilizavel` e `README.md:32/97` |
| 11 | 4 | `construtor-01-02` (workflow `wiki-diagramas-01-02-e-revisao`) | sonnet | a conversa | 01 (1.274 l) e 02 (785 l), 12 ADRs, 5 perguntas | reprovado em parte: afirmou "75/75 ok" e o script da skill deu 69 ok / 6 ERRO; retratou por engano a correção do docblock de `AgenteIa` (a sessão reverteu no step 6) |
| 12 | 5 | `citacoes` — script de citações | haiku | — | "3 OK / 10 ERRO" | reprovado em parte pelo analista (#16): 4 "linhas erradas" estavam certas; o total contou só as linhas impressas |
| 13 | 5 | `restricoes-site-testes` | haiku | — | tabela premissa × código | reprovado em parte: CT-20 citado na linha errada; 6 "diverge" eram estado futuro; não viu o `package-lock` |
| 14 | 5 | `fatos-das-guardas` | sonnet | — | viabilidade por DG | aceito com ressalva: não pegou DG-11 e DG-12 inviáveis como escritos |
| 15 | 5 | `classe-irma` | haiku | — | listas paralelas | reprovado em parte: `phpunit.xml:41` errado (é a 39); conclusão "tudo previsto" falsa |
| 16 | 5 | `analista-step5` — julga e corrige a wiki | herdado (Opus 5.5) | a conversa | 36 correções; citações 102/102 (script da skill) e 149/149 (estendido) | sessão conferiu `git status` (só a wiki) e releu as perguntas |
| 17–19 | 6 | `ponytail-01-a`, `ponytail-01-b`, `ponytail-02` (workflow `ponytail-review-da-wiki-de-diagramas`) | herdado | a conversa | −105, −145 e −270 linhas possíveis | sessão julgou cada achado (`ponytail-julgamento-da-sessao.md`): maioria aceita, 3 rejeitados (install.gif por corte de quadro; tirar a lista branca de tipos; retirar a correção do `AgenteIa`) e 1 parcial (custo do sent.dm) |
| 20–21 | 6 | `aplica-01`, `aplica-02` — aplicam o julgamento (arquivos disjuntos, em paralelo) | sonnet | a conversa | 01: 1.584→1.426 l; 02: 842→625 l | conferido por #22 |
| 22 | 6 | `conferencia` — citações e referências cruzadas pós-edição | sonnet | — | 139 ok / 1 ERRO; 4 resíduos | aceito; a sessão corrigiu os 4 resíduos em linha (citação `forAgent:69-76`, símbolo em `GuardaPrompt.php:max_tokens:19`, `[DG-03]` no passo certo, `npm ci` antes do PR) |
| 23 | 4 | `deriva-04` (`feature-test-design`) — deriva o `04` e o `05` a partir do `00` + recorte de 85 linhas do `01` (paths, rotas, Superfície de UI, dependências) | herdado (Opus 5.5) | `01`/`02` inteiros, a conversa | `04`: 38 regras, 55 cenários, 134 mutantes; `05`: 3 CT-B, 11 mutantes; 18 perguntas | citações do `04`/`05` conferidas pelo script estendido da sessão (125 ok; 2 "ERRO" são a citação envelhecida `KitServiceProvider.php:172` reproduzida de propósito); `git status` só a wiki |
| 24 | 4 | `monta-03` — monta este `03` espelhando os 21 passos do `01` | **sonnet** | a conversa, o `04` | 308 linhas, 20 seções de passo ("8 e 9" unidas, como no `01`), 91 checkboxes abertos | `grep -n '^- \[x\]'` → só o exemplo dentro do comentário do template |
| 25 | 4 | `adversario-c1` — revisão adversarial cega (`fw-adversario-ct`) | herdado (Opus 5.5) | `01`, `02`, `03`, o código da feature | 18 achados (3 alta, 13 média, 2 baixa) | aceito: todos com implementação errada concreta |
| 26 | 4 | `revisa-04-c1` — fecha os achados do ciclo 1 | herdado | `01`/`02` inteiros | CT-56..CT-77; 186 mutantes | revisto pelo ciclo 2 |
| 27 | 4 | `adversario-c2` — re-revisão cega (teto da skill: 2 rodadas) | herdado | idem #25 | 22 achados (3 alta, 16 média, 3 baixa) — **não convergiu** (18 → 22) | aceito; os 3 estruturais (A2-01..A2-03) escalados ao mantenedor como premissas P-29..P-31 |
| 28 | 4 | `revisa-04-c2` — fecha os achados do ciclo 2 | herdado | `01`/`02` inteiros | 46 regras, 104 cenários (CT-01..CT-104), 259 mutantes no `04`; 43 perguntas | **sem 3ª revisão** (teto); a sessão incorporou as 43 perguntas ao `00` e decidiu P-03 e P-06 |

**Sem despacho — em linha, por exceção**: captura verbatim do `00` e dos Adendos 1 e 2 (a skill proíbe passar a fonte por resumo de sub-agente); as quatro perguntas de escopo ao mantenedor; as decisões 1/7/8 da sessão sobre as perguntas do 01; o julgamento do ponytail; a conferência do docblock de `AgenteIa` (`grep -rnE "temperature|maxTokens" app/Ai/` → vazio).

## Blockers
<!-- Impedimentos encontrados durante implementação -->
- [ ] {Blocker 1}: {descrição + o que está sendo feito para resolver}

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- {Passo X alterado}: {motivo}

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- Os campos `temperatura` e `max_tokens` do catálogo de agentes no `/admin` não chegam ao provider — `TextGenerationOptions::forAgent` lê `temperature()`/`maxTokens()` do agente e `AgenteBase` não implementa; fora do escopo, corrigido só o docblock (passo 1); achado entregue ao mantenedor.

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: {ponto positivo}
- **Faltou no plano**: {ponto de melhoria para próxima wiki}
