# Decisões Arquiteturais — Cobertura de testes

## ADR-01: PCOV, e não Xdebug

**Status**: Aceita · **Data**: 2026-09-24 · **Atende**: RQ-03

### Contexto

Cobertura de código em PHP exige um driver: **Xdebug 3.0+** ou **PCOV**. Nenhum dos dois estava
instalado — medido antes de decidir:

```
php -m | grep -iE 'pcov|xdebug'
→ (vazio)
```

O PHP local é `8.4.25 (cli) NTS Visual C++ 2022 x64`, extensão API `20240924`.

### Decisão

**PCOV 1.0.12**, o build `php_pcov-1.0.12-8.4-nts-vs17-x64.zip`, que casa exatamente com esse PHP.
Instalado em `C:\php-8.4.25\ext\php_pcov.dll`, declarado no `php.ini` **desligado por padrão**:

```ini
extension=pcov
pcov.enabled=0
```

Liga-se por execução: `php -d pcov.enabled=1 vendor/bin/pest --coverage`.

### Alternativas Consideradas

1. **Xdebug** — descartado. Ele é um depurador que também faz cobertura, e o custo reflete isso:
   a literatura mede 2 a 5× mais lento que o PCOV para o mesmo relatório. Numa suíte de 2.904
   casos, a diferença decide se a medição é praticável.
2. **Manter sem driver e nunca medir** — é o estado até esta wiki, e é o que o requisito veio
   desfazer. Também é o que mantém `pest --mutate` indisponível (achado QA-36 do quality gate
   da feature `rodape-coerente`).

### Consequências

- **Positiva**: o `--tia` do Pest 5 e o `pest --mutate` passam a ser possíveis — os dois exigem
  driver de cobertura, e estavam bloqueados pelo mesmo motivo. Não entram nesta entrega (o `00`
  declara mutation fora de escopo), mas deixam de ser impossíveis.
- **Positiva**: `pcov.enabled=0` faz a suíte do dia a dia **não pagar nada** pela medição. Medido:
  sem a flag, o Pest responde `No code coverage driver is available` — ou seja, não há como uma
  execução comum ativar a instrumentação por acidente.
- **Negativa**: o `php.ini` da máquina mudou. Há backup em `php.ini.bak-antes-do-pcov`.
- **Risco**: o DLL é específico da combinação PHP/ABI. Um upgrade de PHP exige baixar o build
  correspondente, e o sintoma de esquecer é `PHP Startup: Unable to load dynamic library 'pcov'`.
  Registrado na documentação.

### Referências

- `https://downloads.php.net/~windows/pecl/releases/pcov/1.0.12/`
- Doc do Pest 5, *Test Coverage*: *"requires XDebug 3.0+ or PCOV"*

---

## ADR-02: Cobertura roda em SÉRIE — e isso não é escolha

**Status**: Aceita · **Data**: 2026-09-24 · **Atende**: RQ-04, RQ-05

### Contexto

O kit roda a suíte com `--parallel` (16 processos), e é isso que a mantém em ~3 minutos. A
pergunta natural era se a cobertura acompanha.

### Decisão

**Não acompanha.** Medido, e não deduzido:

| Comando | Resultado |
|---|---|
| `pest … --parallel --coverage` | imprime o **usage do paratest** — a opção não existe lá |
| `artisan test … --parallel --coverage-clover=arquivo` | roda, **e não gera o arquivo** |
| `pest … --coverage-clover=arquivo` (série) | **gera**, 764 KB para um arquivo de teste |

Então: **a medição de cobertura é serial, por construção do paratest**, e o comando do kit reflete
isso em vez de fingir o contrário.

### Alternativas Consideradas

1. **`--coverage-php` por processo + merge manual** — o PHPUnit sabe serializar cobertura por
   processo e fundir depois. Descartado por custo/benefício: exige código de merge no kit para
   uma medição que não roda no loop do dia a dia. Se o tempo serial se tornar inviável, é o
   caminho a reabrir, e fica registrado aqui por isso.
2. **Medir só um subconjunto** (`app/Support`, por exemplo) — descartado porque contraria o RQ-02:
   o requisito pergunta *"quanto do nosso kit está coberto"*, e um recorte arbitrário responde
   outra pergunta.

