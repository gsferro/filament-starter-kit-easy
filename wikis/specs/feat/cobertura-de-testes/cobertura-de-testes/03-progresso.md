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
- [x] Denominador medido pelo próprio relatório: **240 arquivos, 9.848 statements**, 2026-09-24

## 3. Medir: número e tempo (RQ-01, RQ-04)

- [x] `pest --parallel --coverage` **não funciona** — imprime o *usage* do paratest, 2026-09-24
- [x] `artisan test --parallel --coverage-clover=arquivo` roda e **não gera o arquivo**, 2026-09-24
- [x] `pest --coverage-clover=arquivo` em **série** gera (764 KB numa amostra), 2026-09-24
- [x] Overhead do PCOV isolado — mesma amostra: **3,08 s sem** × **4,45 s com** = **+44 %**, 2026-09-24
- [x] **Medição completa** — `php -d pcov.enabled=1 vendor/bin/pest tests/Kit tests/Tenancy --coverage-clover=cobertura.xml`, 2026-09-24:
  **79,79 % de linha (7.858 / 9.848 statements)**, 62,66 % de métodos (745 / 1.189),
  **1.618 s = 26 min 58 s**, 2.880 testes, 11.150 asserções
- [x] Decomposição por diretório e piores arquivos — tabela no `## Notas de Implementação`
- [x] **12 de 240 arquivos com zero cobertura**, somando **494 statements**

## 4. Escolher a meta (RQ-06)

- [x] **Meta: piso de 78 %** no `--min`, com o medido em **79,79 %** — ADR-06
- [x] Folga declarada: **1,79 pp ≈ 176 statements**. Com 9.848 statements, 1 pp vale ~98 linhas —
  o piso não se mexe por mudança pequena; só cai se uma funcionalidade inteira entrar sem teste

## 5. Comando e job de CI (RQ-05, RQ-09)

- [x] `composer test:coverage` — mede, grava o badge e aplica o piso
- [x] `.github/cobertura.php` — lê o Clover, aplica `--min` e escreve/confere o badge
- [x] `.github/badges/cobertura.json` gerado: `{"message": "79%", "color": "green"}`
- [x] Job `cobertura` no `ci.yml`, com `coverage: pcov`
- [x] **Os dois caminhos de reprovação verificados à mão**: com `--min=95` sai 1; com o JSON
  adulterado para `91%` sai 1 dizendo o que rodar; restaurado, sai 0
- [x] **Frequência decidida**: `push` em `main` + `workflow_dispatch`, **não** em PR —
  `if: github.event_name != 'pull_request'`. A medição é serial por construção (ADR-02) e custa
  ~27 min contra ~3 min da suíte paralela; cobrar isso de toda PR quadruplicaria o CI do
  repositório para vigiar um número que se move ~1 pp a cada ~98 linhas
- [x] O badge guarda o percentual **inteiro truncado**, não o real — senão a guarda reprovaria por
  ruído da terceira casa e viraria alarme falso

## 6. Fechar lacuna real até a meta (RQ-07)

- [x] Arquivos de `app/` ordenados por cobertura ascendente — tabela em `## Notas de Implementação`
- [x] **Meta atingida sem escrever teste**: o medido (79,79 %) já está acima do piso (78 %). A meta
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

### Decomposição da medição de 2026-09-24

| Diretório | Coberto | Total | % | Arq. |
|---|---:|---:|---:|---:|
| `app/Providers` | 1.302 | 1.337 | **97,4 %** | 6 |
| `app/Http` | 365 | 378 | **96,6 %** | 9 |
| `app/Models` | 527 | 531 | **99,2 %** | 7 |
| `app/Support` | 1.094 | 1.228 | 89,1 % | 34 |
| `app/Filament` | 3.777 | 4.466 | 84,6 % | 126 |
| `app/Listeners` | 37 | 44 | 84,1 % | 1 |
| `app/Notifications` | 41 | 51 | 80,4 % | 3 |
| `app/Ai` | 182 | 248 | 73,4 % | 17 |
| `app/Services` | 19 | 31 | 61,3 % | 1 |
| **`app/Console`** | 272 | 1.006 | **27,0 %** | 8 |
| **`app/Livewire`** | 42 | 157 | **26,8 %** | 2 |
| **`app/Policies`** | 51 | 222 | **23,0 %** | 16 |
| `app/Data`, `app/Observers`, `app/Settings`, `app/Traits` | 149 | 149 | **100 %** | 10 |

**Os três piores têm causas diferentes, e só um deles é lacuna de verdade:**

1. **`app/Console` a 27 %** — `KitUpdate` (5/330), `KitInstall` (0/195), `KitTenancy` (0/119) e
   `KitArte` (0/61). Estes **são** testados, e pesadamente: os 4 testes locais de instalação
   rodam `composer create-project` de verdade, em processo separado. O PCOV mede o processo do
   Pest, então nada disso aparece. **Não é código sem teste; é teste fora do medidor.**
2. **`app/Policies` a 23 %** — aqui o número **é** o que parece. As policies são exercitadas
   indiretamente (a tela nega, o teste vê negado), mas o `Gate` curto-circuita antes do método em
   boa parte dos casos. É a lacuna com melhor razão entre risco e esforço: autorização é
   exatamente onde um defeito silencioso custa caro.
3. **`app/Livewire` a 27 %** — 113 dos 157 statements são do `AssistenteChatWidget`, que fala com
   provedor de IA. Cobrir exige fake do provedor. Lacuna real, custo médio.

## Retrospectiva

<!-- Preenchido no fim. -->
