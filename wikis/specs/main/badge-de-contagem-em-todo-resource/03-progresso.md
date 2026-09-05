# Progresso — Badge de contagem em todo Resource do kit

Wiki criada em 2026-09-04. **Implementação concluída em 2026-09-04.**

## 1. `BadgeContagemNavegacao`: o zero aparece, e a cor o distingue

- [x] `getNavigationBadge()` sempre devolve o badge — o `return null` no zero sai
- [x] `getNavigationBadgeColor()` declarado: `'gray'` no zero, `null` (default do Filament) acima
- [x] `contagemDoBadge()` privado, memoizando com `once()`
- [x] Contagem continua saindo de `getEloquentQuery()`, nunca de `getModel()::count()`
- [x] Docblock reescrito — a frase "Zero não vira badge" sai e aponta ADR-01
- [x] Sem log

## 2. `RoleResource` recebe o trait, com as três colisões resolvidas

- [x] `use BadgeContagemNavegacao, Essentials\HasNavigation { ... }` num bloco único
- [x] Três `insteadof`: `getNavigationBadge`, `getNavigationBadgeColor`, `getNavigationBadgeTooltip`
- [x] Os outros quatro `use Essentials\...` intactos
- [x] Comentário no bloco apontando ADR-02, para ninguém "limpar" a sintaxe e derrubar o boot

## 3. `ComposerReleasePackageResource` recebe o trait

- [x] `use BadgeContagemNavegacao;` + import
- [x] Sem `insteadof` — o resource pai não declara método de badge (verificado)

## 4. O enforço: teste que reprova Resource do app sem o trait

- [x] `tests/Kit/BadgeDeNavegacaoTest.php`
- [x] Lista derivada de `Filament::getPanel($id)->getResources()` nos três painéis, **nunca escrita à mão**
- [x] Filtro `str_starts_with($fqcn, 'App\Filament\')` — a fronteira de escopo que o usuário decidiu

## Testes

