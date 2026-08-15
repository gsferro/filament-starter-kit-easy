# Decisões Arquiteturais — Identidade visual da organização

## ADR-01: Uma cor e uma logo — não um "tema" por organização

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-02 pede *"as imagens, logos, cores personalizados"* — plural nos três. RQ-03 restringe:
*"a principio, vamos deixar ele escolher as cores"*. E RQ-06 exige a logo na lock-screen, o que
tira a logo do "depois".

A tentação é modelar um "tema": tabela `tenant_themes`, ou uma coluna `json` com paleta completa,
cores secundárias, tipografia, banner, favicon.

### Decisão

**Duas colunas na tabela `tenants`**: `cor_primaria` (string 7, hex) e `logo` (string, path no
disk `public`).

Nada de tabela nova, nada de JSON, nada de paleta gravada.

### Alternativas Consideradas

1. **Coluna `json` com o tema inteiro** — descartada por agora, mas é a evolução natural. Hoje ela
   custaria: sem colunas nomeadas, o `ColorPicker` precisaria de `->statePath()` aninhado, o
   `FileUpload` idem, e nenhum índice serve. Ganho zero enquanto há dois campos.
2. **Tabela `tenant_identidades` 1:1** — descartada: relação 1:1 obrigatória é coluna com passos
   extras. Justificaria-se se a identidade visual fosse versionada ou auditada em separado — e ela
   já é auditada, porque `Tenant` usa `AuditsFillables`.
3. **Gravar as 11 shades da paleta** — descartada, e é a mais tentadora das três porque parece
   "pré-calcular". `Color::generatePalette()` (`vendor/filament/support/src/Colors/Color.php:663`)
   deriva as 11 de um hex, e o `ColorManager` já a chama quando recebe string
   (`ColorManager.php:84-85`). Guardar a paleta é guardar dado calculável — e desatualizável, no
   dia em que o Filament ajustar a curva de luminosidade.

### Consequências

- **Positivas**: migration de 2 linhas, form de 2 campos, nenhum código de serialização. A feature
  é **inerte com os campos nulos**, o que a torna segura de mergear antes de qualquer cliente
  preencher.
- **Negativas**: acrescentar banner/favicon/cor secundária exigirá migration nova. Aceito —
  migration de coluna nullable é barata, e RQ-03 diz explicitamente "a princípio".
- **Riscos**: se a próxima evolução pedir 6 campos de uma vez, o JSON passa a valer. O gatilho está
  nomeado: **ao terceiro campo de identidade visual, reavaliar a alternativa 1.**

### Referências

- `00-requisito.md` → Ambiguidades → "RQ-02 × RQ-03"
- `01-plano-acao.md` → passo 1

---

## ADR-02: `FilamentColor::register()` com Closure, nunca `Panel::colors()`

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-05 pede que a identidade visual seja carregada *"ao abrir o painel de app, com o tenant
correspondente"*. Ou seja: a cor depende de um objeto que só existe **durante o request**.

O Filament oferece dois caminhos, e ambos aceitam `Closure` na assinatura — o que faz os dois
parecerem equivalentes. Não são.

### Decisão

**`FilamentColor::register(fn () => [...])`**, chamado no `bootUsing()` do `AppPanelProvider`.

Com guarda dupla: só registra quando o painel corrente é `app` **e** o tenant tem `cor_primaria`.

### Alternativas Consideradas

1. **`$panel->colors(fn () => ['primary' => Filament::getTenant()?->cor_primaria])`** —
   **descartada, e é a armadilha central desta feature.** A assinatura aceita Closure
   (`Panel/Concerns/HasColors.php:17`), mas o `Panel::boot()` faz
   `FilamentColor::register($this->getColors())` (`Panel.php:95`) e o `getColors()` do painel
   **avalia a Closure ali mesmo** (`HasColors.php:31`).

   E o `Panel::boot()` é disparado pelo middleware `panel:{id}` / `SetUpPanel`, que o
   `HasMiddleware.php:97-103` coloca na **primeira posição** da pilha — antes do `IdentifyTenant`,
   que vive no grupo interno de rotas (`vendor/filament/filament/routes/web.php:119`).

   Resultado: `Filament::getTenant()` é **sempre `null`** ali. O código *parece* certo, roda sem
   erro, e simplesmente nunca aplica cor. Falha silenciosa.

2. **Middleware próprio que chama `FilamentColor::register()` com array já resolvido** —
   descartada por ser mais código para o mesmo efeito: o `register()` já aceita Closure e já a
   avalia tarde. Um middleware acrescentaria um arquivo e um ponto de ordenação para não ganhar
   nada.

