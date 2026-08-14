# Progresso — Lembretes de convite

> **Ordem satisfeita.** `convite-para-usuario-existente` já está na árvore de trabalho (ainda não
> commitada): a coluna `recusado_em`, `Convite::situacao()` no model, `aceitarComoUsuarioExistente()`,
> `recusar()` e o `whereNull('recusado_em')` dentro de `valido()`. O que sobrou dela para cá são os
> dois `update` condicionais que precisam levar `'token_lembrete' => null` — e o fato de que
> **ela e esta feature editam o mesmo `Convite::valido()`**. Ver os Blockers.

## 1. Migration — três colunas em `convites`

- [x] `database/migrations/2026_08_14_000002_add_lembretes_to_convites_table.php`
- [x] `token_lembrete` `string(64)` nullable **unique** — o segundo token, hasheado (ADR-01)
- [x] `enviado_em` `timestamp` nullable — o relógio dos lembretes (ADR-02)
- [x] `lembretes_enviados` `unsignedTinyInteger` default `0`
- [x] **Sem `->after()`** (não amarrar a ordem física a `recusado_em`)
- [x] **Sem índice em `enviado_em`** e **sem backfill** — `created_at` não é a data do último
      envio, então backfill fabricaria um relógio errado
- [x] `down()` derruba as três
- [x] `php artisan migrate` roda limpo nos dois modos

## 2. `App\Models\Convite` — o segundo token, o relógio e `lembrar()`

- [x] `token_lembrete` no `$hidden`; as três colunas **fora** do `$fillable` (mantém os dois
      hashes fora da trilha de `/infra/audits`, de `toArray()` e de `dd()`)
- [x] `casts()` ganha `enviado_em` como `datetime`
- [x] Três `@property` novos no docblock da classe
- [x] `enviar()` grava `enviado_em = now()`, `lembretes_enviados = 0` e **`token_lembrete = null`**
      no `forceFill` que já existe
- [x] O log de `enviar()` ganha `enviado_em` no `$context`
- [x] **`valido()` aceita os dois tokens, com `where(closure)` em volta do par** — e os filtros
      de estado (`aceito_em`, `expira_em`, e o `recusado_em` da wiki irmã) ficam **fora** do
      agrupamento
- [x] O PHPDoc de `valido()` traz o SQL errado e o sintoma (convite expirado voltando a valer)
- [x] `aceitar()` grava `'token_lembrete' => null` junto de `aceito_em`
- [x] `lembrar(): void` — gera `Str::random(64)`, grava `hash('sha256', ...)` + contador **antes**
      de notificar, notifica com `lembrete: true`, loga `info`
- [x] Uma escrita só, por `forceFill` (não `increment()` numa segunda query)
- [x] Nenhum token nem hash no log
- [x] **Nada de `scopePendentes()`, `validoPorLembrete()`, `podeSerLembrado()`, Service, Job,
      Enum ou evento**
- [x] **Fora do plano**: `enviar()` chama `$this->refresh()` antes do `forceFill`. Ver Desvios e
      Notas, item 1

## 3. `App\Notifications\ConviteDeAcesso` — a mesma classe, um texto a mais

- [x] Terceiro parâmetro `public readonly bool $lembrete = false` (default mantém os dois
      chamadores atuais válidos)
- [x] `->subject()` vira ternário
- [x] Uma linha de lembrete antes do `->action()`
- [x] `url()` **intocado** — o token é outro, o formato da URL não
- [x] **Zero log nesta classe**, como hoje

## 4. `App\Console\Commands\KitConvitesLembrar`

- [x] `app/Console/Commands/KitConvitesLembrar.php`
- [x] `$signature` `kit:convites-lembrar`, **sem opção nenhuma**; `$description` numa frase
- [x] Docblock de classe explicando por que o lembrete **não** chama `enviar()`
- [x] Lista de dias vazia → `info` e `SUCCESS` na primeira linha
- [x] Query: `whereNull('aceito_em')`, `whereNull('recusado_em')`, `expira_em > now()`,
      `lembretes_enviados < count($dias)`, `enviado_em <= now()->subDays(min($dias))`
- [x] `chunkById(100)` — **nunca `->get()`**
- [x] `$devidos` por `array_filter` e **uma** chamada de `lembrar()` no laço (ADR-03)
- [x] `try/catch` por convite, com `warning` e sem falhar o comando
- [x] `info` de resumo com `total` e `dias`
- [x] Sempre `self::SUCCESS`
- [x] Saída por `$this->components->info()`, no padrão de `KitTenancy.php:55`

## 5. `config/kit.php` + `.env.example`

