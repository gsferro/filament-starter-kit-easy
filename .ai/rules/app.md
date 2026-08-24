---
paths:
  - 'app/**'
---

# App

## Papel se atribui dentro de ContextoDePapeis, nunca com assignRole cru
Com `permission.teams` ligado, `assignRole()` grava em `model_has_roles.team_id` o contexto **corrente do request** — e a relação `roles` do spatie é filtrada pelo team do request na leitura. Atribuir no contexto errado produz o pior sintoma possível: a pessoa autentica, o painel responde **200**, e ela não vê nada.

Foi Blocker na v0.19.1. `User::aprovar()` chamava `assignRole()` cru, e quem aprova está no /admin, cujo contexto é o global: o papel ia para `team_id = 0` com a organização em `id = 1`. E o estado era sem saída pela própria tela — a ação tem `visible = aprovacao_pendente` e a pendência é baixada antes, então a mesma tela não conserta o que acabou de fazer.

Use `App\Support\ContextoDePapeis::em($contexto, $usuario, $callback)`. Ele fixa o team, limpa o cache da relação nas duas pontas e restaura no `finally`.

O contexto certo: papel de painel COM tenancy (`app`) vai no `team_id` da organização — um por organização, se houver várias. Papel de painel SEM tenancy (`admin`, `infra`) vai em `Tenant::CONTEXTO_GLOBAL`, porque ser admin dentro de uma organização não é credencial para administrar a instalação. Ver `Convite::contextoDoPapel()` para o precedente.

E dentro do callback use `assignRole()`, NUNCA `sync()` na relação: o sync escreve só as colunas da chave e estoura `NOT NULL constraint failed: model_has_roles.team_id`.

Sinal de alerta na revisão: um `assignRole()` ou `syncRoles()` que não esteja dentro de um `ContextoDePapeis::em()`. Nesta rodada, `aprovar()` era o único caminho novo que escapava — o convite, o cadastro pelo registro aberto e o gerenciador de usuários da organização já passavam por lá.
