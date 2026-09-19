# Decisões Arquiteturais — Estudo e adoção de pacotes Filament, rodada 2

## ADR-01: Uma branch e uma wiki, sem worktree por item

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

RQ-09 pede que se decida se cada item precisa de branch/worktree própria. O precedente do
repositório é `wikis/specs/feat/estudo-advanced-tables/`, que usou worktree — e o solicitante citou
aquele estudo como referência de método.

### Decisão

Uma branch (`feat/estudo-de-pacotes-rodada-2`), uma wiki, um PR.

### Alternativas Consideradas

1. **Worktree por pacote (10 worktrees)** — descartada. Nove dos dez pacotes não geraram código
   nenhum; worktree para produzir um parágrafo de dossiê é overhead puro.
2. **Worktree por item nativo (5 worktrees)** — descartada, e este era o candidato real. Os cinco
   itens tocam **os mesmos quatro arquivos**: os três `PanelProvider` e `ConfiguracoesDoKit`. Cinco
   worktrees produziriam cinco conflitos nos mesmos arquivos, sem nenhum paralelismo verdadeiro,
   porque quem implementa é um agente por vez.
3. **Branch por item (5 PRs)** — descartada pelo mesmo motivo, mais o custo de cinco ciclos de
   quality gate para cinco mudanças de ≤40 linhas cada.

### Consequências

- **Positivas**: um diff, uma revisão, uma regressão. Os cinco itens compartilham a mesma
  justificativa (o estudo), e separá-los tornaria cada PR incompreensível sem o contexto dos outros.
- **Negativas**: o diff mistura correção de privacidade, feature de UI e correção de comentário.
  Mitigado por commits separados, um por item.
- **Riscos**: reverter um item isolado exige `git revert` do commit dele, não do PR.

### Por que o precedente do `estudo-advanced-tables` não vale aqui

Aquele estudo **não gerou código** (RQ-05 dele: "não precisa implementar nada por enquanto"), e
worktree serve exatamente a isso: isolar leitura pesada da árvore de trabalho. Este gera código nos
arquivos mais quentes do kit.

---

## ADR-02: O alerta de alterações não salvas é do Filament, ligado por Settings

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

RQ-03 e RQ-04 pediam avaliação de dois pacotes de rascunho (`ronssij/filament-simple-draft` e
`yousefaman/filament-autosave`) e de uma tela no Settings listando quais formulários teriam
salvamento. A necessidade por trás dos dois é uma só: **não perder o que foi digitado**.

A leitura do código dos dois achou impedimentos que não são de maturidade:

- **`simple-draft`**: o `->draftable()` chama `nullable()` com fallback `true` quando o componente
  Livewire não tem a propriedade `shouldSaveAsDraft`, e `nullable()` no Filament é literalmente
  `->required(fn () => ! $condition)`. Resultado: o mesmo `form()` usado num RelationManager ou num
  modal de `CreateAction` tem **todos os campos `->draftable()` com `required` desligado**, em
  silêncio. Ele também reimplementa as form actions e perde `->submit()` e
  `->keyBindings(['mod+s'])` — Enter e Ctrl+S deixam de salvar.
- **`autosave`**: escreve no registro real sem rodar validação de campo, e o kit tem **cinco models
  `Auditable`**. Uma linha em `audits` por pausa de digitação transforma a trilha de auditoria —
  que é feature vendida do kit — em ruído.

E o Filament 5 já tem o recurso: `Panel::unsavedChangesAlerts()`
(`vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php:11`), nascendo `false`
(`:9`), que o kit nunca ligou.

### Decisão

Ligar `->unsavedChangesAlerts()` nos três painéis, com **Closure** lendo
`config('kit.alerta_alteracoes_nao_salvas')`, editável em /admin/configuracoes-da-aplicacao.
Nenhum dos dois pacotes é adotado.

### Alternativas Consideradas

1. **`yousefaman/filament-autosave` só no modo Create** (draft em cache, sem write no banco, sem
   tocar na auditoria) — é a alternativa **defensável**, e fica registrada como caminho de
   reabertura. Descartada agora por ser dependência nova para um problema que o recurso nativo
   cobre na maior parte dos casos, e porque o draft em cache guarda dado pessoal parcial fora de
   qualquer trilha, com direito de eliminação não implementado.
2. **`ronssij/filament-simple-draft`** — recusada pelo fail-open de validação. Um starter kit não
   pode embarcar um pacote cujo modo de falha é `required` silenciosamente ausente.
