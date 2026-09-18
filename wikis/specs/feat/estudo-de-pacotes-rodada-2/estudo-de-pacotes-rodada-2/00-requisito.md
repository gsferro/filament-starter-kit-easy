# Requisito — Estudo e adoção de pacotes Filament, rodada 2

## Fonte

- **Origem**: mensagem no chat, invocando a skill `feature-wiki` (texto escrito pelo solicitante)
- **Data**: 2026-09-18
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> faça uma analise minunciosa e completa dos pacotes do filament a seguir para agregar valor ao starter-kit:
> - https://filamentphp.com/plugins/pedro-monteiro-page-header
> - https://filamentphp.com/plugins/jeffersongoncalves-page-visits
> - https://filamentphp.com/plugins/jeffersongoncalves-ban
> - https://filamentphp.com/plugins/packstub-flow
> - https://filamentphp.com/plugins/syofyan-zuhad-connection-indicator
> - https://filamentphp.com/plugins/matondo-avatar-picker
> - https://filamentphp.com/plugins/vaslv-app-version (talvez pegar pela tag lançada ou pela branch no formato: "release/<versão-da-release-no-ambiente>"
> - https://filamentphp.com/plugins/cj-ronxel-simple-draft (talvez ter uma visão no settings de quais forms podemo ou não ter o salvamento de rascunho, ou isso estar configurado direto no codigo)
> - https://filamentphp.com/plugins/yousef-aman-autosave (mesma coisa do anterior, mas esse salvaria automaticamente, analise pros e contras e como talvez ativar/desativar conforme necessidade)
> - https://filamentphp.com/plugins/alex-kramarenko-openapi-docs (criar automaticamente quando tiver alguma api disponivel, ativar/desativar no settings do kit)
> - use sub-agentes para ler os pacotes em paralelo
> - veja como foi feito os estudos de outros pacotes e como eles foram documentados para fazer o mesmo nessa rodada
> - faça a prorposta de quais vão entrar e já implementar
> - veja se é necessário criar branchs/worktrees para cada ou se faz tudo junto
> - use /code-review e use o blueprint (o auth.json ja foi colocado no projeto)
> - implemente do inicio ao fim e use as skills de busca e estudo necessários

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Os 10 pacotes listados são analisados a fundo, um a um, com o critério de "agregar valor ao starter-kit" | "faça uma analise minunciosa e completa dos pacotes do filament a seguir para agregar valor ao starter-kit" | funcional |
| RQ-02 | A análise do `app-version` avalia obter a versão pela tag lançada ou pela branch no formato `release/<versão>` | "talvez pegar pela tag lançada ou pela branch no formato: \"release/<versão-da-release-no-ambiente>\"" | funcional |
| RQ-03 | A análise do `simple-draft` avalia uma visão no Settings de quais formulários têm salvamento de rascunho, ou a alternativa de configurar direto no código | "talvez ter uma visão no settings de quais forms podemo ou não ter o salvamento de rascunho, ou isso estar configurado direto no codigo" | funcional |
| RQ-04 | A análise do `autosave` traz prós e contras e avalia como ativar/desativar conforme necessidade | "analise pros e contras e como talvez ativar/desativar conforme necessidade" | funcional |
| RQ-05 | A análise do `openapi-docs` avalia criação automática da documentação quando houver API disponível, com ativar/desativar no Settings do kit | "criar automaticamente quando tiver alguma api disponivel, ativar/desativar no settings do kit" | funcional |
| RQ-06 | A leitura dos pacotes é feita por sub-agentes em paralelo | "use sub-agentes para ler os pacotes em paralelo" | restrição |
| RQ-07 | O estudo segue o formato de documentação já usado nos estudos de pacotes anteriores do repositório | "veja como foi feito os estudos de outros pacotes e como eles foram documentados para fazer o mesmo nessa rodada" | restrição |
| RQ-08 | É apresentada uma proposta de quais pacotes entram, e o que for aprovado é implementado | "faça a prorposta de quais vão entrar e já implementar" | funcional |
| RQ-09 | É decidido e justificado se cada item precisa de branch/worktree própria ou se tudo vai junto | "veja se é necessário criar branchs/worktrees para cada ou se faz tudo junto" | funcional |
| RQ-10 | O diff da entrega passa por `/code-review` | "use /code-review" | restrição |
| RQ-11 | O Blueprint é usado na entrega (o `auth.json` já está no projeto) | "use o blueprint (o auth.json ja foi colocado no projeto)" | restrição |
| RQ-12 | A entrega vai do início ao fim, usando as skills de busca e estudo necessárias | "implemente do inicio ao fim e use as skills de busca e estudo necessários" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-08** — "quais vão entrar" pressupõe que ao menos um pacote entra. A análise concluiu que **nenhum dos 10** passa, e que cinco itens nativos entregam o mesmo valor sem dependência nova.
  - **Resolvido pelo solicitante em 2026-09-18**: escolha "Os 5 nativos" na pergunta de escopo. A proposta de adoção zero foi aceita, e a implementação é dos cinco itens nativos.

