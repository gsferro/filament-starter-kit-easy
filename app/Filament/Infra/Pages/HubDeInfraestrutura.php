<?php

declare(strict_types=1);

namespace App\Filament\Infra\Pages;

use App\Filament\Concerns\DescobreCardsDoPainel;
use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use BackedEnum;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use Bityukov\CommandCenter\Filament\Pages\Commands;
use Bityukov\CommandCenter\Filament\Pages\History;
use Bityukov\CommandCenter\Filament\Resources\CommandRecordResource;
use Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource;
use Filament\Pages\Dashboard;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use LaBoiteACode\DependencyGraph\Filament\Pages\DependencyGraphPage;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Resources\ComposerReleasePackageResource;
use Promethys\Revive\Pages\RecycleBin;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;
use Tapp\FilamentMailLog\Resources\MailLogResource;

/**
 * Porta de entrada do /infra: os destinos do painel em grade, não em árvore.
 *
 * É o painel que mais precisa: quatro grupos de navegação e as páginas próprias de sete plugins
 * (health, backups, filas, logs, auditoria, trilha de acesso, central de comandos, grafo de
 * dependências, releases). "Onde vejo os backups?" é uma pergunta real aqui.
 *
 * O hub SOMA à barra lateral, não a substitui: nenhum item sai da navegação. Esconder da sidebar
 * o que está no hub quebraria a busca ⌘K e obrigaria dois cliques onde havia um (ADR-01 da wiki
 * `hub-de-navegacao-em-cards`).
 *
 * Os cartões saem de `cardsDoPainel()`, que filtra por `canAccess()` de cada destino — ver o
 * cabeçalho de `App\Filament\Concerns\DescobreCardsDoPainel` para por que isso não pode ser
 * `CardItem::make()` à mão.
 *
 * ## Por que esta é a única que NÃO depende de `config('kit.hub')`
 *
 * Os hubs de /admin e /app nascem desligados: o kit inicial não precisa de grade de cartões para
 * oito destinos, e o /app de um projeto de verdade nasce vazio. Aqui são dezesseis, em quatro
 * grupos, e metade tem rótulo de plugin de terceiro sem tradução ("audits", "Exception",
 * "Manage commands", "Run history") — a árvore da barra lateral não responde "onde vejo os
 * backups?" e a grade com descrição responde.
 *
 * A assimetria é decisão do mantenedor, registrada em ADR-03 da wiki `hub-de-cards-opcional`.
 * **Não acrescente `canAccess()` com a flag aqui**: há um caso de teste que fica vermelho se
 * alguém "corrigir" a inconsistência (`tests/Kit/HubDeCardsTest.php`, o cenário com a flag
 * desligada).
 *
 * ## A PERMISSÃO é outra coisa, e ela vale
 *
 * A proibição acima é sobre a **flag**. `View:HubDeInfraestrutura` sempre existiu no banco e no
 * checkbox de `/admin/shield/roles`, e até a 0.18.9 não decidia nada — o `canAccess()` default do
 * Filament é `return true`. Agora decide, por `ExigePermissaoDaTela`, e o cenário guarda de ADR-03
 * (`tests/Kit/HubDeCardsTest.php:110`, com a flag desligada) segue verde porque a persona dele tem o
 * papel `infra`, que carrega a permissão. Ver ADR-06 da wiki `permissoes-de-telas-e-acoes`.
 */
class HubDeInfraestrutura extends CardsPage
{
    use DescobreCardsDoPainel;
    use ExigePermissaoDaTela;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    // Sem grupo e com sort negativo: fica no topo, acima dos quatro grupos, como porta de entrada.
    protected static ?int $navigationSort = -10;

    /** @var int|string|array<string, int|string> */
    protected static int|string|array $columns = 3;

    // O painel com mais destinos é o único onde um campo de busca paga o próprio espaço.
    protected static bool $searchable = true;

    protected static ?string $searchPlaceholder = 'Buscar destino...';

    protected static ?string $title = 'Central de infraestrutura';

