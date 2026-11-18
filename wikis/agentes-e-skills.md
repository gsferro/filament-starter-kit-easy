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
# Roteiro de atualização — v0.53.2 → v0.54.0

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.53.2

Destino: v0.54.0

Data: 2026-11-18

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.54.0/wikis/atualizacoes/v0.54.0.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.53.2 para a v0.54.0 seguindo este roteiro”. Ele pode vir com o link da Identificação ou sem ele; sem o link, monte-o pelo padrão com a versão destino `v0.54.0` e confira que é o mesmo endereço da Identificação. O agente segue os passos abaixo usando o `kit:update` para aplicar os arquivos.

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

   Esperado: `0.53.2`. Outro valor encerra este roteiro sem aplicar arquivos: `0.54.0` indica marcador já no destino; uma versão anterior exige as atualizações intermediárias, em ordem; uma versão posterior exige outro roteiro. Informe ao dono o valor encontrado.

3. **O banco está acessível e a impressão dos dados foi guardada.**

   Confirme com o dono que há backup recuperável antes de aplicar os arquivos.

   ```bash
   php artisan migrate:status
   php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
   php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => array_map("md5", App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "configuracoes" => md5(json_encode(app(App\Settings\ConfiguracoesDoKit::class)->toArray()))]);'
   ```

   Esperado: consulta sem erro e nenhuma migration `Pending`. Havendo pendência do projeto, informe os nomes e pare: o migrate executaria essas pendências junto com as três migrations desta versão. Anote a multi-organização, `SIM` ou `NAO`, e guarde integralmente a última saída: um hash por usuário, um por organização e um das Configurações da aplicação. Essa **impressão dos dados** deve ser idêntica ao final, excluindo na consulta final somente os campos acrescentados nesta versão: `users.aparencia`, `tenants.aparencia_por_usuario` e os três settings novos. Confira os valores novos separadamente, antes de qualquer escolha manual.

