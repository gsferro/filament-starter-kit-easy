# Requisito — Hub de cards fora do padrão da instalação

## Fonte

- **Origem**: pedido colado no chat pelo mantenedor do kit, invocando a skill `feature-wiki`
- **Data**: 2026-08-21
- **Autor / solicitante**: Guilherme Ferro (mantenedor do starter-kit-easy)
- **Fidelidade**: alta (texto escrito)
- **Wiki ancestral**: `wikis/specs/main/hub-de-navegacao-em-cards/` — é ela que instalou o pacote e criou os três hubs que este requisito revisa

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> a gente inslatou o pacote de hub para criar links usando cards. ele entrou como padrão e todas os paineis, porem não acredito que seja necessário para o starter-kit inicial. esse de adequa melhor talvez em paginas para exibir links e fluxos. deixe ele instalado e documentado para uso quando for necessário, mas para telas iniciais, não compensa.
> veja se tem imagens de prints/ thumbs, se houver coloca como opções de uso, mas tira de ser padrão na instalação

### Acréscimo — segunda mensagem, 2026-08-21

> nos cards que ficarem na infra, adicione uma descrição para explicar o que cada link serve

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `harvirsidhu/filament-cards` continua instalado no projeto | "deixe ele instalado" | restrição |
| RQ-02 | O uso do pacote fica documentado, para quando for necessário | "e documentado para uso quando for necessário" | não-funcional |
| RQ-03 | O hub deixa de ser padrão da instalação | "tira de ser padrão na instalação" | funcional |
| RQ-04 | Tela inicial de painel não nasce com hub | "mas para telas iniciais, não compensa" | funcional |
| RQ-05 | Se houver imagem de print/thumb do hub, ela entra como opção de uso na documentação | "veja se tem imagens de prints/ thumbs, se houver coloca como opções de uso" | não-funcional |
| RQ-06 | A documentação registra o encaixe melhor do pacote: páginas que exibem links e fluxos | "esse de adequa melhor talvez em paginas para exibir links e fluxos" | não-funcional |
| RQ-07 | Cada card que permanecer no hub de `/infra` tem uma descrição que explica para que o link serve | "nos cards que ficarem na infra, adicione uma descrição para explicar o que cada link serve" | funcional |

> **Por que RQ-03 e RQ-04 são separadas.** Elas falham independentemente. Um kit que mantém o hub
> registrado nos três painéis mas o esconde da navegação atende RQ-04 e **não** atende RQ-03 (a
> rota continua de pé, o Spotlight continua oferecendo). Um kit que apaga as três páginas atende
> RQ-03 e mata RQ-01/RQ-02 junto. A matriz de rastreabilidade precisa marcar as duas em separado.

## Ambiguidades e Perguntas Abertas

### Resolvidas com o usuário em 2026-08-21

- **RQ-03 — como o hub sai do padrão: flag ou remoção?**
  - **Decisão do usuário**: **flag `kit.hub`, default `false`**. As três Pages continuam no
    repositório; quem quiser o hub liga `KIT_HUB=true` no `.env` e não edita código nenhum.
  - **Razão registrada**: é o mesmo padrão que o kit já usa em `ProjetoResource::canAccess()` com
    `config('kit.demo')` (`app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88`), e o que
    mantém RQ-01 e RQ-02 vivos sem custo — o código documentado é o código que roda, não um trecho
    copiado de receita que ninguém prova.
  - **Alternativa recusada**: apagar as três Pages, a trait, o CSS e os três arquivos de teste,
    deixando só a receita em `wikis/receitas.md`. Recusada porque transformaria a única
    implementação testada do padrão em texto — ver ADR-01.

- **RQ-04 — o hub sai dos três painéis?**
  - **Decisão do usuário**: **fica no `/infra`; sai de `/admin` e de `/app`.**
  - **Consequência para a leitura literal da cláusula**: "para telas iniciais não compensa" tem
    **uma exceção declarada**, e ela é a do painel que a wiki ancestral já argumentava ser o caso
    mais forte — `/infra` tem quatro grupos de navegação e as páginas próprias de sete plugins, e
    "onde vejo os backups?" é pergunta real ali (ADR-01 da wiki ancestral,
    `app/Filament/Infra/Pages/HubDeInfraestrutura.php:14-22`).
  - **Efeito na cobertura**: RQ-04 é atendida **com a exceção do `/infra` declarada pelo usuário**.
    O `feature-quality-gate` não deve ler o hub de infraestrutura vivo como omissão.

