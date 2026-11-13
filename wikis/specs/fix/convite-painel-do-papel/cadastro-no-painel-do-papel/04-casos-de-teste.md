# Casos de Teste — cadastro por convite cai no painel do papel

> Oráculo: `00-requisito.md` desta feature. Vocabulário: `wikis/glossario.md`.
>
> Derivação independente, sem leitura da implementação. Os arquivos de teste e o seeder autorizados forneceram somente nomes e convenções.
>
> Documento proposto, entregue integralmente nesta resposta. Nenhum arquivo foi editado e nenhum teste foi executado.
>
> Revisão adversarial: duas rodadas independentes; oito achados da primeira rodada incorporados; nenhum novo achado demonstrado na segunda. Q1 permanece aberta somente no recorte indicado.

## Perfil de Derivação

| Área | Probabilidade | Impacto | P × I | Perfil |
|---|---:|---:|---:|---|
| Destino, autorização e sessão após cadastro | 3 | 3 | 9 | Completo |
| Nota de deploy | 1 | 1 | 1 | Mínimo |

O risco principal é concluir o cadastro com uma sessão autenticada cujo destino é inacessível, inclusive por falta de vínculo com a organização. O desempate entre vários painéis também pode encaminhar a conta para um painel proibido.

Técnicas: particionamento de equivalência, tabela de decisão, matriz de acesso por papel e painel, cardinalidade de painéis acessíveis em 0/1/2/3 e observação da sessão e do destino HTTP.

As contagens automáticas por arquivo e a correspondência com testes implementados ficam pendentes da materialização deste documento.

## Varredura SFDIPOT

| Dimensão | Recorte derivado do requisito | Cobertura |
|---|---|---|
| Structure | Resposta de cadastro, integração com o registro e documentação de deploy | CT-01–CT-14 |
| Function | Preservar destino permitido, substituir corrente inacessível e tratar zero painéis | CT-03, CT-04, CT-06, CT-08–CT-12 |
| Data | Papéis, organização, pretendida, default, ordem e quantidade de painéis acessíveis | CT-01–CT-12 |
| Interfaces | Cadastro pelo componente e resposta solicitada diretamente, com acesso HTTP posterior | G1 e G2 |
| Platform | Tenancy ligada/desligada; orientação documental sobre Apache | CT-01, CT-02, CT-05–CT-07, CT-11, CT-13 |
| Operations | Login unificado desligado; regressão delimitada do modo ligado | CT-01–CT-12, CT-14 |
| Time | Os convites usados estão válidos e não expirados; nenhuma nova regra temporal é determinada | Precondição dos cenários de cadastro |

## Mapa de Regras

Uma linha por cláusula ou premissa vigente.

| Regra | Origem | Comportamento verificável | Perfil / técnica | Cenários |
|---|---|---|---|---|
| R1 | RQ-01 | Convite de papel de outro painel conclui no painel permitido do papel | Completo / EP e matriz papel × tenancy | CT-01, CT-02, CT-03; CT-14 como regressão do limite de escopo |
| R2 | RQ-02 | Corrente acessível preserva pretendida acessível; sem ela, permanece na corrente | Completo / tabela de decisão | CT-04, CT-05 |
| R3 | RQ-03 | Corrente inacessível e um painel acessível levam à URL desse painel | Completo / cardinalidade e organização | CT-03, CT-06; invariante em CT-12 |
| R4 | RQ-04 | Vários acessíveis têm destino determinístico, sem tela de escolha | Completo / tabela de decisão | CT-08, CT-09 |
| R5 | RQ-05 | Zero painéis acessíveis levam ao login | Completo / fronteira de cardinalidade | CT-10 |
| R6 | RQ-06 | Pretendida e corrente inacessíveis terminam em painel acessível | Completo / EP da causa da inacessibilidade | CT-11; exemplos multipainel em CT-09 |
| R7 | RQ-07 | Convite `panel_user` com organização preserva `/app/{organizacao}` | Completo / EP de organização | CT-07 |
| R8 | RQ-08 | Documentação explica `/public/app` e orienta a configuração de deploy | Mínimo / verificação documental | CT-13 |
| R9 | P-01 | Zero painéis encerram a autenticação e entregam o login da corrente | Completo / efeito sobre sessão e saída alcançável | CT-10 |
| R10 | P-02 | Default acessível prevalece; senão, vale o primeiro acessível na ordem registrada | Completo / tabela de decisão e ordenação | CT-08, CT-09 |

**Cobertura parcial de RQ-03/RQ-04:** a precedência da pretendida acessível quando a corrente é inacessível depende de Q1. Os casos sem pretendida ou com pretendida inacessível continuam determinados e cobertos.

## Costuras de Teste

Costuras confirmadas pela sessão em 2026-11-13, sob a autorização de implementar do pedido. Derivação: Codex (`codex exec -s read-only`), contexto novo, só o `00` como oráculo.

| Grupo | Regras | Costura | Existente ou nova | Por que esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Cadastro por convite | R1, R2, R7 | componente Livewire/Filament | Existente: padrão de `tests/Kit/DestinoAposCadastroTest.php`, com `RegistroPorConvite` e `CadastroUnificado`; adaptação tenancy proposta | Prova que o cadastro real entrega a resposta correta para a conta criada | sessão, 2026-11-13 |
| G2 — Resposta e navegação | R1–R6, R9, R10 | Pest feature HTTP | Existente: chamada direta ao contrato `RegistrationResponse` no teste autorizado; adaptação tenancy proposta | Exercita a unidade da resposta fora da UI, com aplicação, autenticação e sessão reais; observa o destino por HTTP | sessão, 2026-11-13 |
| G3 — Nota de deploy | R8 | unit de regra | Nova proposta: verificação estática do documento de deploy entregue | O observável é o conteúdo documental; não depende de aplicação ou navegador | sessão, 2026-11-13 |

