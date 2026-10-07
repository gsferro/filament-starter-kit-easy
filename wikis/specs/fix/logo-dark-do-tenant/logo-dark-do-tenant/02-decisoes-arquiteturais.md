# Decisões Arquiteturais — fix/logo-dark-do-tenant

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01.
     A ADR não leva arquivo:linha nem contagem: cita módulo ou classe por nome. -->

## ADR-01: A P-12 do `cabecalho-do-painel` deixa de valer no `/app` com organização aberta

**Status**: Aceita
**Data**: 2026-10-07
**Portões**: difícil de reverter ✅ (o topo do `/app/{organização}` é a tela que o usuário da organização vê o dia inteiro; trocar a marca dele por update é uma mudança de identidade visível em toda instalação que tenha organização com logo, e desfazê-la depois é outra mudança visível) · surpreendente ✅ (a wiki `cabecalho-do-painel` registra, em Q16/P-12 e na documentação, que a logo da composição "é sempre a da instalação, inclusive no `/app`", confirmada pelo solicitante; quem ler só aquela wiki, ou o contrato RQ-06 no docblock de `CabecalhoDoPainel`, espera a logo da instalação) · trade-off ✅ (identidade por organização no topo contra a regra do cabeçalho de que "instalação existente não muda de cara com update do kit")

### Contexto
A feature `logo-dark-mode` criou a variante escura da logo, da instalação e da organização. A feature `cabecalho-do-painel` decidiu depois que a marca do topo é sempre a da instalação. Resultado: a logo da organização, clara e escura, só aparece na tela de bloqueio e no cadastro dela no `/admin`, e a escura só troca com o tema na tela de bloqueio. O solicitante abriu esta correção dizendo que a escura da organização "não está sendo alterada conforme troca o modo" e, perguntado onde ela deveria trocar, escolheu o topo do `/app` com organização aberta (Adendo 1 do `00`, RQ-05).

### Decisão
No painel `/app`, com uma organização aberta, a marca do topo (a marca simples do Filament e a composição do cabeçalho) usa a logo da organização, clara e escura, trocando com o tema, e cai para a da instalação quando a organização não tem logo. A reversão é **só para o `/app` com organização aberta**: o `/admin`, o `/infra`, o `/app` sem organização, as telas de autenticação e a instalação sem tenancy continuam com a logo da instalação, como a P-12 manda. O contrato RQ-06 ganha a ressalva "para quem tem organização com logo própria". A wiki antiga não é editada: esta ADR e o docblock de `CabecalhoDoPainel` registram a reversão. O caso de teste da wiki antiga que fixava a P-12 no `/app` (o CT-07 de `CabecalhoDoPainelTenancyTest`) é **substituído**, e a aprovação dessa substituição é a resposta do solicitante no Adendo 1 do `00`.

### Alternativas Consideradas
1. **Só onde a logo da organização já aparece** (tela de bloqueio e cadastro; opção (b) da Q1) — descartada pelo solicitante: não resolve a queixa, porque a tela de bloqueio já troca.
2. **Topo do `/app` e telas do cadastro no `/admin`** (opção (c)) — descartada pelo solicitante; o cadastro continua mostrando só a clara, registrado como lacuna fora de escopo no `01`.

### Consequências
- **Positivas**: a logo que a organização envia, clara e escura, é a que o usuário dela vê no topo, trocando com o tema, sem tela nova nem configuração.
- **Negativas**: toda instalação com organização que já tem logo vê o topo do `/app/{slug}` mudar no próximo update (dito no CHANGELOG, com o caminho de volta: apagar a logo da organização). O contrato RQ-06 deixa de ser incondicional.
- **Riscos**: logo pensada só para a tela de bloqueio (retangular grande, fundo opaco) aparece no topo, a 2 rem de altura. Mitigação: a ajuda do campo `logo` passa a dizer onde a logo aparece, e a documentação recomenda enviar o par.

### Referências
- `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/` (Q16, P-12), `wikis/specs/feat/logo-dark-mode/logo-dark-mode/`; `00-requisito.md` (Adendo 1, RQ-05)

