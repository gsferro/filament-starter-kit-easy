# Casos de Teste — Proteção anti-robô nas telas públicas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. O PRD entrou só para nomes de classe, rotas e a tabela de
> superfície. Nenhum cenário foi escrito olhando implementação — ela não existia.
>
> Pipeline da skill `feature-test-design` seguido pelo mesmo agente (perfil de risco, SFDIPOT, mapa
> de regras, técnica por regra, taxonomia, mutantes). Declarado no `03-progresso.md`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A verificação do token (regra de validação + provedor externo) | 3 | 3 | 9 | completo |
| A regra das duas condições (liga/desliga) | 2 | 3 | 6 | padrão |
| Segredo no Settings e na tela | 2 | 3 | 6 | padrão |
| Presença/ausência do widget nas três telas | 1 | 2 | 2 | mínimo |

- Técnicas aplicadas: tabela de decisão (duas condições), partição de equivalência (resposta do
  provedor; provedor), rastreio de efeito (autenticação, e-mail de redefinição, conta criada, log),
  trio de segredo da rule `pages.md`.
- Cenários: 19 · Regras: 8 · Mutantes previstos: 24 · Sem matador: 2 (declarados)

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| S | enum, campo de form, view, página nova, 4 propriedades de settings + migration, config, 3 providers | CT-13, CT-15, CT-18 |
| F | exibir o widget; exigir o token; verificar no provedor; recusar; redefinir o widget; gravar as chaves | CT-03..CT-12, CT-17 |
| D | token ausente / vazio / inválido / válido; chave vazia / só espaços; provedor fora da lista; segredo | CT-02, CT-06..CT-08, CT-13 |
| I | três telas × três painéis; tela de Settings; `.env`; API HTTP do provedor | CT-03, CT-04, CT-09, CT-10, CT-15 |
| P | rede de saída para o provedor (pode não haver); `APP_KEY` para a cifra | CT-11, CT-12, CT-13 |
| O | administrador liga sem chave; provedor cai em produção; robô envia sem token | CT-02, CT-06, CT-11 |
| T | token de uso único (segundo envio); timeout de 5 s | CT-17; timeout é **lacuna declarada** (ver M-T) |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — de fábrica a proteção está desligada e as telas não carregam script externo | liga/desliga (padrão) | RQ-07 | EP + observação de HTML | CT-01, CT-03 |
| R2 — ligada só com interruptor + chave do site + chave secreta + provedor válido | liga/desliga (padrão) | RQ-07, RQ-08 | tabela de decisão | CT-02 |
| R3 — ligada, as três telas carregam o script do provedor escolhido e o campo; a chave secreta não aparece | widget (mínimo) | RQ-01..03 | EP por provedor × tela | CT-04 |
| R4 — desligada, os três formulários funcionam sem token | verificação (completo) | RQ-07 | rastreio de efeito | CT-05 |
| R5 — ligada, token ausente ou recusado pelo provedor reprova o envio e nada acontece | verificação (completo) | RQ-01..03 | EP (ausente / inválido) × tela + rastreio de efeito | CT-06, CT-07, CT-09, CT-10 |
| R6 — ligada, token aceito deixa o envio seguir; a verificação vai ao endpoint do provedor com secret + response | verificação (completo) | RQ-01..03 | EP por provedor + rastreio de efeito | CT-08, CT-09, CT-10 |
| R7 — provedor indisponível ou com erro recusa e registra | verificação (completo) | RQ-01..03 (implícito: proteção é proteção) | EP (exceção / 5xx) | CT-11, CT-12 |
| R8 — a chave secreta é segredo: cifrada, fora do HTML, sobrevive ao save em branco, grava quando preenchida; as quatro propriedades alcançam a config | segredo (padrão) | RQ-08 | trio de `pages.md` + mapa | CT-13, CT-14, CT-15, CT-16 |
| R9 — a página nova veste o layout de autenticação sem vazá-lo | estrutura | RQ-02 (+ `.ai/rules/auth.md`) | par da rule | CT-18 |
| R10 — o widget é redefinido depois de cada verificação | verificação (completo) | RQ-01 (token de uso único) | rastreio de efeito | CT-17 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `CampoAntiRobo`, `TelaRecuperarSenha`, `antiRobo()` | escolha de implementação | detalhe do cenário |
| texto da mensagem de erro "Não foi possível confirmar…" | visível ao usuário e o requisito não determina | o `Então` afirma **que há erro no campo**, não o texto |
| nome do evento `kit-anti-robo-redefinir` | escolha de implementação | detalhe de CT-17 |
| URLs dos provedores | não são do requisito, são do provedor (documentação oficial) | o `Então` de CT-04/CT-08 usa a URL **do provedor**, que é fato externo, não do PRD |
| `timeout(5)` | número do PRD, não do requisito | lacuna declarada, não cenário |

