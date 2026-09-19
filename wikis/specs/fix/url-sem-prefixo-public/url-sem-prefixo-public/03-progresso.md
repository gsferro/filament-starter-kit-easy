# Progresso — `/public` nunca aparece na URL antes do painel

> Cada `[x]` leva evidência inline (`— {resultado}, {data}`). Item sem evidência continua `[ ]`.

## 1. `configureRaizDeUrl()` no `KitServiceProvider`

- [ ] Método `protected` criado, com o guard de console e a comparação de sufixo
- [ ] Chamado em `boot()`

## 2. Cobertura por teste

- [ ] `tests/Kit/UrlSemPrefixoPublicTest.php` — CT-01…CT-08

## 3. Documentação

- [ ] `docs/pt/recursos/configuracao-global-filament.md` — seção do `DocumentRoot`
- [ ] `docs/en/recursos/configuracao-global-filament.md` — par em inglês
- [ ] `CHANGELOG.md` — entrada em *Corrigido*

## 4. Entrega

- [ ] Branch `fix/url-sem-prefixo-public` em worktree próprio
- [ ] PR para a `main`

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test tests/Kit/UrlSemPrefixoPublicTest.php`
- [ ] `composer test:kit` — regressão obrigatória (toca infra compartilhada)
- [ ] **Custo medido** — zero query no caminho comum
- [ ] **`/code-review` no diff (step 7.5)**
- [ ] Falsificabilidade provada por mutante — cada CT falha sem a correção
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa
- [ ] Docs pt/en e CHANGELOG reconciliados
- [ ] `git commit`

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| — | — | — | preenchida no step 7 |

## Quality Gate

<!-- Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| a ser preenchida | | |

### Varredura da classe irmã (step 5)

| Classe nova | Irmã escolhida | Onde a irmã aparece | A nova entrou? |
|---|---|---|---|
| **nenhuma classe nova** — a correção é um método `protected` num provider existente | — | — | não se aplica |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| a ser preenchida | | | |

## Blockers

- nenhum até aqui

## Desvios do Plano

- nenhum até aqui

## Notas de Implementação

### Descobertas durante a investigação, antes do código

| # | Descoberta | Onde ficou registrada |
|---|---|---|
| N1 | O `UrlGenerator` guarda a **própria** referência de request: trocar `app()->instance('request', …)` não o afeta. Sem `setRequest()`, um harness de teste mede o request antigo e "antes" e "depois" saem idênticos — o cenário passa **sem exercitar nada** | `04`, Setup Global |
| N2 | O Symfony percorre `SCRIPT_FILENAME`, `PHP_SELF` e `ORIG_SCRIPT_NAME` para derivar a base. Passar só `SCRIPT_NAME` devolve base **vazia** em todos os casos, e o cenário do caso quebrado não é exercitado | `04`, Setup Global |

> N1 e N2 custaram dois harnesses errados durante a investigação. Os dois produziam o mesmo
> sintoma — "antes igual a depois" — por causas diferentes, e o primeiro quase foi lido como
> "a correção não funciona".

## Retrospectiva

- **Funcionou bem**: —
- **Faltou no plano**: —
