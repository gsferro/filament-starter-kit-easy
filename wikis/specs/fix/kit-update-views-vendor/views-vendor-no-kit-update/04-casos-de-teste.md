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

- Probabilidade 2 na classificação: comparação de conteúdo com fim de linha, pares por caminho relativo e o mesmo nome em mais de um pacote. Impacto 2: o defeito de cada direção é retrabalho manual (view que não chega; customização do projeto sobrescrita, recuperável pelo git). Nenhuma área com Impacto 3 nem perfil completo: **revisão adversarial não disparada**.
- Técnicas aplicadas: EP (classificação por conteúdo, partições isoladas), tabela de decisão (lista × autoria), rastreio de efeito negativo (pulo declarado fora da árvore).
- Cenários: 11 · Regras: 7 · Mutantes previstos: 23 · Sem matador: 0
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho); recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a constante `KitUpdate::CAMINHOS_DO_KIT` (entradas novas), a varredura de `resources/views/vendor` em `tests/Kit/KitUpdateTest.php`, a entrada do `CHANGELOG.md` | CT-01, CT-06…CT-08, CT-11 |
| F | entregar override autoral pelo `kit:update`; classificar pasta como autoral ou publish cru; reprovar divergência lista × autoria antes da tag | CT-01…CT-08 |
| D | 12 pastas reais, partições autoral (ao menos uma view diferente) e cru (todas idênticas); pasta mista; view sem par no vendor; mesmo caminho relativo em dois pacotes; fim de linha CRLF × LF | CT-02, CT-06…CT-08 |
| I | `php artisan kit:update` (lê a lista do fonte do destino por `caminhosDeclaradosEm()` e une com a desta versão); a suíte `kit` antes da tag | CT-01, CT-10 |
| P | Windows (CRLF no checkout, separador `\`) e Linux; árvore do kit × projeto instalado (`.github` e `CHANGELOG.md` são `export-ignore`, `.gitattributes:21`) | CT-02, CT-09, CT-11 |
| O | mantenedor edita uma view publicada e esquece a lista (a classe do issue); pacote atualiza a própria view e o publish cru fica para trás (P-05); projeto instalado com publishes próprios (P-04) | CT-03, CT-06, CT-09 |
| T | não se aplica ao comportamento: nada depende de relógio. A única dimensão temporal é a drift do pacote (P-05), tratada como dado em CT-03 | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a view da lock-screen está coberta por `CAMINHOS_DO_KIT` | entrega (mínimo) | RQ-01, RQ-05 | EP (1 partição) | CT-01 |
| R2 — autoria decidida por conteúdo contra o vendor instalado, fim de linha normalizado, pasta inteira | classificação (padrão) | P-03, P-02, P-01, RQ-04 | EP, partições isoladas | CT-02 |
| R3 — a varredura reprova autoral fora da lista e crua dentro dela, e nomeia pasta, arquivos e as duas saídas | classificação (padrão) | RQ-03, RQ-04, P-01, P-05 | tabela de decisão (autoria × listada) | CT-03, CT-04, CT-05 |
| R4 — na árvore real do kit, toda pasta autoral está coberta e nenhuma crua está | classificação (padrão) | RQ-02, RQ-03, RQ-04, P-01, P-02 | EP sobre a árvore real + controle positivo | CT-06, CT-07, CT-08 |
| R5 — a varredura só roda na árvore do kit e, fora dela, pula com motivo | guarda (padrão) | P-04 | rastreio de efeito (rodou / pulou declarado) | CT-09 |
| R6 — as entradas novas têm a forma que `caminhosDeclaradosEm()` lê | entrega (mínimo) | RQ-01 (chega a quem atualiza) | EP (1 partição) | CT-10 |
| R7 — o CHANGELOG registra a correção do #148 | changelog (mínimo) | sem `RQ`: convenção de entrega do kit, pedida pela sessão (ver Fronteira) | EP (1 partição) | CT-11 |

- RQ-05 — coberta pela entrega (CT-01) e pelo `LogoDarkModeTest` CT-16 existente, que fica verde na árvore do kit. O "projeto antigo + `kit:update` real" é Fora de Escopo declarado no `00` (cenário 3 do checklist de release): lacuna declarada, não cenário.
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
- **Árvore de fixture** (CT-02…CT-05): diretório temporário único por caso (`sys_get_temp_dir()` + id), com `views/{pasta}/...` e `vendor/{fornecedor}/{pacote}/resources/views/...`, apagado no `afterEach`. Conteúdo escrito com fim de linha explícito (`"\n"` ou `"\r\n"`), nunca herdado do checkout.
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
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a entrada `resources/views/vendor/filament-auth-designer` não entra na constante (correção só no `.gitattributes`, na doc ou num comentário da constante) | CT-01 | `estaCoberto(media.blade.php)` é `true`; o mutante devolve `false` |
| M2 | entrada com o caminho errado: `resources/views/filament-auth-designer` (sem `vendor/`) ou `…/filament-auth-design` | CT-01 | o prefixo da entrada não casa com o caminho do `Dado`; `estaCoberto` devolve `false` |

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
        | pasta | pacote             | relacao                                                                      | classe   | # partição                         |
        | cru   | acme/cru           | uma view, idêntica byte a byte                                               | cru      | idêntica                           |
        | crlf  | acme/crlf          | uma view, igual exceto CRLF no kit e LF no pacote                            | cru      | só fim de linha                    |
        | byte  | acme/byte          | uma view, um espaço a mais no meio de uma linha                              | autoral  | diferença mínima de conteúdo       |
        | mista | acme/mista         | duas views: "a-identica.blade.php" idêntica, "b-editada.blade.php" diferente | autoral  | pasta mista, idêntica vem primeiro |
        | final | acme/final         | uma view, igual exceto uma linha a mais no fim                               | autoral  | diferença no fim do arquivo        |
        | sopar | (nenhum)           | uma view sem par em pacote nenhum                                            | autoral  | Q7 (b), decidida: autoral          |
        | dois  | acme/um e acme/dois | mesma view relativa nos dois; idêntica só à de "acme/dois"                  | cru      | Q7 (a), decidida: cru              |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M3 | "existe no vendor" no lugar de comparar conteúdo | CT-02 linhas `byte`, `mista`, `final` | esperado `autoral`; o mutante classifica `cru` (as três têm par no vendor) |
| M4 | comparação sem normalizar fim de linha | CT-02 linha `crlf` | esperado `cru`; o mutante vê bytes diferentes e classifica `autoral` |
| M5 | normalização larga demais (`trim()` ou remoção de todo espaço em branco) | CT-02 linhas `byte` e `final` | esperado `autoral`; o mutante apaga a diferença e classifica `cru` |
| M6 | pasta decidida pela primeira view (ou pela maioria), não por "ao menos uma" | CT-02 linha `mista` | esperado `autoral`; o mutante lê `a-identica` primeiro e classifica `cru` |
| M7 | compara só com o primeiro pacote que tem o caminho, ou trata sem par como cru | CT-02 linhas `dois` e `sopar` | `dois`: esperado `cru`, o mutante compara com `acme/um` e dá `autoral`; `sopar`: esperado `autoral`, o mutante dá `cru` |

---

## Regra R3 — a varredura reprova divergência entre a lista e a autoria

> `RQ-03`, `RQ-04`, `P-01`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** autoria × listada (4 células: autoral×listada = aprova; autoral×fora = reprova; cru×listada = reprova; cru×fora = aprova — as duas de aprovação cabem num cenário só, CT-05)

```gherkin
  Regra: override autoral fora da lista e publish cru dentro dela reprovam, com mensagem acionável

    Cenário: [CT-03] publish cru que o pacote atualizou é acusado como autoral fora da lista, com as duas saídas
      Dado uma árvore de fixture com a pasta "drift" igual à versão antiga da view "painel.blade.php" do pacote "acme/drift"
      E o pacote instalado com a view "painel.blade.php" já alterada
      E a lista de caminhos do kit sem "resources/views/vendor/drift"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "drift", o arquivo "painel.blade.php", "CAMINHOS_DO_KIT" e "vendor:publish"

    Cenário: [CT-04] publish cru dentro da lista reprova
      Dado uma árvore de fixture com a pasta "crua" idêntica à do pacote "acme/crua"
      E a lista de caminhos do kit com "resources/views/vendor/crua"
      Quando a varredura confere a lista contra a árvore
      Então a varredura reprova
      E a mensagem cita a pasta "crua"

    Cenário: [CT-05] lista coerente com a autoria aprova
      Dado uma árvore de fixture com a pasta autoral "editada" e a pasta "crua" idêntica ao pacote
      E a lista de caminhos do kit com "resources/views/vendor/editada" e sem "resources/views/vendor/crua"
      Quando a varredura confere a lista contra a árvore
      Então a varredura aprova, sem nenhuma pasta acusada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M8 | a varredura só confere uma direção (autoral fora da lista) e ignora cru listada — a direção que sobrescreve customização do projeto | CT-04 | esperado "reprova" citando `crua`; o mutante aprova |
| M9 | mensagem genérica, sem os arquivos ou com uma saída só | CT-03 | a mensagem contém `painel.blade.php`, `CAMINHOS_DO_KIT` **e** `vendor:publish`; o mutante falta ao menos um |
| M10 | "toda pasta de `resources/views/vendor` fora da lista reprova", sem classificar (o `continue` trocado por exigência total) | CT-05 | esperado "aprova" com `crua` fora da lista; o mutante acusa `crua` |

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

    Cenário: [CT-08] a varredura examina todas as pastas e encontra as duas partições
      Dado a árvore do kit com as pastas de resources/views/vendor e o vendor instalado
      Quando a varredura classifica cada pasta pelo conteúdo
      Então o número de pastas classificadas é o número de diretórios de resources/views/vendor
      E há ao menos uma pasta autoral e ao menos uma publish cru
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M11 | uma pasta autoral esquecida na lista (só `filament-auth-designer`, como sugere o issue, sem a auditoria do resto) | CT-06 | a lista de pastas autorais descobertas é `[]`; o mutante lista as autorais que faltaram |
| M12 | entradas por arquivo em vez de pasta (`command-center` só com as 3 views editadas; `filament-auth-designer/components/partials/media.blade.php`) | CT-06 | a view idêntica de `command-center` e a sonda `novo-arquivo-sonda.blade.php` estão cobertas; o mutante deixa as duas de fora |
| M13 | uma pasta crua acrescentada à lista (ex.: `pulse`, por "auditoria" que lista tudo que existe) | CT-07 | a lista de views cruas cobertas é `[]`; o mutante devolve as views de `pulse` |
| M14 | `resources/views/vendor` inteiro na lista | CT-07 | idem: toda view crua fica coberta e a lista deixa de ser `[]` |
| M15 | raiz das views ou do vendor montada errada (separador `\` no Windows, `base_path` duplicado) e a varredura examina zero pastas — CT-06 e CT-07 verdes por vazio | CT-08 | pastas classificadas = diretórios de `resources/views/vendor` (≥ 2) e as duas partições não vazias; o mutante devolve 0 |

---

## Regra R5 — a varredura só roda na árvore do kit

> `P-04` · perfil **padrão** · técnica: **rastreio de efeito** — rodou na árvore / pulou declarado fora dela

```gherkin
  Regra: fora da árvore do kit a varredura de resources/views/vendor pula, com motivo

    Cenário: [CT-09] num projeto instalado a varredura é reportada como pulada, e na árvore do kit ela roda
      Dado a suíte kit executada na árvore do kit e na extração do git archive da mesma versão
      Quando o mantenedor roda os casos CT-06, CT-07 e CT-08 nos dois ambientes com relatório junit
      Então na árvore do kit os três casos passam com asserções
      E na extração os três casos constam como pulados, com motivo que cita os publishes do próprio projeto
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M16 | guarda invertida (`skip(fn () => naArvoreDoKit())`) | CT-09 | na árvore do kit o junit marca os casos como `passed`; o mutante os marca `skipped` |
| M17 | sem guarda: a varredura roda no projeto instalado e acusa os publishes do projeto (P-04) | CT-09 | na extração o junit marca `skipped`; o mutante marca `failed` |
| M18 | guarda no padrão do vizinho, `expect(true)->toBeTrue(); return;` — verde por ausência, sem motivo | CT-09 | na extração o junit marca `skipped` com motivo; o mutante marca `passed` |

Matador **procedural**: o CT-09 não é um `it()` que se auto-avalia; é a execução de CT-06…CT-08 nos dois ambientes com `--log-junit` (o mesmo procedimento da simulação do cenário 1 antes da tag), com o resultado colado no `03`. O teto de pulados da `Validação antes da tag` do CHANGELOG sobe em 3, decomposto por arquivo (`.ai/rules/testes.md` §Caso que lê `docs/`…).

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
| M23 | caso lendo só a seção do topo: passa hoje e expira na próxima entrega | CT-11 | a leitura é do arquivo inteiro; com uma seção `[Unreleased]` nova acima, o mutante reprova e o CT continua verde |

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
| Cardinalidade 0 / 1 / N | 0 pastas examinadas: CT-08 · 1 view: CT-02 `cru`/`byte` · N views com mistura: CT-02 `mista`, CT-06 | Classificação por fixture · Árvore real do kit |
| Ausente ≠ null ≠ vazio | CT-02 linha `sopar` (view sem par no vendor) | Classificação por fixture |
| Texto: fim de linha, espaço | CT-02 linhas `crlf`, `byte`, `final` | Classificação por fixture |
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
| Projeto antigo + `kit:update` real (RQ-05 ponta a ponta) | lacuna declarada: Fora de Escopo do `00` (cenário 3 do checklist de release); a entrega é provada por CT-01 + CT-10, e o sintoma pelo `LogoDarkModeTest` CT-16 existente | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a view da lock-screen está coberta | R1 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M1, M2, M20 |
| CT-02 | classificação por conteúdo (7 partições) | R2 | EP | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M3, M4, M5, M6, M7 |
| CT-03 | drift do pacote acusado com as duas saídas | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M9 |
| CT-04 | publish cru listado reprova | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M8 |
| CT-05 | lista coerente aprova | R3 | tabela de decisão | Classificação por fixture | unit de regra | `tests/Kit/KitUpdateTest.php` | M10 |
| CT-06 | pasta autoral coberta inteira, com sonda | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M11, M12 |
| CT-07 | nenhuma view crua coberta | R4 | EP | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M13, M14 |
| CT-08 | todas as pastas examinadas, as duas partições presentes | R4 | controle positivo | Árvore real do kit | unit de regra | `tests/Kit/KitUpdateTest.php` | M15 |
| CT-09 | pula fora da árvore com motivo, roda dentro | R5 | rastreio de efeito | Árvore real do kit | unit de regra | procedimento junit sobre CT-06…CT-08 (evidência no `03`) | M16, M17, M18 |
| CT-10 | o leitor do fonte produz a lista da constante | R6 | EP | Lista do kit | unit de regra | `tests/Kit/KitUpdateTest.php` (caso existente) | M19, M20 |
| CT-11 | CHANGELOG cita #148 e resources/views/vendor | R7 | EP | CHANGELOG | unit de regra | `tests/Kit/KitUpdateTest.php` | M21, M22, M23 |

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

- Critério da derivação: não disparada (nenhuma área com perfil completo nem Impacto 3). **Decisão da sessão, 2026-10-06**: despachada mesmo assim ao `fw-adversario-ct` — a classe de defeito (arquivo que viaja por uma rota e não pela outra) já ocorreu cinco vezes no kit, e a independência custa um despacho. Resultado em `03` → `## Despachos`.
