---
title: "Qualidade de código"
description: "A maioria dos projetos Laravel para no level 5 ou 6. O kit roda no 8, com zero erros e sem baseline: não há @phpstan-ignore espalhado, não há…"
sidebar:
  order: 1
---
## PHPStan no level 8 — e por que isso é um ponto forte

A maioria dos projetos Laravel para no level 5 ou 6. O kit roda no **8, com zero erros e sem
baseline**: não há `@phpstan-ignore` espalhado, não há `phpstan-baseline.neon` escondendo dívida.

O que o 7 e o 8 pegam e o 6 não pega, na prática:

- **Nulo não checado** (o que o 8 acrescenta). `Filament::getCurrentPanel()` devolve `?Panel`;
  `auth()->user()` devolve `?User`; `Panel::getLoginUrl()` devolve `?string`. Até o level 7 você
  chama método neles e passa. No 8, precisa provar que existe.
- **Tipo largo do vendor entrando no seu código.** `session()` é `mixed`, `env()` é `bool|string`,
  os getters do Shield são `?array`. O 7 obriga a estreitar na **fronteira**, uma vez, em vez de
  torcer para o valor ser o esperado em cada uso.
- **`list<T>` vs `array<int,T>`.** `filter()` e `map()` preservam chave. Um array com buracos
  entregue onde se esperava lista é bug que só aparece no `json_encode` — vira objeto em vez de
  array, e o front quebra.

Subir de 6 para 7 expôs **29 erros reais** no kit, e um deles era bug latente de verdade: um
`Convite|null` com método chamado direto. Subir de 7 para 8 expôs mais **48**, todos de nulidade:
27 eram nulo impossível escrito com um tipo largo demais, 7 eram URL de painel que é `null` em
painel sem `->login()` — e ganharam o destino que o kit já usa, a raiz do painel —, 13 eram
invariante sem guarda, e 1 era anotação errada do Laravel. Todos corrigidos na origem — nenhum
silenciado.

> ### ⚠️ Ponto de atenção ao implementar no seu projeto
>
> **O level 8 vale para o código que você escrever também.** `composer test` roda
> `phpstan analyse` e reprova o build inteiro.
>
> O que mais aparece quando alguém começa a escrever no kit:
>
> | Você escreve | O que o PHPStan cobra |
> |---|---|
> | `auth()->user()->id` | prove que há usuário: `auth()->user()?->id`, ou um `if` antes |
> | `Filament::getTenant()->nome` | `?Model` — use `instanceof Tenant` como guarda |
> | `->filter()->all()` num `@return list<string>` | `array_values()` no fim |
> | `env('ALGUMA_COISA')` direto num `str_*` | `(string) env(...)`, ou `config()` com default tipado |
> | método sem tipo de retorno | declare o tipo; o kit exige em tudo |
>
> **Não resolva com `@phpstan-ignore` nem baseline.** O kit tem exatamente **quatro** exceções em
> `phpstan.neon`: um macro de vendor resolvido em runtime (`simpleLightbox()`), um ponto de extensão
> sem uso dentro do kit (`WidgetDinamico`), a anotação insatisfazível de `customMyProfilePage()` do
> filament-breezy e o `@return` com `null` que o `EnsureEmailIsVerified` do Laravel declara e nunca
> devolve — cada uma com escopo de arquivo, o motivo, as alternativas testadas e descartadas, e o
> teste que cobre o ponto de verdade. `tests/Kit/QualidadeDeCodigoTest.php` trava o inventário. Esse é o padrão:
> se precisar de exceção, ela vem com a justificativa e com o teste que a substitui.
>
> Se quiser afrouxar no seu projeto, é uma linha em `phpstan.neon`. Mas saiba o que está trocando:
> os 77 erros acima eram todos reais.

## FilaCheck: o lint que só entende de Filament

`composer filament:check` roda o `laraveldaily/filacheck` — 17 regras que o Pint e o PHPStan não
têm como ter: método depreciado da API do Filament, namespace errado de action, chamada que mudou
entre versões. Ele entra no `composer test` junto com o pint e o phpstan, então a CI reprova o
mesmo que a sua máquina.

