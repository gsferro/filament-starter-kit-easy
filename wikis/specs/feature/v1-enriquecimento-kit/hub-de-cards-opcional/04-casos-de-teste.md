# Casos de Teste — Hub de cards fora do padrão da instalação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — no
> momento da derivação ela não existia. Os ajustes pós-implementação estão marcados como tal e
> têm a razão em "Desvios do Plano" do `03-progresso.md`.

## Divergência declarada: rule do projeto vence a skill

A skill sugere `vendor/bin/pest --parallel --tia` como comando padrão de verificação.
`.ai/rules/testes-browser.md` mede o contrário nesta base: `--parallel` derruba 4 dos 11 cenários
de browser, e `--tia` sem **PCOV** não termina (abortado após 35 min com Xdebug). **A rule vence.**
Os comandos deste arquivo são dois: `--group=kit` para o backend e `--testsuite=Browser` em série.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — a flag governa os hubs de `/admin` e `/app` | 2 (integra com navegação, autorização de Page, Spotlight e matriz do Shield) | 2 (retrabalho: hub aparece onde não devia, ou desaparece de onde devia ficar) | 4 | **padrão** |
| A2 — o `/infra` fora da flag | 1 (ausência de código) | 2 (o painel mais denso perde a porta de entrada) | 2 | mínimo |
| A3 — descrição nos cartões do `/infra` | 2 (mexe na assinatura de um trait usado por três Pages, e depende da blade e do CSS do pacote) | 2 (descrição errada desorienta; trait quebrado derruba dois outros hubs) | 4 | **padrão** |
| A4 — o pacote permanece instalado | 1 | 2 (`composer remove` derruba o `/infra` com classe inexistente) | 2 | mínimo |
| A5 — documentação e imagem | 1 | 1 | 1 | mínimo |

- **Técnicas aplicadas**: EP (partição da flag; partição dos destinos), tabela de decisão
  (flag × superfície de acesso), matriz papel × acesso, rastreio de efeito (o não-efeito na matriz
  de permissões), rastreio de efeito estrutural (dependência declarada).
- **BVA não se aplica**: não há faixa ordenável nesta feature. A única variável de entrada é um
  booleano, e o mapa de descrições é um dicionário de strings. Declarado, não omitido.
- **Criação ≠ edição ≠ uso não se aplica**: a feature não grava nada. Não há ponto de criação nem
  de edição — só o de uso (leitura da flag e leitura do mapa a cada render).
- Cenários: **8** (sendo 5 `Esquema do Cenário`) · Regras: **7** · Mutantes previstos: **21** ·
  Sem matador: **2** (ambos declarados).

> **Quatro cenários foram cortados pela auditoria Ponytail** (step 6 da `feature-wiki`), e os
> motivos estão em `## Cogitado e cortado`. Um deles, o antigo CT-04, era **tautológico** — ficava
> verde com a feature inteira removida. Os IDs não foram renumerados: buraco na numeração é mais
> honesto que rastreabilidade reescrita.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | 1 chave de config nova; 1 env no `phpunit.xml`; 1 linha no `.env.example`; 2 Pages ganham `canAccess()`; 1 Page ganha um mapa; 1 trait ganha parâmetro; 2 imagens em `art/`. **Nenhuma migration, nenhum model, nenhuma permission nova** | CT-01, CT-06, CT-11, CT-12 |
| **F** | três funções: **decidir** se a Page é alcançável a partir de uma flag; **anexar** descrição a cada cartão; e **não decidir nada** no `/infra` | CT-01, CT-02, CT-05, CT-07, CT-08 |
| **D** | o dado é (a) um booleano de config e (b) um dicionário estático `FQCN => string`. Cardinalidades relevantes: flag `true`/`false`; chave do mapa **presente**, **ausente** e **órfã**; hub que **não declara mapa nenhum**. Nada vem do banco | CT-01, CT-02, CT-05, CT-07 |
| **I** | três superfícies de acesso à mesma Page: **URL**, **barra lateral** e **busca ⌘K** — as três decididas pelo mesmo `canAccess()`. Mais duas interfaces de leitura: `php artisan config:show kit.hub` e a matriz do Shield | CT-01, CT-02, CT-06 |
| **P** | autorização de Page do Filament 5.6; a blade do `harvirsidhu/filament-cards` e o **subconjunto escrito à mão** de utilitárias em `resources/css/filament/cards.css` — é aqui que mora o risco de "HTML certo, tela sem estilo"; precedência `phpunit.xml` > `.env` | CT-B02, CT-01 |
| **O** | quatro perfis: `master_global` (vence pelo `Gate::before`), `admin`, `infra`, `panel_user`. Dois ambientes: kit recém-instalado (flag `false`) e projeto que ligou. **Uso indevido previsto**: um agente futuro "corrigindo" a assimetria do `/infra` | CT-01, CT-05 |
| **T** | **não se aplica**: nenhuma dimensão temporal. Não há expiração, agendamento, concorrência nem ordenação por tempo. A flag é lida a cada request e não guarda estado | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a flag `kit.hub` governa a alcançabilidade dos hubs de `/admin` e `/app`: desligada (o default), eles não são alcançáveis nem por URL nem pelo menu; ligada, voltam | A1 (padrão) | RQ-03, RQ-04 | tabela de decisão (flag × superfície) + matriz papel × acesso | CT-01, CT-02 |
| **R2** — o hub de `/infra` é alcançável com a flag em qualquer valor | A2 (mínimo) | RQ-04 (exceção declarada pelo usuário) | EP no valor da flag, com o **mesmo** resultado esperado nas duas partições | CT-05 |
| **R3** — desligar o hub não altera a matriz de permissões | A1 (padrão) | RQ-03 | rastreio de efeito — o **não-efeito** | CT-06 |
| **R4** — cada destino do hub de `/infra` exibe uma descrição do que ele serve | A3 (padrão) | RQ-07 | EP sobre os destinos | CT-07, CT-08 |
| **R5** — a descrição é opcional: hub que não declara mapa nenhum continua renderizando os cartões | A3 (padrão) | RQ-07 (por contraste) + RQ-03 (ligar a flag devolve a tela que existe hoje) | EP: mapa declarado × mapa ausente | CT-02 |
| **R6** — o pacote `harvirsidhu/filament-cards` permanece declarado como dependência do projeto | A4 (mínimo) | RQ-01 | rastreio de efeito estrutural | CT-11 |
| **R7** — a documentação do pacote carrega o novo default, a imagem e o encaixe declarado | A5 (mínimo) | RQ-02, RQ-05, RQ-06 | EP sobre os artefatos documentais | CT-12 |

