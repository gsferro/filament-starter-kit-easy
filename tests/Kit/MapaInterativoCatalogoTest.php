<?php

/**
 * Guardas do catálogo de features do mapa interativo (lote G1a do passo 8 da feature-wiki
 * `mapa-interativo-do-kit`): CT-01 a CT-05 e CT-35, regras R1 a R5 do `04-casos-de-teste.md`.
 *
 * Toda guarda é uma função aplicada ao publicado e a cópias adulteradas EM MEMÓRIA, nunca ao arquivo
 * real, e devolve aceita ou reprova com motivos que nomeiam o item. As linhas de controle chamam a
 * MESMA função de decisão que a linha "publicado".
 *
 * A leitura do catálogo (`linhasDoCatalogo()`) e do modelo (`modeloDoMapa()`) e o motivo de enfeite
 * (`motivoDeEnfeite()`) moram em `tests/Pest.php`, porque as outras guardas do mapa também os usam
 * (`.ai/rules/testes.md`).
 */
if (! function_exists('catalogoTextoDaLinha')) {
    /**
     * @param  list<string>  $celulas
     */
    function catalogoTextoDaLinha(array $celulas): string
    {
        return '| '.implode(' | ', $celulas).' |';
    }
}

if (! function_exists('catalogoNormalizar')) {
    /**
     * O texto sem a marcação Markdown (link vira o texto dele; negrito, crase e tachado saem), com os
     * espaços colapsados e em minúsculas.
     */
    function catalogoNormalizar(string $texto): string
    {
        $texto = (string) preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $texto);
        $texto = str_replace(['**', '`', '~~', '*'], '', $texto);
        $texto = (string) preg_replace('/\s+/u', ' ', $texto);

        return mb_strtolower(trim($texto));
    }
}

if (! function_exists('catalogoTemPalavra')) {
    /**
     * O termo aparece como palavra inteira, sem diferença de maiúscula e com acento.
     */
    function catalogoTemPalavra(string $texto, string $termo): bool
    {
        return preg_match('/(?<![\p{L}\p{N}_])'.preg_quote($termo, '/').'(?![\p{L}\p{N}_])/iu', $texto) === 1;
    }
}

if (! function_exists('catalogoOrigensDoGlossario')) {
    /**
     * As três origens do glossário, na mesma ordem nos dois idiomas.
     *
     * @return array{pt: list<string>, en: list<string>}
     */
    function catalogoOrigensDoGlossario(): array
    {
        return [
            'pt' => ['pacote', 'código próprio', 'pacote com customização própria'],
            'en' => ['package', 'own code', 'package with own customization'],
        ];
    }
}

if (! function_exists('catalogoOrigemDaLinha')) {
    /**
     * @param  list<string>  $celulas
     * @return array{origem: string, pacotes: list<string>}
     */
    function catalogoOrigemDaLinha(array $celulas): array
    {
        $celula  = $celulas[2] ?? '';
        $posicao = mb_strpos($celula, ':');
        $origem  = $posicao === false ? $celula : mb_substr($celula, 0, $posicao);
        $resto   = $posicao === false ? '' : mb_substr($celula, $posicao + 1);

        preg_match_all('/`([^`]+)`/u', $resto, $pacotes);
        $lista = $pacotes[1];
        sort($lista);

        return ['origem' => mb_strtolower(trim(str_replace('*', '', $origem))), 'pacotes' => $lista];
    }
}

if (! function_exists('catalogoTokens')) {
    /**
     * @return list<string>
     */
    function catalogoTokens(string $texto): array
    {
        preg_match_all('/`([^`]+)`/u', $texto, $tokens);
        $lista = array_values(array_unique($tokens[1]));
        sort($lista);

        return $lista;
    }
}

if (! function_exists('catalogoRotas')) {
    /**
     * Os caminhos de rota do "onde fica": tokens entre crases que começam por `/`, com `{painel}` e
     * `{panel}` tidos como o mesmo curinga.
     *
     * @param  list<string>  $celulas
     * @return list<string>
     */
    function catalogoRotas(array $celulas): array
    {
        $rotas = array_filter(catalogoTokens($celulas[3] ?? ''), fn (string $token): bool => str_starts_with($token, '/'));
        $rotas = array_values(array_unique(array_map(fn (string $rota): string => str_replace('{panel}', '{painel}', $rota), $rotas)));
        sort($rotas);

        return $rotas;
    }
}

if (! function_exists('catalogoPaginasDaLinha')) {
    /**
     * As páginas a que a linha leva por link relativo, resolvidas a partir de
     * `operacao/roteiro-de-features/`, relativas a `docs/{idioma}/` e sem extensão.
     *
     * @param  list<string>  $celulas
     * @return list<string>
     */
    function catalogoPaginasDaLinha(array $celulas): array
    {
        preg_match_all('/\]\(([^)\s]+)\)/u', implode(' | ', $celulas), $links);
        $paginas = [];

        foreach ($links[1] as $link) {
            $link = (string) preg_replace('/[#?].*$/u', '', $link);

            if ($link === '' || preg_match('#^([a-z]+:|/)#i', $link) === 1) {
                continue;
            }

            $segmentos = ['operacao', 'roteiro-de-features'];

            foreach (explode('/', $link) as $segmento) {
                if ($segmento === '..') {
                    array_pop($segmentos);
                } elseif ($segmento !== '' && $segmento !== '.') {
                    $segmentos[] = $segmento;
                }
            }

            $paginas[] = implode('/', $segmentos);
        }

        $paginas = array_values(array_unique($paginas));
        sort($paginas);

        return $paginas;
    }
}

if (! function_exists('catalogoRemocao')) {
    /**
     * A marca de removida na célula "Feature" — "~~Passkeys~~ (removida: motivo)" — e o motivo que
     * vem depois dos dois-pontos.
     *
     * @param  list<string>  $celulas
     * @return array{removida: bool, motivo: string}
     */
    function catalogoRemocao(array $celulas): array
    {
        if (preg_match('/(?<![\p{L}])(removid[ao]|removed)(?![\p{L}])(.*)$/iu', $celulas[1] ?? '', $marca) !== 1) {
            return ['removida' => false, 'motivo' => ''];
        }

        $resto  = trim($marca[2]);
        $motivo = str_starts_with($resto, ':') ? trim(mb_substr($resto, 1)) : '';

        return ['removida' => true, 'motivo' => trim($motivo, " \t)*")];
    }
}

