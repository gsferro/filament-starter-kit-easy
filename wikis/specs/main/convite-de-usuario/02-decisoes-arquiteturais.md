# Decisões Arquiteturais — Convite de usuário

## ADR-01: A tela de aceite é a página de registro nativa do Filament

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O aceite do convite precisa de uma tela pública onde a pessoa define a própria senha. O
reflexo é criar a peça: uma rota em `routes/web.php`, um controller, uma view Blade, um
`FormRequest`. O kit tem `routes/web.php` com sete linhas e uma rota só (`routes/web.php:5-7`)
— acrescentar um fluxo de autenticação ali seria a primeira superfície HTTP fora do Filament.

Investigando o que já existe: `Filament\Auth\Pages\Register`
(`vendor/filament/filament/src/Auth/Pages/Register.php:41`) entrega, pronto:

| O que | Onde |
| --- | --- |
| rate limit por IP (2 tentativas) | `:73`, trait `WithRateLimiting` |
| rate limit por e-mail (`filament-register:{sha1}`) | `:80, :129-151` |
| transação envolvendo criação e relações | `:84-102` |
| ganchos `mutateFormDataBeforeRegister` e `handleRegistration` | `:91, :95` |
| evento `Registered` | `:104` |
| auto-login + `session()->regenerate()` | `:108-110` |
| senha hasheada e validada por `Password::default()` | `:226-228` |

E a rota já é pública por construção: `routes/web.php:54-57` do Filament fica dentro do
`->prefix($panel->getPath())` (`:30`) e fora do `Route::middleware($panel->getAuthMiddleware())`
(`:60`).

O Auth Designer, por sua vez, já tem uma página `Register` estilizada
(`vendor/caresome/filament-auth-designer/src/Pages/Auth/Register.php:10`) que estende a do
Filament — o mesmo split de mídia do login, de graça.

### Decisão

A tela de aceite é `App\Filament\Pages\Auth\RegistroPorConvite`, subclasse da página do
Auth Designer, ligada ao painel `app` (que é o `->default()`, `AppPanelProvider.php:53`).

**Registro e convite passam a ser a mesma coisa**: não existe cadastro sem convite. A guarda
vive em `mount()` — sem token válido na query string, a página nem chega a renderizar o
formulário. O kit escreve dois métodos de comportamento (`mount()` e `handleRegistration()`)
e dois de apresentação.

Zero rota, zero controller, zero Blade, zero `FormRequest`.

### Alternativas Consideradas

1. **Rota + controller + view próprios.** Descartada: reimplementaria rate limit, transação,
   hash de senha, auto-login e a arte da tela — cinco coisas que o Filament já faz e que
   erram em silêncio quando refeitas. E o kit passaria a ter duas telas de autenticação com
   aparências diferentes.
2. **Um `Filament\Pages\Page` customizado**, fora do fluxo de auth. Descartada: uma página de
   painel exige autenticação para ser alcançada, que é o oposto do necessário. Fazer o
   contrário significaria mexer no `authMiddleware` do painel — abrir uma exceção no
   middleware de auth para uma rota é como se cria buraco de segurança.
3. **Registrar o registro nos três painéis.** Descartada: `/admin` e `/infra` governam a
   instalação. Quem administra ou opera infraestrutura não nasce de link em e-mail; nasce
   por decisão de um `master_global` na tela de usuários. O convite entrega papel de
   qualquer painel, mas a **porta** é uma só, e é a do painel de negócio.
4. **Usar `tenantRegistration()` do Filament.** Descartada: é a tela de "crie sua
   organização", não a de "entre nesta organização". O `AppPanelProvider.php:188-190` já
   documenta por que ela fica desligada — ligá-la deixaria qualquer autenticado criar
   tenants.

### Consequências

- **Positivas**: a superfície nova é uma classe. Rate limit, transação e auto-login vêm
  testados pelo Filament. A tela nasce com a arte do login.
- **Negativas**: o painel `/app`, que era 100% autenticado, ganha uma rota pública. Toda a
  segurança dessa rota está na guarda do `mount()` — uma condição só, num método só. É por
  isso que ela tem três CTs em vez de um (CT-02, CT-03, CT-04).
- **Riscos**: ligar `hasRegistration()` tem um efeito colateral em outra tela — o login passa
  a exibir "Cadastre-se" (`vendor/filament/filament/src/Auth/Pages/Login.php:445-455`).
  Tratado no passo 5 do plano; sem isso, o kit ofereceria na UI um caminho que sempre recusa,
  o que `wikis/convencoes.md:84` classifica como bug.

