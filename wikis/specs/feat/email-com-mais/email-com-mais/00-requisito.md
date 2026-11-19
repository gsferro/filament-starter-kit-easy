# Requisito — Permitir ou bloquear o "+" no e-mail do cadastro

## Fonte

- **Origem**: pedido do mantenedor no chat em 2026-11-16, registrado para depois das quatro pendências (publicadas até a v0.55.1)
- **Data**: 2026-11-18
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> a possibilidade de permitir ou bloquear que ao se cadastrar, usar no email o "+" dentro do radical do email. exemplo: "gsferroti@gmail.com", "gsferroti+starter@gmail.com", "gsferroti+starter-kit@gmail.com". Todos esses emails são validos e entram na caixa de mensagem do primeiro email. logo, é possivel ter infinitos emails desse jeito. isso não é um problema, mas acho que é uma proteção extra que pode ser habilitada ou não no sistema. se for habilitada, a pessoa se cadastra, por convite, register ou crud direto, se não for, adiciona essa Rule no validation e bloqueia o uso

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Existe uma opção do sistema que permite ou bloqueia o "+" na parte local (antes do "@") do e-mail de quem se cadastra | "a possibilidade de permitir ou bloquear que ao se cadastrar, usar no email o "+" dentro do radical do email" · "uma proteção extra que pode ser habilitada ou não no sistema" | funcional | fechada |
| RQ-02 | Com o "+" permitido, a pessoa se cadastra com ele por convite, pelo registro e pelo CRUD direto | "se for habilitada, a pessoa se cadastra, por convite, register ou crud direto" | funcional | fechada |
| RQ-03 | Com o "+" bloqueado, uma regra de validação recusa o e-mail com "+" nas três vias | "se não for, adiciona essa Rule no validation e bloqueia o uso" | funcional | fechada |
| RQ-04 | O e-mail sem "+" (o "radical") continua aceito nos dois estados | "gsferroti@gmail.com" · "Todos esses emails são validos" | funcional | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | A instalação nova nasce com o "+" permitido (como hoje) ou bloqueado? | RQ-01, P-01 | permitido: nada muda sem decisão do admin, e o texto trata o "+" como válido ("isso não é um problema") | aberta — implementada a recomendação (P-01) |
| Q2 | A troca de e-mail no perfil também segue a opção? Sem ela, quem se cadastrou como `fulano@` troca para `fulano+1@` e libera o `fulano@` para um segundo cadastro | RQ-03, P-03 | sim: é a mesma porta por outro caminho | aberta — implementada a recomendação (P-03) |
| Q3 | Cada organização pode ter a própria opção, como o "cadastro aberto"? | RQ-01, P-02 | não: uma opção da instalação; a organização não cria contas fora das vias da instalação | aberta — implementada a recomendação (P-02) |
| Q4 | Um convite com "+" criado antes de a opção ser desligada ainda pode ser aceito? | RQ-03, P-06 | sim: o admin escolheu o endereço quando a opção permitia; recusar no aceite deixaria a pessoa diante de um campo desabilitado que ela não pode corrigir, e o convite pendente seguraria a exclusão do papel | aberta — implementada a recomendação (P-06) |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | A opção nasce **permitindo** o "+": a instalação existente e a nova se comportam como hoje até o admin desligá-la (Q1). Toda ausência de valor também permite — a chave ausente no `.env`, vazia ou irreconhecível, o projeto que manteve o próprio `config/kit.php` sem a chave, e o settings ainda não migrado; só um "desligado" explícito bloqueia | step 4 — o texto trata o "+" como válido e a proteção como "extra", que "pode ser habilitada ou não" | 2026-11-18 | RQ-01 | vigente |
| P-02 | A opção é da instalação, na tela de configurações (aba Registro) e no `.env`, com um leitor único; sem opção por organização (Q3) | step 4 — `.ai/rules/config.md` ("a chave tem uma dona só"); hoje só o cadastro aberto tem opção por organização | 2026-11-18 | RQ-01 | vigente |
| P-03 | "Cadastrar" cobre toda via que dá a uma conta um e-mail novo: o registro aberto (`/app/register` e `/cadastro`), o convite (criação, um a um e em massa, e aceite), o CRUD de usuários (`/admin` e `/app`, criar e editar), o login social que cria conta e a troca de e-mail no perfil (Q2). Os comandos `kit:install` e `kit:admin` ficam fora: são do operador da instalação, não de quem se cadastra | step 3 — o mapa das vias; o texto cita "convite, register ou crud direto" | 2026-11-18 | RQ-02, RQ-03 | vigente |
| P-04 | O "+" que conta é o da parte local, antes do "@". Conta já existente com "+" continua entrando e pode salvar o perfil sem trocar o e-mail; a regra só olha um e-mail que está sendo dado a uma conta. Por isso o convite para um e-mail que já é de uma conta existente passa: ele não cria conta, só vincula (o convite para usuário existente) | step 4 — a proteção é contra novos cadastros, não contra quem já existe | 2026-11-18 | RQ-03, RQ-04 | vigente |
| P-05 | O bloqueio aparece como erro de validação no campo de e-mail, com uma mensagem que diz o motivo; no convite em massa, o endereço com "+" sai com um motivo próprio e os outros seguem; no login social, a conta não é criada e a pessoa vê o motivo | step 4 — o padrão de cada via (erro de campo, motivo por endereço, recusa do login social) | 2026-11-18 | RQ-03 | vigente |
| P-06 | O convite já emitido vale: o convite com "+" criado antes de a opção ser desligada é aceito, pela tela e pelo login social (Q4). Com a opção desligada, nenhum convite novo com "+" nasce para conta inexistente | step 4 e advisor — recusar no aceite deixa a pessoa num campo desabilitado e o convite pendente, que bloqueia a exclusão do papel | 2026-11-18 | RQ-03 | vigente |

## Fora de Escopo (declarado)

- Outras formas de multiplicar uma caixa de e-mail (pontos no Gmail, domínios descartáveis)
- Os comandos `kit:install` e `kit:admin` (P-03)
- Contas que já têm "+" no e-mail (P-04)
- Convites já emitidos (P-06)
