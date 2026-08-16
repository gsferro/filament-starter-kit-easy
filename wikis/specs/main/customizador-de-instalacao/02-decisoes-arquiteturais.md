# Decisões Arquiteturais — Customizador de instalação

## ADR-01: As perguntas moram no `kit:install`, não num binário `starter-kit new`

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

RQ-01 pede paridade com o `laravel new`. Mas o `laravel new` é um **binário externo**
(`laravel/installer`) que roda antes de o projeto existir: ele pergunta, decide, e só então chama
`composer create-project`. O kit não tem — nem quer ter — um binário próprio para instalar; a via
documentada é `composer create-project gsferro/starter-kit-easy`.

### Decisão

As perguntas rodam **dentro do projeto**, no `php artisan kit:install`, que já é o
`post-create-project-cmd` do `composer.json`. O modelo copiado do Laravel é o das perguntas
(`select` para banco, defaults silenciosos sem TTY, substituição pontual no `.env`), não o do
empacotamento.

### Alternativas Consideradas

1. **Publicar um `gsferro/starter-kit-installer`** — daria paridade literal, mas cria um segundo
   artefato para versionar, publicar e documentar, e obriga o usuário a instalar algo globalmente
   antes de instalar o kit. Custo alto para um ganho que é de embalagem.
2. **Script `configure.php` rodado à mão depois** (modelo do package-skeleton do Spatie) — viola
   RQ-01: não acontece "no processo de instalação", vira um passo que se esquece.

### Consequências

- **Positivas**: zero artefato novo; funciona igual para `create-project` e para `git clone` +
  `composer setup`; reexecutável com `--force`.
- **Negativas**: depende de o Composer repassar o TTY ao script — verificado
  (`EventDispatcher::executeTty`, Composer 2.9.5), mas é uma dependência de ambiente que o kit não
  controla.
- **Riscos**: ambiente sem TTY degrada para a instalação de hoje — que é justamente RQ-08, então o
  pior caso é o comportamento atual.

### Referências

- `composer.json:149-152` (`post-create-project-cmd`)
- `phar://composer.phar/src/Composer/EventDispatcher/EventDispatcher.php` — `executeTty()`
- `laravel/installer` → `NewCommand::promptForDatabaseOptions()`, `configureDefaultDatabaseConnection()`

---

## ADR-02: Quatro dos onze itens do README viram pergunta; os outros sete viram ponteiro

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

RQ-02 diz "todos os itens que colocamos como personalizavel". RQ-11 e RQ-12 dizem "não lento e
exaustivo" e "eficiente e rápido". Os dois se contradizem para sete dos onze itens: arte do login é
um arquivo de imagem; matriz de permissões, health checks, comandos da UI e backups são **código
PHP**; acesso aos painéis é dado criado depois no `/admin`; agente de IA é registro de banco
editável por tela. Perguntar por eles significaria pedir código dentro de um prompt de terminal.

### Decisão

Perguntar os quatro que são valor escalar — nome, cor, credenciais do admin, multi-tenancy — mais
o banco de dados (que nem está na lista do README, mas é RQ-05/RQ-06 e é o item mais caro de
mudar depois). Os sete restantes aparecem no **resumo final**, um por linha, com o arquivo exato
a editar.

Confirmada com o solicitante em 2026-08-16 (opção "Essencial — 5 perguntas").

### Alternativas Consideradas

