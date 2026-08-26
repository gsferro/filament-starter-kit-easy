# Requisito — Aderência total do kit ao Filament Blueprint

## Fonte

- **Origem**: mensagem do dono do projeto no chat, em duas partes (o pedido, e um complemento
  enviado no meio da execução sobre o Shield)
- **Data**: 2026-08-25
- **Autor / solicitante**: gsferro
- **Fidelidade**: **alta** (texto escrito, colado verbatim)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - crie uma /feature-wiki agora como revisão completa, minuciosa e detalhada de TUDO que foi implementado ate agora usando o blueprint e as skills.
> - faça um comparativo de tudo que implementando com as boas praticas e todas as skills que o blueprint fornece
> - essa é uma etapa de auditoria e refinamento total do starter-kit
> - vamos deixar todo o kit completamente aderente ao que o pacote do blueprint orienta.
> - quer que voce use sub-agentes, loops, e todas as tools necesárias para passar um pente fino no kit do inicio ao fim, garantido a total qualidade tanto do codigo quanto do que foi impelmentado seguindo boas praticas tanto de arquitetura, quanto qualidade de codigo e, principalmente, segurança.
> - foi investido na compra deste pacote visando justamente construir um starter-kit completo, seguro, funcional e eficiente, de modo a já começar a usa-lo focando no projeto que nascerá dele, com o maximo de qualidade e facilidade (já que o proprio nome do pacote é starter-kit-easy)
> - revise se ficou alguma duvida tecnica ou alguma coisa mal construida que passou despercebido.
> - temos muitos caminhos de configuração de opt-in, o que nos faz precisar ter ainda mais cuidado na implementação para garantir que cada caminho escolhido esteja 100% funcionando como se espera e esta documentado.
> - passe o pente fino do inicio ao fim do projeto. Instale-o na pasta: "D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT" tantas quantas vezes forem necessários para confirmar que as customizações e commands do kit estão certos.
> - voce tem a permissão para manipular essa pasta a vontade, para testar tantas quantas vezes forem necessárias
> - use os /mcp do booster e do playwrite para abrir localmente as instalações para confirmação extra, além dos testes de CTs e CT-Bs
> - quero a sua garantia que voce vai deixar o sistema com ZERO erros, qualquer que seja o caminho de instalação e customização
> - aproveite para revisão profunda e minunciosamente os @README.md e @README.en.md, tanto para ver se tudo esta bem explicado, quando para ver se não tem alguma divergencia do que esta realmente disponivel e funcionando no kit
> - aproveite também e revise todas as @.ai/rules\ que o pacote criou para ver se estão em concordancia com o blueprint e o filament
> - deixo a garantia do sucesso nas suas mãos! EU confio em voce! Faça o seu melhor!

Complemento, enviado durante a execução:

> - um ponto de suma importancia é que o pacote de shield precisa estar totalmente aderente aos modulos e ações que já temos instalados. garantindo que os perfis de acesso e as permissões funcionem desde o inicio

## O padrão contra o qual se audita

O `filament/blueprint` v2.2.0 traz duas coisas, e esta wiki usa as duas:

1. **`filament-security-audit`** — o catálogo de 21 checks de segurança. Já foi rodado na wiki
   anterior (`travas-de-exclusao-e-upload-anonimo`, v0.20.0), com dois achados corrigidos. Esta
   rodada **não repete** aquele catálogo; ela o toma como linha de base e mede o que ele não cobre.
