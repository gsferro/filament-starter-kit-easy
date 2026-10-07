# Casos de Teste — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, superfície, helpers e o mapa de resolução de `## Mapeamentos`, recebidos pela sessão)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação (a correção não existia na derivação; a classe `CabecalhoDoPainel`, os helpers de teste e o `logo.blade.php` do Filament foram lidos só para nomes, classes CSS observáveis e convenção).
> **Revisão adversarial: FEITA** em 2026-10-07 (26 achados, 6 altos; aplicados 23, recusados 3 com motivo, ver `## Revisão Adversarial`).
> IDs desta wiki: **CT-40 a CT-57** e **CT-B01** (decisão da sessão em 2026-10-07, ver `### Colisão de IDs`).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Resolução do par de logos (clara e escura, por variante) | 3 | 2 | 6 | padrão |
| Topo do `/app` com organização aberta: marca simples, composição, marca unificada | 2 | 2 | 4 | padrão |
| Qual organização o topo usa (aberta pela rota, nunca outra; nunca no `/admin`, no `/infra`, na autenticação; redesenho Livewire; tela de bloqueio) | 2 | 3 | 6 | padrão, com **Impacto 3** |
| Swap claro/escuro no navegador | 2 | 2 | 4 | padrão |
| Documentação que fixa a revisão (RQ-01, RQ-02) | 1 | 1 | 1 | mínimo |

- Probabilidade 3 na resolução: quatro entradas (clara e escura da organização, clara e escura da instalação) mais o modo da marca, com a regra por variante que um desenvolvedor competente escreve "em bloco" por engano. Impacto 3 na área da organização do topo: errar a fonte não falha, **mostra a logo de outra organização** (dado de terceiro) no topo de quem não é cliente dela, e o dado já chegou ao navegador quando alguém nota (ADR-03). P×I 6 não chega ao perfil completo, mas o Impacto 3 disparou a revisão adversarial.
- Técnicas aplicadas: tabela de decisão por variante (R1, com ausente ≠ vazio ≠ órfão), EP (partições da organização: com par, sem logo, só a clara, só a escura; modo unificado × separado; painel corrente × objeto aberto), rastreio de efeito negativo com controle positivo em todo cenário de ausência, 2-switch de organização no mesmo usuário nas duas ordens de vínculo (R6), estado do framework usado sem validar (R7).
- Técnica escalada acima do perfil: R6 e R7 usam o cruzamento completo (organização × painel × vínculo) em área `padrão` porque o defeito é vazamento entre clientes, que a amostragem não pega.
- Cenários: 18 (mais 1 CT-B no 05) · Regras: 11 · Mutantes previstos: 47 (mais 5 no 05: M36 a M39 e M48) · Sem matador: 1 (M51, por HTTP)
<!-- derivado do arquivo por grep -c (template-04 §Contagem do cabeçalho); recalcular a cada cenário novo. Os mutantes M36 a M39 e M48 de R10 vivem no 05 -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a regra de resolução do par (um método único da identidade da instalação), a marca simples e a composição do cabeçalho (`CabecalhoDoPainel`), a delegação da tela de bloqueio, o parâmetro novo da factory de organização, o texto de ajuda do upload, a documentação pt/en | CT-40, CT-41, CT-42, CT-45, CT-54, CT-55 |
| F | escolher o par por variante com queda; trocar a marca do topo no `/app` com organização aberta; a marca unificada ignorar a escura; a organização do topo ser a aberta pela rota e só ela; o redesenho Livewire manter o par | CT-40…CT-57 |
| D | `logo`/`logo_dark` da organização e da instalação: preenchida, `null`, string vazia, arquivo ausente do disco (órfã), em ambas as variantes; duas organizações com logo, uma pessoa nas duas, nas duas ordens de vínculo; organização sem vínculo e `master_global` sem vínculo; objeto "tenant" que não é organização; sessão `tenant_corrente` apontando outra organização | CT-40, CT-49, CT-50, CT-52, CT-53, CT-56 |
| I | `/app/{slug}` (marca simples e composição), `/admin`, `/infra`, `/app/login`, `/app`, `/app/new`, a tela de bloqueio `/app/screen/lock`, o redesenho Livewire dos componentes da barra superior e da barra lateral (`refresh-topbar`, `refresh-sidebar`), o navegador (swap por tema, com alternância sem recarregar) | CT-42…CT-54, CT-56, CT-57, CT-B01 |
| P | navegador (Playwright: classe `dark` no `<html>`, `getComputedStyle`, seletor de tema do Filament), disco `public` (arquivo da logo, real no navegador, fake nos demais), gerenciador do Filament (`scoped` em produção, vivo entre requests em teste) | CT-B01, CT-51, CT-52 |
| O | o usuário da organização abre o `/app/{slug}` o dia inteiro e alterna o tema; o mantenedor da instalação pode ter organização esquecida no gerenciador em teste ou job; uma pessoa com duas organizações abertas em duas abas | CT-49, CT-51, CT-52, CT-53, CT-B01 |
| T | estado compartilhado entre requests (memo da marca), ordem de abertura e de vínculo das organizações, redesenho Livewire posterior à página, alternância de tema depois do load | CT-49, CT-53, CT-B01 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o par de logos resolve por variante e de forma independente: clara = clara da organização, senão da instalação; escura = nenhuma na marca unificada, senão escura da organização, senão da instalação; nunca cai na clara | resolução (padrão) | RQ-05, P-03, P-04 | tabela de decisão (modo × quatro entradas) + ausente ≠ vazio ≠ órfão | CT-40 |
| R2 — sem organização aberta, o par é o da instalação, como hoje | resolução (padrão) | RQ-04, RQ-05 | EP (modo unificado × separado) | CT-41 |
| R3 — a marca simples e a composição do `/app` com organização aberta são o par da organização, com a queda por variante; na composição a escura depende da clara; sempre exatamente uma `fi-logo-dark` por marca | topo do `/app` (padrão) | RQ-05, RQ-02, P-02, P-03 | EP (com par, sem logo, só a clara, só a escura com e sem clara na instalação) + rastreio de efeito (um só `fi-logo-dark`) | CT-42, CT-43, CT-44, CT-57 |
| R4 — a composição do cabeçalho com organização aberta mostra o par da organização, com exatamente um `fi-logo-dark`, e descarta a escura quando não há clara | topo do `/app` (padrão) | RQ-05, RQ-02, P-02, P-03 | EP + rastreio de efeito (um só `fi-logo-dark`) | CT-45, CT-46, CT-47, CT-57 |
| R5 — com a marca unificada a escura da organização e a da instalação são ignoradas, nas duas formas | topo do `/app` (padrão) | P-04, RQ-05 | EP (forma da marca) | CT-48 (e as linhas `unificada` de CT-40) |
| R6 — a organização do topo é a **aberta pela rota**, com ou sem vínculo formal (a `master_global` sem vínculo vê a logo da organização que abriu): nem a primeira nem a última vinculada, nem a da sessão, nem a do request anterior; quem a rota recusa não a recebe | organização do topo (padrão, Impacto 3) | RQ-05 | 2-switch de organização nas duas ordens + EP (vínculo) | CT-49, CT-50, CT-56 |
| R7 — `/admin`, `/infra`, as telas de autenticação, o cadastro de organização, `/app` sem organização e o contexto sem painel nunca mostram logo de organização, ainda que haja uma "aberta" no gerenciador ou na sessão; só o painel com tenancy e uma `Tenant` a mostra | organização do topo (padrão, Impacto 3) | RQ-04, RQ-05 | EP (painel corrente × objeto aberto) | CT-51, CT-52 |
| R8 — o redesenho Livewire da barra superior e da barra lateral mantém o par da organização aberta, mesmo com a sessão apontando outra | organização do topo (padrão, Impacto 3) | RQ-05 | rastreio de efeito (redesenho = outra renderização) + EP (componente, sessão) | CT-53 |
| R9 — a tela de bloqueio segue com a organização da sessão e o mesmo par; a organização do topo não a contamina | organização do topo (padrão, Impacto 3) | P-01, RQ-04 | EP (fonte: sessão × gerenciador) | CT-54 |
| R10 — a imagem visível no topo do `/app/{slug}` troca com o tema, também quando o tema é alternado sem recarregar | swap (padrão) | RQ-05, RQ-02 | EP (forma da marca × sentido da alternância) | CT-B01 (no `05`) |
| R11 — a documentação pt e en deixa de afirmar o que a mudança desmente | documentação (mínimo) | RQ-01, RQ-02 | EP (idioma) | CT-55 |

