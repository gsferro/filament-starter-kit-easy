# Progresso — Validação de release em projeto instalado

## 1. A correção do `[CT-12]`

- [x] `->skip(fn () => ! naArvoreDoKit(), …)` aplicado — `tests/Kit/HostLocalTest.php`, 2026-09-22
- [x] Docblock com a causa, a mensagem de erro literal e a nota de que a guarda já existia no
      próprio arquivo (`tests/Kit/HostLocalTest.php:[CT-34]:1354`) — 2026-09-22
- [x] `vendor/bin/pest tests/Kit/HostLocalTest.php --compact` — **77/77 verdes**, 2026-09-22

## 2. `wikis/checklist-de-release.md`

- [x] Criado, com declaração de audiência no topo (ADR-03) — 2026-09-22
- [x] Tabela dos quatro cenários, com **o que cada um cobre que os outros não** — 2026-09-22
- [x] Roteiro executável com as três armadilhas medidas (indexação do Packagist, `git` exigido pelo
      `kit:tenancy`, `--force` destrutivo fora de projeto novo) — 2026-09-22
- [x] Seção **"O que já quebrou aqui"** com os dois casos da `v0.38.0` — 2026-09-22

## 3. `CONTRIBUTING.md` e os links

- [x] `CONTRIBUTING.md` criado — **não existia no repositório**, descoberto ao escrever o `00` e
      decidido com o usuário, 2026-09-22
- [x] Seção "Antes de lançar uma tag" apontando o checklist como **obrigatório** — 2026-09-22
- [x] Linha 11 no índice de `wikis/README.md` — 2026-09-22
- [x] Contadores dos READMEs sincronizados — documentos de referência 10 → **11**, features
      especificadas 66 → **67**, 2026-09-22

## 4. A lista de entrega

- [x] `wikis/checklist-de-release.md` em `KitUpdate::CAMINHOS_DO_KIT` — 2026-09-22
- [x] Comentário do `.gitattributes`: "onze" → **"doze documentos de topo"** — 2026-09-22

## 5. Release `v0.38.1`

- [ ] `config/kit.php` e as quatro menções nas docs
- [ ] `CHANGELOG.md` com a seção `Corrigido`
- [ ] Tag anotada + release

## 6. Reexecutar os quatro cenários — critério de aceite de RQ-09

- [ ] Contra a `v0.38.1` **publicada**
- [ ] Zero erro e zero falha nos quatro, com a contagem de pulados registrada

## 7. `[CT-11]` — a guarda endurecida (achado RD-02 do step 6.5)

- [x] `[CT-11]` acrescentado a `tests/Kit/RedeDeDocumentacaoTest.php`, cobrindo **só** a fatia
      decidível: leitura de caminho **literal** não entregue exige a sentinela **no próprio caso**
      — 2026-09-22
- [x] **Provado por mutação**, com o `[CT-12]` defeituoso restaurado na árvore — 2026-09-22:

      | Guarda | Veredito com o defeito presente |
      |---|---|
      | `[CT-10]` — a que existia | **verde** |
      | `[CT-11]` — a nova | **vermelho** |

- [x] A primeira versão do `[CT-11]` **repetiu o erro da terceira varredura** — acusou os 15
      cenários do `SiteDeDocumentacaoTest`, porque não via o `markTestSkipped` do `beforeEach`.
      Corrigida com a checagem do preâmbulo, e o motivo ficou escrito **dentro do caso** — 2026-09-22
- [x] `vendor/bin/pest tests/Kit/RedeDeDocumentacaoTest.php` — **10/10 verdes**, 2026-09-22

## Testes

- [x] `04-casos-de-teste.md` derivado — **23 cenários, 9 regras, 42 mutantes, 5 lacunas
      declaradas**, com duas rodadas de revisão adversarial, 2026-09-22
