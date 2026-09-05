# Requisito — Badge de contagem em todo Resource do kit

## Fonte

- **Origem**: pedido do usuário no chat, via invocação da skill `feature-wiki`
- **Data**: 2026-09-04
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante, colado verbatim abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> Adicione os count no navigate badge do painel admin em "Convites" e "Papeis".
> - Criar regra que por padrão, TODOS os itens no menu, que renderizem um Resource, devem ter um navigateBadge, usando o pacote do OdometerStats implementado

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O item "Convites" do menu do painel `admin` exibe a contagem no badge | "Adicione os count no navigate badge do painel admin em \"Convites\"" | funcional |
| RQ-02 | O item "Papéis" do menu do painel `admin` exibe a contagem no badge | "e \"Papeis\"" | funcional |
| RQ-03 | Todo item de menu que renderiza um Resource tem badge de navegação, por padrão | "TODOS os itens no menu, que renderizem um Resource, devem ter um navigateBadge" | funcional |
| RQ-04 | O badge é renderizado pelo pacote de odômetro já instalado | "usando o pacote do OdometerStats implementado" | restrição |
| RQ-05 | O comportamento de RQ-03 é registrado como **regra**, não só implementado | "Criar regra que por padrão" | restrição (processo) |

## Ambiguidades e Perguntas Abertas

### Resolvidas com o usuário antes do plano

- **RQ-01 — "Convites" já tem o badge.** A premissa do requisito é falsa e isso é achado, não
  detalhe de redação: `App\Filament\Admin\Resources\Convites\ConviteResource` **já usa** o trait
  `App\Filament\Concerns\BadgeContagemNavegacao`. A tabela `convites` tem **0 registros** nesta
  instalação (medido), e o trait decide, textualmente, que *"Zero não vira badge: um `0` cinza em
  todo item só polui o menu."* A ausência que o solicitante viu é a regra do kit funcionando.
  - **Respondido pelo usuário**: **cinza no 0, colorido acima**. O zero passa a aparecer, em cor
    discreta; contagem maior que zero mantém a cor de destaque.
  - **Consequência aceita**: a mudança é no trait, então **afeta os 8 resources que já o usam**,
    nos três painéis — não só "Convites".

- **RQ-03 — "TODOS os itens no menu" inclui Resource de vendor?** Dos resources registrados, 9
  (contando repetição entre painéis) pertencem a pacotes de terceiros e não podem receber o trait
  sem editar `vendor/`.
  - **Respondido pelo usuário**: **só resources do app** (`app/Filament/**/Resources/**`).
  - **Consequência aceita**: `ExceptionResource`, `QueueMonitorResource`,
    `AuthenticationLogResource`, `AuditResource`, `MailLogResource`, `CommandRecordResource` e os
    dois de Onboarding **continuam sem badge**, e isso é escopo declarado, não omissão.

- **RQ-03 — a regra vale para qual painel?** RQ-01 e RQ-02 falam do `admin`; RQ-03 fala em "todos
  os itens no menu", sem dizer de qual menu.
  - **Respondido pelo usuário**: **os três painéis** (`admin`, `app`, `infra`).
  - **Consequência**: entra também
    `App\Filament\Infra\Resources\ComposerReleasePackages\ComposerReleasePackageResource`, que é o
    segundo — e último — resource do app hoje sem badge.

### Em aberto

- **Qual cor para contagem maior que zero?** O requisito diz "colorido" sem nomear cor.
  - **Assumido**: a cor **default do Filament**, que é o que os 8 resources com badge já exibem
    hoje. Ou seja, `getNavigationBadgeColor()` devolve `null` acima de zero, e só `'gray'` no zero.
  - **Se negado**: uma linha muda, e o badge de todo resource do kit muda de cor junto.

- **O badge conta registros excluídos por soft delete?** Nenhuma cláusula toca no assunto.
  - **Assumido**: **não**. A contagem sai de `getEloquentQuery()`, que já aplica os escopos do
    resource — é o que o trait faz hoje e o requisito não pede para mudar.
  - **Se negado**: a contagem passa a divergir da listagem que o usuário vê ao clicar, que é
    exatamente o que o docblock do trait existe para evitar.

- **Levantada na derivação dos casos de teste** — o texto do tooltip do badge não é determinado
  pelo requisito.
  - **Assumido**: `Total de registros`, que é o texto já vigente no trait.
  - **Se negado**: troca de string, sem efeito em nenhum caso de teste — nenhum `Então` afirma o
    tooltip, de propósito.

## Fora de Escopo (declarado)

- **Resource de pacote de terceiro** — decidido com o usuário, ver acima. Dá badge só quem está em
  `app/Filament/**/Resources/**`.
- **Page e Widget do menu.** RQ-03 diz "que renderizem um Resource". `HubDeAdministracao`,
  `HubDeInfraestrutura`, `ConfiguracoesDoKit`, `ConvitesRecebidos` e as telas de perfil são Pages
  e continuam sem badge.
- **Mudar o que a contagem significa.** Continua sendo "total de registros que a listagem daquele
  resource mostraria".
- **Badge em sub-navegação, em cluster ou em `NavigationItem` escrito à mão.**