1. **Perguntar os 11** — mataria RQ-11/RQ-12 e produziria prompts sem resposta boa ("qual sua
   matriz de permissões?").
2. **Perguntar só 3** (nome, banco, admin) — deixaria de fora justamente a tenancy, que é o item
   cujo adiamento custa `migrate:fresh` depois (ADR-04).

### Consequências

- **Positivas**: instalação em cinco Enters; nenhum prompt sem efeito no disco.
- **Negativas**: RQ-02 é atendido "na parte que um instalador atende" — a leitura literal de
  "todos os itens" não é.
- **Riscos**: o resumo vira o único lugar que lembra dos sete manuais; se ele encolher, a
  descoberta some. Mitigado por CT que afirma sobre o conteúdo do resumo.

### Referências

- `README.md` → "Personalize seu projeto" (11 itens)
- `00-requisito.md` → Ambiguidades, RQ-02

---

## ADR-03: A cor primária é `KIT_COR_PRIMARIA` no `.env`, não reescrita dos PanelProviders

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

O item 3 do README diz "`->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`" — mas
**nenhum dos três providers tem essa chamada hoje**. Para o instalador "ajustar o código-fonte"
(RQ-07), ele teria de injetar a chamada por regex em três arquivos de PHP.

### Decisão

O kit passa a ler `config('kit.cor_primaria')` num `->colors()` versionado nos três providers, e o
instalador escreve apenas `KIT_COR_PRIMARIA=Blue` no `.env`. A lista de cores oferecida é fechada,
e a resolução devolve paleta vazia quando a constante não existe.

### Alternativas Consideradas

1. **Regex nos três providers** — é o que o README descreve literalmente, e é frágil: o arquivo é
   feito para ser editado pelo usuário, então o ponto de ancoragem do patch some na primeira
   customização. Um patch que erra num arquivo de provider derruba os três painéis.
2. **Deixar cor fora do instalador** — perde um item barato e visível, que é a primeira coisa que
   diferencia o projeto de alguém do kit padrão.

### Consequências

- **Positivas**: nenhuma reescrita de PHP; muda no `.env` a qualquer momento, sem redeploy de
  código; `kit:update` continua entregando os providers sem conflito com a escolha do usuário.
- **Negativas**: o item 3 do README precisa ser reescrito — a customização deixa de ser "edite o
  provider" e passa a ser "defina a variável" (quem quiser paleta completa continua editando).
- **Riscos**: precedência contra a cor da organização no `/app` (R4 do PRD). A cor do tenant é
  registrada em `bootUsing()` e avaliada no `renderStyles()`, depois do `->colors()` do painel —
  precisa ser confirmado por CT de regressão, e o plano B é registrar a cor global pela mesma via.

### Referências

- `app/Providers/Filament/AppPanelProvider.php:96-145` — o comentário que explica por que a cor do
  tenant usa `FilamentColor::register()` e não `->colors()`
- ADR-02 da wiki `identidade-visual-da-organizacao`
- `vendor/filament/support/src/Colors/Color.php` — as constantes da paleta

---

## ADR-04: Multi-tenancy na instalação liga **antes** do migrate e não destrói nada

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

O `kit:tenancy` é destrutivo (`migrate:fresh --seed`) por uma razão real: a migration de
permissões do spatie cria as colunas de team lendo `permission.teams` **em tempo de execução**, e
ligar a flag depois de migrar deixa config e schema incoerentes — falha silenciosa até o primeiro
login. Ele também exige repositório git com árvore limpa, para que a destruição seja reversível.

Nenhuma das duas coisas faz sentido **durante a instalação**: o banco ainda não existe e o projeto
recém-criado não é um repositório git com histórico.

### Decisão

Extrair para `App\Support\AtivadorDeTenancy` os três passos não destrutivos — `KIT_TENANCY` no
`.env`, `permission.teams` + `filament-shield.tenant_model` nos configs, e o alinhamento da config
em memória — e chamá-los no `kit:install` **antes** do `migrate`. O `kit:tenancy` continua sendo o
caminho para ligar depois, com o `preVoo()` de git e o `migrate:fresh` que só ele precisa.

### Alternativas Consideradas

1. **Chamar `kit:tenancy` no fim da instalação** — falharia no `preVoo()` (sem git) e, se
   contornado com uma flag, refaria do zero o banco que acabou de ser migrado e semeado. Instalação
   duas vezes mais lenta, contra RQ-12.
2. **Deixar tenancy fora do instalador** — mantém o item mais caro do kit como decisão adiada, que
   é exatamente o que produz o `migrate:fresh` doloroso lá na frente.

### Consequências

- **Positivas**: a tenancy ligada no dia 1 sai **de graça** — sem recriação de banco, sem git, sem
  segunda migração; e o conhecimento das "três chaves que precisam concordar" passa a ter um dono
  único, em vez de estar duplicado em dois comandos.
- **Negativas**: `KitTenancy` é refatorado — comportamento externo idêntico, mas com risco de
  regressão em um comando destrutivo. `tests/Tenancy/**` é a rede.
- **Riscos**: o `alinharConfigEmMemoria()` precisa rodar antes do `migrate` do **mesmo processo**;
  se a chamada for esquecida, as tabelas nascem sem `team_id` e o erro só aparece no primeiro
  login. CT dedicado.

### Referências

- `app/Console/Commands/KitTenancy.php:165-238` — os três passos e o docblock que explica os prazos
- `tests/TestCase.php:createApplication()` — as mesmas três chaves, com o mesmo raciocínio

---

## ADR-05: A feature não cria channel de log próprio

**Status**: Aceita *(revisada na auditoria Ponytail do plano — a versão anterior criava o channel
`instalacao` com um helper de fallback; foi cortada antes de virar código)*
**Data**: 2026-08-16

### Contexto

O padrão de log do projeto pede um channel por feature, e o kit já tem três (`ai`, `tenancy`,
`autenticacao`). Só que `config/logging.php` **não** está em `KitUpdate::CAMINHOS_DO_KIT` — de
propósito, porque é arquivo que o usuário edita e sobrescrevê-lo apagaria a configuração de log
dele. Um projeto antigo, atualizado por `kit:update`, teria o `KitInstall` novo e **não** teria o
channel: `Log::channel('instalacao')` lançaria `InvalidArgumentException` e derrubaria a
reinstalação. Evitar isso exigiria um helper de fallback existindo só para essa feature.

### Decisão

**Nenhum channel próprio.** Os registros vão para o channel default, com o mesmo prefixo
`[Classe@Método]` de sempre.

O argumento que derrubou o channel não é o custo do helper, é o propósito: a justificativa seria o
post-mortem da **instalação desatendida** — e instalação desatendida não tem terminal, logo **pula
a customização inteira** (R1 do `04`). O conteúdo que o arquivo de log guardaria não chega a
existir nesse caso. Na instalação atendida, o registro está no terminal, na frente de quem instala.

### Alternativas Consideradas

1. **Channel `instalacao` + helper de fallback** — era o desenho original. Três arquivos e uma
   indireção para um arquivo de log que, no cenário que o justificava, nasce vazio.
2. **Pôr `config/logging.php` em `CAMINHOS_DO_KIT`** — o `kit:update` passaria a oferecer a
   substituição do arquivo de log inteiro do usuário. Troca um erro raro por perda de configuração.
3. **Registrar o channel em runtime** — funciona e esconde do usuário, no arquivo de config, que
   existe um log de instalação.

### Consequências

- **Positivas**: `config/logging.php` não é tocado; nenhum helper novo; reinstalar em projeto
  antigo não estoura por definição.
- **Negativas**: os registros de instalação ficam misturados ao log geral. Para uma feature que
  roda **uma vez na vida do projeto**, o custo de filtrar por `[KitInstall@` é aceitável.
- **Riscos**: se um dia a instalação desatendida passar a customizar (por flags na linha de
  comando, por exemplo), o argumento acima cai e o channel volta a fazer sentido.

### Referências

- `app/Console/Commands/KitUpdate.php` → `CAMINHOS_DO_KIT`
- `config/logging.php:85-108` — os channels `ai`, `tenancy` e `autenticacao`

---

## ADR-06: Banco inacessível pula migrate e seed em vez de falhar em cascata

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

O `kit:install` tem um princípio explícito no docblock: nenhum passo aborta a instalação; o que
falha vira aviso com a instrução de como refazer. Com a escolha de Postgres ou MySQL, surge um modo
de falha novo que ele não previa: o servidor de banco pode simplesmente não estar de pé — no caso
do Postgres, é o caso **normal**, porque o container ainda não subiu.

Sem tratamento, `migrate` falha, `db:seed` falha em seguida pelo mesmo motivo, e o usuário recebe
duas stack traces de PDO e dois avisos que dizem a mesma coisa.

### Decisão

Antes de migrar, quando o driver não é sqlite, abrir a conexão (`DB::connection()->getPdo()`) num
`try`. Falhou: um aviso só, acionável (`docker compose up -d` para pgsql; conferir credenciais e
criar o banco, para mysql), e migrate/seed são **pulados**. O resto da instalação — assets,
front-end, banner — segue.

### Alternativas Consideradas

1. **Deixar falhar** — duas exceções de PDO no meio do output de instalação, e o usuário sem saber
   se o projeto instalou ou não.
2. **Subir o container sozinho** (`docker compose up -d` automático) — instalação que executa
   Docker por conta própria é surpresa, pode baixar imagens de vários GB e não funciona para quem
   escolheu MySQL. Vira sugestão, não ação.
3. **Perguntar host/porta/usuário/senha** — quatro prompts a mais para cobrir um caso que o
   `.env` resolve em dez segundos. Contra RQ-11/RQ-12.

### Consequências

- **Positivas**: mensagem única e acionável; o projeto fica instalado e pronto para um
  `php artisan migrate --seed` depois de subir o serviço.
- **Negativas**: o projeto termina a instalação com o banco vazio — o banner precisa dizer isso com
  todas as letras, senão o usuário abre `/app` e leva erro de tabela inexistente.
- **Riscos**: um falso negativo na conferência (timeout de rede lento) pularia migrations
  desnecessariamente. Aceito: o comando de correção é uma linha.

### Referências

- `app/Console/Commands/KitInstall.php:12-19` — o princípio de "nenhum passo aborta"
- `.env.docker` + `docker-compose.yml:25-43` — os valores de Postgres que o instalador escreve

---

## ADR-07: E-mail e provider de IA ficam fora das perguntas

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

RQ-11 pede que se pense em itens adicionais. Os dois candidatos naturais eram `MAIL_MAILER`
(log / Mailpit / SMTP) e `AI_PROVIDER` (llama.cpp local / nenhum / SaaS com API key).

### Decisão

Ficam de fora.

### Alternativas Consideradas

1. **Perguntar e-mail** — a resposta útil (SMTP real) exige host, porta, usuário, senha e
   remetente: cinco prompts, ou uma resposta que não configura nada. Mailpit depende do profile
   `mail` do compose, que o usuário sobe quando quiser. O default `log` já é o certo para o dia 1,
   e o README documenta a troca.
2. **Perguntar IA** — a opção que muda algo de verdade é "SaaS + API key", e pedir chave de API num
   prompt de instalação é pedir para ela vazar no histórico do shell. O default (llama.cpp local)
   já é o do kit, e trocar é uma linha de `.env`.

### Consequências

- **Positivas**: cinco perguntas em vez de sete; nenhuma credencial de terceiro digitada no
  terminal.
- **Negativas**: quem quer Mailpit ou OpenAI ainda edita o `.env` — mas os dois já estão
  documentados no `.env.example` e no `.env.docker`.
- **Riscos**: se o usuário pedir depois, os dois entram como perguntas condicionais sem redesenho
  (a lista de perguntas é uma sequência, não uma máquina de estados).

### Referências

- `.env.example` e `.env.docker` — blocos de e-mail e IA já comentados
- `00-requisito.md` → RQ-11

---

## ADR-08: A senha do admin usa `password()` com "Enter mantém o default"

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

`Laravel\Prompts\password()` não aceita `default` — por desenho, já que o valor digitado não é
exibido. Mas RQ-12 exige que "Enter em tudo" produza uma instalação válida, e o default do kit
(`password`) precisa continuar valendo para quem não quer decidir isso agora.

### Decisão

`password('Senha do administrador', hint: 'Enter mantém a senha padrão (password)')`, com
`required: false`; resposta vazia mantém o default de `config/kit.php`. A senha **nunca** entra no
log (o context leva `admin_email`, não `admin_password`) e, no resumo, aparece mascarada — com a
mesma advertência que o README já dá sobre trocar antes de expor o ambiente.

### Alternativas Consideradas

1. **`text()` com default visível** — mostra a senha na tela e a deixa no scrollback do terminal,
   inclusive em gravação de sessão e em log de CI.
2. **Não perguntar senha** — deixaria todo projeto do kit nascendo com `password`, que é o problema
   que o item 8 do README pede para resolver.

### Consequências

- **Positivas**: senha nunca é exibida nem registrada; Enter segue instalando.
- **Negativas**: quem digita errado não vê o erro — mitigado pelo resumo, que informa que a senha
  foi alterada, e pela troca em `/admin` → Meu perfil.
- **Riscos**: nenhum relevante.

### Referências

- `vendor/laravel/prompts/src/helpers.php:78` — assinatura de `password()`
- `config/kit.php:131-135` — `kit.admin.*`
- `README.md` → "Acesso de demonstração", o aviso de trocar a senha

---

## ADR-09: A estrela segue o modelo do Pest, com duas diferenças

**Status**: Aceita
**Data**: 2026-08-16

### Contexto

RQ-13 e RQ-14 pedem a opção de dar estrela, no modelo do Pest. O
`vendor/pestphp/pest/src/Console/Thanks.php` faz: verifica `PEST_NO_SUPPORT` e
`input->isInteractive()`, pergunta com `ConfirmationQuestion`, imprime a lista de links de qualquer
jeito e, no "sim", abre o navegador com `open` (Darwin), `start` (Windows) ou `xdg-open` (Linux).

### Decisão

Mesmo desenho, com duas diferenças deliberadas: (a) desligamento por `--no-support` — **só** a
opção de comando, que é o idioma da casa, e não também uma variável de ambiente equivalente, que
seria um segundo interruptor para o mesmo botão (corte da auditoria Ponytail); (b) a URL do
repositório é impressa **sempre**, inclusive sem TTY, para quem lê o output depois.

### Alternativas Consideradas

1. **Abrir o navegador sem perguntar** — abrir janela sem consentimento no fim de uma instalação é
   invasivo, e em servidor sem ambiente gráfico é um `exec` que falha calado.
2. **Só imprimir o link** — atende menos que RQ-13, que pede a *opção* de dar a estrela.

### Consequências

- **Positivas**: comportamento reconhecível para quem já usa Pest; desligável em CI.
- **Negativas**: `exec()` de comando do SO é intestável em CI — o CT cobre a decisão
  (perguntou/não perguntou, respeitou a flag), não a abertura do navegador.
- **Riscos**: o `start` do Windows precisa de aspas quando a URL tem `&` — a URL do kit não tem, e
  ela é constante, não entrada do usuário.

### Referências

- `vendor/pestphp/pest/src/Console/Thanks.php:41-81`
- `https://github.com/gsferro/filament-starter-kit-easy`
