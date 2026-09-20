import { defineConfig } from 'vitepress';
import sidebar from './sidebar.json' with { type: 'json' };

/**
 * Spike do VitePress, para comparar com o do Starlight lado a lado.
 *
 * Diferenças que aparecem já aqui, no config:
 *
 * - a BARRA LATERAL é declarada, não descoberta. Não existe `autogenerate`: o `sidebar.json` é
 *   gerado pelo `converter.mjs` justamente para o config não virar uma árvore de 60 itens escrita
 *   à mão, que ninguém mantém em dia.
 * - a BUSCA local é do próprio VitePress (MiniSearch), configurada por locale.
 * - o i18n é nativo como no Starlight, e o seletor leva à PÁGINA EQUIVALENTE nos dois. Escrevi
 *   aqui o contrário antes de conferir; o que enganou foi o método: o VitePress emite um `<a
 *   href="/en/...">` e o Starlight um `<select>` com `<option value="/en/...">`, então procurar
 *   `href` no HTML do Starlight não acha nada e parece ausência de recurso. Comportamento
 *   idêntico, marcação diferente.
 */
export default defineConfig({
  title: 'Starter Kit Easy',
  description:
    'Kit inicial Laravel 13 + Filament 5: três painéis separados, convites, login social, proteção anti-robô e multi-tenancy opcional.',
  lang: 'pt-BR',
  cleanUrls: true,
  base: process.env.DOCS_BASE || '/',
  // A raiz não tem conteúdo próprio: os dois idiomas são irmãos, e `/` redireciona para o padrão.
  srcExclude: ['**/node_modules/**'],

  locales: {
    pt: {
      label: 'Português',
      lang: 'pt-BR',
      link: '/pt/',
      themeConfig: {
        nav: [
          { text: 'Começar', link: '/pt/comecar/' },
          { text: 'Recursos', link: '/pt/recursos/' },
          { text: 'Referência', link: '/pt/referencia/' },
        ],
        sidebar: sidebar['/pt/'],
        outline: { level: [2, 3], label: 'Nesta página' },
        docFooter: { prev: 'Anterior', next: 'Próxima' },
        darkModeSwitchLabel: 'Tema',
        returnToTopLabel: 'Voltar ao topo',
        lastUpdatedText: 'Atualizado em',
        editLink: {
          pattern:
            'https://github.com/gsferro/filament-starter-kit-easy/edit/main/site-vitepress/:path',
          text: 'Editar esta página no GitHub',
        },
      },
    },
    en: {
      label: 'English',
      lang: 'en',
      link: '/en/',
      themeConfig: {
        nav: [
          { text: 'Getting started', link: '/en/comecar/' },
          { text: 'Features', link: '/en/recursos/' },
          { text: 'Reference', link: '/en/referencia/' },
        ],
        sidebar: sidebar['/en/'],
        outline: { level: [2, 3], label: 'On this page' },
        editLink: {
          pattern:
            'https://github.com/gsferro/filament-starter-kit-easy/edit/main/site-vitepress/:path',
          text: 'Edit this page on GitHub',
        },
      },
    },
  },

  themeConfig: {
    socialLinks: [
      { icon: 'github', link: 'https://github.com/gsferro/filament-starter-kit-easy' },
    ],
    search: {
      provider: 'local',
      options: {
        locales: {
          pt: {
            translations: {
              button: { buttonText: 'Pesquisar', buttonAriaLabel: 'Pesquisar' },
              modal: {
                noResultsText: 'Nenhum resultado para',
                resetButtonTitle: 'Limpar',
                footer: { selectText: 'selecionar', navigateText: 'navegar', closeText: 'fechar' },
              },
            },
          },
        },
      },
    },
    footer: {
      message: 'Laravel 13 + Filament 5',
      copyright: 'gsferro/starter-kit-easy',
    },
  },
});
