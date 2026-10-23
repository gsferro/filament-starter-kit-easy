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
# Roteiro de atualização — v0.51.2 → v0.52.0

Este é o roteiro que um agente segue para levar um projeto derivado do starter kit da v0.51.2 para a
v0.52.0. Ele é autossuficiente: tudo o que você precisa para atualizar está aqui. Leia o roteiro
inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.51.2

Destino: v0.52.0

Data: 2026-10-23

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/wikis/atualizacoes/v0.52.0.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

O roteiro desta versão nasceu depois da tag v0.52.0, por isso o link dele aponta para a `main`, e não
para a tag. Da próxima versão em diante, monte o link pelo padrão acima: troque `{versão}` pela versão
destino que o dono pediu, nas duas posições, com o `v` na frente, como na tag. Sem a versão destino no
pedido, pare e pergunte ao dono qual é.

Ao montar o link pelo padrão, leia também o mesmo arquivo na `main` (o mesmo endereço, com `main` no
lugar de `v{versão}`). Prefira o da `main` quando ele trouxer a linha `Roteiro revisado em` nesta
seção: é a correção publicada depois da tag. Se as duas cópias divergirem e nenhuma trouxer essa linha,
ou se você não conseguir ler alguma delas, na dúvida, pare e pergunte ao dono antes de continuar.

## Antes de começar

O pedido que dispara este roteiro tem a forma "Atualize este projeto seguindo o roteiro em" seguida do
link acima. Rode cada conferência abaixo na raiz do projeto. Os comandos valem para Git Bash e para
PowerShell, exceto onde aparecem as duas formas.

1. **A árvore do git está limpa.** O comando de atualização recusa árvore suja, e é ela que torna tudo
   reversível.

   ```bash
   git status --porcelain
   ```

   Esperado: saída vazia. Com alguma linha, pare e peça ao dono que commite ou guarde o trabalho em
   andamento. Não commite nem guarde o trabalho do dono por conta própria.

2. **A versão instalada é a origem deste roteiro.**

   ```bash
   php artisan config:clear
   php artisan tinker --execute "echo config('kit.version');"
   ```

   Esperado: `0.51.2`. Qualquer outro valor encerra o roteiro aqui, sem alterar nada: `0.52.0` quer
   dizer que o projeto já está atualizado; uma versão anterior à `0.51.2` pede antes os roteiros das
   versões intermediárias, um de cada vez e em ordem, conferindo a versão entre um e outro; uma versão
   posterior à `0.52.0` quer dizer que este roteiro não serve a este projeto.

3. **O banco está acessível, e você guarda a impressão dos dados do dono.**

   ```bash
   php artisan migrate:status
   php artisan tinker --execute "echo config('kit.tenancy.enabled') ? 'SIM' : 'NAO';"
   php artisan tinker --execute "echo json_encode(['usuarios' => array_map('md5', App\Models\User::orderBy('id')->get()->keyBy('id')->map->toJson()->all()), 'jornadas' => array_map('md5', Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow::orderBy('id')->get()->keyBy('id')->map->toJson()->all()), 'passos' => array_map('md5', Wallacemartinss\FilamentOnboarding\Models\OnboardingStep::orderBy('id')->get()->keyBy('id')->map->toJson()->all()), 'configuracoes' => md5(json_encode(collect(app(App\Settings\ConfiguracoesDoKit::class)->toArray())->except('onboarding_habilitado')))]);"
   ```

   Esperado: a lista de migrations sai sem erro e nenhuma aparece como `Pending`. Uma migration
   pendente é do dono, e o `migrate --force` do passo 6 a rodaria junto: pare e pergunte antes. Anote se a multi-organização está `SIM` ou `NAO` e
   guarde a saída do último comando, a **impressão**: um hash por usuário, por jornada e por passo do
   tutorial, e um das Configurações da aplicação. Ela não expõe dado nem segredo, e no fim tem de bater.

