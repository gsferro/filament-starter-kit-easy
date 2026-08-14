# Progresso — Convite em massa

> **Dependência de ordem satisfeita**: `convite-para-usuario-existente` está implementada
> (`recusado_em`, `valido()` excluindo recusados, `aceitarComoUsuarioExistente()`, `recusar()`,
> `situacao()`), então "já tem conta" não é motivo de falha e `recusou_antes` tem em que se apoiar.
> O `->unique('users', 'email')` do form do `/app` **já saiu** naquela wiki (ela registrou como
> desvio) — a nota deste cabeçalho estava desatualizada.
>
> **O passo 7 é independente de tudo** — correção de buraco em código já entregue (ADR-06), em
> commit próprio e primeiro.

## 1. `config/kit.php` — o limite do lote

- [x] `limite_do_lote` dentro do bloco `convites` que já existe
- [x] Comentário dizendo o número **e** a condição (worker de fila)
- [x] `KIT_CONVITE_LIMITE_LOTE=100` no `.env.example`, junto de `KIT_CONVITE_VALIDADE_DIAS`
- [x] `php artisan config:show kit.convites.limite_do_lote` devolve `100`

## 2. `Convite::separarEmails()`

- [x] `public static function separarEmails(?string $texto): Collection`
- [x] Um `preg_split('/[\s,;]+/', …, flags: PREG_SPLIT_NO_EMPTY)`
- [x] Normalização em minúsculas + `trim` em cada endereço — por `Str::of()->trim()->lower()`, ver
      Desvios
- [x] `->unique()->values()` — repetido no texto não é falha
- [x] Aceita `null` sem estourar
- [x] Público e estático porque tem **dois** chamadores (a ação e `convidarEmMassa()`)

## 3. `Convite::convidarEmMassa()`

- [x] Assinatura `(Collection $emails, int $roleId, ?int $tenantId, ?int $convidadoPorId): array`
- [x] PHPDoc com a array shape `array{enviados: list<string>, falhas: list<array{email, motivo}>}`
- [x] **Sem chave `total` e sem `count()`** — a armadilha do `BulkInvitationResult`
- [x] `partition()` por `Validator::make(['email' => …], ['email' => ['email']])` — a mesma regra
      do form individual
- [x] Pré-carregamento de convites: **uma** query, com `whereIn('email', …)` e
      `whereNull('tenant_id')` quando `$tenantId` é nulo
- [x] `pendentes` derivado com as **mesmas** condições de `Convite::valido()`, incluindo
      `recusado_em`; `recusaram` da mesma query, sem uma terceira consulta
- [x] Pré-carregamento de membros: **uma** query, só quando há organização
- [x] Precedência dos motivos: `convite_pendente` → `recusou_antes` → `ja_e_membro`
- [x] `create()` + `enviar()` por endereço, com o **retorno de `enviar()` descartado**
- [x] `catch (Throwable)` por endereço, com `warning` levando `'exception' => $e`
- [x] **Sem transação** envolvendo o lote
- [x] `info` `[Convite@convidarEmMassa]` com `recebidos`, `enviados`, `falhas`, `motivos`
      (`countBy`) e `com_falha` **mascarado**
- [x] Nenhum token em nenhum log, em nenhuma forma — conferido num lote real, ver Verificação
- [x] `enviar()`, `valido()` e `aceitar()` **não** foram tocados

## 4. `App\Filament\Concerns\ConvidaEmMassa`

- [x] `app/Filament/Concerns/ConvidaEmMassa.php`, no padrão de `BadgeContagemNavegacao`
- [x] `acaoDeConvidarEmMassa(Select $papel, bool $escolheOrganizacao = false): Action`
- [x] `->authorize('create', Convite::class)`
- [x] `Textarea::make('emails')->required()->rows(8)`, **sem** `->email()` e **sem**
      `nestedRecursiveRules()`