- RQ-03 — substituída por RQ-05 (Adendo 1), sem cenário. P-06 — fora desta entrega (`## Cobertura do Requisito` do `01`), sem cenário. P-05 (revisada, ADV-24) — a página de erro do Sentinel herda a marca do painel; o que ela mostra decorre de quando o middleware fixa a organização. Comportamento decorrente, não exigido e não testado nesta entrega (a única exceção é a linha `sem vínculo` de CT-50, que mede a marca na página de recusa sem a exigir).
- RQ-01 e RQ-02 são **análise**: o entregável é a tabela `## Revisão da feature` do `01`, que não é verificável por teste. O que é verificável delas está coberto: o que ficou de fora (L1, a escura nunca no topo) por R3 a R5 e R10; a documentação que fixa o mapa (L5) por R11; a factory (L3) é provada por uso em todo cenário que precisa de organização com par; o texto de ajuda do upload (L4) e a lacuna L7 (a escura da organização invisível no cadastro) ficam em `## Cogitado e Cortado`.
- A tabela de decisão de R1 não é duplicada por forma de renderização: R3 a R5 afirmam só a **ligação** (o topo chama a regra com a organização certa), com uma linha por partição que a ligação pode errar.
- R1, R2 e R5 herdam `padrão` mesmo com tamanho de `mínimo`: a regra por variante é onde mora a diferença entre "em bloco" e "por variante" (M1 de R1), que EP de um valor só não distingue.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 Resolução do par | R1, R2 | unit de regra | existente — `tests/Kit/LogoDarkModeTest.php` (molde: tabela de decisão do `[CT-16]` da wiki ancestral `logo-dark-mode`, com `Storage::fake('public')`, `config('kit.identidade.*')` e `Tenant::factory()`) | o `Então` é um valor calculado (duas URLs); não precisa de request, rota nem painel. `tests/Kit` liga o `TestCase` da aplicação, então container, config e disco resolvem | — (preenchida pelo construtor da implementação) |
| G2 Topo do `/app` por requisição | R3, R4, R5 | Pest feature HTTP | existente — `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (`gravarLogoDaInstalacaoDaTenancia()`, `gravarCabecalhoDaTenancia()`, `imagensDaMarcaDaTenancia()`, `regiaoDoHeader()`) | o `Então` é o HTML do topo (as `<img>` e as classes `fi-logo-light`/`fi-logo-dark`); `tests/Tenancy` é onde `permission.teams` e a organização na rota existem | — |
| G3 Organização do topo | R6, R7 | Pest feature HTTP | existente — o mesmo arquivo; CT-52 chama a marca do topo direto, "por fora" da rota, com o painel e o objeto aberto arranjados no gerenciador | a fonte da organização é estado do gerenciador do Filament, que a requisição só alcança de um jeito; o estado sem painel corrente só se arranja chamando direto | — |
| G4 Redesenho Livewire | R8 | componente Livewire/Filament | **nova**, no mesmo arquivo de tenancy: `Livewire::test(\Filament\Livewire\Topbar::class)` e `Livewire::test(\Filament\Livewire\Sidebar::class)` com o painel `app` corrente e a organização fixada por `Filament::setTenant($org)`, o estado que o middleware persistente `IdentifyTenant` recria no update real. Nenhum teste existente renderiza a barra superior nem a lateral por componente | decidido por D5 do `01` (Q6): o POST real ao endpoint de update fica fora do arnês; o restante do mutante da rota é coberto pelos CT HTTP | — |
| G5 Tela de bloqueio | R9 | Pest feature HTTP | existente — `tests/Kit/LogoDarkModeTest.php` (molde: sessão bloqueada do `[CT-16]` da ancestral) | o `Então` é o HTML da tela de bloqueio, resolvida pela sessão | — |
| G6 Swap no navegador | R10 | browser | existente — `tests/BrowserTenancy/IdentidadeVisualTest.php` | só o navegador prova qual imagem está **visível** e que ela troca sem recarregar (`getComputedStyle`, seletor de tema, classe `dark`) | — |
| G7 Documentação | R11 | unit de regra | existente — `tests/Kit/LogoDarkModeTest.php` (molde: `[CT-20]` da ancestral, leitura de `docs/` com `naArvoreDoKit()`) | leitura de arquivo da árvore | — |

A coluna `Confirmada` é preenchida pelo construtor da implementação (decisão da sessão, 2026-10-07). `tests/Kit` e `tests/Tenancy` ligam o `TestCase` da aplicação (`tests/Pest.php`); "unit de regra" aqui roda com container. Helper usado só por `CabecalhoDoPainelTenancyTest.php` fica nele (`.ai/rules/testes.md`).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `logosPara()`, `organizacaoAberta()`, o memo por `Request` (`WeakMap`) e a decisão D4 | escolha de implementação | detalhe dos cenários; o oráculo é a **URL** da `<img>` e a classe CSS, não o método |
| a ordem exata da regra (`clara = org ?? inst`; `escura = unificada ? null : org ?? inst`) | é a leitura do requisito (P-03, P-04) **e** do código da tela de bloqueio; a fonte do oráculo é P-03/P-04 e a tabela do `01` `## Mapeamentos` (fonte única, decidida pela sessão na D2) | linhas da tabela de decisão de CT-40 |
| a linha "só a escura, e a instalação sem clara" de `## Mapeamentos` (clara `null`; a composição descarta a escura; a marca simples mostra o nome em texto no claro e a `<img>` escura no escuro) | o comportamento visível **só o PRD** determina (P-03 diz só "clara da instalação com a escura da organização"); o `Então` não parte do plano: parte da consequência do comportamento nativo do Filament para marca clara nula e da dependência clara→escura que a ancestral `cabecalho-do-painel` já fixou (P-07). Marcado `@premissa` (mecanismo); não é pergunta de requisito porque nenhuma decisão nova é tomada | CT-44, CT-47 |
| `Filament::getTenant()`, `getCurrentPanel()?->hasTenancy()`, `instanceof Tenant` | escolha de implementação (ADR-03) | detalhe de CT-52; o oráculo é "a marca devolvida é a da instalação" em cada linha, não a chamada |
| os `alt` das imagens (P-06) | fora desta entrega no `01` | sem cenário |
| a página de erro do Sentinel (P-05, revisada em ADV-24) | comportamento decorrente da marca do painel, não exigido | sem cenário próprio; só a linha `sem vínculo` de CT-50 mede a marca na página de recusa |
| o texto exato do parágrafo novo da documentação | o `01` o propõe; o requisito não o determina | CT-55 afirma só a **ausência** dos termos-chave obsoletos e a presença de "/app" junto de "organização" na seção; o texto novo é do PRD |
| os nomes dos arquivos de teste e do helper `gravarLogoDaInstalacaoDaTenancia(bool $unifica = true)` | escolha de implementação | Setup Global |

**Perguntas geradas pela derivação** (a sessão renumerou a `Q?1` para Q6, sequência única da feature):

❓ Q6 · raia: desenho · afeta: RQ-05 · depende de: — · **respondida por D5 no `01` (sessão, 2026-10-07)**
O CT-53 (redesenho da barra superior) precisaria de um update real (POST ao endpoint do Livewire com o snapshot da página)?
➡️ Decisão (D5): não. O CT-53 usa `Livewire::test(\Filament\Livewire\Topbar::class)` com o painel `app` corrente e a organização fixada por `Filament::setTenant($org)`, afirmando o par da organização no HTML renderizado. O POST real fica fora do arnês; mata M31, e o restante do mutante da rota é coberto pelos CT HTTP.

## Setup Global

### Personas
- Pessoa da organização: `usuarioComPapel('panel_user', $organizacao)` (`tests/Pest.php:usuarioComPapel():726`) mais `$usuario->tenants()->attach($organizacao->id)`; para papel numa segunda organização, `papelNaOrganizacao($usuario, 'panel_user', $outra)` (`tests/Pest.php:papelNaOrganizacao():801`).
- Pessoa de duas organizações com acesso aos painéis globais (CT-51, CT-52): `duasOrganizacoes()` (`tests/Pest.php:duasOrganizacoes():743`), que já dá `admin` global; `/infra` exige o papel de infraestrutura (`usuarioDoKit()`, `tests/Pest.php:usuarioDoKit():492`, ou o que o arquivo de teste irmão do painel usa).
- Pessoa sem vínculo com a Globex (CT-50): `usuarioComPapel('panel_user', $acme)` com `attach` só da Acme.
- `master_global` sem vínculo com a Globex (CT-56): `usuarioDoKit('master_global')` (`tests/Pest.php:usuarioDoKit():492`), sem `attach`.