- [x] `lembretes_dias` como **chave escalar** dentro de `convites` (sem sub-array de um filho)
- [x] `explode` + `array_filter` para `KIT_CONVITE_LEMBRETES_DIAS=` virar `[]`, não `[0]`
- [x] Comentário avisando que todo dia precisa ser **menor** que `validade_em_dias`
- [x] Comentário dizendo que o teto é a quantidade de dias (não existe segundo botão)
- [x] `KIT_CONVITE_LEMBRETES_DIAS=3,5` no `.env.example`, dentro do bloco de convite

## 6. Agendamento em `routes/console.php`

- [x] `Schedule::command('kit:convites-lembrar')->dailyAt('08:00')`
- [x] **Ligado, não comentado** (ADR-04) — diferente dos backups de `:30-31`
- [x] Comentário explicando por que é inerte numa instalação nova
- [x] `ponytail:` sobre a ausência de `->onOneServer()`
- [x] `php artisan schedule:list` mostra a linha (`0 8 * * *  php artisan kit:convites-lembrar`)

## 7. Documentação

- [x] `wikis/arquitetura.md` — **dois tokens hasheados** abrem o mesmo convite, presos ao mesmo
      `expira_em` (subseção `#### O convite cobra a si mesmo`)
- [x] `wikis/convencoes.md` — **três** armadilhas novas: o `orWhere` sem agrupamento, o segundo
      token em vez de `enviar()`, e o `refresh()` de `enviar()`
- [x] `wikis/receitas.md` — `## Problemas comuns` ganha "convidei e a pessoa não respondeu" e
      "o lembrete não sai"
- [x] `README.md` — cronograma, `KIT_CONVITE_LEMBRETES_DIAS`, scheduler **e** worker, que o
      contador sobe mesmo com o worker parado, e que o link do lembrete **não** invalida o
      original
- [x] `README.en.md` — espelho
- [x] `CHANGELOG` — **nada a corrigir**: "em claro o token existe no e-mail e em lugar nenhum
      mais" continua verdadeiro, agora para os dois tokens
- [x] `wikis/pacotes.md` — nada a acrescentar

## Testes

- [x] `tests/Kit/ConviteTest.php` — CT-01 a CT-11 apendados (nenhum arquivo novo, nenhum helper
      renomeado)
- [x] **CT-04 escrito e visto falhando com `valido()` SEM o closure de agrupamento** — o sintoma
      observado está em Notas, item 2
- [x] CT-02 vista falhando antes da implementação (`Command "kit:convites-lembrar" is not
      defined.`); as linhas "nada antes do prazo" de CT-01 passam com a implementação e não têm
      como ficar vermelhas sem o comando existir
- [x] CT-02 visto falhando **também** com `lembrar()` chamando `enviar()` de propósito — Notas,
      item 3
- [x] Nada em `tests/Tenancy` (o comando é global)
- [x] Casos existentes de `ConviteTest.php` continuam verdes sem edição

## Verificação Final

- [x] `php artisan migrate`
- [x] Com `MAIL_MAILER=log` e um convite pendente antigo: `php artisan kit:convites-lembrar` e o
      e-mail conferido em `storage/logs/laravel.log` (assunto de lembrete e
      `register?token=` com token **diferente** do do envio)
- [x] `php artisan schedule:list` mostra `kit:convites-lembrar`
- [x] **À mão, num convite lembrado: os dois links abrem a tela de aceite; depois de expirar,
      nenhum dos dois abre** — medido: no prazo `envio=3 lembrete=3`, expirado
      `envio=null lembrete=null`
- [x] `grep` do token em claro **e** do hash dos dois tokens em `storage/logs/autenticacao*.log`:
      zero ocorrências depois de um ciclo envio + lembrete
