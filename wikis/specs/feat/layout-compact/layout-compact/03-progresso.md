# Progresso — Opção de layout compacto

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Casos: `04-casos-de-teste.md`

## Estado

**Implementada, medida e documentada.** As dez cláusulas do `00` estão atendidas. Pendentes: o
cenário `CT-15` (a lacuna `L3` do `04`), o quality gate (step 8) e o PR.

| Passo do `01` | Estado | Evidência |
|---|---|---|
| 1 — Medir profundidade e peso (RQ-01…RQ-04) | ✅ | Seis ADRs em `02-decisoes-arquiteturais.md`, commit `c862361`. **Decisão: RQ-05** |
| 2 — `App\Support\DensidadeDoLayout` | ✅ | `app/Support/DensidadeDoLayout.php` · commit `589110c` · CT-10, CT-12, CT-13 |
| 3 — Os três lugares do Settings + o campo na tela | ✅ | `app/Settings/ConfiguracoesDoKit.php:densidade_do_layout:162`, a linha do mapa, a migration de settings e `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:densidade_do_layout:810` · commit `d82eccf` · CT-07, CT-08, CT-09, CT-14 |
| 4 — O render hook | ✅ | `app/Providers/KitServiceProvider.php:configureDensidadeDoLayout():611` · commit `3c6d5f6` · CT-01, CT-03, CT-05, CT-06 |
| 5 — A suíte | ✅ | `tests/Kit/DensidadeDoLayoutTest.php` — 14 CTs, **25 casos**, 47 asserções · commit `c2189d7` |
| 6 — Medir o resultado no kit, nos quatro níveis | ✅ | `## Medição`, abaixo |
| 7 — Documentação | ✅ | docs pt/en, CHANGELOG e contadores: commits `e98f436` e `022e027`. `wikis/roadmap.md` mais a seção *Futuras melhorias* nos dois READMEs: commit `bf6e799` — acrescentado depois, ver `## Desvios do Plano` → D3 |

---

## Medição

**Playwright, estilo computado, viewport 1600×1000, oito telas por nível, 2026-09-21.**

> A coluna **`padrão`** foi medida com a feature **fora da árvore**, por `git stash` — ela **não** é
> a coluna `confortavel` renomeada. É ela que transforma CT-01 e CT-04 de promessa em fato: o nível
> de fábrica é idêntico, nas onze linhas, ao kit sem a feature.

| | padrão | confortável | compacto (`0.2rem`) | denso (`0.175rem`) |
|---|---|---|---|---|
| linha da tabela | 56,0 px | 56,0 px | **46,4 px (−17,1%)** | **42,9 px (−23,4%)** |
| tabela de 10 linhas | 612 px | 612 px | **509,2 px (−16,8%)** | **471,8 px (−22,9%)** |
| `.fi-btn` | 36,0 px | 36,0 px | 32,8 px (−8,9%) | 31,2 px (−13,3%) |
| `.fi-input` | 36,0 px | 36,0 px | 28,8 px (−20,0%) | 25,2 px (−30,0%) |
| item de sidebar | 40,0 px | 40,0 px | 32,8 px (−18,0%) | 31,2 px (−22,0%) |
| stat (`/admin`, `/infra`) | 150 / 140 px | 150 / 140 px | 137,2 / 127,2 px (−8,5%) | 130,8 / 120,8 px (−12,8%) |
| `.fi-section` | 92 px | 92 px | 77,6 px (−15,7%) | 70,4 px (−23,5%) |
| ícone | 24 px | 24 px | 19,2 px (−20%) | 16,8 px (−30%) |
| largura da sidebar | 320 px | 320 px | **320 px** | **320 px** |
| topbar | 64 px | 64 px | **64 px** | **64 px** |
| overflow horizontal | não | não | não | não |

**As quatro superfícies do escopo respondem** — stats, tabela, menu e botão —, e isso é medição, não
dedução de `--spacing` aparecer no seletor.

### Achados da Medição

#### A1 — Largura da sidebar e altura da topbar **não** apertam, e não é defeito

320 px e 64 px nos três níveis. A largura do menu sai de **`--sidebar-width`**, não de `--spacing`;
a topbar tem **altura fixa**. Só a **altura dos itens** do menu aperta (40,0 → 32,8 → 31,2 px).

Isto não contraria o invariante do `00` ("nenhuma superfície fica parcialmente compacta"): o
invariante fala das **quatro superfícies nomeadas**, e a largura da sidebar não é nenhuma delas.
Acrescentar `--sidebar-width` à escala é barato — é o candidato mais provável a entrar primeiro, e
está no item 5 do roadmap.

