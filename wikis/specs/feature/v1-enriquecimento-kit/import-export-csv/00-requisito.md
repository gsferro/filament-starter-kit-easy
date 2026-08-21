# Requisito — Import e Export de CSV

## Origem

**Texto do usuário no chat**, transcrito verbatim, sem correção de ortografia nem reordenação:

> - olhe a documentação padrão do filament para import e export de dados via csv:
> 1.Import: https://filamentphp.com/docs/5.x/actions/import#import-action
> 2. Export: https://filamentphp.com/docs/5.x/actions/export
> - pense em ser uma action generica que pode ser ligada ou desligada conforme necessidade e escolha do usuário, mas já instalada no starter-kit
> - documente bem essa opção nos readmes e na "/wikis"
> - lembre-se de que se o tenancy estiver ativo, models que usa-la, também precisam implementar em ambos os cenarios para evitar exposição de dados
> - a ideia é que todas as models/Resources com list tenham essa opção nativa, mas caso não faça sentido tanto import quanto export, o usuário poderia colocar como off
> - coloque como uma rule que ao criar um resource, deve-se perguntar se tera import e/ou export para ser criado junto
> - assim já nasce com essa opção de uso que pode ser cometada caso não precise
> - como envolve actions, precisamos também implementar nas permissions ambas as funcionalidades, já que pode ter cenarios diferentes caso envolve quem pode exportar e quem pode importar
> - pense nos outros impactos que vale ter atenção que envolvem o starter-kit e também as features já implementadas
> - talvez salvar no audits ou em um channel de log especifico para termos o tracking ou em uma table com prune de 30 dias, o que voce julgar melhor para o cenario
> - pode criar aqui dentro desta branch
> - faça os commits individualizados por escopo
> - pode implementar ao terminar de validar as wikis criadas
> - use sub-agentes se necessário
> - valide a aplicação e crie os testes de browser para navegar e ver funcionando
> - implemente o import e export com uso de jobs para que o processamento fique async
> - quando se tratar do export, pode colocar na notifications, o link para o download
> - crie, se não houver uma forma generica nativa do filament, para poder fazer reuso de codigo, sem duplicação, dos mecanismos que envolvem o import e/ou export usando job e enviando notification, com botão de download

## Cláusulas

### Funcionalidade

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-01** | Usar as Actions padrão de Import do Filament 5 | "olhe a documentação padrão do filament para import" |
| **RQ-02** | Usar as Actions padrão de Export do Filament 5 | "2. Export: …" |
| **RQ-03** | O mecanismo é **instalado** no kit, não opcional de instalar | "mas já instalada no starter-kit" |
| **RQ-04** | Cada resource pode **ligar ou desligar** import | "caso não faça sentido tanto import quanto export, o usuário poderia colocar como off" |
| **RQ-05** | Cada resource pode ligar ou desligar export, **independente** do import | "pode ter cenarios diferentes" |
| **RQ-06** | O resource nasce com a opção presente, **comentável** | "já nasce com essa opção de uso que pode ser cometada caso não precise" |
| **RQ-07** | Alvo são os resources **com listagem** | "todas as models/Resources com list tenham essa opção nativa" |

> **RQ-04 e RQ-05 são cláusulas separadas de propósito.** "Pode desligar os dois juntos" e "pode
> desligar um sem o outro" falham independentemente, e o pedido é explícito quanto ao segundo.

### Processamento

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-08** | Import processa em **job**, assíncrono | "implemente o import e export com uso de jobs para que o processamento fique async" |
| **RQ-09** | Export processa em job, assíncrono | idem |
| **RQ-10** | Ao terminar o export, **notificação** com link de download | "pode colocar na notifications, o link para o download" |
| **RQ-11** | O download é oferecido por **botão** na notificação | "com botão de download" |
| **RQ-12** | Se o Filament não oferecer forma genérica nativa, **criar uma**, sem duplicação | "crie, se não houver uma forma generica nativa do filament, para poder fazer reuso de codigo, sem duplicação" |

> **RQ-12 é condicional.** Ela só se ativa se a pesquisa mostrar que o Filament **não** resolve.
> Se resolver, a cláusula é atendida usando o nativo — e o registro dessa verificação é parte da
> entrega, porque a alternativa (escrever camada própria sobre algo que já existe) é exatamente
> o que a cláusula proíbe.

### Segurança e isolamento

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-13** | Com tenancy ativa, models que usam a feature isolam dados **na exportação** | "models que usa-la, também precisam implementar em ambos os cenarios para evitar exposição de dados" |
| **RQ-14** | Com tenancy ativa, o mesmo vale para a **importação** | "em ambos os cenarios" |
| **RQ-15** | Permissão específica para **quem exporta** | "precisamos também implementar nas permissions ambas as funcionalidades" |
| **RQ-16** | Permissão específica para **quem importa**, distinta da de exportar | "pode ter cenarios diferentes caso envolve quem pode exportar e quem pode importar" |

> **"em ambos os cenarios"** é ambíguo — ver `## Ambiguidades`.