- [x] `helperText` dizendo o limite e que repetidos são ignorados
- [x] Select de organização só quando `$escolheOrganizacao` **e** com tenancy
- [x] Limite excedido: notificação `danger` + `$action->halt()` — **modal continua aberta**
- [x] Fail-closed sem organização corrente no `/app`, com `warning` no channel `autenticacao`
- [x] `notificarResultadoDoLote()` — título com enviados/não-enviados, corpo com a lista,
      `->persistent()`
- [x] `motivoLegivel()` — os cinco motivos em pt-BR, num lugar só, com `default`

## 5. `/admin` — a ação no header

- [x] `app/Filament/Admin/Resources/Convites/Pages/ListConvites.php` usa o trait
- [x] `getHeaderActions()` com `CreateAction::make()` **e** a ação nova
- [x] Select de papel sem filtro, `escolheOrganizacao: true`
- [x] O rótulo com o painel foi copiado, e o parâmetro da closure se chama `$record`

## 6. `/app` — a mesma ação, carimbada e travada

- [x] `app/Filament/App/Resources/Convites/Pages/ListConvites.php` usa o trait
- [x] Select de papel com `->relationship(… where('painel','app'))` (barreira de UX)
- [x] **`->rule(Rule::exists(roles)->where('painel','app'))`** — a trava de servidor
- [x] `escolheOrganizacao: false` — nenhum campo de organização
- [x] `tenant_id` vem de `Filament::getTenant()`, nunca do payload (CT-09 forja e perde)

## 7. Fechar a assimetria da subtração do `panel_user`

> **Commit próprio, e primeiro.**

- [x] Números anotados **antes**: **38 / 36 / 2** — `View:MyProfilePage` e `View:ConvitesRecebidos`
      fora do alcance. O plano media 37 / 36 / 1; ver Desvios
- [x] `Paineis::mapa()` colhe `getPages()` e `getWidgets()` na mesma volta do laço, com a instância
      limpa que `shieldNovo()` já garante
- [x] `entidadesDoPainel()` privado — `array_column($…, 'key')` para Resource, `array_keys()` para
      Page e Widget
- [x] Chave do mapa vinda de `resourceFqcn` / `pageFqcn` / `widgetFqcn`
- [x] `permissoesDe(string $painel, array $fqcns): Collection` com `->only()` — FQCN exato
- [x] PHPDoc de retorno de `mapa()` com a terceira chave
- [x] `permissoes()` e `resources()` **não** mudaram
- [x] `permissoesDeAdministracaoDoApp()` delega e fica só com a lista de FQCN; a linha de uso
      intacta
- [x] `.ai/rules/filament.md` reescrita para **Resource, Page OU Widget**, com os números medidos, o
      sintoma e a armadilha do formato de `permissions`
- [ ] CT-16 escrito e **visto falhando** antes — não cumprido: escrito depois do método. O que ficou
      provado é o contrário (ver Desvios): com `array_column` na Page o caso fica vermelho
- [x] Os dois seeders rodados (`ShieldPermissionsSeeder`, depois `PapeisSeeder`)
- [x] Contagem de `permissions` (199) e matrizes dos quatro papéis **idênticas** antes e depois —
      diff byte a byte de um dump JSON (`permissions_total`, permissões de cada papel, total do
      painel `app`, quantas de Resource e quais ficam fora)
- [x] `tests/Kit/PaineisTest.php` inteiro verde (23 casos)

## 8. Helpers de teste compartilhados

- [x] `espiarAutenticacao()` movido de `tests/Kit/ConviteTest.php` para `tests/Pest.php`
- [x] `usuarioDoKit()` movido de `tests/Kit/ConviteTest.php` para `tests/Pest.php`, agora reusando o
      `usuario()` que já vivia lá
- [x] `usuarioComPapel()` já estava em `tests/Pest.php` (subiu na wiki irmã) — reusado, não copiado
- [x] `tests/Kit/ConviteTest.php` e `tests/Tenancy/ConviteTenancyTest.php` continuam verdes, sem
      mudança de expectativa