4. **Os nomes reservados pelo comando estão livres.**

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.54.0
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.54.0`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à release.**

   ```bash
   php artisan kit:update --from=0.53.2 --tag=v0.54.0 --dry-run --keep-remote --no-interaction
   git diff --stat kit-v0.53.2 kit-v0.54.0 -- composer.json composer.lock package.json package-lock.json
   git diff --name-status kit-v0.53.2 kit-v0.54.0 -- database/migrations database/settings resources public
   git diff --name-status kit-v0.53.2 kit-v0.54.0 -- app/Providers app/Models/User.php app/Models/Tenant.php app/Filament/Admin app/Settings app/Livewire app/Support config/kit.php .env.example tests/Kit tests/Tenancy
   ```

   Esperado: resumo entre `kit-v0.53.2` e `kit-v0.54.0`, sem tocar arquivos; diff de dependências vazio; exatamente as três migrations listadas em **O que muda nesta versão**, o componente e sua view novos, sem asset publicado novo. Confira todos os arquivos da entrega relacionados abaixo. O dry-run mantém o remote `kit` e as tags para as comparações seguintes.

   Se a tag ainda não estiver disponível, pare e informe ao dono. Não substitua a tag pela `main`. Dependência alterada, migration além das três previstas, asset publicado novo ou ausência de parte da entrega exige parada com o diff encontrado.

6. **As edições do dono foram identificadas.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.53.2 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.54.0 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos; repita sem `--name-only` para descrever a edição ao dono. Confira especialmente os três providers de painel, o render hook do `KitServiceProvider`, a página e a classe de settings, os models `User` e `Tenant`, o `TenantForm`, `config/kit.php` e `.env.example`. Os pontos de integração estão detalhados abaixo.

## O que muda nesta versão

A v0.54.0 traz **preferências de aparência por usuário**. O Meu perfil dos três painéis ganha a seção **Aparência**, com densidade do layout, tamanho da fonte, cor primária da lista do kit e modo do menu lateral ao recolher. Cada campo começa em **Padrão**; a escolha é uma só por pessoa e vale nos três painéis onde estiver liberada.

A chave da instalação `aparencia_por_usuario` nasce **LIGADA**: logo após atualizar, todo usuário vê a seção **Aparência** no Meu perfil. Nada muda na tela de ninguém até escolher, além dessa seção nova. A fonte `normal` e o menu `icones` reproduzem o comportamento anterior; a densidade e a cor já configuradas continuam sendo o padrão. A aba **Aplicação** de *Configurações da aplicação* ganha **Aparência dos painéis**, reunindo a densidade, os dois padrões novos e **Cada pessoa pode escolher a própria aparência**.

Para não oferecer a escolha, desligue esse interruptor em *Configurações da aplicação* → **Aplicação** → **Aparência dos painéis**, ou configure `KIT_APARENCIA_POR_USUARIO=false` no `.env` **antes do migrate**, limpando o cache de configuração. As chaves `KIT_TAMANHO_DA_FONTE`, `KIT_MODO_DO_MENU` e `KIT_APARENCIA_POR_USUARIO` só semeiam os valores: depois da migration, governa a tela. Desligar ignora as escolhas, sem apagá-las; religar volta a aplicá-las.

Com organizações (tenancy), **Liberar a aparência pessoal**, no cadastro da organização, nasce **desligado**, inclusive nas organizações existentes. Só aparece com a chave da instalação ligada. Dentro de `/app/{organização}`, a escolha pessoal exige as duas chaves ligadas; quando liberada, a cor pessoal vence a da organização. Sem liberação, continuam a cor da organização e os padrões da instalação. O Meu perfil do `/app`, fora de uma organização no endereço, segue somente a chave da instalação.

Confira estes arquivos que o `kit:update` traz e os pontos de conflito em projeto que mexeu à mão:

- `app/Providers/Filament/AdminPanelProvider.php`, `app/Providers/Filament/AppPanelProvider.php` e `app/Providers/Filament/InfraPanelProvider.php`: menu lateral com `sidebarCollapsibleOnDesktop` e `sidebarFullyCollapsibleOnDesktop`, `sidebarWidth` pela densidade pessoal, inclusão do componente em `myProfileComponents` sem perder os componentes próprios e `bootUsing` para a cor pessoal; no `/app`, preserve a cor da organização e aplique a pessoal depois;
- `app/Providers/KitServiceProvider.php`: render hook de densidade e fonte, lendo `AparenciaDoUsuario`;
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`: seção nova, densidade movida e campos de fonte, menu e liberação;
- `app/Settings/ConfiguracoesDoKit.php`: propriedades e mapa de configuração para `tamanho_da_fonte`, `modo_do_menu` e `aparencia_por_usuario`;
- `app/Models/User.php`: cast de `aparencia` para array e campo oculto, mantendo-o fora do `$fillable`; `app/Models/Tenant.php`: cast booleano e `$fillable` de `aparencia_por_usuario`;
- `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`: interruptor da organização;
- `config/kit.php`: marcador `0.54.0`, imports de `TamanhoDaFonte` e `ModoDoMenu`, as três chaves novas e suas sementes do ambiente;
- `.env.example`: exemplos comentados das três variáveis; não substitui o `.env` do dono;
- novos `app/Livewire/PreferenciasDeAparencia.php`, `resources/views/livewire/preferencias-de-aparencia.blade.php`, `app/Support/AparenciaDoUsuario.php`, `app/Support/TamanhoDaFonte.php` e `app/Support/ModoDoMenu.php`;
- `app/Console/Commands/KitUpdate.php`, com a entrega da view nova, e os ajustes em `tests/Kit/KitInfoTest.php`.

As três migrations são:

- `database/migrations/2026_11_18_100000_add_aparencia_to_users_table.php`: JSON anulável `users.aparencia`, nulo nas contas existentes;
- `database/migrations/2026_11_18_100001_add_aparencia_por_usuario_to_tenants_table.php`: booleano `tenants.aparencia_por_usuario`, falso;
- `database/settings/2026_11_18_100000_add_aparencia_por_usuario_to_kit_settings.php`: adiciona ao grupo `kit` os settings `tamanho_da_fonte` (`normal`), `modo_do_menu` (`icones`) e `aparencia_por_usuario` (`true`), salvo semente explícita do ambiente.

Não há dependência nova, instalação de pacotes, build, publicação de assets, semeadura ou geração de permissões a executar. Há alteração de esquema: os arquivos sozinhos não concluem a atualização.

## Cuidados

- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker. As três migrations só acrescentam campos; confirme backup recuperável.
- **Preserve a multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **A liberação da instalação é ligada.** Informe ao dono antes do migrate; não confunda visual preservado com seção escondida. Não escolha preferências pelos usuários nem ligue organizações do dono para demonstrar a feature.
- **Os arquivos trabalham juntos.** Provider antigo pode deixar menu, largura, cor ou seção do perfil sem efeito; render hook antigo perde fonte e densidade pessoais. Settings, models e formulário antigos podem impedir persistência ou liberação. Ausência do componente, sua view ou helpers novos impede a entrega completa. Registre cada limitação.
- **A suíte pode recriar tabelas no banco que receber.** Force as três variáveis de banco do passo 5, depois de limpar o cache de configuração. Faça escolhas manuais somente em ambiente de testes com dados descartáveis.
- **O `--all` substitui arquivos existentes.** Resolva a decisão de conflito antes e restaure ou integre as edições do dono antes de validar. O marcador `0.54.0` sozinho não prova que toda a entrega chegou.
- **Preserve `.env`, canais de log e customizações.** Não copie `.env.example` sobre `.env`. A única edição opcional do ambiente é a semente explicitamente escolhida pelo dono antes do migrate; registre o diff. Preserve `config/logging.php`.
- **As mudanças ficam para revisão.** Use `git status` e `git diff --cached`; não commite, não faça merge nem apague o branch por conta própria. Desfazer exige pedido do dono: `git reset --hard HEAD` desfaz arquivos e índice; avalie arquivos não rastreados um a um. Isso não reverte as migrations nem os dados do banco.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo, com os diffs, antes do passo 1. Sem conflitos, registre “nenhum conflito” e siga. Apresente também a liberação da aparência pessoal, antes do migrate.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos têm edição sua e a v0.54.0 também os altera; manter providers, render hook, settings, models ou formulário antigos deixa parte da aparência sem efeito: para cada arquivo, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas após revisar os diffs, preservando componentes próprios do Meu perfil, menu, largura e cor customizados junto com a leitura de `AparenciaDoUsuario`. Em `config/kit.php`, preservar as configurações do dono e integrar o marcador e as três chaves novas com seus imports.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

### Liberação da aparência pessoal

Pergunta: Considerando que não há alteração de visual por padrão — nenhum para quem não escolhe nada: fonte `normal` e menu `icones` reproduzem o comportamento anterior —, mas a chave `aparencia_por_usuario` nasce ligada e todo usuário verá a seção Aparência no Meu perfil, deseja manter essa liberação ou iniciar com ela desligada?

Recomendação da release: manter ligada, com todas as escolhas em Padrão e todas as organizações desligadas. Para iniciar desligada, definir `KIT_APARENCIA_POR_USUARIO=false` antes do migrate e limpar o cache; depois da migration, desligar pela tela de Configurações da aplicação.

Sem resposta: mantém o default da release, ligada, sem editar o `.env`, sem escolher aparência e sem liberar organização nenhuma; registra a decisão e o default aplicado.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono.

### 1. Aplicar os arquivos

Condição: as seis conferências do pré-voo passaram, e cada conflito tem resposta ou default seguro registrado.

