# Casos de Teste — Travas de exclusão e upload anônimo

> Requisito: `00-requisito.md` · Relatório: `05-security.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito e do relatório de auditoria**, não do plano. Nenhum cenário foi escrito
> olhando a implementação da correção — ela não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Negação de exclusão no `/app` (F-01) | 3 | 3 | **9** | **completo** |
| RPC de upload em rota pública (F-02) | 2 | 3 | **6** | padrão |
| Documentação da trava (RQ-04) | 1 | 2 | 2 | mínimo |

**Por que F-01 é `completo`**: `P=3` porque a correção depende de qual método o Filament consulta
internamente — é conhecimento de versão, e a versão muda. `I=3` porque a ação é autorização de
exclusão **irreversível** que, por `cascadeOnDelete` em `tenant_user`, derruba o vínculo da pessoa
com **todas** as organizações.

**Por que F-02 é `padrão` e não `completo`**: o `I` é 3 — é superfície anônima —, mas o `P` é 2: a
correção é um flag booleano sem parâmetro, sem fronteira e sem combinação. Não há domínio a
particionar.

- Técnicas aplicadas: **matriz papel × ação** (R1–R4), **matriz painel × operação** (R3, R4),
  **partição estrutural** (R5), **varredura de fonte** (R5 sweep, R6)
- Cenários: **14** · Regras: **6** · Mutantes previstos: **19** · Sem matador: **0**
- **Mutação verificada nas seis correções** — a matriz está no `03-progresso.md`
- Revisão adversarial: **11 lacunas**, 8 fechadas, 3 dissolvidas na medição (ver `03-progresso.md`)

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | dois `Resource` do `/app`, duas classes de `Page`, um docblock | CT-01…CT-11 |
| **F** | **negar** exclusão e **negar** upload. Duas funções, ambas de recusa — o que muda o desenho dos casos: cenário de recusa tem de afirmar o **não-efeito**, não só a recusa | CT-01…CT-06, CT-08, CT-09 |
| **D** | nenhum dado novo. Nenhuma migration, coluna ou payload. O que existe é o `$record` passado à autorização | CT-01, CT-04 |
| **I** | **três** interfaces, e é a dimensão que mais importa aqui: (1) a `DeleteAction` de tabela, (2) a `DeleteBulkAction`, (3) o RPC `_startUpload`/`_finishUpload` do Livewire. As três resolvem por caminhos **diferentes** do framework, e cobrir uma não cobre as outras | CT-01, CT-02, CT-06 |
| **P** | Filament **5.7.6**. É a dimensão de risco da entrega: o mapeamento ação → resposta de autorização vive em `Pages/Page.php:313,329` e é contrato do framework, não nosso | CT-02 (o par simétrico), CT-03B (o mapeamento do framework) |
| **O** | quem opera é o administrador de UMA organização no `/app`, e o `master_global` no `/admin`. O uso indevido é o administrador da organização tentando alcançar exclusão global | CT-04, CT-05 |
| **T** | **não se aplica**: nenhuma regra depende de tempo, ordem, concorrência ou expiração. A autorização é pura e sem estado | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a exclusão de usuário no `/app` é negada pelo método que o Filament consulta | F-01 (completo) | RQ-02 | matriz papel × ação | CT-01, CT-02, CT-03 |
| **R2** — a exclusão de convite no `/app` é negada do mesmo modo | F-01 (completo) | RQ-03 | matriz papel × ação | CT-04 |
| **R3** — a negação é **local ao painel**: o `/admin` continua podendo excluir | F-01 (completo) | RQ-02 | matriz painel × operação | CT-05 |
| **R4** — a negação **não vaza** para outras operações do mesmo resource | F-01 (completo) | RQ-02 | matriz painel × operação | CT-06, CT-07 |
| **R5** — página servida fora da autenticação do painel restringe o upload ao próprio schema | F-02 (padrão) | RQ-05, RQ-06 | partição estrutural + varredura de fonte | CT-08, CT-09, CT-10 |
| **R6** — a documentação da trava nomeia o mecanismo que de fato trava | doc (mínimo) | RQ-04 | varredura de fonte | CT-11 |

**Técnica escalada acima do perfil da área**: R6 está em área `mínimo` (1 cenário por regra) e é o
que ela recebe — mas a técnica é varredura de fonte, e não EP, porque o objeto da regra é **texto de
docblock**, que não tem domínio a particionar.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| O texto exato da mensagem do `Response::deny()` | comportamento visível que **o requisito não determina** — o relatório pede que a negação funcione, não que diga uma frase específica | **pergunta**, abaixo. Os cenários afirmam que a resposta é negada e que **há** mensagem, nunca o texto |
| Os nomes `getDeleteAuthorizationResponse` / `getDeleteAnyAuthorizationResponse` | pareceriam escolha de implementação — mas **não são**: o requisito, via §2 do relatório, nomeia esses métodos como o mecanismo que o framework consulta | **aceito como oráculo** (ADR-04) |
| O trait `RestrictsFileUploadsToSchemaComponents` | idem — nomeado no relatório §2 como a correção | aceito como oráculo |
| A escolha de manter os `can*()` | escolha de implementação (ADR-01) | detalhe. Nenhum cenário afirma que eles existem |
| O arquivo `tests/Kit/TravaDeExclusaoTest.php` | path, não comportamento | detalhe |

**Perguntas em aberto** (replicar em `00-requisito.md` → `## Ambiguidades`):

