# Casos de Teste — `kit:update` lê a lista de caminhos da versão destino

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela
> não existe. O que foi olhado, para herdar convenção: `tests/Kit/KitUpdateTest.php` (helper
> `caminhosDoKit()` por reflexão, `:14`; leitura do fonte por `file_get_contents`, `:294`),
> `.ai/rules/testes.md` (asserção de ausência sobre fonte filtra comentário) e os fontes de quatro
> tags (`git show <tag>:app/Console/Commands/KitUpdate.php`) — que são **dado**, não implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Parser da constante (regex sobre fonte de outra versão) | 2 — integra com o formato textual de um arquivo que evolui | 2 — parser errado devolve lista incompleta em silêncio; reversível, sem dado | 4 | **padrão** |
| União das listas / filtro do diff | 2 — um único consumidor, mas é o que decide o que chega a quem atualiza | 2 — omissão deixa projeto sem boot entre rodadas; reversível por git | 4 | **padrão** |
| Aviso da segunda rodada | 1 — condição sobre um booleano | 1 — instrução a mais ou a menos no terminal | 1 | **mínimo** |
| Documentação do contorno | 1 | 1 | 1 | **mínimo** |

- Técnicas aplicadas: **EP** sobre as formas de fonte; **rastreio de efeito** sobre a união (aconteceu / não perdeu / não duplicou); **controle negativo** no parser; **asserção sobre o fonte** com filtro de comentário (regra do projeto) onde a operação exige git real.
- Cenários: **8** · Regras: 5 · Mutantes previstos: 17 · Sem matador na suíte: **2** (M13, M14 — declarados; mortos pela prova manual do passo 7 do PRD).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | dois métodos estáticos públicos novos em `KitUpdate`, um método privado de cola, uma propriedade booleana, uma troca em `arquivosAlterados()`, uma condição em `encerrar()`; CHANGELOG e duas páginas de docs | CT-01…CT-09 |
| **F** | extrair a lista de um fonte; unir; filtrar o diff; avisar; documentar | CT-01…CT-09 |
| **D** | o fonte da própria versão (59 caminhos), fontes de tags antigas (55/57), fonte sem a constante, fonte com constante em forma irreconhecível, caminho comentado com `//`, caminho repetido nas duas listas, caminho só na antiga | CT-01…CT-03, CT-05…CT-07 |
| **I** | `git show destino:caminho` (a única entrada nova); o terminal (aviso) | CT-08 (indireto); prova manual |
| **P** | git ≥ qualquer versão que suporte `show tag:path` (já é pré-condição do comando); regex PCRE do PHP 8.4 | declarado: não é variável aqui |
| **O** | quem atualiza a partir desta versão (beneficiado) vs. quem atualiza **para** esta versão a partir de uma anterior (rodada 1 com classe antiga — só a docs alcança) | CT-09 |
| **T** | **não se aplica**: sem estado persistido, sem concorrência. A única ordem que importa (ler a lista **antes** do diff) está na estrutura do código e é o que CT-08 afirma | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — de um fonte do `KitUpdate.php`, o parser devolve exatamente os caminhos declarados em `CAMINHOS_DO_KIT`; de um fonte sem a constante reconhecível, devolve vazio | parser (padrão) | RQ-01, RQ-04 | EP sobre as formas de fonte + controle negativo | CT-01, CT-02, CT-03 |
| R2 — a lista que filtra o diff é a união da lista desta versão com a do destino: nada da antiga se perde, o novo entra, nada se repete | união (padrão) | RQ-01, RQ-02 | rastreio de efeito (entra / não perde / não duplica) | CT-05, CT-06, CT-07 |
| R3 — `arquivosAlterados()` filtra pela união, e não pela constante direta | união (padrão) | RQ-01, RQ-02 | asserção sobre o fonte (operação exige git real) | CT-08 |
| R4 — o parágrafo "RODE O COMANDO DE NOVO" só é emitido quando a lista do destino não foi lida; o parágrafo "comportamento anterior" continua sempre | aviso (mínimo) | RQ-04, RQ-05 | asserção sobre o fonte | CT-08 |
| R5 — a documentação de atualização registra que a lista é lida do destino e mantém o contorno para instalações anteriores | docs (mínimo) | RQ-06 | EP (pt / en) | CT-09 |

