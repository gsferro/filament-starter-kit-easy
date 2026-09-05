# Decisões Arquiteturais — Fix: conta indisponível não pode ser personificada

## ADR-01: A barreira vive no model, não no `->visible()` da Action

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A ação Impersonate é registrada em `app/Filament/Admin/Resources/Users/UserResource.php:222`.
Bastaria acrescentar `->visible(fn (User $record) => $record->ativo)` ali para o botão desaparecer,
e seria o menor diff possível.

### Decisão

Corrigir `User::canBeImpersonated()` e **não** tocar na Action.

### Alternativas Consideradas

1. **`->visible()` na Action** — descartada por duas razões, e a segunda é decisiva:
   - `.ai/rules/filament.md:19-29` é explícito: *"A query é filtro de UI; a barreira é uma
     asserção no método do model"*, e o raciocínio que produz o furo é sempre *"a tela já
     filtra, conferir de novo no model é redundância"*. A mesma regra registra que `->visible()`
     e `->authorize()` **bloqueiam igual** e que a diferença é semântica, não enforço (`:163`);
   - o pacote consulta `canBeImpersonated()` **duas** vezes — no `visible()`
     (`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:37`) e outra vez dentro
     do `impersonate()`, antes de executar (`:112` → `:167`). Corrigir o model fecha os dois
     pontos com uma linha; corrigir a Action fecharia **um**, e o outro continuaria consultando o
     model permissivo.
2. **As duas coisas** (model + `visible()`) — descartada: seria a segunda cópia da regra, que
   divergiria no primeiro ajuste. É o mesmo argumento que impediu a cópia da precedência de cor
   em `CorPrimaria::resolver()`.

### Consequências

- **Positivas**: uma dona da regra; a proteção vale para qualquer chamador futuro (job, comando,
  action em massa, rota de API) e não só para a linha da tabela.
- **Negativas**: quem lê apenas a `UserResource` não vê a condição. Mitigado pelo docblock do
  método, que cita os dois pontos do vendor com `file:line`.

### Referências

- `.ai/rules/filament.md:19-29`, `:163`
- `vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:37`, `:112`, `:167`

---

## ADR-02: A régua é `motivoDeIndisponibilidade()`, não uma releitura de `ativo`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O requisito fala em "status desativado", que é a coluna `ativo`. A implementação literal seria
`return ! $this->isMasterGlobal() && $this->ativo;`.

### Decisão

Usar `motivoDeIndisponibilidade()` (`app/Models/User.php:236`) mais `aprovacao_pendente` — a
**mesma** pergunta que `canAccessPanel()` faz, na mesma ordem.

### Alternativas Consideradas

1. **`&& $this->ativo`** — descartada: é a terceira leitura de `ativo` como fronteira de acesso no
   mesmo model, e ignora que `deleted_at` e `aprovacao_pendente` produzem o **mesmo** estado
   observável (conta que não consegue entrar sozinha). Deixaria duas portas abertas com o
   argumento idêntico por trás.
2. **Um método novo, `estaDisponivel()`, encapsulando as duas condições** — descartada por YAGNI:
   dois chamadores (`canAccessPanel()` e `canBeImpersonated()`) e a expressão tem uma linha.
   Se um terceiro aparecer, aí sim.

### Consequências

- **Positivas**: quando um estado novo de indisponibilidade for criado, ele entra em
  `motivoDeIndisponibilidade()` e a personificação passa a recusá-lo **sozinha**.
- **Negativas**: o fix entrega mais do que o texto do requisito pediu — três estados em vez de um.
  Registrado como premissa **A1** no `00`, com o "Se negado".
- **Riscos**: se algum fluxo legítimo dependesse de personificar conta pendente (por exemplo, para
  o administrador conferir o que a pessoa verá após a aprovação), ele quebra. Não existe fluxo
  assim no kit — conta pendente nasce sem papel, então o painel não abriria de qualquer forma
  (documentado no docblock de `canAccessPanel()`).

### Referências

- `app/Models/User.php:236` (`motivoDeIndisponibilidade`), `canAccessPanel()` e seu docblock
- ADR-01 da wiki `status-e-exclusao-logica-de-usuario`

---

## ADR-03: `aprovacao_pendente` entra explícito, porque não está na indisponibilidade

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

`motivoDeIndisponibilidade()` cobre **excluída** e **inativa**, mas não a pendência —
`canAccessPanel()` a checa numa segunda condição, e o docblock de lá explica que a ordem
(indisponibilidade → pendência → atalho do `master_global`) **é** a decisão, porque o atalho do
`master_global` devolve `true` sem consultar mais nada.

### Decisão

