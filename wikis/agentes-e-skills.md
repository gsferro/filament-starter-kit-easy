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

# Roteiro de atualização — v0.52.1 → v0.53.1

Leia o roteiro inteiro antes de alterar um arquivo do projeto.

## Identificação

Origem: v0.52.1

Destino: v0.53.1

Data: 2026-11-15

Link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v0.53.1/wikis/atualizacoes/v0.53.1.md

Padrão do link: https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md

Na próxima atualização, substitua `{versão}` pela versão destino do pedido. Sem ela, pergunte ao dono.

Leia também o mesmo arquivo na `main`. Se o arquivo da `main` trouxer a linha `Roteiro revisado em`, prefira o arquivo da `main`. Se as cópias divergirem sem essa marca, ou uma delas não abrir, pare e informe o problema ao dono. Na dúvida, pare e pergunte ao dono.

## Antes de começar

O pedido que dispara este roteiro é: “Atualize meu projeto da v0.52.1 para a v0.53.1 seguindo este roteiro”. Ele pode vir com o link da Identificação ou sem ele; sem o link, monte-o pelo padrão do link com a versão destino `v0.53.1` e confira que o endereço montado é o mesmo da Identificação.

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

   Esperado: `0.52.1`. Outro valor encerra este roteiro sem aplicar arquivos: `0.53.1` indica que o marcador já está no destino; `0.53.0` não encerra: o projeto recebeu o código da v0.53.0 pelo `kit:update` antes de a tag ser retirada (veja O que muda nesta versão) e segue este roteiro com a mesma origem `0.52.1` nos comandos, com o cuidado do passo 6; uma versão anterior a `0.52.1` exige primeiro as atualizações intermediárias, em ordem; uma versão posterior a `0.53.1` exige outro roteiro. Informe ao dono o valor encontrado.

3. **O banco está acessível, e a impressão dos dados foi guardada.**

   ```bash
   php artisan migrate:status
   php artisan tinker --execute 'echo config("kit.tenancy.enabled") ? "SIM" : "NAO";'
   php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map(fn ($tenant) => md5($tenant->makeHidden("aparencia_das_telas")->toJson()))->all(), "configuracoes" => md5(json_encode(collect(app(App\Settings\ConfiguracoesDoKit::class)->toArray())->except("aparencia_das_telas")->all()))]);'
   ```

   Esperado: migrations consultadas sem erro e nenhuma `Pending`. Se houver pendência, informe ao dono quais são e pare: o passo 4 roda `php artisan migrate --force`, que executaria junto as migrations pendentes do dono.

   Anote o estado da multi-organização, `SIM` ou `NAO`, e guarde integralmente a última saída: um hash por usuário, um por organização e um das Configurações da aplicação. Essa é a **impressão dos dados**, que deve ser idêntica ao final. A comparação exclui só o campo novo `aparencia_das_telas`, que esta versão acrescenta vazio às Configurações e à tabela de organizações; com a multi-organização desligada, a lista de organizações costuma vir vazia, e vazia deve continuar.

4. **Os nomes reservados pelo comando estão livres.**

   ```bash
   git remote
   git tag -l "kit-*"
   git branch --list kit-update/v0.53.1
   ```

   Esperado: nenhum remote `kit`, nenhuma tag `kit-*` e nenhum branch `kit-update/v0.53.1`. Se algum existir, pare e informe ao dono, sem apagar nada: o comando usa esses nomes e remove o remote e as tags ao encerrar.

