# Relatório de QA — Estudo e adoção de pacotes Filament, rodada 2

> Requisito: `00-requisito.md` (Texto Original + **Adendo 1** + **Adendo 2**) · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** — UI com JS (`beforeunload`), fronteira de privacidade (avatar) e de auditoria
> Natureza da wiki: `nova` · Regressão: **sim** — o `01` declara `Toca infra compartilhada? sim`, o que a força mesmo em wiki nova
> Executado por quem **não** implementou a feature.

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 1 · Major: 3 · Minor: 4 · Cosmético: 1
- Ambiente: app **não servido** em `APP_URL`; os cenários dinâmicos rodaram pelo kernel do Pest e
  pelo servidor in-process do `pest-plugin-browser`. PHP 8.4.25 · PHPUnit 13.3 · MCP **indisponível**.
- Medido nesta execução: `Kit,Tenancy --parallel` **2446/2446** (9577 asserções) ·
  `--testsuite=Browser` **75 casos, 61 verdes, 14 pulados, 0 falhas** (CT-B01 e CT-B02 rodados
  isolados também passam, 2/2) · os 8 arquivos da feature **181/181**.
- Vai para **especificação** porque o `01` ainda descreve a feature anterior ao Adendo 2 (QA-02):
  corrigir código contra plano desatualizado é retrabalho garantido.

---

## Achados

### QA-01 — RQ-15 não foi entregue: o Filament não foi atualizado · **Blocker** · destino 2

- **Dimensão** A · **Relacionado a** RQ-15, RQ-14, `07-dossies` §1
- **Esperado**: `00 › Adendo 1`, RQ-15 — *"se sair atualização, atualize no starter-kit"*. O gatilho disparou.
- **Observado**: kit em `filament/filament v5.7.6`; série publicada **5.8.2**. `composer.json` e
  `composer.lock` **intocados** no branch. O `03` registra isso como resultado desejado
  (*"composer.json intocado — é o resultado central da rodada"*), o que vale para "nenhum pacote
  novo", não para "não atualizar o que já está lá".
- **Repro**: (1) `composer outdated "filament/*"` → `filament/filament 5.7.6 ! 5.8.2`;
  (2) `git diff --stat main...HEAD -- composer.json composer.lock` → vazio.
- **Contradição interna que confirma**: `00 › Adendo 1 › Consequência` raciocina a partir de
  *"Atualizado o Filament, esse motivo cai"*, enquanto `07-dossies:80` escreve o gatilho de
  reabertura do `page-header` como *"o kit **já** em Filament 5.8+"* — trata como futuro o que
  RQ-15 manda fazer agora. O veredito ADIAR segue correto (os outros dois motivos bastam), mas a
  cadeia se apoia num estado que não existe.
- **Ação exigida**: ou `composer update` da série 5 com regressão completa, ou Adendo no `00`
  registrando RQ-15 como compromisso operacional fora desta entrega. Hoje não há nem um nem outro.

### QA-02 — O `01-plano-acao.md` nunca foi reconciliado com o Adendo 2 · **Major** · destino 1

- **Dimensão** L3 · **Relacionado a** RQ-02, RQ-13…RQ-18, passo 3
- **Esperado**: o `03` afirma *"passo 3 reescrito; ADR-04 reescrita"*. A ADR-04 foi; o `01` não.
- **Observado**, no `01` de hoje:
  - `## Cobertura do Requisito` cobre só RQ-01…RQ-12 — **sem linha para RQ-13…RQ-18**, as seis
    cláusulas dos Adendos, que são as que compram a entrega
  - `:23` ainda dá RQ-02 por *"atendido sob premissa: a tag já é `config('kit.version')`"* — a
    premissa que o Adendo 2 declara errada
  - `:321` chama-se *"Versão do kit no rodapé"*; `:323` usa `v{{ config('kit.version') }}`;
    `:338` afirma *"Fonte da versão: `config('kit.version')`, **única**"*. O código usa
    `config('app.version')` (`versao-do-kit.blade.php:58`)
  - `:145` afirma *"Nenhuma chave nova para a versão nem para o avatar"* — `APP_VERSION` e
    `KIT_EXIBIR_VERSAO` entraram no `.env.example` e no `phpunit.xml`
  - `## Superfície de UI` não lista o `TextInput versao_do_sistema` nem o `Toggle exibir_versao_do_kit`
- **Repro**: `grep -n "APP_VERSION\|versao_do_sistema\|RQ-17" .../01-plano-acao.md` → zero;
  `grep -n "kit.version" .../01-plano-acao.md` → `:23`, `:164`, `:323`, `:338`.
- **Ação exigida**: reescrever passo 3, tabela de cobertura, variáveis de ambiente e superfície de
  UI do `01`, com marca `*(alterado em 2026-09-18 — Adendo 2)*`.

### QA-03 — O código novo documenta a versão errada · **Major** · destino 1

- **Dimensão** L3 · **Relacionado a** RQ-17 e ao **item 5 da própria entrega**
- **Observado**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php`, docblock de
  `configuraVersaoNoRodape()`: *"A versão **do kit** no rodapé"* e *"A fonte é
  `config('kit.version')` … **Uma fonte só**"*. A fonte é `config('app.version')`, e são **duas**
  chaves com papéis distintos. O mesmo texto encabeça o bloco `.kit-versao` em
  `resources/css/filament/kit.css` e `public/css/kit/kit-correcoes.css`.
- **Repro**: comparar os três arquivos com `versao-do-kit.blade.php:58`.
- **Por que Major**: é exatamente o defeito que o item 5 desta entrega existe para corrigir —
  comentário que justifica decisão com afirmação que o código desmente. A blade nova está certa; o
  provider e a CSS, que é onde a próxima pessoa chega primeiro, dizem o contrário.
- **Ação exigida**: atualizar os três blocos. Nenhuma linha de comportamento muda.

### QA-04 — RQ-16 apoia-se num mecanismo que não faz o que o `00` diz · **Major** · destino 1

- **Dimensão** A / L3 · **Relacionado a** RQ-16, `04 › Perguntas nº 6`, `04 › Lacuna L4`
- **Esperado**: o `00 › Adendo 1` declara RQ-16 atendida por **dois** mecanismos existentes: a
  constraint em caret herdada e `php artisan kit:update`, *"o mecanismo do kit para propagar mudança"*.
- **Observado**:
  - Mecanismo 1 é real e coberto: `composer.json:34` = `"filament/filament": "^5.6"`, fixado por CT-32. ✅
  - Mecanismo 2 **não propaga**: `KitUpdate.php:299-306` lista `composer.json` entre os arquivos que
    o comando **nunca** sobrescreve, e `:960-970` só emite aviso (*"este arquivo NUNCA é aplicado
    automaticamente"*) com instrução manual. É notificação, não propagação.
  - Somado a QA-01, o efeito prático é nulo: não há atualização a propagar.
  - RQ-16 não tem linha no `## Cobertura do Requisito`, não tem CT e não tem código. O `04` a
    declara lacuna e a pergunta nº 6 segue **aberta**.
