# Progresso — Convite para quem já tem conta

## 1. Coluna `recusado_em`

- [x] `database/migrations/2026_08_14_000001_add_recusado_em_to_convites_table.php`
- [x] `down()` derruba a coluna
- [x] `php artisan migrate` roda limpo nos dois modos

## 2. `Convite`

- [x] `recusado_em` no `$fillable` e nos `casts()`
- [x] `valido()` passa a excluir `recusado_em`
- [x] `atribuirPapel(User $user)` extraído do `aceitar()` (dois chamadores)
- [x] `exigirDono(User $user)` — a asserção de e-mail, normalizada
- [x] `aceitar()` desvia em vez de lançar para e-mail existente
- [x] `aceitarComoUsuarioExistente(User $user): User` com `update` condicional
- [x] `recusar(User $user): void` com `update` condicional
- [x] ~~`paraUsuarioExistente(): bool`~~ → `usuarioExistente(): ?User` (ver Desvios)
- [x] `pendentesPara(?User $user): Builder`
- [x] `situacao(): string` — quatro estados, aceito vencendo expirado
- [x] `email_verified_at` no aceite de conta nova — `forceFill` depois do `create()`, porque a
      coluna está fora do `$fillable` do `User`
- [x] Logs em `autenticacao`: aceite (`info`), recusa (`warning`), e-mail divergente (`warning`)
- [x] `token` continua fora do `$fillable` e em `$hidden`

## 3. Caixa de entrada

- [x] `app/Filament/App/Pages/ConvitesRecebidos.php`
- [x] Query por `Convite::pendentesPara()` — não um `where` na página
- [x] Ações `aceitar` e `recusar`, as duas com `requiresConfirmation()`
- [x] Redireciona para `/app/{slug}` depois de aceitar
- [x] `canAccess()` amarrado a `config('kit.tenancy.enabled')`

## 4. Item de menu com contagem

- [x] `Action` no `bootUsing()` do `AppPanelProvider`, no padrão de `TelaBloqueio::itemDeMenu()`
- [x] `->badge(… ?: null)` — badge zero não aparece
- [x] `->visible()` amarrado a haver oferta

## 5. `RegistroPorConvite`

- [x] Ramo do meio no `mount()`, antes de `parent::mount()`
- [x] `desviarParaAceite(): never` por `HttpResponseException` (nunca `redirect()` cru)
- [x] Autenticada com o e-mail certo → aceita e vai para a organização
- [x] Autenticada com outro e-mail → notifica e mantém a sessão
- [x] Não autenticada → login, **sem** consumir o token
- [x] Aceite pós-login **não** é automático

## 6. Notificação

- [x] `ConviteDeAcesso::toMail()` alterna o texto — uma classe, dois textos
- [x] Token só na URL do botão, nunca no corpo

## 7. Form e tabelas

- [x] `->unique('users','email')` removido do `ConviteForm` (e o comentário)
- [x] `->unique('users','email')` removido **também** do form do `/app`
      (`App\Filament\App\Resources\Convites\ConviteResource`) — estava de pé e CT-17 o
      derrubou. Ver Desvios.
- [x] `helperText` explicando a bifurcação, nos dois forms
- [x] `situacao()` sai para o model; as duas tabelas passam a usá-lo
- [x] A tabela do `/app` ganha coluna de situação (mostrava `aceito_em` com placeholder
      "Pendente", que mentiria para um convite recusado)
- [x] Cor `gray` para recusado, nas duas

## 8. `kit:update`

- [x] `tests/Kit/KitUpdateTest.php` verde (`app/Filament` e `tests/Tenancy` já cobrem os
      arquivos novos; a varredura não descobriu nada fora da lista)

## 9. Regra de IA

- [x] `.ai/rules/filament.md`: asserção de identidade vive no model, não na query da tela

## 10. Documentação

- [x] `wikis/arquitetura.md` — `#### Duas vias, decididas no aceite`, com a tabela de qual
      token basta em cada via, os dois caminhos de aceite e o `update` condicional
- [x] `wikis/convencoes.md` — três armadilhas novas: asserção no model (sintoma do teamkit),
      consumo por `update` condicional, e o placeholder "Pendente" da tabela do `/app`
- [x] `wikis/receitas.md` — `## Convidar alguém que ainda não tem conta` virou
      `## Convidar alguém`, com as duas vias; `## Problemas comuns` ganhou "este convite não é
      para esta conta" e "não vejo meus convites"
