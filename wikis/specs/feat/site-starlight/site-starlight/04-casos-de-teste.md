# Casos de Teste — Migração do site para Astro Starlight

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando a implementação do spike — o que
> o spike forneceu foram **medições** (o fallback do Starlight, o `build.format` do Astro), e
> medição de comportamento de terceiro é a mesma fonte que a `.ai/rules/specs.md` exige: vendor
> lido, não vendor suposto.

## Numeração

Esta wiki **continua a numeração da ancestral**, que vai até `CT-25`. Os cenários novos são
`CT-26`..`CT-40`. O motivo é prático: os dois conjuntos convivem no mesmo arquivo de teste
(`tests/Kit/SiteDeDocumentacaoTest.php`), e ID repetido ali é ambíguo para quem lê a falha.

**`CT-25` é a exceção: ele é redefinido, não acrescentado.** A afirmação antiga (*"nenhum fluxo
de Actions publica o site"*) é o que esta feature revoga — ADR-03. O ID fica, a afirmação inverte.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — Publicação (workflow, `base`, origem do Pages) | 3 | 2 | 6 | padrão |
| B — Conteúdo transformado (front-matter, H1, links) | 3 | 2 | 6 | padrão |
| C — i18n (locales, paridade, slug) | 3 | 2 | 6 | padrão |
| D — **Redirects das URLs antigas** | 2 | **3** | 6 | padrão |
| E — Acoplamento com a suíte existente | 2 | 2 | 4 | padrão |

**Por que a área D leva Impacto 3**, e não é inflação: o site é **público**, e as URLs antigas
estão em lugares que o repositório não controla — issues, fóruns, favoritos, o índice dos
buscadores. Um `git revert` conserta o repositório e **não** conserta o link que alguém já
compartilhou nem o que o buscador já indexou. É a única parte desta entrega cujo erro não é
totalmente reversível por quem o cometeu.

- Técnicas: EP, BVA (contagem de arquivos), **matriz caminho × idioma**, rastreio de efeito
  (o guarda de build), normalização de caminho
- **Revisão adversarial: obrigatória** — disparada por Impacto 3 na área D
- Cenários: **17** (`CT-25` redefinido + `CT-26`..`CT-41`) · Regras: **10** · Mutantes previstos:
  **35** · Sem matador: 2 (declarados)

> As contagens são recalculadas por `grep`, nunca digitadas de memória: a primeira versão declarava
> 27 mutantes quando já eram 32, porque a revisão adversarial acrescentou cinco. `QA-05`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `site/` (projeto Astro, tema, stubs), `.github/workflows/pages.yml`, `.gitattributes`, `docs/**` transformado, `site-vitepress/` | CT-25, CT-26, CT-27, CT-39 |
| **F** | gerar o site, publicar, redirecionar URL antiga, servir dois idiomas, buscar | CT-25, CT-28, CT-36, CT-40 |
| **D** | 66 páginas markdown + 2 landings `.mdx` + 54 stubs. Cardinalidade importa: a **contagem** por idioma é o oráculo da paridade | CT-29, CT-30, CT-34, CT-37, CT-38 |
| **I** | não há rota de aplicação. As interfaces são **o build** (`npm run build`), **o workflow** e **o Pages**. É por isso que a camada é outra — ver `## Camada` | CT-40 |
| **P** | Node 24 + npm 11 no runner; o build nativo do Pages **deixa de ser** plataforma. `sharp` e `esbuild` exigem install scripts, que o npm 11 bloqueia por padrão — o `npm ci` do runner precisa resolver isso | CT-26 (declara o runner), e ver `## Lacunas` |
| **O** | quem usa: leitor da documentação (anônimo) e o agente de IA que varre `docs/`. O segundo é o que a transformação de front-matter mais afeta | CT-29 |
| **T** | **não se aplica**: sem concorrência, sem agendamento, sem expiração. O único "tempo" é a ordem dos passos 8 e 12 do PRD (trocar a origem antes de remover o Jekyll), e isso é procedimento, não comportamento testável | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Existe exatamente **um** publicador do site, e ele é o `pages.yml` | A (padrão) | RQ-04 | rastreio de efeito (presença × ausência) | CT-25 |
| R2 — O projeto Astro está configurado para os dois locales | A (padrão) | RQ-04, RQ-06 | EP | CT-26 |
| R3 — O Jekyll não publica mais | A (padrão) | RQ-04 | EP | CT-27 |
| R4 — O Starlight alcança o conteúdo em `docs/` | B (padrão) | RQ-09 | sentinela de configuração | CT-28 |
| R5 — O conteúdo está no formato do Starlight, não no do just-the-docs | B (padrão) | RQ-09 | EP + normalização | CT-29, CT-30, CT-31 |
| R6 — O tema define a cor nos **dois** esquemas | B (padrão) | RQ-03 | EP por esquema | CT-32, CT-33 |
| R7 — As duas árvores de idioma são espelho **por caminho** | C (padrão) | RQ-06, RQ-07 | matriz caminho × idioma | CT-34, CT-35 |
| R8 — Toda URL antiga de folha redireciona para uma página existente | D (padrão, **I3**) | RQ-08 | EP + BVA de contagem | CT-36, CT-37, CT-38 |
| R9 — O plano B continua executável, e o guarda de build não pode sumir | A/E (padrão) | RQ-05 | rastreio de efeito | CT-39, CT-40 |
| R10 — O conversor é reexecutável | B (padrão) | RQ-09 | rastreio de efeito | CT-41 |

**Técnica escalada acima do perfil**: nenhuma. **Rebaixada**: nenhuma.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `site/` como nome do diretório | escolha de implementação — o requisito não nomeia diretório | detalhe do cenário |
| `pages.yml` como nome do workflow | idem | detalhe do cenário; o que o cenário afirma é *"um único publicador"* |
| `glob({ base: '../docs' })` | mecanismo. O requisito não pede que o conteúdo fique em `docs/` | CT-28 afirma o **efeito** (o Starlight lê de lá), não a API |
| `DOCS_BASE` como nome da variável | escolha de implementação | detalhe |
| **Os stubs em `public/` com `meta refresh`** | mecanismo — o requisito pede "redirects", não a técnica | CT-36 afirma que a URL antiga leva a uma página existente |
| A rampa de cor `#ff8577` | escolha de implementação | CT-32 afirma que **existe** rampa nos dois esquemas, não qual |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):
- **RQ-01 não é testável como está** — bloqueia uma afirmação direta sobre "moderno/bonito".
  Premissa adotada: o oráculo é o julgamento já emitido pelo solicitante; os cenários cobrem as
  **propriedades falsificáveis que sustentam a aparência** (CT-30, CT-31, CT-32, CT-33).
  **Se negado**: CT-30..CT-33 continuam válidos, e entra um critério visual declarado.
