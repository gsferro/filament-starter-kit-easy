# Casos de Teste — Anexos privados

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano.
>
> **Segunda versão.** A primeira passou por revisão adversarial e voltou com 12 lacunas, duas
> delas apoiadas em afirmações minhas **factualmente falsas**. O registro está em
> `## Revisão Adversarial`, ao fim. Ler antes de confiar em qualquer oráculo daqui.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Disco de destino e URL | 3 | 3 | **9** | completo |
| Migração das mídias legadas | 3 | 3 | **9** | completo |
| Documentação | 1 | 1 | 1 | mínimo |

- Técnicas: EP, tabela de decisão (caminho × disco), **oráculo diferencial**, rastreio de efeito
  sobre dado derivado, matriz estado × operação
- Cenários: 21 · Regras: 6 · Mutantes previstos: 24 · Sem matador: 3 (declarados)

## Fatos de plataforma que decidem o desenho

Verificados no vendor **antes** de escrever oráculo. Cada um invalidaria cenários se ignorado.

| # | Fato | Evidência | Consequência para os CT |
|---|---|---|---|
| **F1** | `ServeFile` valida a assinatura **antes** de checar existência | `ServeFile.php:27-32` | 403 sem assinatura é devolvido para **qualquer** caminho — protegido, inexistente ou quebrado. Oráculo negativo sozinho não prova proteção |
| **F2** | `getUrl()` de disco `local` devolve `/storage/{path}` **sem assinatura** | `FilesystemAdapter.php:825-839`; medido | a URL "normal" da mídia privada **sempre** 403. Falha fechado, e é indistinguível de disco quebrado |
| **F3** | `ServeFile` pula a assinatura inteira se `visibility === 'public'`, lendo o **config capturado no boot** | `ServeFile.php:55-61`; captura em `FilesystemServiceProvider.php:96-117` | `config()->set()` e `Storage::fake()` **não** alcançam esse valor. O mutante correspondente não morre por cenário de rede |
| **F4** | `Storage::fake()` **não** substitui a rota `storage.{disk}` | `Storage.php:103-126` → `FilesystemManager::set()` | cenário de rede com fake mede o disco falso pela rota real — parece válido e mede outra coisa |
| **F5** | Existe **`PUT storage/{path}`** (`storage.local.upload`), com `middleware: []` | `route:list --json`, medido | superfície de **escrita** anônima. Não estava na varredura da primeira versão |
| **F6** | `conversions_disk` cai no disco do original quando `MEDIA_CONVERSIONS_DISK` é vazio | `FileAdder.php:445-466`; `config/media-library.php:47` | afirmar "conversão no mesmo disco do original" é **tautologia** no caminho de escrita |
| **F7** | O sombreamento do symlink ocorre **antes** do framework | `server.php:12-14` (`return false` antes do `index.php`) | `$this->get()` nunca consulta `public/`. Cenário de colisão é **inobservável** em teste HTTP |

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | `config/media-library.php`, `config/filesystems.php`, `Projeto::registerMediaCollections()`, comando de migração, `.env.example` | CT-01, CT-03, CT-05, CT-18 |
| **F** | gravar (dois caminhos), gerar URL, **servir**, **receber PUT**, migrar | CT-01…CT-21 |
| **D** | mídia nova; legada `disk=public`; **conversões, com coluna própria**; coleção com disco explícito; **mídia de outro `model_type`** | CT-02, CT-12, CT-15, CT-17 |
| **I** | upload pela tela; `addMedia()`; comando artisan; **rota GET** `storage.local`; **rota PUT** `storage.local.upload` | CT-04, CT-06…CT-11 |
| **P** | driver `local` com `serve`; **symlink no mesmo URI**; config lido no boot | CT-18, CT-19 |
| **O** | usuário do painel; operador do comando; **anônimo de posse da URL** | CT-06…CT-11 |
| **T** | expiração da assinatura; **ordem arquivo→banco** na migração | CT-14 |

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — mídia nova nasce em disco privado por qualquer caminho, e os caminhos **concordam** | disco (completo) | RQ-02, RQ-04 | tabela de decisão | CT-01…CT-05 |
| **R2** — o arquivo só sai com assinatura válida, e **sai** quando ela existe | disco (completo) | RQ-02, RQ-03 | **oráculo diferencial** | CT-06…CT-11 |
| **R3** — a coleção declara o disco, e a declaração vence o default | disco (completo) | RQ-04 (ADR-02) | EP | CT-03 |
| **R4** — a migração leva **original e conversões**, de **qualquer model**, sem perder arquivo, e é reexecutável | migração (completo) | RQ-05 | estado × operação | CT-12…CT-17 |
| **R5** — a migração não desfaz escolha explícita de disco público | migração (completo) | RQ-05 (ADR-04) | EP | CT-16, CT-17 |
| **R6** — a documentação descreve o comportamento real | documentação (mínimo) | RQ-06 | — | CT-20, CT-21 |

