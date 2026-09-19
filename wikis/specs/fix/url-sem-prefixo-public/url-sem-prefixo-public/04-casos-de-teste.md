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
- Cenários: **11** · Regras: **5** · Mutantes previstos: **14** · Sem matador: **0**
- *(recontado em 2026-09-18, depois do Adendo 1: entra R5 com CT-09…CT-12, e CT-07 sai)*

> Teto do perfil padrão é 3 cenários por regra. R1 usa 3, R2 usa 2, R3 usa 1 (Esquema conta como
> 1) e R4 usa 2 — nenhum estouro.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | um método `protected` num provider. Sem migration, model, job, policy, command ou config nova | — |
| **F** | decidir **se** força a raiz da URL, e **qual** raiz. Nada mais | CT-01…CT-08 |
| **D** | entrada única: `Request::getBaseUrl()`, derivado pelo Symfony de `SCRIPT_NAME`/`REQUEST_URI`. Partições: vazio · `/public` · `/algo` · `/algo/public` · `/meupublic` · `/PUBLIC` | CT-01…CT-05 |
| **I** | request HTTP de qualquer painel; **e a ausência de request** (console, fila, scheduler) | CT-06, CT-07, CT-08 |
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
SCRIPT_NAME     = /public/index.php
SCRIPT_FILENAME = <raiz>/public/index.php
PHP_SELF        = /public/index.php
REQUEST_URI     = <o que o cenário declara>
```

> **Por que as quatro, e não só `SCRIPT_NAME`**: o Symfony percorre `SCRIPT_FILENAME`, `PHP_SELF`
> e `ORIG_SCRIPT_NAME` na cadeia de derivação da base. Omitir qualquer uma produz base **vazia**
> em todos os casos — e aí todo cenário passaria sem exercitar nada. Isto foi medido durante a
> investigação, com dois harnesses errados antes do certo, e por isso está escrito aqui em vez de
> ficar implícito.

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
| M11 | lê o host no `boot()` do provider, antes do `TrustProxies` | **não coberto por teste** — é decisão de LOCAL, e a guarda é a ADR-05 mais o registro em `bootstrap/app.php`. Lacuna declarada: reproduzir exigiria um proxy no arnês |

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

    Esquema do Cenário: [CT-11] a declaração explícita vence a detecção
      Dado uma instalação sem .htaccess na raiz
      E a configuração de remoção declarada como "<declarado>"
      Quando a aplicação monta o endereço do painel "/app"
      Então o endereço é "<esperado>"

      Exemplos:
        | declarado | esperado                      | # caso                |
        | true      | https://kit.test/app          | nginx, reescrita no vhost |
        | false     | https://kit.test/public/app   | desligado de propósito    |

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
| **Estado do framework usado sem validar** | CT-05 — `getBaseUrl()` é derivado de variável de servidor, e as bordas de sufixo são o domínio inválido dele. Não vira índice de array, `parse` nem nome de coluna |
| **IDOR por entidade** | **não se aplica**: nenhuma tabela persistida |
| **Escopo com discriminante nulo** | CT-02 e **CT-09** — a base vazia e a ausência de sinal são os dois "nulos" desta feature, e os dois cenários declaram que o desejado é **não agir** |
| **Saída do estado de erro** | **não se aplica**: a feature não produz 4xx, 5xx nem redirect |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | base `/public` gera endereço limpo | R1 | EP | Kit | `tests/Kit/UrlSemPrefixoPublicTest.php` | M2, M5, M7 |
| CT-02 | instalação correta não é tocada | R2 | EP | Kit | idem | M5 |
| CT-03 | base mais funda perde só o último segmento | R1 | BVA | Kit | idem | M2, M3 |
| CT-04 | asset também sai limpo | R1 | EP | Kit | idem | M4 |
| CT-05 | base parecida com `/public` é preservada | R2 | BVA (Esquema) | Kit | idem | M1, M6, M7 |
| CT-06 | qualquer painel, inclusive um inexistente | R3 | Esquema | Kit | idem | M8, M9 |
| CT-08 | host e esquema vêm do request | R4 | EP | Kit | idem | M10 |
| CT-09 | sem sinal, a raiz é preservada | R5 | EP | Kit | idem | M12, M13 |
| CT-10 | `.htaccess` sem reescrita não é sinal | R5 | EP | Kit | idem | M13 |
| CT-11 | declaração explícita vence a detecção | R5 | tabela de decisão | Kit | idem | M14 |
| CT-12 | `false` vence o sinal presente | R5 | tabela de decisão | Kit | idem | M14 |

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
