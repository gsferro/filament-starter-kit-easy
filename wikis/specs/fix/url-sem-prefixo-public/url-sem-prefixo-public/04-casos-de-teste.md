# Casos de Teste — `/public` nunca aparece na URL antes do painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> ela não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| geração da raiz de URL | 2 | 2 | **4** | **padrão** |

- **P=2**: integra com um componente existente (o `UrlGenerator` e o `Request` do framework); a
  regra em si é simples.
- **I=2**: link errado é retrabalho e incômodo — não é dinheiro, dado de terceiro nem autorização,
  e o rollback é remover uma chamada. **Não** dispara revisão adversarial.
- Técnicas: **EP** sobre o domínio da base, **BVA de sufixo** (a fronteira aqui é textual, não
  numérica) e **Esquema do Cenário** para o eixo dos painéis.
- Cenários: **15** · Regras: **7** · Mutantes previstos: **19** · **Sem matador: 1** (M11 — ver R4)
- *(recontado duas vezes: no **ciclo 2** entrou R6/CT-13 e a contagem anterior foi corrigida em
  três pontos — creditava CT-07, dizia 4 cenários em R4+R5 e declarava zero mutantes sem
  matador enquanto M11 se declarava sem. No **ciclo 3** entraram R7 e CT-14…CT-16, e os
  mutantes de R7 foram medidos um a um)*

> Teto do perfil padrão é 3 cenários por regra. R1 usa 3, R2 usa 2, R3 usa 1 (Esquema conta como
> 1), R4 usa 1, R6 usa 1, R7 usa 3 — e **R5 usa 4**, estouro declarado: a tabela de decisão dela tem seis
> linhas, e três cenários deixariam a combinação `false` com sinal presente (CT-12) sem matador.
> O gate de falsificabilidade vence o teto.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | um **middleware** (`RaizDeUrlSemPublic`), o registro dele em `bootstrap/app.php`, a chave `kit.url.remover_sufixo_public` em `config/kit.php` e o método `BooleanoDoEnv::ouNulo()`. Sem migration, model, job, policy ou command | CT-13, CT-14…CT-16 |
| **F** | decidir **se** força a raiz da URL, e **qual** raiz. Nada mais | CT-01…CT-08 |
| **D** | entrada única: `Request::getBaseUrl()`, derivado pelo Symfony de `SCRIPT_NAME`/`REQUEST_URI`. Partições: vazio · `/public` · `/algo` · `/algo/public` · `/meupublic` · `/PUBLIC` | CT-01…CT-05 |
| **I** | request HTTP de qualquer painel. A ausência de request deixou de ser cenário: com a correção em **middleware**, console e fila não a atravessam por construção | CT-06, CT-08, **CT-13** |
| **P** | servidor web que reescreve para dentro de `public/`; `TrustProxies` para o esquema atrás de proxy. **Banco não entra** — a feature não consulta nada | CT-08 |
| **O** | dois mundos: instalação **correta** (o caso comum, e o que não pode regredir) e instalação com `DocumentRoot` na raiz do projeto | CT-01, CT-02 |
| **T** | **não se aplica**: nenhum comportamento depende de instante, expiração, ordem ou concorrência | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — base terminada em `/public` não é honrada: a raiz passa a ser a base **sem** esse sufixo | raiz de URL (padrão) | RQ-01 | EP + BVA de sufixo | CT-01, CT-03, CT-04 |
| **R2** — base que **não** termina em `/public` é preservada intacta | raiz de URL (padrão) | RQ-01 | EP (partição complementar) | CT-02, CT-05 |
| **R3** — a garantia não depende de **qual** painel, nem de o painel constar de lista nenhuma | raiz de URL (padrão) | RQ-02, RQ-03 | Esquema do Cenário | CT-06 |
| **R4** — a raiz nova sai do request, nunca do `APP_URL` | raiz de URL (padrão) | ADR-02 | EP | CT-08 |
| **R5** — só encurta com **evidência positiva**; sem ela, não age | raiz de URL (padrão) | **RQ-06, RQ-07, RQ-08** (Adendo 1) | EP + tabela de decisão | CT-09…CT-12 |
| **R6** — a correção está **ligada**: middleware no stack global, depois do `TrustProxies` | raiz de URL (padrão) | RQ-01 (sem registro, nada vale) + ADR-05 | EP | CT-13 |
| **R7** — a chave tri-estado distingue "ausente" de "desligado" | raiz de URL (padrão) | RQ-08 | EP | CT-14…CT-16 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome `configureRaizDeUrl()` | escolha de implementação | detalhe do cenário, nunca `Então` |
| `KitServiceProvider` como local | escolha de implementação | detalhe |
| `URL::forceRootUrl` como mecanismo | escolha de implementação — o requisito fala do **endereço exibido**, não de como se chega nele | detalhe |

