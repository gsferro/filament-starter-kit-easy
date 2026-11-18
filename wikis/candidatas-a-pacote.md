# Candidatas a pacote — melhorias do kit que podem virar plugin Filament

> **Este documento é sobre o código que o KIT escreveu, não sobre pacotes de terceiros.**
>
> [`pacotes-candidatos.md`](pacotes-candidatos.md) responde *"o que existe lá fora e vale instalar"*.
> Este responde a pergunta inversa: **o que o kit implementou direto no próprio código e poderia sair
> dele como um plugin Filament independente** — para que outro projeto use só aquela melhoria, sem
> adotar o kit inteiro, e para que outras pessoas possam contribuir com a melhoria em si.
>
> Nada aqui é compromisso de extrair. Enquanto não houver um segundo consumidor, o código fica no kit
> (decisão do mantenedor, wiki `preferencias-de-aparencia`, RQ-11). A lista existe para a decisão de
> extrair ser tomada com o acoplamento já medido.

Companheiro de [`roadmap.md`](roadmap.md) (capacidades adiadas) e de
[`pacotes-candidatos.md`](pacotes-candidatos.md) (pacotes de terceiros).

---

## Como ler a tabela

- **Acoplamento ao kit**: o que precisaria virar contrato ou configuração do plugin. `config('kit.*')`
  quer dizer uma chave que a tela de configurações sobrepõe com o banco a cada boot; num plugin, ela
  vira configuração própria dele.
- **Esforço**: baixo (uma classe ou um hook, sem dependência do kit), médio (uma ou duas dependências
  do kit a abstrair), alto (preso a settings, `User`, `Tenant` ou ao Shield de várias formas).
- **Ordem**: esforço crescente e, no mesmo esforço, utilidade maior para um painel Filament qualquer.
- Levantamento por revisão do código em 2026-11-18. Ao extrair, confira de novo os arquivos: a lista
  envelhece a cada feature.

## A primeira candidata — preferências de aparência por usuário

| | |
|---|---|
| **O que faz** | Cada pessoa escolhe densidade do layout, tamanho da fonte, cor primária e modo do menu no Meu perfil, com a configuração da instalação como padrão; uma chave da instalação e, com organizações, uma chave por organização liberam a escolha |
| **Arquivos** | `app/Support/AparenciaDoUsuario.php`, `app/Support/DensidadeDoLayout.php`, `app/Support/TamanhoDaFonte.php`, `app/Support/ModoDoMenu.php`, `app/Support/CorPrimaria.php`, `app/Livewire/PreferenciasDeAparencia.php`, o render hook em `app/Providers/KitServiceProvider.php` e as linhas de menu e cor dos três providers de painel |
| **Acoplamento ao kit** | `config('kit.*')` para os padrões e a chave; coluna `users.aparencia`; coluna `tenants.aparencia_por_usuario`; seção do perfil montada sobre o `jeffgreco13/filament-breezy` |
| **Esforço** | médio — o mecanismo (variáveis CSS fora de layer e Closures avaliadas no render) não depende do kit; o que precisa de contrato é onde gravar a escolha (coluna configurável no model de usuário) e de onde vem a chave da organização |
| **Por que vale** | é útil para qualquer painel Filament e não existe equivalente pronto; densidade e fonte por variável CSS dispensam tema Vite e `npm run build` |
| **Wiki** | `wikis/specs/feat/preferencias-de-aparencia/` (e as ancestrais `layout-compact` e `paleta-do-filament-na-organizacao`) |

## As demais, da revisão do código

