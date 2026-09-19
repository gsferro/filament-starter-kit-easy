# Requisito — Adoção do `mortalkiller/filament-page-header` nas telas de registro

## Fonte

- **Origem**: mensagem no chat, invocando a skill `feature-wiki` (texto escrito pelo solicitante)
- **Data**: 2026-09-19
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> quero que voce instale o pacote "https://filamentphp.com/plugins/pedro-monteiro-page-header"
> - adicione ele nas telas de User e Tenant na parte de View/Edit
> - deixe documentando o uso dele em outros cenarios onde temos uma tela de Resouce com Relations, ontem exibimos um resumo do registro e abas para os relacionamentos
> - use sub-agentes e uma branch especifica
> - veja na doc de pacotes candidatos que cita esse pacote que corrija dizendo que ele sera usado e onde no codigo
> - faça os commits correspondentes e agrupados
> - use o blueprint e /code-review
> - ao finalizar a implementação com os testes passando, abra o PR
> - atenção ao kit:update para levar essa melhoria

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `mortalkiller/filament-page-header` é instalado como dependência do kit | "quero que voce instale o pacote" | funcional |
| RQ-02 | O header rico é aplicado nas telas de **View** e **Edit** de `User` | "adicione ele nas telas de User e Tenant na parte de View/Edit" | funcional |
| RQ-03 | O header rico é aplicado nas telas de **View** e **Edit** de `Tenant` | idem | funcional |
| RQ-04 | Fica documentado o uso do pacote em **Resource com Relations** — resumo do registro no topo e abas para os relacionamentos | "deixe documentando o uso dele em outros cenarios onde temos uma tela de Resouce com Relations, ontem exibimos um resumo do registro e abas para os relacionamentos" | funcional |
| RQ-05 | A implementação usa sub-agentes | "use sub-agentes" | restrição |
| RQ-06 | A implementação vive numa branch própria | "e uma branch especifica" | restrição |
| RQ-07 | `wikis/pacotes-candidatos.md` é corrigido: o pacote deixa de constar como adiado e passa a constar como **adotado**, dizendo **onde** no código | "veja na doc de pacotes candidatos que cita esse pacote que corrija dizendo que ele sera usado e onde no codigo" | funcional |
| RQ-08 | Os commits são agrupados por assunto | "faça os commits correspondentes e agrupados" | restrição |
| RQ-09 | O Blueprint é usado na entrega | "use o blueprint" | restrição |
| RQ-10 | O diff passa por `/code-review` | "e /code-review" | restrição |
| RQ-11 | O PR abre ao fim da implementação, **com os testes passando** | "ao finalizar a implementação com os testes passando, abra o PR" | restrição |
| RQ-12 | O `kit:update` leva esta melhoria aos projetos que nasceram do kit | "atenção ao kit:update para levar essa melhoria" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-02** — **`User` não tem tela de View.** Os dois `UserResource` (`app/Filament/Admin/Resources/Users/UserResource.php:318-325` e `app/Filament/App/Resources/Users/UserResource.php:397-404`) declaram apenas `index`, `create` e `edit`. O `Tenant` tem `view` (`TenantResource.php:143`), o `User` não. A cláusula pede "View/Edit" para os dois.
  - **Resolvido pelo solicitante em 2026-09-19**: **criar o `ViewUser`**, com rota `view`, infolist, permissão e testes. Vira o passo mais caro da entrega, e é feature nova dentro da feature.

- **RQ-02** — existem **dois** `UserResource`, um por painel.
  - **Resolvido pelo solicitante em 2026-09-19**: **os dois** — `Admin` e `App`.

- **RQ-04** — "documentando" não diz onde: doc de usuário (`docs/pt` e `docs/en`), ADR, ou comentário no código.
  - **Assumido**: os três níveis, cada um com o papel dele — a **receita** em `wikis/receitas.md` (que é onde o kit guarda "como fazer X"), a **decisão** em ADR, e o **exemplo executável** no próprio `ViewTenant`, que já tem `UsersRelationManager` e por isso é o caso real, não hipotético.
  - **Se negado**: a receita sai e fica só a ADR.

