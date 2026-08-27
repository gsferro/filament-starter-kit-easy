# Decisões Arquiteturais — Proteção anti-robô nas telas públicas

## ADR-01: Nenhum pacote do catálogo — implementação própria mínima

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O requisito manda analisar a fundo o `tallcms-registration-plugin` e procurar no catálogo
`filamentphp.com/plugins` outros que atendam. A pergunta certa não é "qual tem captcha", é: **qual
cobre as três telas, roda em Laravel 13 + Filament 5, se encaixa nas páginas que o kit já
substitui (`TelaLogin`, `RegistroPorConvite`) e lê a chave do nosso Settings em tempo de
execução?** Buscas feitas em 2026-08-26 por `captcha`, `recaptcha`, `turnstile`, `honeypot` e
`spam`, com leitura do `composer.json` e do código dos candidatos plausíveis.

| Pacote | O que cobre | Filament 5 / Laravel 13 | Manutenção | Como se integra às telas de auth | Chave em runtime (do nosso Settings)? |
|---|---|---|---|---|---|
| **`tallcms/filament-registration`** v1.4.2 | Turnstile (default), reCAPTCHA **v3**, honeypot, throttle 30/min — **só registro** | `filament ^5.0`, `illuminate ^11\|^12\|^13` ✅ | ativo (commit há 1 dia) | substitui a página `Register` via `->registration(Register::class)` **e** exige `->plugin()`; acopla atribuição de papel do spatie | tem settings próprios (tela "Settings → Registration") e env `FILAMENT_REGISTRATION_*` — uma **segunda** fonte da verdade ao lado do nosso `ConfiguracoesDoKit` |
| `muazzambuilds/filament-turnstile` | Turnstile — login, registro e reset | Filament 5, **sem Laravel 13** (o catálogo avisa que não co-instala) | 34 dias | **troca as classes das páginas de auth** do painel ("chame `->plugin()` depois de `->login()`") | só `.env` |
| `l3aro/filament-turnstile` | Turnstile — campo de formulário | `filament ^4\|^5`, `illuminate ^11\|^12\|^13` ✅ | 69 dias | `Turnstile::make('captcha')` no `form()` de uma página **sua**; `TurnstileRule` chama o siteverify com `Http::retry(3, 10)` | `config('filament-turnstile.secret')` lida **na chamada** → mapeável via `mapaDeConfiguracao()` |
| `afatmustafa/filamentv3-turnstile` | Turnstile — campo | Filament **3** | 536 dias | campo | `.env` |
| `abanoub-nassem/grecaptcha-field` | reCAPTCHA v2 — campo | Filament **2–3** | legado | campo | `.env` |
| `wallacemartins/filament-security` 2.x | honeypot + e-mail descartável + DNS/MX — **só registro**, sem captcha | Filament 5 | 149 dias | substitui a `Register` | env/config/runtime |
| `mortezaashrafi/shield-captcha`, `marcogermani87/captcha` | captcha de **imagem** (GD) — campo | não declarado | — | campo | — |
| `dominion-solutions/captcha`, `ousid/cloudflare-turnstile` | marcados **Legacy** pelo catálogo | — | — | — | — |

O que a tabela diz:

1. **O `tallcms` responde a uma pergunta diferente.** Ele é um fluxo de registro completo (captcha
   + papel padrão + throttle), e o kit já tem o seu — `RegistroPorConvite` é a página de registro
   dos dois modos, com a autoridade do convite sobre o e-mail. Adotá-lo obrigaria a escolher entre
   a página dele e a nossa; e ele não toca login nem recuperação de senha, que são dois terços do
   requisito. O seu Settings próprio seria uma segunda tela de configuração ao lado de
   `/admin/configuracoes-do-kit`, exatamente a "duas fontes sem regra de precedência" que
   `.ai/rules/config.md` proíbe.
2. **O único que cobre as três telas (`muazzambuilds`) faz isso trocando as classes das páginas**
   — as mesmas que o kit já troca pelo Auth Designer (`usingPage()`), e não instala em Laravel 13.
