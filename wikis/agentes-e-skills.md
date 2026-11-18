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
# Roteiro de atualização — v0.54.0 → v0.55.0

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.54.0

Destino: v0.55.0

Data: 2026-11-18

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.55.0/wikis/atualizacoes/v0.55.0.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.54.0 para a v0.55.0 seguindo este roteiro”. Ele pode vir com o link da Identificação ou sem ele; sem o link, monte-o pelo padrão com a versão destino `v0.55.0` e confira que é o mesmo endereço da Identificação. O agente segue os passos abaixo usando o `kit:update` para aplicar os arquivos.

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

   Esperado: `0.54.0`. Outro valor encerra este roteiro sem aplicar arquivos: `0.55.0` indica marcador já no destino; uma versão anterior exige as atualizações intermediárias, em ordem; uma versão posterior exige outro roteiro. Informe ao dono o valor encontrado.

3. **O banco está acessível e a impressão dos dados foi guardada.**

   Confirme com o dono que há backup recuperável antes de aplicar os arquivos.

   ```bash
   php artisan migrate:status
   php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
   php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => array_map("md5", App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "configuracoes" => md5(json_encode(app(App\Settings\ConfiguracoesDoKit::class)->toArray()))]);'
   ```

   Esperado: consulta sem erro e nenhuma migration `Pending`. Havendo pendência do projeto, informe os nomes e pare: esta versão não tem migration e não deve executar pendências alheias. Anote a multi-organização, `SIM` ou `NAO`, e guarde integralmente a última saída: um hash por usuário, um por organização e um das Configurações da aplicação. Essa **impressão dos dados** deve ser idêntica ao final, sem excluir campos.

