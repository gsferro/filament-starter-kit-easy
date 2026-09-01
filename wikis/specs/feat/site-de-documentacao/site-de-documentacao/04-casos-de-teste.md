# Casos de Teste — Site de documentação do pacote em GitHub Pages

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Mapa: `90-mapa-do-conteudo.md`
> Derivado do **requisito**. O `01` e o `90` entraram só para caminho, estrutura e a tabela
> `## Superfície de UI` — nenhum comportamento esperado saiu deles. O `02-decisoes-arquiteturais.md`
> foi lido **apenas** na revisão de 2026-09-01, e só para os fatos de plataforma que a troca de
> gerador trouxe (não há workflow; o build roda em `--safe`; não há plugin de i18n). ADR continua
> não sendo oráculo: nenhum `Então` deste arquivo saiu dela.

## Revisão de 2026-09-01 — o gerador mudou

**VitePress saiu; entrou o Jekyll embutido do GitHub Pages, no build nativo** (`Source: Deploy from
a branch → main → /docs`). **Não há workflow de Actions, não há npm, não há build local
obrigatório.**

O que a troca fez com este conjunto:

| Mudança de plataforma | Efeito nos casos de teste |
|---|---|
| **Não há workflow** | R7 mudou de alvo: de "o fluxo publica" para "o repositório está apontado para publicar, e ninguém reintroduziu um fluxo". Morreram **M31** (concorrência) e **M34** (`npm ci` no lugar errado) — sem Actions não há grupo de concorrência nem passo de instalação a errar. **M43 não morreu: mudou de forma**, e a forma nova é pior de detectar |
| **Não há gate de build** | O Jekyll **não falha em link morto** — nem no README, nem dentro de `docs/`. O portão que o plano anterior supunha **não existe em lugar nenhum**. CT-14 cresceu para cobrir os links internos do site, não só os do README |
| **Não há i18n** (build `--safe`, e o tema também não tem) | A paridade PT/EN deixa de ter qualquer apoio da plataforma e passa a ser sustentada **só por estes cenários**. A área subiu de perfil `padrão` para **`completo`** |
| **Navegação é manual**, em `_data/nav_pt.yml` e `_data/nav_en.yml` | Superfície nova, que não existia na derivação original: duas árvores escritas à mão que podem divergir entre si **e** do conteúdo. R10 cresceu |
| **Liquid processa `{{` e `{%` até dentro de bloco de código** | R9 ficou **mais simples e mais forte**: sem filtro de cercas, o oráculo perde o ponto onde podia se auto-anular |
| **Sem npm; `Gemfile` opcional e só em `docs/`** | R5 ganhou o `Gemfile` na raiz como alvo proibido; morreu **M24** (não há manifesto obrigatório em `docs/`) |
| **As imagens já são absolutas** (20 de 27, nenhuma relativa) | R11 trocou de eixo: o risco deixou de ser duplicar `art/` e passou a ser **introduzir caminho relativo** na migração |

O que **não** mudou, e é o núcleo do conjunto: a sentinela por `.github`, a assimetria
presença/ausência, a paridade nos dois sentidos com razão por página, `tests/Kit` como única
camada, o `git check-attr` em caminho aninhado com controles negativos, e os helpers indo para
`tests/Pest.php`.

## O que esta feature não é

Não há código de aplicação: nenhuma rota Laravel, model, policy, job ou migration. É **conteúdo,
configuração de publicação**. Depois da troca de gerador não há sequer workflow: o GitHub
constrói e publica sozinho a partir de `main:/docs`. Isso muda três coisas no pipeline, e as três estão
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
| Paridade PT/EN (páginas **e** navegação) | 3 | 3 | **9** | **completo** | P: o histórico do projeto já registra **4 divergências**, todas por omissão no inglês. **I subiu de 2 para 3 na revisão de 2026-09-01**: sem i18n no build e sem i18n no tema, nada além destes cenários acusa uma página ou uma entrada de menu faltando num idioma. Divergência deixou de ser retrabalho e virou entrega errada publicada |
| Fronteira do dist (`create-project`) | 2 | 3 | 6 | padrão | I: irreversível na prática — uma dependência de documentação na raiz é baixada por **todo** projeto instalado, "para sempre" (`00` → Restrições Herdadas, item 1) |
| Publicação (origem do Pages, `baseurl`) | 2 | 3 | 6 | padrão | **I subiu na revisão de 2026-09-01**: sem workflow, publicar deixou de ser um arquivo versionado e virou **configuração de repositório** — que nenhum teste alcança e que nenhum `git revert` conserta |
| README como landing e os links | 2 | 2 | 4 | padrão | **Subiu na revisão de 2026-09-01**: sem portão de build para link morto, o link quebrado passa a ser detectável só aqui. Não é mais só "edição de texto" |

As áreas **navegação** e **mídia** não aparecem como linhas próprias de propósito: R10 herda o
perfil da área **paridade** (é o mesmo risco, na superfície nova que a troca de gerador criou) e
R11 herda o da **fronteira do dist** (é peso e conteúdo entrando onde não devia). A 2ª revisão
adversarial apontou, com razão, que as duas apareciam com nome de área que a tabela não continha —
o que deixava duas regras com perfil declarado e risco nunca calculado.

- **Técnicas aplicadas**: EP exaustiva sobre baseline congelado, comparação de conjunto nos dois
  sentidos, BVA de dois lados (piso **e** teto), rastreio de efeito aplicado a **asserção** em vez
  de a e-mail, checklist de taxonomia adaptado a artefato de arquivo.
- **Cenários**: 25 · **Regras**: 11 · **Mutantes previstos**: 59 vivos (4 mortos pela troca de
  gerador, riscados e mantidos à vista) · **Sem matador**: 5 (declarados) — **M43′ é o mais grave**
