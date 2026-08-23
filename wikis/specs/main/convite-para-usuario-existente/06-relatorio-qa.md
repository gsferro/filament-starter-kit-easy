# Relatório de QA — Convite para quem já tem conta

**Data**: 2026-08-23 · **Ciclo**: 1 de 3
**Oráculo**: `01-plano-acao.md` — **fraco**, e declarado

> A wiki não tem `00-requisito.md`, e ao contrário das seis irmãs de `main/` também **não** tem
> a seção "o que o usuário pediu, nas palavras dele". O que existe é a seção "Objetivo"
> (`01:3-21`), escrita pelo mesmo agente que escreveu o plano. Confrontar entrega contra plano
> mede fidelidade de execução, não de requisito: uma cláusula que o usuário pediu e o plano
> esqueceu é invisível daqui.
>
> A frase que mais se aproxima de intenção de negócio é `01:11-12` — *"a consultora que atende
> dois clientes, a funcionária que trabalha em duas unidades"* —, e ela é ilustração do autor,
> não citação.

**Veredito: APROVADO.** Nenhum achado de implementação. Dois de especificação, os dois de
deriva: documento que envelheceu enquanto o código andava.

---

## Por que este relatório é curto, e isso é o resultado

Esta wiki era a próxima da fila de risco alto na triagem das 18 (ficou fora do lote anterior só
pelo teto de três). A expectativa era encontrar o mesmo padrão das outras: cláusula
especificada que nunca virou teste. **Não é o caso.**

| Verificação | Resultado |
|---|---|
| CT especificados | 17 numerados, **16 reais** — CT-03 não existe |
| A ausência de CT-03 está declarada? | **Sim**, `03-progresso.md:152` |
| CT com teste correspondente | **16 de 16** — todos citados em `tests/Kit/ConviteUsuarioExistenteTest.php` (4 casos) e `tests/Tenancy/ConviteUsuarioExistenteTest.php` (12) |
| Cláusulas de "Autorização" com guarda | as duas: posse de token **e** `$user->email === $convite->email` |
| Promessas de "Impacto em Features Existentes" | 5 de 6 conferidas no código; a 6ª é a deriva de QA-02 |

Numeração pulada **com a lacuna declarada** é higiene, não defeito: quem for escrever CT-18
não vai reusar o 03 por acidente.

---

## O que sustenta a nota — a barreira que a feature inteira protege

O plano diz (`01:118-127`) que aceitar exige **duas** condições independentes, e que a segunda
vive no model, não na tela. Conferido:

- `Convite::aceitarComoUsuarioExistente()` chama `exigirDono($user, 'aceitarComoUsuarioExistente')`
  (`app/Models/Convite.php:633`) **antes** de qualquer `update` ou `attach`.
- O método tem **dois** chamadores hoje — `ConvitesRecebidos.php:90` (a caixa de entrada) e
  `RegistroPorConvite.php:106` (o link do e-mail). É exatamente o cenário que o plano usou para
  justificar pôr a asserção no model: o segundo chamador chegou, e a barreira valeu para ele de
  graça.
- CT-04 (`tests/Kit/ConviteUsuarioExistenteTest.php:54`) chama o método **direto**, com o
  usuário errado, sem tela no caminho, e cobra a exceção **mais** a ausência de linha em
  `tenant_user`. É o teste que fica vermelho se alguém "simplificar" a asserção alegando que a
  query da tela já filtra — que é o furo declarado do `jeffersongoncalves/filament-teams`.

Dimensão I (segurança da superfície nova): **aprovada**, e por evidência direta, não por
ausência de achado.

### Duas promessas verificadas no código

| Promessa do plano | Onde | Estado |
|---|---|---|
| `ConviteForm` perde o `unique('users','email')` | `ConviteForm.php:38` — o comentário diz *"SEM `->unique('users','email')`, e isto é a feature"* | ✅ |
| `recusado_em` entra no `$fillable`, logo na trilha de `/infra/audits` | `Convite.php:77-79`, com o comentário separando-o do `token`, que fica fora | ✅ |

---

## QA-01 — a wiki chama o papel de `admin_organizacao`; ele é `admin_app` · Minor · destino 1

- **Dimensão**: adequação da especificação
- **Observado**: 4 ocorrências de `admin_organizacao` (1 no `01`, 1 no `03`, 2 no `04`). O papel
  foi renomeado em `database/migrations/2026_08_16_000001_rename_admin_organizacao_role.php`, e
  `grep -rn admin_organizacao app/` não devolve nada.
