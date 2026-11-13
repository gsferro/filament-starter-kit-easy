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

# Roteiro de atualização — v0.52.0 → v0.52.1

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.52.0

Destino: v0.52.1

Data: 2026-11-13

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.52.1/wikis/atualizacoes/v0.52.1.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.52.0 para a v0.52.1 seguindo este roteiro”, acompanhado do link da Identificação.

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

   Esperado: `0.52.0`. Outro valor encerra este roteiro sem aplicar arquivos: `0.52.1` indica que o marcador já está no destino; uma versão anterior a `0.52.0` exige primeiro as atualizações intermediárias, em ordem; uma versão posterior a `0.52.1` exige outro roteiro. Informe ao dono o valor encontrado.

3. **O banco está acessível, e a impressão dos dados foi guardada.**

   ```bash
   php artisan migrate:status
   php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
   php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "configuracoes" => md5(json_encode(app(App\Settings\ConfiguracoesDoKit::class)->toArray()))]);'
   ```

   Esperado: migrations consultadas sem erro e nenhuma `Pending`. Se houver pendência, informe ao dono quais são e pare; esta atualização não traz migration nem autoriza executar as dele.

   Anote o estado da multi-organização, `SIM` ou `NAO`, e guarde integralmente a última saída: um hash por usuário e um das Configurações da aplicação. Essa é a **impressão dos dados**, que deve ser idêntica ao final. Nenhuma configuração é excluída da comparação nesta versão.

4. **Os nomes reservados pelo comando estão livres.**

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.52.1
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.52.1`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à release, inclusive nas dependências.**

   ```bash
   php artisan kit:update --from=0.52.0 --tag=v0.52.1 --dry-run --keep-remote --no-interaction
   git diff kit-v0.52.0 kit-v0.52.1 -- composer.json
   git diff kit-v0.52.0 kit-v0.52.1 -- composer.lock
   git diff kit-v0.52.0 kit-v0.52.1 -- package.json package-lock.json
   git diff --name-status kit-v0.52.0 kit-v0.52.1 -- database/migrations database/settings tests/Kit tests/Tenancy
   ```

   Esperado:

   - resumo entre `kit-v0.52.0` e `kit-v0.52.1`, com os caminhos rotulados `novo no kit`, `modificado` ou `removido do kit`, e a informação de que nenhum arquivo foi tocado;
   - diff do `composer.json` vazio;
   - diff do `composer.lock` **não vazio**, com atualizações de versões e seus metadados, sem inclusão de pacote novo;
   - diff do `package.json` e do `package-lock.json` com bumps de versões, sem dependência nova;
   - nenhuma migration nova, nem em `database/migrations` nem em `database/settings`;
   - os arquivos novos `tests/Kit/CadastroNoPainelDoPapelTest.php`, `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php` e `tests/Kit/RoteiroDeAtualizacaoTest.php`.

   O dry-run mantém o remote `kit` e as tags com prefixo `kit-`, usados nas comparações seguintes. Ele não aplica os locks: `composer.json`, `composer.lock`, `package.json` e `package-lock.json` ficam fora dos caminhos aplicados pelo comando. O passo 4 de `## Passos` trata as dependências separadamente.

   Se a tag ainda não estiver disponível, pare e informe isso ao dono. Não substitua a tag pela `main`. Se o resumo ou os diffs não corresponderem às evidências acima, pare e mostre a divergência.

