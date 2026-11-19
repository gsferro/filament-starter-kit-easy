# Casos de Teste — email-com-mais: permitir ou bloquear o "+" no e-mail do cadastro

> Requisito: `00-requisito.md` (versão com P-01, P-04 e P-06/Q4 revistos em 2026-11-18) · Plano: `01-plano-acao.md` (só `## Estrutura de Implementação`, para paths)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> **Revisão adversarial: NÃO exigida pelo perfil.** Nenhuma área é `completo` e nenhuma tem Impacto 3. O motivo do Impacto 2 está abaixo da tabela. A sessão pode despachar a revisão mesmo assim, por decisão própria.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — a opção: padrão, leitura, tela de configurações | 2 | 2 | 4 | padrão |
| A2 — vias de formulário e de model: registro aberto, convite (um a um, em massa, aceite), CRUD de usuários, perfil | 3 | 2 | 6 | padrão |
| A3 — login social que cria conta ou entra | 3 | 2 | 6 | padrão |

- Por que o Impacto é 2 e não 3: o `00` trata o bloqueio como "proteção extra" e diz que o "+" "não é um problema". Se o bloqueio falhar, nasce uma conta a mais, e o admin a resolve à mão. Se a recusa for indevida, o próprio admin a desfaz religando a opção. A falha não envolve dinheiro, dado de terceiro, autorização de painel nem perda irreversível.
- Por que P é 3 em A2 e A3: são oito vias, três painéis, os dois estados da opção, as exceções da P-04 (conta existente) e da P-06 (convite já emitido), e uma integração externa (o provedor social).
- Técnicas aplicadas: EP (partição do valor da opção e da parte local), BVA de posição do "+" na parte local, tabela de decisão (estado × e-mail × conta existente × via), rastreio de efeito (conta, convite, notificação, sessão), criação ≠ edição ≠ uso.
- Cenários: 31 · Regras: 11 · Mutantes previstos: 58 · Sem matador: 2 *(alterado em 2026-11-19: CT-28 e M53/M54 do step 9; as linhas `off`/`no` do CT-01 pela recomendação da Q6 — QA-06/QA-07 do gate; a R7 reescrita pela resposta à Q4, com CT-29..CT-31 e sete mutantes no lugar de três)*
<!-- derivado por grep -c (template-04.md §Contagem do cabeçalho) -->

**Divergência declarada com uma Project Rule.** `.ai/rules/config.md` manda que um interruptor de env falhe **fechado**. Aqui vence a P-01 do `00`: a ausência de valor (chave ausente, vazia ou irreconhecível) **permite**, e só um "desligado" explícito bloqueia. A rule fala de interruptor que **abre** superfície pública. Esta opção liga uma proteção opcional, e o requisito escolheu a direção. Os CT-01 a CT-03 seguem a P-01.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | a opção: chave de config, env, propriedade do settings e sua migration; o leitor único; a regra de validação; os campos de e-mail das vias; o componente de informações pessoais do perfil nos três painéis | CT-01…CT-06, CT-26 |
| F | aceitar ou recusar o e-mail novo conforme a opção; dizer o motivo (erro de campo, motivo no lote, aviso no login social); deixar passar a conta existente (P-04) e o convite já emitido (P-06) | CT-07…CT-25, CT-27 |
| D | e-mail com e sem "+"; "+" no início, no fim e repetido na parte local; "+" depois do "@"; valor da opção (ausente, vazio, irreconhecível, `true`, `false`, `0`); conta existente com "+"; convite com "+" já emitido; lote misto | CT-01, CT-03, CT-07, CT-13, CT-15 |
| I | `/app/register`, `/cadastro`, `/admin` e `/app` (convites, usuários, meu-perfil), `/infra/meu-perfil`, a ação de convite em massa, `RegistroAberto::registrar()` e `Convite::convidarEmMassa()` chamados direto, o retorno `/auth/{provedor}/callback`, a tela de configurações e o `.env` | CT-04, CT-08…CT-27 |
| P | banco de teste SQLite. O "+" é ASCII e não depende de colação. Medido: a regra `email` do Laravel e o `filter_var` recusam `fulano@exem+plo.com` e `fulano＋a@exemplo.com` (U+FF0B), e aceitam `+fulano@`, `fulano+@` e `fulano+a+b@` | CT-07, CT-15 |
| O | o admin da instalação liga e desliga a opção; o admin da organização (`admin_app`) cria convite e usuário no `/app`; o visitante se cadastra; a pessoa convidada aceita; quem já tem conta entra e edita o perfil; uso indevido: multiplicar contas com `fulano+N@` | CT-06, CT-12, CT-21, CT-22, CT-25, CT-27 |
| T | a ordem importa: o convite emitido com a opção permitindo e aceito depois que ela foi desligada (P-06); o settings ainda não migrado (P-01). Não há concorrência relevante: a opção é lida na hora da validação | CT-03, CT-16, CT-17 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Toda ausência de valor permite; só "desligado" explícito bloqueia | A1 (padrão) | RQ-01, P-01 | EP | CT-01, CT-02, CT-03 |
| R2 — A opção é da instalação, governada pela tela e lida por um só leitor | A1 (padrão) | RQ-01, P-02 | EP + rastreio de efeito | CT-04, CT-05, CT-06 |
| R3 — Só conta o "+" da parte local | A2 (padrão) | RQ-01, RQ-03, RQ-04, P-04 | EP + BVA de posição | CT-07 |
| R4 — Registro aberto (`/app/register`, `/cadastro`) segue a opção | A2 (padrão) | RQ-02, RQ-03, RQ-04, P-03, P-05 | tabela de decisão + rastreio de efeito | CT-08, CT-09, CT-10 |
| R5 — Convite um a um segue a opção, exceto para conta existente | A2 (padrão) | RQ-02, RQ-03, RQ-04, P-03, P-04, P-06 | tabela de decisão | CT-11, CT-12 |
| R6 — No convite em massa, o "+" sai com motivo próprio e os outros seguem | A2 (padrão) | RQ-03, P-04, P-05 | EP + rastreio de efeito | CT-13, CT-14, CT-15, CT-28 |
| R7 — Com a opção desligada, o convite para conta nova com "+" não se aceita | A2 (padrão) | RQ-03, P-06, Q7 | estado × evento | CT-16, CT-17, CT-29, CT-30, CT-31 |
| R8 — CRUD de usuários: criar segue a opção; editar só olha o e-mail novo | A2 (padrão) | RQ-02, RQ-03, P-03, P-04 | criação ≠ edição + tabela de decisão | CT-18, CT-19, CT-20 |
| R9 — O login social não cria conta com "+" bloqueado e diz o motivo | A3 (padrão) | RQ-03, P-03, P-05 | rastreio de efeito + EP | CT-21, CT-22, CT-23 |
| R10 — A troca de e-mail no perfil segue a opção, nos três painéis | A2 (padrão) | RQ-03, P-03, P-04 (Q2) | criação ≠ edição + EP por painel | CT-24, CT-25, CT-26 |
| R11 — A conta existente com "+" continua entrando | A2/A3 (padrão) | P-04 | EP | CT-27 |