- **Matadores efetivos deste conjunto**: 52. Os outros sete são: 5 declarados sem matador, **M30**
  (prevenido por desenho, não é mutante de produto) e **M26** (matador **herdado** de
  `KitUpdateTest.php:204-216`, teste que já existe). A distinção é da 2ª revisão adversarial, e
  importa: "com matador" e "com matador **aqui**" não são a mesma contagem
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
| **S**tructure | `docs/_config.yml`, `docs/index.md`, `docs/_data/nav_pt.yml`, `docs/_data/nav_en.yml`, 44 páginas `.md`, 2 `index.md` de idioma; `.gitattributes`; os dois READMEs; as 6 suítes de documentação. **Nenhum workflow, nenhum `package.json` em `docs/`, zero artefato Laravel** | CT-01, CT-11, CT-15, CT-20, CT-23, CT-25 |
| **F**unction | ler, navegar, buscar (função do leitor); publicar (função **do GitHub**, sem CI próprio); e a **função escondida**: o `create-project` deixar `docs/` de fora, e o `kit:update` não oferecer o que o dist retém | CT-11, CT-15 |
| **D**ata | 5.055 linhas de markdown de origem → 44 arquivos de destino. Dado grande: a maior seção tem **384 linhas** (Login social). Dado ausente: página que existe em `pt` e não em `en`. Dado duplicado: as 5 seções `ambos`, que ficam nos **dois** lados | CT-01…CT-06, CT-13 |
| **I**nterfaces | Nenhuma rota HTTP. As interfaces reais são: o **build do GitHub** (remoto, e **não é um portão** — não falha em link morto), o **`git archive`** do `create-project`, o **`kit:update`**, e o **Packagist**, que só lê o README e nunca vê o site. **A interface que sumiu é o build local**, e com ela o único gate automático que o plano anterior contava ter | CT-11, CT-12, CT-14 |
| **P**latform | Jekyll no build nativo do Pages, em `--safe`: **lista fechada de plugins, nenhum de i18n**; o tema também não tem. Sem Node, sem npm, sem Ruby na raiz. Liquid processa `{{` e `{%` **inclusive dentro de bloco de código**. Pages não serve Git LFS. Site em subdiretório exige `baseurl` | CT-15, CT-16, CT-18, CT-19, CT-20; e `## Fora do alcance` |
| **O**perations | Três públicos que não se cruzam: quem lê o site, quem descobre o kit pelo **Packagist** (nunca vê o site), e quem roda `create-project` (**não pode receber `docs/`**). Uso indevido previsível: alguém "consertar" uma asserção vermelha apagando-a | CT-07, CT-10, CT-14 |
| **T**ime | Ordem obrigatória: o passo 5 (corrigir as 4 divergências) vem **antes** do passo 4 (migrar) — invertido, a divergência é carregada para 44 arquivos. Deriva ao longo do tempo: as seções `ambos` passam a ter duas cópias que divergem sozinhas, e as duas árvores de navegação manuais também. **Concorrência saiu do mapa na revisão de 2026-09-01**: sem workflow não há execução paralela a serializar — o Pages enfileira sozinho | CT-03, CT-06, CT-20 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — Todo bloco de conteúdo do baseline chega a **exatamente um** destino, nos dois idiomas | migração (padrão) | RQ-02, RQ-03 | EP exaustiva sobre baseline congelado + asserção de ausência + baseline auto-verificável | CT-01, CT-02, CT-03, CT-24 |
| **R2** — `/pt/` e `/en/` têm o **mesmo conjunto** de páginas, a mesma estrutura e cada uma no seu idioma | paridade (**completo**) | RQ-01, RQ-03 | conjunto (diferença nos **dois** sentidos) + razão de tamanho por página + marcador lexical | CT-04, CT-05, CT-19 |
| **R3** — As 4 divergências PT/EN conhecidas não sobrevivem à migração | paridade (padrão, escalada) | RQ-03 | regressão nomeada por **token estável** | CT-06 |
| **R4** — A rede de asserções continua vigiando o texto **onde ele passou a morar** | rede (completo) | RQ-03 + Restrição Herdada 2 | rastreio de efeito aplicado a asserção (existe / não virou vácuo / não mudou de alvo) + **inspeção estática do arnês** em CT-10 | CT-07, CT-08, CT-09, CT-10 |
| **R5** — Nada da documentação do site vaza para o projeto instalado | dist (padrão) | RQ-01 + Restrições Herdadas 1 e 3 | atributo de exportação em caminho **aninhado** + unicidade de dependência | CT-11, CT-12 |
| **R6** — O README encolhe **nos dois idiomas**, e todo link — do README e entre as páginas — resolve | landing + links (padrão) | RQ-02 | BVA de dois lados (piso e teto) + resolução de link com piso | CT-13, CT-14, CT-22 |
| **R7** — O repositório está montado para o GitHub publicar, e no endereço certo | publicação (padrão) | RQ-01, RQ-06 | EP de estrutura + derivação do `baseurl` + controle positivo do detector | CT-15, CT-16, CT-25 |
| **R8** — O processo de atualização está **escrito**, nos dois idiomas | publicação (padrão) | RQ-06 | EP por idioma | CT-17 |
| **R9** — A migração não injeta sintaxe que o gerador interpreta como código | migração (padrão) | RQ-03 | texto livre / caractere especial | CT-18 |
| **R10** — Toda página é alcançável, e as duas árvores de navegação estão em paridade | paridade (**completo**) | RQ-02 | alcançabilidade com piso + conjunto simétrico + marcador lexical | CT-20, CT-23 |
| **R11** — A mídia é imagem e GIF, sem duplicar peso, sem LFS e sem caminho relativo | dist (padrão) | RQ-05 + Herdadas 4 e 5 | EP sobre tipo de artefato | CT-21 |

**Cobertura das `RQ`**: RQ-01 → R2, R5, R7 — mas **com uma ressalva que a troca de gerador criou**:
enquanto havia workflow, CT-15 afirmava a *publicação*. Agora afirma só a **estrutura publicável**;
a publicação em si virou **M43′**, sem matador, no item 0 da verificação manual. Marcar RQ-01 como
coberta sem essa frase seria cobertura ficcional — e foi exatamente o achado 3 da 1ª rodada, desfeito
pela plataforma nova.
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
| `baseurl: /filament-starter-kit-easy` | valor literal escolhido pelo plano | CT-16 **deriva** o valor do repositório real e recusa o literal |
| `remote_theme` e qual tema | escolha de implementação da ADR-01 | detalhe. Nenhum `Então` nomeia tema |
| `Source: main → /docs` | é o plano dizendo **onde** publicar | aceito como **dado**, recusado como oráculo do "está publicado": ver M43′ e `## Fora do alcance` |
| Os caminhos `docs/pt/autenticacao/login-social.md` etc. | árvore proposta pelo `90` | usados como **dado do cenário**; o oráculo de R1 é "exatamente um destino", não "este destino" |
| Jekyll como gerador | o `00` diz que a escolha **não foi feita pelo solicitante**; ela veio depois, e mudou uma vez | plataforma dos caminhos. Só R9 depende do formato, e por um motivo medido |
| "o build falha em link interno morto" (mitigação da versão anterior do `01`) | **era falso pela metade e agora é falso inteiro** | **achado, agravado na revisão**: o Jekyll não falha em link morto em lugar nenhum. Não existe portão de link. Foi o que originou CT-14 — e o que o fez **crescer** para os links internos do site |
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

**O mesmo arquivo guarda o "antes" do `package.json` da raiz**, que CT-12 usa: os **sete** nomes
de dependência que existem hoje — `@tailwindcss/vite`, `concurrently`, `laravel-vite-plugin`,
`playwright`, `tailwindcss`, `vite` e `@laravel/multiplex` — mais os dois scripts, `build` e `dev`.

Sem isso o `Então` de CT-12 ("o conjunto é idêntico ao de antes") **não tem quem guarde o antes**:
comparar o arquivo com ele mesmo é tautologia, e comparar com o commit anterior faz o cenário
depender de estar num repositório git com histórico — que é justamente o que **não** existe numa
instalação por `create-project` (medido em `tests/Pest.php:143`, a guarda do TIA). A lista
literal é a forma que funciona nos dois lugares, e ela envelhece com barulho: acrescentar uma
dependência legítima à raiz obriga a editá-la, que é exatamente a revisão que se quer forçar.

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

    Cenário: [CT-01] @premissa todo título do baseline existe no arquivo de destino
      Dado o baseline congelado com 32 títulos de segundo nível e 83 de terceiro
      E o mapa que classifica cada um como "landing", "site" ou "ambos"
      Quando o mantenedor procura cada um dos 115 títulos no arquivo que o mapa lhe deu
      Então a lista de títulos que não aparecem no seu arquivo de destino é vazia
      E nenhum título aparece em mais de um destino no mesmo idioma

    Cenário: [CT-24] o baseline é o de antes, e não uma foto do depois
      Dado o baseline congelado e o SHA do commit anterior à migração que ele declara
      Quando o mantenedor re-deriva os títulos dos READMEs naquele commit
      Então a lista re-derivada é igual à do baseline, título a título e na mesma ordem
      E ela tem 32 títulos de segundo nível e 83 de terceiro

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
| M2 | A página é criada com o título e o parágrafo de abertura, e o corpo fica para depois | CT-02 (linhas mínimas + os `h3` atribuídos) nas 5 partições amostradas; **CT-01 fecha as outras 28**, porque percorre os 115 títulos e não uma amostra |
| M3 | O conteúdo é **copiado** para o site e permanece no README — a landing não encolhe e as duas cópias divergem sozinhas | CT-03 |
| M4 | O baseline é escrito **a partir do resultado**: gerado do README de hoje mais os títulos das páginas do site, ele tem 32 e 83 entradas, bate com o `00` e faz CT-01 passar por construção | **CT-24**. A defesa anterior era a **cardinalidade** conferida contra o `00` — e cardinalidade **não é identidade**, exatamente a fraqueza que a 1ª rodada corrigiu em CT-07 e que ninguém propagou para cá. O SHA no topo do fixture era comentário: nada o conferia. **2ª revisão adversarial** |
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
      Quando o mantenedor compara as duas versões da página
      Então as duas têm a mesma quantidade de títulos de segundo e terceiro nível
      E o tamanho da inglesa difere do da portuguesa em no máximo 10% ou 5 linhas, o que for maior

      Exemplos:
        | pagina                              | # partição                          |
        | autenticacao/login-social           | maior página do site                |
        | operacao/convencoes-do-kit          | página curta, onde 10% são 3 linhas |
        | referencia/estudo-advanced-tables   | página mínima, piso de 5 linhas     |

    Esquema do Cenário: [CT-19] cada árvore de idioma está no seu idioma
      Dado todas as páginas publicadas em "<idioma>"
      Quando o mantenedor procura em cada uma marcador lexical de idioma
      Então toda página contém ao menos um marcador exclusivo de "<idioma>"
      E nenhuma página contém marcador exclusivo de "<o outro>"

      Exemplos:
        | idioma    | o outro   | # direção                         |
        | inglês    | português | cópia do pt para o en             |
        | português | inglês    | cópia do en para o pt (simétrico) |
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
| M10 | `cp -r docs/pt docs/en` e traduz só as páginas que os `Exemplos` nomeiam | **CT-19**, linha `inglês`. Antes da 1ª revisão adversarial estava marcado como morto por CT-06, **incorretamente** — ver a nota do cenário |
| M61 | `cp -r docs/en docs/pt`: o **espelho** de M10, e ele nasceu da correção de M10 — a versão anterior de CT-19 só olhava o inglês, então uma árvore portuguesa em inglês passava por tudo | **CT-19**, linha `português`. **2ª revisão adversarial**: a lacuna de segunda ordem que a própria correção criou |
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

    Cenário: [CT-10] nenhum cenário se guarda pela própria entrega
      Dado os arquivos de teste desta feature
      Quando o mantenedor inspeciona os desvios de execução declarados em cada um
      Então o único guard usado é a sentinela da árvore do kit
      E nenhum arquivo condiciona execução à existência do diretório de documentação
