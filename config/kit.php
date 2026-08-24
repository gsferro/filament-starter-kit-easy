<?php

use App\Support\BooleanoDoEnv;
use App\Support\NumeroDoEnv;
use App\Support\ValidadeDoConvite;

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

    'version' => '0.19.3',

    /*
    |--------------------------------------------------------------------------
    | Repositório do kit
    |--------------------------------------------------------------------------
    | Origem consultada pelo `php artisan kit:update`. O vínculo é temporário:
    | o comando adiciona o remote, compara, aplica o que você aprovar e desfaz
    | tudo ao final — o projeto não fica com remote nem tags de terceiros.
    */

    'repository' => env('KIT_REPOSITORY') ?: 'https://github.com/gsferro/filament-starter-kit-easy.git',

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
    | Cor primária livre (hexadecimal)
    |--------------------------------------------------------------------------
    | A alternativa à lista fechada de cima, para quem tem uma cor de marca que
    | não está na paleta do Filament. Formato `#rgb` ou `#rrggbb`.
    |
    | **Esta chave VENCE a `cor_primaria`** quando as duas estão preenchidas. A
    | razão é que ela é a mais específica: quem digita um hexadecimal escolheu
    | aquela cor, enquanto o seletor da lista tem valor padrão e pode nunca ter
    | sido tocado. A precedência inversa tornaria a cor livre inalcançável em
    | toda instalação que escolheu cor no `kit:install`.
    |
    | Valor fora do formato é IGNORADO e a resolução cai para a `cor_primaria` —
    | mesma tolerância deliberada do nome, e pelo mesmo motivo: isto roda no boot
    | de todo painel, e `Color::generatePalette()` não valida nada antes de
    | passar o valor para `convertToOklch()`.
    |
    | O caminho normal de gravação é a tela /admin/configuracoes-do-kit; esta
    | chave é a semente e o plano B.
    */

    'cor_primaria_hex' => env('KIT_COR_PRIMARIA_HEX'),

    /*
    |--------------------------------------------------------------------------
    | Identidade visual da instalação
    |--------------------------------------------------------------------------
    | Caminhos no disco `public` (não URLs), gravados pela tela
    | /admin/configuracoes-do-kit. `App\Support\IdentidadeDoKit` os resolve para
    | URL e cai no padrão quando o arquivo declarado não existe no disco — um
    | <link rel="icon"> apontando para 404 no <head> de TODA página é pior que o
    | ícone padrão.
    |
    | `null` significa "sem arquivo próprio": logo e favicon somem (o Filament usa
    | o brand em texto e o ícone dele), e a arte do login cai em
    | `IdentidadeDoKit::ARTE_PADRAO`.
    |
    | O default da arte NÃO mora aqui de propósito: ele é um arquivo servido de
    | `public/`, e estas três chaves são caminhos no DISCO `public`
    | (storage/app/public). Misturar as duas origens numa chave só produziria um
    | valor que às vezes é `asset()` e às vezes é `Storage::url()` — o resolvedor
    | trata cada origem no lugar dela. Quem quiser outro padrão substitui o
    | arquivo public/images/auth/login.svg, como sempre.
    |
    | Sem `env()`: são caminhos de arquivo enviado pela tela, não escolha de
    | ambiente.
    |
    | Não confundir com a logo de uma ORGANIZAÇÃO (multi-tenancy): aquela é a
    | coluna `logo` do model Tenant, editada em /admin/organizacoes.
    */

    'identidade' => [
        'logo'          => null,
        'favicon'       => null,
        'arte_do_login' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults de TODA tabela do projeto
    |--------------------------------------------------------------------------
    | Lidos por `ConfiguraFilamentGlobal::configuraTable()`, que roda num
    | `Table::configureUsing()` — então valem também para as tabelas dos plugins
    | de terceiros, onde não há como editar o `table()` do resource.
    |
    | Editáveis em /admin/configuracoes-do-kit. Estas chaves fecham o TODO que
    | vivia no topo daquele trait. "Densidade de tabela" NÃO está aqui porque não
    | existe no Filament 5: nenhuma ocorrência de `density` em
    | vendor/filament/tables/src, e `Enums/` não tem enum de densidade. O que o
    | framework oferece de controle visual de aperto é `striped()`, abaixo.
    |
    | ## Por que `BooleanoDoEnv` e não `(bool) env()`
    |
    | `(bool) env('CHAVE', true)` é o MESMO defeito que .ai/rules/config.md
    | documenta para inteiros: o segundo argumento do `env()` só vale para chave
    | AUSENTE. Com `CHAVE=` (presente, vazia — o que sobra quando alguém apaga o
    | valor e esquece o `=`), `env()` devolve string vazia, `(bool) ''` é false, e
    | o default `true` nunca entra.
    |
    | E `filter_var(..., FILTER_NULL_ON_FAILURE) ?? true` NÃO conserta: foi a
    | primeira correção escrita aqui, e as três chaves nasceram DESLIGADAS com
    | ela. O filtro do PHP trata `null` e `''` como false, não como falha, e o
    | `??` nunca dispara. Está medido no docblock de App\Support\BooleanoDoEnv.
    |
    | O `KIT_HUB` mais abaixo continua com `(bool) env()` porque o default dele é
    | `false` — ali o defeito é inócuo, e trocar por trocar esconderia a regra.
    */

    'tabelas' => [
        'paginacao'                => NumeroDoEnv::positivo(env('KIT_TABELA_PAGINACAO'), 10),
        'listrada'                 => BooleanoDoEnv::comPadrao(env('KIT_TABELA_LISTRADA'), true),
        'persistir_filtros'        => BooleanoDoEnv::comPadrao(env('KIT_TABELA_PERSISTIR_FILTROS'), true),
        'colunas_redimensionaveis' => BooleanoDoEnv::comPadrao(env('KIT_TABELA_COLUNAS_REDIMENSIONAVEIS'), true),
    ],

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
        'label'        => env('KIT_TENANCY_LABEL') ?: 'Organização',
        'label_plural' => env('KIT_TENANCY_LABEL_PLURAL') ?: 'Organizações',

        // Segmento do cadastro no painel admin: /admin/organizacoes.
        // Só a URL do CRUD — o endereço do painel de negócio é /app/{slug do
        // próprio registro}, definido em cada tenant.
        'slug' => env('KIT_TENANCY_SLUG') ?: 'organizacoes',

    ],

    /*
    |--------------------------------------------------------------------------
    | Registro aberto no painel /app
    |--------------------------------------------------------------------------
    | Desligado por default, e isso não é cautela genérica: enquanto for `false`,
    | `/app/register` só responde a quem traz um token de convite válido — o
    | comportamento que o kit sempre teve. Ligar aqui abre uma porta PÚBLICA que
    | cria conta, e é a única superfície anônima de escrita do kit.
    |
    | Quem se registra por ela recebe UM papel, o `panel_user`, e nada além:
    | nenhum acesso a /admin nem a /infra. Quem administra pode mudar isso depois,
    | na tela de usuários.
    |
    |   'aprovacao_manual' — o cadastro nasce PENDENTE e não entra em painel
    |   nenhum até alguém aprovar na tela de usuários. Com `false`, entra na hora.
    |
    |   'verificar_email'  — exige e-mail validado no /app. Liga a tela de
    |   confirmação (que nasceu vestida e com a rota desligada) e o middleware do
    |   Filament. ATENÇÃO: o middleware vale para TODO usuário do /app, não só
    |   para os recém-registrados — quem estiver sem `email_verified_at` é barrado.
    |   Quem vem de convite nunca é afetado: `Convite::aceitar()` grava a coluna,
    |   porque o token já prova posse do endereço.
    |
    | Com multi-organização ligada, cada organização ainda precisa habilitar o
    | registro na tela dela (`tenants.registro_habilitado`), e o link carrega o
    | slug: /app/register?org={slug}. As duas condições valem juntas.
    |
    | `(bool) env()` é seguro aqui, ao contrário do `(int) env()` que
    | `.ai/rules/config.md` proíbe: com a chave presente e vazia (`KIT_REGISTRO=`)
    | o `env()` devolve string vazia, e `(bool) ''` é `false` — que é exatamente o
    | default desejado. Vazio e ausente colapsam no mesmo valor. O `(int) ''` é
    | `0`, e ali o zero significava outra coisa: era isso que matava o default.
    |
    | NÃO leia estas chaves direto. O ponto único é `App\Support\RegistroAberto`,
    | e há um caso de teste que reprova qualquer outro leitor — é ele que mantém a
    | troca para a página de Settings do /admin num arquivo só.
    */

    'registro' => [
        'habilitado'       => (bool) env('KIT_REGISTRO', false),
        'aprovacao_manual' => (bool) env('KIT_REGISTRO_APROVACAO_MANUAL', false),
        'verificar_email'  => (bool) env('KIT_REGISTRO_VERIFICAR_EMAIL', false),
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
    | Hub de navegação em cards
    |--------------------------------------------------------------------------
    | Desligado por default. Liga as páginas hub — uma grade de cartões com os
    | destinos do painel, em vez da árvore da barra lateral — nos painéis
    | /admin e /app.
    |
    | Desligado porque o kit inicial não precisa: /admin tem oito destinos e o
    | /app de um projeto de verdade nasce vazio. Grade de cartões paga o próprio
    | espaço quando há MUITOS caminhos e a pergunta "onde vejo X?" é real.
    |
    | ## O /infra NÃO depende desta chave
    |
    | Lá o hub nasce ligado, e de propósito: são dezesseis destinos em quatro
    | grupos, metade com rótulo de plugin de terceiro não traduzido. É o único
    | painel do kit onde a grade ganha da árvore no default.
    |
    | Ligando aqui, os três painéis passam a ter hub — nada mais precisa ser
    | editado, porque o FilamentCardsPlugin já está registrado nos três e o CSS
    | dos cartões já é publicado.
    |
    | O pacote (harvirsidhu/filament-cards) fica instalado com a chave
    | desligada: ele é o dono do padrão "página que exibe links e fluxos em
    | grade", e wikis/receitas.md tem a receita de quando usá-lo.
    */

    'hub' => (bool) env('KIT_HUB', false),

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
        'excecoes_em_dias' => NumeroDoEnv::diasOuDesligado(env('KIT_RETENCAO_EXCECOES_DIAS'), 14),
        'emails_em_dias'   => NumeroDoEnv::diasOuDesligado(env('KIT_RETENCAO_EMAILS_DIAS'), 14),

        /*
         * Histórico de import e export (`imports`, `exports`, `failed_import_rows`).
         *
         * 30 dias, e não 14 como as duas acima: o histórico de importação é o que
         * responde "quem escreveu isso em massa na semana passada", e a pergunta costuma
         * chegar depois do fechamento do mês.
         *
         * A poda do export **apaga o arquivo**, não só a linha. Sem isso o disco cresce
         * para sempre com CSV que ninguém mais consegue baixar, porque o link de download
         * é assinado e a linha que o autorizava já foi.
         *
         * Zero ou negativo desliga a poda, sem apagar nada por engano.
         */
        'importacoes_em_dias' => NumeroDoEnv::diasOuDesligado(env('KIT_RETENCAO_IMPORTACOES_DIAS'), 30),
        'exportacoes_em_dias' => NumeroDoEnv::diasOuDesligado(env('KIT_RETENCAO_EXPORTACOES_DIAS'), 30),
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
    | Tela de login: login social e rodape
    |--------------------------------------------------------------------------
    | Antes do bloco de convites de proposito: quem le este arquivo de cima para
    | baixo precisa encontrar o interruptor do login social ANTES de ler que "o
    | convite e a unica forma de alguem de fora virar usuario". As duas coisas
    | conversam, e a ordem evita a leitura errada.
    |
    | DEFAULT DESLIGADO. Ligar aqui nao poe o botao no ar sozinho: as tres chaves
    | de `services.google` tambem precisam estar preenchidas. Sao duas condicoes
    | em conjuncao, e a razao de serem duas e que elas falham por motivos
    | diferentes - interruptor desligado e escolha, credencial vazia e descuido.
    |
    | Com o interruptor desligado as rotas /auth/google/* respondem 404. Esconder
    | o botao nao basta: a URL e fixa, publica e conhecida, e "escondido no HTML"
    | nao e barreira. Ver ADR-03.
    |
    | O QUE O LOGIN SOCIAL FAZ, e o que ele nao faz: ele AUTENTICA quem ja tem
    | conta com aquele e-mail, verificado no provedor. Ele NAO cria conta enquanto
    | o registro aberto estiver desligado - o exemplo updateOrCreate da propria
    | documentacao do Socialite transformaria qualquer pessoa com conta Google em
    | usuaria do sistema, contornando o convite. Ver ADR-06.
    |
    | Por que filter_var e nao um cast de bool - MEDIDO, e mais estreito do que
    | parece. O Env::getOption() do Laravel ja converte "true"/"false"/"(false)"/
    | "null"/"empty" em valor PHP de verdade
    | (vendor/laravel/framework/src/Illuminate/Support/Env.php:252-262), entao
    | KIT_SOCIALITE_GOOGLE=false ja chega aqui como boolean false e um cast de
    | bool acertaria. Os tres irmaos deste arquivo - tenancy.enabled, demo e hub -
    | usam cast de bool e NAO estao errados: para todo valor documentado no
    | .env.example os dois jeitos dao o mesmo resultado. Nao "conserte" os tres.
    |
    | A diferenca aparece so nos valores que o Laravel NAO reconhece, e ela e de
    | direcao: "off", "no" e qualquer lixo dao TRUE no cast de bool e FALSE no
    | filter_var. Ou seja, o cast falha ABERTO e o filter_var falha FECHADO.
    |
    | Para as tres chaves irmas isso e gosto. Aqui nao e: este interruptor abre
    | uma superficie PUBLICA de OAuth, e "off" e um valor que gente escreve. Um
    | interruptor de seguranca que liga sozinho por causa de um valor
    | irreconhecivel e o tipo de default que ninguem descobre a tempo.
    |
    | Nao vale extrair uma classe para isto (ha App\Support\NumeroDoEnv para
    | inteiro, porque la o significado do zero muda por chave): aqui e uma
    | chamada da stdlib, e o significado e o mesmo em toda chave booleana.
    |
    | O rodape e TEXTO, nunca HTML: ele e renderizado numa pagina publica e nao
    | autenticada, e sai escapado. Ver ADR-09.
    |
    | Estas chaves sao o DESTINO da tela de Settings, nao o lugar final. Quem le
    | as tres e App\Support\ConfiguracaoDoLogin, o ponto unico de ligacao: no dia
    | em que o Settings existir, so o corpo daqueles tres metodos muda.
    */

    'login' => [
        'google' => [
            'habilitado' => filter_var(env('KIT_SOCIALITE_GOOGLE', false), FILTER_VALIDATE_BOOLEAN),
        ],

        'rodape' => env('KIT_LOGIN_RODAPE'),
    ],

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
        /*
         * A coerção vive em `App\Support\ValidadeDoConvite` e não aqui, por dois motivos
         * medidos — o defeito que ela conserta e o teste que ela viabiliza. Os dois estão
         * escritos no docblock da classe. Resumo: valor VAZIO na env fazia o convite nascer
         * expirado, e a regra escrita nesta linha só era testável montando `putenv()` à mão,
         * o que passava localmente e falhava no CI.
         */
        'validade_em_dias' => ValidadeDoConvite::emDias(env('KIT_CONVITE_VALIDADE_DIAS')),

        /*
         * Máximo de endereços por lote na tela "Convidar em massa". Com
         * QUEUE_CONNECTION=sync cada e-mail é um handshake SMTP DENTRO do request:
         * a 200-400 ms por endereço, cem encostam nos 30 s de max_execution_time e
         * o operador leva 504 com metade do lote enviada. Subir daqui exige worker
         * de fila rodando.
         */
        'limite_do_lote' => NumeroDoEnv::positivo(env('KIT_CONVITE_LIMITE_LOTE'), 100),

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
        'name'     => env('KIT_ADMIN_NAME') ?: 'Administrador',
        'email'    => env('KIT_ADMIN_EMAIL') ?: 'admin@example.com',
        'password' => env('KIT_ADMIN_PASSWORD') ?: 'password',
    ],

];