if (! function_exists('catalogoBase')) {
    /**
     * A base congelada (commit `d9608c5a`): cada F-nn com o termo que identifica a sua feature — o
     * texto da coluna "Feature" da base, sem a marcação.
     *
     * @return array{pt: array<string, string>, en: array<string, string>}
     */
    function catalogoBase(): array
    {
        return [
            'pt' => [
                'F-01'  => 'Login nos três painéis', 'F-02' => 'Recuperação de senha', 'F-03' => 'Registro por convite',
                'F-03a' => 'Cadastro aberto (opt-in)', 'F-03b' => 'Aprovação de cadastro (opt-in)', 'F-03c' => 'Validação de e-mail (opt-in)',
                'F-04'  => 'Autenticação em dois fatores', 'F-05' => 'Passkeys', 'F-06' => 'Bloqueio de sessão',
                'F-07'  => 'Meu perfil, avatar e senha', 'F-08' => 'Impersonate', 'F-09' => 'O papel decide o painel (roles.painel)',
                'F-10'  => '403 legível no painel errado', 'F-11' => 'master_global vence por Gate::before',
                'F-12'  => 'Papéis e permissões agrupados por painel', 'F-13' => 'panel_user não administra', 'F-14' => 'Sem papel, ninguém entra',
                'F-62'  => 'Toda tela do painel tem permissão própria, e ela é consultada',
                'F-63'  => 'Toda action e todo link do kit tem permissão própria',
                'F-64'  => 'Action e link novos não nascem abertos por esquecimento',
                'F-65'  => 'Boas-vindas na raiz, com o que a instalação personalizou', 'F-66' => 'A raiz herda tema e cor do projeto',
                'F-67'  => 'As três exceções estão declaradas, não escondidas', 'F-68' => 'Hub de navegação em cartões',
                'F-15'  => 'Convite individual', 'F-16' => 'Convite para quem já tem conta', 'F-17' => 'Caixa de convites recebidos',
                'F-18'  => 'Convite em massa', 'F-19' => 'Lembretes automáticos', 'F-20' => 'Reenviar / revogar', 'F-21' => 'Ligar o modo',
                'F-22'  => 'Painel por organização', 'F-23' => 'Cadastro de organizações', 'F-24' => 'Vínculo de usuários', 'F-25' => 'admin_app',
                'F-26'  => 'Escopo por trait', 'F-27' => 'Identidade visual: cor', 'F-28' => 'Identidade visual: logo',
                'F-69'  => 'A ficha do /app não conta onde mais a pessoa está', 'F-29' => 'Usuários', 'F-30' => 'Catálogo de agentes de IA',
                'F-31'  => 'Autoria de onboarding', 'F-32' => 'Dashboard preenchido', 'F-33' => 'Health checks', 'F-34' => 'Backups',
                'F-35'  => 'Filas', 'F-36' => 'Logs', 'F-37' => 'Auditoria de alterações', 'F-38' => 'Trilha de acesso',
                'F-39'  => 'Central de comandos', 'F-40' => 'Pulse', 'F-41' => 'Grafo de dependências', 'F-42' => 'Releases do Composer',
                'F-43'  => 'Execuções de IA', 'F-44' => 'Limpar caches', 'F-57' => 'Exceções agrupadas', 'F-58' => 'Trilha de e-mails',
                'F-59'  => 'Lixeira', 'F-45' => 'Busca ⌘K', 'F-46' => 'Badges de contagem animados', 'F-47' => 'Centro de notificações',
                'F-48'  => 'Troca de painel', 'F-49' => 'Tema claro/escuro', 'F-50' => 'Colunas redimensionáveis', 'F-51' => 'Indicador de ambiente',
                'F-52'  => 'Páginas de erro brandadas', 'F-60' => 'Seletor de idioma', 'F-61' => 'Anexos e mídia',
                'F-70'  => 'Cabeçalho rico nas telas de registro', 'F-71' => 'Avatar de iniciais desenhado aqui dentro',
                'F-53'  => 'Chat do assistente', 'F-54' => 'Guardrails encadeados', 'F-55' => 'Ledger de execuções', 'F-56' => 'Inferência local',
            ],
            'en' => [
                'F-01'  => 'Login on the three panels', 'F-02' => 'Password recovery', 'F-03' => 'Registration by invitation',
                'F-03a' => 'Open sign-up (opt-in)', 'F-03b' => 'Sign-up approval (opt-in)', 'F-03c' => 'E-mail verification (opt-in)',
                'F-04'  => 'Two-factor authentication', 'F-05' => 'Passkeys', 'F-06' => 'Session lock',
                'F-07'  => 'My profile, avatar and password', 'F-08' => 'Impersonate', 'F-09' => 'The role decides the panel (roles.painel)',
                'F-10'  => 'Readable 403 on the wrong panel', 'F-11' => 'master_global wins by Gate::before',
                'F-12'  => 'Roles and permissions grouped by panel', 'F-13' => 'panel_user does not administer', 'F-14' => 'Without a role, nobody enters',
                'F-62'  => 'Every screen in the panel has its own permission, and it is enforced',
                'F-63'  => 'Every action and every link in the kit has its own permission',
                'F-64'  => 'A new action or link does not ship open by forgetfulness',
                'F-65'  => 'Welcome page at the root, with what the installation customised', 'F-66' => 'The root inherits the project theme and colour',
                'F-67'  => 'The three exceptions are declared, not hidden', 'F-68' => 'Card navigation hub',
                'F-15'  => 'Individual invitation', 'F-16' => 'Invitation for someone who already has an account', 'F-17' => 'Received invitations box',
                'F-18'  => 'Bulk invitation', 'F-19' => 'Automatic reminders', 'F-20' => 'Resend / revoke', 'F-21' => 'Turn the mode on',
                'F-22'  => 'Panel by organization', 'F-23' => 'Organization CRUD', 'F-24' => 'User linking', 'F-25' => 'admin_app',
                'F-26'  => 'Scope by trait', 'F-27' => 'Visual identity: color', 'F-28' => 'Visual identity: logo',
                'F-69'  => 'The /app record page does not tell where else the person is', 'F-29' => 'Users', 'F-30' => 'AI agent catalog',
                'F-31'  => 'Onboarding authoring', 'F-32' => 'Filled dashboard', 'F-33' => 'Health checks', 'F-34' => 'Backups',
                'F-35'  => 'Queues', 'F-36' => 'Logs', 'F-37' => 'Change auditing', 'F-38' => 'Access trail',
                'F-39'  => 'Command center', 'F-40' => 'Pulse', 'F-41' => 'Dependency graph', 'F-42' => 'Composer releases',
                'F-43'  => 'AI runs', 'F-44' => 'Clear caches', 'F-57' => 'Grouped exceptions', 'F-58' => 'Mail trail',
                'F-59'  => 'Recycle bin', 'F-45' => '⌘K search', 'F-46' => 'Animated count badges', 'F-47' => 'Notification center',
                'F-48'  => 'Panel switch', 'F-49' => 'Light/dark theme', 'F-50' => 'Resizable columns', 'F-51' => 'Environment indicator',
                'F-52'  => 'Branded error pages', 'F-60' => 'Language switcher', 'F-61' => 'Attachments and media',
                'F-70'  => 'Rich header on the record screens', 'F-71' => 'Initials avatar drawn in here',
                'F-53'  => 'Assistant chat', 'F-54' => 'Chained guardrails', 'F-55' => 'Run ledger', 'F-56' => 'Local inference',
            ],
        ];
    }
}

if (! function_exists('catalogoReadmeDaBase')) {
    /**
     * Os 46 itens da lista "O que já vem pronto" / "What's already there" do README da base
     * (`d9608c5a`), pelo título (ADV2-03), cada um com a F-nn que o cobre e, nos quinze que a base do
     * catálogo não cobre, o termo que a linha casada tem de trazer como palavra inteira (ADV-01 e a
     * correção de causa a do passo 8, gravada na R5 do `04`).
     *
     * @return list<array{0: string, 1: string, 2: list<string>, 3: ?string, 4: ?string}>
     */
    function catalogoReadmeDaBase(): array
    {
        return [
            ['Página de boas-vindas na rota `/`', 'Welcome page on the `/` route', ['F-65'], null, null],
            ['Shield', 'Shield', ['F-12'], null, null],
            ['Breezy', 'Breezy', ['F-07', 'F-04'], null, null],
            ['Ficha de usuário em tela cheia', 'Full-screen user record page', ['F-29', 'F-69'], null, null],
            ['Avatar desenhado aqui dentro', 'Avatar drawn in here', ['F-71'], null, null],
            ['Auth Designer', 'Auth Designer', ['F-01'], null, null],
            ['Aparência das telas de acesso escolhida na tela', 'Sign-in screen appearance chosen on screen', ['F-75'], 'telas de acesso', 'sign-in screen'],
            ['Registro aberto opcional', 'Optional open sign-up', ['F-03a', 'F-03b', 'F-03c'], null, null],
            ['"+" no e-mail do cadastro, permitido ou bloqueado', '"+" in the sign-up e-mail, allowed or blocked', ['F-76'], '"+"', '"+"'],
            ['Lockscreen', 'Lockscreen', ['F-06'], null, null],
            ['Impersonate, log de autenticação, auditoria de alterações', 'Impersonate, authentication log, change auditing', ['F-08', 'F-38', 'F-37'], null, null],
            ['Panel Switch', 'Panel Switch', ['F-48'], null, null],
            ['DTO com `spatie/laravel-data`', 'DTOs with `spatie/laravel-data`', ['F-111'], 'Laravel Data', 'Laravel Data'],
            ['Dock de atalhos', 'Shortcut dock', ['F-93'], 'dock', 'dock'],
            ['Tutorial guiado', 'Guided tutorial', ['F-94'], 'tutorial', 'tutorial'],
            ['Proteção anti-robô opcional', 'Optional anti-robot protection', ['F-74'], 'anti-robô', 'anti-robot'],
            ['Página única de login', 'Single login page', ['F-73'], 'página única', 'single login'],
            ['Login social por painel', 'Social login per panel', ['F-72'], 'login social', 'social login'],
            ['Spatie Health com checks de banco, cache, filas, agendador, disco', 'Spatie Health with checks for database, cache, queues, scheduler, disk', ['F-33'], null, null],
            ['Backup Monitor', 'Backup Monitor', ['F-34', 'F-35', 'F-36'], null, null],
            ['Exceções agrupadas', 'Grouped exceptions', ['F-57'], null, null],
            ['Trilha de e-mails enviados', 'Sent-mail trail', ['F-58'], null, null],
            ['Lixeira', 'Recycle bin', ['F-59'], null, null],
            ['Command Center', 'Command Center', ['F-39'], null, null],
            ['Laravel Pulse embutido como página do painel', 'Laravel Pulse embedded as a panel page', ['F-40'], null, null],
            ['Dependency Graph', 'Dependency Graph', ['F-41'], null, null],
            ['Release Notifier', 'Release Notifier', ['F-42'], null, null],
            ['`laravel/ai` com catálogo de agentes no banco', '`laravel/ai` with an agent catalog in the database', ['F-30'], null, null],
            ['Desligada por padrão', 'Off by default', ['F-84'], 'interruptor', 'switch'],
            ['Guardrails encadeados', 'Chained guardrails', ['F-54'], null, null],
            ['Ledger de execuções', 'Execution ledger', ['F-55'], null, null],
            ['Widget de chat com streaming', 'Chat widget with streaming', ['F-53'], null, null],
            ['Inferência 100% local via llama.cpp', '100% local inference through llama.cpp', ['F-56'], null, null],
            ['Busca ⌘K', '⌘K search', ['F-45'], null, null],
            ['Badges de contagem animados no menu, centro de notificações com abas, indicador de ambiente', 'Animated count badges in the menu, notification center with tabs, environment indicator', ['F-46', 'F-47', 'F-51'], null, null],
            ['Dashboards já preenchidos', 'Dashboards already filled in', ['F-32'], null, null],
            ['Páginas de erro brandadas', 'Branded error pages', ['F-52'], null, null],
            ['UI 100% em pt-BR, inclusive nos plugins que só trazem inglês', '100% pt-BR UI, including plugins that ship English only', ['F-99'], 'pt-BR', 'pt-BR'],
            ['Seletor de idioma', 'Language switcher', ['F-60'], null, null],
            ['Camada de mídia', 'Media layer', ['F-61'], null, null],
            ['Cabeçalho rico', 'Rich header', ['F-70'], null, null],
            ['Aviso de alterações não salvas', 'Unsaved-changes warning', ['F-82'], 'não salvas', 'unsaved'],
            ['Densidade do layout em três níveis', 'Layout density in three steps', ['F-83'], 'densidade', 'density'],
            ['Visões salvas de tabela', 'Saved table views', ['F-95'], 'visões', 'views'],
            ['Carregamento contínuo de tabela', 'Continuous table loading', ['F-96'], 'carregamento contínuo', 'continuous'],
            ['Link de acesso direto ao painel de cada organização', "Direct link to each organization's panel", ['F-87'], 'cada organização', 'each organization'],
        ];
    }
}