3. **O mais próximo do que precisamos é o `l3aro`**: campo de formulário, chave lida por request.
   Mas é Turnstile-only, e ainda exigiria as nossas três subclasses de página para pôr o campo
   no `form()`. O que ele economizaria é o blade do widget e a regra — umas sessenta linhas — em
   troca de uma dependência, um adaptador de config e a impossibilidade de oferecer o reCAPTCHA
   que o requisito nomeia.
4. **O protocolo é um só.** reCAPTCHA v2, Turnstile e hCaptcha carregam um script com
   `?render=explicit&onload=`, expõem `render(el, {sitekey, callback})` e `reset(id)`, e verificam
   com `POST secret + response (+ remoteip)` devolvendo `{"success": bool}`. É por isso que os
   pacotes acima são todos pequenos, e por isso a implementação própria também é.

### Decisão

Implementação própria: um enum (`ProvedorAntiRobo`), um campo de formulário (`CampoAntiRobo`) com
a regra de validação que chama o `siteverify` do provedor via `Http`, uma view Blade para o
widget, e o campo acrescentado ao `form()` das três páginas do kit. Nenhuma dependência nova.

### Alternativas Consideradas

1. `tallcms/filament-registration` — descartado: só registro, página própria conflitante, Settings
   próprio, reCAPTCHA v3 (ver ADR-02).
2. `l3aro/filament-turnstile` — descartado por margem: Turnstile-only e ainda exige as nossas
   páginas; a economia não paga a dependência. Fica registrado como o pacote a reconsiderar se um
   dia o widget precisar de recursos (idioma, tamanho, aparência) que o blade mínimo não tiver.
3. `muazzambuilds/filament-turnstile` — descartado: não instala em Laravel 13 e troca as páginas
   que o kit já troca.

### Consequências

- **Positivas**: zero dependência; a chave vem do Settings sem adaptador (as chaves de config são
  nossas desde o início); o mesmo campo serve aos três provedores e às três telas.
- **Negativas**: o kit passa a manter ~150 linhas de integração com serviço de terceiro (três URLs
  por provedor, o contrato do `render()`/`reset()`, o formato da resposta). Se um provedor mudar o
  protocolo, é o kit que acompanha — os pacotes têm o mesmo problema, só que com atraso.
- **Riscos**: o `render=explicit` + `onload` é o caminho documentado dos três provedores, mas só o
  navegador prova que o widget renderiza. Ver o gate de CT-B no `04`.

### Referências

- `https://github.com/tallcms/filament-registration` (`composer.json` v1.4.2)
- `https://github.com/l3aro/filament-turnstile` (`src/Forms/Turnstile.php`, `src/FilamentTurnstile.php`, `config/filament-turnstile.php`)
- `https://filamentphp.com/plugins/muazzambuilds-turnstile`, `.../wallacemartins-security`
- `CLAUDE.md`: "Do not change the application's dependencies without approval."

---

## ADR-02: reCAPTCHA v2 (caixa), com Turnstile e hCaptcha como alternativas de mesmo protocolo

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

"reCAPTCHA" cobre dois produtos distintos. O **v2** mostra uma caixa, devolve um token quando a
pessoa a resolve, e o servidor recebe `success: true|false`. O **v3** é invisível: o servidor recebe
uma pontuação de 0 a 1 e um `action`, e cabe a quem integra escolher o limiar e nomear uma ação por
tela — e recalibrar quando a pontuação começa a barrar gente de verdade.

### Decisão

v2 (caixa). E como Cloudflare Turnstile e hCaptcha oferecem o mesmo contrato — script com
`render=explicit`, `render(el, {sitekey, callback})`, `reset(id)`, `siteverify` com `secret` +
`response` —, o campo aceita os três por um enum. O default é `recaptcha`, que é o que o requisito
nomeia; o Turnstile fica como a alternativa sem rastreamento e sem custo, relevante para instalação
sob LGPD.

### Alternativas Consideradas

1. **reCAPTCHA v3** — descartado: exige limiar e `action` configuráveis (dois campos a mais na tela
   e uma decisão que quem instala não sabe tomar), e o `tallcms` mostra o custo disso (ele expõe o
   limiar na tela dele). Fica como a extensão registrada no `00`: um caso a mais no enum.
2. **Só reCAPTCHA v2** — descartado por margem: o segundo e o terceiro provedor custam um `match`
   de três linhas em cada método do enum. O que **não** entra é nada que quebre o contrato comum
   (reCAPTCHA v3, captcha de imagem local).
