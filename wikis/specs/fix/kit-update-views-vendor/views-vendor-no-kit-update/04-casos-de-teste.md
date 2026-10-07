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
| Comparação da entrada nova no `kit:update` (R8) *(alterado em 2026-10-06: CR-01/RD-01)* | 2 | 2 | 4 | padrão |

- Probabilidade 2 na classificação: comparação de conteúdo com fim de linha, pares por caminho relativo e o mesmo nome em mais de um pacote. Impacto 2: o defeito de cada direção é retrabalho manual (view que não chega; customização do projeto sobrescrita, recuperável pelo git). Nenhuma área com Impacto 3 nem perfil completo pelo critério da derivação; a sessão despachou a revisão adversarial mesmo assim (ver `## Revisão adversarial`), e ela devolveu 21 achados — 19 aplicados nesta versão *(alterado em 2026-10-06)*.
- Técnicas aplicadas: EP (classificação por conteúdo, partições isoladas; conjuntos de caminhos), tabela de decisão (lista × autoria; status do diff × origem), rastreio de efeito negativo (pulo declarado fora da árvore), procedimento ponta a ponta do comando real (CT-09, CT-17).
- R8 (P-06): Probabilidade 2 — integra com o `git diff` e com a leitura da lista da origem; Impacto 2 — o defeito de uma direção é o override que nunca chega (o próprio #148), o da outra é acusar como "modificado" a edição do projeto em pasta antiga da lista (retrabalho manual, recuperável). *(alterado em 2026-10-06: CR-01/RD-01)*
- Cenários: 18 · Regras: 9 · Mutantes previstos: 46 · Sem matador: 1 *(alterado em 2026-10-06: CR-01/RD-01, CR-07, RD-05, RD-06)*
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho); recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a constante `KitUpdate::CAMINHOS_DO_KIT` (entradas novas), a varredura de `resources/views/vendor` em `tests/Kit/KitUpdateTest.php`, a entrada do `CHANGELOG.md` | CT-01, CT-06…CT-08, CT-11, CT-14 |
| F | entregar override autoral pelo `kit:update`; classificar pasta como autoral ou publish cru; reprovar divergência lista × autoria antes da tag | CT-01…CT-08 |
| D | 12 pastas reais, partições autoral (ao menos uma view diferente) e cru (todas idênticas); pasta mista (3 views); arquivo do kit sem par com o pacote instalado; view sem pacote nenhum; mesmo caminho relativo em dois pacotes, em ambas as ordens; pasta com nome diferente do pacote; subpasta; arquivo não-blade; pasta vazia; fim de linha CRLF × LF nas duas direções; espaço de borda | CT-02, CT-06…CT-08 |
| I | `php artisan kit:update` (lê a lista do fonte do destino por `caminhosDeclaradosEm()` e une com a desta versão; compara a entrada nova na lista tag de destino × árvore do projeto, P-06 *(alterado em 2026-10-06: CR-01/RD-01)*); a suíte `kit` antes da tag | CT-01, CT-10, CT-15…CT-17 |
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
| R8 — caminho que está na lista do destino e não estava na da origem é comparado tag de destino × árvore do projeto, só ele; sem lista da origem, nada muda; e arquivo que o projeto não tem é "novo no kit" (P-08) *(alterado em 2026-10-06: CR-01/RD-01; QA-03)* | comparação do `kit:update` (padrão) | P-06, P-08, RQ-01, RQ-05 | EP (conjuntos de caminhos) + tabela de decisão (status × origem) + procedimento ponta a ponta | CT-15, CT-16, CT-17 |
| R9 — as dez views autorais mudam nesta release, para a classe antiga do `kit:update` entregá-las na primeira rodada | entrega (mínimo) | P-07 (RQ-01, RQ-02, RQ-05) | rastreio de efeito (diff tag anterior → HEAD) | CT-18 *(alterado em 2026-10-06: P-07, escrito pela sessão — cenário procedural, como CT-17)* |