O `Então` de todo cenário afirma **a URL gerada**, que é exatamente o que RQ-01 nomeia
("exibir na url"). Nenhum cenário afirma que um método foi chamado.

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **RQ-01** — variação de caixa (`/PUBLIC`, `/Public`) conta como "o `/public`" da cláusula?
  - **Premissa adotada**: **não** — comparação sensível a caixa. O diretório do Laravel é
    literalmente `public`, e é ele que o servidor expõe; tratar `/PUBLIC` encurtaria a raiz de uma
    aplicação que legitimamente viva sob um caminho assim.
  - **Invariante afirmado junto, que nenhuma resposta inverte**: base **exatamente** `/public` é
    sempre encurtada — é o que CT-01 afirma.
  - **Se negado**: a linha `/PUBLIC` de CT-05 inverte, e a comparação passa a ser insensível a
    caixa.

## Setup Global

### Personas

Nenhuma — a feature não lê usuário, papel nem organização.

### Fixtures

Nenhuma no banco. O "mundo" de cada cenário é um `Illuminate\Http\Request` construído com as
variáveis de servidor que o Apache entregaria **depois** da reescrita interna:

```
SCRIPT_NAME     = <base>/index.php
SCRIPT_FILENAME = <raiz>/<base>/index.php
```

> **Só duas são load-bearing, e isto foi remedido no ciclo 2** *(alterado em 2026-09-18)*: o
> Symfony toma o basename de `SCRIPT_FILENAME`
> (`vendor/symfony/http-foundation/Request.php:prepareBaseUrl():1938`) e compara com o de
> `SCRIPT_NAME` (`:1940`). Sem `SCRIPT_FILENAME` a base sai **vazia** e nenhum cenário exercita
> nada.
>
> `PHP_SELF` (`:1942`) e `ORIG_SCRIPT_NAME` (`:1944`) estão na cadeia, mas como **fallback** —
> só são consultados quando o basename de `SCRIPT_NAME` **não** bate. No fixture acima ele
> bate, e por isso removê-los não muda nada. É a explicação da medição, e faltava.
>
> Este bloco já afirmou o contrário, como se fosse medido, e também que
> `app()->instance('request', …)` não alcançaria o `UrlGenerator`. **As duas eram falsas** — o
> harness que falhou na investigação não tinha `SCRIPT_FILENAME`, e o sintoma foi atribuído à
> causa errada. Fica registrado porque wiki que ensina o errado é pior que wiki omissa.

### Fakes

Nenhum. Sem fila, sem e-mail, sem HTTP externo.

### Estratégia de DB

Irrelevante — nenhum cenário toca banco.

---

## Regra R1 — base terminada em `/public` não é honrada

> `RQ-01` · perfil **padrão** · técnica: **EP** + **BVA de sufixo**
>
> A fronteira é textual: o que separa "encurta" de "não encurta" é o sufixo `/public` **como
> segmento completo**. As bordas são `/meupublic` (mesmo final, segmento diferente) e
> `/sistema/public` (sufixo presente, um nível mais fundo).

```gherkin
# language: pt
Funcionalidade: o endereço do painel nunca exibe /public

  Regra: base de URL terminada em /public não é honrada

    Cenário: [CT-01] o request que chega com /public gera endereço limpo
      Dado uma instalação cujo servidor entrega a base de URL "/public"
      Quando a aplicação monta o endereço do painel "/app/acme"
      Então o endereço é "https://kit.test/app/acme"
      E o endereço não contém "/public"

    Cenário: [CT-03] a base mais funda perde apenas o último segmento
      Dado uma instalação cujo servidor entrega a base de URL "/sistema/public"
      Quando a aplicação monta o endereço do painel "/admin"
      Então o endereço é "https://kit.test/sistema/admin"

    Cenário: [CT-04] o endereço de asset também sai limpo
      Dado uma instalação cujo servidor entrega a base de URL "/public"
      Quando a aplicação monta o endereço do arquivo "/css/kit/kit-correcoes.css"
      Então o endereço é "https://kit.test/css/kit/kit-correcoes.css"
```