5. **O dry-run corresponde à release, sem dependência nova.**

   ```bash
   php artisan kit:update --from=0.52.1 --tag=v0.53.1 --dry-run --keep-remote --no-interaction
   git diff --stat kit-v0.52.1 kit-v0.53.1 -- composer.json composer.lock package.json package-lock.json
   git diff --name-status kit-v0.52.1 kit-v0.53.1 -- database/migrations database/settings tests/Kit tests/Tenancy
   ```

   Esperado:

   - resumo entre `kit-v0.52.1` e `kit-v0.53.1`, com os caminhos rotulados `novo no kit`, `modificado` ou `removido do kit`, e a informação de que nenhum arquivo foi tocado;
   - diff dos quatro arquivos de dependências **vazio**: esta versão não traz pacote novo nem bump, e não há install a fazer;
   - exatamente duas migrations novas: `database/migrations/2026_11_13_100000_add_aparencia_das_telas_to_tenants_table.php` e `database/settings/2026_11_13_100000_add_aparencia_das_telas_to_kit_settings.php`;
   - os arquivos novos `tests/Kit/AparenciaDasTelasDeAcessoTest.php`, `tests/Kit/AparenciaDasTelasAdendoTest.php`, `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php`, `tests/Kit/fixtures/ComponenteDeRascunhoDeTeste.php` e `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php`;
   - no resumo, entre outros, os novos `app/Filament/Pages/Auth/TelaRedefinirSenha.php`, `app/Filament/Pages/Auth/TelaVerificacaoDeEmail.php`, `app/Filament/Pages/Auth/PreviewDasTelasDeAcesso.php`, `app/Support/AparenciaDasTelasDeAcesso.php` e `resources/views/filament/auth/painel-de-marca.blade.php`.

   O dry-run mantém o remote `kit` e as tags com prefixo `kit-`, usados nas comparações seguintes.

   Se a tag ainda não estiver disponível, pare e informe isso ao dono. Não substitua a tag pela `main`. Se o resumo ou os diffs não corresponderem às evidências acima, pare e mostre a divergência.

