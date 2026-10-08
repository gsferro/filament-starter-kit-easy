# Requisito — ocultar-seletor-de-organizacao-unica

## Fonte

- **Origem**: conversa com o solicitante no chat (pedido colado como texto)
- **Data**: 2026-10-08
- **Autor / solicitante**: usuário (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> quando um usuário tiver acesso somente a um unico tenant, ter a opção de ocultar o combo de seleção no setttings da aplicação
>
> - quando o usuário tiver acesso a mais de um tenant, exibe o combo de seleção
> - isso não influencia se ele tiver acesso a multiplos paineis, somente a acesso a tenants diferentes
> - como temos agora a configurara a exibição no header da logo marca, nome e o nome do painel, quando chega no cenario onde so tem um tenant e ativa a configuração fica uma informação redundante, conforme print 1:
> - com esse novo settings é necessário ter as thumbs e gifs demonstrando quando altera o settings, como exibe na tela

*(O "print 1" é uma captura de tela anexada na conversa mostrando o painel /app com o seletor de organização no topo da barra lateral, redundante ao cabeçalho configurável.)*

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Existe uma opção nas configurações da aplicação que permite ocultar o combo de seleção de tenant quando o usuário tem acesso a somente um tenant | "ter a opção de ocultar o combo de seleção no setttings da aplicação" | funcional | fechada |
| RQ-02 | Com a opção ativa e mais de um tenant acessível, o combo de seleção continua exibido | "quando o usuário tiver acesso a mais de um tenant, exibe o combo de seleção" | funcional | fechada |
| RQ-03 | O critério de contagem é o acesso a **tenants**; o acesso a múltiplos painéis não influencia a exibição | "isso não influencia se ele tiver acesso a multiplos paineis, somente a acesso a tenants diferentes" | restrição | fechada |
| RQ-04 | Com a opção ativa e exatamente um tenant acessível, o combo de seleção não aparece — motivo: a informação é redundante quando o header já exibe marca, nome e nome do painel | "quando chega no cenario onde so tem um tenant e ativa a configuração fica uma informação redundante" | funcional | fechada |
| RQ-05 | A entrega inclui thumbs e gifs que demonstram o efeito da alteração do settings na tela | "é necessário ter as thumbs e gifs demonstrando quando altera o settings, como exibe na tela" | não-funcional | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Respondidas na mesma sessão (confirmação "continue"
     no chat, 2026-10-08, sobre as recomendações apresentadas pela sessão). -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "Acesso a um tenant" conta o quê — o vínculo cru na tabela pivot, ou a lista que o próprio seletor usa (só organizações ativas, e para `master_global` todas as ativas)? | RQ-01, RQ-02, RQ-04 | A mesma fonte do seletor: organizações acessíveis e **ativas** — contar a pivot divergiria do que o usuário poderia trocar | respondida — confirmação no chat, 2026-10-08 |
| Q2 | A opção nasce ligada ou desligada? | RQ-01 | **Desligada** (default): quem atualiza o kit não perde o seletor de surpresa — a opção é opt-in | respondida — confirmação no chat, 2026-10-08 |
| Q3 | O `master_global`, que enxerga todas as organizações ativas sem vínculo, entra na mesma regra de contagem? | RQ-01, RQ-02 | Sim — para ele "acesso" é a lista de ativas; com uma só ativa ele também vê o seletor oculto | respondida — confirmação no chat, 2026-10-08 |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | "Ocultar o combo" significa esconder o bloco inteiro do menu de tenant (avatar + rótulo + nome + dropdown), e não só a lista de troca — é o bloco que carrega a informação redundante do print 1 | decisão de desenho confirmada com o solicitante | 2026-10-08 | RQ-01, RQ-04 | vigente |
| P-02 | A opção é da **instalação** (tela de configurações do kit), não por usuário nem por organização | o texto diz "settings da aplicação" | 2026-10-08 | RQ-01 | vigente |
| P-03 | Ocultar é decisão de UI: `canAccessTenant()`, a URL `/app/{tenant}` e o acesso direto por link continuam valendo — quem tem um tenant continua dentro dele | consequência direta de "informação redundante" (RQ-04) | 2026-10-08 | RQ-01, RQ-04 | vigente |
| P-04 | Com a multi-organização desligada (`KIT_TENANCY=false`), o seletor não existe e o toggle não aparece na tela de configurações — um interruptor inerte na tela é pior que ausente (mesmo critério já aplicado a `login_anti_robo_local`) | decisão de desenho; precedente na própria tela de settings | 2026-10-08 | RQ-01 | vigente |

## Fora de Escopo (declarado)

- Mudar quem pode acessar qual tenant (a permissão é de `canAccessTenant()` e não muda).
- Ocultar ou alterar o menu do usuário, a marca ou o nome do painel no cabeçalho (já têm settings próprios).
- Qualquer efeito nos painéis `/admin` e `/infra`, que não têm seletor de tenant.
- Impedir acesso direto por URL a um tenant válido quando o seletor está oculto.
