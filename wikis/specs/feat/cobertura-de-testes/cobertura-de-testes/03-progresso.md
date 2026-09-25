# Progresso — Cobertura de testes medida, com meta e badge

## 1. Instalar o driver no PHP local (RQ-03)

- [x] Build correto identificado — `php_pcov-1.0.12-8.4-nts-vs17-x64.zip` casa com `PHP 8.4.25 (cli) NTS Visual C++ 2022 x64`, API `20240924`, 2026-09-24
- [x] `php_pcov.dll` em `C:\php-8.4.25\ext\` — 27.136 bytes, 2026-09-24
- [x] Backup do `php.ini` — `C:\php-8.4.25\php.ini.bak-antes-do-pcov`, 2026-09-24
- [x] `extension=pcov` + `pcov.enabled=0` no `php.ini`, com o comentário do porquê — 2026-09-24
- [x] Verificado: `php -i | grep '^pcov'` → `PCOV support => Disabled`, `PCOV version => 1.0.12`, 2026-09-24
- [x] Verificado que **sem** a flag o Pest recusa — `vendor/bin/pest tests/Unit --coverage` devolve `ERROR No code coverage driver is available`, 2026-09-24

## 2. Confirmar o denominador (RQ-02)

- [x] `phpunit.xml` **já** declarava `<source><include><directory>app</directory></include></source>` — nada alterado, 2026-09-24
- [x] Denominador medido pelo próprio relatório: **242 arquivos, 9.976 statements**, 2026-09-25

## 3. Medir: número e tempo (RQ-01, RQ-04)

- [x] `pest --parallel --coverage` **não funciona** — imprime o *usage* do paratest, 2026-09-24
- [x] `artisan test --parallel --coverage-clover=arquivo` roda e **não gera o arquivo**, 2026-09-24
- [x] `pest --coverage-clover=arquivo` em **série** gera (764 KB numa amostra), 2026-09-24
- [x] Overhead do PCOV isolado — mesma amostra: **3,08 s sem** × **4,45 s com** = **+44 %**, 2026-09-24
- [x] **Medição final** — `php -d pcov.enabled=1 vendor/bin/pest --testsuite=Kit,Tenancy --no-tia --coverage-clover=cobertura.xml`, 2026-09-25:
  **79,89 % de linha (7.970 / 9.976 statements)**, 62,67 % de métodos (752 / 1.200),
  **1.508 s = 25 min 08 s**, 2.942 testes, 11.344 asserções, 0 falhas
- [x] Decomposição por diretório e piores arquivos — tabela no `## Notas de Implementação`
- [x] **12 de 242 arquivos com zero cobertura**, somando **503 statements**
- [x] Três medições ao todo, e as duas primeiras ficaram obsoletas por motivo declarado: a de
  79,79 % era anterior ao merge do #100 e aos testes novos; a de 78,84 % é a que expôs o
  `KitCobertura` descoberto

## 4. Escolher a meta (RQ-06)

- [x] **Meta: piso de 78 %** no `--min`, com o medido em **79,89 %** — ADR-06
- [x] Folga declarada: **1,89 pp ≈ 189 statements**. Com 9.976 statements, 1 pp vale ~100 linhas —
  o piso não se mexe por mudança pequena; só cai se uma funcionalidade inteira entrar sem teste

## 5. Comando e job de CI (RQ-05, RQ-09)

- [x] `composer test:coverage` — mede, grava o badge e aplica o piso
- [x] **`php artisan kit:cobertura`** — lê o Clover, aplica `--min` e escreve/confere o badge
- [x] `.github/badges/cobertura.json` gerado: `{"message": "79%", "color": "green"}`
- [x] Job `cobertura` no `ci.yml`, com `coverage: pcov`
- [x] **Os dois caminhos de reprovação verificados à mão**: com `--min=95` sai 1; com o JSON
  adulterado para `91%` sai 1 dizendo o que rodar; restaurado, sai 0
