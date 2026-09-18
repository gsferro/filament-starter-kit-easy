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
