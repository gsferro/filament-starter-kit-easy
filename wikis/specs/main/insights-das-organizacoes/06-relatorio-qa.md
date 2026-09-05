# Relatório de QA — Insights das organizações

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil: completo · Infra compartilhada: `authentication_log`

## Veredito — Ciclo 1

**REPROVADO → teste/implementação; corrigido no mesmo ciclo**

- Blocker: 0 · Major: 1 · Minor: 0 · Cosmético: 0

### QA-01 — Organização sem acesso desaparecia · Major · destinos 3 e 2

- **Dimensão**: A/K
- **Relacionado a**: RQ-02, RQ-04, R5, CT-09.
- **Esperado**: a métrica cobre cada organização; sem usuário elegível, exibe zero.
- **Observado**: o teste exigia ausência da organização e a consulta usava `INNER JOIN`.
- **Repro**: organização com uma tentativa falha e uma pessoa excluída não aparecia no breakdown.
- **Correção**: CT-09 passou a exigir `Globex => 0`; consulta passou a `LEFT JOIN` com agregado condicional.
- **Prova**: CT-09 falhou antes da correção e passou depois; CT-08..CT-10 verdes.

## Veredito — Ciclo 2

**APROVADO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- RQ-01..RQ-07 rastreados até migration, hook, seis widgets e duas páginas.
- Autorização herda `TenantResource::canAccess()`; fontes opcionais falham fechadas.
- Consultas são agregadas/eager-loaded, sem N+1 por organização.

## Evidências

- Suíte focal + regressão: 114 testes, 163 assertions, todos verdes.
- Suíte Tenancy completa após a correção: 292 testes, 1122 assertions, todos verdes.
- PHPStan global: zero erros.

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ | QA-01 corrigido |
| B | Fronteiras | ✅ | nulo, falha, soft delete, borda temporal |
| C | Permissão | ✅ | página e widgets negados sem ViewAny |
| D | Observabilidade | ✅ | dados já persistem em auth/audits; sem log duplicado |
| E | Performance | ✅ | agregados no banco; morph eager-loaded |
| F | UX de erro | ✅ | fontes ausentes escondem apenas widgets dependentes |
| G | Tema | ✅ | componentes Filament sem cores CSS próprias |
| H | Acessibilidade | ✅ | componentes nativos, leitura server-rendered |
| I | Segurança | ✅ | sem rota nova; mesma barreira do Resource |
| J | Regressão | ✅ | widgets de infra e tenancy exercitados |
| K | Adequação dos testes | ✅ | CT-09 agora mata o INNER JOIN defeituoso |

## Não verificado

- Plano visual por screenshot — widgets usam componentes nativos e não há baseline de imagem.