- **Repro**: `sed -n '295,310p;955,972p' app/Console/Commands/KitUpdate.php`.
- **Veredito sobre a legitimidade** (era a pergunta): **parcialmente legítima**. Não é omissão
  silenciosa — está declarada em três artefatos com premissa e reabertura. Mas a premissa sobre o
  `kit:update` é contradita pelo código, e a cláusula nunca virou passo.
- **Ação exigida**: responder a pergunta nº 6 com o solicitante (o `kit:update` deve propagar,
  avisar, ou nada) e corrigir a premissa do `00` por Adendo.

### QA-05 — Citações `arquivo:símbolo:linha` erradas · **Minor** · destino 1

- **Dimensão** L2 · O commit `7594c5a` afirma *"citacoes reverificadas"*.
- **Observado**: `User.php:841-845` é citada como `getFilamentAvatarUrl()` em
  `app/Support/AvatarDeIniciais.php:26`, `01:89`, `01:256` e `02:136`. O método está em
  **`:857-862`**; `:841-845` é log de personificação recusada. E `01`, passo 4, cita o `forceFill`
  de `desativar()` em `:291`; a linha é **`:307`** — que o próprio `01 › Análise dos Arquivos` já diz.
- **Repro**: `sed -n '841,845p;857,862p;289,293p;307p' app/Models/User.php`.
- **Nota**: reconferi uma a uma as 9 citações de `vendor/` que sustentam decisão
  (`HasAvatars:10`, `UiAvatarsProvider:15-29`, `HasUnsavedChangesAlerts:9` e `:18-21`,
  `user-menu.blade:43`/`:40`/`:97`, `simple.blade:58`, `index.blade:122`, `ColorManager:104-107`) —
  **todas conferem**. O defeito está só nas citações de `app/`.

### QA-06 — Referências cruzadas de ADR trocadas · **Minor** · destino 1

- **Dimensão** L3
- `00 › Ambiguidades › RQ-03/RQ-04` e `01:25` mandam ler **ADR-03** para a reabertura do
  `yousefaman/filament-autosave`; ADR-03 é a do avatar e o caminho está em **ADR-02** (`02:78-82`).
- `01:145` manda ler *"ADR-04 e ADR-05"* para "nenhuma chave nova … para o avatar"; a do avatar é **ADR-03**.
- `01:31` diz que o registro dos sub-agentes (RQ-06) está no `03`; está em `07-dossies:6`.
- **Ação exigida**: corrigir as três no `01`; a do `00`, só por Adendo.

### QA-07 — As dez perguntas do `04` nunca chegaram ao `00` · **Minor** · destino 1

- **Dimensão** A / L5
- `04 › ## Perguntas para o 00-requisito` abre com *"Bloco pronto para colagem"*, e a nº 1 pede
  *"Confirmar como **Adendo 3**"*. O `00` tem só os Adendos 1 e 2, e sua seção `## Ambiguidades`
  não contém nenhuma das dez. Em particular:
  - **nº 1** — o avatar de iniciais, maior mudança de privacidade da entrega, **não tem cláusula
    própria**: entra só por RQ-13, e a lista dos cinco vive no `01`/`02`, não no `00`. Pela matriz,
    isto é *código sem RQ*.
  - **nº 9** — o `04` reconhece que o invariante de CT-01 só é verificável casando rótulo e que a
    pergunta nº 8 proíbe casar rótulo; escreve *"não há terceira saída"*, e segue sem saída.
- **Mitigação, e por isso Minor**: todas têm premissa adotada, declarada, com direção de falha
  fechado e "Se negado" escrito. Não é suposição escondida — é suposição que o dono nunca viu.

### QA-08 — Verificação Final do `03` aberta · **Minor** · destino 2

- **Dimensão** L4
- Esta execução fechou por medição própria `test:browser`, "Custo medido" e a regressão. Continuam
  abertos o ciclo `bp:on` → aderência → `bp:off` (RQ-11) e o `ponytail-review`.
- RQ-11 está **parcial**: `tests/Kit/AderenciaAoBlueprintTest.php` roda verde na suíte (é varredura
  textual, não precisa do pacote), mas o `filament/blueprint` nunca rodou sobre este diff.
- "Citações reverificadas" está marcada como feita e é desmentida por QA-05.

### QA-09 — Nome do arquivo da blade descreve a versão errada · **Cosmético** · destino 1

`resources/views/filament/versao-do-kit.blade.php` renderiza primariamente a versão do **sistema**.
Nome do desenho pré-Adendo 2. Renomear arrasta `ConfiguraFilamentGlobal` e a CSS.

---

## Matriz de Rastreabilidade

