<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O botão "Voltar ao topo" — presença e posição.
 *
 * O que estes casos provam: que o render hook está registrado GLOBALMENTE e que o offset
 * por painel é o certo. Ambos são HTML do servidor, e provam-se em milissegundos.
 *
 * O que eles NÃO provam, de propósito: que o botão aparece. Ele nasce com `x-show="visivel"`
 * e `visivel = false`, então está sempre no DOM e sempre invisível até o Alpine reagir ao
 * scroll. Isso é `tests/Browser/VoltarAoTopoTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioCom('master_global'));
});

/**
 * RQ-04 em um caso: os TRÊS painéis, não só o que alguém lembrou.
 *
 * A âncora é `data-voltar-ao-topo` e não o texto: o rótulo do botão é `aria-label`, e não há
 * texto visível para procurar.
 */
it('injeta o botão em todos os painéis', function (string $painel): void {
    $this->get("/{$painel}")
        ->assertSuccessful()
        ->assertSee('data-voltar-ao-topo', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * RQ-05 — "todas as pages", e a parte que importa: as telas que NÃO são nossas.
 *
 * Auditoria, log de autenticação, exceções e monitor de filas vêm de plugin. É justamente
 * por elas que a implementação é render hook global e não trait por página — um trait não
 * alcança `ListRecords` de vendor sem estender a classe do pacote.
 *
 * Se alguém trocar o hook por um registro por painel ou por um trait, este caso é o que cai.
 */
it('alcança também as telas que vêm de plugin', function (string $rota): void {
    $this->get($rota)
        ->assertSuccessful()
        ->assertSee('data-voltar-ao-topo', escape: false);
})->with([
    'auditoria'        => '/infra/audits',
    'log de acesso'    => '/infra/authentication-logs',
    'exceções'         => '/infra/exceptions',
    'monitor de filas' => '/infra/queue-monitors',
    'permissões'       => '/admin/shield/roles',
])->group('kit');

/**
 * O offset existe por causa do chat, e o chat só está no /app.
 *
 * `bottom-24` = 96px levanta o botão acima do widget de chat, que ocupa de 24px a 80px do
 * rodapé (`bottom-6` + `h-14`). Nos outros dois painéis não há chat, e `bottom-6` é o certo.
 *
 * Se este caso cair, provavelmente a posição do chat mudou — e aí os dois arquivos precisam
 * mudar juntos.
 */
it('levanta o botão no /app para não colidir com o chat', function (): void {
    $this->get('/app')
        ->assertSuccessful()
        ->assertSee('bottom-24', escape: false);
})->group('kit');

it('mantém o botão na posição padrão onde não há chat', function (string $painel): void {
    $this->get("/{$painel}")
        ->assertSuccessful()
        ->assertSee('bottom-6', escape: false)
        ->assertDontSee('bottom-24', escape: false);
})->with(['admin', 'infra'])->group('kit');

/**
 * A camada é a menor de todas, e isso é decisão, não descuido.
 *
 * Acima dele: topbar e sidebar mobile (z-30), chat e modal (z-40), slide-over e notificações
 * (z-50). Como o Blade entra em `BODY_END`, depois de todos no DOM, empatar em z-30 faria o
 * botão pintar por cima do overlay da sidebar no celular.
 *
 * Com modal, chat ou menu aberto, "voltar ao topo" não faz sentido — ficar embaixo é correto.
 */
it('mantém o botão abaixo de topbar, sidebar, modal e chat', function (): void {
    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('z-20', escape: false);
})->group('kit');

/**
 * Acessibilidade mínima: o botão não tem texto, então o rótulo é obrigatório.
 *
 * Sem `aria-label`, um leitor de tela anuncia "botão" e mais nada.
 */
it('rotula o botão para leitor de tela', function (): void {
    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('aria-label="Voltar ao topo"', escape: false);
})->group('kit');
