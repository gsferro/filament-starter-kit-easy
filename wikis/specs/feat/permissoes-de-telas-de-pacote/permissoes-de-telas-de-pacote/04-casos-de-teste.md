# Casos de Teste — W6: permissões das telas de pacote

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação —
> ela não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| autorização das telas de pacote | 3 | 3 | **9** | **completo** |
| invariante da matriz de permissões | 2 | 3 | **6** | padrão |
| documentação (README, CHANGELOG) | 1 | 1 | 1 | mínimo |

- **P=3** na primeira área: a decisão passa por sete pacotes de terceiros, cada um com um
  mecanismo próprio, e um deles exige remover um plugin do painel.
- **I=3**: é autorização, e o erro espelhado (trancar o papel `infra` fora da observabilidade) só
  aparece durante um incidente, que é o pior momento possível.
- Técnicas aplicadas: **matriz papel × tela** (partição exaustiva), **EP** (permissão × fonte de
  dados), **partição de persona**, **partição de estado do dado** (permissão ausente da tabela),
  **invariante numérico**, **rastreio de efeito** (item de navegação).
- Cenários: **9** (CT-24 reescrito + 8 novos) · Regras: **7** · Mutantes previstos: **17** ·
  Sem matador: **1** (declarado em M13).

## Divergência declarada entre skill e Project Rule

A skill sugere `pest --parallel --tia` como padrão. Este projeto tem
`.ai/rules/testes-browser.md` e o `composer.json` com scripts próprios; o comando que a wiki
manda rodar é `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` e `composer test:browser`.
**A rule vence** — e o motivo, para os CT-B, está escrito na própria skill (item 6 dos fatos do
`pest-plugin-browser`: nunca `--parallel` com navegador).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | 2 Pages novas do kit (subclasses), 1 Widget novo, 1 classe de `Support`, 3 PanelProviders alterados, nenhuma migration | CT-06 |
| **F** | decidir acesso a tela; esconder item de navegação; esconder widget; declarar a lacuna que sobra | CT-01, CT-02, CT-03, CT-05, CT-07 |
| **D** | a linha em `permissions`; a relação papel × permissão; a permissão **ausente** do banco; a chave que **não resolve** (painel corrente errado) | CT-06, CT-08 |
| **I** | `GET` na rota do painel; request Livewire de hidratação; montagem da navegação; grade de widgets do dashboard; categoria do Spotlight; cartão de hub | CT-02, CT-03, CT-05 |
| **P** | Filament 5, `filament-shield`, sete pacotes de tela; banco SQLite; o commit por nome do Livewire para o header widget do backup monitor | CT-B01 |
| **O** | papéis `master_global`, `infra`, `admin`, `panel_user`; uso indevido = revogar a permissão e digitar a URL | CT-01, CT-02, CT-04 |
| **T** | **não se aplica**: nenhuma regra temporal. O único efeito de ordem é a memoização `once()` de `FilamentShield::getPages()`, que é por request e por instância — e é o que `noPainelDoShield()` existe para tratar em teste de componente | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a permissão da tela de pacote **decide** o acesso: quem tem entra, quem não tem toma 403 | autorização (completo) | RQ-01, RQ-02, RQ-03 | matriz papel × tela, partição **exaustiva** das telas fechadas | CT-01, CT-02 |
| **R2** — o item de navegação da tela some junto com o acesso, em vez de aparecer e dar 403 no clique | autorização (completo) | RQ-01 | rastreio de efeito (presença/ausência) | CT-03 |
| **R3** — o Widget de pacote só aparece para quem tem a permissão dele, **e** só quando a fonte de dados existe | autorização (completo) | RQ-01 | EP: permissão × fonte, 2×2 | CT-05 |
| **R4** — a permissão continua existindo no banco e chegando aos papéis: nenhuma chave nova, nenhuma órfã | matriz (padrão) | RQ-04, RQ-06 | invariante numérico | CT-06 |
| **R5** — as três telas da Central de comandos seguem **sem** permissão por tela, e a lacuna é observável | autorização (completo) | RQ-05 | caso negativo declarado | CT-07 |
| **R6** — `master_global` atravessa pelo `Gate::before`; o papel real, não | autorização (completo) | RQ-02 | partição de persona | CT-04 |
| **R7** — permissão **ausente** da tabela fecha a tela, em vez de abri-la | autorização (completo) | RQ-01 | partição de estado do dado | CT-08 |