Técnica escalada: nenhuma. R3 e R4 usam asserção sobre o fonte **porque** a operação real depende de um repositório git com duas tags — a prova de comportamento é o passo 7 do PRD (manual, registrado no `03`), declarada abaixo como matador fora da suíte.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `caminhosDeclaradosEm()`, `caminhosUnidos()`, `caminhosDoKit()`, `$listaDoDestinoLida` | escolha de implementação | detalhe do cenário; o `04` **exige** que a extração e a união sejam alcançáveis sem git (por isso o PRD as expõe como estáticos públicos) |
| a regex exata do parser | escolha de implementação | não aparece em nenhum `Então`; o oráculo é a **lista devolvida** |
| texto do aviso "Não foi possível ler a lista…" | comportamento visível que o requisito **não** determina | CT-08 afirma só a **existência condicional** do aviso; o texto fica livre. Registrado como pergunta abaixo |
| `TESTES KIT/v0290-sem-tenancy` como ambiente da prova | procedimento, não comportamento | passo 7 do PRD |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- nenhuma nova que bloqueie regra. O texto do aviso de fallback é livre — se o solicitante quiser um texto fixo, vira `Então` em CT-08.

## Setup Global

### Personas

Não se aplica — comando de console, sem usuário.

### Fixtures

- `FONTE_ATUAL` — `file_get_contents(base_path('app/Console/Commands/KitUpdate.php'))`
- `CONSTANTE_ATUAL` — helper existente `caminhosDoKit()` (`tests/Kit/KitUpdateTest.php:14`, reflexão). **Atenção ao nome**: o helper de teste `caminhosDoKit()` já existe e é a constante por reflexão; o método **privado** do comando com o mesmo nome é outra coisa. Não renomear o helper — ele é usado por outros casos.
- `FONTE_ANTIGA` — texto literal inline, na forma da v0.22.3, com **quatro** caminhos declarados, um comentário `/* … */` contendo um caminho entre aspas, um caminho comentado com `//`, e uma segunda constante (`CAMINHOS_SO_RELATORIO`) depois, para provar que o parser para na primeira:

  ```php
  $fonteAntiga = <<<'PHP'
      private const CAMINHOS_DO_KIT = [
          /*
           * Comentário citando 'app/Comentado' — não é declaração.
           */
          'app/Filament',
          // 'app/Desligado',
          'app/Support',
          'resources/views/errors',
          'config/kit.php',
      ];

      private const CAMINHOS_SO_RELATORIO = [
          'composer.json',
      ];
  PHP;
  ```

### Fakes

Nenhum. Sem rede, sem fila, sem banco.

### Estratégia de DB

Não se aplica.

---

## Regra R1 — o parser devolve exatamente os caminhos declarados; sem constante reconhecível, devolve vazio

> `RQ-01`, `RQ-04` · perfil **padrão** · técnica: **EP** sobre as formas de fonte (atual, antiga, sem constante, forma irreconhecível) + **controle negativo** (caminho em comentário)

```gherkin
# language: pt

Funcionalidade: ler a lista de caminhos de outra versão do kit:update

  Regra: de um fonte do KitUpdate.php, o parser devolve exatamente os caminhos declarados em CAMINHOS_DO_KIT

    Cenário: [CT-01] o fonte desta versão produz a mesma lista que a constante carregada
      Dado o fonte atual de app/Console/Commands/KitUpdate.php
      Quando o parser extrai os caminhos
      Então a lista é idêntica, na ordem, à constante CAMINHOS_DO_KIT lida por reflexão

    Cenário: [CT-02] um fonte de versão antiga produz só o que está declarado
      Dado o fonte antigo com quatro caminhos declarados, um citado em comentário e um comentado com //
      Quando o parser extrai os caminhos
      Então a lista é exatamente ["app/Filament", "app/Support", "resources/views/errors", "config/kit.php"]
      E não contém "app/Comentado" nem "app/Desligado" nem "composer.json"

    Esquema do Cenário: [CT-03] fonte sem a constante reconhecível produz lista vazia
      Dado um fonte que <situacao>
      Quando o parser extrai os caminhos
      Então a lista é vazia

      Exemplos:
        | situacao                                                        | # partição                    |
        | não contém CAMINHOS_DO_KIT                                      | arquivo de outra classe       |
        | declara CAMINHOS_DO_KIT = array_merge(self::A, self::B);        | forma irreconhecível          |
        | é a string vazia                                                | git show falhou               |
```

