# Progresso — Roadmap item 8.2: `composer-require-checker` + `composer-unused`

**Estado**: em implementação

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
- [x] `wikis/qualidade-de-codigo.md` (seção nova, tabela e recomendações), `docs/{pt,en}/referencia/qualidade-de-codigo.md`, roadmap 8.2 FEITO, READMEs (`composer deps:check`), 2026-11-18

## Testes

## Tickets
Não fatiado — 2026-11-18: 6 RQ, ferramental sem tela; nenhum sinal de fatiamento

## Verificação Final
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` (level 8)
- [ ] testes da feature
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [ ] step 9 (revisão do diff) e step 11 (quality gate)
- [ ] `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh` silenciosos
- [ ] CHANGELOG 0.55.0, versão, `atualizando-o-projeto`, roteiro `wikis/atualizacoes/v0.55.0.md`

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

## Conformidade com Rules

## Despachos

| # | Step | Rota | Modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `codex exec --yolo` (triagem das saídas das duas ferramentas) | não exposto pelo host | as duas saídas + o repositório, sem editar | 5 pacotes usados por outro caminho, 3 sem uso, 181 símbolos agrupados (137 transitivos, 44 extensões), sem falso positivo | — | amostragem: `config/command-center.php` (`backup:run`) e `docker-compose.yml` (`pulse:check`, `reverb:start`) conferidos por grep; `composer deps:check` depois da configuração lista exatamente os 3 sem uso |

## Candidatos a Rule