- **A mensagem da negação é parte do requisito?** O relatório sugere um texto, mas o `00` não
  determina nenhum. **Premissa adotada**: a mensagem é livre; o que o conjunto exige é que ela
  exista e não seja vazia, porque 403 mudo é pior experiência sem ser mais seguro. Cenários
  marcados `@premissa`: CT-01, CT-04.
  **Se negado** (o texto for normativo): CT-01 e CT-04 ganham uma assertion de igualdade sobre a
  string, e nada mais muda.

## Setup Global

### Personas

Três, e **distintas** — persona colapsada é o modo mais fácil de um cenário de autorização passar
com a barreira removida:

- `$adminDaOrganizacao` — `usuarioComPapel('admin_app', $organizacao, 'admin.app@example.com')`
- `$masterGlobal` — `usuarioComPapel('master_global', email: 'master@example.com')`
- `$alvo` — `usuario('alvo@example.com')`, o usuário **sobre o qual** a exclusão é tentada. Nunca o
  mesmo que executa: com ator = alvo, uma implementação que só proíba "excluir a si mesmo" passaria.

### Fixtures

- `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach` — é o padrão
  de `tests/Kit/PermissoesDeTelasTest.php`, e importa: sem as permissões semeadas, a policy recusa
  por ausência e **todo** cenário de negação fica verde pelo motivo errado.
- `$organizacao` — `tenant('Acme', 'acme')`

### Fakes

Nenhum. A feature não emite e-mail, job, evento nem notificação — o `## Channel de Log` do PRD
registra que ela também não loga.

### Estratégia de DB

`RefreshDatabase`, global no `tests/Pest.php` para a suíte `Kit`.

---

## Regra R1 — a exclusão de usuário no `/app` é negada pelo método que o Filament consulta

> `RQ-02` · perfil **completo** · técnica: **matriz papel × ação**

```gherkin
# language: pt

Funcionalidade: Travas de exclusão no painel de negócio

  Regra: excluir usuário a partir de uma organização é sempre negado, e a negação vem do
         mecanismo que o Filament consulta para autorizar a ação

    Cenário: [CT-01] @premissa a resposta de autorização de exclusão é negada, com motivo
      Dado o administrador de uma organização, autenticado no painel /app
      E um outro usuário como alvo
      Quando o painel resolve a autorização de exclusão daquele alvo
      Então a resposta é negada
      E a resposta carrega uma mensagem não vazia

    Cenário: [CT-02] a negação vale também para a exclusão em massa
      Dado o administrador de uma organização, autenticado no painel /app
      Quando o painel resolve a autorização de exclusão em massa
      Então a resposta é negada

    Cenário: [CT-03] a negação não depende de quem pergunta nem de ter a permissão na matriz
      Dado um usuário com a permissão "Delete:User" concedida no papel
      Quando o painel resolve a autorização de exclusão de um alvo
      Então a resposta é negada
```

