> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes de decidir os arquivos extras). Fonte única de: arquivos extras `05-*` e exemplo de árvore criada — com o glossário do projeto, os tickets do step 8 e o quadro entre features.

# Estrutura criada

## Arquivos Extras (conforme necessidade)

Criar apenas quando a feature exige:

| Arquivo | Quando criar |
|---------|-------------|
| `05-casos-de-teste-browser.md` | `## Costuras de Teste` do `04` tem uma linha `browser` — ver [Gate do `05`](../SKILL.md#gate-do-05-browser) |
| `05-design.md` | Feature com UI significativa (Filament, Livewire, Blade) |
| `05-api-contract.md` | Feature com API externa (payloads, endpoints, autenticação) |
| `05-db-schema.md` | Feature com schema complexo (múltiplas tabelas, migrations em cadeia) |
| `05-fluxo.md` | Feature com fluxo multi-etapas (queues + jobs + callbacks) |
| `05-rollback.md` | Feature com migrations destrutivas ou mudanças de schema irreversíveis |
| `05-performance.md` | Feature com volume alto (batch processing, relatórios, imports de CSV) |
| `05-security.md` | Feature com dados sensíveis (PII, pagamentos, autenticação, LGPD) |

## Exemplo de Estrutura Criada

```text
wikis/
├── glossario.md                            ← global: vocabulário do domínio (fora da pasta da feature)
└── specs/
    ├── INDEX.md                            ← quadro entre features: gerado pelo indice.sh da feature-tickets, nunca editado
    └── ferro/
        └── 579/
            └── relatorio-mba-lote/
                ├── 00-requisito.md                  ← requisito bruto imutável + RQ-## + perguntas + premissas
                ├── 01-plano-acao.md
                ├── 02-decisoes-arquiteturais.md      ← ADR só com os três portões (zero é válido)
                ├── 03-progresso.md                   ← **Estado** + Tickets, Revisão do Diff, Despachos, Blockers, Desvios, Retrospectiva
                ├── 04-casos-de-teste.md              ← step 7 (depois do Ponytail): costuras de teste + CTs de backend
                ├── 05-casos-de-teste-browser.md      ← CT-B + roteiro desenhado × implementado
                ├── 05-api-contract.md                ← extra quando necessário
                ├── 05-rollback.md                    ← extra quando necessário
                ├── 06-relatorio-qa.md                ← saída do feature-quality-gate
                └── 07-tickets/                       ← só se o step 8 fatiou (feature-tickets)
                    ├── README.md                         ← quadro da feature, gerado pelo indice.sh — nunca editado
                    ├── 00-prefactor-consulta-por-turma.md ← prefactoring, quando há: vem primeiro
                    ├── 01-coordenador-dispara-lote.md    ← um ticket vertical por arquivo, em ordem de dependência
                    └── 02-coordenador-notificado.md
```
