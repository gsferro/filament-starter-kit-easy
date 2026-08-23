# Relatório de QA — Convite de usuário

> Requisito: **não existe `00-requisito.md`** · Plano: `01-plano-acao.md` · Casos: `04-casos-de-teste.md`
> Perfil de esforço: **completo** (domínio sensível — credencial, rota pública, e-mail, concessão de acesso)
> Natureza da wiki: nova, sobre infra compartilhada · Regressão: sim (abre a primeira rota pública do `/app`)
> Confrontado contra o código de `main` em `e196f20` (v0.18.2).

> ⚠️ **ORÁCULO FRACO — sem `00-requisito.md`** e **sem** a seção "O que o usuário pediu, nas
> palavras dele" que a irmã `admin-da-organizacao` tem (conferido por `grep`). O oráculo foi
> o `01-plano-acao.md`: **o que foi planejado, não o que foi pedido**. Consequência
> declarada: a dimensão A pega cláusula do plano sem teste, mas não pega pedido do usuário
> que nunca chegou ao plano.

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 3 · Cosmético: 0
- Ambiente: sem app servido; Pest 5.0.5; sqlite `:memory:`; `MAIL_MAILER=array`; MCP não usado
- Suítes: sondas efêmeras em `tests/Kit/` (apagadas); leitura integral de
  `tests/Kit/ConviteTest.php` (23 casos) e `tests/Tenancy/ConviteTenancyTest.php` (4 casos)

Os 16 CT do `04` têm todos teste equivalente rodando. A fundação de credencial é boa: token
`Str::random(64)`, gravado como `sha256`, fora do `$fillable` **e** do `$hidden`, uso único
por `aceito_em`, prazo por `expira_em`, e o `where(closure)` que impede o `OR` de partir o
`WHERE` tem CT dedicado. Os achados são de borda.

## Achados

### QA-01 — a rota pública de aceite não tem throttle, e cada tentativa recusada escreve no log · Minor · destino 2

