# Requisito — Diagramas da arquitetura do kit no README e no site

> **Fonte da verdade.** Captura verbatim do pedido, antes de qualquer interpretação.

## Fonte

- **Origem**: mensagem do mantenedor no chat do Claude Code, invocando `/feature-wiki`
- **Data**: 2026-09-27
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> existe um site que transforma qualquer repositorio no github em um driagrama, rodei no pacote e esse é o resultado: "https://gitdiagram.com/gsferro/filament-starter-kit-easy"
>
> * analise-o completamente e estude as funcionalidades que o site fornece
> * o site que passei, tem uma opção de export, tanto em phn quanto em mermaid
> * veja a viabilidade de adiciona-lo no @README.md e talvez exibir os diagramas dentro das docs e site para exemplificar graficamente como o kit funciona
> * seguindo essa linha, estude a possibilidade de adicionarmos mais graficos, diagramas de casos de uso e de sequencia e outras representações graficar da arquitetura do kit
> * tudo isso tem que refletir o que o kit já implementa disponibilizando tanto no readme quanto no site do kit

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O diagrama que o GitDiagram gerou para este repositório é analisado por inteiro, elemento a elemento, contra o que o kit implementa | "rodei no pacote e esse é o resultado" + "analise-o completamente" | funcional |
| RQ-02 | As funcionalidades que o GitDiagram oferece são levantadas e registradas | "estude as funcionalidades que o site fornece" | funcional |
| RQ-03 | As duas saídas de export do GitDiagram — imagem e Mermaid — são avaliadas como insumo para o kit | "tem uma opção de export, tanto em phn quanto em mermaid" | funcional |
| RQ-04 | A viabilidade de levar o GitDiagram ao `README.md` é avaliada, com decisão registrada | "veja a viabilidade de adiciona-lo no @README.md" | funcional |
| RQ-05 | A viabilidade de exibir diagramas dentro da documentação e do site é avaliada, com decisão registrada | "talvez exibir os diagramas dentro das docs e site" | funcional |
| RQ-06 | Os diagramas servem para mostrar graficamente **como o kit funciona** — não são decoração | "para exemplificar graficamente como o kit funciona" | restrição |
| RQ-07 | Diagramas de **casos de uso** são estudados | "diagramas de casos de uso" | funcional |
| RQ-08 | Diagramas de **sequência** são estudados | "e de sequencia" | funcional |
| RQ-09 | **Outras representações gráficas da arquitetura** são estudadas | "e outras representações graficar da arquitetura do kit" | funcional |
| RQ-10 | Todo diagrama publicado reflete o que o kit **já implementa** — nada de funcionalidade inexistente, planejada ou inferida | "tudo isso tem que refletir o que o kit já implementa" | restrição |
| RQ-11 | Os diagramas ficam disponíveis no **README** | "disponibilizando tanto no readme" | funcional |
| RQ-12 | Os diagramas ficam disponíveis no **site do kit** | "quanto no site do kit" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-03** — "phn" não é formato conhecido.
  - **Assumido**: PNG (erro de digitação — `n` e `g` são vizinhos no teclado, e PNG é a exportação de imagem usual)
  - **Se negado**: RQ-03 muda de formato; só a avaliação da saída de imagem é refeita
- **RQ-04** — "adiciona-lo" tem dois referentes possíveis: **o site** (link ou badge para o GitDiagram) ou **o diagrama** que ele gerou (a imagem ou o Mermaid exportado).
  - **Assumido**: os dois são avaliados, e a decisão diz qual entra
  - **Se negado**: a avaliação do referente descartado sai do `01`
- **RQ-05 / RQ-12** — "docs e site": o `docs/` é a fonte do site Starlight, então "docs" e "site" podem ser o mesmo artefato — ou "docs" pode incluir as `wikis/`.
  - **Assumido**: `docs/` (pt e en), que é o que o site publica. `wikis/` fica de fora
  - **Se negado**: as `wikis/` entram como terceiro destino
- **RQ-11 / RQ-12** — o pedido cita `@README.md` (o pt). O kit mantém `README.en.md` e `docs/en/` em paridade com o pt.
  - **Assumido**: os dois idiomas, porque é a convenção do repositório e os testes de documentação a cobram
  - **Se negado**: só pt; a paridade vira dívida declarada
- **RQ-07 / RQ-08 / RQ-09** — "estude a possibilidade" pede estudo, mas o último item pede que "tudo isso" seja disponibilizado.
  - **Assumido**: o estudo escolhe um conjunto de diagramas, e o conjunto escolhido é implementado nesta entrega
  - **Se negado**: a entrega para no estudo (esta wiki) e a implementação vira outra
- **RQ-10** — um diagrama é uma afirmação sobre o código, e o código muda. "Tem que refletir" vale no dia da publicação ou também depois?
  - **Assumido**: também depois — cada diagrama precisa de uma guarda que falhe quando o código deixar de bater com ele, como os números do README já têm (`tests/Kit/SiteDeDocumentacaoTest.php`)
  - **Se negado**: os diagramas nascem sem guarda, e a defasagem futura vira dívida declarada

### Perguntas da derivação dos casos de teste (`feature-test-design`, 2026-09-27)

> Devolvidas pela derivação do `04` (dois ciclos de revisão adversarial). Cada uma traz o que foi **assumido** e o
> custo **se negado**. As premissas valem até o mantenedor negar. Duas foram decididas pela sessão:
>
> - **P-03 → decidido: entram os cinco docblocks, mais o irmão `GuardaPrompt.php:19`.** O do `KitInstall.php:27`
>   ("Nenhum passo aborta a instalação") tem a contradição **confirmada** pelo crítico da pesquisa (D5:
>   `BancoSqlite::recriar` lança `RuntimeException` sem captura com `--force` e o SQLite preso)
> - **P-06 → resolvido pelo Adendo 2**: a opção escolhida ("1 diagrama + link") já dizia "o Packagist
>   provavelmente mostra o código cru" — o mantenedor aceitou esse custo ao escolhê-la
>
> **Confirmações do mantenedor, 2026-09-28** (respostas verbatim às três perguntas que a sessão levou):
>
> > "Quanto rigor os diagramas de estado (conta e convite) devem ter? [...]"="Seta com condição (Recomendado)", "Qual o tamanho da suíte de guardas?"="Os 104 cenários (Recomendado)", "Os GIFs novos vão em art/, que viaja no composer create-project (hoje 8,9 MB). Impõe teto de peso?"="Sem teto, medido (Recomendado)"
>
> - **P-29 e P-30 → confirmadas**: seta com a condição que o código usa, curta (`aprovar [estava ativa]`); a matriz
>   completa de situações × eventos mora só no teste
> - **O `04` inteiro é o contrato** (104 cenários, 259 mutantes), inclusive as trocas de técnica do ciclo 2 (P-31)
> - **P-10 → confirmada**: sem teto de peso; o tamanho de cada GIF sai medido no CHANGELOG

- **RQ-23 (ER)** — "ER do núcleo": quais entidades são o núcleo?
  - **Assumido (P-01)**: os 7 models de `app/Models` e as tabelas de ligação das relações deles
  - **Se negado**: CT-27 troca a lista; CT-28 não muda
