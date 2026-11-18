# Progresso — Roadmap item 8.2: `composer-require-checker` + `composer-unused`

**Estado**: em revisão

**Base do PR**: `main`

## 1. As duas ferramentas no `require-dev`
- [x] `composer require --dev` das duas — `composer.json` (`^4.16`, `^0.9`) e `composer.lock` (só pacotes novos; nenhuma versão existente mudou, conferido no diff do lock), 2026-11-18

## 2. A configuração do `composer-unused`
- [x] `composer-unused.php` com as 5 exclusões comprovadas — triagem pelo `codex` (despacho 1), relatório cai de 8 para os 3 sem uso, 2026-11-18

## 3. Os scripts do composer
- [x] `deps:unused`, `deps:require`, `deps:check` — `composer validate` "is valid"; `composer deps:check` lista os 3 sem uso e os 181 símbolos, 2026-11-18

## 4. O relatório no CI
- [x] passo "Dependências declaradas × usadas (relatório)" no job `qualidade` — `.github/workflows/ci.yml`, 2026-11-18

## 5. A configuração viaja com o kit
- [x] `composer-unused.php` em `KitUpdate::CAMINHOS_DO_KIT` — `app/Console/Commands/KitUpdate.php`, 2026-11-18

## 6. A triagem registrada
- [x] Triagem registrada — `wikis/qualidade-de-codigo.md` (seção nova, tabela e recomendações), `docs/{pt,en}/referencia/qualidade-de-codigo.md`, roadmap 8.2 FEITO, READMEs (`composer deps:check`), 2026-11-18

## Testes
- [x] `tests/Kit/VerificacaoDeDependenciasTest.php` (CT-01..CT-09) — `vendor/bin/pest tests/Kit/VerificacaoDeDependenciasTest.php`: 12/12, 171 asserções, ~4,4 s; falsificabilidade: com a entrega revertida (scripts, passo do CI, lista do `kit:update`, `composer-unused.php`) 7 de 10 vermelhos — os 3 verdes leem só a triagem, que ficou; CT-06 vermelho com um nome da lista trocado, 2026-11-18

## Tickets
Não fatiado — 2026-11-18: 6 RQ, ferramental sem tela; nenhum sinal de fatiamento

## Verificação Final
- [x] `vendor/bin/pint --dirty --format agent` — `{"tool":"pint","result":"passed"}`, 2026-11-18
- [x] `vendor/bin/phpstan analyse` (level 8) — sem código de `app/` novo além de uma linha de lista; o job `qualidade` do PR roda o gate
- [x] testes da feature — ver `## Testes`, 2026-11-18
- [x] `vendor/bin/pest --parallel --tia` contra a baseline — `pest --tia --parallel --processes=3 --exclude-testsuite=Browser`: 6194 testes, 6191 passaram, 3 pulados, 0 falhas (a rodada anterior pegou o `composer.json` inválido no meio da edição — 269 erros "Unable to detect application namespace" — e foi descartada), 2026-11-18
- [x] step 9 (revisão do diff) — `/code-review` (9), `fw-revisor-diff` (6) e `codex` (3); destino em `## Revisão do Diff (step 9)`, 2026-11-18
- [ ] step 11 (quality gate)
- [x] `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh` silenciosos — exit 0 nos cinco depois de P-07 na R2 e da tabela de conformidade, 2026-11-18
- [x] CHANGELOG 0.55.0, versão, `atualizando-o-projeto`, roteiro `wikis/atualizacoes/v0.55.0.md` — roteiro escrito pelo `codex`, `RoteiroDeAtualizacaoTest` 212/212; `ext-sodium` acrescentada depois do step 9, 2026-11-18

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-18 — mantenedor, pelas respostas escritas a Q1 e Q2 e "prossiga ate finalizar" — 1 rodada, 2 perguntas na raia requisito
- Roteiro manual: não — 2026-11-18 — convenção: ferramental sem tela; a validação é o próprio `composer deps:check` e o resumo do CI

### Revisão profunda (step 5)

| Premissa do plano | O que o código diz | Correção |
|---|---|---|
| os scripts de qualidade são um comando por ferramenta | `composer.json` `lint`, `lint:check`, `types:check`, `filament:check` | nenhuma |
| a configuração das ferramentas irmãs viaja pelo `kit:update` | `KitUpdate::CAMINHOS_DO_KIT` lista `rector.php`, `phpstan.neon`, `phpunit.xml`, `pint.json` | nenhuma |
| o `kit:update` não sobrescreve o `composer.json` | `KitUpdate.php`: "O que mudou no `composer.json` do kit — como relatório, nunca aplicado" | o roteiro de atualização manda rodar o `composer require --dev` e copiar os scripts |

### Varredura da classe irmã

- `pint.json` (irmão de `composer-unused.php`): `KitUpdate::CAMINHOS_DO_KIT` e `.gitattributes` (nenhum `export-ignore`, então viaja no `create-project`). O arquivo novo entra na lista do `kit:update`; nenhuma outra ocorrência

### Auditoria Ponytail (step 6)

Passe manual pela skill `ponytail-review`:

- `delete:` `composer-require-checker.json` — a triagem não achou falso positivo; um `{}` seria arquivo sem efeito (D2). Aplicado: sem o arquivo
- `01` passos 1 a 6: nada a cortar

## Desvios do Plano

