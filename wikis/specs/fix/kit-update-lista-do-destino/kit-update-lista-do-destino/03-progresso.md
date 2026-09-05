# Progresso — `kit:update` lê a lista de caminhos da versão destino

> Branch: `fix/kit-update-lista-do-destino` · Wiki criada em 2026-09-05 · Implementação: **concluída em 2026-09-05**

## 1. Extrair a lista de caminhos de um fonte do `KitUpdate.php`

- [x] `KitUpdate::caminhosDeclaradosEm(string $fonte): array` — público, estático, `@return list<string>`
- [x] Devolve `[]` quando a forma da constante não é reconhecida
- [x] Comentário com o teto declarado (`ponytail:` — forma textual como contrato, CT reprova antes da tag)

## 2. Unir a lista desta versão com a do destino

- [x] `private bool $listaDoDestinoLida = false;`
- [x] `KitUpdate::caminhosUnidos(array $doDestino): array` — público, estático, união com `CAMINHOS_DO_KIT` (`array_unique`, `array_values`)
- [x] `caminhosDoKit(string $destino): array` — `git show {destino}:app/Console/Commands/KitUpdate.php` → parser → flag → `caminhosUnidos()`
- [x] `components->warn()` quando a lista do destino não foi lida

## 3. `arquivosAlterados()` usa a união

- [x] `...self::CAMINHOS_DO_KIT` → `...$this->caminhosDoKit($destino)` em `KitUpdate.php:525`
- [x] Frase no comentário da constante apontando para `caminhosDoKit()`

## 4. O aviso da segunda rodada só quando fez falta

- [x] `encerrar()`: parágrafo (i) "comportamento anterior" sempre; parágrafo (ii) "RODE O COMANDO DE NOVO" só se `! $this->listaDoDestinoLida`
- [x] Strings `php artisan kit:update{$from} --tag={$versao} --no-branch` e `' --from='.str_replace('kit-v', '', $origem)` preservadas literalmente
- [x] Comentário do bloco (`:868-883`) atualizado

## 5. CHANGELOG e documentação

- [x] `CHANGELOG.md` → `## [Unreleased]` → `### Corrigido`
- [x] `docs/pt/comecar/atualizando-o-projeto.md` — item "O próprio `kit:update` se atualiza"
- [x] `docs/en/comecar/atualizando-o-projeto.md` — idem
- [x] Contorno para instalações anteriores (v0.22.x → ≥ 0.23.0) registrado — RQ-06

## 6. Testes

- [x] `tests/Kit/KitUpdateTest.php` — CT-01…CT-03 e CT-05…CT-09 do `04-casos-de-teste.md` (CT-04 cortado na auditoria; IDs mantidos) — **52 casos verdes** no arquivo (11 antigos + 8 novos, 3 deles com dataset)

## 7. Prova em instalação real