- **RQ-21..RQ-25** — o 00 não diz qual DG é qual; a derivação leu a ordem do recorte (DG-02 casos de uso, DG-04..DG-09 autenticação, DG-13 ER). E DG-10, DG-19, DG-20 (RQ-25) não têm conteúdo determinado aqui.
  - **Assumido (P-02)**: a leitura da tabela "Catálogo" do 04; para os extras, a guarda declara um fato por DG — uma **relação** entre dois elementos que resolvem no código (ciclo 1) —, e ele é o que o `02` justificar
  - **Se negado**: renumeram-se as linhas de CT-03/CT-06; os extras continuam na lacuna L-01 (já reduzida por R40/CT-77) até o fato ser escrito
- **RQ-29 (docblocks)** — o Adendo 2 diz "três docblocks contradizem o código"; há cinco candidatos (KitInstall:27, AgenteIa:75-76, AdminPanelProvider:262-269, AppPanelProvider:67, InfraPanelProvider:343), quatro com a contradição conferida.
  - **Assumido (P-03)**: os quatro conferidos; o do `KitInstall` fica fora
  - **Se negado**: CT-45 ganha ou perde linhas
- **RQ-29 (escopo)** — as afirmações falsas de passkeys e do `composer dev` também estão em `docs/` (`referencia/pacotes-instalados.md`, `comecar/instalacao-avancada.md`, `operacao/roteiro-de-features.md`). Corrige-se só o README ou a documentação toda?
  - **Assumido (P-04)**: a documentação toda (falha fechado — a afirmação é falsa onde estiver)
  - **Se negado**: CT-42/CT-43 passam de `documentacaoDoKit()` para o README; as páginas ficam como dívida declarada
- **RQ-29 (composer dev)** — o texto cita "omite o reverb". O `pail` também sobe, mas só fora do Windows. Ele entra na descrição?
  - **Assumido (P-05)**: obrigatório servidor, fila, vite e reverb; o `pail` é opcional no texto
  - **Se negado**: CT-43 exige o `pail` com a ressalva de plataforma
- **RQ-20** — o README também é o que o Packagist (e a página de plugins do Filament) mostram, e nenhum dos dois renderiza Mermaid: lá o DG-01 aparece como código. É aceitável?
  - **Assumido (P-06)**: aceitável; nenhum cenário trata o Packagist
  - **Se negado**: o README precisa de alternativa textual ao lado do bloco — regra nova
- **RQ-18 / RQ-20** — o DG-01 do README é o mesmo bloco do DG-01 da página de diagramas?
  - **Assumido (P-07)**: sim, byte a byte por idioma — é o que faz a prova no navegador do site valer para o GitHub
  - **Se negado**: CT-36 sai e o README precisa de prova de renderização própria (lacuna L-03 cresce)
- **RQ-22 (estados)** — os diagramas de estado descrevem o que a **tela** permite ou o que o **model** permite? Ex.: `enviar()` num convite Aceito o leva a Pendente, mas a ação Reenviar não aparece para Aceito.
  - **Assumido (P-08)**: o que os pontos de entrada do kit permitem (tela com o `visible()`, comando, rota)
  - **Se negado**: CT-22 inverte para o Aceito; a linha do Recusado continua (o model também não o reabre)
- **RQ-18 / RQ-12** — lista fechada de tipos de diagrama: `flowchart`, `graph`, `sequenceDiagram`, `stateDiagram-v2`, `erDiagram`. `C4`, `architecture-beta`, `mindmap`, `classDiagram` entram?
  - **Assumido (P-09)**: não entram (falha fechado)
  - **Se negado**: CT-34 move a linha do tipo para "aceita"
- **RQ-27 / RQ-17** — `art/` **não** é `export-ignore`: todo `create-project` baixa os GIFs novos. Há teto de peso por GIF, ou os GIFs vão para `export-ignore`?
  - **Assumido (P-10)**: sem teto; nenhum cenário mede peso
  - **Se negado**: regra nova de tamanho (BVA no teto) e/ou linha no `.gitattributes`
- **RQ-23 (/infra)** — "mapa do /infra (tela → quem grava)" cobre toda tela do painel ou só as trilhas?
  - **Assumido (P-11)**: toda Resource do `/infra` mais as páginas cuja fonte é gravada por outro processo (Health, Backups, Logs, Pulse)
  - **Se negado**: CT-25/CT-26 passam a uma lista do mantenedor
- **RQ-27 (densidade)** — as capturas de densidade já são imagens publicadas no README e nas docs; ao virarem quadros de clipe, continuam publicadas como PNG?
  - **Assumido (P-12)**: sim (CT-48)
  - **Se negado**: a linha "quadro que é imagem declarada" de CT-48 inverte, e as páginas que citam `art/densidade-*.png` precisam mudar
- **RQ-30** — o 00 traz o texto do crédito só em português. Qual é o texto em inglês?
  - **Assumido (P-13)**: equivalente com "AI" e "not verified"
  - **Se negado**: CT-54 recebe o literal
- **RQ-12** — a guarda herdada `[CT-38]` exige stub de redirect até para página que nunca existiu no Jekyll (`referencia/arquitetura-em-diagramas`). Cria-se o stub ou a página nova é isenta?
  - **Assumido (P-14)**: cria-se o stub (a guarda existente vence)
  - **Se negado**: `[CT-38]` ganha uma lista de páginas nascidas depois da migração
- **RQ-18** — o 00 afirma que o GitHub renderiza com Mermaid 11.17.2. Quando o GitHub mudar de versão, o kit acompanha?
  - **Assumido (P-15)**: a versão fica fixada até decisão do mantenedor; a renderização no GitHub é conferida à mão no PR (L-03)
  - **Se negado**: regra nova de atualização da versão
- **RQ-10** — como um diagrama marca que um recurso é opcional?
  - **Assumido (P-16)**: o bloco nomeia a chave de `.env` que o liga (`KIT_TENANCY`, `KIT_LOGIN_UNIFICADO`…)
  - **Se negado**: CT-08 troca a chave pela marca escolhida; o invariante (nunca sem condição) não muda
- **RQ-28** — o texto novo do README diz "senha aleatória, impressa uma vez"?
  - **Assumido (P-17)**: sim, com "aleatória"/"random"
  - **Se negado**: CT-40 troca a palavra; a ausência de `` `password` `` não muda
- **Adendo 2 ("segue o tema claro/escuro")** — vale só no carregamento da página, ou também quando o leitor troca o tema com a página aberta?
  - **Assumido (P-18)**: também na troca (é o que o alternador do site faz)
  - **Se negado**: CT-B02 vira dois carregamentos, um por tema
- **RQ-10 (opcionais, ciclo 1)** — "recurso opcional" é toda chave de `config/kit.php` desligada por padrão (16 hoje, entre elas `KIT_ANTI_ROBO`, `KIT_DASHBOARD_DINAMICO`, `KIT_REGISTRO`, `KIT_HUB`, `KIT_DEMO`, `KIT_EXIBIR_VERSAO`), ou só as que o Adendo 2 cita? E, sendo todas, que termos identificam cada elemento num bloco?
  - **Assumido (P-19)**: todas (falha fechado); os termos mínimos de cada uma estão na tabela de CT-57, e a guarda pode ter mais
  - **Se negado**: CT-56 passa a comparar o mapa com a lista do mantenedor; o controle do extrator continua