RQ-07 (README) e RQ-08 (CT-24 atualizado) não geram regra de comportamento: RQ-07 é documentação
e RQ-08 é uma instrução sobre **este** arquivo, cumprida pela reescrita de CT-24 em CT-02.

### Técnica escalada acima do perfil

Nenhuma. R4 está em área `padrão` e recebeu invariante numérico, que é a técnica mínima que
falsifica "a chave da permissão mudou" — não há BVA a fazer sobre contagem exata.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\PermissaoDaTela::permite()` | nome de classe e de método é escolha de implementação — ADR-02 poderia ter escolhido outra | detalhe; **nenhum** `Então` menciona o nome |
| `use ExigePermissaoDaTela;` nas subclasses | mecanismo, não comportamento. O `Então` de CT-02 e CT-05 é sobre 403 e sobre o cartão do widget, não sobre o nome do trait | detalhe. **É deliberado**: o oráculo comportamental sobrevive a renomeação e mata o `use` inerte — a mesma razão pela qual CT-23 da wiki ancestral não menciona trait nenhum |
| remover `FilamentBackupMonitorPlugin` do painel | escolha de implementação (ADR-04) | detalhe — mas o **efeito colateral** dela (o commit por nome do header widget) é comportamento visível e virou CT-B01 |
| os números 126 / 140 / 47 / 17 / 269 | vêm de medição do banco, não do requisito | **usados como oráculo**, e é legítimo: RQ-06 diz "nenhuma permissão órfã", e o número é a forma de falsificar isso. O cenário afirma o número **medido antes**, não um número escolhido |

**Perguntas em aberto** (já replicadas em `00-requisito.md` → `## Ambiguidades`):

- a contagem "10 Pages e 1 Widget" não fecha com a lista (10 classes, não 11) — **não bloqueia**
  nenhuma regra; a lista é normativa e é ela que alimenta o `Esquema do Cenário` de CT-02.
- `MyProfilePage` entra no escopo? — **premissa adotada: sim**. A linha de `MyProfilePage` em
  CT-01 e CT-02 está marcada `@premissa`. Se a resposta for "não", as duas linhas saem e a classe
  vira a quarta de CT-07.

## Setup Global

### Personas

- `usuarioDoKit('infra', 'infra@example.com')` — papel `infra`, que tem as 10 permissões
  (`tests/Pest.php:449`)
- `usuarioDoKit('master_global', …)` — o coringa do `Gate::before`
- `usuarioDoKit('admin', …)` — para a linha de `MyProfilePage` no painel `/admin`
- `usuarioCom(null)` — usuário sem papel nenhum, para o percurso estrutural

> **Persona discriminante**: o lado "não tem" é sempre arranjado revogando a permissão do
> **papel real** com `semAPermissao()` (`tests/Pest.php:618`), nunca criando papel vazio. Papel
> vazio perde `canAccessPanel()` e o 403 viria da porta do painel — o cenário passaria com a
> feature inteira ausente. É a mesma escolha, e pelo mesmo motivo, do docblock de
> `PermissoesDeTelasTest.php:20-23`.

### Fixtures

- os dois seeders no `beforeEach`: `ShieldPermissionsSeeder` e `PapeisSeeder` — é o que
  `tests/Kit/PermissoesDeTelasTest.php:28-30` já faz
- **nenhuma factory nova**

### Fakes

Nenhum. A feature não emite e-mail, job, evento nem HTTP.

### Estratégia de DB