**Perguntas em aberto** (as do `00-requisito.md`): v2 × v3; telas com token; um interruptor para os
três painéis. Os cenários seguem as premissas do `00`; nenhum está marcado `@premissa` porque as
premissas não mudam o **formato** de nenhum cenário, só a lista de telas.

## Setup Global

### Personas

- visitante anônimo (a maioria dos cenários)
- `usuarioDoKit('admin')` para a tela de Settings; `usuarioDoKit('panel_user')` para autenticar no `/app`

### Fixtures

- `User::factory()` via `usuario()` (senha `password`); convite via `Convite::factory()` + `enviar()` para o token em claro (mesmo arranjo de `ConviteTest::conviteCom()`)

### Fakes

- `Http::preventStrayRequests()` em todo o arquivo + `Http::fake([url-do-provedor => ...])` por cenário
- `Notification::fake()` para a redefinição de senha (o `Então` é o e-mail enviado ou não)
- `espiarAutenticacao()` para o canal de log

### Estratégia de DB

- `RefreshDatabase` global do `tests/Pest.php` (suíte Kit, single-tenant)

### Helper local

- `ligarAntiRobo(ProvedorAntiRobo $provedor = Recaptcha, array $sobrescrever = [])` — `config()->set('kit.login.anti_robo', [...])`. Só este arquivo usa.

---

## Regra R1 — de fábrica, desligada e sem script

> `RQ-07` · perfil **padrão** · técnica: **EP** (o valor de fábrica) + observação do HTML

```gherkin
# language: pt
Funcionalidade: Proteção anti-robô nasce desligada

  Regra: Sem nada configurado, nada muda nas telas

    Cenário: [CT-01] A configuração de fábrica está desligada
      Dado a configuração efetivamente lida pelo processo de teste, sem ajuste do cenário
      Então o ponto único de leitura responde que não há provedor anti-robô ativo
      E a chave "kit.login.anti_robo.habilitado" é falsa
      E a chave "kit.login.anti_robo.provedor" é "recaptcha"

    Esquema do Cenário: [CT-03] Desligada, a tela não carrega script de provedor nenhum
      Dado a proteção desligada
      Quando um visitante abre a <tela>
      Então a resposta é 200
      E o HTML não contém a URL de script de nenhum dos três provedores
      E não contém o nome do campo anti-robô

      Exemplos:
        | tela                                |
        | /app/login                          |
        | /admin/login                        |
        | /infra/login                        |
        | /app/password-reset/request         |
        | /admin/password-reset/request       |
        | /infra/password-reset/request       |
        | /app/register?token={convite válido} |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | default `true` no `config/kit.php` ou `phpunit.xml` fixando a chave | CT-01 (mede o default de verdade: `KIT_ANTI_ROBO` não está no `phpunit.xml`, conferido) |
| M2 | campo sempre visível, só a regra condicionada | CT-03 (o nome do campo aparece) |
| M3 | script carregado no `<head>` por render hook independentemente do estado | CT-03 (URL presente) |

---

## Regra R2 — duas condições (e um provedor válido)

> `RQ-07`, `RQ-08` · perfil **padrão** · técnica: **tabela de decisão** (interruptor × chave do site × chave secreta × provedor)

```gherkin
  Regra: Liga só com interruptor ligado E chave do site E chave secreta E provedor conhecido

    Esquema do Cenário: [CT-02] A tabela de decisão da ativação
      Dado interruptor <ligado>, chave do site <site>, chave secreta <secreta> e provedor <provedor>
      Então o ponto único responde <resultado>

      Exemplos:
        | ligado | site        | secreta     | provedor  | resultado           |
        | false  | preenchida  | preenchida  | recaptcha | nenhum              |
        | true   | vazia       | preenchida  | recaptcha | nenhum              |
        | true   | preenchida  | vazia       | recaptcha | nenhum              |
        | true   | preenchida  | só espaços  | recaptcha | nenhum              |
        | true   | preenchida  | preenchida  | banana    | nenhum (e warning)  |
        | true   | preenchida  | preenchida  | recaptcha | Recaptcha           |
        | true   | preenchida  | preenchida  | turnstile | Turnstile           |
        | true   | preenchida  | preenchida  | hcaptcha  | Hcaptcha            |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | só o interruptor decide | linhas 2 e 3 |