- **RQ-02** — "pegar pela tag lançada ou pela branch `release/<versão>`" não diz se a leitura é em tempo de execução (ler `.git` a cada request) ou em tempo de release (gravar num arquivo versionado).
  - **Assumido**: tempo de release. `config/kit.php:22` já guarda a versão e o `kit:update` já a grava a partir da tag (`0.34.2` ↔ tag `v0.34.2`), então a tag já é a fonte. Ler `.git` em runtime criaria uma segunda fonte de verdade e não funciona em imagem de produção sem `.git`.
  - **Se negado**: entra um resolvedor lendo `.git/HEAD` (branch) e `.git/packed-refs` (tag), com a precedência a definir, e o passo 3 do PRD cresce.

- **RQ-03 / RQ-04** — "uma visão no settings de quais forms" descreve uma tela de allowlist por formulário. Ela só faz sentido se um dos dois pacotes entrar, e nenhum entrou.
  - **Assumido**: a necessidade por trás ("não perder o que foi digitado") é atendida pelo `unsavedChangesAlerts()` nativo, que é `bool|Closure` e por isso já é governável pelo Settings **globalmente**. A granularidade por formulário não entra nesta rodada.
  - **Se negado**: reabre-se a adoção do `yousefaman/filament-autosave` no modo Create, com allowlist por `shouldAutosave()` lendo o Settings — o caminho está descrito no `02-decisoes-arquiteturais.md`, ADR-03.

- **RQ-05** — "criar automaticamente quando tiver alguma api disponivel" pressupõe que o kit tenha API. Ele não tem: `routes/` contém apenas `console.php` e `web.php`.
  - **Assumido**: a cláusula fica **fora desta entrega** por ausência do pré-requisito, e o gatilho de reabertura fica registrado.
  - **Se negado**: o kit precisaria primeiro ganhar `routes/api.php` e uma superfície REST, que é feature própria e maior que esta.

- **RQ-11** — "use o blueprint" não diz o que se espera dele: rodar a auditoria de aderência, gerar código, ou só tê-lo disponível.
  - **Assumido**: rodar a aderência. O kit já tem `tests/Kit/AderenciaAoBlueprintTest.php` e os scripts `composer bp:on` / `bp:off`, e `.ai/rules/general.md` proíbe que a dependência fique no `composer.json` commitado.
  - **Se negado**: o passo 7 do PRD muda conforme o uso pretendido.


### Perguntas devolvidas pela `feature-test-design` (2026-09-18)

As dez perguntas abaixo saíram da derivação dos casos de teste, e **cada uma tem premissa adotada,
declarada, com a direção de falha e o "Se negado" escrito**. Não são suposições escondidas — são
suposições que o dono do requisito ainda não viu. Chegaram aqui pelo achado **QA-07** do ciclo 1 do
quality gate: o `04` as deixou prontas para colagem e ninguém as colou.