`RefreshDatabase` global (`tests/Pest.php`). Suíte **`tests/Kit`** — single-tenant. O papel
`admin_app` **não existe** nesta suíte (`.ai/rules/testes.md`), então nenhum cenário o usa.

---

## Regra R1 — a permissão da tela de pacote decide o acesso

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **completo** · técnica: **matriz papel × tela**, com
> partição **exaustiva** das telas fechadas

Exaustiva e não amostrada, e o motivo é a forma do defeito: cada tela é fechada por um mecanismo
**diferente** (quatro callbacks distintos de quatro pacotes + duas subclasses). Amostrar três das
sete deixaria quatro mecanismos sem nenhum cenário, e o erro mais plausível de todos é
**esquecer uma linha ao copiar as outras**.

```gherkin
# language: pt

Funcionalidade: permissão das telas que o painel /infra recebe de pacotes

  Regra: a permissão View:{Tela} decide o acesso à tela de pacote

    Esquema do Cenário: [CT-01] quem tem a permissão da tela abre a tela
      Dado um usuário com o papel "<papel>", que carrega a permissão "<permissao>"
      Quando ele acessa "<rota>"
      Então a resposta é bem-sucedida
      E a página mostra "<marca>"

      Exemplos:
        | papel | rota                          | permissao                    | marca                | # mecanismo                  |
        | infra | /infra/health-check-results   | View:HealthCheckResults      | Health               | plugin ->authorize()         |
        | infra | /infra/backup-runs            | View:BackupRunsPage          | Backup               | subclasse do kit             |
        | infra | /infra/logs                   | View:LogsExplorer            | Logs                 | plugin ->canAccessUsing()    |
        | infra | /infra/dependency-graph       | View:DependencyGraphPage     | Depend               | plugin ->canAccessUsing()    |
        | infra | /infra/recycle-bin            | View:RecycleBin              | Lixeira              | plugin ->authorize()         |
        | infra | /infra/meu-perfil             | View:MyProfilePage           | perfil               | subclasse do kit (@premissa) |
        | admin | /admin/meu-perfil             | View:MyProfilePage           | perfil               | mesma classe, outro painel   |

    Esquema do Cenário: [CT-02] revogar a permissão do papel fecha a tela
      Dado um usuário com o papel "<papel>", de quem a permissão "<permissao>" foi revogada
      Quando ele acessa "<rota>"
      Então a resposta é 403
      E a permissão "<permissao>" continua existindo na tabela de permissões

      Exemplos:
        | papel | rota                          | permissao                    | # mecanismo                  |
        | infra | /infra/health-check-results   | View:HealthCheckResults      | plugin ->authorize()         |
        | infra | /infra/backup-runs            | View:BackupRunsPage          | subclasse do kit             |
        | infra | /infra/logs                   | View:LogsExplorer            | plugin ->canAccessUsing()    |
        | infra | /infra/dependency-graph       | View:DependencyGraphPage     | plugin ->canAccessUsing()    |
        | infra | /infra/recycle-bin            | View:RecycleBin              | plugin ->authorize()         |
        | infra | /infra/meu-perfil             | View:MyProfilePage           | subclasse do kit (@premissa) |
        | admin | /admin/meu-perfil             | View:MyProfilePage           | mesma classe, outro painel   |
```

**CT-02 é o CT-24 reescrito** (RQ-08). O antigo afirmava `assertSuccessful()` na linha do
`LogsExplorer` com a permissão revogada — a lacuna. A segunda asserção (`a permissão continua
existindo`) é a **mesma** do original e está aqui por RQ-04: ela é o que mantém vermelha a
"correção" errada de pôr a classe em `pages.exclude`.