#### A2 — Os cartões do Pulse ficam em 128 px nos três níveis

`.fi-section` de `/infra/pulse` não responde, porque os cartões do Pulse carregam **CSS própria**.
As **tabelas** do Pulse apertam normalmente — é só o cartão.

Corrigir exigiria escrever regra mirando o Pulse, que é exatamente o acoplamento a classe de vendor
que a ADR-03 recusa como mecanismo. Documentado no item 5 do roadmap, sem plano.

#### A3 — O ganho no kit é **maior** que na demo limpa, e o denso alcança o tema pago

Com o **mesmo** `0.2rem`, a demo limpa da ADR-03 rendeu **−11,3%** e o kit rendeu **−16,8%**. As
tabelas do kit têm mais coluna e mais altura por linha, então a mesma declaração rende mais aqui.

E o `denso` chega a **−22,9%**, contra os **−22,5%** do tema compacto **pago** oficial (ADR-05) —
praticamente o mesmo ganho, com **uma** declaração em vez de 548 blocos de regra sobre 370 classes
de vendor. O preço é a distorção de proporção, que o tema pago não tem: ele não mexe em `--spacing`
nem em `--text-*`.

Este número muda o peso da ADR-05: a recomendação de compra continua válida para projeto único que
não aceite a distorção, mas deixou de ser "o kit entrega metade do possível". Entrega quase tudo, com
um preço diferente — e o preço está declarado nas docs pt/en, junto com o ganho.

### A armadilha de ambiente: a primeira rodada mediu o app errado

**Registrar isto é o item mais útil desta seção.** A primeira rodada de medição subiu o servidor na
porta **8123** e produziu uma tabela inteira — coerente, com percentuais plausíveis, sem erro, sem
aviso. Só que **havia um servidor de outro worktree já escutando na 8123**, e o Playwright mediu
aquele app: o kit sem a feature, respondendo os três níveis com os mesmos números.

O sintoma é o pior possível: **nada falha**. A página responde 200, a screenshot sai correta, os
números são consistentes entre si, e a conclusão que sairia dali seria *"a declaração não tem
efeito"* — o oposto do fato.

A rodada foi refeita na porta **8347**, e é a dela que está na tabela acima.

**A lição, para a próxima medição de navegador**: antes de medir, **provar que o servidor medido é o
seu**. Uma porta livre não é suficiente — um `curl` que confirme uma string que **só a sua árvore
tem** é, e custa segundos. Sem essa prova, toda a medição vira uma afirmação sobre um app
desconhecido.

---

## Notas de Implementação

### N1 — O defeito real: `->options(DensidadeDoLayout::class)` derrubava a tela inteira

**O único defeito de código desta entrega, e ele foi pego pela suíte.**

A primeira versão do campo era `->options(DensidadeDoLayout::class)`, que é a forma idiomática do
Filament para enum e parece obviamente certa. A cadeia do defeito:

1. Passar a **classe** faz o Filament castear o estado de volta para **instância do enum**
2. O `fill()` do spatie atribui o estado direto à propriedade
   (`vendor/spatie/laravel-settings/src/Settings.php:fill():178`)
3. A propriedade é tipada `string` (`app/Settings/ConfiguracoesDoKit.php:densidade_do_layout:162`)
4. `TypeError` — **ao salvar a tela inteira**, não só este campo

**A amplitude é o que torna o caso caro**: a tela de configurações salva todas as abas numa chamada
só, então a falha derrubou **60 casos** de features que o diff nem tocou — login social, anti-robô e
login unificado, todos vermelhos por causa de um campo novo em outra aba.

**Correção**: `DensidadeDoLayout::opcoes()`
(`app/Support/DensidadeDoLayout.php:opcoes():131`), que devolve o array cru `valor => rótulo`. O
array cru também é mais seguro na volta: um valor ilegível gravado na tabela abre a tela com o campo
em branco, em vez de estourar `from()` antes de renderizar.

**Cobertura**: **CT-14**, e só ele. Os outros treze cenários gravam **direto na tabela**, que é
justamente o caminho que contorna o elo quebrado — nenhum deles pegava. É o argumento inteiro da
rule `.ai/rules/testes.md` (*"uma tela aberta não é uma tela que grava"*) numa entrega concreta: o
cenário de gravação por componente não é formalidade de checklist, foi o que pegou o defeito.

**O que isto vale como lição**: a vizinhança de um campo novo numa tela de settings é a tela
**inteira**. O raio de explosão de um campo mal declarado não é o campo.

### N2 — O `null` de `espacamento()` é contrato, não conveniência