- **RQ-06 chegou sem objeto** — premissa: era o tratamento de i18n como um todo, e a resposta do
  solicitante sobre slugs a confirma. **Se negado**: R7 muda de escopo e vira wiki própria.

## Camada

**Todos os cenários rodam na suíte `Kit` do Pest, afirmando sobre arquivo em disco.** Não há
código de aplicação nesta feature — não há rota, model, policy nem componente.

A decisão que importa é **o que se afirma sobre a fonte e o que se afirma sobre o build**:

| Afirmação | Onde vive | Por quê |
|---|---|---|
| configuração, conteúdo, tema, stubs, workflow | **Pest (`Kit`)** | arquivo em disco, custo de milissegundos, roda em toda suíte |
| links internos resolvem · redirects chegam a página existente · a sentinela do loader aparece | **`site/verifica-links.mjs`, no workflow** | exigem o `dist/`, e construir o site dentro do Pest acrescentaria Node à suíte do kit — o que a ADR-02 ancestral proíbe, e o `[CT-12]` reprova |

Essa divisão tem um buraco óbvio: **um guarda que mora no workflow pode ser removido do workflow
sem ninguém notar.** É exatamente o que `CT-40` fecha.

> **Divergência declarada com a skill**: ela sugere `pest --parallel --tia` como padrão. A rule
> do projeto e o `composer.json` usam `--parallel` só no `test:kit`, e não há driver de cobertura
> instalado (sem PCOV/Xdebug o `--tia` não roda). **A rule do projeto vence**; o comando desta
> feature é `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`.

## Setup Global

### Personas
**Não se aplica** — nenhum cenário autentica. O site é público e estático.

### Fixtures
Nenhuma factory. O universo é o **repositório em disco**.

### Fakes
Nenhum. Não há HTTP, fila, e-mail nem evento.

### Estratégia de DB
**Não se aplica** — nenhum cenário toca o banco.