### Rastreabilidade

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-17** | Import e export ficam **rastreáveis**, com retenção | "salvar no audits ou em um channel de log especifico … ou em uma table com prune de 30 dias" |

> O usuário delegou a escolha do mecanismo: **"o que voce julgar melhor para o cenario"**. A
> decisão vai para ADR; a cláusula que não se negocia é existir rastro com retenção.

### Documentação e convenção

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-18** | Documentar nos **READMEs** | "documente bem essa opção nos readmes" |
| **RQ-19** | Documentar nas **wikis** | "e na '/wikis'" |
| **RQ-20** | Criar **rule**: ao criar resource, perguntar se terá import e/ou export | "coloque como uma rule que ao criar um resource, deve-se perguntar" |

### Processo

| RQ | Cláusula | Trecho de origem |
|----|----------|------------------|
| **RQ-21** | Trabalhar nesta branch | "pode criar aqui dentro desta branch" |
| **RQ-22** | Commits individualizados **por escopo** | "faça os commits individualizados por escopo" |
| **RQ-23** | Implementar só **após validar as wikis** | "pode implementar ao terminar de validar as wikis criadas" |
| **RQ-24** | Validar a aplicação | "valide a aplicação" |
| **RQ-25** | Criar **testes de browser** navegando e vendo funcionar | "crie os testes de browser para navegar e ver funcionando" |
| **RQ-26** | Levantar **outros impactos** sobre o kit e as features já implementadas | "pense nos outros impactos que vale ter atenção" |

> **RQ-26 é uma cláusula de análise, não de código.** Ela é atendida por uma seção da wiki que
> enumere os impactos, e é verificável: um impacto conhecido e não listado é violação.

## Ambiguidades

- **RQ-13/RQ-14 — o que são "ambos os cenarios"?**
  - **Assumido**: **tenancy ligada e tenancy desligada**. O kit tem os dois modos (`kit.tenancy.enabled`),
    tem suíte separada para cada um (`tests/Kit` e `tests/Tenancy`), e a frase começa com "se o
    tenancy estiver ativo", o que torna a comparação com o modo desligado a leitura natural.
  - **Leitura alternativa**: "ambos" = import e export. Ela é atendida junto, porque a RQ-13 e a
    RQ-14 já separam os dois — então a premissa é segura sob qualquer das duas leituras.
  - **Se negado**: nada muda no escopo; muda só a redação da wiki.

- **RQ-07 — "todas as models/Resources com list" significa habilitar em todos, hoje?**
  - **Assumido**: **não**. Significa que todo resource com listagem passa a ter a opção
    **disponível e visível no código**, nascendo comentada ou ligada conforme faça sentido — é o
    que a RQ-06 diz com "já nasce com essa opção de uso que pode ser cometada". Ligar export em
    todo resource do kit sem análise caso a caso criaria superfície de vazamento em telas como
    a de usuários, que carrega e-mail de todo mundo.
  - **Se negado**: o escopo cresce para uma decisão por resource existente, e cada uma precisa
    da sua justificativa de exposição de dado.

- **RQ-17 — "prune de 30 dias" é fixo?**
  - **Assumido**: é sugestão, não número acordado. O kit já tem `kit.retencao` com
    `excecoes_em_dias` e `emails_em_dias`, ambos 14, alinhados ao `days` da rotação de log.
    A chave nova segue o mesmo padrão e nasce configurável.
  - **Se negado**: fixa-se 30 sem chave de config, o que destoa das duas retenções existentes.

## Premissa de escopo verificada

O requisito fala em "todas as models/Resources com list". **Verificado antes de assumir**, e não
estimado: o kit tem **9 resources**, e os 9 têm página de listagem.

| Painel | Resource | Model |
|---|---|---|
| `/admin` | `AgenteIaResource` | `AgenteIa` |
| `/admin` | `ConviteResource` | `Convite` |
| `/admin` | `RoleResource` | `Role` (do Shield) |
| `/admin` | `TenantResource` | `Tenant` |
| `/admin` | `UserResource` | `User` |
| `/app` | `ConviteResource` | `Convite` |
| `/app` | `ProjetoResource` | `Projeto` (demo) |
| `/app` | `UserResource` | `User` |
| `/infra` | `AiRunResource` | `AiRun` |

**O que fica fora por limite físico**: as demais telas do `/infra` — exceções, trilha de e-mail,
lixeira, logs, health, backup — **não são resources em `app/Filament`**. São páginas e resources
de pacotes de terceiro, e não podem receber a convenção sem editar `vendor/`.

Isso não é ambiguidade de redação. Está registrado aqui, e a cobertura da RQ-07 no plano precisa
dizer, resource a resource, qual recebe o quê — porque quatro deles (`User` nos dois painéis,
`Convite` nos dois) carregam e-mail de pessoas, e ligar export neles sem decisão é criar
superfície de vazamento em massa logo depois de fechar outra.

## Fora de escopo

- Import/export em formato que não seja CSV
- Agendamento de exportação recorrente
- Interface de mapeamento de colunas além do que a Action nativa oferece