> **R6 não tem poder de falsificação sobre R1–R5.** Ela é grep de prosa: fica verde com o código
> inteiro vazando, desde que os textos estejam certos. Declarado aqui porque a primeira versão
> deixava isso implícito, e checklist com item ✅ que não protege nada é pior que lacuna.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome do comando | escolha de implementação | detalhe do cenário |
| o disco chamar-se `local` | o requisito pede **privado**, não `local` | os `Então` falam de recuperabilidade |

> **A recusa acima abriu um buraco na primeira versão**, apontado pela revisão: sem fixar o nome,
> nada impedia que a tela gravasse num disco privado e o `addMedia()` em **outro** — os dois
> satisfazendo "não servido sem assinatura", e o comando de migração não enxergando nenhum.
> **CT-05** fecha isso afirmando **coerência entre os caminhos**, sem nomear o disco.

## Setup Global

### Personas

- `usuarioComPapel('master_global')` — opera a tela e o comando
- **anônimo** — sem `actingAs`; ator dos cenários centrais

### Fixtures

- `tenant('Acme', 'acme')` + `noPainelDa($organizacao)` — `projetos.tenant_id` é `NOT NULL`
- `noPainelBootado('app')` nos cenários de tela — sem ele o macro `simpleLightbox()` não existe

### Fakes — e por que quase não há

**Nenhum `Storage::fake()` em CT-06 a CT-11.** A razão **não** é a que a primeira versão deu
(*"o fake substitui a rota"* — falso, ver F4). É pior: o fake **deixa a rota de pé** e faz o
`ServeFile` ler do root falso enquanto consulta a visibilidade do config de boot. Um cenário de
rede com fake parece válido e mede outra coisa.

Cenários de rede usam disco real em diretório temporário, limpo no `afterEach`.

`Storage::fake('local')` continua aceitável em CT-01 e CT-03, que afirmam **onde** a mídia foi
gravada e não fazem requisição.

### Estratégia de DB

`RefreshDatabase` global. Tenancy em `tests/Tenancy`.

---

## Regra R1 — mídia nova nasce em disco privado, e os dois caminhos concordam

> `RQ-02`, `RQ-04` · **completo** · tabela de decisão

| # | Caminho | Coleção declara? | Esperado | Cenário |
|---|---|---|---|---|
| 1 | `toMediaCollection('anexos')` | sim | privado | CT-01 |
| 2 | `toMediaCollection('nova')` — sem declaração | não | privado, pelo default | CT-02 |
| 3 | upload pela tela | sim | privado | CT-04 |
| 4 | 1 e 3 no mesmo model | — | **mesmo** disco | **CT-05** |
| 5 | disco explícito `'public'` | — | público, por escolha | CT-03 |

```gherkin
# language: pt

Funcionalidade: Anexos privados

  Regra: mídia nova nasce em disco privado, e os caminhos de escrita concordam

    Cenário: [CT-01] a chamada idiomática do spatie não grava em disco público
      Dado um projeto da organização Acme
      Quando o sistema anexa um arquivo com "addMedia(...)->toMediaCollection('anexos')"
      Então o disco gravado não declara visibilidade pública na configuração

    Cenário: [CT-02] coleção sem declaração herda o default privado
      Dado que nenhuma variável de ambiente de disco de mídia está definida
      E um model com uma coleção que não declara disco
      Quando o sistema anexa um arquivo a ela
      Então o disco gravado não declara visibilidade pública na configuração

    Cenário: [CT-03] coleção que declara o disco ignora o default
      Dado que o default da media library foi trocado para o disco público
      Quando o sistema anexa um arquivo à coleção de anexos
      Então o disco gravado não declara visibilidade pública na configuração

    Cenário: [CT-04] o upload pela tela continua gravando em disco privado
      Dado um coordenador autenticado na organização Acme
      Quando ele cria um projeto anexando um arquivo pelo formulário
      Então o disco gravado não declara visibilidade pública na configuração

    Cenário: [CT-05] os dois caminhos de escrita convergem para o mesmo disco
      Dado um projeto da organização Acme
      Quando ele recebe um arquivo pelo formulário e outro por "addMedia" na mesma coleção
      Então as duas mídias estão no mesmo disco
```

