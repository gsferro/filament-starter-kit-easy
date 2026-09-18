# Plano de Ação — Estudo e adoção de pacotes Filament, rodada 2

> Requisito: `00-requisito.md`
> Dossiês dos 10 pacotes: `07-dossies-dos-pacotes.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (o precedente de método é `wikis/specs/feat/estudo-advanced-tables/`, que é
  referência de formato, não ancestral: aquele estudo não gerou código e não há regressão a rodar
  contra ele)
- **Motivo**: rodada nova de avaliação de pacotes, com 10 candidatos indicados pelo solicitante
- **Toca infra compartilhada?**: **sim** — os três `PanelProvider`, `App\Traits\AuditsFillables`
  (usada por 5 models) e `App\Settings\ConfiguracoesDoKit`. **A regressão é obrigatória** mesmo
  sendo wiki `nova`: contra `tests/Kit` e `tests/Tenancy` inteiros, mais os CT-B das telas de
  autenticação (o `AvatarProvider` alcança a tela de login pelo layout `simple`).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Análise a fundo dos 10 pacotes | 6 (dossiê consolidado) + `07-dossies-dos-pacotes.md` | Executada antes deste plano; o produto documental é o passo 6 |
| RQ-02 | `app-version` pela tag ou pela branch `release/<versão>` | — | ❌ **SUBSTITUÍDA por RQ-17** no Adendo 2. A premissa original ("a tag já é `config('kit.version')`") respondia a pergunta errada: aquela é a tag **do kit**, não a do produto *(alterado em 2026-09-18: adendo 2)* |
| RQ-03 | `simple-draft` com visão no Settings de quais forms | 2 | Atendido **parcialmente**: o Settings governa o alerta **globalmente**, não por formulário. Granularidade por form declarada fora de escopo no `00` |
| RQ-04 | `autosave` com prós/contras e ativar/desativar | 6 | Análise entregue; adoção recusada, com o caminho de reabertura em **ADR-02** *(alterado em 2026-09-18: QA-06 — apontava ADR-03, que é a do avatar)* |
| RQ-05 | `openapi-docs` automático quando houver API | — | ⚠️ **fora desta entrega** — o kit não tem `routes/api.php`. Gatilho de reabertura registrado no `00` e em ADR-06 |
| RQ-06 | Sub-agentes em paralelo | — | Cumprido na fase de pesquisa: 5 sub-agentes, 2 pacotes cada. Registro em `07-dossies-dos-pacotes.md`, seção *Método* *(alterado em 2026-09-18: QA-06 — apontava o `03`)* |
| RQ-07 | Mesmo formato dos estudos anteriores | 6 | `wikis/pacotes-candidatos.md` §4 e `wikis/pacotes-ranking.md`, conforme o processo da §5 daquela página |
| RQ-08 | Proposta do que entra + implementar | 1–5 | Proposta: nenhum pacote entra; entram 5 itens nativos. Aprovada pelo solicitante em 2026-09-18 |
| RQ-09 | Decidir branch/worktree | — | Decidido em ADR-01: uma branch, uma wiki |
| RQ-10 | `/code-review` no diff | 8 | Step 7.5 da `feature-wiki` |
| RQ-11 | Usar o Blueprint | 7 | `composer bp:on` → aderência → `bp:off`, conforme `.ai/rules/general.md` |
| RQ-12 | Do início ao fim | 1–9 | — |
| RQ-13 | Implementar os cinco itens nativos | 1–5 | Adendo 1 |
| RQ-14 | Não travar a versão do Filament | 10 | Adendo 1. `composer.json` declara `filament/filament: ^5.6`, que **já** permite toda a série 5.x — a constraint nunca esteve travada. Verificado, não presumido |
| RQ-15 | Saindo atualização do Filament, atualizar o kit | 10 | Adendo 1. **Foi omissão até o ciclo 1 do quality gate** (QA-01, Blocker): a constraint permitia, mas ninguém rodou o update. Fechado com `composer update "filament/*"` → **v5.7.6 → v5.8.2** |
| RQ-16 | Os projetos que usam o kit recebem a atualização | 10 | Adendo 1. Atendido **parcialmente e com limite declarado** — ver ADR-08 |
| RQ-17 | O rodapé exibe a versão do **sistema**, não a do kit | 3 | Adendo 2. **Substitui RQ-02** |
| RQ-18 | A versão do kit é exibível, mas customizável por quem usa o kit | 3 | Adendo 2. Interruptor `exibir_versao_do_kit`, nascendo desligado |
| RQ-19 | Nenhum dado do usuário sai para terceiro na renderização de um avatar | 1 | **Adendo 3**. Tira o avatar da condição de *código sem `RQ`*: ele entrava só por RQ-13, que é cláusula sobre quantidade de itens, não sobre garantia. R7/R8 do `04` deixam de ser `@premissa` |
| RQ-20 | A versão do kit, quando exibida, carrega rótulo que a distingue da do sistema | 3 | **Adendo 3**. Dá forma operacional ao invariante da premissa nº 2, que era prosa. O texto do rótulo não é fixado pelo requisito |

## Objetivo

Avaliar dez plugins Filament indicados pelo solicitante e decidir, com leitura de código-fonte e
não de descrição de diretório, quais agregam valor ao starter kit. A conclusão da avaliação é que
**nenhum dos dez entra**, e que cinco mudanças nativas entregam o valor que quatro deles prometiam
— três delas corrigindo defeito que já existe no kit hoje.

A entrega, portanto, tem duas metades: o **registro documental** das dez decisões, para que a
próxima varredura não reavalie do zero (é o que `wikis/pacotes-candidatos.md` §5 exige), e a
**implementação** dos cinco itens nativos.

## Contexto

O kit tem 60 dependências diretas e um método já estabelecido para decidir a 61ª: a varredura de
2026-08-18 classificou 547 plugins, o Tier S foi adotado com wiki própria por pacote, e
`pacotes-candidatos.md` registra explicitamente que *"um `SIM` aqui significa 'vale abrir o
repositório', não 'pode instalar'"*. Esta rodada aplica o mesmo critério a uma lista escolhida a
dedo.

O resultado desta rodada é desfavorável por um motivo concreto e mensurável: **sete dos dez
repositórios têm menos de 40 dias de vida** e nenhum passa de 14 stars. Não é preconceito contra
pacote novo — é que um starter kit é redistribuído, e cada dependência dele vira dependência de
todo projeto que nasce dele. O kit já registrou essa decisão uma vez, ao recusar
`gboquizosanchez/filament-scroll-to-top` e escrever um render hook próprio
(`ConfiguraFilamentGlobal::configuraBotaoVoltarAoTopo()`).

Três defeitos apareceram **no próprio kit** durante a leitura dos pacotes, e são o principal
resultado desta rodada:

1. Todo usuário sem foto de perfil faz o navegador buscar as iniciais dele em `ui-avatars.com`.
2. Desativar uma conta não entra na trilha de auditoria.
3. Quatro arquivos justificam uma decisão de render hook com uma afirmação errada sobre o vendor.

## Análise dos Arquivos Existentes

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`
Cada um monta o painel com uma cadeia fluente. Nenhum dos três chama `->defaultAvatarProvider()`
nem `->unsavedChangesAlerts()`. É onde entram os passos 1 e 2 — método de `Panel`, não de
componente, então não cabe em `ConfiguraFilamentGlobal`, que é configuração estática.

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`
Configuração global por `configureUsing()` estático + render hooks globais. O passo 3 entra aqui,
ao lado de `configuraBotaoVoltarAoTopo()`, e pelo mesmo motivo documentado lá: sem `scopes:`, o
hook vale para os três painéis e para qualquer painel que o projeto criar depois.

### `app/Traits/AuditsFillables.php`
`getAuditInclude()` devolvia `getFillable()` (`:17` antes desta entrega; `:21-23` depois). O docblock declara a regra certa — *"o que o
usuário pode alterar é o que fica registrado"* — mas `getFillable()` é um proxy errado para ela:
`ativo` é alterável pelo administrador via Action e **não** é fillable. Passo 4.

### `app/Models/User.php`
`$fillable` = `name, email, password, avatar_url` (`:89-94`); `ativo` vive em `$attributes`
(`:106-108`). `desativar()` grava com `forceFill(['ativo' => false])->save()` (`:307`) — o evento
`updated` dispara, o auditor observa, e o atributo é descartado pelo filtro do `getAuditInclude()`.
`getFilamentAvatarUrl()` devolve `null` sem avatar (`:857-862`), que é o que faz o Filament cair no
`defaultAvatarProvider`.

### `app/Settings/ConfiguracoesDoKit.php`
Contrato de três lugares: propriedade, linha em `mapaDeConfiguracao()`, `add`/`deleteIfExists` numa
migration **nova**. Passo 2.

### `config/kit.php:22`
`'version' => '0.34.2'`, gravada pelo `KitUpdate::marcarVersao()` (`:1050-1079`) a partir da tag.
É a fonte do passo 3.

## Autorização

- **Policies**: nenhuma nova.
- **Gates**: nenhum novo.
- **Middleware**: nenhum novo.
- **Permissões Shield**: **nenhuma nova.** Nenhum passo cria Resource, Page ou Widget, então
  `shield:generate` não tem entidade nova a descobrir e `PapeisSeeder` não muda. Declarado aqui
  porque `.ai/rules/filament.md` torna a omissão dessa checagem um defeito, não um silêncio.
- O único item com efeito de autorização é o **guard de visitante** no blade da versão (passo 3):
  o rodapé é renderizado também pelo layout `simple`, que é o das telas de autenticação.

## Rotas

**Nenhuma rota nova.** Nenhum passo registra rota, e nenhum pacote foi adotado.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Avatar padrão (iniciais) no menu do usuário e nas tabelas de usuários | Filament (provider) | todas as telas dos 3 painéis | nenhuma — é exibição | Não |
| Rodapé com a versão do sistema (e a do kit, rotulada, sob interruptor) | Blade em render hook `FOOTER` | todas as telas dos 3 painéis | nenhuma — é exibição | Não |
| Alerta de alterações não salvas | Filament nativo (`unsavedChangesAlerts`) | telas `create`/`edit` dos 3 painéis | tentar sair da página com formulário sujo → confirmação do navegador | **Sim** |
| Campo "Avisar sobre alterações não salvas" | Filament (`Toggle`) | `/admin/configuracoes-da-aplicacao`, aba **Kit** | liga/desliga e salva | Não |
| Campo "Versão do sistema" | Filament (`TextInput`) | `/admin/configuracoes-da-aplicacao`, aba **Identidade** | digita a versão do produto e salva | Não |
| Campo "Mostrar também a versão do kit no rodapé" | Filament (`Toggle`) | `/admin/configuracoes-da-aplicacao`, aba **Kit** | liga/desliga e salva | Não |

**Gate de CT-B**: a tabela é gatilho, não critério.

- O **alerta de alterações não salvas** só o navegador prova: é `beforeunload` do browser,
  disparado por JavaScript do Filament. **Vai para o `05`.**
- O **avatar de iniciais** precisa de um CT-B por um motivo específico: a asserção que importa é
  *"nenhuma requisição sai para `ui-avatars.com`"*, e isso é observação de **rede**, não de HTML.
  Um teste de componente veria a string do `data:` URI e ficaria verde mesmo com outro ponto da
  página buscando o domínio. **Vai para o `05`.**
- O **rodapé da versão** e o **toggle no Settings** são conteúdo renderizado e gravação de
  formulário — **ficam no `04`**, como teste de componente Livewire.

**Gate de tela de escrita**: a única rota de escrita tocada é `/admin/configuracoes-da-aplicacao`,
que já tem cenário de gravação por componente em `tests/Kit/ConfiguracoesDoKitTelaTest.php`; o
campo novo entra lá.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_ALERTA_ALTERACOES_NAO_SALVAS` | `true` | Semente de `kit.alerta_alteracoes_nao_salvas`. O banco vence em execução; esta chave semeia a migration de settings e é o plano B |
| `APP_VERSION` | vazio | Semente de `app.version`, a versão do **sistema**. Vazia, o rodapé não mostra a sua versão. Semeia **uma vez**, na instalação: depois disso o banco vence |
| `KIT_EXIBIR_VERSAO` | `false` | Semente de `kit.exibir_versao`. Acrescenta a versão do **kit**, rotulada, ao lado da do sistema |

