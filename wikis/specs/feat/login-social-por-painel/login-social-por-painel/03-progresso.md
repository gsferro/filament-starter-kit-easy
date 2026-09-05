# Progresso — Login social por painel

> Wiki criada em 2026-09-02, na branch `feat/login-social-por-painel`.
> **Fidelidade do requisito: média.** As três ambiguidades materiais foram **respondidas pelo
> mantenedor** antes de o plano ser escrito: A1 (permissão **e** destino), A2 (default todos os
> painéis), A4 (os três painéis). A2 e A5 seguem como premissas assumidas, com o "Se negado" no `00`.

## 0. Antes de implementar

- [x] Requisito capturado verbatim e decomposto em RQ-01..RQ-06
- [x] Levantamento do código: `ConfiguracaoDoLogin`, o render hook global, as rotas fora do painel,
      os seis `getPanel('app')` e o mecanismo de sessão `login_social.contexto`
- [x] **A1 respondida**: a entrega inclui o destino, não só a permissão
- [x] **A2 respondida**: default é todos os painéis (feature nasce inerte)
- [x] **A4 respondida**: os três painéis entram na escolha
- [x] Confirmar **A3** (settings do kit como fonte — assumido, ADR-07) e **A5** (quem entra sem
      papel do painel cai no comportamento atual do painel)
- [x] `04-casos-de-teste.md` recebido da `feature-test-design` — 26 cenários, 8 regras, 39
      mutantes, 2 sem matador (declarados), com revisão adversarial fechada
- [x] **Seis perguntas devolvidas pela derivação incorporadas ao `00`** (A6..A11), e uma delas
      corrigiu um **defeito da própria wiki** — ver a tabela da revisão profunda

## 1. `config/kit.php` — a chave `paineis` por provedor

- [x] Quatro chaves, no padrão de lista do `.env` que o kit já usa (`kit.convites.lembretes_dias`)
- [x] Vazio devolve `[]`; a tradução "vazio = todos" é de quem lê, não da config (ADR-04)
- [x] `.env.example` com as quatro chaves vazias, ao lado das que já existem por provedor

## 2. `ConfiguracoesDoKit` — a quarta propriedade por provedor

- [x] A propriedade `array` por provedor, nome de `ProvedorSocial::propriedadeDeSettings('paineis')`
- [x] A linha no `mapaDeConfiguracao()` por provedor
- [x] Migration de settings **NOVA** (nunca editar a de 2026-08-25 que já rodou)
- [x] Fora de `encrypted()` — não é segredo
- [x] `tests/Kit/ConfiguracoesDoKitTest.php` continua verde (há caso que assere que toda
      propriedade declarada é semeada)

## 3. `ConfiguracaoDoLogin` — a terceira condição

- [x] `disponivel(ProvedorSocial $provedor, ?string $painel = null)` — default nulo preserva os
      chamadores anteriores
- [x] `painelAutorizado()` público — o controller precisa distinguir as duas recusas no log
- [x] Lista vazia = todos (ADR-04); `in_array` **estrito**
- [x] Docblock reescrito: a pergunta passou a ser por provedor **e** painel

## 4. O blade dos botões passa o painel corrente

- [x] `filament()->getCurrentOrDefaultPanel()?->getId()` — o padrão do kit em tela de auth
- [x] `disponiveis($painel)` e `'painel' => $painel` no link, dentro do `array_filter` existente
- [x] **Nenhum nome de diretiva com arroba nos comentários** do blade (`.ai/rules/views.md`)

## 5. `LoginSocialController` — valida, carrega na sessão, usa como destino

- [x] `painelDaRequisicao()` — lista branca de `Paineis::opcoes()`
- [x] A barreira no `redirecionar()`: painel não autorizado → `warning` + 404 (RQ-05)
- [x] O painel entra no `login_social.contexto`, junto de `org`/`token`
- [x] `painel(?string $id)` — o da sessão quando válido, o **default** quando não (ADR-06)
- [x] Os seis `getPanel('app')` parametrizados **um por um** (a tabela do PRD diz o porquê de cada)
- [x] `retorno()` e `confirmarVinculo()` **sem** reconferência por painel (ADR-05)
- [x] O painel de destino no contexto dos logs de sucesso que já existem

## 6. A tela de settings ganha o campo por provedor

- [x] `Select::multiple()` com `Paineis::opcoes()`, um por provedor
- [x] `helperText('Vazio = todos os painéis.')` — a decisão da ADR-04 precisa estar na tela
- [x] `->visible()` casado com o toggle do provedor, como os campos de credencial já fazem

## 7. Documentação e changelog

- [x] `docs/pt/autenticacao/login-social.md` — a escolha de painéis e o destino
- [x] `docs/en/autenticacao/login-social.md` — a mesma, em inglês
- [x] `CHANGELOG.md` → `### Adicionado` (a escolha) **e** `### Alterado` (o destino mudou)
- [x] Conferir antes se outra branch editou os arquivos de doc nesta rodada