> **CT-03 é o BVA que separa "remover o sufixo" de "zerar a raiz".** Uma implementação que
> devolvesse raiz vazia passaria em CT-01 e falharia aqui — e é o erro mais provável de quem
> escreve a correção com pressa.
>
> **CT-04 existe porque RQ-01 fala do endereço, não da rota.** Rota e asset saem do mesmo gerador;
> se a correção tocasse só o roteador, o CSS continuaria prefixado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `str_contains($base, '/public')` no lugar de `str_ends_with` | CT-05, linha `/publicacoes` |
| M2 | remove o sufixo com off-by-one (`-6` ou `-8` em vez de `-7`) | CT-01 e CT-03 — o endereço exato não bate |
| M3 | zera a raiz em vez de remover só o sufixo | **CT-03** — o `/sistema` desapareceria |
| M4 | corrige a geração de rota e esquece a de asset | **CT-04** |

---

## Regra R2 — base que não termina em `/public` é preservada

> `RQ-01` · perfil **padrão** · técnica: **EP**, partição complementar
>
> Esta regra é o que impede a correção de virar defeito. Sem ela, "tirar o `/public`" poderia ser
> implementado como "forçar sempre a raiz", e toda instalação em subdiretório quebraria.

```gherkin
    Cenário: [CT-02] instalação correta não é tocada
      Dado uma instalação cujo servidor entrega a base de URL vazia
      Quando a aplicação monta o endereço do painel "/app/acme"
      Então o endereço é "https://kit.test/app/acme"

    Esquema do Cenário: [CT-05] base que apenas se parece com /public é preservada
      Dado uma instalação cujo servidor entrega a base de URL "<base>"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "https://kit.test<base>/app"

      Exemplos:
        | base         | # partição                                    |
        | /sistema     | subdiretório legítimo                         |
        | /meupublic   | borda: mesmo final, segmento diferente        |
        | /publicacoes | borda: prefixo igual, não é sufixo            |
        | /PUBLIC      | @premissa: caixa diferente — ver Ambiguidades |
```

> A linha `/PUBLIC` é `@premissa`. Se a resposta do solicitante for "também conta", ela inverte e
> passa a esperar `https://kit.test/app`. O **invariante** que nenhuma resposta muda está em CT-01.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | condição invertida (`! str_ends_with`) | **CT-01** fica vermelho, e CT-02 também |
| M6 | força a raiz **sempre**, ignorando a condição | **CT-05**, linha `/sistema` |
| M7 | usa `config('app.url')` como raiz nova em vez do host do request | CT-01 e CT-05 — o host do cenário difere do `APP_URL` de teste |

---

## Regra R3 — a garantia não depende de qual painel

> `RQ-02`, `RQ-03` · perfil **padrão** · técnica: **Esquema do Cenário**
>
> RQ-03 é a cláusula que decide o desenho: *"ou outro que possa vir a ser criada pelo usuario do
> kit"*. Um cenário com um painel que **não existe no kit** é o que a falsifica — e é o único
> jeito de distinguir "corrigiu a raiz" de "corrigiu uma lista de painéis".

```gherkin
    Esquema do Cenário: [CT-06] qualquer painel sai sem /public
      Dado uma instalação cujo servidor entrega a base de URL "/public"
      Quando a aplicação monta o endereço "<caminho>"
      Então o endereço é "https://kit.test<caminho>"

      Exemplos:
        | caminho           | # painel                                 |
        | /app/acme         | do kit                                   |
        | /admin            | do kit                                   |
        | /infra            | do kit                                   |
        | /financeiro/lotes | painel INEXISTENTE — criado pelo usuário |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | lista fechada de painéis (`in_array($painel, ['app','admin','infra'])`) | **CT-06**, linha `/financeiro/lotes` |
| M9 | middleware registrado em cada `PanelProvider` do kit | **CT-06**, mesma linha — o painel novo não teria o middleware |

---

## Regra R4 — a raiz nova sai do request, nunca do `APP_URL`

> ADR-02 · perfil **padrão** · técnica: **EP**

```gherkin
    Cenário: [CT-08] o esquema e o host vêm do request, não de APP_URL
      Dado uma instalação servida em "https://outro.test" com reescrita na raiz
      E a aplicação configurada com APP_URL "https://kit.test"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "https://outro.test/app"