- Técnica escalada: R3 usa BVA de **posição** (primeiro caractere, último antes do "@", repetido) e não só EP. "Contém '+'" e "contém '+' antes do '@'" só divergem em posições escolhidas a dedo.
- Nenhuma `RQ` está aberta. Q1 a Q4 estão `aberta — implementada a recomendação`: o recorte é derivado da recomendação vigente (P-01, P-02, P-03, P-06), e a P vigente é a origem.
- RQ-03 tem **cobertura parcial**: CT-08 e CT-10 afirmam que a mensagem cita o "+" e não é a recusa genérica. A redação exata está aberta (Q?1).
- P-06 tem **cobertura parcial**: CT-16 e CT-17 cobrem o aceite. Ficam abertos o reenvio e o lembrete do convite com "+" emitido antes, com a opção desligada (Q?3).

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — leitura da opção | R1, R2 (CT-05) | Pest feature HTTP | existente — `tests/Kit/RegistroAbertoTest.php` (CT-01 de varredura com filtro de comentário; "deixa o valor gravado no settings vencer o env"), `kitConfigCom()`, `configuracaoGravada()`, `alinharConfiguracoesDoKit()` de `tests/Pest.php` | o `Então` afirma um valor efetivo lido e uma via que aceita ou recusa, sem tela | |
| G2 — tela de configurações | R2 (CT-04) | componente Livewire/Filament | existente — `tests/Kit/ConfiguracoesDoKitTelaTest.php` (gravação por componente), `carregarAba()`, `chaveDoCampo()` | gate de tela de escrita: a tela grava, e o efeito aparece numa via | |
| G3 — regra da parte local | R3 | Pest feature HTTP | nova — a regra é avaliada pelo `Validator` do Laravel, com o container de pé, em `tests/Kit` (o `tests/Unit` não liga o `TestCase`, `tests/Pest.php`) | o `00` diz "adiciona essa Rule no validation": o observável é a regra passar ou falhar para cada valor | |
| G4 — formulários das vias no `/admin` e sem tenancy | R4, R5 (CT-11), R7 (CT-16), R8 (CT-18, CT-19), R10, R11 | componente Livewire/Filament | existente — `registrarAberto()` de `tests/Kit/RegistroAbertoTest.php`; `[CT-55]` de `tests/Kit/LoginUnificadoTest.php` (`/cadastro`); `conviteCom()` e `aceitarConvite()` de `tests/Kit/ConviteTest.php`; `CreateUser` de `tests/Kit/PaineisTest.php`; `Livewire::test(DefinirSenhaPorEmail::class)` de `tests/Kit/DefinirSenhaPorEmailTest.php` (componente do perfil) | validação de formulário e gravação são `fillForm` → `call` → `assertHasFormErrors(['email'])` / `assertDatabaseHas` | |
| G5 — vias por fora da UI | R4 (CT-09), R6 (CT-13…CT-15) | Pest feature HTTP | existente — "recusa o registro chamado fora da tela" de `tests/Kit/RegistroAbertoTest.php`; `Convite::convidarEmMassa()` em `tests/Kit/ConviteEmMassaTest.php` | gate de camada da regra: a regra precisa valer fora do formulário | |
| G6 — `/app` com organizações | R2 (CT-06), R5 (CT-12), R8 (CT-20) | componente Livewire/Filament | existente — `cenario()` de `tests/Tenancy/AdminDaOrganizacaoTest.php` (CreateConvite e CreateUser/EditUser do `/app`); `registrarNaOrganizacao()` e `organizacaoComRegistro()` de `tests/Tenancy/RegistroAbertoTenancyTest.php` | `admin_app` e o UserResource do `/app` só existem em `tests/Tenancy` (`.ai/rules/testes.md`); GET real antes do primeiro `Livewire::test()` | |
| G7 — login social | R7 (CT-17), R9, R11 (linha social) | Pest feature HTTP | existente — `tests/Kit/CadastroSocialPorConviteTest.php` (`conviteSocialPara()`), `tests/Kit/LoginSocialGoogleTest.php`; `ligarProvedor()`, `usuarioSocialFalso()`, `avisosDoFilament()` de `tests/Pest.php` | o retorno do provedor é uma rota HTTP; o aviso é uma notificação do Filament lida por `avisosDoFilament()` | |
| G8 — perfil servido pelos três painéis | R10 (CT-26) | Pest feature HTTP | existente — GET de `/{painel}/meu-perfil` (`tests/Kit/PermissoesDeTelasTest.php`) | `Livewire::test` do componente novo não prova que os painéis o servem; o GET mede o que cada `/meu-perfil` entrega | |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da chave `kit.email.permitir_mais`, env `KIT_EMAIL_PERMITIR_MAIS`, propriedade `email_permitir_mais` | escolha de implementação | detalhe do cenário (Setup). Antes de usar `config()->set()`, confirmar com `grep` que a chave existe em `config/kit.php` (`.ai/rules/config.md`) |
| classe `App\Support\EmailComMais` e os métodos `permitido()`, `temMais()`, `aceita()`, `mensagem()`, `regra()` | escolha de implementação | detalhe: o CT-05 usa o nome do leitor para a varredura; o CT-07 usa a regra exposta |
| motivo `email_com_mais` no lote e o texto de `motivoLegivel()` | o `00` exige só "motivo próprio" | o `Então` do CT-13 afirma que o motivo é diferente de `formato_invalido`, `convite_pendente`, `recusou_antes` e `erro_no_envio`; o nome é detalhe |
| texto da mensagem de recusa | visível ao usuário, e o `00` só diz "diz o motivo" | o `Então` afirma que o texto cita o "+" e que não é a recusa genérica "O acesso a este sistema é por convite." A redação exata é pergunta (Q?1) |
| logs `warning` no canal `autenticacao` com motivo `email_com_mais` | log não é cláusula (Proibição 12) | sem CT; fica para a dimensão D do quality gate |
| seção colapsável "E-mail com +" na aba Registro | o `00` só fixa a aba (P-02); título e texto são da tela | o CT-04 entra pela aba `registro` e pelo campo; o título não é oráculo |
| `InformacoesPessoais` estendendo o `PersonalInfo` do Breezy | escolha de implementação | detalhe do CT-26, que mede o que cada painel serve |
| "o modo convite não ganha a regra; o `aceitar()` não muda" | mecanismo; o comportamento vem da P-06 | o CT-16 e o CT-17 afirmam o comportamento (o aceite passa), não o mecanismo |

### Perguntas geradas pela derivação

❓ Q?1 · raia: requisito · afeta: P-05, RQ-03 · depende de: —
Qual é o texto exato da mensagem que explica o bloqueio, no campo de e-mail e no aviso do login social?
➡️ Recomendação: uma frase só, igual em todas as vias, citando o "+" e a parte antes do "@". Por exemplo: `Este sistema não aceita e-mail com "+" antes do "@".` — enquanto isso, os CT afirmam apenas que a mensagem cita o "+" e que não é a recusa genérica; a igualdade entre vias e a redação ficam abertas.

❓ Q?2 · raia: desenho · afeta: P-01 · depende de: —
Quais valores do `.env` são "desligado explícito"? Só `false` e `0`, ou também `off` e `no`?
➡️ Recomendação: os falsos que o `FILTER_VALIDATE_BOOLEAN` reconhece (`false`, `0`, `off`, `no`). Vazio e qualquer outro valor contam como ausência e permitem (P-01). O CT-01 já fixa `false`, `0`, vazio e `talvez`. As linhas `off` e `no` entram com a resposta.

❓ Q?3 · raia: requisito · afeta: P-06 · depende de: —
Com a opção desligada, o convite com "+" emitido antes pode ser **reenviado** (token novo) e continua recebendo **lembrete**? Ou o reenvio conta como "convite novo"?
➡️ Recomendação: segue valendo. É o mesmo convite já emitido (P-06), e o reenvio não cria conta nem endereço novo. Sem cenário até a resposta.

## Perguntas para o 00-requisito.md

Desvio declarado: a derivação roda em sub-agente e não edita o `00`. As perguntas de raia requisito vão aqui, prontas para colar, com numeração provisória:

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q?1 | Qual é o texto exato da mensagem que explica o bloqueio do "+" (erro do campo e aviso do login social)? | P-05, RQ-03 | uma frase única para todas as vias, citando o "+" antes do "@" | aberta |
| Q?3 | Com a opção desligada, o convite com "+" emitido antes pode ser reenviado e lembrado? | P-06 | sim — é o mesmo convite já emitido; não cria endereço novo | aberta |

## Setup Global

