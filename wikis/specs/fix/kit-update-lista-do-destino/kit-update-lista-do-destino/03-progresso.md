# Progresso — `kit:update` lê a lista de caminhos da versão destino

> Branch: `fix/kit-update-lista-do-destino` · Wiki criada em 2026-09-05 · Implementação: **não iniciada**

## 1. Extrair a lista de caminhos de um fonte do `KitUpdate.php`

- [ ] `KitUpdate::caminhosDeclaradosEm(string $fonte): array` — público, estático, `@return list<string>`
- [ ] Devolve `[]` quando a forma da constante não é reconhecida
- [ ] Comentário com o teto declarado (`ponytail:` — forma textual como contrato, CT reprova antes da tag)

## 2. Unir a lista desta versão com a do destino

- [ ] `private bool $listaDoDestinoLida = false;`
- [ ] `KitUpdate::caminhosUnidos(array $doDestino): array` — público, estático, união com `CAMINHOS_DO_KIT` (`array_unique`, `array_values`)
- [ ] `caminhosDoKit(string $destino): array` — `git show {destino}:app/Console/Commands/KitUpdate.php` → parser → flag → `caminhosUnidos()`
- [ ] `components->warn()` quando a lista do destino não foi lida

## 3. `arquivosAlterados()` usa a união

- [ ] `...self::CAMINHOS_DO_KIT` → `...$this->caminhosDoKit($destino)` em `KitUpdate.php:525`
- [ ] Frase no comentário da constante apontando para `caminhosDoKit()`

## 4. O aviso da segunda rodada só quando fez falta

- [ ] `encerrar()`: parágrafo (i) "comportamento anterior" sempre; parágrafo (ii) "RODE O COMANDO DE NOVO" só se `! $this->listaDoDestinoLida`
- [ ] Strings `php artisan kit:update{$from} --tag={$versao} --no-branch` e `' --from='.str_replace('kit-v', '', $origem)` preservadas literalmente
- [ ] Comentário do bloco (`:868-883`) atualizado

## 5. CHANGELOG e documentação

- [ ] `CHANGELOG.md` → `## [Unreleased]` → `### Corrigido`
- [ ] `docs/pt/comecar/atualizando-o-projeto.md` — item "O próprio `kit:update` se atualiza"
- [ ] `docs/en/comecar/atualizando-o-projeto.md` — idem
- [ ] Contorno para instalações anteriores (v0.22.x → ≥ 0.23.0) registrado — RQ-06

## 6. Testes

- [ ] `tests/Kit/KitUpdateTest.php` — CT-01…CT-03 e CT-05…CT-09 do `04-casos-de-teste.md` (CT-04 cortado na auditoria; IDs mantidos)

## 7. Prova em instalação real

- [ ] `TESTES KIT/v0290-sem-tenancy` com o `KitUpdate.php` desta branch; tag local `v99.0.0` com diretório novo
- [ ] `--dry-run` lista o arquivo do diretório novo **sem** segunda rodada (RQ-02)
- [ ] Contraprova: sem a correção o arquivo **não** aparece
- [ ] Fallback (RQ-04): tag com constante irreconhecível → aviso + diff da lista própria
- [ ] Limpeza: tag apagada, instalação revertida

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `php artisan test tests/Kit/KitUpdateTest.php --compact`
- [ ] `php artisan test tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php --compact`
- [ ] `composer test:kit`
- [ ] `git commit` (quatro commits do plano)

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

- (pós-implementação)

## Notas de Implementação

- (pós-implementação; inclui o resultado datado do passo 7)

## Retrospectiva

- (pós-implementação)
