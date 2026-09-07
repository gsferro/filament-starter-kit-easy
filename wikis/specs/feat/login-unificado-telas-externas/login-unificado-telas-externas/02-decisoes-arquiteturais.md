# Decisões Arquiteturais — Telas externas com o login unificado

> Refinam a wiki ancestral `wikis/specs/feat/login-unificado/login-unificado/02-decisoes-arquiteturais.md` (ADR-01, ADR-03, ADR-05, ADR-08).

## ADR-01: Os cartões nascem de `Filament::getPanels()` e leem rótulo e ícone da mesma fonte que o Panel Switch

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

A ADR-01 da ancestral escolheu cartões em vez do Panel Switch porque o pacote não tem modo página. Os cartões, porém, foram escritos como uma lista fixa de três (`app/Support/Paineis.php:cartoes():317-337`), com rótulos e ícones **diferentes** dos que o Panel Switch mostra (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraPanelSwitch():325-345`). O requisito pede paridade (RQ-01) e lista dinâmica (RQ-02); o `projeto-3` tem cinco painéis e precisou editar o arquivo à mão.

O Panel Switch decide a lista em `vendor/bezhansalleh/filament-panel-switch/src/PanelSwitch.php:getPanels():242-262`: `Filament::getPanels()` filtrado por `canAccessPanel()`, rótulo de `labels()` com fallback `str($id)->ucfirst()` e ícone de `icons()` com fallback `heroicon-o-square-2-stack` (`resources/views/panel-switch-menu.blade.php:33-34`). Não há `excludes()` nem `visible()` na 3.1.0.

### Decisão

- `Paineis::rotulos()` e `Paineis::icones()` passam a ser a **única** fonte dos dois mapas; `configuraPanelSwitch()` os consome; `Paineis::rotulo(Panel)` e `Paineis::icone(Panel)` aplicam **os mesmos fallbacks do Panel Switch**.
- `Paineis::cartoes()` itera `Filament::getPanels()`. Descrição e cor ficam num mapa local só dos painéis do kit; painel novo vem sem descrição e com cor `gray`.
- O rótulo do `app` passa a ser `config('app.name')` e os ícones passam a ser os do Panel Switch (`heroicon-o-rocket-launch`, `heroicon-o-wrench-screwdriver`, `heroicon-o-server-stack`) — o preço da paridade que o requisito pede.

### Alternativas Consideradas

1. **Ler `labels()`/`icons()` de volta do próprio `PanelSwitch`** — o objeto é configurado por `configureUsing()` e só existe dentro de um painel bootado com o render hook; fora do painel (a escolha roda sob `panel:app`, mas antes do hook) não há instância a consultar. Descartada.
2. **Manter os cartões com textos próprios e só tornar a lista dinâmica** — atende RQ-02 mas não RQ-01 ("as mesmas opções"). Descartada.
3. **Config `kit.paineis.*` com rótulo/ícone/descrição por painel** — terceira fonte da verdade para a mesma coisa; quem adiciona painel teria de editar provider **e** config. Descartada (YAGNI; o mapa em `Paineis` já é o ponto único).

### Consequências

- **Positivas**: painel novo aparece na escolha e na boas-vindas sem tocar o kit; um só lugar para rótulo e ícone; o Panel Switch e a escolha nunca mais divergem.
- **Negativas**: texto visível muda ("Painel do negócio" → nome da aplicação); testes de rótulo literal atualizados; instalações que customizaram `cartoes()` têm conflito no `kit:update`.
- **Riscos**: `config('app.name')` vazio produz cartão sem rótulo — mitigado pelo fallback `ucfirst(id)` quando o mapa devolve string vazia (CT).

### Referências

- `vendor/bezhansalleh/filament-panel-switch/src/PanelSwitch.php:getPanels():242-262`, `getLabels():193-196`, `getIcons():166-175`
- `vendor/filament/support/src/Concerns/HasIcon.php:icon():15` — `CardItem` aceita a string do ícone
- Refina: ADR-01 da ancestral

---

## ADR-02: `/cadastro` e `/esqueci-minha-senha` seguem o molde de `/login` — subclasse com `ehAPaginaUnica()`, rota no `KitServiceProvider`, redirect da rota do painel

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

RQ-06 pede `/esqueci-minha-senha`; RQ-04 (premissa (b) do `00`) leva o registro ao mesmo tratamento. A ancestral já resolveu o problema idêntico para o login: página servida fora dos painéis sob `panel:app` (ADR-08), guarda de laço em `mount()` por método sobrescrevível (`.ai/rules/auth.md:15-16`), rota registrada sempre no provider (ADR-05, `.ai/rules/providers.md:8`).

Os links da tela de login vêm do Filament: `Login::registerAction()` usa `filament()->getRegistrationUrl()` (`vendor/filament/filament/src/Auth/Pages/Login.php:registerAction():367-373`) e o hint do campo de senha usa `filament()->getRequestPasswordResetUrl()` (`getPasswordFormComponent():300-309`). Ambos resolvem pelo painel **corrente** — sob `panel:app`, sempre `/app/...`.

### Decisão

- `CadastroUnificado extends RegistroPorConvite` e `TelaRecuperarSenhaUnificada extends TelaRecuperarSenha`, cada uma com `$layout` redeclarado, `ehAPaginaUnica(): true` e `mount()` que devolve à rota do painel quando a chave está desligada.
- As classes-mãe ganham a guarda inversa no `mount()` (chave ligada e não é a página única → redirect para a rota única), **preservando a query** (`token`, `org`).
- `TelaLogin` sobrescreve `registerAction()` e `getPasswordFormComponent()` para apontar os links pela chave (`RegistroAberto::urlDoCadastro()`, `TelaLogin::urlDeRecuperacaoDeSenha()`). Vive em `TelaLogin`, não só na `Unificada`, porque a decisão é pela chave e a classe do painel redireciona antes de renderizar de qualquer forma.
- Redefinição de senha (link do e-mail) e verificação de e-mail **ficam por painel**: chegam por
  link assinado ou já autenticadas, e o requisito não as citou. *(alterado em 2026-09-07: o
  painel do link passou a ser o **da pessoa**, não o `app` da rota — ver ADR-05.)*
- As três páginas únicas mandam quem **já entrou** para o destino da regra de login, e não para
  `Filament::getUrl()` do painel corrente. *(acrescentado em 2026-09-07: o `mount()` do vendor
  fazia o segundo, e um `admin` tomava 403 no `/app`.)*

### Alternativas Consideradas

1. **Sobrescrever `getRegistrationUrl()`/`getRequestPasswordResetUrl()` no `Panel`** — são métodos do `Panel`, não da página; exigiria subclasse de `Panel` ou macro, e mudaria as URLs em todos os consumidores (e-mails inclusive). Descartada.
2. **Um controller que só redireciona `/cadastro` → `/app/register`** — a URL final continuaria `/app/register`, que é o que RQ-06 pede para não acontecer no reset. Descartada.
3. **Um `Route::redirect` do painel para a única em vez da guarda no `mount()`** — a rota do painel é registrada pelo Filament; interceptá-la por rota exigiria prioridade de registro e não vale dentro do `/livewire/update`. A guarda no `mount()` é o padrão medido da ancestral. Descartada.

### Consequências

- **Positivas**: URLs sem prefixo de painel para as três entradas públicas (`/login`, `/cadastro`, `/esqueci-minha-senha`); um padrão só; desligar a chave devolve tudo às rotas do painel.
- **Negativas**: dois arquivos novos pequenos; um salto extra em e-mails de convite antigos (`/app/register?token=` → `/cadastro?token=`); o broker de senha da página única é o do painel default.
- **Riscos**: laço se a subclasse esquecer `ehAPaginaUnica()` — coberto pela regra de auth e por CT nos dois sentidos.

### Referências

- `app/Filament/Pages/Auth/TelaLogin.php:mount():60-73`, `app/Filament/Pages/Auth/TelaLoginUnificada.php:mount():35-54` — o molde
- `app/Providers/KitServiceProvider.php:configureLoginUnificado():527-546`
- Refina: ADR-03, ADR-05 e ADR-08 da ancestral

---

## ADR-03: O slug muda por `$slug`, com redirect do antigo; o nome do arquivo de doc e a tag de auditoria não mudam

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

O Filament deriva o slug do nome da classe (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:getDefaultSlug():74-83`) e o nome da rota do slug (`getRelativeRouteName():56-58`). A página já se chama "Configurações da aplicação" no título e no menu (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:83-85`); só a URL ficou.

Ninguém chama `ConfiguracoesDoKit::getUrl()`; 20 linhas de teste, o inventário de telas e `ConfiguracoesDoKitDocumentacaoTest` batem na string literal. A string `configuracoes-do-kit` também é (a) a **tag de auditoria** gravada em `audits` (`app/Listeners/AuditarConfiguracoesDoKit.php:140`) e (b) o **nome do arquivo** `docs/{pt,en}/recursos/configuracoes-do-kit.md`, travado por `SiteDeDocumentacaoTest` CT-22 (links internos) e `RedeDeDocumentacaoTest:119-120`.

### Decisão

- `protected static ?string $slug = 'configuracoes-da-aplicacao';` — sem renomear a classe (renomear arrastaria a classe de Settings homônima, o listener e 30 imports).
- `Route::redirect('/admin/configuracoes-do-kit', '/admin/configuracoes-da-aplicacao', 301)` no `KitServiceProvider`, uma linha — favoritos e links internos de instalações reais não quebram *(premissa RQ-03)*.
- Tag de auditoria e nome do arquivo de doc **ficam**: um é dado já gravado (mudar quebra a consulta às trilhas antigas), o outro é identidade do site (mudar quebra links externos e dois testes). Só o **texto** das URLs nas docs muda.

### Alternativas Consideradas

1. **Renomear a classe para `ConfiguracoesDaAplicacao`** — slug certo de graça, mas colide com a semântica de `App\Settings\ConfiguracoesDoKit`, o listener e as permissões geradas pelo Shield (`page_ConfiguracoesDoKit`), que estão gravadas no banco das instalações. Descartada.
2. **Sem redirect** — a URL antiga só existiu em menu; mas o `projeto-3` é real e tem gente com favorito. Uma linha resolve. Mantida como premissa reversível.

### Consequências

- **Positivas**: URL coerente com o nome; troca cirúrgica; permissões do Shield intactas.
- **Negativas**: ~50 ocorrências de texto a atualizar (app, tests, docs, READMEs); `ConfiguracoesDoKitDocumentacaoTest` muda junto com os textos que ele vigia.
- **Riscos**: esquecer uma URL literal em teste → o próprio teste acusa.

### Referências

- `vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:12,56-58,74-83`
- Doc oficial Filament 5, "Customizing the page URL" (`search-docs`): `protected static ?string $slug`

---

## ADR-04: Com tenancy, o link de cadastro do login só aparece com organização resolvível, e a carrega

**Status**: Aceita
**Data**: 2026-09-06

### Contexto

A ADR-07 de `registro-e-aprovacao` decidiu que, com tenancy, a organização de destino vem por `?org={slug}` e a tela recusa sem ela — de propósito, com a mesma mensagem do convite inválido para não revelar qual condição falhou. Mas a tela de login mostra "Cadastre-se" sempre que `RegistroAberto::habilitado()` (`app/Filament/Pages/Auth/TelaLogin.php:getSubheading():90-97`), apontando para `/app/register` **sem** `?org=`. Com tenancy, esse link é um beco sem saída — foi exatamente o que o `projeto-3` viu (cinco recusas `convite_invalido` no log), e vale para `/app/login` desde antes do login unificado.

### Decisão

- O link só aparece quando `RegistroAberto::habilitado()` **e** (sem tenancy, ou `RegistroAberto::organizacao(request()->query('org'))` resolve). Quando aparece, carrega `?org=` (via `RegistroAberto::urlDoCadastro($org)`).
- A recusa e a mensagem **não** mudam (ADR-07 da outra wiki continua valendo).
- Quando o registro está ligado e o link foi ocultado por falta de organização, um `info` no canal `autenticacao` explica — hoje ninguém sabe por que o link "sumiu".
- A doc de registro aberto passa a dizer: com tenancy, divulgue `/login?org={slug}` (ou `/cadastro?org={slug}`); a organização precisa aceitar cadastro público.

### Alternativas Consideradas

1. **Mudar a mensagem para "o cadastro precisa do link da organização"** — contraria a decisão de não revelar a condição que falhou. Descartada.
2. **Escolher a organização na própria tela de registro (select)** — registro aberto listaria as organizações existentes para quem não está logado: vazamento de nomes. Descartada.
3. **Não mexer: documentar apenas** — deixa o link levar à recusa. Descartada.

### Consequências

- **Positivas**: fim do beco sem saída; o `?org=` atravessa login → cadastro; o `projeto-3` resolve com o toggle da organização.
- **Negativas**: instalação com tenancy sem `?org=` na URL de login não vê o link — é o comportamento correto, mas precisa estar na doc.
- **Riscos**: `?org=` com slug de organização inexistente na URL de login — o link some, sem erro; coberto por CT.

### Referências

- `app/Filament/Pages/Auth/RegistroPorConvite.php:mount():102-151`, `recusar():375-431`
- `app/Support/RegistroAberto.php:organizacao():136-147`
- `wikis/specs/feat/registro-e-aprovacao/registro-e-aprovacao/02-decisoes-arquiteturais.md:434-462` (ADR-07)
- `docs/pt/autenticacao/registro-aberto.md:85-92`

---

## ADR-05: Na página única, a recuperação de senha resolve o painel da CONTA antes de pedir o link

**Status**: Aceita
**Data**: 2026-09-07

### Contexto

Achado da revisão das telas externas (RQ-07), medido por CT-61. O `request()` do Filament só envia
o e-mail quando `$user->canAccessPanel(Filament::getCurrentOrDefaultPanel())`
(`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:request():56-104`).
Na página única o painel corrente é o `app`, emprestado pelo `panel:app` da rota (ADR-08 da
ancestral): quem só acessa o `/admin` recebia a mensagem genérica de "se a conta existir, enviamos"
e **nenhum e-mail**. A falha é silenciosa nos dois lados — a tela não distingue, e o log não registra.

Medido também na tela do painel quando o painel corrente é o `app`: não é defeito do redirect novo,
é a consequência de servir a recuperação fora do painel da pessoa.

### Decisão

`TelaRecuperarSenhaUnificada::request()` resolve a conta pelo **mesmo** caminho do vendor
(`PasswordBroker::getUser()`), põe o painel corrente no primeiro painel que ela acessa
(`DestinoAposLogin::paineisDe()`) e delega ao `parent::request()`. O método do vendor continua
inteiro — rate limit, `Timebox` do broker, notificações de falha e de sucesso.

Efeito colateral desejado: o link do e-mail passa a apontar para o painel **da pessoa**
(`/admin/password-reset/reset?...`), que é onde ela consegue entrar depois de redefinir.

### Alternativas Consideradas

1. **Sobrescrever `request()` inteiro** com uma closure própria no broker — 45 linhas duplicadas do
   vendor, incluindo rate limit e as três notificações. A cada upgrade do Filament, um diff a
   reconciliar. Descartada.
2. **Afrouxar `User::canAccessPanel()`** para a página de reset — mexeria na autorização global
   para resolver um problema de contexto. Descartada de imediato.
3. **Aceitar e documentar** ("use a tela do seu painel") — a pessoa não sabe qual é o painel dela
   antes de entrar, e o link do login unificado é o único que ela tem. Descartada.

### Consequências

- **Positivas**: a recuperação volta a funcionar para todo mundo; o link do e-mail abre no painel
  certo; nenhuma linha do vendor duplicada.
- **Negativas**: `getUser()` roda uma vez a mais por pedido (uma consulta por e-mail), e a premissa
  RQ-06 ("o link continua por painel") passa a significar "o painel da pessoa", não `app`.
- **Riscos**: conta sem painel acessível não reposiciona nada e cai no comportamento do vendor — não
  recebe e-mail. É o mesmo desfecho de `EscolhaDePainel` para quem não acessa nada, e não piora nada.

### Referências

- `app/Filament/Pages/Auth/TelaRecuperarSenhaUnificada.php:request():68-77`
- Refina: ADR-02 desta wiki e ADR-07/ADR-08 da ancestral
