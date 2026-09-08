# Progresso — fix: destino depois do cadastro

## 1. Método público no decisor: descartar a pretendida inacessível

- [x] `descartarPretendidaInacessivel()` em `app/Support/DestinoAposLogin.php` — implementado, 2026-09-08
- [x] Reusa `paineisDe()` e `painelDe()`, sem duplicar regra de URL — conferido em `DestinoAposLogin.php`, 2026-09-08
- [x] `warning` no channel `autenticacao`, só no caso do descarte — conferido, 2026-09-08

## 2. A resposta de cadastro do kit

- [x] `app/Http/Responses/RespostaDeCadastro.php` criada no molde de `RespostaDeLogin` — arquivo conferido, 2026-09-08
- [x] Ramo da chave ligada delega a `DestinoAposLogin::urlPara()` — CT-67/69/75, 2026-09-08
- [x] Ramo da chave desligada descarta a pretendida e delega ao `parent::toResponse()` — CT-70/75, 2026-09-08
- [x] Guarda de tipo para `Filament::auth()->user()` que não é `App\Models\User` — conferido, 2026-09-08

## 3. O bind no container

- [x] `bind(RegistrationResponse::class, RespostaDeCadastro::class)` em `configureLoginUnificado()` — `KitServiceProvider.php`, 2026-09-08
- [x] Docblock do método corrigido: a resposta de cadastro **não** é idêntica à do Filament com a — docblock conferido, 2026-09-08
      chave desligada

## 4. Testes

- [x] `tests/Kit/DestinoAposCadastroTest.php` — CTs do `04` — CT-67/69/70/72/74/75, 2026-09-08
- [x] Regressão dos arquivos que atravessam a resposta de cadastro — `composer test:kit` 2226 passaram, 2026-09-08

## 5. Docs de usuário

- [x] `docs/pt/autenticacao/login-unificado.md` + `docs/en/...` — conferidos, 2026-09-08
- [x] `docs/pt/autenticacao/registro-aberto.md` + `docs/en/...` — conferidos, 2026-09-08

## 6. CHANGELOG e version

- [x] `CHANGELOG.md` — entrada 0.32.3 conferida, 2026-09-08
- [ ] `config/kit.php` → `version` (só no commit de release)

## 7. Reconciliação

- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [x] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa — CT-67/69/70/72/74/75/76, 2026-09-08

## 8. Quality gate, PR, merge, tag

- [ ] `feature-quality-gate` → `06-relatorio-qa.md`
- [ ] PR aberto com link da wiki e veredito
- [ ] CI verde nos quatro jobs
- [ ] Merge, tag, release marcada Latest, site conferido

## 9. Validação por `kit:update` em instalação real

- [ ] `kit:update --all --no-interaction` em `TESTES KIT/login-unificado-sem-tenancy`
- [ ] Os três arquivos da correção entregues
- [ ] Sequência do laudo reproduzida no navegador: cai no `/app`, não em 403

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent` — passou, 2026-09-08
- [x] `vendor/bin/phpstan analyse --memory-limit=2G` — passou, 2026-09-08
- [x] `vendor/bin/filacheck --fix` — 17 rules passed, 2026-09-08
- [x] `vendor/bin/pest --filter=DestinoAposCadastro --compact` — 11 passaram, 2026-09-08
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `vendor/bin/pest --parallel --tia` — impacto real × `## Impacto` do PRD
- [ ] Citações `arquivo:símbolo:linha` reverificadas — {n}/{n} ok
- [ ] Docs pt/en e CHANGELOG reconciliados
- [x] `git commit` — dois commits: testes CT-67/69/70/72 e CT-74/75/76, 2026-09-08

<!-- Cada [x] acima leva " — {evidência}, {data}". -->

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | | |
| `providers.md` | `app/Providers/**` | | |
| `auth.md` | `app/Filament/Pages/Auth/**` | | |
| `testes.md` | `tests/**` | | |
| `specs.md` | `wikis/specs/**` | | |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: · **Veredito**: · **Data**:
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| citação da linha 14 de `RegistrationResponse.php` apontando o método `toResponse` | a linha 14 é o corpo; o símbolo está na 12 | citação virou `:toResponse():12-15`, e o mesmo em quatro outras — o grep da skill exige o símbolo NA linha citada |
| `carimbarAcesso()` na linha 172 | está na 165 | ADR-03 e ADR-04 corrigidas |
| ninguém já vincula `RegistrationResponse` no kit | confirmado: só `RegistroPorConvite` importa o contrato, para o tipo de retorno de `register()` | nenhuma — premissa confirmada |
| `app/Http/Responses` é entregue pelo `kit:update` | confirmado: o diretório inteiro está em `KitUpdate::CAMINHOS_DO_KIT` | nenhuma — o arquivo novo é entregue sem editar a lista |
| existe channel de log de auth para reusar | confirmado: `autenticacao` em `config/logging.php:132` | nenhuma |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `descartarPretendidaInacessivel()` devolvia `bool` que nenhum chamador leria — `yagni` | sim, virou `void` | `app/Support/DestinoAposLogin.php` + passo 1 do `01`, marcado `*(alterado em 2026-09-08: …)*` |
| 2 | tabela vazia de `## Rotas` no `01` (linha `| — | — | — | — |`) | recusada: a seção é obrigatória no template da skill, e a frase "nenhuma rota nova" acima dela já é a resposta — apagar a tabela faria a próxima leitura procurar a seção | `01` |
| 3 | o resto do diff | `Lean already` — a correção são 3 linhas de decisão numa classe de resposta e 1 bind; o volume do diff é docblock e wiki, que são boundary do Ponytail | — |

## Blockers

- nenhum

## Desvios do Plano

<!-- Onde a implementação divergiu do PRD e por quê -->

## Notas de Implementação

<!-- Descobertas durante o código que não estavam no plano -->

## Retrospectiva

- **Funcionou bem**:
- **Faltou no plano**:
