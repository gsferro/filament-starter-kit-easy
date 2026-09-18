# Dossiê dos pacotes — rodada 2

Produto de RQ-01. Dez plugins indicados pelo solicitante, lidos **no código-fonte**, não na
descrição do diretório.

> **Método**: cinco sub-agentes em paralelo, dois pacotes cada (RQ-06). Cada um confirmou o nome
> Composer real no `composer.json` do repositório, a constraint real de `filament/filament`, idade,
> stars, downloads e CI via API do GitHub e do Packagist, e leu os fontes principais via
> `raw.githubusercontent`. Data da coleta: **2026-09-18**.
>
> Os achados que viraram decisão de implementação foram **reverificados no vendor local** antes de
> entrar na wiki — é o que `.ai/rules/specs.md` exige. As reverificações estão marcadas com ✔.

---

## O slug do diretório não é o nome Composer — e nesta rodada os dez divergiram

`wikis/pacotes-candidatos.md` já avisava disso como limite nº 1 do método, e a rodada confirmou em
100% dos casos:

| Slug em `filamentphp.com/plugins/...` | Nome Composer real |
|---|---|
| `pedro-monteiro-page-header` | `mortalkiller/filament-page-header` |
| `jeffersongoncalves-page-visits` | `jeffersongoncalves/filament-page-visits` |
| `jeffersongoncalves-ban` | `jeffersongoncalves/filament-ban` |
| `packstub-flow` | `packstub/filament-flow` |
| `syofyan-zuhad-connection-indicator` | `syofyanzuhad/filament-connection-indicator` |
| `matondo-avatar-picker` | `matondojk/filament-avatar-picker` |
| `vaslv-app-version` | `vaslv/filament-app-version` |
| `cj-ronxel-simple-draft` | `ronssij/filament-simple-draft` |
| `yousef-aman-autosave` | `yousefaman/filament-autosave` |
| `alex-kramarenko-openapi-docs` | `alexkramse/filament-openapi-docs` |

---

## Quadro geral

| Pacote | Versão / data | Idade do repo | ★ | DL/mês | CI | Veredito |
|---|---|---|---|---|---|---|
| `mortalkiller/filament-page-header` | v2.1.4 · 15/09/2026 | 5 dias | 7 | 199 | sim | **ADIAR** |
| `jeffersongoncalves/filament-page-visits` | v3.0.6 · 14/09/2026 | 7 dias | 3 | 233 | sim | **ADIAR** |
| `jeffersongoncalves/filament-ban` | v3.0.3 · 12/09/2026 | 3 meses | 2 | 10 | sim | **RECUSAR** |
| `packstub/filament-flow` | v1.4.1 · 18/09/2026 | 16 dias | 14 | 451 | sim | **ADIAR** |
| `syofyanzuhad/filament-connection-indicator` | v1.0.0 · 12/09/2026 | 6 dias | 1 | 8 | — | **RECUSAR** |
| `matondojk/filament-avatar-picker` | v4.0.1 · 10/09/2026 | 34 dias | 7 | 1.526 | **não** | **RECUSAR** |
| `vaslv/filament-app-version` | v1.2.0 · 14/08/2026 | 1 mês | 2 | 1.354 | sim | **ADIAR** |
| `ronssij/filament-simple-draft` | v1.2.0 · 11/08/2026 | 1 mês | 3 | 2 | **não** | **RECUSAR** |
| `yousefaman/filament-autosave` | v1.0.0 · 27/07/2026 | 2 meses | 3 | 685 | sim | **ADIAR** |
| `alexkramse/filament-openapi-docs` | v1.0.4 · 06/09/2026 | 2 meses | 9 | 266 | **não** | **RECUSAR** |

**Sete dos dez repositórios têm menos de 40 dias de vida. Nenhum passa de 14 stars. Todos têm bus
factor 1.**

---

## 1. `mortalkiller/filament-page-header` — cabeçalho de página rico

**Veredito: ADIAR.**

Cabeçalho de página com avatar, badges, metadados em linha e modo compacto ao rolar. Opt-in em três
níveis (plugin no painel, trait na página, componente no schema), sem migration, sem rota, sem
comando. Zero query nova — o record vem do que a página já carregou. O JS não faz request Livewire
no scroll.

**Fecha lacuna real**: o Filament nativo só dá `getHeading()` + `getSubheading()` + ações, e nenhum
dos pacotes instalados toca o cabeçalho de página. O kit já compensa isso com `getHeaderWidgets()`
em `ViewTenant`.

**Por que fica de fora, em ordem de peso:**

1. **Exige `filament/filament ^4.12.6 || ^5.8.1`** e o kit está em **v5.7.6** ✔ (`composer.lock`).
   Adotar arrasta um upgrade de minor do framework, que é decisão própria. O README declara que a
   faixa `5.0`–`5.8.0` não foi validada.
2. **Repo de 5 dias** (criado 13/09/2026), 8 releases em 3 dias, com **breaking de major em 24 h**
   (`HeaderLayout` → `Header`). A v2.1.4 já traz métodos `@deprecated`.
3. **Ganho por tela, não por painel**: trait + `headerSchema()` por página. E a trait sobrescreve
   `getHeader()` — página que já sobrescreva esse método torna o pacote **inerte em silêncio**.
   Some `php artisan filament:assets` virando passo obrigatório de deploy.

**Gatilho de reabertura**: ≥3 meses sem breaking na série `2.x` **e** o kit já em Filament 5.8+.
O Adendo 1 (`sem travar a versão do Filament`) derruba o motivo 1, mas não os outros dois.

**Alternativa nativa**: header rico *estático* (avatar + título + badges + metadados) sai em ~20-25
linhas com `x-filament-panels::avatar.user` e `x-filament::badge`, sobrescrevendo `getHeader()` — o
kit já tem o padrão em `resources/views/filament/user-menu-header.blade.php`. O que **não** é
replicável barato é o modo compacto (medir altura, transicionar sem reflow, recalcular em `resize`,
`visualViewport` e `livewire:navigated`) — são as 232 linhas do JS dele.

---

## 2. `jeffersongoncalves/filament-page-visits` — analytics de visita

**Veredito: ADIAR.**

São **três** repositórios, não um: `filament-page-visits` → `laravel-page-visits` →
`laravel-visitor-fingerprint`. **Os três foram criados em 11/09/2026** — sete dias.

**A engenharia é boa, e vale dizer:** captura no `terminate()` do middleware (a resposta nunca
atrasa), trabalho pesado num job, falha de tracking nunca vaza, IP só como `sha256` salgado e
truncado, user agent só como hash, retenção e agregação **auto-agendadas**, e uma API de LGPD
explícita (`exportForIp()`/`forgetForIp()`). Postura de privacidade acima da média do ecossistema.

**Por que fica de fora:**

1. **O kit quase não tem rota pública para medir.** `routes/web.php` tem `/`, `/auth/social/*` e
   `/conta-indisponivel`; os painéis vivem em `/app`, `/admin`, `/infra`. O `exclude` default cobre
   `admin/*` e `app/*` mas **não `infra/*`** — numa instalação limpa, a tabela registraria uma
   página pública real e toda a navegação GET do painel de infraestrutura.
2. **`PageVisitResource` não declara `$isScopedToTenant = false`.** Registrar o plugin no `/app`
   (que é `->tenant(Tenant::class)`) faz o Filament instalar o global scope de tenancy num model sem
   relação `tenant()` e estourar `LogicException`. Não há aviso no README. É exatamente a armadilha
   que `.ai/rules/filament.md` documenta.
3. **Widget morto ligado por padrão**: `is_vpn`, `is_proxy`, `is_tor` e `is_datacenter` são gravados
   como `false` literal (`TrackPageVisitJob.php:87-90`), e o `SecurityOverview` calcula quatro
   porcentagens sobre eles — quatro cards exibindo 0,0% sempre.
4. **Bug latente**: `TrackPageVisit.php:82` chama `$request->route()->getName()` **fora** do
   `try/catch`; num 404 que não casou rota, `route()` é `null`.
5. **Um job enfileirado por GET.** Com `QUEUE_CONNECTION=database` (o default do kit), cada visita
   custa três escritas.
6. Cadeia de três pacotes com sete dias de vida e um mantenedor.