2. **As 23 referências de planejamento** em `resources/markdown/planning/` (3.232 linhas). Elas são
   escritas para *planejar* features novas, não para auditar código existente. Por isso o primeiro
   trabalho desta wiki é **extrair delas as regras verificáveis** — o que se pode medir num código
   que já existe — e é contra essa extração que o kit é comparado. A extração está em
   [`05-norma-blueprint.md`](05-norma-blueprint.md).

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Comparar tudo o que o kit implementa com as 23 referências do Blueprint e a skill de auditoria, e registrar cada desvio | "faça um comparativo de tudo que implementando com as boas praticas e todas as skills que o blueprint fornece" | funcional |
| RQ-02 | Corrigir os desvios encontrados, deixando o kit aderente | "vamos deixar todo o kit completamente aderente ao que o pacote do blueprint orienta" | funcional |
| RQ-03 | Auditar segurança como pilar principal, com arquitetura e qualidade de código ao lado | "garantido a total qualidade tanto do codigo quanto do que foi impelmentado seguindo boas praticas tanto de arquitetura, quanto qualidade de codigo e, principalmente, segurança" | não-funcional |
| RQ-04 | Toda tela, módulo e ação instalada tem permissão do Shield que **funciona desde a instalação** — não só existe, mas fecha a porta quando revogada | "o pacote de shield precisa estar totalmente aderente aos modulos e ações que já temos instalados. garantindo que os perfis de acesso e as permissões funcionem desde o inicio" | autorização |
| RQ-05 | Cada caminho de opt-in (env `KIT_*`, flags do `kit:install`, `kit:tenancy`) funciona e está documentado | "temos muitos caminhos de configuração de opt-in [...] garantir que cada caminho escolhido esteja 100% funcionando como se espera e esta documentado" | funcional |
| RQ-06 | Instalar o kit em `TESTES KIT` tantas vezes quanto necessário, por caminho, e confirmar customizações e commands | "Instale-o na pasta [...] tantas quantas vezes forem necessários para confirmar que as customizações e commands do kit estão certos" | restrição |
| RQ-07 | Abrir as instalações localmente com os MCPs (Boost, Playwright) como confirmação extra aos CT/CT-B | "use os /mcp do booster e do playwrite para abrir localmente as instalações para confirmação extra" | restrição |
| RQ-08 | Revisar `README.md` e `README.en.md`: clareza e **divergência do que realmente funciona** | "revisão profunda e minunciosamente os @README.md e @README.en.md [...] alguma divergencia do que esta realmente disponivel e funcionando" | não-funcional |
| RQ-09 | Revisar todas as `.ai/rules` contra o Blueprint e o Filament | "revise todas as @.ai/rules\ [...] em concordancia com o blueprint e o filament" | não-funcional |
| RQ-10 | Usar sub-agentes, loops e as tools necessárias | "use sub-agentes, loops, e todas as tools necesárias" | restrição |
| RQ-11 | Revisar dúvidas técnicas e construções que passaram despercebidas | "revise se ficou alguma duvida tecnica ou alguma coisa mal construida que passou despercebido" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **"ZERO erros, qualquer que seja o caminho"** — a cláusula não é atendível como está, e é
  honesto dizer isso antes de começar em vez de fingir depois. O que esta wiki entrega é:
  **cada caminho de opt-in enumerado, cada um instalado ou exercitado de verdade, cada resultado
  registrado com evidência**, e o que não pôde ser provado **declarado como lacuna**, nunca omitido.
  "Zero erros" é o alvo; a garantia é a de que nenhum erro conhecido ficou sem registro.
  - **Assumido**: a matriz de opt-in se enumera pelas chaves `KIT_*` do `.env.example`, pelas flags
    do `kit:install` e pelo `kit:tenancy`. Combinações **pairwise** entre os grupos que interagem
    (tenancy × registro × demo × hub), não o produto cartesiano completo — que são milhares de
    instalações e não cabem em nenhuma rodada.
  - **Se negado** (o usuário quiser mais combinações): a matriz do `01-plano-acao.md` cresce, e o
    custo é linear em instalações.

- **"tudo que foi implementado até agora usando o blueprint"** — só a wiki anterior usou o Blueprint
  de fato. Lido literalmente, o escopo seria um arquivo. Lido pelo resto da mensagem ("pente fino
  do inicio ao fim", "todo o kit"), o escopo é o kit inteiro.
  - **Assumido**: o kit inteiro, com o Blueprint como **norma**, não como filtro do que auditar.
  - **Se negado**: a rodada encolhe para revisar a v0.20.0 — trabalho de uma hora, não de uma wiki.

- **Norma de planejamento aplicada a código existente.** As 23 referências dizem "o plano deve
  conter X"; não dizem "o código deve ter X". Onde a tradução é direta (namespaces corretos,
  `scopedUnique` em painel tenant, `canAccessTenant()` gateando por pertencimento, `->live()` e não
  `->reactive()`), ela vira regra verificável. Onde a referência é sobre a **forma do plano**
  (incluir URL de docs, listar o comando de scaffold), ela não se aplica a código e é declarada
  `N/A` na norma extraída — não é desvio do kit.

## Fora de Escopo (declarado)

- **Repetir o catálogo de segurança** da v0.20.0. Ele é linha de base; o que esta rodada mede é o
  que ele não cobre (tenancy com o `unique`, cobertura do Shield por ação, opt-ins).
- **Auditar o código-fonte do Filament, do Shield ou dos plugins.** A norma do Blueprint é sobre
  *como a aplicação usa* o Filament.
- **Produto cartesiano dos opt-ins.** Ver a primeira ambiguidade.
- **Instalar os guidelines do Blueprint no `CLAUDE.md`.** É conteúdo de pacote pago num repositório
  público; a leitura é do `vendor/`, e essa decisão já foi tomada na wiki anterior.