A linha de `/admin/meu-perfil` não é redundante com a de `/infra/meu-perfil`: é **uma classe só
em dois painéis**, e é a única forma de falsificar um helper que resolvesse a chave pelo painel
errado — o `once()` de `FilamentShield::getPages()` é por instância, e um request é um painel só.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a linha de autorização é copiada para três dos quatro plugins e uma fica de fora | CT-02 (a linha da tela esquecida) |
| M2 | a subclasse escreve `canAccess()` própria em vez de `regraLocalDeAcesso()`, e o método da classe vence o do trait em silêncio | CT-02 (linhas `backup-runs` e `meu-perfil`) |
| M3 | o helper monta a chave à mão (`'View:'.class_basename($pagina)`) e a string não casa com a permission — o fail-open abre a tela para todos | CT-02 (todas as linhas) |
| M4 | `\|\|` no lugar de `&&` no `LogsExplorer` (gate **ou** permissão) | CT-02 (linha `/infra/logs`) |
| M5 | o helper devolve `true` incondicionalmente | CT-02 (todas as linhas) |
| M6 | o helper devolve `false` quando a chave não resolve (fail-closed), trancando tudo | CT-01 (todas as linhas) |

---

## Regra R2 — o item de navegação some junto com o acesso

> `RQ-01` · perfil **completo** · técnica: **rastreio de efeito** (presença/ausência do rótulo)

```gherkin
    Cenário: [CT-03] o item de menu da tela desaparece quando a permissão é revogada
      Dado um usuário com o papel "infra", de quem a permissão "View:LogsExplorer" foi revogada
      Quando ele acessa "/infra"
      Então a resposta é bem-sucedida
      E a barra lateral não mostra o rótulo da tela de logs
      E a barra lateral ainda mostra o rótulo da Lixeira
```

A segunda asserção é o que mata "a navegação inteira ficou vazia" — sem ela, um `canAccess()` que
negasse tudo passaria. É a mesma construção de CT-03 da wiki ancestral
(`PermissoesDeTelasTest.php:~100`).

O painel é o **do próprio papel**. Dizer que o papel `infra` não vê um item do `/admin` é
trivialmente verdadeiro e ficaria verde com o item escondido por um `hasRole()` paralelo à
permissão — que é o caso em que o item aparece para quem tem o papel, não tem a permissão, e leva
a um 403 no clique.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | a barreira é um middleware de rota: a rota devolve 403 e o item de menu continua na barra lateral | CT-03 (primeira asserção) |
| M8 | o item é escondido e a rota continua respondendo 200 (`registerNavigation(false)` em vez de autorização) | CT-02 |
| M9 | a autorização nega tudo e a navegação fica vazia | CT-03 (segunda asserção) |

---

## Regra R3 — o Widget de pacote depende da permissão E da fonte de dados

> `RQ-01` · perfil **completo** · técnica: **EP 2×2** (permissão sim/não × fonte presente/ausente)

```gherkin
  Regra: o Widget de pacote só aparece com a permissão dele e com a fonte de dados presente

    Esquema do Cenário: [CT-05] o cartão de releases obedece às duas condições
      Dado um usuário com o papel "infra"
      E a permissão "View:ComposerReleaseOverviewWidget" <estado_da_permissao> para esse papel
      E a tabela de snapshots de release <estado_da_fonte>
      Quando o dashboard do painel /infra é montado
      Então o cartão de releases <resultado>

      Exemplos:
        | estado_da_permissao | estado_da_fonte | resultado    | # partição            |
        | presente            | presente        | aparece      | as duas satisfeitas   |
        | revogada            | presente        | não aparece  | só a permissão falha  |
        | presente            | ausente         | não aparece  | só a fonte falha      |
```

A quarta célula (as duas ausentes) foi **cortada**: ela não distingue nenhum dos mutantes de R3 —
qualquer implementação que respeite uma das duas condições já a recusa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | o `canView()` da subclasse não sobrescreve o do pacote (`auth()->check()`) e o cartão aparece para quem não tem a permissão | CT-05 (linha "revogada") |
| M11 | o guarda de tabela é escrito em `canView()` e não em `fonteDeDadosDisponivel()`, apagando a checagem de permissão | CT-05 (linha "revogada") |
| M12 | o widget do pacote continua registrado (`->widget(enabled: true)` mantido) além do do kit | CT-05 (linha "revogada") + CT-06 |
| M13 | a permissão é respeitada e o guarda de tabela é esquecido: o dashboard morre inteiro numa instalação sem as migrations do pacote | CT-05 (linha "fonte ausente") |