Nenhuma chave nova para o avatar — ver **ADR-03** *(alterado em 2026-09-18: QA-06 — a redação anterior mandava ler "ADR-04 e ADR-05")*. Para a versão, `APP_VERSION` (chave do Laravel, semeando `app.version`) e `KIT_EXIBIR_VERSAO` *(alterado em 2026-09-18: adendo 2 — a redação anterior negava chaves que passaram a existir)*.

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum novo.
- **Listeners**: nenhum novo.
- **Observers**: nenhum novo. O passo 4 muda **o que** o observer do `owen-it/laravel-auditing`
  grava, não **se** ele grava.

## Jobs / Queues

Nenhum. Nenhum passo enfileira trabalho.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **1** — nenhum passo adia carga, faz polling ou torna widget `lazy` |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado **por request**? | nada. `AvatarDeIniciais::get()` é string building puro (sem I/O, sem query); o rodapé lê `config('kit.version')`, que é array em memória |
| O que é cacheado **entre** requests? | nada novo. O Settings já é alinhado uma vez por boot por `KitServiceProvider::configureSettingsDoKit()` |
| Custo do caminho principal | **zero query nova.** O avatar hoje custa 1 requisição HTTP **do navegador** para `ui-avatars.com` por usuário sem foto; depois do passo 1 custa 0 |

