# feature-wiki — Documentação Antes de Implementar

> **Skill**: [`SKILL.md`](SKILL.md) · versão **2.10.0**
> Este README fala com a **pessoa**: por que a skill existe, o que ela entrega, dependências e limitações. O procedimento que o agente segue está no `SKILL.md` e não é duplicado aqui.

## Índice

- [O que a skill faz](#o-que-a-skill-faz)
- [Os arquivos que ela cria](#os-arquivos-que-ela-cria)
- [Quando é invocada](#quando-é-invocada)
- [Dependências](#dependências)
- [Como informar o requisito](#-como-informar-o-requisito-para-a-feature-wiki)
- [Testes de Browser (Pest + Playwright)](#-testes-de-browser-na-feature-wiki-pest--playwright)
- [Documentation API do Boost](#-documentation-api-do-boost-search-docs)

---

## O que a skill faz

Força o agente a **documentar antes de codar**. Em vez de sair implementando a partir de um card, ele produz uma wiki versionada da feature: o requisito bruto, um plano de ação minucioso o suficiente para outro agente executar sem ambiguidade, as decisões arquiteturais com alternativas descartadas, os casos de teste **antes** do código, e o tracking do progresso.

**O ganho central** não é documentação bonita — é que **escrever o caso de teste antes de implementar força a pesquisa das APIs envolvidas na fase de planejamento, não na fase de debug**. Nome de método errado, FK obrigatória em fixture, restrição de schema de biblioteca externa: tudo isso aparece ao escrever o CT, quando custa uma linha de correção, em vez de aparecer no meio da implementação.

### Vantagens

| Vantagem | Como |
|---|---|
| Plano executável por outro agente | passos numerados com path exato, assinatura, lógica e logs especificados |
| Premissa validada antes de codar | step 3 obriga `search-docs`, leitura de vendor source, `Grep` no código real |
| Plano auditado contra over-engineering | step 6 invoca `/ponytail:ponytail-review` **automaticamente** |
| Teste como especificação, não como sobra | `04` e `05` escritos antes da implementação |
| Log padronizado e rastreável | formato `[Classe@Método]` + channel por feature + context estruturado |
| Decisão que não se perde | ADR com contexto, alternativas e consequências |
| Retomada sem reler tudo | `03-progresso.md` atualizado em tempo real |
| Validação por quem não implementou | CT-B escritos em loop por sub-agente, e QA pela [`feature-quality-gate`](../feature-quality-gate/README.md) |

## Os arquivos que ela cria

```text
wikis/specs/{branch}/{feature}/
├── 00-requisito.md               ← requisito bruto IMUTÁVEL + cláusulas RQ-##
├── 01-plano-acao.md              ← PRD: passos, rotas, autorização, logs, riscos
├── 02-decisoes-arquiteturais.md  ← ADRs
├── 03-progresso.md               ← checklist + blockers + desvios + retrospectiva
├── 04-casos-de-teste.md          ← CTs de backend (Feature/Unit)
├── 05-casos-de-teste-browser.md  ← CT-B (condicional: só com UI que justifique)
├── 05-*.md                       ← extras: api-contract, rollback, security, performance
└── 06-relatorio-qa.md            ← saída do feature-quality-gate
```

Os **cinco primeiros são obrigatórios** (o `05` de browser é condicional a um gate). Os `05-*` extras e o `06` são criados conforme necessidade.

## Quando é invocada

**Sempre** que começar uma feature nova, um card, um ticket — antes de qualquer `php artisan make:*`.

**Não** invocar para: correção de texto, ajuste de config trivial, refactoring sem mudança de comportamento, bump de dependência, seeder isolado. O critério: se a mudança não adiciona lógica de negócio, não altera fluxo de dados e não cria arquivo de código, não precisa de wiki. Bug fix **com nova regra de negócio** precisa.

## Dependências

### Obrigatórias

Nenhuma além de um projeto Laravel com git. A skill funciona com Pest 3, 4 ou 5, com ou sem Boost.

### Opcionais — a skill degrada e declara o que não pôde fazer

| Item | O que habilita | Sem ela |
|---|---|---|
| Laravel Boost | `search-docs`, `database-schema`, `database-query`, `Browser Logs` | pesquisa cai para doc oficial e `Grep` |
| Pest 5 | `--parallel --tia`, `--agent`, sharding, novos matchers | usa `pest --filter` |
| `pest-plugin-browser` + Playwright | CT-B executáveis | `05` fica como roteiro manual |
| Playwright MCP | observar a página no loop de correção do CT-B | `screenshot()`, `content()` filtrado, leitura do Blade |
| PCOV ou Xdebug | pré-requisito do `--tia` | roda o suite completo |
| Ponytail | escada de simplicidade na execução + auditoria do plano | step 6 fica manual |
| Caveman | prosa terse na conversa (nunca nos arquivos wiki) | — |
| [`feature-quality-gate`](../feature-quality-gate/README.md) | step 8: QA confrontando requisito × plano × app | step 8 é pulado |
| [`requirement-to-rule`](../requirement-to-rule/README.md) | step 9: decisão da wiki vira Project Rule | step 9 é pulado |

---

## 📥 Como informar o requisito para a `feature-wiki`

A partir da **v2.10.0**, a `feature-wiki` cria um arquivo antes do PRD: **`00-requisito.md`** — o requisito **como ele chegou**, sem interpretação.

### Por que isso existe

O mesmo agente lê o requisito, escreve o PRD, escreve os testes, implementa e roda os testes. Se ele **entendeu o requisito errado**, erra coerentemente cinco vezes e tudo fica verde. O PRD não serve como linha de base porque **ele é a interpretação** — validar contra o PRD confirma o erro em vez de expô-lo.

O `00-requisito.md` é a única linha de base independente do agente. É também o oráculo do [`feature-quality-gate`](../feature-quality-gate/README.md): sem ele, a etapa de QA não tem contra o que confrontar.

### Como passar o requisito

| Forma | O que fazer |
|---|---|
| **Colar o texto no chat** (card do Jira/Azure/GitHub, e-mail, mensagem) | cole o texto do card **como está**. O agente transcreve verbatim — não corrige ortografia, não resume, não reordena |
| **Arquivo no projeto** (`.md`, `.pdf`, `.docx`) | aponte o caminho: *"o requisito está em `docs/requisitos/RF-231.pdf`, páginas 3 e 4"*. O agente lê, registra o path + páginas e transcreve os trechos normativos |
| **Descrever na conversa** | funciona, mas é marcado como **fidelidade baixa** e o agente pede confirmação antes de implementar |
| **Não informar** | a skill **para e pede**. Sem requisito não há linha de base, e a wiki nasce cega |

Exemplo de invocação:

```text
/feature-wiki

Card FERRO-579:
"Precisamos gerar os relatórios de MBA em lote, por turma. Apenas o coordenador
da turma pode disparar. Avisar o coordenador quando terminar. Precisa ser rápido."
```

### O que a skill faz com isso

1. **`## Texto Original`** — verbatim, **imutável**. Nunca editado, nem para "melhorar"
2. **`## Decomposição em Cláusulas`** — cada exigência vira um `RQ-##` citando o trecho literal:

| ID | Cláusula | Trecho literal | Tipo |
|----|----------|----------------|------|
| RQ-01 | gerar relatório por turma em lote | "em lote, por turma" | funcional |
| RQ-02 | só coordenador da turma dispara | "Apenas o coordenador da turma pode disparar" | autorização |
| RQ-03 | notificar coordenador ao concluir | "Avisar o coordenador quando terminar" | funcional |
| RQ-04 | ⚠️ "rápido" sem número | "Precisa ser rápido" | não-funcional |

3. **`## Ambiguidades e Perguntas Abertas`** — a RQ-04 já entrega valor **antes de qualquer código**: *"rápido" não é testável; qual o SLA?* Vira pergunta para você em vez de suposição silenciosa do agente
4. **`## Fora de Escopo`** — o que o requisito explicitamente não pede, para o quality gate não acusar omissão indevida

Depois disso, **todo passo do PRD e todo CT/CT-B cita o `RQ` que atende**. É essa amarração que permite detectar cláusula sem plano, sem teste ou sem código.

> **Wiki antiga sem `00`**: ao retomar uma wiki criada antes da v2.10.0, a skill pede o requisito original **a você** — nunca deriva do PRD. PRD derivado de PRD não é oráculo.

---

## 🧪 Testes de Browser na feature-wiki (Pest + Playwright)

A partir da **v2.6.0** (refinado na v2.7.0), a `feature-wiki` cria — **quando pertinente** — um quinto arquivo dedicado a casos de teste de navegador: `05-casos-de-teste-browser.md`.

### Por que isso existe

Até a v2.5.0 o `04-casos-de-teste.md` era 100% backend: `RefreshDatabase`, `Queue::fake()`, `Http::fake()`, `Log::spy()`, factories. Uma feature Filament/Livewire saía da wiki com CTs provando que o Job despachou e o log saiu — e **nada** provando que o botão renderiza, que o `wire:model` persiste ou que o modal fecha. O arquivo `05` fecha essa lacuna.

Ele tem **duplo uso**:

1. **Especificação de teste** — CT-B executáveis via `pest-plugin-browser`
2. **Roteiro de auditoria** — tabela *Desenhado × Implementado*, preenchida na pós-implementação, que confere linha por linha o que o PRD prometeu contra a tela que existe de fato

### Quando o arquivo é criado (gate)

O `01-plano-acao.md` passou a ter uma seção obrigatória `## Superfície de UI`:

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `RelatorioLoteForm` | Livewire | `/relatorios/lote` | seleciona turma, dispara geração | Sim |

O `05-casos-de-teste-browser.md` só é criado se houver ao menos uma linha nessa tabela **e** pelo menos uma destas condições:

1. `Depende de JS? = Sim` (Livewire, Filament, Alpine, Inertia, upload, modal, polling), **ou**
2. a interação atravessa ≥ 2 telas/etapas (wizard, checkout, aprovação em duas mãos)

Se o gate não passar, a skill **não cria o arquivo** e registra no `04` a linha *"Sem CT-B: {motivo}"*. Feature de job, webhook, command ou import de CSV **não** gera CT-B — isso é deliberado, para a seção não virar burocracia morta.

### Fronteira entre o 04 e o 05

| Pergunta | Arquivo | Tipo de teste |
|---|---|---|
| A regra de negócio está correta? | `04-casos-de-teste.md` | `Feature` / `Unit` |
| O usuário consegue chegar até a regra? | `05-casos-de-teste-browser.md` | `Browser` |

Se um `CT-B` falha e nenhum `CT` de backend falha → o defeito é de UI. Se ambos falham → corrigir o backend primeiro.

**Teto deliberado (Ponytail)**: no máximo **1 happy path + 1 erro visível ao usuário** por feature. A matriz de regras de negócio continua no `04`, que é ordens de magnitude mais rápido. Browser é caro; usar como bisturi, não como rede de arrasto.

### Dependências que o projeto precisa ter

O runtime dos CT-B é o **Pest Browser plugin**, que roda **Playwright** por baixo. A skill não assume que está instalado: se faltar, ela inclui a instalação como passo numerado na seção `## Dependências` do PRD.

```bash
# 1. Plugin de browser do Pest
composer require pestphp/pest-plugin-browser --dev

# 2. Playwright + browsers
npm install playwright@latest
npx playwright install
```

E adicione ao `.gitignore`:

```gitignore
tests/Browser/Screenshots
```

**Requisitos de ambiente para rodar os CT-B:**

| Item | Detalhe |
|---|---|
| Node.js | necessário para o Playwright |
| Browsers | baixados por `npx playwright install` |
| App servido | acessível na `APP_URL` — Herd, `php artisan serve`, Sail ou Vite dev server. **Registrar no `05` qual é o do projeto** |
| Assets | `npm run build` executado, ou dev server ativo |
| DB | seeders determinísticos; `RefreshDatabase` no `tests/Pest.php` |

**Opcional, mas recomendado (Pest 5):**

```bash
# Verificação pontual de mudança pelo próprio agente de IA
composer require pestphp/pest-plugin-agent --dev

# Driver de cobertura — pré-requisito do --tia
pecl install pcov     # ou Xdebug
```

**Instalação/upgrade do Pest 5** (a doc oficial não usa `php artisan pest:install`):

```bash
composer remove phpunit/phpunit
composer require pestphp/pest --dev --with-all-dependencies
./vendor/bin/pest --init          # cria tests/Pest.php
```

Vindo do Pest 4: `"pestphp/pest": "^5.0"` no `composer.json` e todos os plugins para `^5.0`. Requer **PHP 8.4+** e PHPUnit 13.

**CI (GitHub Actions)** — os CT-B exigem Node e browsers no runner:

```yaml
- uses: actions/setup-node@v4
  with:
    node-version: lts/*
- run: npm ci
- name: Install Playwright Browsers
  run: npx playwright install --with-deps
```

### Comandos

| Comando | Uso |
|---|---|
| `vendor/bin/pest --filter={Feature} --compact` | CTs de backend (arquivo `04`) |
| `vendor/bin/pest tests/Browser --filter={Feature}` | CT-B (arquivo `05`) |
| `vendor/bin/pest --parallel --tia` | Loop rápido durante a implementação (Pest 5) |
| `vendor/bin/pest --headed` | Ver o browser abrindo, para depurar um CT-B |
| `vendor/bin/pest --browser firefox` | Rodar CT-B em outro navegador |
| `vendor/bin/pest --agent='...'` | Verificação pontual e efêmera pelo agente (Pest 5) |

### O que o Pest 5 trouxe para a skill

A `feature-wiki` incorpora três recursos do Pest 5 (requer **PHP 8.4 + PHPUnit 13**):

**1. TIA — Test Impact Analysis (`--parallel --tia`)**

Roda só os testes afetados pelo diff e replica do cache o restante. Exige PCOV ou Xdebug. O suite de 19.000+ testes do Laravel Cloud caiu de ~3 minutos para ~5 segundos.

**Sobre o `--parallel`**: ele **não** é pré-requisito técnico do `--tia` — `vendor/bin/pest --tia` funciona sozinho. Mas a invocação canônica da doc oficial é `./vendor/bin/pest --parallel --tia`, e os dois são complementares: o TIA corta **quanto** roda, o `--parallel` corta **quanto tempo** o que sobrou leva. A skill adotou `--parallel --tia` como padrão.

> **Cuidado com `--parallel` + CT-B**: browser em paralelo multiplica processos de navegador e exige DB por worker. Se houver flake, rodar os CT-B em série e deixar o `--parallel --tia` para o backend.

E um ponto que a doc faz questão de deixar claro — replay **não** é atalho: *"each cached test stores everything it produced, including the exact lines and branches it covered, so a replayed run reports the same code coverage as a full run"*. É o que autoriza usar `--tia` na Verificação Final sem perder confiança.

Na skill, o TIA muda duas coisas:

- **Durante a implementação**: `--parallel --tia` a cada passo concluído do PRD. Rodar o suite **a cada passo** passa a ser viável, em vez de só no final.
- **Na verificação da seção `## Impacto em Features Existentes` do PRD**: essa seção sempre foi especulativa — "o que pode quebrar". O TIA responde com fatos quais testes o diff realmente afetou. Divergência entre o previsto e o medido vira "Desvios do Plano" no `03-progresso.md`.

Ativação sem flag, no `tests/Pest.php`:

```php
pest()->tia()->locally();   // liga local, desliga sozinho em CI

// mapeia assets de browser para os CT-B
pest()->tia()->watch([
    'public/build/**/*' => 'tests/Browser',
]);
```

> ⚠️ **Nunca** colocar `--tia` no comando que roda o suite em CI — a doc do Pest é explícita: o pipeline roda o suite completo. TIA em CI existe só num job dedicado de baseline (`--tia --coverage --fresh`).

**2. Agent plugin (`--agent`)**

Executa um snippet PHP dentro da configuração real do Pest e devolve pass/fail definitivo — em vez de o agente de IA *achar* que funcionou:

```bash
vendor/bin/pest --agent='visit("/contato")->type("email", "a@b.com")->press("Enviar")->assertSee("Mensagem enviada");'
```

Regras: aspas simples no snippet, aspas duplas nas strings PHP, classes sempre com FQN (`\App\Models\User`). **Não substitui CT** — o `--agent` é efêmero e não fica versionado. Verificação que se mostre valiosa deve virar CT no `04` ou no `05`.

**3. Recursos pontuais**

`--profile` (investigar CT lento), `--type-coverage` (features com muito DTO/enum), `--mutate` (regra de negócio crítica: cálculo, cobrança), sharding por tempo real (`--update-shards` / `--shard=1/4`) e os novos matchers `toBeEmail()`, `toBeUlid()`, `toBeIpAddress()`, `toBeMacAddress()`, `toBeHostname()`, `toBeDomain()`, `toBeBase64()`, `toBeHexadecimal()` — que substituem regex custom nos CTs.

### Escrita dos CT-B: loop com sub-agente como auditoria

Os CT-B são o único ponto da wiki onde o teste **é** o instrumento de auditoria — ele executa o que o PRD desenhou contra a UI que existe de fato. Por isso a skill delega a escrita deles a um **sub-agente em loop**, em vez de escrever inline:

1. **Ruído**: falha de browser despeja HTML, snapshot, stack do Playwright e path de screenshot. O sub-agente absorve isso e devolve só o veredito — o contexto principal fica limpo (e o Caveman `ultra` continua fazendo sentido).
2. **Iteração**: acertar seletor e timing de UI é tentativa e erro. Loop isolado, no máximo 3 iterações.
3. **Independência**: quem escreve o CT-B a partir do `05` não deve ser quem escreveu a implementação — reduz o viés de "testar o que eu fiz" em vez de "testar o que foi especificado".

O contrato passado ao sub-agente tem uma proibição central:

```text
PROIBIDO:
  - Alterar código de aplicação para o teste passar
  - Relaxar assertion para "ficar verde"
  - Remover CT-B que não passou
```

E a classificação obrigatória de cada falha:

| Causa | O que fazer |
|---|---|
| (a) CT-B especificado errado (seletor/rota/texto) | corrigir o CT-B no arquivo `05` |
| (b) **Implementação divergente do PRD** | **não corrigir** — registrar divergência |
| (c) Flake (timing/assíncrono) | ajustar estratégia de espera e anotar |

**Teste vermelho por causa (b) é resultado válido, não falha do ciclo** — é exatamente a divergência entre desenhado e implementado que se queria capturar. Sub-agente que "conserta" a aplicação para ficar verde destrói o instrumento de medição. Após 3 iterações com vermelho, para e reporta como blocker.

### Playwright MCP na validação: quem atesta × quem observa

Uma dúvida legítima: se o `pest-plugin-browser` já roda Playwright, o Playwright MCP entra nessa história? **Sim, mas num papel bem estreito.** A regra da skill:

> **O `pest-plugin-browser` atesta. O Playwright MCP observa.**
>
> O CT-B é sempre um teste Pest versionado. O MCP nunca produz cobertura, nunca entra no arquivo `05` como evidência e nunca substitui um CT-B — ele existe para o agente **ver** a página quando o teste falha.

#### Por que o pest-plugin-browser não resolve sozinho

O plugin tem ferramentas de debug excelentes — e **todas exigem um humano na frente**:

| Ferramenta do plugin | O que faz | Serve para agente autônomo? |
|---|---|---|
| `$page->debug()` | abre o browser e **pausa o teste** | ❌ pausa esperando pessoa — o agente travaria |
| `$page->tinker()` | abre sessão Tinker interativa | ❌ interativo |
| `$page->waitForKey()` | abre no browser e espera tecla | ❌ espera input humano |
| `--headed` | mostra a janela do navegador | ❌ só serve se alguém estiver olhando |
| `$page->screenshot()` | salva PNG | ⚠️ funciona, mas é imagem: caro e impreciso para achar seletor |
| `$page->content()` | devolve o HTML da página | ⚠️ funciona, mas despeja a página inteira no contexto |

Para **rodar e atestar**, o plugin basta e é o único caminho. Para o agente **investigar sozinho** por que o seletor não casou, as opções nativas são um PNG ou um dump de HTML. É aí que o MCP ganha: `browser_snapshot` devolve a árvore de acessibilidade (~200–400 tokens de texto estruturado, contra ~3.000–5.000 de um screenshot) e `browser_generate_locator` converte o elemento observado em locator estável.

Três lacunas concretas: **descobrir o locator verdadeiro** numa falha de seletor; **observar quando o elemento realmente aparece** em UI assíncrona (o plugin não documenta `waitFor(seletor)`, só `wait(segundos)` — sem observar, o agente chuta o tempo); e **extrair seletores de tela existente** antes de escrever o CT-B.

#### Os 3 pontos onde o MCP entra (todos opcionais)

| Etapa | Uso | Tools |
|---|---|---|
| **Step 3** — pesquisa | extrair locators reais das telas que a feature vai tocar → alimenta a tabela `### Seletores` do arquivo `05` | `browser_navigate`, `browser_find`, `browser_generate_locator` |
| **Loop do CT-B** — falha de tipo (a) ou (c) | observar a página ao vivo, achar locator/estado real, corrigir o CT-B | `browser_find`, `browser_generate_locator`, `browser_wait_for` |
| **Step 7** — evidência | anexar console e rede ao roteiro *Desenhado × Implementado* | `browser_console_messages`, `browser_network_requests` |

Na falha de **tipo (b)** — implementação divergente do PRD — o MCP é **só leitura**: serve para descrever a divergência com precisão, nunca para contorná-la. A divergência é o achado da auditoria.

> Para o step 7, verifique primeiro o **`Browser Logs`** do Boost MCP ("read logs and errors from the browser"): é uma tool que o projeto provavelmente já tem, sem adicionar servidor novo.

#### Configuração obrigatória

```json
{
  "mcpServers": {
    "playwright": {
      "command": "npx",
      "args": ["@playwright/mcp@latest",
               "--isolated", "--headless",
               "--caps=testing",
               "--test-id-attribute=data-testid"]
    }
  }
}
```

**`--isolated` não é opcional.** O default do Playwright MCP é **perfil persistente**: o login sobrevive entre sessões e, combinado a uma URL errada, o agente pode clicar em produção autenticado. `--caps=testing` habilita `browser_generate_locator` e os `browser_verify_*` — é o único grupo que interessa. E só `localhost`/`APP_URL` de desenvolvimento; apontar para staging ou produção é proibido pela skill.

#### Regras de uso

1. **Ref nunca entra em teste.** `ref=e5` é válido *"until the next page change"* — efêmero. Só o resultado de `browser_generate_locator` vai para o CT-B.
2. **`browser_find` antes de `browser_snapshot` cru** — snapshot em loop acumula contexto; `find` devolve só o trecho.
3. **Proibido `browser_run_code_unsafe`** — se o cenário exige, ele exige um CT-B.
4. **Proibido `--caps=vision`** — clique por coordenada XY destrói o determinismo.
5. **Sessão MCP não é cobertura** — nada de "validado via MCP" no `05` sem CT-B correspondente. Falsa cobertura é pior que cobertura ausente.

#### Se o MCP não estiver configurado

**A skill funciona sem ele** — MCP é aceleração, não dependência. Fallback dentro do próprio plugin, na ordem: `screenshot()` no ponto da falha → `content()` **filtrado** com `Grep` (não despejar tudo no contexto) → ler o Blade/componente e derivar o seletor do código-fonte → após 3 iterações, escalar ao usuário com screenshot e sugerir `--headed` para inspeção humana.

### Limitações conhecidas (documentadas na skill)

Levantadas na análise da doc oficial do plugin, para o agente não inventar API:

- **Login**: a doc demonstra autenticação **pela própria UI** (factory + preencher formulário + `press`) e `$this->assertAuthenticated()`. `actingAs()` em teste de browser **não** está documentado — a skill manda herdar o padrão real do projeto em vez de assumir.
- **Espera assíncrona**: o plugin expõe `wait(segundos)`; não há `waitFor(seletor)` documentado. Para Livewire/Filament, a skill manda preferir assertion sobre o estado final visível a `wait()` fixo, e registrar a estratégia escolhida no CT-B.
- **Servidor**: a doc não explicita se o plugin sobe o app ou exige servidor externo. A skill obriga a registrar no `05` como o projeto serve o app em teste.
- **Boost**: as guidelines e a Documentation API do Laravel Boost cobrem Pest `core, 3.x, 4.x`. Se o projeto está em Pest 5, o `search-docs` pode devolver informação de versão anterior — confirmar na doc oficial.

### Estrutura resultante

```text
wikis/specs/ferro/579/relatorio-mba-lote/
├── 01-plano-acao.md                 ← + seção ## Superfície de UI (gate do CT-B)
├── 02-decisoes-arquiteturais.md
├── 03-progresso.md                  ← + checkboxes de CT-B
├── 04-casos-de-teste.md             ← backend: Feature/Unit, log, autorização
└── 05-casos-de-teste-browser.md     ← CT-B + roteiro Desenhado × Implementado
```

---
## 🔎 Documentation API do Boost (`search-docs`)

O Boost expõe a tool MCP **`search-docs`**, que consulta a Documentation API hospedada da Laravel — **17.000+ trechos** com busca semântica por embeddings, **filtrada pelos pacotes que o projeto realmente tem instalados**. A skill trata isso como fonte primária, antes de vendor source e antes de doc na web.

### Cobertura oficial

| Stack | Versões cobertas |
|---|---|
| Laravel Framework | 10.x, 11.x, 12.x, **13.x** |
| Filament | 2.x, 3.x, 4.x, **5.x** |
| Livewire | 1.x, 2.x, 3.x, **4.x** |
| Inertia | 1.x, 2.x |
| Flux UI | 2.x Free, 2.x Pro |
| Nova | 4.x, 5.x |
| Pest | 3.x, **4.x** |
| Tailwind CSS | 3.x, 4.x |

### Na `feature-wiki`: obrigatório antes de escrever o PRD

O step 3 (Pesquisa e Contexto) ganhou uma seção dedicada, com um mapa do que consultar para cada trecho do plano:

| O que vai escrever no PRD | Consultar `search-docs` sobre |
|---|---|
| Rotas, middleware, policies, validação | Laravel Framework (versão do projeto) |
| Componente de UI, tabela, form, modal | Filament / Livewire / Flux |
| Jobs, queues, batching, scheduling | Laravel Framework — queues |
| CTs do arquivo `04` | Pest — expectations, mocking, datasets |
| CT-B do arquivo `05` | Livewire/Filament (comportamento assíncrono) + Pest browser |
| Broadcasting, eventos, Reverb/Echo | Laravel Framework |

**Como consultar bem**: uma pergunta específica por consulta (*"Filament 5 table bulk action confirmation modal"* vence *"Filament tabelas"*); citar a versão do pacote; **confirmar no código antes de escrever no PRD** — a doc diz o que a API oferece, o `Grep` diz o que o **seu** projeto faz, e divergência entre os dois vira ADR; e citar a origem no plano (*"conforme doc do Filament 5 (search-docs)"*) para dar rastreabilidade e evitar re-pesquisa na próxima wiki.

### Lacunas — e o fallback de cada uma

| Stack | Situação | Fallback |
|---|---|---|
| **Pest 5** | a API cobre até **4.x** | `pestphp.com/docs` — `--tia`, `--agent`, sharding e os matchers novos **não** estão no `search-docs` |
| **Playwright / `pest-plugin-browser`** | não coberto | `pestphp.com/docs/browser-testing` + `playwright.dev` |
| Pacotes de terceiros | não coberto | vendor source (`Read vendor/{vendor}/{pkg}/src/...`), já obrigatório no step 3 |
| Código da sua aplicação | não coberto por design | `Grep`/`Read` + `.ai/rules/` do projeto |

Isso é importante justamente porque a skill agora **recomenda** Pest 5: consultar `search-docs` sobre `--tia` devolveria informação de Pest 4. A skill declara essa lacuna em vez de deixar o agente confiar numa resposta desatualizada.