**Por que CT-03 existe**: é o cenário que separa "negado porque falta permissão" de "negado porque
este painel proíbe". Sem ele, todos os outros ficariam verdes num sistema onde a negação viesse
apenas da matriz do Shield — e a trava do resource poderia ser removida sem nada apitar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | manter só `canDelete()`/`canDeleteAny()` devolvendo `false` (o estado atual) | CT-01, CT-02 |
| M2 | sobrescrever `getDeleteAuthorizationResponse()` e **esquecer** `getDeleteAnyAuthorizationResponse()` — a `DeleteBulkAction` resolve pelo segundo (`Page.php:329`) | CT-02 |
| M3 | devolver `Response::allow()` por engano | CT-01, CT-02 |
| M4 | devolver `Response::deny()` **sem mensagem**, dando 403 mudo | CT-01 |
| M5 | negar consultando a permissão (`abort_unless($user->can(...))`) em vez de negar sempre — o administrador da organização **tem** `Delete:User` na matriz | CT-03 |

---

## Regra R2 — a exclusão de convite no `/app` é negada do mesmo modo

> `RQ-03` · perfil **completo** · técnica: **matriz papel × ação**

```gherkin
    Cenário: [CT-04] @premissa convite não se exclui a partir da organização
      Dado o administrador de uma organização, autenticado no painel /app
      E um convite enviado por aquela organização
      Quando o painel resolve a autorização de exclusão daquele convite
      Então a resposta é negada
      E a resposta carrega uma mensagem não vazia
      E a resposta de exclusão em massa também é negada
```

**Por que um cenário e não três**: o `ConviteResource` recebe a **mesma** correção do
`UserResource`, e R1 já exercitou as três interfaces. O que R2 acrescenta é a cobertura do **segundo
arquivo** — a omissão real possível aqui é "corrigi um resource e esqueci o outro", e um cenário
mata esse mutante. Repetir CT-01…CT-03 no convite seria o "dois cenários que matam o mesmo conjunto
de mutantes" que o passo 7 manda podar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | corrigir só o `UserResource` e esquecer o `ConviteResource` | CT-04 |
| M7 | corrigir o `ConviteResource` só no par por registro, sem o `*Any` | CT-04 (a terceira asserção) |

---

## Regra R3 — a negação é local ao painel: o `/admin` continua podendo excluir

> `RQ-02` (regressão) · perfil **completo** · técnica: **matriz painel × operação**

```gherkin
  Regra: a proibição pertence ao painel de negócio, não ao sistema

    Cenário: [CT-05] o /admin continua autorizando a exclusão de usuário
      Dado o master global, autenticado no painel /admin
      E um outro usuário como alvo
      Quando o painel resolve a autorização de exclusão daquele alvo
      Então a resposta é permitida
```

**Por que este é o cenário mais importante do conjunto**: a correção "óbvia" para o achado é negar
na `UserPolicy`, e ela **passaria** em CT-01 a CT-04 inteiros. Este é o único cenário que a
reprova — e o `/admin` é o painel onde excluir usuário é a operação legítima e esperada. Sem CT-05,
a entrega pode fechar tudo verde tendo quebrado a administração global.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | negar na `UserPolicy::delete()` — proibiria os dois painéis | CT-05 |
| M9 | negar num trait compartilhado, ou na classe base dos resources | CT-05 |
| M10 | negar via `Gate::before` / `Gate::after` global | CT-05 |

---

## Regra R4 — a negação não vaza para outras operações do mesmo resource

> `RQ-02` (regressão) · perfil **completo** · técnica: **matriz painel × operação**

A matriz aqui é `operação × painel`, e a coluna que costuma faltar é a **válida**: cobrir só as
recusas deixa `editar` com três negativas e nenhuma edição que funciona.

| Operação no `/app` | Esperado | Cenário |
|---|---|---|
| `delete` | negado | CT-01 |
| `deleteAny` | negado | CT-02 |
| `update` | **permitido** | CT-06 |
| `view` / `viewAny` | **permitido** | CT-07 |

```gherkin
  Regra: proibir a exclusão não muda nenhuma outra operação do resource

    Cenário: [CT-06] a edição de usuário no /app continua funcionando de ponta a ponta
      Dado o administrador de uma organização, autenticado no painel /app
      E um usuário da organização com o nome "Nome Antigo"
      Quando ele salva o formulário de edição com o nome "Nome Novo"
      Então o formulário não acusa erro
      E o usuário gravado passa a se chamar "Nome Novo"

    Cenário: [CT-07] a listagem de usuários da organização continua visível
      Dado o administrador de uma organização, autenticado no painel /app
      E dois usuários vinculados àquela organização
      Quando ele abre a listagem de usuários
      Então os dois usuários aparecem
```