- **Dimensão**: I (segurança da superfície nova) + D (observabilidade)
- **Relacionado a**: passo 4 do PRD, `01:257` (*"Ganha uma rota **pública** nova ... A guarda
  do `mount()` é a única coisa entre essa rota e um cadastro aberto"*), `01:286`
  (Dependências: *"Rate limit da tela | `danharrin/livewire-rate-limiting` (transitiva do
  Filament, usada em `Register.php:45`)"*), e o docblock de
  `app/Filament/Pages/Auth/RegistroPorConvite.php:24-26` (*"Rate limit (por IP e por
  e-mail) ... vêm todos da página do Filament"*).
- **Esperado**: pela leitura do plano e do docblock, a tela de aceite herda rate limit do
  Filament.
- **Observado**: o rate limit do Filament está em `register()`, **não** em `mount()` —
  `vendor/filament/filament/src/Auth/Pages/Register.php:73` (`$this->rateLimit(2)`) e
  `:135-148` (`isRegisterRateLimited()`, chave `filament-register:sha1($email)`), ambos
  dentro de `register()`. O `mount()` do Filament (`:57-63`) não tem nenhum. E o
  `RegistroPorConvite::mount()` do kit chama `recusar()` **antes** de
  `parent::mount()`, então o caminho de recusa **nunca** alcança um throttle. Cada `GET`
  recusado escreve um `warning` em `storage/logs/autenticacao-*.log`
  (`RegistroPorConvite::recusar()`), sem autenticação e sem limite.
- **Repro** (sonda efêmera em `tests/Kit/`):
  1. seeders no `beforeEach`; espionar o channel `autenticacao`
  2. 12× `$this->get('/app/register?token='.bin2hex(random_bytes(32)))`
- **Evidência**: `status das 12 tentativas => [302 => 12]` — nenhum 429 — e o canal recebeu
  `warning` **12 vezes**.
- **O que este achado NÃO é** — hipótese testada e **rejeitada**: *"sem throttle o token é
  adivinhável"*. **Não é.** `Str::random(64)` sobre o alfabeto de 62 caracteres
  (`Convite::enviar()`, `Convite::lembrar()`) não é força-brutável, e a resposta é a mesma
  para os três motivos de recusa (ADR-02), então não há oráculo de estado. O que sobra é
  **amplificação de log**: uma linha de arquivo por request anônimo, driver `daily`, 14 dias
  de retenção — e o mesmo arquivo é o que o Logs Explorer do `/infra` abre na tela.
- **Destino**: 2 (implementação) e, junto, 1 — porque a **afirmação sobre o vendor** é o que
  sustentou a decisão de não escrever throttle próprio, e ela é imprecisa como está escrita.
  É o padrão que `.ai/rules/specs.md` registra: frase sobre vendor cuja conclusão passa por
  certa por outro motivo.
- **Ação exigida**: (a) corrigir o docblock e a linha de Dependências para dizer *"rate
  limit no envio do formulário; o caminho de recusa do `mount()` não passa por ele"*; e
  (b) decidir entre throttle na rota, `RateLimiter` no `recusar()` ou rebaixar o log a
  `debug`. **Severidade sobe a Major** numa instalação sem throttle de borda: o disco enche
  com `curl` num laço.

### QA-02 — cláusula `KIT_CONVITE_VALIDADE_DIAS` (passo 7) sem nenhum teste · Minor · destino 3 · ✅ **RESOLVIDO em 2026-08-22**

- **Dimensão**: A (omissão silenciosa) + K
- **Relacionado a**: passo 7 do PRD, `01:217-222` (seção "Variáveis de Ambiente"),
  `config/kit.php:234`, `app/Models/Convite.php:158`
- **Esperado**: o prazo do convite é configurável — é a **única** cláusula de configuração
  que a wiki introduz, e o prazo é o único limite temporal da credencial.
- **Observado**: nenhum teste da suíte lê `kit.convites.validade_em_dias` nem o env
  correspondente (`grep -rn "validade_em_dias\|KIT_CONVITE_VALIDADE" tests/` volta vazio).
  A única asserção próxima é `expect($convite->expira_em?->isFuture())->toBeTrue()`
  (`tests/Kit/ConviteTest.php:136`), que passa com qualquer valor futuro.
- **Mutantes que sobreviveriam**, todos em `Convite::enviar()`:
  `now()->addDays((int) config('kit.convites.validade_em_dias', 7))` → `now()->addDays(7)`
  (o projeto configura 1 dia e continua com 7, em silêncio); → `now()->addYears(1)`; →
  `addDays(config(...) + 1)`. Nenhum quebra teste algum.
- **Repro**: leitura + `grep`. Para ver falhar: fixar `KIT_CONVITE_VALIDADE_DIAS=1` e
  conferir que `expira_em` continua em D+7 se a linha for trocada pelo literal.
- **Destino**: 3 (teste). Um caso: `config(['kit.convites.validade_em_dias' => 1])`,
  `enviar()`, `expect($convite->expira_em->diffInDays(now()))->toBe(1)` — e o irmão que
  prova o default 7 sem env.

### QA-03 — CT-15 do `04` diz o contrário do que o código faz hoje · Minor · destino 1 · ✅ **RESOLVIDO em 2026-08-23**
 · ✅ **RESOLVIDO em 2026-08-23**
- **Dimensão**: A (rastreabilidade)
- **Relacionado a**: `04:535-573` (CT-15: *"e-mail já cadastrado é recusado nas duas
  pontas"*), `03-progresso.md:175`, `App\Models\Convite::aceitar()`
- **Esperado / Observado**: o `04` ainda descreve a recusa. O `03` registra uma **primeira**
  reescrita (a ponta 2 passou a ser o `->unique()` do Filament). Depois disso a wiki irmã
  `convite-para-usuario-existente` **inverteu a decisão inteira**: `Convite::aceitar()` hoje
  desvia para `aceitarComoUsuarioExistente()` em vez de lançar
  `RuntimeException('E-mail já cadastrado.')`, e o teste correspondente se chama
  `it('convida quem ja tem conta em vez de recusar')` (`tests/Kit/ConviteTest.php:404`).
  Nenhum dos dois documentos desta wiki registra a inversão.
- **Repro**: `grep -n "convida quem ja tem conta" tests/Kit/ConviteTest.php` vs. `04:535`.
- **Destino**: 1 (especificação). Cadeia de deriva em três saltos (`04` → `03` → wiki irmã)
  num CT cujo assunto é a porta de entrada da aplicação: quem for validar a feature por CT
  vai testar a regra revogada.

## Matriz de Rastreabilidade

Oráculo = `01-plano-acao.md` (fraco). Todos os 16 CT têm teste; a coluna "Veredito" só marca
o que a wiki declara e a realidade contradiz, mais a cláusula de plano sem CT.

| CT / cláusula | Passo PRD | Código | Teste real | Veredito |
|---------------|-----------|--------|------------|----------|
| CT-01 notificação disparada | 8 | `CreateConvite::afterCreate` → `Convite::enviar()` | `cria convite pela tela e dispara a notificacao` | OK |
| CT-02 token inválido **e ausente** | 4 | `RegistroPorConvite::mount` + `Convite::valido` (`blank`) | `recusa registro com token inexistente` (cobre os dois) | OK |
| CT-03 token expirado | 2 | `Convite::valido` (`expira_em > now`) | `recusa registro com convite expirado` | OK |
| CT-04 reuso + log sem token | 2, 4 | `whereNull('aceito_em')`, `recusar()` | `recusa reuso do convite e loga sem expor o token` | OK |
| CT-05 aceite cria usuário com o papel | 2 | `Convite::aceitar` + `atribuirPapel` | `aceita o convite e cria o usuario com o papel` | OK |
| CT-06 e-mail vem do convite | 4 | `mutateFormDataBeforeRegister` | `ignora o email enviado pelo formulario…` | OK |
| CT-07 vincula a organização | 2 | `syncWithoutDetaching` | `vincula o usuario a organizacao do convite` | OK |
| CT-08 reenvio rotaciona | 2 | `enviar()` (`refresh()` + `forceFill`) | `reenvia com token novo e mata o anterior` | OK |
| CT-09 revogação + auditoria | 8 | `AuditsFillables` (token fora do `$fillable`) | `revoga o convite e o link deixa de valer` (assere a trilha) | OK |
| CT-10 log mascarado | 2 | `Str::mask($email,'*',3)` | `registra envio e aceite no channel autenticacao…` | OK |
| CT-11/12 contexto do papel | 2 | `contextoDoPapel()` | `atribui papel de app…`, `atribui papel de admin…` | OK |
| CT-13 layout do auth designer | 6 | `$layout` redeclarado | `veste o layout do auth designer sem vazar…` | OK |
| CT-14 login sem "Cadastre-se" | 5 | `TelaLogin` | `nao oferece cadastro na tela de login` | OK |
| CT-15 e-mail já cadastrado | 2 | **invertido** por wiki irmã | `convida quem ja tem conta em vez de recusar` | ✅ |
| CT-16 URL fora do segmento de org | 3 | rota nativa do painel | `mantem a url de aceite fora do segmento…` | OK |
| **passo 7 — `KIT_CONVITE_VALIDADE_DIAS`** | **7** | `Convite.php:158`, `config/kit.php:234` | `ConviteTest` — *respeita o prazo configurado* (3 e 30 dias) + *mantem os dois defaults* | ✅ |
| **rota pública sem throttle** | **4** | `RegistroPorConvite::mount/recusar` | **— nenhum** | ❌ **QA-01** |

Nenhum passo do PRD ficou sem código. Nenhum código novo ficou sem passo.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 2 cláusulas de plano sem CT (QA-01, QA-02); oráculo fraco declarado |
| B | Fronteiras e dados | ✅ | token ausente, inexistente, expirado, usado, recusado e de lembrete têm caso |
| C | Matriz de permissão | ✅ | quem convida é o `/admin` (`*:Convite` via `Paineis::permissoes('admin')`); quem aceita é o token, não um papel |
| D | Observabilidade | ⚠️ | formato, canal, nível e mascaramento corretos e **testados** (CT-04, CT-10); o volume não tem limite (QA-01) |
| E | Performance | ✅ | `valido()` é um `first()` por par de colunas; envio em `ShouldQueue` |
| F | UX de erro | ✅ | resposta única para os três motivos (por desenho, ADR-02), com notificação persistente e destino no login |
| G | Tema e cor | ⏭️ | a tela é a do Auth Designer, com `themeToggle()`; `tests/Browser/TemaEscuroTest.php` cobre o painel |
| H | Acessibilidade | ⏭️ | campo de e-mail `disabled` com `helperText`; sem CT-B próprio nesta wiki |
| I | Segurança da superfície nova | ⚠️ | **QA-01**. Token, hash, uso único, prazo e mascaramento em log: corretos e testados |
| J | Regressão adjacente | ✅ | `ConviteTest` (23), `ConviteTenancyTest` (4), `ConviteEmMassaTest`, `ConviteUsuarioExistenteTest` presentes e verdes na suíte do kit |
| K | Adequação da suíte | ✅ | CT-04 e CT-10 asserem **ausência** do segredo no `$context`, o que é oráculo forte; ver QA-02 para a lacuna |

## Débitos Aceitos

- QA-02 (Minor): teste do prazo configurável.
- QA-03 (Minor): correção documental do CT-15.

## Suspeitas Não Confirmadas

- **Força bruta do token pela rota sem throttle** — reproduzida e **rejeitada**: 64
  caracteres de `Str::random()` e resposta única para os três motivos. Ver QA-01.

## Não Verificado

- `--mutate` (dimensão K, passo 2) — sem driver de cobertura no worktree isolado. Os
  mutantes de QA-02 foram **derivados por leitura**, não medidos.
- Entrega real de e-mail (`MAIL_MAILER=array` na suíte; `log` no `.env.example`) — a wiki já
  registra isso como risco documentado, não como defeito.
- Dimensões G/H por screenshot — app não servido; Playwright MCP não usado.

---

## Fechamento parcial — 2026-08-22

**QA-02 resolvido.** `tests/Kit/ConviteTest.php` ganhou *"respeita o prazo configurado do
convite"*, com dataset de **3 e 30 dias** — valores diferentes do default de propósito, porque
com 7 o mutante do literal passaria. Visto falhando: trocado o `config()` de `Convite.php:158`
por `addDays(7)` literal, os dois datasets quebram com a data errada.

O segundo caso, *"mantem os dois defaults do prazo em sete dias"*, existe porque o default está
escrito em dois lugares (`config/kit.php:234` e o segundo argumento do `config()` no model) e
divergirem seria silencioso.

### Achado novo, não coberto e não corrigido: valor VAZIO na env

Ao escrever o caso apareceu um cenário que **nenhum teste cobre e que é alcançável**:
`KIT_CONVITE_VALIDADE_DIAS=` (chave presente, valor vazio) no `.env`. Medido:

    env('KIT_CONVITE_VALIDADE_DIAS', 7)  →  string(0) ""
    (int) ""                             →  0

O segundo argumento do `env()` só vale para chave **ausente**, não para valor vazio. Então
`now()->addDays(0)` grava `expira_em` igual ao instante do envio, e `valido()` — que exige prazo
no futuro — rejeita o convite **no primeiro clique**. O convite nasce morto, o e-mail sai, o log
registra sucesso, e quem recebe vê "convite expirado".

**✅ Corrigido em 2026-08-22**, por decisão do usuário, na fronteira onde o valor entra —
`config/kit.php`, e não no model, para valer para qualquer leitor da chave:

```php
'validade_em_dias' => max(1, (int) (env('KIT_CONVITE_VALIDADE_DIAS') ?: 7)),
```

`?:` trata vazio, `null` e `0` como não configurado — convite de zero dia nunca é intenção.
`max(1, …)` cobre negativo e texto (`(int) 'abc'` é 0), que produziriam o mesmo convite morto. O
pior caso passa a ser um convite de **um dia**: curto e visível, em vez de inválido ao nascer.

| `.env` | Antes | Depois |
|---|---|---|
| `KIT_CONVITE_VALIDADE_DIAS=` | **0** — convite morto | 7 |
| `=0` | 0 — convite morto | 7 |
| `=-5` | −5 — convite morto | 1 |
| `=abc` | 0 — convite morto | 1 |
| ausente | 7 | 7 |
| `=30` | 30 | 30 |

A coerção vive em `App\Support\ValidadeDoConvite::emDias()`, não na linha do `config/kit.php` —
e a razão de estar numa classe é a segunda metade desta história.

**A primeira guarda que eu escrevi estava errada, e o CI provou.** Ela montava
`putenv()`/`$_ENV` à mão e dava `require` no `config/kit.php`, para exercitar a expressão em vez
do valor já resolvido (que `config([...])` mediria). Passou na máquina local e **falhou no
runner**, com três datasets devolvendo o default: o que o `env()` do Laravel enxerga depende dos
adaptadores de ambiente em uso, então o caso media o runner, não a regra. Teste de coerção que
depende de plumbing de ambiente é teste do ambiente.

Com a regra num método puro, o dataset ficou determinístico em qualquer máquina — e ganhou dois
casos que a versão anterior não conseguia expressar (`0` e `30` já como `int`, não só como
string).

**Guarda**: `tests/Kit/ConviteTest.php`, caso *"nunca resolve o prazo do convite para zero ou
negativo"*, dataset de oito. Visto falhando: trocado o corpo do método por `(int) $bruto`,
**6 dos 8** quebram — o do valor vazio com *"Failed asserting that 0 is identical to 7"*.