**O `Então` mudou de "não é o disco público" para "não declara visibilidade pública na
configuração"**, e a diferença é o mutante M7. Por F3, a rota consulta
`config('filesystems.disks.{disco}.visibility')` **capturado no boot** — inalcançável por
`config()->set()` dentro do teste. Afirmar sobre esse valor é a única forma de matar M7 numa
suíte só.

**CT-02 força a ausência da variável de ambiente.** O veredito não pode depender do `.env`, que
é gitignorado: quem corrigir apenas o `.env.example` teria CT-02 verde numa instalação nova e o
default do `config/` — o único artefato que o `kit:update` entrega — seguiria vazando.

**CT-04 é guarda de regressão, não falsificação de R1.** Por F2 e pela `## Errata` do `00`, o
caminho da tela **já estava correto**: ele não pode ficar vermelho antes da correção, logo não
prova a correção. Está aqui para que a correção não quebre o que funcionava.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | corrigir só o `useDisk()` e deixar o default público | **CT-02** |
| M2 | corrigir só o default e não declarar na coleção | **CT-03** |
| M3 | corrigir só o `.env.example` | **CT-02** |
| M4 | trocar o default e quebrar o upload da tela | CT-04 |
| M5 | apontar cada caminho para um disco privado **diferente** | **CT-05** |
| M6 | declarar `'visibility' => 'public'` no disco privado | **CT-01…CT-04** (pelo novo `Então`) |

---

## Regra R2 — o arquivo só sai com assinatura válida, e sai quando ela existe

> `RQ-02`, `RQ-03` · **completo** · **oráculo diferencial**

Por F1 e F2, "403 sem assinatura" não prova proteção: é o que a rota devolve para arquivo
inexistente, disco quebrado ou conversão nunca gerada. **O oráculo é o par**: a mesma URL, com e
sem assinatura, precisa dar respostas diferentes — e a versão assinada precisa entregar o
conteúdo.

```gherkin
  Regra: o arquivo só sai com assinatura válida, e sai quando ela existe

    Cenário: [CT-06] a mesma URL responde diferente com e sem assinatura
      Dado um projeto da organização Acme com um anexo
      Quando um visitante anônimo requisita a URL do anexo sem assinatura e depois com assinatura válida
      Então a primeira resposta é 403
      E a segunda é 200 e entrega os bytes do arquivo anexado

    Cenário: [CT-07] a miniatura obedece ao mesmo par
      Dado um projeto da organização Acme com uma imagem anexada
      Quando um visitante anônimo requisita a URL da miniatura sem assinatura e depois com assinatura válida
      Então a primeira resposta é 403
      E a segunda é 200 e entrega uma imagem menor que o original

    Cenário: [CT-08] a URL que o sistema publica não serve o arquivo
      Dado um projeto da organização Acme com um anexo
      Quando um visitante anônimo requisita a URL devolvida por "getUrl()"
      Então a resposta não é 200

    Cenário: [CT-09] assinatura expirada não serve
      Dado um projeto da organização Acme com um anexo
      E uma URL assinada com validade de cinco minutos
      Quando um visitante anônimo a requisita seis minutos depois
      Então a resposta não é 200

    Cenário: [CT-10] a rota de escrita não aceita PUT anônimo sem assinatura
      Dado um projeto da organização Acme com um anexo
      Quando um visitante anônimo faz PUT no caminho do anexo, sem assinatura
      Então a resposta não é 200
      E o conteúdo do anexo permanece o original

    Cenário: [CT-11] a assinatura válida é o que separa o 403 do 200
      Dado um projeto da organização Acme com um anexo
      Quando um visitante anônimo, sem sessão, requisita a URL assinada ainda válida
      Então a resposta é 200
```

**CT-07 é o par positivo que faltava.** A primeira versão só tinha o lado negativo da miniatura,
e por F1 ele fica verde com a conversão **nunca gerada** — o mutante "trocar o disco e quebrar a
conversão no mesmo commit" passava inteiro. O `Então` afirma **bytes de imagem menor**, não
apenas 200.