O passo 1 é a única mudança com efeito medível de desempenho, e ele **reduz** custo: troca uma
requisição de rede externa por uma string embutida no HTML.

## Impacto em Features Existentes

- **Telas de autenticação**: o render hook `FOOTER` também é emitido por
  `vendor/filament/filament/resources/views/components/layout/simple.blade.php:61`, que é o layout
  de login/registro/recuperação. O guard de visitante do passo 3 existe por causa disso. Risco:
  se o guard falhar, a versão aparece para quem não autenticou.
- **`tests/Kit/TelasDeAutenticacaoTest.php` e `tests/Browser/`**: qualquer asserção que case texto
  na tela de login pode ser afetada pelo rodapé. Conferir.
- **Trilha de auditoria (`/infra/audits`)**: o passo 4 acrescenta `ativo` ao que é gravado **de
  agora em diante**. Registros antigos não mudam. `tests/Kit/ExclusaoDeUsuarioTest.php` e
  `SituacaoDaContaTest.php` são os vizinhos diretos.
- **Avatares em tabela**: `UserResource` (admin e app) e o widget
  `UltimosUsuariosCadastrados` exibem avatar. Passam a mostrar o SVG local em vez do remoto.
- **`unsavedChangesAlerts`**: liga um comportamento de navegador em **toda** tela `create`/`edit`
  dos três painéis, inclusive nas de plugin de terceiro. É a mudança de maior superfície desta
  entrega, e a razão de ela nascer governável pelo Settings.
