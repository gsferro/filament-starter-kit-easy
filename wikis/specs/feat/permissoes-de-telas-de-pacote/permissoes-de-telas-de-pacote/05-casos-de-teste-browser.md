# Casos de Teste de Browser — W6: permissões das telas de pacote

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `composer test:browser` (roda `npm run build` antes; **nunca** `--parallel`).

## Gate — por que existe UM cenário aqui

Quase nada desta feature precisa de navegador: 403 de rota, presença de item de menu e
visibilidade de widget são request e componente Livewire, e vivem no `04`.

O que **só** o navegador prova é consequência de ADR-04, não da autorização: ao trocar
`BackupRunsPage` por uma subclasse do kit, o `FilamentBackupMonitorPlugin` **sai** do painel, e
com ele sai o `->livewireComponents([LatestBackupsWidget::class])` que o plugin fazia. O comentário
do próprio pacote diz o que acontece sem esse registro
(`vendor/brimham/filament-backup-monitor/src/FilamentBackupMonitorPlugin.php:22-25`):

> *"The page's header widget is isolated, so Livewire commits it via its own by-name request;
> without registering it the panel only renders it inline and the follow-up 419s (release-token
> mismatch)."*

Um `$this->get('/infra/backup-runs')` fica **verde** nesse cenário: o HTML da página vem íntegro,
status 200, e o widget vem como placeholder. O 419 acontece no request seguinte, que só existe
quando há um navegador executando Livewire. É a definição de cenário só-navegador.

O lote de `tests/Browser/TelasDoKitTest.php` já visita `/infra/backup-runs` com
`assertNoJavaScriptErrors()`. Ele **não substitui** este cenário: `assertNoJavaScriptErrors()` é
assertion de apoio, e a própria skill proíbe usá-la como oráculo único — página em branco e widget
que nunca carregou passam nela. O que falta é uma assertion sobre **o conteúdo que só existe
depois do commit do Livewire**.

## Pré-requisitos

- [ ] `npm run build` executado (o `composer test:browser` já faz)
- [ ] Autenticação por `$this->actingAs(usuarioDoKit(...))` antes do `visit()` — login pela tela
      custa dezenas de segundos e não é o assunto
- [ ] Os dois seeders no `beforeEach`, como em `tests/Browser/TelasDoKitTest.php:24-28`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| título da página de backups | texto traduzido `Execuções de backup` (`lang/pt_BR/backups.php:6` do pacote) | sim |
| cabeçalho do widget isolado | texto traduzido `Último backup por destino` (`.../backups.php` → `widget.heading`) | sim |
| estado vazio do widget | texto traduzido `Nenhum backup ainda` (`widget.empty_title`) | sim |

Sem `data-testid`: as telas são de pacote e o kit não publica as views delas. Fica registrado como
dívida — o texto traduzido é o seletor mais estável disponível, e é o que
`tests/Browser/PermissoesDoDashboardTest.php` já usa.

---

## CT-B01: o widget isolado da tela de backups carrega depois do commit do Livewire

**Por que browser e não Livewire**: a assertion é sobre conteúdo que só existe **depois** de um
segundo request do Livewire, disparado pelo navegador para um componente isolado registrado por
nome. Um teste de componente montaria o widget direto — provaria que a classe funciona e **não**
que ela está registrada no painel, que é exatamente o que a remoção do plugin põe em risco.

```gherkin
# language: pt

  Cenário: [CT-B01] a tela de backups carrega o cabeçalho do widget isolado
    Dado um usuário com o papel "infra", que carrega a permissão "View:BackupRunsPage"
    Quando ele abre a tela de execuções de backup no navegador
    Então a página mostra o título "Execuções de backup"
    E a página mostra o cabeçalho do widget "Último backup por destino"
    E o console do navegador não registra erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autenticar como papel `infra` | `$this->actingAs(usuarioDoKit('infra', 'infra@example.com'));` | — |
| 2 | abrir a tela | `visit('/infra/backup-runs')` | a página de execuções de backup |
| 3 | provar que a página é a certa | `->assertSee('Execuções de backup')` | o título |
| 4 | provar que o widget isolado carregou | `->assertSee('Último backup por destino')` | o cabeçalho do widget |
| 5 | apoio | `->assertNoJavaScriptErrors()` | console limpo |

O passo 4 é o oráculo. O plugin do pacote é `assertNoJavaScriptErrors()` de terceiro, então
`assertNoSmoke()` está fora (item 7 dos fatos do `pest-plugin-browser`).

Sem `assertPathIs`: não há ação que navegue — o cenário é um `visit()` direto.

Sem `wait()`: o `pest-plugin-browser` reexecuta a assertion até o teto de
`pest()->browser()->timeout()`, e é isso que espera o commit do Livewire. `waitForText` não existe.

O papel é `infra` e não `master_global`: `master_global` passa pelo `Gate::before` e o cenário
ficaria verde com o `canAccess()` da subclasse quebrado. Como o assunto do CT-B é renderização e
não autorização, poderia ser qualquer um dos dois — usar o papel real custa o mesmo e não perde
nada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | o `FilamentBackupMonitorPlugin` é removido e o `->livewireComponents([LatestBackupsWidget::class])` é esquecido | CT-B01 (passo 4) |
| M25 | a subclasse é registrada e a classe do pacote continua registrada pelo plugin: duas rotas para o slug `backup-runs`, e a que vence depende da ordem | CT-B01 (passo 3) + CT-02 do `04` (a rota atende à permissão) |
| M26 | a subclasse não é descoberta (fora de `app/Filament/Infra/Pages`) e a tela responde 404 | CT-B01 (passo 3) — e `tests/Kit/InventarioDeTelasTest.php`, que reprova rota morta |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| o 403 renderizado com a permissão revogada, em navegador | o `04` já prova o 403 por request (CT-02); a página de 403 do Filament não tem JS próprio a provar, e `assertNoJavaScriptErrors()` **passa** num 403 — seria o defeito que `InventarioDeTelasTest` existe para pegar |
| a barra lateral sem o item, em navegador | CT-03 do `04` prova por request, mais barato, e a montagem da navegação não depende de JS |
| `/infra/meu-perfil` em navegador depois da subclasse | já está no lote de `tests/Browser/TelasDoKitTest.php`, e a troca da classe do Breezy não muda o registro do componente — o plugin continua no painel |
| o cartão de releases aparecendo/desaparecendo no dashboard, em navegador | `tests/Browser/PermissoesDoDashboardTest.php` já é a suíte desse assunto, e CT-05 do `04` prova por componente |

## Roteiro de Validação: Desenhado × Implementado

<!-- Preenchido no step 7 da feature-wiki, depois de rodar os CT-B. -->

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `/infra/backup-runs` continua na mesma URL | | | |
| 2 | o header widget carrega sem o plugin no painel | | | |
| 3 | as 6 telas restantes de `## Superfície de UI` abrem para o papel `infra` | | | |
