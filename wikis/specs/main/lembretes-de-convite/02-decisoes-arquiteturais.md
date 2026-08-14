# Decisões Arquiteturais — Lembretes de convite

## ADR-01: O lembrete leva um SEGUNDO token, também hasheado — o link original não é tocado

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Esta é a decisão de que a feature inteira depende, e ela começa como um beco.

`Convite::enviar()` gera `Str::random(64)` e grava **o hash** no banco:

```php
$this->forceFill([
    'token'     => hash('sha256', $token),      // app/Models/Convite.php:130
    'expira_em' => now()->addDays(...),
    'aceito_em' => null,
])->save();
```

O token em claro é devolvido pelo método (`:152`), consumido pela `ConviteDeAcesso`
(`app/Notifications/ConviteDeAcesso.php:33`) e **desaparece**. ADR-02 da wiki
`convite-de-usuario` é explícita sobre o motivo: sha256 não se reverte, então "um dump de banco
vazado não vira acesso" — e a frase está publicada no CHANGELOG: em claro o token existe no
e-mail e em lugar nenhum mais.

Um lembrete que reenviasse **o mesmo** link precisaria do token em claro **dias depois**, dentro
de um comando de cron. Ele não existe em lugar recuperável. Quatro saídas:

1. **Chamar `enviar()` de novo.** É o reflexo, e é o mais errado. `enviar()` gera token novo e
   **sobrescreve a coluna que o link antigo casaria** (`:129-133`) — o e-mail que está na caixa
   de entrada da pessoa passa a dar redirect para o login. Pior: a rotação é uma escrita no
   banco, e a entrega é uma notificação **enfileirada**
   (`app/Notifications/ConviteDeAcesso.php:27`). Se o lembrete cai no spam, volta com bounce, ou
   o worker está parado, o convite foi **efetivamente revogado por um lembrete** — a pessoa fica
   sem nenhum link válido e ninguém é avisado. Um lembrete que pode revogar não é lembrete.
2. **Lembrete sem link** ("você tem um convite pendente, procure o e-mail anterior"). Um
   empurrão sem ação, num e-mail que a pessoa provavelmente não achou — é exatamente o problema
   que a feature existe para resolver.
3. **Guardar o token em claro na coluna.** Desfaz a decisão de segurança da feature de convite,
   e para todo convite do sistema, não só para os que recebem lembrete.
4. **Guardar uma segunda cópia reversível** (cifrada com a `APP_KEY`).

As três primeiras se recusam sozinhas. A quarta funciona — e foi por um tempo a decisão desta
wiki. **Mas a pergunta estava errada.** "Como reenviar o mesmo link?" pressupõe que o link tem
de ser o mesmo. O que a feature precisa é mais fraco: **que nada do que a pessoa já tem seja
invalidado.**

### Decisão

**Um segundo token, também hasheado.** Coluna nova `token_lembrete` (`string(64)`, nullable,
`unique`), no mesmo formato de `token`.

```php
// Convite::lembrar()
$token = Str::random(64);

$this->forceFill([
    'token_lembrete'     => hash('sha256', $token),
    'lembretes_enviados' => $this->lembretes_enviados + 1,
])->save();

Notification::route('mail', $this->email)->notify(new ConviteDeAcesso($this, $token, lembrete: true));
```

O lembrete gera um token novo, grava o hash dele e manda **esse** no link. `token` e `expira_em`
**não são tocados**: o link original continua valendo até o prazo, aconteça o que acontecer com
o lembrete. O argumento que derrubou a saída 1 é preservado inteiro — um lembrete perdido não
revoga nada.

Quatro decisões acompanham:

**1. `Convite::valido()` aceita os dois, com agrupamento explícito.**

```php
->where(fn (Builder $consulta) => $consulta
    ->where('token', $hash)
    ->orWhere('token_lembrete', $hash))
->whereNull('aceito_em')
->whereNull('recusado_em')
->where('expira_em', '>', now())
```

O closure não é estilo. Sem ele o SQL sai como
`WHERE token = ? AND aceito_em IS NULL AND ... OR token_lembrete = ?`, e o `OR` parte
o `WHERE` inteiro: **o token de lembrete passa a valer sozinho, sem prazo e sem estado** — um
convite expirado (ou já aceito) volta a ser aceitável pelo link do lembrete. Nada acusa; a tela
simplesmente aceita. É a armadilha desta ADR, e CT-04 existe só para ela.

O método devolve `?self` como sempre, e **não** diz qual token casou: a autorização é a mesma
nos dois casos, e por isso não existe um segundo método (`validoPorLembrete()` seriam dois
corpos idênticos e um chamador tendo de adivinhar qual link a pessoa clicou).