- **RQ-22 (estados, ciclo 1)** — como o diagrama de estados mostra uma transição que o código às vezes recusa (desativar a própria conta, desativar o último master_global, restaurar para o estado anterior)?
  - **Assumido (P-20)**: a seta existe e leva a condição no rótulo, com o nome da razão do código ("própria conta", "último master_global", "estava ativa/inativa")
  - **Se negado**: CT-20 troca o termo da condição; o destino da seta e a ausência de estado inventado (CT-60) não mudam
- **RQ-22 / RQ-26 (identificadores, ciclo 1)** — os identificadores de estado do DG-08 e do DG-09 são os rótulos que o código devolve (`rotuloDaSituacao()`, `situacao()`, mais `Excluída`)?
  - **Assumido (P-21)**: sim; o en os mostra por alias
  - **Se negado**: CT-60/CT-61 comparam por um mapa identificador → rótulo declarado na guarda
- **RQ-29 (citações, ciclo 1)** — "três docblocks contradizem o código": vale também para citação por número de linha que envelheceu? Esta derivação achou, além do `KitServiceProvider.php:172`, o irmão `:173` (`InfraPanelProvider.php:435`, `command-center:access` está hoje em `:430`) e duas no `AppPanelProvider.php:508` (`DemoTenancySeeder.php:103` → hoje `:95`; `Convite.php:591` → hoje `:634`). E as 65 citações de `vendor/` nos providers de painel?
  - **Assumido (P-22)**: entram as citações de arquivos do kit (`app/`, `database/`, `config/`, `routes/`) nos comentários dos quatro arquivos de R31, na forma `{Arquivo}.php:{símbolo}:{linha}`; `vendor/` fica fora
  - **Se negado**: CT-66 perde a linha do `AppPanelProvider` (as duas do `InfraPanelProvider` continuam — são da contradição conferida); se ampliado, CT-66 ganha `vendor/` e o repositório inteiro
- **RQ-26 (tradução, ciclo 1)** — que rótulos podem ficar iguais no pt e no en? A derivação assumiu identificador de código e nome próprio de produto.
  - **Assumido (P-23)**: esses dois, com a lista de nomes próprios congelada na guarda; palavra comum do pt nunca
  - **Se negado**: CT-68 recebe a lista do mantenedor
- **RQ-19 / RQ-30 (imagens, ciclo 1)** — imagem nova em `art/` que o `kit:arte` não produz pode entrar no README ou no site? E imagem de host fora de `raw.githubusercontent.com/…/main/art/`, `img.shields.io` e `plumbphp.dev`?
  - **Assumido (P-24)**: não (falha fechado) — é por aí que o PNG do GitDiagram entraria
  - **Se negado**: CT-69 ganha a lista de exceções do mantenedor
- **RQ-17 / RQ-27 (endereço das imagens, ciclo 1)** — o endereço de toda imagem de `art/` usa o ref `main`? Referência relativa (`art/x.gif`) é aceita?
  - **Assumido (P-25)**: ref `main`, literal (é a forma das 118 de hoje); relativa recusada (o Packagist e o site não a resolvem)
  - **Se negado**: CT-74 aceita o ref ou a forma que o mantenedor escolher; o branch de feature continua recusado
- **RQ-10 (referências, ciclo 1)** — uma referência a código num bloco é reconhecida pela forma (classe, `kit:`, `/`, `kit.`, `KIT_`, tabela)?
  - **Assumido (P-26)**: sim, sem lista escrita no teste
  - **Se negado**: CT-77 recebe a marcação que o mantenedor escolher
- **RQ-22 (login unificado, ciclo 1)** — a decisão sobre a URL pretendida no DG-05 nomeia a condição "painel que a pessoa acessa"?
  - **Assumido (P-27)**: sim, no rótulo
  - **Se negado**: CT-59 perde o `Então` do rótulo; os destinos das três linhas continuam
- **RQ-24 (kit:update, ciclo 1)** — como o DG-17 mostra um diretório que o `kit:update` entrega só em parte (`app/Models`, `config/`)?
  - **Assumido (P-28)**: nomeando os arquivos, ou "só os do kit" junto do diretório
  - **Se negado**: CT-72 troca a ressalva; o diretório desenhado como entregue inteiro continua recusado
- **RQ-22 (estados da conta, ciclo 2)** — o rótulo da tela não é o estado: uma conta pendente pode ser desativada (a ação aparece), continua "Pendente", e ao ser aprovada vira "Inativo"; uma conta excluída ainda pendente pode ser aprovada na lixeira. O DG-08 mostra isso?
  - **Assumido (P-30)**: sim, pela condição da seta — aprovar: "estava ativa" / "estava desativada"; restaurar: "estava ativa" / "estava inativa" / "estava pendente" (en: "was active", "was deactivated", "was inactive", "was pending"); e a matriz é sobre as 7 situações concretas × 5 eventos (CT-78)
  - **Se negado** (o diagrama mostra só o caminho comum): CT-78 perde as linhas S2 × aprovar e S7 × restaurar como obrigatórias, e o DG-08 precisa de uma nota que diga que o rótulo esconde o `ativo` — sem ela, a seta sem condição volta a ser falsa
- **RQ-22 (laços, ciclo 2)** — o diagrama de estados pode desenhar um laço (`Pendente --> Pendente : desativar`) para a operação que roda e não muda o rótulo?
  - **Assumido (P-29)**: pode, só onde alguma situação daquele rótulo tem a operação como efeito oculto; em célula só "não oferecida", o laço é recusado
  - **Se negado** (nenhum laço): CT-79 e CT-81 invertem as linhas "laço aceito"
- **RQ-10 (referências, ciclo 2)** — como a guarda reconhece que um nó do diagrama afirma um produto, uma classe ou um comando?
  - **Assumido (P-31)**: pela forma — PascalCase composto é classe (resolve em `app/`, no `vendor/` ou na lista de nomes próprios); nome de um catálogo de produtos do ecossistema (Horizon, Meilisearch, Sanctum…) tem de estar instalado; `namespace:comando` em minúsculas resolve em `Artisan::all()`. Substitui a parte de classe de P-26
  - **Se negado**: CT-82 recebe a marcação que o mantenedor escolher; produto fora do catálogo continua em L-06
- **RQ-21 (casos de uso, ciclo 2)** — quais casos de uso o DG-02 desenha, e com que permissão cada um se decide?
  - **Assumido (P-32)**: no mínimo gerir usuários (`Create:User`), gerir convites (`Create:Convite`), ver os logs (`View:LogsExplorer`), ver a saúde (`View:HealthCheckResults`), montar o dashboard (`Manage:Dashboard`) e aceitar convite recebido (`Aceitar:Convite`, com `KIT_TENANCY`); aresta ⇔ `can()`; caso fora do mapa é recusado; o `master_global` pode ir a um nó único do `Gate::before`
  - **Se negado**: CT-83 recebe o mapa do mantenedor; a regra "aresta ⇔ `can()`" e a linha "presente" por papel não mudam