- 2026-11-18 — **ordem**: a implementação (passos 1 a 6) foi feita antes da derivação do `04`, porque a pergunta do requisito (RQ-06) só tem resposta medindo: a triagem é o dado que o plano precisava. O derivador recebe só o `00` e os paths do `01`; a falsificabilidade dos CT é provada revertendo o diff

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CX-01 / RD (eixo correção) | `codex`, `fw-revisor-diff` | o passo do CI parava no primeiro erro (`bash -e` sem pipefail): a segunda ferramenta não rodava | correção (`\|\| true` nas duas) | CT-04 dataset "primeira quebrada" | — |
| CX-02 | `codex` | o stderr não chegava ao resumo | correção (`} 2>&1 \| tee`) | CT-04 dataset "segunda só com erro" | — |
| CX-03 | `codex` | o CT-04 dependia de achado real e rodava as ferramentas (~24 s) | CT-04 reescrito com ferramentas falsas (2,9 s) | CT-04 | — |
| CR-01 | `/code-review` | o `composer-require-checker` 4.24 traz `php-standard-library`, que exige `ext-sodium`, e limita o PHP a 8.4–8.5 | documentado (README pt/en, CHANGELOG, `atualizando-o-projeto`) | — | rebaixar para a 4.17 (sem a dependência) foi medido e recusado: rebaixaria o `symfony/console` de produção 8.1 → 7.4 (P-05) |
| CR-02 | `/code-review` | execução quebrada fica igual a execução com achado | — | — | o erro agora chega ao resumo (CX-02); reprovar seria contrariar Q2 |
| CR-03 | `/code-review` | helper `vdBash()` sem uso | removido | — | — |
| CR-04 | `/code-review` | o CI repete as linhas de comando em vez de chamar os scripts `deps:*` | — | — | RQ-04 pede o CI rodando pelo `vendor/bin`; os scripts locais saem com o código da ferramenta, e o passo do CI precisa engolir |
| CR-05 | `/code-review` | o helper do CT-02 tratava `@putenv`/`@composer` como script | correção | CT-02 | — |
| CR-06 | `/code-review` | scripts `deps:*` sem `disableProcessTimeout` | correção | — | — |
| CR-07 | `/code-review` | o `kit:update` sobrescreveria a lista de exclusão do projeto | — | — | mesmo regime de `pint.json` e `phpstan.neon` (P-06): arquivo do kit editado pelo dono vira conflito que o roteiro pergunta |
| CR-08 | `/code-review` | parâmetro do dataset do CT-01 sem uso | removido | — | — |
| CR-09 | `/code-review` | os números da triagem repetidos em várias páginas | — | — | são números datados ("triagem de 2026-11-18"), linha de base histórica, não estado corrente |
| RD-01 | `fw-revisor-diff` | "oito extensões" seriam sete | — | — | refutado: o grep do revisor era minúsculo e perdeu `ext-SimpleXML`; são oito |
| RD-02 | `fw-revisor-diff` | "ver a seção abaixo" apontava para cima | correção (pt/en) | — | — |
| RD-03 | `fw-revisor-diff` | `atualizando-o-projeto` sem o `ext-sodium` | correção (pt/en) | — | — |
| RD-04 | `fw-revisor-diff` | nenhum teste provava que a ferramenta lê o `composer-unused.php` | CT-06 roda o `composer-unused` real em JSON (~1,5 s) e confere os excluídos como ignorados | CT-06 (vermelho medido trocando um nome na lista) | — |
| RD-05 | `fw-revisor-diff` | a guarda do CT-05 procurava só `--config-file` | correção (`--configuration`/`-c` também) | CT-05 | — |
| RD-06 | `fw-revisor-diff` | o motivo do skip dizia que `wikis/` não viaja | correção do texto: a triagem é do kit, não do projeto | — | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | n.a. | só uma linha na lista `CAMINHOS_DO_KIT` |
| `commands.md` | `app/Console/Commands/**` | aplicada | o arquivo novo entra em `KitUpdate::CAMINHOS_DO_KIT` ao lado de `pint.json` (P-06) |
| `config.md` | `config/**` | aplicada | só `kit.version` 0.55.0 |
| `general.md` | `composer.json` | aplicada | as duas dependências novas são públicas (Packagist) e só de desenvolvimento; nenhuma dependência privada |
| `kit.md` | `tests/Kit/**` | aplicada | `VerificacaoDeDependenciasTest` na suíte Kit |
| `specs.md` | `wikis/specs/**` | aplicada | citações por símbolo; testes citados pelo ID do CT |
| `testes.md` | `tests/**` | aplicada | `->skip(fn (): bool => ! naArvoreDoKit(), …)` nos casos que leem `.github/`, `.gitattributes` e a triagem; helpers `vd*` só usados no próprio arquivo |

## Despachos

| # | Step | Rota | Modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `codex exec --yolo` (triagem das saídas das duas ferramentas) | não exposto pelo host | as duas saídas + o repositório, sem editar | 5 pacotes usados por outro caminho, 3 sem uso, 181 símbolos agrupados (137 transitivos, 44 extensões), sem falso positivo | — | amostragem: `config/command-center.php` (`backup:run`) e `docker-compose.yml` (`pulse:check`, `reverb:start`) conferidos por grep; `composer deps:check` depois da configuração lista exatamente os 3 sem uso |

## Candidatos a Rule
