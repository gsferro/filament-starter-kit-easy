# Casos de Teste — W7: validação de e-mail editável

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando implementação — ela não existe
> ainda. O vendor do Filament e do Laravel foi lido para confirmar **API**, nunca comportamento
> esperado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| decisão por request (middleware) | 3 | 3 | 9 | **completo** |
| existência da rota de destino | 2 | 3 | 6 | padrão |
| edição na tela (Settings + toggle) | 2 | 2 | 4 | padrão |
| não-regressão (convite, `/admin`, `/infra`) | 2 | 3 | 6 | padrão |

Justificativa do 9: a feature decide **fronteira de acesso a painel**. Errar para o lado permissivo
deixa a opção inerte (o defeito que o gate anterior reprovou); errar para o restritivo **tranca
todo mundo fora do `/app`**, inclusive quem nunca teve nada a ver com registro aberto.

- Técnicas aplicadas: **tabela de decisão** (opção × `email_verified_at` × `expectsJson`),
  **EP** (partições do estado de verificação e da origem da conta), **matriz painel × exigência**,
  **rastreio de efeito** (notificação de verificação e log de barramento).
- Cenários: 14 na derivação + **8 acrescentados pela revisão adversarial** = 22 · Regras: 7 ·
  Mutantes previstos: 20 na derivação + 5 da revisão = 25 · Sem matador: 0
- **Divergência declarada (rule vence skill)**: `.ai/rules/testes-browser.md` e a experiência do
  projeto mandam rodar browser em série; a skill sugere `--parallel --tia` como padrão. Aqui não há
  CT-B, então a divergência não se materializa — mas o comando da verificação final é
  `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`, como a rule do projeto pede, e não
  `--parallel --tia`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | middleware novo (`ExigirEmailVerificado`); propriedade nova no Settings; migration de settings nova; toggle novo na tela; 2 rotas do painel que passam a existir sempre | CT-11, CT-12, CT-13 |
| **F** | decidir por request se barra; redirecionar para a tela de confirmação; responder 403 em requisição JSON; registrar o barramento | CT-01…CT-05, CT-14 |
| **D** | `users.email_verified_at` (nulo × preenchido); a propriedade booleana do Settings; o valor de `config('kit.registro.verificar_email')`. Origem da conta é partição relevante: registro aberto, convite, seeder/factory, tela de usuários | CT-01…CT-06, CT-09 |
| **I** | toda rota de página do `/app` (HTTP); requisição AJAX do Livewire (`expectsJson`); a tela de Configurações do Kit (componente Livewire); o `.env` como semeador | CT-04, CT-11, CT-12 |
| **P** | SQLite na suíte; sem dependência de plataforma além do banco. **Nenhuma** dependência de JS, cor ou layout — é o que dispensa o `05` | — |
| **O** | quem opera: administrador liga/desliga em `/admin`; usuário comum é o afetado. Uso indevido plausível: ligar numa base legada e trancar todos | CT-07, CT-13 |
| **T** | o requisito é sobre **quando** a leitura acontece (boot × request). É a dimensão central, não um detalhe. Não há expiração, agendamento nem timezone envolvidos | CT-05, CT-06 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o barramento é decidido a cada request, pelo valor efetivo da opção | middleware (completo) | RQ-02, RQ-03, RQ-06 | tabela de decisão | CT-01…CT-04 |
| R2 — a mudança na tela vale no request seguinte, sem deploy | middleware (completo) | RQ-01, RQ-06 | rastreio de estado + EP | CT-05, CT-06 |
| R3 — com a opção desligada ninguém é barrado e nenhum e-mail sai | middleware (completo) | RQ-05 | rastreio de efeito | CT-02, CT-07 |
| R4 — a rota de destino existe nos dois estados da opção | rota (padrão) | RQ-09 | EP (2 partições) | CT-08 |
| R5 — quem vem de convite não é barrado e não recebe e-mail | não-regressão (padrão) | RQ-07 | rastreio de efeito | CT-09 |
| R6 — `/admin` e `/infra` não exigem e-mail validado | não-regressão (padrão) | RQ-08 | matriz painel × exigência | CT-10 |
| R7 — a opção é editável e o valor gravado governa o comportamento | tela (padrão) | RQ-01, RQ-06 | os três lugares do contrato do Settings | CT-11, CT-12, CT-13 |