| M5 | `isset()` em vez de `filled()` | linha 4 (só espaços) |
| M6 | provedor inválido cai no `recaptcha` | linha 5 |
| M7 | conferir só a chave do site (a secreta "é do servidor, não precisa") | linha 3 |

---

## Regra R3 — ligada, o script certo em cada tela; a secreta nunca

> `RQ-01..03` · perfil **mínimo** · técnica: **EP** provedor × tela

```gherkin
  Regra: O widget do provedor escolhido aparece nas três telas dos três painéis

    Esquema do Cenário: [CT-04] Script e campo presentes, chave secreta ausente
      Dado a proteção ligada com o provedor <provedor>, chave do site "SITE-42" e chave secreta "SEGREDO-DE-TESTE-42"
      Quando um visitante abre a <tela>
      Então a resposta é 200
      E o HTML contém a URL de script do <provedor>
      E contém "SITE-42"
      E NÃO contém "SEGREDO-DE-TESTE-42"

      Exemplos: as sete telas de CT-03 com recaptcha; mais /app/login com turnstile e com hcaptcha
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | `/admin` e `/infra` continuam com a `Login` do vendor (esqueceu o `usingPage`) | linhas `/admin/login`, `/infra/login` |
| M9 | um dos três `passwordReset` sem `usingPage(TelaRecuperarSenha)` | as três linhas de `password-reset` |
| M10 | a view recebe o objeto de settings inteiro e serializa a secreta | asserção "NÃO contém" |
| M11 | `urlDoScript()` com `match` não exaustivo ou trocado | linhas turnstile / hcaptcha |

---

## Regra R4 — desligada, tudo funciona como hoje

> `RQ-07` · perfil **completo** · técnica: **rastreio de efeito** nas três telas

```gherkin
  Regra: Desligada, o formulário não pede token

    Cenário: [CT-05a] Login sem token autentica
      Dado a proteção desligada e um usuário com senha "password"
      Quando ele envia e-mail e senha pela tela de login, sem token
      Então não há erro de formulário e ele está autenticado

    Cenário: [CT-05b] Recuperação de senha sem token envia o e-mail
      Dado a proteção desligada e um usuário
      Quando ele pede a redefinição pela tela, sem token
      Então não há erro de formulário e a notificação de redefinição é enviada a ele

    Cenário: [CT-05c] Aceite de convite sem token cria a conta
      Dado a proteção desligada e um convite válido
      Quando o convidado envia o formulário, sem token
      Então não há erro de formulário e a conta existe
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | `required()` incondicional (campo oculto mas `dehydratedWhenHidden()`) | os três |
| M13 | a regra roda mesmo com o campo oculto e chama o provedor | os três (`Http::preventStrayRequests()` estoura) |

---

## Regra R5 — ligada, sem token ou com token recusado, nada acontece

> `RQ-01..03` · perfil **completo** · técnica: **EP** (ausente / recusado) × tela + **rastreio de efeito** (não aconteceu; log)