**CT-10 cobre a superfície descoberta em F5.** A rota `PUT storage/{path}` existe com middleware
vazio. Sem assinatura ela recusa — o cenário fixa isso, porque a rota nasce do mesmo
`serve => true` que a correção passa a depender, e ninguém a tinha olhado.

**CT-11 é deliberadamente um sucesso para anônimo.** Fixa o limite que o ADR-03 aceitou: **quem
tem o link entra, sem sessão**. Fica vermelho no dia em que alguém implementar autorização real,
sinalizando que a decisão mudou.

**O `Quando` de CT-06 e CT-07 tem duas requisições, e isso é intencional**: o oráculo é a
**diferença** entre elas. Separar em dois cenários devolveria dois oráculos fracos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | disco privado sem `serve => true` — sem rota, nada é entregável | **CT-06** (lado positivo) |
| M8 | trocar o disco e quebrar a geração da conversão | **CT-07** |
| M9 | mídia gravada em disco privado, mas o arquivo não chega lá | **CT-06** |
| M10 | rota aceitar assinatura expirada | **CT-09** |
| M11 | `getUrl()` passar a devolver URL assinada permanente | CT-08 |

---

## Regra R3 — a coleção declara o disco, e vence o default

> `RQ-04` (ADR-02) · coberta por **CT-03**.

| # | Mutante | Cenário |
|---|---|---|
| M12 | remover o `useDisk()` por ser "redundante" | **CT-03** |

---

## Regra R4 — a migração leva original e conversões, de qualquer model, sem perder arquivo

> `RQ-05` · **completo** · estado × operação

| Estado da mídia | migrar | migrar de novo | simular |
|---|---|---|---|
| pública, **só original** | move (CT-12) | não muda (CT-14) | não move (CT-17) |
| pública, **com conversão** | move **as duas** (CT-13) | não muda | não move |
| pública, **de outro `model_type`** | move (CT-15) | não muda | não move |
| já privada | ignora | ignora | não move |
| pública **por declaração** | **não toca** (CT-16) | não toca | não toca |

```gherkin
  Regra: a migração leva original e conversões, de qualquer model, e pode rodar de novo

    Cenário: [CT-12] anexo legado deixa de ser entregue sem assinatura
      Dado um anexo gravado no disco público
      Quando o operador executa a migração
      Então a URL do anexo sem assinatura responde 403
      E a URL assinada entrega os mesmos bytes de antes da migração

    Cenário: [CT-13] a conversão legada é migrada junto do original
      Dado um anexo legado no disco público, com miniatura já gerada
      Quando o operador executa a migração
      Então a URL da miniatura sem assinatura responde 403
      E não resta arquivo de miniatura no diretório público

    Cenário: [CT-14] a segunda execução não muda o estado deixado pela primeira
      Dado um anexo gravado no disco público
      E que o operador já executou a migração uma vez
      Quando ele executa a migração outra vez
      Então o inventário de arquivos é idêntico ao de antes desta execução
      E o comando informa que zero mídias foram migradas

    Cenário: [CT-15] mídia de outro model também é migrada
      Dado um anexo público pertencente a um model que não é Projeto
      Quando o operador executa a migração
      Então a URL desse anexo sem assinatura responde 403
```

**CT-13 é a lacuna mais cara que a revisão encontrou.** Uma migração que mova o original e ignore
`conversions_disk` deixa **exatamente a segunda linha da medição do requisito** — a miniatura em
`/storage/1/conversions/…` — servida em 200, com a suíte inteira verde.

**CT-14 corrigiu o `Quando` duplo.** A primeira versão dizia "executa duas vezes" num passo só, e
com isso uma primeira execução que já falha e uma segunda que corrige davam o mesmo verde. Agora
a primeira execução está no `Dado`, e o oráculo compara o inventário **antes e depois da
segunda** — ancorado no agregado, não em contador.

**CT-15 fecha o escopo.** Um comando filtrado por `model_type = Projeto` passaria todos os outros
cenários, porque `Projeto` é a única superfície de mídia do kit — e contrariaria a premissa
registrada no `00`, de que a correção é do kit, não da demo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | copiar em vez de mover | **CT-13** |
| M14 | migrar o original e ignorar `conversions_disk` | **CT-13** |
| M15 | escopar o comando a `Projeto` | **CT-15** |
| M16 | atualizar a linha do banco antes de o arquivo chegar | ⚠️ **sem matador** — lacuna L2 |
| M17 | reexecutar e mover de novo, criando órfão | **CT-14** |
| M18 | contador que sempre informa zero | **CT-17** (que fixa o número) |