**Risco LGPD**: baixo-médio. `ip_hash` é **pseudonimização**, não anonimização — 2³² endereços IPv4
são força-bruta de minutos com o salt conhecido, e o default do salt é a `APP_KEY`. Some
`latitude`/`longitude` com 6 casas, `city`, `isp`, `asn` e `referer_url` de até 2048 caracteres
gravada crua. `forgetForIp()` **não apaga a linha** — anula os campos de IP e deixa
país/cidade/coordenadas/referer.

**Gatilhos de reabertura**: (a) os três repos com ~6 meses e issues de terceiros resolvidas;
(b) `is_vpn`/`is_proxy` deixarem de ser `false` hardcoded; (c) o kit passar a servir páginas
públicas de verdade.

**Alternativa nativa**: enquanto a superfície pública for `/` e `/conta-indisponivel`, ~80 linhas
resolvem — uma tabela de 8 colunas **sem PII nenhuma**, um middleware terminável aplicado só às
rotas públicas por nome (com a guarda de `route() === null` que falta no pacote), `flowframe/laravel-trend`
(já instalado) para o gráfico e `Model::prunable()` para o expurgo.

---

## 3. `jeffersongoncalves/filament-ban` — bloqueio de conta

**Veredito: RECUSAR.**

Wrapper fino (~150 linhas, 7 classes) sobre `cybercog/laravel-ban`, que é maduro (745 mil
downloads, 1.127 stars). Traduções pt-BR de fábrica, namespaces v5 corretos.

**Por que fica de fora:**

1. **O kit já bloqueia conta, por três caminhos, com uma fonte de verdade documentada em ADR**:
   `User::canAccessPanel()`, o `TelaLogin` e o `LoginSocialController`, todos consultando
   `motivoDeIndisponibilidade()`. `ban` acrescentaria um **quarto** estado (`banned_at`) paralelo a
   `ativo`, `deleted_at` e `aprovacao_pendente`.
2. **Não bloqueia nada sozinho.** O `cybercog` traz `ForbidBannedUser` e `LogsOutBannedUser`, e
   **nenhum dos dois pacotes os registra**. Sem middleware colado à mão, banir só pinta um ícone. E
   os middlewares dele não conhecem `canAccessPanel()` — seriam dois sistemas de bloqueio paralelos,
   com mensagens diferentes, uma delas hardcoded em inglês.
3. **A expiração é inerte por padrão**: `isBanned()` só testa `banned_at !== null`; quem lê
   `expired_at` é o comando `ban:delete-expired`, que não é auto-agendado.
4. **Buraco silencioso com o `impersonate` instalado**: `User::canBeImpersonated()` consulta
   `motivoDeIndisponibilidade()` e `aprovacao_pendente` — um usuário `banned_at` mas `ativo = true`
   continuaria personificável.
5. 12 downloads totais, 2 stars.

**O que ele teria de bom, e que virou o item 4 da entrega**: motivo, expiração e **trilha de quem
bloqueou**. A terceira parte era uma lacuna real — e a causa era do kit, não do pacote: ✔
`AuditsFillables::getAuditInclude()` devolve `getFillable()` (`:17`), e `ativo` não é fillable
(`User.php:89-94`), então **desativar conta não entrava em `/infra/audits`**. Corrigido por ADR-05,
sem pacote. Motivo e expiração persistidos ficaram declaradamente fora de escopo.

---

## 4. `packstub/filament-flow` — automação no-code

**Veredito: ADIAR.**

Motor de automação estilo n8n dentro do painel: grafo `nodes`+`edges` num canvas Svelte, runner PHP,
`Trigger → Condition → Action`. **Único no ecossistema com esse escopo para Filament 5.**

**Qualidade acima da média do diretório**: ~238 casos de teste, CI, PHPStan, `svelte-check`,
`vitest`, smoke Playwright, CHANGELOG com nota de upgrade, 15 páginas de documentação. E segurança
pensada, não declarada: `UrlGuard::assertAllowed()` resolve DNS e recusa IP privado/reservado
(proteção SSRF explícita), secrets cifrados e mascarados no log, limites de execução configuráveis.
Multi-tenancy tratada com `MorphTo` que o Filament 5 escopa nativamente.

**Por que fica de fora:**

