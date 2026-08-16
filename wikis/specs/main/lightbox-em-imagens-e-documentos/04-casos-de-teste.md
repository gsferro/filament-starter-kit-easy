# Casos de Teste — Lightbox em imagens e documentos

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela ainda não existe.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — exibição de mídia em tabela (avatar, logo) | 2 — integra com componente existente (`ImageColumn`) e com macro registrado por plugin | 2 — tela de listagem central do `/admin` e do `/app`; falha derruba a página inteira, não o card | 4 | **padrão** |
| A2 — documentação e dependência declarada | 1 | 1 | 1 | **mínimo** |

- Técnicas aplicadas: **EP** (com mídia × sem mídia), **rastreio de efeito** (o gatilho do lightbox no HTML), **matriz painel × macro**
- Cenários: 6 (5 no `04`, 1 no `05`) · Regras: 4 · Mutantes previstos: 11 · Sem matador: 2 (declarados)

> **Divergência declarada — Project Rule vence a skill.** A skill sugere `pest --parallel --tia`
> como padrão de execução. `.ai/rules/testes-browser.md` mediu que `--parallel` derruba 4 dos 11
> cenários de browser e que, sem PCOV, o `--tia` não termina (abortado após 35 min). Vale a rule:
> `vendor/bin/pest --parallel --group=kit` para o backend e `vendor/bin/pest --testsuite=Browser`
> em série para os CT-B, em dois comandos.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | nenhum artefato novo: 3 colunas em tabelas existentes + 3 linhas de plugin nos `PanelProvider`. Sem model, migration, job, policy ou command | CT-03 |
| **F** | uma função só: **exibir mídia ampliável**. Sem cálculo, sem fluxo, sem função administrativa escondida | CT-01, CT-02 |
| **D** | `users.avatar_url` (nullable) e `tenants.logo` (nullable). Cardinalidade relevante: **registro com mídia × registro sem mídia**, e a listagem mistura os dois na mesma página. Sem dado de outro tenant em jogo — a coluna só reexibe o que a query da tela já trouxe | CT-01, CT-02, CT-04 |
| **I** | uma interface só: a **tabela renderizada** nos três painéis. Sem rota nova, sem comando, sem webhook, sem import | CT-01, CT-02 |
| **P** | **navegador** — o lightbox é JS (`fslightbox`), criado em runtime. **Disk `public`** — a URL da miniatura depende do disk certo. Sem dependência de versão de banco ou de colação | CT-01, CT-B01 |
| **O** | três perfis abrem as telas: `master_global`, papel de painel (`admin`/`infra`) e `panel_user` no `/app`. A coluna **não** muda de comportamento por perfil — quem vê a linha vê a mídia dela | não se aplica: a autorização é a da tela, já coberta por `tests/Kit/PaginasInfraTest.php` e `tests/Kit/PaineisTest.php` |
| **T** | não se aplica: nada expira, nada é agendado, nada depende de ordem. O único efeito temporal possível — arquivo apagado do disco depois de gravado — foi decidido como **não tratado** (ADR-05) | não se aplica, por decisão registrada |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a coluna de mídia entrega o gatilho do lightbox junto da miniatura | A1 (padrão) | RQ-02, RQ-08, RQ-09 | EP + rastreio de efeito | CT-01, CT-02 |
| **R2** — registro sem mídia não oferece miniatura nem gatilho | A1 (padrão) | RQ-10 | EP (partição vazia) | CT-04 |
| **R3** — o macro `simpleLightbox()` existe em todo painel do kit | A1 (padrão) | RQ-01, RQ-04 | matriz painel × macro | CT-03 |
| **R4** — o lightbox abre de fato sobre a imagem clicada | A1 (padrão) | RQ-02 | rastreio de efeito no navegador | CT-B01 |

**Regras que o requisito gera e que não viram cenário:**