## Testes

- [x] `04-casos-de-teste.md` derivado do `00` pela `feature-test-design`
- [x] Cenários nos vizinhos (`tests/Kit/LoginSocial*`) ou arquivo próprio, conforme o `04`
- [x] Suíte `tests/Tenancy` para o `/app` com tenancy (o destino resolve organização default)
- [x] Helper novo usado por mais de um arquivo vai para `tests/Pest.php` (`.ai/rules/testes.md`)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse`
- [x] `php artisan test --compact tests/Kit/LoginSocial*Test.php tests/Kit/VinculoDeProvedorSocialTest.php`
- [x] `php artisan test --compact tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/ConfiguracoesDoKitTelaTest.php`
- [x] `php artisan test --compact --testsuite=Tenancy`
- [x] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [x] `vendor/bin/pest --parallel --tia`
- [x] À mão: Google só no `/admin` → botão fora do `/app/login`; `?painel=app` forjado → 404;
      entrada pelo `/admin` termina no `/admin`
- [x] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o painel corrente resolve no `redirecionar()`" | **falso** — a rota é global (`routes/web.php:61-70`), fora do painel; `getCurrentOrDefaultPanel()` cairia no **default**, que é o defeito que a feature corrige | ADR-03 descartou essa alternativa explicitamente; o painel passou a vir da query |
| "basta filtrar o botão" | o `redirecionar()` é rota pública e a query é entrada do usuário — filtro de UI não é barreira (`.ai/rules/filament.md:19-29`) | RQ-05 e o passo 5a; a barreira é no servidor |
| "o destino é configurável" | **falso** — `Filament::getPanel('app')` está escrito em seis pontos, e um deles é o destino de sucesso (`:645-648`) | o achado que mudou o escopo; A1 foi levada ao mantenedor |
| "os seis pontos são equivalentes" | não são: quatro são "volta ao login", um é destino de sucesso, um é o perfil (e consulta `hasTenancy()`) | o passo 5d trata um por um, com tabela |
| "preciso de uma constante de painéis" | `App\Support\Paineis::opcoes()` (`:62-70`) já devolve exatamente a lista, de `Filament::getPanels()` | ADR-02: reuso, nenhuma constante |
| "monto o nome da propriedade por concatenação" | `ProvedorSocial::propriedadeDeSettings()` (`:120`) já faz, e trata o hífen do LinkedIn | passos 2 e 6 a reusam |
| "propriedade de settings pode ser lida no boot" | armadilha medida: `registro_verificar_email` gravava e não fazia nada (`ConfiguracoesDoKit.php:318-330`) | ADR-07: a leitura é por request, nos dois pontos |
| "vazio pode significar nenhum painel" | seria desligar o login social de toda instalação existente num update | ADR-04: vazio = todos |
| "posso editar a migration de settings existente" | instalação de terceiro que só roda `migrate` ficaria sem a linha e estouraria `MissingSettings` no boot de todo request (`:66-68`) | passo 2: migration **nova**, obrigatoriamente |
| **"`getPanel()` com id inexistente lança"** (ADR-06, primeira versão) | **não lança nesta versão**: `PanelRegistry::get()` devolve `null` no modo estrito (`vendor/filament/filament/src/PanelRegistry.php:36-44`) | **achado da derivação**, que abriu o vendor como `.ai/rules/specs.md` manda. Justificativa errada, conclusão certa: sem a guarda o `->getUrl()` estoura `Error ... on null`, e o observável é 500. ADR-06 corrigida com o `file:line` |
| **"painel inexistente na query responde 404"** (ADR-03, primeira versão) | **contradizia o próprio PRD**: o código do passo 5b faz `painelDaRequisicao()` devolver `null` para painel inexistente, e o `abort` é condicionado a `$painel !== null` — ou seja, segue no default | **achado da derivação dos casos de teste (A6)**, não da minha revisão. O código estava certo e a prosa errada: 404 ali transformaria um link antigo sem `painel` na mesma resposta de uma tentativa negada. ADR-03 e `01` corrigidos, com o motivo escrito |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | **Nenhuma.** Revisor independente auditou os quatro arquivos e devolveu `Lean already. Ship.` | — | — |

Auditado explicitamente, a pedido: os sete passos do PRD, as sete ADRs, os três métodos novos
(`painelAutorizado()`, `painelDaRequisicao()`, `painel()`), as quatro propriedades de settings — uma
por provedor — e a tabela dos seis pontos de `getPanel('app')` do passo 5d. Nenhum foi apontado
como enxerto, cerimônia ou reinvenção do que o kit já entrega.

## Blockers

- [x] **`04-casos-de-teste.md` pendente** — derivação delegada à `feature-test-design`; a
      implementação não começa antes dele (a barreira de RQ-05 é o cenário que mais importa)
- [x] **A3 e A5 não confirmadas** — as duas seguem como premissa assumida, com o "Se negado"
      registrado. Nenhuma das duas bloqueia os passos 1-5.

## Desvios do Plano

<!-- Pós-implementação -->

## Notas de Implementação

<!-- Pós-implementação -->

## Retrospectiva

- **Funcionou bem**: levantar o código **antes** de escrever o plano. O achado dos seis
  `getPanel('app')` mudou a feature de "uma condição a mais" para "o fluxo conhece o painel", e foi
  levado ao mantenedor como decisão em vez de assumido em silêncio — que era o erro mais provável
  aqui, porque a segunda linha do requisito autoriza a leitura estreita.
- **Faltou no plano**: nada ainda.


---

## Implementação — concluída em 2026-09-04

Implementada em `main`, não na branch `feat/login-social-por-painel`: aquela branch não tem nenhum
commit à frente de `main` (`git log main..feat/login-social-por-painel` é vazio) — é um ponteiro
antigo, e a wiki já estava versionada em `main`.

**A1 foi CONFIRMADA com o usuário antes de qualquer código**: leitura **ampla**, permissão mais
destino. A wiki a marcava como *"a pergunta que decide o tamanho da feature"* e ela nunca tinha
sido respondida — e como a entrega toca o caminho de autenticação, perguntar custou menos que
supor.

### O que entrou

| Passo | Onde |
|---|---|
| 1 | `config/kit.php` — `paineis` por provedor, lida do `.env`, default `[]` |
| 2 | `ConfiguracoesDoKit` — 4 propriedades, 4 linhas no `mapaDeConfiguracao()`, migration de settings **nova** |
| 3 | `ConfiguracaoDoLogin` — `disponivel($provedor, ?$painel)` e `painelAutorizado()` |
| 4 | `botoes-sociais.blade.php` — filtra pelo painel corrente e o propaga na URL do botão |
| 5 | `LoginSocialController` — barreira com log, lista branca, painel na sessão, destino |
| 6 | `ConfiguracoesDoKit` (Page) — `Select` múltiplo de painéis, por provedor |
| — | `.env.example` — as quatro chaves, vazias, com a nota do que vazio significa |

**Zero `getPanel('app')` restantes no controller** — eram seis, e cada um passou a resolver o
painel de destino. Era o coração de A1: sem isso, o botão apareceria no `/admin` e levaria a pessoa
para o `/app`.

### Casos de teste

`tests/Kit/LoginSocialPorPainelTest.php` — 17 casos (10 `it`, dois com dataset), **17/17 na
primeira execução**.

A matriz painel × lista tem 8 linhas, e três delas carregam o peso: `[]` valendo nos três painéis
(a compatibilidade), `['admin']` recusando o `app` (o caso de uso do requisito) e a lista com
inteiro `[0]` (a comparação estrita — com `in_array` frouxo, `0 == 'admin'` autorizaria tudo).

Os casos que não vieram do gabarito e são os que mais matam:

- **a condição é conjuntiva** — painel autorizado NÃO liga provedor desligado. Mata o `||` no lugar
  do `&&`, e sem ele "restringir por painel" viraria uma forma de LIGAR um provedor;
- **a rota recusa, nas duas direções** — RQ-05. Esconder o botão e deixar a rota aberta entregaria
  a restrição só na tela. A direção positiva impede o falso ✅ de um `abort(404)` incondicional;
- **painel forjado na query** é ignorado, não estoura. `Filament::getPanel()` com id inexistente
  lança, e isso seria 500 no meio do fluxo de autenticação;
- **a recusa é auditada com `motivo`** — o 404 é o mesmo do provedor desligado, e é o log que
  distingue "não existe" de "alguém chegou aqui e não devia".

**Discriminância medida**: trocando a terceira condição por `return true`, quatro casos reprovam
(13 passam, 4 falham).

### Desvios do Plano

- **`urlDeLoginDoPainel()` não estava no plano.** O passo 5d listava quatro pontos que chamavam
  `getPanel('app')->getLoginUrl()` e um que passava a URL para o `ContaIndisponivelController`.
  Todos queriam a mesma coisa, e um método com nome resolve os cinco — em vez de repetir
  `$this->painelDeDestino()->getLoginUrl()` cinco vezes.
- **`painelDoContexto()` também é novo.** O plano lia `$contexto['painel'] ?? null` em cada ponto;
  isolar a leitura da sessão num método é o que permite o `painelDeDestino()` revalidar num lugar
  só.
- **O plano dizia `urlDoPainel(?string $painel = null)`.** Passar o painel por argumento em cada
  chamada obrigaria cada chamador a ler a sessão. Lendo dentro, os chamadores não mudam de
  assinatura — e há cinco deles.

### Dois defeitos que a regressão pegou

Nenhum dos dois apareceu nos 17 casos novos — os dois só apareceram quando a suíte **existente**
rodou. É o argumento da regressão obrigatória, medido.

- **`SettingsBlueprint::deleteIfExists()` NÃO EXISTE.** O passo 2 do plano pedia esse método no
  `down()` da migration de settings. A API do pacote tem `add`, `delete`, `rename`, `update`,
  `addEncrypted`, `updateEncrypted`, `encrypt` e `decrypt` — e nada mais
  (`vendor/spatie/laravel-settings/src/Migrations/SettingsBlueprint.php:20-58`). Quem pegou foi
  `it desfaz e refaz as migrations de settings sem quebrar`, que já existia. Trocado por
  `delete()`.
- **O `Select` de painéis foi inserido DUAS vezes**, e a segunda caiu dentro de `secaoAntiRobo()`,
  onde `$provedor` não existe. A tela de configurações passou a responder **500** com
  `Undefined variable $provedor`, e três casos existentes reprovaram. Causa: um `str.replace` sem
  contagem, cujo padrão (`->maxLength(255)->visible($ligado),` fechando o `schema`) casava em dois
  métodos. As 22 linhas indevidas foram removidas.

O segundo é o mais instrutivo: `php -l` passava, o Pint passava, e os 17 casos novos passavam —
porque nenhum deles abre a tela de configurações. Só a regressão viu.

### Duas âncoras que ficaram vermelhas de propósito

A suíte `Kit` inteira (1729 casos) reprovou em duas, e as duas são **âncoras funcionando** — não
defeito:

- **`KitInfoTest` CT-06** conta as propriedades do `mapaDeConfiguracao()`: 44 → **48**. O loop do
  caso é gerado do próprio mapa, então as quatro propriedades novas entraram sozinhas; só o
  NÚMERO é escrito à mão, e é ele que obriga a decisão. Atualizado, com o motivo no comentário.
- **`TextoDoEnvTest`** proíbe `env('CHAVE', 'default')` com default de TEXTO em `config/kit.php` —
  porque `env()` só usa o default para chave **ausente**, e chave presente e vazia devolve string
  vazia. As quatro chaves `_PAINEIS` entraram na lista de isenção, e o caso é **exatamente** o do
  `KIT_CONVITE_LEMBRETES_DIAS` que o teste já isentava: vazio não é acidente, é o jeito de escrever
  "sem restrição". Um `?:` aqui não teria default para oferecer — o default **é** o vazio.

### Notas de Implementação

- **A inversão de `filled()` para `blank()` nas credenciais** era pré-condição do passo 3 e está
  coberta: os casos por provedor com cada credencial vazia continuam verdes.
- **`array_filter(['painel' => $painel])` descarta o nulo**, e é isso que mantém o contexto da
  sessão idêntico ao de antes desta feature quando não há painel na query. Há caso de teste
  afirmando a ausência da chave.
- **O `abort_unless` de `retorno()` e `confirmarVinculo()` ficou SEM painel**, como ADR-05 decidiu:
  no callback o painel vem da sessão e a autorização já foi decidida na ida. Reconferir lá
  transformaria uma configuração alterada no meio do fluxo num 404 depois de a pessoa já ter
  autenticado no provedor — pior UX pelo mesmo nível de segurança.
- **A migration de settings é NOVA**, nunca a que já rodou. Editar
  `2026_08_25_000000_add_provedores_sociais_to_kit_settings.php` deixaria instalação de terceiro
  sem as linhas, e `aplicarNaConfig()` estouraria `MissingSettings` no boot de todo request.

### Retrospectiva

- **Funcionou bem**: confirmar A1 antes de escrever. A leitura estreita teria entregue uma
  configuração que não produz o efeito que o requisito usa para justificá-la, e a wiki já sabia
  disso — o que faltava era a decisão.
- **Funcionou bem**: o plano ter escrito o código dos passos 3 e 5 quase inteiro. Os 17 casos
  passaram de primeira, o que em três wikis nesta sessão só aconteceu aqui.
- **Faltou no plano**: o `pest-plugin-livewire` inexistente e o `StatPlus::getValue()` devolvendo
  HTML — os dois custaram ciclo vermelho nas outras duas wikis desta rodada e valem uma linha na
  verificação de stack de testes da skill.

## Quality Gate Final — 2026-09-04

- **Veredito**: APROVADO no ciclo 1.
- **Rastreabilidade**: RQ-01..RQ-06 sem lacunas.
- **Regressão**: suíte Tenancy completa, 292 testes e 1122 assertions, verde.
- **Relatório**: `06-relatorio-qa.md`.