6. **As edições do dono foram identificadas antes de qualquer aplicação.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.52.0 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.52.1 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos. Repita sem `--name-only` para descrever a edição ao dono.

   Confira especialmente:

   - `app/Http/Responses/RespostaDeCadastro.php`;
   - `app/Support/DestinoAposLogin.php`;
   - `app/Console/Commands/KitCobertura.php`;
   - `database/seeders/DashboardPadraoSeeder.php`;
   - `database/seeders/DemoTenancySeeder.php`;
   - `database/seeders/ShieldPermissionsSeeder.php`;
   - `database/seeders/Onboarding/SeederDeOnboardingDeTenancy.php`;
   - `config/kit.php`, incluindo as credenciais e configurações do dono.

   Confira também o conjunto de dependências:

   ```bash
   git diff --name-only kit-v0.52.0 HEAD -- composer.json composer.lock package.json package-lock.json
   ```

   Se qualquer um desses quatro arquivos divergir da origem, trate o conjunto como conflito na decisão abaixo. Não substitua um lock isoladamente quando ele precisa representar dependências próprias do projeto.

## O que muda nesta versão

A v0.52.1 corrige o destino após o cadastro por convite quando o login unificado está desligado. O convite usa o cadastro do `/app`, mas a conta criada pode ter acesso somente a outro painel. Antes, ela podia terminar em 403 ao permanecer no `/app`.

Depois da correção, uma URL pretendida acessível continua tendo prioridade. Sem ela, se o painel corrente for inacessível, a conta segue para um painel que pode acessar: primeiro o painel padrão, quando acessível; senão, o primeiro acessível na ordem dos painéis registrados. Sem nenhum painel acessível, a sessão é encerrada e a pessoa volta ao login. O fluxo com painel corrente acessível e o modo de login unificado ligado mantêm seu comportamento.

Com a multi-organização ligada, a URL pretendida de uma organização que a conta não acessa (por exemplo `/app/outra` para quem só é da `acme`) passa a ser descartada, no cadastro e também no login unificado e no login social, que usam a mesma regra: antes ela era aceita e a pessoa terminava numa página de erro do painel. As rotas do painel que não são de organização (login, verificação de e-mail) continuam aceitas. Avise o dono dessa mudança no login, porque ela muda para onde vai quem chega por um link antigo de outra organização.

A implementação chega em `app/Http/Responses/RespostaDeCadastro.php` e `app/Support/DestinoAposLogin.php`. A regressão é coberta pelos testes novos `tests/Kit/CadastroNoPainelDoPapelTest.php` e `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php`, ambos em diretórios entregues pelo `kit:update`.

Quatro seeders passam a aceitar a ausência de um comando de console ao emitir avisos ou mensagens: `DashboardPadraoSeeder`, `DemoTenancySeeder`, `ShieldPermissionsSeeder` e `SeederDeOnboardingDeTenancy`. O `KitCobertura` também recebe ajustes de tipos exigidos pelas dependências atualizadas.

Os locks trazem bumps de dependências, incluindo Laravel 13.42.0, Laravel Settings 3.10.2, Larastan 3.15.0, Rector 2.8.4, Pint 1.32.3, Vite 8.3.8, Laravel Vite Plugin 3.2.1, Tailwind CSS 4.3.5 e Playwright 1.65.0. Não há pacote novo. Os arquivos de dependências exigem o tratamento separado e os installs do passo 4.

A versão também acrescenta a guarda dos roteiros de atualização e passa a entregar ao projeto instruções locais para futuras atualizações. O `config/kit.php` muda somente o marcador da versão para `0.52.1`.

Não há migration nova, jornada nova a semear, permissão nova a gerar ou default de configuração alterado.

## Cuidados

- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker.
- **Preserve o modo de multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **As correções dos seeders não pedem semeadura.** Não execute seeders no banco do dono para aplicar esta release. Isso poderia criar dados de demonstração ou redefinir permissões sem necessidade.
- **A suíte pode recriar tabelas no banco que receber.** Execute-a somente com as três variáveis de banco forçadas no próprio comando do passo 6, depois de limpar o cache de configuração.
- **O `--all` substitui arquivos existentes.** Resolva previamente a decisão de conflito e restaure ou reaplique as edições conforme o passo 2 antes de executar os demais passos.
- **Os dois arquivos do cadastro trabalham juntos.** Se um deles ficar integralmente na versão do dono, registre que a correção pode não ter sido incorporada. Não anuncie o defeito como resolvido apenas porque o marcador mudou.
- **Os installs usam os locks locais.** Executá-los sem antes tratar os arquivos de dependências não traz automaticamente as versões da release. Não sobrescreva dependências próprias do projeto nem execute atualização geral para contornar incompatibilidade.
- **O marcador não prova a entrega.** O comando pode marcar `0.52.1` depois de aplicar apenas parte dos arquivos. Confira o branch, os arquivos novos e os diffs.
- **O `.env` e os canais de log são do dono.** Esta versão não exige alterar o `.env` nem o `config/logging.php`.
- **As mudanças ficam para revisão.** O comando aplica arquivos no índice. Use `git status` e `git diff --cached`; não commite, não faça merge e não apague o branch por conta própria.
- **Desfazer exige pedido do dono.** `git reset --hard HEAD` desfaz alterações de arquivos e do índice, incluindo arquivos novos adicionados pelo update. Arquivos não rastreados precisam ser avaliados e removidos um a um. Isso não reverte o banco nem reinstala as dependências anteriores.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo antes do passo 1. Faça a pergunta com os diffs e a recomendação. Sem resposta, aplique o default abaixo e registre as limitações. Com a lista vazia, registre “nenhum conflito” e siga.

Nenhuma outra decisão do dono nesta versão.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos do kit têm edição sua e a v0.52.1 também os altera, incluindo o conjunto de dependências quando houver divergência: para cada arquivo ou conjunto, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas após revisar os diffs; no conjunto de dependências, preservar os pacotes e scripts próprios do projeto e manter manifests e locks compatíveis.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo ou por conjunto de dependências, o que deixou de chegar.

Explique junto da pergunta que manter um dos dois arquivos do cadastro sem integrar a correção pode deixar o 403 sem solução. Manter os arquivos de dependências pode deixar os bumps pendentes. No `config/kit.php`, preservar o conteúdo do dono inclui mudar somente o marcador para `0.52.1`, sem substituir suas configurações.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono conforme `## Quando parar`.

### 1. Aplicar os arquivos entregues pelo comando

Condição: as seis conferências do pré-voo passaram, e a decisão de conflito foi respondida ou registrada com o default seguro.

Ação:

```bash
php artisan kit:update --from=0.52.0 --tag=v0.52.1 --all --keep-remote --no-interaction
```

Evidência: os arquivos aparecem como `aplicado:`, a saída contém `config/kit.php: versão marcada como 0.52.1` e o branch criado é `kit-update/v0.52.1`. As tags ficam disponíveis para resolver conflitos e tratar as dependências.

Se o comando recusar a árvore, pedir nova execução ou anunciar outra versão, pare. Não faça commit para tentar uma segunda rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: existem arquivos em conflito. Sem conflitos, siga para o passo 3.

Ação, arquivo a arquivo:

- **Manter a versão do dono:** restaure com `git checkout HEAD -- caminho/do/arquivo`.
- **Receber a versão do kit:** mantenha o que o passo 1 aplicou.
- **Receber a versão do kit com as mudanças do dono:** consulte o diff guardado no pré-voo e reaplique somente as mudanças do dono com a ferramenta de edição. Se a integração não for clara, pare e mostre o ponto ao dono.

Quando preservar o `config/kit.php`, troque somente `'version' => '0.52.0',` por `'version' => '0.52.1',`.

Revise e adicione ao índice cada arquivo integrado:

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
```

Inclua o marcador no índice depois de conferir que as demais configurações foram preservadas:

```bash
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

O conjunto de dependências será tratado no passo 4, conforme a mesma decisão.

Evidência: arquivos mantidos integralmente têm `git diff HEAD -- caminho/do/arquivo` vazio. No `config/kit.php` preservado, a única diferença é a versão. Arquivos integrados contêm a correção aceita e as customizações do dono, com o resultado revisado no índice. Registre qualquer correção que ficou pendente.