| `RQ` | Por que não há cenário |
|---|---|
| RQ-03 (documento) | **lacuna declarada por escopo**: não existe coluna de documento no kit, e o `00-requisito.md` registra que criar uma só para exercitar o pacote está fora de escopo. O cenário é **inexpressável**, não esquecido. Vira pergunta (abaixo) |
| RQ-05, RQ-06 (documentação, README) | não se aplica: prosa em markdown. Um teste que faz `str_contains(file_get_contents('README.md'), 'simplelightbox')` verifica que a string existe, não que a documentação está correta — é tautológico e quebra em toda reescrita. Verificação por revisão, item da Verificação Final do PRD |
| RQ-07 (avaliar necessidade de teste) | é este arquivo |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `ImageColumn::make('avatar_url')` / `('logo')` | escolha de implementação — o requisito diz "exibir o avatar", não qual componente | detalhe do cenário |
| `->circular()` no avatar e `->size(40)` na logo | escolha de apresentação que só o PRD determina | detalhe; **nenhum `Então` afirma forma ou tamanho** |
| a coluna ser a **primeira** da tabela | idem | detalhe |
| `disk('public')` | **aceito como oráculo parcial**: o requisito diz "se tiver sido feito o upload correspondente", e o upload correspondente (Breezy, `TenantForm`) grava no disk `public`. A URL apontar para a mídia que foi enviada é comportamento, não implementação | CT-01 afirma que a URL da miniatura aponta para o caminho gravado |
| a string `SimpleLightBox.open` no `x-on:click` | **aceito como oráculo**, com ressalva: é o contrato do pacote (`SimpleLightBoxPlugin.php`), não do PRD. Se o pacote mudar o nome do gatilho num upgrade, o CT fica vermelho — e é isso que se quer, porque o clique teria parado de funcionar em silêncio | CT-01, CT-02 |

**Perguntas para o `00-requisito.md`** (replicar em `## Ambiguidades`):

- **RQ-03** — o requisito manda usar lightbox "sempre que … for colocado um documento na table", e o kit não tem nenhuma coluna de documento. A ausência de cenário é consequência de uma **decisão de escopo**, não do conjunto de testes. Confirmar: (a) fica só como convenção documentada, como assumido; ou (b) alguma entidade do kit deve ganhar campo de anexo nesta entrega?
- **RQ-02 + ADR-03** — para documento, o pacote envia a URL a `docs.google.com` / `view.officeapps.live.com`. A convenção adotada restringe o uso a arquivo público e não sensível. Confirmar que essa restrição é aceitável como leitura de "sempre".

## Setup Global

### Personas

- **`master_global`** — `usuarioDoKit('master_global')` (helper de `tests/Pest.php`). Vence pelo `Gate::before` sem depender da matriz de permissões; é a persona certa para cenários que **não** são sobre autorização.
- **`panel_user` numa organização** — `usuarioComPapel('panel_user', $tenant)` + `noPainelDa($tenant)`, para o cenário do `/app`.

