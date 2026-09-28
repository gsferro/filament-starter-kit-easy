<?php

use App\Console\Commands\KitUpdate;

/**
 * Os agentes da esteira da `feature-wiki` que o Claude Code carrega são os da versão instalada das skills.
 *
 * Cada skill de `gsferro/laravel-ai-skills` traz o seu agente em `.ai/skills/{skill}/agents/`, mas o
 * Claude Code **só** carrega agente de `.claude/agents/` — e nem o `boost:add-skill` nem o
 * `boost:update` copiam para lá. A cópia é um passo à mão, de `.ai/skills/{skill}/agents/` para
 * `.claude/agents/`, e esquecê-lo não dá erro nenhum: a sessão segue com os agentes da versão
 * anterior.
 *
 * Desde a `feature-wiki` 4.0.0 isso deixou de ser só desatualização. Os cinco agentes `fw-*` declaram
 * um hook `PreToolUse` que roda `feature-wiki/scripts/guarda-subagente.sh` e nega, por construção, a
 * leitura e a edição fora do perfil de cada um. Agente copiado de antes da 4.0.0 não tem o bloco
 * `hooks:` — a cegueira volta a ser só instrução no prompt, e a suíte continua verde.
 */
it('todo agente das skills esta em .claude/agents, identico ao da skill', function (): void {
    $daSkill = glob(base_path('.ai/skills/*/agents/*.md')) ?: [];

    // CONTROLE POSITIVO: sem ele, um glob que não casa deixaria o caso verde sobre nada.
    expect(count($daSkill))->toBeGreaterThanOrEqual(5, 'nenhum agente em .ai/skills/*/agents — o laço abaixo mediria o vazio');

    foreach ($daSkill as $origem) {
        $copia = base_path('.claude/agents/'.basename($origem));

        expect(is_file($copia))->toBeTrue('o agente '.basename($origem).' não está em .claude/agents — rode `cp .ai/skills/*/agents/*.md .claude/agents/`');

        $normalizar = static fn (string $caminho): string => str_replace("\r\n", "\n", (string) file_get_contents($caminho));

        expect($normalizar($copia))->toBe($normalizar($origem), '.claude/agents/'.basename($origem).' é de outra versão da skill — rode `cp .ai/skills/*/agents/*.md .claude/agents/` e reabra a sessão');
    }
});

it('o script que o hook dos agentes roda existe onde o hook o procura', function (): void {
    $comHook = array_filter(
        glob(base_path('.claude/agents/fw-*.md')) ?: [],
        static fn (string $agente): bool => str_contains((string) file_get_contents($agente), 'guarda-subagente.sh'),
    );

    expect(count($comHook))->toBeGreaterThanOrEqual(5, 'nenhum agente fw-* declara o hook — o caso abaixo mediria o vazio');

    /*
     * O hook procura em `.ai/skills`, depois `.claude/skills`, depois `~/.claude/skills`, e sem o script
     * FALHA FECHADO: nega toda ferramenta, e a sessão cai no fallback `general-purpose`, sem cegueira
     * por construção. Os dois primeiros são do projeto e viajam com o kit; o terceiro é da máquina.
     */
    foreach (['.ai/skills', '.claude/skills'] as $raiz) {
        expect(is_file(base_path("{$raiz}/feature-wiki/scripts/guarda-subagente.sh")))
            ->toBeTrue("{$raiz}/feature-wiki/scripts/guarda-subagente.sh não existe — os agentes fw-* negariam toda ferramenta");
    }
});

/**
 * O pedido que abriu este arquivo: quem já instalou o kit recebe a skill nova e os agentes pelo
 * `kit:update`, e não só quem instala do zero.
 */
it('as skills, os agentes e o script do hook chegam pelo kit:update', function (): void {
    $cobertos = (new ReflectionClassConstant(KitUpdate::class, 'CAMINHOS_DO_KIT'))->getValue();

    $coberto = static fn (string $caminho): bool => array_filter(
        $cobertos,
        static fn (string $listado): bool => $caminho === $listado || str_starts_with($caminho, $listado.'/'),
    ) !== [];

    foreach ([
        '.ai/skills/feature-tickets/SKILL.md',
        '.claude/skills/feature-tickets/SKILL.md',
        '.claude/agents/fw-revisor-diff.md',
        '.ai/skills/feature-wiki/scripts/guarda-subagente.sh',
        '.claude/skills/feature-wiki/scripts/guarda-subagente.sh',
    ] as $caminho) {
        expect(is_file(base_path($caminho)))->toBeTrue("{$caminho} não existe — o caso abaixo afirmaria a entrega de um arquivo que não há");
        expect($coberto($caminho))->toBeTrue("{$caminho} não está coberto por KitUpdate::CAMINHOS_DO_KIT — quem atualiza pelo kit:update nunca o recebe");
    }
});