### Personas
- `master_global` e `admin` da instalação: `usuarioDoKit('master_global')` / `usuarioDoKit('admin')`, depois de `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.
- `admin_app` da Acme (só em `tests/Tenancy`): `cenario()` de `tests/Tenancy/AdminDaOrganizacaoTest.php` (Ana), com `noPainelDa($acme)` e um GET real a `/app/acme/users` antes do primeiro `Livewire::test()`.
- Conta existente com "+": `usuario('gsferroti+starter@gmail.com')`, com papel por `papelNaOrganizacao()` quando a via exige painel.
- Visitante: `Filament::auth()->logout()` antes de cada cadastro (molde `registrarAberto()`).

### Helpers dos irmãos e onde os CT moram
- `registrarAberto()`, `conviteCom()` e `aceitarConvite()` são locais de `RegistroAbertoTest` e `ConviteTest`, e `conviteSocialPara()` é local de `CadastroSocialPorConviteTest`. Para o `tests/Kit/EmailComMaisTest.php` usá-los, eles **sobem para `tests/Pest.php`** no mesmo commit, sem clone com outro nome (`.ai/rules/testes.md`; prova: `tests/Kit/HelpersDeTesteTest.php`). Se a subida for recusada, o CT vai para o arquivo irmão do helper.
- `cenario()` (de `AdminDaOrganizacaoTest`) e `registrarNaOrganizacao()` (de `RegistroAbertoTenancyTest`) **ficam** nos irmãos: CT-12 e CT-20 vão para `tests/Tenancy/AdminDaOrganizacaoTest.php`, e CT-06 para `tests/Tenancy/RegistroAbertoTenancyTest.php`. `cenario()` é genérico demais para ser global.

### Estado da opção
- Helper novo **em `tests/Pest.php`**, porque Kit e Tenancy o usam: `opcaoDoMais(bool $permitido)`. Ele define a chave da opção na config do processo e, quando o settings existe, grava a propriedade com `gravarConfiguracao()` e chama `alinharConfiguracoesDoKit()`. Assim o estado fica igual nas duas fontes e não depende da precedência entre elas.
- O `phpunit.xml` já força `KIT_EMAIL_PERMITIR_MAIS=true`. **Nenhum CT de padrão pode medir esse ambiente**: o CT-01 relê o `config/kit.php` com `kitConfigCom()`, apagando ou trocando a variável.
- Os e-mails dos exemplos são os **literais do `00`**: `gsferroti@gmail.com`, `gsferroti+starter@gmail.com`, `gsferroti+starter-kit@gmail.com`.

### Fixtures
- Convite já emitido: `conviteCom('panel_user', email: 'gsferroti+starter@gmail.com')` (Kit, em `tests/Kit/ConviteTest.php`) ou `ofertaPara()` + `enviar()` (`tests/Pest.php`), criado com a opção **permitindo**. A opção só é desligada depois, por `opcaoDoMais(false)`. A ordem é a do mundo real, e o convite nasce pela via de criação, não por `forceFill`.
- Organização com registro: `organizacaoComRegistro('acme')`.

### Fakes
- `Notification::fake()` em todo cenário de convite e de cadastro. A notificação `ConviteDeAcesso` é o efeito do caminho feliz do convite.
- Login social: `ligarProvedor(ProvedorSocial::Google)` + `Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], ['email' => …]))`; `Http::preventStrayRequests()`.
- Aviso exibido: `avisosDoFilament()` (título da notificação `danger`). Também serve `followingRedirects()` até o login, com a asserção sobre o corpo depois de `corpoDepoisDoTitulo()`.

### Estratégia de DB
- `RefreshDatabase` ligado pelo `tests/Pest.php` nas suítes `Kit` e `Tenancy`. Com ele, o `KitServiceProvider::boot()` roda antes das migrations, e por isso o alinhamento do settings é chamado à mão (`alinharConfiguracoesDoKit()`).

---

## Regra R1 — Toda ausência de valor permite; só "desligado" explícito bloqueia

> `RQ-01`, `P-01` · perfil **padrão** · técnica: **EP** (partições do valor bruto da opção em cada fonte: env, config do projeto, settings)

```gherkin
# language: pt
Funcionalidade: Opção de permitir ou bloquear o "+" no e-mail do cadastro

  Regra: Toda ausência de valor permite o "+"; só um "desligado" explícito bloqueia

    Esquema do Cenário: [CT-01] o valor da variável de ambiente decide, e só o falso explícito bloqueia
      Dado o arquivo de configuração do kit relido com a variável da opção "<env>"
      Quando a instalação consulta se o "+" é permitido
      Então a resposta é "<resposta>"

      Exemplos:
        | env        | resposta  | # partição                  |
        | (ausente)  | permitido | ausência                    |
        | (vazia)    | permitido | ausência: vazia             |
        | talvez     | permitido | ausência: irreconhecível    |
        | true       | permitido | ligado explícito            |
        | false      | bloqueado | desligado explícito         |
        | 0          | bloqueado | desligado explícito         |
        | off        | bloqueado | desligado explícito (Q6)    |
        | no         | bloqueado | desligado explícito (Q6)    |

    Cenário: [CT-02] o projeto que manteve o próprio config/kit.php sem a chave permite o "+"
      Dado um projeto cuja configuração do kit não tem a chave da opção (o grupo removido da config, não gravado como null)
      E o registro aberto ligado
      Quando o visitante se registra com "gsferroti+starter@gmail.com"
      Então a conta "gsferroti+starter@gmail.com" existe
      E o campo de e-mail não tem erro

    @premissa
    Esquema do Cenário: [CT-03] o settings ainda não migrado permite, e o gravado decide
      Dado o registro aberto ligado e a variável de ambiente da opção "<env>"
      E a propriedade da opção no settings "<settings>"
      E o alinhamento do settings com a configuração executado
      Quando o visitante se registra com "gsferroti+starter@gmail.com"
      Então a conta "gsferroti+starter@gmail.com" "<conta>"

      Exemplos:
        | env       | settings        | conta      | # partição                                 |
        | (ausente) | (linha ausente) | existe     | settings não migrado e env ausente: permite |
        | false     | (linha ausente) | não existe | settings não migrado: o env explícito decide |
        | false     | true            | existe     | gravado permitindo vence o env             |
        | (ausente) | false           | não existe | gravado desligado bloqueia                 |
```

`@premissa` de mecanismo (raia desenho): o `00` não fixa quem vence entre settings e env. O CT-03 segue a convenção do kit provada pelo irmão "deixa o valor gravado no settings vencer o env" (`tests/Kit/RegistroAbertoTest.php`); se o desenho trocar a precedência, só a linha `false` + `true` muda.

Leitura do CT-03. A linha ausente no settings **não força** nenhum dos dois estados: vale o env. A linha `true` contra o env `false` prova que o valor gravado vence o env, que é a condição para a tela governar (P-02).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `(bool) env('X', true)`: a string vazia vira `false` e bloqueia | CT-01 | linha `(vazia)`: "permitido"; o mutante responde "bloqueado" |
| M2 | `filter_var(env('X', false), FILTER_VALIDATE_BOOLEAN)` (molde "falha fechado" da rule): o irreconhecível bloqueia | CT-01 | linha `talvez`: "permitido"; o mutante responde "bloqueado" |
| M3 | default `false` no `config/kit.php` | CT-01 | linha `(ausente)`: "permitido"; o mutante responde "bloqueado" |
| M4 | o leitor usa `config('kit.email.permitir_mais', false)` ou trata `null` como falso | CT-02 | a conta `gsferroti+starter@gmail.com` existe; o mutante recusa com erro em `email` |
| M5 | a falta da linha no settings é lida como `false` e sobrescreve um env que permite | CT-03 | linha `(ausente)` + `(linha ausente)`: "existe"; o mutante recusa |
| M6 | o settings não entra na leitura (só o env vale) | CT-03 | linha `true`: "existe"; com o env `false`, o mutante recusa |

---

## Regra R2 — A opção é da instalação, governada pela tela e lida por um só leitor

> `RQ-01`, `P-02` · perfil **padrão** · técnica: **EP** + **rastreio de efeito** (gravar na tela → efeito numa via)

```gherkin
  Regra: A opção é uma só, da instalação, gravada pela tela de configurações e lida por um único leitor

    Cenário: [CT-04] o admin desliga o "+" na aba Registro e o registro aberto passa a recusar
      Dado o admin da instalação na tela de configurações, com a opção permitindo e o registro aberto ligado
      Quando ele desliga a opção na aba Registro e salva
      Então a propriedade da opção no settings é false
      E, depois do alinhamento, o registro com "gsferroti+starter@gmail.com" tem erro no campo de e-mail e a conta não existe

    Cenário: [CT-05] só o leitor único lê a chave da opção
      Dado o código de app/, sem comentários
      Quando se procura a leitura da chave da opção fora do leitor e do mapa de configuração do settings
      Então nenhuma ocorrência é encontrada
      E o leitor tem ao menos uma ocorrência (controle positivo)

    Esquema do Cenário: [CT-06] a organização segue a opção da instalação no cadastro aberto
      Dado a organização "acme" com o registro habilitado e a opção da instalação "<opcao>"
      Quando o visitante se registra na "acme" com "gsferroti+starter@gmail.com"
      Então a conta "<conta>"
      E o vínculo da conta com a "acme" "<conta>"

      Exemplos:
        | opcao     | conta      |
        | permitido | existe     |
        | bloqueado | não existe |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o campo existe na tela, mas a linha do mapa de configuração fica de fora: salva e nada acontece | CT-04 | depois do alinhamento, o registro recusa; o mutante aceita e cria a conta |
