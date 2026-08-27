# Requisito — Status de ativo/inativo e exclusão lógica de usuário

## Fonte

- **Origem**: texto colado no chat pelo solicitante, repassado pelo agente coordenador ao
  sub-agente desta wiki
- **Data**: 2026-08-26
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, copiado sem alteração

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos ter o status de ativo ou inativo no login.
> - caso o login seja desativado, o usuário que tentar logar, deve cair me uma tela de aviso (talvez uma do pacote sentinel ou outra pagina) informando que o link dele esta desativado e para entrar em contato com o admin para reativar
> - registrar tentativas de logins de usuários desativados
> - isso também é valido para os logins sociais
> - acredito que seja um ponto otimo para ao inves de simplesmente excluir um usuário
> - também precisamos log de tentativas de acesso de usuários excluidos dizendo quando o login foi excluido e pedindo para entrar em contato com o admin
> - no caso de exclusão, no "/admin" ou no "/infra" reativar a exclusão do lixo, já que usaremos a exclusão logica
> - então agora, o usuário tem status de ativo ou inativo e pode ser excluido (logicamente)
> - deixe isso muito bem documentado no @README.md
> - use sub-agente e worktree para rodar em paralelo

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O usuário tem um estado **ativo** ou **inativo**, e esse estado é consultado no login | "precisamos ter o status de ativo ou inativo no login" / "o usuário tem status de ativo ou inativo" | funcional |
| RQ-02 | Usuário **inativo** que tenta entrar com a senha certa **não abre sessão** | "caso o login seja desativado, o usuário que tentar logar" | autorização |
| RQ-03 | Em vez do erro genérico de credenciais, o inativo **cai numa tela de aviso** própria (no estilo das páginas de erro do Sentinel, ou outra página) | "deve cair me uma tela de aviso (talvez uma do pacote sentinel ou outra pagina)" | funcional |
| RQ-04 | A tela de aviso do inativo diz que o acesso dele está **desativado** e pede para **entrar em contato com o administrador** para reativar | "informando que o link dele esta desativado e para entrar em contato com o admin para reativar" | funcional |
| RQ-05 | Toda **tentativa de login de usuário inativo** fica **registrada** | "registrar tentativas de logins de usuários desativados" | não-funcional |
| RQ-06 | As cláusulas RQ-02 a RQ-05 valem **também para o login social** (os quatro provedores) | "isso também é valido para os logins sociais" | funcional |
| RQ-07 | Desativar é oferecido como alternativa a excluir: quem administra pode **desativar** (e, por simetria, **reativar**) um usuário | "acredito que seja um ponto otimo para ao inves de simplesmente excluir um usuário" | funcional |
| RQ-08 | A exclusão de usuário passa a ser **lógica** (soft delete), não remoção da linha | "já que usaremos a exclusão logica" / "pode ser excluido (logicamente)" | funcional |
| RQ-09 | Toda **tentativa de acesso de usuário excluído** fica **registrada** em log | "também precisamos log de tentativas de acesso de usuários excluidos" | não-funcional |
| RQ-10 | O usuário excluído que tenta entrar vê um aviso dizendo **quando** o login foi excluído e pedindo para **entrar em contato com o administrador** | "dizendo quando o login foi excluido e pedindo para entrar em contato com o admin" | funcional |
| RQ-11 | Usuário excluído pode ser **restaurado da lixeira** pelo `/admin` ou pelo `/infra` | "no '/admin' ou no '/infra' reativar a exclusão do lixo" | funcional |
| RQ-12 | Tudo isso fica **muito bem documentado no README** | "deixe isso muito bem documentado no @README.md" | restrição |
| RQ-13 | A execução usa **sub-agente e worktree** em paralelo | "use sub-agente e worktree para rodar em paralelo" | restrição (processo) |

## Ambiguidades e Perguntas Abertas

- **RQ-03 / RQ-10** — "tela de aviso (talvez uma do pacote sentinel ou outra pagina)". O 403 do
  Sentinel só exibe o motivo fora de produção (`resources/views/errors/403.blade.php`, bloco
  `$mostrarDiagnostico`), então reaproveitar o 403 cru não entrega a frase pedida em produção.
  - **Assumido**: uma página própria, `resources/views/auth/conta-indisponivel.blade.php`, que
    **estende o layout do Sentinel** (`errors.sentinel-layout`) — mesma cara, mesmo número de
    mensagem `SNT-403-…`, e o texto pedido aparece em qualquer ambiente. Servida por uma rota
    pública que só renderiza com o aviso na sessão (sem aviso, volta ao login).
  - **Se negado**: troca-se a view; RQ-03/RQ-04/RQ-10 mantêm os casos, muda só o oráculo de texto.