`app/Support/DensidadeDoLayout.php:espacamento():148` devolve `null` no `Confortavel`, e é isso que
faz o hook devolver string vazia em vez de `<style></style>`. Um `'0.25rem'` ali seria equivalente na
tela e **mentiria na inspeção** — toda instalação que nunca mexeu nisto passaria a carregar uma
declaração inútil em toda página de todo painel.

### N3 — A coerção precisa existir nos **dois** lados, e o motivo não é simetria

`coagir()` é chamada em `config/kit.php:densidade_do_layout:287` **e** em
`app/Support/DensidadeDoLayout.php:deConfig():217`, e as duas chamadas não são redundantes: as duas
entradas escapam uma da outra. O arquivo de config coage o que veio do `.env`; `deConfig()` coage o
que veio do **banco**, que `aplicarNaConfig()`
(`app/Settings/ConfiguracoesDoKit.php:aplicarNaConfig():504`) escreve **direto na config**, sem
passar pelo arquivo. Sem a segunda, uma linha adulterada em `settings` chegaria crua ao render hook
de toda tela. É o que CT-11 mede, e é por isso que CT-10 sozinho não bastava.

### N4 — Três guardas do próprio kit ficaram vermelhas, e nenhuma por acaso

- `CitacoesDeCodigoTest [CT-26]` — o `use DensidadeDoLayout` novo em `config/kit.php` empurrou a
  chave `arte_do_login` de `:135` para `:136`, e duas citações apontavam para a linha velha. É
  exatamente o envelhecimento que `.ai/rules/specs.md` descreve e que o caso existe para pegar
- `SiteDeDocumentacaoTest` — contadores dos READMEs: features especificadas 64 → 65, arquivos de
  teste 147/173 → 148/174
- `KitInfoTest [CT-06]` — a âncora de propriedades do settings, 54 → 55. Ela é escrita à mão de
  propósito: é o número que fica vermelho para **obrigar a decisão**

---

## Desvios do Plano

> O `01` foi escrito **depois** da implementação, então "desvio do plano" aqui significa outra
> coisa: são os pontos em que a execução divergiu do que o `00` e o `02` previam. Cada um corrigiu
> a fonte, ou está marcado como aberto.

### D1 — O MCP do Playwright foi trocado pelo pacote npm

O `00` nomeia a ferramenta (*"use o mcp do playwrite para navegar"*). O MCP não estava configurado
na sessão, e a premissa adotada — pacote npm `playwright` — já estava declarada em
`00-requisito.md` → `## Ambiguidades`. **Ganho colateral**: o pacote npm lê **estilo computado**, e
é isso que tornou toda a medição desta wiki possível. Sem ele, os números seriam estimativa.

### D2 — A medição da ADR-03 ficou defasada pela do kit, e as duas continuam no `02`

A ADR-03 registra **−11,3%** com `0.2rem`, medido na demo limpa; o kit rendeu **−16,8%** com o mesmo
valor. As duas leituras estão certas e medem coisas diferentes, e por isso a ADR **não** foi
reescrita: ela registra o que se sabia quando a decisão foi tomada. A leitura do kit está aqui
(`## Medição`, A3), no docblock do enum e nas docs de usuário — que é onde ela governa.

### D3 — RQ-10 atravessou oito commits escrita e não entregue

`wikis/roadmap.md` foi escrito cedo, com os cinco itens completos, e ficou **fora do índice do
git** — `git status` devolvia `?? wikis/roadmap.md` — enquanto os oito primeiros commits da
feature passavam. Junto com ele faltava a linha do `README.md` que RQ-10 pede por extenso
(*"utilizando no @README.md"*).

**Fechado pelo commit `bf6e799`**, que versionou o roadmap e acrescentou a seção *Futuras
melhorias* a `README.md` e `README.en.md`, mais a linha em `wikis/README.md`.

**O que não fechou junto, e é o que importa**: a suíte ficou **verde do começo ao fim**, porque
nenhum caso afirma sobre a existência do documento nem sobre a ligação. O cenário que teria pego a
omissão no dia está escrito em Gherkin no `04-casos-de-teste.md` (`L3`, como `CT-15`) e continua
**não implementado** — ver `## Pendências` → P1. O kit **tem** o padrão de oráculo documental
(`tests/Kit/RedeDeDocumentacaoTest.php`, `tests/Kit/SiteDeDocumentacaoTest.php`), e ele não foi
usado.

---

## Pendências

### ~~P1~~ — FECHADA em 2026-09-21

