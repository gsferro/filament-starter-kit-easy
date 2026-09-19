# Plano B: o mesmo site em VitePress

> **Este diretório não é o site.** O site é o `site/`, em Astro Starlight, escolhido em
> 2026-09-19. Isto aqui é a alternativa avaliada lado a lado na mesma decisão, mantida em
> funcionamento para o caso de o Starlight deixar de servir.

## Por que ele continua no repositório

Escolher gerador é decisão que só se testa vivendo com ela. Um segundo caminho **que comprovadamente
constrói** vale muito mais que um parágrafo dizendo "se der errado, dá para usar VitePress" — porque
a alternativa em prosa nunca foi executada, e a primeira vez que alguém tentar vai descobrir os
problemas sob pressão, que é o pior momento.

Este spike foi construído do mesmo conteúdo, com o mesmo conferidor de links, e fechou:

| | |
|---|---|
| Páginas construídas | 68 |
| Links internos conferidos | 2.573, **0 quebrados** |
| Build | 7,8s |
| Saída | 6,5 MB |
| Busca | MiniSearch local, índice por idioma |

## Quando trocar

Nenhum destes é hipotético — são os modos de falha que a escolha assumiu:

- **O Starlight quebra num upgrade e não dá para consertar por variável.** As quatro regras de CSS
  que descem a componente (`sl-link-button`, `card`, `hero`, `table`) são o ponto frágil: elas
  dependem de nome de classe do tema, e nome de classe do tema não é contrato. O VitePress fez a
  mesma identidade **só com variáveis** `--vp-c-brand-*`.
- **O Astro sobe um major que custa caro.** O VitePress tem uma dependência a menos de decisão:
  ele é o gerador, não uma integração sobre um gerador.
- **A árvore de arquivos deixa de ser boa fonte para a barra lateral.** Aqui é o oposto do
  Starlight — o VitePress **exige** declarar, e o `sidebar.json` é gerado pelo `converter.mjs`.

## O que ele faz melhor, e que o Starlight precisou contornar

Vale registrar porque não é opinião, foi medido na conversão:

1. **Links relativos.** O VitePress resolve `[x](../recursos/y.md)` sozinho. O Starlight publica
   `convites/` onde o Jekyll publicava `convites.html`, então **toda** profundidade de `../` muda —
   e o conversor do `site/` precisa reescrever cada link como caminho absoluto. Link mal convertido
   chega numa página que às vezes existe, o que falha em silêncio.
2. **O H1.** O VitePress renderiza o markdown como está. No Starlight o `title` do front-matter
   vira o H1, então o H1 do corpo tem de ser removido — 66 páginas.
3. **Tema sem override de componente**, como acima.

## O que ele faz pior

1. **TOC trunca com reticências** em vez de quebrar linha, e os títulos deste repositório são
   longos: *"O dashboard dinâmico é um i…"*.
2. **Barra lateral declarada**, não descoberta — mais uma coisa que pode envelhecer calada.

## Como rodar

```bash
cd site-vitepress
npm install
node converter.mjs   # regenera as páginas e o sidebar.json a partir de docs/
npm run build
npm run preview      # porta 4322
node verifica-links.mjs
```

> O `converter.mjs` lê de **`../docs/`**, a árvore do Jekyll. Se o `docs/` for removido quando a
> migração para o Starlight se completar, aponte-o para `../site/src/content/docs/` e trate o
> front-matter do Starlight em vez do do Jekyll — é o único ajuste necessário.
