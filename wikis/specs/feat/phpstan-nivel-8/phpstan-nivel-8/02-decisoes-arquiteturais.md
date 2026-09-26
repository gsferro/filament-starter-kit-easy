# Decisões Arquiteturais — PHPStan no level 8

## ADR-01: Level 8, e não 9

**Status**: Aceita
**Data**: 2026-09-26

### Contexto
O pedido é *"as melhores possíveis"*. Medido hoje contra `app`, `config`, `database`, `routes` e
`bootstrap/app.php`: level 8 → **48** erros, level 9 → **474**, `max` → **594** (os dois últimos
de 2026-09-24, roadmap §8.1; o 48 foi remedido hoje e não mudou).

### Decisão
Level 8. O 8 acrescenta uma família só — nulidade — e ela é a que mais produz 500 em produção. O 9
acrescenta `mixed` estrito: todo `config()`, `request()->input()` e `json_decode()` passa a exigir
narração, e boa parte dos 426 erros extras é ruído de framework, não defeito.

### Alternativas Consideradas
1. **Level 9 direto** — diff de centenas de arquivos, irrevisável; e gate vermelho por ruído ensina
   a ignorar gate (o mesmo argumento que manteve `tests/` fora dos paths)
2. **Level 8 com baseline dos 48** — sobe o número sem subir a qualidade; o baseline é exatamente o
   que o `00` (RQ-06) proíbe

### Consequências
- **Positivas**: código novo do projeto que chama método sobre nulo reprova no `composer test`
- **Negativas**: quem estende o kit e escreve código que passava no 7 pode reprovar no 8 — por isso
  a release é minor (0.41.0), e o CHANGELOG diz isso
- **Riscos**: nenhum de runtime; o gate só lê

## ADR-02: URL de painel nula cai na raiz do painel, não num destino inventado

**Status**: Aceita
**Data**: 2026-09-26

### Contexto
`Panel::getLoginUrl()` devolve `null` quando o painel não tem `->login()`
(`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:getLoginUrl:380-387`); `Panel::getUrl()`
devolve `null` quando o painel tem tenant por domínio e ninguém autenticado
(`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170-180`, via
`getRedirectUrl()`). Os três painéis do kit têm `->login()` hoje, então o nulo não acontece **no
kit** — mas acontece no projeto que tirar o login de um painel, e aí `new RedirectResponse(null)`
explode.

### Decisão
`?? url($painel->getPath())` em todo ponto que consome essas URLs. A raiz do painel passa pelo
middleware de autenticação dele, que manda para o login que existir.

### Alternativas Consideradas
1. **`?? url('/')`** — usado em `TelaLoginUnificada` e `LoginSocialController::urlDoPainel()`. Sai
   do painel; para quem estava aceitando convite do `/app`, cair na raiz do site perde o contexto
2. **Lançar exceção** — o nulo é configuração legítima do Filament, não invariante quebrado

### Exceção *(alterado em 2026-09-26: premissa P-03 do `04`)*
`LoginSocialController::urlDeLoginDoPainel()` não cai na raiz do painel de origem: cai no login do
painel **padrão**. A volta do provedor precisa de uma porta de login, e painel sem `->login()` é
tratado como painel inexistente na query — que já cai no padrão. Invariante das duas leituras:
nunca 500, nunca `Location` vazio.

### Consequências
- **Positivas**: um padrão só, o de `app/Support/Paineis.php:urlDoPainel:242`
- **Negativas**: nenhum caminho do kit exercita o fallback, então o teste precisa montar o painel
  sem login — ver `04`

### Referências
- `app/Support/Paineis.php:urlDoPainel:242`

## ADR-03: A anotação errada do `EnsureEmailIsVerified` vai para `ignoreErrors`, não para stub

**Status**: **Substituída pela ADR-05** *(alterado em 2026-09-26: achado RD-06 do step 6.5, Adendo 1 / RQ-09)*
**Data**: 2026-09-26

### Contexto
`Illuminate\Auth\Middleware\EnsureEmailIsVerified::handle()` declara
`@return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null`
(`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:handle:29-31`).
O corpo nunca devolve `null`: ou `abort(403)`, ou `Redirect::guest()`, ou `$next($request)`.
`ExigirEmailVerificado` herda e declara `: Response` (Symfony) — o correto. O level 8 acusa a
diferença.

### Decisão
Exceção em `ignoreErrors`, com escopo **só** do arquivo do middleware e a mensagem exata, no molde
do bloco do Breezy que o `phpstan.neon` já tem.

### Alternativas Consideradas (medidas)
1. **Stub em `stubFiles`** — é a ferramenta certa em tese, e foi tentada. Com o stub declarando o
   `@return` correto, o erro do middleware some, mas a **validação de stub do próprio PHPStan**
   acusa `class.notFound` no tipo de retorno — para `Symfony\Component\HttpFoundation\Response` e
   para `Illuminate\Http\Response`, com e sem `use`, com o stub dentro e fora do projeto. Troca-se
   um erro por outro, e o gate continua vermelho
