# Plano de Ação — Login social por painel

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wikis ancestrais**, e as três importam:
  - `wikis/specs/feat/login-social-google/login-social-google/` — criou `ConfiguracaoDoLogin`, as
    rotas globais e o render hook sem escopo de painel (ADR-05 de lá)
  - `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/` — generalizou para quatro
    provedores, criou `ProvedorSocial` e o blade único (ADR-08 de lá)
  - `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/…` — criou o contexto na sessão
    (`login_social.contexto`), que este plano estende (ADR-02 de lá)
- **Motivo**: a disponibilidade do provedor é global. Não há como oferecer um provedor no `/admin`
  e não no `/app`, e o destino do fluxo é fixo no `/app`.
- **Toca infra compartilhada?**: **sim**, e em três frentes:
  1. `App\Support\ConfiguracaoDoLogin` — consumida pelo blade, pelo controller e pela tela de settings
  2. `App\Settings\ConfiguracoesDoKit` — o mapa e uma migration de settings nova
  3. `LoginSocialController` — o fluxo de autenticação inteiro

  Regressão **obrigatória** contra os CT/CT-B das três wikis ancestrais.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Escolha de painéis por provedor | 1, 2, 6 | propriedade de settings + campo na tela |
| RQ-02 | A escolha é por provedor | 1 | uma propriedade por provedor, como as outras três |
| RQ-03 | Condição conjuntiva com as atuais | 3 | `disponivel()` ganha a terceira condição |
| RQ-04 | Botão não aparece em painel não autorizado | 4 | o blade passa o painel corrente |
| RQ-05 | A rota recusa painel não autorizado | 5 | validação no `redirecionar()` — é o ponto de segurança |
| RQ-06 | As premissas atuais intactas | 3 | as duas condições existentes não mudam de lugar |
| A1 (ampla) | Quem entra pelo `/admin` termina no `/admin` | 5 | o painel viaja pela sessão; os seis `getPanel('app')` são parametrizados |
| A2 | Default: todos os painéis | 1, 2 | a migration semeia com os três |
| A4 | Os três painéis na lista | 6 | `Paineis::opcoes()` já os devolve |

## Objetivo

Dar a cada provedor social uma lista de painéis em que ele vale, e fazer o fluxo terminar no painel
de origem. Hoje ligar o Google liga o Google nos três painéis e leva todo mundo ao `/app`; depois
desta entrega, o Google pode valer só no `/admin`, e quem entra por ele chega no `/admin`.

## Contexto

Duas limitações que se somam, e a segunda é a que torna a primeira inútil sozinha:

1. `ConfiguracaoDoLogin::disponivel()` decide por provedor e não recebe painel. O render hook dos
   botões é registrado **uma vez, sem escopo**, então a mesma lista sai nos três painéis.
2. O `LoginSocialController` termina sempre no `/app` — `Filament::getPanel('app')` em seis pontos.

O caso de uso do requisito (Google corporativo para o `/admin`, não para o `/app`) precisa das
duas: a permissão para o botão não aparecer onde não deve, e o destino para a entrada funcionar
onde deve.

## Análise dos Arquivos Existentes

### `app/Support/ConfiguracaoDoLogin.php`

- `disponivel(ProvedorSocial $provedor): bool` (`:64-76`) — duas condições conjuntivas. **Ganha um
  segundo parâmetro** `?string $painel = null`. O default nulo preserva todo chamador que não passa
  painel (a tela de settings, por exemplo, pergunta "está configurado?" sem painel).
- `disponiveis(): array` (`:86-93`) — o `array_filter` sobre `ProvedorSocial::cases()`. **Ganha o
  mesmo parâmetro** e o repassa.
- O docblock de `disponivel()` diz que a pergunta é por provedor "e isso é requisito". Continua
  verdade; a pergunta passa a ser **por provedor e painel**, e o docblock precisa dizer isso.

### `app/Support/Paineis.php`