4. **Os nomes reservados pelo comando estão livres.**

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.55.0
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.55.0`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à release.**

   ```bash
   php artisan kit:update --from=0.54.0 --tag=v0.55.0 --dry-run --keep-remote --no-interaction
   git diff --stat kit-v0.54.0 kit-v0.55.0 -- composer.json composer.lock package.json package-lock.json
   git diff --name-status kit-v0.54.0 kit-v0.55.0 -- database/migrations database/settings resources public
   git diff --name-status kit-v0.54.0 kit-v0.55.0 -- app/Console/Commands/KitUpdate.php config/kit.php composer-unused.php .github/workflows/ci.yml tests/Kit tests/Tenancy
   git diff kit-v0.54.0 kit-v0.55.0 -- composer.json .github/workflows/ci.yml
   ```

   Esperado: resumo entre `kit-v0.54.0` e `kit-v0.55.0`, sem tocar arquivos; duas dependências novas no `require-dev`, suas dependências de desenvolvimento no lock e os três scripts abaixo; nenhuma mudança em `require`, pacotes de produção, dependências JS, migrations, settings, resources ou assets publicados. Confira o novo `composer-unused.php`, a inclusão dele na entrega em `KitUpdate.php`, o marcador e o passo de relatório do CI. O dry-run mantém o remote `kit` e as tags para as comparações seguintes.

   Se a tag ainda não estiver disponível, pare e informe ao dono. Não substitua a tag pela `main`. Migration, mudança de runtime, dependência além das previstas ou ausência de parte da entrega exige parada com o diff encontrado.

6. **As edições do dono foram identificadas.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.54.0 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.55.0 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos; repita sem `--name-only` para descrever a edição ao dono. Confira especialmente `app/Console/Commands/KitUpdate.php`, `config/kit.php` e eventual `composer-unused.php` próprio. Compare também os scripts do `composer.json` e o job `qualidade` do CI do projeto, que serão integrados manualmente: esses arquivos não são aplicados pelo comando.

## O que muda nesta versão

A v0.55.0 acrescenta o relatório de **dependências declaradas × usadas**. Nada muda em runtime, nenhuma dependência de produção é alterada e não há default mudado. Não há migration, alteração de esquema ou de settings, build, publicação de assets, semeadura ou geração de permissões.

Entram duas dependências novas de desenvolvimento: `maglnet/composer-require-checker:^4.16` e `icanhazstring/composer-unused:^0.9`. O `composer-unused` aponta dependências declaradas e não usadas; o `composer-require-checker` aponta símbolos usados de pacotes transitivos e extensões PHP não declarados diretamente.

Confira os arquivos e pontos de integração:

- `composer.json`: as duas entradas de `require-dev` e os scripts `deps:check`, `deps:unused` e `deps:require`, copiados no passo 4. Eles ficam fora do `composer test`. O `kit:update` NÃO aplica o `composer.json`, só relata a diferença; a instalação e os scripts exigem integração manual, preservando as dependências do projeto. O lock do projeto é resolvido pelo Composer, sem copiar o do kit.
- Novo `composer-unused.php` na raiz, trazido pelo `kit:update`: exclui somente cinco pacotes usados por caminhos que a análise não enxerga, cada um com seu motivo: `anselmokossa/filament-sentinel` (auto-discovery do provider), `laravel/pulse` (configuração, dashboard e daemon), `laravel/reverb` (serviço opt-in), `livewire/blaze` (compilação do Blade por auto-discovery) e `spatie/laravel-backup` (configuração e comando de backup).
- `app/Console/Commands/KitUpdate.php`: inclui `composer-unused.php` nos caminhos entregues; a atualização já lê os caminhos do destino para trazer o arquivo nesta rodada.
- `config/kit.php`: somente o marcador passa a `0.55.0`; as demais configurações continuam iguais.
- `.github/workflows/ci.yml`: novo passo **Dependências declaradas × usadas (relatório)** no job `qualidade`, após a análise estática. Roda as duas ferramentas, grava a saída no resumo do job e só relata, sem reprovar por achados. `.github` não viaja pelo `kit:update`: integre o passo manualmente se o projeto tem esse workflow; se não tem, registre a ausência, sem criar CI inteiro.

A linha de base do kit em 2026-11-18 tem três pacotes sem uso: `awcodes/filament-badgeable-column`, `lara-zeus/progress` e `syriable/filament-activitylog`; e 181 símbolos usados sem declaração direta: 137 de pacotes transitivos e 44 de oito extensões PHP (`ext-ctype`, `ext-dom`, `ext-filter`, `ext-gd`, `ext-libxml`, `ext-mbstring`, `ext-SimpleXML` e `ext-tokenizer`). Os achados do projeto podem diferir conforme seu código. Esta atualização entrega o diagnóstico; não remove pacotes, não declara transitivos ou extensões em produção e não esconde achados reais nas exclusões.

## Cuidados

- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker. Não há migration nesta versão; não execute atualização de esquema.
- **Preserve a multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **Dependências exigem integração manual.** Não substitua o `composer.json` nem o `composer.lock` do dono pelos do kit. Instale só as duas ferramentas em ambiente de desenvolvimento e revise o lock gerado; conflito de resolução ou alteração inesperada de produção exige parada.
- **Achado não é defeito da execução.** Localmente, `deps:require` e, por consequência, `deps:check` podem retornar código diferente de zero por símbolos não declarados. Guarde a saída e o código; falha para iniciar, configuração inválida ou ferramenta ausente exige análise. No CI, preserve o caráter de relatório, sem converter em gate ou incluir no `composer test`.
- **Não esconda dependência realmente sem uso.** As cinco exclusões representam uso indireto comprovado; não acrescente os três pacotes sem uso à configuração para zerar a saída.
- **A suíte pode recriar tabelas no banco que receber.** Force as três variáveis de banco do passo 6, depois de limpar o cache de configuração.
- **O `--all` substitui arquivos existentes.** Resolva a decisão de conflito antes e restaure ou integre as edições do dono antes de validar. O marcador `0.55.0` sozinho não prova que o arquivo novo, as ferramentas e os scripts chegaram.
- **Preserve `.env`, canais de log e customizações.** Não copie `.env.example` sobre `.env`; esta versão não pede variável de ambiente nem configuração de runtime.
- **As mudanças ficam para revisão.** Use `git status` e `git diff --cached`; não commite, não faça merge nem apague o branch por conta própria. Desfazer exige pedido do dono: `git reset --hard HEAD` desfaz arquivos e índice; avalie arquivos não rastreados um a um. Isso não desfaz o banco nem restaura os pacotes instalados em `vendor`.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo, com os diffs, antes do passo 1. Sem conflitos, registre “nenhum conflito” e siga. Nenhuma outra decisão do dono nesta versão.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos têm edição sua e a v0.55.0 também os altera; manter o comando ou a configuração de análise antigos pode deixar parte do relatório sem chegar: para cada arquivo, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas após revisar os diffs, preservando configurações e integrações do projeto; no `config/kit.php`, integrar somente o marcador. Para scripts e CI editados pelo dono, integrar somente os trechos desta versão.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono.

### 1. Aplicar os arquivos

Condição: as seis conferências do pré-voo passaram, e cada conflito tem resposta ou default seguro registrado.

Ação:

```bash
php artisan kit:update --from=0.54.0 --tag=v0.55.0 --all --keep-remote --no-interaction
```

Evidência: arquivos anunciados como `aplicado:`, incluindo `composer-unused.php`, saída `config/kit.php: versão marcada como 0.55.0` e branch `kit-update/v0.55.0`. A diferença do `composer.json` foi apenas relatada. Se o comando recusar a árvore, pedir segunda execução ou anunciar outra versão, pare; não faça commit para tentar outra rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: há conflitos; sem eles, siga para o passo 3.

Ação, arquivo a arquivo: para manter a versão do dono, use `git checkout HEAD -- caminho/do/arquivo`; para receber a do kit, mantenha o aplicado; para integrar, reaplique somente as mudanças do dono conforme o diff guardado. Integração incerta exige parada. No `config/kit.php` preservado, troque `'version' => '0.54.0',` por `'version' => '0.55.0',`, preservando todas as demais configurações.

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

Evidência: arquivos mantidos têm diff vazio contra `HEAD`, integrações contêm ambas as mudanças revisadas e o config preserva as demais configurações. Registre cada parte do relatório que não chegou.

### 3. Conferir marcador e arquivos

Condição: aplicação e conflitos concluídos.

Ação:

```bash
php artisan optimize:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git ls-files --error-unmatch composer-unused.php
git diff kit-v0.55.0 -- composer-unused.php app/Console/Commands/KitUpdate.php config/kit.php
git diff HEAD -- database/migrations database/settings resources public
```

Evidência: versão `0.55.0`, branch `kit-update/v0.55.0`, arquivo novo presente e rastreado, com as cinco exclusões justificadas. Diferenças contra o destino correspondem somente às decisões registradas; o índice corresponde ao dry-run. Nenhuma migration ou asset alterado. Não rode migrate: não há migration nesta atualização.

### 4. Instalar as ferramentas e copiar os scripts

Condição: arquivos conferidos, ambiente de desenvolvimento com Composer e eventuais conflitos dos scripts resolvidos. Confira também a extensão PHP `sodium`: `php -m | grep -i sodium` (no PowerShell, `php -m | Select-String sodium`). O `composer-require-checker` 4.24 depende do `php-standard-library`, que a exige; sem ela o `composer require` abaixo falha na resolução. No Windows ela vem comentada no `php.ini` (`;extension=sodium`): peça ao dono para descomentá-la antes de seguir — não edite o `php.ini` por conta própria.

Ação:

```bash
composer require --dev "maglnet/composer-require-checker:^4.16" "icanhazstring/composer-unused:^0.9"
```

Copie estas três entradas para o objeto `scripts` do `composer.json` do projeto, preservando todos os outros scripts:

```json
"deps:unused": [
    "Composer\\Config::disableProcessTimeout",
    "composer-unused --no-progress --ignore-exit-code"
],
"deps:require": [
    "Composer\\Config::disableProcessTimeout",
    "composer-require-checker check composer.json"
],
"deps:check": [
    "@deps:unused",
    "@deps:require"
]
```

O trecho é parte do objeto `scripts`; ajuste a vírgula de separação conforme as entradas vizinhas. Não acrescente as ferramentas ao `composer test`.

```bash
composer validate --no-check-publish
git diff HEAD -- composer.json composer.lock
git add -- composer.json composer.lock
```

Evidência: as duas ferramentas instaladas em `require-dev`, os três scripts iguais aos acima e demais dependências e scripts preservados. Lock atualizado pelo Composer, sem mudança inesperada em pacotes de produção. Se a resolução falhar, entregue o erro; não amplie versões nem remova dependências para forçar a instalação.

### 5. Integrar o relatório no CI e executar a análise

Condição: ferramentas e scripts instalados; conflito no CI resolvido quando houver workflow próprio.

Ação: se existir `.github/workflows/ci.yml` com job `qualidade`, copie o passo abaixo após a análise estática, preservando os demais passos. Ele depende de instalação prévia das dependências de desenvolvimento. Se não houver esse workflow ou job, registre a integração como não aplicável e siga com o relatório local.

```yaml
      - name: Dependências declaradas × usadas (relatório)
        continue-on-error: true
        run: |
          {
            echo '## Dependências declaradas × usadas'
            echo
            echo '### composer-unused — declaradas e não usadas'
            echo '```'
            vendor/bin/composer-unused --no-progress --no-ansi --ignore-exit-code
            echo '```'
            echo '### composer-require-checker — usadas e não declaradas'
            echo '```'
            vendor/bin/composer-require-checker check composer.json --no-ansi || true
            echo '```'
          } | tee -a "$GITHUB_STEP_SUMMARY"
