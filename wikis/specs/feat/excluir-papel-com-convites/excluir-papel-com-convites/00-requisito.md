# Requisito — Roadmap item 9: excluir pela tela um papel que tem convite

## Fonte

- **Origem**: `wikis/roadmap.md`, seção "9. Excluir pela tela um papel que tem convite pendente responde 500" (medido em 2026-09-26 pela wiki `phpstan-nivel-8`, premissa P-07), mais o pedido do mantenedor no chat em 2026-11-16 e as duas respostas dele às perguntas Q1 e Q2 (ferramenta de perguntas do chat, escolha entre opções escritas)
- **Data**: 2026-11-16
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito no roadmap e respostas escolhidas por escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Pedido no chat (2026-11-16):**

> abra uma /feature-wiki  para resolver de 1 a 4 que são as pendencias para depois que tudo estiver resolvido, vamos para novas melhorias

(o item 1 da lista oferecida era "Papel com convite: erro 500 — Item 9 do roadmap: excluir pela tela um papel que tem convite pendente responde 500. Pede uma decisão de produto: recusar com um aviso que nomeia os convites, ou cancelá-los junto.")

**`wikis/roadmap.md`, item 9:**

> A FK `convites.role_id` **sem cascade** (`database/migrations/2026_08_13_000002_create_convites_table.php`)
> é deliberada: apagar o papel de um convite pendente tem de doer na hora, em vez de deixar o convite
> aceitar com papel nulo. A recusa está certa. O que está errado é **como** ela chega: a ação de
> excluir do `RoleResource` não trata a violação, e o administrador vê o 500 do `QueryException`.
>
> **Fica para depois porque** não é nulidade, não é o que a wiki `phpstan-nivel-8` pediu, e a saída
> certa é decisão de produto: recusar com aviso nomeando os convites pendentes, ou oferecer
> cancelá-los junto. O invariante já está travado por `[CT-12]` da wiki `phpstan-nivel-8`; falta a UX.

**Q1 — "Hoje, excluir um papel que tem convite pendente mostra um erro 500. O que deve acontecer no lugar dele?" — resposta escolhida:**

> Recusar e listar (Recomendado) — A exclusão é recusada com um aviso que lista os e-mails dos convites pendentes. O admin revoga esses convites na tela de Convites e tenta de novo. Nada é apagado sem que ele veja.

**Q2 — "Convites já aceitos, recusados ou expirados também bloqueiam a exclusão do papel (hoje dão o mesmo erro 500). O que fazer com eles?" — resposta escolhida:**