A unidade da resposta de G2 exige aplicação e sessão. Não deve ser tratada como unidade pura sem container.

Nos cenários de G1 e G2, a observação do redirect tem um par: solicitar o destino retornado na **mesma sessão**, sem autenticar novamente e sem desabilitar middleware. O acesso deve terminar em resposta de sucesso, sem 403 nem ciclo de redirecionamentos.

## Fronteira com o Plano

| Fonte ou item | Uso permitido | Limite |
|---|---|---|
| `00-requisito.md` | Comportamento esperado e premissas vigentes | Oráculo |
| `wikis/glossario.md` | Vocabulário e significado de organização e vínculo | Não acrescenta fluxo à feature |
| `tests/Pest.php` | Nomes `ofertaPara` e `ligarLoginUnificado` | Seus resultados não definem o esperado |
| `tests/Kit/DestinoAposCadastroTest.php` | Nomes dos componentes, contrato da resposta e padrão de costura | As expectativas existentes não são herdadas como oráculo |
| `database/seeders/PapeisSeeder.php` | Nomes dos papéis | A implementação do seeder não define o esperado |
| Plano, ADRs externos e implementação | Não consultados | Não foram usados para preencher lacunas |

As versões dos pacotes não foram verificadas, respeitando a restrição de fontes. Este documento não prescreve novas APIs de teste.

CT-14 cobre somente a regressão inequívoca descrita no Texto Original: no modo ligado, uma conta com um único painel acessível chega a esse painel. Não define comportamento multipainel para esse modo.

## Setup Global

- Em todos os cenários, exceto CT-14, o login unificado está efetivamente desligado. Usar o nome autorizado `ligarLoginUnificado(false)`, sem depender do default do ambiente.
- Os cenários de componente começam com e-mail novo e convite **Pendente**, válido e não expirado.
- Nas chamadas diretas à resposta, a conta está ativa, autenticada e sem aprovação pendente.
- Criar papéis e vínculos reais. Não simular o resolvedor de destino nem calcular o esperado chamando o próprio código sob teste.
- Medir painéis acessíveis distintos, não a quantidade de papéis.
- Inicializar a aplicação no modo tenancy do exemplo. Alterar somente uma flag depois do boot não demonstra que a tenancy está operante.
- Isolar e restaurar default, ordem registrada, painel corrente, organização e sessão entre exemplos.
- A URL esperada usa a base da instalação acrescida do caminho literal indicado. Os caminhos dos exemplos não representam URLs de uma instalação específica.
- O login esperado em CT-10 é a rota de login registrada para o painel corrente do exemplo.
- Não depender de um helper definido exclusivamente em outro arquivo de teste para executar um subconjunto isolado da suíte.

### Convenções dos exemplos

- **Ausente:** não há pretendida na sessão.
- **Sem organização:** convite ou conta sem organização associada, conforme o cenário.
- Nos exemplos com tenancy, `acme` e `outra` são organizações distintas e existentes.
- “Somente acme” significa que a conta pertence a `acme`, não pertence a `outra` e não é `master_global`.

## Tabela de Decisão

O domínio desta tabela são destinos internos de painéis.

| Modo | Corrente acessível | Acessíveis | Pretendida | Default | Resultado determinado | Cobertura |
|---|---|---:|---|---|---|---|
| Desligado | Sim | ≥1 | Acessível | Qualquer | Pretendida completa | CT-04 |
| Desligado | Sim | ≥1 | Ausente | Qualquer | Corrente | CT-04, CT-05 |
| Desligado | Sim | ≥1 | Inacessível | Qualquer | Corrente permitida | CT-04 |
| Desligado | Não | 0 | Ausente ou inacessível | Inacessível | Login da corrente, autenticação encerrada | CT-10 |
| Desligado | Não | 1 | Ausente | Qualquer | Único painel acessível | CT-03, CT-06 |
| Desligado | Não | 1 | Inacessível | Qualquer | Único painel acessível | CT-11 |
| Desligado | Não | 1 | Acessível | Qualquer | Painel permitido; preservação da URL completa aberta | CT-12 / Q1 |
| Desligado | Não | >1 | Ausente | Acessível | Default | CT-08 |
| Desligado | Não | >1 | Ausente | Inacessível | Primeiro acessível registrado | CT-09 |
| Desligado | Não | >1 | Inacessível | Inacessível | Primeiro acessível registrado | CT-09 |
| Desligado | Não | >1 | Acessível | Qualquer | Precedência exata aberta | Q1 |
| Ligado | Não | 1 | Ausente | Qualquer | Único painel acessível, regressão delimitada | CT-14 |
| CT-15 | Pretendida em rota sem `{tenant}` ou da própria organização não é descartada | P-04 | R6 | G2 | Pest feature HTTP | Adaptação tenancy |
| CT-16 | Pretendida de organização sem vínculo em rota inexistente é descartada | P-04 | R6 | G2 | Pest feature HTTP | Adaptação tenancy |

Corrente acessível com zero painéis acessíveis é uma combinação contraditória. Pretendida de painel acessível com zero painéis acessíveis também é contraditória neste domínio.

