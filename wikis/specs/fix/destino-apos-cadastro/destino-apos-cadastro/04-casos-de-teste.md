# Casos de Teste — fix: destino depois do cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (**ainda não existia** quando este
> arquivo foi escrito — a superfície veio do pedido ao agente e está registrada em
> `## Fronteira com o Plano`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação da
> correção, que ainda não existe. `app/Support/DestinoAposLogin.php`,
> `app/Http/Responses/RespostaDeLogin.php` e o vendor do Filament foram lidos só para herdar
> convenção de teste e conferir as afirmações de código do `00` — nunca como oráculo.

> **Numeração**: os CTs deste projeto são contínuos entre wikis. O maior ID em uso é **CT-66**
> (`tests/Tenancy/LoginUnificadoTenancyTest.php`). Esta wiki começa em **CT-67**.

> ⚠️ **Leia antes de implementar**: **CT-68 nasce vermelho contra a superfície prevista**, e isso é
> o achado principal desta derivação, não um defeito do caso. RQ-01 é absoluto ("**nunca** é
> entregue na URL de um painel que ela não acessa") e a premissa da Ambiguidade 1 do `00` ("não muda
> o fallback do Filament") o contradiz para uma persona real e comum: a conta convidada como
> `admin`, cujo único painel **não** é o que serve a tela de cadastro. Ver
> `## Perguntas para o 00-requisito.md`, pergunta **P1**.

---

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| **A** — destino com a chave `kit.login.unificado` **ligada** | 2 | 3 | 6 | padrão |
| **B** — destino com a chave **desligada** (mecanismo novo: descartar a pretendida sem trocar o fallback) | 3 | 3 | 9 | **completo** |
| **C** — superfície de invocação: as duas telas de cadastro e o contrato do container | 2 | 3 | 6 | padrão |
| **D** — documentação e release (RQ-05, RQ-06, RQ-07) | 1 | 1 | 1 | mínimo |

**Justificativa das notas.** Impacto 3 em A, B e C porque o observável do defeito é **autorização**:
a pessoa termina num painel que não acessa e vê 403 depois de um cadastro que funcionou —
irreversível do ponto de vista dela (a conta já nasceu, a sessão já está aberta, e não há tela que
explique). Probabilidade 3 em B porque é o único código **novo** da correção (descartar a pretendida
inacessível é regra com condições) e porque é o caminho que a instalação **default** roda: a chave
nasce desligada (CT-01 da wiki `login-unificado`). Probabilidade 2 em A e C porque reusam
`DestinoAposLogin::urlPara()` e o contrato do Filament, que já existem e já têm cobertura.

- **Técnicas aplicadas**: partição de equivalência (classe da pretendida × painéis da conta nova),
  tabela de decisão fechada (32 células, abaixo), valor limite de **prefixo de path** (incremento:
  1 caractere), rastreio de efeito (consumo da pretendida na sessão), partição de interface (duas
  telas × dois modos), partição de plataforma (uma célula de tenancy).
- **Revisão adversarial**: obrigatória (perfil completo em B **e** Impacto 3 em A, B e C).
  Executada — ver `## Revisão Adversarial`.
- Cenários: **10** (CT-67…CT-76) · Regras: **7** (R1–R6 + a célula de tenancy) · Mutantes previstos:
  **25** (R1 5, R2 5, R3 6, R4 3, R5 2, R6 2, tenancy 2) · Sem matador: **1** (R2/M5, lacuna
  declarada) · Mutantes cujo matador nasce vermelho contra a superfície prevista: **1** (R1/M2, CT-68)
- Células da matriz: **32** = 22 por CT + 3 por CT `@premissa` + 3 lacunas declaradas + 4 `não se aplica`

### Divergência declarada: rule do projeto vence a skill

A skill sugere `pest --parallel --tia` como padrão de verificação e `pest --mutate` como fechamento
do ciclo. **`.ai/rules/testes-browser.md` vence**: ela mediu que `--parallel` derruba cenários de
browser e que, **sem PCOV no ambiente**, `--tia` é inviável (abortado após 35 min com Xdebug em
série). Consequência para esta wiki:

- comando de verificação: `composer test:kit` (`--testsuite=Kit,Tenancy --parallel`), ou
  `vendor/bin/pest tests/Kit/DestinoAposCadastroTest.php` para o arquivo só;
- `pest --mutate` fica **declarado como lacuna de ambiente**, não como passo: exige driver de
  cobertura que a rule diz não existir aqui. Se PCOV entrar no ambiente, o escopo é
  `--path=app/Http/Responses --path=app/Support/DestinoAposLogin.php`.

Como não há CT-B nesta wiki (ver `## Sem CT-B`), `--parallel` continua valendo para ela.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S**tructure | `app/Http/Responses/RespostaDeCadastro.php` (nova), bind do contrato em `KitServiceProvider::configureLoginUnificado()`, método público novo em `app/Support/DestinoAposLogin.php`. **Nenhuma** migration, model, view, tradução ou rota nova | CT-71 (o bind) |
| **F**unction | decidir a URL de destino de um cadastro que **entra**; descartar a pretendida inacessível; delegar ao Filament com a chave desligada | CT-67…CT-72 |
| **D**ata | `session('url.intended')` nas classes {ausente, vazia, de painel acessível, de painel inacessível, forjada}; painéis da conta nova nas classes {1 = o da tela, 1 ≠ o da tela, vários, nenhum}; `aprovacao_pendente` (fora de escopo, mas o fluxo atravessa aqui); organização (Tenancy) | CT-69, CT-70, CT-74, CT-76 |
| **I**nterfaces | três, e é isso que faz R4 existir: a tela do painel `/app/register` (`RegistroPorConvite`), a tela sem prefixo `/cadastro` (`CadastroUnificado`, só com a chave ligada) e o **contrato** `Filament\Auth\Http\Responses\Contracts\RegistrationResponse` resolvido pelo container — por onde entra qualquer página de registro futura. **Uma quarta ficou de fora e é achado**: o cadastro por provedor social não resolve o contrato (`LoginSocialController::retorno():352` → `urlDoPerfil()`/`urlDoPainel()`), logo a correção não o alcança — pergunta **P2** | CT-71, CT-76 |
| **P**latform | **não se aplica**: a sessão é o único armazenamento, e a decisão não toca banco, fila, storage, cache nem driver. O único acoplamento de plataforma é o `session()->regenerate()` do vendor (`Register.php:110`), que **preserva** os dados — já conferido no `00` | — |
| **O**perations | a chave nasce **desligada**, então a área B é o que a maioria das instalações roda. O convite de papel `admin` é o caminho normal de onboarding de administrador do kit — não é caso de borda. Uso indevido: a pretendida não é parâmetro de request (só o middleware `Authenticate` a grava), então a correção não abre superfície de injeção nova | CT-68, CT-70 |
| **T**ime | **não se aplica**: sem expiração, sem agendamento, sem timezone, sem concorrência relevante — dois cadastros simultâneos do mesmo e-mail são serializados pelo `->unique()` do campo, regra anterior a esta entrega | — |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — um cadastro que entra nunca termina na URL de um painel que a conta nova não acessa — nem pela pretendida, nem pelo fallback | A + B (**completo**) | RQ-01 | tabela de decisão + rastreio de efeito | CT-67, CT-68, CT-72, CT-75 |
| **R2** — com a chave ligada, o destino é o que `DestinoAposLogin` decide para o login, sem regra nova | A (padrão) | RQ-02, RQ-04 | partição por classe de resultado | CT-69 |
| **R3** — com a chave desligada, a pretendida inacessível é descartada e o destino segue sendo o do Filament; a acessível continua honrada | B (**completo**) | RQ-03, RQ-04 | tabela de decisão + valor limite de prefixo | CT-70 |
| **R4** — a correção vale nas duas telas de cadastro e nos dois modos (convite e aberto), porque vive no contrato | C (padrão) | RQ-01 + superfície | partição de interface | CT-71 |
| **R5** — a entrega fica registrada na documentação do kit e no CHANGELOG | D (mínimo) | RQ-05 | partição (pt, en, CHANGELOG) | CT-73 |
| **R6** — *regressão*: o cadastro pendente de aprovação continua encerrando a sessão e terminando no login | C (padrão) | `00` → `## Fora de Escopo` | 1 cenário de regressão | CT-74 |
| **R2 sob tenancy** — a célula que só a plataforma expõe | A (padrão) | RQ-01, RQ-02 | partição de plataforma | CT-76 |

**Técnica escalada acima do perfil da área**: R1 usa **rastreio de efeito** (CT-75, o consumo da
pretendida na sessão) mesmo com a área A em perfil padrão, porque o mutante *"descarta a pretendida
da decisão e a deixa na sessão"* devolve o mesmo 403 um redirecionamento depois — e **nenhuma**
asserção sobre a URL de destino o mata.

**RQ que não geraram regra de suíte, com o motivo:**

| RQ | Por que não vira cenário |
|---|---|
| RQ-06 (merge na `main` + tag publicada) | ato de release, verificado pelo fluxo do repositório, não por asserção de suíte. Evidência vai no `03-progresso.md` |
| RQ-07 (`kit:update` numa instalação de teste) | validação manual em instalação externa; a suíte do kit não alcança a pasta de testes. Evidência vai no `03-progresso.md` |

---

## Fronteira com o Plano

O `01-plano-acao.md` **não existia** quando este arquivo foi derivado. A superfície abaixo veio do
pedido ao agente e foi conferida no código; toda ela é **escolha de implementação**, e nenhuma linha
dela é oráculo de nenhum cenário.

| Item da superfície | Recusado como oráculo porque | Destino |
|---|---|---|
| a classe se chamar `RespostaDeCadastro` e viver em `app/Http/Responses/` | escolha de implementação | detalhe do cenário; nenhum `Então` cita o nome da classe |
| o bind ficar em `KitServiceProvider::configureLoginUnificado()` | escolha de implementação | detalhe. O que os cenários afirmam é o **observável**: o contrato resolvido do container decide certo (CT-69/CT-70) e as duas telas usam a decisão (CT-71) |
| o método público novo de `DestinoAposLogin` ("descartar a pretendida inacessível") e o nome dele | escolha de implementação | **premissa de mecanismo**, não de escopo: ela fixa que os cenários da chave desligada são escritos contra o contrato resolvido, e **não** dispensa nenhum. O endurecimento de path (host externo, `//`, `\`, prefixo sem barra) **não é herdado por presunção** — CT-70 o exercita de novo, porque um método novo pode reescrever a checagem em vez de reusar `painelDe()` |
| `parent::toResponse()` como fallback com a chave desligada | é exatamente o ponto em que RQ-01 e a Ambiguidade 1 do `00` colidem | **pergunta P1**, e CT-68 escrito por falha fechado |
| o texto da entrada de CHANGELOG e das docs | deliverable, não comportamento — mas o termo asserido precisa ser fixado, senão teste e documentação divergem | CT-73 assere um termo declarado; se o `01` escolher outro, **os dois mudam no mesmo commit** |

---

## A matriz fechada: chave × classe de pretendida × painéis da conta nova

Esta feature **não tem entidade com ciclo de vida**, então não há tabela estado × evento — está
declarado, não omitido. O análogo auditável é o produto cartesiano das três dimensões que decidem o
destino. Ele é montado a partir das **dimensões**, não do mapa de regras, e é **uma tabela só**.

**Dimensões (do domínio, não das regras):**

- **chave** `kit.login.unificado` — `{ligada, desligada}` → **2**
- **classe da pretendida** — `{I1 ausente, I2 de painel acessível, I3 de painel inacessível, I4 forjada ou enganosa}` → **4**
- **painéis da conta nova** — `{P1 exatamente 1, e é o painel que serve a tela; P2 exatamente 1, e NÃO é o painel da tela; P3 vários; P4 nenhum}` → **4**

**Total: 2 × 4 × 4 = 32 células.** 4 `não se aplica`, 22 resolvidas por CT, 3 por CT `@premissa` que
nasce vermelho, 3 por lacuna declarada. Soma: 32.

**Legenda — e ela é asserção, conferida célula a célula.** Cada célula afirma **a URL de destino**,
com valor concreto e nunca "funciona"; nenhuma se resolve por `assertOk()` sozinho.

A **segunda** metade — a pretendida foi **consumida** da sessão — é o não-efeito desta feature, e a
legenda dela é **mais estreita do que a grade**, o que está declarado aqui em vez de presumido: quem
a afirma é só **CT-75**, nas quatro combinações `{chave ligada, desligada} × {ramo descartado, ramo
honrado}`. Isso cobre os dois ramos de código que apagam a chave, que são os dois modos de o mutante
*"decide certo e deixa a pretendida na sessão"* existir — mas **não** é uma asserção de consumo em
cada uma das 32 células, e afirmar que fosse tornaria a legenda falsa. As células de **I1 ausente**
não têm o que consumir e a metade não se aplica a elas; as de **P3** e **P4** herdam o ramo que
CT-75 exercita, porque o apagamento não depende de quantos painéis a conta tem — **lacuna declarada,
com o que foi tentado**: acrescentar as linhas de P3/P4 a CT-75 foi cogitado e cortado por não
discriminar nada (o mesmo `pull()`/`forget()` roda, e o `Então` seria idêntico ao da linha de P1).

### Chave LIGADA — a decisão é `DestinoAposLogin::urlPara()`

| | I1 ausente | I2 de painel acessível | I3 de painel inacessível | I4 forjada/enganosa |
|---|---|---|---|---|
| **P1** só `/app` (`panel_user`) | `/app` — CT-69 | `/app/users` — CT-69 | `/app` — CT-69 | `/app` — CT-69 |
| **P2** só `/admin` (`admin`) | `/admin` — **CT-67** | `/admin/users` — CT-69 | `/admin` — CT-69 (colapso ✱) | `/admin` — CT-69 (colapso ✱) |
| **P3** os três (`master_global`) | escolha — CT-69 | `/admin/users` — CT-69 | *não se aplica*: com os três painéis acessíveis não existe painel interno inacessível no kit | escolha — CT-69 (colapso ✱) |
| **P4** nenhum (papel sem `painel`) | escolha — CT-69 | *não se aplica*: sem painel acessível não existe pretendida acessível | escolha — CT-69 (colapso ✱) | escolha — CT-69 (colapso ✱) |

✱ **Colapso declarado, com o motivo.** Nestas células a ação (delegar a `urlPara()`) não depende de
qual sabor de pretendida chega, e a discriminação **interna** entre I2/I3/I4 já é provada sobre a
**mesma função** por CT-14, CT-15 e CT-16 da wiki `feat/login-unificado`
(`tests/Kit/LoginUnificadoTest.php`) — incluindo as sete variantes forjadas de I4. O que esta wiki
precisa provar é a **delegação**, e CT-69 a prova com uma linha por classe de resultado. Este
colapso vale **só** para a chave ligada: com ela desligada o código é novo e não herda nada.

### Chave DESLIGADA — descartar a inacessível e delegar ao `parent::toResponse()`

| | I1 ausente | I2 de painel acessível | I3 de painel inacessível | I4 forjada/enganosa |
|---|---|---|---|---|
| **P1** só `/app` | `/app` — CT-70 | `/app/users` — CT-70 | `/app` — CT-70 | `/app` — CT-70 (4 variantes) |
| **P2** só `/admin` | ⚠️ **CT-68 `@premissa`** | `/admin/users` — CT-70 | ⚠️ **CT-68 `@premissa`** | ⚠️ **CT-68 `@premissa`** |
| **P3** os três | `/app`, **não** a escolha — CT-70 | `/admin/users` — CT-70 | *não se aplica*: idem acima | `/app` — CT-70 |
| **P4** nenhum | ⚠️ lacuna declarada — P1 | *não se aplica*: idem acima | ⚠️ lacuna declarada — P1 | ⚠️ lacuna declarada — P1 |

As seis células ⚠️ são **a mesma pergunta P1**: com a chave desligada o fallback do Filament é
`Filament::getUrl()` — o painel que serve a tela de cadastro, sempre o `/app` —, e para P2 e P4 esse
painel é inacessível. RQ-01 diz "nunca"; a Ambiguidade 1 diz "não muda o fallback do Filament". Não
dá para cumprir as duas.

- Nas três células de **P2** o cenário é **escrito** (CT-68), por falha fechado, com o invariante que
  nenhuma resposta à P1 inverte: *a conta nova não é entregue numa URL de painel que ela não acessa*.
  Ele nasce vermelho contra a superfície prevista — de propósito, e é o valor de derivar do requisito.
- Nas três células de **P4** o cenário é **inexpressável** sem responder a P1: não existe destino que
  o kit possa afirmar, porque a escolha de painel com a chave desligada é explicitamente fora de
  escopo no `00`. Lacuna declarada, **com o que foi tentado**: (i) `route('login.painel')` —
  descartado, é a mudança de feature que a Ambiguidade 1 recusa; (ii) o login do `/app` com aviso,
  no molde do pendente em `RegistroPorConvite::register()` — descartado, inventa comportamento que o
  requisito não pede; (iii) `url('/')` — descartado, não há tela pública de destino no kit.

---

## Setup Global

### Suíte

`tests/Kit/DestinoAposCadastroTest.php` (grupo `kit`, sem tenancy) para CT-67…CT-75, e
`tests/Tenancy/DestinoAposCadastroTenancyTest.php` para CT-76. A escolha não é cosmética: o
`PapeisSeeder` cria papéis diferentes conforme a tenancy esteja ligada (`.ai/rules/testes.md`), e
nenhum cenário desta wiki precisa de `admin_app` — o que precisa da tenancy é só CT-76, pela
resolução de organização no destino.

```php
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});
```

### Personas — e por que estas

A persona é **dimensão**, não detalhe do exemplo. Percorrer a matriz só com `panel_user` produziria
uma tabela "100% coberta" com o defeito de R1 intacto, porque o painel dela **é** o painel que serve
a tela de cadastro — e aí o fallback errado do vendor acerta por acidente.

| Persona | Como nasce | Painéis | Por que é discriminante |
|---|---|---|---|
| **P1** | `ofertaPara('novo@example.com')` (papel `panel_user`, o default do helper) | só `/app` | o caso em que o fallback do vendor **acerta**: é o controle |
| **P2** | `ofertaPara('novo.admin@example.com', null, 'admin')` | só `/admin` | **a persona que separa a correção do acidente**. O próprio `tests/Kit/LoginUnificadoTest.php` já a documenta: *"`admin` é discriminante: não acessa o /app"*. É também o onboarding normal de administrador do kit |
| **P3** | `ofertaPara('novo.master@example.com', null, 'master_global')` | os três (via `Gate::before`) | separa "um painel → direto" de "vários → escolha", e é a única persona que mata o mutante *"a chave é lida invertida"* (R3/M6) |
| **P4** | papel criado no arranjo com `painel => null` que **não** é `master_global`, e convite para ele | nenhum | é a persona da Ambiguidade 2 do `00`. **Confirmar no arranjo**: `canAccessPanel()` cai em `temPapelDoPainel($panel->getId())` e devolve `false` para os três (`app/Models/User.php:canAccessPanel():190-205`) |

`ofertaPara()` **sempre com o papel explícito**: o default da `ConviteFactory` é o *primeiro* papel
da tabela, que é `master_global` (docblock do helper, `tests/Pest.php`). Um convite criado sem essa
linha concede o papel guarda-chuva e apaga a distinção P1/P2/P3 — todos os cenários passariam a
medir P3.

### Fixtures e arranjo

- **senha longa**: `'segredo-bem-longo-123'` nos dois campos — senha curta é recusada pelo campo do
  Filament e o cenário morreria no arranjo (molde de CT-55).
- **modo aberto**: `config(['kit.registro.habilitado' => true])`, e sem `?token=`.
- **modo convite**: o token vai pela query, **nunca** pelo construtor —
  `Livewire::withQueryParams(['token' => $token])->test(...)`, porque é `mount()` que o lê. É como o
  link do e-mail de convite entra (molde de CT-55).
- **semear a pretendida**: `session()->put('url.intended', url('/admin/users'))` quando o cenário
  quer a classe da pretendida sob controle; e `$this->get('/admin')` quando o cenário quer provar que
  **o caminho real do navegador** a grava (CT-72). As duas formas não são intercambiáveis: só a
  segunda prova a premissa do laudo do `00`.
- **painel corrente**: `Filament::setCurrentPanel('app')` antes de resolver o contrato — a resposta lê
  `Filament::auth()` e o fallback do vendor lê `Filament::getUrl()`, e os dois dependem do painel
  corrente. Molde de `entrarPelaPaginaUnica()`.

### Helpers já existentes — reusar, não clonar

`ligarLoginUnificado()`, `ofertaPara()`, `usuarioDoKit()`, `papelDoKit()`, `organizacaoComRegistro()`,
`espiarAutenticacao()`, `documentacaoDoKit()`, `painelRegistradoEmTeste()`. Helper novo usado pelos
**dois** arquivos (o de `Kit` e o de `Tenancy`) vai para `tests/Pest.php`, nunca para o arquivo de
teste — `.ai/rules/testes.md`, e o sintoma de errar é `Call to undefined function` em `--parallel`.

### Armadilhas de arnês que se aplicam aqui

| Armadilha | Consequência nesta wiki |
|---|---|
| `Filament::registerPanel()` não registra dentro de teste | nenhum cenário desta wiki precisa de painel novo; se algum precisar, é `painelRegistradoEmTeste()`, nunca a facade |
| `noPainelBootado('app')` estoura no `/app` (o Breezy lê `route()->parameter()`) | os cenários que precisam do painel `app` bootado usam um **GET real** antes do primeiro `Livewire::test()` — `.ai/rules/testes.md` |
| a decisão **consome** `session('url.intended')` | cenário que roda duas decisões na mesma sessão precisa semear de novo; CT-75 depende disso e é o único que o afirma |
| `assertRedirect()` depois de `->call('register')` funciona por um **hook incidental** do Livewire | `SupportFileDownloads::call()` (`vendor/livewire/livewire/src/Features/SupportFileDownloads/SupportFileDownloads.php:12-18`) chama `->toResponse(request())` em **todo** retorno de ação que seja `Responsable`, e `Contracts\RegistrationResponse` estende `Responsable`. É nessa chamada que o `redirect()` de dentro da resposta resolve o Redirector **do Livewire** e grava o efeito que `assertRedirect()` lê. Mesmo mecanismo de `->call('authenticate')` — mas o arquivo que o sustenta se chama "file downloads": se um upgrade mexer nesse hook, os CTs de componente desta wiki caem juntos e a mensagem não aponta para cá |

---

## Regra R1 — um cadastro que entra nunca termina na URL de um painel inacessível

> `RQ-01` · áreas A + B · perfil **completo** · técnica: **tabela de decisão** (células P2 e P4) +
> **rastreio de efeito** (consumo da pretendida)

O ponto da regra é que RQ-01 é absoluto e cobre **dois** caminhos, não um: a URL pretendida (o que o
laudo do `00` descreve) **e** o fallback `Filament::getUrl()` (que o laudo não menciona, e que erra
para toda conta cujo único painel não seja o que serve a tela). Um conjunto que só exercitasse a
pretendida deixaria metade da regra sem matador.

```gherkin
# language: pt

