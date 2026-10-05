# Decisões Arquiteturais — feat/logo-dark-mode

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

## ADR-01: Troca light/dark por CSS (dois `<img>`) + override da partial de mídia do vendor

**Status**: Aceita
**Data**: 2026-10-03
**Portões**: difícil de reverter ✅ (a view sobreposta fica congelada em relação ao pacote — upgrades do `filament-auth-designer` passam a exigir reconciliação manual do arquivo, e remover a decisão exige desfazer markup, CSS e testes juntos) · surpreendente ✅ (o requisito pedia detecção no servidor com `$this->isDarkMode()` — método que não existe no Filament; e um override de `vendor` carrega lógica de `instanceof` que ninguém espera numa partial) · trade-off ✅ (duas `<img>` no DOM e uma decisão client-side, contra uma impossibilidade técnica server-side ou editar código de pacote)

### Contexto

O requisito sugere resolver o tema no servidor (`$this->isDarkMode()` / `request()->prefersDarkMode()`). Duas impossibilidades medidas:

- `isDarkMode()` **não existe** no Filament — o tema é decidido no cliente (classe `dark` no `<html>`), cobrindo escolha manual e preferência do sistema;
- `prefersDarkMode()` do Laravel lê o Client Hint `Sec-CH-Prefers-Color-Scheme`, que os navegadores **não enviam por padrão** — com tema "Sistema" (default do kit) o servidor simplesmente não sabe se a tela está escura.

Para o swap por CSS na lock-screen é preciso emitir **duas** `<img>` — mas `AuthDesignerConfig` é `final readonly` com `media` escalar, e a partial `media.blade.php` do pacote renderiza uma única tag. Os caminhos de extensão do pacote (`renderHooks`) só acrescentam conteúdo em posições fixas, não substituem o `<img>` nem removem `fi-auth-media` (obrigações RQ-05/RQ-07).

### Decisão

1. **O tema troca no cliente**: o componente emite a `<img>` light sempre e a `<img>` dark quando `urlsDasLogos()['escura']` resolve; as classes nativas `fi-logo`/`fi-logo-light`/`fi-logo-dark` (regras já publicadas no `app.css` do Filament) ligam/desligam conforme a classe `dark` do `<html>` — mesmo mecanismo do `darkModeBrandLogo` nativo (usado na topbar dos três painéis).
2. **A partial é sobreposta** em `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php` e bifurca por `method_exists($livewire, 'urlsDasLogos')`: para a página que resolve o par de logos (hoje `TelaBloqueio`), wrapper flex centrado + `object-fit:contain` + a dupla de imgs com as classes nativas `fi-logo`/`fi-logo-light`/`fi-logo-dark`; para qualquer outra (login, reset, 2FA), o markup original do vendor com `fi-auth-media`/`cover` é preservado — a `arte_do_login` não pode herdar `contain` (Adendo 1, Q1).
3. **Resolução por variante** (D3): dark = `tenant.logo_dark ?? kit.logo_dark`; dark ausente → só a light renderiza e serve aos dois modos.

### Alternativas Consideradas

1. **Detecção server-side (`prefersDarkMode()`)** — descartada: sem Client Hint devolve falso sempre e quebraria o tema "Sistema"; exigiria re-render Livewire a cada troca de tema.
2. **Fork/`setPageConfig` no `AuthDesignerConfigRepository`** — descartada: o repositório substitui o `AuthPageConfig` inteiro sem merge (já documentado em `TelaBloqueio`), e `AuthDesignerConfig` é `final readonly` — não há onde pendurar a segunda URL.
3. **Render hook `MediaOverlay` injetando a `<img>` dark** — descartada: o hook insere sobre a imagem sem removê-la nem trocar `fi-auth-media`; ficariam a arte + a logo no mesmo quadro.
4. **PR no pacote com `mediaDark`/`imageClass` configurável** — descartada para este ciclo: bloqueia a feature no tempo de revisão de terceiro; fica como contribuição candidata futura.
5. **`dark:` utilities do Tailwind no markup** — descartada: o kit não compila Tailwind próprio para utilitárias de blade de vendor (`.ai/rules/css-filament.md`); classes próprias em `kit.css` são previsíveis e escopadas.
6. **Classes `kit-logo-*` próprias** — descartada na revisão: as classes `fi-logo`/`fi-logo-light`/`fi-logo-dark` já têm as regras do swap no `app.css` publicado (verificado em `public/css/filament/filament/app.css`); `margin:0` inline neutraliza as margens de header que `fi-logo` carrega. Reuso nativo venceu o CSS novo (zero arquivo editado em `resources/css`).

### Consequências

- **Positivas**: cobre manual + "Sistema" com zero JS próprio; a arte de login fica byte a byte igual; o padrão é o mesmo que o Filament usa na topbar (`fi-logo-light`/`fi-logo-dark`), então a semântica "duas imgs, CSS decide" já existe no produto.
- **Negativas**: o override precisa ser reconciliado a cada upgrade do `filament-auth-designer` (risco mitigado por CT de contrato que assere o markup não-logo idêntico ao vendor); duas `<img>` no DOM da lock-screen quando a dark existe (peso irrelevante, imagens pequenas).
- **Riscos**: se o pacote renomear o namespace de views ou a partial, o override fica órfão em silêncio — mitigação: teste que renderiza a lock-screen e assere `fi-logo-light`/`fi-logo-dark` + `object-fit:contain` no markup (se o override deixar de valer, o teste quebra).

### Referências

- `Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig` (final readonly)
- `vendor/caresome/filament-auth-designer/resources/views/components/partials/media.blade.php` e `layouts/auth.blade.php` (`$livewire` no escopo do `@include`)
- `vendor/filament/filament/resources/views/components/logo.blade.php` (`fi-logo-light`/`fi-logo-dark` + `darkModeBrandLogo`) — padrão seguido
- `.ai/rules/css-filament.md` (por que não `dark:` utilities)
- `wikis/specs/feature/identidade-visual-da-organizacao/` (ADR-03/04 — guarda de painel na resolução da logo)

## Superfície Livewire

| Componente | Arquivo | Renderiza via | Interage com | Costura de teste |
|---|---|---|---|---|
| `App\Filament\Pages\Auth\TelaBloqueio` | `app/Filament/Pages/Auth/TelaBloqueio.php` | `filament-auth-designer::components.layouts.auth` → `partials.media` (override) | `getAuthDesignerConfig()`, `urlDaLogoEscura()` | `$this->get(route('lockscreen.app.page'))` com `lockscreen`+`tenant_corrente` em sessão (padrão de `tests/Kit/IdentidadeVisualTest.php`) |
| `App\Filament\Admin\Pages\ConfiguracoesDoKit` | `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` | SettingsPage do plugin spatie | `abaIdentidade()` — `Toggle` + 2 `FileUpload` | `Livewire::test(ConfiguracoesDoKit::class)` + `gravarConfiguracao()` |