```

> **CT-09**: a granularidade é **seção**, não página. Afirmar sobre a página deixa passar o nome
> no topo e o motivo trezentas linhas abaixo — e o `Então` de existência (`ao menos uma seção
> nomeia Discord`) é o que impede a versão vácua, em que nenhuma página menciona Discord e a
> co-localização passa por vazio.
>
> **CT-10 é o cenário mais reescrito deste arquivo, e as duas rodadas adversariais explicam por
> quê.** A sentinela óbvia seria `is_dir(base_path('docs'))` — e ela é **auto-anulante**: se a
> migração não acontecer, `docs/` não existe, todos os cenários são ignorados e
> `composer test:kit` fica verde com zero entrega. O guard só pode se apoiar em algo que existe
> **independentemente desta feature**, e por isso é `.github`.
>
> A **1ª rodada** apontou isso, e a correção foi um `Então` de auto-declaração — *"nenhum cenário
> é ignorado, e a suíte executa ao menos uma asserção sobre `docs/`"*. A **2ª rodada** mostrou que
> essa correção não corrige nada: as duas asserções viviam **dentro do próprio CT-10** e passavam
> enquanto os outros 24 cenários ficavam guardados por `is_dir('docs')` e todos ignorados. **O
> cenário atestava a si mesmo.**
>
> A forma que funciona é **inspeção estática do arnês**, e o kit já tem o precedente exato:
> `tests/Kit/HelpersDeTesteTest.php` usa `token_get_all()` para auditar o próprio código de teste,
> justamente porque regex conta menção em comentário como chamada. O oráculo aqui é do mesmo tipo —
> ele lê os arquivos de teste, não o resultado deles.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | A asserção fica vermelha na migração e alguém a **apaga** para destravar o commit | CT-07 (piso de 79, escrito com o número literal do `00`) |
| M16 | A asserção de **ausência** é mantida apontando para o README e vira vácuo | CT-08 (a âncora de presença no mesmo documento) |
| M17 | A asserção é reapontada para o arquivo errado — `docs/pt/...` no dataset do inglês | CT-08 (linhas `en` com âncora em inglês: `MEDIA_DISK` casa nos dois, `shows the application name` não) |
| M18 | A verificação de co-localização é reescrita concatenando `docs/**`: nome numa página, motivo em outra, e passa | CT-09 (nomeia uma única página; e o segundo `Então`) |
| M19 | Os cenários novos varrem `docs/`, que é `export-ignore` — **toda instalação** fica vermelha em `composer test:kit` | CT-10 (ramo de fora do kit) |
| M40 | A sentinela vira `is_dir('docs')`: se a entrega inteira não acontecer, tudo é ignorado e a suíte fica verde | **CT-10**, na forma de inspeção estática. A forma anterior — um `Então` de auto-declaração dentro do próprio CT-10 — **não o matava**: 2ª revisão adversarial |
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
        | docs/_config.yml                           | sim      | raiz de docs          |
        | docs/_data/nav_en.yml                      | sim      | um nível abaixo       |
        | docs/pt/comecar/instalacao-avancada.md     | sim      | três níveis           |
        | README.md                                  | não      | **controle negativo** |
        | art/install.gif                            | não      | **controle negativo** |

    Cenário: [CT-12] nenhuma toolchain de documentação encosta na raiz do projeto
      Dado os dois manifestos de pacote da raiz, o de PHP e o de JavaScript
      Quando o mantenedor compara cada um com o conjunto congelado no baseline
      Então o conjunto de dependências de cada manifesto é idêntico ao do baseline
      E os scripts "build" e "dev" do manifesto de JavaScript continuam existindo
      E não existe Gemfile nem Gemfile.lock na raiz do repositório
```

> **Revisão de 2026-09-01**: com o build nativo **não há dependência a instalar** — o GitHub
> resolve as gems no servidor dele. O cenário perdeu a metade "existe do lado certo" (**M24
> morreu**: não há manifesto obrigatório em `docs/`, e a ADR-02 deixa o `Gemfile` de prévia local
> como *opcional*) e ganhou a metade que a troca de gerador criou: **Ruby não pode aparecer na
> raiz de um projeto PHP+Node**. O princípio da ADR-02 sobreviveu à troca; só mudou o nome do
> arquivo proibido.
>
> "Aparece no manifesto" seria presença de string — o campo `name` ou um comentário bastariam. Por
> isso o oráculo é o **conjunto** de dependências, comparado com o estado anterior: torna "a raiz
> não foi tocada" falsificável sem congelar o arquivo inteiro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | O padrão de exportação é escrito de forma que não alcança subdiretório (`/docs/*.md`) | CT-11 (a linha de três níveis; uma comparação de texto no arquivo de atributos **não** mataria) |
| M23 | Uma dependência de documentação é declarada na raiz — todo projeto instalado passa a baixá-la | CT-12 (primeiro `Então`) |
| M62 | A dependência de documentação entra pelo `composer.json` (`require-dev`) em vez do `package.json`, e o cenário que só olhava "o manifesto de pacotes da raiz", no singular, não a via | **CT-12**, agora sobre os **dois** manifestos. **2ª revisão adversarial** |
| M63 | O "estado anterior" é lido da própria árvore de trabalho — `git show HEAD:package.json` **depois** do commit da migração já é o estado migrado, e o cenário compara o arquivo consigo mesmo | **CT-12** + a lista literal do `## Fixture`. **2ª revisão adversarial**: sem um baseline congelado, "idêntico ao de antes" é tautologia |
| M25 | Ao editar a raiz para "não tocar", alguém remove os scripts `build`/`dev` e quebra o projeto instalado | CT-12 (segundo `Então`) |
| M51 | Um `Gemfile` de prévia local é criado **na raiz** em vez de em `docs/`: Ruby na raiz de um projeto PHP+Node, e todo projeto instalado nasce com ele | **CT-12** (terceiro `Então`) — mutante da troca de gerador |
| ~~M24~~ | ~~O diretório de documentação não ganha manifesto próprio e nada instala~~ | **morto pela troca de gerador**: o build nativo não instala nada, e o `Gemfile` em `docs/` é opcional por ADR-02 |
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

    Cenário: [CT-22] nenhum link interno do site aponta para página inexistente
      Dado todas as páginas publicadas nos dois idiomas
      Quando o mantenedor resolve cada link interno e cada âncora para o seu destino
      Então toda página que o baseline atribuía uma referência cruzada carrega ao menos um link interno
      E a lista de links internos sem destino existente é vazia
      E nenhuma página conserva âncora para uma seção que virou outra página
```

> **CT-22 é filho direto da troca de gerador, e é o cenário que mais ganhou importância.** A
> mitigação de link quebrado do plano anterior era "o build falha em link morto" — já registrada
> aqui como verdadeira só dentro de `docs/`. Com o Jekyll ela é **falsa inteira**: o build nativo
> publica link quebrado sem reclamar, em qualquer lugar. **Não existe portão automático de link
> em lugar nenhum além deste cenário.**
>
> O segundo `Então` mira o defeito específico da migração: o passo 4 converte âncoras internas
> (`#secao`) em links entre páginas. Uma âncora esquecida continua sintaticamente válida, não
> quebra nada visivelmente no markdown, e leva o leitor a lugar nenhum.
>
> **CT-14 precisa do piso, e é a lição mais barata da 1ª revisão adversarial**: "todo link
> resolve" é um quantificador universal, e universal sobre **conjunto vazio é verdadeiro**. Um
> README de 120 linhas sem link nenhum passava — 44 páginas escritas, ninguém chega a elas, RQ-02
> morta com tudo verde. O piso por seção migrada é o que fecha.
>
> **CT-22 precisa do seu próprio piso, e a 2ª rodada derrubou o argumento de que não precisava.**
> A versão anterior desta nota dizia que CT-22 "herdava a lição" de CT-14 e CT-20 — **e não
> herda**: CT-14 conta links **do README**, CT-20 conta referências **da árvore de navegação**, e
> nenhum dos dois prova que existe um único link *de uma página do site para outra*. Uma migração
> que transforme toda referência cruzada em texto simples passava por CT-22 nos dois `Então`, por
> vacuidade. O piso agora é do próprio cenário, ancorado nas referências cruzadas que o baseline
> registra.
>
> **CT-13 ganhou a terceira asserção pela mesma razão**: contar linhas não mede o que M28 diz
> medir. Um README truncado no meio de uma frase tem 750 linhas e não ensina a instalar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | O português encolhe e o inglês fica com 2.533 linhas — o modo de falha **documentado** deste projeto | CT-13 (linha `inglês` e o terceiro `Então`) |
| M28 | O README é reduzido a badges e um link para o site; quem chega pelo Packagist não vê mais como instalar | CT-13 (piso de 100) |
| M29 | Um link do README aponta para `/pt/recursos/anexos.md` enquanto o arquivo é `anexos-e-midia.md` | CT-14. **Nenhum build pega isto** — ver M52 |
| M52 | Um link **interno ao site** aponta para página inexistente, ou uma âncora `#secao` sobrevive à migração apontando para uma seção que virou outra página | **CT-22** — mutante da troca de gerador: o Jekyll publica link morto sem reclamar |
| M30 | O teto é escrito como constante (`<= 340`) e a primeira edição legítima da landing derruba a suíte | prevenido **por desenho** — CT-13 usa razão sobre o valor histórico. Não é mutante de produto; fica registrado porque é o erro que o cenário existe para não cometer |

---

## Regra R7 — O repositório está montado para o GitHub publicar, e no endereço certo

> `RQ-01`, `RQ-06` · perfil **padrão** · técnica: EP sobre a estrutura que o build nativo exige +
> **derivação** do endereço base
>
> **Reescrita em 2026-09-01.** A regra anterior falava de um fluxo de Actions que **não existe
> mais**. O que o build nativo exige é estrutura de arquivo em `main:/docs`, mais uma configuração
> de repositório que nenhum teste alcança — e a fronteira entre as duas metades é o assunto desta
> regra.

```gherkin
# language: pt

  Regra: o diretório publicado tem o que o build nativo exige, e ninguém reintroduz um fluxo

    Cenário: [CT-15] a raiz publicada tem o que o build nativo exige
      Dado o diretório que o GitHub publica, na raiz de "docs"
      Quando o mantenedor confere o que existe nele
      Então existe configuração do gerador com o endereço base declarado
      E existe uma página inicial não vazia na raiz publicada
      E não existe manifesto de pacote npm dentro do diretório publicado

    Cenário: [CT-25] nenhum outro mecanismo publica o mesmo site
      Dado os fluxos de Actions do repositório
      E um fluxo de controle que publica empurrando para uma branch de páginas
      Quando o mantenedor aplica o detector de publicação a todos eles
      Então o fluxo de controle é acusado
      E nenhum fluxo real do repositório é acusado

    Cenário: [CT-16] o endereço base do site é o do repositório, não o do pacote
      Dado que o pacote se chama "gsferro/starter-kit-easy"
      E que o repositório se chama "filament-starter-kit-easy"
      Quando o mantenedor lê o endereço base configurado no site
      Então ele é o nome do repositório, derivado da página inicial declarada no pacote
      E ele começa com barra e não termina com barra
```

> **CT-16 mudou de forma de valor com o gerador.** O `base` do VitePress leva barra nos dois lados;
> o `baseurl` do Jekyll leva barra **só na frente** — `/filament-starter-kit-easy`. A asserção
> anterior ("começa e termina com barra") estaria **errada** hoje, e é o tipo de detalhe que passa
> despercebido numa troca de plataforma. O que não mudou é o valor discriminante: o pacote se chama
> `starter-kit-easy` e o repositório `filament-starter-kit-easy`, então derivar do `name` do
> `composer.json` produz o endereço errado e o site quebra publicado.
>
> **CT-25 é a guarda contra a regressão de arquitetura**, e ele tem controle positivo por um
> motivo apontado pela 2ª revisão adversarial: *"não existe fluxo que publique o site"* não tem
> definição operacional. Um detector que procura `actions/deploy-pages` deixa passar `peaceiris`,
> `JamesIves` ou um `git push origin gh-pages` cru — e, sem controle, o cenário não distingue "não
> há fluxo" de "meu detector não casa nada". O repositório ainda tem dois fluxos legítimos
> (`ci.yml`, `seguranca.yml`) servindo de **falso conforto**: o detector roda, não acusa nada, e
> parece que funcionou.
>
> **CT-15 e CT-25 são dois cenários e não um** porque o `Quando` anterior era conjuntivo — conferia
> `docs/` **e** `.github/` na mesma frase. Além de soldar dois comportamentos, isso escondia a
> segunda metade no relatório quando a primeira falhava.
>
> **O terceiro `Então` diz `npm` e não "manifesto de pacote", de propósito.** A ADR-02 **permite**
> um `Gemfile` dentro de `docs/` para prévia local — proibir "manifesto de qualquer linguagem"
> criaria um caso de teste que reprova uma decisão aceita. O que não pode voltar é o **npm**, que a
> troca de gerador eliminou por completo: um `package.json` em `docs/` é o sinal de que alguém
> começou a reintroduzir a toolchain que a ADR-01 dispensou.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | O endereço base é omitido: o site funciona em prévia local e quebra publicado — **o único defeito desta feature invisível em desenvolvimento** | CT-16 |
| M33 | O endereço base é derivado do **nome do pacote** (`/starter-kit-easy`), que difere do repositório (`/filament-starter-kit-easy`) — armadilha real e medida: os dois nomes não batem neste projeto | CT-16 (o cenário fixa os dois nomes lado a lado, que é o valor discriminante) |
| M44 | O `baseurl` é escrito sem a barra inicial, ou com barra no fim — satisfaz "é o nome do repositório" e quebra o site publicado | **CT-16** (segundo `Então`). Origem: 1ª revisão adversarial; **a forma exigida inverteu com a troca de gerador** |
| M53 | Alguém reintroduz um fluxo de Actions publicando o mesmo site, que passa a competir com o build nativo | **CT-15** (segundo `Então`) — mutante da troca de gerador |
| M54 | O diretório publicado não tem página inicial na raiz, e o site abre em 404 antes de qualquer idioma | **CT-15** (primeiro `Então`) — mutante da troca de gerador |
| **M43′** | **O Pages nunca foi habilitado, ou foi apontado para a branch ou a pasta errada.** Todos os arquivos existem, todos os cenários passam, e o site não existe — `GET /repos/.../pages` continua devolvendo o 404 que o `00` mediu | ⚠️ **sem matador automático.** É a forma que M43 assumiu com a troca de gerador, e ela é **pior**: antes o defeito morava num arquivo versionado (o workflow) e podia ser lido; agora mora numa configuração de repositório, fora do alcance de qualquer teste desta suíte e fora do alcance de um `git revert`. Tentado: derivar de arquivo — não há arquivo; o `_config.yml` é idêntico com o Pages ligado ou desligado. **Vai para `## Fora do alcance` como item 0 da verificação manual, com evidência nomeada** |
| ~~M31~~ | ~~Sem grupo de concorrência, dois deploys competem~~ | **morto pela troca de gerador**: sem Actions não há execução paralela a serializar |
| ~~M34~~ | ~~O `npm ci` roda na raiz em vez de em `docs/`~~ | **morto pela troca de gerador**: não há instalação de dependência em lugar nenhum |

---

## Regra R8 — O processo de atualização está escrito, nos dois idiomas

> `RQ-06` · perfil **padrão** · técnica: EP por idioma

RQ-06 é literal: *"o processo de atualização precisa estar definido e ser conhecido"*. "Definido"
é R7; **"conhecido" é esta regra**, e sem ela a cláusula fica órfã — o mecanismo existe e ninguém
sabe que existe.

**A troca de gerador tornou esta regra mais fácil de satisfazer e mais fácil de esquecer.** O
processo virou *editar markdown → commit na `main` → o GitHub publica*, sem build e sem workflow.
Justamente por não haver artefato de CI para ler, **a única forma de alguém descobrir o processo é
estar escrito** — e é preciso dizer também o que **não** existe, senão o próximo mantenedor procura
um `docs.yml` que nunca vai achar e conclui que a publicação está quebrada.

```gherkin
# language: pt

  Regra: quem mantém o kit descobre pela documentação como o site se atualiza

    Esquema do Cenário: [CT-17] cada idioma explica como a documentação é publicada
      Dado a documentação de manutenção do kit em "<idioma>"
      Quando o mantenedor procura como publicar uma alteração no site
      Então o texto diz que a publicação acontece no push para o branch padrão
      E o texto diz que não há workflow nem build a rodar
      E o texto nomeia onde a origem do Pages é configurada

      Exemplos:
        | idioma    |
        | português |
        | inglês    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M35 | O site é publicado e nada é escrito: o próximo mantenedor edita `docs/` e não sabe se publicou | CT-17 |
| M36 | O processo é documentado só em português — a mesma omissão que gerou as 4 divergências | CT-17 (linha `inglês`) |
| M59 | A documentação é escrita a partir do plano **anterior** e descreve um workflow e um `npm run docs:build` que não existem — o mantenedor procura o que nunca vai achar | **CT-17** (segundo `Então`) — mutante da troca de gerador, e o mais provável de todos, porque o texto costuma ser copiado do plano |
| M60 | O texto explica como publicar e não diz **onde** a origem do Pages se liga — o único passo que não está em nenhum arquivo (M43′) fica sem dono | **CT-17** (terceiro `Então`) — mutante da troca de gerador |

---

## Regra R9 — A migração não injeta sintaxe que o gerador interpreta como código

> `RQ-03` · perfil **padrão** · técnica: **texto livre / caractere especial**
>
> **Simplificada em 2026-09-01, e ficou mais forte.**

O Jekyll compila markdown com **Liquid**, e o Liquid processa `{{` e `{%` **inclusive dentro de
bloco de código** — ao contrário do gerador anterior, onde a cerca protegia. O `01` mediu **0
ocorrências** dos dois delimitadores nos dois READMEs, dentro e fora de código, então a migração de
hoje não é afetada.

**Mantido mesmo assim, e de propósito.** O cenário deixou de ser sobre a migração e virou uma
**guarda para conteúdo futuro**: o kit é um starter kit Laravel, sua documentação vai ganhar
exemplos de Blade, e um `{{ $user->name }}` colado numa página some da tela renderizada sem nada
ficar vermelho — porque não há build local que acuse. Custo: uma varredura de string sobre 44
arquivos. Pela escada do Ponytail o degrau se paga: é a coisa mais barata que existe entre um
exemplo de Blade e uma página com buraco.

```gherkin
# language: pt

  Regra: nenhuma página do site contém delimitador de template não escapado

    Cenário: [CT-18] a migração não deixa delimitador de template solto
      Dado todas as páginas publicadas nos dois idiomas
      E dois textos de controle, um com interpolação e outro com tag de template
      Quando o mantenedor aplica o detector a todos eles
      Então os dois controles são acusados
      E nenhuma página publicada é acusada fora de bloco de escape literal
```

> **Sumiu o filtro de cercas, e com ele o ponto fraco que a 1ª revisão adversarial apontou.** Lá o
> oráculo morava no `Quando` — um filtro guloso apagava a página inteira e o `Então` ficava
> vacuamente verdadeiro. Como o Liquid não respeita a cerca, **não há filtro**.
>
> **Mas os controles voltaram, e a 2ª revisão adversarial explicou por quê.** Tirar o filtro tirou
> junto os dois controles que a 1ª rodada tinha instalado, e sobrou um `Então` universal-negativo
> sobre um conjunto que pode ser vazio: um detector com erro de escape — `{{` não escapado numa
> expressão regular — **nunca casa nada** e o cenário fica verde para sempre, com a página
> quebrada. Achado fechado na 1ª rodada, reaberto pela troca de plataforma, fechado de novo aqui.
>
> A única exceção legítima é o bloco de escape do próprio Liquid (`{% raw %}`), que é o remédio que
> o `01` prescreve para quem for escrever exemplo de Blade depois.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M37 | Um exemplo de Blade é escrito numa página e o Liquid o interpreta: o trecho **some** da página publicada, sem erro em lugar nenhum | CT-18 |
| M38 | A verificação roda só no português | CT-18 (o `Dado` é sobre os dois idiomas) |
| M55 | A verificação procura só `{{` e ignora `{%`, que é a metade que executa lógica de template | **CT-18** (o segundo controle) — mutante da troca de gerador |
| M64 | O detector tem erro de escape e nunca casa nada: o `Então` universal-negativo fica verde sobre um conjunto vazio de achados, para sempre | **CT-18** (o `Então` dos controles). **2ª revisão adversarial**: a remoção do filtro levou junto os controles que o protegiam |
| ~~M39~~ | ~~O filtro de cercas é guloso e o cenário nunca acusa~~ | **morto pela troca de gerador**: sem cerca protegendo, não há filtro que possa se auto-anular |

---

## Regra R10 — Toda página é alcançável, e as duas árvores de navegação estão em paridade

> `RQ-02` · perfil **completo** (herdado da área de paridade) · técnica: alcançabilidade +
> comparação de conjunto nos dois sentidos sobre as duas árvores
>
> **Origem: 1ª revisão adversarial.** RQ-02 estava formalmente coberta (R1 migra, R6 encolhe) e
> materialmente descoberta: 44 páginas escritas com a navegação vazia satisfaziam tudo o que o
> conjunto afirmava. "Mais fácil a leitura" morre com página órfã.
>
> **Cresceu em 2026-09-01.** As duas árvores agora são **arquivos escritos e mantidos à mão** —
> `docs/_data/nav_pt.yml` e `docs/_data/nav_en.yml` —, porque o build nativo roda em `--safe` e não
> aceita plugin de i18n, e o tema também não tem. É superfície que não existia na derivação
> original: dois arquivos que podem divergir **entre si** e **do conteúdo**, sem nada na
> plataforma para acusar.

```gherkin
# language: pt

  Regra: nenhuma página fica fora da navegação, e as duas árvores têm a mesma forma

    Cenário: [CT-20] toda página aparece na navegação do idioma a que pertence
      Dado o conjunto de páginas publicadas em cada idioma
      Quando o mantenedor confronta esse conjunto com a árvore de navegação daquele idioma
      Então cada idioma tem ao menos as 22 páginas que o mapa lhe atribui
      E toda página está referenciada na árvore do seu idioma
      E a árvore não referencia nenhuma página inexistente

    Cenário: [CT-23] as duas árvores de navegação têm a mesma estrutura
      Dado as duas árvores de navegação mantidas à mão
      Quando o mantenedor compara os grupos e as entradas de cada uma
      Então as duas têm os mesmos grupos, na mesma ordem e na mesma quantidade
      E cada entrada de uma tem a entrada equivalente na outra, no mesmo grupo
      E os rótulos de cada árvore trazem marcador lexical do seu próprio idioma
```

> **O terceiro `Então` de CT-23 é o que discrimina.** Estrutura igual é fácil de conseguir
> copiando o arquivo — e um `cp nav_pt.yml nav_en.yml` passa nos dois primeiros `Então` com o menu
> inteiro em português. É o mesmo defeito que CT-19 pega no conteúdo, na superfície que a troca de
> gerador criou.
>
> **A forma do oráculo mudou depois da 2ª revisão adversarial.** A versão anterior dizia "nenhum
> rótulo aparece literalmente igual na outra árvore" — **inexequível**: `Docker`, `Filament`,
> `Livewire` e `Pest` são rótulos legítimos e idênticos nos dois idiomas. A nota alegava que a
> exceção ficava "declarada no dado do cenário", e o `Dado` não declarava exceção nenhuma. Um
> implementador honesto relaxaria para "ao menos um rótulo difere", e aí `cp` mais uma tradução
> passa — **M57 vivo**. Usar o mesmo **marcador lexical** de CT-19 resolve sem lista de exceção:
> nome próprio não carrega marcador de idioma nenhum, então não é falso positivo nem falso
> negativo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M45 | As 44 páginas são escritas e a navegação fica vazia — o site publica e ninguém acha nada | CT-20 |
| M46 | A navegação é escrita à mão e envelhece: aponta para uma página renomeada | CT-20 (segundo `Então`) |
| M47 | A árvore do inglês reaproveita os caminhos do português | CT-20 (o `Então` é por idioma) — e CT-04 |
| M56 | Uma página nova é acrescentada ao menu de um idioma e esquecida no do outro — **exatamente o modo de falha que o projeto já demonstrou quatro vezes**, agora numa superfície onde nada além deste cenário olha | **CT-23** (segundo `Então`) — mutante da troca de gerador |
| M57 | `nav_en.yml` nasce de um `cp` do `nav_pt.yml`: estrutura perfeita, menu em português | **CT-23** (terceiro `Então`) — mutante da troca de gerador |

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

    Cenário: [CT-21] a documentação não carrega vídeo, cópia de mídia, ponteiro de LFS nem caminho relativo
      Dado todas as páginas e todos os arquivos sob o diretório de documentação
      Quando o mantenedor classifica cada referência de mídia e cada arquivo binário
      Então nenhuma página referencia vídeo ou embed de vídeo
      E nenhum binário do diretório de arte aparece duplicado sob a documentação
      E nenhum arquivo da documentação é um ponteiro de armazenamento grande
      E toda referência de imagem é absoluta, como já era na origem
```

> **O quarto `Então` entrou em 2026-09-01.** A medição da ADR-06 é que das 27 referências de imagem
> dos READMEs, **20 são URLs absolutas, 7 são badges e nenhuma é relativa** — por isso o plano não
> tem passo de imagem. Isso transforma o risco: não é mais duplicar `art/`, é a migração
> **introduzir** um caminho relativo que ninguém pediu. E um caminho relativo é o defeito perfeito
> desta plataforma — resolve na prévia local, quebra no site publicado por causa do `baseurl`, e
> nenhum build acusa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M48 | Alguém acha que o site "merece" um vídeo de demonstração e embute um — o solicitante tirou vídeo de escopo na mensagem 2 | CT-21 (primeiro `Então`) |
| M49 | `art/` é copiado para `docs/public/`, dobrando 8,5 MB no repositório | CT-21 (segundo `Então`) |
| M50 | Uma imagem nova entra por LFS e o Pages entrega o arquivo-ponteiro — imagem quebrada só em produção | CT-21 (terceiro `Então`) |
| M58 | A migração converte uma URL absoluta em caminho relativo "para ficar mais limpo": funciona na prévia e quebra publicado, pelo `baseurl` | **CT-21** (quarto `Então`) — mutante da troca de gerador |

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
| Concorrência | **não se aplica desde 2026-09-01**: sem workflow não há execução paralela a serializar; o build nativo do Pages enfileira sozinho. Era CT-15 quando havia Actions |
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
| **Alcançabilidade** (linha nova, trazida pela 1ª revisão adversarial) | CT-20, CT-22, CT-23 — artefato que existe em disco e não é alcançável pela navegação é o "criado mas nunca usado" deste meio. **Com o Jekyll a linha vale dobrado**: nenhum build acusa link morto, então alcançabilidade só existe se estes cenários existirem |
| **Controle positivo / negativo do próprio oráculo** (linha nova) | CT-11 (controles negativos), CT-10 (ramo positivo da sentinela). **CT-18 saiu desta linha em 2026-09-01**: como o Liquid ignora a cerca de código, o filtro que precisava de controle deixou de existir — a troca de gerador removeu um defeito de desenho do caso de teste |
| **Configuração fora do repositório** (linha nova, 2026-09-01) | ⚠️ **lacuna declarada — M43′**. O Pages habilitado e apontado para `main:/docs` não tem representação em arquivo nenhum. É a primeira vez neste kit que uma cláusula de requisito depende de estado que não está versionado. Vai para verificação manual, item 0 |
| **Criação ≠ edição ≠ uso** (a linha que mais rende) | **criação**: CT-01, CT-02 (a página nasce completa). **edição**: CT-03 — as 5 seções `ambos` deixam **duas** cópias do assunto, e é na edição posterior que elas divergem; CT-03 é o que impede a segunda cópia de nascer. **uso**: CT-14 (o leitor chega pelo link do README) |
| **Rastreio de efeito, aplicado a asserção** | CT-07 (existe), CT-08 (não virou vácuo), CT-09 (não mudou de alvo), CT-10 (não quebra fora do kit) |

---

## Fora do alcance da suíte PHP — e por quê

Isto não é lista de escopo, é a lista do que **fica sem rede** e precisa de outro dono. Escrever
"verificado" sem ela é o falso ✅ que a skill combate.

**A troca de gerador aumentou esta lista, e é o custo honesto da simplicidade que ela comprou.**
Sem workflow não há arquivo versionado que descreva a publicação; sem build local não há portão
automático nenhum. O que era "o build prova" virou "alguém precisa olhar".

| Afirmação | Por que a suíte não alcança | Quem verifica |
|---|---|---|
| **O Pages está habilitado, apontando para `main` → `/docs`** (M43′) | **Não existe arquivo que diga isso.** É configuração de repositório, lida pela API do GitHub — e o `_config.yml` é byte a byte idêntico com o Pages ligado ou desligado. Nenhum teste desta suíte alcança, e nenhum `git revert` conserta | **Verificação manual, item 0 — o mais importante da lista** |
| O `baseurl` está certo **em produção** | quebra publicado e passa em prévia local, por definição. CT-16 prova que a configuração é coerente com o repositório, não que a publicação funcionou | Verificação manual, item 1 |
| O site **construiu** sem erro | o build roda nos servidores do GitHub, depois do push. Não há build local obrigatório para servir de portão, e um erro de Liquid ou de front matter só aparece lá | Verificação manual, item 1 (a página abrir já é o sinal) |
| As imagens **renderizam** no site (RQ-05) | CT-21 prova que a mídia não foi duplicada, não está em LFS e continua absoluta; que o GitHub as sirva é outra coisa | Verificação manual, item 2 |
| A busca do tema funciona | é JavaScript rodando no site publicado; nenhum arnês deste projeto o alcança (ver `## Gate de CT-B`). E o índice é **único**, misturando os dois idiomas — limitação conhecida do tema, não defeito | Verificação manual, item 3 |

### Verificação manual — quatro itens, com evidência nomeada

Não é "conferir o site". É esta lista, e cada linha pede uma evidência:

0. **`GET /repos/gsferro/filament-starter-kit-easy/pages` deixou de responder 404**, e o `source`
   devolvido é `branch: main`, `path: /docs` — evidência: o corpo da resposta, colado no `03`. O
   `00` mediu o 404 antes da entrega; enquanto ele não virar 200 com esse `source`, **RQ-01 não
   está atendida por mais verde que a suíte esteja**.
1. Abrir `https://gsferro.github.io/filament-starter-kit-easy/pt/` e navegar até uma página de
   terceiro nível — evidência: a URL contém o nome do repositório e a página renderiza com CSS.
2. Numa página que tenha captura de tela, confirmar que a imagem carrega — evidência: a imagem
   aparece e o endereço dela é `raw.githubusercontent`, como na origem.
3. Buscar por um termo que só existe em uma página (`getTabs`) — evidência: o resultado leva à
   página de convenções. **Não** se espera separação por idioma: o índice é único.

---

## Gate de CT-B

**Concordo com a conclusão do `01`: sem CT-B.** E contesto o argumento.

**Onde o `01` está certo — e ficou mais certo com a troca de gerador.** O `pest-plugin-browser`
sobe um servidor HTTP in-process que serve a **aplicação Laravel** — `tests/Pest.php:111-114` e o
bloco de comentário nas linhas 96-109. Com o build nativo, **o site não existe localmente em
momento nenhum**: quem o gera é o GitHub, depois do push. Não há `dist` para copiar para dentro de
`public/`, e produzir um exigiria instalar Ruby e o Jekyll num job que não tem nem Node. Pela
escada do Ponytail isso não passa do primeiro degrau: o cenário não precisa existir.

**Onde o `01` está errado, e importa.** A frase que fecha o gate — *"o que se afirma é tudo
verificável em arquivo"* — é falsa em dois pontos, e é o tipo de frase que faz a próxima pessoa
parar de procurar:

1. **O `baseurl` correto não é verificável em arquivo.** CT-16 prova que a configuração é
   *coerente com o repositório*; ele não prova que o site publicado resolve seus próprios ativos.
2. **A busca do tema não é verificável em arquivo.** Ela é a metade de RQ-02 que justifica o site
   existir ("mais fácil a leitura"), é JavaScript, e nenhuma linha desta wiki a toca.
3. **A publicação em si não é verificável em arquivo** — e este item nasceu com a troca de
   gerador. Enquanto havia workflow, "o repositório publica" morava num arquivo versionado que um
   teste podia ler. Com o build nativo mora numa configuração de repositório. É o M43′, e é a
   maior lacuna deste conjunto.

**Consequência**: o gate se sustenta, a justificativa não. O correto é `sem CT-B` **porque o
arnês não alcança o artefato**, e não porque não há nada a provar no navegador — e o que sobra
vira a lista de verificação manual acima, com evidência nomeada. Gate fechado com a justificativa
errada é como uma lacuna vira ✅ sem ninguém perceber.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Gerar o site localmente e servi-lo por `public/docs` para visitar no navegador da suíte | exige Ruby e Jekyll num job que não tem nem Node, para reproduzir um build que só o GitHub faz; e o `baseurl` de produção continuaria sem cobertura |
| Consultar a API do GitHub, no teste, para provar que o Pages está habilitado (M43′) | teste dependente de rede, de token e do estado de um serviço externo — falha vermelho por motivo que não é o dele, e em toda instalação do kit. Vira **item 0 da verificação manual**, que é onde ele prova alguma coisa |
| `assertNoJavaScriptErrors()` na home do site | assertion de apoio como oráculo único — a skill a proíbe, e uma página em branco passaria |
| Screenshot da home nos dois temas | não há tema customizado (decisão do `01`); nenhum mutante previsto morre |
| Visitar o site publicado por HTTP a partir do teste | teste dependente de rede e do estado do GitHub; falha por motivo que não é o dele |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | Todo título do baseline existe no destino | R1 | EP exaustiva | Kit | `tests/Kit/SiteDeDocumentacaoTest.php` | M1, M2 (28/32), M5 |
| CT-02 | A página de destino não é esqueleto | R1 | EP + borda de tamanho | Kit | idem | M2, M5 |
| CT-03 | Nada que migrou continua no README (exaustivo) | R1 | ausência exaustiva | Kit | idem | M3 |
| CT-04 | Conjuntos de páginas idênticos nos dois sentidos | R2 | conjunto simétrico | Kit | idem | M6, M7 |
| CT-05 | Nenhuma página inglesa é resumo da portuguesa | R2 | razão por página | Kit | idem | M8 |
| CT-06 | As 4 divergências chegam ao destino em inglês | R3 | regressão por token | Kit | idem | M11, M12, M14 |
| CT-07 | O inventário não encolhe nem troca de conteúdo | R4 | rastreio de efeito | Kit | `tests/Kit/RedeDeDocumentacaoTest.php` | M15, M41 |
| CT-08 | Ausência sempre acompanhada de âncora | R4 | rastreio de efeito | Kit | idem | M16, M17 |
| CT-09 | Nome e motivo na mesma seção | R4 | rastreio de efeito (co-localização) | Kit | idem | M18, M42 |
| CT-10 | Nenhum cenário se guarda pela própria entrega | R4 | inspeção estática do arnês | Kit | idem | M19, M40 |
| CT-11 | `docs/` fora do dist, com controle negativo | R5 | atributo de exportação | Kit | `tests/Kit/SiteDeDocumentacaoTest.php` | M22 |
| CT-12 | Nenhuma toolchain de documentação na raiz | R5 | conjunto congelado + ausência | Kit | idem | M23, M25, M51, M62, M63 |
| CT-13 | A landing encolhe sem se esvaziar, nos dois idiomas | R6 | BVA de dois lados | Kit | idem | M27, M28 |
| CT-14 | O README leva ao site, e os links resolvem | R6 | resolução + piso | Kit | idem | M29 |
| CT-15 | A raiz publicada tem o que o build nativo exige | R7 | EP de estrutura | Kit | idem | M54 |
| CT-16 | `baseurl` derivado do repositório, com a forma do Jekyll | R7 | derivação + formato | Kit | idem | M32, M33, M44 |
| CT-17 | O processo de atualização está escrito nos dois idiomas | R8 | EP por idioma | Kit | idem | M35, M36, M59, M60 |
| CT-18 | Sem delimitador de template solto | R9 | texto livre + controle positivo | Kit | idem | M37, M38, M55, M64 |
| CT-19 | Cada árvore de idioma está no seu idioma | R2 | marcador lexical, nos dois sentidos | Kit | idem | M10, M61 |
| CT-20 | Toda página é alcançável pela navegação | R10 | alcançabilidade | Kit | idem | M45, M46, M47 |
| CT-21 | Sem vídeo, sem mídia duplicada, sem LFS, sem caminho relativo | R11 | EP por tipo de artefato | Kit | idem | M48, M49, M50, M58 |
| CT-22 | Nenhum link interno do site é morto | R6 | resolução com piso | Kit | idem | M52 |
| CT-23 | As duas árvores de navegação em paridade | R10 | conjunto simétrico + marcador lexical | Kit | idem | M56, M57 |
| CT-24 | O baseline é o de antes, não uma foto do depois | R1 | baseline auto-verificável | Kit | idem | M4 |
| CT-25 | Nenhum outro mecanismo publica o site | R7 | controle positivo do detector | Kit | idem | M53 |

**Mutantes sem matador**: **M43′** (o Pages nunca habilitado ou apontado para o lugar errado —
o mais grave, e novo desde a troca de gerador), M9 (tolerância agregada × por página), M13 (token
de F-06 fraco por construção), M20 (inventário editado para baixo), M21 (janela entre commits).
Os cinco estão declarados na sua regra, com o que foi tentado.

**Mutantes mortos pela troca de gerador**, riscados e mantidos à vista em vez de apagados: M24
(manifesto obrigatório em `docs/`), M31 (grupo de concorrência), M34 (`npm ci` no lugar errado),
M39 (filtro de cercas guloso). Apagá-los esconderia que o conjunto já cobriu aquele risco — e
seriam os primeiros a ressuscitar se alguém voltar a ADR-01 para a alternativa 2 (Jekyll via
Actions), que a própria ADR deixa nomeada como saída.

---

## Fechamento do Ciclo — por que não há mutation score aqui

`pest --mutate` **não tem o que mutar**: esta feature não produz uma linha de código de aplicação.
Não é falha de configuração, é a consequência que a própria skill descreve — *"mutation testing só
muta código que existe"*. Um score de 100% aqui significaria zero mutantes gerados, e reportá-lo
como qualidade seria o falso ✅ mais caro possível.

O que substitui, e é o único indicador desta wiki:

1. **O gate de mutantes de especificação** (59 vivos; 52 mortos por cenário deste conjunto, 2 por
   desenho ou por teste herdado, 5 declarados sem matador) —
   ele nasce do requisito e por isso enxerga **omissão**, que é a classe de defeito desta entrega.
2. **A rastreabilidade `RQ` → regra**, no `## Mapa de Regras`, com as duas dispensas escritas
   (RQ-04, RQ-05).
3. Depois de implementar: rodar `php artisan test --testsuite=Kit,Tenancy --compact` e conferir
   que **os 25 CT existem como teste real** — a coluna "Arquivo" do índice acima é o que fecha.

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

### Segunda rodada — executada em 2026-09-01, depois da troca de gerador

Mesmo contrato, sub-agente diferente, entrada só `00` + `04`. Ela era devida desde a 1ª rodada
(o fechamento tinha criado cenário e regra novos) e ficou **ainda mais necessária** com a troca de
plataforma, que reescreveu R7, R9 e R10.

**24 achados.** O saldo é desconfortável e é o ponto do exercício: **três correções da 1ª rodada
não corrigiam o que diziam corrigir**, e uma delas era justamente o achado que a 1ª rodada tinha
classificado como o mais perigoso.

#### Os cinco que reabrem defeito já dado por morto

| # | Achado | O que virou |
|---|---|---|
| 1 | **M40 estava vivo.** CT-10 afirmava "nenhum cenário é ignorado" **com asserções dentro do próprio CT-10** — elas passam enquanto os outros 24 cenários estão guardados pelo `is_dir` errado e todos ignorados. O cenário atestava a si mesmo | **CT-10 reescrito** como **inspeção estática do arnês** (`token_get_all`, o padrão que `HelpersDeTesteTest` já usa): o oráculo lê os arquivos de teste, não o resultado deles |
| 2 | **M4 estava vivo.** A defesa era "a cardinalidade do baseline é conferida contra os 32/83 do `00`" — e **cardinalidade não é identidade**. Um fixture gerado do README de hoje mais os títulos do site tem 32 e 83 entradas e passa. O SHA no topo era comentário; nada o conferia | **CT-24 novo**: re-deriva os títulos do commit anterior e compara título a título. É a mesma lição de "contagem sem identidade" que a 1ª rodada aplicou a CT-07 e ninguém propagou |
| 3 | **M61 — a correção de M10 criou o espelho de M10.** CT-19 só olhava a árvore inglesa. Uma cópia da árvore inglesa para dentro da portuguesa passava por CT-04, CT-05, CT-06, CT-19, CT-20 e CT-23: as páginas inglesas *são* inglesas, e a árvore de navegação escrita à mão é coerente | **CT-19 virou `Esquema` nos dois sentidos**. Lacuna de segunda ordem em estado puro: nasceu do conserto |
| 4 | **M63 — CT-12 comparava com um "antes" que ninguém guardava.** Ler o manifesto do commit corrente depois da migração devolve o estado já migrado; e o cenário dizia "o manifesto", no singular, deixando o de PHP de fora (**M62**) | **CT-12 sobre os dois manifestos**, contra o conjunto congelado no `## Fixture` — que já tinha sido estendido para isso nesta mesma revisão |
| 5 | **M64 — a simplificação de CT-18 levou junto os controles.** Tirar o filtro de cercas (correto: o Liquid não respeita cerca) deixou um `Então` universal-negativo sem controle, e um detector com erro de escape nunca casa nada e fica verde para sempre | **Controles de volta em CT-18**, agora sobre os dois delimitadores. Achado fechado na 1ª rodada, reaberto pela plataforma, fechado de novo |

#### Oráculos fracos e cenários soldados

| Cenário | Achado | Correção |
|---|---|---|
| CT-01 | afirmava sobre **o mapa**, não sobre o disco; e os 83 títulos de terceiro nível do fixture não eram consumidos por nenhum oráculo universal — CT-02 amostra 4 seções de 32, então M1 e M2 viviam em 28/32 | `Então` universal sobre os **115** títulos, cada um procurado no seu arquivo de destino |
| CT-22 | universal sobre conjunto que pode ser vazio, e **a mitigação escrita era falsa**: CT-14 conta links do README, CT-20 conta referências da navegação, nenhum prova que existe link *entre páginas* | piso próprio, ancorado nas referências cruzadas do baseline |
| CT-20 | mesmo buraco: com o diretório vazio, os dois `Então` são verdadeiros | piso de cardinalidade por idioma |
| CT-23 | terceiro `Então` **inexequível**: `Docker`, `Filament`, `Pest` são rótulos legítimos e idênticos nos dois idiomas. A nota alegava exceção "declarada no `Dado`", e o `Dado` não declarava nada — a relaxação óbvia deixa **M57 vivo** | trocado por **marcador lexical**, o mesmo de CT-19: nome próprio não carrega marcador, então não é falso positivo nem negativo |
| CT-15 | `Quando` **conjuntivo** (o diretório publicado **e** o de fluxos): dois comportamentos soldados, e a falha do primeiro escondia o segundo | dividido em **CT-15** (estrutura publicável, com página inicial **não vazia** — antes um arquivo de 0 byte passava) e **CT-25** (detector de publicação concorrente, **com controle positivo**: sem ele o teste não distingue "não há fluxo" de "meu detector não casa nada", e os dois fluxos legítimos do repositório servem de falso conforto) |
| CT-05 | o piso de 5 linhas vivia na premissa e nunca chegou ao `Então`; numa página de 6 linhas, 10% é meia linha | piso escrito no `Então` |

#### Contradições internas do documento

| # | Achado | Correção |
|---|---|---|
| 15 | **M10 ainda aparecia morto por CT-06 no índice** — a atribuição que o corpo declara falsa duas vezes | removida do índice |
| 18 | `## Cobertura das RQ` dizia "RQ-01 → … com CT-15 afirmando **publicação**", frase que a reescrita de R7 tornou falsa. **Cobertura ficcional** | ressalva escrita: RQ-01 depende de M43′, que não tem matador |
| 19 | `## Mapa de Regras` não listava CT-22 nem CT-23 — 21 de 23 cenários rastreados | mapa completo, com CT-24 e CT-25 |
| 20 | R10 tinha perfil `padrão` no mapa e `completo` na seção, e as áreas "navegação" e "mídia" **não existiam** na tabela de perfil — duas regras com risco nunca calculado | R10 herda **paridade**, R11 herda **dist**, e está escrito por quê |
| 21 | técnicas divergentes entre índice e regra em CT-09, CT-10, CT-22 e CT-23 | alinhadas |
| 22 | "51 com matador" não fechava: **M30** (prevenido por desenho) e **M26** (matador herdado de `KitUpdateTest.php:204-216`) não são mortos por nenhum cenário **deste** conjunto | contagem separada: **52 matadores efetivos**, 2 de outra origem, 5 declarados |
| 23 | CT-23 não aparecia em nenhuma linha da varredura SFDIPOT | acrescentado a `Structure`, junto com CT-25 |
| 24 | M43′ era contado como mutante com dono **e** declarado fora do alcance | conta como **sem matador**; o dono é o item 0 da verificação manual |

#### Achados 16 e 17 — já corrigidos antes de a revisão rodar

A revisão apontou que CT-17 exigia documentar um build local que a plataforma não tem, e que
CT-15 proibia em `docs/` o `Gemfile` que a ADR-02 permite. **Os dois já tinham sido corrigidos**
enquanto ela lia o arquivo — CT-17 passou a exigir que o texto diga que *não há* workflow nem
build, e CT-15 proíbe `package.json`, não "manifesto de qualquer linguagem". Ficam registrados
porque a coincidência é o ponto: eram contradições reais, achadas por dois caminhos independentes.

#### Achados registrados e **não** fechados

| # | Achado | Por que fica |
|---|---|---|
| 11 | CT-07 não define **quem constrói** o inventário: sem derivação declarada, é um array escrito à mão, e 79 se consegue digitando | Procede. **M20 já estava declarado sem matador**; a 2ª rodada mostra que **M15 também não morre por completo**. A derivação automática foi tentada (`token_get_all` sobre as suítes) e conta chamadas, não guardas efetivas. Fica como a segunda maior lacuna do conjunto, atrás de M43′ |
| 14 | `Quando` conjuntivo em **CT-21** ("cada referência de mídia **e** cada arquivo binário") e em **CT-05** | Aceito. Em CT-15 valia dividir porque eram dois sujeitos distintos; em CT-21 os quatro `Então` percorrem o mesmo diretório, e dividir criaria dois cenários com o mesmo `Dado` para ganhar só granularidade de relatório |

### Terceira rodada

**Devida pelo gatilho, recusada pelo teto.** O fechamento criou cenários novos (CT-24, CT-25 e o
`Esquema` de CT-19) e reescreveu CT-10 inteiro — superfície nova, que é o gatilho da re-revisão.
Mas a skill põe **teto de 2 rodadas**, e a razão do teto se aplica exatamente aqui: quando a
segunda rodada ainda traz achado **estrutural** — e trouxe, três correções que não corrigiam —, o
problema deixou de ser o conjunto e passou a ser o tamanho de algumas regras. **R1 e R4 concentram
a maior parte dos mutantes vivos e provavelmente deveriam ser quatro regras.** Registrado e
escalado, em vez de uma 3ª rodada que a skill não autoriza.

### Checklist do gate — estado real

- [x] Toda regra declara ≥2 mutantes (≥3 nas de perfil completo)
- [x] Todo mutante tem cenário matador **ou** lacuna declarada com motivo (5 declaradas)
- [x] Cada cenário na camada mais barata que o arnês sustenta (`tests/Kit`, com o motivo medido)
- [x] Teto do perfil respeitado — R1 e R4 usam 4 cenários cada, e a skill autoriza: rastreio de
      efeito consome o teto inteiro. R6 e R7 usam 3
- [x] Revisão adversarial — **2 rodadas independentes, 53 achados no total, todos endereçados**
- [x] Teto de 2 rodadas respeitado; o achado estrutural da 2ª foi escalado em vez de virar 3ª
- [ ] **Aberto: R1 e R4 provavelmente deveriam ser quatro regras** — decisão de quem implementar