| M2 | o campo é posto fora da aba Registro ou não é gravado no grupo do settings | CT-04 | `configuracaoGravada(...)` é `false`; o mutante deixa `true` |
| M3 | uma via lê `config('kit.email…')` direto em vez do leitor | CT-05 | a varredura acha a ocorrência fora do leitor |
| M4 | a opção passa a ser lida da organização (como o "cadastro aberto"), e a organização sem valor permite | CT-06 | linha `bloqueado`: "não existe"; o mutante cria a conta na Acme |
| M5 | a regra só é aplicada no registro sem organização | CT-06 | linha `bloqueado`: o mutante cria a conta vinculada à Acme |

---

## Regra R3 — Só conta o "+" da parte local

> `RQ-01`, `RQ-03`, `RQ-04`, `P-04` · perfil **padrão** · técnica: **EP + BVA de posição** (escalada: sem a posição, "contém '+'" e "contém '+' antes do '@'" não divergem)

```gherkin
  Regra: A regra de validação recusa o "+" antes do "@" só com a opção desligada; o e-mail sem "+" passa sempre

    Esquema do Cenário: [CT-07] a regra decide pela parte local e pelo estado da opção
      Dado a opção "<opcao>"
      Quando o e-mail "<email>" é validado pela regra do "+"
      Então a validação "<resultado>"

      Exemplos:
        | opcao     | email                           | resultado | # partição / borda                |
        | bloqueado | gsferroti@gmail.com             | passa     | sem "+" (RQ-04)                   |
        | bloqueado | gsferroti+starter@gmail.com     | falha     | "+" no meio (literal do 00)       |
        | bloqueado | gsferroti+starter-kit@gmail.com | falha     | "+" no meio, com hífen depois     |
        | bloqueado | +gsferroti@gmail.com            | falha     | borda: primeiro caractere         |
        | bloqueado | gsferroti+@gmail.com            | falha     | borda: último antes do "@"        |
        | bloqueado | gsferroti+a+b@gmail.com         | falha     | "+" repetido                      |
        | bloqueado | gsferroti@gma+il.com            | passa     | "+" depois do "@" não conta       |
        | permitido | gsferroti+starter-kit@gmail.com | passa     | permitido com "+"                 |
        | permitido | gsferroti@gmail.com             | passa     | permitido sem "+"                 |
```

Nas vias, o "+" depois do "@" nunca chega à regra do "+". Medido: a regra `email` do Laravel e o `filter_var` recusam `fulano@exem+plo.com`. Por isso essa borda é provada aqui, na regra, e no lote (CT-15), onde o motivo da recusa separa as duas regras.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `str_contains($email, '+')`: olha o e-mail inteiro | CT-07 | linha `gsferroti@gma+il.com`: "passa"; o mutante falha |
| M2 | `strpos($email, '+') > 0`: o "+" na posição 0 escapa | CT-07 | linha `+gsferroti@gmail.com`: "falha"; o mutante passa |
| M3 | a parte local é `explode('@')[0]` e um regex exige caractere depois do "+" (`/\+.+@/`) | CT-07 | linha `gsferroti+@gmail.com`: "falha"; o mutante passa |
| M4 | a regra ignora o estado da opção (sempre recusa) | CT-07 | linha `permitido` com "+": "passa"; o mutante falha |
| M5 | condição invertida: recusa o e-mail sem "+" | CT-07 | linha `bloqueado` sem "+": "passa"; o mutante falha |

---

## Regra R4 — Registro aberto (`/app/register` e `/cadastro`) segue a opção

> `RQ-02`, `RQ-03`, `RQ-04`, `P-03`, `P-05` · perfil **padrão** · técnica: **tabela de decisão** (via × estado × e-mail) + **rastreio de efeito** (conta, autenticação)

```gherkin
  Regra: O cadastro pelo registro aberto aceita ou recusa o "+" conforme a opção, nas duas telas

    Esquema do Cenário: [CT-08] as duas telas de registro aberto seguem a opção
      Dado o registro aberto ligado, o login unificado "<unificado>" e a opção "<opcao>"
      E nenhuma conta com o e-mail "<email>"
      Quando o visitante se registra em "<tela>" com "<email>"
      Então a conta "<email>" "<conta>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | tela           | unificado | opcao     | email                       | conta      | erro                                    |
        | /app/register  | desligado | permitido | gsferroti+starter@gmail.com | existe     | não tem erro                            |
        | /app/register  | desligado | bloqueado | gsferroti+starter@gmail.com | não existe | tem erro com mensagem que cita o "+"    |
        | /app/register  | desligado | bloqueado | gsferroti@gmail.com         | existe     | não tem erro                            |
        | /cadastro      | ligado    | permitido | gsferroti+starter@gmail.com | existe     | não tem erro                            |
        | /cadastro      | ligado    | bloqueado | gsferroti+starter@gmail.com | não existe | tem erro com mensagem que cita o "+"    |
        | /cadastro      | ligado    | bloqueado | gsferroti@gmail.com         | existe     | não tem erro                            |

    Cenário: [CT-09] o registro chamado por fora da tela também recusa o "+" bloqueado
      Dado o registro aberto ligado e a opção bloqueada
      Quando um chamador registra direto, sem a tela, a conta "gsferroti+starter@gmail.com"
      Então o registro é recusado com exceção
      E nenhuma conta existe

    Cenário: [CT-10] a recusa no registro diz o motivo, e a pessoa pode corrigir na mesma tela
      Dado o registro aberto ligado e a opção bloqueada
      E o visitante recusado com "gsferroti+starter@gmail.com" em /app/register
      Quando ele troca o e-mail para "gsferroti@gmail.com" e envia de novo
      Então a conta "gsferroti@gmail.com" existe e está autenticada
      E nenhuma conta "gsferroti+starter@gmail.com" existe
```

O CT-10 é o par de saída do estado de erro: a recusa tem destino. A pessoa corrige na própria tela, e a conta nasce sem o "+".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a regra entra só em `RegistroPorConvite` e esquece `/cadastro` | CT-08 | linha `/cadastro` + bloqueado + "+": "não existe"; o mutante cria a conta |
| M2 | a regra só existe no formulário; `registrar()` direto aceita | CT-09 | a exceção é lançada e `User::count()` é 0; o mutante cria a conta |
| M3 | a recusa usa a mensagem do `email` genérico ou a de `unique`, sem motivo | CT-08 | o erro do campo `email` cita "+"; o mutante mostra "deve ser um e-mail válido" |
| M4 | a conta é criada antes da validação do "+" e a recusa vem depois | CT-08 | linha bloqueado + "+": `User::where('email', …)->exists()` é falso; o mutante deixa a conta |
| M5 | a recusa persiste no componente e impede o segundo envio corrigido | CT-10 | a conta `gsferroti@gmail.com` existe e está autenticada; o mutante mantém o erro |

---

## Regra R5 — Convite um a um segue a opção, exceto para conta existente

> `RQ-02`, `RQ-03`, `RQ-04`, `P-03`, `P-04`, `P-06` · perfil **padrão** · técnica: **tabela de decisão** (estado × "+" × conta existente)

