# Agentes de IA, Boost e skills

> O que está instalado para trabalhar **neste** repositório com um agente de código (Claude Code, Codex, Cursor, Junie, OpenCode). Não confundir com a [camada de IA da aplicação](ia.md), que é runtime do produto.

## Laravel Boost

O [Laravel Boost](https://github.com/laravel/boost) está instalado e configurado em `boost.json`:

```json
{ "agents": ["claude_code", "codex", "junie", "opencode", "cursor"],
  "guidelines": true, "mcp": true, "skills": [ ... ] }
```

Ele entrega três coisas:

| Entrega | Onde | Observação |
|---|---|---|
| **Guidelines** | `AGENTS.md` e `CLAUDE.md` (idênticos) | **gerados** — não edite à mão, o `boost:update` sobrescreve |
| **Servidor MCP** | `.mcp.json`, `.cursor/mcp.json`, `.junie/mcp/mcp.json`, `opencode.json` → `php artisan boost:mcp` | ferramentas que leem a aplicação de verdade |
| **Skills** | `.claude/skills/`, `.ai/skills/`, `.agents/skills/`, `.cursor/skills/`, `.junie/skills/` | uma cópia por agente |

### Ferramentas MCP que valem o hábito

| Ferramenta | Use no lugar de |
|---|---|
| `search-docs` | procurar documentação genérica — ela retorna a doc **da versão instalada** |
| `database-schema` | abrir migration atrás da estrutura da tabela |
| `database-query` | `tinker` com SQL cru (é read-only) |
| `list-artisan-commands` | adivinhar assinatura de comando |
| `get-absolute-url` | montar URL à mão antes de mandar para o usuário |
| `browser-logs` | perguntar ao usuário o que apareceu no console |
| `record-rule` | anotar regra durável — grava em `.ai/rules`, versionado e compartilhado com o time (memória nativa do agente é pessoal e some) |

## Skills instaladas

As skills listadas no `boost.json`, sincronizadas para todos os agentes. Ative a que corresponde ao domínio **assim que entrar nele** — não espere travar.

| Skill | Quando ativar |
|---|---|
| **`feature-wiki`** | ao iniciar **qualquer feature nova** — chame `/feature-wiki` antes de qualquer `make:` |
| `feature-test-design`, `feature-quality-gate`, `requirement-to-rule` | chamadas pela `feature-wiki` nos steps 7, 11 e 12 — ver abaixo |
| `feature-tickets` | só por `/feature-tickets`, quando o plano não cabe numa sessão (o step 8 sugere) |
| `laravel-best-practices` | qualquer PHP Laravel (traz `rules/` por tema: eloquent, queue, security, testing…) |
| `pest-testing` | escrever, editar ou consertar teste |
| `ai-sdk-development` | mexer em `app/Ai/`, agentes, tools, guardrails, streaming |
| `tailwindcss-development` | Blade, Tailwind, layout, dark mode |
| `pulse-development` | Pulse: cards, recorders, autorização do dashboard |
| `laravel-backup` | `spatie/laravel-backup`: destino, limpeza, monitor, notificação |
| `blaze-optimize` | otimização de componentes Blade com `livewire/blaze` |
| `infer-conventions` | levantar/registrar convenções do projeto em `.ai/rules` |

### `feature-wiki` — a skill do fluxo

De [gsferro/laravel-ai-skills](https://github.com/gsferro/laravel-ai-skills), versão 4.0.0, com as outras quatro skills da coletânea. Cria, **antes de implementar**, a pasta `wikis/specs/{branch}/{feature}/`:

| Arquivo | Conteúdo | Quem escreve |
|---|---|---|
| `00-requisito.md` | o pedido **verbatim**, imutável, decomposto em cláusulas `RQ`; o que cresce vira Adendo | `feature-wiki` |
| `01-plano-acao.md` | PRD — detalhado ao ponto de um agente implementar sem ambiguidade | `feature-wiki` |
| `02-decisoes-arquiteturais.md` | ADRs: contexto, decisão, alternativas descartadas, consequências | `feature-wiki` |
| `03-progresso.md` | checklist espelhando o plano, despachos de sub-agente, desvios, retrospectiva | `feature-wiki` |
| `04-casos-de-teste.md`, `05-casos-de-teste-browser.md` | casos de teste derivados **do requisito**, com técnica formal e gate de mutantes | `feature-test-design` (step 7) |
| `06-relatorio-qa.md` | o veredito do quality gate, com a matriz de rastreabilidade | `feature-quality-gate` (step 11) |
| `07-tickets/` | as fatias, quando o plano não cabe numa sessão | `feature-tickets` (step 8, condicional) |

Ela também define o **padrão de log do projeto** (`[Classe@Método] mensagem | parâmetro`, channel por feature, context estruturado) — resumido em [convencoes.md](convencoes.md#padrão-de-log).

**Quando não invocar:** typo, ajuste trivial de config ou CSS, refactor puro, bump de dependência, seeder isolado. Critério: se não adiciona lógica de negócio, não altera fluxo de dados e não cria arquivo de código novo, não precisa de wiki.

Instalação e atualização (já feitas neste repositório; o `kit:update` entrega as skills e os agentes a quem já instalou):

```bash
php artisan boost:add-skill gsferro/laravel-ai-skills --all --force   # as cinco skills; já roda o boost:update
cp .ai/skills/*/agents/*.md .claude/agents/                          # os sub-agentes, que o Boost não copia
```

Depois, reabra a sessão do Claude Code — a lista de sub-agentes é lida quando a sessão abre.

### Os sub-agentes da esteira

No Claude Code, a `feature-wiki` despacha o trabalho que exige independência para cinco agentes em
`.claude/agents/`: `fw-revisor-diff` (revisão do diff, cego ao plano), `fw-executor-ct` e
`fw-executor-ctb` (escrevem os testes a partir do `04`/`05`, sem tocar `app/`), `fw-adversario-ct`
(ataca os casos de teste lendo só o `00` e o `04`/`05`) e `fw-qa-gate` (o quality gate, sem gravar
nada). Desde a 4.0.0, cada um declara um hook `PreToolUse` que roda
`.ai/skills/feature-wiki/scripts/guarda-subagente.sh` e nega, antes de a ferramenta rodar, a leitura
e a edição fora do perfil.

- **No Windows, o hook exige o Git Bash** (Git for Windows). Sem ele, roda no PowerShell, nega toda
  ferramenta, e a sessão cai no fallback `general-purpose` — sem restrição de ferramenta.
- **A cópia para `.claude/agents/` é à mão**, e esquecê-la não dá erro: a sessão segue com o agente
  da versão anterior, sem o hook. `tests/Kit/AgentesDaEsteiraTest.php` fica vermelho nesse caso.

## O trio, no Claude Code

Além das skills do Boost, dois plugins estão habilitados em `.claude/settings.json`:

```json
{ "enabledPlugins": { "ponytail@ponytail": true, "caveman@caveman": true } }
```

Cada um cobre uma camada diferente do ciclo:

| Camada | Ferramenta | Responsabilidade | Onde **não** se aplica |
|---|---|---|---|
| Comunicação (agente ↔ você) | [Caveman](https://github.com/JuliusBrussee/caveman) | prosa enxuta, corta o excesso de tokens | arquivos da wiki, código, commits, PRs, avisos de segurança |
| Planejamento | `feature-wiki` | PRD + ADR + CTs + tracking | — (a wiki é detalhada **por design**) |
| Execução | [Ponytail](https://github.com/DietrichGebert/ponytail) | escada da simplicidade: reusar → stdlib → feature nativa → uma linha → mínimo que funciona | não corta validação, segurança nem tratamento de erro |

Fronteira que o kit torna explícita: **arquivos da wiki são boundary do Caveman**. Comprimir um PRD destrói a propriedade que o faz existir (implementável sem ambiguidade). O mesmo vale para ADR, casos de teste e os `05-*.md` extras.

Instalação dos plugins, se você for replicar em outro projeto:

```
/plugin marketplace add DietrichGebert/ponytail
/plugin install ponytail@ponytail
/plugin marketplace add JuliusBrussee/caveman
/plugin install caveman@caveman
```

### Auditoria do plano

A `feature-wiki` invoca **`/ponytail:ponytail-review`** sozinha no step 6, depois de escrever do `00` ao `03` e da revisão profunda, para caçar over-engineering **no plano** — antes de custar tempo de implementação. Depois de implementar, roda de novo, agora no diff.

> O comando exige o namespace: `/ponytail:ponytail-review`. Sem ele não é encontrado.

## Fluxo recomendado de uma feature

Na numeração dos steps da `feature-wiki` 4.0.0:

1. **Steps 0–4** — `feature-wiki` → cria `00` a `03` em `wikis/specs/{branch}/{feature}/` (pesquisa antes de escrever: `search-docs`, `database-schema`, leitura do código real).
2. **Step 5** — revisão profunda: revalidar cada premissa do plano contra o código.
3. **Step 6** — `/ponytail:ponytail-review` na wiki → aplicar os cortes.
4. **Step 7** — `feature-test-design` deriva o `04`/`05` do requisito, com revisão adversarial pelo `fw-adversario-ct`.
5. **Step 8**, condicional — `/feature-tickets` quando o plano não cabe numa sessão.
6. Aprovação do usuário e implementação com Ponytail ativo, marcando checkboxes de `03-progresso.md` **em tempo real**; `vendor/bin/pint --dirty` → `php artisan test --compact --filter=…` → `composer test:kit` se tocou a fundação.
7. **Step 9** — revisão do diff por quem não implementou: `/code-review` e `fw-revisor-diff`, mais `/ponytail:ponytail-review` no diff.
8. **Step 10** — reconciliação: fechar `03-progresso.md` (desvios, notas, retrospectiva) e alinhar wiki, código e testes.
9. **Step 11** — `/feature-quality-gate` pelo `fw-qa-gate` → `06-relatorio-qa.md`; só então o PR, com a wiki linkada.
10. **Step 12** — candidatos a regra, por `/requirement-to-rule`, com a sua aprovação.

## Onde cada configuração de agente mora

| Arquivo | Versionado? | O quê |
|---|---|---|
| `boost.json` | sim | agentes, skills e pacotes cobertos pelo Boost |
| `AGENTS.md` / `CLAUDE.md` | sim (gerados) | guidelines do Boost |
| `.mcp.json` | sim | servidor MCP para Claude Code |
| `.claude/settings.json` | sim | plugins Ponytail e Caveman |
| `.claude/agents/` | sim | os cinco sub-agentes `fw-*` da esteira, copiados de `.ai/skills/*/agents/` |
| `.claude/`, `.ai/`, `.agents/`, `.junie/` | sim | cópias das skills |
| `.cursor/`, `.codex/` | **não** (`.gitignore`) | mesmas skills, geradas por `boost:update` |
| `.ai/rules/` | sim, quando existir | regras duráveis por glob, gravadas com `record-rule` |

## Atualizar o projeto pelo agente

Para atualizar o projeto pedindo a um agente, sem rodar o `kit:update` à mão, mande o agente seguir o
**roteiro de atualização** da versão destino. Cada publicação do kit tem o seu, que cobre só a passagem da
versão anterior para ela: o que muda, os cuidados, as decisões que são suas (o agente pergunta antes do
passo, com a recomendação da release, e sem resposta segue o default seguro e registra), os passos e a
conferência. O agente usa o próprio `kit:update` como ferramenta. Salto de várias versões é feito um
roteiro por vez, em ordem.

O link do roteiro segue este padrão, com a versão destino e o `v` da tag nas duas posições:

```text
https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md
```

A exceção é a v0.52.0, cujo roteiro nasceu depois da tag: o link dela aponta para a `main`. Ao montar o
link pelo padrão, o agente lê também o mesmo arquivo na `main` (com `main` no lugar de `v{versão}`) e
prefere o da `main` quando ele trouxer a linha `Roteiro revisado em`, que marca uma correção publicada
depois da tag. Se as duas cópias divergirem sem essa linha, ou se uma delas não puder ser lida, na dúvida,
o agente para e pergunta a você.

O pedido-modelo, num agente em sessão nova:

```text
Atualize este projeto seguindo o roteiro em https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md
```

Abaixo, a cópia do roteiro da versão instalada, para consulta depois da atualização. Ela é trocada a cada
versão; o roteiro que vale para a próxima atualização é sempre o do link.

<!-- roteiro-de-atualizacao:inicio -->
# Roteiro de atualização — v0.56.0 → v0.57.0

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.56.0

Destino: v0.57.0

Data: 2026-11-26

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.57.0/wikis/atualizacoes/v0.57.0.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.56.0 para a v0.57.0 seguindo este roteiro”. Ele pode vir com o link da Identificação ou sem ele; sem o link, monte-o pelo padrão com a versão destino `v0.57.0` e confira que é o mesmo endereço da Identificação. O agente segue os passos abaixo usando o `kit:update` para aplicar os arquivos.

Rode as conferências na raiz do projeto. Os comandos valem para Git Bash e PowerShell, exceto onde aparecem as duas formas. Guarde as saídas para o relatório final.

1. **A árvore do git está limpa.**

   ```bash
   git status --porcelain
   ```

   Esperado: saída vazia. Com alguma linha, pare e peça ao dono que commite ou guarde o trabalho em andamento. Não faça isso por conta própria.

2. **A versão instalada é a origem deste roteiro.**

   ```bash
   php artisan config:clear
   php artisan tinker --execute 'echo config("kit.version");'
   ```

   Esperado: `0.56.0`. Outro valor encerra este roteiro sem aplicar arquivos: `0.57.0` indica marcador já no destino; uma versão anterior exige as atualizações intermediárias, em ordem; uma versão posterior exige outro roteiro. Informe ao dono o valor encontrado.

3. **O banco está acessível e a impressão dos dados foi guardada.**

   Confirme com o dono que há backup recuperável antes de aplicar os arquivos.

   ```bash
   php artisan migrate:status
   php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
   php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => array_map("md5", App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "configuracoes" => md5(json_encode(app(App\Settings\ConfiguracoesDoKit::class)->toArray()))]);'
   ```

   Esperado: consulta sem erro e nenhuma migration `Pending`. Havendo pendência do projeto, informe os nomes e pare; esta atualização não executa migrations. Anote a multi-organização, `SIM` ou `NAO`, e guarde integralmente a última saída: um hash por usuário, um por organização e um das Configurações da aplicação. Essa **impressão dos dados** deve ser idêntica ao final, sem excluir campos.

4. **Os nomes reservados pelo comando estão livres.**

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.57.0
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.57.0`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à release.**

   ```bash
   php artisan kit:update --from=0.56.0 --tag=v0.57.0 --dry-run --keep-remote --no-interaction
   git diff --stat kit-v0.56.0 kit-v0.57.0 -- composer.json composer.lock package.json package-lock.json
   git diff --name-status kit-v0.56.0 kit-v0.57.0 -- app bootstrap database resources public routes lang .env.example phpunit.xml
   git diff --name-status kit-v0.56.0 kit-v0.57.0 -- .ai/rules wikis/glossario.md config/kit.php tests/Pest.php tests/Kit tests/Tenancy tests/Browser
   ```

   Esperado: resumo entre `kit-v0.56.0` e `kit-v0.57.0`, sem tocar arquivos; diff de dependências vazio; a terceira linha sem saída nenhuma (nada muda em código da aplicação, migrations, settings, views, assets, rotas, traduções, `.env.example` ou `phpunit.xml`); a quarta lista exatamente os arquivos de **O que muda nesta versão**. O dry-run mantém o remote `kit` e as tags para as comparações seguintes.

   Se a tag ainda não estiver disponível, pare e informe ao dono. Não substitua a tag pela `main`. Dependência alterada, arquivo de aplicação, migration ou asset alterado, ou ausência de parte da entrega exige parada com o diff encontrado.

6. **As edições do dono foram identificadas.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.56.0 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.57.0 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos; repita sem `--name-only` para descrever a edição ao dono. Confira especialmente `.ai/rules/index.md` (rules próprias do dono entram nele), `wikis/glossario.md` (termos próprios do dono), `tests/Pest.php` e `config/kit.php`. O `tests/Pest.php` ganha 35 funções globais novas, as dos testes do mapa; liste-as e procure, nos testes próprios do dono, função com o mesmo nome:

   ```bash
   git diff kit-v0.56.0 kit-v0.57.0 -- tests/Pest.php | grep "^+function"
   ```

   Para cada nome impresso, `git grep -n "function nomeDaFuncao(" -- tests` fora do `tests/Pest.php` indica uma redeclaração que o PHP recusaria; registre como conflito.

## O que muda nesta versão

A v0.57.0 traz o **mapa interativo do kit** e o **catálogo único de features**: cada painel, sem e com o tenant ativo, e os fluxos do kit viram diagramas interativos gerados do código, com a prova de cada ligação, e o roteiro de features do site passa a ser o catálogo único, com 50 features novas numeradas. Tudo isso vive no site de documentação (`site/` e o diretório das páginas do site), que é material do repositório do kit: fica fora do pacote do `composer create-project` e fora da lista de caminhos do `kit:update`. **Nada muda no comportamento do projeto**: nenhum arquivo de `app/`, `config/` (fora o marcador), `database/`, `resources/`, `public/` ou `routes/` mudou entre as duas tags.

Confira estes arquivos que o `kit:update` traz:

- `.ai/rules/catalogo-de-features.md`, novo: a rule do catálogo de features. Ela declara que só vale no repositório do kit, onde existe o catálogo; no projeto, fica como referência para o agente e não exige nada;
- `.ai/rules/index.md`: a linha que mapeia essa rule;
- `wikis/glossario.md`: cinco termos novos (peça, ligação, peça condicional, condição de disponibilidade e catálogo de features);
- `tests/Pest.php`: os helpers das guardas do mapa (35 funções globais novas, como `modeloDoMapa()` e `diagramasDoMapa()`);
- novos `tests/Kit/MapaInterativoCatalogoTest.php`, `tests/Kit/MapaInterativoDiagramasTest.php`, `tests/Kit/MapaInterativoPublicacaoTest.php`, `tests/Kit/MapaInterativoTest.php` e `tests/Tenancy/MapaInterativoTenancyTest.php`: leem o site e o modelo do mapa, que não existem no projeto, então **todos os 439 casos pulam** fora da árvore do kit, pela guarda `naArvoreDoKit()`;
- `tests/Kit/KitArteTest.php`, `tests/Kit/LinkDoPainelSemTenancyTest.php` e `tests/Browser/RoteiroDoKitTest.php`: só comentários (citações de linha do `tests/Pest.php` e a descrição do roteiro de features);
- `config/kit.php`, com o marcador `0.57.0`.

Por causa desses cinco arquivos, o teto de pulados da suíte `Kit,Tenancy` fora da árvore do kit sobe de **1161 para 1600** (+439, medidos arquivo a arquivo: 64 do catálogo, 148 dos diagramas, 65 da publicação, 135 do modelo e 27 do de multi-organização).

Não há migration, alteração de settings, dependência PHP ou JS nova, asset publicado nem default mudado. Basta aplicar os arquivos e conferir o resultado; não há instalação de pacotes, build, semeadura ou geração de permissões a executar. Os READMEs, a lista de mudanças, as páginas do site e a cópia versionada deste roteiro não são aplicados pelo `kit:update`; integre à documentação do projeto só o que o dono quiser e registre no relatório o que foi integrado ou considerado inaplicável.

## Cuidados

- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker. Esta versão não pede alteração de esquema nem de settings.
- **Preserve a multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **Não traga o site para o projeto.** O mapa, o catálogo e o gerador dos diagramas são do repositório do kit; copiar `site/` ou as páginas do site para o projeto não faz os testes do mapa rodarem, e acrescenta um toolchain de documentação que o projeto não usa. Os 439 pulados novos são esperados.
- **O `tests/Pest.php` e os testes do mapa andam juntos.** Os cinco arquivos novos chamam os helpers que só o `tests/Pest.php` novo define. Manter o `tests/Pest.php` antigo com os testes novos dá erro de função indefinida; função própria do dono com o mesmo nome de um helper novo dá redeclaração, e o PHP recusa. Registre cada limitação como entrega parcial.
- **As rules e o glossário são do dono também.** Rule própria listada no `.ai/rules/index.md` e termo próprio no `wikis/glossario.md` não podem sumir na troca: integre só a linha nova do índice e os cinco termos novos.
- **A suíte pode recriar tabelas no banco que receber.** Force as três variáveis de banco do passo 4, depois de limpar o cache de configuração. Faça escolhas manuais somente em ambiente de testes com dados descartáveis.
- **O `--all` substitui arquivos existentes.** Resolva a decisão de conflito antes e restaure ou integre as edições do dono antes de validar. O marcador `0.57.0` sozinho não prova que toda a entrega chegou.
- **Preserve `.env`, canais de log e customizações.** Esta versão não exige editar o `.env` ou o `config/logging.php`. Ao preservar `config/kit.php`, altere somente o marcador de versão.
- **As mudanças ficam para revisão.** Use `git status` e `git diff --cached`; não commite, não faça merge nem apague o branch por conta própria. Desfazer exige pedido do dono: `git reset --hard HEAD` desfaz arquivos e índice; avalie arquivos não rastreados um a um. Isso não reverte operações feitas no banco.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo, com os diffs, antes do passo 1. Sem conflitos, registre “nenhum conflito” e siga. Nenhuma outra decisão do dono nesta versão: nada muda no comportamento nem nos defaults.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos têm edição sua e a v0.57.0 também os altera; manter o `tests/Pest.php` antigo deixa os testes do mapa sem os helpers, e manter o índice de rules ou o glossário antigos deixa a rule nova sem mapeamento ou os termos novos de fora: para cada arquivo, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas após revisar os diffs, preservando as rules e os termos próprios do dono e mantendo uma única definição de cada função global de teste. Em `config/kit.php`, preservar suas configurações e alterar só a versão para `0.57.0`.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono.

### 1. Aplicar os arquivos

Condição: as seis conferências do pré-voo passaram, e cada conflito tem resposta ou default seguro registrado.

Ação:

```bash
php artisan kit:update --from=0.56.0 --tag=v0.57.0 --all --keep-remote --no-interaction
```

Evidência: arquivos anunciados como `aplicado:`, saída `config/kit.php: versão marcada como 0.57.0` e branch `kit-update/v0.57.0`. Se o comando recusar a árvore, pedir segunda execução ou anunciar outra versão, pare; não faça commit para tentar outra rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: há conflitos; sem eles, siga para o passo 3.

Ação, arquivo a arquivo: para manter a versão do dono, use `git checkout HEAD -- caminho/do/arquivo`; para receber a do kit, mantenha o aplicado; para integrar, reaplique somente as mudanças do dono conforme o diff guardado. Integração incerta exige parada. No `config/kit.php` preservado, troque apenas `'version' => '0.56.0',` por `'version' => '0.57.0',`.

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

Evidência: arquivos mantidos têm diff vazio contra `HEAD`, integrações contêm ambas as mudanças revisadas e o config preserva as demais configurações. Registre cada parte da entrega que não chegou. Se o `tests/Pest.php` foi mantido antigo, registre que os testes do mapa não podem ficar no projeto sem ele; se uma função própria do dono foi renomeada para evitar a redeclaração, registre o nome antigo e o novo.

### 3. Conferir marcador e arquivos

Condição: aplicação e conflitos concluídos.

Ação:

```bash
php artisan optimize:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git diff HEAD -- .ai/rules/index.md wikis/glossario.md config/kit.php
```

Evidência: versão `0.57.0`, branch `kit-update/v0.57.0` e arquivos da entrega presentes, com o conteúdo do destino ou a integração aprovada. Compare cada caminho listado em **O que muda nesta versão** com `git diff kit-v0.57.0 -- caminho/do/arquivo`; diferenças devem corresponder somente às decisões registradas. Confira os seis arquivos novos (`.ai/rules/catalogo-de-features.md` e os cinco de teste do mapa) com `git ls-files --error-unmatch caminho/do/arquivo` depois do `git add`, ou com `git status --short`. O índice corresponde ao dry-run. Não execute migrations nem publicação de assets nesta atualização.

### 4. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos conferidos, incluindo os helpers no `tests/Pest.php`.

Ação, no Git Bash:

```bash
php artisan config:clear
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=sqlite:///:memory: php artisan test --testsuite=Kit,Tenancy --parallel --compact
```

Ação equivalente no PowerShell, preservando as variáveis da sessão:

```powershell
php artisan config:clear
$bancoAnteriorConnection = $env:DB_CONNECTION
$bancoAnteriorDatabase = $env:DB_DATABASE
$bancoAnteriorUrl = $env:DB_URL

try {
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = ':memory:'
    $env:DB_URL = 'sqlite:///:memory:'
    php artisan test --testsuite=Kit,Tenancy --parallel --compact
    $codigoDaSuite = $LASTEXITCODE
}
finally {
    $env:DB_CONNECTION = $bancoAnteriorConnection
    $env:DB_DATABASE = $bancoAnteriorDatabase
    $env:DB_URL = $bancoAnteriorUrl
}

$codigoDaSuite
```

O `DB_URL` também precisa apontar para o banco em memória; vazio pode permitir que o `.env` decida o banco usado.

Evidência: código zero, zero erro, zero falha e no máximo **1600 pulados** fora da árvore do kit: o teto de 1161 recebido da origem mais os 439 casos dos cinco arquivos do mapa, que leem o site e o modelo do mapa, ausentes do projeto. Registre a quantidade efetiva; aumento além disso exige listar as causas e parar, sem alterar os testes para passar.

Para a parte nova, rode também os cinco arquivos do mapa, com as mesmas variáveis de banco acima:

```bash
php artisan test --compact tests/Kit/MapaInterativoCatalogoTest.php tests/Kit/MapaInterativoDiagramasTest.php tests/Kit/MapaInterativoPublicacaoTest.php tests/Kit/MapaInterativoTest.php tests/Tenancy/MapaInterativoTenancyTest.php
```

Evidência: 439 pulados, nenhum verde, nenhuma falha e nenhum erro. Erro de função indefinida ou redeclarada aponta para o `tests/Pest.php` fora da versão do destino ou para uma função própria do dono com o mesmo nome.

Depois da suíte, confira as policies que ela pode gerar ao semear permissões para Resources do dono:

```bash
git status --porcelain app/Policies
```

Registre os caminhos com `??` gerados pela suíte e remova somente esses arquivos não rastreados:

```bash
git clean -f -- app/Policies
git status --porcelain app/Policies
```

Evidência: nenhuma policy não rastreada gerada pela suíte permanece; policies rastreadas continuam preservadas. Alteração rastreada exige parada e análise. Depois desta validação, peça ao dono que rode a suíte completa com `php artisan test --compact` em ambiente de testes com banco descartável.

### 5. Conferir a preservação dos dados e do projeto

Condição: suíte concluída e variáveis PowerShell restauradas, quando aplicável.

Ação: repita os três comandos de consulta do item 3 do pré-voo e compare as saídas guardadas. Confira também:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
git diff HEAD -- .env config/logging.php
git status --short
git diff --cached --stat
```

Evidência: `0.57.0`, mesma multi-organização, mesmo estado das migrations e impressão dos dados idêntica. Diff vazio de `.env` e `config/logging.php`; customizações preservadas conforme as decisões. Nenhuma escrita no banco do dono foi necessária.

### 6. Remover o remote e as tags temporários

Condição: ao terminar ou parar depois da criação dos nomes temporários; o pré-voo confirmou que não existiam.

Ação, no Git Bash:

```bash
git tag -l "kit-*" | xargs -r git tag -d
git remote remove kit
```

Ação equivalente no PowerShell:

```powershell
git tag -l "kit-*" | ForEach-Object { git tag -d $_ }
git remote remove kit
```

Se uma tentativa anterior já removeu algum nome, confira e remova somente o que ainda existir. Evidência: `git remote` não lista `kit` e `git tag -l "kit-*"` tem saída vazia. O branch `kit-update/v0.57.0` permanece, com as mudanças para revisão e sem commit.

## Como conferir

O estado esperado é o dos cenários 3 e 4 de atualização: com ou sem multi-organização, versão e arquivos conferidos, dados e customizações preservados.

| O que | Esperado |
|---|---|
| Marcador | `0.57.0` depois de limpar o cache de configuração |
| Arquivos da entrega | Rule do catálogo, índice de rules, glossário, `tests/Pest.php`, os cinco testes do mapa, os três testes com comentário atualizado e config conferidos contra o destino ou a integração aprovada |
| Código da aplicação | Nenhum arquivo de `app/`, `database/`, `resources/`, `public/` ou `routes/` alterado pelo `kit:update` |
| Migrations, settings, assets e dependências | Nenhum novo; nenhuma execução necessária |
| Defaults e multi-organização | Nenhum default mudado; mesmo `SIM` ou `NAO` do pré-voo |
| Suíte Kit,Tenancy | Zero erro, zero falha, até 1600 pulados fora da árvore do kit |
| Testes do mapa | Os 439 casos dos cinco arquivos pulados, sem erro de função indefinida ou redeclarada |
| Policies | Não rastreadas geradas pela suíte removidas e listadas; rastreadas preservadas |
| Arquivos do dono | Rules e termos próprios preservados; `.env` e canais em `config/logging.php` intactos |
| Dados do dono | Impressão final idêntica à inicial |
| Revisão | Branch `kit-update/v0.57.0`, índice revisável, sem commit; remote e tags temporários removidos |

Não há tela nova nem mudança visível nos painéis para conferir: o mapa interativo é publicado no site de documentação do kit, não no projeto. Se quiser, abra `/admin` e `/app` e confirme que nada mudou. Registre o resultado, ou que a conferência não foi executada e o motivo. Arquivo mantido antigo é entrega parcial, mesmo com marcador `0.57.0`.

## Quando parar

Pare e entregue ao dono a condição, a saída relevante e o que falta para continuar:

- Roteiro indisponível, cópias divergentes sem marca de revisão ou dúvida de interpretação.
- Árvore suja no início, versão instalada diferente de `0.56.0`, banco inacessível, backup não confirmado ou migrations pendentes do projeto: liste os valores e caminhos encontrados.
- Remote, tags ou branch reservados já existentes: informe os nomes, sem removê-los.
- Tag `v0.57.0` indisponível ou dry-run divergente: mostre os diffs, principalmente dependências, código da aplicação, migrations, assets ou parte da entrega ausente.
- Comando recusado, pedido de segunda rodada, aplicação incompleta, integração incerta ou marcador, branch e arquivos fora do previsto: descreva o estado parcial, sem commit ou nova rodada.
- Suíte com erro, falha ou mais de 1600 pulados: entregue a saída e os casos envolvidos. Caso do mapa que **não** pula fora da árvore do kit é defeito da release: registre o caso e a mensagem. Se houver erro de função definida duas vezes ou ausente, registre a causa nos arquivos de teste e no `tests/Pest.php`, sem enfraquecer os testes.
- Policy rastreada alterada, impressão de dados diferente, multi-organização alterada ou mudança inesperada em `.env`, canais de log, rules, glossário ou customizações: mostre a diferença antes de restaurar qualquer coisa.
- Um passo exigiria ação proibida em **Cuidados**: informe a necessidade e aguarde orientação.

Ao parar depois de criar os nomes temporários, execute ainda o passo 6 para remover somente o remote e as tags deste roteiro. Preserve o branch e as mudanças para revisão. Este roteiro não promete funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida, pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- versões de partida e chegada, saída de `config('kit.version')`, branch e confirmação de que nada foi commitado nem integrado por merge;
- arquivos aplicados e conferidos, os seis arquivos novos e as integrações, em especial do índice de rules, do glossário e do `tests/Pest.php`;
- cada decisão de conflito, pergunta e resposta ou “sem resposta”, default aplicado e limitações por arquivo; sem conflitos, registre isso;
- funções próprias do dono que colidiam com os helpers novos e o que foi feito com cada uma, ou “nenhuma colisão”;
- confirmação de que não houve migration, alteração de settings, dependência nova, asset publicado, default mudado, instalação, build, semeadura ou geração de permissões, e de que nenhum arquivo de código da aplicação mudou;
- saída literal da suíte Kit,Tenancy, contagem efetiva de pulados contra o teto de 1600 e pedido para rodar a suíte completa em ambiente de testes;
- saída dos cinco arquivos de teste do mapa, com os 439 pulados;
- o destino dos READMEs, da lista de mudanças, das páginas do site e da cópia deste roteiro: integrado ou considerado inaplicável;
- policies não rastreadas removidas, impressões dos dados antes e depois e resultado da comparação;
- preservação de `.env`, canais de log, rules e termos próprios, customizações e estado da multi-organização;
- remoção do remote `kit` e das tags `kit-*`;
- em caso de parada, passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar com `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Isso não reverte operações no banco; esta atualização não exigiu nenhuma no banco do dono.
<!-- roteiro-de-atualizacao:fim -->