- **O bump do Filament (passo 10) é a maior superfície do branch**, e maior que o
  `unsavedChangesAlerts` acima *(alterado em 2026-09-18: achado QA-24 do ciclo 3 — este bloco não
  mencionava o passo 10)*. Medido, não estimado, com `composer.lock` antes × depois:

  | | |
  |---|---|
  | Pacotes que mudaram de versão | **18** |
  | Da família `filament/` | 13 (v5.7.6 → v5.8.2) |
  | **Fora da família** | **5** |
  | Pacotes novos ou removidos | **nenhum** |

  Os cinco de fora: `ramsey/uuid` 4.9.3→4.9.4 e `spatie/laravel-medialibrary` 11.23.5→11.23.8 são
  **de runtime** — o primeiro alimenta `App\Traits\TemUuid`, o segundo é a camada de mídia do kit;
  `danharrin/livewire-rate-limiting` v2.2.1→v2.3.0 atende o rate limit das telas de autenticação; e
  `phpstan/phpdoc-parser` e `rector/rector` são de desenvolvimento.

  Mais 17 arquivos de asset regerados pelo `post-update-cmd` (11 bundles JS do Filament, a folha
  `app.css`, 7 fontes Inter).

## Rollback

- **Migration down**: `database/settings/..._add_alerta_alteracoes_nao_salvas_to_kit_settings.php`
  tem `deleteIfExists`. `migrate:rollback` remove a propriedade; a config volta a valer pelo
  `.env`, e `ConfiguracoesDoKit::aplicarNaConfig()` deixa de sobrepor a chave.
- **Feature flag**: o alerta desliga pela tela (`/admin/configuracoes-da-aplicacao`, aba Kit) sem
  deploy.
- **Reversão dos demais**: os passos 1, 3, 4 e 5 são reversíveis por `git revert` — nenhum grava
  dado novo nem altera schema.
- **O passo 10 NÃO é reversível por `git revert`** *(alterado em 2026-09-18: achado QA-24)*.
  Reverter o commit devolve `composer.json` e `composer.lock` ao estado anterior, mas **não**
  reinstala o `vendor/` nem regera os assets publicados. A reversão real é:

  ```bash
  git revert {commit}          # devolve o lock
  composer install             # reinstala o vendor a partir dele
  php artisan filament:assets  # regera o que o post-update-cmd publicou
  ```

  E ela desfaz os 18 pacotes juntos, inclusive os 5 fora da família Filament — não há reversão
  parcial, porque o lock é um só.
- **Reversão de dados**: nenhuma. Nenhum passo migra dado.

## Dependências