```gherkin
  Regra: Token ausente ou recusado reprova o envio

    Cenário: [CT-06] Login sem token reprova e não chama o provedor
      Dado a proteção ligada
      Quando o usuário envia e-mail e senha corretos, sem token
      Então há erro de validação no campo anti-robô
      E ele continua anônimo
      E nenhuma chamada HTTP foi feita

    Esquema do Cenário: [CT-07] Login com token recusado pelo provedor reprova
      Dado a proteção ligada com <provedor>, cujo endpoint responde {"success": false, "error-codes": ["invalid-input-response"]}
      Quando o usuário envia e-mail e senha corretos com o token "token-ruim"
      Então há erro de validação no campo anti-robô
      E ele continua anônimo
      E o canal "autenticacao" recebeu um warning com motivo "token_invalido" e sem o token no contexto

      Exemplos: recaptcha, turnstile, hcaptcha

    Cenário: [CT-09a] Recuperação de senha com token recusado não envia e-mail
      Dado a proteção ligada e o endpoint respondendo success false
      Quando o usuário pede a redefinição com um token
      Então há erro no campo anti-robô e nenhuma notificação foi enviada

    Cenário: [CT-10a] Aceite de convite com token recusado não cria conta
      Dado a proteção ligada, o endpoint respondendo success false e um convite válido
      Quando o convidado envia o formulário com um token
      Então há erro no campo anti-robô, a conta não existe e o convite continua pendente
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | sem `required()` — token ausente pula a regra do Laravel e o closure recebe `null` (Laravel não roda closure rule em campo ausente sem `required`) | CT-06 |
| M15 | `$fail()` esquecido no ramo `success: false` | CT-07 |
| M16 | ler `$resposta->successful()` e ignorar `json('success')` (o provedor responde **200** com `success: false`) | CT-07 |
| M17 | a regra só no login, não no registro / reset | CT-09a, CT-10a |
| M18 | o token no contexto do log | CT-07 (asserção sobre o contexto) |

---

## Regra R6 — ligada, token aceito segue; o request ao provedor está certo

> `RQ-01..03` · perfil **completo** · técnica: **EP por provedor** + **rastreio de efeito** (aconteceu, uma vez, no endpoint certo)

```gherkin
  Regra: Token aceito deixa passar, e a verificação vai ao provedor certo com os campos certos

    Esquema do Cenário: [CT-08] Login com token aceito autentica
      Dado a proteção ligada com <provedor>, chave secreta "SEGREDO-42", e o endpoint dele respondendo {"success": true}
      Quando o usuário envia e-mail e senha corretos com o token "token-bom"
      Então ele está autenticado
      E foi feita exatamente UMA chamada, ao endpoint de verificação do <provedor>, como formulário, com secret "SEGREDO-42", response "token-bom" e remoteip preenchido

      Exemplos: recaptcha, turnstile, hcaptcha

    Cenário: [CT-09b] Recuperação de senha com token aceito envia o e-mail
    Cenário: [CT-10b] Aceite de convite com token aceito cria a conta
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | `urlDeVerificacao()` trocada entre provedores | CT-08 (o fake é por URL; URL errada estoura o `preventStrayRequests`) |
| M20 | `secret` vindo da chave do site (ou vice-versa) | CT-08 (`assertSent` confere o corpo) |
| M21 | enviar JSON em vez de form-urlencoded (o Google exige form) | CT-08 (`assertSent` confere o `Content-Type`) |
| M22 | verificar duas vezes (uma no `afterStateUpdated`, outra na regra) — o token é de uso único e a segunda falharia em produção | CT-08 (`assertSentCount(1)`) |

---

## Regra R7 — provedor indisponível recusa e registra

> `RQ-01..03` · perfil **completo** · técnica: **EP** da falha (exceção de conexão / 5xx)

