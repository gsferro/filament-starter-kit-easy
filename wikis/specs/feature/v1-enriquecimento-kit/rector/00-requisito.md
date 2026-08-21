# Requisito — Rector no pipeline de qualidade do kit

## Fonte

- **Origem**: mensagem do usuário no chat, invocando `/feature-wiki`
- **Data**: 2026-08-18
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito, colado verbatim abaixo)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> veja o pacote: https://github.com/driftingly/rector-laravel
> - analise se o rector pode fazer parte do starter-kit como parte da qualidade do codigo e das validações no lint
> - veja se existem regras especificas para o Filament v5 (base do starter-kit) e usaremos a partir do laravel 13 (e das outras stacks basicas que já estão no composer.json) como base de partida
> - se agregar valor ao projeto, instalei e documente bem tanto das @wikis\ quanto nos @README.md do projeto

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Analisar o pacote `driftingly/rector-laravel` | "veja o pacote: https://github.com/driftingly/rector-laravel" | funcional |
| RQ-02 | Avaliar se o Rector pode fazer parte do kit como qualidade de código | "analise se o rector pode fazer parte do starter-kit como parte da qualidade do codigo" | funcional |
| RQ-03 | Avaliar especificamente a entrada dele nas **validações do lint** | "e das validações no lint" | funcional |
| RQ-04 | Verificar se existem regras específicas para **Filament v5** | "veja se existem regras especificas para o Filament v5 (base do starter-kit)" | funcional |
| RQ-05 | Tomar **Laravel 13** como base de partida das regras | "usaremos a partir do laravel 13 ... como base de partida" | restrição |
| RQ-06 | Considerar também as demais stacks já presentes no `composer.json` | "(e das outras stacks basicas que já estão no composer.json)" | restrição |
| RQ-07 | Instalar **se** agregar valor ao projeto | "se agregar valor ao projeto, instalei" | funcional (condicional) |
| RQ-08 | Documentar bem nas wikis | "documente bem tanto das @wikis\\" | funcional |
| RQ-09 | Documentar bem nos READMEs do projeto | "quanto nos @README.md do projeto" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-02 versus RQ-03 — são duas perguntas, não uma.** "Fazer parte da qualidade do código" e
  "entrar nas validações do lint" descrevem papéis diferentes para a mesma ferramenta:

  | Papel | O que significa na prática |
  |---|---|
  | **Lint / gate contínuo** | `rector --dry-run` dentro de `composer test`; CI reprova enquanto houver diferença |
  | **Ferramenta de upgrade** | `rector process` rodado sob demanda, ao subir major de Laravel ou PHP |

  Os dois foram avaliados separadamente, com medição, e a resposta é **diferente para cada um** —
  ver a Matriz de Decisão do `01-plano-acao.md`.
  - **Assumido**: as duas perguntas merecem resposta própria, e a entrega responde as duas.
  - **Se negado**: se o usuário quis apenas "coloque no lint", a resposta medida é **não** — e o
    motivo está em ADR-02, com o defeito concreto que isso reintroduziria.

- **RQ-07 é condicional, e a condição foi medida.** "Se agregar valor" não é opinião: foi resolvido
  por `--dry-run` sobre o código real do kit, contando arquivos e regras aplicadas.
  - **Assumido**: agrega valor **no papel de upgrade** e **não agrega no papel de lint**. A adoção
    reflete essa divisão.
  - **Se negado**: se o usuário quiser o Rector no `composer test` mesmo assim, é uma linha em
    `composer.json` — mas ver ADR-02 antes, porque o preço está medido.

## Fora de Escopo (declarado)

- Rodar `rector process` de verdade sobre o código do kit (o que muda 103 arquivos). Esta entrega
  configura e documenta; aplicar refatoração em massa é decisão e revisão do mantenedor.
- Escrever regras Rector próprias para o kit ou para Filament.
- Substituir Pint, PHPStan ou FilaCheck. Nenhum dos três sai.
