# Progresso — W7: validação de e-mail editável

> Concluído em 2026-08-24.

## 1. O middleware que decide por request

- [x] `app/Http/Middleware/ExigirEmailVerificado.php` criado, estendendo `EnsureEmailIsVerified`
- [x] guarda `if (! RegistroAberto::exigirVerificacaoDeEmail()) return $next($request);`
- [x] log de barramento no channel `autenticacao`, no formato `[Classe@Método]`, com e-mail mascarado
- [x] nenhum log no caminho liberado (decisão, não esquecimento)

## 2. O painel aplica sempre e delega a decisão

- [x] `AppPanelProvider`: `->emailVerification(EmailVerification::class)` sem condição
- [x] `->emailVerifiedMiddlewareName(ExigirEmailVerificado::class)`
- [x] import novo
- [x] bloco de comentário reescrito (o anterior descreve mecanismo que deixou de existir)

## 3. A propriedade e a linha do mapa

- [x] `public bool $registro_verificar_email;` em `App\Settings\ConfiguracoesDoKit`
- [x] `'registro_verificar_email' => 'kit.registro.verificar_email'` em `mapaDeConfiguracao()`
- [x] comentário que justificava a ausência substituído pela justificativa da presença

## 4. A migration de settings

- [x] `database/settings/2026_08_25_000000_add_registro_verificar_email_to_kit_settings.php`
- [x] `up()` semeia de `config('kit.registro.verificar_email')`
- [x] `down()` com `deleteIfExists`

## 5. O toggle na tela

- [x] `TextEntry::make('aviso_verificacao_email')` removido de `abaRegistro()`
- [x] `Toggle::make('registro_verificar_email')` acrescentado — **sem** `->visible($aberto)`, ver *Desvios do Plano* → desvio 4
- [x] `helperText` diz as duas coisas: vale para todo usuário do `/app`, e convite não é afetado
- [x] import de `TextEntry` removido se ficou sem uso

## 6. Os docblocks que passam a mentir

- [x] `app/Support/RegistroAberto.php` — o bloco da dívida
- [x] `app/Support/ConfiguracaoDoLogin.php` — a citação do contraexemplo
- [x] `config/kit.php` — o bloco de `'verificar_email'`

## 7. Testes

- [x] `tests/Kit/VerificacaoDeEmailTest.php` — CT-01…CT-14 + os 8 cenários da revisão adversarial
- [x] `tests/Kit/RegistroAbertoTest.php` — as duas **inversões** (ver *Desvios do Plano*)
- [x] `tests/Kit/ConfiguracoesDoKitTest.php` — o caso de desfazer/refazer generalizado para
      todas as migrations de settings
- [x] CT-13 ganhou cenário próprio (o caso invariante prova existência de linha, não o valor semeado)

## 8. README (pt e en) e proposta de rule

