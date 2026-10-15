---
paths:
  - 'app/Filament/Admin/Pages/**'
---

# Pages

## Segredo em formulário: esconder na tela não é esconder no HTML
`->password()` e `->revealable()` mexem no `type` do input — ou seja, na TELA. O valor continua em `$this->data`, que é propriedade **pública** do componente Livewire, e o Livewire serializa isso inteiro no `wire:snapshot` do HTML.

Foi Blocker na v0.19.0: `GET /admin/configuracoes-da-aplicacao` devolvia a senha de SMTP **em claro no corpo da resposta**, com 200 e sem clique em "revelar". O banco (cifrado) e a trilha (mascarada) estavam corretos — vazava só no navegador, que é onde ninguém pensou em olhar.

Campo de segredo em formulário precisa de DOIS pontos, e nenhum deles é visual:

1. zerar a chave em `mutateFormDataBeforeFill()` — o segredo não entra em `$data`, logo não entra no snapshot;
2. `->dehydrated(fn (?string $e): bool => filled($e))` — a chave só chega ao save quando o campo foi preenchido.

O valor guardado sobrevive porque `$settings->fill($data)` aplica só as chaves presentes (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:83`). Ponha um `->placeholder()` dizendo "em branco mantém", senão a pessoa acha que apagou.

`->revealable()` pode ficar: o que ele revela agora é o que a PESSOA acabou de digitar, que é conferência de digitação, não exposição do que estava gravado.

Cubra em **trio**, e o terceiro caso é o que faltou e custou um Blocker:

1. o segredo NÃO está no HTML (`assertOk()` junto, senão um 500 passa por engano);
2. ele **sobrevive** a um salvamento que não o tocou;
3. ele **é gravado e lido de volta** quando a pessoa digita um valor novo.

Sem o (3), os dois primeiros passam num campo que **nunca grava** — e foi exatamente o que aconteceu. Na v0.19.9 o quality gate achou que `client_secret` e a senha de SMTP não podiam ser gravados pela tela: a closure era `fn (?string $estado)`, e o Filament resolve parâmetro de closure **por nome** (`schemas/src/Components/Component.php:87-98`); nome desconhecido com tipo escalar não resolve (`support/src/Concerns/EvaluatesClosures.php:143-160`), a closure recebia `null`, `filled(null)` era `false` sempre. O nome é `$state`.

O caso (2) passava por construção: o valor sobrevivia porque nunca era escrito.

E há um quarto ponto, fora do formulário: **a chave precisa estar em `encrypted()`**. Duas vezes ela ficou de fora e produziu defeito diferente — segredo em texto claro na trilha de `audits` (o listener mascara comparando com essa lista), e `addEncrypted` na migration sem o par na classe, o que cifra na ida e devolve ciphertext na volta. Ao acrescentar campo de segredo, confira os quatro: fill, dehydrate, `encrypted()`, e o trio de casos.

Vale para `mail_password` e `login_google_client_secret` hoje, e para o próximo. Os dois estão em `ConfiguracoesDoKit::encrypted()`.

## Toda seção da tela de configurações é colapsável, com ícone e id estável
A tela `/admin/configuracoes-da-aplicacao` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php`) só cresce. Campo solto na aba, seção sem ícone ou seção que não fecha viram uma parede de formulário que ninguém mais lê — foi o estado da tela até a v0.49.0 (31 campos soltos em 6 abas).

A convenção, em três pontos:

1. **todo campo mora numa `Section`** — nunca direto na aba, mesmo o campo oculto por `visible()`;
2. **toda seção nasce pelo helper `secao($aba, $chave, $titulo, $icone)`** da Page: `icon()`, `collapsible()`, `persistCollapsed()` e `id("configuracoes-{aba}-{chave}")`. O `id()` é a chave do `localStorage` (`filament:section:collapsed:{id}`) — por isso é explícito, único e **não muda** depois de publicado (mudar apaga a preferência de todo mundo); ele não vira atributo HTML;
3. **toda aba nasce pelo helper `aba($chave, $rotulo, $icone, [...])`**: `Tab::key()` fixo (é o valor de `?tab=`) e `Schema::make()->components([...])->deferLoading()` — o conteúdo só renderiza quando a aba é aberta. A chave de todo campo fica aninhada (`configuracoes.{aba}.{campo}`); nos testes, use `chaveDoCampo()`/`carregarAba()` de `tests/Pest.php`, nunca o nome solto.

Seção que precisa nascer fechada (provedores sociais) acrescenta `->collapsed()` ao helper; o `persistCollapsed()` guarda os dois estados, então a seção que o usuário abriu continua aberta no mesmo navegador. Seção condicional (SMTP) recebe o `->visible()` na própria seção, não só nos campos.

Enforcement: `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` — CT-03 (nenhum campo fora de seção), CT-04 (toda seção `isCollapsible()` com ícone), CT-05 (id explícito, único, `shouldPersistCollapsed()`), CT-06 (toda aba adiada com chave fixa). Campo novo fora do padrão reprova na hora. Origem: `wikis/specs/feat/configuracoes-secoes-colapsaveis/`.