- **RQ-10** — o aviso do excluído revela que **existe** uma conta com aquele e-mail. Isso é
  informação para quem tenta e-mails ao acaso (enumeração de contas), e o kit hoje responde a
  mesma mensagem genérica para "não existe" e "senha errada".
  - **Assumido**: o aviso do excluído (e o do inativo) só aparece quando a **senha confere**. Com
    senha errada, a resposta é a genérica de credenciais inválidas, igual à de hoje. No login
    social a prova equivalente é o e-mail verificado no provedor, que já é a barreira existente.
  - **Se negado**: remove-se o `Hash::check()` do interceptor; um caso de teste (senha errada →
    genérico) inverte a asserção.
- **RQ-07** — quem pode desativar/reativar, e a partir de qual painel. Excluir usuário é "ato
  global" que o kit proíbe no `/app` (ADR-01 de `travas-de-exclusao-e-upload-anonimo`).
  - **Assumido**: desativar/reativar segue a **mesma régua da exclusão** — só no `/admin`, com
    permissões próprias (`Desativar:User`, `Reativar:User`). O `/app` mostra o estado (coluna e
    filtro), mas não oferece a ação, porque desativar tira a pessoa de **todas** as organizações.
  - **Se negado**: acrescentam-se as duas ações ao `UserResource` do `/app` (uma linha cada) e
    dois casos de permissão; a matriz de `panel_user` já as subtrai por FQCN.
- **RQ-07** — desativar a si mesmo, e desativar o último `master_global` ativo.
  - **Assumido**: os dois são recusados — no model (barreira para qualquer chamador) e na tela (a
    ação não aparece). Não pedido literalmente; é a trava que evita uma instalação sem ninguém que
    consiga reativar.
  - **Se negado**: remove-se a guarda; dois casos de teste saem.
- **RQ-11** — "no `/admin` ou no `/infra`". A Lixeira (`promethys/revive`) existe só no `/infra`,
  e o papel `admin` não entra no `/infra`.
  - **Assumido**: os dois. No `/infra`, `User::class` entra na lista de models da Lixeira (a tela
    existente). No `/admin`, a própria lista de usuários ganha o filtro "Lixeira" e a ação
    "Restaurar" nativos do Filament, autorizados pela permissão `Restore:User` que a policy já
    declara. Nenhuma tela nova.
  - **Se negado** (só `/infra`): removem-se filtro e ação do `/admin`; um caso de teste sai.
- **RQ-08** — e-mail de conta na lixeira. `users.email` é único e a linha continua lá.
  - **Assumido**: o e-mail fica **reservado** enquanto a conta estiver na lixeira — o formulário
    de usuário, o registro aberto e o convite recusam criar outra conta com ele (a regra `unique`
    do Laravel não conhece soft delete, então já recusa sem código novo). Quem quiser o e-mail de
    volta restaura ou exclui definitivamente.
  - **Se negado**: as regras `unique` passam a ignorar `deleted_at` e a exclusão definitiva vira
    obrigatória antes de recriar — decisão maior, registrada como fora desta entrega.
- **RQ-13** — é restrição de processo (o coordenador já a cumpriu ao abrir este worktree). Não
  gera passo de implementação nem caso de teste.

## Fora de Escopo (declarado)

- Interruptor em Settings para a feature: status é **dado** do usuário, não configuração da
  instalação.
- Desativação automática (por inatividade, por tentativas, por prazo).
- Exclusão **definitiva** (`forceDelete`) pela tela: a permissão `ForceDelete:User` existe na
  policy, mas a ação não é oferecida nesta entrega — a lixeira restaura, não apaga.
- Notificar por e-mail a pessoa desativada ou excluída.
- Aviso em tela para quem já está **autenticado** e é desativado no meio da sessão: o
  `canAccessPanel()` passa a negar no request seguinte (403 do Sentinel, como já acontece com o
  cadastro pendente); a tela de aviso própria é do **login**.