- **RQ-22 (convite, ciclo 2)** — o DG-07 mostra a fila entre o envio e o e-mail?
  - **Assumido (P-33)**: sim, como participante ou nó — o e-mail não sai sem worker, e é o que o operador precisa saber
  - **Se negado**: CT-90 perde o `Então` da fila; o destino do link e o disparo do lembrete continuam
- **RQ-26 (rótulos en, ciclo 2)** — o en de Aceito e Expirado é "Accepted" e "Expired"? (Active, Inactive, Deleted, Declined e pending já estão em `docs/en/autenticacao/`.)
  - **Assumido (P-34)**: sim
  - **Se negado**: CT-86 troca as duas linhas; as outras seis vêm do site en e não mudam
- **RQ-26 (identificadores, ciclo 2)** — P-21 dizia `Excluída`, e o bloco en não pode ter acento (CT-03). O identificador do estado excluído é `Excluida`, com alias "Excluída" no pt?
  - **Assumido (P-35)**: sim (corrige P-21)
  - **Se negado** (acento no identificador): CT-03 tem de aceitar acento em identificador no en, e o detector de tradução ausente perde a força nos rótulos
- **RQ-18 / Adendo 2 (tema, ciclo 2)** — um bloco pode ter frontmatter YAML? Com que chaves?
  - **Assumido (P-36)**: só `title:`; frontmatter com `config:` é recusado inteiro, qualquer que seja a chave (falha fechado)
  - **Se negado**: CT-95 move a linha `look: handDrawn` (ou a chave liberada) para "aceita"; `theme` e `themeVariables` continuam recusados
- **RQ-28 / RQ-17 (seção, ciclo 2)** — "a mesma seção" do README é o trecho entre dois títulos Markdown?
  - **Assumido (P-37)**: sim (`secoesDoMarkdown()`)
  - **Se negado**: CT-97 e CT-104 trocam o recorte pelo do mantenedor
- **RQ-29 (passkeys, ciclo 2)** — um diagrama pode citar passkeys para dizer que estão desligadas?
  - **Assumido (P-38)**: não — enquanto nenhum painel as liga, nenhum bloco nomeia passkey, WebAuthn ou FIDO; a prosa explica (CT-42)
  - **Se negado**: CT-101 aceita a linha da nota; o ramo de login por passkey continua recusado
- **RQ-29 (docblocks, ciclo 2)** — as âncoras dos comentários corrigidos de `AgenteIa` e do onboarding são as da tabela de CT-102?
  - **Assumido (P-39)**: sim
  - **Se negado**: CT-102 recebe as âncoras do mantenedor; os termos proibidos não mudam
- **RQ-11 (README, ciclo 2)** — um bloco Mermaid dentro de comentário HTML (para esconder o código cru no Packagist, P-06) é aceitável?
  - **Assumido (P-40)**: não — diagrama escondido não está disponível no README (falha fechado)
  - **Se negado**: CT-103 aceita a linha do comentário, e o README precisa de outra prova de que o bloco aparece no GitHub (L-03 cresce)
- **RQ-10 / RQ-27 (GIFs, ciclo 2)** — o GIF de um recurso desligado por padrão (login unificado) precisa da chave na mesma seção?
  - **Assumido (P-41)**: sim, com o mapa clipe → chave declarado na guarda e só com chaves que R39 extrai de `config/kit.php`
  - **Se negado**: CT-104 sai; o elemento opt-in nos blocos (CT-08, CT-57) continua
- **RQ-23 (configuração, ciclo 2)** — o DG-14 precisa mostrar as chaves que o banco não governa (`KIT_TENANCY`)?
  - **Assumido (P-42)**: sim — uma via "só `.env`" com ao menos `KIT_TENANCY`; a via única para "toda chave `KIT_*`" é recusada
  - **Se negado**: CT-92 perde a linha do controle positivo; `KIT_TENANCY` na via do banco continua recusado
- **RQ-22 / RQ-10 (recusa do convite, ciclo 2)** — a recusa do convite só tem porta com a tenancy (a caixa de convites recebidos). O DG-09 marca o evento recusar e o estado Recusado com `KIT_TENANCY`?
  - **Assumido (P-43)**: sim (R4, falha fechado), e a matriz do convite roda em `tests/Tenancy`
  - **Se negado**: CT-81 perde a linha `KIT_TENANCY`; a matriz de CT-80 não muda
- **RD2-08 (revisão do diff, 2ª rodada; premissa registrada no step 11, QA-03)** — o valor gravado no `.env` pelo `kit:install` (o `APP_NAME` que a pessoa digita, com barra invertida, `$` ou aspas) volta igual na leitura, e a gravação troca só a primeira ocorrência da chave?
  - **Assumido (P-44)**: sim — `SubstituicaoEmArquivo::definirNoEnv` preserva o valor literal (ida e volta pelo `.env`) e troca uma ocorrência só; é o comportamento que o `[RD2-08]` e o `04` passam a travar
  - *(alterado em 2026-09-29, Adendo 7: a gravação troca **toda linha ativa** da chave e nenhuma comentada (RQ-50), e a quebra de linha vira espaço sem injetar chave (RQ-51) — a "troca de uma ocorrência só" não vale mais)*
  - **Se negado**: o `[RD2-08]` perde o cenário da chave repetida, e a ida e volta continua
- **RQ-50 (Adendo 7; premissa registrada no fechamento da revisão adversarial da adição, 2026-09-30)** — a chave só existe **comentada** no `.env` (o bloco `# DB_*` do `.env.example`, ou um `# APP_URL=` deixado para preencher), sem nenhuma linha ativa. A gravação descomenta a linha no lugar, como hoje, ou a mantém intacta e acrescenta a ativa no fim, como a letra de RQ-50 ("nenhuma linha comentada") lida sozinha? (Q?9 do `04`, raia requisito)
  - **Assumido (P-45)**: sem linha ativa, descomentar no lugar — o comentário que RQ-50 protege é o que documenta **ao lado** de uma linha ativa, não o marcador de lugar do `.env.example`; a guarda existente (`[CT-19]` do `HostLocalTest`) vence. RQ-50 fica **aberta em parte** até a resposta: o invariante das duas leituras — exatamente uma linha ativa, com o valor gravado — é o CT-139
  - **Se negado**: a gravação passa a acrescentar a linha ativa no fim e o comentário fica; o `[CT-19]` do `HostLocalTest` e o bloco `DB_*` de quem escolhe PostgreSQL ou MySQL mudam de forma
- **RQ-28 (a senha já definida com o banco não populado; idem, 2026-09-30)** — com a senha já definida (digitada no prompt, ou em `KIT_ADMIN_PASSWORD` no `.env`) e o banco não populado (`--no-seed` ou inacessível), nenhum administrador existe. O que a linha "Senha do administrador" e o banner dizem? Hoje a linha diz "a que você digitou" ou "a que você já definiu", e o banner manda definir a chave que já está definida. (Q?10 do `04`, raia requisito)
  - **Assumido (P-46)**: os dois dizem o mesmo, e nada promete a credencial de um administrador que não existe (falha fechado): nenhum administrador foi criado nesta execução, e a senha de `KIT_ADMIN_PASSWORD` vale quando `php artisan db:seed` rodar — sem mandar definir a chave já definida. RQ-28 fica **aberta em parte** até a resposta; o invariante das duas leituras é o CT-134, e a direção (R56.M11) fica sem matador (L-17)
  - **Se negado**: a linha e o banner mantêm o texto de hoje, e L-17 vira lacuna aceita