### Fixtures
- Organizações: `tenant('Acme', 'acme')` (`tests/Pest.php:tenant():387`, sem logo) e `Tenant::factory()->comIdentidadeVisual(cor, logo, paleta, logoEscura)->create([...])` — a factory ganha `?string $logoEscura = null` no fim (passo 3 do `01`, que o passo 4 só consome depois). Para colunas em branco (`''`) e órfãs (coluna preenchida, arquivo ausente do disco): `create(['logo_dark' => ''])` e `put` seletivo no disco fake.
- Identidade da instalação: `gravarConfiguracao()` (`tests/Pest.php:gravarConfiguracao():349`) + `alinharConfiguracoesDoKit()` (`tests/Pest.php:alinharConfiguracoesDoKit():370`), pela cadeia real settings → config. `gravarLogoDaInstalacaoDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:gravarLogoDaInstalacaoDaTenancia():56`) grava a clara e a marca **unificada**; o passo 4 do `01` a estende com `bool $unifica = true` para a marca separada — com `false` ela também grava a escura da instalação (`kit/logo-dark-ct.png`). Com `true` a escura da instalação também precisa existir (inerte) nos cenários de marca unificada (CT-48).
- Composição: `gravarCabecalhoDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:gravarCabecalhoDaTenancia():41`): `nome_do_projeto` e `nome_do_painel` desligados e `logo_da_marca` ligado para o "só o segmento logo"; todos desligados para a marca simples.
- Extração das imagens: `imagensDaMarcaDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:imagensDaMarcaDaTenancia():76`) devolve só os `src` das `<img class~fi-logo>`; os cenários que distinguem clara de escura precisam da **classe** (`fi-logo-light`/`fi-logo-dark`), então o lote D0 a estende (classe → `src`) sem mudar o retorno para os chamadores existentes, ou acrescenta um irmão.
- Região do cabeçalho: `regiaoDoHeader($html, 'kit-cabecalho')` (`tests/Pest.php:regiaoDoHeader():1739`) — todo oráculo da composição é **dentro** da região; o oráculo "nenhum `fi-logo-dark` fora da composição" (CT-45) compara a contagem na página inteira com o número de composições (`kit-cabecalho`), com a contagem >= 1 como controle.
- Fronteira de request: `fronteiraDeRequest()` (`tests/Pest.php:fronteiraDeRequest():777`) entre dois requests do mesmo teste (CT-49); **não** chamá-la em CT-51, onde a organização esquecida no gerenciador é justamente o `Dado`. `noPainelDa($organizacao)` (`tests/Pest.php:noPainelDa():427`) arranja o painel `app` corrente e o tenant, como o middleware faria; é o arranjo do CT-53 (junto de `Livewire::test()`).
- URLs de logo: arquivos reais em `Storage::fake('public')` com nomes distintos por dono: instalação `kit/logo-ct.png` e `kit/logo-dark-ct.png`; Acme `organizacoes/logos/acme.png` e `organizacoes/logos/acme-dark.png`; Globex `organizacoes/logos/globex.png` e `organizacoes/logos/globex-dark.png`. Nome distinto é o que torna a ausência observável por `assertStringNotContainsString`.

### Fakes
- `Storage::fake('public')` em todo cenário de backend. **No navegador o disco `public` é o real** (o navegador faz request HTTP à URL) e a limpeza vai no `afterEach` (ver `05`). Nenhum `Mail`, `Queue`, `Event` nem `Http` fake: a feature não tem efeito colateral.

### Estratégia de DB
- `RefreshDatabase` herdado de `tests/Kit` e de `tests/Tenancy` (`tests/Pest.php`); o `beforeEach` do arquivo de tenancy já semeia `ShieldPermissionsSeeder` e `PapeisSeeder`.

### Regra de oráculo de ausência
- Todo `Então` de ausência ("nenhuma imagem aponta para X", "nenhuma `fi-logo-dark`") usa `assertStringNotContainsString` (rule `testes.md`: `not->toContain($x, $msg)` não afirma mensagem), é **sobre a página inteira** quando o defeito pode pôr a imagem fora da região (ADV-17), e vem depois de um `Então` de presença da imagem esperada no mesmo cenário, com o `Dado` declarando que X **existe** no disco e na coluna (ausência num mundo em que X não existiria é falso verde, gate do passo 6, item 7).
- "Nenhuma" no retorno da regra de resolução é `null` estrito (`toBeNull()`), nem string vazia nem URL (ADV-21); "o nome em texto" é afirmado **dentro do elemento da marca** (`div.fi-logo`), não na página (ADV-19).

---

## Regra R1 — o par de logos resolve por variante e de forma independente

> `RQ-05`, `P-03`, `P-04` · perfil **padrão** · técnica: **tabela de decisão** (modo × quatro entradas), com **ausente ≠ vazio ≠ órfão** nas duas variantes; um `Esquema do Cenário` conta como 1

```gherkin
# language: pt
Funcionalidade: A logo da organização, clara e escura, no topo do `/app`

  Regra: o par resolve por variante, e a escura nunca cai na clara

    Esquema do Cenário: [CT-40] o par resolvido para uma organização segue a tabela de decisão por variante
      Dado a marca <modo>
      E a organização com a logo "<org_clara>" e a variante escura "<org_escura>"
      E a instalação com a logo "<inst_clara>" e a variante escura "<inst_escura>"
      E todo caminho preenchido existe no disco público, exceto o marcado "(ausente)"
      Quando o par de logos é resolvido para a organização
      Então a clara é "<clara>"
      E a escura é "<escura>"

      Exemplos:
        | modo      | org_clara    | org_escura   | inst_clara | inst_escura | clara   | escura  | # partição                                            |
        | unificada | A            | B            | C          | D           | A       | nenhuma | unificada ignora a escura da organização             |
        | unificada | vazia        | B            | C          | D           | C       | nenhuma | unificada, só escura na organização                   |
        | separada  | A            | B            | C          | D           | A       | B       | par da organização                                    |
        | separada  | A            | vazia (null) | C          | D           | A       | D       | só clara: escura cai na da instalação                 |
        | separada  | A            | "" (branco)  | C          | D           | A       | D       | branco = null                                         |
        | separada  | A            | B (ausente)  | C          | D           | A       | D       | escura órfã: coluna preenchida, arquivo ausente       |
        | separada  | A (ausente)  | B            | C          | D           | C       | B       | clara órfã: cai na da instalação, escura segue        |
        | separada  | "" (branco)  | B            | C          | D           | C       | B       | clara em branco = null                                |
        | separada  | A            | vazia        | C          | vazia       | A       | nenhuma | queda da escura não vira a clara da organização       |
        | separada  | vazia        | vazia        | C          | D           | C       | D       | sem logo nenhuma: par da instalação                   |
        | separada  | vazia        | B            | C          | D           | C       | B       | só escura: clara da instalação, escura da organização |
        | separada  | vazia        | B            | vazia      | D           | nenhuma | B       | só escura e instalação sem clara                      |
```

> "nenhuma" é `null` estrito: nenhuma URL, nenhuma string vazia e nenhuma segunda imagem. A linha `unificada` com `vazia, B` mata a escura da organização vazando com a marca unificada mesmo quando a clara cai na instalação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | regra "em bloco": a organização com clara usa o par dela (com queda), sem clara usa o par da instalação inteiro (a escura olha a clara) | CT-40 (linhas `vazia, B, C, D`, `A (ausente), B, C, D` e `vazia, B, vazia, D`) | esperado clara "C" e escura "B" (e clara nenhuma e escura "B" na última); o mutante devolve "C"/"D" e nenhuma/"D" |
| M2 | a escura da organização, ausente, cai para a **clara da organização** (`?? urlDaLogo()`) | CT-40 (linhas `A, vazia, C, D` e `A, vazia, C, vazia`) | esperado escura "D" e "nenhuma"; o mutante devolve "A" nas duas |
| M3 | a regra ignora a marca unificada: devolve a escura da organização mesmo unificada | CT-40 (linhas `unificada`) | esperado escura "nenhuma"; o mutante devolve "B" |
| M4 | ordem invertida: a variante da instalação ganha da da organização | CT-40 (linha `separada, A, B, C, D`) | esperado clara "A" e escura "B"; o mutante devolve "C"/"D" |
| M5 | a escura da organização é lida da coluna crua com `asset()` (sem a guarda de existência do disco), então o órfão vira URL quebrada | CT-40 (linha `B (ausente)`) | esperado escura "D" (a da instalação); o mutante devolve a URL de "B" |
| M46 | a clara da organização é lida da coluna crua com `asset()` (sem a guarda de existência do disco): o órfão vira URL quebrada e a queda não acontece *(revisão adversarial, ADV-06)* | CT-40 (linhas `A (ausente)` e `"" (branco)`) | esperado clara "C"; o mutante devolve a URL de "A" (ou a string vazia) |

---

## Regra R2 — sem organização aberta, o par é o da instalação