---

## Regra R4 — nenhuma chave de permissão nasce nem morre

> `RQ-04`, `RQ-06` · perfil **padrão** · técnica: **invariante numérico**

```gherkin
  Regra: substituir a classe registrada não muda a matriz de permissões

    Cenário: [CT-06] a matriz de permissões continua com os mesmos totais
      Dado o banco semeado com ShieldPermissionsSeeder e PapeisSeeder
      Quando as permissões de cada papel são contadas
      Então o papel "admin" tem 126 permissões
      E o papel "infra" tem 140 permissões
      E o papel "panel_user" tem 17 permissões
      E a tabela de permissões tem 269 linhas
      E as permissões "View:BackupRunsPage", "View:MyProfilePage" e "View:ComposerReleaseOverviewWidget" existem
```

Os números são a medição feita **antes** da implementação (registrada em `01-plano-acao.md` →
"Invariante da matriz"), não valores escolhidos. Um número que mude é o sintoma exato de RQ-06:
chave renomeada, permissão órfã, e alguém trancado fora de uma tela.

A última asserção é o par nominal do invariante: as três contagens poderiam se compensar (uma
chave nova entrando e outra saindo do mesmo painel), e é o nome que impede isso.

> **Fragilidade aceita, com o custo escrito**: os quatro números envelhecem a cada tela nova do
> kit, e este caso vai ficar vermelho num branch que acrescente um Resource. Isso é o
> comportamento pedido — `.ai/rules/filament.md` registra que o número **38** dessa mesma família
> ficou parado por sete versões e "número de rule parado é o que faz o próximo agente concluir
> que a subtração está completa quando não está". Um caso vermelho é mais barato que uma rule
> velha. A mensagem de falha diz o número medido e o esperado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | a subclasse recebe outro nome (`BackupRunsPageDoKit`) e a chave muda: nasce `View:BackupRunsPageDoKit` e `View:BackupRunsPage` fica sem ninguém | CT-06 (o total 269 e o nome) |
| M15 | alguém acrescenta as permissões "na mão" ao `PapeisSeeder` "para garantir", duplicando o que a matriz do painel já entrega | CT-06 (contagem por papel) |
| M16 | a classe do pacote é excluída via `config('filament-shield.pages.exclude')` para o checkbox parar de mentir | CT-06 (o total 269) |

---

## Regra R5 — a lacuna da Central de comandos é declarada e observável

> `RQ-05` · perfil **completo** · técnica: **caso negativo declarado**

```gherkin
  Regra: as três telas da Central de comandos seguem sem permissão por tela

    Esquema do Cenário: [CT-07] revogar a permissão da tela não fecha a tela da Central de comandos
      Dado um usuário com o papel "infra", de quem a permissão "<permissao>" foi revogada
      Quando ele acessa "<rota>"
      Então a resposta é bem-sucedida
      E a permissão "<permissao>" continua existindo na tabela de permissões

      Exemplos:
        | rota                            | permissao      |
        | /infra/command-center/commands  | View:Commands  |
        | /infra/command-center/history   | View:History   |
```

**Este caso fica vermelho no dia em que alguém fechar as três** — é o mesmo papel que o CT-24
original tinha, transferido para o escopo que **de fato** sobra, e é deliberado. Quando ficar
vermelho, o sinal é que ADR-05 desta wiki precisa ser revisada, não que o caso está errado.

