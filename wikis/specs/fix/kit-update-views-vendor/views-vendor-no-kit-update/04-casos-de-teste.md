# Casos de Teste — Issue #148: `kit:update` entrega os overrides autorais de `resources/views/vendor`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, stack e superfície, recebidos pela sessão)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (a correção não existe).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Entrega do override da lock-screen (R1, R6) | 1 | 2 | 2 | mínimo |
| Classificação autoral × publish cru e varredura (R2, R3, R4) | 2 | 2 | 4 | padrão |
| Guarda da árvore do kit (R5) | 2 | 2 | 4 | padrão |
| Registro no CHANGELOG (R7) | 1 | 1 | 1 | mínimo |

- Probabilidade 2 na classificação: comparação de conteúdo com fim de linha, pares por caminho relativo e o mesmo nome em mais de um pacote. Impacto 2: o defeito de cada direção é retrabalho manual (view que não chega; customização do projeto sobrescrita, recuperável pelo git). Nenhuma área com Impacto 3 nem perfil completo pelo critério da derivação; a sessão despachou a revisão adversarial mesmo assim (ver `## Revisão adversarial`), e ela devolveu 21 achados — 19 aplicados nesta versão *(alterado em 2026-10-06)*.
- Técnicas aplicadas: EP (classificação por conteúdo, partições isoladas), tabela de decisão (lista × autoria), rastreio de efeito negativo (pulo declarado fora da árvore).
- Cenários: 14 · Regras: 7 · Mutantes previstos: 33 · Sem matador: 1
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho); recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a constante `KitUpdate::CAMINHOS_DO_KIT` (entradas novas), a varredura de `resources/views/vendor` em `tests/Kit/KitUpdateTest.php`, a entrada do `CHANGELOG.md` | CT-01, CT-06…CT-08, CT-11, CT-14 |
| F | entregar override autoral pelo `kit:update`; classificar pasta como autoral ou publish cru; reprovar divergência lista × autoria antes da tag | CT-01…CT-08 |
| D | 12 pastas reais, partições autoral (ao menos uma view diferente) e cru (todas idênticas); pasta mista (3 views); arquivo do kit sem par com o pacote instalado; view sem pacote nenhum; mesmo caminho relativo em dois pacotes, em ambas as ordens; pasta com nome diferente do pacote; subpasta; arquivo não-blade; pasta vazia; fim de linha CRLF × LF nas duas direções; espaço de borda | CT-02, CT-06…CT-08 |
| I | `php artisan kit:update` (lê a lista do fonte do destino por `caminhosDeclaradosEm()` e une com a desta versão); a suíte `kit` antes da tag | CT-01, CT-10 |
| P | Windows (CRLF no checkout, separador `\`) e Linux; árvore do kit × projeto instalado (`.github` e `CHANGELOG.md` são `export-ignore`, `.gitattributes:21`) | CT-02, CT-09, CT-11 |
| O | mantenedor edita uma view publicada e esquece a lista (a classe do issue); pacote atualiza a própria view e o publish cru fica para trás (P-05); projeto instalado com publishes próprios (P-04) | CT-03, CT-06, CT-09 |
| T | não se aplica ao comportamento: nada depende de relógio. A única dimensão temporal é a drift do pacote (P-05), tratada como dado em CT-03 | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a view da lock-screen está coberta por `CAMINHOS_DO_KIT` e chega inteira pela entrada-pasta | entrega (mínimo) | RQ-01, RQ-05 | EP (1 partição) + entrega simulada | CT-01, CT-14 |
| R2 — autoria decidida por conteúdo contra o vendor instalado, fim de linha normalizado, pasta inteira | classificação (padrão) | P-03, P-02, P-01, RQ-04 | EP, partições isoladas | CT-02 |
| R3 — a varredura reprova autoral fora da lista e crua dentro dela (coberta por qualquer forma de entrada), e nomeia pasta, arquivos divergentes e a saída certa de cada direção | classificação (padrão) | RQ-03, RQ-04, P-01, P-05 | tabela de decisão (autoria × cobertura: exata, ancestral, sem par) | CT-03, CT-04, CT-05, CT-12 |
| R4 — na árvore real do kit, toda pasta autoral está coberta e nenhuma crua está | classificação (padrão) | RQ-02, RQ-03, RQ-04, P-01, P-02 | EP sobre a árvore real + controle positivo | CT-06, CT-07, CT-08 |
| R5 — a varredura só roda na árvore do kit e, fora dela, pula com motivo | guarda (padrão) | P-04 | rastreio de efeito (pulou declarado na extração) + guarda declarada no fonte | CT-09, CT-13 |
| R6 — as entradas novas têm a forma que `caminhosDeclaradosEm()` lê | entrega (mínimo) | RQ-01 (chega a quem atualiza) | EP (1 partição) | CT-10 |
| R7 — o CHANGELOG registra a correção do #148 | changelog (mínimo) | sem `RQ`: convenção de entrega do kit, pedida pela sessão (ver Fronteira) | EP (1 partição) | CT-11 |

- RQ-05 — coberta pela entrega (CT-01) e pela entrega simulada (CT-14, *alterado em 2026-10-06: ADV-21*). O `LogoDarkModeTest` CT-16 existente é verde na árvore do kit antes e depois da correção e não discrimina. O cenário 3 **sobre a tag publicada** continua Fora de Escopo do `00`.
- Q1, Q2 e Q3 abertas no `00` não bloqueiam: Q1/Q2 estão vigentes como P-01/P-02 (a direção falha fechado) e Q3 é a tag. Nenhuma `RQ` está `aberta — Qn`.
- R2 escala de "mínimo" para EP com 7 partições isoladas: a classificação é o ponto onde mora a direção errada das duas (RQ-02 × RQ-04), e um `Esquema do Cenário` conta como 1.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Lista do kit | R1, R6 | unit de regra | existente — `tests/Kit/KitUpdateTest.php` (`estaCoberto()`, `caminhosDoKit()`, caso `extrai do fonte desta versão…`) | a afirmação é sobre o valor da constante e do leitor estático; nenhum banco nem rota | sessão (desenvolvedor), 2026-10-06 |
| Classificação por fixture | R2, R3 | unit de regra | nova, no mesmo arquivo — a classificação e a varredura chamáveis com **duas raízes** (views do kit, views do vendor) e **uma lista**, apontadas para um diretório temporário. Nenhuma costura existente serve: a varredura atual só olha a árvore real, onde as partições "difere só em CRLF", "sem par" e "drift do pacote" podem não existir | sem fixture, os mutantes de normalização e de pasta mista dependem do checkout de quem roda | sessão (desenvolvedor), 2026-10-06 |
| Árvore real do kit | R4, R5 | unit de regra | existente — o caso `cobre todo o código do kit…` de `tests/Kit/KitUpdateTest.php`, que hoje pula `resources/views/vendor/` com `continue` | a afirmação é sobre os arquivos do repositório × a constante; guarda `naArvoreDoKit()` de `tests/Pest.php` | sessão (desenvolvedor), 2026-10-06 |
| CHANGELOG | R7 | unit de regra | existente — padrão do caso `documenta a lista do destino…` de `tests/Kit/KitUpdateTest.php` (CHANGELOG inteiro + `->skip(fn (): bool => ! naArvoreDoKit(), …)`) | leitura de arquivo da árvore | sessão (desenvolvedor), 2026-10-06 |

`tests/Kit` é ligado ao `TestCase` da aplicação com `RefreshDatabase` (`tests/Pest.php`, `pest()->extend(TestCase::class)…->in('Kit')`): "unit de regra" aqui roda com container, sem tocar banco. Helper usado só por `KitUpdateTest.php` fica nele (`.ai/rules/testes.md` §Helper de teste).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `estaCoberto()`, `caminhosDoKit()`, `caminhosDeclaradosEm()`, `caminhosUnidos()`, `naArvoreDoKit()` | escolha de implementação já existente | detalhe do cenário |
| a forma textual `        'caminho',` lida por regex | escolha de implementação; o oráculo é a **igualdade** entre o que o leitor extrai e a constante, não a forma | detalhe de CT-10 |
| contagem medida no step 3 (12 pastas; 5 autorais; 7 cruas; quais são) | fato do ambiente, não requisito: virar `Então` congelaria a medição e reprovaria a P-05 legítima | só no `Dado` e no Setup Global (existência das duas partições); o oráculo é a classificação por conteúdo |
| entrada do `CHANGELOG.md` | nenhuma `RQ` pede o registro; é convenção de entrega do kit (`.ai/rules/testes.md` §CHANGELOG) pedida pela sessão | mantido como CT-11, mínimo, origem declarada sem `RQ`; candidato a corte se a sessão discordar |
| as palavras exatas da mensagem de falha | o requisito só fixa o conteúdo: pasta, arquivos e as duas saídas (P-05) | CT-03 afirma presença do nome da pasta, do caminho do arquivo, de `CAMINHOS_DO_KIT` e de `vendor:publish`, não a frase |

**Perguntas geradas pela derivação** (em sub-agente: `Q?n` provisório, a sessão renumera):

❓ Q7 · raia: desenho · afeta: P-03 · depende de: — · **respondida pela sessão em 2026-10-06: a recomendação vale (é a D3 do `01`); as duas linhas de CT-02 deixam de ser premissa aberta**
P-03 manda comparar com "a view de mesmo caminho relativo dentro de `vendor/*/*/resources/views`", sem amarrar a pasta (`resources/views/vendor/pulse`) ao pacote. Há dois casos que o texto não fecha: (a) o mesmo caminho relativo existe em mais de um pacote (`laravel/pulse` e `mohaphez/pulse`); (b) a view do kit não tem par em pacote nenhum. Contra qual candidato se compara, e o que é a view sem par?
➡️ Recomendação: (a) publish cru se for idêntica a **algum** candidato; (b) sem par é **autoral** (não é publish de nada). É a leitura literal do glossário — "publish cru" exige identidade com o pacote instalado — e a direção que não deixa view do kit sem entrega; amarrar pasta a pacote pelo namespace da view custaria um mapa à mão, que P-03 recusa. Não bloqueia: as duas linhas de CT-02 saíram marcadas `@premissa` e foram fechadas com a resposta.

## Setup Global

### Fixtures
- **Árvore real** (CT-06…CT-09): `base_path('resources/views/vendor')` e `base_path('vendor')`. Fato medido no step 3, usado só para afirmar que o `Dado` é discriminante: há pastas das duas partições (5 autorais, entre elas a mista `command-center` 3/4; 7 cruas), e o nome `pulse` existe em mais de um pacote.
- **Árvore de fixture** (CT-02…CT-05, CT-12): diretório temporário único por caso (`sys_get_temp_dir()` + id), com `views/{pasta}/...` e `vendor/{fornecedor}/{pacote}/resources/views/...`, apagado no `afterEach`. Conteúdo escrito com fim de linha explícito (`"\n"` ou `"\r\n"`), nunca herdado do checkout. O par de uma view do kit é procurado pelo **caminho relativo dentro da pasta** em **todos** os `vendor/*/*/resources/views` — nunca pelo nome da pasta (ADV-01) —, a listagem da pasta do kit é **recursiva** e inclui **todo arquivo**, não só `*.blade.php` (ADV-03, ADV-04), e a iteração parte das views **do kit** (ADV-02).
- **Lista**: nas fixtures, a lista é passada à varredura; na árvore real, é `caminhosDoKit()`.

### Fakes
- Nenhum. Não há fila, e-mail, HTTP nem evento.

### Estratégia de DB
- `RefreshDatabase` herdado de `tests/Kit` (`tests/Pest.php`); nenhum cenário toca banco.

### Guarda da árvore
- Caso que lê `resources/views/vendor` e `vendor/` reais para decidir autoria, ou lê `CHANGELOG.md`: `->skip(fn (): bool => ! naArvoreDoKit(), '{motivo}')`. **Divergência declarada**: o caso vizinho `cobre todo o código do kit…` usa `is_dir(base_path('.github'))` com `expect(true)->toBeTrue(); return;` (verde por ausência); P-04 pede pulo **declarado**, então o caso novo usa `skip` com motivo — a regra de `.ai/rules/testes.md` (§Caso que lê `docs/`…) vence o padrão do vizinho.

---

## Regra R1 — a view da lock-screen está coberta por `CAMINHOS_DO_KIT`

> `RQ-01`, `RQ-05` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt
Funcionalidade: Entrega dos overrides autorais de resources/views/vendor pelo kit:update

  Regra: o override da lock-screen chega a quem atualiza

    Cenário: [CT-01] a view do par claro/escuro da lock-screen está na rota de entrega do kit:update
      Dado o caminho "resources/views/vendor/filament-auth-designer/components/partials/media.blade.php"
      Quando o mantenedor confere a cobertura pela lista de caminhos do kit desta versão
      Então o caminho está coberto por uma entrada de CAMINHOS_DO_KIT

    Cenário: [CT-14] a entrada-pasta entrega o arquivo aninhado a uma árvore que não o tinha
      Dado uma árvore temporária sem "resources/views/vendor/filament-auth-designer"
      E a lista de caminhos unida com a de um destino antigo que não tem a entrada
      Quando o mantenedor extrai desta versão, por git archive, as entradas da lista unida que começam por "resources/views/vendor/" para a árvore temporária
      Então "resources/views/vendor/filament-auth-designer/components/partials/media.blade.php" existe na árvore temporária
      E o conteúdo dele é o do kit
      # Pula fora da árvore do kit: precisa do git do kit (ADV-21 — simulação local do cenário 3, sem a tag)
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a entrada `resources/views/vendor/filament-auth-designer` não entra na constante (correção só no `.gitattributes`, na doc ou num comentário da constante) | CT-01 | `estaCoberto(media.blade.php)` é `true`; o mutante devolve `false` |
| M2 | entrada com o caminho errado: `resources/views/filament-auth-designer` (sem `vendor/`) ou `…/filament-auth-design` | CT-01 | o prefixo da entrada não casa com o caminho do `Dado`; `estaCoberto` devolve `false` |
| M31 | entrada por arquivo (`…/partials/media.blade.php`) ou entrega não recursiva da pasta: a lista unida com um destino antigo perde o arquivo aninhado | CT-14 | o arquivo existe na árvore temporária com o conteúdo do kit; o mutante não o extrai (ou extrai só a raiz da pasta) |

---

## Regra R2 — autoria decidida por conteúdo contra o vendor instalado

> `P-03`, `P-02`, `P-01`, `RQ-04` · perfil **padrão** · técnica: **EP**, uma partição por linha (cada linha difere da linha "idêntica" em um só fator)

```gherkin
  Regra: a pasta é autoral se ao menos uma view difere da do pacote, comparando com fim de linha normalizado

    Esquema do Cenário: [CT-02] a classificação de uma pasta de override segue o conteúdo das views
      Dado uma árvore de fixture com a pasta "<pasta>" de views do kit e o pacote "<pacote>" no vendor
      E as views da pasta comparadas com as de mesmo caminho relativo no pacote são "<relacao>"
      Quando a varredura classifica a pasta
      Então a pasta é classificada como "<classe>"

      Exemplos:
        | pasta    | pacote                           | relacao                                                                                              | classe  | # partição                                                   |
        | cru      | acme/cru                         | uma view, idêntica byte a byte                                                                       | cru     | idêntica                                                     |
        | crlf     | acme/crlf                        | uma view, igual exceto CRLF no kit e LF no pacote                                                    | cru     | só fim de linha, kit em CRLF                                 |
        | crlf-inv | acme/crlf-inv                    | uma view, igual exceto LF no kit e CRLF no pacote                                                    | cru     | só fim de linha, pacote em CRLF (ADV-05)                     |
        | byte     | acme/byte                        | uma view, um espaço a mais no meio de uma linha                                                      | autoral | diferença mínima de conteúdo                                 |
        | borda    | acme/borda                       | uma view, igual exceto um espaço no fim de uma linha e um "\n" a mais no fim do arquivo               | autoral | só espaço de borda — mata `trim`/`rtrim` (ADV-12)            |
        | mista    | acme/mista                       | três views: "a.blade.php" idêntica, "b.blade.php" diferente, "c.blade.php" idêntica                  | autoral | pasta mista — mata primeira, última e maioria (ADV-13)       |
        | extra    | acme/extra                       | "a.blade.php" idêntica e "novo.blade.php" que o pacote instalado não tem                             | autoral | arquivo do kit sem par, pacote presente (ADV-02)             |
        | aninhada | acme/aninhada                    | "x.blade.php" idêntica na raiz e "components/y.blade.php" diferente                                  | autoral | subpasta (ADV-03)                                            |
        | svg      | acme/svg                         | nenhuma blade; "icone.svg" diferente do do pacote                                                    | autoral | arquivo não-blade (ADV-04)                                   |
        | outro    | acme/outro-nome                  | uma view idêntica, mas o pacote não tem o nome da pasta                                              | cru     | pasta ≠ pacote (ADV-01)                                      |
        | vazia    | (nenhum)                         | pasta sem nenhum arquivo                                                                             | cru     | cardinalidade 0 — nada autoral, nada a entregar (ADV-06)     |
        | sopar    | (nenhum)                         | uma view sem par em pacote nenhum                                                                    | autoral | Q7 (b), decidida: autoral                                    |
        | painel   | acme/a-diferente e acme/z-igual  | mesma view relativa nos dois; diferente da do primeiro em ordem, idêntica à do último                | cru     | Q7 (a), decidida: cru — mata "só o primeiro" (ADV-11)        |
        | painel2  | acme/a-igual e acme/z-diferente  | mesma view relativa nos dois; idêntica à do primeiro em ordem, diferente da do último                | cru     | espelho — mata "só o último" (ADV-11)                        |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M3 | "existe no vendor" no lugar de comparar conteúdo | CT-02 linhas `byte`, `mista`, `final` | esperado `autoral`; o mutante classifica `cru` (as três têm par no vendor) |
| M4 | comparação sem normalizar fim de linha | CT-02 linha `crlf` | esperado `cru`; o mutante vê bytes diferentes e classifica `autoral` |
| M5 | normalização larga demais (`trim()`, `rtrim()` por linha ou remoção de todo espaço em branco) | CT-02 linhas `byte` e `borda` | esperado `autoral`; o mutante apaga a diferença de borda (`borda`) ou todo espaço (`byte`) e classifica `cru` |
| M6 | pasta decidida pela primeira view, pela última ou pela maioria, não por "ao menos uma" | CT-02 linha `mista` | esperado `autoral`; com `a` = , `b` ≠ , `c` = , os três mutantes classificam `cru` |
| M7 | compara só com o primeiro (ou só com o último) pacote que tem o caminho, ou trata sem par como cru | CT-02 linhas `painel`, `painel2` e `sopar` | `painel`: esperado `cru`, "só o primeiro" compara com `acme/a-diferente` e dá `autoral`; `painel2`: "só o último" dá `autoral`; `sopar`: esperado `autoral`, o mutante dá `cru` |
| M24 | o par é procurado pelo nome da pasta (`vendor/*/{pasta}/resources/views`), não pelo caminho relativo em todos os pacotes | CT-02 linha `outro` | esperado `cru`; o mutante não acha `acme/outro-nome` e dá `autoral` (na árvore real, `authentication-log` e `asmit-resized-column` virariam autorais) |
| M25 | a iteração parte das views do pacote: arquivo que só o kit tem nunca é olhado | CT-02 linha `extra` | esperado `autoral`; o mutante só compara `a.blade.php` e dá `cru` |
| M26 | listagem não recursiva da pasta do kit | CT-02 linha `aninhada` | esperado `autoral`; o mutante não vê `components/y.blade.php` e dá `cru` |
| M27 | só `*.blade.php` entra na comparação | CT-02 linha `svg` | esperado `autoral`; o mutante não vê arquivo e dá `cru` |
| M28 | fim de linha normalizado só do lado do kit | CT-02 linha `crlf-inv` | esperado `cru`; o mutante vê bytes diferentes e dá `autoral` |

---

## Regra R3 — a varredura reprova divergência entre a lista e a autoria

> `RQ-03`, `RQ-04`, `P-01`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** autoria × cobertura. "Coberta" é por prefixo, como `estaCoberto()` — entrada exata da pasta, entrada ancestral (`resources/views/vendor`) ou entrada de arquivo dentro dela (ADV-07). Células: autoral×coberta = aprova; autoral×fora = reprova (inclusive sem pacote instalado, ADV-08); cru×coberta = reprova, por qualquer forma de entrada; cru×fora = aprova — as duas de aprovação cabem num cenário só, CT-05

```gherkin
  Regra: override autoral fora da lista e publish cru dentro dela reprovam, com mensagem acionável

    Cenário: [CT-03] publish cru que o pacote atualizou é acusado como autoral fora da lista, com as duas saídas e só os arquivos que divergem
      Dado uma árvore de fixture com a pasta "drift" com "painel.blade.php" igual à versão antiga da view do pacote "acme/drift" e "outro.blade.php" idêntico ao pacote
      E o pacote instalado com a view "painel.blade.php" já alterada
      E a lista de caminhos do kit sem "resources/views/vendor/drift"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "drift", o arquivo "painel.blade.php", "CAMINHOS_DO_KIT" e "vendor:publish"
      E a mensagem não cita "outro.blade.php"

    Esquema do Cenário: [CT-04] publish cru coberto pela lista reprova, por qualquer forma de entrada, com a saída de remover
      Dado uma árvore de fixture com a pasta "crua" idêntica à do pacote "acme/crua"
      E a lista de caminhos do kit com "<entrada>"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "crua" e a saída "remover de CAMINHOS_DO_KIT"
      E a mensagem não sugere listá-la

      Exemplos:
        | entrada                                        | # forma            |
        | resources/views/vendor/crua                    | exata              |
        | resources/views/vendor                         | ancestral (ADV-07) |
        | resources/views/vendor/crua/painel.blade.php   | arquivo (ADV-07)   |

    Cenário: [CT-05] lista coerente com a autoria aprova
      Dado uma árvore de fixture com a pasta autoral "editada" e a pasta "crua" idêntica ao pacote
      E a lista de caminhos do kit com "resources/views/vendor/editada" e sem "resources/views/vendor/crua"
      Quando a varredura confere a lista contra a árvore
      Então a varredura aprova, sem nenhuma pasta acusada

    Cenário: [CT-12] pasta autoral sem pacote instalado e fora da lista reprova
      Dado uma árvore de fixture com a pasta "sopar" com uma view sem par em pacote nenhum
      E a lista de caminhos do kit sem "resources/views/vendor/sopar"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "sopar" e "CAMINHOS_DO_KIT"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M8 | a varredura só confere uma direção (autoral fora da lista) e ignora cru listada — a direção que sobrescreve customização do projeto | CT-04 | esperado "reprova" citando `crua`; o mutante aprova |
| M9 | mensagem genérica, sem os arquivos ou com uma saída só | CT-03 | a mensagem contém `painel.blade.php`, `CAMINHOS_DO_KIT` **e** `vendor:publish`; o mutante falta ao menos um |
| M10 | "toda pasta de `resources/views/vendor` fora da lista reprova", sem classificar (o `continue` trocado por exigência total) | CT-05 | esperado "aprova" com `crua` fora da lista; o mutante acusa `crua` |
| M29 | "coberta" decidida por igualdade exata da entrada com `resources/views/vendor/{pasta}` | CT-04 linhas `ancestral` e `arquivo` | esperado "reprova" citando `crua`; o mutante não vê a cobertura e aprova |
| M30 | a varredura pula (`continue`) pasta sem pacote instalado na direção autoral × fora da lista | CT-12 | esperado "reprova" citando `sopar`; o mutante aprova |
| M9b | mensagem da direção cru × coberta reaproveita a de autoral × fora ("liste em `CAMINHOS_DO_KIT`") | CT-04 | a mensagem contém "remover de CAMINHOS_DO_KIT" e não "liste"; o mutante falha nas duas (ADV-17) |
| M9c | mensagem lista todos os arquivos da pasta, não os divergentes | CT-03 | a mensagem não contém `outro.blade.php`; o mutante contém (ADV-18) |

---

## Regra R4 — na árvore real do kit, toda pasta autoral está coberta e nenhuma crua está

> `RQ-02`, `RQ-03`, `RQ-04`, `P-01`, `P-02` · perfil **padrão** · técnica: **EP** sobre a árvore real (as duas partições existem nela — Setup Global) + controle positivo

```gherkin
  Regra: a lista de caminhos do kit desta versão casa com a autoria das pastas de resources/views/vendor

    Cenário: [CT-06] toda pasta autoral está coberta inteira, inclusive por arquivo que ainda não existe
      Dado a árvore do kit com as pastas de resources/views/vendor e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então toda view de toda pasta autoral está coberta por CAMINHOS_DO_KIT
      E o caminho "novo-arquivo-sonda.blade.php" dentro de cada pasta autoral também está coberto

    Cenário: [CT-07] nenhuma view de pasta publish cru está coberta
      Dado a árvore do kit com as pastas de resources/views/vendor e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então nenhuma view de pasta classificada como publish cru está coberta por CAMINHOS_DO_KIT

    Cenário: [CT-08] a varredura examina todas as pastas da árvore real
      Dado a árvore do kit com as pastas de resources/views/vendor e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então o número de pastas classificadas é o número de diretórios de resources/views/vendor, e é maior que zero
      # A existência das duas partições é controle positivo da fixture (CT-05), não da árvore real: afirmá-la aqui congelaria a medição do step 3 e reprovaria a saída recomendada da Q1 (ADV-14)
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | uma pasta autoral esquecida na lista (só `filament-auth-designer`, como sugere o issue, sem a auditoria do resto) | CT-06 | a lista de pastas autorais descobertas é `[]`; o mutante lista as autorais que faltaram |
| M12 | entradas por arquivo em vez de pasta (`command-center` só com as 3 views editadas; `filament-auth-designer/components/partials/media.blade.php`) | CT-06 | a view idêntica de `command-center` e a sonda `novo-arquivo-sonda.blade.php` estão cobertas; o mutante deixa as duas de fora |
| M13 | uma pasta crua acrescentada à lista (ex.: `pulse`, por "auditoria" que lista tudo que existe) | CT-07 | a lista de views cruas cobertas é `[]`; o mutante devolve as views de `pulse` |
| M14 | `resources/views/vendor` inteiro na lista | CT-07 | idem: toda view crua fica coberta e a lista deixa de ser `[]` |
| M15 | raiz das views ou do vendor montada errada (separador `\` no Windows, `base_path` duplicado) e a varredura examina zero pastas — CT-06 e CT-07 verdes por vazio | CT-08 | pastas classificadas = diretórios de `resources/views/vendor` e > 0; o mutante devolve 0 |

---

## Regra R5 — a varredura só roda na árvore do kit

> `P-04` · perfil **padrão** · técnica: **rastreio de efeito** — rodou na árvore / pulou declarado fora dela

```gherkin
  Regra: fora da árvore do kit a varredura de resources/views/vendor pula, com motivo

    # Procedimento (não vira `it()`): executado pela sessão, resultado no `03`. A regressão automatizada é o CT-13.
    Cenário: [CT-09] num projeto instalado a varredura é reportada como pulada, com motivo
      Dado a extração do git archive desta versão, com uma pasta de resources/views/vendor publicada e editada pelo projeto, fora da lista
      Quando o mantenedor roda os casos CT-06, CT-07 e CT-08 nela com relatório junit
      Então os três casos constam como pulados, com motivo que cita os publishes do próprio projeto
      # Na árvore do kit, os três rodam com asserções: é o próprio verde de CT-06…CT-08 (ADV-20: um ambiente por cenário)

    Cenário: [CT-13] a guarda da árvore está declarada no fonte dos casos da árvore real
      Dado o fonte de tests/Kit/KitUpdateTest.php
      Quando o mantenedor procura a declaração dos casos CT-06, CT-07 e CT-08
      Então cada um pula com "! naArvoreDoKit()" e um motivo não vazio
      E nenhum deles usa a forma "expect(true)->toBeTrue(); return;" como guarda
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M16 | guarda invertida (`skip(fn () => naArvoreDoKit())`) | CT-13 e CT-06…CT-08 | o fonte não contém `! naArvoreDoKit()` na guarda (CT-13), e na árvore do kit os três casos saem `skipped` em vez de `passed` |
| M17 | sem guarda: a varredura roda no projeto instalado e acusa os publishes do projeto (P-04) | CT-09, CT-13 | na extração, com a pasta editada pelo projeto, o junit marca `skipped`; o mutante marca `failed` citando essa pasta (ADV-15); o fonte não tem a guarda (CT-13) |
| M18 | guarda no padrão do vizinho, `expect(true)->toBeTrue(); return;` — verde por ausência, sem motivo | CT-09, CT-13 | na extração o junit marca `skipped` com motivo; o mutante marca `passed`; o fonte contém a forma proibida (CT-13) |

Matador **procedural** (CT-09): a execução de CT-06…CT-08 na extração com `--log-junit` (o mesmo procedimento da simulação do cenário 1 antes da tag), com o resultado colado no `03`. O teto de pulados da `Validação antes da tag` do CHANGELOG sobe em 3, decomposto por arquivo (`.ai/rules/testes.md` §Caso que lê `docs/`…). O CT-13 é o matador **de regressão** (ADV-19): um `it()` que lê o fonte do próprio arquivo, no padrão dos meta-casos do kit (ex.: o CT-11 de `SiteDeDocumentacaoTest`, que confere a forma do `skip`).

---

## Regra R6 — as entradas novas têm a forma que `caminhosDeclaradosEm()` lê

> `RQ-01` (a entrada precisa chegar ao `kit:update` que lê o fonte do destino) · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: o leitor do fonte extrai desta versão exatamente a lista da constante

    Cenário: [CT-10] o fonte desta versão produz a mesma lista que a constante, entradas de resources/views/vendor incluídas
      Dado o fonte de app/Console/Commands/KitUpdate.php desta versão
      Quando o kit:update extrai dele a lista de caminhos declarada
      Então a lista extraída é igual à lista da constante, na mesma ordem
```

Materialização: **caso existente** `extrai do fonte desta versão exatamente a lista da constante — a forma textual é contrato` (`tests/Kit/KitUpdateTest.php`); o executor o renomeia com o prefixo de ID do CT-10 ou declara no índice "fundido no caso existente". Não há `it()` novo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M19 | entrada nova em forma que o leitor não reconhece (aspas duplas, duas entradas na mesma linha, indentação diferente) | CT-10 | `caminhosDeclaradosEm($fonte)` sem a entrada nova ≠ `caminhosDoKit()` com ela; `toBe` reprova |
| M20 | entrada nova depois do fim da constante (noutra constante do arquivo, ex.: a de relatório) | CT-10 e CT-01 | o leitor para na primeira constante; a lista extraída ou a constante não contém a pasta, e `estaCoberto(media.blade.php)` vira `false` |

---

## Regra R7 — o CHANGELOG registra a correção

> sem `RQ` (convenção de entrega; ver Fronteira) · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: a correção do #148 está registrada no CHANGELOG

    Cenário: [CT-11] o CHANGELOG registra a entrega de resources/views/vendor e o issue #148
      Dado o CHANGELOG.md inteiro da árvore do kit
      Quando o mantenedor procura a correção
      Então o arquivo cita "#148" e "resources/views/vendor"
```

Pula fora da árvore com motivo: `CHANGELOG.md` é `export-ignore` (`.gitattributes:21`). Afirma sobre o **arquivo inteiro**, nunca a seção do topo (`.ai/rules/testes.md` §Asserção sobre a seção do topo do CHANGELOG).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | correção entregue sem entrada no CHANGELOG | CT-11 | o arquivo contém `#148`; o mutante não |
| M22 | entrada que cita só a lock-screen, sem dizer que `resources/views/vendor` passou a ser entregue | CT-11 | o arquivo contém `resources/views/vendor`; o mutante não |
| M23 | caso lendo só a seção do topo: passa hoje e expira na próxima entrega | — sem matador | não há cenário que divirja hoje (ADV-16): a leitura do arquivo inteiro é regra de `.ai/rules/testes.md` §Asserção sobre a seção do topo do CHANGELOG, conferida na revisão do diff |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: sem rota, recurso nem usuário (superfície: "Sem superfície de UI") | — |
| Autorização exercida na ação | não se aplica: sem policy nem permissão | — |
| Idempotência | não se aplica: a varredura só lê; o `kit:update` já existente não muda de mecanismo | — |
| Concorrência | não se aplica: nenhum contador nem escrita concorrente | — |
| Fronteira no ponto de entrada | CT-01, CT-10 (a entrada da constante é o ponto de entrada da rota `kit:update`) | Lista do kit |
| Domínio condicionado | CT-02 linha `dois` (a mesma view relativa vale conforme o pacote) | Classificação por fixture |
| Cardinalidade 0 / 1 / N | 0 pastas examinadas: CT-08 · pasta com 0 arquivos: CT-02 `vazia` · 1 view: CT-02 `cru`/`byte` · N views com mistura: CT-02 `mista` (3), CT-06 | Classificação por fixture · Árvore real do kit |
| Ausente ≠ null ≠ vazio | CT-02 linhas `sopar` (pacote ausente), `extra` (pacote presente, arquivo ausente nele), `vazia` (pasta sem arquivo); CT-12 | Classificação por fixture |
| Texto: fim de linha, espaço | CT-02 linhas `crlf`, `crlf-inv`, `byte`, `borda` | Classificação por fixture |
| Plataforma: separador de caminho no Windows | CT-08 (raiz montada errada ⇒ zero pastas) | Árvore real do kit |
| Estado × operação de escrita | não se aplica: sem entidade com ciclo de vida | — |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: nada depende de relógio | — |
| Unicidade + soft delete | não se aplica: sem model | — |
| CRUD combinado | não se aplica: sem model | — |
| Mass assignment | não se aplica: sem formulário nem payload | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| Superfície Livewire / estado do framework | não se aplica: sem página, widget nem componente | — |
| Escopo com discriminante nulo | não se aplica: sem filtro de escopo | — |
| Saída do estado de erro | CT-03 (a reprovação declara as duas saídas: `vendor:publish` ou `CAMINHOS_DO_KIT`) | Classificação por fixture |
| **Teste que viaja lendo arquivo que não viaja** (linha do projeto) | CT-09, CT-11 | Árvore real do kit · CHANGELOG |
| Projeto antigo + `kit:update` real (RQ-05 ponta a ponta) | CT-14 simula localmente a entrega da entrada-pasta a uma árvore sem ela (lista unida com destino antigo + `git archive`); o cenário 3 **sobre a tag publicada** continua Fora de Escopo do `00`. O `LogoDarkModeTest` CT-16 é verde na árvore do kit antes e depois da correção e por isso não discrimina (ADV-21) | Lista do kit |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a view da lock-screen está coberta | R1 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M1, M2, M20 |
| CT-02 | classificação por conteúdo (14 partições) | R2 | EP | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M3, M4, M5, M6, M7, M24, M25, M26, M27, M28 |
| CT-03 | drift do pacote acusado com as duas saídas e só os divergentes | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M9, M9c |
| CT-04 | publish cru coberto reprova (3 formas de entrada), saída de remover | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M8, M29, M9b |
| CT-05 | lista coerente aprova | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M10 |
| CT-06 | pasta autoral coberta inteira, com sonda | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M11, M12 |
| CT-07 | nenhuma view crua coberta | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M13, M14 |
| CT-08 | todas as pastas da árvore real examinadas (> 0) | R4 | controle positivo | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M15 |
| CT-09 | pula fora da árvore com motivo (extração com publish do projeto) | R5 | rastreio de efeito | Árvore real do kit | procedimento | fundido em CT-13 como regressão; o procedimento junit sobre CT-06…CT-08 na extração é evidência da `## Verificação Final` do `03`, não `it()` | M17, M18 |
| CT-10 | o leitor do fonte produz a lista da constante | R6 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` (caso existente) | M19, M20 |
| CT-11 | CHANGELOG cita #148 e resources/views/vendor | R7 | EP | CHANGELOG | unit de regra | `tests/Kit/KitUpdateTest.php` | M21, M22 |
| CT-12 | autoral sem pacote, fora da lista, reprova | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M30 |
| CT-13 | guarda `! naArvoreDoKit()` com motivo declarada no fonte dos casos da árvore real | R5 | meta-caso sobre o fonte | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M16, M17, M18 |
| CT-14 | a entrada-pasta entrega o arquivo aninhado por git archive numa árvore sem ele | R1 | entrega simulada | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M31 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| `caminhosUnidos()` com a lista de um destino v0.42.0 (sem as entradas novas) contém `filament-auth-designer` | a união já é provada genericamente pelos casos existentes (`caminho que só esta versão cobre não se perde…`); com CT-01 verde, nenhum mutante desta correção sobrevive a eles |
| um cenário por pasta real (12 linhas com a classe esperada) | congelaria a medição do step 3 como oráculo e reprovaria a drift legítima de P-05; CT-06/CT-07 cobrem as mesmas pastas pelo conteúdo |
| BOM no início da view como partição | P-03 normaliza só fim de linha; BOM é diferença de conteúdo e cai na partição `byte`, sem mutante novo |

## Sem CT-B

- Motivo: "Sem superfície de UI" no plano. A correção é uma constante e um teste; a afirmação de cada cenário é sobre arquivos e a lista do kit. O efeito na tela (o par claro/escuro da lock-screen) já tem o `LogoDarkModeTest` CT-16, que não é desta wiki. Nenhuma costura `browser`, logo o `05` não existe.

## Perguntas devolvidas

- Q7 (raia desenho, afeta P-03) — ver `## Fronteira com o Plano`. Nenhuma pergunta de raia requisito.

## Revisão adversarial

- Critério da derivação: não disparada (nenhuma área com perfil completo nem Impacto 3). **Decisão da sessão, 2026-10-06**: despachada mesmo assim ao `fw-adversario-ct` — a classe de defeito (arquivo que viaja por uma rota e não pela outra) já ocorreu cinco vezes no kit, e a independência custa um despacho.
- Resultado: **21 achados (6 bloqueantes, 15 cosméticos) — 19 aplicados, 2 rejeitados**, 2026-10-06.

| ID | Achado | Destino |
|---|---|---|
| ADV-01 [B] | classificador amarra pasta ao pacote pelo nome | CT-02 `outro`, M24, Setup Global |
| ADV-02 [B] | iteração parte do pacote: arquivo novo do kit nunca é olhado | CT-02 `extra`, M25 |
| ADV-03 [B] | listagem não recursiva | CT-02 `aninhada`, M26 |
| ADV-04 | só `*.blade.php` | CT-02 `svg`, M27 |
| ADV-05 | normalização só do lado do kit | CT-02 `crlf-inv`, M28 |
| ADV-06 | pasta vazia indefinida | CT-02 `vazia` → cru (nada autoral, nada a entregar) |
| ADV-07 | "listada" por igualdade exata | CT-04 vira Esquema (exata / ancestral / arquivo), M29 |
| ADV-08 | sem par × fora da lista ausente da tabela | CT-12, M30 |
| ADV-09 | caixa do nome no Windows | **rejeitado**: NTFS não distingue caixa, então a fixture com dois nomes que só diferem em caixa não existe no Windows e o cenário teria resultado por plataforma por construção; o kit não tem par assim (`git ls-files resources/views/vendor` sem colisão de caixa) |
| ADV-10 | leitor da versão de origem | **rejeitado**: esta correção não toca `caminhosDeclaradosEm()` (só a constante), e o caso existente `FONTE_ANTIGA_DO_KIT_UPDATE` já prova o leitor sobre a forma antiga; a entrada nova tem a mesma forma textual das 79 anteriores |
| ADV-11 [B] | M7 não morria pela ordem do glob | CT-02 `painel` + `painel2` |
| ADV-12 [B] | `trim` não morria | CT-02 `borda` |
| ADV-13 [B] | "maioria"/"última" não morriam | CT-02 `mista` com 3 views |
| ADV-14 | CT-08 congelava a medição | CT-08 só conta pastas (> 0); partições na fixture |
| ADV-15 | extração sem publish do projeto | CT-09 com pasta editada pelo projeto; M17 |
| ADV-16 | M23 não morre hoje | M23 → sem cenário matador (regra de `.ai/rules`) |
| ADV-17 | CT-04 aceita conselho errado | CT-04 afirma "remover" e não "liste"; M9b |
| ADV-18 | CT-03 não distingue "todos os arquivos" | CT-03 com `outro.blade.php` idêntico; M9c |
| ADV-19 | CT-09 só procedural | CT-13 (meta-caso sobre o fonte) |
| ADV-20 | CT-09 com dois "Quando" | CT-09 só a extração |
| ADV-21 | RQ-05 sem cenário discriminante | CT-14 (entrega simulada por `git archive`), M31 |
