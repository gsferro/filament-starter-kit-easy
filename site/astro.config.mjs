// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import sidebar from './sidebar.json' with { type: 'json' };

/**
 * Spike: o mesmo conteúdo do site atual, servido pelo Starlight.
 *
 * O que muda em relação ao Jekyll embutido do GitHub Pages:
 * - i18n é nativo, então `pt/` e `en/` deixam de ser convenção manual e passam a ser
 *   locales de verdade — com seletor de idioma e link para a página equivalente.
 * - a busca (Pagefind) indexa POR IDIOMA, resolvendo a limitação aceita na ADR-01.
 * - o sidebar sai da árvore de arquivos; `nav_order` virou `sidebar.order`.
 */

/*
 * O prefixo base, normalizado UMA vez — porque ele entra em dois lugares e o Astro só cuida de um.
 *
 * Para a prévia o site é servido na raiz (`DOCS_BASE` ausente, prefixo vazio). No deploy real ele
 * é `/filament-starter-kit-easy`, que é o que o `baseurl` do Jekyll guardava.
 */
const base = (process.env.DOCS_BASE || '').replace(/\/+$/, '');

export default defineConfig({
  site: 'https://gsferro.github.io',
  base: base || undefined,
  /*
   * `/` vai para o idioma padrão, e as 54 rotas de folha do Jekyll vão para a forma nova.
   *
   * A migração muda `/pt/comecar/instalacao-avancada.html` para
   * `/pt/comecar/instalacao-avancada/` — TODA rota de folha muda de forma, então link salvo e
   * indexação apontam para o vazio. Índice de seção não entra: `/pt/comecar/` já era assim nos
   * dois, e redirect de rota que não mudou é ruído que esconde os que importam.
   *
   * Os stubs ficam em `public/` (copiado literalmente) porque o `redirects` do Astro trata a
   * chave como rota e o `build.format` padrão transforma `/pt/x.html` no diretório `x.html/`.
   * O motivo longo está no `converter.mjs`; aqui fica só a raiz, que é rota de verdade.
   *
   * O DESTINO LEVA O `base` À MÃO, e é isto que mantém a porta da frente de pé.
   *
   * O Astro aplica o `base` à CHAVE do redirect — o stub sai em `dist/index.html` e é servido em
   * `/filament-starter-kit-easy/` — e **não** ao valor. Com `'/pt/'` cru, o stub publicado dizia
   * `url=/pt/`, que sob o domínio do Pages é `gsferro.github.io/pt/`: fora do site do projeto, e
   * 404. A raiz do site — o endereço que todo README e todo link externo usam — caía nele, e as
   * páginas internas respondiam 200 o tempo todo, então o defeito parecia ser "o site inteiro"
   * para quem chegava pela porta da frente e invisível para quem já estava dentro.
   *
   * Com prefixo vazio isto volta a ser `/pt/`, que é o certo para a prévia na raiz.
   */
  redirects: { '/': `${base}/pt/` },
  integrations: [
    starlight({
      title: 'Starter Kit Easy',
      description:
        'Kit inicial Laravel 13 + Filament 5: três painéis separados, convites, login social, proteção anti-robô e multi-tenancy opcional.',
      defaultLocale: 'pt',
      locales: {
        pt: { label: 'Português', lang: 'pt-BR' },
        en: { label: 'English', lang: 'en' },
      },
      head: [
      /*
       * Torna focável por teclado toda região que REALMENTE rola na horizontal.
       *
       * O axe reprova `scrollable-region-focusable` (serious): elemento que rola e não recebe
       * foco é conteúdo que quem navega por teclado não alcança. Medido no site construído:
       * **26 páginas**, em blocos de código e em tabelas.
       *
       * As tabelas são nossas — o Starlight não as embrulha, e foi a regra `overflow-x: auto` do
       * `kit.css` que as tornou roláveis. Os blocos de código são do tema. Este script cobre os
       * dois, e só quando há transbordo de verdade (`scrollWidth > clientWidth`), o que evita pôr
       * na ordem de tabulação dezenas de elementos que não rolam.
       *
       * **Por que não um plugin rehype**: `markdown.rehypePlugins` no Astro 7 exige instalar
       * `@astrojs/markdown-remark` e trocar o processador de markdown inteiro. Medido — o build
       * recusa com essa mensagem. Trocar o pipeline de renderização para resolver um `tabindex`
       * é desproporcional.
       *
       * Sem `role="region"`: `role` exige nome acessível, e nome genérico em site bilíngue
       * criaria uma violação nova no lugar desta.
       */
      {
        tag: 'script',
        content: [
          'const focavel = () => document',
          "  .querySelectorAll('.sl-markdown-content table, .sl-markdown-content pre')",
          '  .forEach((el) => {',
          '    if (el.scrollWidth > el.clientWidth) el.setAttribute("tabindex", "0");',
          '  });',
          "document.addEventListener('DOMContentLoaded', focavel);",
          "document.addEventListener('astro:page-load', focavel);",
        ].join(String.fromCharCode(10)),
      },
      ],

      social: [
        {
          icon: 'github',
          label: 'GitHub',
          href: 'https://github.com/gsferro/filament-starter-kit-easy',
        },
      ],
      editLink: {
        baseUrl: 'https://github.com/gsferro/filament-starter-kit-easy/edit/main/site/',
      },
      /*
       * O ícone, e ele estava faltando — em TODA página.
       *
       * Sem esta linha o Starlight aponta para `/favicon.svg`, que nunca existiu em `site/public/`:
       * 122 páginas publicadas pedindo um arquivo que responde 404. Não aparece na navegação, não
       * quebra nada visível, e é o 404 mais repetido do site.
       *
       * O arquivo agora existe em `site/public/favicon.svg`, no vermelho do Laravel de onde o
       * `kit.css` já deriva o acento. O `favicon.ico` da aplicação Laravel não servia: tem 0 byte.
       *
       * A linha fica explícita mesmo apontando para o padrão do Starlight, porque foi justamente
       * o padrão implícito que ficou dois deploys apontando para o vazio sem ninguém notar.
       */
      favicon: '/favicon.svg',
      lastUpdated: true,
      customCss: ['./src/styles/kit.css'],
      /*
       * A barra lateral é DECLARADA, não descoberta.
       *
       * O `autogenerate` não funciona com o conteúdo fora da raiz do projeto Astro (ADR-02):
       * os grupos renderizam sem nenhum item. Os `slug` abaixo são gerados pelo `converter.mjs`
       * a partir da árvore, sem prefixo de idioma — o Starlight os localiza para cada locale.
       */
      sidebar,
    }),
  ],
});