- **Composer**: **nenhuma nova.** É o resultado central desta rodada — nenhum dos dez pacotes
  avaliados entrou, e `composer.json` não ganhou linha.
- **Composer, versões**: 18 pacotes **subiram de versão** no passo 10, sem nenhum entrar ou sair.
  A constraint de `filament/filament` continua `^5.6` — RQ-14 proíbe travar
  *(alterado em 2026-09-18: achado QA-24 — este bloco dizia só "nenhuma nova", o que era verdade
  sobre entrada e silencioso sobre versão)*.
- **NPM**: nenhuma nova.
- **Removidas**: nenhuma.
- **`filament/blueprint`**: entra e sai por `composer bp:on` / `bp:off` no passo 7, e **nunca**
  fica no `composer.json` commitado — `.ai/rules/general.md` e
  `tests/Kit/BlueprintForaDoPacoteTest.php`.

## Riscos

- **O rodapé vazar a versão para visitante** — mitigação: guard no blade + CT dedicado que abre
  `/admin/login` deslogado e assere a ausência da string.
- **`unsavedChangesAlerts` incomodar em telas de terceiro** — mitigação: nasce governável pela
  tela; desligar é um clique, sem deploy.
- **Asserção de teste existente casar texto na tela de login** e quebrar com o rodapé — mitigação:
  rodar `composer test:kit` inteiro, não só o filtro da feature.
- **O avatar de iniciais perder contraste** em alguma paleta — mitigação: fundo fixo em
  `gray-950` e texto branco, que é exatamente o par do provider padrão do Filament
  (`UiAvatarsProvider.php:27`), e não depende da cor primária nem da cor da organização.
- **Nome com caractere que quebre o SVG** (aspas, `<`, `&`) — mitigação: `e()` no valor antes de
  compor o SVG, e CT com nome contendo `<script>`.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem os channels do kit; o mais próximo do assunto é `autenticacao`, usado por
`User::desativar()`/`reativar()` (`User.php:294`, `:310`) e pelo escopo de tenancy.

### Decisão

**Nenhum channel novo, e nenhum log novo.** A justificativa, para que a ausência seja decisão e
não esquecimento:

- O passo 1 é uma função pura de string — não há decisão de fluxo, não há falha possível que não
  seja um erro de programação.
- Os passos 2 e 3 são leitura de config já alinhada no boot.
- O passo 4 muda **o que** o auditor grava; o registro é a própria linha de `audits`, que é mais
  forte que log.
- Os passos 5 e 6 são texto.

Um channel `estudo-de-pacotes-rodada-2` nasceria vazio e viraria dívida. Onde havia lacuna real de
rastreabilidade — a desativação de conta fora da trilha — a correção é o passo 4, não um log a
mais.

## Estrutura de Implementação

### 1. Avatar padrão local, com iniciais

> Skills: `laravel-best-practices`, `pest-testing`

**Problema**: `vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10` define
`UiAvatarsProvider` como provider padrão, e
`vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:29` monta
`https://ui-avatars.com/api/?name={iniciais}&format=svg&color=FFFFFF&background={hex}`. Os três
painéis do kit não sobrescrevem (`grep -rn "defaultAvatarProvider" app/` = vazio), e
`User::getFilamentAvatarUrl()` devolve `null` quando não há foto (`User.php:857-862`). Logo: todo
usuário sem foto faz o **navegador** requisitar um domínio de terceiro, enviando as iniciais na
query string e o `Referer` do painel.

- **Path**: `app/Support/AvatarDeIniciais.php` (novo)
- **Assinatura**:
  ```php
  final class AvatarDeIniciais implements \Filament\AvatarProviders\Contracts\AvatarProvider
  {
      public function get(\Illuminate\Database\Eloquent\Model $record): string;
  }
  ```
- **Lógica**:
  1. Nome via `Filament::getNameForDefaultAvatar($record)` — o mesmo que o provider do vendor usa,
     para não divergir de quem já tem o nome customizado.
  2. Iniciais: primeira letra dos dois primeiros segmentos não vazios, em maiúscula, no máximo 2.
     Nome vazio devolve string vazia, e o SVG sai sem texto — nunca lança.
  3. Fundo: `Color::convertToHex(FilamentColor::getColor('gray')[950] ?? Color::Gray[950])` — a
     mesma expressão do `UiAvatarsProvider.php:27`, para que a aparência não mude.
  4. Texto branco, `font-family` do sistema, `text-anchor: middle`.
  5. Devolve `'data:image/svg+xml;base64,'.base64_encode($svg)`.
  6. **Escapar** o texto com `e()` antes de compor o SVG.
