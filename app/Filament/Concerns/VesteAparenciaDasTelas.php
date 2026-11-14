<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Tenant;
use App\Support\AparenciaDasTelasDeAcesso;
use App\Support\IdentidadeDoKit;
use App\Support\Paineis;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Illuminate\Support\Facades\Log;

/**
 * Veste uma tela de autenticação com a aparência escolhida em "Telas de acesso".
 *
 * Toda página do kit que usa o layout do `caresome/filament-auth-designer` usa este trait e
 * sobrescreve `getAuthDesignerConfig()` numa linha — `aplicarAparencia(parent::…)`. Uma linha por
 * página, e não um `getAuthDesignerConfig()` aqui, porque duas das páginas (`TelaBloqueio`,
 * `TelaDoisFatores`) usam a trait do pacote diretamente, e dois traits com o mesmo método na
 * mesma classe exigiriam `insteadof` em cada uma.
 *
 * A página que conhece uma organização sobrescreve `organizacaoDaAparencia()` e passa o candidato
 * por `organizacaoParaAparencia()`, que aplica as três condições de P-06: tenancy ligada, painel
 * `app` (inclui as páginas únicas `/login` e `/cadastro`, servidas sob `panel:app`) e organização
 * ativa. Fora delas, vale a aparência da instalação. A aparência NUNCA decide acesso.
 *
 * Os três métodos públicos (`aparenciaDaTela()`, `corDoPainelDeMarca()`, `logosDoPainelDeMarca()`)
 * existem para o partial `media` lê-los do componente. São ações chamáveis por `$wire` como todo
 * método público de componente Livewire; por isso a cor e as logos saem vazias fora do layout
 * `painel` (e as logos, com a logo desligada) — nada além do que a própria tela mostra (P-16).
 */
trait VesteAparenciaDasTelas
{
    /** @var array<string, mixed>|null */
    private ?array $aparenciaResolvida = null;

    private bool $aparenciaFoiResolvida = false;

    private ?Tenant $organizacaoDaAparenciaResolvida = null;

    private bool $organizacaoDaAparenciaFoiResolvida = false;

    /**
     * A aparência que esta tela usa — `null` = clássico. Memoizada por instância: o layout, o
     * partial e o `getAuthDesignerConfig()` perguntam no mesmo render.
     *
     * @return array<string, mixed>|null
     */
    public function aparenciaDaTela(): ?array
    {
        if (! $this->aparenciaFoiResolvida) {
            $this->aparenciaResolvida    = $this->resolverAparencia();
            $this->aparenciaFoiResolvida = true;
        }

        return $this->aparenciaResolvida;
    }

    /**
     * A cor de fundo do painel de marca, já validada — ver `AparenciaDasTelasDeAcesso::corDoPainel()`.
     *
     * `null` fora do layout `painel`: o método é ação chamável por `$wire`, e no clássico a tela não
     * mostra cor nenhuma — devolvê-la ali revelaria a primária de uma organização a quem só testou
     * um `?org=` (P-16).
     */
    public function corDoPainelDeMarca(): ?string
    {
        if (! $this->ehPainel()) {
            return null;
        }

        return AparenciaDasTelasDeAcesso::corDoPainel($this->aparenciaDaTela(), $this->organizacaoDaAparenciaMemo());
    }

    /**
     * O par de logos do painel de marca: o da organização, com queda para a instalação.
     *
     * @return array{clara: ?string, escura: ?string}
     */
    public function logosDoPainelDeMarca(): array
    {
        // Só o que a tela mostra: nada fora do `painel` nem com a logo desligada (P-16).
        if (! $this->ehPainel() || ! (bool) ($this->aparenciaDaTela()['logo_no_painel'] ?? true)) {
            return ['clara' => null, 'escura' => null];
        }

        return IdentidadeDoKit::logosPara($this->organizacaoDaAparenciaMemo());
    }

    private function ehPainel(): bool
    {
        return ($this->aparenciaDaTela()['layout'] ?? null) === AparenciaDasTelasDeAcesso::LAYOUT_PAINEL;
    }

    /** @return array<string, mixed>|null */
    protected function resolverAparencia(): ?array
    {
        return AparenciaDasTelasDeAcesso::daOrganizacao($this->organizacaoDaAparenciaMemo());
    }

    /** A página que conhece uma organização sobrescreve. O padrão é a aparência da instalação. */
    protected function organizacaoDaAparencia(): ?Tenant
    {
        return null;
    }

    protected function aplicarAparencia(AuthDesignerConfig $config): AuthDesignerConfig
    {
        return AparenciaDasTelasDeAcesso::aplicar($config, $this->aparenciaDaTela());
    }

    /**
     * O candidato vale só sob as três condições de P-06. Havendo candidata, um `debug` no `tenancy`
     * diz por quê, como o da `TelaBloqueio`.
     */
    protected function organizacaoParaAparencia(?Tenant $candidata): ?Tenant
    {
        $motivo = match (true) {
            ! config('kit.tenancy.enabled')                      => 'sem_tenancy',
            Paineis::correnteOuPadrao()->getId() !== 'app'       => 'painel_sem_tenancy',
            ! $candidata instanceof Tenant                       => 'org_ausente',
            ! $candidata->ativo                                  => 'org_inativa',
            default                                              => 'ok',
        };

        // Só a recusa é explicada: sem candidata não há o que explicar, e um log a cada abertura de
        // login com organização válida seria volume que um visitante controla.
        if (! $candidata instanceof Tenant) {
            return null;
        }

        if ($motivo === 'ok') {
            return $candidata;
        }

        Log::channel('tenancy')->debug(
            '[VesteAparenciaDasTelas@organizacaoDaAparencia] Organizacao da aparencia resolvida | tenant: '.$candidata->getKey().' - motivo: '.$motivo,
            ['tenant_id' => $candidata->getKey(), 'motivo' => $motivo, 'pagina' => static::class],
        );

        return null;
    }

    private function organizacaoDaAparenciaMemo(): ?Tenant
    {
        if (! $this->organizacaoDaAparenciaFoiResolvida) {
            $this->organizacaoDaAparenciaResolvida    = $this->organizacaoDaAparencia();
            $this->organizacaoDaAparenciaFoiResolvida = true;
        }

        return $this->organizacaoDaAparenciaResolvida;
    }
}