**Técnica escalada acima do perfil da área**: nenhuma.

**Nenhuma regra estoura o teto do perfil.** R1 tinha 4 cenários na primeira derivação, contra teto
de 3; a auditoria Ponytail mostrou que dois deles não matavam mutante próprio e o teto passou a ser
respeitado por corte, não por justificativa.

## Fronteira com o Plano

| Item do PRD | Vale como oráculo? | Destino |
|---|---|---|
| o nome `kit.hub` / `KIT_HUB` | **sim** — não é escolha só do PRD: a decisão "flag `kit.hub`, default `false`" está registrada no `00-requisito.md`, na ambiguidade de RQ-03 **resolvida com o usuário em 2026-08-21**. O PRD só a implementa | oráculo de R1 e R2 |
| **403** como resposta da rota desligada | **sim, sob premissa** — está no `00`, seção "Devolvidas pela derivação de testes", com o "Se negado" escrito | CT-01 marcado `@premissa` |
| o **texto** de cada descrição | **não** — só o PRD o determina, e é **comportamento visível ao usuário**. Isto é **achado**: o requisito diz "uma descrição para explicar o que cada link serve" e não determina as frases | ver pergunta abaixo; CT-07 usa uma string literal marcada `@premissa` |
| os nomes `descricoesDosDestinos()` e o parâmetro `descricoes:` | **não** — escolha de implementação | detalhe do cenário; nenhum `Então` os menciona |
| o nome de arquivo `infra-hub` para a captura | **não** como comportamento — mas o **artefato** é exigido por RQ-05, e o nome é o único jeito de apontá-lo | oráculo de R7, como caminho de arquivo |
| `1400x875` / `760x475` como proporção | **não** — decisão anterior, de `app/Console/Commands/KitArte.php` | fora dos cenários |
| a lista dos 16 FQCN do painel `/infra` | **não** — é o estado atual do painel, não requisito. Um plugin novo muda a lista sem mudar a cláusula | ver a pergunta sobre CT-08 |

**Perguntas em aberto** (a replicar em `00-requisito.md` → `## Ambiguidades`):

- **RQ-07 — quem aprova o texto das dezesseis descrições?** O requisito pede "uma descrição para
  explicar o que cada link serve" e não determina as frases. Elas foram escritas no PRD a partir da
  leitura de cada destino.
  - **Premissa adotada**: as frases do PRD valem. CT-07 assere **uma** delas literalmente, como
    canário; as outras quinze são cobertas por "descrição presente e não vazia".
  - **Se negado**: troca-se a string de CT-07, não a regra.

- **RQ-07 — "cada card" obriga a cobrir os cartões que ainda não existem?** Um plugin novo no
  painel `/infra` acrescenta um cartão, e ele nasce **sem** descrição, porque o mapa é escrito à
  mão.
  - **Premissa adotada**: sim, e CT-08 existe para acusar. Consequência aceita: **CT-08 fica
    vermelho quando alguém instala um plugin com página no `/infra` e não escreve a frase.**
  - **Tensão com ADR-04 desta wiki**, que recusou "um teste que compare o mapa com a lista de
    destinos" chamando o vermelho de "ruído, não defeito". CT-08 é uma variante desse teste, e a
    leitura literal de RQ-07 o exige. **A ADR e a cláusula discordam, e a decisão é do usuário** —
    manter CT-08 (RQ-07 ao pé da letra, ao custo de um vermelho a cada plugin novo) ou cortá-lo
    (ADR-04, ao custo de cartão sem frase entrar sem ninguém notar).
  - CT-08 fica marcado `@premissa` até a resposta.

- **RQ-02 e RQ-06 não são integralmente verificáveis por teste.** "Documentado para uso quando for
  necessário" e "o encaixe é páginas de links e fluxos" se materializam em prosa. CT-12 cobre o que
  é verificável — os arquivos existem, estão referenciados e a flag é mencionada. Que a prosa seja
  **boa** não tem assertion. **Lacuna declarada por natureza da cláusula**, registrada para o
  `feature-quality-gate` não a ler como omissão.