**As três que precisavam de decisão foram respondidas**, e nenhuma segue como premissa:

| Pergunta | Resposta | Onde |
|---|---|---|
| nº 1 — o avatar não tem cláusula própria | **sim, vira cláusula** | Adendo 3, RQ-19 |
| nº 6 — o contrato do `kit:update` | **notifica, não propaga** — medido, não presumido | ADR-08 |
| nº 9 — rótulo que distinga as duas versões | **sim, vira requisito** | Adendo 3, RQ-20 |

As outras sete seguem na premissa adotada, cada uma com direção de falha e "Se negado" escritos.

1. **O item "avatar padrão de iniciais" não tem cláusula própria.** Ele entra só por RQ-13 ("os 5
   nativos"), e a lista dos cinco vive no `01`/`02`, não no `00`. Confirmar como **Adendo 3** com
   uma cláusula própria, ou aceitar que R7/R8 fiquem permanentemente `@premissa`.
   — *bloqueia R7, R8 (CT-20…CT-25).*
   **Premissa adotada** (falha fechado): nenhum dado de usuário sai da aplicação para terceiro na
   renderização de um avatar. **Se negado**: CT-21 inverte e passa a admitir o provider remoto.
2. **Versão do sistema vazia com a versão do kit ligada** — o rodapé mostra só a do kit, mostra
   nada, ou mostra um marcador de "sem versão"? O Adendo 2 decide as pontas (as duas preenchidas;
   as duas vazias) e não decide o meio.
   — *bloqueia a linha 3 de CT-01.*
   **Premissa adotada** (falha fechado, corrigida pela revisão adversarial — a direção anterior era
   a aberta): **o rodapé não renderiza nada**. A área recebeu Impacto 3 por divulgação de
   informação; anunciar a versão do kit quando o produto não declarou a dele é abrir por
   conveniência. **Invariante afirmado no mesmo cenário**, e ele vale nas duas leituras: nada no
   rodapé apresenta a versão do kit como se fosse a do produto. **Se negado**, a linha 3 inverte
   para "0.34.2, identificada como do kit" e o invariante fica como está.

   **RESOLVIDA em 2026-09-18 — a premissa foi NEGADA, e o ramo "Se negado" é o que vale.** Achado
   QA-16 do ciclo 2 do quality gate: o código mostra a versão do kit **rotulada**, e isso está em
   CT-01 linha 3, em CT-47 e nas docs pt/en. Três coisas sustentam o ramo negado:

   - o **Adendo 3 (RQ-20)** tornou o rótulo requisito, então `kit 0.34.2` sozinho **não** pode ser
     lido como a versão do produto — que era exatamente o dano que a premissa previa;
   - o interruptor **nasce desligado**, então este estado só existe quando alguém o ligou de
     propósito. Não é abertura por conveniência, é escolha declarada;
   - o invariante **continua valendo**, como o próprio "Se negado" antecipava, e agora é asserível
     (CT-47), em vez de prosa.

   O que mudou não foi o invariante — foi o ramo da linha 3. A premissa está fechada; o que segue
   em aberto é só a preferência do solicitante, caso ele prefira o rodapé mudo nesse caso.
3. **`versao_do_sistema` tem limite de tamanho ou formato?** O Adendo 2 diz "texto livre"
   (SemVer, data ou número de build), e texto livre sem limite entra no HTML de **toda** tela dos
   três painéis.
   — *bloqueia CT-44.*
   **Premissa adotada**: o requisito não decide, e **o cenário não escolhe por ele** — CT-44 tem
   `Então` disjuntivo (*ou grava inteiro, ou recusa*) e afirma o invariante que vale nas duas
   leituras: **nunca trunca em silêncio**. A revisão adversarial mostrou que a versão anterior
   escolhia a direção aberta ("grava 1024 caracteres") e, se negada, **inverteria** em vez de
   continuar valendo — que é a definição de premissa mal fixada. **Se negado** (houver limite),
   CT-44 fixa o ramo da recusa e o limite vira borda de BVA.
4. **O alerta de alterações não salvas nasce ligado ou desligado?** RQ-03/RQ-04 pedem
   "ativar/desativar conforme necessidade" e não dizem o estado inicial. O `01` escolheu ligado.
   — *bloqueia CT-16.*
   **Premissa adotada**: **ligado**. Aqui falha fechado aponta para ligado, não para desligado: o
   modo de falha do alerta é incômodo (um clique para desligar), e o modo de falha da ausência é
   perda de dado digitado, que é irreversível. **Invariante afirmado no mesmo cenário** (última
   linha de CT-16, acrescentada pela revisão): qualquer que seja o default, a chave governa. **Se
   negado**, só o valor de fábrica inverte.
5. **A lista de colunas de fronteira de acesso auditadas está fechada em `ativo` e
   `aprovacao_pendente`?** `deleted_at` (exclusão lógica) é fronteira de acesso pelo mesmo critério
   e não está na lista.
   — *não bloqueia mais nenhuma célula.* A revisão adversarial mostrou que usar esta premissa para
   marcar quatro células como "não se aplica" era converter escolha de implementação em cobertura:
   ela é premissa de **mecanismo** (como a exclusão aparece na trilha), não de escopo.
   **Premissa adotada**: a lista das colunas **declaradas** fica fechada nas duas, e a coluna
   `excluir` da matriz é exercitada assim mesmo, pelo invariante que vale nas duas leituras e que
   CT-42 afirma — **a exclusão lógica de uma conta deixa registro na trilha**. Para uma regra de
   compliance, falha fechado é auditar mais. **Se negado** (a exclusão também dever entrar pela
   lista declarada), CT-42 ganha a asserção da coluna e as células viram `✅F`.
6. **RQ-15 e RQ-16 têm oráculo verificável?** "Se sair atualização, atualize no starter-kit e os
   projetos que o usarem" descreve um compromisso operacional contínuo, não um estado do
   repositório. A constraint que o habilita é testável (R11); o cumprimento futuro não é.
   — *bloqueia nada hoje; declara a lacuna.*
   **Premissa adotada**: a constraint em caret é o oráculo aceito, e a atualização em si é
   processo. **Se negado**, é preciso decidir qual artefato (data do `composer.lock`? um comando de
   verificação?) passa a ser medido.
   **Correção da revisão adversarial**: o `00` assume **dois** mecanismos de propagação — a
   constraint em caret **e** `php artisan kit:update` —, e a lacuna L3 só justificava o descarte do
   primeiro (rede/Packagist). O segundo existe no repositório e é testável. A pergunta que falta é
   **qual contrato do `kit:update`** RQ-16 compra: ele deve propagar a constraint do Filament para
   o `composer.json` do projeto? apenas avisar? nada? Sem isso, escrever o cenário seria inventar o
   requisito. *bloqueia R11 pela metade.*
7. **A aparência do avatar é requisito?** Fundo, cor de texto e contraste vêm do `02` (cinza fixo,
   texto branco) e o `00` não diz nada.
   — *bloqueia qualquer cenário de cor/contraste, que por isso não foi escrito.*
   **Premissa adotada**: aparência não é requisito; só privacidade e legibilidade das iniciais são.
8. **Os rótulos dos dois campos novos na tela são requisito?** O `00` não os determina.
   — *bloqueia nada;* nenhum `Então` casa rótulo, de propósito.
9. **A versão do kit precisa aparecer no rodapé com um rótulo que a distinga da versão do
   sistema?** A pergunta nasce de uma contradição que a rodada 2 encontrou: o invariante da
   premissa nº 2 — *nada no rodapé apresenta a versão do kit como se fosse a do produto* — só é
   verificável casando **rótulo**, e a pergunta nº 8 declara que nenhum `Então` casa rótulo. Ou o
   rótulo vira requisito (Adendo 3) e o invariante ganha forma operacional, ou o invariante
   continua sendo prosa e a premissa nº 2 volta a ser lacuna cega. **Não há terceira saída**, e é
   por isso que isto é pergunta e não decisão do derivador.
   — *bloqueia o invariante de CT-01.*
10. **A tela de configurações tem uma barreira só, para abrir e para gravar?** Se tiver, a variante
    de defeito *"a autorização vive só no `mount`"* é **inexpressável** — CT-11 e CT-41 entram pela
    mesma porta e recusam juntos. Se houver (ou dever haver) permissão separada de leitura e de
    escrita, CT-41 ganha a persona que monta e não grava, e a linha do checklist deixa de ser
    cobertura parcial.
    — *bloqueia o fechamento de L7.*

---

> **A nº 6 foi respondida em 2026-09-18, por ADR-08.** O contrato do `kit:update` foi medido, não
> presumido: `app/Console/Commands/KitUpdate.php:299-306` lista `composer.json` entre os arquivos
> que o comando **nunca** sobrescreve, e `:960-970` só emite aviso. Ele **notifica**, não propaga —
> e isso é deliberado daquele comando, porque sobrescrever o `composer.json` de um projeto de
> terceiro apagaria as dependências que ele acrescentou. RQ-16 fica **atendida parcialmente, com o
> limite declarado**. Continua sendo ⚠️ apenas se o solicitante quiser um contrato diferente.

> **Sobre a nº 9**: a implementação **já** distingue — o rodapé emite `kit 0.34.2`, com o prefixo
> literal `kit `, ao lado de `v2.4.1`. Então o invariante da premissa nº 2 *é* verificável hoje. O
> que falta é decidir se esse rótulo é **requisito** (e então vira Adendo 3, com cenário que o
> casa) ou detalhe de implementação (e então o invariante continua sendo prosa). É a única das dez
> em que a resposta muda um cenário existente.


## Adendo 3 — 2026-09-18

- **Fonte**: resposta do solicitante às perguntas nº 1 e nº 9 devolvidas pela `feature-test-design`,
  apresentadas a ele durante o ciclo 1 do quality gate
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL. A resposta é curta porque as perguntas eram fechadas; elas estão transcritas
     abaixo, na íntegra, porque sem elas o "sim pros dois" não tem referente. -->

> sim pros dois, vira adendo 3

**As duas perguntas a que ele responde**, como foram feitas:

> **nº 1 — o avatar de iniciais não tem cláusula própria no requisito.** Ele entra só por RQ-13
> ("os 5 nativos"), e a lista dos cinco vive no `01`/`02`, não no `00`. Pela matriz de
> rastreabilidade isso é *código sem RQ* — a maior mudança de privacidade da entrega não tem linha
> no requisito. Vira **Adendo 3** com cláusula própria, ou aceito que as regras R7/R8 fiquem
> permanentemente `@premissa`?
>
> **nº 9 — a versão do kit precisa de rótulo que a distinga da do sistema?** A implementação
> **já** distingue: o rodapé emite `v2.4.1 · kit 0.34.2`, com o prefixo literal `kit `. Se esse
> rótulo for requisito, ele vira Adendo 3 e ganha cenário que o casa; se for detalhe de
> implementação, o invariante *"nada no rodapé apresenta a versão do kit como se fosse a do
> produto"* continua sendo prosa, sem teste que o prove.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-19 | Nenhum dado do usuário sai da aplicação para um terceiro na renderização de um avatar | "sim pros dois" → pergunta nº 1 | funcional | — |
| RQ-20 | A versão do kit, quando exibida, carrega rótulo que a distingue da versão do sistema | "sim pros dois" → pergunta nº 9 | funcional | — |

### O que este adendo fecha

**RQ-19** tira o avatar de iniciais da condição de *código sem `RQ`*. Ele era a maior mudança de
privacidade da entrega e entrava no requisito apenas de raspão, por RQ-13 ("implementar os cinco
nativos") — uma cláusula sobre **quantidade de itens**, não sobre **o que cada um garante**. As
regras R7 e R8 do `04` deixam de ser `@premissa` e passam a ter origem própria.

A cláusula é escrita como **garantia**, não como implementação: *"nenhum dado sai para terceiro"*.
Desenhar iniciais num SVG embutido é **um** jeito de cumpri-la; gerar arquivo local é outro. O que
o requisito proíbe é a requisição a terceiro, e é isso que o cenário assere.

**RQ-20** dá forma operacional a um invariante que até aqui era prosa. A premissa nº 2 afirmava
*"nada no rodapé apresenta a versão do kit como se fosse a do produto"*, e a pergunta nº 8 tinha
declarado que nenhum `Então` casa rótulo — o que deixava o invariante sem como ser verificado. A
rodada 2 da revisão adversarial registrou a contradição e escreveu *"não há terceira saída"*. Esta
é a saída: o rótulo vira requisito.

O texto do rótulo (`kit `) **não** é fixado pelo requisito — o que é exigido é que exista distinção
legível entre as duas versões. Fixar a string seria o requisito escolhendo a redação da interface,
que é o erro que a pergunta nº 8 evita.


## Fora de Escopo (declarado)

- **Adotar qualquer um dos 10 pacotes.** A recusa de cada um está registrada em `wikis/pacotes-candidatos.md` §4 com o motivo, conforme o processo daquela página (§5).
- **Documentação de API / OpenAPI** (RQ-05) — sem `routes/api.php`, não há o que documentar. Gatilho de reabertura: o kit passar a expor API REST.
- **Rascunho por formulário com allowlist no Settings** (RQ-03) — depende de adotar um dos dois pacotes de rascunho, e nenhum entrou.
- **Motivo e expiração persistidos no bloqueio de conta.** O item 4 desta entrega corrige o defeito de auditoria (`ativo` fora da trilha); persistir *por que* e *até quando* a conta foi desativada é feature nova, com migration, campos de formulário e regra de expiração. Fica registrada como candidata, não entregue.
- **Leitura de git em tempo de execução** (ver RQ-02).

## Adendo 1 — 2026-09-18

- **Fonte**: pedido do solicitante no chat, durante a implementação (antes do passo 1)
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - continua a implementação dos 5 nativos.
> - deixe a atualização do filament sem restrição, se sair atualização, atualize no starter-kit e os projetos que o usarem, sem travar versão

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-13 | Os cinco itens nativos aprovados são implementados | "continua a implementação dos 5 nativos" | funcional | — |
| RQ-14 | O kit não trava a versão do Filament: a constraint permite toda atualização da série corrente | "deixe a atualização do filament sem restrição... sem travar versão" | restrição | — |
| RQ-15 | Saindo atualização do Filament, o kit é atualizado para ela | "se sair atualização, atualize no starter-kit" | funcional | — |
| RQ-16 | Os projetos que usam o kit também recebem a atualização | "e os projetos que o usarem" | funcional | — |

### Ambiguidades do adendo

- **RQ-14** — "sem travar versão" pode significar (a) manter a constraint em caret, que já permite
  toda a série 5.x, ou (b) afrouxar para `*`, que permitiria o major 6.
  - **Assumido**: (a). O `composer.json` já declara `filament/filament: ^5.6`, que **não trava** —
    ele já permite 5.7, 5.8 e qualquer 5.x. O kit está em 5.7.6 porque ninguém rodou
    `composer update`, não por causa da constraint. `*` seria travar ao contrário: um major do
    Filament entraria sozinho e quebraria toda instalação do kit num `composer update` de rotina.
  - **Se negado**: a constraint vira `*` e o kit passa a aceitar major novo sem revisão; a
    consequência entra em ADR própria.

- **RQ-16** — "os projetos que o usarem" recebem a atualização por qual caminho?
  - **Assumido**: pelos dois que já existem — a constraint em caret no `composer.json` que o
    projeto herda (um `composer update` do projeto já traz o Filament novo) e o
    `php artisan kit:update`, que é o mecanismo do kit para propagar mudança. Nenhum mecanismo
    novo é criado.
  - **Se negado**: seria preciso um canal de atualização forçada, que é feature própria e maior
    que esta.

### Consequência sobre uma decisão já tomada

RQ-14/RQ-15 **reabrem parcialmente** a avaliação de `mortalkiller/filament-page-header`: o motivo
nº 1 para adiá-lo era exigir `filament/filament ^5.8.1` com o kit em 5.7.6. Atualizado o Filament,
esse motivo cai. Os outros dois permanecem (repo com 5 dias, breaking de major em 24 h; ganho por
tela e não por painel), e são suficientes para manter o ADIAR. O gatilho de reabertura passa a ser
só a maturidade do pacote.

## Adendo 2 — 2026-09-18

- **Fonte**: pedido do solicitante no chat, durante a implementação do passo 3
- **Fidelidade**: alta (texto escrito)

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> - sobre a versão do kit, na verdade, tem que ser a versão do sistema, o kit inicial é so uma metrica interna do starter não do produto que esta sendo implementado.
> - então, pode indicar a versão do kit, mas tem que ser customizavel para o usuário que esta usando o kit nos seus projetos

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-17 | O rodapé exibe a versão **do sistema** (o produto que nasce do kit), não a do kit | "tem que ser a versão do sistema, o kit inicial é so uma metrica interna do starter não do produto que esta sendo implementado" | funcional | RQ-02 (integral) |
| RQ-18 | A versão do kit pode ser exibida, mas é customizável por quem usa o kit no próprio projeto | "pode indicar a versão do kit, mas tem que ser customizavel para o usuário que esta usando o kit nos seus projetos" | funcional | — |

### O que este adendo corrige na leitura anterior

**RQ-02 foi lido errado, e o adendo é o que revela isso.** A cláusula original dizia "talvez pegar
pela tag lançada ou pela branch no formato `release/<versão-da-release-no-ambiente>`", e o plano a
resolveu apontando para `config('kit.version')` com o argumento de que essa chave **já é** a tag do
release — o que é verdade, mas da tag **do kit**, não da tag **do produto**. "A release no
ambiente" sempre foi a do produto implantado.

A premissa registrada em `## Ambiguidades` ("tempo de release, e a tag já é `config/kit.php:22`")
estava, portanto, respondendo a pergunta errada com precisão. É o padrão que `.ai/rules/specs.md`
nomeia — conclusão coerente sustentada por uma premissa que ninguém conferiu — e foi o solicitante
que a pegou, não um gate.

RQ-02 fica **substituída** por RQ-17. `config('kit.version')` continua no rodapé, mas como
informação secundária e opcional (RQ-18), nunca como a versão do produto.

### Decisões tomadas pelo solicitante em 2026-09-18

| Pergunta | Resposta escolhida |
|---|---|
| De onde sai a versão do sistema | **Tela + `.env`**: setting `versao_do_sistema` em /admin/configuracoes-da-aplicacao, semeado por `APP_VERSION`. Sem leitura de `.git` |
| A versão do kit ao lado | **Toggle, desligado por padrão** (`exibir_versao_do_kit`) |

Consequência de "sem leitura de `.git`": o deploy que só troca para a branch `release/2.4` **não**
atualiza o rodapé sozinho — quem implanta preenche `APP_VERSION` ou o campo da tela. Foi decisão
explícita, pelo custo do resolvedor de git e por ele não funcionar em imagem de produção sem `.git`.

Consequência de "toggle desligado": o repositório do próprio kit nasce **sem** mostrar a versão do
kit no rodapé. Quem quiser vê-la usa `php artisan kit:info` ou liga o toggle.

### Fora de escopo do adendo

- Resolver a versão do sistema lendo tag ou branch do `.git` em tempo de execução.
- Qualquer sincronia automática entre `APP_VERSION` e o processo de release do projeto: quem
  implanta é quem preenche.