- `opcoes(): array` (`:62-70`) — devolve `['admin' => '/admin', 'app' => '/app', 'infra' => '/infra']`
  de `Filament::getPanels()`. **É a lista do campo e a lista branca da validação.** Reuso direto,
  nenhuma constante nova. Painel novo no kit entra na escolha sozinho.

### `app/Settings/ConfiguracoesDoKit.php`

- Três propriedades por provedor hoje (`:153-175`), e o comentário de `:141-151` diz o contrato:
  "Três propriedades por provedor, uma linha por provedor no `mapaDeConfiguracao()`, e um par
  `add`/`addEncrypted` na migration. São os TRÊS lugares do contrato desta classe".
- **A quarta propriedade por provedor** entra no mesmo molde: `login_{provedor}_paineis`.
- `propriedadeDeSettings()` de `ProvedorSocial` (`:120`) monta o nome a partir do sufixo — o LinkedIn
  tem hífen no valor e sublinhado na propriedade. **Reusar**, não montar string à mão.
- **Não cai na armadilha do `registro_verificar_email`** (`:318-330`): aquela propriedade era lida no
  **boot do painel** e o toggle não fazia nada. Esta é lida **por request** — no render hook e no
  controller. Confirmado na revisão profunda.

### `app/Http/Controllers/Auth/LoginSocialController.php`

- `redirecionar()` (`:73-96`): `abort_unless(disponivel($provedor), 404)` (`:80`), monta
  `$contexto = $this->contextoDeCadastro()` e grava em `session('login_social.contexto')` (`:85`).
  **É aqui que o painel entra**, e é aqui que a validação de segurança vive.
- `retorno()` (`:108+`): `abort_unless(disponivel($provedor), 404)` (`:108`) e
  `session()->pull('login_social.contexto', [])` (`:136`).
- `confirmarVinculo()`: o terceiro `abort_unless` (`:477`).
- Os **seis** `Filament::getPanel('app')`: `:434`, `:466`, `:590`, `:645-648` (`urlDoPainel()`),
  `:666` (`urlDoPerfil()`), `:694`.

### `resources/views/filament/auth/botoes-sociais.blade.php`

- `:40` — `$provedores = ConfiguracaoDoLogin::disponiveis();`
- `:52` — o link: `route('auth.social.redirect', array_filter(['provedor' => …, 'org' => …, 'token' => …], 'is_string'))`
- **Duas edições**: passar o painel corrente para `disponiveis()` e acrescentar `painel` ao link.
- **Aviso do próprio arquivo**: comentário de blade **não** protege diretiva; nunca escrever nome de
  diretiva com arroba no comentário (`.ai/rules/views.md`).

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- A seção de login social, onde os três campos por provedor vivem. **Ganha um quarto campo**, e o
  docblock de `:636` já registra que `ConfiguracaoDoLogin::disponivel()` é quem decide — os campos
  só alimentam a config.

### `config/kit.php`

- `kit.login.{provedor}.habilitado` — o interruptor por provedor. **Ganha `paineis`** ao lado.
- Padrão de lista no `.env` que o kit já usa: `kit.convites.lembretes_dias` (`:640-643`),
  `array_values(array_filter(array_map('intval', explode(',', (string) env(…)))))`.

## Autorização

Esta feature **é** uma decisão de autorização, e o ponto mais sensível do plano:

- **O painel chega por query string** (`?painel=admin`), no link que o blade monta. Isso é
  **entrada do usuário** — alguém pode forjar `?painel=admin` com o Google autorizado só para o
  `app`. A validação no `redirecionar()` **não é conveniência, é a barreira** (RQ-05), e é o que
  `.ai/rules/filament.md:19-29` exige: a barreira é no servidor, não na tela que monta o link.