- [x] `README.md` §"Validação de e-mail (opcional)" + linha `F-03c` da matriz
- [x] `README.en.md` — as duas seções equivalentes
- [x] proposta de atualização de `.ai/rules/settings.md` escrita abaixo (**não gravada**)
- [x] `CHANGELOG.md`

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --testsuite=Kit --filter="VerificacaoDeEmail|RegistroAberto|ConfiguracoesDoKit" --compact`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base **1016**, não cair
- [x] `vendor/bin/phpstan analyse` — 0 erros
- [x] `composer test:browser`
- [x] `git commit` por bloco concluído — mergeado via PR #32

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `emailVerifiedMiddlewareName()` aceita a classe do middleware | confirmado: `HasAuth.php:174-178`, e `getEmailVerifiedMiddleware()` (`:367-370`) concatena `"{nome}:{rota}"` — o Laravel resolve FQCN com parâmetro | nenhuma; ADR-01 já cita as linhas |
| `emailVerification()` com um argumento entra com `isRequired: true` | confirmado: assinatura em `HasAuth.php:110` tem `bool \| Closure $isRequired = true` | nenhuma |
| o helper `usuarioDoKit()` produz usuário **validado** | **falso.** Ele usa `usuario()`, que é `User::create()`, e `email_verified_at` está fora do `$fillable` → nasce **não** validado | `04` → `## Setup Global` corrigido: a persona "com e-mail validado" precisa de `forceFill` explícito |
| basta acrescentar o `add()` na migration de settings existente | **falso.** O docblock dela proíbe (`2026_08_24_000000_create_kit_settings.php:34-38`) e instalação existente ficaria sem a propriedade → `MissingSettings` no boot | ADR-05 escrita; passo 4 do plano é migration **nova** |
| a migration nova não afeta teste existente | **falso.** `ConfiguracoesDoKitTest` → *"desfaz e refaz a migration de settings sem quebrar"* fixa o nome de uma migration e afirma `count(linhas) === count(mapa)`. Com duas migrations o `up()` de uma só devolve 24 linhas contra 25 no mapa | passo 7 do plano ganhou o item; ver *Desvios do Plano* |
| `/admin` e `/infra` não pedem verificação | confirmado: nenhum dos dois providers chama `emailVerification()`, e o default é `isEmailVerificationRequired = false` (`HasAuth.php:56`) | nenhuma |
| a rota de prompt é a única afetada pela decisão | **incompleto.** A rota `verify` (`{id}/{hash}`) também passa a existir sempre; ela é protegida por `signed` + `throttle:6,1` (`vendor/filament/filament/routes/web.php:79-82`) | ADR-03 → *Riscos* registra a superfície |
| o `Authenticate` do painel roda antes do nosso middleware | confirmado: as rotas de página nascem dentro de `Route::middleware($panel->getAuthMiddleware())->group(...)` (`routes/web.php:60`), então visitante anônimo é mandado ao login antes; o ramo `! $request->user()` do pai é inalcançável no painel | `04` não gera cenário para visitante anônimo — registrado em *Cogitado e Cortado* |
| `Register::sendEmailVerificationNotification()` pula quem já validou | confirmado: `vendor/filament/filament/src/Auth/Pages/Register.php:161-167` | nenhuma |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar channel de log novo — reusar `autenticacao` | sim | ADR-04, passo 1 |
| 2 | não criar classe/interface de "política de verificação"; a decisão é um `if` | sim | ADR-02, *Filosofia de Implementação* |
| 3 | não reimplementar `EnsureEmailIsVerified` — herdar | sim | ADR-02 |
| 4 | não criar `05-casos-de-teste-browser.md` — nada aqui exige navegador | sim | `04` → `## Sem CT-B` |
| 5 | não criar subclasse da tela de prompt só para redirecionar com a opção desligada | sim — aceito como trade-off | ADR-03, e premissa registrada no `00` |
| 6 | não registrar alias de middleware em `bootstrap/app.php` — o Filament injeta a classe | sim | passo 2 |

### Revisão adversarial (perfil completo)

Delegada a agente independente, com acesso **apenas** ao `00-requisito.md` e ao
`04-casos-de-teste.md` — sem o PRD, sem as ADRs, sem código. **13 achados**, 12 fechados com
cenário novo ou oráculo reescrito, 1 refutado com medição. A tabela completa está em
`04-casos-de-teste.md` → `## Revisão Adversarial — achados e fechamento`.

Os três que mais valeram o custo:

1. **RQ-03 não tinha nenhum cenário falsificador.** A cláusula pede *"um middleware proprio do
   kit"*, e o conjunto inteiro media só comportamento — um decisor escrito como Closure no provider
   passaria em tudo. Virou CT-03b, uma asserção sobre a string que está no array de middleware da
   rota.
2. **A coluna do JSON estava colapsada por asserção, não por prova.** A implementação que lê a
   opção depois de checar `expectsJson()` responde 403 a requisição AJAX com a exigência
   DESLIGADA — o default do kit —, e isso quebraria todo Livewire do `/app` sem nenhum cenário de
   HTML acusar.
3. **O `Dado` não fixava o registro aberto**, e daí saiu a única mudança de implementação que a
   revisão provocou (ver *Desvios do Plano* → desvio 4).

## Blockers