Nenhuma técnica foi escalada acima do perfil da área.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da classe `ExigirEmailVerificado` | é **também** o único observável de RQ-03, que exige um *middleware próprio do kit* | **aceito como oráculo em CT-03b, e só nele.** A primeira versão desta linha afirmava que CT-03 já fazia isso, e era falsa — o Gherkin de CT-03 não menciona a classe. A revisão adversarial pegou a discrepância |
| `emailVerifiedMiddlewareName()` como ponto de extensão | escolha de implementação | detalhe; nenhum `Então` afirma sobre o método |
| o rótulo e o `helperText` do toggle | comportamento visível que o requisito não determina | nenhum cenário afirma sobre o texto; CT-11 afirma sobre o **campo** e a **gravação** |
| reutilizar o channel `autenticacao` | escolha de implementação | CT-14 afirma que **existe** trilha do barramento; qual channel é detalhe herdado do padrão do projeto |
| a migration nova em vez da existente | escolha de implementação | CT-13 afirma sobre o **estado semeado**, não sobre qual arquivo semeou |

**Perguntas em aberto** (já registradas em `00-requisito.md` → `## Ambiguidades`):

- A chave continua no `.env` como semeadora? — premissa: **sim**. Bloqueia R7; CT-13 marcado
  `@premissa`.
- "Nenhum e-mail sai" cobre o usuário que navega de propósito até o prompt com a opção desligada?
  — premissa: **não**. Bloqueia R3; a fronteira do cenário CT-07 é o **fluxo**, e isso está escrito
  no cenário.

## Setup Global

### Personas

- **usuário do `/app` com e-mail validado** — `usuarioDoKit('panel_user')` + `forceFill(['email_verified_at' => now()])->save()`
  (o helper usa `User::create()`, e `email_verified_at` está fora do `$fillable` — então o usuário
  do helper nasce **não** validado)
- **usuário do `/app` sem e-mail validado** — `usuarioDoKit('panel_user')`, sem mais nada
- **convidado** — `ofertaPara('convidado@example.com')` + aceite pela tela `RegistroPorConvite`
- **administrador** — `usuarioDoKit('admin')`, para a tela de Configurações do Kit

### Fixtures

- `Convite` via `ofertaPara()` (helper do projeto)
- Papéis: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` no `beforeEach`,
  como `RegistroAbertoTest` já faz

### Fakes

- `Notification::fake()` nos cenários de rastreio de efeito de e-mail
- `espiarAutenticacao()` (helper do projeto) no cenário de log

### Estratégia de DB

- `RefreshDatabase` global, via `tests/Pest.php`. Suíte **Kit** (`tests/Kit`) — nenhum cenário
  precisa de `admin_app` nem de organização, então nada vai para `tests/Tenancy`
  (`.ai/rules/testes.md`).

---

## Regra R1 — o barramento é decidido a cada request, pelo valor efetivo da opção

> `RQ-02`, `RQ-03`, `RQ-06` · perfil **completo** · técnica: **tabela de decisão**

Condições e ações, com as células colapsadas só onde a ação comprovadamente não depende da
condição:

| # | opção | `email_verified_at` | requisição espera JSON | ação esperada |
|---|---|---|---|---|
| 1 | ligada | nulo | não | redireciona para a tela de confirmação |
| 2 | ligada | nulo | sim | 403 |
| 3 | ligada | preenchido | — | entra |
| 4 | desligada | nulo | — | entra |
| 5 | desligada | preenchido | — | entra (colapsada com a 4: com a opção desligada a coluna do e-mail não é lida) |

```gherkin
# language: pt
Funcionalidade: exigência de e-mail validado no painel de negócio

  Regra: o barramento é decidido a cada request, pelo valor efetivo da opção

    Cenário: [CT-01] com a exigência ligada, quem não validou o e-mail é levado à confirmação
      Dado a exigência de e-mail validado ligada
      E um usuário do painel de negócio sem e-mail validado, autenticado
      Quando ele abre a página inicial do painel de negócio
      Então a resposta redireciona para a tela de confirmação de e-mail
      E ele não vê o conteúdo do painel

    Cenário: [CT-02] com a exigência desligada, quem não validou o e-mail entra
      Dado a exigência de e-mail validado desligada
      E um usuário do painel de negócio sem e-mail validado, autenticado
      Quando ele abre a página inicial do painel de negócio
      Então a resposta é bem-sucedida
      E ele vê o conteúdo do painel

    Cenário: [CT-03] com a exigência ligada, quem validou o e-mail entra
      Dado a exigência de e-mail validado ligada
      E um usuário do painel de negócio com e-mail validado, autenticado
      Quando ele abre a página inicial do painel de negócio
      Então a resposta é bem-sucedida

    Cenário: [CT-04] requisição que espera JSON recebe 403, não redirecionamento
      Dado a exigência de e-mail validado ligada
      E um usuário do painel de negócio sem e-mail validado, autenticado
      Quando ele faz uma requisição que aceita apenas JSON à página inicial do painel
      Então a resposta tem status 403