**2. Cada lembrete sobrescreve `token_lembrete`.** No máximo **dois** links vivos por convite: o
do envio e o do último lembrete, os dois presos ao mesmo `expira_em`. O link do lembrete
anterior morre quando o seguinte sai (CT-03).

**3. `enviar()` limpa `token_lembrete`, e o consumo também.** Reenviar é emitir um convite novo:
ADR-04 da wiki `convite-de-usuario` promete que "o link anterior morre", e sem essa limpeza a
promessa seria mentira pela metade — um lembrete anterior ao reenvio continuaria aceitando.
`aceitar()` (`:281`) grava `['aceito_em' => now(), 'token_lembrete' => null]`, e os dois pontos
de consumo que a wiki `convite-para-usuario-existente` já pôs na árvore —
`aceitarComoUsuarioExistente()` (`:325-329`) e `recusar()` (`:376-380`) — recebem a mesma chave nos
`update` condicionais deles.

**4. `token_lembrete` fora do `$fillable` e dentro do `$hidden`**, exatamente como `token`. Fora
do `$fillable` porque `AuditsFillables::getAuditInclude()` devolve o `$fillable`, e hash de
credencial não entra na trilha de `/infra/audits`; dentro do `$hidden` porque um hash também não
pode aparecer em `toArray()`, num `dd($convite)` ou num `$context` de log que passe o model
inteiro. Hash de credencial não é dado de diagnóstico.

**Nenhum segredo reversível entra no banco.** A frase do CHANGELOG continua verdadeira, agora
para os dois tokens.

### Alternativas Consideradas

1. **Rotacionar o token no lembrete** (saída 1 do contexto). Descartada: um lembrete não
   entregue revoga o convite. Também troca a semântica do prazo — `enviar()` renova `expira_em`,
   então um convite de 7 dias lembrado em D+3 e D+5 duraria 12. E rotacionar **preservando**
   `expira_em` resolve só o prazo: o link antigo continua morrendo, que é o defeito grave.
2. **Segunda cópia cifrada** — `token_cifrado` com `Crypt::encryptString()`, decifrada pelo
   comando para reenviar o link idêntico. **Funciona**, e chegou a ser a decisão desta wiki.
   Descartada quando a saída 4 apareceu, por três razões cumulativas: (a) põe **segredo
   reversível** no banco — quem tiver o dump **e** o `.env` cria contas, e a promessa passaria de
   "o banco só tem o hash" para "o banco tem o hash e uma cifra"; (b) contradiz uma frase **já
   publicada no CHANGELOG**, e enfraquecer uma promessa de segurança publicada por uma feature de
   **conveniência** é a troca errada; (c) traz consigo `Crypt`, um `try/catch` de
   `DecryptException`, um ramo "token não recuperável" no comando, um argumento inteiro sobre não
   usar o cast `encrypted` (que materializaria o claro no atributo), e um modo de falha novo —
   `APP_KEY` rotacionada deixa os convites pendentes sem lembrete. O segundo token hasheado
   entrega a propriedade que importa (nada é revogado) e **apaga todos esses cinco itens**. O
   custo que sobra está nas Consequências, e é um `orWhere`.
3. **Token derivado por HMAC** — `hash_hmac('sha256', $convite->uuid.$convite->expira_em, APP_KEY)`,
   recomputável a qualquer momento, **zero colunas novas**. Recusada por três motivos: (a) o
   token passa a ser função de colunas **mutáveis** — `enviar()` altera `expira_em`, e o dia em
   que alguém editar aquele campo por qualquer outra razão todos os links vivos morrem em
   silêncio; (b) sem entrada aleatória, dois envios no mesmo segundo derivam o mesmo token; (c) é
   clever, e clever é o que alguém decodifica às 3 da manhã.