### Consequências

- **Negativa, e é a principal**: a medição custa o tempo da suíte **serial** mais o overhead do
  PCOV, medido em **+44 %** numa amostra (3,08 s → 4,45 s). Isso a tira do loop de
  desenvolvimento e a coloca num comando próprio.
- **Positiva**: como ela não está no loop, nada do dia a dia fica mais lento — o `composer test:kit`
  continua paralelo e sem driver ativo.

---

## ADR-03: O denominador é `app/`, e ele já estava declarado

**Status**: Aceita · **Data**: 2026-09-24 · **Atende**: RQ-02

### Contexto

O `00` registrou como **premissa** que o denominador seria `app/`, com o "se negado" escrito. A
verificação mostrou que a premissa não era uma escolha a fazer: o `phpunit.xml` **já declarava**.

```xml
<source>
    <include>
        <directory>app</directory>
    </include>
</source>
```

### Decisão

Manter `app/` como fonte, sem tocar no `phpunit.xml`. São **240 arquivos** e **9.848 statements**
— o `statements` do relatório Clover é o denominador real, não as 34.271 linhas físicas.

### Consequências

- **Positiva**: a premissa do `00` virou fato verificado, e a entrega não precisou alterar
  configuração de teste para produzir o número.
- **Negativa**: `database/`, `config/` e `routes/` ficam fora. Em `database/seeders` há lógica
  (a matriz de papéis) que a cobertura não vai enxergar. Declarado, não escondido.

---

## ADR-04: O número vive no repositório, e o CI reprova quando ele mente

**Status**: Aceita · **Data**: 2026-09-24 · **Atende**: RQ-08, RQ-09

### Contexto

O `00` pediu badge no README *"se tiver como deixar explícito no github"*, e registrou a
ambiguidade: badge de cobertura normalmente vem de **Codecov** ou **Coveralls**, que são serviços
de terceiro.

Três fatos medidos pesaram na escolha:

1. o repositório é **público** — Codecov funcionaria sem token;
2. **o CI deste kit nunca commitou de volta**: `grep -n 'git commit|git push|contents: write'`
   nos workflows não devolve nada. Não há precedente nem permissão;
3. o kit **já guarda números de README com teste** — `SiteDeDocumentacaoTest` reprova quando a
   contagem de pacotes ou de arquivos de teste diverge da árvore. Duas dessas guardas me pegaram
   nesta mesma semana.

### Decisão

O número de cobertura fica **versionado em `.github/badges/cobertura.json`**, no formato
*endpoint* do shields.io, e o README aponta o badge para o arquivo bruto no GitHub.

Quem atualiza é **quem roda a medição**, com `composer test:coverage` — não o CI. E o CI
**reprova** quando o arquivo commitado diverge do que ele mede.

### Alternativas Consideradas

1. **Codecov** — descartado, e o motivo não é ideológico: o relatório passa a viver fora do
   repositório, e a pergunta *"qual era a cobertura na v0.38.0?"* deixa de ser respondível por
   `git show`. Com o JSON versionado, ela é. Fica registrado que, se o time quiser os recursos
   dele (comentário em PR, sunburst, diff coverage), é uma linha no workflow e o repo é público.
2. **CI commita o badge de volta** — descartado por três motivos: exige `contents: write`, cria
   commit automático em `main` sem revisão, e abre a porta para laço de CI. E não é preciso: o
   arquivo versionado à mão **com guarda** dá o mesmo resultado sem nenhum deles.
3. **SVG gerado e commitado** — dispensaria o shields.io, mas o diff de um SVG é ilegível e a
   revisão perderia a capacidade de ver o número mudando. O JSON mostra `"message": "78%"` no diff.
4. **Número escrito à mão no README, sem guarda** — é exatamente a classe de defeito que este kit
   documenta como *"afirmação que envelhece"*. A linha *Telas navegáveis* do README ficou parada
   em `12 / 28 / 27` por mais de um mês por não ter guarda, e o próprio README registra isso.

### Consequências