- [x] `tests/Kit/BadgeDeNavegacaoTest.php` — CT-01, CT-02, CT-04, CT-05, CT-06, CT-07
- [x] `tests/Tenancy/BadgeDeNavegacaoTenancyTest.php` — CT-03, CT-08

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent` — dois passes; o segundo por causa do `ordered_traits`
- [x] `php artisan test --compact tests/Kit/BadgeDeNavegacaoTest.php tests/Tenancy/BadgeDeNavegacaoTenancyTest.php` — **11/11**
- [x] Regressão: `php artisan test --compact` com `PermissoesDeResourcesTest` junto — **56/56**
- [x] Regressão ampla: `--testsuite=Kit` **1691/1691** e `--testsuite=Tenancy` **271/271** —
      1962 casos, zero regressão
- [x] `pest --mutate --path=app/Filament/Concerns/BadgeContagemNavegacao.php` — **87,5%**, 7 mortos, 1 não coberto (o tooltip, lacuna já declarada no `04`)
- [x] `php artisan about` — o boot não estoura; rodado **antes e depois** do passo 2
- [x] Badge conferido nos três painéis — por **teste descartável** enumerando
      `Filament::getPanel($id)->getResources()`, não por navegador. Os 10 Resources do app
      devolveram badge; `RoleResource` e `ComposerReleasePackageResource` inclusive
- [ ] `git commit` — **não executado**: o usuário não pediu commit nesta rodada

> **`--parallel --tia` não foi usado, e `--group=kit` também não.** As duas tentativas de
> `--group=kit` foram **mortas por falta de memória** — o grupo arrasta a suíte de browser, que
> sobe Playwright. O que rodou foram as duas suítes explícitas, `--testsuite=Kit` e
> `--testsuite=Tenancy`, que cobrem o mesmo conjunto sem o navegador. Divergência do que o PRD
> escreveu, registrada aqui em vez de marcada como feita.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

Sete verificações, quatro delas **executadas** e não apenas lidas.

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "Convites" não tem badge | **falso.** `ConviteResource` do admin **já usa** o trait; a tabela `convites` tem **0 registros** e o trait esconde o zero | levado ao usuário antes de qualquer plano; virou a decisão de ADR-01 e reescreveu RQ-01 no `00` |
| aplicar o trait em `RoleResource` seria só acrescentar um `use` | **falso, e fatal.** Medido, aplicando o trait sem resolução: `PHP Fatal error: Trait method ...HasNavigation::getNavigationBadge has not been applied ... because of collision with ...BadgeContagemNavegacao::getNavigationBadge`. `php artisan about` morre | virou ADR-02, com a mensagem transcrita e o passo 2 reescrito |
| um `insteadof` bastaria | **falso: são três.** `Essentials\Resource\HasNavigation` declara `getNavigationBadge` (`:41`), `getNavigationBadgeColor` (`:46`) e `getNavigationBadgeTooltip` (`:51`) | **executado**: com os três `insteadof`, `RoleResource::getNavigationBadge()` devolveu o odômetro com `5` e `getNavigationBadgeColor()` devolveu a cor do trait |
| `once()` memoiza dentro de método estático | **confirmado por execução**: duas chamadas, uma execução do callback, valor correto | sustenta ADR-03 |
| `ComposerReleasePackageResource` também colidiria | **falso.** O resource pai do vendor estende `Filament\Resources\Resource` puro e não declara método de badge | passo 3 fica sendo um `use` simples |
| o `04` declarou lacuna de soft delete alegando que só `User` a tinha | **falso.** `Projeto` também usa `SoftDeletes`, e **`ProjetoResource` está registrado** no painel `app` da suíte `Tenancy` (medido com teste descartável) | lacuna falsa convertida em **CT-08**, com M15 novo. É o caso que a skill chama de "impossibilidade de arnês é hipótese, não conclusão" |
| `Convite` teria soft delete e serviria de fixture para CT-08 | **falso.** Só `Projeto` e `User` usam `SoftDeletes` | `## Fixtures` do `04` corrigido; CT-08 usa `Projeto` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `delete:` a linha "vazio" (contagem 0) do `Esquema` de CT-02 — CT-04 já afirma o mesmo número, e com mais força (existência do badge **e** cor) | **sim** — nenhum mutante ficou sem matador | `04`, R2 |
| 2 | `delete:` "passo 5 — Candidato a Project Rule" não é passo de implementação; é o step 9 da skill | **sim** — virou nota depois do passo 4 | `01`, Estrutura de Implementação |
| 3 | `delete:` a seção `## Mapeamentos` repetia a tabela de estado→cor que já está em ADR-01 | **sim** | `01` |
| 4 | `yagni:` memoização com `once()` antes de medir custo | **recusada** — não é hipótese: a cor e o número precisam do **mesmo** `count()` por construção, desde a primeira linha de código. É uma linha de stdlib contra duplicar toda consulta de menu | `01`, passo 1 |
| 5 | `delete:` CT-07 (Resource de vendor não é cobrado) parece defensivo demais | **recusada** — ele protege a fronteira de escopo que o usuário decidiu. Sem ele, alguém inclui vendor na varredura, 9 classes inintocáveis ficam vermelhas, e a reação provável é desligar o enforço inteiro | `04`, R5 |

Segunda passada da wiki não executada: os cortes só removeram conteúdo — nenhum arquivo, passo ou
cenário novo, logo nenhuma superfície nova a auditar.

### Auditoria Ponytail do DIFF (step 7)

Rodada depois de implementar, sobre os cinco arquivos da entrega. Os três de produção passaram sem
achado. Os três achados são todos no arquivo de teste, e **dois deles não eram complexidade — eram
oráculo fraco que a revisão de complexidade desenterrou**:

| # | Achado | Aplicado? | Efeito |
|---|---|---|---|
| 1 | `yagni:` `resourcesSemBadge()` era uma **segunda** varredura, estrutural (*"usa o trait?"*), que só CT-06 consumia; CT-01 usava outra, comportamental (*"devolve badge?"*). CT-06 testava a varredura que **não** enforça | **sim** | as duas colapsaram numa só, e o oráculo que sobrou é o comportamental — sobrevive a um `use` inerte, que é a armadilha documentada em `ExigePermissaoDoWidget` |
| 2 | `delete:` a asserção de CT-07 era **tautologia**: comparava dois conjuntos calculados com o mesmo `str_starts_with`, um o complemento do outro. Nenhuma implementação a faria falhar | **sim** | CT-07 passou a ancorar numa classe concreta de vendor (`ExceptionResource`) e a afirmar as três coisas que importam: está registrada, está fora do escopo, e não tem badge |
| 3 | `shrink:` CT-05 chamava `getNavigationBadge()` duas vezes | **sim** | uma variável |

**Discriminância do enforço, medida**: com o trait comentado em `ComposerReleasePackageResource`,
CT-01 reprova com a mensagem `Resources do app sem badge:` seguida do FQCN da classe. Restaurado o
trait, 8/8 verde. O enforço não é decorativo.

## Degradações declaradas

- **`search-docs` indisponível.** O MCP `laravel-boost` respondeu `CONNECT_TIMEOUT` durante toda a
  sessão. Toda API citada foi confirmada por **leitura direta do vendor** com `arquivo:linha`
  (`HasNavigation` do Filament e do PluginEssentials, `OdometerNavigationBadge`), e quatro
  premissas foram **executadas** em vez de lidas — ver a tabela acima.
- **`pest --agent` não está disponível** neste projeto: `pestphp/pest-plugin-agent` não está
  instalado (`vendor/pestphp/` tem pest, plugin, arch, browser, laravel, mutate, phpstan,
  profanity). As sondagens do step 5 foram feitas com testes descartáveis em `tests/Kit` e
  `tests/Tenancy`, criados e removidos na mesma execução.

## Candidatos a Rule (step 9)

**Um candidato, aprovado pelo usuário.** Dois fatos que queriam virar rule tinham o **mesmo glob**,
então viraram **uma seção só** — e numa rule que já existe, em vez de arquivo novo. Teto da skill é
3 candidatos; ficou em 1.

| Candidato | Glob | Gates | Decisão |
|---|---|---|---|
| Resource do app nasce com badge de contagem + a armadilha da colisão de trait | `app/Filament/**` (já indexado) | durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅ | **aprovado** — seção nova em `.ai/rules/filament.md` |

Por que os gates passam, e o terceiro é o que menos parece passar: *"um agente competente, lendo o
código ao redor, erraria?"* — **sim, e a prova é o histórico**. Oito dos dez Resources do app já
usavam o trait, e `RoleResource` e `ComposerReleasePackageResource` ficaram de fora assim mesmo.
Copiar o vizinho não bastou.

A rule é curta na parte que a máquina cobre (aponta para `tests/Kit/BadgeDeNavegacaoTest.php`) e
longa na parte que a máquina **não alcança**: a colisão de trait, com a mensagem de erro
transcrita e os três `insteadof`. Nenhum teste captura erro fatal de compilação.

### Desvio declarado — a rule foi escrita à mão

A skill manda gravar **sempre** por `record-rule` do Boost, porque ele regenera o
`.ai/rules/index.md` e rule criada à mão não é descoberta até a próxima regeneração.

O MCP `laravel-boost` **não conectou nesta sessão** (`CONNECT_TIMEOUT`), confirmado por
`ToolSearch`. A seção foi escrita à mão em `.ai/rules/filament.md`, e o motivo da proibição **não se
aplica aqui**: o glob `app/Filament/**` já consta do `index.md` (linha 11), então a seção nasce
descoberta sem regeneração nenhuma. Nenhuma linha do índice foi tocada.

Se o Boost voltar, vale rodar `boost:update` para conferir que o índice continua coerente.

## Blockers

- Nenhum.

## Desvios do Plano

Quatro, todos de **arranjo de teste** — nenhum passo de produção mudou de forma.

