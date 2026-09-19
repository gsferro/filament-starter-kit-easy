// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

/**
 * Spike: o mesmo conteúdo do site atual, servido pelo Starlight.
 *
 * O que muda em relação ao Jekyll embutido do GitHub Pages:
 * - i18n é nativo, então `pt/` e `en/` deixam de ser convenção manual e passam a ser
 *   locales de verdade — com seletor de idioma e link para a página equivalente.
 * - a busca (Pagefind) indexa POR IDIOMA, resolvendo a limitação aceita na ADR-01.
 * - o sidebar sai da árvore de arquivos; `nav_order` virou `sidebar.order`.
 */
export default defineConfig({
  site: 'https://gsferro.github.io',
  // Para a prévia o site é servido na raiz. No deploy real isto volta a ser
  // `/filament-starter-kit-easy`, que é o que o `baseurl` do Jekyll guarda hoje.
  base: process.env.DOCS_BASE || undefined,
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
   */
  redirects: { '/': '/pt/' },
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
      lastUpdated: true,
      customCss: ['./src/styles/kit.css'],
      sidebar: [
        {
          label: 'Começar',
          translations: { en: 'Getting started' },
          items: [{ autogenerate: { directory: 'comecar' } }],
        },
        {
          label: 'Autenticação',
          translations: { en: 'Authentication' },
          items: [{ autogenerate: { directory: 'autenticacao' } }],
        },
        {
          label: 'Recursos',
          translations: { en: 'Features' },
          items: [{ autogenerate: { directory: 'recursos' } }],
        },
        {
          label: 'Operação',
          translations: { en: 'Operations' },
          items: [{ autogenerate: { directory: 'operacao' } }],
        },
        {
          label: 'Referência',
          translations: { en: 'Reference' },
          items: [{ autogenerate: { directory: 'referencia' } }],
        },
      ],
    }),
  ],
});
