# Casos de Teste — Site de documentação do pacote em GitHub Pages

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Mapa: `90-mapa-do-conteudo.md`
> Derivado do **requisito**. O `01` e o `90` entraram só para caminho, estrutura e a tabela
> `## Superfície de UI` — nenhum comportamento esperado saiu deles. O `02-decisoes-arquiteturais.md`
> **não foi lido**, de propósito: ADR é interpretação, e interpretação não é oráculo.

## O que esta feature não é

Não há código de aplicação: nenhuma rota Laravel, model, policy, job ou migration. É **conteúdo,
configuração de build e um workflow de CI**. Isso muda três coisas no pipeline, e as três estão
declaradas onde importam:

1. **`pest --mutate` é inexpressível aqui** — ver `## Fechamento do Ciclo`.
2. **A camada mais barata que prova é sempre `tests/Kit`** — ver `## Setup Global`.
3. **O risco não é comportamento errado, é perda silenciosa**: conteúdo que não chega, e asserção
   que deixa de proteger porque o texto que ela vigiava mudou de arquivo. Tudo verde, documentação
   errada. Todo o gate de falsificabilidade abaixo mira esse risco.

---

## Perfil de Derivação

| Área | P | I | P×I | Perfil | Justificativa do fator |
|---|---|---|---|---|---|
| Rede das 79 asserções sobre os READMEs | 3 | 3 | **9** | **completo** | P: migração de dado atravessando 6 suítes. I: a rede guarda afirmações de **segurança** — o disco de mídia não ser público (`AnexosPrivadosDocumentacaoTest:36-38`) e o `client_secret` não vazar (`LoginSocialGoogleTest:898`). Perder a guarda é perder a barreira, não o texto |
| Migração de conteúdo (44 páginas) | 3 | 2 | 6 | padrão | P: migração de dado, 2.180 linhas × 2 idiomas. I: retrabalho manual, reversível por `git revert` |
| Paridade PT/EN | 3 | 2 | 6 | padrão | P: o histórico do próprio projeto já registra **4 divergências**, todas por omissão no inglês. É a falha mais provável desta entrega |
| Fronteira do dist (`create-project`) | 2 | 3 | 6 | padrão | I: irreversível na prática — uma versão publicada com `vitepress` na raiz é baixada por **todo** projeto instalado, "para sempre" (`00` → Restrições Herdadas, item 1) |
| Publicação (workflow, Pages, `base`) | 2 | 2 | 4 | padrão | O `base` errado quebra em produção e nada em local |
| README como landing | 1 | 2 | 2 | mínimo | Edição de texto, reversível, visível a olho |

- **Técnicas aplicadas**: EP exaustiva sobre baseline congelado, comparação de conjunto nos dois
  sentidos, BVA de dois lados (piso **e** teto), rastreio de efeito aplicado a **asserção** em vez
  de a e-mail, checklist de taxonomia adaptado a artefato de arquivo.
- **Cenários**: 21 · **Regras**: 11 · **Mutantes previstos**: 50 · **Sem matador**: 4 (declarados)
- **CT-B**: 0 — ver `## Gate de CT-B`, com a contestação.
- **Revisão adversarial**: 1 rodada independente executada, **29 achados**, todos fechados —
  3 cenários novos, 2 regras novas, 11 mutantes novos, 1 afirmação **errada** corrigida. Ver
  `## Revisão Adversarial`.

### Técnica escalada acima do perfil da área

**R3** (as 4 divergências conhecidas) fica numa área `padrão`, onde EP daria um cenário genérico de
paridade. Foi escalada para **cenário de regressão por token estável, um por divergência**, porque
paridade estatística é **comprovadamente cega** a três das quatro: um parágrafo de 5 linhas dentro
de uma página de 384 é 1,3% de divergência, e nenhuma tolerância utilizável a enxerga. A medida
agregada não substitui a regressão nomeada.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S**tructure | `docs/package.json`, `docs/.vitepress/config.ts`, 44 páginas `.md`, 2 `index.md`; `.github/workflows/docs.yml`; `.gitattributes`; os dois READMEs; as 6 suítes de documentação. **Zero artefato Laravel** | CT-01, CT-11, CT-15 |
| **F**unction | ler, navegar, buscar (função do leitor); publicar (função do CI); e a **função escondida**: o `create-project` deixar `docs/` de fora, e o `kit:update` não oferecer o que o dist retém | CT-11, CT-15 |
| **D**ata | 5.055 linhas de markdown de origem → 44 arquivos de destino. Dado grande: a maior seção tem **384 linhas** (Login social). Dado ausente: página que existe em `pt` e não em `en`. Dado duplicado: as 5 seções `ambos`, que ficam nos **dois** lados | CT-01…CT-06, CT-13 |
| **I**nterfaces | Nenhuma rota HTTP. As quatro interfaces reais são: o **build** do VitePress, o **`git archive`** do `create-project`, o **`kit:update`**, e o **Packagist** — que só lê o README e nunca vê o site | CT-11, CT-12, CT-14 |
| **P**latform | Node 22 + `npm ci` dentro de `docs/`. **Medido**: o job PHP do `.github/workflows/ci.yml:42` roda `--testsuite=Unit,Feature,Kit,Tenancy` **sem `setup-node`** — nenhum CT desta wiki pode depender de `npm`. Pages não serve Git LFS. Site em subdiretório exige `base` | CT-15, CT-16; e `## Fora do alcance` |
| **O**perations | Três públicos que não se cruzam: quem lê o site, quem descobre o kit pelo **Packagist** (nunca vê o site), e quem roda `create-project` (**não pode receber `docs/`**). Uso indevido previsível: alguém "consertar" uma asserção vermelha apagando-a | CT-07, CT-10, CT-14 |
| **T**ime | Ordem obrigatória: o passo 5 (corrigir as 4 divergências) vem **antes** do passo 4 (migrar) — invertido, a divergência é carregada para 44 arquivos. Deriva ao longo do tempo: as seções `ambos` passam a ter duas cópias que divergem sozinhas. Concorrência: dois pushes em `main` disparam dois deploys do Pages | CT-03, CT-06, CT-15 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — Todo bloco de conteúdo do baseline chega a **exatamente um** destino, nos dois idiomas | migração (padrão) | RQ-02, RQ-03 | EP exaustiva sobre baseline congelado + asserção de ausência | CT-01, CT-02, CT-03 |
| **R2** — `/pt/` e `/en/` têm o **mesmo conjunto** de páginas, a mesma estrutura e cada uma no seu idioma | paridade (padrão) | RQ-01, RQ-03 | conjunto (diferença nos **dois** sentidos) + razão de tamanho por página + marcador lexical | CT-04, CT-05, CT-19 |
| **R3** — As 4 divergências PT/EN conhecidas não sobrevivem à migração | paridade (padrão, escalada) | RQ-03 | regressão nomeada por **token estável** | CT-06 |
| **R4** — A rede de asserções continua vigiando o texto **onde ele passou a morar** | rede (completo) | RQ-03 + Restrição Herdada 2 | rastreio de efeito aplicado a asserção: existe / não virou vácuo / não mudou de alvo | CT-07, CT-08, CT-09, CT-10 |
| **R5** — Nada da documentação do site vaza para o projeto instalado | dist (padrão) | RQ-01 + Restrições Herdadas 1 e 3 | atributo de exportação em caminho **aninhado** + unicidade de dependência | CT-11, CT-12 |
| **R6** — O README encolhe **nos dois idiomas** e todo link que ele passa a carregar resolve | landing (mínimo → padrão) | RQ-02 | BVA de dois lados (piso e teto) + resolução de link | CT-13, CT-14 |
| **R7** — O site publica sozinho, no endereço certo, sem concorrer consigo mesmo | publicação (padrão) | RQ-01, RQ-06 | tabela de decisão do gatilho + derivação do `base` | CT-15, CT-16 |
| **R8** — O processo de atualização está **escrito**, nos dois idiomas | publicação (padrão) | RQ-06 | EP por idioma | CT-17 |
| **R9** — A migração não injeta sintaxe que o gerador interpreta como código | migração (padrão) | RQ-03 | texto livre / caractere especial | CT-18 |
| **R10** — Toda página publicada é alcançável pela navegação | navegação (padrão) | RQ-02 | alcançabilidade | CT-20 |
| **R11** — A mídia é imagem e GIF, sem duplicar peso nem usar LFS | mídia (padrão) | RQ-05 + Herdadas 4 e 5 | EP sobre tipo de artefato | CT-21 |

