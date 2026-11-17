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
# Roteiro de atualização — v0.53.1 → v0.53.2

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.53.1

Destino: v0.53.2

Data: 2026-11-17

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.53.2/wikis/atualizacoes/v0.53.2.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.53.1 para a v0.53.2 seguindo este roteiro”. Ele pode vir com o link da Identificação ou sem ele; sem o link, monte-o pelo padrão com a versão destino `v0.53.2` e confira que é o mesmo endereço da Identificação. O agente segue os passos abaixo usando o `kit:update` para aplicar os arquivos.

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

   Esperado: `0.53.1`. Outro valor encerra este roteiro sem aplicar arquivos: `0.53.2` indica marcador já no destino; uma versão anterior exige as atualizações intermediárias, em ordem; uma versão posterior exige outro roteiro. Informe ao dono o valor encontrado.

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
   git branch --list kit-update/v0.53.2
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.53.2`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à correção.**

   ```bash
   php artisan kit:update --from=0.53.1 --tag=v0.53.2 --dry-run --keep-remote --no-interaction
   git diff --stat kit-v0.53.1 kit-v0.53.2 -- composer.json composer.lock package.json package-lock.json
   git diff --name-status kit-v0.53.1 kit-v0.53.2 -- database/migrations database/settings resources public
   git diff --name-status kit-v0.53.1 kit-v0.53.2 -- app/Models/Role.php app/Models/Convite.php app/Filament/Admin/Resources/Roles tests/Pest.php tests/Kit tests/Tenancy
   ```

   Esperado: resumo entre `kit-v0.53.1` e `kit-v0.53.2`, sem tocar arquivos; diff de dependências vazio; nenhum arquivo de migration ou asset novo. Confira a entrega da exclusão de papéis nos caminhos relacionados em **O que muda nesta versão**. O dry-run mantém o remote `kit` e as tags para as comparações seguintes.

   Se a tag ainda não estiver disponível, pare e informe ao dono. Não substitua a tag pela `main`. Dependência alterada, migration ou asset novo, ou ausência de parte da correção exige parada com o diff encontrado.

6. **As edições do dono foram identificadas.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.53.1 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.53.2 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos; repita sem `--name-only` para descrever a edição ao dono. Confira especialmente os dois models, o Resource e a página de edição de papéis, `tests/Pest.php`, os testes de convites e `config/kit.php`. Procure também testes próprios que chamam `$papel->delete()` para simular um convite cujo papel desapareceu; eles precisam do cuidado descrito abaixo.

## O que muda nesta versão

A v0.53.2 corrige a exclusão de papéis que tinham convites e terminava em erro **500**. Um convite **Pendente** agora impede a exclusão com o aviso **Papel com convite pendente**. Nada é apagado, inclusive permissões e vínculos com usuários. A regra vale na ação da linha, no cabeçalho da edição e na exclusão em massa: se um papel da seleção estiver bloqueado, nenhum deles é excluído.

O aviso lista até cinco e-mails pendentes por papel, seguidos de “e mais N” quando necessário. Quem pode excluir papéis mas não possui `ViewAny:Convite` vê somente a quantidade, sem os e-mails.

Convites **Aceitos, Recusados ou Expirados** não bloqueiam: são excluídos junto com o papel, um a um, com cada remoção registrada em `/infra/audits`. A regra em `Role::delete()` é atômica: falha em qualquer etapa, inclusive convite criado ou reenviado durante a operação, preserva o papel, os convites, as permissões e os usuários. `Convite::pendentes()` passa a concentrar a regra de pendência usada pela caixa de entrada, pela busca do token e pela exclusão.

Confira estes arquivos na entrega:

- `app/Models/Role.php` e `app/Models/Convite.php`;
- `app/Filament/Admin/Resources/Roles/RoleResource.php` e `app/Filament/Admin/Resources/Roles/Pages/EditRole.php`;
- `tests/Pest.php`, com o helper `apagarPapelPelaQuery()`;
- `tests/Kit/ExclusaoDePapelComConviteTest.php`, `tests/Kit/ConviteTest.php`, `tests/Kit/CadastroSocialPorConviteTest.php` e `tests/Kit/ConviteUsuarioExistenteTest.php`;
- `tests/Tenancy/ConviteTenancyTest.php` e `tests/Tenancy/ConviteUsuarioExistenteTest.php`;
- `config/kit.php`, com o marcador `0.53.2`.

Não há migration, asset novo, dependência nova nem default mudado. Basta aplicar os arquivos e conferir o resultado; não há instalação de pacotes, build, semeadura ou geração de permissões a executar.

## Cuidados

- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker. Esta correção não pede alteração de esquema.
- **Preserve a multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **A recusa do model é comportamento esperado.** Com convite pendente, `$papel->delete()` agora devolve `false`. Não retire a proteção para fazer um teste antigo passar. Se o teste simula um papel desaparecido, use `apagarPapelPelaQuery('nome_do_papel')` ou um `DELETE` pela query, com `PRAGMA defer_foreign_keys = ON` antes, na transação SQLite do teste. O helper faz só o DELETE; o teste precisa ativar o PRAGMA. Isso serve exclusivamente para montar esse estado inválido no banco descartável, nunca para excluir papéis na aplicação ou no banco do dono.
- **Os arquivos da correção trabalham juntos.** Manter um model antigo pode perder a proteção ou a regra única de pendência. Manter o Resource ou a página antigos pode deixar uma ação sem o aviso. Manter `tests/Pest.php` antigo deixa os testes recebidos sem o helper. Registre cada limitação como entrega parcial.
- **A suíte pode recriar tabelas no banco que receber.** Force as três variáveis de banco do passo 4, depois de limpar o cache de configuração. Faça as exclusões manuais somente em ambiente de testes com dados descartáveis.
- **O `--all` substitui arquivos existentes.** Resolva a decisão de conflito antes e restaure ou integre as edições do dono antes de validar. O marcador `0.53.2` sozinho não prova que toda a correção chegou.
- **Preserve `.env`, canais de log e customizações.** Esta versão não exige editar o `.env` ou o `config/logging.php`. Ao preservar `config/kit.php`, altere somente o marcador de versão.
- **As mudanças ficam para revisão.** Use `git status` e `git diff --cached`; não commite, não faça merge nem apague o branch por conta própria. Desfazer exige pedido do dono: `git reset --hard HEAD` desfaz arquivos e índice; avalie arquivos não rastreados um a um. Isso não reverte operações feitas no banco.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo, com os diffs, antes do passo 1. Sem conflitos, registre “nenhum conflito” e siga. Nenhuma outra decisão do dono nesta versão.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos têm edição sua e a v0.53.2 também os altera; manter models, Resource, página de edição ou `tests/Pest.php` antigos deixa a correção parcial ou os testes sem helper: para cada arquivo, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas após revisar os diffs, preservando a proteção de `Role::delete()`, a regra de `Convite::pendentes()`, os avisos nas três ações e os helpers dos testes. Em `config/kit.php`, preservar suas configurações e alterar só a versão para `0.53.2`.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono.

### 1. Aplicar os arquivos

Condição: as seis conferências do pré-voo passaram, e cada conflito tem resposta ou default seguro registrado.

Ação:

```bash
php artisan kit:update --from=0.53.1 --tag=v0.53.2 --all --keep-remote --no-interaction
```

Evidência: arquivos anunciados como `aplicado:`, saída `config/kit.php: versão marcada como 0.53.2` e branch `kit-update/v0.53.2`. Se o comando recusar a árvore, pedir segunda execução ou anunciar outra versão, pare; não faça commit para tentar outra rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: há conflitos; sem eles, siga para o passo 3.

Ação, arquivo a arquivo: para manter a versão do dono, use `git checkout HEAD -- caminho/do/arquivo`; para receber a do kit, mantenha o aplicado; para integrar, reaplique somente as mudanças do dono conforme o diff guardado. Integração incerta exige parada. No `config/kit.php` preservado, troque apenas `'version' => '0.53.1',` por `'version' => '0.53.2',`.

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

Evidência: arquivos mantidos têm diff vazio contra `HEAD`, integrações contêm ambas as mudanças revisadas e o config preserva as demais configurações. Registre cada parte da correção que não chegou. Se `tests/Pest.php` foi mantido sem o helper, registre que os testes recebidos podem falhar por essa escolha.

### 3. Conferir marcador e arquivos

Condição: aplicação e conflitos concluídos.

Ação:

```bash
php artisan optimize:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git diff HEAD -- app/Models/Role.php app/Models/Convite.php app/Filament/Admin/Resources/Roles/RoleResource.php app/Filament/Admin/Resources/Roles/Pages/EditRole.php tests/Pest.php tests/Kit tests/Tenancy config/kit.php
```

Evidência: versão `0.53.2`, branch `kit-update/v0.53.2` e arquivos da correção presentes, com o conteúdo do destino ou a integração aprovada. Compare cada caminho listado em **O que muda nesta versão** com `git diff kit-v0.53.2 -- caminho/do/arquivo`; diferenças devem corresponder somente às decisões registradas. O índice corresponde ao dry-run. Não execute migrations nem publicação de assets nesta atualização.

### 4. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos conferidos, incluindo o helper e os ajustes dos testes recebidos. Ajustes em testes próprios de papel desaparecido precisam preservar o cenário e seguir o cuidado do PRAGMA, sem alterar a regra de produção.

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

Evidência: código zero, zero erro, zero falha e no máximo **1161 pulados** fora da árvore do kit, o teto recebido da origem. Os pulados permitidos leem arquivos exclusivos do repositório do kit. Registre a quantidade efetiva; aumento exige listar as causas e parar, sem alterar os testes para passar.

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

Evidência: `0.53.2`, mesma multi-organização, mesmo estado das migrations e impressão dos dados idêntica. Diff vazio de `.env` e `config/logging.php`; customizações preservadas conforme as decisões. Nenhuma escrita no banco do dono foi necessária. As exclusões de conferência abaixo acontecem em outro banco, descartável.

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

Se uma tentativa anterior já removeu algum nome, confira e remova somente o que ainda existir. Evidência: `git remote` não lista `kit` e `git tag -l "kit-*"` tem saída vazia. O branch `kit-update/v0.53.2` permanece, com as mudanças para revisão e sem commit.

## Como conferir

O estado esperado é o dos cenários 3 e 4 de atualização: com ou sem multi-organização, versão e arquivos conferidos, dados e customizações preservados.

| O que | Esperado |
|---|---|
| Marcador | `0.53.2` depois de limpar o cache de configuração |
| Arquivos da correção | Dois models, Resource, EditRole, helper e testes listados conferidos contra o destino ou a integração aprovada |
| Migrations, assets e dependências | Nenhum novo; nenhuma execução necessária |
| Defaults e multi-organização | Nenhum default mudado; mesmo `SIM` ou `NAO` do pré-voo |
| Suíte Kit,Tenancy | Zero erro, zero falha, até 1161 pulados fora da árvore do kit |
| Policies | Não rastreadas geradas pela suíte removidas e listadas; rastreadas preservadas |
| Arquivos do dono | Customizações preservadas; `.env` e canais em `config/logging.php` intactos |
| Dados do dono | Impressão final idêntica à inicial |
| Revisão | Branch `kit-update/v0.53.2`, índice revisável, sem commit; remote e tags temporários removidos |

Faça a conferência manual **em ambiente de testes com banco descartável**, usando papéis e convites de teste. Não use os papéis do dono para demonstrar exclusão:

1. Com um papel que tenha convite **Pendente**, confirme a exclusão pela ação da linha. Deve aparecer **Papel com convite pendente**, sem erro 500, mantendo papel, convites, permissões e usuários. Repita pelo cabeçalho da edição.
2. Selecione esse papel junto de outro sem pendências e confirme a exclusão em massa. Deve aparecer o aviso e **nenhum** papel da seleção deve sair.
3. Com `ViewAny:Convite`, confira os e-mails pendentes, até cinco por papel e “e mais N” para os demais. Com usuário autorizado a excluir papéis mas sem essa permissão, confira somente a quantidade, sem e-mail.
4. Use outro papel que tenha somente convites **Aceitos, Recusados ou Expirados**. Confirme a exclusão: o papel sai, os convites saem e cada remoção de convite consta em `/infra/audits`. Repita nas ações da tela que foram integradas pelo projeto.

Registre o resultado e os dados de teste usados. Se não houver ambiente ou permissão para conferir a tela e as auditorias, registre a conferência como não executada e o motivo; não declare validação manual concluída. Arquivo mantido antigo é entrega parcial, mesmo com marcador `0.53.2`.

## Quando parar

Pare e entregue ao dono a condição, a saída relevante e o que falta para continuar:

- Roteiro indisponível, cópias divergentes sem marca de revisão ou dúvida de interpretação.
- Árvore suja no início, versão instalada diferente de `0.53.1`, banco inacessível, backup não confirmado ou migrations pendentes do projeto: liste os valores e caminhos encontrados.
- Remote, tags ou branch reservados já existentes: informe os nomes, sem removê-los.
- Tag `v0.53.2` indisponível ou dry-run divergente: mostre os diffs, principalmente dependências, migrations, assets ou parte da correção ausente.
- Comando recusado, pedido de segunda rodada, aplicação incompleta, integração incerta ou marcador, branch e arquivos fora do previsto: descreva o estado parcial, sem commit ou nova rodada.
- Suíte com erro, falha ou mais de 1161 pulados: entregue a saída e os casos envolvidos. Se um teste próprio usou `$papel->delete()` para simular desaparecimento, explique o retorno `false` e a preparação pela query; não enfraqueça a regra. Se faltou o helper por decisão de conflito, registre essa causa.
- Policy rastreada alterada, impressão de dados diferente, multi-organização alterada ou mudança inesperada em `.env`, canais de log ou customizações: mostre a diferença antes de restaurar qualquer coisa.
- Conferência manual com 500, exclusão de papel bloqueado, exclusão parcial da seleção, exposição de e-mail sem permissão ou remoção sem auditoria: registre a ação, o usuário de teste e o resultado.
- Um passo exigiria ação proibida em **Cuidados**: informe a necessidade e aguarde orientação.

Ao parar depois de criar os nomes temporários, execute ainda o passo 6 para remover somente o remote e as tags deste roteiro. Preserve o branch e as mudanças para revisão. Este roteiro não promete funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida, pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- versões de partida e chegada, saída de `config('kit.version')`, branch e confirmação de que nada foi commitado nem integrado por merge;
- arquivos aplicados e conferidos, integrações e ajustes em testes próprios de papel desaparecido;
- cada decisão de conflito, pergunta e resposta ou “sem resposta”, default aplicado e limitações por arquivo; sem conflitos, registre isso;
- confirmação de que não houve migration, asset novo, default mudado, instalação, build, semeadura ou geração de permissões;
- saída literal da suíte Kit,Tenancy, contagem efetiva de pulados e pedido para rodar a suíte completa em ambiente de testes;
- policies não rastreadas removidas, impressões dos dados antes e depois e resultado da comparação;
- preservação de `.env`, canais de log, customizações e estado da multi-organização;
- conferências manuais do aviso, das três ações, da seleção em massa, da privacidade dos e-mails e das remoções auditadas, ou o motivo de não execução;
- remoção do remote `kit` e das tags `kit-*`;
- em caso de parada, passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar com `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Isso não reverte operações no banco; esta atualização não exigiu nenhuma no banco do dono.
<!-- roteiro-de-atualizacao:fim -->
