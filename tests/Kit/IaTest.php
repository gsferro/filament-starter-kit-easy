<?php

use App\Ai\Agents\Assistente;
use App\Ai\Exceptions\AgenteInativoException;
use App\Ai\Exceptions\AgenteSemGuardrailsException;
use App\Ai\Guardrails\GuardrailRegistry;
use App\Models\AgenteIa;
use App\Models\User;
use Database\Seeders\AssistenteSeeder;
use Database\Seeders\GuardaPromptSeeder;

/**
 * O catálogo é o contrato da camada de IA: quem muda uma linha de `agentes_ia`
 * muda o comportamento em produção sem deploy. Estes testes fixam as garantias
 * que impedem isso de virar um agente cru chegando ao provider.
 */
it('semeia o catálogo de agentes de forma idempotente', function (): void {
    $this->seed([AssistenteSeeder::class, GuardaPromptSeeder::class]);
    $this->seed([AssistenteSeeder::class, GuardaPromptSeeder::class]);

    expect(AgenteIa::count())->toBe(2)
        ->and(AgenteIa::doSlug('assistente'))->not->toBeNull()
        ->and(AgenteIa::doSlug('guarda-prompt'))->not->toBeNull();
});

it('resolve os guardrails declarados no catálogo', function (): void {
    $this->seed(AssistenteSeeder::class);

    $guardrails = AgenteIa::doSlug('assistente')->guardrails;

    expect($guardrails)->not->toBeEmpty()
        ->and(GuardrailRegistry::resolver($guardrails))->toHaveCount(count($guardrails));
});

it('recusa guardrail desconhecido em vez de silenciar', function (): void {
    GuardrailRegistry::resolver(['nao_existe']);
})->throws(InvalidArgumentException::class);

it('bloqueia agente sem guardrails (fail-closed)', function (): void {
    $this->seed(AssistenteSeeder::class);

    AgenteIa::doSlug('assistente')->update(['guardrails' => []]);

    (new Assistente(new User))->middleware();
})->throws(AgenteSemGuardrailsException::class);

it('bloqueia agente ausente do catálogo', function (): void {
    (new Assistente(new User))->instructions();
})->throws(RuntimeException::class);

it('recusa responder quando o agente está inativo', function (): void {
    $this->seed(AssistenteSeeder::class);

    AgenteIa::doSlug('assistente')->update(['ativo' => false]);

    (new Assistente(new User))->instructions();
})->throws(AgenteInativoException::class);