## Setup Global

### Personas

| Persona | Como criar | Por que ela, e não outra |
|---|---|---|
| `master_global` | `usuarioDoKit('master_global', 'master@example.com')` | **persona discriminante de R1**: ele vence toda checagem de permissão pelo `Gate::before`. Se o desligamento tiver sido implementado como permission em vez de config, **só ele** atravessa — e o cenário fica vermelho |
| `admin` | `usuarioDoKit('admin', 'admin@example.com')` | papel de painel do `/admin`, sem coringa |
| `infra` | `usuarioDoKit('infra', 'infra@example.com')` | papel de painel do `/infra` |
| `panel_user` | `usuarioComPapel('panel_user', $this->acme, 'comum@example.com')` + `->tenants()->attach()` | usuário comum do `/app`; só existe com contexto de organização |

### Fixtures

Nenhuma. A feature não tem model. O que os cenários precisam é dos **seeders do Shield**, como toda
tela do kit: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.

### Fakes

Nenhum. Não há e-mail, job, evento nem HTTP externo nesta feature.

### Estratégia de DB

`RefreshDatabase` global, aplicado por `tests/Pest.php` a `Kit`, `Tenancy`, `Browser` e
`BrowserTenancy`. Nada a configurar por arquivo.

### Arranjo da flag — e a armadilha da precedência

O `phpunit.xml` fixa `KIT_HUB=false` com `force="true"`. Consequência para os cenários:

- **partição "desligada" não precisa de arranjo**, e é isso que faz dela um teste do **default**
  em vez de um teste do ambiente de quem roda;
- **partição "ligada" usa `config(['kit.hub' => true])`**, no mesmo padrão que nove arquivos já
  usam para `kit.demo`.

> ⚠️ Um cenário que escrevesse `Dado a configuração de fábrica` sem afirmar o valor efetivo lido
> seria vácuo. Por isso CT-01 tem um `E` explícito sobre o valor de `config('kit.hub')`.

### Ligação do `tests/Unit` — por que nenhum cenário mora lá

`tests/Pest.php` **não** estende `TestCase` em `Unit`: a pasta roda sem container da aplicação.
Todo cenário desta feature lê `config()` ou renderiza componente, então a camada mais barata que o
arnês do projeto sustenta é `tests/Kit` (single-tenant) ou `tests/Tenancy`. Declarado para o próximo
agente não "otimizar" um caso para `Unit` e vê-lo morrer no arranjo.

---

## Regra R1 — a flag governa o acesso aos hubs de `/admin` e `/app`

> `RQ-03`, `RQ-04` · perfil **padrão** · técnica: **tabela de decisão** (flag × superfície) +
> **matriz papel × acesso**

### A tabela de decisão

| # | flag | painel | superfície | resultado esperado | cenário |
|---|---|---|---|---|---|
| 1 | `false` | `/admin` | URL | 403 | CT-01 |
| 2 | `false` | `/app` | URL | 403 | CT-01 |
| 3 | `false` | `/admin` | barra lateral | item ausente | CT-01 (última assertion) |
| 4 | `false` | qualquer | busca ⌘K | item ausente | **colapsada** — a categoria `PagesAutorizadasCategory` do kit consulta o mesmo `canAccess()` que a célula 1 falsifica, e não tem ramo próprio para o hub |
| 5 | `true` | `/admin` | URL | 200 + destinos | CT-02 |
| 6 | `true` | `/app` | URL | 200 + destinos | CT-02 |
| 7 | `true` | `/admin` | barra lateral | item presente | CT-02 (penúltima assertion) |

> **As células 3 e 7 não ganharam cenário próprio de propósito.** A auditoria Ponytail apontou:
> `Page::registerNavigationItems()` retorna cedo quando `canAccess()` é falso
> (`vendor/filament/filament/src/Pages/Page.php:133-135`), então **não existe implementação
> plausível que acerte a rota e erre o menu**, ou vice-versa. Um cenário separado para a barra
> lateral não mata mutante que a assertion embutida não mate — e vira um teste do early-return do
> vendor.
>
> **Foi cortada também a célula "cartão dentro do hub de `/infra`"**, que estava na primeira
> derivação como CT-04. Ela era **tautológica**: `HubDeAdministracao` não é um destino do painel
> `/infra`, logo o `assertDontSee` ficava verde com a flag inteira removida. Ver
> `## Cogitado e cortado`.