- RQ-05 — coberta pela entrega (CT-01), pela extração da entrada-pasta (CT-14, *alterado em 2026-10-06: ADV-21*) e pela comparação da entrada nova a quem já está numa versão que tinha a pasta fora da lista (CT-15…CT-17) *(alterado em 2026-10-06: CR-01/RD-01, CR-08)*. O `LogoDarkModeTest` CT-16 existente é verde na árvore do kit antes e depois da correção e não discrimina. O cenário 3 **sobre a tag publicada** continua Fora de Escopo do `00`.
- Q1, Q2 e Q3 abertas no `00` não bloqueiam: Q1/Q2 estão vigentes como P-01/P-02 (a direção falha fechado) e Q3 é a tag. Nenhuma `RQ` está `aberta — Qn`.
- R2 escala de "mínimo" para EP com 7 partições isoladas: a classificação é o ponto onde mora a direção errada das duas (RQ-02 × RQ-04), e um `Esquema do Cenário` conta como 1.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Lista do kit | R1, R6 | unit de regra | existente — `tests/Kit/KitUpdateTest.php` (`estaCoberto()`, `caminhosDoKit()`, caso `extrai do fonte desta versão…`) | a afirmação é sobre o valor da constante e do leitor estático; nenhum banco nem rota | sessão (desenvolvedor), 2026-10-06 |
| Classificação por fixture | R2, R3 | unit de regra | nova, no mesmo arquivo — a classificação e a varredura chamáveis com **duas raízes** (views do kit, views do vendor) e **uma lista**, apontadas para um diretório temporário. Nenhuma costura existente serve: a varredura atual só olha a árvore real, onde as partições "difere só em CRLF", "sem par" e "drift do pacote" podem não existir | sem fixture, os mutantes de normalização e de pasta mista dependem do checkout de quem roda | sessão (desenvolvedor), 2026-10-06 |
| Árvore real do kit | R4, R5 | unit de regra | existente — o caso `cobre todo o código do kit…` de `tests/Kit/KitUpdateTest.php`, que hoje pula `resources/views/vendor/` com `continue` | a afirmação é sobre os arquivos do repositório × a constante; guarda `naArvoreDoKit()` de `tests/Pest.php` | sessão (desenvolvedor), 2026-10-06 |
| Diff da entrada nova *(alterado em 2026-10-06: CR-01/RD-01)* | R8 | unit de regra | existente — `tests/Kit/KitUpdateTest.php` (os dois estáticos públicos de `KitUpdate` chamados direto, sem git nem árvore) | a afirmação é sobre conjuntos de caminhos e rótulos calculados; o comando real ponta a ponta é procedimento (CT-17), como o CT-09 | sessão (desenvolvedor), 2026-10-06 |
| CHANGELOG | R7 | unit de regra | existente — padrão do caso `documenta a lista do destino…` de `tests/Kit/KitUpdateTest.php` (CHANGELOG inteiro + `->skip(fn (): bool => ! naArvoreDoKit(), …)`) | leitura de arquivo da árvore | sessão (desenvolvedor), 2026-10-06 |

`tests/Kit` é ligado ao `TestCase` da aplicação com `RefreshDatabase` (`tests/Pest.php`, `pest()->extend(TestCase::class)…->in('Kit')`): "unit de regra" aqui roda com container, sem tocar banco. Helper usado só por `KitUpdateTest.php` fica nele (`.ai/rules/testes.md` §Helper de teste).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `estaCoberto()`, `caminhosDoKit()`, `caminhosDeclaradosEm()`, `caminhosUnidos()`, `naArvoreDoKit()` | escolha de implementação já existente | detalhe do cenário |
| a forma textual `        'caminho',` lida por regex | escolha de implementação; o oráculo é a **igualdade** entre o que o leitor extrai e a constante, não a forma | detalhe de CT-10 |
| contagem medida no step 3 (12 pastas; 5 autorais; 7 cruas; quais são) | fato do ambiente, não requisito: virar `Então` congelaria a medição e reprovaria a P-05 legítima | só no `Dado` e no Setup Global (existência das duas partições); o oráculo é a classificação por conteúdo |
| entrada do `CHANGELOG.md` | nenhuma `RQ` pede o registro; é convenção de entrega do kit (`.ai/rules/testes.md` §CHANGELOG) pedida pela sessão | mantido como CT-11, mínimo, origem declarada sem `RQ`; candidato a corte se a sessão discordar |
| as palavras exatas da mensagem de falha | o requisito só fixa o conteúdo: pasta, arquivos e as duas saídas (P-05) | CT-03 afirma presença do nome da pasta, do caminho do arquivo, de `CAMINHOS_DO_KIT` e de `vendor:publish`, não a frase. CT-04 (linha `ancestral`) e CT-12 afirmam o **trecho** da saída certa daquela célula ("estreite a entrada ancestral…", "apague a pasta órfã…"), fixado pela revisão do diff como saída acionável da P-05 — não a frase inteira *(alterado em 2026-10-06: RD-05)* |
| nomes `caminhosNovosNaLista()`, `rotularDiff()` e a composição em `arquivosAlterados()` *(alterado em 2026-10-06: CR-01/RD-01)* | escolha de implementação (recebida da sessão, não do plano lido) | detalhe de CT-15/CT-16; o oráculo é P-06: entrada nova = só no destino; "novo no kit" e "modificado" sem origem; arquivo só do projeto ignorado. O rótulo "removido do kit" com origem é o comportamento de hoje do `kit:update`, mantido como regressão |

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

    # alterado em 2026-10-06: CR-08 — reescopado; a "lista antiga" unida era comutativa e não exercitava o diff entre tags (ver Cogitado e cortado)
    Cenário: [CT-14] a entrada-pasta extrai o arquivo aninhado
      Dado uma árvore temporária sem "resources/views/vendor/filament-auth-designer"
      E a lista de caminhos do kit desta versão
      Quando o mantenedor extrai desta versão, por git archive, as entradas da lista que começam por "resources/views/vendor/" para a árvore temporária
      Então "resources/views/vendor/filament-auth-designer/components/partials/media.blade.php" existe na árvore temporária
      E o conteúdo dele é o do kit
      # Pula fora da árvore do kit: precisa do git do kit (ADV-21). O mecanismo real de quem atualiza — o diff entre tags — é R8 (CT-15…CT-17)
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a entrada `resources/views/vendor/filament-auth-designer` não entra na constante (correção só no `.gitattributes`, na doc ou num comentário da constante) | CT-01 | `estaCoberto(media.blade.php)` é `true`; o mutante devolve `false` |
| M2 | entrada com o caminho errado: `resources/views/filament-auth-designer` (sem `vendor/`) ou `…/filament-auth-design` | CT-01 | o prefixo da entrada não casa com o caminho do `Dado`; `estaCoberto` devolve `false` |
| M31 | entrega não recursiva da entrada-pasta (`git archive` só da raiz, ou cópia sem subpastas): a extração perde o arquivo aninhado *(alterado em 2026-10-06: CR-08)* | CT-14 | o arquivo existe na árvore temporária com o conteúdo do kit; o mutante não o extrai. *(alterado em 2026-10-06: a variante "entrada por arquivo" saiu daqui — ela ainda extrai o próprio arquivo e quem a mata é a sonda do CT-06, medido no mutante manual C)* |

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
| M3 | "existe no vendor" no lugar de comparar conteúdo | CT-02 linhas `byte`, `mista`, `borda` *(alterado em 2026-10-06: QA-06 — citava `final`, que virou `borda`)* | esperado `autoral`; o mutante classifica `cru` (as três têm par no vendor) |
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

    # alterado em 2026-10-06: RD-05 — a saída depende da forma da entrada
    Esquema do Cenário: [CT-04] publish cru coberto pela lista reprova, por qualquer forma de entrada, com a saída certa para a forma
      Dado uma árvore de fixture com a pasta "crua" idêntica à do pacote "acme/crua"
      E a lista de caminhos do kit com "<entrada>"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "crua" e a saída "<saida>"
      E a mensagem não sugere listá-la

      Exemplos:
        | entrada                                        | saida                                                    | # forma            |
        | resources/views/vendor/crua                    | remover de CAMINHOS_DO_KIT                               | exata              |
        | resources/views/vendor                         | estreite a entrada ancestral para as pastas autorais     | ancestral (ADV-07, RD-05) — remover a entrada levaria junto toda pasta autoral |
        | resources/views/vendor/crua/painel.blade.php   | remover de CAMINHOS_DO_KIT                               | arquivo (ADV-07)   |

    Cenário: [CT-05] lista coerente com a autoria aprova
      Dado uma árvore de fixture com a pasta autoral "editada" e a pasta "crua" idêntica ao pacote
      E a lista de caminhos do kit com "resources/views/vendor/editada" e sem "resources/views/vendor/crua"
      Quando a varredura confere a lista contra a árvore
      Então a varredura aprova, sem nenhuma pasta acusada

    # alterado em 2026-10-06: RD-05 — sem pacote não há o que republicar
    Cenário: [CT-12] pasta autoral sem pacote instalado e fora da lista reprova, oferecendo listar ou apagar a órfã
      Dado uma árvore de fixture com a pasta "sopar" com uma view sem par em pacote nenhum
      E a lista de caminhos do kit sem "resources/views/vendor/sopar"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "sopar", "CAMINHOS_DO_KIT" e "apague a pasta órfã, se o pacote saiu do composer.json"
      E a mensagem não cita "vendor:publish"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M8 | a varredura só confere uma direção (autoral fora da lista) e ignora cru listada — a direção que sobrescreve customização do projeto | CT-04 | esperado "reprova" citando `crua`; o mutante aprova |