4. **Os nomes que o comando reserva estão livres.** Ele usa o remote `kit`, as tags `kit-*` e o branch
   `kit-update/v0.52.0`, e apaga o remote e as tags que encontrar com esses nomes.

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.52.0
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch. Se algum existir, ele é do dono:
   pare e pergunte, sem apagar nada.

5. **O que a versão nova traz, sem aplicar arquivo.**

   ```bash
   php artisan kit:update --from=0.51.2 --tag=v0.52.0 --dry-run --keep-remote --no-interaction
   git diff kit-v0.51.2 kit-v0.52.0 -- composer.json composer.lock
   ```

   Esperado: um resumo da `v0.52.0` com os arquivos do kit que mudaram, cada um rotulado `novo no kit`,
   `modificado` ou `removido do kit`, e a frase de que nenhum arquivo foi tocado; e a saída do `git diff`
   **vazia**. O dry-run não aplica arquivo, mas cria o remote `kit` e as tags `kit-v0.51.2` e
   `kit-v0.52.0`, que os passos usam e o passo 10 remove. Pare se o resumo avisar que a tag pedida não
   existe, se ele falar de outra versão, ou se o `git diff` mostrar alguma linha: esta versão não traz
   dependência nova.

6. **Quais arquivos do kit o dono editou.** Confira cada arquivo do resumo que já existe no projeto. Um
   `modificado` se compara com a v0.51.2; um `novo no kit` que já existe se compara com a v0.52.0:

   ```bash
   # Git Bash
   git diff --quiet kit-v0.51.2 HEAD -- caminho/do/modificado; echo $?
   git diff --quiet kit-v0.52.0 HEAD -- caminho/do/novo; echo $?
   ```

   ```powershell
   # PowerShell
   git diff --quiet kit-v0.51.2 HEAD -- caminho/do/modificado; $LASTEXITCODE
   git diff --quiet kit-v0.52.0 HEAD -- caminho/do/novo; $LASTEXITCODE
   ```

   `0`: nada do dono no arquivo. `1`: o arquivo tem edição do dono, e ele entra na lista de conflitos
   da primeira decisão em `## Decisões do dono do projeto`. Veja a edição com o mesmo `git diff`, sem o
   `--quiet`, para descrevê-la ao dono. O `config/kit.php` é o candidato mais comum: é ali que ficam as
   credenciais do seeder.

## O que muda nesta versão

A v0.52.0 entrega o **tutorial guiado** nos três painéis (`/admin`, `/app` e `/infra`), construído
sobre o pacote `wallacemartinss/filament-onboarding`, que o projeto já tem instalado desde antes:

- jornadas de primeiros passos com um checklist flutuante, tours por feature e a página **Primeiros
  passos**, em `/{painel}/onboarding`;
- um interruptor novo, *Usar o tutorial guiado*, na aba Aplicação de `/admin/configuracoes-da-aplicacao`.
  Ele nasce **desligado**. Desligado, o checklist, os tours, a página "Primeiros passos" e a autoria de
  jornadas e condições no `/admin` somem e respondem 404, inclusive numa tela já aberta. Nada é apagado;
- na v0.51.2 a autoria de jornadas aparecia no menu do `/admin` sem interruptor. Depois da
  atualização ela só volta com o interruptor ligado, e as jornadas que o dono já criou continuam no
  banco;
- o tutorial depende de sete arquivos do kit que a versão muda: o `config/kit.php` (o bloco
  `onboarding`, que lê `KIT_ONBOARDING`), o `app/Settings/ConfiguracoesDoKit.php` (a propriedade
  `onboarding_habilitado` e a linha dela no mapa de configuração), o `phpunit.xml` (a linha
  `<env name="KIT_ONBOARDING" value="false" force="true"/>`, que deixa o tutorial desligado na suíte) e
  os providers `app/Providers/KitServiceProvider.php`, `app/Providers/Filament/AdminPanelProvider.php`,
  `AppPanelProvider.php` e `InfraPanelProvider.php`, que registram o tutorial nos painéis e o canal de
  log;