```

Execute localmente, tanto em Git Bash quanto em PowerShell:

```bash
composer deps:check
git diff HEAD -- .github/workflows/ci.yml
```

Guarde a saída literal de `composer deps:check` e seu código de saída antes do comando seguinte (`echo $?` no Git Bash; `$LASTEXITCODE` no PowerShell). Se houve integração no workflow, coloque somente o arquivo alterado no índice com `git add -- .github/workflows/ci.yml`.

Evidência: `composer deps:check` roda as duas ferramentas e lista os achados do projeto. A linha de base do kit é três pacotes sem uso e 181 símbolos; diferenças do projeto são descritas, sem exigir essas contagens como meta. Código não zero causado pelos achados do checker é esperado; erro de execução é condição de parada. O passo do CI usa `--ignore-exit-code`, `|| true` e `continue-on-error: true` para só relatar no resumo; confira o resumo na próxima execução, sem publicar ou disparar workflow por conta própria.

### 6. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos, ferramentas, scripts e relatório conferidos; nenhuma migration a executar.

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

### 7. Conferir a preservação dos dados e do projeto

Condição: suíte concluída e variáveis PowerShell restauradas, quando aplicável.

Ação: repita as consultas da multi-organização e da impressão dos dados do pré-voo, sem excluir campos. Rode também:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
php artisan migrate:status
git diff HEAD -- .env config/logging.php database/migrations database/settings
git status --short
git diff --cached --stat
```