| RQ | Cláusula (resumo) | Passo | CT | CT-B | Código | Resultado |
|----|---|---|---|---|---|---|
| RQ-01 | análise dos 10 pacotes | 6 | CT-33 | — | `07-dossies` | ✅ |
| RQ-02 | versão pela tag/branch | — | — | — | — | ⬛ **substituída por RQ-17**; `01:23` ainda a dá por atendida → QA-02 |
| RQ-03 | rascunho por form no Settings | 2 | CT-14…17 | CT-B01 | `unsavedChangesAlerts` | ⚠️ parcial **declarado**: governo global, não por form |
| RQ-04 | autosave, prós e contras | 6 | CT-33 | — | ADR-02 | ✅ (referência errada → QA-06) |
| RQ-05 | OpenAPI automático | — | — | — | — | ✅ **fora de escopo legítimo** (ver abaixo) |
| RQ-06 | sub-agentes em paralelo | — | — | — | `07-dossies:6` | ✅ (o `01` aponta artefato errado → QA-06) |
| RQ-07 | formato dos estudos anteriores | 6 | CT-33 | — | `pacotes-candidatos.md` | ✅ |
| RQ-08 | proposta + implementar | 1–5 | CT-34 | — | os 5 nativos | ✅ |
| RQ-09 | branch/worktree | — | — | — | ADR-01 | ✅ |
| RQ-10 | `/code-review` | 8 | — | — | `03 › step 7.5` | ✅ (3 achados conferidos abaixo) |
| RQ-11 | usar o Blueprint | 7 | — | — | `AderenciaAoBlueprintTest` | ⚠️ parcial → QA-08 |
| RQ-12 | do início ao fim | 1–9 | — | — | — | ⚠️ pendente do que este relatório abre |
| RQ-13 | implementar os 5 nativos | **sem linha** | CT-14…31, 35 | CT-B01, B02 | 5 itens | ✅ no código, ❌ na rastreabilidade → QA-02 |
| RQ-14 | não travar o Filament | **sem linha** | CT-32 | — | `composer.json:34` `^5.6` | ✅ |
| RQ-15 | atualizar quando sair versão | **sem linha** | — | — | **nenhum** | ❌ **não entregue** → QA-01 |
| RQ-16 | projetos que usam recebem | **sem linha** | CT-32 (só mec. 1) | — | constraint | ⚠️ meia-legítima → QA-04 |
| RQ-17 | rodapé mostra a versão do SISTEMA | **sem linha** | CT-01…06, 10…13, 37, 38, 41, 44 | — | blade, `config/app.php:42` | ✅ no código |
| RQ-18 | versão do kit opcional | **sem linha** | CT-07…09, 43 | — | `kit.exibir_versao`, Toggle | ✅ no código |

**Sem RQ**: `aprovacao_pendente` (Desvio 2 do `03`, ADR-05 ✅) · README 57→58 (Desvio 4 ✅) · o
**avatar de iniciais**, que só se liga ao `00` por RQ-13 → QA-07.

**RQ-05 — declaração legítima.** Confirmei o pré-requisito ausente em vez de aceitar a afirmação:
`routes/` só tem `console.php` e `web.php`; `bootstrap/app.php:9-13` declara `withRouting()` com
`web`, `commands` e `health` e **sem** `api:`; não há `sanctum` nem `passport`. A exclusão está em
três lugares, e ADR-06 registra nove alternativas, o caminho escolhido e o gatilho de reabertura.
Não é omissão disfarçada.

**RQ-16 — não se sustenta inteira.** Ver QA-04.

---

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ❌ | QA-01, QA-04, QA-07 |
| B | Fronteiras e dados | ✅ | 12 partições sondadas no avatar (vazio, só espaços, 1 char, emoji, acento, hífen, 800 chars, `<script>`, aspas, RTL, CJK, NBSP): todas produzem XML válido por `simplexml_load_string`, nenhuma lança, tamanho ≤ ~304 bytes |
| C | Matriz de permissão | ✅ | Nenhuma entidade Shield nova. Única tela de escrita tocada tem CT-11 (não abre) e CT-41 (não grava) para persona sem permissão |
| D | Observabilidade real | ✅ | `01` decide "nenhum log novo" com justificativa por passo, e o diff confirma: zero `Log::`/`logger()` novo. **Sem PII**: o log existente de `User@desativar` usa `{$this->id}` e `Str::mask($this->email)` |
| E | Performance | ✅ | **Custo medido**: `AvatarDeIniciais::get()` = 0 query, 426 bytes; `getNameForDefaultAvatar()` não consulta; rodapé só lê `config()`. Bate com o `## Modelo de Execução`, e o avatar **remove** 1 requisição externa por usuário sem foto |
| F | UX de erro | ✅ | Campo vazio é estado legítimo documentado, não erro. `helperText` em português explicando a precedência banco × `.env` |
| G | Tema e cor | ⚠️ | `.kit-versao` **não declara cor** de propósito — herda do tema e aplica só `opacity`, dispensando o par `dark:`. Avatar `#09090b` sobre `#FFFFFF` (~19:1), fixo e independente da paleta da organização. Nível visual não rodou |
| H | Acessibilidade | ⚠️ | Nenhum CT-B novo chama `assertNoAccessibilityIssues()`, e o rodapé não é exercitado por nenhum. Débito, não achado (some-se à DT-12) |
| I | Segurança da superfície nova | ✅ | Nenhuma rota nova; nada recebe id de terceiro (sem IDOR possível); nenhuma propriedade pública Livewire nova. Guard de visitante conferido — `filament()->auth()->check()` e não `@auth`, e o hook `FOOTER` é de fato emitido pelos dois layouts (`index.blade:122`, `simple.blade:58`), com CT-05/06/37. Versão escapada por `{{ }}` (CT-04). `app.version` não é lido por nenhum pacote do `vendor/` — o mapeamento não sequestra chave alheia |
| J | Regressão adjacente | ✅ | 2446/2446 em `Kit,Tenancy`; 61/14/0 em `Browser`. Nada em `TelasDeAutenticacaoTest` quebrou com o rodapé — era o risco nomeado no `01` |
| K | Adequação da suíte | ⚠️ | **Estático**: 7 arquivos varridos, nenhum caso sem asserção, nenhum `assertOk()`/`assertNoJavaScriptErrors()` como oráculo único, nenhum `assertDatabaseHas` só com PK. Os CT-B são exemplares — CT-B01 tem linha de base antes do oráculo, CT-B02 tem gate de mundo vazio antes da asserção de ausência. **Mutação não rodou** (sem driver) |
| L | Consistência documental | ❌ | QA-02, QA-03, QA-05, QA-06. L1 em estado transitório — abaixo |