```

> **CT-07 foi removido em 2026-09-18.** Ele afirmava *"em console a raiz não é forçada"*, e o
> `/code-review` (achado 4) mostrou que ele exercitava a mesma partição de CT-02 e passava com
> **qualquer** implementação cujo gatilho fosse um teste de `/public`. Com a correção virando
> **middleware**, a regra ficou verdadeira por construção — middleware não roda em console —, e
> um cenário que não pode falhar não é caso de teste. A rastreabilidade do `04` creditava a ele
> mais do que ele entregava, que é o pior desfecho possível.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | monta a raiz nova com `config('app.url')` em vez do host do request | **CT-08** |
| M11 | lê o host no `boot()` do provider, antes do `TrustProxies` | ⚠️ **sem matador direto.** Lacuna declarada: reproduzir o efeito exigiria um proxy no arnês, com `X-Forwarded-Port` divergente. O que existe é a guarda **estrutural** de CT-13, que fixa a posição no stack — mata a causa, não o sintoma |

---

## Regra R5 — só encurta com evidência positiva; sem ela, não age

> `RQ-06`, `RQ-07`, `RQ-08` (Adendo 1) · perfil **padrão** · técnica: **EP** + tabela de decisão
>
> A regra que nasceu do achado 1 do `/code-review`, e a mais importante deste conjunto: sem ela a
> correção **quebra** uma instalação que funciona.

| `.htaccess` reescreve para `public/`? | `kit.url.remover_sufixo_public` | Encurta? | Cenário |
|---|---|---|---|
| sim | `null` (detecta) | **sim** | CT-01…CT-06, CT-08 |
| não existe | `null` | **não** | **CT-09** |
| existe, sem reescrita para `public/` | `null` | **não** | **CT-10** |
| não existe | `true` | **sim** | CT-11 |
| não existe | `false` | **não** | CT-11 |
| sim | `false` | **não** | **CT-12** |

```gherkin
    Cenário: [CT-09] sem sinal de reescrita, a raiz é preservada
      Dado uma instalação sem .htaccess na raiz
      E o servidor entregando a base de URL "/public"
      Quando a aplicação monta o endereço do painel "/app/acme"
      Então o endereço é "https://kit.test/public/app/acme"

    Cenário: [CT-10] .htaccess sem reescrita para public não conta como sinal
      Dado uma instalação cujo .htaccess da raiz só define um cabeçalho
      E o servidor entregando a base de URL "/public"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "https://kit.test/public/app"

    Cenário: [CT-11] true declarado encurta mesmo sem sinal, para nginx
      Dado uma instalação sem .htaccess na raiz
      E a configuração de remoção declarada como "true"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "https://kit.test/app"

    # A linha `false` que existia aqui foi CORTADA no ciclo 1 (ponytail): sem `.htaccess` a
    # detecção já devolve falso, então ela passava mesmo se a config fosse ignorada por
    # completo. Quem mata o `false` é CT-12, onde o sinal existe.

    Cenário: [CT-12] false explícito vence o sinal presente
      Dado uma instalação COM reescrita na raiz
      E a configuração de remoção declarada como "false"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "https://kit.test/public/app"
```

> **CT-09 é o cenário que derrubou o desenho anterior**, e o mais valioso do arquivo. Sem ele, a
> correção teria ido para a `main` quebrando toda instalação servida em `https://host/public/...`
> sem reescrita — que é o default de quem instala o kit, já que ele não distribui `.htaccess` na
> raiz.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | encurta sempre que a base termina em `/public`, sem exigir sinal (o desenho anterior) | **CT-09**, **CT-10** |
| M13 | aceita a mera existência do `.htaccess` como sinal | **CT-10** |
| M14 | lê a config só quando a detecção falha, deixando o `false` sem efeito onde há sinal | **CT-12** |

---

## Regra R6 — a correção está ligada

> RQ-01 (sem registro, nada vale) + ADR-05 · perfil **padrão** · técnica: **EP**
>
> **Nasceu do QA-01 do ciclo 1**, e o achado é o mais grave do gate: os outros doze cenários
> chamam `handle()` direto. Apagar o `append` de `bootstrap/app.php` deixa a feature **inerte**, e
> os 2.479 testes do kit seguem verdes — nada no repositório afirmava o registro.
>
> A **ordem** também é afirmada: antes do `TrustProxies` o middleware leria host e porta sem os
> `X-Forwarded-*` e congelaria uma raiz inalcançável. A ADR-05 decidia isso e nada mantinha.