- **Duas validações, em ordem, e com destinos DIFERENTES** (corrigido pela pergunta A6 da
  derivação — a primeira versão desta seção dizia que as duas davam 404, contradizendo o código do
  passo 5b):
  - painel **inexistente** (`?painel=marketing`) → tratado como **ausente**, o fluxo segue no
    painel default. É indistinguível de link antigo sem `painel`, e nenhum dos dois é ataque.
  - painel real com provedor **não autorizado** nele → **404** com `warning`. É a barreira.
- Nenhuma policy, gate ou middleware novo. `canAccessPanel()` continua decidindo quem entra.

## Rotas

Nenhuma rota nova. As três de `routes/web.php:61-70` seguem iguais; muda o que o `redirect` aceita
na query.

| Método | URI | Name | Middleware | O que muda |
|---|---|---|---|---|
| GET | `auth/{provedor}/redirect` | `auth.social.redirect` | `throttle:10,1` | aceita `?painel=`, valida e guarda na sessão |
| GET | `auth/{provedor}/callback` | `auth.social.callback` | `throttle:10,1` | lê o painel da sessão e o usa como destino |
| GET | `auth/{provedor}/confirmar` | `auth.social.confirmar` | `throttle:10,1`, `signed` | idem |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Configurações do kit — seção de login social | Filament (Page de Settings) | `/admin/configuracoes-do-kit` | escolhe os painéis de cada provedor num campo de seleção múltipla | Não |
| Tela de login dos três painéis | Blade (render hook) | `/admin/login`, `/app/login`, `/infra/login` | vê ou não vê o botão do provedor | Não |
| Tela de registro | Blade (render hook) | `/app/register` | idem | Não |

**Gate de CT-B**: **não passa.** Todas as afirmações são "o botão aparece / não aparece" e "a
gravação persistiu" — provadas por componente Livewire e por requisição HTTP, em milissegundos.
Nada de JavaScript executado, cor, layout ou acessibilidade.

**Gate de tela de escrita**: a Page de Settings é tela de escrita. O `04` precisa de um cenário de
**gravação por componente** (`fillForm` → `->call('save')` → asserção sobre o valor gravado), e não
apenas de visita.

## Variáveis de Ambiente

Uma por provedor, semeando o settings. Default vazio, que a config traduz para "todos" (A2).

| Key | Default | Descrição |
|---|---|---|
| `KIT_SOCIALITE_GOOGLE_PAINEIS` | vazio = todos | lista separada por vírgula: `admin,app,infra` |
| `KIT_SOCIALITE_GITHUB_PAINEIS` | vazio = todos | idem |
| `KIT_SOCIALITE_LINKEDIN_PAINEIS` | vazio = todos | idem |
| `KIT_SOCIALITE_X_PAINEIS` | vazio = todos | idem |

> **Vazio significa "todos", e isso é decisão registrada (ADR-04)**, não descuido: preserva o
> comportamento atual num update e evita que apagar o valor no `.env` tranque o login social.

## Eventos / Listeners / Observers

Nenhum novo. A trilha de auditoria das configurações já existe
(`App\Listeners\AuditarConfiguracoesDoKit`, ouvindo `SavingSettings`) e passa a registrar a
propriedade nova sozinha.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Login social (as três wikis ancestrais)**: `disponivel()` ganha parâmetro com default nulo, e
  `disponiveis()` idem — nenhum chamador atual quebra. Rodar
  `tests/Kit/LoginSocialGoogleTest.php`, `tests/Kit/LoginSocialProvedoresTest.php`,
  `tests/Kit/LoginSocialContaIndisponivelTest.php`, `tests/Kit/VinculoDeProvedorSocialTest.php` e
  os CT-B de `tests/Browser/`.
- **Cadastro por convite e organização**: o `login_social.contexto` da sessão ganha uma chave. O
  `org`/`token` continuam intactos. Rodar os testes daquela wiki.