### Sentinela da árvore do kit
Todo cenário herda o `beforeEach` de `tests/Kit/SiteDeDocumentacaoTest.php`, que pula fora da
árvore do kit — `site/` e `docs/` são `export-ignore` e não existem no projeto instalado. A
sentinela é `.github`, **nunca** o próprio diretório do site, que seria autoanulante
(`[CT-10]` de `RedeDeDocumentacaoTest`).

---

## Regra R1 — Existe exatamente um publicador do site

> `RQ-04` · área A · técnica: **rastreio de efeito** (presença **e** ausência)

**Esta regra substitui a afirmação de `CT-25` da wiki ancestral.** Lá o cenário provava que
**nenhum** workflow publicava; aqui ele prova que **exatamente um** publica. A inversão é a
decisão da ADR-03, e o cenário é reescrito em vez de removido porque a rede não pode perder a
afirmação sobre o mecanismo de publicação — que é justamente o que mudou.

```gherkin
# language: pt
Funcionalidade: Publicação do site de documentação

  Regra: Exatamente um fluxo de Actions publica o site

    Cenário: [CT-25] o site tem um publicador, e só um
      Dado o diretório de workflows do repositório
      Quando os fluxos são varridos por um detector de publicação de Pages
      Então exatamente um fluxo casa o detector
      E esse fluxo é o que declara o ambiente `github-pages`
      E o detector casa uma linha plantada, provando que ele não está cego
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o workflow de publicação é removido, e o site congela na última versão | CT-25 (exige ≥1) |
| M2 | um segundo workflow de publicação é acrescentado, e os dois disputam o deploy | CT-25 (exige ≤1) |
| M3 | o detector deixa de casar qualquer coisa após uma renomeação da action, e o cenário fica verde sobre lista vazia | CT-25 (controle positivo com a linha plantada) |

---

## Regra R2 — O projeto Astro está configurado para os dois locales

> `RQ-04`, `RQ-06` · área A · técnica: **EP** (um exemplo por locale)

```gherkin
  Regra: O gerador do site é o Starlight, com os dois idiomas declarados

    Cenário: [CT-26] a configuração declara o Starlight e os dois locales
      Dado a configuração do projeto Astro
      Então a integração do Starlight está registrada
      E existe um locale "pt" cujo idioma declarado é "pt-BR"
      E existe um locale "en" cujo idioma declarado é "en"
      E o locale padrão é "pt"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | o `en` é esquecido na configuração, e o Starlight serve só português | CT-26 |
| M5 | o `lang` do `pt` fica como `pt` em vez de `pt-BR`, e o Pagefind indexa com o idioma errado | CT-26 |
| M6 | o `defaultLocale` aponta para um locale que não existe, e o `/` redireciona para 404 | CT-26 |

---

## Regra R3 — O Jekyll não publica mais

> `RQ-04` · área A · técnica: **EP** (a configuração existe / não existe)

```gherkin
  Regra: O gerador antigo é removido depois que o novo está no ar

    Cenário: [CT-27] o repositório não tem mais configuração de Jekyll
      Dado a raiz do diretório de conteúdo
      Então não existe `_config.yml`
      E nenhum arquivo do site declara `remote_theme`
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | o `_config.yml` fica para trás, e um `Source: branch` acidental republica o Jekyll por cima | CT-27 |
| M8 | o tema remoto continua declarado em outro arquivo, confundindo quem for manter | CT-27 |

---

## Regra R4 — O Starlight alcança o conteúdo em `docs/`

> `RQ-09` · área B · técnica: **sentinela de configuração**

O `glob` com `base` fora da raiz do Astro é suportado e **foi medido** (ADR-02), mas é uso
incomum: um upgrade pode apertá-lo. O cenário não afirma a API — afirma o **efeito**.

```gherkin
  Regra: O conteúdo publicado é o de `docs/`, não uma cópia

    Cenário: [CT-28] a coleção do Starlight aponta para a árvore de conteúdo do kit
      Dado a configuração de coleções do projeto Astro
      Então a coleção de documentação declara uma base fora da raiz do projeto Astro
      E essa base resolve para o diretório de conteúdo do kit
      E não existe uma segunda árvore de conteúdo dentro do projeto Astro
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | alguém "arruma" o layout movendo o conteúdo para dentro do projeto Astro, e as 9 referências a `docs/` da suíte quebram | CT-28 (exige a base fora) |
| M10 | a cópia antiga do spike fica esquecida dentro do projeto Astro, e passam a existir duas fontes da verdade, divergindo em silêncio | CT-28 (exige que não haja segunda árvore) |
| M11 | a base aponta para um diretório que não existe, e o build sai com zero páginas sem erro | ⚠️ **sem matador na suíte do Pest** — ver `## Lacunas`; morto no workflow pelo `verifica-links.mjs`, que tem piso de população |

