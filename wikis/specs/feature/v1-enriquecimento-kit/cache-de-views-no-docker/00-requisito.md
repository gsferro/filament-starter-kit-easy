# Requisito — Cache de views no boot do container

## Fonte

- **Origem**: conversa no chat, sequência de mensagens do usuário
- **Data**: 2026-08-19
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

Mensagem que pediu a verificação:

> sim, verifica o view:cache no kit:install e no Dockerfile

Mensagem que aprovou a implementação, após o relatório da verificação:

> sim, abre a wiki e implementa

## Contexto da origem (não é requisito, é rastro)

O pedido nasceu de um achado meu, no fim de uma investigação de performance:

> "o alvo real que apareceu é outro: **a primeira request de cada painel custa ~3s de
> compilação de view**. Isso é `php artisan view:cache` no deploy — e vale checar se o
> `kit:install` e o Dockerfile já fazem."

A verificação respondeu: **nenhum dos dois faz**, e ainda revelou uma armadilha que muda o
lugar da correção — ver RQ-04.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Verificar se o `kit:install` faz cache de views | "verifica o view:cache no kit:install" | funcional |
| RQ-02 | Verificar se o `Dockerfile` faz cache de views | "e no Dockerfile" | funcional |
| RQ-03 | Abrir wiki para a correção | "sim, abre a wiki" | funcional |
| RQ-04 | Implementar a correção | "e implementa" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-04 — "implementa" o quê, exatamente?** O pedido veio depois de um relatório que já
  separava três coisas, e a aprovação foi ao conjunto do relatório.
  - **Assumido**: implementar o que o relatório recomendou, e **não** o que a pergunta
    original sugeria. O relatório concluiu que `RUN php artisan view:cache` no Dockerfile
    **não teria efeito** (o volume `app-storage` encobre `storage/`), e que a correção certa
    é no **boot do container**.
  - **Se negado**: se a intenção era mesmo um `RUN` no Dockerfile, a implementação seria
    inócua — os 39s continuariam sendo pagos em runtime. A medição está em ADR-01.

- **RQ-01 — o `kit:install` deve passar a fazer cache?** A verificação disse que não, e a
  aprovação veio junto do relatório que dizia isso.
  - **Assumido**: **não mexer** no `kit:install`. É instalação local; view cacheada obriga
    `view:clear` a cada edição de Blade, contra o loop de desenvolvimento que o kit protege
    com `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`.
  - **Se negado**: uma linha no `KitInstall::publicarAssets()` resolve, e o custo é o dev
    perder o hot reload de Blade.

## Fora de Escopo (declarado)

- `config:cache` e `route:cache` — os dois são **perigosos aqui**, e o motivo está em ADR-02.
- Otimizar o tempo de compilação em si (417 views é o que o kit tem).
- Qualquer mudança que afete `composer dev`, a suíte de testes ou instalação local.
