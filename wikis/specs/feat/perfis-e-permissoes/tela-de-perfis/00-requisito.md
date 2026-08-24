# Requisito — Tela de perfis (papéis) do /admin

## Fonte

- **Origem**: `.claude/requisitos/w1a-tela-de-perfis.txt` — texto colado pelo usuário no chat, gravado em arquivo na raiz do repositório
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito, copiado verbatim)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos melhorar a tela: "/admin/shield/roles"
> - vamos mudar para papeis ou perfis, o que voce preferir
> - ajuste o label e o breadcumb
> - adicione a coluna de quantos usuários tem cada perfil
> - crie uma modal overslide para exibir todos os usuários
> - sempre que for exibir o perfil, use o Nome (como é exibido na list)
> - ao abrir a tela de vinculo das permisões, precisamos exibir a quantidade de permissões que cada grupo aquele perfil já tem selecionado.
> - ao alterar, colocar na url o uuid ao inves do id (regra do proprio starter-kit, onde NUNCA se deve usar id na url para acessar ou editar qualquer registro)
> - o tab "Recursos", coloque outro tab vertical para exibir os paines, ai inves de um colapse para cada, assim, melhora o UX de quem for customizar a permissão
> - o campo "Guard", esta texto livre, porem ele vem de: @config/auth.php dentro de "guards", e ai exibimos as opções de seleção baseado daqui
> - use o mcp do playwrite para acessar a pagina do form, e pode usar as skills do design para sugerir uma tela com um belo UX e uma boa diagramação para o sets das permissões
> - use os componentes nativos do proprio filament: https://filamentphp.com/docs/5.x/forms/overview para form

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A tela deixa de se chamar "Funções" e passa a se chamar por um dos dois termos escolhidos pelo agente: "papéis" ou "perfis" | "vamos mudar para papeis ou perfis, o que voce preferir" | funcional |
| RQ-02 | O **label** do recurso (navegação, título de página, singular/plural) reflete o termo escolhido | "ajuste o label e o breadcumb" | funcional |
| RQ-03 | O **breadcrumb** reflete o termo escolhido — inclusive o segmento do registro, que hoje mostra a chave crua (`panel_user`) | "ajuste o label e o breadcumb" | funcional |
| RQ-04 | A listagem ganha uma coluna com a quantidade de usuários de cada papel | "adicione a coluna de quantos usuários tem cada perfil" | funcional |
| RQ-05 | Existe uma modal **slide-over** que exibe todos os usuários daquele papel | "crie uma modal overslide para exibir todos os usuários" | funcional |
| RQ-06 | Em **toda** exibição de papel, o texto mostrado é o rótulo legível, nunca a chave — o mesmo que a listagem já mostra | "sempre que for exibir o perfil, use o Nome (como é exibido na list)" | funcional |
| RQ-07 | Na tela de vínculo de permissões, cada **grupo** exibe quantas permissões daquele grupo o papel já tem selecionadas | "precisamos exibir a quantidade de permissões que cada grupo aquele perfil já tem selecionado" | funcional |
| RQ-08 | A URL de alteração do papel usa o `uuid`, não o `id` | "ao alterar, colocar na url o uuid ao inves do id" | restrição |
| RQ-09 | Nenhuma URL do kit usa `id` para acessar ou editar registro — regra geral do starter-kit, reafirmada aqui | "regra do proprio starter-kit, onde NUNCA se deve usar id na url para acessar ou editar qualquer registro" | restrição |
| RQ-10 | No tab "Recursos", os painéis viram um **tab vertical** em vez de um collapse por painel | "o tab \"Recursos\", coloque outro tab vertical para exibir os paines, ai inves de um colapse para cada" | funcional |
| RQ-11 | O campo "Guard" deixa de ser texto livre e passa a ser seleção, com as opções lidas das chaves de `config('auth.guards')` | "o campo \"Guard\", esta texto livre, porem ele vem de: @config/auth.php dentro de \"guards\", e ai exibimos as opções de seleção baseado daqui" | funcional |
| RQ-12 | A tela do formulário é inspecionada pelo MCP do Playwright e recebe tratamento de UX/diagramação para os sets de permissão | "use o mcp do playwrite para acessar a pagina do form, e pode usar as skills do design para sugerir uma tela com um belo UX" | não-funcional |
| RQ-13 | A implementação usa componentes **nativos** do Filament 5, sem componente próprio nem Blade custom onde o framework já resolve | "use os componentes nativos do proprio filament ... para form" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-01** — "papeis ou perfis, o que voce preferir" delega a escolha ao agente.
  - **Escolhido**: **Papéis**. Justificativa completa em `02-decisoes-arquiteturais.md`, ADR-01.
  - **Se negado**: só o `AdminPanelProvider` (três chamadas de label) e as strings de asserção dos CT mudam; nenhuma decisão de estrutura depende do termo.

- **RQ-06** — "sempre que for exibir o perfil" não delimita a superfície: o docblock de
  `app/Support/Papeis.php:16-19` fala em "sete telas", mas a varredura encontrou **doze** pontos que
  já usam `Papeis::rotulo()` e **cinco** que exibem a chave crua.
  - **Assumido**: "sempre" = todo ponto de UI do kit que renderiza nome de papel para uma pessoa —
    coluna de tabela, opção de select, badge de widget e o título do registro (breadcrumb, busca
    global). A lista fechada está em `01-plano-acao.md`, passo 5.
  - **Se negado** (o usuário queria só a tela de papéis): o passo 5 encolhe para o `getRecordTitle()`
    e os CT-06..CT-09 saem.