- uma migration de settings nova (`database/settings/2026_10_21_100000_add_onboarding_to_kit_settings.php`)
  cria a configuração `onboarding_habilitado` com o valor de `config('kit.onboarding.habilitado')` na
  hora em que roda: `false` sem o bloco `onboarding` no `config/kit.php` ou sem `KIT_ONBOARDING=true`.
  Depois disso o banco vence o `.env`;
- o conteúdo do tutorial vem de um seeder próprio, `OnboardingSeeder`, fora do `db:seed` comum: um
  seeder filho por feature em `database/seeders/Onboarding/` (21 sempre e 6 só com multi-organização).
  Ele é idempotente e não sobrescreve jornada nem passo que já existe. Semear não liga o interruptor;
- ligado, o tutorial aparece para quem tem a permissão `View:PrimeirosPassos`; cada passo aparece só
  para quem abre a tela dele, e o passo de uma feature desligada sai do checklist até ela voltar;
- o `config/filament-onboarding.php` passa a ter os idiomas `pt_BR` e `en` (antes só `en`): só os
  idiomas listados ali ficam editáveis na autoria;
- as views de `resources/views/vendor/filament-onboarding` passam a ser entregues pelo comando de
  atualização (a página "Primeiros passos" foi corrigida);
- um canal de log `onboarding` novo. O `config/logging.php` é do dono e não é entregue: o kit declara o
  canal sozinho quando o arquivo do projeto não o tem;
- os testes do kit (`tests/Kit`) e o `phpunit.xml` chegam com o tutorial desligado na suíte, e a suíte
  passa a pular até 942 casos, os que só valem dentro do repositório do kit.

Esta versão não traz pacote novo no `composer.json`.

## Cuidados

- **Nenhum comando que apague dados do banco.** Não rode `php artisan migrate:fresh`,
  `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem
  `php artisan migrate:rollback`, com ou sem prefixo (`./vendor/bin/sail artisan …`,
  `docker compose exec app php artisan …`). Os dados do banco são do dono, e esses comandos apagam
  tabelas ou desfazem migrations com dados dentro.
- **Não rode `php artisan kit:tenancy` nem `php artisan kit:install`.** Os dois recriam o banco. A
  multi-organização do projeto fica como está.
- **A suíte só roda com o banco forçado no próprio comando**, como no passo 9. Sem isso, ela pode usar o
  banco do dono e recriar as tabelas dele.
- **O `--all` sobrescreve os arquivos `modificado` e os `novo no kit` que já existem.** É por isso que o
  pré-voo lista os que o dono editou e a primeira decisão pergunta o que fazer com eles.
- **O comando de atualização que roda é o da v0.51.2.** Ele mesmo é um dos arquivos que a versão nova
  atualiza, e a classe antiga continua carregada até o fim da execução: as mensagens que você vê são as
  da versão anterior. O comportamento novo vale a partir da próxima execução.
- **A saída do comando não prova a atualização.** Ele marca `0.52.0` no `config/kit.php` assim que
  aplica um arquivo e anuncia cada `aplicado:` sem conferir o git. Confira o branch, o índice e os
  arquivos, no passo 3.
- **O `.env` e o `config/logging.php` são do dono.** Nenhum passo os sobrescreve. O único toque
  permitido no `.env` é a linha `KIT_ONBOARDING`, do jeito que o passo 4 descreve.
- **O `PapeisSeeder` redefine as permissões dos papéis do kit para as de fábrica.** Rode só com a
  resposta do dono à decisão das permissões.
- **Nada é commitado.** O comando trabalha no branch `kit-update/v0.52.0` e deixa as mudanças no
  índice: revise com `git status` e `git diff --cached`. Não commite, não faça merge e não apague o
  branch.
- **Desfazer é decisão do dono, e só com o pedido dele.** `git reset --hard HEAD` volta o índice e os
  arquivos ao último commit do dono, inclusive os arquivos novos que o update pôs no índice. Arquivos
  não rastreados que os comandos criaram aparecem no `git status` e saem um a um, com o ok dele. Nada
  disso desfaz o banco (migration, jornadas, permissões) nem a linha do `.env`.

## Decisões do dono do projeto

Faça as cinco perguntas abaixo ao dono **antes** do passo 1 de `## Passos`, de uma vez, com a
recomendação de cada uma. Espere a resposta. Sem resposta, siga o `Sem resposta:` de cada decisão e
registre a escolha no relatório final.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos do kit têm edição sua e a v0.52.0 também os traz (a lista do passo 6 de Antes de começar): para cada um, você quer manter a sua versão, receber a versão do kit, ou receber a do kit com as suas mudanças reaplicadas por cima?