```

**Por que CT-04 existe**: as requisições do Livewire dentro do painel esperam JSON. Uma
implementação que sempre redireciona faz o painel responder um HTML de redirecionamento a uma
chamada AJAX — a tela quebra sem erro no servidor. É a sutileza que o `handle()` do Laravel
resolve e que uma reimplementação apressada perde.

**Por que CT-02 e CT-03 não são o mesmo cenário**: eles diferem em **duas** condições ao mesmo
tempo, de propósito — cada um mata um mutante diferente (a guarda ausente e a guarda invertida), e
juntos fecham a diagonal da tabela.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a condição volta para o boot (`isRequired: RegistroAberto::exigirVerificacaoDeEmail()`) — o toggle grava e não faz efeito | CT-05 |
| M2 | guarda ausente: o middleware sempre delega ao pai, ignorando a opção | CT-02 |
| M3 | guarda invertida (`if (exigir) { return $next(); }`) | CT-01 **e** CT-02 |
| M4 | `hasVerifiedEmail()` negado ou trocado por `email !== null` | CT-03 |
| M5 | `expectsJson()` ignorado: redireciona sempre | CT-04 |
| M6 | o middleware lê `env('KIT_REGISTRO_VERIFICAR_EMAIL')` em vez de passar por `RegistroAberto` — o valor do banco nunca chega | CT-05 |

---

## Regra R2 — a mudança na tela vale no request seguinte, sem deploy

> `RQ-01`, `RQ-06` · perfil **completo** · técnica: **rastreio de estado** + EP

Esta é a regra que o defeito original violava, e o cenário tem de ser escrito de modo a
**falsificar** a implementação antiga: o valor entra pelo caminho real (gravar no Settings), não
por `config()` no teste. Injetar por `config()` mediria o middleware e deixaria o mapa sem oráculo
— que é exatamente o defeito silencioso que a rule `.ai/rules/settings.md` nomeia.

```gherkin
# language: pt
  Regra: a mudança feita na tela vale no request seguinte

    Cenário: [CT-05] a exigência gravada no banco barra no request seguinte
      Dado a exigência de e-mail validado desligada no ambiente
      E um usuário do painel de negócio sem e-mail validado, autenticado
      E que ele abre a página inicial do painel de negócio com sucesso
      Quando a exigência é gravada como ligada nas configurações do kit
      Então ao abrir a página inicial do painel de negócio ele é levado à confirmação de e-mail

    Cenário: [CT-06] a exigência desligada no banco vence o ambiente que a ligou
      Dado a exigência de e-mail validado ligada no ambiente
      E um usuário do painel de negócio sem e-mail validado, autenticado
      Quando a exigência é gravada como desligada nas configurações do kit
      Então ao abrir a página inicial do painel de negócio a resposta é bem-sucedida