`CT-15` foi escrito como `[CT-48]` de `tests/Kit/SiteDeDocumentacaoTest.php`, e o `/code-review`
mostrou que a primeira versão dele **cobria só metade do problema** — ver o step 7.5 abaixo. O
texto original da pendência fica abaixo, porque a análise que ela fez continua certa.

### P1 (texto original) — `CT-15` não escrito: a cláusula documental continua sem oráculo

**Não bloqueia nenhuma cláusula — bloqueia a garantia de que ela continue atendida.**

RQ-07 a RQ-10 estão entregues (commit `bf6e799`). O que falta é o caso que impede a omissão de
voltar. O Gherkin está pronto em `04-casos-de-teste.md` → `L3`:

- existe `wikis/roadmap.md` **na árvore versionada**
- o texto dele diz que descreve o futuro **do kit**, não o do projeto de quem o instala — a
  asserção não é enfeite: o arquivo **viaja** para todo projeto criado do kit (`wikis/*.md` fora do
  `export-ignore`, `/wikis/specs export-ignore` em `.gitattributes:24`), e sem essa frase ele se lê
  como promessa
- o `README.md` contém o link para ele

Roteamento: destino **3 — teste**. Custa três asserções, no molde de
`tests/Kit/RedeDeDocumentacaoTest.php`.

### P2 — Quality gate (step 8) e PR

Não executados. A seção `## Quality Gate` abaixo está vazia, e enquanto estiver, a feature **não**
está concluída.

---

## Duas medições confundidas, e as duas por concorrência com a própria medição

Ficam registradas porque as duas produziram um resultado que se lê como defeito e não era.

**A primeira, no `composer test:kit`.** Num shell sem o `composer` no PATH, o comando imprime
`command not found` e **sai com código 0**. Ler só o código de saída registraria como verde uma
suíte que não rodou — e isso aconteceu justamente ao corrigir o achado 3 do `/code-review`, que era
exatamente sobre evidência não conferida.

**A segunda, mais sutil.** A regressão reprovou em
`tests/Kit/ConfiguracoesDoKitTest.php:tem a tabela de settings migrada` com *"55 não é 54"*. Não era
defeito: eu rodei a **mutação de falsificabilidade** — que apaga a linha do `mapaDeConfiguracao()` —
**enquanto a suíte corria na mesma árvore**. Ela leu o arquivo mutado, e a contagem do banco (55
linhas semeadas) deixou de bater com a do mapa (54, com a linha apagada).

A lição é a mesma das duas: **medição não pode compartilhar árvore com o que a altera.** Mutação
de código e suíte de fundo não convivem, e o sintoma resultante aponta para o lugar errado — neste
caso, para um contador de settings que estava correto o tempo todo.

## Step 7.5 — `/code-review` no diff (2026-09-21)

Três achados. **Dois eram defeito de verdade, e os dois moravam em código escrito para fechar uma
lacuna apontada por outro gate** — o que é a lição desta seção.

### Achado 1 — o roadmap não chegava a quem JÁ instalou (alto)

`wikis/roadmap.md` entrou no repositório e **não** entrou em `KitUpdate::CAMINHOS_DO_KIT`. O
`kit:update` compara duas versões restrito a essa lista: arquivo fora dela não é entregue nunca.
`tests/Kit/KitUpdateTest.php` reprovava — era a **única** falha da suíte `Kit` inteira.

**O que faz este achado valer mais que a correção**: `CT-48` foi escrito exatamente para garantir
que o roadmap "viaja com o projeto", e passava verde. Porque "viajar" tem **dois** caminhos, e ele
afirmava só um:

| Caminho | Governado por | Atende |
|---|---|---|
| `composer create-project` | `.gitattributes` | quem instala **agora** |
| `php artisan kit:update` | `KitUpdate::CAMINHOS_DO_KIT` | quem **já** instalou |

Enquanto isso o README prometia, nas duas línguas, que o roadmap *"vem junto com o seu projeto"*.
A promessa estava no texto, o mecanismo do `create-project` estava certo, o do `kit:update` não
existia, e o caso desenhado para cobrir a promessa **não tocava** nele.

Fechado nos dois lados. Para usar a lista sem repetir reflexão, o helper `caminhosDoKit()` saiu de
dentro de `KitUpdateTest.php` e foi para `tests/Pest.php` — helper usado por dois arquivos só
estoura em `--parallel`, `--tia` ou arquivo solto (`.ai/rules/testes.md`), e as três formas foram
rodadas.

Nit do mesmo achado, corrigido junto: o comentário do `.gitattributes` dizia "dez documentos de
topo de `wikis/`"; são onze.