6. **As edições do dono foram identificadas antes de qualquer aplicação.**

   Para cada arquivo do resumo que existe no projeto, compare com a origem quando for `modificado`, ou com o destino quando for `novo no kit`:

   ```bash
   git diff --name-only kit-v0.52.1 HEAD -- caminho/do/modificado
   git diff --name-only kit-v0.53.1 HEAD -- caminho/do/novo
   ```

   Saída vazia significa que não há diferença naquele arquivo. Caminho impresso entra na lista de conflitos. Repita sem `--name-only` para descrever a edição ao dono.

   Com o marcador em `0.53.0`, o projeto já tem os arquivos da v0.53.0, e a comparação com `kit-v0.52.1` acusa todos eles. Para esses caminhos, compare também com o commit da v0.53.0 (que chega junto com a tag `kit-v0.53.1`); saída vazia aqui significa que o arquivo é o da v0.53.0, sem edição do dono, e ele sai da lista de conflitos:

   ```bash
   git diff --name-only bfce1edd90e568afe4ef0944480160ce04317a55 HEAD -- caminho/do/modificado
   ```

   Confira especialmente, porque esta versão os altera e são os lugares onde o dono costuma mexer:

   - os providers dos painéis `app/Providers/Filament/AdminPanelProvider.php`, `app/Providers/Filament/AppPanelProvider.php` e `app/Providers/Filament/InfraPanelProvider.php`, em especial o bloco `AuthDesignerPlugin` de cada um;
   - `app/Providers/KitServiceProvider.php`, que registra a rota nova do preview;
   - `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`, a cópia do kit do partial de mídia do Auth Designer;
   - as páginas de autenticação `app/Filament/Pages/Auth/TelaLogin.php`, `TelaRecuperarSenha.php`, `TelaBloqueio.php`, `TelaDoisFatores.php` e `RegistroPorConvite.php`, na mesma pasta;
   - `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, `app/Settings/ConfiguracoesDoKit.php` e `app/Models/Tenant.php`;
   - os arquivos de `app/Filament/Admin/Resources/Tenants/` que aparecerem no resumo;
   - `resources/css/filament/kit.css` e `public/css/kit/kit-correcoes.css`;
   - `tests/Pest.php`, que recebe os helpers dos testes novos;
   - `config/kit.php`, incluindo as credenciais e configurações do dono.

## O que muda nesta versão

A v0.53.1 é a entrega da v0.53.0. A tag `v0.53.0` foi publicada por engano no commit da v0.52.1 e o Packagist, que não deixa uma versão estável mudar de conteúdo, guardou essa referência: quem instalou a v0.53.0 pelo `composer create-project` recebeu o código da v0.52.1, com o marcador `0.52.1`. A tag `v0.53.0` voltou a esse commit e não deve ser usada; este roteiro vai direto da v0.52.1 para a v0.53.1.

A v0.53.1 traz a **aparência das telas de acesso**, escolhida na própria aplicação. A aba **Telas de acesso** de *Configurações da aplicação*, no `/admin`, troca o layout das telas de autenticação dos três painéis (login, cadastro, recuperação e redefinição de senha, confirmação de e-mail, dois fatores e bloqueio de sessão) entre o **clássico**, que é o de sempre e continua o default, e o **painel de marca**: o formulário de um lado e, do outro, um painel arredondado de largura escolhida (30% a 70%), com cor ou arte de fundo, a logo num selo claro, um destaque central (a logo grande ou uma imagem PNG, JPEG ou WebP enviada), uma ilustração de rede de pontos parada ou em movimento, com botão de pausar e respeito a "reduzir movimento", e duas frases opcionais com negrito, itálico, tachado e link (Markdown seguro). O fundo pode ser a cor primária, uma cor própria, um **gradiente** ou a arte. No celular o painel vira uma faixa curta acima do formulário, só com a logo ou o nome da aplicação. Login, cadastro e "esqueci a senha" podem ter **cada um a sua aparência**, e a troca entre eles é animada (**transição entre as telas**: o painel desliza para o lado da outra tela e o formulário aparece aos poucos, só em computador e sem "reduzir movimento"). Login social e desafio anti-robô continuam no formulário em qualquer layout. Um **preview** mostra login, recuperação de senha e cadastro com as alterações ainda não salvas, em largura de computador e de celular, sem autenticar nem enviar nada.

Com a multi-organização ligada, o formulário de cada organização ganha a seção **Telas de acesso**: usar a aparência padrão ou ter a sua, que vale no login com `?org=`, no cadastro e no bloqueio de sessão dela.

**Nada muda na tela de quem atualiza** até um administrador escolher o painel de marca. As duas migrations novas nascem vazias: a configuração `aparencia_das_telas` no grupo `kit` das Configurações, com valor nulo, e a coluna JSON anulável `aparencia_das_telas` na tabela `tenants`. Nulo significa layout clássico. O `config/kit.php` ganha, além do marcador `0.53.1`, a chave `aparencia_das_telas` com valor `null` no bloco `identidade`.

O que chega em arquivos:

- as páginas de **redefinição de senha** (o link do e-mail) e de **confirmação de e-mail** passam a ser do kit, `TelaRedefinirSenha` e `TelaVerificacaoDeEmail`, só para receberem a aparência. A redefinição fica registrada nos três providers, pelo `usingResetPage` dentro do bloco `AuthDesignerPlugin`; a confirmação de e-mail, só no provider do `/app`, o único painel com ela no ar. Antes, as duas eram as páginas do pacote;
- o partial de mídia do Auth Designer que o kit sobrescreve ganha um ramo novo para o painel de marca; o clássico segue pelos ramos de sempre;
- a rota nova `/admin/preview-das-telas-de-acesso`, registrada pelo `KitServiceProvider`, protegida pelo acesso ao `/admin`;
- o CSS do kit (`resources/css/filament/kit.css` e o publicado `public/css/kit/kit-correcoes.css`) ganha as regras do painel de marca, com classes próprias: não há rebuild do front-end;
- a imagem do destaque, quando enviada, é gravada no disco `public`, em `storage/app/public/telas-de-acesso/`, e servida pelo link `public/storage`;
- os testes da feature em `tests/Kit` e `tests/Tenancy`, os helpers deles em `tests/Pest.php` e um teste de browser em `tests/Browser`.

Não há dependência nova nem bump de composer ou npm, jornada nova a semear, permissão nova a gerar nem default de configuração alterado.

## Cuidados

- **Não use a tag `v0.53.0`.** Ela foi retirada e aponta para o código da v0.52.1; o `kit:update` interativo ainda a lista como `kit-v0.53.0`. Rode sempre com `--tag=v0.53.1`, como nos passos.
- **Preserve o banco existente.** Não rode `php artisan migrate:fresh`, `php artisan db:wipe`, `php artisan migrate:refresh`, `php artisan migrate:reset` nem `php artisan migrate:rollback`, inclusive por Sail ou Docker. As duas migrations desta versão só acrescentam; não as desfaça para "testar de novo".
- **Preserve o modo de multi-organização.** Não rode `php artisan kit:tenancy` nem `php artisan kit:install`, pois recriam o banco.
- **Não escolha o painel de marca pelo dono.** A atualização entrega o layout clássico. Não grave aparência nas Configurações nem em organização, não envie imagem e não salve a aba **Telas de acesso**: isso mudaria as telas de acesso do projeto e a impressão dos dados.
- **Os arquivos da feature trabalham juntos.** Manter a versão do dono num provider de painel deixa a redefinição de senha (e, no `/app`, a confirmação de e-mail) na página do pacote, sem a aparência. Manter o partial de mídia faz o painel de marca nunca aparecer, mesmo escolhido. Manter o `KitServiceProvider` sem a rota nova quebra o preview da aba **Telas de acesso**. Manter o `app/Models/Tenant.php` impede a organização de gravar a sua aparência. Manter o `tests/Pest.php` deixa os testes novos sem os helpers. Em todos esses casos, registre a entrega como parcial.
- **A suíte pode recriar tabelas no banco que receber.** Execute-a somente com as três variáveis de banco forçadas no próprio comando do passo 6, depois de limpar o cache de configuração.
- **O `--all` substitui arquivos existentes.** Resolva previamente a decisão de conflito e restaure ou reaplique as edições conforme o passo 2 antes de executar os demais passos.
- **O marcador não prova a entrega.** O comando pode marcar `0.53.1` depois de aplicar apenas parte dos arquivos. Confira o branch, os arquivos novos, as migrations e a rota.
- **Não crie o link de storage por conta própria.** Se o passo 5 mostrar que `public/storage` não existe, registre e recomende ao dono; não rode comandos que mexam em `public/` além dos deste roteiro.
- **O `.env` e os canais de log são do dono.** Esta versão não exige alterar o `.env` nem o `config/logging.php`.
- **As mudanças ficam para revisão.** O comando aplica arquivos no índice. Use `git status` e `git diff --cached`; não commite, não faça merge e não apague o branch por conta própria.
- **Desfazer exige pedido do dono.** `git reset --hard HEAD` desfaz alterações de arquivos e do índice, incluindo arquivos novos adicionados pelo update. Arquivos não rastreados precisam ser avaliados e removidos um a um. Isso não reverte o banco: a configuração e a coluna novas continuam lá, vazias.

## Decisões do dono do projeto

Apresente a lista de conflitos do pré-voo antes do passo 1. Faça a pergunta com os diffs e a recomendação. Sem resposta, aplique o default abaixo e registre as limitações. Com a lista vazia, registre “nenhum conflito” e siga.

Nenhuma outra decisão do dono nesta versão.

### Conflito em arquivo do kit que você editou

Pergunta: Estes arquivos do kit têm edição sua e a v0.53.1 também os altera (em especial o bloco AuthDesignerPlugin dos providers dos painéis e o partial de mídia do Auth Designer): para cada arquivo, você quer manter a sua versão, receber a versão do kit ou receber a versão do kit com suas mudanças reaplicadas?

Recomendação da release: receber a versão do kit com suas mudanças reaplicadas, depois de revisar os diffs; nos providers, preservar as suas configurações do `AuthDesignerPlugin` e acrescentar o `usingResetPage(TelaRedefinirSenha::class)` e, no `/app`, a `TelaVerificacaoDeEmail` na confirmação de e-mail; no partial de mídia, acrescentar o ramo do painel de marca antes dos seus ramos.

Sem resposta: mantém a versão do dono e registra no relatório, arquivo a arquivo, o que deixou de chegar.

Explique junto da pergunta a dependência entre os arquivos: manter um provider, o partial de mídia, o `KitServiceProvider`, o `app/Models/Tenant.php` ou o `tests/Pest.php` na versão do dono deixa parte da aparência das telas de acesso sem efeito, como descrito em `## Cuidados`, e a entrega fica parcial mesmo com o marcador em `0.53.1`. Com o `tests/Pest.php` mantido, os casos dos testes novos da aparência falham por falta dos helpers: diga isso na pergunta, porque o passo 6 vai parar nesse caso. No `config/kit.php`, preservar o conteúdo do dono inclui mudar o marcador para `0.53.1` e acrescentar a chave `aparencia_das_telas` com valor `null` no bloco `identidade`, sem substituir suas configurações.