- [x] `php artisan test --compact tests/Kit/KitUpdateTest.php` verde — nada a acrescentar em
      `CAMINHOS_DO_KIT`
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --group=kit` — **213 passando**, duas execuções com o mesmo número
- [x] `composer types:check` — 0 erros
- [x] Suíte rodada duas vezes; `git status --short` sem sujeira nova
- [x] **Comando inerte numa instalação limpa**: sem convite pendente ele termina em sucesso
      dizendo "Nenhum convite pendente para lembrar." e não envia nada
- [x] `/ponytail:ponytail-review` no diff — **nada a cortar**. O diff de `app/` é 152 linhas em 5
      arquivos (77 de código no comando, o resto comentário no tom do repositório), com um método
      novo, uma coluna de config e nenhuma camada: sem Service, Job, Enum, scope, evento, tabela de
      histórico, `--dry-run`, `--convite=` nem `validoPorLembrete()`. O único candidato levantado e
      mantido foi `'enviado_em'` no `$context` de `enviar()` (é sempre "agora" naquele instante, e a
      própria linha de log tem timestamp) — fica porque o plano o pede e porque ele marca, no mesmo
      registro, de onde o cronograma passa a contar.
- [ ] `git commit`

## Blockers

- [x] **`convite-para-usuario-existente` está na árvore mas não commitada** — resolvido: ela **já
      está commitada** (`7e086a1`/`8414cd7` e vizinhos), e o estado real do model foi lido antes de
      qualquer edição. Os dois `update` condicionais de `aceitarComoUsuarioExistente()` e
      `recusar()` receberam `'token_lembrete' => null`.
- [x] **As duas wikis editam `Convite::valido()`.** Os três filtros de estado ficaram **fora** do
      agrupamento, e o método foi escrito primeiro **sem** o closure de propósito, para ver CT-04
      vermelho pelo motivo certo. Ver Notas, item 2.
- [x] **Qual exceção o Symfony Mailer lança para o endereço inválido de CT-10**: medido,
      `Symfony\Component\Mime\Exception\RfcComplianceException` — *Email "sem-arroba" does not
      comply with addr-spec of RFC 2822*. O `catch (Throwable)` a cobre; o CT assere
      `instanceof Throwable` em vez de amarrar a classe, porque o que importa é o lote não cair.

## Desvios do Plano

| Passo | O que mudou | Por quê |
| --- | --- | --- |
| 2b | **`enviar()` ganhou `$this->refresh()`** antes do `forceFill` | `save()` grava só o que está sujo, e o plano não previu isso: numa instância carregada **antes** de um lembrete, `lembretes_enviados` e `token_lembrete` estão velhos em memória, o `forceFill` os iguala ao valor antigo e a reinicialização é descartada **em silêncio**. Ver Notas, item 1 |
| 4a | O closure do `chunkById` tipa `Illuminate\Database\Eloquent\Collection $convites` | o plano deixava o parâmetro sem tipo. Com o tipo, o `types:check` passa e o `$convite` do laço é `Convite` para o PHPStan — sem ele, `->enviado_em` seria `mixed` |
| CT-10 | **Sem `Notification::fake()`**, contra o que o `04-casos-de-teste.md` prescreve | com o fake nada monta o destinatário no Symfony Mailer, então o endereço inválido **não estoura** e o caso não testaria nada: nem o `try/catch`, nem o `warning`, nem a starvation que ele existe para provar. Sem o fake o mailer é `array` e a exceção acontece de verdade |
| CT-10 | A asserção "o lembrete do bom saiu" passou de `$notifiable->routes['mail']` para os destinatários do envelope do transporte `array` | consequência direta da linha acima: sem fake não há `assertSentOnDemand` |
| CT-09 | "`enviado_em` é agora" é comparado por `format('Y-m-d H:i:s')`, não por `equalTo(now())` | a coluna é `timestamp` e não guarda os microssegundos que `now()` tem. O `equalTo` falhava por sub-segundo, com "Failed asserting that false is true" — e o caso pareceria um bug de relógio |
| CT-04 | Os três estados viraram um `foreach` sobre um array `[estado => colunas]`, em vez de três blocos repetidos | mesma cobertura em um terço das linhas, e o nome do estado vai como mensagem do `toBeNull()` — a falha diz **qual** filtro escapou, que é o motivo de o CT cobrar um por vez |
| Testes | Um helper novo em `tests/Kit/ConviteTest.php`: `tokensDosLembretes(): array` | o `04-casos-de-teste.md` traz o bloco de captura inline, e quatro casos precisavam dele (CT-02, CT-03, CT-07, CT-09). O PHPDoc registra por que a ordem é confiável: todo destinatário on-demand cai no mesmo balde do fake, porque `AnonymousNotifiable::getKey()` devolve `null` |
| — | Nada foi acrescentado a `.ai/rules` | o diff não toca `app/Filament/**`, que é o único caminho coberto pelos globs do índice. As três armadilhas foram para `wikis/convencoes.md#armadilhas-já-resolvidas`, que é onde o kit as procura |
| — | A baseline da suíte era **197**, não 200 | contado antes de escrever qualquer linha: `php artisan test --group=kit` = 197 passando |

## Notas de Implementação

Três coisas que o plano não previu. A primeira é um bug real que só apareceu executando.

1. **`save()` grava só o que está SUJO, e isso quase deixou `enviar()` mentindo.** CT-09 ficou
   vermelho em `lembretes_enviados` = 1 depois de um `enviar()` que manda `0`. O motivo não é o
   `forceFill`: a instância do teste foi carregada **antes** do lembrete, então em memória o
   contador ainda era `0` e `token_lembrete` ainda era `null`. O `forceFill` escreveu exatamente
   os valores que o Eloquent já tinha como `original`, `getDirty()` devolveu as outras colunas e
   as duas de lembrete **não entraram no UPDATE** — sem erro, sem aviso.

   O sintoma que importa não é o contador: é **`token_lembrete` sobrevivendo a um reenvio**, e
   com ele um link de lembrete que a modal de *Reenviar* promete matar. Nos chamadores de hoje
   (`CreateConvite` das duas páginas, a ação *Reenviar*, `convidarEmMassa`) o registro é fresco e
   o bug não aparece — o que o torna pior, porque ele espera pelo primeiro chamador que segure
   uma instância. A correção é `$this->refresh()` na primeira linha de `enviar()`: um SELECT por
   chave primária num método que já faz UPDATE + e-mail + log.

   Recusadas: `update()` na query (bypassa os eventos do model e **apagaria a linha de auditoria**
   que hoje registra `expira_em`/`aceito_em`) e corrigir o **teste** em vez do código (o teste é o
   único chamador que reproduz o cenário; consertá-lo esconderia o defeito).

2. **CT-04 visto vermelho, e o sintoma foi PIOR do que o plano descrevia.** `valido()` foi escrito
   primeiro sem o `where(closure)`, exatamente como a wiki manda. O plano previa que o **token de
   lembrete** passasse a valer sozinho; o que aconteceu é que o **token do envio** também passou —
   porque `AND` liga mais forte que `OR`, e o SQL sai como

       WHERE token = ? OR (token_lembrete = ? AND aceito_em IS NULL AND ...)

   deixando o primeiro `where` **inteiramente sem filtro de estado**. Medido: a primeira asserção
   do laço já falhou, com o dump do convite mostrando `aceito_em` preenchido e o método devolvendo
   o registro. Ou seja, sem o closure um convite **já aceito** volta a ser aceitável **pelo link
   original** — e `it('recusa reuso do convite e loga sem expor o token')`, que existe desde a
   primeira wiki de convite, ficaria vermelho junto. Com o closure, os 28 casos do arquivo passam.

3. **CT-02 confirmado como alarme do reflexo errado.** Com `lembrar()` trocado por
   `$token = $this->enviar()`, o caso falha na asserção 2 com os dois hashes de `token` lado a
   lado (`-'0cc0c9ff…' +'13891c1c…'`). É a asserção que impede alguém de "simplificar" o lembrete
   para o método que revoga o link da pessoa.

### O que a verificação à mão mostrou

Convite pendente com `enviado_em` recuado quatro dias, `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync`:

| Conferido | Resultado |
| --- | --- |
| Token do lembrete no e-mail | `UnUEgd8JEY…` — **diferente** do token do envio (`RDjce48QNd…`) |
| Os dois links no prazo | `Convite::valido()` devolve o **mesmo** convite (id 3) para os dois |
| Os dois links depois de `expira_em` no passado | `null` para os dois — o agrupamento está de pé |
| `autenticacao.log` | zero ocorrências dos dois tokens em claro **e** dos dois hashes |
| E-mail mascarado no log | `smo**************` |
| Banco vazio de pendentes | "Nenhum convite pendente para lembrar.", exit 0, nada enviado |

### Números medidos

| | Antes | Depois |
| --- | --- | --- |
| Testes do grupo `kit` | 197 | **213** |
| Casos em `tests/Kit/ConviteTest.php` | 12 | 28 (CT-01 tem 5 linhas de dataset, CT-06 tem 2) |
| Asserções do grupo `kit` | 582 | 726 |
| Colunas de `convites` | 12 | 15 |
| Permissions no banco | 199 | 199 (nenhuma entidade nova) |

## Retrospectiva

- **Funcionou bem**: ler o código-fonte do `laravel-invite-only` antes de escrever o plano deu
  quatro decisões de graça — a lista de dias, a recuperação sem rajada, a rejeição do
  `--mark-expired` (ele precisa dela porque tem coluna de status; nós não temos) e o corte do
  `max_reminders` (que pode discordar de `after_days` em silêncio).
- **Funcionou bem**: perguntar "de onde sai o token em claro dias depois?" **antes** de escrever
  qualquer passo. Era o beco da feature, e ele decidiu uma coluna nova, o `orWhere` de `valido()`
  e quatro casos de teste. Se a pergunta tivesse vindo durante a implementação, o caminho fácil
  (chamar `enviar()`) já estaria escrito e testado — e o defeito só apareceria como "cliquei no
  link e ele me manda para o login".
- **Funcionou bem, e é a lição da wiki**: a primeira versão da ADR-01 respondia "como reenviar o
  **mesmo** link?" e chegou a uma cópia cifrada do token com `Crypt` — que funciona, e que
  arrastava consigo um segredo reversível no banco, um `try/catch` de `DecryptException`, um ramo
  "token não recuperável" no comando, um argumento inteiro contra o cast `encrypted` e um modo de
  falha novo por `APP_KEY` rotacionada. **A pergunta estava errada.** O que a feature precisa é
  mais fraco — que nada do que a pessoa já tem seja invalidado —, e um segundo token hasheado
  entrega isso apagando os cinco itens. Vale como método: quando a solução começa a crescer
  puxando exceções atrás de si, reler a pergunta antes de continuar respondendo.
- **Funcionou bem**: escrever os CTs antes mostrou que o laço por marco do pacote de referência
  não precisava do acumulador de ids se o laço fosse por convite — ADR-03 nasceu da tentativa de
  descrever a idempotência sem falar de índices.
- **Auditoria de over-engineering (`/ponytail:ponytail-review`, antes de implementar)**: a wiki
  saiu de 2.737 para ~1.400 linhas. Dezoito CTs viraram onze (cinco casos de marco temporal eram
  o mesmo cenário e couberam num `dataset()`), sete ADRs viraram seis (a que recusava
  `--marcar-expirados` não decidia nada: ela deferia a uma decisão já tomada em
  `convite-de-usuario`, e virou uma alternativa recusada em ADR-03), e caíram o `--dry-run` (o
  agendamento nasce ligado, então a primeira execução é do cron e não de um humano — a opção
  guardava uma porta aberta; `MAIL_MAILER=log` é o ensaio) e a coluna `Lembretes` na tabela do
  `/admin` (escondida por default, sem CT, e colidindo com a wiki irmã no mesmo arquivo, para
  responder o que o `autenticacao.log` já responde). **Nada de trava foi cortado**: ADR-01 inteira,
  CT-04 e o procedimento de vê-lo falhar, as três colunas, os pontos de consumo limpando
  `token_lembrete` e as três regras de log continuam onde estavam.
- **Funcionou bem, e foi o item mais valioso da execução**: escrever `valido()` **sem** o closure
  de propósito e ver CT-04 vermelho. Não só provou que o caso cobre a armadilha — mostrou que o
  plano **subestimava** o sintoma: sem o agrupamento é o link **original** que também deixa de
  respeitar o estado do convite, porque `AND` liga mais forte que `OR` e o primeiro `where` fica
  nu. Um CT que passa com a implementação certa e com uma implementação sem `token_lembrete` não
  prova nada até ser visto vermelho; este agora tem sintoma medido no `03-progresso.md`.
- **Faltou no plano**: a interação entre `forceFill` e o dirty-checking do Eloquent. O plano
  descreve `enviar()` como "mesma query, três chaves a mais", e três chaves a mais só entram na
  query se estiverem sujas. Foi o único bug de código da rodada, e CT-09 o pegou por escrever o
  cenário do jeito mais realista (o comando escreve na linha enquanto o teste segura a instância).
  Lição geral para o kit: **`forceFill(['coluna' => $valorDeReset])` não é garantia de escrita** —
  quando o valor de reset é o valor default, ou refresque antes ou escreva pela query.
- **Faltou no plano**: `Notification::fake()` e teste de falha de entrega são mutuamente
  exclusivos. O `04-casos-de-teste.md` mandava usar o fake em nove dos onze casos, e CT-10 é
  justamente sobre a exceção que o mailer lança — com o fake ele passaria **sem exercitar o
  `try/catch`**, que é o caso mais fácil de "passar por acidente" do lote. Vale a regra: caso que
  prova tratamento de falha de e-mail não usa fake de notificação.
- **Faltou no plano**: comparar `timestamp` do banco com `now()` por `equalTo()`. A coluna não
  guarda microssegundos; a asserção certa é ao segundo. Custou uma execução de arquivo (2,3 min).
- **A escada do Ponytail não cortou nada de novo na implementação** — o plano já havia sido
  auditado duas vezes (−527 linhas na segunda). O único acréscimo ao código foi o `refresh()`, que
  é correção e não estrutura: nenhum Service, Job, Enum, scope, tabela de histórico, `--dry-run`
  nem `validoPorLembrete()` foi escrito. O único acréscimo aos testes foi um helper de três linhas
  usado por quatro casos.