- [x] Casos escritos conforme o `04` — **em dois arquivos**, e o segundo só entrou depois do
      quality gate (QA-02): `tests/Kit/RedeDeDocumentacaoTest.php` ganhou `[CT-22]` e `[CT-23]`,
      que o `04` atribuía a ele e que a primeira rodada deixou de escrever. A linha anterior
      afirmava conformidade com o `04` contando só um dos dois arquivos.
      `tests/Kit/ChecklistDeReleaseTest.php`, **26 casos: 23
      verdes, 3 pulados** (os três datasets de CT-18, inviáveis por razão declarada), 2026-09-22
- [x] Regressão com o arquivo novo dentro — **2.804 testes, 2.801 passaram, 10.883 asserções, 3 pulados, 0
      falhas** — os quatro a mais que a medição anterior são `[CT-22]` e os três datasets de `[CT-23]`, 2026-09-22

### O que a escrita dos casos encontrou — quatro vermelhos, todos legítimos

| # | Vermelho | Classe | Quem estava errado |
|---|---|---|---|
| 1 | **CT-16**: o alvo do link do `CONTRIBUTING.md` não resolve | implementação | **eu**, ao fechar o RD-01. Movi o arquivo para `.github/` e deixei o link relativo à raiz; no GitHub ele aponta para `.github/wikis/…`. Eram **três** links quebrados, e o caso só olha um |
| 2 | **CT-15**: o roteiro não exigia versão validada, os quatro diretórios nem a saída colada | implementação | o artefato. Seção *"Depois dos quatro"* reescrita com a exigência e a ordem (**antes** da tag) |
| 3 | **CT-11 acusou o `[CT-20]`** do arquivo novo | **teste** | **a minha guarda**. Ela usava regex, e o CT-20 *menciona* a chamada dentro de uma string de asserção. Reescrita por `token_get_all()`: menção é **um** `T_CONSTANT_ENCAPSED_STRING` e não se decompõe em chamada |
| 4 | **CT-25**: contador de arquivos de teste dos READMEs | implementação | o artefato. 151/177 → **152/178** |

**O terceiro é o que vale registrar.** A ADR-02 aceitou o `[CT-11]` declarando o risco
*"produzir falso positivo numa forma de leitura que eu não previ"*. Ele apareceu no **primeiro
arquivo escrito depois dela**, e a correção é a técnica que o próprio arquivo novo já usava.
Reprovado nos dois sentidos depois de corrigido: zero falso positivo, e ainda vermelho quando o
`skip` do `[CT-12]` é removido.

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent` — `passed`, 2026-09-22
- [x] `vendor/bin/pest` nos cinco arquivos tocados (`SiteDeDocumentacao`, `RedeDeDocumentacao`,
      `KitUpdate`, `HostLocal`, `CitacoesDeCodigo`) — **209/209**, 2026-09-22
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel` — **2.773 passaram, 10.790 asserções, 0 falhas**, 2026-09-22. Mesmo total da `v0.38.0`: a correção **declara o alcance** de um caso, não acrescenta caso
- [x] `vendor/bin/phpstan analyse` — level 7, **0 erros** · `vendor/bin/filacheck` — **17/17**, 2026-09-22
- [x] `/code-review high main...HEAD` + passe de eixos (step 6.5) — **9 achados, 3 Major e 6 Minor,
      todos fechados**, 2026-09-22:

      | # | Sev | Achado | Fechado por |
      |---|---|---|---|
      | RD-01 | Major | `CONTRIBUTING.md` na raiz **viajava** para o projeto instalado, e não chegava por `kit:update` — entrega assimétrica sem teste vermelho | `git mv` para `.github/`, onde `SECURITY.md` já estava; ADR-03 reescrita |
      | RD-02 | Major | a ADR-02 decidia *"não escrever guarda"* sem dizer que **uma guarda já existia** (`[CT-10]`), verde o tempo todo | `[CT-11]`, provado por mutação; ADR-02, `00` e `03` reescritos |
      | RD-03 | Major | o checklist afirmava que **os dois** casos passaram por todos os gates — falso para o do roadmap, e o próprio texto se contradizia duas linhas depois | seção reescrita separando os dois casos, com a história conferida no `git log` |
      | RD-04 | Minor | `145 pulados` afirmado como valor corrente em 4 lugares | trocado por medição datada + a previsão 146/147 que o passo 6 mede |
      | RD-05 | Minor | `KitUpdate.php` dizia *"os sete documentos de topo"* sobre uma lista de **doze** | corrigido |
      | RD-06 | Minor | citação `SiteDeDocumentacaoTest.php:30` sem o símbolo | forma `:naArvoreDoKit:30` |
      | RD-07 | Minor | o checklist mandava conferir `config('kit.version')` contra *"a tag nova"* — o valor não tem o `v` | esperado explicitado (`0.38.1`, não `v0.38.1`) |
      | RD-08 | Minor | a nota do Packagist dizia que sem indexação o `create-project` *"pega a versão anterior"* — com a versão **pinada** ele falha alto | nota corrigida: pinar é o que torna o roteiro seguro |
      | RD-09 | Minor | os dois últimos itens de `CAMINHOS_DO_KIT` fora da ordem alfabética | reordenados; ordem declarada no comentário |