```gherkin
  Regra: Com a opção desligada, nenhum convite novo com "+" nasce para conta inexistente; para conta existente, passa

    Esquema do Cenário: [CT-11] a criação de convite no /admin segue a opção e a P-04
      Dado o administrador geral no /admin, a opção "<opcao>"
      E "<conta>" com o e-mail "<email>"
      Quando ele cria um convite para "<email>" com o papel panel_user
      Então o convite para "<email>" "<convite>"
      E a notificação de convite para "<email>" "<notificacao>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | opcao     | conta              | email                       | convite    | notificacao | erro                                 |
        | permitido | nenhuma conta      | gsferroti+starter@gmail.com | existe     | foi enviada | não tem erro                         |
        | bloqueado | nenhuma conta      | gsferroti+starter@gmail.com | não existe | não saiu    | tem erro com mensagem que cita o "+" |
        | bloqueado | nenhuma conta      | gsferroti@gmail.com         | existe     | foi enviada | não tem erro                         |
        | bloqueado | uma conta existente | gsferroti+starter@gmail.com | existe     | foi enviada | não tem erro                         |

    Esquema do Cenário: [CT-12] a criação de convite no /app da organização segue a opção e a P-04
      Dado a admin da Acme no /app da Acme, a opção "<opcao>"
      E "<conta>" com o e-mail "<email>"
      Quando ela cria um convite para "<email>" com o papel panel_user
      Então o convite para "<email>" na Acme "<convite>"
      E a notificação de convite para "<email>" "<notificacao>"

      Exemplos:
        | opcao     | conta               | email                       | convite    | notificacao |
        | permitido | nenhuma conta       | gsferroti+starter@gmail.com | existe     | foi enviada |
        | bloqueado | nenhuma conta       | gsferroti+starter@gmail.com | não existe | não saiu    |
        | bloqueado | uma conta existente | gsferroti+starter@gmail.com | existe     | foi enviada |
        | bloqueado | nenhuma conta       | gsferroti@gmail.com         | existe     | foi enviada |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a regra entra no formulário do `/admin` e esquece o do `/app` | CT-12 | linha "nenhuma conta" + "+": convite "não existe"; o mutante grava |
| M2 | a regra recusa o "+" sem olhar se a conta existe (contraria a P-04) | CT-11, CT-12 | linha "uma conta existente": convite "existe"; o mutante recusa |
| M3 | a exceção da conta existente vale para qualquer conta (a de quem convida, ou qualquer e-mail sem "+" igual) | CT-11 | linha bloqueado + "nenhuma conta" + "+": "não existe"; o mutante grava porque há outras contas no banco (o admin) |
| M4 | o convite é recusado, mas a notificação já tinha saído | CT-11 | linha bloqueado + "+": `Notification::assertNotSentOnDemand(ConviteDeAcesso::class)` para o endereço; o mutante notifica |

---

## Regra R6 — No convite em massa, o "+" sai com motivo próprio e os outros seguem

> `RQ-03`, `P-04`, `P-05` · perfil **padrão** · técnica: **EP** (sem "+", "+" sem conta, "+" com conta, "+" depois do "@") + **rastreio de efeito** (convite, notificação)

```gherkin
  Regra: No lote, o endereço com "+" sem conta sai com um motivo próprio, e os demais seguem

    Cenário: [CT-13] lote misto com a opção desligada: três enviados, uma falha com motivo próprio
      Dado a opção bloqueada e uma conta existente "c+1@example.com"
      Quando o lote "a@example.com", "b+1@example.com", "c+1@example.com", "d@example.com" é convidado pelo model, com o papel panel_user
      Então os enviados são "a@example.com", "c+1@example.com", "d@example.com"
      E a única falha é "b+1@example.com", com um motivo diferente de formato_invalido, convite_pendente, recusou_antes e erro_no_envio
      E saíram exatamente 3 notificações de convite, e nenhum convite para "b+1@example.com" existe

    Cenário: [CT-14] o mesmo lote com a opção permitindo envia todos
      Dado a opção permitindo e uma conta existente "c+1@example.com"
      Quando o administrador geral convida em massa, pela ação da tela de convites, o mesmo lote
      Então existem 4 convites e saíram 4 notificações de convite
      E nenhum endereço do lote ficou sem convite

    Cenário: [CT-15] o "+" depois do "@" cai no formato, não no motivo do "+"
      Dado a opção bloqueada
      Quando o lote "gsferroti@gma+il.com", "gsferroti@gmail.com" é convidado pelo model
      Então "gsferroti@gma+il.com" falha com o motivo formato_invalido
      E "gsferroti@gmail.com" é enviado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o "+" reaproveita o motivo `formato_invalido` | CT-13 | o motivo de `b+1@` não é `formato_invalido`; o mutante é |
| M2 | o primeiro "+" derruba o lote inteiro (validação do formulário inteiro em vez de por endereço) | CT-13 | `enviados` tem 3 endereços; o mutante não tem nenhum |
| M3 | o lote ignora a P-04 e recusa `c+1@` com conta | CT-13 | `c+1@example.com` está em `enviados`; o mutante o põe em `falhas` |
| M4 | o lote aplica a regra do "+" sempre, mesmo permitindo | CT-14 | 4 convites; o mutante cria 3 |
| M5 | o "+" é testado no e-mail inteiro e antes do formato | CT-15 | motivo `formato_invalido`; o mutante devolve o motivo do "+" |

---

## Regra R7 — Com a opção desligada, o convite para conta nova com "+" não se aceita

> `RQ-03`, `P-06` (Q4, respondida "não" em 2026-11-19), `Q7` · perfil **padrão** · técnica: **estado × evento** (convite pendente emitido com a opção permitindo × aceite, reenvio e lembrete depois de desligada)
> *(alterado em 2026-11-19: a Q4 foi respondida "não" pelo solicitante — esta regra dizia que o convite já emitido valia. Reescrita pela sessão, sem sub-agente da `feature-test-design`: a derivação cega anterior cobria a mesma superfície com o oráculo invertido.)*

