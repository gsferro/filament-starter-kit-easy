# Decisões Arquiteturais — Adotar `ddr/filament-captcha`

## ADR-01: Adotar `ddr/filament-captcha` com adapters em vez de estender a implementação própria

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

A implementação atual (`CampoAntiRobo` + `ProvedorAntiRobo` + blade) funciona para reCAPTCHA v2,
Turnstile e hCaptcha, mas o usuário pediu reCAPTCHA v3. Duas opções: adicionar v3 ao enum
existente (~100 linhas novas) ou adotar o pacote `ddr/filament-captcha` que já o traz.

A ADR-01 da wiki ancestral (`recaptcha-nas-telas-publicas`) descartou pacotes porque nenhum
cobria as 3 telas + Filament 5 + Laravel 13 + Settings em runtime. O `ddr/filament-captcha` é
diferente: é um **componente de formulário** (`Captcha::make()`), não uma substituição de página.
Ele se encaixa no `form()` das nossas páginas, como o `CampoAntiRobo` se encaixa hoje.

### Decisão

Adotar o pacote com 3 adapters:

1. **CaptchaBridge** — projeta as env vars/Settings do kit para `config('captcha.*')`
2. **CaptchaDriverDecorator** — adiciona logging + falha fechada + try/catch ao `verify()`
3. **CaptchaField** — wrapper com `acrescentarA()` e reset de token via dispatch

### Alternativas Consideradas

1. **Adicionar v3 ao enum existente** (~100 linhas) — descartado: resolve v3 mas mantém o enum
   monolítico, a view unificada, e a manutenção de URLs/protocolos dentro do kit. O pacote
   transfere essa responsabilidade para fora.
2. **Fork do pacote** — descartado: perderíamos atualizações e teríamos que manter o fork.
3. **Manter como está (sem v3)** — o usuário decidiu contra.

### Consequências

- **Positivas**: reCAPTCHA v3 de graça; drivers extensíveis; views separadas por provedor;
  manutenção de protocolo transferida ao pacote; i18n (en/es/pt/pt_BR) inclusa
- **Negativas**: dependência nova no projeto (~8 arquivos src); adapters somam ~200 linhas;
  reescrita de 56+5 testes
- **Riscos**: pacote jovem (4.710 installs); adapters podem divergir em futuras versões do pacote

### Referências

- `https://github.com/danie1net0/filament-captcha` (`composer.json` v1.x)
- `wikis/specs/feat/recaptcha-nas-telas-publicas/recaptcha-nas-telas-publicas/02-decisoes-arquiteturais.md` ADR-01
- Avaliação profunda documentada no chat de 2026-08-31

---

## ADR-02: Bridge em vez de mapa direto para alimentar `config('captcha.*')`

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O pacote lê de `config('captcha.{driver}.sitekey')` e `config('captcha.{driver}.secret')`. O
nosso Settings guarda uma ÚNICA chave do site e uma ÚNICA chave secreta, independente do driver
(porque o admin troca de driver e mantém as mesmas chaves — cada driver tem suas). O mapa
1-para-1 de `mapaDeConfiguracao()` não funciona: a mesma propriedade `login_anti_robo_chave_do_site`
precisaria mapear para `captcha.hcaptcha.sitekey` OU `captcha.recaptcha_v2.sitekey` OU
`captcha.recaptcha_v3.sitekey` OU `captcha.turnstile.sitekey`, dependendo do driver ativo.

### Decisão

Criar `CaptchaBridge::aplicar()`, chamado APÓS o `aplicarNaConfig()` padrão. Ele:
1. Lê o driver ativo de `config('kit.login.anti_robo.provedor')` (já mapeado pelo mapa padrão)
2. Escreve `config('captcha.driver')` com o driver
3. Escreve `config("captcha.{driver}.sitekey")` e `config("captcha.{driver}.secret")` com as
   chaves do kit
4. Se driver for `recaptcha_v3`, escreve `config('captcha.recaptcha_v3.score')` com o score do kit