### 3. Conferir o branch, o marcador e os arquivos novos

Condição: aplicação e resolução dos conflitos de código concluídas.

Ação:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git ls-files --error-unmatch tests/Kit/RoteiroDeAtualizacaoTest.php tests/Kit/CadastroNoPainelDoPapelTest.php tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php
```

Evidência: versão `0.52.1`, branch `kit-update/v0.52.1` e os três caminhos novos impressos pelo `git ls-files`. O índice corresponde ao resumo do dry-run, consideradas as escolhas de conflito. Os arquivos que o dono manteve aparecem no relatório como exceções.

A ausência de arquivo novo só pode corresponder a uma escolha explícita de conflito, que deixa a entrega incompleta e deve ser registrada. Ausência sem essa explicação exige parada.

### 4. Trazer as versões das dependências e instalar

Condição: os diffs de dependências do pré-voo foram conferidos, e qualquer conflito nesse conjunto já tem resposta ou default registrado.

Ação:

Se os quatro arquivos locais eram iguais aos da origem, traga os três arquivos alterados pela release:

```bash
git checkout kit-v0.52.1 -- composer.lock package.json package-lock.json
```

O `composer.json` não mudou entre as tags e não precisa ser substituído.

Se o conjunto tinha customizações, siga a decisão:

- **Manter a versão do dono ou sem resposta:** preserve os quatro arquivos locais e registre os bumps que ficaram pendentes.
- **Receber a versão do kit:** substitua o conjunto somente quando essa substituição tiver sido aceita expressamente na decisão.
- **Receber a versão do kit com mudanças reaplicadas:** integre os bumps preservando as dependências e scripts próprios. Se isso exigir resolver novamente dependências ou alterar restrições além dos bumps aceitos, pare e apresente o conflito ao dono; não improvise uma atualização geral.

Revise o resultado:

```bash
git diff HEAD -- composer.json composer.lock package.json package-lock.json
```

Depois, execute:

```bash
composer install
npm install
```

Confira novamente e adicione somente os arquivos de dependências cujo resultado foi revisado:

```bash
git diff HEAD -- composer.json composer.lock package.json package-lock.json
git add -- composer.json composer.lock package.json package-lock.json
git diff --cached --stat -- composer.json composer.lock package.json package-lock.json
```

Evidência: os dois installs terminam com código de saída zero, sem erro de plataforma, lock incompatível ou dependência ausente. No caminho sem customizações, os três arquivos recebidos continuam iguais aos da tag:

```bash
git diff kit-v0.52.1 -- composer.lock package.json package-lock.json
```

Esperado nesse caminho: saída vazia. Com integração aprovada, o diff deve conter somente as customizações preservadas e os ajustes necessários já revisados. Qualquer alteração inesperada provocada pelos installs exige parada.

Com o default de manter o conjunto, registre que os installs usaram os locks do dono e que isso não comprova a instalação dos bumps da release.

### 5. Atualizar os assets e limpar os caches

Condição: os installs terminaram sem erro.

Ação:

```bash
npm run build
php artisan filament:assets
php artisan optimize:clear
```

Evidência: os três comandos terminam sem erro. Confira `git status --short` e revise eventuais arquivos gerados antes de adicioná-los ao índice. Não inclua artefatos inesperados.

Não há migration, semeadura de jornadas ou geração de permissões para executar nesta atualização.

### 6. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos e dependências foram conferidos e os caches foram limpos.

Ação, no Git Bash:

```bash
php artisan config:clear
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=sqlite:///:memory: php artisan test --testsuite=Kit,Tenancy --parallel --compact
```

Ação equivalente no PowerShell, preservando os valores anteriores das variáveis da sessão:

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

O `DB_URL` também precisa apontar para o banco em memória. Deixá-lo vazio pode permitir que o valor do `.env` decida o banco usado pela suíte.

Evidência: código de saída zero, zero erro, zero falha e **no máximo 942 pulados** fora da árvore do kit. Os pulados permitidos são os casos que leem arquivos exclusivos do repositório do kit. Se houver mais, liste os casos adicionais e pare sem modificar os testes.

Depois da suíte:

```bash
git status --porcelain app/Policies
```

Os testes semeiam permissões no banco descartável e podem gerar policies no disco para Resources do dono. Registre cada caminho com `??` e remova esses arquivos não rastreados:

```bash
git clean -f -- app/Policies
git status --porcelain app/Policies
```

Evidência: nenhum arquivo não rastreado gerado pela suíte permanece em `app/Policies`. Os arquivos rastreados, inclusive os recebidos pelo update, continuam preservados. Alteração em policy rastreada exige parada e análise.

Depois dessa validação, peça ao dono que rode a suíte completa com `php artisan test --compact` em ambiente de testes com banco descartável.

### 7. Conferir os dados e a preservação do projeto

Condição: a suíte terminou e as variáveis da sessão PowerShell foram restauradas, quando aplicável.

Ação:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
php artisan migrate:status
php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "configuracoes" => md5(json_encode(app(App\Settings\ConfiguracoesDoKit::class)->toArray()))]);'
git diff HEAD -- .env config/logging.php
git status --short
git diff --cached --stat
```