## Passos

Cada passo tem condição, ação e evidência. Se a evidência não aparecer, pare e informe ao dono conforme `## Quando parar`.

### 1. Aplicar os arquivos entregues pelo comando

Condição: as seis conferências do pré-voo passaram, e a decisão de conflito foi respondida ou registrada com o default seguro.

Ação:

```bash
php artisan kit:update --from=0.52.1 --tag=v0.53.1 --all --keep-remote --no-interaction
```

Evidência: os arquivos aparecem como `aplicado:`, a saída contém `config/kit.php: versão marcada como 0.53.1` e o branch criado é `kit-update/v0.53.1`. As tags ficam disponíveis para resolver conflitos.

Se o comando recusar a árvore, pedir nova execução ou anunciar outra versão, pare. Não faça commit para tentar uma segunda rodada.

### 2. Resolver os conflitos conforme a decisão

Condição: existem arquivos em conflito. Sem conflitos, siga para o passo 3.

Ação, arquivo a arquivo:

- **Manter a versão do dono:** restaure com `git checkout HEAD -- caminho/do/arquivo`.
- **Receber a versão do kit:** mantenha o que o passo 1 aplicou.
- **Receber a versão do kit com as mudanças do dono:** consulte o diff guardado no pré-voo e reaplique somente as mudanças do dono com a ferramenta de edição. Se a integração não for clara, pare e mostre o ponto ao dono.