- **Por que Minor e não cosmético**: o nome aparece dentro de CT-17 (*"o admin da organização
  convida quem já tem conta"*), então quem for reescrever aquele caso a partir do `04` procura
  um papel que não existe e conclui que o cenário mudou.
- **É o mesmo achado de QA-02 da wiki `admin-da-organizacao`**, onde a contagem foi 39
  ocorrências. A renomeação teve migration e dois testes de retrofit, e **nenhuma wiki**.

## QA-02 — o CT-15 da wiki irmã continua dizendo o contrário do código · Minor · destino 1

- **Dimensão**: rastreabilidade entre wikis
- **Relacionado a**: `01:155` — a própria tabela de "Impacto em Features Existentes" desta wiki
  **previu** isto: *"CT-15 de `convite-de-usuario` … **inverte de sentido**: passa a provar que
  o convite é criado e que o aceite vincula em vez de recusar"*.
- **Observado**: `wikis/specs/main/convite-de-usuario/04-casos-de-teste.md:535` segue com
  *"CT-15: e-mail já cadastrado é recusado nas duas pontas"*, e o índice em `:623` repete. O
  teste real chama-se `it('convida quem ja tem conta em vez de recusar')`.
- **O que este achado é de fato**: não é a wiki irmã ter envelhecido — é **esta** wiki ter
  identificado o impacto, escrito no plano, e ninguém ter fechado o laço. Impacto previsto e
  não aplicado é pior que impacto não previsto: existe registro de que se sabia.
- **Já registrado** como QA-03 do `06-relatorio-qa.md` de `convite-de-usuario`. Aqui entra
  porque a origem da dívida é esta feature.

---

## Hipóteses testadas e **rejeitadas**

Registradas porque a rejeição custou o mesmo que o achado custaria, e porque um relatório sem
elas parece que só procurou onde achou.

| Hipótese | Veredito | O que a derrubou |
|---|---|---|
| *"Sobrou chamador de `Convite::aceitar()` esperando a `RuntimeException` que o plano removeu"* | **Rejeitada** | O plano dizia *"só o `RegistroPorConvite` chama"*; segue verdade — `RegistroPorConvite.php:154` é o único |
| *"A asserção de e-mail é redundante com a query da caixa de entrada"* | **Rejeitada** — é o furo do teamkit | Dois chamadores hoje; a query só protege um |
| *"Usuário sem organização fica sem caminho de aceite"* | **Não é defeito** | Declarado em ADR-05 e nos Riscos: a caixa de entrada exige tenant, o **link** cobre esse caso. Cláusula com decisão escrita |
| *"CT-03 foi perdido numa renumeração"* | **Rejeitada** | `03-progresso.md:152` declara a lacuna |

---

## Matriz de Rastreabilidade

Só as linhas que precisam de veredito; as 16 correspondências CT ↔ teste estão confirmadas em
bloco acima.

| Cláusula do plano | Código | Teste | Estado |
|---|---|---|---|
| aceite exige token **e** e-mail conferido no model | `Convite.php:633` | CT-04, direto no model | ✅ |
| caixa de entrada escopada por e-mail (conveniência de UI) | `ConvitesRecebidos.php` | CT-05 | ✅ |
| `ConviteForm` sem `unique('users','email')` | `ConviteForm.php:38` | CT-01 | ✅ |
| aceite idempotente para quem já é membro | `syncWithoutDetaching` | CT-07 | ✅ |
| aceite concorrente consome uma vez | `update` condicional na transação | CT-06 | ✅ |
| recusa registra e invalida | `recusado_em` + `valido()` | CT-09, CT-10 | ✅ |
| `recusado_em` na trilha de auditoria | `Convite.php:77-79` | — nenhum | ⚠️ ver nota |
| papel do convite aplicado no contexto certo | `atribuirPapel()` | CT-02, CT-17 | ✅ |
| nome do papel na wiki | — | — | ❌ **QA-01** |
| impacto no CT-15 da wiki irmã | — | — | ❌ **QA-02** |

> **Nota sobre a trilha de auditoria**: `recusado_em` estar no `$fillable` é o que o põe na
> trilha, e nenhum CT assere que a linha aparece em `/infra/audits`. **Não** virou achado: a
> auditoria vem de `AuditsFillables`, que é fundação com teste próprio, e um CT aqui testaria o
> pacote de auditoria em vez da feature. Registrado para que a ausência seja lida como decisão,
> não como esquecimento.

---

## Dimensões

| | Dimensão | Estado | Nota |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | 16/16 CT cobertos; o ⚠️ é do **oráculo**, não da cobertura |
| B | Fronteiras | ✅ | e-mail divergente, token consumido, recusado, concorrência |
| C | Matriz de permissão | ✅ | CT-17 cobre o `admin_app`; sem policy nova |
| D | Log real | ✅ | canal `autenticacao`, aceite `info` e divergência `warning`, sem segredo no contexto |
| E | N+1 | ✅ | caixa de entrada é uma query por `pendentesPara()` |
| F | UX de erro | ✅ | três destinos do desvio, todos com mensagem |
| G | Tema/dark mode | n/a | nenhuma tela nova de layout |
| H | Acessibilidade | n/a | idem |
| I | Segurança da superfície nova | ✅ | ver a seção da barreira |
| J | Regressão adjacente | ⚠️ | QA-02: o impacto previsto na wiki irmã não foi aplicado |
| K | Adequação da suíte | ✅ | CT-04 é oráculo forte — chama o model direto, sem tela |

---

## Ações

| # | Ação | Destino | Estado |
|---|---|---|---|
| 1 | Corrigir `admin_organizacao` → `admin_app` nas 4 ocorrências | 1 | aplicado nesta rodada |
| 2 | Corrigir o CT-15 da wiki `convite-de-usuario` | 1 | aplicado nesta rodada |

Nenhuma ação de destino 2 (implementação) ou 3 (teste). É o primeiro gate desta auditoria que
fecha sem defeito de código.
