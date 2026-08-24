# Casos de Teste — W8: mais provedores de login social

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito** e dos ADRs. Nenhum cenário foi escrito olhando a implementação da
> feature — o worktree já tem `app/Support/ProvedorSocial.php`, `LoginSocialController.php` e os
> blades novos em progresso, e eles **não** foram lidos, de propósito (§"Regra de higiene de
> contexto" da skill). O que foi lido do `app/` é infra **anterior** à feature, para herdar
> convenção de arranjo: `config/kit.php`, `config/services.php`,
> `app/Settings/ConfiguracoesDoKit.php` e `app/Listeners/AuditarConfiguracoesDoKit.php`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil | Por quê |
|---|---|---|---|---|---|
| Barreira de e-mail verificado por provedor (ADR-03) | 3 | 3 | 9 | **completo** | integração externa (HTTP no GitHub), 4 ramos com regras diferentes; impacto é tomada de conta |
| Predicado de disponibilidade e as rotas de OAuth (RQ-04, RQ-09) | 3 | 3 | 9 | **completo** | reescreve caminho que já funcionava para servir quatro; abre superfície pública de OAuth |
| Segredos no Settings — cifra e normalização (ADR-06) | 3 | 3 | 9 | **completo** | migração de dado gravado; impacto é credencial em claro em banco, backup e trilha |
| Segredo fora de toda saída (`.ai/rules/pages.md`) | 2 | 3 | 6 | **completo** (escalado) | defeito histórico Blocker do kit na v0.19.0; escalado por reincidência medida |
| Rastro no channel `autenticacao` | 2 | 2 | 4 | **padrão** | efeito colateral, com precedente na wiki ancestral |
| Tela de Settings — seção, campos que abrem, gravação (RQ-05, RQ-06) | 2 | 2 | 4 | **padrão** | integra com Settings compartilhado; retrabalho manual |
| Botões e ícones na tela de login (RQ-03) | 2 | 3 | 6 | **padrão** | render hook único alcança as três telas de login; blade quebrado derruba as três |
| Escopo — Facebook e Discord fora (ADR-04, ADR-05) | 2 | 3 | 6 | **padrão** | `.env` meio preenchido é o arranjo real; impacto é autorização |
| Multi-tenancy ligada | 2 | 2 | 4 | **padrão** | ramo que a wiki ancestral só ganhou depois do quality gate (QA-02) |
| Defaults e coerção de env (RQ-07, RQ-08) | 1 | 3 | 3 | **padrão** (escalado) | P baixo, mas é o interruptor que abre OAuth; `.ai/rules/config.md` cobra falha fechada |
| Lista aberta / extensão (RQ-02) | 1 | 2 | 2 | **mínimo** | invariante estrutural |
| Documentação (RQ-10) | 1 | 2 | 2 | **mínimo** | asserção de presença em texto |

- **Técnicas aplicadas**: EP (com partição inválida isolada), BVA de string (vazio × ausente ×
  só-espaços), tabela de decisão, tabela estado × operação, matriz provedor × prova,
  rastreio de efeito (4 direções), normalização de identidade, pairwise implícito
  (provedor × arranjo).
- **Cenários**: 49 CT numerados (+ 5 desdobramentos `b`, 54 blocos de `Cenário`) + 2 CT-B ·
  **Regras**: 19 · **Mutantes previstos**: 128 · **Sem matador**: 0 · **Lacunas declaradas**: 2
  (em R9 e R14, as duas com o que foi tentado escrito).
- **Efeito da revisão adversarial** (rodada 1, fechada): 44 → 49 CT, 17 → 19 regras, 111 → 128
  mutantes. 23 achados, nenhum descartado: 5 cenários novos, 5 desdobramentos, 2 regras novas,
  1 regra escalada de perfil, 9 oráculos reescritos e **6 citações de mutante corrigidas — cinco
  delas eram falso ✅**. Ver `## Revisão Adversarial`.
- **Rodada 2 pendente**, e é o próximo passo desta wiki: o fechamento criou cenário novo, o que a
  regra de fechamento manda re-revisar uma vez.

### Divergência declarada: rule do projeto vence a skill

A skill sugere `pest --parallel --tia` como padrão. `.ai/rules/testes-browser.md` mediu que
`--parallel` derruba 4 de 11 cenários de navegador e que, sem PCOV, o `--tia` não termina (35 min,
abortado). **A rule vence.** Os comandos desta feature são dois:

```bash
composer test:kit        # = artisan test --testsuite=Kit,Tenancy --parallel — os 44 CT
composer test:browser    # = npm run build && view:cache && test --testsuite=Browser — os 2 CT-B, em série
```

Os dois scripts já existem em `composer.json:152,171`. **Use os scripts, não o `pest` cru**: o
`test:browser` embute `npm run build` **e** `view:cache`, e os dois são pré-requisito duro —
sem eles todo cenário de navegador falha por um motivo que não é o dele.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | enum de provedor; predicado de disponibilidade; controller de OAuth; par de rotas; blade dos botões + 4 partials de ícone; 9 propriedades de Settings + migration de settings; aba "Login" da tela de configurações; 3 blocos em `config/services.php` e 3 em `config/kit.php` | CT-01…CT-09, CT-20…CT-35, CT-40, CT-41 |
| **F**unction | exibir/esconder botão; derrubar rota; redirecionar ao provedor; provar e-mail verificado (4 provas diferentes); casar conta por e-mail; criar conta; escolher destino; cifrar e decifrar segredo; normalizar segredo já gravado; abrir campos no toggle | CT-01…CT-38 |
| **D**ata | interruptor por provedor; 3 chaves de credencial por provedor; payload bruto do provedor (`email_verified`, `verified_email`, `confirmed_email`, ausência); lista de e-mails do GitHub; `payload` JSON da tabela `settings`; **dado já gravado em claro** (o segredo do Google de instalação existente); `null`, string vazia e só-espaços em toda credencial | CT-01, CT-03, CT-10…CT-19, CT-20…CT-25, CT-39 |
| **I**nterfaces | `GET /auth/{provedor}/redirect` e `/callback`; `GET /{painel}/login` nos três painéis; `GET /admin/configuracoes-do-kit`; componente Livewire da página de Settings; migration de settings (`up`/`down`); a chamada de saída a `api.github.com` | CT-03, CT-04, CT-07, CT-08, CT-23…CT-35, CT-17 |
| **P**latform | `laravel/socialite ^5.30` (os drivers e o que cada um põe no bruto); `Http` do Laravel **não** intercepta o Guzzle do Socialite; `APP_KEY` (valor cifrado); implicit enum binding do Laravel 13; SQLite `:memory:` da suíte | CT-07, CT-15…CT-19, CT-20…CT-25 |
| **O**perations | quem instala com `.env` meio preenchido (o caso de `client_secret` vazio); quem liga um provedor e esquece os outros; quem já salvou o segredo do Google pela tela **antes** desta entrega; quem administra a instalação sem a permission da tela; script alternando quatro provedores contra o `throttle` compartilhado | CT-01, CT-05, CT-06, CT-24, CT-33 |
| **T**ime | ordem entre reverter código e reverter dado no rollback do ADR-06; a chamada extra do GitHub pode demorar/falhar; idempotência do callback repetido (duplo clique, retry do provedor) | CT-14, CT-19, CT-25 |

Nenhuma dimensão vazia. **Declarado o que NÃO gerou cenário**: `throttle:10,1` compartilhado
(ADR-02, consequência aceita por escrito) não ganhou cenário — o limite é do middleware do Laravel
e testá-lo mede o framework; e o efeito de rotacionar a `APP_KEY` (ADR-06, "riscos") não ganhou
cenário porque o comportamento esperado é a exceção do próprio Laravel.

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o botão de um provedor aparece **se e somente se** o interruptor dele está ligado E as três chaves de `services.{value}` estão preenchidas | predicado (completo) | RQ-04, RQ-08, RQ-09 | tabela de decisão × EP por provedor | CT-01, CT-02 |
| **R2** — indisponível, as **duas** rotas do provedor respondem 404; disponível, as duas ficam no ar | predicado (completo) | RQ-09 | estado × operação, 100% das células | CT-03, CT-04 |
| **R3** — o estado de um provedor não altera o de nenhum outro, nas duas direções | predicado (completo) | RQ-09 (leitura simétrica do `00`), RQ-02 | isolamento / matriz provedor × provedor | CT-05, CT-06 |
| **R4** — segmento de provedor fora do enum responde 404 nas duas rotas, **mesmo com credenciais e interruptor em config** | escopo (padrão) | RQ-01 + ADR-04, ADR-05 | EP das partições inválidas | CT-07 |
| **R5** — cada botão traz o ícone da marca **daquele** provedor e o `href` da rota dele, depois do formulário, nos três painéis | botões (padrão) | RQ-03 | EP exaustiva do enum × painéis | CT-08, CT-09 |
| **R6** — o login só autentica quando **aquele** provedor prova que o e-mail está verificado, pela prova que **ele** oferece | verificação (completo) | RQ-09 + ADR-03 | tabela de decisão provedor × prova + partição inválida isolada | CT-10…CT-14 |
| **R7** — a conferência do GitHub é **positiva**: exige `/user/emails` bem-sucedido com entrada `primary` **e** `verified` **e** e-mail igual ao do provedor | verificação (completo) | RQ-09 + ADR-03 | tabela de decisão + normalização + rastreio de efeito | CT-15…CT-19 |
| **R8** — os quatro `client_secret` ficam **criptograma** na tabela `settings`, legíveis na leitura e mascarados na trilha | segredos (completo) | ADR-06 | rastreio de efeito × EP por provedor | CT-20, CT-21, CT-22 |
| **R9** — a migration normaliza o `login_google_client_secret` já gravado, sem tocar em mais nada | segredos (completo) | ADR-06 | EP de 3 partições + estado × operação de rollback | CT-23, CT-24, CT-25 |
| **R10** — nenhum `client_secret` aparece no HTML de nenhuma tela, e **sobrevive** a um save que deixou o campo em branco | segredo fora da saída (completo) | `.ai/rules/pages.md`, RQ-06 | par obrigatório × EP por provedor | CT-26…CT-29 |
| **R11** — ligar o interruptor de um provedor **abre** os campos de credencial dele, e só dele | tela (padrão) | RQ-05, RQ-06 | estado × campo por provedor | CT-30, CT-31, CT-32 |
| **R12** — o que se grava na tela alcança **tudo que vem** do interruptor: config, botão e rota | tela (padrão) | RQ-06, RQ-09 | rastreio de efeito ponta a ponta | CT-33, CT-34, CT-35 |
| **R13** — o rastro no channel `autenticacao`: sucesso `info`, recusa `warning` com `motivo`, e-mail mascarado, segredo nunca | rastro (padrão) | RQ-09 + channel do PRD + padrão `[Classe@Método]` do kit | rastreio de efeito, 4 direções | CT-36…CT-39 |
| **R14** — o interruptor de **cada** provedor nasce `false` e falha **fechado** | defaults (padrão) | RQ-07, RQ-08 | EP de coerção × 4 chaves | CT-40 |
| **R15** — o enum é a **única** lista: acrescentar um caso não deixa **nenhuma** das sete superfícies derivadas para trás | extensão (**padrão**, escalado na revisão) | RQ-02 | invariante estrutural sobre todas as superfícies | CT-41, CT-46 |
| **R16** — cada provedor está declarado onde quem instala procura, e a ausência dos dois recusados está escrita **com o motivo** | docs (mínimo) | RQ-10 | asserção de presença + proximidade + ausência | CT-42 |
| **R17** — as barreiras e a tela valem igual com a multi-tenancy ligada, nas **duas** direções | tenancy (padrão) | RQ-09 + achado QA-02 da wiki ancestral | estado × operação replicado no ramo | CT-43, CT-44, CT-49 |
| **R18** — sem conta, o callback cria conta **se e somente se** o registro aberto está ligado | verificação (completo) | RQ-07, RQ-09 | tabela de decisão de 2 condições, **4 células** | CT-45 (+ CT-37) |
| **R19** — o login social autentica na conta **certa** e devolve a pessoa ao painel de **onde ela clicou** | predicado (completo) | RQ-09 | desambiguação de identidade + EP por painel | CT-47, CT-48 |

**Técnica escalada acima do perfil da área** (declarado, como a skill exige):

- **R10** roda em perfil `completo` numa área de P×I = 6. Motivo: o defeito exato desta regra foi
  **Blocker medido** na v0.19.0 (senha de SMTP em claro no corpo de `GET /admin/configuracoes-do-kit`),
  e esta entrega multiplica por quatro o número de campos sujeitos a ele. Reincidência medida vence
  o orçamento.
- **R14** roda em `padrão` numa área de P×I = 3. Motivo: `.ai/rules/config.md` documenta que o
  cast de bool falha **aberta** em `off`/`no`/lixo, e a chave abre superfície pública de OAuth. EP
  de uma linha não distingue as duas implementações.

**Estouro de teto declarado**: R13 tem 4 cenários num perfil `padrão` (teto 3). Rastreio de efeito
já exige três direções obrigatórias, e aqui há **duas superfícies** de log (ida e volta) mais a
direção "não logar na abertura de tela", que o `config/logging.php` cobra por escrito (1,1 MB/dia
medido). O gate do passo 6 vence o teto.

---

## Fronteira com o Plano

O que veio do `01-plano-acao.md` e foi **recusado como oráculo**, para nenhum cenário virar teste
do PRD:

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `LoginSocialController` como nome de classe | escolha de implementação — o requisito não nomeia classe | detalhe do cenário. O oráculo de R13 é o **canal**, o **nível**, a presença de `motivo`, o provedor nomeado e a máscara; o prefixo `[Classe@Método]` é padrão de casa (o kit inteiro o usa), não do PRD |
| `ConfiguracaoDoLogin::disponivel()` / `disponiveis()` | nome de método | detalhe. **Só CT-02** se prende ao nome; todos os outros observam por HTTP |
| `auth.social.redirect` / `auth.social.callback` como nome de rota | escolha de implementação, e o ADR-02 registra que ninguém consome o nome | não asserido. Os cenários usam **URL literal**, como a wiki ancestral |
| `propriedadeDeSettings()`, `secaoDoProvedor()`, `segredoGuardadoDe()` | nomes de método | detalhe |
| Nomes das 9 propriedades de Settings (`login_linkedin_openid_client_secret`) | só o PRD os determina | detalhe **do arranjo**. O oráculo de CT-35 é que cada propriedade alcance **a chave de config dela** — isso RQ-06/RQ-09 determinam |
| Textos de recusa devolvidos ao usuário ("Não foi possível concluir a entrada com o {rotulo}.") | comportamento **visível** que o requisito não determina | **pergunta** (A-04 abaixo). Nenhum cenário asserta essas strings |
| Slugs de `motivo` (`falha_no_provedor`, `email_ausente`, `email_nao_verificado`, `conta_inexistente_registro_fechado`, `github_emails_indisponivel`) | só o PRD os determina | asseridos **como contrato de log já estabelecido**: a wiki ancestral fixou `conta_inexistente_registro_fechado` em CT-20 e ele está em produção. Renomear um slug renomeia o cenário — e é assim que se sabe que a auditoria mudou de vocabulário |
| `data-provedor` / qualquer marcador por provedor no SVG | **não existe nem no PRD** | **pergunta bloqueante** (A-01). CT-08 é `@premissa` |
| Teto de 45 s do navegador, `view:cache`, aquecimento pelo kernel | infra de teste, não comportamento | pré-requisito do `05` |

### Perguntas em aberto

Para colar em `00-requisito.md` → `## Ambiguidades`. **Desvio declarado**: o `00-requisito.md`
não foi editado por esta skill (proibição 8 permite só acrescentar pergunta; a instrução desta
rodada pede que a pergunta volte ao autor). O bloco abaixo está pronto para colagem.

```markdown
- **RQ-03 / o ícone das marcas monocromáticas** — o ícone do Google é provável pelas quatro cores
  da marca (`#EA4335`, `#4285F4`, `#FBBC05`, `#34A853`), e é assim que a wiki ancestral o testa.
  GitHub, LinkedIn e X são monocromáticos em `currentColor` (ADR-08), então **nada no HTML diz
  qual marca aquele `<svg>` desenha** — um Heroicon genérico, ou o ícone do provedor errado,
  passa em qualquer asserção disponível. RQ-03 fica sem oráculo em 3 dos 4 provedores.
  - **Assumido**: cada partial de ícone carrega `data-provedor="{value}"` no `<svg>` (atributo
    inerte, uma palavra por arquivo). CT-08 está marcado `@premissa` e asserta esse marcador.
  - **Se negado**: o oráculo passa a ser um fragmento do `d` do path oficial de cada marca —
    funciona, e quebra quando alguém troca a renderização do mesmo logo. CT-08 muda de oráculo,
    não de existência.

- **RQ-04 / o `redirect` é dado de config sem campo na tela** — o predicado confere as **três**
  chaves (`client_id`, `client_secret`, `redirect`), como o `00` registra. Mas a aba "Login" tem
  **dois** campos por provedor: `redirect` é literal do `config/services.php` e a tela nunca o
  expõe. Então "abre os campos para adicionar os dados de config" (RQ-05) abre dois dos três
  dados.
  - **Assumido**: correto assim — `redirect` é derivado do `APP_URL` e editá-lo pela tela
    permitiria apontar o callback para fora. CT-01 exercita `redirect` vazio por `config()`,
    provando o predicado; nenhum cenário cobra campo na tela.
  - **Se negado**: mais três propriedades de Settings e o ADR-07 muda.

- **RQ-09 / o X autentica com sinal de verificação explicitamente falso** — o ramo do X é
  `filled($doProvedor->getEmail())` (ADR-03), porque a API do X só devolve `confirmed_email`.
  Consequência derivada: uma volta do X cujo payload traga `email_verified => false` **autentica**,
  enquanto a mesma volta pelo Google e pelo LinkedIn é recusada. O nível de garantia passa a
  depender de qual botão a pessoa clicou — que é exatamente o argumento com que o ADR-05 recusou
  o Facebook.
  - **Assumido**: aceito como o ADR-03 decidiu. CT-12 está marcado `@premissa` e afirma os dois
    veredictos no mesmo cenário, para a divergência ficar visível em vez de implícita.
  - **Se negado**: o ramo do X passa a recusar quando o bruto trouxer sinal de verificação falso,
    e CT-12 perde a linha do X.

- **ADR-06 tem um terceiro sintoma que a decisão não lista: a trilha de auditoria** —
  `App\Listeners\AuditarConfiguracoesDoKit:127` decide o mascaramento com
  `in_array($propriedade, ConfiguracoesDoKit::encrypted(), true)`. Com `encrypted()` devolvendo
  só `['mail_password']`, **o `client_secret` do Google vai para `audits.old_values` e
  `audits.new_values` em texto claro** desde a v0.19.2 — visível em `/infra/audits`, que é tela
  de outra permissão. O conserto do ADR-06 corrige isso de graça, e **nada na wiki nomeia o
  efeito**, então nada guardaria a regressão.
  - **Assumido**: é parte do mesmo defeito e o conserto é o mesmo. CT-22 existe por isso.
  - **Se negado**: nada muda no código, e CT-22 vira o caso que documenta a exposição aceita.

- **RQ-09 / o painel de destino de quem entra por login social** — o botão está nas **três** telas
  de login (RQ-03 pede o botão, e o render hook é único, então `/app/login`, `/admin/login` e
  `/infra/login` todos o têm). O requisito não diz para onde a pessoa vai depois. Uma implementação
  que mande todo mundo para `/app` é indistinguível de uma correta em qualquer cenário que não
  afirme o destino — e quem clicou em `/infra/login` acabaria noutro painel.
  - **Assumido**: a pessoa volta para o painel de **onde clicou**. CT-48 assume isso e reprova o
    destino fixo. Exige que o kit guarde o painel de origem entre a ida e a volta do OAuth.
  - **Se negado**: o destino passa a ser o painel do papel da pessoa, e CT-48 muda de oráculo (não
    de existência) — continua sendo preciso um cenário por painel.

- **RQ-07 aponta para um caso que não existe** — a linha de RQ-07 na `## Cobertura do Requisito`
  do PRD diz "coberto por regressão (**CT-R1**)", e `CT-R1` não existe em wiki nenhuma. O caso
  real foi localizado: `tests/Kit/RegistroAbertoTest.php:140`,
  `it('nasce com as tres opcoes de registro desligadas')`. Ele cobre RQ-07 e roda na regressão.
  - **Assumido**: a referência é um resíduo de redação. Nenhum passo do PRD precisa mudar; a
    linha da tabela deve apontar para `tests/Kit/RegistroAbertoTest.php:140`.
  - Nesta wiki, o **lado do login social** do mesmo default é CT-37 (linha do X: sem conta e com
    o registro fechado, recusa com `motivo` próprio).
```

### Onde o requisito e o plano discordam

1. **RQ-07 aponta para um caso inexistente** (`CT-R1`) — acima.
2. **O PRD subestima o conserto da wiki ancestral.** O passo 8 diz "só o que o rename de
   controller e de mensagem exige (**2 asserções de log**)". São 2 asserções de log
   (`tests/Kit/LoginSocialGoogleTest.php:731` e `:758`) **mais duas chamadas a
   `ConfiguracaoDoLogin::googleDisponivel()`** em `it('deixa o settings governar o botao do
   google e o rodape')` (`:~830`), método que o passo 2 do PRD **remove**. Aquele caso não
   compila depois do passo 2, e ele não é caso de log. São **três** pontos de edição, em dois
   assuntos.
3. **`## Superfície de UI` não lista a tela de login como tela de escrita, e ela é a única
   superfície pública da feature.** Não é discordância de comportamento — é a razão de CT-08 e
   CT-28 existirem nos três painéis, exaustivos e não amostrados.

---

## Setup Global

### Personas

- **visitante** — sem sessão. É quem vê a tela de login e quem chega no callback. Nenhum
  `actingAs`.
- **quem já tem conta** — `usuario('ja.tem@example.com')` (`tests/Pest.php`).
- **quem não tem conta** — nenhum registro; o e-mail vem só do provedor. **Usada por CT-45 e
  CT-37.** Na rodada 1 esta persona estava declarada e não era usada por cenário nenhum, e foi
  esse o sintoma que a revisão adversarial usou para achar a lacuna inteira de R18.
- **duas contas parecidas** — `ja.tem@example.com` **e** `ja_tem@example.com`, no mesmo banco.
  Existe só para CT-47, e existe porque nenhum cenário da rodada 1 tinha mais de uma conta: com
  uma conta só, um `where('email','like',$email)` é indistinguível de `where('email',$email)`.
- **administrador da instalação** — `usuarioDoKit('admin')` depois de
  `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`. É quem opera
  `/admin/configuracoes-do-kit`.

### Fixtures e helpers

Dois helpers novos, e os dois vão para **`tests/Pest.php`**, porque três arquivos os usam
(`tests/Kit/LoginSocialProvedoresTest.php`, `tests/Kit/SegredosDoSettingsTest.php` e
`tests/Tenancy/LoginSocialProvedoresTenancyTest.php`) — `.ai/rules/testes.md` §"Helper de teste
usado por mais de um arquivo". Declarar num arquivo e usar noutro estoura
`Call to undefined function` sob `--parallel`, `--tia` e arquivo isolado.

```php
/** Liga um provedor social para o caso corrente, com as três chaves preenchidas. */
function ligarProvedor(ProvedorSocial $provedor, array $credenciais = []): void

/** O usuário que aquele provedor devolveria, com o bruto que ELE povoa. */
function usuarioSocialFalso(ProvedorSocial $provedor, array $atributos = []): SocialiteUser
```

`usuarioSocialFalso()` existe porque **o bruto muda de provedor para provedor** (ADR-03) e um
`User::fake()` genérico esconderia a única coisa que varia:

| Provedor | Bruto que o helper monta |
|---|---|
| `google` | `email_verified => true` |
| `linkedin-openid` | `email_verified => true` |
| `x` | só `email` — a presença **é** a prova |
| `github` | nada de verificação; quem prova é o `Http::fake()` de `/user/emails` |

**⚠️ `User::fake()` sozinho NÃO serve, e é o achado mais afiado da revisão adversarial.**
`Laravel\Socialite\Two\User::fake()` faz `setRaw($attributes)` **e** `map($attributes)`
(`vendor/laravel/socialite/src/Two/User.php:44-63`). Ou seja: o duplo popula o **bruto** e o
**atributo**, e por isso uma implementação que leia `$doProvedor->email_verified` (o atributo) em
vez de `getRaw()` fica **verde em todo cenário faked** — e em produção recusa **todo** login de
Google, porque o `GoogleProvider` real não mapeia essa propriedade
(`AbstractUser::map()` só atribui quando `property_exists`,
`vendor/laravel/socialite/src/AbstractUser.php:138-149`). É o mutante M34, que a rodada 1 desta
derivação declarou morto e **não estava**.

Por isso `usuarioSocialFalso()` **não** usa `User::fake()` para o campo de verificação:

```php
/**
 * O usuário do provedor com o campo de verificação SÓ no bruto — como o driver real entrega.
 *
 * `setRaw()` sem `map()` no campo de verificação, de propósito: `User::fake()` popula as duas
 * pontas e por isso não distingue `getRaw()` de leitura de atributo (M34).
 */
function usuarioSocialFalso(ProvedorSocial $provedor, array $bruto = [], array $mapeados = []): SocialiteUser
```

`token = 'fake-token'` continua vindo do `fake()`/`setToken()` — é esse token que o
`Http::assertSent()` de CT-17 confere.

### Os dois veredictos, definidos uma vez

CT-10, CT-11, CT-15 e CT-45 usam a coluna `veredicto` nas tabelas de `Exemplos:`. **`autentica` e
`recusa` não são oráculos** — são apelidos para estes dois conjuntos de asserções, e é aqui que
eles ficam escritos. Um `Então o veredicto é "recusa"` sem esta seção seria oráculo sem valor
concreto, e foi achado da revisão adversarial.

| Apelido | O que o `Então` afirma, sempre |
|---|---|
| **autentica** | a conta autenticada é **a de `<e-mail>`** (`assertAuthenticatedAs`, não `assertAuthenticated`) · o total de contas **não mudou** · o destino do 302 é o painel de origem · o channel `autenticacao` recebeu `info` |
| **recusa** | ninguém autenticado (`assertGuest`) · o total de contas **não mudou** · 302 para `/{painel de origem}/login` · o channel recebeu `warning` com `motivo` · o channel **não** recebeu `info` de sucesso |

As duas linhas "o total de contas não mudou" e "não recebeu `info`" são o que separa recusa de
**recusa depois de gravar** e de **recusa que segue para o sucesso** (o `return` esquecido).

### Fakes

- `Socialite::fake($provedor->value, usuarioSocialFalso($provedor))` — API oficial do pacote.
  **Nenhum caso sai para a rede.**
- `Http::fake([...])` para `api.github.com/user/emails`, **sempre** acompanhado de
  `Http::preventStrayRequests()`: `Http::fake()` sem stub devolve 200 vazio e o cenário passa
  sem provar nada.
- **Registrado, porque não é intuitivo**: `Http::preventStrayRequests()` **não** protege as
  chamadas do Socialite — ele usa o Guzzle dele (`getHttpClient()`), não a facade `Http`
  (nota já escrita em `tests/Kit/LoginSocialGoogleTest.php`, CT-05). Protege só a chamada do
  kit a `/user/emails`, que é a única que passa pela facade.
- `espiarAutenticacao()` (`tests/Pest.php`) para R13 — espia só o channel `autenticacao`.

### Estratégia de DB

`RefreshDatabase` global, ligado em `tests/Pest.php` para `Kit`, `Tenancy` e `Browser`.
`tests/Kit` roda **single-tenant**; R17 vai para `tests/Tenancy` porque `Tests\TenancyTestCase`
fixa `permission.teams` em `createApplication()`, antes das migrations
(`.ai/rules/testes.md`).

### Arquivos de teste

| Arquivo | Regras |
|---|---|
| `tests/Kit/LoginSocialProvedoresTest.php` (novo) | R1–R7, R13, R14, R15, R16 |
| `tests/Kit/SegredosDoSettingsTest.php` (novo) | R8, R9, **R10, R11, R12** |
| `tests/Tenancy/LoginSocialProvedoresTenancyTest.php` (novo) | R17 |
| `tests/Kit/LoginSocialGoogleTest.php` (**existente**) | regressão — ver `## Regressão Obrigatória` |

**Três arquivos, não quatro — e a razão é uma rule, não gosto.** R10, R11 e R12 são casos de tela
e o instinto é criar um quarto arquivo, `tests/Kit/ConfiguracoesDoKitLoginSocialTest.php`, para
eles. Isso colide com
`.ai/rules/testes.md`: eles precisam do valor **gravado na tabela**, e o helper que faz isso —
`configuracaoGravada()` — **já existe**, declarado localmente em
`tests/Kit/ConfiguracoesDoKitTelaTest.php:36`. Um arquivo novo teria de escolher entre um clone
com outro nome (que a rule proíbe por nome: "troca um erro que estoura por duas funções idênticas
que ninguém percebe") e uma redeclaração fatal.

**Consequência obrigatória para quem implementar**: ao usar `configuracaoGravada()` num segundo
arquivo, **mova-a para `tests/Pest.php`** e apague a declaração local — não crie a segunda. O
guarda disso é `tests/Kit/HelpersDeTesteTest.php`, que usa `token_get_all()` e vai acusar.

R8–R12 no mesmo arquivo também é o agrupamento certo por assunto: são os **quatro segredos** e as
**quatro seções**, o mesmo eixo do enum.

---

## Regra R1 — o botão aparece se e somente se o interruptor está ligado E as três credenciais estão preenchidas

> RQ-04, RQ-08, RQ-09 · perfil **completo** · técnica: **tabela de decisão × EP por provedor**
> (2 condições, a segunda com 3 sub-chaves; fronteira de string: ausente × vazio × só-espaços)

```gherkin
# language: pt

Funcionalidade: mais provedores de login social

  Regra: o botão de um provedor aparece se e somente se o interruptor dele está ligado e as três chaves de config dele estão preenchidas

    Esquema do Cenário: [CT-01] a tabela de decisão da exibição do botão, provedor por provedor
      Dado o kit no arranjo "<arranjo>" para o provedor "<provedor>"
      Quando o visitante abre a tela de login do painel /app
      Então o botão "Entrar com <rotulo>" <presenca> na tela

      Exemplos:
        | provedor        | rotulo   | arranjo                              | presenca      | # partição                    |
        | google          | Google   | de fábrica                           | não aparece   | default, mede o default real  |
        | github          | GitHub   | de fábrica                           | não aparece   | default, mede o default real  |
        | linkedin-openid | LinkedIn | de fábrica                           | não aparece   | default, mede o default real  |
        | x               | X        | de fábrica                           | não aparece   | default, mede o default real  |
        | google          | Google   | client_secret vazio                  | não aparece   | a chave que ninguém lembra    |
        | github          | GitHub   | client_secret vazio                  | não aparece   | a chave que ninguém lembra    |
        | linkedin-openid | LinkedIn | client_secret vazio                  | não aparece   | a chave que ninguém lembra    |
        | x               | X        | client_secret vazio                  | não aparece   | a chave que ninguém lembra    |
        | google          | Google   | completo                             | aparece       | célula válida                 |
        | github          | GitHub   | completo                             | aparece       | célula válida                 |
        | linkedin-openid | LinkedIn | completo                             | aparece       | célula válida                 |
        | x               | X        | completo                             | aparece       | célula válida                 |
        | github          | GitHub   | interruptor desligado, três chaves   | não aparece   | condição 1 sozinha            |
        | linkedin-openid | LinkedIn | client_id vazio                      | não aparece   | segunda sub-chave             |
        | x               | X        | redirect vazio                       | não aparece   | terceira sub-chave            |
        | github          | GitHub   | client_secret só com espaços         | não aparece   | fronteira de string           |

    Cenário: [CT-02] a lista de provedores disponíveis traz exatamente os completos
      Dado que o GitHub e o X estão ligados com as três chaves preenchidas
      E que o Google está ligado com o client_secret vazio
      E que o LinkedIn está desligado com as três chaves preenchidas
      Quando o kit é perguntado quais provedores estão disponíveis
      Então a resposta é exatamente GitHub e X, nessa ordem
```

**Camada**: CT-01 `Feature` (HTTP, `GET /app/login`) — o oráculo é o HTML, e ele mora na tela.
CT-02 `Feature` (predicado + `config`) — é o único cenário preso a nome de método.

**Notas de arranjo, que mudam o resultado:**

- A linha "de fábrica" é a única que mede o **default de verdade**: ela não chama
  `ligarProvedor()`, e as quatro chaves `KIT_SOCIALITE_*` **não** estão no `phpunit.xml`
  (conferido: `grep -n "SOCIALITE" phpunit.xml` não devolve nada). Fixá-las no cenário mediria o
  `phpunit.xml`.
- "client_secret vazio" é **string vazia**, não ausente. É o que um `.env` meio preenchido deixa,
  e `isset()` passa por ele (`.ai/rules/config.md`).
- A ordem em CT-02 é o oráculo de "na ordem do enum" — sem ela, um `array_filter()` que devolve
  o array com as chaves originais e um que devolve `array_values()` são indistinguíveis, e o
  primeiro quebra o laço do blade.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o predicado confere `client_id` e `client_secret` e esquece o `redirect` | CT-01 (linha `redirect vazio`) |
| M2 | o predicado confere `client_id` e `redirect` e esquece o `client_secret` | CT-01 (as 4 linhas `client_secret vazio`) |
| M3 | `isset()` no lugar de `filled()` — valor vazio conta como preenchido | CT-01 (linhas de vazio e de só-espaços) |
| M4 | o interruptor é ignorado: credencial preenchida sozinha põe o botão no ar | CT-01 (linha `interruptor desligado, três chaves`) |
| M5 | o default do interruptor é `true` | CT-01 (as 4 linhas `de fábrica`) |
| M6 | `disponiveis()` devolve `ProvedorSocial::cases()` inteiro | CT-02 |
| M7 | `disponiveis()` devolve o array filtrado **sem** `array_values()` | CT-02 (a ordem/identidade da lista) |

---

## Regra R2 — indisponível, as duas rotas respondem 404; disponível, as duas ficam no ar

> RQ-09 · perfil **completo** · técnica: **tabela estado × operação, 100% das células**

Esconder o botão não é barreira: a URL é fixa, pública e conhecida. A matriz é
`{disponível, indisponível} × {redirect, callback} × 4 provedores`, e **nenhuma célula é
colapsada** — o mutante clássico é pôr o guarda no `redirect`, onde quem escreve está pensando
no botão, e esquecer o `callback`.

```gherkin
  Regra: enquanto um provedor está indisponível, as duas rotas de OAuth dele respondem 404

    Esquema do Cenário: [CT-03] as células inválidas da matriz, nas duas rotas e nos quatro provedores
      Dado o provedor "<provedor>" indisponível por "<motivo>"
      Quando o visitante abre "<rota>"
      Então a resposta é 404
      E ninguém está autenticado

      Exemplos:
        | provedor        | motivo               | rota                                    |
        | google          | interruptor desligado| /auth/google/redirect                   |
        | google          | interruptor desligado| /auth/google/callback                   |
        | github          | interruptor desligado| /auth/github/redirect                   |
        | github          | interruptor desligado| /auth/github/callback                   |
        | linkedin-openid | interruptor desligado| /auth/linkedin-openid/redirect          |
        | linkedin-openid | interruptor desligado| /auth/linkedin-openid/callback          |
        | x               | interruptor desligado| /auth/x/redirect                        |
        | x               | interruptor desligado| /auth/x/callback                        |
        | google          | client_secret vazio  | /auth/google/redirect                   |
        | google          | client_secret vazio  | /auth/google/callback                   |
        | github          | client_secret vazio  | /auth/github/redirect                   |
        | github          | client_secret vazio  | /auth/github/callback                   |
        | linkedin-openid | client_secret vazio  | /auth/linkedin-openid/redirect          |
        | linkedin-openid | client_secret vazio  | /auth/linkedin-openid/callback          |
        | x               | client_secret vazio  | /auth/x/redirect                        |
        | x               | client_secret vazio  | /auth/x/callback                        |

  Regra: enquanto um provedor está disponível, as duas rotas de OAuth dele ficam no ar

    Esquema do Cenário: [CT-04] a rota de ida manda a pessoa para O provedor dela
      Dado o provedor "<provedor>" ligado, com client_id "id-de-<provedor>" e as outras duas chaves preenchidas
      E que NENHUM provedor está falsificado
      Quando o visitante abre "/auth/<provedor>/redirect"
      Então a resposta é 302
      E o destino começa com "<host de autorização>"
      E o destino traz o parâmetro client_id igual a "id-de-<provedor>"
      E o destino traz o parâmetro redirect_uri terminando em "/auth/<provedor>/callback"

      Exemplos:
        | provedor        | host de autorização                             | # lido no vendor                    |
        | google          | https://accounts.google.com/o/oauth2/auth       | `GoogleProvider.php:36`             |
        | github          | https://github.com/login/oauth/authorize        | `GithubProvider.php:23`             |
        | linkedin-openid | https://www.linkedin.com/oauth/v2/authorization | `LinkedInOpenIdProvider.php:28`     |
        | x               | https://x.com/i/oauth2/authorize                | `XProvider.php:15`                  |

> As quatro URLs foram **lidas** em `vendor/laravel/socialite/src/Two/`, não presumidas
> (`.ai/rules/specs.md`). A do X é a discriminante do conjunto: o `TwitterProvider` pai usa
> `https://twitter.com/i/oauth2/authorize` (`TwitterProvider.php:43`) e o `XProvider` sobrescreve
> só as URLs (`:15`) — então esta linha é a que reprova o kit oferecendo o driver `twitter` no
> lugar do `x`, que o ADR-09 item 6 recusou.

    Esquema do Cenário: [CT-04b] a rota de volta trata a recusa em vez de estourar
      Dado o provedor "<provedor>" ligado com as três chaves preenchidas
      Quando o visitante abre "/auth/<provedor>/callback" sem code e sem state
      Então a resposta é 302 para "/app/login"
      E ninguém está autenticado

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |
```

**Achado da revisão adversarial, fechado aqui.** A versão da rodada 1 asseverava
`destino = socialite.fake` nas quatro linhas de ida, **com `Socialite::fake()` no arranjo** — e
esse valor é o mesmo para a implementação certa e para
`Socialite::driver('google')->redirect()` com o parâmetro usado só no guarda e no log, porque o
`FakeProvider` não depende do driver pedido. Os quatro botões mandariam todo mundo para o Google e
CT-04 ficaria verde.

**Por isso CT-04 é o segundo cenário do conjunto que roda SEM `Socialite::fake()`** — o primeiro é
CT-05 da wiki ancestral, pelo motivo simétrico (o fake ignora o `state`). E **também não toca a
rede**: `AbstractProvider::redirect()` só monta a URL de autorização e devolve um
`RedirectResponse` (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83`), sem um único byte
de requisição. Quem sai para a rede é `user()`, que este cenário não chama.

O `client_id` no `Então` é o que mata a variante mais sutil: driver certo, **credencial de outro
provedor** — que é o defeito que o ADR-01 alternativa 3 descreve (gravar em `services.linkedin` e
ler em `services.linkedin-openid`) chegando por outra porta.

**Camada**: `Feature` (HTTP). A barreira é `abort_unless()` no controller, e só o request a
exercita.

**Nota**: a linha do `callback` de CT-04 chega **sem `code` e sem `state`**, então o resultado
esperado é a **recusa tratada** — 302 para a tela de login, e não 500. É ela que prova que o
`catch` existe em vez de deixar a exceção do Socialite vazar. Mesma manobra da wiki ancestral
(CT-03 de lá).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | o `abort_unless` está só no `redirecionar()` | CT-03 (as 8 linhas de `callback`) |
| M9 | o `abort_unless` está só no `retorno()` | CT-03 (as 8 linhas de `redirect`) |
| M10 | o guarda usa `abort_if` (condição negada) e derruba a rota **sempre** | CT-04 (as 8 células válidas) |
| M11 | o guarda confere o interruptor e não as credenciais | CT-03 (as 8 linhas `client_secret vazio`) |
| M12 | o guarda confere um provedor fixo (o do primeiro caso escrito) em vez do parâmetro | **CT-04 e CT-04b** nas linhas dos outros três. **Corrigido na revisão**: a rodada 1 citava também CT-03, que **não** mata — no default tudo está indisponível, então um guarda fixo no Google devolve 404 nas 16 linhas |
| M12b | o **driver** é fixo, e o parâmetro entra só no guarda e no log — os quatro botões vão para o Google | **CT-04** (o host de autorização por linha). Nenhum cenário da rodada 1 o matava, porque o oráculo era `socialite.fake` nas quatro |
| M13 | a exceção do Socialite no callback sem `state` vaza como 500 | CT-04 (linhas de `callback`) |
| M14 | as rotas são registradas dentro de um `if` do interruptor — indisponível vira rota inexistente, não guarda | **CT-41 (última linha)**. ⚠️ **Citação corrigida na revisão**: a rodada 1 citava CT-03 e CT-42, e nenhum dos dois mata — rota não registrada devolve 404 igual ao guarda, e CT-42 é presença de termo em arquivo, não `route:list` |

---

## Regra R3 — o estado de um provedor não altera o de nenhum outro

> RQ-09 (a leitura simétrica que o `00` registra), RQ-02 · perfil **completo** ·
> técnica: **isolamento / matriz provedor × provedor**

```gherkin
  Regra: ligar um provedor liga só aquele, e desligar um não derruba os outros

    Cenário: [CT-05] ligar só o GitHub não põe nenhum outro provedor no ar
      Dado que só o GitHub está ligado com as três chaves preenchidas
      E que o Google, o LinkedIn e o X estão desligados, cada um com as três chaves preenchidas
      Quando o visitante abre a tela de login do painel /app
      Então a tela traz o botão "Entrar com GitHub"
      E não traz "Entrar com Google", "Entrar com LinkedIn" nem "Entrar com X"
      E /auth/github/redirect responde 302
      E /auth/google/redirect, /auth/linkedin-openid/redirect e /auth/x/redirect respondem 404

    Cenário: [CT-06] desligar o Google não derruba o GitHub
      Dado que o Google e o GitHub estão ligados com as três chaves preenchidas
      Quando o interruptor do Google é desligado
      Então a tela de login continua trazendo o botão "Entrar com GitHub"
      E /auth/github/redirect continua respondendo 302
      E /auth/google/redirect passa a responder 404
```

**Camada**: `Feature` (HTTP). Dois cenários e não um `Esquema` porque as direções são diferentes:
CT-05 é "ligar não vaza", CT-06 é "desligar não arrasta".

**Nota sobre o `Quando` único**: em CT-05 e CT-06 o `Quando` é a **mudança de estado**; as
verificações de rota do `Então` são leituras, não ações. Elas não introduzem segundo `Quando`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o predicado lê `config('kit.login.google.habilitado')` fixo em vez de `{$provedor->value}` | CT-05 (o GitHub ligado não apareceria) e CT-06 |
| M16 | o interruptor é único para o login social inteiro, e não por provedor | CT-05 (os outros três apareceriam) |
| M17 | `disponiveis()` devolve todos assim que **um** está completo | CT-05 |
| M18 | o blade cacheia a lista de disponíveis entre requests | CT-06 (o Google continuaria na tela depois de desligado) |
| M19 | a chave de config do LinkedIn é escrita `linkedin` e lida `linkedin-openid` (a alternativa 3 recusada no ADR-01) | CT-05 (o LinkedIn desligado com credenciais responderia 302) e CT-35 |

---

## Regra R4 — provedor fora do enum responde 404, mesmo com credenciais em config

> RQ-01 + ADR-04 (Discord sem driver) e ADR-05 (Facebook sem prova de verificação) ·
> perfil **padrão** · técnica: **EP das partições inválidas, uma por linha**

Este é o cenário que **prova** que Facebook e Discord estão fora — e o arranjo é deliberadamente
o mais favorável possível ao mutante: interruptor ligado **e** três credenciais preenchidas em
`config`. `config()->set()` aceita **qualquer** chave (`.ai/rules/config.md`), então o arranjo é
realizável; o enum é o que não aceita.

```gherkin
  Regra: um segmento de provedor que não é caso do enum responde 404, mesmo com interruptor e credenciais em config

    Esquema do Cenário: [CT-07] as partições inválidas do parâmetro de provedor
      Dado que "kit.login.<segmento>.habilitado" está ligado
      E que "services.<segmento>" tem as três chaves preenchidas
      Quando o visitante abre "/auth/<segmento>/<rota>"
      Então a resposta é 404
      E ninguém está autenticado

      Exemplos:
        | segmento  | rota     | # por que esta linha existe                               |
        | facebook  | redirect | ADR-05: recusado por não provar e-mail verificado         |
        | facebook  | callback | a mesma recusa na rota de volta                           |
        | discord   | redirect | ADR-04: não é driver do Socialite                         |
        | discord   | callback | a mesma recusa na rota de volta                           |
        | twitter   | redirect | ADR-09 item 6: OAuth 1.0 não põe e-mail no bruto          |
        | linkedin  | redirect | o driver legado, sem campo de verificação (Ambiguidades)  |
        | slack     | redirect | driver que o Socialite TEM e o kit não oferece            |
```

**As duas linhas de normalização de caixa exigem arranjo OPOSTO, e é achado da revisão
adversarial.** Na rodada 1 elas viviam na tabela acima, ligando `kit.login.GOOGLE.*` — e ali são
**decorativas**: uma implementação que faça `strtolower()` antes de resolver o enum cai em
`google`, que está **desligado por default**, e responde 404 igual à implementação certa. O
mutante M23 ficava vivo com a linha marcada ✅.

```gherkin
    Esquema do Cenário: [CT-07b] o segmento não é normalizado antes de resolver o enum
      Dado que o provedor google está LIGADO com as três chaves preenchidas
      Quando o visitante abre "/auth/<segmento>/redirect"
      Então a resposta é 404
      E ninguém está autenticado

      Exemplos:
        | segmento  | # o que a normalização faria                                  |
        | GOOGLE    | `strtolower()` cairia em google, que está no ar → 302         |
        | Google    | caixa mista, o mesmo                                          |
        | google%20 | `trim()` cairia em google → 302                               |
        | " google" | espaço à esquerda, o mesmo                                    |
```

O arranjo com o `google` **ligado** é o que torna as linhas discriminantes: agora a implementação
que normaliza responde **302** e a que não normaliza responde **404**. É a mesma manobra que faz as
linhas `facebook` e `discord` de CT-07 funcionarem — arranjo favorável ao mutante.

**Camada**: `Feature` (HTTP). O 404 vem do **implicit enum binding** do roteador (ADR-02), antes
de qualquer código do kit rodar — e é justamente isso que o cenário prova.

**Nota**: a linha `slack` é a discriminante do conjunto. `facebook`, `discord` e `twitter` também
morreriam num `match` com lista branca escrita à mão; `slack` é um driver **existente e válido**
do Socialite que o kit não oferece, e ele só é recusado se a lista for o **enum**. `GOOGLE` mata
"casa o segmento com `strtolower()` antes de resolver o enum".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | o parâmetro é `string` e o controller faz `Socialite::driver($provedor)` — segmento livre vira driver | CT-07 (todas as linhas: `facebook` responderia 302) |
| M21 | o parâmetro é `?ProvedorSocial` (nullable) — o binding deixa passar e o guarda decide | CT-07 |
| M22 | `ProvedorSocial::tryFrom()` no controller, devolvendo recusa 302 em vez de 404 | CT-07 (o oráculo é **404**) |
| M23 | o segmento é normalizado com `strtolower()`/`trim()` antes de resolver o enum | **CT-07b** (as quatro linhas, com o `google` LIGADO no arranjo). ⚠️ **Corrigido na revisão**: a linha `GOOGLE` de CT-07 não matava, porque no arranjo dela o `google` estava desligado e a normalização caía em 404 igual |
| M24 | o enum ganhou `Facebook` ou `Discord` "para não deixar RQ-01 incompleto" | CT-07 (linhas `facebook` e `discord`) |

---

## Regra R5 — cada botão traz o ícone da marca daquele provedor e o href da rota dele

> RQ-03 · perfil **padrão** · técnica: **EP exaustiva do enum × painéis**

```gherkin
  Regra: cada botão de provedor traz o ícone da marca dele e aponta para a rota dele, depois do formulário, nos três painéis

    @premissa
    Esquema do Cenário: [CT-08] o botão do provedor, com o ícone dele e não o de outro
      Dado que só o provedor "<provedor>" está ligado com as três chaves preenchidas
      Quando o visitante abre a tela de login do painel "<painel>"
      Então "Entrar com <rotulo>" aparece depois do campo de senha
      E o botão aponta para "/auth/<provedor>/redirect"
      E a tela traz o marcador de ícone de "<provedor>"
      E não traz o marcador de ícone de nenhum dos outros três provedores

      Exemplos:
        | provedor        | rotulo   | painel |
        | google          | Google   | app    |
        | google          | Google   | admin  |
        | google          | Google   | infra  |
        | github          | GitHub   | app    |
        | github          | GitHub   | admin  |
        | github          | GitHub   | infra  |
        | linkedin-openid | LinkedIn | app    |
        | linkedin-openid | LinkedIn | admin  |
        | linkedin-openid | LinkedIn | infra  |
        | x               | X        | app    |
        | x               | X        | admin  |
        | x               | X        | infra  |

    Cenário: [CT-09] com nenhum provedor disponível, o divisor "ou" também não aparece
      Dado que os quatro provedores estão desligados
      Quando o visitante abre a tela de login do painel /app
      Então a tela responde 200
      E não traz nenhum botão de provedor
      E não traz o divisor "ou"
```

**Camada**: `Feature` (HTTP). Presença, ordem no DOM, `href` e marcador se provam no HTML — ~40×
mais barato que navegador, e é o que a wiki ancestral já faz. O que **só** o navegador prova
(o botão de fato **visível**) é CT-B01.

**Notas:**

- `@premissa` em CT-08: o "marcador de ícone" é a pergunta **A-01** acima. Assumido
  `data-provedor="{value}"` no `<svg>` de cada partial. Sem ele, RQ-03 não tem oráculo em 3 dos
  4 provedores, e o oráculo cai para um fragmento do `d` do path oficial de cada marca.
- Para o Google, o marcador **redundante** já existente (as quatro cores da marca) continua
  asserido pela wiki ancestral (CT-04 de lá) — é a linha de controle que prova que o marcador
  novo não substituiu o ícone real por um placeholder.
- O arranjo de "só um provedor ligado" é o que torna a cruz-negativa expressável: com os quatro
  na tela, "não traz o marcador do outro" é falso por construção.
- Os três painéis, **exaustivos e não amostrados**: o defeito histórico do kit nesta área é
  configurar um painel e esquecer os outros dois, e a registração do render hook é única e sem
  escopo. Este cenário é o que reprova alguém "consertando" para escopo de painel.
- CT-09 é o contrapeso do laço: `@if ($provedores !== [])` errado deixa um divisor "ou" solto
  numa tela sem botão nenhum — cosmético, mas é a tela por onde todo mundo entra.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | o blade inclui sempre a mesma partial (a do primeiro provedor, ou uma genérica) | CT-08 (a cruz-negativa em 9 das 12 linhas) |
| M26 | o `href` é montado com um provedor fixo, ou com o `rotulo()` em vez do `value` | CT-08 (o `href` por linha) |
| M27 | o botão é renderizado **antes** do formulário | CT-08 (a ordem; `assertSee` sozinho ficaria verde) |
| M28 | o render hook ganhou escopo de painel e alcança só o `/app` | CT-08 (as linhas `admin` e `infra`) |
| M29 | o divisor "ou" é renderizado fora do `@if` | CT-09 |
| M30 | o `rotulo()` do LinkedIn devolve `linkedin-openid` (o `value` cru) na tela | CT-08 (o texto `Entrar com LinkedIn`) |
| M31 | o laço não escapa o rótulo e o marcador entra no HTML como texto | CT-08 (o `href` casaria e o marcador não) |

---

## Regra R6 — o login só autentica quando aquele provedor prova que o e-mail está verificado

> RQ-09 + ADR-03 · perfil **completo** · técnica: **tabela de decisão provedor × prova**,
> mais **partição inválida isolada** e **idempotência ancorada no agregado**

A prova é diferente em cada provedor, e é este o eixo que o ADR-01 identificou como o único que
varia. Por isso a técnica é a matriz `provedor × forma da prova`, e **não** uma partição de um
campo só.

```gherkin
  Regra: o login pelo LinkedIn OpenID autentica somente com email_verified verdadeiro no bruto

    Esquema do Cenário: [CT-10] a partição exaustiva do valor de verificação do LinkedIn
      Dado que o LinkedIn está ligado com as três chaves preenchidas
      E que existe conta para "vitima@example.com"
      E que o LinkedIn devolve o bruto "<bruto>" para esse e-mail
      Quando o visitante chega no callback do LinkedIn
      Então o veredicto é "<veredicto>"

      Exemplos:
        | bruto                       | veredicto  | # partição                        |
        | email_verified = true       | autentica  | válida                            |
        | email_verified = false      | recusa     | a tomada de conta                 |
        | campo ausente               | recusa     | falha fechada                     |
        | email_verified = "false"    | recusa     | discriminante: (bool) "false" é true |
        | email_verified = "0"        | recusa     | discriminante                     |
        | email_verified = 1          | autentica  | inteiro verdadeiro do JSON        |
        | email_verified = "true"     | autentica  | string verdadeira do JSON         |
        | true SÓ no bruto            | autentica  | **mata M34** — é onde o driver real põe |
        | true SÓ no atributo mapeado | recusa     | **mata M34** — bruto vazio é falha fechada |

  Regra: no X, a presença do e-mail é a prova — porque é a única que o X devolve

    @premissa
    Esquema do Cenário: [CT-11] o X decide pela presença do e-mail, e por nada mais
      Dado que o X está ligado com as três chaves preenchidas
      E que existe conta para "vitima@example.com"
      E que o X devolve "<payload>"
      Quando o visitante chega no callback do X
      Então o veredicto é "<veredicto>"

      Exemplos:
        | payload                                        | veredicto  | # o que prova                                |
        | e-mail preenchido, sem campo de verificação    | autentica  | a presença basta (ADR-03)                    |
        | e-mail nulo                                    | recusa     | sem e-mail não há prova nenhuma              |
        | e-mail só com espaços                          | recusa     | fronteira de string, `filled()` e não `isset` |
        | e-mail preenchido e email_verified = false     | autentica  | @premissa: o ramo do X não lê esse campo     |

    @premissa
    Esquema do Cenário: [CT-12] o mesmo payload recebe veredictos diferentes em provedores diferentes
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E que existe conta para "vitima@example.com"
      E que ninguém está autenticado
      E que o provedor devolve e-mail preenchido e email_verified FALSO no bruto
      Quando o visitante chega no callback desse provedor
      Então o veredicto é "<veredicto>"

      Exemplos:
        | provedor        | veredicto  | # por que este veredicto                        |
        | google          | recusa     | a régua do Google é o booleano do bruto         |
        | linkedin-openid | recusa     | a régua do LinkedIn é o booleano do bruto       |
        | x               | autentica  | @premissa: o ramo do X não lê esse campo (ADR-03) |

  Regra: provedor que não devolve e-mail é recusado, em qualquer provedor

    Esquema do Cenário: [CT-13] o e-mail ausente é partição inválida própria
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      E que o provedor não devolve e-mail
      Quando o visitante chega no callback desse provedor
      Então a resposta é 302 para "/app/login"
      E ninguém está autenticado
      E o total de contas continua sendo 1
      E nenhuma requisição saiu para "api.github.com"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

  Regra: a mesma volta duas vezes não produz uma segunda conta

    Esquema do Cenário: [CT-14] idempotência do callback, ancorada no total de contas
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      Quando o visitante chega duas vezes no callback desse provedor com o mesmo e-mail
      Então a conta autenticada é a de "ja.tem@example.com"
      E o total de contas continua sendo 1
      E o total de linhas de trilha de acesso desse usuário é 2

      Exemplos:
        | provedor        |
        | github          |
        | linkedin-openid |
        | x               |
```

**Camada**: `Feature` (HTTP no callback) — o `Então` afirma sobre autenticação e sobre registro
gravado, e nenhum dos dois é observável abaixo do request.

**Notas:**

- **O Google não tem cenário próprio de partição aqui**: a wiki ancestral já o cobre exaustivo
  (CT-11 de `tests/Kit/LoginSocialGoogleTest.php`, com as seis linhas incluindo o alias
  `verified_email`). Duplicar seria cenário que mata os mesmos mutantes. Registrado em
  `## Cogitado e Cortado` e em `## Regressão Obrigatória`.
- CT-10 tem duas linhas **verdadeiras** (`1` e `"true"`) além de `true`. Elas existem porque o
  bruto vem de JSON e `filter_var(..., FILTER_VALIDATE_BOOLEAN)` precisa aceitar as três — um
  `=== true` estrito recusaria todo login de LinkedIn, e o sintoma seria "o provedor nunca deixa
  ninguém entrar" (o defeito que o ADR-03 nomeia).
- CT-11, última linha, é `@premissa` (pergunta A-03). É deliberadamente a linha mais
  desconfortável do conjunto: se a resposta do dono do kit for "o X também recusa sinal falso",
  esta linha inverte e o ramo do X muda. Escrevê-la agora é o que impede a divergência de passar
  em silêncio.
- CT-12 é o cenário que prova que o `match` do ADR-03 **tem ramos diferentes**. Sem ele, um
  `match` com um ramo único (a régua do Google para todos, ou `true` para todos) fica verde em
  CT-10 **ou** em CT-11, nunca reprovado pelos dois.
- CT-14 ancora no **agregado persistido** (total de contas + trilha de acesso), não no retorno da
  chamada. A terceira linha do `Então` — duas linhas de trilha — é o contrapeso: sem ela, uma
  implementação que ignore o segundo callback inteiro (um `return` antecipado por "já
  autenticado") passaria, e o kit perderia o registro do segundo acesso.
- CT-14 exclui o Google porque a wiki ancestral já tem o caso (CT-23 de lá). Inclui o GitHub
  porque **lá há uma chamada HTTP a mais**, e "duas voltas, duas chamadas, uma conta" é
  informação nova.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | cast de bool no lugar de `filter_var` — `"false"` vira `true` | CT-10 (linhas `"false"` e `"0"`) |
| M33 | `=== true` estrito — recusa `1` e `"true"` do JSON | CT-10 (as duas linhas verdadeiras não-booleanas) |
| M34 | o valor é lido de `$user->email_verified` (atributo mapeado) em vez de `getRaw()` | **CT-10 (as duas linhas novas: só no bruto / só no atributo)**. ⚠️ **Corrigido na revisão**: a rodada 1 citava CT-12 e ele **não** matava — `User::fake()` popula bruto E atributo, então todo cenário faked ficava verde enquanto a produção recusaria todo login de Google |
| M35 | o `match` tem um ramo só, com a régua do Google para os quatro | CT-11 (linha 1: o X não manda o campo e seria recusado) e CT-12 |
| M36 | o `match` tem um ramo só, devolvendo `true` (confia em todo provedor) | CT-10 (linha `false`) e CT-12 (Google e LinkedIn) |
| M37 | o ramo do X devolve `true` fixo em vez de `filled($email)` | CT-11 (linhas de e-mail nulo e só-espaços) — e CT-13 se a barreira de e-mail ausente também caísse |
| M38 | o ramo do X usa `isset()` — e-mail só com espaços passa | CT-11 (linha de só-espaços) |
| M39 | a barreira de e-mail ausente foi combinada com a de verificação num `if` só, e a ordem inverteu | CT-13 (as quatro linhas) — isolada de CT-10/CT-11 de propósito |
| M40 | e-mail nulo chega ao `where` e estoura 500 no callback | CT-13 (o oráculo é 302 para a tela de login) |
| M41 | `User::updateOrCreate()` — o exemplo da doc do Socialite — cria conta a cada volta | CT-14 (o total de contas) |
| M42 | o segundo callback é abortado por "já autenticado" e não registra o acesso | CT-14 (as duas linhas de trilha) |

---

## Regra R7 — a conferência do GitHub é positiva

> RQ-09 + ADR-03 · perfil **completo** · técnica: **tabela de decisão** sobre a resposta de
> `/user/emails`, mais **normalização de identidade** e **rastreio de efeito**

O `GithubProvider` do Socialite tem um `catch → return` (`:68-70`) que engole a falha e deixa em
`$response['email']` o e-mail do **perfil público**. Então "e-mail não vazio" é indistinguível de
"a verificação falhou" — e é por isso que o kit refaz a chamada.

```gherkin
  Regra: o GitHub só é aceito quando /user/emails devolve uma entrada primária, verificada e com o mesmo e-mail

    Esquema do Cenário: [CT-15] a tabela de decisão da resposta de /user/emails
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existe conta para "vitima@example.com"
      E que o GitHub devolve "vitima@example.com" como e-mail do perfil
      E que "api.github.com/user/emails" responde "<resposta>"
      Quando o visitante chega no callback do GitHub
      Então o veredicto é "<veredicto>"

      Exemplos:
        | resposta                                                              | veredicto  | # regra da tabela            |
        | 200 com [ primary=true, verified=true, mesmo e-mail ]                 | autentica  | a única célula válida        |
        | 200 com [ primary=true, verified=false, mesmo e-mail ]                | recusa     | verificado falso             |
        | 200 com [ primary=false, verified=true, mesmo e-mail ]                | recusa     | não é o e-mail primário      |
        | 200 com [ primary=true, verified=true, OUTRO e-mail ]                 | recusa     | verificado, de outra pessoa  |
        | 200 com [ primary=false, verified=true, outro ] e [ primary=true, verified=false, mesmo ] | recusa | duas entradas, nenhuma completa |
        | 200 com lista vazia                                                   | recusa     | sem evidência                |
        | 200 com objeto no lugar da lista                                      | recusa     | forma inesperada             |
        | 401                                                                   | recusa     | token recusado               |
        | 403                                                                   | recusa     | limite de taxa               |
        | 500                                                                   | recusa     | falha do provedor            |
        | 200 com [ primary=true, verified="true" (string), mesmo e-mail ]      | recusa     | @premissa: exige booleano true |

    Cenário: [CT-16] a comparação do e-mail do GitHub ignora caixa e espaços nos dois lados
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      E que o GitHub devolve "Ja.Tem@Example.com" como e-mail do perfil
      E que "/user/emails" devolve uma entrada primária e verificada com "  ja.tem@EXAMPLE.com "
      Quando o visitante chega no callback do GitHub
      Então a conta autenticada é a de "ja.tem@example.com"
      E o total de contas continua sendo 1

    Cenário: [CT-17] a conferência usa o token do provedor, bate na URL da API e acontece uma única vez
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      Quando o visitante chega no callback do GitHub
      Então exatamente uma requisição saiu para "https://api.github.com/user/emails"
      E ela levou o cabeçalho de autorização com o token que o provedor devolveu
      E ela pediu resposta em JSON

    Cenário: [CT-18] a falha da conferência grava alerta com o motivo, e não grava sucesso
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      E que "/user/emails" responde 403
      Quando o visitante chega no callback do GitHub
      Então o channel de autenticação recebe um alerta com motivo "github_emails_indisponivel" e o status 403
      E o e-mail aparece mascarado no alerta, nunca em claro
      E o channel não recebe nenhum log de autenticação bem-sucedida

    Esquema do Cenário: [CT-19] os outros três provedores não fazem chamada HTTP nenhuma na volta
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com"
      Quando o visitante chega no callback desse provedor e é autenticado
      Então nenhuma requisição saiu pela camada HTTP do kit

      Exemplos:
        | provedor        |
        | google          |
        | linkedin-openid |
        | x               |
```

**Camada**: `Feature` (HTTP) — o efeito é uma chamada de saída e um log, e nenhum dos dois é
observável abaixo do request.

**Notas:**

- `Http::fake()` **sempre** com `Http::preventStrayRequests()`: sem o segundo, um stub com a URL
  errada devolve 200 vazio e CT-15 passaria com a implementação chamando outro endereço.
- CT-15 linha 5 (duas entradas, nenhuma completa) é a discriminante da tabela: ela mata
  `array_filter` por `verified` seguido de `array_filter` por `primary` em **variáveis
  diferentes** — um `any(verified) && any(primary)` fica verde ali, e a entrada verificada é de
  outra pessoa.
- CT-15 última linha é `@premissa`: o ADR-03 diz `verified === true`, estrito. Se a intenção for
  `filter_var`, esta linha inverte. Escrita porque a decisão está no ADR e o ADR é oráculo.
- CT-17 é rastreio de efeito **na direção "aconteceu uma só vez"**. Sem `assertSentCount(1)`,
  uma conferência dentro de um laço sobre os provedores passaria — e são 4 requisições por login.
  O oráculo do token é o que mata "chama sem `withToken()`", que devolveria 401 em produção e
  recusaria **todo** login de GitHub.
- CT-19 é o contrapeso obrigatório: sem ele, um `emailVerificado()` que chame `/user/emails` para
  **todos** os provedores passa em tudo e, em produção, recusa Google, LinkedIn e X — porque
  aqueles tokens não valem na API do GitHub. É o mutante mais caro desta regra e o mais barato de
  escrever.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M43 | o ramo do GitHub aceita e-mail não vazio (o que os tutoriais fazem) | CT-15 (todas as linhas de recusa) |
| M44 | confere `verified` e esquece `primary` | CT-15 (linha `primary=false, verified=true`) |
| M45 | confere `primary` e esquece `verified` | CT-15 (linha `primary=true, verified=false`) |
| M46 | não compara o e-mail da entrada com o do provedor | CT-15 (linha `OUTRO e-mail`) |
| M47 | `any(verified) && any(primary)` em vez de uma entrada que satisfaça as três | CT-15 (linha das duas entradas) |
| M48 | não confere `$resposta->successful()` — 403 com corpo vazio vira lista vazia e o fluxo segue | CT-15 (linhas 401/403/500) e CT-18 |
| M49 | a comparação de e-mail é `===` cru | CT-16 |
| M50 | a chamada sai sem o token | CT-17 |
| M51 | a conferência roda para cada provedor do enum, num laço | CT-17 (`uma única vez`) e CT-19 |
| M52 | a conferência roda para todos os provedores, não só o GitHub | CT-19 |
| M53 | a falha da conferência não é logada (só devolve `false`) | CT-18 |
| M54 | a falha loga o e-mail em claro | CT-18 |
| M55 | a falha loga o alerta e **segue** para o log de sucesso (`return` esquecido) | CT-18 (a terceira asserção) |

---

## Regra R18 — sem conta, o callback cria conta se e somente se o registro aberto está ligado

> RQ-07, RQ-09 · perfil **completo** · técnica: **tabela de decisão de 2 condições, 4 células**
> · **regra inteira trazida pela revisão adversarial**

**Esta regra não existia na rodada 1, e a lacuna era a maior do conjunto.** A tabela é
`conta existe {sim, não} × registro aberto {ligado, desligado}`, e só **uma** das quatro células
tinha cenário (a linha do X em CT-37: sem conta + registro fechado). A célula que faltava é a
**válida** — sem conta + registro aberto → **cria**. Sem ela, uma implementação que recuse
`conta_inexistente_registro_fechado` **sempre**, sem nunca consultar o interruptor, passava nos 44
cenários: GitHub, LinkedIn e X nunca criariam conta em produção, e nada ficaria vermelho.

O sintoma que denunciou a lacuna: a persona **"quem não tem conta"** estava declarada no
`## Setup Global` e **não era usada por cenário nenhum**, e a palavra "registro" aparecia **uma
única vez** em todo o Gherkin.

```gherkin
  Regra: sem conta, o callback cria conta se e somente se o registro aberto está ligado

    Esquema do Cenário: [CT-45] as quatro células de conta × registro aberto
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E que o provedor prova o e-mail "<email>" como verificado
      E o arranjo de conta "<conta>" e de registro "<registro>"
      Quando o visitante chega no callback desse provedor
      Então o veredicto é "<veredicto>"
      E o total de contas passa a ser <total>
      E o destino do redirecionamento contém "<destino>"

      Exemplos:
        | provedor        | email               | conta      | registro           | veredicto | total | destino    | # célula                    |
        | github          | novo@example.com    | não existe | aberto ligado      | autentica | 1     | meu-perfil | **a célula que faltava**    |
        | linkedin-openid | novo@example.com    | não existe | aberto ligado      | autentica | 1     | meu-perfil | **a célula que faltava**    |
        | x               | novo@example.com    | não existe | aberto ligado      | autentica | 1     | meu-perfil | **a célula que faltava**    |
        | github          | novo@example.com    | não existe | DEFAULT de fábrica | recusa    | 0     | /app/login | RQ-07 medido, sem arranjo   |
        | linkedin-openid | novo@example.com    | não existe | DEFAULT de fábrica | recusa    | 0     | /app/login | RQ-07 medido, sem arranjo   |
        | x               | novo@example.com    | não existe | DEFAULT de fábrica | recusa    | 0     | /app/login | RQ-07 medido, sem arranjo   |
        | github          | ja.tem@example.com  | existe     | aberto ligado      | autentica | 1     | /app       | conta existe vence          |
        | github          | ja.tem@example.com  | existe     | DEFAULT de fábrica | autentica | 1     | /app       | conta existe vence          |

    Cenário: [CT-45b] a conta criada por login social nasce com o e-mail já verificado e o nome do provedor
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que o registro aberto está ligado
      E que o GitHub prova "novo@example.com" como verificado, com o nome "Pessoa do GitHub"
      Quando o visitante chega no callback do GitHub
      Então existe exatamente uma conta com o e-mail "novo@example.com"
      E o nome dela é "Pessoa do GitHub"
      E o e-mail dela está marcado como verificado
      E a conta autenticada é essa
```

**Camada**: `Feature` (HTTP). O `Então` afirma sobre registro gravado e sobre sessão.

**Notas:**

- As três linhas `DEFAULT de fábrica` são a **única** medida direta de RQ-07 dentro desta wiki, e
  elas **não arranjam** o interruptor de registro — exatamente como a linha "de fábrica" de CT-01.
  Arranjar seria medir o `phpunit.xml`.
- A chave é `kit.registro.habilitado`, da feature de registro e aprovação. **Não** invente
  `kit.registro.aberto`: `config()->set()` aceita qualquer chave, e essa foi a chave imaginária que
  deixou dois casos da wiki ancestral verdes enquanto a produção recusava o cadastro
  (`.ai/rules/config.md`, "uma pergunta, uma dona").
- O destino `meu-perfil` versus `/app` é o contrapeso das duas metades: sem ele, uma implementação
  que mande **todo mundo** para o perfil passa nas três primeiras linhas.
- CT-45b traz os valores concretos que separam "criou uma conta" de "criou a conta certa": uma
  implementação que grave o e-mail no campo do nome, ou que deixe `email_verified_at` nulo (e
  prenda a pessoa numa tela de "verifique seu e-mail" depois de o provedor **já** ter verificado),
  passa em CT-45 e morre aqui.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M112 | a recusa por conta inexistente é incondicional — o interruptor de registro nunca é consultado | CT-45 (as três linhas `aberto ligado` sem conta) |
| M113 | o inverso: cria conta sempre que não existe, ignorando o registro fechado | CT-45 (as três linhas `DEFAULT de fábrica`) |
| M114 | a criação de conta ficou só no ramo do Google (o primeiro escrito) | CT-45 (as linhas de LinkedIn e X) |
| M115 | todo mundo é mandado para o perfil, não só quem se registrou | CT-45 (as duas últimas linhas, destino `/app`) |
| M116 | a conta nova nasce com `email_verified_at` nulo | CT-45b |
| M117 | o nome da conta nova recebe o e-mail | CT-45b |
| M118 | `updateOrCreate` cria uma segunda conta para quem já tem | CT-45 (as duas últimas linhas, total 1) |

---

## Regra R19 — o login autentica na conta certa e devolve a pessoa ao painel de onde ela clicou

> RQ-09 · perfil **completo** · técnica: **desambiguação de identidade** + **EP por painel**
> · **regra inteira trazida pela revisão adversarial**

Duas lacunas do mesmo eixo, e as duas passavam nos 44 cenários da rodada 1:

1. **Nenhum cenário tinha mais de uma conta no banco.** Com uma conta só,
   `where('email','like',$email)` é indistinguível de `where('email',$email)` — e o `like` é
   case-insensitive **e** trata `_` como curinga. Quem controla `ja_tem@example.com` num provedor
   entra na conta `ja.tem@example.com`. O `## Checklist de Taxonomia` da rodada 1 dispensou IDOR
   dizendo "nenhuma rota recebe id de recurso"; **o e-mail é o identificador de recurso desta
   feature**, e o conjunto nunca o desambiguava.
2. **Nenhum cenário afirmava o painel de destino.** CT-08 prova que o botão existe em
   `/infra/login`; uma implementação que mande todo mundo para `/app` passava em tudo.

```gherkin
  Regra: o e-mail do provedor casa com UMA conta, exatamente, e nunca com a vizinha

    Esquema do Cenário: [CT-47] duas contas parecidas, e só a certa é autenticada
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existem as contas "ja.tem@example.com" e "ja_tem@example.com"
      E que o GitHub prova "<email do provedor>" como verificado
      Quando o visitante chega no callback do GitHub
      Então a conta autenticada é a de "<conta esperada>"
      E o total de contas continua sendo 2
      E a outra conta não foi alterada

      Exemplos:
        | email do provedor    | conta esperada      | # o que mata                          |
        | ja_tem@example.com   | ja_tem@example.com  | `like` com `_` como curinga           |
        | ja.tem@example.com   | ja.tem@example.com  | o lado espelho do mesmo curinga       |
        | JA_TEM@EXAMPLE.COM   | ja_tem@example.com  | normalização de caixa sem trocar de conta |
        | " ja.tem@example.com"| ja.tem@example.com  | espaços nas bordas sem trocar de conta |

  Regra: quem entra por um painel volta para aquele painel

    Esquema do Cenário: [CT-48] o destino segue o painel de onde a pessoa clicou
      Dado que o GitHub está ligado com as três chaves preenchidas
      E que existe conta para "ja.tem@example.com", com papel de acesso ao painel "<painel>"
      E que o visitante veio da tela de login de "<painel>"
      Quando ele chega no callback do GitHub e é autenticado
      Então o destino do redirecionamento é o painel "<painel>"

      Exemplos:
        | painel |
        | app    |
        | admin  |
        | infra  |
```

**Camada**: `Feature` (HTTP).

**Notas:**

- CT-47 é o cenário que a linha "IDOR — não se aplica" do checklist da rodada 1 dispensou
  indevidamente. Ele é barato e mata a variante mais provável do casamento "case-insensitive
  competente": em SQLite e em MySQL o `LIKE` ignora caixa, então `like` **parece** a solução
  elegante para o que CT-16 pede, e traz o curinga de brinde.
- As duas últimas linhas de CT-47 são o cruzamento com R7: elas provam que a normalização de caixa
  e de espaços (que CT-16 exige) **não** foi comprada ao preço de trocar de conta.
- CT-48 `@premissa` implícita: o kit precisa saber de qual painel a pessoa veio. Se a
  implementação não guardar isso, o cenário reprova — e é a reprovação correta, porque o botão está
  nas três telas de login (CT-08) e mandar quem clicou em `/infra/login` para `/app` é defeito
  observável. **Registrado como pergunta A-06.**

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M119 | o casamento usa `where('email','like',$email)` | CT-47 (as duas primeiras linhas) |
| M120 | o casamento normaliza a caixa com `like` e mantém o curinga | CT-47 (linha `JA_TEM@`) |
| M121 | a normalização de espaços é feita com `str_replace(' ','',...)` e junta e-mails distintos | CT-47 (última linha) |
| M122 | o destino é `/app` fixo para todos os painéis | CT-48 (linhas `admin` e `infra`) |
| M123 | o destino é o painel do **primeiro** papel do usuário, não o de origem | CT-48 |

---

## Regra R8 — os quatro client_secret ficam criptograma na tabela, legíveis na leitura e mascarados na trilha

> ADR-06 · perfil **completo** · técnica: **rastreio de efeito × EP por provedor**

O defeito é anterior a esta entrega: `addEncrypted` na migration cifra na gravação, e
`ConfiguracoesDoKit::encrypted()` devolve `['mail_password']` — o nome do segredo do Google não
está lá, então `SettingsMapper` se omite nas **duas** pontas.

```gherkin
  Regra: o client_secret de cada provedor é gravado como criptograma e devolvido em claro

    Esquema do Cenário: [CT-20] o oráculo de três pontas, provedor por provedor
      Dado o segredo "SEGREDO-DE-<PROVEDOR>-42" gravado pela API do settings
      Quando as configurações do kit são alinhadas com a config do processo
      Então o payload bruto da linha de settings não contém "SEGREDO-DE-<PROVEDOR>-42"
      E a leitura da propriedade devolve "SEGREDO-DE-<PROVEDOR>-42"
      E a chave de config do provedor recebe "SEGREDO-DE-<PROVEDOR>-42"

      Exemplos:
        | provedor        | propriedade                            | chave de config                       |
        | google          | login_google_client_secret             | services.google.client_secret         |
        | github          | login_github_client_secret             | services.github.client_secret         |
        | linkedin-openid | login_linkedin_openid_client_secret    | services.linkedin-openid.client_secret|
        | x               | login_x_client_secret                  | services.x.client_secret              |

    Esquema do Cenário: [CT-21] o segredo salvo PELA TELA também fica criptograma
      Dado o administrador da instalação na tela de configurações do kit
      Quando ele preenche o client_secret do provedor "<provedor>" com "DIGITADO-NA-TELA-42" e salva
      Então o payload bruto da linha de settings não contém "DIGITADO-NA-TELA-42"
      E a leitura da propriedade devolve "DIGITADO-NA-TELA-42"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

    Esquema do Cenário: [CT-22] a trilha de auditoria guarda a máscara, não o segredo
      Dado o administrador da instalação na tela de configurações do kit
      Quando ele preenche o client_secret do provedor "<provedor>" com "DIGITADO-NA-TELA-42" e salva
      Então a linha de trilha daquela propriedade traz o valor mascarado no antigo e no novo
      E nenhuma linha de trilha contém "DIGITADO-NA-TELA-42"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |
```

**Camada**: CT-20 `Feature` (API de settings + `config()`); CT-21 e CT-22 **componente Livewire**
(`Livewire::test(ConfiguracoesDoKit::class)->fillForm([...])->call('save')`), porque o `Então` fala
do caminho de gravação da **tela**, que é o sintoma 2 do ADR-06.

**Notas:**

- CT-20 é o padrão de casa, copiado de `tests/Kit/ConfiguracoesDoKitTest.php:324` (a senha de
  SMTP). As três pontas distinguem três implementações: **sem cifra** a primeira falha; **cifra
  sem decifra** a segunda; **decifra que não chega ao consumidor** a terceira — e a terceira é o
  sintoma 1 do ADR-06, o botão no ar apontando para um OAuth quebrado.
- A linha do LinkedIn é a discriminante de CT-20: ela é a única em que o nome da propriedade e a
  chave de config **divergem em forma** (`_openid` com underscore, `-openid` com hífen), e é a
  alternativa 3 recusada no ADR-01 tentando voltar por outra porta.
- CT-22 nasceu de um achado desta derivação, não do ADR-06: o mascaramento da trilha é decidido
  por `in_array($propriedade, ConfiguracoesDoKit::encrypted(), true)`
  (`app/Listeners/AuditarConfiguracoesDoKit.php:127`), então hoje o segredo do Google vai para
  `audits` em **claro**. Registrado nas perguntas acima.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M56 | `encrypted()` continua devolvendo só `['mail_password']` | CT-20 (a primeira asserção nas quatro linhas) e CT-22 |
| M57 | `encrypted()` recebe os três novos e deixa o Google fora (a alternativa 2 recusada no ADR-06) | CT-20 (linha `google`) e CT-22 (linha `google`) |
| M58 | o nome do LinkedIn em `encrypted()` é `login_linkedin_client_secret`, sem `_openid` | CT-20 (linha do LinkedIn: a propriedade real fica sem cifra) |
| M59 | o mapa do LinkedIn aponta para `services.linkedin.client_secret` | CT-20 (terceira asserção da linha do LinkedIn) e CT-35 |
| M60 | a cifra é aplicada só na migration, e o `save()` da tela grava em claro | CT-21 |
| M61 | o mascaramento da trilha usa uma lista de nomes própria, escrita à mão | CT-22 (as linhas dos três provedores novos) |

---

## Regra R9 — a migration normaliza o segredo do Google já gravado, sem tocar em mais nada

> ADR-06 · perfil **completo** · técnica: **EP de 3 partições** + **estado × operação de rollback**

```gherkin
  Regra: a normalização do segredo já gravado decide por decifrar, nunca por heurística sobre o formato

    Esquema do Cenário: [CT-23] as três partições do valor já gravado
      Dado que a linha de "login_google_client_secret" está gravada como "<estado>"
      Quando a normalização da migration desta entrega roda
      Então o payload bruto passa a ser "<payload>"
      E a leitura da propriedade devolve "<leitura>"

      Exemplos:
        | estado                          | payload      | leitura              | # partição            |
        | criptograma de "SEGREDO-42"     | criptograma  | SEGREDO-42           | já certo, não mexe    |
        | texto claro "SEGREDO-42"        | criptograma  | SEGREDO-42           | o dado de quem salvou pela tela |
        | nulo                            | nulo         | nulo                 | passa reto            |
        | string vazia                    | criptograma  | string vazia         | fronteira de string   |

    Cenário: [CT-24] a normalização não toca em nenhuma outra propriedade
      Dado que "mail_password" está gravada como criptograma de "senha-do-smtp"
      E que "nome_da_aplicacao" está gravada em claro como "Kit de Exemplo"
      Quando a normalização da migration desta entrega roda
      Então "mail_password" continua legível como "senha-do-smtp"
      E o payload de "nome_da_aplicacao" continua sendo "Kit de Exemplo" em claro

    Cenário: [CT-25] o rollback apaga as nove propriedades novas e preserva a do Google
      Dado que as nove propriedades desta entrega existem
      E que "login_google_client_secret" está gravada com um segredo
      Quando a migration desta entrega é revertida
      Então nenhuma das nove propriedades novas existe
      E "login_google_client_secret" continua existindo, com o mesmo valor legível
```

**Camada**: `Feature`. O arnês existe e foi conferido: as migrations de settings do kit
**retornam classe anônima** (`return new class extends SettingsMigration`,
`database/settings/2026_08_24_000000_create_kit_settings.php:40`), então o cenário faz
`$migration = require database_path('settings/....php')` e chama `up()` / `down()` diretamente.
O estado inicial em **claro** se monta com `gravarConfiguracao()` (`tests/Pest.php`), que escreve
o `payload` sem passar pelo `save()` e portanto sem cifrar — é exatamente o dado que uma
instalação afetada tem hoje.

**Notas:**

- A linha `string vazia` é a fronteira que o `00` e o `.ai/rules/config.md` cobram em toda
  credencial. `Crypto::encrypt('')` **não** é `Crypto::encrypt(null)`
  (`vendor/spatie/laravel-settings/src/Support/Crypto.php:8-12` devolve `null` só para `null`),
  então a linha distingue `is_null()` de `blank()` no guarda da closure — e um `blank()` ali
  deixaria a string vazia sem cifrar, o que é inofensivo, e sem **decifrar** na leitura, o que
  não é: `decrypt('')` estoura e o `catch (Throwable)` do `KitServiceProvider` engoliria a
  **leitura do grupo inteiro**, jogando a instalação de volta no `.env` em silêncio.
- CT-25 é a célula de operação que ninguém escreve: o `down()` com `deleteIfExists` de **dez**
  nomes em vez de nove apaga a credencial do Google de uma instalação configurada. O `## Rollback`
  do PRD documenta a ordem correta; este cenário é o que a cobra.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M62 | a normalização cifra sempre — o valor já cifrado é cifrado duas vezes | CT-23 (linha 1: a leitura devolveria criptograma) |
| M63 | a normalização decide pelo formato da string (heurística de prefixo `eyJ`) em vez de `try { decrypt } catch` | CT-23 (linhas 1 e 2 juntas: uma heurística acerta uma e erra a outra) |
| M64 | a normalização não trata `null` e estoura `DecryptException` fora do `catch` | CT-23 (linha `nulo`) |
| M65 | o guarda de `null` é `blank()` e a string vazia cai fora do tratamento | CT-23 (linha `string vazia`) |
| M66 | a normalização roda para todas as propriedades do grupo | CT-24 (`nome_da_aplicacao` viraria criptograma) |
| M67 | o `down()` inclui `login_google_client_secret` na lista de exclusão | CT-25 |
| M68 | a normalização roda com `$encrypted = true` e o mapper cifra o retorno da closure | CT-23 (linha 1) — ⚠️ **sem matador direto**: cifrar duas vezes um valor já cifrado e ler uma vez devolve criptograma, o que CT-23 linha 1 mata; mas cifrar duas vezes um valor em CLARO e ler uma vez devolve criptograma, o que CT-23 linha 2 também mata. **Coberto** |

**Lacuna declarada (R9)**: não há cenário para **concorrência** na normalização (a migration
rodando em duas instâncias ao mesmo tempo, num deploy com duas máquinas). Tentado: `RefreshDatabase`
com SQLite `:memory:` é um processo e uma conexão, e `pest --parallel` distribui **arquivos**, não
threads dentro de um cenário — não há como expressar duas execuções simultâneas da migration neste
arnês. O risco real é baixo (migration roda sob lock do `migrations` do Laravel) e fica registrado
em vez de coberto por um cenário que não prova nada.

---

## Regra R10 — nenhum client_secret aparece no HTML, e sobrevive a um save que deixou o campo em branco

> `.ai/rules/pages.md`, RQ-06 · perfil **completo** (escalado) · técnica: **par obrigatório × EP
> por provedor**

`->password()` e `->revealable()` mexem no `type` do input — na **tela**. O valor continua em
`$this->data`, propriedade pública do componente Livewire, e o Livewire serializa isso inteiro no
`wire:snapshot`. Foi Blocker na v0.19.0, com a senha de SMTP em claro no corpo de um 200.

```gherkin
  Regra: o client_secret de cada provedor não aparece no HTML de nenhuma tela

    Esquema do Cenário: [CT-26] o segredo fora do HTML da tela de configurações
      Dado o segredo "SEGREDO-DE-<PROVEDOR>-42" gravado para o provedor "<provedor>"
      E o administrador da instalação autenticado
      Quando ele abre a tela de configurações do kit
      Então a resposta é 200
      E o corpo da resposta não contém "SEGREDO-DE-<PROVEDOR>-42"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

    Esquema do Cenário: [CT-27] o segredo fora do HTML da tela de login
      Dado que o provedor "<provedor>" está ligado com o client_secret "SEGREDO-DE-<PROVEDOR>-42"
      Quando o visitante abre a tela de login do painel /app
      Então a resposta é 200
      E a tela traz o botão "Entrar com <rotulo>"
      E o corpo da resposta não contém "SEGREDO-DE-<PROVEDOR>-42"

      Exemplos:
        | provedor        | rotulo   |
        | google          | Google   |
        | github          | GitHub   |
        | linkedin-openid | LinkedIn |
        | x               | X        |

  Regra: o client_secret guardado sobrevive a um salvamento que deixou o campo em branco

    Cenário: [CT-28] o save que não tocou em nenhum segredo não apaga nenhum dos quatro
      Dado os QUATRO segredos gravados com valores distintos — "SEGREDO-DE-GOOGLE-42",
        "SEGREDO-DE-GITHUB-42", "SEGREDO-DE-LINKEDIN-42" e "SEGREDO-DE-X-42"
      E a senha de SMTP gravada como "SENHA-DE-SMTP-42"
      E o administrador da instalação na tela de configurações do kit
      Quando ele altera o nome da aplicação e salva, sem tocar em nenhum campo de segredo
      Então o formulário não acusa erro
      E o segredo do Google continua sendo "SEGREDO-DE-GOOGLE-42"
      E o segredo do GitHub continua sendo "SEGREDO-DE-GITHUB-42"
      E o segredo do LinkedIn continua sendo "SEGREDO-DE-LINKEDIN-42"
      E o segredo do X continua sendo "SEGREDO-DE-X-42"
      E a senha de SMTP continua sendo "SENHA-DE-SMTP-42"
      E o nome da aplicação passou a ser o novo

    Esquema do Cenário: [CT-29] preencher o campo do segredo substitui o valor guardado
      Dado o segredo "SEGREDO-ANTIGO-42" gravado para o provedor "<provedor>"
      E o administrador da instalação na tela de configurações do kit
      Quando ele preenche o client_secret desse provedor com "SEGREDO-NOVO-42" e salva
      Então o segredo do provedor "<provedor>" passa a ser "SEGREDO-NOVO-42"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |
```

**Camada**: CT-26 e CT-27 `Feature` (HTTP — o oráculo é o corpo da resposta); CT-28 e CT-29
**componente Livewire** (o `Então` fala do que o `save` fez com o estado do formulário).

**Notas:**

- Os valores são discriminantes: `SEGREDO-DE-GITHUB-42` não aparece por acidente em lugar nenhum.
  Usar `secret` ou `password` produziria falso vermelho pelo próprio formulário.
- `assertOk()` **junto** da ausência, em CT-26 e CT-27: sem ele, um 500 passa por engano —
  a página que não renderiza também não contém o segredo.
- CT-27 asserta o **botão presente** junto da ausência do segredo, pela mesma razão: uma tela sem
  botão nenhum passaria trivialmente.
- CT-29 é o contrapeso obrigatório de CT-28. Sem ele, um `->dehydrated(false)` fixo — em vez do
  `->dehydrated(fn ($e) => filled($e))` — faz o campo **nunca** gravar, CT-28 fica verde, e o
  administrador não consegue trocar credencial pela tela.
- **CT-28 deixou de ser `Esquema` e passou a arranjar os QUATRO segredos com valores distintos, por
  achado da revisão adversarial.** A versão da rodada 1 gravava o segredo **só** do provedor da
  linha e afirmava que "os outros três continuam o que eram" — os outros três eram `null` antes e
  `null` depois, então a asserção era **tautologia**, e o mutante que ela dizia matar (o laço de
  `mutateFormDataBeforeFill()` zerando os quatro e o save gravando `null` em três) **sobrevivia**
  comparando `null` com `null`. Com os quatro valores distintos e nomeados no `Então`, um save que
  apague qualquer um deles morre. A senha de SMTP entra na mesma lista porque o laço novo passa por
  ela: é a regressão da wiki `settings-do-kit` dentro do mesmo cenário.
- A última linha (`o nome da aplicação passou a ser o novo`) é o que impede o cenário de passar
  porque **o save não fez nada**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M69 | `mutateFormDataBeforeFill()` zera só `mail_password` e o do Google (o estado anterior) | CT-26 (linhas dos três provedores novos) |
| M70 | o laço de zeramento usa o nome errado para o LinkedIn (sem `_openid`) | CT-26 (linha do LinkedIn) |
| M71 | `->dehydrated(false)` fixo no campo de segredo | CT-29 |
| M72 | o campo de segredo é dehidratado sempre — o save em branco grava `null` | CT-28 (as quatro linhas) |
| M73 | o zeramento acontece **depois** do fill e o valor já entrou no snapshot | CT-26 |
| M74 | a credencial é injetada no blade da tela de login (para montar o `href`) | CT-27 |

---

## Regra R11 — ligar o interruptor de um provedor abre os campos de credencial dele, e só dele

> RQ-05, RQ-06 · perfil **padrão** · técnica: **estado × campo por provedor**

```gherkin
  Regra: os campos de credencial de um provedor ficam escondidos com o interruptor desligado e visíveis com ele ligado

    Esquema do Cenário: [CT-30] a visibilidade dos campos segue o interruptor daquele provedor
      Dado o administrador da instalação na tela de configurações do kit
      Quando ele põe o interruptor do provedor "<provedor>" em "<estado>"
      Então o campo de client_id desse provedor está "<visibilidade>"
      E o campo de client_secret desse provedor está "<visibilidade>"

      Exemplos:
        | provedor        | estado    | visibilidade |
        | google          | desligado | escondido    |
        | google          | ligado    | visível      |
        | github          | desligado | escondido    |
        | github          | ligado    | visível      |
        | linkedin-openid | desligado | escondido    |
        | linkedin-openid | ligado    | visível      |
        | x               | desligado | escondido    |
        | x               | ligado    | visível      |

    Cenário: [CT-31] ligar um provedor não abre os campos de nenhum outro
      Dado o administrador da instalação na tela de configurações do kit, com os quatro interruptores desligados
      Quando ele liga apenas o interruptor do GitHub
      Então os campos de credencial do GitHub estão visíveis
      E os campos de credencial do Google, do LinkedIn e do X continuam escondidos

    Cenário: [CT-32] a aba Login tem uma seção por provedor, com os campos de cada um
      Dado o administrador da instalação na tela de configurações do kit
      Quando o formulário é montado
      Então existe interruptor, client_id e client_secret para cada um dos quatro provedores
      E existe o campo de rodapé da tela de login, fora das seções de provedor
```

**Camada**: **componente Livewire** (`assertSchemaComponentVisible` /
`assertSchemaComponentHidden` / `assertSchemaComponentExists`). Confirmado no vendor que
`assertFormFieldVisible` e `assertFormFieldHidden` estão `@deprecated` no Filament 5
(`vendor/filament/forms/.stubs.php:35,40`) e continuam funcionando sem avisar — CT escrito com
eles passa hoje e quebra no upgrade.

**Nota de gate**: o que estes três cenários **não** provam é que o `->live()` existe. Num teste de
componente, `fillForm()` muda o estado e a asserção seguinte reavalia o schema — CT-30 fica verde
com o `->live()` removido, e no navegador os campos não aparecem até a pessoa clicar em outra
coisa. Esse é o único mutante desta regra que o navegador mata, e é **CT-B02**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M75 | os campos não têm `visible()` — aparecem sempre | CT-30 (as quatro linhas `desligado`) |
| M76 | o `visible()` está invertido | CT-30 (as linhas `ligado`) |
| M77 | o `visible()` de todos os provedores lê o interruptor do primeiro | CT-31 |
| M78 | a aba tem só a seção do Google (o estado anterior) | CT-32 |
| M79 | o `foreach` sobre o enum monta as seções e o rodapé **dentro** de cada uma | CT-32 (o rodapé fora das seções) |
| M80 | o toggle não é `->live()` — os campos só aparecem no próximo ciclo | ⚠️ **só CT-B02** (declarado no `05`) |

---

## Regra R12 — o que se grava na tela alcança tudo que vem do interruptor

> RQ-06, RQ-09 ("refletir em tudo que vem") · perfil **padrão** ·
> técnica: **rastreio de efeito ponta a ponta**

```gherkin
  Regra: ligar um provedor pela tela põe o botão dele no ar e tira as rotas dele do 404

    Esquema do Cenário: [CT-33] a gravação pela tela alcança a tela de login e as rotas
      Dado o administrador da instalação na tela de configurações do kit
      Quando ele liga o provedor "<provedor>", preenche as duas credenciais dele e salva
      Então o formulário não acusa erro
      E a tela de login do painel /app passa a trazer o botão "Entrar com <rotulo>"
      E "/auth/<provedor>/redirect" responde 302 para o host de autorização daquele provedor
      E o client_id no destino é o que ele acabou de digitar

      Exemplos:
        | provedor        | rotulo   |
        | google          | Google   |
        | github          | GitHub   |
        | linkedin-openid | LinkedIn |
        | x               | X        |

    Cenário: [CT-34] desligar pela tela derruba a rota sem apagar a credencial guardada
      Dado o GitHub ligado, com as duas credenciais gravadas
      E o administrador da instalação na tela de configurações do kit
      Quando ele desliga o interruptor do GitHub e salva
      Então "/auth/github/redirect" volta a responder 404
      E a tela de login não traz mais o botão "Entrar com GitHub"
      E o client_id e o client_secret do GitHub continuam gravados

    Esquema do Cenário: [CT-35] cada propriedade nova alcança a chave de config dela
      Dado o valor "<valor>" gravado na propriedade "<propriedade>"
      Quando as configurações do kit são alinhadas com a config do processo
      Então a chave "<chave>" recebe "<valor>"

      Exemplos:
        | propriedade                         | valor            | chave                                   |
        | login_github_habilitado             | verdadeiro       | kit.login.github.habilitado             |
        | login_github_client_id              | id-do-github     | services.github.client_id               |
        | login_github_client_secret          | segredo-github   | services.github.client_secret           |
        | login_linkedin_openid_habilitado    | verdadeiro       | kit.login.linkedin-openid.habilitado    |
        | login_linkedin_openid_client_id     | id-do-linkedin   | services.linkedin-openid.client_id      |
        | login_linkedin_openid_client_secret | segredo-linkedin | services.linkedin-openid.client_secret  |
        | login_x_habilitado                  | verdadeiro       | kit.login.x.habilitado                  |
        | login_x_client_id                   | id-do-x          | services.x.client_id                    |
        | login_x_client_secret               | segredo-x        | services.x.client_secret                |
```

**Camada**: CT-33 e CT-34 **componente Livewire** para a ação, `Feature` (HTTP) para o `Então` —
o efeito é observável só no request seguinte. CT-35 `Feature` (settings + `config()`).

**Notas:**

- CT-35 é o caso que `.ai/rules/settings.md` cobra por escrito: "esquecer a linha do mapa é o
  defeito silencioso — o campo aparece, grava, e não governa nada". Nove linhas, uma por
  propriedade nova, porque nove é o número de chances de esquecer.
- As três linhas do LinkedIn de CT-35 são as discriminantes: a propriedade tem `_openid` com
  **underscore** e a chave de config tem `-openid` com **hífen**, e é a única transformação de
  nome do desenho inteiro (ADR-01). Um `str_replace` esquecido, ou aplicado na direção errada,
  morre exatamente aqui.
- CT-34 é a direção de volta, e a terceira linha do `Então` é o que separa "desligou" de
  "desligou apagando a credencial" — que faria o administrador precisar recadastrar o app OAuth
  para religar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M81 | falta a linha do mapa de uma das nove propriedades | CT-35 (a linha correspondente) |
| M82 | o mapa do LinkedIn usa `services.linkedin.*` / `kit.login.linkedin.habilitado` | CT-35 (as três linhas do LinkedIn) e CT-05 |
| M83 | o mapa do LinkedIn usa a propriedade `login_linkedin_*` (sem `_openid`) | CT-35 (as três linhas do LinkedIn) |
| M84 | o interruptor gravado no banco não chega à config (só as credenciais chegam) | CT-33 (a rota continuaria em 404) e CT-35 (linhas `habilitado`) |
| M85 | desligar o interruptor zera as credenciais | CT-34 |
| M86 | o toggle grava e não faz efeito até o próximo deploy (chave de boot) | CT-33 e CT-34 |

---

## Regra R13 — o rastro no channel autenticacao

> RQ-09 + o channel declarado no PRD + o padrão `[Classe@Método]` do kit · perfil **padrão**,
> **teto estourado com justificativa** · técnica: **rastreio de efeito, 4 direções**

```gherkin
  Regra: a volta bem-sucedida grava informação no channel de autenticação, nomeando o provedor e mascarando o e-mail

    Esquema do Cenário: [CT-36] o log de sucesso, provedor por provedor
      Dado que o provedor "<provedor>" está ligado com o client_secret "SEGREDO-IRRECONHECIVEL-42"
      E que existe conta para "ja.tem@example.com"
      Quando o visitante chega no callback desse provedor e é autenticado
      Então o channel de autenticação recebe uma informação que nomeia o provedor "<provedor>"
      E a mensagem traz o e-mail mascarado
      E NENHUMA parte do registro — mensagem E contexto, serializados — traz o e-mail em claro
      E nenhuma parte do registro traz "SEGREDO-IRRECONHECIVEL-42"
      E nenhuma parte do registro traz o payload bruto do provedor

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

  Regra: a recusa grava alerta com o motivo, e não grava informação de sucesso

    Esquema do Cenário: [CT-37] o motivo de cada barreira que recusa
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      E o arranjo "<barreira>"
      Quando o visitante chega no callback desse provedor
      Então o channel de autenticação recebe um alerta com motivo "<motivo>"
      E o channel não recebe nenhuma informação de autenticação bem-sucedida
      E ninguém está autenticado

      Exemplos:
        | provedor        | barreira                                       | motivo                                |
        | github          | o provedor não devolve e-mail                  | email_ausente                         |
        | linkedin-openid | o e-mail não está verificado no provedor       | email_nao_verificado                  |
        | x               | não há conta e o registro está fechado         | conta_inexistente_registro_fechado    |
        | google          | o retorno chega sem code e sem state           | falha_no_provedor                     |

  Regra: a rota de ida registra o redirecionamento, e a abertura da tela de login não registra nada

    Esquema do Cenário: [CT-38] o log da ida nomeia o provedor
      Dado que o provedor "<provedor>" está ligado com as três chaves preenchidas
      Quando o visitante abre a rota de ida desse provedor
      Então o channel de autenticação recebe uma informação que nomeia o provedor "<provedor>"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

    Esquema do Cenário: [CT-39] abrir a tela de login não escreve nada no channel de autenticação
      Dado que os quatro provedores estão ligados com as três chaves preenchidas
      Quando o visitante abre a tela de login do painel "<painel>"
      Então a resposta é 200
      E a tela traz os quatro botões
      E o channel de autenticação não recebe nenhum registro

      Exemplos:
        | painel |
        | app    |
        | admin  |
        | infra  |
```

**Camada**: `Feature` (HTTP) com `espiarAutenticacao()` (`tests/Pest.php`), que espia só o channel
`autenticacao` e deixa os outros reais.

**Notas:**

- CT-36 tem as **três** direções no mesmo cenário porque as três falam do mesmo registro: que ele
  existe, o que ele diz e o que ele **não** diz. A asserção do mascarado **presente** é o que separa
  "mascarou" de "não logou nada": sem ela, remover todos os logs deixaria o cenário verde.
- **O oráculo cobre mensagem E contexto, por achado da revisão adversarial.** A rodada 1 afirmava
  só sobre "a mensagem", e a fuga plausível não é a mensagem — é o contexto:
  `Log::info('...', ['bruto' => $doProvedor->getRaw()])`, que um dev escreve para "facilitar a
  depuração" e que carrega o e-mail em claro **e o payload inteiro do provedor**. Foi o mesmo achado
  que revelou que M89 (segredo no contexto) estava declarado morto por um oráculo que não olhava
  para lá.
- CT-37 tem uma barreira por linha e **provedores diferentes por linha**, de propósito: percorrer
  as quatro barreiras sempre com o mesmo provedor deixaria três provedores sem nenhuma prova de que
  a recusa deles é logada, e um `if ($provedor === Google)` no bloco de log passaria.
- CT-39 é a direção "não aconteceu onde não devia", e ela vem do `config/logging.php`: um `info`
  por request de abertura de tela custou **1,1 MB/dia medido** nesta instalação. **Virou `Esquema`
  com uma linha por painel, por achado da revisão adversarial**: a versão da rodada 1 abria as três
  telas num cenário só, o que são três `Quando` disfarçados de leitura — a abertura **é** a ação sob
  teste aqui, e num cenário só não se sabe **qual** painel logou. As duas primeiras linhas do
  `Então` (200 e os quatro botões) impedem o cenário de passar porque a tela não renderizou.
- O prefixo `[Classe@Método]` é padrão de casa (o kit inteiro o usa) e entra como **detalhe** do
  cenário, não como oráculo — ver `## Fronteira com o Plano`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M87 | o log de sucesso não nomeia o provedor (mensagem herdada do Google) | CT-36 (linhas de GitHub, LinkedIn e X) |
| M88 | o log de sucesso traz o e-mail em claro | CT-36 |
| M89 | o log de sucesso traz o `client_secret` ou o payload bruto **no contexto** | **CT-36** (o oráculo agora cobre mensagem E contexto). ⚠️ **Corrigido na revisão**: a rodada 1 afirmava só sobre "a mensagem", e o contexto é justamente onde o vazamento aconteceria |
| M90 | a recusa loga o alerta e **segue** para o log de sucesso (`return` esquecido) | CT-37 (a segunda asserção) |
| M91 | as quatro barreiras compartilham um `motivo` genérico | CT-37 (as quatro linhas exigem motivos diferentes) |
| M92 | a recusa é logada em `info` em vez de `warning` | CT-37 |
| M93 | o log da ida foi removido ("não interessa quem só clicou") | CT-38 |
| M94 | a abertura da tela de login loga o predicado a cada render | CT-39 |
| M95 | o log vai para o channel default (`stack`) em vez de `autenticacao` | CT-36 e CT-38 (o espião é do channel) |

---

## Regra R14 — o interruptor de cada provedor nasce false e falha fechado

> RQ-07, RQ-08 · perfil **padrão** (escalado) · técnica: **EP de coerção × 4 chaves**

```gherkin
  Regra: o interruptor de cada provedor fica desligado, exceto em valor claramente verdadeiro

    Esquema do Cenário: [CT-40] a coerção de cada interruptor, medida no próprio config
      Dado a variável de ambiente "<chave>" com o valor "<valor>"
      Quando o arquivo de configuração do kit é relido
      Então "<caminho>" vale "<esperado>"

      Exemplos:
        | chave                    | caminho                              | valor    | esperado |
        | KIT_SOCIALITE_GOOGLE     | login.google.habilitado              | ausente  | falso    |
        | KIT_SOCIALITE_GOOGLE     | login.google.habilitado              | off      | falso    |
        | KIT_SOCIALITE_GOOGLE     | login.google.habilitado              | true     | verdade  |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | ausente  | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | vazio    | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | false    | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | 0        | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | off      | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | no       | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | lixo     | falso    |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | true     | verdade  |
        | KIT_SOCIALITE_GITHUB     | login.github.habilitado              | 1        | verdade  |
        | KIT_SOCIALITE_LINKEDIN   | login.linkedin-openid.habilitado     | ausente  | falso    |
        | KIT_SOCIALITE_LINKEDIN   | login.linkedin-openid.habilitado     | no       | falso    |
        | KIT_SOCIALITE_LINKEDIN   | login.linkedin-openid.habilitado     | true     | verdade  |
        | KIT_SOCIALITE_X          | login.x.habilitado                   | ausente  | falso    |
        | KIT_SOCIALITE_X          | login.x.habilitado                   | lixo     | falso    |
        | KIT_SOCIALITE_X          | login.x.habilitado                   | 1        | verdade  |
```

**Camada**: `Feature` com `kitConfigCom()` (`tests/Pest.php`), que relê o `config/kit.php` com a
variável forçada e restaura o ambiente no `finally`. **Comportamental, não textual**: a versão
anterior deste cenário na wiki ancestral nasceu errada duas vezes — uma afirmando sobre o texto do
arquivo, outra testando a stdlib do PHP.

**Nota**: o GitHub leva a partição **completa** (nove valores) e os outros três levam três linhas
cada — ausente, um valor de falha aberta e um verdadeiro. A partição completa por quatro chaves
seriam 36 linhas provando quatro vezes a mesma coisa sobre `filter_var`; o que precisa ser provado
por chave é que **aquela chave usa o mecanismo certo**, e as linhas `off` / `no` / `lixo` são
suficientes para isso — são elas que distinguem `filter_var` de `(bool) env()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M96 | uma das três chaves novas usa `(bool) env()` — falha **aberta** em `off`/`no`/lixo | CT-40 (linhas `off` do LinkedIn, `lixo` do X, `off`/`no`/`lixo` do GitHub) |
| M97 | o default de uma das chaves é `true` | CT-40 (as quatro linhas `ausente`) |
| M98 | a chave do LinkedIn é escrita `KIT_SOCIALITE_LINKEDIN_OPENID` no config e `KIT_SOCIALITE_LINKEDIN` no `.env.example` | CT-40 (linhas do LinkedIn: o `ausente` passaria e o `true` falharia) e CT-42 |
| M99 | o caminho de config do LinkedIn é `login.linkedin.habilitado` | CT-40 (linhas do LinkedIn) |
| M100 | as quatro chaves compartilham um interruptor único | **CT-05**. ⚠️ **Corrigido na revisão**: as linhas `verdade` de CT-40 não matam — o `Então` confere só o caminho daquela linha, nunca os outros três |

**Lacuna declarada (R14)**: não há cenário para o **valor de env com espaços nas bordas**
(`KIT_SOCIALITE_GITHUB=" true "`). Tentado com `kitConfigCom()`: `filter_var` com
`FILTER_VALIDATE_BOOLEAN` **aceita** `" true "` (ele apara espaços), então a implementação certa e
a errada concordam e o cenário não discrimina. Registrado como não-discriminante em vez de escrito.

---

## Regra R15 — o enum é a única lista, em TODAS as sete superfícies derivadas

> RQ-02 · perfil **padrão** (escalado na revisão adversarial, era `mínimo`) ·
> técnica: **invariante estrutural sobre todas as superfícies**

**Escalada por achado da revisão adversarial.** A versão da rodada 1 conferia **duas** superfícies
(views de ícone e "as rotas aceitam") de pelo menos **sete**. Consequência: um `disponiveis()` com
um array de quatro valores escrito à mão, um `encrypted()` com quatro nomes escritos à mão, um mapa
de Settings escrito à mão e um `rotulo()` sem caso novo ficariam **todos verdes** e derrubariam o
quinto provedor em silêncio — que é literalmente o que RQ-02 proíbe.

```gherkin
  Regra: cada caso do enum tem, obrigatoriamente, todas as sete superfícies derivadas dele

    Esquema do Cenário: [CT-41] as sete superfícies, caso por caso do enum
      Dado o caso "<provedor>" do enum de provedores
      Então existe a view de ícone dele
      E o rótulo dele não é vazio e é diferente do valor cru
      E "services.<provedor>" tem client_id, client_secret e redirect
      E o redirect dele termina em "/auth/<provedor>/callback"
      E existe a chave "kit.login.<provedor>.habilitado"
      E as três propriedades de Settings dele existem na tabela
      E a propriedade de segredo dele está na lista de propriedades cifradas
      E as duas rotas de OAuth dele estão REGISTRADAS, mesmo com o interruptor desligado

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |

    Cenário: [CT-41b] nada sobrando — nenhuma superfície de provedor fora do enum
      Dado a lista de provedores que o enum declara
      Então o diretório de ícones não tem nenhuma view além das dos casos do enum
      E não existe nenhuma view de botão de provedor específico ao lado do blade genérico
      E nenhuma propriedade de Settings com prefixo "login_" pertence a provedor fora do enum
      E nenhuma chave "kit.login.*" com sub-chave "habilitado" pertence a provedor fora do enum
```

**Camada**: `Feature` — precisa do resolvedor de views, do roteador, do `config` e da tabela de
settings; um `Unit` sem o `TestCase` da aplicação não os tem (`tests/Pest.php` não estende `Unit`).

**Notas:**

- A última linha de CT-41 — **rotas registradas com o interruptor desligado** — separa duas causas
  que produzem o mesmo 404 e é achado da revisão adversarial: "404 porque o guarda recusou" e "404
  porque a rota não existe". CT-03 não distingue as duas, então o mutante M14 (rotas dentro de um
  `if` do interruptor) estava declarado morto por CT-03 **e não estava**. É o defeito que o
  comentário do próprio `routes/web.php` avisa: registrar dentro de um `if` faz
  `route('auth.social.*')` deixar de existir e estoura `RouteNotFoundException` em `route:list` e
  em qualquer `route:cache` feito com o `.env` de outro momento.
- O `rotulo()` diferente do valor cru é o que mata "provedor novo entra no enum sem caso no `match`
  do rótulo" — no LinkedIn isso apareceria na tela como `Entrar com linkedin-openid`.
- CT-41b linha 2 é onde M103 morre de verdade: `botao-google.blade.php` **não** está no diretório
  de ícones, então a asserção da rodada 1 ("o diretório de ícones não tem view além dessas") nunca
  o alcançava.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M101 | um provedor entra no enum sem a partial de ícone | CT-41 |
| M102 | o `icone()` de um caso devolve um nome que não existe como view | CT-41 |
| M103 | o `botao-google.blade.php` continua no repositório ao lado do genérico (a segunda fonte da verdade que o ADR-08 removeu) | **CT-41b (linha 2)** — a rodada 1 citava CT-41, que não o alcançava |
| M124 | `disponiveis()` percorre um array de quatro valores escrito à mão em vez de `cases()` | CT-41 (o quinto provedor não teria as superfícies; e CT-02 para a ordem) |
| M125 | `encrypted()` lista quatro nomes escritos à mão | CT-41 (a linha da lista de cifradas) |
| M126 | o `rotulo()` de um caso cai no valor cru | CT-41 (a linha do rótulo) |
| M127 | o `redirect` de um provedor aponta para o callback de outro | CT-41 (a linha do redirect) e CT-46 |
| M14 | as rotas são registradas dentro de um `if` do interruptor | **CT-41 (última linha)** — a rodada 1 citava CT-03 e CT-42, e nenhum dos dois o matava |

```gherkin
  Regra: o arquivo de config de fábrica já traz os quatro blocos, com o redirect de cada provedor

    Esquema do Cenário: [CT-46] o default do config/services.php, sem nenhum arranjo
      Dado nenhum arranjo — o arquivo de configuração como ele está no repositório
      Quando "services.<provedor>" é lido
      Então ele tem as chaves client_id, client_secret e redirect
      E o redirect é exatamente "/auth/<provedor>/callback"

      Exemplos:
        | provedor        |
        | google          |
        | github          |
        | linkedin-openid |
        | x               |
```

**Camada**: `Feature` (leitura de `config`), **sem `config()->set()` nenhum** — é o ponto.

**Achado da revisão adversarial, fechado aqui.** Nenhum cenário da rodada 1 lia o
`config/services.php` de fábrica: `ligarProvedor()` escreve as três chaves em memória, e a linha
"de fábrica" de CT-01 media só o interruptor. Um bloco copiado do Google — os quatro `redirect`
apontando para `/auth/google/callback`, ou um provedor faltando o bloco inteiro — passava nos 44
cenários e, em produção, mandaria o GitHub devolver a pessoa no callback do Google.

É exatamente o método que `.ai/rules/config.md` cobra e que CT-40 aplicou a `config/kit.php`: **medir
o default do arquivo**, não o valor que o teste escreveu. A rodada 1 aplicou a um dos dois arquivos
de config da feature e esqueceu o outro.

---

## Regra R16 — cada provedor está declarado onde quem instala procura

> RQ-10 · perfil **mínimo** · técnica: **asserção de presença**

```gherkin
  Regra: as chaves, as URIs de callback e as duas recusas estão escritas nos arquivos que quem instala lê

    Esquema do Cenário: [CT-42] o termo declarado no arquivo
      Quando o arquivo "<arquivo>" é lido
      Então ele contém "<termo>"

      Exemplos:
        | arquivo      | termo                          |
        | .env.example | KIT_SOCIALITE_GITHUB           |
        | .env.example | KIT_SOCIALITE_LINKEDIN         |
        | .env.example | KIT_SOCIALITE_X                |
        | .env.example | GITHUB_CLIENT_ID               |
        | .env.example | GITHUB_CLIENT_SECRET           |
        | .env.example | LINKEDIN_CLIENT_ID             |
        | .env.example | LINKEDIN_CLIENT_SECRET         |
        | .env.example | X_CLIENT_ID                    |
        | .env.example | X_CLIENT_SECRET                |
        | README.md    | /auth/github/callback          |
        | README.md    | /auth/linkedin-openid/callback |
        | README.md    | /auth/x/callback               |
        | README.md    | KIT_SOCIALITE_GITHUB           |
        | README.en.md | /auth/github/callback          |
        | README.en.md | /auth/linkedin-openid/callback |
        | README.en.md | /auth/x/callback               |
        | README.en.md | KIT_SOCIALITE_GITHUB           |

    Esquema do Cenário: [CT-42b] a recusa de cada provedor vem com o motivo, na mesma vizinhança
      Quando o arquivo "<arquivo>" é lido
      Então "<provedor>" e "<motivo>" aparecem na MESMA seção
      E o arquivo, sem as linhas de comentário e de citação, não contém "/auth/linkedin/callback"

      Exemplos:
        | arquivo      | provedor | motivo               |
        | README.md    | Discord  | socialiteproviders   |
        | README.md    | Facebook | e-mail verificado    |
        | README.en.md | Discord  | socialiteproviders   |
        | README.en.md | Facebook | verified email       |
```

**Camada**: `Feature` (leitura de arquivo). Asserção de **presença** sobre o texto cru, então
**sem** o filtro de comentário — `.ai/rules/testes.md` exige o filtro só na asserção de ausência.

**Notas:**

- As linhas `Discord` e `Facebook` cobram RQ-10 sobre a parte mais fácil de esquecer: os dois
  provedores que o requisito pediu e a entrega recusou. O README precisa dizer **por que** e **o que
  faltaria** (ADR-04, ADR-05), senão a ausência lê como esquecimento e a próxima pessoa refaz a
  investigação.
- **Elas saíram de CT-42 e viraram CT-42b, por achado da revisão adversarial.** Como simples
  presença da palavra, eram decorativas: `Discord` e `Facebook` são satisfeitas por **qualquer**
  menção — uma linha de roadmap, ou o próprio texto do requisito colado no README. Certa e errada
  produziam o mesmo resultado. CT-42b exige o par **provedor + motivo na mesma seção**, que é o que
  RQ-10 realmente pede.
- A última linha de CT-42b é **asserção de ausência**, então ela — e só ela — vai sobre o texto
  **filtrado**, sem linhas de comentário e de citação (`.ai/rules/testes.md`). Os READMEs deste kit
  citam o que proíbem, e é lá que está escrito o porquê: o padrão já reprovou três vezes nesta base.
  A URI errada `/auth/linkedin/callback` é a que a documentação erraria por hábito, e cadastrá-la no
  console da LinkedIn produz um OAuth que falha sem dizer por quê.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M104 | uma das seis chaves de credencial fica fora do `.env.example` | CT-42 |
| M105 | a URI de callback do LinkedIn é documentada como `/auth/linkedin/callback` | CT-42 |
| M106 | só o `README.md` é atualizado; o `README.en.md` fica atrás | CT-42 (as linhas do `.en`) |
| M107 | as recusas de Facebook e Discord ficam só na wiki, que quem instala não lê | CT-42 |

---

## Regra R17 — as barreiras e a tela valem igual com a multi-tenancy ligada

> RQ-09 + o achado QA-02 da wiki ancestral · perfil **padrão** ·
> técnica: **regressão de ramo não exercitado**

```gherkin
  Regra: ligar a multi-tenancy não muda o login social

    Cenário: [CT-43] os quatro botões aparecem na tela de login com a tenancy ligada
      Dado a multi-tenancy ligada
      E os quatro provedores ligados com as três chaves preenchidas
      Quando o visitante abre a tela de login do painel /app
      Então a tela traz os quatro botões, um por provedor
      E cada botão aponta para a rota do provedor dele

    Cenário: [CT-44] quem entra pelo GitHub e tem organização cai no painel da organização
      Dado a multi-tenancy ligada
      E o GitHub ligado com as três chaves preenchidas
      E uma conta para "ja.tem@example.com" pertencente à organização de slug "acme"
      E que a conferência de e-mails do GitHub confirma esse e-mail como primário e verificado
      Quando o visitante chega no callback do GitHub
      Então a conta autenticada é a de "ja.tem@example.com"
      E o destino do redirecionamento contém "acme"

    Esquema do Cenário: [CT-49] com a tenancy ligada, a barreira continua derrubando as rotas
      Dado a multi-tenancy ligada
      E o GitHub desligado, com as três chaves preenchidas
      Quando o visitante abre "<rota>"
      Então a resposta é 404
      E ninguém está autenticado

      Exemplos:
        | rota                     |
        | /auth/github/redirect    |
        | /auth/github/callback    |
```

**Camada**: `Feature`, suíte `Tenancy` — `Tests\TenancyTestCase` fixa `permission.teams` em
`createApplication()`, antes das migrations, e ligar a flag num `beforeEach` é tarde demais
(`.ai/rules/testes.md`).

**Notas:**

- Três cenários e não a suíte inteira duplicada. O ramo `hasTenancy()` do controller é
  **provedor-agnóstico** — a wiki ancestral já o cobre com o Google, incluindo o caso da conta sem
  organização. O que é novo aqui é que o caminho reescrito continua chegando lá com um provedor
  **diferente do primeiro escrito**, e que a tela de login (não escopada por tenant) continua
  mostrando os botões.
- **CT-49 é a célula inválida, e faltava — achado da revisão adversarial.** A rodada 1 tinha só
  células **válidas** com a tenancy ligada, enquanto a regra promete que "as **barreiras** valem
  igual". Um `abort_unless` que o ramo de tenancy desvie, ou um middleware de tenant que resolva a
  rota antes do guarda, sobrevivia a CT-43 e CT-44 — e é a direção em que o erro é grave, porque
  deixa uma rota pública de OAuth no ar com o provedor desligado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M108 | a tela de login passa a ser escopada por organização e o botão desaparece com a tenancy ligada | CT-43 |
| M109 | o destino de quem entra é `/app` cru, ignorando a organização | CT-44 |
| M110 | o ramo de tenancy foi reescrito com o provedor fixo no Google | CT-44 |
| M111 | a query que casa a conta ganha escopo global por tenant e não acha ninguém | CT-44 |
| M128 | o guarda por provedor é desviado no ramo de tenancy (ou o middleware de tenant resolve antes dele) | **CT-49** |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **CT-47** — ⚠️ a rodada 1 escreveu aqui "não se aplica: nenhuma rota recebe id de recurso", e a revisão adversarial derrubou a dispensa: **o e-mail é o identificador de recurso desta feature**, e o conjunto nunca o desambiguava (nenhum cenário tinha duas contas). Um `like` no casamento entrega a conta do vizinho. Também CT-03 (o `abort_unless` por provedor) e CT-07 (o parâmetro é enum, não id) |
| **Criação × uso** (a regra "criação ≠ edição ≠ uso" da skill) | **CT-45, CT-45b** — ⚠️ ausente na rodada 1: o requisito descreve o **uso** (autenticar), e a célula de **criação** de conta não tinha cenário nenhum. Foi a maior lacuna que a revisão adversarial achou |
| Autorização exercida na ação, não só em `can()` | CT-03, CT-07 — a recusa é medida no request, não num predicado. A permission da tela de Settings é regressão da wiki `settings-do-kit` |
| Idempotência (ancorada no agregado persistido) | CT-14 — total de contas **e** linhas de trilha de acesso; CT-29 (o save repetido do segredo) |
| Concorrência | **lacuna declarada (R9)**: tentado expressar duas execuções simultâneas da migration de normalização; `RefreshDatabase` + SQLite `:memory:` é um processo e uma conexão, e `--parallel` distribui arquivos, não threads. O `abort_unless` não tem contador nem saldo a ultrapassar |
| Fronteira no ponto de entrada (gravação, não só uso) | CT-21, CT-28, CT-29, CT-33, CT-34 — as credenciais são exercitadas na **gravação pela tela**, não só na leitura pelo predicado |
| Domínio condicionado (um campo cujo domínio depende de outro) | CT-01 (o interruptor condiciona se as credenciais importam), CT-30 (o interruptor condiciona a existência dos campos), CT-12 (o provedor condiciona o que conta como prova de verificação) |
| Estado × operação de escrita (o desativado ainda funciona?) | CT-03 — as duas rotas de escrita de sessão contra provedor desligado; CT-34 — desligar pela tela derruba a rota |
| Ausente ≠ `null` ≠ vazio | CT-01 (`client_secret` vazio, só-espaços e ausente em linhas separadas), CT-10 (`campo ausente` isolado de `false`), CT-11 (e-mail nulo × só-espaços), CT-23 (`nulo` × `string vazia`) |
| Paginação / ordenação | **não se aplica**: nenhuma listagem nesta feature. A única lista é `disponiveis()`, cuja **ordem** é oráculo em CT-02 |
| Timezone / DST | **não se aplica**: nenhum valor desta feature é data ou hora. O `expiresIn` do token do provedor não é gravado (`## Fora de Escopo` do `00`) |
| Unicode / limite de varchar | **lacuna declarada**: nenhuma credencial de OAuth é texto livre do usuário — os quatro provedores emitem ASCII. O e-mail com acento não foi coberto porque o casamento normalizado (CT-16, e CT-07 da wiki ancestral) usa `mb_strtolower`, e `mb_` é a função certa por construção |
| Unicidade + soft delete | **não se aplica**: nada é criado com unicidade nesta feature além da conta, que a wiki ancestral cobre, e `User` do kit não é soft-deletable neste caminho |
| CRUD combinado (ler/editar/excluir inexistente; excluir duas vezes) | CT-07 (provedor inexistente nas duas rotas), CT-25 (o rollback), CT-29 (editar o segredo depois de gravado), CT-28 (salvar sem alterar nada — a armadilha própria da **edição**) |
| Mass assignment | CT-28 (terceira asserção: um save que não tocou nos outros três segredos não pode gravar `null` neles). **Declarado**: não há payload livre nesta feature — o formulário do Filament é a única entrada, e ele dehidrata por campo declarado |
| Upload | **não se aplica**: nenhum upload |
| Precisão monetária | **não se aplica**: nenhum valor numérico nesta feature |
| **Segredo em saída** (item local do kit, `.ai/rules/pages.md`) | CT-26, CT-27, CT-36 (log), CT-22 (trilha) — quatro superfícies, e a trilha é a que ninguém tinha olhado |
| **Falha fechada em interruptor de env** (item local, `.ai/rules/config.md`) | CT-40 |
| **Chave de config que não existe** (item local, `.ai/rules/config.md`) | CT-35 — o mapa é conferido chave por chave, e é o que impede `config()->set()` de aceitar um nome imaginário |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| A partição exaustiva de `email_verified` para o **Google** | já é CT-11 da wiki ancestral, com as seis linhas e o alias `verified_email`. Mata os mesmos mutantes |
| Idempotência do callback do Google | já é CT-23 da wiki ancestral |
| O `state` de CSRF por provedor (o callback direto, sem `state` na sessão) | já é CT-05 da wiki ancestral, e o `->stateless()` continua fora nos quatro (ADR-09 item 1). Um cenário por provedor mataria o mesmo mutante quatro vezes, e três deles teriam de rodar **sem** `Socialite::fake()` — o fake ignora a verificação de state |
| 2FA não contornado por provedor | já é CT-16 da wiki ancestral. A barreira é o middleware do Breezy e não depende de qual provedor autenticou |
| Trilha de acesso (`authentication_log`) por provedor | já é CT-21 da wiki ancestral. O evento é do `Auth::login()`, um só. **Parcialmente reaproveitado**: CT-14 usa a contagem de trilha como âncora de idempotência |
| Escape do rodapé da tela de login | já é CT-15 da wiki ancestral; esta entrega não toca o rodapé |
| Idempotência da migration de normalização rodando duas vezes | o `up()` faz `add()` das nove, que estoura na segunda execução. O que interessava — não cifrar duas vezes — é a linha 1 de CT-23 |
| `throttle:10,1` compartilhado pelos quatro provedores | mede o middleware do Laravel. A consequência está aceita por escrito no ADR-02 |
| `client_id` **não** cifrado (o contraste de CT-20) | nenhum mutante plausível: cifrar o `client_id` também não produz defeito observável |
| O `redirect` como campo na tela de Settings | pergunta A-02, não cenário. Nada no requisito pede o campo |
| Rotação de `APP_KEY` deixando os quatro segredos ilegíveis | o comportamento esperado é a exceção do próprio Laravel (ADR-06, "riscos") |

---

## Regressão Obrigatória

Tipo `evolução` + infra compartilhada. Os três arquivos da wiki ancestral **e** os da wiki
`settings-do-kit` rodam sem nenhum caso removido.

| Arquivo | O que muda, e por quê |
|---|---|
| `tests/Kit/LoginSocialGoogleTest.php` | **três** pontos de edição, não dois — em **dois assuntos**, não um: (1) o prefixo e o texto do log de sucesso; (2) `'Autenticado pelo Google'` na asserção **negativa** de CT-20; (3) as **duas** chamadas a `ConfiguracaoDoLogin::googleDisponivel()` em `it('deixa o settings governar o botao do google e o rodape')`, método que o passo 2 do PRD remove — **não é log**, e o PRD não o prevê. **Estado conferido nesta data**: os três já estão feitos no worktree — `:729` e `:751` com `Autenticado pelo provedor`, e `:808`/`:819` com `disponivel(ProvedorSocial::Google)`. A contagem do PRD ("2 asserções de log") estava errada; quem implementou varreu além dela |
| `tests/Tenancy/LoginSocialGoogleTenancyTest.php` | nenhuma edição prevista — usa URL literal e não asserta log. Roda como controle |
| `tests/Browser/LoginSocialGoogleTest.php` | o `aria-label="Entrar com Google"` é preservado de propósito (ADR-08), então nenhuma edição. Roda como controle do CT-B01 novo |
| Casos de `settings-do-kit` sobre `mail_password` e o mapa | `encrypted()` muda de 1 para 5 nomes. `tests/Kit/ConfiguracoesDoKitTest.php:324` (a senha cifrada e legível) é o controle de que a mudança não quebrou o campo antigo |

Base da suíte: **1016** casos (`## Verificação` do PRD). Não pode cair.

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | tabela de decisão da exibição do botão, por provedor | R1 | tabela de decisão × EP | Feature | `tests/Kit/LoginSocialProvedoresTest.php` | M1–M5 |
| CT-02 | a lista de disponíveis traz exatamente os completos | R1 | EP | Feature | idem | M6, M7 |
| CT-03 | 404 nas duas rotas × 2 motivos × 4 provedores | R2 | estado × operação | Feature | idem | M8, M9, M11, M12, M14 |
| CT-04 | as células válidas: as duas rotas no ar | R2 | estado × operação | Feature | idem | M10, M12, M13 |
| CT-05 | ligar só o GitHub não põe outro no ar | R3 | isolamento | Feature | idem | M15–M17, M19, M100 |
| CT-06 | desligar o Google não derruba o GitHub | R3 | isolamento | Feature | idem | M15, M18 |
| CT-07 | provedor fora do enum responde 404 | R4 | EP inválida | Feature | idem | M20–M24 |
| CT-08 | o botão, o ícone e o href por provedor × 3 painéis | R5 | EP exaustiva | Feature | idem | M25–M28, M30, M31 |
| CT-09 | sem provedor, sem divisor "ou" | R5 | EP | Feature | idem | M29 |
| CT-10 | partição exaustiva de verificação do LinkedIn | R6 | EP exaustiva | Feature | idem | M32–M34 |
| CT-11 | o X decide pela presença do e-mail | R6 | EP + fronteira de string | Feature | idem | M35, M37, M38 |
| CT-12 | o mesmo payload, veredictos diferentes | R6 | tabela de decisão | Feature | idem | M34–M36 |
| CT-13 | e-mail ausente, isolado, por provedor | R6 | partição inválida isolada | Feature | idem | M39, M40 |
| CT-14 | idempotência do callback, por provedor novo | R6 | idempotência no agregado | Feature | idem | M41, M42 |
| CT-15 | tabela de decisão de `/user/emails` | R7 | tabela de decisão | Feature | idem | M43–M48 |
| CT-16 | comparação normalizada do e-mail do GitHub | R7 | normalização | Feature | idem | M49 |
| CT-17 | token, URL e uma única chamada | R7 | rastreio de efeito | Feature | idem | M50, M51 |
| CT-18 | a falha da conferência loga alerta e não sucesso | R7 | rastreio de efeito | Feature | idem | M53–M55 |
| CT-19 | os outros três não fazem chamada HTTP | R7 | rastreio de efeito (negativo) | Feature | idem | M51, M52 |
| CT-20 | ciphertext, leitura e config, por provedor | R8 | rastreio de efeito × EP | Feature | `tests/Kit/SegredosDoSettingsTest.php` | M56–M59 |
| CT-21 | o segredo salvo pela tela também fica cifrado | R8 | rastreio de efeito | Livewire | idem | M60 |
| CT-22 | a trilha guarda a máscara | R8 | rastreio de efeito | Livewire | idem | M56, M61 |
| CT-23 | as três partições do valor já gravado | R9 | EP | Feature | idem | M62–M65, M68 |
| CT-24 | a normalização não toca em mais nada | R9 | rastreio de efeito (negativo) | Feature | idem | M66 |
| CT-25 | o rollback apaga nove e preserva a do Google | R9 | estado × operação | Feature | idem | M67 |
| CT-26 | segredo fora do HTML da tela de configurações | R10 | par obrigatório × EP | Feature | `tests/Kit/SegredosDoSettingsTest.php` | M69, M70, M73 |
| CT-27 | segredo fora do HTML da tela de login | R10 | par obrigatório × EP | Feature | idem | M74 |
| CT-28 | o save em branco não apaga o segredo | R10 | par obrigatório × EP | Livewire | idem | M72 |
| CT-29 | preencher substitui o guardado | R10 | contrapeso | Livewire | idem | M71 |
| CT-30 | visibilidade dos campos segue o interruptor | R11 | estado × campo | Livewire | idem | M75, M76 |
| CT-31 | ligar um não abre os campos de outro | R11 | isolamento | Livewire | idem | M77 |
| CT-32 | uma seção por provedor, e o rodapé fora | R11 | EP exaustiva | Livewire | idem | M78, M79 |
| CT-33 | gravar pela tela põe o botão e tira o 404 | R12 | rastreio de efeito | Livewire + Feature | idem | M84, M86 |
| CT-34 | desligar derruba a rota sem apagar a credencial | R12 | estado × operação | Livewire + Feature | idem | M85, M86 |
| CT-35 | cada propriedade nova alcança a chave dela | R12 | EP exaustiva das 9 | Feature | idem | M59, M81–M84 |
| CT-36 | o log de sucesso, por provedor | R13 | rastreio de efeito | Feature | `tests/Kit/LoginSocialProvedoresTest.php` | M87–M89, M95 |
| CT-37 | o motivo de cada barreira | R13 | rastreio de efeito | Feature | idem | M90–M92 |
| CT-38 | o log da ida nomeia o provedor | R13 | rastreio de efeito | Feature | idem | M93, M95 |
| CT-39 | abrir a tela de login não loga nada | R13 | rastreio de efeito (negativo) | Feature | idem | M94 |
| CT-40 | a coerção de cada interruptor | R14 | EP de coerção | Feature | idem | M96–M100 |
| CT-41 | o enum é a única lista | R15 | invariante estrutural | Feature | idem | M101–M103 |
| CT-42 | os termos declarados nos três arquivos | R16 | presença | Feature | idem | M98, M104–M107 |
| CT-43 | os quatro botões com a tenancy ligada | R17 | regressão de ramo | Feature | `tests/Tenancy/LoginSocialProvedoresTenancyTest.php` | M108 |
| CT-44 | o GitHub leva ao painel da organização | R17 | regressão de ramo | Feature | idem | M109–M111 |
| CT-04b | a volta trata a recusa em vez de estourar | R2 | estado × operação | Feature | `tests/Kit/LoginSocialProvedoresTest.php` | M13 |
| CT-07b | o segmento não é normalizado antes do enum | R4 | EP inválida, arranjo invertido | Feature | idem | M23 |
| **CT-45** | **as quatro células de conta × registro aberto** | **R18** | tabela de decisão 2×2 | Feature | idem | M112–M115, M118 |
| **CT-45b** | **a conta criada nasce verificada e com o nome certo** | **R18** | valor concreto | Feature | idem | M116, M117 |
| **CT-46** | **o default do `config/services.php`, sem arranjo** | **R15** | medir o default do arquivo | Feature | idem | M127 |
| **CT-47** | **duas contas parecidas, só a certa autentica** | **R19** | desambiguação de identidade | Feature | idem | M119–M121 |
| **CT-48** | **o destino segue o painel de origem** | **R19** | EP por painel | Feature | idem | M122, M123 |
| CT-41b | nada sobrando fora do enum | R15 | invariante estrutural | Feature | idem | M103 |
| CT-42b | a recusa vem com o motivo, na mesma seção | R16 | proximidade + ausência | Feature | idem | M107 |
| **CT-49** | **a barreira derruba as rotas com a tenancy ligada** | **R17** | célula inválida do ramo | Feature | `tests/Tenancy/LoginSocialProvedoresTenancyTest.php` | M128 |

**Mutantes sem matador**: nenhum. Duas **lacunas declaradas** (concorrência na normalização, em
R9; env com espaços nas bordas, em R14), as duas com o que foi tentado escrito.

**Cinco falso ✅ corrigidos na revisão adversarial** — M12, M14, M23, M34 e M89 estavam marcados
como mortos por cenários que **não** os matavam, e M100 apontava para o cenário errado. Falso ✅ é
pior que lacuna declarada, porque ninguém volta a olhar: cada linha de mutante agora registra o que
a rodada 1 citava e por que não funcionava.

---

## Revisão Adversarial

Obrigatória no perfil `completo`, e **não** pode ser autorrevisão: modelos de linguagem são
melhores em **gerar** oráculo do que em **classificar** se um oráculo está correto, então o mesmo
agente conferindo o próprio conjunto reproduz o viés que o gerou.

**Rodada 1: executada e fechada.** Delegada a sub-agente independente, que recebeu **só** o
`00-requisito.md` e o `04` da rodada 1 — sem o PRD, sem os ADRs, sem o código e sem o raciocínio
da derivação.

**23 achados. Nenhum descartado.** O saldo: **5 cenários novos** (CT-45, CT-46, CT-47, CT-48,
CT-49) mais 4 desdobramentos (CT-04b, CT-07b, CT-41b, CT-42b, CT-45b), **2 regras novas** (R18,
R19), **1 regra escalada de perfil** (R15: `mínimo` → `padrão`), **9 oráculos reescritos** e
**5 citações de mutante corrigidas — todas elas eram falso ✅**.

### O que a revisão provou, e é o que importa registrar

Cinco implementações erradas plausíveis passavam pelos 44 cenários da rodada 1:

| # | A implementação errada | Por que passava | Fechado por |
|---|---|---|---|
| 1 | recusa `conta_inexistente_registro_fechado` **sempre**, sem consultar o registro aberto — GitHub, LinkedIn e X nunca criam conta | só 1 das 4 células de `conta × registro` tinha cenário, e era a inválida | **R18** (CT-45, CT-45b) |
| 2 | `emailVerificado()` lê o **atributo mapeado** em vez de `getRaw()` — em produção nenhum login de Google entra | `User::fake()` popula bruto **e** atributo, então todo cenário faked concorda | duas linhas novas em **CT-10** + `usuarioSocialFalso()` sem `map()` |
| 3 | `Socialite::driver('google')` fixo, parâmetro só no guarda e no log — os quatro botões vão para o Google | o oráculo era `socialite.fake`, igual para as quatro linhas | **CT-04** reescrito, sem fake, com host + `client_id` + `redirect_uri` |
| 4 | `config/services.php` com os quatro `redirect` apontando para `/auth/google/callback` | nenhum cenário lia o arquivo de fábrica — `ligarProvedor()` escrevia tudo em memória | **CT-46** |
| 5 | `where('email','like',$email)` — quem controla `ja_tem@` entra na conta `ja.tem@` | **nenhum cenário tinha duas contas no banco** | **R19** (CT-47) |

### Achados por categoria, e o que virou cada um

| Categoria | Achados | Fechamento |
|---|---|---|
| **Célula de matriz ausente** | criação de conta (4 células, 1 escrita); barreira com tenancy (só células válidas) | R18 (CT-45); CT-49 |
| **Oráculo sem valor concreto** | `Então o veredicto é "<x>"` em 23 linhas de CT-10/CT-11/CT-15; CT-13 "redireciona para a tela de login"; CT-41 "as rotas aceitam" | os dois veredictos definidos **uma vez** em `## Setup Global`; CT-13 com 302 + painel; CT-41 reescrito |
| **Tautologia** | CT-28 comparando `null` com `null` nos outros três segredos | CT-28 arranja os **quatro** com valores distintos, e nomeia os quatro no `Então` |
| **Asserção de status negativa** | CT-33 "deixa de responder 404" (500, 403 e 302 errado satisfaziam) | 302 + host do provedor + `client_id` digitado |
| **Superfície de efeito incompleta** | CT-36 afirmava só sobre "a mensagem"; a fuga é o **contexto** | mensagem **e** contexto, mais o payload bruto |
| **Invariante estrutural parcial** | CT-41 conferia 2 de 7 superfícies derivadas — R15 **é** RQ-02 | CT-41 com as sete + CT-41b (nada sobrando) |
| **Linha de `Exemplos:` decorativa** | CT-07 `GOOGLE`/`google%20` (arranjo desligado); CT-42 `Discord`/`Facebook` (mera menção) | CT-07b com o `google` **ligado**; CT-42b exigindo provedor + motivo na mesma seção |
| **Mais de um `Quando`** | CT-12 (três callbacks, com dependência de ordem); CT-39 (três telas) | os dois viraram `Esquema`, uma linha por provedor / painel |
| **`Quando` tautológico** | CT-32 e CT-41 ("quando o formulário é montado") | aceito e declarado: são cenários de **existência de componente**, e o `Quando` deles é a montagem. Não viram comportamento — o comportamento é CT-30/CT-31 |
| **Citação de mutante falsa** | M12, M14, M23, M34, M89, M100 | as seis reapontadas, cada uma com o motivo da correção escrito na própria linha |
| **Achado de forma** | a seção declarava "7 achados" e remetia a uma seção inexistente | corrigido **antes** de a revisão voltar; era número inventado, e está registrado aqui como o erro que foi |

### Rodada 2

**Não executada, e a decisão é declarada.** A regra de fechamento manda re-revisar **uma vez** se o
fechamento criou cenário **novo** — e criou cinco. A rodada 2 fica **pendente** e é o próximo passo
desta wiki, antes de a implementação fechar. O que ela precisa atacar, por ordem de superfície nova
introduzida: **R18** (a tabela de criação, que trouxe as noções de destino e de `email_verified_at`),
**R19** (que trouxe a segunda conta no banco e o painel de origem) e **CT-04** (que trouxe o único
cenário sem `Socialite::fake()` do arquivo).

> **Teto de 2 rodadas.** Achado estrutural na rodada 2 não vira rodada 3: significa que a regra
> deveria ser duas, e isso se registra e escala.

---

## Fechamento do Ciclo (pós-implementação)

```bash
vendor/bin/pest tests/Kit/LoginSocialProvedoresTest.php --mutate --path=app/Support --min=80
vendor/bin/pest tests/Kit/SegredosDoSettingsTest.php --mutate --path=app/Settings --min=80
vendor/bin/pest tests/Kit/LoginSocialProvedoresTest.php --mutate --path=app/Http/Controllers/Auth
```

- Exige driver de cobertura. **Sem PCOV neste ambiente**, o `--mutate` com Xdebug é lento —
  escopar por `--path` é obrigatório, nunca mutar o projeto inteiro.
- `pestphp/pest-plugin-mutate ^5.0` está declarado em `composer.json:93` (direto, não
  transitivo) — conferido.
- **Armadilha**: `covers(X::class)` restringe o que conta como coberto; mutante fora do
  `covers()` é reportado `uncovered` e o score vai a 0%. Estes arquivos não declaram `covers()`.
- Mutante sobrevivente **não** é ajuste de teste: é lacuna de derivação. A tradução está na
  tabela de operadores da skill, e o cenário novo entra numa das 17 regras acima.
- **E o que o mutation score não responde**: cláusula que nunca virou código não gera mutante e
  não derruba o score. Quem responde por omissão é o `## Mapa de Regras` (RQ → regra → cenário) e
  o gate de mutantes de especificação de cada regra.