Evidência: `0.55.0`, mesma multi-organização, mesmas migrations, nenhuma pendente e impressão dos dados idêntica. Diff vazio de `.env`, canais de log e esquema; customizações preservadas conforme as decisões. A suíte do projeto continua verde: em ambiente de testes, rode também `php artisan test --compact` com as três variáveis de banco descartável do passo 6, preservando e restaurando a sessão no PowerShell. Registre a saída; sem execução, registre a pendência e não declare a suíte do projeto validada.

### 8. Remover o remote e as tags temporários

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

Se uma tentativa anterior já removeu algum nome, confira e remova somente o que ainda existir. Evidência: `git remote` não lista `kit` e `git tag -l "kit-*"` tem saída vazia. O branch `kit-update/v0.55.0` permanece, com as mudanças para revisão e sem commit.

## Como conferir

O estado esperado é o dos cenários 3 e 4 de atualização: com ou sem multi-organização, versão, arquivos e migrations conferidos, dados e customizações preservados.

| O que | Esperado |
|---|---|
| Marcador | `0.55.0` depois de limpar o cache de configuração |
| Arquivo novo | `composer-unused.php` presente, com as cinco exclusões justificadas ou integração registrada |
| Entrega | `KitUpdate.php` inclui o arquivo novo; demais diferenças correspondem às decisões |
| Dependências | Duas ferramentas novas em `require-dev`, lock resolvido no projeto e dependências de produção preservadas |
| Scripts | `deps:check`, `deps:unused` e `deps:require` copiados; fora de `composer test` |
| Relatório local | `composer deps:check` executado, achados e código de saída registrados |
| CI | Passo no job `qualidade` só relata no resumo, ou ausência do workflow/job registrada |
| Migrations | Nenhuma nova e nenhuma executada; `php artisan migrate:status` sem pendências |
| Runtime e defaults | Sem mudança de comportamento, configuração ou liberação de recurso |
| Multi-organização | Mesmo modo de tenancy do pré-voo |
| Suíte Kit,Tenancy | Zero erro, zero falha, até 1161 pulados fora da árvore do kit |
| Testes do projeto | Suíte continua verde em banco descartável; saída ou pendência explícita registrada |
| Policies | Não rastreadas geradas pela suíte removidas e listadas; rastreadas preservadas |
| Arquivos e dados do dono | Impressão anterior idêntica; `.env`, customizações e canais de log intactos |
| Revisão | Branch `kit-update/v0.55.0`, índice revisável, sem commit; remote e tags temporários removidos |

