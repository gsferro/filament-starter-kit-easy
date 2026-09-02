# Plano de Ação — Fix: conta indisponível não pode ser personificada

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/status-e-exclusao-logica-de-usuario/status-e-exclusao-logica-de-usuario/`
  — é ela que criou `ativo`, `motivoDeIndisponibilidade()` e a regra "conta indisponível não entra
  em painel nenhum" (ADR-01 de lá). Este fix fecha a porta que ficou aberta ao lado.
- **Motivo**: `canAccessPanel()` recusa a conta desativada; `canBeImpersonated()` não olha o
  estado, então o `master_global` entra **como** ela. Contorna a decisão da wiki ancestral.
- **Toca infra compartilhada?**: **sim** — `App\Models\User` é consumido por todos os painéis, e
  `canBeImpersonated()` é lido pelo vendor em dois pontos. Regressão obrigatória contra
  `tests/Kit/SituacaoDaContaTest.php`, `tests/Kit/PermissoesDeAcoesTest.php` e
  `tests/Kit/LoginSocialContaIndisponivelTest.php`.

> Tipo `correção` **e** infra compartilhada: a regressão roda de qualquer forma.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Conta desativada não é personificável, e a ação não aparece | 1 | o `visible()` do vendor consulta o mesmo método |
| RQ-02 | A recusa vale fora da tela também | 1 | o vendor consulta `canBeImpersonated()` antes de executar (`Impersonate.php:167`) — a correção no model cobre os dois |
| RQ-03 | Reativada volta a ser personificável | 1 | consequência de a régua ser lida por request, não gravada |
| RQ-04 | `master_global` continua fora de alcance | 1 | a condição existente é preservada |
| A1 | Pendente e excluída também recusadas | 1 | premissa assumida; ver `00` |
| — | Documentar a barreira | 2 | os estados já têm página; ganha uma frase |

## Objetivo

Fazer `User::canBeImpersonated()` recusar toda conta que o kit já recusa no login, para que
personificar deixe de ser um caminho lateral em volta de `canAccessPanel()`. Uma condição no
model, lida pelo pacote tanto para esconder a ação quanto para barrar a execução.

## Contexto

A wiki `status-e-exclusao-logica-de-usuario` decidiu que conta inativa, pendente ou excluída não
entra em painel nenhum, e concentrou a decisão em `User::canAccessPanel()`. A ação Impersonate foi
registrada em outro momento e recebeu uma guarda própria — `! isMasterGlobal()` — que responde a
outra pergunta ("quem é intocável?") e nunca foi cruzada com a primeira.

O resultado é uma assimetria observável: a pessoa desativada vê o aviso "conta desativada, procure
o administrador"; o administrador abre a lista de usuários e entra como ela.

## Análise dos Arquivos Existentes

### `app/Models/User.php`

- `canBeImpersonated()` (`:709-713`) — **o único arquivo que o fix altera**. Hoje:
  `return ! $this->isMasterGlobal();`
- `motivoDeIndisponibilidade()` (`:236`) — devolve `'conta_excluida'` / `'conta_inativa'` / `null`.
  É a pergunta que o login por senha, o login social e o middleware do painel já fazem. **É ela
  que o fix reutiliza** — não uma segunda leitura de `ativo` e `deleted_at`.
- `aprovacao_pendente` — cast em `casts()`; `canAccessPanel()` a checa **depois** da
  indisponibilidade e **antes** do atalho de `master_global`, e o docblock de lá explica que a
  ordem é a decisão.
- `canImpersonate()` (`:704`) — quem **pode** personificar. **Não muda** (fora de escopo).
- `isMasterGlobal()` (`:551`).
- `reativar()` (`:304`) — não muda; RQ-03 sai de graça porque a régua é lida a cada request.

### `vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php`

- `:37` — `visible(fn ($record) => $this->canImpersonate(...))` → esconde o botão.
- `:112` → `:167` — o `impersonate()` reconsulta antes de executar. **É por isso que corrigir o
  model basta**: fecha tela e escrita de uma vez, como `.ai/rules/filament.md:19-29` exige.
- `:157-159` — recusa soft-deleted quando `config('filament-impersonate.allow_soft_deleted')` é
  falsa. Default `false` (`config/filament-impersonate.php:9` do vendor), e o kit não publicou a
  config. É a guarda frágil que a premissa A1 substitui por decisão própria.

### `app/Filament/Admin/Resources/Users/UserResource.php`

- `:222` — `Impersonate::make()`, sem `->visible()` próprio. **Não muda**: o `visible()` do vendor
  já consulta o model, e acrescentar uma condição aqui criaria a segunda cópia da regra.
- `:36` — o import.

### `app/Filament/Concerns/SituacaoDaConta.php`

- `acaoDeDesativar()` (`:70`) e as vizinhas. **Não muda** — só serve de referência de como o kit
  já escreve ação dependente de estado.

### `tests/Kit/SituacaoDaContaTest.php`

- Onde os cenários novos entram. Já tem o helper local `usuarioNoEstado($papel, $email, $ativo)`
  (`:32-41`) e usa `TestAction` do Filament e `Livewire::test(ListUsers::class)`.
- **Zero testes de impersonate no kit hoje** — este fix traz a primeira cobertura da
  funcionalidade.

## Autorização

É o próprio assunto do fix. Nenhuma policy, gate ou middleware novo: a decisão vive no model, que
é o que o pacote consulta. `canImpersonate()` (quem opera) permanece `isMasterGlobal()`.

## Rotas

Nenhuma. As rotas de personificação são do pacote e não são tocadas.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Lista de usuários do `/admin` (`ListUsers`) | Filament | `/admin/users` | a ação **Personificar** deixa de aparecer na linha de uma conta indisponível | Não |

**Gate de CT-B**: **não passa.** A afirmação é "a ação não está disponível naquela linha" e
"a execução é recusada" — as duas se provam por componente Livewire (`assertActionHidden` e
`callAction`), em milissegundos. Nada de JavaScript, cor, layout ou acessibilidade.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é tocada.

## Variáveis de Ambiente

Nenhuma nova. Com a premissa A1, `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED` deixa de ser a única
guarda da conta excluída — passa a ser irrelevante para essa proteção.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Estados de usuário** (wiki ancestral): a barreira fica **mais** restritiva; nenhum caso de lá
  muda de resultado. Rodar `tests/Kit/SituacaoDaContaTest.php` inteiro.
- **Permissões de ações** (`tests/Kit/PermissoesDeAcoesTest.php`): se algum caso lista as ações
  visíveis de uma linha, o conjunto pode mudar para linha de conta indisponível. Verificar.
- **Login social** (`tests/Kit/LoginSocialContaIndisponivelTest.php`): não toca impersonate, mas
  consome `motivoDeIndisponibilidade()`. Rodar por segurança.
- **Nenhum caminho perde capacidade** para conta ativa e não-pendente: o comportamento é idêntico
  ao de hoje.

## Rollback

Reverter uma condição em um método. Sem migration, sem `.env`, sem cache, sem dado migrado.

## Dependências

Nenhuma nova. `stechstudio/filament-impersonate ^5.5` já está em `composer.json:70`.

## Riscos

- **A premissa A1 alarga o pedido** (de um estado para três). Mitigação: a decisão está registrada
  no `00` com o "Se negado", e recuar é apagar duas condições de uma linha.
- **A ação some silenciosamente**, sem dizer por quê (premissa A2). Mitigação: é o comportamento
  do `visible()` do pacote e das ações de estado do kit; se incomodar, entra uma notificação.
- **Branch**: esta wiki nasce em `feat/paleta-do-filament-na-organizacao`, que já carrega outra
  feature e a wiki `kit-info`. Um fix de fronteira de acesso merece branch própria
  (`fix/impersonate-respeita-estado-da-conta`). **Decisão sua.**

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php:132-139` tem o canal **`autenticacao`**, e é o que todo o vizinho usa:
`canAccessPanel()`, `desativar()`, `reativar()` e `AdministradorDaInstalacao::regraDeNomeDePapel()`
escrevem nele. O canal é lido pelo Logs Explorer do `/infra`.