- **RQ-07** — "cada grupo" não diz qual é o grupo.
  - **Assumido**: os dois níveis de agrupamento que a tela tem depois do RQ-10 — o **painel**
    (tab vertical) e o **Resource** (seção dentro do painel).
  - **Se negado**: sobra apenas um dos dois níveis; o passo 4 perde uma das duas contagens.

- **RQ-04 / RQ-05** — "quantos usuários tem cada perfil" não diz o que fazer quando a mesma pessoa
  tem o mesmo papel em duas organizações (tenancy ligada: `model_has_roles` tem `team_id` na chave
  primária — `database/migrations/2026_08_12_164859_create_permission_tables.php:88-93`, logo há uma
  linha de pivot por organização).
  - **Assumido**: a coluna conta **pessoas distintas**, não linhas de pivot. "Quantos usuários" é
    contagem de gente; dizer 2 para uma pessoa em duas organizações é número errado na tela.
  - **Se negado**: a coluna volta ao `->counts('users')` puro e o CT-05 inverte a asserção.

- **RQ-09** — a cláusula é geral ("NUNCA ... qualquer registro"), mas o requisito é sobre uma tela.
  - **Assumido**: nesta entrega ela é cumprida para `roles` (a tabela que faltava) e **auditada**
    para o resto — um teste de arquitetura que reprova model com Resource cujo route key seja `id`
    fica fora, porque a auditoria manual não achou outro caso.
  - **Se negado**: entra um `pest --arch` cobrindo todos os models com Resource.

### Devolvidas pela derivação de casos de teste (`feature-test-design`)

- **RQ-07** — qual o formato do número exibido: `selecionadas/total` (`3/12`) ou só o total de
  selecionadas (`3`)?
  - **Assumido**: `selecionadas/total`. Sozinho, "3" não diz se falta muito; e o badge que o Shield
    já põe no tab externo é o total, então repetir só o total não acrescentaria nada.
  - **Se negado**: os `Então` de CT-15 e CT-16 mudam de `0/` e `1/` para `0` e `1`; nada de estrutura
    muda.

- **RQ-04** — qual o rótulo da coluna nova?
  - **Assumido**: "Usuários".
  - **Se negado**: muda o `assertSee` de CT-04 e, possivelmente, o seletor de CT-B02 — o rótulo da
    coluna colide com o rótulo da ação no modo estrito do Playwright, o que é argumento para os dois
    serem diferentes.

- **RQ-05** — o slide-over mostra só nome e e-mail, ou também a organização e a data do vínculo?
  - **Assumido**: nome e e-mail, porque é o que a listagem de usuários do `/admin` já mostra.
  - **Se negado**: o schema do slide-over ganha colunas; CT-B02 ganha asserções.

### Declarado fora do "sempre" de RQ-06 (achado 2 do quality gate)

- **O bloco de diagnóstico do 403** (`resources/views/errors/403.blade.php`) exibe `Papel ausente`
  e `Seus papéis` com as **chaves cruas**, e continua assim de propósito. O bloco inteiro está sob
  `$mostrarDiagnostico = ! app()->isProduction()` (`:15`) e o comentário do próprio arquivo
  (`:9-13`) diz que ele existe para o desenvolvedor e que por isso não pode chegar ao usuário
  final. A chave é o valor útil ali — é o que se põe em `assignRole()` — e a classe CSS `mono` já
  a marca como identificador.
  - **Se negado**: o bloco passa a usar `Papeis::rotulo()` e perde a serventia de depuração; nesse
    caso o certo é mostrar os dois, chave e rótulo.

## Fora desta entrega

- **RQ-12 (Playwright MCP)** — o MCP do Playwright é uma instância única de navegador compartilhada
  com outros agentes rodando em paralelo neste momento; usá-lo colide com eles. A inspeção visual da
  tela será feita pelo agente principal, de forma serializada, depois do merge. A validação
  automatizada desta entrega usa `pest-plugin-browser` (`tests/Browser`), que sobe servidor próprio
  in-process. Registrado como desvio em `03-progresso.md`.

- **"ver quais outras modais ainda não tem permissões... TODAS as telas, links e actions precisam ter
  permissão específica"** — item do requisito original do usuário **recortado desta feature** pelo
  coordenador e movido para a feature `feat/permissoes-de-telas-e-acoes`, que roda em paralelo em
  outro worktree. Nada dele é implementado aqui, e o `feature-quality-gate` desta wiki não deve
  acusar omissão por ele.

- **Renomear a rota `/admin/shield/roles`** — o slug vem de
  `config('filament-shield.shield_resource.slug')` e trocá-lo quebra três arquivos de teste que
  citam a URL literal, além de qualquer link salvo. RQ-01..RQ-03 falam de label e breadcrumb, não de
  URL. Fica como dívida declarada em `02-decisoes-arquiteturais.md`, ADR-02.

- **Coluna de rótulo no banco** — `roles` não tem coluna de rótulo e não ganha uma: o rótulo é
  derivado da chave por `App\Support\Papeis::rotulo()`. Criar a coluna seria uma segunda fonte de
  verdade para a mesma informação.