- **RQ-05 — existe imagem do hub?**
  - **Verificado**: **não.** `art/` e `art/thumbs/` têm 21 imagens, nenhuma do hub. As capturas de
    painel (`art/thumbs/panel-admin.png` e irmãs) são **anteriores** ao hub — conferido abrindo o
    arquivo: a barra lateral não tem o item. O único screenshot do hub que existe hoje é o de
    `tests/Browser/HubDeCardsTest.php:36`, escrito em `tests/Browser/Screenshots/`, que é
    `.gitignore`d (`.gitignore:28`) e não está na proporção da galeria (1400x875).
  - **Decisão do usuário**: **gerar a captura**, acrescentando um cenário à suíte de arte
    (`tests/BrowserTenancy/CapturaDeArteTest.php`), para a documentação da opção mostrar o que se
    ganha ao ligar a flag.
  - **Leitura da cláusula**: a condicional "se houver" nasce **falsa**; o usuário converteu a
    cláusula de condicional em imperativa. RQ-05 passa a exigir a imagem, não a procurá-la.

### Abertas — decididas por premissa, sujeitas a correção

- **RQ-02 — "documentado para uso quando for necessário": documentação nova ou a que existe?**
  - **Assumido**: a receita **já existe** (`wikis/receitas.md`, seção "Página hub de cards", com
    os quatro casos de uso, o que não fazer e o passo a passo do pós-criação). RQ-02 se atende
    **atualizando-a** para descrever a flag e o novo default, não escrevendo um documento novo.
  - **Se negado** (o usuário quiser um documento dedicado): a receita é extraída para
    `wikis/specs/.../05-*.md` ou para um arquivo próprio em `wikis/`, e o PRD ganha um passo.

- **RQ-06 — "páginas para exibir links e fluxos" é caso de uso novo ou reforço do que já está escrito?**
  - **Assumido**: **reforço**. Os quatro casos de uso da receita já cobrem "exibir links"
    (atalhos externos, hub de configurações, página inicial de Cluster). O que falta é a palavra
    **fluxo** — página que apresenta as etapas de um processo como cartões. Entra como quinto caso
    de uso na receita, sem código novo.
  - **Se negado** (o usuário quiser uma página de fluxo implementada como exemplo): vira feature
    própria, com wiki própria — o kit não tem hoje nenhum fluxo multi-etapas com tela de índice.

- **RQ-07 — "cada link": todos os cards, ou só os que o rótulo não explica?**
  - **Assumido**: **todos**. Levantei os 16 destinos do painel `/infra`
    (`php artisan tinker`, `Panel::getResources()` + `getPages()` com
    `shouldRegisterNavigation()`), e eles se dividem em dois grupos: rótulo autoexplicativo
    ("Backups", "Lixeira", "Saúde da aplicação") e rótulo de vendor não traduzido
    ("audits", "Exception", "Manage commands", "Run history", "Commands"). Descrever só o segundo
    grupo produz grade com cards de duas alturas, e o buraco lê como esquecimento, não como
    decisão.
  - **Se negado**: a descrição fica só nos destinos de rótulo obscuro, e o PRD ganha a lista
    curta em vez do mapa completo.

- **RQ-07 — a descrição vale também para `/admin` e `/app` quando alguém liga a flag?**
  - **Assumido**: **não.** O requisito diz "nos cards que ficarem na infra", e a infra é
    justamente o hub que nasce ligado. Os hubs de `/admin` e `/app` chegam sem descrição; quem
    ligar a flag declara as suas no mapa da própria Page, e a receita documenta como.
  - **Se negado**: o mapa dos outros dois painéis entra nesta entrega — são ~8 destinos em
    `/admin` e ~4 em `/app`, sem risco novo, só volume de texto.

### Devolvidas pela derivação de testes

<!-- Preenchido pela skill feature-test-design no step 4. -->

- **RQ-07 — quem aprova o texto das dezesseis descrições?**
  A cláusula pede "uma descrição para explicar o que cada link serve" e **não determina as frases**.
  Elas foram escritas no PRD, cada uma depois de abrir o destino correspondente.
  - **Assumido**: as frases do PRD valem. O CT-07 assere **uma** delas literalmente, como canário;
    as outras quinze são cobertas por "descrição presente e não vazia".
  - **Se negado**: troca-se a string do CT-07, não a regra. Custo baixo, por desenho.