Não há tela nova ou escolha visual para validar. Arquivo antigo mantido que impeça a análise é entrega parcial, mesmo com marcador `0.55.0`. Não declare o relatório do CI observado se ele ainda não executou.

## Quando parar

Pare e entregue ao dono a condição, a saída relevante e o que falta para continuar:

- Roteiro indisponível, cópias divergentes sem marca de revisão ou dúvida de interpretação.
- Árvore suja no início, versão instalada diferente de `0.54.0`, banco inacessível, backup não confirmado ou migrations pendentes do projeto: liste os valores e caminhos encontrados.
- Remote, tags ou branch reservados já existentes: informe os nomes, sem removê-los.
- Tag `v0.55.0` indisponível ou dry-run divergente: mostre os diffs, principalmente migration, mudança de produção, dependência além das previstas ou arquivo novo ausente.
- Comando recusado, pedido de segunda rodada, aplicação incompleta, integração incerta ou marcador, branch e arquivos fora do previsto: descreva o estado parcial, sem commit ou nova rodada.
- Resolução do Composer com erro, script conflitante sem integração definida, ferramenta ausente ou configuração de análise inválida: entregue a saída, sem ampliar dependências ou esconder achados para passar. Achados reportados pelas ferramentas, por si só, não interrompem o roteiro.
- Suíte com erro, falha ou mais de 1161 pulados na Kit,Tenancy: entregue a saída e os casos envolvidos, sem alterar testes para passar.
- Policy rastreada alterada, impressão de dados diferente, multi-organização alterada ou mudança inesperada em `.env`, canais de log ou customizações: mostre a diferença antes de restaurar qualquer coisa.
- Um passo exigiria ação proibida em **Cuidados**: informe a necessidade e aguarde orientação.

Ao parar depois de criar os nomes temporários, execute ainda o passo 8 para remover somente o remote e as tags deste roteiro. Preserve o branch e as mudanças para revisão. Este roteiro não promete funcionar em agentes ou hosts que não foram exercitados com ele. Na dúvida, pare e pergunte ao dono.

## Relatório final

Entregue ao dono, ao terminar ou ao parar:

- versões de partida e chegada, saída de `config('kit.version')`, branch e confirmação de que nada foi commitado nem integrado por merge;
- arquivos aplicados e conferidos, presença de `composer-unused.php` e integrações;
- cada decisão de conflito, pergunta e resposta ou “sem resposta”, default aplicado e limitações por arquivo; sem conflitos, registre isso; nenhuma outra decisão e nenhum default mudado;
- comando de instalação executado, ferramentas em `require-dev`, scripts copiados e diferenças de `composer.json` e `composer.lock`, preservando as dependências do projeto;
- integração manual do passo no job `qualidade`, caráter de relatório e resultado do resumo do CI, ou ausência do workflow/job e validação ainda pendente;
- saída literal de `composer deps:check`, achados, diferenças em relação à linha de base e código de saída; nenhuma remoção ou declaração de dependência de produção feita para zerar o relatório;
- saída de `php artisan migrate:status` e confirmação de que não há migration, execução de alteração de esquema, build, publicação de assets, semeadura ou geração de permissões nesta atualização;
- saída literal da suíte Kit,Tenancy, contagem efetiva de pulados, pedido para rodar `php artisan test --compact` em banco descartável e resultado da suíte do projeto ou pendência explícita;
- policies não rastreadas removidas, impressões dos dados antes e depois e resultado da comparação;
- preservação de `.env`, canais de log, customizações e estado da multi-organização; runtime e defaults preservados;
- remoção do remote `kit` e das tags `kit-*`;
- em caso de parada, passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar com `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Isso não reverte o banco nem restaura `vendor`; após restaurar os manifests, os pacotes instalados precisam corresponder novamente ao lock restaurado.
<!-- roteiro-de-atualizacao:fim -->