2. **`return parent::handle(...) ?? $next($request)`** — o `??` nunca dispara, mas lido por
   alguém significa "o pipeline pode rodar duas vezes". Código que mente para calar o analisador
3. **Reimplementar o `handle()`** — a docblock da classe explica por que herdar: três sutilezas que
   uma cópia perde
4. **Declarar `?Response`** — alarga o tipo do kit para acomodar o erro do vendor; proibido pelo
   `00` (RQ-06)

### Consequências
- **Positivas**: o kit continua afirmando o tipo correto
- **Negativas**: uma quarta exceção no `phpstan.neon`. Ela sai quando o Laravel corrigir a
  anotação — e o PHPStan avisa sozinho (`reportUnmatchedIgnoredErrors`, default ligado), como já
  faz hoje com as outras
- **Cobertura de verdade**: `tests/Kit/VerificacaoDeEmailTest.php`

## ADR-04: O título h3 congelado do README

**Status**: Aceita
**Data**: 2026-09-26

### Contexto
`tests/Kit/fixtures/baseline-readme.php` congela os títulos dos READMEs **antes** da migração para
o site, e o `SiteDeDocumentacaoTest` exige que cada um exista, **idêntico**, no README ou numa
página do site. Um deles é *"PHPStan no level 7 — e por que isso é um ponto forte"* (e o
equivalente em inglês). O cabeçalho da fixture proíbe regenerá-la: ela só falsifica *"o conteúdo
migrou"* porque foi medida antes da implementação.

### Decisão
A decidir na implementação, **depois** de ler o teste inteiro — com a restrição fixa de que a
fixture não é editada. As duas saídas aceitáveis: (a) o título fica e o corpo diz o nível atual;
(b) um mapa explícito de renomeação no teste, auditável, que diz "este título do baseline vive
agora com este nome". A escolha e o motivo entram aqui como `*(alterado em …)*`.

## ADR-05: A anotação errada do `EnsureEmailIsVerified` é contornada por guarda de invariante no kit

**Status**: Aceita — substitui a ADR-03
**Data**: 2026-09-26

### Contexto
A ADR-03 descartou stub, `?? $next` e `?Response`, e ficou com uma exceção em `ignoreErrors`. O
revisor de eixos do step 6.5 (RD-06) apontou a alternativa que ela não considerou: a mesma guarda
que o diff usa em outros cinco lugares (`painelCorrente()`, `papelOuFalha()`, `registro()`,
`CreateRole`/`EditRole`).

### Decisão
`ExigirEmailVerificado::handle()` guarda o retorno de `parent::handle()` num local e lança
`LogicException` se ele não for `Response`. O corpo do vendor nunca devolve `null`, então a guarda
não muda comportamento; e ela diz a verdade ao analisador **e** ao leitor, sem exceção no neon.

### Por que ela vence as alternativas da ADR-03
- **`?? $next($request)`** mentia: dizia que o pipeline poderia rodar duas vezes. A guarda não
  promete caminho nenhum — ela declara que o caminho é impossível e para se ele acontecer
- **exceção em `ignoreErrors`** é silêncio com escopo: correta, mas é a única do diff que trata o
  erro fora do código. O inventário volta a três, e o `QualidadeDeCodigoTest` trava três

### Consequências
- **Positivas**: nenhuma exceção nova no `phpstan.neon`; o padrão do diff é um só
- **Negativas**: nenhuma de runtime. Quando o Laravel corrigir a anotação, a guarda vira código
  morto — e o aviso chega mesmo assim: o PHPStan acusa `instanceof` sempre verdadeiro (regra de
  level 4, com `treatPhpDocTypesAsCertain` ligado por padrão), o mesmo papel que o
  `reportUnmatchedIgnoredErrors` cumpria para a exceção
- **Cobertura**: `tests/Kit/VerificacaoDeEmailTest.php` (regressão) e o CT-06 (inventário = 3)

## Superfície Livewire

A feature não cria página, widget nem componente. Toca três componentes existentes **sem mudar a
superfície**:

| Ponto | Alcançável por | Muda? |
|---|---|---|
| `AssistenteChatWidget::enviar/responder/renomearConversa/novaConversa/retomarConversa` | `$wire.` | **não** — mesma assinatura; `assertContexto()` é `private` |
| `AssistenteChatWidget::$mensagemPendente` (sem `#[Locked]`) | `$wire.set()` | **não** — continua revalidada no topo de `responder()`; a leitura passa a ser única |
| `RegistroPorConvite::$convite` (`public ?Convite`) | modelo Eloquent — Livewire serializa por id | **não** |
| `TelaBloqueio::mount()` | `GET` | **não** — só o destino no caso nulo |