> Seeders: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach`, como em `tests/Kit/PaginasInfraTest.php:20-22`. Papel sem a matriz do Shield abre painel e não abre tela.

### Fixtures

- **Usuário com avatar**: `User::create([...])` + `avatar_url` preenchido com um caminho relativo (`avatars/foto.png`). **Não é preciso arquivo real no disco** — a `ImageColumn` monta a URL a partir da coluna e do disk, sem tocar o storage (é exatamente o comportamento aceito no ADR-05).
- **Usuário sem avatar**: `usuario()` puro — `avatar_url` nulo.
- **Organização com logo**: `tenant('Acme', 'acme')` + `logo` = `organizacoes/logos/acme.png`.
- **Organização sem logo**: `tenant('Globex', 'globex')`.

> Não existe factory com estado de avatar hoje. Se `User::factory()` ganhar um state `comAvatar()` na implementação, os cenários passam a usá-lo — o CT afirma o **estado**, não o caminho para produzi-lo.

### Fakes

Nenhum. Não há e-mail, job, evento ou HTTP nesta feature.

### Estratégia de DB

`RefreshDatabase` global, aplicado por `tests/Pest.php` a `Kit`, `Tenancy`, `Browser` e `BrowserTenancy`.

### Onde os arquivos vivem

| Suíte | Arquivo | Por quê |
|---|---|---|
| `tests/Kit` | `LightboxEmTabelaTest.php` | single-tenant; cobre `/admin` e a matriz de painéis |
| `tests/Tenancy` | **acrescentar a um arquivo existente** (`AdminDaOrganizacaoTest.php` ou equivalente) — nunca um arquivo novo para um cenário só | o cenário do `/app` precisa de `permission.teams` ligado desde o `createApplication()`. Corte da auditoria Ponytail: um cenário não justifica arquivo próprio numa suíte que já tem um sobre a mesma tela |
| `tests/Browser` | `LightboxTest.php` | CT-B01 |

> Nenhum helper novo em `tests/Pest.php`: os cenários usam `usuarioDoKit()`, `usuario()`, `tenant()`, `usuarioComPapel()` e `noPainelDa()`, que já existem. Helper usado por um arquivo só permanece no arquivo — regra de `.ai/rules/testes.md`.

---

## Regra R1 — a coluna de mídia entrega o gatilho do lightbox junto da miniatura

> `RQ-02`, `RQ-08`, `RQ-09` · perfil **padrão** · técnicas: **EP** (partição "tem mídia") + **rastreio de efeito** (o gatilho existe no HTML entregue)

```gherkin

# language: pt

Funcionalidade: Ampliar mídia sem sair da listagem

  Regra: quando um registro tem mídia enviada, a listagem entrega a miniatura e o gatilho de ampliação

    Cenário: [CT-01] a listagem de usuários entrega o avatar ampliável
      Dado uma pessoa cujo avatar enviado está gravado em "avatars/foto.png"
      Quando o administrador abre a listagem de usuários do painel de administração
      Então a linha dessa pessoa exibe uma miniatura apontando para "avatars/foto.png"
      E essa miniatura carrega o gatilho de ampliação

    Cenário: [CT-02] a listagem de organizações entrega a logo ampliável
      Dado uma organização cuja logo enviada está gravada em "organizacoes/logos/acme.png"
      Quando o administrador abre a listagem de organizações
      Então a linha dessa organização exibe uma miniatura apontando para "organizacoes/logos/acme.png"
      E essa miniatura carrega o gatilho de ampliação
