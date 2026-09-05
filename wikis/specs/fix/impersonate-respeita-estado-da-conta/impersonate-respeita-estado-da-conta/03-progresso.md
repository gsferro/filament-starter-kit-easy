# Progresso — Fix: conta indisponível não pode ser personificada

> Wiki criada em 2026-09-02. **Fidelidade do requisito: baixa** (uma linha). A identificação do
> alvo está **resolvida** — "personality" é a ação Impersonate, e o defeito foi confirmado no
> código. O que segue como premissa é o **alcance** (A1: um estado ou três).

## 0. Antes de implementar

- [x] Identificar "personality" — é a ação Impersonate do `stechstudio/filament-impersonate`,
      registrada em `app/Filament/Admin/Resources/Users/UserResource.php:222`
- [x] Confirmar o defeito no código — `User::canBeImpersonated()` (`:709-713`) não olha o estado
- [x] Confirmar que a correção no model fecha tela **e** execução — o vendor consulta o método em
      `Impersonate.php:37` (`visible()`) e `:167` (dentro do `impersonate()`)
- [ ] Confirmar as premissas **A1** (alcance dos estados), **A2** (sem mensagem) e **A3** (não
      publicar a config do vendor) do `00-requisito.md`
- [ ] Decidir a branch: a wiki nasceu em `feat/paleta-do-filament-na-organizacao`, que já carrega
      duas entregas. Fix de fronteira de acesso merece `fix/impersonate-respeita-estado-da-conta`
      — **decisão sua**

## 1. `User::canBeImpersonated()` exige conta disponível

- [ ] Trocar `return ! $this->isMasterGlobal();` pela condição de três partes do PRD
- [ ] Docblock com os dois pontos do vendor (`:37` e `:167`) e a nota da config `allow_soft_deleted`
- [ ] `isMasterGlobal()` recusa **primeiro** — RQ-04 preservada sem alteração
- [ ] Log `warning` no canal `autenticacao`, só na recusa e só com `Auth::id()` presente
- [ ] Confirmar que `Log`, `Auth` e `Str` já estão importados (revisão profunda diz que sim)
- [ ] **Não** tocar em `canImpersonate()`, na `UserResource` nem publicar config do vendor

## 2. Documentação

- [ ] `docs/pt/autenticacao/estados-de-usuario.md` — a frase sobre personificação
- [ ] `docs/en/autenticacao/estados-de-usuario.md` — a mesma, em inglês
- [ ] `CHANGELOG.md` → `[Unreleased]` → `### Corrigido`
- [ ] **Não** tocar `docs/*/recursos/configuracoes-do-kit.md` nem
      `docs/*/comecar/instalacao-avancada.md` (em edição por outras entregas da branch)

## Testes

- [ ] `04-casos-de-teste.md` — derivado pela `feature-test-design` (em andamento)
- [ ] Cenários novos em `tests/Kit/SituacaoDaContaTest.php`, ou arquivo próprio se o conjunto
      crescer. **Primeira cobertura de impersonate no kit** — hoje não existe nenhuma
- [ ] Se precisar de helper usado por mais de um arquivo, ele vai para `tests/Pest.php`
      (`.ai/rules/testes.md`, guardado por `tests/Kit/HelpersDeTesteTest.php`)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `php artisan test --compact tests/Kit/SituacaoDaContaTest.php`
- [ ] `php artisan test --compact tests/Kit/PermissoesDeAcoesTest.php tests/Kit/LoginSocialContaIndisponivelTest.php`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] À mão: desativar no `/admin` → a ação Personificar desaparece da linha; reativar → volta
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "'personality' é alguma feature do kit" | o termo **não existe** em `app/`, `config/`, `resources/` nem no vendor do Filament; a raiz `personate` aparece só no impersonate | `00` passou a documentar a identificação e a evidência |
| "a barreira pode estar no `->visible()` da Action" | `UserResource.php:222` é `Impersonate::make()` **sem** `->visible()` próprio — quem esconde é o `visible()` do vendor (`:37`), que consulta o model | ADR-01: a correção vai no model, a Action não muda |
| "corrigir o model só esconderia o botão" | o vendor **reconsulta** `canBeImpersonated()` dentro do `impersonate()` (`:112` → `:167`), antes de executar | RQ-02 é atendida pelo mesmo passo; não precisa de segunda barreira |
| "conta excluída já está protegida pelo kit" | está protegida pelo **vendor** (`:157-159`), condicionada a `config('filament-impersonate.allow_soft_deleted')`, default `false` no vendor, config **não publicada** no kit | ADR-04; `00` registra que um `.env` reabriria a porta |
| "`motivoDeIndisponibilidade()` cobre os três estados" | cobre **excluída** e **inativa** apenas (`app/Models/User.php:236`); a pendência é condição separada em `canAccessPanel()` | ADR-03: `aprovacao_pendente` entra explícito, e o motivo de não a fundir no método está registrado |
| "há testes de impersonate para estender" | **zero** — `grep -rln -i impersonat tests/` é vazio | `01` e `03` passaram a dizer que esta é a primeira cobertura |
| "os cenários entram em arquivo novo" | `tests/Kit/SituacaoDaContaTest.php` já é o arquivo dos estados da conta, com o helper local `usuarioNoEstado()` (`:32-41`) e o padrão de `TestAction` | passo de testes aponta para lá primeiro |
| "canal de log próprio" | `config/logging.php:132-139` tem `autenticacao`, usado por `desativar()`, `reativar()` e `canAccessPanel()` | reutilizado; nada em `config/logging.php` muda |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | (pendente — rodar `/ponytail:ponytail-review` na wiki antes de implementar) | | |

## Blockers

- [ ] **Premissa A1 não confirmada.** Implementar cobre três estados; se o pedido era só o
      desativado, o recuo é apagar duas condições — mas a conta excluída volta a depender do
      default de uma config do vendor.

## Desvios do Plano

<!-- Pós-implementação -->

## Notas de Implementação

- **Achado adjacente, fora do escopo declarado**: nada encerra uma sessão de personificação **em
  curso** quando a conta-alvo é desativada. O `desativar()` grava `ativo = false` e o
  `canAccessPanel()` barra no request seguinte de quem entrou pelo login — mas a sessão de
  personificação é do operador, e não foi verificado se ela também cai. Vale investigar; não
  entrou nesta wiki porque o requisito pede o **não liberar**, não o **interromper**.

## Retrospectiva

- **Funcionou bem**: tratar o termo desconhecido como pesquisa em vez de suposição. A varredura
  por raiz (`persona*`) achou o alvo em uma consulta, depois de três consultas literais por
  "personality" voltarem vazias.
- **Faltou no plano**: nada ainda — a wiki foi escrita depois da identificação, não antes.