Quando preservar o `config/kit.php`, troque `'version' => '0.52.1',` por `'version' => '0.53.1',` e acrescente, no bloco `identidade`, logo depois da chave `arte_do_login`, a linha `'aparencia_das_telas' => null,`. Nada mais muda nesse arquivo.

Revise e adicione ao índice cada arquivo integrado:

```bash
git diff HEAD -- caminho/do/arquivo
git add -- caminho/do/arquivo
```

Inclua o `config/kit.php` no índice depois de conferir que as demais configurações foram preservadas:

```bash
git diff HEAD -- config/kit.php
git add -- config/kit.php
```

Evidência: arquivos mantidos integralmente têm `git diff HEAD -- caminho/do/arquivo` vazio. No `config/kit.php` preservado, as únicas diferenças são a versão e a chave nova. Arquivos integrados contêm a mudança do kit e as customizações do dono, com o resultado revisado no índice. Registre o que ficou pendente.

### 3. Conferir o branch, o marcador e os arquivos novos

Condição: aplicação e resolução dos conflitos concluídas.

Ação:

```bash
php artisan config:clear
php artisan tinker --execute 'echo config("kit.version");'
git branch --show-current
git status --short
git diff --cached --name-status
git ls-files --error-unmatch app/Filament/Pages/Auth/TelaRedefinirSenha.php app/Filament/Pages/Auth/TelaVerificacaoDeEmail.php app/Filament/Pages/Auth/PreviewDasTelasDeAcesso.php app/Support/AparenciaDasTelasDeAcesso.php resources/views/filament/auth/painel-de-marca.blade.php database/migrations/2026_11_13_100000_add_aparencia_das_telas_to_tenants_table.php database/settings/2026_11_13_100000_add_aparencia_das_telas_to_kit_settings.php tests/Kit/AparenciaDasTelasDeAcessoTest.php tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php
```

Evidência: versão `0.53.1`, branch `kit-update/v0.53.1` e os nove caminhos novos impressos pelo `git ls-files`. O índice corresponde ao resumo do dry-run, consideradas as escolhas de conflito. Os arquivos que o dono manteve aparecem no relatório como exceções.

A ausência de arquivo novo só pode corresponder a uma escolha explícita de conflito, que deixa a entrega incompleta e deve ser registrada. Ausência sem essa explicação exige parada.

### 4. Rodar as duas migrations da versão

Condição: o passo 3 passou, e o pré-voo não mostrou migration pendente do dono.

Ação:

```bash
php artisan migrate --force
php artisan migrate:status
php artisan tinker --execute 'var_export(app(App\Settings\ConfiguracoesDoKit::class)->aparencia_das_telas);'
php artisan tinker --execute 'echo App\Models\Tenant::whereNotNull("aparencia_das_telas")->count();'
```

Evidência: a saída do `migrate` lista como executadas exatamente `2026_11_13_100000_add_aparencia_das_telas_to_tenants_table` e `2026_11_13_100000_add_aparencia_das_telas_to_kit_settings`, e nenhuma outra; o `migrate:status` não mostra `Pending`; a configuração responde `NULL` (layout clássico); a contagem de organizações com aparência própria responde `0`.

Qualquer outra migration executada, erro de coluna ou configuração diferente de `NULL` exige parada.

### 5. Publicar os assets, limpar os caches e conferir a rota

Condição: as migrations terminaram sem erro.

Ação:

```bash
php artisan filament:assets
php artisan optimize:clear
php artisan route:list --name=kit.preview-das-telas-de-acesso
php artisan tinker --execute 'echo file_exists(public_path("storage")) ? "SIM" : "NAO";'
```