```gherkin

# language: pt

Funcionalidade: O hub em cards fora do padrão da instalação

  Regra: com a flag desligada, o hub de /admin e o de /app não são alcançáveis

    @premissa  # 403, e não 404 — ver "Devolvidas pela derivação de testes" no 00-requisito.md
    Esquema do Cenário: [CT-01] a rota do hub recusa todo perfil enquanto a flag está desligada
      Dado um kit instalado, sem nenhum ajuste de configuração no teste
      E que o valor lido de "kit.hub" é falso
      E um usuário com o papel <papel>
      Quando ele abre <rota>
      Então a resposta é 403
      E a página não oferece nenhum destino do painel
      E o painel, aberto no painel de controle, não oferece <item> na navegação

      Exemplos:
        | papel         | rota                          | item                    | # o que esta linha distingue                               |

        | admin         | /admin/hub-de-administracao   | "Hub de administração"  | papel de painel, sem coringa                               |
        | master_global | /admin/hub-de-administracao   | "Hub de administração"  | **discriminante**: coringa do Gate::before também leva 403  |
        | panel_user    | /app/hub-do-negocio           | "Início"                | outro painel, outro papel                                  |

    Cenário: [CT-02] ligar a flag devolve o hub que existia, com os destinos e no menu
      Dado um kit com "kit.hub" ligado
      E um usuário com o papel admin
      Quando ele abre o hub de administração
      Então a resposta é 200
      E a página oferece o destino "Usuários"
      E a página oferece o destino "Funções"
      E o item "Hub de administração" está na navegação do painel
```

> **O CT-02 é o que protege os hubs de `/admin` e `/app` da mudança de assinatura do trait.** Eles
> chamam `cardsDoPainel()` **sem** o parâmetro de descrições, e é este cenário que morre se a
> implementação o exigir (`TypeError`) ou se ler a chave sem `??` (`ErrorException`). Ver R5.
>
> **A assertion `E nenhum cartão da grade tem descrição` foi cortada na implementação**, e o motivo
> está em "Desvios do Plano" do `03-progresso.md`: o hub de `/admin` tem `$searchable = true`, logo
> todo cartão traz `data-search-text`, e distinguir "sem frase" de "com frase" por HTTP exigiria
> fatiar o atributo de cada cartão. Os dois mutantes que ela mataria já morrem nas assertions acima —
> a tela nem responde 200. Assertion que não distingue nada é enfeite, não cobertura.
>
> **A última assertion do CT-01 é o que sobrou do antigo CT-03.** Ela cobre a célula 3 da tabela de
> decisão sem gastar um cenário — e é honesta sobre o que prova: o item ausente do menu é
> consequência do mesmo `canAccess()` que produziu o 403 duas linhas acima.

#### Mutantes previstos R1

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `shouldRegisterNavigation()` sobrescrito em vez de `canAccess()` — tira do menu e deixa a rota aberta | **CT-01** (a rota responderia 200, com a assertion do menu passando) |
| M2 | desligamento implementado como **permission** (`->can('ver-hub')`) em vez de config | **CT-01, linha `master_global`** (o coringa do `Gate::before` atravessaria) |
| M3 | condição invertida (`! config('kit.hub')`) | CT-01 e CT-02, nos dois sentidos |
| M4 | `||` no lugar de `&&` em `config('kit.hub') && parent::canAccess()` | **CT-01** — com o pai devolvendo `true` fixo, o `||` faz a Page abrir sempre |
| M5 | chave errada lida (`config('kit.hubs')`, `config('hub.enabled')`) → sempre `null` → sempre desligado | **CT-02** (ligar a flag não devolveria a tela) |
| M6 | `canAccess()` escrito em **uma** das duas Pages | CT-01 (a linha do painel esquecido) |
| M7 | flag lida no `boot()` do provider e guardada em propriedade estática — deixa de responder a `config()` em runtime | **CT-02** (o arranjo liga a flag depois do boot) |
| M8 | `$shouldRegisterNavigation = false` **junto** com o `canAccess()`, matando o item do menu também com a flag ligada | **CT-02, última assertion** (o item precisa voltar ao menu) |

---

## Regra R2 — o hub de `/infra` é alcançável com a flag em qualquer valor

> `RQ-04` (exceção declarada pelo usuário) · perfil **mínimo** · técnica: **EP no valor da flag,
> com o mesmo resultado esperado nas duas partições**

```gherkin
  Regra: o hub de infraestrutura não depende da flag

    Esquema do Cenário: [CT-05] o hub de infraestrutura responde com a flag em qualquer valor
      Dado um kit com "kit.hub" em <flag>
      E um usuário com o papel infra
      Quando ele abre o hub de infraestrutura
      Então a resposta é 200
      E a página oferece o destino "Execuções de IA"

      Exemplos:
        | flag       | # o que esta linha distingue                                              |

        | desligada  | **a linha que fica vermelha se alguém acrescentar a flag a esta Page**    |
        | ligada     | controle: prova que a linha de cima não passa por acidente de arranjo    |
```

> **É o cenário que ADR-03 pediu.** Duas linhas com o **mesmo** `Então` parecem redundância e não
> são: uma partição sozinha não distingue "não depende da flag" de "depende, e o arranjo calhou de
> estar no valor certo".

#### Mutantes previstos R2

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | a assimetria é "corrigida" e o `/infra` ganha `canAccess()` com a flag | **CT-05, linha `desligada`** |
| M10 | a flag é lida num lugar comum aos três hubs (trait, classe-base, `Page::configureUsing`) e alcança o `/infra` sem querer | **CT-05, linha `desligada`** |
| M11 | o `/infra` é ligado por uma **segunda** chave (`kit.hub.infra`), que alguém depois desliga | CT-05, linha `desligada` — ⚠️ **matador parcial**: mata a chave nova em `false`, não a existência dela. Ver lacuna L1 |