Ação:

```bash
php artisan kit:update --from=0.53.2 --tag=v0.54.0 --all --keep-remote --no-interaction
```

Evidência: arquivos anunciados como `aplicado:`, saída `config/kit.php: versão marcada como 0.54.0` e branch `kit-update/v0.54.0`. Se o comando recusar a árvore, pedir segunda execução ou anunciar outra versão, pare; não faça commit para tentar outra rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: há conflitos; sem eles, siga para o passo 3.

Ação, arquivo a arquivo: para manter a versão do dono, use `git checkout HEAD -- caminho/do/arquivo`; para receber a do kit, mantenha o aplicado; para integrar, reaplique somente as mudanças do dono conforme o diff guardado. Integração incerta exige parada. No `config/kit.php` preservado, troque `'version' => '0.53.2',` por `'version' => '0.54.0',` e integre os imports e as chaves `tamanho_da_fonte`, `modo_do_menu` e `aparencia_por_usuario` conforme o destino, preservando as configurações existentes.

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

Evidência: arquivos mantidos têm diff vazio contra `HEAD`, integrações contêm ambas as mudanças revisadas e o config preserva as demais configurações. Registre cada parte da aparência que não chegou; defaults divergentes devem estar explicados pela decisão do dono.

### 3. Conferir marcador e arquivos

Condição: aplicação e conflitos concluídos.

Ação:

```bash
php artisan optimize:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git diff HEAD -- app/Providers app/Settings app/Models/User.php app/Models/Tenant.php app/Filament/Admin app/Livewire/PreferenciasDeAparencia.php app/Support resources/views/livewire/preferencias-de-aparencia.blade.php database/migrations database/settings config/kit.php .env.example
```

Evidência: versão `0.54.0`, branch `kit-update/v0.54.0` e arquivos da aparência presentes, com o conteúdo do destino ou a integração aprovada. Compare cada caminho listado em **O que muda nesta versão** com `git diff kit-v0.54.0 -- caminho/do/arquivo`; diferenças devem corresponder somente às decisões registradas. O índice corresponde ao dry-run. Confira também os cinco arquivos novos de aplicação e as três migrations com `git ls-files --error-unmatch caminho/do/arquivo`. Ainda não abra os painéis antes do migrate. Não há publicação de assets nesta atualização.

### 4. Rodar as três migrations da versão

Condição: arquivos conferidos, backup confirmado, nenhuma pendência anterior e decisão sobre a liberação registrada.

Ação: se o dono escolheu iniciar desligada, defina `KIT_APARENCIA_POR_USUARIO=false` no `.env` agora. Sem essa escolha, preserve o ambiente. Rode:

```bash
php artisan config:clear
php artisan migrate --force
php artisan optimize:clear
php artisan migrate:status
php artisan tinker --execute 'echo json_encode(collect(app(App\Settings\ConfiguracoesDoKit::class)->toArray())->only(["tamanho_da_fonte", "modo_do_menu", "aparencia_por_usuario"])->all());'
php artisan tinker --execute 'echo App\Models\User::whereNotNull("aparencia")->count();'
php artisan tinker --execute 'echo App\Models\Tenant::where("aparencia_por_usuario", true)->count();'
```

O comando de atualização do banco é `php artisan migrate`; acima, `--force` permite executá-lo também em produção sem pergunta interativa.

Evidência: executadas exatamente `2026_11_18_100000_add_aparencia_to_users_table`, `2026_11_18_100001_add_aparencia_por_usuario_to_tenants_table` e `2026_11_18_100000_add_aparencia_por_usuario_to_kit_settings`; `migrate:status` sem `Pending`. Os settings respondem `normal`, `icones` e `true`, salvo sementes explícitas registradas; ambas as contagens respondem `0`. Erro ou migration adicional exige parada, sem rollback.

### 5. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos e migrations conferidos, caches limpos e valores novos verificados antes de escolhas manuais.

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