### Achado 2 — o nível ilegível travava a tela inteira (médio)

`Select` acrescenta sozinho um `Rule::in()` das próprias opções. Com `compact` gravado na linha de
settings, o formulário nascia com esse valor e a validação o recusava — e o efeito **não** fica no
campo: a tela parava de salvar por inteiro.

**Reproduzido antes de corrigir**: `fillForm(['nome_da_aplicacao' => 'Projeto Novo'])->call('save')`
falhava com `data.densidade_do_layout => "O campo densidade do layout não contém um valor válido."`.
Quem só queria trocar o nome da aplicação levava erro num campo que não tocou.

O arquivo da tela **já descrevia esse defeito** no docblock de `comValorConfigurado()`, para
`MAIL_MAILER=ses` — *"nem o nome da aplicação grava"*. O caso do `.env` tinha sido tratado; este
nasceu com a densidade e não foi. E CT-11 já existia para valor ilegível, **mas só pelo lado do
render** — a metade da tela estava aberta.

Fechado com `DensidadeDoLayout::coagir()` em `mutateFormDataBeforeFill()`, e **não** com
`comValorConfigurado()`: os dois problemas só se parecem. `ses` é transporte legítimo fora da lista
curta, e rebaixá-lo seria perda de dado; nível de densidade tem vocabulário fechado, e `compact` é
lixo — oferecê-lo como opção marcada exibiria lixo e o gravaria de volta.

CT-16 nasceu **vermelho** contra a implementação original, e o oráculo dele é o **campo alheio**:
asserir só que a densidade grava deixaria passar uma correção que conserta o campo e mantém a tela
travada.

### Achado 3 — evidência datada de antes do commit que a invalidou (baixo)

A Verificação Final registrava `composer test:kit` com **2.715 verdes, 0 falhas**, e esse número
era de **antes** do commit `bf6e799` (o roadmap) — que é justamente o que deixou a suíte vermelha.
A linha seguinte fechava o item do roadmap sem repetir a regressão que o roadmap quebrou.

Corrigido remedindo, não reescrevendo a data.

### O padrão, que vale para além desta wiki

Os dois achados de código estavam em trabalho feito para **fechar lacuna apontada por outro gate**.
Fechar lacuna é escrever código novo, e esse código entra **depois** da revisão que o motivou — ou
seja, sem gate. O step 7.5 foi o único que olhou o diff depois disso.

---

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent` — `passed`, 2026-09-21
- [x] `vendor/bin/filacheck --fix` — **17/17 regras passaram**, 2026-09-21
- [x] `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` — **30 passaram, 66 asserções**, remedido em 2026-09-21 (antes: 25/47, antes de CT-16 e CT-17)
- [x] **Regressão completa** (obrigatória por tocar infra compartilhada) — **2.721 passaram, 10.537 asserções, 0 falhas**, 2026-09-21, remedida pela **terceira** vez: depois do `/code-review` (2.717) e de novo depois do quality gate, que acrescentou a largura do menu

  **Rodada por `php artisan test --testsuite=Kit,Tenancy --parallel`, não por `composer test:kit`** — ver a armadilha do código de saída 0 logo abaixo

  O número anterior registrado aqui (2.715 / 10.508) era de **antes** do commit `bf6e799`, que é
  justamente o que tinha deixado a suíte vermelha — achado 3 do `/code-review`. Remedido, não
  reescrito.

  **E a remedição quase repetiu o mesmo erro.** A primeira tentativa rodou `composer test:kit` num
  shell sem o `composer` no PATH: a saída foi `composer: command not found` e o **código de saída
  foi 0**. Ler só o código de saída teria registrado como verde uma suíte que não rodou — o mesmo
  defeito do achado 3, cometido ao corrigi-lo. Rodado direto por
  `php artisan test --testsuite=Kit,Tenancy --parallel`, que é o que o script faz
- [x] **Falsificabilidade por mutação** — apagar a linha do `mapaDeConfiguracao()` reprova **10 dos 30 casos**, remedido em 2026-09-21 (antes: 7 de 25)
- [x] **Custo medido** — **zero request e zero query a mais**: a leitura sai de `config()`, já em memória desde o `boot()`. Bate com o `## Modelo de Execução` do `01`, 2026-09-21
- [x] **Medição no kit** — quatro níveis, oito telas por nível, `padrão` medido com a feature fora da árvore por `git stash`, 2026-09-21
- [x] **Guardas do kit reconciliadas** — `CitacoesDeCodigoTest` (`arte_do_login` :135 → :136), `SiteDeDocumentacaoTest` (contadores) e `KitInfoTest` (54 → 55 propriedades), commit `022e027`, 2026-09-21
- [x] **Docs pt/en e CHANGELOG** — seção nova nas duas línguas **com o preço declarado junto com o ganho**, commit `e98f436`, 2026-09-21
- [x] **Citações `arquivo:símbolo:linha` reverificadas** — **36/36 ok**, por varredura própria em 2026-09-21.

  A declaração anterior, de "21/21 ok", estava errada por dois motivos, e o quality gate pegou os
  dois. O primeiro: o padrão usado não cobria o formato `arquivo:simbolo():linha` — **com
  parênteses** —, que é justamente como as cinco citações a `tests/Pest.php` estão escritas. O
  segundo: a evidência declarada apontava o `CitacoesDeCodigoTest`, que **exclui `wikis/specs/**`
  por decisão registrada** e portanto nunca conferiu esta wiki.

  Erradas de fato: **14**, não 8. Oito o gate achou; as outras **seis eu criei depois dele**, ao
  acrescentar `larguraDaSidebar()` — o método empurrou `DensidadeDoLayout.php` em ~53 linhas e
  invalidou toda citação abaixo dele. É a demonstração mais limpa do que esta dimensão mede:
  citação por número de linha envelhece a cada commit que mexe no arquivo citado. Duas citações do `02` estavam deslocadas pela própria feature (`aplicarNaConfig()` :478 → :504 e o ponto de chamada :346 → :355, empurrados pela propriedade nova) e foram corrigidas **na fonte**, com a nota inline que o step 7 exige, 2026-09-21