Evidência: os dois primeiros comandos terminam sem erro; o `route:list` mostra a rota `admin/preview-das-telas-de-acesso` com o nome `kit.preview-das-telas-de-acesso`; a última linha responde `SIM`. Confira `git status --short` e revise eventuais arquivos gerados em `public/` antes de adicioná-los ao índice. Não inclua artefatos inesperados.

Com `NAO`, o link `public/storage` não existe: a imagem do destaque, quando o dono enviar uma, não vai aparecer nas telas. Registre e recomende ao dono rodar `php artisan storage:link`; não rode por conta própria.

Rota ausente só é aceitável se o dono manteve o `KitServiceProvider` na versão dele; registre como entrega parcial. Sem essa escolha, pare.

Não há `composer install`, `npm install`, build do front-end, semeadura de jornadas ou geração de permissões nesta atualização.

### 6. Rodar a suíte Kit,Tenancy com banco descartável

Condição: arquivos, migrations e assets foram conferidos e os caches foram limpos.

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

Evidência: código de saída zero, zero erro, zero falha e **no máximo 1161 pulados** fora da árvore do kit. Os pulados permitidos são os casos que leem arquivos exclusivos do repositório do kit; os cinco a mais que na versão anterior são do `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php`, que confere a documentação e o site, ausentes do projeto. Se houver mais, liste os casos adicionais e pare sem modificar os testes.

