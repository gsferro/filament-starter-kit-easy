---
title: Usuário ativo, inativo e excluído
parent: Autenticação
grand_parent: Português
nav_order: 5
---

# Usuário ativo, inativo e excluído

A conta de cada usuário tem **três estados** e eles vencem o acesso a qualquer painel:

- **Ativo**: entra com senha ou login social normalmente.
- **Inativo**: a senha ou o login social reconhece a conta, mas a pessoa cai num aviso dizendo que ela foi **desativada** e pedindo para entrar em **contato com o administrador** para **Reativar**; a sessão não abre.
- **Excluído**: a exclusão é lógica; quem tentar entrar cai num aviso com a data de exclusão e, a depender do papel, pode **Restaurar** pela lista de usuários no `/admin` ou pela **Lixeira** no `/infra`.

Quem tem a permissão `Desativar:User` vê a ação de desativar na lista do `/admin` e a permissão `Reativar:User` vê a ação correspondente. A própria pessoa não consegue desativar a própria conta, e o sistema recusa desativar o último `master_global` ativo. A proteção vale no login por **senha**, no login social e na confirmação por link.