- [x] **Frequência decidida**: `push` em `main` + `workflow_dispatch`, **não** em PR —
  `if: github.event_name != 'pull_request'`. A medição é serial por construção (ADR-02), e o custo
  foi medido dos dois lados: **25 min local** (16 núcleos) e **52 min no runner** (4 vCPU), contra
  ~3 min e ~6 min da suíte paralela. O número que decide é o do runner, porque é onde a conta é
  paga — cobrar isso de toda PR multiplicaria por **nove** o CI do repositório
- [x] **O job foi disparado à mão antes do merge** (`gh workflow run ci.yml --ref …`), porque job
  de CI que nunca rodou é afirmação, não garantia. Passou, e trouxe dois dados novos:
  - **52 min**, contra os ~27 min que eu havia estimado a partir do número local. A estimativa
    estava errada e a documentação a repetia em quatro lugares
  - **a cobertura varia com a plataforma**: mesma árvore, **79,89 % no Windows × 79,82 % no
    Linux** — 7 statements de código que só roda num dos dois. Os dois truncam para `79%`, então
    o badge confere nos dois. **A decisão do ADR-04 de guardar o inteiro, tomada por outro motivo
    (ruído da terceira casa), é o que impede o CI de reprovar toda medição feita na plataforma
    oposta** — teria sido um defeito difícil de diagnosticar
- [x] O badge guarda o percentual **inteiro truncado**, não o real — senão a guarda reprovaria por
  ruído da terceira casa e viraria alarme falso

## 6. Fechar lacuna real até a meta (RQ-07)

- [x] Arquivos de `app/` ordenados por cobertura ascendente — tabela em `## Notas de Implementação`
- [x] **Meta atingida, e ela sobreviveu a um susto**: o medido (79,89 %) está acima do piso
  (78 %). Entre uma medição e outra ela caiu para 78,84 % por causa do `KitCobertura` sem teste,
  e voltou quando o teste dele foi escrito — não quando o piso foi afrouxado. A meta
  nasceu da medição, então "fechar a lacuna" seria inverter a ordem — escolher um número e depois
  escrever teste para alcançá-lo é como se produz teste que não prova nada
- [ ] **`app/Policies` a 23 % vira débito declarado**, não tarefa desta entrega. É lacuna real, e
  merece cenário escrito, não linha perseguida — ver o roadmap

## 7. Documentação e badge (RQ-08, RQ-09)

- [x] Seção de cobertura em `docs/pt/referencia/qualidade-de-codigo.md` e no par em inglês —
  **seção**, e não página nova: o assunto já tinha casa, e o índice não precisou mudar
- [x] Badge (*endpoint* do shields.io sobre o JSON versionado) nos dois READMEs
- [x] Linha na tabela `Qualidade` dos dois READMEs
- [x] **`[CT-49]`** guarda a linha nova contra o JSON do badge — a corrente fica fechada:
  medição → JSON (guardado pelo CI) → README (guardado pelo CT). Verificado com mutante: trocar
  `79 %` por `88 %` reprova
- [x] `[CT-25]` (contagem de arquivos) e o caso das specs acusaram a entrega e foram atualizados:
  **157 / 184** arquivos e **69** specs
- [x] `CHANGELOG.md`

## 8. Xdebug e o custo de carregá-lo (RQ-11, RQ-12)