```gherkin
  Regra: Sem resposta do provedor, o envio é recusado (falha fechada)

    Cenário: [CT-11] Conexão recusada
      Dado a proteção ligada e o endpoint lançando ConnectionException
      Quando o usuário envia e-mail e senha corretos com um token
      Então há erro no campo anti-robô, ele continua anônimo
      E o canal "autenticacao" recebeu um warning com motivo "verificacao_indisponivel" e a exceção no contexto

    Cenário: [CT-12] Resposta 503
      Dado a proteção ligada e o endpoint respondendo 503
      Quando o usuário envia e-mail e senha corretos com um token
      Então há erro no campo anti-robô e ele continua anônimo
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | exceção não capturada → 500 em vez de erro de validação | CT-11 (o `call()` estouraria) |
| M24 | `catch` devolvendo `true` ("provedor caiu, deixa passar") | CT-11, CT-12 |
| M-T | `timeout(5)` ausente (default 30 s) | ⚠️ **sem matador** — o `Http::fake()` não simula tempo; verificado por leitura. Lacuna declarada |

---

## Regra R8 — a chave secreta é segredo, e as quatro propriedades alcançam a config

> `RQ-08` · perfil **padrão** · técnica: **trio de `.ai/rules/pages.md`** + mapa

```gherkin
  Regra: A chave secreta é cifrada, não aparece no HTML, sobrevive ao save em branco e grava quando preenchida

    Cenário: [CT-13] Gravada pelo settings, o payload é criptograma, a leitura é legível e chega à config
      Quando se grava "SEGREDO-ANTI-ROBO-42" na propriedade da chave secreta e se realinha a config
      Então o payload gravado não contém o texto claro
      E a leitura pelo settings devolve o texto claro
      E "kit.login.anti_robo.chave_secreta" é o texto claro

    Cenário: [CT-14a] A tela de Settings não serializa a chave secreta no HTML
      Dado a chave secreta "SEGREDO-ANTI-ROBO-42" gravada e a proteção ligada
      Quando o administrador abre /admin/configuracoes-do-kit
      Então a resposta é 200 e o conteúdo cru não contém o segredo

    Cenário: [CT-14b] O save que não tocou na chave a mantém
      Dado a chave secreta gravada
      Quando o administrador salva a tela mudando só o nome da aplicação
      Então a chave secreta continua a mesma

    Cenário: [CT-14c] Preencher a chave substitui a guardada
      Dado a chave secreta "ANTIGA" gravada
      Quando o administrador preenche "NOVA" e salva
      Então a leitura devolve "NOVA"

    Esquema do Cenário: [CT-15] Cada propriedade alcança a chave de config dela
      Quando se grava <valor> em <propriedade> e se realinha a config
      Então config(<chave>) é <valor>

      Exemplos:
        | propriedade                     | valor       | chave                                |
        | login_anti_robo_habilitado      | true        | kit.login.anti_robo.habilitado       |
        | login_anti_robo_provedor        | turnstile   | kit.login.anti_robo.provedor         |
        | login_anti_robo_chave_do_site   | site-x      | kit.login.anti_robo.chave_do_site    |
        | login_anti_robo_chave_secreta   | segredo-x   | kit.login.anti_robo.chave_secreta    |

    Cenário: [CT-16] A tela grava as quatro pela seção e a proteção entra no ar no request seguinte
      Dado o administrador na tela
      Quando ele liga o toggle, escolhe "turnstile", preenche as duas chaves e salva
      Então não há erro de formulário
      E, realinhada a config, o ponto único responde Turnstile
      E os três campos ficam ocultos com o toggle desligado e visíveis com ele ligado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | `addEncrypted` na migration sem o nome em `encrypted()` (o defeito do Google até a v0.19.3) | CT-13 (leitura devolveria ciphertext) |
| M26 | nome fora de `encrypted()` e `add()` simples | CT-13 (payload em claro) |
| M27 | `mutateFormDataBeforeFill()` sem a chave nova | CT-14a |
| M28 | `->dehydrated(false)` fixo | CT-14c |
| M29 | `->dehydrated()` sem condição | CT-14b |
| M30 | linha do mapa esquecida (campo grava e não governa) | CT-15 |
| M31 | `Select` sem as opções do enum (grava mas `Rule::in` recusa) | CT-16 |

---

## Regra R9 — a página nova veste o layout sem vazá-lo

> `RQ-02` + `.ai/rules/auth.md` · técnica: o **par** da rule

```gherkin
  Regra: TelaRecuperarSenha redeclara o layout

    Cenário: [CT-18] O layout de autenticação está na recuperação e não vaza para o painel
      Quando um visitante abre /admin/password-reset/request
      Então a resposta tem "fi-auth-layout"
      Quando, em seguida, o administrador autenticado abre /admin
      Então a resposta NÃO tem "fi-auth-layout"
```

