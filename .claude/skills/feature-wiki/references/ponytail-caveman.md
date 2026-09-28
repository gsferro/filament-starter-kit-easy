> Referência da feature-wiki 4.0.0. Lida em: início da sessão de planejamento (ativar Caveman e Ponytail). Fonte única de: fronteira do Caveman com os arquivos wiki, onde ele se aplica e como ativar o trio.

# Ponytail e Caveman — integração

A tabela de skills companheiras, o comando com namespace (`/caveman:caveman`,
`/ponytail:ponytail-review`) e o step 6 ficam no corpo do `SKILL.md`.

### Caveman + feature-wiki: fronteira clara

**Modo padrão: `ultra`.** Ao iniciar uma sessão de planejamento com esta skill, ativar `/caveman:caveman ultra` — a compressão máxima da prosa vale porque o conteúdo denso vive nos arquivos wiki, não na conversa. Se a resposta ficar ambígua num ponto crítico, descer para `/caveman:caveman full` apenas naquele trecho (o Auto-Clarity do Caveman já faz isso automaticamente em security warnings, ações irreversíveis e sequências multi-etapas).

O Caveman tem uma regra de **Auto-Clarity** que desativa o modo terse em situações críticas (security warnings, irreversible actions, multi-step sequences). Mas isso é implícito — a feature-wiki torna explícito:

> **Arquivos wiki são boundary do Caveman.**
>
> - `00-requisito.md` — o texto original é **verbatim por definição**. Comprimir aqui é falsificar a fonte da verdade.
> - `01-plano-acao.md` — PRD precisa ser "minucioso o suficiente para um agente implementar sem ambiguidade". Compressão destrói essa propriedade.
> - `02-decisoes-arquiteturais.md` — ADR é argumentativo por natureza (Contexto, Decisão, Alternativas, Consequências). Fragmentos perdem o raciocínio.
> - `03-progresso.md` — Checklists e descrições de blockers/desvios precisam de clareza.
> - `04-casos-de-teste.md` — CTs já são estruturados (tabelas, code blocks), mas a prosa explicativa entre eles não deve ser comprimida.
> - `05-*.md` — Arquivos extras (rollback, performance, security) são críticos e não podem ser ambíguos.

**Onde Caveman é bem-vindo**:
- Conversa agent ↔ usuário durante a sessão de planejamento
- Resumos de progresso ("CT-01 passou, CT-02 falha em assertion X")
- Perguntas e confirmações ("Confirmar nome da feature: X?")
- Respostas a dúvidas rápidas durante a implementação

**Onde Caveman NÃO se aplica** (já definido pelo próprio Caveman):
- Código/commits/PRs: "write normal"
- Security warnings e irreversible action confirmations

### Como ativar o trio

```bash
# 1. feature-wiki (via Laravel Boost)
php artisan boost:add-skill gsferro/laravel-ai-skills
# php artisan boost:update   # só se o add-skill terminou com erro; exige boost.json (o add-skill já o chama)

# 2. Ponytail (escolha um agente)
# Claude Code:
#   /plugin marketplace add DietrichGebert/ponytail
#   /plugin install ponytail@ponytail
# Windsurf:
#   curl -o .windsurf/rules/ponytail.md https://raw.githubusercontent.com/DietrichGebert/ponytail/main/.windsurf/rules/ponytail.md

# 3. Caveman (escolha um agente)
# Claude Code:
#   /plugin marketplace add JuliusBrussee/caveman
#   /plugin install caveman@caveman
# Windsurf:
#   curl -o .windsurf/rules/caveman.md https://raw.githubusercontent.com/JuliusBrussee/caveman/main/.windsurf/rules/caveman.md
```

Sessão com o trio ativo: `/caveman:caveman ultra` + `/ponytail:ponytail full` + `feature-wiki` → resposta curta + diff curto + plano detalhado.