Os testes da aparência gravam imagens de teste num disco falso, não em `storage/app/public/telas-de-acesso/`. Se essa pasta aparecer com arquivos depois da suíte, pare e informe.

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
php artisan tinker --execute 'var_export(config("kit.identidade.aparencia_das_telas"));'
php artisan migrate:status
php artisan tinker --execute 'echo json_encode(["usuarios" => array_map("md5", App\Models\User::orderBy("id")->get()->keyBy("id")->map->toJson()->all()), "organizacoes" => App\Models\Tenant::orderBy("id")->get()->keyBy("id")->map(fn ($tenant) => md5($tenant->makeHidden("aparencia_das_telas")->toJson()))->all(), "configuracoes" => md5(json_encode(collect(app(App\Settings\ConfiguracoesDoKit::class)->toArray())->except("aparencia_das_telas")->all()))]);'
git diff HEAD -- .env config/logging.php
git status --short
git diff --cached --stat
```

Evidência: `kit.version` responde `0.53.1`; a multi-organização tem o mesmo valor do pré-voo; `kit.identidade.aparencia_das_telas` responde `NULL`; as migrations têm o estado do pré-voo mais as duas desta versão, sem pendências; a impressão dos usuários, das organizações e das Configurações é exatamente igual à inicial; o diff de `.env` e `config/logging.php` é vazio.

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

Nenhum remote `kit` e nenhuma tag `kit-*`. O branch `kit-update/v0.53.1` permanece com as mudanças para revisão, sem commit.

## Como conferir

O estado esperado é o mesmo dos cenários 3 e 4 de atualização: versão, arquivos e migrations conferidos, com ou sem multi-organização, telas de acesso idênticas às de antes, preservando as customizações e os dados do dono.

| O que | Esperado |
|---|---|
| Versão do kit | `0.53.1`, depois de limpar o cache de configuração |
| Arquivos novos que provam a entrega | `TelaRedefinirSenha`, `TelaVerificacaoDeEmail`, `PreviewDasTelasDeAcesso`, `AparenciaDasTelasDeAcesso` e o partial `painel-de-marca` presentes e rastreados |
| Testes novos | `tests/Kit/AparenciaDasTelasDeAcessoTest.php` e `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` presentes e rastreados |
| Multi-organização | O mesmo `SIM` ou `NAO` do pré-voo |
| Migrations | As duas da versão executadas, nenhuma outra, nenhuma pendente |
| Aparência | Configuração `aparencia_das_telas` nula, nenhuma organização com aparência própria, `kit.identidade.aparencia_das_telas` nulo: layout clássico |
| Rota do preview | `kit.preview-das-telas-de-acesso` registrada, ou a limitação da decisão registrada |
| Providers e partial de mídia | Recebidos ou integrados, ou a limitação da decisão registrada |
| Dependências | Nenhuma mudança; nenhum install executado |
| Link de storage | `public/storage` presente, ou a recomendação registrada |
| Suíte Kit,Tenancy | Zero erro, zero falha, no máximo 1161 pulados |
| Policies geradas pelos testes | Não rastreadas removidas e listadas no relatório; rastreadas preservadas |
| Arquivos do dono | Mantidos ou integrados conforme a decisão, sem mudanças inesperadas |
| `.env` | Intacto |
| Canais de log | `config/logging.php` intacto, incluindo os canais próprios do projeto |
| Dados do banco | Impressão final idêntica à inicial: mesmos usuários, mesmas organizações e mesmas Configurações, fora o campo novo vazio |
| Branch e índice | `kit-update/v0.53.1`, mudanças revisáveis e sem commit |
| Remote e tags temporários | Remote `kit` e tags `kit-*` removidos |

Uma escolha de manter arquivo antigo deve aparecer como entrega parcial. O marcador `0.53.1` sozinho não permite declarar que a aparência das telas de acesso chegou inteira.

## Quando parar

Pare sem tentar contornar e informe ao dono a condição encontrada, a saída relevante e o que falta para continuar:

- **Roteiro indisponível ou cópias divergentes sem marca de revisão:** diga qual leitura falhou ou qual divergência encontrou.
- **Árvore suja no início:** liste os caminhos e peça que o dono resolva o trabalho em andamento.
- **Versão instalada diferente de `0.52.1`:** informe o valor encontrado e a origem exigida.
- **Nomes reservados já existentes:** informe o remote, as tags ou o branch encontrados; não os remova.
- **Banco inacessível ou migrations pendentes do dono:** mostre o erro ou os nomes pendentes; o `migrate --force` deste roteiro as executaria junto.
- **Tag destino indisponível:** informe que a atualização depende da publicação de `v0.53.1`.
- **Dry-run diferente do previsto:** mostre os arquivos divergentes. Qualquer mudança nos arquivos de dependências entre as tags, ou migration além das duas esperadas, não é esperada.
- **Arquivo novo esperado ausente na tag:** informe o caminho; não substitua a tag por outra referência.
- **Aplicação incompleta, árvore recusada ou pedido de segunda execução:** informe que o projeto pode estar entre versões e que nenhuma nova rodada ou commit foi feito.
- **Integração de conflito inviável:** mostre o trecho e preserve a decisão segura, sem inventar uma solução.
- **Migration com erro, migration inesperada executada ou aparência diferente de nula depois dela:** entregue a saída; não tente desfazer a migration.
- **Branch, marcador, rota ou arquivos fora do esperado:** informe os valores e caminhos encontrados.
- **Suíte com erro, falha ou mais de 1161 pulados:** entregue a saída e os casos envolvidos, sem alterar os testes. Com o `tests/Pest.php` mantido na versão do dono, diga que as falhas dos testes novos da aparência decorrem dessa escolha.
- **Arquivos criados em `storage/app/public/telas-de-acesso/` pela suíte:** informe os caminhos.
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
- arquivos mantidos na versão do dono e o que deixou de chegar em cada um, em especial nos providers, no partial de mídia, no `KitServiceProvider`, no `app/Models/Tenant.php` e no `tests/Pest.php`;
- saída do `migrate --force`, com as duas migrations executadas, e a confirmação de que a aparência ficou nula nas Configurações e nas organizações, ou seja, telas de acesso no layout clássico;
- saída do `filament:assets`, a rota do preview conferida e o estado do link `public/storage`, com a recomendação quando ausente;
- confirmação de que não havia dependência nova nem necessidade de install, build, semeadura de jornadas ou geração de permissões;
- saída da suíte Kit,Tenancy, quantidade efetiva de pulados e pedido para o dono rodar a suíte completa em ambiente de testes;
- cada policy não rastreada removida depois da suíte;
- impressões dos dados antes e depois, resultado da comparação e estado da multi-organização;
- confirmação de preservação do `.env`, dos canais de log e das customizações;
- confirmação de remoção do remote `kit` e das tags `kit-*`;
- como o dono escolhe o painel de marca, se quiser: no `/admin`, *Configurações da aplicação*, aba **Telas de acesso**, com o preview antes de salvar;
- se houve parada: passo, saída relevante, estado parcial e condição necessária para continuar.

Explique como revisar: `git status` e `git diff --cached`. Para desfazer, somente com pedido do dono, use `git reset --hard HEAD` e avalie os arquivos não rastreados um a um. Lembre que isso não reverte o banco: a configuração e a coluna novas continuam lá, vazias.

<!-- roteiro-de-atualizacao:fim -->