1. **Superfície desproporcional para um kit redistribuído**: 7 tabelas, 2 rotas fora do painel
   (`POST /flow/webhooks/{workflow}/{token}` público com throttle, `GET /flow/approvals/...`
   assinado), 2 entradas de scheduler (uma a cada minuto), um `Event::listen('*')` em todo evento
   do Laravel, 312 KB de asset, 2 resources + 2 pages + 3 widgets na navegação.
2. **Quem edita um workflow ganha, de fato, poder de chamar URL externa arbitrária, mandar e-mail
   para endereço arbitrário e mutar registros em lote.** Num kit onde o Shield distribui permissões,
   isso é um foot-gun permanente para quem adota.
3. **Duas armadilhas de tenancy documentadas pelo próprio autor**: trigger agendado de um tenant
   **nunca dispara** (o payload de schedule não tem tenant), e workflows globais ficam invisíveis no
   `/app`.
4. `runs.context` e `steps.output` guardam o payload do registro que disparou — inclui PII, com
   retenção default de 30 dias.
5. 16 dias, 14 stars, 451 downloads, bus factor 1.

**Gatilho de reabertura**: ~6 meses de v1.x estável e adoção real de terceiros.

**Alternativa nativa**: não existe equivalente. Se o requisito for *"o operador do painel desenha a
automação sem deploy"*, só o pacote entrega. Se for *"o kit reage a eventos"*, observer + evento +
job já fazem, e o Flow é over-engineering.

---

## 5. `syofyanzuhad/filament-connection-indicator` — indicador de conexão

**Veredito: RECUSAR.**

Dois arquivos PHP, um Blade com Alpine inline. Instala sem mover nenhuma dependência do kit.

**Por que fica de fora — e o motivo principal não é maturidade:**

1. **Mede a coisa errada.** O indicador lê `navigator.onLine` + `navigator.connection`:
   - `navigator.onLine === true` significa "há interface de rede ativa" — fica **verde com o
     servidor fora do ar**;
   - `navigator.connection` **não existe no Safari nem no Firefox**, onde o componente mostra
     verde "Online" sempre.

   Indicador verde durante uma queda de backend é pior que indicador nenhum: dá falsa confiança.
2. **Acessibilidade quebrada**, num kit que acabou de investir nisso (commit `06ba3bc`,
   `feat(a11y)`): raiz é um `<div>` com `@click`, **sem `role`, sem `tabindex`, sem `aria-label`,
   sem `aria-live`**. No estilo default (`dot`), **cor é o único canal de informação** — falha WCAG
   1.4.1. Mudança de online→offline não é anunciada.
3. Cores em hexadecimal fixo, fora dos tokens do Filament — ignora a paleta primária e a cor por
   organização.
4. Sobreposição de espaço com o `pxlrbt/filament-environment-indicator` já instalado, que sinaliza
   algo de fato acionável; e sobreposição funcional com Health e Pulse, que respondem "a aplicação
   está saudável?" com dado de servidor real.
5. 1 star, 8 downloads, 6 dias.

**Alternativa nativa**: ~17 linhas de Blade num render hook reproduzem o default **e** corrigem a
a11y (`role="status"`, `aria-live`, texto além da cor, classes `fi-*` já compiladas). E se a
pergunta real for *"o backend está respondendo?"*, o sinal certo é o estado da conexão do
**Reverb**, que o kit já tem instalado — o pacote não olha para ele.

---

## 6. `matondojk/filament-avatar-picker` — galeria de avatares

**Veredito: RECUSAR — e é a recusa mais dura da rodada.**

É um `Field` que estende `FileUpload` com uma galeria de 73 PNGs. Não gera avatar, não chama serviço
externo (verificado por grep: nenhum `Http::`, `gravatar`, `dicebear`, `ui-avatars`).

**O defeito decisivo — vazamento de foto de perfil entre usuários e organizações:**

Três coisas apontam para o **mesmo diretório** `storage/app/public/avatars`:

1. o ServiceProvider do pacote publica os 73 presets ali;
2. o próprio campo define `->directory('avatars')`;
3. ✔ **o kit já usa esse diretório**: `vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasMyProfile.php:59-63`
   faz `FileUpload::make('avatar_url')->avatar()->disk('public')->directory('avatars')`, e os três
   painéis têm `hasAvatars: true`.