Funcionalidade: destino depois do cadastro

  Regra: um cadastro que entra nunca termina na URL de um painel que a conta nova não acessa

    Cenário: [CT-67] com a chave ligada e sem pretendida nenhuma, a conta convidada como admin cai no /admin
      Dado a chave "kit.login.unificado" ligada e nenhuma URL pretendida na sessão
      E um convite pendente de papel "admin" para "novo.admin@example.com", cujo único painel é o /admin
      Quando a visitante abre o link do convite em "/cadastro" e envia o formulário de cadastro
      Então ela é redirecionada para "/admin"
      E o "/admin" responde 200 para ela
      E a conta "novo.admin@example.com" existe, está autenticada e tem o papel "admin"

    Cenário: [CT-68] @premissa com a chave desligada e sem pretendida nenhuma, a conta convidada como admin não é entregue no /app
      Dado a chave "kit.login.unificado" desligada e nenhuma URL pretendida na sessão
      E um convite pendente de papel "admin" para "novo.admin@example.com", cujo único painel é o /admin
      Quando a visitante abre o link do convite em "/app/register" e envia o formulário de cadastro
      Então o destino do redirecionamento não é "/app"
      E o destino responde 200 para ela, e não 403
      E a conta "novo.admin@example.com" existe e está autenticada

    Esquema do Cenário: [CT-72] o 403 medido no navegador não volta, com a chave ligada e com ela desligada
      Dado a chave "kit.login.unificado" <chave> e nenhuma conta autenticada
      E um convite pendente de papel "panel_user" para "novo@example.com"
      Quando a visitante abre "/admin" como anônima, é mandada ao login, e então abre o link do convite e envia o formulário
      Então ela é redirecionada para "/app"
      E o "/app" responde 200 para ela
      E o destino não é "/admin/users"

      Exemplos:
        | chave      | # configuração                                       |
        | ligada     | a decisão é de DestinoAposLogin                      |
        | desligada  | a decisão é descartar e delegar ao Filament          |

    Esquema do Cenário: [CT-75] a pretendida é consumida pela decisão nos DOIS ramos, e não volta no redirecionamento seguinte
      Dado a chave "kit.login.unificado" <chave> e a URL pretendida "/admin/users" na sessão
      E um convite pendente de papel <papel> para "novo@example.com"
      Quando a visitante se cadastra pelo link do convite
      Então a sessão não tem mais "url.intended"
      E o pedido autenticado seguinte que redirecione termina em <painel>, e nunca em "/admin/users"

      Exemplos:
        | chave     | papel        | painel   | ramo       | # o que a linha discrimina                                  |
        | ligada    | "panel_user" | "/app"   | descartado | urlPara() faz pull; o mutante que só ignora deixa na sessão  |
        | ligada    | "admin"      | "/admin" | honrado    | honrar sem consumir — a pretendida reaparece no hop seguinte |
        | desligada | "panel_user" | "/app"   | descartado | o descarte tem de esquecer, não só deixar de olhar           |
        | desligada | "admin"      | "/admin" | honrado    | idem no ramo honrado, onde quem consome é o intended() cru   |
