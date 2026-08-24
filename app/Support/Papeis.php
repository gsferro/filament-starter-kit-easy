<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Como um papel é EXIBIDO.
 *
 * O nome do papel é chave: `master_global`, `admin_app`, `panel_user`. Ele é
 * assim de propósito — é o que vai em `assignRole()`, em `hasRole()`, nos seeders e nas
 * policies, e mudá-lo quebraria tudo isso de uma vez. O que não serve é mostrar a chave
 * para quem opera: "admin_app" é identificador, não rótulo.
 *
 * Então a chave fica, e a exibição vira Title Case. Aqui, e não repetido em cada tabela:
 * eram sete pontos quando esta classe nasceu, são DEZESSETE hoje — a tabela de papéis e o
 * título do registro dela, o cadastro de usuários dos dois painéis, os dois de convites, o
 * vínculo por organização, as caixas de entrada e dois widgets do dashboard /admin.
 * Dezessete cópias divergem, e a que divergir vai mostrar `panel_user` numa tela e
 * "Panel User" na de baixo.
 *
 * Não confie neste número: recontar com
 * `grep -rn 'Papeis::rotulo' app/ resources/ | wc -l`. Número parado em docblock é o que faz
 * o próximo agente concluir que a varredura está completa quando não está — o mesmo defeito
 * que `.ai/rules/filament.md` registra para a lista de subtração do `panel_user`.
 */
final class Papeis
{
    /**
     * Rótulos que o Title Case não acerta sozinho.
     *
     * `Str::headline('panel_user')` devolve "Panel User" — inglês, e sem dizer o que o
     * papel faz. Quem o tem opera o painel de negócio, então é isso que a tela mostra.
     *
     * Só entram aqui os papéis do kit cujo nome em inglês vazaria para a interface. O
     * resto continua derivado da chave, e um papel SEU criado em `/admin` não precisa
     * de entrada nenhuma.
     *
     * @var array<string, string>
     */
    private const ROTULOS = [
        'panel_user'    => 'Painel App',
        'admin_app'     => 'Administrador App',
        'master_global' => 'Administrador Geral',
    ];

    /** `master_global` → "Master Global"; `panel_user` → "Painel App". */
    public static function rotulo(?string $nome): string
    {
        if (blank($nome)) {
            return '—';
        }

        return self::ROTULOS[$nome] ?? Str::headline($nome);
    }

    /**
     * O painel que o papel abre, dito por extenso.
     *
     * `painel` é a coluna que DÁ o acesso (`User::canAccessPanel()` a lê), e mostrá-la
     * como "app" fazia parecer categoria, não permissão. Vazio não é coringa: papel sem
     * painel não abre painel nenhum, e o rótulo precisa dizer isso.
     */
    public static function rotuloDoPainel(?string $painel): string
    {
        return blank($painel) ? 'Não abre painel' : 'Acesso ao painel /'.$painel;
    }
}