3. **CSS custom injetado por render hook**, montando as CSS vars à mão — descartada: reimplementaria
   `AssetManager::renderStyles()` (`AssetManager.php:279-305`), que já gera `--{cor}-{shade}` para
   cada cor registrada. Reinventar isso perderia a escolha de shade por contraste WCAG que o
   Filament faz em runtime.

### Consequências

- **Positivas**: 8 linhas no provider que já existe. Zero arquivo novo. A cor entra pelo mesmo
  caminho que a cor default, então todo componente Filament a respeita sem saber da feature.
- **Negativas**: `FilamentColor` é **global, não por painel** — daí a guarda de painel ser
  obrigatória, não defensiva. Sem ela, a cor de um cliente pinta `/admin` e `/infra`. CT-05 e
  CT-B03 existem só para provar o não-vazamento.
- **Riscos**: `ColorManager::getColors()` cacheia em `$cachedColors` (`ColorManager.php:78`). O
  manager é singleton **do container**, que morre no fim do request, e um request tem um tenant só
  — então é seguro. **Premissa a verificar**, não a assumir: CT-B02 confirma que dois tenants
  diferentes recebem cores diferentes.

### Referências

- Doc oficial: <https://filamentphp.com/docs/5.x/styling/colors> (RQ-04) — *"You may also pass a
  callable to `register()` that executes only during rendering, enabling access to request-scoped
  objects"*
- `01-plano-acao.md` → `## Contexto` → tabela de "quando a Closure roda"

---

## ADR-03: O tenant da lock-screen vem da sessão, gravada pelo middleware que já existe

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-06 pede a logo do cliente na lock-screen, com a justificativa *"pois eu já sei em qual tenancy
ele estaria quando usando o painel app"*.

**A justificativa é verdadeira para o usuário e falsa para o framework.** O pacote registra a rota
com o path do painel (`vendor/marjose123/filament-lockscreen/routes/web.php`):

```php
->middleware(...$panel->getMiddleware())   // só o middleware base
->prefix($panel->getPath())                 // 'app', nunca 'app/{tenant}'
```

Então a URL é `/app/screen/lock`, sem segmento de tenant, e o `tenantMiddleware` não roda.

E não há de onde puxar: `FilamentManager::$tenant` é propriedade de instância
(`FilamentManager.php:54`), preenchida **só** pelo `IdentifyTenant` a partir do parâmetro de rota
(`IdentifyTenant.php:27-44`). Varredura de `session()->put` em
`vendor/filament/filament/src/` acha três usos — rehash de senha, filtros de dashboard e código
2FA — **nenhum de tenant**. O kit também não guarda: `DefinirTenantDePermissoes` não toca em
`session()`, e `User` não implementa `HasDefaultTenant`.

### Decisão

Gravar o tenant corrente na sessão **no middleware que já o recebe**:
`DefinirTenantDePermissoes::handle()`, que tem o tenant na linha 33, roda em todo request do `/app`
e é persistente nos AJAX do Livewire (`isPersistent: true`).

Uma linha: `session(['tenant_corrente' => $tenant?->getKey()]);`

A lock-screen lê essa chave e **cai na mídia base** quando ela não resolve.

### Alternativas Consideradas

1. **Implementar `HasDefaultTenant::getDefaultTenant()` no `User`** — descartada por estar
   semanticamente errada. O contrato existe (`Models/Contracts/HasDefaultTenant.php:10`) e é
   consumido em `FilamentManager.php:590-604`, mas entrega o tenant **default**, não o **corrente**.
   Para o usuário com duas organizações — exatamente a persona que a wiki
   `convite-para-usuario-existente` criou — ele mostraria a logo errada. E logo errada é pior que
   logo genérica.

2. **Listener do evento `TenantSet`** — descartada por custo, não por mérito; é a alternativa mais
   bonita. O Filament dispara `TenantSet` em `setTenant()` (`FilamentManager.php:899-906`), e um
   listener seria o gancho oficial. Mas custa arquivo novo + registro, e o `setTenant($tenant,
   isQuiet: true)` **não dispara** o evento — e é essa a forma que os testes de tenancy do kit
   usam (`noPainelDa()` em `tests/Pest.php`). O listener ficaria cego justamente nos testes.

3. **Middleware novo, dedicado** — descartada: um middleware para gravar uma chave de sessão, ao
   lado de um que já tem o valor em mãos, é o arquivo que a escada do Ponytail manda não criar.

4. **Passar o tenant no POST de travar a sessão** — descartada: o `itemDeMenu()` do kit
   (`TelaBloqueio.php:66-75`) sabe o tenant, mas o POST vai para `/app/lock-session` e o controller
   é do vendor (`LockscreenSessionController.php`), que só faz
   `session()->put('lockscreen', true)`. Interceptá-lo exigiria bind de controller — mais
   acoplamento a vendor para o mesmo resultado.