> `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **EP** (modo da marca)

```gherkin
  Regra: sem organização, a logo da instalação não muda

    Esquema do Cenário: [CT-41] o par resolvido sem organização é o da instalação
      Dado a marca <modo>, com a logo "C" e a variante escura "D" da instalação
      E os dois arquivos existem no disco público
      Quando o par de logos é resolvido sem organização
      Então a clara é "C"
      E a escura é "<escura>"

      Exemplos:
        | modo      | escura  | # partição            |
        | separada  | D       | par da instalação     |
        | unificada | nenhuma | unificada: só a clara |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | sem organização o par volta vazio (clara nenhuma, escura nenhuma) em vez de cair na instalação | CT-41 | clara "C"; o mutante devolve nenhuma |
| M7 | o caminho sem organização ignora a marca unificada e devolve a escura da instalação | CT-41 (linha `unificada`) | esperado escura `null`; o mutante devolve "D" |
| M8 | a resolução não aceita "sem organização" (`Tenant` sem `?`): o `Quando` lança `TypeError` | CT-41 | o par é devolvido; o mutante lança |

---

## Regra R3 — a marca simples do `/app` com organização aberta é o par da organização

> `RQ-05`, `RQ-02`, `P-02`, `P-03` · perfil **padrão** · técnica: **EP** (a organização tem par; não tem logo; tem só a clara; tem só a escura com e sem clara na instalação). Os eixos de `modo × entradas` já estão em R1: aqui só a **ligação** do topo.

```gherkin
  Regra: o topo do /app com organização aberta mostra a logo dela

    Cenário: [CT-42] a marca simples do /app da Acme é o par da organização
      Dado a marca separada, com a logo "kit/logo-ct.png" e a variante escura "kit/logo-dark-ct.png" da instalação
      E a composição do cabeçalho desligada
      E a Acme com a logo "organizacoes/logos/acme.png" e a variante escura "organizacoes/logos/acme-dark.png"
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a imagem da marca com a classe "fi-logo-light" aponta para "organizacoes/logos/acme.png"
      E a imagem da marca com a classe "fi-logo-dark" aponta para "organizacoes/logos/acme-dark.png"
      E nenhuma imagem da marca aponta para "kit/logo-ct.png" nem para "kit/logo-dark-ct.png"

    Esquema do Cenário: [CT-43] a marca simples cai por variante na instalação quando a organização não tem a logo
      Dado a marca separada, com o par "kit/logo-ct.png" e "kit/logo-dark-ct.png" da instalação
      E a composição do cabeçalho desligada
      E a Acme <organizacao>
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a imagem da marca "fi-logo-light" aponta para "<clara>"
      E a imagem da marca "fi-logo-dark" aponta para "<escura>"

      Exemplos:
        | organizacao                                 | clara                       | escura               | # partição         |
        | sem logo nenhuma (colunas em branco)        | kit/logo-ct.png             | kit/logo-dark-ct.png | queda total        |
        | só com a logo "organizacoes/logos/acme.png" | organizacoes/logos/acme.png | kit/logo-dark-ct.png | queda só da escura |

    @premissa
    Cenário: [CT-44] organização só com a variante escura e instalação sem logo clara: a escura aparece no tema escuro e o nome da aplicação no claro
      Dado a marca separada, a instalação sem logo e a composição desligada
      E a Acme só com a variante escura "organizacoes/logos/acme-dark.png"
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a marca tem uma imagem com a classe "fi-logo-dark" que aponta para "organizacoes/logos/acme-dark.png"
      E a página inteira não tem nenhuma imagem com a classe "fi-logo-light"
      E o nome da aplicação aparece em texto dentro do elemento da marca

    Esquema do Cenário: [CT-57] organização só com a variante escura e instalação com a clara: clara da instalação e escura da organização, nas duas formas
      Dado a marca separada, com o par "kit/logo-ct.png" e "kit/logo-dark-ct.png" da instalação
      E a composição do cabeçalho <composicao>
      E a Acme só com a variante escura "organizacoes/logos/acme-dark.png"
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a imagem "fi-logo-light" do topo aponta para "kit/logo-ct.png"
      E a imagem "fi-logo-dark" do topo aponta para "organizacoes/logos/acme-dark.png"
      E nenhuma imagem do topo aponta para "kit/logo-dark-ct.png"

      Exemplos:
        | composicao                          | # partição    |
        | desligada                           | marca simples |
        | ligada, só o segmento logo da marca | composição    |
```

> `@premissa` em CT-44: o `Então` é o comportamento nativo do Filament para marca clara nula com escura preenchida (nome em texto no claro, `<img>` escura no escuro), consequência da linha "só a escura e instalação sem clara" de R1. Não bloqueia: é mecanismo, não decisão nova de requisito (`## Fronteira com o Plano`). CT-57 é a linha irmã **com** clara na instalação: é a que P-03 diz literalmente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M9 | a marca simples continua devolvendo a logo da instalação (a ligação não foi feita) | CT-42 | `fi-logo-light` com "acme.png"; o mutante emite "kit/logo-ct.png" |
| M10 | só a clara foi trocada: a escura da marca simples continua a da instalação (um dos dois ponteiros) | CT-42 | `fi-logo-dark` com "acme-dark.png"; o mutante emite "kit/logo-dark-ct.png" |
| M11 | organização sem logo deixa a marca sem imagem (a queda não chega ao topo): o nome em texto no lugar da instalação | CT-43 (linha `queda total`) | `fi-logo-light` com "kit/logo-ct.png"; o mutante não emite imagem |
| M12 | a escura sem escura da organização cai para a **clara da organização** | CT-43 (linha `só com a logo "acme.png"`) | `fi-logo-dark` com "kit/logo-dark-ct.png"; o mutante emite "acme.png" |
| M13 | a marca simples aplica a regra da composição (escura depende da clara) e descarta a escura da organização quando a clara resolvida é nula | CT-44 | existe `<img class="fi-logo-dark">` com "acme-dark.png"; o mutante não a emite |
| M45 | o topo aplica a regra "em bloco": organização sem clara usa o par inteiro da instalação (a escura da organização some), na marca simples ou na composição *(revisão adversarial, ADV-05)* | CT-57 (as duas linhas) | `fi-logo-dark` com "acme-dark.png" e nenhuma URL "kit/logo-dark-ct.png"; o mutante emite a escura da instalação |

---

## Regra R4 — a composição do cabeçalho com organização aberta

> `RQ-05`, `RQ-02`, `P-02`, `P-03` · perfil **padrão** · técnica: **EP** + rastreio de efeito (exatamente um `fi-logo-dark`)

```gherkin
  Regra: a composição leva o par da organização, uma vez só

    Cenário: [CT-45] a composição do /app da Acme mostra o par da organização, com um só fi-logo-dark
      Dado a marca separada, com a logo "kit/logo-ct.png" e a variante escura "kit/logo-dark-ct.png" da instalação
      E só o segmento logo da marca ligado
      E a Acme com a logo "organizacoes/logos/acme.png" e a variante escura "organizacoes/logos/acme-dark.png"
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a região do cabeçalho tem exatamente uma imagem "fi-logo-light" para "organizacoes/logos/acme.png" e exatamente uma "fi-logo-dark" para "organizacoes/logos/acme-dark.png"
      E a página inteira não tem nenhuma imagem "fi-logo-dark" fora de uma composição
      E nenhuma imagem do topo aponta para "kit/logo-ct.png" nem para "kit/logo-dark-ct.png"

    Cenário: [CT-46] a composição cai no par da instalação quando a organização não tem logo
      Dado a marca separada, com o par "kit/logo-ct.png" e "kit/logo-dark-ct.png" da instalação
      E só o segmento logo da marca ligado, e a Acme sem logo nenhuma
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a região do cabeçalho tem a imagem "fi-logo-light" para "kit/logo-ct.png"
      E a região do cabeçalho tem a imagem "fi-logo-dark" para "kit/logo-dark-ct.png"

    @premissa
    Cenário: [CT-47] a composição descarta a escura da organização quando não há logo clara
      Dado a marca separada, a instalação sem logo e a Acme só com a variante escura "organizacoes/logos/acme-dark.png"
      E o segmento nome do projeto "Projeto Ômega" e o segmento logo da marca ligados
      Quando uma pessoa com papel na Acme abre /app/acme
      Então a região do cabeçalho tem o texto "Projeto Ômega"
      E a região do cabeçalho não tem nenhuma imagem
      E a página inteira não tem nenhuma imagem "fi-logo-dark"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M14 | a composição resolve a logo por `IdentidadeDoKit::logo()` direto (ignora a organização) | CT-45 | `fi-logo-light` na região com "acme.png"; o mutante emite "kit/logo-ct.png" |
| M15 | `marcaEscura()` devolve a escura com a composição ativa: o Filament emite uma segunda `fi-logo-dark` ao lado da composição (duas escuras) | CT-45 | o total de `fi-logo-dark` da página é igual ao número de composições (>= 1); o mutante tem uma a mais por composição |
| M16 | a clara vem da organização e a escura continua a da instalação (um dos dois ponteiros) | CT-45 | `fi-logo-dark` na região com "acme-dark.png"; o mutante emite "kit/logo-dark-ct.png" |
| M17 | a organização sem logo apaga as imagens da composição (a queda não chega ao par da composição) | CT-46 | a região tem `fi-logo-light` para "kit/logo-ct.png" e `fi-logo-dark` para "kit/logo-dark-ct.png"; o mutante tem zero imagens |
| M18 | a composição mostra a escura sozinha quando a clara é nula (a dependência clara→escura some), na região ou ao lado dela | CT-47 | região sem nenhuma `<img>` e página sem `fi-logo-dark`; o mutante tem uma `fi-logo-dark` para "acme-dark.png" (o controle positivo é o texto "Projeto Ômega", que prova a região) |

---

## Regra R5 — com a marca unificada, a escura da organização é ignorada

> `P-04`, `RQ-05` · perfil **padrão** · técnica: **EP** (forma da marca); a tabela de decisão por modo vive em R1

```gherkin
  Regra: marca unificada = só a clara, da organização, nas duas formas

    Esquema do Cenário: [CT-48] a marca unificada mostra só a clara da organização e nenhuma escura
      Dado a marca unificada, com a logo "kit/logo-ct.png" e a variante escura inerte "kit/logo-dark-ct.png" da instalação
      E a composição do cabeçalho <composicao>
      E a Acme com a logo "organizacoes/logos/acme.png" e a variante escura "organizacoes/logos/acme-dark.png"
      Quando uma pessoa com papel na Acme abre /app/acme
      Então o topo tem a imagem da logo "organizacoes/logos/acme.png"
      E a página inteira não tem nenhuma imagem "fi-logo-dark"
      E a página inteira não aponta para "organizacoes/logos/acme-dark.png" nem para "kit/logo-dark-ct.png"

      Exemplos:
        | composicao                          | # partição    |
        | desligada                           | marca simples |
        | ligada, só o segmento logo da marca | composição    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M19 | a marca simples ignora a marca unificada ao resolver a escura da organização | CT-48 (linha `desligada`) | zero `fi-logo-dark` e nenhuma URL "acme-dark.png"; o mutante emite a escura |
