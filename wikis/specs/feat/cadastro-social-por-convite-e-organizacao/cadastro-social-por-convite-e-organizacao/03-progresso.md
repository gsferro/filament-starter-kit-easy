# Progresso — Cadastro pelo provedor social

Implementada em 2026-08-26, na branch da PR #45, durante a validação real com tenancy.

## 1. A view dos botões carrega `org` e `token`
- [x] `botoes-sociais.blade.php`

## 2. O hook da tela de registro
- [x] `KitServiceProvider::configureTelaDeLogin()` → `AUTH_REGISTER_FORM_AFTER`

## 3. `redirecionar()` guarda o contexto
- [x] sessão `login_social.contexto`; log com `org` e `com_token`

## 4. `retorno()` consome o contexto
- [x] `pull()` logo após o usuário do provedor
- [x] ramo sem conta: convite → `criarContaPorConvite()`; senão `criarConta(…, $organizacao)`
- [x] ramos com conta/vínculo: `aceitarComoUsuarioExistente()` quando há convite para o e-mail

## 5. Testes
- [x] `tests/Kit/CadastroSocialPorConviteTest.php` — CT-C01, C04, C05 (+ o `redirect` gravando a sessão) — 4/4
- [x] `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` — CT-C02, C03, C04, C06, C07, C08 — 7/7
- [x] regressão: 358 (Kit, 6 arquivos) + 4 (Tenancy)
- [x] mutação: `$contexto = []` fixo → C03 e C04 reprovam

## 6. Validação real (`social-tenancy`)
- [ ] (a) conta vinculada entra pelo Google em `/app/acme`
- [ ] (b) `/app/register?org=acme` → GitHub → conta nova na `acme`
- [ ] (c) sem org → recusa
- [ ] ~~(d) convite em `globex` → GitHub → membro~~ — **não vale mais**: desde 2026-08-31 conta
  existente não consome convite na volta do provedor. O roteiro equivalente é: convite em `globex`
  → GitHub → entra **sem** virar membro, e o convite aparece pendente em `ConvitesRecebidos`, onde
  é aceito. Ver "Desvios do Plano" abaixo.

## 7. README
- [x] PT/EN — parágrafo da multi-organização na seção "Vínculo com o provedor" reescrito

## Verificação Final
- [x] pint · phpstan · testes · commit (validação real na instalação `social-tenancy`: pendente do clique do solicitante)

## Auditoria Pré-Implementação

### Revisão profunda — premissas do plano contra o código real
| Premissa | Código real | Correção |
|---|---|---|
| `Convite::valido()` recebe o token em claro | sim: faz `hash('sha256', $token)` e compara com `token` ou `token_lembrete`; filtra `aceito_em`, `recusado_em`, `expira_em` (`Convite.php:459-476`) | — |
| `aceitar()` trata conta existente | sim: `usuarioExistente()` → `aceitarComoUsuarioExistente()` (`Convite.php:569`) | o ramo "conta existe + convite" pode chamar `aceitarComoUsuarioExistente()` direto; `exigirDono()` compara o e-mail (`Convite.php:734`) |
| a tela de registro lê `org` só com tenancy | sim: `RegistroPorConvite.php:113-120`, e recusa sem organização | `RegistroAberto::organizacao(null)` devolve `null` e `registrar()` recusa com tenancy — igual |

### Auditoria Ponytail
Não invocada como skill; o plano já é o mínimo (transporte de contexto + duas chamadas às portas existentes).

## Blockers
- nenhum.

## Desvios do Plano
- Nenhum na entrega original: o plano previa `criarContaPorConvite()` e `aceitarConviteSeHouver()`, e
  foi o que existiu.
- **2026-08-31 — `aceitarConviteSeHouver()` removido.** A auditoria com o Filament Blueprint achou
  dois defeitos nele (F-03: convite queimado por conta indisponível, porque o aceite rodava antes da
  barreira; F-04: aceite sem consentimento, porque o `?token=` entra por rota GET pública sem CSRF e
  o SSO silencioso dispensa o clique). O método virou `avisarConvitePendente()`, que só registra. É
  o "Se negado" da ambiguidade RQ-04 do `00-requisito.md` sendo acionado — a premissa "é a mesma
  prova do formulário" era falsa. Conta **nova** por convite não mudou (RQ-04 preservada nessa
  metade). Teste afetado: `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` CT-C08 inverte
  o oráculo. Wiki da correção: `wikis/specs/feat/travas-de-escalada-de-papeis/`.

## Notas de Implementação
- `Convite::factory()` em teste com tenancy: `Role::query()->where('name', …)->value('id')` em vez de `Role::findByName()`, que aplica o filtro de team do Spatie.
- O `pull()` fica ANTES da checagem de e-mail/verificação de propósito: recusa também consome o contexto (CT-C07).

## Retrospectiva
- **Funcionou**: zero regra nova de cadastro — 11 casos verdes de primeira porque as portas já sabiam.
- **Faltou**: a `feature-test-design` não foi invocada (declarado no `04`).
