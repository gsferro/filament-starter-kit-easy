<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Illuminate\Contracts\View\View;

/**
 * A porta de entrada pública do kit, na rota `/`, no lugar da welcome padrão do Laravel.
 *
 * ## Por que uma Page de painel fora de painel
 *
 * A página NÃO é registrada em painel nenhum — por isso vive em `app/Filament/Pages`, que nenhum
 * `discoverPages()` varre (os três apontam para `Filament/Admin|App|Infra/Pages`). Registrá-la num
 * painel daria rota `/{painel}/boas-vindas`, item de navegação e permissão do Shield: três efeitos
 * que ninguém pediu.
 *
 * Mesmo assim ela é uma `Filament\Pages\Page`, e a rota carrega o middleware `panel:app`. Esse
 * middleware é o que faz o requisito "herdar o css e o darkmode já implementados" acontecer sem uma
 * linha de CSS nova: `SetUpPanel` chama `Filament::bootCurrentPanel()`, `Panel::boot()` registra a
 * paleta do projeto (`FilamentColor::register`, `vendor/filament/filament/src/Panel.php:95`) e o
 * `layout.base` do painel emite a folha do Filament, as fontes e o script de tema
 * (`vendor/filament/filament/resources/views/components/layout/base.blade.php:67-124`).
 *
 * MEDIDO: `@filamentStyles` sozinho NÃO traz a folha do Filament e IGNORA `KIT_COR_PRIMARIA` —
 * emite o âmbar do default com `Violet` na env. Ver ADR-01 da wiki `pagina-boas-vindas` e o caso
 * de teste que guarda isso (CT-15).
 *
 * ## Por que o layout `simple`
 *
 * O default de `Page` é `layout.index`, que traz barra lateral e menu de usuário. Numa página
 * pública isso é um menu vazio (todo `canAccess()` é falso para anônimo) e um menu de usuário sem
 * usuário. O `simple` é o layout das telas de autenticação: conteúdo centralizado, e a topbar dele
 * só existe sob `filament()->auth()->check()`
 * (`vendor/filament/filament/resources/views/components/layout/simple.blade.php:22` e `:30`).
 *
 * ## Nada de segredo aqui
 *
 * A rota `/` é anônima. `config('kit.admin.*')`, `config('database.*')`, `config('kit.repository')`,
 * `config('app.env')` e `config('mail.*')` NÃO entram nesta tela, e a lista completa com o motivo
 * de cada linha está no ADR-04 da wiki. `tests/Kit/BoasVindasTest.php` assere a AUSÊNCIA de cada
 * uma com valor sentinela plantado — acrescentar uma delas aqui deixa a suíte vermelha.
 */
class BoasVindas extends CardsPage
{
    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $title = 'Bem-vindo ao Starter Kit Easy';

    /**
     * Três, e não é gosto: `resources/css/filament/cards.css` cobre `grid-cols-1`, `md:grid-cols-2`,
     * `lg:grid-cols-3` e `xl:grid-cols-4`, e o cabeçalho dele declara que `$columns >= 5` monta o
     * nome da classe por interpolação e NUNCA teria CSS. Subir daqui produz uma grade sem estilo,
     * com o HTML correto e todo teste verde. CT-05 guarda isto.
     *
     * @var int|string|array<string, int|string>
     */
    protected static int|string|array $columns = 3;

    /**
     * O default do layout simples é `Width::Large` — largura de caixa de login
     * (`components/layout/simple.blade.php:7`), estreita demais para três cartões lado a lado.
     */
    protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

    /**
     * A classe que dá escopo ao `resources/css/filament/cards.css`.
     *
     * Sem ela a grade sai sem estilo nenhum — e com o HTML byte a byte correto, sem erro e sem
     * aviso. Mesma razão dos três hubs do kit; ver `.ai/rules/css-filament.md`.
     *
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['kit-cards-page'];
    }

    public function getSubheading(): ?string
    {
        return 'Três painéis prontos para usar. O acesso a cada um continua pedindo login.';
    }

    /**
     * As informações do kit, embaixo dos cartões.
     *
     * `getFooter()` e não uma view própria da página: a blade do `harvirsidhu/filament-cards` é
     * envelopada em `<x-filament-panels::page>` das linhas 109 a 397 e não tem slot para conteúdo
     * extra. O rodapé é o encaixe nativo desse envelope
     * (`vendor/filament/filament/resources/views/components/page/index.blade.php:129-131`), e usá-lo
     * evita copiar as 397 linhas do vendor.
     */
    public function getFooter(): ?View
    {
        return view('filament.pages.boas-vindas');
    }