if (! function_exists('catalogoTabelaDoReadme')) {
    /**
     * A tabela da guarda num idioma: título do item → [F-nn que o cobrem, termo]. Os itens do README
     * da base vêm da tabela congelada; os itens novos do README publicado (hoje nenhum) entrariam
     * aqui com a F-nn e o termo.
     *
     * @return array<string, array{0: list<string>, 1: ?string}>
     */
    function catalogoTabelaDoReadme(string $idioma): array
    {
        $tabela = [];

        foreach (catalogoReadmeDaBase() as [$tituloPt, $tituloEn, $numeros, $termoPt, $termoEn]) {
            $tabela[$idioma === 'pt' ? $tituloPt : $tituloEn] = [$numeros, $idioma === 'pt' ? $termoPt : $termoEn];
        }

        return $tabela;
    }
}

if (! function_exists('catalogoPaginasSemFeature')) {
    /**
     * A lista da guarda: as páginas de `docs/{pt,en}/` que não descrevem feature, com o motivo.
     *
     * @return array<string, ?string>
     */
    function catalogoPaginasSemFeature(): array
    {
        return [
            'index.md'                               => 'página inicial do site: apresenta o kit e leva às seções, sem descrever uma feature',
            'autenticacao/index.md'                  => 'índice da seção de autenticação: só leva às páginas de cada feature',
            'comecar/index.md'                       => 'índice da seção Começar: leva à instalação e aos primeiros passos',
            'operacao/index.md'                      => 'índice da seção de operação: só leva às páginas da seção',
            'recursos/index.md'                      => 'índice da seção de recursos: só leva às páginas de cada feature',
            'referencia/index.md'                    => 'índice da seção de referência: só leva às páginas da seção',
            'operacao/roteiro-de-features.md'        => 'é o próprio catálogo de features',
            'operacao/desenvolvendo-o-kit.md'        => 'guia de quem desenvolve o próprio kit: nada nele chega ao projeto instalado',
            'referencia/arquitetura-em-diagramas.md' => 'referência: os diagramas Mermaid desenham a arquitetura das features já catalogadas',
            'referencia/estudo-advanced-tables.md'   => 'estudo de viabilidade de capacidades de tabela que o kit não entrega',
            'referencia/mapa-interativo.md'          => 'referência: o mapa desenha as peças que entregam as F-nn do catálogo, e não é feature do projeto instalado',
        ];
    }
}

if (! function_exists('catalogoTituloDoItem')) {
    /**
     * O título do item do README (ADV2-03): o negrito do começo; sem negrito, o texto antes do
     * primeiro ":", "—" ou "("; sem nenhum dos dois, o item inteiro.
     */
    function catalogoTituloDoItem(string $item): string
    {
        if (preg_match('/^\*\*(.+?)\*\*/u', $item, $negrito) === 1) {
            return trim($negrito[1]);
        }

        if (preg_match('/^(.*?)(:|—|\()/u', $item, $antes) === 1 && trim($antes[1]) !== '') {
            return trim($antes[1]);
        }

        return trim($item);
    }
}

if (! function_exists('catalogoItensDoReadme')) {
    /**
     * Os títulos dos itens da lista "O que já vem pronto" / "What's already there", até o
     * `### A densidade…` ou o próximo `## `.
     *
     * @return list<string>
     */
    function catalogoItensDoReadme(string $texto): array
    {
        $dentro  = false;
        $titulos = [];

        foreach (preg_split('/\R/u', $texto) as $linha) {
            if (preg_match('/^## (O que já vem pronto|What.s already there)/u', $linha) === 1) {
                $dentro = true;

                continue;
            }

            if (! $dentro) {
                continue;
            }

            if (str_starts_with($linha, '## ') || (str_starts_with($linha, '### ') && preg_match('/densidade|density/iu', $linha) === 1)) {
                break;
            }

            if (preg_match('/^\s*[-*] (.*)$/u', $linha, $item) === 1) {
                $titulos[] = catalogoTituloDoItem($item[1]);
            }
        }

        return $titulos;
    }
}

if (! function_exists('catalogoSemLinha')) {
    /**
     * @param  list<list<string>>  $linhas
     * @return list<list<string>>
     */
    function catalogoSemLinha(array $linhas, string $numero): array
    {
        return array_values(array_filter($linhas, fn (array $celulas): bool => $celulas[0] !== $numero));
    }
}

if (! function_exists('catalogoMudarLinha')) {
    /**
     * @param  list<list<string>>  $linhas
     * @param  callable(list<string>): list<string>  $mudar
     * @return list<list<string>>
     */
    function catalogoMudarLinha(array $linhas, string $numero, callable $mudar): array
    {
        return array_map(fn (array $celulas): array => $celulas[0] === $numero ? $mudar($celulas) : $celulas, $linhas);
    }
}

if (! function_exists('catalogoLinhaDe')) {
    /**
     * @param  list<list<string>>  $linhas
     * @return list<string>
     */
    function catalogoLinhaDe(array $linhas, string $numero): array
    {
        foreach ($linhas as $celulas) {
            if ($celulas[0] === $numero) {
                return $celulas;
            }
        }

        throw new RuntimeException("o catálogo publicado não tem a {$numero}, de que a cópia de controle precisa");
    }
}

if (! function_exists('catalogoTrocarConteudo')) {
    /**
     * Troca tudo, menos o número, entre duas linhas.
     *
     * @param  list<list<string>>  $linhas
     * @return list<list<string>>
     */
    function catalogoTrocarConteudo(array $linhas, string $um, string $outro): array
    {
        $deUm    = catalogoLinhaDe($linhas, $um);
        $deOutro = catalogoLinhaDe($linhas, $outro);

        return array_map(fn (array $celulas): array => match ($celulas[0]) {
            $um     => array_replace($deOutro, [0 => $um]),
            $outro  => array_replace($deUm, [0 => $outro]),
            default => $celulas,
        }, $linhas);
    }
}

if (! function_exists('catalogoMarcarRemovida')) {
    /**
     * @param  list<string>  $celulas
     * @return list<string>
     */
    function catalogoMarcarRemovida(array $celulas, string $idioma, ?string $motivo): array
    {
        $celulas[1] = '~~'.$celulas[1].'~~ ('.($idioma === 'pt' ? 'removida' : 'removed').($motivo === null ? '' : ': '.$motivo).')';

        return $celulas;
    }
}

if (! function_exists('catalogoConferirParidade')) {
    /**
     * Guarda da R1: as mesmas F-nn em pt e en, uma linha por F-nn, uma origem do glossário em cada,
     * e a mesma feature, no mesmo estado, em cada número.
     *
     * @param  array{pt: list<list<string>>, en: list<list<string>>}  $catalogo
     * @param  list<string>  $numerosDaBase
     * @return array{aceita: bool, motivos: list<string>, conferidas: array{pt: list<string>, en: list<string>}}
     */
    function catalogoConferirParidade(array $catalogo, array $numerosDaBase): array
    {
        $origens    = catalogoOrigensDoGlossario();
        $motivos    = [];
        $porNumero  = ['pt' => [], 'en' => []];
        $conferidas = ['pt' => [], 'en' => []];

        foreach (['pt', 'en'] as $idioma) {
            foreach ($catalogo[$idioma] as $celulas) {
                $numero                = $celulas[0];
                $conferidas[$idioma][] = $numero;

                if (isset($porNumero[$idioma][$numero])) {
                    $motivos[] = "{$numero} está em mais de uma linha do catálogo {$idioma}";

                    continue;
                }

                $porNumero[$idioma][$numero] = $celulas;
                $origem                      = catalogoOrigemDaLinha($celulas)['origem'];

                if ($origem === '') {
                    $motivos[] = "{$numero} não declara origem no catálogo {$idioma}";
                } elseif (! in_array($origem, $origens[$idioma], true)) {
                    $motivos[] = "{$numero} declara a origem \"{$origem}\", fora do glossário, no catálogo {$idioma}";
                }
            }
        }

        foreach (['pt' => 'en', 'en' => 'pt'] as $idioma => $outro) {
            foreach (array_keys($porNumero[$idioma]) as $numero) {
                if (! isset($porNumero[$outro][$numero])) {
                    $motivos[] = "{$numero} está no catálogo {$idioma} e falta no idioma {$outro}";
                }
            }
        }

        foreach ($porNumero['pt'] as $numero => $pt) {
            $en = $porNumero['en'][$numero] ?? null;

            if ($en === null) {
                continue;
            }

            $removidaPt = catalogoRemocao($pt)['removida'];
            $removidaEn = catalogoRemocao($en)['removida'];

            if ($removidaPt !== $removidaEn) {
                $motivos[] = $removidaPt
                    ? "{$numero} está removida no idioma pt e vigente no idioma en"
                    : "{$numero} está removida no idioma en e vigente no idioma pt";
            }

            $origemPt   = catalogoOrigemDaLinha($pt);
            $origemEn   = catalogoOrigemDaLinha($en);
            $diferencas = [];

            if (array_search($origemPt['origem'], $origens['pt'], true) !== array_search($origemEn['origem'], $origens['en'], true)) {
                $diferencas[] = 'a origem';
            }

            if ($origemPt['pacotes'] !== $origemEn['pacotes']) {
                $diferencas[] = 'o pacote ('.implode(', ', $origemPt['pacotes']).' × '.implode(', ', $origemEn['pacotes']).')';
            }

            if (catalogoPaginasDaLinha($pt) !== catalogoPaginasDaLinha($en)) {
                $diferencas[] = 'a página ('.implode(', ', catalogoPaginasDaLinha($pt)).' × '.implode(', ', catalogoPaginasDaLinha($en)).')';
            }

            if (catalogoRotas($pt) !== catalogoRotas($en)) {
                $diferencas[] = 'a rota do "onde fica" ('.implode(', ', catalogoRotas($pt)).' × '.implode(', ', catalogoRotas($en)).')';
            }

            if (! in_array($numero, $numerosDaBase, true) && catalogoTokens(catalogoTextoDaLinha($pt)) !== catalogoTokens(catalogoTextoDaLinha($en))) {
                $diferencas[] = 'os tokens entre crases ('.implode(', ', catalogoTokens(catalogoTextoDaLinha($pt))).' × '.implode(', ', catalogoTokens(catalogoTextoDaLinha($en))).')';
            }

            if ($diferencas !== []) {
                $motivos[] = "{$numero} não é a mesma feature em pt e em en: difere ".implode('; ', $diferencas);
            }
        }

        return ['aceita' => $motivos === [], 'motivos' => $motivos, 'conferidas' => $conferidas];
    }
}