Evidência: `kit.version` responde `0.52.1`; a multi-organização tem o mesmo valor do pré-voo; as migrations têm o mesmo estado, sem pendências novas; a impressão dos usuários e das Configurações é exatamente igual à inicial; o diff de `.env` e `config/logging.php` é vazio.

Confira também cada arquivo que ficou com a versão do dono e cada integração aprovada. Qualquer diferença de dados ou customização não explicada exige parada.

### 8. Remover o remote e as tags temporários

Condição: ao terminar ou ao parar depois que o dry-run tiver criado o remote e as tags. Este passo não autoriza apagar nomes que já existiam no pré-voo.

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

Se uma tentativa anterior já removeu algum desses nomes, confira o estado e remova somente o que ainda existir.

Evidência:

```bash
git remote
git tag -l "kit-*"
```

Nenhum remote `kit` e nenhuma tag `kit-*`. O branch `kit-update/v0.52.1` permanece com as mudanças para revisão, sem commit.

## Como conferir

O estado esperado é o mesmo dos cenários 3 e 4 de atualização: versão e arquivos conferidos, com ou sem multi-organização, preservando as customizações e os dados do dono.

| O que | Esperado |
|---|---|
| Versão do kit | `0.52.1`, depois de limpar o cache de configuração |
| Arquivo novo que prova a entrega | `tests/Kit/RoteiroDeAtualizacaoTest.php` presente e rastreado |
| Testes novos do cadastro | `tests/Kit/CadastroNoPainelDoPapelTest.php` e `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php` presentes e rastreados |
| Correção do cadastro | Os dois arquivos do cadastro receberam a correção ou têm a limitação da decisão registrada |
| Multi-organização | O mesmo `SIM` ou `NAO` do pré-voo |
| Migrations | Nenhuma nova nesta release; estado igual ao pré-voo |
| Jornadas e permissões | Nenhuma semeadura ou geração executada no banco do dono |
| Dependências | Installs concluídos; bumps incorporados ou pendências da decisão explicitadas |
| Suíte Kit,Tenancy | Zero erro, zero falha, no máximo 942 pulados |
| Policies geradas pelos testes | Não rastreadas removidas e listadas no relatório; rastreadas preservadas |
| Arquivos do dono | Mantidos ou integrados conforme a decisão, sem mudanças inesperadas |
| `.env` | Intacto |
| Canais de log | `config/logging.php` intacto, incluindo os canais próprios do projeto |
| Dados do banco | Impressão final idêntica à inicial: mesmos usuários e mesmos hashes, mesmas Configurações |
| Branch e índice | `kit-update/v0.52.1`, mudanças revisáveis e sem commit |
| Remote e tags temporários | Remote `kit` e tags `kit-*` removidos |