4. **Guardar o segredo fora de coluna** — `Cache::put($token)` por 7 dias, ou a URL montada em
   vez do token. Descartadas: o cache do kit é descartável por construção (há um botão "Limpar
   cache" no `/infra`, `app/Providers/KitServiceProvider.php:149-158`), e segredo com prazo de 7
   dias não mora em cache; guardar a URL é o mesmo segredo dentro de uma string maior, e congela o
   path do painel e o slug da rota — as duas coisas que `ConviteDeAcesso::url()` (`:88-91`) existe
   para não congelar.
5. **Lembrar o ADMIN em vez da pessoa convidada** — um digest diário "3 convites pendentes,
   clique para reenviar". Não precisa de token nenhum, reusa a ação *Reenviar* que já existe
   (`app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:69-75`), e é honestamente o
   desenho mais lazy de todos. Descartada porque muda a feature: o valor do lembrete é ele
   chegar **sem** um humano no meio.

### Consequências

- **Positivas**: nenhum segredo reversível no banco; a promessa publicada continua verdadeira; o
  link que a pessoa recebeu vale até expirar, aconteça o que acontecer com o lembrete; o prazo
  continua significando o que diz; sem `Crypt`, sem cast, sem `DecryptException`, sem modo de
  falha por `APP_KEY`; `lembrar()` **nunca** tem um caminho de "não consegui" — por isso é
  `void` e não `bool`.
- **Positivas** (efeito colateral bem-vindo): um convite que nunca teve `token` — linha criada
  por factory ou por importação — também é lembrável, porque o token do lembrete é gerado na
  hora e não depende de nada anterior.
- **Negativas**: **`valido()` muda**, e é a porta única de aceite. O custo é um `orWhere` com
  agrupamento obrigatório, e o modo de falha dele é pior que o bug que resolve: sem o closure, o
  prazo do convite deixa de valer. Mitigação em três camadas — o PHPDoc do método, CT-04, e a
  linha em `wikis/convencoes.md#armadilhas-já-resolvidas`.
- **Negativas**: **dois links vivos por convite**, e o do primeiro lembrete morre quando o
  segundo sai. Quem clicar num link de lembrete antigo cai no login, mesmo com o convite
  pendente. É aceitável porque o link **original** e o do **último** lembrete continuam valendo,
  e o e-mail mais recente é o que a pessoa tende a abrir. Uma coluna por lembrete (ou a tabela que
  ADR-05 recusa) só se justificaria para manter **todo** link de lembrete vivo, e ninguém precisa
  disso.
- **Negativas**: uma coluna com índice único a mais.
- **Riscos**: alguém aplicar um patch em `valido()` sem ler o método inteiro — e a wiki
  `convite-para-usuario-existente` já pôs um terceiro filtro de estado nele
  (`whereNull('recusado_em')`, `app/Models/Convite.php:172`). Os três filtros têm de ficar
  **fora** do agrupamento. É o cenário mais provável de reintroduzir o bug, e está registrado nos Blockers do `03-progresso.md`.
- **Riscos**: alguém "unificar" as duas colunas de volta numa só, por parecerem duplicação.
  Mitigação: CT-02 e CT-03 provam que os dois links coexistem; CT-04 prova que nenhum dos dois
  escapa dos filtros de estado.

### Referências

- `app/Models/Convite.php:125-153` (`enviar()`), `:163-175` (`valido()`), `:281` (o carimbo)
- `app/Notifications/ConviteDeAcesso.php:33, 88-91`
- `config/logging.php:80-81` (a regra LGPD dos canais)
- `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md` — ADR-02 (hash em repouso) e
  ADR-04 (reenviar mata o link anterior)
- CT-02, CT-03, CT-04, CT-05, CT-07, CT-09
- Refinada por: ADR-02, ADR-06

---

## ADR-02: O intervalo conta do último envio (`enviado_em`), não de `created_at`

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O `offload-project/laravel-invite-only` mede o prazo do lembrete a partir de `created_at` da
invitation. É a escolha natural quando o convite é criado e enviado uma vez.

**No kit não é.** `Convite::enviar()` é **também o reenvio** — o próprio PHPDoc do método diz
isso (`app/Models/Convite.php:118-120`), não existe `reenviar()`, e a ação da tabela do `/admin`
chama `enviar()` direto (`ConvitesTable.php:75`). Cada reenvio gera token novo e **renova
`expira_em`**.

Então `created_at` pode estar a semanas do último envio real. Convite criado no dia 1, ninguém
responde, expira; no dia 20 um administrador clica em *Reenviar* — link novo, prazo novo, e-mail
saindo agora. A próxima execução do cron olha `created_at` (19 dias atrás), vê
`lembretes_enviados = 0` e manda um "lembrete" **no mesmo dia do reenvio**: a pessoa recebe dois
e-mails em um dia, o segundo dizendo que o primeiro está esperando. Em duas execuções o teto se
esgota, e os lembretes de D+3 e D+5 **do envio que importa** nunca acontecem.

Nada disso dá erro. É o tipo de defeito que chega como "o sistema mandou e-mail duplicado" três
meses depois.

### Decisão

Coluna nova `enviado_em` (`timestamp`, nullable), escrita por `enviar()` no mesmo `forceFill`
que já grava token e prazo. **E `enviar()` zera `lembretes_enviados`**:

```php
'enviado_em'         => now(),
'lembretes_enviados' => 0,
```

Reenviar é emitir um convite novo: relógio novo, contador novo. As duas chaves entram numa query
que já existia — custo zero.

`created_at` continua sendo quando a **linha** nasceu: é o que a listagem do `/admin` ordena
(`ConvitesTable.php:26`) e não tem nada a ver com o cronograma de lembretes.

Linhas anteriores a esta migration ficam com `enviado_em` nulo, e a comparação
`where('enviado_em', '<=', ...)` nunca casa com NULL — elas saem do lote **sem precisar de um
`whereNotNull` a mais**. Isso é o comportamento correto e não um efeito colateral: para essas
linhas o kit **não sabe** quando o convite foi enviado, e `created_at` não serve de palpite
justamente pelo argumento desta ADR. Elas entram no jogo no dia em que alguém clicar em
*Reenviar*. É também a razão de não haver backfill.

### Alternativas Consideradas

1. **`created_at`** (o desenho do `invite-only`). Descartada pelos quatro sintomas acima.
2. **Derivar o envio de `expira_em - validade_em_dias`**, sem coluna nova. Funciona hoje e
   **quebra no dia em que `KIT_CONVITE_VALIDADE_DIAS` mudar**: todo convite pendente teria sua
   data de envio recalculada para outro dia, e o cronograma saltaria. Config não pode reescrever
   o passado.
3. **`updated_at`** como proxy do último envio. Descartada: qualquer escrita no registro o move —
   inclusive a do próprio lembrete, o que faria o segundo lembrete contar do primeiro em vez de
   contar do envio. Auto-referência silenciosa.
4. **Coluna `proximo_lembrete_em`**, calculada no envio. Descartada: é estado derivável mantido
   à mão, e envelheceria no dia em que os dias de lembrete mudassem no `.env` — os convites
   pendentes continuariam apontando para o cronograma antigo.

### Consequências

- **Positivas**: o cronograma sempre conta do e-mail que a pessoa realmente tem; reenviar
  reinicia tudo, o que é a expectativa de quem clica em *Reenviar*; nenhuma query a mais.
- **Negativas**: uma coluna a mais, redundante com `created_at` no caso mais comum (primeiro
  envio, onde as duas são quase iguais porque `enviar()` roda no `afterCreate()`).
- **Riscos**: um chamador futuro que emita convite sem passar por `enviar()` deixaria
  `enviado_em` desatualizado. Mitigação: `enviar()` é o único ponto que escreve a coluna `token`
  e o único que dispara o e-mail do convite — quem escapar dele não emite convite nenhum.

### Referências

- `app/Models/Convite.php:118-120, 118-146`
- `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:69-75`
- CT-01, CT-09
- Refina: ADR-01

---

## ADR-03: Um laço por convite, não uma query por marco — a idempotência é estrutural

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O `laravel-invite-only` resolve a idempotência com uma construção sutil, e ela merece crédito:
para `after_days` como lista, ele percorre `foreach ($afterDays as $index => $days)`, filtra
`reminder_count <= $index` e acumula os ids já processados num `whereNotIn('id', $processedIds)`.
O efeito é o que queremos: **um lembrete por convite por execução** (o `whereNotIn` impede que a
mesma linha seja pega por dois marcos na mesma passada) e **recuperação de dias perdidos** — um
convite em D+6 com zero lembretes recebe o primeiro agora e o segundo na execução seguinte, em
vez de dois de uma vez.

Copiar a construção junto com o comportamento tem dois custos concretos:

1. **O `whereNotIn` cresce sem teto.** Um dia de acúmulo grande vira uma cláusula `IN` com um
   parâmetro por lembrete enviado. O kit roda SQLite (na suíte e em instalação pequena), onde o
   limite histórico de variáveis é 999 — a execução que mais precisava funcionar é a que
   estoura.
2. **O invariante `count <= $index` depende da ordem do array.** Se alguém escrever
   `KIT_CONVITE_LEMBRETES_DIAS=5,3`, ou "organizar" a lista, os índices trocam de significado e
   o convite passa a receber lembrete em **toda** execução até bater o teto. Nada acusa: o
   comando continua terminando em sucesso.

### Decisão

Inverter os laços: **uma query de candidatos, e uma decisão por convite** (o código completo
está no passo 4a do plano).

```php
$devidos = count(array_filter(
    $dias,
    fn (int $prazo): bool => (bool) $convite->enviado_em?->addDays($prazo)->isPast(),
));

if ($devidos <= $convite->lembretes_enviados) {
    continue;
}

$convite->lembrar();   // UM
```

Lido em voz alta: *quantos lembretes já eram devidos até hoje? mandou menos que isso? manda um.*

As três propriedades saem de graça:

| Propriedade | De onde vem |
| --- | --- |
| um lembrete por convite por execução | há **uma** chamada de `lembrar()` no laço, e cada convite passa pelo laço uma vez. Não é um invariante a manter — é a forma do código |
| recuperação sem rajada | `$devidos` pode ser 2 e o contador 0: sobe para 1 nesta execução, para 2 na seguinte |
| teto | `$devidos` nunca passa de `count($dias)`, e a query já filtra `lembretes_enviados < count($dias)` |

Nenhum acumulador, nenhuma aritmética de índice, **uma** query em vez de N, e a ordem da lista
de dias deixa de importar.

Duas decisões menores, dentro do laço, as duas provadas por CT-10:

**A escrita vem ANTES do `notify()`**, por correção e por operação: o hash tem de estar no banco
antes de o link existir numa caixa de entrada, senão o e-mail sai com um token que `valido()` não
encontra; e um endereço permanentemente quebrado não pode fazer o cron tentar o mesmo convite todo
dia para sempre. O preço é o worker parado fazer o contador subir sem o e-mail sair — está em
"Jobs / Queues" do plano e no README.

**Um `try/catch` por convite, com `warning` e sem falhar o comando**, porque o `chunkById` ordena
por `id` e um convite estragado de id baixo deixaria **todos** os outros sem lembrete em toda
execução — starvation silenciosa. Falhar o comando geraria alarme falso diário por um endereço
inválido.

### Alternativas Consideradas

1. **Copiar `foreach ($marcos)` + `whereNotIn` acumulado** (o desenho do `invite-only`).
   Descartada pelos dois custos do contexto. O comportamento que ele garante é o mesmo que
   temos.
2. **Percorrer os marcos do maior prazo para o menor**, preservando os índices do array
   crescente — assim o próprio incremento do contador exclui a linha das iterações seguintes e o
   acumulador desaparece. Funciona (verificado no papel para `[3,5]` e `[3,5,9]`), e foi
   descartada por ser **clever**: depende de um invariante entre a ordem de iteração e o valor do
   índice que ninguém adivinha ao ler, e que qualquer `sort()` bem-intencionado quebra.
3. **Uma coluna `ultimo_lembrete_em` e "manda se faz mais de N dias do último"**. Descartada:
   transforma D+3/D+5 em "a cada 2 dias", que é um cronograma diferente, e o teto voltaria a
   precisar do contador de qualquer forma.
4. **`->get()` em vez de `chunkById()`**. Descartada: é literalmente o defeito do
   `markExpiredInvitations()` do `invite-only`. `chunkById` (e não `chunk()`) porque as páginas
   são faixas disjuntas de `id`, o que torna seguro mutar, na iteração, a própria coluna que a
   query filtra.
5. **Copiar a segunda metade do comando de referência, o `--mark-expired`.** Descartada porque
   carimbaria uma coluna que não temos: `convites` nunca teve status, o estado é **derivado** de
   `aceito_em` + `expira_em` (decidido na wiki `convite-de-usuario`, ADR-04), e os três lugares que
   perguntam "expirou?" comparam com `now()` — `valido()` (`app/Models/Convite.php:173`), a coluna
   Situação do `/admin` (hoje `Convite::situacao()`, `app/Models/Convite.php:228`) e a query deste comando. Carimbar seria um
   terceiro estado a manter em sincronia com um fato que o banco já tem — e o carimbador do pacote é
   `->get()` sem paginação seguido de `update()` sem transação, então um convite aceito entre o
   `SELECT` e o `UPDATE` viraria `expired` com o usuário já criado. Este comando **lê** convites e
   escreve **duas** colunas: o hash do token do lembrete e o contador. CT-06 assere que nenhuma
   coluna de status é escrita.

### Consequências

- **Positivas**: menos código que a fonte, uma query em vez de N, nenhum limite de parâmetros a
  respeitar, a garantia de "um por execução" é estrutural em vez de mantida, e metade do comando
  de referência simplesmente não é escrita.
- **Negativas**: `array_filter` roda em PHP para cada convite candidato — irrelevante em
  qualquer volume que caiba num kit, e o filtro da query já derrubou os que estão em dia.
- **Negativas**: o contador não diz **qual** marco disparou. Nada lê isso (o texto do e-mail é
  um só), e o log tem `enviado_em` + `lembretes_enviados`, que reconstroem a história.
- **Riscos**: alguém "otimizar" movendo o `$devidos` para SQL e recriando o laço por marco.
  Mitigação: CT-01 (um por execução e recuperação sem rajada) fica vermelho.
- **Riscos**: alguém acrescentar uma coluna de status "para facilitar relatório", e junto dela um
  carimbador. Mitigação: a razão está registrada em três wikis, e um relatório de situações se
  escreve com dois `whereNull` e uma comparação de data.

### Referências

- Análise de código-fonte do `offload-project/laravel-invite-only`, 2026-08-14
  (`handle()` e `markExpiredInvitations()`)
- `wikis/specs/main/convite-para-usuario-existente/02-decisoes-arquiteturais.md` — ADR-07 (por que
  o pacote não é instalado)
- `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md` — ADR-04 (estado derivado)
- CT-01, CT-06, CT-10
- Refina: ADR-02

---

## ADR-04: O kit agenda o lembrete por padrão, em `routes/console.php`

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Onde o kit agenda não é dúvida: `routes/console.php`, que tem o bloco "Agendamentos do kit" e as
duas linhas ativas (`:24, :27`), enquanto o `KitServiceProvider` não agenda nada e manda para lá
no próprio docblock (`:127`). O arquivo também já está em `KitUpdate::CAMINHOS_DO_KIT`, então
chega a quem já instalou.

A pergunta é de produto: **ligado ou comentado?** O arquivo tem os dois precedentes — as duas
linhas de backup ficam comentadas (`:30-31`) porque `backup:run` sem destino configurado **falha**;
as outras duas ficam ligadas porque são inertes e úteis desde o primeiro minuto.

O lembrete manda e-mail para pessoas reais, o que puxa para o lado conservador. Mas numa
instalação nova ele é **inerte por quatro razões independentes**: não há convite pendente;
`MAIL_MAILER=log` (`.env.example:56`) não manda nada para fora; sem worker a notificação nem sai
da fila (`QUEUE_CONNECTION=database`, `:42`); e sem scheduler o comando nunca é chamado.

E o `laravel-invite-only` **não agenda nada, só documenta** — com o resultado previsível de a
feature existir e não funcionar até alguém ler o README até o fim.

### Decisão

Agendado e **ligado**, ao lado dos outros dois:

```php
Schedule::command('kit:convites-lembrar')->dailyAt('08:00');
```

Quem já tem scheduler rodando passa a mandar lembretes **sem fazer nada** — que é o ponto de uma
feature de kit. Desligar é uma chave no `.env` (`KIT_CONVITE_LEMBRETES_DIAS=`), não uma linha de
código a comentar.

Diário, e não horário: o marco é medido em dias, e o custo da precisão diária é o lembrete de
D+3 chegar entre D+3 e D+4 conforme a hora do envio. Para um e-mail, isso é irrelevante; um cron
horário multiplica por 24 as execuções para não mudar nada de perceptível.

**Sem `->onOneServer()` e sem `->withoutOverlapping()`**: nenhuma das duas linhas vizinhas usa,
o kit não presume cluster, e `onOneServer()` exige um cache com suporte a lock compartilhado
entre máquinas — uma exigência nova de infraestrutura escondida numa linha. Fica um comentário
`ponytail:` no arquivo nomeando o teto e o upgrade.

**E é esta decisão que corta o `--dry-run`**: com o agendamento ligado, a primeira execução numa
instalação é a do cron, não a de um humano no terminal. Uma opção de ensaio guardaria uma porta
que já está aberta. Quem quiser ensaiar usa `MAIL_MAILER=log`, o default do kit, que escreve o
e-mail em `storage/logs` em vez de mandá-lo.

### Alternativas Consideradas

1. **Comentado, como os backups.** Descartada: os backups são comentados porque **falham** sem
   configuração; este é inerte sem configuração. E linha comentada em arquivo de kit é feature
   que ninguém liga.
2. **Agendar no `KitServiceProvider`**, junto dos health checks. Descartada: contraria o
   docblock do próprio provider (`:127`) e espalharia o cronograma do kit por dois arquivos —
   quem quer saber o que roda de madrugada teria de olhar em dois lugares.
3. **`->everyThirtyMinutes()` com janela de horário.** Descartada: o marco é em dias.
4. **Uma chave `KIT_CONVITE_LEMBRETES_HORA` para a hora do agendamento.** Descartada: é uma
   linha em `routes/console.php`, num arquivo que o projeto edita à vontade. Config para um
   valor que quem se importa edita direto é config a mais.

### Consequências

- **Positivas**: a feature funciona por default; nenhum passo manual no README além do scheduler
  que o kit já exige; o cronograma do kit continua em um arquivo só; o comando não tem opção
  nenhuma.
- **Negativas**: uma instalação que **já tenha** convites pendentes antigos e ligue o scheduler
  manda vários lembretes na primeira execução — um por convite, respeitando o teto.
- **Riscos**: duas máquinas com o mesmo scheduler mandam o lembrete duas vezes. Registrado em
  "Riscos" do plano, com `->onOneServer()` como upgrade nomeado.
- **Riscos**: um projeto que não quer lembretes e não lê o `.env` descobre a feature pelo
  primeiro e-mail. Mitigação: README, `.env.example` e a nota no `config/kit.php`.

### Referências

- `routes/console.php:11-31`
- `app/Providers/KitServiceProvider.php:127`
- `.env.example:42, 56`
- CT-11

---

## ADR-05: Duas colunas e um contador — sem tabela de histórico e sem um segundo teto

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Três tentações de modelagem, todas defensáveis à primeira vista: uma tabela `convite_lembretes`
(uma linha por lembrete, a modelagem "correta" de um evento que acontece N vezes, e relatório de
graça); uma coluna `ultimo_lembrete_em` além do contador; e um `maximo` de lembretes em config
além da lista de dias, que é o que o `invite-only` faz (`max_reminders` + `after_days`).

### Decisão

Nenhuma das três. O que existe é: `enviado_em`, `lembretes_enviados` (e `token_lembrete`, que é
de ADR-01).

**Sem tabela de histórico.** O comando faz duas perguntas: "quantos já foram?" e "desde quando o
convite está enviado?". Um inteiro e uma data respondem as duas. Quem, quando e para qual e-mail
(mascarado) fica no `autenticacao.log`, com uma linha `[Convite@lembrar]` por lembrete, visível
no Logs Explorer do `/infra`. Uma tabela nova traria migration, model, relação, factory, o dever
de aparecer em alguma tela e o dever de ser expurgada um dia — para responder o que dois campos
respondem.

**Sem `ultimo_lembrete_em`.** Ninguém pergunta isso no código, e o horário exato está no log. O
dia em que a listagem precisar da data, a coluna entra sozinha, aditiva.

**Sem `maximo` em config — e este é o ponto mais fácil de errar.** O teto **é** `count(dias)`:
com `dias = [3, 5]`, `$devidos` nunca passa de 2, então nenhum convite recebe um terceiro
lembrete. Um segundo botão pode **discordar** do primeiro, em silêncio: `maximo = 1` com
`dias = [3, 5]` transforma o segundo dia em config morta, e `maximo = 5` com dois dias promete
cinco lembretes que nunca sairão. Duas fontes de verdade para um número derivável.

Pelo mesmo critério, `lembretes_dias` é uma **chave escalar** dentro de `convites`, e não um
sub-array `lembretes` com um único filho: os irmãos que ele anteciparia (`maximo` aqui, `hora` e
`enabled` em ADR-04) estão todos recusados.

### Alternativas Consideradas

1. **Tabela de histórico.** Descartada acima. Volta a valer no dia em que existir tela de
   "histórico de lembretes deste convite" — e nesse dia o log já terá provado quais campos ela
   precisa ter.
2. **Contador como array JSON de datas** (`lembretes: ["2026-08-17", "2026-08-19"]`), meio
   caminho entre inteiro e tabela. Descartada: não é pesquisável, e a única pergunta que a query
   faz sobre ele é "quantos" — `count()` de JSON em SQL não é portável entre SQLite e MySQL.
3. **Validar em código que todo dia de lembrete é menor que `validade_em_dias`.** Recusada como
   código, aceita como comentário. Com `KIT_CONVITE_VALIDADE_DIAS=3` e lembrete em D+3, o convite
   expira antes de o lembrete ser devido e **nenhum lembrete sai, sem erro nenhum** — é uma
   armadilha real. Mas o remédio seria falhar o comando por causa de config, e config incoerente
   não é falha de execução: o comando faria barulho diário sobre algo que ninguém vê. O aviso fica
   no `config/kit.php` (onde quem edita está lendo) e no README. Se aparecer em suporte, vira um
   health check — não uma exceção no cron.

### Consequências

- **Positivas**: três colunas aditivas e nenhuma tabela nova; um único botão de configuração; o
  teto não pode discordar do cronograma porque é o mesmo dado.
- **Negativas**: não há relatório de lembretes na tela, só no log. E "mudar o teto sem mudar os
  dias" não é expressável — o que é a intenção.
- **Riscos**: config com dia ≥ validade produz silêncio total. Aceito e documentado, com o
  caminho de upgrade nomeado.

### Referências

- `config/kit.php:72-91` (o bloco de convites e o tom dos comentários)
- Análise de código-fonte do `offload-project/laravel-invite-only`, 2026-08-14 (`max_reminders`)
- CT-01, CT-07

---

## ADR-06: A mesma `ConviteDeAcesso`, com uma flag — nenhuma Notification, Job ou Enum novo

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O lembrete precisa de um e-mail que diga que é lembrete. O reflexo é
`App\Notifications\LembreteDeConvite` — que teria de repetir de `ConviteDeAcesso`
(`app/Notifications/ConviteDeAcesso.php:42-78`) o assunto, o `->greeting()`, a linha do nome da
organização, a de prazo, a de "se você não esperava", o `->salutation()` traduzido — e o **`url()`
privado** (`:88-91`), o método que garante que o link é montado por `Panel::route()` e não por
string literal. Copiá-lo é copiar a decisão que ele carrega, e a cópia é onde ela vai se
dessincronizar.

A diferença real entre os dois e-mails é **o assunto e uma linha de texto**.

### Decisão

Um terceiro parâmetro no construtor, com default:

```php
public readonly bool $lembrete = false,
```

`toMail()` ganha um ternário no `->subject()` (`:55`) e um `if` de uma linha antes do
`return $mensagem` (`:73`). O default `false` mantém os dois chamadores atuais válidos sem edição.

Isso convive com o que a wiki `convite-para-usuario-existente` faz no passo 6 dela (texto de
oferta para quem já tem conta) porque os dois eixos são **ortogonais**: cada um é um `if` de uma
linha que acrescenta uma frase. Não são quatro combinações de corpo a manter. **Se aparecer um
terceiro eixo, é hora de extrair** — aí sim o `toMail()` estaria decidindo demais.

Pelo mesmo raciocínio, e cada um por um motivo próprio:

- **Nenhum Job.** A `Notification` com `ShouldQueue` já **é** o job (o Laravel a embrulha em
  `SendQueuedNotifications`), e o comando já roda fora do request pelo scheduler. Um Job aqui
  seria um job agendando um job que despacha um job.
- **Nenhum Enum.** O marco (D+3, D+5) não é lido por nada: o texto é um só e o contador é um
  inteiro. Um enum de duas caixas sem consumidor é cerimônia.
- **Nenhum evento `ConviteLembrado`.** Sem segundo consumidor.
- **Nenhum `LembreteService`.** Um método no model e um laço no comando.

### Alternativas Consideradas

1. **`LembreteDeConvite` (classe nova).** Descartada pelo argumento do contexto: duplicaria seis
   linhas de cópia e o `url()` privado.
2. **Herdar de `ConviteDeAcesso` e sobrescrever `toMail()`.** Pior que as duas: `url()` é
   privado, então a subclasse teria de duplicá-lo ou o pai teria de abrir a visibilidade — e o
   `toMail()` sobrescrito reconstruiria o corpo inteiro para mudar duas linhas.
3. **Mandar exatamente o mesmo e-mail, sem flag nenhuma** (a versão mais lazy de todas).
   Descartada por uma razão de produto: um e-mail idêntico chegando de novo parece bug ou spam,
   e clientes de e-mail agrupam por assunto — a pessoa pode nem ver que chegou algo novo. Um
   ternário e uma linha compram o "isto é um lembrete".
4. **Um parâmetro `string $variacao` em vez de `bool`**, prevendo mais textos. Descartada: dois
   estados são um `bool`. `string` convida a um `match` com casos que ninguém pediu.

### Consequências

- **Positivas**: um construtor com um parâmetro a mais, duas linhas em `toMail()`, e o link do
  lembrete é montado pelo **mesmo** `url()` do envio — o token é outro, o formato da URL não.
- **Negativas**: `toMail()` passa a ter dois eixos de variação — o `$jaTemConta` que a wiki irmã já
  pôs lá e o `$lembrete` desta.
  O limite está declarado: no terceiro, extrai-se.
- **Riscos**: alguém passar `lembrete: true` a partir de `enviar()` por engano, e o primeiro
  e-mail já sair como lembrete. Mitigação: parâmetro nomeado no único chamador que o usa
  (`lembrar()`), e CT-08 assere o assunto dos dois e-mails.

### Referências

- `app/Notifications/ConviteDeAcesso.php:27, 31-34, 42-78, 88-91`
- `wikis/specs/main/convite-para-usuario-existente/01-plano-acao.md` — passo 6 (o outro eixo)
- `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md` — ADR-05 (`ShouldQueue` já é o job)
- CT-02, CT-08
- Refina: ADR-01