- [x] Xdebug 3.5.3 (`php_xdebug-3.5.3-8.4-nts-vs17-x86_64.dll`) em `C:\php-8.4.25\ext\`, 2026-09-24
- [x] Backup — `C:\php-8.4.25\php.ini.bak-antes-do-xdebug`
- [x] `zend_extension=xdebug` + `xdebug.mode=off` + `xdebug.start_with_request=no`
- [x] **Convivência com o PCOV verificada empiricamente** — `-d pcov.enabled=1` expõe
  `pcov\collect`; `-d xdebug.mode=coverage` acende `Coverage ✔ enabled`. A frase do README do PCOV
  (*"interoperability with Xdebug is not possible"*) vale para **coletar junto**, não para coexistir
- [x] **RQ-12 medido, máquina ociosa** — 3 arquivos de `tests/Kit`, 3 rodadas alternadas,
  `php -c` com um `php.ini` sem o bloco do Xdebug contra o `php.ini` normal:

  | Rodada | Sem Xdebug | Xdebug carregado + `mode=off` |
  |---|---:|---:|
  | 1 | 10,48 s | 10,53 s |
  | 2 | 10,56 s | 10,49 s |
  | 3 | 10,50 s | 10,48 s |

  **Mediana 10,50 s × 10,49 s.** A diferença está dentro do ruído, e em duas das três rodadas o
  lado *com* Xdebug foi mais rápido. **Carregar o Xdebug com `mode=off` não custa tempo medível** —
  é o comportamento projetado do Xdebug 3, em que `off` desliga os *hooks*, não só a saída.
- [x] **Mas não é de graça**: ele mudou o comportamento do Pest, e isso está no ADR-07

## 9. Níveis de qualidade (RQ-13, RQ-14)

- [x] Todo candidato **rodado contra o kit** antes de entrar na recomendação — tabela no ADR-05
- [x] **Achado**: `pestphp/pest-plugin-arch` está instalado e tem **zero** uso (`grep -rn "arch()" tests/` → `0`)
- [x] Medido: preset `php` **passa limpo** (53 asserções, 6,5 s); preset `security` acusa **1** violação
- [x] Medido: PHPStan **level 8 = 48 erros**, **level 9 = 474**, **max = 594** (level 7 atual = 0)
- [x] Medido: `declare(strict_types=1)` em **98 de 240** arquivos de `app/`
- [x] Medido: **163 de 222** classes de `app/` não são `final` (preset `strict`)
- [x] **RQ-14a — `--tia` funciona**: `php -d pcov.enabled=1 vendor/bin/pest --tia …` responde
  `TIA does not apply to partial runs`, que é o plugin **ativo**; antes do PCOV ele nem carregava
- [x] **RQ-14b — mutation score é real**: `CustomizadorDaInstalacao` devolveu
  **225 mutantes, 4 não testados, score 98,22 %, 43,95 s**. Score **abaixo** de 100 % com duração
  plausível é a prova; pelo caminho falso tudo morre e tudo dá 100 %
- [ ] Adotar os presets `php` e `security` em `tests/Kit/`
- [ ] Registrar no `wikis/roadmap.md` os itens medidos que não entram

## Testes

- [ ] `tests/Kit/CoberturaDeTestesTest.php` — CTs conforme o `04`

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --test --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `vendor/bin/filacheck`
- [ ] `composer test:coverage` — percentual, statements e duração colados
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — contra a baseline de `main`: **2.904 testes, 2.901 passaram, 3 pulados, 11.232 asserções, 0 falhas** (2026-09-24)
- [ ] `.github/badges/cobertura.json` bate com o medido
- [ ] Badge renderiza no GitHub — conferido na aba do PR
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 6.5)**
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `general.md` | `composer.json` | — | a preencher no step 7 |
| `testes.md` | `tests/**` | — | a preencher no step 7 |
| `config.md` | `config/**` | **n.a.** | a entrega não toca `config/` |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| *"o denominador precisa ser escolhido"* (premissa do `00`, RQ-02) | `phpunit.xml` **já** declara `<source>app</source>` | virou ADR-03: a premissa era fato, e o passo 2 passou a ser só verificação |
| *"`--parallel --coverage` deve funcionar"* (expectativa implícita) | imprime o *usage* do paratest | virou ADR-02, com as três formas medidas |
| *"badge exige serviço externo"* (ambiguidade do `00`, RQ-09) | repo é **público** (Codecov dispensaria token), mas o CI **nunca commitou de volta** — `grep 'git commit\|contents: write'` nos workflows volta vazio | virou ADR-04: JSON versionado + guarda, sem serviço e sem permissão de escrita |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | a rodar | — | — |

### Revisão de código do diff (step 6.5) — sub-agente cego ao PRD

Recebeu o diff, a tabela de eixos e nada mais: nem `00`, nem `01`, nem o raciocínio de quem
implementou. **13 achados**, todos tratados.

| # | Achado | Sev. | O que foi feito |
|---|---|---|---|
| RD-01 | `composer test:coverage` viaja no dist e apontava para `.github/cobertura.php`, que é `export-ignore` | **Blocker** | virou `php artisan kit:cobertura` — viaja pelas duas rotas e serve ao projeto instalado |
| RD-02 | `--min=abc` (ou `--mim=78`) desligava o gate **em silêncio** e imprimia *"o piso foi respeitado"* | Major | `is_numeric` + faixa 0–100 + o Artisan recusando opção desconhecida. Os 6 caminhos de saída verificados à mão |
| RD-03 | a mensagem arredondava (`%.0f`) o piso que a comparação truncava | Minor | imprime o piso como veio: `--min=79.9` diz `79.9%` |
| RD-04 | o badge era comparado **byte a byte**: reindentar o JSON reprovava com *"diz 79% e o medido é 79%"* | Minor | compara `message` e `color` decodificados |
| RD-05 | o `[CT-49]` afirmava fechar uma corrente cujo elo do meio **não roda em PR** | Major | a alegação foi corrigida no docblock e no CHANGELOG, com as duas consequências escritas |
| RD-06 | os oito números novos das docs não tinham guarda nenhuma | Major | o `[CT-49]` passou a cobrir o percentual das docs; o resto virou **número datado**, com a ressalva no topo da seção |
| RD-07 | o job media por **caminho**, e todo o resto do CI usa `--testsuite` | Minor | `--testsuite=Kit,Tenancy --no-tia` — o `--no-tia` explícito porque o caminho literal desligava o TIA por efeito colateral |
| RD-08 | o `pestw.cmd` trocava o printer e engolia `--compact` | Major | causa achada: `laravel/pao` apaga o `COLLISION_PRINTER` e escolhe driver por `basename($argv[0])`, que aqui **precisa** ser `pestw.cmd`. `set PAO_DISABLE=1` resolve |
| RD-09 | os SKILLs traziam uma versão **diferente e quebrada** do lançador | Major | as 5 cópias (`.ai`, `.claude`, `.agents`, `.junie`, `.cursor`) atualizadas, com o porquê de cada detalhe |
| RD-10 | o `:` em stdout não é cosmético: envenena consumidor programático | Minor | dito com todas as letras no cabeçalho: *"a saída não é consumível por parser"* |
| RD-11 | `2.880 testes` no CHANGELOG contra `2.907` no README | Minor | remedido |
| RD-12 | *"~3 min"* nas docs contra *"~6 min"* no CI para a mesma grandeza | Cosmético | as docs passaram a citar o número **do runner**, que é onde a conta é paga |
| RD-13 | o `ignoring()` liberava **20 funções** dentro do `KitInstall`, e o docblock falava de **1** `exec` (são 3) | Minor | caso companheiro novo confina a exceção: só `exec`, e só com `self::REPOSITORIO`. Verificado com dois mutantes |

**Hipótese levantada e rejeitada pelo próprio revisor** (HR-01): o caminho
`cobertura-de-testes/cobertura-de-testes/` pareceria referência quebrada, e é a convenção
dominante — 40 das 69 specs repetem o slug. Registrada porque quase virou achado.

**O que o revisor não pôde verificar**, e está declarado: os percentuais (não rodou os 27 min), o
comportamento do job em `ubuntu-latest`, e o mutante do `exec` (ele não pode escrever arquivo).

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 0–3 | **Sem despacho** — captura verbatim do requisito, instalação do driver no PHP da máquina e as medições de tempo | o da sessão | — | `00`, `01`, `02` escritos; PCOV instalado e verificado | — |

> A instalação do driver e as medições rodaram em linha por serem **interação direta com a máquina
> do usuário** e **medição cujo resultado a sessão precisa ler para decidir** — as duas exceções
> declaradas em `## Rodam em linha, sem despacho`. Os gates que exigem cegueira (6.5 e 8) vão para
> sub-agente, como manda a skill.

## Blockers

- Nenhum até aqui.

## Desvios do Plano

<!-- Preenchido durante a implementação. -->

## Notas de Implementação

- **27 % da suíte não pode contribuir para a cobertura de `app/`, por construção.** Medido:
  **415 de 1.517** blocos `it()` vivem em arquivos que leem o disco (`Finder::create`,
  `File::get(base_path…)`, `file_get_contents(base_path…)`) — são testes de **arquitetura** e de
  **documentação**, que inspecionam código em vez de executá-lo.

  Isso não os desqualifica: foi um deles que achou, nesta mesma semana, que `tests/Browser` nunca
  chegava a quem roda `kit:update`. Mas significa que **o número de cobertura mede uma das duas
  naturezas da suíte**, e lê-lo como "quão bons são os testes" seria erro de interpretação. A
  documentação do step 7 precisa dizer isso com todas as letras.

### Decomposição da medição de 2026-09-25

| Diretório | Coberto | Total | % | Arq. |
|---|---:|---:|---:|---:|
| `app/Models` | 527 | 531 | **99,2 %** | 7 |
| `app/Providers` | 1.302 | 1.337 | **97,4 %** | 6 |
| `app/Http` | 365 | 378 | **96,6 %** | 9 |
| `app/Support` | 1.101 | 1.242 | 88,6 % | 35 |
| `app/Filament` | 3.777 | 4.466 | 84,6 % | 126 |
| `app/Listeners` | 37 | 44 | 84,1 % | 1 |
| `app/Notifications` | 41 | 51 | 80,4 % | 3 |
| `app/Ai` | 182 | 248 | 73,4 % | 17 |
| `app/Services` | 19 | 31 | 61,3 % | 1 |
| **`app/Console`** | 377 | 1.120 | **33,7 %** | 9 |
| **`app/Livewire`** | 42 | 157 | **26,8 %** | 2 |
| **`app/Policies`** | 51 | 222 | **23,0 %** | 16 |
| `app/Data`, `app/Observers`, `app/Settings`, `app/Traits` | 149 | 149 | **100 %** | 10 |

**Os três piores têm causas diferentes, e só um deles é lacuna de verdade:**

1. **`app/Console` a 33,7 %** — `KitUpdate` (5/330), `KitInstall` (0/204), `KitTenancy` (0/119) e
   `KitArte` (0/61). Estes **são** testados, e pesadamente: os 4 testes locais de instalação
   rodam `composer create-project` de verdade, em processo separado. O PCOV mede o processo do
   Pest, então nada disso aparece. **Não é código sem teste; é teste fora do medidor.**

   O salto de 24,3 % para 33,7 % nesta última medição não veio de teste novo de comando: veio do
   `KitCoberturaTest`, que cobriu o comando que esta entrega criou.
2. **`app/Policies` a 23 %** — aqui o número **é** o que parece. As policies são exercitadas
   indiretamente (a tela nega, o teste vê negado), mas o `Gate` curto-circuita antes do método em
   boa parte dos casos. É a lacuna com melhor razão entre risco e esforço: autorização é
   exatamente onde um defeito silencioso custa caro.
3. **`app/Livewire` a 27 %** — 113 dos 157 statements são do `AssistenteChatWidget`, que fala com
   provedor de IA. Cobrir exige fake do provedor. Lacuna real, custo médio.

### O comando de medir cobertura foi o maior buraco de cobertura da entrega

Tratar o RD-01 criou `app/Console/Commands/KitCobertura.php` — **105 statements**, e nenhum teste.
Os seis caminhos de saída tinham sido verificados **à mão** e a verificação não foi versionada.

A remedição cobrou a conta na hora: **79,79 % → 78,84 %**, com o arquivo novo respondendo sozinho
pela queda (0 de 105). O piso de 78 % continuava respeitado, o que torna o episódio mais útil
ainda: **passaria**, e ninguém notaria, se o número não tivesse sido olhado.

A saída não foi baixar o piso. Foi escrever `tests/Kit/KitCoberturaTest.php` — 33 casos, os mesmos
seis caminhos mais os valores limite do piso e as três formas de badge divergente. Medido com
mutação, escopado no arquivo:

| Momento | Mutantes | Score |
|---|---|---|
| primeira redação dos testes | 34 não testados / 124 testados | **78,48 %** |
| com as mensagens afirmadas | **17 não testados / 141 testados** | **89,24 %** |

Os 17 que sobram são quase todos `RemoveMethodCall` sobre `$this->components->error(...)` de
mensagens que nenhum caso afirma. Matá-los exigiria travar o texto de cada mensagem, o que deixa o
teste frágil a revisão de redação — parada declarada, não esquecimento.

## Retrospectiva

<!-- Preenchido no fim. -->