### Referências

- `vendor/filament/filament/src/Auth/Pages/Register.php:41-159`
- `vendor/filament/filament/routes/web.php:54-57`
- `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:255-260, 635-638`
- Refinada por: ADR-02, ADR-03, ADR-06

---

## ADR-02: Segurança do token — hash em repouso, uso único, prazo e resposta uniforme

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O token do convite **é a credencial**. Quem o tem cria uma conta com o papel e a organização
que o convite carrega — inclusive papel de `/admin`, se for esse o convite. Não há usuário
autenticado a autorizar: a posse do token **é** a autorização. Isso põe o token no mesmo
patamar de um token de reset de senha, e todas as decisões abaixo derivam disso.

Quatro perguntas independentes:

1. o token vai para o banco em claro ou hasheado, e com qual algoritmo?
2. vale uma vez ou muitas?
3. vale para sempre?
4. a tela pode dizer **por que** recusou?

### Decisão

**1. Hasheado com `hash('sha256', $token)`, `Str::random(64)` na origem.**

- O token em claro existe em três lugares: a variável local de `Convite::enviar()`, o corpo
  do e-mail e o link no navegador de quem foi convidado. Em nenhum outro.
- No banco fica o hash. **Um dump de banco vazado não vira acesso**: quem lê `convites` tem
  o digest, e reverter sha256 de 64 caracteres aleatórios não é operação que exista.
- **Por que sha256 e não `Hash::make()` (bcrypt/argon)**, que é o que o Laravel usa em
  `password_reset_tokens`: bcrypt tem sal por invocação, então o mesmo token gera digests
  diferentes e **não é pesquisável por índice**. O fluxo do reset de senha contorna isso
  porque a busca é por e-mail, e o hash só é comparado depois. Aqui a chave de busca **é** o
  token, e nada mais: o link não carrega o e-mail (carregá-lo criaria um oráculo de quem foi
  convidado). Hash determinístico é requisito funcional.
- **E o argumento de "bcrypt é mais lento, logo mais seguro"?** Não se aplica. O custo do
  bcrypt existe contra força bruta sobre segredos de baixa entropia (senhas humanas).
  `Str::random(64)` usa `random_bytes` e entrega ~380 bits de entropia sobre o alfabeto
  alfanumérico: não há dicionário nem tabela a construir. Um KDF lento aqui compraria
  latência sem comprar segurança.
- Coluna `unique`, 64 chars — o tamanho exato do sha256 em hexadecimal.

**2. Uso único, por `aceito_em`.** `Convite::valido()` filtra `whereNull('aceito_em')`, e
`Convite::aceitar()` carimba `aceito_em` na **mesma transação** que cria o usuário
(`Register.php:84-102` abre a transação). Ou o usuário nasce e o convite morre, ou nenhum dos
dois. Um link reencaminhado depois do aceite não cadastra uma segunda pessoa.

**3. Prazo, por `expira_em`.** Default de 7 dias, em `kit.convites.validade_em_dias`. Um
convite sem prazo é uma credencial permanente esquecida numa caixa de entrada; um prazo curto
demais transforma reenvio em rotina. O prazo é configurável justamente porque o ponto certo é
do projeto, não do kit.

**4. Resposta uniforme na recusa — e é o ponto mais sutil.** Token inexistente, token
expirado e token já usado produzem **exatamente** a mesma tela, a mesma notificação e a mesma
linha de log (`motivo => 'convite_invalido'`). É por isso que `Convite::valido()` devolve
`?self` e não um enum de motivo: se o motivo existisse no retorno, alguém o exibiria "para
ajudar o usuário", e "este convite já foi usado" confirma que o convite existiu.

**5. O token nunca vai para o log.** Nem em claro, nem hasheado, nem em prefixo — prefixo de
segredo é segredo parcial, e o `autenticacao.log` é legível na tela do Logs Explorer do
`/infra`. Correlaciona-se por `convite_id`. A regra vem do cabeçalho do próprio arquivo de
config: "nunca logar conteúdo de prompt/notificação em claro; identificadores sempre
mascarados" (`config/logging.php:80-81`).

**6. O token nunca entra na auditoria.** `AuditsFillables::getAuditInclude()` devolve o
`$fillable` (`wikis/convencoes.md:30-41`), e `token` está fora dele. O mesmo mecanismo que
protege o `uuid` da convenção protege o hash aqui — de graça, sem código.