- **RQ-28 (a senha gerada com a semeadura que não completou; idem, 2026-09-30)** — a senha foi gerada e gravada no `.env`, e o `db:seed` terminou com código diferente de zero: o banner imprime "Login inicial: … / {senha}" e manda trocar com `kit:admin`, que falha sem administrador, e logo abaixo o aviso manda rodar `db:seed`. O banner apresenta a senha como o login de agora, ou como a senha que o `db:seed` vai usar? (Q?11 do `04`, raia requisito)
  - **Assumido (P-47)**: nunca como login de um administrador que o banco não tem (falha fechado): a senha gerada é impressa — está no `.env` e é a única chance de anotá-la —, com o aviso de que o administrador ainda não foi criado e de que `php artisan db:seed` o cria com ela, sem `kit:admin` como próximo passo. O invariante das duas leituras é o CT-136; a direção (R55.M10) fica sem matador (L-18)
  - **Se negado**: o banner mantém o texto de hoje, e L-18 vira lacuna aceita
- **RQ-50 (valor multilinha entre aspas já no `.env`; ciclo 2 do gate, QA-19, 2026-09-30)** — um `.env` editado à mão pode ter um valor entre aspas que ocupa mais de uma linha física, e o Dotenv o lê; a gravação do kit troca só a primeira linha física e o resto vira uma linha que o Dotenv recusa. A gravação precisa suportar esse valor? (Q?14 do `04`, raia requisito)
  - **Assumido (P-48)**: não — valor multilinha entre aspas não é suportado pela gravação do kit: o kit nunca grava um (a quebra vira espaço, RQ-51), a entrada só nasce de edição à mão, e o que o Dotenv aceita ler não é o que o kit promete escrever. Sem cenário que a afirme (lacuna L-21 do `04`); o que vale nas duas leituras — o kit nunca produz valor multilinha — é o CT-117
  - **Se negado**: a gravação passa a trocar o valor inteiro até a aspa que fecha, ou a recusar com mensagem, e a L-21 vira linha de R64
- **Escalada ao mantenedor (ciclo 2, teto de rodadas)** — a 2ª revisão adversarial trouxe três achados estruturais (A2-01, A2-02, A2-03), e o fechamento trocou a técnica de duas regras: R12 passou de "estado × evento sobre o rótulo" a "situação concreta × evento", e R40 de "referência que existe" a "referência pela forma, com catálogo". A skill não permite 3ª rodada. As duas trocas ficam valendo como premissas (P-29..P-31) até a confirmação

## Adendo 1 — 2026-09-27

- **Fonte**: mensagem do mantenedor no chat, durante a pesquisa (step 3), minutos depois do pedido original
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - repositorio do gitdiagram: "https://github.com/ahmedkhaleel2004/gitdiagram"
> - site para gerar o video: "https://www.sent.dm/en?utm_source=gitdiagram&utm_medium=sponsorship&utm_campaign=sent_30_days&utm_content=readme" não é obrigatorio, mas seria interessante ter, tanto aqui nos diagramas, quanto na propria funcionalidade do kit
> - reflita sobre gerar pequenos gifs ou videos de partes do sistema para compor a explicação e ser mais visivel com videos e imagens de quem for usar o kit

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-13 | O repositório `ahmedkhaleel2004/gitdiagram` é a fonte do estudo das funcionalidades do GitDiagram | "repositorio do gitdiagram: \"https://github.com/ahmedkhaleel2004/gitdiagram\"" | restrição | — (fixa a fonte do RQ-02) |
| RQ-14 | O site indicado é avaliado como gerador de vídeo **para os diagramas** — opcional | "site para gerar o video [...] não é obrigatorio, mas seria interessante ter, tanto aqui nos diagramas" | funcional (opcional) | — |
| RQ-15 | O mesmo site é avaliado como **funcionalidade do próprio kit** — opcional | "quanto na propria funcionalidade do kit" | funcional (opcional) | — |
| RQ-16 | Gerar pequenos GIFs ou vídeos de partes do sistema é avaliado como parte da explicação | "reflita sobre gerar pequenos gifs ou videos de partes do sistema para compor a explicação" | funcional | — |
| RQ-17 | Vídeos e imagens existem para tornar o kit mais visível para **quem vai usá-lo** | "ser mais visivel com videos e imagens de quem for usar o kit" | restrição | — |

### Ambiguidades do adendo

- **RQ-14 / RQ-15** — o link carrega `utm_source=gitdiagram&utm_medium=sponsorship`, ou seja, é o link de **patrocinador** que o README do GitDiagram exibe. Não está confirmado que o `sent.dm` gera vídeo.
  - **Assumido**: a pesquisa confirma o que o `sent.dm` é **antes** de qualquer decisão. Se ele não gera vídeo, RQ-14 e RQ-15 fecham como "não se aplica", com a evidência, e a necessidade de vídeo fica com o RQ-16
  - **Se negado** (o mantenedor quis dizer outro site): RQ-14 e RQ-15 são reavaliados contra o site certo
- **RQ-15** — "na própria funcionalidade do kit" pode querer dizer **integrar** um serviço externo ao kit, o que é feature nova com dependência e credencial.
  - **Assumido**: esta entrega **avalia** a integração e registra a decisão. Se ela passar, a implementação vira outra wiki
  - **Se negado**: a integração entra nesta entrega, com `RQ` próprios num Adendo 2
- **RQ-16** — "reflita" pede reflexão, mas o RQ-17 pede o resultado visível.
  - **Assumido**: igual ao RQ-07..09 — o estudo escolhe um conjunto pequeno, e ele é produzido nesta entrega **se** couber no pipeline de capturas que o kit já tem (`composer art`) sem dependência nova. Se exigir dependência nova, a dependência vai para aprovação do mantenedor antes
  - **Se negado**: a entrega para no estudo

## Adendo 2 — 2026-09-27

- **Fonte**: respostas do mantenedor às quatro perguntas de escopo que a sessão fez depois da pesquisa (step 3), no chat do Claude Code
- **Fidelidade**: alta (escolha entre opções escritas, mais um texto livre)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "Como o site deve renderizar os diagramas? (a fonte é sempre Mermaid em texto, versionado)"="astro-mermaid (Recomendado)", "O que entra no README (pt e en)?"="1 diagrama + link (Recomendado)", "Quais grupos de diagramas entram nesta entrega? Cada diagrama vai em pt e en, com uma guarda de teste que falha se o código deixar de bater."="Visão geral (Recomendado), Autenticação, "IA, /infra e dados", Ciclo do kit,  adicione mais diagramas que voce julgar pertinentes e que agrega valor", "O que mais entra nesta entrega?"="GIFs pelo kit:arte, Corrigir a senha password, Link ao GitDiagram, Outras afirmações falsas"