---

## Regra R5 — O conteúdo está no formato do Starlight

> `RQ-09`, `RQ-01` · área B · técnica: **EP** + normalização

Três cenários, porque são três defeitos diferentes com causas diferentes: front-matter velho
(o just-the-docs deixa chaves que o Starlight ignora em silêncio), título duplicado (o Starlight
renderiza o `title`, então o H1 do corpo aparece duas vezes) e entrada duplicada na navegação.

```gherkin
  Regra: Toda página está no formato que o Starlight espera

    Cenário: [CT-29] nenhuma página carrega front-matter do gerador antigo
      Dado todas as páginas das duas árvores de idioma
      Então nenhuma declara `parent`, `grand_parent`, `has_children` ou `nav_order`
      E toda página declara `title`
      E toda página declara `description`

    Cenário: [CT-30] nenhuma página repete o título no corpo
      Dado todas as páginas das duas árvores de idioma
      Quando o corpo de cada uma é lido a partir do fim do front-matter
      Então nenhum corpo começa com um título de nível 1

    Cenário: [CT-31] o índice de cada seção não repete o nome do grupo na navegação
      Dado as páginas de índice de seção das duas árvores
      Então cada uma declara um rótulo de barra lateral próprio
      E esse rótulo é diferente do título da própria página
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | o conversor roda só numa árvore de idioma, e a outra fica no formato antigo | CT-29 (varre as duas) |
| M13 | o `nav_order` é copiado como está e o Starlight o ignora, embaralhando a ordem da barra lateral | CT-29 |
| M14 | a `description` não é gerada, e todo cartão de compartilhamento e resultado de busca sai com texto inventado pelo gerador | CT-29 |
| M15 | o H1 é removido só da primeira página de cada diretório | CT-30 (varre todas) |
| M16 | o H1 é removido por um regex que casa em qualquer posição, apagando um cabeçalho do meio do texto | ⚠️ **sem matador direto** — ver `## Lacunas` |
| M17 | o rótulo de índice é aplicado só ao português, e a árvore inglesa mostra "Features › Features" | CT-31 (varre as duas) |

---

## Regra R6 — O tema define cor nos dois esquemas

> `RQ-03` · área B · técnica: **EP por esquema** (escuro / claro)

O defeito que originou esta regra foi **medido em navegador**: redefinir uma variável de cinza do
tema no seletor global deixou o chip de código inline ilegível **só no tema claro** — bloco quase
preto com texto escuro —, enquanto o escuro ficava impecável. O cenário existe para que a próxima
variável global redefinida não passe pelo mesmo caminho.

```gherkin
  Regra: A identidade visual é definida nos dois esquemas de cor

    Cenário: [CT-32] a rampa de acento existe no claro e no escuro
      Dado a folha de estilo da identidade do kit
      Então ela define a rampa de acento no seletor global
      E ela redefine a rampa de acento no seletor do tema claro

    Cenário: [CT-33] nenhuma variável de cinza do tema é redefinida no seletor global
      Dado a folha de estilo da identidade do kit
      Quando as declarações do seletor global são lidas
      Então nenhuma variável de cinza do tema aparece entre elas
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | a rampa é definida só no escuro, e o tema claro herda o acento padrão do Starlight, perdendo a identidade | CT-32 |
| M19 | uma variável de cinza volta a ser redefinida no global "para esquentar o tema escuro", e o tema claro quebra de novo | CT-33 |
| M20 | a folha de estilo deixa de ser carregada pela configuração, e nada do tema se aplica | CT-32 (lê a folha que a configuração declara) |

---

## Regra R7 — As duas árvores de idioma são espelho por caminho

> `RQ-06`, `RQ-07` · área C · técnica: **matriz caminho × idioma**

**Esta regra é o que torna `RQ-07` falsificável.** Manter o slug em português é uma **não-ação**,
e não-ação não deixa rastro — o que se testa é a **consequência** dela, que é o contrato do i18n
do Starlight: tradução casa por **caminho idêntico**. Um slug traduzido quebra o espelho, e é o
espelho que o cenário afirma.

```gherkin
  Regra: Cada página existe nas duas árvores, no mesmo caminho relativo

    Cenário: [CT-34] nenhum caminho existe num idioma e falta no outro
      Dado as duas árvores de idioma
      Quando os caminhos relativos de cada uma são comparados
      Então o conjunto de caminhos do português é igual ao do inglês
      E a comparação cobriu ao menos trinta caminhos

    Cenário: [CT-35] os locales declarados são exatamente as árvores que existem
      Dado os locales declarados na configuração
      E os diretórios de idioma que existem no conteúdo
      Então os dois conjuntos são iguais
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | um slug inglês é traduzido, o espelho quebra e o Starlight passa a servir a página portuguesa sob `/en/` como fallback | CT-34 |
| M22 | uma página nova entra só em português, e o inglês recebe o fallback em silêncio | CT-34 |
| M23 | um locale é declarado sem árvore correspondente, e o seletor de idioma leva a 404 | CT-35 |
| M24 | a comparação passa sobre dois conjuntos vazios, porque a varredura olhou o diretório errado | CT-34 (piso de população) |

