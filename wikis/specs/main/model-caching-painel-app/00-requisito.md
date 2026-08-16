# Requisito — Model Caching padrão no painel /app

## Fonte

- **Origem**: conversa com usuário (solicitação direta no chat)
- **Data**: 2026-08-16
- **Autor / solicitante**: usuário
- **Fidelidade**: baixa (descrição verbal — confirmar antes de implementar)

## Texto Original

> - precisamos usar a skill feature-wiki para implementar o pacote das models e isso ser uma rule para TODAS as models de painel de app como padrão.
> - crie testes garantindo que se o MODEL_CACHE_ENABLED estiver como true, o cache é implementando e se não, não esta sendo usando, dentro dos testes do Kit
> - é importante que os pacotes que são ativados via config ou env, tenham testes no kit para garantir que estejam configurados e funcionando.
> - faça o commit somente dos arquivos que voce alterar nessa sessao indivializados.
> - ao final do ciclo da skill feature-wiki, pode implementar.
> - lembre-se de deixar tudo documentando nos Readmes do kit
> - talvez seja legal termos uma sessão para dizer quantos testes temos e o que esta coberto como forma de demonstrar qualidade do nosso starter-kit. Pense se agrega valor e adicione se valer a pena.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Implementar o `mike-bronner/laravel-model-caching` como padrão para todas as models do painel `/app` | "implementar o pacote das models" + "regra para TODAS as models de painel de app como padrão" | funcional |
| RQ-02 | Criar testes em `tests/Kit` que provem que, quando `MODEL_CACHE_ENABLED=true`, o cache está sendo utilizado | "testes garantindo que se o MODEL_CACHE_ENABLED estiver como true, o cache é implementando" | funcional |
| RQ-03 | Criar testes em `tests/Kit` que provem que, quando `MODEL_CACHE_ENABLED=false`, o cache NÃO está sendo utilizado | "se não, não esta sendo usando" | funcional |
| RQ-04 | Garantir que pacotes ativados via `.env`/config tenham testes no kit validando configuração e funcionamento | "pacotes que são ativados via config ou env, tenham testes no kit" | não-funcional |
| RQ-05 | Documentar a implementação nos READMEs do kit | "deixar tudo documentando nos Readmes do kit" | não-funcional |
| RQ-06 | Avaliar e, se valer a pena, adicionar uma seção sobre quantidade e cobertura de testes no kit | "sessão para dizer quantos testes temos e o que esta coberto" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-01**: "todas as models de painel de app" — confirmar se isso inclui apenas as models que têm Resource no painel `/app` (`User`, `Convite`, `Projeto`) ou também models relacionadas (ex: `Tenant`, `Role`).
  - **Assumido**: são as models que possuem Resource no painel `/app`: `User`, `Convite`, `Projeto`.
  - **Se negado**: ajustar a lista de models e os testes.

## Fora de Escopo (declarado)

- Alterar o `.env` padrão para ligar `MODEL_CACHE_ENABLED`. O default continua `false` até decisão do usuário.
- Testes de browser/CT-B para model caching — a validação é por teste de componente/kit.