As opções escolhidas diziam, na pergunta:

- **astro-mermaid** — "Adiciona astro-mermaid + mermaid fixado em 11.17.2 no site/package.json (não na raiz). O mesmo bloco renderiza no GitHub e no site, segue o tema claro/escuro."
- **1 diagrama + link** — "Um diagrama de arquitetura em Mermaid (painéis, acesso por papel, IA, /infra) e um link para a página de diagramas do site."
- **Visão geral** — "Arquitetura (painéis, camadas, serviços), casos de uso por papel (master_global, admin, infra, panel_user, admin_app) e a regra de acesso ao painel (canAccessPanel)."
- **Autenticação** — "Sequências: login por senha + 2FA, login unificado (0/1/N painéis), retorno do login social, convite do envio ao aceite; estados da conta e do convite."
- **IA, /infra e dados** — "Sequência do assistente (os 4 guardrails e o ledger ai_runs), mapa do /infra (tela → quem grava), ER do núcleo e de onde vem a configuração (.env → config → banco)."
- **Ciclo do kit** — "Sequência da instalação (create-project → kit:install), fluxo do kit:update com as duas rotas de entrega, containers por profile do Docker."
- **GIFs pelo kit:arte** — "Generaliza o slideshow que o kit:arte já monta: busca ⌘K, login unificado → escolha de painel, densidade, import/export. Sem dependência nova. Vídeo MP4 fica de fora, porque a CT-21 proíbe <video> nas docs."
- **Corrigir a senha password** — "O README (:32, :97), o resumo do kit:install e o install.gif ainda dizem 'password', mas desde o #100 a instalação gera uma senha aleatória. Refazer o GIF e corrigir o texto e o resumo."
- **Outras afirmações falsas** — "README diz 'passkeys' (estão desligadas) e 'composer dev = servidor + fila + vite' (omite o reverb); routes/console.php diz que o schedule:work vem no composer dev (não vem); três docblocks contradizem o código."
- **Link ao GitDiagram** — "Na página de diagramas do site, um link como crédito: 'visão gerada por IA, não verificada — o diagrama oficial é este'. Sem embutir."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-18 | O site renderiza os diagramas com `astro-mermaid` e `mermaid` fixado em 11.17.2, declarados em `site/package.json` — **aprovação explícita** da dependência nova | "astro-mermaid (Recomendado)" | restrição | resolve a ambiguidade de RQ-05 / RQ-12 |
| RQ-19 | A fonte de todo diagrama é Mermaid em texto, versionado no repositório | "(a fonte é sempre Mermaid em texto, versionado)" | restrição | — |
| RQ-20 | O README (pt e en) ganha **um** diagrama de arquitetura em Mermaid e um link para a página de diagramas do site | "1 diagrama + link (Recomendado)" | funcional | resolve RQ-04 / RQ-11 |
| RQ-21 | Entram os diagramas da **visão geral**: arquitetura, casos de uso por papel e a regra de acesso ao painel | "Visão geral (Recomendado)" | funcional | concretiza RQ-07 / RQ-09 |
| RQ-22 | Entram os diagramas de **autenticação**: sequências de login por senha + 2FA, login unificado, retorno do login social e convite; estados da conta e do convite | "Autenticação" | funcional | concretiza RQ-08 |
| RQ-23 | Entram os diagramas de **IA, /infra e dados**: sequência do assistente, mapa do /infra, ER do núcleo e precedência de configuração | "IA, /infra e dados" | funcional | concretiza RQ-08 / RQ-09 |
| RQ-24 | Entram os diagramas do **ciclo do kit**: sequência da instalação, fluxo do `kit:update` com as duas rotas de entrega, containers por profile | "Ciclo do kit" | funcional | concretiza RQ-08 / RQ-09 |
| RQ-25 | Entram também os diagramas que a sessão julgar pertinentes e que agreguem valor — cada um justificado no `02` | "adicione mais diagramas que voce julgar pertinentes e que agrega valor" | funcional | — |
| RQ-26 | Todo diagrama existe em pt **e** en e tem guarda de teste que falha quando o código deixa de bater com ele | "Cada diagrama vai em pt e en, com uma guarda de teste que falha se o código deixar de bater." | restrição | resolve a ambiguidade de RQ-10 (a guarda vale depois da publicação) |
| RQ-27 | O `kit:arte` passa a montar GIFs de mais partes do sistema (busca ⌘K, login unificado → escolha de painel, densidade, import/export), sem dependência nova; vídeo MP4 fica fora | "GIFs pelo kit:arte" | funcional | concretiza RQ-16 / RQ-17 |
| RQ-28 | README (pt e en), o resumo do `kit:install` e o `art/install.gif` deixam de afirmar a senha `password` | "Corrigir a senha password" | funcional | — |
| RQ-29 | As outras afirmações falsas achadas na pesquisa são corrigidas: passkeys, `composer dev`, o comentário do `schedule:work` e os docblocks que contradizem o código | "Outras afirmações falsas" | funcional | — |
| RQ-30 | A página de diagramas do site credita o GitDiagram com um link, marcado como visão gerada por IA e não verificada, sem embutir | "Link ao GitDiagram" | funcional | resolve RQ-04 (o referente "o site") |

### Ambiguidades resolvidas por este adendo

- **RQ-04** → os dois referentes: o **diagrama** entra corrigido (RQ-20, RQ-21), o **site** entra como crédito (RQ-30)
- **RQ-05 / RQ-12** → `docs/` (pt e en), renderizado por `astro-mermaid` (RQ-18). `wikis/` fica fora, como assumido
- **RQ-07 / RQ-08 / RQ-09 / RQ-16** → o estudo vira entrega (RQ-21..RQ-27), como assumido
- **RQ-10** → a guarda vale depois da publicação (RQ-26), como assumido
- **RQ-11 / RQ-12** → os dois idiomas (RQ-26), como assumido
- **RQ-14 / RQ-15** → a pesquisa confirmou que o `sent.dm` é uma API de mensagens (SMS, WhatsApp, RCS) e **não gera vídeo**; o link é o anúncio pago do README do GitDiagram. As duas cláusulas fecham como **não se aplica**, e o mantenedor não pediu a integração no Adendo 2

## Adendo 3 — 2026-09-28

- **Fonte**: respostas do mantenedor às duas perguntas que a sessão levou quando a 2ª rodada da revisão do diff (step 6.5) atingiu o teto da skill com achado estrutural
- **Fidelidade**: alta (escolha entre opções escritas)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "Como fecho o que a 2ª rodada de revisão achou? (o teto da skill é 2 rodadas; uma 3ª precisa da sua decisão)"="Corrigir tudo (Recomendado)", "O DG-11 quebrado só apareceu porque rodei o build do site à mão: o CI de PR não constrói o site (o pages.yml só roda depois do merge). Acrescento essa checagem ao CI de PR?"="Sim, job no PR (Recomendado)"

As opções escolhidas diziam, na pergunta:

- **Corrigir tudo** — "Uma 3ª rodada: os 2 defeitos do navegador (sintaxe do DG-11; contraste do tema escuro ajustado na config do astro-mermaid, sem cor fixa no bloco), os 4 de código, e as guardas: um extrator de arestas normalizado (-->, --->, -.->, ==>) usado em pt e en, e os 21 cenários passando a conferir o conteúdo do bloco. Depois, revisão curta só do delta e o quality gate."
- **Sim, job no PR** — "Um job no ci.yml que roda npm ci + build + verifica-links + verifica-acessibilidade quando o PR toca docs/ ou site/. Custa ~3-5 min nesses PRs e pega diagrama quebrado e contraste antes do merge."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-31 | Uma 3ª rodada do step 6.5 é autorizada, acima do teto da skill, para fechar **todos** os achados da 2ª | "Corrigir tudo (Recomendado)" | restrição | — |
| RQ-32 | Todo diagrama renderiza no site e no GitHub — nenhum bloco com erro de sintaxe | "os 2 defeitos do navegador (sintaxe do DG-11 [...])" | funcional | concretiza RQ-12 |
| RQ-33 | Os diagramas atendem ao contraste WCAG AA nos dois temas, ajustado na configuração do site, nunca com cor fixa no bloco | "contraste do tema escuro ajustado na config do astro-mermaid, sem cor fixa no bloco" | não-funcional | — |
| RQ-34 | As guardas leem as arestas de forma normalizada (qualquer forma de seta) e nos dois idiomas | "um extrator de arestas normalizado (-->, --->, -.->, ==>) usado em pt e en" | restrição | reforça RQ-26 |
| RQ-35 | Os 21 cenários que só conferiam a existência do bloco passam a conferir o conteúdo dele contra o código | "os 21 cenários passando a conferir o conteúdo do bloco" | restrição | reforça RQ-26 |
| RQ-36 | O CI de pull request constrói e confere o site (build, links, acessibilidade e diagramas) quando o PR toca `docs/` ou `site/` | "Um job no ci.yml que roda npm ci + build + verifica-links + verifica-acessibilidade quando o PR toca docs/ ou site/" | funcional | — |

## Adendo 4 — 2026-09-28

- **Fonte**: resposta do mantenedor à pergunta que a sessão levou quando a revisão cega do delta da 3ª rodada (step 6.5 da 3.x, step 9 da 4.0.0) achou 12 itens novos, RQ-31 tendo autorizado só a 3ª
- **Fidelidade**: alta (escolha entre opções escritas)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "A revisão cega do delta da 3ª rodada achou 12 itens novos (RD3-01..12). São 3 Major: RD3-01, o conselho do kit:install para "banco não populado", que leva a um admin com 'password' ou a um beco sem saída; RD3-06, fatos que ainda passam no vazio em DG-12/13/14/16/17, com o DG-03 só em pt; e RD3-02, a suíte vermelha, que já corrigi. Os outros 9 são Minor: formas de seta que o extrator não reconhece, dois achados da 2ª rodada não tocados, publicação do GIF entre volumes, permissions no job de CI, um literal duplicado, entre outros. O que fazer?"="Corrigir tudo, última rodada (Recomendado)"

A opção escolhida dizia, na pergunta:

- **Corrigir tudo, última rodada** — "Uma 4ª rodada fecha os Major e os Minor, com revisão cega só do delta novo. O que essa revisão achar vira dívida declarada no 03, sem 5ª rodada. Depois seguem a reconciliação, o quality gate e o PR."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-37 | Uma 4ª rodada da revisão do diff fecha todos os achados da 3ª (RD3-01..RD3-12), Major e Minor | "Uma 4ª rodada fecha os Major e os Minor" | restrição | — |
| RQ-38 | A 4ª rodada tem revisão cega só do delta novo | "com revisão cega só do delta novo" | restrição | — |
| RQ-39 | É a última rodada: o que a revisão da 4ª achar vira dívida declarada no `03`, sem 5ª rodada | "O que essa revisão achar vira dívida declarada no 03, sem 5ª rodada" | restrição | — |
| RQ-40 | Depois da 4ª rodada, a feature segue para a reconciliação, o quality gate e o PR | "Depois seguem a reconciliação, o quality gate e o PR." | restrição | — |

## Adendo 5 — 2026-09-29

- **Fonte**: resposta do mantenedor à pergunta que a sessão levou quando a revisão cega da 4ª rodada achou duas regressões introduzidas pela própria rodada, o que o RQ-39 ("vira dívida declarada") não previa
- **Fidelidade**: alta (escolha entre opções escritas)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "A revisão cega da 4ª rodada achou duas regressões introduzidas pela própria rodada: RD4-01 (Blocker, o kit:arte não monta mais GIF com o ffmpeg real) e RD4-03 (Major, o fato do DG-03 parou de pegar a troca de rótulos em pt). Também achou dois consertos mecânicos (RD4-04, Pint vermelho que quebra o CI; RD4-06, citações deslocadas) e seis itens que não são regressão (RD4-02, 05, 07, 08, 09, 10). O Adendo 4 diz: "o que essa revisão achar vira dívida declarada, sem 5ª rodada". O que fazer?"="Só regressões e mecânico (Recomendado)"

A opção escolhida dizia, na pergunta:

- **Só regressões e mecânico** — "Corrijo as duas regressões e os dois consertos mecânicos: `-f gif` com um ffmpeg de teste que recusa saída sem formato, o fato do DG-03 lendo o rótulo, o Pint e as citações. Cada correção é provada vermelha, sem nova revisão cega. Os outros seis viram dívida declarada no 03 e no PR, com o RD4-02 e o RD4-10 em destaque."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-41 | As duas regressões da 4ª rodada são corrigidas: o `kit:arte` volta a montar o GIF com o ffmpeg real (RD4-01), e o fato do DG-03 volta a pegar a troca de rótulos, nos dois idiomas (RD4-03) | "Corrijo as duas regressões [...] `-f gif` com um ffmpeg de teste que recusa saída sem formato, o fato do DG-03 lendo o rótulo" | funcional | exceção a RQ-39 |
| RQ-42 | Os dois consertos mecânicos entram: Pint verde (RD4-04) e citações corretas (RD4-06) | "o Pint e as citações" | restrição | exceção a RQ-39 |
| RQ-43 | Cada correção é provada vermelha antes; não há nova revisão cega | "Cada correção é provada vermelha, sem nova revisão cega." | restrição | — |
| RQ-44 | RD4-02, RD4-05, RD4-07, RD4-08, RD4-09 e RD4-10 viram dívida declarada no `03` e no PR, com RD4-02 e RD4-10 em destaque | "Os outros seis viram dívida declarada no 03 e no PR, com o RD4-02 e o RD4-10 em destaque." | restrição | aplica RQ-39 |

## Adendo 6 — 2026-09-29

- **Fonte**: respostas do mantenedor às duas perguntas que a sessão levou quando o quality gate (step 11, ciclo 1) reprovou a feature com 7 Major, 5 Minor e 2 cosméticos (`06-relatorio-qa.md`)
- **Fidelidade**: alta (escolha entre opções escritas)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "O quality gate dos diagramas reprovou com 7 Major, 5 Minor e 2 cosméticos. Como fecho para o ciclo 2 do gate?"="Corrigir tudo e rodar o gate de novo (Recomendado)", "QA-06: no site, a coluna tem 600 px e o Mermaid encolhe o SVG para caber nela, deixando 13 dos 20 diagramas com o texto abaixo de 10 px. Como resolvo?"="Tamanho natural com rolagem (Recomendado)"