> CT-02 e CT-03 recebem o fonte como **string inline**, sem tag nem remote: se o parser ler o
> disco ou chamar `git`, CT-02 devolve os 59 caminhos atuais em vez dos quatro da fixture. É isso
> que mata M4 — não precisa de cenário próprio (CT-04 foi cortado na auditoria Ponytail).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | regex de linha sem âncora de início (`'…',` em qualquer posição) — captura o caminho citado no comentário e o comentado com `//` | CT-02 |
| M2 | regex do bloco gananciosa (`.*` em vez de `.*?`) — engole até o fim da segunda constante e captura `composer.json` | CT-02 |
| M3 | regex do bloco exige a forma **atual** exata (por exemplo, o comentário de cabeçalho) e falha na forma antiga | CT-02 (a fixture é a forma da v0.22.3) |
| M4 | parser lê o fonte de disco ou chama `git` em vez de receber a string | CT-02 (a fixture inline tem 4 caminhos; o disco tem 59) |
| M5 | `preg_match` falhando devolve `null`/`false` em vez de `[]` (tipo de retorno violado) | CT-03 (todas as linhas) |
| M6 | fonte atual não bate com a constante (alguém muda a forma da lista e esquece o parser) | CT-01 — é o **contrato** da forma textual; quebra antes da tag |

---

## Regra R2 — a lista que filtra o diff é a união: nada da antiga se perde, o novo entra, nada se repete

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **rastreio de efeito** — entra (aconteceu) / não perde (não aconteceu o indevido) / não duplica (uma só vez)

```gherkin
# language: pt

  Regra: a lista que filtra o diff é a união da lista desta versão com a lista do destino

    Cenário: [CT-05] caminho que só o destino cobre entra na lista
      Dado a lista do destino contendo "resources/views/kit-prova" — um caminho que não está na constante
      Quando as listas são unidas
      Então a lista resultante contém "resources/views/kit-prova"
      E contém todos os 59 caminhos da constante

    Cenário: [CT-06] caminho que só a versão atual cobre não se perde
      Dado a lista do destino sendo ["app/Filament"] — um subconjunto da constante
      Quando as listas são unidas
      Então a lista resultante é idêntica à constante
      E "public/css/kit" continua presente

    Cenário: [CT-07] lista do destino vazia devolve exatamente a constante, sem repetição e reindexada
      Dado a lista do destino vazia
      Quando as listas são unidas
      Então a lista resultante é idêntica à constante CAMINHOS_DO_KIT
      E não há valor repetido
      E as chaves são 0..n-1 (lista, não mapa)
```

> **Por que a união é testada como função e não pelo `git diff`**: a operação real exige um
> repositório com duas tags. Provar a união em memória (CT-05..07) + provar que o diff **usa** a
> união (CT-08) + a prova manual do passo 7 é a camada mais barata que falsifica cada mutante.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | usar **só** a lista do destino (substituição) — perde "removido do kit" e quebra o fallback | CT-06, CT-07 |
| M8 | usar **só** a constante (o parser é chamado e ignorado) | CT-05 |
| M9 | concatenar sem `array_unique` — caminhos em dobro viram argumentos repetidos do `git diff` | CT-07 (também CT-06: idêntica à constante exige sem dobro) |
| M10 | `array_unique` sem `array_values` — mapa com buracos, e `...$lista` em argumento de `Process` recebe chaves não sequenciais | CT-07 (chaves 0..n-1) |

