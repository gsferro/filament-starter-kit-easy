# Requisito — `kit:info`: um comando que exibe os dados customizados do projeto

## Fonte

- **Origem**: argumento da invocação `/feature-wiki` no chat, pelo mantenedor do kit
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **baixa** — descrição verbal de uma linha, sem card e sem detalhamento. **Confirmar
  as premissas da seção de ambiguidades antes de implementar.**

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> crie um command do kit para exibir os dados customizados do projeto

## O que "dados customizados do projeto" significa hoje no kit, antes do plano

O requisito usa um termo que o kit já emprega com sentido próprio, e a decomposição depende dele.
Levantado no código, não suposto:

**O kit tem duas camadas de customização da instalação**, e ambas se chamam assim na própria
documentação:

1. **As respostas do `kit:install`** — cinco perguntas, aplicadas por
   `App\Support\CustomizadorDaInstalacao::aplicar()` (`app/Support/CustomizadorDaInstalacao.php:266-341`),
   que devolve um **resumo** de pares `[rótulo, valor]` impresso sob o título *"O que foi
   customizado nesta instalação:"* (`app/Console/Commands/KitInstall.php:488-505`): nome do projeto,
   banco de dados, e-mail do administrador, senha do administrador (nunca em claro), cor primária e,
   quando ligada, a multi-organização com o rótulo escolhido. Esse resumo **só aparece uma vez**, no
   fim da instalação — depois disso não há como revê-lo.
2. **As configurações do kit** editáveis em `/admin/configuracoes-do-kit` —
   `App\Settings\ConfiguracoesDoKit` (`app/Settings/ConfiguracoesDoKit.php`), 44 propriedades em
   sete grupos (identidade, e-mail, tabelas, navegação, organização, registro, login social e
   anti-robô). A regra de precedência está no docblock da classe (`:21-23`): **o banco vence em
   tempo de execução; o `.env` semeia e é o plano B.** É a fonte de uma confusão documentada ali
   mesmo: *"alguém edita o .env, nada muda, e ninguém consegue explicar por quê"*.

A página *Personalize seu projeto* da documentação (`docs/pt/comecar/instalacao-avancada.md:59-75`)
lista onze itens; os cinco primeiros são as perguntas da instalação e os demais são código ou dado de
tela, enumerados por `CustomizadorDaInstalacao::itensManuais()` (`:378-389`).

**O que NÃO é "dado customizado do projeto"** nesse vocabulário: a identidade de uma
**organização** (multi-tenancy) — `tenants.cor_primaria`, `tenants.cor_primaria_nome`,
`tenants.logo` — é CRUD comum em `/admin/organizacoes` e pertence à organização, não à instalação
(`ConfiguracoesDoKit.php:44-49`). E as chaves de `config/kit.php` que só existem no `.env` (validade
de convite, retenções, teto de upload) já são visíveis por `php artisan config:show kit`, comando
nativo do Laravel.

**Não existe nada hoje que reúna as duas camadas numa leitura só.** `config:show` mostra a config
efetiva sem dizer de onde veio; a tela de settings mostra o banco e nada do `.env`; o resumo do
`kit:install` some ao terminar. A inspeção de `php artisan list kit` (2026-09-02) confirma sete
comandos e nenhum de consulta: `kit:admin`, `kit:arte`, `kit:convites-lembrar`, `kit:install`,
`kit:midia-privada`, `kit:tenancy`, `kit:update`.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Existe um comando artisan novo no namespace `kit:` do starter-kit, listado por `php artisan list kit` | "crie um command do kit" | funcional |
| RQ-02 | O comando exibe as respostas de customização da instalação — as mesmas cinco do `kit:install` — com os valores **vigentes** | "exibir os dados customizados do projeto" | funcional (sob premissa A1) |
| RQ-03 | O comando exibe as configurações do kit editáveis em `/admin/configuracoes-do-kit`, com os valores **vigentes**, sem expor segredo em claro | "exibir os dados customizados do projeto" | funcional (sob premissa A1); restrição (segredo) |
| RQ-04 | O comando é **somente leitura**: não altera `.env`, banco, config em disco nem cache | "exibir" | restrição |

## Ambiguidades e Perguntas Abertas