```gherkin
    Cenário: [CT-13] o middleware está no stack global, depois do TrustProxies
      Dado a aplicação configurada como o kit a entrega
      Quando se inspeciona o stack de middleware global
      Então `RaizDeUrlSemPublic` está nele
      E a posição dele é posterior à do `TrustProxies`
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o `append` some do `bootstrap/app.php` | **CT-13** — provado por mutante: derruba só ele |
| M16 | registrado **antes** do `TrustProxies` | **CT-13**, segunda asserção |

---

## Regra R7 — a chave tri-estado distingue "ausente" de "desligado"

> `RQ-08` · perfil **padrão** · técnica: **EP** sobre o domínio do valor de env
>
> **Nasceu do QA-17 do ciclo 2**, e a origem importa: `BooleanoDoEnv::ouNulo()` foi escrito para
> fechar o QA-02 e entrou **sem um único caso**. A feature o exercita só por `config()->set()`,
> que pula a linha do `config/kit.php` — então apagar o guard dele fazia `KIT_ALGO=` virar `false`
> e **desligava a correção de URL com a suíte verde**.
>
> Os três cenários nasceram como `it()` sem ID, que é a mesma falha de sincronia pelo outro lado.
> Passaram por aqui no ciclo 3 e ganharam ID.

```gherkin
    Cenário: [CT-14] chave ausente ou vazia significa "decida sozinho"
      Dado a variável de ambiente ausente, ou presente e vazia
      Quando o kit lê a chave tri-estado
      Então o valor é nulo, e a detecção decide

    Cenário: [CT-15] valor ilegível cai no padrão, nunca em "desligado"
      Dado a variável de ambiente com "talvez", "sim", "2" ou "null"
      Quando o kit lê a chave tri-estado
      Então o valor é nulo

    Esquema do Cenário: [CT-16] valor legível declara
      Dado a variável de ambiente com "<bruto>"
      Quando o kit lê a chave tri-estado
      Então o valor é <esperado>

      Exemplos:
        | bruto | esperado | # partição        |
        | true  | true     | texto afirmativo  |
        | 1     | true     | numérico          |
        | on    | true     | palavra do filtro |
        | false | false    | texto negativo    |
        | 0     | false    | numérico          |
        | off   | false    | palavra do filtro |
        | true  | true     | booleano PHP, não string |
        | false | false    | booleano PHP, não string |
