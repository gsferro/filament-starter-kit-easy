# Requisito — Vínculo de provedor social: o que acontece quando o e-mail já tem conta

## Fonte

- **Origem**: conversa no chat durante a validação real dos provedores de login social
  (`wikis/specs/feat/login-social-google/login-social-google/07-validacao-real-dos-provedores.md`)
- **Data**: 2026-08-26
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante; a proposta em três camadas foi
  apresentada pelo agente e **aprovada por escrito** ("concordo, implemente os 3")

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamo fazer esse mesmo teste do login social quando o tenancy estiver ativo. e isso é uma preocupação, pois como saberiamos qual tenancy ele tem permissão de entrar?

> precisamos prever quando a autenticação social tentar logar com um email já cadastrado, como vincular ou simplesmente logar ou retornar uma tela dizendo que o login já existe em uma conta local. acredito que simplesmente logar funcione, porem, isso pode ser uma falha, pois eu poderia criar uma conta do o email de outra pessoa no google e tentar subverter a autenticaçao assim. reflita e proponha uma solução

A proposta apresentada em resposta (agente, mesma conversa), aprovada verbatim:

> 1. **Manter o login direto por e-mail verificado** e registrar isso como ADR. Custo zero, é o que está.
> 2. **Vínculo explícito**: tabela `vinculos_sociais` (user, provedor, `sub` do provedor). Primeira vez que um provedor aparece pra uma conta existente → e-mail "sua conta foi acessada pelo Google pela primeira vez; não foi você?". Detecção, barata.
> 3. **Endurecimento opcional** (`KIT_SOCIALITE_VINCULO=confirmar`): a primeira entrada social numa conta local exige confirmação — a senha local uma vez, ou um link no e-mail. Depois disso o login casa por (`provedor`, `sub`), não por e-mail — imune à reciclagem de endereço.

> - concordo, implemente os 3 enquanto eu configuro o linkedin para testar o login social por ele.
> - lembre-se de documentar muito bem tudo isso no @README.md e colocar os prints das telas

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Entrada social com e-mail verificado que já tem conta local continua **autenticando** essa conta, sem tela intermediária, no modo padrão | "acredito que simplesmente logar funcione" / "1. Manter o login direto por e-mail verificado" | funcional |
| RQ-02 | A decisão de RQ-01 e o seu argumento de segurança ficam **registrados** (ADR + README) | "registrar isso como ADR" / "documentar muito bem tudo isso no @README.md" | restrição |
| RQ-03 | Existe um **vínculo explícito** entre a conta e a identidade no provedor (`provedor`, `sub`), gravado na primeira entrada | "tabela `vinculos_sociais` (user, provedor, `sub` do provedor)" | funcional |
| RQ-04 | Na **primeira** entrada de um provedor numa conta que já existe, a pessoa é **avisada por e-mail** | "Primeira vez que um provedor aparece pra uma conta existente → e-mail 'sua conta foi acessada pelo Google pela primeira vez; não foi você?'" | funcional |
| RQ-05 | Nas entradas seguintes a conta é reconhecida **pelo vínculo** (`provedor`, `sub`), não pelo e-mail | "Depois disso o login casa por (`provedor`, `sub`), não por e-mail — imune à reciclagem de endereço" | funcional |
| RQ-06 | Existe um modo **opcional** em que a primeira entrada social numa conta existente **não entra**: exige confirmação por link no e-mail antes | "3. Endurecimento opcional (…): a primeira entrada social numa conta local exige confirmação — (…) um link no e-mail" | funcional |
| RQ-07 | O modo de RQ-06 é **configurável** (`.env`, e a tela de Settings, como os outros interruptores de login) | "`KIT_SOCIALITE_VINCULO=confirmar`" | não-funcional |
| RQ-08 | O README documenta o comportamento com **capturas de tela** | "colocar os prints das telas" | restrição |
| RQ-09 | Com tenancy ligada, conta **existente** entra normalmente (as organizações são as já vinculadas); conta **nova** sem organização é recusada | "como saberiamos qual tenancy ele tem permissão de entrar?" | autorização |

## Ambiguidades e Perguntas Abertas

- **RQ-06** — "a senha local uma vez, **ou** um link no e-mail". Duas formas; a proposta deixou
  em aberto.
  - **Assumido**: **link no e-mail**, só. É a única que funciona para a conta que não tem senha
    local (a criada por outro provedor), e é a mesma prova que o "Esqueceu a senha?" já aceita.
  - **Se negado**: acrescenta-se um segundo caminho (formulário de senha na volta do OAuth);
    RQ-06 ganha um caso a mais, o resto não muda.
- **RQ-07** — o nome da chave. A proposta escreveu `KIT_SOCIALITE_VINCULO=confirmar` (enum).
  - **Assumido**: booleano `KIT_SOCIALITE_VINCULO_CONFIRMAR=false`, porque os outros interruptores
    de login do kit são booleanos com `FILTER_VALIDATE_BOOLEAN` (falham fechados) e porque a tela
    de Settings os representa como `Toggle`. Só há dois modos; enum de dois valores é booleano.
  - **Se negado**: renomear a chave e o campo; nenhum fluxo muda.
- **RQ-04** — "não foi você?": o que a pessoa faz se não foi. O kit não tem "reportar".
  - **Assumido**: o e-mail orienta a **trocar a senha** (ou definir uma, pelo bloco do perfil) e
    avisar quem administra. Nada automático.

## Fora de Escopo (declarado)

- Cadastro social **por organização** com tenancy ligada (o botão carregar `?org=` no `state` do
  OAuth). É a segunda metade da preocupação da RQ-09 e é feature própria.
- Tela de gerenciamento dos vínculos (listar/remover no perfil). O vínculo nasce e vive; remover
  é `delete` na tabela por quem administra. Cabe numa evolução.
- Confirmação por **senha local** (a alternativa descartada na ambiguidade de RQ-06).
- Facebook/Discord — continuam fora do enum (ADR-04/05 de `mais-provedores-sociais`).