E a galeria lista o diretório **inteiro, sem filtro**:
`Storage::disk('public')->files('avatars')` (`avatar-gallery.blade.php:7`).

Resultado: adotar faria **a foto de perfil de todo usuário virar item de galeria para todos os
outros**, nos três painéis, sem nenhum escopo de organização. O README chama `custom-avatars` de
"diretório isolado e seguro" — o isolamento vale só para o upload feito *dentro* do modal.

**Somam-se:**

- ✔ `license: null` na API do GitHub — MIT declarado no `composer.json` **sem arquivo de licença**.
  Para um kit redistribuído isso é problema jurídico, não detalhe.
- `SECURITY.md` manda reportar vulnerabilidade para `matondo@example.com`.
- Constraint `filament/filament: ^3.0|^4.0|^5.0` é **falsa**: o código importa
  `Filament\Schemas\Components\Tabs`, namespace que não existe na v3 — instala e quebra em runtime.
- 0 testes, 0 CI, 26 releases em 26 dias.
- Implementação frágil por natureza: sequestra o clique do FilePond e auto-clica num botão escondido
  por `getElementById`.
- Conflito de arquitetura com o `spatie-laravel-media-library-plugin` instalado, e `disk('public')`
  hardcoded contra o trabalho de mídia privada que o kit já fez (`KitMidiaPrivada`).

**O que a análise dele rendeu, e vale mais que ele**: a pergunta "de onde vem o avatar de quem não
enviou foto?" ✔ levou ao `UiAvatarsProvider` e ao item 1 desta entrega. Ver ADR-03.

---

## 7. `vaslv/filament-app-version` — versão no painel

**Veredito: ADIAR.**

Qualidade de código notável para a faixa: PHPStan max, 20 arquivos de teste, CI, teste de
arquitetura proibindo `shell_exec`, memoização correta sob Octane, `sr-only` no chip, cores por
`light-dark()` com as variáveis do painel. Zero asset, zero migration.

**Por que fica de fora:**

1. **Exige `php: ^8.4`**; o kit declara `^8.3`. Instalar eleva o piso real sem o `composer.json`
   dizer isso — quem estiver em 8.3 leva erro de resolução.
2. **Não entrega o que foi pedido.** RQ-02 queria a versão pela **tag** ou pela **branch
   `release/<versão>`**. O `GitVersionResolver` devolve **apenas o SHA curto de 7 caracteres** e
   recusa detecção de tag **por desenho** — o docblock dele explica o porquê (a busca reversa
   SHA → tag exige varrer todas as tags, e imagem de produção costuma não ter `.git`). Nome de
   branch ele também descarta, embora abra o arquivo que o contém. Entregar tag ou branch exigiria
   um resolver próprio.
3. 2 stars, repo de um mês, um mês sem commit, nenhum GitHub Release publicado (só tags).

**Alternativa nativa**: ~6 linhas de render hook. E a análise dele produziu o achado que mudou o
desenho: ✔ `config('kit.version')` é a versão **do kit**, não a do produto — o que o Adendo 2
confirmou e a ADR-04 resolveu com `config('app.version')`.

---

## 8. `ronssij/filament-simple-draft` — rascunho manual

**Veredito: RECUSAR.**

**Correção de premissa**: ele **não** resolve "não perder o que foi digitado". É um workflow de
publicação (`published_at IS NULL` = rascunho), com botão manual, gravando o registro real na tabela
real. Não guarda nada em localStorage, cache ou sessão, e não restaura nada.

**O defeito decisivo — fail-open de validação:**

O macro `->draftable()` chama `nullable()` assim:

```php
$this->nullable(function ($livewire) {
    return property_exists($livewire, 'shouldSaveAsDraft')
        ? $livewire->shouldSaveAsDraft
        : true;   // fail-OPEN
});
```

E `nullable()` no Filament é literalmente `->required(fn () => ! $condition)`. Consequência: o mesmo
`form()` do Resource usado num **RelationManager**, num **modal de `CreateAction`** ou num
`ManageRecords` roda num componente sem a propriedade `shouldSaveAsDraft` — e **todos os campos
`->draftable()` ficam com `required` desligado, em silêncio**.