```

**Nota de arnês**: o alinhamento config↔banco acontece no boot do `KitServiceProvider`, que já
passou quando o teste roda. O helper `alinharConfiguracoesDoKit()` do projeto é exatamente o que o
provider faz no request seguinte — usá-lo é reproduzir o request seguinte, não contornar o
mecanismo. É a mesma manobra do caso *"deixa o valor gravado no settings vencer o env"* já
existente.

**CT-06 é o par discriminante de CT-05**: sem ele, uma implementação que ignora o banco e sempre
barra (M2) passaria em CT-05.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | a propriedade existe na classe mas falta a linha do `mapaDeConfiguracao()` — grava e não governa | CT-05, CT-12 |
| M8 | a leitura acontece uma vez e é memoizada em propriedade estática | CT-05 seguido de CT-06 no mesmo processo (a suíte cobre os dois) |
| M9 | o mapa aponta a propriedade para a chave errada (`kit.registro.habilitado`) | CT-12 |

---

## Regra R3 — com a opção desligada ninguém é barrado e nenhum e-mail sai

> `RQ-05` · perfil **completo** · técnica: **rastreio de efeito** (o quê, as direções)

O efeito rastreado é a notificação `Filament\Auth\Notifications\VerifyEmail`, pelo canal que o
Filament usa. As direções: aconteceu quando devia (já coberto pelo CT existente da wiki ancestral),
**não** aconteceu quando não devia (CT-07), e o não-barramento (CT-02).

```gherkin
# language: pt
  Regra: com a exigência desligada nenhum e-mail de verificação é enviado

    Cenário: [CT-07] o cadastro com a exigência desligada não dispara e-mail de verificação
      Dado o registro aberto ligado e a exigência de e-mail validado desligada
      Quando um visitante conclui o cadastro
      Então nenhuma notificação de verificação de e-mail é enviada a ele
      E o e-mail dele consta como validado
```

**Fronteira do cenário, declarada**: "nenhum e-mail sai" é sobre o **fluxo** — o cadastro não
dispara envio. A tela de confirmação continua alcançável por URL digitada e o botão de reenvio dela
funciona; isso está registrado como premissa em `00-requisito.md`. O cenário não afirma o contrário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | `RegistroAberto::registrar()` deixa de gravar `email_verified_at` com a opção desligada — o vendor passa a enviar | CT-07 |
| M11 | a condição de envio passa a olhar a opção em vez de `hasVerifiedEmail()`, e envia para quem já validou | CT-07 (a asserção de ausência) e CT-09 |

---

## Regra R4 — a rota de destino existe nos dois estados da opção

> `RQ-09` · perfil **padrão** · técnica: **EP** (2 partições: opção ligada, opção desligada)

Sem esta regra, ligar a opção pela tela produziria `RouteNotFoundException` em vez de tela — um
500. O oráculo é a **existência nomeada** da rota, medida com a opção nos dois estados.

```gherkin
# language: pt
  Regra: a tela de confirmação de e-mail existe independentemente da opção

    Esquema do Cenário: [CT-08] a rota de confirmação existe nos dois estados da exigência
      Dado a exigência de e-mail validado <estado>
      Quando o painel de negócio é montado
      Então a rota nomeada da tela de confirmação de e-mail existe
      E o painel declara que a verificação é exigida

      Exemplos:
        | estado     | # partição                     |
        | ligada     | opção ligada                   |
        | desligada  | opção desligada — o caso novo  |
```

**A segunda linha é a inversão declarada.** O caso equivalente da wiki ancestral (CT-22b) afirmava
o **oposto** — que com a opção desligada o painel **não** exige verificação. Aquela asserção era o
guardião da dívida: ela travava exatamente o mecanismo que tornava a chave ineditável. A inversão
está justificada em `03-progresso.md`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | `->emailVerification(condição ? Classe : null)` sobrevive — com a opção desligada a rota não nasce, e ligar pela tela derruba o painel | CT-08 (linha "desligada") |
| M13 | `isRequired` continua condicional — o middleware não entra no array da rota e o toggle volta a mentir | CT-08 (linha "desligada") e CT-05 |

---

## Regra R5 — quem vem de convite não é barrado e não recebe e-mail

> `RQ-07` · perfil **padrão** · técnica: **rastreio de efeito** + EP pela origem da conta

```gherkin
# language: pt
  Regra: quem entra por convite não é alcançado pela exigência

    Cenário: [CT-09] o convidado entra no painel com a exigência ligada
      Dado a exigência de e-mail validado ligada
      E um convite válido para um endereço de e-mail
      Quando a pessoa aceita o convite e abre a página inicial do painel de negócio
      Então a resposta é bem-sucedida
      E nenhuma notificação de verificação de e-mail é enviada a ela