**L1, medida durante a reconciliação (declarado).** Medida às **18:39:46** com o `04` tendo `mtime`
**18:39:36** — outro agente o reescrevia no mesmo minuto. O número **vai mudar** e não é achado
neste ciclo. Instante: 66 IDs nos testes, 47 no `04`+`05`; **nenhum ID do `04`/`05` sem teste** (a
direção pior está limpa) e **19 só no teste** (`CT-46`…`57`, `CT-59`…`65`). Às 18:20 o retrato era
outro (faltavam CT-09, CT-25, CT-36; `[CT-38]` rotulava três cenários distintos). Reconferir no ciclo 2.

**Os 3 achados do `/code-review` — conferidos, não recontados.** Os três procedem e estão fechados:
`ENT_SUBSTITUTE` presente com CT em `AvatarDeIniciaisTest.php:289`; dataset com o painel `app` em
`VersaoNoRodapeTest.php:264`; correção documental da precedência nas docs pt **e** en, no
`helperText` e em `config/app.php:29-34`, com CTs em `AlertaDeAlteracoesNaoSalvasTest.php:252` e
`:267`. Concordo com corrigir a documentação e não o comportamento: a precedência é regra universal
do kit, e abrir exceção para uma chave seria a inconsistência invisível.

## Débitos Aceitos

- QA-09 (Cosmético): nome do arquivo da blade.
- Dimensão H: rodapé novo sem `assertNoAccessibilityIssues()`.
- Pergunta nº 10 do `04`: barreira separada de leitura e de escrita na tela de configurações.

## Suspeitas Não Confirmadas

Nenhuma. As duas curiosidades da sondagem de fronteiras — U+00A0 não separa segmentos do nome, e
emoji conta como inicial — reproduzem, mas são **destino 5**: o `UiAvatarsProvider` do vendor faz o
mesmo `explode(' ')`, e a entrega declara que a aparência não deve mudar.

## Não Verificado

- **Mutation score (K, passo 2)**: sem PCOV e sem Xdebug (`php -m`); o próprio Pest avisa
  *"TIA is skipped as it needs ext-pcov or Xdebug"*. Sem piso de 70% medido em `app/Support` e
  `app/Traits`. **Custo**: oráculo fraco que passe no crivo estático não seria pego.
- **Playwright MCP e Boost MCP**: `laravel-boost` fechou a conexão (`CONNECTION_CLOSED`), então
  `search-docs` e `browser-logs` não existiram; Playwright MCP não está configurado. **Custo
  concreto**: sem `browser_snapshot` não houve o confronto "elementos interativos da tela ×
  exercitados pelos CT-B", que é a única medição de lacuna de cobertura de UI da skill; sem
  screenshot nos dois temas, G ficou no nível estático — e para defeito de cor a árvore de
  acessibilidade é cega; sem `browser_network_requests`, 4xx/5xx engolidos em silêncio só
  apareceriam pelo que CT-B02 já assere.
- **App servido**: `curl http://localhost:8000/admin/login` sem resposta. Nada rodou contra
  instância real — suficiente para o afirmado aqui, mas não substitui olhar a tela.
- **RQ-15 como processo**: não consegui verificar se o solicitante a considera compromisso contínuo
  fora desta entrega. A pergunta nº 6 do `04` pede isso e segue sem resposta; QA-01 assume a
  leitura literal do `00`, que é o oráculo.
- **L1**: estado transitório, por decisão declarada.

---

# Ciclo 2 — 2026-09-18

> Requisito: `00-requisito.md` (Texto Original + **Adendos 1, 2 e 3**) · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** · Natureza: `nova` · Regressão: **sim**
> Executado por quem **não** implementou a feature nem executou as correções do ciclo 1.
> Deduplicado contra o **Ciclo 1 deste arquivo**, não contra os achados corrigidos.

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 5 · Minor: 4 · Cosmético: 0
- Ambiente: app **não servido**; PHP 8.4 sem PCOV/Xdebug; MCP **indisponível** (`laravel-boost`
  `CONNECTION_CLOSED`, Playwright não configurado).
- Medido nesta execução, **já com o Filament v5.8.2**: `Kit,Tenancy --parallel` **2461/2461**
  (9600 asserções, 0 falhas) · os 8 arquivos da feature **196/196** (1977 asserções).
- Vai para **especificação** porque os cinco Majors são texto contra código (L1/L2/L3/L4) e
  porque **três deles foram criados pela correção do Blocker do ciclo 1**: subir o Filament
  moveu o `vendor/` sob as citações e sob a paridade de comportamento que a entrega declara.

**O Blocker do ciclo 1 está fechado** (v5.7.6 → v5.8.2, constraint `^5.6` intocada, ADR-08,
passo 10, CHANGELOG). Nenhum Blocker novo.

---

## Achados

### QA-10 — A correção de QA-01 invalidou as citações de vendor, em produção e na wiki · **Major** · destino 1

- **Dimensão** L2 · **Relacionado a** QA-01, QA-05, RQ-14/RQ-15, `.ai/rules/specs.md`
- **Esperado**: `.ai/rules/specs.md:9` — *"abra o arquivo do `vendor/` e cite `file:line`"*. O
  ciclo 1 reconferiu essas nove citações uma a uma e escreveu *"todas conferem"* — **em v5.7.6**.
- **Observado** em v5.8.2 (medido agora, uma a uma):

  | Citada | Real hoje | Onde a citação vive |
  |---|---|---|
  | `user-menu.blade.php:43` (USER_MENU_BEFORE) | **:43** | `AdminPanelProvider:342`, `AppPanelProvider:495`, `InfraPanelProvider:625`, `01:395`, `01:409`, `03:155`, `04:1419`, `04:1776` |
  | `user-menu.blade.php:40` (`<x-filament::dropdown`) | **:45** | `03:155` |
  | `user-menu.blade.php:97`/`:105`/`:128`/`:143` | **:97/:110/:133/:148** | `AdminPanelProvider:356`, `AppPanelProvider:509`, `InfraPanelProvider:639`, `user-menu-header.blade:6`, `01:409`, `03:155` |
  | `layout/simple.blade.php:61` (FOOTER) | **:61** | `versao-do-kit.blade:39`, `01:182`, `01:329`, `02:256`, `03:269`, `04:372`, `04:459`, `VersaoNoRodapeTest:54` |
  | `layout/index.blade.php:126` (FOOTER) | **:126** | `versao-do-kit.blade:38`, `03:269`, `04:372`, `04:460`, `VersaoNoRodapeTest:53` |
  | `UiAvatarsProvider.php:27` (expressão de cor) | **:27** | `AvatarDeIniciais:46`, `02` ADR-03 |
  | `UiAvatarsProvider.php:29` (a URL de terceiro) | **:29** | `AvatarDeIniciais:23`, `01:261`, `02` ADR-03 |
  | `UiAvatarsProvider.php:15-21` / `:15-23` | **:15-25** | `AvatarDeIniciais:77`, `06` (ciclo 1) |
  | `page/index.blade.php:162-166` | **:169** | `05:231` |