    /**
     * Um cartão por painel do kit, apontando para a raiz de cada um.
     *
     * ## Estes cartões NÃO filtram por autorização, e isso é decisão
     *
     * `App\Filament\Concerns\DescobreCardsDoPainel` existe justamente para filtrar por
     * `canAccess()`, e a regra dele continua valendo para os hubs DENTRO de painel. Aqui ela não
     * serve: o visitante da rota `/` é anônimo por definição, todo `canAccess()` é falso, e o
     * resultado seria uma página com zero cartão.
     *
     * O que se aceita, então, é que a página confirme a um anônimo que existem `/admin` e `/infra`.
     * Isso já é público: os três painéis chamam `->login()`, o que registra `/app/login`,
     * `/admin/login` e `/infra/login` como telas públicas — visitadas sem autenticação pelo CT-B04
     * de `tests/Browser/TelasDoKitTest.php`. Nenhum caminho novo é revelado.
     *
     * NÃO replique este padrão num hub de painel. Ver ADR-03 da wiki `pagina-boas-vindas`.
     *
     * @return array<CardGroup|CardItem>
     */
    protected static function getCards(): array
    {
        return [
            static::cardDoPainel(
                painel: 'app',
                rotulo: 'Painel do negócio',
                icone: Heroicon::OutlinedBuildingOffice2,
                cor: 'primary',
                descricao: 'Onde o seu produto vive. Multi-organização, convites e o cadastro do dia a dia.',
            ),
            static::cardDoPainel(
                painel: 'admin',
                rotulo: 'Administração',
                icone: Heroicon::OutlinedUsers,
                cor: 'info',
                descricao: 'Usuários, papéis e permissões, convites, organizações e agentes de IA.',
            ),
            static::cardDoPainel(
                painel: 'infra',
                rotulo: 'Infraestrutura',
                icone: Heroicon::OutlinedServerStack,
                cor: 'gray',
                descricao: 'Filas, logs, exceções, backups, saúde da aplicação e o Pulse.',
            ),
        ];
    }

    /**
     * O cartão de um painel, com a URL resolvida pelo próprio painel.
     *
     * `getUrl()` e não `url('/app')` escrito à mão: é ele que resolve domínio próprio e prefixo de
     * organização. E ele é seguro para o visitante anônimo mesmo com a multi-organização ligada —
     * sem usuário autenticado o tenant fica nulo, os dois ramos de tenant não entram, o ramo da
     * rota `home` exige tenant e também não entra, e sobra o `url($this->getPath())` de
     * `vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:196`.
     *
     * O `??` existe só porque a assinatura devolve `?string`; nesses ramos ela não devolve nulo.
     *
     * As três cores de borda usadas em `getCards()` — `primary`, `info` e `gray` — são as que
     * `resources/css/filament/cards.css` cobre. Cor fora dessa lista produz um cartão sem borda,
     * com o HTML correto.
     */
    protected static function cardDoPainel(
        string $painel,
        string $rotulo,
        Heroicon $icone,
        string $cor,
        string $descricao,
    ): CardItem {
        $instancia = Filament::getPanel($painel);

        return CardItem::make($instancia->getUrl() ?? url($instancia->getPath()))
            ->label($rotulo)
            ->description($descricao)
            ->icon($icone)
            ->color($cor)
            ->badge('/'.$instancia->getPath());
    }