A cardinalidade usa 0/1/2 na fronteira entre os ramos e 3 como representante adicional de “mais de um”. Não se aplica pairwise: as condições são dependentes e foram tratadas por decisão.

## Índice de Cenários

Os destinos de implementação abaixo são propostas; não representam testes já escritos.

| ID | Cenário | Origem | Regra | Grupo | Costura | Destino de implementação |
|---|---|---|---|---|---|---|
| CT-01 | Convite `infra` sem organização | RQ-01 | R1 | G1 | componente Livewire/Filament | Kit e adaptação tenancy |
| CT-02 | Convite `admin` sem organização | RQ-01 | R1 | G1 | componente Livewire/Filament | Kit e adaptação tenancy |
| CT-03 | Um painel acessível pela resposta direta | RQ-01, RQ-03 | R1, R3 | G2 | Pest feature HTTP | Padrão do teste de resposta autorizado |
| CT-04 | Corrente acessível e partições da pretendida | RQ-02 | R2 | G2 | Pest feature HTTP | Kit e adaptação tenancy |
| CT-05 | `panel_user` sem organização e sem tenancy | RQ-02 | R2 | G1 | componente Livewire/Filament | Kit |
| CT-06 | Único painel `app` com organização | RQ-03 | R3 | G2 | Pest feature HTTP | Adaptação tenancy |
| CT-07 | Convite `panel_user` da organização | RQ-07 | R7 | G1 | componente Livewire/Filament | Adaptação tenancy |
| CT-08 | Default acessível prevalece | RQ-04, P-02 | R4, R10 | G2 | Pest feature HTTP | Teste da resposta |
| CT-09 | Default inacessível usa ordem filtrada | RQ-04, RQ-06, P-02 | R4, R6, R10 | G2 | Pest feature HTTP | Teste da resposta |
| CT-10 | Zero painéis encerram sessão e entregam login | RQ-05, P-01 | R5, R9 | G2 | Pest feature HTTP | Teste da resposta e GET do login |
| CT-11 | Pretendida e corrente inacessíveis | RQ-06 | R6 | G2 | Pest feature HTTP | Kit e adaptação tenancy |
| CT-12 | Invariante com pretendida acessível de outro painel | RQ-03, P-03 | R3, recorte Q1 | G2 | Pest feature HTTP | Teste da resposta |
| CT-13 | Nota de deploy orienta configuração | RQ-08 | R8 | G3 | unit de regra | Verificação documental proposta |
| CT-14 | Modo ligado preserva destino permitido | RQ-01 — Texto Original e limite de escopo | R1 | G1 | componente Livewire/Filament | Kit |

## Regra R1 — Convite de outro painel conclui no painel permitido

> Origem: RQ-01. Perfil completo. Técnicas: EP e matriz papel × tenancy.

```gherkin
# language: pt
Funcionalidade: Destino depois do cadastro por convite

  Regra: O cadastro por convite de outro painel alcança o painel permitido do papel

    Esquema do Cenário: [CT-01] convite infra sem organização chega ao infra
      Dado o login unificado desligado, a tenancy <tenancy> e um convite Pendente válido "infra" sem organização para uma conta nova
      E o painel corrente é "app", não há pretendida e o único painel acessível à conta criada será "infra"
      Quando o convidado conclui o cadastro pelo RegistroPorConvite
      Então a conta criada está autenticada e o destino é "/infra"
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | tenancy  | partição                   |
        | desligada | sem tenancy               |
        | ligada    | tenancy sem organização   |

    Esquema do Cenário: [CT-02] convite admin sem organização chega ao admin
      Dado o login unificado desligado, a tenancy <tenancy> e um convite Pendente válido "admin" sem organização para uma conta nova
      E o painel corrente é "app", não há pretendida e o único painel acessível à conta criada será "admin"
      Quando o convidado conclui o cadastro pelo RegistroPorConvite
      Então a conta criada está autenticada e o destino é "/admin"
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | tenancy   | partição                  |
        | desligada | sem tenancy               |
        | ligada    | tenancy sem organização   |

    Esquema do Cenário: [CT-03] a resposta aplica o destino fora do componente
      Dado o login unificado desligado, a tenancy desligada e uma conta autenticada com somente o painel "<painel>" acessível
      E o painel corrente e default é "app", inacessível à conta, e não há pretendida
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é "<destino>" e a conta permanece autenticada
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | painel | destino | partição       |
        | infra  | /infra  | único infra    |
        | admin  | /admin  | único admin    |

    Cenário: [CT-14] modo ligado preserva o destino de um único painel acessível
      Dado o login unificado ligado, a tenancy desligada e um convite Pendente válido "infra" sem organização para uma conta nova
      E o cadastro começa com "app" corrente, sem pretendida, e a conta criada terá somente "infra" acessível
      Quando o convidado conclui o cadastro pelo CadastroUnificado
      Então a conta criada está autenticada e o destino é "/infra"
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects
```

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-01 | Mantém o fallback corrente nos convites sem organização quando a tenancy está ligada | CT-01, CT-02 — tenancy ligada | Destino `/infra` ou `/admin`; o mutante devolve `/app` |
| M-02 | Usa `infra` como destino fixo para todo papel de outro painel | CT-02 | Destino `/admin`, diferente de `/infra` |
| M-03 | Corrige somente o componente, deixando a resposta direta com fallback inacessível | CT-03 | A chamada direta devolve `/infra` ou `/admin` |
| M-04 | A alteração afeta o modo ligado e o faz retornar à corrente | CT-14 | Destino `/infra` com conta autenticada; o mutante retorna `/app` |

