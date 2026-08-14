# Requisito — Regressão de telas em browser real

## Fonte

- **Origem**: prompt do usuário no chat, invocando a skill `/feature-wiki`
- **Data**: 2026-08-14
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: **alta** para o pedido desta wiki (texto escrito, colado abaixo verbatim);
  **degradada** para o oráculo das telas — ver "Oráculo degradado" adiante.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos criar os testes de browser e instalar a nova versão do pest5 e do plugin pest-browser para fazer uso da atualização da skill /feature-wiki
> - crie uma wiki agora de regrassão de todas as telas criadas no projeto e teste se elas estão funcionando
> - como não salvamos o requisito bruto, essa rodada será confiando no que esta nos PRDs.
> - pode usar o mcp do playwrite também nessa rodada, mas a skill trata usando o pest-browser
> - valide as telas e também os perfis, além de validar por hora a questão do darkmode
> - use esse momento também para identificar possiveis dividas tecnicas para correção, antes das proximas evoluções
> - faça commits individualizados e pode criar uma branch exclusiva para tratar dessa wiki

## Oráculo degradado — declarado pelo próprio requisito

O requisito diz, com todas as letras: *"como não salvamos o requisito bruto, essa rodada será
confiando no que esta nos PRDs"*.

Isso é uma **concessão explícita**, não um descuido a corrigir. As seis wikis já existentes
(`wikis/specs/main/*` e `wikis/specs/feature/multi-tenancy/organizacoes`) foram escritas antes
da v2.10.0 da skill e **não têm `00-requisito.md`**. Também não têm a seção
`## Superfície de UI` — verificado com `grep -rn "## Superfície de UI" wikis/specs/*/*/01-plano-acao.md`,
que não retorna nada.

Consequência prática, que precisa estar escrita para o `feature-quality-gate` não inventar
cobertura que não existe:

| Pergunta | Esta wiki responde? |
|---|---|
| As telas que existem estão de pé, sem erro de JS, em tema claro e escuro? | **sim** — é o oráculo desta wiki, e ele é o app rodando |
| Cada papel entra onde deve e é barrado onde não deve? | **sim** — o oráculo é `roles.painel` + `User::canAccessPanel()`, que os PRDs descrevem |
| A tela X faz exatamente o que o usuário pediu quando pediu a feature X? | **não** — esse oráculo foi perdido com o requisito bruto das seis features |

Por isso a decomposição abaixo tem cláusulas de **regressão de superfície**, não de regra de
negócio. Regra de negócio continua coberta pelas 213 asserções de `tests/Kit` e `tests/Tenancy`.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Instalar Pest 5 e o plugin `pest-plugin-browser`, deixando o projeto apto a usar a atualização da skill | "instalar a nova versão do pest5 e do plugin pest-browser para fazer uso da atualização da skill" | restrição |
| RQ-02 | Criar uma wiki de regressão cobrindo **todas** as telas criadas no projeto | "crie uma wiki agora de regrassão de todas as telas criadas no projeto" | funcional |
| RQ-03 | Provar que as telas estão funcionando — não só documentá-las | "e teste se elas estão funcionando" | funcional |
| RQ-04 | Aceitar os PRDs como oráculo desta rodada, no lugar do requisito bruto ausente | "essa rodada será confiando no que esta nos PRDs" | restrição |
| RQ-05 | Validar os **perfis**: quem entra em qual painel, e quem é barrado | "valide as telas e também os perfis" | autorização |
| RQ-06 | Validar o **dark mode** das telas | "além de validar por hora a questão do darkmode" | funcional |
| RQ-07 | Identificar dívidas técnicas para correção antes das próximas evoluções | "identificar possiveis dividas tecnicas para correção, antes das proximas evoluções" | não-funcional |
| RQ-08 | Fazer commits individualizados | "faça commits individualizados" | restrição |
| RQ-09 | Trabalhar numa branch exclusiva para esta wiki | "pode criar uma branch exclusiva para tratar dessa wiki" | restrição |
| RQ-10 | Playwright MCP é permitido nesta rodada, mas a skill executa via `pest-browser` | "pode usar o mcp do playwrite também nessa rodada, mas a skill trata usando o pest-browser" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-02 — "todas as telas"**: o projeto tem **74 rotas GET** nos três painéis
  (`php artisan route:list --method=GET`). Delas, 13 exigem um `{record}` no path, 3 são
  endpoints JSON de passkey e 6 exigem estado ou token na query (`/*/screen/lock`,
  `/*/password-reset/reset`). Sobram **52 telas** alcançáveis por URL fixa.
  **Assumido**: "todas as telas" = as 52 alcançáveis por URL fixa, mais as 13 de `{record}`
  cobertas por fixture onde a wiki de origem já as cobre em `tests/Kit`. Registrado como
  premissa em ADR-02, não como pergunta bloqueante — a leitura alternativa (só as telas
  próprias do kit, ignorando as de plugin) entregaria menos do que foi pedido.

- **RQ-06 — "por hora a questão do darkmode"**: "por hora" lido como **"por ora"** (nesta
  rodada), e não como "de hora em hora". Sob a segunda leitura o pedido seria um agendamento
  recorrente, que nada mais no requisito sustenta. **Assumido**: validar dark mode agora,
  nesta wiki.

- **RQ-07 — "dívidas técnicas"**: sem critério de severidade no requisito. **Assumido**:
  classificar cada dívida em bloqueante / relevante / cosmética, e **não corrigir nenhuma
  nesta wiki** — o requisito pede "identificar (…) antes das próximas evoluções", o que
  coloca a correção depois desta entrega. Correção viraria escopo que o usuário não pediu.

## Fora de Escopo (declarado)

- **Corrigir** as dívidas encontradas — RQ-07 pede identificar, e as posiciona *antes* das
  próximas evoluções. A correção é a evolução seguinte.
- Reconstruir o `00-requisito.md` das seis wikis anteriores — RQ-04 dispensa explicitamente.
- Testar o modo multi-tenancy em browser: `kit.tenancy.enabled` é opt-in e desligado por
  default; ligá-lo reescreve as rotas para `/app/{tenant}` e muda o inventário de telas
  inteiro. `tests/Tenancy` já cobre o recorte em nível HTTP.
- Baseline de screenshot (`assertScreenshotMatches()`): sem baseline versionado e revisado,
  é flake garantido — a própria skill proíbe.