- **Settings do kit**: propriedade nova exige migration nova, **nunca** editar a que já rodou —
  instalação de terceiro que só roda `migrate` ficaria sem a linha e `aplicarNaConfig()` estouraria
  `MissingSettings` no boot de **todo** request (`ConfiguracoesDoKit.php:66-68`).
- **O destino do fluxo muda** para quem usa login social hoje: quem entrava no `/app` por um botão
  na tela do `/admin` passa a chegar no `/admin`. **É a correção pedida**, mas é mudança de
  comportamento e vai no `CHANGELOG` como tal.
- **`tests/Kit/ConfiguracoesDoKitTest.php`**: há um caso que assere que **todas** as propriedades
  declaradas na classe são semeadas (`:305`) e outro que conta as propriedades do mapa. Os dois
  precisam ver a propriedade nova.

## Rollback

- `migrate:rollback` na migration de settings nova remove as quatro propriedades. Com elas fora, o
  `.env` volta a ser a fonte, e o default vazio = todos os painéis restaura o comportamento atual.
- O código: reverter o parâmetro de `disponivel()`/`disponiveis()` e os seis pontos de destino.
- Sem dado a migrar de volta: a propriedade é configuração, não histórico.

## Dependências

Nenhuma nova.

## Riscos

- **O painel vem da query, e é entrada do usuário.** Mitigação: a validação dupla no
  `redirecionar()` (painel existente + provedor autorizado nele), com caso de teste que forja a
  query — é o RQ-05 e o cenário que mais importa do `04`.
- **A sessão pode não ter o painel** (link colado direto, sessão expirada entre o redirect e o
  callback, cookie perdido). Mitigação: o `retorno()` cai no painel **default** do Filament quando
  a sessão não traz painel, e isso é o comportamento de hoje (`app` é o default). Nunca lançar.
- **Os seis pontos de `getPanel('app')` não são iguais**: quatro são "volta ao login", um é o
  destino de sucesso e um é o perfil. Parametrizar em bloco sem ler cada um é o caminho para um
  redirecionamento errado num caminho de erro. Mitigação: o passo 5 trata um por um.
- **Regressão de tenancy**: o `/app` com tenancy resolve a organização default depois do login
  (`:407`, `urlDoPerfil()` em `:666` usa `$painel->hasTenancy()`). Painel de destino diferente do
  `app` não tem tenancy, e o código precisa continuar correto nos dois. Mitigação: cenários na
  suíte `tests/Tenancy`.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php:132-139` tem **`autenticacao`**, e é o canal de todo o login social:
`LoginSocialController` escreve nele em todos os pontos (`:87` no redirect, e nos retornos). É o
canal certo, e é lido pelo Logs Explorer do `/infra`.

### Decisão

**Reutilizar `autenticacao`.** Nada em `config/logging.php` muda.

Dois pontos de log novos, os dois no caminho de decisão:

1. **Recusa por painel não autorizado** (`warning`) — no `redirecionar()`, antes do 404. É o evento
   que interessa auditar: alguém tentou usar um provedor num painel onde ele não vale.
2. **Painel de destino escolhido** (`info`) — no `retorno()`, junto do log de sucesso que já existe,
   acrescentando o painel ao contexto. Não é log novo: é campo novo no que já existe.

**Nada no render hook.** O blade chama `disponiveis()` a cada tela de login renderizada; logar ali
produziria uma linha por visita a `/login`, e o canal já tem a nota de ruído medida em 1,1 MB/dia.

## Estrutura de Implementação

### 1. `config/kit.php` — a chave `paineis` por provedor

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `config/kit.php`, no bloco `login` (perto de `kit.login.{provedor}.habilitado`)
- Uma chave por provedor, na forma que o kit já usa para lista no `.env`
  (`kit.convites.lembretes_dias`, `:640-643`):

  ```php
  'paineis' => array_values(array_filter(array_map(
      'trim',
      explode(',', (string) env('KIT_SOCIALITE_GOOGLE_PAINEIS', '')),
  ))),
  ```

- **Vazio devolve `[]`**, e `[]` significa **todos** — a tradução é de quem lê (passo 3), não da
  config. Ver ADR-04.
- **Não usar `BooleanoDoEnv`**: não é booleano. E não usar `NumeroDoEnv`: não é número.
- **Logs**: nenhum (arquivo de config).
- **`.env.example`**: as quatro chaves, vazias, ao lado das que já existem por provedor
  (`:194-235`).

### 2. `ConfiguracoesDoKit` — a quarta propriedade por provedor

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php`
- **Os três lugares do contrato da classe** (`:60-68`), e nenhum a menos:
  1. **A propriedade**, uma por provedor, ao lado das três existentes (`:153-175`):

     ```php
     /** @var array<int, string> Painéis em que este provedor vale. Vazio = todos. */
     public array $login_google_paineis;
     ```

     (idem `login_github_paineis`, `login_linkedin_openid_paineis`, `login_x_paineis` — o nome sai
     de `ProvedorSocial::propriedadeDeSettings('paineis')`, que trata o hífen do LinkedIn)
  2. **A linha no `mapaDeConfiguracao()`** (`:282-376`), no bloco de cada provedor:
     `'login_google_paineis' => 'kit.login.google.paineis',`
  3. **Uma migration de settings NOVA** — `database/settings/2026_09_02_100000_add_paineis_do_login_social_to_kit_settings.php`,
     com `add()` por propriedade, semeando de `config("kit.login.{$provedor->value}.paineis")`, e
     `deleteIfExists()` no `down()`. **Nunca** editar
     `2026_08_25_000000_add_provedores_sociais_to_kit_settings.php`, que já rodou.