```

**Um único `Quando` com duas ações?** Não: "aceitar o convite e abrir o painel" é uma sequência de
2 eventos, e o oráculo é sobre o resultado do segundo — a forma que a skill pede para ciclo de dois
passos. O aceite é a precondição que **só o fluxo real produz** (é ele que grava
`email_verified_at`); fabricar o usuário por factory provaria menos, porque a factory grava a
coluna por outro caminho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | `Convite::aceitar()` perde o `forceFill(['email_verified_at' => now()])` | CT-09 (as duas asserções) |
| M15 | o middleware barra por "conta nova" em vez de por `hasVerifiedEmail()` | CT-09 |

---

## Regra R6 — `/admin` e `/infra` não exigem e-mail validado

> `RQ-08` · perfil **padrão** · técnica: **matriz painel × exigência**

`App\Models\User` implementa `MustVerifyEmail`, que é contrato **global**. O que protege os dois
painéis de administração é apenas o fato de eles não pedirem verificação — logo isso precisa de
oráculo próprio, e a matriz não se amostra: os **dois** painéis entram.

| painel | exige e-mail validado? |
|---|---|
| `app` | sim (sempre declarado; quem decide é o middleware) |
| `admin` | **não** |
| `infra` | **não** |

```gherkin
# language: pt
  Regra: os painéis de administração não exigem e-mail validado

    Esquema do Cenário: [CT-10] o painel de administração aceita quem não validou o e-mail
      Dado a exigência de e-mail validado ligada
      E um usuário com o papel "<papel>" e sem e-mail validado, autenticado
      Quando ele abre a página inicial do painel "<painel>"
      Então a resposta é bem-sucedida

      Exemplos:
        | painel | papel  |
        | admin  | admin  |
        | infra  | infra  |
```

**Valor discriminante**: a opção está **ligada** nos dois exemplos. Com ela desligada o cenário
passaria com o middleware aplicado globalmente por engano — não distinguiria nada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | o middleware é registrado como middleware global ou de grupo `web` em vez de por painel | CT-10 (as duas linhas) |
| M17 | `emailVerifiedMiddlewareName()` é chamado no provider errado, ou `requiresEmailVerification(true)` vaza para `/admin` | CT-10 |

---

## Regra R7 — a opção é editável e o valor gravado governa

> `RQ-01`, `RQ-06` · perfil **padrão** · técnica: os **três lugares** do contrato do Settings
> (propriedade, linha do mapa, `add()` na migration)

```gherkin
# language: pt
  Regra: a exigência é editável na tela de configurações do kit

    Cenário: [CT-11] o administrador liga a exigência pela tela e o valor é gravado
      Dado o registro aberto ligado
      E um administrador na tela de configurações do kit
      Quando ele marca a exigência de e-mail validado e salva
      Então a exigência gravada nas configurações do kit é verdadeira

    Cenário: [CT-12] a propriedade está ligada à chave de configuração que o kit lê
      Dado a exigência de e-mail validado gravada como ligada
      Quando a configuração do processo é alinhada com o banco
      Então a configuração de exigência de e-mail validado é verdadeira

    Cenário: [CT-13] @premissa a instalação nasce com a exigência semeada do ambiente
      Dado uma instalação recém-migrada
      Quando as configurações do kit são lidas
      Então a exigência de e-mail validado tem uma linha gravada
      E o valor dela é o que o ambiente definiu
