# Casos de Teste de Browser — auth-designer-telas

> Runtime: `pest-plugin-browser`. O plugin sobe o próprio servidor HTTP in-process.
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`; roda em série).
> Ver `.ai/rules/testes-browser.md` — os pré-requisitos duros estão lá, não aqui.

## Pré-requisitos

- [ ] `composer test:browser` (não `pest --testsuite=Browser` direto: o script embute o
      `npm run build` e o `view:cache`, e sem os dois a suíte falha por motivo que não é o dela)
- [ ] Autenticação por `$this->actingAs($user)` antes do `visit()` — nunca login pela tela
- [ ] O `beforeEach` do arquivo alvo (`tests/Browser/TelasDoKitTest.php`) já semeia
      `ShieldPermissionsSeeder` + `PapeisSeeder`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| Alternador de tema do Auth Designer | `.fi-auth-theme-switcher-wrapper` | sim — emitido só pelo layout do pacote (`vendor/caresome/filament-auth-designer/resources/views/components/partials/theme-toggle.blade.php:20`), e só quando `Filament::hasDarkMode()` |
| Campo do código | `#form\.code` | sim — **corrigido depois de vermelho**: o `id` do Filament vem do nome do SCHEMA (`form`), não do `->statePath('data')` (`vendor/jeffgreco13/filament-breezy/src/Pages/TwoFactorPage.php:86`). `#data\.code` foi a especificação errada; `.ai/rules/testes-browser.md` já registrava o padrão `#form\.email` |

Dívida conhecida do kit: não há `data-testid`. Ver `.ai/rules/testes-browser.md`.

---

## CT-B05: a tela do 2FA carrega no layout novo sem erro de JavaScript

**Por que browser e não Livewire ou HTTP** — e o que ele acrescenta, com precisão:

A rota `/{painel}/two-factor-authentication` **já está** em `telasDoKit()`
(`tests/Pest.php:213,235,257`) e **já passava** pelo lote de CT-B01 antes desta feature. Então:

| Já provado por | O quê |
|---|---|
| CT-B01 (o lote) | a tela não registra erro de JavaScript — e agora com a nossa classe na rota |
| CT-01/CT-02 do `04` | as classes do layout e da mídia estão no HTML |
| **nenhum dos dois** | que o layout do Auth Designer **renderiza** o que injeta |

`.fi-auth-theme-switcher-wrapper` só existe no layout do pacote
(`vendor/caresome/filament-auth-designer/resources/views/components/partials/theme-toggle.blade.php:20`).
`assertVisible` sobre ele é a **única** asserção da suíte que afirma que a tela de 2FA saiu
vestida num navegador de verdade, e não apenas com a classe no DOM — e componente Alpine que
estoura não move o status HTTP nem aparece em `assertSee`.