- **Positiva**: o histórico de cobertura é `git log -- .github/badges/cobertura.json`.
- **Positiva**: nenhuma dependência nova, nenhum token, nenhuma permissão de escrita no CI.
- **Negativa**: quem sobe cobertura precisa rodar a medição e commitar o JSON. **O CI avisa
  quando esquece**, com a instrução do comando — então o custo é lembrar de rodar um comando,
  não descobrir sozinho que o número está velho.
- **Risco**: `raw.githubusercontent.com` tem cache de alguns minutos; o badge pode mostrar o valor
  anterior logo após o merge. Aceito — é um badge, não um alarme.

---

## ADR-05: Os níveis de qualidade adicionais foram **medidos** antes de recomendados

> Atende **RQ-13**. A decisão de cada linha está na coluna *Veredito*; o que entra nesta entrega
> está em `### Decisão`.

### Contexto

O pedido é aberto — *"analise e pesquise se temos mais níveis de qualidade para implementarmos"*.
Responder com uma lista de ferramentas conhecidas seria barato e inútil: toda ferramenta de
qualidade parece boa no abstrato, e o que separa as que valem das que não valem é **quanto ruído
elas produzem neste código**. Um analisador que acusa 594 vezes não é mais rigoroso que um que
acusa 48 — é um que ninguém vai ligar.

Então cada candidato foi **rodado contra o kit** antes de entrar na tabela. Os números abaixo são
de 2026-09-24, nesta árvore.

### O que já está pago e ligado

| Ferramenta | Onde roda | Estado |
|---|---|---|
| Pint | `composer lint`, CI `qualidade` | ligado |
| PHPStan **level 7** + Larastan | CI `qualidade` | ligado, **0 erros** |
| `pest-plugin-phpstan` | junto do PHPStan | ligado |
| Rector + `rector-laravel` | `composer rector` | ligado |
| FilaCheck | pós-edição em `app/Filament` | ligado |
| Snyk | CI `seguranca` | ligado |
| `pest-plugin-profanity` | suíte | ligado |
| **PCOV** | a partir desta entrega | instalado |

### O que está pago e **desligado** — o achado principal deste ADR

| Ferramenta | Instalada em | Uso no kit |
|---|---|---|
| **`pestphp/pest-plugin-arch`** | `vendor/pestphp/pest-plugin-arch` | **zero** — `grep -rn "arch()" tests/` volta `0` |
| **`pestphp/pest-plugin-mutate`** | `vendor/pestphp/pest-plugin-mutate` | destravado agora pelo PCOV + `pestw.cmd` |

O caso do `arch` é o mais desconfortável dos dois: o kit tem **113 arquivos** em `tests/Kit`, e
**415 de 1.517** blocos `it()` são guardas de arquitetura escritas **à mão**, lendo o disco com
`Finder`. Uma parte do que elas fazem o `arch()` faz em uma linha — e o plugin já está no
`composer.json` desde sempre, sem nunca ter sido chamado.

### Medição de cada candidato

| # | Nível | Medido nesta árvore | Custo | Veredito |
|---|---|---|---|---|
| 1 | `arch()->preset()->php()` | **passa limpo**, 53 asserções, **6,5 s** | zero — plugin instalado | **adotar nesta entrega** |
| 2 | `arch()->preset()->security()` | **1 violação**: `exec()` em `KitInstall.php:592` | zero | **adotar com `ignoring()` declarado** |
| 3 | **PHPStan level 8** | **48 erros** | uma passada focada | **roadmap, prioridade alta** |
| 4 | PHPStan level 9 | **474 erros** | reescrita de tipagem em massa | **não** — precipício, não degrau |
| 5 | PHPStan level `max` | **594 erros** | idem | **não** |
| 6 | `arch()->preset()->laravel()` | reprova já na 1.ª classe (`ContaIndisponivelController` tem método público fora do conjunto REST) | mudar convenção do kit | **não** — a convenção do kit é deliberada |
| 7 | `arch()->preset()->strict()` | **163 de 222** classes de `app/` não são `final` | marcar 163 classes | **não** — `AgenteBase` existe para ser estendida |
| 8 | `arch()->preset()->relaxed()` | proíbe método `private`; **54 arquivos** usam | contraria o estilo do kit | **não** |
| 9 | `declare(strict_types=1)` | **98 de 240** arquivos — o kit está meio a meio | decisão + 142 arquivos | **roadmap** — ver nota abaixo |
| 10 | **Mutation score** (`--mutate`) | plugin instalado; PCOV + `pestw.cmd` destravaram | escopado por `--path` | **verificar nesta entrega (RQ-14)** |
| 11 | **Branch coverage** (Xdebug) | Xdebug 3.5.3 instalado nesta entrega | mais lento que o PCOV | **roadmap** — é a única razão de o Xdebug existir aqui |
| 12 | `pest-plugin-type-coverage` | **não instalado** | dependência nova | **roadmap** |
| 13 | `composer-require-checker` + `composer-unused` | **não instalados** | dependências novas de dev | **roadmap, prioridade alta** — ver nota |
| 14 | Deptrac | não instalado | configuração de camadas | **não** — o kit não tem fronteira de camada a defender |