```

**Camada**: componente Livewire (`Livewire::test(ListUsers::class)` / `ListTenants::class`).

> ⚠️ **Arranjo obrigatório — verificado no vendor, não suposto.**
> `Filament::setCurrentPanel()` **não boota** o painel: ele só troca a propriedade
> `$currentPanel` (`vendor/filament/filament/src/FilamentManager.php:885-892`). Quem chama
> `Panel::boot()` é `Filament::bootCurrentPanel()`, e o único chamador dele em todo o Filament é
> o middleware `SetUpPanel` (`Http/Middleware/SetUpPanel.php:17`) — que um teste de componente
> Livewire **não atravessa**.
>
> Como o macro `simpleLightbox()` é registrado no `boot(Panel $panel)` do plugin, sem o boot
> explícito o cenário estoura `BadMethodCallException` **no arranjo**, sem defeito nenhum no
> código. O arranjo dos cenários de componente é:
>
> ```php
> Filament::setCurrentPanel('admin');
> Filament::bootCurrentPanel();
> ```
>
> O idiom existente do projeto (`tests/Kit/PaginasInfraTest.php:92-98`) usa só
> `setCurrentPanel()` porque nenhuma tela coberta lá depende de macro de plugin. Esta feature é
> a primeira que depende — e essa é exatamente a razão de o CT-03 existir.

**Assertions** (nenhuma delas sozinha é oráculo):

- a URL da mídia — `assertSee('avatars/foto.png')`, provando que o disk resolvido é o do upload
- o gatilho — `assertSee('SimpleLightBox.open', escape: false)`, provando que o macro foi aplicado à coluna e não só que a coluna existe

> Por que **duas** assertions e não `assertOk()`: uma `ImageColumn` sem `->simpleLightbox()` renderiza a miniatura com a URL certa e passa em qualquer verificação de "a tela abriu". O gatilho é o que distingue a coluna com lightbox da coluna sem.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | coluna criada sem `->simpleLightbox()` (a miniatura aparece, o clique não amplia) | CT-01, CT-02 |
| M2 | `disk()` omitido — cai no default `local`, que aponta para `storage/app/private` e não é servível por URL | CT-01, CT-02 (a URL não conteria o caminho público esperado) |
| M3 | coluna aponta para o atributo errado (`avatar` em vez de `avatar_url`; `logo_url` em vez de `logo`) | CT-01, CT-02 |
| M4 | a coluna é acrescentada só no `/admin` e esquecida no `/app` | CT-05 |
| M5 | `->action(fn () => null)` omitido: o clique dispara a ação padrão da linha em vez do lightbox | ⚠️ **sem matador no `04`** — é comportamento de clique, invisível no HTML renderizado. Coberto parcialmente por CT-B01, que prova que o lightbox abre; **não** prova que a ação da linha deixou de disparar. Lacuna declarada: tentado derivar por presença de `wire:click` na célula, mas a ausência dele não distingue os dois casos |

---

## Regra R2 — registro sem mídia não oferece miniatura nem gatilho

> `RQ-10` · perfil **padrão** · técnica: **EP**, partição vazia isolada

```gherkin
  Regra: registro sem mídia enviada não exibe miniatura nem oferece ampliação

    Cenário: [CT-04] pessoa sem avatar não tem o que ampliar
      Dado uma pessoa que nunca enviou avatar
      E uma pessoa cujo avatar está gravado em "avatars/foto.png"
      Quando o administrador abre a listagem de usuários
      Então a listagem exibe as duas pessoas
      E existe exatamente uma miniatura de avatar na página
```

**Camada**: componente Livewire.

**Assertions**: `assertCanSeeTableRecords([$comAvatar, $semAvatar])` **e** contagem de ocorrências do marcador de miniatura no HTML igual a 1.

> Por que a **contagem** e não `assertDontSee`: as duas pessoas estão na mesma página, então o caminho da mídia da primeira aparece no HTML de qualquer jeito. Um `assertDontSee` genérico passaria com um placeholder clicável renderizado para a segunda — que é exatamente o defeito que o RQ-10 proíbe. A contagem é a única assertion que distingue os dois casos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | `->defaultImageUrl('/images/placeholder.png')` acrescentado "para ficar bonito": todo registro passa a ter miniatura clicável, inclusive quem não enviou nada | CT-04 |
| M7 | `->simpleLightbox($url)` chamado **com** argumento e sem `defaultDisplayUrl: false`, aplicando `defaultImageUrl()` com valor não nulo | CT-04 |

---

## Regra R3 — o macro `simpleLightbox()` existe em todo painel do kit

> `RQ-01`, `RQ-04` · perfil **padrão** · técnica: **matriz painel × macro**

O modo de falha que esta regra cobre é o mais caro da entrega: o macro é registrado no `boot()` do plugin, **por painel**. Chamar `->simpleLightbox()` num painel que não registrou o plugin lança `BadMethodCallException` **na renderização da tabela** — não no boot, não no deploy, não em nenhum teste que só verifique configuração.

```gherkin
  Regra: todo painel do kit tem o macro de ampliação disponível para as colunas de tabela

    Esquema do Cenário: [CT-03] o painel oferece o macro de ampliação
      Dado o painel "<painel>" inicializado
      Quando uma coluna de imagem é construída nesse painel
      Então a coluna aceita a chamada de ampliação

      Exemplos:
        | painel | # motivo                                        |
        | admin  | tem mídia hoje (avatar, logo)                   |
        | app    | tem mídia hoje (avatar)                         |
        | infra  | não tem mídia hoje — ADR-02, cobertura preventiva |