**Cobertura das `RQ`**: RQ-01 → R2, R5, R7 (com CT-15 afirmando **publicação**, não só build).
RQ-02 → R1, R6, **R10**. RQ-03 → R1, R2, R3, R4, R9. RQ-05 → **R11**. RQ-06 → R7, R8.

RQ-04 → **sem regra própria, e a dispensa tem uma ressalva**. É cláusula de *tamanho de entrega*:
determina **quantas** seções o baseline de R1 percorre, não o que o sistema faz. A ressalva é da
revisão adversarial e procede: o número **32** vem da tabela `## O estado de hoje, medido` do
próprio `00`, mas a decisão de que **todas as 32** migram nesta entrega existe só no `01` — que
este arquivo recusa como oráculo. Enquanto a resposta de RQ-04 não voltar ao `00`, **CT-01 e
CT-03 rodam sob a premissa "migração completa"** e estão marcados `@premissa` por isso.

---

## Fronteira com o Plano

| Item do `01` / `90` | Recusado como oráculo porque | Destino |
|---|---|---|
| `~340 linhas` de landing | número **estimado pelo plano**, com memória de cálculo própria; o `00` não quantifica | premissa em R6, com **razão** em vez de constante |
| `22 páginas por idioma` | recorte editorial do plano | detalhe. O oráculo de R1/R2 é **exaustividade e paridade**, que valem para qualquer N |
| `base: '/filament-starter-kit-easy/'` | valor literal escolhido pelo plano | CT-16 **deriva** o valor do repositório real e recusa o literal |
| Os caminhos `docs/pt/autenticacao/login-social.md` etc. | árvore proposta pelo `90` | usados como **dado do cenário**; o oráculo de R1 é "exatamente um destino", não "este destino" |
| VitePress como gerador | o `00` diz explicitamente que a escolha **não foi feita pelo solicitante** e vai para a ADR | plataforma dos caminhos. Só R9 depende do formato, e por um motivo medido |
| `paths: ['docs/**']` no gatilho | otimização do plano | detalhe. **Achado**: com esse filtro, corrigir um link no README não republica o site — correto, mas é a segunda metade do par README↔site e ninguém a declarou |
| "o build do VitePress falha em link interno morto" (mitigação do `01`) | verdadeiro **só dentro de `docs/`** | **achado**: o build é cego aos ~44 links novos que o README passa a carregar. Foi o que originou CT-14 |
| "é tudo verificável em arquivo" (gate de CT-B do `01`) | **falso para dois itens** | contestado em `## Gate de CT-B` |

### Perguntas para o `00-requisito.md`

> **Desvio declarado**: o `00` está sendo usado como linha de base desta derivação e não foi
> editado. As perguntas vão abaixo **em bloco pronto para colagem** em `## Ambiguidades e Perguntas
> Abertas`. Elas continuam bloqueando o que dependem delas.

```markdown
- **Qual o teto de tamanho da landing?** RQ-02 pede "mais fácil a leitura" e não quantifica; o
  número ~340 é estimativa do plano.
  - **Assumido**: teto de **30%** das 2.522 linhas medidas (≤ 756) e **piso de 100 linhas**.
    O piso importa tanto quanto o teto: sem ele, "README reduzido a um link" passa — e é
    exatamente o cenário que a ambiguidade de RQ-03 já assumiu contra.
  - **Se negado**: muda só o número de CT-13; a forma (razão, não constante) fica.

- **Qual a tolerância de divergência de tamanho entre `/pt/` e `/en/`, por página?**
  - **Assumido**: ±10% do tamanho da página em português, com piso absoluto de 5 linhas para
    páginas curtas.
  - **Medido, e é o motivo do valor**: hoje os READMEs inteiros divergem 0,44% (2.522 × 2.533) e
    **escondem três omissões reais**. Fragmentar em 22 páginas encolhe o denominador e devolve
    poder de detecção — a divergência do bullet de `getTabs()` é 0,36% do README e **32%** da
    página de destino.

- **As páginas do site entram na rede de asserções de documentação — e essa rede deixa de rodar
  no projeto instalado**, porque `docs/` será `export-ignore`. Confirma que a garantia de "a
  documentação não mente" passa a ser garantia **do repositório do kit**, não do projeto que nasce
  dele?

- **RQ-04 foi respondida e a resposta não voltou para cá.** O `01` registra "respondido pelo
  solicitante: migração COMPLETA nesta entrega"; o `00` ainda diz "a ser confirmado com o
  solicitante antes de implementar a migração de conteúdo". Registrar a resposta no `00` — é a
  cláusula que dimensiona o baseline de R1 (32 seções, não um subconjunto).
```

Cenários que dependem de premissa estão marcados `@premissa`: **CT-01** e **CT-03** (RQ-04: migração completa), **CT-05** (tolerância) e
**CT-13** (piso e teto).

---

## Setup Global

### Camada — e por que não há escolha

Todos os cenários afirmam sobre **arquivo em disco**. A camada mais externa observável é o
sistema de arquivos, e a mais barata que o **arnês deste projeto sustenta** é `tests/Kit`:

- **`tests/Unit` não serve.** Medido: `tests/Pest.php` liga `TestCase` a `Feature`, `Kit`,
  `Tenancy`, `Browser` e `BrowserTenancy` — e **não a `Unit`**. Sem container não há `base_path()`,
  e todo cenário aqui começa por `base_path()`.
- `tests/Kit` já é onde vivem as 6 suítes de documentação, e o grupo `kit` é o que
  `composer test:kit` roda.
- **Custo aceito**: `tests/Kit` usa `RefreshDatabase`, então um cenário puramente de arquivo paga
  migração. É o que as 6 suítes existentes já pagam; trocar isso é otimização não pedida.

### Arquivo novo

`tests/Kit/SiteDeDocumentacaoTest.php` — CT-01 a CT-06 e CT-11 a CT-18.
`tests/Kit/RedeDeDocumentacaoTest.php` — CT-07 a CT-10 (a rede é sobre as suítes, não sobre uma
feature; separar mantém o alvo legível).

### A sentinela obrigatória — sem ela a suíte fica vermelha em toda instalação

`docs/` será `export-ignore`. Em projeto nascido de `create-project` o diretório **não existe**,
e todo cenário que o varre falharia lá. O kit já tem o padrão e o motivo escrito
(`tests/Kit/KitUpdateTest.php:116`): `.github` é o sinal confiável de "estou na árvore do kit".

```php
function naArvoreDoKit(): bool
{
    return is_dir(base_path('.github'));
}
```

Helper usado por **dois** arquivos → vai para `tests/Pest.php`, não para um dos dois
(`.ai/rules/testes.md`; `tests/Kit/HelpersDeTesteTest.php` reprova o contrário).

### Helpers que precisam mudar de casa

| Helper | Onde está hoje | Por quê move |
|---|---|---|
| `readmeSemCitacao()` | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php:15` | CT-08 e as suítes reapontadas passam a usá-lo em mais de um arquivo |
| `secoesDoMarkdown()` | `tests/Kit/LoginSocialProvedoresTest.php:155` | CT-09 usa a mesma decomposição por título |

Os dois vão para `tests/Pest.php`. **Nunca clonar com outro nome** — a regra é explícita e o
projeto já pagou por ela.

### Fixture: o baseline congelado

`tests/Kit/fixtures/baseline-readme.php` — a lista dos **32 títulos `h2` e 83 `h3`** de
`README.md` e `README.en.md` **antes** da migração, com o SHA do commit de origem no topo do
arquivo, gerada por:

```bash
git show <sha>:README.md | grep -n '^#\{2,3\} '
```

Não é conveniência: é o único artefato que permite falsificar "o conteúdo migrou" **sem
reafirmar o que a implementação fez** — ele foi medido antes de existir implementação, e sua
cardinalidade é conferida contra os números que o próprio `00` já mediu (32 / 83).

### Fakes

Nenhum. Não há e-mail, fila, evento ou HTTP nesta feature.

---

## Regra R1 — Todo bloco do baseline chega a exatamente um destino, nos dois idiomas

> `RQ-02`, `RQ-03` · perfil **padrão** · técnica: **EP exaustiva** sobre o baseline congelado
> (partições: `landing`, `site`, `ambos`) + **asserção de ausência** no cruzamento

A pergunta que esta regra responde é a difícil: *como falsificar "migrou" sem reafirmar o
resultado?* Resposta em três movimentos — **contagem** (o baseline é exaustivo e sua cardinalidade
vem do `00`), **destino único** (nenhuma seção sem destino, nenhuma com dois) e **ausência**
(o que migrou saiu da origem, senão não foi migração, foi cópia).

```gherkin
# language: pt