#### Nota sobre o item 9 — `declare(strict_types=1)` a 98/240

Não é "falta de rigor": é **inconsistência**, que é pior. Metade do `app/` roda com coerção
estrita e metade não, e nada no CI diz de que lado um arquivo novo deve nascer. Qualquer um dos
dois lados é defensável; o que não é defensável é o sorteio. Vira item de roadmap com a decisão
explícita, não um `sed` em 142 arquivos.

#### Nota sobre o item 13 — ele responde a uma pergunta que já foi feita ao kit

A crítica externa analisada nesta mesma sessão perguntou, sobre as 58 dependências: *"quais delas
são de fato usadas?"*. A resposta honesta hoje é *"não sabemos"* — e essas duas ferramentas
respondem mecanicamente, uma olhando `use` contra `require`, a outra o inverso. É o item de
roadmap com melhor razão entre valor e esforço desta tabela.

### Decisão

**Entram nesta entrega** (custo desprezível e já pago): os presets `php` e `security` do `arch()`,
com a exceção do `exec` **declarada com o motivo**; e a verificação do mutation score (RQ-14).

**Viram roadmap com a medição na mão**: PHPStan level 8 (48 erros), `composer-require-checker` +
`composer-unused`, `declare(strict_types=1)`, branch coverage e type coverage.

**Ficam de fora, com o motivo escrito**: PHPStan 9/max, os presets `laravel`/`strict`/`relaxed` e
o Deptrac. Todos reprovam por conflitarem com convenção deliberada do kit ou por custo
desproporcional — e não por serem ruins.

### Sobre o `exec` do item 2

`KitInstall::oferecerEstrela()` chama `exec('open '.self::REPOSITORIO)` para abrir o repositório
no navegador. O argumento é uma **constante de classe**; não há entrada de usuário no caminho, e o
uso já tem comentário de revisão no código. Não é vulnerabilidade.

O que ele **é** hoje é uma chamada de `exec` **sem guarda**: se amanhã alguém escrever
`exec($algoQueVeioDoUsuario)`, nada reprova. Adotar o preset com `ignoring()` nesta única classe
inverte isso — a ocorrência conhecida fica declarada, e a **próxima** reprova. É exatamente a
forma de correção que esta esteira pede: fechar a classe, não o caso.

### Consequências

- **Positiva**: duas capacidades já compradas deixam de ficar ociosas, ao custo de ~6 s de suíte.
- **Positiva**: cada "não" da tabela tem número por trás, então a conversa pode ser reaberta por
  quem discordar — com o mesmo comando, não com opinião.
- **Negativa**: a tabela envelhece. `48 erros no level 8` é de 2026-09-24 e muda a cada release;
  quem for executar o item de roadmap deve **remedir** antes de estimar.
- **Risco**: adotar preset de arquitetura cria atrito para contribuição externa que não o conheça.
  Mitigado por os dois adotados serem os que **hoje já passam** — ninguém é reprovado por código
  que já estava lá.

---

## ADR-06: A meta é **78 %**, e ela nasceu da medição — não do costume

> Atende **RQ-06**. Medido em 2026-09-24: **79,79 %** (7.858 / 9.848 statements).

### Contexto