- **Registro**: `->defaultAvatarProvider(AvatarDeIniciais::class)` nos três `PanelProvider`,
  logo depois de `->colors(...)`, com um comentário de uma linha dizendo o que a ausência
  causava.
- **Logs**: nenhum (ver "Channel de Log").

### 2. Alerta de alterações não salvas, governado pelo Settings

> Skills: `laravel-best-practices`, `pest-testing`

**Problema**: `vendor/filament/filament/src/Panel/Concerns/HasUnsavedChangesAlerts.php:9` nasce
`false`, e nenhum painel do kit liga. Sair de um formulário preenchido sem salvar perde tudo, em
silêncio. É a necessidade por trás de RQ-03 e RQ-04, e o Filament já a resolve.

- **Path 1**: `app/Settings/ConfiguracoesDoKit.php`
  - propriedade `public bool $alerta_alteracoes_nao_salvas;` na seção **Kit**
  - linha em `mapaDeConfiguracao()`: `'alerta_alteracoes_nao_salvas' => 'kit.alerta_alteracoes_nao_salvas'`
  - **não** entra em `encrypted()` — não é segredo
- **Path 2**: `config/kit.php` — chave `alerta_alteracoes_nao_salvas`, lida com
  `BooleanoDoEnv::de('KIT_ALERTA_ALTERACOES_NAO_SALVAS', true)` (é o padrão do kit para env
  booleana; `(bool) env(...)` é o defeito que `.ai/rules/config.md` documenta)
- **Path 3**: `database/settings/2026_09_18_100000_add_alerta_alteracoes_nao_salvas_to_kit_settings.php`
  (migration **nova**, nunca a que já rodou)
- **Path 4**: os três `PanelProvider`:
  ```php
  ->unsavedChangesAlerts(fn (): bool => (bool) config('kit.alerta_alteracoes_nao_salvas'))
  ```
  **Closure, não escalar** — é o que torna a tela dona da decisão. `hasUnsavedChangesAlerts()`
  chama `$this->evaluate()` (`HasUnsavedChangesAlerts.php:18-21`), então a Closure é avaliada no
  render, depois do alinhamento do `KitServiceProvider`. É a mesma razão já documentada para
  `->brandName()` e `->colors()` em `AdminPanelProvider.php:70-77`.
- **Path 5**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — `Toggle` na aba **Kit**
  (`:743`), rótulo "Avisar sobre alterações não salvas", `helperText` dizendo que vale para os
  formulários dos três painéis.
- **Path 6**: `.env.example` — `KIT_ALERTA_ALTERACOES_NAO_SALVAS=true`
- **Logs**: nenhum.

### 3. Versão do SISTEMA no rodapé dos painéis (e a do kit, sob interruptor)

> Skills: `laravel-best-practices`, `tailwindcss-development`, `pest-testing`

- **Path 1**: `resources/views/filament/versao-do-kit.blade.php` (novo)
  - guard de visitante: renderiza vazio quando `! filament()->auth()->check()`. Existe porque o
    hook `FOOTER` também é emitido pelo layout `simple`
    (`vendor/filament/filament/resources/views/components/layout/simple.blade.php:61`), que é o
    das telas de autenticação — e versão exposta a visitante é mapa de CVE.
  - conteúdo: a versão do **sistema** (`config('app.version')`) e, sob `config('kit.exibir_versao')`, a do kit **rotulada** ao lado *(alterado em 2026-09-18: adendo 2 e adendo 3)* — com classes `fi-*` já compiladas na folha do
    Filament. **Nenhuma utilitária Tailwind nova** — o kit não tem `viteTheme()`, e
    `.ai/rules/css-filament.md` documenta o que acontece com classe não compilada.