3. **Rascunho próprio em `localStorage` via Alpine `$persist`** (~30 linhas) — descartada: custo
   zero de servidor, mas dado pessoal parcial persistindo em máquina compartilhada, sobrevivendo ao
   logout, invisível para o servidor e impossível de apagar remotamente. Risco de LGPD pior que o
   do pacote.
4. **Allowlist por formulário no Settings** — fora de escopo. Ela só tem o que governar se um dos
   pacotes entrar; com o recurso nativo, o interruptor é do painel inteiro.

### Consequências

- **Positivas**: zero dependência; o alerta vale também para as telas de plugin de terceiro, onde
  o kit não tem como colar trait; governável pela tela sem deploy.
- **Negativas**: é alerta, não rascunho — quem confirmar a saída perde o preenchimento do mesmo
  jeito. Cobre o caso mais comum (fechar a aba, navegar por engano), não todos.
- **Riscos**: liga um `beforeunload` em toda tela `create`/`edit` dos três painéis. Se incomodar,
  desliga-se num clique — foi por isso que nasceu governável, e não fixo no código.

### Por que a chave PODE ser Settings

`.ai/rules/settings.md` proíbe virar Settings a chave lida **no boot**. Esta é lida **por request**:
`hasUnsavedChangesAlerts()` chama `$this->evaluate()` sobre a Closure
(`HasUnsavedChangesAlerts.php:18-21`), e a avaliação acontece no render. Sem decisor extra, ao
contrário do que `registro_verificar_email` precisou.

### Referências

- `vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php:9,18-21`
- `.ai/rules/settings.md`

---

## ADR-03: O avatar padrão é desenhado no kit, não buscado em `ui-avatars.com`

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

A avaliação de `matondojk/filament-avatar-picker` (recusado: a galeria dele lista
`Storage::disk('public')->files('avatars')` sem filtro, e o Breezy do kit grava a foto de perfil no
**mesmo diretório** — a foto de cada usuário viraria item de galeria para todos os outros, entre
organizações inclusive) levou à pergunta de onde vem o avatar de quem **não** enviou foto.

A resposta, verificada no vendor e no kit:

- `vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10` — o provider padrão é
  `UiAvatarsProvider`;
- `vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:29` — ele devolve
  `https://ui-avatars.com/api/?name={iniciais}&...`;
- `grep -rn "defaultAvatarProvider" app/` — **vazio**: nenhum dos três painéis sobrescreve;
- `app/Models/User.php:857-862` — `getFilamentAvatarUrl()` devolve `null` sem foto, que é a
  condição que faz o Filament cair no provider.

Ou seja: o navegador de cada pessoa requisitava um domínio de terceiro em **toda tela** dos três
painéis, levando as iniciais na query string e o `Referer` do painel junto.

### Decisão

`App\Support\AvatarDeIniciais`, registrado em `->defaultAvatarProvider()` nos três painéis. Devolve
um `data:image/svg+xml;base64,...` com as iniciais sobre `gray[950]`, texto branco — a **mesma**
aparência do provider do vendor.

### Alternativas Consideradas