| M9 | mensagem genérica, sem os arquivos ou com uma saída só | CT-03 | a mensagem contém `painel.blade.php`, `CAMINHOS_DO_KIT` **e** `vendor:publish`; o mutante falta ao menos um |
| M10 | "toda pasta de `resources/views/vendor` fora da lista reprova", sem classificar (o `continue` trocado por exigência total) | CT-05 | esperado "aprova" com `crua` fora da lista; o mutante acusa `crua` |
| M29 | "coberta" decidida por igualdade exata da entrada com `resources/views/vendor/{pasta}` | CT-04 linhas `ancestral` e `arquivo` | esperado "reprova" citando `crua`; o mutante não vê a cobertura e aprova |
| M30 | a varredura pula (`continue`) pasta sem pacote instalado na direção autoral × fora da lista | CT-12 | esperado "reprova" citando `sopar`; o mutante aprova |
| M9b | mensagem da direção cru × coberta reaproveita a de autoral × fora ("liste em `CAMINHOS_DO_KIT`") | CT-04 | a mensagem contém a `<saida>` da linha ("remover de CAMINHOS_DO_KIT" nas linhas `exata` e `arquivo`, "estreite a entrada ancestral…" na `ancestral`) e não "liste"; o mutante falha nas duas (ADV-17; *(alterado em 2026-10-06: RD-05)*) |
| M39 | mensagem única "remover de CAMINHOS_DO_KIT" para toda forma de entrada: na ancestral, mandaria apagar `resources/views/vendor` da lista e levaria junto toda pasta autoral *(alterado em 2026-10-06: RD-05)* | CT-04 linha `ancestral` | a mensagem contém "estreite a entrada ancestral para as pastas autorais"; o mutante não |
| M40 | mensagem de autoral × fora sem pacote reaproveita a de autoral × fora com pacote, oferecendo `vendor:publish` (não há o que republicar) e omitindo apagar a órfã *(alterado em 2026-10-06: RD-05)* | CT-12 | a mensagem contém "apague a pasta órfã, se o pacote saiu do composer.json" e não contém "vendor:publish"; o mutante falha nas duas |
| M9c | mensagem lista todos os arquivos da pasta, não os divergentes | CT-03 | a mensagem não contém `outro.blade.php`; o mutante contém (ADV-18) |

