# Relatório de QA — Login social por painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil: completo · Domínio sensível: autenticação/autorização

## Veredito — Ciclo 1

**APROVADO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- RQ-01..RQ-06 rastreados até configuração, settings, filtro visual, barreira HTTP, sessão e destino.
- A rota forjada é recusada no servidor; painel inexistente degrada para o default; lista vazia preserva compatibilidade.
- Logs de recusa usam o channel `autenticacao`, sem token ou e-mail em claro.

## Evidências

- `tests/Kit/LoginSocialPorPainelTest.php`
- Suíte focal + regressão: 114 testes, 163 assertions, todos verdes.
- Suíte Tenancy completa: 292 testes, 1122 assertions, todos verdes.

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ | nenhuma cláusula órfã |
| B | Fronteiras | ✅ | ausente, inválido, autorizado e negado |
| C | Permissão | ✅ | UI e barreira HTTP separadas |
| D | Observabilidade | ✅ | motivo, painel e provedor; sem segredo |
| E | Performance | ✅ | decisão em memória/config |
| F | UX de erro | ✅ | 404 deliberado sem detalhe sensível |
| G | Tema | ✅ | botão nativo Filament/currentColor |
| H | Acessibilidade | ✅ | aria-label por provedor |
| I | Segurança | ✅ | query não concede acesso ao painel |
| J | Regressão | ✅ | suites social/settings/tenancy |
| K | Adequação dos testes | ✅ | oráculos de decisão, sessão, HTTP e log |

## Não verificado

- Fluxo OAuth real contra provedores externos — coberto por fakes; não é executável deterministicamente no CI.