| M20 | a composição lê a escura da organização sem consultar a marca unificada | CT-48 (linha `ligada`) | zero `fi-logo-dark` na página; o mutante emite uma dentro da composição |
| M21 | a regra de resolução ignora a marca unificada (o mesmo M3, visto pela ligação do topo) | CT-48 e CT-40 (linhas `unificada`) | a clara da organização presente (controle positivo) e nenhuma escura; o mutante emite "acme-dark.png" |
| M47 | a marca unificada deixa a escura **da instalação** (inerte) vazar pelo topo, na marca simples ou na composição *(revisão adversarial, ADV-08)* | CT-48 (as duas linhas) | a página não aponta para "kit/logo-dark-ct.png" e não tem `fi-logo-dark`; o mutante emite a escura da instalação |

---

## Regra R6 — a organização do topo é a aberta pela rota

> `RQ-05` · perfil **padrão**, escalado para o cruzamento completo (Impacto 3) · técnica: **2-switch de organização** nas duas ordens de vínculo + **EP** (vínculo). Decisão da sessão (ADV-07): a marca segue a organização **aberta pela rota**, com ou sem vínculo formal; a `master_global` sem vínculo que abre `/app/globex` vê a logo da Globex.

```gherkin
  Regra: o topo mostra a logo da organização que a tela mostra, e de nenhuma outra

    Esquema do Cenário: [CT-49] a pessoa de duas organizações vê no /app/acme o par da Acme (ou o da instalação), nunca o da Globex
      Dado a marca separada, a composição <composicao>, e a instalação, a Acme <acme> e a Globex cada uma com par próprio
      E a pessoa vinculada primeiro <primeiro>, que acabou de abrir /app/globex e viu o par da Globex, com a sessão apontando a Globex
      Quando ela abre /app/acme
      Então o topo tem a imagem "fi-logo-light" para "<clara>" e a "fi-logo-dark" para "<escura>"
      E a página inteira não aponta para as logos da Globex

      Exemplos:
        | composicao                          | acme       | primeiro | clara                       | escura                          | # partição                       |
        | ligada, só o segmento logo da marca | com par    | à Globex | organizacoes/logos/acme.png | organizacoes/logos/acme-dark.png | primeira vinculada = Globex      |
        | ligada, só o segmento logo da marca | com par    | à Acme   | organizacoes/logos/acme.png | organizacoes/logos/acme-dark.png | primeira vinculada = Acme (ordem inversa) |
        | desligada                           | com par    | à Globex | organizacoes/logos/acme.png | organizacoes/logos/acme-dark.png | marca simples                    |
        | ligada, só o segmento logo da marca | sem logo   | à Globex | kit/logo-ct.png             | kit/logo-dark-ct.png            | queda: a Acme sem logo não herda o par da Globex |

    Esquema do Cenário: [CT-50] a logo de uma organização só chega a quem a rota deixa entrar nela
      Dado a instalação com a logo "kit/logo-ct.png" e a Globex com a logo "organizacoes/logos/globex.png" e a variante escura "organizacoes/logos/globex-dark.png"
      E uma pessoa com papel na Acme, <vinculo> com a Globex
      Quando ela abre /app/globex
      Então a resposta <globex> a URL "organizacoes/logos/globex.png"
      E a resposta <instalacao> a URL "kit/logo-ct.png"

      Exemplos:
        | vinculo     | globex     | instalacao | # partição                                       |
        | com vínculo | contém     | não contém | controle positivo: a marca é a da organização    |
        | sem vínculo | não contém | não afirma | organização de outro cliente: responde 404, a página traz o nome da aplicação (`sn-brand`) e não contém globex.png *(alterado em 2026-10-07: QA-04)* |

    Cenário: [CT-56] a master_global sem vínculo que abre /app/globex vê o par da Globex
      Dado a marca separada, a composição só com o segmento logo da marca, e a instalação e a Globex cada uma com par próprio
      E uma pessoa com papel master_global, sem vínculo com a Globex
      Quando ela abre /app/globex
      Então a região do cabeçalho tem a imagem "fi-logo-light" para "organizacoes/logos/globex.png" e a "fi-logo-dark" para "organizacoes/logos/globex-dark.png"
      E nenhuma imagem da região aponta para "kit/logo-ct.png" nem para "kit/logo-dark-ct.png"
```

> CT-49 **substitui** o `[CT-07]` de `cabecalho-do-painel` (que afirmava a logo da instalação no `/app/acme`); a substituição tem a aprovação do solicitante no Adendo 1 do `00` (reversão da P-12 só no `/app` com organização aberta, ADR-01). O `[CT-27]` e o `[CT-04]` da ancestral seguem sem alteração (regressão). Na linha `sem vínculo` de CT-50, a página de recusa (404 do Sentinel) **não renderiza a marca do painel** (medido: o nome da aplicação em texto, `sn-brand`, e nenhum `<img>`), então a cláusula sobre a logo da instalação saiu *(alterado em 2026-10-07, RC-01)*: a linha mede o 404, o nome da aplicação na MESMA resposta (controle positivo) e a ausência de `globex.png`; M25 tem matador nessa linha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M22 | a organização do topo é a primeira vinculada da pessoa | CT-49 (linha `à Globex`) | imagens da Acme; o mutante emite "globex.png" (Globex vinculada primeiro) |
| M49 | a organização do topo é a **última** vinculada da pessoa *(revisão adversarial, ADV-01)* | CT-49 (linha `à Acme`) | imagens da Acme; o mutante emite "globex.png" (Globex é a última vinculada) |
| M23 | o par memoizado vaza entre requests (memo por classe, e não por `Request`) | CT-49 | no segundo request as imagens são da Acme; o mutante repete as da Globex, que o primeiro request calculou |
| M50 | a queda da organização sem logo usa o par memoizado do request anterior (a Acme sem logo herda o da Globex) *(revisão adversarial, ADV-10)* | CT-49 (linha `sem logo`) | `fi-logo-light` com "kit/logo-ct.png" e a página sem "globex.png"; o mutante emite "globex.png" |
| M24 | a organização do topo é a da sessão `tenant_corrente`, e não a da rota | CT-53 (linha `Topbar`, sessão apontando a Globex): o `Livewire::test` não passa pelo middleware que regrava a sessão com a organização da rota, então sessão ≠ organização aberta; CT-51 (linha `/app/login`) é o irmão M29 na autenticação | o par devolvido é o da Acme; o mutante emite o da Globex. (Por HTTP em `/app/{slug}` o mutante é equivalente: o middleware regrava `tenant_corrente` antes do render, ver ADV-09) |
| M25 | a organização é resolvida pelo slug da rota sem checar o vínculo da pessoa, e a página de recusa leva a logo | CT-50 (linha `sem vínculo`) | a resposta é 404, traz o nome da aplicação em `sn-brand` e não contém "globex.png"; o mutante abre a página (200) com "globex.png" *(alterado em 2026-10-07, RC-01: a recusa não renderiza marca, ver a nota acima)* |
| M43 | a marca exige vínculo formal na resolução (`$user->tenants()->contains($org)`): a `master_global` sem vínculo cai na instalação *(revisão adversarial, ADV-07)* | CT-56 | `fi-logo-light` com "globex.png"; o mutante emite "kit/logo-ct.png" |