---

## Regra R8 — Toda URL antiga de folha redireciona para uma página existente

> `RQ-08` · área D (**Impacto 3**) · técnica: **EP** + **BVA de contagem**

```gherkin
  Regra: As URLs publicadas pelo gerador antigo continuam levando ao conteúdo

    Cenário: [CT-36] todo redirecionamento aponta para uma página que existe
      Dado todos os arquivos de redirecionamento do site
      Então cada um declara um destino
      E para cada destino existe a página correspondente na árvore de conteúdo

    Cenário: [CT-37] rota que não mudou de forma não ganha redirecionamento
      Dado os índices de seção das duas árvores
      Então nenhum deles tem arquivo de redirecionamento

    Cenário: [CT-38] toda página de folha tem o seu redirecionamento
      Dado todas as páginas de folha das duas árvores
      Então a quantidade de arquivos de redirecionamento é igual à quantidade de folhas
```

**Por que `CT-37` existe**: `/pt/comecar/` tinha a mesma forma nos dois geradores. Um redirect ali
é ruído — e ruído numa lista de 54 é o que faz ninguém reler a lista. É a regra da legenda da
matriz aplicada a redirect: a afirmação só vale se a ausência também for afirmada.

**Por que `CT-38` usa contagem e não amostra**: a checagem "todo stub aponta para página existente"
(`CT-36`) fica **verde com zero stubs**. `CT-38` é o piso que impede a regra inteira de virar
vácuo, e a contagem é derivada da árvore — não digitada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | os stubs entram no `.gitignore` "porque são gerados", e somem no primeiro clone limpo | CT-38 (contagem cai a zero) |
| M26 | uma página é renomeada e o stub antigo continua apontando para o caminho velho | CT-36 |
| M27 | o conversor gera stub também para índice de seção, inflando a lista com 10 redirects para lugar nenhum | CT-37 |
| M28 | o conversor deixa de gerar stub para uma árvore de idioma inteira | CT-38 |

---

## Regra R9 — O plano B continua executável e o guarda de build não some

> `RQ-05` · áreas A e E · técnica: **rastreio de efeito**

```gherkin
  Regra: A alternativa registrada continua sendo uma alternativa de verdade

    Cenário: [CT-39] o plano B está completo e declara quando trocar
      Dado o diretório da alternativa
      Então ele tem manifesto de dependências, configuração e conteúdo próprio
      E o seu README declara os gatilhos de troca

  Regra: O conferidor de links roda antes de o site ir ao ar

    Cenário: [CT-40] o fluxo de publicação roda o conferidor antes de publicar
      Dado o fluxo de publicação do site
      Então ele invoca o conferidor de links
      E a invocação vem antes do passo que envia o artefato
```

**`CT-40` é o cenário que fecha o buraco da divisão de camadas.** Os guardas de link e de
redirect moram no Node, fora da suíte do Pest — e um passo de workflow some com uma linha
apagada, sem nada ficar vermelho. Este cenário é a única coisa que torna essa remoção visível.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M29 | o diretório da alternativa é apagado "porque ninguém usa", e o plano B vira parágrafo | CT-39 |
| M30 | o README perde os gatilhos e vira só instruções de instalação, sem dizer **quando** trocar | CT-39 |
| M31 | o passo do conferidor é removido do workflow para o build ficar mais rápido | CT-40 |
| M32 | o conferidor roda **depois** do envio, e o site quebrado vai ao ar mesmo com o job vermelho | CT-40 (exige a ordem) |