- **Fora de `encrypted()`**: não é segredo.
- **Logs**: nenhum novo — `aplicarNaConfig()` já loga o alinhamento no canal `configuracoes`.

### 3. `ConfiguracaoDoLogin` — a terceira condição, conjuntiva

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/ConfiguracaoDoLogin.php:64-93`

```php
/**
 * O botão e as rotas deste provedor entram no ar — e, quando o painel é informado, entram
 * NESTE painel?
 *
 * Três condições em conjunção, e a terceira é a desta wiki. As duas primeiras não mudaram de
 * lugar nem de ordem: o interruptor desligado é escolha de quem instalou, a credencial vazia é
 * descuido de quem configurou, e o painel não autorizado é escolha de quem configurou — três
 * motivos diferentes para a mesma resposta.
 *
 * `$painel` nulo significa "não estou perguntando por painel", e é o que a tela de settings
 * quer: ela pergunta se o provedor está CONFIGURADO, não se vale num painel. Manter o default
 * nulo é o que preserva todo chamador anterior a esta wiki.
 *
 * Lista vazia significa TODOS os painéis, não nenhum — ver ADR-04. Um provedor recém-semeado,
 * ou um `.env` com a chave apagada, continua valendo onde valia.
 */
public static function disponivel(ProvedorSocial $provedor, ?string $painel = null): bool
{
    if (! config("kit.login.{$provedor->value}.habilitado")) {
        return false;
    }

    /** @var array<string, mixed> $credenciais */
    $credenciais = config('services.'.$provedor->value, []);

    if (blank($credenciais['client_id'] ?? null)
        || blank($credenciais['client_secret'] ?? null)
        || blank($credenciais['redirect'] ?? null)) {
        return false;
    }

    return $painel === null || self::painelAutorizado($provedor, $painel);
}

/**
 * Este provedor vale neste painel?
 *
 * Lista vazia = todos. `in_array` estrito porque a lista vem de config e de settings, e uma
 * comparação frouxa casaria `0 == 'admin'`.
 */
public static function painelAutorizado(ProvedorSocial $provedor, string $painel): bool
{
    /** @var array<int, string> $paineis */
    $paineis = (array) config("kit.login.{$provedor->value}.paineis", []);

    return $paineis === [] || in_array($painel, $paineis, true);
}