## 9. Documentação

- [x] `wikis/receitas.md` — `## Convidar várias pessoas de uma vez`, com a tabela de resultados e o
      que fazer com cada motivo
- [x] `wikis/convencoes.md` — três armadilhas novas + um item na seção `## Autorização`
- [x] `wikis/arquitetura.md` — a entrada em lote, sem segundo fluxo de envio
- [x] `README.md` e `README.en.md` — `KIT_CONVITE_LIMITE_LOTE` e a nota do worker multiplicada por N
- [x] `.env.example`
- [x] `.ai/rules/filament.md` — feito no passo 7c

## Testes

- [x] `tests/Kit/ConviteEmMassaTest.php` — CT-01 a CT-04, CT-06 a CT-08, CT-10 a CT-13, CT-15
      (12 casos, 19 testes com o dataset)
- [x] `tests/Tenancy/ConviteEmMassaTenancyTest.php` — CT-05, CT-09, CT-14 **+ um caso que o `04` não
      previu** (`it('usa a organizacao escolhida no lote do admin')`): 4 casos. Ver Desvios
- [x] `tests/Kit/PaineisTest.php` — **CT-16**, no arquivo que já existe
- [x] Helpers locais com nomes que não colidem (`chamarLote()`, `papelDoLote()`,
      `papelDoLoteTenancy()`, `entrarNoPainelDa()`)
- [ ] CT-02 e CT-12 vistos falhando antes — não cumprido nesta rodada (código escrito antes dos
      testes). Duas falhas reais fizeram o papel, ver Notas
- [x] CT-10 visto **falhando** com o `catch` estreitado para `QueryException` — o único caso que
      fica vermelho
- [x] CT-06 confere que a modal **continuou montada** (`assertActionMounted`)

## Verificação Final

- [x] Um lote com um endereço torto no meio: os outros chegam e a modal não reprova (CT-02, com
      `assertHasNoActionErrors()`)
- [x] Nenhum token e nenhum endereço em claro no `autenticacao.log` depois de um lote real de três
      endereços rodado no banco de desenvolvimento (`grep -c token` = 0)
- [x] Um usuário só com `panel_user` não pode criar convite (`can('create', Convite::class)` falso).
      A asserção de tela ficou no `/admin` com um papel de leitura — ver Desvios
- [x] Assimetria fechada: `permissoes('app')` = 38, e `permissoesDe('app', $todosOsFqcns)` = 38.
      Antes: 36 alcançáveis, 2 não
- [x] `php artisan test --compact tests/Kit/KitUpdateTest.php` verde **sem** editar `CAMINHOS_DO_KIT`
- [x] `vendor/bin/pint --dirty --format agent` limpo, `composer types:check` 0 erros
- [x] `php artisan test --group=kit` — **197 passando, 0 falhando** (baseline medido antes de tocar
      em qualquer coisa: 173, e 173 + 24 casos novos = 197); suíte rodada duas vezes
- [ ] `git commit` — três, na ordem do plano. **Não feito**: a instrução da execução pediu a árvore
      suja

## Desvios do Plano