Recomendação da release: receber a do kit com as suas mudanças reaplicadas, depois de ver o diff de cada um.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

No `config/kit.php`, no `app/Settings/ConfiguracoesDoKit.php` e no `phpunit.xml` há uma quarta
resposta: manter o seu arquivo e acrescentar só o que a v0.52.0 traz para o tutorial (o bloco
`onboarding`; a propriedade `onboarding_habilitado` e a linha dela no mapa; a linha `KIT_ONBOARDING` do
`phpunit.xml`). Junto com a pergunta, diga ao dono: se um dos sete arquivos do tutorial ficar com a
versão dele inteira, sem o que a v0.52.0 acrescenta, **ligar o tutorial nesta atualização não é
possível**, porque a migration lê o bloco `onboarding`, a configuração precisa da propriedade, a suíte
precisa do tutorial desligado e os painéis precisam registrar o tutorial. Com a lista vazia, não há o que perguntar: registre "nenhum conflito" e siga.

### Ligar o tutorial guiado

Pergunta: A v0.52.0 chega com o tutorial guiado desligado, e a autoria de jornadas que o /admin mostrava na v0.51.2 some junto com ele: você quer ligar o tutorial guiado neste projeto?

Recomendação da release: sim, ligar, junto com a semeadura das jornadas da decisão seguinte.

Sem resposta: não liga; o tutorial guiado fica desligado, como a release entrega, e a escolha vai para o relatório.

Se a decisão de conflito deixou um dos sete arquivos do tutorial com a versão inteira do dono, o "sim"
não pode ser cumprido: registre "ligar não foi possível" com o arquivo que impediu, e siga como "não".

### Semear as jornadas do tutorial

Pergunta: A v0.52.0 chega com as jornadas do tutorial não semeadas, porque o conteúdo fica fora do db:seed comum: você quer que eu semeie as jornadas agora?

Recomendação da release: sim, semear; o seeder não apaga nem sobrescreve jornada que já existe.

Sem resposta: não semeia, e o relatório traz o comando para semear depois.

### Permissão da página Primeiros passos

Pergunta: Para os papéis do kit enxergarem o tutorial, eles precisam da permissão View:PrimeirosPassos, e o PapeisSeeder a entrega redefinindo as permissões desses papéis para as de fábrica: você quer que eu rode o PapeisSeeder?

Recomendação da release: só se as permissões dos papéis do kit são as de fábrica; se você as mudou, responda não e dê a permissão pela tela /admin/shield/roles.

Sem resposta: não roda o PapeisSeeder nem mexe nos papéis, e registra no relatório.

### Idiomas da autoria do tutorial

Pergunta: A v0.52.0 define os idiomas da autoria pt_BR e en no config/filament-onboarding.php, onde a v0.51.2 tinha só en: você aceita a mudança?

Recomendação da release: sim, aceitar; o conteúdo semeado vem nos dois idiomas.

Sem resposta: mantém o arquivo do projeto como está (só en) e registra no relatório.

## Passos

Cada passo diz quando roda (condição), o que fazer (ação) e o que conferir antes de seguir (evidência).
Evidência que não aparece é motivo de parada, conforme `## Quando parar`.

### 1. Aplicar a versão do kit

Condição: as seis conferências de `## Antes de começar` passaram, e as decisões foram respondidas ou
registradas como sem resposta.

Ação:

```bash
php artisan kit:update --from=0.51.2 --tag=v0.52.0 --all --keep-remote --no-interaction
```