| # | Nome | O que faz | Arquivos principais | Acoplamento ao kit | Esforço |
|---|---|---|---|---|---|
| 1 | Avatar de iniciais local | Desenha o avatar de quem não tem foto como SVG embutido, sem o navegador chamar um serviço externo de avatar | `app/Support/AvatarDeIniciais.php` | nenhum — só a API de avatar e de cores do Filament | baixo |
| 2 | Botão "Voltar ao topo" | Botão flutuante em toda tela dos painéis, inclusive nas de plugins, por render hook | `resources/views/filament/voltar-ao-topo.blade.php`, `resources/css/filament/voltar-ao-topo.css`, `app/Providers/Concerns/ConfiguraFilamentGlobal.php` | só o registro do hook e do asset | baixo |
| 3 | Badge de contagem no menu | Badge com a contagem do item de menu tirada da query do Resource (respeita escopos), zero em cinza | `app/Filament/Concerns/BadgeContagemNavegacao.php` | nenhum | baixo |
| 4 | Exportação CSV sem injeção de fórmula | Classe base de `Exporter` que neutraliza `=`, `+`, `-` e `@` no começo de toda célula | `app/Support/ImportExport/ExportadorDoKit.php` | nenhum | baixo |
| 5 | Ordem das cascade layers | Fixa a ordem das `@layer` no topo do `<head>`, para CSS de plugin não reordenar a do Filament | `app/Providers/KitServiceProvider.php` (`configureOrdemDasCascadeLayers`), `resources/css/filament/kit.css` | nenhum | baixo |
| 6 | Desbloqueio volta à página | Middleware sobre o `marjose123/filament-lockscreen` que guarda a URL interceptada e volta a ela depois do desbloqueio | `app/Http/Middleware/BloquearSessaoComRetorno.php` | só o bind no `AppServiceProvider` | baixo — talvez melhor como contribuição ao próprio pacote |
| 7 | Hub de cartões descoberto | Transforma os destinos navegáveis e autorizados do painel em cartões, agrupados como no menu | `app/Filament/Concerns/DescobreCardsDoPainel.php`, `resources/css/filament/cards.css` | `harvirsidhu/filament-cards`; a chave do hub fica nas páginas | baixo — talvez melhor como contribuição ao próprio pacote |
| 8 | Busca ⌘K com autorização | Categorias e ações "Criar X" da busca global que respeitam `canAccess()`/`canCreate()`, mais o gatilho na barra e o CSS do overlay | `app/Filament/Spotlight/AcoesDeCriacao.php`, `app/Filament/Spotlight/PagesAutorizadasCategory.php`, `resources/css/filament/spotlight.css`, `resources/views/filament/spotlight-trigger.blade.php` | `wezlo/filament-search-spotlight` | baixo a médio — generalizar o CSS |
| 9 | E-mail verificado decidido por request | Middleware que decide a cada request se exige e-mail verificado, para a chave ser editável na tela | `app/Http/Middleware/ExigirEmailVerificado.php` | `RegistroAberto` e `config('kit.registro.verificar_email')` | baixo |
| 10 | Visões salvas com interruptor e permissão | Embrulho do `sehsah/filament-remember-table` que fecha as superfícies do `$wire` quando a opção está desligada e exige permissão | `app/Filament/Concerns/VisoesDeTabela.php` | `config('kit.tabelas.visoes_salvas')` e a permissão `Usar:VisoesDeTabela` | baixo — talvez melhor como contribuição ao próprio pacote |
| 11 | Rodapé com versão | Assinatura única (sistema e versão) no rodapé dos painéis e das telas de login | `app/Support/AssinaturaDoRodape.php`, `resources/views/filament/assinatura-do-rodape.blade.php` | `config('kit.exibir_versao')` e `config('kit.version')` | baixo |
| 12 | Permissão que decide Page e Widget | Traits que tornam `View:{Page}` e `View:{Widget}` do Shield efetivos, preservando a regra local de cada tela | `app/Filament/Concerns/ExigePermissaoDaTela.php`, `app/Filament/Concerns/ExigePermissaoDoWidget.php`, `app/Support/PermissaoDaTela.php`, `app/Support/PoliciesDeVendor.php` | `bezhansalleh/filament-shield`; a lista de policies de vendor é do kit | médio |
| 13 | Anti-robô com falha fechada | Gerenciador e decorador do `ddr/filament-captcha` que falham fechados, com log e configuração por request, mais o campo que reinicia o widget | `app/Support/GerenciadorAntiRobo.php`, `app/Support/VerificacaoAntiRobo.php`, `app/Filament/Forms/Components/CampoAntiRobo.php` | `ConfiguracaoDoLogin`, `config('kit.login.anti_robo.*')` e o canal de log `autenticacao` | médio |
| 14 | Definir senha por e-mail no perfil | Bloco do Meu perfil que manda o link de definição de senha para quem entrou por login social e encerra a sessão | `app/Livewire/DefinirSenhaPorEmail.php`, `resources/views/livewire/definir-senha-por-email.blade.php` | a página de perfil do `jeffgreco13/filament-breezy` | médio |
| 15 | Cabeçalho do painel composto | Marca "projeto \| painel \| logo" e o bloco nome, e-mail e papel do usuário na barra do topo | `app/Support/CabecalhoDoPainel.php`, `resources/views/filament/usuario-no-cabecalho.blade.php` | `IdentidadeDoKit`, `Paineis`, `Papeis`, `User::papelNoPainelCorrente()`, `Tenant` e `config('kit.cabecalho.*')` | médio |
| 16 | Login unificado entre painéis | Um `/login` só, com destino pela URL pretendida, pelo único painel acessível ou por uma tela de escolha | `app/Support/DestinoAposLogin.php`, `app/Filament/Pages/Auth/EscolhaDePainel.php` | `Paineis`, `canAccessPanel()`, rotas próprias e o desenho das telas de acesso | médio a alto |
| 17 | Aparência das telas de acesso | Layout "painel de marca" (cor, gradiente, logos, destaque) por instalação ou por organização, com pré-visualização do rascunho | `app/Support/AparenciaDasTelasDeAcesso.php`, `app/Filament/Concerns/VesteAparenciaDasTelas.php`, `app/Filament/Pages/Auth/PreviewDasTelasDeAcesso.php` | `config('kit.identidade.*')`, coluna JSON em `Tenant`, a tela de configurações | alto |
| 18 | Dock de favoritos autorizados | Favoritos por pessoa no dock de atalhos, com catálogo das telas registradas e autorização avaliada no painel e na organização de destino | `app/Support/DockDeAtalhos.php`, `app/Filament/Dock/PluginDoDock.php` | `User`, `Tenant`, Shield, `Paineis`, `ContextoDePapeis` e `config('kit.dock.*')` | alto |