- **Repro**: `grep -n "FOOTER" vendor/filament/filament/resources/views/components/layout/{simple,index}.blade.php`
  devolve `61` e `126`; `grep -n "USER_MENU" .../user-menu.blade.php` devolve `43`, `97`, `110`, `133`, `148`.
- **Por que Major**: são **5 arquivos de produção** (os três `PanelProvider`, `versao-do-kit.blade.php`,
  `AvatarDeIniciais.php`) e 6 arquivos de wiki. E o CT-35 **previu isto por escrito**
  (`CabecalhoDoMenuDoUsuarioTest.php:231-233`: *"casar `:38` é ruído garantido no próximo upgrade"*)
  — ele se protegeu, os comentários não. A tensão é estrutural: RQ-14/RQ-15 obrigam o kit a aceitar
  toda atualização da série, e `specs.md` obriga toda afirmação sobre vendor a carregar `:linha`.
- **Ação exigida**: reverificar as nove âncoras e corrigir os 11 arquivos. Candidato a rule no
  step 9: citação de vendor carrega **símbolo** e é conferida por `grep`, não por número fixo.

### QA-11 — O `04` descreve um estado que a correção do ciclo 1 desfez · **Major** · destino 1

- **Dimensão** L1 · **Relacionado a** CT-09, CT-46, CT-47, CT-38, Divergência D2
- **Observado**, com o defeito de `config/kit.php` já corrigido e CT-09 já escrito e **verde**:
  - `04:1647` (Índice) e `04:1699` (Reconciliação, Sentido 1): CT-09 = ***"sem teste"*, M11 vivo**.
    Existem **dois** casos `[CT-09]` em `VersaoNoRodapeTest.php:710` e `:731`, verdes.
  - `04:1721` (D2): *"`config/kit.php:250` lê a chave com `(bool) env(...)`"*. Hoje é
    `config/kit.php:263` com `BooleanoDoEnv::comPadrao(...)`.
  - `04:1762`: *"consertados aqui, por falta de mandato: **M11 vivo**"* — M11 está morto.
  - `04:1745` e `04:1810`: *"**2 sem teste** (CT-09 e CT-25)"* — é 1 (CT-25, fundido em CT-23).
  - **CT-46 e CT-47 não estão no Índice de Cenários nem na Reconciliação.** Existem só dentro da
    regra R14 (`04:1297`, `:1305`) e nos testes (`VersaoNoRodapeTest.php:756`, `:793`). O Índice
    tem 47 linhas; deveria ter 49.
  - **`[CT-38]` rotula três casos em dois arquivos**: `VersaoNoRodapeTest` (o canônico, R4) e
    `AlertaDeAlteracoesNaoSalvasTest.php:252` e `:267`. O Índice o dá em um arquivo só, e a
    tabela "Sentido 2" (casos sem CT) não os declara. O ciclo 1 viu isso às 18:20, declarou
    estado transitório e pediu reconferência — **reconferido: não fechou**.
- **Repro**: `grep -rn "\[CT-09\]\|\[CT-38\]\|\[CT-46\]\|\[CT-47\]" tests/Kit/` contra
  `sed -n '1625,1676p' 04-casos-de-teste.md`.
- **Ação exigida**: reescrever Índice, Sentido 1 e D2 do `04` contra a árvore de hoje; dar
  arquivo a CT-46/CT-47; decidir se os dois casos de `AlertaDeAlteracoesNaoSalvasTest` são CT-38
  ou ganham ID próprio.

### QA-12 — QA-02 foi fechado pela metade: o passo 3 e a `## Superfície de UI` seguem pré-Adendo 2 · **Major** · destino 1

- **Dimensão** L3 · **Relacionado a** QA-02, RQ-17, RQ-18, RQ-20
- **Fechado**: `## Cobertura do Requisito` agora cobre RQ-01…**RQ-20**, com RQ-02 marcada como
  substituída. ✅
- **Não fechado**, no `01` de hoje:
  - `:322` — o passo ainda se chama ***"3. Versão do kit no rodapé dos painéis"***.
  - `:331` — *"conteúdo: `v{{ config('kit.version') }}`"*. O código emite
    `config('app.version')` com prefixo `v`, e a do kit só sob interruptor
    (`versao-do-kit.blade.php:58-60`). O bullet *"Fonte da versão"* (`:346`) foi corrigido; o
    bullet que descreve **o que a blade emite** não.
  - `## Superfície de UI` (`:123-131`) — ainda lista *"Rodapé com a versão do kit"* e continua
    **sem** o `TextInput versao_do_sistema` (aba Identidade) e **sem** o
    `Toggle exibir_versao_do_kit` (aba Kit), que existem em
    `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:262` e `:775`. Era item literal da ação
    exigida por QA-02.
  - `## Variáveis de Ambiente` (`:147-153`) — a tabela tem só `KIT_ALERTA_ALTERACOES_NAO_SALVAS`;
    `APP_VERSION` e `KIT_EXIBIR_VERSAO` ficaram em prosa fora da tabela.
  - **RQ-20 → passo 3** na tabela de cobertura, e o passo 3 **não menciona o rótulo** em lugar
    nenhum. É o padrão *"o PRD diz que o passo atende, e o passo não trata disso"*.
- **Repro**: `sed -n '123,153p;322,352p' 01-plano-acao.md` contra `versao-do-kit.blade.php:57-65`.

### QA-13 — O avatar do kit divergiu do provider do vendor na 5.8.2, e ADR-03 ainda afirma paridade · **Minor** · destino 1 (e 3)