Evidência: a saída lista os arquivos como `aplicado:` e contém a linha
`config/kit.php: versão marcada como 0.52.0` (depois dela vêm a nota de que o próprio comando foi
atualizado e o bloco "Próximos passos"). O `--keep-remote` mantém as tags `kit-*` para o passo 2.
Se a saída disser "RODE O COMANDO DE NOVO", pare: veja `## Quando parar`.

### 2. Resolver os arquivos em conflito

Condição: a lista de conflitos não está vazia, ou a decisão dos idiomas não aceitou a mudança (aí o
`config/filament-onboarding.php` volta a ser o do projeto).

Ação, para cada arquivo, conforme a resposta:

- **manter a versão do dono:** `git checkout HEAD -- caminho/do/arquivo`;
- **receber a do kit:** nada a fazer, o passo 1 já aplicou;
- **receber a do kit com as mudanças do dono:** veja as mudanças com o `git diff` do passo 6 do
  pré-voo (sem o `--quiet`) e reaplique-as no arquivo com a ferramenta de edição, sem
  tocar no resto. Se elas não couberem na versão nova, pare e mostre ao dono;
- **arquivo do dono com só o acréscimo do tutorial** (`config/kit.php`, `app/Settings/ConfiguracoesDoKit.php`
  ou `phpunit.xml`): `git checkout HEAD -- caminho/do/arquivo`, depois veja o acréscimo com
  `git diff kit-v0.51.2 kit-v0.52.0 -- caminho/do/arquivo` e copie para o arquivo do dono só as linhas
  do tutorial (o bloco `onboarding`; a propriedade `onboarding_habilitado` e a linha do mapa; a linha
  `KIT_ONBOARDING`), no mesmo lugar em que a versão nova as põe.

Sempre que o `config/kit.php` voltar a ser o do dono, troque nele só a linha `'version' => '0.51.2',` por
`'version' => '0.52.0',`, com a ferramenta de edição de arquivo.

Evidência: `git diff HEAD -- caminho/do/arquivo` sai vazio para cada arquivo que ficou com a versão do
dono (ou só a versão do `config/kit.php` e as linhas do tutorial acrescentadas, quando foram pedidas).

### 3. Conferir a versão, o branch e os arquivos

Condição: sempre.

Ação:

```bash
php artisan config:clear
php artisan tinker --execute "echo config('kit.version');"
git branch --show-current
git diff --cached --name-status
```

```bash
# Git Bash
ls database/seeders/OnboardingSeeder.php app/Support/TutorialGuiado.php app/Filament/Pages/PrimeirosPassos.php database/settings/2026_10_21_100000_add_onboarding_to_kit_settings.php
```

```powershell
# PowerShell
'database/seeders/OnboardingSeeder.php','app/Support/TutorialGuiado.php','app/Filament/Pages/PrimeirosPassos.php','database/settings/2026_10_21_100000_add_onboarding_to_kit_settings.php' | ForEach-Object { "$_ $(Test-Path $_)" }
```

Evidência: a versão responde `0.52.0`; o branch é `kit-update/v0.52.0`; o índice tem os arquivos do
resumo do dry-run, menos os `removido do kit` e os que ficaram com a versão do dono; e os quatro
arquivos novos existem (no PowerShell, `True` nos quatro). Algo diferente: pare.

### 4. Acertar o interruptor antes da migration

Condição: sempre. A migration do passo 6 grava o valor que a configuração tiver na hora em que roda.

Ação: veja as linhas que o `.env` já tem com a chave.

```bash
# Git Bash
grep -n 'KIT_ONBOARDING' .env
```

```powershell
# PowerShell
Select-String -Path .env -Pattern 'KIT_ONBOARDING'
```

- Tutorial a ligar (decisão "sim", e possível) e nenhuma linha: acrescente `KIT_ONBOARDING=true` numa
  linha própria no fim do `.env`, com a ferramenta de edição, sem alterar nenhuma outra linha.
- Uma linha ativa com o valor contrário à escolha, ou mais de uma linha ativa: mostre ao dono e só
  troque com o ok dele. Sem o ok, pare.
- Nos outros casos, não mexa no `.env`.