```

**Por que CT-75 tem os dois ramos, e não só o descartado.** Consumir a pretendida é código
**diferente** em cada ramo: no descartado quem apaga é o descarte, e no honrado quem apaga é o
`pull()` de quem a usou. Um conjunto que só exercitasse o ramo descartado deixaria vivo o mutante
*"honra a pretendida sem consumi-la"* — e o sintoma dele é o pior tipo, porque o primeiro
redirecionamento acerta e o defeito só aparece no seguinte. A persona `admin` é obrigatória nas
linhas do ramo honrado: com `panel_user` a pretendida `/admin/users` nunca é honrada, e o ramo não
seria exercitado.

**Sobre o `Quando` de CT-72.** Ele tem uma única ação de negócio — o cadastro. O `GET /admin` anônimo
é **arranjo**, e está escrito no `Quando` porque é ele que grava a pretendida pelo caminho real do
navegador: semear com `session()->put()` provaria a decisão, mas não a premissa do laudo. Na tradução
para Pest o `GET` fica antes do `Livewire::test()`.

**Por que CT-67 e CT-68 não são duas linhas do mesmo `Esquema`.** O oráculo difere em espécie, não em
valor: CT-67 afirma **a URL exata** (`/admin`, que `urlPara()` decide) e CT-68 afirma um **invariante**
(`não /app`), porque a URL certa depende da resposta à P1. Fundir os dois esconderia justamente que
uma das linhas é pergunta em aberto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a resposta valida a pretendida e mantém `Filament::getUrl()` como fallback — é o que o laudo do `00` literalmente pede, e é só metade da regra | **CT-67** (chave ligada, sem pretendida, painel ≠ tela) |
| M2 | com a chave desligada, o fallback continua o do Filament | **CT-68** — e a **superfície prevista É este mutante**. O cenário nasce vermelho; ver pergunta **P1** |
| M3 | descarta a pretendida da decisão e a deixa na sessão | **CT-75**, linhas do ramo descartado — o 403 voltaria um redirecionamento depois, e nenhuma asserção sobre o destino o pega |
| M5 | **honra** a pretendida acessível sem consumi-la (lê com `get()` no lugar de `pull()`) | **CT-75**, linhas do ramo honrado. É o mutante mais traiçoeiro do conjunto: o primeiro redirecionamento acerta, e o defeito só aparece no seguinte |
| M4 | a correção é escrita mas o contrato não é vinculado, ou é vinculado a `Filament\Auth\Http\Responses\RegistrationResponse` (a **classe**) em vez de `…\Contracts\RegistrationResponse` (o **contrato** que `Register::register():112` resolve) | **CT-71** e **CT-72** — as duas telas continuariam no comportamento de hoje |

---

## Regra R2 — com a chave ligada, o destino é o que `DestinoAposLogin` decide para o login

> `RQ-02`, `RQ-04` · área A · perfil **padrão** · técnica: **partição por classe de resultado** de
> `urlPara()`

RQ-02 é uma **restrição** ("a mesma decisão do login, não uma regra nova"), e o observável dela é que
os quatro resultados possíveis de `urlPara()` aparecem depois do cadastro — inclusive os dois que o
Filament não tem como produzir: a escolha de painel e o descarte da pretendida.

O cenário roda **por fora do componente de UI**, resolvendo o contrato do container. Isso é
obrigatório, não estilo: R2 é regra de autorização, e teste de componente não distingue *"a regra
existe"* de *"a tela chama a regra"*.

```gherkin
# language: pt

  Regra: com a chave ligada, o destino depois do cadastro é o que DestinoAposLogin decide para o login

    Esquema do Cenário: [CT-69] a resposta de cadastro devolve, por classe de resultado, a mesma URL do login
      Dado a chave "kit.login.unificado" ligada e o painel corrente sendo o /app
      E uma conta recém-cadastrada cujos painéis acessíveis são <painéis>
      E a URL pretendida <pretendida> na sessão
      Quando a resposta de cadastro do sistema é resolvida e executada para essa conta
      Então a URL de destino é <destino>

      Exemplos:
        | painéis   | pretendida                      | destino         | # classe de resultado                         |
        | só /admin | "/admin/users"                  | "/admin/users"  | pretendida acessível vence                    |
        | só /app   | "/admin/users"                  | "/app"          | pretendida inacessível é descartada           |
        | só /app   | nenhuma                         | "/app"          | um painel, sem pretendida                     |
        | os três   | nenhuma                         | "/login/painel" | vários painéis, sem pretendida → escolha      |
        | os três   | "/admin/users"                  | "/admin/users"  | a pretendida vence a escolha                  |
        | nenhum    | nenhuma                         | "/login/painel" | zero painéis → escolha, que encerra a sessão  |
        | só /app   | "https://evil.test/app/users"   | "/app"          | pretendida forjada é descartada               |