```gherkin
# language: pt

  Cenário: [CT-B05] a tela do código de 2FA abre operável, sem erro no console
    Dado um administrador autenticado
    Quando ele abre a tela onde se digita o código do 2FA
    Então o campo do código está visível
    E o alternador de tema do layout de autenticação está visível
    E o console do navegador não registra nenhum erro
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autenticar fora do navegador | `$this->actingAs(usuarioDoKit('master_global'))` | — |
| 2 | aquecer a compilação dos componentes do painel **fora** do cronômetro do Playwright | `$this->get('/admin')` | — (ver a rule do `view:cache` para arquivo isolado) |
| 3 | abrir a tela | `visit('/admin/two-factor-authentication')` | a tela do desafio de 2FA |
| 4 | provar que a tela é operável | `->assertVisible('#data\\.code')` | o campo do código |
| 5 | provar que é o layout do Auth Designer, renderizado | `->assertVisible('.fi-auth-theme-switcher-wrapper')` | o alternador de tema |
| 6 | provar que nada estourou | `->assertNoJavaScriptErrors()` | — |

**Assertions**: `assertNoJavaScriptErrors()` e não `assertNoSmoke()` — a tela é de terceiro
(Breezy) dentro de layout de terceiro (Auth Designer), e `assertNoSmoke()` reprovaria por
`console.log` alheio que ninguém vai corrigir (`.ai/rules/testes-browser.md`). E o console
**não** é o oráculo do cenário: os passos 4 e 5 são, porque página em branco e 403 renderizado
passam por um console limpo.

Sem `assertPathIs`: não há ação que navegue neste cenário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| MB1 | a tela é vestida publicando a view do Breezy e envolvendo o markup à mão, e o `<x-filament-panels::page.simple>` sai duplicado ou fora de lugar — HTML válido, Alpine estourando | CT-B05 (passos 4 e 6) |
| MB2 | o alternador de tema não é configurado na chave usada (`->themeToggle()` esquecido), e a tela abre sem ele | CT-B05 (passo 5) — e CT-02 do `04`, pelo mesmo mutante M5 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B para a tela de confirmação de e-mail | **inexpressável**: a tela não tem rota (ADR-03). O que existe para medir é o objeto de configuração, e CT-07 do `04` o mede sem navegador |
| CT-B para o `register` espelhado | o eixo é classe CSS: CT-09 do `04` prova mais barato. E `visit('/app/register')` **sem token** redireciona para o login (`RegistroPorConvite::recusar()`), então o cenário mediria a tela errada — é o limite pré-existente de `tests/Browser/TelasDoKitTest.php:67` |
| CT-B clicando no alternador de tema e conferindo o tema escuro | `assertSee` não valida tema (`.ai/rules/testes-browser.md`), e provar cor exige screenshot e olho humano. O alternador do login já é coberto por `tests/Browser/TemaEscuroTest.php` — este cenário não acrescenta mutante |
| CT-B submetendo o código pela tela | CT-05 e CT-06 do `04` provam por componente Livewire, em milissegundos, e matam os mesmos mutantes (M10, M11) |
| CT-B de acessibilidade na tela nova | não há mutante previsto que ele mate; e `visit([...])` em lote aborta na primeira falha, o que tornaria a auditoria parcial |

> **Teto**: perfil `completo` prevê 1 happy path + 1 erro visível. Entregue **1**. O "erro
> visível" não foi escrito porque o erro desta tela (código inválido) é `addError()` do Livewire,
> renderizado sem JavaScript novo — CT-05 do `04` o prova mais barato e mata o mesmo mutante.

**Onde vive**: `tests/Browser/TelasDoKitTest.php`, no fim do arquivo. Arquivo novo seria um boot
de navegador a mais para o mesmo assunto que ele já cobre (as telas dos três painéis).

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | tela de 2FA com o layout do Auth Designer nos três painéis | `App\Filament\Pages\Auth\TelaDoisFatores`, ligada por `enableTwoFactorAuthentication(action:)` nos três | ✅ | CT-01 (3 datasets) verde; `php artisan route:list --path=two-factor` mostra as três rotas apontando para a classe |
| 2 | mídia e eixo iguais aos do login | chave `login` do Auth Designer, `media-left` + `has-media` | ✅ | CT-02 verde |
| 3 | `register` com a mídia no lado inverso ao do login | `MediaPosition::Right` no bloco `->registration(...)` | ✅ | CT-09 verde (`media-right` presente, `media-left` ausente, com token de convite válido) |
| 4 | confirmação de e-mail vestida (configuração), sem rota no ar | bloco `->emailVerification(...)` no plugin + `->emailVerification(null, isRequired: false)` no painel | ⚠️ **desvio** — o PRD desenhou a rota no ar com `isRequired: false`; a rota ficou fora porque a tela do Filament responde 500 sem `MustVerifyEmail` no model. Ver "Desvios do Plano" no `03-progresso.md` e ADR-03 | CT-07 verde (3 datasets: `hasPageConfig`, mídia, `position`, `showThemeSwitcher`) |
| 5 | nenhum painel exigindo e-mail verificado | `isRequired: false` explícito, depois do `->plugins([...])` | ✅ | CT-08 verde (3 datasets: exigência falsa, `hasEmailVerification()` falso, rota inexistente) |
| 6 | o layout de autenticação não vaza para as outras páginas | `protected static string $layout` redeclarado em `TelaDoisFatores` | ✅ | CT-03 + CT-04 verdes (o par que `.ai/rules/auth.md` cobra) |
| 7 | a tela vestida continua autenticando | nenhum método de comportamento sobrescrito | ✅ | CT-05 (código errado recusado) e CT-06 (código de recuperação aceito e consumido) verdes |

## Execução

| Quando | Resultado |
|---|---|
| 1ª rodada | **vermelho** — causa (a), seletor especificado errado (`#data\.code`). Corrigido o CT-B, não a aplicação |
| 2ª rodada (`--filter`) | verde, 3 asserções, 12,2 s |
| suíte completa (`--testsuite=Browser`) | **38 testes, 33 passando, 0 falhando, 5 pulados** |