Nenhum. A hipótese de bloqueio que o requisito previa — *"se você concluir que não dá para resolver
sem quebrar algo, pare"* — não se materializou: o ponto de extensão
`Panel::emailVerifiedMiddlewareName()` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:174-178`)
existe e é público, e é ele que torna a inversão possível sem tocar em vendor, sem macro e sem
dependência nova.

## Desvios do Plano

### As duas inversões de asserção, e por que elas não são regressão

Dois casos existentes **guardavam a dívida**. Invertê-los não é afrouxar teste; é o oposto — eles
afirmavam o mecanismo que impedia a chave de ser editável.

**Inversão 1 — `tests/Kit/RegistroAbertoTest.php` → *"exige validacao de email no painel de negocio
somente com a opcao ligada"* (CT-22b da wiki ancestral).**
Ele montava o painel pelo provider e afirmava
`hasEmailVerification() === false` e `isEmailVerificationRequired() === false` com a opção
desligada. Ambos passam a ser **sempre `true`** — é exatamente o que tira a decisão do array da
rota (ADR-01) e o que garante que a rota de destino exista (ADR-03, RQ-09). O caso é **reescrito**,
não removido: passa a afirmar que a rota nomeada existe nos dois estados e que o middleware
declarado pelo painel é o do kit. Cobre CT-08.

**Inversão 2 — `tests/Kit/RegistroAbertoTest.php` → *"mantem as tres chaves de registro no mapa de
configuracao"*.**
A linha `->and($mapa)->not->toHaveKey('registro_verificar_email')` era o guardião explícito da
dívida — ela existia para *"impedir alguém de 'completar' o mapa achando que faltou uma linha"*. A
dívida foi paga, então a asserção negativa vira **positiva**. Cobre CT-12.

Em ambos os casos o comentário do teste é reescrito apontando para esta wiki, para que a próxima
pessoa não leia a inversão como afrouxamento.

**Inversão 3 (encontrada só pela suíte completa) — `tests/Kit/TelasDeAutenticacaoTest.php` → CT-08,
*"não põe a confirmação de e-mail no ar em nenhum painel"*.**
Era o **terceiro** guardião da dívida, e o único que as rodadas filtradas não pegaram: ele afirmava,
para os três painéis, que `hasEmailVerification()` e `isEmailVerificationRequired()` eram `false` e
que a rota do prompt **não existia**. Para o `app` isso deixou de ser verdade por requisito
(RQ-09 — sem a rota, ligar a opção pela tela dá `RouteNotFoundException`).

Resolvido **sem perder cobertura**, e este é o ponto: o `app` saiu do dataset e ganhou um caso
espelhado (CT-08b) que afirma o oposto com o motivo escrito; `admin` e `infra` continuam no dataset
original — e ali a asserção **ganhou** importância, porque `MustVerifyEmail` no `User` é contrato
global e é essa asserção que impede os dois painéis de administração de passarem a exigir e-mail
validado. Apagar o caso inteiro teria sido a saída errada.

Lição para o processo: as três inversões foram achadas por três meios diferentes — duas pela leitura
do código, uma **só** pela suíte completa. Rodada filtrada por nome de feature não encontra guardião
que vive num arquivo cujo nome não tem relação com a feature.

**Desvio 4 — o toggle NÃO se esconde com o registro aberto desligado, ao contrário do plano.**
O passo 5 do PRD dizia `->visible($aberto)`, copiando o vizinho. A revisão adversarial mostrou por
que isso estava errado: o middleware não consulta `RegistroAberto::habilitado()`, então a exigência
vale mesmo com o cadastro aberto fechado — e o campo escondido produziria exigência **ligada e
invisível**, sem como desligá-la pela tela. É o defeito espelhado do que a feature vem consertar.
`aprovacao_manual` continua oculto, porque aprovação de cadastro realmente só existe com porta
aberta. Coberto por CT-01c (comportamento) e CT-11b (tela).

**Desvio 3 — `tests/Kit/ConfiguracoesDoKitTest.php` → *"desfaz e refaz a migration de settings sem
quebrar"*.**
Ele fixava o nome de uma migration e afirmava `count(linhas) === count(mapa)` depois do `up()`.
Com duas migrations de settings isso é aritmeticamente impossível. Generalizado para percorrer
**todas** as migrations de `database/settings/` — `down()` em ordem inversa, `up()` em ordem —, o
que mata o mesmo mutante (`add()` sem `deleteIfExists()` no `down()`) para toda migration presente
e futura, e remove um nome de arquivo fixado.

## Notas de Implementação

**A medição que fecha o risco maior da feature.** `php artisan route:list` depois da mudança:

- **12** rotas carregam `ExigirEmailVerificado:filament.app.auth.email-verification.prompt`;
- **todas** sob `/app` — `/admin` e `/infra` ficam com **zero**, o que prova RQ-08 estruturalmente
  (e não só por comportamento);
- a rota `filament.app.auth.email-verification.prompt` **não** está entre elas. É o que descarta o
  laço de redirecionamento, e a razão é estrutural: ela nasce de um `Route::get()` direto no
  `routes/web.php` do Filament (`:75-84`), não de `Page::registerRoutes()`, então nunca passa por
  `getRouteMiddleware()`.

**PHPStan pediu uma correção real, não cosmética.** A primeira versão do log fazia
`$user instanceof Authenticatable` para obter o id com segurança; o Larastan já resolve
`$request->user()` como `App\Models\User`, e apontou o `instanceof` como sempre-verdadeiro. A
guarda que ficou é a que tem conteúdo semântico (`instanceof MustVerifyEmail`, a mesma do
middleware do Laravel), e o id sai de `getAuthIdentifier()`.

**O channel `autenticacao` tem outros escritores.** `shouldNotHaveReceived('warning')` cru reprovou:
um `GET /app` bem-sucedido já emite dois `warning` nesse canal (log de autenticação e bloqueio de
sessão). A asserção de ausência passou a nomear o prefixo `[ExigirEmailVerificado@handle]` — é a
mesma lição que `.ai/rules/testes.md` já registra para asserção de ausência sobre arquivo
documentado, agora numa variante nova: **asserção de ausência sobre canal compartilhado precisa
filtrar o emissor.** Candidata a linha nova na rule de `tests/**`.

**O achado mais valioso da execução não era desta feature: CT-37 não podia falhar.** O caso
*"liga o alinhamento no provider da aplicacao e em nenhum painel"* de `ConfiguracoesDoKitTest`
reprovou porque o docblock do middleware novo menciona `aplicarNaConfig()` — a quarta ocorrência do
padrão que `.ai/rules/testes.md` já registra (asserção de ausência sobre arquivo documentado precisa
filtrar comentário). Ao consertar, apareceu o defeito de baixo:

```php
->not->toContain('aplicarNaConfig', "O painel {$painel} chama o alinhamento — ...")
```

`toContain()` é **variádico**: a explicação não era mensagem de falha, era uma segunda **agulha**. E
o `->not` do Pest passa assim que a asserção positiva lança — bastava a mensagem longa não estar no
arquivo. O laço dos painéis, que existe para impedir que alguém pendure o alinhamento no
`bootUsing()` de um painel, **não podia falhar com nenhum conteúdo**. O `AppPanelProvider` desta
própria branch cita `aplicarNaConfig()` e passava.

Consertado: uma agulha por chamada, filtro de comentário nos dois laços, e a explicação no docblock
do caso. As asserções do caso subiram de 4 para 7. **Este defeito é anterior à feature e não teria
sido encontrado sem ela** — foi o docblock do middleware que forçou a leitura do caso.

Candidata a linha nova em `.ai/rules/testes.md`: *"mensagem de falha não existe em `toContain()`;
o segundo argumento é outra agulha, e `->not` variádico passa se qualquer uma faltar"*.

**O `.env` do worktree tem `KIT_TENANCY=true`, e isso não afeta a suíte `Kit`.** `Tests\TestCase`
escreve a flag no ambiente **antes** do bootstrap (`createApplication()`), então `tests/Kit` roda
single-tenant e `/app` é a URL do dashboard. Foi conferido antes de escrever os cenários HTTP, para
não derivar rota com `{tenant}`.

## Proposta de atualização de `.ai/rules/settings.md` (NÃO gravada)

A rule afirma hoje, no fim da seção *"Chave lida no boot não pode virar Settings"*:

> Para tornar uma chave de boot editável de verdade, o caminho é um middleware próprio que decida
> por request — aí a decisão sai do array da rota. **Não foi feito; é dívida conhecida.**

Com esta entrega a última frase é falsa. Proposta de substituição, para decisão do dono do
repositório:

```markdown
## Chave lida no boot não pode virar Settings — a menos que um middleware próprio decida por request

O critério não é sobre a chave, é sobre QUANDO ela é lida — e sobre existir ou não um ponto de
extensão que aceite trocar a decisão por um decisor.

- **Lida por request**: pode ir para o Settings. Basta a linha no `mapaDeConfiguracao()`.
- **Lida no boot** (montar painel, registrar rota, decidir middleware): não pode **diretamente**.
- **Lida no boot, mas com um ponto de extensão que aceita uma classe nossa**: pode, invertendo a
  condição de lugar. É o caso de `registro_verificar_email`, resolvido em
  `wikis/specs/feat/verificacao-de-email-editavel/`:
  o middleware de e-mail verificado continua fixado no array da rota
  (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), mas o painel passou a
  aplicá-lo **sempre** e a declarar a classe do kit em `emailVerifiedMiddlewareName()`
  (`app/Providers/Filament/AppPanelProvider.php`). O array da rota deixou de guardar uma decisão
  e passou a guardar um decisor: `App\Http\Middleware\ExigirEmailVerificado` lê a opção a cada
  request.

  O preço tem nome: a rota de destino do redirecionamento precisa nascer **sempre**, senão ligar
  a opção produz `RouteNotFoundException` em vez de tela.

**Toggle que grava e não faz efeito até o próximo deploy continua sendo pior que campo ausente.**
A saída, quando não há ponto de extensão, continua sendo deixar no `.env` e dizer na tela onde a
chave mora. O que mudou é que "chave de boot" deixou de ser veredito automático: antes de aceitar
a dívida, procure o `...MiddlewareName()` (ou equivalente) do componente.

Ao acrescentar propriedade, são TRÊS lugares, sempre: a propriedade na classe, a linha em
`mapaDeConfiguracao()` e o `add()`/`addEncrypted()` numa migration de settings — **nova**, não a
existente, que já rodou em instalação de terceiro. Esquecer a linha do mapa é o defeito
silencioso: o campo aparece, grava, e não governa nada. Há caso de teste guardando o mapa por isso.
```

Gates avaliados: **durável** ✅ (vale para toda chave de boot futura) · **escopável** ✅
(`app/Settings/**`, e talvez `app/Providers/Filament/**`) · **não-inferível** ✅ (ninguém acha
`emailVerifiedMiddlewareName()` lendo o código do kit) · **não-redundante** ✅ (atualiza rule
existente em vez de criar outra, que é o preferido).

## Quality Gate (step 8)

Perfil **completo** (natureza `correção` + domínio sensível: a feature decide fronteira de acesso a
painel). Relatório em `06-relatorio-qa.md`.

- **Ciclo 1 — APROVADO COM DÉBITO**: 1 Major (QA-01) e 1 Minor (QA-02).
  - **QA-01**, dimensão K: o caso que prova que `/admin` e `/infra` não regrediram usava
    `assertSuccessful()` sozinho. Como o que ele nega é justamente um **redirecionamento**, o oráculo
    era cego para o modo de falha mais próximo — um 200 que não é o painel. Os cenários do `/app` já
    tinham âncora de conteúdo; os de fora dele ficaram sem. Destino 3.
  - **QA-02**, dimensão D: `ip` em claro no context do log. Destino 5 (não-defeito) — é o padrão
    vigente do canal `autenticacao`, e o valor forense da trilha depende dele. Aceito como débito.
- **Ciclo 2 — APROVADO**: QA-01 fechado com `assertSeeLivewire(Dashboard::class)`; nenhum achado
  novo. Loop encerrado em 2 de 3 ciclos.

Duas coisas que o gate registrou e que não viraram achado, por serem comportamento correto:

- **`master_global` sem e-mail validado É barrado no `/app`** — o middleware não consulta o
  `Gate::before`. Correto pelo requisito (*"todo usuário do /app"*) e **sem risco de trancar alguém
  fora**: o `/admin`, onde o toggle mora, não é afetado, então quem ligou por acidente consegue
  desligar.
- **Zero query acrescentada.** O middleware faz uma leitura de `config()` (array em memória) e
  `hasVerifiedEmail()` sobre o usuário que o `Authenticate` já resolveu.

## Débitos Aceitos

- **QA-02** (Minor): `ip` em claro no context do log de barramento, consistente com o padrão do
  canal `autenticacao`. Se o kit adotar política de mascaramento de IP, o lugar é o canal, não este
  middleware.

## Retrospectiva

- **Funcionou bem**: começar confirmando o diagnóstico no vendor em vez de redescobri-lo. As duas
  linhas que decidiram a feature inteira (`HasAuth.php:174-178` e `HasAuth.php:367-370`) foram
  achadas na primeira leitura de `HasRoutes.php:91` — o `getEmailVerifiedMiddleware($panel)` que o
  requisito citava como parte do problema era, ele mesmo, o ponto de extensão da solução.
- **Funcionou bem**: a revisão adversarial com contexto amputado. Ela produziu 13 achados, um deles
  mudou a implementação, e nenhum era ruído.
- **Faltou no plano**: prever que uma migration de settings nova quebraria um caso de teste por
  **aritmética** (`count(linhas) === count(mapa)` com o `up()` de uma migration só). A revisão
  profunda do step 5 pegou, mas só porque foi procurar — não estava na lista de impacto.
- **Faltou no plano**: a asserção de ausência sobre canal de log compartilhado. Custou uma rodada
  de teste vermelho, e é generalizável o suficiente para virar rule.