### Decisão

**Reutilizar `autenticacao`.** Nada em `config/logging.php` muda.

**Um log, e só no caminho da recusa** — `warning`, no formato do vizinho. Nada no caminho feliz:
`canBeImpersonated()` é chamado no `visible()` de **cada linha** da tabela, então logar sucesso
produziria uma linha por usuário listado por render. É exatamente o ruído que a nota do canal
`autenticacao` mediu em 1,1 MB/dia.

**A recusa também é chamada por linha**, então mesmo o `warning` precisa de cuidado: ele só sai
quando há **operador autenticado** e o alvo está indisponível — o caso raro, não o render.

## Estrutura de Implementação

### 1. `User::canBeImpersonated()` passa a exigir conta disponível

> Skills: `laravel-best-practices`, `eloquent-best-practices`, `ponytail`

- **Path**: `app/Models/User.php:709-713`

```php
/**
 * Quem pode ser ALVO de personificação.
 *
 * Três recusas, e as duas primeiras são a correção: personificar não pode ser o caminho lateral
 * em volta de `canAccessPanel()`. A conta inativa, a pendente e a excluída são recusadas no
 * login — por senha, por login social e pelo middleware do painel — e entrar nelas pelo
 * `/admin` contorna a decisão da wiki `status-e-exclusao-logica-de-usuario` sem nada acusar.
 *
 * A pergunta é a MESMA de `canAccessPanel()`, e de propósito: `motivoDeIndisponibilidade()`
 * mais `aprovacao_pendente`. Reler `ativo` e `deleted_at` aqui seria a segunda cópia de uma
 * regra que já tem dona, e a cópia divergiria no primeiro ajuste.
 *
 * **Isto não é barreira de tela.** O pacote consulta este método no `visible()` da ação
 * (`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:37`) E outra vez antes
 * de executar (`:112` → `:167`), então esconder e recusar vêm da mesma linha.
 *
 * E ele fecha uma guarda que era do vendor: a conta excluída só estava protegida pelo default
 * de `config('filament-impersonate.allow_soft_deleted')` (`:157-159`), config que o kit nunca
 * publicou — um `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true` no `.env` a reabriria.
 */
public function canBeImpersonated(): bool
{
    // Master global nunca é alvo de impersonação.
    if ($this->isMasterGlobal()) {
        return false;
    }

    return $this->motivoDeIndisponibilidade() === null && ! $this->aprovacao_pendente;
}
```