- [x] `TESTES KIT/v0290-sem-tenancy` com o `KitUpdate.php` desta branch; tag local `v99.0.0` com diretório novo
- [x] `--dry-run` lista o arquivo do diretório novo **sem** segunda rodada (RQ-02)
- [x] Contraprova: sem a correção o arquivo **não** aparece
- [x] Fallback (RQ-04): tag com constante irreconhecível → aviso + diff da lista própria
- [x] Limpeza: tag apagada, instalação revertida

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse --no-progress`
- [x] `php artisan test tests/Kit/KitUpdateTest.php --compact`
- [x] `php artisan test tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php --compact`
- [x] `composer test:kit`
- [x] `git commit` (quatro commits do plano)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `git()` não lança em falha e devolve `''` | `KitUpdate.php:952-958`: `Process::run()` + `getOutput()`, sem `mustRun()` — confirmado | nenhuma; o passo 2 depende disso para o fallback |
| A constante fecha com `\n    ];` (4 espaços) | `KitUpdate.php:248`: `    ];` — confirmado; e a regex extraiu a contagem certa nas 4 tags medidas | nenhuma |
| `aplicar()` já lê do destino com `git checkout destino -- caminho` | `KitUpdate.php:765` — confirmado | nenhuma |
| `KitUpdateTest.php:294` lê o fonte e exige as strings do comando pronto | confirmado (`file_get_contents` + `toContain`) | passo 4 e ADR-04 preservam as strings |
| `config/logging.php` não está em `CAMINHOS_DO_KIT` | lista atual (`:84-248`): só `config/kit.php`, `config/media-library.php`, `config/filament-maillog.php`, `config/filament-shield.php` — confirmado | sustenta a ADR-03 |
| Existe instalação v0.22.3 e v0.29.0 para a prova | `TESTES KIT/v0223-tenancy` (0.22.3), `v0290-sem-tenancy` e `v0290-com-tenancy` (0.29.0) — confirmado; `v0223-padrao` sem `config/kit.php` legível | passo 7 usa `v0290-sem-tenancy` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `04` CT-04 ("parser puro, sem git"): cenário que não mata mutante próprio — M4 já morre em CT-02, cuja fixture inline (4 caminhos) difere do disco (59) | sim | `04`, R1: cenário removido, M4 reapontado |
| 2 | `04` CT-01 "E tem 59 itens": duplica a igualdade com a constante e quebra a cada caminho novo | sim | `04`, CT-01: cláusula removida |
| 3 | `01`/`03` Verificação Final: `pest --parallel --tia` contradiz a divergência declarada no `04` (sem PCOV garantido) | sim | `composer test:kit` nos dois |
| 4 | `01` passo 7 item 5 (contraprova do fallback exige forjar uma segunda tag): cogitado cortar e confiar em CT-03 + CT-07 | **recusada** | é o único matador de M14 (condição do aviso invertida); custo de três comandos |
| 5 | `02` ADR-04 alternativa 1 vs. manter dois `note()`: cogitado fundir num só `note()` com o parágrafo (ii) condicional por concatenação | **recusada** | a string do comando pronto precisa continuar literal no fonte para `KitUpdateTest.php:294`; dois `note()` é a forma mais simples que preserva isso |

## Blockers

- nenhum

## Desvios do Plano

- **Passo 2 ganhou `caminhosUnidos()` público e estático** (já refletido no PRD antes da implementação): a `feature-test-design` exigiu que a união fosse alcançável sem git — CT-05..CT-07 são funções puras. `caminhosDoKit()` ficou só a cola (git show → parser → flag → união).
- **Passo 4: dois `note()` em vez de um.** O parágrafo "comportamento da versão anterior" sai sempre; o "RODE O COMANDO DE NOVO" ficou num `note()` próprio sob `if (! $this->listaDoDestinoLida)`. As duas strings que `KitUpdateTest.php:294` procura continuam literais.
- **Passo 5, docs: a versão citada é `v0.30.1`** ("a partir da v0.30.1"). Premissa: esta correção sai como patch da 0.30.0. Se o release for outro número, ajustar as duas páginas (a checagem CT-09 não afirma o número).
- **Commits: três, não quatro** — fix + testes num só commit, porque o teste do fonte (CT-08) e o código são inseparáveis para o CI ficar verde em cada commit; docs e wiki separados.

## Notas de Implementação

- **Gate de falsificação (2026-09-05)**: dois mutantes plantados à mão antes de commitar. M7 (`caminhosUnidos()` devolvendo só `$doDestino`): **3 de 4** casos da união vermelhos. M1 (regex de linha sem âncora `^\s+`): CT-02 vermelho — a fixture com caminho em comentário e caminho `//` acusou. Fonte restaurado byte a byte (`cp` do backup).
- **Prova em instalação real (passo 7, 2026-09-05)** — `TESTES KIT/v0290-sem-tenancy` (v0.29.0, sem git; `git init` + dois commits, desfeitos ao final), clone descartável do kit em `C:\tmp\kit-prova` com `core.longpaths=true` (o scratchpad estoura o limite de caminho do Windows), tags locais `v99.0.0` (constante + `resources/views/kit-prova/x.blade.php`) e `v99.1.0` (constante reescrita como `array_merge([`):
  - **RQ-02** — `kit:update --repo=<clone> --from=0.29.0 --tag=99.0.0 --dry-run` com o `KitUpdate.php` desta branch: `resources/views/kit-prova/x.blade.php  novo no kit`. **Sem segunda rodada.** Remote e tags `kit-*` removidos ao sair.
  - **Contraprova** — mesmo comando com o `KitUpdate.php` original da v0.29.0: **nenhuma** linha `kit-prova`.
  - **RQ-04** — `--tag=99.1.0`: `WARN Não foi possível ler a lista de caminhos da versão kit-v99.1.0; usando a desta versão…` e nenhuma linha `kit-prova` (a lista própria não o cobre). Mata M13 e M14, os dois sem matador na suíte.
  - Limpeza conferida: instalação sem `.git`, `KitUpdate.php` dela idêntico ao original; kit sem tags `v99*`, `main == origin/main`.
- **Incidente durante a prova, sem dano publicado**: a primeira versão do script fez `git clone --local` entre discos (falha por hardlink), o `cd` para o clone falhou e, sem `set -e`, os passos seguintes rodaram **no repositório do kit**: `git checkout main`, dois commits "prova" em `main` (com a implementação inteira dentro), duas tags `v99*` e `user.email` local trocado. Nada foi enviado. Recuperação: tags apagadas, config local desfeita, `main` reapontada para `origin/main` (`git branch -f`), arquivos da implementação recuperados do commit descartado para a árvore da branch e a linha `kit-prova` removida da constante — smoke check confirmou `parser == constante (59)`. O script foi reescrito com `set -eu`, `cd || exit 1`, guarda de "não estou no kit" e `trap` de limpeza. Lição para a retrospectiva.
- **Caminho longo no Windows**: `git clone` para dentro do scratchpad falha com `Filename too long` (wiki `admin-app-nao-alcanca-master-global` + `resources/views/vendor/...`). Clone em `C:\tmp` com `-c core.longpaths=true` resolve.
- Suíte de documentação (`SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`): 46 verdes após as edições em `docs/`.

## Retrospectiva

- **Funcionou**: medir a forma da constante nas quatro tags **antes** de decidir por regex — a decisão (ADR-01) nasceu com número, e o parser saiu de primeira igual à constante (59/59, 55, 57). A `feature-test-design` mudou o desenho para melhor antes do código (`caminhosUnidos()` puro), e o gate de falsificação com mutante plantado custou dois minutos.
- **Faltou no plano**: prever que a instalação de teste **não é git** (a wiki do spotlight já tinha registrado isso e o plano não leu) e que um script de prova precisa de `set -e` e de guarda de diretório — o incidente acima teria sido evitado por duas linhas. Regra pessoal para scripts que tocam mais de um repositório: falhar cedo, confirmar o `pwd`, e nunca `git checkout` sem checar onde se está.
- **Faltou no plano**: o número da versão nos docs. "A partir da v0.30.1" é premissa de release, não fato; melhor teria sido citar o CHANGELOG sem número.