---

## Checklist de Taxonomia

> Resposta válida: um ID, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`.

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: o site é estático e público; não há recurso por `{id}`, não há sessão |
| Autorização exercida na ação | **não se aplica**: sem autorização. As permissões são do workflow, e são de infraestrutura |
| Idempotência (ancorada no agregado) | **não se aplica**: o build é uma função pura da árvore de arquivos; rodá-lo duas vezes produz o mesmo `dist/` |
| Concorrência | **não se aplica**: o `concurrency.group` do workflow serializa deploys. Sem contador, saldo ou estoque |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: não há gravação. A única "entrada" é `DOCS_BASE`, e ela é de build |
| Domínio condicionado (tipo × valor) | **não se aplica**: sem campo discriminador |
| Estado × operação de escrita | **não se aplica**: sem entidade com ciclo de vida |
| Ausente ≠ null ≠ vazio | CT-29 (`description` ausente é o caso que importa) |
| Paginação / ordenação | **não se aplica**: o site não pagina. A ordem da barra lateral é coberta por CT-29 (`nav_order` migrado) |
| Timezone / DST | **não se aplica**: nenhuma afirmação depende de instante |
| Unicode / limite de varchar | **não se aplica**: sem banco. O conteúdo já é UTF-8 e migra sem reencodar |
| Unicidade + soft delete | **não se aplica**: sem banco |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: sem formulário nem payload |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| Superfície Livewire | **não se aplica**: o diff não toca `app/` nem `resources/` — medido no `02`, com a varredura colada |
| Estado do framework usado sem validar | **não se aplica**: sem Livewire |
| IDOR por entidade | **não se aplica**: sem tabela |
| Escopo com discriminante nulo | **não se aplica**: sem query |
| **Saída do estado de erro** | CT-36 — o redirect **é** o estado de erro desta feature (a URL antiga), e o cenário afirma que ele tem destino alcançável |
| **Cardinalidade (0 / 1 / N)** | CT-38 (o caso **zero** é o mutante M25) e CT-34 (piso de população) |
| **Guarda que mora fora da suíte** | CT-40 |
| **Regressão contra a wiki ancestral** | `composer test` completo — `CT-01`..`CT-24` continuam valendo sem alteração; `CT-25` é redefinido aqui |

## Lacunas Declaradas

Duas, com o que foi tentado:

1. **M11 — base do loader apontando para diretório inexistente.** Tentado afirmar isso no Pest
   lendo a configuração e resolvendo o caminho: o cenário fica **circular**, porque quem resolve o
   caminho de verdade é o Astro, com as regras dele de resolução relativa. Afirmar a string não
   prova que o Astro a resolve. **Coberto no workflow**: o `verifica-links.mjs` tem piso de
   população (`toBeGreaterThan`), e base quebrada produz zero páginas e zero links, reprovando lá.
2. **M16 — regex de remoção de H1 casando fora do início.** O cenário `CT-30` prova que nenhum
   corpo **começa** com H1; ele não prova que nenhum H1 do **meio** foi apagado por engano.
   Tentado comparar a contagem de cabeçalhos antes e depois, e esbarrou em um problema real: o
   "antes" é o estado do git, e um teste que compara com `git show HEAD~n` amarra a suíte ao
   histórico — que é do git, não do conteúdo. **Mitigado, não coberto**: o baseline congelado de
   115 títulos do `[CT-01]` ancestral compara os títulos do site com a medição de antes da
   migração original, e um H1 apagado do meio do texto cai lá.

## Perguntas para o `00-requisito.md`

Já incorporadas em `## Ambiguidades` do `00` — nenhuma pendente desta derivação.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-25 | um publicador, e só um | R1 | rastreio de efeito | Kit | `tests/Kit/SiteDeDocumentacaoTest.php` | M1, M2, M3 |
| CT-26 | Starlight e os dois locales declarados | R2 | EP | Kit | idem | M4, M5, M6 |
| CT-27 | sem configuração de Jekyll | R3 | EP | Kit | idem | M7, M8 |
| CT-28 | a coleção aponta para `docs/` | R4 | sentinela de configuração | Kit | idem | M9, M10 |
| CT-29 | sem front-matter do gerador antigo | R5 | EP | Kit | idem | M12, M13, M14 |
| CT-30 | nenhum corpo começa com H1 | R5 | normalização | Kit | idem | M15 |
| CT-31 | índice de seção com rótulo próprio | R5 | EP | Kit | idem | M17 |
| CT-32 | rampa de acento nos dois esquemas | R6 | EP por esquema | Kit | idem | M18, M20 |
| CT-33 | sem cinza do tema no seletor global | R6 | EP por esquema | Kit | idem | M19 |
| CT-34 | as árvores são espelho por caminho | R7 | matriz caminho × idioma | Kit | idem | M21, M22, M24 |
| CT-35 | locales declarados = árvores existentes | R7 | EP | Kit | idem | M23 |
| CT-36 | todo redirect tem destino existente | R8 | EP | Kit | idem | M26 |
| CT-37 | índice de seção sem redirect | R8 | ausência afirmada | Kit | idem | M27 |
| CT-38 | toda folha tem redirect | R8 | BVA de contagem | Kit | idem | M25, M28 |
| CT-39 | o plano B está completo | R9 | rastreio de efeito | Kit | idem | M29, M30 |
| CT-40 | o conferidor roda antes de publicar | R9 | rastreio de efeito + ordem | Kit | idem | M31, M32 |
| CT-41 | toda folha conserva a posição na navegação | R10 | rastreio de efeito | Kit | idem | M33, M34, M35 |