| Passo | O que mudou | Por quê |
| --- | --- | --- |
| 7 | Os números medidos são **38 / 36 / 2**, não 37 / 36 / 1 | A wiki irmã `convite-para-usuario-existente` registrou `App\Filament\App\Pages\ConvitesRecebidos` no painel `app` **depois** de o plano ser escrito. É uma Page, e é a segunda permission que a subtração antiga não alcançava. Reforça ADR-06 em vez de contradizê-la: a "próxima Page" que o plano temia chegou em duas semanas — e ela é de todo mundo, então continuou inofensiva por sorte |
| 7a | `permissoesDe()` usa `flatMap()`, não `flatten()` | `flatten()` é `static<int, mixed>` nos stubs do Laravel, e o PHPStan nível 6 reprova `Collection<int, mixed>` como `Collection<int, string>` |
| 2 | A normalização é `Str::of($email)->trim()->lower()->toString()`, não `mb_strtolower(trim($email))` | Mesmo resultado, mas `mb_strtolower` tipa como `lowercase-string`, e `Collection` é **invariante** no PHPStan: `Collection<int, lowercase-string>` não satisfaz `Collection<int, string>` e a assinatura pública era reprovada. O comentário no código diz isso, senão a "simplificação" volta |
| CT-12 | A ponta "não vê a ação" usa um papel de leitura criado no caso (`ViewAny:Convite` + `View:Convite`), no `/admin`, e não o `panel_user` | Como o CT foi escrito, o caso não testava o `->authorize()`: `panel_user` não abre o `/admin` de forma alguma, então `Livewire::test(ListConvites::class)` morre em `Attempt to read property "mountedActions" on null` — 403 antes de haver ação para esconder. Com um papel que **enxerga** a listagem sem poder criar, o caso prova o que ADR-02 pede. O `panel_user` continua no caso, pela permission |
| CT-08 | `motivos` é comparado com `==` e não `===` | A ordem das chaves do `countBy` segue a ordem em que os motivos apareceram no lote (`formato_invalido` vem antes porque as falhas de formato entram na lista antes do laço). `===` em array compara ordem, e o caso travaria uma decisão de implementação em vez do conteúdo |
| CT-04 | A metade "lista de falhas vazia" é asserida pelo model, com um usuário criado no caso | Pela tela só dá para contar convites e notificações; a lista de falhas é retorno do método. Duas chamadas em vez de uma, e a asserção fica no lugar em que o dado existe |
| Testes | **Um caso a mais na suíte de tenancy**: `it('usa a organizacao escolhida no lote do admin')` | Os dezesseis CTs deixavam o campo de organização do `/admin` **sem nenhuma cobertura**: ele só existe com tenancy ligada, e os três casos de tenancy do plano são todos do `/app`. Era o único trecho do lote que ninguém montava — e é um `Select` com `->relationship()->preload()` dentro de uma modal cujo formulário não tem registro, exatamente o tipo de coisa que estoura só ao renderizar. Passa |
| 9 | `wikis/pacotes.md` não foi editado | Nenhum pacote novo. O plano não pedia |
| — | `usuarioDoKit()` passou a reusar o `usuario()` de `tests/Pest.php` | O corpo era o mesmo `User::create()` com outro nome (`'Teste'` × `'Usuário'`), e nenhum caso da suíte assere o nome |

## Notas de Implementação

Quatro coisas que o plano não previu, todas descobertas executando.

1. **`Collection` é invariante no PHPStan, e isso decide a implementação de `separarEmails()` e de
   `permissoesDe()`.** Os dois métodos foram escritos exatamente como o plano os especificou e os
   dois foram reprovados pelo `types:check` — um por `lowercase-string`, outro por `mixed`. A lição
   é geral: método público que devolve `Collection<int, string>` não pode montar a coleção com
   `mb_strtolower()` nem com `flatten()`. Nenhum dos dois é erro de lógica, e nos dois o PHPStan
   estava certo em recusar.

2. **`Livewire::test()` de uma `ListRecords` que o usuário não pode acessar não falha dizendo
   "403".** Falha em `Attempt to read property "mountedActions" on null`, dentro do
   `assertActionHidden()` — o componente nunca foi montado. Sintoma que manda a investigação para o
   lado da ação, quando o problema é acesso à página. Foi o que reescreveu CT-12.

3. **`===` em `countBy()` compara a ordem das chaves.** A asserção de log de CT-08 falhou com
   `motivos` corretíssimo, e a mensagem do Mockery (`should be called at least 1 times but called 0
   times`) não diz **qual** argumento não casou: o `withArgs` devolve um booleano, e o Mockery só
   sabe que ele deu `false`. Vale isolar as condições ao depurar um `withArgs` de log.

