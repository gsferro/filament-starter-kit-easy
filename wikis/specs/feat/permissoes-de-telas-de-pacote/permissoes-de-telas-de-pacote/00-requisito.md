# Requisito — W6: permissões das telas de pacote no painel /infra

## Fonte

- **Origem**: `.claude/requisitos/w6-permissoes-de-telas-de-pacote.txt` (raiz do repositório)
- **Data**: 2026-08-24
- **Autor / solicitante**: dono do kit
- **Fidelidade**: alta (texto escrito)
- **Dívida de origem**: declarada na v0.18.10, achado QA-01 da wiki
  `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/`, recusada por
  ADR-05 daquela wiki.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> [Contexto: divida declarada na v0.18.10, achado QA-01 da wiki permissoes-de-telas-e-acoes]
>
> TODAS as telas, links e actions do sistema precisa ter sua permissão especifica desde o inicio no starter-kit como padrão, para o maximo controle e total flexibilidade a quem for usar o kit nos seus projetos
>
> Ficaram de fora 10 Pages e 1 Widget que vem de PACOTES de terceiros no painel /infra:
> HealthCheckResults, BackupRunsPage, LogsExplorer, DependencyGraphPage, Commands, History,
> RunView, RecycleBin, MyProfilePage, ComposerReleaseOverviewWidget.
>
> Nelas a permissao existe no banco e no checkbox da tela de papeis, e nao decide nada.
> Repro: revogar `View:LogsExplorer` do papel `infra` e abrir /infra/logs responde 200.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A permissão `View:{Classe}` de cada tela de pacote listada passa a **decidir** o acesso, e não apenas a existir | "Nelas a permissao existe no banco e no checkbox da tela de papeis, e nao decide nada" | autorização |
| RQ-02 | Quem **tem** a permissão continua abrindo a tela | "para o maximo controle e total flexibilidade a quem for usar o kit" | autorização |
| RQ-03 | Quem **não tem** a permissão recebe 403 na rota — a repro citada tem de inverter de 200 para 403 | "Repro: revogar `View:LogsExplorer` do papel `infra` e abrir /infra/logs responde 200" | autorização |
| RQ-04 | A permissão continua **existindo no banco e no checkbox** da tela de papéis (a alavanca não é removida para o checkbox parar de mentir) | "a permissao existe no banco e no checkbox da tela de papeis" | restrição |
| RQ-05 | Tela cujo pacote não oferecer ponto de extensão viável fica **declarada** com o motivo e `file:line`, mantendo o caso de teste que assere a lacuna | (do enunciado da tarefa: "Se a subclasse de plugin se mostrar inviável para alguma delas, **declare** com o motivo e `file:line`") | restrição |
| RQ-06 | Nenhuma permissão nasce órfã: toda permissão consultada tem de estar na matriz de `PapeisSeeder`, e o papel `infra` não pode ficar trancado fora do próprio painel | (do enunciado: "Permissão que não entra na matriz do `database/seeders/PapeisSeeder.php` nasce órfã") | restrição |
| RQ-07 | O `README.md` e o `README.en.md` deixam de prometer só "toda tela DO KIT" e passam a refletir o presente | (do enunciado: "Se você fechar, **essa frase precisa mudar**") | não-funcional |
| RQ-08 | O CT-24 de `tests/Kit/PermissoesDeTelasTest.php`, escrito para ficar vermelho no dia em que a lacuna fechar, é **atualizado** para asserir a cobertura — nunca apagado | (do enunciado: "atualize-o para asserir a cobertura... Não o delete") | restrição |

## Ambiguidades e Perguntas Abertas

- **Contagem do texto original** — "10 Pages e 1 Widget" não fecha com a lista, que enumera
  **9 Pages** (`HealthCheckResults`, `BackupRunsPage`, `LogsExplorer`, `DependencyGraphPage`,
  `Commands`, `History`, `RunView`, `RecycleBin`, `MyProfilePage`) e **1 Widget**
  (`ComposerReleaseOverviewWidget`) — **10 classes**, não 11.
  - **Assumido**: a **lista** é normativa; o número na prosa é um lapso herdado de ADR-05 daquela
    wiki, que dizia "9 Pages de vendor ... mais a `MyProfilePage`" e nomeava 8 no parênteses.
    Conferido no banco: existem exatamente 10 permissões, uma por classe listada (`View:Commands`,
    `View:History`, `View:RunView`, …) — nenhuma décima primeira classe de pacote sem cobertura
    aparece em `Filament::getPanel('infra')->getPages()`.
  - **Se negado**: uma 11ª classe seria nomeada e entraria no escopo com o mesmo tratamento;
    nenhum passo do plano muda de forma.

- **RQ-01 e `MyProfilePage`** — a tela é "meu perfil" (senha, 2FA, avatar) e está registrada nos
  **três** painéis, com uma única permissão `View:MyProfilePage`. Gatear a própria tela de perfil
  é o que o requisito pede ("TODAS as telas"), mas trancar alguém fora dela impede a troca da
  própria senha.
  - **Assumido**: entra no escopo. Conferido no banco: `View:MyProfilePage` já está nos **quatro**
    papéis (`admin`, `infra`, `admin_app`, `panel_user`), então o default do kit não muda nada —
    só passa a existir a alavanca.
  - **Se negado**: `MyProfilePage` sai do escopo e vira a quarta linha declarada em RQ-05.

## Fora desta entrega (declarado)

- **Actions dentro** das telas de pacote (o botão "limpar log", o "restaurar" da lixeira, o
  "executar" da central de comandos). O requisito fala de "telas, links e actions", mas a lista
  que ele enumera é de Pages e de um Widget; Action de pacote é outro mecanismo
  (`CanBeAuthorized`) e outra superfície de medição.
- **Resources** de pacote no `/infra` (`ExceptionResource`, `CommandRecordResource`,
  `MailLogResource`, …). Resource já é autorizado por policy pelo Filament, e o Shield gera as
  policies — não há lacuna aqui.
- **Remover permissão do banco** para o checkbox parar de mentir: proibido por RQ-04.