Um starter kit não pode embarcar um pacote cujo modo de falha é validação silenciosamente ausente.

**Somam-se:**

- Reimplementa as form actions e **perde `->submit()` e `->keyBindings(['mod+s'])`** — Enter dentro
  de um input deixa de submeter e Ctrl+S deixa de salvar.
- O global scope usa `config('filament-simple-draft.publishable_column')` enquanto as macros usam a
  coluna do model — o override por model documentado no README quebra a filtragem.
- Quebra recursos aninhados (não trata `getParentRecord()`/`associateRecordWithParent()`).
- `composer.json` declara só `filament/support`, mas o código usa `filament/forms`,
  `filament/actions` e `filament/filament` — funciona por acidente.
- 0 testes, 3 stars, **2 downloads/mês**, docs incompletas (omitem o trait obrigatório no Resource).

**Sobre a visão no Settings que RQ-03 pedia**: **impossível sem fork**. Os três pontos de ativação
(trait no Model, na Page e no Resource) são composição de traits — compile-time. O Settings não
alcança nenhum deles.

---

## 9. `yousefaman/filament-autosave` — salvamento automático

**Veredito: ADIAR. É o melhor pacote da rodada e mesmo assim não entra.**

Resolve de fato o problema. Debounce de 1500 ms sobre `$wire.data`, **sem polling** — formulário
aberto e parado custa **zero request**. No Edit escreve no registro real dentro de transação; no
Create guarda draft em cache com TTL. Nada em localStorage.

**Engenharia defensiva bem acima da média da faixa**: 128 testes e CI de 8 pernas; `#[Locked]` em
toda propriedade pública; `authorizeAccess()` em todo método Livewire exposto; senha e
`TemporaryUploadedFile` removidos **em qualquer profundidade**; chaves injetadas pelo cliente podadas
contra a árvore de campos declarada; chave de cache escopada por **tenant + guard + usuário**, com
session de convidado hasheada; log de falha com **só a classe** da exceção, nunca PII; `tryGet()`
que não derruba painel sem o plugin. README honesto sobre as próprias limitações.

**Por que fica de fora:**

1. **Conflito com a auditoria, e é o decisivo.** O kit tem **cinco models `Auditable`** (`User`,
   `Tenant`, `Projeto`, `Convite`, `AgenteIa`). `handleRecordUpdate()` → `$record->update()` → **uma
   linha em `audits` por pausa de digitação**. Um usuário editando um `Projeto` por dez minutos
   geraria dezenas de revisões com diff de um caractere. A trilha de auditoria — que é feature
   vendida do kit — vira ruído inutilizável, e a tabela cresce sem retenção.
2. **Validação de campo não roda no Edit.** `performAutosave()` nunca chama `$this->form->validate()`;
   só há guardas para `required`-em-branco e `in:options`. Um e-mail meio digitado (`joao@`) **vai
   para o banco**. É design consciente e documentado, mas é o custo real da feature.
3. `getAutosaveFields()` sem memoização, ~5 chamadas por autosave; `getInValidationRule()` faz uma
   query por `Select::relationship()`. 4–8 queries por autosave.
4. Um draft por **classe de página** por usuário: começar o Projeto A, abandonar e começar o B
   devolve o draft de A.
5. Falha silenciosa e ilegível se um `Select` não tem `options()` — o autosave do formulário inteiro
   morre e o log não diz qual campo.
6. v1.0.0, 3 stars, 0 forks, 0 issues — nenhuma validação por terceiros.

**Sobre a visão no Settings que RQ-04 pedia**: **é possível, e o caminho está mapeado** —
`shouldAutosave()` é consultado no servidor a cada mount, então uma allowlist de páginas lida do
`ConfiguracoesDoKit` funciona de verdade. Fica registrada em ADR-02 como caminho de reabertura.

**Gatilho de reabertura**: resolver o que fazer com a auditoria, e começar **apenas pelo modo
Create** (draft em cache, sem write no banco, sem tocar em `audits`).

**Alternativa nativa adotada**: `->unsavedChangesAlerts()`, que ✔ o Filament 5 tem
(`HasUnsavedChangesAlerts.php:11`), nasce `false` (`:9`) e o kit nunca ligou. Cobre o caso mais
comum — fechar a aba, navegar por engano — com custo zero de servidor e zero risco de LGPD. Ver
ADR-02.