**7. Rate limit: o herdado.** Duas tentativas por IP (`Register.php:73`) e duas por e-mail
(`Register.php:80, :129-151`). Não se acrescenta um terceiro limitador: o token não é
adivinhável por varredura, então o limite existe contra abuso do formulário, não contra
brute force do token — e para isso o do Filament basta.

**8. Por que o convite não vira enumeração de e-mails cadastrados.** Duas superfícies,
tratadas de forma diferente **de propósito**:

| Superfície | Quem alcança | Comportamento | Por quê |
| --- | --- | --- | --- |
| Tela de aceite (`/app/register`) | qualquer um, público | o e-mail **não é digitado** — vem do convite, e a autoridade é o servidor (`mutateFormDataBeforeRegister()`). Não há campo onde testar endereços, e a recusa é uniforme | uma tela pública nunca deve responder "este e-mail existe" |
| Formulário de criar convite (`/admin/convites/create`) | administrador autenticado, com a permission `Create:Convite` | o campo tem `->unique('users', 'email')` e diz "já cadastrado" | quem já pode abrir `/admin/users` e buscar pelo e-mail não aprende nada novo. Esconder aqui seria teatro, e faria o administrador criar convites que falhariam no aceite |

O aceite ainda checa `User::where('email', ...)->exists()` antes de criar
(`Convite::aceitar()`, passo 2c do plano): entre o convite e o clique podem passar dias, e o
e-mail pode ter virado usuário por outro caminho. A recusa aí é registrada no log e devolvida
como erro — não como "escolha outro e-mail".

### Alternativas Consideradas

1. **Token em claro na coluna.** Descartada: transforma leitura do banco em criação de conta
   com papel arbitrário. É a diferença entre um vazamento de dados e um vazamento de acesso.
2. **`Hash::make()` (bcrypt).** Descartada: sal por invocação impede o lookup por índice, e o
   único contorno seria carregar o e-mail no link — o que cria o oráculo que a decisão 4
   existe para evitar. O ganho do KDF é nulo sobre segredo de alta entropia.
3. **JWT / URL assinada (`URL::temporarySignedRoute`).** Tentador: expiração embutida, nada
   no banco. Descartada por **não ter revogação**. Uma URL assinada vale até expirar,
   independentemente do que o administrador faça depois; revogar exigiria uma lista de
   revogados — que é a tabela que estaríamos evitando, só que pior. Uso único também exigiria
   estado. E o payload assinado é legível: o papel e a organização ficariam expostos no link.
4. **Token só com prazo, sem uso único.** Descartada: o link fica na caixa de entrada. Um
   e-mail encaminhado dentro da janela criaria uma segunda conta com o mesmo papel.
5. **Mensagem específica por motivo de recusa** ("expirado" / "já usado"). Descartada pelo
   argumento da decisão 4. O custo de UX é real e foi pago conscientemente: a notificação diz
   o que fazer ("peça um convite novo"), que é a informação de que a pessoa precisa.
6. **`Str::uuid()` como token.** Descartada: UUID v4 tem 122 bits de aleatoriedade e é
   reconhecível como identificador — convida a ser tratado como id em log, em URL de suporte,
   em ticket. `Str::random(64)` não se parece com nada além de um segredo.

### Consequências

- **Positivas**: banco vazado não vira acesso; convite revogável a qualquer momento (é uma
  linha); uso único e prazo são duas colunas e um `where`; a resposta uniforme não custa
  código nenhum — custa uma decisão de não escrever o `else`.
- **Negativas**: quem recebeu um convite vencido não descobre pela tela que ele venceu, e
  precisa pedir outro. O administrador enxerga a situação real na listagem do `/admin`, então
  a informação existe — do lado autorizado.
- **Riscos**: o token em claro trafega por e-mail, que não é canal seguro. É a mesma
  exposição do reset de senha de qualquer aplicação, mitigada pelo prazo curto e pelo uso
  único. Um segundo risco, específico da fila, está registrado em ADR-05.

### Referências

- `config/logging.php:76-83` (a regra LGPD dos canais)
- `wikis/convencoes.md:30-41` (`AuditsFillables` devolve o `$fillable`)
- `vendor/filament/filament/src/Auth/Pages/Register.php:73, 80, 129-151` (os rate limits)
- CT-02, CT-03, CT-04, CT-08, CT-10 travam cada uma das decisões
- Refina: ADR-01