- [x] **`vendor/bin/pest tests/Kit/CitacoesDeCodigoTest.php --compact`** — verde. `wikis/specs/**` fica **fora** do escopo desse caso por decisão registrada (wiki é registro datado), então ele não confere esta wiki: quem confere é o grep da linha acima, 2026-09-21
- [x] **Wiki completada** — `01`, `03` e `04` escritos contra o código existente, com as lacunas declaradas em vez de caladas, 2026-09-21
- [x] **`wikis/roadmap.md` commitado e ligado ao `README.md`** — RQ-07 a RQ-10 fechadas, commit `bf6e799` (roadmap + *Futuras melhorias* nos dois READMEs + linha em `wikis/README.md`), 2026-09-21
- [x] **CT-15** (oráculo documental do roadmap) **escrito** — vive em `tests/Kit/SiteDeDocumentacaoTest.php` sob o ID local `[CT-48]`, o arquivo que já é dono dos contadores de README. Verificado por mutação: `export-ignore` no roadmap, link quebrado no README e índice da wiki sem a entrada deixam os três o caso vermelho, 2026-09-21
- [x] **`/code-review` (PR-02)** — três achados, todos fechados. Ver `## Step 7.5` abaixo, 2026-09-21
- [x] **CT-16** — a tela de configurações não trava com nível ilegível gravado. Nasceu **vermelho** contra a implementação original, 2026-09-21
- [ ] `feature-quality-gate` (step 8)
- [ ] `git commit` da wiki e PR

---

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | **n.a.** | as duas regras são `ContextoDePapeis` × `assignRole` e DTO em `app/Data` — a feature não cria nenhum dos dois |
| `settings.md` | `app/Settings/**` | **aplicada** | os **três lugares** estão presentes e guardados por CT-07 (propriedade, linha do mapa, linha semeada). O critério de "pode virar Settings" é satisfeito pelo mecanismo, não por analogia: a chave é lida **por request** num render hook — `.ai/rules/settings.md:13` fixa *"numa closure de render hook… pode ir para o Settings"*. Ver ADR-06 |
| `config.md` | `config/**` | **aplicada** | "falhe fechado": `coagir()` com `tryFrom() ?? padrao()` nas **duas** portas (CT-10, CT-11). "Uma pergunta, uma dona": a densidade não tinha dona antes e passa a ter exatamente uma, `kit.densidade_do_layout` — nenhum consumidor lê `env()` direto |
| `css-filament.md` | `app/Providers/**` | **aplicada** | a rule `CSS de plugin que declara @layer reordena a página inteira` é o que esta feature usa **a favor**: a declaração fica **fora** de cascade layer e por isso não depende de ordem de folha. Guardado por CT-05. `configureOrdemDasCascadeLayers()` da `v0.37.1` continua intacta, e CT-01 exige que ela continue saindo no bloco |
| `providers.md` | `app/Providers/**` | **n.a.** | a rule é sobre rota nascer no `KitServiceProvider` com `web` explícito — a feature não registra rota |
| `filament.md` | `app/Filament/**` | **n.a.** | nenhum Resource, Page, Widget ou Action novo; a feature acrescenta **um campo** a uma Page existente, que já tem permissão e cobertura próprias |
| `pages.md` | `app/Filament/Admin/Pages/**` | **aplicada** | a rule é *"segredo em formulário: esconder na tela não é esconder no HTML"*. A densidade **não** é segredo, e a decisão está afirmada nos dois sentidos por CT-09 — fora da lista `encrypted()` **e** com o `payload` legível |
| `testes.md` | `tests/**` | **aplicada** | *"uma tela aberta não é uma tela que grava"* — CT-14 cobre a gravação por componente Livewire, e foi ele que pegou o defeito N1. Helper local (`gravarDensidade`) fica no arquivo porque só **um** arquivo o usa; os quatro helpers cruzados vêm de `tests/Pest.php`, como a rule manda |
| `specs.md` | `wikis/specs/**` | **aplicada** | toda afirmação sobre vendor tem `arquivo:símbolo:linha` conferido — o layout base, o `viteTheme()`, o `fill()` do spatie e o `cell.css`, citados por extenso nas seções acima; a conferência mecânica está em `## Verificação Final` |
| `general.md` | `composer.json` | **n.a.** | a feature não toca `composer.json`. O Filament Blueprint **não** foi ligado nesta rodada; `composer.lock` intacto |

