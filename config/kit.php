<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versão do kit que originou este projeto
    |--------------------------------------------------------------------------
    | Marca de nascença: o `kit:update` usa isto para saber a partir de qual
    | versão comparar e mostrar só o que o KIT mudou — sem confundir com o que
    | você mudou no seu projeto.
    |
    | O `kit:update` grava este número sozinho ao final de cada atualização —
    | você não precisa editar à mão. Sem a chave, ele cai na comparação direta
    | contra a árvore de trabalho, que é mais ruidosa.
    */

    'version' => '0.16.9',

    /*
    |--------------------------------------------------------------------------
    | Repositório do kit
    |--------------------------------------------------------------------------
    | Origem consultada pelo `php artisan kit:update`. O vínculo é temporário:
    | o comando adiciona o remote, compara, aplica o que você aprovar e desfaz
    | tudo ao final — o projeto não fica com remote nem tags de terceiros.
    */

    'repository' => env('KIT_REPOSITORY', 'https://github.com/gsferro/filament-starter-kit-easy.git'),

    /*
    |--------------------------------------------------------------------------
    | Cor primária dos painéis
    |--------------------------------------------------------------------------
    | Nome de uma constante da paleta do Filament (Filament\Support\Colors\Color):
    | Amber, Blue, Cyan, Emerald, Fuchsia, Indigo, Lime, Orange, Pink, Purple,
    | Red, Rose, Sky, Slate, Teal, Violet.
    |
    | Vazio = o padrão do Filament (âmbar). O `kit:install` grava esta chave a
    | partir da pergunta de customização, e trocar depois é editar o .env.
    |
    | Vale para os TRÊS painéis. A cor de uma ORGANIZAÇÃO (multi-tenancy) continua
    | vencendo esta dentro de /app/{slug}: ela é registrada mais tarde no ciclo,
    | no `bootUsing()` do AppPanelProvider — ver o comentário de lá.
    |
    | Nome fora da lista é ignorado, e o painel volta ao padrão em vez de morrer
    | com "Undefined constant" em toda página.
    */

    'cor_primaria' => env('KIT_COR_PRIMARIA'),

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy
    |--------------------------------------------------------------------------
    | Desligado por default: o kit nasce single-tenant. Ligue com
    | `php artisan kit:tenancy` — ele escreve `enabled`, liga os papéis por
    | tenant (`permission.teams`) e recria o banco.
    |
    | Com o modo ligado, o painel /app passa a ser /app/{tenant} e o usuário só
    | enxerga os tenants aos quais está vinculado. /admin e /infra seguem globais.
    |
    | Não ligue `enabled` à mão num projeto já migrado: as tabelas de permissão
    | só ganham a coluna de tenant quando `permission.teams` está ativo ANTES do
    | migrate. Use o comando.
    |
    | ## Código em inglês, interface em português
    |
    | O CÓDIGO usa o vocabulário da API do Filament — model `Tenant`, tabela
    | `tenants`, coluna `tenant_id`, métodos `getTenants()`/`canAccessTenant()`.
    | Assim a documentação oficial do Filament se lê sem tradução mental.
    |
    | O que o USUÁRIO vê é o que estiver aqui embaixo. O default é "Organização",
    | mas cada projeto troca para o termo do seu negócio — Empresa, Cliente,
    | Escola, Unidade, Loja — sem tocar numa linha de código.
    */

    'tenancy' => [

        'enabled' => (bool) env('KIT_TENANCY', false),

        // Rótulos exibidos na interface (menu, títulos, formulários, mensagens).
        'label'        => env('KIT_TENANCY_LABEL', 'Organização'),
        'label_plural' => env('KIT_TENANCY_LABEL_PLURAL', 'Organizações'),

        // Segmento do cadastro no painel admin: /admin/organizacoes.
        // Só a URL do CRUD — o endereço do painel de negócio é /app/{slug do
        // próprio registro}, definido em cada tenant.
        'slug' => env('KIT_TENANCY_SLUG', 'organizacoes'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Cenário de demonstração
    |--------------------------------------------------------------------------
    | Ligado por `php artisan kit:tenancy --demo`, que cria duas organizações,
    | três usuários e alguns projetos para você VER o isolamento funcionando.
    |
    | É esta chave que faz o resource de Projetos aparecer no painel /app. Sem
    | ela o painel nasce vazio, que é o desenho do kit: ninguém sabe o que o seu
    | projeto vai construir, e um resource de exemplo no menu de um projeto de
    | verdade é lixo que alguém vai ter de limpar.
    |
    | Desligar aqui tira a demo da vista sem apagar nada. Para removê-la de vez,
    | apague os arquivos que o `kit:tenancy` lista ao final.
    |
    | Só tem efeito com a multi-organização ligada: a demo É o cenário de
    | tenancy, e um Projeto sem tenant não demonstra isolamento nenhum.
    */

    'demo' => (bool) env('KIT_DEMO', false),

    /*
    |--------------------------------------------------------------------------
    | Observabilidade — retenção
    |--------------------------------------------------------------------------
    | Quanto tempo as trilhas que o kit GRAVA sobrevivem. Não é preferência de
    | gosto: as duas tabelas abaixo crescem por evento e guardam dado sensível.
    |
    | `excecoes` alimenta /infra/exceptions (bezhansalleh/filament-exceptions).
    | A tabela cresce por REQUEST com defeito — um bug em laço enche o disco em
    | horas — e o stack trace guardado pode conter parâmetro de request, logo
    | pode conter dado pessoal.
    |
    | `emails` alimenta /infra (tapp/filament-maillog). Mais delicada ainda: o
    | CORPO do e-mail é gravado, e o convite de acesso carrega o link de aceite.
    |
    | Os dois defaults são 14 dias, alinhados ao `days` da rotação de log em
    | config/logging.php: a trilha morre junto com o log que a originou, não
    | depois dele.
    |
    | Quem APLICA a retenção é `model:prune`, agendado em routes/console.php.
    | Sem o agendador rodando, o número aqui é só uma intenção declarada.
    |
    | Zero ou negativo desliga a poda daquela trilha — e aí a tabela cresce sem
    | teto, o que é uma escolha, não um esquecimento.
    */

    'retencao' => [
        'excecoes_em_dias' => (int) env('KIT_RETENCAO_EXCECOES_DIAS', 14),
        'emails_em_dias'   => (int) env('KIT_RETENCAO_EMAILS_DIAS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idiomas do painel
    |--------------------------------------------------------------------------
    | Lista de locales oferecidos no seletor de idioma
    | (bezhansalleh/filament-language-switch), nos três painéis e nas telas de
    | autenticação.
    |
    | **Um único idioma esconde o seletor** — é assim que o kit nasce, e é a
    | razão de isto ser uma LISTA e não um booleano: quem quer a feature declara
    | o segundo idioma, e o dado liga o botão. Não há flag para esquecer ligada
    | com um idioma só.
    |
    | Antes de acrescentar `en` aqui, saiba o que você recebe: a tradução cobre a
    | camada do Filament e dos pacotes (laravel-lang/common), NÃO os rótulos do
    | próprio kit. "Administrador Geral", "Acesso ao painel /app", os títulos dos
    | hubs e os labels dos resources são strings pt-BR escritas no código — há
    | dez `__()` em todo o app. Com `en` ligado hoje, metade da tela troca de
    | idioma e a outra metade não.
    |
    | Internacionalizar o kit é trabalho declarado e ainda não feito. Ver
    | wikis/pacotes-ranking.md, item 6 do Tier S.
    */

    'idiomas' => ['pt_BR'],

    /*
    |--------------------------------------------------------------------------
    | Convites de acesso
    |--------------------------------------------------------------------------
    | O convite é a única forma de alguém de fora virar usuário: a tela de
    | registro do painel /app recusa quem não traz um token válido.
    |
    | O token é de uso único e vale pelo prazo abaixo. Prazo curto reduz a
    | janela de um link vazado (encaminhado, esquecido na caixa de entrada);
    | prazo longo evita reenvio para quem só demorou a ver o e-mail. Sete dias
    | é o meio-termo — troque no .env, não aqui.
    |
    | Lembre que o envio depende de MAIL_MAILER configurado (o default `log`
    | escreve o convite em storage/logs e não manda nada) e de um worker de
    | fila rodando, porque a notificação é enfileirável.
    */

    'convites' => [
        'validade_em_dias' => (int) env('KIT_CONVITE_VALIDADE_DIAS', 7),

        /*
         * Máximo de endereços por lote na tela "Convidar em massa". Com
         * QUEUE_CONNECTION=sync cada e-mail é um handshake SMTP DENTRO do request:
         * a 200-400 ms por endereço, cem encostam nos 30 s de max_execution_time e
         * o operador leva 504 com metade do lote enviada. Subir daqui exige worker
         * de fila rodando.
         */
        'limite_do_lote' => (int) env('KIT_CONVITE_LIMITE_LOTE', 100),

        /*
        | Dias, contados do ENVIO, em que cada lembrete do convite pendente é
        | devido. O lembrete manda um SEGUNDO link, paralelo ao do envio: nada é
        | invalidado e o prazo não é renovado, então o e-mail que a pessoa já tem
        | continua valendo.
        |
        | Lista vazia desliga a feature. Todo dia aqui precisa ser MENOR que
        | `validade_em_dias`: com validade 3 e lembrete em D+3 o convite expira
        | antes de o lembrete ser devido, e nenhum lembrete sai — sem erro nenhum.
        |
        | O teto de lembretes por convite é a quantidade de dias desta lista. Não
        | existe um segundo botão de máximo: dois botões discordam.
        |
        | Quem chama é `kit:convites-lembrar`, agendado em routes/console.php.
        */
        'lembretes_dias' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('KIT_CONVITE_LEMBRETES_DIAS', '3,5')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Usuário inicial
    |--------------------------------------------------------------------------
    | Criado pelo UsuarioAdminSeeder com o papel master_global. Troque a senha
    | antes de expor o ambiente — ou defina as variáveis abaixo no .env antes
    | de rodar o `kit:install`.
    */

    'admin' => [
        'name'     => env('KIT_ADMIN_NAME', 'Administrador'),
        'email'    => env('KIT_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('KIT_ADMIN_PASSWORD', 'password'),
    ],

];
