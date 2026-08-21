---
paths:
  - 'resources/css/filament/**'
---

# Css Filament

## Utilitária que blade de vendor emite precisa existir no CSS do kit
O kit não tem tema Filament customizado (`viteTheme()` não é usado em nenhum painel), e a CSS pré-compilada do Filament 5 carrega quase só as classes `fi-*`. Pacote que renderiza blade própria com utilitárias Tailwind **não** ganha estilo de graça: medido no `harvirsidhu/filament-cards`, 51 das 53 utilitárias que a blade dele emite não existem lá.

Por isso `resources/css/filament/cards.css` é um subconjunto escrito à mão, escopado em `.kit-cards-page`, registrado por `FilamentAsset::register()` em `KitServiceProvider::configureCorrecoesDeCss()`.

**O modo de falhar é silencioso**: utilitária ausente produz HTML byte a byte correto e sem estilo nenhum. `assertSee`, `assertOk` e todo teste de componente ficam verdes, e a grade vira uma lista de links soltos.

Ao usar um recurso NOVO de um pacote assim (uma opção do componente, um campo que a blade só renderiza sob `@if`), abra a blade do vendor, liste as classes daquele bloco e confira cada uma no arquivo do kit ANTES de assumir que funciona. Depois de editar: `php artisan filament:assets`.

Ver ADR-02 de `wikis/specs/main/hub-de-navegacao-em-cards/` e ADR-04 de `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/`.