- **Dimensão** A / L3 · **Relacionado a** QA-01, RQ-19, ADR-03
- **Esperado**: `AvatarDeIniciais.php:44-49` — *"**Por que a aparência não muda**: o fundo sai da
  MESMA expressão do provider do vendor … quem já usava o kit não vê diferença"*. ADR-03 repete
  (*"aparência idêntica à anterior"*) e é com isso que ela dispensa decisão de estética.
- **Observado**: a v5.8.2 mudou o cálculo das iniciais do `UiAvatarsProvider` — passou a **pular
  pontuação inicial** de cada segmento (`UiAvatarsProvider.php:19-23`). O kit copiou a versão
  5.7.6, que não pulava, e não acompanhou.
- **Repro** (medido, não deduzido — o zip da 5.7.6 ainda está no cache do Composer):
  - 5.7.6: `->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')`
  - 5.8.2: `preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment)` — *"Skip leading punctuation"*
  - nome `[SYSTEM] Admin` → kit: `[A` · vendor 5.8.2: `S A` · vendor 5.7.6: `[A`
- **Por que Minor e não Cosmético**: o dano visível é pequeno (nome que começa com pontuação),
  mas a **classe** é a que importa — a correção de um Blocker mudou em silêncio o vendor que uma
  ADR usa como referência de comportamento, e nenhum CT compara os dois. Nenhum teste ficou
  vermelho.
- **Ação exigida**: decidir se o kit persegue a paridade (então CT novo comparando kit × vendor,
  destino 3) ou se ADR-03 e o docblock passam a declarar a divergência com a data.

### QA-14 — R14: CT-47 ficou com o oráculo fraco que CT-46 perdeu, e CT-46 ganhou um posicional · **Minor** · destino 3

- **Dimensão** K · **Relacionado a** RQ-20, CT-46, CT-47, M64, M65
- **Metade fechada** ✅: CT-46 exige `/\p{L}/u` no trecho entre as duas versões
  (`VersaoNoRodapeTest.php:782`). Sem rótulo o trecho seria `·` e o caso fica vermelho — o
  mutante M63 morre. Confirmado lendo o par blade × asserção.
- **Não fechada — CT-47** (`:793-800`). O oráculo é
  `not->toBe(config('kit.version'))` mais `not->toStartWith('v')`. Ele mata M65 na forma literal
  (`0.34.2` sozinho), mas **admite rodapé sem rótulo nenhum**: `(0.34.2)` ou `· 0.34.2` passam
  nas duas asserções e não têm letra. É exatamente o *"algo entre as duas versões"* que CT-46
  perdeu, sobrevivendo no cenário vizinho. RQ-20 pede *rótulo*, e pontuação não é rótulo.
- **Repro**: `php -r 'var_dump("(0.34.2)" !== "0.34.2", ! str_starts_with("(0.34.2)", "v"));'`
  devolve `true, true` — verde com a garantia de RQ-20 violada.
- **A outra ponta, oposta**: CT-46 fecha com `->and($rodape)->toStartWith('v2.4.1')`, e CT-01
  (`:175`) e a linha `:102` fixam a **string** `'kit '`. O Adendo 3 diz por extenso que *"o texto
  do rótulo **não** é fixado pelo requisito"*, e o próprio docblock de CT-46 declara que não casa
  a palavra `kit`. Renomear o rótulo para `starter ` — permitido por RQ-20 — deixa CT-46/CT-47
  verdes e **CT-01/CT-08 vermelhos**. A suíte afirma mais que o requisito em dois casos e menos
  em um.
- **Ação exigida**: `feature-test-design` com M65 como entrada — a lacuna de derivação é
  *"invariante de forma afirmado num cenário e não no vizinho"*, e ela fecha a classe.

### QA-15 — O `03-progresso.md` não registra a remediação do ciclo 1, e uma linha da tabela de rules virou falsa · **Major** · destino 1

- **Dimensão** L4 / L3 · **Relacionado a** QA-01, D2, passo 10
- **Observado**:
  - O `03` tem seções `## 1.` a `## 7.` e **nenhuma `## 10.`**. O passo 10 existe no `01:439`,
    tem ADR-08 e entrada no CHANGELOG, e não tem uma linha no arquivo que é o tracking único da
    feature. `grep -n "5\.8\.2\|composer update" 03-progresso.md` devolve **só** a linha de
    QA-01 na lista de achados abertos.
  - A correção de `config/kit.php` (`(bool) env()` para `BooleanoDoEnv`) e os CT novos (CT-09,
    CT-46, CT-47) também não têm linha.
  - `## Conformidade com Rules`, linha `config.md`: *"`BooleanoDoEnv::comPadrao()` na chave com
    default `true`; **`(bool) env()` só na de default `false`**, com o motivo escrito"*. Falso
    desde a correção: `config/kit.php:263` usa `BooleanoDoEnv` numa chave de default `false`.
    A declaração do implementador é o que esta dimensão confere, e ela não confere.
  - `## Verificação Final`: `composer test` está `[x]` com **2379/2379** — medição de **antes**
    do bump. O `01:452` define o oráculo do passo 10 como *"`composer test` completo"*, e não há
    medição pós-bump registrada. (Esta execução mediu `Kit,Tenancy` = **2461/2461**.)
  - Seguem `[ ]`: `test:browser`, `--parallel --tia`, custo medido, ciclo do Blueprint (RQ-11),
    citações reverificadas, IDs `[CT-nn]`, `ponytail-review` — é o QA-08 do ciclo 1, **ainda aberto**.
- **Ação exigida**: registrar o passo 10 e a divergência D2 no `03`; corrigir a linha de
  `config.md`; reexecutar e registrar o oráculo do passo 10.

### QA-16 — A premissa nº 2 do `00` segue declarando o oposto do que o produto faz · **Major** · destino 1

- **Dimensão** A / L3 / L5 · **Relacionado a** Divergência D1 do `04`, RQ-20, CT-01 linha 3, CT-47
- **Esperado**: `00 › Perguntas devolvidas › nº 2` — *"**Premissa adotada** (falha fechado…): **o
  rodapé não renderiza nada**"*, com o *Se negado* escrito (*"a linha 3 inverte para `0.34.2`,
  identificada como do kit"*).