As opções escolhidas diziam, na pergunta:

- **Corrigir tudo e rodar o gate de novo** — "Os 14 achados, com teste primeiro onde o destino é teste. O QA-12 vira nota para a skill. Depois, o quality gate roda o ciclo 2, cego como no 1."
- **Tamanho natural com rolagem** — "CSS do site: o diagrama nunca encolhe abaixo de um piso de fonte (cerca de 12 px), e o que não couber rola na horizontal, dentro do próprio bloco. Não muda nenhum bloco nem o GitHub. Um CT-B mede o piso antes."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-45 | Todos os achados do ciclo 1 do quality gate (QA-01..QA-14) são corrigidos, com o teste primeiro onde o destino é teste; o QA-12 vira nota para a skill | "Os 14 achados, com teste primeiro onde o destino é teste. O QA-12 vira nota para a skill." | restrição | — |
| RQ-46 | O quality gate roda o ciclo 2, cego como no 1, antes do PR | "Depois, o quality gate roda o ciclo 2, cego como no 1." | restrição | — |
| RQ-47 | No site, o diagrama nunca encolhe abaixo de um piso de fonte efetiva (cerca de 12 px); o que não couber rola na horizontal dentro do próprio bloco | "o diagrama nunca encolhe abaixo de um piso de fonte (cerca de 12 px), e o que não couber rola na horizontal, dentro do próprio bloco" | não-funcional | concretiza RQ-12/RQ-17 |
| RQ-48 | A legibilidade se resolve no CSS do site, sem mudar bloco nem o que o GitHub mostra | "Não muda nenhum bloco nem o GitHub." | restrição | — |
| RQ-49 | Um CT-B mede o piso de fonte antes da correção | "Um CT-B mede o piso antes." | restrição | — |

## Adendo 7 — 2026-09-29

- **Fonte**: respostas do mantenedor às três perguntas de raia requisito que a derivação dos cenários do ciclo 2 do quality gate levantou (Q?3, Q?4, Q?5 do `04`)
- **Fidelidade**: alta (escolha entre opções escritas)

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "Q?4: se a mesma chave estiver ativa duas vezes no .env (edição à mão), o instalador hoje troca só a primeira linha (P-44). Só que o Dotenv, que o kit usa para ler, fica com a última, e o Laravel fica com a primeira. O que a gravação deve fazer?"="Trocar toda linha ativa (Recomendado)", "Q?3: o valor digitado com quebra de linha não volta igual do .env. O instalador troca a quebra por espaço, porque uma quebra injetaria uma chave nova no arquivo. Mantém assim ou recusa o valor?"="Troca por espaço (Recomendado)", "Q?5: quando o ffmpeg monta o GIF e quem falha é a publicação em art/ (disco, permissão), a saída do kit:arte deve dizer que foi a publicação, e não a montagem?"="Sim, nomeia a etapa (Recomendado)"

As opções escolhidas diziam, na pergunta:

- **Trocar toda linha ativa** — "Falha fechado: qualquer leitor fica com o valor gravado. A gravação troca todas as linhas ativas da chave e nenhuma comentada. A P-44 e o teste do QA-10 mudam para isso."
- **Troca por espaço** — "É o comportamento de hoje, e o valor vem de um prompt de uma linha. A P-44 ganha a exceção escrita, e o teste afirma que o .env não ganha chave nova."
- **Sim, nomeia a etapa** — "A mensagem diz "não consegui publicar", porque é o que mostra ao mantenedor onde agir (disco e permissão, não o ffmpeg). Fecha o mutante sem matador R33.M10."

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-50 | A gravação no `.env` troca toda linha ATIVA da chave e nenhuma linha comentada: qualquer leitor fica com o valor gravado | "A gravação troca todas as linhas ativas da chave e nenhuma comentada." | funcional | altera P-44 |
| RQ-51 | Valor com quebra de linha vai ao `.env` com a quebra trocada por espaço, e o arquivo não ganha chave nova | "o teste afirma que o .env não ganha chave nova" | funcional | exceção escrita à P-44 |
| RQ-52 | Quando a montagem do GIF dá certo e a publicação em `art/` falha, a saída do `kit:arte` nomeia a publicação ("não consegui publicar"), e não a montagem | "A mensagem diz "não consegui publicar"" | funcional | — |

## Adendo 8 — 2026-09-30

- **Fonte**: resposta do mantenedor à pergunta Q?9 (raia requisito, P-45), feita pela sessão com a recomendação e as duas opções depois do veredito do ciclo 2 do quality gate (QA-15); Q?10 e Q?11 foram feitas junto
- **Fidelidade**: baixa — o mantenedor mandou seguir sem escolher uma opção; a sessão aplicou a recomendação que a pergunta trazia (opção 1) e deixa registrado que a decisão foi por omissão

### Texto Original

<!-- IMUTÁVEL. Pergunta como foi feita → resposta como foi dada. -->

> "A decisão que destrava o ciclo 3 (Q?9, RQ-50): quando a chave só existe comentada no .env (o bloco # DB_* do .env.example, um # APP_URL= deixado para preencher), a gravação deve: 1. Descomentar no lugar (o comportamento de hoje e a P-45): o comentário que a RQ-50 protege é o que documenta ao lado de uma linha ativa, não o marcador de lugar do .env.example; mantém o [CT-19] do HostLocalTest verde. É a minha recomendação. 2. Manter o comentário e acrescentar a linha ativa no fim (a letra da RQ-50 lida sozinha): muda o .env de quem escolhe PostgreSQL/MySQL e exige reescrever o [CT-19]. Se puder, decida também Q?10 e Q?11 (…); as recomendações estão no 00 como P-46 e P-47."
>
> "continue"

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-53 | Sem nenhuma linha ativa da chave, a gravação no `.env` descomenta a primeira linha comentada no lugar; "nenhuma linha comentada" da RQ-50 vale para o comentário ao lado de uma linha ativa | "continue" (a opção 1 da pergunta, aplicada por omissão) | funcional | fecha a Q?9 / P-45; complementa RQ-50 |

### Ambiguidades do adendo

- "continue" não escolhe uma opção. A sessão leu como "siga com a recomendação" porque a pergunta a nomeava e o texto anterior já dizia que o código de hoje faz isso. **Se negado**: a gravação passa a manter o comentário e acrescentar a ativa no fim, o CT-139 troca a direção, e o `[CT-19]` do `HostLocalTest` (outra wiki) é reescrito
- Q?10 e Q?11 (P-46, P-47) **continuam abertas**: a resposta não as tocou, e nenhuma direção delas é implementada; o gate as trata como teto, não como reprovação

## Fora de Escopo (declarado)

- Mudar o comportamento do kit para caber num diagrama — o diagrama segue o código, nunca o contrário
- Diagramas gerados em tempo de execução dentro dos painéis (o `/infra` já tem o Dependency Graph)
- Funcionalidade planejada ou adiada (`wikis/roadmap.md`) — RQ-10 a exclui