## Sem CT-B

**Não há casos de teste de browser, e o motivo é estrutural, não de orçamento.**

O `pest-plugin-browser` sobe **a aplicação Laravel** em processo — ele não tem como servir um site
estático gerado por outro toolchain. Um CT-B aqui não seria caro, seria **impossível**.

A verificação em navegador real **existe** e foi o que encontrou os quatro defeitos de cor: é o
`site/capturas.mjs`, que dirige o Playwright contra a prévia local e produz imagens nos dois temas,
em desktop e celular, para o agente olhar. Ela é **observação**, não cobertura — exatamente o
estatuto que a skill dá ao Playwright MCP. O que ela achou virou `CT-32` e `CT-33`, que são
falsificáveis e rodam na suíte.

---

## Regra R10 — O conversor é reexecutável

> `RQ-09` · área B · técnica: **rastreio de efeito sobre o próprio artefato**

**Esta regra nasceu do step 7.5, depois de o `04` estar fechado — e o cenário foi escrito como
teste ANTES de existir aqui, o que é a Proibição 11 da `feature-test-design` (teste derivado do
código). O quality gate pegou como `QA-03`, e esta seção é a correção: a regra, o Gherkin e os
mutantes passam a existir, e o teste passa a derivar deles.**

O conversor é uma ferramenta, não um script de uma vez só: ele existe para ser rodado de novo
quando uma página nova chega. Rodá-lo duas vezes degradava o conteúdo — mudava 55 arquivos,
apagava `sidebar.order` de 32 páginas e embaralhava a barra lateral — **sem erro, sem aviso, com
o build verde**. Ferramenta que degrada em silêncio é pior que ferramenta que falha.

A causa é de leitura: o front-matter do just-the-docs era plano (`nav_order: 3`) e o do Starlight
aninha sob `sidebar:`. O leitor só enxergava chave de topo.

```gherkin
  Regra: Rodar o conversor de novo não muda o que já está convertido

    Cenário: [CT-41] toda página de folha conserva a sua posição na navegação
      Dado todas as páginas de folha das duas árvores de idioma
      Então cada uma declara a sua ordem na barra lateral
      E a varredura cobriu ao menos quarenta folhas
```

**Por que o cenário afirma sobre a ORDEM e não sobre "rodar duas vezes":** rodar o conversor dentro
do Pest modificaria `docs/` durante a suíte, o que é inaceitável. O que o caso afirma é a
**consequência observável** da degradação — a ordem perdida —, que é o que fica no disco e o que
alguém commitaria sem perceber. A reexecutabilidade em si foi provada por medição no step 7.5:
três passadas seguidas, `git diff` vazio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M33 | o leitor de front-matter volta a exigir chave no início da linha, e a segunda passada apaga `sidebar.order` | CT-41 |
| M34 | a ordem passa a ser herdada só de `nav_order`, que não existe mais depois da primeira passada | CT-41 |
| M35 | a varredura de folhas quebra e o caso fica verde sobre lista vazia | CT-41 (piso de 40) |

---

## Revisão Adversarial — 2026-09-19

Disparada por **Impacto 3 na área D** (redirects). Sub-agente que não derivou os cenários,
recebendo apenas o `00` e o `04` — sem o PRD, sem as ADRs, sem o código.