- **Path 2**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` — método
  `configuraVersaoNoRodape()`, chamado por `configuraFilamentGlobal()`:
  ```php
  FilamentView::registerRenderHook(
      PanelsRenderHook::FOOTER,
      fn (): View => view('filament.versao-do-kit'),
  );
  ```
  **Sem `scopes:`** — mesma técnica e mesma justificativa de
  `configuraBotaoVoltarAoTopo()`: o `ViewManager` normaliza `null` para o bucket `''`, que o
  `renderHook()` lê em qualquer escopo. Vale para os três painéis e para qualquer painel que o
  projeto criar depois.
- **Fonte da versão**: `config('app.version')` — a do **produto**, editável na tela e semeada por
  `APP_VERSION`. `config('kit.version')` é a do **starter kit** e só aparece sob
  `config('kit.exibir_versao')`, que nasce desligada. Ver ADR-04 para por que não se lê `.git` em
  execução. *(alterado em 2026-09-18: adendo 2 — a redação anterior dava a versão do kit como
  fonte única, que é o defeito que o adendo corrigiu)*
- **Logs**: nenhum.

### 4. `ativo` na trilha de auditoria

> Skills: `laravel-best-practices`, `pest-testing`

**Problema**: `AuditsFillables::getAuditInclude()` devolve `getFillable()` (`:17`), e `ativo` não
é fillable em `User` (`:89-94`; o default vive em `$attributes`, `:106-108`). `desativar()` grava
com `forceFill(['ativo' => false])->save()` (`:307`) — o evento dispara, o auditor observa, e o
atributo é descartado pelo filtro. Desativar e reativar conta **não** aparecem em `/infra/audits`.

- **Path 1**: `app/Traits/AuditsFillables.php`
  ```php
  public function getAuditInclude(): array
  {
      return [...$this->getFillable(), ...$this->auditaAlemDoFillable()];
  }

  /**
   * Colunas auditáveis que NÃO são fillable.
   *
   * @return list<string>
   */
  protected function auditaAlemDoFillable(): array
  {
      return [];
  }
  ```
  A regra do docblock da trait continua a mesma — *"o que o usuário pode alterar é o que fica
  registrado"*. O que muda é o proxy: `getFillable()` sozinho não alcança coluna que só a Action
  altera.
- **Path 2**: `app/Models/User.php` — `auditaAlemDoFillable(): array { return ['ativo']; }`, com
  uma linha de comentário dizendo por que `ativo` não é fillable (a atribuição em massa a
  destrancaria).
- **Efeito colateral verificado**: os outros quatro models com a trait (`Tenant`, `Projeto`,
  `Convite`, `AgenteIa`) herdam o default vazio e **não mudam de comportamento**.
- **Logs**: nenhum — a linha de `audits` é o registro.

### 5. Correção dos comentários sobre `USER_MENU_BEFORE`

> Skills: nenhuma — é texto

**Problema**: quatro arquivos afirmam que `PanelsRenderHook::USER_MENU_BEFORE` "renderiza DENTRO
do dropdown do usuário". No Filament 5.7.6 instalado ele é emitido em
`vendor/filament/filament/resources/views/components/user-menu.blade.php:43`, **antes e fora** do
`<x-filament::dropdown>` que abre na linha 40; quem renderiza dentro é `USER_MENU_PROFILE_BEFORE`
(`:97`, `:110`, `:133`, `:148`).

A decisão de usar `GLOBAL_SEARCH_BEFORE` para o gatilho ⌘K continua certa por outro motivo (a
posição exata do campo de busca), mas a justificativa escrita está errada — e é exatamente o
padrão que `.ai/rules/specs.md` nomeia: *"a CONCLUSÃO estava certa por outro motivo. É isso que
torna o erro invisível"*.

- `app/Providers/Filament/AdminPanelProvider.php:316-317`
- `app/Providers/Filament/AppPanelProvider.php:469-470`
- `app/Providers/Filament/InfraPanelProvider.php:599-600`
- `resources/views/filament/user-menu-header.blade.php:7-10`

Cada um passa a citar `user-menu.blade.php:43` e `:97`.

### 6. Registro documental das dez decisões

> Skills: nenhuma — é documentação

- **Path 1**: `wikis/specs/.../07-dossies-dos-pacotes.md` (novo) — o dossiê consolidado: nome
  Composer real, idade, stars, downloads, constraint real de `filament/filament`, mecanismo,
  risco, veredito e motivo, um por pacote. É o que RQ-01 pede e o que sustenta as dez decisões.
- **Path 2**: `wikis/pacotes-candidatos.md` — seção nova "Rodada 2 — 2026-09-18", com a tabela dos
  dez vereditos e o link para o dossiê. Os recusados vão para a §4 com o motivo, que é o que
  aquela página exige na §5 (*"Pacote recusado fica aqui, na seção 4, com o motivo"*).
- **Path 3**: `wikis/pacotes-ranking.md` — a linha 70 (`alex-kramarenko/openapi-docs`) ganha o
  veredito e a condição de reabertura; os outros nove entram como avaliados.
- **Nomes Composer confirmados** (o slug da URL do diretório não é o nome, e nesta rodada **os dez
  divergiram**):

  | Slug do diretório | Nome Composer real |
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

### 10. Atualizar o Filament para a versão corrente da série

> Acrescentado pelo **Adendo 1** (RQ-14, RQ-15, RQ-16) e **exigido pelo achado QA-01**, Blocker do
> ciclo 1 do quality gate. Numerado 10 por ser posterior aos nove originais.

**Problema**: a primeira leitura do Adendo 1 verificou só a constraint (`^5.6`, que não trava) e
concluiu que RQ-14 estava satisfeita — sem notar que RQ-15 é uma cláusula **separada**, com
gatilho já disparado. `composer outdated "filament/*"` devolvia `5.7.6 ! 5.8.2`.

- **Comando**: `composer update "filament/*" --with-all-dependencies`
- **Resultado**: `filament/filament` **v5.7.6 → v5.8.2**, e as 9 dependências irmãs junto
- **Constraint**: **intocada** em `^5.6` — RQ-14 proíbe travar, e subir a constraint para `^5.8`
  seria travar as instalações que ainda estão na 5.7
- **Assets**: o `post-update-cmd` republica sozinho; `public/js/filament/**` e
  `public/fonts/filament/**` entram no diff
- **Oráculo**: `composer test` completo. Bump de minor de framework não tem outro
- **Logs**: nenhum — é operação de dependência

Ver ADR-08, inclusive para o limite de RQ-16: o `kit:update` **notifica** a atualização, não a
propaga, e o porquê disso ser deliberado.

### 7. Documentação de usuário, CHANGELOG e Blueprint

> Skills: nenhuma

- `docs/pt/` e `docs/en/` — a mudança visível ao usuário do kit é o alerta de alterações não
  salvas (campo novo na tela de configurações) e o rodapé com a versão. Entram na página de
  configurações do kit, nos dois idiomas.
- `CHANGELOG.md` — entrada nova, seguindo o formato Keep a Changelog já usado, com as seções
  Adicionado / Corrigido / Documentação.
- **Blueprint** (RQ-11): `composer bp:on`, rodar a aderência, `composer bp:off`. O estado
  commitado do `composer.json` fica **sempre desligado**, e
  `tests/Kit/BlueprintForaDoPacoteTest.php` reprova se escapar.

### 8. Verificação e revisão

> Skills: `pest-testing`

Ver `## Verificação Final`. O `/code-review` do step 7.5 roda sobre
`git diff main..HEAD` inteiro, por quem não implementou.

### 9. Quality gate e PR

`feature-quality-gate` → `06-relatorio-qa.md` → só então o PR.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> 1. Reutilizar código existente antes de criar novo — o passo 3 copia a técnica de
>    `configuraBotaoVoltarAoTopo()`; o passo 1 copia a expressão de cor do `UiAvatarsProvider`.
> 2. Usar stdlib do PHP/Laravel antes de código custom.
> 3. Usar features nativas antes de dependências — é literalmente a tese desta entrega: o passo 2
>    é uma chamada a um método que o Filament já tem.
> 4. Uma linha quando possível.
> 5. Mínimo código que funciona.
>
> Atalhos deliberados marcados com `ponytail:` comment.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-07), código, commits e PRs são boundary do Caveman.

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check` (phpstan)
- [ ] `composer filament:check` (filacheck — `.ai/rules` e o CLAUDE.md o exigem após tocar `app/Filament`)
- [ ] `vendor/bin/pest tests/Kit --compact` (CTs desta feature)
- [ ] `composer test:kit` — regressão obrigatória (a entrega toca infra compartilhada)
- [ ] `composer test:browser` — CT-B
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] **Custo medido** — queries do caminho principal contra o `## Modelo de Execução` (esperado: 0 query nova)
- [ ] `composer bp:on` → aderência → `composer bp:off` (RQ-11), com `composer.json` commitado desligado
- [ ] **`/code-review` no diff (step 7.5)**
- [ ] Citações `arquivo:símbolo:linha` reverificadas

## Commits

- `:sparkles: feat(privacidade): avatar padrao gerado localmente`
- `:sparkles: feat(formulario): alerta de alteracoes nao salvas`
- `:sparkles: feat(kit): versao no rodape dos paineis`
- `:bug: fix(auditoria): desativacao de conta na trilha`
- `:memo: docs(providers): corrige a afirmacao sobre USER_MENU_BEFORE`
- `:memo: docs(pacotes): rodada 2 de avaliacao de pacotes`
- `:memo: docs(wiki): wiki da feature estudo-de-pacotes-rodada-2`