Ao ser adotado, ele encontrou **7 problemas preexistentes** no próprio kit — seis métodos de teste
depreciados e um `ImageColumn::size()` — todos corrigidos.

## Rector: upgrade de major, não lint

O kit tem **quatro** ferramentas de qualidade, em quatro eixos — e só **três** estão no gate:

| Ferramenta | Eixo | Ao achar problema | Roda |
|---|---|---|---|
| **Pint** | estilo | **corrige** | sempre (gate) |
| **PHPStan** + larastan | tipos | reporta | sempre (gate), **level 8** |
| **FilaCheck** | API do Filament | reporta | sempre (gate) |
| **Rector** | reescrita de código | **muda semântica** | **sob demanda** |

`composer refactor:preview` e `composer refactor:apply` **não** estão no `composer test` — e isso é
deliberado.

**Para que o Rector serve aqui: upgrade de major.** Laravel 13 → 14, PHP 8.4 → 8.5. O `rector.php`
da raiz nasce **sem nenhum set ligado**, e traz, num bloco de comentário, qual set ligar em cada
caso. O fluxo é: descomentar o set → `composer refactor:preview` → ler o diff inteiro →
`composer refactor:apply` → `composer test` → desligar o set de novo.

**Por que ele fica fora do gate — foi medido, não opinado.** Com os sets de qualidade do Laravel
ligados, o Rector reescreveria **103 arquivos** deste projeto. Os três maiores motivos:

| Regra | Arquivos | O que propõe |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | `User::find()` → `User::query()->find()` |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | `: void` em closure |
| `AppToResolveRector` | 21 | `app()` → `resolve()` |

São opinião de estilo, não correção. Num kit cujo produto **é o código-exemplo legível**,
`User::find()` e `app()` são o idioma que o ecossistema lê sem parar.

E há um caso que fecha a questão. `CarbonToDateFacadeRector` propõe, no `InfraPanelProvider`:

```diff
- Carbon::now()->subDays(...)
+ Date::now()->subDays(...)
```

E isso **quebra**, por três fatos verificáveis:

1. `now()` **é** `Date::now()` — `Illuminate/Foundation/helpers.php:623`
2. O kit faz `Date::use(CarbonImmutable::class)` — `KitServiceProvider.php:57`
3. `FilamentExceptionsPlugin::modelPruneInterval()` exige `Carbon` **mutável**

O PHPStan level 7 **já reportou exatamente esse erro** quando o código usava `now()`. O
`Carbon::now()` explícito é a correção — e o Rector a desfaria.

> **Ferramenta de qualidade que reverte a correção de outra não é gate, é disputa** — e o build
> passaria a depender de qual das duas rodou por último.

`tests/Kit/QualidadeDeCodigoTest.php` fixa isso: falha se o Rector entrar no `composer test`, ou se
um set de qualidade for ligado.

**Upgrade de Filament é outra ferramenta.** **Não existe regra de Filament no
`driftingly/rector-laravel`** — busca por "filament" no pacote devolve zero. Não é lacuna: o
Filament distribui a **própria** ferramenta, também baseada em Rector.

```bash
composer upgrade:filament   # roda o vendor/bin/filament-v5 — o filament/upgrade já está no require-dev
```

Ela é mantida em lockstep com o framework — quem escreve as regras é quem quebra a API.