```

> **CT-15 é o que separa esta chave de uma booleana comum.** Num `comPadrao()`, valor ilegível
> cair em `false` é aceitável — o padrão é um booleano. Aqui `false` é **uma das três respostas**
> e significa "desligado de propósito"; deixar o ilegível virar `false` desliga a correção sem
> ninguém ter pedido.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | o guard de ausente/vazio some; `FILTER_NULL_ON_FAILURE` fica | **CT-14** — medido: 2 vermelhos, todos dele |
| M18 | o `FILTER_NULL_ON_FAILURE` some; o guard fica | **CT-15** — medido: 4 vermelhos, todos dele |
| M19 | o valor legível é ignorado (devolve sempre `null`) | **CT-16** |

> **Os dois primeiros foram medidos separados, e isso corrigiu um crédito errado** (QA-27 do
> ciclo 3). A versão anterior dizia "6 casos vermelhos" e creditava M17 a CT-14 **e** CT-15 —
> aquele 6 era a mutação dos dois defeitos ao mesmo tempo. Com `FILTER_NULL_ON_FAILURE`
> preservado, `filter_var('talvez', …)` já devolve `null` e o CT-15 passa. Cada mutante mata o
> seu, e nenhum cobre o do outro.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota, ação ou recurso por id |
| Autorização exercida na ação | **não se aplica**: a feature não decide permissão |
| Idempotência | **não se aplica**: sem operação de escrita; nada é persistido |
| Concorrência | **não se aplica**: sem contador, saldo ou limite |
| Fronteira no ponto de entrada | CT-03, CT-05 |
| Domínio condicionado | **não se aplica**: um único campo de entrada |
| Estado × operação de escrita | **não se aplica**: sem entidade com ciclo de vida |
| Ausente ≠ null ≠ vazio | CT-02 — a base **vazia** é partição própria, e é o caminho comum |
| Paginação / ordenação | **não se aplica** |
| Timezone / DST | **não se aplica**: dimensão **T** declarada vazia |
| Unicode / limite de varchar | **não se aplica**: a base é caminho de URL, não texto livre do usuário |
| Caixa (maiúscula/minúscula) | CT-05, linha `/PUBLIC` — `@premissa`, com a pergunta aberta |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: sem formulário nem payload |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Superfície Livewire** | **não se aplica**: sem página, widget ou componente — inventariado no `02` |
| **Lista paralela** (classe nova citada em mais de um lugar) | **CT-13** — o registro em `bootstrap/app.php` é a segunda lista, e era a que ninguém guardava |
| **Chave de configuração nova** | CT-11, CT-12; e o tri-estado (`""` e ilegível → `null`) por `BooleanoDoEnv::ouNulo()`, que é a convenção do kit |
| **Estado do framework usado sem validar** | CT-05 — `getBaseUrl()` é derivado de variável de servidor, e as bordas de sufixo são o domínio inválido dele. Não vira índice de array, `parse` nem nome de coluna |
| **IDOR por entidade** | **não se aplica**: nenhuma tabela persistida |
| **Escopo com discriminante nulo** | CT-02 e **CT-09** — a base vazia e a ausência de sinal são os dois "nulos" desta feature, e os dois cenários declaram que o desejado é **não agir** |
| **Saída do estado de erro** | **não se aplica**: a feature não produz 4xx, 5xx nem redirect |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | base `/public` gera endereço limpo | R1 | EP | Kit | `tests/Kit/UrlSemPrefixoPublicTest.php` | M2, M5 |
| CT-02 | instalação correta não é tocada | R2 | EP | Kit | idem | M5 |
| CT-03 | base mais funda perde só o último segmento | R1 | BVA | Kit | idem | M2, M3 |
| CT-04 | asset também sai limpo | R1 | EP | Kit | idem | M4 |
| CT-05 | base parecida com `/public` é preservada | R2 | BVA (Esquema) | Kit | idem | M1, M6 |
| CT-06 | qualquer painel, inclusive um inexistente | R3 | Esquema | Kit | idem | M8, M9 |
| CT-08 | host e esquema vêm do request | R4 | EP | Kit | idem | M7, M10 |
| CT-09 | sem sinal, a raiz é preservada | R5 | EP | Kit | idem | M12, M13 |
| CT-10 | `.htaccess` sem reescrita não é sinal | R5 | EP | Kit | idem | M13 |
| CT-11 | `true` declarado encurta sem sinal (nginx) | R5 | EP | Kit | idem | M14 |
| CT-12 | `false` vence o sinal presente | R5 | tabela de decisão | Kit | idem | M14 |
| CT-13 | o middleware está registrado, depois do `TrustProxies` | R6 | EP | Kit | idem | M15, M16 |
| CT-14 | chave ausente ou vazia é "decida sozinho" | R7 | EP | Kit | `tests/Kit/BooleanoDoEnvTest.php` | M17 |
| CT-15 | valor ilegível cai no padrão | R7 | EP | Kit | idem | M18 |
| CT-16 | valor legível declara | R7 | EP (Esquema) | Kit | idem | M19 |

**Camada**: todos em `tests/Kit`, a suíte do kit com a aplicação bootada. É a mais barata que os
prova: o oráculo é **a URL gerada**, e para observá-la bastam um `Request` construído e o gerador
de URL. Nenhum cenário exige HTTP real, componente Livewire ou navegador — não há JavaScript,
pixel nem acessibilidade em jogo.

## Sem CT-B

- **Motivo**: nenhum cenário afirma sobre JavaScript executado, console, acessibilidade, cor ou
  layout. A URL gerada é observável em PHP puro, e levá-la ao navegador custaria dezenas de
  segundos por cenário sem provar nada a mais.
- A `## Superfície de UI` do `01` declara "Sem superfície de UI própria", então o gate do `05` não
  chega a abrir.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| request real por HTTP a `/public/app`, conferindo o HTML | o `.htaccess` que produz a base não existe no ambiente de teste; o cenário mediria o servidor de teste, não a correção |
| um cenário por painel, em arquivos separados | mesma classe de equivalência de CT-06; o Esquema já os cobre e conta como 1 |
| afirmar que `URL::forceRootUrl` foi chamado | oráculo de implementação — proibido pela Fronteira com o Plano |
| base `/public/` com barra final | o Symfony normaliza a base sem barra final; o cenário não é expressável pelo arnês |