Uma escolha de manter código antigo ou dependências antigas deve aparecer como entrega parcial. O marcador `0.52.1` sozinho não permite declarar que todas as correções e bumps chegaram.

## Quando parar

Pare sem tentar contornar e informe ao dono a condição encontrada, a saída relevante e o que falta para continuar:

- **Roteiro indisponível ou cópias divergentes sem marca de revisão:** diga qual leitura falhou ou qual divergência encontrou.
- **Árvore suja no início:** liste os caminhos e peça que o dono resolva o trabalho em andamento.
- **Versão instalada diferente de `0.52.0`:** informe o valor encontrado e a origem exigida.
- **Nomes reservados já existentes:** informe o remote, as tags ou o branch encontrados; não os remova.
- **Banco inacessível ou migrations pendentes:** mostre o erro ou os nomes pendentes; esta release não exige executar migrations.
- **Tag destino indisponível:** informe que a atualização depende da publicação de `v0.52.1`.
- **Dry-run ou dependências diferentes do previsto:** mostre os arquivos divergentes. Diff do `composer.lock` é esperado; pacote novo ou mudança no `composer.json` entre as tags não é.
- **Arquivo novo esperado ausente na tag:** informe o caminho; não substitua a tag por outra referência.
- **Aplicação incompleta, árvore recusada ou pedido de segunda execução:** informe que o projeto pode estar entre versões e que nenhuma nova rodada ou commit foi feito.
- **Integração de conflito inviável:** mostre o trecho e preserve a decisão segura, sem inventar uma solução.
- **Install ou build com erro, lock incompatível ou alteração inesperada de dependências:** informe o erro e os diffs; não execute atualização geral para resolver.
- **Branch, marcador ou arquivos fora do esperado:** informe os valores e caminhos encontrados.
- **Suíte com erro, falha ou mais de 942 pulados:** entregue a saída e os casos envolvidos, sem alterar os testes.
- **Policy rastreada alterada pela suíte:** informe o caminho e o diff antes de qualquer restauração.
- **Impressão dos dados diferente, mudança na multi-organização, no `.env` ou nos canais de log:** informe exatamente qual conferência divergiu.
- **Um passo exigiria ação proibida em `## Cuidados`:** informe a necessidade encontrada e aguarde orientação.

Ao parar depois da criação dos nomes temporários, execute ainda o passo 8 para remover somente o remote e as tags criados por este roteiro. Preserve o branch e as mudanças para revisão.

Este roteiro não promete funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida, pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- versões de partida e chegada, com a saída de `config('kit.version')`;
- branch atual e confirmação de que nada foi commitado nem integrado por merge;
- arquivos aplicados, arquivos novos conferidos e integrações realizadas;
- cada decisão de conflito, com a pergunta, a resposta ou “sem resposta” e o default aplicado; sem conflitos, registre isso;
- arquivos mantidos na versão do dono e o que deixou de chegar em cada um, incluindo a situação da correção do cadastro;
- tratamento dos manifests e locks, resultados de `composer install`, `npm install` e build, com os bumps incorporados ou pendentes;
- confirmação de que não havia migration nova nem necessidade de semear jornadas ou gerar permissões;
- saída da suíte Kit,Tenancy, quantidade efetiva de pulados e pedido para o dono rodar a suíte completa em ambiente de testes;
- cada policy não rastreada removida depois da suíte;
- impressões dos dados antes e depois, resultado da comparação e estado da multi-organização;
- confirmação de preservação do `.env`, dos canais de log e das customizações;
- confirmação de remoção do remote `kit` e das tags `kit-*`;
- se houve parada: passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar: `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Lembre que isso não reverte o banco nem reinstala as versões anteriores das dependências.

<!-- roteiro-de-atualizacao:fim -->