Funcionalidade: Migração da documentação dos READMEs para o site

  Regra: todo bloco de conteúdo do baseline chega a exatamente um destino, em cada idioma

    Cenário: [CT-01] @premissa nenhuma seção do baseline fica sem destino, e nenhuma tem dois
      Dado o baseline congelado com 32 títulos de segundo nível e 83 de terceiro
      E o mapa que classifica cada um como "landing", "site" ou "ambos"
      Quando o mantenedor confere o baseline contra a árvore publicada
      Então cada um dos 32 títulos aparece em exatamente um destino por idioma
      E a lista de títulos sem destino é vazia

    Esquema do Cenário: [CT-02] a página de destino carrega o conteúdo, não um esqueleto
      Dado a seção "<secao>" classificada como "site", de <linhas_origem> linhas no baseline
      Quando o leitor abre a página de destino no idioma "<idioma>"
      Então a página contém os <h3> títulos de terceiro nível que o baseline lhe atribuiu
      E a página tem ao menos <minimo> linhas

      Exemplos:
        | secao                | idioma | linhas_origem | h3 | minimo | # partição            |
        | Login social         | pt     | 384           | 12 | 300    | maior seção           |
        | Login social         | en     | 384           | 12 | 300    | maior seção, inglês   |
        | Import e export      | pt     | 190           | 6  | 150    | seção grande          |
        | Estudo Advanced Tables | pt   | 6             | 0  | 4      | menor seção migrada   |
        | Estudo Advanced Tables | en    | 6            | 0  | 4      | borda inferior, inglês |

    Cenário: [CT-03] @premissa nada que migrou continua no README
      Dado os 27 títulos do baseline classificados como "site"
      E os títulos de terceiro nível que as 5 seções "ambos" mandam migrar
      Quando o mantenedor lê os dois READMEs depois da migração
      Então a lista de títulos migrados que ainda aparecem em algum README é vazia
      E cada um deles aparece na sua página de destino, nos dois idiomas
```

> **CT-03 é exaustivo de propósito.** A revisão adversarial apontou a assimetria: CT-01 percorre
> o baseline inteiro na direção da **presença**, e a versão anterior deste cenário conferia a
> ausência em **1 seção de 32** — deixando o mutante M3 vivo em 31/32 do conteúdo, com um README
> truncado no tamanho certo e duplicando tudo. A ausência é a metade cara; ela recebe o mesmo
> percurso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | Uma seção fica pelo caminho: 21 páginas migradas em vez de 22, e ninguém conta | CT-01 (a lista de sem-destino deixa de ser vazia) |
| M2 | A página é criada com o título e o parágrafo de abertura, e o corpo fica para depois | CT-02 (linhas mínimas + os `h3` atribuídos) |
| M3 | O conteúdo é **copiado** para o site e permanece no README — a landing não encolhe e as duas cópias divergem sozinhas | CT-03 |
| M4 | O baseline é escrito **a partir do resultado** — 22 entradas, e tudo bate por construção | CT-01: a cardinalidade é conferida contra os **32 / 83** medidos no `00`, não contra a árvore |
| M5 | A migração roda só no português; o inglês recebe a estrutura e o conteúdo antigo | CT-02 (linhas `en`) — e, de forma independente, R2 |

---

## Regra R2 — `/pt/` e `/en/` têm o mesmo conjunto de páginas e a mesma estrutura

> `RQ-01`, `RQ-03` · perfil **padrão** · técnica: **comparação de conjunto nos dois sentidos** +
> razão de tamanho por par de páginas

O histórico do projeto é o argumento: **4 divergências conhecidas, todas por omissão no inglês**.
A comparação precisa ser simétrica — `en ⊆ pt` passa com página faltando no inglês, e contagem
total passa quando `pt` tem A e `en` tem B.

```gherkin
# language: pt

  Regra: as duas árvores de idioma têm exatamente as mesmas páginas

    Cenário: [CT-04] nenhum idioma tem página que o outro não tem
      Dado a árvore de páginas publicada em português
      E a árvore publicada em inglês
      Quando o mantenedor compara os dois conjuntos de caminhos relativos
      Então o conjunto de caminhos presentes só em português é vazio
      E o conjunto de caminhos presentes só em inglês é vazio

    Esquema do Cenário: [CT-05] @premissa nenhuma página inglesa é um resumo da portuguesa
      Dado a página "<pagina>" nos dois idiomas
      Quando o mantenedor compara título a título e tamanho a tamanho
      Então as duas têm a mesma quantidade de títulos de segundo e terceiro nível
      E o tamanho da inglesa fica dentro de 10% do tamanho da portuguesa

      Exemplos:
        | pagina                              | # partição                          |
        | autenticacao/login-social           | maior página do site                |
        | operacao/convencoes-do-kit          | página curta, onde 10% são 3 linhas |
        | referencia/estudo-advanced-tables   | página mínima, piso de 5 linhas     |

    Cenário: [CT-19] a árvore inglesa é inglesa, e não uma cópia da portuguesa
      Dado todas as páginas publicadas em inglês
      Quando o mantenedor procura em cada uma marcador lexical de idioma
      Então toda página contém ao menos um marcador exclusivo do inglês
      E nenhuma página contém marcador exclusivo do português
```

> **CT-19 nasceu de um erro desta derivação.** A versão anterior afirmava que CT-06 mataria a
> cópia não traduzida "pelos tokens em inglês" — **falso**: os quatro tokens de CT-06
> (`getTabs()`, `export-ignore`, `F-06`, `travas-de-escalada-de-papeis`) são identificadores,
> **idênticos nos dois idiomas**. Um `cp -r docs/pt docs/en` com as poucas páginas amostradas
> traduzidas passava por CT-04 (conjuntos iguais), CT-05 (0% de divergência de tamanho) e CT-06
> (tokens presentes). Marcador lexical: partículas de altíssima frequência e sem colisão entre
> os dois idiomas — `the `/`with ` de um lado, ` não `/` que ` do outro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | A verificação de paridade é `en ⊆ pt` — passa com uma página inglesa faltando | CT-04 (o segundo `Então`, na direção inversa) |
| M7 | A paridade compara só a **contagem** de páginas: 22 e 22, com nomes diferentes | CT-04 (compara conjuntos, não cardinalidade) |
| M8 | A página inglesa nasce como `<!-- TODO: translate -->` mais o título | CT-05 (razão de tamanho) |
| M10 | `cp -r docs/pt docs/en` e traduz só as páginas que os `Exemplos` nomeiam | **CT-19**. Antes da revisão adversarial este mutante estava marcado como morto por CT-06, **incorretamente** — ver a nota do cenário |
| M9 | A tolerância é aplicada ao **total** dos dois idiomas, não por página | ⚠️ **sem matador** — nenhum cenário distingue uma implementação que mede o agregado de uma que mede cada página, porque ambas passam num conjunto correto. É lacuna **de forma**, não de comportamento: mitigada por o `Esquema` nomear a página em cada linha, e declarada aqui para não parecer coberta |

---

## Regra R3 — As 4 divergências PT/EN conhecidas não sobrevivem à migração

> `RQ-03` · perfil **padrão, técnica escalada** · técnica: **regressão nomeada por token estável**

O problema de escrever paridade de prosa é congelar tradução. A saída é afirmar sobre o que a
divergência **carrega** e que não muda com a redação: identificadores, caminhos e nomes de método.
Os quatro tokens abaixo saem do `90-mapa-do-conteudo.md` como **fato medido do conteúdo de
origem** — não como texto escolhido pelo plano.

E a **ordem importa**: o `01` corrige as divergências no README (passo 5) e só depois migra (passo
4). Se o CT afirmasse sobre o README, ele morreria quando o README encolhe. Por isso o `Então` é
sempre sobre a **página de destino em inglês**.

```gherkin
# language: pt

  Regra: as omissões conhecidas do inglês são fechadas e chegam ao site

    Esquema do Cenário: [CT-06] cada omissão conhecida do inglês chega à página de destino
      Dado a divergência "<divergencia>", registrada como omissão no inglês
      Quando o leitor abre a página "<pagina>" em inglês
      Então a página contém "<token>"
      E a página portuguesa equivalente contém o mesmo token

      Exemplos:
        | divergencia                  | pagina                            | token                          | # origem |
        | vínculo de convite ×  social | autenticacao/login-social         | travas-de-escalada-de-papeis   | 90 §3.1  |
        | onde ficam as ADRs do kit    | operacao/agentes-de-ia            | export-ignore                  | 90 §3.2  |
        | convenção de abas            | operacao/convencoes-do-kit        | getTabs()                      | 90 §3.3  |
        | F-06 volta por login social  | operacao/roteiro-de-features      | F-06                           | 90 §3.4  |