if (! function_exists('catalogoConferirContraBase')) {
    /**
     * Guarda da R3: o número da base fica com a sua feature (o termo congelado), ou fica marcado como
     * removido com motivo, e o removido não volta.
     *
     * @param  array<string, array{termo: string, removida: bool}>  $base
     * @param  list<list<string>>  $linhas
     * @return array{aceita: bool, motivos: list<string>}
     */
    function catalogoConferirContraBase(array $base, array $linhas): array
    {
        $porNumero = [];

        foreach ($linhas as $celulas) {
            $porNumero[$celulas[0]] ??= $celulas;
        }

        $motivos = [];

        foreach ($base as $numero => $daBase) {
            $celulas = $porNumero[$numero] ?? null;

            if ($celulas === null) {
                $motivos[] = "{$numero} sumiu do catálogo sem registro de remoção";

                continue;
            }

            $remocao = catalogoRemocao($celulas);

            if ($remocao['removida']) {
                if (motivoDeEnfeite($remocao['motivo'])) {
                    $motivos[] = "{$numero} está marcada como removida sem motivo, ou com motivo de enfeite";
                }

                continue;
            }

            if ($daBase['removida']) {
                $motivos[] = "{$numero} está removida na base e voltou a descrever uma feature";

                continue;
            }

            if (! str_contains(catalogoNormalizar($celulas[1]), catalogoNormalizar($daBase['termo']))) {
                $motivos[] = "{$numero} deixou de ter o termo \"{$daBase['termo']}\" da sua feature";
            }
        }

        return ['aceita' => $motivos === [], 'motivos' => $motivos];
    }
}

if (! function_exists('catalogoConferirNumerosNovos')) {
    /**
     * Guarda da R4: os números que a base não tem começam no maior inteiro da base + 1, seguem em
     * sequência e não têm sufixo de letra.
     *
     * @param  list<string>  $numerosDaBase
     * @param  list<list<string>>  $linhas
     * @return array{aceita: bool, motivos: list<string>, maiorDaBase: int, novos: list<string>}
     */
    function catalogoConferirNumerosNovos(array $numerosDaBase, array $linhas): array
    {
        $maiorDaBase = max(array_map(fn (string $numero): int => (int) substr($numero, 2), $numerosDaBase));
        $novos       = array_values(array_unique(array_diff(array_column($linhas, 0), $numerosDaBase)));
        $motivos     = [];
        $inteiros    = [];

        foreach ($novos as $numero) {
            if (preg_match('/^F-(\d+)$/', $numero, $partes) !== 1) {
                $motivos[] = "{$numero} é número novo com sufixo de letra";

                continue;
            }

            $inteiros[] = (int) $partes[1];
        }

        sort($inteiros);
        $esperado = $maiorDaBase + 1;

        foreach ($inteiros as $inteiro) {
            if ($inteiro !== $esperado) {
                $motivos[] = sprintf('F-%02d é número novo fora da sequência: pula a F-%02d', $inteiro, $esperado);

                break;
            }

            $esperado++;
        }

        return ['aceita' => $motivos === [], 'motivos' => $motivos, 'maiorDaBase' => $maiorDaBase, 'novos' => $novos];
    }
}

if (! function_exists('catalogoFontesPublicadas')) {
    /**
     * As fontes da R2: os dois READMEs, as páginas de `docs/` fora do catálogo (a do mapa marcada) e
     * os arquivos de `site/mapa/`.
     *
     * @return list<array{nome: string, idioma: string, tipo: string, texto: string}>
     */
    function catalogoFontesPublicadas(): array
    {
        $fontes = [
            ['nome' => 'README.md', 'idioma' => 'pt', 'tipo' => 'pagina', 'texto' => (string) file_get_contents(base_path('README.md'))],
            ['nome' => 'README.en.md', 'idioma' => 'en', 'tipo' => 'pagina', 'texto' => (string) file_get_contents(base_path('README.en.md'))],
        ];

        foreach (['pt', 'en'] as $idioma) {
            foreach (paginasDoSite($idioma) as $relativo => $texto) {
                if ($relativo === 'operacao/roteiro-de-features.md') {
                    continue;
                }

                $fontes[] = [
                    'nome'   => "docs/{$idioma}/{$relativo}",
                    'idioma' => $idioma,
                    'tipo'   => $relativo === 'referencia/mapa-interativo.md' ? 'pagina-do-mapa' : 'pagina',
                    'texto'  => $texto,
                ];
            }
        }

        $arquivos = array_merge(glob(base_path('site/mapa/*.*')) ?: [], glob(base_path('site/mapa/diagramas/*.json')) ?: []);
        sort($arquivos);

        foreach ($arquivos as $arquivo) {
            $fontes[] = [
                'nome'   => str_replace('\\', '/', substr($arquivo, strlen(base_path()) + 1)),
                'idioma' => 'ambos',
                'tipo'   => 'mapa',
                'texto'  => (string) file_get_contents($arquivo),
            ];
        }

        return $fontes;
    }
}

if (! function_exists('catalogoGruposDoJson')) {
    /**
     * Cada objeto ou lista do JSON vira um grupo: os textos que são filhos diretos dele, juntos.
     *
     * @return list<string>
     */
    function catalogoGruposDoJson(mixed $dados): array
    {
        if (! is_array($dados)) {
            return [];
        }

        $diretos = array_filter($dados, fn (mixed $valor): bool => is_string($valor));
        $grupos  = $diretos === [] ? [] : [implode(' · ', $diretos)];

        foreach ($dados as $valor) {
            if (is_array($valor)) {
                array_push($grupos, ...catalogoGruposDoJson($valor));
            }
        }

        return $grupos;
    }
}

if (! function_exists('catalogoEhNomeDeFeature')) {
    /**
     * O texto é o nome que o catálogo dá a uma F-nn (o texto da coluna "Feature", sem a marcação),
     * com ou sem o número.
     *
     * @param  array<string, string>  $nomes  F-nn → nome normalizado
     */
    function catalogoEhNomeDeFeature(string $texto, array $nomes): bool
    {
        $texto = catalogoNormalizar(strip_tags($texto));
        $texto = trim((string) preg_replace('/^f-\d+[a-z]?\s*[:—–·-]?\s*/u', '', $texto), ' .;,:');

        return $texto !== '' && in_array($texto, $nomes, true);
    }
}

if (! function_exists('catalogoBlocosDaPagina')) {
    /**
     * As listas e as tabelas de uma página Markdown: cada bloco é a lista dos seus itens, e cada item
     * é a lista dos textos que ele mostra (o item de lista, ou as células da linha de tabela).
     *
     * @return list<list<list<string>>>
     */
    function catalogoBlocosDaPagina(string $texto): array
    {
        $blocos    = [];
        $atual     = [];
        $tipoAtual = null;

        foreach (preg_split('/\R/u', $texto) as $linha) {
            if (trim($linha) === '' || ($tipoAtual === 'lista' && preg_match('/^\s{2,}\S/u', $linha) === 1 && preg_match('/^\s*(?:[-*+]|\d+\.)\s/u', $linha) !== 1)) {
                continue;
            }

            $tipo = null;
            $item = [];

            if (preg_match('/^\s*\|(.*)\|\s*$/u', $linha, $tabela) === 1) {
                $tipo = 'tabela';
                $item = preg_match('/^[\s:|-]+$/u', $tabela[1]) === 1 ? [] : array_map('trim', explode('|', $tabela[1]));
            } elseif (preg_match('/^\s*(?:[-*+]|\d+\.)\s+(.*)$/u', $linha, $lista) === 1) {
                $tipo = 'lista';
                $item = [$lista[1]];
            }

            if ($tipo !== $tipoAtual && $atual !== []) {
                $blocos[] = $atual;
                $atual    = [];
            }

            $tipoAtual = $tipo;

            if ($item !== []) {
                $atual[] = $item;
            }
        }

        if ($atual !== []) {
            $blocos[] = $atual;
        }

        return $blocos;
    }
}