```

**CT-11 é o gate de tela de escrita** (`/admin/configuracoes-do-kit` é rota de escrita): gravação
por componente Livewire, não visita. **CT-12 é o guardião do mapa** — sem ele, CT-11 fica verde com
o campo gravando e não governando nada, que é literalmente o defeito de 2026-08 desta chave.
**CT-13** cobre o terceiro lugar (a migration) e é o que impede `MissingSettings` no boot de uma
instalação existente que só rodou `migrate`.

**Nota**: CT-13 já é coberto pelo caso invariante existente
(`ConfiguracoesDoKitTest` → *"semeia todas as propriedades que a classe de settings declara"*), que
compara `mapaDeConfiguracao()` com as linhas do banco por reflexão. Não se escreve um caso novo
para ele — registra-se a cobertura herdada. O que **precisa** ser escrito é o ajuste do caso
existente *"desfaz e refaz a migration de settings sem quebrar"*, que hoje fixa o nome de **uma**
migration e conta as linhas contra o mapa: com duas migrations, ele reprova por aritmética. Ver
`03-progresso.md`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 (repetido) | propriedade sem linha no mapa | CT-12 |
| M18 | migration sem o `add()` — `aplicarNaConfig()` estoura `MissingSettings` no boot | CT-13 (herdado do caso invariante) |
| M19 | toggle não-`dehydrated`, ou fora do schema — a tela mostra e não grava | CT-11 |

---

## Checklist de Taxonomia

<!-- Resposta válida: ID de cenário, "não se aplica: {motivo}", ou "lacuna declarada: {tentado}". -->

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota nova recebe `{id}` de recurso; a rota `verify` do vendor recebe `{id}/{hash}` e é protegida por `signed` |
| Autorização exercida na ação (não só `can()`) | CT-01 (o barramento é exercido pelo request real, não por um predicado) |
| Idempotência (ancorada no agregado) | não se aplica: o middleware não escreve nada. A escrita da feature é o `save()` do Settings, cujo agregado é a linha de settings — `upsert()` por construção, e CT-11 grava sobre valor existente |
| Concorrência | não se aplica: sem contador, saldo nem limite |
| Fronteira no ponto de entrada (gravação) | não se aplica: o domínio é booleano — não há fronteira ordenável. As duas partições estão em CT-08 e no par CT-05/CT-06 |
| Domínio condicionado (um campo depende de outro) | CT-11 — a exigência só é editável com o registro aberto ligado, e é a mesma condição de visibilidade das outras duas opções de registro |
| Estado × operação de escrita | não se aplica: sem ciclo de vida nem soft delete |
| Ausente ≠ null ≠ vazio | CT-13 — propriedade sem linha no banco cai no `.env`; é o caso já coberto por *"mantem o valor do env quando a propriedade nao tem linha no banco"* |
| Paginação / ordenação | não se aplica: sem listagem nova |
| Timezone / DST | não se aplica: nenhuma comparação de data. `email_verified_at` é lido como nulo/não-nulo por `hasVerifiedEmail()` |
| Unicode / limite de varchar | não se aplica: sem texto livre novo |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | CT-05 seguido de CT-06 — ligar, desligar e voltar a ler no mesmo processo |
| Mass assignment | não se aplica ao middleware. `email_verified_at` fora do `$fillable` já é coberto por caso existente de `ConviteUsuarioExistenteTest` |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Regressão de painel adjacente** (novo, específico deste kit) | CT-10 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | ligada + não validado → confirmação | R1 | tabela de decisão | Feature (HTTP) | `tests/Kit/VerificacaoDeEmailTest.php` | M3 |
| CT-02 | desligada + não validado → entra | R1 | tabela de decisão | Feature (HTTP) | idem | M2, M3 |
| CT-03 | ligada + validado → entra | R1 | tabela de decisão | Feature (HTTP) | idem | M4 |
| CT-04 | JSON → 403 | R1 | tabela de decisão | Feature (HTTP) | idem | M5 |
| CT-05 | gravado no banco barra no request seguinte | R2 | rastreio de estado | Feature (HTTP) | idem | M1, M6, M7, M8, M13 |
| CT-06 | gravado desligado vence o ambiente ligado | R2 | rastreio de estado | Feature (HTTP) | idem | M8 |
| CT-07 | desligada → nenhum e-mail | R3 | rastreio de efeito | Feature (Livewire) | idem | M10, M11 |
| CT-08 | rota existe nos dois estados | R4 | EP | Unit-ish (painel montado) | idem | M12, M13 |
| CT-09 | convidado entra e não recebe e-mail | R5 | rastreio de efeito | Feature (HTTP + Livewire) | idem | M14, M15 |
| CT-10 | `/admin` e `/infra` inalterados | R6 | matriz painel × exigência | Feature (HTTP) | idem | M16, M17 |
| CT-11 | toggle grava pela tela | R7 | gravação por componente | Livewire | idem | M19 |
| CT-12 | a linha do mapa governa a config | R7 | contrato do Settings | Feature | idem | M7, M9 |
| CT-13 | a migration semeia do ambiente | R7 | contrato do Settings | — (herdado) | `tests/Kit/ConfiguracoesDoKitTest.php` | M18 |
| CT-14 | o barramento deixa trilha no log de autenticação | R1 | rastreio de efeito | Feature (HTTP) | `tests/Kit/VerificacaoDeEmailTest.php` | M20 |

**CT-14 e M20** (padrão de log do projeto, exigido pela `feature-wiki`): a implementação que barra
sem registrar deixa o suporte sem nada para olhar quando alguém liga a opção por acidente e o
painel "para de abrir". O oráculo é a mensagem no formato `[Classe@Método]` no channel
`autenticacao`, com o e-mail mascarado — o mesmo contrato que `RegistroAberto::registrar()` já
cumpre.

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| ~~ligada + não validado numa rota de resource~~ | **corte revertido.** A justificativa original ("o middleware vem de `getRouteMiddleware()`") era derivada do **plano**, não do requisito: a suíte importava a hipótese que devia testar, e todos os cenários batiam na mesma URL. Virou CT-01b, com duas rotas de classes diferentes |
| a tela de prompt renderiza para quem não validou | é o caminho feliz do vendor, já coberto pela suíte do Filament; nenhum mutante nosso morre nele |
| usuário sem nenhum papel + opção ligada | `canAccessPanel()` já barra antes; o cenário mediria a barreira errada |
| ligar e desligar pela tela e reler duas vezes | subsumido por CT-05 + CT-06 |
| o `helperText` do toggle menciona convite | texto que o requisito não determina — recusado como oráculo (ver `## Fronteira com o Plano`) |