---

## 10. `alexkramse/filament-openapi-docs` — documentação OpenAPI no painel

**Veredito: RECUSAR.** Reavaliado uma segunda vez, com hardening completo na mesa, e a recusa se
manteve — **por motivos diferentes dos iniciais**.

**Correção de fato importante**: a primeira leitura tratou "`dedoc/scramble ^0.13` é pré-1.0, logo
instável". **Errado.** O Scramble tem **1,9 milhão de downloads/mês**, 2209 stars, 67 contribuidores,
CI, quatro anos de vida e release no mesmo dia da coleta. O `0.x` é convenção do autor, não
imaturidade. A recusa é do **plugin**, não do motor.

**Os motivos que sobrevivem:**

1. **O badge de navegação gera a spec inteira e não há como desligar.**
   `OpenApiDocsPage.php:109` chama `staticOpenApiData()` **antes** do `match` que lê o modo —
   `->navigationBadge(null)` e `'badge' => null` suprimem só a exibição. E o Filament chama
   `getNavigationBadge()` de forma eager ao montar a navegação. Sem `scramble:cache` no deploy, é
   análise de AST a cada request de painel.
2. **Zero CI.** `.github/workflows` não existe; o badge "tests passing" do README é uma imagem
   estática hardcoded, afirmando um fato que o repositório não comprova. 9 stars, **0 forks**,
   bus factor 1, dois meses.
3. **A página não traz autorização própria** — não sobrescreve `canAccess()`, e o default do
   Filament libera para todo usuário autenticado do painel.
4. **O kit não tem API.** `routes/` só tem `console.php` e `web.php`; `bootstrap/app.php` não declara
   `api:`. A página nasceria vazia.

**Sobre as rotas abertas**: confirmado que `RestrictedDocsAccess` libera `/docs/api` e
`/docs/api.json` **sem autenticação** quando `APP_ENV=local`. Mas — correção — **`Scramble::ignoreDefaultRoutes()`
existe** e as elimina. O risco real não é "o Scramble é inseguro"; é que o **default** dele é
inseguro, e um kit redistribuído publicaria esse default para quem nunca leu o config.

**As alternativas levantadas**, com o motivo de cada uma não servir hoje, estão em ADR-06 — incluindo
duas correções ao próprio ranking do kit: `zpmlabs/api-docs` (posição 70) **não existe** (o nome real
é `zpmlabs/filament-api-docs-builder`, licença `proprietary`, bloqueado por `.ai/rules/general.md`),
e `vyuldashev/laravel-openapi` está morto desde 2023.

**A escolha registrada para quando houver API**: `dedoc/scramble` (gerar) + `scalar/laravel`
(renderizar), fora do painel, com quatro linhas de endurecimento. Ver ADR-06.

---

## O que esta rodada devolveu ao kit

Três defeitos do **próprio kit**, todos encontrados ao ler o código dos pacotes e todos ✔
reverificados no vendor local:

| # | Defeito | Achado ao avaliar |
|---|---|---|
| 1 | Todo usuário sem foto faz o navegador buscar as iniciais em `ui-avatars.com`, levando nome e `Referer` do painel | `matondojk/filament-avatar-picker` |
| 2 | Desativar conta não entra na trilha de `/infra/audits` | `jeffersongoncalves/filament-ban` |
| 3 | Quatro arquivos justificam a escolha de render hook com uma afirmação errada sobre o vendor | `syofyanzuhad/filament-connection-indicator` |

Mais duas lacunas que os pacotes revelaram e que o Filament já resolvia sozinho:
`->unsavedChangesAlerts()` desligado desde sempre, e a ausência de qualquer versão visível na UI.

E duas correções a `wikis/pacotes-ranking.md`: o nome inexistente na posição 70, e
`vyuldashev/laravel-openapi` (não listado, mas candidato óbvio) estar morto.

> É a mesma lição que `wikis/pacotes-candidatos.md` registrou ao adotar o Tier S: *"triagem não
> substitui leitura de código"*. Desta vez a leitura não aprovou nenhum pacote — e ainda assim foi o
> que produziu mais valor para o kit.