- **Ordem das condições**: `isMasterGlobal()` primeiro, preservando RQ-04 exatamente como está
  hoje. As duas novas depois, sem tocar na existente.
- **Por que `=== null`** e não `blank()`: `motivoDeIndisponibilidade()` devolve `?string` com
  união literal declarada; `null` é o único valor que significa "disponível".
- **Logs**: um `warning` no canal `autenticacao`, **somente quando há operador autenticado e o
  alvo está indisponível** — o caso raro. Sem operador (fila, comando, teste de model direto) a
  recusa é silenciosa, porque não há ato humano a auditar e o método roda por linha de tabela:

  ```php
  Log::channel('autenticacao')->warning(
      "[User@canBeImpersonated] Personificação recusada | alvo: {$this->id} - razao: {$razao}",
      [
          'alvo_id'     => $this->id,
          'executor_id' => Auth::id(),
          'motivo'      => 'personificacao_recusada',
          'razao'       => $razao,   // 'conta_inativa' | 'conta_excluida' | 'aprovacao_pendente'
          'email'       => Str::mask((string) $this->email, '*', 3),
      ],
  );
  ```

  `Log`, `Auth` e `Str` já estão importados no arquivo (usados por `desativar()` e vizinhos) —
  confirmar na revisão profunda antes de escrever import novo.

  > **Ponytail**: se o log por linha se mostrar ruidoso na prática, ele é o primeiro candidato a
  > cair — a barreira é a condição, não o log. Marcar com `ponytail:` se for cortado.

### 2. Documentação

> Skills: nenhuma específica — prosa

- **Path**: `docs/pt/autenticacao/estados-de-usuario.md` — uma frase no fim do parágrafo que já
  fala das ações de desativar e reativar: *"Conta inativa, pendente de aprovação ou excluída
  também não pode ser **personificada** — a ação não aparece na linha dela na lista do `/admin`."*
- **Path**: `docs/en/autenticacao/estados-de-usuario.md` — a mesma frase, em inglês (paridade
  exigida por `tests/Kit/SiteDeDocumentacaoTest.php`, CT-04/CT-05).
- **Path**: `CHANGELOG.md` → `## [Unreleased]` → `### Corrigido`:
  *"**Conta indisponível não pode mais ser personificada.** `User::canBeImpersonated()` só olhava
  se o alvo era `master_global`, então um administrador entrava, pelo `/admin`, como uma conta
  inativa, pendente de aprovação ou excluída — as três que o kit recusa no login. A régua passou a
  ser a mesma de `canAccessPanel()`. A conta excluída estava protegida apenas pelo default de uma
  config do pacote, que o kit nunca publicou."*
- **Não** editar `docs/*/recursos/configuracoes-do-kit.md` nem `docs/*/comecar/instalacao-avancada.md`
  (em edição por outras entregas na mesma branch).

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**.
> 1. Reutilizar antes de criar — `motivoDeIndisponibilidade()` já responde a pergunta; não reler
>    `ativo` nem `deleted_at`
> 2. Não acrescentar `->visible()` na Action: o `visible()` do vendor já consulta o model, e a
>    condição na tela seria a segunda cópia da regra
> 3. Não publicar config do vendor para reafirmar um default
> 4. O fix é **uma condição em um método** — se o diff crescer, algo saiu do escopo
>
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo** na conversa; arquivos wiki, código e commits são boundary.

## Testes

> Ver `04-casos-de-teste.md` — derivado pela `feature-test-design` a partir do `00-requisito.md`.
> Sem `05`: nenhum cenário exige navegador (ver o gate na `## Superfície de UI`).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `php artisan test --compact tests/Kit/SituacaoDaContaTest.php`
- [ ] `php artisan test --compact tests/Kit/PermissoesDeAcoesTest.php tests/Kit/LoginSocialContaIndisponivelTest.php`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php` — paridade pt/en
- [ ] `vendor/bin/pest --parallel --tia` — nada mais quebrou
- [ ] À mão: desativar um usuário no `/admin` e confirmar que a ação Personificar desapareceu da
      linha dele; reativar e confirmar que voltou

## Commits

- `🐛 fix(impersonate): conta inativa, pendente ou excluída deixa de ser personificável`
- `📝 docs(estados): documenta que conta indisponível não é personificada`
- `📝 docs(wiki): wiki do fix impersonate-respeita-estado-da-conta`