### Consequências

- **Positivas**: uma linha, no lugar exato, sem arquivo novo. Resolve não só a lock-screen mas
  qualquer superfície futura que precise do tenant sem tê-lo na rota.
- **Negativas**: o `DefinirTenantDePermissoes` passa a ter duas responsabilidades, e o nome dele
  só anuncia uma. Mitigado pelo docblock — **renomear tocaria o `AppPanelProvider` e os testes de
  tenancy por ganho cosmético**.
- **Riscos**: sessão compartilhada entre painéis. Se o usuário abre `/app/acme`, trava, e depois
  entra em `/admin`, a chave ainda diz `acme`. Por isso a guarda da lock-screen checa **o painel
  também** — a mesma `TelaBloqueio` serve os três, e `/admin` não deve ver logo de cliente.

### Referências

- `00-requisito.md` → Ambiguidades → "RQ-06"
- `01-plano-acao.md` → passos 7 e 8
- Refine: nenhuma

---

## ADR-04: `mergeWith()` para trocar a mídia, nunca `setPageConfig()` cru

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

`AuthPageConfig::media(?string $media, ?string $alt)` aceita **só string**
(`vendor/caresome/filament-auth-designer/src/Data/AuthPageConfig.php:28`), então não dá para
passar `fn () => $tenant->logo` na configuração do plugin no `AppPanelProvider`.

Mas o config é lido **tarde**: o blade do layout resolve na primeira linha,
`$config = $livewire->getAuthDesignerConfig();`
(`resources/views/components/layouts/auth.blade.php:6`). O repositório é singleton
(`AuthDesignerServiceProvider.php:29`) e `setPageConfig()` é público
(`AuthDesignerConfigRepository.php:40`).

### Decisão

Sobrescrever `getAuthDesignerConfig()` em `TelaBloqueio` — `public` e não-final na trait
(`HasAuthDesignerLayout.php:20`) — e **combinar** o config existente com a mídia da organização
via `mergeWith()` (`AuthPageConfig.php:180`).

### Alternativas Consideradas

1. **`setPageConfig()` com um `AuthPageConfig` novo** — descartada por perda silenciosa. O setter
   **substitui o objeto inteiro, sem merge** (`AuthDesignerConfigRepository.php:42`). O
   `AppPanelProvider` configura `mediaPosition(Left)`, `mediaSize('70%')` e `themeToggle()`
   (`AppPanelProvider.php:123-133`) — todos seriam apagados, e a tela de bloqueio perderia o
   alternador de tema sem nenhum erro. É a mesma classe de defeito que ADR-06 da wiki
   `convite-de-usuario` já documentou para a tela de registro.

2. **`AuthPageConfig::renderHook()`** (`:71-76`) — aceita Closure nativamente, avaliada no render
   (`Data/AuthDesignerConfig.php:85-98`). **Descartada para este caso**, mas registrada porque é a
   via certa para outra coisa: os três pontos disponíveis são `card.before`, `card.after` e
   `media.overlay` (`View/AuthDesignerRenderHook.php:9-13`) — nenhum deles **troca** a mídia, só
   acrescenta conteúdo em volta. Serve para sobrepor a logo; não serve para substituir a imagem.

3. **Publicar e editar o blade do layout** — descartada: o kit passaria a manter uma cópia da view
   do pacote, e todo upgrade do Auth Designer exigiria reconciliar. O kit já paga esse preço em
   outros lugares e a wiki `perfil-e-acesso-ao-painel` registrou o custo.

### Consequências

- **Positivas**: nenhuma view publicada, nenhum bind de container, nenhuma escrita no repositório
  singleton (que afetaria o request inteiro). O override é local à página.
- **Negativas**: depende de `getAuthDesignerConfig()` continuar `public` e não-final, e de o blade
  continuar lendo lazy. São duas premissas de vendor — anotadas para o próximo upgrade do pacote.
- **Riscos**: se `TelaBloqueio` declarar um `boot()` próprio no futuro, ele mataria o `boot()` da
  trait que seta `static::$layout` (`HasAuthDesignerLayout.php:11-18`). Hoje sobrevive porque a
  classe redeclara a propriedade (`TelaBloqueio.php:40`) — e o comentário dela já explica esse
  acoplamento.

### Referências

- `01-plano-acao.md` → passo 8
- `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md` → ADR-06 (o mesmo repositório,
  o mesmo tipo de perda silenciosa)

---