```

A última linha é **uma** representante de I4. As outras variantes de forja (`//evil.test/…`,
`/\evil.test/…`, `https://evil.test\@localhost/…`, `javascript://…`, `/administracao/x`) são as sete
linhas de CT-15 da wiki `feat/login-unificado`, sobre a **mesma** função — é o colapso ✱ declarado na
matriz, e ele vale só aqui.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a pretendida acessível é descartada junto — a validação foi escrita ao contrário | CT-69, linhas 1 e 5 |
| M2 | a pretendida é honrada sem validar o painel — o defeito de hoje, apenas movido de lugar | CT-69, linha 2 |
| M3 | `count($paineis) === 1` virou `>= 1`: o primeiro painel vence a escolha | CT-69, linha 4 |
| M4 | zero painéis cai em `Filament::getUrl()` em vez da escolha, e a pessoa vê 403 no lugar do aviso | CT-69, linha 6 |
| M5 | a resposta **reimplementa** a decisão em vez de chamar `DestinoAposLogin` — viola RQ-02 sem mudar nenhum destino | ⚠️ **sem matador por asserção de comportamento**: as duas implementações produzem a mesma URL em todas as linhas. Lacuna declarada, com o que foi tentado — (i) espiar o log `[DestinoAposLogin@urlPara]` com `espiarAutenticacao()`: descartado, ancoraria o caso numa string de log e RQ-02 não fala de log; (ii) teste de arquitetura afirmando que `RespostaDeCadastro` referencia `DestinoAposLogin`: **viável e recomendado ao `01`**, mas é asserção estrutural e não cenário — entra como passo do plano, não como CT |