**L1 — lacuna declarada**: nenhum cenário impede que alguém **crie** uma segunda chave para o
`/infra` e a deixe em `true`. Tentado: um teste de arquitetura sobre as chaves de `config/kit.php`.
Recusado — ficaria vermelho a cada chave nova legítima, e o custo do defeito é baixo (um
interruptor supérfluo, não uma tela quebrada). ADR-03 registra a decisão de uma chave só; a
prosa é a barreira aqui, e ela é admitidamente mais fraca que um teste.

---

## Regra R3 — desligar o hub não altera a matriz de permissões

> `RQ-03` · perfil **padrão** · técnica: **rastreio de efeito — o não-efeito**

```gherkin
  Regra: a flag esconde a tela e não mexe na permissão

    Esquema do Cenário: [CT-06] a permissão do hub continua na matriz com a flag desligada
      Dado um kit instalado, sem nenhum ajuste de configuração no teste
      Quando os seeders de permissão são executados
      Então a permissão "<permissao>" existe

      Exemplos:
        | permissao                | # painel                        |

        | View:HubDeInfraestrutura | infra — o hub que fica ligado   |
        | View:HubDeAdministracao  | admin — hub desligado           |
        | View:HubDoNegocio        | app — hub desligado             |
```

> **É o cenário que protege ADR-02.** A alternativa recusada lá — recortar o registro da Page no
> provider — passaria pelo CT-01 sem esforço e faria estas três permissões desaparecerem.
> Sem CT-06, a decisão de ADR-02 não tem nenhuma barreira executável.
>
> O caso já existente `it('mantém o hub do negócio com o usuário comum e a administração fora
> dele')` (`tests/Tenancy/HubDoNegocioTest.php:79-85`) cobre a metade "o `panel_user` **tem**
> `View:HubDoNegocio`" e **continua valendo sem alteração**. CT-06 cobre a outra metade: a
> permissão existir, nos três painéis, com a flag no default.

#### Mutantes previstos R3

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | desligamento por recorte no `->pages()` / `discoverPages()` do provider | **CT-06** (as duas permissões dos hubs desligados sumiriam) |
| M13 | o hub desligado é acrescentado a `PapeisSeeder::permissoesDeAdministracaoDoApp()` "porque agora é opcional" | caso existente em `tests/Tenancy/HubDoNegocioTest.php:79-85` (primeira assertion) |
| M14 | a permissão é apagada por um seeder novo "de limpeza" | CT-06 |

---

## Regra R4 — cada destino do hub de `/infra` exibe descrição

> `RQ-07` · perfil **padrão** · técnica: **EP sobre os destinos**

```gherkin
  Regra: cada cartão do hub de infraestrutura explica para que o destino serve

    @premissa  # o TEXTO das frases é escolha do PRD; ver "Fronteira com o Plano"
    Cenário: [CT-07] os cartões trazem a descrição do seu próprio destino
      Dado um kit instalado
      E um usuário com o papel infra
      Quando ele abre o hub de infraestrutura
      Então o cartão "Backups" descreve "quando rodaram, tamanho e se o destino respondeu"
      E o cartão "Execuções de IA" traz uma descrição não vazia
      E o cartão "Exception" traz uma descrição não vazia
      E as três descrições são diferentes entre si

    @premissa  # pendente da resposta sobre "cada card obriga a cobrir cartão que ainda não existe"
    Cenário: [CT-08] nenhum cartão do hub de infraestrutura fica sem descrição
      Dado um kit instalado
      E um usuário com o papel infra
      Quando ele abre o hub de infraestrutura
      Então todo cartão da grade tem descrição não vazia
```

