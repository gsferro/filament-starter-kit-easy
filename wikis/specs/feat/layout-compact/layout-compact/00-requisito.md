# Requisito — Opção de layout compacto

## Fonte

- **Origem**: pedido do usuário no chat, invocando a skill `feature-wiki`
- **Data**: 2026-09-21
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado diretamente
- **Gênese**: nasceu do defeito de CSS corrigido na `v0.37.1`. O sintoma relatado lá foi
  *"parece que tentou adicionar um efeito de compact mas ficou péssimo"* — e a ideia veio dele.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> esse erro do css, me deu uma ideia, que inclusive é um pacote pago no filament, de ter uma opção de layout compact
> - link do pacote: "https://filamentphp.com/plugins/filament-compact-theme"
> - veja a demo: "https://demo.filamentphp.com/login" (use o mcp do playwrite para navegar)
> - veja como colocar essa opção de layout compact no settings no kit
> - uma vez ativado, adiciona o compact no stats, table, menu, button e etc
> - o ideal é ter a possibilidade de ativar ou desativar em tempo de execução, mas é necessário entender a profundidade da mudança e o peso, se for algo complexo e que teria um custo alto, deixamos documetando tanto o pacote quanto o estudo ate uma evolução do filament que facilite, mas se for algo facil de implementar, acredito que agregaria valor.
> - como um TODO, penso que podemos colocar/criar preferencias de usuários, assim ele poderia aumentar ou diminuir a fonte, ativar o layout compact, mudar a cor, mudar o tipo de ocultação do menu (esconder tudo ou deixar exibindo o icone, como é o padrão hoje) e isso ate mesmo poderia se tornar um pacote filament externo, que nasceria no kit e poderia ser evoluido por terceiros, mas é um TODO que precisa de analise e documentação para proximas releases
> - rode essa wiki em uma nova branch baseado na main
> - use /code-review e blueprint
> - use subagents para rodar em paralelo
> - deixe tudo muito bem documentado
> - crie uma para TODOs ou com o nome normalmente utilizando no @README.md para informas futuras melhorias

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O **plugin pago** `filament-compact-theme` é estudado e documentado | "link do pacote: …" | não-funcional |
| RQ-02 | A **demo** oficial do Filament é navegada e o que ela mostra é registrado | "veja a demo: … (use o mcp do playwrite para navegar)" | não-funcional |
| RQ-03 | Fica **estudado e documentado** onde a opção de layout compacto entraria no *settings* do kit | "veja como colocar essa opção de layout compact no settings no kit" | não-funcional |
| RQ-04 | **Medir a profundidade e o peso** da mudança — é esta medição que decide entre implementar e documentar | "é necessário entender a profundidade da mudança e o peso" | restrição |
| RQ-05 | **Se fácil**: implementar, com o compacto alcançando stats, table, menu, button e demais superfícies | "se for algo facil de implementar, acredito que agregaria valor" + "adiciona o compact no stats, table, menu, button e etc" | funcional **condicional** |
| RQ-06 | **Se complexo/caro**: documentar o pacote **e o estudo**, aguardando evolução do Filament | "se for algo complexo e que teria um custo alto, deixamos documetando tanto o pacote quanto o estudo ate uma evolução do filament que facilite" | funcional **condicional** |
| RQ-07 | O TODO de **preferências de usuário** é analisado e documentado para próximas releases | "é um TODO que precisa de analise e documentação para proximas releases" | não-funcional |
| RQ-08 | O TODO enumera: tamanho de fonte, layout compacto, cor, e **modo de ocultação do menu** (esconder tudo × manter o ícone) | "ele poderia aumentar ou diminuir a fonte, ativar o layout compact, mudar a cor, mudar o tipo de ocultação do menu (esconder tudo ou deixar exibindo o icone, como é o padrão hoje)" | não-funcional |
| RQ-09 | O TODO registra a hipótese de o resultado **virar pacote Filament externo**, nascido no kit | "isso ate mesmo poderia se tornar um pacote filament externo, que nasceria no kit e poderia ser evoluido por terceiros" | não-funcional |
| RQ-10 | Existe um documento de **futuras melhorias**, ligado ao `README.md` | "crie uma para TODOs ou com o nome normalmente utilizando no @README.md para informas futuras melhorias" | não-funcional |

> **RQ-05 e RQ-06 são mutuamente exclusivas, e o gatilho é RQ-04.** Esta é a cláusula mais
> importante do requisito: o usuário **não** pediu a implementação — pediu a **medição**, e
> delegou a decisão ao resultado dela. Entregar código sem a medição, ou a medição sem a decisão
> declarada, deixa o requisito sem atender.

## Instruções de Processo (não são RQ do produto)