<!-- Sem usuário para responder no momento da escrita: cada uma traz o par Assumido / Se negado. -->

- **A1 — o que entra em "dados customizados do projeto"?** O texto não define.
  - **Assumido**: as **duas camadas** que o kit já chama assim — as cinco respostas do `kit:install`
    (RQ-02) **e** as propriedades de `ConfiguracoesDoKit` (RQ-03). Ficam **fora**: a identidade de
    cada organização (é dado da organização, não do projeto) e as chaves de `config/kit.php` que só
    existem no `.env` (já cobertas por `config:show kit`).
  - **Se negado** (só as cinco da instalação): RQ-03 sai; os passos 3 e 4 do PRD e os cenários
    derivados deles são removidos. O comando encolhe para o resumo do `kit:install` re-exibível.
  - **Se ampliado** (incluir organizações): entra uma seção por organização e a suíte
    `tests/Tenancy` ganha os cenários; o passo 2 do PRD ganha um item.

- **A2 — o comando deve dizer de onde cada valor vem (banco × `.env`)?** "Exibir os dados" não
  pede origem, mas a origem é o que a documentação do settings aponta como fonte da confusão.
  - **Assumido**: **sim, de forma mínima** — uma linha de cabeçalho dizendo qual fonte está valendo,
    e uma seção *"Onde o .env diverge do banco"* que **só aparece quando há divergência**. Sem
    divergência, o comando não fala em `.env`.
  - **Se negado**: o passo 4 do PRD e o refactor de `devolverConfigAoEnv()` saem; o comando mostra só
    o valor vigente.

- **A3 — nome do comando.** Não foi dito.
  - **Assumido**: `kit:info`, no molde do `about` nativo do Laravel e do `composer info`.
  - **Se negado**: renomear é uma linha na signature, uma no `03` e as menções na documentação.

- **A4 — o e-mail do administrador aparece em claro ou mascarado?** O precedente do kit é mascarar
  no terminal (`KitAdmin::confirmar()`, `KitAdmin.php:171`, com `Str::mask(..., '*', 3)`).
  - **Assumido**: **mascarado**, seguindo o precedente — a saída de um comando de resumo é o que se
    cola num chamado de suporte.
  - **Se negado**: uma linha.

- **A5 — precisa de saída legível por máquina (`--json`)?** Não foi pedido.
  - **Assumido**: **não**. YAGNI; `php artisan about --json` e `config:show` já existem para script.
  - **Se negado**: entra uma opção e um cenário de teste.

- **A6 — o comando deve ser documentado nos dois idiomas, como os demais `kit:*`?** (devolvida
  pela `feature-test-design`, P2). O texto não fala em documentação.
  - **Assumido**: **sim** — é a convenção do projeto, e `tests/Kit/SiteDeDocumentacaoTest.php`
    (CT-04, CT-05) já exige paridade pt/en de toda página. Passo 4 do PRD; CT-17 do `04`.
  - **Se negado**: passo 4 e CT-17 saem; o comando continua descobrível por `php artisan list kit`.

- **P1 — os textos de estado** (`ligada`/`desligada`, `definida`/`vazia`, `indisponível`,
  `padrão do Filament`) **são aceitáveis como o PRD os escreveu?** (devolvida pela
  `feature-test-design`). São o único observável de algumas linhas, então os cenários CT-03, CT-04,
  CT-05, CT-08, CT-14 e CT-15 afirmam sobre essas palavras.
  - **Assumido**: sim.
  - **Se negado**: trocar a palavra no comando e nos cenários citados — nenhuma regra muda.

## Fora de Escopo (declarado)

- Alterar qualquer dado — para isso já existem `kit:install --custom`, `kit:admin`, `kit:tenancy`
  e a tela `/admin/configuracoes-do-kit`. O comando **aponta** para eles.
- Identidade visual das organizações (`tenants.*`) — ver A1.
- Chaves de `config/kit.php` sem propriedade em `ConfiguracoesDoKit` (convites, retenções, upload,
  `demo`, `idiomas`) — `php artisan config:show kit`.
- Uma seção no `php artisan about` nativo — considerada e descartada na ADR-01 do `02`.
- Tela no `/infra` com o mesmo conteúdo — não pedido.