```

> O token de F-06 é fraco de propósito — `F-06` existe na tabela mesmo com a cláusula omitida. A
> linha correspondente precisa de um segundo `Então` sobre a **célula**: a linha `F-06` da tabela
> inglesa menciona login social. Está escrito assim no cenário porque um token que não discrimina
> é pior que lacuna declarada; ver M13.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | O passo 5 corrige o README e o passo 4 migra o inglês de uma cópia anterior à correção | CT-06 (afirma sobre o destino, não sobre a origem) |
| M12 | O token migra, mas para a página errada — o parágrafo de vínculo cai em `convites` em vez de `login-social` | CT-06 (o cenário nomeia o arquivo) |
| M13 | A linha `F-06` chega sem a cláusula de login social | CT-06 **só se** o segundo `Então` da linha F-06 afirmar sobre a célula. Escrito como token simples, **não mata** — a nota acima é a instrução de escrita, e o teste que a ignorar reintroduz o mutante |
| M14 | A correção é feita só no README e o site nunca a recebe (README e site divergem no dia 1) | CT-06 (`Então` no destino) + CT-03 |

---

## Regra R4 — A rede de asserções continua vigiando o texto onde ele passou a morar

> `RQ-03` + Restrição Herdada 2 · perfil **completo** · técnica: **rastreio de efeito**, com o
> "efeito" sendo a própria asserção: ela existe / não virou vácuo / não mudou de alvo

Esta é a regra que carrega o risco central da entrega, e ela tem uma assimetria que precisa ser
dita antes dos cenários:

| Tipo de asserção | O que acontece quando o texto migra | Risco |
|---|---|---|
| **presença** (`toContain`) — a maioria | fica **vermelha** | baixo: é barulhenta e se conserta sozinha. O risco é o conserto ser **apagar** |
| **ausência** (`not->toContain`) — 6 sítios de código sobre os READMEs, que os datasets multiplicam por idioma | fica **verde e vazia** | **alto e silencioso**: o README encolhido não contém mais nada sobre o assunto, então proibir um literal ali passa por construção — enquanto a página migrada pode carregar a promessa proibida à vontade |

O segundo caso é a perda silenciosa em forma pura. Exemplo concreto e verificável:
`ConfiguracoesDoKitDocumentacaoTest.php:127` proíbe `public/images/auth/login.svg` no README;
depois da migração o assunto vive em `docs/**/configuracoes-do-kit.md`, o README nunca mais o
menciona, e a asserção **continua verde para sempre**, guardando um arquivo que não guarda nada.
Vale igual para as três de `AnexosPrivadosDocumentacaoTest.php:36-38`, que vigiam uma afirmação de
**segurança** (o disco de mídia não ser público).

O kit **já conhece** este modo de falha e o escreveu: `ConfiguracoesDoKitDocumentacaoTest.php:63`
— *"asserção só de AUSÊNCIA passa num README que apaga a linha"*. O que muda agora é que a
migração dispara essa condição em **todas** de uma vez.

```gherkin
# language: pt

  Regra: nenhuma das asserções de documentação perde o alvo na migração

    Cenário: [CT-07] o inventário do que é vigiado não encolhe nem troca de conteúdo
      Dado o inventário dos literais que a documentação é obrigada a conter ou a não conter
      Quando o mantenedor confere o inventário depois da migração
      Então o inventário tem ao menos 79 entradas
      E ele contém nominalmente a guarda do disco de mídia e a do segredo do provedor
      E toda entrada aponta para um arquivo que existe

    Esquema do Cenário: [CT-08] nenhuma asserção de ausência fica sem assunto para vigiar
      Dado que o documento "<documento>" é proibido de conter "<proibido>"
      Quando o mantenedor confere se esse documento ainda trata do assunto
      Então o documento contém a âncora "<ancora>"
      E o documento não contém "<proibido>"

      Exemplos:
        | documento                             | proibido                    | ancora            | # origem            |
        | docs/pt/recursos/anexos-e-midia       | `public` por padrão         | MEDIA_DISK        | Anexos:36-38        |
        | docs/en/recursos/anexos-e-midia       | `public` by default         | MEDIA_DISK        | Anexos:36-38, en    |
        | docs/pt/recursos/configuracoes-do-kit | public/images/auth/login.svg | mostra o nome     | Configuracoes:127   |
        | docs/en/recursos/configuracoes-do-kit | public/images/auth/login.svg | shows the application name | Configuracoes:127, en |
        | docs/pt/autenticacao/login-social     | /auth/linkedin/callback     | socialiteproviders | Provedores:1646    |

    Cenário: [CT-09] o nome e o motivo continuam na mesma seção
      Dado que a documentação precisa explicar a recusa de Discord onde o nomeia
      Quando o mantenedor decompõe as páginas do site por título
      Então ao menos uma seção nomeia Discord
      E toda seção que nomeia Discord traz o motivo da recusa na mesma seção

    Cenário: [CT-10] a sentinela ignora fora do kit e executa dentro dele
      Dado a árvore do repositório do kit, onde o diretório de documentação existe
      Quando o desenvolvedor roda a suíte do kit
      Então nenhum dos cenários de documentação do site é ignorado
      E a suíte executa ao menos uma asserção sobre o diretório de documentação