- **RQ-07 — "cada card" obriga a cobrir os cartões que ainda não existem?**
  O mapa de descrições é escrito à mão. Um plugin novo com página no `/infra` acrescenta um cartão,
  e ele nasce **sem** frase.
  - **Assumido**: sim. O CT-08 ("nenhum cartão fica sem descrição") existe para acusar, e a
    consequência aceita é que **ele fica vermelho quando alguém instala um plugin e não escreve a
    frase**.
  - **Tensão explícita com ADR-04 desta wiki**, que recusou "um teste que compare o mapa com a
    lista de destinos" chamando o vermelho de "ruído, não defeito". O CT-08 é uma variante desse
    teste, e a leitura literal desta cláusula o exige. **A ADR e a cláusula discordam.**
  - **Decisão pendente do usuário**: manter o CT-08 (a cláusula ao pé da letra, ao custo de um
    vermelho a cada plugin novo) ou cortá-lo (a ADR, ao custo de cartão sem frase entrar sem
    ninguém notar).

- **RQ-02 e RQ-06 não são integralmente verificáveis por teste.**
  "Documentado para uso quando for necessário" e "o encaixe é páginas de links e fluxos" se
  materializam em prosa. O CT-12 cobre o que é verificável — os arquivos existem, estão
  referenciados e a flag é mencionada. Que a prosa seja **boa** não tem assertion.
  **Lacuna declarada por natureza da cláusula**, registrada para o `feature-quality-gate` não a ler
  como omissão.

- **A flag desligada deve responder 403 ou 404 na rota do hub de `/admin`?**
  O requisito não determina, e o mecanismo escolhido decide: `canAccess()` falso faz o Filament
  responder **403** (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:8-15`,
  `abort_unless(static::canAccess(), 403)`), e a rota continua registrada.
  - **Assumido**: **403 é aceitável**, porque é o mesmo comportamento que `ProjetoResource` já
    produz com a demo desligada — o kit tem uma tela de erro branda para 403
    (`anselmokossa/filament-sentinel`, ver `art/erro-403.png`).
  - **Se negado** (o usuário quiser 404, ou a rota inexistente): o desligamento passa a ser
    condicional no `->pages([...])` do provider, o que exige `if` no provider e faz o
    `discoverPages()` ter de ser recortado — mais código e o Shield deixa de gerar a permission.

## Fora de Escopo (declarado)

- **Remover o pacote do `composer.json`** — RQ-01 proíbe explicitamente
- **Apagar as três Pages, a trait `DescobreCardsDoPainel` ou `resources/css/filament/cards.css`** —
  decisão do usuário em RQ-03
- **Apagar os três arquivos de teste do hub** — eles continuam sendo a prova de que o padrão
  funciona, e passam a rodar com a flag ligada no arranjo
- **Desregistrar `FilamentCardsPlugin::make()` de `/admin` e `/app`** — o plugin é **inerte**
  (`register()` e `boot()` vazios em
  `vendor/harvirsidhu/filament-cards/src/FilamentCardsPlugin.php:13-22`), e é o que faz
  `KIT_HUB=true` bastar sem editar provider
- **Pergunta no `kit:install`** — o customizador aceita "só valor escalar que muda bit no disco" e
  fechou em cinco perguntas por decisão registrada
  (`app/Support/CustomizadorDaInstalacao.php:16-29`); uma sexta pergunta sobre navegação não passa
  nessa régua
- **Implementar uma página de fluxo de exemplo** — ver premissa de RQ-06
- **Internacionalizar os rótulos do hub** — declarado pendente no kit inteiro
- **Traduzir os rótulos de navegação de vendor** ("audits", "Exception", "Manage commands",
  "Run history") — a descrição de RQ-07 **compensa** o rótulo obscuro, não o corrige. Traduzir o
  rótulo é mexer em `->navigationLabel()` de sete plugins, cada um por um mecanismo diferente
  (método do plugin, chave de config, arquivo de tradução): é outra feature
- **Descrição nos cards de `/admin` e `/app`** — ver premissa de RQ-07