- **RQ-12** — "atenção ao `kit:update`" não diz o que precisa acontecer. O comando **não** sobrescreve `composer.json` (`app/Console/Commands/KitUpdate.php:299-306`) — ele avisa. Logo, a dependência nova não chega sozinha ao projeto de quem já instalou o kit.
  - **Assumido**: a atenção pedida é (a) garantir que o `kit:update` **avise** sobre a dependência nova, e (b) documentar o passo manual (`composer require` + `filament:assets`) para quem atualiza. Criar propagação automática de dependência é o que a ADR-08 da rodada anterior já recusou, com motivo.
  - **Se negado**: entra um passo novo no `kit:update`, e isso é feature própria.

## Fora de Escopo (declarado)

- **Aplicar o header em outras telas** além de `User` (View/Edit, dois painéis) e `Tenant` (View/Edit). Os demais Resources do kit ganham a **receita**, não a implementação.
- **Propagação automática da dependência** para projetos que já nasceram do kit — ver RQ-12.
- **Substituir os widgets de cabeçalho** que o `ViewTenant` já usa (`getHeaderWidgets()`): o header do pacote e os widgets ocupam regiões diferentes da página e convivem.

## Adendo 1 — 2026-09-19

- **Fonte**: pedido do solicitante no chat, logo após a decisão de criar o `ViewUser`
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL. -->

> - adicione tambem a permission para o view user

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-13 | A tela `ViewUser` nasce com permissão própria, gerada pelo Shield e distribuída na matriz de papéis | "adicione tambem a permission para o view user" | autorização | — |

### Por que esta cláusula não era redundante

`config/filament-shield.php:183` já lista `view` entre os métodos de policy, então `shield:generate`
**já produz** `View:User`. O que não existe é a **tela** — e permissão sem tela é checkbox que não
governa nada, que é o defeito que `.ai/rules/filament.md` chama de *"checkbox que mente"*.

A cláusula cobra o inverso do caso conhecido: aqui a tela nasce depois da permissão, e o risco é a
tela nascer **sem** consultar a permissão que já existe. Ela exige as duas pontas — a Page
consultando, e a matriz de `PapeisSeeder` entregando a quem deve ter.

## Adendo 2 — 2026-09-19

- **Fonte**: decisão do solicitante em resposta a pergunta direta, com as três saídas e o custo de cada uma na mesa
- **Fidelidade**: alta (escolha registrada)

### A pergunta

O pacote exige `filament/filament: ^4.12.6 || ^5.8.1`. O kit declarava `^5.6`, e o
`CHANGELOG.md:### Alterado — Filament atualizado de v5.7.6 para v5.8.2:38` registra que aquele
`^5.6` era **deliberado**: *"A constraint do `composer.json` continua `^5.6` e **não sobe**: travar
em `^5.8` deixaria de fora quem ainda está na 5.7"*.

Adotar o pacote força o piso `5.8.1` pelo resolvedor de qualquer jeito. A declaração `^5.6` deixaria
de descrever o que o kit aceita — quem estivesse na 5.7 receberia um conflito de resolução em vez
da mensagem do kit.

### Decisão

> Subir para `^5.8.1`.

### Decomposição

| ID | Cláusula | Tipo | Substitui |
|----|----------|------|-----------|
| RQ-14 | `composer.json` declara `filament/filament` em `^5.8.1` — piso honesto, ainda em caret na série 5 | restrição | a decisão registrada no `CHANGELOG.md:38` da v0.35.0 |

### O que RQ-14 **não** afrouxa

A cláusula equivalente da wiki anterior — RQ-14 de `estudo-de-pacotes-rodada-2`, *"não travar a
versão do Filament"* — continua valendo e **não** conflita:
`^5.8.1` é caret, não pino. O oráculo dela — `tests/Kit/PacotesRodada2Test.php:[CT-32]:70` — segue
verde, medido: aceita a instalada (5.8.2), aceita `5.99.99`, recusa `6.0.0`.

O que muda é só o **piso**, e o piso subiu porque a realidade subiu.