/**
 * Os provedores no ar agora — no painel informado, quando informado.
 *
 * @return array<int, ProvedorSocial>
 */
public static function disponiveis(?string $painel = null): array
{
    return array_values(array_filter(
        ProvedorSocial::cases(),
        static fn (ProvedorSocial $provedor): bool => self::disponivel($provedor, $painel),
    ));
}
```

- **A inversão do `filled()` para `blank()`** nas credenciais é para poder acrescentar a terceira
  condição sem uma expressão de quatro termos. **Mesma semântica** — há caso de teste por provedor
  com cada credencial vazia, e eles são a guarda dessa reescrita.
- `painelAutorizado()` é **pública** porque o passo 5 a chama direto para a validação de segurança,
  onde a resposta precisa distinguir "provedor desligado" de "painel não autorizado" no log.
- **Logs**: nenhum aqui. Este método é chamado por linha de botão renderizado; o log da recusa vive
  no controller (passo 5), onde há um ato deliberado a auditar.

### 4. O blade dos botões passa o painel corrente

> Skills: `tailwindcss-development` (é Blade), `ponytail`

- **Path**: `resources/views/filament/auth/botoes-sociais.blade.php:39-41` e `:52`
- No bloco `@php`:

  ```php
  $painel      = filament()->getCurrentOrDefaultPanel()?->getId();
  $provedores  = \App\Support\ConfiguracaoDoLogin::disponiveis($painel);
  ```

- No link, acrescentar `'painel' => $painel` ao array do `route()`, dentro do `array_filter(…, 'is_string')`
  que já está lá — painel nulo simplesmente não vai na query.
- **`getCurrentOrDefaultPanel()` e não `getCurrentPanel()`**: é o padrão do kit em tela de auth
  (`TelaLogin.php:83`, `TelaBloqueio.php:90`, `perfil-indicator.blade.php:32`), e o `?->` evita
  `Error` se o painel não estiver resolvido.
- **Atenção do próprio arquivo**: comentário de blade **não** protege diretiva — não escrever nome
  de diretiva com arroba nos comentários (`.ai/rules/views.md`; já derrubou três telas com `ParseError`).
- **Logs**: nenhum.

### 5. `LoginSocialController` — valida o painel, carrega na sessão, usa como destino

> Skills: `laravel-best-practices`, `laravel-specialist`

- **Path**: `app/Http/Controllers/Auth/LoginSocialController.php`
- **5a. `redirecionar()` (`:73-96`) — a barreira.** Depois do `abort_unless` existente:

  ```php
  $painel = $this->painelDaRequisicao();

  if ($painel !== null && ! ConfiguracaoDoLogin::painelAutorizado($provedor, $painel)) {
      Log::channel('autenticacao')->warning(
          "[LoginSocialController@redirecionar] Provedor não autorizado neste painel | provedor: {$provedor->value} - painel: {$painel} - ip: ".request()->ip(),
          [
              'ip'       => request()->ip(),
              'provedor' => $provedor->value,
              'painel'   => $painel,
              'motivo'   => 'painel_nao_autorizado',
          ],
      );

      abort(404);
  }

  $contexto = $this->contextoDeCadastro() + array_filter(['painel' => $painel]);
  ```

  O `$contexto` continua indo para `session('login_social.contexto')` na linha que já existe — o
  painel viaja junto do `org`/`token`, no mecanismo que a wiki `cadastro-social-por-convite-e-organizacao`
  criou.

- **5b. `painelDaRequisicao(): ?string`** — método privado novo, a lista branca:

  ```php
  /**
   * O painel pedido na query, se ele existe de fato.
   *
   * Devolve `null` para query ausente E para painel inexistente — o chamador trata os dois do
   * mesmo jeito, porque a diferença não muda a resposta: sem painel válido, o fluxo segue no
   * painel default, que é o comportamento anterior a esta wiki.
   *
   * `Paineis::opcoes()` é a lista branca, e ela sai de `Filament::getPanels()` — painel novo no
   * kit entra aqui sozinho, e string forjada na query não passa.
   */
  private function painelDaRequisicao(): ?string
  {
      $painel = request()->query('painel');

      return is_string($painel) && array_key_exists($painel, Paineis::opcoes())
          ? $painel
          : null;
  }
  ```

- **5c. O destino.** `urlDoPainel()` (`:645-648`) passa a resolver o painel da sessão:

  ```php
  private function urlDoPainel(?string $painel = null): string
  {
      return $this->painel($painel)->getUrl() ?? url('/');
  }

  /** O painel de destino: o da sessão quando válido, o default do Filament quando não. */
  private function painel(?string $id): Panel
  {
      return $id !== null && array_key_exists($id, Paineis::opcoes())
          ? Filament::getPanel($id)
          : Filament::getDefaultPanel();
  }
  ```

  **Revalidar aqui, e não confiar na sessão**: a sessão é do próprio usuário e o valor já foi
  validado na ida, mas revalidar custa um `array_key_exists` e fecha a porta de uma sessão
  manipulada. `getPanel()` com id inexistente lança.

- **5d. Os seis pontos, um por um** — o painel lido da sessão (`$contexto['painel'] ?? null`) chega
  a cada um. **Não é substituição cega**:

  | Linha | Hoje | Depois | Por quê |
  |---|---|---|---|
  | `:645-648` | `getPanel('app')->getUrl()` | painel da sessão | é o destino de sucesso — o coração de A1 |
  | `:666` | `getPanel('app')` em `urlDoPerfil()` | painel da sessão | o perfil é o do painel onde a pessoa entrou; o `hasTenancy()` de lá continua decidindo a organização |
  | `:434` | volta ao login do `app` | login do painel da sessão | quem foi recusado volta para onde tentou entrar |
  | `:466` | idem | idem | idem |
  | `:590` | aviso de conta indisponível → login do `app` | login do painel da sessão | o `ContaIndisponivelController::redirecionar()` recebe a URL; muda o argumento, não ele |
  | `:694` | volta ao login do `app` | login do painel da sessão | idem |

- **5e. `retorno()` e `confirmarVinculo()`**: o `abort_unless(disponivel($provedor), 404)` de `:108`
  e `:477` **fica sem painel**. Motivo: no callback o painel vem da **sessão**, não da requisição, e
  a autorização já foi decidida na ida. Reconferir com o painel da sessão seria correto também, mas
  transformaria uma configuração alterada **no meio do fluxo** num 404 no callback, depois de a
  pessoa já ter autenticado no provedor — pior UX pelo mesmo nível de segurança. **Registrar como
  decisão** (ADR-05).
- **Logs**: o `warning` de 5a; e o painel de destino acrescentado ao **contexto** dos logs de
  sucesso que já existem no `retorno()`.
- **Imports novos**: `App\Support\Paineis` e `Filament\Panel` — confirmar na revisão profunda o que
  já está importado.

### 6. A tela de settings ganha o campo por provedor

> Skills: `livewire-development`, `laravel-best-practices`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`, na seção de login social
- Um campo por provedor, ao lado dos três que já existem:

  ```php
  Select::make(ProvedorSocial::Google->propriedadeDeSettings('paineis'))
      ->label('Painéis')
      ->helperText('Vazio = todos os painéis.')
      ->multiple()
      ->options(Paineis::opcoes())
      ->visible(fn (Get $get): bool => (bool) $get(ProvedorSocial::Google->propriedadeDeSettings('habilitado'))),
  ```