- [x] Reverificação após os nove: `pint` **passed** · `phpstan` level 7 **0 erros** · os cinco
      arquivos tocados **210/210** · `--testsuite=Kit,Tenancy --parallel` **2.804 testes, 2.801
      passaram, 10.883 asserções, 3 pulados, 0 falhas**, 2026-09-22.

      **Corrigido pelo quality gate (QA-07).** Este bullet dizia *"2.774 passaram, 10.791
      asserções"* e, logo abaixo, *"2.774 é um a mais que a `v0.38.0`, e o um é o `[CT-11]` — a
      única linha do diff que acrescenta caso"*. As duas frases eram verdadeiras quando escritas e
      falsas na árvore entregue: o diff acrescentou `ChecklistDeReleaseTest.php` com **26** casos
      depois disso. É o padrão *número certo num arquivo e velho em outro* — o valor correto já
      estava na seção `## Testes` deste mesmo documento
- [ ] `feature-quality-gate` (step 8)
- [x] Citações `arquivo:símbolo:linha` reverificadas — **4 distintas, 8 ocorrências, 4/4 corretas**, 2026-09-22. O extrator e a conferência, colados porque o *"3/3 ok"* anterior não saía de comando nenhum (QA-08):

      ```
      $ grep -rhoE '([A-Za-z0-9_/.-]+\.php):(\[?[A-Za-z_0-9-]+\]?):([0-9]+)' wikis/specs/fix/validacao-de-release/ | sort | uniq -c
            5 tests/Kit/HostLocalTest.php:[CT-34]:1354
            1 tests/Kit/SiteDeDocumentacaoTest.php:naArvoreDoKit:30
            1 app/Console/Commands/KitUpdate.php:handle:414
            1 app/Console/Commands/KitUpdate.php:handle:1089
      ```


      **Uma delas estava errada, e é o registro que importa**: escrevi `HostLocalTest.php:1356`
      no texto que documenta citações erradas, e a minha própria edição no docblock do CT-12
      tinha deslocado o arquivo em 14 linhas. Corrigida para a forma com símbolo
      (`tests/Kit/HostLocalTest.php:[CT-34]:1354`), que é a que sobrevive ao deslocamento
- [ ] Os quatro cenários contra a `v0.38.1` publicada — **RQ-09**

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `testes.md` | `tests/**` | **violada e corrigida** | a guarda usada é o padrão que o próprio arquivo já tinha (`tests/Kit/HostLocalTest.php:[CT-34]:1354`). **Mas a evidência anterior — *"nenhum helper novo, nenhum helper cruzado"* — descrevia só o passo 1**: o diff acrescenta 15 helpers, e um deles, `codigoPhpSemComentario()`, era **clone byte a byte** de `codigoSemComentario()` (mesmo `md5` do corpo). A rule proíbe exatamente isso — *"nunca crie um clone com outro nome para escapar da colisão"*. Achado QA-06; fechado movendo a função para `tests/Pest.php`, com um nome só |
| `app.md` | `app/**` | **n.a. no que ela exige** | o diff toca `app/Console/Commands/KitUpdate.php`, mas só acrescenta uma string a uma constante. A rule governa atribuição de papel/permissão e DTO — nenhum dos dois |
| `specs.md` | `wikis/specs/**` | **aplicada** | citações conferidas — ver Verificação Final |

