# Requisito — Rodapé coerente entre os painéis e a tela de login

## Fonte

- **Origem**: mensagem do mantenedor no chat, invocando `/feature-wiki`
- **Data**: 2026-09-22
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado verbatim

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> temos agora a versão do sistema no "/admin/configuracoes-da-aplicacao" e uma vez que preenchemos, ela é colocada no rodape
> - acho que poderiamos também adicionar o nome do projeto ao lado da versão quando for exibir
> - adicione também o "©" antes do nome da aplicação ao exibir no rodape
> - ainda no campo "Versão do sistema" adicione um preffix do "v" que é adicionado ao numero. documentação do filament: "https://filamentphp.com/docs/5.x/forms/text-input#adding-affix-text-aside-the-field"
> - pense em como gerir melhor essa etapa de dados do rodape, talvez valha uma atenção especial se julgar necessário
> - temos um cadastro com textarea para colocar o rodape na tela de login, precisamos que seja coerente ambos os cenarios. reflita e avalie a melhor solução
> - use o blueprint para usar corretamente o filament
> - abra um card para isso e siga o ritos
> - não precisamos já lançar uma nova versao somente com essa melhoria, podemos acumular mais evoluções e ai lançamos uma versão com mais features/fix que vierem.
> - crie uma branch para rodar essa melhoria se julgar necessário

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O rodapé exibe o **nome da aplicação** ao lado da versão | "poderiamos também adicionar o nome do projeto ao lado da versão quando for exibir" | funcional |
| RQ-02 | O nome é precedido de **`© {ano corrente}`** ao ser exibido no rodapé | "adicione também o \"©\" antes do nome da aplicação ao exibir no rodape" | funcional |
| RQ-03 | O campo **"Versão do sistema"** ganha **prefixo `v`** no formulário | "no campo \"Versão do sistema\" adicione um preffix do \"v\" que é adicionado ao numero" | funcional |
| RQ-04 | O prefixo usa o recurso **nativo de afixo** do Filament | "documentação do filament: \"…/text-input#adding-affix-text-aside-the-field\"" | restrição |
| RQ-05 | A gestão dos dados do rodapé é **reavaliada**, não só estendida | "pense em como gerir melhor essa etapa de dados do rodape, talvez valha uma atenção especial se julgar necessário" | não-funcional |
| RQ-06 | O rodapé dos painéis e o da tela de login são **coerentes entre si** | "temos um cadastro com textarea para colocar o rodape na tela de login, precisamos que seja coerente ambos os cenarios" | restrição |
| RQ-07 | A solução para RQ-06 é **avaliada**, não presumida | "reflita e avalie a melhor solução" | restrição |
| RQ-08 | O Filament é usado conforme o **Blueprint** | "use o blueprint para usar corretamente o filament" | restrição |
| RQ-09 | A entrega **não gera tag própria** — acumula com outras evoluções | "não precisamos já lançar uma nova versao somente com essa melhoria" | restrição |

## O que existe hoje — entrada, não a reinvestigar

São **dois rodapés independentes**, e a independência é **deliberada e documentada**.