- [x] `README.md` e `README.en.md` — as duas vias e a recusa registrada; espelhos exatos
      (18 seções `##` e 159 linhas de tabela nos dois)

## Testes

- [x] `tests/Tenancy/ConviteUsuarioExistenteTest.php` — CT-01, CT-02, CT-05 a CT-07,
      CT-09 a CT-12, CT-15 a CT-17 (12 casos)
- [x] `tests/Kit/ConviteUsuarioExistenteTest.php` — CT-04, CT-08, CT-13, CT-14 (9 casos,
      contando as 6 linhas do dataset de `situacao()`)
- [x] Helper `usuarioComPapel()` extraído para uso compartilhado — foi para `tests/Pest.php`,
      junto com `tenant()`, `usuario()` e `papelNaOrganizacao()`. Ver Desvios.
- [x] Caso invertido em `tests/Kit/ConviteTest.php` (já vinha da implementação)
- [ ] ~~CT-01 e CT-13 vistos **falhando** antes da implementação~~ — impossível: os testes
      foram escritos depois do código. O que se viu falhar foi CT-17, e ele encontrou um
      `unique` esquecido. Ver Desvios.

## Verificação Final

- [x] Diff revisado em modo ponytail: nenhuma abstração nova nos testes além de três helpers
      com dois ou mais chamadores cada (`ofertaPara`, `convitePara`, `carlaDaGlobex`), e a
      alteração de `app/` é uma remoção
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check` — 0 erros
- [x] `php artisan test --group=kit` — 173 passando, 461 asserções (152 antes + 21 novos)
- [x] Suíte rodada duas vezes; `git status --short` sem sujeira nova
- [x] `git commit` — commitado e mergeado em `main` (`git branch --no-merged main` vazio)

## Blockers

- [x] **Como o token chega ao componente Livewire nos testes** — resolvido: **query string**,
      nunca o construtor. `RegistroPorConvite::mount()` é `mount(): void` e lê
      `request()->query('token')`, então `livewire(…, ['token' => …])` não tem onde entregar o
      valor e o `mount()` cairia no ramo de convite inválido. Duas formas, conforme o que se
      prova:
      - **formulário dentro do componente** → `Livewire::withQueryParams(['token' => …])->test(...)`,
        que é o que `aceitarConvite()` (`tests/Kit/ConviteTest.php:75`) já fazia desde a wiki
        anterior;
      - **os desvios do `mount()`** → request HTTP `get("/app/register?token={$token}")`. As
        três saídas de `desviarParaAceite()` são `HttpResponseException`; num `Livewire::test()`
        a exceção sobe e derruba o caso, e é o request que a traduz em redirect.

      Os dois `04-casos-de-teste.md` (desta wiki e da `convite-de-usuario`) foram corrigidos.

## Desvios do Plano

- **`paraUsuarioExistente(): bool` não existe** — a implementação tem
  `usuarioExistente(): ?User`, um método para as duas perguntas (o `aceitar()` precisa do
  objeto para desviar, o `mount()` só precisa saber se existe). É o que o próprio passo 5 do
  plano já pedia; o checkbox do passo 2 ficou com o nome antigo.
- **O `->unique('users','email')` do `/app` estava de pé.** O passo 7 do plano fala só do
  `ConviteForm` do `/admin`, e o form do painel `app` é outro: ele vive dentro de
  `App\Filament\App\Resources\Convites\ConviteResource::form()`, com o comentário "quem
  preenche já administra a organização e um convite para quem já tem conta falharia no
  aceite". Resultado: a feature estava **desligada exatamente para a persona que a motivou** —
  o `admin_app` continuava sem caminho para convidar quem já tem conta. CT-17 foi
  escrito como a wiki manda, falhou com
  `"data.email" => ["O valor indicado para o campo e-mail já se encontra registrado."]`, e a
  correção foi remover a regra e trocar o `helperText` pelo texto da bifurcação. **É a única
  alteração de `app/` feita nesta rodada de testes+documentação.**
- **Helper compartilhado foi para `tests/Pest.php`**, não para um arquivo novo. O kit não tinha
  lugar de helper compartilhado: cada suíte declarava os seus no primeiro arquivo que
  precisava (`TenancyTest.php`, `ConviteTest.php`). Em Pest a função é global no processo, então
  declarar `usuarioComPapel()` num segundo arquivo seria fatal error de redeclaração, e mantê-la
  em `TenancyTest.php` faria `php artisan test tests/Tenancy/OutroTest.php` (arquivo isolado)
  morrer com "undefined function". `tests/Pest.php` é carregado sempre e já está em
  `KitUpdate::CAMINHOS_DO_KIT`. Foram quatro: `tenant()`, `usuario()`, `usuarioComPapel()` e
  `papelNaOrganizacao()`. `TenancyTest.php` e `AdminDaOrganizacaoTest.php` continuam usando as
  mesmas funções, sem alteração de chamada.
- **CT-03 não existe** — o `04-casos-de-teste.md` pula de CT-02 para CT-04 (o índice também).
  Nada foi escrito no lugar.
- **CT-01 e CT-13 não foram vistos falhando antes**, porque nesta rodada o código já estava
  implementado. A barreira que a wiki queria (ver falhar antes de implementar) foi cumprida
  por acidente e no lugar mais útil: CT-17.

## Notas de Implementação

- **`assertRedirect()` com URL exata é frágil na via de oferta.** `Panel::getUrl()` com tenancy
  resolve a organização do usuário autenticado, então o destino do desvio "outra conta" depende
  de quais organizações o Bruno tem. CT-02 usa `assertRedirectContains('/app/acme')` e CT-11
  usa `assertRedirect()` sem argumento; o que os casos precisam provar é o **destino do
  desvio**, não a string.
- **`hasRole()` depois de um request HTTP mente.** O `PermissionRegistrar` fica com o contexto
  que o middleware deixou (`CONTEXTO_GLOBAL` num request sem organização), então
  `$user->fresh()->hasRole('panel_user')` devolve `false` para um papel gravado no team da
  Globex. Foi a primeira versão de CT-11, e o teste falhava por motivo nenhum. Asserção certa é
  na pivot, com `team_id` explícito.
- **O badge do item de menu volta como string.** `Action::getBadge()` devolve `'2'`, não `2` —
  daí o cast em CT-15. A asserção fala de contagem, não do tipo com que a view a imprime.
- **Dataset de `situacao()` usa string relativa** (`'+1 day'`, `'now'`) e não `now()->addDay()`:
  o `->with([...])` é avaliado quando o arquivo é carregado, antes de a aplicação bootar, e
  `now()` ali dependeria de um container que ainda não existe. O cast `datetime` do model
  resolve a string.
- **O corpo do e-mail é quoted-printable.** CT-16 decodifica antes de asserir (as quebras a cada
  76 colunas cortam frases e o token ao meio). A asserção "o token só aparece na URL" é
  `substr_count($corpo, 'token='.$token) === substr_count($corpo, $token)`, que continua valendo
  se a parte texto do e-mail repetir a URL.
- **`ConviteFactory` concede `master_global` por default.** `role_id` sai de
  `Config::roleModel()::query()->value('id')` — o primeiro papel da tabela. Os dois helpers
  novos (`ofertaPara()`, `convitePara()`) passam `role_id` explícito, e o PHPDoc dos dois diz
  por quê.
- **`Log::shouldReceive('channel')->with('autenticacao')`** é o padrão que a suíte de tenancy já
  usa e não depende de `espiarAutenticacao()` (que vive em `tests/Kit/ConviteTest.php` e não
  estaria carregado numa execução só de `--testsuite=Tenancy`).

## Retrospectiva

- **Funcionou bem**: a análise dos dois pacotes antes de escrever o plano deu três decisões
  de graça — a asserção no model (o furo do teamkit), o `update` condicional (o furo do
  invite-only) e a decisão de não instalar nenhum dos dois. Ler código alheio pagou mais que
  ler o README.
- **Funcionou bem, parte 2**: CT-17 pagou o preço de estar na wiki. Era o caso "óbvio" —
  a persona que a feature existe para servir — e foi o único que falhou. Um `unique` esquecido
  num segundo formulário não aparece em revisão de diff (o diff não mostra o que **não** mudou),
  e nenhum outro CT passaria por ele.
- **Faltou no plano**: o passo 7 tratou "o form do convite" como se houvesse um. Há **dois**,
  em painéis diferentes, e o do `/app` tem schema próprio dentro do Resource em vez de uma
  classe `Schemas/`. Plano que nomeia um arquivo por comportamento deveria ter perguntado
  "quantos arquivos fazem isso?" — foi a mesma pergunta que salvou `situacao()`, que o plano
  acertou justamente por ter contado as duas telas.
- **Faltou no plano**: nenhuma menção a onde helper de teste compartilhado mora. A wiki
  `admin-da-organizacao` já tinha pedido a extração de `usuarioComPapel()` e ela não aconteceu
  porque não havia lugar; agora há (`tests/Pest.php`), e é onde a próxima vai.