(O primeiro `Então` já é coberto por `TelasDeAutenticacaoTest` — a linha fica aqui pelo par.)

---

## Regra R10 — o widget é redefinido depois de cada verificação

> `RQ-01` (token de uso único) · técnica: **rastreio de efeito**

```gherkin
  Regra: Depois de verificar, o componente manda o widget se redefinir

    Cenário: [CT-17] Verificação feita dispara o evento de redefinição
      Dado a proteção ligada e o endpoint respondendo success false
      Quando o usuário envia o login com um token
      Então o componente Livewire despachou "kit-anti-robo-redefinir"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | `dispatch()` só no ramo de sucesso | CT-17 (o cenário usa o ramo de falha) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: telas públicas sem registro alvo |
| Autorização exercida na ação | não se aplica: a tela de Settings já tem `PermissoesDeTelasTest` |
| Idempotência | CT-08 (`assertSentCount(1)`) |
| Concorrência | não se aplica: sem estado compartilhado além do token de uso único, que é do provedor |
| Fronteira no ponto de entrada | CT-02 (chave vazia / espaços / provedor inválido) |
| Domínio condicionado | CT-02 (chaves só importam com o interruptor ligado) |
| Estado × operação de escrita | CT-14b, CT-14c |
| Ausente ≠ null ≠ vazio | CT-06 (ausente), CT-02 (vazio, espaços) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | `maxLength(255)` nos dois campos — não se aplica além disso |
| Unicidade + soft delete | não se aplica |
| Mass assignment | CT-10b (o token não chega a `User::create()` — a conta é criada sem erro) |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| Segredo em log | CT-07 (contexto sem token), CT-14a (HTML sem segredo) |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | default desligado | R1 | EP | Unit | `tests/Kit/ProtecaoAntiRoboTest.php` | M1 |
| CT-02 | tabela de decisão da ativação | R2 | tabela de decisão | Unit | idem | M4–M7 |
| CT-03 | sem script nas 7 telas | R1 | HTML | Feature | idem | M2, M3 |
| CT-04 | script certo, secreta ausente | R3 | EP | Feature | idem | M8–M11 |
| CT-05 | desligada, 3 formulários funcionam | R4 | efeito | componente | idem | M12, M13 |
| CT-06 | login sem token | R5 | EP | componente | idem | M14 |
| CT-07 | login com token recusado (×3) | R5 | EP | componente | idem | M15, M16, M18 |
| CT-08 | login com token aceito (×3) | R6 | EP + efeito | componente | idem | M19–M22 |
| CT-09 | reset recusado / aceito | R5, R6 | efeito | componente | idem | M17 |
| CT-10 | convite recusado / aceito | R5, R6 | efeito | componente | idem | M17 |
| CT-11 | conexão recusada | R7 | EP | componente | idem | M23, M24 |
| CT-12 | 503 | R7 | EP | componente | idem | M24 |
| CT-13 | cifra + leitura + config | R8 | trio | Feature | idem | M25, M26 |
| CT-14 | HTML / save em branco / preenchido | R8 | trio | componente | idem | M27–M29 |
| CT-15 | mapa (×4) | R8 | mapa | Feature | idem | M30 |
| CT-16 | tela grava e liga; visibilidade | R8 | componente | componente | idem | M31 |
| CT-17 | evento de redefinição | R10 | efeito | componente | idem | M32 |
| CT-18 | par do layout | R9 | rule | Feature | idem | — |

## Sem CT-B

- **Motivo**: o que só o navegador prova — o widget do provedor renderizar e o token chegar ao
  estado via `$wire.set` — depende de rede externa e de chave do provedor. O reCAPTCHA tem chaves
  de teste públicas (`6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI` / `...AAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`),
  o Turnstile também (`1x00000000000000000000AA`), mas as duas exigem carregar script de terceiro
  no CI, o que o kit não faz em nenhuma suíte. A verificação de navegador fica para a rodada de
  validação real, como a do login social (`07-validacao-real-dos-provedores.md` da wiki
  ancestral) — registrado no `03-progresso.md` como pendência de quem valida no navegador.
