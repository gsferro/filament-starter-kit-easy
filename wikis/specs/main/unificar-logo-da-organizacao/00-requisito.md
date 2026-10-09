# Requisito — unificar-logo-da-organizacao

## Fonte

- **Origem**: conversa com o solicitante no chat (pedido colado como texto)
- **Data**: 2026-10-09
- **Autor / solicitante**: usuário (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - me faça uma verificação se no tenant, existe a opção de ter logo dark e light.
> - se sim, aplique a mesma logica usada no settings da aplicação de ter a opção de unificar a logo da marca, para não obrigar a empresa a colocar 2 logos se ele não quiser
> - crie uma [feature-wiki] se julgar necessário. siga o fluxo padrão de implementação e testes e quando tudo estiver passando, lance a tag + release com a evolução

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Verificar se a organização (tenant) já suporta logo clara e escura — **verificado: sim** (`tenants.logo`, `tenants.logo_dark`, `TenantForm`) | "me faça uma verificação se no tenant, existe a opção de ter logo dark e light" | não-funcional | fechada |
| RQ-02 | A organização ganha a opção de usar uma logo só nos dois temas, espelho do `unifica_logo_marca` das configurações da aplicação | "aplique a mesma logica usada no settings da aplicação de ter a opção de unificar a logo da marca" | funcional | fechada |
| RQ-03 | Com a opção ligada, a organização que envia só a logo clara não é obrigada a enviar a escura — a dela serve os dois temas (não cai para a logo_dark da instalação) | "para não obrigar a empresa a colocar 2 logos se ele não quiser" | funcional | fechada |
| RQ-04 | A entrega segue o fluxo padrão (wiki, testes) e termina em tag + release | "siga o fluxo padrão de implementação e testes e quando tudo estiver passando, lance a tag + release com a evolução" | não-funcional | fechada |

## Perguntas ao Solicitante

<!-- Respondidas na mesma sessão: o "prossiga" do chat, 2026-10-09, confirmou as quatro
     recomendações apresentadas pela sessão (pergunta feita antes de abrir a wiki). -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | O toggle mora onde — por organização (form do tenant) ou global? | RQ-02 | Por organização: quem decide "uma logo só" é a empresa, e o `logo_dark` já é campo dela | respondida — confirmação no chat, 2026-10-09 |
| Q2 | Default do toggle? | RQ-02, RQ-03 | `true` (unificada) para novas organizações; linhas existentes **com** `logo_dark` gravada nascem `false` — quem já separou não perde a separação por update | respondida — confirmação no chat, 2026-10-09 |
| Q3 | Com unificada ligada, o tema escuro mostra a logo CLARA da organização, e não a da instalação — como no settings (`escura = null`)? | RQ-03 | Sim — a mesma semântica | respondida — confirmação no chat, 2026-10-09 |
| Q4 | Unificada desligada e `logo_dark` em branco: o dark mostra a `logo_dark` da instalação (atual) ou a clara da organização? | RQ-03 | Manter o atual (a da instalação) — compat com o comportamento já publicado | respondida — confirmação no chat, 2026-10-09 |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | A opção é uma **coluna da organização** (`tenants.unifica_logo`, bool), decidida por organização — não settings global da instalação | o texto fala "a empresa" decidir por si; Q1 | 2026-10-09 | RQ-02 | vigente |
| P-02 | O toggle só aparece no formulário quando a instalação está com a marca **separada** (`unifica_logo_marca` desligado): com a marca unificada, a `logo_dark` da organização já é inerte e o toggle prometeria um controle que não existe | mesmo critério do campo `logo_dark` no `TenantForm` | 2026-10-09 | RQ-02 | vigente |
| P-03 | A regra vale nas duas superfícies que consomem o par do tenant — topo do `/app` e tela de bloqueio — porque ambas leem `IdentidadeDoKit::logosPara()`; mudar ali cobre as duas | análise do código | 2026-10-09 | RQ-02, RQ-03 | vigente |
| P-04 | Linhas existentes **sem** `logo_dark` nascem com `unifica_logo = true` — a mudança de tela (dark passa a mostrar a clara dela, não a da instalação) é exatamente o comportamento pedido; quem nunca enviou a escura demonstra não querer duas | Q2, semântica do pedido | 2026-10-09 | RQ-02, RQ-03 | vigente |
| P-05 | Sem multi-tenancy ligada, ou sem organização aberta, nada muda — o par continua o da instalação | consequência direta do consumo por `logosPara($organizacao)` | 2026-10-09 | RQ-02 | vigente |

## Fora de Escopo (declarado)

- Mudar a regra da marca **da instalação** (`unifica_logo_marca`, `kit.identidade.*`) — ela já tem o toggle dela.
- Validar obrigatoriedade das logos do tenant (hoje os dois campos são inertes em branco e continuam).
- Efeito nos painéis `/admin` e `/infra`, que nunca mostram logo de organização.
- Preview dos temas no formulário, crop, ou qualquer editor de imagem.