```

**Camada**: `Feature` (precisa do container e do registro de painéis; não é cálculo puro).

**Assertions**: para cada painel, `Filament::setCurrentPanel($painel)` + `Filament::bootCurrentPanel()` e então afirmar que `ImageColumn` responde ao macro. A afirmação é sobre a **capacidade**, não sobre uma tela específica.

> **Atenção ao arranjo entre linhas do `Esquema`**: `Panel::boot()` roda uma vez por painel, mas
> o macro é registrado numa classe **estática** (`ImageColumn::macro`). Uma vez registrado por
> qualquer painel, ele permanece no processo. Consequência: se as três linhas rodarem no mesmo
> processo sem isolamento, a segunda e a terceira passam **mesmo que aqueles painéis não
> registrem o plugin** — o cenário viraria falso ✅.
>
> Duas saídas, nesta ordem de preferência:
>
> 1. **Afirmar sobre a configuração do painel, não sobre o macro**: para cada painel, conferir
>    que `Filament::getPanel($id)->getPlugin('filament-simplelightbox')` resolve sem exceção.
>    É determinístico, não depende de ordem de execução, e mata os mesmos mutantes.
> 2. Isolar cada linha em processo próprio — caro e frágil.
>
> **A opção 1 é a especificada.** A opção 2 fica registrada só para explicar por que foi recusada.

> Este é o único cenário do conjunto que existe por causa de uma decisão arquitetural (ADR-02) e não de uma cláusula literal do requisito. Ele é a tradução executável de "a convenção vale em qualquer painel" (RQ-04).

```gherkin
    Cenário: [CT-05] a listagem de usuários da organização também entrega o avatar ampliável
      Dado uma organização com uma pessoa vinculada, cujo avatar está gravado
      Quando o administrador da organização abre a listagem de usuários dela
      Então a linha dessa pessoa exibe a miniatura com o gatilho de ampliação