- **Observado**: o produto faz o *Se negado*. `versao-do-kit.blade.php:60` monta `$partes` com
  `array_filter` e renderiza o que sobrar, então com a versão do sistema vazia e o toggle ligado
  o rodapé emite `kit 0.34.2`. CT-01 (linha `'só o kit' => [null, true, false, true]`) e CT-47
  **afirmam esse comportamento**. O `04:338` já escreve a linha invertida; o `00`, que é o
  oráculo, não.
- **Contágio na documentação de usuário**: `docs/pt/recursos/configuracoes-do-kit.md` e a en
  dizem *"Campo vazio, rodapé sem versão"* / *"Empty field, no version in the footer"* — falso
  com o interruptor ligado.
- **Por que agora e não no ciclo 1**: o ciclo 1 não reportou D1. E o Adendo 3 foi a oportunidade
  natural de fechá-la: o solicitante respondeu às perguntas **nº 1 e nº 9**, e a **nº 2**, que é a
  única que o código contradiz, ficou de fora. D1 continua roteada para "especificação" no `04`
  desde antes.
- **Na mesma condição, menor**: a divergência **D10** (o campo tem `->maxLength(50)`, logo o
  código escolheu o ramo da recusa da premissa nº 3) também segue sem registro no `00`.
- **Ação exigida**: perguntar ao solicitante se confirma a direção aberta; se sim, Adendo 4
  fixando a linha 3 e corrigindo a frase das duas docs de usuário.

### QA-17 — O `composer update` trouxe cinco pacotes fora da família Filament, e três documentos dizem que não · **Minor** · destino 1