- **CT-02, CT-04 e CT-05 não usam `ofertaPara()`.** O helper global exige os papéis semeados
  (`RoleDoesNotExist: panel_user`), e semear a matriz do Shield custaria segundos por caso para
  produzir um `role_id` que nenhuma asserção olha. O arquivo declara um helper local
  `convites(int)` que cria um papel qualquer com `Role::firstOrCreate()` e usa a
  `ConviteFactory`.
- **CT-03 e CT-08 não usam `duasOrganizacoes()`.** Mesmo motivo: aquele helper monta também uma
  pessoa com papéis semeados, e o badge não depende de quem está logado — depende da organização
  corrente. Os cenários usam `tenant()` direto.
- **CT-04 virou dataset de duas linhas**, em vez de um cenário com dois momentos. Motivo técnico,
  e ele é consequência de ADR-03: `contagemDoBadge()` memoiza por request com `once()`, então
  criar um registro no MEIO de um teste não muda o badge. Cada linha do dataset é um teste com
  container próprio. O poder de falsificação é o mesmo — as duas metades (`gray` no zero, não
  `gray` acima) continuam num par obrigatório.
- **O Pint reordenou o bloco de traits** de `RoleResource` (`ordered_traits`), levando o bloco de
  resolução para o topo. O comentário foi ajustado de "os outros quatro `use` **acima**" para
  "**logo abaixo**".

## Notas de Implementação

- **O odômetro embrulha o número em word joiners (U+2060) e markup.** Foi o que confirmou a
  escolha de oráculo do `04`: comparar contra `OdometerNavigationBadge::make($esperado)` em vez de
  `toContain('3')`. Um oráculo textual teria sido frágil e fraco ao mesmo tempo.
- **Mutation score do trait: 87,5%** (7 mortos, 1 não coberto), com
  `pest --mutate --path=app/Filament/Concerns/BadgeContagemNavegacao.php`.
  O único mutante não coberto é `AlwaysReturnNull` na **linha 62** — o corpo de
  `getNavigationBadgeTooltip()`. Isso **não é lacuna nova**: o `04` já registrou o tooltip em
  `## Fronteira com o Plano` como texto que só o PRD determina, portanto sem cenário. Fechá-lo
  significaria afirmar uma string que o requisito não determina, que é exatamente o que a fronteira
  proíbe. A medição confirmou que a declaração era honesta.
- **`once()` memoiza dentro do teste, não só entre requests.** Consequência prática para quem
  escrever CT novo aqui: mudar o número de registros no meio de um caso não muda o badge. Use
  dataset.
- **`toContain()` do Pest recebe VALORES, não mensagem.** Um segundo argumento string vira outro
  valor esperado, e a asserção falha com *"Failed asserting that an array contains 'O kit deveria
  registrar…'"*. Custou um ciclo vermelho em CT-07; o comentário ficou no arquivo.
- **A verificação `php artisan about` não é cerimônia.** Ela foi executada antes e depois do passo
  2, e é a única forma de provar que a resolução de colisão compila — nenhum teste alcança um erro
  fatal de compilação, porque o processo morre antes de o Pest carregar.

## Retrospectiva

- **Funcionou bem**: perguntar antes de planejar. A premissa do requisito ("Convites não tem
  badge") era falsa, e descobrir isso na pesquisa — em vez de na implementação — mudou a entrega
  inteira de "acrescentar dois badges" para "reverter uma regra e enforçar o padrão".
- **Funcionou bem**: executar as premissas do step 5 em vez de raciocinar sobre elas. Três das
  cinco correções vieram de rodar código descartável: a colisão fatal, os três `insteadof`
  necessários (eu tinha assumido um) e o `Projeto` com soft delete que invalidou uma lacuna
  declarada no `04`.
- **Faltou no plano**: o PRD não previu que `ofertaPara()` e `duasOrganizacoes()` arrastam o
  `PapeisSeeder`. Custou dois ciclos de teste vermelho. Numa próxima wiki, a seção `## Fixtures`
  do `04` deveria declarar **o que cada helper global exige**, não só o que ele devolve.