Estouro do teto do perfil padrão (5) em R3: M39 e M40 vêm da revisão do diff (RD-05), a mesma exceção do mutante trazido pela revisão adversarial — achado medido, não enchimento *(alterado em 2026-10-06: RD-05)*.

---

## Regra R4 — na árvore real do kit, toda pasta autoral está coberta e nenhuma crua está

> `RQ-02`, `RQ-03`, `RQ-04`, `P-01`, `P-02` · perfil **padrão** · técnica: **EP** sobre a árvore real (as duas partições existem nela — Setup Global) + controle positivo

```gherkin
  Regra: a lista de caminhos do kit desta versão casa com a autoria das pastas de resources/views/vendor

    # alterado em 2026-10-06: RD-06 — só arquivo rastreado decide a classe
    Cenário: [CT-06] toda pasta autoral está coberta inteira, inclusive por arquivo que ainda não existe
      Dado os arquivos rastreados de resources/views/vendor (git ls-files) e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então toda view de toda pasta autoral está coberta por CAMINHOS_DO_KIT
      E o caminho "novo-arquivo-sonda.blade.php" dentro de cada pasta autoral também está coberto

    # alterado em 2026-10-06: RD-06
    Cenário: [CT-07] nenhuma view de pasta publish cru está coberta
      Dado os arquivos rastreados de resources/views/vendor (git ls-files) e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então nenhuma view de pasta classificada como publish cru está coberta por CAMINHOS_DO_KIT

    # alterado em 2026-10-06: CR-07 — referência independente e o objeto da RQ-01 classificado
    Cenário: [CT-08] a varredura examina todas as pastas da árvore real e classifica a da lock-screen como autoral
      Dado a árvore do kit com as pastas de resources/views/vendor e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então o número de pastas classificadas é o número de diretórios de resources/views/vendor com arquivo rastreado, contados por listagem independente da varredura (scandir ou Finder, nunca o mesmo glob), e é maior que zero
      E a pasta "filament-auth-designer" está classificada como "autoral"
      # A existência das duas partições é controle positivo da fixture (CT-05), não da árvore real: afirmá-la aqui congelaria a medição do step 3 e reprovaria a saída recomendada da Q1 (ADV-14). Por isso nenhuma pasta é afirmada crua (nem pulse): só a da RQ-01 tem classe fixada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | uma pasta autoral esquecida na lista (só `filament-auth-designer`, como sugere o issue, sem a auditoria do resto) | CT-06 | a lista de pastas autorais descobertas é `[]`; o mutante lista as autorais que faltaram |
| M12 | entradas por arquivo em vez de pasta (`command-center` só com as 3 views editadas; `filament-auth-designer/components/partials/media.blade.php`) | CT-06 | a view idêntica de `command-center` e a sonda `novo-arquivo-sonda.blade.php` estão cobertas; o mutante deixa as duas de fora |
| M13 | uma pasta crua acrescentada à lista (ex.: `pulse`, por "auditoria" que lista tudo que existe) | CT-07 | a lista de views cruas cobertas é `[]`; o mutante devolve as views de `pulse` |
| M14 | `resources/views/vendor` inteiro na lista | CT-07 | idem: toda view crua fica coberta e a lista deixa de ser `[]` |
| M15 | raiz das views ou do vendor montada errada (separador `\` no Windows, `base_path` duplicado) e a varredura examina zero pastas — CT-06 e CT-07 verdes por vazio | CT-08 | pastas classificadas = diretórios contados pela referência independente e > 0; o mutante devolve 0 |
| M37 | a varredura descobre as pastas pelo padrão das views na raiz (`resources/views/vendor/*/*.blade.php`): pasta cujas views vivem só em subpasta — `filament-auth-designer/components/partials` — nunca é examinada; uma referência pelo mesmo glob concordaria com o erro *(alterado em 2026-10-06: CR-07)* | CT-08 | contagem da varredura = contagem por `scandir`/`Finder` e `filament-auth-designer` classificada `autoral`; o mutante conta uma pasta a menos e não classifica `filament-auth-designer` |
| M38 | a varredura lista o disco (glob/Finder) em vez dos arquivos rastreados: arquivo não rastreado criado localmente em pasta crua muda a classe dela *(alterado em 2026-10-06: RD-06)* | CT-06 e CT-07 — mutante manual D | criar `resources/views/vendor/pulse/local.blade.php` sem `git add` e rodar CT-06 e CT-07: com `git ls-files` os dois seguem verdes; o mutante vê `local.blade.php` sem par, classifica `pulse` como autoral e CT-06 reprova acusando `pulse` fora da lista. Manual porque a fixture alteraria a árvore real; resultado colado no `03` |

Estouro do teto do perfil padrão (5) em R4: M37 e M38 vêm da revisão do diff (CR-07, RD-06), a mesma exceção do mutante trazido pela revisão adversarial *(alterado em 2026-10-06: CR-07, RD-06)*.

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

Matador **procedural** (CT-09): a execução de CT-06…CT-08 na extração com `--log-junit` (o mesmo procedimento da simulação do cenário 1 antes da tag), com o resultado colado no `03`. O teto de pulados da `Validação antes da tag` do CHANGELOG sobe em 6 — CT-06, CT-07, CT-08, CT-11, CT-14 e CT-18 pulam fora da árvore *(alterado em 2026-10-06: QA-05 — dizia 3)* — decomposto por arquivo (`.ai/rules/testes.md` §Caso que lê `docs/`…). O CT-13 é o matador **de regressão** (ADV-19): um `it()` que lê o fonte do próprio arquivo, no padrão dos meta-casos do kit (ex.: o CT-11 de `SiteDeDocumentacaoTest`, que confere a forma do `skip`).

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

## Regra R8 — a entrada nova na lista é comparada tag de destino × árvore do projeto *(alterado em 2026-10-06: CR-01/RD-01)*

> `P-06`, `RQ-01`, `RQ-05` · perfil **padrão** · técnica: **EP** sobre conjuntos de caminhos (CT-15) + **tabela de decisão** status do `git diff --name-status` × há origem (CT-16) + **procedimento** do comando real (CT-17). Células da tabela: com origem, `A` = novo no kit, `M` = modificado, `D` = removido do kit; sem origem, `D` = novo no kit, `M` = modificado, `A` = ignorado (só o projeto tem); outra letra = modificado, nas duas colunas · *(alterado em 2026-10-06: QA-12 — células acrescentadas pela P-08: `M` + ausente no projeto = "novo no kit"; `M` + presente = "modificado"; qualquer outro status ignora o callable, inclusive `R`/`C`/`T`)*

```gherkin
  Regra: o caminho que entrou na lista do destino e não estava na da origem é comparado contra a árvore do projeto, e só ele

    Esquema do Cenário: [CT-15] entrada nova é a que está na lista do destino e não na da origem
      Dado a lista do destino <destino>
      E a lista da origem <origem>
      Quando o kit:update separa as entradas novas na lista
      Então as entradas novas são <novas>, como lista indexada a partir de 0, na ordem do destino

      Exemplos:
        | destino                                                            | origem                                    | novas                                    | # partição                                                         |
        | ["app", "resources/views/vendor/fad", "config/kit.php", "lang/x"]  | ["app", "config/kit.php"]                 | ["resources/views/vendor/fad", "lang/x"] | entradas a mais, não contíguas — mata sem reindexar e a ordem      |
        | ["app", "config/kit.php"]                                          | ["app", "config/kit.php"]                 | []                                       | listas iguais                                                      |
        | ["app", "resources/views/vendor/fad"]                              | []                                        | []                                       | origem não lida: "não pude ler" não vira "tudo é novo"             |
        | ["app", "config/kit.php"]                                          | ["config/kit.php", "app", "routes"]       | []                                       | presentes nas duas, com a mesma forma e em outra ordem, não são novas; "routes" só na origem não entra — mata origem − destino |

    # P-08 (QA-03): com `existeNoProjeto`, status M de arquivo que o projeto não tem vira "novo no kit"; com o arquivo presente, continua "modificado"; sem o callable, nada muda. Três linhas a mais nos Exemplos (M + ausente, M + presente, D + ausente) e, pelo QA-08, a linha `R100` (renome segue "modificado"). *(alterado em 2026-10-06, escrito pela sessão; QA-12 — dizia "duas")*
    Esquema do Cenário: [CT-16] a saída do git diff --name-status vira rótulo conforme haja origem
      Dado a saída real do "git diff --name-status" <saida>
      E <origem>
      E <existe no projeto> *(alterado em 2026-10-06: P-08 — coluna nova; "—" = sem o callable, como antes)*
      Quando o kit:update rotula a saída
      Então os rótulos por caminho, em ordem de caminho, são <rotulos>

      Exemplos:
        | saida                                                    | origem         | existe no projeto | rotulos                                                                           | # célula                                               |
        | "M\tp/b.php\nA\tp/a.php\nD\tp/c.php\n"                   | há origem      | —                 | {"p/a.php": "novo no kit", "p/b.php": "modificado", "p/c.php": "removido do kit"} | com origem: A, M, D; entrada fora de ordem             |
        | "D\tp/falta.php\nM\tp/dif.php\nA\tp/so-projeto.php\n"      | não há origem  | —                 | {"p/dif.php": "modificado", "p/falta.php": "novo no kit"}                         | sem origem: D, M, A ignorado (P-06)                    |
        | "T\tp/link.php\n\n"                                       | não há origem  | —                 | {"p/link.php": "modificado"}                                                      | outra letra; linha em branco final não vira caminho "" |
        | ""                                                       | há origem      | —                 | {}                                                                                | saída vazia                                            |
        | "M\tresources/views/vendor/x/a.blade.php\n"              | há origem      | não               | {"resources/views/vendor/x/a.blade.php": "novo no kit"}                           | P-08: M + ausente ⇒ novo no kit (é o que --only-new aplica) |
        | "M\tresources/views/vendor/x/a.blade.php\n"              | há origem      | sim               | {"resources/views/vendor/x/a.blade.php": "modificado"}                            | P-08: M + presente ⇒ modificado                        |
        | "D\tresources/views/vendor/x/a.blade.php\n"              | há origem      | não               | {"resources/views/vendor/x/a.blade.php": "removido do kit"}                       | P-08: o callable só mexe em M — D com origem segue "removido" (mata "aplica a todo status") |
        | "R100\tp/old.php\tp/new.php\n"                          | há origem      | não               | {"p/old.php\tp/new.php": "modificado"}                                           | QA-08: renome segue "modificado" com a chave pré-existente (Q8); o callable não a transforma em "novo no kit" |

    # Procedimento (não vira `it()`): executado pela sessão, resultado no `03`. A regressão automatizada é CT-15/CT-16.
    Cenário: [CT-17] quem já está numa versão com a pasta fora da lista recebe o override como novo no kit
      Dado um projeto na v0.45.0 sem a pasta "resources/views/vendor/filament-auth-designer"
      E nesse projeto um arquivo de pasta que a lista da v0.45.0 já tinha, editado só no projeto e igual entre a v0.45.0 e a tag temporária
      Quando o mantenedor roda "php artisan kit:update --repo={repositório local do kit} --tag={tag temporária com a correção} --dry-run"
      Então a lista traz "resources/views/vendor/filament-auth-designer/components/partials/media.blade.php" como "novo no kit"
      E a lista não traz o arquivo editado só no projeto
      # Controle de discriminação: o mesmo comando contra uma tag temporária sem a correção não traz nenhum arquivo de "resources/views/vendor/filament-auth-designer" — o diff tag→tag dentro da pasta é vazio, porque a origem tem os mesmos arquivos que o destino
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M32 | diferença invertida: `array_diff(origem, destino)` | CT-15 linhas 1 e 4 | linha 1: esperado `["resources/views/vendor/fad", "lang/x"]`, o mutante dá `[]`; linha 4: esperado `[]`, o mutante dá `["routes"]` |
| M33 | `array_diff` sem reindexar (chaves 1 e 3 preservadas) | CT-15 linha 1 | `toBe` com `[0 => "resources/views/vendor/fad", 1 => "lang/x"]`; o mutante devolve `[1 => …, 3 => …]` |
| M34 | lista da origem vazia tratada como "tudo é novo" (`array_diff(destino, [])`) | CT-15 linha 3 | esperado `[]`; o mutante devolve a lista do destino inteira |
| M35 | sem origem, a tabela de rótulos é a mesma de com origem: `D` lido como "removido do kit" e `A` (só o projeto tem) como "novo no kit" | CT-16 linha 2 | esperado `{"p/dif.php": "modificado", "p/falta.php": "novo no kit"}`; o mutante dá `"p/falta.php": "removido do kit"` e acrescenta `"p/so-projeto.php"` |
| M45 | `rotularDiff` ignora o `callable` (ou o aplica a todo status): arquivo ausente com status M sai "modificado", e `--only-new` nunca o aplica | CT-16 (as três linhas de P-08) | esperado "novo no kit" para M + ausente, "modificado" para M + presente e "removido do kit" para D + ausente; "ignora" erra a primeira, "todo status" erra a terceira *(alterado em 2026-10-06: QA-03)* |
| M46 | o callable aplicado a todo rótulo "modificado" (inclusive `R`/`C`/`T`): a chave de renome `old\tnew` nunca existe, vira "novo no kit", e `--only-new` tenta aplicar um caminho inexistente e relata `aplicado:` | CT-16 (linha `R100`) | esperado `{"p/old.php\tp/new.php": "modificado"}`; o mutante devolve "novo no kit" *(alterado em 2026-10-06: QA-08)* |
| M36 | o segundo diff (destino × árvore do projeto) roda sobre a lista inteira, não só sobre as entradas novas: acusa como "modificado" toda edição do projeto em pasta antiga da lista | CT-17 (procedural) | a lista do `--dry-run` não traz o arquivo editado só no projeto; o mutante o traz como "modificado". Decisão: CT-16 não mata, porque `rotularDiff` recebe a saída pronta e não escolhe os caminhos do diff — a escolha vive em `arquivosAlterados()`, privado; o matador fica no procedimento ponta a ponta, como asserção extra (ver Cogitado e cortado) |

---

## Regra R9 — as dez views autorais mudam nesta release *(alterado em 2026-10-06: P-07, escrito pela sessão)*

> `P-07` (`RQ-01`, `RQ-02`, `RQ-05`) · perfil **mínimo** · técnica: **rastreio de efeito**

```gherkin
  Regra: a classe antiga do kit:update, que compara tag→tag, entrega os dez arquivos na primeira rodada

    # O "Quando/Então" do git diff vira `it()` (continência dos dez, nenhum de pasta crua); o `--dry-run` com a classe antiga é procedimento da sessão, resultado no `03`. *(alterado em 2026-10-06)*
    # No CI (checkout raso, sem tags) este caso pula sempre: o M43 só morre localmente e na extração; a contagem de pulados do CI sobe 1. *(alterado em 2026-10-06: ciclo 2, suspeita registrada)*
    Cenário: [CT-18] cada view autoral difere da tag anterior, e a classe antiga as lista num projeto já atualizado
      Dado o repositório do kit com a tag anterior "v0.45.0" e esta versão
      # Partição "tag anterior ausente" (QA-02): o checkout raso do CI não traz tags — o caso pula com motivo quando "git rev-parse --verify v0.45.0" falha, e o pulo entra na contagem. *(alterado em 2026-10-06, escrito pela sessão)*
      Quando o mantenedor roda "git diff --name-only v0.45.0 HEAD -- resources/views/vendor"
      Então a saída contém os dez arquivos das cinco pastas autorais, e nenhum caminho dela pertence a pasta classificada como publish cru *(alterado em 2026-10-06: continência, não igualdade)*
      E, num projeto na v0.45.0 com a CLASSE ANTIGA do kit:update, "kit:update --repo={kit local} --tag={tag com a correção} --dry-run" lista os dez arquivos
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M43 | a linha de comentário entrou em algumas views e não em todas (ou numa view crua) | CT-18 | a saída do `git diff --name-only` contém os dez; o mutante tem menos de dez, ou um arquivo de pasta crua |
| M44 | CT-18 sem a guarda da tag: `git diff v0.45.0` falha no checkout raso do CI (`fatal: bad revision`) e o caso fica vermelho | CT-18 (partição "tag ausente") | com a tag inexistente o caso é `skipped` com motivo; o mutante é `failed` — medido num clone `--depth 1 --no-tags` *(alterado em 2026-10-06: QA-02)* |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: sem rota, recurso nem usuário (superfície: "Sem superfície de UI") | — |
| Autorização exercida na ação | não se aplica: sem policy nem permissão | — |
| Idempotência | não se aplica: a varredura só lê; o `kit:update` já existente não muda de mecanismo | — |
| Concorrência | não se aplica: nenhum contador nem escrita concorrente | — |
| Fronteira no ponto de entrada | CT-01, CT-10 (a entrada da constante é o ponto de entrada da rota `kit:update`) | Lista do kit |
| Domínio condicionado | CT-02 linhas `painel`/`painel2` (a mesma view relativa vale conforme o pacote) *(alterado em 2026-10-06: QA-06)* | Classificação por fixture |
| Cardinalidade 0 / 1 / N | 0 pastas examinadas: CT-08 · pasta com 0 arquivos: CT-02 `vazia` · 1 view: CT-02 `cru`/`byte` · N views com mistura: CT-02 `mista` (3), CT-06 | Classificação por fixture · Árvore real do kit |
| Ausente ≠ null ≠ vazio | CT-02 linhas `sopar` (pacote ausente), `extra` (pacote presente, arquivo ausente nele), `vazia` (pasta sem arquivo); CT-12; lista da origem não lida (`[]`) ≠ "tudo é novo": CT-15 linha 3; saída vazia do diff: CT-16 linha 4 *(alterado em 2026-10-06: CR-01/RD-01)* | Classificação por fixture · Diff da entrada nova |
| Texto: fim de linha, espaço | CT-02 linhas `crlf`, `crlf-inv`, `byte`, `borda` | Classificação por fixture |
| Plataforma: separador de caminho no Windows | CT-08 (raiz montada errada ⇒ zero pastas) | Árvore real do kit |
| Arquivo local não rastreado na árvore do kit *(alterado em 2026-10-06: RD-06)* | CT-06/CT-07 com `Dado` em `git ls-files`; o matador do mutante M38 é o mutante manual D | Árvore real do kit |
| Texto: separador e linhas da saída do git (TAB, linha em branco final) *(alterado em 2026-10-06: CR-01/RD-01)* | CT-16 linhas 1 a 3 | Diff da entrada nova |
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
| Saída do estado de erro | CT-03 (a reprovação declara as duas saídas: `vendor:publish` ou `CAMINHOS_DO_KIT`); CT-04 linha `ancestral` (estreitar a entrada) e CT-12 (listar ou apagar a órfã, sem `vendor:publish`) *(alterado em 2026-10-06: RD-05)* | Classificação por fixture |
| **Teste que viaja lendo arquivo que não viaja** (linha do projeto) | CT-09, CT-11 | Árvore real do kit · CHANGELOG |
| Projeto antigo + `kit:update` real (RQ-05 ponta a ponta) | CT-17 (procedimento: `kit:update --dry-run` de um projeto v0.45.0 sem a pasta contra tag temporária com a correção, com controle sem a correção), com regressão em CT-15/CT-16; CT-14 só prova que a entrada-pasta extrai o arquivo aninhado. O cenário 3 **sobre a tag publicada** continua Fora de Escopo do `00`. O `LogoDarkModeTest` CT-16 é verde na árvore do kit antes e depois da correção e por isso não discrimina (ADV-21) *(alterado em 2026-10-06: CR-01/RD-01, CR-08)* | Lista do kit · Diff da entrada nova |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a view da lock-screen está coberta | R1 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M1, M2, M20 |
| CT-02 | classificação por conteúdo (14 partições) | R2 | EP | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M3, M4, M5, M6, M7, M24, M25, M26, M27, M28 |
| CT-03 | drift do pacote acusado com as duas saídas e só os divergentes | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M9, M9c |
| CT-04 | publish cru coberto reprova (3 formas de entrada), saída certa para a forma *(alterado em 2026-10-06: RD-05)* | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M8, M29, M9b, M39 |
| CT-05 | lista coerente aprova | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M10 |
| CT-06 | pasta autoral coberta inteira, com sonda, sobre os arquivos rastreados *(alterado em 2026-10-06: RD-06)* | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M11, M12, M38 (manual D) |
| CT-07 | nenhuma view crua coberta, sobre os arquivos rastreados *(alterado em 2026-10-06: RD-06)* | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M13, M14, M38 (manual D) |
| CT-08 | todas as pastas examinadas (referência independente, > 0) e `filament-auth-designer` autoral *(alterado em 2026-10-06: CR-07)* | R4 | controle positivo | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M15, M37 |
| CT-09 | pula fora da árvore com motivo (extração com publish do projeto) | R5 | rastreio de efeito | Árvore real do kit | procedimento | fundido em CT-13 como regressão; o procedimento junit sobre CT-06…CT-08 na extração é evidência da `## Verificação Final` do `03`, não `it()` | M17, M18 |
| CT-10 | o leitor do fonte produz a lista da constante | R6 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` (caso existente) | M19, M20 |
| CT-11 | CHANGELOG cita #148 e resources/views/vendor | R7 | EP | CHANGELOG | unit de regra | `tests/Kit/KitUpdateTest.php` | M21, M22 |
| CT-12 | autoral sem pacote, fora da lista, reprova oferecendo listar ou apagar a órfã, sem `vendor:publish` *(alterado em 2026-10-06: RD-05)* | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M30, M40 |
| CT-13 | guarda `! naArvoreDoKit()` com motivo declarada no fonte dos casos da árvore real | R5 | meta-caso sobre o fonte | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M16, M17, M18 |
| CT-14 | a entrada-pasta extrai o arquivo aninhado *(alterado em 2026-10-06: CR-08)* | R1 | entrega simulada | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M31 |
| CT-15 | entradas novas = destino − origem, reindexadas; origem `[]` ⇒ `[]` *(alterado em 2026-10-06: CR-01/RD-01)* | R8 | EP | Diff da entrada nova | unit de regra | `tests/Kit/KitUpdateTest.php` | M32, M33, M34 |
| CT-16 | rótulo por status × origem, com saída real do git *(alterado em 2026-10-06: CR-01/RD-01)* | R8 | tabela de decisão | Diff da entrada nova | unit de regra | `tests/Kit/KitUpdateTest.php` | M35, M45, M46 |
| CT-17 | `kit:update --dry-run` de v0.45.0 sem a pasta lista `media.blade.php` como novo no kit, e não a edição do projeto *(alterado em 2026-10-06: CR-01/RD-01)* | R8 | procedimento ponta a ponta | Diff da entrada nova | procedimento | fundido em CT-15/CT-16 como regressão; o procedimento é evidência do `03`, não `it()` | M36 (e M32, M34, M35 de ponta a ponta) |
| CT-18 | as dez views autorais diferem da tag anterior (⊇, por `it()`); a classe antiga as lista (procedimento) | R9 | rastreio de efeito | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` — o `git diff --name-only v0.45.0 HEAD` é `it()` com **continência** dos dez e nenhum de pasta crua *(alterado em 2026-10-06: igualdade exata ficaria falsa na próxima release que tocar uma view)*; o `--dry-run` com a classe antiga é evidência da `## Verificação Final` do `03` | M43, M44 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| `caminhosUnidos()` com a lista de um destino v0.42.0 (sem as entradas novas) contém `filament-auth-designer` | a união já é provada genericamente pelos casos existentes (`caminho que só esta versão cobre não se perde…`); com CT-01 verde, nenhum mutante desta correção sobrevive a eles |
| um cenário por pasta real (12 linhas com a classe esperada) | congelaria a medição do step 3 como oráculo e reprovaria a drift legítima de P-05; CT-06/CT-07 cobrem as mesmas pastas pelo conteúdo |
| BOM no início da view como partição | P-03 normaliza só fim de linha; BOM é diferença de conteúdo e cai na partição `byte`, sem mutante novo |
| CT-14 com a lista "unida com a de um destino antigo" como prova de entrega a quem atualiza *(alterado em 2026-10-06: CR-08)* | a união é comutativa: a lista unida contém a entrada nova seja qual for a lista antiga, então o cenário passava com e sem P-06 e nunca exercitava o defeito real — entrada que entrou na lista com o arquivo igual entre as tags, e por isso fora do diff tag→tag. O mecanismo real (o diff entre tags) fica em CT-15 (quais entradas são novas), CT-16 (como a saída do diff vira rótulo) e CT-17 (o comando inteiro); CT-14 ficou só com o que prova: a entrada-pasta extrai o arquivo aninhado |
| `it()` que monta um repositório git temporário com duas tags e roda `arquivosAlterados()` *(alterado em 2026-10-06: CR-01/RD-01)* | o método é privado e o arnês custaria um repositório com histórico por caso; a composição (qual diff roda sobre quais caminhos, M36) fica no procedimento CT-17, e as duas peças puras têm regressão em CT-15/CT-16 |
| linha `R100\told\tnew` no CT-16 *(alterado em 2026-10-06: CR-01/RD-01)* | o desenho recebido diz que `R` vira "modificado", mas não qual dos dois caminhos é a chave; fixar um seria inventar o oráculo — volta como pergunta de desenho (Q8 do retorno). A célula "outra letra" está coberta pela linha `T` — **revertido em 2026-10-06 (QA-08/QA-12)**: a linha `R100` entrou no CT-16 com o oráculo pré-existente (chave `old\tnew`, rótulo "modificado"), porque a P-08 precisava provar que o callable não a alcança |

## Sem CT-B

- Motivo: "Sem superfície de UI" no plano. A correção é uma constante, dois estáticos do comando, um teste e uma linha em dez views *(alterado em 2026-10-06: QA-04 — dizia "uma constante e um teste")*; a afirmação de cada cenário é sobre arquivos, a lista do kit e a saída do `git diff`. O efeito na tela (o par claro/escuro da lock-screen) já tem o `LogoDarkModeTest` CT-16, que não é desta wiki. Nenhuma costura `browser`, logo o `05` não existe.

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