---

## Regra R3 — com a chave desligada, descarta a inacessível, honra a acessível, não troca o fallback

> `RQ-03`, `RQ-04` · área B · perfil **completo** · técnica: **tabela de decisão** + **valor limite de
> prefixo de path** (incremento: 1 caractere)

É o único código novo da correção e o caminho que a instalação default roda. Também roda **por fora
do componente**, pelo mesmo motivo de R2.

```gherkin
# language: pt

  Regra: com a chave desligada, a pretendida inacessível é descartada e o destino segue sendo o do Filament

    Esquema do Cenário: [CT-70] o descarte com a chave desligada
      Dado a chave "kit.login.unificado" desligada e o painel corrente sendo o /app
      E uma conta recém-cadastrada cujos painéis acessíveis são <painéis>
      E a URL pretendida <pretendida> na sessão
      Quando a resposta de cadastro do sistema é resolvida e executada para essa conta
      Então a URL de destino é <destino>

      Exemplos:
        | painéis   | pretendida                               | destino        | # o que a linha discrimina                            |
        | só /app   | "/admin/users"                           | "/app"         | não descartar nada — o defeito de hoje                |
        | só /app   | "/app/users"                             | "/app/users"   | descartar sempre — RQ-04 quebrado                     |
        | só /admin | "/admin/users"                           | "/admin/users" | honrar também painel diferente do da tela             |
        | só /app   | nenhuma                                  | "/app"         | ausente ≠ vazia ≠ preenchida (1 de 3)                 |
        | só /app   | "" (gravada vazia)                       | "/app"         | esquecer, não branquear: put('') daria "/" (2 de 3)   |
        | só /app   | "/application/x"                         | "/app"         | prefixo sem a barra — limite de 1 caractere           |
        | só /app   | "/app"                                   | "/app"         | raiz exata é acessível — o outro lado do mesmo limite  |
        | só /app   | "https://evil.test\@localhost/app/users" | "/app"         | comparar só o host do parse_url()                     |
        | só /app   | "//evil.test/app"                        | "/app"         | path que começa por "//"                              |
        | os três   | nenhuma                                  | "/app"         | a chave lida invertida — aqui **não** há escolha      |
        | os três   | "/admin/users"                           | "/admin/users" | a pretendida acessível vence o fallback               |
```

Três linhas parecem decorativas e não são:

- **`"/app"` raiz exata → `/app`**: o destino coincide com o fallback, então ela sozinha não
  discrimina. Ela existe **em par** com `"/application/x"`: juntas fixam a fronteira do prefixo dos
  dois lados (`/app` casa, `/application` não), que é o valor limite desta regra. Quem dá
  discriminação ao lado "honrado" com destino ≠ fallback é a linha `só /admin` + `"/admin/users"`.
- **`""` gravada vazia → `/app`, e não `/`**: `redirect()->intended()` do Laravel faz
  `pull('url.intended', $default)`, e uma string vazia gravada vence o default — o destino viraria a
  raiz da aplicação. É o único jeito de separar `forget()` de `put('')`.
- **`os três` + nenhuma → `/app`**: é a única linha que mata *"a chave é lida invertida"*, e é também
  a que **testa a premissa declarada** da Ambiguidade 1 do `00` ("não inventa tela de escolha" com a
  chave desligada). Se a P1 for respondida no sentido de estender a escolha à chave desligada, é
  esta linha que inverte.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | não descarta nada — o `redirect()->intended()` cru do Filament sobrevive | CT-70, linha 1 |
| M2 | descarta sempre que a chave está desligada | CT-70, linhas 2 e 3 |
| M3 | `str_starts_with($pretendida, '/app')` sem exigir a barra | CT-70, linha 6 (e a 7 fixa o outro lado) |
| M4 | compara só o `parse_url(…, PHP_URL_HOST)`, ou aceita path que começa por `//` e `/\` | CT-70, linhas 8 e 9 |
| M5 | grava `session()->put('url.intended', '')` em vez de `forget()` | CT-70, linha 5 |
| M6 | a chave é lida invertida, ou a resposta chama `urlPara()` também com ela desligada — e a pessoa cai na escolha de painel, que a Ambiguidade 1 recusa | CT-70, linha 10 |

---

## Regra R4 — a correção vale nas duas telas e nos dois modos, porque vive no contrato

> `RQ-01` + superfície · área C · perfil **padrão** · técnica: **partição de interface**

Duas telas × dois modos de cadastro. A correção morar no contrato é mecanismo; o **observável** é que
nenhuma das quatro combinações fica de fora. Aqui a camada é o componente Livewire, porque o que se
prova é a ligação entre a tela e a decisão.

```gherkin
# language: pt

  Regra: as duas telas de cadastro e os dois modos usam a mesma decisão de destino

    Esquema do Cenário: [CT-71] toda tela de cadastro passa pela decisão, nos dois modos
      Dado a chave "kit.login.unificado" <chave>
      E a URL pretendida "/admin/users" na sessão
      E um destino de cadastro no modo <modo>, para uma conta cujo único painel é o /app
      Quando a visitante envia o formulário de <tela>
      Então ela é redirecionada para "/app", e não para "/admin/users"
      E a conta existe, está autenticada e tem o papel "panel_user"

      Exemplos:
        | chave     | tela            | modo            | # o que a linha discrimina                        |
        | ligada    | "/cadastro"     | convite         | a tela sem prefixo                                |
        | ligada    | "/cadastro"     | cadastro aberto | a correção não lê $this->convite                  |
        | desligada | "/app/register" | convite         | a tela do painel, no caminho default do kit       |
        | desligada | "/app/register" | cadastro aberto | as duas dimensões cruzadas na configuração default |
```

Com a chave ligada `/app/register` redireciona para `/cadastro`, e com ela desligada `/cadastro`
redireciona para `/app/register` (CT-51 e CT-52 da wiki `login-unificado-telas-externas`): as quatro
combinações ausentes da tabela **não existem** como tela servida, e é por isso que a partição de
interface tem 4 linhas e não 8.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o bind fica dentro de um `if (ConfiguracaoDoLogin::unificado())` — e a instalação default, que é a de chave desligada, não recebe a correção | CT-71, linhas 3 e 4 |
| M2 | a correção é escrita em `CadastroUnificado::register()` em vez da resposta: `/app/register` fica sem ela | CT-71, linhas 3 e 4 |
| M3 | a correção só vale no modo convite (lê `$this->convite` ou o token) | CT-71, linhas 2 e 4 |

---

## Regra R5 — a entrega fica registrada na documentação e no CHANGELOG

> `RQ-05` · área D · perfil **mínimo** · técnica: **partição** (pt, en, CHANGELOG)

```gherkin
# language: pt

  Regra: a correção do destino depois do cadastro está registrada na documentação do kit

    Esquema do Cenário: [CT-73] a documentação de cada idioma e o CHANGELOG citam a correção
      Dado a documentação do kit em <alvo>
      Quando ela é lida por inteiro
      Então ela cita o destino depois do cadastro

      Exemplos:
        | alvo         |
        | "pt"         |
        | "en"         |
        | "CHANGELOG"  |