## Regra R2 — Corrente acessível preserva o destino atual

> Origem: RQ-02. Perfil completo. Técnica: tabela de decisão.

```gherkin
# language: pt
Funcionalidade: Preservação do destino permitido

  Regra: Com corrente acessível, a pretendida permitida prevalece e o fallback é a corrente

    Esquema do Cenário: [CT-04] a resposta preserva a pretendida autorizada ou usa a corrente
      Dado o login unificado desligado, a tenancy <tenancy> e uma conta autenticada com os papéis "<papéis>", a corrente "<corrente>" acessível e o default "<default>"
      E o vínculo de organização é "<organização>" e a pretendida é "<pretendida>"
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino completo é "<destino>" e a conta permanece autenticada
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | tenancy   | papéis           | corrente | default | organização | pretendida                       | destino                          | partição                         |
        | desligada | panel_user,infra | app      | infra   | nenhuma     | ausente                          | /app                             | sem pretendida                   |
        | desligada | panel_user,infra | app      | infra   | nenhuma     | /admin                           | /app                             | painel pretendido proibido       |
        | desligada | panel_user,infra | app      | infra   | nenhuma     | /app?origem=convite               | /app?origem=convite               | pretendida no mesmo painel       |
        | desligada | panel_user,infra | app      | infra   | nenhuma     | /infra?origem=convite             | /infra?origem=convite             | pretendida em outro painel        |
        | desligada | admin,infra      | infra    | admin   | nenhuma     | ausente                          | /infra                           | corrente infra                   |
        | desligada | admin,infra      | admin    | infra   | nenhuma     | ausente                          | /admin                           | corrente admin                   |
        | desligada | admin,infra      | infra    | admin   | nenhuma     | /admin/users?origem=convite       | /admin/users?origem=convite       | subpágina acessível               |
        | ligada    | panel_user       | app      | admin   | somente acme | /app/outra                      | /app/acme                        | organização sem vínculo          |

    Cenário: [CT-05] panel_user sem organização permanece no app sem tenancy
      Dado o login unificado desligado, a tenancy desligada e um convite Pendente válido "panel_user" sem organização para uma conta nova
      E o painel corrente é "app", acessível à conta criada, e não há pretendida
      Quando o convidado conclui o cadastro pelo RegistroPorConvite
      Então a conta criada está autenticada e o destino é "/app"
      E o acesso HTTP a "/app" na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects
```

Na última linha de CT-04, `outra` existe, mas a conta não possui vínculo com ela. A fixture não usa `master_global`.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-05 | Substitui a corrente acessível pelo default | CT-04 — sem pretendida | Corrente `app`, default `infra`: destino `/app` |
| M-06 | Descarta toda pretendida | CT-04 — mesmo painel | Preserva `/app?origem=convite` integralmente |
| M-07 | Aceita pretendida somente do painel corrente | CT-04 — outro painel | Preserva `/infra?origem=convite` com corrente `app` |
| M-08 | Mantém a pretendida de painel proibido | CT-04 — painel proibido | Destino `/app`, diferente de `/admin` |
| M-32 | Remove o subcaminho da pretendida, preservando apenas a query | CT-04 — subpágina | Preserva `/admin/users?origem=convite`, inclusive `/users` |
| M-33 | Confere somente acesso ao painel e aceita organização sem vínculo | CT-04 — organização sem vínculo | Destino `/app/acme`, diferente de `/app/outra` |
| M-34 | Preserva corrente acessível somente quando ela é `app` | CT-04 — correntes `infra` e `admin` | Mantém a corrente mesmo com default diferente |

M-32–M-34 decorrem da revisão adversarial e não entram no teto de mutantes originalmente previstos pela skill.

## Regra R3 — Um painel acessível substitui a corrente inacessível

> Origem: RQ-03. Perfil completo. Técnicas: cardinalidade e EP de organização.
>
> CT-03 também exercita esta regra fora da UI.

```gherkin
# language: pt
Funcionalidade: Destino com um único painel acessível

  Regra: Sem corrente acessível, um único painel permitido determina o destino

    Cenário: [CT-06] o único painel app inclui a organização
      Dado o login unificado desligado, a tenancy ligada e uma conta autenticada "panel_user" vinculada somente à organização "acme", com somente "app" acessível
      E a corrente é "infra", o default é "admin", ambos inacessíveis, e não há pretendida
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é "/app/acme" e a conta permanece autenticada
      E o acesso HTTP a "/app/acme" na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

    Cenário: [CT-12] pretendida acessível de outro painel mantém o destino dentro do painel permitido
      Dado o login unificado desligado, a tenancy desligada e uma conta autenticada com somente "infra" acessível
      E a corrente "app" é inacessível e a pretendida "/infra?origem=convite" é acessível
      Quando a resposta de cadastro é solicitada diretamente
      Então o caminho do destino é "/infra" e a conta permanece autenticada
      E o acesso HTTP ao destino efetivamente retornado na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects
```

*(alterado em 2026-11-13: Q1 respondida como P-03 — CT-12 afirma também a URL completa `/infra?origem=convite`)* **Limite de CT-12 (original):** a preservação da query não é afirmada enquanto Q1 estiver aberta. O cenário prova a invariante de destino permitido e sessão preservada, sem escolher silenciosamente entre as leituras do requisito.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-09 | Usa o default sem conferir acesso | CT-03 | Default fixado em `app` inacessível; destino deve ser `/infra` ou `/admin` |
| M-10 | Encaminha ao login mesmo havendo exatamente um painel acessível | CT-06 | Destino `/app/acme` e conta autenticada |
| M-11 | Monta a URL do único painel sem a organização | CT-06 | Destino completo `/app/acme`, diferente de `/app` |