if (! function_exists('catalogoConferirCitacoes')) {
    /**
     * Guarda da R2: README, páginas de `docs/` fora do catálogo e arquivos do mapa só citam F-nn que
     * o catálogo do mesmo idioma tem, e nenhum deles traz lista ou tabela de features.
     *
     * @param  list<array{nome: string, idioma: string, tipo: string, texto: string}>  $fontes
     * @param  array{pt: list<list<string>>, en: list<list<string>>}  $catalogo
     * @return array{aceita: bool, motivos: list<string>, conferidas: list<string>}
     */
    function catalogoConferirCitacoes(array $fontes, array $catalogo): array
    {
        $regex   = '/(?<![\p{L}\p{N}])F-\d+[a-z]?(?![\p{L}\p{N}])/u';
        $numeros = ['pt' => [], 'en' => []];
        $nomes   = ['pt' => [], 'en' => []];

        foreach (['pt', 'en'] as $idioma) {
            foreach ($catalogo[$idioma] as $celulas) {
                $numeros[$idioma][$celulas[0]] = true;
                $nomes[$idioma][$celulas[0]]   = catalogoNormalizar($celulas[1]);
            }
        }

        $motivos    = [];
        $conferidas = [];

        foreach ($fontes as $fonte) {
            $idiomas = $fonte['idioma'] === 'ambos' ? ['pt', 'en'] : [$fonte['idioma']];
            preg_match_all($regex, $fonte['texto'], $citadas);

            foreach (array_unique($citadas[0]) as $numero) {
                $conferidas[$numero] = true;

                foreach ($idiomas as $idioma) {
                    if (! isset($numeros[$idioma][$numero])) {
                        $motivos[] = "{$fonte['nome']} cita a {$numero}, que o catálogo {$idioma} não tem";
                    }
                }
            }

            if ($fonte['tipo'] !== 'mapa') {
                foreach (preg_split('/\R/u', $fonte['texto']) as $linha) {
                    if (preg_match('/^\s*(\||[-*+]\s|\d+\.\s)/u', $linha) === 1 && preg_match($regex, $linha) === 1) {
                        $motivos[] = "{$fonte['nome']} traz linha de tabela ou item de lista que cita F-nn: ".trim($linha);
                    }
                }
            }

            if ($fonte['tipo'] === 'pagina-do-mapa') {
                foreach (catalogoBlocosDaPagina($fonte['texto']) as $bloco) {
                    $quantos = 0;

                    foreach ($bloco as $textos) {
                        $levaADiagrama = preg_match('/(href="[^"]*mapa|\]\([^)]*mapa)/u', implode(' ', $textos)) === 1;
                        $ehNome        = array_filter($textos, fn (string $texto): bool => catalogoEhNomeDeFeature($texto, $nomes[$fonte['idioma']])) !== [];

                        if (! $levaADiagrama && $ehNome) {
                            $quantos++;
                        }
                    }

                    if ($quantos >= 3) {
                        $motivos[] = "{$fonte['nome']} traz lista ou tabela com {$quantos} itens que são nomes de F-nn do catálogo";
                    }
                }
            }

            if ($fonte['tipo'] !== 'mapa') {
                continue;
            }

            $dados  = str_ends_with($fonte['nome'], '.json') ? json_decode($fonte['texto'], true) : null;
            $grupos = is_array($dados) ? catalogoGruposDoJson($dados) : preg_split('/\R/u', $fonte['texto']);

            foreach ($grupos as $grupo) {
                preg_match_all($regex, $grupo, $noGrupo);

                foreach (array_unique($noGrupo[0]) as $numero) {
                    foreach (['pt', 'en'] as $idioma) {
                        $nome = $nomes[$idioma][$numero] ?? '';

                        if ($nome !== '' && catalogoTemPalavra(catalogoNormalizar($grupo), $nome)) {
                            $motivos[] = "{$fonte['nome']} traz a {$numero} junto do nome que o catálogo dá a ela";
                        }
                    }
                }
            }

            if ($fonte['nome'] === 'site/mapa/pecas.json' && is_array($dados)) {
                foreach ($dados as $peca) {
                    foreach ((array) ($peca['features'] ?? []) as $feature) {
                        if (! is_string($feature) || preg_match('/^F-\d+[a-z]?$/', $feature) !== 1) {
                            $motivos[] = "a peça {$peca['id']} cita a feature pelo nome (\"".(is_string($feature) ? $feature : json_encode($feature)).'"), sem a F-nn';
                        }
                    }
                }
            }

            if (is_array($dados)) {
                foreach ((array) ($dados['cards'] ?? []) as $card) {
                    $quantos = 0;

                    foreach ((array) ($card['items'] ?? []) as $item) {
                        foreach (['pt', 'en'] as $idioma) {
                            $texto = is_array($item) ? (string) ($item[$idioma] ?? '') : (string) $item;

                            if (catalogoEhNomeDeFeature($texto, $nomes[$idioma])) {
                                $quantos++;

                                break;
                            }
                        }
                    }

                    if ($quantos >= 3) {
                        $motivos[] = "{$fonte['nome']} traz um card com {$quantos} itens que são nomes de F-nn do catálogo";
                    }
                }
            }
        }

        return ['aceita' => $motivos === [], 'motivos' => array_values(array_unique($motivos)), 'conferidas' => array_keys($conferidas)];
    }
}

if (! function_exists('catalogoConferirFonte')) {
    /**
     * Guarda das fontes da R5: cada item do README casa, pela tabela da guarda, com F-nn que existe
     * no catálogo do mesmo idioma (e, quando a tabela dá o termo, a linha casada o traz como palavra
     * inteira); cada página é levada por link de uma linha do catálogo, ou está na lista das que não
     * descrevem feature, com motivo — e nenhuma página de `recursos/` ou `autenticacao/`, fora os
     * índices, está nessa lista.
     *
     * @param  'readme'|'paginas'  $tipo
     * @param  list<string>  $itens
     * @param  list<list<string>>  $linhas
     * @param  array<string, array{0: list<string>, 1: ?string}>  $tabela
     * @param  array<string, ?string>  $lista
     * @return array{aceita: bool, motivos: list<string>, casados: array<string, list<string>>, conferidos: int}
     */
    function catalogoConferirFonte(string $tipo, array $itens, array $linhas, array $tabela, array $lista): array
    {
        $porNumero = [];

        foreach ($linhas as $celulas) {
            $porNumero[$celulas[0]] ??= $celulas;
        }

        $motivos    = [];
        $casados    = [];
        $conferidos = 0;

        foreach ($itens as $item) {
            $conferidos++;

            if ($tipo === 'readme') {
                if (! isset($tabela[$item])) {
                    $motivos[] = "o item \"{$item}\" do README não tem F-nn na tabela da guarda";

                    continue;
                }

                [$numerosDoItem, $termo] = $tabela[$item];

                foreach ($numerosDoItem as $numero) {
                    if (! isset($porNumero[$numero])) {
                        $motivos[] = "o item \"{$item}\" casa com a {$numero}, que o catálogo não tem";

                        continue;
                    }

                    if ($termo !== null && ! catalogoTemPalavra(catalogoTextoDaLinha($porNumero[$numero]), $termo)) {
                        $motivos[] = "o item \"{$item}\" casa com a {$numero}, cuja linha não traz \"{$termo}\" como palavra inteira";

                        continue;
                    }

                    $casados[$item][] = $numero;
                }

                continue;
            }

            $semExtensao     = $item === 'index.md' ? '' : (string) preg_replace('#(/index)?\.md$#', '', $item);
            $numerosQueLevam = [];

            foreach ($porNumero as $numero => $celulas) {
                if (in_array($semExtensao, catalogoPaginasDaLinha($celulas), true)) {
                    $numerosQueLevam[] = (string) $numero;
                }
            }

            if (array_key_exists($item, $lista)) {
                if (preg_match('#^(recursos|autenticacao)/#', $item) === 1 && ! str_ends_with($item, '/index.md')) {
                    $motivos[] = "a página {$item} descreve feature e está na lista das que não descrevem";
                } elseif (motivoDeEnfeite($lista[$item])) {
                    $motivos[] = "a página {$item} está na lista das que não descrevem feature sem motivo, ou com motivo de enfeite";
                } else {
                    $casados[$item] = ['lista'];
                }

                continue;
            }

            if ($numerosQueLevam === []) {
                $motivos[] = "a página {$item} não é levada por link de nenhuma F-nn do catálogo e não está na lista das que não descrevem feature";

                continue;
            }

            $casados[$item] = $numerosQueLevam;
        }

        return ['aceita' => $motivos === [], 'motivos' => $motivos, 'casados' => $casados, 'conferidos' => $conferidos];
    }
}

if (! function_exists('catalogoNomeia')) {
    /**
     * Confere que, para cada grupo de nomes, algum motivo da guarda nomeia todos eles juntos.
     *
     * @param  list<string>  $motivos
     * @param  list<list<string>>  $exigidos
     */
    function catalogoNomeia(array $motivos, array $exigidos): void
    {
        foreach ($exigidos as $juntos) {
            $achados = array_filter($motivos, fn (string $motivo): bool => array_reduce(
                $juntos,
                fn (bool $todos, string $nome): bool => $todos && str_contains($motivo, $nome),
                true,
            ));

            expect($achados)->not->toBeEmpty('nenhum motivo nomeia '.implode(' + ', $juntos).'. Motivos: '.implode(' | ', $motivos));
        }
    }
}

if (! function_exists('catalogoMundoDoCt01')) {
    /**
     * @return array{pt: list<list<string>>, en: list<list<string>>}
     */
    function catalogoMundoDoCt01(string $mundo): array
    {
        $pt         = linhasDoCatalogo('pt');
        $en         = linhasDoCatalogo('en');
        $trocarLink = fn (string $alvo): Closure => function (array $celulas) use ($alvo): array {
            $celulas[1] = preg_match('/\]\([^)]*\)/u', $celulas[1]) === 1
                ? (string) preg_replace('/\]\([^)]*\)/u', ']('.$alvo.')', $celulas[1])
                : '['.$celulas[1].']('.$alvo.')';

            return $celulas;
        };

        return match ($mundo) {
            'publicado'                                => ['pt' => $pt, 'en' => $en],
            'en sem a F-69'                            => ['pt' => $pt, 'en' => catalogoSemLinha($en, 'F-69')],
            'F-72 do pt no dock e a do en no tutorial' => [
                'pt' => catalogoMudarLinha($pt, 'F-72', $trocarLink('../../recursos/dock-de-atalhos/')),
                'en' => catalogoMudarLinha($en, 'F-72', $trocarLink('../../recursos/tutorial-guiado/')),
            ],
            'F-30 com a origem biblioteca'                   => ['pt' => catalogoMudarLinha($pt, 'F-30', fn (array $celulas): array => array_replace($celulas, [2 => 'biblioteca: `laravel/ai`'])), 'en' => $en],
            'F-30 sem origem'                                => ['pt' => catalogoMudarLinha($pt, 'F-30', fn (array $celulas): array => array_replace($celulas, [2 => ''])), 'en' => $en],
            'F-30 em duas linhas do pt'                      => ['pt' => [...$pt, catalogoLinhaDe($pt, 'F-30')], 'en' => $en],
            'F-118 e F-121 com as descrições trocadas no en' => ['pt' => $pt, 'en' => catalogoTrocarConteudo($en, 'F-118', 'F-121')],
            'F-05 removida no pt e vigente no en'            => [
                'pt' => catalogoMudarLinha($pt, 'F-05', fn (array $celulas): array => catalogoMarcarRemovida($celulas, 'pt', 'o kit deixou de oferecer passkeys, que nenhum painel ligava')),
                'en' => $en,
            ],
        };
    }
}

