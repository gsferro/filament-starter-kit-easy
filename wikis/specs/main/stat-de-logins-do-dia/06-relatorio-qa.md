# Relatório de QA — Stat de logins do dia

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil: completo · UI com Chart.js

## Veredito — Ciclo 1

**APROVADO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- RQ-01..RQ-06 rastreados ao sexto stat, valor diário, fonte de autenticação e série de sete dias.
- Sem tabela do pacote, somente o sexto stat é omitido; os cinco anteriores permanecem.

## Evidências

- `tests/Kit/StatDeLoginsDoDiaTest.php`
- Suíte focal + regressão: 114 testes, 163 assertions, todos verdes.
- Mutation score registrado pela implementação: 91,36% no widget.

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ | nenhuma cláusula órfã |
| B | Fronteiras | ✅ | meia-noite, sete dias, dias vazios e tabela ausente |
| C | Permissão | ✅ | widget preexistente mantém seu gate |
| D | Observabilidade | ✅ | leitura não gera log duplicado |
| E | Performance | ✅ | agregação diária no banco via Trend |
| F | UX de erro | ✅ | degrada para cinco stats sem afirmar zero falso |
| G | Tema | ✅ | chartColor semântico do Filament |
| H | Acessibilidade | ✅ | Stat nativo; sem controle interativo novo |
| I | Segurança | ✅ | somente leitura agregada |
| J | Regressão | ✅ | cinco stats e inventário preservados |
| K | Adequação dos testes | ✅ | valor, série, cardinalidade e estrutura afirmados |

## Não verificado

- Aparência pixel a pixel do canvas — sem baseline visual; dados e montagem do gráfico foram verificados programaticamente.