Ela devolveu **5 implementações erradas plausíveis** que passariam por todos os 16 cenários e
**14 lacunas**. Duas das cinco eram **defeitos reais já presentes no código**, não hipóteses.

### O que cada achado virou

| # | Lacuna | Virou |
|---|---|---|
| L2 | o destino do redirect não carrega o `base`; todos os 54 dariam 404 em produção | **defeito real, corrigido**: o destino passou a ser RELATIVO, o que dispensa o `base` inteiro. `CT-36` afirma que nenhum destino é absoluto |
| L4 | o conferidor fica verde sobre `dist/` vazio, e o passo não reprova | **defeito real, corrigido**: piso de população (60 páginas, 1.000 links) e `process.exitCode = 1`. `CT-40` proíbe `continue-on-error` |
| L13 | *"declara um destino"* é satisfeito por um `<a href>`; nada exige redirecionar sozinho | fechado em `CT-36`: exige `content="0; url=` |
| L7 | `description` derivada do slug passa em `CT-29`; nada exige conservação do título | fechado nos dois lados: `CT-29` recusa `description` vazia, igual ao título ou começando por imagem; e o conversor passou a fazer `title ← H1` |
| L6 | `CT-29`, `CT-30` e `CT-31` sem piso de população | fechado: os três têm piso (`> 60` páginas, `= 10` índices) |
| L5 | `CT-38` verde para `0 == 0` | fechado: piso de 40 folhas antes da igualdade |
| L11 | asserção de ausência sem controle positivo em `CT-27`, `CT-33`, `CT-37` | fechado em `CT-33` (exige que o bloco global exista antes de afirmar o que não há dentro). `CT-27` e `CT-37` seguem sem controle — declarado abaixo |
| L14 | nada afirma a forma da rota servida | fechado em `CT-36`, que resolve o destino contra o arquivo real |
| L9 | o workflow poderia publicar o artefato errado | fechado em `CT-25` + `CT-40`: um único publicador, e ele envia `site/dist` depois de rodar o build do Astro |
| L12 | o plano B é conferido por texto, não por efeito | **parcial**: `CT-39` confere manifesto, config, conversor e os gatilhos do README. Rodar `npm install` do plano B na suíte do Pest traria Node para dentro dela, o que a ADR-02 ancestral proíbe |

### Achados aceitos e NÃO fechados — declarados

1. **L1 — não há inventário congelado das URLs antigas.** Os redirects são derivados da árvore
   nova, e a árvore nova é também a fonte do defeito que eles corrigiriam: é circular. Funciona
   hoje porque a forma da rota mudou de um jeito só (`x.html` → `x/`), e `CT-36` resolve cada
   destino contra o arquivo real. Um baseline congelado das URLs que o Jekyll publicava seria mais
   forte, e não foi feito.
2. **L3 — nenhum cenário do Pest afirma sobre o site CONSTRUÍDO.** Os 16 afirmam sobre arquivo em
   disco. Quem segue as URLs no `dist/` é o `verifica-links.mjs`, no workflow. `CT-40` garante que
   ele roda e reprova, o que fecha metade do buraco — a outra metade é confiar no script.
3. **L8 — o contraste não é calculado.** `CT-32` afirma que existe rampa nos dois esquemas, não
   que ela é legível. Os valores hexadecimais estão em disco e o cálculo é aritmética simples;
   não foi feito, e o oráculo de legibilidade continua sendo a captura em navegador.
4. **L10 — renomeação SIMÉTRICA dos slugs passa.** `CT-34` afirma o espelho entre as árvores, não
   o idioma do segmento. Trocar `recursos` por `features` nos dois idiomas mantém o espelho, passa
   no caso e invalida as 54 URLs antigas de uma vez. É a lacuna mais próxima de virar defeito.

### O achado estrutural, e o que fizemos com ele

> *"12 dos 16 cenários não têm `Quando` nenhum. Para uma feature cuja tese de risco é 'o leitor
> segue uma URL antiga', a ausência de um `Quando` que SIGA alguma coisa é o defeito de derivação
> central."*

Aceito, e é a mesma coisa que L3 vista de outro ângulo. A resposta honesta é a divisão de camadas
declarada em `## Camada`: o Pest não constrói o site — fazê-lo traria Node para a suíte do kit, o
que o `[CT-12]` reprova. O que o Pest **pode** afirmar, e passou a afirmar, é que o guarda que
segue as URLs existe, roda antes do envio e reprova de verdade.