`RunView` fica **fora do `Esquema do Cenário`**: a rota dela exige um registro de execução
(`{record}`), e ela não está no inventário de `telasDoKit()` por isso. A declaração dela vive no
comentário do provider e neste ADR; forjar uma execução no banco só para provar que uma tela
**não** está protegida seria arranjo caro para um oráculo que as outras duas já sustentam.
Registrado como **lacuna declarada**, não como cobertura.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | `View:Commands` é usada no callback do plugin e passa a valer pelas três telas — revogá-la fecha `History` e `RunView` também | CT-07 (linha `View:Commands` fica vermelha, apontando que a decisão mudou sem ADR) |
| M18 | a união (`can('View:Commands') \|\| can('View:History')`) é usada: quem tem uma entra nas três | ⚠️ **sem matador** — tentado: o cenário precisaria de um papel com **uma** das três e sem as outras, e `PapeisSeeder` entrega as três juntas ao papel `infra`; arranjar isso exige revogar duas e manter uma, o que é exatamente o que CT-07 faz linha por linha e produz `assertSuccessful()` nas duas implementações. **Lacuna declarada**: enquanto as três estão inertes, união e ausência de checagem são indistinguíveis por comportamento |

---

## Regra R6 — `master_global` atravessa; o papel real, não

> `RQ-02` · perfil **completo** · técnica: **partição de persona**

```gherkin
  Regra: o coringa do Gate::before vence a permissão revogada, e só ele

    Esquema do Cenário: [CT-04] só o master_global entra com a permissão fora de todo papel
      Dado que a permissão "View:LogsExplorer" foi revogada do papel "infra"
      E um usuário com o papel "<papel>"
      Quando ele acessa "/infra/logs"
      Então a resposta tem status <status>

      Exemplos:
        | papel         | status | # partição              |
        | master_global | 200    | coringa do Gate::before |
        | infra         | 403    | papel de painel         |
```

**As duas linhas são o caso**, não duas amostras. A de `master_global` sozinha ficaria verde com
`canAccess()` devolvendo `true` incondicional; é a de `infra` que distingue "o `Gate::before`
venceu" de "a tela não checa nada". E a de `master_global` é a única que falsifica uma checagem
por `hasPermissionTo()` direto — aquela ignora o `Gate::before` e trancaria o coringa fora do
painel dele.