---

## Quality Gate

<!-- Step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: **REPROVADO → especificação** · **Data**: 2026-09-21
- **Relatório**: `06-relatorio-qa.md`
- **Severidade**: Blocker 0 · Major 3 · Minor 3 · Cosmético 0

| Achado | Título | Sev. | Destino |
|---|---|---|---|
| QA-01 | O menu entrega só metade do compacto — o invariante do `00` é fechado por releitura, não por decisão do usuário | Major | 1 — especificação |
| QA-02 | O `01` ainda carrega a evidência que o `/code-review` invalidou (2.715/10.508, "14 CTs, 25 casos", CT-15 "pendente", diff de seis arquivos) e não tem passo para o código do 7.5 | Major | 1 — especificação |
| QA-03 | Oito citações `arquivo:símbolo:linha` erradas, com o `03:327` declarando "21/21 ok" | Minor | 1 — especificação |
| QA-04 | CT-16 e a quarta asserção de CT-48 existem no código sem cenário no `04`; índice, regras e contagens divergem | Major | 3 — teste |
| QA-05 | A guarda de `export-ignore` de `[CT-48]` reimplementa o `.gitattributes` com regex e deixa passar `wikis/** export-ignore` | Minor | 3 — teste |
| QA-06 | Docs pt/en dizem "duas coisas não apertam"; são três (falta o Pulse) | Minor | 1 — especificação |

**Confirmado pelo gate, para não ser reaberto**: regressão remedida em **2.717 / 10.525 / 0 falhas**;
Pint `passed` e FilaCheck **17/17**; o roadmap **viaja pelos dois caminhos**, verificado por
`git archive HEAD | tar -t` (11 documentos de topo, zero `wikis/specs`) e por
`KitUpdate::CAMINHOS_DO_KIT`; nenhum log e portanto nenhum PII em log; zero query e zero request a
mais; nenhuma célula de permissão nova.

**Entrada que o gate vai encontrar**, e vale avisar para ele não gastar ciclo redescobrindo:

- **RQ-07 a RQ-10 estão entregues** (commit `bf6e799`), mas atravessaram oito commits **escritas e
  não versionadas** sem que nada ficasse vermelho. A lacuna que permitiu isso — `CT-15`, oráculo
  documental — continua aberta e está declarada em `## Pendências` → P1
- **RQ-06 é ⛔ por desenho** — excluída por RQ-04, que escolheu RQ-05. Marcar como "não atendida"
  seria erro de leitura: as duas são mutuamente exclusivas
- **A prova do pixel é medição, não suíte** — lacuna `L1` do `04`, declarada

---

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas contra o código real

| Premissa | O código real diz | Correção |
|---|---|---|
| "o Filament tem densidade global" | **não tem**: `compact()` é por componente (`Section`, `EmptyState`, `Repeater`), `Table` não tem nenhum equivalente, e nenhum dos 34 concerns de `Panel` é de espaçamento | ADR-01 escrita a partir da varredura |
| "o padding da célula mora em `.fi-ta-cell`" | `vendor/filament/tables/resources/css/cell.css:2` é `@apply p-0`; o padding mora no elemento da coluna, em nove arquivos `columns/*.css` | ADR-03 — e a tentativa ingênua **piorou** 21,9% |
| "ligar em runtime é a parte difícil" | é a **barata**: render hook é avaliado por request (`vendor/filament/filament/resources/views/components/layout/base.blade.php:STYLES_BEFORE:44`) e o kit já faz isso desde a `v0.37.1` | ADR-02 inverteu a premissa do requisito |
| "densidade cai na armadilha do `settings.md`" | **não cai por render hook; cairia por `viteTheme()`**, que é resolvido no registro do painel e não aceita `Closure` (`vendor/filament/filament/src/Panel/Concerns/HasTheme.php:viteTheme():27`) | ADR-06 |