---

## ADR-03: Convite inválido redireciona para o login, não responde 403

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Recusado o token, a página precisa devolver alguma coisa. Duas leituras defensáveis:

- **403**: é uma negativa de autorização, e o kit já tem uma tela de 403 do
  `anselmokossa/filament-sentinel` (`wikis/arquitetura.md:194-197`);
- **redirect para o login** com notificação.

Do ponto de vista de vazamento de informação **as duas são equivalentes**: a resposta é a
mesma para os três motivos de recusa (ADR-02, decisão 4), e não há nada a enumerar — o token
é aleatório e o e-mail não aparece na URL. Então a escolha é de produto, não de segurança.

Quem chega nessa tela é uma pessoa **de fora**, que clicou num link de e-mail, muitas vezes
dias depois. Três situações reais levam ao mesmo lugar: o convite expirou; o convite já foi
aceito e a pessoa reabriu o link antigo; a pessoa já tinha conta. Nos três, o destino
correto é o **login** — no segundo e no terceiro ela literalmente já tem credenciais.

### Decisão

Redirect para `Filament::getPanel('app')->getLoginUrl()`, com uma
`Filament\Notifications\Notification` `->danger()->persistent()` explicando que o convite é
inválido ou expirou e que basta pedir outro — mais a dica de que quem já tem conta pode
entrar ali mesmo.

A saída usa `throw new HttpResponseException(new RedirectResponse($url))`, **não**
`redirect()` solto. Isto não é preferência: é a armadilha que o kit já pagou. O
`TelaBloqueio::mount()` documenta em `app/Filament/Pages/Auth/TelaBloqueio.php:74-85` que
`redirect()` dentro de `mount()` de página Livewire devolve o Redirector do Livewire onde o
Laravel espera um código HTTP, e o request morre em 500. O padrão correto está em
`TelaBloqueio.php:99-102` e é reusado literalmente.

Vale notar que o próprio `Register::mount()` do Filament faz `redirect()` solto
(`vendor/filament/filament/src/Auth/Pages/Register.php:60`) para o caso "já autenticado". Não
se copia esse jeito.

### Alternativas Consideradas

1. **`abort(403)`.** Descartada: é um beco. A pessoa não tem sessão, não tem link de saída, e
   a tela de 403 do kit mostra diagnóstico de permissão fora de produção
   (`wikis/arquitetura.md:196`) — diagnóstico de permissão para quem nem é usuário.
2. **`abort(404)`,** por analogia com o 404 do tenant não vinculado
   (`wikis/arquitetura.md:184-186`). Descartada: lá o 404 existe para não confirmar que um
   slug de cliente existe. Aqui não há nada a confirmar — o token não é enumerável, e a
   rota `/app/register` existe publicamente de qualquer forma. Seria cargo cult da decisão
   certa em outro lugar.
3. **Renderizar a própria tela com uma mensagem de erro no lugar do formulário.** Descartada:
   deixa uma tela de cadastro pública renderizada como estado normal, e cria um segundo
   caminho de render para manter. O redirect resolve com uma exceção.

### Consequências

- **Positivas**: a pessoa acaba onde precisa estar nos três cenários; nenhuma tela nova; o
  padrão de saída é o que o kit já usa e já testou.
- **Negativas**: um redirect é menos explícito que um status HTTP para quem depura por
  `curl` — o CT-02 assere `assertRedirect()`, então o contrato fica travado no teste.
- **Riscos**: a notificação depende da sessão sobreviver ao redirect. É o mecanismo padrão do
  Filament e o CT-02 confere que a tela de destino é o login; se a notificação sumir num
  upgrade, o redirect continua correto.

### Referências

- `app/Filament/Pages/Auth/TelaBloqueio.php:74-85, 99-102`
- `vendor/filament/filament/src/Auth/Pages/Register.php:60`
- `wikis/arquitetura.md:184-186` (o 404 do tenant, que é o caso diferente)
- Refina: ADR-01, ADR-02

---

## ADR-04: Convite é imutável — revogar e criar outro, nunca editar

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O reflexo de todo Resource do Filament é `index` + `create` + `edit`. Um convite tem cinco
campos que alguém gostaria de editar: e-mail, papel, organização, prazo e "estado".