3. **Captcha de imagem gerado localmente** (`shield-captcha`) — descartado: sem dependência
   externa, mas quebrável por OCR e inacessível para leitor de tela; e o requisito diz reCAPTCHA.

### Consequências

- **Positivas**: resposta binária, sem calibração; o mesmo blade e a mesma regra para três
  provedores.
- **Negativas**: a caixa é atrito visível em toda entrada. É o que o requisito pediu.

### Referências

- `https://developers.google.com/recaptcha/docs/display` (`render=explicit`, `onload`, `grecaptcha.render`, `grecaptcha.reset`)
- `https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/` (`render=explicit`, `turnstile.render`, `turnstile.reset`)
- `https://docs.hcaptcha.com/configuration` (`render=explicit`, `hcaptcha.render`, `hcaptcha.reset`)
- `app/Support/ProvedorAntiRobo.php`

---

## ADR-03: Duas condições para ligar — o interruptor E as duas chaves — e provedor inválido desliga

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O requisito diz "da mesma forma do login social". Lá, `ConfiguracaoDoLogin::disponivel()` exige o
interruptor ligado **e** as credenciais preenchidas, porque as duas falham por motivos diferentes:
interruptor desligado é escolha, credencial vazia é descuido. Aqui o descuido custa mais caro que
lá: no login social, credencial vazia deixaria um botão apontando para um OAuth inexistente; aqui
deixaria **um campo obrigatório que nunca se preenche**, nas três telas de entrada dos três
painéis — ninguém entra mais, inclusive quem administra.

### Decisão

`ConfiguracaoDoLogin::antiRobo()` devolve o provedor só quando `habilitado` é verdadeiro **e**
`chave_do_site` está `filled()` **e** `chave_secreta` está `filled()` **e**
`ProvedorAntiRobo::tryFrom(provedor)` não é `null`. Qualquer outra combinação = `null` = proteção
desligada; a tela de Settings avisa no `helperText` do toggle que ligar sem as chaves não liga
nada. Provedor inválido registra `warning` — é o único ramo que loga, porque é o único que não é
escolha nem estado normal.

### Alternativas Consideradas

1. **Só o interruptor decide; chave vazia é erro na tela** (`required` condicional no Settings) —
   descartado: não cobre o `.env` (`KIT_ANTI_ROBO=true` sem chave), que é onde o descuido acontece.
2. **Provedor inválido cai no `recaptcha`** (como a cor fora da lista cai no default) —
   descartado: com chaves do Turnstile e widget do Google, o widget não renderiza e o login trava;
   pior que desligar.

### Consequências

- **Positivas**: não há estado em que o kit exija um desafio que não consegue exibir.
- **Negativas**: a proteção pode estar "ligada" no toggle e desligada de fato. A tela diz isso no
  `helperText`, e CT-02 do `04` cobre a tabela inteira.

### Referências

- `app/Support/ConfiguracaoDoLogin.php` (`disponivel()`, o precedente)
- `.ai/rules/config.md` ("Interruptor de env que abre superfície pública falha FECHADO")

---

## ADR-04: Provedor indisponível recusa o envio (falha fechada), com `timeout` de 5 s

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

A verificação é um `POST` ao provedor dentro do request de envio do formulário. Se o provedor não
responder, há duas saídas: deixar passar (falha aberta) ou recusar (falha fechada).

### Decisão

Recusar, com a mesma mensagem de token inválido para a pessoa e um `warning` distinto
(`motivo: verificacao_indisponivel`, com a exceção) no canal `autenticacao`. `Http::timeout(5)`
para a pessoa ver o erro em cinco segundos e não em trinta; sem `retry`, porque três tentativas
sobre um provedor caído triplicariam o tempo de resposta de uma tela pública.

### Alternativas Consideradas

1. **Falha aberta** — descartado: quem derruba o provedor (ou bloqueia a saída de rede do
   servidor) desliga a proteção sem tocar em nada do kit. Uma proteção que se desliga sob ataque
   não é proteção.
2. **Retry** (o `l3aro` faz `retry(3, 10)`) — descartado: 10 ms entre tentativas não cobre queda
   de provedor, só oscilação de rede; e o custo no pior caso é 3× o timeout.

### Consequências

