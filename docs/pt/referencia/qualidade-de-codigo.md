---
title: "Qualidade de código"
parent: "Referência"
grand_parent: "Português"
nav_order: 1
---

# Qualidade de código

## PHPStan no level 7 — e por que isso é um ponto forte

A maioria dos projetos Laravel para no level 5 ou 6. O kit roda no **7, com zero erros e sem
baseline**: não há `@phpstan-ignore` espalhado, não há `phpstan-baseline.neon` escondendo dívida.

O que o level 7 pega e o 6 não pega, na prática:

- **Nulo não checado.** `Filament::getCurrentPanel()` devolve `?Panel`; `auth()->user()` devolve
  `?User`. No level 6 você chama método neles e passa. No 7, precisa provar que existe.
- **Tipo largo do vendor entrando no seu código.** `session()` é `mixed`, `env()` é `bool|string`,
  os getters do Shield são `?array`. O 7 obriga a estreitar na **fronteira**, uma vez, em vez de
  torcer para o valor ser o esperado em cada uso.
- **`list<T>` vs `array<int,T>`.** `filter()` e `map()` preservam chave. Um array com buracos
  entregue onde se esperava lista é bug que só aparece no `json_encode` — vira objeto em vez de
  array, e o front quebra.

Subir de 6 para 7 expôs **29 erros reais** no kit, e um deles era bug latente de verdade: um
`Convite|null` com método chamado direto. Todos corrigidos na origem — nenhum silenciado.

> ### ⚠️ Ponto de atenção ao implementar no seu projeto
>
> **O level 7 vale para o código que você escrever também.** `composer test` roda
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
> **Não resolva com `@phpstan-ignore` nem baseline.** O kit tem exatamente **duas** exceções em
> `phpstan.neon`: uma para um macro de vendor resolvido em runtime (`simpleLightbox()`), outra para
> a anotação insatisfazível de `customMyProfilePage()` do filament-breezy — cada uma com o motivo, as
> alternativas testadas e descartadas, e o teste que cobre o ponto de verdade. Esse é o padrão:
> se precisar de exceção, ela vem com a justificativa e com o teste que a substitui.
>
> Se quiser afrouxar no seu projeto, é uma linha em `phpstan.neon`. Mas saiba o que está trocando:
> os 29 erros acima eram todos reais.

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
| **PHPStan** + larastan | tipos | reporta | sempre (gate), **level 7** |
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