```gherkin
  Regra: O convite com "+" para conta nova é recusado ao abrir o link e no login social; o admin revoga, e nada reenvia o link

    Esquema do Cenário: [CT-16] o link do convite com "+" emitido antes de desligar é recusado ao abrir, com o motivo
      Dado um convite pendente para "gsferroti+starter@gmail.com", emitido com a opção permitindo, e o login unificado "<unificado>"
      E a opção desligada depois do envio
      Quando a pessoa convidada abre o link em "<tela>"
      Então ela é levada ao login
      E vê o aviso "Este convite não pode ser aceito", com a mensagem do "+" e o pedido de um convite sem o "+"
      E nenhuma conta "gsferroti+starter@gmail.com" existe
      E o convite continua pendente

      Exemplos:
        | tela                   | unificado |
        | /app/register?token=…  | desligado |
        | /cadastro?token=…      | ligado    |

    Cenário: [CT-17] o login social pelo convite com "+" emitido antes de desligar recusa com o motivo
      Dado um convite pendente para "gsferroti+starter@gmail.com", emitido com a opção permitindo, e o registro aberto desligado
      E a opção desligada depois do envio, com o token do convite na sessão do login social
      Quando o Google devolve a pessoa com o e-mail "gsferroti+starter@gmail.com"
      Então nenhuma conta nasce e ninguém está autenticado
      E o aviso cita o "+", e não é a recusa genérica de convite
      E o convite continua pendente

    Cenário: [CT-29] o model recusa o aceite de conta nova com "+" e aceita a conta existente com "+"
      Dado a opção desligada
      E um convite pendente para "nova+1@example.com", sem conta
      E um convite pendente para "velha+1@example.com", que já é de uma conta
      Quando o aceite é chamado direto no model, para os dois
      Então o primeiro lança a recusa com a mensagem do "+" e nenhuma conta nasce
      E o segundo vincula a conta existente

    Cenário: [CT-30] o admin não reenvia o convite que não se aceita, e revoga
      Dado a opção desligada
      E um convite pendente para "gsferroti+starter@gmail.com", sem conta
      E um convite pendente para "gsferroti@gmail.com"
      Quando o admin abre a lista de convites do /admin
      Então a ação Reenviar não aparece para o convite com "+" e aparece para o outro
      E a ação Revogar aparece para os dois, e revogar o convite com "+" o remove

    Cenário: [CT-31] o lembrete automático pula o convite que não se aceita
      Dado a opção desligada
      E dois convites pendentes, com o lembrete devido: "gsferroti+starter@gmail.com" (sem conta) e "gsferroti@gmail.com"
      Quando o comando de lembretes roda
      Então só "gsferroti@gmail.com" recebe lembrete
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tela aceita o convite com "+" (a leitura antiga da P-06) | CT-16 | a conta não existe e o convite segue pendente; o mutante cria |
| M2 | a recusa da tela vira erro no campo desabilitado, sem redirecionar | CT-16 | o redirecionamento ao login com o aviso; o mutante fica na tela |
| M3 | o login social pelo convite pula a conferência do "+" | CT-17 | ninguém autenticado e o aviso do "+"; o mutante cria a conta |
| M4 | o `aceitar()` do model não recusa | CT-29 | a exceção e a contagem de contas; o mutante cria |
| M5 | o `aceitar()` recusa também a conta existente com "+" | CT-29 | o vínculo da conta existente; o mutante lança |
| M6 | o Reenviar continua visível para o convite com "+" | CT-30 | a ação oculta nessa linha |
| M7 | o lembrete não pula o convite com "+" | CT-31 | uma notificação só, para o endereço sem "+" |

---

## Regra R8 — CRUD de usuários: criar segue a opção; editar só olha o e-mail novo

> `RQ-02`, `RQ-03`, `P-03`, `P-04` · perfil **padrão** · técnica: **criação ≠ edição** + **tabela de decisão** (e-mail atual × e-mail novo)

```gherkin
  Regra: Criar um usuário segue a opção; editar só olha o e-mail se ele mudar

    Esquema do Cenário: [CT-18] criar usuário no /admin segue a opção
      Dado o administrador geral no /admin, a opção "<opcao>"
      Quando ele cria o usuário "<email>" com nome, senha e o papel panel_user
      Então o usuário "<email>" "<usuario>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | opcao     | email                       | usuario    | erro                                 |
        | permitido | gsferroti+starter@gmail.com | existe     | não tem erro                         |
        | bloqueado | gsferroti+starter@gmail.com | não existe | tem erro com mensagem que cita o "+" |
        | bloqueado | gsferroti@gmail.com         | existe     | não tem erro                         |

    Esquema do Cenário: [CT-19] editar usuário no /admin com a opção desligada só olha o e-mail novo
      Dado o administrador geral no /admin, a opção "<opcao>"
      E o usuário existente com o e-mail "<atual>" e o nome "Antigo"
      Quando ele salva o usuário com o e-mail "<novo>" e o nome "Novo"
      Então o e-mail gravado é "<gravado>" e o nome gravado é "<nome>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | opcao     | atual                       | novo                            | gravado                     | nome   | erro                                 | # partição                   |
        | bloqueado | gsferroti+starter@gmail.com | gsferroti+starter@gmail.com     | gsferroti+starter@gmail.com | Novo   | não tem erro                         | sem trocar (P-04)            |
        | bloqueado | gsferroti+starter@gmail.com | gsferroti+starter-kit@gmail.com | gsferroti+starter@gmail.com | Antigo | tem erro com mensagem que cita o "+" | troca de "+" para outro "+"  |
        | bloqueado | gsferroti+starter@gmail.com | gsferroti@gmail.com             | gsferroti@gmail.com         | Novo   | não tem erro                         | troca para sem "+"           |
        | bloqueado | gsferroti@gmail.com         | gsferroti+starter@gmail.com     | gsferroti@gmail.com         | Antigo | tem erro com mensagem que cita o "+" | troca de sem "+" para "+"    |
        | permitido | gsferroti@gmail.com         | gsferroti+starter@gmail.com     | gsferroti+starter@gmail.com | Novo   | não tem erro                         | permitido: troca para "+"    |

    Esquema do Cenário: [CT-20] criar e editar usuário no /app da organização seguem a opção
      Dado a admin da Acme no /app da Acme, a opção "<opcao>"
      E, na edição, o usuário da Acme com o e-mail "<atual>"
      Quando ela "<operacao>" o usuário com o e-mail "<email>"
      Então o e-mail "<email>" "<gravado>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | opcao     | operacao | atual                       | email                       | gravado          | erro                                 |
        | bloqueado | cria     | —                           | gsferroti+starter@gmail.com | não foi gravado  | tem erro com mensagem que cita o "+" |
        | bloqueado | cria     | —                           | gsferroti@gmail.com         | foi gravado      | não tem erro                         |
        | bloqueado | salva    | gsferroti+starter@gmail.com | gsferroti+starter@gmail.com | continua gravado | não tem erro                         |
        | bloqueado | salva    | gsferroti@gmail.com         | gsferroti+starter@gmail.com | não foi gravado  | tem erro com mensagem que cita o "+" |
        | permitido | cria     | —                           | gsferroti+starter@gmail.com | foi gravado      | não tem erro                         |
        | permitido | salva    | gsferroti@gmail.com         | gsferroti+starter@gmail.com | foi gravado      | não tem erro                         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a regra não ignora o e-mail atual: a conta com "+" não salva nada | CT-19, CT-20 | linha "sem trocar": nome "Novo", sem erro; o mutante dá erro em `email` |
| M2 | a regra é ignorada sempre que o e-mail **atual** tem "+" (em vez de quando o novo é igual ao atual) | CT-19 | linha "de '+' para outro '+'": erro e e-mail mantido; o mutante grava `+starter-kit` |
| M3 | a regra só existe na criação (`->required(fn …)`/`visibleOn('create')`) | CT-19, CT-20 | linha "de sem '+' para '+'": erro; o mutante grava |
| M4 | a regra entra no `/admin` e esquece o `/app` | CT-20 | linha `cria` + "+": "não foi gravado"; o mutante grava |
| M5 | a regra sempre bloqueia (ignora o estado), na criação ou na edição | CT-18, CT-19, CT-20 | linhas `permitido` com "+": "existe" / "foi gravado"; o mutante recusa |

---

## Regra R9 — O login social não cria conta com "+" bloqueado e diz o motivo

> `RQ-03`, `P-03`, `P-05` · perfil **padrão** · técnica: **rastreio de efeito** (conta, vínculo, sessão, aviso) + **EP** (estado da opção)

```gherkin
  Regra: Com a opção desligada, o login social não cria a conta com "+" e mostra o motivo

    Cenário: [CT-21] o Google devolve um e-mail com "+" e a opção desligada: nada nasce, e o aviso diz o motivo
      Dado o registro aberto ligado, a opção bloqueada, o login com Google ligado
      E nenhuma conta nem convite para "gsferroti+starter@gmail.com"
      Quando o Google devolve a pessoa com o e-mail "gsferroti+starter@gmail.com"
      Então ela não está autenticada, nenhuma conta "gsferroti+starter@gmail.com" existe e nenhum vínculo social foi gravado
      E o aviso exibido cita o "+" e não é "O acesso a este sistema é por convite."

    Cenário: [CT-22] a mesma volta do Google com a opção permitindo cria a conta
      Dado o registro aberto ligado, a opção permitindo, o login com Google ligado
      E nenhuma conta para "gsferroti+starter@gmail.com"
      Quando o Google devolve a pessoa com o e-mail "gsferroti+starter@gmail.com"
      Então a conta "gsferroti+starter@gmail.com" existe e está autenticada

    Cenário: [CT-23] a recusa social leva ao login, de onde a pessoa ainda entra com outro e-mail
      Dado o registro aberto ligado, a opção bloqueada, e a pessoa recusada no retorno do Google com "gsferroti+starter@gmail.com"
      Quando o Google devolve a mesma pessoa, agora com o e-mail "gsferroti@gmail.com"
      Então a conta "gsferroti@gmail.com" existe e está autenticada
      E nenhuma conta "gsferroti+starter@gmail.com" existe
```

O registro aberto **ligado** no `Dado` do CT-21 é o que torna a recusa discriminante. Fechado, o login social recusaria pela mensagem genérica de convite, e o cenário passaria sem a regra do "+".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o login social não aplica a regra: cria a conta com "+" | CT-21 | `User::where('email', …)->exists()` é falso; o mutante cria e autentica |
| M2 | recusa com a mensagem genérica de "por convite" | CT-21 | o título do aviso cita "+" e é diferente de "O acesso a este sistema é por convite."; o mutante mostra a genérica |
| M3 | grava o vínculo social antes de recusar | CT-21 | nenhum `VinculoSocial` para o `sub`; o mutante grava |
| M4 | recusa todo e-mail do provedor com a opção desligada, ou ignora o estado | CT-22, CT-23 | CT-22: conta existe com permitido; CT-23: `gsferroti@` entra com bloqueado; o mutante recusa |
| M5 | a recusa deixa a sessão do login social com o contexto antigo e trava a volta seguinte | CT-23 | a segunda volta autentica `gsferroti@gmail.com`; o mutante recusa |