    /**
     * As informações do kit, em duas seções: o que este projeto personalizou e o que a config do
     * kit define.
     *
     * Resolvido pelo nome do método: `InteractsWithSchemas::cacheSchema()` reflete um método com
     * parâmetro `Schema` (`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:231-260`),
     * e é por isso que a view do rodapé consegue fazer `{{ $this->informacoesDoKit }}`.
     *
     * Sem registro Eloquent: `->state()` é o caminho documentado para valor estático
     * (doc do Filament 5, infolists/overview).
     */
    public function informacoesDoKit(Schema $schema): Schema
    {
        $tenancy = (bool) config('kit.tenancy.enabled');
        $demo    = (bool) config('kit.demo');
        $hub     = (bool) config('kit.hub');

        return $schema->components([
            Section::make('Este projeto')
                ->description('O que o kit:install personalizou. Sem o comando, você vê os padrões do kit.')
                ->icon(Heroicon::OutlinedSparkles)
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('nome_da_aplicacao')
                        ->label('Nome da aplicação')
                        ->state((string) config('app.name')),

                    TextEntry::make('cor_primaria')
                        ->label('Cor primária')
                        ->state(static::corPrimaria()),

                    TextEntry::make('multi_organizacao')
                        ->label('Multi-organização')
                        ->state($tenancy ? 'Ligada' : 'Desligada')
                        ->badge()
                        ->color($tenancy ? 'success' : 'gray'),

                    TextEntry::make('rotulo_da_organizacao')
                        ->label('Como a organização é chamada')
                        ->state(sprintf(
                            '%s / %s',
                            (string) config('kit.tenancy.label'),
                            (string) config('kit.tenancy.label_plural'),
                        )),

                    TextEntry::make('demo')
                        ->label('Cenário de demonstração')
                        ->state($demo ? 'Ligado' : 'Desligado')
                        ->badge()
                        ->color($demo ? 'success' : 'gray'),

                    TextEntry::make('hub')
                        ->label('Hub em cartões')
                        ->state($hub ? 'Ligado' : 'Desligado')
                        ->badge()
                        ->color($hub ? 'success' : 'gray'),
                ]),

            Section::make('Configuração do kit')
                ->description('Lida de config/kit.php — troque no .env, não no arquivo.')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('versao_do_kit')
                        ->label('Versão do kit')
                        ->state((string) config('kit.version')),

                    TextEntry::make('idiomas')
                        ->label('Idiomas do painel')
                        ->state(implode(', ', array_map('strval', (array) config('kit.idiomas')))),

                    TextEntry::make('validade_do_convite')
                        ->label('Validade do convite')
                        ->state(static::emDias((int) config('kit.convites.validade_em_dias'))),

                    TextEntry::make('lembretes_do_convite')
                        ->label('Lembretes do convite')
                        ->state(static::lembretes((array) config('kit.convites.lembretes_dias'))),

                    TextEntry::make('limite_do_lote')
                        ->label('Convites por lote')
                        ->state((string) (int) config('kit.convites.limite_do_lote')),

                    TextEntry::make('retencao')
                        ->label('Retenção de trilhas')
                        ->state(sprintf(
                            'exceções %s · e-mails %s · importações e exportações %s',
                            static::retencao((int) config('kit.retencao.excecoes_em_dias')),
                            static::retencao((int) config('kit.retencao.emails_em_dias')),
                            static::retencao((int) config('kit.retencao.importacoes_em_dias')),
                        )),
                ]),
        ]);
    }

    /**
     * O nome da cor escolhida, ou o padrão do Filament quando a chave está ausente OU vazia.
     *
     * Os dois estados caem no mesmo lugar porque `App\Support\CorPrimaria::paleta()` trata os dois
     * como "mantém o padrão" — testar só `=== null` deixaria uma linha em branco na string vazia,
     * que é o que sobra quando alguém apaga o valor do `.env` e esquece o `=`.
     */
    protected static function corPrimaria(): string
    {
        $nome = config('kit.cor_primaria');

        return is_string($nome) && $nome !== ''
            ? $nome
            : 'Âmbar (padrão do Filament)';
    }

    /** O singular existe porque "1 dias" é erro visível numa tela de boas-vindas. */
    protected static function emDias(int $dias): string
    {
        return $dias === 1 ? '1 dia' : $dias.' dias';
    }

    /**
     * Prazo de retenção, em que zero ou negativo DESLIGA a poda.
     *
     * `config/kit.php` promete isso por escrito, e `App\Support\NumeroDoEnv::diasOuDesligado()`
     * deixa o zero passar de propósito. Exibir "0 dias" mentiria sobre o comportamento — e é a
     * mesma fronteira que, escrita com o comparador errado, já apagou a trilha de exceções inteira
     * neste kit. Ver `.ai/rules/config.md`.
     */
    protected static function retencao(int $dias): string
    {
        return $dias > 0 ? static::emDias($dias) : 'Sem poda';
    }

    /**
     * Os dias de lembrete de convite, como ordinais. Lista vazia desliga a feature.
     *
     * @param  array<array-key, mixed>  $dias
     */
    protected static function lembretes(array $dias): string
    {
        if ($dias === []) {
            return 'Desligados';
        }

        $ordinais = array_values(array_map(
            static fn (mixed $dia): string => (int) $dia.'º',
            $dias,
        ));

        $ultimo = array_pop($ordinais);

        return ($ordinais === [] ? $ultimo : implode(', ', $ordinais).' e '.$ultimo).' dia';
    }
}