---

## Regra R3 — `arquivosAlterados()` filtra pela união, não pela constante direta

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **asserção sobre o fonte** (presença sobre o texto cru; ausência com comentários filtrados — `.ai/rules/testes.md`)

```gherkin
# language: pt

  Regra: o diff do kit:update é filtrado pela lista unida, lida do destino antes de comparar

    Cenário: [CT-08] o diff usa a lista unida e a constante direta sai do filtro
      Dado o fonte atual de app/Console/Commands/KitUpdate.php, com os comentários /* */ e // removidos para a asserção de ausência
      Quando se lê o corpo do método arquivosAlterados
      Então ele chama caminhosDoKit($destino) ao montar os argumentos do git diff
      E não contém "self::CAMINHOS_DO_KIT" — a constante só é lida por caminhosUnidos()
      E o método caminhosDoKit lê "app/Console/Commands/KitUpdate.php" do destino via git show
      E o parágrafo "RODE O COMANDO DE NOVO" está sob a condição de a lista do destino não ter sido lida
      E o texto "comportamento da versão anterior" não está sob essa condição
```

> Um cenário com cinco `Então` sobre o mesmo fonte, e não cinco cenários: a operação é uma só
> (ler o fonte), e as cinco afirmações são as **fronteiras estruturais** que a prova manual não
> vê individualmente quando passa. Perfil padrão permite até 3 cenários por regra; R3 e R4 gastam
> um, compartilhado — a leitura é a mesma.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `arquivosAlterados()` continua com `...self::CAMINHOS_DO_KIT` e `caminhosDoKit()` existe sem ser chamado | CT-08 (`Então` 1 e 2) |
| M12 | `caminhosDoKit()` lê o arquivo errado do destino (por exemplo, `config/kit.php`) ou lê o arquivo **local** com `file_get_contents` em vez de `git show destino:` | CT-08 (`Então` 3) — e **passo 7 do PRD**, que é quem prova o comportamento |
| M13 | a lista do destino é lida **depois** do `git diff` (ordem trocada) | ⚠️ **sem matador na suíte** — o fonte não expressa ordem de execução de forma confiável. **Matador fora da suíte**: passo 7 do PRD (o arquivo do diretório novo aparece no `--dry-run`) |

---

## Regra R4 — o aviso "rode de novo" só quando a lista do destino não foi lida

> `RQ-04`, `RQ-05` · perfil **mínimo** · técnica: **asserção sobre o fonte** (compartilha CT-08)

Cenário: **CT-08**, `Então` 4 e 5. As strings do comando pronto (`php artisan kit:update{$from} --tag={$versao} --no-branch`, `' --from='.str_replace('kit-v', '', $origem)`) já são exigidas pelo caso existente `manda a segunda rodada com --from explícito e --no-branch` (`KitUpdateTest.php:294`) — não se repete.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | condição invertida (`if ($this->listaDoDestinoLida)`) — avisa quem não precisa e cala para quem precisa | ⚠️ **sem matador na suíte** — a asserção sobre o fonte distingue "há condição" de "não há", mas não o sentido com confiança. **Matador fora da suíte**: passo 7 do PRD, item 5 (fallback → aviso presente; lista lida → aviso ausente) |
| M15 | o parágrafo "comportamento da versão anterior" entra **também** na condição — quem teve a lista lida não é avisado de que viu a classe antiga | CT-08 (`Então` 5) |

---

## Regra R5 — a documentação registra a lista do destino e mantém o contorno

> `RQ-06` · perfil **mínimo** · técnica: **EP** (uma partição por idioma)