---

## Regra R10 — A troca de e-mail no perfil segue a opção, nos três painéis

> `RQ-03`, `P-03` (Q2), `P-04` · perfil **padrão** · técnica: **criação ≠ edição** + **EP por painel**

```gherkin
  Regra: Trocar o e-mail no perfil para um com "+" é recusado com a opção desligada; salvar sem trocar passa

    Esquema do Cenário: [CT-24] a troca de e-mail no perfil segue a opção e só olha o e-mail novo
      Dado a pessoa autenticada no /admin com o e-mail "<atual>" e o nome "Antigo", e a opção "<opcao>"
      Quando ela salva as informações pessoais com o e-mail "<novo>", o nome "Novo" e a senha atual correta
      Então o e-mail gravado é "<gravado>" e o nome gravado é "<nome>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | opcao     | atual                       | novo                            | gravado                         | nome   | erro                                 |
        | bloqueado | gsferroti@gmail.com         | gsferroti+1@gmail.com           | gsferroti@gmail.com             | Antigo | tem erro com mensagem que cita o "+" |
        | bloqueado | gsferroti+starter@gmail.com | gsferroti+starter@gmail.com     | gsferroti+starter@gmail.com     | Novo   | não tem erro                         |
        | bloqueado | gsferroti+starter@gmail.com | gsferroti+starter-kit@gmail.com | gsferroti+starter@gmail.com     | Antigo | tem erro com mensagem que cita o "+" |
        | bloqueado | gsferroti@gmail.com         | gsferroti2@gmail.com            | gsferroti2@gmail.com            | Novo   | não tem erro                         |
        | permitido | gsferroti@gmail.com         | gsferroti+1@gmail.com           | gsferroti+1@gmail.com           | Novo   | não tem erro                         |

    Esquema do Cenário: [CT-25] a troca para "+" no perfil de cada painel segue a opção
      Dado a pessoa com acesso ao painel "<painel>", o e-mail "gsferroti@gmail.com", e a opção "<opcao>"
      E a página "/<painel>/meu-perfil" aberta (GET real, que boota o painel)
      Quando ela salva as informações pessoais desse painel com o e-mail "gsferroti+1@gmail.com" e a senha atual correta
      Então o e-mail gravado é "<gravado>"
      E o campo de e-mail "<erro>"

      Exemplos:
        | painel | opcao     | gravado               | erro                                 |
        | admin  | bloqueado | gsferroti@gmail.com   | tem erro com mensagem que cita o "+" |
        | app    | bloqueado | gsferroti@gmail.com   | tem erro com mensagem que cita o "+" |
        | infra  | bloqueado | gsferroti@gmail.com   | tem erro com mensagem que cita o "+" |
        | admin  | permitido | gsferroti+1@gmail.com | não tem erro                         |
        | app    | permitido | gsferroti+1@gmail.com | não tem erro                         |
        | infra  | permitido | gsferroti+1@gmail.com | não tem erro                         |

    Esquema do Cenário: [CT-26] cada painel serve, em meu-perfil, as informações pessoais com a regra
      Dado a pessoa com acesso ao painel "<painel>"
      Quando ela abre "/<painel>/meu-perfil"
      Então a página responde 200 e traz o componente de informações pessoais do kit
      E não traz o componente de informações pessoais padrão do Breezy

      Exemplos:
        | painel |
        | admin  |
        | app    |
        | infra  |
```

No CT-25, o componente testado é o que o plugin Breezy **daquele painel** registra como `personal_info`, depois do GET, e não uma classe fixa. Assim o cenário mede o registro por painel. O CT-26 fecha o mesmo mutante pelo HTML entregue.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o perfil não aplica a regra (fica o `PersonalInfo` padrão) | CT-24 | linha 1: erro e e-mail mantido; o mutante grava `gsferroti+1@` |
| M2 | a regra não ignora o e-mail atual: a conta com "+" não salva o nome | CT-24 | linha 2: nome "Novo", sem erro; o mutante dá erro |
| M3 | o componente novo é registrado só no `/app` (ou em dois painéis) | CT-25, CT-26 | linhas `admin`/`infra`: erro e e-mail mantido; o mutante grava; o CT-26 acha o componente padrão |
| M4 | a regra é ignorada quando o atual tem "+" | CT-24 | linha 3: erro e `+starter` mantido; o mutante grava `+starter-kit` |

---

## Regra R11 — A conta existente com "+" continua entrando

> `P-04` · perfil **padrão** · técnica: **EP** (via de entrada: senha × social)