## ADR-05: RQ-07 se cumpre com um ponto de extensão, não com campos inventados

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-07: *"podemos colocar também ali mais definições e informações da organização, porém, isso a
cargo do usuário do kit"*.

A cláusula não é testável como está — não nomeia campos nem critério. E ela tem uma segunda
metade que muda tudo: *"a cargo do usuário do kit"*.

### Decisão

Ler a cláusula como **"não impeça"**, e não como **"implemente"**.

Cumpre-se com: uma `Section` nomeada no `TenantForm` onde campos novos entram sem reestruturar
nada, e um docblock dizendo isso. **Nenhum campo de negócio é criado** — sem CNPJ, sem endereço,
sem telefone.

### Alternativas Consideradas

1. **Adivinhar os campos** (CNPJ, razão social, endereço, contato) — descartada, e seria o erro mais
   caro desta wiki. São dados de negócio: cada instalação quer os seus, com as suas validações e o
   seu formato fiscal. Um kit que crava "CNPJ" não serve fora do Brasil, e obriga migration de
   remoção em todo projeto que não quer o campo.
2. **Coluna `json` de "extras"** — descartada: aparência de extensibilidade, dado não-consultável.
   Quem usa o kit tem migration à disposição, que é melhor em tudo.

### Consequências

- **Positivas**: zero código especulativo. Campo novo custa uma migration e uma linha no form — o
  caminho que quem usa o kit já conhece.
- **Negativas**: um leitor apressado do requisito pode achar que a cláusula não foi entregue. É o
  motivo desta ADR, e de RQ-07 aparecer na Cobertura do Requisito apontando para o passo do form.

### Referências

- `00-requisito.md` → Ambiguidades → "RQ-07"
- `01-plano-acao.md` → passo 5

---

## ADR-06: Verificar o modal antes de corrigi-lo

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-09 diz: *"acho que hoje esta apenas abrindo uma modal, é melhor que seja tela completa"*. O
"acho" é do próprio requisito.

O código diz o contrário para a tabela principal. `Resources/Pages/Page.php:373-380`:

```php
if (($action instanceof EditAction) && (static::getResource()::hasPage('edit')) && …) {
    return $this->getResourceUrl('edit', ['record' => $action->getRecord()]);
}
```

Com URL preenchida, o Filament renderiza `<a href>` e não `wire:click`
(`Actions/Action.php:889`). O `TenantResource` **tem** página `edit` (`:110-114`), então o
`EditAction` navega.

O modal vem de outro lugar: o `UsersRelationManager` **não declara `$relatedResource`**, e nesse
caso `RelationManager::getDefaultActionUrl()` devolve `null`
(`RelationManagers/RelationManager.php:396-398`) — então `AttachAction`, `DetachAction` e a
`Action::make('papeisNaOrganizacao')` abrem modal **sempre**, e é o que se vê ao editar uma
organização.

### Decisão

**Não corrigir nada no passo de implementação.** Escrever os CT-B primeiro (passo 4 do PRD), rodar,
e só então decidir:

- `EditAction` navega → RQ-09 já satisfeito na tabela; a divergência real é o RelationManager, e
  vai para "Desvios do Plano"
- `EditAction` abre modal → achado de implementação, e a causa será uma das três guardas de
  `Page.php:376-377`, não falta de `->url()`

A página `view` é criada independentemente do resultado: essa lacuna está **confirmada**, não
suposta.

### Alternativas Consideradas

1. **Aplicar `->url()` no `EditAction` por precaução** — descartada. Duplicaria o que
   `getDefaultActionUrl()` já faz, e se um dia o resource perder a página `edit` o `->url()`
   apontaria para rota inexistente. Código que "não custa nada" e mente sobre o motivo de existir.
2. **Remover a página `edit` para forçar modal** — descartada: é o oposto do que RQ-09 pede.
3. **Assumir que o usuário está certo e reescrever a tabela** — descartada. O requisito diz "acho";
   tratar palpite como fato é como nasceu o erro de proveniência de DT-01 na wiki anterior, que só
   o quality gate pegou.

### Consequências

- **Positivas**: a suíte de browser construída na wiki anterior é usada para o que ela serve —
  responder o que o código faz, em vez de discutir o que ele deveria fazer.
- **Negativas**: o passo 4 gasta um ciclo sem produzir código de aplicação. Aceito: é mais barato
  que corrigir o lugar errado.
- **Riscos**: nenhum. O pior caso é confirmar a premissa do usuário, e aí o CT-B já está escrito.

### Referências

- `00-requisito.md` → Ambiguidades → "RQ-09"
- `01-plano-acao.md` → passo 4
- `05-casos-de-teste-browser.md` → CT-B01, CT-B02