**CT-06 é gravação por componente, não visita** — a regra do par: *uma tela aberta não é uma tela
que grava*. Uma negação escrita no método errado (por exemplo em `getEditAuthorizationResponse`)
deixa o `GET` verde e quebra o `save`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | escrever a negação em `getEditAuthorizationResponse()` por confusão de nome | CT-06 |
| M12 | escrever em `getViewAnyAuthorizationResponse()` — a tela desapareceria do menu | CT-07 |
| M13 | negar em `getAuthorizationResponse()` (o genérico), atingindo **todas** as ações | CT-06, CT-07, e CT-05 |

---

## Regra R5 — página servida fora da autenticação do painel restringe o upload ao próprio schema

> `RQ-05`, `RQ-06` · perfil **padrão** · técnica: **partição estrutural + varredura de fonte**

As duas páginas são as duas partições estruturais do conjunto: a **pública** (`BoasVindas`, na rota
`/`, sem `auth`) e a **autenticada sem campo de upload** (`ConvitesRecebidos`).

```gherkin
  Regra: componente que herda o RPC de upload do Livewire e não tem campo de upload
         precisa restringir o upload ao próprio schema

    Cenário: [CT-08] a página pública restringe o upload ao schema
      Dado a página de boas-vindas, servida na rota pública "/"
      Quando se consulta se ela restringe uploads ao próprio schema
      Então a resposta é verdadeira

    Cenário: [CT-09] a página de convites recebidos também restringe
      Dado a página de convites recebidos do painel /app
      Quando se consulta se ela restringe uploads ao próprio schema
      Então a resposta é verdadeira

    Cenário: [CT-10] nenhuma página montada fora de um painel fica sem a restrição
      Dado toda classe de página do Filament montada direto em routes/web.php
      Quando se percorre essa lista
      Então cada uma restringe uploads ao próprio schema
      E a lista não está vazia
```

**Por que o oráculo é a resposta do método, e não um upload anônimo de verdade**: o `abort_unless`
vive num hook `on('call')` do Livewire (`SchemasServiceProvider.php:63-77`) e depende de
`isFileUploadForSchemaComponent()`. Exercitar o RPC exigiria montar uma requisição Livewire crua com
o snapshot do componente — caro, frágil, e mediria o Filament. O que é **nosso** é a composição do
trait, e é ela que o cenário afirma.

**Por que CT-10 tem a segunda asserção** ("a lista não está vazia"): sem ela, o cenário fica verde
para sempre no dia em que a extração da lista parar de achar as páginas — um `foreach` sobre array
vazio passa. É a armadilha do sweep vazio, e este projeto já a registrou como rule.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | aplicar o trait só na `BoasVindas` e esquecer a `ConvitesRecebidos` | CT-09 |
| M15 | aplicar em nenhuma das duas | CT-08, CT-09, CT-10 |
| M16 | sobrescrever o método devolvendo `false` (ou aplicar o trait e sobrescrever depois) | CT-08, CT-09 |
| M17 | página pública nova nascer sem o trait, no futuro | CT-10 |

---

## Regra R6 — a documentação da trava nomeia o mecanismo que de fato trava

> `RQ-04` · perfil **mínimo** · técnica: **varredura de fonte**

```gherkin
  Regra: nenhum comentário do kit afirma que canDelete() é a trava de exclusão

    Cenário: [CT-11] o docblock não aponta mais para a trava inexistente
      Dado o arquivo da página de edição de usuário do painel /app
      Quando se procura a afirmação de que a trava é o canDelete()
      Então ela não está lá
      E o arquivo cita o método de resposta de autorização
```