> **Os três destinos de CT-07 são escolha discriminante, não conveniência.** "Backups" tem rótulo
> autoexplicativo e é o que carrega a string literal; "Execuções de IA" é do kit; "Exception" é
> rótulo de vendor não traduzido — a partição para a qual RQ-07 existe. E `as três descrições são
> diferentes entre si` é a assertion que mata o mapa deslocado por um índice: sem ela, uma
> implementação que aplica a mesma frase a todo cartão passa nas três primeiras linhas.
>
> **A descrição entrar na busca da página não ganhou cenário.** Ela entra: a blade do pacote injeta
> a frase no `data-search-text` de cada cartão
> (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:264`). Mas quem
> injeta é o **vendor**, e não há código do kit que possa deixar de fazê-lo — o mutante seria
> implausível, e o cenário viraria teste da blade de terceiro. É o mesmo corte que a wiki ancestral
> já fez em "busca client-side — testa o Alpine do vendor". O ganho continua real e está registrado
> em ADR-04; ele simplesmente **vem de graça** e não precisa de guarda.

#### Mutantes previstos R4

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | `->description()` nunca é chamado — o mapa existe e não é ligado ao cartão | **CT-07** (primeira assertion) |
| M16 | a mesma frase é aplicada a todos os cartões (variável de laço fora do escopo) | **CT-07** (`as três são diferentes`) |
| M17 | o mapa é indexado por **rótulo** em vez de FQCN — funciona nos rótulos em português e falha nos de vendor | **CT-07** (a linha "Exception") |
| M18 | o mapa é indexado por posição e desloca por um (o `excluir` remove um item antes) | **CT-07** (`as três são diferentes` + a string literal em "Backups") |
| M19 | a descrição é passada ao `CardGroup` em vez do `CardItem` — aparece uma vez por grupo | **CT-07** (o cartão de "Backups" não teria a frase) |
| M20 | um destino novo entra no painel e ninguém escreve a frase | **CT-08** — que é justamente a razão de ele ser controverso |

---

## Regra R5 — a descrição é opcional

> `RQ-07` por contraste + `RQ-03` (ligar a flag devolve a tela que existe hoje) ·
> perfil **padrão** · técnica: **EP: mapa declarado × mapa ausente** ·
> **coberta por assertion dentro do CT-02**, sem cenário próprio

A partição "mapa ausente" é o hub de `/admin`, que não declara descrição nenhuma. O CT-02 já o abre
com a flag ligada e já assere os destinos — e são essas assertions que falsificam a regra, porque
as duas implementações erradas plausíveis impedem a tela de responder 200:

```gherkin
  Regra: hub que não declara descrição continua renderizando os cartões

    # As assertions do CT-02, acima:
    #   Então a resposta é 200
    #   E a página oferece o destino "Usuários"
```

> A regra continua existindo, e é ela que protege os **outros dois hubs** da mudança de assinatura
> do trait: uma implementação que exija o parâmetro — ou que leia a chave sem `??` — derruba
> `/admin` e `/app` no dia em que alguém ligar a flag. O que a auditoria cortou foi o **cenário
> duplicado**: um segundo cenário abrindo a mesma tela, com a mesma persona e a mesma flag, mata
> exatamente os mesmos dois mutantes.
>
> **Pós-implementação**: a assertion "nenhum cartão tem descrição", que o `04` previa dentro do
> CT-02, também saiu — ela não distinguia nada por HTTP, e os dois mutantes morrem antes, no status
> da resposta. Ver "Desvios do Plano" no `03-progresso.md`.

#### Mutantes previstos R5

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | o parâmetro `descricoes` é declarado **obrigatório** no trait | **CT-02** (as duas outras Pages nem instanciam) |
| M22 | `$descricoes[$componente]` sem `??` — `ErrorException: Undefined array key` no primeiro cartão sem frase | **CT-02** |

**L2 — lacuna declarada**: a partição "chave **órfã** no mapa" (FQCN que não existe no painel) não
tem cenário. Tentado: injetar a chave órfã por `config()` — não serve, o mapa é código; e por
subclasse de teste do `HubDeInfraestrutura` — recusado, porque a subclasse testaria a subclasse.
O risco é baixo por construção: a chave só é lida por `$descricoes[$componente] ?? null`, dentro de
um `->map()` sobre os componentes **do painel** — chave que não casa nunca é alcançada.

---

## Regra R6 — o pacote permanece declarado como dependência

> `RQ-01` · perfil **mínimo** · técnica: **rastreio de efeito estrutural**

```gherkin
  Regra: tirar o hub do padrão não tira o pacote do projeto

    Cenário: [CT-11] o pacote do hub continua declarado e carregável
      Dado o composer.json do projeto
      Então a seção de dependências declara "harvirsidhu/filament-cards"
      E a classe base de página em cartões do pacote é carregável
```

> **É o único cenário que RQ-01 tem**, porque a cláusula não gera passo de implementação: ela
> **proíbe** uma ação. Sem ele, `composer remove harvirsidhu/filament-cards` — o caminho que
> ADR-01 recusou — passaria por toda a suíte de backend desta wiki, e o `/infra` só quebraria
> quando alguém abrisse o painel.
>
> **As duas assertions não são a mesma.** O `composer.json` prova a **declaração** (que sobrevive a
> `composer install`); o `class_exists` prova que o pacote está de fato instalado. Uma sem a outra
> deixa passar `--no-dev` mal configurado ou dependência transitiva não declarada.

#### Mutantes previstos R6

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | `composer remove harvirsidhu/filament-cards` como forma de "tirar do padrão" | **CT-11** |
| M24 | o pacote é movido para `require-dev` — presente em dev, ausente em produção | **CT-11** (a assertion é sobre `require`, não sobre a união das duas seções) |

---

## Regra R7 — a documentação carrega o novo default, a imagem e o encaixe

> `RQ-02`, `RQ-05`, `RQ-06` · perfil **mínimo** · técnica: **EP sobre os artefatos documentais**

```gherkin
  Regra: a opção documentada é uma opção encontrável

    Esquema do Cenário: [CT-12] os artefatos da documentação da opção existem
      Dado a árvore do projeto
      Então <artefato> existe e está íntegro

      Exemplos:
        | artefato                                                    | # o que esta linha distingue                    |

        | o arquivo art/infra-hub.png                                 | a captura foi produzida (RQ-05)                 |
        | o arquivo art/thumbs/infra-hub.png                          | a thumb foi gerada pelo kit:arte (RQ-05)        |
        | a referência a art/thumbs/infra-hub.png em wikis/receitas.md | a imagem não ficou órfã (RQ-05)                 |
        | a menção a KIT_HUB em wikis/receitas.md                      | a flag ficou documentada (RQ-02)                |
```

> **A terceira e a quarta linhas são as que valem.** Imagem que existe e ninguém referencia é peso
> morto no repositório; referência para imagem que não existe é o **ícone quebrado** que o GitHub
> renderiza — defeito visível para todo mundo que abre o README, e invisível para toda a suíte.
>
> O kit já assere sobre conteúdo de arquivo de documentação em
> `tests/Kit/CacheDeViewsNoDockerTest.php`. A rule `.ai/rules/testes.md` avisa: **asserção de
> ausência sobre arquivo documentado precisa filtrar comentário**. Aqui as quatro assertions são de
> **presença**, então o filtro não se aplica — mas fica registrado para quem acrescentar uma
> negativa depois.

#### Mutantes previstos R7

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | a captura é gerada e nunca linkada na documentação | **CT-12, linha 3** |
| M26 | o link é escrito para um caminho que não existe (`infra_hub.png`, `hub-infra.png`) | **CT-12, linhas 1 e 3** |
| M27 | a flag é criada e a documentação não a menciona | **CT-12, linha 4** |
| M28 | só o PNG cheio é publicado, sem a thumb (`kit:arte` não rodado) | **CT-12, linha 2** |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}`, ou
> `lacuna declarada: {o que foi tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota desta feature recebe `{id}` de recurso. As Pages não têm registro |
| Autorização exercida na ação, não só no `can()` | **CT-01** — o cenário abre a **rota** e afirma 403. Um cenário que chamasse `HubDeAdministracao::canAccess()` direto ficaria verde com a Page ainda alcançável, porque provaria o método e não o caminho |
| Idempotência (ancorada no agregado) | **não se aplica**: a feature não tem operação de escrita. Nada é gravado, nada é consumido |
| Concorrência | **não se aplica**: sem contador, saldo, estoque ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: sem ponto de gravação. A única entrada é um booleano de config, coberto por EP em CT-01/CT-02/CT-05 |
| Domínio condicionado (um campo depende do outro) | **CT-05** — é a única condicional da feature: o resultado depende do **painel**, não só da flag |
| Estado × operação de escrita | **não se aplica**: sem ciclo de vida, sem `status`, sem operação de escrita |
| Ausente ≠ `null` ≠ vazio | **CT-02** — duas partições no mesmo cenário: chave ausente no mapa (última assertion, cartão sem frase) e `KIT_HUB` ausente do `.env`, onde `env()` devolve `null` e `(bool) null` dá o default pretendido. A partição `""` não existe: o cast é para booleano |
| Paginação / ordenação | **não se aplica**: a grade não pagina. A ordem dos cartões é a da navegação e já é coberta por CT-05 da wiki ancestral, que não muda |
| Timezone / DST | **não se aplica**: dimensão T da varredura declarada vazia |
| Unicode / limite de varchar | **não se aplica**: as descrições são literais de código, não entrada de usuário. Sem campo de texto, sem coluna |
| Unicidade + soft delete | **não se aplica**: sem persistência |
| CRUD combinado | **não se aplica**: sem CRUD |
| Mass assignment | **não se aplica**: sem payload de formulário |
| Upload | **não se aplica**: sem upload |
| Precisão monetária | **não se aplica**: sem valor monetário |
| **Matriz papel × acesso** (item local, não da lista padrão) | **CT-01** — três papéis, incluindo o coringa `master_global`, que é a linha discriminante |
| **Dependência declarada não removida** (item local) | **CT-11** |
| **Documentação que aponta para arquivo inexistente** (item local) | **CT-12** |

> As três últimas linhas são candidatas a entrar na taxonomia do projeto em `.ai/rules/testes.md`:
> "documentação que referencia arquivo inexistente" já produziu ícone quebrado em README neste
> repositório antes, e não está na lista padrão da skill.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | rota do hub recusa todo perfil com a flag desligada | R1 | tabela de decisão + matriz papel×acesso | Feature HTTP | `tests/Kit/HubDeCardsTest.php` (linhas `/admin`) · `tests/Tenancy/HubDoNegocioTest.php` (linha `/app`) | M1, M2, M3, M4, M6 |
| CT-02 | ligar a flag devolve o hub, com destinos, no menu e sem descrição | R1, R5 | EP | Feature HTTP | `tests/Kit/HubDeCardsTest.php` | M3, M5, M7, M8, M21, M22 |
| CT-05 | o `/infra` responde com a flag em qualquer valor | R2 | EP | Feature HTTP | `tests/Kit/HubDeCardsTest.php` | M9, M10, M11 (parcial) |
| CT-06 | as três permissões continuam na matriz | R3 | rastreio de não-efeito | Feature | `tests/Kit/HubDeCardsTest.php` | M12, M14 |
| CT-07 | os cartões trazem a descrição do próprio destino | R4 | EP sobre destinos | componente Livewire | `tests/Kit/HubDeCardsTest.php` | M15, M16, M17, M18, M19 |
| CT-08 | nenhum cartão fica sem descrição | R4 | EP exaustiva `@premissa` | componente Livewire | `tests/Kit/HubDeCardsTest.php` | M20 |
| CT-11 | o pacote continua declarado e carregável | R6 | efeito estrutural | Feature | `tests/Kit/HubDeCardsTest.php` | M23, M24 |
| CT-12 | os artefatos da documentação existem e estão íntegros | R7 | EP sobre artefatos | Feature | `tests/Kit/HubDeCardsTest.php` | M25, M26, M27, M28 |
| CT-B02 | a descrição aparece desenhada no cartão | R4 | inspeção visual | **Browser** | `tests/Browser/HubDeCardsTest.php` | ver `05-casos-de-teste-browser.md` |

**Camada — nota sobre CT-01 e CT-05.** Eles são HTTP e não componente de propósito: o oráculo é o
**403 da rota**, e `Livewire::test()` não atravessa o middleware do painel. Um cenário de
componente afirmaria sobre o método, não sobre o caminho — é a distinção que a linha "Autorização
exercida na ação" do checklist cobra.

**Camada — nota sobre CT-07 e CT-08.** São componente e não browser porque o oráculo é **texto no
DOM**, que `Livewire::test()->assertSee()` falsifica em milissegundos. Que a frase esteja **pintada
e caiba no cartão** é outra afirmação, e é a única que foi para o `05`.

**Camada — nota sobre CT-11 e CT-12.** Não leem config nem renderizam, então rodariam em `Unit` —
mas `tests/Unit` não tem `TestCase` ligado em `tests/Pest.php`, e os dois usam `base_path()`.
Ficam em `tests/Kit`, junto do resto, e entram no `composer test:kit`.

## Cogitado e cortado

### Cortes da auditoria Ponytail (step 6 da `feature-wiki`)

| Cenário cortado | Por que foi cortado |
|---|---|
| **CT-04** — hub desligado não aparece como cartão dentro do hub de `/infra` | **tautológico.** `HubDeAdministracao` não é um destino do painel `/infra` — o `assertDontSee` ficava verde com a flag inteira removida. Mata M1 e M6 zero vezes. É o pior tipo de cenário: parece cobrir o vazamento que ADR-04 da ancestral descreve, e não cobre nada. **Nada o substitui**, e a decisão é consciente: o cenário que provaria aquele vazamento só existe num kit com os três hubs ligados, e esse kit não é o default |
| **CT-03** — o item do hub não está na barra lateral | `Page::registerNavigationItems()` já retorna cedo em `canAccess()` falso (`vendor/filament/filament/src/Pages/Page.php:133-135`). Não existe implementação plausível que acerte a rota e erre o menu. Virou a **última assertion do CT-01** |
| **CT-09** — a busca alcança o cartão pela descrição | assere o `data-search-text`, preenchido pela **blade do vendor** (`cards-page.blade.php:264`). Não há código do kit que possa deixar de injetá-lo: o mutante não é plausível, e o cenário viraria teste de blade de terceiro |
| **CT-10** — hub sem mapa renderiza sem descrição | abria a **mesma tela**, com a **mesma persona** e a **mesma flag** do CT-02, matando os mesmos dois mutantes. Virou a **última assertion do CT-02** |

### Cortes da própria derivação

| Cenário cogitado | Por que foi cortado |
|---|---|
| a busca ⌘K não oferece o hub desligado | a categoria `PagesAutorizadasCategory` consulta o mesmo `canAccess()` que CT-01 falsifica, e não tem ramo próprio para o hub. Célula 4 da tabela de decisão, colapsada |
| `php artisan config:show kit.hub` devolve `false` | mede o comando do Laravel, não a feature. Ficou como item de conferência manual na `## Verificação Final` do PRD |
| o `.env.example` contém `KIT_HUB=false` | duplica CT-12 (linha 4) num arquivo que não é lido em teste — o `phpunit.xml` sobrepõe. Mutante coberto por M28 |
| chave órfã no mapa de descrições | ver **L2** — inexpressável sem subclasse de teste, e inalcançável por construção |
| segunda chave de config para o `/infra` | ver **L1** — mata o valor, não a existência. Teste de arquitetura sobre `config/kit.php` recusado por falso positivo a cada chave nova |
| `Esquema do Cenário` exaustivo com os 16 destinos e as 16 frases | é CT-08 com custo de manutenção máximo: 16 linhas que mudam a cada plugin. CT-07 (3 destinos discriminantes) + CT-08 (todo cartão tem frase) cobrem os mesmos mutantes com um décimo do texto |
| cenário de `/app` para CT-07 e CT-08 | a superfície é a mesma e o painel não discrimina nada nessas regras. O `/app` entra onde discrimina: CT-01 (linha `panel_user`) |

## Fechamento com Mutation Testing

`pestphp/pest-plugin-mutate` **está declarado** em `composer.json:92` — não é transitivo, não há
passo de instalação a acrescentar ao PRD.

```bash
vendor/bin/pest tests/Kit/HubDeCardsTest.php --mutate --path=app/Filament
```

- Exige **PCOV ou Xdebug** com `XDEBUG_MODE=coverage`. A rule do projeto registra que o ambiente
  ainda não tem PCOV — se o comando não terminar, é o mesmo motivo que inviabiliza o `--tia`, e não
  um defeito do conjunto.
- Escopo `--path=app/Filament` cobre as três Pages e o trait. **Não** usar `--path=app`: o kit
  inteiro é caro e devolve ruído.
- **Cegueira estrutural que este conjunto não delega ao mutante**: RQ-01, RQ-02, RQ-05 e RQ-06 não
  geram código a mutar. Nenhum mutante nasce delas, e o score não cai se elas forem violadas. Quem
  responde por essas quatro é a rastreabilidade `RQ` → cenário — CT-11 e CT-12.
