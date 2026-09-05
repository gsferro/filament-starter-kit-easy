# Requisito — Insights das organizações no `/admin`

## Fonte

- **Origem**: pedido do usuário no chat, via invocação da skill `feature-wiki`
- **Data**: 2026-09-04
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, colado verbatim abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> temos como fazer um controle de quantos acessos cada painel/tenant teve?!
> - quero que voce adicione mais widgets na tela de cadastro dos tenants dentro do admin
> - se conseguissemos contar, usando o pacote do logs de acesso, quantos usuários, exclusivos de cada tenant, seria um bom widgets
> - exibir um widget de timeline dos ultimos dados atualizados e outros insights, que voce tenha para enriquecer a tela

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O sistema registra e exibe quantos acessos cada **painel** teve | "temos como fazer um controle de quantos acessos cada painel/tenant teve?!" | funcional |
| RQ-02 | O sistema exibe quantos acessos cada **organização** (tenant) teve | "temos como fazer um controle de quantos acessos cada painel/tenant teve?!" | funcional |
| RQ-03 | A tela de cadastro das organizações no painel `admin` ganha widgets novos | "quero que voce adicione mais widgets na tela de cadastro dos tenants dentro do admin" | funcional |
| RQ-04 | Um widget conta os **usuários únicos** de cada organização | "quantos usuários, exclusivos de cada tenant, seria um bom widgets" | funcional |
| RQ-05 | A contagem de RQ-04 sai do **pacote de logs de acesso** já instalado | "usando o pacote do logs de acesso" | restrição |
| RQ-06 | Um widget de **timeline** exibe os últimos dados atualizados | "exibir um widget de timeline dos ultimos dados atualizados" | funcional |
| RQ-07 | Widgets adicionais de insight enriquecem a tela, a critério do agente | "e outros insights, que voce tenha para enriquecer a tela" | funcional (aberto) |

## Ambiguidades e Perguntas Abertas

Todas as quatro foram levadas ao usuário antes de o plano ser escrito. As respostas estão
registradas como decisão, não como suposição.

- **RQ-03** — "tela de cadastro dos tenants" é a listagem (`/admin/organizacoes`) ou a tela de
  um registro?
  - **Respondido pelo usuário**: **as duas**. Widgets agregados na listagem (`ListTenants`) e
    widgets do registro na tela de visualização (`ViewTenant`).

- **RQ-01** — a tabela `authentication_log` do pacote **não tem coluna de painel**. O registro é
  um `morphTo` para `User` e nada mais (verificado em
  `vendor/rappasoft/laravel-authentication-log/src/Models/AuthenticationLog.php`). "Acessos por
  painel" não é derivável do dado existente.
  - **Respondido pelo usuário**: **gravar o painel no login** — migration acrescenta a coluna e
    o kit carimba o painel corrente no momento em que a linha nasce.
  - **Consequência aceita**: o dado só existe do deploy em diante. Toda linha anterior fica com
    `painel` nulo, e o widget precisa dizer isso em vez de contar nulo como zero.

- **RQ-04** — "usuários exclusivos" significa usuários **distintos** que acessaram, ou usuários
  que pertencem **só** àquela organização?
  - **Respondido pelo usuário**: **usuários distintos (únicos)**. Uma pessoa vinculada a duas
    organizações conta nas duas.

- **RQ-02** — a tabela de log também **não tem coluna de organização**, e não adianta carimbá-la
  no login: no painel `/app` a organização é escolhida **depois** da autenticação, então no
  instante do `Login` não há tenant corrente.
  - **Assumido**: acesso por organização é derivado do **vínculo** — join
    `authentication_log` → `users` → `tenant_user`. É a leitura que RQ-04 já exige, e ela
    responde RQ-02 sem coluna nova.
  - **Se negado**: RQ-02 muda de escopo e exige carimbar a organização na troca de tenant (não
    no login), o que é outra feature.

### Devolvidas pela derivação dos casos de teste

Quatro perguntas novas, levantadas pela `feature-test-design` ao derivar o `04`. Nenhuma delas foi
respondida pelo solicitante — todas seguem como premissa explícita, com o custo de errar escrito.

- **Qual é a janela das métricas?** O requisito não menciona período nenhum.
  - **Assumido**: 30 dias, inclusiva na borda.
  - **Se negado**: os cenários CT-08, CT-09 e CT-10 mudam de valor, não de forma. O número vive
    em um lugar só por widget.

- **RQ-02 / RQ-04 — organização inativa (`ativo = false`) entra nas métricas?**
  - **Assumido**: entra. O requisito diz "cada tenant", sem recorte.
  - **Se negado**: o filtro entra nas consultas dos passos 3, 5 e 9 do plano, e CT-08 ganha uma
    organização inativa no arranjo.

- **RQ-04 — usuário excluído (soft delete) conta?** O vínculo dele permanece em `tenant_user`.
  - **Assumido**: não conta. A métrica é "quem está usando o sistema", e quem foi excluído não
    está.
  - **Se negado**: some o filtro de exclusão e CT-09 inverte a asserção sobre Elena.

- **RQ-04 — há teto de organizações exibidas no widget?**
  - **Assumido**: as 10 maiores.
  - **Se negado**: a 11ª organização hoje fica invisível sem nenhum aviso na tela. É a premissa
    mais silenciosa das quatro.

## Fora de Escopo (declarado)

- Contagem de **page views** ou navegação dentro do painel. "Acesso" aqui é **sessão de
  autenticação** — o que o pacote de log registra. Métrica de navegação exigiria instrumentação
  de request, que o requisito não pede.
- Retroatividade do painel nos registros já existentes. Não há como inferir o painel de um login
  passado.
- Widgets no painel `/app` ou `/infra`. O requisito diz "dentro do admin".
- Alteração da tela de **edição** (`EditTenant`) e de **criação** (`CreateTenant`). Widget em tela
  de escrita compete com o formulário pela atenção; o requisito fala em "cadastro" no sentido de
  cadastro-de-organizações, e o usuário confirmou listagem + visualização.