### 6. Conferir a preservação dos dados e do projeto

Condição: suíte concluída e variáveis PowerShell restauradas, quando aplicável.

Ação: repita a consulta da multi-organização do pré-voo e compare a impressão dos dados com a consulta abaixo, que exclui somente os campos novos:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
php artisan migrate:status
php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map(fn ($tenant) => md5($tenant->makeHidden("aparencia_por_usuario")->toJson()))->all(), "configuracoes" => md5(json_encode(collect(app(App\Settings\ConfiguracoesDoKit::class)->toArray())->except(["tamanho_da_fonte", "modo_do_menu", "aparencia_por_usuario"])->all()))]);'
git diff HEAD -- .env config/logging.php
git status --short
git diff --cached --stat
```

O model User oculta `aparencia` na serialização; por isso sua impressão continua comparável sem exclusão adicional. Os campos novos foram conferidos separadamente no passo 4.

Evidência: `0.54.0`, mesma multi-organização, migrations anteriores mais as três novas, nenhuma pendente e impressão dos dados idêntica. Diff vazio de `.env` e `config/logging.php`, salvo a semente opcional explicitamente escolhida; customizações preservadas conforme as decisões. Abra também o Meu perfil e execute as conferências de **Como conferir**, registrando o resultado antes de encerrar.

### 7. Remover o remote e as tags temporários

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

Se uma tentativa anterior já removeu algum nome, confira e remova somente o que ainda existir. Evidência: `git remote` não lista `kit` e `git tag -l "kit-*"` tem saída vazia. O branch `kit-update/v0.54.0` permanece, com as mudanças para revisão e sem commit.

## Como conferir

O estado esperado é o dos cenários 3 e 4 de atualização: com ou sem multi-organização, versão, arquivos e migrations conferidos, dados e customizações preservados.

| O que | Esperado |
|---|---|
| Marcador | `0.54.0` depois de limpar o cache de configuração |
| Arquivos da aparência | Providers, hook, settings, models, formulário, componente, view e helpers conferidos contra o destino ou a integração aprovada |
| Migrations | As três desta versão executadas; `php artisan migrate:status` sem pendências |
| Instalação | `aparencia_por_usuario=true`, ou a decisão explícita de iniciar desligada registrada |
| Defaults visuais | Fonte `normal`, menu `icones`, densidade e cor anteriores preservadas, salvo sementes explícitas |
| Organizações | Todas com o interruptor desligado; mesmo modo de tenancy do pré-voo |
| Meu perfil | Seção Aparência presente, quatro campos em Padrão, quando a instalação libera |
| Suíte Kit,Tenancy | Zero erro, zero falha, até 1161 pulados fora da árvore do kit |
| Testes do projeto | Pedido para rodar `php artisan test --compact` em banco descartável registrado |
| Policies | Não rastreadas geradas pela suíte removidas e listadas; rastreadas preservadas |
| Arquivos e dados do dono | Impressão anterior preservada, fora os campos novos; customizações e canais de log intactos |
| Revisão | Branch `kit-update/v0.54.0`, índice revisável, sem commit; remote e tags temporários removidos |

Abra **Meu perfil** em cada painel a que a conta tenha acesso: a seção **Aparência** deve aparecer com densidade, fonte, cor e menu em Padrão; o visual anterior deve continuar igual. Se o dono iniciou desligada, a seção deve estar ausente. Não salve escolhas no banco do dono.

Faça as demais conferências **em ambiente de testes com banco descartável**:

1. Escolha fonte, densidade, cor e menu numa conta de teste, salve e confira a aplicação nos três painéis autorizados. Outra conta mantém seu padrão. Voltar os quatro campos a Padrão restaura a aparência da instalação.
2. Desligue a chave da instalação na tela: a seção some e as escolhas deixam de valer; religue e confira que voltam, sem terem sido apagadas.
3. Com tenancy, confira primeiro a organização desligada: vale a cor dela e os padrões da instalação. Ligue **Liberar a aparência pessoal** somente nessa organização de teste: a escolha da pessoa passa a valer, inclusive a cor. Outra organização desligada continua no padrão. Com a instalação desligada, a chave da organização não libera sozinha e o interruptor não aparece no cadastro.

Registre os resultados e os dados de teste. Sem ambiente ou permissão para conferir as telas, registre como não executado e explique o motivo; não declare validação manual concluída. Arquivo antigo mantido é entrega parcial, mesmo com marcador `0.54.0`.

## Quando parar

Pare e entregue ao dono a condição, a saída relevante e o que falta para continuar:

- Roteiro indisponível, cópias divergentes sem marca de revisão ou dúvida de interpretação.
- Árvore suja no início, versão instalada diferente de `0.53.2`, banco inacessível, backup não confirmado ou migrations pendentes do projeto: liste os valores e caminhos encontrados.
- Remote, tags ou branch reservados já existentes: informe os nomes, sem removê-los.
- Tag `v0.54.0` indisponível ou dry-run divergente: mostre os diffs, principalmente dependências, migrations além das três previstas ou parte da entrega ausente.
- Comando recusado, pedido de segunda rodada, aplicação incompleta, integração incerta ou marcador, branch e arquivos fora do previsto: descreva o estado parcial, sem commit ou nova rodada.
- Migration com erro, migration adicional executada ou valores novos fora do default ou da semente registrada: entregue as saídas, sem tentar desfazer o banco.
- Suíte com erro, falha ou mais de 1161 pulados: entregue a saída e os casos envolvidos, sem alterar testes para passar.
- Policy rastreada alterada, impressão de dados diferente, multi-organização alterada ou mudança inesperada em `.env`, canais de log ou customizações: mostre a diferença antes de restaurar qualquer coisa.
- Seção ausente com a instalação ligada, aparência mudada sem escolha, liberação indevida numa organização desligada ou escolha pessoal aplicada a outra conta: registre conta de teste, contexto e resultado.
- Um passo exigiria ação proibida em **Cuidados**: informe a necessidade e aguarde orientação.

Ao parar depois de criar os nomes temporários, execute ainda o passo 7 para remover somente o remote e as tags deste roteiro. Preserve o branch e as mudanças para revisão. Este roteiro não promete funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida, pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- versões de partida e chegada, saída de `config('kit.version')`, branch e confirmação de que nada foi commitado nem integrado por merge;
- arquivos aplicados e conferidos, arquivos novos e integrações;
- cada decisão de conflito e de liberação, pergunta e resposta ou “sem resposta”, default aplicado e limitações por arquivo; sem conflitos, registre isso;
- saída do migrate com os três nomes e do `php artisan migrate:status`, mais os valores novos e as duas contagens conferidas;
- confirmação de que a instalação nasce ligada e mostra Aparência no Meu perfil, ou a decisão explícita de desligar, com a semente e seu diff quando usados; organizações continuam desligadas;
- confirmação de que não houve dependência nova, instalação, build, publicação de assets, semeadura ou geração de permissões;
- saída literal da suíte Kit,Tenancy, contagem efetiva de pulados e pedido para rodar os testes do projeto com `php artisan test --compact` em ambiente de testes com banco descartável;
- policies não rastreadas removidas, impressões dos dados antes e depois e resultado da comparação;
- preservação de `.env` (salvo a semente escolhida), canais de log, customizações e estado da multi-organização;
- conferências manuais do Meu perfil, dos padrões e das duas chaves com organizações, ou o motivo de não execução;
- remoção do remote `kit` e das tags `kit-*`;
- em caso de parada, passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar com `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Isso não reverte o banco: as colunas e os settings novos permanecem, com seus valores.
<!-- roteiro-de-atualizacao:fim -->