Mas o convite já foi **enviado**. Existe um e-mail na caixa de entrada de alguém com um link
funcionando. Editar o papel de um convite pendente significa que a pessoa que clicar vai
receber um papel diferente do que o e-mail dela anunciou — e não há como saber, do lado de
cá, se ela já leu. Editar o e-mail é pior: o link continua válido no endereço antigo, agora
apontando para um convite que diz outro endereço.

O convite não é um cadastro. É um **ato**, com data e destinatário, como uma mensagem
enviada.

### Decisão

`ConviteResource::getPages()` devolve apenas `index` e `create`. Sem `EditConvite`, sem
`ViewConvite`.

As duas operações sobre um convite pendente são:

- **Revogar** — o `DeleteAction` nativo, relabelado. Apaga a linha; o link para de funcionar
  no mesmo instante, porque `Convite::valido()` não encontra mais nada.
- **Reenviar** — `Convite::enviar()` de novo, o que gera token novo, sobrescreve o hash,
  renova `expira_em` e zera `aceito_em`. **O link anterior morre**, porque a coluna que ele
  casaria foi sobrescrita. Reenviar é, na prática, revogar e emitir num passo — e é por isso
  que a modal de confirmação diz exatamente isso.

Errou o papel? Revogue e crie outro. São dois cliques, e o resultado é um convite novo, com
link novo, para um destinatário que recebe a informação correta.

**Revogar é `DELETE`, não uma coluna `revogado_em`.** A trilha não se perde: `Convite`
implementa `Auditable` com `AuditsFillables`, e o `owen-it/laravel-auditing` registra o
evento de exclusão com os atributos — visível em `/infra/audits`
(`wikis/convencoes.md:30-41`). E como `token` está fora do `$fillable`, a trilha guarda quem,
quando e para qual e-mail, **sem** guardar o hash da credencial.

### Alternativas Consideradas

1. **`edit` completo.** Descartada pelo argumento do contexto: cria divergência entre o que o
   e-mail diz e o que o convite concede, sem nenhuma forma de detectá-la.
2. **`edit` só do prazo** ("estender validade"). Descartada como meio-caminho: é reenviar com
   outro nome, mas **sem** trocar o token — ou seja, estende a vida de uma credencial que já
   circulou. Reenviar é estritamente melhor, porque o link antigo morre.
3. **Coluna `revogado_em` (soft delete manual).** Descartada: um terceiro estado a filtrar em
   `Convite::valido()`, em toda listagem e em todo relatório, para guardar uma informação que
   a auditoria já guarda. Se um dia "revogados" precisarem aparecer na listagem, `SoftDeletes`
   entra sem tocar em `valido()` — o `whereNull('deleted_at')` é global.
4. **`ViewConvite`.** Descartada: a listagem já mostra os cinco campos. Uma tela de detalhe
   para cinco campos é navegação a mais.

### Consequências