```gherkin
# language: pt

  Regra: quem atualiza sabe que a lista é lida do destino, e quem está numa versão anterior encontra o contorno

    Esquema do Cenário: [CT-09] a página "Atualizando o projeto" explica a lista do destino e o contorno
      Dado a página <arquivo>
      Quando se lê o item sobre o kit:update se atualizar
      Então ela menciona que a lista de caminhos é lida da versão destino
      E menciona o erro "View [svg.arte-do-login] not found" ou a faixa "0.22" → "0.23" como o caso conhecido
      E o CHANGELOG tem, em [Unreleased] → Corrigido, uma entrada com "kit:update" e "destino"

      Exemplos:
        | arquivo                                     |
        | docs/pt/comecar/atualizando-o-projeto.md    |
        | docs/en/comecar/atualizando-o-projeto.md    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | docs atualizadas só em português | CT-09 (linha `en`) |
| M17 | CHANGELOG sem entrada — a correção existe e ninguém sabe | CT-09 (`Então` 3) |

> Teto de mutantes por regra (2 a 5 no perfil padrão): R1 tem 6 — M6 é o **contrato da forma
> textual** (não é mutante do parser, é do fonte que ele lê) e fica por ser o que impede a
> regressão silenciosa em tag futura. Total 17 (R1: 6, R2: 4, R3: 3, R4: 2, R5: 2); 2 sem matador
> na suíte (M13, M14), ambos mortos pelo passo 7 do PRD.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: sem usuário, sem `{id}` |
| Autorização exercida na ação | não se aplica |
| Idempotência (ancorada no agregado) | não se aplica: funções puras sem estado (CT-02, CT-07 provam entrada → saída determinística) |
| Concorrência | não se aplica: processo único, interativo |
| Fronteira no ponto de entrada (gravação) | não se aplica: nada é gravado por esta feature |
| Domínio condicionado | não se aplica |
| Estado × operação de escrita | não se aplica |
| Ausente ≠ null ≠ vazio | CT-03 (string vazia) e CT-07 (lista vazia) — o `null` não é expressável: tipos `string` e `array` sem nullable |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | não se aplica: caminhos ASCII do próprio repositório; caminho com acento no kit seria acusado por `só lista caminhos que existem de fato` (`KitUpdateTest.php:234`) antes |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Fonte de outra versão com forma diferente** (linha nova da taxonomia, vinda desta feature) | CT-02, CT-03 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | fonte atual == constante | R1 | EP / contrato | Kit (puro) | `tests/Kit/KitUpdateTest.php` | M6 |
| CT-02 | fonte antigo — só o declarado | R1 | EP + controle negativo | Kit (puro) | idem | M1, M2, M3, M4 |
| CT-03 | fonte sem constante reconhecível → vazio (3 linhas) | R1 | EP inválidas isoladas | Kit (puro) | idem | M5 |
| CT-05 | novo do destino entra | R2 | rastreio (aconteceu) | Kit (puro) | idem | M8 |
| CT-06 | só da antiga não se perde | R2 | rastreio (não perdeu) | Kit (puro) | idem | M7, M9 |
| CT-07 | destino vazio → constante, sem dobro, reindexada | R2 | rastreio (uma vez) | Kit (puro) | idem | M7, M9, M10 |
| CT-08 | o fonte: diff usa a união; git show do destino; aviso condicional | R3, R4 | asserção sobre o fonte | Kit (fonte) | idem | M11, M12, M15 |
| CT-09 | docs pt/en + CHANGELOG | R5 | EP | Kit (fonte) | idem | M16, M17 |

**Fora da suíte, obrigatório antes do merge** — passo 7 do PRD, resultado datado no `03`: mata M13 e M14, e é a única prova de RQ-02 com git real.

## Sem CT-B

- Motivo: sem superfície de UI. Comando de console; nada que só o navegador prove.

## Divergência declarada com a skill

- A skill pede `vendor/bin/pest --parallel --tia` na verificação final. O projeto mede que a suíte `Kit` roda em `--parallel` (`composer test:kit`) mas o `--tia` exige PCOV/Xdebug, que não está garantido na máquina de quem atualiza; a Verificação Final do PRD usa `php artisan test tests/Kit/KitUpdateTest.php --compact` + `composer test:kit`. Prevalece o comando que o projeto roda.