Depois:

```bash
php artisan config:clear
php artisan tinker --execute "var_export(config('kit.onboarding.habilitado'));"
```

Evidência: `true` com o tutorial a ligar; `false` ou `NULL` nos outros casos. Diferente disso, pare:
uma variável de ambiente fora do `.env` ou o `config/kit.php` sem o bloco estão decidindo no lugar do
dono.

### 5. Publicar os assets e limpar os caches

Condição: sempre.

Ação:

```bash
php artisan filament:assets
php artisan optimize:clear
```

Evidência: os dois comandos terminam sem erro.

### 6. Rodar a migration da versão

Condição: sempre.

Ação:

```bash
php artisan migrate --force
php artisan tinker --execute "echo app(App\Settings\ConfiguracoesDoKit::class)->onboarding_habilitado ? 'SIM' : 'NAO';"
```

Evidência: a saída do `migrate` lista `2026_10_21_100000_add_onboarding_to_kit_settings` como
executada, e o interruptor responde `SIM` com o tutorial a ligar e `NAO` nos outros casos.

### 7. Semear as jornadas

Condição: o dono respondeu sim na decisão "Semear as jornadas do tutorial". Sem resposta ou com não,
pule este passo.

Ação:

```bash
php artisan db:seed --class=OnboardingSeeder --force --no-interaction
```

Evidência: o comando termina sem erro.

### 8. Gerar e entregar a permissão da página

Condição: o tutorial foi ligado no passo 6, ou o dono respondeu sim na decisão "Permissão da página
Primeiros passos". Nos outros casos, pule este passo.

Ação:

```bash
php artisan db:seed --class=ShieldPermissionsSeeder --force --no-interaction
php artisan tinker --execute "echo app(config('permission.models.permission'))->where('name', 'View:PrimeirosPassos')->count();"
```

Só com o sim na decisão das permissões:

```bash
php artisan db:seed --class=PapeisSeeder --force --no-interaction
php artisan tinker --execute "echo app(config('permission.models.permission'))->where('name', 'View:PrimeirosPassos')->first()?->roles->pluck('name');"
```

Evidência: a contagem é `1` ou mais; com o `PapeisSeeder`, a lista traz os papéis do kit. Um aviso
`shield:generate não pôde rodar`, ou contagem `0`, vira pendência no relatório. Se o seeder criar
arquivos novos em `app/Policies`, anote-os.

### 9. Rodar a suíte do kit

Condição: sempre.

Ação, com o banco descartável forçado no próprio comando. O `DB_URL` aponta para o mesmo banco em
memória: vazio, no PowerShell, ele apagaria a variável e deixaria um `DB_URL` do `.env` decidir.

```bash
# Git Bash
php artisan config:clear
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=sqlite:///:memory: php artisan test --testsuite=Kit,Tenancy --parallel --compact
```

```powershell
# PowerShell
php artisan config:clear
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE=':memory:'; $env:DB_URL='sqlite:///:memory:'
php artisan test --testsuite=Kit,Tenancy --parallel --compact
Remove-Item Env:DB_CONNECTION, Env:DB_DATABASE, Env:DB_URL
```

Evidência: zero erro, zero falha e **no máximo 942 pulados**. Os 942 são os casos que só valem dentro
do repositório do kit (leem arquivos que não viajam para o projeto). Mais que isso: pare e liste os
casos pulados a mais.

### 10. Remover o remote e as tags do kit

Condição: sempre, ao terminar ou ao parar depois do passo 5 de `## Antes de começar`.

Ação:

```bash
# Git Bash
git tag -l "kit-*" | xargs -r git tag -d
git remote remove kit
```

```powershell
# PowerShell
git tag -l "kit-*" | ForEach-Object { git tag -d $_ }
git remote remove kit
```

Evidência: `git remote` não lista `kit` e `git tag -l "kit-*"` sai vazio.

## Como conferir

O projeto atualizado tem de mostrar tudo isto, conferido com os comandos das seções de pré-voo e de passos:

| O que | Esperado |
|---|---|
| Versão do kit | `0.52.0`, depois de `php artisan config:clear` |
| Multi-organização | o mesmo `SIM` ou `NAO` anotado no pré-voo |
| Arquivos novos | os quatro do passo 3 presentes |
| Migration nova | `2026_10_21_100000_add_onboarding_to_kit_settings` executada |
| Interruptor do tutorial | `SIM` só com o pedido do dono e os sete arquivos do tutorial; `NAO` nos outros casos |
| Arquivos que ficaram com a versão do dono | `git diff HEAD -- caminho/do/arquivo` vazio em cada um |
| `config/logging.php` | `git diff HEAD -- config/logging.php` vazio |
| `.env` | as linhas do dono intactas; no máximo a linha `KIT_ONBOARDING` do passo 4 |
| Dados do banco | a impressão do pré-voo, rodada de novo: cada usuário e as Configurações com o mesmo hash; cada jornada e passo de antes com o mesmo hash (a semeadura só acrescenta ids novos) |
| Suíte do kit | zero erro, zero falha, no máximo 942 pulados |
| Branch | `kit-update/v0.52.0`, com as mudanças no índice e sem commit |
| Remote e tags do kit | removidos no passo 10 |

## Quando parar

Pare, não tente contornar e diga ao dono o que viu, com a saída do comando, quando:

- a árvore do git não está limpa no começo: o trabalho em andamento é do dono;
- a versão instalada não é `0.51.2`: diga a versão instalada, a origem deste roteiro (`v0.51.2`) e,
  se o projeto estiver atrás, que faltam os roteiros das versões entre as duas;
- já existe remote `kit`, tag `kit-*` ou branch `kit-update/v0.52.0` no projeto;
- você não conseguiu ler o roteiro pelo link, ou a cópia da tag e a da `main` divergem sem a linha
  `Roteiro revisado em`;
- o dry-run fala de outra versão, ou o `git diff` do `composer.json` e do `composer.lock` não sai vazio;
- o comando de atualização sai sem a linha `config/kit.php: versão marcada como 0.52.0`, recusa a árvore
  ou pede para rodar de novo: o projeto pode ter ficado entre as duas versões, e a segunda rodada exige
  commit, que é decisão do dono;
- a conferência do passo 3 ou do passo 4 dá outro resultado;
- a migration do passo 6 falha: não tente desfazer migrations, mostre o erro ao dono;
- a suíte do kit termina com erro, falha ou mais de 942 pulados: liste os casos e não mexa nos testes;
- a impressão dos dados do banco mudou;
- um passo pediria um comando que `## Cuidados` proíbe.

Ao parar depois do passo 5 de `## Antes de começar`, rode ainda o passo 10. Neste roteiro não há
promessa de funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida sobre um passo,
pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- a versão de partida e a de chegada (`config('kit.version')` antes e depois);
- o branch em que as mudanças ficaram e o lembrete de que nada foi commitado;
- os arquivos aplicados e os que ficaram com a versão do dono, com o que deixou de chegar em cada um;
- cada decisão do dono: a pergunta, a resposta, ou "sem resposta" com o default aplicado; e "ligar não
  foi possível", com o arquivo que impediu, quando for o caso;
- o estado do interruptor do tutorial guiado e se as jornadas foram semeadas; sem a semeadura, o
  comando para semear depois: `php artisan db:seed --class=OnboardingSeeder`;
- a permissão `View:PrimeirosPassos`: se existe e com quais papéis, e as pendências do passo 8;
- os arquivos novos que o `ShieldPermissionsSeeder` criou em `app/Policies`, se criou;
- a saída da suíte do kit, com o número de pulados;
- a impressão dos dados antes e depois, e o estado da multi-organização;
- se parou: em que passo, a saída do comando e o que falta para continuar;
- como revisar: `git status` e `git diff --cached`; e como desfazer, só com o pedido do dono:
  `git reset --hard HEAD`, os arquivos não rastreados um a um, e a lembrança de que o banco e o `.env`
  não voltam com isso.
<!-- roteiro-de-atualizacao:fim -->