```

Duas restrições do projeto valem aqui, e as duas já custaram caro:

- a asserção é sobre o **arquivo inteiro**, nunca sobre a primeira seção `## [` do `CHANGELOG.md`:
  recortar o topo faz o caso expirar sozinho na release seguinte, reprovando uma feature que nada
  tem a ver com ele (`.ai/rules/testes.md`);
- `documentacaoDoKit($idioma)` de `tests/Pest.php` é o alvo dos dois primeiros — o oráculo é *"a
  documentação deste idioma afirma X"*, e não *"o arquivo Y afirma X"*.

**O termo asserido tem de ser fixado pelo `01`**, e teste e documentação mudam no mesmo commit. Sem
isso o caso vira asserção sobre uma frase que ninguém prometeu.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | só o CHANGELOG é atualizado, a documentação de usuário não | CT-73, linhas "pt" e "en" |
| M2 | só o `pt` é atualizado e o `en` fica atrás — o modo de falha normal de kit bilíngue | CT-73, linha "en" |

---

## Regra R6 — regressão: o cadastro pendente continua encerrando a sessão

> Origem: `00` → `## Fora de Escopo` ("o cadastro **pendente de aprovação**, que já tem tratamento
> próprio e encerra a sessão") · área C · perfil **padrão**

O pendente está fora de escopo como *comportamento a mudar* — e é exatamente por isso que precisa de
cenário: a correção mexe no ponto em que `RegistroPorConvite::register()` decide descartar a resposta
do vendor. O oráculo sai da frase do próprio `00`, não de uma RQ nova.

```gherkin
# language: pt

  Regra: o cadastro pendente de aprovação não recebe destino nenhum — a sessão é encerrada

    Esquema do Cenário: [CT-74] com aprovação manual ligada, o cadastro pendente termina no login e sem sessão
      Dado a chave "kit.login.unificado" <chave> e a aprovação manual de cadastro ligada
      E o cadastro aberto habilitado
      E a URL pretendida "/admin/users" na sessão
      Quando a visitante envia o formulário de cadastro
      Então ela é redirecionada para o login do /app
      E não há ninguém autenticado
      E a conta existe com "aprovacao_pendente" verdadeiro e sem papel nenhum
      E o destino não é "/admin/users" nem "/login/painel"

      Exemplos:
        | chave     |
        | ligada    |
        | desligada |
```

A última linha do `Então` é o não-efeito, e ela **discrimina de verdade**: a pretendida `/admin/users`
está na sessão, e uma implementação que roteasse o pendente pela resposta nova a honraria (com a
chave desligada) ou mandaria à escolha (com ela ligada). O alvo do efeito existe no `Dado` — não é
asserção de ausência em mundo vazio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `RegistroPorConvite::register()` passa a delegar o pendente à resposta nova, e o pendente vai para a escolha (chave ligada) ou para `/app` (desligada) — em vez do login, e com a sessão viva | CT-74, as duas linhas |
| M2 | a resposta nova consome `url.intended` no construtor, ou fora do `toResponse()`, e o pendente a perde antes do tratamento próprio | CT-74 (o destino continua o login) + CT-75 (o consumo é do `toResponse()`) |

---

## Regra R2 sob tenancy — a célula que só a plataforma expõe

> `RQ-01`, `RQ-02` · área A · técnica: **partição de plataforma** (uma célula, não uma matriz nova)

```gherkin
# language: pt

  Regra: com a tenancy ligada, o destino do cadastro não tenta resolver organização de painel que não é o destino

    Cenário: [CT-76] com tenancy e a chave ligada, a conta convidada como admin cai no /admin sem passar pelo /app
      Dado a tenancy ligada e a chave "kit.login.unificado" ligada
      E um convite pendente de papel "admin", sem organização, para "novo.admin@example.com"
      Quando a visitante abre o link do convite em "/cadastro" e envia o formulário de cadastro
      Então ela é redirecionada para "/admin"
      E o "/admin" responde 200 para ela
      E a conta não pertence a organização nenhuma
```

**Por que uma célula de tenancy e não a matriz toda.** Com a tenancy ligada, o fallback do vendor não
erra apenas o painel: `Filament::getUrl()` no `/app` procura a organização de destino de quem não tem
nenhuma — o mesmo estado inalcançável que `RegistroAberto::exigirPortaAberta()` recusa criar (ramo
`sem_organizacao`). O modo de falha deixa de ser 403 e passa a ser erro de resolução de organização,
que é sintoma **diferente** de tudo o que os cenários sem tenancy produzem. As outras 31 células não
mudam de comportamento com a tenancy: a decisão é sobre painel, e a organização só entra na montagem
da URL do `/app`, já coberta pelos CTs de login.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o destino é montado a partir do painel **corrente** (o `/app`, emprestado pelo `panel:app`) em vez do painel decidido, e morre procurando organização | CT-76 |
| M2 | a decisão passa a exigir organização para qualquer conta nova, e a conta só de `/admin` é recusada ou devolvida ao login | CT-76 — o `/admin` responde 200 e a conta não pertence a organização nenhuma |

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou
     "lacuna declarada: {o que foi tentado}". Nunca "sim". -->

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **lacuna declarada, herdada**: a pretendida é validada por **painel**, não por recurso, então `/app/{organização alheia}` é honrada e cai no 404 do tenant. Já é Info **aceito** da wiki `feat/login-unificado` (`03-progresso.md`: *"pretendida `/app/{tenant alheio}` cai no 404 do tenant"*), e a correção não muda o mecanismo. Tentado: escrever a linha em CT-70 — descartado, afirmaria comportamento que o requisito não pede e que a wiki anterior aceitou por escrito |
| Autorização exercida na ação, não só consultada | **CT-69** e **CT-70** (contrato resolvido do container, por fora do componente de UI) + **CT-71** (a tela) — o par que o gate de camada exige |
| Idempotência ancorada no agregado | **CT-75**: o agregado é a **sessão**, e a asserção é sobre o estado persistido dela depois da decisão, não sobre o retorno da chamada. Cadastrar duas vezes o mesmo e-mail não é o eixo — o `->unique()` do campo o recusa, e é regra anterior a esta entrega |
| Fronteira no ponto de entrada (gravação) | **CT-70**, linhas `/app` × `/application/x` — valor limite de prefixo, incremento de 1 caractere |
| Domínio condicionado (o válido de um campo depende de outro) | **CT-69** e **CT-70**: o domínio válido da pretendida depende dos painéis da conta — é o cruzamento das duas dimensões, não a fronteira do campo isolado |
| Cardinalidade do alvo (0 / 1 / N) | **CT-69**, linhas "nenhum", "só /app"/"só /admin" e "os três" painéis |
| Ausente ≠ null ≠ vazio | **CT-70**, linhas "nenhuma" e `""`. Semântica declarada: ausente e vazia levam ao mesmo destino, mas por caminhos diferentes — e é a vazia que mata `put('')` no lugar de `forget()` |
| Efeito colateral: log e carimbo de painel do `authentication_log` | **lacuna declarada**. Reusar `urlPara()` escreve `[DestinoAposLogin@urlPara] Destino após login decidido` para um **cadastro**, e tenta carimbar o painel do último acesso. Tentado: (i) asserção por `espiarAutenticacao()` — descartada, o requisito não fala de log e o caso ficaria ancorado numa string; (ii) asserção sobre `authentication_log.painel` — descartada porque `carimbarAcesso()` filtra `whereNull('painel')` e o `creating` do `KitServiceProvider` já preencheu a coluna com o painel corrente no cadastro, então o `update` não acha linha e o observável não muda. Fica como **pergunta P3** |
| Estado × operação de escrita (registro desativado ainda funciona?) | **não se aplica**: conta recém-criada não nasce inativa nem excluída. O análogo — conta indisponível ⇒ zero painéis (`User::canAccessPanel():150-163`) — não é alcançável no cadastro, e o único caminho de zero painéis que é alcançável (papel sem `painel`) é a persona P4: coberta em CT-69 com a chave ligada, e lacuna declarada com ela desligada |
| Concorrência | **não se aplica**: a decisão é uma leitura de sessão por request, sem contador, saldo ou limite. Dois cadastros simultâneos do mesmo e-mail são serializados pelo `->unique()`, regra anterior à entrega |
| Mass assignment | **não se aplica**: a correção não acrescenta campo de formulário nem parâmetro de request; a pretendida não é parâmetro — só o middleware `Authenticate` a grava |
| CRUD combinado (ler/editar/excluir inexistente, duas vezes) | **não se aplica**: não há recurso a manipular; a entrega é uma decisão de redirecionamento |
| Paginação / ordenação | **não se aplica**: sem listagem |
| Timezone / DST | **não se aplica**: nenhuma comparação temporal na decisão |
| Unicode / limite de varchar | **não se aplica** ao domínio da regra: a pretendida é URL, e as formas maliciosas dela estão em CT-70 (host, `//`, `\`, prefixo) |
| Unicidade + soft delete | **não se aplica**: sem coluna única nova |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| Regressão adjacente | **CT-74** (pendente) e **CT-71** linhas 3 e 4 (a tela do painel na configuração default) |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-67 | chave ligada, sem pretendida, conta só de `/admin` → `/admin` e 200 | R1 | tabela de decisão (célula P2/I1) | componente Livewire + 1 GET | `tests/Kit/DestinoAposCadastroTest.php` | R1/M1 |
| CT-68 | ⚠️ `@premissa` chave **desligada**, sem pretendida, conta só de `/admin` → não `/app` | R1 | falha fechado + invariante | componente Livewire + 1 GET | `tests/Kit/DestinoAposCadastroTest.php` | R1/M2 (**nasce vermelho** — P1) |
| CT-69 | com a chave ligada, uma linha por classe de resultado de `urlPara()` | R2 | partição por classe de resultado | Feature (contrato, fora da UI) | `tests/Kit/DestinoAposCadastroTest.php` | R2/M1…M4 |
| CT-70 | com a chave desligada, o descarte: 11 linhas | R3 | tabela de decisão + valor limite de prefixo | Feature (contrato, fora da UI) | `tests/Kit/DestinoAposCadastroTest.php` | R3/M1…M6 |
| CT-71 | as duas telas × os dois modos passam pela decisão | R4 | partição de interface | componente Livewire | `tests/Kit/DestinoAposCadastroTest.php` | R4/M1…M3, R1/M4 |
| CT-72 | o 403 medido não volta: `GET /admin` anônimo → convite → destino 200 | R1 | regressão de ponta a ponta | Feature HTTP + componente | `tests/Kit/DestinoAposCadastroTest.php` | R1/M1, R1/M4 |
| CT-73 | documentação pt, en e CHANGELOG citam a correção | R5 | partição | Feature (arquivo) | `tests/Kit/DestinoAposCadastroTest.php` | R5/M1, R5/M2 |
| CT-74 | pendente de aprovação: login, sem sessão, sem honrar a pretendida | R6 | regressão | componente Livewire | `tests/Kit/DestinoAposCadastroTest.php` | R6/M1, R6/M2 |
| CT-75 | a pretendida é consumida nos dois ramos e não volta no hop seguinte | R1 | rastreio de efeito | Feature (contrato, fora da UI) | `tests/Kit/DestinoAposCadastroTest.php` | R1/M3, R1/M5, R6/M2 |
| CT-76 | com tenancy, conta só de `/admin` cai no `/admin` sem procurar organização | R2 sob tenancy | partição de plataforma | componente Livewire + 1 GET | `tests/Tenancy/DestinoAposCadastroTenancyTest.php` | tenancy/M1, M2 |

**Teto do perfil.** R1 tem 4 cenários contra teto 5 do perfil completo; R3 tem 1 `Esquema` (um
`Esquema` conta como 1 cenário, não como 11 linhas). Nenhuma regra estourou o teto.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| repetir as sete variantes de pretendida forjada com a chave **ligada** | já provadas sobre a mesma função por CT-15 da wiki `feat/login-unificado`; é o colapso ✱ declarado na matriz. Com a chave desligada elas **não** foram cortadas, porque o código é novo |
| um cenário `Unit` sobre o método novo de `DestinoAposLogin` isolado | ele não existe ainda, e derivar um caso a partir da assinatura prevista seria testar a implementação imaginada. CT-70 prova o mesmo comportamento pelo contrato, uma camada acima e sem presumir o nome do método |
| cadastro pelo provedor social com convite de papel `admin` | mata um mutante real (`urlDoPerfil()` não consulta decisão nenhuma), mas o mecanismo está **fora** da superfície prevista, e a resposta à **P2** decide se ele entra nesta wiki ou em outra. Registrado como pergunta, não como cenário: escrevê-lo agora produziria um vermelho cuja correção ninguém aprovou |
| `assertNoJavaScriptErrors()` nas telas de cadastro | as telas não mudam; a correção é decisão de redirecionamento no servidor. A cobertura de tela já existe (`telasDoKit()`) |
| duplicar a matriz inteira na suíte `Tenancy` | as 31 células restantes não mudam de comportamento com a tenancy; só a montagem da URL do `/app` muda, e ela já é coberta pelos CTs de login. Uma célula (CT-76) é o que a plataforma acrescenta |
| dois cadastros consecutivos na mesma sessão | o `->unique()` do e-mail recusa o segundo com o mesmo endereço, e com endereços diferentes o caso mede o `pull` da sessão, que é CT-75 |

---

## Sem CT-B

O gate do `05` **não passa**, e por isso o arquivo não existe.

- **Não há superfície de UI nova**: nenhuma tela, campo, ação, modal, coluna ou texto muda. A entrega
  é a troca de uma classe de resposta HTTP resolvida pelo container.
- **Nenhum cenário afirma sobre algo que só o navegador prova**: não há JavaScript executado, erro de
  console, acessibilidade, cor, tema ou layout no oráculo de nenhum dos 10 CTs. Todos afirmam sobre
  **URL de destino**, **código de resposta** e **estado de sessão** — que o arnês HTTP e o teste de
  componente Livewire falsificam integralmente, e em milissegundos.
- **O defeito original ter sido medido no navegador não é argumento para CT-B**: o observável dele é
  um **403 do servidor**, num endereço decidido no servidor. CT-72 reproduz a cadeia inteira
  (`GET /admin` anônimo → gravação da pretendida → cadastro → destino) na camada HTTP, que é onde a
  cadeia de fato acontece.
- Consequência de custo: `--parallel` continua valendo para esta wiki, porque nenhum caso de browser
  entra na invocação (`.ai/rules/testes-browser.md`).

---

## Perguntas para o `00-requisito.md`

> **Desvio declarado**: a skill manda replicar as perguntas em `00-requisito.md` →
> `## Ambiguidades e Perguntas Abertas`. O `00` está **sendo editado por outro agente** neste
> momento, então elas ficam aqui, em bloco pronto para colagem. Elas continuam **bloqueando** o que
> delas depende: **P1 bloqueia R1 e R3** (CT-68 e seis células da matriz), **P2 bloqueia o alcance de
> RQ-01**, **P3 e P4 não bloqueiam**.

```markdown
- **P1 — RQ-01 é absoluto e a premissa da Ambiguidade 1 o contradiz para a conta cujo único painel
  não é o da tela de cadastro.** RQ-01 diz "**nunca** é entregue na URL de um painel que ela não
  acessa"; a Ambiguidade 1 assume que, com a chave desligada, a correção "não muda o fallback do
  Filament". Esse fallback é `Filament::getUrl()`
  (`vendor/filament/filament/src/Auth/Http/Responses/RegistrationResponse.php:toResponse():12-15`)
  — o painel que serve a tela, sempre o `/app`. Uma conta convidada como `admin` (que é o
  onboarding normal de administrador do kit) acessa só o `/admin`: com a chave desligada, e **sem
  nenhuma URL pretendida envolvida**, ela recebe `/app` e vê o mesmo 403 do laudo. O laudo do `00`
  descreve só a metade da pretendida; esta é a outra metade do mesmo defeito.
  - **Assumido** (falha fechado, porque a cláusula de RQ-01 é absoluta e o 403 é o observável que a
    entrega existe para eliminar): com a chave desligada, a resposta **também** substitui o fallback
    quando `Filament::getUrl()` aponta para painel inacessível, escolhendo o único painel acessível
    da conta. Isso não cria tela de escolha e não contradiz a Ambiguidade 1 no ponto que ela
    realmente decide, que é o caso de zero painéis.
  - **Se negado** (o fallback fica intocado com a chave desligada): RQ-01 ganha o recorte "quando
    houver URL pretendida", **CT-68 sai do conjunto**, e as três células P2 da matriz da chave
    desligada passam a `não se aplica` — e a instalação default continua entregando 403 a todo
    administrador convidado. Nesse caso o `00` precisa dizer isso explicitamente, porque hoje ele
    diz o contrário.
  - **Invariante afirmado nos dois sentidos** (CT-68 o assere, e nenhuma resposta o inverte): a
    conta nova nunca recebe uma URL cujo painel `canAccessPanel()` nega.

- **P2 — o cadastro por provedor social não passa pela correção, e RQ-01 não o exclui.** A superfície
  prevista é o contrato `RegistrationResponse`, e `LoginSocialController::retorno()` não o resolve:
  conta nova vai para `urlDoPerfil()` e conta existente para `urlDoPainel()`
  (`app/Http/Controllers/Auth/LoginSocialController.php:352`). `urlDoPerfil()` monta a rota do perfil
  de `painelDeDestino()` — o painel de origem —, **inclusive com a chave ligada** (`:761-774`).
  Então uma conta criada por provedor a partir de um convite de papel `admin` termina em
  `/app/meu-perfil` e vê 403, exatamente o defeito relatado. O `## Fora de Escopo` do `00` lista o
  login, a verificação de e-mail e a redefinição de senha — **não** lista o cadastro social.
  - **E a suíte atual é cega para isso, medido**: em `tests/Kit/CadastroSocialPorConviteTest.php` e
    `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php`, **todo** `assertRedirect()` de
    caminho de sucesso é **sem argumento** (só as recusas afirmam URL, e afirmam a do login), e os
    dois arquivos usam **só** `role_id => panel_user` — a persona cujo painel É o `/app`. Oráculo
    fraco somado a persona colapsada: o defeito passaria verde na suíte inteira hoje.
  - **Assumido** (falha fechado): RQ-01 vale para todo cadastro que entra, inclusive o social, e o
    alcance da correção precisa incluir o destino de conta nova por provedor.
  - **Se negado**: acrescentar o cadastro social ao `## Fora de Escopo` com o motivo, e abrir a wiki
    de correção dele — porque o sintoma é o mesmo que esta entrega existe para eliminar, e fechar só
    um dos dois chamadores deixa o outro quebrado em silêncio.

- **P3 — o que o `authentication_log` deve registrar como painel de um cadastro?** (não bloqueante)
  Reusar `DestinoAposLogin::urlPara()` faz o cadastro escrever a linha
  `[DestinoAposLogin@urlPara] Destino após login decidido` e tentar carimbar o painel do último
  acesso (`carimbarAcesso()`, filtrando `whereNull('painel')`). No cadastro a coluna já vem
  preenchida com o painel **corrente** pelo `creating` do `KitServiceProvider` — que na tela de
  cadastro é sempre o `app`, mesmo quando a pessoa entra no `/admin`. Então o stat "acessos por
  painel" atribui ao `app` um acesso que foi ao `/admin`.
  - **Assumido**: o comportamento de hoje é mantido, e nenhum cenário desta wiki afirma sobre o log
    ou sobre o carimbo (registrado como lacuna declarada no checklist de taxonomia).
  - **Se a resposta for "o carimbo deve seguir o destino"**: entra um passo no plano e um cenário
    novo nesta wiki, no molde de R9 da wiki `feat/login-unificado`.

- **P4 — a Ambiguidade 1 do `00` conflaciona "papel que não dá `/app`" com "sem painel nenhum".**
  (não bloqueante) O texto diz "conta nova sem painel nenhum. O caso existe (convite com papel que
  não dá `/app`)", mas um papel que não dá `/app` normalmente dá **outro** painel (`admin`,
  `infra`) — o que é a persona P2 desta derivação, não P4. São situações diferentes e com destinos
  diferentes: P2 tem um painel para onde ir, P4 não tem nenhum. A distinção é exatamente o que
  decide se a correção resolve ou se a pessoa continua vendo 403.
  - **Assumido**: as duas são tratadas como personas distintas aqui (P2 e P4 da matriz), e P4 exige
    papel sem `painel` criado à mão — nenhum papel semeado pelo `PapeisSeeder` produz zero painéis,
    porque `master_global` entra pelo `Gate::before`.
  - **Se negado**: a Ambiguidade 1 é reescrita para nomear qual das duas ela decide.