CT-12 também mata M-20, declarado em R6.

## Regra R4 — Vários painéis têm destino determinístico sem tela de escolha

> Origem: RQ-04. Perfil completo. Técnica: tabela de decisão.

```gherkin
# language: pt
Funcionalidade: Destino com vários painéis acessíveis

  Regra: No modo desligado, vários painéis não levam a uma tela de escolha

    Esquema do Cenário: [CT-08] default acessível prevalece sobre o primeiro acessível
      Dado o login unificado desligado, a tenancy desligada e uma conta autenticada com os painéis "<acessíveis>" acessíveis e a corrente "<corrente>" inacessível
      E não há pretendida e o default "<default>" é acessível, mas não é o primeiro acessível na ordem "<ordem>"
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é "<destino>", sem tela de escolha, e a conta permanece autenticada
      E o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | acessíveis      | corrente | default | ordem                    | destino | partição                    |
        | admin,infra     | app      | infra   | admin,app,infra          | /infra  | admin e infra               |
        | admin,app       | infra    | app     | admin,app,infra          | /app    | admin e panel_user          |
        | app,infra       | admin    | infra   | app,admin,infra          | /infra  | panel_user e infra          |
        | admin,app,infra | restrito | infra   | admin,app,infra,restrito | /infra  | três painéis acessíveis     |

    Esquema do Cenário: [CT-09] default inacessível usa o primeiro acessível na ordem registrada
      Dado o login unificado desligado, a tenancy desligada e uma conta autenticada com "admin" e "infra" acessíveis e "app" corrente e default inacessível
      E a ordem registrada é "<ordem>" e a pretendida é "<pretendida>"
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é "<destino>", sem tela de escolha, e a conta permanece autenticada
      E a pretendida proibida não permanece na sessão e o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | ordem           | pretendida | destino | partição                                          |
        | infra,app,admin | ausente    | /infra  | primeiro registrado acessível                     |
        | admin,app,infra | ausente    | /admin  | ordem inversa                                     |
        | infra,app,admin | /app       | /infra  | vários acessíveis e pretendida inacessível        |
        | admin,app,infra | /app       | /admin  | pretendida inacessível com ordem inversa          |
        | app,infra,admin | ausente    | /infra  | primeiro registrado inacessível                   |
        | app,admin,infra | /app       | /admin  | primeiro e pretendida inacessíveis                |
```

Em CT-08, os pares de painéis resultam dos respectivos papéis acumulados pela **mesma conta**. Na última linha, `restrito` é um painel de ensaio registrado e inacessível; o mecanismo de registro da fixture fica na costura proposta, sem prescrição de API.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-12 | Reutiliza a tela de escolha do modo ligado | CT-08 | Redirect direto para o destino do exemplo |
| M-13 | Usa o default mesmo quando ele é inacessível | CT-09 | Default `app`; destino `/infra` ou `/admin` |
| M-14 | Trata somente exatamente dois painéis como multipainel | CT-08 — três acessíveis | Três acessíveis ainda resultam em `/infra`, com autenticação preservada |

## Regra R5 — Zero painéis levam ao login

> Origem: RQ-05. Perfil completo. Técnica: fronteira de cardinalidade.
>
> O mesmo cenário discrimina P-01.

```gherkin
# language: pt
Funcionalidade: Saída para uma conta sem painel acessível

  Regra: Uma conta sem painel acessível recebe o login da corrente

    Esquema do Cenário: [CT-10] zero painéis encerram a autenticação e tornam o login alcançável
      Dado o login unificado desligado, a tenancy desligada e uma conta ativa autenticada sem qualquer painel acessível
      E a corrente é "<corrente>", o default é "admin" e a pretendida é "<pretendida>"
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é o login do painel "<login>" e a conta deixou de estar autenticada no guard do painel
      E o acesso HTTP a esse login na mesma sessão renderiza a tela de login com sucesso, sem retornar ao painel

      Exemplos:
        | corrente | pretendida | login | partição                         |
        | app      | ausente    | app   | zero sem pretendida              |
        | app      | /admin     | app   | zero com pretendida proibida     |
        | infra    | ausente    | infra | outra corrente sem pretendida    |
        | infra    | /admin     | infra | outra corrente com pretendida    |
```

O GET do login não pode ser precedido por logout adicional no teste. Isso esconderia a omissão do encerramento da autenticação pela resposta.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-15 | Zero painéis usa a URL da corrente | CT-10 | Destino é a rota de login da corrente |
| M-16 | Zero painéis encaminha à seleção de painel | CT-10 | Destino é o login, cuja tela é alcançável |
| M-17 | Zero painéis encaminha ao painel default | CT-10 | Destino é login de `app` ou `infra`, diferente do painel `admin` |

## Regra R6 — Pretendida e corrente inacessíveis terminam em painel permitido

> Origem: RQ-06. Perfil completo. Técnica: EP da causa da inacessibilidade.
>
> CT-09 cobre também a combinação com vários painéis acessíveis.

