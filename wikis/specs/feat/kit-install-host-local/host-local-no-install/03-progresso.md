# Progresso — Host local no final do `kit:install`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Casos: `04-casos-de-teste.md`

## Estado

**Implementada.** Pendentes apenas as etapas de processo que o requisito reserva ao usuário
(PR-04: PR, merge, tag e release só com aprovação explícita).

| Passo do `01` | Estado | Evidência |
|---|---|---|
| 1 — `App\Support\HostLocal` | ✅ | `app/Support/HostLocal.php` |
| 2 — `.env` por `SubstituicaoEmArquivo::definirNoEnv()` + `config()` em memória | ✅ | `HostLocal::aplicarNoEnv()` · CT-18, CT-19 |
| 3 — ponto de chamada no `KitInstall` | ✅ | `KitInstall::oferecerHostLocal()`, chamado entre `desvincularDoSnyk()` e `banner()` · CT-01 |
| 4 — as duas perguntas | ✅ | `HostLocal::oferecer()` · CT-03, CT-05, CT-07, CT-09, CT-26 |
| 5 — documentação | ✅ | `docs/{pt,en}/comecar/instalacao-avancada.md`, nota cruzada em `dominio-local.md`, `README.md`, `README.en.md`, `CHANGELOG.md` · CT-34 |
| 6 — testes | ✅ | `tests/Kit/HostLocalTest.php` — 34 CTs, 57 casos com datasets |

### Verificação executada

| Comando | Resultado |
|---|---|
| `vendor/bin/pint --dirty --format agent` | `fixed` (só `tests/Kit/HostLocalTest.php`) |
| `php artisan test --compact tests/Kit/HostLocalTest.php` | **57 passaram**, 192 asserções |
| `php artisan test --compact` nos quatro arquivos vizinhos (Customizador, SiteDeDocumentacao, RedeDeDocumentacao, HelpersDeTeste) | **136 passaram**, 428 asserções |
| Falsificabilidade (`git stash push -u -- app/`) | **55 dos 57 reprovaram** (2 falhas + 53 erros). Os 2 que sobrevivem são as duas linhas de CT-34, que afirmam sobre documentação e não sobre código — é o esperado |

Pendente e **insubstituível**: o teste manual da janela de UAC numa instalação real. Nenhuma suíte
alcança essa camada, pelo mesmo motivo já registrado para o TTY do Composer.

## Desvios do Plano

> Cada um corrigiu a fonte (`01`, `02` ou `04`) no mesmo commit. Nenhum deles inverte uma premissa
> do `00`: as seis (P1…P6) continuam valendo como estavam escritas.

### D1 — o construtor tem cinco parâmetros, e não dois

O `01` (passo 1) previa `__construct(private string $base, private ?Closure $executor = null)`.
Faltavam três seams, e cada um é exigido por um caso que já estava escrito no `04`:

| Parâmetro | Caso que o exige | Por que não havia alternativa |
|---|---|---|
| `$hosts` | todos os de efeito | sem ele o caso escreveria no `hosts` do sistema |
| `$resolvedor` | CT-32 (`@premissa` P6) | a sonda de resolução real precisa responder "já resolve" sem depender da rede da máquina |
| `$so` | CT-15 (3 linhas) e CT-12 | `PHP_OS_FAMILY` é constante de **compilação**: sem injeção, cada máquina exercitaria uma linha da tabela de decisão e as outras duas ficariam sem matador |

`01` corrigido no passo 1.

### D2 — o comando de elevação ganhou `-Wait`

ADR-01 decide que **o oráculo é reler o arquivo**, e a própria ADR registra que
`Start-Process -Verb RunAs` é assíncrono. As duas coisas juntas são contraditórias: sem `-Wait` a
releitura acontece enquanto a janela do UAC ainda espera resposta, e o cadastro bem-sucedido seria
reportado como falha em toda instalação.

A correção é uma palavra no comando — e ela entra **na página**, não só no código, porque o comando
é o da documentação por cláusula literal de RQ-06 (CT-12 compara os dois textos). Atualizadas
`docs/pt/comecar/dominio-local.md` e `docs/en/comecar/dominio-local.md`, com a explicação do porquê.
ADR-01 corrigida.

### D3 — o gate de terminal é parâmetro, não um `if` no comando

