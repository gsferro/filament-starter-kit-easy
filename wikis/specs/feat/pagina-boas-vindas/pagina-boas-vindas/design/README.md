# Desenho da tela (RQ-08)

- **Fonte**: `Main.dc.html` (o artboard) + `canvas.json` (o layout do canvas).
- **Publicado em**: https://claude.ai/code/artifact/cd1677da-a5f4-44f0-9995-70baf64e0552
  — o chip `dark` acima do artboard alterna claro/escuro.

O arquivo que a skill `design` gera para publicar (`boas-vindas-do-starter-kit.html`, ~2 MB, o
editor de canvas embutido) **não** é versionado: é artefato de build, e 2 MB de código de editor
não pertencem ao repositório do kit. Para regerar, a partir desta pasta:

```
node "<base da skill design>/seed-canvas.mjs" \
  --template "<base da skill design>/payload.template.html" \
  --out boas-vindas-do-starter-kit.html \
  --title "Boas-vindas do Starter Kit" \
  --artboard Main.dc.html \
  --canvas canvas.json
```

## Os tokens não foram inventados

Os `oklch` do artboard são os que `FilamentAsset::renderStyles()` emite nesta árvore, e as classes
dos cartões são as de `resources/css/filament/cards.css`. A fonte é Inter porque é a que o Filament
registra (`Font::make('inter', dist/fonts/inter)`, `FilamentServiceProvider.php:100`).

## Divergências entre o desenho e a tela

Duas, ambas deliberadas e detalhadas no `03-progresso.md` → `## Desvios do Plano`:

1. o badge de versão do cabeçalho não foi implementado (a versão aparece uma vez, na infolist);
2. a composição da segunda seção mudou — as três retenções viraram uma entrada, e as duas vagas
   liberadas foram para "Versão do kit" e "Idiomas do painel".