```

---

## Premissas não confirmadas

Estas afirmações sustentam decisões deste arquivo e **não** foram confirmadas em execução. Se alguma
cair, o cenário indicado muda de **camada**, nunca de oráculo.

| Premissa | Situação | Consequência se cair |
|---|---|---|
| `Livewire::test(...)->call('register')->assertRedirect($url)` funciona | ✅ **confirmada no vendor**: `SupportFileDownloads::call()` chama `->toResponse(request())` em todo retorno `Responsable` de ação (`vendor/livewire/livewire/src/Features/SupportFileDownloads/SupportFileDownloads.php:12-18`), e `Contracts\RegistrationResponse` estende `Responsable`. É o mesmo caminho que já deixa `->call('authenticate')` verde na suíte atual | — (mas ver a armadilha na tabela de arnês: o hook que sustenta isso não se chama nada parecido com redirecionamento) |
| `redirect()->intended()` devolve a raiz quando `url.intended` está gravada como `''` | ✅ **confirmada no vendor**: `Redirector::intended():95-100` faz `pull('url.intended', $default)` e o default só vale quando a **chave não existe** — gravada vazia, `to('')` devolve a raiz da aplicação | — |
| um papel com `painel => null` que não seja `master_global` produz zero painéis | ✅ **confirmada no código**: `canAccessPanel()` resolve `isMasterGlobal()` **antes** e cai em `temPapelDoPainel($panel->getId())` depois, e o docblock de `User::papelDoPainel()` é explícito — *"nulo não é coringa, quem o faz entrar em todo painel é o `Gate::before`"* | — |
| resolver `app(RegistrationResponse::class)->toResponse(request())` com `actingAs` + `Filament::setCurrentPanel('app')` reproduz a decisão | ⚠️ **não executada**. É o molde de `RespostaDeLogin`, que lê `Filament::auth()->user()`, e fora de um request Livewire o `redirect()` devolve um `RedirectResponse` real cujo `getTargetUrl()` é asserível — mas não há caso equivalente no repositório para a resposta de **cadastro** | CT-69, CT-70 e CT-75 sobem para a camada de componente, e a exigência de "≥1 cenário por fora do componente de UI" passa a ser cumprida por um caso HTTP direto. O oráculo não muda |
| um convite de papel `admin` sem organização é aceito com a tenancy ligada | ⚠️ **não executada**: `Convite::aceitar()` resolve o contexto por `contextoDoPapel()`, e papel de painel sem tenancy vai para o contexto global — lido, não rodado | CT-76 troca a persona por `infra`, ou passa a exigir organização e o oráculo se restringe ao painel de destino |

---

## Revisão Adversarial

**Disparo**: perfil completo na área B **e** Impacto 3 nas áreas A, B e C. Executada por sub-agente
que não derivou os cenários, recebendo apenas `00-requisito.md` e este arquivo — sem a superfície
prevista, sem o código e sem o raciocínio da derivação.

**Áreas e regras percorridas**: A, B, C e D; R1 a R6 e a célula de tenancy.

| # | Achado | O que virou |
|---|---|---|
| A1 | *(a preencher no fechamento da rodada)* | |

<!-- Fechamento: cada achado vira cenário novo, oráculo reescrito, ou lacuna declarada com motivo.
     Re-revisar uma única vez, e só se o fechamento criou cenário novo. Teto: 2 rodadas. -->