---

## Regra R5 — a migração não desfaz escolha explícita

> `RQ-05` (ADR-04) · EP

```gherkin
  Regra: escolha explícita de disco público é preservada

    Cenário: [CT-16] coleção que escolheu o disco público não é migrada
      Dado um model com uma coleção que declara o disco público
      E um arquivo anexado a essa coleção
      Quando o operador executa a migração
      Então esse arquivo continua sendo entregue sem assinatura

    Cenário: [CT-17] a simulação não move nada e informa o número exato
      Dado exatamente dois anexos gravados no disco público
      Quando o operador executa a migração em modo de simulação
      Então os dois continuam sendo entregues sem assinatura
      E o comando informa que duas mídias seriam migradas
```

**CT-17 fixa o número.** A primeira versão dizia "informa quantos seriam migrados", que um
comando imprimindo sempre `0` satisfaz literalmente — o mutante M18 sobrevivia a um cenário que
o declarava morto.

**O `Então` de CT-16 é comportamental**, não sobre o nome do disco: "continua sendo entregue sem
assinatura" é o que caracteriza público.

| # | Mutante | Cenário |
|---|---|---|
| M19 | migrar tudo que estiver em disco público, sem olhar a declaração | **CT-16** |
| M20 | `--dry-run` que move mesmo assim | **CT-17** |

---

## Regra R6 — a documentação descreve o comportamento real

> `RQ-06` · **mínimo** · **sem poder de falsificação sobre R1–R5**

```gherkin
  Regra: a documentação não promete o que o código não faz

    Cenário: [CT-20] nenhum documento afirma que anexo vive em disco público
      Dado os READMEs, as wikis e as rules do projeto
      Quando o texto é lido ignorando comentário
      Então nenhum deles descreve a coleção de anexos como pública

    Cenário: [CT-21] a documentação declara o limite aceito do link assinado
      Dado a documentação de mídia do kit
      Quando ela é lida
      Então afirma que compartilhar o link concede acesso durante a validade
```

**CT-21 é o teste do ADR-03.** A decisão de aceitar o limite só é honesta se estiver escrita.

| # | Mutante | Cenário |
|---|---|---|
| M21 | corrigir o código e deixar a documentação velha | CT-20 |
| M22 | apagar a frase do limite por parecer alarmista | **CT-21** |

---

## Lacunas declaradas

| # | O quê | O que foi tentado | Por que fica |
|---|---|---|---|
| **L1** | colisão do symlink (CT-09 da 1ª versão) | indexar como cenário HTTP | **Inobservável nessa camada** (F7): `server.php:12-14` devolve o arquivo físico **antes** do `index.php`, e `$this->get()` instancia o kernel direto, sem passar por `public/`. Exigiria servidor real. Substituído por **CT-18** |
| **L2** | falha no meio da migração (M16) | mockar o `Storage` para falhar no `put` depois do `get` | Viável e mata M16. Fora por teto do perfil — R4 já tem 6 cenários. É o primeiro a escrever se a migração falhar em campo |
| **L3** | autorização por organização | — | Decisão do ADR-03: a rota assinada não conhece usuário nem tenant. CT-11 **fixa o limite** em vez de fechá-lo |

### CT-18 e CT-19 — o que substitui a lacuna L1

```gherkin
  Regra: nada no diretório servido pelo symlink sombreia a rota assinada

    Cenário: [CT-18] após a migração não resta mídia no diretório público
      Dado anexos legados no disco público, com conversões
      Quando o operador executa a migração
      Então não resta nenhum arquivo de mídia sob o diretório servido pelo symlink

    Cenário: [CT-19] o disco privado não compartilha prefixo de URI com o symlink
      Dado a configuração de discos da aplicação
      Então o prefixo de URI do disco de mídia não é servido também por arquivo estático
```

**CT-18 é estrutural e observável**, ao contrário do que ele substitui: afirma sobre o sistema de
arquivos, não sobre resposta HTTP. É ele que garante que **não há o que sombrear**, que é a
propriedade de que o requisito precisa.