- **Positivas**: o que o destinatário leu e o que o sistema concede nunca divergem. Três
  arquivos a menos (a página de edição, a de visualização e a lógica de "o que fazer com o
  link antigo"). A revogação é auditada sem uma linha de código.
- **Negativas**: corrigir um papel errado custa revogar + recriar, e o destinatário recebe
  dois e-mails. É o preço de o segundo e-mail estar certo.
- **Riscos**: alguém do time acrescenta `EditConvite` por hábito ("todo Resource tem edit").
  Mitigação: a razão está no PHPDoc do Resource, onde quem for acrescentar a página lê antes.
  **Sem CT para isso**: um teste sobre `getPages()` asseriria um literal escrito no mesmo
  commit — não é comportamento, é declaração.

### Referências

- `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php:11-15` (precedente do kit:
  Resource que remove ação por decisão de domínio, com o porquê no PHPDoc)
- `wikis/convencoes.md:30-41` (auditoria pelo `$fillable`)
- CT-09

---

## ADR-05: `Notification` enfileirável, não `Mailable` nem `Job`

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O convite precisa sair por e-mail para alguém que **ainda não é um `User`**. Isso já elimina
o caminho mais comum (`$user->notify(...)`), e abre três opções: `Mailable` com
`Mail::to($email)->send()`, `Notification` com `Notification::route('mail', $email)->notify()`,
ou um `Job` que faça uma das duas.

### Decisão

`App\Notifications\ConviteDeAcesso extends Notification implements ShouldQueue`, disparada
por `Notification::route('mail', $this->email)->notify(...)` — a rota on-demand do Laravel,
feita exatamente para destinatário que não é um model notificável.

**Sem Job.** Uma `Notification` com `ShouldQueue` **já é** um job: o Laravel a embrulha em
`SendQueuedNotifications`. Um Job que chamasse a notificação seria um job despachando um job.

**Sem `Mailable`.** A `Notification` dá o `MailMessage` com `->action()` pronto (o botão do
convite), o template já traduzível, e deixa a porta aberta para um canal `database` ou
`broadcast` no futuro sem trocar a classe.

**Sem `->onQueue()` fixo.** Fila nomeada obrigaria o kit a documentar `queue:work --queue=...`
num README que já pede bastante do leitor.

O que acontece em cada connection:

| `QUEUE_CONNECTION` | Onde é o default | Comportamento |
| --- | --- | --- |
| `database` | `.env.example:42` e `config/queue.php:16` — **o modo real do kit** | vira linha em `jobs`. **Sem worker, o convite não sai.** O `composer dev` sobe um; um deploy sem worker convida em silêncio, e a fila parada aparece no `filament-jobs-monitor` do `/infra` |
| `sync` | `phpunit.xml` (`QUEUE_CONNECTION=sync`) | envia inline, no request. É o que permite aos CTs verem a notificação sem worker |

> Correção de premissa comum: o kit **não** roda `sync` por padrão. `sync` é o ambiente de
> teste; o `.env.example` traz `database`.

### Alternativas Consideradas

1. **`Mailable` + `Mail::to($email)->send()`.** Funciona. Descartada por entregar menos pelo
   mesmo esforço: sem `->action()` pronto, sem multi-canal, e o kit não tem nenhum
   `Mailable` — a convenção seria nova.
2. **Job dedicado (`EnviarConviteJob`).** Descartada: `ShouldQueue` já é o job. Um wrapper só
   acrescentaria um lugar onde as tentativas e o backoff podem discordar do outro.
3. **Envio síncrono (sem `ShouldQueue`).** Tem um mérito real — ver os riscos abaixo — e o
   argumento de que a falha de SMTP apareceria na hora, na ação do Filament, em vez de num
   job falhado. Descartada porque prender o request do administrador a um handshake SMTP é o
   tipo de lentidão que se descobre em produção, e porque o kit já tem monitor de fila no
   `/infra`. Projeto com exigência dura sobre o risco abaixo troca uma palavra na assinatura
   da classe.

### Consequências

- **Positivas**: uma classe, sem Job, sem Mailable; falha de SMTP não derruba a criação do
  convite (a linha fica no banco e o reenvio existe); multi-canal é uma linha em `via()`.
- **Negativas**: o kit passa a depender de um worker rodando para funcionar de verdade.
  Precisa estar no README com todas as letras — e a nota tem de dizer `database`, não `sync`.
- **Riscos**: **o token em claro é serializado no payload do job.** Com
  `QUEUE_CONNECTION=database` ele fica legível na tabela `jobs` até o worker processar. É uma
  janela curta (a linha é removida ao completar) e quem lê `jobs` já lê `convites` — mas
  enfraquece, dentro dessa janela, a promessa de ADR-02 de que "o banco só tem o hash". Um
  projeto que trate isso como inaceitável remove `implements ShouldQueue`: a notificação passa
  a ser síncrona, o token nunca é serializado, e nada mais no plano muda.
- **Riscos**: convite revogado antes de o worker rodar faz o job falhar com
  `ModelNotFoundException` — que é o comportamento **desejado**: convite revogado não deve
  ser entregue. Aparece como job falhado no `/infra`, não como e-mail indevido.

### Referências

- `.env.example:42, 56` (`QUEUE_CONNECTION=database`, `MAIL_MAILER=log`)
- `config/queue.php:16`, `config/mail.php:17`
- `phpunit.xml` (`QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`)
- CT-01 e CT-10
- Refina: ADR-02

---

## ADR-06: O registro se liga pelo `AuthDesignerPlugin`, não por `$panel->registration()`

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Há duas formas de apontar o painel `app` para a página de aceite:

```php
// A — direto no painel
$panel->registration(RegistroPorConvite::class);

// B — pelo plugin
AuthDesignerPlugin::make()
    ->registration(fn (AuthPageConfig $config) => $config->usingPage(RegistroPorConvite::class)->media(...))
```

A forma A é mais curta e é a API nativa do Filament
(`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:255-260`). Pela escada do Ponytail,
seria a escolhida.

**Ela está errada aqui**, e a leitura do vendor mostra por quê. O `AuthDesignerPlugin` faz
duas coisas em momentos diferentes:

```php
public function register(Panel $panel): void {
    if ($this->hasRegistration()) {                                   // AuthDesignerPlugin.php:33
        $panel->registration($this->getRegistrationPageClass());      // :34
    }
}

public function configureRepository(?string $panelId = null): void {
    if ($this->hasRegistration()) {                                   // :92
        $repository->setPageConfig('registration', $this->buildPageConfig($this->registrationConfigurator), $panelId);  // :93
    }
}
```

A flag `hasRegistration()` nasce `false` (`src/Concerns/HasPages.php:20`) e só vira `true` em
`HasPages::registration()` (`:44-46`). Pela forma A ela **nunca** vira `true`: a rota é
registrada, mas a chave `registration` do `AuthDesignerConfigRepository` nunca é gravada.

E o que acontece então **não é um erro**:

```php
$pageConfig = $this->getPageConfig($page, $panelId) ?? new AuthPageConfig;   // AuthDesignerConfigRepository.php:80
```

Sem a chave, o repositório devolve uma config vazia. A tela renderiza, com o layout certo,
mas **sem mídia, sem tamanho e sem alternador de tema** — visivelmente diferente do login ao
lado, e sem uma linha no log. É uma falha de vitrine que só é descoberta abrindo a página.

### Decisão

Forma B. O registro passa pelo `AuthDesignerPlugin`, no bloco `plugins()` do
`AppPanelProvider`, com a mesma configuração de mídia do login. O plugin chama
`$panel->registration(...)` por baixo (`AuthDesignerPlugin.php:34`), então a rota nativa nasce
igual — mais a config da tela.

A página customizada se aponta por `AuthPageConfig::usingPage()`
(`src/Data/AuthPageConfig.php:57`), lida em `HasPages::getRegistrationPageClass()` (`:119-124`),
que faz fallback para a `Register` do próprio pacote.

Pelo mesmo motivo, `TelaLogin` (a subclasse que remove o "Cadastre-se", ADR-01) entra por
`->login(fn ($config) => $config->usingPage(TelaLogin::class)->media(...))`, e não trocando a
classe de login no painel.

### Alternativas Consideradas

1. **`$panel->registration(...)` direto.** Descartada pelo argumento acima: tela sem estilo,
   em silêncio.
2. **Forma A + `AuthDesignerPlugin::make()->registration()` sem configurador**, só para ligar
   a flag. Descartada: duas chamadas dizendo a mesma coisa, e a última a rodar decide qual
   classe vale — exatamente o tipo de dupla fonte de verdade que o kit já pagou caro
   (`tests/TestCase.php:15-31` documenta o bug das três chaves que precisavam concordar).
3. **Publicar a view do layout e resolver a config à mão.** Descartada: cópia de vendor para
   contornar uma chamada de API.

### Consequências

- **Positivas**: uma chamada faz rota **e** estilo; a tela de aceite nasce idêntica ao login;
  a config fica ao lado da do login, no mesmo bloco, onde alguém que mudar a arte vai
  encontrar as duas.
- **Negativas**: o kit passa a depender de um detalhe de ordem interna do plugin (a flag que
  governa `register()` e `configureRepository()` ao mesmo tempo). Se o pacote separar as duas,
  a config volta a ficar vazia — em silêncio, de novo.
- **Riscos**: CT-13 é o que acusa. Ele assere `fi-auth-layout` na tela de aceite **e** que uma
  página comum do painel não o tem depois — o par exigido por `.ai/rules/auth.md:13`.

### Referências

- `vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:27-51, 79-109`
- `vendor/caresome/filament-auth-designer/src/Concerns/HasPages.php:20, 44-46, 119-124`
- `vendor/caresome/filament-auth-designer/src/Data/AuthPageConfig.php:57`
- `vendor/caresome/filament-auth-designer/src/AuthDesignerConfigRepository.php:78-88`
- `.ai/rules/auth.md`
- Refina: ADR-01

---

## ADR-07: O contexto de atribuição do papel é derivado de `roles.painel`

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Esta é a decisão que faz o convite valer alguma coisa — ou não valer nada, em silêncio.

Com `permission.teams` ligado, `model_has_roles.team_id` é NOT NULL: toda atribuição de papel
pertence a um contexto (`app/Models/Tenant.php:47-61`). E a wiki `perfil-e-acesso-ao-painel`
estabeleceu, em ADR-04, que `User::canAccessPanel()` **exige contextos diferentes conforme o
painel**:

- painel **sem** tenancy (`/admin`, `/infra`): o papel tem de estar em `Tenant::CONTEXTO_GLOBAL`
  — ser `admin` dentro de uma organização não é credencial para administrar a instalação;
- painel **com** tenancy (`/app`): o papel vale em qualquer organização.

O convite carrega um `role_id` e um `tenant_id`. Atribuir no contexto errado produz um
usuário que **entra e leva 403**, ou pior: um usuário com papel de `/admin` atribuído dentro
de uma organização, que parece administrador na tela de papéis e não é. Nenhum dos dois gera
erro; os dois geram chamado de suporte.

### Decisão

O contexto é derivado do painel que o papel declara:

```php
$contexto = $this->papel->painel === 'app'
    ? $this->tenant_id
    : Tenant::CONTEXTO_GLOBAL;
```

- papel de `/app` → contexto é a organização do convite (é onde ele precisa valer);
- qualquer outro papel → `CONTEXTO_GLOBAL`, inclusive papel com `painel` nulo, que não abre
  painel algum (ADR-03 da wiki irmã) e portanto não tem contexto natural;
- sem `permission.teams`, `setPermissionsTeamId()` é inofensivo — o spatie ignora. **Um
  caminho de código para os dois modos**, sem `if (config('kit.tenancy.enabled'))`.

A atribuição é `assignRole()`, dentro de uma troca temporária do `PermissionRegistrar`,
restaurada no `finally`. **Nunca `sync()` na relação** — a armadilha já registrada em
`.ai/rules/filament.md:8-15`: o `sync()` escreve só as colunas da chave e estoura
`NOT NULL constraint failed: model_has_roles.team_id`.

O formulário de criação reforça a mesma regra do outro lado: `tenant_id` é obrigatório quando
o papel escolhido tem `painel = 'app'` e a tenancy está ligada. Sem isso o convite nasceria
com papel de negócio e sem organização, e o aceite atribuiria no contexto global — criando
alguém que entra em `/app` e não enxerga organização nenhuma.

### Alternativas Consideradas

1. **Sempre `CONTEXTO_GLOBAL`.** Descartada: um usuário com `panel_user` global entraria em
   `/app` (o painel aceita qualquer contexto) mas o vínculo de organização e o papel ficariam
   em lugares diferentes — e o dia em que `canAccessPanel()` apertar a regra, todos eles
   perdem acesso de uma vez.
2. **Sempre o `tenant_id` do convite.** Descartada: é exatamente a falha de segurança que
   ADR-04 da wiki irmã existe para impedir. Um convite de `admin` para dentro da Acme criaria
   um administrador da instalação sem que ninguém tivesse decidido isso.
3. **Guardar o contexto numa coluna do convite** (`contexto_papel`). Descartada: terceira
   fonte de verdade para algo que `roles.painel` + `tenant_id` já determinam. Dessincroniza no
   dia em que alguém mudar o `painel` do papel.
4. **Convidar sem papel e atribuir depois.** Descartada: é criar conta morta. O plano da wiki
   irmã tornou o papel obrigatório na criação de usuário justamente por isso — o convite não
   pode ser a porta dos fundos dessa regra.

### Consequências

- **Positivas**: a propriedade de segurança de ADR-04 da wiki irmã sobrevive ao caminho novo;
  a regra é derivada, não declarada num segundo lugar; um caminho para os dois modos.
- **Negativas**: `Convite::aceitar()` precisa mexer no `PermissionRegistrar`, que é estado
  global do container. É o mesmo padrão que os testes do kit já usam
  (`tests/TestCase.php:93-95`) e é restaurado no `finally`.
- **Riscos**: um painel novo com tenancy que não se chame `app` cairia em `CONTEXTO_GLOBAL`
  pela comparação literal. O kit tem três painéis e um só com tenancy; quando houver um
  segundo, a comparação vira `Filament::getPanel($painel)->hasTenancy()` — a mesma fonte que
  `canAccessPanel()` usa. Anotado como `ponytail:` no código.

### Referências

- `app/Models/Tenant.php:47-62`
- `app/Models/User.php:71-82` (após a wiki `perfil-e-acesso-ao-painel`)
- `.ai/rules/filament.md:8-15` (`assignRole()`, nunca `sync()`)
- `wikis/specs/main/perfil-e-acesso-ao-painel/02-decisoes-arquiteturais.md` — ADR-04
- CT-05, CT-11, CT-12