- **`->visible()` casado com o toggle do provedor**, como os campos de credencial já fazem — o campo
  de painéis não faz sentido com o provedor desligado. É UX; a barreira é a do passo 5.
- **`Paineis::opcoes()` como `options()`**: rótulo é o path (`/admin`), chave é o id (`admin`).
- **Logs**: nenhum — a gravação já é auditada por `AuditarConfiguracoesDoKit`.

### 7. Documentação e changelog

> Skills: nenhuma específica

- **Path**: `docs/pt/autenticacao/login-social.md` — uma subseção sobre a escolha de painéis, o
  default "vazio = todos" e o destino passar a ser o painel de origem
- **Path**: `docs/en/autenticacao/login-social.md` — a mesma, em inglês (paridade exigida por
  `tests/Kit/SiteDeDocumentacaoTest.php`, CT-04/CT-05)
- **Path**: `CHANGELOG.md` → `[Unreleased]` → `### Adicionado` (a escolha) **e** `### Alterado`
  (o destino passa a ser o painel de origem — mudança de comportamento)
- **Não** tocar `docs/*/recursos/configuracoes-do-kit.md` nem `docs/*/comecar/instalacao-avancada.md`
  sem checar antes: outras branches os editaram nesta rodada

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**.
> 1. Reutilizar antes de criar — `Paineis::opcoes()` é a lista e a validação;
>    `ProvedorSocial::propriedadeDeSettings()` monta os nomes; o `login_social.contexto` da sessão
>    já existe; o canal `autenticacao` já existe
> 2. Nenhuma constante de painéis escrita à mão — ela divergiria de `Filament::getPanels()`
> 3. Nenhuma tabela nova: é configuração, e o settings do kit é onde configuração mora
> 4. `painelAutorizado()` é o único método novo em `ConfiguracaoDoLogin`, e existe porque o
>    controller precisa distinguir as duas recusas no log
>
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo** na conversa; wiki, código e commits são boundary.