| ID | Instrução | Trecho literal |
|----|-----------|----------------|
| PR-01 | Branch nova **baseada na main** | "rode essa wiki em uma nova branch baseado na main" |
| PR-02 | Usar `/code-review` | "use /code-review" |
| PR-03 | Usar **blueprint** | "use blueprint" |
| PR-04 | Subagents em paralelo | "use subagents para rodar em paralelo" |
| PR-05 | Documentar muito bem | "deixe tudo muito bem documentado" |

## Ambiguidades e Perguntas Abertas

- **RQ-04 — "fácil" e "custo alto" não têm unidade, e é isso que a cláusula precisa ganhar.**
  Sem critério declarado, o agente decide por gosto e o requisito fica sem oráculo.
  **Critério adotado, a ser confirmado**: é "fácil" se a mudança (a) não exige tema Vite
  customizado novo — o kit hoje **não tem** nenhum; (b) não reintroduz o modo de falha da
  `v0.37.1`, em que ordem de cascade layer decidida por terceiro derrubava o estilo; (c) o toggle
  em tela **tem efeito no próximo request**, sem deploy. Falhar em qualquer um dos três é "caro".
  **Se negado**: RQ-05/RQ-06 podem trocar de lado, e o PRD é refeito.

- **RQ-05 — "stats, table, menu, button e etc" não é lista fechada.** O "e etc" deixa a
  superfície aberta. As quatro nomeadas são verificáveis; o resto não.
  **DECIDIDO com o usuário em 2026-09-21**: *"segue com as 4 superfícies"* — **stats, table, menu
  e button** são o escopo. Qualquer superfície além delas vai para o roadmap, não para esta
  entrega.
  **Invariante afirmado junto**: seja qual for a decisão, **nenhuma superfície fica
  parcialmente compacta** — meia tela compacta é pior que nenhuma.

- **RQ-02 — a demo exige credencial?** `demo.filamentphp.com/login` é tela de login. Se não
  houver acesso público ao painel, a navegação só alcança a tela de login, e o estudo do compacto
  vindo da demo fica limitado ao que ela expõe.
  **Premissa adotada**: registrar o que a tela pública mostra e **declarar a limitação**, em vez
  de inferir o interior.

- **RQ-02 — o MCP do Playwright não está configurado nesta sessão.** Já confirmado hoje. O
  requisito nomeia a ferramenta.
  **Premissa adotada**: usar o pacote npm `playwright`, que entrega a mesma capacidade de
  navegação e ainda permite ler estilo computado — decidido com o usuário no início da sessão para
  a wiki do CSS. **Desvio registrado porque o requisito nomeia a ferramenta.**

- **PR-03 — "blueprint"** é o **Filament Blueprint**, pacote **privado com licença**, ligado por
  `composer bp:on` / `bp:off`. A rule `.ai/rules/general.md` é dura: ele **nunca** pode estar no
  `composer.json`/`composer.lock` commitados, e há guarda em `tests/Kit/BlueprintForaDoPacoteTest.php`.
  **Premissa adotada**: se ligar, desligar antes de qualquer commit, e registrar o estado no `03`.

- **RQ-10 — qual nome?** O repo **não tem** `ROADMAP.md` nem `TODO.md`. A convenção para
  documento de referência é `wikis/*.md`, e o análogo mais próximo é o `wikis/pacotes-candidatos.md`
  (decisões sobre o que ainda não entrou).
  **DECIDIDO com o usuário em 2026-09-21**: **`wikis/roadmap.md`**, com uma linha no `README.md`, e
  **fora do `export-ignore`** — ou seja, ele **viaja** para todo projeto criado do kit, como os
  demais `wikis/*.md`.

  A consequência é deliberada e vale escrever: quem instalar o kit recebe o roadmap **do kit**
  dentro do próprio projeto. Isso é coerente com a decisão que o `.gitattributes` já registra para
  `wikis/` — *"a wiki de referência é material de trabalho de quem instala"* —, e o texto do
  roadmap precisa deixar claro que ele descreve o **futuro do kit**, não do projeto de quem o usa.
  **Se negado**: uma linha no `.gitattributes` reverte, sem tocar em mais nada.

## Fora de Escopo (declarado)

- **Comprar ou embutir o plugin pago.** O requisito pede estudá-lo e documentá-lo
- **Implementar as preferências de usuário** — RQ-07 a RQ-09 pedem *análise e documentação para
  próximas releases*, explicitamente
- **Criar o pacote Filament externo** — é hipótese a registrar (RQ-09), não entrega
- Trocar o tema visual do kit, ou mexer na paleta
- Reabrir a correção de cascade layer da `v0.37.1`
