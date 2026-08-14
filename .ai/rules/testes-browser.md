# Testes de browser (`pest-plugin-browser`)

## O plugin sobe o próprio servidor — não configure nenhum

Servidor HTTP in-process (amphp), em porta aleatória. **Nenhum Herd, `artisan serve`, Sail ou Vite dev server**, e nada de `APP_URL` a configurar.

Como é o **mesmo processo** do teste, três coisas continuam valendo dentro do navegador, ao contrário do que um teste de browser costuma exigir:

- `DB_DATABASE=:memory:` e `RefreshDatabase`
- `$this->actingAs($user)` antes do `visit()` — **use isto**, não login pela UI. Logar pela tela custa ~20 s por cenário; reserve um cenário só para o formulário de login, que é o único caminho real do usuário
- `$this->assertAuthenticated()` e o resto das asserções do Laravel

## `npm run build` é pré-requisito duro

Sem `public/build/manifest.json` **toda** tela responde `ViteException` e todo cenário falha por um motivo que não é o dele. Rode com `composer test:browser`, que já embute o build.

## `assertPathIs` antes das asserções de conteúdo

`assertPathIs` é a asserção que **espera a navegação**. Depois de qualquer ação que navegue (`press`, `click`), ela vem primeiro:

```php
->press('Login')->assertPathIs('/app')->assertSee('Painel de Controle')
```

Invertido, o `assertSee` é avaliado contra o snapshot da página anterior e falha dizendo que não achou o texto — **com a ação tendo funcionado**. O screenshot da falha mostra a tela nova, e o erro aponta a URL velha.

Nunca use `wait(n)` com segundos fixos. O plugin reexecuta cada asserção até o teto de `pest()->browser()->timeout()`; espere pelo estado final visível.

## Nunca `--parallel` com browser

Multiplica processos de navegador e produz timeout. Medido: `pest --parallel --tia` no run completo derruba 4 dos 11 cenários. Rode `pest --testsuite=Browser` em série.

Consequência: o `--tia` exige run **completo** (`--group` e `--exclude-group` o desligam), então `--parallel --tia` e os CT-B não convivem numa invocação só. Use dois comandos.

## `visit([...])` aborta na primeira falha

Lote é o jeito certo de cobrir muitas telas — 52 em 2 cenários. Mas a primeira exceção encerra o laço, e as rotas seguintes **não são verificadas naquele run**. Se o que você quer é *colher todos* os problemas (auditoria de acessibilidade, por exemplo), separe em um cenário por painel.

## Seletores: `aria-label` e texto, não classe de CSS

O kit não tem `data-testid` (dívida conhecida). O disponível hoje:

- campos do Filament: `#form\.email`, `#form\.password` — `id` gerado, e o `.` precisa de escape em CSS
- alternador de tema: `[aria-label="Mudar para tema escuro"]`
- rótulos: texto visível — mas confira o texto **traduzido**. O `<h1>` do dashboard é `Painel de Controle`, não `Dashboard`

## `assertSee` não valida tema

`assertSee('Salvar')` **passa** com texto branco em fundo branco: o texto está no DOM, só está invisível. `->inDarkMode()->assertSee(...)` prova que a tela abre sob `prefers-color-scheme: dark`, e nada sobre legibilidade. Para defeito de cor não há saída barata — é screenshot e olhar.

Use `assertNoSmoke()` só em tela de autoria própria; nas de plugin de terceiro, `assertNoJavaScriptErrors()`, senão a suíte fica vermelha por `console.log` que ninguém vai corrigir.