## Mapeamentos

| Provedor | Valor do enum | Propriedade de settings | Chave de config | `.env` |
|---|---|---|---|---|
| Google | `google` | `login_google_paineis` | `kit.login.google.paineis` | `KIT_SOCIALITE_GOOGLE_PAINEIS` |
| GitHub | `github` | `login_github_paineis` | `kit.login.github.paineis` | `KIT_SOCIALITE_GITHUB_PAINEIS` |
| LinkedIn | `linkedin-openid` | `login_linkedin_openid_paineis` | `kit.login.linkedin-openid.paineis` | `KIT_SOCIALITE_LINKEDIN_PAINEIS` |
| X | `x` | `login_x_paineis` | `kit.login.x.paineis` | `KIT_SOCIALITE_X_PAINEIS` |

> O hífen do LinkedIn é o nome do driver do Socialite e **precisa** casar na chave de config; a
> propriedade usa sublinhado porque PHP não aceita hífen. A tradução é
> `ProvedorSocial::propriedadeDeSettings()` — a única do desenho, e não se escreve outra.

## Testes

> Ver `04-casos-de-teste.md` — a ser derivado pela `feature-test-design` a partir do
> `00-requisito.md`. Sem `05`: nenhum cenário exige navegador (ver o gate na `## Superfície de UI`).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `php artisan test --compact tests/Kit/LoginSocial*Test.php tests/Kit/VinculoDeProvedorSocialTest.php`
- [ ] `php artisan test --compact tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/ConfiguracoesDoKitTelaTest.php`
- [ ] `php artisan test --compact --testsuite=Tenancy`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] À mão: deixar o Google só no `/admin`, confirmar que o botão desapareceu do `/app/login`,
      confirmar que `?painel=app` na query responde 404, e confirmar que a entrada pelo `/admin`
      termina no `/admin`

## Commits

- `✨ feat(login-social): cada provedor escolhe em quais painéis vale`
- `✨ feat(login-social): o fluxo termina no painel de origem, não no /app fixo`
- `📝 docs(login-social): documenta a escolha de painéis nos dois idiomas`
- `📝 docs(wiki): wiki da feature login-social-por-painel`