As chaves de `kit.login.anti_robo.*` continuam como propriedades do Settings, e o mapa continua
mapeando para `kit.login.anti_robo.*`. O bridge é a projeção adicional.

### Alternativas Consideradas

1. **4 propriedades de chave por driver no Settings** — descartado: o admin configura UM driver
   de cada vez; 4 pares de chaves na tela seria confuso e quebraria a UX existente
2. **Substituir todo o mapa por um bridge único** — descartado: perderia a mecânica genérica
   (1 linha por propriedade) que o mapa oferece para as 50+ outras propriedades
3. **Config do pacote lendo `config('kit.*')` via Closure** — descartado: `config()` não aceita
   Closures; e o pacote lê no `createDriver()`, que é chamado sob demanda

### Consequências

- **Positivas**: as env vars e a tela de Settings não mudam de estrutura; o bridge é ~30 linhas
- **Negativas**: uma chamada a mais no boot; o bridge precisa ser chamado NA ORDEM CERTA (após
  o mapa padrão, antes de qualquer uso do pacote)

### Referências

- `app/Settings/ConfiguracoesDoKit.php:262-353` (`mapaDeConfiguracao()`)
- `app/Settings/ConfiguracoesDoKit.php:367-382` (`aplicarNaConfig()`)
- `.ai/rules/settings.md` (regra dos 3 lugares)

---

## ADR-03: Decorator para logging e falha fechada — não fork nem middleware

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O pacote não trata exceções de rede no `verify()` (um `ConnectionException` vira 500) e não loga
nada. A ADR-04 da wiki ancestral exige falha fechada (provedor caído = envio recusado, não aberto).
O padrão de log do projeto exige `[Classe@Método]` no canal `autenticacao`.

### Decisão

Decorator (`CaptchaDriverDecorator`) que envolve o driver real. O `verify()` tem try/catch que
loga e retorna `false` em caso de exceção. O `CaptchaManager` é estendido ou rebindado no
container para retornar o decorator em vez do driver cru.

### Alternativas Consideradas

1. **Fork do pacote adicionando try/catch nos drivers** — descartado: manteria fork e perderia
   atualizações
2. **Middleware de HTTP que captura exceção** — descartado: a exceção acontece dentro da closure
   da Rule de validação, não no middleware de rota
3. **Monkey-patch via `Http::fake()` em produção** — descartado: absurdo
4. **Estender cada driver individualmente** — descartado: 4 subclasses em vez de 1 decorator;
   o decorator é o pattern correto

### Consequências

- **Positivas**: falha fechada preservada; logging com o padrão do projeto; zero alteração nos
  drivers originais
- **Negativas**: mais uma camada de indireção; o decorator precisa delegar `getSiteKey()`,
  `getScriptUrl()` e `getView()` sem alteração

### Referências

- `app/Filament/Forms/Components/CampoAntiRobo.php:128-171` (`confirmarToken()` — o equivalente atual)
- ADR-04 da wiki ancestral (falha fechada)

---

## ADR-04: Valor do provedor muda de `recaptcha` para `recaptcha_v2` — com migration de dados

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O enum antigo usa `recaptcha` como valor do caso; o pacote usa `recaptcha_v2` como nome do driver
(porque `recaptcha_v3` é outro driver). Se não migrarmos, quem já tem `recaptcha` no banco terá
um driver desconhecido e a proteção desligará silenciosamente.

### Decisão

Criar migration de settings que atualiza `login_anti_robo_provedor` de `'recaptcha'` para
`'recaptcha_v2'`. O `config/kit.php` também muda o default. O `down()` reverte.

### Alternativas Consideradas

1. **Fallback no código** (`recaptcha` → `recaptcha_v2` em runtime) — descartado: a proteção
   se autodesligava com provedor desconhecido (ADR-03 ancestral); um fallback quebraria essa
   invariante e esconderia dados sujos
2. **Manter `recaptcha` como alias do driver `recaptcha_v2`** — descartado: exigiria estender o
   `CaptchaManager` para mapear aliases; complexidade desnecessária para uma migração one-shot