---

## Regra R7 — fora do `/app` com organização, nunca a logo de organização

> `RQ-04`, `RQ-05` · perfil **padrão**, escalado ao cruzamento completo (Impacto 3) · técnica: **EP** (painel corrente × objeto aberto)

```gherkin
  Regra: só o painel com tenancy e uma organização de verdade mostra a logo dela

    Esquema do Cenário: [CT-51] o topo fora do /app com organização é o par da instalação
      Dado a marca separada, com o par "kit/logo-ct.png" e "kit/logo-dark-ct.png" da instalação, e a composição desligada
      E a Acme com o par próprio "organizacoes/logos/acme.png" e "organizacoes/logos/acme-dark.png", <aberta_no_gerenciador>, e a sessão <sessao>
      Quando <pessoa> abre <tela>
      Então a marca tem a imagem "fi-logo-light" para "kit/logo-ct.png" e a "fi-logo-dark" para "kit/logo-dark-ct.png"
      E a página inteira não aponta para "organizacoes/logos/acme.png" nem para "organizacoes/logos/acme-dark.png"

      Exemplos:
        | tela       | pessoa                                   | aberta_no_gerenciador               | sessao                   | # partição                          |
        | /admin     | a pessoa com acesso ao painel            | com a Acme esquecida no gerenciador | sem organização corrente | painel global, org esquecida        |
        | /infra     | a pessoa com acesso ao painel            | com a Acme esquecida no gerenciador | sem organização corrente | outro painel global                 |
        | /app/login | uma visitante                            | sem organização no gerenciador      | apontando a Acme         | autenticação, sessão com org        |
        | /app/password-reset/request | uma visitante | sem organização no gerenciador | apontando a Acme | autenticação, sem organização aberta *(alterado em 2026-10-07: QA-04 — `/app` responde 302 e `/app/new` 404; o kit não tem cadastro de organização pelo /app)* |

    Esquema do Cenário: [CT-52] a marca do topo exige painel com tenancy e um objeto que seja organização
      Dado a marca separada, com o par "C" e "D" da instalação e a Acme com a logo "A" e a variante escura "B"
      E <painel> como painel corrente e <objeto> como o objeto aberto no gerenciador
      Quando a marca do topo é pedida
      Então a logo clara da marca é "<clara>"
      E a logo escura da marca é "<escura>"

      Exemplos:
        | painel          | objeto                         | clara | escura | # partição                         |
        | nenhum          | a Acme                         | C     | D      | sem painel corrente (console, job) |
        | o painel /admin | a Acme                         | C     | D      | painel sem tenancy                 |
        | o painel /app   | a Acme                         | A     | B      | controle positivo                  |
        | o painel /app   | uma pessoa (não é organização) | C     | D      | objeto que não é Tenant            |
```

> Na linha `/app` de CT-51, se a rota redireciona para a organização, a resposta não tem topo para afirmar: o executor mede e, nesse caso, troca a linha por outra rota sem organização que renderize o topo, declarando a troca no `03`. Nunca a deixa passar só pelo redirecionamento.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | a guarda de painel lê `Paineis::correnteOuPadrao()` em vez de `getCurrentPanel()`: sem painel corrente cai no `app`, que tem tenancy | CT-52 (linha `nenhum`) | a clara é "C" e a escura "D"; o mutante devolve "A" e "B" |
| M27 | sem guarda de painel: só `Filament::getTenant()`; a organização esquecida aparece no `/admin` e no `/infra` | CT-51 (linhas `/admin`, `/infra`) e CT-52 (linha `o painel /admin`) | "acme.png" e "acme-dark.png" ausentes e o par da instalação presente (controle positivo); o mutante emite os da Acme |
| M28 | `Filament::getTenant()` sem checar `instanceof Tenant`: o objeto de outro modelo vai à regra | CT-52 (linha `uma pessoa`) | a clara é "C" e a escura "D" e a chamada devolve; o mutante lança `TypeError` |
| M29 | a tela de autenticação do `/app` resolve a organização pela sessão (`tenant_corrente`) | CT-51 (linha `/app/login`) | "acme.png" ausente; o mutante o emite (sessão apontando a Acme, visitante sem organização aberta) |
| M30 | a guarda só vale para `/admin` e esquece o `/infra` (lista de painéis fixa) | CT-51 (linha `/infra`) | "acme.png" ausente no `/infra`; o mutante o emite |
| M44 | a guarda cobre só a clara: a escura da organização esquecida aparece no `/admin` *(revisão adversarial, ADV-04)* | CT-51 (linhas `/admin`, `/infra`) e CT-52 (linha `o painel /admin`, escura) | `fi-logo-dark` com "kit/logo-dark-ct.png" e sem "acme-dark.png"; o mutante emite "acme-dark.png" (escura "B" no CT-52) |
| M51 | o cadastro de organização (`/app/new`) e o `/app` sem slug resolvem a organização pela sessão (`tenant_corrente`) e vazam a logo da Acme *(revisão adversarial, ADV-12)* | CT-51 (linhas `/app/new`, `/app`; **sem matador por HTTP** *(alterado em 2026-10-07)*) | "acme.png" ausente; o mutante o emite. `/app` só redireciona (302) e `/app/new` é 404, sem topo para afirmar: as duas linhas viraram `/app/password-reset/request`, e M51 não tem matador por HTTP (declarado) |

---

## Regra R8 — o redesenho Livewire mantém o par

> `RQ-05` · perfil **padrão** · técnica: **rastreio de efeito** (o redesenho é outra renderização) + EP (componente × sessão); mecanismo decidido por D5 do `01` (Q6)

```gherkin
  Regra: a marca redesenhada pelo componente é a mesma da página, da organização aberta

    Esquema do Cenário: [CT-53] a barra superior e a lateral do /app da Acme, renderizadas pelo componente, devolvem o par da organização
      Dado a marca separada, a composição desligada, a instalação, a Acme e a Globex cada uma com par próprio
      E a pessoa com papel na Acme, com o painel /app corrente, a Acme como organização aberta e a sessão <sessao>
      Quando o componente <componente> é renderizado
      Então a marca devolvida tem a imagem "fi-logo-light" para "organizacoes/logos/acme.png" e a "fi-logo-dark" para "organizacoes/logos/acme-dark.png"
      E nenhuma imagem da marca devolvida aponta para as da instalação nem para as da Globex

      Exemplos:
        | componente                       | sessao                 | # partição                            |
        | `\Filament\Livewire\Topbar`      | sem organização corrente | barra superior                      |
        | `\Filament\Livewire\Sidebar`     | sem organização corrente | barra lateral (a outra que desenha a marca) |
        | `\Filament\Livewire\Topbar`      | apontando a Globex     | duas abas: a sessão é de outra organização |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M31 | a organização só é resolvida com a guarda de painel sobre um painel corrente que o redesenho não reconstrói: a marca cai na instalação | CT-53 | a marca devolvida tem "acme.png"; o mutante tem "kit/logo-ct.png" |
| M32 | a organização é lida do parâmetro da rota (`request()->route('tenant')`), que no update é `livewire/update`: a marca cai na instalação | CT-53 | "acme-dark.png" presente; o mutante emite a escura da instalação (no `Livewire::test` a rota não tem o parâmetro, então o mutante falha o CT; a equivalência com o update real não é provada pelo arnês, D5) |
| M52 | a barra lateral usa outra fonte de organização que a barra superior (a marca é desenhada por dois componentes) *(revisão adversarial, ADV-11)* | CT-53 (linha `Sidebar`) | a marca da lateral tem "acme.png"; o mutante tem "kit/logo-ct.png" ou o da Globex |

---

## Regra R9 — a tela de bloqueio segue com a organização da sessão

> `P-01`, `RQ-04` · perfil **padrão** · técnica: **EP** (fonte da organização: sessão × gerenciador). O par resolvido por variante já é o do CT-16 da ancestral `logo-dark-mode` (regressão, sem alteração).

```gherkin
  Regra: a organização da tela de bloqueio é a da sessão, não a do topo

    Cenário: [CT-54] a tela de bloqueio mostra o par da organização da sessão, e não o de outra aberta no gerenciador
      Dado a marca separada, com o par "kit/logo-ct.png" e "kit/logo-dark-ct.png" da instalação
      E a sessão bloqueada na Acme, que tem par próprio, com a Globex, que também tem, aberta no gerenciador de painéis
      Quando a tela de bloqueio é renderizada
      Então a mídia tem a imagem "fi-logo-light" para "organizacoes/logos/acme.png" e a "fi-logo-dark" para "organizacoes/logos/acme-dark.png"
      E a tela não aponta para as logos da Globex nem para as da instalação
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M33 | a tela de bloqueio passa a usar a organização aberta do topo (a fonte do `/app`): na rota dela não há organização aberta, então cai na instalação | CT-54 | "acme.png" e "acme-dark.png" presentes; o mutante emite o par da instalação |
| M34 | a tela de bloqueio resolve pelo gerenciador quando há organização: a Globex esquecida ganha da sessão | CT-54 | "globex.png" ausente; o mutante o emite |
| M35 | a delegação muda a saída da tela de bloqueio (perde a marca unificada ou a queda por variante) | CT-16 da ancestral `logo-dark-mode` (regressão, sem ID desta wiki) | as cinco linhas do esquema da ancestral; a linha `unificado` espera escura "nenhuma" e o mutante emite a escura |

