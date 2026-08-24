---
paths:
  - 'app/Filament/Admin/Pages/**'
---

# Pages

## Segredo em formulário: esconder na tela não é esconder no HTML
`->password()` e `->revealable()` mexem no `type` do input — ou seja, na TELA. O valor continua em `$this->data`, que é propriedade **pública** do componente Livewire, e o Livewire serializa isso inteiro no `wire:snapshot` do HTML.

Foi Blocker na v0.19.0: `GET /admin/configuracoes-do-kit` devolvia a senha de SMTP **em claro no corpo da resposta**, com 200 e sem clique em "revelar". O banco (cifrado) e a trilha (mascarada) estavam corretos — vazava só no navegador, que é onde ninguém pensou em olhar.

Campo de segredo em formulário precisa de DOIS pontos, e nenhum deles é visual:

1. zerar a chave em `mutateFormDataBeforeFill()` — o segredo não entra em `$data`, logo não entra no snapshot;
2. `->dehydrated(fn (?string $e): bool => filled($e))` — a chave só chega ao save quando o campo foi preenchido.

O valor guardado sobrevive porque `$settings->fill($data)` aplica só as chaves presentes (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:83`). Ponha um `->placeholder()` dizendo "em branco mantém", senão a pessoa acha que apagou.

`->revealable()` pode ficar: o que ele revela agora é o que a PESSOA acabou de digitar, que é conferência de digitação, não exposição do que estava gravado.

Cubra em par, sempre: um caso assertando que o segredo NÃO está no HTML (`assertOk()` junto, senão um 500 passa por engano), e outro provando que ele **sobrevive** a um salvamento que não o tocou. Sem o segundo, apagar o segredo passaria no primeiro.

Vale para `mail_password` e `login_google_client_secret` hoje, e para o próximo. Os dois estão em `ConfiguracoesDoKit::encrypted()`.