- **Dimensão** L5 · **Relacionado a** QA-01, passo 10, ADR-08, CHANGELOG
- **Esperado**: CHANGELOG (*"com as **nove** dependências irmãs junto"*), `01:445` (*"e as 9
  dependências irmãs junto"*) e ADR-08.
- **Observado**, medido no `composer.lock`: **18** pacotes mudaram. São **11** irmãs Filament em
  `packages` (mais `filament/upgrade` em dev), e **cinco fora da família**:
  `danharrin/livewire-rate-limiting` 2.2.1→2.3.0, `spatie/laravel-medialibrary` 11.23.5→11.23.8,
  `ramsey/uuid` 4.9.3→4.9.4, `phpstan/phpdoc-parser` 2.3.3→2.3.5, `rector/rector` 2.6.2→2.6.7.
  Duas delas são dependência de runtime do kit.
- **Repro**: comparar `git show HEAD:composer.lock` com `composer.lock` por nome e versão.
- **Por que importa**: `--with-all-dependencies` foi a decisão certa e o texto subdeclara o
  alcance dela. Quem ler o CHANGELOG para decidir se aceita o bump lê "só Filament".
- **Ação exigida**: corrigir a contagem e nomear as cinco no CHANGELOG e em ADR-08.

### QA-18 — Uma das citações de QA-05 sobreviveu, em ADR-05 · **Minor** · destino 1

- **Dimensão** L2 · **Relacionado a** QA-05
- **Fechado** ✅: `:841-845` virou `:857-862` nos quatro lugares; `:291` virou `:307` no `01` e
  em `02:274`.
- **Não fechado**: `02:329` (ADR-05 › Referências) ainda lista `app/Models/User.php:106-108`,
  **`:291`**, `:310`. `User.php:291` é `if (($razao = $this->motivoParaNaoDesativar()) !== null)`,
  não o `forceFill` — que está em `:307` (e o de `reativar()` em `:326`). A citação também não
  traz símbolo, que `specs.md` pede.
- **Repro**: `sed -n '291p;307p;326p' app/Models/User.php`.

---

## Ciclo 1 — o que foi verificado e fechado

| # | Estado | Evidência medida agora |
|---|---|---|
| QA-01 | ✅ **fechado** | `composer.lock` com `filament/filament v5.8.2` nas 12 posições; `composer.json:34` segue `^5.6`; passo 10 no `01:439`; ADR-08; CHANGELOG. **Gerou QA-10, QA-13 e QA-17.** |
| QA-02 | ⚠️ **parcial** | Cobertura RQ-01…RQ-20 ✅, RQ-02 marcada como substituída ✅. Passo 3, Superfície de UI e Variáveis de Ambiente ❌ → **QA-12** |
| QA-03 | ✅ **fechado** | Docblock de `configuraVersaoNoRodape()` (`ConfiguraFilamentGlobal.php:101-113`) e o cabeçalho de `.kit-versao` nas **duas** folhas dizem "versão do SISTEMA"; a citação nova `KitInfo.php:73` confere |
| QA-04 | ✅ **fechado** | ADR-08 declara o limite de RQ-16 com as duas citações conferidas (`KitUpdate.php` `CAMINHOS_SO_RELATORIO` em `:305-307`, o aviso em `:960-972`). Resíduo: `00 › Adendo 1 › Ambiguidades › RQ-16` ainda chama o `kit:update` de *"mecanismo para propagar mudança"*; o `00` só muda por Adendo |
| QA-05 | ⚠️ **parcial** | 5 de 6 corrigidas → **QA-18** |
| QA-06 | ✅ **fechado** (no `01`) | `01:26` aponta ADR-02, `01:153` aponta ADR-03, `01:27` aponta `07-dossies`. Resíduo conhecido e declarado: `00:60` ainda manda ler ADR-03 para o autosave — só sai por Adendo |
| QA-07 | ✅ **fechado** | As 10 perguntas estão em `00 › Perguntas devolvidas`, com as respostas das nº 1, nº 6 e nº 9; Adendo 3 criou **RQ-19** e **RQ-20**; R7/R8 perderam o `@premissa`; R14 nasceu com CT-46/CT-47. **Mas a nº 2 ficou de fora → QA-16** |
| QA-08 | ❌ **aberto** | Ver QA-15 |
| QA-09 | débito | sem mudança |

### Os três achados que o solicitante encontrou e corrigiu — reavaliados

1. **`config/kit.php` com `(bool) env()`** — **a correção procede**, e o defeito era real:
   `(bool) 'off'` é `true` em PHP, e `BooleanoDoEnv::comPadrao()` (`:55-59`) manda `null`, `''` e
   texto ilegível ao default **antes** do `filter_var`, que é o ponto que `FILTER_NULL_ON_FAILURE`
   sozinho não cobre. Não é mais estrito que `.ai/rules/config.md` exige — é mais estrito que ela
   *recomenda* (*"Para o resto, `(bool) env()` basta"*), na direção segura; a rule é candidata a
   revisão no step 9. O rastro ficou incompleto: `03 › Conformidade com Rules` e o `04 › D2` ainda
   descrevem o código antigo (QA-11, QA-15).
2. **CT-09 posicional, reescrito com `kitConfigCom()`** — **procede**. O caso agora afirma sobre a
   fiação do `config/kit.php` relida com a env forçada (`tests/Pest.php:555`), e não sobre a
   classe. **Mata o mutante**: com `(bool) env()` de volta, as linhas `off`, `no` e `talvez`
   resolvem para `true` e `toBeFalse()` reprova; o par positivo impede a implementação que
   devolve `false` sempre. Verde nesta execução. Ressalva de forma, não de valor: **dois** casos
   distintos carregam o rótulo `[CT-09]` (QA-11).
3. **CT-46 fraco, reescrito com `/\p{L}/u`** — **procede para CT-46 e não para CT-47**, e CT-46
   trocou o oráculo fraco por um posicional. Ver **QA-14**, que é o achado que esta reavaliação
   produziu.

---

## Matriz de Rastreabilidade — só as linhas com lacuna

| RQ | Cláusula (resumo) | Passo | CT | CT-B | Código | Resultado |
|----|---|---|---|---|---|---|
| RQ-02 | versão pela tag/branch | — | — | — | — | ⬛ substituída por RQ-17; agora declarado no `01:23` ✅ |
| RQ-15 | atualizar quando sair versão | **10** | — (declarado não-testável, pergunta nº 6) | — | `composer.lock` v5.8.2 | ✅ **entregue**; sem registro no `03` → QA-15 |
| RQ-16 | projetos que usam recebem | 10 | CT-32 (mecanismo 1) | — | constraint | ⚠️ parcial **com limite declarado** em ADR-08 ✅ |
| RQ-19 | nenhum dado sai para terceiro no avatar | 1 | CT-20, 21, 22, 45 | CT-B02 | `AvatarDeIniciais` | ✅ — a garantia é cumprida; a **paridade** que a ADR promete, não → QA-13 |
| RQ-20 | a versão do kit carrega rótulo que a distingue | 3 | CT-46, CT-47 | — | `versao-do-kit.blade.php:59` (`'kit '`) | ⚠️ código ✅; o passo 3 não menciona o rótulo (QA-12); CT-47 não exige rótulo (QA-14); CT-46/47 fora do índice do `04` (QA-11) |

**RQ-19 e RQ-20 ganharam passo, CT e código** — era a pergunta (a) do pedido. Nenhuma das duas é
omissão silenciosa. As lacunas são de texto e de oráculo, não de entrega.

---

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | QA-16 (premissa nº 2 negada pelo produto). RQ-19/RQ-20 completas |
| B | Fronteiras e dados | ✅ | Reaproveitado do ciclo 1; nada no diff da remediação mexe em entrada de usuário |
| C | Matriz de permissão | ✅ | Sem entidade nova; CT-11 e CT-41 seguem verdes |
| D | Observabilidade real | ✅ | Zero `Log::`/`logger()` novo na remediação; sem PII |
| E | Performance | ✅ | A remediação é config, teste e texto. `BooleanoDoEnv` é string building |
| F | UX de erro | ✅ | Sem superfície de erro nova |
| G | Tema e cor | ⚠️ | Sem mudança de CSS além de comentário. Nível visual **não rodou** (sem MCP) |
| H | Acessibilidade | ⚠️ | Débito do ciclo 1 mantido |
| I | Segurança da superfície nova | ✅ | Nenhuma rota, propriedade pública ou id de terceiro novo. `BooleanoDoEnv` **fecha** o interruptor em vocabulário ilegível, que é a direção certa |
| J | Regressão adjacente | ✅ | **Com o Filament v5.8.2**: `Kit,Tenancy --parallel` **2461/2461** (9600 asserções, 0 falhas); os 8 arquivos da feature **196/196**. Era o risco central do bump |
| K | Adequação da suíte | ⚠️ | **Estático**: QA-14. Nenhum caso sem asserção, nenhum `assertOk()` como oráculo único. **Mutação não rodou** (sem PCOV/Xdebug) |
| L | Consistência documental | ❌ | QA-10 (L2), QA-11 (L1), QA-12 (L3), QA-15 (L4), QA-17 (L5), QA-18 (L2) |

---

## Débitos Aceitos (acumulados)

- QA-09 (Cosmético): nome do arquivo da blade.
- Dimensão H: rodapé sem `assertNoAccessibilityIssues()`.
- Pergunta nº 10 do `04`: barreira separada de leitura e de escrita.
- Resíduos do `00` que só saem por Adendo: `00:60` (ADR-03 no lugar de ADR-02) e a premissa de
  RQ-16 sobre o `kit:update`.

## Suspeitas Não Confirmadas

Nenhuma. Todo achado deste ciclo tem repro de uma linha.

## Não Verificado

- **Mutation score (K, passo 2)**: `php -m` sem PCOV e sem Xdebug, igual ao ciclo 1. QA-14 saiu
  do crivo estático, não de mutação medida.
- **`composer test` completo pós-bump**: rodando em outro processo durante esta execução. Aqui
  foi medido `Kit,Tenancy` (2461/2461) e os arquivos da feature (196/196); `Unit,Feature` não.
- **`composer test:browser`**: não executado — exige `npm run build` e binários do Playwright, e
  a suíte estava ocupada. CT-B01 e CT-B02 **não** foram reexecutados contra o Filament 5.8.2, e o
  `beforeunload` é JS do vendor que o bump tocou (`page/index.blade.php` mudou de linha).
  **É a lacuna mais cara deste ciclo.**
- **Playwright MCP e Boost MCP**: indisponíveis, como no ciclo 1. Sem confronto
  "elementos da tela × exercitados pelo CT-B" e sem screenshot nos dois temas.
- **App servido**: nada rodou contra instância real.
- **RQ-11 (Blueprint)**: o ciclo `bp:on` → aderência → `bp:off` segue sem execução.