4. **CT-10 foi visto falhando de propósito, com o `catch (Throwable)` estreitado para
   `catch (QueryException)`.** O caso ficou vermelho com a exceção **crua** subindo até o teste
   (`RuntimeException: SMTP fora do ar`, na linha da chamada): a exceção do `MessageSending` volta
   pelo `SyncQueue::handleException()`, não casa com o `catch` estreito e **derruba o lote inteiro** —
   `depois@example.com` nunca é convidado, e não há resultado nenhum para inspecionar. É exatamente o
   defeito do `laravel-invite-only`, reproduzido no repositório. Medido: com o `catch` estreitado,
   **18 de 19** casos do arquivo passam e só este falha. É o único que acusa.

5. **Não edite arquivo de teste com a suíte rodando.** Uma execução do grupo `kit` reportou 196 e a
   seguinte 197, com o mesmo código — a primeira tinha começado antes de um caso novo ser
   acrescentado ao arquivo de tenancy, e o Pest colheu o arquivo já em andamento. O sintoma é um
   número de testes que "some" e uma conta que não fecha (`--list-tests` dizia 197 desde então).
   Vale para qualquer suíte longa: se o total surpreender, reconfira que nada foi salvo durante a
   execução antes de procurar teste pulado.

Duas confirmações de leitura do vendor que se sustentaram: `callAction()` aceita
`string | TestAction | array` com o state no segundo argumento
(`vendor/filament/actions/src/Testing/TestsActions.php:78-80`) e `assertActionMounted()` existe
(`:411`); e `FilamentShield::getEntityPermissionKeys()` (`:140-145`) usa `array_keys()` para Page e
Widget, que é o caminho que `entidadesDoPainel()` copiou.

### Números medidos

| | Antes | Depois |
| --- | --- | --- |
| Testes do grupo `kit` | 173 | 197 |
| Permissions no banco | 199 | 199 (o passo 7 não cria nem apaga permission) |
| `permissoes('app')` | 38 | 38 |
| Alcançáveis pela subtração no `app` | 36 | **38** |
| Permissions do `panel_user` | 14 | 14, as mesmas |

## Retrospectiva

- **Funcionou**: ler o `PapeisSeeder` e o `Paineis` **antes** de escolher onde a tela vive. A
  pergunta parecia de ergonomia (modal × página) e virou de autorização.
- **Funcionou melhor**: **medir** o que a leitura sugeriu em vez de parar na decisão de desviar. Os
  números mostraram que a assimetria está aberta hoje, e o caso concreto é inofensivo — que é
  exatamente por que ninguém teria olhado. Virou o passo 7 e a ADR-06. Lição: quando a decisão é
  "desviar do problema X", vale um comando para saber se X já está acontecendo.
- **Funcionou, e mediu-se de novo na implementação**: os números do plano já estavam **velhos** duas
  semanas depois (37 / 36 / 1 → 38 / 36 / 2), e a diferença era uma Page nova de uma wiki irmã. Medir
  antes de tocar no código não é ritual: é o que faz a "prova de que nada mudou" valer alguma coisa.
- **Funcionou**: ler o JS do `TagsInput` em vez de assumir que o componente nativo resolvia a
  entrada — o `paste` divide pelos `splitKeys`, e quebra de linha não é um deles.
- **Funcionou**: estreitar o `catch` de propósito para ver CT-10 falhar. É a diferença entre um caso
  que passa e um caso que **prova** — e custou dois minutos.
- **Faltou no plano**: nenhum dos dois problemas de tipo era previsível lendo o vendor, e os dois
  estavam no código que o plano transcreveu pronto. Plano que entrega o corpo do método deveria
  passar pelo `types:check` da máquina antes de virar checkbox — ou avisar que o corpo é rascunho.
- **Faltou no plano**: CT-12 foi escrito com a persona errada. O plano sabia que `panel_user` não
  administra o `/app`, mas escreveu o caso no `/admin`, onde essa persona não entra nem para olhar.
  Caso de autorização precisa nomear **o que o sujeito pode fazer** além do que não pode, senão ele
  testa a barreira anterior.