```

**Camada**: componente Livewire, suíte `tests/Tenancy` (precisa de `permission.teams` ligado desde o `createApplication()`, e de `noPainelDa($tenant)` para o `getEloquentQuery()` não cair no ramo fail-closed).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | plugin registrado só no `/admin` (o painel onde o desenvolvedor testou) | CT-03 (linhas `app` e `infra`), CT-05 |
| M9 | plugin removido de um painel num refactor de `->plugins([...])` | CT-03 |
| M10 | plugin registrado no `/infra` e depois retirado por parecer inútil (é o raciocínio que o ADR-02 antecipa) | CT-03 (linha `infra`) |

---

## Regra R4 — o lightbox abre de fato sobre a imagem clicada

> `RQ-02` · perfil **padrão** · vive no `05-casos-de-teste-browser.md` (CT-B01)

Presença do gatilho no HTML (R1) e execução do gatilho são coisas diferentes: o `x-on:click` pode estar lá e o script do pacote não ter sido publicado por `filament:assets` — caso em que **nada acontece no clique, sem erro nenhum**. Só o navegador distingue.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `php artisan filament:assets` não executado: o JS do pacote não é publicado e o clique é inerte | CT-B01 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a coluna não recebe `{id}` nem consulta registro — reexibe o que a query da tela já autorizou. O recorte por organização do `/app` é do `getEloquentQuery()`, já coberto pela wiki `admin-da-organizacao` |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: não há ação nova. O clique é 100% client-side |
| Idempotência (ancorada no agregado) | **não se aplica**: nenhuma operação de escrita |
| Concorrência | **não se aplica**: sem contador, saldo ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: esta entrega não altera nenhum formulário; os uploads de avatar (Breezy) e logo (`TenantForm`) já existem e não são tocados |
| Domínio condicionado (tipo × valor) | **não se aplica** |
| Estado × operação de escrita | **não se aplica**: entidade sem ciclo de vida nesta feature |
| **Ausente ≠ null ≠ vazio** | CT-04 — e a distinção importa: `avatar_url` nulo e `avatar_url` string vazia precisam se comportar igual. **Lacuna declarada**: o `Esquema do Cenário` cobre só o nulo; a coluna vazia depende de o Breezy conseguir gravar `''`, o que não foi confirmado. Tentado: procurar validação no `HasMyProfile` do vendor. Se a implementação usar `filled()`, os dois casos colapsam |
| Paginação / ordenação | **não se aplica**: nenhuma coluna nova é ordenável ou pesquisável; a paginação da tabela não muda |
| Timezone / DST | **não se aplica**: nada temporal (ver SFDIPOT → T) |
| Unicode / limite de varchar | **não se aplica**: o caminho da mídia é gerado pelo `FileUpload`, não digitado |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum campo novo entra em formulário |
| Upload | **não se aplica nesta entrega**: os dois uploads são pré-existentes. O `TenantForm` já barra SVG (`acceptedFileTypes` explícito, por causa de XSS armazenado) e isso continua coberto por onde já era |
| Precisão monetária | **não se aplica** |
| **Console limpo / JS** | CT-B01 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | avatar ampliável na listagem do `/admin` | R1 | EP + rastreio de efeito | componente Livewire | `tests/Kit/LightboxEmTabelaTest.php` | M1, M2, M3 |
| CT-02 | logo ampliável na listagem de organizações | R1 | EP + rastreio de efeito | componente Livewire | `tests/Kit/LightboxEmTabelaTest.php` | M1, M2, M3 |
| CT-03 | o macro existe nos três painéis | R3 | matriz painel × macro | Feature | `tests/Kit/LightboxEmTabelaTest.php` | M8, M9, M10 |
| CT-04 | pessoa sem avatar não tem o que ampliar | R2 | EP (partição vazia) | componente Livewire | `tests/Kit/LightboxEmTabelaTest.php` | M6, M7 |
| CT-05 | avatar ampliável na listagem do `/app` | R1, R3 | EP | componente Livewire | arquivo existente em `tests/Tenancy/` | M4, M8 |
| CT-B01 | o lightbox abre sobre a imagem clicada | R4 | rastreio de efeito no navegador | Browser | `tests/Browser/LightboxTest.php` | M11 |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| lightbox em `TextColumn` com URL de PDF | RQ-03 está fora de escopo (não há coluna de documento); o cenário seria escrito contra uma tela inexistente |
| verificar que o `README.md` cita o pacote | tautológico — mede a existência de uma string, não a correção da documentação |
| abrir as três telas com `panel_user`, `admin` e `infra` | autorização de tela já é coberta por `tests/Kit/PaineisTest.php` e `PaginasInfraTest.php`; repetir aqui não mata mutante novo |
| conferir que a coluna é a primeira da tabela | ordem de coluna é escolha do PRD, não do requisito (ver `## Fronteira com o Plano`) |
| screenshot da miniatura circular | forma é decisão de apresentação; nenhum `Então` do requisito fala em forma |

## Fechamento com Mutation Testing

Aplicabilidade **baixa** nesta feature, e vale declarar por quê: `pest --mutate` muta código PHP, e a entrega quase não tem lógica PHP — são chamadas encadeadas de configuração de coluna. Os mutantes que importam aqui (M1, M6, M8) são **de especificação**, não de operador: "a chamada não foi feita", "um argumento a mais foi passado", "o plugin não foi registrado". Nenhum deles é gerado por operador de mutação.

Se for rodado assim mesmo, escopar em `--path=app/Filament` e tratar o score como informação sobre o resto do diretório, não sobre esta feature.