## Sem CT-B

Nenhum cenário afirma sobre algo que **só o navegador prova**. O detalhamento:

- o barramento e o redirecionamento são HTTP puro — `assertRedirect` / `assertOk` bastam e são
  ordens de magnitude mais baratos;
- a visibilidade condicional do toggle depende de `->live()`, que é reatividade **Livewire** e se
  prova com `fillForm` + `assertFormFieldIsHidden` (a tabela de camadas da `feature-test-design` é
  explícita nesse ponto);
- a feature não introduz JavaScript, cor, tema, layout nem elemento de acessibilidade novo;
- a tela de confirmação já existia e já é visitada pelos cenários de smoke de browser do kit.

Registrado conforme o gate: `05-casos-de-teste-browser.md` **não** é criado.

## Revisão Adversarial — achados e fechamento

Perfil **completo** exige revisão por agente independente. Rodada 1 executada por sub-agente que
recebeu **apenas** o `00-requisito.md` e este arquivo — sem o PRD, sem as ADRs, sem código. Ela
produziu **13 achados**, dos quais 12 foram fechados com cenário novo ou oráculo reescrito e 1 foi
refutado com medição.

| # | Achado | Fechamento | Cenário |
|---|---|---|---|
| 1 | a direção "aconteceu quando devia" do rastreio de efeito estava delegada a um caso da wiki ancestral **sem ID nem arquivo** — promessa, não cobertura. Uma implementação que grava `email_verified_at` sempre passaria | as duas direções num `Esquema do Cenário` próprio, com asserção sobre `hasVerifiedEmail()` **e** sobre a notificação | **CT-07** reescrito |
| 2 | possível **laço de redirecionamento**: se o destino fosse guardado pelo mesmo middleware, `Route::has()` (nome) não pegaria. `Route::has` prova nome, não alcance | medido no `route:list`: a rota do prompt **não** carrega o middleware, porque nasce de um `Route::get()` direto no `routes/web.php` do Filament e não de `Page::registerRoutes()`. Cenário novo com `followingRedirects()` prova alcance | **CT-08b** novo |
| 3 | a coluna `expectsJson` foi colapsada nas linhas de opção desligada **por asserção, não por prova**. A implementação que lê a opção só no ramo HTML responde 403 em JSON com a exigência DESLIGADA — quebrando todo Livewire do `/app` no default do kit | as quatro combinações instanciadas; CT-04 ganhou a partição `desligada → 200` | **CT-04** ampliado |
| 4 | **RQ-03 sem nenhum cenário falsificador.** Um decisor implementado como Closure no provider passaria em todos os cenários de comportamento | oráculo estrutural sobre o array de middleware da rota, com a string completa (`FQCN:rota`) | **CT-03b** novo |
| 5 | todos os cenários batiam na **mesma URL**, e o corte do cenário de segunda rota tinha justificativa derivada do plano | duas rotas de classes diferentes: a `Dashboard` do vendor e uma `Page` do kit | **CT-01b** novo |
| 6 | oráculos fracos: `assertOk` sem âncora de conteúdo passa com 403 renderizado, redirecionamento resolvido ou tela de erro | âncora de identidade da resposta (`assertSeeLivewire(Dashboard::class)`) nos cenários de entrada | CT-01…CT-03, CT-06, CT-09 |
| 7 | **CT-14 não existia como cenário** — só no índice e na prosa; M20 citado sem linha na tabela | cenário escrito, e o par de ausência com ele | **CT-14 / CT-14b** |
| 8 | CT-13 declarava `Então o valor é o que o ambiente definiu` e delegava a um caso que prova **existência de linha**, nunca o valor | cenário próprio com as duas partições do ambiente. Importa: semear `false` literal desligaria em silêncio a barreira de quem tinha a chave ligada, durante uma atualização | **CT-13** reescrito |
| 9 | CT-12 instanciava só a partição verdadeira; mapa que devolve constante passaria | `Esquema do Cenário` com as duas partições | **CT-12** ampliado |
| 10 | M8 (leitura memoizada em estático) tinha "esperança de ordem de execução" como matador — `--filter` ou `--parallel` apagariam o kill | liga → desliga → liga, **num único cenário** | **CT-05/CT-06** fundidos |
| 11 | o `Dado` não fixava o estado do **registro aberto**. `habilitado() && exigirVerificacaoDeEmail()` é leitura plausível do requisito e deixaria a exigência inerte em toda instalação que só usa convite — o default do kit | cenário com o registro **fechado** e a exigência ligada. **Mudou a implementação**: o toggle deixou de se esconder com o registro desligado, porque exigência ligada e invisível é o defeito espelhado | **CT-01c** novo + **CT-11b** novo |
| 12 | CT-09 tinha duas ações no `Quando`, e "nenhuma notificação enviada" podia passar por o aceite ter estourado antes | o aceite virou precondição **com asserção própria** (`hasVerifiedEmail()`), deixando um `Quando` só | **CT-09** reescrito |
| 13 | RQ-04 provada só sobre usuário fabricado, nunca pelo fluxo real de registro com a opção ligada | coberto pelo fechamento do achado 1 | CT-07 |

**O único achado refutado**: o laço de redirecionamento (achado 2) não existe, e a refutação é
medição, não argumento — `php artisan route:list` mostra 12 rotas do `/app` com o middleware, e a
rota do prompt não está entre elas. Mesmo refutado, o cenário foi escrito: o que hoje é verdade por
construção passa a ser verdade **verificada**, e a próxima pessoa que mexer no registro de rotas
descobre pelo teste em vez de por um laço em produção.

**Rodada 2**: não executada. O teto da skill é 2 rodadas e a segunda é obrigatória apenas quando o
fechamento cria superfície nova de comportamento. Aqui o fechamento criou cenário sobre a superfície
que já estava mapeada — mais uma rota, mais uma partição, mais um oráculo — e uma única mudança de
implementação (a visibilidade do toggle), ela própria coberta por CT-11b. Registrado como decisão,
não como esquecimento.