1. **`matondojk/filament-avatar-picker`** — recusado pelo vazamento acima, e também por
   `license: null` na API do GitHub (MIT declarado sem arquivo de licença), `SECURITY.md` apontando
   para `matondo@example.com`, zero testes e constraint `filament ^3.0` falsa (o código importa
   `Filament\Schemas\`, namespace que não existe na v3).
2. **`leek/dicebear`** (posição 63 do ranking, avatar gerado com cache em disco) — não avaliado a
   fundo nesta rodada; resolveria a geração, mas acrescenta dependência e disco para um problema
   que cabe numa classe.
3. **Gerar arquivo PNG/SVG no disco** — descartada: exige disco, link simbólico, limpeza de órfão e
   invalidação quando o nome muda. Um `data:` URI não tem nenhum desses.
4. **Manter o `UiAvatarsProvider` e apenas documentar** — descartada: documentar um vazamento não o
   fecha, e o kit cifra `client_secret` no settings enquanto mandava o nome do usuário para um
   terceiro a cada page load.

### Consequências

- **Positivas**: fecha o vazamento; remove uma requisição de rede externa por avatar; funciona
  offline e em rede fechada; nenhuma dependência nova; aparência idêntica à anterior.
- **Negativas**: o `data:` URI ocupa algumas centenas de bytes no HTML por avatar. Numa listagem de
  usuários com 25 linhas isso é medível, mas continua mais barato que 25 requisições HTTP.
- **Riscos**: nome com caractere especial poderia quebrar o SVG — mitigado com
  `htmlspecialchars(..., ENT_QUOTES | ENT_XML1)`, com CT dedicado.

### Por que fundo cinza fixo e não a cor primária

O avatar aparece dentro de `/app/{slug}`, onde a paleta é a da **organização**
(`AppPanelProvider::bootUsing()`). Fundo que mudasse de cor por organização tornaria a legibilidade
do texto branco uma aposta: `Rose` e `Amber` têm luminâncias muito diferentes. Cinza escuro fixo é
contraste garantido em qualquer paleta — e é o que o provider do vendor já fazia.

### Referências

- `vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10`
- `vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:27`
- `app/Support/AvatarDeIniciais.php`

---

## ADR-04: O rodapé mostra a versão do SISTEMA; a do kit é opcional e nasce desligada

**Status**: Aceita
**Data**: 2026-09-18
**Substitui**: a primeira redação desta ADR, que apontava o rodapé para `config('kit.version')`

### Contexto

RQ-02 pedia a versão "pela tag lançada ou pela branch `release/<versão-da-release-no-ambiente>`". A
primeira leitura resolveu isso apontando para `config('kit.version')`, com o argumento de que essa
chave **já é** a tag do release — o `KitUpdate::marcarVersao()` a reescreve
(`app/Console/Commands/KitUpdate.php:1050-1079`).

O argumento estava certo sobre a tag **do kit** e errado sobre a pergunta: "a release no ambiente"
sempre foi a do **produto implantado**. O Adendo 2 do `00-requisito.md` corrigiu:

> *"tem que ser a versão do sistema, o kit inicial é so uma metrica interna do starter não do
> produto que esta sendo implementado"*

### Decisão

Duas chaves, com papéis distintos e documentados nos dois arquivos de config:

| Chave | O que é | Onde se edita |
|---|---|---|
| `config('app.version')` | a versão do **produto** | tela (aba Identidade), semeada por `APP_VERSION` |
| `config('kit.version')` | a versão do **starter kit** | escrita pelo `kit:update`, nunca à mão |

O rodapé mostra a primeira. A segunda aparece ao lado **apenas** se
`config('kit.exibir_versao')` estiver ligada, e ela nasce `false`. Vazio nos dois → o rodapé não
renderiza nada.

### Alternativas Consideradas

1. **Resolver a versão do sistema lendo o `.git`** (tag anotada do HEAD, senão branch
   `release/<versão>`) — apresentada ao solicitante e **recusada por ele**. Custo: ~40 linhas de
   leitura de `.git/HEAD` e `packed-refs` sem `shell_exec`, mais um caso de teste. Impedimento
   real: imagem de produção normalmente não tem `.git`, e a leitura criaria uma segunda fonte de
   verdade divergente do que a tela mostra.
2. **Só `APP_VERSION` no `.env`, sem campo na tela** — recusada pelo solicitante: foge do padrão de
   tudo mais no kit ser editável em /admin, e mudar a versão passaria a exigir editar arquivo e
   limpar cache de config.
3. **`vaslv/filament-app-version`** — recusado. Exige `php: ^8.4` contra o `^8.3` declarado do kit,
   e o `GitVersionResolver` dele devolve **apenas o SHA curto**, recusando detecção de tag por
   desenho — ou seja, não entrega o que RQ-02 pedia. Sobra um `<span>` por uma dependência.
4. **Mostrar sempre as duas versões, sem toggle** — recusada pelo solicitante: quem entrega o
   produto a um cliente final não teria como esconder de qual kit ele nasceu.

### Consequências

- **Positivas**: a distinção fica explícita em dois arquivos de config e na tela; o produto pode
  versionar do jeito dele (texto livre — SemVer, data, número de build); nenhuma dependência nova.
- **Negativas**: **o deploy que só troca para a branch `release/2.4` não atualiza o rodapé
  sozinho.** Quem implanta preenche `APP_VERSION` ou o campo da tela. É consequência aceita da
  alternativa 1 ter sido recusada, e está escrita no Adendo 2.
- **Riscos**: o rodapé é renderizado também pelo layout `simple`, o das telas de autenticação —
  versão exposta a visitante é mapa de CVE. Mitigado pelo guard `filament()->auth()->check()` na
  blade, com CT dedicado.

### Por que `filament()->auth()->check()` e não `@auth`

`@auth` consulta o guard **default**. O kit nasce sem `->authGuard()` nos painéis, mas o projeto
que nascer dele pode declarar um — e aí `@auth` responderia sobre outro guard que não o do painel.
O modo de falhar seria a versão aparecendo para visitante, com o diff parecendo correto.

### Referências

- `config/app.php` (bloco "Versão do SISTEMA"), `config/kit.php` (`version`, `exibir_versao`)
- `resources/views/filament/versao-do-kit.blade.php`
- `vendor/filament/filament/resources/views/components/layout/simple.blade.php:61`

---

## ADR-05: Fronteira de acesso entra na trilha de auditoria por lista declarada

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

`App\Traits\AuditsFillables::getAuditInclude()` devolvia `getFillable()`. O docblock
declarava a regra certa — *"o que o usuário pode alterar é o que fica registrado"* — mas
`getFillable()` é um proxy errado para ela.

`users.ativo` vive em `$attributes` (`app/Models/User.php:106-108`) e **nunca** em `$fillable`,
porque `User::create($request->all())` com `ativo` fillable deixaria qualquer formulário destrancar
uma conta. Quem a escreve é `desativar()`/`reativar()`, com `forceFill(...)->save()`
(`User.php:307`, `:326`): o evento `updated` dispara, o auditor observa, e o atributo era descartado
pelo filtro.

Resultado medido: **desativar uma conta não aparecia em `/infra/audits`**. A trilha registrava a
troca do nome do usuário e não registrava o corte do acesso dele — que é o evento que importa.

### Decisão

Ponto de extensão na trait:

```php
public function getAuditInclude(): array
{
    return [...$this->getFillable(), ...$this->auditaAlemDoFillable()];
}

protected function auditaAlemDoFillable(): array { return []; }
```

`User` declara `['ativo', 'aprovacao_pendente']`.

### Alternativas Consideradas

1. **Tornar `ativo` fillable** — descartada, e seria o defeito de segurança que a ausência dela
   evita: atribuição em massa passaria a destrancar conta.
2. **Trocar `getAuditInclude()` por `getAuditExclude()`** (auditar tudo menos o que se exclui) —
   descartada: inverte o default de fechado para aberto. Token, contador e cache passariam a entrar
   na trilha até alguém lembrar de excluí-los.
3. **Sobrescrever `getAuditInclude()` direto no `User`** — descartada: resolve o caso e some com a
   regra. O próximo model com coluna de fronteira repetiria a decisão do zero, ou não a tomaria.
4. **`jeffersongoncalves/filament-ban`** — recusado. Traria motivo e expiração de bloqueio, mas
   criaria um **quarto** estado de conta paralelo a `ativo`/`deleted_at`/`aprovacao_pendente`, não
   bloqueia nada sem middleware registrado à mão, e `User::canBeImpersonated()` não o consultaria —
   um usuário `banned_at` mas `ativo = true` continuaria personificável.

### Consequências

- **Positivas**: a trilha passa a registrar quem desativou, quando e o valor anterior — que é a
  parte de "motivo + quem" do `ban` que realmente faltava, sem pacote e sem migration. Vale também
  para `aprovacao_pendente`, o outro estado de fronteira do kit.
- **Negativas**: a contagem de linhas em `audits` cresce um pouco, proporcional ao número de
  desativações — que é um evento raro.
- **Riscos**: model que declarar a lista com uma coluna sensível (token, segredo) a colocaria na
  trilha. Mitigado pelo docblock e pelo par de casos de teste.

### O que ficou de fora

**Motivo e expiração persistidos.** Registrar *por que* e *até quando* a conta foi desativada é
feature nova — migration, campos no formulário da Action, regra de expiração na leitura. Está
declarado em `## Fora de Escopo` do `00-requisito.md`. O que esta ADR entrega é **quem, quando e o
antes/depois**, que a linha de `audits` já carrega de graça.

### Referências

- `app/Traits/AuditsFillables.php`
- `app/Models/User.php:106-108`, `:291`, `:310`
- `tests/Kit/FundacaoTest.php` — o par de casos

---

## ADR-06: Documentação de API fica fora desta entrega, com o gatilho registrado

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

RQ-05 pedia documentação OpenAPI "quando tiver alguma api disponivel", com liga/desliga no Settings.
O kit **não tem API**: `routes/` contém apenas `console.php` e `web.php`, `bootstrap/app.php` não
declara `api:` no `withRouting()`, e não há `laravel/sanctum` nem `laravel/passport`.

### Decisão

Nada é instalado e nada é preparado. A escolha para o dia em que houver API fica registrada aqui.

### A escolha registrada

**`dedoc/scramble` para gerar + `scalar/laravel` para renderizar**, ambos fora do painel, com
quatro linhas de endurecimento obrigatórias:

```php
Scramble::ignoreDefaultRoutes();                                 // mata /docs/api e /docs/api.json
// config/scalar.php
'middleware' => ['web', 'auth', 'can:ver-documentacao-da-api'],
'file'       => storage_path('api-docs/openapi.json'),           // servidor lê; arquivo não é público
'configuration.proxyUrl' => null, 'configuration.telemetry' => false,
// deploy: php artisan scramble:export --path=storage/api-docs/openapi.json
```

### Alternativas Consideradas

1. **`alexkramse/filament-openapi-docs`** — recusado. Motivos confirmados na reavaliação:
   `getNavigationBadge()` chama `staticOpenApiData()` **incondicionalmente**
   (`src/Pages/OpenApiDocsPage.php:109`, antes do `match` que lê o modo), então o badge gera a spec
   inteira a cada montagem de navegação e **não há configuração que desligue isso** — só uma
   subclasse. Soma-se **zero CI** (`.github/workflows` não existe; o badge "tests passing" do README
   é estático), 9 stars, 0 forks, bus factor 1, dois meses de idade.
   *Correção de fato*: o problema **não** é o `dedoc/scramble` ser `0.x` — ele tem 1,9 M
   downloads/mês, 2209 stars, 67 contribuidores e CI. A recusa é do plugin, não do motor.
2. **`dardangashi/filament-api-explorer`** — a única alternativa Filament **sem** dependência dura
   de Scramble, e a única com gancho de autorização próprio (`->authorizeUsing()`,
   `src/ApiExplorerPlugin.php:202`). Recusada por maturidade: 2 stars, 1 contribuidor, 162
   downloads, três semanas parado. Tem `'examples' => ['capture' => true]` por padrão, que grava
   respostas reais da API em cache e as mostra a quem abrir a página.
3. **`zpmlabs/filament-api-docs-builder`** — **bloqueado**, não recusado: licença `proprietary`,
   instalação por repositório privado. `.ai/rules/general.md` proíbe dependência de repositório
   privado no `composer.json`/`composer.lock` commitados.
4. **`knuckleswtf/scribe`** — a alternativa defensável ao Scramble (build-time puro, zero custo de
   runtime, 1 M downloads/mês). Descartada por produzir um site paralelo em `/docs`, público por
   padrão, que não casa com o resto do kit.
5. **`darkaonline/l5-swagger`** — descartada: exige anotar cada endpoint à mão, o oposto do que um
   starter kit entrega.
6. **`rakutentech/laravel-request-docs`** — descartada com prejuízo: `/request-docs` é pública em
   **qualquer** ambiente por padrão (o middleware `NotFoundWhenProduction` vem comentado no config),
   e o "try it out" devolve SQL executado, logs e models tocados.
7. **`vyuldashev/laravel-openapi`** — morta: último release em 2023-05-04, não instala em
   Laravel 13.
8. **Preparar agora, desligado** — descartada explicitamente. Instalar o Scramble "desligado" não o
   desliga: o `ScrambleServiceProvider` registra rotas em `$this->app->booted()`, e
   `RestrictedDocsAccess` libera `/docs/api` **sem autenticação** quando `APP_ENV=local`
   (`src/Http/Middleware/RestrictedDocsAccess.php:11-13`). Seria abrir superfície pública para
   documentar zero endpoints.
9. **Um `Toggle` no Settings que não faz nada até existir API** — descartada por
   `.ai/rules/settings.md`: *"Toggle que grava e não faz efeito até o próximo deploy é pior que
   campo ausente"*. Um que nunca faz efeito é a versão degenerada disso.

### Consequências

- **Positivas**: nenhuma dependência, nenhuma rota nova, nenhuma superfície pública. A decisão fica
  tomada e justificada para quem acender.
- **Negativas**: RQ-05 não é atendida nesta entrega, e isso está declarado em
  `## Fora de Escopo (declarado)` do `00-requisito.md`.
- **Gatilho de reabertura**: `routes/api.php` existir e ter endpoint.

### Como ligaria por Settings, quando existir

A rota `/scalar` é registrada no boot, então **gatear o registro é proibido** por
`.ai/rules/settings.md`. O padrão da v0.19.8 resolve: registra sempre, decide por request num
middleware próprio de três linhas (`abort_unless(config('kit.api.documentacao_habilitada'), 404)`),
declarado em `config/scalar.php` → `'middleware'`. `Scramble::ignoreDefaultRoutes()` **não** pode
virar Settings: é boot e decide se a rota existe.

### Referências

- `bootstrap/app.php` (sem `api:` no `withRouting()`), `routes/`
- `.ai/rules/settings.md`, `.ai/rules/general.md`
- `07-dossies-dos-pacotes.md`, seção OpenAPI

---

## ADR-07: Nenhum dos dez pacotes é adotado

**Status**: Aceita
**Data**: 2026-09-18

### Contexto

RQ-08 pedia a proposta de "quais vão entrar", pressupondo que ao menos um entraria. A leitura de
código dos dez concluiu que nenhum passa.

### Decisão

Zero adoções. Cinco itens nativos, sem dependência nova.

### O critério que decidiu

O gate que reprovou a maioria não foi maturidade — foi **alternativa nativa**: se o mesmo valor
cabe em ≤ ~30 linhas no padrão que o kit já usa, o pacote perde. O kit já tinha esse precedente
escrito, ao recusar `gboquizosanchez/filament-scroll-to-top` e escrever
`ConfiguraFilamentGlobal::configuraBotaoVoltarAoTopo()`.

Maturidade foi o gate secundário, e pesou porque **sete dos dez repositórios têm menos de 40 dias
de vida** e nenhum passa de 14 stars. Num kit redistribuído, cada dependência vira dependência de
todo projeto que nasce dele.

### Consequências

- **Positivas**: `composer.json` intocado; três defeitos do próprio kit corrigidos; as dez decisões
  ficam registradas em `wikis/pacotes-candidatos.md` §4 com o motivo, para a próxima varredura não
  reavaliar do zero.
- **Negativas**: RQ-08 é atendida com "nenhum", que não era o resultado esperado pelo solicitante.
  Foi apresentada como proposta e **aprovada explicitamente** antes da implementação.
- **Riscos**: dois dos adiados (`packstub/filament-flow` e `yousefaman/filament-autosave`) são
  tecnicamente bons e podem amadurecer. Os gatilhos de reabertura estão no dossiê.

### Os dez, com o gate que reprovou cada um

| Pacote | Veredito | Gate decisivo |
|---|---|---|
| `mortalkiller/filament-page-header` | ADIAR | exige `filament ^5.8.1` (kit em 5.7.6); API quebrou em 24 h |
| `jeffersongoncalves/filament-page-visits` | ADIAR | kit quase não tem rota pública; `PageVisitResource` quebra no `/app` |
| `jeffersongoncalves/filament-ban` | RECUSAR | quarto estado de conta paralelo; não bloqueia sozinho |
| `packstub/filament-flow` | ADIAR | superfície desproporcional para kit distribuído |
| `syofyanzuhad/filament-connection-indicator` | RECUSAR | mede `navigator.onLine`; verde com servidor caído |
| `matondojk/filament-avatar-picker` | RECUSAR | vaza foto de perfil entre usuários e organizações |
| `vaslv/filament-app-version` | ADIAR | exige PHP `^8.4`; não resolve tag nem branch |
| `ronssij/filament-simple-draft` | RECUSAR | `nullable()` fail-open desliga `required` fora do trait |
| `yousefaman/filament-autosave` | ADIAR | uma linha em `audits` por pausa de digitação |
| `alexkramse/filament-openapi-docs` | RECUSAR | kit não tem API; badge gera spec; sem CI |

### Referências

- `07-dossies-dos-pacotes.md` — o dossiê completo, por pacote
- `wikis/pacotes-candidatos.md` §4, `wikis/pacotes-ranking.md`

---

## ADR-08: O kit acompanha o Filament sem travar, e o que ele NÃO propaga

**Status**: Aceita
**Data**: 2026-09-18
**Origem**: Adendo 1 do `00-requisito.md` (RQ-14, RQ-15, RQ-16), e o achado QA-01 do ciclo 1 do
quality gate

### Contexto

O Adendo 1 pediu três coisas que parecem uma: não travar a versão do Filament (RQ-14), atualizar o
kit quando sair atualização (RQ-15), e fazer os projetos que usam o kit receberem a atualização
(RQ-16).

A primeira leitura tratou as três como uma só e verificou apenas a constraint: `composer.json`
declara `filament/filament: ^5.6`, que permite toda a série 5.x. Conclusão registrada: *"a
constraint nunca esteve travada, RQ-14 está satisfeita"*.

**A conclusão estava certa e a entrega estava incompleta.** O quality gate mediu o que a wiki não
mediu: `composer outdated "filament/*"` devolvia `5.7.6 ! 5.8.2`, e
`git diff main...HEAD -- composer.json composer.lock` vinha **vazio**. RQ-15 tinha disparado e nada
tinha sido feito — sem passo no PRD, sem CT, sem nota de adiamento. Pior: o próprio Adendo 1
raciocinava a partir de *"Atualizado o Filament, esse motivo cai"*, como se o update tivesse
acontecido.

É omissão silenciosa com aparência de decisão, e é exatamente a classe que a dimensão A do quality
gate existe para pegar.

### Decisão

Três partes, uma por cláusula:

1. **RQ-14 — constraint em caret, nunca exata, nunca `*`.** `^5.6` permite 5.7, 5.8 e qualquer 5.x.
   `*` seria travar ao contrário: um major do Filament entraria sozinho num `composer update` de
   rotina e quebraria toda instalação do kit.
2. **RQ-15 — o kit sobe para a versão corrente da série.** Feito nesta entrega:
   `composer update "filament/*" --with-all-dependencies`, **v5.7.6 → v5.8.2**, com a suíte
   completa como oráculo.
3. **RQ-16 — atendida parcialmente, e o limite é declarado.** Ver abaixo.

### O limite de RQ-16, medido e não presumido

O `00` nomeou dois mecanismos de propagação. **Só o primeiro funciona:**

| Mecanismo | O que faz de fato |
|---|---|
| A constraint em caret que o projeto herda | **funciona**: o projeto nasce com `^5.6` no próprio `composer.json`, e um `composer update` dele traz o Filament novo |
| `php artisan kit:update` | **não propaga dependência.** `app/Console/Commands/KitUpdate.php:299-306` lista `composer.json` entre os arquivos que o comando **nunca** sobrescreve, e `:960-970` apenas emite aviso com instrução manual |

Ou seja: o `kit:update` **notifica**, não atualiza. E isso é decisão deliberada daquele comando —
sobrescrever o `composer.json` de um projeto de terceiro apagaria as dependências que ele
acrescentou, que é um estrago muito maior que uma versão atrasada.

**Consequência aceita**: um projeto que nasceu do kit e nunca roda `composer update` fica para
trás, e nenhum mecanismo do kit o alcança. O que o kit garante é que **nada o impede** de
atualizar — que é o que a constraint em caret compra.

### Alternativas Consideradas

1. **`kit:update` passar a mesclar o `composer.json` do projeto** — descartada. Merge de
   `composer.json` de terceiro é destrutivo na melhor das hipóteses e insolúvel na pior (conflito
   de constraint entre o que o kit quer e o que o projeto precisa).
2. **Fixar a versão exata do Filament no kit** (`5.8.2` em vez de `^5.6`) — descartada: é o oposto
   literal de RQ-14.
3. **Afrouxar para `*`** — descartada: aceitaria major novo sem revisão.
4. **Um comando novo `kit:deps`** que só reporta dependência desatualizada — descartada por
   redundância: `mominalzaraa/filament-composer-release-notifier` já está instalado e faz
   exatamente isso, com tela em `/infra`.

### Consequências

- **Positivas**: o kit publicado passa a nascer na 5.8.2; a constraint continua permitindo as
  próximas sem intervenção; o limite de propagação fica escrito em vez de presumido.
- **Negativas**: subir de minor traz mudanças de vendor que a suíte do kit cobre, mas o projeto do
  usuário não — quem atualiza roda a própria suíte.
- **Riscos**: um minor do Filament pode mudar comportamento de vendor que o kit consome
  indiretamente. Mitigação: `composer test` completo como gate do bump, e é o que foi feito.

### Referências

- `composer.json` — `"filament/filament": "^5.6"`
- `app/Console/Commands/KitUpdate.php:299-306`, `:960-970`
- `06-relatorio-qa.md`, achados QA-01 e QA-04