it('[CT-01] o catálogo tem as mesmas F-nn nos dois idiomas, a mesma feature em cada número, uma linha por F-nn e uma origem em cada', function (string $mundo, string $resultado, array $nomeados): void {
    $catalogo      = catalogoMundoDoCt01($mundo);
    $numerosDaBase = array_keys(catalogoBase()['pt']);
    $veredito      = catalogoConferirParidade($catalogo, $numerosDaBase);

    expect($veredito['aceita'])->toBe($resultado === 'aceita', 'Motivos: '.implode(' | ', $veredito['motivos']));
    catalogoNomeia($veredito['motivos'], $nomeados);

    foreach (['pt', 'en'] as $idioma) {
        $ausentesDoMundo = array_values(array_diff($numerosDaBase, array_column($catalogo[$idioma], 0)));

        if ($mundo === 'publicado') {
            expect($ausentesDoMundo)->toBe([], "o catálogo {$idioma} publicado não tem todas as 74 F-nn da base");
        }

        expect(array_values(array_diff($numerosDaBase, $veredito['conferidas'][$idioma])))
            ->toBe($ausentesDoMundo, "a guarda não conferiu, no {$idioma}, todas as F-nn da base");
    }
})->with([
    'publicado'                             => ['publicado', 'aceita', []],
    'en sem a F-69'                         => ['en sem a F-69', 'reprova', [['F-69', 'idioma en']]],
    'F-72 com outra feature em cada idioma' => ['F-72 do pt no dock e a do en no tutorial', 'reprova', [['F-72']]],
    'origem biblioteca'                     => ['F-30 com a origem biblioteca', 'reprova', [['F-30', 'biblioteca']]],
    'origem ausente'                        => ['F-30 sem origem', 'reprova', [['F-30', 'não declara origem']]],
    'número repetido'                       => ['F-30 em duas linhas do pt', 'reprova', [['F-30', 'mais de uma linha']]],
    'número novo com outra feature'         => ['F-118 e F-121 com as descrições trocadas no en', 'reprova', [['F-118'], ['F-121']]],
    'estado diferente entre os idiomas'     => ['F-05 removida no pt e vigente no en', 'reprova', [['F-05', 'vigente no idioma en']]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site não viaja no pacote: a guarda do catálogo só roda na árvore do kit');

if (! function_exists('catalogoMundoDoCt02')) {
    /**
     * @return array{fontes: list<array{nome: string, idioma: string, tipo: string, texto: string}>, catalogo: array{pt: list<list<string>>, en: list<list<string>>}, nomeados: list<list<string>>}
     */
    function catalogoMundoDoCt02(string $mundo): array
    {
        $fontes     = catalogoFontesPublicadas();
        $catalogo   = ['pt' => linhasDoCatalogo('pt'), 'en' => linhasDoCatalogo('en')];
        $mudarFonte = fn (string $nome, Closure $mudar): array => array_map(
            fn (array $fonte): array => $fonte['nome'] === $nome ? array_replace($fonte, ['texto' => $mudar($fonte['texto'])]) : $fonte,
            $fontes,
        );
        $mudarJson = fn (string $nome, Closure $mudar): array => $mudarFonte(
            $nome,
            fn (string $texto): string => (string) json_encode($mudar(json_decode($texto, true)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        $paginaDoMapa = 'docs/pt/referencia/mapa-interativo.md';

        if ($mundo === 'publicados') {
            return ['fontes' => $fontes, 'catalogo' => $catalogo, 'nomeados' => []];
        }

        if ($mundo === 'página de docs/pt/ que cita a F-199') {
            expect(array_column($catalogo['pt'], 0))->not->toContain('F-199');

            return [
                'fontes'   => $mudarFonte('docs/pt/autenticacao/login-social.md', fn (string $texto): string => $texto."\n\nVeja também a F-199.\n"),
                'catalogo' => $catalogo,
                'nomeados' => [['docs/pt/autenticacao/login-social.md', 'F-199']],
            ];
        }

        if ($mundo === 'peça que cita a feature pelo nome') {
            $pecas  = modeloDoMapa()['pecas'];
            $indice = array_key_first(array_filter($pecas, fn (array $peca): bool => ($peca['features'] ?? []) !== []));
            $nome   = catalogoNormalizar(catalogoLinhaDe($catalogo['pt'], $pecas[$indice]['features'][0])[1]);

            return [
                'fontes' => $mudarJson('site/mapa/pecas.json', function (array $dados) use ($indice, $nome): array {
                    $dados[$indice]['features'][0] = $nome;

                    return $dados;
                }),
                'catalogo' => $catalogo,
                'nomeados' => [[$pecas[$indice]['id']]],
            ];
        }

        if ($mundo === 'README.en.md que cita uma F-nn que só o catálogo pt tem') {
            $catalogo['pt'][] = array_replace(catalogoLinhaDe($catalogo['pt'], 'F-121'), [0 => 'F-122']);

            return [
                'fontes'   => $mudarFonte('README.en.md', fn (string $texto): string => $texto."\n\nSee F-122.\n"),
                'catalogo' => $catalogo,
                'nomeados' => [['README.en.md', 'F-122']],
            ];
        }

        if ($mundo === 'página do mapa com uma tabela de três F-nn') {
            return [
                'fontes'   => $mudarFonte($paginaDoMapa, fn (string $texto): string => $texto."\n\n| F-nn | Feature |\n|---|---|\n| F-05 | Passkeys |\n| F-08 | Impersonate |\n| F-59 | Lixeira |\n"),
                'catalogo' => $catalogo,
                'nomeados' => [[$paginaDoMapa]],
            ];
        }

        if ($mundo === 'arquivo do mapa com uma lista de features, com a F-nn e o nome') {
            return [
                'fontes' => $mudarJson('site/mapa/interface.json', fn (array $dados): array => $dados + ['features' => [
                    ['fnn' => 'F-05', 'nome' => 'Passkeys'],
                    ['fnn' => 'F-08', 'nome' => 'Impersonate'],
                    ['fnn' => 'F-59', 'nome' => 'Lixeira'],
                ]]),
                'catalogo' => $catalogo,
                'nomeados' => [['site/mapa/interface.json']],
            ];
        }

        if ($mundo === 'página do mapa com uma lista só de nomes') {
            return [
                'fontes'   => $mudarFonte($paginaDoMapa, fn (string $texto): string => $texto."\n\n- Passkeys\n- Impersonate\n- Lixeira\n"),
                'catalogo' => $catalogo,
                'nomeados' => [[$paginaDoMapa]],
            ];
        }

        return [
            'fontes' => $mudarJson('site/mapa/diagramas/admin-convite.json', function (array $dados): array {
                $dados['cards'][] = ['dot' => 'amber', 'title' => ['pt' => 'Features', 'en' => 'Features'], 'items' => [
                    ['pt' => 'Passkeys', 'en' => 'Passkeys'],
                    ['pt' => 'Impersonate', 'en' => 'Impersonate'],
                    ['pt' => 'Lixeira', 'en' => 'Recycle bin'],
                ]];

                return $dados;
            }),
            'catalogo' => $catalogo,
            'nomeados' => [['site/mapa/diagramas/admin-convite.json']],
        ];
    }
}

it('[CT-02] o README, as páginas do site e o mapa só citam F-nn que o catálogo tem, e nenhum deles traz lista ou tabela de features', function (string $mundo, string $resultado): void {
    $arranjo  = catalogoMundoDoCt02($mundo);
    $veredito = catalogoConferirCitacoes($arranjo['fontes'], $arranjo['catalogo']);

    expect($veredito['aceita'])->toBe($resultado === 'aceita', 'Motivos: '.implode(' | ', $veredito['motivos']));
    catalogoNomeia($veredito['motivos'], $arranjo['nomeados']);

    if ($mundo === 'publicados') {
        $doMapa = [];

        foreach (modeloDoMapa()['pecas'] as $peca) {
            array_push($doMapa, ...($peca['features'] ?? []));
        }

        expect($doMapa)->not->toBeEmpty('o modelo não aponta nenhuma F-nn');

        foreach (['F-03', 'F-04', ...array_unique($doMapa)] as $numero) {
            expect($veredito['conferidas'])->toContain($numero);
        }
    }
})->with([
    'publicados'                                         => ['publicados', 'aceita'],
    'página de docs/pt/ com F-nn que o catálogo não tem' => ['página de docs/pt/ que cita a F-199', 'reprova'],
    'peça com a feature pelo nome'                       => ['peça que cita a feature pelo nome', 'reprova'],
    'README.en.md com F-nn só do pt'                     => ['README.en.md que cita uma F-nn que só o catálogo pt tem', 'reprova'],
    'tabela de três F-nn na página do mapa'              => ['página do mapa com uma tabela de três F-nn', 'reprova'],
    'lista de features num arquivo do mapa'              => ['arquivo do mapa com uma lista de features, com a F-nn e o nome', 'reprova'],
    'lista só de nomes na página do mapa'                => ['página do mapa com uma lista só de nomes', 'reprova'],
    'card de três nomes num arquivo do mapa'             => ['arquivo do mapa com um card de três itens que são nomes de F-nn', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site, o README e site/ não viajam no pacote: a guarda só roda na árvore do kit');

if (! function_exists('catalogoMundoDoCt03')) {
    /**
     * @return array{base: array<string, array{termo: string, removida: bool}>, idiomas: array<string, list<list<string>>>}
     */
    function catalogoMundoDoCt03(string $base, string $mundo): array
    {
        $comF05Removida = $base === 'd9608c5a com a F-05 removida';
        $baseDo         = fn (string $idioma): array => array_map(
            fn (string $termo): array => ['termo' => $termo, 'removida' => false],
            catalogoBase()[$idioma],
        );
        $pt         = linhasDoCatalogo('pt');
        $removerF05 = fn (?string $motivo): array => catalogoMudarLinha($pt, 'F-05', fn (array $celulas): array => catalogoMarcarRemovida($celulas, 'pt', $motivo));
        $motivoReal = 'o kit deixou de oferecer passkeys, que nenhum painel ligava';

        $idiomas = match ($mundo) {
            'publicado, pt e en'                            => ['pt' => $pt, 'en' => linhasDoCatalogo('en')],
            'cópia com a F-10 e a F-11 trocadas de feature' => ['pt' => catalogoTrocarConteudo($pt, 'F-10', 'F-11')],
            'cópia sem a linha da F-05'                     => ['pt' => catalogoSemLinha($pt, 'F-05')],
            'cópia com a F-05 marcada como removida, com o motivo',
            'cópia que mantém a F-05 marcada como removida, com o motivo'                      => ['pt' => $removerF05($motivoReal)],
            'cópia com a F-05 marcada como removida, sem o motivo'                             => ['pt' => $removerF05(null)],
            'cópia com a F-05 marcada como removida, com o motivo "—"'                         => ['pt' => $removerF05('—')],
            'cópia com a F-05 marcada como removida, com o motivo "não se aplica", sem porquê' => ['pt' => $removerF05('não se aplica')],
            'cópia em que a F-05 descreve outra feature'                                       => ['pt' => catalogoMudarLinha($pt, 'F-05', fn (array $celulas): array => array_replace(catalogoLinhaDe($pt, 'F-118'), [0 => 'F-05']))],
        };

        $bases = [];

        foreach (array_keys($idiomas) as $idioma) {
            $bases[$idioma] = $baseDo($idioma);

            if ($comF05Removida) {
                $bases[$idioma]['F-05']['removida'] = true;
            }
        }

        return ['base' => $bases, 'idiomas' => $idiomas];
    }
}

it('[CT-03] o número da base fica com a sua feature, ou fica marcado como removido com o motivo, e não volta', function (string $base, string $mundo, string $resultado, array $nomeados): void {
    $arranjo = catalogoMundoDoCt03($base, $mundo);
    $motivos = [];

    foreach ($arranjo['idiomas'] as $idioma => $linhas) {
        expect($arranjo['base'][$idioma])->toHaveCount(74);

        foreach (catalogoConferirContraBase($arranjo['base'][$idioma], $linhas)['motivos'] as $motivo) {
            $motivos[] = "[{$idioma}] {$motivo}";
        }
    }

    expect($motivos === [])->toBe($resultado === 'aceita', 'Motivos: '.implode(' | ', $motivos));
    catalogoNomeia($motivos, $nomeados);
})->with([
    'da base × manter'                               => ['d9608c5a', 'publicado, pt e en', 'aceita', []],
    'da base × dar o número a outra feature'         => ['d9608c5a', 'cópia com a F-10 e a F-11 trocadas de feature', 'reprova', [['F-10'], ['F-11']]],
    'da base × remover sem registro (sem a linha)'   => ['d9608c5a', 'cópia sem a linha da F-05', 'reprova', [['F-05']]],
    'da base × remover com registro'                 => ['d9608c5a', 'cópia com a F-05 marcada como removida, com o motivo', 'aceita', []],
    'da base × remover sem registro (sem motivo)'    => ['d9608c5a', 'cópia com a F-05 marcada como removida, sem o motivo', 'reprova', [['F-05']]],
    'da base × remover sem registro (motivo —)'      => ['d9608c5a', 'cópia com a F-05 marcada como removida, com o motivo "—"', 'reprova', [['F-05']]],
    'da base × remover sem registro (não se aplica)' => ['d9608c5a', 'cópia com a F-05 marcada como removida, com o motivo "não se aplica", sem porquê', 'reprova', [['F-05']]],
    'removida × manter'                              => ['d9608c5a com a F-05 removida', 'cópia que mantém a F-05 marcada como removida, com o motivo', 'aceita', []],
    'removida × dar o número a outra feature'        => ['d9608c5a com a F-05 removida', 'cópia em que a F-05 descreve outra feature', 'reprova', [['F-05']]],
    'removida × remover sem registro'                => ['d9608c5a com a F-05 removida', 'cópia sem a linha da F-05', 'reprova', [['F-05']]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site não viaja no pacote: a guarda do catálogo só roda na árvore do kit');

it('[CT-04] os números que a base não tem começam na F-72, seguem em sequência e não têm sufixo', function (string $mundo, string $resultado, array $nomeados): void {
    $numerosDaBase = array_keys(catalogoBase()['pt']);
    $mundos        = [];

    foreach (['pt', 'en'] as $idioma) {
        $publicado = linhasDoCatalogo($idioma);
        $daBase    = array_values(array_filter($publicado, fn (array $celulas): bool => in_array($celulas[0], $numerosDaBase, true)));
        $novaComo  = fn (string $numero, string $de): array => array_replace(catalogoLinhaDe($publicado, $de), [0 => $numero]);

        $mundos[$idioma] = match ($mundo) {
            'publicado, pt e en'                      => $publicado,
            'cópia com um único número novo, a F-72'  => [...$daBase, $novaComo('F-72', 'F-72')],
            'cópia com um único número novo, a F-73'  => [...$daBase, $novaComo('F-73', 'F-72')],
            'cópia com os números novos F-72 e F-74'  => [...$daBase, $novaComo('F-72', 'F-72'), $novaComo('F-74', 'F-74')],
            'cópia com um único número novo, a F-03d' => [...$daBase, $novaComo('F-03d', 'F-76')],
        };

        if ($mundo !== 'publicado, pt e en') {
            break;
        }
    }

    $motivos = [];

    foreach ($mundos as $idioma => $linhas) {
        $veredito = catalogoConferirNumerosNovos($numerosDaBase, $linhas);

        expect($veredito['maiorDaBase'])->toBe(71);

        if ($mundo === 'publicado, pt e en') {
            expect($veredito['novos'])->not->toBeEmpty("o catálogo {$idioma} publicado não acrescentou F-nn à base");
        }

        foreach ($veredito['motivos'] as $motivo) {
            $motivos[] = "[{$idioma}] {$motivo}";
        }
    }

    expect($motivos === [])->toBe($resultado === 'aceita', 'Motivos: '.implode(' | ', $motivos));
    catalogoNomeia($motivos, $nomeados);
})->with([
    'sequência real' => ['publicado, pt e en', 'aceita', []],
    'borda'          => ['cópia com um único número novo, a F-72', 'aceita', []],
    'borda+1'        => ['cópia com um único número novo, a F-73', 'reprova', [['F-73', 'pula a F-72']]],
    'buraco'         => ['cópia com os números novos F-72 e F-74', 'reprova', [['F-74', 'pula a F-73']]],
    'sufixo novo'    => ['cópia com um único número novo, a F-03d', 'reprova', [['F-03d']]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site não viaja no pacote: a guarda do catálogo só roda na árvore do kit');

it('[CT-05] todo item de feature do README da base e toda página do site chegam ao catálogo, ou a página está na lista das que não descrevem feature', function (string $fonte, string $idioma): void {
    $linhas  = linhasDoCatalogo($idioma);
    $tabela  = catalogoTabelaDoReadme($idioma);
    $lista   = catalogoPaginasSemFeature();
    $paginas = paginasDoSite($idioma);

    expect($tabela)->toHaveCount(46);

    [$tipo, $itens] = match ($fonte) {
        'lista de features do README da base'   => ['readme', array_keys($tabela)],
        'todas as páginas de docs/'             => ['paginas', array_keys($paginas)],
        'lista de features do README publicado' => ['readme', catalogoItensDoReadme((string) file_get_contents(base_path($idioma === 'pt' ? 'README.md' : 'README.en.md')))],
    };

    $veredito = catalogoConferirFonte($tipo, $itens, $linhas, $tabela, $lista);

    expect($veredito['aceita'])->toBeTrue('Motivos: '.implode(' | ', $veredito['motivos']));
    expect($veredito['conferidos'])->toBe(count($itens));

    foreach ($itens as $item) {
        expect($veredito['casados'])->toHaveKey($item);
    }

    if ($fonte === 'lista de features do README da base') {
        expect($veredito['conferidos'])->toBe(46);

        foreach ($tabela as $titulo => [$numeros, $termo]) {
            if ($termo !== null) {
                expect(catalogoTemPalavra(catalogoTextoDaLinha(catalogoLinhaDe($linhas, $veredito['casados'][$titulo][0])), $termo))
                    ->toBeTrue("a linha casada com \"{$titulo}\" não traz \"{$termo}\"");
            }
        }

        expect(array_filter($tabela, fn (array $entrada): bool => $entrada[1] !== null))->toHaveCount(15);
    }

    if ($fonte === 'todas as páginas de docs/') {
        expect(count($paginas))->toBeGreaterThanOrEqual(39);

        foreach ($veredito['casados'] as $pagina => $numeros) {
            if ($numeros === ['lista']) {
                continue;
            }

            $semExtensao = $pagina === 'index.md' ? '' : (string) preg_replace('#(/index)?\.md$#', '', $pagina);

            foreach ($numeros as $numero) {
                expect(catalogoPaginasDaLinha(catalogoLinhaDe($linhas, $numero)))->toContain($semExtensao);
            }
        }

        expect(array_filter(array_keys($lista), fn (string $pagina): bool => preg_match('#^(recursos|autenticacao)/#', $pagina) === 1 && ! str_ends_with($pagina, '/index.md')))->toBe([]);
    }

    if ($fonte === 'lista de features do README publicado') {
        expect(count($itens))->toBeGreaterThanOrEqual(46);

        foreach (array_diff($itens, array_keys($tabela)) as $novo) {
            expect($tabela[$novo][1] ?? null)->not->toBeNull("o item novo \"{$novo}\" não tem termo na tabela da guarda");
        }
    }
})->with([
    'README da base pt'   => ['lista de features do README da base', 'pt'],
    'README da base en'   => ['lista de features do README da base', 'en'],
    'páginas de docs/pt/' => ['todas as páginas de docs/', 'pt'],
    'páginas de docs/en/' => ['todas as páginas de docs/', 'en'],
    'README publicado pt' => ['lista de features do README publicado', 'pt'],
    'README publicado en' => ['lista de features do README publicado', 'en'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site e o README não viajam no pacote: a guarda das fontes só roda na árvore do kit');

it('[CT-35] a guarda das fontes reprova o item casado com a feature errada, a página escondida na lista e o motivo de enfeite', function (string $mundo, array $nomeados): void {
    $linhas = linhasDoCatalogo('pt');
    $tabela = catalogoTabelaDoReadme('pt');
    $lista  = catalogoPaginasSemFeature();
    $tipo   = 'readme';
    $itens  = array_keys($tabela);

    switch ($mundo) {
        case 'cópia da tabela que casa "Dock de atalhos" com a F-06':
            expect(catalogoTemPalavra(catalogoTextoDaLinha(catalogoLinhaDe($linhas, 'F-06')), 'dock'))->toBeFalse();
            $tabela['Dock de atalhos'] = [['F-06'], 'dock'];
            break;
        case 'cópia da tabela que casa "Dock de atalhos" com a F-56':
            expect(catalogoTextoDaLinha(catalogoLinhaDe($linhas, 'F-56')))->toContain('docker');
            $tabela['Dock de atalhos'] = [['F-56'], 'dock'];
            break;
        case 'cópia da tabela que casa "Tutorial guiado" com a F-199':
            expect(array_column($linhas, 0))->not->toContain('F-199');
            $tabela['Tutorial guiado'] = [['F-199'], 'tutorial'];
            break;
        case 'cópia do README pt com o item "Mapa interativo" fora da tabela':
            $readme = (string) preg_replace(
                '/^(## O que já vem pronto[^\r\n]*)/mu',
                "$1\n\n- **Mapa interativo**: os três painéis e os fluxos do kit em diagramas interativos",
                (string) file_get_contents(base_path('README.md')),
                1,
            );
            $itens = catalogoItensDoReadme($readme);
            expect($itens)->toContain('Mapa interativo');
            break;
        default:
            $tipo  = 'paginas';
            $itens = array_keys(paginasDoSite('pt'));
            $lista = array_replace($lista, match ($mundo) {
                'cópia da lista com a página recursos/dock-de-atalhos.md, com motivo'                    => ['recursos/dock-de-atalhos.md' => 'a página só mostra um botão flutuante'],
                'cópia da lista com a página comecar/index.md, sem o motivo'                             => ['comecar/index.md' => null],
                'cópia da lista com a página comecar/index.md, com o motivo "—"'                         => ['comecar/index.md' => '—'],
                'cópia da lista com a página comecar/index.md, com o motivo "não se aplica", sem porquê' => ['comecar/index.md' => 'não se aplica'],
                'cópia da lista com a página comecar/index.md, com o motivo "N/A"'                       => ['comecar/index.md' => 'N/A'],
                'cópia da lista com a página comecar/index.md, com o motivo "not applicable"'            => ['comecar/index.md' => 'not applicable'],
                'cópia da lista com a página comecar/index.md, com o motivo "Does not apply"'            => ['comecar/index.md' => 'Does not apply'],
                'cópia da lista com a página comecar/index.md, com o motivo 0, que não é texto'          => ['comecar/index.md' => 0],
            });
    }

    $veredito = catalogoConferirFonte($tipo, $itens, $linhas, $tabela, $lista);

    expect($veredito['aceita'])->toBeFalse('a guarda aceitou a cópia: '.$mundo);
    catalogoNomeia($veredito['motivos'], $nomeados);
})->with([
    'termo ausente da linha casada'                                    => ['cópia da tabela que casa "Dock de atalhos" com a F-06', [['Dock de atalhos', 'F-06']]],
    'termo só como pedaço de palavra'                                  => ['cópia da tabela que casa "Dock de atalhos" com a F-56', [['Dock de atalhos', 'F-56']]],
    'F-nn inexistente'                                                 => ['cópia da tabela que casa "Tutorial guiado" com a F-199', [['Tutorial guiado', 'F-199']]],
    'página de recurso na lista'                                       => ['cópia da lista com a página recursos/dock-de-atalhos.md, com motivo', [['recursos/dock-de-atalhos.md']]],
    'motivo ausente'                                                   => ['cópia da lista com a página comecar/index.md, sem o motivo', [['comecar/index.md']]],
    'motivo de enfeite (—)'                                            => ['cópia da lista com a página comecar/index.md, com o motivo "—"', [['comecar/index.md']]],
    'motivo de enfeite (não se aplica)'                                => ['cópia da lista com a página comecar/index.md, com o motivo "não se aplica", sem porquê', [['comecar/index.md']]],
    'item novo sem F-nn'                                               => ['cópia do README pt com o item "Mapa interativo" fora da tabela', [['Mapa interativo']]],
    'motivo de enfeite, tabela do Setup Global (N/A)'                  => ['cópia da lista com a página comecar/index.md, com o motivo "N/A"', [['comecar/index.md']]],
    'motivo de enfeite, tabela do Setup Global (not applicable)'       => ['cópia da lista com a página comecar/index.md, com o motivo "not applicable"', [['comecar/index.md']]],
    'motivo de enfeite, tabela do Setup Global (Does not apply)'       => ['cópia da lista com a página comecar/index.md, com o motivo "Does not apply"', [['comecar/index.md']]],
    'motivo de enfeite, tabela do Setup Global (0, que não é texto)'   => ['cópia da lista com a página comecar/index.md, com o motivo 0, que não é texto', [['comecar/index.md']]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site e o README não viajam no pacote: a guarda das fontes só roda na árvore do kit');

it('[CT-35] a guarda das fontes decide cada entrada da tabela do motivo de enfeite do Setup Global como a tabela, pela motivoDeEnfeite() única', function (mixed $motivo, string $resultado): void {
    $lista = array_replace(catalogoPaginasSemFeature(), ['comecar/index.md' => $motivo]);

    expect(motivoDeEnfeite($motivo))->toBe($resultado === 'reprova', 'a motivoDeEnfeite() de tests/Pest.php decide a entrada '.var_export($motivo, true).' diferente da tabela do Setup Global');

    $veredito = catalogoConferirFonte('paginas', array_keys(paginasDoSite('pt')), linhasDoCatalogo('pt'), catalogoTabelaDoReadme('pt'), $lista);

    if ($resultado === 'reprova') {
        expect($veredito['aceita'])->toBeFalse('a guarda aceitou como motivo a entrada de enfeite '.var_export($motivo, true));
        catalogoNomeia($veredito['motivos'], [['comecar/index.md', 'motivo de enfeite']]);

        return;
    }

    expect($veredito['casados'])->toHaveKey('comecar/index.md');
    expect($veredito['casados']['comecar/index.md'])->toBe(['lista']);
    expect(array_values(array_filter($veredito['motivos'], fn (string $motivoDaGuarda): bool => str_contains($motivoDaGuarda, 'comecar/index.md'))))->toBe([]);
    expect($veredito['aceita'])->toBeTrue('Motivos: '.implode(' | ', $veredito['motivos']));
})->with([
    'vazio'                                => ['', 'reprova'],
    'ausente ou nulo'                      => [null, 'reprova'],
    'só travessão (—)'                     => ['—', 'reprova'],
    'hífen e espaço (- )'                  => ['- ', 'reprova'],
    'reticências (...)'                    => ['...', 'reprova'],
    'não se aplica'                        => ['não se aplica', 'reprova'],
    'nao se aplica'                        => ['nao se aplica', 'reprova'],
    'not applicable'                       => ['not applicable', 'reprova'],
    'Not applicable.'                      => ['Not applicable.', 'reprova'],
    'does not apply'                       => ['does not apply', 'reprova'],
    'Does not apply'                       => ['Does not apply', 'reprova'],
    'N/A'                                  => ['N/A', 'reprova'],
    'n/a'                                  => ['n/a', 'reprova'],
    'o número 0, que não é texto'          => [0, 'reprova'],
    'não se aplica: com o porquê (motivo)' => ['não se aplica: a classe é abstrata e vazia', 'aceita'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório das páginas do site não viaja no pacote: a guarda das fontes só roda na árvore do kit');