```gherkin
# language: pt
Funcionalidade: Descarte da pretendida inacessível

  Regra: A pretendida proibida não faz a resposta reutilizar uma corrente inacessível

    Esquema do Cenário: [CT-11] a resposta descarta a pretendida e entrega um destino permitido
      Dado o login unificado desligado, a tenancy <tenancy> e uma conta autenticada com somente o painel "<acessível>" acessível e vínculo "<organização>"
      E a corrente "<corrente>" e a pretendida "<pretendida>" são inacessíveis
      Quando a resposta de cadastro é solicitada diretamente
      Então o destino é "<destino>" e a conta permanece autenticada
      E a pretendida proibida não permanece na sessão e o acesso HTTP ao destino na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects

      Exemplos:
        | tenancy   | acessível | organização  | corrente | pretendida | destino   | partição                         |
        | desligada | infra     | nenhuma      | app      | /admin     | /infra    | painel pretendido proibido       |
        | ligada    | app       | somente acme | infra    | /app/outra | /app/acme | organização sem vínculo          |
```

Na segunda linha, a conta é `panel_user`, a organização `outra` existe e não possui vínculo com essa conta.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-18 | Sanitiza a pretendida somente quando a corrente é acessível | CT-11 | Pretendida proibida é removida mesmo com corrente inacessível |
| M-19 | Descarta a pretendida, mas mantém a corrente como fallback | CT-11 | Destino `/infra` ou `/app/acme`, diferente da corrente proibida |
| M-20 | Confunde pretendida acessível de outro painel com ausência de acesso e encerra a autenticação | CT-12 | Caminho `/infra` e conta ainda autenticada |
| M-35 | Sanitiza a pretendida somente no ramo com um painel acessível | CT-09 — pretendida `/app` | Com dois acessíveis, destino permitido e pretendida proibida removida |

M-35 decorre da revisão adversarial.

## Regra R7 — A organização do convite integra o destino do app

> Origem: RQ-07. Perfil completo. Técnica: EP de organização.

```gherkin
# language: pt
Funcionalidade: Destino do convite de organização

  Regra: O convite panel_user de uma organização conclui no app dessa organização

    Cenário: [CT-07] convite da organização acme chega a app acme
      Dado o login unificado desligado, a tenancy ligada e as organizações distintas "acme" e "outra" existentes
      E há um convite Pendente válido "panel_user" da organização "acme" para uma conta nova, sem pretendida
      Quando o convidado conclui o cadastro pelo RegistroPorConvite no painel "app"
      Então a conta criada está autenticada e o destino é "/app/acme"
      E o acesso HTTP a "/app/acme" na mesma sessão termina com sucesso, sem 403 nem ciclo de redirects
```

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-21 | Omite a organização na URL | CT-07 | Destino `/app/acme`, diferente de `/app` |
| M-22 | Usa outra organização existente para montar o destino | CT-07 | Destino contém `acme`, não `outra` |
| M-23 | Aplica o destino fixo `infra` a todos os convites | CT-07 | Destino `/app/acme`, diferente de `/infra` |

## Regra R8 — A documentação orienta a configuração de deploy

> Origem: RQ-08. Perfil mínimo. Técnica: verificação documental.
>
> A execução ou alteração do servidor permanece fora de escopo.

```gherkin
# language: pt
Funcionalidade: Orientação de deploy para o caminho app

  Regra: A documentação explica o problema da raiz e apresenta a configuração recomendada

    Cenário: [CT-13] a nota de deploy explica public app e a alternativa de configuração
      Dado o documento de deploy entregue pelo kit
      Quando o leitor consulta a orientação para instalações com DocumentRoot na raiz do projeto
      Então a nota relaciona essa configuração e o DirectorySlash ao caminho "/public/app" e recomenda DocumentRoot apontando para "public/"
      E apresenta "DirectorySlash Off" no ".htaccess" da raiz como alternativa quando o DocumentRoot não puder ser ajustado
```

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-24 | Explica o sintoma, mas omite a recomendação de apontar para `public/` | CT-13 | A recomendação identifica `public/` como DocumentRoot |
| M-25 | Orienta colocar `DirectorySlash Off` no `.htaccess` de `public/` | CT-13 | A alternativa identifica o `.htaccess` da raiz |

## Regra R9 — O login de zero painéis exige autenticação encerrada

> Origem: P-01. Perfil completo. Técnica: rastreio do efeito sobre autenticação.
>
> Cenário compartilhado: **CT-10**, incluindo o GET do login sem logout adicional ou nova autenticação pelo teste.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-26 | Devolve o login sem encerrar a autenticação | CT-10 | Conta não autenticada e login renderizado sem voltar ao painel |
| M-27 | Encerra a autenticação, mas devolve o login do default | CT-10 — corrente `app` | Login esperado é o de `app`, não o de `admin` |
| M-28 | Um retorno antecipado com pretendida presente pula o encerramento da autenticação | CT-10 — pretendida `/admin` | Conta não autenticada também nessa partição |
| M-36 | Usa sempre o login de `app`, independentemente da corrente | CT-10 — corrente `infra` | Destino é o login de `infra` |

M-36 decorre da revisão adversarial.

## Regra R10 — Default e ordem registrada governam o desempate

> Origem: P-02. Perfil completo. Técnica: tabela de decisão e EP de ordenação.
>
> Cenários compartilhados: **CT-08 e CT-09**.