```gherkin
  Regra: A opção desligada não impede a conta que já tem "+" de entrar

    Esquema do Cenário: [CT-27] a conta existente com "+" entra com a opção desligada
      Dado a conta existente "gsferroti+starter@gmail.com", com papel panel_user, e a opção bloqueada
      E o login com Google ligado
      Quando ela entra por "<via>"
      Então ela está autenticada como "gsferroti+starter@gmail.com"
      E continua existindo uma única conta com esse e-mail

      Exemplos:
        | via                                          |
        | e-mail e senha no login do /app              |
        | retorno do Google com o mesmo e-mail         |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o login social aplica a regra do "+" antes de procurar a conta existente | CT-27 | linha Google: `assertAuthenticatedAs`; o mutante recusa com o aviso do "+" |
| M2 | a regra entra na validação do e-mail do formulário de login | CT-27 | linha senha: autenticada; o mutante dá erro no campo |
| M3 | o retorno social trata o "+" como conta nova e cria uma segunda | CT-27 | linha Google: `User::where('email', …)->count()` é 1 |

---

## Lacunas declaradas

| # | Mutante | Por que sem matador |
|---|---|---|
| L1 — sem matador | a regra vive só nos formulários do CRUD (`/admin` e `/app`) e do perfil; por fora da UI, um `User::create`/`update` com "+" passa | o `00` exige a regra nas vias, não no model. `kit:install` e `kit:admin` estão fora de escopo (P-03) e criam contas pelo model, então um CT de model recusando "+" afirmaria um comportamento que o requisito exclui. O gate de camada é cumprido pelo registro (CT-09) e pelo lote (CT-13), e fica declarado para o CRUD e o perfil |
| L2 — sem matador | o reenvio ou o lembrete de um convite com "+" emitido antes é recusado ou liberado, com a opção desligada | recorte aberto (Q?3); sem cenário da direção até a resposta |

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | regra do "+" só no formulário do CRUD e do perfil, sem prova por fora da UI | ⚠️ **sem matador** — L1 | — (lacuna declarada) |
| M2 | reenvio/lembrete de convite com "+" emitido antes tratado como convite novo | ⚠️ **sem matador** — L2 (Q?3) | — (lacuna declarada) |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a feature não recebe `{id}` novo nem muda quem edita qual usuário; o `EditUser` já tem barreira própria (`tests/Tenancy/AdminDaOrganizacaoTest.php`) | — |
| Autorização exercida na ação | não se aplica: a opção não muda quem pode convidar, criar ou editar; a tela de configurações já tem CT de autorização (`ConfiguracoesDoKitTelaTest`) | — |
| Idempotência (ancorada no agregado) | não se aplica: a feature não cria operação de escrita nova; acrescenta uma recusa às existentes, cuja idempotência os irmãos já cobrem (`LoginSocialGoogleTest` "não cria segunda conta quando o mesmo e-mail volta do Google duas vezes") | — |
| Concorrência | não se aplica: não há contador nem limite; a opção é lida a cada validação | — |
| **Fronteira no ponto de entrada** (gravação) | CT-08, CT-09, CT-11, CT-12, CT-13, CT-18, CT-20, CT-21, CT-24 | G4, G5, G6, G7 |
| **Domínio condicionado** (opção × e-mail × conta existente) | CT-07, CT-11, CT-13 | G3, G4, G5 |
| **Estado × operação de escrita** (convite já emitido × aceite) | CT-16, CT-17 | G4, G7 |
| Ausente ≠ null ≠ vazio | CT-01 (env ausente × vazio × irreconhecível), CT-02 (chave ausente no config), CT-03 (settings sem a linha) | G1 |
| Paginação / ordenação | não se aplica: nenhuma listagem nova | — |
| Timezone / DST | não se aplica: a regra não depende de data; a ordem "emitido antes, desligado depois" é de evento, não de relógio (CT-16) | — |
| Unicode / texto livre | CT-07 (posições do "+"); o "＋" (U+FF0B) é recusado pela regra `email` antes da regra do "+" (medido), então não chega à regra | G3 |
| Unicidade + soft delete | não se aplica: a feature não mexe na unicidade do e-mail | — |
| CRUD combinado (editar sem alterar nada) | CT-19, CT-20, CT-24 (linhas "sem trocar") | G4, G6 |
| Mass assignment | não se aplica: nenhum campo novo é aceito de formulário; o e-mail do aceite já é travado pelo irmão "ignora o email enviado pelo formulario" de `ConviteTest` | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| **Superfície Livewire** (método público, prop pública) | CT-25 (o componente de perfil por painel é chamado com e-mail fora do domínio); o novo campo da tela de configurações é um toggle booleano, coberto por CT-04 | G4, G2 |
| **Estado do framework usado sem validar** | não se aplica: nenhum `$filters`/`$tableSearch` novo | — |
| **IDOR e mass assignment por entidade** | não se aplica: a feature não cria tabela nem rota com `{id}`; persiste só uma propriedade nova no settings, gravada pela tela de configurações (autorização coberta em `ConfiguracoesDoKitTelaTest`) | — |
| **Escopo com discriminante nulo** | não se aplica: não há filtro de escopo novo; a ausência de valor da opção está na linha "Ausente ≠ null ≠ vazio" | — |
| **Saída do estado de erro** | CT-10 (registro), CT-23 (login social) | G4, G7 |
| Cardinalidade do destinatário (0/1/N) | CT-13 (3 de 4 notificados), CT-11 (1 / 0) | G5, G4 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| `kit:admin` / `kit:install` criando conta com "+" com a opção desligada | fora de escopo (P-03); vira lacuna L1 e não cenário |
| linhas `off` e `no` no CT-01 | dependem de Q?2 (raia desenho); entram com a resposta |
| registro na organização com `/cadastro` | mata o mesmo M4/M5 de R2 que o CT-06 já mata |
| aceite do convite com "+" com a opção **permitindo** | é o caminho feliz existente (`ConviteTest` "aceita o convite e cria o usuario com o papel"); não mata mutante novo |
| convite em massa pela ação do `/app` (Tenancy) | o lote é o mesmo model (`Convite::convidarEmMassa`), já provado por CT-13 e CT-14 |
| e-mail com parte local entre aspas contendo "@" (`"a@b"+x@…`) | medido: a regra `email` do Laravel o recusa antes da regra do "+" |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | o valor do env decide; só o falso explícito bloqueia | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R1-M1, M2, M3 |
| CT-02 | config do projeto sem a chave permite | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R1-M4 |
| CT-03 | settings não migrado e gravado | R1 | EP | G1 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R1-M5, M6 |
| CT-04 | a tela desliga e o registro recusa | R2 | rastreio de efeito | G2 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R2-M1, M2 |
| CT-05 | só o leitor lê a chave | R2 | EP (varredura) | G1 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R2-M3 |
| CT-06 | a organização segue a opção da instalação | R2 | EP | G6 | componente Livewire/Filament | `tests/Tenancy/RegistroAbertoTenancyTest.php` | R2-M4, M5 |
| CT-07 | a regra decide pela parte local | R3 | EP + BVA de posição | G3 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R3-M1…M5 |
| CT-08 | `/app/register` e `/cadastro` seguem a opção | R4 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R4-M1, M3, M4 |
| CT-09 | registro por fora da tela recusa | R4 | rastreio de efeito | G5 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R4-M2 |
| CT-10 | recusa no registro tem saída | R4 | saída do erro | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R4-M5 |
| CT-11 | convite no `/admin` segue a opção e a P-04 | R5 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R5-M2, M3, M4 |
| CT-12 | convite no `/app` segue a opção e a P-04 | R5 | tabela de decisão | G6 | componente Livewire/Filament | `tests/Tenancy/AdminDaOrganizacaoTest.php` | R5-M1, M2 |
| CT-13 | lote misto: motivo próprio, os outros seguem | R6 | EP + rastreio | G5 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R6-M1, M2, M3 |
| CT-14 | lote com a opção permitindo envia todos | R6 | EP | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R6-M4 |
| CT-15 | "+" depois do "@" cai no formato | R6 | BVA de posição | G5 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R6-M5 |
| CT-16 | aceite pela tela de convite já emitido | R7 | estado × evento | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R7-M1, M2 |
| CT-17 | aceite social de convite já emitido | R7 | estado × evento | G7 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R7-M2, M3 |
| CT-18 | criar usuário no `/admin` | R8 | tabela de decisão | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R8-M5 |
| CT-19 | editar usuário no `/admin` | R8 | criação ≠ edição | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R8-M1, M2, M3 |
| CT-20 | criar e editar usuário no `/app` | R8 | criação ≠ edição | G6 | componente Livewire/Filament | `tests/Tenancy/AdminDaOrganizacaoTest.php` | R8-M1, M3, M4 |
| CT-21 | social com "+" bloqueado: nada nasce, aviso diz o motivo | R9 | rastreio de efeito | G7 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R9-M1, M2, M3 |
| CT-22 | social com "+" permitido cria | R9 | EP | G7 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R9-M4 |
| CT-23 | recusa social tem saída | R9 | saída do erro | G7 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R9-M4, M5 |
| CT-24 | troca de e-mail no perfil | R10 | criação ≠ edição | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R10-M1, M2, M4 |
| CT-25 | recusa no perfil de cada painel | R10 | EP por painel | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R10-M3 |
| CT-26 | cada painel serve o componente do kit | R10 | EP por painel | G8 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R10-M3 |
| CT-27 | conta existente com "+" entra | R11 | EP | G7 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R11-M1, M2, M3 |
| CT-28 | lote pela tela: motivo do "+" e conta existente em qualquer caixa | R6 | EP + rastreio de efeito | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | M53, M54 |
| CT-29 | o model recusa conta nova com "+" e aceita a existente | R7 | estado × evento | G5 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R7-M4, M5 |
| CT-30 | Reenviar oculto para o convite que não se aceita; Revogar presente | R7 | estado × evento | G4 | componente Livewire/Filament | `tests/Kit/EmailComMaisTest.php` | R7-M6 |
| CT-31 | o lembrete pula o convite que não se aceita | R7 | estado × evento | G5 | Pest feature HTTP | `tests/Kit/EmailComMaisTest.php` | R7-M7 |

O arquivo `tests/Kit/EmailComMaisTest.php` é proposto. Os CT de Tenancy vão para os arquivos irmãos que já têm os helpers (Setup Global, "Helpers dos irmãos e onde os CT moram").

## Sem CT-B

- Motivo: nenhum oráculo depende de JavaScript executado, de pixel ou de acessibilidade. O erro de campo, a gravação, o aviso do Filament (`avisosDoFilament()`), o redirect do login social e o componente servido por painel são observáveis por componente Livewire ou por HTTP. A troca de aba da tela de configurações não é oráculo desta feature, porque o CT-04 carrega a aba com `carregarAba()`.

## Adendo do step 9 (2026-11-19)

Vem do passe de eixos (RD-02, RD-04): nenhum caso lia o texto do motivo do lote na tela, e o lote
comparava a coluna crua, recusando a conta existente gravada com caixa mista que o formulário
individual aceita.

```gherkin
Cenário: [CT-28] Pela tela, o lote mostra o motivo do "+" e deixa passar a conta existente em qualquer caixa
  Dado a opção desligada
  E uma conta "C+1@Example.com"
  Quando o admin convida em lote "a@example.com", "b+1@example.com" e "c+1@example.com" pela tela de convites
  Então "c+1@example.com" recebe convite
  E "b+1@example.com" não recebe
  E o aviso do lote diz "b+1@example.com — este sistema não aceita e-mail com "+" antes do "@""
```

| CT | Regra | Técnica | Mata |
|---|---|---|---|
| CT-28 | R6 | EP (caixa da conta existente) e rastreio de efeito na tela | M53 (o lote sem `lower(email)`), M54 (o motivo sem texto em `motivoLegivel()`) |