| | Rodapé dos painéis | Rodapé da tela de login |
|---|---|---|
| Arquivo | `resources/views/filament/versao-do-kit.blade.php` | `resources/views/filament/auth/rodape-login.blade.php` |
| Registro | hook `PanelsRenderHook::FOOTER`, sem `scopes:` (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraVersaoNoRodape:129`) | registro próprio, **fora** do `FOOTER` (`app/Providers/KitServiceProvider.php:configureTelaDeLogin:706`) |
| Conteúdo | composto por código: `v{app.version}` e, opcional, `kit {kit.version}`, unidos por ` · ` | **texto livre** do admin, em Markdown, campo `login_rodape` |
| Audiência | **só autenticado** — `filament()->auth()->check()` | **público** |
| Origem do dado | `config('app.version')`, semeado por `APP_VERSION` | `config('kit.login.rodape')`, editável em `MarkdownEditor` |

**Por que o rodapé do login não usa o hook `FOOTER`** (`KitServiceProvider.php:693-700`): o layout
`simple` do Filament **e o do `filament-auth-designer`, que é o que estas telas de fato usam**,
emitem o mesmo hook que o layout de painel autenticado — usar o
`FOOTER` faria o texto do login aparecer em **toda tela do sistema**.

**Por que o rodapé dos painéis tem guarda de visitante** (`versao-do-kit.blade.php`): o hook
`FOOTER` é emitido pelos **dois** layouts. Sem a guarda, *"a versão exata da instalação apareceria
para QUALQUER visitante da tela de login — que é entregar o mapa de CVEs aplicáveis a quem ainda
não autenticou."*

## Ambiguidades e Perguntas Abertas

- **RQ-06 — o que "coerente" significa, dado que uma superfície é pública e a outra não?**
  É a pergunta central da entrega, e ela **não é de redação**: é de escopo.
  O rodapé dos painéis esconde a versão de visitante **por decisão de segurança registrada**. A
  tela de login é pública. Logo, *"coerente"* **não pode** significar "mesmo conteúdo nos dois" —
  isso desfaria a decisão anterior sem que ninguém a tenha revisto.

  **DECIDIDO com o usuário em 2026-09-22**: coerente é **mesma linha automática nos dois, com
  visibilidade diferente**. Um **ponto único de composição** monta `© {Nome}` para todo mundo e
  acrescenta a versão **só para autenticado**. Na tela de login, o texto livre do admin passa a ser
  uma linha **adicional**, abaixo da automática — e não a única.

  As outras duas opções e por que caíram:
  - *"o login herda quando o textarea está vazio"* — coerente **só enquanto ninguém preenche**: o
    admin que digitasse qualquer coisa perderia o `© Nome` sem perceber, e os dois voltariam a
    divergir em silêncio
  - *"só acrescentar `©` + nome ao painel"* — atende RQ-01 e RQ-02 e **deixa RQ-06 sem resposta**

- **RQ-01/RQ-02 — o `©` e o nome aparecem também para o visitante?**
  **DECIDIDO com o usuário em 2026-09-22: sim, e a versão não.**
  Nome e marca já são públicos — aparecem no topo do painel e na aba do navegador. A **versão
  exata da instalação** continua guardada por `filament()->auth()->check()`, com o motivo que já
  está escrito na blade. A alternativa de um toggle `exibir_versao_publica` foi oferecida e
  **recusada**: mais um campo na tela e mais um caminho a testar, para uma escolha que a decisão
  de segurança já resolve.

- **RQ-03 — o prefixo `v` é de exibição ou entra no valor gravado?**
  O recurso citado pelo usuário (`prefix()`) é **de exibição**: não altera o que é gravado.
  E a blade **já** antepõe `'v'` (`assinatura-do-rodape.blade.php:59`).
  **Assumido**: prefixo só no formulário, valor gravado continua sem `v`, rodapé continua
  compondo o `v`. É o que torna os dois coerentes sem duplicar o caractere.
  **Se negado**: seria preciso decidir o que fazer com os valores já gravados.
  **Efeito colateral a cobrir por caso**: quem hoje digitou `v1.2.3` passa a ver `vv1.2.3` no
  campo — o prefixo torna o erro visível, mas o dado sujo continua lá.

- **RQ-05 — PERGUNTA ABERTA, e ela vai ao usuário antes de qualquer trabalho nesse sentido.**
  *"Gerir melhor essa etapa de **dados** do rodapé"* inclui a **superfície de edição**?
  A entrega atendeu a cláusula como **composição** — um ponto único monta a linha — e **não tocou
  os dados**: as três chaves que compõem uma única faixa visual continuam em **duas abas
  diferentes** da mesma tela (`nome_da_aplicacao` e `versao_do_sistema` em *Identidade*,
  `login_rodape` em *Login*), e nada na tela diz que elas se combinam.
  **Esta é a única cláusula do pedido resolvida por assunção**, enquanto as vizinhas foram levadas
  ao usuário e voltaram decididas. Achado QA-01 do quality gate, ciclos 1 e 2 — no ciclo 1 eu a
  registrei só no `03`, e o gate apontou que **o oráculo é o `00`**: quem lê o requisito não via
  que a pergunta existia.

- **RQ-05 — "gerir melhor" tem oráculo?**
  Não como está.
  **Assumido**: a entrega é um **ponto único de composição** do rodapé, com teste, em vez de a
  regra viver espalhada em duas blades.
  **Se negado**: vira só o acréscimo de RQ-01/RQ-02 na blade existente.

- **O `©` leva ano? — DECIDIDO com o usuário em 2026-09-22: ano corrente, automático.**
  O pedido trazia só o `©`. As três formas (sem ano · ano corrente · campo próprio) foram levadas
  a ele com o custo de cada uma, porque ano de copyright é conteúdo, não formatação.
  **Escolhida: ano corrente**, calculado no render. Ver ADR-04, **revertida** por esta decisão.
  **Consequência aceita**: o texto muda sozinho na virada do ano. **Consequência no teste**: nenhum
  caso afirma o ano literal sem congelar o tempo, senão a suíte quebra sozinha em 1º de janeiro.

- **O recado do textarea aparece em quais telas? — DECIDIDO com o usuário: só nas de login.**
  A **assinatura** passa a aparecer em toda tela de autenticação — consequência do hook `FOOTER` e
  comportamento pedido. O **recado** mantém o alcance de hoje: `TelaLogin` e `TelaLoginUnificada`,
  e mais nenhuma. É a falha fechada — não amplia onde texto livre de admin é publicado sem que
  alguém peça, e a tela de recuperação de senha é alvo de phishing.

- **Quem já digitou `© Nome` no textarea passa a ver duas assinaturas.**
  **Assumido**: convivem, e o admin remove a dele ao ver. É visível, não silencioso.

- **Nome da aplicação vazio — emite `©` sozinho ou omite?**
  **Assumido: omite.** Falha fechada, e o mesmo `filled()` das outras partes. Só alcançável por
  `.env` ou banco — o campo é `->required()`.

## Fora de Escopo (declarado)

- **Lançar tag** — RQ-09 é explícita
- **Mudar a decisão de o rodapé do login não usar o hook `FOOTER`** — é anterior, tem motivo
  próprio registrado, e nada no pedido a questiona
- **Tornar a versão do kit (`kit.version`) visível por padrão** — o toggle nasce desligado por
  decisão registrada