**CT-19 fica vermelho hoje** — a rota `storage.local` e o symlink dividem `/storage`. Isso é
correto e deliberado: é a lacuna sendo declarada como teste, não como parágrafo. Se o time
decidir que a convivência é aceitável enquanto CT-18 valer, o cenário vira `@premissa` com a
decisão escrita. Está aqui como **pergunta ao usuário**, não como veredito.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **lacuna L3** — ADR-03; CT-11 fixa o limite |
| Autorização exercida na ação | não se aplica: sem ação nova |
| Idempotência (ancorada no agregado) | **CT-14** |
| Concorrência | não se aplica: comando de operador |
| Fronteira no ponto de entrada | não se aplica: sem faixa ordenável |
| Domínio condicionado | **CT-03** (default × declaração) |
| Estado × operação | tabela de R4 |
| Ausente ≠ null ≠ vazio | **CT-02** (variável de ambiente ausente) |
| Paginação / ordenação | não se aplica |
| Timezone / expiração | **CT-09** |
| Unicode / varchar | não se aplica |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | **CT-14** |
| Mass assignment | não se aplica |
| **Upload** | CT-04 (tela), CT-01 (programático), **CT-10 (PUT anônimo)** |
| Precisão monetária | não se aplica |

## Índice de Cenários

| ID | Cenário | Regra | Camada | Arquivo |
|----|---------|-------|--------|---------|
| CT-01…CT-05 | disco de destino e convergência | R1, R3 | Tenancy | `tests/Tenancy/AnexosPrivadosTest.php` |
| CT-06…CT-11 | rede: par assinado × não assinado | R2 | Tenancy (HTTP) | idem |
| CT-12…CT-15 | migração | R4 | Kit | `tests/Kit/MigracaoDeMidiaTest.php` |
| CT-16, CT-17 | escolha explícita e simulação | R5 | Kit | idem |
| CT-18, CT-19 | symlink e prefixo de URI | R2 | Kit | idem |
| CT-20, CT-21 | documentação | R6 | Kit | `tests/Kit/AnexosPrivadosTest.php` |

## Sem CT-B

A superfície de UI não muda. O `<img>` que responde 403 **não gera erro de JavaScript**, então
`assertNoJavaScriptErrors()` ficaria verde com a imagem quebrada.

O CT-B existente (`tests/BrowserTenancy/AnexosDoProjetoTest.php:57-73`) precisa de asserção sobre
a `src` da miniatura, pelo mesmo motivo.

---

## Revisão Adversarial

Executada por sub-agente que recebeu **apenas** o `00-requisito.md` e a primeira versão deste
arquivo — sem o plano, sem as ADRs, sem o raciocínio da derivação.

**12 lacunas.** As de maior consequência:

| Achado | O que era | O que virou |
|---|---|---|
| Migração ignorando `conversions_disk` passava tudo | R4 sem eixo de conversão | **CT-13** |
| Comando escopado a `Projeto` passava tudo | sem partição de `model_type` | **CT-15** |
| CT-09 (colisão) **inobservável** na camada indexada | erro de alocação de camada | **L1** + CT-18, CT-19 |
| Oráculos negativos não distinguiam protegido de quebrado | F1 ignorado | **oráculo diferencial** em toda R2 |
| Nenhum `Então` positivo sobre a miniatura | par da EP faltando | **CT-07** |
| `--dry-run` sem número fixado | asserção sem valor | **CT-17** |
| Rota `PUT` anônima fora da varredura | dimensão I incompleta | **CT-10** |
| CT-12 com `Quando` duplo | passo composto | **CT-14** |
| Nada garantia que os dois caminhos convergem | tabela sem linha de coerência | **CT-05** |

**Duas afirmações minhas eram factualmente falsas**, e sustentavam decisões de desenho:

1. *"O fake substitui o driver e com ele a rota `storage.{disk}`"* — **falso** (F4). A rota
   sobrevive, e o `ServeFile` passa a ler do root falso consultando o config de boot. A conclusão
   (não usar fake nos cenários de rede) estava certa; o motivo, errado — e o motivo errado teria
   levado alguém a "consertar" o cenário usando fake com opções.
2. *"O `phpunit.xml` não define `MEDIA_DISK`, então o teste lê o `config/`"* — o veredito depende
   do `.env`, que é gitignorado. **CT-02** passou a forçar a ausência da variável.

É o mesmo padrão da `## Errata` do `00`: **a conclusão sobreviveu, o mecanismo não.** Três vezes
nesta feature. Vale como sinal, não como coincidência — a checagem que faltou nas três foi ler o
vendor antes de escrever a justificativa, e não depois.

**Rodadas**: 1. A segunda não foi disparada porque o fechamento criou cenários novos e o teto da
skill é 2 — a segunda rodada fica para depois da implementação, junto do `pest --mutate`.