A pergunta *"qual é um número aceitável de cobertura?"* costuma ser respondida com folclore — 80 %
porque é redondo, 100 % porque soa rigoroso. Nenhum dos dois é um argumento.

O que existe de sólido na literatura é **negativo**: cobertura de linha alta não prova que a suíte
detecta defeito, e a própria esteira deste kit registra isso na dimensão K da
`feature-quality-gate` — duas suítes com 100 % de mutation score detectaram 7 e 12 defeitos
plantados de 18. Cobertura mede **o que foi executado**, não **o que foi verificado**.

Então o número aqui não é uma meta de qualidade. É um **detector de regressão**: ele responde
*"entrou código novo sem nenhum teste passando por ele?"* — e só isso.

### Decisão

**Piso de CI: 78 %.** O badge mostra o medido; o `--min` reprova abaixo de 78.

A folga é de **1,79 pp**, e ela foi escolhida com a escala na mão: com 9.848 statements, **1 pp
vale ~98 linhas**. Uma correção de bug, um método novo ou um refactor não movem o número.
Para ele cair 1,79 pp é preciso que **quase 180 statements** entrem sem teste — que é
exatamente o evento que o piso existe para pegar.

Piso mais apertado (79 %) reprovaria por flutuação e viraria ruído; mais frouxo (70 %) deixaria
uma funcionalidade inteira entrar sem teste sem ninguém notar.

### O que o número NÃO inclui, e por quê

| Fora da medição | Motivo |
|---|---|
| `tests/Browser` e `tests/BrowserTenancy` (25 arquivos) | rodam contra um **servidor em outro processo**. O PCOV instrumenta o processo do Pest; código executado pelo servidor é invisível para ele, com ou sem driver |
| Os 4 testes locais de instalação | rodam `composer create-project` de verdade, em processo separado — mesmo motivo |
| `tests/Unit` e `tests/Feature` | têm **1 arquivo de exemplo cada**: são os diretórios do consumidor, não do kit |

A consequência prática é que **79,79 % é um piso, não o teto**: `app/Console` aparece a 27 % e é,
na verdade, o código mais exercitado do kit — só que por fora do medidor. Isso está escrito na
decomposição do `03-progresso.md` e precisa estar na documentação de usuário, porque ler
*"Console 27 %"* como *"os comandos não têm teste"* seria a conclusão errada.

### Alternativas Consideradas

1. **Meta por diretório, em vez de global** — descartada por ora: com `app/Console` distorcido
   por medição, um piso por diretório reprovaria o diretório mais testado do kit. Fica registrado
   como evolução para depois de o problema do processo separado ter solução.
2. **Subir a meta junto com a cobertura, automaticamente (*ratchet*)** — atraente e descartada: o
   piso passaria a subir também quando a cobertura sobe por **remoção de código**, e travaria
   refactors legítimos. Preferido subir o piso à mão, com decisão registrada.
3. **Sem piso, só badge informativo** — descartado: um número que não reprova nada é decoração, e
   este repositório já tem o precedente da linha *Telas navegáveis* que ficou errada por um mês
   por não ter guarda.

### Consequências

- **Positiva**: o piso pega o evento que interessa (funcionalidade inteira sem teste) e ignora o
  ruído do dia a dia.
- **Negativa**: 78 % soa modesto ao lado de projetos que anunciam 95 %. A documentação precisa
  dizer, com todas as letras, **o que está fora da conta** — senão o número subestima o kit.
- **Débito registrado**: `app/Policies` a **23 %** é a única das três piores fatias que é lacuna
  de verdade. Vira alvo do step 6 (RQ-07).

---

## ADR-07: O driver liga por `-d` na linha de comando — e há **duas** armadilhas nisso

> Nasceu de dois defeitos reais encontrados ao atender RQ-14. Os dois têm o mesmo formato: o
> sintoma aponta para o lugar errado.

### Contexto

O ADR-01 decidiu ligar o PCOV por `-d pcov.enabled=1` na invocação, e não por `php.ini`, para que a
suíte do dia a dia não pague os **+44 %** de instrumentação. A decisão continua certa. O que esta
entrega descobriu é que **um `-d` não sobrevive a um relançamento de processo** — e o Pest relança
o processo em mais situações do que parece.

