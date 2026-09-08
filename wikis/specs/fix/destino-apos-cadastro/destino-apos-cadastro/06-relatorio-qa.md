# Relatório de QA — fix: destino depois do cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **mínimo** (correção sem superfície de UI, domínio comum)
> Diff: `6414a74..HEAD` — 16 arquivos, +2005 linhas

## Veredito — Ciclo 1

**APROVADO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- Ambiente: local (branch `fix/destino-apos-cadastro`) · Pest 3.x · PHPStan pass · Filacheck 17/17

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | Código | Resultado |
|----|----------|-----------|----|--------|-----------|
| RQ-01 | Conta nova nunca entregue em painel inacessível | 4 | CT-70, CT-75 (panel_user) | `RespostaDeCadastro.php` + `DestinoAposLogin.php` | ✅ |
| RQ-02 | Mesmo decisor do login | 2, 4 | CT-69, CT-75 | `RespostaDeCadastro.php:21-31` | ✅ |
| RQ-03 | Vale com chave desligada | 5 | CT-70, CT-75 (desligada) | `RespostaDeCadastro.php:39-43` | ✅ |
| RQ-04 | Pretendida acessível continua honrada | 4 | CT-75 (admin) | `DestinoAposLogin.php` | ✅ |
| RQ-05 | Wiki, docs e CHANGELOG | 6, 7 | CT-71 | `docs/`, `CHANGELOG.md`, wiki | ✅ |
| RQ-06 | Merge main + tag | — | — | — | ⏸️ fora do escopo deste gate (pós-PR) |
| RQ-07 | Validação por `kit:update` | 9 | — | — | ⏸️ pendente de execução na instalação de testes |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | RQ-01 a RQ-05 rastreadas; RQ-06/07 são ações pós-gate |
| D | Observabilidade real | ⏭️ pulada | canal `autenticacao` usa `NullHandler` no ambiente de teste (`config/logging.php:134`); a chamada `Log::channel('autenticacao')->warning()` está no código e é coberta pelos CTs que espiam o channel |
| J | Regressão adjacente | ✅ | `composer test:kit` — 2226 passaram, 20 processos, 7428 assertions |
| K | Adequação da suíte | ✅ | nenhum CT sem assertion fraca; CT-75 usa dataset para cobrir 4 variações; mutation score não medido por falta de driver PCOV nesta máquina |
| L | Consistência documental | ✅ | IDs CT-67/69/70/72/74/75/76 presentes nos testes e no `04`; docs pt/en e CHANGELOG concordam; ADR-03/04 já corrigidas no `01`/`02` conforme auditoria do `03` |

## Não Verificado

- `vendor/bin/pest --parallel --tia` — `LOG_KIT_DRIVER` não configurado e PCOV indisponível neste ambiente; o `composer test:kit` normal já passou
- `kit:update` em instalação real — requer ação pós-gate

## Veredito e próximo passo

Feature **APROVADA** para abertura de PR. O diff atende às cláusulas funcionais e de documentação. RQ-06 e RQ-07 continuam pendentes, conforme planejado, para após o merge e release.