Duas linhas em `Exemplos:` e não dois `actingAs()` no mesmo cenário: o segundo request herdaria a
sessão do primeiro e responderia 302 para o login, medindo a sessão em vez da permissão
(observado e documentado em `PermissoesDeTelasTest.php:120-123`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | a checagem usa `hasPermissionTo()` em vez de `can()`, ignorando o `Gate::before` | CT-04 (linha `master_global`) |
| M20 | a checagem é `hasRole('infra')` em vez da permissão | CT-04 (linha `infra`) |

---

## Regra R7 — permissão ausente da tabela fecha a tela

> `RQ-01` · perfil **completo** · técnica: **partição de estado do dado**

```gherkin
  Regra: sem a linha na tabela de permissões, a tela fecha em vez de abrir

    Esquema do Cenário: [CT-08] a tela de pacote nega acesso quando a permissão não existe no banco
      Dado que a linha "View:RecycleBin" foi apagada da tabela de permissões
      E um usuário com o papel "<papel>"
      Quando ele acessa "/infra/recycle-bin"
      Então a resposta tem status <status>

      Exemplos:
        | papel         | status | # partição              |
        | infra         | 403    | papel de painel         |
        | master_global | 200    | coringa do Gate::before |
```

A partição que nenhum outro cenário exercita: as suítes semeiam as permissões, então todos os
demais rodam com a tabela populada. Aqui a linha **não existe** — instalação sem seeder,
permissão apagada, `kit:install --custom`.

O caminho errado plausível é uma guarda `if (! Permission::where('name', $chave)->exists())
return true;`, escrita para "não travar instalação nova", e ela passa em CT-01..CT-04 inteiros.
A linha do `master_global` é o controle: sem ela, uma implementação que negasse a **todos** por
erro de resolução de chave também ficaria verde.

`RecycleBin` e não `LogsExplorer` de propósito: é uma tela fechada por `->authorize()` do plugin,
partição de mecanismo diferente da de CT-08 na wiki ancestral (que usava `Pulse`, uma Page do
kit).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | guarda "se a permissão não existe, libera" para não travar instalação nova | CT-08 (linha `infra`) |
| M22 | erro de resolução de chave que nega a todos, inclusive ao coringa | CT-08 (linha `master_global`) |

---

## Enforço estrutural — o que sobra depois dos cenários

Dois casos que não são cenário de comportamento, e sim inventário. Existem porque
`.ai/rules/specs.md` manda preferir enforço automático a prosa, e porque o custo de escrever o
par tem/não-tem para toda tela futura de pacote é burocracia que ninguém mantém.

### CT-30 — nenhuma tela de pacote registrada no /infra fica fora das duas listas

Percorrer `Filament::getPanel('infra')->getPages()`, separar o que **não** está no namespace
`App\Filament\`, e exigir que cada classe esteja **ou** na lista de "fechada por callback do
plugin" **ou** na lista de "declarada". Fica vermelho quando um upgrade de plugin trouxer uma
tela nova — que é exatamente o momento em que o kit voltaria a ter checkbox inerte sem ninguém
saber.

Mesmo enforço para `getWidgets()`.

> Mata M23: *um upgrade de pacote acrescenta uma Page nova ao painel e ninguém repara*.

### CT-31 — o percurso comportamental já existente adota as classes novas

`CT-21` e `CT-23` de `tests/Kit/PermissoesDeTelasTest.php` (e os equivalentes de Widget em
`PermissoesDeWidgetsTest.php`) percorrem `App\Filament\**` e exigem, respectivamente, o concern e
a negação de acesso a quem não tem permissão nenhuma. As duas Pages novas e o Widget novo caem
nesse filtro **de graça** — nenhuma linha nova de teste, e a cobertura sobe.

**Não é cenário novo**: é a verificação de que os casos existentes ficam verdes com escopo maior.
Se ficarem vermelhos, a implementação está errada.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma tela desta entrega é por registro. `RunView` é (`{record}`), e é uma das declaradas — a autorização horizontal dela é do pacote |
| Autorização exercida na ação, não só `can()` | CT-01, CT-02 — os dois fazem `GET` na rota real; nenhum afirma sobre `$usuario->can()` como oráculo |
| Autorização exercida em **cada verbo irmão** | CT-02 + CT-03 — a rota **e** a navegação; são os dois efeitos de `canAccess()` que o Filament produz (`Page.php:133-135` e `CanAuthorizeAccess.php:9`) |
| Idempotência (ancorada no agregado) | **não se aplica**: nenhuma operação de escrita |
| Concorrência | **não se aplica**: nenhum contador, saldo ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nenhum campo numérico, nenhuma tela de escrita nova |
| Domínio condicionado (um campo depende do outro) | CT-05 — permissão × fonte de dados é exatamente isso |
| Estado × operação de escrita | **não se aplica**: sem entidade com ciclo de vida |
| Ausente ≠ `null` ≠ vazio | CT-08 — a permissão **ausente** da tabela é a partição, e é a que ninguém exercita |
| Paginação / ordenação | **não se aplica** |
| Timezone / DST | **não se aplica**: nenhuma regra temporal (ver SFDIPOT → T) |
| Unicode / limite de `varchar` | **não se aplica** |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum payload novo |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Método de classe vence método de trait, em silêncio** (linha própria deste projeto, de `.ai/rules/filament.md`) | CT-02 (linhas de subclasse) + CT-31 (o percurso comportamental de CT-23, cujo oráculo não menciona trait nenhum) |
| **Chave de permissão renomeada por refatoração** (linha própria, nova) | CT-06 |
| **Painel corrente errado na resolução da chave** (linha própria, de `tests/Pest.php:640-656`) | CT-01/CT-02, linha `/admin/meu-perfil` |

## Índice de Cenários

Preenchido DEPOIS da auditoria Ponytail do step 6, que trocou o arquivo novo por linhas nos casos
que já existiam — ver `03-progresso.md` → "Auditoria Ponytail", cortes 1 a 6. O ID de CT que não tem
arquivo é o que **não foi escrito**, com o motivo na mesma linha.

| ID | Cenário | Regra | Camada | Onde ficou |
|----|---------|-------|--------|-----------|
| CT-01 | quem tem a permissão abre a tela (8 linhas de pacote) | R1 | Kit (HTTP) | `tests/Kit/PaginasInfraTest.php` → `it('abre as telas do painel infra com o papel infra')` |
| CT-02 + CT-03 | revogar fecha a tela **e** tira o item do menu (4 linhas de pacote, uma por mecanismo) | R1, R2 | Kit (HTTP) | `tests/Kit/PermissoesDeTelasTest.php` → `it('fecha a tela e esconde o item de menu…')` |
| CT-24 | a repro do requisito, invertida (3 linhas) | R1 | Kit (HTTP) | `tests/Kit/PermissoesDeTelasTest.php` → `it('faz a Page de pacote negar acesso sem a permissão…')` |
| CT-04 + CT-08 | persona × arranjo, no caminho do pacote (4 linhas) | R6, R7 | Kit (HTTP) | `tests/Kit/PermissoesDeTelasTest.php` → `it('nega a tela de pacote a quem não tem a permissão…')` |
| CT-05 | o widget obedece às duas condições (3 linhas novas) | R3 | Livewire | `tests/Kit/PermissoesDeWidgetsTest.php`, nos dois casos existentes |
| CT-06 | invariante numérico da matriz | R4 | — | **não escrito** (corte 4). Número congelado em `expect()` envelhece; a asserção nominal de CT-24 + a medição registrada no `01` e no CHANGELOG cobrem M14/M16 sem a fragilidade |
| CT-07 → CT-27 | a lacuna que sobra, e o inventário do que a produz | R5 | Kit | `tests/Kit/PermissoesDeTelasTest.php` → `it('deixa só as três telas da Central de comandos…')` |
| CT-30 | inventário por duas listas escritas à mão | — | — | **não escrito** (corte 3): virou o CT-27 acima, que pergunta ao painel e compara com UMA lista |
| CT-31 | CT-21/CT-23 adotam as classes novas | — | — | **não é caso de teste** (corte 5): é a verificação de que os casos existentes seguem verdes com escopo maior |
| CT-B01 | o header widget isolado carrega | — | Browser | `tests/Browser/TelasDoKitTest.php` → `it('carrega o header widget da tela de backups…')` |

**Onde CT-24 foi**: o mesmo `it()` de `tests/Kit/PermissoesDeTelasTest.php`, com `assertSuccessful()`
trocado por `assertForbidden()`, dataset de 3 linhas e o docblock reescrito para explicar a inversão.
A segunda asserção — a permissão continua na tabela — não mudou. **Não é deleção**: é o mesmo oráculo
com o sinal trocado, e o `03-progresso.md` registra a troca (RQ-08).

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| a quarta célula de CT-05 (sem permissão e sem tabela) | não distingue nenhum mutante de R3 |
| `RunView` em CT-07 | exige forjar um registro de execução; o oráculo já é sustentado pelas outras duas linhas. **Lacuna declarada**, não cobertura |
| o cartão do hub `/infra` desaparecendo junto com a tela de pacote | `HubDeInfraestrutura` descobre cartões por `DescobreCardsDoPainel`, que já é coberto por CT-03 da wiki ancestral. Mesmo mutante (M7), cenário mais caro |
| a categoria do Spotlight escondendo a tela | `PagesAutorizadasCategory` consulta o mesmo `canAccess()`; mata M7, que CT-03 já mata mais barato |
| um cenário por painel para `MyProfilePage` no `/app` | a suíte `tests/Kit` é single-tenant e o `/app` responde diferente sem organização (`.ai/rules/testes.md`). As duas linhas de `/infra` e `/admin` já falsificam a resolução por painel |
| `expect($usuario->can('View:LogsExplorer'))->toBeFalse()` como cenário próprio | tautologia: mede o arranjo de `semAPermissao()`, não a tela. Fica como asserção **de apoio** dentro de CT-01, nunca como oráculo |