## Quality Gate

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`

## Por que nenhum gate pegou o `[CT-12]` — RQ-07

Esta seção é a entrega de RQ-07: *"entender os erros"*, não só corrigi-los.

O caso atravessou, sem ser visto:

| Gate | Por que não pegou |
|---|---|
| 3 ciclos de `feature-quality-gate` | rodam na árvore do kit, onde `docs/` existe |
| `/code-review` no diff | idem, e o caso estava **correto** em relação ao plano |
| `fw-revisor-diff` (eixos) | idem — nenhum eixo pergunta *"este arquivo viaja?"* |
| 2 × `fw-qa-gate` (ciclo 4, com L6) | idem. A L6 confere **alegação × comando**, e a alegação aqui não era falsa: o caso realmente passava, onde rodava |

**A causa é estrutural, não de rigor.** Todos os gates rodam no mesmo lugar: a árvore do kit. O
defeito só existe **fora** dela. Nenhum aumento de rigor dentro da árvore o encontraria — e é por
isso que a resposta é um roteiro que sai da árvore, não um gate a mais dentro dela.

**O que o torna não-óbvio**: o `[CT-12]` não está errado. O oráculo documental é bom, o docblock
explica por que ele existe, e ele funciona. O erro é só de **alcance** — ele vale onde `docs/`
existe, e ninguém declarou isso.

## Notas de Implementação

### O passo 4 nasceu de um vermelho, não do plano

Assim que `wikis/checklist-de-release.md` foi criado, o `KitUpdateTest` reprovou:

```
Documentação do kit fora de KitUpdate::CAMINHOS_DO_KIT: wikis/checklist-de-release.md
Quem já instalou o projeto nunca vai receber estes arquivos
```

É **a mesma classe de defeito** que o próprio checklist documenta no caso do roadmap — e aconteceu
enquanto o checklist sobre ela estava sendo escrito. A guarda funcionou dentro do ciclo que a
descreve, o que é a melhor evidência possível de que ela vale.

### O `CONTRIBUTING.md` não existia

A opção escolhida pelo usuário (`wikis/` + `CONTRIBUTING`) pressupunha um arquivo que o repositório
não tinha. Descoberto ao escrever o `00`, registrado como ambiguidade e decidido com ele: criar o
arquivo, que o GitHub já espera — aparece no botão de contribuir, no template de PR e nos insights.

### Três varreduras estáticas erraram — e a conclusão que tirei delas também

| Tentativa | Respondeu | Errou porque |
|---|---|---|
| por arquivo | 8 arquivos "com guarda" | guarda no arquivo não é guarda no caso |
| por caso | 20 desprotegidos | não via `markTestSkipped()` no `beforeEach` |
| por caso + `beforeEach` | 17 desprotegidos | o regex não atravessava chaves aninhadas |
| **instalar e rodar** | **1** | — |
| **varredura do revisor do 6.5**, ~25 linhas | **1** | — |

**A última linha é o achado RD-02.** Eu concluí das três primeiras que *"uma varredura precisa
acertar os três mecanismos, e por isso não haverá guarda"*. O revisor do step 6.5, cego a este
documento, escreveu a quarta em ~25 linhas e ela acertou. **As três erraram por limitação delas,
não por propriedade do problema** — e a fatia decidível (caminho literal, no corpo do caso) era
exatamente por onde o defeito tinha passado.

E havia um erro maior embaixo: **já existia uma guarda estática**, o `[CT-10]` do
`tests/Kit/RedeDeDocumentacaoTest.php`, verde o tempo todo porque olha o **arquivo**, e o
`HostLocalTest` tinha a sentinela no `[CT-34]`. A pergunta nunca foi *"criar guarda?"*; era
*"endurecer a que existe?"*, e ninguém a fez. Ver ADR-02, reescrita, e o `[CT-11]` no passo 7.

## Retrospectiva

<!-- Preenchido no fim. -->