O `01` (passo 3) previa `if (! $this->temTerminal()) { return; }` dentro de `oferecerHostLocal()`.
A nota de arnês do `04` (CT-02) já antecipava o problema: `temTerminal()` devolve `true` sob
`runningUnitTests()`, então esse `if` nunca teria o ramo negativo exercitado.

A decisão virou parâmetro — `HostLocal::oferecer(bool $interativo)` —, que é o desenho de
`CustomizadorDaInstalacao::perguntar($comando, $interativo)`. CT-02 exercita o ramo negativo com
oráculos concretos e amarra, por inspeção de fonte, que é `$this->temTerminal()` que chega ali.
`01` corrigido no passo 3.

### D4 — o arnês das perguntas é o FALLBACK, e não `Prompt::fake()`

O `04` (`### Fakes`) previa `Laravel\Prompts\Prompt::fake([...])` para os cenários que afirmam
sobre o texto da pergunta e sobre o default. **Não funciona no Windows**, que é justamente o sistema
desta etapa: `Prompt::prompt()` chama `checkEnvironment()`, que lança
`Prompt is not currently supported on Windows` sempre que o fallback está desligado
(`vendor/laravel/prompts/src/Prompt.php`). E desligar o fallback não é possível pela API pública:
`fallbackWhen($condicao)` faz `$condicao || static::$shouldFallback` — só sabe **ligar**.

O caminho que roda de verdade no Windows é o fallback, que
`ConfiguresPrompts::configurePrompts()` liga em `windows_os() || runningUnitTests()`. O arnês passou
a registrar o **próprio fallback** (`ConfirmPrompt::fallbackUsing()`, `TextPrompt::fallbackUsing()`),
e com isso ganhou um oráculo melhor que o de simulação de teclas: o objeto do prompt que o código
construiu, com `label`, `default` e `validate` legíveis. M7 (`default: true`) e M14 (a sugestão não
vira default) passaram a ser afirmados **diretamente**, e não pelo texto renderizado.

`Prompt::fake([])` continua no `beforeEach`, só para capturar em buffer a saída de `note()` — é o
que CT-15 afirma sobre a instrução do Unix. `04` corrigido na seção `### Fakes`.

### D5 — a validação também roda dentro de `processar()`

O `01` (passo 4) punha a validação na segunda pergunta. Ela ficou em `HostLocal::erroDoDominio()`,
usada **nos dois** lugares: no `validate:` do prompt (que é o que produz mensagem e repergunta) e na
entrada de `processar()`. Sem a segunda, CT-09 e CT-30 só existiriam atravessando o diálogo, e a
regra "nada do que foi digitado vira comando novo no executor elevado" ficaria dependente da
interface em vez de valer por construção. `01` corrigido no passo 4.

### D6 — CT-09 virou dois casos, e nenhum deles mede o arnês

O `Esquema` de CT-09 afirma seis coisas: mensagem, repergunta e quatro não-efeitos. As duas
primeiras só existem atravessando o diálogo; as quatro últimas ficam mais fortes fora dele. Ficaram:

- `[CT-09] recusa dominio malformado, com motivo e sem efeito` — as seis partições inválidas contra
  `processar()`, com os quatro não-efeitos **exatamente** como o cenário os escreve
- `[CT-09] exibe o motivo da recusa e pergunta o dominio de novo` — o diálogo, afirmando a mensagem
  que cita a entrada, o retorno da pergunta e que o domínio que vale no fim é o **segundo**

Nenhum ID novo foi criado: é o mesmo cenário, com o `Quando` partido onde o oráculo muda de
natureza.

## Reconciliação

- `.ai/rules/` — nada a registrar. As decisões desta entrega estão nas ADRs e valem para esta
  feature, não como restrição permanente de um diretório. A regra que ela **usou** já existe
  (`.ai/rules/testes.md`, helper cruzado em `tests/Pest.php`) e foi cumprida: `envDoTeste()` e
  `valorNoEnv()` saíram de `tests/Kit/CustomizadorDaInstalacaoTest.php` para `tests/Pest.php` no
  mesmo commit em que o segundo arquivo passou a usá-los
- Contadores dos readmes sincronizados (arquivos de teste 145 → 146, total 171 → 172; features
  especificadas 61 → 62), como `[CT-25]` de `tests/Kit/SiteDeDocumentacaoTest.php` exige