---

## Regra R10 — o swap visual no navegador

> `RQ-05`, `RQ-02` · perfil **padrão** · técnica: **EP** (forma da marca × sentido da alternância). Gherkin, roteiro, seletores e os mutantes M36 a M39 e M48 estão no `05-casos-de-teste-browser.md` (costura `browser`, CT-B01).

---

## Regra R11 — a documentação deixa de afirmar o que a mudança desmente

> `RQ-01`, `RQ-02` · perfil **mínimo** · técnica: **EP** (idioma). A frase nova da documentação é do PRD e não é oráculo (ver `## Fronteira com o Plano`).

```gherkin
  Regra: a documentação não diz que a logo do topo é sempre a da instalação

    Esquema do Cenário: [CT-55] a documentação do cabeçalho dos painéis não afirma mais o que deixou de valer
      Dado o arquivo de configurações do kit em "<idioma>", lido da árvore do kit
      Quando a seção do cabeçalho dos painéis é lida
      Então a seção fala de "/app" junto de "<organizacao>"
      E ela não contém o termo-chave "<obsoleto>"
      E ela não contém o termo-chave "<obsoleto_bloqueio>"

      Exemplos:
        | idioma | organizacao  | obsoleto                  | obsoleto_bloqueio                                  | # partição |
        | pt     | organização  | sempre a da instalação    | segue aparecendo só na tela de bloqueio            | português  |
        | en     | organisation | always the installation's | keeps showing only on the lock screen              | inglês     |
```