A leitura completa das quatro ferramentas está em
[`wikis/qualidade-de-codigo.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/qualidade-de-codigo.md).

## Os testes do kit

O kit traz sua própria suíte, isolada em `tests/Kit/` — acesso aos três painéis, telas de infra e admin de pé, invariantes da fundação (uuid, gates, auditoria) e o contrato da camada de IA.

Ela fica separada da sua de propósito: depois de um `kit:update` você quer saber se a **fundação** continua íntegra, sem esperar a suíte do seu negócio.

```bash
composer test:kit                     # em paralelo — ~3 min
composer test:kit:serial              # em série, para investigar falha
php artisan test --testsuite=Feature  # só os SEUS testes
```

**Roda em paralelo por padrão.** Medido nesta suíte: **12m26s → ~3min** (20 núcleos), mesmos casos e
mesmas asserções. Cada worker tem o próprio banco, porque o `phpunit.xml` usa SQLite `:memory:`, que
é por processo.

Se uma falha aparecer só em paralelo, é sinal de teste que depende de ordem ou de estado
compartilhado — `composer test:kit:serial` isola isso, e a diferença entre os dois é o diagnóstico.

> **Por que `--testsuite` e não `--group=kit`**: o `pest-plugin-browser` sobe o Playwright já na
> **coleta**, ao parsear qualquer arquivo com `visit()` — antes de qualquer filtro de grupo ser
> consultado. Num projeto recém-instalado, sem os browsers baixados, `--group=kit` morre em
> `PlaywrightNotInstalledException` sem rodar um único teste.

> **Argumento extra precisa de `--`**: `composer test:kit --parallel` é engolido em silêncio pelo
> Composer; o que funciona é `composer test:kit -- --parallel`. Como o paralelo já é o padrão, você
> não precisa disso — mas vale saber para qualquer outra flag.

Seus testes vão em `tests/Feature` e `tests/Unit`, como de costume — o kit não encosta neles.

## A cobertura de testes — e o que o número **não** inclui

**81 %** das linhas de `app/` — 8.141 de 9.976 statements, medido em 2026-09-25. O badge do
README vem daqui, e o CI reprova quando ele mente.

> **O número varia um pouco com o sistema operacional**, e o badge foi desenhado para absorver
> isso: numa medição de 2026-09-25, a mesma árvore deu **79,89 % no Windows e 79,82 % no Linux**
> — 7 statements de diferença,
> de código que só roda numa das plataformas. Os dois truncam para `79%`, então o badge confere
> nos dois. Se ele guardasse a casa decimal, o CI reprovaria toda medição feita na outra
> plataforma.

> Os números com casa decimal desta página são **datados**, não derivados: eles valem para a
> medição de 2026-09-25 e envelhecem. O único que tem guarda automática é o percentual inteiro,
> travado contra `.github/badges/cobertura.json` pelo `[CT-49]`.

```bash
composer test:coverage    # mede, grava o badge e aplica o piso — ~25 min, em série

# as duas metades, separadas, quando você já tem o Clover:
php -d pcov.enabled=1 vendor/bin/pest --testsuite=Kit,Tenancy --no-tia --coverage-clover=cobertura.xml
php artisan kit:cobertura cobertura.xml --min=78          # confere (é o que o CI faz)
php artisan kit:cobertura cobertura.xml --min=78 --write  # grava o badge
```

O `kit:cobertura` **viaja com o kit**: no seu projeto ele aplica o piso que você escolher, sobre o
`phpunit.xml` que você tiver. A parte do badge é a única que é só do kit — fora da árvore dele o
`.github/badges/` não existe, e o comando simplesmente não mexe em badge nenhum.

### Os três badges de teste do README, e o que cada um garante

| Badge | De onde sai | Quem impede de envelhecer |
|---|---|---|
| **cobertura** | `.github/badges/cobertura.json`, gerado por `composer test:coverage` | o job `cobertura` do CI, que remede e reprova se o JSON mentir |
| **casos de teste** | contagem de blocos `it()`/`test()` em `tests/` | o `[CT-50]`, que **recalcula da árvore** a cada rodada da suíte |
| **PHPStan** | o `level:` do `phpstan.neon` | o `[CT-50]`, pelo mesmo mecanismo |

Os dois últimos não dependem de rodar nada — são deriváveis por leitura, então a guarda calcula a
verdade sozinha em milissegundos. O de cobertura depende dos ~25 min de medição local (52 min no
runner do CI), e por isso vive
num arquivo versionado com conferência no CI.

> **O badge de casos conta blocos escritos, não casos executados.** Um `it()` com `->with()` de
> cinco linhas vira cinco casos na execução — por isso o número do badge é menor que o que o Pest
> imprime. É a grandeza que dá para derivar sem rodar a suíte, e é o que o badge quer dizer:
> quantos cenários foram escritos à mão.

### Por que em série, e por que demora

`--parallel --coverage` **não existe**: o Pest imprime o *usage* do paratest, e
`artisan test --parallel --coverage-clover` roda e não gera arquivo nenhum. A medição é serial por
construção, e o custo foi medido dos dois lados:

| Onde | Em paralelo, sem cobertura | Em série, com cobertura |
|---|---:|---:|
| máquina local, 16 núcleos | ~3 min | **25 min** |
| runner do CI, 4 vCPU | ~6 min | **52 min** |

Por isso o job roda em `main` e por disparo manual, não em toda PR: **52 minutos** é o número que
importa, porque é onde a conta é paga, e cobrar isso de toda PR multiplicaria por nove o tempo de
CI do repositório.

O driver é o **PCOV**, não o Xdebug: para cobertura de **linha** o Xdebug não acrescenta nada e
custa muito mais. Ele fica desligado no `php.ini` (`pcov.enabled=0`) e liga só na invocação, para
que a suíte do dia a dia não pague os **+44 %** de instrumentação.

### O número é um piso, não um teto

Três coisas ficam fora da conta, e ler o número sem saber disso leva à conclusão errada:

| Fora da medição | Por quê |
|---|---|
| `tests/Browser` e `tests/BrowserTenancy` | rodam contra um servidor em **outro processo**; o PCOV instrumenta o processo do Pest |
| Os testes de instalação | rodam `composer create-project` de verdade, também em outro processo |
| `tests/Unit` e `tests/Feature` | são **seus**, não do kit — têm um arquivo de exemplo cada |

O efeito é visível na decomposição: **`app/Console` aparece a 34 %** e é, na prática, o código mais
exercitado do kit — os testes de instalação executam `kit:install` inteiro, de fora. Lê-lo como
*"os comandos não têm teste"* seria exatamente o erro que esta seção existe para evitar.

A lacuna **real** é outra: `app/Policies`, a **23 %**. As policies são exercitadas indiretamente
(a tela nega, o teste vê negado), mas o `Gate` curto-circuita antes do método em boa parte dos
casos — e autorização é onde defeito silencioso custa mais caro.

### O piso é 78 %, e ele não é folclore

Com 9.976 statements, **1 ponto percentual vale ~100 linhas**. Um bug corrigido, um método novo
ou um refactor não movem o número; para cair os 1,89 pp de folga é preciso que quase 190
statements entrem sem teste — que é o único evento que o piso existe para pegar.

E cobertura de linha **não** mede se a suíte detecta defeito: ela mede o que foi **executado**, não
o que foi **verificado**. Quem responde isso é o mutation score, e para ele o kit usa
`pest --mutate` (em Windows, pelo `pestw.cmd` da raiz — o cabeçalho do arquivo explica por quê).

## O mutation score, e por que ele responde o que a cobertura não responde

Cobertura mede **o que foi executado**. Mutation testing mede **o que foi verificado**: ele altera o
código de propósito — troca um `<` por `<=`, apaga uma chamada, inverte um `&&` — e pergunta se
algum teste fica vermelho. Mutante que sobrevive é um defeito que ninguém detectaria.

```bash
# Linux e macOS
vendor/bin/pest tests/Kit/AlgumTest.php --mutate --path=app/Support/X.php --covered-only --no-tia

# Windows — pelo lançador da raiz, e o motivo está no cabeçalho dele
cmd /c pestw.cmd tests/Kit/AlgumTest.php --mutate --path=app/Support/X.php --covered-only --no-tia
```

Medições desta árvore, com o comando, a duração e a plataforma — sem os três, um score não é
auditável:

| Alvo | Mutantes | Score | Duração | Plataforma |
|---|---:|---:|---:|---|
| `app/Support/CustomizadorDaInstalacao.php` | 225, sendo 4 não testados e 0 sobreviventes | **98,22 %** | 42,96 s | Windows, PHP 8.4.25, PCOV |
| `app/Console/Commands/KitCobertura.php` | 158, sendo 4 não testados e 0 sobreviventes | **97,47 %** | 38,00 s | Windows, PHP 8.4.25, PCOV |
| `app/Policies/` (as 16) | **4** | 100 % | 0,19 s | Windows, PHP 8.4.25, PCOV |

**Sobrevivente não é o mesmo que não testado.** Sobrevivente é mutante que um teste **executou** e
não percebeu — defeito que passaria. Não testado é mutante numa linha que **nenhum** teste executa.
As duas medições acima têm zero sobreviventes; os não testados, nomeados para quem quiser fechá-los:

- `app/Support/CustomizadorDaInstalacao.php:470` — quatro mutações na mesma linha
- `app/Console/Commands/KitCobertura.php:57`, `:59` (o relatório ausente) e `:198` (o relatório
  ilegível) — saídas antecipadas que a cobertura por linha não atribui a nenhum teste, embora
  `[CT-12]` e `[CT-13]` de `tests/Kit/KitCoberturaTest.php` afirmem as duas mensagens; o motivo
  não foi investigado

O `KitCobertura` saiu de **89,24 %** (17 não testados, medido em 2026-09-25) para **97,47 %** em
2026-09-26, quando a reconciliação da wiki `cobertura-de-testes` escreveu os cenários que faltavam.

> **Score alto e instantâneo é sintoma, não resultado.** No Windows, rodar por `vendor/bin/pest`
> em vez do lançador devolve **100 % em segundos** para uma suíte de minutos — o plugin relança
> `argv[0]`, o `cmd` não executa um script `sh`, e toda saída não-zero é contada como mutante
> morto. A linha das policies acima é honesta por outro motivo: **4 mutantes para 222 statements**,
> porque código de pura delegação não tem operador para mutar. Ali o instrumento não é a mutação —
> é a asserção de que cada policy confere a permissão do **próprio** modelo.

## Os níveis de qualidade que o kit **não** adotou, e por quê

Levantamento de 2026-09-25. Cada candidato foi **rodado contra esta arvore** antes de entrar na
tabela — porque toda ferramenta de qualidade parece boa no abstrato, e o que separa as que valem é
quanto ruído elas produzem **neste** código. Um analisador que acusa 594 vezes não é mais rigoroso
que um que acusa 48; é um que ninguém vai ligar.

| Nível | Medido aqui | Veredito |
|---|---|---|
| `arch()->preset()->php()` | passa limpo, 53 asserções, 6,5 s | **adotado** |
| `arch()->preset()->security()` | 1 classe acusada (3 `exec()` no `KitInstall`, com constante) | **adotado**, com a exceção declarada e confinada |
| **PHPStan level 8** | **48 erros** | roadmap, prioridade alta — cabe numa release própria |
| PHPStan level 9 / `max` | **474** e **594** erros | **não** — é precipício, não degrau |
| `arch()->preset()->laravel()` | reprova na 1.ª classe (controller com método fora do conjunto REST) | **não** — a convenção do kit é deliberada |
| `arch()->preset()->strict()` | **163 de 222** classes não são `final` | **não** — `AgenteBase` existe para ser estendida |
| `arch()->preset()->relaxed()` | proíbe método `private`; **54** arquivos usam | **não** — contraria o estilo do kit |
| `declare(strict_types=1)` | **98 de 240** arquivos | roadmap — não é falta de rigor, é **inconsistência**, e exige decidir o lado |
| `composer-require-checker` + `composer-unused` | não instalados | roadmap, prioridade alta — respondem *"das 58 dependências, quais são usadas?"* |
| Branch coverage (Xdebug) | Xdebug 3.5.3 instalado | roadmap — é a única razão de o Xdebug existir aqui |
| `pest-plugin-type-coverage` | não instalado | roadmap |
| Deptrac | não instalado | **não** — o kit não tem fronteira de camada a defender |

Os três critérios de corte, aplicados a cada linha: **(a)** responde a uma lacuna já medida neste
projeto? **(b)** o custo de adoção é conhecido? **(c)** o que ele pega não é pego por nada que já
existe? Os itens de roadmap passaram em (a) e (c) e esperam orçamento; os recusados falharam em
(a) ou conflitam com convenção escrita.

> Os números acima envelhecem — `48 erros no level 8` é de 2026-09-25 e muda a cada release. Quem
> for executar um item de roadmap **remede antes de estimar**.

## As imagens do README saem de um teste

As capturas de tela deste README **não são feitas à mão**. Elas nascem de
`tests/BrowserTenancy/CapturaDeArteTest.php`, na mesma suíte que prova que as telas funcionam:

```bash
composer art
```

O comando navega de verdade, salva os PNG, publica em `art/`, gera as thumbs de `art/thumbs/` e
monta o GIF do fluxo. É o único jeito que encontramos de a documentação não envelhecer: ninguém
refaz quinze imagens a cada release, e o resultado é um README mostrando uma versão do kit que
não existe mais.

| Etapa | O que faz |
|---|---|
| `npm run build` + `view:cache` | pré-requisitos duros da suíte de navegador |
| `KIT_ART=1 pest tests/BrowserTenancy/CapturaDeArteTest.php` | navega e escreve os PNG em `tests/Browser/Screenshots/` (caminho fixo do plugin) |
| `php artisan kit:arte` | copia para `art/`, redimensiona as thumbs e monta o GIF |

Três decisões que valem saber antes de mexer:

- **`KIT_ART=1` não é enfeite.** É variável só de teste — não existe em `config/` nem no
  `.env.example`; o próprio arquivo de teste a lê. Sem a variável o arquivo é *skipped*. Ele escreve em `art/`, e uma
  suíte de CI que suja a árvore de trabalho é pior que uma suíte lenta.
- **As medidas são fixas: 1400x875 no cheio, 760x475 na thumb.** É a proporção das imagens que já
  estavam no `art/`, e a galeria põe duas thumbs por linha — thumb com outra proporção desalinha a
  tabela.
- **O GIF é slideshow, montado com `ffmpeg` a partir de três quadros.** O plugin de navegador não
  grava vídeo, e quadro capturado é o que dá para reproduzir de forma determinística. Sem `ffmpeg`
  no PATH o comando avisa e segue: as imagens estáticas já foram publicadas.

Precisa só refazer as thumbs, sem repetir a navegação? `php artisan kit:arte --sem-gif`.

## Como os testes são pensados: varredura SFDIPOT

Toda feature nova passa por uma varredura **SFDIPOT** antes de virar caso de teste. A heurística, criada por James Bach, divide o sistema em sete perspectivas para que nenhuma dimensão seja esquecida na especificação:

| Letra | Perspectiva | O que cobre |
|---|---|---|
| **S** — Structure | Estrutura | Código, arquivos, componentes físicos ou lógicos |
| **F** — Function | Função | O que o software faz, suas funcionalidades |
| **D** — Data | Dados | O que o sistema processa, armazena ou manipula |
| **I** — Interfaces | Interfaces | Telas, APIs, integrações, entradas e saídas |
| **P** — Platform | Plataforma | Sistema operacional, hardware ou ambiente onde roda |
| **O** — Operations | Operações | Como o usuário ou administrador usa o sistema no dia a dia |
| **T** — Time | Tempo | Concorrência, desempenho, histórico ou a sequência dos eventos |

O benefício está em não derivar os testes só do "caminho feliz". O que escapa raramente é mais um caso a mais — geralmente é uma dimensão inteira (dados, plataforma, tempo, operações) que ninguém lembrou de cobrir. A varredura força essa revisão no plano, antes do código existir.