### Varredura da classe irmã

**Classe nova**: `App\Support\DensidadeDoLayout`. A irmã escolhida foi `App\Support\BooleanoDoEnv`,
por papel idêntico — coerção de valor de `.env` consumida por `config/kit.php`.

**Ocorrências**: `config/kit.php` (o único lugar onde a irmã aparece). A classe nova entrou lá, e
também em `app/Settings/ConfiguracoesDoKit.php` e no render hook, que são consumo e não lista
paralela. **Nenhuma lista paralela encontrada além das previstas** — a feature não cria Page,
Resource nem Widget, que é o que produz listas em `config/filament-shield.php`, seeders e
inventários de teste.

O que **existe** de lista paralela, e foi atualizado: a âncora manual de `tests/Kit/KitInfoTest.php`
(54 → 55 propriedades do settings). Foi ela que ficou vermelha e obrigou a decisão — exatamente o
papel que o comentário dela reivindica.

### Superfície Livewire

A tabela não se aplica na forma cheia: a feature **não cria** Page, Widget nem componente. Os dois
pontos que ela acrescenta:

| Ponto | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| `Select::make('densidade_do_layout')` | `$wire` da Page de settings, que já existe e já é protegida | vocabulário fechado no Select **e** `coagir()` no consumo — a validação do formulário não é a única guarda | `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:densidade_do_layout:810` · CT-14 |
| O valor gravado, que vira **concatenação de string CSS** no render hook | quem escreve na tabela `settings` por fora, ou no `.env` | `coagir()` antes do uso, nas duas portas — `tryFrom() ?? padrao()` | `app/Support/DensidadeDoLayout.php:coagir():219` · CT-10, CT-11 |

O segundo é o que importa: **é valor de configuração que vira texto emitido no HTML**. Sem a
coerção, um `payload` adulterado seria concatenado cru dentro de `<style>`. A guarda existe e está
medida.

---

## Retrospectiva

**Funcionou bem**

- **Medir antes de decidir, e medir de novo no alvo certo.** A ADR-03 mediu na demo e a decisão saiu
  de lá; a medição no kit mudou o número para melhor (−11,3% → −16,8%) e produziu três achados que
  nenhuma leitura de código teria encontrado. Medir duas vezes custou uma sessão e evitou duas
  afirmações erradas na documentação de usuário
- **A tentativa ingênua ter sido medida em vez de temida.** `.fi-ta-cell{padding-block:.5rem}`
  **piorou** 21,9%. Se o caminho tivesse sido descartado por intuição, o argumento da ADR-03 seria
  "acho que dá trabalho" em vez de um número — e alguém o reabriria na próxima release
- **O cenário de gravação por componente.** CT-14 parecia redundante diante de treze cenários
  verdes, e foi o único que pegou o defeito. A rule do projeto já dizia isso; aqui ela cobrou

**Faltou**

- **O `01`, o `03` e o `04` não existirem durante a implementação.** A ordem da skill é `04` antes
  do código, e ela foi invertida. O custo não é teórico: a cláusula que **não** tem caso de teste
  (RQ-10) é exatamente a que ficou por entregar, e ninguém percebeu porque não havia mapa de
  cobertura para consultar. Um `## Cobertura do Requisito` escrito antes teria mostrado RQ-07 a
  RQ-10 sem passo fechado
- **Oráculo documental para cláusula documental.** O kit **tem** o padrão
  (`RedeDeDocumentacaoTest`, `SiteDeDocumentacaoTest`) e ele não foi usado. `wikis/roadmap.md` ficou
  oito commits no limbo — escrito, completo, fora do índice —, e o que o tirou de lá foi alguém
  reparar, não a suíte. Três asserções teriam feito o trabalho no dia
- **Provar o servidor antes de medir.** A rodada perdida na porta 8123 não deu nenhum sinal de
  erro — e uma medição silenciosamente errada é pior que uma medição que falha. Um `curl` de
  confirmação passa a ser passo obrigatório de qualquer medição de navegador neste repositório