- **Positivas**: comportamento previsível e registrado.
- **Negativas**: provedor fora do ar = ninguém entra até alguém desligar o toggle. O toggle é por
  request (sem deploy), e o README diz isso por escrito.

### Referências

- `app/Filament/Forms/Components/CampoAntiRobo.php` (`confirmarToken()`)

---

## ADR-05: O campo é `->visible()` pela configuração, e não registrado condicionalmente

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O campo precisa sumir por completo com a proteção desligada — sem script externo, sem `<div>`, sem
regra de validação (RQ-07). Há dois jeitos: só acrescentar o campo ao `form()` quando a proteção
está ligada, ou acrescentá-lo sempre e deixá-lo decidir a própria visibilidade.

### Decisão

Sempre acrescentado, `->visible(fn () => ConfiguracaoDoLogin::antiRobo() !== null)`. Componente
oculto no Filament não é renderizado e é pulado por `Schema::getValidationRules()`
(`vendor/filament/schemas/src/Concerns/CanBeValidated.php:75-79` →
`isNeitherDehydratedNorValidated()`, `.../Components/Concerns/HasState.php:801-821`). Resultado
idêntico ao de não existir — mas a decisão é avaliada **no render e na validação**, não na montagem
do formulário, o que é o critério de `.ai/rules/settings.md` para a chave poder viver no Settings.

### Alternativas Consideradas

1. **`if` no `form()`** — descartado: a mesma decisão escrita em três páginas, e avaliada uma vez
   por instância de componente em vez de a cada render.
2. **Render hook `AUTH_*_FORM_AFTER` + middleware validando o token** — descartado: o hook não
   participa da validação do formulário Livewire, e um middleware no request do Livewire teria de
   distinguir o envio do formulário de qualquer outra interação. É a saída de quem não pode tocar
   nas páginas; o kit pode.

### Consequências

- **Positivas**: uma linha por página; o comportamento "ligado/desligado" é o de todo campo
  condicional do Filament.
- **Negativas**: o campo existe no schema mesmo desligado (custo: um objeto a mais por render).

### Referências

- `vendor/filament/schemas/src/Concerns/CanBeValidated.php:75-96`
- `vendor/filament/schemas/src/Components/Concerns/HasState.php:796-821`

---

## ADR-06: `->dehydrated(false)` e o widget redefinido por evento depois de cada verificação

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

Dois detalhes do ciclo de vida que não estão no requisito e mordem se esquecidos.

O primeiro: `Register::register()` entrega `$this->form->getState()` a `handleRegistration()`, que
no kit vira `Convite::aceitar($data)` ou `RegistroAberto::registrar($data)`. Uma chave `anti_robo`
a mais nesse array chegaria a `User::create()`.

O segundo: o token que o widget devolve é de **uso único**. Depois de uma senha errada, o Filament
re-renderiza o formulário com o erro; o widget continua marcado, mas o token já foi gasto na nossa
verificação — o segundo envio falharia por "token já usado", e a pessoa não entenderia por quê.

### Decisão

`->dehydrated(false)`: o token é validado (`isValidatedWhenNotDehydrated()` é o default) e não entra
em `$data`. E a regra de validação, em qualquer resultado, dispara
`$this->getLivewire()->dispatch('kit-anti-robo-redefinir')`; o blade escuta o evento na janela e
chama `reset(id)` do provedor. Livewire 3+ entrega todo `dispatch()` também como evento de
navegador, então `x-on:kit-anti-robo-redefinir.window` basta.

### Alternativas Consideradas

1. **Redefinir no `afterStateUpdated` de outro campo** — descartado: o gatilho certo é "houve uma
   verificação", e só a regra sabe disso.
2. **`wire:ignore` e deixar o widget como está** — descartado: é o cenário do token gasto.

### Consequências

- **Positivas**: o segundo envio funciona; nenhum consumidor de `$data` vê a chave nova.
- **Negativas**: a pessoa precisa marcar a caixa de novo depois de errar a senha. É o comportamento
  de todo site com reCAPTCHA.

### Referências

- `vendor/filament/filament/src/Auth/Pages/Register.php:70-112` (o `$data` que chega a `handleRegistration()`)
- `https://livewire.laravel.com/docs/events#dispatching-browser-events`