**Sobreposição com a primeira candidata.** `DensidadeDoLayout` e `CorPrimaria` (que a revisão também
apontou como candidatas isoladas, de esforço baixo) são partes do pacote de preferências de aparência:
extrair as duas sozinhas criaria dois pacotes que o terceiro teria de juntar. Elas ficam dentro da
primeira candidata.

## O que ficou de fora, e por quê

- **Ferramentas de instalação do kit** (`kit:install`, `kit:update`, `kit:arte`, `kit:tenancy`): só fazem
  sentido para o próprio kit.
- **Configuração pura de pacote de terceiro** (padrões de tabela, carregamento da tabela, seletor de
  idioma): não há código do kit a extrair, só escolhas.
- **Utilidades Laravel que não são Filament** (`RaizDeUrlSemPublic`, `ProxiesConfiaveis`,
  `BooleanoDoEnv`, `ContextoDePapeis`): seriam pacotes Laravel, outra conversa.
- **O widget do assistente de IA** e **a importação com fronteira de organização**: dependem do agente
  do kit e do `BelongsToTenant` do kit, e o esforço seria alto sem ganho claro para outro painel.

## Como acrescentar uma candidata

Ao terminar uma feature que soma comportamento ao Filament com código próprio, pergunte: *outro
painel Filament usaria isto sem o resto do kit?* Se sim, acrescente uma linha com os arquivos, o
acoplamento e o esforço, e cite a wiki da feature.