### Mutantes previstos

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-29 | Escolhe sempre o primeiro acessível, ignorando default acessível | CT-08 — `admin,infra` | Primeiro acessível `admin`, default `infra`: destino `/infra` |
| M-30 | Ordena os acessíveis alfabeticamente antes de escolher | CT-09 — ordem `infra,app,admin` | Destino `/infra`; a ordenação alfabética escolheria `admin` |
| M-31 | Escolhe o primeiro registrado sem filtrar acesso | CT-09 — ordem `app,infra,admin` | Primeiro registrado `app` é proibido; destino `/infra` |

M-31 incorpora o achado adversarial sobre primeiro registrado inacessível.

## Checklist de Taxonomia

As dispensas abaixo se referem ao **comportamento especificado neste requisito**. Não afirmam inexistência de superfícies no produto nem dispensam controles da implementação.

| Gatilho | Cobertura ou limite | Grupo |
|---|---|---|
| Autorização exercida fora da UI | CT-03, CT-04, CT-06, CT-08–CT-12 solicitam a resposta diretamente | G2 |
| Acesso a organização sem vínculo | CT-04 e CT-11 incluem organização existente sem vínculo | G2 |
| Cardinalidade 0/1/N | CT-10: zero; CT-03/CT-06: um; CT-08/CT-09: dois; CT-08: três | G2 |
| Domínio condicionado pela tenancy | CT-01/CT-02 com tenancy ligada e desligada; CT-05 sem organização; CT-06/CT-07 com organização | G1, G2 |
| Ordenação de candidatos | CT-08/CT-09 distinguem default, ordem registrada, ordem inversa e primeiro proibido | G2 |
| Pretendida ausente, acessível e inacessível | CT-04, CT-09–CT-12 | G2 |
| Pretendida raiz e subpágina | CT-04 preserva caminho e query completos quando a corrente é acessível | G2 |
| Saída alcançável depois do redirect | GET na mesma sessão em CT-01–CT-12 e CT-14 | G1, G2 |
| Efeito sobre autenticação | Preservação nos destinos permitidos; encerramento e login alcançável em CT-10 | G1, G2 |
| IDOR por `{id}` de recurso | Não se aplica à regra de seleção de destino descrita; não há afirmação de cobertura de detalhes de recursos | — |
| Idempotência e concorrência de gravações | Não derivadas: o requisito não define retry, duplicidade ou corrida no cadastro; não se reivindica essa cobertura | — |
| Limites de campos na gravação | Não se aplica aos observáveis especificados de destino; nenhum novo domínio de formulário é definido | — |
| Opcional ausente × `null` × vazio | A pretendida ausente é coberta; semântica de valores malformados não é determinada pelo requisito | G2 |
| Destinatários de notificações | Não se aplica ao comportamento especificado; envio e cardinalidade de notificações não são critérios desta correção | — |
| Paginação e ordenação por coluna | Não se aplica: os critérios descrevem ordem de painéis registrados, coberta por CT-09, sem listagem paginada | — |
| Datas, timezone e DST | Não se aplica à seleção de destino especificada; os convites das fixtures são válidos | — |
| Texto livre e limites Unicode | Não se aplica: nenhum campo de texto livre ou teto é especificado | — |
| Unicidade e soft delete | Não se aplica às decisões de destino descritas | — |
| Entidade removida/desativada e matriz completa de ciclo de vida | Não derivadas: o requisito não altera esse ciclo; os convites positivos partem de Pendente válido | — |
| CRUD e IDs inexistentes | Não se aplica às operações determinadas pelo requisito | — |
| Mass assignment por tabela | Não se reivindica cobertura: o requisito não determina alterações de payload ou persistência por tabela | — |
| Upload | Não se aplica aos critérios especificados | — |
| Precisão monetária | Não se aplica aos critérios especificados | — |
| Superfície Livewire, método público e estado adulterado | CT de cadastro exercitam a entrada real; inventário e cobertura de payload inválido não foram realizados sob a restrição de fontes | G1 |
| Estado do framework usado como índice, coluna ou operador | Inventário não realizado; nenhuma alegação de proteção ou dispensa é feita | G1 |
| Discriminante nulo em consultas | Sem organização é coberto nos convites; não se reivindica cobertura de consultas com discriminante nulo | G1 |
| Navegador, pixel e acessibilidade | Nenhuma afirmação do requisito depende desses observáveis | — |

## Revisão Adversarial

Executada em contexto novo, sem histórico da derivação, com o requisito, glossário e este documento. A restrição de leitura foi observada por contrato; não havia hook técnico de bloqueio por caminho. O sandbox permaneceu somente leitura.

| Achado da primeira rodada | Fechamento na especificação |
|---|---|
| ADV-01 — pretendida proibida no ramo multipainel | CT-09 inclui pretendida `/app` inacessível nas duas ordens |
| ADV-02 — primeiro registrado sem filtrar acesso | CT-09 começa a ordem por `app` inacessível |
| ADV-03 — organização exigida para convite externo com tenancy | CT-01/CT-02 incluem tenancy ligada sem organização |
| ADV-04 — preservação da corrente limitada a `app` | CT-04 inclui correntes `infra` e `admin`, com default diferente |
| ADV-05 — login fixo de `app` no caso zero | CT-10 inclui corrente `infra` |
| ADV-06 — perda do subcaminho da pretendida | CT-04 preserva `/admin/users?origem=convite` integralmente |
| ADV-07 — autorização somente pelo painel da pretendida | CT-04/CT-11 incluem organização existente sem vínculo |
| ADV-08 — default não fixado para discriminar M-09 | CT-03 fixa default `app` inacessível |