> Os termos-chave são comparados sem a marcação em negrito (`**`) e sem distinção de quebra de linha (o texto das docs quebra linha no meio da frase).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M40 | só a documentação em português é atualizada | CT-55 (linha `en`) | o termo-chave "always the installation's" ausente; o mutante o mantém |
| M41 | a frase da composição é reescrita e a de "só na tela de bloqueio" fica | CT-55 (as duas linhas) | os dois termos-chave ausentes; o mutante mantém um |
| M42 | a seção é apagada em vez de corrigida | CT-55 | a seção fala de "/app" junto de "organização"; o mutante não a tem |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal (organização de outro cliente) | CT-50 (linha `sem vínculo`, escrito como se o middleware do painel não barrasse), CT-49, CT-56 (a rota decide, com ou sem vínculo) | G3 |
| Autorização exercida na ação (não só `can()`) | não se aplica: nenhuma ação, policy ou permissão nova (`## Autorização` do `01`: "nenhum novo"); a barreira de vínculo é a do painel, exercida por CT-50 | — |
| Idempotência | não se aplica: a entrega só lê; nenhuma escrita | — |
| Concorrência | não se aplica: sem contador nem saldo; o estado compartilhado entre requests (memo) é CT-49 | — |
| Fronteira no ponto de entrada (gravação) | não se aplica: nenhum campo gravado por esta entrega (o upload da logo existe e não muda) | — |
| Domínio condicionado (modo × variante) | CT-40 (a escura depende do modo; as linhas `unificada`), CT-48 | G1 |
| Estado × operação de escrita | não se aplica: nenhuma entidade com ciclo de vida, nenhuma escrita | — |
| Ausente ≠ null ≠ vazio | CT-40 (linhas `vazia (null)`, `"" (branco)` e `(ausente)`, nas duas variantes: três casos, com a mesma queda declarada) | G1 |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: nada depende de data ou hora | — |
| Unicode / limite de varchar | não se aplica: o nome da organização na composição é da `cabecalho-do-painel`; esta entrega só troca imagem | — |
| Unicidade + soft delete | não se aplica: nenhuma unicidade | — |
| Entidade removível ou desativável (organização inativa) | lacuna declarada: a organização inativa é barrada pelo middleware do painel antes de a marca ser desenhada (`LinkDoPainelDaOrganizacaoTest`, casos `sem_vinculo` e `ativo: false`); tentado: linha `inativa` em CT-50, mas o status e o destino da barreira são de feature existente e o `Então` de ausência da URL já vale pelas duas linhas; sem cenário próprio | G3 |
| CRUD combinado | não se aplica | — |
| Mass assignment | não se aplica: nenhum formulário novo; `logo_dark` já está no `$fillable` desde a ancestral | — |
| Upload | não se aplica: o upload não muda (só o texto de ajuda: `## Cogitado e Cortado`) | — |
| Precisão monetária | não se aplica | — |
| **Superfície Livewire** (método público, propriedade pública, estado do framework) | CT-53 (renderização da barra superior e da lateral pelo componente; a marca é desenhada pelos componentes `Topbar`/`Sidebar` do Filament); nenhum `public function` nem `public $` novo (`02`: "Superfície Livewire não exigida") | G4 |
| **Estado do framework usado sem validar** | CT-52 (o objeto aberto no gerenciador, sem checagem de tipo, vira argumento da regra: linha `uma pessoa`), CT-53 (sessão apontando outra organização) | G3, G4 |
| **IDOR por entidade** (uma linha por tabela persistida) | `tenants`: CT-50, CT-56. Nenhuma tabela nova | G3 |
| **Escopo com discriminante nulo** (fecha ou abre?) | CT-41 e CT-52 (linhas `nenhum`), CT-51 (`/app/password-reset/request`: sem organização aberta): organização nula **fecha** na instalação; nunca abre a de outra | G1, G3 |
| **Saída do estado de erro** (4xx/redirect tem destino) | o único `Então` 4xx desta wiki é o 404 do CT-50 (recusa de organização sem vínculo); nenhum é 5xx ou redirect | — |
| **Afirmação negativa que dispensa controle** ("a organização aberta já vem validada pelo painel") | CT-50 (linha `sem vínculo`, escrita como se a negativa fosse falsa) e CT-52 (linha `uma pessoa`); evidência do vendor: `vendor/filament/filament/src/FilamentManager.php:getTenant:448` devolve `$this->tenant` sem olhar o painel, e `vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:hasTenancy:207` diz se o painel tem organização | G3 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-40 | par resolvido para uma organização segue a tabela de decisão por variante | R1 | tabela de decisão | G1 | unit de regra | `tests/Kit/LogoDarkModeTest.php` | M1, M2, M3, M4, M5, M46 |
| CT-41 | par resolvido sem organização é o da instalação | R2 | EP | G1 | unit de regra | `tests/Kit/LogoDarkModeTest.php` | M6, M7, M8 |
| CT-42 | marca simples do /app da Acme é o par da organização | R3 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M9, M10 |
| CT-43 | marca simples cai por variante na instalação | R3 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M11, M12 |
| CT-44 | organização só com a escura e instalação sem clara, na marca simples | R3 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M13 |
| CT-45 | composição mostra o par da organização, com um só fi-logo-dark | R4 | EP + rastreio | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M14, M15, M16 |
| CT-46 | composição cai no par da instalação | R4 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M17 |
| CT-47 | composição descarta a escura sem clara | R4 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M18 |
| CT-48 | marca unificada mostra só a clara da organização | R5 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M19, M20, M21, M47 |
| CT-49 | duas organizações nas duas ordens: o /app/acme mostra o par da Acme ou o da instalação (substitui o CT-07 de `cabecalho-do-painel`) | R6 | 2-switch | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M22, M23, M49, M50 |
| CT-50 | a logo de uma organização só chega a quem a rota deixa entrar | R6 | EP (vínculo) | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M25 |
| CT-51 | topo fora do /app com organização é o par da instalação | R7 | EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M27, M29, M30, M44, M51 |
| CT-52 | marca do topo exige painel com tenancy e objeto que seja organização | R7 | EP | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M26, M27, M28, M44 |
| CT-53 | barras superior e lateral renderizadas pelo componente devolvem o par da organização | R8 | rastreio de efeito + EP | G4 | componente Livewire/Filament | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M24, M31, M32, M52 |
| CT-54 | tela de bloqueio usa a organização da sessão | R9 | EP | G5 | Pest feature HTTP | `tests/Kit/LogoDarkModeTest.php` | M33, M34 |
| CT-55 | documentação pt e en não afirma mais o obsoleto | R11 | EP | G7 | unit de regra | `tests/Kit/LogoDarkModeTest.php` | M40, M41, M42 |
| CT-56 | master_global sem vínculo abre /app/globex e vê o par da Globex | R6 | EP (vínculo) | G3 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M43 |
| CT-57 | organização só com a escura e instalação com a clara, nas duas formas | R3, R4 | EP | G2 | Pest feature HTTP | `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | M45 |
| CT-B01 | topo do /app da Acme troca a imagem visível com o tema, alternado sem recarregar | R10 | EP | G6 | browser | `tests/BrowserTenancy/IdentidadeVisualTest.php` | M36, M37, M38, M39, M48 (no `05`) |

### Regressão (casos de outras wikis que esta entrega obriga a manter verdes, sem ID desta wiki)

| Caso da ancestral | Wiki | Por que é regressão desta entrega |
|---|---|---|
| `[CT-16]` esquema da tela de bloqueio por variante | `feat/logo-dark-mode` | a tela de bloqueio troca a implementação (delega à regra única) e **não** muda a saída (P-01); mata M35 |
| `[CT-27]` marca de fábrica com organização ativa | `feat/cabecalho-do-painel` | organização **sem logo** continua mostrando a da instalação e nada de composição (RQ-04, queda total); não é o controle de nenhuma queda de R1 |
| `[CT-04]` o segmento do painel mostra a organização aberta | `feat/cabecalho-do-painel` | a organização aberta continua a mesma fonte do nome do segmento |
| `[CT-B01]` (logo-dark-mode) e `[CT-B02]` (cabecalho-do-painel) no navegador | ambas | swap sem organização e da composição no `/admin`: a logo da instalação (RQ-04) não muda |
| `CabecalhoDoPainelTest`, `CabecalhoDoPainelTelaTest` | `feat/cabecalho-do-painel` | composição sem tenancy não muda (P-02) |

### Caso substituído

| Caso substituído | Wiki | Substituído por | Aprovação |
|---|---|---|---|
| `[CT-07]` "no /app da Acme, a composição mostra a logo da instalação e nenhuma logo de organização" | `feat/cabecalho-do-painel` | **CT-49** desta wiki | Adendo 1 do `00` (reversão da P-12 só no `/app` com organização aberta; ADR-01). O `04` da ancestral não é editado |

### Colisão de IDs

Decidido pela sessão em 2026-10-07: esta wiki é numerada a partir de **CT-40** (CT-40 a CT-57; os CT-01 a CT-16 do primeiro rascunho foram renumerados em todo o `04`, nos mutantes e no `03`), para ficar ortogonal às ancestrais que vivem nos mesmos arquivos de teste: `tests/Kit/LogoDarkModeTest.php` tem CT-01 a CT-20 (`logo-dark-mode`) e `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` tem CT-04, CT-07, CT-11, CT-15, CT-27, CT-29 a CT-31 (`cabecalho-do-painel`, que vai até CT-35). O `ids-ct.sh` desta wiki sobre esses arquivos continua acusando os `[CT-nn]` da ancestral que não são desta: é esperado, e a sessão o registra no `03` em vez de tentar silenciá-lo. O CT-B fica **CT-B01**: `tests/BrowserTenancy/IdentidadeVisualTest.php` não tem nenhum `[CT-B01]` (só a menção `CT-B01`, sem colchetes, num comentário de uma ancestral).

## Revisão Adversarial

Rodada única, 2026-10-07: **26 achados** (ADV-01 a ADV-26), 6 altos. Aplicados 23, recusados 3 com motivo. Os mutantes trazidos pela revisão (M43, M44, M45, M46, M47, M48, M49, M50, M51, M52) não contam para o teto da regra: são achado medido.

| Achado | Destino |
|---|---|
| ADV-01 | CT-49 ganha a ordem inversa de vínculo (linha `à Acme`); M49 |
| ADV-02 | CT-49 ganha a coluna `composicao` (linha `desligada`: marca simples) e a página inteira sem URL da Globex |
| ADV-03, ADV-22, ADV-23 | CT-B01 reescrito: alternância do tema pelo seletor, sem recarregar, nos dois sentidos; M48 (no `05`) |
| ADV-04, ADV-13, ADV-14 | CT-51 grava e afirma o par inteiro (inclusive `acme-dark`); CT-52 ganha a coluna `escura`; M44 |
| ADV-05 | CT-57 (só a escura com clara na instalação, nas duas formas); M45 |
| ADV-06 | CT-40 ganha linhas de clara órfã e em branco; M46 |
| ADV-07 | R6 reescrita ("aberta pela rota"); CT-56 com `master_global`; M43 |
| ADV-08 | CT-48 grava a escura da instalação (inerte) e a afirma ausente; M47 |
| ADV-09, ADV-16 | M24 morto por CT-53 (linha com sessão apontando a Globex); CT-49 ganha o controle positivo da Globex no primeiro request |
| ADV-10 | CT-49 ganha a linha `sem logo` (a Acme não herda o par da Globex); M50 |
| ADV-11 | CT-53 ganha a linha `Sidebar`; M52 |
| ADV-12 | CT-51 ganha as linhas `/app/new` e `/app`; M51 |
| ADV-15 | CT-50: marca da instalação na mesma resposta recusada; M25 condicionado à página de recusa renderizar marca |
| ADV-17, ADV-19, ADV-21 | oráculos endurecidos (página inteira sem `fi-logo-dark`; nome dentro do elemento da marca; `null` estrito), ver `### Regra de oráculo de ausência` |
| ADV-20 | CT-55 por termo-chave obsoleto e presença de "/app" junto de "organização" |
| ADV-24 | P-05 do `00` reescrita (decorrente, não exigida, não testada); tirada a menção "fora desta entrega" no `04` |
| **ADV-18 (recusado)** | objeto não-organização com atributo `logo`: a assinatura tipada `?Tenant` e o `instanceof` já são exercidos por CT-52 linha `uma pessoa`; o cenário seria artificial |
| **ADV-25 (recusado)** | `alt` das imagens: fora de escopo por P-06 |
| **ADV-26 (recusado)** | desvínculo com aba aberta: fora de escopo, sem `RQ` |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| teste da `TenantFactory::comIdentidadeVisual()` aceitando a escura (L3) | é teste do teste: a factory é provada por uso em CT-42 a CT-57, e a escura gravada é o que CT-40 e CT-49 leem |
| o `helperText` do upload `logo` do `TenantForm` (L4) | texto de ajuda de formulário: não mata nenhum mutante de comportamento; caso de texto de UI sem requisito vira teste do PRD |
| a escura da organização visível no cadastro `/admin/organizacoes` (L7) | fora de escopo (opção (b)/(c) da Q1 não escolhida): sugestão ao solicitante, sem cenário |
| seletor de organizações do `/app` com logo | o seletor usa avatar de iniciais e não consulta a logo; sem organização aberta não há logo de organização (RQ-04; coberto pelas linhas `nenhum` de CT-52 e por CT-51) |
| a organização inativa (linha em CT-50) | o status da barreira é de feature existente; declarado no checklist |
| a mesma tabela de decisão de R1 repetida nas formas de renderização (HTTP) | mata os mesmos mutantes de CT-40, mais caro; as formas só precisam da ligação (R3 a R5, CT-57) |
| segundo CT-B com erro visível (teto do padrão: 1 happy path) | os mutantes de erro (M9 a M13) morrem em HTTP |
| CT-B só da composição e CT-B só da marca simples | um `Esquema do Cenário` (conta 1) cobre as duas formas e os dois sentidos da alternância, que têm CSS diferente (a `.fi-logo-dark` do Filament × a do kit) |
| POST real ao endpoint de update do Livewire para o redesenho | fora do arnês (D5); `Livewire::test` com o tenant fixado prova o par renderizado |
| objeto aberto que não é organização mas tem um atributo `logo` (ADV-18) | recusado: ver `## Revisão Adversarial` |
| o `alt` das imagens (P-06; ADV-25) e o desvínculo com aba aberta (ADV-26) | fora de escopo, ver `## Revisão Adversarial` |