> Apagar junto (Recomendado) — Esses convites não servem mais para entrar no sistema. Somem junto com o papel, e o registro de quem convidou quem continua na auditoria. Só o convite pendente segue a escolha da pergunta anterior.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Excluir pela tela um papel que tem convite pendente não responde 500 | "o administrador vê o 500 do `QueryException`" / "falta a UX" | funcional | fechada |
| RQ-02 | A exclusão de um papel com convite pendente é recusada: o papel continua existindo | "A recusa está certa" / "A exclusão é recusada" | funcional | fechada |
| RQ-03 | A recusa mostra um aviso que lista os e-mails dos convites pendentes do papel | "com um aviso que lista os e-mails dos convites pendentes" | funcional | fechada |
| RQ-04 | Recusada a exclusão, nada é apagado — nem o papel, nem convite algum | "Nada é apagado sem que ele veja" | funcional | fechada |
| RQ-05 | O convite pendente continua aceitável com o mesmo papel depois da recusa (o invariante do `[CT-12]` da `phpstan-nivel-8` continua valendo) | "apagar o papel de um convite pendente tem de doer na hora, em vez de deixar o convite aceitar com papel nulo" | restrição | fechada |
| RQ-06 | Depois de revogar os convites pendentes na tela de Convites, o admin consegue excluir o papel | "O admin revoga esses convites na tela de Convites e tenta de novo" | funcional | fechada |
| RQ-07 | Convites aceitos, recusados ou expirados de um papel não impedem a exclusão: são apagados junto com o papel | "Esses convites não servem mais para entrar no sistema. Somem junto com o papel" | funcional | fechada |
| RQ-08 | A remoção dos convites aceitos, recusados ou expirados fica registrada na auditoria | "o registro de quem convidou quem continua na auditoria" | não-funcional | fechada |
| RQ-09 | Num papel com convite pendente e também com convites aceitos, recusados ou expirados, prevalece a recusa: nenhum deles é apagado | "Só o convite pendente segue a escolha da pergunta anterior" + "Nada é apagado sem que ele veja" | funcional | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Excluir papel com convite pendente: recusar e listar, ou revogar junto? | RQ-02, RQ-03, RQ-04 | recusar e listar | respondida no Texto Original (2026-11-16) |
| Q2 | Convites aceitos, recusados ou expirados: apagar junto, ou recusar também? | RQ-07, RQ-08 | apagar junto | respondida no Texto Original (2026-11-16) |
| Q3 | Na recusa, cada papel bloqueado aparece pelo nome técnico (`suporte`) ou pelo rótulo que a tela de papéis usa como título do registro? | RQ-03, P-03 | o rótulo | aberta — premissa P-07 vigente até a resposta |
| Q4 | Quem pode excluir papel mas não pode revogar convite: a exclusão apaga também os convites aceitos, recusados e expirados do papel? | RQ-07, RQ-08 | sim — a resposta a Q2 não condiciona a permissão, e a remoção fica na auditoria | aberta — premissa P-08 vigente até a resposta |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | "Pendente" é o mesmo estado que a coluna "Situação" da tela de Convites mostra como Pendente: nem aceito, nem recusado, e com validade no futuro. Convite sem validade gravada conta como expirado | step 3 — leitura do model de convite | 2026-11-16 | RQ-02, RQ-07 | vigente |
| P-02 | A recusa vale para os três caminhos de exclusão da tela de papéis: a ação da linha da tabela, a ação da página de edição e a exclusão em massa | step 3 — as três ações existem | 2026-11-16 | RQ-01, RQ-02 | vigente |
| P-03 | Na exclusão em massa, se algum papel selecionado tem convite pendente, nada da seleção é excluído e o aviso lista os papéis bloqueados com os e-mails de cada um (falha fechada, igual à exclusão de um só) | step 4 — premissa de comportamento, opção que falha fechada | 2026-11-16 | RQ-02, RQ-03, RQ-04 | vigente |
| P-04 | O aviso lista no máximo 5 e-mails por papel e diz quantos faltam ("e mais N") | step 4 — semente "teto de todo texto livre" | 2026-11-16 | RQ-03 | vigente |
| P-06 | O aviso só mostra os e-mails para quem pode ver os convites (`ViewAny` de convite); para quem só pode excluir papéis, mostra a quantidade de convites pendentes por papel | step 4 — semente de visibilidade: excluir papel não é permissão para ler e-mail de terceiro, a mesma regra da ação "Usuários" da tela de papéis | 2026-11-16 | RQ-03 | vigente |
| P-07 | No aviso, cada papel bloqueado aparece pelo rótulo que a tela de papéis usa como título do registro | step 7 — Q?1 da `feature-test-design` (Q3) | 2026-11-16 | RQ-03, P-03 | vigente |
| P-08 | Os convites encerrados saem com o papel para qualquer pessoa autorizada a excluir o papel, sem exigir permissão de excluir convite | step 7 — Q?2 da `feature-test-design` (Q4); leitura literal da resposta a Q2 | 2026-11-16 | RQ-07, RQ-08 | vigente |
| P-09 | A exclusão do papel é atômica: se qualquer passo falhar (inclusive por um convite criado ou reenviado no meio), nada muda — o papel mantém permissões, usuários e convites encerrados; e um convite que esteja pendente no momento da limpeza nunca é apagado como encerrado | step 9 — CR-01 (`/code-review`), RD-01 (`fw-revisor-diff`) e o revisor `codex`, convergentes | 2026-11-17 | RQ-04, RQ-07, RQ-09 | vigente |
| P-10 | Para quem não pode ver os convites (`ViewAny` de convite), o aviso de recusa não manda revogar "na tela de Convites", que essa pessoa não alcança: diz para pedir a quem administra os convites que os revogue. Para quem pode ver, a frase continua apontando a tela de Convites | step 11 — QA-04 (`fw-qa-gate`, ciclo 1) | 2026-11-17 | RQ-06, P-06 | vigente |
| P-05 | Os convites de todas as organizações contam, inclusive com tenancy ligada: o banco recusa por qualquer convite, não só pelos da organização corrente | step 3 — o convite tem escopo de organização no painel `/app`, e a chave estrangeira não | 2026-11-16 | RQ-02, RQ-07 | vigente |

## Fora de Escopo (declarado)

- Revogar convites pendentes a partir da tela de papéis (Q1 escolheu recusar; a revogação continua na tela de Convites)
- Mudar a chave estrangeira `convites.role_id` (continua sem cascade)
- O que acontece com os usuários que têm o papel: o comportamento atual de excluir o papel não muda para eles