Repetir a estrutura: `motivoDeIndisponibilidade() === null && ! $this->aprovacao_pendente`, com
`isMasterGlobal()` recusando **antes** das duas.

### Alternativas Consideradas

1. **Acrescentar a pendência dentro de `motivoDeIndisponibilidade()`** — descartada, e é a
   alternativa tentadora: unificaria as duas condições em uma. Mas aquele método alimenta a
   **mensagem** que a tela de login e o `LoginSocialController` mostram
   (`'conta_excluida'` → data da exclusão, `'conta_inativa'` → "procure o administrador"), e um
   terceiro valor mudaria o texto que a pessoa pendente vê hoje. Isso é mudança de comportamento
   fora do escopo deste fix, num caminho que a wiki ancestral desenhou de propósito.
2. **Ignorar a pendência** — descartada pelo argumento da ADR-02.

### Consequências

- **Positivas**: nenhum comportamento existente muda; a mensagem de login continua igual.
- **Negativas**: a expressão tem duas partes em vez de uma, e a explicação de por que vive no
  docblock.

### Referências

- `app/Models/User.php:236`, `canAccessPanel()`
- `App\Support\RegistroAberto::registrar()` — quem marca a pendência

---

## ADR-04: Não publicar `config/filament-impersonate.php`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A conta **excluída** já não é personificável hoje, mas por decisão do **vendor**: ele recusa
soft-deleted quando `config('filament-impersonate.allow_soft_deleted')` é falsa
(`Impersonate.php:157-159`), e o default do pacote é `false`
(`vendor/.../config/filament-impersonate.php:9`). O kit **não** publicou essa config — não existe
`config/filament-impersonate.php`. Um `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true` no `.env`
reabriria a porta.

### Decisão

**Não publicar** a config. Com a ADR-02, `canBeImpersonated()` recusa a conta excluída por
decisão do kit, e a chave do vendor deixa de ser a única guarda.

### Alternativas Consideradas

1. **Publicar a config fixando `allow_soft_deleted => false`** — descartada: acrescenta um arquivo
   de config ao projeto para reafirmar um default que já é esse, e cria a impressão de que a
   proteção depende dele. Depois deste fix, não depende.
2. **Publicar a config E corrigir o model** — descartada pelo mesmo motivo: duas guardas para o
   mesmo caso, uma delas desligável pelo `.env`.

### Consequências

- **Positivas**: um arquivo a menos; a proteção passa a ser inegociável por variável de ambiente.
- **Negativas**: se alguém ligar a chave do vendor esperando personificar conta excluída, nada
  acontece — e o motivo está no código do kit, não na config que ele mexeu. Mitigado pelo
  docblock, que cita a chave pelo nome.

### Referências

- `vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:157-159`
- `vendor/stechstudio/filament-impersonate/config/filament-impersonate.php:9`

---

## ADR-05: Log só na recusa, e só com operador autenticado

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O padrão de log do projeto pede rastro nos pontos de decisão de fluxo, e as transições vizinhas
(`desativar()`, `reativar()`, `canAccessPanel()`) todas escrevem no canal `autenticacao`. Mas
`canBeImpersonated()` é chamado pelo `visible()` **de cada linha** da tabela de usuários — logar
sem critério produz uma linha de log por usuário listado, por render.

### Decisão

Um `warning`, apenas quando a recusa acontece **e** há operador autenticado. Sem log no caminho
feliz e sem log quando não há `Auth::id()`.

### Alternativas Consideradas

1. **Logar toda avaliação** — descartada: é o ruído que a própria nota do canal `autenticacao`
   mediu em 1,1 MB/dia, e o canal é lido por uma tela do `/infra`.
2. **Não logar nada** — descartada: a recusa é um evento de fronteira de acesso, e as vizinhas
   todas registram. Auditoria de quem tentou entrar como quem tem valor.
3. **Logar dentro da Action, não no model** — descartada: a Action é só um dos chamadores (ADR-01).

### Consequências

- **Positivas**: rastro no caso que importa, silêncio no render.
- **Negativas**: a recusa disparada fora de request (comando, job, teste de model direto) não
  deixa rastro. Aceito: não há ato humano a auditar ali.
- **Riscos**: a tabela pode conter várias linhas de conta indisponível ao mesmo tempo (a listagem
  tem abas por estado), e aí o `warning` sai uma vez por linha indisponível por render. Se isso
  se mostrar ruidoso, o log é o primeiro candidato a cair — a barreira é a condição, não o log.

### Referências

- `config/logging.php:132-139` (canal `autenticacao`) e a nota longa antes do canal `ai`
- `app/Models/User.php` — `desativar()` (`:269`), `canAccessPanel()`