### Consequências

- **Positivas**: dados limpos; o `CaptchaManager` resolve o driver sem ambiguidade
- **Negativas**: a migration precisa rodar; quem fizer `composer update` sem `migrate` terá a
  proteção desligada — o README deve avisar
- **Riscos**: instalações sem o valor no banco (novo setup) não são afetadas — o default já é
  `recaptcha_v2`

### Referências

- `database/settings/2026_08_26_100000_add_anti_robo_to_kit_settings.php:24`
- `config/kit.php:558`

---

## ADR-05: Views publicadas com dark mode, reset e `data-anti-robo`

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

As views do pacote:
- Não detectam dark mode — o widget fica claro em tema escuro
- Não têm `data-anti-robo` — os testes browser usam esse atributo
- Não escutam evento de reset — o token gasto não é redefinido
- Usam `$wire.$entangle()` — potencial request por keystroke no v3

### Decisão

Publicar as views (`php artisan vendor:publish --tag=filament-captcha-views`) e customizar as 4:
- Adicionar `data-anti-robo="{driver}"` no container
- Adicionar `class="fi-fo-anti-robo"` no container
- Adicionar detecção de dark mode via `document.documentElement.classList.contains('dark')`
- Adicionar `x-on:kit-anti-robo-redefinir.window` que chama `reset()` do provedor
- reCAPTCHA v3: trocar `$entangle` por `$wire.set(path, token, false)` para evitar request
  desnecessário ao gerar o token

### Alternativas Consideradas

1. **CSS override para dark mode** — descartado: o `theme` é parâmetro do `render()`, não CSS;
   sem passá-lo, o widget do Google ignora o tema
2. **JavaScript externo que escuta o evento** — descartado: aumenta a superfície; o `x-on` no
   Alpine é mais idiomático e já existe na implementação atual

### Consequências

- **Positivas**: comportamento idêntico ao atual; testes browser usam os mesmos seletores
- **Negativas**: views publicadas divergem do pacote; atualizações futuras do pacote precisam ser
  mescladas manualmente — risco baixo (views são ~30 linhas cada)

### Referências

- `resources/views/filament/forms/components/campo-anti-robo.blade.php` (implementação atual)
- Views do pacote em `vendor/ddr/filament-captcha/resources/views/drivers/`

---

## ADR-06: Manter env vars `KIT_ANTI_ROBO_*` e projetar para `captcha.*` via bridge

**Status**: Aceita
**Data**: 2026-08-31

### Contexto

O pacote usa `CAPTCHA_DRIVER`, `HCAPTCHA_SITEKEY`, `RECAPTCHA_V2_SITEKEY`, etc. O kit usa
`KIT_ANTI_ROBO_PROVEDOR`, `KIT_ANTI_ROBO_CHAVE_DO_SITE`, `KIT_ANTI_ROBO_CHAVE_SECRETA`. Trocar
seria breaking change para quem já tem o `.env` configurado.

### Decisão

Manter as env vars do kit. O `config/captcha.php` publicado pode ler das env vars do pacote como
fallback, mas a autoridade é o Settings (via bridge). Quem nunca configurou nada no `.env` e
quer usar o pacote diretamente pode usar as vars do pacote — o bridge sobrescreve o que vier do
banco quando `login_anti_robo_habilitado` estiver ativo.

### Alternativas Consideradas

1. **Migrar para as env vars do pacote** — descartado: breaking change; e o Settings (banco)
   é a autoridade em runtime, não o `.env`
2. **Suportar as duas** — é o que fazemos: o `config/captcha.php` lê das vars do pacote; o
   bridge sobrescreve com o que veio do Settings/kit

### Consequências

- **Positivas**: zero breaking change no `.env`; instalações existentes continuam funcionando
- **Negativas**: duas "camadas" de config (pacote + bridge); um dev novo pode se confundir —
  documentar no `.env.example`

### Referências

- `.env.example`
- `config/captcha.php` (publicado do pacote)
- `app/Support/CaptchaBridge.php`
