# Casos de Teste — Laravel Data como padrão de DTO

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando implementação — ela não existe.
>
> **Versão 3.** A v1 caiu na rodada 1 da revisão adversarial (5 implementações erradas passando em
> 21 cenários); a v2 caiu na rodada 2 (mais 5, com conflito bloqueante entre dois cenários). O teto
> de 2 rodadas foi atingido e o caso foi **escalado ao usuário**, que decidiu duas coisas — cortar o
> escopo para **3 Data** e trocar a estratégia do guarda por **raízes como dado**. Esta versão é o
> resultado. O histórico está em [Revisão Adversarial](#revisão-adversarial--duas-rodadas-e-a-escalação).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| **A** — enforço da regra (guarda) | 2 | 2 | 4 | padrão |
| **B** — Data na fronteira com API externa (A1, A2) | 3 | 3 | 9 | **completo** |
| **C** — Data em fronteira interna (A4) | 2 | 2 | 4 | padrão |
| **D** — documentação e rule | 1 | 1 | 1 | mínimo |

> A área A subiu de `mínimo` para `padrão`: as duas rodadas adversariais mostraram que o guarda é o
> ponto mais frágil do conjunto — quatro das dez implementações erradas viviam nele.
> A área C caiu de Impacto 3 para 2 com o corte de escopo: o instalador e o contrato público de
> `widgets()` saíram desta entrega.

- Técnicas: EP, ausente ≠ nulo ≠ vazio, **partição válida obrigatória no guarda**, oráculo
  **discriminante de mensagem**, rastreio de efeito **no ponto de uso com valor que só o Data
  produz**, tabela de decisão com coluna positiva e invariante em todas as colunas
- Cenários: 31 · Regras: 10 · Mutantes previstos: 41 · Sem matador: 0

## Escopo desta entrega (decisão do usuário, 2026-09-15)

| Data | Entra? | Motivo |
|---|---|---|
| `VeredictoDoGuardrailData` (A1) | **sim** | resposta de API externa no caminho de segurança |
| `PerfilSocialData` (A2) | **sim** | resposta de API externa no caminho de conta |
| `ResultadoDoConviteEmMassaData` + `FalhaDoConviteData` (A4) | **sim** | mesmo shape duplicado em duas classes |
| `UsoDeTokensData` (A3) | não | ganho baixo; o array é montado e consumido num método só |
| `MarcaDaInstalacaoData` (A5) | não | dois campos; o instalador roda no `create-project` e o risco não paga |
| `WidgetDoDashboardData` (A6) | não | `widgets()` é **contrato público publicado na v0.33.0**; mexer nele agora é risco sem ganho |

Os três que ficam de fora seguem mapeados no `01` com `arquivo:símbolo:linha` — são entrega
seguinte, não esquecimento.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | 3 Data em `app/Data/**`, 1 guarda **com raízes expostas como dado**, 1 rule, `composer.json`/`lock`, paths do `kit:update`, 2 páginas de doc | CT-04…CT-15 |
| **F** | traduzir payload externo; **consumir o Data no ponto de produção**; preservar fail-open e o texto da notificação; enforçar o padrão | CT-01…CT-03, CT-16…CT-31 |
| **D** | veredito `{seguro, categoria, motivo}`; perfil `{id, email, nome, avatar, emailVerificado, bruto}`; `{enviados, falhas[]}`. **Ausência é o eixo**, inclusive no campo de decisão | CT-16…CT-19 |
| **I** | middleware de IA, callback do Socialite, ação de convite em massa — e o guarda | CT-22…CT-26, CT-28…CT-31 |
| **P** | PHP 8.3+, Laravel 13, pacote 4.23, PHPStan 7, Livewire (o synth do pacote reconstrói Data do payload do cliente) | CT-02, CT-08 |
| **O** | o kit e quem atualiza por `kit:update`; agentes de IA lendo a rule | CT-13, CT-14 |
| **T** | **não se aplica**: nenhum campo temporal, nenhuma regra de ordem, expiração ou fuso | — |

## Mapa de Regras

| Regra | Área | Origem | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O Data nasce **pelo pipeline do pacote**, com os valores convertidos | B | RQ-01, ADR-03 | EP + polimorfismo de `from()` | CT-01, CT-02, CT-03 |
| R2a — O guarda **julga certo e diz o motivo discriminante** | A | RQ-02, ADR-02 | matriz de varredura com partição válida | CT-04…CT-12 |
| R2b — O guarda **enxerga os caminhos de produção** e fica verde no kit publicado | A | RQ-02, ADR-07 | oráculo sobre o escopo | CT-13, CT-14, CT-15 |
| R3 — Payload externo vira Data com os defaults, inclusive no campo de decisão | B | RQ-03 | ausente≠nulo≠vazio | CT-16…CT-19 |
| R4 — As três superfícies de API sem Data reprovam | A | RQ-04, ADR-07 | EP | CT-20, CT-21 |
| R5a — **Produção consome o Data**, provado por valor que só ele produz | B/C | RQ-03, RQ-06 | rastreio de efeito no ponto de uso | CT-22, CT-23, CT-24 |
| R5b — O observável de hoje não muda | C | RQ-06 | regressão + idempotência | CT-25, CT-26 |
| R6 — Credencial não viaja no Data | B | ADR-04, P6 | EP | CT-27 |
| R8 — Os três desfechos do classificador se distinguem, e nenhum vaza o prompt | B | ADR-06 | decisão com coluna positiva + invariante | CT-28…CT-31 |

> **R2 virou R2a + R2b, e R5 virou R5a + R5b.** Foi o diagnóstico estrutural da rodada 2: uma regra
> misturava "o guarda julga certo" com "o guarda olha o lugar certo", e a outra misturava "o Data é
> usado" com "nada mudou para o usuário". Regra que precisa de dois eixos de oráculo é duas regras.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nomes de classe e de fábrica | escolha de implementação | detalhe do cenário |
| Caminhos `app/Data/**` etc. | escolha de implementação — **exceto** em R2a/R2b/R4, onde a localização é o que o guarda verifica | oráculo só nessas |
| Texto da notificação do convite | comportamento **já existente** | oráculo de regressão em CT-25 |

**Perguntas em aberto**: P6 (chaves do `bruto`) — premissa por falha fechado, invariante em CT-27.

## Setup Global

### Personas

`usuarioDoKit('master_global')`, `usuarioDoKit('panel_user')`, `usuarioComPapel('admin_app', …)`

### Fixtures do guarda — uma modalidade só

Toda fixture de artefato (bom ou ruim) mora em **raiz temporária** criada pelo teste sob
`storage/framework/testing/dto/{caso}/`, imitando a árvore de produção (`Data/`, `Models/`,
`Http/Resources/`, `routes/api.php`). O guarda recebe essa raiz como argumento.

**Nada é escrito em `app/` nem em `routes/`.** A revisão adversarial mostrou quatro riscos
independentes na escrita em caminho de produção: visibilidade entre workers em `--parallel`,
interrupção deixando classe defeituosa autocarregada, dependência de cache de rota, e `git status`
sujo no meio da execução.

O que amarra a raiz temporária ao mundo real são **CT-13 e CT-14**: as raízes padrão do guarda são
dado, e o kit publicado passa por ele sem reprovação.

### Fakes

`Http::preventStrayRequests()` em todos. `Notification::fake()` **não** é usado em CT-25 — o resumo
é do `Filament\Notifications\Notification`, e o fake da facade do Laravel não o alcança; o cenário
roda pelo componente Livewire, que já isola o envio.

### Estratégia de DB

`RefreshDatabase` global (`tests/Pest.php`).

---

## Regra R1 — O Data nasce pelo pipeline do pacote, com os valores convertidos

> `RQ-01`, ADR-03 · perfil **completo** · técnica: **EP + polimorfismo de `from()`**

```gherkin
# language: pt

Funcionalidade: Laravel Data como padrão de DTO

  Regra: A fábrica usa o pipeline do pacote, e não montagem à mão

    Cenário: [CT-01] o Data expõe os valores, e só eles
      Dado o payload de um classificador com seguro=false, categoria="injecao" e motivo="tentativa"
      Quando o Data do veredito é criado pela fábrica dele
      Então a representação em array do Data é exatamente
        | seguro    | false     |
        | categoria | injecao   |
        | motivo    | tentativa |

    Esquema do Cenário: [CT-02] a mesma fonte em três formatos produz o mesmo Data
      Dado o mesmo veredito expresso como <formato>
      Quando o Data do veredito é criado pela fábrica dele
      Então o Data resultante é igual ao criado a partir do array

      Exemplos:
        | formato                          | # partição |
        | array associativo                | array      |
        | objeto com as três propriedades  | objeto     |
        | texto JSON                       | json       |

    Cenário: [CT-03] o cast do pacote converte o texto em booleano
      Dado um payload em que seguro chega como o texto "false"
      Quando o Data do veredito é criado pela fábrica
      Então seguro é o booleano false
```

> CT-02 é o que separa "fábrica que usa o pacote" de "fábrica artesanal com `new self()` e cast à
> mão" (implementação errada **I9** da rodada 2): só o pipeline do pacote aceita as três fontes.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | classe não estende o `Data` do pacote | CT-01 |
| M2 | `toArray()` com valores zerados ou chave extra | CT-01 ("é exatamente") |
| M3 | fábrica artesanal com `new self()` — aceita só array | CT-02 (linhas "objeto" e "json") |
| M4 | cast ausente: `"false"` vira `true` | CT-03 |

---

## Regra R2a — O guarda julga certo e diz o motivo discriminante

> `RQ-02`, ADR-02 · perfil **padrão** · técnica: **matriz de varredura com partição válida**

```gherkin
  Regra: O guarda distingue o artefato correto do defeituoso, e nomeia o defeito certo

    Esquema do Cenário: [CT-10] o Data correto é aprovado, nas duas formas válidas
      Dado uma raiz temporária com um Data <forma>
      Quando o guarda roda sobre essa raiz
      Então ele não reprova nada

      Exemplos:
        | forma                                                              | # partição |
        | final, readonly na classe, estendendo o Data pelo nome curto        | curto      |
        | final, readonly em cada propriedade, estendendo pelo nome completo  | completo   |

    Cenário: [CT-11] propriedade com nome parecido, mas legítima, é aprovada
      Dado uma raiz temporária com um Data que tem a propriedade tokenDoConvite
      Quando o guarda roda sobre essa raiz
      Então ele não reprova nada

    Cenário: [CT-04] Data sem a herança do pacote reprova, e o motivo é a herança
      Dado uma raiz temporária com uma classe no diretório de DTO que não estende o Data do pacote
      Quando o guarda roda sobre essa raiz
      Então ele reprova exatamente essa classe
      E a mensagem cita a herança ausente e não cita final, readonly nem abstract

    Esquema do Cenário: [CT-05] cada defeito de imutabilidade é nomeado pelo seu motivo
      Dado uma raiz temporária com uma classe no diretório de DTO <defeito>
      Quando o guarda roda sobre essa raiz
      Então ele reprova exatamente essa classe
      E a mensagem cita "<motivo>" e não cita "<motivo_alheio>"

      Exemplos:
        | defeito                         | motivo   | motivo_alheio | # partição       |
        | sem o modificador final         | final    | abstract      | herança aberta   |
        | com propriedade pública mutável | readonly | final         | estado mutável   |
        | declarada abstrata              | abstract | readonly      | não instanciável |

    Cenário: [CT-06] classe fora do diretório de DTO terminada em Data reprova
      Dado uma raiz temporária com uma classe RelatorioData em Models
      Quando o guarda roda sobre essa raiz
      Então ele reprova exatamente essa classe
      E a mensagem diz que o sufixo Data é reservado

    Esquema do Cenário: [CT-07] credencial em Data reprova, nomeando a propriedade
      Dado uma raiz temporária com um Data que tem a propriedade <nome>
      Quando o guarda roda sobre essa raiz
      Então ele reprova essa classe
      E a mensagem cita "<nome>" e não cita nenhum dos outros nomes de credencial

      Exemplos:
        | nome     |
        | senha    |
        | password |
        | token    |
        | secret   |
        | api_key  |

    Cenário: [CT-08] Data como propriedade pública de componente reprova
      Dado uma raiz temporária com um componente que tem propriedade pública tipada como Data
      Quando o guarda roda sobre essa raiz
      Então ele reprova esse componente
      E a mensagem cita que o pacote reconstrói a propriedade a partir do payload do navegador

    Cenário: [CT-09] instanciar Data com new fora da fábrica reprova
      Dado uma raiz temporária com um arquivo que instancia um Data com new fora de uma fábrica
      Quando o guarda roda sobre essa raiz
      Então ele reprova nomeando o arquivo

    Cenário: [CT-12] o guarda percorre o diretório inteiro, não o primeiro arquivo
      Dado uma raiz temporária com duas classes defeituosas em subpastas diferentes
      Quando o guarda roda sobre essa raiz
      Então ele reprova as duas, nomeando cada uma
```

> O `e não cita` de CT-04, CT-05 e CT-07 é o que mata a **"mensagem catch-all"** (implementação
> errada **I8**): um guarda com uma mensagem única listando todos os motivos satisfaz "a mensagem
> cita X" em todos os cenários e não distingue nada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | guarda por `str_contains` no fonte — reprova nome completo e `readonly` por propriedade | CT-10 |
| M6 | guarda reprova tudo indiscriminadamente | CT-10, CT-11 |
| M7 | guarda reprova qualquer nome que contenha "token" | CT-11 |
| M8 | mensagem única citando todos os motivos | CT-04, CT-05, CT-07 (o "não cita") |
| M9 | confere o diretório, não a herança | CT-04 |
| M10 | aceita classe `abstract` | CT-05 (linha "abstrata") |
| M11 | sufixo reservado não verificado | CT-06 |
| M12 | credencial procurada só como `password` | CT-07 (demais linhas) |
| M13 | guarda de componente ausente | CT-08 |
| M14 | `new` fora da fábrica não é visto | CT-09 |
| M15 | lê só o primeiro arquivo de cada subpasta | CT-12 |

---

## Regra R2b — O guarda enxerga os caminhos de produção e fica verde no kit publicado

> `RQ-02`, ADR-07 · perfil **padrão** · técnica: **oráculo sobre o escopo**

```gherkin
  Regra: As raízes do guarda são dado verificável, e o kit passa por ele

    Cenário: [CT-13] as raízes padrão são exatamente os caminhos de produção
      Dado o guarda do padrão de DTO
      Quando as raízes padrão dele são lidas
      Então elas são exatamente app, routes/api.php e app/Http/Resources

    Cenário: [CT-14] o kit publicado passa pelo guarda sem reprovação
      Dado o kit como está publicado, sem fixture nenhuma
      Quando o guarda roda sem receber raiz
      Então ele não reprova nada

    Cenário: [CT-15] a árvore de produção imitada é percorrida inteira
      Dado uma raiz temporária que imita a árvore de produção, com um Data defeituoso em Data,
        uma classe RelatorioData em Models e um componente com propriedade Data em Filament
      Quando o guarda roda sobre essa raiz
      Então ele reprova os três, nomeando cada um
```

> CT-14 é o cenário que faltava nas duas rodadas: sem ele, um guarda que **reprova tudo quando
> chamado sem raiz** passa no conjunto inteiro (implementação errada **I7**) e o kit real nunca é
> exercitado contra o próprio guarda. CT-13 impede que as raízes encolham em silêncio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | guarda só enxerga a raiz que o teste entrega | CT-13, CT-14 |
| M17 | raízes padrão encolhidas para `app/Data` | CT-13 |
| M18 | guarda reprova tudo quando chamado sem raiz | CT-14 |
| M19 | percorre `Data/` e ignora `Models/` e componentes | CT-15 |

---

## Regra R3 — Payload externo vira Data com os defaults, inclusive no campo de decisão

> `RQ-03` · perfil **completo** · técnica: **ausente ≠ nulo ≠ vazio**

```gherkin
  Regra: Campo ausente cai no default declarado — e o default do campo de decisão é o seguro

    Esquema do Cenário: [CT-16] o veredito preenche os defaults do campo ausente
      Dado uma resposta do classificador <payload>
      Quando o Data do veredito é criado pela fábrica
      Então seguro vale <seguro>, categoria vale "<categoria>" e motivo vale "<motivo>"

      Exemplos:
        | payload                     | seguro | categoria      | motivo    | # partição            |
        | os três campos preenchidos  | false  | injecao        | tentativa | completo              |
        | sem a chave categoria       | false  | fora_de_escopo |           | ausente (descritivo)  |
        | com categoria nula          | false  | fora_de_escopo |           | nulo                  |
        | com categoria vazia         | false  |                |           | vazio                 |
        | **sem a chave seguro**      | false  | fora_de_escopo |           | **ausente (decisão)** |
        | com seguro=true e nada mais | true   | fora_de_escopo |           | mínimo aceitável      |

    Cenário: [CT-17] provedor social sem e-mail não quebra a fronteira
      Dado um usuário do provedor com identificador "1234", nome "Ana" e sem e-mail
      Quando o Data do perfil é criado a partir dele
      Então o e-mail do Data é nulo
      E o identificador vale "1234" e o nome vale "Ana"

    Cenário: [CT-18] e-mail vazio é tratado como ausente
      Dado um usuário do provedor cujo e-mail vem como texto vazio
      Quando o Data do perfil é criado a partir dele
      Então o e-mail do Data é nulo

    Esquema do Cenário: [CT-19] "email verificado" é lido dos três nomes possíveis
      Dado um usuário do provedor cujo dado bruto é <bruto>
      Quando o Data do perfil é criado a partir dele
      Então a marca de e-mail verificado vale <resultado>

      Exemplos:
        | bruto                                 | resultado | # partição           |
        | { "email_verified": true }            | true      | nome 1               |
        | { "verified_email": true }            | true      | nome 2               |
        | { "confirmed_email": true }           | true      | nome 3               |
        | { "email_verified": false }           | false     | desmentido explícito |
        | { "locale": "pt_BR" } (nenhuma das 3) | nulo      | o provedor não disse |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | default de `categoria` trocado | CT-16 (linha "ausente (descritivo)") |
| M21 | `seguro` ausente vira `true` | CT-16 (linha "ausente (decisão)") |
| M22 | lê só `email_verified` | CT-19 (linhas "nome 2" e "nome 3") |
| M23 | ausência vira `false` em vez de `null` | CT-19 (linha "o provedor não disse") |
| M24 | e-mail ausente estoura | CT-17 |
| M25 | e-mail vazio passa por endereço válido | CT-18 |

---

## Regra R4 — As três superfícies de API sem Data reprovam

> `RQ-04`, ADR-07 · perfil **padrão** · técnica: **EP**

```gherkin
  Regra: Rota de API, Resource e controller JSON exigem Data

    Cenário: [CT-20] hoje o kit não tem superfície de API — e o caso afirma isso
      Dado o kit como está publicado
      Quando se procura routes/api.php, app/Http/Resources e retorno JSON em controller
      Então nenhuma das três existe

    Esquema do Cenário: [CT-21] cada superfície de API sem Data reprova
      Dado uma raiz temporária com <artefato>
      Quando o guarda roda sobre essa raiz
      Então ele reprova nomeando o arquivo

      Exemplos:
        | artefato                                          | # superfície |
        | um controller que devolve JSON a partir de array  | controller   |
        | um Resource em Http/Resources                     | resource     |
        | um routes/api.php com rota devolvendo array       | rota         |
```

> CT-20 e CT-21 deixaram de se contradizer: CT-20 afirma sobre o **kit publicado**, CT-21 sobre uma
> **raiz temporária**. Na v2 os dois escreviam no mesmo caminho de produção e o resultado dependia
> do escalonamento dos workers.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | guarda olha só `routes/api.php` | CT-21 (linhas "controller" e "resource") |
| M27 | guarda olha só controller | CT-21 (linhas "resource" e "rota") |
| M28 | guarda de API sempre passa | CT-21 (qualquer linha) |

---

## Regra R5a — Produção consome o Data, provado por valor que só ele produz

> `RQ-03`, `RQ-06` · perfil **completo** · técnica: **rastreio de efeito no ponto de uso**

```gherkin
  Regra: Cada Data tem um ponto de uso em produção, e o valor prova que o caminho passou por ele

    Cenário: [CT-22] o middleware decide com o veredito convertido pelo Data
      Dado um classificador que devolve seguro como o texto "true"
      Quando o prompt do usuário passa pelo middleware de segurança
      Então o prompt segue para o agente

    Cenário: [CT-23] o callback social grava o vínculo a partir do Data do perfil
      Dado um provedor que devolve identificador "1234", e-mail "ana@example.com" e a chave extra is_admin
      Quando a pessoa volta do provedor pelo callback de login social
      Então o vínculo gravado tem identificador "1234" e e-mail "ana@example.com"
      E nenhum campo do vínculo corresponde à chave extra do provedor

    Cenário: [CT-24] a tela do convite em massa recebe o resultado tipado
      Dado uma organização com um papel de painel e dois endereços válidos
      Quando o administrador envia o lote pela tela de convites
      Então o resultado devolvido pelo domínio é o Data do resultado do lote
      E o resumo exibido foi montado a partir dele
```

> **CT-22 é discriminante — e a direção foi corrigida na implementação** *(alterado em 2026-09-15:
> a versão anterior deste cenário afirmava que o texto `"false"` passaria pelo código antigo. É
> falso: a comparação era `($veredito['seguro'] ?? false) === true`, estrita contra o booleano, e
> qualquer texto já era tratado como inseguro — fail-closed.)* A diferença real é o oposto: com
> `seguro` chegando como o texto `"true"`, o código antigo **bloqueava um prompt legítimo**, e o
> cast do Data o libera. Medido com `git stash` do middleware: sem o Data, o cenário fica vermelho.
> CT-23 usa a chave extra pelo mesmo princípio: pelo array cru ela chegaria ao insert.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M29 | o Data existe, mas o middleware continua lendo o array cru — e bloqueia prompt legítimo cujo `seguro` veio como texto | CT-22 |
| M30 | o callback continua lendo o objeto bruto do provedor | CT-23 |
| M31 | campo extra do provedor chega ao vínculo gravado | CT-23 |
| M32 | o domínio volta a devolver array e a tela lê por chave | CT-24 |

---

## Regra R5b — O observável de hoje não muda

> `RQ-06` · perfil **padrão** · técnica: **regressão + idempotência**

```gherkin
  Regra: Trocar array por Data não muda o que o usuário vê nem o que o banco guarda

    Cenário: [CT-25] o resumo do convite em massa sai com o mesmo texto de hoje
      Dado uma organização com um papel de painel e três endereços, um deles inválido
      Quando o administrador envia o lote pela tela de convites
      Então a notificação diz que dois convites foram enviados e um não
      E o corpo lista o endereço inválido com o motivo legível dele

    Cenário: [CT-26] reenviar o mesmo lote não duplica convite
      Dado uma organização com um convite pendente para contato@exemplo.com
      Quando o mesmo endereço é enviado novamente no lote
      Então continua existindo exatamente um convite pendente para aquele endereço
      E o resultado traz esse endereço entre as falhas, com o motivo de já existir
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M33 | a contagem passa a somar falha como enviado | CT-25 |
| M34 | o motivo da falha some | CT-25 |
| M35 | o lote duplica convite pendente | CT-26 |

---

## Regra R6 — Credencial não viaja no Data

> ADR-04, P6 · perfil **completo** · técnica: **EP**

```gherkin
  Regra: Nenhuma credencial do provedor entra na representação do Data

    Cenário: [CT-27] o perfil social não leva nenhuma das quatro credenciais
      Dado um usuário do provedor cujo bruto contém token, refresh_token, access_token e secret
      E cujo bruto também contém o identificador "1234"
      Quando o Data do perfil é criado a partir dele
      Então a representação em array do Data não contém nenhuma das quatro chaves
      E contém o identificador "1234"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | `bruto` copiado inteiro, com credencial | CT-27 |
| M37 | `toArray()` vazio e a ausência passa por vácuo | CT-27 (segunda asserção) |

---

## Regra R8 — Os três desfechos se distinguem, e nenhum vaza o prompt

> ADR-06 · perfil **completo** · técnica: **decisão com coluna positiva + invariante em todas as colunas**

| Condição | Veredito inseguro | Resposta fora do schema | Classificador indisponível |
|---|---|---|---|
| Prompt | **bloqueado** | segue | segue |
| Trilha | `warning` com a categoria | `warning`, `acao: schema` | `warning`, `acao: fail_open` + exceção |
| Prompt no log | **nunca** | **nunca** | **nunca** |

```gherkin
  Regra: Bloqueio, schema e indisponibilidade são três desfechos distinguíveis

    Cenário: [CT-28] veredito inseguro bloqueia o prompt
      Dado um classificador que devolve seguro=false com categoria "injecao"
      Quando o prompt do usuário passa pelo middleware de segurança
      Então o prompt é bloqueado
      E a trilha registra a categoria "injecao" em nível warning

    Cenário: [CT-29] resposta fora do schema não vira veredito
      Dado um classificador que devolve texto solto em vez de resposta estruturada
      Quando o prompt do usuário passa pelo middleware de segurança
      Então o prompt segue para o agente
      E a trilha registra, em nível warning, a ação "schema"

    Cenário: [CT-30] classificador fora do ar não derruba o chat
      Dado um classificador que lança exceção de conexão
      Quando o prompt do usuário passa pelo middleware de segurança
      Então o prompt segue para o agente
      E a trilha registra, em nível warning, a ação "fail_open" com a exceção

    Esquema do Cenário: [CT-31] em nenhum desfecho o prompt do usuário entra na trilha
      Dado um prompt contendo o texto "ELEFANTE-ROXO" e um classificador <desfecho>
      Quando o prompt passa pelo middleware de segurança
      Então nenhum registro da trilha contém "ELEFANTE-ROXO"

      Exemplos:
        | desfecho                        | # coluna    |
        | que devolve veredito inseguro   | bloqueio    |
        | que devolve texto solto         | schema      |
        | que lança exceção de conexão    | fail_open   |
```

> CT-31 é o invariante nas **três** colunas: na v2 ele existia só na coluna `fail_open`, e um ramo
> que logasse a resposta bruta do classificador — que contém o prompt — passava (**I10**).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | `catch` único engolindo os dois modos com log igual | CT-29 × CT-30 |
| M39 | o middleware nunca bloqueia | CT-28 |
| M40 | exceção de criação do Data derruba o chat | CT-30 |
| M41 | algum ramo loga a resposta bruta do classificador | CT-31 (as três linhas) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: sem rota, id de recurso ou entidade nova |
| Autorização exercida na ação | não se aplica: nenhuma regra de autorização criada ou alterada — a regressão obrigatória do `01` é o oráculo |
| Idempotência (ancorada no agregado) | CT-26 |
| Concorrência | não se aplica |
| **Fronteira no ponto de entrada** | CT-16 (inclui o campo de decisão), CT-18, CT-19 |
| Domínio condicionado | não se aplica |
| Estado × operação de escrita | não se aplica: nenhum Data tem ciclo de vida |
| **Ausente ≠ null ≠ vazio** | CT-16, CT-17, CT-18, CT-19 |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | não se aplica: nada novo é persistido |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| **Mass assignment** | CT-23 (a chave extra do provedor não chega ao vínculo gravado) |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Superfície do pacote de terceiro** | CT-08 (propriedade reconstruída pelo navegador) + CT-23 |
| IDOR por entidade persistida | não se aplica: nenhuma entidade nova |
| Escopo com discriminante nulo | não se aplica: nenhum Data participa de query |
| Saída do estado de erro | não se aplica: nenhum 4xx novo; CT-29 e CT-30 seguem para o agente e CT-28 é o bloqueio previsto |

> **Mass assignment mudou de lugar de propósito**: na v2 o cenário afirmava que `from()` ignora
> chave desconhecida — comportamento **default do pacote**, que nenhuma implementação do kit
> conseguiria falhar. Agora a asserção está no **ponto de uso** (o vínculo gravado), onde o defeito
> é possível.

## Índice de Cenários

| ID | Cenário | Regra | Camada | Arquivo | Mata |
|----|---------|-------|--------|---------|------|
| CT-01 | Data expõe os valores, e só eles | R1 | Feature | `tests/Kit/GuardrailsDtoTest.php` | M1, M2 |
| CT-02 | três formatos, mesmo Data | R1 | Feature | idem | M3 |
| CT-03 | cast do pacote converte texto | R1 | Feature | idem | M4 |
| CT-04 | sem herança reprova, motivo herança | R2a | Feature | `tests/Kit/DtoComLaravelDataTest.php` | M8, M9 |
| CT-05 | imutabilidade, motivo por defeito | R2a | Feature | idem | M8, M10 |
| CT-06 | sufixo reservado | R2a | Feature | idem | M11 |
| CT-07 | credencial nomeada | R2a | Feature | idem | M8, M12 |
| CT-08 | Data em componente | R2a | Feature | idem | M13 |
| CT-09 | `new` fora da fábrica | R2a | Feature | idem | M14 |
| CT-10 | Data correto aprovado (2 formas) | R2a | Feature | idem | M5, M6 |
| CT-11 | nome legítimo aprovado | R2a | Feature | idem | M7 |
| CT-12 | diretório inteiro | R2a | Feature | idem | M15 |
| CT-13 | raízes padrão são dado | R2b | Feature | idem | M16, M17 |
| CT-14 | kit publicado passa verde | R2b | Feature | idem | M16, M18 |
| CT-15 | árvore de produção imitada | R2b | Feature | idem | M19 |
| CT-16 | defaults, inclusive decisão | R3 | Feature | `tests/Kit/GuardrailsDtoTest.php` | M20, M21 |
| CT-17 | provedor sem e-mail | R3 | Feature | `tests/Kit/LoginSocialDtoTest.php` | M24 |
| CT-18 | e-mail vazio vira nulo | R3 | Feature | idem | M25 |
| CT-19 | três nomes de "verificado" | R3 | Feature | idem | M22, M23 |
| CT-20 | vacuidade declarada da API | R4 | Feature | `tests/Kit/DtoComLaravelDataTest.php` | — (par de CT-21) |
| CT-21 | três superfícies reprovam | R4 | Feature | idem | M26, M27, M28 |
| CT-22 | middleware bloqueia pelo Data | R5a | Feature | `tests/Kit/GuardrailsDtoTest.php` | M29 |
| CT-23 | callback grava pelo Data | R5a | Feature | `tests/Kit/LoginSocialDtoTest.php` | M30, M31 |
| CT-24 | tela recebe resultado tipado | R5a | Livewire | `tests/Kit/ConviteEmMassaDtoTest.php` | M32 |
| CT-25 | resumo do convite igual | R5b | Livewire | idem | M33, M34 |
| CT-26 | reenvio não duplica | R5b | Feature | idem | M35 |
| CT-27 | perfil sem as quatro credenciais | R6 | Feature | `tests/Kit/LoginSocialDtoTest.php` | M36, M37 |
| CT-28 | veredito inseguro bloqueia | R8 | Feature | `tests/Kit/GuardrailsDtoTest.php` | M39 |
| CT-29 | fora do schema, ação "schema" | R8 | Feature | idem | M38 |
| CT-30 | indisponível, ação "fail_open" | R8 | Feature | idem | M38, M40 |
| CT-31 | prompt nunca entra na trilha | R8 | Feature | idem | M41 |

## Sem CT-B

O `01` declara **Sem superfície de UI**. Os dois cenários de tela (CT-24, CT-25) afirmam sobre o
conteúdo da notificação, que o teste de componente prova.

## Revisão Adversarial — duas rodadas e a escalação

| Rodada | Entrada | Resultado |
|---|---|---|
| 1 | `04` v1 (21 CTs) | 5 implementações erradas passando; 21 lacunas; 5 mutantes sem matador |
| 2 | `04` v2 (30 CTs) | mais 5 implementações erradas; 8 lacunas; 4 conflitos internos (1 bloqueante); 7 mutantes sem matador; **veredito: não está pronto** |
| — | escalação ao usuário | teto de 2 rodadas atingido; decisões: **3 Data** e **raízes como dado** |

**Diagnóstico estrutural da rodada 2**: duas regras faziam trabalho de quatro. R2 misturava
*"o guarda julga certo"* com *"o guarda olha o lugar certo"*; R5 misturava *"o Data é usado"* com
*"nada mudou para o usuário"*. Esta versão as separa (R2a/R2b, R5a/R5b), que era exatamente o que a
skill prevê quando a segunda rodada ainda traz achado estrutural.

**Achados fechados nesta versão**: I6 (CT-22, CT-23, CT-24 — um ponto de uso por Data, com valor
discriminante), I7 (CT-13, CT-14), I8 (o "não cita" de CT-04, CT-05, CT-07), I9 (CT-02), I10
(CT-31), conflito CT-09×CT-10 da v2 (raiz temporária), L27/L29 (o mass assignment foi para o ponto
de uso; `widgets()` saiu do escopo).

**Fora do escopo desta entrega, com o achado registrado**: os três Data cortados levam junto as
lacunas que só existiam neles (L23 parcial, L27) — elas voltam com eles, na entrega seguinte.

## Divergências declaradas

- O projeto não tem suíte `Unit`; cenários que seriam `Unit` rodam em `tests/Kit`
  (`.ai/rules/testes.md` vence a skill).
- Helpers usados por mais de um arquivo de teste vão para `tests/Pest.php`, pela mesma rule.