### Armadilha 1 — o `PcovRestarter` reconstrói a linha de comando e descarta o `-d`

`vendor/pestphp/pest/src/Restarters/PcovRestarter.php:44` relança o PHP quando o PCOV está
carregado **e o TIA está ligado**, para ajustar o `pcov.directory`. O comando que ele monta é:

```php
$command = array_merge(
    [PHP_BINARY, '-d', 'pcov.directory='.$projectRoot],
    array_values($arguments),
);
```

Repare: a linha é **reconstruída do zero**, com um único `-d`. O `-d pcov.enabled=1` original não
está em `$arguments` (que é o `argv`, começando pelo script) e some.

Este projeto liga o TIA por padrão — `tests/Pest.php:149`,
`pest()->tia()->defaultBranch('main')->locally()`. Logo, **toda** invocação local com PCOV passava
por esse relançamento.

**O sintoma manda para o lado errado.** O `--mutate` morre com *"Mutation testing requires code
coverage to be enabled"* enquanto, testado à mão no mesmo terminal,
`Pest\Support\Coverage::isAvailable()` devolve `true`. A investigação natural é procurar defeito na
instalação do PCOV — e não há nenhum.

**Decisão**: toda invocação de `--mutate` leva `--no-tia`. Não há perda: TIA escolhe *quais* testes
rodar a partir do diff, e mutação precisa rodar **todos** os cobridores de cada mutante. As duas
opções nunca fizeram sentido juntas.

### Armadilha 2 — no Windows o relançamento do mutante precisa de um `argv[0]` executável

`vendor/pestphp/pest/bin/pest:25` guarda `$_SERVER['argv']`, e
`vendor/pestphp/pest-plugin-mutate/src/MutationTest.php:91` usa esse array como comando do
`Symfony\Process` para cada mutante. O `argv[0]` é, portanto, **exatamente o que foi digitado**.

Digitando `php vendor/bin/pest`, o `argv[0]` vira `vendor/bin/pest` — um script `sh`, que o `cmd`
não executa. Cada mutante sai com código 1 em ~30 ms, e o plugin conta **qualquer** saída não-zero
como *mutante morto*.

**O sintoma é um score perfeito**: *"206 mutantes, 100 %, 3 s"* para uma suíte que leva minutos.
Este número já enganou um juiz cego do quality gate desta esteira.

**Decisão**: `pestw.cmd` na raiz — um poliglota `cmd`/PHP cuja primeira linha é um **rótulo** para
o `cmd` (`:<?php /*`) e a abertura de um comentário para o PHP. Rodando por ele, o `argv[0]` é um
`.cmd`, que o `cmd` sabe executar, e o `-d pcov.enabled=1` viaja para cada relançamento.

O rótulo é necessário: sem os dois-pontos, o `cmd` lê o `<` como redirecionamento de entrada a
partir de um arquivo chamado `?php` e morre com *"A sintaxe do nome do arquivo … está incorreta"*.
O preço é um `:` solto no começo da saída — o PHP imprime a linha 1, e não há como pedir que ele
ignore texto fora de tag.

### Como saber que o score é verdadeiro

Não por ele ser alto. Por **duração plausível** e por **existir sobrevivente em algum alvo**.

A evidência desta entrega: `app/Support/CustomizadorDaInstalacao.php` devolveu **225 mutantes,
4 não testados, score 98,22 %, 43,95 s**. Score **abaixo** de 100 % é a prova de que os mutantes
rodaram — pelo caminho falso, tudo morre e tudo dá 100 %.

### Consequências

- **Positiva**: a dimensão K da `feature-quality-gate`, que aparecia como *"não verificado"* em
  todo relatório deste kit, passa a ter número medido.
- **Negativa**: quem for mutar precisa lembrar de **dois** detalhes (`pestw.cmd` e `--no-tia`). Os
  dois estão no cabeçalho do `pestw.cmd`, com o sintoma de cada um, para que o próximo não gaste o
  mesmo tempo investigando o PCOV.
- **Risco**: `pestw.cmd` é específico de Windows. Em Linux e macOS o `vendor/bin/pest` já é
  executável e só o `--no-tia` continua valendo. Registrado no cabeçalho do arquivo.