    protected static ?string $navigationLabel = 'Central de infraestrutura';

    /**
     * A classe que dá escopo ao `resources/css/filament/cards.css`.
     *
     * Sem ela a grade sai sem estilo nenhum — e com o HTML correto, sem erro, sem aviso.
     * O CSS é escopado (e não global) para não atropelar a marcação de outros plugins que
     * usem os mesmos nomes de utilitária.
     *
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['kit-cards-page'];
    }

    /**
     * O que cada destino deste painel serve, por FQCN.
     *
     * ## Por que um mapa aqui, e não um método na classe de cada destino
     *
     * Treze dos dezesseis destinos deste painel são **vendor** — o monitor de filas, as exceções,
     * a saúde, os backups, os logs, a lixeira e as três telas da central de comandos, entre
     * outras. Não há onde declarar a frase na classe, e um `getNavigationDescription()` no trait
     * só funcionaria para os três destinos que são código do kit: a grade sairia com três frases
     * e treze buracos.
     *
     * A frase também entra no `data-search-text` de cada cartão
     * (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:264`), então
     * a busca desta página passa a encontrar por assunto — "fila", "restaurar", "e-mail" — e não
     * só pelo rótulo. É o que faz a descrição valer a pena num painel com `$searchable = true`.
     *
     * Plugin novo neste painel entra **sem** frase, e é para isso que existe o cenário
     * "nenhum cartão fica sem descrição" em `tests/Kit/HubDeCardsTest.php`: ele fica vermelho e
     * pede a linha aqui. Chave órfã (plugin removido) é inócua — nunca casa e nada acusa.
     *
     * Ver ADR-04 da wiki `hub-de-cards-opcional`.
     *
     * @return array<class-string, string>
     */
    protected static function descricoesDosDestinos(): array
    {
        return [
            // Observabilidade
            HealthCheckResults::class   => 'Estado atual dos checks de banco, cache, fila, agendador, disco e ambiente.',
            QueueMonitorResource::class => 'Histórico dos jobs da fila: o que rodou, o que falhou e quanto tempo levou.',
            ExceptionResource::class    => 'Exceções agrupadas por tipo e frequência, com stack trace e dados da requisição.',
            Pulse::class                => 'Requisições e queries lentas, uso de servidor e vazão das filas.',

            // IA
            AiRunResource::class => 'Ledger das execuções de IA: prompt, resposta, tokens, custo em USD e duração.',

            // Trilhas
            AuthenticationLogResource::class => 'Quem entrou, de qual IP e em qual dispositivo — e as tentativas que falharam.',
            AuditResource::class             => 'Trilha de alterações dos registros: quem mudou o quê, com o valor antes e depois.',
            MailLogResource::class           => 'Todo e-mail que a aplicação enviou, com destinatário, assunto e corpo.',
            LogsExplorer::class              => 'Os arquivos de log da aplicação, lidos pela interface, sem acesso ao servidor.',

            // Sem grupo — ficam no topo do menu
            BackupRunsPage::class => 'Últimos backups: quando rodaram, o tamanho e se o destino respondeu.',

            // Sistema
            ComposerReleasePackageResource::class => 'Versões novas dos pacotes instalados, comparadas com o composer.lock.',
            Commands::class                       => 'Roda um comando Artisan pela interface, dentro da lista autorizada.',
            History::class                        => 'Histórico de execução dos comandos: quem rodou, com quais argumentos e a saída.',
            CommandRecordResource::class          => 'Cadastro dos comandos liberados para a central de comandos.',
            RecycleBin::class                     => 'Registros apagados com soft delete, com restauração registro por registro.',
            DependencyGraphPage::class            => 'Mapa dos models, relações, resources e painéis da aplicação.',
        ];
    }

    /**
     * @return array<CardGroup>
     */
    protected static function getCards(): array
    {
        return static::cardsDoPainel(
            excluir: [static::class, Dashboard::class],
            descricoes: static::descricoesDosDestinos(),
        );
    }
}