---

## ADR-02: A regra de queda da logo vira um método único, `IdentidadeDoKit::logosPara()`

**Status**: Aceita
**Data**: 2026-10-07
**Portões**: difícil de reverter ❌ (é uma refatoração local, desfeita num commit; este portão **não** sustenta a ADR) · surpreendente ✅ (quem procura "qual logo a organização tem" olha em `Tenant`; quem procura "qual logo o topo mostra" olha em `CabecalhoDoPainel`; a resolução mora em `IdentidadeDoKit`, que passa a depender do model) · trade-off ✅ (um dono para uma regra compartilhada por duas superfícies, ao custo de uma dependência de `app/Support` para `app/Models`)
*Falha um portão; mantida como ADR por pedido do orquestrador (2026-10-07), porque a regra compartilhada é o que as ADR-01 e ADR-03 pressupõem.*

### Contexto
A regra "a logo da organização, senão a da instalação; a escura da organização, senão a da instalação, e nenhuma com a marca unificada" existia escrita uma vez, inline, na tela de bloqueio. Levá-la ao topo do `/app` obriga a escolher entre copiá-la para o cabeçalho (duas regras com o mesmo nome, que divergem no primeiro ajuste) ou dar a ela um dono. O projeto já tem um precedente do segundo caminho: o papel exibido tem **uma** fonte, `User::papelNoPainelCorrente()` (rule `models.md`).

### Decisão
Um método estático em `IdentidadeDoKit`: `logosPara(?Tenant $organizacao): array{clara: ?string, escura: ?string}`, com a regra por variante e independente que a tela de bloqueio já aplica (descrita uma vez no `01`, em `## Mapeamentos`). A tela de bloqueio delega a ele, mantendo o `once()` dela; `CabecalhoDoPainel` o usa na marca simples e na composição. O método não guarda cache (a classe se declara sem cache de propósito); o memo por request vive em `CabecalhoDoPainel`. A saída da tela de bloqueio não muda.

### Alternativas Consideradas
1. **Método no model**, `Tenant::logosComQueda()` — descartada: o model passaria a conhecer a identidade da instalação, invertendo a dependência.
2. **Copiar a regra em `CabecalhoDoPainel`** — descartada: dois lugares decidem o mesmo caso, e o primeiro ajuste os faria divergir.
3. **Um objeto de valor** (`ParDeLogos`) — descartada: um `array{clara, escura}` tem o formato que a tela de bloqueio e a partial `media.blade.php` já consomem.

### Consequências
- **Positivas**: uma regra, uma tabela de decisão de teste, e o próximo lugar que mostrar a logo da organização a chama em vez de copiá-la.
- **Negativas**: `IdentidadeDoKit` passa a importar `App\Models\Tenant`.
- **Riscos**: a regra, agora pública, é chamada com a organização errada por um consumidor futuro. Mitigação: ADR-03 fixa de onde vem a organização em cada superfície.

### Referências
- `App\Support\IdentidadeDoKit`, `App\Filament\Pages\Auth\TelaBloqueio`, `App\Support\CabecalhoDoPainel`, `App\Models\Tenant`
- Precedente: rule `models.md`, "`User::papelNoPainelCorrente()` é a fonte única do papel EXIBIDO"; ADR-03 desta wiki

---

## ADR-03: A organização do topo é a organização **aberta** (a da rota), não a da sessão `tenant_corrente`

**Status**: Aceita
**Data**: 2026-10-07
**Portões**: difícil de reverter ✅ (a fonte escolhida decide de qual cliente é a identidade exibida; errar não falha, mostra a logo de outro cliente, e o dado já chegou ao navegador quando alguém nota) · surpreendente ✅ (a mesma feature tem duas fontes de organização, a sessão para a tela de bloqueio e a rota para o topo, e quem vê as duas lado a lado tende a "unificar" para a mais simples) · trade-off ✅ (rota: sem consulta ao banco e sempre a organização que a tela mostra; sessão: a única disponível onde a rota não tem organização)

