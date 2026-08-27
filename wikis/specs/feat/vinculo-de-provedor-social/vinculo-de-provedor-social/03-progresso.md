# Progresso — Vínculo de provedor social

Implementada em 2026-08-26, na mesma sessão da rodada de validação real dos provedores (arquivo
`07` da wiki `login-social-google`), na branch da PR #45.

## 1. Tabela e model do vínculo

- [x] `database/migrations/2026_08_26_172455_create_vinculos_sociais_table.php`
- [x] `app/Models/VinculoSocial.php` (`de()`, `vincular()`, `registrarAcesso()`)
- [x] `User::vinculosSociais()`

## 2. Config, settings e ponto único de leitura

- [x] `config/kit.php` → `login.vinculo_confirmar`
- [x] `ConfiguracoesDoKit` (propriedade + mapa) e a migration de settings
- [x] `ConfiguracaoDoLogin::vinculoExigeConfirmacao()`
- [x] `.env.example`

## 3. As duas notificações

- [x] `PrimeiroAcessoSocial`
- [x] `ConfirmarVinculoSocial`

## 4. O `retorno()` decide pelo vínculo antes do e-mail

- [x] trecho entre `contaCom()` e `Auth::login()` reescrito
- [x] `pedirConfirmacaoDoVinculo()`
- [x] pendência tratada para conta nova **e** existente

## 5. A rota e a ação de confirmação

- [x] `auth.social.confirmar` sob `signed`
- [x] `confirmarVinculo()`

## 6. A tela de Settings

- [x] `Toggle` na seção "Login social"

## 7. Testes

- [x] `tests/Kit/VinculoDeProvedorSocialTest.php` — CT-V01..V09 (10 casos com o dataset)
- [x] Regressão: 416 casos (Kit) + 4 (Tenancy) verdes
- [x] Mutação: `$vinculo = null` → CT-V02 e CT-V03 reprovam; rota sem `signed` → CT-V05 reprova

## 8. README e ADR

- [x] `02-decisoes-arquiteturais.md` ADR-01..04
- [x] README PT/EN — seções "As telas" e "Vínculo com o provedor: a primeira vez, e as seguintes", linha da tabela "identidade no provedor"

## 9. Capturas para o README

- [x] `CapturaDeArteTest` (5 cenários) + `KitArte::IMAGENS` + `kit:arte` — `login-social`, `admin-configuracoes-login`, `app-perfil-definir-senha`, `app-bloqueio-social`, `admin-users-origem`

## Verificação Final

- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` (0 erros nos arquivos tocados)
- [x] testes novos + regressão
- [x] capturas (10/10 no `CapturaDeArte`, publicadas em `art/`)
- [x] commit, PR #45 (`1e71fc6` código; README e capturas no commit seguinte)

## Auditoria Pré-Implementação

### Revisão profunda — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção |
|---|---|---|
| `usuarioSocialFalso()` vive em `LoginSocialProvedoresTest` | vive em `tests/Pest.php` e aceita `id` em `$mapeados` — é o `sub` | plano e testes citam o helper certo |
| `firstOrCreate` pela relação devolve `self` | devolve `Model` para o PHPStan; `self::query()->firstOrCreate()` com `user_id` nos atributos devolve `static` | `vincular()` reescrito; `user_id` entrou no `$fillable` |
| `$vinculo->user` é `User` | é `User|null` para o tipo (a FK em cascata garante, o tipo não sabe) | o `if` exige `instanceof User` além do vínculo |

### Auditoria Ponytail

Não invocada como skill nesta rodada (o plano já nasceu na escada: tabela mínima, mecanismo nativo
de URL assinada, notificações copiadas da `ConviteDeAcesso`, sem tela de gerenciamento). O que
ficou de fora e por quê está em `00-requisito.md` → *Fora de Escopo*.

## Desvios do Plano

- O `if ($user->aprovacao_pendente)` passou a valer para conta **existente** também (antes só
  para a nova): a existência do vínculo trouxe o caso "conta pendente entra pelo provedor e cai
  num 403" para dentro da mesma decisão. CT-V09.

## Notas de Implementação

- **`sub` vazio**: nenhum dos quatro drivers devolve vazio, mas o código não cria vínculo sem
  `sub` e registra `warning` (`motivo: sub_ausente`) — o fluxo cai no e-mail, como antes.
- **A confirmação também destrava o lockscreen**, como o `retorno()`: quem pediu confirmação com
  a sessão bloqueada volta destravado.
- **Fila**: as duas notificações são `ShouldQueue`. Na instalação de validação (sem worker) o
  e-mail só saiu com `queue:work --stop-when-empty` — o README precisa dizer isso na seção.

## Blockers

- nenhum.

## Retrospectiva

- **Funcionou**: nascer do achado real (a pergunta do solicitante) com a proposta escrita antes do
  código — o `00` tem a fonte verbatim e a aprovação.
- **Funcionou**: dois casos adversariais (V03, V08) escritos ANTES do código, e a mutação de V03
  medida — é o caso que dá sentido à tabela.
- **Faltou**: a `feature-test-design` não foi invocada; o desvio está declarado no `04`. Se a
  feature crescer (gerenciar vínculos no perfil), derivar os casos por ela.