A segunda rodada percorreu RQ-01–RQ-08, P-01/P-02, todos os cenários e mutantes. Os oito achados foram considerados fechados **na especificação**; nenhum novo defeito que passe pelo conjunto atualizado foi demonstrado. Isso não representa execução ou aprovação da implementação.

## Perguntas para o 00-requisito.md

Desvio por somente leitura: a pergunta é devolvida aqui, sem alterar o requisito. Somente o recorte dependente da resposta permanece aberto.

❓ **Q1 — raia: requisito — afeta: RQ-03/RQ-04, precedência da pretendida acessível com corrente inacessível — depende de: nenhuma**

Quando a pretendida aponta para uma URL acessível de outro painel, mas a corrente é inacessível, deve-se preservar a pretendida completa, inclusive subpágina e query, ou ir à URL determinada pelo painel único/default/ordem? O Texto Original fala em trocar apenas o fallback; RQ-03/RQ-04 formulam o destino pela URL do painel.

➡️ **Recomendação:** preservar somente a pretendida validada como acessível; caso contrário, usar o painel acessível determinado pelas regras. A recomendação mantém a falha fechada para destinos não autorizados. Até a decisão, CT-12 afirma apenas a invariante permitida.

Bloco para reconciliação no `00-requisito.md`:

| ID | Pergunta | Afeta | Recomendação | Estado |
|---|---|---|---|---|
| Q1 | Com corrente inacessível e pretendida acessível de outro painel, preservar a URL completa ou usar a URL do painel único/default/ordem? | Recorte de RQ-03/RQ-04 | Preservar somente pretendida validada como acessível; sem validação, usar o painel permitido determinado | Respondida pela sessão como P-03 (comportamento já travado pelo CT-75 de `fix/destino-apos-cadastro`), 2026-11-13 |

## Cogitado e Cortado

| Cenário cogitado | Motivo |
|---|---|
| Repetir o cadastro por convite em navegador | Componente e observação HTTP demonstram os destinos e efeitos de sessão requeridos |
| Definir destino multipainel no modo ligado | Fora do escopo e sem destino exato determinado nesta entrada |
| Executar Apache com `DocumentRoot` na raiz | Configuração real do servidor está fora de escopo; CT-13 verifica a orientação documental |
| Escolher silenciosamente a precedência da pretendida com corrente inacessível | Comportamento ambíguo devolvido em Q1; CT-12 preserva a invariante |
| Repetir CT-10 separadamente para RQ-05 e P-01 | O mesmo cenário discrimina destino, encerramento da autenticação e login alcançável |

## Sem CT-B

Nenhuma afirmação exige JavaScript executado, pixel ou acessibilidade. O cadastro é verificável por componente Livewire/Filament; destino e autenticação, pela resposta e pelo acesso HTTP na mesma sessão; a nota de deploy, por verificação estática. Não há costura `browser`.

## Referências Metodológicas Lidas

Na skill `.claude/skills/feature-test-design/`:

- `references/template-04.md` — passos 2, 6 e 7; estrutura do documento.
- `references/tecnicas-por-regra.md` — passo 3.
- `references/taxonomia-de-defeito.md` — passo 4.
- `references/gherkin.md` — passo 5.
- `references/armadilhas-de-api.md` — passos 5 e 7.
- `references/escolha-de-camada.md` — passos 2 e 7.
- `references/mutation-testing.md` — passo 6; somente previsão de mutantes.
- `references/revisao-adversarial.md` e `agents/fw-adversario-ct.md` — revisão independente.
- `../feature-wiki/references/hosts-e-subagentes.md` — contexto novo e limites dos controles.

Também foi lida `.agents/skills/testing-best-practices/SKILL.md` para orientação metodológica de observáveis e cobertura. A instrução de ler a implementação não foi aplicada, conforme a proibição expressa desta tarefa.
## Adendo de achado — CT-15 (step 9, 2026-11-13)

> Origem: P-04. Escrito pela sessão (degradação: `feature-test-design` não reinvocada para um cenário).

```gherkin
# language: pt
  Esquema do Cenário: [CT-15] pretendida em rota sem organização ou da própria organização não é descartada
    Dado o login unificado desligado, a tenancy ligada e uma conta "panel_user" somente da organização "acme"
    E a corrente "app" é acessível e a pretendida é "<pretendida>"
    Quando a resposta de cadastro é solicitada diretamente
    Então o destino é "<pretendida>"

    Exemplos:
      | pretendida                     | partição                       |
      | /app/email-verification/prompt | rota fora do grupo {tenant}    |
      | /app/acme                      | organização da própria conta   |
```

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-40 | Lê o primeiro segmento depois do painel como organização | CT-15 — verificação de e-mail | destino `/app/email-verification/prompt`, não `/app/acme` (falha medida sem a correção) |
| M-41 | Recusa toda pretendida com organização | CT-15 — própria organização | destino `/app/acme` preservado |

## Adendo de achado — CT-16 (step 9, RD-01, 2026-11-13)

```gherkin
  Cenário: [CT-16] pretendida de organização sem vínculo em rota inexistente é descartada
    Dado o login unificado desligado, a tenancy ligada e uma conta "panel_user" somente da organização "acme"
    E a corrente "app" é acessível e a pretendida é "/app/outra/rota-que-nao-existe"
    Quando a resposta de cadastro é solicitada diretamente
    Então o destino é "/app/acme" e a pretendida não fica na sessão
```

| ID | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-42 | URL que não casa rota libera em vez de barrar | CT-16 | destino `/app/acme`, não a pretendida |