### Contexto
A tela de bloqueio resolve a organização por `session('tenant_corrente')`: a rota dela não tem organização na URL, então a sessão é a única pista. O topo do `/app` é desenhado em rotas **com** organização, onde o Filament já resolveu e validou a organização para o usuário e a deixa em `Filament::getTenant()`. A marca é desenhada pelos componentes Livewire `Topbar` e `Sidebar` e redesenhada em update; a organização continua presente nesses updates porque o middleware que a identifica é persistente no Livewire.

Duas coisas tornam a sessão errada para o topo. Um usuário com duas organizações abertas em duas abas tem **uma** sessão: a aba da Acme mostraria a logo da Globex se a Globex foi a última a gravar. E a sessão custa uma consulta (`Tenant::find`) que a rota já pagou. O que a rota não garante é o painel: `Filament::getTenant()` devolve o que o gerenciador guarda, sem olhar o painel corrente. Em produção o binding `filament` é `scoped` e some entre requests e jobs, então o risco é de **estado de teste** (uma organização esquecida depois de visitar o `/app`, vazando para o `/admin`) e de contexto sem painel (console, job).

### Decisão
O topo usa `Filament::getTenant()` e só o aceita quando o painel corrente tem tenancy e o objeto é um `Tenant`; do contrário a organização é `null` e a logo é a da instalação. A guarda lê `Filament::getCurrentPanel()?->hasTenancy()` e **nunca** `Paineis::correnteOuPadrao()`: sem painel corrente este cai no `app`, que tem tenancy, e a guarda deixaria de guardar. É a exceção prevista na rule `app.md` (o que não pode cair no painel padrão lê `getCurrentPanel()`), com precedente na Closure da cor da organização do `AppPanelProvider`. A tela de bloqueio continua com a organização da sessão, resolvida em `organizacaoResolvida()`, onde está a guarda dela. As duas fontes alimentam o mesmo método, `logosPara()`, que só recebe a organização.

### Alternativas Consideradas
1. **Reusar a organização da sessão no topo** — descartada: stale entre abas e uma consulta a mais, para uma informação que a rota já tem.
2. **`Filament::getTenant()` sem a guarda de painel** — descartada: o estado de teste descrito acima vira vazamento de logo de cliente para o `/admin`.
3. **`Paineis::correnteOuPadrao()` na guarda** — descartada: cai no `app` sem painel corrente.

### Consequências
- **Positivas**: cada aba mostra a logo da organização que ela mostra; zero consulta a mais; `/admin` e `/infra` nunca mostram logo de organização; os updates de `Topbar` e `Sidebar` mantêm a organização.
- **Negativas**: duas fontes de organização na mesma feature, para a mesma pergunta; a diferença precisa continuar escrita (aqui e no docblock de `CabecalhoDoPainel`) para ninguém "unificar".
- **Riscos**: um painel novo com tenancy e modelo de organização diferente de `Tenant` (projeto derivado) cai na logo da instalação, em silêncio. Mitigação: o `instanceof Tenant` é explícito e coberto.

### Referências
- `App\Support\CabecalhoDoPainel`, `App\Filament\Pages\Auth\TelaBloqueio` (`organizacaoResolvida()`), `Filament\FilamentManager` (`getTenant()`), `Filament\Panel` (`hasTenancy()`), `App\Providers\Filament\AppPanelProvider` (Closure da cor)
- Rule `.ai/rules/app.md`: "Painel corrente por `Paineis::correnteOuPadrao()`"
- Refine: ADR-03 da wiki `identidade-visual-da-organizacao`

---

## Superfície Livewire

Não exigida: a entrega não cria página, widget nem componente, e nenhum `public function` ou `public $` novo é alcançável pelo navegador. Registro do que a marca já é: ela é desenhada pelos componentes Livewire do Filament `Topbar` e `Sidebar`, que a redesenham em update (`refresh-topbar`, `refresh-sidebar`), com a organização presente porque o middleware que a identifica é persistente. O caso de update da `Topbar` está no passo 4 do `01`.
