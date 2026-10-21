<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

use Database\Seeders\OnboardingSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingStep;

/**
 * Base dos seeders filhos do tutorial guiado — um filho por feature do kit, chamado pelo
 * `Database\Seeders\OnboardingSeeder` (wiki `onboarding-guiado`, RQ-05).
 *
 * Cada filho acrescenta o SEU passo à jornada "Primeiros passos" do painel em que a feature vive
 * (uma jornada por painel, RQ-18): a jornada nasce no primeiro filho que a pede.
 *
 * ## `firstOrCreate`, e não o `updateOrCreate` dos outros seeders do kit
 *
 * De propósito (RQ-25): a jornada, o passo e a condição se identificam pela chave, e rodar o
 * seeder de novo cria só o que falta — nunca sobrescreve o que já existe, inclusive o texto que
 * alguém editou na autoria (/admin/onboarding-flows). O conteúdo semeado é o ponto de partida; a
 * autoria é de quem administra a instalação.
 *
 * ## Por Eloquent, sempre
 *
 * Os eventos `saved` dos models do pacote limpam o cache das definições
 * (`Onboarding::flushCache()`); `insert()` deixaria jornada velha no cache por até uma hora.
 *
 * Nenhum filho usa factory/faker (`DatabaseSeeder`): a imagem Docker roda `--no-dev`.
 */
abstract class SeederDeOnboarding extends Seeder
{
    /** A ordem das jornadas no checklist, quando a pessoa tem mais de uma. */
    private const ORDEM_DOS_PAINEIS = ['app' => 10, 'admin' => 20, 'infra' => 30];

    /** A jornada "Primeiros passos" do painel — criada na primeira vez que um filho a pede. */
    protected function jornada(string $painel): OnboardingFlow
    {
        return OnboardingFlow::query()->firstOrCreate(
            ['key' => "primeiros-passos-{$painel}"],
            [
                'panel_id'       => $painel,
                'title'          => self::texto('Primeiros passos', 'Getting started'),
                'description'    => self::texto(
                    'Um passeio pelas telas deste painel — cada passo mostra onde fica e para que serve.',
                    'A walk through the screens of this panel — each step shows where it is and what it is for.',
                ),
                'icon'           => 'heroicon-o-map',
                'is_active'      => true,
                'is_dismissible' => true,
                'sort_order'     => self::ORDEM_DOS_PAINEIS[$painel] ?? 90,
            ],
        );
    }

    /**
     * O passo de uma feature na jornada do painel. A chave é única dentro da jornada; o mesmo
     * passo em três painéis são três passos (um por jornada).
     *
     * @param  array<string, mixed>  $atributos  colunas de `onboarding_steps` (título, tour, modo de conclusão…)
     */
    protected function passo(string $painel, string $chave, array $atributos): void
    {
        $jornada = $this->jornada($painel);

        $passo = OnboardingStep::query()->firstOrCreate(
            ['flow_id' => $jornada->getKey(), 'key' => $chave],
            [...['is_active' => true, 'is_required' => true, 'visibility_condition' => $this->visibilidadePadrao($chave)], ...$atributos],
        );

        if (! $passo->wasRecentlyCreated) {
            Log::channel('onboarding')->debug(
                '[SeederDeOnboarding@passo] Passo já existia, preservado | passo: '.$chave,
                ['jornada' => $jornada->key, 'passo' => $chave],
            );
        }
    }

    /**
     * O passo de uma feature com TELA só aparece para quem abre a tela (P-11): a condição
     * `tela:{chave}` (`TutorialGuiado::condicoes()`) pergunta ao `canAccess()` da tela no painel
     * corrente — permissão, interruptor da feature (hub, agentes de IA, central de comandos,
     * demonstração) e multi-tenant numa pergunta só, a mesma que o menu faz. Passo sem tela
     * (busca, dock, bloqueio, troca de organização) declara a sua condição.
     */
    private function visibilidadePadrao(string $chave): ?string
    {
        $comTela = in_array(static::class, array_merge(...array_values(OnboardingSeeder::TELAS)), true);

        return $comTela ? "tela:{$chave}" : null;
    }

    /**
     * Uma condição gravada no banco — aparece em Onboarding → Condições e é editável lá. As
     * condições que leem config ou relação vivem em código (`App\Support\TutorialGuiado::condicoes()`).
     *
     * @param  array<string, mixed>  $atributos  colunas de `onboarding_conditions`
     */
    protected function condicao(string $chave, array $atributos): void
    {
        OnboardingCondition::query()->firstOrCreate(['key' => $chave], [...['is_active' => true], ...$atributos]);
    }

    /**
     * Texto nos dois idiomas em que o kit é documentado (P-01) — os mesmos de
     * `config('filament-onboarding.locales')`, para continuar editável na autoria.
     *
     * @return array{pt_BR: string, en: string}
     */
    protected static function texto(string $portugues, string $ingles): array
    {
        return ['pt_BR' => $portugues, 'en' => $ingles];
    }

    /**
     * Uma parada de tour. `$rota` é o NOME da rota (sobrevive à troca de slug; `{tenant}` é
     * preenchido pelo pacote). `$alvo` só dos que o pacote nomeia — `part:table`, `part:search`,
     * `action:submit`, `field:{nome}`, `column:{nome}` — ou nulo: sem alvo, o popover centraliza
     * e o texto continua legível. Nunca CSS do markup de vendor.
     *
     * @param  array{0: string, 1: string}  $titulo  [pt_BR, en]
     * @param  array{0: string, 1: string}  $corpo  [pt_BR, en]
     * @return array<string, mixed>
     */
    protected static function parada(string $rota, array $titulo, array $corpo, ?string $alvo = null, bool $opcional = false): array
    {
        return [
            'route'     => $rota,
            'url'       => null,
            'target'    => $alvo,
            'selector'  => null,
            'placement' => 'auto',
            'title'     => self::texto(...$titulo),
            'body'      => self::texto(...$corpo),
            'condition' => null,
            'advance'   => null,
            'optional'  => $opcional,
        ];
    }
}