**Por que isto é caso de teste e não só um commit**: a frase errada é o vetor de reintrodução do
defeito. Um mantenedor que leia "a trava de verdade é `canDelete()`" volta a confiar nela. O caso
custa quatro linhas e impede que a afirmação retorne num futuro refactor de comentário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | corrigir o código e deixar o docblock antigo | CT-11 |
| M19 | reescrever o docblock sem citar o mecanismo real, deixando o leitor sem referência | CT-11 (a segunda asserção) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a negação é absoluta no painel, não por dono. Não há caso "A alcança o recurso de B" a distinguir — nem A nem B podem |
| Autorização exercida na ação (não só `can()`) | **CT-01, CT-02** — e é a essência do achado: o kit tinha exatamente o defeito inverso, uma negação em `can*()` que a ação nunca consultava |
| Idempotência (ancorada no agregado) | **não se aplica**: resolver autorização é leitura pura, sem efeito a duplicar |
| Concorrência | **não se aplica**: sem contador, saldo ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: a entrega não cria nem altera campo |
| Domínio condicionado | **não se aplica**: a negação não tem parâmetro |
| **Estado × operação de escrita** | **CT-06** — a coluna válida de `editar`, exercitada e afirmando o valor gravado |
| **Verbo irmão não herda evidência** | **CT-02, CT-04** — `delete` e `deleteAny` são verbos irmãos com resolução diferente no framework, e cada um tem cenário próprio |
| **Persona discriminante** | **CT-01, CT-03, CT-05** — três pessoas distintas: administrador da organização, portador da permissão, master global. Ator nunca é o alvo |
| Ausente ≠ null ≠ vazio | **não se aplica**: sem campo opcional |
| Paginação / ordenação | **não se aplica**: nenhuma listagem nova. CT-07 é regressão de visibilidade |
| Timezone / DST | **não se aplica**: dimensão T da SFDIPOT declarada vazia |
| Unicode / limite de varchar | **não se aplica**: sem entrada de texto |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **CT-06** (editar sem alterar o resto), **CT-05** (excluir onde é permitido) |
| Mass assignment | **não se aplica**: nenhum payload novo |
| **Upload** | **CT-08, CT-09, CT-10** — e o item aqui não é tamanho nem extensão: é o **canal** de upload existir sem destino legítimo |
| Precisão monetária | **não se aplica** |
| **Sweep vazio** | **CT-10** (segunda asserção) — `foreach` sobre lista vazia passa calado |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | resposta de exclusão negada, com motivo | R1 | matriz papel×ação | Feature | `tests/Kit/TravaDeExclusaoTest.php` | M1, M3, M4 |
| CT-02 | exclusão em massa negada | R1 | matriz papel×ação | Feature | idem | M1, M2, M3 |
| CT-03 | negado mesmo com a permissão na matriz | R1 | matriz papel×ação | Feature | idem | M5 |
| CT-04 | convite não se exclui da organização | R2 | matriz papel×ação | Feature | idem | M6, M7 |
| CT-05 | o `/admin` continua excluindo | R3 | matriz painel×operação | Feature | idem | M8, M9, M10 |
| CT-06 | edição no `/app` grava | R4 | matriz painel×operação | Livewire | `tests/Tenancy/AuditoriaDeSegurancaTenancyTest.php` | M11, M13 |
| CT-07 | listagem continua visível | R4 | matriz painel×operação | Livewire | idem | M12, M13 |
| CT-08 | página pública restringe upload | R5 | partição estrutural | Unit | `tests/Kit/TravaDeExclusaoTest.php` | M15, M16 |
| CT-09 | convites recebidos restringe | R5 | partição estrutural | Unit | idem | M14, M15, M16 |
| CT-10 | sweep das páginas fora de painel | R5 | varredura de fonte | Unit | idem | M17 |
| CT-11 | docblock aponta a trava real | R6 | varredura de fonte | Unit | idem | M18, M19 |

**Nota de camada**: CT-06 e CT-07 vão para `tests/Tenancy/` porque exigem organização corrente
resolvida — o `/app` é o painel escopado, e o helper `noPainelDa($tenant)` vive naquela suíte. Os
demais não precisam de painel bootado: a negação de R1/R2 é incondicional, e R5/R6 são estruturais.

## Sem CT-B

**Motivo**: nenhum cenário afirma sobre JavaScript executado, console, acessibilidade, cor, tema ou
layout. Os dois achados são de autorização, e autorização na tela é teste de componente Livewire —
CT-06 e CT-07 são exatamente isso.

A regressão visual da rota `/` continua coberta por `tests/Browser/BoasVindasTest.php`, que não
muda: o trait não altera renderização. Se alterasse, o cenário certo seria lá, não um novo.