```

> **CT-09**: a granularidade é **seção**, não página. Afirmar sobre a página deixa passar o nome
> no topo e o motivo trezentas linhas abaixo — e o `Então` de existência (`ao menos uma seção
> nomeia Discord`) é o que impede a versão vácua, em que nenhuma página menciona Discord e a
> co-localização passa por vazio.
>
> **CT-10 afirma o ramo positivo de propósito, e este é o achado mais perigoso da revisão
> adversarial.** A sentinela óbvia seria `is_dir(base_path('docs'))` — e ela é **auto-anulante**:
> se a migração inteira não acontecer, `docs/` não existe, os cenários são todos ignorados e
> `composer test:kit` fica verde com zero entrega. O guard só pode se apoiar em algo que existe
> **independentemente desta feature**, e por isso é `.github`. O `Então` deste cenário é o que
> impede a troca silenciosa de uma sentinela pela outra.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | A asserção fica vermelha na migração e alguém a **apaga** para destravar o commit | CT-07 (piso de 79, escrito com o número literal do `00`) |
| M16 | A asserção de **ausência** é mantida apontando para o README e vira vácuo | CT-08 (a âncora de presença no mesmo documento) |
| M17 | A asserção é reapontada para o arquivo errado — `docs/pt/...` no dataset do inglês | CT-08 (linhas `en` com âncora em inglês: `MEDIA_DISK` casa nos dois, `shows the application name` não) |
| M18 | A verificação de co-localização é reescrita concatenando `docs/**`: nome numa página, motivo em outra, e passa | CT-09 (nomeia uma única página; e o segundo `Então`) |
| M19 | Os cenários novos varrem `docs/`, que é `export-ignore` — **toda instalação** fica vermelha em `composer test:kit` | CT-10 (ramo de fora do kit) |
| M40 | A sentinela vira `is_dir('docs')`: se a entrega inteira não acontecer, tudo é ignorado e a suíte fica verde | **CT-10** (ramo positivo) — mutante trazido pela revisão adversarial |
| M41 | O inventário mantém 79 entradas trocando as guardas de segurança por 79 asserções triviais novas | **CT-07** (segundo `Então`, nominal) — revisão adversarial: `client_secret` sustentava o perfil `completo` e não estava em nenhum dataset |
| M42 | A co-localização é afirmada por página: nome no topo, motivo 300 linhas abaixo | **CT-09** — revisão adversarial |
| M20 | O inventário é editado para baixo junto com a asserção removida | ⚠️ **sem matador automático**. Tentado: derivar a contagem do próprio código dos testes por `token_get_all()`, como faz `HelpersDeTesteTest`; isso conta chamadas, não **guardas efetivas**, e um dataset esvaziado continua com a chamada de pé. O que resta é o número **79 literal** em CT-07, que obriga quem reduz a editá-lo — visível em revisão de diff. Lacuna declarada |
| M21 | A migração das asserções acontece num commit **posterior** ao da migração do conteúdo, e a janela entre os dois fica sem guarda | ⚠️ **sem matador** — o conjunto é executado no estado final, não na sequência de commits. É restrição de processo (`01`, ADR-05: "no mesmo commit"), não de comportamento. Declarada |

---

## Regra R5 — Nada da documentação do site vaza para o projeto instalado

> `RQ-01` + Restrições Herdadas 1 e 3 · perfil **padrão** · técnica: **atributo de exportação em
> caminho aninhado** + unicidade de dependência

Duas fronteiras diferentes e simétricas: o **peso** (uma dependência de documentação na raiz é
baixada por todo projeto instalado, para sempre) e o **conteúdo** (`docs/` no dist).

```gherkin
# language: pt

  Regra: o pacote distribuído não carrega a documentação nem as dependências dela

    Esquema do Cenário: [CT-11] o diretório de documentação fica fora do pacote distribuído
      Dado o arquivo "<caminho>" versionado no repositório
      Quando o empacotador consulta o atributo de exportação desse caminho
      Então o resultado é "<ignorado>"

      Exemplos:
        | caminho                                    | ignorado | # partição            |
        | docs/package.json                          | sim      | raiz de docs          |
        | docs/.vitepress/config.ts                  | sim      | um nível abaixo       |
        | docs/pt/comecar/instalacao-avancada.md     | sim      | três níveis           |
        | README.md                                  | não      | **controle negativo** |
        | art/install.gif                            | não      | **controle negativo** |

    Cenário: [CT-12] a dependência do gerador existe uma vez, e do lado certo
      Dado o manifesto de pacotes da raiz do projeto
      E o manifesto próprio do diretório de documentação
      Quando o mantenedor procura a dependência do gerador do site
      Então ela está declarada como dependência de desenvolvimento no manifesto da documentação
      E o conjunto de dependências da raiz é idêntico ao de antes da migração
      E os scripts "build" e "dev" da raiz continuam existindo
```

> **"Aparece no manifesto" é presença de string** — o campo `name` ou um comentário bastariam, e
> nada impediria *outra* dependência de documentação na raiz. Por isso o oráculo é o **conjunto**
> de dependências da raiz, comparado com o estado anterior: é o que torna "a raiz não foi tocada"
> falsificável sem congelar o arquivo inteiro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | O padrão de exportação é escrito de forma que não alcança subdiretório (`/docs/*.md`) | CT-11 (a linha de três níveis; uma comparação de texto no arquivo de atributos **não** mataria) |
| M23 | A dependência do gerador é declarada na raiz — todo projeto instalado passa a baixá-la | CT-12 (segundo `Então`) |
| M24 | O diretório de documentação não ganha manifesto próprio e nada instala | CT-12 (primeiro `Então`) |
| M25 | Ao editar a raiz para "não tocar", alguém remove os scripts `build`/`dev` e quebra o projeto instalado | CT-12 (terceiro `Então`) |
| M26 | `docs/` é adicionado à lista de caminhos do `kit:update`, entregando pelo comando o que o dist retém | **matador herdado**: `tests/Kit/KitUpdateTest.php:204-216` já reprova a interseção entre a lista e os caminhos ignorados na exportação. Nenhum cenário novo — reuso declarado |

---

## Regra R6 — O README encolhe nos dois idiomas e todo link que ele passa a carregar resolve

> `RQ-02` · área **mínimo**, elevada a **padrão** porque a regra tem duas metades independentes ·
> técnica: **BVA de dois lados** + resolução de link

O pedido do caller — *afirmar que encolheu sem congelar um número que muda a cada edição* — se
resolve trocando **constante** por **razão contra um fato histórico**: as 2.522 e 2.533 linhas
estão medidas no `00` e nunca mudam, porque são a medição de antes. Edição legítima da landing
move o numerador, não o denominador.

E o BVA precisa dos **dois lados**. Só teto deixa passar "README reduzido a um link", que é
exatamente o cenário que a ambiguidade de RQ-03 assumiu **contra**.

```gherkin
# language: pt

  Regra: o README vira landing, nos dois idiomas, e aponta para páginas que existem

    Esquema do Cenário: [CT-13] @premissa a landing encolhe sem se esvaziar
      Dado o README em "<idioma>", que tinha <antes> linhas antes da migração
      Quando o mantenedor mede o arquivo depois da migração
      Então ele tem no máximo <teto> linhas
      E ele tem ao menos 100 linhas
      E ele contém a instrução de instalação, os requisitos e o link do site
      E a diferença de tamanho para o outro idioma é de no máximo 5%

      Exemplos:
        | idioma    | antes | teto | # lado             |
        | português | 2522  | 756  | teto e piso        |
        | inglês    | 2533  | 759  | paridade de encolhimento |

    Cenário: [CT-14] o README leva ao site, e todo link que ele carrega resolve
      Dado o README depois da migração
      Quando o mantenedor resolve cada link do site para o arquivo correspondente
      Então o README carrega ao menos um link por seção migrada
      E a lista de links que não resolvem para um arquivo existente é vazia
```

> **CT-14 precisa do piso, e é a lição mais barata da revisão adversarial**: "todo link resolve"
> é um quantificador universal, e universal sobre **conjunto vazio é verdadeiro**. Um README de
> 120 linhas sem link nenhum passava — 44 páginas escritas, ninguém chega a elas, RQ-02 morta com
> tudo verde. O piso por seção migrada é o que fecha.
>
> **CT-13 ganhou a terceira asserção pela mesma razão**: contar linhas não mede o que M28 diz
> medir. Um README truncado no meio de uma frase tem 750 linhas e não ensina a instalar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | O português encolhe e o inglês fica com 2.533 linhas — o modo de falha **documentado** deste projeto | CT-13 (linha `inglês` e o terceiro `Então`) |
| M28 | O README é reduzido a badges e um link para o site; quem chega pelo Packagist não vê mais como instalar | CT-13 (piso de 100) |
| M29 | Um link aponta para `/pt/recursos/anexos.md` enquanto o arquivo é `anexos-e-midia.md` | CT-14. **O build do gerador não pega isto**: ele valida links internos a `docs/`, e o README está fora |
| M30 | O teto é escrito como constante (`<= 340`) e a primeira edição legítima da landing derruba a suíte | prevenido **por desenho** — CT-13 usa razão sobre o valor histórico. Não é mutante de produto; fica registrado porque é o erro que o cenário existe para não cometer |

---

## Regra R7 — O site publica sozinho, no endereço certo, sem concorrer consigo mesmo

> `RQ-01`, `RQ-06` · perfil **padrão** · técnica: tabela de decisão do gatilho + **derivação** do
> endereço base

```gherkin
# language: pt

  Regra: a publicação é automática, serializada e no endereço do repositório

    Cenário: [CT-15] o fluxo publica o site, e não apenas o constrói
      Dado o fluxo de publicação da documentação no repositório
      Quando o mantenedor lê o fluxo de ponta a ponta
      Então o fluxo dispara em push no branch padrão e declara grupo de concorrência
      E ele instala as dependências e constrói dentro do diretório de documentação
      E ele sobe o resultado do build como artefato de páginas e o publica no ambiente do Pages

    Cenário: [CT-16] o endereço base do site é o do repositório, não o do pacote
      Dado que o pacote se chama "gsferro/starter-kit-easy"
      E que o repositório se chama "filament-starter-kit-easy"
      Quando o mantenedor lê o endereço base configurado no site
      Então ele é o nome do repositório, derivado da página inicial declarada no pacote
      E ele começa e termina com barra
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M31 | O fluxo não declara grupo de concorrência: dois pushes seguidos disparam dois deploys e o segundo publica o artefato do primeiro | CT-15 (segundo `Então`) |
| M32 | O endereço base é omitido: o site funciona em local e quebra publicado — **o único defeito desta feature invisível em desenvolvimento** | CT-16 |
| M33 | O endereço base é derivado do **nome do pacote** (`/starter-kit-easy/`), que difere do repositório (`/filament-starter-kit-easy/`) — armadilha real e medida: os dois nomes não batem neste projeto | CT-16 (o cenário fixa os dois nomes lado a lado, que é o valor discriminante) |
| M34 | O `npm ci` roda na raiz em vez de em `docs/`, instalando o front-end do kit e não o gerador | CT-15 (segundo `Então`) |
| M43 | O fluxo **constrói e não publica** — sem subir artefato, sem deploy, e o Pages segue em 404 como o `00` mediu. RQ-01 fica verde e o site não existe | **CT-15** (terceiro `Então`) — revisão adversarial: nenhum `Então` do conjunto anterior mencionava publicar |
| M44 | O `base` é escrito sem as barras (`filament-starter-kit-easy`) — satisfaz "é o nome do repositório" e quebra o site publicado | **CT-16** (segundo `Então`) — revisão adversarial |

---

## Regra R8 — O processo de atualização está escrito, nos dois idiomas

> `RQ-06` · perfil **padrão** · técnica: EP por idioma

RQ-06 é literal: *"o processo de atualização precisa estar definido e ser conhecido"*. "Definido"
é R7; **"conhecido" é esta regra**, e sem ela a cláusula fica órfã — o fluxo existe e ninguém sabe
que existe.

```gherkin
# language: pt

  Regra: quem mantém o kit descobre pela documentação como o site se atualiza

    Esquema do Cenário: [CT-17] cada idioma explica como a documentação é publicada
      Dado a documentação de manutenção do kit em "<idioma>"
      Quando o mantenedor procura como publicar uma alteração no site
      Então o texto diz que a publicação acontece no push para o branch padrão
      E o texto nomeia o comando de build local da documentação

      Exemplos:
        | idioma    |
        | português |
        | inglês    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M35 | O fluxo é criado e nada é escrito: o próximo mantenedor edita `docs/` e não sabe se publicou | CT-17 |
| M36 | O processo é documentado só em português — a mesma omissão que gerou as 4 divergências | CT-17 (linha `inglês`) |

---

## Regra R9 — A migração não injeta sintaxe que o gerador interpreta como código

> `RQ-03` · perfil **padrão** · técnica: **texto livre / caractere especial**

O `01` mediu **0 ocorrências** de interpolação fora de bloco de código nos READMEs de hoje. Essa
medição vale para a **origem**; a migração reescreve 4.360 linhas, e é durante a reescrita que a
chave dupla aparece — num exemplo de Blade colado sem cerca, num trecho de config. O modo de falha
é build vermelho ou, pior, texto que some da página renderizada.

```gherkin
# language: pt

  Regra: nenhuma página do site contém interpolação fora de bloco de código

    Cenário: [CT-18] a migração não deixa chave dupla solta no texto
      Dado todas as páginas publicadas nos dois idiomas
      E um texto de controle com chave dupla fora de cerca e outro com ela dentro
      Quando o mantenedor aplica o filtro de código a todos eles
      Então o controle de fora da cerca é acusado e o de dentro não é
      E nenhuma página publicada é acusada
```

> **O controle positivo não é zelo, é o que faz o cenário poder reprovar.** A revisão adversarial
> apontou que o oráculo de CT-18 mora no `Quando` — no filtro de cercas. Um filtro guloso demais
> apaga a página inteira, o `Então` fica vacuamente verdadeiro e o cenário nunca acusa nada,
> qualquer que seja o conteúdo migrado. Os dois controles medem o filtro antes de o filtro medir
> as páginas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M37 | Um exemplo de Blade migra sem cerca de código e o gerador o interpreta | CT-18 |
| M38 | A verificação roda só no português | CT-18 (o `Dado` é sobre os dois idiomas) |
| M39 | O filtro de código remove só as cercas triplas e ignora o código em linha, gerando falso positivo que faz alguém desligar o cenário | CT-18 (o `Quando` remove os dois) |

---

## Regra R10 — Toda página publicada é alcançável

> `RQ-02` · perfil **padrão** · técnica: alcançabilidade sobre o conjunto de páginas
> **Origem: revisão adversarial.** RQ-02 estava formalmente coberta (R1 migra, R6 encolhe) e
> materialmente descoberta: 44 páginas escritas com `nav` e `sidebar` vazios satisfazem tudo o que
> o conjunto anterior afirmava. "Mais fácil a leitura" morre com página órfã.

```gherkin
# language: pt

  Regra: nenhuma página publicada fica fora da navegação do seu idioma

    Cenário: [CT-20] toda página aparece na navegação do idioma a que pertence
      Dado o conjunto de páginas publicadas em cada idioma
      Quando o mantenedor confronta esse conjunto com a navegação configurada
      Então toda página está referenciada na navegação do seu idioma
      E a navegação não referencia nenhuma página inexistente
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M45 | As 44 páginas são escritas e a navegação fica vazia — o site publica e ninguém acha nada | CT-20 |
| M46 | A navegação é escrita à mão e envelhece: aponta para uma página renomeada | CT-20 (segundo `Então`) |
| M47 | A navegação do inglês reaproveita os caminhos do português | CT-20 (o `Então` é por idioma) — e CT-04 |

---

## Regra R11 — A mídia é imagem e GIF, e não duplica o peso do repositório

> `RQ-05` + Restrições Herdadas 4 e 5 · perfil **padrão** · técnica: EP sobre tipo de artefato
> **Origem: revisão adversarial.** RQ-05 estava dispensada com o argumento "não há vídeo a
> proibir" — que é falso como argumento de teste: a restrição **é** falsificável em arquivo, e
> nada no conjunto anterior impedia um `<video>`, um embed, a cópia dos 8,5 MB de `art/` para
> dentro de `docs/`, ou um ponteiro de LFS que o Pages não serve. O `00` diz, sobre estas duas
> restrições herdadas, que um plano que as ignore "produz entrega quebrada".

```gherkin
# language: pt

  Regra: o site usa imagem e GIF, sem duplicar mídia nem versionar arquivo que o Pages não serve

    Cenário: [CT-21] a documentação não carrega vídeo, cópia de mídia nem ponteiro de LFS
      Dado todas as páginas e todos os arquivos sob o diretório de documentação
      Quando o mantenedor classifica cada referência de mídia e cada arquivo binário
      Então nenhuma página referencia vídeo ou embed de vídeo
      E nenhum binário do diretório de arte aparece duplicado sob a documentação
      E nenhum arquivo da documentação é um ponteiro de armazenamento grande
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M48 | Alguém acha que o site "merece" um vídeo de demonstração e embute um — o solicitante tirou vídeo de escopo na mensagem 2 | CT-21 (primeiro `Então`) |
| M49 | `art/` é copiado para `docs/public/`, dobrando 8,5 MB no repositório | CT-21 (segundo `Então`) |
| M50 | Uma imagem nova entra por LFS e o Pages entrega o arquivo-ponteiro — imagem quebrada só em produção | CT-21 (terceiro `Então`) |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi
> tentado}`. Nunca "sim". A tabela padrão da skill supõe código de aplicação; onde ela não tem
> tradução para artefato de arquivo, o motivo está escrito.

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: não há rota, recurso por identificador nem sessão. O `01` declara `Autorização: nenhuma` |
| Autorização exercida na ação | não se aplica: o site é público e estático |
| Idempotência (ancorada no agregado) | não se aplica: o agregado seria o artefato publicado, e o deploy do Pages **substitui** o artefato inteiro — não há acumulação a duplicar. Ancorar em qualquer outra coisa produziria o cenário tautológico que a skill proíbe |
| Concorrência | CT-15 — dois pushes simultâneos, serializados pelo grupo de concorrência |
| Fronteira no ponto de entrada (gravação) | CT-11, CT-12 — o "ponto de entrada" aqui é o dist do `create-project` e o `kit:update` |
| Domínio condicionado (tipo × valor) | não se aplica: nenhum campo cujo domínio dependa de outro |
| Estado × operação de escrita | não se aplica: não há entidade com ciclo de vida. A aproximação mais próxima é seção × destino, coberta exaustivamente por CT-01 |
| Ausente ≠ null ≠ vazio | CT-02 — a distinção que importa é **página ausente** (CT-04) × **página vazia** (CT-02) × página completa. As três estão separadas |
| Paginação / ordenação | não se aplica: não há listagem consultável pela suíte |
| Timezone / DST | não se aplica: nenhum comportamento dependente de instante |
| Unicode / limite de varchar | CT-18 — o caractere especial que importa neste meio não é acento nem emoji, é a chave dupla que o gerador interpreta |
| Unicidade + soft delete | não se aplica: sem banco |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: sem payload |
| Upload | CT-21 — o análogo aqui é **mídia entrando no repositório**: tipo proibido (vídeo), peso duplicado e ponteiro de LFS que o Pages não serve |
| Precisão monetária | não se aplica |
| **Alcançabilidade** (linha nova, trazida pela revisão adversarial) | CT-20 — artefato que existe em disco e não é alcançável pela navegação é o "criado mas nunca usado" deste meio |
| **Controle positivo / negativo do próprio oráculo** (linha nova) | CT-11 (controles negativos), CT-18 (controles dos dois lados da cerca), CT-10 (ramo positivo da sentinela). Sempre que o oráculo depende de um **filtro** ou de um **guard**, o filtro precisa ser medido antes de medir |
| **Criação ≠ edição ≠ uso** (a linha que mais rende) | **criação**: CT-01, CT-02 (a página nasce completa). **edição**: CT-03 — as 5 seções `ambos` deixam **duas** cópias do assunto, e é na edição posterior que elas divergem; CT-03 é o que impede a segunda cópia de nascer. **uso**: CT-14 (o leitor chega pelo link do README) |
| **Rastreio de efeito, aplicado a asserção** | CT-07 (existe), CT-08 (não virou vácuo), CT-09 (não mudou de alvo), CT-10 (não quebra fora do kit) |

---

## Fora do alcance da suíte PHP — e por quê

Isto não é lista de escopo, é a lista do que **fica sem rede** e precisa de outro dono. Escrever
"verificado" sem ela é o falso ✅ que a skill combate.

| Afirmação | Por que a suíte não alcança | Quem verifica |
|---|---|---|
| O build da documentação passa | O job PHP do `.github/workflows/ci.yml:42` roda sem `setup-node` — **medido**. Adicionar Node ali para provar o que o fluxo de publicação já prova é infraestrutura nova por nada | O próprio `docs.yml`, que é gatilho de merge |
| Nenhum link **interno a `docs/`** está morto | mesma razão | O build |
| As imagens de `art/` **renderizam** no site (RQ-05) | CT-21 prova que a mídia não foi duplicada nem versionada em LFS; que a imagem **carrega** depende do build e do `publicDir`, cuja alternativa vencedora o `01` deixou em aberto | Verificação manual, item 2 abaixo |
| O `base` está certo **em produção** | quebra publicado e passa em local, por definição. CT-16 prova que a configuração é coerente, não que a publicação funcionou | Verificação manual, item 1 |
| A busca local funciona | é JavaScript rodando no site publicado; nenhum arnês deste projeto o alcança (ver `## Gate de CT-B`) | Verificação manual, item 3 |

### Verificação manual — três itens, com evidência nomeada

Não é "conferir o site". É esta lista, e cada linha pede uma evidência:

1. Abrir `https://gsferro.github.io/filament-starter-kit-easy/pt/` e navegar até uma página de
   terceiro nível — evidência: a URL contém o nome do repositório e a página renderiza.
2. Numa página que tenha captura de tela, confirmar que a imagem carrega — evidência: a imagem
   aparece, e o endereço dela é o que a alternativa vencedora do `01` previu.
3. Buscar por um termo que só existe em uma página (`getTabs`) — evidência: o resultado leva à
   página de convenções, nos dois idiomas.

---

## Gate de CT-B

**Concordo com a conclusão do `01`: sem CT-B.** E contesto o argumento.

**Onde o `01` está certo.** O `pest-plugin-browser` sobe um servidor HTTP in-process que serve a
**aplicação Laravel** — `tests/Pest.php:111-114` e o bloco de comentário nas linhas 96-109. O site
compilado vive em `docs/.vitepress/dist`, fora de `public/`. Fazer um CT-B exigiria copiar o `dist`
para dentro de `public/` durante o teste **e** ter Node no job que hoje não tem, para provar o que
o build já prova. Pela escada do Ponytail, isso não passa do primeiro degrau: o cenário não precisa
existir.

**Onde o `01` está errado, e importa.** A frase que fecha o gate — *"o que se afirma é tudo
verificável em arquivo"* — é falsa em dois pontos, e é o tipo de frase que faz a próxima pessoa
parar de procurar:

1. **O `base` correto não é verificável em arquivo.** CT-16 prova que a configuração é *coerente
   com o repositório*; ele não prova que o site publicado resolve seus próprios ativos. O `01`
   sabe disso — escreveu "conferir o site publicado, não só o build" no passo 9 — e mesmo assim
   fechou o gate dizendo que tudo é verificável em arquivo. As duas frases não podem estar certas.
2. **A busca local não é verificável em arquivo.** Ela é a metade de RQ-02 que justifica o site
   existir ("mais fácil a leitura"), é JavaScript, e nenhuma linha desta wiki a toca.

**Consequência**: o gate se sustenta, a justificativa não. O correto é `sem CT-B` **porque o
arnês não alcança o artefato**, e não porque não há nada a provar no navegador — e o que sobra
vira a lista de verificação manual acima, com evidência nomeada. Gate fechado com a justificativa
errada é como uma lacuna vira ✅ sem ninguém perceber.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Servir `docs/.vitepress/dist` por `public/docs` e visitar no navegador da suíte | exige Node no job PHP; prova o que o build já prova; e o `base` de produção continuaria sem cobertura |
| `assertNoJavaScriptErrors()` na home do site | assertion de apoio como oráculo único — a skill a proíbe, e uma página em branco passaria |
| Screenshot da home nos dois temas | não há tema customizado (decisão do `01`); nenhum mutante previsto morre |
| Visitar o site publicado por HTTP a partir do teste | teste dependente de rede e do estado do GitHub; falha por motivo que não é o dele |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | Nenhuma seção sem destino, nenhuma com dois | R1 | EP exaustiva | Kit | `tests/Kit/SiteDeDocumentacaoTest.php` | M1, M4 |
| CT-02 | A página de destino não é esqueleto | R1 | EP + borda de tamanho | Kit | idem | M2, M5 |
| CT-03 | Nada que migrou continua no README (exaustivo) | R1 | ausência exaustiva | Kit | idem | M3 |
| CT-04 | Conjuntos de páginas idênticos nos dois sentidos | R2 | conjunto simétrico | Kit | idem | M6, M7 |
| CT-05 | Nenhuma página inglesa é resumo da portuguesa | R2 | razão por página | Kit | idem | M8 |
| CT-06 | As 4 divergências chegam ao destino em inglês | R3 | regressão por token | Kit | idem | M11, M12, M14, M10 |
| CT-07 | O inventário não encolhe nem troca de conteúdo | R4 | rastreio de efeito | Kit | `tests/Kit/RedeDeDocumentacaoTest.php` | M15, M41 |
| CT-08 | Ausência sempre acompanhada de âncora | R4 | rastreio de efeito | Kit | idem | M16, M17 |
| CT-09 | Nome e motivo na mesma seção | R4 | co-localização | Kit | idem | M18, M42 |
| CT-10 | A sentinela ignora fora do kit e executa dentro | R4 | decisão de dois lados | Kit | idem | M19, M40 |
| CT-11 | `docs/` fora do dist, com controle negativo | R5 | atributo de exportação | Kit | `tests/Kit/SiteDeDocumentacaoTest.php` | M22 |
| CT-12 | Dependência do gerador uma vez, do lado certo | R5 | unicidade | Kit | idem | M23, M24, M25 |
| CT-13 | A landing encolhe sem se esvaziar, nos dois idiomas | R6 | BVA de dois lados | Kit | idem | M27, M28 |
| CT-14 | O README leva ao site, e os links resolvem | R6 | resolução + piso | Kit | idem | M29 |
| CT-15 | O fluxo publica, e não apenas constrói | R7 | rastreio de efeito | Kit | idem | M31, M34, M43 |
| CT-16 | Endereço base derivado do repositório | R7 | derivação + formato | Kit | idem | M32, M33, M44 |
| CT-17 | O processo de atualização está escrito nos dois idiomas | R8 | EP por idioma | Kit | idem | M35, M36 |
| CT-18 | Sem interpolação solta nas páginas | R9 | texto livre + controle positivo | Kit | idem | M37, M38, M39 |
| CT-19 | A árvore inglesa é inglesa | R2 | marcador lexical | Kit | idem | M10 |
| CT-20 | Toda página é alcançável pela navegação | R10 | alcançabilidade | Kit | idem | M45, M46, M47 |
| CT-21 | Sem vídeo, sem mídia duplicada, sem LFS | R11 | EP por tipo de artefato | Kit | idem | M48, M49, M50 |

**Mutantes sem matador**: M9 (tolerância agregada × por página), M13 (token de F-06 fraco por
construção), M20 (inventário editado para baixo), M21 (janela entre commits). Os quatro estão
declarados na sua regra, com o que foi tentado.

---

## Fechamento do Ciclo — por que não há mutation score aqui

`pest --mutate` **não tem o que mutar**: esta feature não produz uma linha de código de aplicação.
Não é falha de configuração, é a consequência que a própria skill descreve — *"mutation testing só
muta código que existe"*. Um score de 100% aqui significaria zero mutantes gerados, e reportá-lo
como qualidade seria o falso ✅ mais caro possível.

O que substitui, e é o único indicador desta wiki:

1. **O gate de mutantes de especificação** (50 previstos, 46 com matador nomeado, 4 declarados) —
   ele nasce do requisito e por isso enxerga **omissão**, que é a classe de defeito desta entrega.
2. **A rastreabilidade `RQ` → regra**, no `## Mapa de Regras`, com as duas dispensas escritas
   (RQ-04, RQ-05).
3. Depois de implementar: rodar `php artisan test --testsuite=Kit,Tenancy --compact` e conferir
   que **os 21 CT existem como teste real** — a coluna "Arquivo" do índice acima é o que fecha.

### Divergência entre skill e regra do projeto

A skill sugere `pest --parallel --tia` como padrão. Prevalece o que o projeto mediu
(`tests/Pest.php:143` e o bloco de comentário acima): o TIA só liga com repositório git e é desligado em CI,
e `--parallel` não convive com a suíte de browser. Aqui isso é irrelevante — não há CT-B —, mas a
divergência fica declarada para o comando desta wiki ser o que o `01` já escreveu:
`php artisan test --testsuite=Kit,Tenancy --parallel --compact`.

---

## Revisão Adversarial

Executada por sub-agente independente, que recebeu **apenas** o `00-requisito.md` e este arquivo —
sem o `01`, sem o `90`, sem o código e sem o raciocínio da derivação. Contrato: provar que o
conjunto deixa passar um defeito; proibido elogiar ou reescrever.

**29 achados.** O saldo honesto: a revisão encontrou **uma afirmação factualmente errada**, duas
cláusulas materialmente descobertas e um guard auto-anulante que teria deixado a feature inteira
passar sem entrega. Nenhum deles seria encontrado por quem derivou — que é a razão de a skill
proibir autorrevisão.

### Os cinco achados estruturais

| # | Achado | O que virou |
|---|---|---|
| 1 | **A sentinela pode se autodesligar.** Guardar os cenários por `is_dir('docs')` faz a suíte ficar verde quando a migração **não acontece** — o guard só é verdadeiro depois que o entregável existe, então o teste prova a implementação com a própria implementação | **CT-10 reescrito** com o ramo positivo ("nenhum cenário é ignorado dentro do kit") + **M40**. É o achado mais perigoso da rodada |
| 2 | **`cp -r docs/pt docs/en` passava por tudo.** Os quatro tokens de CT-06 são identificadores idênticos nos dois idiomas — a afirmação anterior de que CT-06 mataria a cópia não traduzida "pelos tokens em inglês" era **falsa** | **CT-19 novo** (marcador lexical por página) + **M10 recolocado** com a correção escrita no cenário |
| 3 | **O fluxo podia construir e nunca publicar.** Nenhum `Então` do conjunto mencionava artefato, deploy ou Pages — com o `00` medindo `GET /pages → 404`. RQ-01 ficava verde com o site inexistente | **CT-15 ganhou o terceiro `Então`** + **M43** |
| 4 | **44 páginas órfãs passavam.** `nav`/`sidebar` vazios e um README sem link: CT-14 é universal sobre conjunto de links, e **universal sobre vazio é verdadeiro**. RQ-02 morria com tudo verde | **R10 e CT-20 novos** (alcançabilidade) + **piso de links em CT-14** |
| 5 | **A ausência de CT-03 cobria 1 seção de 32.** CT-01 percorre o baseline inteiro na presença; a ausência conferia só "Comandos" — M3 sobrevivia em 31/32, com README truncado no tamanho certo e tudo duplicado | **CT-03 virou exaustivo** sobre as 27 seções `site` e os `h3` das 5 `ambos` |

### Oráculos fracos, corrigidos

| Cenário | Fraqueza apontada | Correção |
|---|---|---|
| CT-07 | contagem sem identidade: 79 asserções triviais novas passam. E **`client_secret` — metade da justificativa do perfil `completo` — não estava em nenhum dataset** | `Então` nominal sobre as duas guardas de segurança + **M41** |
| CT-09 | co-localização em granularidade de **página**, quando a regra fala em **seção**; e ausência sem âncora (passava se ninguém mencionasse Discord) | granularidade de seção + `Então` de existência + **M42** |
| CT-11 | aferia o atributo sem controle negativo | duas linhas de **controle negativo** (`README.md`, `art/install.gif`) no `Esquema` |
| CT-12 | "aparece no manifesto" é presença de string; nada impedia outra dependência de doc na raiz | oráculo passou a ser o **conjunto** de dependências da raiz |
| CT-13 | só linhas — M28 dizia medir "quem vem do Packagist não vê como instalar" e o `Então` não media isso | terceiro `Então` sobre as âncoras da landing |
| CT-16 | não fixava a **forma**: `filament-starter-kit-easy` sem barras satisfazia e quebrava o site | `Então` sobre as barras + **M44** |
| CT-18 | **o oráculo mora no `Quando`** (o filtro de cercas): filtro guloso ⇒ `Então` vacuamente verdadeiro, cenário nunca reprova | dois **controles**, um de cada lado da cerca |

### Cláusulas descobertas

| # | Achado | O que virou |
|---|---|---|
| RQ-05 | dispensa **indevida**: "não há vídeo a proibir" não é argumento de teste — a restrição é falsificável em arquivo e nada impedia `<video>` ou embed | **R11 e CT-21 novos** |
| Herdadas 4 e 5 | sem regra e sem dispensa: nada impedia duplicar os 8,5 MB de `art/` sob `docs/`, nem versionar mídia em LFS que o Pages não serve | **CT-21**, mesmos `Então` |
| RQ-04 | dispensa **contraditória**: CT-01 congela 32 seções a partir de uma decisão que vive só no `01` — declarado não-oráculo — enquanto o `00` ainda diz "a ser confirmado" | ressalva escrita na `## Cobertura das RQ`; CT-01 e CT-03 sob `@premissa` |

### Achados registrados e **não** fechados

| # | Achado | Por que fica |
|---|---|---|
| 24 | **16 dos 18 cenários de então tinham `Quando` de inspeção** ("o mantenedor lê / confere / conta"): o estado não muda entre `Dado` e `Então`, então não há causa separada do efeito — é inspeção de estado com forma de Gherkin | **Procede, e é inerente ao objeto.** Uma feature sem runtime não tem ação a executar; o "sistema sob teste" é uma árvore de arquivos. Reescrever os `Quando` como ações inventadas seria teatro. Registrado como propriedade do conjunto, não como defeito a consertar |
| 9 | CT-04 passa com **duas árvores vazias** — não há piso de cardinalidade | Mitigado de lado: com `docs/` vazio, CT-01, CT-02, CT-14 e CT-20 reprovam. Um piso em CT-04 duplicaria o oráculo de CT-01. **Não fechado por desenho**, e escrito aqui para não parecer esquecimento |
| 11 | O segundo `Então` de CT-06 ("a portuguesa contém o mesmo token") não discrimina nada | Procede — é redundante, não errado. Mantido como âncora de simetria; **M13 continua declarado sem matador** |

### Segunda rodada

**Devida, e não executada.** O fechamento criou **superfície nova** — 3 cenários e 2 regras —, e a
skill manda re-revisar exatamente nesse caso: é na superfície nova que mora a lacuna de segunda
ordem. Fica como o único item aberto do gate, com o mesmo contrato e um sub-agente diferente.

### Checklist do gate — estado real

- [x] Toda regra declara ≥2 mutantes (≥3 nas de perfil completo)
- [x] Todo mutante tem cenário matador **ou** lacuna declarada com motivo (4 declaradas)
- [x] Cada cenário na camada mais barata que o arnês sustenta (`tests/Kit`, com o motivo medido)
- [x] Teto do perfil respeitado — R4 usa 4 cenários, e a skill autoriza: rastreio de efeito
      consome o teto inteiro
- [x] Revisão adversarial por sub-agente independente — **1 rodada, 29 achados, todos endereçados**
- [ ] **Segunda rodada — pendente**, devida porque o fechamento criou cenário e regra novos
